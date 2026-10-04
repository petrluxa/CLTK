"""Archiv PDF – kontrola rozlišení obrázků ve verzi pro web.

Ke každému umístění obrázku na stránce (strana + rámeček) porovná efektivní dpi
v originálu a ve verzi pro web. Chyba = obrázek je teď pod 145 dpi, přestože
originál měl víc (tj. zmenšilo se víc, než se mělo).

python nastroje/archiv-pdf/rozliseni.py        (všechny zmenšené z komprese.json)
"""
import json
import math
import sys
from concurrent.futures import ProcessPoolExecutor
from pathlib import Path

KOREN = Path(__file__).resolve().parents[2]
ORIG = KOREN / 'podklady/_raw/pdf-originaly'
UPLOADS = KOREN / 'web/uploads'


def umisteni(path: Path) -> dict:
    import pymupdf
    pymupdf.TOOLS.mupdf_display_errors(False)
    out = {}
    with pymupdf.open(path) as d:
        for p in d:
            for info in p.get_image_info():
                a, b, c, dd = info['transform'][:4]
                w, h = math.hypot(a, b), math.hypot(c, dd)
                if w < 1 or h < 1:
                    continue
                dpi = min(info['width'] / (w / 72), info['height'] / (h / 72))
                klic = (p.number, tuple(round(v) for v in info['bbox']))
                out.setdefault(klic, []).append(dpi)
    return out


def zkontroluj(cesta: str) -> tuple[str, int, list]:
    o, n = umisteni(ORIG / cesta), umisteni(UPLOADS / cesta)
    spatne = []
    for klic, dpis in o.items():
        nove = sorted(n.get(klic, []))
        for i, d0 in enumerate(sorted(dpis)):
            if i >= len(nove):
                spatne.append((klic, round(d0), 'chybí'))
                continue
            d1 = nove[i]
            if d1 < min(145, d0 * 0.97):
                spatne.append((klic, round(d0), round(d1)))
    return cesta, sum(len(v) for v in o.values()), spatne


def main() -> None:
    k = json.loads((ORIG / 'komprese.json').read_text(encoding='utf-8'))
    cesty = sys.argv[1:] or [c for c, v in k.items() if v['pouzito'] != 'original']
    celkem = 0
    with ProcessPoolExecutor(max_workers=6) as ex:
        for cesta, pocet, spatne in ex.map(zkontroluj, cesty):
            celkem += pocet
            if spatne:
                print(f'{cesta}: {len(spatne)} z {pocet} umístění pod limitem, např. {spatne[:3]}')
    print(f'Zkontrolováno {len(cesty)} souborů, {celkem} umístění obrázků.')


if __name__ == '__main__':
    main()
