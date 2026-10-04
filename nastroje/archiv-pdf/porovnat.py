"""Archiv PDF – automatické porovnání originálu a verze pro web, stránku po stránce.

Každou stranu obou souborů vykreslí v nízkém rozlišení, lehce rozmaže (ať se nepočítá
šum z přepočtu fotek) a spočítá podíl pixelů, které se liší o víc než PRAH úrovní jasu
nebo barvy. Rozbitá průhlednost (šedé čtverce místo vloček) nebo rozsypané logo
se tím spolehlivě pozná; běžná ztrátová komprese fotek ne.

python nastroje/archiv-pdf/porovnat.py [cesta …]     (bez cest = všechny zmenšené z komprese.json)
"""
import json
import sys
from concurrent.futures import ProcessPoolExecutor
from pathlib import Path

KOREN = Path(__file__).resolve().parents[2]
ORIG = KOREN / 'podklady/_raw/pdf-originaly'
UPLOADS = KOREN / 'web/uploads'
DPI = 50
PRAH = 48


def obraz(page):
    import pymupdf
    from PIL import Image, ImageFilter
    pm = page.get_pixmap(dpi=DPI, alpha=False, colorspace=pymupdf.csRGB)
    return Image.frombytes('RGB', (pm.width, pm.height), pm.samples).filter(ImageFilter.GaussianBlur(1.2))


def porovnej(cesta: str, cil: Path | None = None) -> dict:
    import pymupdf
    from PIL import ImageChops
    pymupdf.TOOLS.mupdf_display_errors(False)
    pymupdf.TOOLS.mupdf_display_warnings(False)
    cil = cil or UPLOADS / cesta
    nejhorsi = (0.0, 0)
    stran = []
    with pymupdf.open(ORIG / cesta) as do, pymupdf.open(cil) as dn:
        if do.page_count != dn.page_count:
            return dict(cesta=cesta, chyba='jiný počet stran', max=1.0, strana=0)
        for i in range(do.page_count):
            a, b = obraz(do[i]), obraz(dn[i])
            if a.size != b.size:
                b = b.resize(a.size)
            diff = ImageChops.difference(a, b).convert('L')
            hist = diff.histogram()
            podil = sum(hist[PRAH:]) / (a.width * a.height)
            stran.append(round(podil, 5))
            if podil > nejhorsi[0]:
                nejhorsi = (podil, i + 1)
    return dict(cesta=cesta, max=round(nejhorsi[0], 5), strana=nejhorsi[1], stran=stran)


def main() -> None:
    cesty = sys.argv[1:]
    if not cesty:
        k = json.loads((ORIG / 'komprese.json').read_text(encoding='utf-8'))
        cesty = [c for c, v in k.items() if v['pouzito'] == 'zmensene']
    with ProcessPoolExecutor(max_workers=6) as ex:
        for v in ex.map(porovnej, cesty):
            print(f"{v['max'] * 100:7.3f} %  s. {v['strana']:>3}  {v['cesta']}", flush=True)


if __name__ == '__main__':
    main()
