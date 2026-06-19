#!/usr/bin/env bash
#
# PWA-Icons aus dem Logo generieren (Issue #83).
#
# Das Quell-Logo (public/images/logo.png) ist nicht quadratisch. Damit die
# Icons nicht verzerrt werden, wird zunächst eine quadratische Basis mit weißem
# Rand erzeugt (--padToHeightWidth) und anschließend auf jede Zielgröße
# skaliert. Nutzt das macOS-native `sips` – keine zusätzlichen Abhängigkeiten.
#
# Aufruf (aus dem Projekt-Root):
#   ./bin/generate-pwa-icons.sh

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$ROOT/public/images/logo.png"
OUT="$ROOT/public/icons"
SQUARE="$(mktemp -t endlech-square).png"

if [ ! -f "$SRC" ]; then
    echo "Logo nicht gefunden: $SRC" >&2
    exit 1
fi

mkdir -p "$OUT"

# 1) Quadratische Basis mit weißem Padding (auf die größere Kante).
DIM="$(sips -g pixelWidth -g pixelHeight "$SRC" | awk '/pixel/ {print $2}' | sort -nr | head -1)"
sips --padToHeightWidth "$DIM" "$DIM" --padColor FFFFFF "$SRC" --out "$SQUARE" >/dev/null

# 2) Auf jede Zielgröße skalieren.
for size in 57 60 72 76 114 120 144 152 180 192 512; do
    sips -z "$size" "$size" "$SQUARE" --out "$OUT/icon-${size}.png" >/dev/null
    echo "  -> public/icons/icon-${size}.png"
done

rm -f "$SQUARE"
echo "Fertig: 11 Icons in public/icons/"
