<?php

declare(strict_types=1);

namespace Pegelstand\Jobs;

use DateTimeImmutable;

/** Ein Hintergrundjob. Er läuft höchstens einmal gleichzeitig und frühestens nach `interval()` Sekunden erneut. */
interface Job
{
    public function name(): string;

    /** Mindestabstand zwischen zwei Läufen in Sekunden. */
    public function interval(): int;

    /** Höchste erwartete Laufzeit in Sekunden. Danach gilt die Sperre eines abgestürzten Laufs als frei. */
    public function timeout(): int;

    /** @return string Kurzmeldung für die Statusanzeige, z. B. "3 Sites, 5 Tage" */
    public function run(DateTimeImmutable $now): string;
}
