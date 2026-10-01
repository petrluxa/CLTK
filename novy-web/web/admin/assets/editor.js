/* Jednoduchý textový editor pro bloky stránek a popisy akcí.
 *
 * Každé <textarea data-editor> (vyrábí ho pole_editor() v admin/inc/ui.php)
 * se nahradí editační plochou s lištou: odstavec / nadpis, tučné, kurzíva,
 * seznamy, citace, odkaz, zrušení formátu a přepnutí na HTML.
 * Před odesláním se HTML vrátí do textového pole; na serveru ho vždy
 * propere html_ocistit() – sem se tedy nebezpečný kód nedostane.
 * Bez JavaScriptu zůstane obyčejné textové pole. Bez knihoven. */
(function () {
  'use strict';

  var IKONY = {
    ul: '<svg viewBox="0 0 24 24"><path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/></svg>',
    ol: '<svg viewBox="0 0 24 24"><path d="M10 6h10M10 12h10M10 18h10M4 5l1.5-1v5M3.5 14.5c.4-.9 2.5-.9 2.5.4 0 1.1-2.5 1.6-2.5 3.1H6"/></svg>',
    citace: '<svg viewBox="0 0 24 24"><path d="M7 7h4v4c0 3-1.5 5-4 6M15 7h4v4c0 3-1.5 5-4 6"/></svg>',
    odkaz: '<svg viewBox="0 0 24 24"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>',
    bezodkazu: '<svg viewBox="0 0 24 24"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7M4 4l16 16"/></svg>',
    smazat: '<svg viewBox="0 0 24 24"><path d="M6 18h7M10 6h8M14 6l-4 12M4 4l16 16"/></svg>',
    html: '<svg viewBox="0 0 24 24"><path d="M8 7l-5 5 5 5M16 7l5 5-5 5"/></svg>'
  };

  function tlacitko(akce, obsah, popis) {
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'ed-btn';
    b.dataset.akce = akce;
    b.innerHTML = obsah;
    b.title = popis;
    b.setAttribute('aria-label', popis);
    return b;
  }

  function oddel() {
    var s = document.createElement('span');
    s.className = 'ed-oddel';
    s.setAttribute('aria-hidden', 'true');
    return s;
  }

  function vytvor(pole) {
    var obal = document.createElement('div');
    obal.className = 'ed-obal';

    var lista = document.createElement('div');
    lista.className = 'ed-lista';
    lista.setAttribute('role', 'toolbar');
    lista.setAttribute('aria-label', 'Formátování textu');

    var blok = document.createElement('select');
    blok.className = 'ed-blok';
    blok.setAttribute('aria-label', 'Druh odstavce');
    [['p', 'Odstavec'], ['h2', 'Nadpis'], ['h3', 'Podnadpis']].forEach(function (o) {
      var opt = document.createElement('option');
      opt.value = o[0];
      opt.textContent = o[1];
      blok.appendChild(opt);
    });
    blok.style.width = 'auto';
    blok.style.minHeight = '38px';
    blok.style.padding = '4px 34px 4px 10px';
    blok.style.fontSize = '14px';
    blok.style.border = '0';
    blok.style.background = 'transparent';
    lista.appendChild(blok);
    lista.appendChild(oddel());
    lista.appendChild(tlacitko('bold', '<b>B</b>', 'Tučně'));
    lista.appendChild(tlacitko('italic', '<em>I</em>', 'Kurzíva'));
    lista.appendChild(oddel());
    lista.appendChild(tlacitko('insertUnorderedList', IKONY.ul, 'Odrážky'));
    lista.appendChild(tlacitko('insertOrderedList', IKONY.ol, 'Číslovaný seznam'));
    lista.appendChild(tlacitko('citace', IKONY.citace, 'Citace'));
    lista.appendChild(oddel());
    lista.appendChild(tlacitko('odkaz', IKONY.odkaz, 'Vložit odkaz'));
    lista.appendChild(tlacitko('unlink', IKONY.bezodkazu, 'Zrušit odkaz'));
    lista.appendChild(tlacitko('removeFormat', IKONY.smazat, 'Zrušit formátování'));
    lista.appendChild(oddel());
    lista.appendChild(tlacitko('html', IKONY.html, 'Upravit jako HTML'));

    var plocha = document.createElement('div');
    plocha.className = 'ed-plocha';
    plocha.contentEditable = 'true';
    plocha.setAttribute('role', 'textbox');
    plocha.setAttribute('aria-multiline', 'true');
    var popisek = pole.id ? document.querySelector('label[for="' + pole.id + '"]') : null;
    if (popisek) {
      if (!popisek.id) popisek.id = pole.id + '-popisek';
      plocha.setAttribute('aria-labelledby', popisek.id);
      popisek.addEventListener('click', function (e) { e.preventDefault(); plocha.focus(); });
    }
    plocha.dataset.navod = 'Začněte psát…';
    plocha.innerHTML = pole.value;

    var zdroj = document.createElement('textarea');
    zdroj.className = 'ed-zdroj';
    zdroj.setAttribute('aria-label', 'HTML zdroj textu');

    pole.hidden = true;
    pole.removeAttribute('required');
    obal.appendChild(lista);
    obal.appendChild(plocha);
    obal.appendChild(zdroj);
    pole.parentNode.insertBefore(obal, pole.nextSibling);

    try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}

    function prenes() {
      pole.value = obal.classList.contains('je-zdroj') ? zdroj.value : plocha.innerHTML;
    }

    lista.addEventListener('mousedown', function (e) {
      if (e.target.closest('.ed-btn')) e.preventDefault();      // výběr textu zůstane
    });
    lista.addEventListener('click', function (e) {
      var b = e.target.closest('.ed-btn');
      if (!b) return;
      var akce = b.dataset.akce;
      if (akce === 'html') {
        if (obal.classList.contains('je-zdroj')) {
          plocha.innerHTML = zdroj.value;
          obal.classList.remove('je-zdroj');
          b.classList.remove('je-aktivni');
        } else {
          zdroj.value = plocha.innerHTML;
          obal.classList.add('je-zdroj');
          b.classList.add('je-aktivni');
        }
        prenes();
        return;
      }
      plocha.focus();
      if (akce === 'odkaz') {
        var adresa = window.prompt('Kam má odkaz vést? (https://…, e-mail nebo stránka webu, např. clenstvi.php)', 'https://');
        if (!adresa || adresa === 'https://') return;
        adresa = adresa.trim();
        if (/^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i.test(adresa)) adresa = 'mailto:' + adresa;
        else if (!/^(https?:\/\/|mailto:|tel:|#|[a-z0-9\-_\/]+\.php)/i.test(adresa)) adresa = 'https://' + adresa;
        if (window.getSelection && String(window.getSelection()) === '') {
          document.execCommand('insertHTML', false, '<a href="' + adresa.replace(/"/g, '&quot;') + '">' + adresa.replace(/</g, '&lt;') + '</a>');
        } else {
          document.execCommand('createLink', false, adresa);
        }
      } else if (akce === 'citace') {
        document.execCommand('formatBlock', false, 'blockquote');
      } else {
        document.execCommand(akce, false, null);
      }
      stav();
      prenes();
    });
    blok.addEventListener('change', function () {
      plocha.focus();
      document.execCommand('formatBlock', false, blok.value);
      prenes();
    });

    function stav() {
      lista.querySelectorAll('.ed-btn').forEach(function (b) {
        var a = b.dataset.akce;
        if (['bold', 'italic', 'insertUnorderedList', 'insertOrderedList'].indexOf(a) === -1) return;
        try { b.classList.toggle('je-aktivni', document.queryCommandState(a)); } catch (e) {}
      });
    }
    plocha.addEventListener('keyup', stav);
    plocha.addEventListener('mouseup', stav);
    plocha.addEventListener('input', prenes);
    zdroj.addEventListener('input', prenes);

    /* vložený text ze schránky jen jako prostý text – Word a weby nesou styly */
    plocha.addEventListener('paste', function (e) {
      if (!e.clipboardData) return;
      e.preventDefault();
      var text = e.clipboardData.getData('text/plain');
      var html = text.split(/\n{2,}/).map(function (odst) {
        return '<p>' + odst.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/\n/g, '<br>') + '</p>';
      }).join('');
      document.execCommand('insertHTML', false, html);
      prenes();
    });

    var form = pole.closest('form');
    if (form) form.addEventListener('submit', prenes);
  }

  document.querySelectorAll('textarea[data-editor]').forEach(vytvor);
})();
