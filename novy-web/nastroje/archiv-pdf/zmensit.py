"""Archiv PDF ze starého webu – krok 3: verze pro web + mapa souborů.

Z originálu v podklady/_raw/pdf-originaly/<cesta> udělá web/uploads/<cesta>.
Zkouší dvě cesty a bere menší z těch, které projdou kontrolou vzhledu (porovnat.py):

  A) vestavěný přepis obrázků PyMuPDF/MuPDF (nad 170 dpi na 150 dpi, JPEG 72);
     u některých souborů obrázky rozbije (logo s /Decode, vločky s měkkou maskou,
     obálka Revue 1/2012 rozsypaná do pruhů) – proto vždy jen s kontrolou;
  B) vlastní opatrné zmenšení: jen obrázky 8 bit v šedi/RGB/CMYK bez masek a zvláštností,
     se známým umístěním na stránce a efektivním rozlišením nad 170 dpi → 150 dpi;
     barevný prostor (ICC), měkká maska i /Decode u CMYK zůstávají.

Zmenšenou verzi použije jen tehdy, když ušetří aspoň 25 % a žádná strana se viditelně
neliší od originálu; jinak zkopíruje originál. Každý výstup ověří (otevře se,
stejný počet stran, každá strana jde načíst) a nakonec zapíše podklady/data/mapa-souboru.json.

Spuštění z kořene projektu:  python nastroje/archiv-pdf/zmensit.py [--jen cesta …]
"""
import io
import json
import math
import shutil
import sys
import time
import zlib
from concurrent.futures import ProcessPoolExecutor
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from porovnat import porovnej  # noqa: E402

KOREN = Path(__file__).resolve().parents[2]
ORIG = KOREN / 'podklady/_raw/pdf-originaly'
UPLOADS = KOREN / 'web/uploads'
VYSL = ORIG / 'komprese.json'
MAPA = KOREN / 'podklady/data/mapa-souboru.json'
USPORA_MIN = 0.25
ROZDIL_MAX = 0.0002   # nejvýš 0,02 % plochy strany smí vypadat jinak (viz porovnat.py)
DPI_PRAH, DPI_CIL, KVALITA = 170, 150, 72

# Soubory, u kterých se po vizuální kontrole nechává originál (cesta → důvod).
NECHAT_ORIGINAL: dict[str, str] = {}


def varianta_a(src: Path, cil: Path) -> None:
    import pymupdf
    with pymupdf.open(src) as d:
        d.rewrite_images(dpi_threshold=DPI_PRAH, dpi_target=DPI_CIL, quality=KVALITA, lossy=True,
                         lossless=True, bitonal=True, color=True, gray=True)
        d.save(cil, garbage=4, deflate=True, clean=True)


def _n_barev(doc, xref: int) -> int:
    """Počet složek barevného prostoru obrázku; 0 = prostor, na který nesaháme."""
    typ, cs = doc.xref_get_key(xref, 'ColorSpace')
    if typ == 'name':
        return {'/DeviceGray': 1, '/DeviceRGB': 3, '/DeviceCMYK': 4}.get(cs, 0)
    if typ == 'xref':
        obj = doc.xref_object(int(cs.split()[0]), compressed=True).strip()
    elif typ == 'array':
        obj = cs.strip()
    else:
        return 0
    if obj.startswith('[/ICCBased') or obj.startswith('[ /ICCBased'):
        icc = int(obj.replace('[', ' ').split()[1])
        t, n = doc.xref_get_key(icc, 'N')
        return int(n) if t == 'int' and n in ('1', '3', '4') else 0
    return {'/DeviceGray': 1, '/DeviceRGB': 3, '/DeviceCMYK': 4}.get(obj, 0)


