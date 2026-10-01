<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Core;

use Pegelstand\Core\Request;
use Pegelstand\Core\Response;
use Pegelstand\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testFindetFestenPfad(): void
    {
        $router = new Router();
        $router->add('GET', '/install', static fn(): Response => new Response('ok'));

        $antwort = $router->dispatch(new Request('GET', '/install'));

        self::assertSame('ok', $antwort?->body);
    }

    public function testPlatzhalterWerdenUebergeben(): void
    {
        $router = new Router();
        $router->add('GET', '/sites/{id}/berichte/{art}', static fn(Request $r, array $p): Response => new Response($p['id'] . '|' . $p['art']));

        $antwort = $router->dispatch(new Request('GET', '/sites/42/berichte/woche'));

        self::assertSame('42|woche', $antwort?->body);
    }

    public function testUnbekannterPfadLiefertNull(): void
    {
        self::assertNull((new Router())->dispatch(new Request('GET', '/nichts')));
    }

    public function testFalscheMethodeLiefert405MitAllow(): void
    {
        $router = new Router();
        $router->add('GET', '/x', static fn(): Response => new Response('a'));
        $router->add('POST', '/x', static fn(): Response => new Response('b'));

        $antwort = $router->dispatch(new Request('DELETE', '/x'));

        self::assertSame(405, $antwort?->status);
        self::assertSame('GET, POST', $antwort->headers['Allow'] ?? '');
    }

    public function testPfadMussGanzPassen(): void
    {
        $router = new Router();
        $router->add('GET', '/install', static fn(): Response => new Response('ok'));

        self::assertNull($router->dispatch(new Request('GET', '/install/mehr')));
        self::assertNull($router->dispatch(new Request('GET', '/vorher/install')));
    }
}
