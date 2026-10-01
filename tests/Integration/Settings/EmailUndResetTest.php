<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Integration\Settings;

use Pegelstand\Core\Csrf;
use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Tests\Integration\InstalliertTestCase;
use Pegelstand\Tests\Support\FakeSmtpServer;

final class EmailUndResetTest extends InstalliertTestCase
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
        return $this->app()->handle(new Request('POST', $pfad, post: ['_csrf' => (new Csrf($this->session))->token()] + $post, ip: '203.0.113.9'));
    }

    private function anmelden(): void
    {
        $this->post('/login', ['email' => 'frank@beispiel.de', 'password' => 'ein sehr langer satz']);
    }

    /**
     * @param array<string, string> $zusatz
     */
    private function mailEinrichten(int $port, array $zusatz = []): Response
    {
        return $this->post('/einstellungen/email', $zusatz + [
            'host' => '127.0.0.1', 'port' => (string) $port, 'security' => 'none', 'user' => '', 'password' => '',
            'from_address' => 'pegelstand@beispiel.de', 'from_name' => 'Pegelstand', 'base_url' => 'https://stats.beispiel.de',
        ]);
    }

    private function nachrichtText(FakeSmtpServer $server): string
    {
        $zeilen = [];
        foreach (explode("\n", $server->inhalt()) as $z) {
            if (str_starts_with($z, 'D> ')) {
                $zeilen[] = substr($z, 3);
            }
        }

        return quoted_printable_decode(implode("\r\n", $zeilen));
    }

    public function testEinstellungenSpeichernUndTestversand(): void
    {
        $this->anmelden();
        $schlecht = $this->mailEinrichten(1, ['host' => 'kein server!', 'port' => '0', 'from_address' => 'unsinn', 'base_url' => 'ftp://x']);
        self::assertSame(422, $schlecht->status);
        self::assertStringContainsString('Servernamen', $schlecht->body);

        $server = new FakeSmtpServer();
        try {
            self::assertSame(303, $this->mailEinrichten($server->port, ['password' => 'geheimes-passwort'])->status);
            $gespeichert = $this->db->fetchValue('SELECT value FROM ' . $this->db->table('settings') . " WHERE name = 'mail.password'");
            self::assertIsString($gespeichert);
            self::assertStringNotContainsString('geheimes-passwort', $gespeichert, 'Das SMTP-Passwort liegt verschlüsselt in der Datenbank.');
            self::assertStringNotContainsString('geheimes-passwort', $this->get('/einstellungen/email')->body, 'Das Passwort wird nie angezeigt.');

            $this->post('/einstellungen/email/test');
            $text = $this->nachrichtText($server);
            $log = $server->inhalt();
        } finally {
            $server->stop();
        }
        self::assertStringContainsString('Subject: Testnachricht von Pegelstand', $text);
        self::assertStringContainsString('RCPT TO:<frank@beispiel.de>', $log);
        self::assertStringContainsString('unterwegs', $this->get('/einstellungen/email')->body);
    }

    public function testNurAdministratorenSehenDieEmailEinstellungen(): void
    {
        self::assertSame('/login', $this->get('/einstellungen/email')->headers['Location']);
        $this->anmelden();
        $this->post('/einstellungen/benutzer', ['name' => 'Gast', 'email' => 'gast@beispiel.de', 'password' => 'ein anderer langer satz', 'password_repeat' => 'ein anderer langer satz']);
        $this->session->destroy();
        $this->post('/login', ['email' => 'gast@beispiel.de', 'password' => 'ein anderer langer satz']);

        self::assertSame(403, $this->get('/einstellungen/email')->status);
        self::assertSame(403, $this->post('/einstellungen/email', ['host' => 'x'])->status);
    }

    public function testPasswortZuruecksetzenKompletterAblauf(): void
    {
        // Ohne E-Mail-Einrichtung ist der Weg nicht verfügbar.
        self::assertStringContainsString('nicht eingerichtet', $this->get('/passwort-vergessen')->body);

        $this->anmelden();
        $server = new FakeSmtpServer();
        $this->mailEinrichten($server->port);
        $this->session->destroy();

        try {
            $antwort = $this->post('/passwort-vergessen', ['email' => 'Frank@Beispiel.de']);
            self::assertSame(200, $antwort->status);
            self::assertStringContainsString('Wenn es zu dieser Adresse einen Zugang gibt', $antwort->body);
            $text = $this->nachrichtText($server);
        } finally {
            $server->stop();
        }
        self::assertSame(1, preg_match('#https://stats\.beispiel\.de/passwort-zuruecksetzen/([a-f0-9]{64})#', $text, $m), $text);
        $token = $m[1] ?? '';
        $gespeichert = $this->db->fetchValue('SELECT token_hash FROM ' . $this->db->table('password_resets'));
        self::assertSame(hash('sha256', $token), $gespeichert, 'Nur der Hash des Tokens liegt in der Datenbank.');

        self::assertSame(200, $this->get('/passwort-zuruecksetzen/' . $token)->status);
        $kurz = $this->post('/passwort-zuruecksetzen/' . $token, ['password' => 'kurz', 'password_repeat' => 'kurz']);
        self::assertSame(422, $kurz->status);
        $ok = $this->post('/passwort-zuruecksetzen/' . $token, ['password' => 'ein ganz neues passwort', 'password_repeat' => 'ein ganz neues passwort']);
        self::assertSame(303, $ok->status);
        self::assertSame('/login?zurueckgesetzt=1', $ok->headers['Location']);

        // Link ist verbraucht.
        self::assertSame(410, $this->get('/passwort-zuruecksetzen/' . $token)->status);
        self::assertSame(410, $this->post('/passwort-zuruecksetzen/' . $token, ['password' => 'noch ein neues passwort', 'password_repeat' => 'noch ein neues passwort'])->status);

        self::assertSame(422, $this->post('/login', ['email' => 'frank@beispiel.de', 'password' => 'ein sehr langer satz'])->status);
        self::assertSame(303, $this->post('/login', ['email' => 'frank@beispiel.de', 'password' => 'ein ganz neues passwort'])->status);
    }

    public function testUnbekannteAdresseSiehtDieselbeAntwortUndBekommtNichts(): void
    {
        $this->anmelden();
        $server = new FakeSmtpServer();
        $this->mailEinrichten($server->port);
        $this->session->destroy();

        try {
            $antwort = $this->post('/passwort-vergessen', ['email' => 'niemand@beispiel.de']);
            $log = $server->inhalt();
        } finally {
            $server->stop();
        }

        self::assertStringContainsString('Wenn es zu dieser Adresse einen Zugang gibt', $antwort->body);
        self::assertSame('', $log, 'Es wurde nichts verschickt.');
        self::assertSame(0, $this->db->fetchInt('SELECT COUNT(*) FROM ' . $this->db->table('password_resets')));
    }

    public function testAbgelaufenerOderGeratenerLink(): void
    {
        self::assertSame(410, $this->get('/passwort-zuruecksetzen/' . str_repeat('a', 64))->status);
        self::assertSame(410, $this->get('/passwort-zuruecksetzen/zu-kurz')->status);

        $token = str_repeat('b', 64);
        $this->db->run('INSERT INTO ' . $this->db->table('password_resets') . ' (user_id, token_hash, expires_at) VALUES (1, ?, ?)', [hash('sha256', $token), gmdate('Y-m-d H:i:s', time() - 10)]);
        self::assertSame(410, $this->get('/passwort-zuruecksetzen/' . $token)->status, 'Abgelaufen.');
    }

    public function testGesperrterBenutzerBekommtKeinenLink(): void
    {
        $this->anmelden();
        $server = new FakeSmtpServer();
        $this->mailEinrichten($server->port);
        $this->db->run('UPDATE ' . $this->db->table('users') . ' SET disabled_at = NOW()');
        $this->session->destroy();

        try {
            $this->post('/passwort-vergessen', ['email' => 'frank@beispiel.de']);
            $log = $server->inhalt();
        } finally {
            $server->stop();
        }

        self::assertSame('', $log);
    }

    public function testAnfragenWerdenBegrenzt(): void
    {
        $this->anmelden();
        $this->mailEinrichten(1);
        $this->session->destroy();

        $status = [];
        for ($i = 0; $i < 4; ++$i) {
            $status[] = $this->post('/passwort-vergessen', ['email' => 'niemand@beispiel.de'])->status;
        }

        self::assertSame([200, 200, 200, 429], $status);
    }
}
