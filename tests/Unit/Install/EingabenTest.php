<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Install;

use Pegelstand\Install\AdminInput;
use Pegelstand\Install\DatabaseInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EingabenTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private static function gueltigeDb(): array
    {
        return ['host' => 'localhost', 'port' => '3306', 'name' => 'pegelstand', 'user' => 'pegel', 'password' => 'geheim', 'prefix' => 'ps_'];
    }

    public function testGueltigeDatenbankangaben(): void
    {
        $eingabe = DatabaseInput::fromPost(self::gueltigeDb());

        self::assertSame([], $eingabe->validate());
        self::assertSame(3306, $eingabe->port);
        self::assertSame('ps_', $eingabe->toArray()['prefix']);
    }

    public function testStandardwerte(): void
    {
        $eingabe = DatabaseInput::fromPost([]);

        self::assertSame('localhost', $eingabe->host);
        self::assertSame(3306, $eingabe->port);
        self::assertSame('ps_', $eingabe->prefix);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function ungueltigeDatenbank(): iterable
    {
        yield 'Host mit Leerzeichen' => ['host', 'local host', 'host'];
        yield 'Host leer' => ['host', '', 'host'];
        yield 'Port Text' => ['port', 'abc', 'port'];
        yield 'Port null' => ['port', '0', 'port'];
        yield 'Port zu groß' => ['port', '70000', 'port'];
        yield 'DSN-Einschleusung im Namen' => ['name', 'db;host=böse', 'name'];
        yield 'Name mit Backtick' => ['name', 'db`x', 'name'];
        yield 'Name leer' => ['name', '', 'name'];
        yield 'Name zu lang' => ['name', str_repeat('a', 65), 'name'];
        yield 'Benutzer leer' => ['user', '', 'user'];
        yield 'Passwort zu lang' => ['password', str_repeat('x', 201), 'password'];
        yield 'Präfix mit Sonderzeichen' => ['prefix', 'ps-1; DROP', 'prefix'];
        yield 'Präfix zu lang' => ['prefix', str_repeat('a', 33), 'prefix'];
    }

    #[DataProvider('ungueltigeDatenbank')]
    public function testUngueltigeDatenbankangaben(string $feld, string $wert, string $erwartetesFeld): void
    {
        $eingabe = DatabaseInput::fromPost([...self::gueltigeDb(), $feld => $wert]);

        self::assertArrayHasKey($erwartetesFeld, $eingabe->validate());
    }

    public function testLeererPraefixIstErlaubt(): void
    {
        self::assertSame([], DatabaseInput::fromPost([...self::gueltigeDb(), 'prefix' => ''])->validate());
    }

    public function testDatenbankPasswortWirdNichtGekuerzt(): void
    {
        $eingabe = DatabaseInput::fromPost([...self::gueltigeDb(), 'password' => ' mit Leerzeichen ']);

        self::assertSame(' mit Leerzeichen ', $eingabe->password);
    }

    public function testGueltigerAdministrator(): void
    {
        $admin = AdminInput::fromPost([
            'name' => ' Frank Panzer ',
            'email' => 'frank@beispiel.de',
            'password' => 'ein langer satz als passwort',
            'password_repeat' => 'ein langer satz als passwort',
        ]);

        self::assertSame([], $admin->validate());
        self::assertSame('Frank Panzer', $admin->name);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function ungueltigerAdmin(): iterable
    {
        $gut = ['name' => 'Frank', 'email' => 'frank@beispiel.de', 'password' => 'zwoelf zeichen!', 'password_repeat' => 'zwoelf zeichen!'];
        yield 'Name leer' => [[...$gut, 'name' => ''], 'name'];
        yield 'Name zu lang' => [[...$gut, 'name' => str_repeat('a', 121)], 'name'];
        yield 'E-Mail ohne Domain' => [[...$gut, 'email' => 'frank@beispiel'], 'email'];
        yield 'E-Mail leer' => [[...$gut, 'email' => ''], 'email'];
        yield 'Passwort zu kurz' => [[...$gut, 'password' => 'kurz', 'password_repeat' => 'kurz'], 'password'];
        yield 'Passwort elf Zeichen' => [[...$gut, 'password' => '12345678901', 'password_repeat' => '12345678901'], 'password'];
        yield 'Passwort zu lang' => [[...$gut, 'password' => str_repeat('x', 201), 'password_repeat' => str_repeat('x', 201)], 'password'];
        yield 'Wiederholung abweichend' => [[...$gut, 'password_repeat' => 'etwas anderes!'], 'password_repeat'];
    }

    /**
     * @param array<string, string> $post
     */
    #[DataProvider('ungueltigerAdmin')]
    public function testUngueltigerAdministrator(array $post, string $feld): void
    {
        self::assertArrayHasKey($feld, AdminInput::fromPost($post)->validate());
    }

    public function testZwoelfZeichenReichen(): void
    {
        $admin = AdminInput::fromPost(['name' => 'F', 'email' => 'a@b.de', 'password' => '123456789012', 'password_repeat' => '123456789012']);

        self::assertSame([], $admin->validate());
    }

    public function testUmlauteZaehlenAlsEinZeichen(): void
    {
        $passwort = 'äöüäöüäöüäöü';
        $admin = AdminInput::fromPost(['name' => 'F', 'email' => 'a@b.de', 'password' => $passwort, 'password_repeat' => $passwort]);

        self::assertSame([], $admin->validate());
    }
}
