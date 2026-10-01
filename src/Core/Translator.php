<?php

declare(strict_types=1);

namespace Pegelstand\Core;

use RuntimeException;

/**
 * Übersetzungen aus Sprachdateien (resources/lang/<sprache>.php). Deutsch ist die einzige Sprache
 * in Version 1, die Struktur erlaubt weitere. Schlüssel nutzen Punktschreibweise.
 */
final class Translator
{
    /**
     * @param array<string, mixed> $messages
     */
    public function __construct(private readonly array $messages) {}

    public static function fromFile(string $file): self
    {
        $inhalt = (static fn(): mixed => require $file)();
        if (!is_array($inhalt)) {
            throw new RuntimeException('Die Sprachdatei muss ein Array zurückgeben.');
        }

        /** @var array<string, mixed> $inhalt */
        return new self($inhalt);
    }

    public function has(string $key): bool
    {
        return is_string($this->find($key));
    }

    /**
     * @param array<string, scalar> $params Ersetzt {name} im Text
     */
    public function get(string $key, array $params = []): string
    {
        $text = $this->find($key);
        if (!is_string($text)) {
            return $key;
        }
        foreach ($params as $name => $wert) {
            $text = str_replace('{' . $name . '}', (string) $wert, $text);
        }

        return $text;
    }

    private function find(string $key): mixed
    {
        $aktuell = $this->messages;
        foreach (explode('.', $key) as $teil) {
            if (!is_array($aktuell) || !array_key_exists($teil, $aktuell)) {
                return null;
            }
            $aktuell = $aktuell[$teil];
        }

        return $aktuell;
    }
}
