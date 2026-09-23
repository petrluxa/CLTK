/* ==========================================================================
   I. ČLTK Praha – návrh A „Zlatá deska“ – sdílené chování (vlastník: CORE)
   Bez knihoven, bez sestavení. Funguje z file:// i z podsložky GitHub Pages.
   Jedna úvodní stránka: menu, přístupnost, triptych, deska, konfigurátor.
   ========================================================================== */
(function () {
  'use strict';

  var d = document;
  var html = d.documentElement;
  var CLTK = window.CLTK = window.CLTK || {};
  html.classList.add('js');

  /* ── Úložiště: jen pohodlí, vždy v try/catch ─────────────────────────── */
  CLTK.uloziste = {
    cti: function (klic) { try { return window.localStorage.getItem('cltk-a:' + klic); } catch (e) { return null; } },
    pis: function (klic, hodnota) { try { window.localStorage.setItem('cltk-a:' + klic, hodnota); } catch (e) { /* soukromé okno */ } },
    smaz: function (klic) { try { window.localStorage.removeItem('cltk-a:' + klic); } catch (e) { /* nic */ } }
  };

  /* ── Pomocníci ───────────────────────────────────────────────────────── */
  var $ = function (sel, koren) { return (koren || d).querySelector(sel); };
  var $$ = function (sel, koren) { return Array.prototype.slice.call((koren || d).querySelectorAll(sel)); };
  CLTK.$ = $; CLTK.$$ = $$;
  var NBSP = String.fromCharCode(160);
  /* každý prvek inicializovat jen jednou (CLTK.init lze volat opakovaně) */
  function jednou(el, klic) { var a = 'data-hotovo-' + klic; if (el.hasAttribute(a)) return false; el.setAttribute(a, ''); return true; }

  /* Čísla a ceny: „21 000 Kč“ s nezlomitelnými mezerami */
  CLTK.cislo = function (n) {
    return String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, NBSP);
  };
  CLTK.kc = function (n) { return CLTK.cislo(n) + NBSP + 'Kč'; };

  function mnozne(n, jedno, dve, pet) { return n === 1 ? jedno : (n >= 2 && n <= 4 ? dve : pet); }

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
      html.classList.toggle('je-odrolovano', (window.scrollY || window.pageYOffset) > 24);
    }
    window.addEventListener('scroll', function () { if (!cekam) { cekam = true; window.requestAnimationFrame(stav); } }, { passive: true });
    stav();
  }

  /* ── Mobilní menu: zámek rolování na <html> (Safari), Esc, past fokusu ─ */
  function initMenu() {
    var tl = $('.menu-tlacitko');
    var nav = tl && d.getElementById(tl.getAttribute('aria-controls'));
    if (!tl || !nav) return;
    var mq = window.matchMedia('(max-width: 1179.98px)');
    function otevri() {
      tl.setAttribute('aria-expanded', 'true');
      tl.querySelector('.menu-tlacitko__text').textContent = 'Zavřít';
      html.classList.add('menu-otevreno');
      var prvni = nav.querySelector('a');
      if (prvni) prvni.focus({ preventScroll: true });
    }
    function zavri(vratitFokus) {
      tl.setAttribute('aria-expanded', 'false');
      tl.querySelector('.menu-tlacitko__text').textContent = 'Menu';
      html.classList.remove('menu-otevreno');
      if (vratitFokus) tl.focus();
    }
    tl.addEventListener('click', function () { tl.getAttribute('aria-expanded') === 'true' ? zavri(false) : otevri(); });
    nav.addEventListener('click', function (e) { if (e.target.closest('a') && mq.matches) zavri(false); });
    d.addEventListener('keydown', function (e) {
      if (!html.classList.contains('menu-otevreno')) return;
      if (e.key === 'Escape') { zavri(true); return; }
      if (e.key !== 'Tab') return;
      var prvky = [tl].concat($$('a, button, input', nav).filter(function (x) { return x.offsetParent !== null; }));
      var i = prvky.indexOf(d.activeElement);
      if (e.shiftKey && (i <= 0)) { e.preventDefault(); prvky[prvky.length - 1].focus(); }
      else if (!e.shiftKey && i === prvky.length - 1) { e.preventDefault(); prvky[0].focus(); }
    });
    var zmena = function () { if (!mq.matches) zavri(false); };
    if (mq.addEventListener) mq.addEventListener('change', zmena); else if (mq.addListener) mq.addListener(zmena);
  }

  /* ── CS / EN: anglická verze zatím není – elegantní oznámení ─────────── */
  function initJazyk() {
    var pruh = d.getElementById('oznameni-en');
    d.addEventListener('click', function (e) {
      var b = e.target.closest('[data-jazyk]');
      if (b && pruh) {
        if (b.getAttribute('data-jazyk') === 'en') {
          pruh.hidden = false;
          var zavrit = pruh.querySelector('[data-oznameni-zavrit]');
          if (zavrit) zavrit.focus();
        } else {
          pruh.hidden = true;
        }
        return;
      }
      if (e.target.closest('[data-oznameni-zavrit]') && pruh) {
        pruh.hidden = true;
        var cs = $('.hlavicka [data-jazyk="en"]');
        if (cs && cs.offsetParent) cs.focus();
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
    /* pojistka: po 4 s ukázat vše (tisk, pomalé zařízení) */
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
    koren.dispatchEvent(new CustomEvent('zalozka', { detail: { id: tab.id }, bubbles: true }));
  }
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
  CLTK.aktivujZalozku = aktivujZalozku;

  /* ── Dvojí metr [data-metr] + radio name libovolné, value = metr ─────── */
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
      var typo = $('[data-trava-typo]', t);
      if (typo) {
        typo.addEventListener('click', function (e) {
          e.stopPropagation();
          var zap = typo.getAttribute('aria-pressed') !== 'true';
          typo.setAttribute('aria-pressed', String(zap));
          t.classList.toggle('trava--typo', zap);
          typo.textContent = zap ? typo.getAttribute('data-text-zpet') : typo.getAttribute('data-text-typo');
        });
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

  /* ── Ceník členství 2026 (obsah.json → clenstvi) ─────────────────────── */
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
    /* vstup: { druh: 'osobni'|'firemni', h, n, d, zv: ''|'do30'|'senior'|'mladez' }
       výstup: { stav: 'ok'|'nezname'|'neplatne'|'prazdne', cena, typ, druh, zprava } */
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
    /* popis složení domácnosti pro lidi: „2 hrající · 1 dítě“ */
    slozeni: function (v) {
      var c = [];
      if (+v.h) c.push(v.h + NBSP + 'hrající');
      if (+v.n) c.push(v.n + NBSP + 'nehrající');
      if (+v.d) c.push(v.d + NBSP + mnozne(+v.d, 'dítě', 'děti', 'dětí'));
      return c.join(' · ');
    }
  };

  /* ── Konfigurátor [data-konfigurator] – živá cena, náhled Členského listu */
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
          el.cena.textContent = CLTK.kc(r.cena);
          if (el.za) el.za.textContent = 'ročně · ceník ' + CLTK.clenstvi.rok;
          el.popis.textContent = r.typ;
          el.stav.textContent = '';
        } else {
          el.cena.textContent = r.stav === 'nezname' ? 'Na míru' : '—';
          if (el.za) el.za.textContent = '';
          el.popis.textContent = r.zprava;
          el.stav.textContent = r.stav === 'nezname' ? 'Stačí odeslat přihlášku, cenu potvrdí kancelář klubu.' : '';
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
      /* předvyplnění z adresy (?h=1&n=0&d=1&zv=do30&druh=osobni) nebo z úložiště */
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

  /* ── Konfigurátor: Pokračovat = další krok na místě (bez podstránky) ─── */
  function initDalsiKrok() {
    $$('form[data-konfigurator]').forEach(function (f) {
      var panel = $('[data-konf-dalsi]', f), mail = $('[data-konf-mail]', f);
      if (!panel) return;
      f.addEventListener('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(f);
        var v = { druh: fd.get('druh') || 'osobni', h: fd.get('h') || 0, n: fd.get('n') || 0, d: fd.get('d') || 0, zv: fd.get('zv') || '' };
        var r = CLTK.clenstvi.spocitej(v);
        if (r.stav === 'prazdne' || r.stav === 'neplatne') { var s = $('[data-konf-stav]', f); if (s) s.textContent = r.zprava; return; }
        if (mail) {
          var bezNbsp = function (t) { return String(t).split(NBSP).join(' '); };
          var telo = 'Dobrý den,\n\nmám zájem o členství v I. ČLTK Praha.\n' +
            (r.stav === 'ok' ? 'Vybráno: ' + r.typ + ' – ' + bezNbsp(CLTK.kc(r.cena)) + ' ročně (ceník ' + CLTK.clenstvi.rok + ').' : 'Složení: ' + bezNbsp(CLTK.clenstvi.slozeni(v)) + ' – prosím o nabídku.') +
            '\n\nDěkuji.';
          mail.href = 'mailto:stefkova@cltk.cz?subject=' + encodeURIComponent('Žádost o členství ' + CLTK.clenstvi.rok) + '&body=' + encodeURIComponent(telo);
        }
        panel.hidden = false;
        panel.focus({ preventScroll: true });
      });
      f.addEventListener('change', function () { panel.hidden = true; });
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
      var cil = d.getElementById(window.location.hash.slice(1));
      if (cil && cil.tagName === 'DETAILS') cil.open = true;
    }
  }

  /* ── Navigace po kotvách: aria-current podle sekce ve výhledu ─────────── */
  function initScrollspy() {
    var odkazy = $$('.nav a[href^="#"]:not(.btn)');
    var sekce = odkazy.map(function (a) { return d.getElementById(a.getAttribute('href').slice(1)); });
    if (!('IntersectionObserver' in window)) return;
    var viditelne = {};
    var io = new IntersectionObserver(function (zaznamy) {
      zaznamy.forEach(function (z) { viditelne[z.target.id] = z.isIntersecting; });
      var aktivni = null;
      sekce.forEach(function (s) { if (s && viditelne[s.id] && !aktivni) aktivni = s.id; });
      odkazy.forEach(function (a) {
        if (a.getAttribute('href') === '#' + aktivni && aktivni !== 'uvod') a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
      });
    }, { rootMargin: '-35% 0px -60% 0px' });
    sekce.forEach(function (s) { if (s) io.observe(s); });
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
    initMenu();
    initJazyk();
    initScrollspy();
    initOtevrit();
    initDalsiKrok();
    CLTK.init(d);
    initReveal();
    var prepocet;
    window.addEventListener('resize', function () { clearTimeout(prepocet); prepocet = setTimeout(function () { initBoxy(d); }, 200); });
  }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();
})();
