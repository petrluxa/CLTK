"""Další přílohy ze stránek starého webu – originály, verze pro web a mapa podklady/data/prilohy-nove.json.

Předpoklad: nastroje/archiv-pdf/prilohy-stahnout.py (stažené soubory v podklady/_raw/prilohy/stazene/,
zápis v podklady/_raw/prilohy/stazeni.json) a prilohy-rozvrhy.py (rozvrhy → JSON).

Co dělá:
  1. originál pod čistým jménem do podklady/_raw/pdf-originaly/<cesta> (kopie ze stažených);
  2. „Rozlosování + kontakty“ (Non Profi Cup 2019, 2020): tabulka „Kontakty“ se soukromými
     e-maily a telefony hráčů se z verze pro web ODSTRANÍ (redakce PyMuPDF – text, čáry
     i výplň pryč, ne jen zakrytí), zůstane rozlosování do skupin; kontrola, že v souboru
     už žádný e-mail ani telefon není;
  3. verze pro web do web/uploads/<cesta>: stejné dvě zkoušky zmenšení jako zmensit.py
     (A = PyMuPDF rewrite_images, B = vlastní opatrné zmenšení), zmenšená jen při úspoře
     aspoň 25 % a beze změny vzhledu (porovnání stran jako porovnat.py), jinak originál;
  4. obálka speciální Revue 1893–2023 ze strany 1 (900 px na výšku, JPEG q85 + WebP);
  5. zápis podklady/data/prilohy-nove.json a podklady/_raw/prilohy/komprese.json.

Spuštění z kořene projektu:  python nastroje/archiv-pdf/prilohy-zpracovat.py
"""
import hashlib
import json
import re
import shutil
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from porovnat import obraz, PRAH  # noqa: E402
from zmensit import varianta_a, varianta_b, USPORA_MIN, ROZDIL_MAX  # noqa: E402

KOREN = Path(__file__).resolve().parents[2]
PRILOHY = KOREN / 'podklady/_raw/prilohy'
STAZENE = PRILOHY / 'stazene'
UPRAVENE = PRILOHY / 'upravene'
ORIG = KOREN / 'podklady/_raw/pdf-originaly'
UPLOADS = KOREN / 'web/uploads'
VYSTUP = KOREN / 'podklady/data/prilohy-nove.json'
KOMPRESE = PRILOHY / 'komprese.json'
T = 'dokumenty/turnaje/'

