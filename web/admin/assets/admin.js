/* Administrace I. ČLTK Praha – drobné chování rozhraní.
   Bez knihoven. Funguje i bez JavaScriptu (jen bez pohodlí). */
(function () {
  'use strict';

  /* ---------- menu na mobilu ----------
     Zámek rolování patří na <html> (Safari roluje kořen, ne <body>). */
  var btn = document.getElementById('menuBtn');
  var side = document.getElementById('side');
  var clona = document.getElementById('sideClona');
  function menu(otevrit) {
    if (!side || !btn) return;
    side.classList.toggle('open', otevrit);
    btn.setAttribute('aria-expanded', otevrit ? 'true' : 'false');
    document.documentElement.classList.toggle('menu-otevrene', otevrit);
    if (clona) clona.hidden = !otevrit;
    if (otevrit) {
      var prvni = side.querySelector('a');
      if (prvni) { try { prvni.focus({ preventScroll: true }); } catch (e) { prvni.focus(); } }
    }
  }
  if (btn) btn.addEventListener('click', function () { menu(!side.classList.contains('open')); });
  if (clona) clona.addEventListener('click', function () { menu(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && side && side.classList.contains('open')) { menu(false); btn.focus(); }
  });

  /* ---------- potvrzení před odesláním (mazání) ---------- */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f && f.getAttribute && f.getAttribute('data-potvrdit')) {
      if (!window.confirm(f.getAttribute('data-potvrdit'))) e.preventDefault();
    }
  }, true);

  /* ---------- náhled vybraného obrázku ---------- */
  document.querySelectorAll('[data-obr-vstup]').forEach(function (vstup) {
    vstup.addEventListener('change', function () {
      var obal = vstup.closest('[data-obr-pole]');
      if (!obal || !vstup.files || !vstup.files[0] || !window.URL) return;
      var img = obal.querySelector('[data-obr-nahled]');
      if (!img) return;
      img.src = URL.createObjectURL(vstup.files[0]);
      img.alt = 'Náhled vybraného obrázku';
      var prev = img.closest('.obr-prev');
      if (prev) prev.hidden = false;
    });
  });

  /* ---------- ohnisko fotky: klepnutí do náhledu nastaví „x% y%“ ---------- */
  document.querySelectorAll('[data-fokus]').forEach(function (obal) {
    var obraz = obal.querySelector('.fokus__obraz');
    var bod = obal.querySelector('.fokus__bod');
    var vstup = obal.querySelector('[data-fokus-vstup]');
    if (!obraz || !bod || !vstup) return;
    function ukaz() {
      var m = /^\s*([\d.]+)%\s+([\d.]+)%\s*$/.exec(vstup.value);
      if (!m) return;
      bod.style.left = m[1] + '%';
      bod.style.top = m[2] + '%';
    }
    obraz.addEventListener('click', function (e) {
      var r = obraz.getBoundingClientRect();
      var x = Math.round(Math.max(0, Math.min(100, (e.clientX - r.left) / r.width * 100)));
      var y = Math.round(Math.max(0, Math.min(100, (e.clientY - r.top) / r.height * 100)));
      vstup.value = x + '% ' + y + '%';
      ukaz();
    });
    vstup.addEventListener('input', ukaz);
    ukaz();
  });

  /* ---------- tlačítka „Zlatá kurzíva“ a „Nový řádek“ u polí, která smí <em> / <br> ----------
     Pole se pozná podle nápovědy nebo vzoru, který <em> zmiňuje – člověk pak nemusí psát
     značky ručně: označí slovo a klepne. Server text čistí stejně jako dřív (jen <em>, <br>). */
  document.querySelectorAll('.field input[type="text"], .field input:not([type]), .field textarea').forEach(function (pole) {
    var field = pole.closest('.field');
    if (!field || field.querySelector('[data-znacky]')) return;
    var napoveda = (field.textContent || '') + ' ' + (pole.getAttribute('placeholder') || '');
    if (napoveda.indexOf('<em>') === -1 && napoveda.indexOf('<em') === -1) return;
    var sBr = napoveda.indexOf('<br>') !== -1;
    var lista = document.createElement('div');
    lista.className = 'znacky';
    lista.setAttribute('data-znacky', '');
    function tlacitko(text, popis, pred, za) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn btn-sm btn-ghost';
      b.textContent = text;
      b.setAttribute('aria-label', popis);
      b.addEventListener('click', function () {
        var od = pole.selectionStart, po = pole.selectionEnd, v = pole.value;
        if (od === null || od === undefined) { od = po = v.length; }
        var vyber = v.slice(od, po);
        pole.value = v.slice(0, od) + pred + vyber + za + v.slice(po);
        var kurzor = vyber ? od + pred.length + vyber.length + za.length : od + pred.length;
        pole.focus();
        try { pole.setSelectionRange(kurzor, kurzor); } catch (e) {}
        pole.dispatchEvent(new Event('input', { bubbles: true }));
      });
      lista.appendChild(b);
    }
    tlacitko('Zlatá kurzíva', 'Označené slovo zlatou kurzívou', '<em>', '</em>');
    if (sBr) tlacitko('Nový řádek', 'Vložit nový řádek', '<br>', '');
    pole.insertAdjacentElement('afterend', lista);
  });

  /* ---------- varování před odchodem s neuloženými změnami ---------- */
  document.querySelectorAll('form[data-hlidat-zmeny]').forEach(function (f) {
    var zmeneno = false;
    f.addEventListener('input', function () { zmeneno = true; });
    f.addEventListener('change', function () { zmeneno = true; });
    f.addEventListener('submit', function () { zmeneno = false; });
    window.addEventListener('beforeunload', function (e) {
      if (zmeneno) { e.preventDefault(); e.returnValue = ''; }
    });
  });
})();
