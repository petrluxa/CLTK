<?php
/* Příjem výsledků ze služby Pointeks – veřejná adresa pointeks/feed (s lomítkem na konci i bez).
   web/.htaccess (a lokálně nastroje/router.php) ji BEZ přesměrování přepíše na tento soubor.
   Jeden POST = jeden výsledek jednoho hráče klubu (JSON podepsaný HMAC-SHA256 klíčem POINTEKS_SECRET).
   Celá logika a kódy odpovědí: pointeks_prijmi() v inc/pointeks.php.

   Záměrně NEvkládá inc/rezim.php – v režimu přípravy by návštěvník dostal 503 a výsledky by se
   ztratily; příjem musí běžet i tehdy. Nezakládá relaci ani cookies a vrací jen stavový kód. */
require_once __DIR__ . '/inc/functions.php';
pointeks_prijmi();
