<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Settings;

use Pegelstand\Auth\Totp;
use Pegelstand\Core\Csrf;
use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Tests\Integration\InstalliertTestCase;

final class EinstellungenTest extends InstalliertTestCase
{
    private function get(string $pfad): Response
    {
        return $this->app()->handle(new Request('GET', $pfad));
    }

    /**
     * @param array<string, mixed> $post
     */
    private function post(string $pfad, array $post = []): Response
    {
        return $this->app()->handle(new Request('POST', $pfad, post: ['_csrf' => (new Csrf($this->session))->token()] + $post));
    }

    private function anmelden(string $mail = 'frank@beispiel.de', string $pw = 'ein sehr langer satz'): void
    {
        $antwort = $this->post('/login', ['email' => $mail, 'password' => $pw]);
        self::assertSame(303, $antwort->status, $antwort->body);
    }

    /**
     * @param array<string, string> $query
     */
    private function export(string $pfad, array $query): Response
    {
        return $this->app()->handle(new Request('GET', $pfad, query: $query));
    }

    private function seitenOptionen(): string
    {
        $id = $this->db->fetchValue('SELECT public_id FROM ' . $this->db->table('sites') . ' ORDER BY id LIMIT 1');

        return is_string($id) ? $id : '';
    }

    public function testOhneAnmeldungUmleitungZumLogin(): void
    {
        foreach (['/einstellungen', '/einstellungen/websites', '/einstellungen/benutzer', '/einstellungen/konto'] as $pfad) {
            self::assertSame('/login', $this->get($pfad)->headers['Location'], $pfad);
        }
        self::assertSame('/login', $this->post('/einstellungen/websites', ['name' => 'x'])->headers['Location']);
    }

    public function testWebsiteAnlegenAendernUndLoeschen(): void
    {
        $this->anmelden();
        $this->db->run('DELETE FROM ' . $this->db->table('sites'));

        $schlecht = $this->post('/einstellungen/websites', ['name' => '', 'domain' => 'kein domain!', 'timezone' => 'Mars/Olympus', 'retention_days' => '5', 'allowed_hosts' => 'x y!z']);
        self::assertSame(422, $schlecht->status);
        self::assertStringContainsString('keine gültige Domain', $schlecht->body);
        self::assertStringContainsString('Wähle eine Zeitzone', $schlecht->body);
        self::assertSame(0, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('sites')));

        $ok = $this->post('/einstellungen/websites', ['name' => 'Mein Blog', 'domain' => 'https://Blog.Beispiel.de/', 'timezone' => 'Europe/Berlin', 'retention_days' => '365', 'allowed_hosts' => "*.beispiel.de\nshop.beispiel.de", 'respect_dnt' => '1']);
        self::assertSame(303, $ok->status);
        $id = $this->seitenOptionen();
        self::assertSame('/einstellungen/websites/' . $id, $ok->headers['Location']);
        $zeile = $this->db->fetchAll('SELECT * FROM ' . $this->db->table('sites'))[0];
        self::assertSame('blog.beispiel.de', $zeile['domain']);
        self::assertEquals(365, $zeile['retention_days']);
        self::assertEquals(1, $zeile['respect_dnt']);
        self::assertEquals(0, $zeile['respect_gpc']);

        $seite = $this->get('/einstellungen/websites/' . $id);
        self::assertSame(200, $seite->status);
        self::assertStringContainsString('data-site=&quot;' . $id . '&quot;', $seite->body);
        self::assertStringContainsString('Mein Blog', $seite->body);

        $this->post('/einstellungen/websites/' . $id, ['name' => 'Neu', 'domain' => 'blog.beispiel.de', 'timezone' => 'UTC', 'retention_days' => '100', 'allowed_hosts' => '']);
        $zeile = $this->db->fetchAll('SELECT * FROM ' . $this->db->table('sites'))[0];
        self::assertSame('Neu', $zeile['name']);
        self::assertNull($zeile['allowed_hosts']);
        self::assertEquals(0, $zeile['respect_dnt']);

