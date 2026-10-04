/* ==========================================================================
   I. ČLTK Praha · Kiosek I.ČLTK Revue (z Varianty 4 / návrhu 2 „Ostrov v čase“)
   Polička čísel po letech, hledání v titulcích a obsazích, příběh v obálkách,
   detail čísla v <dialog>. Styly: assets/css/v2.css (obal .v2).

   Data z databáze (revue.php):
     <?= json_skript('revue-data', revue_data_js()) ?>
   = [{o:'01/2026', rok, c, obalka, titulky:[…], obsah:[['06','Titulek'],…], stran, naklad, uzaverka, pdf, mb, img}, …]
   Kostra (viz web/PRUVODCE-STRANKY.md):
     <div class="kiosek" data-kiosek="revue-dialog" data-kiosek-poradi="stare|nove">
       <input data-kiosek-hledat> <p data-kiosek-stav> <ol data-kiosek-vysledky>
       <button class="cip" data-kiosek-pribeh="muchova" data-kmen="muchov" aria-pressed="false">Karolína Muchová</button>
       <ol class="policka" data-kiosek-policka></ol>      (obálky může vykreslit už server jako <a class="r-obalka"
                                                        href="PDF" data-kiosek-cislo="01/2026"> – pak zůstanou)
       [data-kiosek-jen-js] – části, které se ukážou až s JavaScriptem (hledání, příběhy)
     </div>
     <dialog class="dialog v2" id="revue-dialog"> … [data-dialog-titul] … [data-dialog-zavrit] … [data-dialog-obsah] </dialog>
   Otevírání a zavírání dialogu (i zámek rolování) obstará app.js (CLTK.dialog).
   ========================================================================== */
