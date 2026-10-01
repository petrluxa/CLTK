/* ==========================================================================
   I. ČLTK Praha · Historie – kronika po epochách (historie.php)
   Razítko v boční liště ukazuje dobové jméno klubu podle kapitoly, kterou
   právě čtete; v seznamu kapitol se zvýrazní aktuální kapitola.
   Kostra:  [data-kronika] > [data-razitko-jmeno] [data-razitko-roky]
            a[data-kapitola-odkaz] → section[data-era][data-era-nazev][data-era-roky]
   Bez JavaScriptu razítko ukazuje první epochu a každá kapitola má vlastní
   hlavičku – obsah je vidět celý. Pod 1100 px je boční lišta skrytá.
   ========================================================================== */
(function () {
  'use strict';
  var d = document;
  function vse(sel, root) { return [].slice.call((root || d).querySelectorAll(sel)); }

  function kronika(root) {
    var kapitoly = vse('[data-era]', root);
    if (!kapitoly.length) return;
    var jmeno = root.querySelector('[data-razitko-jmeno]');
    var roky = root.querySelector('[data-razitko-roky]');
    var razitko = jmeno ? jmeno.closest('.kronika__razitko') : null;
    var odkazy = vse('[data-kapitola-odkaz]', root);
    var aktualni = null;

    function nastav(k) {
      if (!k || k === aktualni) return;
      var prvni = aktualni === null;
      aktualni = k;
      if (jmeno) jmeno.textContent = k.getAttribute('data-era-nazev') || '';
      if (roky) roky.textContent = k.getAttribute('data-era-roky') || '';
      if (razitko && !prvni) {
        razitko.classList.remove('je-zmena');
        void razitko.offsetWidth;          // znovu spustit animaci otisku
        razitko.classList.add('je-zmena');
      }
      odkazy.forEach(function (a) {
        if (a.getAttribute('href') === '#' + k.id) a.setAttribute('aria-current', 'true');
        else a.removeAttribute('aria-current');
      });
    }

    /* aktuální kapitola = poslední, jejíž začátek už přejel třetinu okna */
    function vyber() {
      var hranice = window.innerHeight * 0.35, vybrana = kapitoly[0];
      kapitoly.forEach(function (k) { if (k.getBoundingClientRect().top <= hranice) vybrana = k; });
      nastav(vybrana);
    }
    var cekam = false;
    window.addEventListener('scroll', function () {
      if (cekam) return;
      cekam = true;
      window.requestAnimationFrame(function () { cekam = false; vyber(); });
    }, { passive: true });
    window.addEventListener('resize', vyber);
    vyber();
  }

  function start() { vse('[data-kronika]').forEach(kronika); }
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();
})();
