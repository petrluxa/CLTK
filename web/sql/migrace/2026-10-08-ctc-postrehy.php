<?php
/* JEDNORÁZOVÁ migrace 8. 10. 2026 – připomínky klubu ke stránce Centenary Tennis Clubs (ctc.php) a k rodokmenu
   stoletých klubů (ctc.php i klub.php). Jen tabulky cltk_ctc a cltk_bloky, SQLite i MySQL, opakovaný běh nic nezmění.

     1. Členství v CTC: „Hra v klubech CTC“ bez „zdarma“ (záleží na každém klubu); „Klub ve vedení CTC“ –
        Ing. Petr Šimůnek člen řídicího výboru od 2005, od 2011 viceprezident,
     2. Mezinárodní utkání – úvodní odstavec doplněn o soutěže CTC,
     3. Utkání posledních let: vypuštěny akce International Clubs (2025, 2026 – klub jen poskytuje kurty),
        přidána CTC Senior Competition 35+/45+ Winners Group (30.–31. 8. 2025, TC Padova, Cumberland LTC),
     4. Kluby, které hrály na Štvanici: doplněny kluby z klubem pořádané CTC Senior Competition,
     5. Rodokmen stoletých: vypuštěn The Hurlingham Club (není členem CTC).
   Přepisuje / maže VŽDY jen řádek, jehož hodnoty jsou pořád PŘESNĚ původní (úpravy z administrace zůstanou);
   nové řádky vloží, jen když tam řádek se stejným typem a názvem ještě není. Pořadí (poradi) se po vypuštění
   řádku posune, aby v seznamu nevznikla mezera. Na konci vypíše HOTOVO.

   Spuštění:
     lokálně      CLTK_DB_FILE=web/data/x.sqlite ./_php/php.exe web/sql/migrace/2026-10-08-ctc-postrehy.php [--nanecisto]
     na serveru   soubor zkopírovat do kořene webu (sql/ je zvenku zavřené) a otevřít
                  …/2026-10-08-ctc-postrehy.php?token=<token>   (&nanecisto=1 = jen ukázat, co by se změnilo)
                  Po doběhnutí soubor z kořene webu smazat.
   Token: repozitář je veřejný, proto je tu jen jeho otisk SHA-256.
   Výchozí obsah (sql/seed/57-vedeni-ctc.php, 58-klub.php) má stejný nový stav i stejná čísla řádků. */
const MIGRACE_TOKEN_SHA256 = 'f09706ff2128f25087246cd4b9ecf1c128e1bfe598b34696c36dd7f7d14bc259';

