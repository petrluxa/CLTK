"""Rozvrhy tenisové školy ze starého webu (PDF z LibreOffice Calc) → podklady/dokumenty-texty/rozvrhy.json.

Tabulku skládá z čar a výplní, které PDF opravdu obsahuje (ne z odhadu podle textu):
  * svislé a vodorovné čáry tabulky → mřížka drobných buněk, sousední buňky bez čáry
    mezi sebou se spojí (sloučené buňky – jedna skupina přes dvě hodiny nebo přes obě
    poloviny řádku);
  * šedá výplň = „obsazeno“, žlutá = „3 trenéři“ (legenda u kurtu 5; bílá = „2 trenéři“).

JMÉNA DĚTÍ SE NEPŘEBÍRAJÍ. V buňkách rozvrhu jsou jen příjmení dětí (někdy se zkratkou
jména); ukládá se jen den, čas, kurt, stav, počet trenérů podle barvy, počet jmen v buňce
a poznámka, která není jménem („samostatný sparing“). Jména jdou jen do kontrolního
výpisu v podklady/_raw/prilohy/ (mimo git, na web ne).

Spuštění z kořene projektu:  python nastroje/archiv-pdf/prilohy-rozvrhy.py
"""
import json
import re
from pathlib import Path

import pymupdf

KOREN = Path(__file__).resolve().parents[2]
STAZENE = KOREN / 'podklady/_raw/prilohy/stazene'
VYSTUP = KOREN / 'podklady/dokumenty-texty/rozvrhy.json'
KONTROLA = KOREN / 'podklady/_raw/prilohy/kontrola-rozvrhy.txt'

ROZVRHY = [
    dict(slug='zima-2026-27', soubor='h5h1rxl4mmn01.pdf',
         nazev='Rozvrh tenisové školy – zima 2026/27',
         odkaz_text='Rozvrh TŠ zima 2026/27',
         zdroj_url='https://files.cltk.cz/h5h1rxl4mmn01/rozvrh%20zima%202026%3A27.pdf',
         platnost_od='2026-09-29', platnost_do='2027-04-02'),
    dict(slug='tyden-2026-09-29', soubor='nnaiwso4zkn01.pdf',
         nazev='Rozvrh tenisové školy – 29. 9. – 2. 10. 2026',
         odkaz_text='Rozvrh TŠ 29.9. - 2.10.2026',
         zdroj_url='https://files.cltk.cz/nnaiwso4zkn01/rozvrh%2029%20zari%20-%202%20rijna%202026.pdf',
         platnost_od='2026-09-29', platnost_do='2026-10-02'),
]
DNY = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek']
POZNAMKY = ['samostatný sparing']   # text v buňce, který není jménem dítěte
POZNAMKA_ROZVRHU = (
    'V buňkách PDF jsou jen jména dětí (příjmení, u shod se zkratkou jména) – nepřebírají se. Skupina, věk, úroveň '
    'ani trenér konkrétní hodiny v PDF nejsou; u kurtu jsou jen „kontaktní trenéři“. Některá jména jsou v PDF červeně '
    'nebo šedě bez vysvětlení v legendě – tahle informace se váže ke jménům, proto se také nepřebírá. '
    'Věta „kontakty uvedeny výše“ v informacích odkazuje na kontaktní trenéry kurtu v PDF.')
TOL = 1.5


def cara_pokryva(cary, poloha, od, do, svisla: bool) -> bool:
    """Leží na `poloha` čára, která pokryje úsek od–do (ve druhé ose)?"""
    stred = (od + do) / 2
    for c in cary:
        if svisla and abs(c['x'] - poloha) < TOL and c['y0'] - TOL <= stred <= c['y1'] + TOL:
            return True
        if not svisla and abs(c['y'] - poloha) < TOL and c['x0'] - TOL <= stred <= c['x1'] + TOL:
            return True
    return False


