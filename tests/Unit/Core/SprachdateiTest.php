<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Core;

use Pegelstand\Core\Translator;
use Pegelstand\Install\Requirements;
use PHPUnit\Framework\TestCase;

/**
 * Stellt sicher, dass jeder in Templates verwendete Text in der Sprachdatei steht.
 */
final class SprachdateiTest extends TestCase
{
    private Translator $translator;

    protected function setUp(): void
    {
        $this->translator = Translator::fromFile(PEGELSTAND_ROOT . '/resources/lang/de.php');
    }

    public function testAlleSchluesselAusTemplatesExistieren(): void
    {
        $fehlend = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(PEGELSTAND_ROOT . '/resources/views'));
        foreach ($iterator as $datei) {
            if (!$datei instanceof \SplFileInfo || $datei->getExtension() !== 'php') {
                continue;
            }
            $inhalt = (string) file_get_contents($datei->getPathname());
            preg_match_all("/->(?:t|translate)\\('([a-z0-9_.]+)'/", $inhalt, $treffer);
            foreach ($treffer[1] as $schluessel) {
                // Schlüssel, die mit einem Punkt enden, werden im Template zusammengesetzt (siehe Tests unten).
                if (!str_ends_with($schluessel, '.') && !$this->translator->has($schluessel)) {
                    $fehlend[] = $datei->getFilename() . ': ' . $schluessel;
                }
            }
        }

        self::assertSame([], $fehlend);
    }

    public function testTexteFuerAllePruefpunkte(): void
    {
        $ids = ['php', 'config_schreibbar', 'storage_schreibbar', 'argon2'];
        foreach (Requirements::REQUIRED_EXTENSIONS as $erweiterung) {
            $ids[] = 'ext_' . $erweiterung;
        }
        foreach ($ids as $id) {
            self::assertTrue($this->translator->has("install.pruefung.punkte.$id.label"), "Label für $id");
        }
        foreach (['ok', 'warn', 'fail', 'pruefe'] as $status) {
            self::assertTrue($this->translator->has("install.pruefung.status.$status"));
        }
    }

    public function testZusammengesetzteSchluesselExistieren(): void
    {
        foreach (['pruefung', 'datenbank', 'admin', 'fertig'] as $schritt) {
            self::assertTrue($this->translator->has("install.schritte.$schritt"), $schritt);
        }
        foreach (['404', '405', '419', '500', 'datenbank', 'wartung'] as $art) {
            self::assertTrue($this->translator->has("fehlerseite.$art.titel"), $art);
            self::assertTrue($this->translator->has("fehlerseite.$art.text"), $art);
        }
    }

    public function testFehlermeldungenDerInstallationExistieren(): void
    {
        foreach (['db_zugang', 'db_unbekannt', 'db_rechte', 'db_server', 'db_allgemein', 'dbversion', 'vorhanden', 'migration', 'config_schreiben', 'allgemein', 'csrf'] as $key) {
            self::assertTrue($this->translator->has("install.fehler.$key"), $key);
        }
        foreach (['host', 'port', 'dbname', 'dbuser', 'dbpasswort', 'praefix', 'name', 'email', 'passwort_kurz', 'passwort_lang', 'passwort_gleich'] as $key) {
            self::assertTrue($this->translator->has("install.fehler.feld.$key"), $key);
        }
    }

    public function testKeineEnglischenPlatzhalterOderAusrufezeichenInflation(): void
    {
        $alles = (string) file_get_contents(PEGELSTAND_ROOT . '/resources/lang/de.php');

        self::assertStringNotContainsString('!!', $alles);
        self::assertDoesNotMatchRegularExpression('/\b(Ihr|Ihre|Ihren|Ihrem|Ihnen)\b|\bSie (können|müssen|haben|sind|werden)\b/', $alles, 'Die Ansprache erfolgt mit „du“.');
    }
}
