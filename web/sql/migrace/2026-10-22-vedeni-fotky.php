<?php
/* JEDNORÁZOVÁ migrace 22. 10. 2026 – portréty Výkonného výboru (Petr poslal fotky všech devíti členů).
   Do cltk_vedeni.foto doplní cestu k fotce (uploads/vedeni/<jméno>.jpg + .webp vedle – soubory se nahrávají
   zvlášť, stejné, jaké by vyrobila administrace) u řádků výboru i kanceláře se STEJNÝM jménem.
   Přepisuje jen prázdné pole foto; vlastní fotku z administrace nechá (POZOR). Jen tabulka cltk_vedeni,
   SQLite i MySQL, opakovaný běh nic nezmění, na konci HOTOVO.

   Spuštění:
     lokálně      CLTK_DB_FILE=web/data/x.sqlite ./_php/php.exe web/sql/migrace/2026-10-22-vedeni-fotky.php [--nanecisto]
     na serveru   soubor zkopírovat do kořene webu a otevřít …/2026-10-22-vedeni-fotky.php?token=<token>
                  (&nanecisto=1 = jen ukázat, co by se změnilo); po doběhnutí soubor z kořene webu smazat.
   Token: repozitář je veřejný, proto je tu jen jeho otisk SHA-256. */
const MIGRACE_TOKEN_SHA256 = '6f04c0ae474ae28fdbb8530046c86d374b15b44e075873afeec8028b343bf069';

/** jméno v modulu Vedení (přesně) => soubor v uploads/ */
const VF22_FOTKY = [
    'Ing. Petr Šimůnek'     => 'vedeni/petr-simunek.jpg',
    'JUDr. Tomáš Havel'     => 'vedeni/tomas-havel.jpg',
    'JUDr. Zdeněk Krampera' => 'vedeni/zdenek-krampera.jpg',
    'Mgr. Václav Kučera'    => 'vedeni/vaclav-kucera.jpg',
    'Ing. Dušan Palcr'      => 'vedeni/dusan-palcr.jpg',
    'Mgr. Jan Pecha, Ph.D.' => 'vedeni/jan-pecha.jpg',
    'Vladislav Šavrda'      => 'vedeni/vladislav-savrda.jpg',
    'Mgr. Martin Šustr'     => 'vedeni/martin-sustr.jpg',
    'Petr Vaníček'          => 'vedeni/petr-vanicek.jpg',
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
if ($jadro === '') { echo "CHYBA: nenašel jsem inc/functions.php.\n"; exit(1); }
require_once $jadro;
$nanecisto = PHP_SAPI === 'cli' ? in_array('--nanecisto', (array)($argv ?? []), true) : (($_GET['nanecisto'] ?? '') === '1');

echo 'Migrace 2026-10-22-vedeni-fotky · databáze: ', db_je_mysql() ? 'mysql' : 'sqlite', $nanecisto ? ' · NANEČISTO (nic se nezapíše)' : '', "\n\n";
$zmen = 0;
$pozor = 0;
db()->beginTransaction();
try {
    foreach (VF22_FOTKY as $jmeno => $foto) {
        if (!is_file(UPLOAD_DIR . '/' . $foto)) { echo "POZOR: soubor uploads/{$foto} na serveru není – {$jmeno} přeskočen\n"; $pozor++; continue; }
        $radky = rows('SELECT id, skupina, foto FROM cltk_vedeni WHERE jmeno = ? ORDER BY skupina, id', [$jmeno]);
        if (!$radky) { echo "POZOR: {$jmeno} v modulu Vedení není – přeskočeno\n"; $pozor++; continue; }
        foreach ($radky as $r) {
            $mist = $jmeno . ' (' . $r['skupina'] . ')';
            if ((string)$r['foto'] === $foto) { echo "{$mist}: fotku už má – beze změny\n"; continue; }
            if (trim((string)$r['foto']) !== '') { echo "{$mist}: POZOR – má jinou fotku z administrace („{$r['foto']}“), nechávám\n"; $pozor++; continue; }
            if (!$nanecisto) db_update('cltk_vedeni', (int)$r['id'], ['foto' => $foto]);
            echo "{$mist}: foto → {$foto}\n";
            $zmen++;
        }
    }
    if ($nanecisto) db()->rollBack(); else db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    echo "\nCHYBA: ", $e->getMessage(), "\nNic se nezapsalo.\n";
    exit(1);
}
echo "\n", $nanecisto ? "Změn by bylo: {$zmen}" : "Změn: {$zmen}", $pozor ? " · POZOR: {$pozor}" : '', ".\n";
echo $nanecisto ? "HOTOVO (nanečisto – nic se nezapsalo)\n" : "HOTOVO\n";
