"""Míčky Babolat (Red / Orange / Green, fotky od Petra 6. 10. 2026) → průhledné WebP do web/assets/img/micek-<barva>.webp.

Míček na fotce není dokonalý kruh (plsť, mírně zploštělý) a bílé švy na okraji splývají s bílým pozadím, proto:
1. maska plsti = SYTÉ pixely (stín, bílé pozadí ani švy nejsou syté); drobné ostrůvky (šum, kousky okolí) pryč,
2. obrys míčku = KONVEXNÍ OBAL plsti (míček je konvexní – obal přemostí i švy, které vybíhají k okraji),
3. maska vyhlazená 4× převzorkováním a o ~0,75 px zúžená (žádný světlý lem z bílého pozadí),
4. čtverec kolem míčku, max. 360 px, WebP s alfou (a PNG pro kontrolu do podkladů).
Spuštění: python podklady/klient-micky/babolat/vyrez.py [barva …]
"""
import os
import sys
from collections import deque
import numpy as np
from PIL import Image, ImageDraw, ImageFilter

TU = os.path.dirname(os.path.abspath(__file__))
CIL = os.path.normpath(os.path.join(TU, '..', '..', '..', 'web', 'assets', 'img'))
MAX = 360


def komponenty(maska):
    """Souvislé oblasti masky (4-sousedství) → seznam (velikost, [(y, x), …])."""
    H, W = maska.shape
    videno = np.zeros_like(maska, bool)
    vysledek = []
    for y0, x0 in zip(*np.where(maska)):
        if videno[y0, x0]:
            continue
        q, body = deque([(y0, x0)]), []
        videno[y0, x0] = True
        while q:
            y, x = q.popleft(); body.append((y, x))
            for dy, dx in ((1, 0), (-1, 0), (0, 1), (0, -1)):
                ny, nx = y + dy, x + dx
                if 0 <= ny < H and 0 <= nx < W and maska[ny, nx] and not videno[ny, nx]:
                    videno[ny, nx] = True; q.append((ny, nx))
        vysledek.append((len(body), body))
    return sorted(vysledek, key=lambda k: -k[0])


def konvexni_obal(body):
    """Monotone chain – konvexní obal bodů [(x, y), …] proti směru hodin."""
    b = sorted(set(body))
    if len(b) < 3:
        return b
    def kriz(o, a, c):
        return (a[0] - o[0]) * (c[1] - o[1]) - (a[1] - o[1]) * (c[0] - o[0])
    dolni, horni = [], []
    for p in b:
        while len(dolni) >= 2 and kriz(dolni[-2], dolni[-1], p) <= 0: dolni.pop()
        dolni.append(p)
    for p in reversed(b):
        while len(horni) >= 2 and kriz(horni[-2], horni[-1], p) <= 0: horni.pop()
        horni.append(p)
    return dolni[:-1] + horni[:-1]


def zmen(img, s):
    """Zmenšení s předem vynásobenou alfou (bez světlého lemu)."""
    p = np.asarray(img).astype(np.float64) / 255
    prem = Image.fromarray((np.dstack([p[..., :3] * p[..., 3:4], p[..., 3:4]]) * 255).round().astype(np.uint8), 'RGBA')
    o = np.asarray(prem.resize((s, s), Image.LANCZOS)).astype(np.float64) / 255
    al = o[..., 3:4]
    rgb = np.where(al > 0.003, o[..., :3] / np.maximum(al, 1e-6), 0)
    return Image.fromarray((np.dstack([np.clip(rgb, 0, 1), al]) * 255).round().astype(np.uint8), 'RGBA')


for barva in (sys.argv[1:] or ['cerveny', 'oranzovy', 'zeleny']):
    cesta = os.path.join(TU, f'{barva}-original.png')
    im = Image.open(cesta).convert('RGB')
    a = np.asarray(im).astype(np.float64) / 255
    mx, mn = a.max(axis=2), a.min(axis=2)
    syt = np.where(mx > 0, (mx - mn) / np.maximum(mx, 1e-6), 0)
    plst = (syt > 0.28) & (mx > 0.25)

    # 1) ostrůvky: nechat oblasti, které mají aspoň 3 % největší (vrchlík, střed, spodek míčku)
    kom = komponenty(plst)
    nejvetsi = kom[0][0]
    body = [bod for vel, bb in kom if vel >= 0.03 * nejvetsi for bod in bb]
    zahozeno = sum(vel for vel, _ in kom if vel < 0.03 * nejvetsi)

    # 2) konvexní obal z krajních pixelů každého řádku (rohy pixelů → přesný obrys)
    radky = {}
    for y, x in body:
        lo, hi = radky.get(y, (x, x)); radky[y] = (min(lo, x), max(hi, x))
    rohy = []
    for y, (lo, hi) in radky.items():
        rohy += [(lo, y), (lo, y + 1), (hi + 1, y), (hi + 1, y + 1)]
    obal = konvexni_obal(rohy)

    # 3) maska 4×, zúžení ~0,75 px, vyhlazení
    W, H = im.size
    S = 4
    m4 = Image.new('L', (W * S, H * S), 0)
    ImageDraw.Draw(m4).polygon([(x * S, y * S) for x, y in obal], fill=255)
    m4 = m4.filter(ImageFilter.MinFilter(7))
    maska = m4.resize((W, H), Image.LANCZOS)
    out = Image.merge('RGBA', (*im.split(), maska))

    # 4) čtverec kolem míčku
    x0, y0, x1, y1 = maska.getbbox()
    out = out.crop((x0, y0, x1, y1))
    s = max(out.size)
    ctverec = Image.new('RGBA', (s, s), (0, 0, 0, 0))
    ctverec.alpha_composite(out, ((s - out.size[0]) // 2, (s - out.size[1]) // 2))
    if s > MAX:
        ctverec = zmen(ctverec, MAX)
    ven = os.path.join(CIL, f'micek-{barva}.webp')
    ctverec.save(ven, 'WEBP', quality=90, method=6)
    ctverec.save(os.path.join(TU, f'{barva}-vyrez.png'))
    print(f'{barva}: {W}×{H}, obrys {x1 - x0}×{y1 - y0} px (poměr {(x1 - x0) / (y1 - y0):.3f}), oblastí plsti {sum(1 for v, _ in kom if v >= 0.03 * nejvetsi)}, '
          f'zahozeno {zahozeno} px šumu, obal {len(obal)} bodů → {ctverec.size}, {os.path.getsize(ven) // 1024} kB')
