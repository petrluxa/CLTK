#!/usr/bin/env bash
# Vlastní kopie databáze pro agenta / pokus: zkopíruje hlavní web/data/cltk.sqlite.
#
#   nastroje/kopie-db.sh agent-areal        → web/data/agent-areal.sqlite (přepíše starou kopii)
#
# Pak:  CLTK_DB_FILE=web/data/agent-areal.sqlite …   nebo   nastroje/spustit.sh 8810 web/data/agent-areal.sqlite
set -euo pipefail

KOREN="$(cd "$(dirname "$0")/.." && pwd)"
JMENO="${1:?Zadejte jméno kopie, např. agent-areal}"
case "$JMENO" in *[!a-z0-9-]*) echo "Jméno smí mít jen malá písmena, číslice a pomlčku." >&2; exit 1 ;; esac
[ "$JMENO" != "cltk" ] || { echo "Hlavní databázi takhle nepřepisujte." >&2; exit 1; }
[ -f "$KOREN/web/data/cltk.sqlite" ] || { echo "Hlavní databáze chybí – nejdřív nastroje/novy-seed.sh" >&2; exit 1; }

cp "$KOREN/web/data/cltk.sqlite" "$KOREN/web/data/$JMENO.sqlite"
echo "Hotovo: web/data/$JMENO.sqlite"
echo "Použití: CLTK_DB_FILE=web/data/$JMENO.sqlite   (nebo nastroje/spustit.sh <port> web/data/$JMENO.sqlite)"
