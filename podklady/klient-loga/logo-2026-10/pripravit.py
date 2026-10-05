"""Nové logo I. ČLTK (od Petra 5. 10. 2026, PNG 436×396 na bílém) → průhledné PNG pro web.

1. Pozadí = bílé pixely SPOJENÉ s okrajem obrázku (vyplňování od okraje). Bílý pruh trikolóry a bílá linka
   uvnitř rámu se nedotknou, protože je od okolí odděluje zlatý rám.
2. Vyhlazený okraj: u pixelů na hranici rámu a pozadí se spočítá průhlednost proti bílé a barva se vezme
   z nejbližšího plně krytého pixelu rámu → žádný bílý lem na krémovém pozadí webu.
3. Ořez na znak + průhledný okraj na poměr 224 : 256 (stejný jako dřív – atributy width/height v šabloně platí).
4. Varianty: logo.png (4× zvětšené Lanczos + jemné doostření, pro velká místa), logo-512/256/128/64.png.
Spuštění: python podklady/klient-loga/logo-2026-10/pripravit.py   (zapisuje do web/assets/img/)
"""
import os
from collections import deque
import numpy as np
from PIL import Image, ImageFilter

TU = os.path.dirname(os.path.abspath(__file__))
CIL = os.path.normpath(os.path.join(TU, '..', '..', '..', 'web', 'assets', 'img'))

im = Image.open(os.path.join(TU, 'logo-original.png')).convert('RGB')
a = np.asarray(im).astype(np.float64)
H, W, _ = a.shape
bila = np.array([255.0, 255.0, 255.0])
vzd = np.sqrt(((bila - a) ** 2).sum(axis=2))            # vzdálenost od bílé

# 1) pozadí: téměř bílé pixely spojené s okrajem
skoro_bila = vzd < 60
pozadi = np.zeros((H, W), bool)
q = deque()
for x in range(W):
    for y in (0, H - 1):
        if skoro_bila[y, x] and not pozadi[y, x]: pozadi[y, x] = True; q.append((y, x))
for y in range(H):
    for x in (0, W - 1):
        if skoro_bila[y, x] and not pozadi[y, x]: pozadi[y, x] = True; q.append((y, x))
while q:
    y, x = q.popleft()
    for dy, dx in ((1, 0), (-1, 0), (0, 1), (0, -1)):
        ny, nx = y + dy, x + dx
        if 0 <= ny < H and 0 <= nx < W and skoro_bila[ny, nx] and not pozadi[ny, nx]:
            pozadi[ny, nx] = True; q.append((ny, nx))

# 2) alfa: uvnitř 1, v pozadí 0; na hranici (pozadí s „téměř bílou“, které sousedí se znakem) podle barvy
alfa = np.where(pozadi, 0.0, 1.0)
barva = a.copy()
# plně kryté pixely znaku = ne-pozadí a dál než 2 px od pozadí
def bfs(zdroje):
    """Vzdálenost (šachovnicová, 8 sousedů) od nejbližšího pixelu masky a jeho souřadnice."""
    dist = np.full((H, W), np.inf); sy = np.zeros((H, W), int); sx = np.zeros((H, W), int)
    fr = deque()
    for y, x in zip(*np.where(zdroje)):
        dist[y, x] = 0; sy[y, x] = y; sx[y, x] = x; fr.append((y, x))
    while fr:
        y, x = fr.popleft()
        for dy in (-1, 0, 1):
            for dx in (-1, 0, 1):
                ny, nx = y + dy, x + dx
                if (dy or dx) and 0 <= ny < H and 0 <= nx < W and dist[ny, nx] == np.inf:
                    dist[ny, nx] = dist[y, x] + 1; sy[ny, nx] = sy[y, x]; sx[ny, nx] = sx[y, x]; fr.append((ny, nx))
    return dist, sy, sx