# id souboru na files.cltk.cz → co s ním
SOUBORY = {
    's26yxq6kkn501': dict(typ='revue-special', cesta='revue/pdf/revue-special-130-let-1893-2023.pdf',
                          nazev='I. ČLTK Revue 1893–2023 – speciální číslo ke 130. výročí klubu', rok=2023, turnaj=None),
    'h5h1rxl4mmn01': dict(typ='rozvrh', original='dokumenty/rozvrhy/rozvrh-ts-zima-2026-27.pdf', rozvrh='zima-2026-27',
                          nazev='Rozvrh tenisové školy – zima 2026/27', rok=2026, turnaj=None),
    'nnaiwso4zkn01': dict(typ='rozvrh', original='dokumenty/rozvrhy/rozvrh-ts-2026-09-29-az-10-02.pdf', rozvrh='tyden-2026-09-29',
                          nazev='Rozvrh tenisové školy – 29. 9. – 2. 10. 2026', rok=2026, turnaj=None),
    'mnbn96x3d312': dict(typ='turnaj', cesta=T + '2018-babolat-non-profi-cup-pozvanka.pdf',
                         nazev='Babolat Non Profi Cup 2018 – pozvánka a pravidla soutěže', rok=2018, turnaj='Babolat Non Profi Cup'),
    'ygo65at31512': dict(typ='turnaj', cesta=T + '2018-babolat-non-profi-cup-vysledky.pdf',
                         nazev='Babolat Non Profi Cup 2018 – výsledky skupin a play-off', rok=2018, turnaj='Babolat Non Profi Cup'),
    'd64eqlk3y912': dict(typ='turnaj', cesta=T + '2018-oslavy-125-let-klubu-pozvanka.pdf',
                         nazev='Oslavy 125 let klubu 10. 6. 2018 – pozvánka', rok=2018, turnaj='Oslavy 125 let klubu'),
    'rwbxzdq3n612': dict(typ='turnaj', cesta=T + '2018-golf-cup-pozvanka.pdf',
                         nazev='I. ČLTK Praha Golf Cup 2018 – pozvánka', rok=2018, turnaj='I. ČLTK Praha Golf Cup'),
    '8w11xiusct17': dict(typ='turnaj', cesta=T + '2019-babolat-non-profi-cup-pozvanka.pdf',
                         nazev='Babolat Non Profi Cup 2019 – pozvánka a pravidla soutěže', rok=2019, turnaj='Babolat Non Profi Cup'),
    'm4ljrmrszu17': dict(typ='turnaj', cesta=T + '2019-babolat-non-profi-cup-rozlosovani.pdf',
                         original=T + '2019-babolat-non-profi-cup-rozlosovani-kontakty.pdf', bez_kontaktu=True,
                         nazev='Babolat Non Profi Cup 2019 – rozlosování do skupin', rok=2019, turnaj='Babolat Non Profi Cup'),
    'kbxl211riu19': dict(typ='turnaj', cesta=T + '2019-klubovy-den-vysledky-debla.pdf',
                         nazev='Klubový den 2019 – výsledky deblového turnaje', rok=2019, turnaj='Klubový den'),
    '81gbp6am2625': dict(typ='turnaj', cesta=T + '2020-babolat-non-profi-cup-pozvanka.pdf',
                         nazev='Babolat Non Profi Cup 2020 – pozvánka a pravidla soutěže', rok=2020, turnaj='Babolat Non Profi Cup'),
    '3rsk44dlex25': dict(typ='turnaj', cesta=T + '2020-babolat-non-profi-cup-rozlosovani.pdf',
                         original=T + '2020-babolat-non-profi-cup-rozlosovani-kontakty.pdf', bez_kontaktu=True,
                         nazev='Babolat Non Profi Cup 2020 – rozlosování do skupin', rok=2020, turnaj='Babolat Non Profi Cup'),
    '8iwyllwkeq26': dict(typ='turnaj', cesta=T + '2020-babolat-non-profi-cup-tabulky-skupin.pdf',
                         nazev='Babolat Non Profi Cup 2020 – tabulky skupin s výsledky', rok=2020, turnaj='Babolat Non Profi Cup'),
    'x56gskqh8b31': dict(typ='turnaj', cesta=T + '2021-babolat-non-profi-cup-pozvanka.pdf',
                         nazev='Babolat Non Profi Cup 2021 – pozvánka a pravidla soutěže', rok=2021, turnaj='Babolat Non Profi Cup'),
    'u8gynonhuc31': dict(typ='turnaj', cesta=T + '2021-babolat-non-profi-cup-tabulka.pdf',
                         nazev='Babolat Non Profi Cup 2021 – tabulka každý s každým (před zahájením, bez výsledků)', rok=2021,
                         turnaj='Babolat Non Profi Cup'),
    'dw18zifi6a32': dict(typ='turnaj', cesta=T + '2021-babolat-non-profi-cup-vysledky.pdf',
                         nazev='Babolat Non Profi Cup 2021 – kompletní výsledky', rok=2021, turnaj='Babolat Non Profi Cup'),
    '3oicly4jch33': dict(typ='turnaj', cesta=T + '2022-babolat-non-profi-cup-pozvanka.pdf',
                         nazev='Babolat Non Profi Cup 2022 – pozvánka a pravidla soutěže', rok=2022, turnaj='Babolat Non Profi Cup'),
    'mcrbo37jzi33': dict(typ='turnaj', cesta=T + '2022-babolat-non-profi-cup-tabulka.pdf',
                         nazev='Babolat Non Profi Cup 2022 – tabulka každý s každým (před zahájením, bez výsledků)', rok=2022,
                         turnaj='Babolat Non Profi Cup'),
    '2j9nccjjo3201': dict(typ='turnaj', cesta=T + '2022-babolat-non-profi-cup-vysledky.pdf',
                          nazev='Babolat Non Profi Cup 2022 – kompletní výsledky', rok=2022, turnaj='Babolat Non Profi Cup'),
    'a1tdc6nka4301': dict(typ='turnaj', cesta=T + '2023-babolat-non-profi-cup-pozvanka.pdf',
                          nazev='Babolat Non Profi Cup 2023 – pozvánka a pravidla soutěže', rok=2023, turnaj='Babolat Non Profi Cup'),
    'smycd9okx5301': dict(typ='turnaj', cesta=T + '2023-babolat-non-profi-cup-tabulka.pdf',
                          nazev='Babolat Non Profi Cup 2023 – tabulka každý s každým (před zahájením, bez výsledků)', rok=2023,
                          turnaj='Babolat Non Profi Cup'),
    'g8mmsjukkt401': dict(typ='turnaj', cesta=T + '2023-babolat-non-profi-cup-vysledky.pdf',
                          nazev='Babolat Non Profi Cup 2023 – kompletní výsledky', rok=2023, turnaj='Babolat Non Profi Cup'),
    'f6gu5moh4h801': dict(typ='turnaj', cesta=T + '2024-babolat-amateur-tour-pozvanka.pdf',
                          nazev='Babolat Amateur Tour 2024 – pozvánka a pravidla soutěže', rok=2024, turnaj='Babolat Amateur Tour'),
    'jrmtyz6c4fd01': dict(typ='turnaj', cesta=T + '2024-babolat-amateur-tour-vysledky.pdf',
                          nazev='Babolat Amateur Tour 2024 – kompletní výsledky', rok=2024, turnaj='Babolat Amateur Tour'),
    'i44oyxu4onm01': dict(typ='turnaj', cesta=T + '2026-babolat-amateur-tour-pozvanka.pdf',
                          nazev='Babolat Amateur Tour 2026 – pozvánka a pravidla soutěže', rok=2026, turnaj='Babolat Amateur Tour'),
    'zqo64ty42mm01': dict(typ='turnaj', cesta=T + '2026-babolat-amateur-tour-tabulka.pdf',
                          nazev='Babolat Amateur Tour 2026 – tabulka každý s každým (bez výsledků)', rok=2026,
                          turnaj='Babolat Amateur Tour'),
}

