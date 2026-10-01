#!/usr/bin/env bash
# Zastaví lokální servery PHP spuštěné nastroje/spustit.sh (port a port+100).
#   nastroje/zastavit.sh [port]        (výchozí 8801; adresa z CLTK_HOST, výchozí 127.0.0.1)
# Zastaví jen php.exe, který na daném portu opravdu poslouchá – nic jiného.
# Na Windows může na jednom portu poslouchat i víc serverů PHP najednou
# (PHP nastavuje SO_REUSEADDR) – zastaví všechny.
set -uo pipefail

KOREN="$(cd "$(dirname "$0")/.." && pwd)"
PORT="${1:-8801}"
HOST="${CLTK_HOST:-127.0.0.1}"

for P in "$PORT" "$((PORT + 100))"; do
  PIDY="$(netstat -ano 2>/dev/null | grep -E "${HOST//./\.}:$P[[:space:]].*LISTEN" | awk '{print $NF}' | sort -u)"
  if [ -z "$PIDY" ]; then
    echo "Na portu $P nic neběží."
  fi
  for WPID in $PIDY; do
    [ "$WPID" != "0" ] || continue
    if tasklist //FI "PID eq $WPID" 2>/dev/null | grep -qi 'php'; then
      taskkill //F //PID "$WPID" > /dev/null 2>&1 && echo "Zastaven server na portu $P (PID $WPID)."
    else
      echo "Na portu $P běží něco jiného než PHP (PID $WPID) – nechávám být."
    fi
  done
  rm -f "$KOREN/web/data/servery/$P.pid"
done
