<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Stats;

use Pegelstand\Stats\CsvExport;
use PHPUnit\Framework\TestCase;

final class CsvExportTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function daten(): array
    {
        return [
            'diagramm' => [
                'etiketten' => [['datum' => '2026-10-01'], ['datum' => '2026-10-02', 'stunde' => 9]],
                'reihen' => [
                    'besucher' => ['aktuell' => [10, 12]],
                    'aufrufe' => ['aktuell' => [25, 30]],
                    'seitenProBesuch' => ['aktuell' => [2.5, 2.4999]],
                    'absprungrate' => ['aktuell' => [40.5, 38.0]],
                    'dauer' => ['aktuell' => [125.25, 90]],
                ],
            ],
            'tabellen' => [
                'seiten' => ['top' => [
                    ['name' => '/preise', 'besucher' => 1234, 'anteil' => 45.678, 'aufrufe' => 2000, 'absprung' => 40.0, 'ausstieg' => 12.5],
                    ['name' => '=HYPERLINK("http://evil")', 'besucher' => 1, 'anteil' => 0.05, 'aufrufe' => 1, 'absprung' => 0, 'ausstieg' => 0],
                    ['name' => 'a;b "c"', 'besucher' => 1, 'anteil' => 0, 'aufrufe' => 1, 'absprung' => 0, 'ausstieg' => 0],
                ]],
                'ereignisse' => [['name' => 'Anmeldung', 'besucher' => 3, 'anzahl' => 4, 'rate' => 1.5]],
            ],
        ];
    }

    public function testZeitverlauf(): void
    {
        $csv = CsvExport::build('zeitverlauf', $this->daten());

        self::assertNotNull($csv);
        self::assertStringStartsWith("\xEF\xBB\xBF", $csv, 'BOM, damit Excel UTF-8 erkennt.');
        $zeilen = explode("\r\n", trim(substr($csv, 3)));
        self::assertSame('Zeitpunkt;Besucher;Seitenaufrufe;Seiten pro Besuch;Absprungrate (%);Besuchsdauer (Sekunden)', $zeilen[0]);
        self::assertSame('01.10.2026;10;25;2,5;40,5;125,25', $zeilen[1]);
        self::assertSame('02.10.2026 09:00;12;30;2,5;38;90', $zeilen[2]);
    }

    public function testTabelleMitDezimalkommaUndSchutz(): void
    {
        $csv = (string) CsvExport::build('seiten', $this->daten());
        $zeilen = explode("\r\n", trim(substr($csv, 3)));

        self::assertSame('Seite;Besucher;Anteil (%);Seitenaufrufe;Absprungrate (%);Ausstiegsrate (%)', $zeilen[0]);
        self::assertSame('/preise;1234;45,68;2000;40;12,5', $zeilen[1]);
        self::assertStringStartsWith('"\'=HYPERLINK(', $zeilen[2], 'Formeln werden entschärft.');
        self::assertStringStartsWith('"a;b ""c""";', $zeilen[3], 'Trenner und Anführungszeichen werden maskiert.');
    }

    public function testEreignisse(): void
    {
        $csv = (string) CsvExport::build('ereignisse', $this->daten());

        self::assertStringContainsString("Ereignis;Besucher;Anzahl;Rate (%)\r\nAnmeldung;3;4;1,5\r\n", $csv);
    }

    public function testLeereTabelleHatNurDieKopfzeile(): void
    {
        $csv = (string) CsvExport::build('laender', []);

        self::assertSame("\xEF\xBB\xBFLand;Besucher;Anteil (%);Seitenaufrufe;Absprungrate (%);Ausstiegsrate (%)\r\n", $csv);
    }

    public function testUnbekannteTabelle(): void
    {
        self::assertNull(CsvExport::build('passwoerter', []));
    }
}
