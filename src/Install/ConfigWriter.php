<?php

declare(strict_types=1);

namespace Pegelstand\Install;

/**
 * Schreibt config/config.php und die Sperrdatei. Die Datei entsteht atomar, damit nie eine halbe Konfiguration liegt.
 */
final class ConfigWriter
{
    /**
     * @param array<string, mixed> $konfiguration
     */
    public function write(string $configDir, array $konfiguration): void
    {
        if (!is_dir($configDir) && !@mkdir($configDir, 0750, true) && !is_dir($configDir)) {
            throw new InstallException('install.fehler.config_schreiben');
        }
        $inhalt = "<?php\n\n// Von Pegelstand bei der Installation erzeugt. Diese Datei enthält Zugangsdaten und darf nicht weitergegeben werden.\n"
            . "declare(strict_types=1);\n\ndefined('PEGELSTAND_ROOT') || exit;\n\nreturn "
            . var_export($konfiguration, true) . ";\n";

        $this->atomar($configDir . '/config.php', $inhalt);
        $this->atomar($configDir . '/installed.lock', gmdate('c') . "\n");
    }

    private function atomar(string $ziel, string $inhalt): void
    {
        $temp = $ziel . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($temp, $inhalt, LOCK_EX) === false) {
            throw new InstallException('install.fehler.config_schreiben');
        }
        @chmod($temp, 0640);
        if (!@rename($temp, $ziel)) {
            @unlink($temp);
            throw new InstallException('install.fehler.config_schreiben');
        }
    }
}
