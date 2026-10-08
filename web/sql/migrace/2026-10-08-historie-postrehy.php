<?php
/* JEDNORÁZOVÁ migrace 8. 10. 2026 – postřehy klienta k historii klubu (stránky Historie a Klub).
   Rozhodnutí klienta:
     A) „Tři vítězové Wimbledonu vyrostli na Štvanici“: perex jen „1954, 1973 a 2023.“ (karty bez
        „V den titulu hrál/a za“ – to dělá nový kód historie.php; údaje hral_za v databázi zůstávají),
     B) Wimbledonská linka: Karolína Muchová 2026 jen „finále s Lindou Noskovou“ (bez výsledku),
     C) kronika: jména klubu 1948–1969 (milník 1949), doba „Spartak a Motorlet (1949–1969)“, dvanáct
        titulů mistra republiky 1956–1968 (milník se z roku 1966 přesouvá na rok 1956 – řadí se mezi 1954
        a 1962, popisek fotky „Mistři ligy 1966“), nové milníky 1978 (Složil, mix na Roland Garros)
        a 2006 (Damm, čtyřhra na US Open) s pramenem a jistotou, Benešová s Melzerem v milníku 2011,
     D) stránka Klub: „Sedm jmen, jeden klub“ (DSO Spartak, Sokol Šverma Jinonice, Spartak Praha
        Motorlet, Motorlet Praha),
     E) Zlatá deska: Grand Slam jako jeden seznam všech, kdo na Štvanici vyrostli (bez dvojího metru –
        dělá nový kód; sloupec metr zůstává), Složil s Renátou Tomanovou, Mistři republiky 10× Spartak
        Praha Motorlet (1956–1965) a 2× Motorlet Praha (1966, 1968) – rozpis v textu záložky se změní
        jen spolu s řádky, čestní a zasloužilí členové a první prezidenti bez štítku „doplní klub“,
        Prof. Ing. Ladislav Šimek mezi prezidenty za J. Rösslerem-Ořovským.

   Co udělá (jen tabulky cltk_bloky, cltk_milniky a cltk_deska_zaznamy, SQLite i MySQL, jedna transakce,
   opakované spuštění nic nezmění):
     – přepíše VŽDY jen hodnotu, která se pořád PŘESNĚ rovná původní (výchozí obsah, sql/data.json před
       8. 10. 2026) nebo hodnotě z dřívější verze této migrace (8. 10. 2026 dopoledne, jen lokální kopie:
       bez nezlomitelných mezer, milník Mistrů v roce 1966, nové milníky bez pramene a jistoty); jinou
       hodnotu nechá a ohlásí „změněno v administraci – nechávám“,
     – nový řádek vloží, jen když tam ještě není (kronika: stejný rok a titulek; milník téhož roku
       s titulkem z dřívější verze nebo se stejným textem se jen doplní; deska: stejná kategorie, druh
       a jméno); řádky hledá podle obsahu, ne podle id, pořadí počítá z databáze.
   Odmítne běh, dokud jsou na serveru staré historie.php, klub.php nebo admin/historie.php. Datový soubor
   není potřeba. Vypíše každou změnu a na konci HOTOVO.

   Spuštění:
     lokálně      CLTK_DB_FILE=web/data/x.sqlite ./_php/php.exe web/sql/migrace/2026-10-08-historie-postrehy.php [--nanecisto]
     na serveru   soubor zkopírovat do kořene webu (sql/ je zvenku zavřené) a otevřít
                  …/2026-10-08-historie-postrehy.php?token=<token>   (&nanecisto=1 = jen ukázat, co by se změnilo)
                  Po doběhnutí soubor z kořene webu smazat.
   Funguje z kořene webu i ze sql/migrace/ (jádro inc/functions.php najde o patra výš).
   Výchozí obsah (sql/seed/50-historie.php, 58-klub.php, 60-bloky.php) už má nový stav – nová instalace
   migraci nepotřebuje. */

/* Token pro spuštění přes prohlížeč (32 znaků). Repozitář je veřejný (GitHub), proto je tu jen
   jeho otisk SHA-256 – token sám zná orchestrátor. Porovnává se hash_equals(). */
const HIST8_TOKEN_SHA256 = '8c096eee97c28932eff57537fb114bb7fad8b8b3de36dc39e5d180b9f0358ffd';

/** Bloky stránek: [stránka, klíč, [sloupec => [původní uložená hodnota, nová hodnota]]]. Původní hodnota může
 *  být i seznam: původní + hodnota z dřívější verze této migrace (8. 10. 2026 dopoledne).
 *  Text se ukládá přes html_k_ulozeni() (jako výchozí obsah), štítek „doplní klub“ se ruší jen spolu s textem.
 *  Rozpis titulů v textu historie/deska-mistri je zvlášť (HIST8_MISTRI_TEXT) – mění se jen spolu s řádky Mistrů. */
