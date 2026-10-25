<?php
/* JEDNORÁZOVÁ migrace 25. 10. 2026 – výřez fotky aktuality (Petr: „ať si manažer při psaní sám vybere výřez fotky“).

   Co udělá (jen tabulka cltk_aktuality, SQLite i MySQL, opakované spuštění nic nezmění):
     přidá sloupec cltk_aktuality.vyrez (VARCHAR(20) NOT NULL DEFAULT ''), jen když chybí.
     '' = dosavadní automatika (fotka jiného tvaru celá, jinak vyplní rámeček podle ohniska), 'cela' = celá fotka
     na paspartě, číslo 1–3 = přiblížení výřezu (poloha zůstává ve sloupci fokus). Stávající aktuality se nezmění.
   Spouští se PŘED nahráním nového kódu (starý kód sloupec nepotřebuje, nová administrace do něj zapisuje).

   Spuštění:
     lokálně      CLTK_DB_FILE=web/data/x.sqlite ./_php/php.exe web/sql/migrace/2026-10-25-aktuality-vyrez.php [--nanecisto]
     na serveru   soubor zkopírovat do kořene webu (sql/ je zvenku zavřené) a otevřít
                  …/2026-10-25-aktuality-vyrez.php?token=<token>   (&nanecisto=1 = jen ukázat, co by se změnilo)
                  Po doběhnutí soubor z kořene webu smazat.
   Token: repozitář je veřejný, proto je tu jen jeho otisk SHA-256. */
const MIGRACE_TOKEN_SHA256 = 'eac154ecb0fe577374ae62be0b5edd7e783ea6daa9dd04c4236b195d07b147c7';

if (PHP_SAPI !== 'cli') {
    $token = $_GET['token'] ?? '';
    if (!is_string($token) || strlen($token) !== 32 || !hash_equals(MIGRACE_TOKEN_SHA256, hash('sha256', $token))) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        exit("403 – bez platného tokenu se migrace nespustí.\n");
    }
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
}
$jadro = '';
for ($d = __DIR__, $i = 0; $i <= 4; $i++, $d = dirname($d)) {
    if (is_file($d . '/inc/functions.php') && is_file($d . '/inc/db.php')) { $jadro = $d . '/inc/functions.php'; break; }
}
if ($jadro === '') { echo "CHYBA: nenašel jsem inc/functions.php (soubor patří do kořene webu nebo do sql/migrace/).\n"; exit(1); }
require_once $jadro;
$nanecisto = PHP_SAPI === 'cli' ? in_array('--nanecisto', (array)($argv ?? []), true) : (($_GET['nanecisto'] ?? '') === '1');

echo 'Migrace 2026-10-25-aktuality-vyrez · databáze: ', db_je_mysql() ? 'mysql' : 'sqlite', $nanecisto ? ' · NANEČISTO (nic se nezapíše)' : '', "\n\n";

if (db_je_mysql()) {
    $ma = (int)val('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
        ['cltk_aktuality', 'vyrez']) > 0;
} else {
    $ma = (bool)preg_match('/[(,]\s*[`"\[]?vyrez[`"\]]?\s/i', (string)val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", ['cltk_aktuality']));
}
if ($ma) {
    echo "Sloupec cltk_aktuality.vyrez už existuje – přeskočeno.\n";
} elseif ($nanecisto) {
    echo "Sloupec cltk_aktuality.vyrez by se přidal (VARCHAR(20) NOT NULL DEFAULT '').\n";
} else {
    q("ALTER TABLE cltk_aktuality ADD COLUMN vyrez VARCHAR(20) NOT NULL DEFAULT ''");
    echo "Sloupec cltk_aktuality.vyrez přidán (VARCHAR(20) NOT NULL DEFAULT '').\n";
}
$pocet = (int)val('SELECT COUNT(*) FROM cltk_aktuality');
echo "Aktualit v tabulce: {$pocet} – zůstávají beze změny (výřez '' = dosavadní automatika).\n";
echo "\n", $nanecisto ? "HOTOVO (nanečisto – nic se nezapsalo)\n" : "HOTOVO\n";
