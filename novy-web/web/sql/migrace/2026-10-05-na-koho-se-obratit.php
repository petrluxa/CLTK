<?php
/* JEDNORÁZOVÁ migrace 5. 10. 2026 – skupina „Na koho se obrátit“ v administraci Texty a údaje.
   Přání klienta: karty Recepce a Kancelář a lidé ze stránky Kontakt v jedné skupině, kterou si klub
   upravuje sám; na kartě Recepce jen „Obsazenost kurtů“ se správným odkazem na Roger Online.

   Co udělá (jen tabulka cltk_settings, SQLite i MySQL, opakované spuštění nic nezmění):
     1. recepce_popis, recepce_telefon, recepce_email, kancelar_jmeno, kancelar_popis, kancelar_telefon,
        kancelar_email (skupina kontakt) a obsazenost_url (skupina odkazy) přesune do skupiny „lide“
        s pořadím 9–16 (obsazenost hned pod recepcí) – jen když je klíč pořád v původní skupině,
     2. obsazenost_url: stará adresa onlinehq (courtst.php?klub=181) → https://www.rogeronline.cz/v2/index.php?klub=181
        – jen když je hodnota pořád PŘESNĚ ta stará (jinou hodnotu jen ohlásí),
     3. popisek a nápověda obsazenost_url, nápověda kancelar_jmeno – jen když jsou pořád původní z výchozího obsahu.
   Lidé (cltk_vedeni) se nemění – administrace je jen ukazuje v nové skupině. Datový soubor není potřeba.
   Na konci vypíše HOTOVO.

   Spuštění:
     lokálně      CLTK_DB_FILE=web/data/x.sqlite ./_php/php.exe web/sql/migrace/2026-10-05-na-koho-se-obratit.php [--nanecisto]
     na serveru   soubor zkopírovat do kořene webu (sql/ je zvenku zavřené) a otevřít
                  …/2026-10-05-na-koho-se-obratit.php?token=<token>   (&nanecisto=1 = jen ukázat, co by se změnilo)
                  Po doběhnutí soubor z kořene webu smazat.
   Funguje z kořene webu i ze sql/migrace/ (jádro inc/functions.php najde o patra výš).
   Výchozí obsah (sql/seed/10-nastaveni.php) už má nový stav – nová instalace migraci nepotřebuje. */

/* Token pro spuštění přes prohlížeč (32 znaků). Repozitář je veřejný (GitHub), proto je tu jen
   jeho otisk SHA-256 – token sám zná orchestrátor. Porovnává se hash_equals(). */
const LIDE_MIGRACE_TOKEN_SHA256 = '1d246161e631b4d6ddbe517dd4a5970041947f9ca984a26be4b35fddf7962f3f';

/* stará adresa složená ze dvou kusů – hledání staré domény ve web/ (grep) pak najde jen skutečné odkazy */
const LIDE_OBSAZENOST_STARA = 'https://onlinehq' . '.cz/r/courtst.php?klub=181';
const LIDE_OBSAZENOST_NOVA  = 'https://www.rogeronline.cz/v2/index.php?klub=181';

/** Klíče do skupiny „lide“: klíč => [původní skupina, nové pořadí]. */
const LIDE_PRESUN = [
    'recepce_popis'    => ['kontakt', 9],
    'recepce_telefon'  => ['kontakt', 10],
    'recepce_email'    => ['kontakt', 11],
    'obsazenost_url'   => ['odkazy', 12],
    'kancelar_jmeno'   => ['kontakt', 13],
    'kancelar_popis'   => ['kontakt', 14],
    'kancelar_telefon' => ['kontakt', 15],
    'kancelar_email'   => ['kontakt', 16],
];

/** Popisky a nápovědy: [klíč, sloupec, původní text z výchozího obsahu, nový text]. */
const LIDE_TEXTY = [
    ['obsazenost_url', 'label', 'Obsazenost kurtů', 'Obsazenost kurtů – odkaz'],
    ['obsazenost_url', 'napoveda', '', 'Ukazuje se na kartě Recepce na stránce Kontakt, v horní liště webu a na stránce Ceník kurtů. Otevírá se v novém okně.'],
    ['kancelar_jmeno', 'napoveda', '', 'Tato osoba má na webu vlastní kartu Kancelář, v seznamu lidí níže se už neopakuje.'],
];

if (PHP_SAPI !== 'cli') {
    $token = $_GET['token'] ?? '';
    if (!is_string($token) || strlen($token) !== 32 || !hash_equals(LIDE_MIGRACE_TOKEN_SHA256, hash('sha256', $token))) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        exit("403 – bez platného tokenu se migrace nespustí.\n");
    }
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    @set_time_limit(120);
    ignore_user_abort(true);                 // zavřené okno / výpadek proxy migraci nepřeruší uprostřed
}
$jadro = lide_najdi_jadro(__DIR__);
if ($jadro === '') { echo "CHYBA: nenašel jsem inc/functions.php (soubor patří do kořene webu nebo do sql/migrace/).\n"; exit(1); }
require_once $jadro;
$nanecisto = PHP_SAPI === 'cli' ? in_array('--nanecisto', (array)($argv ?? []), true) : (($_GET['nanecisto'] ?? '') === '1');
$ok = lide_migrace_spust($nanecisto, static function (string $s): void { echo $s, "\n"; });
exit($ok ? 0 : 1);

/** Najde jádro webu: inc/functions.php v této složce nebo o 1–4 patra výš. */
function lide_najdi_jadro(string $odkud): string {
    $d = $odkud;
    for ($i = 0; $i <= 4; $i++) {
        if (is_file($d . '/inc/functions.php') && is_file($d . '/inc/db.php')) return $d . '/inc/functions.php';
        $d = dirname($d);
    }
    return '';
}

