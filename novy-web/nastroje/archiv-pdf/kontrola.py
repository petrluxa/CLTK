"""Archiv PDF – vizuální kontrola: originál vlevo, verze pro web vpravo.

Pro každou zadanou cestu vykreslí strany (výchozí 1 a 2) celé a navíc výřez
textu ve 2× větším rozlišení. PNG do podklady/_raw/pdf-originaly/kontrola/.

python nastroje/archiv-pdf/kontrola.py revue/pdf/revue-2006-1.pdf … [--strany 1,2] [--vyrez x0,y0,x1,y1]
(výřez v poměrných souřadnicích stránky 0–1)
"""
import sys
from pathlib import Path

import pymupdf
from PIL import Image, ImageDraw

KOREN = Path(__file__).resolve().parents[2]
ORIG = KOREN / 'podklady/_raw/pdf-originaly'
UPLOADS = KOREN / 'web/uploads'
OUT = ORIG / 'kontrola'

pymupdf.TOOLS.mupdf_display_errors(False)


def pix(doc, idx, dpi, clip=None):
    p = doc[idx]
    r = p.rect
    if clip:
        clip = pymupdf.Rect(r.x0 + r.width * clip[0], r.y0 + r.height * clip[1],
                            r.x0 + r.width * clip[2], r.y0 + r.height * clip[3])
    pm = p.get_pixmap(dpi=dpi, clip=clip, alpha=False, colorspace=pymupdf.csRGB)
    return Image.frombytes('RGB', (pm.width, pm.height), pm.samples)


def vedle(a: Image.Image, b: Image.Image, popis: str) -> Image.Image:
    h = max(a.height, b.height)
    img = Image.new('RGB', (a.width + b.width + 12, h + 24), 'white')
    img.paste(a, (0, 24))
    img.paste(b, (a.width + 12, 24))
    d = ImageDraw.Draw(img)
    d.text((4, 4), f'ORIGINAL  |  {popis}', fill='black')
    d.text((a.width + 16, 4), 'WEB', fill='black')
    return img


def main() -> None:
    args = sys.argv[1:]
    strany = [1, 2]
    vyrez = (0.08, 0.30, 0.55, 0.60)
    if '--strany' in args:
        i = args.index('--strany')
        strany = [int(x) for x in args[i + 1].split(',')]
        del args[i:i + 2]
    if '--vyrez' in args:
        i = args.index('--vyrez')
        vyrez = tuple(float(x) for x in args[i + 1].split(','))
        del args[i:i + 2]
    OUT.mkdir(parents=True, exist_ok=True)
    for cesta in args:
        with pymupdf.open(ORIG / cesta) as do, pymupdf.open(UPLOADS / cesta) as dn:
            for s in strany:
                if s > do.page_count:
                    continue
                jmeno = cesta.replace('/', '_').removesuffix('.pdf')
                cela = vedle(pix(do, s - 1, 80), pix(dn, s - 1, 80), f'{cesta} s. {s}')
                cela.save(OUT / f'{jmeno}-s{s}-cela.png')
                det = vedle(pix(do, s - 1, 150, vyrez), pix(dn, s - 1, 150, vyrez), f'{cesta} s. {s} vyrez 150 dpi')
                det.save(OUT / f'{jmeno}-s{s}-vyrez.png')
                print(OUT / f'{jmeno}-s{s}-cela.png')
                print(OUT / f'{jmeno}-s{s}-vyrez.png')


if __name__ == '__main__':
    main()
