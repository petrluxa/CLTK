<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Vedení klubu (Výkonný výbor, kancelář, další kontakty) a Centenary Tennis Clubs.
   Zdroj: obsah.json → klub.vykonny_vybor, klub.kancelar, klub.kontakty,
   klub.clenstvi_v_organizacich, klub.rodokmen_stoletych.
   Telefony Petra Vaníčka a Vladislava Šavrdy klient na webu nechce
   (ZADANI §4.10) – u nich je zobrazit_kontakt = 0. */

$klub = seed_json('obsah.json')['klub'] ?? [];

/* ---------- vedení ---------- */
if (seed_prazdna('cltk_vedeni')) {
    $kontakty = [];
    foreach ($klub['kontakty'] ?? [] as $k) {
        if (!empty($k['jmeno'])) $kontakty[$k['jmeno']] = $k;
    }
    $skryt = ['Petr Vaníček', 'Vladislav Šavrda'];
    $radky = [];
    $osoba = static function (string $skupina, string $jmeno, string $funkce) use ($kontakty, $skryt): array {
        $k = $kontakty[$jmeno] ?? [];
        return ['skupina' => $skupina, 'jmeno' => $jmeno, 'funkce' => $funkce,
                'telefon' => (string)($k['telefon'] ?? ''), 'email' => (string)($k['email'] ?? ''),
                'zobrazit_kontakt' => in_array($jmeno, $skryt, true) ? 0 : 1, 'foto' => '', 'text' => '', 'visible' => 1];
    };
    foreach ($klub['vykonny_vybor'] ?? [] as $v) {
        $jmeno = trim(preg_replace('/\s*\(.*\)\s*$/u', '', $v));
        $funkce = preg_match('/\((.*)\)/u', $v, $m) ? $m[1] : 'člen výboru';
        $radky[] = $osoba('vybor', $jmeno, $funkce);
    }
    foreach ($klub['kancelar'] ?? [] as $v) {
        [$jmeno, $funkce] = array_map('trim', explode(' – ', $v, 2)) + [1 => ''];
        $radky[] = $osoba('kancelar', $jmeno, $funkce);
    }
    // další kontakty, které nejsou v kanceláři
    foreach ([
        ['Mgr. Jan Pecha, Ph.D.', 'vedoucí trenér Tenisové školy (hráči do 9 let)'],
        ['Bc. Joseph B. Truesdale', 'wellness a fyzioterapie'],
        ['Tomáš Truneček', 'tenis shop (vestibul, Po–Pá 9:00–12:00 a 13:00–17:00)'],
    ] as [$jmeno, $funkce]) {
        $radky[] = $osoba('kontakt', $jmeno, $funkce);
    }
    $radky[] = ['skupina' => 'kontakt', 'jmeno' => 'Webmaster', 'funkce' => 'správa webu', 'telefon' => '', 'email' => 'webmaster@cltk.cz',
                'zobrazit_kontakt' => 1, 'foto' => '', 'text' => '', 'visible' => 1];

    $poradi = [];
    foreach ($radky as &$x) { $poradi[$x['skupina']] = ($poradi[$x['skupina']] ?? -1) + 1; $x['poradi'] = $poradi[$x['skupina']]; }
    unset($x);
    seed_log('Vedení: ' . seed_vloz('cltk_vedeni', $radky) . ' osob.');
}

/* ---------- Centenary Tennis Clubs ---------- */
/** Kluby, které hrály na Štvanici v klubem pořádané CTC Senior Competition (seznam od klubu 8. 10. 2026; Fitzwilliam LTC
    je v seznamu už jako soupeř z přátelského utkání). Názvy opravené podle klubů (Genève, Edgbaston, Wiener Parkclub). */
