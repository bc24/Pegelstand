<?php

declare(strict_types=1);

namespace Pegelstand\Stats;

/** Ordnet Referrer-Domains einer Gruppe (Suche, Soziale Netzwerke, sonstige) zu. Die Regeln stehen in resources/data/quellen.php. */
final class ReferrerClassifier
{
    /** @var array<string, array{id: string, name: string, gruppe: string}> */
    private array $cache = [];

    /**
     * @param list<array{id: string, name: string, gruppe: string, muster: string}> $regeln
     */
    public function __construct(private readonly array $regeln) {}

    public static function fromFile(string $datei): self
    {
        $liste = is_file($datei) ? (static fn(): mixed => require $datei)() : [];
        $regeln = [];
        foreach (is_array($liste) ? $liste : [] as $regel) {
            if (is_array($regel) && is_string($regel['id'] ?? null) && is_string($regel['name'] ?? null) && is_string($regel['gruppe'] ?? null) && is_string($regel['muster'] ?? null)) {
                $regeln[] = ['id' => $regel['id'], 'name' => $regel['name'], 'gruppe' => $regel['gruppe'], 'muster' => $regel['muster']];
            }
        }

        return new self($regeln);
    }

    /**
     * @return array{id: string, name: string, gruppe: string}
     */
    public function classify(string $host): array
    {
        if (isset($this->cache[$host])) {
            return $this->cache[$host];
        }
        foreach ($this->regeln as $regel) {
            if (preg_match($regel['muster'], $host) === 1) {
                return $this->cache[$host] = ['id' => $regel['id'], 'name' => $regel['name'], 'gruppe' => $regel['gruppe']];
            }
        }

        return $this->cache[$host] = ['id' => $host, 'name' => $host, 'gruppe' => 'referrer'];
    }
}
