/* Moduly obsahu administrace I. ČLTK Praha – chování navíc k admin.js a editor.js.
 *
 * 1) Obrázek do textu: do lišty editoru (editor.js) přidá tlačítko, které nahraje
 *    fotku na pozadí (POST na adresu z data-obrazky-url, s CSRF tokenem formuláře)
 *    a vloží ji na místo kurzoru. Server obrázek přes GD znovu uloží a při uložení
 *    textu ho html_ocistit() propustí jen z vlastní složky uploads/.
 * 2) Přidat řádek: v tabulce s poli (obsah čísla Revue) naklonuje prázdný řádek.
 *
 * Skript se načítá před editor.js (je výš ve stránce), proto čeká na DOMContentLoaded –
 * to přijde až po všech skriptech s defer, takže lišta editoru už existuje.
 * Bez JavaScriptu zůstane obyčejné textové pole a tři prázdné řádky navíc. */
(function () {
  'use strict';

  var IKONA_OBRAZEK = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4.5" width="18" height="15" rx="2"/>'
    + '<circle cx="8.5" cy="9.5" r="1.6"/><path d="M4 17l4.5-4.2a2 2 0 0 1 2.7 0l3 2.8m0 0l1.8-1.6a2 2 0 0 1 2.7 0L20 15.5"/></svg>';

  function escAttr(s) {
    return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  /* ---------- 1) obrázek do textu ---------- */
  function pripravObrazky(obal) {
    var ed = obal.querySelector('.ed-obal');
    if (!ed || ed.querySelector('[data-akce="obrazek"]')) return;
    var lista = ed.querySelector('.ed-lista');
    var plocha = ed.querySelector('.ed-plocha');
    var zdroj = ed.querySelector('.ed-zdroj');
    var form = obal.closest('form');
    var tokenPole = form ? form.querySelector('input[name="_token"]') : null;
    var adresa = obal.getAttribute('data-obrazky-url');
    if (!lista || !plocha || !adresa) return;

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ed-btn';
    btn.dataset.akce = 'obrazek';
    btn.title = 'Vložit obrázek do textu';
    btn.setAttribute('aria-label', 'Vložit obrázek do textu');
    btn.innerHTML = IKONA_OBRAZEK;

    var htmlBtn = lista.querySelector('[data-akce="html"]');
    var predHtml = htmlBtn ? htmlBtn.previousElementSibling : null;   // oddělovač před tlačítkem HTML
    lista.insertBefore(btn, predHtml || htmlBtn || null);

    var hlaska = document.createElement('span');
    hlaska.className = 'ed-hlaska';
    hlaska.setAttribute('role', 'status');
    hlaska.setAttribute('aria-live', 'polite');
    lista.appendChild(hlaska);

    var vstup = document.createElement('input');          // bez name – s formulářem se neodešle
    vstup.type = 'file';
    vstup.accept = 'image/jpeg,image/png,image/webp,image/gif';
    vstup.hidden = true;
    vstup.setAttribute('aria-hidden', 'true');
    vstup.tabIndex = -1;
    obal.appendChild(vstup);

    /* místo kurzoru v textu – po výběru souboru se do něj obrázek vloží */
    var rozsah = null;
    function zapamatujVyber() {
      var s = window.getSelection ? window.getSelection() : null;
      if (s && s.rangeCount && plocha.contains(s.getRangeAt(0).commonAncestorContainer)) {
        rozsah = s.getRangeAt(0).cloneRange();
      }
    }
    document.addEventListener('selectionchange', zapamatujVyber);
    btn.addEventListener('mousedown', function (e) { e.preventDefault(); });   // výběr v textu zůstane
    btn.addEventListener('click', function () {
      zapamatujVyber();
      vstup.click();
    });

    function stav(text, druh) {
      hlaska.textContent = text;
      hlaska.className = 'ed-hlaska' + (druh ? ' je-' + druh : '');
    }

    function vloz(url, popis) {
      var html = '<p><img src="' + escAttr(url) + '" alt="' + escAttr(popis) + '"></p>';
      if (ed.classList.contains('je-zdroj') && zdroj) {
        var a = zdroj.selectionStart || zdroj.value.length;
        zdroj.value = zdroj.value.slice(0, a) + html + zdroj.value.slice(zdroj.selectionEnd || a);
        zdroj.dispatchEvent(new Event('input', { bubbles: true }));
        return;
      }
      plocha.focus();
      var s = window.getSelection();
      if (rozsah && s && plocha.contains(rozsah.startContainer)) {        // místo kurzoru ještě v textu je
        s.removeAllRanges();
        s.addRange(rozsah);
      } else if (s) {                                        // kurzor nebyl v textu → na konec
        var r = document.createRange();
        r.selectNodeContents(plocha);
        r.collapse(false);
        s.removeAllRanges();
        s.addRange(r);
      }
      var ok = false;
      try { ok = document.execCommand('insertHTML', false, html); } catch (e) { ok = false; }
      if (!ok) plocha.insertAdjacentHTML('beforeend', html);
      plocha.dispatchEvent(new Event('input', { bubbles: true }));   // editor.js přenese HTML do pole
    }

    function nahraj(soubor) {
      if (soubor.size > 20 * 1048576) { stav('Obrázek je větší než 20 MB – zmenšete ho prosím.', 'chyba'); return; }
      stav('Nahrávám obrázek…');
      btn.disabled = true;
      var data = new FormData();
      data.append('action', 'obrazek_do_textu');
      data.append('_token', tokenPole ? tokenPole.value : '');
      data.append('obrazek', soubor);
      fetch(adresa, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) {
          return r.json().catch(function () { throw new Error('Server neodpověděl – obnovte prosím stránku a zkuste to znovu.'); });
        })
        .then(function (v) {
          if (!v || !v.ok) throw new Error((v && v.chyba) || 'Nahrání se nezdařilo.');
          var popis = window.prompt('Krátký popis obrázku (pro nevidomé a vyhledávače). Můžete nechat prázdné.', '') || '';
          vloz(v.url, popis.trim().slice(0, 200));
          stav('Obrázek je vložený – nezapomeňte text uložit.', 'hotovo');
        })
        .catch(function (err) { stav(err.message, 'chyba'); })
        .then(function () { btn.disabled = false; });
    }

    vstup.addEventListener('change', function () {
      if (vstup.files && vstup.files[0]) nahraj(vstup.files[0]);
      vstup.value = '';
    });
  }

  /* ---------- 1b) HTML zdroj editoru vyčistit před návratem do editoru ----------
     editor.js po přepnutí z „Upravit jako HTML“ zpět vloží zdroj přes innerHTML – vložený
     <img src=x onerror=…> by se tím v administraci spustil (server ho sice při uložení
     zahodí, ale to už je pozdě). Zdroj se proto nejdřív rozebere v neaktivním dokumentu
     (DOMParser – nic se nenačte ani nespustí) a nechají se jen značky, které propustí
     i html_ocistit() na serveru, bez atributů (kromě href u odkazu a src/alt u obrázku). */
  var POVOLENE = ' P BR STRONG B EM I U S H2 H3 H4 UL OL LI BLOCKQUOTE FIGURE FIGCAPTION A IMG HR ';
  var ZAHODIT = ' SCRIPT STYLE IFRAME OBJECT EMBED FORM INPUT BUTTON SELECT TEXTAREA SVG MATH NOSCRIPT TEMPLATE HEAD TITLE META LINK BASE FRAME FRAMESET APPLET VIDEO AUDIO CANVAS ';
  function nebezpecnaAdresa(v) {
    return /^(javascript|data|vbscript|file):/i.test(String(v).replace(/[\x00-\x20\x7f]+/g, ''));
  }
  function vycistiHtml(html) {
    if (!window.DOMParser) return html;
    var doc = new DOMParser().parseFromString('<!DOCTYPE html><html><body>' + html + '</body></html>', 'text/html');
    (function projdi(rodic) {
      Array.prototype.slice.call(rodic.childNodes).forEach(function (n) {
        if (n.nodeType === 3) return;                                   // text zůstává
        if (n.nodeType !== 1) { rodic.removeChild(n); return; }         // komentáře pryč
        var z = ' ' + String(n.nodeName).toUpperCase() + ' ';
        if (ZAHODIT.indexOf(z) !== -1) { rodic.removeChild(n); return; }
        // obrázek jen z vlastní složky uploads/ (jako html_adresa_obrazku na serveru) – cizí ani nenačítat
        if (z === ' IMG ' && !/(^|\/)uploads\/[a-z0-9\-_\/]+\.(jpe?g|png|webp|gif)$/i.test(n.getAttribute('src') || '')) { rodic.removeChild(n); return; }
        projdi(n);
        Array.prototype.slice.call(n.attributes).forEach(function (a) {
          var j = a.name.toLowerCase();
          var nechat = (z === ' A ' && j === 'href' && !nebezpecnaAdresa(a.value))
                    || (z === ' IMG ' && (j === 'alt' || (j === 'src' && !nebezpecnaAdresa(a.value))));
          if (!nechat) n.removeAttribute(a.name);
        });
        if (POVOLENE.indexOf(z) === -1) {                               // nepovolená značka: obsah zůstane
          while (n.firstChild) rodic.insertBefore(n.firstChild, n);
          rodic.removeChild(n);
        }
      });
    })(doc.body);
    return doc.body.innerHTML;
  }
  function chranZdroj(ed) {
    var zdroj = ed.querySelector('.ed-zdroj');
    var htmlBtn = ed.querySelector('.ed-btn[data-akce="html"]');
    if (!zdroj || !htmlBtn) return;
    // posluchač přímo na tlačítku proběhne dřív než posluchač editoru na liště (probublání)
    htmlBtn.addEventListener('click', function () {
      if (ed.classList.contains('je-zdroj')) zdroj.value = vycistiHtml(zdroj.value);
    });
  }

  /* ---------- 2) přidat prázdný řádek do tabulky s poli ---------- */
  function pripravRadky(btn) {
    var tbody = document.getElementById(btn.getAttribute('data-pridat-radek'));
    if (!tbody) return;
    btn.hidden = false;
    btn.addEventListener('click', function () {
      var vzor = tbody.querySelector('tr.radek-novy:last-of-type') || tbody.querySelector('tr:last-child');
      if (!vzor) return;
      var novy = vzor.cloneNode(true);
      novy.querySelectorAll('input, textarea').forEach(function (p) {
        if (p.type === 'checkbox') p.checked = false; else p.value = '';
        if (p.id) p.removeAttribute('id');
      });
      novy.classList.add('radek-novy');
      tbody.appendChild(novy);
      var prvni = novy.querySelector('input, textarea');
      if (prvni) prvni.focus();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ed-obal').forEach(chranZdroj);
    document.querySelectorAll('[data-obrazky-url]').forEach(pripravObrazky);
    document.querySelectorAll('[data-pridat-radek]').forEach(pripravRadky);
  });
})();
