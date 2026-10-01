<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

/**
 * Liefert Zahlen für das Dashboard, entweder aus Aggregaten oder aus Rohdaten.
 *
 * Kennzahlen (`Totals`): besucher, aufrufe (Seitenaufrufe), besuche (Sitzungen), bounces (Absprünge), dauer (Summe der Sitzungsdauern in Sekunden).
 * Zeilen (`Row`): key (Wörterbuch-ID, Ländercode-Zahl o. Ä.), besucher, aufrufe, besuche, bounces.
 *
 * @phpstan-type Totals array{besucher: int, aufrufe: int, besuche: int, bounces: int, dauer: int}
 * @phpstan-type Row array{key: int, besucher: int, aufrufe: int, besuche: int, bounces: int}
 */
interface StatsSource
{
    /** @return Totals */
    public function totals(int $siteId, Period $period): array;

    /**
     * Eine Kennzahlenzeile je Punkt der Zeitreihe, in derselben Reihenfolge wie $buckets.
     *
     * @param list<Bucket> $buckets
     * @return list<Totals>
     */
    public function series(int $siteId, array $buckets): array;

    /**
     * Aufschlüsselung nach einer Dimension (siehe Dimension), absteigend nach Besuchern, höchstens $limit Zeilen.
     * Mit $keys nur diese Schlüssel (für Seiten und Ereignisse, etwa bei Zielen).
     *
     * @param list<int>|null $keys
     * @return list<Row>
     */
    public function dimension(int $siteId, int $dim, Period $period, int $limit, ?array $keys = null): array;

    /**
     * Seitenaufrufe je Seite (Wörterbuch-ID), für die Ausstiegsrate.
     *
     * @param list<int> $pathIds
     * @return array<int, int>
     */
    public function pageViews(int $siteId, Period $period, array $pathIds): array;

    /**
     * Eigenschaften der Ereignisse: Ereignisname-ID => Liste aus Eigenschaft, Wert und Anzahl.
     *
     * @return array<int, list<array{key_id: int, value_id: int, anzahl: int}>>
     */
    public function eventProps(int $siteId, Period $period): array;
}
