<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

/** Erkennt Automaten an ihrem User-Agent. Die Liste liegt in resources/data/bots.php. */
final class BotFilter
{
    /**
     * @param list<string> $patterns Teilstrings in Kleinbuchstaben
     */
    public function __construct(private readonly array $patterns) {}

    public static function fromFile(string $file): self
    {
        $liste = is_file($file) ? (static fn(): mixed => require $file)() : [];

        return new self(is_array($liste) ? array_values(array_filter($liste, 'is_string')) : []);
    }

    public function isBot(string $userAgent): bool
    {
        // Echte Browser senden immer einen ausführlichen User-Agent.
        if (strlen(trim($userAgent)) < 12) {
            return true;
        }
        $klein = strtolower($userAgent);
        foreach ($this->patterns as $muster) {
            if (str_contains($klein, $muster)) {
                return true;
            }
        }

        return false;
    }
}
