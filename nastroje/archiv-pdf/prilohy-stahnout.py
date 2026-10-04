"""Další přílohy ze stránek starého webu (podklady/_raw/prilohy/nove-prilohy.json) – stažení.

Adresy v seznamu jsou zapsané různě (HTML entity „&iacute;“, %-kódování, http/https,
„?download“). Skript je převede na jednotný tvar, porovná s už staženými soubory
(podklady/data/mapa-souboru.json) a nové soubory stáhne postupně s pauzou do
podklady/_raw/prilohy/stazene/<id-na-files.cltk.cz>.pdf. Zápis: podklady/_raw/prilohy/stazeni.json

Spuštění z kořene projektu:  python nastroje/archiv-pdf/prilohy-stahnout.py
"""
import hashlib
import html
import json
import sys
import time
import urllib.parse
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from stahnout import stahni  # noqa: E402

KOREN = Path(__file__).resolve().parents[2]
PRILOHY = KOREN / 'podklady/_raw/prilohy'
SEZNAM = PRILOHY / 'nove-prilohy.json'
MAPA = KOREN / 'podklady/data/mapa-souboru.json'
CIL = PRILOHY / 'stazene'
LOG = PRILOHY / 'stazeni.json'


def klic(url: str) -> str:
    """Jednotný tvar adresy pro porovnání: entity a %-kódy rozbalené, https, bez ?download."""
    u = urllib.parse.unquote(html.unescape(url.strip()))
    u = u.split('?')[0]
    if u.startswith('http://'):
        u = 'https://' + u[len('http://'):]
    return u


def ke_stazeni(url: str) -> str:
    """Adresa pro HTTP: cesta znovu %-zakódovaná (mezery, diakritika, „+“, „:“)."""
    p = urllib.parse.urlsplit(klic(url))
    return urllib.parse.urlunsplit(('https', p.netloc, urllib.parse.quote(p.path, safe='/'), '', ''))


def main() -> None:
    seznam = json.loads(SEZNAM.read_text(encoding='utf-8'))
    mapa = json.loads(MAPA.read_text(encoding='utf-8'))
    zname = {}
    for m in mapa:
        zname.setdefault(klic(m['stara_url']), m['nova_cesta'])
    log = json.loads(LOG.read_text(encoding='utf-8')) if LOG.exists() else {}
    CIL.mkdir(parents=True, exist_ok=True)
    videne: dict[str, str] = {}
    for i, p in enumerate(seznam, 1):
        k = klic(p['url'])
        zaznam = dict(url=p['url'], klic=k, stazeni=ke_stazeni(p['url']), text=p['text'], stranky=p['stranky'])
        if k in zname:
            zaznam.update(stav='uz-mame', nova_cesta=zname[k])
        elif k in videne:
            zaznam.update(stav='duplicita-v-seznamu', soubor=videne[k])
        else:
            ident = urllib.parse.urlsplit(k).path.split('/')[1]
            soubor = f'{ident}.pdf'
            videne[k] = soubor
            cil = CIL / soubor
            zaznam['soubor'] = soubor
            if not (cil.exists() and log.get(p['url'], {}).get('stav') == 'stazeno'):
                vysl = stahni(zaznam['stazeni'], cil)
                zaznam.update(vysl)
                time.sleep(1.0)  # ohleduplně k serveru
            else:
                zaznam.update({x: log[p['url']][x] for x in ('stav', 'velikost', 'typ', 'konecna_url')})
            zaznam['sha256'] = hashlib.sha256(cil.read_bytes()).hexdigest()
        log[p['url']] = zaznam
        print(f"[{i}/{len(seznam)}] {zaznam['stav']:<20} {zaznam.get('nova_cesta') or zaznam.get('soubor')}  ← {k}",
              flush=True)
        LOG.write_text(json.dumps(log, ensure_ascii=False, indent=1), encoding='utf-8')


if __name__ == '__main__':
    main()
