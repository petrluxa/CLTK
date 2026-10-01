/* ==========================================================================
   I. ČLTK Praha · Klubový kalendář na úvodu [data-kalendar]
   Data: <script type="application/json" id="kalendar-data"> = kalendar_data()
   (inc/data.php) + token přihlášky u akcí, kde se lze přihlásit.

   – výchozí okno = NEJBLIŽŠÍ NADCHÁZEJÍCÍ akce podle dnešního data
     návštěvníka; pravidlo je stejné jako na serveru (nejblizsi_akce()):
       začátek = datum_od, jinak poslední den měsíce; konec = datum_do,
       jinak začátek; proběhlá = konec < dnes; nejbližší = nejmenší
       začátek >= dnes, jinak právě probíhající s nejpozdějším začátkem,
       a když není ani ta, poslední akce roku. Při shodě rozhoduje pořadí.
   – klik / Enter na akci v seznamu ukáže její detail bez přenačtení;
     na mobilu stránka sjede k oknu pod seznamem
   – „Přihlásit se“ otevře formulář přímo v okně a odešle ho fetch()em na
     akce.php?id=… (Accept: application/json); bez JavaScriptu vede každá
     položka seznamu na akce.php?id=…
   ========================================================================== */
(function () {
  'use strict';
  var d = document;
  var C = window.CLTK || {};
  var esc = C.esc || function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var NBSP = String.fromCharCode(160);

  /* typografie jako na serveru (typo_text): nezlomitelná mezera po jednopísmenných předložkách */
  var RE_PREDLOZKA = /(^|[\s(„“])([ksvzouaiKSVZOUAI])[ \t]+/g;
  function typo(s) {
    var t = String(s == null ? '' : s);
    t = t.replace(RE_PREDLOZKA, '$1$2' + NBSP).replace(RE_PREDLOZKA, '$1$2' + NBSP);   // dvakrát: „a v Praze“
    t = t.replace(/(\d)[ \t]+(Kč|km|m|min|h|hod|%|let|×|kurtů|kurty|osob|MB)(?=[\s.,;:)\/–]|$)/g, '$1' + NBSP + '$2');
    return esc(t).replace(new RegExp(NBSP, 'g'), '&nbsp;');
  }
  function sipka(druh) { return '<span class="sipka' + (druh ? ' sipka--' + druh : '') + '" aria-hidden="true"></span>'; }
  function pohyb() { return C.pohybPovolen ? C.pohybPovolen() : true; }
  function dnesMistni() {
    var t = new Date();
    return t.getFullYear() + '-' + ('0' + (t.getMonth() + 1)).slice(-2) + '-' + ('0' + t.getDate()).slice(-2);
  }

  /* pravidlo „nejbližší akce“ – shodné s nejblizsi_akce() v inc/data.php */
  function nejblizsi(akce, dnes) {
    var nad = null, probiha = null;
    akce.forEach(function (a) {
      if (a.konec < dnes) return;
      if (a.zacatek >= dnes) { if (!nad || a.zacatek < nad.zacatek) nad = a; }
      else if (!probiha || a.zacatek > probiha.zacatek) probiha = a;
    });
    return nad || probiha;
  }

  /* stav přihlašování pro návštěvníka (akce_stav_prihlasek() v komponenty.php) */
  function stavPrihlasek(a) {
    if (!a.prihlaseni_zapnuto || a.prihlaseni) return '';
    if (a.probehla) return 'Akce už proběhla.';
    if (a.obsazeno) return 'Kapacita akce je naplněná – přihlašování je uzavřené.';
    return 'Přihlašování na tuto akci je uzavřené.';
  }

  /* detail v okně – stejné HTML jako kalendar_detail_html() na serveru */
  function detailHtml(a) {
    var meta = [a.stitek, a.misto, a.cas].map(function (x) { return String(x || '').trim(); }).filter(Boolean).join(' · ');
    var h = '<article class="kal-detail" data-akce-id="' + a.id + '" aria-labelledby="kal-detail-nazev">';
    h += '<p class="stitek kal-detail__termin">' + typo(a.termin_dlouze) + '</p>';
    h += '<h3 class="kal-detail__nazev" id="kal-detail-nazev">' + typo(a.nazev) + '</h3>';
    if (meta) h += '<p class="kal-detail__meta">' + typo(meta) + '</p>';
    if (a.probehla) h += '<p class="kal-detail__stav"><span class="doplni">proběhlo</span></p>';
    if (a.perex_html) h += '<div class="kal-detail__perex">' + a.perex_html + '</div>';   // perex_html je už vyčištěné na serveru (paragraphs)
    var akce = '';
    if (a.prihlaseni) {
      akce += '<a class="btn" href="' + esc(a.detail_url + '#prihlaska') + '" data-prihlasit>' + typo((a.formular && a.formular.tlacitko) || 'Přihlásit se') + ' ' + sipka('ven') + '</a>';
    }
    if (a.odkaz && a.odkaz_text) {
      akce += '<a class="odkaz" href="' + esc(a.odkaz) + '"' + (a.odkaz_externi ? ' target="_blank" rel="noopener"' : '') + '>' + typo(a.odkaz_text) + ' ' + sipka(a.odkaz_externi ? 'ven' : '') +
        (a.odkaz_externi ? '<span class="vh"> (v novém okně)</span>' : '') + '</a>';
    }
    if (akce) h += '<div class="kal-detail__akce">' + akce + '</div>';
    var stav = stavPrihlasek(a);
    if (stav && !a.probehla) h += '<p class="kal-detail__stav drobne">' + esc(stav) + '</p>';
    if (a.popis_html) h += '<p class="kal-detail__vice"><a href="' + esc(a.detail_url) + '">Více o akci</a></p>';
    return h + '</article>';
  }

  /* formulář přihlášky – pole podle akce_formular() (u každé akce jiná) */
  function formularHtml(a, souhlas) {
    var f = a.formular || { pole: {}, povinne: {}, vlastni: [] };
    var id = 'kp-' + a.id + '-';
    var hv = '<span class="povinne" aria-hidden="true">*</span>';
    function pole(nazev, label, vstup, povinne) {
      return '<div class="pole" data-pole="' + nazev + '"><label for="' + id + nazev + '">' + typo(label) + (povinne ? hv : '') + '</label>' + vstup + '</div>';
    }
    var h = '<form class="formular kal-formular" method="post" action="' + esc(a.detail_url + '#prihlaska') + '" novalidate data-kal-formular>';
    h += '<input type="hidden" name="_ft" value="' + esc(a.token || '') + '">';
    h += '<div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden"><label>Toto pole nevyplňujte<input type="text" name="web_adresa" value="" tabindex="-1" autocomplete="off"></label></div>';
    h += '<div class="hlaska hlaska--chyba" data-kal-chyba tabindex="-1" hidden></div>';
    h += pole('jmeno', 'Jméno a příjmení', '<input type="text" id="' + id + 'jmeno" name="jmeno" autocomplete="name" required maxlength="160">', true);
    h += pole('email', 'E-mail', '<input type="email" id="' + id + 'email" name="email" autocomplete="email" inputmode="email" required maxlength="160">', true);
    if (f.pole && f.pole.telefon) h += pole('telefon', 'Telefon', '<input type="tel" id="' + id + 'telefon" name="telefon" autocomplete="tel" inputmode="tel" maxlength="40"' + (f.povinne && f.povinne.telefon ? ' required' : '') + '>', f.povinne && f.povinne.telefon);
    if (f.pole && f.pole.pocet) h += pole('pocet', 'Počet osob', '<input type="number" id="' + id + 'pocet" name="pocet" value="1" min="1" max="50" inputmode="numeric">', false);
    (f.vlastni || []).forEach(function (p) {
      var k = 'pole_' + p.klic, req = p.povinne ? ' required' : '';
      if (p.typ === 'zaskrtavatko') {
        h += '<div class="pole" data-pole="' + esc(k) + '"><label class="volba" for="' + id + esc(k) + '"><input type="checkbox" id="' + id + esc(k) + '" name="' + esc(k) + '" value="1"' + req + '><span>' + typo(p.popisek) + (p.povinne ? hv : '') + '</span></label></div>';
      } else if (p.typ === 'vyber') {
        var o = '<option value="">Vyberte…</option>' + (p.moznosti || []).map(function (m) { return '<option value="' + esc(m) + '">' + esc(m) + '</option>'; }).join('');
        h += pole(k, p.popisek, '<select id="' + id + esc(k) + '" name="' + esc(k) + '"' + req + '>' + o + '</select>', p.povinne);
      } else {
        h += pole(k, p.popisek, '<input type="text" id="' + id + esc(k) + '" name="' + esc(k) + '" maxlength="500"' + (p.typ === 'cislo' ? ' inputmode="decimal"' : '') + req + '>', p.povinne);
      }
    });
    if (f.pole && f.pole.poznamka) h += pole('poznamka', 'Poznámka', '<textarea id="' + id + 'poznamka" name="poznamka" rows="3" maxlength="2000"' + (f.povinne && f.povinne.poznamka ? ' required' : '') + '></textarea>', f.povinne && f.povinne.poznamka);
    h += '<div class="pole" data-pole="souhlas"><label class="volba" for="' + id + 'souhlas"><input type="checkbox" id="' + id + 'souhlas" name="souhlas" value="1" required><span>' + typo(souhlas) + hv + '</span></label></div>';
    if (f.poznamka) h += '<p class="formular__pozn">' + typo(f.poznamka) + '</p>';
    h += '<div class="formular__akce"><button class="btn" type="submit">' + typo(f.tlacitko || 'Přihlásit se') + ' ' + sipka() + '</button></div>';
    h += '<button class="odkaz odkaz--text kal-formular__zpet" type="button" data-kal-zpet>Zpět na detail akce</button>';
    return h + '</form>';
  }

  function init(koren) {
    var data = C.data ? C.data('kalendar-data') : null;
    var okno = d.getElementById('kalendar-okno');
    if (!data || !data.akce || !okno) return;
    var akce = data.akce;
    var podleId = {};
    akce.forEach(function (a) { podleId[a.id] = a; });
    var dnes = data.ladeni && data.dnes ? data.dnes : dnesMistni();
    var mqMobil = window.matchMedia('(max-width: 899.98px)');

    /* proběhlé akce podle data návštěvníka */
    akce.forEach(function (a) {
      a.probehla = a.konec < dnes;
      if (a.probehla) a.prihlaseni = false;
      var li = koren.querySelector('.kal-polozka[data-akce="' + a.id + '"]');
      if (li) li.classList.toggle('je-probehla', a.probehla);
    });

    var vybrana = null;
    function oznac(id) {
      [].forEach.call(koren.querySelectorAll('[data-akce-odkaz]'), function (o) {
        o.setAttribute('aria-controls', 'kalendar-okno');
        if (+o.getAttribute('data-akce-odkaz') === id) o.setAttribute('aria-current', 'true'); else o.removeAttribute('aria-current');
      });
    }
    function ukaz(id, jakUzivatel) {
      var a = podleId[id];
      if (!a) return;
      vybrana = a;
      oznac(id);
      okno.innerHTML = detailHtml(a);
      if (jakUzivatel) {
        if (mqMobil.matches) okno.scrollIntoView({ behavior: pohyb() ? 'smooth' : 'auto', block: 'start' });
        if (C.fokus) C.fokus(okno); else okno.focus();
      }
    }

    /* výchozí stav: nejbližší akce podle dnešního data návštěvníka */
    var nej = nejblizsi(akce, dnes);
    var vychozi = nej ? nej.id : (akce.length ? akce[akce.length - 1].id : null);
    var naServeru = okno.querySelector('[data-akce-id]');
    if (vychozi !== null) {
      if (!naServeru || +naServeru.getAttribute('data-akce-id') !== vychozi || podleId[vychozi].probehla !== !!naServeru.querySelector('.doplni')) ukaz(vychozi, false);
      else { vybrana = podleId[vychozi]; oznac(vychozi); }
    }

    /* klik na akci v seznamu */
    koren.addEventListener('click', function (e) {
      var o = e.target.closest('[data-akce-odkaz]');
      if (!o || e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
      e.preventDefault();
      ukaz(+o.getAttribute('data-akce-odkaz'), true);
    });

    /* přihláška v okně */
    okno.addEventListener('click', function (e) {
      var p = e.target.closest('[data-prihlasit]');
      if (p && vybrana && vybrana.prihlaseni && vybrana.token && window.fetch && window.FormData) {
        e.preventDefault();
        var clanek = okno.querySelector('.kal-detail');
        [].forEach.call(clanek.querySelectorAll('.kal-detail__perex, .kal-detail__akce, .kal-detail__stav, .kal-detail__vice'), function (x) { x.remove(); });
        clanek.insertAdjacentHTML('beforeend', formularHtml(vybrana, data.souhlas || ''));
        var prvni = clanek.querySelector('input[name="jmeno"]');
        if (prvni) prvni.focus();
        return;
      }
      if (e.target.closest('[data-kal-zpet]') && vybrana) { ukaz(vybrana.id, false); if (C.fokus) C.fokus(okno); }
    });

    okno.addEventListener('submit', function (e) {
      var f = e.target.closest('[data-kal-formular]');
      if (!f || !vybrana) return;
      e.preventDefault();
      var a = vybrana;
      var chybaBox = f.querySelector('[data-kal-chyba]');
      [].forEach.call(f.querySelectorAll('.pole__chyba'), function (x) { x.remove(); });
      [].forEach.call(f.querySelectorAll('[aria-invalid]'), function (x) { x.removeAttribute('aria-invalid'); x.removeAttribute('aria-describedby'); });
      [].forEach.call(f.querySelectorAll('.pole--chyba'), function (x) { x.classList.remove('pole--chyba'); });
      chybaBox.hidden = true;
      var tl = f.querySelector('button[type="submit"]');
      tl.disabled = true;
      fetch(a.detail_url, { method: 'POST', body: new FormData(f), headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json().catch(function () { return { ok: false, chyba: '' }; }); })
        .then(function (j) {
          tl.disabled = false;
          if (j && j.ok) {
            var clanek = okno.querySelector('.kal-detail');
            f.remove();
            clanek.insertAdjacentHTML('beforeend', '<div class="hlaska hlaska--ok" tabindex="-1" role="status"><span class="hlaska__titul">' + typo(j.zprava || 'Děkujeme, přihláška je uložená.') + '</span><p>Na akci <b>' + typo(a.nazev) + '</b> jsme si vás zapsali. Kdyby se něco změnilo, ozveme se e-mailem.</p></div>' +
              '<button class="odkaz odkaz--text kal-formular__zpet" type="button" data-kal-zpet>Zpět na detail akce</button>');
            var ok = clanek.querySelector('.hlaska--ok');
            if (ok) ok.focus();
            return;
          }
          var chyby = (j && j.chyby) || {};
          var prvniPole = null;
          Object.keys(chyby).forEach(function (k) {
            var p = f.querySelector('[data-pole="' + k + '"]');
            if (!p) return;
            p.classList.add('pole--chyba');
            var vstup = p.querySelector('input, select, textarea');
            var cid = 'kp-' + a.id + '-' + k + '-chyba';
            p.insertAdjacentHTML('beforeend', '<p class="pole__chyba" id="' + cid + '">' + esc(chyby[k]) + '</p>');
            if (vstup) { vstup.setAttribute('aria-invalid', 'true'); vstup.setAttribute('aria-describedby', cid); if (!prvniPole) prvniPole = vstup; }
          });
          var zprava = (j && j.chyba) || (Object.keys(chyby).length ? 'Zkontrolujte prosím zvýrazněná pole.' : '');
          if (!zprava) zprava = 'Přihlášku se nepodařilo odeslat.';
          chybaBox.innerHTML = '<span class="hlaska__titul">' + esc(zprava) + '</span>' +
            (!j || !j.chyby || !Object.keys(j.chyby).length ? '<p>Zkuste to prosím znovu, nebo použijte <a href="' + esc(a.detail_url + '#prihlaska') + '">formulář na stránce akce</a>.</p>' : '');
          chybaBox.hidden = false;
          if (prvniPole) prvniPole.focus(); else if (C.fokus) C.fokus(chybaBox);
        })
        .catch(function () {
          tl.disabled = false;
          chybaBox.innerHTML = '<span class="hlaska__titul">Přihlášku se nepodařilo odeslat.</span><p>Zkontrolujte připojení a zkuste to znovu, nebo použijte <a href="' + esc(a.detail_url + '#prihlaska') + '">formulář na stránce akce</a>.</p>';
          chybaBox.hidden = false;
          if (C.fokus) C.fokus(chybaBox);
        });
    });
  }

  function start() { [].forEach.call(d.querySelectorAll('[data-kalendar]'), init); }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();
})();
