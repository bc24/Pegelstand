#!/usr/bin/env bash
# Baut das Release-Zip. Aufruf: bin/build-release.sh <Version>, z. B. 1.0.0
# Voraussetzung: "composer install --no-dev --optimize-autoloader" ist gelaufen
# und die Assets in assets/ sind gebaut. Gerüst, wird in Phase 8 ausgebaut.
set -euo pipefail

VERSION="${1:?Version fehlt, z. B. 1.0.0}"
WURZEL="$(cd "$(dirname "$0")/.." && pwd)"
ZIEL="$WURZEL/dist"
STAGING="$ZIEL/pegelstand"

rm -rf "$ZIEL"
mkdir -p "$STAGING"

# Nur ausgewählte Pfade ausliefern (Allowlist), keine Entwicklungsdateien
for pfad in index.php .htaccess LICENSE README.md CHANGELOG.md src config resources assets storage vendor bin; do
    cp -a "$WURZEL/$pfad" "$STAGING/"
done

# vendor/ ist im Repository nicht enthalten und erhält die Zugriffssperre beim Build
cp "$WURZEL/src/.htaccess" "$STAGING/vendor/.htaccess"

(cd "$STAGING" && zip -qr "$ZIEL/pegelstand-$VERSION.zip" .)
echo "Erstellt: dist/pegelstand-$VERSION.zip"
