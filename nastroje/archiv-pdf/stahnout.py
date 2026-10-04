"""Archiv PDF ze starého webu – krok 2: stažení originálů.

Stahuje postupně (jeden soubor po druhém, s pauzou) soubory z plan.json do
podklady/_raw/pdf-originaly/<nova_cesta>. Už stažený soubor se správnou velikostí přeskočí.

Spuštění z kořene projektu:  python nastroje/archiv-pdf/stahnout.py
"""
import json
import time
import urllib.request
from pathlib import Path

KOREN = Path(__file__).resolve().parents[2]
ORIG = KOREN / 'podklady/_raw/pdf-originaly'
PLAN = ORIG / 'plan.json'
LOG = ORIG / 'stazeni.json'
UA = 'Mozilla/5.0 (archivace PDF pro novy web I. CLTK Praha; jednorazove stazeni)'


def stahni(url: str, cil: Path) -> dict:
    req = urllib.request.Request(url, headers={'User-Agent': UA})
    with urllib.request.urlopen(req, timeout=120) as r:
        delka = int(r.headers.get('Content-Length') or 0)
        typ = r.headers.get('Content-Type', '')
        konecna = r.geturl()
        if cil.exists() and delka and cil.stat().st_size == delka:
            return dict(stav='uz-stazeno', velikost=delka, typ=typ, konecna_url=konecna)
        tmp = cil.with_suffix('.part')
        with open(tmp, 'wb') as f:
            while True:
                kus = r.read(1 << 20)
                if not kus:
                    break
                f.write(kus)
    velikost = tmp.stat().st_size
    if delka and velikost != delka:
        tmp.unlink()
        raise IOError(f'neúplné stažení {velikost} z {delka} B')
    with open(tmp, 'rb') as f:
        if f.read(5) != b'%PDF-':
            tmp.unlink()
            raise IOError(f'není PDF (Content-Type {typ})')
    tmp.replace(cil)
    return dict(stav='stazeno', velikost=velikost, typ=typ, konecna_url=konecna)


def main() -> None:
    plan = json.loads(PLAN.read_text(encoding='utf-8'))
    log = json.loads(LOG.read_text(encoding='utf-8')) if LOG.exists() else {}
    soubory = plan['soubory']
    for i, s in enumerate(soubory, 1):
        url, cesta = s['url'], s['nova_cesta']
        cil = ORIG / cesta
        cil.parent.mkdir(parents=True, exist_ok=True)
        if log.get(url, {}).get('stav') in ('stazeno', 'uz-stazeno') and cil.exists() \
                and cil.stat().st_size == log[url]['velikost']:
            print(f'[{i}/{len(soubory)}] už je {cesta}')
            continue
        for pokus in range(1, 4):
            try:
                t0 = time.time()
                vysl = stahni(url, cil)
                vysl['cesta'] = cesta
                log[url] = vysl
                print(f'[{i}/{len(soubory)}] {vysl["stav"]} {cesta} {vysl["velikost"] / 1e6:.1f} MB '
                      f'({time.time() - t0:.0f} s)', flush=True)
                break
            except Exception as e:  # noqa: BLE001 – zalogovat a zkusit znovu
                print(f'[{i}/{len(soubory)}] CHYBA {cesta} (pokus {pokus}): {e}', flush=True)
                log[url] = dict(stav='chyba', chyba=str(e), cesta=cesta)
                time.sleep(10 * pokus)
        LOG.write_text(json.dumps(log, ensure_ascii=False, indent=1), encoding='utf-8')
        time.sleep(1.0)  # ohleduplně k serveru
    chyby = [u for u, v in log.items() if v.get('stav') == 'chyba']
    print(f'Hotovo. Chyb: {len(chyby)}')
    for u in chyby:
        print('  ', u, log[u].get('chyba'))


if __name__ == '__main__':
    main()
