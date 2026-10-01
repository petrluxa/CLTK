/* ==========================================================================
   I. ČLTK Praha · Vodorovný pás s posouváním [data-pas]
   Použití: výsledky hráčů na úvodu (víc než tři – šipky, tažení myší,
   scroll-snap, na mobilu po jednom). Hodí se i pro jiné karty na podstránkách.

   <div class="pas" data-pas>
     <div class="pas__hlava">… <div class="pas__sipky">
       <button class="pas__sipka" type="button" data-pas-zpet aria-controls="X" aria-label="Předchozí">…</button>
       <button class="pas__sipka" type="button" data-pas-dal aria-controls="X" aria-label="Další">…</button>
     </div></div>
     <ul class="pas__drazka" id="X" tabindex="0" role="region" aria-label="…">…</ul>
     <div class="pas__stav" aria-hidden="true"><span></span></div>
   </div>
   Bez JavaScriptu pás roluje sám (dotyk, trackpad, klávesnice).
   ========================================================================== */
(function () {
  'use strict';
  var d = document;

  function pohyb() {
    return window.CLTK && typeof window.CLTK.pohybPovolen === 'function' ? window.CLTK.pohybPovolen() : true;
  }

  function init(pas) {
    var drazka = pas.querySelector('.pas__drazka');
    var zpet = pas.querySelector('[data-pas-zpet]');
    var dal = pas.querySelector('[data-pas-dal]');
    var sipky = pas.querySelector('.pas__sipky');
    var stav = pas.querySelector('.pas__stav span');
    if (!drazka) return;

    function krok() {
      var prvni = drazka.children[0];
      if (!prvni) return drazka.clientWidth;
      var mezera = parseFloat(window.getComputedStyle(drazka).columnGap) || 0;
      return prvni.getBoundingClientRect().width + mezera;
    }
    function obnov() {
      var max = drazka.scrollWidth - drazka.clientWidth;
      var preteka = max > 4;
      if (sipky) sipky.hidden = !preteka;
      if (zpet) zpet.disabled = drazka.scrollLeft <= 4;
      if (dal) dal.disabled = drazka.scrollLeft >= max - 4;
      if (stav) {
        var podil = drazka.scrollWidth ? drazka.clientWidth / drazka.scrollWidth : 1;
        stav.parentNode.hidden = !preteka;
        stav.style.width = (podil * 100) + '%';
        stav.style.transform = 'translateX(' + (max > 0 ? (drazka.scrollLeft / max) * ((1 / podil) - 1) * 100 : 0) + '%)';
      }
    }
    function posun(smer) {
      drazka.scrollBy({ left: smer * krok(), behavior: pohyb() ? 'smooth' : 'auto' });
    }
    if (zpet) zpet.addEventListener('click', function () { posun(-1); });
    if (dal) dal.addEventListener('click', function () { posun(1); });
    var cekam = false;
    drazka.addEventListener('scroll', function () {
      if (cekam) return;
      cekam = true;
      window.requestAnimationFrame(function () { cekam = false; obnov(); });
    }, { passive: true });
    window.addEventListener('resize', obnov);

    /* tažení myší (dotyk a trackpad rolují samy) */
    var tah = null, odtazeno = false;
    drazka.addEventListener('pointerdown', function (e) {
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      tah = { x: e.clientX, left: drazka.scrollLeft };
      odtazeno = false;
    });
    drazka.addEventListener('pointermove', function (e) {
      if (!tah) return;
      var dx = e.clientX - tah.x;
      if (!odtazeno && Math.abs(dx) > 6) {
        odtazeno = true;
        drazka.style.scrollSnapType = 'none';
        drazka.style.cursor = 'grabbing';
        try { drazka.setPointerCapture(e.pointerId); } catch (er) { /* nic */ }
      }
      if (odtazeno) drazka.scrollLeft = tah.left - dx;
    });
    function konec(e) {
      if (!tah) return;
      tah = null;
      if (odtazeno) {
        drazka.style.cursor = '';
        /* dojet na nejbližší kartu */
        var k = krok(), cil = Math.round(drazka.scrollLeft / k) * k;
        drazka.style.scrollSnapType = '';
        drazka.scrollTo({ left: cil, behavior: pohyb() ? 'smooth' : 'auto' });
        try { drazka.releasePointerCapture(e.pointerId); } catch (er) { /* nic */ }
      }
    }
    drazka.addEventListener('pointerup', konec);
    drazka.addEventListener('pointercancel', konec);
    /* klik po tažení nesmí otevřít odkaz */
    drazka.addEventListener('click', function (e) {
      if (odtazeno) { e.preventDefault(); e.stopPropagation(); odtazeno = false; }
    }, true);
    drazka.addEventListener('dragstart', function (e) { e.preventDefault(); });

    obnov();
    window.setTimeout(obnov, 400);   // po načtení písem
  }

  function start() { [].forEach.call(d.querySelectorAll('[data-pas]'), init); }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();
})();
