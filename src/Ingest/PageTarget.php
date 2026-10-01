<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

/** Aufbereitete Adresse einer Seite: Host, bereinigter Pfad und UTM-Angaben. */
final class PageTarget
{
    /**
     * @param array<string, string> $utm Schlüssel: source, medium, campaign, term, content
     */
    public function __construct(
        public readonly string $host,
        public readonly string $path,
        public readonly array $utm,
    ) {}
}