def varianta_b(src: Path, cil: Path) -> int:
    """Vlastní opatrné zmenšení obrázků. Vrátí počet přepsaných obrázků."""
    import pymupdf
    from PIL import Image
    pocet = 0
    with pymupdf.open(src) as doc:
        # obrázky, které slouží jako maska jiného obrázku, nechat být
        masky = set()
        obrazky = []
        for x in range(1, doc.xref_length()):
            try:
                if doc.xref_get_key(x, 'Subtype') != ('name', '/Image'):
                    continue
            except Exception:  # noqa: BLE001
                continue
            obrazky.append(x)
            for k in ('SMask', 'Mask'):
                t, v = doc.xref_get_key(x, k)
                if t == 'xref':
                    masky.add(int(v.split()[0]))
        # největší zobrazená velikost (v bodech) každého obrázku
        velikost: dict[int, tuple[float, float]] = {}
        for page in doc:
            for info in page.get_image_info(xrefs=True):
                x = info.get('xref') or 0
                if not x:
                    continue
                a, b, c, d = info['transform'][:4]
                w, h = math.hypot(a, b), math.hypot(c, d)
                ow, oh = velikost.get(x, (0.0, 0.0))
                velikost[x] = (max(ow, w), max(oh, h))
        for x in obrazky:
            if x in masky or x not in velikost:
                continue
            obj = doc.xref_object(x, compressed=True)
            if '/ImageMask true' in obj or '/SMaskInData' in obj or '/Mask' in obj.replace('/SMask', ''):
                continue
            if doc.xref_get_key(x, 'BitsPerComponent') != ('int', '8'):
                continue
            filtr = doc.xref_get_key(x, 'Filter')
            if filtr not in (('name', '/DCTDecode'), ('name', '/FlateDecode')):
                continue
            n = _n_barev(doc, x)
            if not n:
                continue
            dec = doc.xref_get_key(x, 'Decode')
            cmyk_inverze = dec[0] == 'array' and dec[1].split() == ['[1', '0', '1', '0', '1', '0', '1', '0]']
            if dec[0] != 'null' and not (n == 4 and cmyk_inverze):
                continue
            w = int(doc.xref_get_key(x, 'Width')[1])
            h = int(doc.xref_get_key(x, 'Height')[1])
            dw, dh = velikost[x]
            if dw < 1 or dh < 1:
                continue
            dpi = min(w / (dw / 72), h / (dh / 72))
            puvodni_delka = len(doc.xref_stream_raw(x))
            # JPEG větší než nekomprimovaná data = nafouknutý metadaty (logo 173 × 198 px má 1,8 MB):
            # překódovat ve stejném rozlišení a ve vyšší kvalitě
            nafouknuty = filtr[1] == '/DCTDecode' and puvodni_delka > w * h * n
            if dpi <= DPI_PRAH and not nafouknuty:
                continue
            if dpi > DPI_PRAH:
                nw, nh, kvalita = max(1, round(w * DPI_CIL / dpi)), max(1, round(h * DPI_CIL / dpi)), KVALITA
            else:
                nw, nh, kvalita = w, h, 88
            try:
                pix = pymupdf.Pixmap(doc, x)
            except Exception:  # noqa: BLE001 – obrázek, který MuPDF nenačte, nechat být
                continue
            if pix.alpha:
                pix = pymupdf.Pixmap(pix, 0)
            if pix.n != n or pix.width != w or pix.height != h:
                continue
            rezim = {1: 'L', 3: 'RGB', 4: 'CMYK'}[n]
            img = Image.frombytes(rezim, (w, h), pix.samples)
            if (nw, nh) != (w, h):
                img = img.resize((nw, nh), Image.LANCZOS, reducing_gap=3.0)
            buf = io.BytesIO()
            img.save(buf, 'JPEG', quality=kvalita, optimize=True)
            jpeg = buf.getvalue()
            surova = img.tobytes()
            flate = zlib.compress(surova, 9) if filtr[1] == '/FlateDecode' else None
            # bezeztrátový originál (grafika, loga) zůstane bezeztrátový, když to není o moc větší
            if flate is not None and len(flate) <= 2.5 * len(jpeg):
                if len(flate) >= puvodni_delka:
                    continue
                doc.update_stream(x, surova, compress=True)
                doc.xref_set_key(x, 'Decode', 'null')
            else:
                if len(jpeg) >= puvodni_delka:
                    continue
                doc.update_stream(x, jpeg, compress=False)
                doc.xref_set_key(x, 'Filter', '/DCTDecode')
                # Pillow ukládá CMYK JPEG invertovaně (Adobe) – stejně jako Photoshop, proto /Decode
                doc.xref_set_key(x, 'Decode', '[1 0 1 0 1 0 1 0]' if n == 4 else 'null')
            doc.xref_set_key(x, 'DecodeParms', 'null')
            doc.xref_set_key(x, 'Width', str(nw))
            doc.xref_set_key(x, 'Height', str(nh))
            doc.xref_set_key(x, 'BitsPerComponent', '8')
            pocet += 1
        doc.save(cil, garbage=4, deflate=True, clean=True)
    return pocet