dist_od_pozadi, _, _ = bfs(pozadi)
kryte = (~pozadi) & (dist_od_pozadi > 2)
_, ky, kx = bfs(kryte)                                              # nejbližší krytý pixel
pas = (dist_od_pozadi <= 2.5) | (pozadi & (vzd > 6))               # hraniční pás (obě strany)
for y, x in zip(*np.where(pas)):
    f = a[ky[y, x], kx[y, x]]                                       # barva rámu poblíž
    c = a[y, x]
    w = bila - f
    n = float((w * w).sum())
    if n < 1: continue
    t = float(((bila - c) * w).sum()) / n                           # c = bílá + t·(f − bílá)
    t = min(1.0, max(0.0, t))
    if pozadi[y, x] and t < 0.04: t = 0.0
    alfa[y, x] = t
    barva[y, x] = f if t > 0 else c

rgba = np.dstack([np.clip(barva, 0, 255), np.clip(alfa * 255, 0, 255)]).astype(np.uint8)
znak = Image.fromarray(rgba, 'RGBA')

# 3) ořez na znak a plátno 224 : 256
ys, xs = np.where(alfa > 0.02)
x0, y0, x1, y1 = xs.min(), ys.min(), xs.max() + 1, ys.max() + 1
znak = znak.crop((x0, y0, x1, y1))
zw, zh = znak.size
POMER = 224 / 256
okraj = 2
ch = zh + 2 * okraj
cw = max(zw + 2 * okraj, round(ch * POMER))
ch = max(ch, round(cw / POMER))
platno = Image.new('RGBA', (cw, ch), (0, 0, 0, 0))
platno.alpha_composite(znak, ((cw - zw) // 2, (ch - zh) // 2))
print('znak', (zw, zh), 'plátno', platno.size, 'pozadí px', int(pozadi.sum()),
      'průhledných uvnitř obrysu', int(((alfa < 0.5)[y0:y1, x0:x1] & ~pozadi[y0:y1, x0:x1]).sum()))

# 4) varianty – zvětšovat s předem vynásobenou alfou (jinak na okraji vzniknou světlé lemy)
def zmen(img, sirka):
    vyska = round(sirka / POMER)
    p = np.asarray(img).astype(np.float64) / 255
    prem = np.dstack([p[..., :3] * p[..., 3:4], p[..., 3:4]])
    out = Image.fromarray((prem * 255).round().astype(np.uint8), 'RGBA').resize((sirka, vyska), Image.LANCZOS)
    o = np.asarray(out).astype(np.float64) / 255
    al = o[..., 3:4]
    rgb = np.where(al > 0.003, o[..., :3] / np.maximum(al, 1e-6), 0)
    return Image.fromarray((np.dstack([np.clip(rgb, 0, 1), al]) * 255).round().astype(np.uint8), 'RGBA')

velke = zmen(platno, platno.size[0] * 4)
rgb, al = velke.convert('RGB').filter(ImageFilter.UnsharpMask(radius=2.2, percent=55, threshold=2)), velke.getchannel('A')
velke = Image.merge('RGBA', (*rgb.split(), al))
velke.save(os.path.join(CIL, 'logo.png'), optimize=True)
for s in (512, 256, 128, 64):
    zmen(velke, s).save(os.path.join(CIL, f'logo-{s}.png'), optimize=True)
print('uloženo do', CIL, velke.size)

# 5) na stránky lehké WebP (průhledné), PNG zůstávají pro ikony a náhled sdílení; logo.png zmenšené na 640 px
zmen(velke, 448).save(os.path.join(CIL, 'logo.webp'), 'WEBP', quality=88, method=6)
zmen(velke, 640).save(os.path.join(CIL, 'logo.png'), optimize=True)
for f in ('logo.webp', 'logo.png', 'logo-512.png', 'logo-256.png', 'logo-128.png', 'logo-64.png'):
    print(f, os.path.getsize(os.path.join(CIL, f)) // 1024, 'kB')