# už převedené dokumenty (stejný soubor jako v mapa-souboru.json) → slug v dokumenty.json
DOKUMENT_PODLE_CESTY = {
    'dokumenty/pravidla-hrani-2026.pdf': 'pravidla-hrani-2026',
    'dokumenty/provozni-rad-posilovna.pdf': 'provozni-rad-posilovna',
    'dokumenty/provozni-rad-bazen.pdf': 'provozni-rad-bazen',
    'dokumenty/provozni-rad-wellness.pdf': 'provozni-rad-wellness',
}

EMAIL = re.compile(rb'[\w.+-]+@[\w-]+\.[a-z]{2,}', re.I)
TELEFON = re.compile(r'\b\d{3} ?\d{3} ?\d{3}\b')


def sha(p: Path) -> str:
    return hashlib.sha256(p.read_bytes()).hexdigest()


def porovnej_soubory(a: Path, b: Path) -> dict:
    """Totéž co porovnat.porovnej, ale nad dvěma zadanými soubory."""
    import pymupdf
    from PIL import ImageChops
    nejhorsi = (0.0, 0)
    with pymupdf.open(a) as da, pymupdf.open(b) as db:
        if da.page_count != db.page_count:
            return dict(chyba='jiný počet stran', max=1.0, strana=0)
        for i in range(da.page_count):
            x, y = obraz(da[i]), obraz(db[i])
            if x.size != y.size:
                y = y.resize(x.size)
            hist = ImageChops.difference(x, y).convert('L').histogram()
            podil = sum(hist[PRAH:]) / (x.width * x.height)
            if podil > nejhorsi[0]:
                nejhorsi = (podil, i + 1)
    return dict(max=round(nejhorsi[0], 5), strana=nejhorsi[1])


