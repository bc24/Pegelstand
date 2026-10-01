<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Ingest;

use Pegelstand\Ingest\BotFilter;
use Pegelstand\Ingest\HostMatcher;
use Pegelstand\Ingest\UrlParser;
use Pegelstand\Ingest\UserAgentInfo;
use Pegelstand\Ingest\UserAgentParser;
use Pegelstand\Ingest\VisitorHasher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    /**
     * @return iterable<string, array{string, ?string, ?string, int}>
     */
    public static function userAgents(): iterable
    {
        yield 'Chrome Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36', 'Chrome', 'Windows', UserAgentInfo::DEVICE_DESKTOP];
        yield 'Edge' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.0.0', 'Edge', 'Windows', UserAgentInfo::DEVICE_DESKTOP];
        yield 'Firefox Linux' => ['Mozilla/5.0 (X11; Linux x86_64; rv:127.0) Gecko/20100101 Firefox/127.0', 'Firefox', 'Linux', UserAgentInfo::DEVICE_DESKTOP];
        yield 'Safari Mac' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15', 'Safari', 'macOS', UserAgentInfo::DEVICE_DESKTOP];
        yield 'Safari iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1', 'Safari', 'iOS', UserAgentInfo::DEVICE_MOBILE];
        yield 'Chrome Android Smartphone' => ['Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36', 'Chrome', 'Android', UserAgentInfo::DEVICE_MOBILE];
        yield 'Chrome Android Tablet' => ['Mozilla/5.0 (Linux; Android 14; SM-X710) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36', 'Chrome', 'Android', UserAgentInfo::DEVICE_TABLET];
        yield 'iPad' => ['Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1', 'Safari', 'iOS', UserAgentInfo::DEVICE_TABLET];
        yield 'Samsung Internet' => ['Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/25.0 Chrome/121.0.0.0 Mobile Safari/537.36', 'Samsung Internet', 'Android', UserAgentInfo::DEVICE_MOBILE];
        yield 'Firefox iOS' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/127.0 Mobile/15E148 Safari/605.1.15', 'Firefox', 'iOS', UserAgentInfo::DEVICE_MOBILE];
        yield 'Unbekannt' => ['EtwasSeltsames/1.0', null, null, UserAgentInfo::DEVICE_DESKTOP];
        yield 'Leer' => ['', null, null, UserAgentInfo::DEVICE_UNKNOWN];
    }

    #[DataProvider('userAgents')]
    public function testUserAgentErkennung(string $ua, ?string $browser, ?string $os, int $geraet): void
    {
        $info = (new UserAgentParser())->parse($ua);

        self::assertSame($browser, $info->browser);
        self::assertSame($os, $info->os);
        self::assertSame($geraet, $info->device);
    }

    public function testBotFilter(): void
    {
        $filter = BotFilter::fromFile(PEGELSTAND_ROOT . '/resources/data/bots.php');

        self::assertTrue($filter->isBot(''));
        self::assertTrue($filter->isBot('curl/8.5.0'));
        self::assertTrue($filter->isBot('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'));
        self::assertTrue($filter->isBot('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/126.0.0.0 Safari/537.36'));
        self::assertTrue($filter->isBot('Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)'));
        self::assertFalse($filter->isBot('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36'));
        self::assertFalse($filter->isBot('Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1'));
    }

    public function testBotFilterOhneDatei(): void
    {
        $filter = BotFilter::fromFile('/gibt/es/nicht.php');

        self::assertFalse($filter->isBot('Mozilla/5.0 (Windows NT 10.0) Chrome/126.0'));
        self::assertTrue($filter->isBot('kurz'));
    }

    public function testHostMatcher(): void
    {
        $hosts = new HostMatcher('www.Beispiel.de', "shop.beispiel.de\n*.blog.beispiel.de\n  \nandere.example");

        self::assertTrue($hosts->accepts('beispiel.de'));
        self::assertTrue($hosts->accepts('www.beispiel.de'));
        self::assertTrue($hosts->accepts('BEISPIEL.de:8080'));
        self::assertTrue($hosts->accepts('shop.beispiel.de'));
        self::assertTrue($hosts->accepts('a.blog.beispiel.de'));
        self::assertTrue($hosts->accepts('x.y.blog.beispiel.de'));
        self::assertTrue($hosts->accepts('andere.example'));
        self::assertFalse($hosts->accepts('blog.beispiel.de'), 'Die Wildcard deckt nur Subdomains, nicht die Domain selbst.');
        self::assertFalse($hosts->accepts('evilbeispiel.de'));
        self::assertFalse($hosts->accepts('beispiel.de.evil.example'));
        self::assertFalse($hosts->accepts(''));
    }

    public function testSeitenadresse(): void
    {
        $ziel = (new UrlParser())->page('https://Beispiel.de/Ueber-uns?x=1&utm_source=Newsletter&utm_medium=E-Mail&utm_campaign=Herbst%20Aktion#kapitel');

        self::assertNotNull($ziel);
        self::assertSame('beispiel.de', $ziel->host);
        self::assertSame('/Ueber-uns', $ziel->path);
        self::assertSame(['source' => 'newsletter', 'medium' => 'e-mail', 'campaign' => 'Herbst Aktion'], $ziel->utm);
    }

    public function testQueryStringUndAnkerWerdenVerworfen(): void
    {
        $ziel = (new UrlParser())->page('https://beispiel.de/suche?q=geheim&sid=123#abschnitt');

        self::assertSame('/suche', $ziel?->path);
        self::assertSame([], $ziel->utm);
    }

    public function testHashRouting(): void
    {
        $parser = new UrlParser();

        self::assertSame('/app#/konto/profil', $parser->page('https://beispiel.de/app#/konto/profil')?->path);
        self::assertSame('/app#/konto', $parser->page('https://beispiel.de/app#!/konto')?->path);
        self::assertSame('/app', $parser->page('https://beispiel.de/app#kapitel')?->path);
    }

    public function testPfadMitUmlautenUndSteuerzeichen(): void
    {
        $parser = new UrlParser();

        self::assertSame('/über-uns', $parser->page('https://beispiel.de/%C3%BCber-uns')?->path);
        self::assertSame('/a%0Ab', $parser->page('https://beispiel.de/a%0Ab')?->path, 'Steuerzeichen bleiben kodiert.');
        self::assertSame('/%FF%FE', $parser->page('https://beispiel.de/%FF%FE')?->path, 'Ungültiges UTF-8 bleibt kodiert.');
        self::assertSame(1024, mb_strlen($parser->page('https://beispiel.de/' . str_repeat('a', 3000))->path ?? ''));
    }

    public function testUngueltigeAdressen(): void
    {
        $parser = new UrlParser();

        self::assertNull($parser->page('javascript:alert(1)'));
        self::assertNull($parser->page('ftp://beispiel.de/'));
        self::assertNull($parser->page('/nur/pfad'));
        self::assertNull($parser->page(''));
    }

    public function testReferrerHost(): void
    {
        $parser = new UrlParser();

        self::assertSame('google.com', $parser->referrerHost('https://www.google.com/search?q=geheim'));
        self::assertNull($parser->referrerHost(''));
        self::assertNull($parser->referrerHost('android-app://com.example'));
        self::assertNull($parser->referrerHost('https://exa mple.com/'));
    }

    public function testBesucherHash(): void
    {
        $salt = str_repeat('a', 32);
        $hash = VisitorHasher::visitor($salt, 1, '192.0.2.1', 'Agent');

        self::assertSame(16, strlen($hash));
        self::assertSame($hash, VisitorHasher::visitor($salt, 1, '192.0.2.1', 'Agent'));
        self::assertNotSame($hash, VisitorHasher::visitor($salt, 2, '192.0.2.1', 'Agent'), 'Andere Site');
        self::assertNotSame($hash, VisitorHasher::visitor($salt, 1, '192.0.2.2', 'Agent'), 'Andere Adresse');
        self::assertNotSame($hash, VisitorHasher::visitor($salt, 1, '192.0.2.1', 'Andere'), 'Anderer User-Agent');
        self::assertNotSame($hash, VisitorHasher::visitor(str_repeat('b', 32), 1, '192.0.2.1', 'Agent'), 'Anderes Salt');
        self::assertNotSame(VisitorHasher::rateKey($salt, '192.0.2.1'), $hash);
    }
}
