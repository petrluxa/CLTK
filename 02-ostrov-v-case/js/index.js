/* I. ČLTK Praha – koncept B „Ostrov v čase“ · úvodní stránka
   Drobnosti nad sdíleným app.js: titulek dialogu u vitríny, aktivní položka navigace podle sekce. */
(function () {
  'use strict';
  var d = document;

  // titulek dialogu z data-dialog-nadpis (běží v zachytávací fázi, tedy před otevřením v app.js)
  d.addEventListener('click', function (e) {
    var o = e.target.closest && e.target.closest('[data-dialog-nadpis]');
    if (!o) return;
    var t = d.querySelector('[data-dialog-titul]');
    if (t) t.textContent = o.getAttribute('data-dialog-nadpis');
  }, true);

  // aktivní položka hlavní navigace podle sekce v zorném poli
  var odkazy = [].slice.call(d.querySelectorAll('.navigace a[href^="#"]'));
  if (!odkazy.length || !('IntersectionObserver' in window)) return;
  var mapa = {};
  odkazy.forEach(function (a) {
    var s = d.getElementById(a.getAttribute('href').slice(1));
    if (s) mapa[s.id] = a;
  });
  var io = new IntersectionObserver(function (zaznamy) {
    zaznamy.forEach(function (z) {
      var a = mapa[z.target.id];
      if (!a) return;
      if (z.isIntersecting) {
        odkazy.forEach(function (x) { x.removeAttribute('aria-current'); });
        a.setAttribute('aria-current', 'true');
      } else a.removeAttribute('aria-current');
    });
  }, { rootMargin: '-45% 0px -50% 0px' });
  Object.keys(mapa).forEach(function (id) { io.observe(d.getElementById(id)); });
})();