def bez_kontaktu(src: Path, cil: Path) -> dict:
    """Odstraní z rozlosování tabulku „Kontakty:“ (vše od nadpisu dolů) a ověří, že nic nezbylo."""
    import pymupdf
    with pymupdf.open(src) as d:
        if d.page_count != 1:
            raise RuntimeError(f'{src.name}: čekal jsem 1 stranu')
        pg = d[0]
        nadpis = pg.search_for('Kontakty:')
        if len(nadpis) != 1:
            raise RuntimeError(f'{src.name}: nadpis „Kontakty:“ nalezen {len(nadpis)}×')
        y = nadpis[0].y0 - 2
        # nad nadpisem nesmí být nic z kontaktů (e-maily / telefony)
        nad = pg.get_text('text', clip=pymupdf.Rect(0, 0, pg.rect.width, y))
        if '@' in nad or TELEFON.search(nad):
            raise RuntimeError(f'{src.name}: kontakty i nad nadpisem')
        oblast = pymupdf.Rect(0, y, pg.rect.width, pg.rect.height)
        pg.add_redact_annot(oblast, fill=(1, 1, 1))
        pg.apply_redactions(images=pymupdf.PDF_REDACT_IMAGE_REMOVE,
                            graphics=pymupdf.PDF_REDACT_LINE_ART_REMOVE_IF_TOUCHED,
                            text=pymupdf.PDF_REDACT_TEXT_REMOVE)
        d.set_metadata({})   # autor apod. z Excelu na web nepatří
        d.save(cil, garbage=4, deflate=True, clean=True)
    # kontrola: text i všechny dekomprimované proudy bez e-mailů a telefonů
    with pymupdf.open(cil) as d:
        text = ''.join(p.get_text() for p in d)
        proudy = b''
        for x in range(1, d.xref_length()):
            try:
                if d.xref_is_stream(x):
                    proudy += d.xref_stream(x) or b''
            except Exception:  # noqa: BLE001
                pass
        zbylo_mail = EMAIL.findall(text.encode()) + EMAIL.findall(proudy)
        zbylo_tel = TELEFON.findall(text)
        if zbylo_mail or zbylo_tel or 'Kontakty' in text:
            raise RuntimeError(f'{cil.name}: po redakci zbylo {zbylo_mail[:3]} {zbylo_tel[:3]}')
        return dict(odstraneno_od_y=round(y, 1), zbyly_text=re.sub(r'\s+', ' ', text).strip()[:200])


def verze_pro_web(src: Path, cil: Path) -> dict:
    import pymupdf
    pymupdf.TOOLS.mupdf_display_errors(False)
    pymupdf.TOOLS.mupdf_display_warnings(False)
    cil.parent.mkdir(parents=True, exist_ok=True)
    puvodni = src.stat().st_size
    with pymupdf.open(src) as d:
        stran = d.page_count
    zkousky = {}
    for jmeno, fn in (('A', varianta_a), ('B', varianta_b)):
        tmp = cil.with_name(f'{cil.stem}.zkouska-{jmeno}.pdf')
        z = {}
        try:
            v = fn(src, tmp)
            z['velikost'] = tmp.stat().st_size
            if jmeno == 'B':
                z['obrazku'] = v
            if z['velikost'] <= puvodni * (1 - USPORA_MIN):
                p = porovnej_soubory(src, tmp)
                z.update(rozdil=p['max'], rozdil_strana=p['strana'])
                z['ok'] = p.get('chyba') is None and p['max'] <= ROZDIL_MAX
            else:
                z.update(ok=False, malo=True)
        except Exception as e:  # noqa: BLE001
            z.update(chyba=f'{type(e).__name__}: {e}', ok=False)
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
    with pymupdf.open(cil) as d:
        if d.page_count != stran:
            raise RuntimeError(f'{cil}: počet stran {d.page_count} ≠ {stran}')
        for i in range(d.page_count):
            d[i].get_text('text')
    return dict(puvodni=puvodni, velikost_nova=cil.stat().st_size, stran=stran,
                pouzito=f'zmensene-{vybrana}' if vybrana else 'original', zkousky=zkousky)


