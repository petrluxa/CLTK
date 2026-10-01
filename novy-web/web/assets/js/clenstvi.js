/* ==========================================================================
   I. ČLTK Praha · Členství (clenstvi.php)
   1. Konfigurátor → přihláška: „Pokračovat“ v konfigurátoru (app.js počítá
      cenu a náhled Členského listu) předvyplní typ členství v přihlášce,
      otevře místa pro další osoby a sjede k formuláři – bez přenačtení.
   2. Přihláška ve čtyřech krocích: Osoby · Adresa a kontakt · Souhlasy ·
      Shrnutí. Každý krok se před pokračováním zkontroluje, Enter v poli
      přejde na další krok (neodešle formulář předčasně), na konci shrnutí.
   3. Podmíněné části: firma a IČO jen u firemního členství, zákonný zástupce
      jen když je některé osobě méně než 15 let, další osoby přes „Přidat“.
   4. Poděkování: tlačítko „Vytisknout Členský list“ (tiskne jen list).
   Bez JavaScriptu je přihláška jedna dlouhá stránka se všemi poli a server
   ji zkontroluje sám (clenstvi.php) – nic z toho není nutné k odeslání.
   Kostra: form[data-prihlaska] > [data-kroky] [data-krok="1…4"] [data-odeslat];
           [data-jen-firemni] [data-povinne-firma] · [data-zastupce] [data-povinne-zastupce]
           details[data-osoba] [data-povinne-osoba] [data-osoba-pridat] [data-osoba-odebrat]
   ========================================================================== */