def unikatni(hodnoty):
    vys = []
    for h in sorted(hodnoty):
        if not vys or h - vys[-1] > TOL:
            vys.append(h)
    return vys


def cas(t: str) -> tuple[str, str]:
    m = re.fullmatch(r'\s*(\d{1,2})\s*[–-]\s*(\d{1,2})\s*', t)
    if not m:
        raise ValueError(f'čas záhlaví: {t!r}')
    return f'{int(m[1]):02d}:00', f'{int(m[2]):02d}:00'


def pocet_jmen(text: str) -> int:
    """Počet dětí v buňce: příjmení, případně se zkratkou jména („Novotný H“, „Zaslavská Ch“,
    „Christensen Nat“, „Novotná Emma“) – zkratka/jméno za příjmením se nepočítá zvlášť."""
    slova = text.split()
    n = 0
    for s in slova:
        zkratka = len(s) <= 3 or s in ('Emma',)
        if not (zkratka and n):
            n += 1
    return n


def stranka(pg) -> dict:
    sirka = pg.rect.width
    cary_sv, cary_vod, vyplne = [], [], []
    for d in pg.get_drawings():
        if d['type'] == 's':
            for it in d['items']:
                if it[0] != 'l':
                    continue
                a, b = it[1], it[2]
                if abs(a.x - b.x) < 0.5:
                    cary_sv.append(dict(x=a.x, y0=min(a.y, b.y), y1=max(a.y, b.y)))
                elif abs(a.y - b.y) < 0.5:
                    cary_vod.append(dict(y=a.y, x0=min(a.x, b.x), x1=max(a.x, b.x)))
        elif d['type'] == 'f' and d.get('fill'):
            barva = tuple(round(v, 2) for v in d['fill'])
            vyplne.append(dict(r=d['rect'], barva=barva))
    # tabulka = čáry; okraj tabulky = nejzazší svislá čára
    xs = unikatni(c['x'] for c in cary_sv)
    ys = unikatni([c['y'] for c in cary_vod])
    x_konec, y_konec = xs[-1], ys[-1]
    spany = []
    for b in pg.get_text('dict')['blocks']:
        for line in b.get('lines', []):
            for s in line['spans']:
                if s['text'].strip():
                    x0, y0, x1, y1 = s['bbox']
                    spany.append(dict(t=s['text'], x=(x0 + x1) / 2, y=(y0 + y1) / 2, x0=x0, y0=y0,
                                      barva=s['color'], vel=s['size']))

    def text_v(x0, y0, x1, y1) -> str:
        vyber = [s for s in spany if x0 < s['x'] < x1 and y0 < s['y'] < y1]
        vyber.sort(key=lambda s: (round(s['y']), s['x0']))
        return re.sub(r'\s+', ' ', ' '.join(s['t'] for s in vyber)).strip()

    def vypln(x0, y0, x1, y1):
        cx, cy = (x0 + x1) / 2, (y0 + y1) / 2
        for v in vyplne:
            r = v['r']
            if r.x0 - TOL <= cx <= r.x1 + TOL and r.y0 - TOL <= cy <= r.y1 + TOL:
                return v['barva']
        return None

    # mřížka drobných buněk (sloupce × řádky) a sloučení přes chybějící čáry
    nc, nr = len(xs) - 1, len(ys) - 1
    rodic = {(c, r): (c, r) for c in range(nc) for r in range(nr)}

    def koren(k):
        while rodic[k] != k:
            rodic[k] = rodic[rodic[k]]
            k = rodic[k]
        return k

    for c in range(nc):
        for r in range(nr):
            if c + 1 < nc and not cara_pokryva(cary_sv, xs[c + 1], ys[r], ys[r + 1], True):
                rodic[koren((c + 1, r))] = koren((c, r))
            if r + 1 < nr and not cara_pokryva(cary_vod, ys[r + 1], xs[c], xs[c + 1], False):
                rodic[koren((c, r + 1))] = koren((c, r))
    skupiny: dict = {}
    for k in rodic:
        skupiny.setdefault(koren(k), []).append(k)
    bunky = []
    for clenove in skupiny.values():
        c0, c1 = min(k[0] for k in clenove), max(k[0] for k in clenove)
        r0, r1 = min(k[1] for k in clenove), max(k[1] for k in clenove)
        if len(clenove) != (c1 - c0 + 1) * (r1 - r0 + 1):
            raise RuntimeError('nepravidelná sloučená buňka')
        bunky.append(dict(c0=c0, c1=c1, r0=r0, r1=r1, x0=xs[c0], x1=xs[c1 + 1], y0=ys[r0], y1=ys[r1 + 1]))
    # záhlaví: první řádek = kurt + časy; první sloupec = dny
    plne = [y for y in ys if any(abs(c['y'] - y) < TOL and c['x0'] <= xs[0] + TOL and c['x1'] >= x_konec - TOL
                                 for c in cary_vod)]
    hlava = [b for b in bunky if b['r0'] == 0]
    roh = next(b for b in hlava if b['c0'] == 0)
    kurt_text = text_v(roh['x0'], roh['y0'], roh['x1'], roh['y1'])
    m = re.fullmatch(r'(Kurt \d+) (\S+)', kurt_text)
    kurt, povrch = m[1], m[2]
    casy = {}
    for b in hlava:
        if b['c0'] > 0:
            casy[b['c0']] = cas(text_v(b['x0'], b['y0'], b['x1'], b['y1']))
    radky = []
    for b in sorted(bunky, key=lambda b: (b['y0'], b['x0'])):
        if b['r0'] == 0 or b['c0'] == 0:
            continue
        # den = řádek mezi plnými čarami
        di = next(i for i in range(len(plne) - 1) if plne[i] - TOL <= b['y0'] and b['y1'] <= plne[i + 1] + TOL) - 1
        den_bunka = next(x for x in bunky if x['c0'] == 0 and x['y0'] - TOL <= (b['y0'] + b['y1']) / 2 <= x['y1'] + TOL)
        den = text_v(den_bunka['x0'], den_bunka['y0'], den_bunka['x1'], den_bunka['y1'])
        if den != DNY[di]:
            raise RuntimeError(f'den {den!r} ≠ {DNY[di]}')
        cele_vyska = abs(b['y0'] - plne[di + 1]) < TOL and abs(b['y1'] - plne[di + 2]) < TOL
        pul = None if cele_vyska else (1 if abs(b['y0'] - plne[di + 1]) < TOL else 2)
        text = text_v(b['x0'], b['y0'], b['x1'], b['y1'])
        poznamka = None
        jmena = text
        for p in POZNAMKY:
            if p in jmena:
                poznamka = p
                jmena = jmena.replace(p, ' ')
        barva = vypln(b['x0'], b['y0'], b['x1'], b['y1'])
        if barva == (0.4, 0.4, 0.4):
            stav = 'obsazeno'
        elif text:
            stav = 'trénink'
        else:
            stav = 'volno'
        radky.append(dict(
            den=den, cas_od=casy[b['c0']][0], cas_do=casy[b['c1']][1], kurt=kurt, povrch=povrch,
            soubezna_skupina=pul, stav=stav, skupina=None, vek=None, trener=None,
            treneru=(3 if barva == (1.0, 1.0, 0.0) else None),
            pocet_deti=pocet_jmen(jmena) if jmena.strip() else 0,
            poznamka=poznamka, _jmena=jmena.strip()))
    # boční panel vpravo od tabulky
    bok = [s for s in spany if s['x0'] > x_konec + 2 and s['y'] > ys[0] - 5]
    bok.sort(key=lambda s: (round(s['y']), s['x0']))
    treneri = []
    for s in bok:
        m = re.fullmatch(r'\s*(.+?) / telefon: (\+\d[\d ]+\d)\s*', s['t'])
        if m:
            treneri.append(dict(jmeno=m[1], telefon=m[2]))
    legenda = [s['t'].strip() for s in bok if s['vel'] <= 6.5 and s['t'].strip() in ('obsazeno', '2 trenéři', '3 trenéři')]
    cervene = [s for s in bok if s['barva'] == 0xff0000]
    info_nadpis = cervene[0]['t'].strip()
    body, akt = [], ''
    for s in cervene[1:]:
        t = s['t'].strip()
        if t.startswith('- '):
            if akt:
                body.append(akt)
            akt = t[2:]
        else:
            akt += ' ' + t
    body.append(akt)
    # záhlaví nad tabulkou
    nad = [s for s in spany if s['y'] < ys[0]]
    nad.sort(key=lambda s: s['x0'])
    nad_text = [s['t'].strip() for s in nad]
    pod = [s['t'].strip() for s in spany if ys[-1] < s['y'] < pg.rect.height]
    return dict(kurt=kurt, povrch=povrch, kontaktni_treneri=treneri, legenda=legenda,
                info_nadpis=info_nadpis, info=[re.sub(r'\s+', ' ', b).strip() for b in body],
                nad=nad_text, pod=pod, radky=radky, sirka=sirka)


