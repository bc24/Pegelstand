<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

use Closure;
use DateTimeImmutable;
use Pegelstand\Core\Request;
use Pegelstand\Database\Database;
use Pegelstand\Geo\CountryLookup;

/**
 * Nimmt einen Messpunkt entgegen: prüft und filtert ihn, ordnet ihn einer Sitzung zu und speichert ihn.
 * Die IP-Adresse wird nur im Arbeitsspeicher für Hash, Ratenbegrenzung und Länderbestimmung genutzt.
 */
final class Collector
{
    public const MAX_BODY = 8192;
    public const SESSION_GAP_SECONDS = 1800;
    private const KIND_PAGEVIEW = 1;
    private const KIND_EVENT = 2;
    private const MAX_PROPS = 10;

    /** @var Closure(): DateTimeImmutable */
    private readonly Closure $clock;

    /**
     * @param (Closure(): DateTimeImmutable)|null $clock
     */
    public function __construct(
        private readonly Database $db,
        private readonly SaltService $salts,
        private readonly Dictionary $dictionary,
        private readonly UrlParser $urls,
        private readonly UserAgentParser $agents,
        private readonly BotFilter $bots,
        private readonly CountryLookup $geo,
        private readonly RateLimiter $limiter,
        private readonly ClientIp $clientIp,
        private readonly int $rateLimit = 300,
        ?Closure $clock = null,
    ) {
        $this->clock = $clock ?? static fn(): DateTimeImmutable => new DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function collect(Request $request): IngestResult
    {
        if (strlen($request->body) > self::MAX_BODY) {
            return IngestResult::TooLarge;
        }
        $daten = json_decode($request->body, true, 6);
        if (!is_array($daten)) {
            return IngestResult::BadRequest;
        }
        $publicId = $daten['s'] ?? null;
        $url = $daten['u'] ?? null;
        if (!is_string($publicId) || preg_match('/^[A-Za-z0-9]{8,16}$/', $publicId) !== 1 || !is_string($url) || strlen($url) > 2048) {
            return IngestResult::BadRequest;
        }

        $zeilen = $this->db->fetchAll(
            'SELECT id, domain, allowed_hosts, respect_dnt, respect_gpc FROM ' . $this->db->table('sites') . ' WHERE public_id = ?',
            [$publicId],
        );
        if ($zeilen === []) {
            return IngestResult::UnknownSite;
        }
        $site = $zeilen[0];
        $siteId = is_numeric($site['id']) ? (int) $site['id'] : 0;
        $hosts = new HostMatcher(is_string($site['domain']) ? $site['domain'] : '', is_string($site['allowed_hosts']) ? $site['allowed_hosts'] : null);

        $ziel = $this->urls->page($url);
        if ($ziel === null || !$hosts->accepts($ziel->host)) {
            return IngestResult::Ignored;
        }
        if (($site['respect_dnt'] ?? 0) == 1 && $request->header('DNT') === '1') {
            return IngestResult::Ignored;
        }
        if (($site['respect_gpc'] ?? 0) == 1 && $request->header('Sec-GPC') === '1') {
            return IngestResult::Ignored;
        }

        $userAgent = $request->header('User-Agent');
        if ($this->bots->isBot($userAgent)) {
            return IngestResult::Ignored;
        }
        $rohIp = $this->clientIp->resolve($request);
        $ip = IpAddress::normalize($rohIp);
        if ($ip === null) {
            return IngestResult::Ignored;
        }
        if ($this->istAusgeschlossen($siteId, $rohIp)) {
            return IngestResult::Ignored;
        }

        $jetzt = ($this->clock)();
        $salt = $this->salts->forTime($jetzt);
        if ($this->limiter->exceeded('ingest', VisitorHasher::rateKey($salt, $ip), $this->rateLimit, $jetzt)) {
            return IngestResult::RateLimited;
        }

        [$name, $eigenschaften] = $this->ereignis($daten);
        $zeit = $jetzt->format('Y-m-d H:i:s');
        $besucher = VisitorHasher::visitor($salt, $siteId, $ip, $userAgent);
        $pfadId = $this->dictionary->id('path', $ziel->path);
        $istEreignis = $name !== null;

        $sitzungId = $this->offeneSitzung($siteId, $besucher, $jetzt);
        if ($sitzungId === null) {
            $sitzungId = $this->neueSitzung($siteId, $besucher, $zeit, $pfadId, $ziel, $daten, $hosts, $userAgent, $rohIp, $istEreignis);
        } else {
            $spalte = $istEreignis ? 'custom_events = custom_events + 1' : 'pageviews = pageviews + 1';
            $this->db->run(
                'UPDATE ' . $this->db->table('sessions') . ' SET last_seen_at = ?, exit_path_id = ?, ' . $spalte . ' WHERE id = ?',
                [$zeit, $pfadId, $sitzungId],
            );
        }

        $this->db->run(
            'INSERT INTO ' . $this->db->table('events') . ' (site_id, session_id, occurred_at, kind, path_id, name_id) VALUES (?, ?, ?, ?, ?, ?)',
            [$siteId, $sitzungId, $zeit, $istEreignis ? self::KIND_EVENT : self::KIND_PAGEVIEW, $pfadId, $istEreignis ? $this->dictionary->id('event_name', $name) : null],
        );
        if ($istEreignis && $eigenschaften !== []) {
            $ereignisId = (int) $this->db->pdo->lastInsertId();
            foreach ($eigenschaften as $schluessel => $wert) {
                $this->db->run(
                    'INSERT IGNORE INTO ' . $this->db->table('event_props') . ' (event_id, key_id, value_id) VALUES (?, ?, ?)',
                    [$ereignisId, $this->dictionary->id('prop_key', (string) $schluessel), $this->dictionary->id('prop_value', $wert)],
                );
            }
        }

        return IngestResult::Accepted;
    }

    /**
     * @param array<mixed> $daten
     * @return array{0: ?string, 1: array<string, string>} Ereignisname (null bei Seitenaufruf) und Eigenschaften
     */
    private function ereignis(array $daten): array
    {
        $name = $daten['n'] ?? null;
        $name = is_string($name) ? trim(mb_substr($name, 0, 120)) : '';
        if ($name === '' || strtolower($name) === 'pageview' || preg_match('/[\x00-\x1F\x7F]/', $name) === 1) {
            return [null, []];
        }
        $eigenschaften = [];
        $roh = $daten['p'] ?? null;
        if (is_array($roh)) {
            foreach ($roh as $schluessel => $wert) {
                if (count($eigenschaften) >= self::MAX_PROPS) {
                    break;
                }
                if (!is_string($schluessel) || preg_match('/^[\p{L}\p{N}_.\- ]{1,64}$/u', $schluessel) !== 1 || !is_scalar($wert)) {
                    continue;
                }
                $text = is_bool($wert) ? ($wert ? 'true' : 'false') : trim((string) $wert);
                $text = mb_substr((string) preg_replace('/[\x00-\x1F\x7F]/', '', $text), 0, 255);
                if ($text !== '') {
                    $eigenschaften[$schluessel] = $text;
                }
            }
        }

        return [$name, $eigenschaften];
    }

    private function istAusgeschlossen(int $siteId, string $ip): bool
    {
        $bereiche = $this->db->fetchAll('SELECT ip_range FROM ' . $this->db->table('site_ip_exclusions') . ' WHERE site_id = ?', [$siteId]);
        foreach ($bereiche as $zeile) {
            if (is_string($zeile['ip_range']) && IpAddress::inRange($ip, $zeile['ip_range'])) {
                return true;
            }
        }

        return false;
    }

    private function offeneSitzung(int $siteId, string $besucher, DateTimeImmutable $jetzt): ?int
    {
        $grenze = gmdate('Y-m-d H:i:s', $jetzt->getTimestamp() - self::SESSION_GAP_SECONDS);
        $id = $this->db->fetchInt(
            'SELECT id FROM ' . $this->db->table('sessions')
            . ' WHERE site_id = ? AND visitor_hash = ? AND last_seen_at >= ? ORDER BY last_seen_at DESC LIMIT 1',
            [$siteId, $besucher, $grenze],
        );

        return $id > 0 ? $id : null;
    }

    /**
     * @param array<mixed> $daten
     */
    private function neueSitzung(
        int $siteId,
        string $besucher,
        string $zeit,
        int $pfadId,
        PageTarget $ziel,
        array $daten,
        HostMatcher $hosts,
        string $userAgent,
        string $ip,
        bool $istEreignis,
    ): int {
        $geraet = $this->agents->parse($userAgent);
        $referrer = null;
        $roh = $daten['r'] ?? null;
        if (is_string($roh) && $roh !== '' && strlen($roh) <= 2048) {
            $host = $this->urls->referrerHost($roh);
            if ($host !== null && !$hosts->accepts($host)) {
                $referrer = $this->dictionary->id('referrer', mb_substr($host, 0, 255));
            }
        }
        $utm = [];
        foreach (['source', 'medium', 'campaign', 'term', 'content'] as $feld) {
            $utm[] = isset($ziel->utm[$feld]) ? $this->dictionary->id('utm', $ziel->utm[$feld]) : null;
        }
        $this->db->run(
            'INSERT INTO ' . $this->db->table('sessions')
            . ' (site_id, visitor_hash, started_at, last_seen_at, entry_path_id, exit_path_id, pageviews, custom_events, referrer_id,'
            . ' utm_source_id, utm_medium_id, utm_campaign_id, utm_term_id, utm_content_id, country, device, browser_id, os_id)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $siteId, $besucher, $zeit, $zeit, $pfadId, $pfadId, $istEreignis ? 0 : 1, $istEreignis ? 1 : 0, $referrer,
                $utm[0], $utm[1], $utm[2], $utm[3], $utm[4],
                $this->geo->country($ip),
                $geraet->device,
                $geraet->browser !== null ? $this->dictionary->id('browser', $geraet->browser) : null,
                $geraet->os !== null ? $this->dictionary->id('os', $geraet->os) : null,
            ],
        );

        return (int) $this->db->pdo->lastInsertId();
    }
}
