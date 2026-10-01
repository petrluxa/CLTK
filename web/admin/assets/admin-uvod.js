/* Administrace I. ČLTK Praha – pohodlí pro moduly úvodní stránky
   (lišta, galerie, výsledky, kalendář akcí, partneři, Texty a údaje).
   Bez knihoven. Všechno funguje i bez JavaScriptu – tohle jen pomáhá:
   živý náhled textu, přepínání částí formuláře podle typu, vlastní pole
   přihlášky (přidat / posunout / odebrat), náhled setů, zobrazení hesla.
   Tlačítka, která tu vznikají, mají type="button" – Enter v poli tak
   dál odešle jen Uložit. */
(function () {
  'use strict';

  /* ---------- živé zrcadlo textu (náhled lišty, desky) ----------
     <div data-zrcadlo="f-text" data-zrcadlo-prazdne="…"> přebírá text pole #f-text. */
  document.querySelectorAll('[data-zrcadlo]').forEach(function (cil) {
    var pole = document.getElementById(cil.getAttribute('data-zrcadlo'));
    if (!pole) return;
    var prazdne = cil.getAttribute('data-zrcadlo-prazdne') || '';
    var html = cil.hasAttribute('data-zrcadlo-inline');
    function prepis() {
      var v = pole.value.trim();
      if (!html) { cil.textContent = v || prazdne; return; }
      /* jen <em>, <strong>, <br> – stejně jako html_inline() na serveru */
      var t = (v || prazdne).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      t = t.replace(/&lt;(\/?)(em|strong|i|b)&gt;/gi, function (m, z, n) {
        n = n.toLowerCase(); if (n === 'i') n = 'em'; if (n === 'b') n = 'strong';
        return '<' + z + n + '>';
      }).replace(/&lt;br\s*\/?&gt;/gi, '<br>');
      cil.innerHTML = t;
    }
    pole.addEventListener('input', prepis);
  });

  /* ---------- počítadlo znaků s doporučenou délkou ---------- */
  document.querySelectorAll('[data-pocitadlo]').forEach(function (pole) {
    var doporuceno = parseInt(pole.getAttribute('data-pocitadlo'), 10) || 0;
    var info = document.createElement('div');
    info.className = 'uv-pocitadlo';
    info.setAttribute('aria-live', 'polite');
    pole.insertAdjacentElement('afterend', info);
    function prepocti() {
      var n = pole.value.length;
      info.textContent = n + ' znaků' + (doporuceno ? ' · doporučeno nejvýš ' + doporuceno : '');
      info.classList.toggle('je-dlouhe', doporuceno > 0 && n > doporuceno);
    }
    pole.addEventListener('input', prepocti);
    prepocti();
  });

  /* ---------- části formuláře podle vybraného typu ----------
     <select data-typ-prepinac="galerie"> + <div data-pro-typ="foto"> … */
  document.querySelectorAll('[data-typ-prepinac]').forEach(function (vyber) {
    var form = vyber.closest('form') || document;
    function prepni() {
      form.querySelectorAll('[data-pro-typ]').forEach(function (cast) {
        var typy = cast.getAttribute('data-pro-typ').split(/\s+/);
        cast.hidden = typy.indexOf(vyber.value) === -1;
      });
    }
    vyber.addEventListener('change', prepni);
    prepni();
  });

  /* ---------- zaškrtávátko odkrývá další pole ----------
     <input type="checkbox" data-odkryva="id-bloku"> */
  document.querySelectorAll('[data-odkryva]').forEach(function (ch) {
    var blok = document.getElementById(ch.getAttribute('data-odkryva'));
    if (!blok) return;
    function prepni() { blok.hidden = !ch.checked; }
    ch.addEventListener('change', prepni);
    prepni();
  });

  /* ---------- náhled setů výsledku („6:3 1:6 10:6“) ---------- */
  function rozloz(text) {
    var m = text.match(/\[?\d+\s*[:\-]\s*\d+\]?(?:\s*\(\s*\d+\s*[:\-]\s*\d+\s*\))?(?:\s*skr\.?)?/g) || [];
    return m.map(function (s) {
      s = s.replace(/[\[\]]/g, '').trim();
      var c = /^(\d+)\s*[:\-]\s*(\d+)\s*(.*)$/.exec(s);
      if (!c) return { hlavni: s, doplnek: '', vyhra: false };
      return { hlavni: c[1] + ':' + c[2], doplnek: c[3].trim(), vyhra: parseInt(c[1], 10) > parseInt(c[2], 10) };
    });
  }
  document.querySelectorAll('[data-sety-nahled]').forEach(function (cil) {
    var pole = document.getElementById(cil.getAttribute('data-sety-nahled'));
    if (!pole) return;
    function prekresli() {
      var sety = rozloz(pole.value);
      cil.innerHTML = '';
      if (!sety.length) {
        var t = document.createElement('span');
        t.className = 'uv-tlumene';
        t.textContent = pole.value.trim() ? 'Sety se nepodařilo přečíst – pište např. 6:3 1:6 10:6' : 'Náhled setů se ukáže tady.';
        cil.appendChild(t);
        return;
      }
      var obal = document.createElement('span');
      obal.className = 'uv-sety uv-sety--velke';
      sety.forEach(function (s) {
        var sp = document.createElement('span');
        if (!s.vyhra) sp.className = 'p';
        sp.textContent = s.hlavni;
        if (s.doplnek) { var d = document.createElement('small'); d.textContent = ' ' + s.doplnek; sp.appendChild(d); }
        obal.appendChild(sp);
      });
      cil.appendChild(obal);
    }
    pole.addEventListener('input', prekresli);
    prekresli();
  });

  /* ---------- kalendář akcí: rok a měsíc podle data „od“ ----------
     Jen dokud je člověk sám nezměnil (u kempů bývá měsíc v kalendáři jiný než den začátku). */
  var datumOd = document.querySelector('[data-akce-datum-od]');
  if (datumOd) {
    var rok = document.getElementById('f-rok');
    var mesic = document.getElementById('f-mesic');
    var posledni = datumOd.value;
    var zRuky = false;
    [rok, mesic].forEach(function (p) { if (p) p.addEventListener('change', function () { zRuky = true; }); });
    datumOd.addEventListener('change', function () {
      var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(datumOd.value) || /^(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})$/.exec(datumOd.value);
      if (!m) return;
      var r, me;
      if (m[0].indexOf('-') > 0) { r = m[1]; me = String(parseInt(m[2], 10)); } else { r = m[3]; me = String(parseInt(m[2], 10)); }
      var puvodni = /^(\d{4})-(\d{2})/.exec(posledni || '');
      var sedelo = !puvodni || (rok && rok.value === puvodni[1] && mesic && mesic.value === String(parseInt(puvodni[2], 10)));
      if (!zRuky || sedelo || !rok.value) {
        if (rok) rok.value = r;
        if (mesic) mesic.value = me;
        zRuky = false;
      }
      posledni = datumOd.value;
    });
  }

  /* ---------- vlastní pole přihlášky k akci ---------- */
  var seznam = document.querySelector('[data-vlastni-pole]');
  var sablona = document.querySelector('template[data-sablona-pole]');
  var pridat = document.querySelector('[data-pridat-pole]');
  if (seznam) {
    var citac = seznam.querySelectorAll('.uv-pole').length + 1000;

    function cisluj() {
      var radky = seznam.querySelectorAll('.uv-pole');
      radky.forEach(function (r, i) {
        var c = r.querySelector('[data-pole-cislo]');
        if (c) c.textContent = String(i + 1);
        var nah = r.querySelector('[data-pole-nahoru]');
        var dol = r.querySelector('[data-pole-dolu]');
        if (nah) nah.disabled = i === 0;
        if (dol) dol.disabled = i === radky.length - 1;
      });
      var prazdno = document.querySelector('[data-vlastni-prazdno]');
      if (prazdno) prazdno.hidden = radky.length > 0;
    }

    function nastavRadek(r) {
      var typ = r.querySelector('[data-pole-typ]');
      var moz = r.querySelector('.uv-pole__moznosti');
      function prepniTyp() { if (typ && moz) moz.hidden = typ.value !== 'vyber'; }
      if (typ) typ.addEventListener('change', prepniTyp);
      prepniTyp();
      var odebrat = r.querySelector('[data-pole-odebrat]');
      if (odebrat) odebrat.addEventListener('change', function () { r.classList.toggle('je-odebrane', odebrat.checked); });
    }

    seznam.querySelectorAll('.uv-pole').forEach(nastavRadek);

    /* sousední řádek pole (přeskočí <noscript> a jiné prvky) */
    function soused(r, smer) {
      var s = smer < 0 ? r.previousElementSibling : r.nextElementSibling;
      while (s && !s.classList.contains('uv-pole')) s = smer < 0 ? s.previousElementSibling : s.nextElementSibling;
      return s;
    }

    seznam.addEventListener('click', function (e) {
      var b = e.target.closest('button');
      if (!b || !seznam.contains(b)) return;
      var r = b.closest('.uv-pole');
      if (!r) return;
      var predchozi = soused(r, -1);
      var dalsi = soused(r, 1);
      if (b.hasAttribute('data-pole-nahoru') && predchozi) {
        seznam.insertBefore(r, predchozi);
      } else if (b.hasAttribute('data-pole-dolu') && dalsi) {
        seznam.insertBefore(dalsi, r);
      } else if (b.hasAttribute('data-pole-zrusit')) {
        r.parentNode.removeChild(r);           // nové, ještě neuložené pole
      } else {
        return;
      }
      cisluj();
      var form = seznam.closest('form');
      if (form) form.dispatchEvent(new Event('change'));   // hlídání neuložených změn
      b.focus();
    });

    if (pridat && sablona) {
      pridat.hidden = false;
      pridat.addEventListener('click', function () {
        var html = sablona.innerHTML.replace(/__N__/g, 'n' + (citac++));
        var obal = document.createElement('div');
        obal.innerHTML = html.trim();
        var r = obal.firstElementChild;
        seznam.appendChild(r);
        nastavRadek(r);
        cisluj();
        var prvni = r.querySelector('input[type=text]');
        if (prvni) prvni.focus();
      });
    }
    cisluj();
  }

  /* ---------- zobrazit / skrýt heslo ---------- */
  document.querySelectorAll('[data-ukaz-heslo]').forEach(function (b) {
    var pole = document.getElementById(b.getAttribute('data-ukaz-heslo'));
    if (!pole) return;
    b.hidden = false;
    b.addEventListener('click', function () {
      var skryte = pole.type === 'password';
      pole.type = skryte ? 'text' : 'password';
      b.textContent = skryte ? 'Skrýt' : 'Zobrazit';
      b.setAttribute('aria-pressed', skryte ? 'true' : 'false');
    });
  });
})();