def zpracuj(cesta: str) -> dict:
    import pymupdf
    pymupdf.TOOLS.mupdf_display_errors(False)
    pymupdf.TOOLS.mupdf_display_warnings(False)
    src = ORIG / cesta
    cil = UPLOADS / cesta
    cil.parent.mkdir(parents=True, exist_ok=True)
    t0 = time.time()
    puvodni = src.stat().st_size
    with pymupdf.open(src) as d:
        stran = d.page_count
    zkousky = {}
    if cesta not in NECHAT_ORIGINAL:
        for jmeno, fn in (('A', varianta_a), ('B', varianta_b)):
            tmp = cil.with_name(f'{cil.stem}.zkouska-{jmeno}.pdf')
            z = {}
            try:
                vysl = fn(src, tmp)
                z['velikost'] = tmp.stat().st_size
                if jmeno == 'B':
                    z['obrazku'] = vysl
                if z['velikost'] <= puvodni * (1 - USPORA_MIN):
                    p = porovnej(cesta, tmp)
                    z['rozdil'] = p['max']
                    z['rozdil_strana'] = p['strana']
                    z['ok'] = p.get('chyba') is None and p['max'] <= ROZDIL_MAX
                else:
                    z['ok'] = False
                    z['malo'] = True
            except Exception as e:  # noqa: BLE001
                z['chyba'] = f'{type(e).__name__}: {e}'
                z['ok'] = False
            z['soubor'] = tmp
            zkousky[jmeno] = z
    dobre = [j for j, z in zkousky.items() if z['ok']]
    vybrana = min(dobre, key=lambda j: zkousky[j]['velikost']) if dobre else None
    if vybrana:
        zkousky[vybrana]['soubor'].replace(cil)
    else:
        shutil.copyfile(src, cil)
    for z in zkousky.values():
        if z['soubor'].exists():
            z['soubor'].unlink()
        z.pop('soubor')
    # ověření výstupu
    with pymupdf.open(cil) as d:
        stran_nove = d.page_count
        for i in range(stran_nove):  # každá stránka se musí dát načíst
            d[i].get_text('text')
    if stran_nove != stran:
        raise RuntimeError(f'{cesta}: počet stran {stran_nove} ≠ {stran}')
    return dict(cesta=cesta, puvodni=puvodni, velikost_nova=cil.stat().st_size, stran=stran,
                pouzito=f'zmensene-{vybrana}' if vybrana else 'original',
                duvod=NECHAT_ORIGINAL.get(cesta, ''), zkousky=zkousky, sekund=round(time.time() - t0, 1))


def zapis_mapu(vysledky: dict) -> None:
    plan = json.loads((ORIG / 'plan.json').read_text(encoding='utf-8'))
    mapa = []
    for o in plan['odkazy']:
        v = vysledky[o['nova_cesta']]
        mapa.append(dict(stara_url=o['stara_url'], nova_cesta=o['nova_cesta'], tabulka=o['tabulka'],
                         sloupec=o['sloupec'], id=o['id'], velikost_puvodni=v['puvodni'],
                         velikost_nova=v['velikost_nova'], stran=v['stran']))
    MAPA.parent.mkdir(parents=True, exist_ok=True)
    MAPA.write_text(json.dumps(mapa, ensure_ascii=False, indent=1) + '\n', encoding='utf-8')
    print(f'Mapa: {len(mapa)} záznamů → {MAPA}')


def popis(z: dict) -> str:
    if 'chyba' in z:
        return 'chyba'
    s = f"{z['velikost'] / 1e6:.1f} MB"
    if 'rozdil' in z:
        s += f" rozdíl {z['rozdil'] * 100:.3f} % (s. {z['rozdil_strana']})"
    return s + (' OK' if z['ok'] else '')


def main() -> None:
    plan = json.loads((ORIG / 'plan.json').read_text(encoding='utf-8'))
    cesty = [s['nova_cesta'] for s in plan['soubory']]
    if '--jen' in sys.argv:
        cesty = sys.argv[sys.argv.index('--jen') + 1:]
    vysledky = json.loads(VYSL.read_text(encoding='utf-8')) if VYSL.exists() else {}
    # velké soubory napřed, ať pool nečeká na konec
    cesty.sort(key=lambda c: -(ORIG / c).stat().st_size)
    with ProcessPoolExecutor(max_workers=6) as ex:
        for v in ex.map(zpracuj, cesty):
            vysledky[v['cesta']] = v
            zk = ', '.join(f'{j}: {popis(z)}' for j, z in v['zkousky'].items())
            print(f"{v['cesta']}: {v['puvodni'] / 1e6:.1f} → {v['velikost_nova'] / 1e6:.1f} MB "
                  f"[{v['pouzito']}] ({zk}) {v['sekund']} s", flush=True)
            VYSL.write_text(json.dumps(vysledky, ensure_ascii=False, indent=1), encoding='utf-8')
    vsechny = [s['nova_cesta'] for s in plan['soubory']]
    if all(c in vysledky for c in vsechny):
        zapis_mapu(vysledky)
        p = sum(vysledky[c]['puvodni'] for c in vsechny)
        n = sum(vysledky[c]['velikost_nova'] for c in vsechny)
        zm = sum(1 for c in vsechny if vysledky[c]['pouzito'] != 'original')
        print(f'Celkem {len(vsechny)} souborů: {p / 1e6:.0f} MB → {n / 1e6:.0f} MB, zmenšeno {zm}')


if __name__ == '__main__':
    main()
