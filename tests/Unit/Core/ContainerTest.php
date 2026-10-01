<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Core;

use ArrayObject;
use Pegelstand\Core\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

final class ContainerTest extends TestCase
{
    public function testFabrikLaeuftNurEinmal(): void
    {
        $container = new Container();
        $aufrufe = 0;
        $container->set('x', static function () use (&$aufrufe): stdClass {
            ++$aufrufe;

            return new stdClass();
        });

        self::assertSame($container->get('x', stdClass::class), $container->get('x', stdClass::class));
        self::assertSame(1, $aufrufe);
    }

    public function testUnbekannterDienst(): void
    {
        $this->expectException(RuntimeException::class);
        (new Container())->get('fehlt', stdClass::class);
    }

    public function testFalscherTyp(): void
    {
        $container = new Container();
        $container->set('x', static fn(): stdClass => new stdClass());

        $this->expectException(RuntimeException::class);
        $container->get('x', ArrayObject::class);
    }

    public function testHasUndUeberschreiben(): void
    {
        $container = new Container();
        self::assertFalse($container->has('x'));
        $container->set('x', static fn(): ArrayObject => new ArrayObject([1]));
        self::assertTrue($container->has('x'));
        $container->get('x', ArrayObject::class);
        $container->set('x', static fn(): ArrayObject => new ArrayObject([2]));

        self::assertSame([2], $container->get('x', ArrayObject::class)->getArrayCopy());
    }
}
