#!/usr/bin/env bash
# Prüft, dass das Tracking-Script gzip-komprimiert unter 2 KB (2048 Byte) bleibt.
# Solange das Script noch nicht existiert (bis Phase 3), wird die Prüfung übersprungen.
set -euo pipefail

GRENZE=2048
DATEI="${1:-assets/p.js}"

if [ ! -f "$DATEI" ]; then
    echo "Tracking-Script $DATEI existiert noch nicht. Prüfung übersprungen."
    exit 0
fi

GROESSE=$(gzip -9 -c "$DATEI" | wc -c)
echo "Tracking-Script $DATEI: $GROESSE Byte gzip (Grenze: unter $GRENZE Byte)"

if [ "$GROESSE" -ge "$GRENZE" ]; then
    echo "::error::Das Tracking-Script ist mit $GROESSE Byte gzip nicht kleiner als $GRENZE Byte."
    exit 1
fi
