/* ==========================================================================
   I. ČLTK Praha – sdílené chování webu (převzato z Varianty 4 a doplněné)
   Bez knihoven, bez sestavení. Načítá se na každé stránce (defer).
   – přístupnost (Omezit pohyb / Vyšší kontrast, jen localStorage)
   – hlavička: kompaktní stav, podmenu (najetí, kliknutí, klávesnice),
     mobilní menu se skupinami a zámkem rolování (Safari: tělo se zafixuje)
   – dialogy, záložky, rozbalování, dvojí metr, triptych, krokovač,
     konfigurátor členství, rolovací boxy tabulek, reveal
   Stránky mohou na nový obsah zavolat CLTK.init(koren).
   ========================================================================== */
(function () {
  'use strict';

  var d = document;
  var html = d.documentElement;
  var CLTK = window.CLTK = window.CLTK || {};
  html.classList.add('js');

  /* ── Úložiště: jen pohodlí, vždy v try/catch (soukromé okno, zablokovaná data) ── */
  CLTK.uloziste = {
    cti: function (klic) { try { return window.localStorage.getItem('cltk-a:' + klic); } catch (e) { return null; } },
    pis: function (klic, hodnota) { try { window.localStorage.setItem('cltk-a:' + klic, hodnota); } catch (e) { /* nic */ } },
    smaz: function (klic) { try { window.localStorage.removeItem('cltk-a:' + klic); } catch (e) { /* nic */ } }
  };

  /* ── Pomocníci ───────────────────────────────────────────────────────── */
  var $ = function (sel, koren) { return (koren || d).querySelector(sel); };
  var $$ = function (sel, koren) { return Array.prototype.slice.call((koren || d).querySelectorAll(sel)); };
  CLTK.$ = $; CLTK.$$ = $$;
  var NBSP = String.fromCharCode(160);
  /* každý prvek inicializovat jen jednou (CLTK.init lze volat opakovaně) */
  function jednou(el, klic) { var a = 'data-hotovo-' + klic; if (el.hasAttribute(a)) return false; el.setAttribute(a, ''); return true; }
  CLTK.jednou = jednou;
  CLTK.esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  };
  /* JSON vložený do stránky (<script type="application/json" id="…">) */
  CLTK.data = function (id) {
    var el = d.getElementById(id);
    if (!el) return null;
    try { return JSON.parse(el.textContent || 'null'); } catch (e) { return null; }
  };
  /* fokus bez odrolování (Safari jinak přepíše pozici) */
  CLTK.fokus = function (el) {
    if (!el) return;
    try { el.focus({ preventScroll: true }); } catch (e) { el.focus(); }
  };

  /* Čísla a ceny: „21 000 Kč“ s nezlomitelnými mezerami */
  CLTK.cislo = function (n) {
    return String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, NBSP);
  };
  CLTK.kc = function (n) { return CLTK.cislo(n) + NBSP + 'Kč'; };
  function mnozne(n, jedno, dve, pet) { return n === 1 ? jedno : (n >= 2 && n <= 4 ? dve : pet); }

  /* ── Zámek rolování: v Safari roluje <html>, overflow na <body> nestačí.
        Tělo se zafixuje a po odemčení se pozice vrátí. ─────────────────── */
  CLTK.zamek = (function () {
    var odstup = 0, pocet = 0;
    return {
      zamkni: function () {
        if (pocet++ > 0) return;
        odstup = window.pageYOffset || html.scrollTop || 0;
        var b = d.body;
        b.style.position = 'fixed';
        b.style.top = (-odstup) + 'px';
        b.style.left = '0';
        b.style.right = '0';
        b.style.width = '100%';
        html.style.overflow = 'hidden';
        html.classList.add('je-zamceno');
      },
      odemkni: function () {
        if (pocet === 0 || --pocet > 0) return;
        var b = d.body;
        b.style.position = b.style.top = b.style.left = b.style.right = b.style.width = '';
        html.style.overflow = '';
        html.classList.remove('je-zamceno');
        var sb = html.style.scrollBehavior;
        html.style.scrollBehavior = 'auto';
        window.scrollTo(0, odstup);
        html.style.scrollBehavior = sb;
      },
      jeZamceno: function () { return pocet > 0; }
    };
  })();

  /* ── Přístupnost: Omezit pohyb / Vyšší kontrast ──────────────────────── */
  var mqPohyb = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
  var mqKontrast = window.matchMedia ? window.matchMedia('(prefers-contrast: more)') : null;
  function stavPohybu() {
    var ulozeno = CLTK.uloziste.cti('pohyb');
    if (ulozeno === 'omezit' || ulozeno === 'plny') return ulozeno;
    return mqPohyb && mqPohyb.matches ? 'omezit' : 'plny';
  }
  function stavKontrastu() {
    var ulozeno = CLTK.uloziste.cti('kontrast');
    if (ulozeno === 'vyssi' || ulozeno === 'normalni') return ulozeno;
    return mqKontrast && mqKontrast.matches ? 'vyssi' : 'normalni';
  }
  CLTK.pohybPovolen = function () { return html.getAttribute('data-pohyb') !== 'omezit'; };
  function nastavPristupnost() {
    html.setAttribute('data-pohyb', stavPohybu());
    html.setAttribute('data-kontrast', stavKontrastu());
    html.classList.toggle('js-pohyb', CLTK.pohybPovolen());
    $$('[data-prepinac="pohyb"]').forEach(function (b) { b.setAttribute('aria-pressed', String(!CLTK.pohybPovolen())); });
    $$('[data-prepinac="kontrast"]').forEach(function (b) { b.setAttribute('aria-pressed', String(html.getAttribute('data-kontrast') === 'vyssi')); });
    if (!CLTK.pohybPovolen()) $$('[data-reveal]').forEach(function (el) { el.classList.add('in'); });
    d.dispatchEvent(new CustomEvent('cltk:pristupnost'));
  }
  function initPristupnost() {
    nastavPristupnost();
    d.addEventListener('click', function (e) {
      var b = e.target.closest('[data-prepinac]');
      if (!b) return;
      var co = b.getAttribute('data-prepinac');
      if (co === 'pohyb') CLTK.uloziste.pis('pohyb', CLTK.pohybPovolen() ? 'omezit' : 'plny');
      if (co === 'kontrast') CLTK.uloziste.pis('kontrast', html.getAttribute('data-kontrast') === 'vyssi' ? 'normalni' : 'vyssi');
      nastavPristupnost();
    });
  }

  /* ── Hlavička: kompaktní stav po odrolování ──────────────────────────── */
  function initHlavicka() {
    var cekam = false;
    function stav() {
      cekam = false;
      if (CLTK.zamek.jeZamceno()) return;
      html.classList.toggle('je-odrolovano', (window.scrollY || window.pageYOffset) > 24);
    }
    window.addEventListener('scroll', function () { if (!cekam) { cekam = true; window.requestAnimationFrame(stav); } }, { passive: true });
    stav();
  }

  /* ── Podmenu: odkaz + tlačítko <button aria-expanded>.
        Desktop: najetí myší, kliknutí, ↓ otevře a skočí na první odkaz,
        ↑/↓ v podmenu, Esc zavře a vrátí fokus, odchod fokusu zavře.
        Mobil: tlačítko rozbalí skupinu. ───────────────────────────────── */
  var mqDesktop = window.matchMedia('(min-width: 1240px)');
  var mqNajeti = window.matchMedia('(hover: hover) and (pointer: fine)');
  function initPodmenu() {
    var polozky = $$('.ma-podmenu');
    if (!polozky.length) return;
    function nastav(li, otevrit) {
      var tl = $('.nav__rozbal', li);
      li.classList.toggle('je-otevreno', otevrit);
      if (tl) tl.setAttribute('aria-expanded', String(otevrit));
      if (!otevrit) li.removeAttribute('data-najetim');
    }
    function zavriOstatni(krome) {
      polozky.forEach(function (li) { if (li !== krome && li.classList.contains('je-otevreno')) nastav(li, false); });
    }
    CLTK.zavriPodmenu = function () { zavriOstatni(null); };
    CLTK.nastavPodmenu = nastav;
    polozky.forEach(function (li) {
      var tl = $('.nav__rozbal', li);
      var casovac = null;
      if (!tl) return;
      tl.addEventListener('click', function () {
        var otevreno = li.classList.contains('je-otevreno');
        if (mqDesktop.matches) zavriOstatni(li);
        if (otevreno && li.hasAttribute('data-najetim')) { li.removeAttribute('data-najetim'); return; }   // otevřeno najetím → kliknutí ho „připíchne“
        nastav(li, !otevreno);
      });
      tl.addEventListener('keydown', function (e) {
        if (!mqDesktop.matches) return;
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          zavriOstatni(li);
          nastav(li, true);
          CLTK.fokus($('.podmenu a', li));
        }
      });
      li.addEventListener('mouseenter', function () {
        if (!mqDesktop.matches || !mqNajeti.matches) return;
        window.clearTimeout(casovac);
        if (!li.classList.contains('je-otevreno')) { zavriOstatni(li); nastav(li, true); li.setAttribute('data-najetim', ''); }
      });
      li.addEventListener('mouseleave', function () {
        if (!mqDesktop.matches || !mqNajeti.matches || !li.hasAttribute('data-najetim')) return;
        casovac = window.setTimeout(function () { if (!li.contains(d.activeElement) || d.activeElement === tl) nastav(li, false); }, 220);
      });
      li.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && li.classList.contains('je-otevreno') && mqDesktop.matches) {
          e.stopPropagation();
          nastav(li, false);
          tl.focus();
          return;
        }
        if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && e.target.closest('.podmenu')) {
          e.preventDefault();
          var odkazy = $$('.podmenu a', li);
          var i = odkazy.indexOf(e.target);
          var j = e.key === 'ArrowDown' ? Math.min(i + 1, odkazy.length - 1) : i - 1;
          if (j < 0) tl.focus(); else odkazy[j].focus();
        }
      });
      li.addEventListener('focusout', function (e) {
        if (mqDesktop.matches && e.relatedTarget && !li.contains(e.relatedTarget)) nastav(li, false);
      });
    });
    d.addEventListener('click', function (e) {
      if (mqDesktop.matches && !e.target.closest('.ma-podmenu')) zavriOstatni(null);
    });
    d.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && mqDesktop.matches) zavriOstatni(null);
    });
    var zmena = function () { zavriOstatni(null); };
    if (mqDesktop.addEventListener) mqDesktop.addEventListener('change', zmena); else if (mqDesktop.addListener) mqDesktop.addListener(zmena);
  }

  /* ── Mobilní menu: zámek rolování, Esc, past fokusu, skupiny ─────────── */
  function initMenu() {
    var tl = $('.menu-tlacitko');
    var nav = tl && d.getElementById(tl.getAttribute('aria-controls'));
    var hlavicka = $('.hlavicka');
    if (!tl || !nav) return;
    var text = $('.menu-tlacitko__text', tl);
    function otevri() {
      /* v režimu „s pruhem“ (správce) je hlavička níž – panel začne pod ní */
      if (hlavicka) nav.style.top = Math.max(0, Math.round(hlavicka.getBoundingClientRect().bottom)) + 'px';
      tl.setAttribute('aria-expanded', 'true');
      tl.setAttribute('aria-label', 'Zavřít menu');
      if (text) text.textContent = 'Zavřít';
      CLTK.zamek.zamkni();
      html.classList.add('menu-otevreno');
      /* skupina aktuální stránky je rozbalená */
      $$('.ma-podmenu.je-aktivni', nav).forEach(function (li) { if (CLTK.nastavPodmenu) CLTK.nastavPodmenu(li, true); });
      nav.scrollTop = 0;
      CLTK.fokus($('a, button', nav));
    }
    function zavri(vratitFokus) {
      if (tl.getAttribute('aria-expanded') !== 'true') return;
      tl.setAttribute('aria-expanded', 'false');
      tl.removeAttribute('aria-label');
      if (text) text.textContent = 'Menu';
      html.classList.remove('menu-otevreno');
      nav.style.top = '';
      if (CLTK.zavriPodmenu) CLTK.zavriPodmenu();
      CLTK.zamek.odemkni();
      if (vratitFokus) CLTK.fokus(tl);
    }
    CLTK.zavriMenu = zavri;
    tl.addEventListener('click', function () { tl.getAttribute('aria-expanded') === 'true' ? zavri(false) : otevri(); });
    nav.addEventListener('click', function (e) {
      var a = e.target.closest('a');
      if (a && !mqDesktop.matches) zavri(false);
    });
    d.addEventListener('keydown', function (e) {
      if (!html.classList.contains('menu-otevreno')) return;
      if (e.key === 'Escape') { zavri(true); return; }
      if (e.key !== 'Tab') return;
      var prvky = [tl].concat($$('a, button, input', nav).filter(function (x) { return x.offsetParent !== null; }));
      var i = prvky.indexOf(d.activeElement);
      if (e.shiftKey && i <= 0) { e.preventDefault(); prvky[prvky.length - 1].focus(); }
      else if (!e.shiftKey && i === prvky.length - 1) { e.preventDefault(); prvky[0].focus(); }
    });
    var zmena = function () { if (mqDesktop.matches) zavri(false); };
    if (mqDesktop.addEventListener) mqDesktop.addEventListener('change', zmena); else if (mqDesktop.addListener) mqDesktop.addListener(zmena);
  }

  /* ── Dialogy: <dialog class="dialog" id="…">, otevře [data-dialog-otevrit="id"],
        zavře [data-dialog-zavrit], Esc nebo klik mimo okno. ─────────────── */
  var posledniFokus = null;
  CLTK.dialog = {
    otevri: function (dlg, obsah, titul) {
      if (!dlg) return;
      posledniFokus = d.activeElement;
      if (obsah) {
        var cil = $('[data-dialog-obsah]', dlg) || dlg;
        cil.innerHTML = '';
        if (typeof obsah === 'string') cil.innerHTML = obsah; else cil.appendChild(obsah);
        CLTK.init(cil);
      }
      if (titul) { var t = $('[data-dialog-titul]', dlg); if (t) t.textContent = titul; }
      if (typeof dlg.showModal === 'function') { if (!dlg.open) dlg.showModal(); } else dlg.setAttribute('open', '');
      CLTK.zamek.zamkni();
      CLTK.fokus($('[data-dialog-zavrit]', dlg) || dlg);
    },
    zavri: function (dlg) {
      if (!dlg) return;
      if (typeof dlg.close === 'function' && dlg.open) dlg.close();
      else if (dlg.hasAttribute('open')) { dlg.removeAttribute('open'); zavrenoDialog(); }
    }
  };
  function zavrenoDialog() {
    CLTK.zamek.odemkni();
    if (posledniFokus && posledniFokus.focus) CLTK.fokus(posledniFokus);
  }
  function initDialogy() {
    $$('dialog').forEach(function (dlg) { if (jednou(dlg, 'dialog')) dlg.addEventListener('close', zavrenoDialog); });
    d.addEventListener('click', function (e) {
      var o = e.target.closest('[data-dialog-otevrit]');
      if (o) {
        var dlg = d.getElementById(o.getAttribute('data-dialog-otevrit'));
        if (dlg) { e.preventDefault(); CLTK.dialog.otevri(dlg); }
        return;
      }
      var z = e.target.closest('[data-dialog-zavrit]');
      if (z) { CLTK.dialog.zavri(z.closest('dialog')); return; }
      if (e.target.tagName === 'DIALOG' && e.target.open) {
        var r = e.target.getBoundingClientRect();
        if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) CLTK.dialog.zavri(e.target);
      }
    });
  }

  /* ── Reveal: jen vylepšení, pod omezeným pohybem nic neskrývá ─────────── */
  function initReveal() {
    var prvky = $$('[data-reveal]');
    if (!prvky.length) return;
    if (!('IntersectionObserver' in window) || !CLTK.pohybPovolen()) {
      prvky.forEach(function (el) { el.classList.add('in'); });
      return;
    }
    var io = new IntersectionObserver(function (zaznamy) {
      zaznamy.forEach(function (z) { if (z.isIntersecting) { z.target.classList.add('in'); io.unobserve(z.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    prvky.forEach(function (el) { io.observe(el); });
    window.setTimeout(function () { prvky.forEach(function (el) { el.classList.add('in'); }); }, 4000);
    window.addEventListener('beforeprint', function () { prvky.forEach(function (el) { el.classList.add('in'); }); });
  }

  /* ── Záložky [data-zalozky] s role=tablist, šipky, Home/End, #hash ───── */
  function aktivujZalozku(tab, fokus) {
    var seznam = tab.closest('[role="tablist"]');
    var koren = tab.closest('[data-zalozky]');
    $$('[role="tab"]', seznam).forEach(function (t) {
      var vybrano = t === tab;
      t.setAttribute('aria-selected', String(vybrano));
      t.tabIndex = vybrano ? 0 : -1;
      var panel = d.getElementById(t.getAttribute('aria-controls'));
      if (panel) panel.hidden = !vybrano;
    });
    if (fokus) tab.focus();
    if (koren) koren.dispatchEvent(new CustomEvent('zalozka', { detail: { id: tab.id }, bubbles: true }));
  }
  CLTK.aktivujZalozku = aktivujZalozku;
  function initZalozky(koren) {
    $$('[data-zalozky]', koren).forEach(function (z) {
      if (!jednou(z, 'zalozky')) return;
      var seznam = $('[role="tablist"]', z);
      if (!seznam) return;
      var taby = $$('[role="tab"]', seznam);
      taby.forEach(function (t) {
        t.addEventListener('click', function () { aktivujZalozku(t, false); });
        t.addEventListener('keydown', function (e) {
          var i = taby.indexOf(t), j = null;
          if (e.key === 'ArrowRight' || e.key === 'ArrowDown') j = (i + 1) % taby.length;
          if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') j = (i - 1 + taby.length) % taby.length;
          if (e.key === 'Home') j = 0;
          if (e.key === 'End') j = taby.length - 1;
          if (j !== null) { e.preventDefault(); aktivujZalozku(taby[j], true); }
        });
      });
      var h = window.location.hash.slice(1);
      var podleHash = h && taby.filter(function (t) { return t.getAttribute('data-hash') === h || t.getAttribute('aria-controls') === h; })[0];
      if (podleHash) aktivujZalozku(podleHash, false);
    });
  }

  /* ── Dvojí metr [data-metr] + radio [data-metr-volba] ─────────────────── */
  function initMetr(koren) {
    $$('[data-metr]', koren).forEach(function (m) {
      if (!jednou(m, 'metr')) return;
      var vystup = $('[data-metr-vystup]', m);
      function prepni(hodnota) {
        m.setAttribute('data-metr', hodnota);
        if (vystup) {
          var t = vystup.getAttribute('data-text-' + hodnota);
          if (t) vystup.textContent = t;
        }
      }
      $$('input[type="radio"][data-metr-volba]', m).forEach(function (r) {
        r.addEventListener('change', function () { if (r.checked) prepni(r.value); });
        if (r.checked) prepni(r.value);
      });
    });
  }

  /* ── Tři wimbledonské trávy [data-trava] ─────────────────────────────── */
  function initTravy(koren) {
    $$('[data-trava]', koren).forEach(function (t) {
      if (!jednou(t, 'trava')) return;
      var tl = $('[data-trava-prepinac]', t);
      var detail = tl && d.getElementById(tl.getAttribute('aria-controls'));
      function nastav(otevreno) {
        tl.setAttribute('aria-expanded', String(otevreno));
        if (detail) detail.hidden = !otevreno;
        t.classList.toggle('je-aktivni', otevreno);
      }
      if (tl) {
        tl.addEventListener('click', function () { nastav(tl.getAttribute('aria-expanded') !== 'true'); });
        var obraz = $('.trava__obraz', t);
        if (obraz) obraz.addEventListener('click', function (e) { if (!e.target.closest('button')) tl.click(); });
      }
    });
  }

  /* ── Krokovač [data-krokovac]: − [n] + ───────────────────────────────── */
  function initKrokovace(koren) {
    $$('[data-krokovac]', koren).forEach(function (k) {
      if (!jednou(k, 'krokovac')) return;
      var vstup = $('input', k);
      var minus = $('[data-krok="-1"]', k), plus = $('[data-krok="1"]', k);
      if (!vstup) return;
      var min = +vstup.min || 0, max = vstup.max === '' ? 99 : +vstup.max;
      function obnov() {
        var v = Math.max(min, Math.min(max, parseInt(vstup.value, 10) || 0));
        if (String(v) !== vstup.value) vstup.value = v;
        if (minus) minus.disabled = v <= min;
        if (plus) plus.disabled = v >= max;
      }
      function zmen(o) {
        vstup.value = (parseInt(vstup.value, 10) || 0) + o;
        obnov();
        vstup.dispatchEvent(new Event('input', { bubbles: true }));
      }
      if (minus) minus.addEventListener('click', function () { zmen(-1); });
      if (plus) plus.addEventListener('click', function () { zmen(1); });
      vstup.addEventListener('change', obnov);
      vstup.addEventListener('blur', obnov);
      obnov();
    });
  }

  /* ── Ceník členství pro konfigurátor. Výchozí hodnoty = ceník 2026
        (obsah.json → clenstvi); stránka je přepíše JSONem z databáze:
        <script type="application/json" id="clenstvi-ceny">{rok, hrajici:{dospely,do30,senior,mladez},
        rodinne:{"1-1-0":{cena,typ},…}, nehrajici:{cena,typ,podminka}, firemni:{cena,typ,podminka}}</script> ── */
  CLTK.clenstvi = {
    rok: 2026,
    hrajici: {
      dospely: { cena: 21000, typ: '1 dospělý hrající' },
      do30: { cena: 15500, typ: '1 dospělý hrající do 30 let' },
      senior: { cena: 10000, typ: 'seniorky od 67 let / senioři od 72 let (po–pá hraní jen do 14:00, so–ne bez omezení)' },
      mladez: { cena: 9500, typ: 'mládež 4–18 let / studující do 26 let' }
    },
    rodinne: {
      '1-1-0': { cena: 28000, typ: '1 dospělý hrající + 1 dospělý nehrající' },
      '1-0-1': { cena: 27000, typ: '1 dospělý hrající + 1 dítě' },
      '1-1-1': { cena: 34000, typ: '1 dospělý hrající + 1 dospělý nehrající + 1 dítě' },
      '1-1-2': { cena: 40000, typ: '1 dospělý hrající + 1 dospělý nehrající + 2 děti' },
      '2-0-0': { cena: 35500, typ: '2 dospělí hrající' },
      '2-0-1': { cena: 41500, typ: '2 dospělí hrající + 1 dítě' },
      '2-0-2': { cena: 47500, typ: '2 dospělí hrající + 2 děti' }
    },
    nehrajici: { cena: 10000, typ: 'nehrající', podminka: 'jen v návaznosti na hrající členství nebo při změně z hrajícího na nehrající' },
    firemni: { cena: 40000, typ: 'firemní', podminka: 'pro firmy s min. 2 přenosnými klubovými kartami' },
    spocitej: function (v) {
      v = v || {};
      if (v.druh === 'firemni') {
        return { stav: 'ok', cena: this.firemni.cena, typ: 'Firemní členství – ' + this.firemni.podminka, druh: 'firemni', kratce: 'Firemní členství' };
      }
      var h = +v.h || 0, n = +v.n || 0, dt = +v.d || 0, zv = v.zv || '';
      if (h + n + dt === 0) return { stav: 'prazdne', zprava: 'Zvolte alespoň jednu osobu.' };
      if (h === 0 && n > 0) return { stav: 'neplatne', zprava: 'Nehrající členství lze sjednat jen v návaznosti na hrající členství.' };
      if (h === 1 && n === 0 && dt === 0) {
        var jed = this.hrajici[zv] || this.hrajici.dospely;
        return { stav: 'ok', cena: jed.cena, typ: jed.typ, druh: 'individualni', kratce: 'Hrající členství' };
      }
      if (h === 0 && n === 0 && dt === 1) {
        return { stav: 'ok', cena: this.hrajici.mladez.cena, typ: this.hrajici.mladez.typ, druh: 'individualni', kratce: 'Hrající členství' };
      }
      var r = this.rodinne[h + '-' + n + '-' + dt];
      if (r) return { stav: 'ok', cena: r.cena, typ: r.typ, druh: 'rodinne', kratce: 'Rodinné členství', zvIgnorovano: !!zv };
      return { stav: 'nezname', zprava: 'Tuto kombinaci ceník neuvádí. Ozveme se vám s nabídkou na míru.' };
    },
    slozeni: function (v) {
      var c = [];
      if (+v.h) c.push(v.h + NBSP + 'hrající');
      if (+v.n) c.push(v.n + NBSP + 'nehrající');
      if (+v.d) c.push(v.d + NBSP + mnozne(+v.d, 'dítě', 'děti', 'dětí'));
      return c.join(' · ');
    }
  };
  function nactiCenyClenstvi() {
    var j = CLTK.data('clenstvi-ceny');
    if (!j || typeof j !== 'object') return;
    ['rok', 'hrajici', 'rodinne', 'nehrajici', 'firemni'].forEach(function (k) { if (j[k]) CLTK.clenstvi[k] = j[k]; });
  }

  /* ── Konfigurátor [data-konfigurator] – živá cena, náhled Členského listu ── */
  function initKonfiguratory(koren) {
    $$('form[data-konfigurator]', koren).forEach(function (f) {
      if (!jednou(f, 'konfigurator')) return;
      var el = {
        cena: $('[data-konf-cena]', f), za: $('[data-konf-za]', f), popis: $('[data-konf-popis]', f), stav: $('[data-konf-stav]', f),
        zv: $('[data-konf-zvyhodneni]', f), osoby: $('[data-konf-osoby]', f)
      };
      var list = f.getAttribute('data-list') ? d.getElementById(f.getAttribute('data-list')) : null;
      function hodnoty() {
        var fd = new FormData(f);
        return { druh: fd.get('druh') || 'osobni', h: fd.get('h') || 0, n: fd.get('n') || 0, d: fd.get('d') || 0, zv: fd.get('zv') || '' };
      }
      function vykresli() {
        var v = hodnoty();
        var jenJeden = +v.h === 1 && !+v.n && !+v.d;
        if (el.zv) el.zv.disabled = v.druh === 'firemni' || !jenJeden;
        if (el.osoby) el.osoby.disabled = v.druh === 'firemni';
        if (el.zv && el.zv.disabled) v.zv = '';
        var r = CLTK.clenstvi.spocitej(v);
        f.setAttribute('data-stav', r.stav);
        if (r.stav === 'ok') {
          if (el.cena) el.cena.textContent = CLTK.kc(r.cena);
          if (el.za) el.za.textContent = 'ročně · ceník ' + CLTK.clenstvi.rok;
          if (el.popis) el.popis.textContent = r.typ;
          if (el.stav) el.stav.textContent = '';
        } else {
          if (el.cena) el.cena.textContent = r.stav === 'nezname' ? 'Na míru' : '—';
          if (el.za) el.za.textContent = '';
          if (el.popis) el.popis.textContent = r.zprava;
          if (el.stav) el.stav.textContent = r.stav === 'nezname' ? 'Stačí odeslat přihlášku, cenu potvrdí kancelář klubu.' : '';
        }
        if (list) {
          var typ = $('[data-list-typ]', list), cena = $('[data-list-cena]', list), druh = $('[data-list-druh]', list);
          if (druh) druh.textContent = r.stav === 'ok' ? r.kratce : 'Členství';
          if (typ) typ.textContent = r.stav === 'ok' ? r.typ : (CLTK.clenstvi.slozeni(v) || '—');
          if (cena) cena.textContent = r.stav === 'ok' ? CLTK.kc(r.cena) : 'na míru';
        }
        CLTK.uloziste.pis('konfigurace', JSON.stringify(v));
        f.dispatchEvent(new CustomEvent('konfigurace', { detail: { vstup: v, vysledek: r }, bubbles: true }));
      }
      try {
        var q = new URLSearchParams(window.location.search);
        var ulozeno = q.has('h') || q.has('druh') ? null : JSON.parse(CLTK.uloziste.cti('konfigurace') || 'null');
        ['h', 'n', 'd'].forEach(function (k) {
          var hod = q.get(k) || (ulozeno && ulozeno[k]);
          var inp = f.elements[k];
          if (inp && hod !== null && hod !== undefined && hod !== '') inp.value = hod;
        });
        ['zv', 'druh'].forEach(function (k) {
          var hod = q.get(k) || (ulozeno && ulozeno[k]);
          if (hod === null || hod === undefined) return;
          $$('input[name="' + k + '"]', f).forEach(function (r) { r.checked = r.value === hod; });
        });
      } catch (e) { /* bez úložiště */ }
      f.addEventListener('input', vykresli);
      f.addEventListener('change', vykresli);
      initKrokovace(f);
      vykresli();
    });
  }

  /* ── Rolovací boxy tabulek: fokus z klávesnice, jen když přetékají ───── */
  function initBoxy(koren) {
    $$('.tabulka-box, .police', koren).forEach(function (b) {
      if (b.scrollWidth > b.clientWidth + 2) {
        b.tabIndex = 0;
        if (!b.hasAttribute('role')) b.setAttribute('role', 'region');
        if (!b.hasAttribute('aria-label')) {
          var cap = b.querySelector('caption');
          b.setAttribute('aria-label', cap ? cap.textContent.trim() : 'Posuvný obsah');
        }
      }
    });
  }

  /* ── Odkaz na rozbalovací blok: <a data-otevrit=id> otevře <details id> ─ */
  function initOtevrit() {
    d.addEventListener('click', function (e) {
      var a = e.target.closest('[data-otevrit]');
      if (!a) return;
      var det = d.getElementById(a.getAttribute('data-otevrit'));
      if (det && det.tagName === 'DETAILS') det.open = true;
    });
    if (window.location.hash) {
      var cil = null;
      try { cil = d.getElementById(decodeURIComponent(window.location.hash.slice(1))); } catch (e) { /* nic */ }
      if (cil && cil.tagName === 'DETAILS') cil.open = true;
    }
  }

  /* ── Inicializace (stránky mohou volat CLTK.init(koren) na nový obsah) ─ */
  CLTK.init = function (koren) {
    koren = koren || d;
    initZalozky(koren);
    initMetr(koren);
    initTravy(koren);
    initKonfiguratory(koren);
    initKrokovace(koren);
    initBoxy(koren);
  };

  function start() {
    initPristupnost();
    initHlavicka();
    initPodmenu();
    initMenu();
    initDialogy();
    initOtevrit();
    nactiCenyClenstvi();
    CLTK.init(d);
    initReveal();
    var prepocet;
    window.addEventListener('resize', function () { clearTimeout(prepocet); prepocet = setTimeout(function () { initBoxy(d); }, 200); });
  }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();
})();
