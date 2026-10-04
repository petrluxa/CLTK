"""Archiv PDF ze starého webu (files.cltk.cz) – krok 1: plán.

Projde lokální DB (jen čtení) a ke každému odkazu na soubor na files.cltk.cz
určí nový čistý název v web/uploads/. Výsledek: podklady/_raw/pdf-originaly/plan.json

Spuštění z kořene projektu:  python nastroje/archiv-pdf/plan.py
"""
import json
import os
import re
import sqlite3
import sys
import unicodedata
from pathlib import Path

KOREN = Path(__file__).resolve().parents[2]
DB = Path(os.environ.get('CLTK_DB_FILE') or KOREN / 'web/data/cltk.sqlite')
VYSTUP = KOREN / 'podklady/_raw/pdf-originaly/plan.json'

URL_RE = re.compile(r"https?://files\.cltk\.cz/[^\s\"'<>)\\]+")

# Názvy dokumentů (cltk_dokumenty.id → slug); ostatní tabulky odkazují na tytéž soubory.
DOKUMENTY_SLUG = {
    1: 'stanovy-klubu',
    2: 'pravidla-hrani-2026',
    4: 'cenik-zimni-sezona-2026-27',
    5: 'plan-arealu-09-2025',
    6: 'provozni-rad-bazen',
    7: 'provozni-rad-posilovna',
    8: 'provozni-rad-wellness',
}


def slug(text: str) -> str:
    s = unicodedata.normalize('NFKD', text)
    s = ''.join(ch for ch in s if not unicodedata.combining(ch)).lower()
    s = re.sub(r'[^a-z0-9]+', '-', s).strip('-')
    return s


def ciste_url(url: str) -> str:
    """Adresa ke stažení – bez „?download“."""
    return re.sub(r'\?download$', '', url)


def main() -> None:
    con = sqlite3.connect(f'file:{DB.as_posix()}?mode=ro', uri=True)
    con.row_factory = sqlite3.Row

    soubory: dict[str, str] = {}   # čistá URL → nova_cesta
    odkazy: list[dict] = []          # výskyty v DB

    def pridej_soubor(url: str, cesta: str) -> None:
        u = ciste_url(url)
        if u in soubory and soubory[u] != cesta:
            sys.exit(f'Konflikt: {u} → {soubory[u]} i {cesta}')
        if cesta in soubory.values() and soubory.get(u) != cesta:
            sys.exit(f'Dvě různé adresy na stejnou cestu {cesta}')
        soubory[u] = cesta

    # Revue
    for r in con.execute("SELECT id, rok, cislo, pdf_url FROM cltk_revue WHERE pdf_url <> ''"):
        cesta = f"revue/pdf/revue-{r['rok']}-{int(r['cislo'])}.pdf"
        pridej_soubor(r['pdf_url'], cesta)
        odkazy.append(dict(stara_url=r['pdf_url'], tabulka='cltk_revue', sloupec='pdf_url', id=r['id']))

    # Newslettery
    for r in con.execute("SELECT id, rok, cislo, oznaceni, pdf_cs, pdf_en FROM cltk_newslettery"):
        cislo = (r['cislo'] or '').strip()
        if cislo.isdigit():
            zaklad = f"newsletter-{r['rok']}-{int(cislo)}"
        else:
            # bez čísla (2023): podle označení bez roku a závorky, např. „říjen 2023“ → rijen
            ozn = re.sub(r'\(.*?\)', '', r['oznaceni'] or '')
            ozn = slug(ozn.replace(str(r['rok']), ''))
            zaklad = f"newsletter-{r['rok']}-{ozn or 'id' + str(r['id'])}"
        for sloupec, pripona in (('pdf_cs', ''), ('pdf_en', '-en')):
            url = r[sloupec] or ''
            if not url:
                continue
            cesta = f"newslettery/{zaklad}{pripona}.pdf"
            pridej_soubor(url, cesta)
            odkazy.append(dict(stara_url=url, tabulka='cltk_newslettery', sloupec=sloupec, id=r['id']))

    # Dokumenty
    for r in con.execute("SELECT id, nazev, url FROM cltk_dokumenty"):
        url = r['url'] or ''
        if 'files.cltk.cz' not in url:
            continue
        if r['id'] not in DOKUMENTY_SLUG:
            sys.exit(f'Dokument {r["id"]} ({r["nazev"]}) nemá určený název')
        pridej_soubor(url, f"dokumenty/{DOKUMENTY_SLUG[r['id']]}.pdf")
        odkazy.append(dict(stara_url=url, tabulka='cltk_dokumenty', sloupec='url', id=r['id']))

    # Ostatní sloupce odkazují na už známé soubory (ceník, plán areálu, Revue v pramenech kroniky)
    for tabulka, sloupec in (('cltk_price_lists', 'pdf_url'), ('cltk_bloky', 'odkaz'),
                             ('cltk_bloky', 'odkaz2'), ('cltk_milniky', 'zdroj'), ('cltk_ctc', 'zdroj'),
                             ('cltk_vysledky', 'odkaz')):
        for r in con.execute(f'SELECT id, "{sloupec}" AS v FROM {tabulka} WHERE "{sloupec}" LIKE ?',
                             ('%files.cltk.cz%',)):
            for url in dict.fromkeys(URL_RE.findall(r['v'])):
                if ciste_url(url) not in soubory:
                    sys.exit(f'{tabulka}.{sloupec} #{r["id"]}: neznámý soubor {url}')
                odkazy.append(dict(stara_url=url, tabulka=tabulka, sloupec=sloupec, id=r['id']))

    # Kontrola: žádný jiný výskyt files.cltk.cz v DB
    znamo = {(o['tabulka'], o['sloupec'], o['id'], o['stara_url']) for o in odkazy}
    for (t,) in con.execute("SELECT name FROM sqlite_master WHERE type='table'"):
        for c in con.execute(f'PRAGMA table_info("{t}")'):
            col = c[1]
            try:
                rows = con.execute(f'SELECT id, "{col}" FROM "{t}" WHERE "{col}" LIKE ?', ('%files.cltk.cz%',)).fetchall()
            except sqlite3.Error:
                continue
            for rid, v in rows:
                for url in URL_RE.findall(str(v)):
                    if (t, col, rid, url) not in znamo:
                        sys.exit(f'Nezahrnutý výskyt: {t}.{col} #{rid} {url}')

    for o in odkazy:
        o['nova_cesta'] = soubory[ciste_url(o['stara_url'])]

    VYSTUP.parent.mkdir(parents=True, exist_ok=True)
    VYSTUP.write_text(json.dumps({
        'soubory': [{'url': u, 'nova_cesta': c} for u, c in soubory.items()],
        'odkazy': odkazy,
    }, ensure_ascii=False, indent=1), encoding='utf-8')
    print(f'Souborů ke stažení: {len(soubory)}, výskytů v DB: {len(odkazy)} → {VYSTUP}')


if __name__ == '__main__':
    main()
