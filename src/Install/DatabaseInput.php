<?php

declare(strict_types=1);

namespace Pegelstand\Install;

/**
 * Datenbank-Zugangsdaten aus dem Installer-Formular samt Validierung.
 */
final class DatabaseInput
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $name,
        public readonly string $user,
        public readonly string $password,
        public readonly string $prefix,
    ) {}

    /**
     * @param array<mixed> $post
     */
    public static function fromPost(array $post): self
    {
        $text = static fn(string $key, string $standard = ''): string => is_string($post[$key] ?? null) ? $post[$key] : $standard;
        $port = trim($text('port', '3306'));

        return new self(
            trim($text('host', 'localhost')),
            ctype_digit($port) ? (int) $port : 0,
            trim($text('name')),
            trim($text('user')),
            $text('password'),
            trim($text('prefix', 'ps_')),
        );
    }

    /**
     * @return array<string, string> Feld zu Schlüssel der Fehlermeldung
     */
    public function validate(): array
    {
        $fehler = [];
        if (preg_match('/^[A-Za-z0-9._\-\[\]:]{1,255}$/', $this->host) !== 1) {
            $fehler['host'] = 'install.fehler.feld.host';
        }
        if ($this->port < 1 || $this->port > 65535) {
            $fehler['port'] = 'install.fehler.feld.port';
        }
        if (preg_match('/^[A-Za-z0-9_$.\-]{1,64}$/', $this->name) !== 1) {
            $fehler['name'] = 'install.fehler.feld.dbname';
        }
        if ($this->user === '' || mb_strlen($this->user) > 80 || preg_match('/[\x00-\x1F]/', $this->user) === 1) {
            $fehler['user'] = 'install.fehler.feld.dbuser';
        }
        if (mb_strlen($this->password) > 200) {
            $fehler['password'] = 'install.fehler.feld.dbpasswort';
        }
        if (preg_match('/^[A-Za-z0-9_]{0,32}$/', $this->prefix) !== 1) {
            $fehler['prefix'] = 'install.fehler.feld.praefix';
        }

        return $fehler;
    }

    /**
     * @return array{host: string, port: int, name: string, user: string, password: string, prefix: string}
     */
    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'name' => $this->name,
            'user' => $this->user,
            'password' => $this->password,
            'prefix' => $this->prefix,
        ];
    }
}
