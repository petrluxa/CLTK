#!/usr/bin/env bash
# Založí lokální databázi znovu a naplní ji výchozím obsahem (web/sql/seed.php --novy).
#
#   nastroje/novy-seed.sh                              → hlavní web/data/cltk.sqlite
#   nastroje/novy-seed.sh web/data/agent-areal.sqlite  → vlastní kopie agenta
#   nastroje/novy-seed.sh web/data/x.sqlite --z-dat    → obsah ze sql/data.json (jako instalace.php)
#
# Nad databází nesmí běžet server (Windows soubor nepustí) – zastavte ho nastroje/zastavit.sh.
# Obrázky v uploads/ se znovu nezpracovávají, když už jsou hotové.
# Lokálně vznikne zkušební účet admin@cltk.local / cltk-test-2026 a režim přípravy je vypnutý.
set -euo pipefail

KOREN="$(cd "$(dirname "$0")/.." && pwd)"
DB="${1:-web/data/cltk.sqlite}"
shift || true

PHP="$KOREN/_php/php.exe"
[ -x "$PHP" ] || PHP="$(command -v php)"

cd "$KOREN"
CLTK_DB_FILE="$DB" "$PHP" -d memory_limit=1024M web/sql/seed.php --novy "$@"