def obalka(pdf: Path, jpg: Path, vyska: int = 900) -> dict:
    import pymupdf
    from PIL import Image
    with pymupdf.open(pdf) as d:
        pg = d[0]
        zoom = 2 * vyska / pg.rect.height       # dvojnásobek a pak zmenšení (ostřejší)
        pm = pg.get_pixmap(matrix=pymupdf.Matrix(zoom, zoom), alpha=False, colorspace=pymupdf.csRGB)
        img = Image.frombytes('RGB', (pm.width, pm.height), pm.samples)
    sirka = round(img.width * vyska / img.height)
    img = img.resize((sirka, vyska), Image.LANCZOS)
    jpg.parent.mkdir(parents=True, exist_ok=True)
    img.save(jpg, 'JPEG', quality=85, optimize=True, progressive=True)
    webp = jpg.with_suffix('.webp')
    img.save(webp, 'WEBP', quality=82, method=6)
    return dict(obalka=jpg.relative_to(UPLOADS).as_posix(), webp=webp.relative_to(UPLOADS).as_posix(),
                rozmer=f'{sirka} × {vyska}', velikost_jpg=jpg.stat().st_size, velikost_webp=webp.stat().st_size)


def main() -> None:
    import pymupdf
    pymupdf.TOOLS.mupdf_display_warnings(False)
    log = json.loads((PRILOHY / 'stazeni.json').read_text(encoding='utf-8'))
    komprese = {}
    vystup = []
    dokumenty = {x['slug']: x for x in json.loads((KOREN / 'podklady/dokumenty-texty/dokumenty.json')
                                                  .read_text(encoding='utf-8'))}
    rozvrhy = {r['slug']: r for r in json.loads((KOREN / 'podklady/dokumenty-texty/rozvrhy.json')
                                                .read_text(encoding='utf-8'))['rozvrhy']}
    UPRAVENE.mkdir(parents=True, exist_ok=True)
    for url, z in log.items():
        zaznam = dict(stara_url=url, stara_url_ke_stazeni=z['stazeni'], odkaz_text=z['text'],
                      stara_stranka=z['stranky'][0] if len(z['stranky']) == 1 else z['stranky'])
        if z['stav'] == 'uz-mame':
            slug = DOKUMENT_PODLE_CESTY[z['nova_cesta']]
            dok = dokumenty[slug]
            zaznam.update(nova_cesta=None, typ='text', nazev=dok['nazev'], rok=None, turnaj=None, stran=dok['stran'],
                          velikost=None,
                          duplicita=dict(stejny_soubor_jako=z['nova_cesta'], dokument_slug=slug,
                                         html=f'podklady/dokumenty-texty/{slug}.html'),
                          poznamka='Stejný soubor jako už převedený dokument (adresa jen jinak zapsaná: HTML entity, '
                                   'http místo https nebo jiné %-kódování) – nic nového, odkaz vede na textovou verzi.')
            vystup.append(zaznam)
            print(f"duplicita  {z['nova_cesta']}  ← {z['klic']}")
            continue
        ident = z['soubor'][:-4]
        s = SOUBORY[ident]
        stazeny = STAZENE / z['soubor']
        orig_cesta = s.get('original') or s['cesta']
        orig = ORIG / orig_cesta
        orig.parent.mkdir(parents=True, exist_ok=True)
        if not orig.exists() or sha(orig) != z['sha256']:
            shutil.copyfile(stazeny, orig)
        if sha(orig) != z['sha256']:
            raise RuntimeError(f'{orig}: kontrolní součet nesedí')
        with pymupdf.open(orig) as d:
            stran = d.page_count
        zaznam.update(typ=s['typ'], nazev=s['nazev'], rok=s['rok'], turnaj=s['turnaj'], stran=stran,
                      original=f'podklady/_raw/pdf-originaly/{orig_cesta}', velikost_originalu=orig.stat().st_size,
                      sha256_originalu=z['sha256'])
        if s['typ'] == 'rozvrh':
            r = rozvrhy[s['rozvrh']]
            zaznam.update(nova_cesta=None, velikost=None, rozvrh_slug=s['rozvrh'],
                          data='podklady/dokumenty-texty/rozvrhy.json',
                          platnost_od=r['platnost_od'], platnost_do=r['platnost_do'],
                          poznamka='Převedeno na data (rozvrhy.json). V buňkách PDF jsou jen jména dětí (příjmení, '
                                   'někdy se zkratkou jména) – ta se na web nepřebírají; skupina, věk ani trenér skupiny '
                                   'v PDF nejsou. PDF zůstává jen v podklady/_raw (mimo git, na server ne).')
            vystup.append(zaznam)
            print(f"rozvrh     {s['rozvrh']}  ({stran} s.)")
            continue
        zdroj = orig
        if s.get('bez_kontaktu'):
            zdroj = UPRAVENE / Path(s['cesta']).name
            info = bez_kontaktu(orig, zdroj)
            zaznam['upraveno'] = dict(
                co='Odstraněna tabulka „Kontakty:“ (jméno, soukromý e-mail a telefon každého hráče); '
                   'na webu zůstává jen rozlosování do skupin A a B. Originál s kontakty je jen v podklady/_raw.',
                odstraneno_od_y_pt=info['odstraneno_od_y'], soubor=f'podklady/_raw/prilohy/upravene/{zdroj.name}')
        cil = UPLOADS / s['cesta']
        v = verze_pro_web(zdroj, cil)
        komprese[s['cesta']] = dict(zdroj=str(zdroj.relative_to(KOREN).as_posix()), **v)
        zaznam.update(nova_cesta=s['cesta'], velikost=v['velikost_nova'], komprese=v['pouzito'])
        if s['typ'] == 'revue-special':
            zaznam['obalka'] = obalka(cil, UPLOADS / 'revue/revue-special-130-let.jpg')
            zaznam['navrh_zaznamu_revue'] = dict(
                rok=2023, oznaceni='1893–2023', nazev='Speciální číslo ke 130. výročí klubu',
                obalka='revue/revue-special-130-let.jpg',
                obalka_popis='Markéta Vondroušová se stříbrnou medailí z OH Tokio 2021 (foto Martin Sidorják)',
                titulky=['1893–2023', 'MARKÉTA VONDROUŠOVÁ – STŘÍBRNÁ MEDAILE, OH TOKIO 2021'],
                obsah=[['03', 'Editorial'], ['04', 'Moderna s geniem loci'],
                       ['08', 'Neměnila bych (Markéta Vondroušová)'],
                       ['11', 'Oáza na ostrově – kapitoly ze slavných dějin I. ČLTK Praha'],
                       ['15', 'Prezidenti I. ČLTK Praha od roku 1893'],
                       ['23', 'Legenda o legendě s geny útočníka (Jan Kodeš o Jaroslavu Drobném)'],
                       ['26', 'Zázemí nové dimenze'], ['29', 'Klíčem bylo rozdělení'],
                       ['30', 'Ti, kteří šířili slávu'], ['36', 'Legendy na Štvanici'],
                       ['40', '„Čemp“ patří na Štvanici (Jan Kodeš)']],
                obsah_poznamka='Číslo nemá stránku s obsahem – seznam je sestavený z nadpisů článků (strana = začátek článku).',
                stran=stran, naklad='300 výtisků', vyslo='duben 2023',
                tiraz='Bulletin I. ČLTK Praha ke 130. výročí klubu. Produkce: EagleMedia, s.r.o. Texty: Jiljí Kubec. '
                      'Foto: Martin Sidorják, Jiří Koliš, Kamil Rodinger, Pavel Lebeda, archív I. ČLTK. '
                      'Foto na titulu: Martin Sidorják. Grafická úprava: Kateřina Kuželová. Náklad 300 výtisků. '
                      'Tisk: Astron. Vyšlo v dubnu 2023.',
                pdf_soubor=s['cesta'], pdf_mb=f"{v['velikost_nova'] / 1e6:.1f}".replace('.', ','),
                odkaz_z='historie.php (a kiosek Revue na revue.php)')
        vystup.append(zaznam)
        print(f"{s['typ']:<13} {s['cesta']}  {v['puvodni'] / 1e3:.0f} → {v['velikost_nova'] / 1e3:.0f} kB "
              f"[{v['pouzito']}] {stran} s.")
    VYSTUP.write_text(json.dumps(vystup, ensure_ascii=False, indent=1) + '\n', encoding='utf-8')
    KOMPRESE.write_text(json.dumps(komprese, ensure_ascii=False, indent=1) + '\n', encoding='utf-8')
    print(f'Zapsáno {len(vystup)} záznamů → {VYSTUP.relative_to(KOREN)}')


if __name__ == '__main__':
    main()
