<?php

declare(strict_types=1);

namespace Pegelstand\Tests\Support;

/**
 * Erzeugt aus den Dokumenten in docs/ die Seiten des GitHub-Wikis (Ordner wiki/). docs/ ist die einzige Quelle,
 * von Hand geschrieben sind nur Home, Sidebar, Footer und die Seiten ohne Entsprechung in docs/.
 */
final class WikiBuilder
{
    public const REPO = 'https://github.com/bc24/Pegelstand';
    public const ROH = 'https://raw.githubusercontent.com/bc24/Pegelstand/main';

    /** Dokument in docs/ => Name der Wiki-Seite */
    public const SEITEN = [
        'installation.md' => 'Installation',
        'aktualisieren.md' => 'Aktualisieren',
        'datensicherung.md' => 'Datensicherung',
        'fehlerbehebung.md' => 'Fehlerbehebung',
        'tracking.md' => 'Tracking-Code-und-Ereignisse',
        'hintergrundjobs.md' => 'Hintergrundjobs',
        'api.md' => 'REST-API',
        'nginx.md' => 'nginx',
        'docker.md' => 'Docker',
        'demo.md' => 'Demo-Modus',
        'performance.md' => 'Leistung',
    ];

    public function __construct(private readonly string $root) {}

    /**
     * @return array<string, string> Dateiname im Wiki => Inhalt
     */
    public function build(): array
    {
        $ergebnis = [];
        foreach (self::SEITEN as $datei => $seite) {
            $inhalt = (string) file_get_contents($this->root . '/docs/' . $datei);
            $ergebnis[$seite . '.md'] = "<!-- Automatisch aus docs/$datei erzeugt (php bin/build-wiki.php). Änderungen bitte dort vornehmen. -->\n" . $this->umschreiben($inhalt);
        }

        return $ergebnis;
    }

    private function umschreiben(string $text): string
    {
        // Beschriftungen wie "docs/nginx.md" werden zum Seitennamen.
        $text = (string) preg_replace_callback('/\[docs\/([a-z\-]+\.md)\]\(/', static fn(array $m): string => '[' . str_replace('-', ' ', self::SEITEN[$m[1]] ?? $m[1]) . '](', $text);
        // Links zwischen Dokumenten werden zu Wiki-Seiten.
        $text = (string) preg_replace_callback('/\]\((?:\.\/)?([a-z\-]+\.md)(#[^)]*)?\)/', static function (array $m): string {
            $seite = self::SEITEN[$m[1]] ?? null;

            return $seite === null ? '](' . self::REPO . '/blob/main/docs/' . $m[1] . ($m[2] ?? '') . ')' : '](' . $seite . ($m[2] ?? '') . ')';
        }, $text);
        // Links aus docs/ heraus (README, CHANGELOG, docker/ …) zeigen auf den Quellcode.
        $text = (string) preg_replace_callback('/\]\(\.\.\/([^)#]+)(#[^)]*)?\)/', static fn(array $m): string => '](' . self::REPO . '/blob/main/' . $m[1] . ($m[2] ?? '') . ')', $text);
        // Bilder
        $text = (string) preg_replace('/\]\((?:\.\/)?bilder\//', '](' . self::ROH . '/docs/bilder/', $text);

        return $text;
    }
}
