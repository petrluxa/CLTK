/* ==========================================================================
   I. ČLTK Praha – plán areálu Léto / Zima (areal.php)
   Kresba převzatá z návrhu 3 „Živá Štvanice“ (podle plánu areálu 09-2025),
   vysázená v jazyce Varianty 4. Kresba = poloha kurtů a služeb a letní povrch
   (to je obsah plánu). Všechno ostatní je z databáze:
     – zimní haly, zimní povrch a „v zimě uzavřeno“ podle sekcí zimního ceníku
       (popis sekce „kurty 5, 6 · …“ určuje, které kurty pod halou jsou),
     – ceny z ceníků kurtů (cenik.js → CLTK.ceniky),
     – služby (název, perex, časy) z modulu Areál a služby podle kotvy.
   Data: <script type="application/json" id="mapa-data">
     {ceniky: {leto, zima, dnes, ladeni}, sluzby: [{kotva, nazev, perex, casy}],
      rezervace, cenik_url}</script>
   Bez JavaScriptu zůstane obrázek plánu (.mapa__zaloha) a odkaz na PDF.
   ========================================================================== */
(function () {
  'use strict';

  var d = document;
  var CLTK = window.CLTK = window.CLTK || {};
  var NB = String.fromCharCode(160);
  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  };
  function typo(s) { return CLTK.ceniky ? CLTK.ceniky.typo(s) : esc(s); }
  function mnozne(n, a, b, c) { return n === 1 ? a : (n >= 2 && n <= 4 ? b : c); }

  /* ── Kresba: souřadnice v plánu 2000 × 1178, výřez y 120–1100 ─────────── */
  var VYREZ = { y0: 120, v: 980 };
  function bodY(yPct) { return ((yPct / 100 * 1178) - VYREZ.y0) / VYREZ.v * 100; }

  /* kurty: id, poloha v % plánu, letní povrch, skupina */
  var KURTY = [
    ['1', 50.7, 37, 'antuka'], ['2', 71.6, 53, 'antuka'], ['3', 75.4, 53, 'antuka'], ['4', 79.3, 53, 'antuka'],
    ['5', 68.3, 35, 'antuka'], ['6', 72.5, 35, 'antuka'], ['7', 77.4, 35, 'hard'], ['8', 81.4, 35, 'hard'], ['9', 86.4, 35, 'hard'],
    ['C', 55.1, 73.3, 'hard'], ['P1', 44.65, 71.73, 'pevna'], ['P2', 48.6, 71.73, 'pevna'],
    ['10', 31.8, 34, 'antuka'], ['11', 26.8, 34, 'antuka'], ['12', 22.5, 34, 'antuka'], ['13', 17.4, 34, 'antuka'],
    ['14', 13.1, 34, 'antuka'], ['15', 8.1, 34, 'antuka'], ['16', 3.6, 34, 'antuka']
  ].map(function (k) { return { id: k[0], x: k[1], y: k[2], leto: k[3] }; });

  /* služby: kotva z modulu Areál a služby → poloha na plánu */
  var SLUZBY = {
    'recepce': [53.1, 48.6], 'bazen': [62.5, 35], 'fitness': [49.2, 25.5], 'wellness': [52.8, 25.5],
    'fyzioterapie': [56.6, 28.8], 'tenis-shop': [53.3, 59.2], 'salonek': [56.9, 59.2], 'restaurace': [60.4, 59.2],
    'parkoviste': [39.2, 54.2], 'beach-volejbal': [30, 48.2], 'hriste-slavoj': [20.7, 48.2], 'hriste-trojkurt': [74.9, 63.1]
  };

  /* přetlakové haly (zima) nad skupinami kurtů: x, y, š, v, kurty */
  var HALY = [[962, 340, 102, 193, '1'], [1378, 551, 265, 164, '2 3 4'], [1317, 323, 181, 179, '5 6'],
              [1497, 323, 181, 179, '7 8'], [1676, 323, 106, 179, '9'], [1052, 765, 98, 190, 'C']];

  function lajny(cx, cy, w, h, vodorovne) {
    if (vodorovne) { var t = w; w = h; h = t; }
    var x = cx - w / 2, y = cy - h / 2, i = w * 0.125, s = h * 0.269;
    var dd = 'M' + x + ' ' + y + 'h' + w + 'v' + h + 'h' + (-w) + 'z' +
      'M' + (x + i) + ' ' + y + 'v' + h + 'M' + (x + w - i) + ' ' + y + 'v' + h +
      'M' + (x + i) + ' ' + (cy - s) + 'h' + (w - 2 * i) + 'M' + (x + i) + ' ' + (cy + s) + 'h' + (w - 2 * i) +
      'M' + cx + ' ' + (cy - s) + 'v' + (2 * s);
    var sit = '<path class="m-lajny m-lajny--tenke" d="M' + (x - 4) + ' ' + cy + 'h' + (w + 8) + '" stroke-dasharray="3 2"/>';
    if (vodorovne) return '<g transform="rotate(90 ' + cx + ' ' + cy + ')"><path class="m-lajny" d="' + dd + '"/>' + sit + '</g>';
    return '<path class="m-lajny" d="' + dd + '"/>' + sit;
  }

  function svg(stav) {
    var s = '<svg class="mapa__svg" viewBox="0 120 2000 980" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">';
    s += '<defs><pattern id="m-prosiv" width="26" height="26" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0H26M0 0V26" class="m-prosiv"/></pattern>' +
      '<pattern id="m-rady" width="8" height="8" patternUnits="userSpaceOnUse"><path d="M0 4H8" class="m-rada"/></pattern>' +
      '<pattern id="m-rady-v" width="8" height="8" patternUnits="userSpaceOnUse"><path d="M4 0V8" class="m-rada"/></pattern></defs>';
    s += '<rect class="m-voda" x="0" y="120" width="2000" height="980"/>';
    [[150, 138], [520, 132], [980, 130], [1480, 136], [260, 1086], [760, 1090], [1300, 1088], [1760, 1084]].forEach(function (v) {
      s += '<path class="m-vlnka" d="M' + v[0] + ' ' + v[1] + 'q14 -7 28 0t28 0t28 0"/>';
    });
    s += '<text class="m-popis m-popis--reka" x="1270" y="155">Vltava</text>';
    s += '<path class="m-zem" d="M0 196 C 240 150 520 142 700 150 L 840 146 C 1200 126 1620 140 2000 162 L 2000 1062 C 1600 1078 1150 1082 840 1074 L 700 1070 C 420 1078 180 1064 0 1046 Z"/>';
    s += '<path class="m-park" d="M0 700 L 240 700 L 330 650 L 695 650 L 695 1068 C 420 1076 180 1062 0 1044 Z"/>';
    s += '<path class="m-park" d="M1500 830 L 1880 650 L 2000 640 L 2000 1060 C 1750 1072 1400 1078 1180 1080 L 1300 1000 Z"/>';
    s += '<path class="m-cesta" d="M5 262 L 695 232 L 695 648 L 330 648 L 238 690 L 5 690 Z"/>';
    s += '<path class="m-cesta" d="M838 222 L 1790 282 L 1848 330 L 1874 640 L 1470 836 L 1292 906 L 1182 990 L 838 990 Z"/>';
    s += '<path class="m-cesta" d="M757 120 H835 V1100 H757 Z"/>';
    s += '<path class="m-cesta m-cesta--slaba" d="M838 990 L 1182 990 L 1300 1006 L 2000 1014 L 2000 1040 L 838 1040 Z"/>';
    s += '<path class="m-budova" d="M840 226 L 1174 250 L 1174 520 L 1150 520 L 1150 642 L 1288 702 L 1288 904 L 1180 986 L 840 986 L 840 722 L 896 662 L 840 604 Z"/>';
    s += '<rect class="m-viadukt" x="697" y="120" width="60" height="980"/>';
    s += '<path class="m-kolej" d="M712 120V1100M742 120V1100"/>';
    var pr = ''; for (var yy = 130; yy < 1100; yy += 22) pr += 'M706 ' + yy + 'h42';
    s += '<path class="m-prazec" d="' + pr + '"/>';
    s += '<text class="m-popis m-popis--male" transform="translate(735 1000) rotate(-90)">Negrelliho viadukt</text>';
    var pk = ''; for (var py = 236; py < 566; py += 30) pk += 'M806 ' + py + 'h26';
    s += '<path class="m-parkovani" d="' + pk + '"/>';
    [[160, 180, 50], [318, 172, 52], [572, 172, 48], [36, 780, 62], [462, 780, 70], [655, 745, 42], [62, 905, 40], [196, 1020, 40], [408, 1040, 38], [572, 965, 40], [1932, 402, 56], [1978, 604, 38], [1618, 848, 40], [1750, 778, 40], [1860, 882, 40], [1960, 800, 30], [1110, 1060, 30], [1400, 1052, 30], [1522, 1048, 32], [1718, 1040, 30]].forEach(function (t) {
      s += '<circle class="m-strom" cx="' + t[0] + '" cy="' + t[1] + '" r="' + t[2] + '"/>';
    });
    /* Slavoj 10–16 */
    s += '<g class="m-slavoj' + (stav.slavojZavreno ? ' m-zavira-se' : '') + '">';
    [[25, 318, 182, 170], [213, 318, 182, 170], [401, 318, 182, 170], [590, 318, 95, 170]].forEach(function (b) {
      s += '<rect class="m-antuka" x="' + b[0] + '" y="' + b[1] + '" width="' + b[2] + '" height="' + b[3] + '"/>';
    });
    [72, 162, 262, 348, 450, 536, 636].forEach(function (cx) { s += lajny(cx, 403, 58, 128); });
    s += '</g>';
    s += '<text class="m-popis" x="600" y="296">Slavoj</text>';
    if (stav.slavojZavreno) s += '<text class="m-popis m-popis--zima" x="354" y="302" text-anchor="middle">V zimě uzavřeno</text>';
    /* multifunkční hřiště a beach volejbal na Slavoji */
    s += '<rect class="m-trava" x="330" y="520" width="168" height="95"/><path class="m-lajny m-lajny--tenke" d="M340 530h148v75h-148zM414 530v75"/><circle class="m-lajny m-lajny--tenke" cx="414" cy="567" r="18"/>';
    s += '<rect class="m-pisek" x="515" y="520" width="170" height="95"/><path class="m-lajny m-lajny--tenke" d="M525 530h150v75h-150zM600 530v75"/>';
    /* kurt 1 s tribunami */
    s += '<rect class="m-tribuna" x="937" y="345" width="31" height="183"/><rect x="937" y="345" width="31" height="183" fill="url(#m-rady-v)"/>';
    s += '<rect class="m-tribuna" x="1058" y="345" width="31" height="183"/><rect x="1058" y="345" width="31" height="183" fill="url(#m-rady-v)"/>';
    s += '<rect class="m-povrch m-antuka" data-kurty="1" x="968" y="345" width="90" height="183"/>' + lajny(1013, 437, 56, 124);
    /* bazén */
    s += '<g class="m-bazen"><rect class="m-trava" x="1183" y="328" width="130" height="169"/><rect class="m-bazen-voda" x="1226" y="366" width="48" height="92" rx="3"/></g>';
    s += '<text class="m-popis m-popis--male" x="1248" y="352" text-anchor="middle">Bazén</text>';
    /* 5, 6 antuka · 7, 8, 9 tvrdé */
    s += '<rect class="m-povrch m-antuka" data-kurty="5 6" x="1322" y="328" width="171" height="169"/>' + lajny(1366, 412, 56, 122) + lajny(1450, 412, 56, 122);
    s += '<rect class="m-povrch m-hard" data-kurty="7 8" x="1502" y="328" width="171" height="169"/>' + lajny(1548, 412, 56, 122) + lajny(1628, 412, 56, 122);
    s += '<rect class="m-povrch m-hard" data-kurty="9" x="1681" y="328" width="96" height="169"/>' + lajny(1728, 412, 56, 122);
    /* trojkurt 2, 3, 4 */
    s += '<rect class="m-tribuna" x="1352" y="556" width="31" height="154"/><rect x="1352" y="556" width="31" height="154" fill="url(#m-rady-v)"/>';
    s += '<rect class="m-tribuna" x="1638" y="556" width="31" height="154"/><rect x="1638" y="556" width="31" height="154" fill="url(#m-rady-v)"/>';
    s += '<rect class="m-povrch m-antuka" data-kurty="2 3 4" x="1383" y="556" width="255" height="154"/>' + lajny(1432, 633, 56, 122) + lajny(1508, 633, 56, 122) + lajny(1586, 633, 56, 122);
    /* odrazová stěna a malé multifunkční hřiště */
    s += '<rect class="m-hard" x="1390" y="716" width="53" height="94"/><path class="m-lajny m-lajny--tenke" d="M1398 724h37v78h-37z"/>';
    s += '<rect class="m-trava" x="1449" y="716" width="96" height="54"/><path class="m-lajny m-lajny--tenke" d="M1456 722h82v42h-82zM1497 722v42"/>';
    s += '<text class="m-popis m-popis--male" x="1416" y="838" text-anchor="middle">Odrazová stěna</text>';
    /* pevná hala P1, P2 */
    s += '<rect class="m-hard" x="843" y="740" width="179" height="200"/>' + lajny(893, 845, 56, 122) + lajny(972, 845, 56, 122);
    s += '<rect class="m-pevna-strecha" x="848" y="745" width="169" height="190"/>';
    s += '<text class="m-popis m-popis--male" x="858" y="766">Pevná hala</text>';
    /* centrální dvorec C */
    s += '<rect class="m-tribuna" x="1023" y="740" width="155" height="245"/><rect x="1023" y="740" width="155" height="245" fill="url(#m-rady)" opacity=".8"/>';
    s += '<rect class="m-povrch m-hard" data-kurty="C" x="1057" y="770" width="88" height="180"/>' + lajny(1101, 862, 56, 124);
    s += '<text class="m-popis m-popis--male" x="1234" y="880" text-anchor="middle">Kanceláře ČTS</text>';
    /* vstupy */
    [[843, 640, 0], [1215, 590, 180], [1845, 535, 190], [1487, 830, 205]].forEach(function (v) {
      s += '<path class="m-vstup" transform="translate(' + v[0] + ' ' + v[1] + ') rotate(' + v[2] + ')" d="M0 -12 L 16 0 L 0 12 Z"/>';
    });
    s += '<text class="m-popis m-popis--male" x="1236" y="578">Vstup k recepci</text>';
    /* přetlakové haly – jen nad kurty, které mají v zimním ceníku přetlakovou halu */
    HALY.forEach(function (h) {
      var hala = stav.halaKurtu(h[4].split(' ')[0]);
      if (!hala || hala.pevna) return;
      var rx = Math.min(38, h[2] / 3);
      s += '<g class="m-hala' + (hala.antuka ? ' m-hala--antuka' : '') + '"><rect class="m-hala__plast" x="' + h[0] + '" y="' + h[1] + '" width="' + h[2] + '" height="' + h[3] + '" rx="' + rx + '"/>' +
        '<rect class="m-hala__vzor" x="' + h[0] + '" y="' + h[1] + '" width="' + h[2] + '" height="' + h[3] + '" rx="' + rx + '"/></g>';
    });
    return s + '</svg>';
  }

  /* ── Texty karet ─────────────────────────────────────────────────────── */
  function nazevKurtu(k) { return k.id === 'C' ? 'Centrální dvorec' : 'Kurt ' + k.id; }
  function povrchLeto(k) { return k.leto === 'antuka' ? 'antuka' : (k.leto === 'pevna' ? 'tvrdý povrch v pevné hale' : 'tvrdý povrch'); }
  function rozsah(ceny) {
    ceny = ceny.filter(function (x) { return typeof x === 'number'; });
    if (!ceny.length) return '';
    var a = Math.min.apply(null, ceny), b = Math.max.apply(null, ceny);
    return (a === b ? CLTK.ceniky.kc(a) : String(a) + '–' + CLTK.ceniky.kc(b)) + '/hod';
  }
  /** „10, 11, 12, 13“ → „10–13“ (jen u čísel jdoucích po sobě) */
  function seznamKurtu(ids) {
    var cisla = ids.filter(function (x) { return /^\d+$/.test(x); }).map(Number).sort(function (a, b) { return a - b; });
    var ostatni = ids.filter(function (x) { return !/^\d+$/.test(x); });
    var useky = [], i = 0;
    while (i < cisla.length) {
      var j = i;
      while (j + 1 < cisla.length && cisla[j + 1] === cisla[j] + 1) j++;
      useky.push(j - i >= 2 ? cisla[i] + '–' + cisla[j] : cisla.slice(i, j + 1).join(', '));
      i = j + 1;
    }
    return useky.concat(ostatni).join(', ');
  }

  function mapa(box, data) {
    if (!CLTK.ceniky) return;
    var c = CLTK.ceniky.pripravit(data.ceniky || {});
    var maZimu = c.zima.length > 0;
    function halaKurtu(id) { return c.zima.filter(function (h) { return h.kurty.indexOf(id) >= 0; })[0] || null; }
    function mistoKurtu(id) { return c.leto.mista.filter(function (m) { return m.kurty.indexOf(id) >= 0; })[0] || null; }
    var slavoj = KURTY.filter(function (k) { return +k.id >= 10; });
    var stav = {
      halaKurtu: halaKurtu,
      slavojZavreno: maZimu && slavoj.every(function (k) { return !halaKurtu(k.id); })
    };
    var rezim = maZimu ? c.sezona : 'leto';
    var sluzby = (data.sluzby || []).filter(function (s) { return SLUZBY[s.kotva]; });
    var rezervace = String(data.rezervace || ''), cenikUrl = String(data.cenik_url || ''), sluzbyUrl = String(data.sluzby_url || '');
    var uid = 'mapa-' + Math.random().toString(36).slice(2, 7);

    var zaloha = box.querySelector('.mapa__zaloha');
    if (zaloha) zaloha.hidden = true;

    /* ovládání: sezóna, vrstvy, legenda */
    var listy = d.createElement('div');
    listy.className = 'mapa__listy';
    listy.innerHTML =
      (maZimu ? '<fieldset class="mapa__sezona"><legend class="vh">Sezóna na plánu</legend><div class="segment">' +
        '<label><input type="radio" name="' + uid + '-s" value="leto"><span>Léto</span></label>' +
        '<label><input type="radio" name="' + uid + '-s" value="zima"><span>Zima</span></label></div></fieldset>' : '') +
      '<div class="mapa__vrstvy" role="group" aria-label="Zobrazit na plánu">' +
        '<label class="volba"><input type="checkbox" checked data-vrstva="kurty"><span>Kurty</span></label>' +
        (sluzby.length ? '<label class="volba"><input type="checkbox" checked data-vrstva="sluzby"><span>Služby</span></label>' : '') +
        '<span class="mapa__legenda" aria-hidden="true"><i class="mapa__vzorek mapa__vzorek--antuka"></i>antuka<i class="mapa__vzorek mapa__vzorek--hard"></i>tvrdý povrch' +
        (maZimu ? '<i class="mapa__vzorek mapa__vzorek--hala"></i>hala' : '') + '</span></div>';

    var posun = d.createElement('p');
    posun.className = 'mapa__posun';
    posun.innerHTML = 'Plán lze posouvat do strany <span class="sipka" aria-hidden="true"></span>';

    var okno = d.createElement('div');
    okno.className = 'mapa__okno';
    var platno = d.createElement('div');
    platno.className = 'mapa__platno';
    var body = d.createElement('div');
    body.className = 'mapa__body';
    KURTY.forEach(function (k) {
      var b = d.createElement('button');
      b.type = 'button';
      b.className = 'mapa__bod mapa__bod--kurt';
      b.setAttribute('data-id', 'k-' + k.id);
      b.setAttribute('aria-pressed', 'false');
      b.style.setProperty('--x', k.x);
      b.style.setProperty('--y', bodY(k.y).toFixed(2));
      b.textContent = k.id;
      body.appendChild(b);
    });
    sluzby.forEach(function (sl) {
      var p = SLUZBY[sl.kotva];
      var b = d.createElement('button');
      b.type = 'button';
      b.className = 'mapa__bod mapa__bod--sluzba';
      b.setAttribute('data-id', 's-' + sl.kotva);
      b.setAttribute('aria-pressed', 'false');
      b.setAttribute('aria-label', sl.nazev);
      b.title = sl.nazev;
      b.style.setProperty('--x', p[0]);
      b.style.setProperty('--y', bodY(p[1]).toFixed(2));
      body.appendChild(b);
    });
    okno.appendChild(platno);

    var karta = d.createElement('div');
    karta.className = 'mapa__karta';
    karta.setAttribute('aria-live', 'polite');

    var cipy = d.createElement('div');
    cipy.className = 'mapa__sluzby';
    if (sluzby.length) {
      cipy.innerHTML = '<p class="stitek">Služby na plánu</p><ul class="pilulky" role="list">' + sluzby.map(function (sl) {
        return '<li><button type="button" class="pilulka" data-id="s-' + esc(sl.kotva) + '" aria-pressed="false">' + typo(sl.nazev) + '</button></li>';
      }).join('') + '</ul>';
    }

    box.appendChild(listy); box.appendChild(posun); box.appendChild(okno); box.appendChild(karta);
    if (sluzby.length) box.appendChild(cipy);
    /* kompaktní rozložení (úvodní stránka): plán + proužek s popiskem vlevo,
       všechny volby (Léto / Zima, vrstvy, legenda) a služby v panelu vpravo */
    if (box.classList.contains('mapa--kompakt')) {
      var bok = d.createElement('div');
      bok.className = 'mapa__bok';
      bok.appendChild(listy);
      if (sluzby.length) bok.appendChild(cipy);
      box.appendChild(bok);
    }

    var foto = d.querySelector('[data-letecky]');
    var vybrano = null;

    function tlacitkoRezervace() {
      return rezervace ? '<a class="btn btn--mala" href="' + esc(rezervace) + '" target="_blank" rel="noopener">Rezervovat kurt <span class="sipka sipka--ven" aria-hidden="true"></span><span class="vh"> (rezervační systém v novém okně)</span></a>' : '';
    }
    function sloupec(nazev, obsah, aktivni) {
      return '<div class="mapa__karta-sloupec' + (aktivni ? ' je-aktivni' : '') + '"><p class="stitek">' + nazev + '</p>' + obsah + '</div>';
    }

    function kartaVychozi() {
      var z = rezim === 'zima';
      var antuka = KURTY.filter(function (k) { return k.leto === 'antuka'; }).length;
      var tvrde = KURTY.filter(function (k) { return k.leto === 'hard' && k.id !== 'C'; }).length;
      var pevne = KURTY.filter(function (k) { return k.leto === 'pevna'; }).length;
      var maC = KURTY.some(function (k) { return k.id === 'C'; });
      var leto = [antuka + NB + mnozne(antuka, 'antukový kurt', 'antukové kurty', 'antukových kurtů'),
                  tvrde + NB + mnozne(tvrde, 'kurt', 'kurty', 'kurtů') + ' s tvrdým povrchem',
                  pevne + NB + 'v pevné hale'].join(', ') + (maC ? ' a centrální dvorec' : '');
      var kryte = KURTY.filter(function (k) { return halaKurtu(k.id); }).length;
      var zavrene = KURTY.filter(function (k) { return !halaKurtu(k.id); }).map(function (k) { return k.id; });
      var zima = c.zima.map(function (h) { return typo(h.nazev) + ' – ' + h.kurty.length + NB + mnozne(h.kurty.length, 'kurt', 'kurty', 'kurtů'); }).join('<br>') +
        (zavrene.length ? '<br>' + (zavrene.length === 1 ? 'kurt ' : 'kurty ') + esc(seznamKurtu(zavrene)) + ' v' + NB + 'zimě uzavřené' : '');
      karta.innerHTML =
        '<div class="mapa__karta-hlava"><span class="mapa__karta-cislo" aria-hidden="true">' + (z ? kryte : KURTY.length) + '</span><div>' +
        '<p class="mapa__karta-nazev">' + (z ? 'Zima: ' + kryte + NB + mnozne(kryte, 'krytý kurt', 'kryté kurty', 'krytých kurtů') : 'Léto: ' + KURTY.length + NB + 'kurtů na ostrově') + '</p>' +
        '<p class="mapa__karta-pod">Ťukněte na kurt nebo službu na plánu.</p></div></div>' +
        sloupec('Léto', '<p>' + typo(leto) + '</p>', !z) +
        (maZimu ? sloupec('Zima', '<p>' + zima + '</p>', z) : '') +
        '<div class="mapa__karta-akce">' + tlacitkoRezervace() + (cenikUrl ? '<a class="odkaz" href="' + esc(cenikUrl) + '">Ceník a&nbsp;kalkulačka <span class="sipka" aria-hidden="true"></span></a>' : '') + '</div>';
    }

    function kartaKurtu(k) {
      var z = rezim === 'zima', h = halaKurtu(k.id), m = mistoKurtu(k.id);
      var zavreno = z && maZimu && !h;
      var pp = c.leto.priplatky.filter(function (p) { return p.kurty.indexOf(k.id) >= 0; });
      var leto = '<p>' + typo(povrchLeto(k)) + '</p>' + (m
        ? '<p class="drobne tnum">' + typo(m.text.cena) + (m.text.clen ? ' · člen ' + typo(m.text.clen) : '') + '</p>' +
          pp.map(function (p) { return '<p class="drobne tnum">' + typo(p.nazev) + ' ' + typo(p.text.cena) + '</p>'; }).join('')
        : '<p class="drobne">cena <span class="doplni">doplní klub</span></p>');
      var zima = h
        ? '<p>' + typo(h.nazev) + '</p><p class="drobne tnum">' + esc(rozsah(h.pasma.map(function (p) { return p.hod.cena; }))) +
          (h.pasma.some(function (p) { return p.clen; }) ? ' · člen ' + esc(rozsah(h.pasma.map(function (p) { return p.clen ? p.clen.cena : null; }))) : '') + '</p>' +
          (h.obdobi ? '<p class="drobne">' + typo(h.obdobi) + '</p>' : '')
        : '<p>v' + NB + 'zimě uzavřeno</p>';
      karta.innerHTML =
        '<div class="mapa__karta-hlava"><span class="mapa__karta-cislo' + (zavreno ? ' je-zavreno' : '') + '" aria-hidden="true">' + esc(k.id) + '</span><div>' +
        '<p class="mapa__karta-nazev">' + esc(nazevKurtu(k)) + '</p>' +
        (m ? '<p class="mapa__karta-pod">' + typo(m.nazev) + '</p>' : '') + '</div></div>' +
        sloupec('Léto', leto, !z) + (maZimu ? sloupec('Zima', zima, z) : '') +
        '<div class="mapa__karta-akce">' + (zavreno ? '<span class="doplni">v zimě uzavřeno</span>' : tlacitkoRezervace()) +
        (cenikUrl && (h || m) ? '<a class="odkaz" href="' + esc(cenikUrl + (cenikUrl.indexOf('?') >= 0 ? '&' : '?') + 'kurt=' + encodeURIComponent(k.id) + '#kalkulacka') + '">Spočítat cenu <span class="sipka" aria-hidden="true"></span></a>' : '') + '</div>';
    }

    function kartaSluzby(sl) {
      karta.innerHTML =
        '<div class="mapa__karta-hlava"><span class="mapa__karta-cislo mapa__karta-cislo--sluzba" aria-hidden="true"></span><div>' +
        '<p class="mapa__karta-nazev">' + typo(sl.nazev) + '</p>' + (sl.perex ? '<p class="mapa__karta-pod">' + typo(sl.perex) + '</p>' : '') + '</div></div>' +
        sloupec('Časy', '<p>' + (sl.casy ? typo(sl.casy) : '<span class="doplni">doplní klub</span>') + '</p>', false) +
        '<div class="mapa__karta-akce"><a class="odkaz" href="' + esc(sluzbyUrl) + '#' + esc(sl.kotva) + '">Podrobnosti <span class="sipka" aria-hidden="true"></span></a></div>';
    }

    function vyber(id) {
      vybrano = vybrano === id ? null : id;
      Array.prototype.forEach.call(box.querySelectorAll('[data-id]'), function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-id') === vybrano)); });
      if (!vybrano) { kartaVychozi(); return; }
      if (vybrano.indexOf('k-') === 0) {
        var k = KURTY.filter(function (x) { return 'k-' + x.id === vybrano; })[0];
        if (k) kartaKurtu(k);
      } else {
        var sl = sluzby.filter(function (x) { return 's-' + x.kotva === vybrano; })[0];
        if (sl) kartaSluzby(sl);
      }
    }

    function popisKurtu(k) {
      var zavreno = rezim === 'zima' && maZimu && !halaKurtu(k.id);
      return nazevKurtu(k) + ', ' + povrchLeto(k) + (zavreno ? ', v zimě uzavřeno' : '');
    }

    /* kresba jen jednou – sezóna se přepíná atributem data-rezim (CSS přechody) */
    platno.innerHTML = svg(stav);
    platno.appendChild(body);
    /* zimní povrch podle zimního ceníku (použije se jen v režimu Zima) */
    Array.prototype.forEach.call(platno.querySelectorAll('.m-povrch[data-kurty]'), function (el) {
      var h = halaKurtu(el.getAttribute('data-kurty').split(' ')[0]);
      if (h) el.classList.add(h.antuka ? 'm-zima-antuka' : 'm-zima-hard');
    });

    function nastavRezim(r) {
      rezim = r;
      box.setAttribute('data-rezim', r);
      KURTY.forEach(function (k) {
        var b = body.querySelector('[data-id="k-' + k.id + '"]');
        b.classList.toggle('je-zavreno', r === 'zima' && maZimu && !halaKurtu(k.id));
        b.setAttribute('aria-label', popisKurtu(k));
      });
      var radio = listy.querySelector('input[value="' + r + '"]');
      if (radio) radio.checked = true;
      if (foto) foto.setAttribute('data-rezim', r);
      if (vybrano) { var v = vybrano; vybrano = null; vyber(v); } else kartaVychozi();
    }

    listy.addEventListener('change', function (e) {
      var t = e.target;
      if (t.name === uid + '-s') nastavRezim(t.value);
      if (t.hasAttribute('data-vrstva')) box.setAttribute('data-vrstva-' + t.getAttribute('data-vrstva'), t.checked ? 'ano' : 'ne');
    });
    function ukazNaPlanu(id) {
      var bod = body.querySelector('[data-id="' + id + '"]');
      if (!bod) return;
      var plynule = CLTK.pohybPovolen && CLTK.pohybPovolen() ? 'smooth' : 'auto';
      var navic = platno.scrollWidth - okno.clientWidth;
      if (navic > 0) {
        var x = bod.getBoundingClientRect().left - platno.getBoundingClientRect().left - okno.clientWidth / 2;
        okno.scrollTo({ left: Math.max(0, Math.min(navic, x)), behavior: plynule });
      }
      var r = okno.getBoundingClientRect();
      var horni = parseFloat(getComputedStyle(d.documentElement).getPropertyValue('--hlavicka-kompakt')) + (parseFloat(getComputedStyle(d.documentElement).getPropertyValue('--pruh-v')) || 0);
      if (r.top < horni || r.bottom > window.innerHeight) okno.scrollIntoView({ block: r.height + horni < window.innerHeight ? 'nearest' : 'start', behavior: plynule });
      bod.classList.remove('je-pulz'); void bod.offsetWidth; bod.classList.add('je-pulz');
    }
    box.addEventListener('click', function (e) {
      var b = e.target.closest('button[data-id]');
      if (!b || !box.contains(b)) return;
      var id = b.getAttribute('data-id');
      vyber(id);
      if (!body.contains(b) && vybrano === id) ukazNaPlanu(id);
    });
    /* vnější přepínače (u leteckého snímku): [data-mapa-prepni="zima"] */
    Array.prototype.forEach.call(d.querySelectorAll('[data-mapa-prepni]'), function (t) {
      t.hidden = !maZimu;
      t.addEventListener('click', function () { nastavRezim(t.getAttribute('data-mapa-prepni')); });
    });
    nastavRezim(rezim);
    box.classList.add('je-hotova');
    /* na úzké obrazovce plán roluje do strany – začít u hlavní budovy (recepce), ne u Slavoje */
    var navic = platno.scrollWidth - okno.clientWidth;
    if (navic > 0) okno.scrollLeft = Math.round(navic * 0.62);
  }

  function start() {
    var box = d.querySelector('[data-mapa]');
    if (!box) return;
    var data = CLTK.data ? CLTK.data('mapa-data') : null;
    if (!data) return;
    try { mapa(box, data); } catch (e) { if (window.console) console.error(e); return; }
    /* prvky, které mají smysl jen s JavaScriptem (dialog s plánem) */
    Array.prototype.forEach.call(d.querySelectorAll('[data-jen-js]'), function (el) { el.hidden = false; });
  }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();
})();
