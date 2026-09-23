/* ==========================================================================
   I. ČLTK Praha – koncept C „Živá Štvanice“ – sdílené chování (vlastní CORE)
   Vanilla JS, bez závislostí. Funguje z file:// i z podsložky GitHub Pages.
   Veřejné API: window.CLTK (datum, sezona, kc, sklonuj, data, pohybPovolen, hlas)
   Komponenty se zapínají atributy v HTML (data-mapa, data-kalkulacka, data-cifernik …).
   ========================================================================== */
(function () {
  'use strict';

  var CLTK = (window.CLTK = window.CLTK || {});
  var doc = document;
  var root = doc.documentElement;

  /* ── Úložiště (jen pohodlí, vždy v try/catch) ─────────────────────────── */
  var uloz = {
    get: function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { if (v === null) window.localStorage.removeItem(k); else window.localStorage.setItem(k, v); } catch (e) { /* nic */ } }
  };
  CLTK.uloz = uloz;

  /* ── Data z obsah.json (stav k 23. 9. 2026) ─────────────────────────────── */
  var DATA = (CLTK.data = {
    telefon: '+420 608 974 974',
    telefonOdkaz: 'tel:+420608974974',
    email: 'recepce@cltk.cz',
    rezervace: 'https://www.rogeronline.cz/v2/index.php?klub=181',
    souradnice: { lat: 50.0955, lon: 14.4378 },
    stavK: '2026-09-23',
    sezona: {
      stavbaHal: ['2026-09-21', '2026-09-28'],
      pevnaHala: ['2026-09-28', '2027-04-04'],
      pretlakove: ['2026-10-05', '2027-04-04'],
      tsZima: ['2026-09-07', '2027-04-02'],
      tsVolno: [['2026-09-21', '2026-09-28'], ['2026-10-26', '2026-10-30'], ['2026-11-17', '2026-11-17'], ['2026-12-23', '2027-01-03']]
    },
    uzavirky: [
      ['2026-07-20', '2026-07-26', 'Livesport Prague Open (WTA 250)'],
      ['2026-08-01', '2026-08-02', 'Oktagon 92 – od 14:00 uzavřeny šatny a fitness, od 10:00 vnitřní parkoviště a oblouky viaduktu'],
      ['2026-08-16', '2026-08-22', 'Sekyra Group Prague Open 2026']
    ],
    cenik: {
      zima: [
        { id: 'antuka', nazev: 'Přetlaková hala · antuka', kurty: 'kurty 5, 6', obdobi: '5. 10. 2026 – 4. 4. 2027', tydnu: 26,
          pasma: [['všední den 7:00–14:00', 540, 430, 12480, 10140, 480, 390], ['všední den 14:00–21:00', 730, 590, 17160, 13780, 660, 530], ['víkend 8:00–21:00', 450, 390, 10660, 8580, 410, 330]] },
        { id: 'tvrda', nazev: 'Přetlaková hala · tvrdý povrch', kurty: 'kurty C, 1, 2, 3, 4, 7, 8, 9', obdobi: '5. 10. 2026 – 4. 4. 2027', tydnu: 26,
          pasma: [['všední den 7:00–14:00', 730, 590, 17160, 13780, 660, 530], ['všední den 14:00–22:00', 860, 690, 20280, 16380, 780, 630], ['víkend 7:00–22:00', 600, 490, 14040, 11440, 540, 440]] },
        { id: 'pevna', nazev: 'Pevná hala Novasport', kurty: 'kurty P1, P2', obdobi: '28. 9. 2026 – 4. 4. 2027', tydnu: 27,
          pasma: [['všední den 7:00–22:00', 890, 800, 21870, 19710, 810, 730], ['víkend 7:00–22:00', 700, 630, 17010, 15120, 630, 560]] }
      ],
      leto: [
        { id: 'hlavni', nazev: 'Hlavní areál', kurty: 'kurty 1–9', cena: 500, clen: 0, svetla: true },
        { id: 'slavoj', nazev: 'Slavoj', kurty: 'kurty 10–16', cena: 400, clen: 0 },
        { id: 'pevna-leto', nazev: 'Pevná hala', kurty: 'kurty P1, P2', cena: 600, clen: 500 }
      ],
      svetla: 150
    }
  });

  /* ── Datum a čeština ──────────────────────────────────────────────────── */
  var DNY = ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'];
  var MESICE_GEN = ['ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince'];
  var MESICE = ['leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'];
  var MESICE_ZKR = ['led', 'úno', 'bře', 'dub', 'kvě', 'čvn', 'čvc', 'srp', 'zář', 'říj', 'lis', 'pro'];
  var NB = ' ';

  function zIso(s) { var p = String(s).split('-'); return new Date(+p[0], (+p[1] || 1) - 1, +p[2] || 1, 12, 0, 0); }
  function iso(d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }
  function denBez(d) { return new Date(d.getFullYear(), d.getMonth(), d.getDate(), 12, 0, 0); }
  function rozdilDni(a, b) { return Math.round((denBez(b) - denBez(a)) / 864e5); }
  function mezi(d, od, dO) { var x = iso(d); return x >= od && x <= dO; }

  /* Náhled jiného dne: ?den=2026-10-12 (pro předvedení zimní kůže) */
  var nahledDen = null;
  try {
    var q = new URLSearchParams(window.location.search).get('den');
    if (q && /^\d{4}-\d{2}-\d{2}$/.test(q)) nahledDen = q;
  } catch (e) { /* nic */ }

  function dnes() {
    var t = new Date();
    if (!nahledDen) return t;
    var d = zIso(nahledDen); d.setHours(t.getHours(), t.getMinutes(), 0, 0); return d;
  }

  CLTK.datum = {
    dnes: dnes,
    nahled: function () { return nahledDen; },
    zIso: zIso,
    iso: iso,
    rozdilDni: rozdilDni,
    dny: DNY, mesice: MESICE, mesiceGen: MESICE_GEN, mesiceZkr: MESICE_ZKR,
    dlouhy: function (d) { return DNY[d.getDay()] + ' ' + d.getDate() + '.' + NB + MESICE_GEN[d.getMonth()] + ' ' + d.getFullYear(); },
    bezDne: function (d) { return d.getDate() + '.' + NB + MESICE_GEN[d.getMonth()] + ' ' + d.getFullYear(); },
    kratky: function (d) { return d.getDate() + '.' + NB + (d.getMonth() + 1) + '.' + NB + d.getFullYear(); },
    denMesic: function (d) { return d.getDate() + '.' + NB + (d.getMonth() + 1) + '.'; },
    cas: function (d) {
      try { return new Intl.DateTimeFormat('cs-CZ', { hour: '2-digit', minute: '2-digit', timeZone: 'Europe/Prague' }).format(d); }
      catch (e) { return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2); }
    }
  };

  CLTK.sklonuj = function (n, tvary) { // tvary: ['den','dny','dní']
    var a = Math.abs(n);
    if (a === 1) return tvary[0];
    if (a >= 2 && a <= 4) return tvary[1];
    return tvary[2];
  };
  CLTK.zaDni = function (n) {
    if (n === 0) return 'dnes';
    if (n === 1) return 'zítra';
    if (n < 0) return 'před ' + (-n) + NB + CLTK.sklonuj(-n, ['dnem', 'dny', 'dny']);
    return 'za ' + n + NB + CLTK.sklonuj(n, ['den', 'dny', 'dní']);
  };
  CLTK.kc = function (n) {
    var s;
    try { s = new Intl.NumberFormat('cs-CZ').format(n); } catch (e) { s = String(n).replace(/\B(?=(\d{3})+(?!\d))/g, NB); }
    return s.replace(/\s/g, NB) + NB + 'Kč';
  };

  /* Sezóna webu: zima 28. 9. – 4. 4., jinak léto */
  CLTK.sezona = function (d) {
    d = d || dnes();
    var md = ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
    return (md >= '09-28' || md <= '04-04') ? 'zima' : 'leto';
  };

  /* Západ a východ slunce (algoritmus NOAA) */
  CLTK.slunce = function (d, lat, lon) {
    var rad = Math.PI / 180;
    var y = d.getFullYear(), m = d.getMonth(), den = d.getDate();
    var n = Math.round((Date.UTC(y, m, den) - Date.UTC(y, 0, 1)) / 864e5) + 1;
    var g = 2 * Math.PI / 365 * (n - 1);
    var eq = 229.18 * (0.000075 + 0.001868 * Math.cos(g) - 0.032077 * Math.sin(g) - 0.014615 * Math.cos(2 * g) - 0.040849 * Math.sin(2 * g));
    var dec = 0.006918 - 0.399912 * Math.cos(g) + 0.070257 * Math.sin(g) - 0.006758 * Math.cos(2 * g) + 0.000907 * Math.sin(2 * g) - 0.002697 * Math.cos(3 * g) + 0.00148 * Math.sin(3 * g);
    var ha = Math.acos(Math.cos(90.833 * rad) / (Math.cos(lat * rad) * Math.cos(dec)) - Math.tan(lat * rad) * Math.tan(dec)) / rad;
    var base = Date.UTC(y, m, den);
    return {
      vychod: new Date(base + (720 - 4 * (lon + ha) - eq) * 6e4),
      zapad: new Date(base + (720 - 4 * (lon - ha) - eq) * 6e4)
    };
  };

  /* ── Pohyb a kontrast ─────────────────────────────────────────────────── */
  function mq(q) { try { return window.matchMedia(q).matches; } catch (e) { return false; } }
  CLTK.pohybPovolen = function () {
    var p = root.getAttribute('data-pohyb');
    if (p === 'omezeny') return false;
    if (p === 'plny') return true;
    return !mq('(prefers-reduced-motion: reduce)');
  };

  /* Hlasatel pro čtečky */
  var hlasatel;
  CLTK.hlas = function (text) {
    if (!hlasatel) {
      hlasatel = doc.createElement('div');
      hlasatel.className = 'sr-only'; hlasatel.setAttribute('aria-live', 'polite'); hlasatel.id = 'cltk-hlaseni';
      doc.body.appendChild(hlasatel);
    }
    hlasatel.textContent = '';
    setTimeout(function () { hlasatel.textContent = text; }, 60);
  };

  function el(tag, attrs, html) {
    var e = doc.createElement(tag);
    if (attrs) for (var k in attrs) { if (attrs[k] !== null && attrs[k] !== undefined) e.setAttribute(k, attrs[k]); }
    if (html !== undefined) e.innerHTML = html;
    return e;
  }
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  /* ── Nastavení: sezóna, pohyb, kontrast ───────────────────────────────── */
  function nastaveni() {
    root.setAttribute('data-sezona', CLTK.sezona());
    var p = uloz.get('cltk-c-pohyb'); if (p === 'omezeny' || p === 'plny') root.setAttribute('data-pohyb', p);
    var k = uloz.get('cltk-c-kontrast');
    if (k === 'vysoky' || (k === null && mq('(prefers-contrast: more)'))) root.setAttribute('data-kontrast', 'vysoky');
    root.classList.toggle('pohyb-povolen', CLTK.pohybPovolen());

    $$('[data-prepinac-pohyb]').forEach(function (b) {
      b.setAttribute('aria-pressed', String(!CLTK.pohybPovolen()));
      b.addEventListener('click', function () {
        var omezit = b.getAttribute('aria-pressed') !== 'true';
        root.setAttribute('data-pohyb', omezit ? 'omezeny' : 'plny');
        uloz.set('cltk-c-pohyb', omezit ? 'omezeny' : 'plny');
        $$('[data-prepinac-pohyb]').forEach(function (x) { x.setAttribute('aria-pressed', String(omezit)); });
        root.classList.toggle('pohyb-povolen', CLTK.pohybPovolen());
        CLTK.hlas(omezit ? 'Pohyb na stránce je omezen.' : 'Pohyb na stránce je povolen.');
      });
    });
    $$('[data-prepinac-kontrast]').forEach(function (b) {
      b.setAttribute('aria-pressed', String(root.getAttribute('data-kontrast') === 'vysoky'));
      b.addEventListener('click', function () {
        var zap = b.getAttribute('aria-pressed') !== 'true';
        if (zap) root.setAttribute('data-kontrast', 'vysoky'); else root.removeAttribute('data-kontrast');
        uloz.set('cltk-c-kontrast', zap ? 'vysoky' : 'normalni');
        $$('[data-prepinac-kontrast]').forEach(function (x) { x.setAttribute('aria-pressed', String(zap)); });
        CLTK.hlas(zap ? 'Vyšší kontrast zapnut.' : 'Vyšší kontrast vypnut.');
      });
    });

    /* Zimní varianty obrázků: <img data-zima-src="…" data-zima-alt="…"> */
    if (root.getAttribute('data-sezona') === 'zima') {
      $$('img[data-zima-src]').forEach(function (i) {
        i.removeAttribute('srcset');
        i.src = i.getAttribute('data-zima-src');
        if (i.getAttribute('data-zima-alt')) i.alt = i.getAttribute('data-zima-alt');
        if (i.getAttribute('data-zima-pozice')) i.style.objectPosition = i.getAttribute('data-zima-pozice');
      });
      $$('[data-zima-text]').forEach(function (x) { x.textContent = x.getAttribute('data-zima-text'); });
    }

    /* Při náhledu jiného dne přenášet ?den= do interních odkazů */
    if (nahledDen) {
      $$('a[href]').forEach(function (a) {
        var h = a.getAttribute('href');
        if (/^[a-z0-9-]+\.html(#.*)?$/i.test(h)) {
          var c = h.split('#');
          a.setAttribute('href', c[0] + '?den=' + nahledDen + (c[1] ? '#' + c[1] : ''));
        }
      });
    }
  }

  /* ── Hlavička: stav po odrolování ─────────────────────────────────────── */
  function hlavicka() {
    var h = doc.querySelector('[data-hlavicka]');
    if (!h) return;
    var hranice = doc.querySelector('.dnes');
    function nastav() {
      var y = window.scrollY || window.pageYOffset;
      var mez = hranice ? hranice.offsetHeight + 4 : 8;
      h.classList.toggle('je-odrolovano', y > mez);
    }
    var ceka = false;
    window.addEventListener('scroll', function () {
      if (ceka) return; ceka = true;
      window.requestAnimationFrame(function () { nastav(); ceka = false; });
    }, { passive: true });
    nastav();
  }

  /* ── Mobilní menu (zámek rolování na <html>) ──────────────────────────── */
  function mobilniMenu() {
    var tl = doc.querySelector('.menu-tlacitko');
    var menu = tl && doc.getElementById(tl.getAttribute('aria-controls'));
    if (!tl || !menu) return;
    var h = doc.querySelector('[data-hlavicka]');
    var popis = tl.querySelector('.menu-tlacitko__text');
    function otevri() {
      var spodek = h ? Math.max(0, h.getBoundingClientRect().bottom) : 80;
      menu.style.setProperty('--menu-top', spodek + 'px');
      root.classList.add('menu-otevreno');
      menu.classList.add('je-otevreno');
      menu.removeAttribute('aria-hidden');
      tl.setAttribute('aria-expanded', 'true');
      if (popis) popis.textContent = 'Zavřít';
      var prvni = menu.querySelector('a, button');
      if (prvni) setTimeout(function () { prvni.focus({ preventScroll: true }); }, 30);
    }
    function zavri(vratit) {
      root.classList.remove('menu-otevreno');
      menu.classList.remove('je-otevreno');
      menu.setAttribute('aria-hidden', 'true');
      tl.setAttribute('aria-expanded', 'false');
      if (popis) popis.textContent = 'Menu';
      if (vratit) tl.focus({ preventScroll: true });
    }
    menu.setAttribute('aria-hidden', 'true');
    tl.addEventListener('click', function () { tl.getAttribute('aria-expanded') === 'true' ? zavri(true) : otevri(); });
    menu.addEventListener('click', function (e) { if (e.target.closest('a')) zavri(false); });
    doc.addEventListener('keydown', function (e) {
      if (tl.getAttribute('aria-expanded') !== 'true') return;
      if (e.key === 'Escape') { zavri(true); return; }
      if (e.key === 'Tab') {
        var f = [tl].concat($$('a[href], button:not([disabled])', menu));
        var i = f.indexOf(doc.activeElement);
        if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); }
        else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
      }
    });
    window.addEventListener('resize', function () { if (window.innerWidth >= 1100 && root.classList.contains('menu-otevreno')) zavri(false); });
  }

  /* ── CS / EN – EN zatím jen naznačená ─────────────────────────────────── */
  var oznameni;
  CLTK.oznam = function (nadpis, text, lang) {
    if (!oznameni) {
      oznameni = el('div', { class: 'oznameni', role: 'status', 'aria-live': 'polite' });
      oznameni.innerHTML = '<button type="button" class="oznameni__zavrit" aria-label="Zavřít oznámení">×</button><p class="oznameni__nadpis"></p><p class="oznameni__text"></p>';
      doc.body.appendChild(oznameni);
      oznameni.querySelector('button').addEventListener('click', function () { oznameni.classList.remove('je-videt'); });
      doc.addEventListener('keydown', function (e) { if (e.key === 'Escape') oznameni.classList.remove('je-videt'); });
    }
    oznameni.setAttribute('lang', lang || 'cs');
    oznameni.querySelector('.oznameni__nadpis').textContent = nadpis;
    oznameni.querySelector('.oznameni__text').innerHTML = text;
    oznameni.classList.add('je-videt');
  };
  function jazyk() {
    $$('[data-jazyk-en]').forEach(function (b) {
      b.addEventListener('click', function (e) {
        e.preventDefault();
        CLTK.oznam('English edition in preparation',
          'The English version of the new club website is being translated. Until then, our reception will gladly help: <a href="tel:+420608974974">+420 608 974 974</a> · <a href="mailto:recepce@cltk.cz">recepce@cltk.cz</a>.', 'en');
      });
    });
  }

  /* ── Reveal – obsah viditelný vždy, animace jen jako vylepšení ────────── */
  function reveal() {
    var prvky = $$('[data-reveal]');
    if (!prvky.length || !CLTK.pohybPovolen() || !('IntersectionObserver' in window) || root.classList.contains('snimek')) return;
    root.classList.add('reveal');
    var io = new IntersectionObserver(function (zaznamy) {
      zaznamy.forEach(function (z) { if (z.isIntersecting) { z.target.classList.add('je-videt'); io.unobserve(z.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.06 });
    prvky.forEach(function (p) {
      var r = p.getBoundingClientRect();
      if (r.top < window.innerHeight) p.classList.add('je-videt'); else io.observe(p);
    });
    setTimeout(function () { prvky.forEach(function (p) { p.classList.add('je-videt'); }); }, 4000);
  }

  /* ── DNES NA ŠTVANICI ─────────────────────────────────────────────────── */
  var WMO = { 0: 'jasno', 1: 'skoro jasno', 2: 'polojasno', 3: 'zataženo', 45: 'mlha', 48: 'mlha', 51: 'mrholení', 53: 'mrholení', 55: 'mrholení', 56: 'mrznoucí mrholení', 57: 'mrznoucí mrholení', 61: 'slabý déšť', 63: 'déšť', 65: 'silný déšť', 66: 'mrznoucí déšť', 67: 'mrznoucí déšť', 71: 'slabé sněžení', 73: 'sněžení', 75: 'silné sněžení', 77: 'sněhová zrna', 80: 'přeháňky', 81: 'přeháňky', 82: 'silné přeháňky', 85: 'sněhové přeháňky', 86: 'sněhové přeháňky', 95: 'bouřky', 96: 'bouřky s kroupami', 99: 'bouřky s kroupami' };

  CLTK.stavDne = function (d) {
    d = d || dnes();
    var S = DATA.sezona, x = iso(d), wd = d.getDay(), vsedni = wd > 0 && wd < 6, mesic = d.getMonth() + 1;
    var r = { hlavni: null, polozky: [], panel: [] };

    var uz = DATA.uzavirky.filter(function (u) { return x >= u[0] && x <= u[1]; })[0];
    var kurty, haly, typ = 'otevreno';
    if (uz) { r.hlavni = 'Uzavírka: ' + uz[2]; typ = 'zmena'; }
    if (mezi(d, S.stavbaHal[0], S.stavbaHal[1])) {
      kurty = 'Stavba zimních hal (21.–28.' + NB + '9.)';
      haly = x === S.pevnaHala[0] ? 'Pevná hala P1, P2 dnes zahajuje zimní sezónu; přetlakové haly od 5.' + NB + '10.' : 'Pevná hala P1, P2 od 28.' + NB + '9., přetlakové haly od 5.' + NB + '10.';
      typ = 'zmena';
    } else if (x >= S.pevnaHala[0] && x < S.pretlakove[0]) {
      kurty = 'Pevná hala P1, P2 v provozu';
      haly = 'Přetlakové haly od 5.' + NB + '10.' + NB + '2026';
      typ = 'zmena';
    } else if (x >= S.pretlakove[0] && x <= S.pretlakove[1]) {
      kurty = 'Zimní sezóna · 12 krytých kurtů';
      haly = 'Přetlakové haly i pevná hala v provozu do 4.' + NB + '4.' + NB + '2027; Slavoj 10–16 v zimě uzavřen';
      typ = 'hala';
    } else if (x > S.pretlakove[1]) {
      kurty = 'Letní sezóna – začátek určí management podle počasí';
      haly = 'Zimní sezóna 2026/27 skončila 4.' + NB + '4.' + NB + '2027';
    } else {
      kurty = 'Letní sezóna · venkovní dvorce denně 7:00–22:00';
      haly = 'Zimní sezóna od 28.' + NB + '9. (pevná hala) a 5.' + NB + '10. (přetlakové haly)';
    }
    if (!r.hlavni) r.hlavni = kurty;
    r.typ = typ;
    r.polozky.push({ text: r.hlavni, stav: typ, hlavni: true });

    var tsVolno = S.tsVolno.some(function (v) { return x >= v[0] && x <= v[1]; });
    var tsText = tsVolno ? 'Tenisová škola nehraje' : (mezi(d, S.tsZima[0], S.tsZima[1]) ? 'Tenisová škola trénuje podle zimních rozvrhů' : 'Tenisová škola – rozvrh doplní klub');
    if (tsVolno) r.polozky.push({ text: 'Tenisová škola nehraje', stav: 'zavreno' });
    if (x >= S.stavbaHal[0] && x <= S.stavbaHal[1] && x !== S.pevnaHala[0]) r.polozky.push({ text: 'Pevná hala od 28.' + NB + '9., přetlakové haly od 5.' + NB + '10.', stav: 'hala' });

    var wellness = vsedni ? 'Wellness dnes 16:00–20:00' : 'Wellness ve všední dny 16:00–20:00';
    r.polozky.push({ text: wellness, stav: vsedni ? 'otevreno' : 'zavreno' });
    var bazenSezona = mesic >= 5 && mesic <= 9;
    var bazen = bazenSezona ? 'Bazén: sezóna květen–září, podle počasí' : 'Bazén mimo sezónu (květen–září)';
    r.polozky.push({ text: 'Počasí se načítá', pocasi: true });
    var sl = CLTK.slunce(d, DATA.souradnice.lat, DATA.souradnice.lon);
    var venku = CLTK.sezona(d) === 'leto' || x < S.pretlakove[0];
    r.polozky.push({ text: 'Západ slunce ' + CLTK.datum.cas(sl.zapad) + (venku ? ' · světla jen na kurtech 2, 3, 4' : ''), zapad: true });

    var poradi = function (p) { return p.hlavni ? 0 : p.pocasi ? 1 : p.stav === 'hala' ? 2 : p.zapad ? 4 : /škola/.test(p.text) ? 3 : 5; };
    r.polozky.sort(function (a, b) { return poradi(a) - poradi(b); });

    r.panel = [
      { co: 'Kurty', stav: kurty, pozn: venku ? 'Konec letní i začátek zimní sezóny určuje management podle počasí.' : 'Rezervace online nebo na recepci ' + DATA.telefon + '.', tecka: typ },
      { co: 'Haly', stav: haly, tecka: 'hala' },
      { co: 'Tenisová škola', stav: tsText, pozn: 'Volno: 21.–28.' + NB + '9., 26.–30.' + NB + '10., 17.' + NB + '11., 23.' + NB + '12.' + NB + '2026 – 3.' + NB + '1.' + NB + '2027', tecka: tsVolno ? 'zavreno' : 'otevreno' },
      { co: 'Wellness', stav: vsedni ? 'Dnes 16:00–20:00, bez rezervace' : 'Ve všední dny 16:00–20:00', pozn: 'Jen pro členy; rekreační členové max. 2× týdně.', tecka: vsedni ? 'otevreno' : 'zavreno' },
      { co: 'Bazén', stav: bazen, pozn: 'Členové zdarma, host člena 400' + NB + 'Kč/den.', tecka: bazenSezona ? 'otevreno' : 'zavreno' },
      { co: 'Tenis shop', stav: vsedni ? 'Dnes 9:00–12:00 a 13:00–17:00' : 'Po–Pá 9:00–12:00 a 13:00–17:00', pozn: 'Vyplétání raket, sleva pro členy.', tecka: vsedni ? 'otevreno' : 'zavreno' },
      { co: 'Kancelář klubu', stav: vsedni ? 'Dnes 9:00–17:00' : 'Ve všední dny 9:00–17:00', pozn: 'Stálé rezervace a platby členství.', tecka: vsedni ? 'otevreno' : 'zavreno' },
      { co: 'Recepce', stav: DATA.telefon, pozn: 'Otevírací dobu recepce doplní klub.', doplni: true },
      { co: 'Restaurace Tiebreak', stav: 'Terasa a salónek s dětským koutkem', pozn: 'Otevírací dobu doplní klub.', doplni: true },
      { co: 'Uzavírky', stav: uz ? uz[2] : 'Žádná další uzavírka v roce 2026 není ohlášena', pozn: 'Podle stránky Plánované uzavírky, stav k 23.' + NB + '9.' + NB + '2026.' },
      { co: 'Počasí', stav: 'Načítá se…', pocasi: true, pozn: 'Open-Meteo, Štvanice' },
      { co: 'Slunce', stav: 'Východ ' + CLTK.datum.cas(sl.vychod) + ' · západ ' + CLTK.datum.cas(sl.zapad), pozn: venku ? 'Večerní svícení jen na kurtech 2, 3, 4 (150' + NB + 'Kč/h).' : 'V halách se hraje do 21:00 nebo 22:00 podle haly.' }
    ];
    return r;
  };

  function dnesNaStvanici() {
    var box = doc.querySelector('[data-dnes]');
    if (!box) return;
    var d = dnes(), stav = CLTK.stavDne(d);
    var datum = box.querySelector('[data-dnes-datum]');
    if (datum) { datum.textContent = CLTK.datum.dlouhy(d); datum.setAttribute('datetime', iso(d)); }
    var seznam = box.querySelector('[data-dnes-polozky]');
    if (seznam) {
      seznam.innerHTML = stav.polozky.map(function (p) {
        var t = p.stav ? '<span class="stav' + (p.stav === 'zavreno' ? ' stav--zavreno' : p.stav === 'zmena' ? ' stav--zmena' : p.stav === 'hala' ? ' stav--hala' : '') + '" aria-hidden="true"></span>' : '';
        return '<li class="dnes__polozka' + (p.hlavni ? ' dnes__polozka--hlavni' : '') + '"' + (p.pocasi ? ' data-dnes-pocasi hidden' : '') + '>' + t + '<span>' + esc(p.text) + '</span></li>';
      }).join('');
    }
    var panel = box.querySelector('[data-dnes-panel-seznam]');
    if (panel) {
      panel.innerHTML = stav.panel.map(function (p) {
        var tecka = p.tecka ? '<span class="stav' + (p.tecka === 'zavreno' ? ' stav--zavreno' : p.tecka === 'zmena' ? ' stav--zmena' : p.tecka === 'hala' ? ' stav--hala' : '') + '" aria-hidden="true"></span>' : '';
        return '<div class="concierge__polozka"' + (p.pocasi ? ' data-dnes-pocasi-panel' : '') + '><dt class="concierge__co">' + tecka + esc(p.co) + '</dt><dd class="concierge__stav">' + esc(p.stav) +
          (p.doplni ? ' <span class="pozn pozn--doplni">doplní klub</span>' : '') + (p.pozn ? '<span class="concierge__pozn" style="display:block">' + esc(p.pozn) + '</span>' : '') + '</dd></div>';
      }).join('');
    }
    var nahled = box.querySelector('[data-nahled-zimy]');
    if (nahled) {
      var soubor = (window.location.pathname.split('/').pop() || 'index.html');
      if (nahledDen) { nahled.textContent = 'Zpět na dnešní den'; nahled.setAttribute('href', soubor); }
      else { nahled.setAttribute('href', soubor + '?den=2026-10-12'); }
    }
    var info = box.querySelector('[data-dnes-nahled-info]');
    if (info && nahledDen) { info.hidden = false; info.textContent = 'Náhled dne ' + CLTK.datum.kratky(d); }

    /* rozbalení panelu */
    var tl = box.querySelector('[data-dnes-vice]');
    var pn = tl && doc.getElementById(tl.getAttribute('aria-controls'));
    if (tl && pn) {
      tl.addEventListener('click', function () {
        var ot = tl.getAttribute('aria-expanded') === 'true';
        tl.setAttribute('aria-expanded', String(!ot));
        pn.hidden = ot;
        var tx = tl.querySelector('.dnes__vice-text'); if (tx) tx.textContent = ot ? 'Celý den' : 'Skrýt';
      });
    }
    pocasi();
  }

  function pocasi() {
    var polozky = $$('[data-dnes-pocasi]'), panely = $$('[data-dnes-pocasi-panel] .concierge__stav');
    if (!polozky.length && !panely.length) return;
    function vypis(t, kod) {
      var text = Math.round(t) + NB + '°C, ' + (WMO[kod] || 'počasí');
      polozky.forEach(function (p) { p.hidden = false; p.lastChild.textContent = text; });
      panely.forEach(function (p) { p.firstChild.textContent = text + ' '; });
    }
    function chyba() {
      polozky.forEach(function (p) { p.hidden = true; });
      panely.forEach(function (p) { p.firstChild.textContent = 'Počasí se teď nepodařilo načíst. '; });
    }
    try {
      var c = JSON.parse(uloz.get('cltk-c-pocasi') || 'null');
      if (c && Date.now() - c.cas < 30 * 6e4) { vypis(c.t, c.k); return; }
    } catch (e) { /* nic */ }
    if (!window.fetch || (navigator.onLine === false)) { chyba(); return; }
    var ctl = window.AbortController ? new AbortController() : null;
    var casovac = setTimeout(function () { if (ctl) ctl.abort(); }, 5000);
    var url = 'https://api.open-meteo.com/v1/forecast?latitude=' + DATA.souradnice.lat + '&longitude=' + DATA.souradnice.lon + '&current=temperature_2m,weather_code&timezone=Europe%2FPrague';
    fetch(url, ctl ? { signal: ctl.signal } : {}).then(function (r) { if (!r.ok) throw new Error('http'); return r.json(); })
      .then(function (j) {
        clearTimeout(casovac);
        var t = j && j.current && j.current.temperature_2m, k = j && j.current && j.current.weather_code;
        if (typeof t !== 'number') throw new Error('data');
        vypis(t, k);
        uloz.set('cltk-c-pocasi', JSON.stringify({ cas: Date.now(), t: t, k: k }));
      })['catch'](function () { clearTimeout(casovac); chyba(); });
  }

  /* ── MAPA AREÁLU LÉTO / ZIMA ──────────────────────────────────────────── */
  /* Souřadnice: plán areálu 09-2025 (plan-arealu.jpg) v jednotkách 2000 × 1178; výřez y 120–1100 */
  var VYREZ = { y0: 120, v: 980 };
  function bodY(yPct) { return ((yPct / 100 * 1178) - VYREZ.y0) / VYREZ.v * 100; }

  var KURTY = [
    { id: '1', x: 50.7, y: 37, leto: 'antuka', zima: 'hard', nazev: 'Kurt 1', pod: '„malý centr“ s tribunou', l: 'antuka, kapacita cca 1000 míst', z: 'přetlaková hala, tvrdý povrch', cl: '500 Kč/h · členové zdarma', cz: '600–860 Kč/h · člen 490–690 Kč/h', hala: 'tvrda' },
    { id: '2', x: 71.6, y: 53, leto: 'antuka', zima: 'hard', nazev: 'Kurt 2', pod: 'trojkurt 2, 3, 4', l: 'antuka, malé tribuny, mírně pod úrovní terénu; večerní svícení 150 Kč/h', z: 'přetlaková trojhala, tvrdý povrch (MIBOsport, 2024)', cl: '500 Kč/h · členové zdarma', cz: '600–860 Kč/h · člen 490–690 Kč/h', hala: 'tvrda' },
    { id: '3', x: 75.4, y: 53, leto: 'antuka', zima: 'hard', nazev: 'Kurt 3', pod: 'trojkurt 2, 3, 4', l: 'antuka, malé tribuny, mírně pod úrovní terénu; večerní svícení 150 Kč/h', z: 'přetlaková trojhala, tvrdý povrch (MIBOsport, 2024)', cl: '500 Kč/h · členové zdarma', cz: '600–860 Kč/h · člen 490–690 Kč/h', hala: 'tvrda' },
    { id: '4', x: 79.3, y: 53, leto: 'antuka', zima: 'hard', nazev: 'Kurt 4', pod: 'trojkurt 2, 3, 4', l: 'antuka, malé tribuny, mírně pod úrovní terénu; večerní svícení 150 Kč/h', z: 'přetlaková trojhala, tvrdý povrch (MIBOsport, 2024)', cl: '500 Kč/h · členové zdarma', cz: '600–860 Kč/h · člen 490–690 Kč/h', hala: 'tvrda' },
    { id: '5', x: 68.3, y: 35, leto: 'antuka', zima: 'antuka', nazev: 'Kurt 5', pod: 'jen pro členy', l: 'antuka; jen pro členy (člen + člen / člen + host)', z: 'přetlaková dvojhala s antukou', cl: 'jen pro členy · host člena 100 Kč/h', cz: '450–730 Kč/h · člen 390–590 Kč/h', hala: 'antuka' },
    { id: '6', x: 72.5, y: 35, leto: 'antuka', zima: 'antuka', nazev: 'Kurt 6', pod: 'dvojice 5, 6', l: 'antuka', z: 'přetlaková dvojhala s antukou', cl: '500 Kč/h · členové zdarma', cz: '450–730 Kč/h · člen 390–590 Kč/h', hala: 'antuka' },
    { id: '7', x: 77.4, y: 35, leto: 'hard', zima: 'hard', nazev: 'Kurt 7', pod: 'dvojice 7, 8', l: 'tvrdý povrch – nový povrch a nafukovací hala 2024 za více než 14 mil. Kč (Revue 02/2024)', z: 'přetlaková dvojhala, tvrdý povrch', cl: '500 Kč/h · členové zdarma', cz: '600–860 Kč/h · člen 490–690 Kč/h', hala: 'tvrda' },
    { id: '8', x: 81.4, y: 35, leto: 'hard', zima: 'hard', nazev: 'Kurt 8', pod: 'dvojice 7, 8', l: 'tvrdý povrch – nový povrch a nafukovací hala 2024 za více než 14 mil. Kč (Revue 02/2024)', z: 'přetlaková dvojhala, tvrdý povrch', cl: '500 Kč/h · členové zdarma', cz: '600–860 Kč/h · člen 490–690 Kč/h', hala: 'tvrda' },
    { id: '9', x: 86.4, y: 35, leto: 'hard', zima: 'hard', nazev: 'Kurt 9', pod: 'nový hard Novasport 2025', l: 'tvrdý povrch (Novasport, 2025)', z: 'přetlaková hala, tvrdý povrch', cl: '500 Kč/h · členové zdarma', cz: '600–860 Kč/h · člen 490–690 Kč/h', hala: 'tvrda' },
    { id: 'C', x: 55.1, y: 73.3, leto: 'hard', zima: 'hard', nazev: 'Centrální dvorec', pod: 'štvanický stadion · patří Českému tenisovému svazu', l: 'tvrdý povrch; web klubu uvádí 8 000 míst (ÚDU AV ČR: 7 000 sedících)', z: 'přetlaková hala', cl: 'doplní klub', cz: '600–860 Kč/h · člen 490–690 Kč/h', hala: 'tvrda' },
    { id: 'P1', x: 47.6, y: 68.7, leto: 'hard', zima: 'hard', nazev: 'Kurt P1', pod: 'pevná hala Novasport', l: 'tvrdý povrch v pevné hale pod ochozem velkého centru', z: 'celoročně hala', cl: '600 Kč/h · člen 500 Kč/h', cz: '700–890 Kč/h · člen 630–800 Kč/h', hala: 'pevna' },
    { id: 'P2', x: 47.6, y: 74.5, leto: 'hard', zima: 'hard', nazev: 'Kurt P2', pod: 'pevná hala Novasport', l: 'tvrdý povrch v pevné hale pod ochozem velkého centru', z: 'celoročně hala', cl: '600 Kč/h · člen 500 Kč/h', cz: '700–890 Kč/h · člen 630–800 Kč/h', hala: 'pevna' }
  ];
  [['10', 31.8], ['11', 26.8], ['12', 22.5], ['13', 17.4], ['14', 13.1], ['15', 8.1], ['16', 3.6]].forEach(function (k) {
    KURTY.push({ id: k[0], x: k[1], y: 34, leto: 'antuka', zima: 'zavreno', slavoj: true, nazev: 'Kurt ' + k[0], pod: 'Slavoj, za Negrelliho viaduktem', l: 'antuka', z: 'v zimě uzavřeno', cl: '400 Kč/h · členové zdarma', cz: 'v zimě uzavřeno', hala: null });
  });
  var SLUZBY = [
    { id: 'recepce', x: 53.1, y: 48.6, nazev: 'Recepce', pod: 'rezervace kurtů', l: 'Rezervace: ' + DATA.telefon + ' · recepce@cltk.cz', z: 'Otevírací dobu recepce doplní klub.', doplni: true },
    { id: 'bazen', x: 62.5, y: 35, nazev: 'Venkovní bazén', pod: 'v areálu již více než 20 let', l: 'Provoz přibližně květen–září (podle počasí). Trávník, polohovatelná lehátka.', z: 'Jen pro členy (zdarma) a jejich hosty (400 Kč/den).' },
    { id: 'fitness', x: 49.2, y: 25.5, nazev: 'Fitness', pod: '2. NP, od roku 2017', l: 'Pro závodní i rekreační hráče; otevírací doba = doba budovy.', z: 'Členové zdarma, host člena 400 Kč.' },
    { id: 'wellness', x: 52.8, y: 25.5, nazev: 'Regenerace a wellness', pod: '2. NP, od března 2019', l: 'Vířivka, sauna s odpočívárnou, infrasauna, Kneippovy lázně.', z: 'Všední dny 16:00–20:00, jen pro členy; rekreační členové max. 2× týdně.' },
    { id: 'lekar', x: 56.6, y: 28.8, nazev: 'Fyzioterapie a sportovní lékařství', pod: 've wellness centru', l: 'Fyzioterapie Bc. Joseph B. Truesdale, sportovní lékařství Zdravý sport.', z: 'Pro závodní hráče, členy i veřejnost.' },
    { id: 'shop', x: 53.3, y: 59.2, nazev: 'Tenis shop', pod: 've vestibulu od listopadu 2023', l: 'Vyplétání raket, Mizuno, Babolat a klubový merchandising.', z: 'Po–Pá 9:00–12:00 a 13:00–17:00 · sleva pro členy.' },
    { id: 'salonek', x: 56.9, y: 59.2, nazev: 'Salónek a dětský koutek', pod: 'u restaurace', l: 'Salónek s dětským koutkem, dětské hřiště u terasy.', z: 'Rauty a firemní akce na dotaz.' },
    { id: 'restaurace', x: 60.4, y: 59.2, nazev: 'Restaurace Tiebreak', pod: 's terasou', l: 'Nová terasa podle ateliéru Adama Fröhlicha, 60 míst (podle klubu).', z: 'Otevírací dobu a kontakt doplní klub.', doplni: true },
    { id: 'parkoviste', x: 39.2, y: 54.2, nazev: 'Parkoviště', pod: 'u Negrelliho viaduktu, zadní vchod', l: 'Pod oblouky viaduktu 4 oblouky po 5 místech (podle klubu, Revue 02/2025).', z: 'Celkovou kapacitu doplní klub.', doplni: true },
    { id: 'beach', x: 30, y: 48.2, nazev: 'Beach volejbal', pod: 'Slavoj', l: 'Pronájem 400 Kč/h.', z: 'Hrají-li jen členové, zdarma; s hosty o 25 % levněji.' },
    { id: 'multi', x: 20.7, y: 48.2, nazev: 'Multifunkční hřiště', pod: 'Slavoj', l: 'Pronájem 900 Kč/h; které ze dvou hřišť se pronajímá, doplní klub.', z: 'Hrají-li jen členové, zdarma; s hosty o 25 % levněji.' },
    { id: 'multi2', x: 74.9, y: 63.1, nazev: 'Multifunkční hřiště', pod: 'u trojkurtu', l: 'Na plánu jsou dvě multifunkční hřiště; které se pronajímá, doplní klub.', z: 'Odrazová stěna hned vedle.' }
  ];

  function lajnyKurtu(cx, cy, w, h, vodorovne) {
    if (vodorovne) { var t = w; w = h; h = t; }
    var x = cx - w / 2, y = cy - h / 2, i = w * 0.125, s = h * 0.269;
    var d = 'M' + x + ' ' + y + 'h' + w + 'v' + h + 'h' + (-w) + 'z' +
      'M' + (x + i) + ' ' + y + 'v' + h + 'M' + (x + w - i) + ' ' + y + 'v' + h +
      'M' + (x + i) + ' ' + (cy - s) + 'h' + (w - 2 * i) + 'M' + (x + i) + ' ' + (cy + s) + 'h' + (w - 2 * i) +
      'M' + cx + ' ' + (cy - s) + 'v' + (2 * s);
    var sit = '<path class="m-lajny m-lajny--tenke" d="M' + (x - 4) + ' ' + cy + 'h' + (w + 8) + '" stroke-dasharray="3 2"/>';
    if (vodorovne) {
      // otočit o 90° kolem středu
      return '<g transform="rotate(90 ' + cx + ' ' + cy + ')"><path class="m-lajny" d="' + d + '"/>' + sit + '</g>';
    }
    return '<path class="m-lajny" d="' + d + '"/>' + sit;
  }

  function svgMapy() {
    var s = '';
    s += '<svg class="mapa__svg" viewBox="0 120 2000 980" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">';
    s += '<defs><pattern id="m-prosiv" width="26" height="26" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0H26M0 0V26" stroke="#e2d9cc" stroke-width="1.6" fill="none"/></pattern>' +
      '<pattern id="m-rady" width="8" height="8" patternUnits="userSpaceOnUse"><path d="M0 4H8" stroke="#c6b8a3" stroke-width="1.4"/></pattern>' +
      '<pattern id="m-rady-v" width="8" height="8" patternUnits="userSpaceOnUse"><path d="M4 0V8" stroke="#c6b8a3" stroke-width="1.4"/></pattern></defs>';
    // voda a vlnky
    s += '<rect class="m-voda" x="0" y="120" width="2000" height="980"/>';
    [[150, 138], [520, 132], [980, 130], [1480, 136], [260, 1086], [760, 1090], [1300, 1088], [1760, 1084]].forEach(function (v) {
      s += '<path class="m-vlnka" d="M' + v[0] + ' ' + v[1] + 'q14 -7 28 0t28 0t28 0"/>';
    });
    s += '<text class="m-popis m-popis--velky" x="1270" y="152">Vltava</text>';
    // ostrov
    s += '<path class="m-zem" d="M0 196 C 240 150 520 142 700 150 L 840 146 C 1200 126 1620 140 2000 162 L 2000 1062 C 1600 1078 1150 1082 840 1074 L 700 1070 C 420 1078 180 1064 0 1046 Z"/>';
    // parky a zeleň
    s += '<path class="m-park" d="M0 700 L 240 700 L 330 650 L 695 650 L 695 1068 C 420 1076 180 1062 0 1044 Z"/>';
    s += '<path class="m-park" d="M1500 830 L 1880 650 L 2000 640 L 2000 1060 C 1750 1072 1400 1078 1180 1080 L 1300 1000 Z"/>';
    // cesty a zpevněné plochy
    s += '<path class="m-cesta" d="M5 262 L 695 232 L 695 648 L 330 648 L 238 690 L 5 690 Z"/>';
    s += '<path class="m-cesta" d="M838 222 L 1790 282 L 1848 330 L 1874 640 L 1470 836 L 1292 906 L 1182 990 L 838 990 Z"/>';
    s += '<path class="m-cesta" d="M757 120 H835 V1100 H757 Z"/>';
    s += '<path class="m-cesta" d="M838 990 L 1182 990 L 1300 1006 L 2000 1014 L 2000 1040 L 838 1040 Z" opacity=".7"/>';
    // hlavní budova
    s += '<path class="m-budova" d="M840 226 L 1174 250 L 1174 520 L 1150 520 L 1150 642 L 1288 702 L 1288 904 L 1180 986 L 840 986 L 840 722 L 896 662 L 840 604 Z"/>';
    // viadukt
    s += '<rect class="m-viadukt" x="697" y="120" width="60" height="980"/>';
    s += '<path class="m-kolej" d="M712 120V1100M742 120V1100"/>';
    var pr = ''; for (var yy = 130; yy < 1100; yy += 22) pr += 'M706 ' + yy + 'h42';
    s += '<path class="m-prazec" d="' + pr + '" opacity=".55"/>';
    s += '<text class="m-popis" transform="translate(735 1000) rotate(-90)" style="font-size:15px">Negrelliho viadukt</text>';
    // parkování
    var pk = ''; for (var py = 236; py < 566; py += 30) pk += 'M806 ' + py + 'h26';
    s += '<path class="m-parkovani" d="' + pk + '"/>';
    // stromy
    [[160, 180, 50], [318, 172, 52], [572, 172, 48], [36, 780, 62], [462, 780, 70], [655, 745, 42], [62, 905, 40], [196, 1020, 40], [408, 1040, 38], [572, 965, 40], [1932, 402, 56], [1978, 604, 38], [1618, 848, 40], [1750, 778, 40], [1860, 882, 40], [1960, 800, 30], [1110, 1060, 30], [1400, 1052, 30], [1522, 1048, 32], [1718, 1040, 30]].forEach(function (t) {
      s += '<circle class="m-strom" cx="' + t[0] + '" cy="' + t[1] + '" r="' + t[2] + '"/>';
    });
    // Slavoj (10–16)
    s += '<g class="m-slavoj">';
    [[25, 318, 182, 170], [213, 318, 182, 170], [401, 318, 182, 170], [590, 318, 95, 170]].forEach(function (b) {
      s += '<rect class="m-plocha-antuka" x="' + b[0] + '" y="' + b[1] + '" width="' + b[2] + '" height="' + b[3] + '"/>';
    });
    [72, 162, 262, 348, 450, 536, 636].forEach(function (cx) { s += lajnyKurtu(cx, 403, 58, 128); });
    s += '</g>';
    s += '<text class="m-popis" x="600" y="296">Slavoj</text>';
    s += '<text class="m-popis--zima" x="354" y="302" text-anchor="middle">V zimě uzavřeno</text>';
    // multifunkční a beach na Slavoji
    s += '<rect class="m-plocha-trava" x="330" y="520" width="168" height="95"/><path class="m-lajny m-lajny--tenke" d="M340 530h148v75h-148zM414 530v75"/><circle class="m-lajny m-lajny--tenke" cx="414" cy="567" r="18"/>';
    s += '<rect class="m-plocha-pisek" x="515" y="520" width="170" height="95"/><path class="m-lajny m-lajny--tenke" d="M525 530h150v75h-150zM600 530v75"/>';
    // kurt 1 s tribunami
    s += '<rect class="m-tribuna" x="937" y="345" width="31" height="183"/><rect x="937" y="345" width="31" height="183" fill="url(#m-rady-v)"/>';
    s += '<rect class="m-tribuna" x="1058" y="345" width="31" height="183"/><rect x="1058" y="345" width="31" height="183" fill="url(#m-rady-v)"/>';
    s += '<g data-zima-povrch="hard"><rect class="m-povrch m-plocha-antuka" x="968" y="345" width="90" height="183"/>' + lajnyKurtu(1013, 437, 56, 124) + '</g>';
    // bazén
    s += '<g class="m-bazen"><rect class="m-plocha-trava" x="1183" y="328" width="130" height="169"/><rect class="m-bazen-voda" x="1226" y="366" width="48" height="92" rx="3"/></g>';
    s += '<text class="m-popis" x="1248" y="352" text-anchor="middle" style="font-size:14px">Bazén</text>';
    // 5, 6 antuka
    s += '<rect class="m-povrch m-plocha-antuka" x="1322" y="328" width="171" height="169"/>' + lajnyKurtu(1366, 412, 56, 122) + lajnyKurtu(1450, 412, 56, 122);
    // 7, 8, 9 tvrdé
    s += '<rect class="m-plocha-hard" x="1502" y="328" width="171" height="169"/>' + lajnyKurtu(1548, 412, 56, 122) + lajnyKurtu(1628, 412, 56, 122);
    s += '<rect class="m-plocha-hard" x="1681" y="328" width="96" height="169"/>' + lajnyKurtu(1728, 412, 56, 122);
    // trojkurt 2, 3, 4
    s += '<rect class="m-tribuna" x="1352" y="556" width="31" height="154"/><rect x="1352" y="556" width="31" height="154" fill="url(#m-rady-v)"/>';
    s += '<rect class="m-tribuna" x="1638" y="556" width="31" height="154"/><rect x="1638" y="556" width="31" height="154" fill="url(#m-rady-v)"/>';
    s += '<g data-zima-povrch="hard"><rect class="m-povrch m-plocha-antuka" x="1383" y="556" width="255" height="154"/>' + lajnyKurtu(1432, 633, 56, 122) + lajnyKurtu(1508, 633, 56, 122) + lajnyKurtu(1586, 633, 56, 122) + '</g>';
    // odrazová stěna a malé multifunkční
    s += '<rect class="m-plocha-hard" x="1390" y="716" width="53" height="94"/><path class="m-lajny m-lajny--tenke" d="M1398 724h37v78h-37z"/>';
    s += '<rect class="m-plocha-trava" x="1449" y="716" width="96" height="54"/><path class="m-lajny m-lajny--tenke" d="M1456 722h82v42h-82zM1497 722v42"/>';
    s += '<text class="m-popis" x="1416" y="836" text-anchor="middle" style="font-size:12px">Odrazová stěna</text>';
    // pevná hala P1, P2
    s += '<rect class="m-plocha-hard" x="843" y="740" width="179" height="200"/>' + lajnyKurtu(952, 809, 56, 122, true) + lajnyKurtu(952, 878, 56, 122, true);
    s += '<rect class="m-pevna-strecha" x="848" y="745" width="169" height="190" rx="4"/>';
    s += '<text class="m-popis" x="858" y="764" style="font-size:12px">Pevná hala</text>';
    // centrální dvorec C
    s += '<rect class="m-tribuna" x="1023" y="740" width="155" height="245"/><rect x="1023" y="740" width="155" height="245" fill="url(#m-rady)" opacity=".8"/>';
    s += '<rect class="m-plocha-hard" x="1057" y="770" width="88" height="180"/>' + lajnyKurtu(1101, 862, 56, 124);
    s += '<text class="m-popis" x="1234" y="880" text-anchor="middle" style="font-size:12px">Kanceláře ČTS</text>';
    // vstupy
    [[843, 640, 0], [1215, 590, 180], [1845, 535, 190], [1487, 830, 205]].forEach(function (v) {
      s += '<path class="m-vstup" transform="translate(' + v[0] + ' ' + v[1] + ') rotate(' + v[2] + ')" d="M0 -12 L 16 0 L 0 12 Z"/>';
    });
    s += '<text class="m-popis" x="1236" y="578" style="font-size:12px">Vstup k recepci</text>';
    // haly (zima)
    [[962, 340, 102, 193, 'tvrda'], [1378, 551, 265, 164, 'tvrda'], [1317, 323, 181, 179, 'antuka'], [1497, 323, 181, 179, 'tvrda'], [1676, 323, 106, 179, 'tvrda'], [1052, 765, 98, 190, 'tvrda']].forEach(function (h) {
      var cls = 'm-hala' + (h[4] === 'antuka' ? ' m-hala--antuka' : '');
      s += '<g class="' + cls + '"><rect class="m-hala__plast" x="' + h[0] + '" y="' + h[1] + '" width="' + h[2] + '" height="' + h[3] + '" rx="' + Math.min(38, h[2] / 3) + '"/>' +
        '<rect class="m-hala__vzor" x="' + h[0] + '" y="' + h[1] + '" width="' + h[2] + '" height="' + h[3] + '" rx="' + Math.min(38, h[2] / 3) + '"/></g>';
    });
    s += '</svg>';
    return s;
  }

  function mapa(box) {
    var rezim = box.getAttribute('data-mapa-rezim') || CLTK.sezona();
    var cenik = box.getAttribute('data-mapa-cenik') || '#cenik';
    var fotoSel = box.getAttribute('data-mapa-foto');
    var souhrnSel = box.getAttribute('data-mapa-souhrn');
    var zaloha = box.querySelector('.mapa__zaloha');
    if (zaloha) zaloha.hidden = true;
    var uid = 'mapa' + Math.random().toString(36).slice(2, 7);

    var lista = el('div', { class: 'mapa__listy' });
    lista.innerHTML =
      '<div class="volba" role="radiogroup" aria-label="Sezóna na plánu">' +
      '<input type="radio" id="' + uid + '-l" name="' + uid + '-s" value="leto"><label for="' + uid + '-l">Léto</label>' +
      '<input type="radio" id="' + uid + '-z" name="' + uid + '-s" value="zima"><label for="' + uid + '-z">Zima</label></div>' +
      '<div class="mapa__vrstvy" role="group" aria-label="Zobrazit na plánu">' +
      '<label class="zatrzitko"><input type="checkbox" checked data-vrstva="kurty"><span>Kurty</span></label>' +
      '<label class="zatrzitko"><input type="checkbox" checked data-vrstva="sluzby"><span>Služby</span></label>' +
      '<span class="sede"><span class="stav" style="background:var(--antuka-mapa)"></span> antuka &nbsp; <span class="stav" style="background:var(--hard-mapa)"></span> tvrdý povrch &nbsp; <span class="stav" style="background:#fbfaf7;box-shadow:inset 0 0 0 1px #cdbfab"></span> hala</span></div>';

    var posun = el('p', { class: 'mapa__posun' }, 'Plán lze posouvat do strany →');
    var okno = el('div', { class: 'mapa__okno' });
    var platno = el('div', { class: 'mapa__platno' });
    platno.innerHTML = svgMapy();
    var body = el('div', { class: 'mapa__body' });
    KURTY.forEach(function (k) {
      var b = el('button', { type: 'button', class: 'mapa__bod mapa__bod--kurt', 'data-id': k.id, 'aria-pressed': 'false', style: '--x:' + k.x + ';--y:' + bodY(k.y).toFixed(2) },
        esc(k.id));
      b.setAttribute('aria-label', k.nazev + ', ' + k.pod);
      body.appendChild(b);
    });
    SLUZBY.forEach(function (sl) {
      var b = el('button', { type: 'button', class: 'mapa__bod mapa__bod--sluzba', 'data-id': sl.id, 'aria-pressed': 'false', style: '--x:' + sl.x + ';--y:' + bodY(sl.y).toFixed(2), title: sl.nazev });
      b.setAttribute('aria-label', sl.nazev + ', ' + sl.pod);
      body.appendChild(b);
    });
    platno.appendChild(body);
    okno.appendChild(platno);

    var karta = el('div', { class: 'mapa__karta', 'aria-live': 'polite' });
    var sluzbyRadek = el('div', { class: 'mapa__sluzby' });
    sluzbyRadek.innerHTML = '<span class="stitek">Služby v areálu</span>' + SLUZBY.map(function (sl) {
      return '<button type="button" class="cip" data-id="' + sl.id + '" aria-pressed="false">' + esc(sl.nazev) + (sl.id === 'multi2' ? ' (u trojkurtu)' : sl.id === 'multi' ? ' (Slavoj)' : '') + '</button>';
    }).join('');

    box.appendChild(lista); box.appendChild(posun); box.appendChild(okno); box.appendChild(karta); box.appendChild(sluzbyRadek);

    var foto = fotoSel ? doc.querySelector(fotoSel) : null;
    var souhrn = souhrnSel ? doc.querySelector(souhrnSel) : null;
    var vybrano = null;

    function vychoziKarta() {
      var z = rezim === 'zima';
      karta.innerHTML =
        '<div class="mapa__karta-hlava"><span class="kurt-cislo kurt-cislo--plne kurt-cislo--velke" aria-hidden="true">' + (z ? '12' : '19') + '</span><div>' +
        '<p class="mapa__karta-nazev">' + (z ? 'Zima: 12 krytých kurtů' : 'Léto: 19 kurtů na ostrově') + '</p>' +
        '<p class="mapa__karta-pod">Ťukněte na kurt nebo službu. Plán podle klubu, verze 09-2025.</p></div></div>' +
        '<div class="mapa__karta-sloupec' + (!z ? ' je-aktivni-sezona' : '') + '"><span class="stitek">Léto</span><span>13 antukových kurtů (3 s osvětlením), 3 tvrdé, 2 v pevné hale a centrální dvorec</span></div>' +
        '<div class="mapa__karta-sloupec' + (z ? ' je-aktivni-sezona' : '') + '"><span class="stitek">Zima</span><span>10 tvrdých a 2 antukové pod halami; Slavoj 10–16 uzavřen</span></div>' +
        '<div class="mapa__karta-akce"><a class="tlacitko tlacitko--akcent tlacitko--male" href="' + DATA.rezervace + '" rel="noopener">Rezervovat kurt</a><a class="odkaz-sipka mensi" href="' + cenik + '">Ceník a kalkulačka</a></div>';
    }
    function kartaKurtu(k) {
      var z = rezim === 'zima', zav = z && k.slavoj;
      karta.innerHTML =
        '<div class="mapa__karta-hlava"><span class="kurt-cislo ' + (zav ? 'kurt-cislo--plne' : 'kurt-cislo--akcent') + ' kurt-cislo--velke" aria-hidden="true">' + esc(k.id) + '</span><div>' +
        '<p class="mapa__karta-nazev">' + esc(k.nazev) + '</p><p class="mapa__karta-pod">' + esc(k.pod) + '</p></div></div>' +
        '<div class="mapa__karta-sloupec' + (!z ? ' je-aktivni-sezona' : '') + '"><span class="stitek">Léto</span><span>' + esc(k.l) + '</span><span class="sede tnum">' + esc(k.cl) + (k.cl === 'doplní klub' ? '' : '') + '</span></div>' +
        '<div class="mapa__karta-sloupec' + (z ? ' je-aktivni-sezona' : '') + '"><span class="stitek">Zima 2026/27</span><span>' + esc(k.z) + '</span><span class="sede tnum">' + esc(k.cz) + '</span></div>' +
        '<div class="mapa__karta-akce">' + (zav ? '<span class="pozn">v zimě uzavřeno</span>' : '<a class="tlacitko tlacitko--akcent tlacitko--male" href="' + DATA.rezervace + '" rel="noopener">Rezervovat kurt</a>') +
        (k.hala ? '<a class="odkaz-sipka mensi" href="' + cenik + '" data-kalk-hala="' + k.hala + '">Spočítat cenu v zimě</a>' : '<a class="odkaz-sipka mensi" href="' + cenik + '">Letní ceník</a>') + '</div>';
    }
    function kartaSluzby(sl) {
      karta.innerHTML =
        '<div class="mapa__karta-hlava"><span class="kurt-cislo kurt-cislo--akcent kurt-cislo--velke" aria-hidden="true">•</span><div>' +
        '<p class="mapa__karta-nazev">' + esc(sl.nazev) + '</p><p class="mapa__karta-pod">' + esc(sl.pod) + '</p></div></div>' +
        '<div class="mapa__karta-sloupec"><span class="stitek">Co tu je</span><span>' + esc(sl.l) + '</span></div>' +
        '<div class="mapa__karta-sloupec"><span class="stitek">Pro koho a kdy</span><span>' + esc(sl.z) + (sl.doplni ? ' <span class="pozn pozn--doplni">doplní klub</span>' : '') + '</span></div>' +
        '<div class="mapa__karta-akce"><a class="odkaz-sipka mensi" href="#doplnky-ceny">Ceny služeb</a><a class="telefon mensi" href="' + DATA.telefonOdkaz + '">' + DATA.telefon.replace('+420 ', '') + '</a></div>';
    }
    function vyber(id) {
      vybrano = (vybrano === id) ? null : id;
      $$('[data-id]', box).forEach(function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-id') === vybrano)); });
      if (!vybrano) { vychoziKarta(); return; }
      var k = KURTY.filter(function (x) { return x.id === vybrano; })[0];
      if (k) kartaKurtu(k); else kartaSluzby(SLUZBY.filter(function (x) { return x.id === vybrano; })[0]);
    }
    function nastavRezim(r, oznamit) {
      rezim = r;
      box.setAttribute('data-rezim', r);
      var radio = lista.querySelector('input[value="' + r + '"]'); if (radio) radio.checked = true;
      KURTY.forEach(function (k) {
        var b = body.querySelector('[data-id="' + k.id + '"]');
        var zav = r === 'zima' && k.slavoj;
        b.classList.toggle('je-zavreno', zav);
        b.setAttribute('aria-label', k.nazev + ', ' + k.pod + (zav ? ', v zimě uzavřeno' : ''));
      });
      if (foto) foto.setAttribute('data-rezim', r);
      if (souhrn) $$('[data-sezona-sloupec]', souhrn).forEach(function (c) { c.classList.toggle('je-aktivni', c.getAttribute('data-sezona-sloupec') === r); });
      if (vybrano) { var v = vybrano; vybrano = null; vyber(v); } else vychoziKarta();
      if (oznamit) CLTK.hlas(r === 'zima' ? 'Plán areálu v zimě: kurty pod halami, Slavoj uzavřen.' : 'Plán areálu v létě.');
    }
    lista.addEventListener('change', function (e) {
      if (e.target.name === uid + '-s') nastavRezim(e.target.value, true);
      if (e.target.hasAttribute('data-vrstva')) box.setAttribute('data-vrstva-' + e.target.getAttribute('data-vrstva'), e.target.checked ? 'ano' : 'ne');
    });
    box.addEventListener('click', function (e) {
      var b = e.target.closest('[data-id]');
      if (b && box.contains(b)) { vyber(b.getAttribute('data-id')); return; }
      var a = e.target.closest('[data-kalk-hala]');
      if (a) doc.dispatchEvent(new CustomEvent('cltk:kalkulacka', { detail: { hala: a.getAttribute('data-kalk-hala') } }));
    });
    // vnější přepínače sezóny (např. na leteckém snímku): [data-mapa-prepni="zima"]
    $$('[data-mapa-prepni]').forEach(function (t) { t.addEventListener('click', function () { nastavRezim(t.getAttribute('data-mapa-prepni'), true); }); });
    nastavRezim(rezim, false);
  }

  /* ── KALKULAČKA CENÍKU ────────────────────────────────────────────────── */
  function kalkulacka(box) {
    var C = DATA.cenik, uid = 'kalk' + Math.random().toString(36).slice(2, 7);
    var st = { sezona: box.getAttribute('data-kalk-sezona') || 'zima', hala: 'antuka', pasmo: 1, forma: 'hodina', hodin: 1, clen: false, letoKde: 'hlavni', svetla: false };
    box.classList.add('kalk');
    box.innerHTML =
      '<form class="kalk__ovladani" novalidate aria-label="Kalkulačka ceny kurtu">' +
        '<fieldset class="kalk__skupina"><legend class="skupina-legenda">Sezóna</legend>' +
          '<div class="volba"><input type="radio" id="' + uid + 'z" name="sezona" value="zima"><label for="' + uid + 'z">Zima 2026/27</label>' +
          '<input type="radio" id="' + uid + 'l" name="sezona" value="leto"><label for="' + uid + 'l">Léto 2026</label></div></fieldset>' +
        '<fieldset class="kalk__skupina" data-kalk-cast="kde"><legend class="skupina-legenda">Kde chcete hrát</legend><div class="volby-karty" data-kalk-kde></div></fieldset>' +
        '<fieldset class="kalk__skupina" data-kalk-cast="kdy"><legend class="skupina-legenda">Kdy</legend><div class="cipy" data-kalk-kdy></div></fieldset>' +
        '<fieldset class="kalk__skupina" data-kalk-cast="forma"><legend class="skupina-legenda">Jak často</legend>' +
          '<div class="cipy"><input type="radio" id="' + uid + 'f1" name="forma" value="hodina"><label class="cip" for="' + uid + 'f1">Jednotlivé hodiny</label>' +
          '<input type="radio" id="' + uid + 'f2" name="forma" value="predplatne"><label class="cip" for="' + uid + 'f2" data-kalk-predplatne-popis>Předplatné · 1 hodina týdně</label></div></fieldset>' +
        '<div class="rozsah" data-kalk-cast="hodin"><div class="rozsah__hlava"><label class="skupina-legenda" for="' + uid + 'h" style="margin:0">Počet hodin</label><output class="rozsah__vystup" id="' + uid + 'o" for="' + uid + 'h" aria-live="polite">1 hodina</output></div>' +
          '<input type="range" id="' + uid + 'h" name="hodin" min="1" max="10" step="1" value="1"><div class="rozsah__meze" aria-hidden="true"><span>1</span><span>10</span></div></div>' +
        '<label class="zatrzitko" data-kalk-cast="svetla"><input type="checkbox" name="svetla"><span>Večerní svícení na kurtech 2, 3, 4 <span class="sede">(+150&nbsp;Kč/h)</span></span></label>' +
        '<div class="kalk__radek"><button type="button" class="prepinac" role="switch" aria-checked="false" data-kalk-clen><span class="prepinac__kolej" aria-hidden="true"></span><span>Jsem člen klubu<span class="prepinac__popis">ukáže členskou cenu a úsporu</span></span></button>' +
          '<a class="odkaz-sipka mensi" href="#clenstvi">Ceny členství</a></div>' +
      '</form>' +
      '<aside class="uctenka" aria-label="Výsledná cena"><div class="uctenka__hlava"><span class="stitek">Účtenka</span><p class="uctenka__co" data-u-co></p><p class="uctenka__kdy" data-u-kdy></p></div>' +
        '<div class="uctenka__radky cenik" data-u-radky></div>' +
        '<div class="uctenka__soucet" aria-live="polite"><p class="uctenka__cena tnum" data-u-cena></p><p class="uctenka__jednotka" data-u-jednotka></p><p class="uctenka__uspora" data-u-uspora hidden></p></div>' +
        '<p class="uctenka__pozn" data-u-pozn></p>' +
        '<div class="uctenka__akce"><a class="tlacitko tlacitko--akcent" href="' + DATA.rezervace + '" rel="noopener">Rezervovat kurt</a><a class="telefon mensi" href="' + DATA.telefonOdkaz + '">608 974 974 <span class="telefon__popis">recepce</span></a></div></aside>';

    var form = box.querySelector('form');
    form.addEventListener('submit', function (e) { e.preventDefault(); });
    var q = function (s) { return box.querySelector(s); };
    var kde = q('[data-kalk-kde]'), kdy = q('[data-kalk-kdy]');
    var range = q('input[type="range"]'), out = q('output');

    function kreslVolby() {
      if (st.sezona === 'zima') {
        kde.innerHTML = C.zima.map(function (h) {
          return '<label class="karta-volba"><input type="radio" name="hala" value="' + h.id + '"' + (h.id === st.hala ? ' checked' : '') + '><span class="karta-volba__nazev">' + esc(h.nazev) + '</span><span class="karta-volba__popis">' + esc(h.kurty) + ' · ' + esc(h.obdobi) + '</span>' +
            '<span class="karta-volba__cena">od ' + CLTK.kc(Math.min.apply(null, h.pasma.map(function (p) { return p[1]; }))) + '/h</span></label>';
        }).join('');
        var h = halaZ();
        if (st.pasmo >= h.pasma.length) st.pasmo = 0;
        kdy.innerHTML = h.pasma.map(function (p, i) {
          return '<input type="radio" id="' + uid + 'p' + i + '" name="pasmo" value="' + i + '"' + (i === st.pasmo ? ' checked' : '') + '><label class="cip" for="' + uid + 'p' + i + '">' + esc(p[0]) + '</label>';
        }).join('');
        q('[data-kalk-predplatne-popis]').textContent = 'Předplatné · 1 hodina týdně, ' + h.tydnu + ' týdnů';
      } else {
        kde.innerHTML = C.leto.map(function (h) {
          return '<label class="karta-volba"><input type="radio" name="letoKde" value="' + h.id + '"' + (h.id === st.letoKde ? ' checked' : '') + '><span class="karta-volba__nazev">' + esc(h.nazev) + '</span><span class="karta-volba__popis">' + esc(h.kurty) + ' · celý den vč. víkendů</span>' +
            '<span class="karta-volba__cena">' + CLTK.kc(h.cena) + '/h</span></label>';
        }).join('');
        kdy.innerHTML = '<span class="cip je-aktivni" aria-current="true">celý den včetně víkendů</span>';
      }
      q('[data-kalk-cast="forma"]').hidden = st.sezona !== 'zima';
      if (st.sezona !== 'zima') st.forma = 'hodina';
      q('[data-kalk-cast="svetla"]').hidden = !(st.sezona === 'leto' && st.letoKde === 'hlavni');
      form.querySelector('input[name="sezona"][value="' + st.sezona + '"]').checked = true;
      var f = form.querySelector('input[name="forma"][value="' + st.forma + '"]'); if (f) f.checked = true;
    }
    function halaZ() { return C.zima.filter(function (h) { return h.id === st.hala; })[0] || C.zima[0]; }

    function radek(pol, cena, tr) { return '<div class="cenik__radek' + (tr ? ' ' + tr : '') + '"><span class="cenik__polozka">' + pol + '</span><span class="cenik__vodici" aria-hidden="true"></span><span class="cenik__cena">' + cena + '</span></div>'; }

    function spocti() {
      q('[data-kalk-cast="hodin"]').hidden = st.forma !== 'hodina';
      var co, kdyT, radky = '', cena, jednotka, uspora = 0, usporaText = '', pozn = '';
      var hod = st.hodin, tvar = CLTK.sklonuj(hod, ['hodina', 'hodiny', 'hodin']);
      out.textContent = hod + NB + tvar;
      range.setAttribute('aria-valuetext', hod + ' ' + tvar);
      range.style.setProperty('--plneni', ((hod - 1) / 9 * 100) + '%');
      if (st.sezona === 'zima') {
        var h = halaZ(), p = h.pasma[st.pasmo];
        co = h.nazev; kdyT = h.kurty + ' · ' + p[0];
        if (st.forma === 'hodina') {
          var ver = p[1] * hod, cl = p[2] * hod;
          radky += radek('Veřejnost · ' + hod + NB + tvar, CLTK.kc(ver), st.clen ? 'uctenka__radek--preskrtnuty' : '');
          radky += radek('Člen klubu · ' + hod + NB + tvar, CLTK.kc(cl), st.clen ? '' : '');
          cena = st.clen ? cl : ver;
          jednotka = hod === 1 ? 'za hodinu, ' + (st.clen ? 'členská cena' : 'cena pro veřejnost') : 'za ' + hod + NB + tvar + ' (' + CLTK.kc(st.clen ? p[2] : p[1]) + ' za hodinu)';
          uspora = ver - cl;
          usporaText = st.clen ? 'Ušetříte ' + CLTK.kc(uspora) + (hod === 1 ? ' za hodinu' : '') : 'Členové ušetří ' + CLTK.kc(uspora) + (hod === 1 ? ' za hodinu' : '');
          pozn = 'Období ' + h.obdobi + '. Klub si vyhrazuje právo přesunout rezervaci na jiný dvorec nebo do jiné haly při zachování času a povrchu.';
        } else {
          radky += radek('Předplatné · veřejnost', CLTK.kc(p[3]), st.clen ? 'uctenka__radek--preskrtnuty' : '');
          radky += radek('Předplatné · člen klubu', CLTK.kc(p[4]));
          radky += radek('Přepočet na hodinu', CLTK.kc(st.clen ? p[6] : p[5]));
          cena = st.clen ? p[4] : p[3];
          jednotka = '1 hodina týdně po ' + h.tydnu + ' týdnů · ' + h.obdobi;
          uspora = p[3] - p[4];
          usporaText = (st.clen ? 'Ušetříte ' : 'Členové ušetří ') + CLTK.kc(uspora) + ' za sezónu';
          pozn = 'Zvýhodněná cena předplatného platí jen při platbě předem a za celé období. Trvalé rezervace je nutné zrušit telefonicky nejpozději 24 hodin předem.';
        }
      } else {
        var l = C.leto.filter(function (x) { return x.id === st.letoKde; })[0];
        var sv = (st.svetla && l.svetla) ? C.svetla : 0;
        co = 'Léto 2026 · ' + l.nazev; kdyT = l.kurty + ' · celý den včetně víkendů';
        var ver2 = (l.cena + sv) * hod, cl2 = (l.clen + sv) * hod;
        radky += radek('Veřejnost · ' + hod + NB + tvar + (sv ? ' se svícením' : ''), CLTK.kc(ver2), st.clen ? 'uctenka__radek--preskrtnuty' : '');
        radky += radek('Člen klubu', l.clen === 0 ? (sv ? 'zdarma + svícení' : 'zdarma') : CLTK.kc(cl2));
        cena = st.clen ? cl2 : ver2;
        jednotka = st.clen && l.clen === 0 && !sv ? 'venkovní kurty mají členové v létě zdarma' : (hod === 1 ? 'za hodinu' : 'za ' + hod + NB + tvar);
        uspora = ver2 - cl2;
        usporaText = (st.clen ? 'Ušetříte ' : 'Členové ušetří ') + CLTK.kc(uspora) + (hod === 1 ? ' za hodinu' : '');
        pozn = (sv ? 'Podmínky svícení pro členy doplní klub. ' : '') + 'V létě nejsou trvalé rezervace. Ceník pro sezónu 2027 doplní klub.';
      }
      q('[data-u-co]').textContent = co;
      q('[data-u-kdy]').textContent = kdyT;
      q('[data-u-radky]').innerHTML = radky;
      q('[data-u-cena]').innerHTML = cena === 0 ? 'zdarma' : CLTK.kc(cena).replace(NB + 'Kč', '<small>Kč</small>');
      q('[data-u-jednotka]').textContent = jednotka;
      var u = q('[data-u-uspora]');
      u.hidden = !(uspora > 0);
      u.textContent = usporaText;
      q('[data-u-pozn]').textContent = pozn;
    }

    form.addEventListener('change', function (e) {
      var t = e.target;
      if (t.name === 'sezona') { st.sezona = t.value; kreslVolby(); }
      if (t.name === 'hala') { st.hala = t.value; kreslVolby(); }
      if (t.name === 'pasmo') st.pasmo = +t.value;
      if (t.name === 'forma') st.forma = t.value;
      if (t.name === 'letoKde') { st.letoKde = t.value; kreslVolby(); }
      if (t.name === 'svetla') st.svetla = t.checked;
      spocti();
    });
    range.addEventListener('input', function () { st.hodin = +range.value; spocti(); });
    var clen = q('[data-kalk-clen]');
    clen.addEventListener('click', function () {
      st.clen = clen.getAttribute('aria-checked') !== 'true';
      clen.setAttribute('aria-checked', String(st.clen));
      spocti();
    });
    doc.addEventListener('cltk:kalkulacka', function (e) {
      var d = e.detail || {};
      if (d.hala && d.hala !== 'pevna-leto') { st.sezona = 'zima'; st.hala = d.hala; }
      if (typeof d.clen === 'boolean') { st.clen = d.clen; clen.setAttribute('aria-checked', String(st.clen)); }
      kreslVolby(); spocti();
    });
    kreslVolby(); spocti();
  }

  /* ── CIFERNÍK SEZÓNY ──────────────────────────────────────────────────── */
  function doy(md) { var p = md.split('-'); var d = new Date(2025, +p[0] - 1, +p[1]); return Math.round((d - new Date(2025, 0, 1)) / 864e5); }
  function uhel(md) { return doy(md) / 365 * 360; }
  function bodNaKruhu(r, a) { var rad = (a - 90) * Math.PI / 180; return [250 + r * Math.cos(rad), 250 + r * Math.sin(rad)]; }
  function oblouk(r, a1, a2) {
    if (a2 < a1) a2 += 360;
    if (a2 - a1 < 3) a2 = a1 + 3;
    var p1 = bodNaKruhu(r, a1), p2 = bodNaKruhu(r, a2), velky = (a2 - a1) > 180 ? 1 : 0;
    return 'M' + p1[0].toFixed(2) + ' ' + p1[1].toFixed(2) + ' A' + r + ' ' + r + ' 0 ' + velky + ' 1 ' + p2[0].toFixed(2) + ' ' + p2[1].toFixed(2);
  }
  function cifernik(box) {
    var seznam = box.querySelector('[data-cifernik-seznam]') || doc.querySelector(box.getAttribute('data-cifernik-seznam-sel') || '');
    var plocha = box.querySelector('.cifernik');
    if (!seznam || !plocha) return;
    var POLOMERY = { 1: 214, 2: 192, 3: 171, 4: 150 };
    var d = dnes(), md = ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
    var s = '<svg viewBox="0 0 500 500" aria-hidden="true" focusable="false">';
    s += '<circle class="cf-kruh" cx="250" cy="250" r="232"/><circle class="cf-kruh cf-kruh--jemny" cx="250" cy="250" r="203"/><circle class="cf-kruh cf-kruh--jemny" cx="250" cy="250" r="160"/><circle class="cf-kruh" cx="250" cy="250" r="132"/>';
    for (var t = 0; t < 52; t++) { var a = t / 52 * 360, p1 = bodNaKruhu(232, a), p2 = bodNaKruhu(226, a); s += '<line class="cf-ryska" x1="' + p1[0].toFixed(1) + '" y1="' + p1[1].toFixed(1) + '" x2="' + p2[0].toFixed(1) + '" y2="' + p2[1].toFixed(1) + '"/>'; }
    for (var m = 0; m < 12; m++) {
      var am = uhel(('0' + (m + 1)).slice(-2) + '-01'), q1 = bodNaKruhu(236, am), q2 = bodNaKruhu(222, am);
      s += '<line class="cf-ryska cf-ryska--mesic" x1="' + q1[0].toFixed(1) + '" y1="' + q1[1].toFixed(1) + '" x2="' + q2[0].toFixed(1) + '" y2="' + q2[1].toFixed(1) + '"/>';
      var stred = am + 15.2, pt = bodNaKruhu(250, stred);
      s += '<text class="cf-mesic" x="' + pt[0].toFixed(1) + '" y="' + (pt[1] + 3.5).toFixed(1) + '" text-anchor="middle">' + MESICE_ZKR[m] + '</text>';
    }
    var polozky = $$('li[data-od]', seznam);
    polozky.forEach(function (li, i) {
      var od = li.getAttribute('data-od'), dO = li.getAttribute('data-do') || od, druh = li.getAttribute('data-druh') || 'klub';
      var ok = +(li.getAttribute('data-okruh') || 4), jist = li.getAttribute('data-jistota') || 'potvrzeno';
      s += '<path class="cf-oblouk cf-oblouk--' + druh + (jist === 'doplni' ? ' cf-oblouk--doplni' : '') + '" data-i="' + i + '" d="' + oblouk(POLOMERY[ok] || 150, uhel(od), uhel(dO) + (od === dO ? 0 : 1)) + '"/>';
      li.setAttribute('data-i', i);
    });
    var ah = uhel(md);
    s += '<g class="cf-otoc" style="transform:rotate(' + (CLTK.pohybPovolen() ? 0 : ah) + 'deg)"><line class="cf-rucicka" x1="250" y1="118" x2="250" y2="34"/><circle class="cf-rucicka-hlava" cx="250" cy="34" r="4.5"/></g>';
    s += '</svg>';
    plocha.insertAdjacentHTML('afterbegin', s);
    var otoc = plocha.querySelector('.cf-otoc');
    if (CLTK.pohybPovolen()) {
      requestAnimationFrame(function () { requestAnimationFrame(function () { otoc.style.transform = 'rotate(' + ah + 'deg)'; }); });
    }
    var stredBox = plocha.querySelector('.cifernik__stred');
    var vychozi = stredBox ? stredBox.innerHTML : '';
    function zvyrazni(i) {
      plocha.classList.toggle('je-vyber', i !== null);
      $$('.cf-oblouk', plocha).forEach(function (o) { o.classList.toggle('je-aktivni', i !== null && o.getAttribute('data-i') === String(i)); });
      $$('button', seznam).forEach(function (b) { b.setAttribute('aria-pressed', String(i !== null && b.closest('li').getAttribute('data-i') === String(i))); });
      if (!stredBox) return;
      if (i === null) { stredBox.innerHTML = vychozi; return; }
      var li = seznam.querySelector('li[data-i="' + i + '"]');
      var nazev = li.querySelector('.cifernik__nazev'), kdy = li.querySelector('.cifernik__kdy'), jist = li.querySelector('.cifernik__jistota');
      stredBox.innerHTML = '<p class="cifernik__popis"><strong>' + (nazev ? nazev.innerHTML : '') + '</strong>' + (kdy ? kdy.innerHTML : '') + (jist ? '<br>' + jist.innerHTML : '') + '</p>';
    }
    var aktivni = null;
    seznam.addEventListener('click', function (e) {
      var b = e.target.closest('button'); if (!b) return;
      var i = b.closest('li').getAttribute('data-i');
      aktivni = (aktivni === i) ? null : i; zvyrazni(aktivni);
    });
    seznam.addEventListener('mouseover', function (e) { var b = e.target.closest('button'); if (b && aktivni === null) zvyrazni(b.closest('li').getAttribute('data-i')); });
    seznam.addEventListener('mouseleave', function () { if (aktivni === null) zvyrazni(null); });
    seznam.addEventListener('focusin', function (e) { var b = e.target.closest('button'); if (b && aktivni === null) zvyrazni(b.closest('li').getAttribute('data-i')); });
    seznam.addEventListener('focusout', function (e) { if (!seznam.contains(e.relatedTarget) && aktivni === null) zvyrazni(null); });
  }

  /* „Za X dní“ u nejbližších událostí: [data-za-dni="2026-09-28"] */
  function odpocty() {
    var d = dnes();
    $$('[data-za-dni]').forEach(function (x) {
      var n = rozdilDni(d, zIso(x.getAttribute('data-za-dni')));
      x.textContent = n < 0 ? 'proběhlo' : CLTK.zaDni(n);
    });
    $$('[data-dnes-den]').forEach(function (x) { x.textContent = CLTK.datum.denMesic(d); });
    $$('[data-dnes-dlouhy]').forEach(function (x) { x.textContent = CLTK.datum.dlouhy(d); });
    // nejbližší budoucí událost ze seznamu [data-nejblizsi]
    $$('[data-nejblizsi]').forEach(function (s) {
      var cil = doc.querySelector(s.getAttribute('data-nejblizsi'));
      if (!cil) return;
      var prvni = $$('[data-datum]', s).filter(function (li) { return rozdilDni(d, zIso(li.getAttribute('data-datum'))) >= 0; })[0];
      if (!prvni) { cil.textContent = 'Termíny dalších akcí doplní klub.'; return; }
      var n = rozdilDni(d, zIso(prvni.getAttribute('data-datum')));
      var nz = prvni.querySelector('.nejblizsi__nazev');
      var zac = CLTK.zaDni(n);
      cil.innerHTML = '<strong>' + zac.charAt(0).toUpperCase() + zac.slice(1) + '</strong>' + (nz ? esc(nz.textContent) : '');
    });
  }

  /* ── RAZÍTKA CTC ──────────────────────────────────────────────────────── */
  var citacRazitek = 0;
  function razitko(e) {
    var n = ++citacRazitek, id = 'rz' + n + Math.random().toString(36).slice(2, 5);
    var tvar = e.getAttribute('data-tvar') || 'kruh';
    var horni = e.getAttribute('data-horni') || '', dolni = e.getAttribute('data-dolni') || '', stred = e.getAttribute('data-stred') || '', pod = e.getAttribute('data-pod') || '';
    var rot = parseFloat(e.getAttribute('data-otoceni') || '-6');
    var filtr = '<filter id="' + id + 'f" x="-10%" y="-10%" width="120%" height="120%"><feTurbulence type="fractalNoise" baseFrequency="1.1" numOctaves="2" seed="' + (n * 7) + '" result="sum"/>' +
      '<feDisplacementMap in="SourceGraphic" in2="sum" scale="1.6" xChannelSelector="R" yChannelSelector="G" result="posun"/>' +
      '<feColorMatrix in="sum" type="matrix" values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 -3.6 2.75" result="maska"/>' +
      '<feComposite in="posun" in2="maska" operator="in"/></filter>';
    var s;
    if (tvar === 'obdelnik') {
      s = '<svg viewBox="0 0 300 200" aria-hidden="true" focusable="false"><defs>' + filtr + '</defs><g filter="url(#' + id + 'f)" transform="rotate(' + rot + ' 150 100)" fill="currentColor" stroke="currentColor" opacity=".9">' +
        '<rect x="10" y="22" width="280" height="156" rx="6" fill="none" stroke-width="3.4"/><rect x="19" y="31" width="262" height="138" rx="3" fill="none" stroke-width="1.1"/>' +
        '<text x="150" y="64" text-anchor="middle" class="razitko__text" stroke="none">' + esc(horni) + '</text>' +
        '<line x1="44" y1="78" x2="256" y2="78" stroke-width="1"/>' +
        '<text x="150" y="116" text-anchor="middle" class="razitko__stred" stroke="none" style="font-size:' + (stred.length > 12 ? 24 : 30) + 'px">' + esc(stred) + '</text>' +
        '<line x1="44" y1="132" x2="256" y2="132" stroke-width="1"/>' +
        '<text x="150" y="154" text-anchor="middle" class="razitko__pod" stroke="none">' + esc(dolni) + (pod ? ' · ' + esc(pod) : '') + '</text></g></svg>';
    } else {
      var fs = stred.length > 6 ? 20 : stred.length > 4 ? 26 : 32;
      s = '<svg viewBox="0 0 200 200" aria-hidden="true" focusable="false"><defs>' + filtr +
        '<path id="' + id + 'h" d="M 100 100 m -69 0 a 69 69 0 1 1 138 0"/><path id="' + id + 'd" d="M 100 100 m -79 0 a 79 79 0 0 0 158 0"/></defs>' +
        '<g filter="url(#' + id + 'f)" transform="rotate(' + rot + ' 100 100)" fill="currentColor" stroke="currentColor" opacity=".9">' +
        '<circle cx="100" cy="100" r="94" fill="none" stroke-width="3.4"/><circle cx="100" cy="100" r="86" fill="none" stroke-width="1.1"/><circle cx="100" cy="100" r="52" fill="none" stroke-width="1.1"/>' +
        '<text class="razitko__text" stroke="none" style="font-size:' + (horni.length > 22 ? 10.5 : 12) + 'px"><textPath href="#' + id + 'h" startOffset="50%" text-anchor="middle">' + esc(horni) + '</textPath></text>' +
        '<text class="razitko__text" stroke="none" style="font-size:11.5px"><textPath href="#' + id + 'd" startOffset="50%" text-anchor="middle">' + esc(dolni) + '</textPath></text>' +
        '<text x="22" y="104" text-anchor="middle" stroke="none" style="font-size:9px">★</text><text x="178" y="104" text-anchor="middle" stroke="none" style="font-size:9px">★</text>' +
        '<text x="100" y="' + (pod ? 104 : 110) + '" text-anchor="middle" class="razitko__stred" stroke="none" style="font-size:' + fs + 'px">' + esc(stred) + '</text>' +
        (pod ? '<text x="100" y="124" text-anchor="middle" class="razitko__pod" stroke="none" style="font-size:8.5px">' + esc(pod) + '</text>' : '') +
        '</g></svg>';
    }
    e.insertAdjacentHTML('afterbegin', s);
    e.classList.add('je-vykresleno');
  }

  /* ── STOPA MÍČKU NA ANTUCE ────────────────────────────────────────────── */
  function stopa() {
    $$('[data-stopa]').forEach(function (plocha) {
      var pocet = 0;
      plocha.addEventListener('pointerdown', function (e) {
        if (!CLTK.pohybPovolen() || e.button > 0) return;
        if (e.target.closest('a, button, input, select, textarea, label, [data-bez-stopy]')) return;
        if (pocet > 6) return;
        var r = plocha.getBoundingClientRect();
        var s = el('span', { class: 'stopa', 'aria-hidden': 'true' });
        s.style.left = (e.clientX - r.left) + 'px';
        s.style.top = (e.clientY - r.top) + 'px';
        s.style.setProperty('--r', (Math.random() * 50 - 25).toFixed(1) + 'deg');
        plocha.appendChild(s); pocet++;
        var pryc = function () { if (s.parentNode) { s.parentNode.removeChild(s); pocet--; } };
        s.addEventListener('animationend', pryc);
        setTimeout(pryc, 1900);
      });
    });
  }

  /* ── START ────────────────────────────────────────────────────────────── */
  function start() {
    nastaveni();
    hlavicka();
    mobilniMenu();
    jazyk();
    dnesNaStvanici();
    $$('[data-mapa]').forEach(mapa);
    $$('[data-kalkulacka]').forEach(kalkulacka);
    odpocty();
    $$('[data-cifernik]').forEach(cifernik);
    $$('[data-razitko]').forEach(razitko);
    stopa();
    reveal();
    doc.dispatchEvent(new CustomEvent('cltk:pripraveno'));
  }
  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', start); else start();
})();
