/* ==========================================================================
   Varianta 4 · Úvodní galerie „Znak se otevírá“
   Snímky se střídají po data-interval ms. Další fotka se otevře maskou
   ve tvaru klubového štítu (CSS mask-size) a po okraji běží zlatá linka.
   – omezený pohyb (přepínač v patičce nebo nastavení systému): žádné
     automatické střídání, přepnutí bez animace, tlačítko pauzy skryté
   – pozastaví se při najetí myší, při zaměření klávesnicí a mimo obrazovku
   ========================================================================== */
(function () {
  'use strict';
  var d = document;
  var DELKA = 2400; // ms, shodné s --prechod v CSS

  function pohybPovolen() {
    if (window.CLTK && typeof window.CLTK.pohybPovolen === 'function') return window.CLTK.pohybPovolen();
    return !(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function init(g) {
    var snimky = [].slice.call(g.querySelectorAll('.galerie__snimek'));
    var tlacitka = [].slice.call(g.querySelectorAll('[data-snimek]'));
    var pauza = g.querySelector('[data-galerie-pauza]');
    var pauzaText = pauza && pauza.querySelector('.galerie__pauza-text');
    var popisek = g.querySelector('.galerie__popisek-text');
    var kredit = g.querySelector('.galerie__kredit');
    var interval = parseInt(g.getAttribute('data-interval'), 10) || 7000;
    if (snimky.length < 2) return;

    var aktivni = 0, casovac = null, vPrechodu = false, bezi = false;
    var zastaveno = false, najeto = false, zamereno = false, naObrazovce = true;

    g.style.setProperty('--interval', interval + 'ms');
    g.style.setProperty('--prechod', DELKA + 'ms');

    function restartOdpoctu() {
      g.classList.remove('je-odpocet');
      void g.offsetWidth; // nové spuštění CSS animace odpočtu
      if (bezi) g.classList.add('je-odpocet');
    }

    function oznac(i) {
      tlacitka.forEach(function (b, j) {
        if (j === i) b.setAttribute('aria-current', 'true'); else b.removeAttribute('aria-current');
      });
      var s = snimky[i];
      if (popisek) popisek.innerHTML = s.getAttribute('data-popisek') || '';
      if (kredit) kredit.textContent = s.getAttribute('data-kredit') || '';
      restartOdpoctu();
    }

    function nacti(s) {
      var img = s.querySelector('img');
      if (img && img.getAttribute('loading') === 'lazy') img.setAttribute('loading', 'eager');
    }

    function prejdi(i) {
      if (i === aktivni || vPrechodu) return;
      var stary = snimky[aktivni], novy = snimky[i];
      nacti(novy);
      aktivni = i;
      oznac(i);
      nacti(snimky[(i + 1) % snimky.length]);

      if (!pohybPovolen()) {
        stary.classList.remove('je-aktivni');
        novy.classList.add('je-aktivni');
        return;
      }
      vPrechodu = true;
      novy.classList.add('je-prichozi');
      g.classList.remove('je-prechod');
      void g.offsetWidth;
      g.classList.add('je-prechod');
      window.requestAnimationFrame(function () {
        window.requestAnimationFrame(function () { novy.classList.add('je-otevirani'); });
      });
      window.setTimeout(function () {
        novy.classList.add('je-aktivni');
        novy.classList.remove('je-prichozi', 'je-otevirani');
        stary.classList.remove('je-aktivni');
        g.classList.remove('je-prechod');
        vPrechodu = false;
      }, DELKA + 80);
    }

    function naplanuj() {
      window.clearTimeout(casovac);
      if (!bezi) return;
      casovac = window.setTimeout(function () {
        prejdi((aktivni + 1) % snimky.length);
        naplanuj();
      }, interval);
    }

    function nastavBeh() {
      var mohl = pohybPovolen();
      var mel = bezi;
      bezi = mohl && !zastaveno && !najeto && !zamereno && naObrazovce && !d.hidden;
      g.classList.toggle('je-pozastaveno', !bezi);
      if (pauza) {
        pauza.hidden = !mohl;
        pauza.setAttribute('aria-pressed', String(zastaveno));
        if (pauzaText) pauzaText.textContent = zastaveno ? 'Spustit' : 'Zastavit';
      }
      if (bezi && !mel) { restartOdpoctu(); naplanuj(); }
      if (!bezi) { window.clearTimeout(casovac); g.classList.remove('je-odpocet'); }
    }

    tlacitka.forEach(function (b) {
      b.addEventListener('click', function () {
        prejdi(parseInt(b.getAttribute('data-snimek'), 10));
        if (bezi) naplanuj();
      });
    });
    if (pauza) pauza.addEventListener('click', function () { zastaveno = !zastaveno; nastavBeh(); });

    g.addEventListener('mouseenter', function () { najeto = true; nastavBeh(); });
    g.addEventListener('mouseleave', function () { najeto = false; nastavBeh(); });
    g.addEventListener('focusin', function () { zamereno = true; nastavBeh(); });
    g.addEventListener('focusout', function (e) { if (!g.contains(e.relatedTarget)) { zamereno = false; nastavBeh(); } });
    d.addEventListener('visibilitychange', nastavBeh);
    // přepínač „Omezit pohyb“ v patičce
    d.addEventListener('click', function (e) {
      if (e.target.closest && e.target.closest('[data-prepinac="pohyb"]')) window.setTimeout(nastavBeh, 30);
    });
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (zaznamy) {
        naObrazovce = zaznamy[0].isIntersecting;
        nastavBeh();
      }, { threshold: 0.25 }).observe(g);
    }

    oznac(0);
    nacti(snimky[1]);
    nastavBeh();
  }

  function start() { [].forEach.call(d.querySelectorAll('[data-galerie]'), init); }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();
})();
