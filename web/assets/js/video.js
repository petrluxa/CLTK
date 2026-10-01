/* ==========================================================================
   I. ČLTK Praha · Video bez zvuku ve smyčce [data-video]
   – zdroje mají jen data-src a dostanou src, až video začne hrát (na obrazovce
     a bez omezení pohybu, nebo po kliknutí na Přehrát) – nic se nestahuje
     předem, ani v Safari, které preload="none" nerespektuje
   – bez omezení pohybu se samo spustí, když je z větší části vidět,
     a zastaví se, když odjede pryč
   – s omezeným pohybem (přepínač v patičce / nastavení systému) zůstane
     plakát a tlačítko Přehrát; tlačítko pauzy je vidět vždy (WCAG 2.2.2)
   – bez JavaScriptu má video vlastní ovládání (atribut controls)
   ========================================================================== */
(function () {
  'use strict';
  var d = document;

  function pohyb() {
    return window.CLTK && typeof window.CLTK.pohybPovolen === 'function' ? window.CLTK.pohybPovolen() : true;
  }

  function init(obal) {
    var v = obal.querySelector('video.video__prehravac') || obal.querySelector('video');
    var tl = obal.querySelector('.video__tlacitko');
    var text = tl && tl.querySelector('.video__text');
    if (!v || !tl) return;
    v.removeAttribute('controls');
    v.muted = true;
    v.setAttribute('muted', '');
    v.playsInline = true;
    tl.hidden = false;

    var naObrazovce = false, pozastavilUzivatel = false, spustilUzivatel = false;

    function popis() {
      var hraje = !v.paused && !v.ended;
      obal.classList.toggle('je-prehrava', hraje);
      tl.setAttribute('aria-pressed', String(hraje));
      if (text) text.textContent = hraje ? 'Pozastavit video' : 'Přehrát video';
    }
    var nacteno = false;
    function nactiZdroje() {
      if (nacteno) return;
      nacteno = true;
      [].forEach.call(v.querySelectorAll('source[data-src]'), function (s) { s.src = s.getAttribute('data-src'); s.removeAttribute('data-src'); });
      v.load();
    }
    function prehraj() {
      nactiZdroje();
      var p = v.play();
      if (p && typeof p.catch === 'function') p.catch(function () { popis(); });
    }
    /* má video teď hrát? jen na obrazovce, v otevřené kartě, a buď bez
       omezení pohybu, nebo když ho návštěvník sám spustil */
    function chce() { return !pozastavilUzivatel && (spustilUzivatel || pohyb()); }
    function rozhodni() {
      if (naObrazovce && !d.hidden && chce()) { if (v.paused) prehraj(); }
      else if (!v.paused) v.pause();
    }

    tl.addEventListener('click', function () {
      if (v.paused || v.ended) {
        pozastavilUzivatel = false;
        spustilUzivatel = true;
        prehraj();
      } else {
        pozastavilUzivatel = true;
        spustilUzivatel = false;
        v.pause();
      }
    });
    v.addEventListener('play', popis);
    v.addEventListener('playing', popis);
    v.addEventListener('pause', popis);
    v.addEventListener('ended', popis);

    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (z) {
        naObrazovce = z[0].isIntersecting && z[0].intersectionRatio >= 0.35;
        rozhodni();
      }, { threshold: [0, 0.35, 0.6] }).observe(obal);
    }
    d.addEventListener('visibilitychange', rozhodni);
    d.addEventListener('cltk:pristupnost', function () { if (!pohyb()) spustilUzivatel = false; rozhodni(); });
    popis();
  }

  function start() { [].forEach.call(d.querySelectorAll('[data-video]'), init); }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();
})();
