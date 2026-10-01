<?php

declare(strict_types=1);

namespace Pegelstand\Ingest;

/**
 * Prüft, ob ein Hostname zu einer Site gehört: die Hauptdomain (mit und ohne "www."), zusätzlich erlaubte
 * Hostnamen und Wildcards wie "*.example.com" (alle Subdomains).
 */
final class HostMatcher
{
    /** @var list<string> */
    private array $exact = [];

    /** @var list<string> */
    private array $suffixes = [];

    public function __construct(string $domain, ?string $allowedHosts = null)
    {
        $this->exact[] = self::clean($domain);
        foreach (preg_split('/[\s,]+/', (string) $allowedHosts, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $eintrag) {
            $eintrag = self::clean($eintrag);
            if (str_starts_with($eintrag, '*.')) {
                $this->suffixes[] = substr($eintrag, 1);
            } elseif ($eintrag !== '') {
                $this->exact[] = $eintrag;
            }
        }
    }

    public function accepts(string $host): bool
    {
        $host = self::clean($host);
        if ($host === '') {
            return false;
        }
        if (in_array($host, $this->exact, true)) {
            return true;
        }
        foreach ($this->suffixes as $suffix) {
            if (str_ends_with($host, $suffix) && strlen($host) > strlen($suffix)) {
                return true;
            }
        }

        return false;
    }

    private static function clean(string $host): string
    {
        $host = strtolower(trim($host));
        $host = (string) preg_replace('/:\d+$/', '', $host);
        $host = rtrim($host, '.');

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
