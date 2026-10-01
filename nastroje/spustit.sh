#!/usr/bin/env bash
# Spustí lokální web I. ČLTK Praha – DVA vestavěné servery PHP (port a port+100),
# protože vestavěný server je na Windows jednovláknový (stránka + CSS naráz by ho zasekly).
#
#   nastroje/spustit.sh [port] [databáze] [podsložka]
#
#   nastroje/spustit.sh                                   → 8801 a 8901, web/data/cltk.sqlite
#   nastroje/spustit.sh 8810 web/data/agent-areal.sqlite  → vlastní port a vlastní kopie DB
#   nastroje/spustit.sh 8810 web/data/agent-areal.sqlite /cltkv2
#                                                         → web na http://127.0.0.1:8810/cltkv2/
#                                                           (jako na testu – odhalí cesty „/…“ natvrdo)
#
# Když databáze neexistuje, zkopíruje se z hlavní web/data/cltk.sqlite.
# Servery běží na pozadí, protokoly jsou ve web/data/servery/<port>.log.
# Zastavení: nastroje/zastavit.sh [port]
# Směrovač nastroje/router.php napodobuje .htaccess (403 na inc/ data/ sql/, 404.php).
# Adresa: proměnná CLTK_HOST (výchozí 127.0.0.1) – když port na 127.0.0.1 drží cizí proces,
# poslouží CLTK_HOST=127.0.0.2 (obě adresy jsou místní smyčka).
set -euo pipefail

KOREN="$(cd "$(dirname "$0")/.." && pwd)"
PORT="${1:-8801}"
DB="${2:-web/data/cltk.sqlite}"
PODSLOZKA="${3:-}"
# bez úvodního lomítka – Git Bash by jinak „/cltkv2“ v proměnné prostředí přepsal na cestu Windows
PODSLOZKA_ENV="${PODSLOZKA#/}"
PORT2=$((PORT + 100))
HOST="${CLTK_HOST:-127.0.0.1}"

PHP="$KOREN/_php/php.exe"
[ -x "$PHP" ] || PHP="$(command -v php || true)"
[ -n "$PHP" ] || { echo "PHP nenalezeno (čekám ./_php/php.exe)." >&2; exit 1; }

cd "$KOREN"
case "$DB" in /*|[A-Za-z]:*) DBCESTA="$DB" ;; *) DBCESTA="$KOREN/$DB" ;; esac
if [ ! -f "$DBCESTA" ]; then
  if [ -f "$KOREN/web/data/cltk.sqlite" ] && [ "$DBCESTA" != "$KOREN/web/data/cltk.sqlite" ]; then
    cp "$KOREN/web/data/cltk.sqlite" "$DBCESTA"
    echo "Databáze $DB neexistovala – zkopírována z web/data/cltk.sqlite."
  else
    echo "Databáze $DB neexistuje – zakládám ji (web/sql/seed.php)…"
    CLTK_DB_FILE="$DB" "$PHP" web/sql/seed.php
  fi
fi

mkdir -p web/data/servery
for P in "$PORT" "$PORT2"; do
  if (netstat -ano 2>/dev/null || true) | grep -qE "(${HOST//./\.}|0\.0\.0\.0):$P[[:space:]].*LISTEN"; then
    echo "Port $P už je obsazený – nejdřív nastroje/zastavit.sh $PORT" >&2
    exit 1
  fi
done

for P in "$PORT" "$PORT2"; do
  CLTK_DB_FILE="$DB" CLTK_PODSLOZKA="$PODSLOZKA_ENV" nohup "$PHP" -S "$HOST:$P" -t web nastroje/router.php \
    > "web/data/servery/$P.log" 2>&1 &
  echo $! > "web/data/servery/$P.pid"
done

# počkat, až server odpoví
for i in $(seq 1 40); do
  if curl -s -o /dev/null "http://$HOST:$PORT${PODSLOZKA}/admin/login.php"; then break; fi
  sleep 0.25
done

echo "Web:            http://$HOST:$PORT${PODSLOZKA}/   (druhý server :$PORT2)"
echo "Administrace:   http://$HOST:$PORT${PODSLOZKA}/admin/   admin@cltk.local / cltk-test-2026"
echo "Databáze:       $DB"
echo "Protokol chyb:  web/data/chyby.log"
if [ "$HOST" = "127.0.0.1" ]; then echo "Zastavit:       nastroje/zastavit.sh $PORT"; else echo "Zastavit:       CLTK_HOST=$HOST nastroje/zastavit.sh $PORT"; fi
