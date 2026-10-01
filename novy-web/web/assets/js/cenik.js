/* ==========================================================================
   I. ČLTK Praha – ceníky kurtů: rozbor ceníků z databáze a kalkulačka ceny
   (cenik-kurtu.php). Kalkulačka je převzatá z návrhu 3 „Živá Štvanice“,
   vysázená v jazyce Varianty 4 (segment, čipy, krokovač, přepínač, cena).

   Data vkládá stránka (vše z administrace → Ceníky, nic natvrdo):
     <script type="application/json" id="ceniky-kurtu">
       {leto: {list, sekce: [{…, radky: […]}]}, zima: {…}, dnes, ladeni,
        rezervace, telefon, telefon_text, cenik_url}</script>
   Kalkulačka se vykreslí do [data-kalkulacka]. Bez JavaScriptu zůstanou
   na stránce tabulky ceníku (kalkulačka je jen pomůcka navíc).

   CLTK.ceniky = sdílené pomůcky (čte je i mapa.js na areal.php):
     cena('12 480 Kč (480 Kč/hod)') → {cena: 12480, zaHod: 480}
     kurty('kurty C, 1, 2, 3, 4 · …') → ['C','1','2','3','4']
     pripravit(data) → {zima: [haly], leto: {mista, priplatky}, sezona: 'zima'|'leto', …}
   Adresa cenik-kurtu.php?kurt=5#kalkulacka předvybere halu / místo s kurtem 5.
   ========================================================================== */
