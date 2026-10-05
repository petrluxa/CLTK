<?php
/* JEDNORÁZOVÁ migrace 5. 10. 2026 – aktuality z klubu dostanou datum (na kartě úvodu nad nadpisem).

   Co udělá (jen tabulka cltk_aktuality, SQLite i MySQL, opakované spuštění nic nezmění):
     1. přidá sloupec cltk_aktuality.datum (DATE NULL), jen když chybí,
     2. aktualitám bez data doplní den, kdy byly založené (created_at) – správce ho pak může opravit.
   Spouští se PŘED nahráním nového kódu (starý kód sloupec nepotřebuje, nový ano).

   Spuštění:
     lokálně      CLTK_DB_FILE=web/data/x.sqlite ./_php/php.exe web/sql/migrace/2026-10-05-aktuality-datum.php [--nanecisto]
     na serveru   soubor zkopírovat do kořene webu (sql/ je zvenku zavřené) a otevřít
                  …/2026-10-05-aktuality-datum.php?token=<token>   (&nanecisto=1 = jen ukázat, co by se změnilo)
                  Po doběhnutí soubor z kořene webu smazat.
   Token: repozitář je veřejný, proto je tu jen jeho otisk SHA-256. */
const MIGRACE_TOKEN_SHA256 = '7d7dba2f2baeaa4f565673b6d82b03147f8331690c2dfc215cad58d445aad8ca';

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

echo 'Migrace 2026-10-05-aktuality-datum · databáze: ', db_je_mysql() ? 'mysql' : 'sqlite', $nanecisto ? ' · NANEČISTO (nic se nezapíše)' : '', "\n\n";

/* 1) sloupec */
if (db_je_mysql()) {
    $ma = (int)val('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
        ['cltk_aktuality', 'datum']) > 0;
} else {
    $ma = (bool)preg_match('/[(,]\s*[`"\[]?datum[`"\]]?\s/i', (string)val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", ['cltk_aktuality']));
}
if ($ma) {
    echo "1) Sloupec cltk_aktuality.datum už existuje – přeskočeno.\n";
} elseif ($nanecisto) {
    echo "1) Sloupec cltk_aktuality.datum by se přidal (DATE NULL).\n";
} else {
    q('ALTER TABLE cltk_aktuality ADD COLUMN datum DATE NULL');
    echo "1) Sloupec cltk_aktuality.datum přidán (DATE NULL).\n";
}

/* 2) datum založení tam, kde datum chybí */
$radky = rows($ma ? 'SELECT id, nadpis, created_at, datum FROM cltk_aktuality ORDER BY poradi, id'
                  : 'SELECT id, nadpis, created_at FROM cltk_aktuality ORDER BY poradi, id');
$zmen = 0;
foreach ($radky as $r) {
    if ($ma && !empty($r['datum'])) { echo "2) „{$r['nadpis']}“: datum už má (" . cz_date_dlouze((string)$r['datum']) . ") – beze změny\n"; continue; }
    $den = normalizuj_datum(substr((string)($r['created_at'] ?? ''), 0, 10));
    if (!is_string($den)) { echo "2) „{$r['nadpis']}“: neznámé datum založení – zůstane bez data\n"; continue; }
    if (!$nanecisto) q('UPDATE cltk_aktuality SET datum = ? WHERE id = ? AND datum IS NULL', [$den, (int)$r['id']]);
    echo '2) „' . $r['nadpis'] . '“: datum ' . ($nanecisto ? 'by bylo ' : '') . cz_date_dlouze($den) . " (den založení)\n";
    $zmen++;
}
if (!$radky) echo "2) Žádné aktuality.\n";
echo "\n", $nanecisto ? "HOTOVO (nanečisto – nic se nezapsalo)\n" : "HOTOVO\n";
