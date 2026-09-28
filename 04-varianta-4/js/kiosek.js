/* ==========================================================================
   Varianta 4 · Kiosek I.ČLTK Revue (převzato z návrhu 2 „Ostrov v čase“)
   Polička 41 čísel, hledání v titulcích a obsazích, příběh v obálkách,
   detail čísla v <dialog>. Data: js/data-revue.js (window.CLTK_REVUE).
   ========================================================================== */
(function () {
  'use strict';
  var d = document, html = d.documentElement;
  function vse(sel, root) { return [].slice.call((root || d).querySelectorAll(sel)); }
  function normalizuj(s) { return String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  /* ── dialog ── */
  var posledniFokus = null;
  function otevriDialog(dlg, obsah) {
    if (!dlg) return;
    posledniFokus = d.activeElement;
    if (obsah) { var cil = dlg.querySelector('[data-dialog-obsah]') || dlg; cil.innerHTML = ''; cil.appendChild(obsah); }
    if (typeof dlg.showModal === 'function') { if (!dlg.open) dlg.showModal(); } else dlg.setAttribute('open', '');
    html.classList.add('dialog-otevren');
    var z = dlg.querySelector('[data-dialog-zavrit]'); if (z) z.focus();
  }
  function zavriDialog(dlg) {
    if (!dlg) return;
    if (typeof dlg.close === 'function' && dlg.open) dlg.close(); else { dlg.removeAttribute('open'); zavreno(); }
  }
  function zavreno() {
    html.classList.remove('dialog-otevren');
    if (posledniFokus && posledniFokus.focus) { try { posledniFokus.focus({ preventScroll: true }); } catch (e) { posledniFokus.focus(); } }
  }
  d.addEventListener('click', function (e) {
    var z = e.target.closest && e.target.closest('[data-dialog-zavrit]');
    if (z) { zavriDialog(z.closest('dialog')); return; }
    if (e.target.tagName === 'DIALOG' && e.target.open) {
      var r = e.target.getBoundingClientRect();
      if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) zavriDialog(e.target);
    }
  });
  vse('dialog.v2').forEach(function (dlg) { dlg.addEventListener('close', zavreno); });

  /* ── kiosek Revue: polička, hledání, příběh v obálkách, detail čísla ── */
  var PRIBEHY = {
    muchova: { jmeno: 'Karolína Muchová', kmen: ['muchov'] },
    hradecka: { jmeno: 'Lucie Hradecká', kmen: ['hradeck'] },
    kodes: { jmeno: 'Jan Kodeš', kmen: ['kodes'] },
    bartunkova: { jmeno: 'Nikola Bartůňková', kmen: ['bartunkov'] },
    vondrousova: { jmeno: 'Markéta Vondroušová', kmen: ['vondrous'] }
  };
  
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
          h += '<button type="button" class="r-obalka' + (r.pdf ? '' : ' r-obalka-bez-pdf') + '" data-kiosek-cislo="' + r.o + '" aria-label="I.ČLTK Revue ' + r.o + (r.titulky[0] ? ' – ' + esc(r.titulky[0].toLowerCase()) : '') + '">' +
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
      vse('.r-obalka', root).forEach(function (o) { o.classList.toggle('je-shoda', !!cisla[o.getAttribute('data-kiosek-cislo')]); });
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
      vse('.r-obalka', root).forEach(function (o) { o.classList.remove('je-mimo'); });
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
        vse('.r-obalka', root).forEach(function (o) { o.classList.remove('je-shoda'); });
        if (!zap) { if (stav) stav.textContent = ''; return; }
        b.setAttribute('aria-pressed', 'true');
        var p = PRIBEHY[b.getAttribute('data-kiosek-pribeh')];
        var shody = DATA.filter(function (r) {
          var t = normalizuj(r.titulky.join(' ') + ' ' + r.obalka);
          return p.kmen.some(function (k) { return t.indexOf(k) > -1; });
        });
        var mapa = {}; shody.forEach(function (r) { mapa[r.o] = 1; });
        vse('.r-obalka', root).forEach(function (o) { o.classList.toggle('je-mimo', !mapa[o.getAttribute('data-kiosek-cislo')]); });
        var roky = shody.map(function (r) { return r.rok; });
        if (stav) stav.textContent = 'Příběh v obálkách · ' + p.jmeno + ' · ' + shody.length + (shody.length >= 5 ? ' čísel' : ' čísla') + (roky.length ? ' · ' + Math.min.apply(null, roky) + '–' + Math.max.apply(null, roky) : '');
      });
    });
  }
  function start() { vse('[data-kiosek]').forEach(kiosek); }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();
})();
