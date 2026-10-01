<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Core;

use Pegelstand\Core\Translator;
use Pegelstand\Core\View;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ViewTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/ps-view-' . bin2hex(random_bytes(4));
        mkdir($this->dir . '/teil', 0777, true);
        file_put_contents($this->dir . '/seite.php', '<p><?= $this->e($name) ?></p><?= $this->t("gruss", ["wer" => $name]) ?>');
        file_put_contents($this->dir . '/layout.php', '<main><?= $inhalt ?></main>');
        file_put_contents($this->dir . '/teil/kaputt.php', '<?php throw new RuntimeException("kaputt");');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*.php') ?: []);
        @unlink($this->dir . '/teil/kaputt.php');
        @rmdir($this->dir . '/teil');
        @rmdir($this->dir);
    }

    private function view(string $basis = ''): View
    {
        return new View($this->dir, new Translator(['gruss' => 'Hallo <{wer}>']), $basis);
    }

    public function testAusgabeWirdMaskiert(): void
    {
        $html = $this->view()->render('seite', ['name' => '<script>alert(1)</script>'], null);

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringContainsString('Hallo &lt;&lt;script&gt;', $html, 'Auch übersetzte Texte samt Platzhaltern werden maskiert.');
    }

    public function testAnfuehrungszeichenWerdenMaskiert(): void
    {
        self::assertSame('&quot;a&quot; &amp; &#039;b&#039;', $this->view()->e('"a" & \'b\''));
        self::assertSame('', $this->view()->e(['kein Skalar']));
    }

    public function testLayoutUmschliesstInhalt(): void
    {
        $html = $this->view()->render('seite', ['name' => 'x']);

        self::assertStringStartsWith('<main><p>x</p>', $html);
    }

    public function testUrlsUndIconsBeruecksichtigenUnterverzeichnis(): void
    {
        $view = $this->view('/pegelstand');

        self::assertSame('/pegelstand/install', $view->url('/install'));
        self::assertSame('/pegelstand/assets/css/a.css', $view->asset('css/a.css'));
        self::assertStringContainsString('href="/pegelstand/assets/icons.svg#ps-icon-check"', $view->icon('check'));
        self::assertStringContainsString('ps-icon--s', $view->icon('check', 's'));
    }

    public function testUngueltigeTemplateNamenWerdenAbgelehnt(): void
    {
        $this->expectException(RuntimeException::class);
        $this->view()->render('../etc/passwd', [], null);
    }

    public function testFehlendesTemplate(): void
    {
        $this->expectException(RuntimeException::class);
        $this->view()->render('gibt-es-nicht', [], null);
    }

    public function testFehlerImTemplateLeertDenPuffer(): void
    {
        $stufe = ob_get_level();
        try {
            $this->view()->render('teil/kaputt', [], null);
            self::fail('Ausnahme erwartet');
        } catch (RuntimeException) {
            self::assertSame($stufe, ob_get_level());
        }
    }
}
