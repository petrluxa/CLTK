<?php
/* Export obsahu z lokální databáze pro přenos na server – jen z příkazové řádky.

     ./_php/php.exe web/sql/export-dat.php          → web/sql/data.json
     ./_php/php.exe web/sql/export-dat.php --sql    → navíc web/sql/data.sql (MySQL INSERT)

   data.json čte instalace.php: na čerstvém serveru naplní prázdné tabulky
   přesně tím obsahem, který byl připravený a odsouhlasený lokálně (vkládá
   s parametry, takže nezáleží na ovladači ani na zvláštních znacích).
   data.sql je náhradní cesta pro ruční import (phpMyAdmin) – INSERT IGNORE
   jen do tabulek cltk_, nic nemaže.

   NEPŘENÁŠÍ SE: účty správců, pokusy o přihlášení, návštěvnost, přihlášky
   k akcím a do klubu (osobní údaje) a tajné klíče (system_klic, náhledové
   heslo, režim přípravy). Soubory z uploads/ se nahrávají zvlášť.

   Před exportem se ověří, že se texty vejdou do sloupců na MySQL
   (SQLite délku VARCHAR nehlídá, MySQL ve striktním režimu ano). */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/seed-pomocne.php';

$sSql = in_array('--sql', $argv, true);
echo 'Databáze: ' . (DB_DRIVER === 'sqlite' ? DB_FILE : DB_DRIVER . ' ' . DB_NAME) . "\n";

$schema = (string)file_get_contents(__DIR__ . '/schema.sql');
preg_match_all('/CREATE TABLE (\w+) \((.*?)\n\);/s', $schema, $mTabulky, PREG_SET_ORDER);
$delky = [];
foreach ($mTabulky as [, $tab, $telo]) {
    preg_match_all('/^\s*(\w+)\s+VARCHAR\((\d+)\)/m', $telo, $mSloupce, PREG_SET_ORDER);
    foreach ($mSloupce as [, $sloupec, $n]) $delky[$tab][$sloupec] = (int)$n;
}

$vystup = ['verze' => 1, 'vytvoreno' => date('Y-m-d H:i:s'), 'tabulky' => []];
$sql = [
    '-- I. ČLTK Praha – obsah webu (export ' . date('j. n. Y H:i') . ')',
    '-- Jen INSERT IGNORE do tabulek cltk_. Nic se nemaže ani nepřepisuje.',
    '-- Účty, přihlášky, návštěvnost a tajné klíče tu nejsou.',
    'SET NAMES utf8mb4;',
    '',
];
$problemy = 0;
$celkem = 0;

/** Hodnota do MySQL (výchozí režim se zpětným lomítkem jako escapovacím znakem). */
$mysql = static function ($v): string {
    if ($v === null) return 'NULL';
    if (is_int($v) || is_float($v)) return (string)$v;
    return "'" . strtr((string)$v, ["\\" => "\\\\", "\0" => "\\0", "\n" => "\\n", "\r" => "\\r",
                                    "'" => "\\'", '"' => '\\"', "\x1a" => "\\Z"]) . "'";
};

foreach (db_tabulky() as $tab) {
    if (in_array($tab, SEED_NEPRENASET, true)) continue;
    $klic = $tab === 'cltk_settings' ? 'skey' : 'id';
    $radky = rows('SELECT * FROM ' . cltk_tabulka($tab) . ' ORDER BY ' . $klic);
    if ($tab === 'cltk_settings') {
        $radky = array_values(array_filter($radky, fn($r) => !in_array($r['skey'], SEED_NASTAVENI_NEPRENASET, true)));
    }
    // délky textů proti VARCHAR na MySQL
    foreach ($radky as $r) {
        foreach (($delky[$tab] ?? []) as $sloupec => $max) {
            $d = mb_strlen((string)($r[$sloupec] ?? ''));
            if ($d > $max) {
                $problemy++;
                fwrite(STDERR, "  ! $tab.$sloupec (" . $klic . ' ' . $r[$klic] . "): $d znaků, sloupec pojme $max – na MySQL by zápis selhal.\n");
            }
        }
    }
    $vystup['tabulky'][$tab] = $radky;
    $celkem += count($radky);
    echo sprintf("  %-26s %5d\n", $tab, count($radky));

    if ($sSql && $radky) {
        $sloupce = array_keys($radky[0]);
        foreach (array_chunk($radky, 40) as $davka) {
            $hodnoty = array_map(fn($r) => '(' . implode(', ', array_map($mysql, array_values($r))) . ')', $davka);
            $sql[] = 'INSERT IGNORE INTO ' . $tab . ' (' . implode(', ', $sloupce) . ") VALUES\n" . implode(",\n", $hodnoty) . ';';
        }
        $sql[] = '';
    }
}

if ($problemy > 0) {
    fwrite(STDERR, "Export zastaven: $problemy " . sklonuj($problemy, 'hodnota se nevejde', 'hodnoty se nevejdou', 'hodnot se nevejde') . " do sloupců. Zkraťte je, nebo upravte schéma.\n");
    exit(1);
}

$json = json_encode($vystup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
file_put_contents(__DIR__ . '/data.json', $json . "\n");
echo "Zapsáno: web/sql/data.json ($celkem řádků, " . round(strlen((string)$json) / 1024) . " kB)\n";
if ($sSql) {
    file_put_contents(__DIR__ . '/data.sql', implode("\n", $sql) . "\n");
    echo "Zapsáno: web/sql/data.sql\n";
}
echo "HOTOVO\n";
