<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Core;

use Pegelstand\Core\ArraySession;
use Pegelstand\Core\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    public function testTokenBleibtInDerSitzungStabilUndIstLang(): void
    {
        $csrf = new Csrf(new ArraySession());
        $token = $csrf->token();

        self::assertSame(64, strlen($token));
        self::assertSame($token, $csrf->token());
    }

    public function testPruefung(): void
    {
        $csrf = new Csrf(new ArraySession());
        $token = $csrf->token();

        self::assertTrue($csrf->verify($token));
        self::assertFalse($csrf->verify($token . 'x'));
        self::assertFalse($csrf->verify(''));
    }

    public function testOhneTokenIstJedeEingabeUngueltig(): void
    {
        self::assertFalse((new Csrf(new ArraySession()))->verify(''));
        self::assertFalse((new Csrf(new ArraySession()))->verify('irgendwas'));
    }

    public function testVerschiedeneSitzungenHabenVerschiedeneTokens(): void
    {
        self::assertNotSame((new Csrf(new ArraySession()))->token(), (new Csrf(new ArraySession()))->token());
    }
}
