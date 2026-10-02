#!/usr/bin/env bash
# Baut das Release-Zip für Endnutzer. Aufruf: bin/build-release.sh <Version>, z. B. 1.0.0
# Voraussetzung: Die Assets in assets/ sind gebaut (npm run build) und Composer ist installiert.
# Das Zip enthält nur ausgewählte Pfade (Allowlist), nie Konfiguration, Logs, Sitzungen oder Entwicklungsdateien.
set -euo pipefail

VERSION="${1:?Version fehlt, z. B. 1.0.0}"
WURZEL="$(cd "$(dirname "$0")/.." && pwd)"
ZIEL="${PEGELSTAND_DIST:-$WURZEL/dist}"
STAGING="$ZIEL/pegelstand"

[ -f "$WURZEL/assets/js/pegelstand.js" ] && [ -f "$WURZEL/assets/p.js" ] || {
    echo "Die Assets fehlen. Führe zuerst 'npm run build' aus." >&2
    exit 1
}

rm -rf "$ZIEL"
mkdir -p "$STAGING/config" "$STAGING/storage" "$STAGING/bin"

# Ganze Ordner, die nur Code und Vorlagen enthalten
for pfad in src database resources licenses docs assets; do
    cp -a "$WURZEL/$pfad" "$STAGING/"
done
# Einzelne Dateien
for datei in index.php .htaccess LICENSE README.md CHANGELOG.md THIRD-PARTY.md composer.json composer.lock; do
    cp -a "$WURZEL/$datei" "$STAGING/"
done
# Aus config/, storage/ und bin/ nur das Nötige, damit nie lokale Zugangsdaten oder Logs im Zip landen
cp -a "$WURZEL/config/.htaccess" "$WURZEL/config/config.example.php" "$STAGING/config/"
cp -a "$WURZEL/storage/.htaccess" "$WURZEL/storage/.gitkeep" "$STAGING/storage/"
cp -a "$WURZEL/bin/.htaccess" "$WURZEL/bin/cron.php" "$STAGING/bin/"

# Nur-Entwicklungs-Dateien gehören nicht ins Release
rm -f "$STAGING"/assets/css/komponentenseite.css "$STAGING"/assets/js/komponentenseite.js \
  "$STAGING"/resources/css/komponentenseite.css "$STAGING"/resources/js/komponentenseite.js
rm -rf "$STAGING/docs/bilder-quellen" "$STAGING/assets/.gitkeep"

# Abhängigkeiten ohne Entwicklungspakete, frisch aufgebaut
(cd "$STAGING" && composer install --no-dev --optimize-autoloader --no-interaction --no-progress --quiet)
rm -f "$STAGING/composer.lock"
# vendor/ liegt im Document Root und wird gesperrt
cp "$WURZEL/src/.htaccess" "$STAGING/vendor/.htaccess"

# Version eintragen
sed -i "s/public const CURRENT = '[^']*';/public const CURRENT = '$VERSION';/" "$STAGING/src/Version.php"

(cd "$ZIEL" && zip -qr "pegelstand-$VERSION.zip" pegelstand)
(cd "$ZIEL" && sha256sum "pegelstand-$VERSION.zip" > "pegelstand-$VERSION.zip.sha256")
echo "Erstellt: $ZIEL/pegelstand-$VERSION.zip"
