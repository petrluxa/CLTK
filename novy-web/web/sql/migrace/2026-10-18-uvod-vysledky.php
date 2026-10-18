<?php
/* JEDNORÁZOVÁ migrace 18. 10. 2026 – úvodní stránka: pod úvodem je nově tmavomodrý pás „Naši hráči na turnajích“
   se 4 nejnovějšími výsledky (návrh C, Petr vybral 18. 10. 2026) a sekce 3 na něj navazuje dalšími zápasy.
   Nadpis sekce 3 (cltk_bloky index/vysledky, sloupec stitek) se proto mění z výchozího „Aktuální výsledky
   našich hráčů“ na „Další výsledky našich hráčů“ (stejně jako výchozí text v administraci, seed a data.json).
   Jen tabulka cltk_bloky, SQLite i MySQL, opakovaný běh nic nezmění.
   Přepisuje VŽDY jen hodnotu, která je pořád PŘESNĚ původní (vlastní nadpis z administrace zůstane). Na konci
   vypíše HOTOVO.

   Spuštění:
     lokálně      CLTK_DB_FILE=web/data/x.sqlite ./_php/php.exe web/sql/migrace/2026-10-18-uvod-vysledky.php [--nanecisto]
     na serveru   soubor zkopírovat do kořene webu (sql/ je zvenku zavřené) a otevřít
                  …/2026-10-18-uvod-vysledky.php?token=<token>   (&nanecisto=1 = jen ukázat, co by se změnilo)
                  Po doběhnutí soubor z kořene webu smazat.
   Token: repozitář je veřejný, proto je tu jen jeho otisk SHA-256.
   Výchozí obsah (sql/seed/60-bloky.php) má stejný nový stav. */
const MIGRACE_TOKEN_SHA256 = '873107d2ee584588d4aee780c97b75e565d1a3a47aee81eb3d3b5053966eaf96';

/** cltk_bloky – [stránka, klíč, sloupec, původní, nový]. */
const UV18_BLOKY = [
    ['index', 'vysledky', 'stitek', 'Aktuální výsledky našich hráčů', 'Další výsledky našich hráčů'],
];

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

echo 'Migrace 2026-10-18-uvod-vysledky · databáze: ', db_je_mysql() ? 'mysql' : 'sqlite', $nanecisto ? ' · NANEČISTO (nic se nezapíše)' : '', "\n\n";
$zmen = 0;
$pozor = 0;

db()->beginTransaction();
try {
    foreach (UV18_BLOKY as [$stranka, $klic, $sloupec, $stary, $novy]) {
        $b = row('SELECT id, ' . $sloupec . ' AS h FROM cltk_bloky WHERE stranka = ? AND klic = ?', [$stranka, $klic]);
        if (!$b) { echo "blok {$stranka}/{$klic}: v databázi není – přeskočeno (web použije výchozí text „{$novy}“)\n"; continue; }
        if ((string)$b['h'] === $novy) { echo "blok {$stranka}/{$klic} ({$sloupec}): už „{$novy}“ – beze změny\n"; continue; }
        if ((string)$b['h'] !== $stary) { echo "blok {$stranka}/{$klic} ({$sloupec}): POZOR – vlastní text z administrace („" . (string)$b['h'] . "“), nechávám\n"; $pozor++; continue; }
        if (!$nanecisto) db_update('cltk_bloky', (int)$b['id'], [$sloupec => $novy, 'updated_at' => ted()]);
        echo "blok {$stranka}/{$klic} ({$sloupec}): „{$stary}“ → „{$novy}“\n";
        $zmen++;
    }
    if ($nanecisto) db()->rollBack(); else db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    echo "\nCHYBA: ", $e->getMessage(), "\nNic se nezapsalo.\n";
    exit(1);
}

echo "\n", $nanecisto ? "Změn by bylo: {$zmen}" : ($zmen ? "Změn: {$zmen}" : 'Změn: 0 – databáze už je v novém stavu'), $pozor ? " · POZOR: {$pozor}" : '', ".\n";
echo $nanecisto ? "HOTOVO (nanečisto – nic se nezapsalo)\n" : "HOTOVO\n";