const HIST8_BLOKY = [
    ['historie', 'triptych', [
        'perex' => ['1954, 1973 a 2023. V den titulu hrála v barvách klubu jen vítězka posledního z nich – i to patří k poctivé historii.',
                    '1954, 1973 a 2023.'],
    ]],
    ['historie', 'linka', [
        'text' => ['<ul><li><strong>1954</strong> Jaroslav Drobný · vítěz</li><li><strong>1962</strong> Věra Suková-Pužejová · finále, první československá finalistka</li><li><strong>1973</strong> Jan Kodeš · vítěz</li><li><strong>2023</strong> Markéta Vondroušová · vítězka</li><li><strong>2026</strong> Karolína Muchová · finále s&nbsp;Lindou Noskovou 2:6, 7:5, 3:6</li></ul>',
                   '<ul><li><strong>1954</strong> Jaroslav Drobný · vítěz</li><li><strong>1962</strong> Věra Suková-Pužejová · finále, první československá finalistka</li><li><strong>1973</strong> Jan Kodeš · vítěz</li><li><strong>2023</strong> Markéta Vondroušová · vítězka</li><li><strong>2026</strong> Karolína Muchová · finále s Lindou Noskovou</li></ul>'],
    ]],
    ['historie', 'deska', [
        'perex' => ['Klubové texty počítají úspěchy všech, kdo na Štvanici vyrostli. Registr Českého tenisového svazu jen tituly v barvách klubu. Na desce ukazujeme obojí.',
                    'Na desce jsou úspěchy všech, kdo na Štvanici vyrostli – bez ohledu na to, za který klub zrovna hráli.'],
    ]],
    ['historie', 'deska-grandslam', [
        // perex je prostý text → nezlomitelná mezera jako znak U+00A0 („20 titulů“, „7 ve dvouhře“)
        'perex' => [['Klub počítá tituly všech, kdo na Štvanici vyrostli, kdykoli za klub hráli nebo jsou čestnými či zasloužilými členy: 20 titulů, z toho 7 ve dvouhře.',
                     'Všichni, kdo na Štvanici vyrostli – bez ohledu na to, za který klub v době vítězství hráli nebo kde žili: 20 titulů, z toho 7 ve dvouhře.'],
                    "Všichni, kdo na Štvanici vyrostli – bez ohledu na to, za který klub v době vítězství hráli nebo kde žili: 20\u{00A0}titulů, z toho 7\u{00A0}ve dvouhře."],
        'text'  => ['<p>Registr ČTS: jen tituly hráčů, kteří v&nbsp;den triumfu hráli za I.&nbsp;ČLTK Praha – 10 titulů. Registr začíná rokem 1996, starší tituly v&nbsp;barvách klubu jsou jen pravděpodobné.</p>',
                    ''],
    ]],
    ['historie', 'deska-cestni', [
        'text'        => ['<p>Historičtí čestní členové od roku 1893. Roky udělení doplní klub.</p>', '<p>Historičtí čestní členové od roku 1893.</p>'],
        'doplni_klub' => [1, 0],
    ]],
    ['historie', 'deska-zasluzili', [
        'text'        => ['<p>Roky udělení a&nbsp;medailony doplní klub.</p>', ''],
        'doplni_klub' => [1, 0],
    ]],
    ['historie', 'deska-prezidenti', [
        'text'        => ['<p>Prvních 36&nbsp;let – roky úřadu doplní klub.</p>', '<p>Prvních 36 let – roky úřadu neznáme.</p>'],
        'doplni_klub' => [1, 0],
    ]],
    ['klub', 'jmena', [
        'stitek' => ['Pět jmen, jeden klub', 'Sedm jmen, jeden klub'],
        'nadpis' => ['Pět jmen, jeden <em>klub</em>.', 'Sedm jmen, jeden <em>klub</em>.'],
        'text'   => ['<ul><li><strong>1893</strong> I.&nbsp;Český Lawn-Tennis Klub</li><li><strong>1949</strong> oddíl pod Sokol Jinonice, později TJ Motorlet</li><li><strong>kolem 1956</strong> Spartak Praha Motorlet</li><li><strong>kolem 1969</strong> TJ Dopravní podnik</li><li><strong>1990</strong> I.&nbsp;ČLTK Praha</li></ul><p>Roky změn jmen uvádíme podle klubu, prameny se v&nbsp;nich liší.</p>',
                     '<ul><li><strong>1893</strong> I. Český Lawn-Tennis Klub</li><li><strong>1948–1950</strong> DSO Spartak</li><li><strong>1951–1953</strong> Sokol Šverma Jinonice</li><li><strong>1954–1966</strong> Spartak Praha Motorlet</li><li><strong>1966–1969</strong> Motorlet Praha</li><li><strong>kolem 1969</strong> TJ Dopravní podnik</li><li><strong>1990</strong> I. ČLTK Praha</li></ul><p>Roky změn jmen uvádíme podle klubu, prameny se v nich liší.</p>'],
    ]],
];

/** Kronika – doba (název kapitoly sdílený milníky): [původní, nová]. */
const HIST8_ERA = ['Motorlet (od 1949)', 'Spartak a Motorlet (1949–1969)'];

/** Kronika – úpravy milníků: [rok, původní titulek, [sloupec => [původní (nebo seznam přípustných), nová]]].
 *  Změna roku: milník se hledá v původním i v novém roce (opakovaný běh), rok se mění jen spolu s rok_text
 *  a pořadí se přepočítá v novém roce. */
const HIST8_KRONIKA_UPRAVY = [
    [1949, 'Emigrace a nové jméno', [
        'text' => ['V červenci 1949 zůstává Drobný ve švýcarském Gstaadu. Klub je začleněn jako oddíl pod Sokol Jinonice a později TJ Motorlet.',
                   'V červenci 1949 zůstává Drobný ve švýcarském Gstaadu. Klub je začleněn jako oddíl pod tělovýchovnou jednotu, která postupně nese jména DSO Spartak (1948–1950), Sokol Šverma Jinonice (1951–1953), Spartak Praha Motorlet (1954–1966) a Motorlet Praha (1966–1969).'],
    ]],
    [1966, 'Mistři ligy s Kodešem', [
        'rok'          => ['1966', '1956'],          // řadí se mezi 1954 a 1962: velký rok 1956, pod ním „1956–1968“
        'rok_text'     => ['1963–1966', '1956–1968'],
        'titulek'      => ['Mistři ligy s Kodešem', 'Dvanáct titulů mistra republiky'],
        'text'         => [['Spartak Praha Motorlet s mladým Janem Kodešem vyhrává ligu smíšených družstev (1966 doloženo, 1963–1965 pravděpodobně). Pod jménem Motorlet klub podle svých pramenů získal 12 titulů.',
                            'Spartak Praha Motorlet je v letech 1956–1965 desetkrát mistrem republiky smíšených družstev, Motorlet Praha přidává tituly v letech 1966 a 1968 – v roce 1966 už s mladým Janem Kodešem.'],
                           'Spartak Praha Motorlet je v letech 1956–1965 desetkrát mistrem republiky smíšených družstev, Motorlet Praha přidává tituly v letech 1966 a 1968. Od roku 1963 hraje v prvním týmu mladý Jan Kodeš.'],
        'foto_popisek' => ['', 'Mistři ligy 1966'],  // jen prázdný popisek (je i alt fotky týmu z roku 1966)
    ]],
    [2011, 'Lucie Hradecká a Fed Cup', [
        'titulek' => ['Lucie Hradecká a Fed Cup', 'Hradecká, Benešová a Fed Cup'],
        'text'    => ['Hradecká vyhrává s Hlaváčkovou Roland Garros a rozhodující čtyřhrou finále Fed Cup; Benešová vyhrává mix ve Wimbledonu. V roce 2012 Hradecká přiváží olympijské stříbro, Ivo Minář vyhrává Davis Cup a ve foyer je 14. 6. 2012 odhalena deska Jaroslava Drobného.',
                      'Hradecká vyhrává s Hlaváčkovou Roland Garros a rozhodující čtyřhrou finále Fed Cup; Iveta Benešová vyhrává s Jürgenem Melzerem mix ve Wimbledonu. V roce 2012 Hradecká přiváží olympijské stříbro, Ivo Minář vyhrává Davis Cup a ve foyer je 14. 6. 2012 odhalena deska Jaroslava Drobného.'],
    ]],
];

