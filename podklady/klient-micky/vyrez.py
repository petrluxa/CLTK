"""Míčky HEAD Stage 3/2/1 (od Petra 5. 10. 2026, JPG na bílém) → PNG s průhledným okolím do web/assets/img/.
Kruh podle obrysu míčku (vše, co není bílé), maska vyhlazená 4× převzorkováním, o 1 px dovnitř kvůli bílému lemu."""
import os
import numpy as np
from PIL import Image, ImageDraw

TU = os.path.dirname(os.path.abspath(__file__))
CIL = os.path.join(TU, '..', '..', 'web', 'assets', 'img')
for jmeno in ('micek-cerveny', 'micek-oranzovy', 'micek-zeleny'):
    im = Image.open(os.path.join(TU, jmeno + '-original.jpg')).convert('RGB')
    a = np.asarray(im).astype(int)
    m = np.sqrt(((255 - a) ** 2).sum(axis=2)) > 40
    ys, xs = np.where(m)
    y0, y1, x0, x1 = int(ys.min()), int(ys.max()), int(xs.min()), int(xs.max())
    cx, cy, r = (x0 + x1) / 2, (y0 + y1) / 2, max(x1 - x0, y1 - y0) / 2
    S, (W, H) = 4, im.size
    mask = Image.new('L', (W * S, H * S), 0)
    ImageDraw.Draw(mask).ellipse([(cx - r + 1) * S, (cy - r + 1) * S, (cx + r - 1) * S, (cy + r - 1) * S], fill=255)
    out = im.convert('RGBA')
    out.putalpha(mask.resize((W, H), Image.LANCZOS))
    box = (max(0, round(cx - r)), max(0, round(cy - r)), min(W, round(cx + r) + 1), min(H, round(cy + r) + 1))
    out = out.crop(box)
    out.save(os.path.join(CIL, jmeno + '.png'), optimize=True)
    print(jmeno, im.size, 'obrys', (x0, y0, x1, y1), 'poloměr', round(r, 1), '->', out.size)