defined('CTC_KLUBY_SENIOR') || define('CTC_KLUBY_SENIOR', [
    ['Tennis Club Parioli', 'Řím'], ['Tennis Club de Genève', 'Ženeva'], ['Edgbaston Priory Club', 'Birmingham'],
    ['Real Club de Polo de Barcelona', 'Barcelona'], ['Wiener Parkclub', 'Vídeň'], ['Kungliga LTK', 'Stockholm'],
    ['Real Sociedad de Tenis de La Magdalena', 'Santander'], ['TC Padova', 'Padova'], ['SALK', 'Stockholm'], ['Cumberland LTC', 'Londýn'],
]);
if (seed_prazdna('cltk_ctc')) {
    $r = [];
    $add = static function (array $x) use (&$r): void {
        $r[] = array_merge(['typ' => 'fakt', 'nazev' => '', 'rok' => '', 'misto' => '', 'text' => '', 'odkaz' => '', 'zdroj' => '', 'zvyraznit' => 0, 'visible' => 1], $x);
    };

    $add(['nazev' => 'Sdružení stoletých klubů', 'text' => 'Centenary Tennis Clubs sdružuje tenisové kluby starší 100 let. Vzniklo v roce 1996 pod patronací J. A. Samaranche se sídlem v Olympijském muzeu v Lausanne a dnes má 98 klubů.',
          'odkaz' => 'http://www.centenarytennisclubs.com/members.htm', 'zdroj' => 'web klubu 2026; seznam členů CTC']);
    $add(['nazev' => 'Jediný český člen', 'rok' => '2000', 'text' => 'I. ČLTK Praha je členem od roku 2000 a jediným klubem z České republiky.', 'zdroj' => 'seznam členů CTC; web klubu']);
    // 8. 10. 2026 podle připomínek klubu: bez „zdarma“ (záleží na každém klubu), Šimůnek od 2011 viceprezident CTC
    $add(['nazev' => 'Hra v klubech CTC', 'text' => 'Členové klubu mohou po doporučení generálního manažera hrát v klubech sdružených v Centenary Tennis Clubs.', 'zdroj' => 'web klubu']);
    $add(['nazev' => 'Klub ve vedení CTC', 'rok' => '2005', 'text' => 'Ing. Petr Šimůnek je členem řídicího výboru CTC od roku 2005, od roku 2011 ve funkci viceprezidenta.', 'zdroj' => 'web klubu']);

    foreach (['Real Club de Tenis Barcelona-1899' => 'Barcelona', 'Villa Primrose' => 'Bordeaux', 'Fitzwilliam LTC' => 'Dublin',
              'H.L.T.C. Leimonias' => 'Haag', 'Carrickmines Croquet & LTC' => 'Dublin'] as $nazev => $misto) {
        $add(['typ' => 'klub', 'nazev' => $nazev, 'misto' => $misto, 'text' => 'přátelské utkání na Štvanici', 'zdroj' => 'I. ČLTK Praha, stránka „CTC – Centenary Tennis Clubs“']);
    }

    $add(['typ' => 'utkani', 'nazev' => 'All England Lawn Tennis Club na Štvanici', 'rok' => '2025', 'text' => '6.–8. 6. 2025', 'zdroj' => 'klubový kalendář; newsletter 5/2025']);
    // akce International Clubs (2025, 2026) vypuštěny 8. 10. 2026 – klub jen poskytuje kurty, nejsou jeho (id 11, 12)

    $add(['typ' => 'soutez', 'nazev' => 'Carrickmines Cup (U12)', 'rok' => '2007', 'text' => 'Od roku 2007; soupeři z Dublinu, Barcelony, Londýna, Santanderu a Stockholmu.', 'zdroj' => 'web klubu']);
    $add(['typ' => 'soutez', 'nazev' => 'I. ČLTK Praha Cup (U14)', 'rok' => '2008', 'text' => 'Od roku 2008 mezinárodní přátelské utkání dětí U14 na Štvanici, tradičně první listopadový víkend.', 'odkaz' => 'akce.php', 'zdroj' => 'web klubu']);
    $add(['typ' => 'soutez', 'nazev' => 'CTC Senior Competition', 'rok' => '', 'text' => 'Seniorské týmy klubu vyhrály finálovou skupinu v letech 2016–2019. Klub pořádal skupinu čtyřikrát, naposledy 30.–31. 8. 2025 – trojzápas s Padovou a Cumberlandem (I. ČLTK – Padova 4:5, I. ČLTK – Cumberland 6:3).', 'zdroj' => 'Revue 02/2025']);

    /* rodokmen stoletých klubů – popisky jako ve Variantě 4, roky podle webů klubů */
    $zdroje = [];
    foreach ($klub['rodokmen_stoletych']['kluby'] ?? [] as $k) $zdroje[(int)$k['rok'] . '|' . mb_substr((string)$k['klub'], 0, 12)] = (string)($k['zdroj'] ?? '');
    foreach ([
        // 1869 The Hurlingham Club vypuštěn 8. 10. 2026 – není členem CTC (id 16)
        ['1878', 'Longwood Cricket Club', 'Boston', 'první lawn-tenisový dvorec 1878', 0],
        ['1886', "The Queen's Club", 'Londýn', 'založen 1886', 0],
        ['1892', 'West Side Tennis Club', 'Forest Hills', 'založen 1892', 0],
        ['1893', 'I. Český Lawn-Tennis Klub Praha', 'Praha', 'založen 1893 · jediný český člen Centenary Tennis Clubs', 1],
        ['1893', 'Lawn Tennis de Monte-Carlo', '', 'otevřen 2. 4. 1893', 0],
        ['1897', 'LTTC Rot-Weiß Berlin', '', 'první rok klubové kroniky', 0],
        ['1899', 'Real Club de Tenis Barcelona-1899', '', 'rok v názvu klubu', 0],
        ['1906', 'Tennis Club Parioli', 'Řím', 'založen 1906 jako Lawn Tennis Club Parioli', 0],
    ] as [$rok, $nazev, $misto, $text, $my]) {
        $add(['typ' => 'rodokmen', 'nazev' => $nazev, 'rok' => $rok, 'misto' => $misto, 'text' => $text,
              'zdroj' => $zdroje[$rok . '|' . mb_substr($nazev, 0, 12)] ?? '', 'zvyraznit' => $my]);
    }

    /* doplněno 8. 10. 2026 podle připomínek klubu (stejné pořadí jako migrace 2026-10-08-ctc-postrehy.php):
       utkání CTC Senior Competition 2025 a kluby, které na Štvanici hrály v klubem pořádané CTC Senior Competition
       (Fitzwilliam LTC už v seznamu je) */
    $add(['typ' => 'utkani', 'nazev' => 'CTC Senior Competition 35+/45+ · Winners Group', 'rok' => '2025', 'misto' => 'TC Padova, Cumberland LTC',
          'text' => '30.–31. 8. 2025', 'zdroj' => 'Revue 02/2025']);
    foreach (CTC_KLUBY_SENIOR as [$nazev, $misto]) {
        $add(['typ' => 'klub', 'nazev' => $nazev, 'misto' => $misto, 'text' => 'CTC Senior Competition na Štvanici', 'zdroj' => 'I. ČLTK Praha, seznam klubů CTC Senior Competition (10/2026)']);
    }

    $poradi = [];
    foreach ($r as &$x) { $poradi[$x['typ']] = ($poradi[$x['typ']] ?? -1) + 1; $x['poradi'] = $poradi[$x['typ']]; }
    unset($x);
    // čísla řádků jako na serveru po migraci: vypuštěná id 11, 12, 16 zůstanou volná, nové řádky až za ostatními
    $id = 0;
    foreach ($r as &$x) { do { $id++; } while (in_array($id, [11, 12, 16], true)); $x['id'] = $id; }
    unset($x);
    seed_log('CTC: ' . seed_vloz('cltk_ctc', $r) . ' záznamů.');
}