(function () {
  'use strict';

  var d = document;
  var CLTK = window.CLTK = window.CLTK || {};
  var NB = String.fromCharCode(160);
  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  };
  function kc(n) { return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, NB) + NB + 'Kč'; }
  function mnozne(n, a, b, c) { return n === 1 ? a : (n >= 2 && n <= 4 ? b : c); }
  /* nezlomitelná mezera po jednopísmenných předložkách (jako typo() v PHP) */
  function typo(s) {
    var re = /(^|[\s(„])([ksvzouaiKSVZOUAI]) /g, t = esc(s);
    return t.replace(re, '$1$2' + NB).replace(re, '$1$2' + NB);   // dvakrát: „a v kurtu“
  }

  /* ── Rozbor textů ceníku ─────────────────────────────────────────────── */

  /** „540 Kč“ / „12 480 Kč (480 Kč/hod)“ / „zdarma“ → {cena, zaHod}; prázdné / nečitelné → null */
  function cena(text) {
    var t = String(text == null ? '' : text).replace(/ /g, ' ').trim();
    if (!t || t === '–' || t === '-') return null;
    if (/^zdarma/i.test(t)) return { cena: 0, zaHod: null };
    var m = t.match(/(\d{1,3}(?: \d{3})+|\d+)/);
    if (!m) return null;
    var hod = t.match(/\(\s*(\d{1,3}(?: \d{3})+|\d+)\s*Kč/i);
    return { cena: parseInt(m[1].replace(/ /g, ''), 10), zaHod: hod ? parseInt(hod[1].replace(/ /g, ''), 10) : null };
  }

  /** Čísla kurtů z textu („kurty 5, 6 · …“, „č. 1–9“, „kurty P1, P2“) → ['5','6'] */
  function kurty(text) {
    var t = String(text || '').replace(/ /g, ' ');
    var re = /(?:kurt[yůu]?|č\.)\s+((?:P?\d+|C)(?:\s*(?:,|–|-|a)\s*(?:P?\d+|C))*)/gi;
    var m, vysledek = [];
    while ((m = re.exec(t))) {
      m[1].split(/\s*(?:,|\sa\s)\s*/).forEach(function (cast) {
        var r = cast.match(/^(\d+)\s*[–-]\s*(\d+)$/);
        if (r) { for (var i = +r[1]; i <= +r[2] && i - +r[1] < 60; i++) vysledek.push(String(i)); }
        else if (/^(P?\d+|C)$/i.test(cast.trim())) vysledek.push(cast.trim().toUpperCase());
      });
    }
    return vysledek.filter(function (k, i) { return vysledek.indexOf(k) === i; });
  }

  /** První dvě data „d. m. rrrr“ v textu → ['2026-10-05', '2027-04-04'] (nebo []) */
  function obdobi(text) {
    var re = /(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})/g, m, v = [];
    while ((m = re.exec(String(text || ''))) && v.length < 2) {
      v.push(m[3] + '-' + ('0' + m[2]).slice(-2) + '-' + ('0' + m[1]).slice(-2));
    }
    return v;
  }
  /** Část popisu sekce s datem („5. 10. 2026 – 4. 4. 2027“) */
  function obdobiText(popis) {
    var casti = String(popis || '').split('·').map(function (s) { return s.trim(); });
    for (var i = 0; i < casti.length; i++) if (/\d{1,2}\.\s*\d{1,2}\.\s*\d{4}/.test(casti[i])) return casti[i];
    return '';
  }
  /** Název záložky z názvu ceníku: „Kurty – zimní sezóna 2026/27“ → „Zimní sezóna 2026/27“ */
  function nazevSezony(list, vychozi) {
    var n = String((list && list.nazev) || '').replace(/^\s*kurty\s*[–-]\s*/i, '').trim();
    return n ? n.charAt(0).toUpperCase() + n.slice(1) : vychozi;
  }
  function dnesIso(data) {
    if (data && data.ladeni && data.dnes) return data.dnes;
    var x = new Date();
    return x.getFullYear() + '-' + ('0' + (x.getMonth() + 1)).slice(-2) + '-' + ('0' + x.getDate()).slice(-2);
  }

  /** Data ceníků z databáze → tvar pro kalkulačku a mapu */
  function pripravit(data) {
    data = data || {};
    var zimaC = data.zima || null, letoC = data.leto || null;
    var zima = [], mista = [], priplatky = [];
    if (zimaC && zimaC.sekce) {
      zimaC.sekce.forEach(function (s) {
        var tyd = String(s.popis || '').match(/(\d+)\s*týd/);
        var pasma = (s.radky || []).map(function (r) {
          return { nazev: r.nazev, hod: cena(r.cena), clen: cena(r.cena_clen), sez: cena(r.cena_sezona), sezClen: cena(r.cena_sezona_clen),
                   text: { hod: r.cena, clen: r.cena_clen, sez: r.cena_sezona, sezClen: r.cena_sezona_clen } };
        }).filter(function (p) { return p.hod; });
        if (!pasma.length) return;
        zima.push({
          id: 'z' + s.id, nazev: s.nazev, popis: s.popis || '', kurty: kurty(s.popis), obdobi: obdobiText(s.popis),
          tydnu: tyd ? +tyd[1] : null, antuka: /antuk/i.test(s.nazev + ' ' + s.popis), pevna: /pevn/i.test(s.nazev), pasma: pasma
        });
      });
    }
    if (letoC && letoC.sekce) {
      letoC.sekce.forEach(function (s) {
        (s.radky || []).forEach(function (r) {
          var x = { id: 'l' + r.id, nazev: r.nazev, poznamka: r.poznamka || '', kurty: kurty(r.nazev),
                    cena: cena(r.cena), clen: cena(r.cena_clen), text: { cena: r.cena, clen: r.cena_clen } };
          if (!x.cena) return;
          (String(r.cena_clen || '').trim() === '' ? priplatky : mista).push(x);
        });
      });
      /* příplatek bez kurtů, které by patřily k nějakému místu, je samostatné místo (bez členské ceny) */
      priplatky = priplatky.filter(function (p) {
        var patri = p.kurty.length && mista.some(function (m) { return p.kurty.every(function (k) { return m.kurty.indexOf(k) >= 0; }); });
        if (!patri) mista.push(p);
        return patri;
      });
    }
    var dnes = dnesIso(data);
    var o = obdobi(zimaC && zimaC.list ? zimaC.list.obdobi : '');
    if (o.length < 2) {   // období z popisů sekcí (nejmenší začátek – největší konec)
      zima.forEach(function (h) { var x = obdobi(h.popis); if (x.length === 2) { if (!o[0] || x[0] < o[0]) o[0] = x[0]; if (!o[1] || x[1] > o[1]) o[1] = x[1]; } });
    }
    var sezona = o.length === 2 && dnes >= o[0] && dnes <= o[1] ? 'zima' : 'leto';
    if (!zima.length) sezona = 'leto';
    if (!mista.length && zima.length) sezona = 'zima';
    return {
      zima: zima, leto: { mista: mista, priplatky: priplatky }, sezona: sezona, dnes: dnes,
      nazevZima: nazevSezony(zimaC && zimaC.list, 'Zima'), nazevLeto: nazevSezony(letoC && letoC.list, 'Léto'),
      poznamkaZima: zimaC && zimaC.list ? String(zimaC.list.poznamka_dole || '').split(/\n\s*\n/)[0].trim() : '',
      poznamkaLeto: letoC && letoC.list ? String(letoC.list.poznamka_dole || '').split(/\n\s*\n/)[0].trim() : ''
    };
  }

  /** Příplatky, které patří k místu (např. svícení kurtů 2, 3, 4 k hlavnímu areálu) */
  function priplatkyMista(c, misto) {
    return c.leto.priplatky.filter(function (p) { return p.kurty.every(function (k) { return misto.kurty.indexOf(k) >= 0; }); });
  }

  CLTK.ceniky = { cena: cena, kurty: kurty, obdobi: obdobi, obdobiText: obdobiText, pripravit: pripravit, priplatkyMista: priplatkyMista, kc: kc, typo: typo };

  /* ── Kalkulačka [data-kalkulacka] ────────────────────────────────────── */
  function kalkulacka(box, data) {
    var c = pripravit(data);
    if (!c.zima.length && !c.leto.mista.length) return;
    var uid = 'kalk-' + Math.random().toString(36).slice(2, 7);
    var st = { sezona: c.sezona, hala: c.zima[0] ? c.zima[0].id : '', pasmo: 0, forma: 'hodina', hodin: 1, clen: false,
               misto: c.leto.mista[0] ? c.leto.mista[0].id : '', priplatek: false };

    /* předvýběr z adresy: ?kurt=5 (odkaz „Spočítat cenu“ z plánu areálu) */
    var kurt = '';
    try { kurt = (new URLSearchParams(window.location.search).get('kurt') || '').toUpperCase(); } catch (e) { /* starý prohlížeč */ }
    if (kurt) {
      var hz = c.zima.filter(function (h) { return h.kurty.indexOf(kurt) >= 0; })[0];
      var ml = c.leto.mista.filter(function (m) { return m.kurty.indexOf(kurt) >= 0; })[0];
      if (st.sezona === 'zima' && hz) st.hala = hz.id;
      else if (st.sezona === 'leto' && ml) st.misto = ml.id;
      else if (hz) { st.sezona = 'zima'; st.hala = hz.id; }
      else if (ml) { st.sezona = 'leto'; st.misto = ml.id; }
    }

    var rezervace = String(data.rezervace || '');
    var tel = String(data.telefon || ''), telText = String(data.telefon_text || '');
    var sezony = [];   // pořadí jako záložky ceníku: Léto · Zima (vybraná je dnešní sezóna)
    if (c.leto.mista.length) sezony.push(['leto', c.nazevLeto]);
    if (c.zima.length) sezony.push(['zima', c.nazevZima]);

    box.innerHTML =
      '<form class="kalk__ovladani" novalidate aria-label="Kalkulačka ceny kurtu">' +
        (sezony.length > 1 ? '<fieldset class="kalk__skupina"><legend class="pole__label">Sezóna</legend><div class="segment kalk__segment">' +
          sezony.map(function (s) { return '<label><input type="radio" name="sezona" value="' + s[0] + '"><span>' + esc(s[1]) + '</span></label>'; }).join('') + '</div></fieldset>' : '') +
        '<fieldset class="kalk__skupina"><legend class="pole__label">Kde chcete hrát</legend><div class="kalk__mista" data-k-kde></div></fieldset>' +
        '<fieldset class="kalk__skupina" data-k-cast="kdy"><legend class="pole__label">Kdy</legend><div class="kalk__cipy" data-k-kdy></div></fieldset>' +
        '<fieldset class="kalk__skupina" data-k-cast="forma"><legend class="pole__label">Jak často</legend><div class="segment kalk__segment">' +
          '<label><input type="radio" name="forma" value="hodina"><span>Jednotlivé hodiny</span></label>' +
          '<label><input type="radio" name="forma" value="predplatne"><span data-k-predplatne>Předplatné</span></label></div></fieldset>' +
        '<div class="krokovac kalk__hodiny" data-krokovac data-k-cast="hodin">' +
          '<label class="krokovac__popis" for="' + uid + '-h">Počet hodin<small>1–10 hodin</small></label>' +
          '<div class="krokovac__ovladani"><button type="button" class="krokovac__tl" data-krok="-1" aria-label="O hodinu méně"></button>' +
          '<input id="' + uid + '-h" name="hodin" type="number" inputmode="numeric" min="1" max="10" value="1">' +
          '<button type="button" class="krokovac__tl" data-krok="1" aria-label="O hodinu více"></button></div></div>' +
        '<div data-k-cast="priplatky"></div>' +
        '<div class="kalk__clen"><button type="button" class="prepinac-pristupnost" aria-pressed="false" data-k-clen>Jsem člen klubu</button>' +
          '<span class="drobne">ukáže členskou cenu a&nbsp;úsporu</span></div>' +
      '</form>' +
      '<div class="kalk__vysledek" aria-live="polite">' +
        '<p class="stitek">Výsledná cena</p><p class="kalk__co" data-k-co></p><p class="kalk__kdy" data-k-kdyText></p>' +
        '<ul class="radky kalk__radky" data-k-radky></ul>' +
        '<p class="kalk__cena"><span class="cena--velka" data-k-cena></span><span class="kalk__za" data-k-za></span></p>' +
        '<p class="kalk__uspora" data-k-uspora hidden></p>' +
        '<p class="kalk__pozn" data-k-pozn></p>' +
        '<div class="kalk__akce">' +
          (rezervace ? '<a class="btn" href="' + esc(rezervace) + '" target="_blank" rel="noopener">Rezervovat kurt <span class="sipka sipka--ven" aria-hidden="true"></span><span class="vh"> (rezervační systém v novém okně)</span></a>' : '') +
          (tel ? '<a class="kalk__tel" href="tel:' + esc(tel.replace(/[^\d+]/g, '')) + '">' + esc(telText || tel) + ' <small>recepce</small></a>' : '') +
        '</div></div>';

    var form = box.querySelector('form');
    var q = function (s) { return box.querySelector(s); };
    var kde = q('[data-k-kde]'), kdy = q('[data-k-kdy]'), pripl = q('[data-k-cast="priplatky"]');
    var hodiny = q('input[name="hodin"]'), clenTl = q('[data-k-clen]');
    form.addEventListener('submit', function (e) { e.preventDefault(); });

    function hala() { return c.zima.filter(function (h) { return h.id === st.hala; })[0] || c.zima[0]; }
    function misto() { return c.leto.mista.filter(function (m) { return m.id === st.misto; })[0] || c.leto.mista[0]; }
    function nejnizsi(h) {
      var ceny = h.pasma.map(function (p) { return p.hod.cena; });
      return Math.min.apply(null, ceny);
    }

    function kresliVolby() {
      var s = form.querySelector('input[name="sezona"][value="' + st.sezona + '"]');
      if (s) s.checked = true;
      if (st.sezona === 'zima') {
        kde.innerHTML = c.zima.map(function (h) {
          var pod = [h.kurty.length ? 'kurty ' + h.kurty.join(', ') : '', h.obdobi].filter(Boolean).join(' · ');
          return '<label class="kalk__misto"><input type="radio" name="hala" value="' + esc(h.id) + '"' + (h.id === st.hala ? ' checked' : '') + '>' +
            '<span class="kalk__misto-nazev">' + typo(h.nazev) + '</span>' + (pod ? '<span class="kalk__misto-pod">' + typo(pod) + '</span>' : '') +
            '<span class="kalk__misto-cena">od ' + kc(nejnizsi(h)) + '/hod</span></label>';
        }).join('');
        var h = hala();
        if (st.pasmo >= h.pasma.length) st.pasmo = 0;
        kdy.innerHTML = h.pasma.map(function (p, i) {
          return '<label class="cip"><input type="radio" name="pasmo" value="' + i + '"' + (i === st.pasmo ? ' checked' : '') + '><span>' + typo(p.nazev) + '</span></label>';
        }).join('');
        var maPredplatne = h.pasma.some(function (p) { return p.sez && p.sezClen; });
        q('[data-k-predplatne]').textContent = 'Předplatné' + (h.tydnu ? ' · 1 hodina týdně, ' + h.tydnu + NB + mnozne(h.tydnu, 'týden', 'týdny', 'týdnů') : '');
        q('[data-k-cast="forma"]').hidden = !maPredplatne;
        if (!maPredplatne) st.forma = 'hodina';
        pripl.innerHTML = '';
      } else {
        kde.innerHTML = c.leto.mista.map(function (m) {
          return '<label class="kalk__misto"><input type="radio" name="misto" value="' + esc(m.id) + '"' + (m.id === st.misto ? ' checked' : '') + '>' +
            '<span class="kalk__misto-nazev">' + typo(m.nazev) + '</span>' + (m.poznamka ? '<span class="kalk__misto-pod">' + typo(m.poznamka) + '</span>' : '') +
            '<span class="kalk__misto-cena">' + typo(m.text.cena) + (m.text.clen ? ' · člen ' + typo(m.text.clen) : '') + '</span></label>';
        }).join('');
        kdy.innerHTML = '';
        var pp = priplatkyMista(c, misto());
        pripl.innerHTML = pp.map(function (p) {
          return '<label class="volba"><input type="checkbox" name="priplatek" value="' + esc(p.id) + '"' + (st.priplatek ? ' checked' : '') + '><span>' + typo(p.nazev) + ' (+' + typo(p.text.cena) + ')</span></label>';
        }).join('');
        if (!pp.length) st.priplatek = false;
        st.forma = 'hodina';
      }
      q('[data-k-cast="kdy"]').hidden = st.sezona !== 'zima';
      if (st.sezona !== 'zima') q('[data-k-cast="forma"]').hidden = true;
      var f = form.querySelector('input[name="forma"][value="' + st.forma + '"]');
      if (f) f.checked = true;
    }

    function radek(nazev, hodnota, preskrtnout) {
      return '<li class="radek radek--vodici' + (preskrtnout ? ' kalk__radek--pryc' : '') + '"><span class="radek__nazev">' + nazev + '</span><span class="radek__hodnota">' + hodnota + '</span></li>';
    }

    function spocti() {
      var hod = Math.max(1, Math.min(10, parseInt(hodiny.value, 10) || 1));
      st.hodin = hod;
      q('[data-k-cast="hodin"]').hidden = st.forma !== 'hodina';
      var tvar = mnozne(hod, 'hodina', 'hodiny', 'hodin');
      var co = '', kdyT = '', radky = '', vysl = null, za = '', uspora = 0, usporaText = '', pozn = '';
      if (st.sezona === 'zima') {
        var h = hala(), p = h.pasma[st.pasmo] || h.pasma[0];
        co = h.nazev;
        kdyT = [h.kurty.length ? 'kurty ' + h.kurty.join(', ') : '', p.nazev].filter(Boolean).join(' · ');
        if (st.forma === 'hodina') {
          var ver = p.hod.cena * hod, cl = p.clen ? p.clen.cena * hod : null;
          radky += radek('Veřejnost · ' + hod + NB + tvar, kc(ver), st.clen && cl !== null);
          radky += radek('Člen klubu · ' + hod + NB + tvar, cl !== null ? kc(cl) : 'podle ceníku', false);
          vysl = st.clen && cl !== null ? cl : ver;
          za = hod === 1 ? 'za hodinu, ' + (st.clen && cl !== null ? 'členská cena' : 'cena pro veřejnost')
                         : 'za ' + hod + NB + tvar + ' (' + kc(st.clen && cl !== null ? p.clen.cena : p.hod.cena) + ' za hodinu)';
          if (cl !== null) { uspora = ver - cl; usporaText = (st.clen ? 'Ušetříte ' : 'Členové ušetří ') + kc(uspora) + (hod === 1 ? ' za hodinu' : ''); }
          pozn = h.obdobi ? 'Období ' + h.obdobi + (h.tydnu ? ' · ' + h.tydnu + NB + mnozne(h.tydnu, 'týden', 'týdny', 'týdnů') : '') + '.' : '';
        } else {
          var sv = p.sez ? p.sez.cena : null, sc = p.sezClen ? p.sezClen.cena : null;
          radky += radek('Předplatné · veřejnost', sv !== null ? kc(sv) : 'podle ceníku', st.clen && sc !== null);
          radky += radek('Předplatné · člen klubu', sc !== null ? kc(sc) : 'podle ceníku', false);
          var zaHod = st.clen ? (p.sezClen && p.sezClen.zaHod) : (p.sez && p.sez.zaHod);
          if (zaHod) radky += radek('Přepočet na hodinu', kc(zaHod), false);
          vysl = st.clen && sc !== null ? sc : sv;
          za = '1 hodina týdně' + (h.tydnu ? ' po ' + h.tydnu + NB + mnozne(h.tydnu, 'týden', 'týdny', 'týdnů') : '') + (h.obdobi ? ' · ' + h.obdobi : '');
          if (sv !== null && sc !== null) { uspora = sv - sc; usporaText = (st.clen ? 'Ušetříte ' : 'Členové ušetří ') + kc(uspora) + ' za sezónu'; }
          pozn = c.poznamkaZima;
        }
      } else {
        var m = misto();
        var pp = st.priplatek ? priplatkyMista(c, m)[0] : null;
        co = m.nazev;
        kdyT = m.poznamka;
        var verL = (m.cena.cena + (pp ? pp.cena.cena : 0)) * hod;
        var clL = m.clen ? m.clen.cena * hod : null;
        radky += radek('Veřejnost · ' + hod + NB + tvar + (pp ? ' se svícením' : ''), kc(verL), st.clen && clL !== null);
        radky += radek('Člen klubu · ' + hod + NB + tvar, clL === null ? 'podle ceníku' : (clL === 0 ? 'zdarma' : kc(clL)), false);
        if (pp) radky += radek(typo(pp.nazev) + ' · člen', '<span class="doplni">doplní klub</span>', false);
        vysl = st.clen && clL !== null ? clL : verL;
        if (st.clen && clL !== null) {
          za = clL === 0 ? (pp ? 'kurt mají členové zdarma, cenu svícení pro členy doplní klub' : 'tyto kurty mají členové v létě zdarma') : (hod === 1 ? 'za hodinu, členská cena' : 'za ' + hod + NB + tvar + ', členská cena');
          if (pp && clL !== 0) za += ', bez svícení';
        } else {
          za = hod === 1 ? 'za hodinu, cena pro veřejnost' : 'za ' + hod + NB + tvar + ', cena pro veřejnost';
        }
        if (clL !== null) {
          uspora = m.cena.cena * hod - clL;
          usporaText = (st.clen ? 'Ušetříte ' : 'Členové ušetří ') + kc(uspora) + (hod === 1 ? ' za hodinu' : '') + (pp ? ' na pronájmu kurtu' : '');
        }
        pozn = c.poznamkaLeto;
      }
      q('[data-k-co]').innerHTML = typo(co);
      q('[data-k-kdyText]').innerHTML = typo(kdyT);
      q('[data-k-radky]').innerHTML = radky;
      q('[data-k-cena]').innerHTML = vysl === null ? 'podle ceníku' : (vysl === 0 ? 'zdarma' : kc(vysl).replace(NB + 'Kč', NB + '<small>Kč</small>'));
      q('[data-k-za]').textContent = za;
      var u = q('[data-k-uspora]');
      u.hidden = !(uspora > 0);
      u.textContent = usporaText;
      q('[data-k-pozn]').innerHTML = typo(pozn);
    }

    form.addEventListener('change', function (e) {
      var t = e.target;
      if (t.name === 'sezona') { st.sezona = t.value; kresliVolby(); }
      if (t.name === 'hala') { st.hala = t.value; kresliVolby(); }
      if (t.name === 'misto') { st.misto = t.value; st.priplatek = false; kresliVolby(); }
      if (t.name === 'pasmo') st.pasmo = +t.value;
      if (t.name === 'forma') st.forma = t.value;
      if (t.name === 'priplatek') st.priplatek = t.checked;
      spocti();
    });
    hodiny.addEventListener('input', spocti);
    clenTl.addEventListener('click', function () {
      st.clen = clenTl.getAttribute('aria-pressed') !== 'true';
      clenTl.setAttribute('aria-pressed', String(st.clen));
      spocti();
    });

    kresliVolby();
    spocti();
    box.hidden = false;
    var nahrada = d.querySelector('[data-kalkulacka-nahrada]');
    if (nahrada) nahrada.hidden = true;
    if (CLTK.init) CLTK.init(box);   // krokovač z app.js
  }

  function start() {
    var box = d.querySelector('[data-kalkulacka]');
    if (!box) return;
    var data = CLTK.data ? CLTK.data('ceniky-kurtu') : null;
    if (!data) return;
    try { kalkulacka(box, data); } catch (e) { if (window.console) console.error(e); }
  }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();
})();
