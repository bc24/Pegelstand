<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Reports;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Auth\Crypto;
use Pegelstand\Jobs\ReportJob;
use Pegelstand\Mail\Mailer;
use Pegelstand\Reports\ReportBuilder;
use Pegelstand\Reports\ReportSubscriptions;
use Pegelstand\Settings\SettingsStore;
use Pegelstand\Settings\SiteRepository;
use Pegelstand\Stats\Aggregator;
use Pegelstand\Stats\DashboardService;
use Pegelstand\Stats\ReferrerClassifier;
use Pegelstand\Tests\Integration\SiteTestCase;
use Pegelstand\Tests\Support\FakeSmtpServer;

final class ReportJobTest extends SiteTestCase
{
    private SettingsStore $settings;
    private ReportSubscriptions $abos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settings = new SettingsStore($this->db, new Crypto('base64:' . base64_encode(str_repeat('k', 32))));
        $this->abos = new ReportSubscriptions($this->db);
        $this->db->run('INSERT INTO ' . $this->db->table('users') . " (id, email, name, password_hash, role, created_at) VALUES (1, 'frank@beispiel.de', 'Frank', 'x', 'admin', NOW()), (2, 'gast@beispiel.de', 'Gast', 'x', 'viewer', NOW())");
    }

    private function job(): ReportJob
    {
        $dienst = new DashboardService($this->db, ReferrerClassifier::fromFile(PEGELSTAND_ROOT . '/resources/data/quellen.php'), ['DE' => 'Deutschland']);

        return new ReportJob($this->abos, new SiteRepository($this->db), new ReportBuilder($dienst), new Mailer($this->settings), $this->settings);
    }

    private function mailEinrichten(int $port): void
    {
        foreach (['host' => '127.0.0.1', 'port' => (string) $port, 'security' => 'none', 'from_address' => 'pegelstand@beispiel.de', 'from_name' => 'Pegelstand', 'base_url' => 'https://stats.beispiel.de'] as $k => $v) {
            $this->settings->set('mail.' . $k, $v);
        }
    }

    private function jetzt(string $utc): DateTimeImmutable
    {
        return new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    }

    private function daten(): void
    {
        $this->sitzung('2026-09-29 08:00:00', ['/', '/preise'], ['besucher' => 'A', 'land' => 'DE', 'referrer' => 'google.de']);
        $this->sitzung('2026-09-30 09:00:00', ['/'], ['besucher' => 'B', 'land' => 'DE']);
        $aggregator = new Aggregator($this->db);
        foreach (['2026-09-28', '2026-09-29', '2026-09-30', '2026-10-01', '2026-10-02', '2026-10-03', '2026-10-04'] as $tag) {
            $aggregator->aggregateDay(1, 'Europe/Berlin', $tag);
        }
    }

    public function testNeueAbonnentenBekommenKeinenSofortigenBericht(): void
    {
        $this->abos->replace(1, [1], ['1:weekly'], $this->jetzt('2026-10-05 06:00:00'));
        $this->daten();
        $server = new FakeSmtpServer();
        $this->mailEinrichten($server->port);
        try {
            $meldung = $this->job()->run($this->jetzt('2026-10-05 06:30:00'));
            $log = $server->inhalt();
        } finally {
            $server->stop();
        }

        self::assertSame('0 Berichte gesendet', $meldung, 'Der erste Bericht kommt erst zum nächsten Termin.');
        self::assertSame('', $log);
    }

    public function testFaelligerWochenberichtWirdEinmalGesendet(): void
    {
        $this->abos->replace(1, [1], ['1:weekly']);
        $this->db->run('UPDATE ' . $this->db->table('report_subscriptions') . " SET last_sent_at = '2026-09-28 06:00:00'");
        $this->daten();
        $server = new FakeSmtpServer();
        $this->mailEinrichten($server->port);
        try {
            $erste = $this->job()->run($this->jetzt('2026-10-05 06:30:00'));
            $zweite = $this->job()->run($this->jetzt('2026-10-05 07:30:00'));
            $text = '';
            foreach (explode("\n", $server->inhalt()) as $z) {
                if (str_starts_with($z, 'D> ')) {
                    $text .= substr($z, 3) . "\r\n";
                }
            }
            $text = quoted_printable_decode($text);
        } finally {
            $server->stop();
        }

        self::assertSame('1 Berichte gesendet', $erste);
        self::assertSame('0 Berichte gesendet', $zweite, 'Im selben Zeitraum kein zweiter Bericht.');
        self::assertStringContainsString('To: <frank@beispiel.de>', $text);
        self::assertStringContainsString(base64_encode('Wochenbericht für Test: 28.09.2026 bis 04.10.2026'), $text);
        self::assertMatchesRegularExpression('/Besucher\s+2\b/', $text);
        self::assertStringContainsString('Seitenaufrufe', $text);
        self::assertStringContainsString('https://stats.beispiel.de', $text);
        self::assertStringContainsString('Deutschland', $text);
        self::assertStringContainsString('/preise', $text);
    }

    public function testNurBerechtigteUndAktiveBekommenBerichte(): void
    {
        // Gast ist nicht für Website 1 freigegeben, sein Abonnement zählt nicht.
        $this->db->run('INSERT INTO ' . $this->db->table('report_subscriptions') . " (user_id, site_id, frequency, last_sent_at) VALUES (2, 1, 'weekly', '2026-09-01 00:00:00')");
        self::assertSame([], $this->abos->all());

        $this->db->run('INSERT INTO ' . $this->db->table('site_users') . ' (site_id, user_id) VALUES (1, 2)');
        self::assertCount(1, $this->abos->all());

        $this->db->run('UPDATE ' . $this->db->table('users') . ' SET disabled_at = NOW() WHERE id = 2');
        self::assertSame([], $this->abos->all(), 'Gesperrte Benutzer bekommen nichts.');
    }

    public function testOhneMailEinrichtungWirdNichtsVersucht(): void
    {
        $this->abos->replace(1, [1], ['1:weekly']);

        self::assertSame('E-Mail-Versand nicht eingerichtet', $this->job()->run($this->jetzt('2026-10-05 06:30:00')));
    }

    public function testFehlgeschlageneSendungWirdWiederholt(): void
    {
        $this->abos->replace(1, [1], ['1:weekly']);
        $this->db->run('UPDATE ' . $this->db->table('report_subscriptions') . " SET last_sent_at = '2026-09-28 06:00:00'");
        $this->daten();
        $this->mailEinrichten(1); // Port 1: niemand hört zu

        self::assertSame('0 Berichte gesendet, 1 fehlgeschlagen', $this->job()->run($this->jetzt('2026-10-05 06:30:00')));
        self::assertSame('2026-09-28 06:00:00', $this->db->fetchValue('SELECT last_sent_at FROM ' . $this->db->table('report_subscriptions')), 'Nicht als gesendet vermerkt.');
    }

    public function testAbonnementenVerwalten(): void
    {
        $this->abos->replace(1, [1], ['1:weekly', '1:monthly', '9:weekly']);
        self::assertEqualsCanonicalizing(['1:weekly', '1:monthly'], $this->abos->forUser(1), 'Fremde Websites werden ignoriert.');

        $this->abos->replace(1, [1], ['1:monthly']);
        self::assertSame(['1:monthly'], $this->abos->forUser(1));
    }
}