/** Kronika – nové milníky: [rok, titulek, text, doba (když ji nejde vzít od sousedních milníků), pramen, jistota,
 *  titulek z dřívější verze této migrace – tehdy bez pramene a jistoty; takový milník se jen doplní]. */
const HIST8_KRONIKA_NOVE = [
    [1978, 'Složil vítězem mixu na Roland Garros', 'Pavel Složil vyhrává s Renátou Tomanovou mix na Roland Garros.', 'TJ Dopravní podnik (kolem 1970–1990)',
     'en.wikipedia 1978 French Open – Mixed doubles', 'titul ověřen; vazba na Štvanici podle klubu', 'Složil vítězem Roland Garros'],
    [2006, 'Damm vítězem čtyřhry na US Open', 'Martin Damm vyhrává s Leanderem Paesem čtyřhru na US Open.', 'I. ČLTK Praha (1990–dnes)',
     "en.wikipedia 2006 US Open – Men's doubles", 'ověřeno', 'Damm vítězem US Open'],
];

/** Zlatá deska – Grand Slam: [rok, jméno, původní čin, nový čin]. */
const HIST8_GS_SLOZIL = ['1978', 'Pavel Složil', 'Roland Garros · mix', 'Roland Garros · mix, s Renátou Tomanovou'];

/** Zlatá deska – Mistři republiky: řádek „12× Spartak Praha Motorlet“ → 1956–1965 a nad něj nový řádek Motorlet Praha. */
const HIST8_MISTRI_SPARTAK = ['jmeno' => 'Spartak Praha Motorlet', 'rok' => ['12×', '1956–1965'], 'cin' => ['roky titulů doplní klub', '10 titulů']];
const HIST8_MISTRI_MOTORLET = ['rok' => '1966, 1968', 'jmeno' => 'Motorlet Praha', 'cin' => '2 tituly', 'pramen' => 'podle klubu'];

/** Zlatá deska – rozpis titulů v textu bloku historie/deska-mistri: [[původní, z dřívější verze této migrace], nový].
 *  Mění se jen spolu s řádky Mistrů republiky (krok 3b) – jinak by popisoval řádky, které na desce nejsou.
 *  Text je HTML: nezlomitelná mezera „10× Spartak“, „2× Motorlet“ se ukládá jako &nbsp; (html_k_ulozeni). */
const HIST8_MISTRI_TEXT = [
    ['<p>Rozpis: 12× Spartak Praha Motorlet, 1975, 1990, 2018 a&nbsp;2019. Nezávisle jsou doloženy tituly 1975, 1990, 2018 a&nbsp;2019.</p>',
     '<p>Rozpis: 10× Spartak Praha Motorlet (1956–1965), 2× Motorlet Praha (1966, 1968), 1975, 1990, 2018 a&nbsp;2019. Nezávisle jsou doloženy tituly 1975, 1990, 2018 a&nbsp;2019.</p>'],
    '<p>Rozpis: 10×&nbsp;Spartak Praha Motorlet (1956–1965), 2×&nbsp;Motorlet Praha (1966, 1968), 1975, 1990, 2018 a 2019. Nezávisle jsou doloženy tituly 1975, 1990, 2018 a 2019.</p>',
];

/** Zlatá deska – prezidenti (výčet jmen prvních 36 let): [nové jméno, za koho]. */
const HIST8_PREZIDENT = ['Prof. Ing. Ladislav Šimek', 'Josef Rössler-Ořovský'];

