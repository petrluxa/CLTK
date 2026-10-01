<?php
/* Stránka 404 – sem posílá .htaccess (a lokálně nastroje/router.php)
   každou neexistující adresu. Text je v administraci: Stránky → 404 → uvod.
   require_once: stránka se může vložit i z jiné, která jádro už načetla. */
require_once __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
stranka_nenalezena();