        $siteNr = is_numeric($zeile['id']) ? (int) $zeile['id'] : 0;
        // Messdaten anlegen, damit das Löschen etwas zu tun hat.
        $this->db->run('INSERT INTO ' . $this->db->table('sessions') . ' (site_id, visitor_hash, started_at, last_seen_at, entry_path_id, exit_path_id) VALUES (?, ?, NOW(), NOW(), 1, 1)', [$siteNr, str_repeat('a', 16)]);
        $this->db->run('INSERT INTO ' . $this->db->table('events') . ' (site_id, session_id, occurred_at, kind, path_id) VALUES (?, 1, NOW(), 1, 1)', [$siteNr]);
        $this->db->run('INSERT INTO ' . $this->db->table('agg_daily') . ' VALUES (?, CURDATE(), 1, 1, 1, 0, 0, 0)', [$siteNr]);

        $falsch = $this->post('/einstellungen/websites/' . $id . '/loeschen', ['bestaetigung' => 'falsch.de']);
        self::assertSame(303, $falsch->status);
        self::assertSame(1, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('sites')));

        $this->post('/einstellungen/websites/' . $id . '/loeschen', ['bestaetigung' => 'blog.beispiel.de']);
        foreach (['sites', 'sessions', 'events', 'agg_daily'] as $t) {
            self::assertSame(0, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table($t)), $t);
        }
    }

    public function testAusschlussliste(): void
    {
        $this->anmelden();
        $id = $this->seitenOptionen();

        $this->post('/einstellungen/websites/' . $id . '/ausschluss', ['ip_range' => '192.0.2.0/24', 'label' => 'Büro']);
        $this->post('/einstellungen/websites/' . $id . '/ausschluss', ['ip_range' => 'quatsch']);
        self::assertSame(1, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('site_ip_exclusions')));
        self::assertStringContainsString('192.0.2.0/24', $this->get('/einstellungen/websites/' . $id)->body);

        $eid = $this->db->fetchInt('SELECT id FROM ' . $this->db->table('site_ip_exclusions'));
        $this->post('/einstellungen/websites/' . $id . '/ausschluss/' . $eid . '/loeschen');
        self::assertSame(0, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('site_ip_exclusions')));
    }

    public function testUnbekannteWebsiteIst404(): void
    {
        $this->anmelden();

        self::assertSame(404, $this->get('/einstellungen/websites/gibtesnicht')->status);
        self::assertSame(404, $this->post('/einstellungen/websites/gibtesnicht')->status);
    }

    public function testBenutzerAnlegenUndRechte(): void
    {
        $this->anmelden();
        $siteId = $this->db->fetchInt('SELECT id FROM ' . $this->db->table('sites'));

        $schlecht = $this->post('/einstellungen/benutzer', ['name' => 'Erika', 'email' => 'frank@beispiel.de', 'password' => 'kurz', 'password_repeat' => 'kurz']);
        self::assertSame(422, $schlecht->status);
        self::assertStringContainsString('zu kurz', $schlecht->body);
        self::assertStringContainsString('gehört schon einem Benutzer', $schlecht->body);

        $ok = $this->post('/einstellungen/benutzer', ['name' => 'Erika', 'email' => 'Erika@Beispiel.de', 'password' => 'ein anderer langer satz', 'password_repeat' => 'ein anderer langer satz', 'role' => 'viewer', 'sites' => [(string) $siteId]]);
        self::assertSame(303, $ok->status);
        self::assertSame(1, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('site_users')));
        self::assertSame('erika@beispiel.de', $this->db->fetchValue('SELECT email FROM ' . $this->db->table('users') . " WHERE name = 'Erika'"));

        // Betrachter: kein Zugriff auf Websites und Benutzer, aber auf das eigene Konto.
        $this->session->destroy();
        $this->anmelden('erika@beispiel.de', 'ein anderer langer satz');
        self::assertSame(403, $this->get('/einstellungen/websites')->status);
        self::assertSame(403, $this->get('/einstellungen/benutzer')->status);
        self::assertSame(403, $this->post('/einstellungen/websites', ['name' => 'x'])->status);
        self::assertSame(200, $this->get('/einstellungen/konto')->status);
        self::assertSame('/einstellungen/konto', $this->get('/einstellungen')->headers['Location']);
        self::assertStringNotContainsString('Websites</a>', $this->get('/einstellungen/konto')->body);
    }

    public function testLetzterAdministratorBleibt(): void
    {
        $this->anmelden();
        $id = $this->db->fetchInt('SELECT id FROM ' . $this->db->table('users'));

        $this->post('/einstellungen/benutzer/' . $id, ['name' => 'Frank', 'role' => 'viewer']);
        self::assertSame('admin', $this->db->fetchValue('SELECT role FROM ' . $this->db->table('users')));

        $zweiter = $this->post('/einstellungen/benutzer', ['name' => 'Zwei', 'email' => 'zwei@beispiel.de', 'password' => 'ein anderer langer satz', 'password_repeat' => 'ein anderer langer satz', 'role' => 'admin']);
        self::assertSame(303, $zweiter->status);
        $zweiteId = $this->db->fetchInt('SELECT id FROM ' . $this->db->table('users') . " WHERE name = 'Zwei'");

        // Mit zwei Administratoren darf einer herabgestuft und gelöscht werden, sich selbst löschen nie.
        $this->post('/einstellungen/benutzer/' . $zweiteId, ['name' => 'Zwei', 'role' => 'viewer', 'disabled' => '1']);
        self::assertSame('viewer', $this->db->fetchValue('SELECT role FROM ' . $this->db->table('users') . ' WHERE id = ' . $zweiteId));
        $this->post('/einstellungen/benutzer/' . $id . '/loeschen');
        self::assertSame(2, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('users')));
        $this->post('/einstellungen/benutzer/' . $zweiteId . '/loeschen');
        self::assertSame(1, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('users')));
    }

    public function testGesperrterBenutzerKannSichNichtAnmelden(): void
    {
        $this->anmelden();
        $this->post('/einstellungen/benutzer', ['name' => 'Gast', 'email' => 'gast@beispiel.de', 'password' => 'ein anderer langer satz', 'password_repeat' => 'ein anderer langer satz']);
        $id = $this->db->fetchInt('SELECT id FROM ' . $this->db->table('users') . " WHERE name = 'Gast'");
        $this->post('/einstellungen/benutzer/' . $id, ['name' => 'Gast', 'role' => 'viewer', 'disabled' => '1']);
        $this->session->destroy();

        $antwort = $this->post('/login', ['email' => 'gast@beispiel.de', 'password' => 'ein anderer langer satz']);

        self::assertSame(422, $antwort->status);
    }

    public function testPasswortAendern(): void
    {
        $this->anmelden();

        $falsch = $this->post('/einstellungen/konto', ['current' => 'falsch', 'password' => 'ein neues langes passwort', 'password_repeat' => 'ein neues langes passwort']);
        self::assertSame(422, $falsch->status);
        self::assertStringContainsString('stimmt nicht', $falsch->body);

        $kurz = $this->post('/einstellungen/konto', ['current' => 'ein sehr langer satz', 'password' => 'kurz', 'password_repeat' => 'kurz']);
        self::assertSame(422, $kurz->status);

        $ok = $this->post('/einstellungen/konto', ['current' => 'ein sehr langer satz', 'password' => 'ein neues langes passwort', 'password_repeat' => 'ein neues langes passwort']);
        self::assertSame(303, $ok->status);
        $this->session->destroy();
        $this->anmelden('frank@beispiel.de', 'ein neues langes passwort');
    }

    public function testCsrfSchutz(): void
    {
        $this->anmelden();
        $antwort = $this->app()->handle(new Request('POST', '/einstellungen/websites', post: ['_csrf' => 'falsch', 'name' => 'X', 'domain' => 'x.de', 'timezone' => 'UTC', 'retention_days' => '100']));

        self::assertSame(303, $antwort->status);
        self::assertSame(1, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('sites')));
    }

    public function testMeldungenWerdenEscaped(): void
    {
        $this->anmelden();
        $this->post('/einstellungen/websites', ['name' => '<script>alert(1)</script>', 'domain' => 'x.de', 'timezone' => 'UTC', 'retention_days' => '100']);

        $seite = $this->get('/einstellungen/websites')->body;

        self::assertStringNotContainsString('<script>alert(1)', $seite);
        self::assertStringContainsString('&lt;script&gt;', $seite);
    }

    public function testZweiFaktorEinrichtenAnmeldenUndZuruecksetzen(): void
    {
        $this->anmelden();
        $this->post('/einstellungen/konto/2fa/start');
        $seite = $this->get('/einstellungen/konto')->body;
        preg_match('/<code>([A-Z2-7 ]+)<\/code>/', $seite, $m);
        $secret = str_replace(' ', '', $m[1] ?? '');
        self::assertSame(32, strlen($secret), 'Der Schlüssel wird angezeigt.');

        $falsch = $this->post('/einstellungen/konto/2fa/bestaetigen', ['code' => '000000']);
        self::assertSame(422, $falsch->status);
        self::assertNull($this->db->fetchValue('SELECT totp_enabled_at FROM ' . $this->db->table('users')));

        $code = Totp::code($secret, time());
        self::assertSame(303, $this->post('/einstellungen/konto/2fa/bestaetigen', ['code' => $code])->status);
        self::assertNotNull($this->db->fetchValue('SELECT totp_enabled_at FROM ' . $this->db->table('users')));
        $gespeichert = $this->db->fetchValue('SELECT totp_secret FROM ' . $this->db->table('users'));
        self::assertIsString($gespeichert);
        self::assertStringNotContainsString($secret, $gespeichert, 'Das Geheimnis liegt verschlüsselt in der Datenbank.');

        // Neue Anmeldung: Passwort allein genügt nicht.
        $this->session->destroy();
        $antwort = $this->post('/login', ['email' => 'frank@beispiel.de', 'password' => 'ein sehr langer satz']);
        self::assertSame('/login/2fa', $antwort->headers['Location']);
        self::assertSame('/login', $this->get('/')->headers['Location'], 'Ohne Code kein Zugang.');

        self::assertSame(422, $this->post('/login/2fa', ['code' => '123456'])->status);
        self::assertSame('/login', $this->get('/')->headers['Location']);
        // Derselbe Code, der bei der Einrichtung benutzt wurde, gilt nicht noch einmal.
        self::assertSame(422, $this->post('/login/2fa', ['code' => $code])->status);
        $naechster = Totp::code($secret, time() + 30);
        self::assertSame(303, $this->post('/login/2fa', ['code' => $naechster])->status);
        self::assertSame('/einstellungen/websites', $this->get('/einstellungen')->headers['Location']);

        // Ausschalten braucht das Passwort.
        self::assertSame(422, $this->post('/einstellungen/konto/2fa/aus', ['current_aus' => 'falsch'])->status);
        $this->post('/einstellungen/konto/2fa/aus', ['current_aus' => 'ein sehr langer satz']);
        self::assertNull($this->db->fetchValue('SELECT totp_enabled_at FROM ' . $this->db->table('users')));
    }

    public function testAdministratorSetztZweiFaktorEinesBenutzersZurueck(): void
    {
        $this->anmelden();
        $this->post('/einstellungen/benutzer', ['name' => 'Gast', 'email' => 'gast@beispiel.de', 'password' => 'ein anderer langer satz', 'password_repeat' => 'ein anderer langer satz']);
        $id = $this->db->fetchInt('SELECT id FROM ' . $this->db->table('users') . " WHERE name = 'Gast'");
        $this->db->run('UPDATE ' . $this->db->table('users') . " SET totp_secret = 'x', totp_enabled_at = NOW() WHERE id = ?", [$id]);

        self::assertStringContainsString('Zwei-Faktor-Anmeldung zurücksetzen', $this->get('/einstellungen/benutzer/' . $id)->body);
        $this->post('/einstellungen/benutzer/' . $id . '/2fa-zuruecksetzen');

        self::assertNull($this->db->fetchValue('SELECT totp_enabled_at FROM ' . $this->db->table('users') . ' WHERE id = ' . $id));
    }

    public function testAbgelaufenerCodeSchritt(): void
    {
        $antwort = $this->post('/login/2fa', ['code' => '123456']);

        self::assertSame('/login', $antwort->headers['Location'], 'Ohne vorherige Passworteingabe gibt es keinen Code-Schritt.');
        self::assertSame('/login', $this->get('/login/2fa')->headers['Location']);
    }

    public function testZieleAnlegenUndLoeschen(): void
    {
        $this->anmelden();
        $id = $this->seitenOptionen();

        $schlecht = $this->post('/einstellungen/websites/' . $id . '/ziele', ['goal_name' => '', 'goal_kind' => 'page', 'goal_target' => 'danke']);
        self::assertSame(422, $schlecht->status);
        self::assertStringContainsString('beginnt mit einem Schrägstrich', $schlecht->body);

        $this->post('/einstellungen/websites/' . $id . '/ziele', ['goal_name' => 'Kontakt', 'goal_kind' => 'page', 'goal_target' => '/danke']);
        $this->post('/einstellungen/websites/' . $id . '/ziele', ['goal_name' => 'Anmeldung', 'goal_kind' => 'event', 'goal_target' => 'Signup']);
        self::assertSame(2, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('goals')));
        self::assertStringContainsString('/danke', $this->get('/einstellungen/websites/' . $id)->body);

        $gid = $this->db->fetchInt('SELECT id FROM ' . $this->db->table('goals') . ' ORDER BY id LIMIT 1');
        $this->post('/einstellungen/websites/' . $id . '/ziele/' . $gid . '/loeschen');
        self::assertSame(1, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('goals')));

        // Mit der Website verschwinden auch ihre Ziele.
        $domain = $this->db->fetchValue('SELECT domain FROM ' . $this->db->table('sites'));
        $this->post('/einstellungen/websites/' . $id . '/loeschen', ['bestaetigung' => is_string($domain) ? $domain : '']);
        self::assertSame(0, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('goals')));
    }

    public function testCsvExportNurAngemeldetUndNurFuerEigeneWebsite(): void
    {
        $id = $this->seitenOptionen();

        self::assertSame('/login', $this->export('/export/seiten', ['site' => $id])->headers['Location']);

        $this->anmelden();
        $this->db->run('INSERT INTO ' . $this->db->table('sessions') . ' (site_id, visitor_hash, started_at, last_seen_at, entry_path_id, exit_path_id) VALUES (1, ?, NOW(), NOW(), 1, 1)', [str_repeat('a', 16)]);
        $ok = $this->export('/export/zeitverlauf', ['site' => $id, 'von' => '2020-01-01', 'bis' => '2020-01-03']);
        self::assertSame(200, $ok->status);
        self::assertStringStartsWith("\xEF\xBB\xBFZeitpunkt;Besucher", $ok->body);
        self::assertStringContainsString('attachment; filename="pegelstand-', $ok->headers['Content-Disposition']);
        self::assertStringContainsString('text/csv', $ok->headers['Content-Type']);
        self::assertSame(404, $this->export('/export/passwoerter', ['site' => $id])->status);
        self::assertSame(404, $this->export('/export/seiten', ['site' => 'fremd'])->status);
    }

    /**
     * @param array<string, string> $query
     */
    private function api(string $pfad, ?string $schluessel, array $query = []): Response
    {
        return $this->app()->handle(new Request('GET', $pfad, query: $query, headers: $schluessel === null ? [] : ['authorization' => 'Bearer ' . $schluessel]));
    }

    private function token(): string
    {
        $t = $this->db->fetchValue('SELECT public_token FROM ' . $this->db->table('sites'));

        return is_string($t) ? $t : '';
    }

    private function sitesAusAntwort(Response $antwort): mixed
    {
        $daten = json_decode($antwort->body, true);

        return is_array($daten) ? ($daten['sites'] ?? null) : null;
    }

    public function testApiSchluesselUndSchnittstelle(): void
    {
        $this->anmelden();
        $id = $this->seitenOptionen();
        self::assertSame(422, $this->post('/einstellungen/konto/api-schluessel', ['key_name' => ''])->status);
        $this->post('/einstellungen/konto/api-schluessel', ['key_name' => 'Monitoring']);

        $seite = $this->get('/einstellungen/konto')->body;
        preg_match('/psk_[a-f0-9]{40}/', $seite, $m);
        $schluessel = $m[0] ?? '';
        self::assertNotSame('', $schluessel, 'Der neue Schlüssel wird einmal angezeigt.');
        self::assertStringNotContainsString($schluessel, $this->get('/einstellungen/konto')->body, 'Beim nächsten Aufruf nicht mehr.');
        $gespeichert = $this->db->fetchAll('SELECT key_prefix, key_hash FROM ' . $this->db->table('api_keys'))[0] ?? [];
        self::assertSame(hash('sha256', $schluessel), $gespeichert['key_hash'] ?? '');
        self::assertSame(substr($schluessel, 0, 8), $gespeichert['key_prefix'] ?? '');

        $this->session->destroy(); // Die Schnittstelle braucht keine Sitzung.
        self::assertSame(401, $this->api('/api/v1/sites', null)->status);
        self::assertSame(401, $this->api('/api/v1/sites', 'psk_' . str_repeat('0', 40))->status);
        self::assertSame(401, $this->api('/api/v1/sites', 'falsch')->status);

        $liste = $this->api('/api/v1/sites', $schluessel);
        self::assertSame(200, $liste->status);
        self::assertSame([['id' => $id, 'name' => 'Test', 'domain' => 'beispiel.de', 'zeitzone' => 'Europe/Berlin']], $this->sitesAusAntwort($liste));

        $stats = $this->api('/api/v1/sites/' . $id . '/stats', $schluessel, ['von' => '2020-01-01', 'bis' => '2020-01-31']);
        self::assertSame(200, $stats->status);
        $daten = json_decode($stats->body, true);
        self::assertIsArray($daten);
        self::assertArrayHasKey('leer', $daten);
        self::assertSame(404, $this->api('/api/v1/sites/gibtesnicht/stats', $schluessel)->status);
        self::assertNotNull($this->db->fetchValue('SELECT last_used_at FROM ' . $this->db->table('api_keys')));
        self::assertSame('application/json; charset=utf-8', $stats->headers['Content-Type']);

        // Gelöschter Schlüssel funktioniert nicht mehr.
        $this->anmelden();
        $kid = $this->db->fetchInt('SELECT id FROM ' . $this->db->table('api_keys'));
        $this->post('/einstellungen/konto/api-schluessel/' . $kid . '/loeschen');
        self::assertSame(401, $this->api('/api/v1/sites', $schluessel)->status);
    }

    public function testApiBeachtetFreigabenUndGesperrteBenutzer(): void
    {
        $this->anmelden();
        $this->post('/einstellungen/benutzer', ['name' => 'Gast', 'email' => 'gast@beispiel.de', 'password' => 'ein anderer langer satz', 'password_repeat' => 'ein anderer langer satz']);
        $gastId = $this->db->fetchInt('SELECT id FROM ' . $this->db->table('users') . " WHERE name = 'Gast'");
        $this->db->run('INSERT INTO ' . $this->db->table('api_keys') . ' (user_id, name, key_prefix, key_hash, created_at) VALUES (?, ?, ?, ?, NOW())', [$gastId, 'x', 'psk_abcd', hash('sha256', 'psk_abcdef')]);
        $this->session->destroy();

        // Betrachter ohne Freigabe sieht keine Website.
        self::assertSame([], $this->sitesAusAntwort($this->api('/api/v1/sites', 'psk_abcdef')));
        self::assertSame(404, $this->api('/api/v1/sites/' . $this->seitenOptionen() . '/stats', 'psk_abcdef')->status);

        $this->db->run('UPDATE ' . $this->db->table('users') . ' SET disabled_at = NOW() WHERE id = ?', [$gastId]);
        self::assertSame(401, $this->api('/api/v1/sites', 'psk_abcdef')->status);
    }

    public function testApiBegrenztAnfragen(): void
    {
        $this->anmelden();
        $this->post('/einstellungen/konto/api-schluessel', ['key_name' => 'Flut']);
        preg_match('/psk_[a-f0-9]{40}/', $this->get('/einstellungen/konto')->body, $m);
        $this->session->destroy();

        $letzte = 200;
        for ($i = 0; $i < 125; ++$i) {
            $letzte = $this->api('/api/v1/sites', $m[0] ?? '')->status;
        }

        self::assertSame(429, $letzte);
    }

    public function testOeffentlichesDashboard(): void
    {
        $id = $this->seitenOptionen();
        self::assertSame(404, $this->get('/oeffentlich/' . str_repeat('a', 32))->status);

        $this->anmelden();
        $this->post('/einstellungen/websites/' . $id . '/oeffentlich', ['aktion' => 'an']);
        $token = $this->db->fetchValue('SELECT public_token FROM ' . $this->db->table('sites'));
        self::assertIsString($token);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);
        self::assertStringContainsString('/oeffentlich/' . $token, $this->get('/einstellungen/websites/' . $id)->body);

        $this->db->run('INSERT INTO ' . $this->db->table('sessions') . ' (site_id, visitor_hash, started_at, last_seen_at, entry_path_id, exit_path_id) VALUES (1, ?, NOW(), NOW(), 1, 1)', [str_repeat('a', 16)]);
        $this->session->destroy(); // jetzt ohne Anmeldung

        $seite = $this->get('/oeffentlich/' . $token);
        self::assertSame(200, $seite->status);
        self::assertStringContainsString('data-api="oeffentlich/' . $token . '/daten"', $seite->body);
        self::assertStringNotContainsString('/einstellungen', $seite->body, 'Keine Verwaltung in der öffentlichen Ansicht.');
        self::assertStringNotContainsString('/logout', $seite->body);
        self::assertStringNotContainsString('data-export', $seite->body);
        self::assertSame('no-referrer', $seite->headers['Referrer-Policy']);

        $daten = $this->get('/oeffentlich/' . $token . '/daten');
        self::assertSame(200, $daten->status);
        self::assertArrayHasKey('kennzahlen', (array) json_decode($daten->body, true));
        self::assertSame(200, $this->get('/oeffentlich/' . $token . '/daten/live')->status);
        // Nichts anderes ist ohne Anmeldung erreichbar.
        self::assertSame('/login', $this->get('/')->headers['Location']);
        self::assertSame(401, $this->get('/api/dashboard')->status);

        // Abschalten beendet den Zugang, ein neuer Link macht den alten ungültig.
        $this->anmelden();
        $this->post('/einstellungen/websites/' . $id . '/oeffentlich', ['aktion' => 'an', 'neu' => '1']);
        self::assertSame(404, $this->get('/oeffentlich/' . $token)->status);
        $neu = $this->token();
        $this->post('/einstellungen/websites/' . $id . '/oeffentlich', ['aktion' => 'aus']);
        self::assertSame(404, $this->get('/oeffentlich/' . $neu)->status);
        self::assertNull($this->db->fetchValue('SELECT public_token FROM ' . $this->db->table('sites')));
    }

    public function testOeffentlicheSchnittstelleIstBegrenzt(): void
    {
        $this->anmelden();
        $this->post('/einstellungen/websites/' . $this->seitenOptionen() . '/oeffentlich', ['aktion' => 'an']);
        $token = $this->token();
        $this->session->destroy();

        $letzte = 200;
        for ($i = 0; $i < 65; ++$i) {
            $letzte = $this->get('/oeffentlich/' . $token . '/daten/live')->status;
        }

        self::assertSame(429, $letzte);
    }
}