def main() -> None:
    vystup = dict(
        _popis='Rozvrhy tenisové školy ze starého webu (tenisova-skolicka/rozvrhy/). Vytaženo skriptem '
               'nastroje/archiv-pdf/prilohy-rozvrhy.py z čar a výplní PDF. Jména dětí, která jsou v buňkách '
               'rozvrhu, se záměrně nepřebírají; pocet_deti = počet jmen v buňce (spočítaný, v PDF jako číslo není).',
        _pole=dict(
            den='den v týdnu jako v PDF', cas_od='začátek (z hlavičky sloupce)', cas_do='konec (u buňky přes dvě hodiny konec druhé hodiny)',
            kurt='kurt z rohu tabulky', povrch='povrch z rohu tabulky',
            soubezna_skupina='u kurtu 5 je hodina rozdělená na dvě souběžné skupiny: 1 = horní, 2 = dolní polovina řádku; null = buňka přes celou výšku řádku',
            stav='trénink | obsazeno (šedá výplň, legenda „obsazeno“) | volno (prázdná bílá buňka)',
            skupina='název skupiny – PDF žádný neuvádí', vek='věk/úroveň – PDF neuvádí',
            trener='trenér skupiny – PDF ho u buněk neuvádí, jen kontaktní trenéry kurtu',
            treneru='3 = žlutá buňka (legenda „3 trenéři“); null = bez barvy (legenda kurtu 5: „2 trenéři“, kurt 6 legendu trenérů nemá)',
            pocet_deti='počet jmen dětí v buňce (jména vynechána)',
            poznamka='text v buňce, který není jménem (např. „samostatný sparing“)'),
        rozvrhy=[])
    kontrola = []
    for r in ROZVRHY:
        src = STAZENE / r['soubor']
        with pymupdf.open(src) as d:
            stranky = [stranka(pg) for pg in d]
            stran = d.page_count
        prvni = stranky[0]
        nad = prvni['nad']
        skola = nad[0]
        verze = next(t for t in nad if t.startswith('verze k'))
        idx = nad.index('Tréninky:')
        platnost_pdf = nad[idx + 1]
        for s in stranky[1:]:
            if s['nad'] != nad or s['info'] != prvni['info'] or s['info_nadpis'] != prvni['info_nadpis']:
                raise RuntimeError(f'{r["soubor"]}: strany mají jiné záhlaví nebo informace')
        poznamka_pdf = [t for t in prvni['pod'] if t.startswith('Poznámka:')]
        mv = re.fullmatch(r'verze k (\d{1,2})/(\d{1,2})/(\d{4})', verze)
        zaznam = dict(
            slug=r['slug'], nazev=r['nazev'], skola=skola,
            platnost=platnost_pdf.replace(' - ', ' – '),
            platnost_od=r['platnost_od'], platnost_do=r['platnost_do'],
            verze=f'{mv[3]}-{int(mv[2]):02d}-{int(mv[1]):02d}', verze_text=verze,
            zdroj_url=r['zdroj_url'], odkaz_text=r['odkaz_text'], stran=stran,
            poznamka_pdf=poznamka_pdf[0].split(':', 1)[1].strip() if poznamka_pdf else None,
            info_nadpis=prvni['info_nadpis'].rstrip(': ').rstrip(':'),
            info=prvni['info'],
            kurty=[dict(kurt=s['kurt'], povrch=s['povrch'], kontaktni_treneri=s['kontaktni_treneri'],
                        legenda=s['legenda']) for s in stranky],
            radky=[{k: v for k, v in x.items() if not k.startswith('_')}
                   for s in stranky
                   for x in sorted(s['radky'], key=lambda x: (DNY.index(x['den']), x['cas_od'],
                                                              x['soubezna_skupina'] or 0))],
            poznamka=POZNAMKA_ROZVRHU)
        vystup['rozvrhy'].append(zaznam)
        kontrola.append(f'===== {r["nazev"]} ({r["soubor"]}, {stran} s.)')
        kontrola.append(f'záhlaví: {nad}')
        for s in stranky:
            kontrola.append(f'--- {s["kurt"]} {s["povrch"]} | trenéři {s["kontaktni_treneri"]} | legenda {s["legenda"]}')
            for x in s['radky']:
                kontrola.append(f'{x["den"]:<8} {x["cas_od"]}–{x["cas_do"]} pol={x["soubezna_skupina"]!s:<4} '
                                f'{x["stav"]:<8} trenéři={x["treneru"]!s:<4} dětí={x["pocet_deti"]} '
                                f'pozn={x["poznamka"]!s:<18} | {x["_jmena"]}')
    # kontrola úplnosti: každá hodina × den × polovina řádku kurtu je pokrytá právě jednou buňkou
    kontrola.append('===== pokrytí mřížky')
    for z in vystup['rozvrhy']:
        for k in z['kurty']:
            radky = [x for x in z['radky'] if x['kurt'] == k['kurt']]
            pokryti: dict = {}
            for x in radky:
                for h in range(int(x['cas_od'][:2]), int(x['cas_do'][:2])):
                    for pul in ([1, 2] if x['soubezna_skupina'] is None else [x['soubezna_skupina']]):
                        pokryti[(x['den'], h, pul)] = pokryti.get((x['den'], h, pul), 0) + 1
            hodiny = sorted({h for _, h, _ in pokryti})
            cekano = len(DNY) * len(hodiny) * 2
            prekryvy = sum(1 for v in pokryti.values() if v > 1)
            if len(pokryti) != cekano or prekryvy or hodiny != list(range(hodiny[0], hodiny[-1] + 1)):
                raise RuntimeError(f'{z["slug"]} {k["kurt"]}: mřížka neúplná nebo s překryvy')
            kontrola.append(f'{z["slug"]} {k["kurt"]}: {len(radky)} buněk, pokryto {len(pokryti)}/{cekano}, překryvů 0, '
                            f'{hodiny[0]}–{hodiny[-1] + 1} h, dětí (počet jmen) {sum(x["pocet_deti"] for x in radky)}, '
                            f'obsazeno {sum(1 for x in radky if x["stav"] == "obsazeno")}')
    VYSTUP.write_text(json.dumps(vystup, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    KONTROLA.write_text('\n'.join(kontrola) + '\n', encoding='utf-8')
    for z in vystup['rozvrhy']:
        print(z['nazev'], len(z['radky']), 'buněk')


if __name__ == '__main__':
    main()
