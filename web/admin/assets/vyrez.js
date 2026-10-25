/* Výřez fotky v administraci (pole_vyrez v admin/inc/ui.php; Petr 25. 10. 2026: „ať si manažer při psaní sám vybere
   výřez fotky“). Rámeček v poměru stran rámu na webu: táhne se myší nebo prstem, velikost za rohy, posuvníkem nebo
   klávesami (šipky posun, + − přiblížení); vedle živý náhled karty. Výsledek = dvě skrytá pole:
     fokus „x% y%“ – object-position, a zároveň počátek transform: scale,
     vyrez – přiblížení 1–3 (1 = největší výřez v daném poměru stran) | 'cela' = celá fotka na paspartě.
   object-fit: cover + object-position + scale se stejným počátkem dají na webu přesně stejný obdélník jako tady
   (aktualita_vyrez() v inc/data.php): výřez šířky w = okno / zoom leží na x = fokus_x · (šířka fotky − w). */
(function () {
  'use strict';

  var MESICE = ['ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince'];

  function cislo(v, d) { var n = parseFloat(v); return isFinite(n) ? n : d; }
  function omez(v, a, b) { return Math.max(a, Math.min(b, v)); }
  function zaokr(v, mista) { var k = Math.pow(10, mista); return Math.round(v * k) / k; }

  /* „2026-10-05“ (nebo „5. 10. 2026“) → „5. října 2026“ jako na webu */
  function datumDlouze(s) {
    var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s) || null, d, mes, r;
    if (m) { r = +m[1]; mes = +m[2]; d = +m[3]; }
    else {
      m = /^(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})$/.exec(s);
      if (!m) return '';
      d = +m[1]; mes = +m[2]; r = +m[3];
    }
    if (mes < 1 || mes > 12 || d < 1 || d > 31) return '';
    return d + '. ' + MESICE[mes - 1] + ' ' + r;
  }

  document.querySelectorAll('[data-vyrez]').forEach(function (pole) {
    var form = pole.closest('form');
    var pomer = cislo(pole.getAttribute('data-pomer'), 4 / 3);
    var zoomMax = Math.max(1, cislo(pole.getAttribute('data-zoom-max'), 3));
    var maxPx = cislo(pole.getAttribute('data-max-px'), 0);
    var auto = (pole.getAttribute('data-auto') || '1.2 1.55').split(/\s+/).map(function (x) { return cislo(x, 0); });
    var plocha = pole.querySelector('[data-vyrez-plocha]');
    var prazdno = pole.querySelector('[data-vyrez-prazdno]');
    var obal = pole.querySelector('[data-vyrez-obraz]');
    var img = obal.querySelector('img');
    var ram = pole.querySelector('[data-vyrez-ram]');
    var zoomVstup = pole.querySelector('[data-vyrez-zoom]');
    var zoomText = pole.querySelector('[data-vyrez-zoom-text]');
    var reset = pole.querySelector('[data-vyrez-reset]');
    var varovani = pole.querySelector('[data-vyrez-varovani]');
    var rezimy = pole.querySelectorAll('[data-vyrez-rezim]');
    var fokusVstup = pole.querySelector('[data-vyrez-fokus]');
    var hodnotaVstup = pole.querySelector('[data-vyrez-hodnota]');
    var proVstup = pole.querySelector('[data-vyrez-pro]');
    var karta = pole.querySelector('[data-vyrez-karta]');
    var kartaImg = karta ? karta.querySelector('img') : null;
    var jmenoSouboru = pole.getAttribute('data-soubor');
    var souborVstup = form && jmenoSouboru ? form.querySelector('input[type="file"][name="' + jmenoSouboru + '"]') : null;
    var odebrat = form && jmenoSouboru ? form.querySelector('input[name="' + jmenoSouboru + '_odebrat"]') : null;
    var obalPole = pole.closest('.field') || pole;
    if (!obal || !img || !ram || !fokusVstup || !hodnotaVstup) return;

    var W = 0, H = 0;               // přirozená velikost fotky (po natočení podle EXIF – stejně jako na serveru)
    var st = { rezim: pole.getAttribute('data-rezim') === 'cela' ? 'cela' : 'vyrez', z: 1, px: .5, py: .5 };
    st.z = omez(cislo(pole.getAttribute('data-zoom'), 1), 1, zoomMax);
    var f = /^\s*([\d.]+)%\s+([\d.]+)%\s*$/.exec(pole.getAttribute('data-fokus') || fokusVstup.value);
    if (f) { st.px = omez(cislo(f[1], 50) / 100, 0, 1); st.py = omez(cislo(f[2], 50) / 100, 0, 1); }

    /* největší výřez v poměru stran (přiblížení 1) */
    function okno() { return W / H > pomer ? { w: H * pomer, h: H } : { w: W, h: W / pomer }; }
    /* stav → obdélník výřezu v pixelech fotky */
    function obdelnik() {
      var o = okno(), w = o.w / st.z, h = o.h / st.z;
      return { x: st.px * (W - w), y: st.py * (H - h), w: w, h: h };
    }
    /* obdélník → stav (přiblížení na setiny, poloha na desetiny procenta = přesně to, co se uloží) */
    function zObdelniku(r) {
      var o = okno();
      st.z = omez(zaokr(o.w / r.w, 2), 1, zoomMax);
      var w = o.w / st.z, h = o.h / st.z;
      if (W - w > .5) st.px = omez(zaokr(r.x / (W - w), 3), 0, 1);
      if (H - h > .5) st.py = omez(zaokr(r.y / (H - h), 3), 0, 1);
    }

    function zapis() {
      fokusVstup.value = zaokr(st.px * 100, 1) + '% ' + zaokr(st.py * 100, 1) + '%';
      hodnotaVstup.value = st.rezim === 'cela' ? 'cela' : String(st.z);
      hodnotaVstup.dispatchEvent(new Event('change', { bubbles: true }));     // hlídání neuložených změn formuláře
    }

    function vykresli() {
      var cela = st.rezim === 'cela';
      pole.classList.toggle('vyrez--cela', cela);
      rezimy.forEach(function (r) { r.checked = r.value === st.rezim; });
      zoomVstup.disabled = cela;
      if (reset) reset.disabled = cela;
      zoomVstup.value = String(st.z);
      if (zoomText) zoomText.textContent = Math.round(st.z * 100) + ' %';
      var fokus = zaokr(st.px * 100, 1) + '% ' + zaokr(st.py * 100, 1) + '%';
      if (W && H) {
        var r = obdelnik();
        ram.style.left = (r.x / W * 100) + '%';
        ram.style.top = (r.y / H * 100) + '%';
        ram.style.width = (r.w / W * 100) + '%';
        ram.style.height = (r.h / H * 100) + '%';
        if (varovani) {
          var skut = maxPx > 0 ? r.w * Math.min(1, maxPx / Math.max(W, H)) : r.w;   // fotka se na serveru zmenší
          varovani.hidden = cela || skut >= 700;
        }
      }
      if (kartaImg) {
        karta.classList.toggle('vyrez-karta__foto--cela', cela);
        kartaImg.style.objectPosition = cela ? '50% 50%' : fokus;
        kartaImg.style.transformOrigin = fokus;
        kartaImg.style.transform = !cela && st.z > 1.001 ? 'scale(' + st.z + ')' : '';
      }
    }

    function zmena() { vykresli(); zapis(); }

    function priblizit(z) {          // střed výřezu zůstane na místě
      if (!W) return;
      var r = obdelnik(), cx = r.x + r.w / 2, cy = r.y + r.h / 2, o = okno();
      z = omez(zaokr(z, 2), 1, zoomMax);
      var w = o.w / z, h = o.h / z;
      zObdelniku({ x: omez(cx - w / 2, 0, W - w), y: omez(cy - h / 2, 0, H - h), w: w, h: h });
      zmena();
    }

    /* ---------- fotka: uložená, nebo právě vybraná ---------- */
    function nacteno(nova) {
      W = img.naturalWidth; H = img.naturalHeight;
      if (!W || !H) return;
      if (nova) {
        var tvar = W / H;
        st = { rezim: tvar < auto[0] || tvar > auto[1] ? 'cela' : 'vyrez', z: 1, px: .5, py: .5 };
        if (proVstup) proVstup.value = 'nova';
      }
      plocha.hidden = false;
      if (prazdno) prazdno.hidden = true;
      vykresli();
      if (nova) zapis();
    }
    function nacti(src, nova) {
      img.onload = function () { nacteno(nova); };
      img.src = src;
      if (kartaImg) kartaImg.src = src;
    }
    if (img.getAttribute('src')) {
      if (img.complete && img.naturalWidth) nacteno(false); else img.onload = function () { nacteno(false); };
    }
    if (souborVstup) {
      souborVstup.addEventListener('change', function () {
        var soubor = souborVstup.files && souborVstup.files[0];
        if (!soubor || !window.URL || !/^image\//.test(soubor.type)) return;
        if (odebrat) { odebrat.checked = false; obalPole.hidden = false; }
        nacti(URL.createObjectURL(soubor), true);
      });
    }
    if (odebrat) {
      odebrat.addEventListener('change', function () { obalPole.hidden = odebrat.checked; });
    }

    /* ---------- tažení: posun rámečku, nebo velikost za roh ---------- */
    var tah = null;
    function bod(e) {
      var b = obal.getBoundingClientRect();
      return { x: (e.clientX - b.left) / b.width * W, y: (e.clientY - b.top) / b.height * H };
    }
    obal.addEventListener('pointerdown', function (e) {
      if (st.rezim === 'cela' || !W || (e.pointerType === 'mouse' && e.button !== 0)) return;
      e.preventDefault();
      var p = bod(e), r = obdelnik();
      var roh = e.target.closest ? e.target.closest('[data-roh]') : null;
      if (roh) {
        var k = roh.getAttribute('data-roh');                 // lh | ph | ld | pd = levý/pravý + horní/dolní
        var vlevo = k.charAt(0) === 'l', nahoru = k.charAt(1) === 'h';
        tah = { typ: 'roh', vlevo: vlevo, nahoru: nahoru, ax: vlevo ? r.x + r.w : r.x, ay: nahoru ? r.y + r.h : r.y };
      } else {
        if (p.x < r.x || p.x > r.x + r.w || p.y < r.y || p.y > r.y + r.h) {   // klepnutí vedle = rámeček sem
          zObdelniku({ x: omez(p.x - r.w / 2, 0, W - r.w), y: omez(p.y - r.h / 2, 0, H - r.h), w: r.w, h: r.h });
          zmena();
          r = obdelnik();
        }
        tah = { typ: 'posun', dx: p.x - r.x, dy: p.y - r.y };
      }
      pole.classList.add('vyrez--tah');
      try { obal.setPointerCapture(e.pointerId); } catch (chyba) { /* starý prohlížeč – tah funguje i bez zachycení */ }
      ram.focus({ preventScroll: true });
    });
    obal.addEventListener('pointermove', function (e) {
      if (!tah) return;
      var p = bod(e), r = obdelnik(), o = okno();
      if (tah.typ === 'posun') {
        zObdelniku({ x: omez(p.x - tah.dx, 0, W - r.w), y: omez(p.y - tah.dy, 0, H - r.h), w: r.w, h: r.h });
      } else {
        var minW = o.w / zoomMax;
        var maxW = Math.min(o.w, tah.vlevo ? tah.ax : W - tah.ax, (tah.nahoru ? tah.ay : H - tah.ay) * pomer);
        var w = omez(Math.max(Math.abs(p.x - tah.ax), Math.abs(p.y - tah.ay) * pomer), minW, Math.max(minW, maxW));
        var h = w / pomer;
        zObdelniku({ x: omez(tah.vlevo ? tah.ax - w : tah.ax, 0, W - w), y: omez(tah.nahoru ? tah.ay - h : tah.ay, 0, H - h), w: w, h: h });
      }
      zmena();
    });
    function konec() { tah = null; pole.classList.remove('vyrez--tah'); }
    obal.addEventListener('pointerup', konec);
    obal.addEventListener('pointercancel', konec);
    obal.addEventListener('lostpointercapture', konec);

    /* ---------- klávesnice: šipky posun (Shift = víc), + − přiblížení ---------- */
    ram.addEventListener('keydown', function (e) {
      if (st.rezim === 'cela' || !W) return;
      var krok = e.shiftKey ? .1 : .02, r = obdelnik();
      if (e.key === '+' || e.key === '=' || e.key === 'Add') { e.preventDefault(); priblizit(st.z + .1); return; }
      if (e.key === '-' || e.key === '_' || e.key === 'Subtract') { e.preventDefault(); priblizit(st.z - .1); return; }
      var posun = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] }[e.key];
      if (!posun) return;
      e.preventDefault();
      zObdelniku({ x: omez(r.x + posun[0] * W * krok, 0, W - r.w), y: omez(r.y + posun[1] * H * krok, 0, H - r.h), w: r.w, h: r.h });
      zmena();
    });

    zoomVstup.addEventListener('input', function () { priblizit(cislo(zoomVstup.value, 1)); });
    if (reset) reset.addEventListener('click', function () { st.z = 1; st.px = .5; st.py = .5; zmena(); });
    rezimy.forEach(function (r) {
      r.addEventListener('change', function () { if (r.checked) { st.rezim = r.value === 'cela' ? 'cela' : 'vyrez'; zmena(); } });
    });

    /* ---------- živý náhled karty: datum, nadpis, popis z formuláře ---------- */
    ['datum', 'nadpis', 'popis'].forEach(function (k) {
      var jmeno = pole.getAttribute('data-nahled-' + k);
      var cil = pole.querySelector('[data-vyrez-text="' + k + '"]');
      var vstup = form && jmeno ? form.querySelector('[name="' + jmeno + '"]') : null;
      if (!cil || !vstup) return;
      function prepis() {
        var t = (vstup.value || '').trim();
        if (k === 'datum') t = datumDlouze(t);
        if (k === 'popis' && t.length > 220) t = t.slice(0, 219) + '…';
        cil.textContent = t;
        cil.hidden = t === '';
      }
      vstup.addEventListener('input', prepis);
      vstup.addEventListener('change', prepis);
      prepis();
    });

    vykresli();
  });
})();