if (PHP_SAPI !== 'cli') {
    $token = $_GET['token'] ?? '';
    if (!is_string($token) || strlen($token) !== 32 || !hash_equals(HIST8_TOKEN_SHA256, hash('sha256', $token))) {
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
$jadro = hist8_najdi_jadro(__DIR__);
if ($jadro === '') { echo "CHYBA: nenašel jsem inc/functions.php (soubor patří do kořene webu nebo do sql/migrace/).\n"; exit(1); }
require_once $jadro;
$nanecisto = PHP_SAPI === 'cli' ? in_array('--nanecisto', (array)($argv ?? []), true) : (($_GET['nanecisto'] ?? '') === '1');
$ok = hist8_migrace_spust($nanecisto, static function (string $s): void { echo $s, "\n"; });
exit($ok ? 0 : 1);

/** Najde jádro webu: inc/functions.php v této složce nebo o 1–4 patra výš. */
function hist8_najdi_jadro(string $odkud): string {
    $d = $odkud;
    for ($i = 0; $i <= 4; $i++) {
        if (is_file($d . '/inc/functions.php') && is_file($d . '/inc/db.php')) return $d . '/inc/functions.php';
        $d = dirname($d);
    }
    return '';
}

/** Hodnota do výpisu jako prostý text na jednom řádku (bez značek a entit). */
function hist8_prosty(string $s): string {
    $s = html_entity_decode(strip_tags(str_replace(['</li><li>', '</p><p>'], ' · ', $s)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string)preg_replace('~[\s\x{00A0}]+~u', ' ', $s));
}

/** Hodnota do výpisu: prostý text, nejvýš $n znaků. */
function hist8_kratce(string $s, int $n = 80): string {
    $s = hist8_prosty($s);
    return $s === '' ? '(prázdné)' : mb_strimwidth($s, 0, $n, '…', 'UTF-8');
}

/** Změna do výpisu: „stará“ → „nová“ od místa, kde se liší (dlouhý společný začátek se zkrátí na „…“). */
function hist8_zmena(string $stara, string $nova, int $n = 110): string {
    $a = mb_str_split(hist8_prosty($stara), 1, 'UTF-8');
    $b = mb_str_split(hist8_prosty($nova), 1, 'UTF-8');
    if ($a === $b) return '„' . hist8_kratce($nova) . '“ – liší se jen nezlomitelnými mezerami nebo značkami HTML';
    $i = 0;
    while ($i < count($a) && $i < count($b) && $a[$i] === $b[$i]) $i++;
    $od = $i > 45 ? $i - 30 : 0;                         // kousek společného textu nechat jako kontext
    for ($k = 0; $od > 0 && $k < 15 && $a[$od - 1] !== ' '; $k++) $od--;   // od začátku slova
    $ukaz = static function (array $z) use ($od, $n): string {
        if (!$z) return '(prázdné)';
        return ($od > 0 ? '…' : '') . mb_strimwidth(implode('', array_slice($z, $od)), 0, $n, '…', 'UTF-8');
    };
    return '„' . $ukaz($a) . '“ → „' . $ukaz($b) . '“';
}

/** Stejný titulek / jméno? (bez ohledu na velikost písmen, okrajové a nezlomitelné mezery) */
function hist8_stejne(string $a, string $b): bool {
    $n = static fn(string $s): string => mb_strtolower(trim((string)preg_replace('~[\s\x{00A0}]+~u', ' ', $s)), 'UTF-8');
    return $n($a) === $n($b);
}

/** Nový kód webu je nahraný? Staré soubory mají přepínače „V den titulu hrál za“ a dvojího metru. */
function hist8_kod_ok(callable $log): bool {
    $kontroly = [
        'historie.php'       => static fn(string $s): bool => !str_contains($s, 'data-trava-prepinac') && !str_contains($s, 'data-metr-volba'),
        'klub.php'           => static fn(string $s): bool => str_contains($s, 'Sedm jmen, jeden klub.'),
        'admin/historie.php' => static fn(string $s): bool => !str_contains($s, "pole_select('metr'") && !str_contains($s, "pole_text('hral_za'"),
    ];
    $stare = [];
    foreach ($kontroly as $soubor => $novy) {
        $cesta = WEB_ROOT . '/' . $soubor;
        if (!is_file($cesta) || !$novy((string)file_get_contents($cesta))) $stare[] = $soubor;
    }
    if ($stare) {
        $log('CHYBA: na serveru je starý kód webu (' . implode(', ', $stare) . ' – ještě s „V den titulu hrál za“, dvojím metrem nebo „Pět jmen“).');
        $log('Nejdřív nahrajte nové soubory webu (historie.php, klub.php, admin/historie.php, admin/stranky.php, assets/js/app.js,');
        $log('assets/css/styl.css, assets/css/stranky-klub.css), pak migraci spusťte znovu. Nic se nezměnilo.');
        return false;
    }
    return true;
}

/** Doba pro nový milník: jako ostatní milníky téhož roku, jinak jako sousední milníky (když mají stejnou), jinak $vychozi.
 *  $po = převod doby, jejíž přejmenování nanečisto ještě není zapsané. */
function hist8_era_pro_rok(int $rok, string $vychozi, callable $po): string {
    $stejny = row('SELECT era FROM cltk_milniky WHERE rok = ? ORDER BY poradi DESC, id DESC LIMIT 1', [$rok]);
    if ($stejny && trim((string)$stejny['era']) !== '') return $po((string)$stejny['era']);
    $pred = row('SELECT era FROM cltk_milniky WHERE rok < ? ORDER BY rok DESC, poradi DESC, id DESC LIMIT 1', [$rok]);
    $za = row('SELECT era FROM cltk_milniky WHERE rok > ? ORDER BY rok, poradi, id LIMIT 1', [$rok]);
    if ($pred && $za && trim((string)$pred['era']) !== '' && (string)$pred['era'] === (string)$za['era']) return $po((string)$pred['era']);
    return $vychozi;
}

/**
 * Celá migrace. $log dostává řádky výpisu. $nanecisto = nic nezapisuje, jen vypíše, co by udělala.
 * Vrací true = v pořádku (i když něco nechala kvůli úpravám v administraci).
 */
function hist8_migrace_spust(bool $nanecisto, callable $log): bool {
    $log('Migrace 2026-10-08-historie-postrehy · databáze: ' . DB_DRIVER . ($nanecisto ? ' · NANEČISTO (nic se nezapíše – řádky ukazují, co by se změnilo)' : ''));
    $log('');

    /* ---------- 0) kontroly: tabulky a nový kód webu ---------- */
    $chybi = array_diff(['cltk_bloky', 'cltk_milniky', 'cltk_deska_zaznamy'], db_tabulky());
    if ($chybi) {
        $log('CHYBA: v databázi chybí tabulky ' . implode(', ', $chybi) . ' – je web nainstalovaný? Nic se nezměnilo.');
        return false;
    }
    if (!hist8_kod_ok($log)) return false;
    $log('Kontrola: nový kód webu je nahraný (historie.php, klub.php, admin/historie.php).');

    $zmen = 0;
    $nechano = 0;
    $zmena = static function (string $s, int $kolik = 1) use (&$zmen, $log): void { $zmen += $kolik; $log('  ' . $s); };
    $nechat = static function (string $s) use (&$nechano, $log): void { $nechano++; $log('  POZOR ' . $s); };
    $beze = static function (string $s) use ($log): void { $log('  ' . $s . ' – už v novém stavu, beze změny'); };
    [$eraStara, $eraNova] = HIST8_ERA;
    $poPrejmenovani = static fn(string $e): string => $e === $eraStara ? $eraNova : $e;

    $pdo = db();
    $vTransakci = false;
    if (!$nanecisto) { $pdo->beginTransaction(); $vTransakci = true; }
    try {
        /* ---------- 1) bloky stránek Historie a Klub ---------- */
        $log('');
        $log('1) Bloky stránek (modul Stránky):');
        foreach (HIST8_BLOKY as [$stranka, $klic, $zmeny]) {
            $kde = "blok $stranka/$klic";
            $r = row('SELECT * FROM cltk_bloky WHERE stranka = ? AND klic = ?', [$stranka, $klic]);
            if (!$r) { $nechat("$kde: v databázi chybí – přeskočeno"); continue; }
            $data = [];
            $textNovy = true;                      // štítek „doplní klub“ se ruší, jen když je text bloku nový
            foreach ($zmeny as $sloupec => [$stara, $nova]) {
                if ($sloupec === 'doplni_klub') continue;
                if ($sloupec === 'text' && $nova !== '') $nova = html_k_ulozeni($nova);
                $ted = (string)$r[$sloupec];
                if ($ted === $nova) { $beze("$kde – $sloupec"); continue; }
                if (!in_array($ted, (array)$stara, true)) {
                    $nechat("$kde – $sloupec: změněno v administraci – nechávám („" . hist8_kratce($ted) . '“)');
                    if ($sloupec === 'text') $textNovy = false;
                    continue;
                }
                $data[$sloupec] = $nova;
                $zmena("$kde – $sloupec: " . hist8_zmena($ted, $nova));
            }
            if (isset($zmeny['doplni_klub'])) {
                [$stara, $nova] = $zmeny['doplni_klub'];
                $ted = (int)$r['doplni_klub'];
                if ($ted === $nova) $beze("$kde – štítek „doplní klub“");
                elseif (!$textNovy) $nechat("$kde – štítek „doplní klub“: text bloku je upravený v administraci – štítek nechávám");
                elseif ($ted !== $stara) $nechat("$kde – štítek „doplní klub“: hodnota $ted není původní – nechávám");
                else { $data['doplni_klub'] = $nova; $zmena("$kde – štítek „doplní klub“: zrušen"); }
            }
            if ($data && !$nanecisto) db_update('cltk_bloky', (int)$r['id'], $data + ['updated_at' => ted()]);
        }

        /* ---------- 2) kronika ---------- */
        $log('');
        $log('2) Kronika (modul Historie → Kronika):');
        // 2a) doba „Motorlet (od 1949)“ – sdílený název kapitoly, přejmenuje se u všech jejích milníků
        $radky = array_values(array_filter(rows('SELECT id, rok, titulek, era FROM cltk_milniky WHERE era = ? ORDER BY rok, poradi, id', [$eraStara]),
            static fn(array $x): bool => (string)$x['era'] === $eraStara));
        if ($radky) {
            foreach ($radky as $x) if (!$nanecisto) q('UPDATE cltk_milniky SET era = ? WHERE id = ? AND era = ?', [$eraNova, (int)$x['id'], $eraStara]);
            $zmena('doba „' . $eraStara . '“ → „' . $eraNova . '“ (' . count($radky) . ' ' . sklonuj(count($radky), 'milník', 'milníky', 'milníků') . ': '
                . implode(', ', array_map(static fn(array $x): string => $x['rok'] . ' ' . $x['titulek'], $radky)) . ')', count($radky));
        } elseif ((int)val('SELECT COUNT(*) FROM cltk_milniky WHERE era = ?', [$eraNova]) > 0) {
            $beze('doba „' . $eraNova . '“');
        } else {
            $nechat('doba „' . $eraStara . '“ v kronice není (přejmenovaná v administraci?) – nechávám');
        }

        // 2b) úpravy milníků 1949, 1966 (→ 1956) a 2011
        foreach (HIST8_KRONIKA_UPRAVY as [$rok, $titulekStary, $zmeny]) {
            $titulekNovy = $zmeny['titulek'][1] ?? $titulekStary;
            $rokNovy = isset($zmeny['rok']) ? (int)$zmeny['rok'][1] : $rok;
            $kde = "milník $rok „{$titulekStary}“";
            // v původním i v novém roce (milník 1966 je po prvním běhu v roce 1956)
            $kandidati = rows('SELECT * FROM cltk_milniky WHERE rok = ? OR rok = ? ORDER BY rok, poradi, id', [$rok, $rokNovy]);
            $r = null;
            foreach ($kandidati as $x) {
                if ((string)$x['titulek'] === $titulekStary || (string)$x['titulek'] === $titulekNovy) { $r = $x; break; }
            }
            if (!$r) {   // jediný milník původního roku s titulkem upraveným v administraci (po prvním běhu: v novém roce s novým rok_text)
                $vRoce = array_values(array_filter($kandidati, static fn(array $x): bool => (int)$x['rok'] === $rok));
                if (!$vRoce && $rokNovy !== $rok && isset($zmeny['rok_text'])) {
                    $vRoce = array_values(array_filter($kandidati, static fn(array $x): bool => (int)$x['rok'] === $rokNovy && (string)$x['rok_text'] === $zmeny['rok_text'][1]));
                }
                if (count($vRoce) === 1) $r = $vRoce[0];
            }
            if (!$r) { $nechat("$kde: v kronice není (titulek upraven nebo milník smazán v administraci) – nechávám"); continue; }
            // rok a „rok, jak se má ukázat“ patří k sobě: rok se změní, jen když je rok_text nový (nebo se teď mění na nový)
            $rokTextOk = !isset($zmeny['rok_text']) || (string)$r['rok_text'] === $zmeny['rok_text'][1]
                || in_array((string)$r['rok_text'], (array)$zmeny['rok_text'][0], true);
            $data = [];
            foreach ($zmeny as $sloupec => [$stara, $nova]) {
                $ted = (string)$r[$sloupec];
                if ($ted === $nova) { $beze("$kde – $sloupec"); continue; }
                if (!in_array($ted, (array)$stara, true)) { $nechat("$kde – $sloupec: změněno v administraci – nechávám („" . hist8_kratce($ted) . '“)'); continue; }
                if ($sloupec === 'rok') {
                    if (!$rokTextOk) { $nechat("$kde – rok: „rok, jak se má ukázat“ je upravený v administraci – rok $ted nechávám"); continue; }
                    $data['rok'] = $rokNovy;
                    $data['poradi'] = (int)val('SELECT COALESCE(MAX(poradi), -1) + 1 FROM cltk_milniky WHERE rok = ? AND id <> ?', [$rokNovy, (int)$r['id']]);
                    $pred = (string)val('SELECT MAX(rok) FROM cltk_milniky WHERE rok < ? AND id <> ?', [$rokNovy, (int)$r['id']]);
                    $za = (string)val('SELECT MIN(rok) FROM cltk_milniky WHERE rok > ? AND id <> ?', [$rokNovy, (int)$r['id']]);
                    $zmena("$kde – rok: „{$ted}“ → „{$rokNovy}“ (v kronice " . ($pred !== '' && $za !== '' ? "mezi roky $pred a $za" : 'podle roku')
                        . ($data['poradi'] > 0 ? ', ' . ($data['poradi'] + 1) . '. milník roku' : '') . ')');
                    continue;
                }
                $data[$sloupec] = $nova;
                $zmena("$kde – $sloupec: " . hist8_zmena($ted, $nova));
            }
            if ($data && !$nanecisto) db_update('cltk_milniky', (int)$r['id'], $data);
        }

        // 2c) nové milníky 1978 a 2006 (doba podle sousedních milníků, pořadí na konci svého roku, s pramenem
        //     a jistotou); milník, který vložila dřívější verze této migrace (kratší titulek, bez pramene), se jen doplní
        foreach (HIST8_KRONIKA_NOVE as [$rok, $titulek, $text, $eraVychozi, $zdroj, $jistota, $titulekDrivejsi]) {
            $kde = "milník $rok „{$titulek}“";
            $vRoce = rows('SELECT * FROM cltk_milniky WHERE rok = ? ORDER BY poradi, id', [$rok]);
            if (array_filter($vRoce, static fn(array $x): bool => hist8_stejne((string)$x['titulek'], $titulek))) {
                $log("  $kde: v kronice už je – beze změny");
                continue;
            }
            // milník z dřívější verze: titulek z ní, nebo stejný text (titulek mezitím upravený v administraci) – nevložit dvakrát
            $drivejsi = null;
            foreach ($vRoce as $x) if (hist8_stejne((string)$x['titulek'], $titulekDrivejsi) || (string)$x['text'] === $text) { $drivejsi = $x; break; }
            if ($drivejsi) {
                $kde = "milník $rok „{$drivejsi['titulek']}“ (už v kronice – jen doplnit)";
                $data = [];
                foreach (['titulek' => [$titulekDrivejsi, $titulek], 'zdroj' => ['', $zdroj], 'jistota' => ['', $jistota]] as $sloupec => [$stara, $nova]) {
                    $ted = (string)$drivejsi[$sloupec];
                    if ($ted === $nova) { $beze("$kde – $sloupec"); continue; }
                    if ($ted !== $stara) { $nechat("$kde – $sloupec: změněno v administraci – nechávám („" . hist8_kratce($ted) . '“)'); continue; }
                    $data[$sloupec] = $nova;
                    $zmena("$kde – $sloupec: " . hist8_zmena($ted, $nova));
                }
                if ($data && !$nanecisto) db_update('cltk_milniky', (int)$drivejsi['id'], $data);
                continue;
            }
            $era = hist8_era_pro_rok($rok, $eraVychozi, $poPrejmenovani);
            $poradi = (int)val('SELECT COALESCE(MAX(poradi), -1) + 1 FROM cltk_milniky WHERE rok = ?', [$rok]);
            if (!$nanecisto) db_insert('cltk_milniky', [
                'rok' => $rok, 'rok_text' => '', 'era' => $era, 'titulek' => $titulek, 'text' => $text, 'foto' => '',
                'foto_popisek' => '', 'zdroj' => $zdroj, 'jistota' => $jistota, 'visible' => 1, 'poradi' => $poradi,
            ]);
            $zmena("$kde: přidán (doba „{$era}“" . ($poradi > 0 ? ', ' . ($poradi + 1) . '. milník roku' : '') . ') – „' . hist8_kratce($text)
                . '“, pramen „' . $zdroj . '“, jistota „' . $jistota . '“');
        }

        /* ---------- 3) Zlatá deska ---------- */
        $log('');
        $log('3) Zlatá deska (modul Historie → Zlatá deska):');
        // 3a) Grand Slam – Složil s Renátou Tomanovou
        [$gsRok, $gsJmeno, $gsStary, $gsNovy] = HIST8_GS_SLOZIL;
        $kde = "Grand Slam $gsRok $gsJmeno – čin";
        $kandidati = array_values(array_filter(rows("SELECT * FROM cltk_deska_zaznamy WHERE kategorie = 'grandslam' AND skupina = 'hlavni' ORDER BY poradi, id"),
            static fn(array $x): bool => (string)$x['rok'] === $gsRok && hist8_stejne((string)$x['jmeno'], $gsJmeno)));
        $r = null;
        foreach ($kandidati as $x) if (in_array((string)$x['cin'], [$gsStary, $gsNovy], true)) { $r = $x; break; }
        if (!$r && $kandidati) $r = $kandidati[0];
        if (!$r) $nechat("$kde: řádek v desce není (upraven nebo smazán v administraci) – nechávám");
        elseif ((string)$r['cin'] === $gsNovy) $beze($kde);
        elseif ((string)$r['cin'] !== $gsStary) $nechat("$kde: změněno v administraci – nechávám („" . hist8_kratce((string)$r['cin']) . '“)');
        else {
            if (!$nanecisto) q('UPDATE cltk_deska_zaznamy SET cin = ? WHERE id = ? AND cin = ?', [$gsNovy, (int)$r['id'], $gsStary]);
            $zmena("$kde: „{$gsStary}“ → „{$gsNovy}“");
        }

        // 3b) Mistři republiky – „12× Spartak Praha Motorlet“ → 1956–1965 (10 titulů) a nad něj Motorlet Praha 1966, 1968
        $sp = HIST8_MISTRI_SPARTAK;
        $mo = HIST8_MISTRI_MOTORLET;
        $mistriPrevedeni = false;                  // řádky Mistrů v novém stavu → smí se změnit i rozpis v textu záložky
        $mistri = rows("SELECT * FROM cltk_deska_zaznamy WHERE kategorie = 'mistri' AND skupina = 'hlavni' ORDER BY poradi, id");
        $spartaky = array_values(array_filter($mistri, static fn(array $x): bool => hist8_stejne((string)$x['jmeno'], $sp['jmeno'])));
        $r = null;
        foreach ($spartaky as $x) if (in_array((string)$x['rok'], $sp['rok'], true)) { $r = $x; break; }
        if (!$r && $spartaky) $r = $spartaky[0];
        if (!$r) {
            $nechat('Mistři republiky: řádek „' . $sp['rok'][0] . ' ' . $sp['jmeno'] . '“ v desce není (upraven nebo smazán v administraci) – Mistry republiky nechávám');
        } else {
            $kde = 'Mistři republiky ' . $sp['jmeno'];
            // rok a čin patří k sobě („12×“ + „roky titulů doplní klub“ → „1956–1965“ + „10 titulů“): když klub
            // jedno z nich upravil, řádek zůstane celý, jak je, a řádek Motorlet Praha nepřibude
            $upraveno = array_filter(['rok', 'cin'], static fn(string $sl): bool => !in_array((string)$r[$sl], $sp[$sl], true));
            $spartakNovy = !$upraveno;
            $mistriPrevedeni = $spartakNovy;       // stejná podmínka jako u přidání řádku Motorlet Praha níž
            if ($upraveno) {
                $nechat("$kde: řádek je upravený v administraci (rok „" . hist8_kratce((string)$r['rok']) . '“, čin „' . hist8_kratce((string)$r['cin']) . '“) – nechávám');
            } else {
                $data = [];
                foreach (['rok', 'cin'] as $sloupec) {
                    [$stara, $nova] = $sp[$sloupec];
                    if ((string)$r[$sloupec] === $nova) { $beze("$kde – $sloupec"); continue; }
                    $data[$sloupec] = $nova;
                    $zmena("$kde – $sloupec: „{$stara}“ → „{$nova}“");
                }
                if ($data && !$nanecisto) db_update('cltk_deska_zaznamy', (int)$r['id'], $data);
            }

            $kde = 'Mistři republiky ' . $mo['rok'] . ' ' . $mo['jmeno'];
            if (array_filter($mistri, static fn(array $x): bool => hist8_stejne((string)$x['jmeno'], $mo['jmeno']))) {
                $log("  $kde: v desce už je – beze změny");
            } elseif (!$spartakNovy) {
                $nechat("$kde: řádek Spartak Praha Motorlet je upravený v administraci – nový řádek nepřidávám, řádky i rozpis titulů upravte ručně");
            } else {
                $poradi = (int)$r['poradi'];       // nad řádek Spartaku, ten a další se posunou o jedno níž
                if (!$nanecisto) {
                    q("UPDATE cltk_deska_zaznamy SET poradi = poradi + 1 WHERE kategorie = 'mistri' AND skupina = 'hlavni' AND poradi >= ?", [$poradi]);
                    db_insert('cltk_deska_zaznamy', ['kategorie' => 'mistri', 'skupina' => 'hlavni', 'rok' => $mo['rok'], 'jmeno' => $mo['jmeno'],
                        'cin' => $mo['cin'], 'pramen' => $mo['pramen'], 'metr' => '', 'historie' => 0, 'visible' => 1, 'poradi' => $poradi]);
                }
                $posun = count(array_filter($mistri, static fn(array $x): bool => (int)$x['poradi'] >= $poradi));
                $zmena("$kde: přidán – „{$mo['cin']}“, {$mo['pramen']}, hned nad řádek Spartak Praha Motorlet"
                    . ($posun ? " ($posun " . sklonuj($posun, 'řádek', 'řádky', 'řádků') . ' o místo níž)' : ''));
            }
        }

        // 3b) … a rozpis titulů v textu záložky (blok historie/deska-mistri) – jen když jsou řádky Mistrů převedené
        [$mtStare, $mtNovy] = HIST8_MISTRI_TEXT;
        $mtNovy = html_k_ulozeni($mtNovy);
        $kde = 'Mistři republiky – rozpis titulů (text bloku historie/deska-mistri)';
        $b = row("SELECT * FROM cltk_bloky WHERE stranka = 'historie' AND klic = 'deska-mistri'");
        if (!$b) $nechat("$kde: blok v databázi chybí – přeskočeno");
        elseif ((string)$b['text'] === $mtNovy) $beze($kde);
        elseif (!in_array((string)$b['text'], $mtStare, true)) $nechat("$kde: změněno v administraci – nechávám („" . hist8_kratce((string)$b['text']) . '“)');
        elseif (!$mistriPrevedeni) $nechat("$kde: řádky Mistrů republiky nejsou převedené (viz výše) – text nechávám, upravte ho ručně spolu s řádky");
        else {
            if (!$nanecisto) db_update('cltk_bloky', (int)$b['id'], ['text' => $mtNovy, 'updated_at' => ted()]);
            $zmena("$kde: " . hist8_zmena((string)$b['text'], $mtNovy));
        }

        // 3c) Prezidenti – Prof. Ing. Ladislav Šimek ve výčtu prvních 36 let za Josefem Rössler-Ořovským
        [$prJmeno, $prZa] = HIST8_PREZIDENT;
        $kde = "Prezidenti – $prJmeno";
        $vycet = rows("SELECT * FROM cltk_deska_zaznamy WHERE kategorie = 'prezidenti' AND skupina = 'jmena' ORDER BY poradi, id");
        if (array_filter($vycet, static fn(array $x): bool => hist8_stejne((string)$x['jmeno'], $prJmeno))) {
            $log("  $kde: ve výčtu už je – beze změny");
        } else {
            $za = null;
            foreach ($vycet as $x) if (hist8_stejne((string)$x['jmeno'], $prZa)) { $za = $x; break; }
            if (!$za) foreach ($vycet as $x) if (str_contains(mb_strtolower((string)$x['jmeno'], 'UTF-8'), 'rössler')) { $za = $x; break; }
            $poradi = $za ? (int)$za['poradi'] + 1 : (int)val("SELECT COALESCE(MAX(poradi), -1) + 1 FROM cltk_deska_zaznamy WHERE kategorie = 'prezidenti' AND skupina = 'jmena'");
            $posun = count(array_filter($vycet, static fn(array $x): bool => (int)$x['poradi'] >= $poradi));
            if (!$nanecisto) {
                if ($posun) q("UPDATE cltk_deska_zaznamy SET poradi = poradi + 1 WHERE kategorie = 'prezidenti' AND skupina = 'jmena' AND poradi >= ?", [$poradi]);
                db_insert('cltk_deska_zaznamy', ['kategorie' => 'prezidenti', 'skupina' => 'jmena', 'rok' => '', 'jmeno' => $prJmeno,
                    'cin' => '', 'pramen' => '', 'metr' => '', 'historie' => 0, 'visible' => 1, 'poradi' => $poradi]);
            }
            if ($za) {
                $zmena("$kde: přidán za „{$za['jmeno']}“" . ($posun ? " ($posun " . sklonuj($posun, 'jméno', 'jména', 'jmen') . ' o místo dál)' : ''));
            } else {
                $zmena("$kde: přidán na konec výčtu");
                $nechat("$kde: „{$prZa}“ ve výčtu prezidentů není – Šimek je na konci, pořadí upravte v administraci");
            }
        }

        if ($vTransakci) { $pdo->commit(); $vTransakci = false; }
    } catch (Throwable $e) {
        if ($vTransakci && $pdo->inTransaction()) $pdo->rollBack();
        $log('CHYBA: ' . $e->getMessage());
        $log('Nic se nezměnilo (ROLLBACK).');
        return false;
    }

    /* ---------- 4) kontrola: co v databázi zůstává (jen čtení) ---------- */
    $log('');
    if ($nanecisto) {
        $log('4) Kontrola zbývajících zmínek: nanečisto se nedělá (databáze je pořád v původním stavu).');
    } else {
        $log('4) Kontrola – zmínky, které v databázi zůstávají (jen pro přehled, nic se nemění):');
        $hledat = ['v barvách klubu', 'V den titulu', 'Motorlet (od 1949)', 'Sokol Jinonice', 'Pět jmen', '12×', '12 titulů', '2:6, 7:5, 3:6'];
        $nalezeno = 0;
        foreach (['cltk_bloky' => ['stitek', 'nadpis', 'perex', 'text'], 'cltk_milniky' => ['era', 'titulek', 'text', 'zdroj', 'jistota'],
                  'cltk_deska_zaznamy' => ['rok', 'jmeno', 'cin', 'pramen'], 'cltk_osobnosti' => ['cin']] as $tab => $sloupce) {
            foreach (rows('SELECT * FROM ' . cltk_tabulka($tab) . ' ORDER BY id') as $x) {
                // „doplní klub“ jen ve Zlaté desce (jinde na webu je štítek záměrně)
                $vDesce = $tab === 'cltk_deska_zaznamy' || ($tab === 'cltk_bloky' && $x['stranka'] === 'historie' && str_starts_with((string)$x['klic'], 'deska'));
                foreach ($sloupce as $sl) {
                    $v = str_replace(['&nbsp;', "\u{00A0}"], ' ', (string)$x[$sl]);
                    foreach ($vDesce ? [...$hledat, 'doplní klub'] : $hledat as $h) {
                        if (mb_stripos($v, $h, 0, 'UTF-8') === false) continue;
                        $kde = $tab === 'cltk_bloky' ? 'blok ' . $x['stranka'] . '/' . $x['klic'] : $tab . ' ' . (int)$x['id'];
                        $p = hist8_prosty((string)$x[$sl]);
                        $kde .= " – $sl: „{$h}“";
                        $pos = (int)mb_stripos($p, $h, 0, 'UTF-8');
                        $log("  $kde (" . mb_strimwidth($pos > 50 ? '…' . mb_substr($p, $pos - 45) : $p, 0, 130, '…', 'UTF-8') . ')');
                        $nalezeno++;
                    }
                }
            }
        }
        $doplni = rows("SELECT klic FROM cltk_bloky WHERE stranka = 'historie' AND klic LIKE 'deska%' AND doplni_klub = 1 ORDER BY klic");
        foreach ($doplni as $x) { $log('  blok historie/' . $x['klic'] . ' – štítek „doplní klub“ zůstává'); $nalezeno++; }
        if (!$nalezeno) $log('  nic');
    }

    $log('');
    $log(($nanecisto ? 'Změn by bylo: ' : 'Změn: ') . $zmen . ($zmen === 0 && !$nechano ? ' – databáze už je v novém stavu.' : '.')
        . ($nechano ? ' Ponecháno kvůli úpravám v administraci / nenalezeno: ' . $nechano . ' (řádky POZOR výše).' : ''));
    $log($nanecisto ? 'HOTOVO (nanečisto – nic se nezapsalo)' : 'HOTOVO');
    return true;
}
