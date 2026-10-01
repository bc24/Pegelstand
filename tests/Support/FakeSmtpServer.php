<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Support;

/**
 * Minimaler SMTP-Server für Tests: startet als Kindprozess auf einem freien Port, nimmt genau eine Nachricht an
 * und schreibt das Gespräch in eine Datei. `auth`: Zugangsdaten als "benutzer:passwort" oder leer (keine Anmeldung nötig).
 */
final class FakeSmtpServer
{
    /** @var resource|null */
    private $prozess;
    public readonly int $port;
    public readonly string $protokoll;

    public function __construct(string $auth = '', bool $ablehnen = false)
    {
        $this->protokoll = tempnam(sys_get_temp_dir(), 'smtp') ?: '';
        $this->port = random_int(20000, 60000);
        $skript = __DIR__ . '/fake-smtp.php';
        $this->prozess = proc_open(
            [PHP_BINARY, $skript, (string) $this->port, $this->protokoll, $auth, $ablehnen ? '1' : '0'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        ) ?: null;
        for ($i = 0; $i < 50; ++$i) {
            $s = @fsockopen('127.0.0.1', $this->port, $n, $t, 0.1);
            if ($s !== false) {
                fclose($s);
                // Der Testserver nimmt nur eine Verbindung an: Wartetest verbraucht sie, daher neu starten.
                break;
            }
            usleep(100_000);
        }
    }

    public function inhalt(): string
    {
        return (string) @file_get_contents($this->protokoll);
    }

    public function stop(): void
    {
        if (is_resource($this->prozess)) {
            proc_terminate($this->prozess);
            proc_close($this->prozess);
        }
        @unlink($this->protokoll);
    }
}
