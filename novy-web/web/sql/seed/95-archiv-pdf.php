<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Archiv ze starého webu – stejná logika jako migrace sql/migrace/2026-10-02-dokumenty-archiv.php
   (data vedle ní), aby nový seed měl hned nový stav:
     – Revue a newslettery: sady 55-revue a 50-historie berou obsah z podkladů průzkumu
       (cltk-navrhy/podklady/data), kde PDF vedou na files.cltk.cz → PDF nahraná na webu
       (uploads/revue/pdf/, uploads/newslettery/, stažená a zmenšená nastroje/archiv-pdf/),
     – prameny kroniky a CTC bez odkazů na staré články,
     – jubilejní Revue 1893–2023, archiv klubových turnajů (PDF), rozvrhy Tenisové školy,
       texty bloků stránky rozvrhů a bloky stránky Dokumenty.
   Přepíše jen hodnoty, které se pořád přesně rovnají staré adrese, a vloží jen to, co chybí –
   po importu sql/data.json (už převedená data) nic nemění. Soubor, který v uploads/ chybí,
   ohlásí a jeho řádek přeskočí. */

$migrace = __DIR__ . '/../migrace/2026-10-02-dokumenty-archiv.php';
if (!is_file($migrace)) {
    seed_log('  ! archiv: ' . basename($migrace) . ' chybí – odkazy na files.cltk.cz zůstaly.');
    return;
}
defined('ARCHIV_MIGRACE_KNIHOVNA') || define('ARCHIV_MIGRACE_KNIHOVNA', true);
require_once $migrace;

$radky = [];
$ok = archiv_migrace_spust(dirname($migrace) . '/' . MIGRACE_ARCHIV_DATA, false,
    static function (string $s) use (&$radky): void { $radky[] = $s; }, true);
// do protokolu jen kroky, které něco změnily (opakované spuštění = „beze změny“)
$zmeny = array_values(array_filter($radky, static fn(string $s): bool =>
    $s !== '' && !str_contains($s, 'beze změny') && !preg_match('~: 0 |přeskočeno – |žádný odkaz|už v (kiosku|modulu)|0 upraveno, 0 nových|nevkládá se~u', $s)));
seed_log('Archiv ze starého webu: ' . (!$ok ? 'SELHAL – ' . implode(' ', $radky) : ($zmeny ? 'převedeno' : 'beze změny (už převedeno)')));
if ($ok) foreach ($zmeny as $s) seed_log('  ' . $s);