(function () {
  'use strict';
  var d = document;
  var C = window.CLTK || {};
  function vse(sel, root) { return [].slice.call((root || d).querySelectorAll(sel)); }
  function jeden(sel, root) { return (root || d).querySelector(sel); }
  var esc = C.esc || function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  function fokus(el) { if (!el) return; if (C.fokus) C.fokus(el); else try { el.focus({ preventScroll: true }); } catch (e) { el.focus(); } }
  function pohybOk() { return C.pohybPovolen ? C.pohybPovolen() : true; }
  function sjed(el) {
    if (!el) return;
    try { el.scrollIntoView({ behavior: pohybOk() ? 'smooth' : 'auto', block: 'start' }); } catch (e) { el.scrollIntoView(); }
  }

  /* datum z <input type="date"> nebo z textu „5. 9. 1990“ → [r, m, d] */
  function datum(s) {
    s = String(s || '').trim();
    var m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (m) return [+m[1], +m[2], +m[3]];
    m = s.match(/^(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})$/);
    if (m) return [+m[3], +m[2], +m[1]];
    return null;
  }
  function platneDatum(p) {
    if (!p) return false;
    var t = new Date(p[0], p[1] - 1, p[2]);
    return t.getFullYear() === p[0] && t.getMonth() === p[1] - 1 && t.getDate() === p[2];
  }
  function vek(p, dnes) {
    return dnes[0] - p[0] - ((dnes[1] < p[1] || (dnes[1] === p[1] && dnes[2] < p[2])) ? 1 : 0);
  }

  /* ======================================================================
     PŘIHLÁŠKA
     ====================================================================== */
  function prihlaska(form) {
    var kroky = vse('[data-krok]', form);
    var seznamKroku = jeden('[data-kroky]', form);
    var odeslat = jeden('[data-odeslat]', form);
    var shrnuti = jeden('[data-shrnuti]', form);
    var typ = form.elements.typ;
    var firma = jeden('[data-jen-firemni]', form);
    var zastupce = jeden('[data-zastupce]', form);
    var zastupcePozn = jeden('[data-zastupce-pozn]', form);
    var vekZastupce = +form.getAttribute('data-vek-zastupce') || 15;
    var dnes = datum(form.getAttribute('data-dnes')) || (function () { var t = new Date(); return [t.getFullYear(), t.getMonth() + 1, t.getDate()]; })();
    var osoby = vse('details[data-osoba]', form);
    var pridat = jeden('[data-osoba-pridat]', form);
    var pridatObal = jeden('[data-osoba-pridat-obal]', form);
    var aktualni = 1;
    var posledni = kroky.length;
    if (!kroky.length) return;

    form.classList.add('prihlaska--kroky');

    /* ---------- pomocné: viditelnost a hodnoty ---------- */
    /* je pole součástí přihlášky? (skrytý krok se nepočítá – skrývají se jen podmíněné části) */
    function viditelny(el) {
      for (var x = el; x && x !== form; x = x.parentElement) {
        if (x.hasAttribute('data-krok')) return true;
        if (x.hidden) return false;
        if (x.tagName === 'DETAILS' && x.hasAttribute('data-osoba') && x.hasAttribute('data-skryta')) return false;
      }
      return true;
    }
    function popisek(pole) {
      var lab = pole.type === 'radio' ? jeden('legend', pole.closest('fieldset')) : (pole.id ? form.querySelector('label[for="' + pole.id + '"]') : null);
      if (!lab) return pole.name;
      var t = lab.cloneNode(true);
      vse('.povinne, .vh', t).forEach(function (x) { x.remove(); });
      return t.textContent.replace(/\s+/g, ' ').trim();
    }
    function hodnotaOsoby(det) { return vse('input:not([type="radio"]), select', det).some(function (i) { return i.value.trim() !== ''; }) || vse('input[type="radio"]:checked', det).length > 0; }

    /* ---------- chyby u polí ---------- */
    function obalPole(pole) { return pole.closest('.pole'); }
    function nastavChybu(pole, text) {
      var obal = obalPole(pole);
      if (!obal) return;
      var cil = pole.type === 'radio' ? obal : pole;
      var id = (pole.type === 'radio' ? (obal.querySelector('input').id.replace(/-[^-]+$/, '')) : pole.id) + '-chyba';
      var p = d.getElementById(id);
      if (pole.type === 'radio') vse('input', obal).forEach(function (r) { if (text) r.setAttribute('aria-invalid', 'true'); else r.removeAttribute('aria-invalid'); });
      if (text) {
        obal.classList.add('pole--chyba');
        if (pole.type !== 'radio') pole.setAttribute('aria-invalid', 'true');
        if (!p) { p = d.createElement('p'); p.className = 'pole__chyba'; p.id = id; obal.appendChild(p); }
        p.textContent = text;
        var popis = (cil.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
        if (popis.indexOf(id) < 0) { popis.push(id); cil.setAttribute('aria-describedby', popis.join(' ')); }
      } else {
        obal.classList.remove('pole--chyba');
        if (pole.type !== 'radio') pole.removeAttribute('aria-invalid');
        if (p) p.remove();
        var zbytek = (cil.getAttribute('aria-describedby') || '').split(/\s+/).filter(function (x) { return x && x !== id; });
        if (zbytek.length) cil.setAttribute('aria-describedby', zbytek.join(' ')); else cil.removeAttribute('aria-describedby');
      }
    }

    /* pravidla jako na serveru (clenstvi.php) */
    var RE_TEL = /^\+?[0-9][0-9 ()\/\-]{7,}$/;
    var RE_MAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    function chybaPole(pole) {
      var v = (pole.value || '').trim();
      var povinne = pole.required;
      if (pole.type === 'checkbox') return povinne && !pole.checked ? 'Bez tohoto potvrzení přihlášku nelze odeslat.' : '';
      if (pole.type === 'radio') {
        var skupina = vse('input[name="' + pole.name + '"]', form);
        return povinne && !skupina.some(function (r) { return r.checked; }) ? 'Vyberte prosím pohlaví.' : '';
      }
      if (v === '') {
        if (!povinne) return '';
        return pole.tagName === 'SELECT' ? 'Vyberte prosím jednu z možností.' : 'Toto pole je povinné.';
      }
      if (pole.type === 'email' && !RE_MAIL.test(v)) return 'Zadejte prosím platný e-mail.';
      if (pole.type === 'tel' && !RE_TEL.test(v)) return 'Zadejte prosím telefon, např. 608 974 974.';
      if (pole.hasAttribute('data-narozeni')) {
        var p = datum(v);
        if (!platneDatum(p) || p[0] < 1900 || vek(p, dnes) < 0) return 'Zadejte prosím platné datum narození, např. 5. 9. 1990.';
      }
      if (pole.name === 'ico' && !/^\d{8}$/.test(v.replace(/\s/g, ''))) return 'IČO má osm číslic.';
      if (pole.name === 'psc' && !/^[0-9A-Za-z][0-9A-Za-z \-]{2,9}$/.test(v)) return 'Zadejte prosím PSČ.';
      if (/rodne_cislo/.test(pole.name) && !/^\d{6}\s*\/?\s*\d{3,4}$/.test(v)) return 'Rodné číslo má tvar 000000/0000 – nebo pole nechte prázdné.';
      return '';
    }

    /* povinná pole u dalších osob platí, jen když je osoba aspoň trochu vyplněná */
    function nastavPovinnostOsob() {
      osoby.forEach(function (det) {
        var plna = hodnotaOsoby(det) && !det.hasAttribute('data-skryta');
        vse('[data-povinne-osoba]', det).forEach(function (i) { i.required = plna; });
        vse('input[type="radio"]', det).forEach(function (i) { i.required = plna; });
      });
    }

    function overKrok(krok, ukazat) {
      nastavPovinnostOsob();
      var prvni = null;
      var pole = vse('input, select, textarea', krok).filter(function (p) {
        return p.type !== 'hidden' && p.name && p.name !== 'web_adresa' && viditelny(p) && !p.disabled;
      });
      var hotoveRadio = {};
      pole.forEach(function (p) {
        if (p.type === 'radio') { if (hotoveRadio[p.name]) return; hotoveRadio[p.name] = 1; }
        var t = chybaPole(p);
        if (ukazat) nastavChybu(p, t);
        if (t && !prvni) prvni = p;
      });
      return prvni;
    }

    /* ---------- podmíněné části ---------- */
    function vybranyDruh() {
      var o = typ && typ.options[typ.selectedIndex];
      return o ? (o.getAttribute('data-druh') || '') : '';
    }
    function obnovFirmu() {
      if (!firma) return;
      var ano = vybranyDruh() === 'firemni';
      firma.hidden = !ano;
      vse('[data-povinne-firma]', firma).forEach(function (i) { i.required = ano; });
      var pozn = jeden('.clen-firma__pozn', firma);
      if (pozn && ano) pozn.textContent = pozn.textContent.replace(/^Jen u\s+firemního členství\.\s*/, '');
    }
    function nejmladsi() {
      var min = 999;
      vse('[data-narozeni]', form).forEach(function (i) {
        var det = i.closest('details[data-osoba]');
        if (det && det.hasAttribute('data-skryta')) return;
        var p = datum(i.value);
        if (platneDatum(p)) min = Math.min(min, vek(p, dnes));
      });
      return min;
    }
    function obnovZastupce() {
      if (!zastupce) return;
      var nutny = nejmladsi() < vekZastupce;
      zastupce.hidden = !nutny;
      vse('[data-povinne-zastupce]', zastupce).forEach(function (i) { i.required = nutny; });
      if (zastupcePozn && nutny) zastupcePozn.textContent = 'Některé z osob je méně než ' + vekZastupce + ' let – vyplňte prosím údaje a souhlas zákonného zástupce.';
    }

    /* ---------- další osoby: Přidat / Odebrat ---------- */
    function souhrnOsoby(det) {
      var s = jeden('[data-osoba-souhrn]', det);
      if (!s) return;
      var j = vse('input[name$="[jmeno]"], input[name$="[prijmeni]"]', det).map(function (i) { return i.value.trim(); }).filter(Boolean).join(' ');
      s.textContent = j ? ' – ' + j : '';
    }
    function obnovOsoby() {
      var skryte = osoby.filter(function (det) { return det.hasAttribute('data-skryta'); });
      if (pridatObal) pridatObal.hidden = skryte.length === 0;
      osoby.forEach(function (det) {
        det.hidden = det.hasAttribute('data-skryta');
        var akce = jeden('[data-osoba-akce]', det);
        if (akce) akce.hidden = false;
        souhrnOsoby(det);
      });
      nastavPovinnostOsob();
    }
    function ukazOsobu(det, otevrit) {
      det.removeAttribute('data-skryta');
      if (otevrit) det.open = true;
      obnovOsoby();
    }
    function hodnotyOsoby(det) {
      return vse('input, select', det).map(function (i) { return i.type === 'radio' ? i.checked : i.value; });
    }
    function nastavOsobu(det, h) {
      vse('input, select', det).forEach(function (i, k) { if (i.type === 'radio') i.checked = !!h[k]; else i.value = h ? h[k] : ''; });
    }
    function odeberOsobu(det) {
      var i = osoby.indexOf(det);
      var viditelne = osoby.filter(function (x) { return !x.hasAttribute('data-skryta'); });
      // hodnoty dalších osob se posunou o jedno místo, aby čísla osob zůstala souvislá
      for (var k = i; k < osoby.length - 1; k++) {
        if (osoby[k + 1].hasAttribute('data-skryta')) { nastavOsobu(osoby[k], null); break; }
        nastavOsobu(osoby[k], hodnotyOsoby(osoby[k + 1]));
        osoby[k].open = osoby[k + 1].open;
      }
      var posledniViditelna = viditelne[viditelne.length - 1];
      if (posledniViditelna) { nastavOsobu(posledniViditelna, null); posledniViditelna.setAttribute('data-skryta', ''); posledniViditelna.open = false; }
      vse('.pole--chyba', det).forEach(function (o) { var x = jeden('input, select', o); if (x) nastavChybu(x, ''); });
      obnovOsoby();
      obnovZastupce();
      fokus(pridat && pridatObal && !pridatObal.hidden ? pridat : jeden('summary', osoby[Math.max(0, i - 1)]));
    }
    osoby.forEach(function (det, i) {
      // na začátku viditelné jen osoby s údaji nebo otevřené (z konfigurátoru / po chybě)
      if (!det.open && !hodnotaOsoby(det)) det.setAttribute('data-skryta', '');
      var tl = jeden('[data-osoba-odebrat]', det);
      if (tl) tl.addEventListener('click', function () { odeberOsobu(det); });
      det.addEventListener('input', function () { souhrnOsoby(det); });
    });
    if (pridat) pridat.addEventListener('click', function () {
      var dalsi = osoby.filter(function (det) { return det.hasAttribute('data-skryta'); })[0];
      if (!dalsi) return;
      ukazOsobu(dalsi, true);
      fokus(jeden('input', dalsi));
    });
    function zajistiOsoby(pocet) {
      var vid = osoby.filter(function (det) { return !det.hasAttribute('data-skryta'); }).length;
      for (var k = 0; k < osoby.length && vid < pocet; k++) {
        if (osoby[k].hasAttribute('data-skryta')) { ukazOsobu(osoby[k], true); vid++; }
      }
    }

    /* ---------- kroky ---------- */
    var navigace = [];
    kroky.forEach(function (krok, i) {
      var n = i + 1;
      var nav = d.createElement('div');
      nav.className = 'krok__navigace';
      if (n > 1) nav.innerHTML += '<button class="odkaz odkaz--text krok__zpet" type="button" data-krok-zpet><span class="sipka sipka--zpet" aria-hidden="true"></span> Zpět</button>';
      if (n < posledni) nav.innerHTML += '<button class="btn krok__dalsi" type="button" data-krok-dalsi>Pokračovat<span class="vh"> na krok ' + (n + 1) + ' z ' + posledni + '</span> <span class="sipka" aria-hidden="true"></span></button>';
      krok.appendChild(nav);
      navigace.push(nav);
      nav.addEventListener('click', function (e) {
        if (e.target.closest('[data-krok-zpet]')) jdi(n - 1, true);
        if (e.target.closest('[data-krok-dalsi]')) dalsi();
      });
    });
    if (odeslat) kroky[posledni - 1].insertBefore(odeslat, navigace[posledni - 1]);

    /* seznam kroků nahoře: hotové kroky jdou rozkliknout */
    if (seznamKroku) {
      seznamKroku.hidden = false;
      vse('[data-krok-stav]', seznamKroku).forEach(function (li) {
        var n = +li.getAttribute('data-krok-stav');
        var nazev = jeden('.kroky__nazev', li);
        var b = d.createElement('button');
        b.type = 'button';
        b.className = 'kroky__tl';
        b.setAttribute('data-krok-na', n);
        while (li.firstChild) b.appendChild(li.firstChild);
        li.appendChild(b);
        if (nazev) nazev.insertAdjacentHTML('afterend', '<span class="vh" data-krok-popis></span>');
      });
      seznamKroku.addEventListener('click', function (e) {
        var b = e.target.closest('[data-krok-na]');
        if (!b) return;
        var cil = +b.getAttribute('data-krok-na');
        if (cil <= aktualni) { jdi(cil, true); return; }
        for (var k = aktualni; k < cil; k++) {               // dopředu jen přes zkontrolované kroky
          var chybne = overKrok(kroky[k - 1], true);
          if (chybne) { jdi(k, false); zobrazChybu(chybne); return; }
        }
        jdi(cil, true);
      });
    }

    function jdi(n, sFokusem) {
      n = Math.max(1, Math.min(posledni, n));
      aktualni = n;
      kroky.forEach(function (k, i) { k.hidden = i + 1 !== n; });
      if (n === posledni) vykresliShrnuti();
      if (seznamKroku) vse('[data-krok-stav]', seznamKroku).forEach(function (li) {
        var k = +li.getAttribute('data-krok-stav');
        li.classList.toggle('je-aktualni', k === n);
        li.classList.toggle('je-hotovo', k < n);
        var b = jeden('button', li);
        if (b) { if (k === n) b.setAttribute('aria-current', 'step'); else b.removeAttribute('aria-current'); }
        var p = jeden('[data-krok-popis]', li);
        if (p) p.textContent = k < n ? ' (hotovo)' : (k === n ? ' (aktuální krok)' : '');
      });
      if (sFokusem) {
        var nadpis = jeden('.krok__nadpis', kroky[n - 1]);
        sjed(seznamKroku || nadpis);
        fokus(nadpis);
      }
    }
    function zobrazChybu(pole) {
      var det = pole.closest('details');
      if (det) det.open = true;
      sjed(obalPole(pole) || pole);
      fokus(pole);
    }
    function dalsi() {
      var chybne = overKrok(kroky[aktualni - 1], true);
      if (chybne) { zobrazChybu(chybne); return; }
      jdi(aktualni + 1, true);
    }

    /* Enter v poli kroku 1–3 = Pokračovat (ne odeslání ani jiné tlačítko) */
    form.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' || e.isComposing) return;
      var t = e.target;
      if (!t || t.tagName === 'TEXTAREA' || t.tagName === 'BUTTON' || t.tagName === 'A' || t.tagName === 'SUMMARY') return;
      if (aktualni < posledni) { e.preventDefault(); dalsi(); }
    });

    /* odeslání: ještě jednou všechny kroky (kdyby se mezitím něco změnilo) */
    form.addEventListener('submit', function (e) {
      for (var k = 1; k <= posledni; k++) {
        var chybne = overKrok(kroky[k - 1], true);
        if (chybne) { e.preventDefault(); jdi(k, false); zobrazChybu(chybne); return; }
      }
      var tl = jeden('button[type="submit"]', form);
      if (tl) { tl.disabled = true; tl.setAttribute('aria-busy', 'true'); }
    });

    /* oprava pole = chyba zmizí hned */
    function priZmene(e) {
      var p = e.target;
      if (!p || !p.name) return;
      if (p.name === 'typ') obnovFirmu();
      if (p.hasAttribute('data-narozeni')) obnovZastupce();
      if (p.closest('details[data-osoba]')) nastavPovinnostOsob();
      var obal = obalPole(p);
      if (obal && obal.classList.contains('pole--chyba')) nastavChybu(p, chybaPole(p));
    }
    form.addEventListener('input', priZmene);
    form.addEventListener('change', priZmene);

    /* ---------- shrnutí ---------- */
    function hodnotaText(p) {
      if (p.type === 'checkbox') return p.checked ? 'ano' : '';
      if (p.tagName === 'SELECT') { var o = p.options[p.selectedIndex]; return o && o.value ? o.textContent : ''; }
      if (p.hasAttribute('data-narozeni')) { var x = datum(p.value); return platneDatum(x) ? x[2] + '. ' + x[1] + '. ' + x[0] : p.value; }
      return p.value.trim();
    }
    function radkyZ(koren) {
      var h = '', radio = {};
      vse('input, select, textarea', koren).forEach(function (p) {
        if (!p.name || p.type === 'hidden' || p.name === 'web_adresa' || !viditelny(p)) return;
        var t;
        if (p.type === 'radio') {
          if (radio[p.name]) return; radio[p.name] = 1;
          var z = vse('input[name="' + p.name + '"]', form).filter(function (r) { return r.checked; })[0];
          t = z ? z.value : '';
        } else {
          t = hodnotaText(p);
        }
        if (t === '') return;
        if (p.type === 'checkbox') {
          var lab = p.closest('label'); var k = lab ? lab.cloneNode(true) : null;
          if (k) { vse('input, .povinne, .vh, .souhlas-odkaz', k).forEach(function (x) { x.remove(); }); }
          h += '<li class="clen-shrnuti__souhlas">' + esc(k ? k.textContent.replace(/\s+/g, ' ').trim() : popisek(p)) + '</li>';
          return;
        }
        h += '<div><dt>' + esc(popisek(p)) + '</dt><dd>' + esc(t).replace(/\n/g, '<br>') + '</dd></div>';
      });
      return h;
    }
    function blokShrnuti(nadpis, krok, obsah) {
      if (!obsah) return '';
      var seznam = obsah.indexOf('<li') === 0;
      return '<section class="clen-shrnuti__blok"><div class="clen-shrnuti__hlava"><h4 class="clen-shrnuti__nadpis">' + esc(nadpis) + '</h4>'
        + '<button class="odkaz odkaz--text" type="button" data-krok-oprav="' + krok + '">Upravit<span class="vh"> – ' + esc(nadpis) + '</span></button></div>'
        + (seznam ? '<ul class="clen-shrnuti__souhlasy">' + obsah + '</ul>' : '<dl class="clen-shrnuti__udaje">' + obsah + '</dl>') + '</section>';
    }
    function vykresliShrnuti() {
      if (!shrnuti) return;
      var h = '';
      var k1 = kroky[0];
      var typRadek = typ ? '<div><dt>Typ členství</dt><dd>' + esc(hodnotaText(typ) || '—') + '</dd></div>' : '';
      h += blokShrnuti('Typ členství', 1, typRadek + (firma && !firma.hidden ? radkyZ(firma) : ''));
      h += blokShrnuti('Žadatel', 1, radkyZ(jeden('[data-osoba="0"]', k1)));
      osoby.forEach(function (det, i) {
        if (det.hasAttribute('data-skryta') || !hodnotaOsoby(det)) return;
        h += blokShrnuti('Osoba ' + ['II', 'III', 'IV', 'V', 'VI'][i], 1, radkyZ(det));
      });
      if (zastupce && !zastupce.hidden) h += blokShrnuti('Zákonný zástupce', 1, radkyZ(zastupce).replace(/<li[\s\S]*<\/li>/, ''));
      h += blokShrnuti('Adresa a kontakt', 2, radkyZ(kroky[1]));
      var souhlasy = radkyZ(kroky[2]);
      var pozn = souhlasy.match(/<div>[\s\S]*<\/div>/);
      h += blokShrnuti('Souhlasy', 3, souhlasy.replace(/<div>[\s\S]*<\/div>/, ''));
      if (pozn) h += blokShrnuti('Poznámka', 3, pozn[0]);
      shrnuti.innerHTML = h;
    }
    if (shrnuti) shrnuti.addEventListener('click', function (e) {
      var b = e.target.closest('[data-krok-oprav]');
      if (b) jdi(+b.getAttribute('data-krok-oprav'), true);
    });

    /* ---------- chyby ze serveru: otevřít krok s první chybou ---------- */
    var souhrnChyb = d.getElementById('prihlaska-chyby');
    if (souhrnChyb) souhrnChyb.addEventListener('click', function (e) {
      var a = e.target.closest('a[href^="#p-"]');
      if (!a) return;
      var pole = d.getElementById(a.getAttribute('href').slice(1));
      if (!pole) return;
      e.preventDefault();
      var krok = pole.closest('[data-krok]');
      if (krok) jdi(+krok.getAttribute('data-krok'), false);
      zobrazChybu(pole);
    });

    /* ---------- start ---------- */
    obnovFirmu();
    obnovOsoby();
    obnovZastupce();
    var prvniChyba = jeden('.pole--chyba input, .pole--chyba select, .pole--chyba textarea', form);
    var krokChyby = prvniChyba ? prvniChyba.closest('[data-krok]') : null;
    jdi(krokChyby ? +krokChyby.getAttribute('data-krok') : 1, false);
    if (souhrnChyb) { sjed(souhrnChyb); fokus(souhrnChyb); }

    /* rozhraní pro konfigurátor */
    return {
      vyberTyp: function (hodnota, osob) {
        if (typ && hodnota) {
          var ma = [].slice.call(typ.options).some(function (o) { return o.value === hodnota; });
          if (ma) { typ.value = hodnota; nastavChybu(typ, ''); }
        }
        obnovFirmu();
        if (osob > 1) zajistiOsoby(osob - 1);
        jdi(1, false);
      },
      prvniPole: function () { return typ || jeden('input:not([type="hidden"])', form); }
    };
  }

  /* ======================================================================
     KONFIGURÁTOR → PŘIHLÁŠKA
     ====================================================================== */
  function hodnotaTypu(v) {
    var c = C.clenstvi;
    if (!c) return { typ: '', osob: 0 };
    var r = c.spocitej(v);
    if (r.stav !== 'ok') return { typ: r.stav === 'nezname' ? 'jine' : '', osob: (+v.h || 0) + (+v.n || 0) + (+v.d || 0) };
    var h = +v.h || 0, n = +v.n || 0, dt = +v.d || 0, polozka = null;
    if (v.druh === 'firemni') polozka = c.firemni;
    else if (h === 1 && !n && !dt) polozka = c.hrajici[v.zv] || c.hrajici.dospely;
    else if (!h && !n && dt === 1) polozka = c.hrajici.mladez;
    else polozka = c.rodinne[h + '-' + n + '-' + dt];
    return { typ: polozka && polozka.id ? 'r' + polozka.id : '', osob: v.druh === 'firemni' ? 1 : h + n + dt };
  }

  function start() {
    var form = jeden('form[data-prihlaska]');
    var api = form ? prihlaska(form) : null;

    vse('form[data-konfigurator]').forEach(function (k) {
      if (!api) return;
      k.addEventListener('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(k);
        var v = { druh: fd.get('druh') || 'osobni', h: fd.get('h') || 0, n: fd.get('n') || 0, d: fd.get('d') || 0, zv: fd.get('zv') || '' };
        var zv = k.querySelector('[data-konf-zvyhodneni]');
        if (zv && zv.disabled) v.zv = '';
        var t = hodnotaTypu(v);
        api.vyberTyp(t.typ, t.osob);
        // na úzkém displeji rovnou k formuláři (hlava sekce by pole vytlačila pod lištu)
        var cil = window.matchMedia('(max-width: 899.98px)').matches ? d.getElementById('prihlaska-formular') : d.getElementById('prihlaska');
        sjed(cil);
        fokus(api.prvniPole());
      });
    });

    /* tisk Členského listu po odeslání */
    vse('[data-tisk-listu]').forEach(function (b) {
      b.hidden = false;
      b.addEventListener('click', function () {
        var html = d.documentElement;
        var list = jeden('.clen-dekujeme .clensky-list') || jeden('.clensky-list');
        if (!list) { window.print(); return; }
        var obal = d.createElement('div');
        obal.className = 'tisk-obal';
        var kopie = list.cloneNode(true);
        kopie.removeAttribute('id');
        kopie.classList.add('clensky-list--tisk');
        obal.appendChild(kopie);
        d.body.appendChild(obal);
        html.classList.add('tisk-listu');
        var hotovo = function () {
          html.classList.remove('tisk-listu');
          if (obal.parentNode) obal.parentNode.removeChild(obal);
          window.removeEventListener('afterprint', hotovo);
        };
        window.addEventListener('afterprint', hotovo);
        window.print();
        window.setTimeout(hotovo, 1500);
      });
    });

    /* po odeslání: fokus na poděkování */
    var ok = d.getElementById('prihlaska-ok');
    if (ok && window.location.hash === '#prihlaska') fokus(ok);
  }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();
})();
