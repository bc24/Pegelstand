<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Mail;

use Pegelstand\Mail\MailException;
use Pegelstand\Mail\Message;
use Pegelstand\Mail\SmtpClient;
use Pegelstand\Mail\SmtpConfig;
use Pegelstand\Tests\Support\FakeSmtpServer;
use PHPUnit\Framework\TestCase;

final class SmtpClientTest extends TestCase
{
    private function config(int $port, string $user = '', string $pw = ''): SmtpConfig
    {
        return new SmtpConfig('127.0.0.1', $port, 'none', $user, $pw, 'pegelstand@beispiel.de', 'Pegelstand Übersicht', 3);
    }

    public function testSendetNachrichtMitUmlauten(): void
    {
        $server = new FakeSmtpServer();
        try {
            (new SmtpClient($this->config($server->port)))->send(new Message('frank@beispiel.de', 'Wochenübersicht für beispiel.de', "Hallo Frank,\n.Punkt am Anfang\nÜber 1.000 Besucher", '<p>Hallo</p>'));
            $log = $server->inhalt();
        } finally {
            $server->stop();
        }

        self::assertStringContainsString('C> MAIL FROM:<pegelstand@beispiel.de>', $log);
        self::assertStringContainsString('C> RCPT TO:<frank@beispiel.de>', $log);
        self::assertStringContainsString('D> Subject: =?UTF-8?B?' . base64_encode('Wochenübersicht für beispiel.de') . '?=', $log);
        self::assertStringContainsString('From: =?UTF-8?B?' . base64_encode('Pegelstand Übersicht') . '?= <pegelstand@beispiel.de>', $log);
        self::assertStringContainsString('multipart/alternative', $log);
        self::assertStringContainsString('D> ..Punkt am Anfang', $log, 'Punkte am Zeilenanfang werden verdoppelt.');
        self::assertStringContainsString('=C3=9Cber 1.000 Besucher', $log);
        self::assertStringContainsString('C> QUIT', $log);
    }

    public function testAnmeldungPlain(): void
    {
        $server = new FakeSmtpServer('benutzer:geheim');
        try {
            (new SmtpClient($this->config($server->port, 'benutzer', 'geheim')))->send(new Message('a@b.de', 'Test', 'Text'));
            $log = $server->inhalt();
        } finally {
            $server->stop();
        }

        self::assertStringContainsString('C> AUTH PLAIN ' . base64_encode("\0benutzer\0geheim"), $log);
    }

    public function testFalschesPasswortMeldetFehlerOhnePasswort(): void
    {
        $server = new FakeSmtpServer('benutzer:geheim');
        try {
            (new SmtpClient($this->config($server->port, 'benutzer', 'falsch')))->send(new Message('a@b.de', 'Test', 'Text'));
            self::fail('Ausnahme erwartet');
        } catch (MailException $e) {
            self::assertStringContainsString('Anmeldung am Mailserver wurde abgelehnt', $e->getMessage());
            self::assertStringNotContainsString('falsch', $e->getMessage());
        } finally {
            $server->stop();
        }
    }

    public function testServerLehntNachrichtAb(): void
    {
        $server = new FakeSmtpServer('', true);
        try {
            (new SmtpClient($this->config($server->port)))->send(new Message('a@b.de', 'Test', 'Text'));
            self::fail('Ausnahme erwartet');
        } catch (MailException $e) {
            self::assertStringContainsString('abgelehnt', $e->getMessage());
        } finally {
            $server->stop();
        }
    }

    public function testKeineVerbindung(): void
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('Keine Verbindung zu 127.0.0.1:1');

        (new SmtpClient($this->config(1)))->send(new Message('a@b.de', 'Test', 'Text'));
    }

    public function testKopfzeilenEinschleusungWirdVerhindert(): void
    {
        $this->expectException(MailException::class);

        (new SmtpClient($this->config(1)))->send(new Message("a@b.de\r\nBcc: x@y.de", 'Test', 'Text'));
    }

    public function testBetreffMitZeilenumbruchWirdVerhindert(): void
    {
        $this->expectException(MailException::class);

        (new SmtpClient($this->config(1)))->send(new Message('a@b.de', "Test\r\nBcc: x@y.de", 'Text'));
    }

    public function testKopfWert(): void
    {
        self::assertSame('Hallo', SmtpClient::kopfWert('Hallo'));
        self::assertStringStartsWith('=?UTF-8?B?', SmtpClient::kopfWert('Größe'));
        self::assertSame('a b', SmtpClient::kopfWert("a\r\nb"));
    }
}
