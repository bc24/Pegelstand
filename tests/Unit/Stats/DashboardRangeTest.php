<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Unit\Stats;

use DateTimeImmutable;
use DateTimeZone;
use Pegelstand\Stats\DashboardRange;
use Pegelstand\Stats\ReferrerClassifier;
use PHPUnit\Framework\TestCase;

final class DashboardRangeTest extends TestCase
{
    private function zeit(string $utc): DateTimeImmutable
    {
        return new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    }

    private function berlin(): DateTimeZone
    {
        return new DateTimeZone('Europe/Berlin');
    }

    public function testStandardIst30TageBisHeute(): void
    {
        $r = DashboardRange::create($this->berlin(), null, null, $this->zeit('2026-10-01 10:00:00'));

        self::assertSame('2026-09-02', $r->from);
        self::assertSame('2026-10-01', $r->to);
        self::assertSame('2026-08-03', $r->previousFrom);
        self::assertSame('2026-09-01', $r->previousTo);
        self::assertFalse($r->hourly);
        self::assertCount(30, $r->buckets);
        self::assertCount(30, $r->previousBuckets);
        self::assertSame('2026-09-02', $r->buckets[0]->day);
        self::assertNull($r->buckets[0]->hour);
    }

    public function testTagesgrenzenSindMitternachtInDerZeitzoneDerSite(): void
    {
        $r = DashboardRange::create($this->berlin(), '2026-10-01', '2026-10-03', $this->zeit('2026-10-05 12:00:00'));

        self::assertSame('2026-09-30 22:00:00', $r->current->startUtc, 'Berlin ist im Oktober UTC+2.');
        self::assertSame('2026-10-03 22:00:00', $r->current->endUtc);
        self::assertSame('2026-09-27 22:00:00', $r->previous->startUtc);
        self::assertSame('2026-09-30 22:00:00', $r->previous->endUtc);
    }

    public function testHeuteIstStuendlichUndBeginntMitDerOrtszeit(): void
    {
        // 14:30 Uhr in Berlin (MESZ).
        $r = DashboardRange::create($this->berlin(), '2026-10-01', '2026-10-01', $this->zeit('2026-10-01 12:30:00'));

        self::assertTrue($r->hourly);
        self::assertSame('2026-10-01', $r->today);
        self::assertSame(14, $r->currentHour);
        self::assertSame('14:30', $r->nowLabel);
        self::assertCount(15, $r->buckets, 'Stunden 0 bis 14.');
        self::assertSame(0, $r->buckets[0]->hour);
        self::assertSame(14, $r->buckets[14]->hour);
        self::assertCount(24, $r->previousBuckets, 'Der Vortag ist vollständig.');
        // Der Vergleich deckt nur die gleiche Tageszeit ab (bis 14:30), nicht den ganzen Vortag.
        self::assertSame('2026-09-29 22:00:00', $r->previous->startUtc);
        self::assertSame('2026-09-30 12:30:00', $r->previous->endUtc);
    }

    public function testGesternIstVollstaendigUndWirdMitDemVortagVerglichen(): void
    {
        $r = DashboardRange::create($this->berlin(), '2026-09-30', '2026-09-30', $this->zeit('2026-10-01 12:30:00'));

        self::assertCount(24, $r->buckets);
        self::assertSame('2026-09-29 22:00:00', $r->current->startUtc);
        self::assertSame('2026-09-30 22:00:00', $r->current->endUtc);
        self::assertSame('2026-09-29 22:00:00', $r->previous->endUtc);
    }

    public function testSommerzeitwechselErgibt25StundenBzw23Stunden(): void
    {
        $herbst = DashboardRange::create($this->berlin(), '2026-10-25', '2026-10-25', $this->zeit('2026-11-01 12:00:00'));
        $fruehling = DashboardRange::create($this->berlin(), '2026-03-29', '2026-03-29', $this->zeit('2026-04-05 12:00:00'));

        self::assertCount(25, $herbst->buckets);
        self::assertCount(23, $fruehling->buckets);
        self::assertSame('2026-10-24 22:00:00', $herbst->current->startUtc);
        self::assertSame('2026-10-25 23:00:00', $herbst->current->endUtc);
    }

    public function testEingabenWerdenBegrenzt(): void
    {
        $jetzt = $this->zeit('2026-10-01 10:00:00');

        $zukunft = DashboardRange::create($this->berlin(), '2026-09-25', '2030-01-01', $jetzt);
        self::assertSame('2026-10-01', $zukunft->to);

        $vertauscht = DashboardRange::create($this->berlin(), '2026-09-30', '2026-09-20', $jetzt);
        self::assertSame(['2026-09-20', '2026-09-30'], [$vertauscht->from, $vertauscht->to]);

        $unsinn = DashboardRange::create($this->berlin(), 'quatsch', '2026-02-30', $jetzt);
        self::assertSame('2026-10-01', $unsinn->to, 'Ungültige Daten fallen auf Standardwerte zurück.');
        self::assertSame('2026-09-02', $unsinn->from);

        $lang = DashboardRange::create($this->berlin(), '1990-01-01', '2026-10-01', $jetzt);
        self::assertSame(DashboardRange::MAX_DAYS, DashboardRange::daysBetween($lang->from, $lang->to));
    }

    public function testTageRechnen(): void
    {
        self::assertSame('2026-03-01', DashboardRange::addDays('2026-02-28', 1));
        self::assertSame('2025-12-31', DashboardRange::addDays('2026-01-01', -1));
        self::assertSame(1, DashboardRange::daysBetween('2026-01-01', '2026-01-01'));
        self::assertSame(31, DashboardRange::daysBetween('2026-01-01', '2026-01-31'));
        self::assertSame('2026-02-28', DashboardRange::gueltigerTag('2026-02-28'));
        self::assertNull(DashboardRange::gueltigerTag('2026-02-30'));
        self::assertNull(DashboardRange::gueltigerTag('28.02.2026'));
    }

    public function testReferrerGruppen(): void
    {
        $c = ReferrerClassifier::fromFile(PEGELSTAND_ROOT . '/resources/data/quellen.php');

        self::assertSame(['id' => 'google', 'name' => 'Google', 'gruppe' => 'suche'], $c->classify('google.de'));
        self::assertSame('google', $c->classify('google.co.uk')['id']);
        self::assertSame('google', $c->classify('news.google.com')['id']);
        self::assertSame('bing', $c->classify('bing.com')['id']);
        self::assertSame(['id' => 'facebook', 'name' => 'Facebook', 'gruppe' => 'sozial'], $c->classify('m.facebook.com'));
        self::assertSame('x', $c->classify('t.co')['id']);
        self::assertSame('mastodon', $c->classify('mastodon.social')['id']);
        self::assertSame(['id' => 'heise.de', 'name' => 'heise.de', 'gruppe' => 'referrer'], $c->classify('heise.de'));
        self::assertSame('referrer', $c->classify('notgoogle.example')['gruppe'], 'Nur echte Domains und Subdomains zählen.');
        self::assertSame('referrer', $c->classify('google.evil.example')['gruppe']);
    }
}
