/* ==========================================================================
   I. ČLTK Praha – koncept B „Ostrov v čase“ · sdílené chování (vlastní CORE)
   Moduly (všechny se spouštějí samy podle data-atributů v HTML):
     předvolby (Omezit pohyb / Vyšší kontrast) · mobilní menu · stav hlavičky · CS/EN
     odhalování při rolování · záložky · dialogy · originál archivní fotky
     kronika s razítkem a pilulkou Kapitoly · Tehdy a teď · stěna es · kiosek Revue
     filtry · zoom ve vitríně · české datum
   Veřejné API: window.CLTK (pohybOmezen, datum, otevriDialog, zavriDialog, ulozeni)
   ========================================================================== */
(function () {
  'use strict';
  var d = document, html = d.documentElement;
  var CLTK = window.CLTK = window.CLTK || {};
  html.classList.add('js');

  /* ── úložiště (jen pohodlí, vždy v try/catch) ───────────────────────── */
  var ulozeni = {
    cti: function (k) { try { return window.localStorage.getItem('cltkB.' + k); } catch (e) { return null; } },
    zapis: function (k, v) { try { if (v == null) window.localStorage.removeItem('cltkB.' + k); else window.localStorage.setItem('cltkB.' + k, v); } catch (e) {} }
  };
  CLTK.ulozeni = ulozeni;

  function mq(q) { try { return window.matchMedia(q).matches; } catch (e) { return false; } }
  function pohybOmezen() { return html.getAttribute('data-pohyb') === 'omezit' || mq('(prefers-reduced-motion: reduce)'); }
  CLTK.pohybOmezen = pohybOmezen;
  function vse(sel, root) { return [].slice.call((root || d).querySelectorAll(sel)); }
  function normalizuj(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  CLTK.normalizuj = normalizuj; CLTK.esc = esc;

  /* ── české datum ─────────────────────────────────────────────────────── */
  var MESICE_2 = ['ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince'];
  var MESICE_1 = ['leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'];
  var DNY = ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'];
  function naDatum(x) {
    if (x instanceof Date) return x;
    var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(x || ''));
    return m ? new Date(+m[1], +m[2] - 1, +m[3]) : new Date(x);
  }
  CLTK.datum = {
    mesice: MESICE_2, mesiceNom: MESICE_1, dny: DNY,
    /* styl: 'kratky' 10. 9. 2026 · 'dlouhy' 10. září 2026 · 'den' středa 23. září 2026 · 'mesic' září 2026 */
    formatuj: function (x, styl) {
      var t = naDatum(x); if (isNaN(t)) return '';
      var D = t.getDate(), M = t.getMonth(), R = t.getFullYear(), nb = ' ';
      switch (styl) {
        case 'dlouhy': return D + '.' + nb + MESICE_2[M] + ' ' + R;
        case 'den': return DNY[t.getDay()] + ' ' + D + '.' + nb + MESICE_2[M] + ' ' + R;
        case 'mesic': return MESICE_1[M] + ' ' + R;
        default: return D + '.' + nb + (M + 1) + '.' + nb + R;
      }
    },
    /* rozsah: 16.–22. 8. 2026 */
    rozsah: function (a, b) {
      var x = naDatum(a), y = naDatum(b), nb = ' ';
      if (x.getMonth() === y.getMonth() && x.getFullYear() === y.getFullYear()) return x.getDate() + '.–' + y.getDate() + '.' + nb + (y.getMonth() + 1) + '.' + nb + y.getFullYear();
      return CLTK.datum.formatuj(x) + ' – ' + CLTK.datum.formatuj(y);
    }
  };
  vse('[data-dnes]').forEach(function (n) { n.textContent = CLTK.datum.formatuj(new Date(), n.getAttribute('data-dnes') || 'den'); });

  /* ── předvolby přístupnosti (patička) ────────────────────────────────── */
  function nactiPredvolby() {
    var p = ulozeni.cti('pohyb'), k = ulozeni.cti('kontrast');
    if (p === 'omezit') html.setAttribute('data-pohyb', 'omezit');
    if (k === 'vyssi') html.setAttribute('data-kontrast', 'vyssi');
  }
  nactiPredvolby();
  function obnovPrepinace() {
    vse('[data-prepinac]').forEach(function (b) {
      var typ = b.getAttribute('data-prepinac');
      var zap = typ === 'pohyb' ? html.getAttribute('data-pohyb') === 'omezit' : html.getAttribute('data-kontrast') === 'vyssi';
      b.setAttribute('aria-pressed', zap ? 'true' : 'false');
    });
  }
  d.addEventListener('click', function (e) {
    var b = e.target.closest('[data-prepinac]');
    if (!b) return;
    var typ = b.getAttribute('data-prepinac');
    if (typ === 'pohyb') {
      var zap = html.getAttribute('data-pohyb') !== 'omezit';
      if (zap) html.setAttribute('data-pohyb', 'omezit'); else html.removeAttribute('data-pohyb');
      ulozeni.zapis('pohyb', zap ? 'omezit' : null);
      if (zap) vse('[data-reveal]').forEach(function (n) { n.classList.add('in'); });
    } else if (typ === 'kontrast') {
      var zk = html.getAttribute('data-kontrast') !== 'vyssi';
      if (zk) html.setAttribute('data-kontrast', 'vyssi'); else html.removeAttribute('data-kontrast');
      ulozeni.zapis('kontrast', zk ? 'vyssi' : null);
    }
    obnovPrepinace();
  });
  obnovPrepinace();

  /* ── mobilní menu (zámek rolování na <html>, ne na <body> – Safari) ───── */
  var menuBtn = d.querySelector('.menu-tlacitko');
  var menu = menuBtn && d.getElementById(menuBtn.getAttribute('aria-controls'));
  function menuNastav(otevrit, vratitFokus) {
    if (!menu) return;
    menuBtn.setAttribute('aria-expanded', otevrit ? 'true' : 'false');
    menuBtn.querySelector('.menu-tlacitko-text') && (menuBtn.querySelector('.menu-tlacitko-text').textContent = otevrit ? 'Zavřít' : 'Menu');
    menu.hidden = !otevrit;
    html.classList.toggle('menu-otevreno', otevrit);
    if (otevrit) { var a = menu.querySelector('a, button'); a && a.focus(); }
    else if (vratitFokus) menuBtn.focus();
  }
  if (menu) {
    menuBtn.addEventListener('click', function () { menuNastav(menuBtn.getAttribute('aria-expanded') !== 'true'); });
    menu.addEventListener('click', function (e) { if (e.target.closest('a')) menuNastav(false); });
    d.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !menu.hidden) menuNastav(false, true); });
    window.addEventListener('resize', function () { if (window.innerWidth > 1140 && !menu.hidden) menuNastav(false); });
    // jednoduché držení fokusu uvnitř otevřeného menu
    menu.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab') return;
      var f = vse('a, button', menu).filter(function (x) { return x.offsetParent !== null; });
      if (!f.length) return;
      if (e.shiftKey && d.activeElement === f[0]) { e.preventDefault(); menuBtn.focus(); }
      else if (!e.shiftKey && d.activeElement === f[f.length - 1]) { e.preventDefault(); menuBtn.focus(); }
    });
  }

  /* ── stav hlavičky při rolování ──────────────────────────────────────── */
  var hlavicka = d.querySelector('[data-hlavicka]');
  if (hlavicka) {
    var tik = false;
    var stav = function () { hlavicka.classList.toggle('je-posunuta', window.scrollY > 12); tik = false; };
    window.addEventListener('scroll', function () { if (!tik) { tik = true; requestAnimationFrame(stav); } }, { passive: true });
    stav();
  }

  /* ── přepínač CS / EN (EN zatím jen oznámení) ───────────────────────── */
  var oznameni = null;
  function zavriOznameni() { if (oznameni) { oznameni.remove(); oznameni = null; } }
  d.addEventListener('click', function (e) {
    var b = e.target.closest('[data-jazyk]');
    if (!b) return;
    if (b.getAttribute('data-jazyk') === 'cs') { zavriOznameni(); return; }
    zavriOznameni();
    oznameni = d.createElement('div');
    oznameni.className = 'oznameni';
    oznameni.setAttribute('role', 'status');
    oznameni.setAttribute('lang', 'en');
    oznameni.innerHTML =
      '<button type="button" class="oznameni-zavrit" aria-label="Close">×</button>' +
      '<p class="oznameni-titul">The English version is on its way.</p>' +
      '<p>We are translating the new site together with the club. Until then, the club newsletter is published in English as well.</p>' +
      '<p style="margin-top:10px"><a class="odkaz odkaz--ven" href="https://files.cltk.cz/gltel1y4u2n01/EN%20Newsletter%203%3A2026.pdf?download" target="_blank" rel="noopener">EN Newsletter 3/2026 (PDF)</a></p>';
    d.body.appendChild(oznameni);
    oznameni.querySelector('.oznameni-zavrit').addEventListener('click', function () { zavriOznameni(); b.focus(); });
    oznameni.querySelector('.oznameni-zavrit').focus();
  });
  d.addEventListener('keydown', function (e) { if (e.key === 'Escape') zavriOznameni(); });

  /* ── odhalování při rolování (jen vylepšení, obsah je vidět i bez něj) ── */
  var reveal = vse('[data-reveal]');
  if (reveal.length && 'IntersectionObserver' in window && !pohybOmezen()) {
    var vyska = window.innerHeight || 800;
    reveal.forEach(function (n) { if (n.getBoundingClientRect().top < vyska * 0.95) n.classList.add('in'); });
    html.classList.add('rv-zap');
    var io = new IntersectionObserver(function (zaznamy) {
      zaznamy.forEach(function (z) { if (z.isIntersecting) { z.target.classList.add('in'); io.unobserve(z.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
    reveal.forEach(function (n) { if (!n.classList.contains('in')) io.observe(n); });
  } else reveal.forEach(function (n) { n.classList.add('in'); });

  /* ── záložky (role=tablist) ──────────────────────────────────────────── */
  vse('[data-zalozky]').forEach(function (root) {
    var taby = vse('[role="tab"]', root);
    function vyber(t, fokus) {
      taby.forEach(function (x) {
        var ano = x === t;
        x.setAttribute('aria-selected', ano ? 'true' : 'false');
        x.tabIndex = ano ? 0 : -1;
        var p = d.getElementById(x.getAttribute('aria-controls'));
        if (p) p.hidden = !ano;
      });
      if (fokus) t.focus();
      root.dispatchEvent(new CustomEvent('zalozky:zmena', { bubbles: true, detail: { tab: t } }));
    }
    taby.forEach(function (t, i) {
      t.addEventListener('click', function () { vyber(t); });
      t.addEventListener('keydown', function (e) {
        var j = null;
        if (e.key === 'ArrowRight') j = (i + 1) % taby.length;
        else if (e.key === 'ArrowLeft') j = (i - 1 + taby.length) % taby.length;
        else if (e.key === 'Home') j = 0; else if (e.key === 'End') j = taby.length - 1;
        if (j !== null) { e.preventDefault(); vyber(taby[j], true); }
      });
    });
    var start = taby.filter(function (t) { return t.getAttribute('aria-selected') === 'true'; })[0] || taby[0];
    if (start) vyber(start);
  });

  /* ── dialogy ─────────────────────────────────────────────────────────── */
  var posledniFokus = null;
  function otevriDialog(dlg, obsah) {
    if (!dlg) return;
    posledniFokus = d.activeElement;
    if (obsah) {
      var cil = dlg.querySelector('[data-dialog-obsah]') || dlg;
      cil.innerHTML = '';
      cil.appendChild(obsah);
    }
    if (typeof dlg.showModal === 'function') { if (!dlg.open) dlg.showModal(); }
    else dlg.setAttribute('open', '');
    html.classList.add('dialog-otevren');
    var z = dlg.querySelector('[data-dialog-zavrit]'); z && z.focus();
  }
  function zavriDialog(dlg) {
    if (!dlg) return;
    if (typeof dlg.close === 'function' && dlg.open) dlg.close(); else dlg.removeAttribute('open');
  }
  CLTK.otevriDialog = otevriDialog; CLTK.zavriDialog = zavriDialog;
  d.addEventListener('click', function (e) {
    var o = e.target.closest('[data-dialog-otevrit]');
    if (o) {
      var dlg = d.getElementById(o.getAttribute('data-dialog-otevrit'));
      var sab = o.getAttribute('data-dialog-sablona');
      var tpl = sab && d.getElementById(sab);
      otevriDialog(dlg, tpl ? tpl.content.cloneNode(true) : null);
      return;
    }
    var z = e.target.closest('[data-dialog-zavrit]');
    if (z) { zavriDialog(z.closest('dialog')); return; }
    if (e.target.tagName === 'DIALOG' && e.target.open) {
      var r = e.target.getBoundingClientRect();
      if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) zavriDialog(e.target);
    }
  });
  vse('dialog').forEach(function (dlg) {
    dlg.addEventListener('close', function () {
      html.classList.remove('dialog-otevren');
      if (posledniFokus && posledniFokus.focus) posledniFokus.focus();
    });
  });

  /* zoom předmětu ve vitríně / v dialogu: [data-zoom] (tlačítko s obrázkem) */
  d.addEventListener('click', function (e) {
    var z = e.target.closest('[data-zoom]');
    if (!z) return;
    var r = z.getBoundingClientRect();
    if (e.clientX) { z.style.setProperty('--zx', ((e.clientX - r.left) / r.width * 100).toFixed(1) + '%'); z.style.setProperty('--zy', ((e.clientY - r.top) / r.height * 100).toFixed(1) + '%'); }
    var on = !z.classList.contains('je-zoom');
    z.classList.toggle('je-zoom', on);
    z.setAttribute('aria-pressed', on ? 'true' : 'false');
  });
  d.addEventListener('pointermove', function (e) {
    var z = e.target.closest && e.target.closest('[data-zoom].je-zoom');
    if (!z) return;
    var r = z.getBoundingClientRect();
    z.style.setProperty('--zx', ((e.clientX - r.left) / r.width * 100).toFixed(1) + '%');
    z.style.setProperty('--zy', ((e.clientY - r.top) / r.height * 100).toFixed(1) + '%');
  });

  /* ── originál archivní fotky (duotón ↔ barva) ────────────────────────── */
  d.addEventListener('click', function (e) {
    var b = e.target.closest('[data-original]');
    if (!b) return;
    var f = b.closest('.archiv');
    var on = !f.classList.contains('je-original');
    f.classList.toggle('je-original', on);
    b.setAttribute('aria-pressed', on ? 'true' : 'false');
    b.textContent = on ? 'Archivní tón' : 'Originál';
  });

  /* ── kronika: razítko s dobovým jménem + pilulka Kapitoly ────────────── */
  vse('[data-kronika]').forEach(function (root) {
    var kapitoly = vse('[data-era]', root);
    var razitko = root.querySelector('[data-razitko]');
    var odkazy = vse('[data-kapitola-odkaz]', root);
    var pilulka = d.querySelector('[data-kapitoly-pilulka]');
    var pilBtn = pilulka && pilulka.querySelector('button');
    var pilSeznam = pilulka && pilulka.querySelector('.kapitoly-pilulka-seznam');
    var aktivni = null, casovac = null;
    if (!kapitoly.length) return;

    function napis(k) {
      if (!razitko) return;
      var j = razitko.querySelector('.razitko-jmeno'), r = razitko.querySelector('.razitko-roky');
      if (j) j.textContent = k.getAttribute('data-era-nazev') || k.getAttribute('data-era');
      if (r) r.textContent = k.getAttribute('data-era-roky') || '';
    }
    function nastav(k) {
      if (k === aktivni) return;
      aktivni = k;
      var id = k.id;
      odkazy.forEach(function (a) { if (a.getAttribute('href') === '#' + id) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current'); });
      if (pilulka) {
        vse('a', pilulka).forEach(function (a) { if (a.getAttribute('href') === '#' + id) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current'); });
        var t = pilulka.querySelector('[data-pilulka-text]');
        if (t) t.textContent = (k.getAttribute('data-era-cislo') ? k.getAttribute('data-era-cislo') + ' · ' : '') + (k.getAttribute('data-era-nazev') || '');
      }
      if (!razitko) return;
      if (pohybOmezen()) { napis(k); return; }
      clearTimeout(casovac);
      razitko.classList.remove('je-zmena'); void razitko.offsetWidth; razitko.classList.add('je-zmena');
      casovac = setTimeout(function () { napis(k); setTimeout(function () { razitko.classList.remove('je-zmena'); }, 380); }, 200);
    }
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (zaznamy) {
        zaznamy.forEach(function (z) { if (z.isIntersecting) nastav(z.target); });
      }, { rootMargin: '-42% 0px -52% 0px', threshold: 0 });
      kapitoly.forEach(function (k) { io.observe(k); });
      if (pilulka) {
        var io2 = new IntersectionObserver(function (zaznamy) {
          zaznamy.forEach(function (z) {
            pilulka.classList.toggle('je-videt', z.isIntersecting);
            if (!z.isIntersecting && pilBtn) { pilBtn.setAttribute('aria-expanded', 'false'); pilSeznam.hidden = true; }
          });
        }, { rootMargin: '-35% 0px -45% 0px' });
        io2.observe(root);
      }
    }
    napis(kapitoly[0]); aktivni = null; nastav(kapitoly[0]);
    if (pilBtn) {
      pilBtn.addEventListener('click', function () {
        var otevrit = pilBtn.getAttribute('aria-expanded') !== 'true';
        pilBtn.setAttribute('aria-expanded', otevrit ? 'true' : 'false');
        pilSeznam.hidden = !otevrit;
        if (otevrit) { var a = pilSeznam.querySelector('a'); a && a.focus(); }
      });
      pilSeznam.addEventListener('click', function (e) { if (e.target.closest('a')) { pilBtn.setAttribute('aria-expanded', 'false'); pilSeznam.hidden = true; } });
      d.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !pilSeznam.hidden) { pilBtn.setAttribute('aria-expanded', 'false'); pilSeznam.hidden = true; pilBtn.focus(); } });
    }
  });

  /* ── Tehdy a teď: porovnávací posuvník ───────────────────────────────── */
  vse('[data-porovnani]').forEach(function (root) {
    var r = root.querySelector('input[type="range"]');
    var out = root.parentNode.querySelector('[data-porovnani-vystup]');
    var tehdy = root.getAttribute('data-tehdy') || 'tehdy', ted = root.getAttribute('data-ted') || 'teď';
    var t = null;
    function obnov() {
      var v = +r.value;
      root.style.setProperty('--x', v + '%');
      var txt = 'Vlevo ' + tehdy + ' na ' + v + ' % šířky, vpravo ' + ted + ' na ' + (100 - v) + ' %';
      r.setAttribute('aria-valuetext', txt);
      if (out) { clearTimeout(t); t = setTimeout(function () { out.textContent = txt; }, 250); }
    }
    r.addEventListener('input', obnov);
    obnov();
  });

  /* ── stěna es: jména s doloženou příležitostí ───────────────────────── */
  vse('[data-stena]').forEach(function (root) {
    var karta = d.getElementById(root.getAttribute('data-stena'));
    if (!karta) return;
    root.addEventListener('click', function (e) {
      var b = e.target.closest('button.stena-jmeno--dolozeno');
      if (!b) return;
      var otevreno = b.getAttribute('aria-expanded') === 'true';
      vse('button.stena-jmeno--dolozeno', root).forEach(function (x) { x.setAttribute('aria-expanded', 'false'); });
      if (otevreno) { karta.hidden = true; return; }
      b.setAttribute('aria-expanded', 'true');
      var j = b.getAttribute('data-jistota') || 'overeno';
      var jt = { overeno: 'ověřeno', klub: 'podle klubu' }[j] || 'ověřeno';
      karta.innerHTML =
        '<p class="nadtitul" style="margin:0;color:var(--na-tmave-2)">Doložená příležitost</p>' +
        '<p class="stena-karta-jmeno">' + esc(b.textContent) + '</p>' +
        '<p class="stena-karta-text">' + esc(b.getAttribute('data-prilezitost')) + '</p>' +
        '<p style="display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center;margin-top:4px"><span class="pramen">Pramen · ' + esc(b.getAttribute('data-pramen')) + '</span><span class="jistota jistota--' + j + '">' + jt + '</span></p>';
      karta.hidden = false;
    });
  });

  /* ── filtry seznamů: [data-filtr-skupina] ────────────────────────────── */
  vse('[data-filtr-skupina]').forEach(function (skup) {
    var cil = d.getElementById(skup.getAttribute('data-filtr-skupina'));
    if (!cil) return;
    var stav = d.querySelector('[data-filtr-stav="' + cil.id + '"]');
    var prazdno = d.querySelector('[data-filtr-prazdno="' + cil.id + '"]');
    skup.addEventListener('click', function (e) {
      var b = e.target.closest('[data-filtr]');
      if (!b) return;
      vse('[data-filtr]', skup).forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      var h = b.getAttribute('data-filtr'), n = 0;
      vse('[data-filtr-polozka]', cil).forEach(function (p) {
        var ok = h === 'vse' || (' ' + p.getAttribute('data-filtr-polozka') + ' ').indexOf(' ' + h + ' ') > -1;
        p.hidden = !ok; if (ok) n++;
      });
      if (stav) stav.textContent = n + ' ' + (n === 1 ? 'položka' : n >= 2 && n <= 4 ? 'položky' : 'položek');
      if (prazdno) prazdno.hidden = n > 0;
    });
  });

  /* ── kiosek Revue: polička, hledání, příběh v obálkách, detail čísla ── */
  var PRIBEHY = {
    muchova: { jmeno: 'Karolína Muchová', kmen: ['muchov'] },
    hradecka: { jmeno: 'Lucie Hradecká', kmen: ['hradeck'] },
    kodes: { jmeno: 'Jan Kodeš', kmen: ['kodes'] },
    bartunkova: { jmeno: 'Nikola Bartůňková', kmen: ['bartunkov'] }
  };
  CLTK.pribehyRevue = PRIBEHY;
  function kiosek(root) {
    var DATA = window.CLTK_REVUE || [];
    if (!DATA.length) return;
    var policka = root.querySelector('[data-kiosek-policka]');
    var vstup = root.querySelector('[data-kiosek-hledat]');
    var vysl = root.querySelector('[data-kiosek-vysledky]');
    var stav = root.querySelector('[data-kiosek-stav]');
    var dlg = d.getElementById(root.getAttribute('data-kiosek')) || root.querySelector('dialog');
    var poradi = root.getAttribute('data-kiosek-poradi') === 'nove' ? 'nove' : 'stare';
    var podleOznaceni = {};
    DATA.forEach(function (r) { podleOznaceni[r.o] = r; });

    // polička po letech
    if (policka) {
      var roky = {};
      DATA.forEach(function (r) { (roky[r.rok] = roky[r.rok] || []).push(r); });
      var klice = Object.keys(roky).sort(function (a, b) { return poradi === 'nove' ? b - a : a - b; });
      var h = '';
      klice.forEach(function (rok) {
        var cisla = roky[rok].slice().sort(function (a, b) { return poradi === 'nove' ? b.c - a.c : a.c - b.c; });
        h += '<li class="policka-rok"><div class="policka-obalky">';
        cisla.forEach(function (r) {
          h += '<button type="button" class="obalka' + (r.pdf ? '' : ' obalka-bez-pdf') + '" data-kiosek-cislo="' + r.o + '" aria-label="I.ČLTK Revue ' + r.o + (r.titulky[0] ? ' – ' + esc(r.titulky[0].toLowerCase()) : '') + '">' +
            '<img src="' + r.img + '" alt="" width="96" height="136" loading="lazy" decoding="async"></button>';
        });
        h += '</div><span class="policka-rok-cislo" aria-hidden="true">' + rok + '</span></li>';
      });
      policka.innerHTML = h;
    }

    function detail(o, shodaText) {
      var r = podleOznaceni[o];
      if (!r || !dlg) return;
      var obs = r.obsah.map(function (x) {
        var sh = shodaText && normalizuj(x[1]).indexOf(shodaText) > -1;
        return '<li' + (sh ? ' class="je-shoda"' : '') + '><span>' + esc(x[0]) + '</span>' + esc(x[1]) + '</li>';
      }).join('');
      var box = d.createElement('div');
      box.className = 'revue-detail';
      box.innerHTML =
        '<div><img src="' + r.img + '" alt="Obálka I.ČLTK Revue ' + r.o + '" width="280" height="396"></div>' +
        '<div><p class="nadtitul">I.ČLTK Revue · ' + (r.c === 1 ? 'jarní' : 'podzimní') + ' číslo ' + r.rok + '</p>' +
        '<h3 class="t-d3">Číslo ' + r.o + '</h3>' +
        (r.titulky.length ? '<p class="perex" style="font-size:1.12rem">' + r.titulky.map(esc).join(' · ') + '</p>' : '') +
        (r.obalka ? '<p class="poznamka" style="margin-top:12px">Na obálce: ' + esc(r.obalka) + '</p>' : '') +
        (obs ? '<p class="nadtitul" style="margin:22px 0 0">Obsah čísla · strana</p><ol class="revue-detail-obsah">' + obs + '</ol>' : '') +
        '<div class="listek"><dl>' +
        (r.stran ? '<dt>Rozsah</dt><dd>' + r.stran + ' stran</dd>' : '') +
        (r.naklad ? '<dt>Náklad</dt><dd>' + esc(r.naklad) + '</dd>' : '') +
        (r.uzaverka ? '<dt>Uzávěrka</dt><dd>' + esc(r.uzaverka) + '</dd>' : '') +
        '<dt>PDF</dt><dd>' + (r.pdf ? '<a class="odkaz odkaz--ven" href="' + r.pdf + '" target="_blank" rel="noopener">Otevřít PDF' + (r.mb ? ' (' + String(r.mb).replace('.', ',') + ' MB)' : '') + '</a>' : 'PDF v archivu chybí <span class="jistota jistota--doplni">doplní klub</span>') + '</dd>' +
        '</dl></div></div>';
      var tit = dlg.querySelector('[data-dialog-titul]');
      if (tit) tit.textContent = 'Kiosek · Revue ' + r.o;
      otevriDialog(dlg, box);
    }
    root.addEventListener('click', function (e) {
      var b = e.target.closest('[data-kiosek-cislo]');
      if (b) detail(b.getAttribute('data-kiosek-cislo'), b.getAttribute('data-shoda'));
    });
    vse('[data-kiosek-cislo]', d).forEach(function (b) {
      if (!root.contains(b)) b.addEventListener('click', function () { detail(b.getAttribute('data-kiosek-cislo')); });
    });

    // fulltext (mockup: titulky obálek, obsahy, popisy obálek)
    function hledej(q) {
      var nq = normalizuj(q).trim();
      if (nq.length < 2) { vysl.innerHTML = ''; if (stav) stav.textContent = ''; return; }
      var nalezy = [], cisla = {};
      DATA.forEach(function (r) {
        var zdroje = r.titulky.map(function (t) { return ['obálka', t]; }).concat(r.obsah.map(function (x) { return ['s. ' + x[0], x[1]]; }));
        if (r.obalka) zdroje.push(['obálka', r.obalka]);
        zdroje.forEach(function (z) {
          var n = normalizuj(z[1]), i = n.indexOf(nq);
          if (i > -1) {
            var t = z[1];
            var usek = esc(t.slice(0, i)) + '<mark>' + esc(t.slice(i, i + nq.length)) + '</mark>' + esc(t.slice(i + nq.length));
            nalezy.push({ o: r.o, strana: z[0], html: usek, rok: r.rok, c: r.c });
            cisla[r.o] = 1;
          }
        });
      });
      nalezy.sort(function (a, b) { return b.rok - a.rok || b.c - a.c; });
      var pc = Object.keys(cisla).length;
      if (stav) stav.textContent = nalezy.length ? 'Nalezeno ' + nalezy.length + '× v ' + pc + (pc === 1 ? ' čísle' : ' číslech') : 'Nic nenalezeno – zkuste jméno nebo slovo z titulku';
      vysl.innerHTML = nalezy.slice(0, 60).map(function (n) {
        return '<li><button type="button" data-kiosek-cislo="' + n.o + '" data-shoda="' + esc(nq) + '"><span class="vysl-cislo">' + n.o + '</span><span class="vysl-strana">' + n.strana + '</span><span class="vysl-text">' + n.html + '</span></button></li>';
      }).join('');
      vse('.obalka', root).forEach(function (o) { o.classList.toggle('je-shoda', !!cisla[o.getAttribute('data-kiosek-cislo')]); });
    }
    if (vstup && vysl) {
      var t = null;
      vstup.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { hledej(vstup.value); vypniPribeh(); }, 140); });
      vstup.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); hledej(vstup.value); } });
      var form = vstup.closest('form');
      if (form) form.addEventListener('submit', function (e) { e.preventDefault(); hledej(vstup.value); });
    }

    // příběh v obálkách
    var pribehBtn = vse('[data-kiosek-pribeh]', root);
    function vypniPribeh() {
      pribehBtn.forEach(function (b) { if (b.getAttribute('aria-disabled') !== 'true') b.setAttribute('aria-pressed', 'false'); });
      vse('.obalka', root).forEach(function (o) { o.classList.remove('je-mimo'); });
    }
    pribehBtn.forEach(function (b) {
      b.addEventListener('click', function () {
        if (b.getAttribute('aria-disabled') === 'true') {
          if (stav) stav.textContent = 'Tento příběh zveřejníme po souhlasu klubu';
          return;
        }
        var zap = b.getAttribute('aria-pressed') !== 'true';
        vypniPribeh();
        if (vstup) { vstup.value = ''; vysl.innerHTML = ''; }
        vse('.obalka', root).forEach(function (o) { o.classList.remove('je-shoda'); });
        if (!zap) { if (stav) stav.textContent = ''; return; }
        b.setAttribute('aria-pressed', 'true');
        var p = PRIBEHY[b.getAttribute('data-kiosek-pribeh')];
        var shody = DATA.filter(function (r) {
          var t = normalizuj(r.titulky.join(' ') + ' ' + r.obalka);
          return p.kmen.some(function (k) { return t.indexOf(k) > -1; });
        });
        var mapa = {}; shody.forEach(function (r) { mapa[r.o] = 1; });
        vse('.obalka', root).forEach(function (o) { o.classList.toggle('je-mimo', !mapa[o.getAttribute('data-kiosek-cislo')]); });
        var roky = shody.map(function (r) { return r.rok; });
        if (stav) stav.textContent = 'Příběh v obálkách · ' + p.jmeno + ' · ' + shody.length + (shody.length >= 5 ? ' čísel' : ' čísla') + (roky.length ? ' · ' + Math.min.apply(null, roky) + '–' + Math.max.apply(null, roky) : '');
      });
    });
  }
  vse('[data-kiosek]').forEach(kiosek);
})();
