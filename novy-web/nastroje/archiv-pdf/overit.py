"""Archiv PDF – závěrečné ověření mapy a souborů.

- každý odkaz na files.cltk.cz z DB je v mapě právě jednou pro (tabulka, sloupec, id, stara_url);
- každý soubor z mapy existuje ve web/uploads, otevře se, má stejný počet stran jako originál
  a velikosti v mapě sedí;
- ve složkách nezůstaly pomocné soubory.

python nastroje/archiv-pdf/overit.py
"""
import json
import os
import re
import sqlite3
from collections import Counter
from pathlib import Path

import pymupdf

KOREN = Path(__file__).resolve().parents[2]
DB = Path(os.environ.get('CLTK_DB_FILE') or KOREN / 'web/data/cltk.sqlite')
ORIG = KOREN / 'podklady/_raw/pdf-originaly'
UPLOADS = KOREN / 'web/uploads'
MAPA = KOREN / 'podklady/data/mapa-souboru.json'
URL_RE = re.compile(r"https?://files\.cltk\.cz/[^\s\"'<>)\\]+")

pymupdf.TOOLS.mupdf_display_errors(False)
chyby = []
mapa = json.loads(MAPA.read_text(encoding='utf-8'))

# 1) DB → mapa
con = sqlite3.connect(f'file:{DB.as_posix()}?mode=ro', uri=True)
v_db = Counter()
for (t,) in con.execute("SELECT name FROM sqlite_master WHERE type='table'"):
    for c in con.execute(f'PRAGMA table_info("{t}")'):
        col = c[1]
        try:
            rows = con.execute(f'SELECT id, "{col}" FROM "{t}" WHERE "{col}" LIKE ?', ('%files.cltk.cz%',)).fetchall()
        except sqlite3.Error:
            continue
        for rid, v in rows:
            for u in dict.fromkeys(URL_RE.findall(str(v))):
                v_db[(t, col, rid, u)] += 1
v_mape = Counter((m['tabulka'], m['sloupec'], m['id'], m['stara_url']) for m in mapa)
for k in v_db:
    if v_mape[k] != 1:
        chyby.append(f'v mapě {v_mape[k]}× : {k}')
for k in v_mape:
    if k not in v_db:
        chyby.append(f'v mapě navíc: {k}')
    elif v_mape[k] > 1:
        chyby.append(f'v mapě duplicitně: {k}')
# stará URL v mapě přesně jako v DB (pro sloupce s celou adresou)
for m in mapa:
    v = con.execute(f'SELECT "{m["sloupec"]}" FROM {m["tabulka"]} WHERE id = ?', (m['id'],)).fetchone()[0]
    if m['stara_url'] not in v:
        chyby.append(f'stara_url není v DB: {m}')

# 2) soubory
soubory = {}
for m in mapa:
    soubory.setdefault(m['nova_cesta'], m)
    s = soubory[m['nova_cesta']]
    if (s['velikost_puvodni'], s['velikost_nova'], s['stran']) != (m['velikost_puvodni'], m['velikost_nova'], m['stran']):
        chyby.append(f'nesouhlasné údaje pro {m["nova_cesta"]}')
for cesta, m in soubory.items():
    if not re.fullmatch(r'[a-z0-9/-]+\.pdf', cesta):
        chyby.append(f'název není čisté ASCII: {cesta}')
    f, o = UPLOADS / cesta, ORIG / cesta
    if not f.exists() or not o.exists():
        chyby.append(f'chybí soubor {cesta}')
        continue
    if f.stat().st_size != m['velikost_nova'] or o.stat().st_size != m['velikost_puvodni']:
        chyby.append(f'velikost nesedí {cesta}')
    with pymupdf.open(f) as d, pymupdf.open(o) as do:
        if d.page_count != do.page_count or d.page_count != m['stran']:
            chyby.append(f'počet stran {cesta}: {d.page_count} / {do.page_count} / {m["stran"]}')
        if d.is_encrypted or d.needs_pass:
            chyby.append(f'zašifrované {cesta}')
        for i in range(d.page_count):
            d[i].get_pixmap(dpi=10)

# 3) nic navíc ve složkách
for slozka in ('revue/pdf', 'newslettery', 'dokumenty'):
    for p in (UPLOADS / slozka).iterdir():
        rel = p.relative_to(UPLOADS).as_posix()
        if rel not in soubory:
            chyby.append(f'soubor navíc: {rel}')
for p in ORIG.rglob('*'):
    if p.suffix in ('.part',) or '.zkouska-' in p.name:
        chyby.append(f'pomocný soubor: {p}')

celkem_p = sum(m['velikost_puvodni'] for m in soubory.values())
celkem_n = sum(m['velikost_nova'] for m in soubory.values())
print(f'Záznamů v mapě: {len(mapa)}, souborů: {len(soubory)}, výskytů v DB: {sum(v_db.values())}')
for slozka in ('revue/pdf', 'newslettery', 'dokumenty'):
    ss = [m for c, m in soubory.items() if c.startswith(slozka + '/')]
    print(f'  {slozka}: {len(ss)} souborů, {sum(m["velikost_puvodni"] for m in ss) / 1e6:.1f} MB → '
          f'{sum(m["velikost_nova"] for m in ss) / 1e6:.1f} MB, stran {sum(m["stran"] for m in ss)}')
print(f'Celkem {celkem_p / 1e6:.1f} MB → {celkem_n / 1e6:.1f} MB ({(1 - celkem_n / celkem_p) * 100:.0f} % méně)')
print('CHYBY:' if chyby else 'Bez chyb.')
for c in chyby:
    print('  ', c)