/** cltk_ctc – úpravy textu: [typ, název, původní text, nový text]. */
const CTC8_TEXTY = [
    ['fakt', 'Hra v klubech CTC',
     'Členové klubu mohou po doporučení generálního manažera hrát zdarma v klubech sdružených v Centenary Tennis Clubs.',
     'Členové klubu mohou po doporučení generálního manažera hrát v klubech sdružených v Centenary Tennis Clubs.'],
    ['fakt', 'Klub ve vedení CTC',
     'Ing. Petr Šimůnek zastupuje klub v řídícím výboru CTC od roku 2005.',
     'Ing. Petr Šimůnek je členem řídicího výboru CTC od roku 2005, od roku 2011 ve funkci viceprezidenta.'],
];
/** cltk_ctc – vypustit: [typ, název, rok, text] (smaže se jen řádek s přesně těmito hodnotami). */
const CTC8_VYPUSTIT = [
    ['utkani', 'International Clubs', '2025', '12.–13. 6. 2025, hráči z osmi států'],
    ['utkani', 'The International Lawn Tennis Club', '2026', '11.–12. 6. 2026'],
    ['rodokmen', 'The Hurlingham Club', '1869', 'otevřen 1869'],
];
/** cltk_ctc – nové řádky (vkládají se v tomto pořadí na konec svého seznamu). */
const CTC8_NOVE = [
    ['typ' => 'utkani', 'nazev' => 'CTC Senior Competition 35+/45+ · Winners Group', 'rok' => '2025', 'misto' => 'TC Padova, Cumberland LTC',
     'text' => '30.–31. 8. 2025', 'zdroj' => 'Revue 02/2025'],
    ['typ' => 'klub', 'nazev' => 'Tennis Club Parioli', 'misto' => 'Řím'],
    ['typ' => 'klub', 'nazev' => 'Tennis Club de Genève', 'misto' => 'Ženeva'],
    ['typ' => 'klub', 'nazev' => 'Edgbaston Priory Club', 'misto' => 'Birmingham'],
    ['typ' => 'klub', 'nazev' => 'Real Club de Polo de Barcelona', 'misto' => 'Barcelona'],
    ['typ' => 'klub', 'nazev' => 'Wiener Parkclub', 'misto' => 'Vídeň'],
    ['typ' => 'klub', 'nazev' => 'Kungliga LTK', 'misto' => 'Stockholm'],
    ['typ' => 'klub', 'nazev' => 'Real Sociedad de Tenis de La Magdalena', 'misto' => 'Santander'],
    ['typ' => 'klub', 'nazev' => 'TC Padova', 'misto' => 'Padova'],
    ['typ' => 'klub', 'nazev' => 'SALK', 'misto' => 'Stockholm'],
    ['typ' => 'klub', 'nazev' => 'Cumberland LTC', 'misto' => 'Londýn'],
];
const CTC8_KLUB_TEXT = 'CTC Senior Competition na Štvanici';
const CTC8_KLUB_ZDROJ = 'I. ČLTK Praha, seznam klubů CTC Senior Competition (10/2026)';
/** cltk_bloky – [stránka, klíč, sloupec, původní, nový]. */
const CTC8_BLOKY = [
    ['ctc', 'utkani', 'perex', 'Kluby sdružené v Centenary Tennis Clubs se navzájem zvou k přátelským utkáním.',
     'Kluby sdružené v Centenary Tennis Clubs se navzájem zvou k přátelským utkáním a účastní se soutěží pořádaných CTC.'],
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

echo 'Migrace 2026-10-08-ctc-postrehy · databáze: ', db_je_mysql() ? 'mysql' : 'sqlite', $nanecisto ? ' · NANEČISTO (nic se nezapíše)' : '', "\n\n";
$zmen = 0;
$pozor = 0;
$zkrat = static fn(string $s): string => mb_strlen($s) > 70 ? mb_substr($s, 0, 70) . '…' : $s;

db()->beginTransaction();
try {
    /* 1) texty v cltk_ctc */
    foreach (CTC8_TEXTY as [$typ, $nazev, $stary, $novy]) {
        $r = row('SELECT id, text FROM cltk_ctc WHERE typ = ? AND nazev = ?', [$typ, $nazev]);
        if (!$r) { echo "1) „{$nazev}“: v databázi není – přeskočeno\n"; continue; }
        if ((string)$r['text'] === $novy) { echo "1) „{$nazev}“: už nový text – beze změny\n"; continue; }
        if ((string)$r['text'] !== $stary) { echo "1) POZOR „{$nazev}“: text je změněný v administraci – nechávám („" . $zkrat((string)$r['text']) . "“)\n"; $pozor++; continue; }
        if (!$nanecisto) db_update('cltk_ctc', (int)$r['id'], ['text' => $novy]);
        echo "1) „{$nazev}“: „" . $zkrat($stary) . "“ → „" . $zkrat($novy) . "“\n";
        $zmen++;
    }

    /* 2) blok ctc/utkani */
    foreach (CTC8_BLOKY as [$stranka, $klic, $sloupec, $stary, $novy]) {
        $b = row('SELECT id, ' . $sloupec . ' AS h FROM cltk_bloky WHERE stranka = ? AND klic = ?', [$stranka, $klic]);
        if (!$b) { echo "2) blok {$stranka}/{$klic}: v databázi není – přeskočeno\n"; continue; }
        if ((string)$b['h'] === $novy) { echo "2) blok {$stranka}/{$klic} ({$sloupec}): už nový – beze změny\n"; continue; }
        if ((string)$b['h'] !== $stary) { echo "2) POZOR blok {$stranka}/{$klic} ({$sloupec}): změněný v administraci – nechávám\n"; $pozor++; continue; }
        if (!$nanecisto) db_update('cltk_bloky', (int)$b['id'], [$sloupec => $novy, 'updated_at' => ted()]);
        echo "2) blok {$stranka}/{$klic} ({$sloupec}): „" . $zkrat($novy) . "“\n";
        $zmen++;
    }

    /* 3) vypustit řádky (jen s přesně původními hodnotami) a posunout pořadí za nimi */
    foreach (CTC8_VYPUSTIT as [$typ, $nazev, $rok, $text]) {
        $r = row('SELECT id, rok, text, poradi FROM cltk_ctc WHERE typ = ? AND nazev = ?', [$typ, $nazev]);
        if (!$r) { echo "3) {$typ} „{$nazev}“: už v databázi není – beze změny\n"; continue; }
        if ((string)$r['rok'] !== $rok || (string)$r['text'] !== $text) { echo "3) POZOR {$typ} „{$nazev}“: řádek je změněný v administraci – nemažu\n"; $pozor++; continue; }
        if (!$nanecisto) {
            q('DELETE FROM cltk_ctc WHERE id = ?', [(int)$r['id']]);
            q('UPDATE cltk_ctc SET poradi = poradi - 1 WHERE typ = ? AND poradi > ?', [$typ, (int)$r['poradi']]);
        }
        echo "3) {$typ} „{$nazev}“ ({$rok}): vypuštěn" . ($nanecisto ? ' by byl' : '') . "\n";
        $zmen++;
    }

    /* 4) nové řádky – na konec svého seznamu */
    foreach (CTC8_NOVE as $novy) {
        $novy += ['rok' => '', 'misto' => '', 'text' => CTC8_KLUB_TEXT, 'odkaz' => '', 'zdroj' => CTC8_KLUB_ZDROJ, 'zvyraznit' => 0, 'visible' => 1];
        if (row('SELECT id FROM cltk_ctc WHERE typ = ? AND nazev = ?', [$novy['typ'], $novy['nazev']])) {
            echo "4) {$novy['typ']} „{$novy['nazev']}“: už v databázi je – beze změny\n";
            continue;
        }
        $novy['poradi'] = (int)val('SELECT COALESCE(MAX(poradi), -1) FROM cltk_ctc WHERE typ = ?', [$novy['typ']]) + 1;
        if (!$nanecisto) db_insert('cltk_ctc', $novy);
        echo "4) {$novy['typ']} „{$novy['nazev']}“" . ($novy['misto'] !== '' ? " ({$novy['misto']})" : '') . ': ' . ($nanecisto ? 'by se přidal' : 'přidán') . "\n";
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