/** Celá migrace. $log dostává řádky výpisu. $nanecisto = nic nezapisuje, jen vypíše, co by udělala. */
function lide_migrace_spust(bool $nanecisto, callable $log): bool {
    $log('Migrace 2026-10-05-na-koho-se-obratit · databáze: ' . DB_DRIVER . ($nanecisto ? ' · NANEČISTO (nic se nezapíše)' : ''));
    $log('');

    /* ---------- 0) kontroly: tabulka a nový kód administrace ---------- */
    if (!in_array('cltk_settings', db_tabulky(), true)) {
        $log('CHYBA: v databázi chybí tabulka cltk_settings – je web nainstalovaný? Nic se nezměnilo.');
        return false;
    }
    $admin = WEB_ROOT . '/admin/nastaveni.php';
    if (!is_file($admin) || !str_contains((string)file_get_contents($admin), 'function ns_lide_')) {
        $log('CHYBA: na serveru je starý kód administrace (admin/nastaveni.php bez skupiny „Na koho se obrátit“).');
        $log('Nejdřív nahrajte nové soubory webu (admin/nastaveni.php, kontakt.php …), pak migraci spusťte znovu. Nic se nezměnilo.');
        return false;
    }
    $log('Kontrola: nový kód administrace je nahraný.');

    $pdo = db();
    $vTransakci = false;
    if (!$nanecisto) { $pdo->beginTransaction(); $vTransakci = true; }
    $zmen = 0;
    $budou = $nanecisto ? 'by se přesunul' : 'přesunut';
    try {
        /* ---------- 1) skupina „lide“ ---------- */
        foreach (LIDE_PRESUN as $k => [$puvodni, $poradi]) {
            $r = row('SELECT skey, grp, poradi FROM cltk_settings WHERE skey = ?', [$k]);
            if (!$r) { $log("1) $k: v databázi chybí – přeskočeno (pole se v administraci neukáže, web použije výchozí hodnotu)"); continue; }
            if ((string)$r['grp'] === 'lide') { $log("1) $k: už je ve skupině Na koho se obrátit – beze změny"); continue; }
            if ((string)$r['grp'] !== $puvodni) {
                $log("1) $k: skupina je „" . $r['grp'] . "“ (ne původní „{$puvodni}“) – nechávám, jak je");
                continue;
            }
            if (!$nanecisto) q("UPDATE cltk_settings SET grp = 'lide', poradi = ? WHERE skey = ? AND grp = ?", [$poradi, $k, $puvodni]);
            $zmen++;
            $log("1) $k: $budou ze skupiny „{$puvodni}“ do „lide“ (Na koho se obrátit), pořadí " . (int)$r['poradi'] . " → $poradi");
        }

        /* ---------- 2) odkaz Obsazenost kurtů ---------- */
        $sval = val("SELECT sval FROM cltk_settings WHERE skey = 'obsazenost_url'");
        if ($sval === null || $sval === false) {
            $log('2) obsazenost_url: v databázi chybí – přeskočeno');
        } elseif ((string)$sval === LIDE_OBSAZENOST_STARA) {
            if (!$nanecisto) q("UPDATE cltk_settings SET sval = ? WHERE skey = 'obsazenost_url' AND sval = ?", [LIDE_OBSAZENOST_NOVA, LIDE_OBSAZENOST_STARA]);
            $zmen++;
            $log('2) obsazenost_url: ' . LIDE_OBSAZENOST_STARA . ' → ' . LIDE_OBSAZENOST_NOVA . ($nanecisto ? ' (by se změnil)' : ''));
        } elseif ((string)$sval === LIDE_OBSAZENOST_NOVA) {
            $log('2) obsazenost_url: už vede na Roger Online – beze změny');
        } else {
            $log('2) POZOR obsazenost_url: hodnota není původní („' . (string)$sval . '“) – nechávám, zkontrolujte ji v administraci (Texty a údaje → Na koho se obrátit)');
        }

        /* ---------- 3) popisky a nápovědy ---------- */
        foreach (LIDE_TEXTY as [$k, $sloupec, $stary, $novy]) {
            if (!in_array($sloupec, ['label', 'napoveda'], true)) continue;
            $r = row('SELECT skey, label, napoveda FROM cltk_settings WHERE skey = ?', [$k]);
            if (!$r) continue;
            $co = $sloupec === 'label' ? 'popisek' : 'nápověda';
            if ((string)$r[$sloupec] === $novy) { $log("3) $k – $co: už nový – beze změny"); continue; }
            if ((string)$r[$sloupec] !== $stary) { $log("3) $k – $co: není původní („" . $r[$sloupec] . "“) – nechávám"); continue; }
            if (!$nanecisto) q("UPDATE cltk_settings SET $sloupec = ? WHERE skey = ? AND $sloupec = ?", [$novy, $k, $stary]);
            $zmen++;
            $log("3) $k – $co: „" . $stary . "“ → „" . $novy . '“');
        }

        if ($vTransakci) { $pdo->commit(); $vTransakci = false; }
    } catch (Throwable $e) {
        if ($vTransakci && $pdo->inTransaction()) $pdo->rollBack();
        $log('CHYBA: ' . $e->getMessage());
        $log('Nic se nezměnilo (ROLLBACK).');
        return false;
    }
    if (function_exists('setting_cache')) setting_cache(null, true);

    $log('');
    $log(($nanecisto ? 'Změn by bylo: ' : 'Změn: ') . $zmen . ($zmen === 0 ? ' – databáze už je v novém stavu.' : '.'));
    $log($nanecisto ? 'HOTOVO (nanečisto – nic se nezapsalo)' : 'HOTOVO');
    return true;
}