(function () {
  'use strict';
  var d = document;
  var C = window.CLTK || {};
  function vse(sel, root) { return [].slice.call((root || d).querySelectorAll(sel)); }
  var NBSP = ' ';
  /* 1 číslo · 2–4 čísla · 5+ čísel; „v 1 čísle“, „ve 4 číslech“ (ve před dvě, tři, čtyři, dvanáct… ) */
  function mnozne(n, jedno, dve, pet) { return n === 1 ? jedno : (n >= 2 && n <= 4 ? dve : pet); }
  function vPred(n) { var t = String(n); return /^[234]/.test(t) || /^1[234]$/.test(t) ? 've' : 'v'; }
  function normalizuj(s) { return String(s || '').replace(/\u00a0/g, ' ').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); }
  var esc = C.esc || function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  /* jen bezpečné adresy (http/https nebo cesta webu) */
  function adresa(u) { u = String(u || ''); return /^(https?:\/\/|\/)/i.test(u) && !/^\/\//.test(u) ? u : ''; }

  /* výchozí „příběhy v obálkách“ – tlačítko může mít vlastní data-kmen="kmen1 kmen2" */
  var PRIBEHY = {
    muchova: { jmeno: 'Karolína Muchová', kmen: ['muchov'] },
    hradecka: { jmeno: 'Lucie Hradecká', kmen: ['hradeck'] },
    kodes: { jmeno: 'Jan Kodeš', kmen: ['kodes'] },
    bartunkova: { jmeno: 'Nikola Bartůňková', kmen: ['bartunkov'] },
    vondrousova: { jmeno: 'Markéta Vondroušová', kmen: ['vondrous'] }
  };

  function otevri(dlg, obsah, titul) {
    if (C.dialog) { C.dialog.otevri(dlg, obsah, titul); return; }
    var cil = dlg.querySelector('[data-dialog-obsah]') || dlg; cil.innerHTML = ''; cil.appendChild(obsah);
    if (typeof dlg.showModal === 'function') dlg.showModal(); else dlg.setAttribute('open', '');
  }

  function kiosek(root) {
    var DATA = (C.data && C.data('revue-data')) || window.CLTK_REVUE || [];
    if (!DATA.length) return;
    var policka = root.querySelector('[data-kiosek-policka]');
    var vstup = root.querySelector('[data-kiosek-hledat]');
    var vysl = root.querySelector('[data-kiosek-vysledky]');
    var stav = root.querySelector('[data-kiosek-stav]');
    var dlg = d.getElementById(root.getAttribute('data-kiosek')) || root.querySelector('dialog');
    var poradi = root.getAttribute('data-kiosek-poradi') === 'nove' ? 'nove' : 'stare';
    var podleOznaceni = {};
    DATA.forEach(function (r) { r.titulky = r.titulky || []; r.obsah = r.obsah || []; podleOznaceni[r.o] = r; });

    /* části, které bez JavaScriptu nemají smysl (hledání, příběhy), se ukážou až teď */
    vse('[data-kiosek-jen-js]', root).forEach(function (el) { el.hidden = false; });

    /* polička po letech – když ji vykreslil server (revue.php), zůstane, jak je */
    if (policka && !policka.querySelector('.r-obalka')) {
      var roky = {};
      DATA.forEach(function (r) { (roky[r.rok] = roky[r.rok] || []).push(r); });
      var klice = Object.keys(roky).sort(function (a, b) { return poradi === 'nove' ? b - a : a - b; });
      var h = '';
      klice.forEach(function (rok) {
        var cisla = roky[rok].slice().sort(function (a, b) { return poradi === 'nove' ? b.c - a.c : a.c - b.c; });
        h += '<li class="policka-rok"><div class="policka-obalky">';
        cisla.forEach(function (r) {
          var img = adresa(r.img);
          h += '<button type="button" class="r-obalka' + (adresa(r.pdf) ? '' : ' r-obalka-bez-pdf') + '" data-kiosek-cislo="' + esc(r.o) + '" aria-label="I.ČLTK Revue ' + esc(r.o) + (r.titulky[0] ? ' – ' + esc(String(r.titulky[0]).toLowerCase()) : '') + '">' +
            (img ? '<img src="' + esc(img) + '" alt="" width="96" height="136" loading="lazy" decoding="async">' : '<span class="r-obalka-bez">' + esc(r.o) + '</span>') + '</button>';
        });
        h += '</div><span class="policka-rok-cislo" aria-hidden="true">' + esc(rok) + '</span></li>';
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
      var pdf = adresa(r.pdf), img = adresa(r.img);
      var box = d.createElement('div');
      box.className = 'revue-detail';
      box.innerHTML =
        '<div>' + (img ? '<img src="' + esc(img) + '" alt="Obálka I.ČLTK Revue ' + esc(r.o) + '" width="280" height="396">' : '') + '</div>' +
        '<div><p class="nadtitul">I.ČLTK Revue · ' + (r.c === 0 ? 'speciální číslo · ' : (r.c === 1 ? 'jarní' : 'podzimní') + ' číslo ') + esc(r.rok) + '</p>' +
        '<h3 class="t-d3">' + (r.c === 0 ? 'Speciální číslo ' : 'Číslo ') + esc(r.o) + '</h3>' +
        (r.titulky.length ? '<p class="perex" style="font-size:1.12rem">' + r.titulky.map(esc).join(' · ') + '</p>' : '') +
        (r.obalka ? '<p class="poznamka" style="margin-top:12px">Na obálce: ' + esc(r.obalka) + '</p>' : '') +
        (obs ? '<p class="nadtitul" style="margin:22px 0 0">Obsah čísla · strana</p><ol class="revue-detail-obsah">' + obs + '</ol>' : '') +
        '<div class="listek"><dl>' +
        (r.stran ? '<dt>Rozsah</dt><dd>' + esc(r.stran) + ' stran</dd>' : '') +
        (r.naklad ? '<dt>Náklad</dt><dd>' + esc(r.naklad) + '</dd>' : '') +
        (r.uzaverka ? '<dt>Uzávěrka</dt><dd>' + esc(r.uzaverka) + '</dd>' : '') +
        '<dt>PDF</dt><dd>' + (pdf ? '<a class="odkaz odkaz--ven" href="' + esc(pdf) + '" target="_blank" rel="noopener">Otevřít PDF' + (r.mb ? ' (' + String(r.mb).replace('.', ',') + '&nbsp;MB)' : '') + '<span class="vh"> (v novém okně)</span></a>' : 'PDF v archivu chybí <span class="jistota jistota--doplni">doplní klub</span>') + '</dd>' +
        '</dl></div></div>';
      otevri(dlg, box, 'Kiosek · Revue ' + r.o);
    }
    /* polička, která přetéká (mobil), jde rolovat i z klávesnice */
    if (policka && policka.scrollWidth > policka.clientWidth + 2 && !policka.hasAttribute('tabindex')) {
      policka.tabIndex = 0;
      policka.setAttribute('role', 'region');
    }

    /* obálka je bez JavaScriptu odkaz na PDF; s ním otevře detail čísla */
    root.addEventListener('click', function (e) {
      var b = e.target.closest('[data-kiosek-cislo]');
      if (!b || e.ctrlKey || e.metaKey || e.shiftKey || e.button > 0) return;
      if (!podleOznaceni[b.getAttribute('data-kiosek-cislo')] || !dlg) return;
      e.preventDefault();
      try { b.focus({ preventScroll: true }); } catch (x) { /* Safari při kliknutí odkaz nefokusuje – fokus se pak po zavření vrátí sem */ }
      detail(b.getAttribute('data-kiosek-cislo'), b.getAttribute('data-shoda'));
    });
    vse('[data-kiosek-cislo]', d).forEach(function (b) {
      if (!root.contains(b)) b.addEventListener('click', function (e) { e.preventDefault(); detail(b.getAttribute('data-kiosek-cislo')); });
    });

    /* hledání v titulcích obálek, obsazích a popisu obálky */
    function hledej(q) {
      var nq = normalizuj(q).trim();
      if (nq.length < 2) { vysl.innerHTML = ''; if (stav) stav.textContent = ''; vse('.r-obalka', root).forEach(function (o) { o.classList.remove('je-shoda'); }); return; }
      var nalezy = [], cisla = {};
      DATA.forEach(function (r) {
        var zdroje = r.titulky.map(function (t) { return ['obálka', t]; }).concat(r.obsah.map(function (x) { return ['s. ' + x[0], x[1]]; }));
        if (r.obalka) zdroje.push(['obálka', r.obalka]);
        zdroje.forEach(function (z) {
          var t = String(z[1] || ''), n = normalizuj(t), i = n.indexOf(nq);
          if (i > -1) {
            var usek = esc(t.slice(0, i)) + '<mark>' + esc(t.slice(i, i + nq.length)) + '</mark>' + esc(t.slice(i + nq.length));
            nalezy.push({ o: r.o, strana: z[0], html: usek, rok: r.rok, c: r.c });
            cisla[r.o] = 1;
          }
        });
      });
      nalezy.sort(function (a, b) { return b.rok - a.rok || b.c - a.c; });
      var pc = Object.keys(cisla).length;
      if (stav) stav.textContent = nalezy.length ? 'Nalezeno ' + nalezy.length + '× ' + vPred(pc) + NBSP + pc + NBSP + (pc === 1 ? 'čísle' : 'číslech') : 'Nic nenalezeno – zkuste jméno nebo slovo z' + NBSP + 'titulku';
      vysl.innerHTML = nalezy.slice(0, 60).map(function (n) {
        return '<li><button type="button" data-kiosek-cislo="' + esc(n.o) + '" data-shoda="' + esc(nq) + '"><span class="vysl-cislo">' + esc(n.o) + '</span><span class="vysl-strana">' + esc(n.strana) + '</span><span class="vysl-text">' + n.html + '</span></button></li>';
      }).join('');
      vse('.r-obalka', root).forEach(function (o) { o.classList.toggle('je-shoda', !!cisla[o.getAttribute('data-kiosek-cislo')]); });
    }
    var casovac = null;
    if (vstup && vysl) {
      vstup.addEventListener('input', function () { clearTimeout(casovac); casovac = setTimeout(function () { hledej(vstup.value); vypniPribeh(); }, 140); });
      vstup.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); hledej(vstup.value); } });
      var form = vstup.closest('form');
      if (form) form.addEventListener('submit', function (e) { e.preventDefault(); hledej(vstup.value); });
    }

    /* příběh v obálkách */
    var pribehBtn = vse('[data-kiosek-pribeh]', root);
    function vypniPribeh() {
      pribehBtn.forEach(function (b) { if (b.getAttribute('aria-disabled') !== 'true') b.setAttribute('aria-pressed', 'false'); });
      vse('.r-obalka', root).forEach(function (o) { o.classList.remove('je-mimo'); });
    }
    pribehBtn.forEach(function (b) {
      b.addEventListener('click', function () {
        if (b.getAttribute('aria-disabled') === 'true') { if (stav) stav.textContent = 'Tento příběh zveřejníme po souhlasu klubu'; return; }
        clearTimeout(casovac);                 // rozepsané hledání příběh nepřepíše
        var zap = b.getAttribute('aria-pressed') !== 'true';
        vypniPribeh();
        if (vstup && vysl) { vstup.value = ''; vysl.innerHTML = ''; }
        vse('.r-obalka', root).forEach(function (o) { o.classList.remove('je-shoda'); });
        if (!zap) { if (stav) stav.textContent = ''; return; }
        b.setAttribute('aria-pressed', 'true');
        var klic = b.getAttribute('data-kiosek-pribeh');
        var kmen = (b.getAttribute('data-kmen') || '').split(/\s+/).filter(Boolean);
        var p = kmen.length ? { jmeno: b.textContent.trim(), kmen: kmen.map(normalizuj) } : (PRIBEHY[klic] || { jmeno: b.textContent.trim(), kmen: [normalizuj(b.textContent.trim())] });
        var shody = DATA.filter(function (r) {
          var t = normalizuj(r.titulky.join(' ') + ' ' + (r.obalka || ''));
          return p.kmen.some(function (k) { return t.indexOf(k) > -1; });
        });
        var mapa = {}; shody.forEach(function (r) { mapa[r.o] = 1; });
        vse('.r-obalka', root).forEach(function (o) { o.classList.toggle('je-mimo', !mapa[o.getAttribute('data-kiosek-cislo')]); });
        var roky = shody.map(function (r) { return r.rok; });
        if (stav) stav.textContent = 'Příběh v' + NBSP + 'obálkách · ' + p.jmeno + ' · ' + shody.length + NBSP + mnozne(shody.length, 'číslo', 'čísla', 'čísel') + (roky.length ? ' · ' + Math.min.apply(null, roky) + '–' + Math.max.apply(null, roky) : '');
      });
    });
  }
  function start() { vse('[data-kiosek]').forEach(kiosek); }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();
})();
