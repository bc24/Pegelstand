<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Core;

use Pegelstand\Core\Translator;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    public function testTextParameterUndFehlenderSchluessel(): void
    {
        $t = new Translator(['a' => ['b' => 'Hallo {name}, {name}!', 'c' => ['d' => 'tief']]]);

        self::assertSame('Hallo Frank, Frank!', $t->get('a.b', ['name' => 'Frank']));
        self::assertSame('tief', $t->get('a.c.d'));
        self::assertSame('a.fehlt', $t->get('a.fehlt'));
        self::assertSame('a.c', $t->get('a.c'), 'Ein Zweig ist kein Text.');
        self::assertTrue($t->has('a.b'));
        self::assertFalse($t->has('a.c'));
    }
}
