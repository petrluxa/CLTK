<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Ceníky: kurty léto 2026, kurty zima 2026/27, členství 2026, tenisová škola,
   letní kempy 2026, doplňkové služby. Zdroj: obsah.json → cenik, clenstvi. */

if (!seed_prazdna('cltk_price_lists')) return;

$o = seed_json('obsah.json');
$cenik = $o['cenik'] ?? [];
$clen = $o['clenstvi'] ?? [];
$ted = ted();

/** Založí ceník se sekcemi; $sekce = [[nazev, popis, [hlavičky], [[nazev, poznamka, cena, cena_clen, sezona, sezona_clen], …]], …] */
$zaloz = static function (string $klic, string $nazev, string $podnazev, string $obdobi, string $nahore, string $dole, string $pdf, array $sekce, int $poradi) use ($ted): void {
    $listId = db_insert('cltk_price_lists', [
        'klic' => $klic, 'nazev' => $nazev, 'podnazev' => $podnazev, 'obdobi' => $obdobi,
        'poznamka_nahore' => $nahore, 'poznamka_dole' => $dole, 'pdf_url' => $pdf,
        'visible' => 1, 'poradi' => $poradi, 'updated_at' => $ted,
    ]);
    foreach ($sekce as $si => [$sn, $sp, $hl, $radky]) {
        $sid = db_insert('cltk_price_sections', [
            'list_id' => $listId, 'nazev' => $sn, 'popis' => $sp,
            'hl_nazev' => $hl[0] ?? '', 'hl_cena' => $hl[1] ?? '', 'hl_cena_clen' => $hl[2] ?? '',
            'hl_cena_sezona' => $hl[3] ?? '', 'hl_cena_sezona_clen' => $hl[4] ?? '', 'poradi' => $si,
        ]);
        foreach ($radky as $ri => $r) {
            db_insert('cltk_price_rows', [
                'section_id' => $sid, 'nazev' => $r[0], 'poznamka' => $r[1] ?? '', 'cena' => $r[2] ?? '',
                'cena_clen' => $r[3] ?? '', 'cena_sezona' => $r[4] ?? '', 'cena_sezona_clen' => $r[5] ?? '', 'poradi' => $ri,
            ]);
        }
    }
};
$kc = static fn($n) => number_format((int)$n, 0, ',', ' ') . ' Kč';

/* --- kurty léto 2026 --- */
$zaloz('kurty-leto', 'Kurty – letní sezóna 2026', 'Cena za hodinu, celý den včetně víkendů', '', '',
    'Členové klubu hrají v letní sezóně na venkovních kurtech zdarma.', '', [
    ['Venkovní kurty a pevná hala', '', ['Kurty', 'Veřejnost', 'Člen'], [
        ['Kurty v hlavním areálu, č. 1–9', 'celý den včetně víkendů', '500 Kč/hod', 'zdarma'],
        ['Kurty na „Slavoji“, č. 10–16', 'celý den včetně víkendů', '400 Kč/hod', 'zdarma'],
        ['Pevná hala, kurty P1, P2', 'celý den včetně víkendů', '600 Kč/hod', '500 Kč/hod'],
        ['Večerní svícení, kurty 2, 3, 4', '', '150 Kč/hod', ''],
    ]],
], 0);

/* --- kurty zima 2026/27 --- */
$sekceZima = [];
foreach (($cenik['zima_2026_27']['haly'] ?? []) as $hala) {
    $nazevHaly = (string)$hala['hala'];
    $popis = '';
    if (preg_match('/^(.*?)\s*\((kurty [^)]+)\)\s*$/u', $nazevHaly, $m)) { $nazevHaly = $m[1]; $popis = $m[2]; }
    $nazevHaly = str_replace(' – ', ' · ', $nazevHaly);
    $popis = trim($popis . ' · ' . $hala['obdobi'] . ' · ' . (int)$hala['tydnu'] . ' ' . sklonuj((int)$hala['tydnu'], 'týden', 'týdny', 'týdnů'), ' ·');
    $radky = [];
    foreach ($hala['pasma'] as $p) {
        $radky[] = [$p['cas'], '', $kc($p['hodina_verejnost']), $kc($p['hodina_clen']),
                    $kc($p['sezona_verejnost']) . ' (' . (int)$p['sezona_verejnost_za_hod'] . ' Kč/hod)',
                    $kc($p['sezona_clen']) . ' (' . (int)$p['sezona_clen_za_hod'] . ' Kč/hod)'];
    }
    $sekceZima[] = [$nazevHaly, $popis, ['Čas', 'Veřejnost / hod', 'Člen / hod', 'Předplatné – veřejnost', 'Předplatné – člen'], $radky];
}
$zaloz('kurty-zima', 'Kurty – zimní sezóna 2026/27', 'Cena za hodinu a předplatné jedné hodiny týdně na celé období',
    '28. 9. 2026 – 4. 4. 2027',
    'Pevná hala od 28. 9. 2026, přetlakové haly od 5. 10. 2026 do 4. 4. 2027. V zimě je k dispozici 12 krytých kurtů.',
    implode("\n\n", array_merge($cenik['zima_2026_27']['poznamky'] ?? [], $cenik['pravidla_trvalych_rezervaci'] ?? [])),
    'dokument.php?d=cenik-zima-2026-2027', $sekceZima, 1);              // ceník k vytištění = stránka dokumentu (sada 70-dokumenty)

/* --- členství 2026 --- */
$hl = ['Druh členství', 'Roční příspěvek'];
$zaloz('clenstvi', 'Členství 2026', 'Roční členské příspěvky', '2026',
    (string)($clen['uvod'] ?? ''),
    trim(($clen['hrajici_poznamka'] ?? '') . "\n\n" . 'Dítě = 4–18 let nebo studující. Cena za pronájem venkovního dvorce pro nehrajícího člena je stejná jako pro veřejnost.'),
    '', [
    ['Hrající – jednotlivci', '', $hl, array_map(fn($r) => [mb_strtoupper(mb_substr($r['typ'], 0, 1)) . mb_substr($r['typ'], 1), '', $kc($r['cena'])], $clen['hrajici'] ?? [])],
    ['Rodinné', '', $hl, array_map(fn($r) => [$r['typ'], '', $kc($r['cena'])], $clen['rodinne'] ?? [])],
    ['Nehrající a firemní', '', $hl, array_map(fn($r) => [mb_strtoupper(mb_substr($r['typ'], 0, 1)) . mb_substr($r['typ'], 1) . ' členství', $r['podminka'], $kc($r['cena'])], $clen['ostatni'] ?? [])],
    ['Hosté členů', '', ['Služba', 'Cena'], array_map(fn($r) => [mb_strtoupper(mb_substr($r['polozka'], 0, 1)) . mb_substr($r['polozka'], 1), '', $r['cena'] . ' ' . $r['jednotka']], $clen['hoste'] ?? [])],
], 2);

/* --- tenisová škola --- */
$ts = $cenik['tenisova_skola'] ?? [];
$hlTs = ['Trénink', 'Cena'];
$zaloz('skola', 'Tenisová škola – ceník', 'Cena zahrnuje trenéra i pronájem kurtu', '',
    'Tréninky jsou evidovány v přehledech trenérů a v informačním systému klubu. Cena zahrnuje trenéra i pronájem kurtu.',
    'Základní členství Tenisové školy 1 000 Kč (hradí se vždy na začátku roku).', '', [
    ['Zimní období 2026/27', ($ts['zima_2026_27']['obdobi'] ?? '') . ' · tréninkové volno ' . ($ts['zima_2026_27']['volno'] ?? ''), $hlTs,
     array_map(fn($r) => [mb_strtoupper(mb_substr($r['trenink'], 0, 1)) . mb_substr($r['trenink'], 1), '', $r['cena']], $ts['zima_2026_27']['polozky'] ?? [])],
    ['Letní období 2026', ($ts['leto_2026']['obdobi'] ?? '') . ' · tréninkové volno 1. 5. a 8. 5. 2026', $hlTs,
     array_map(fn($r) => [mb_strtoupper(mb_substr($r['trenink'], 0, 1)) . mb_substr($r['trenink'], 1), '', $r['cena']], $ts['leto_2026']['polozky'] ?? [])],
    ['Členství v Tenisové škole', '', $hlTs, [['Základní členství Tenisové školy', 'hradí se vždy na začátku roku', '1 000 Kč']]],
], 3);

/* --- letní kempy 2026 --- */
$lk = $cenik['letni_kempy_2026'] ?? [];
$zaloz('kempy', 'Letní kempy 2026', 'Pětidenní kempy pro děti zhruba od 4 do 9 let', '29. 6. – 28. 8. 2026',
    'Pro děti od cca 4 do 9 let (minitenis, střední kurt, babytenis) s minimálně jednoroční pravidelnou tenisovou přípravou; úplní začátečníci po předchozí domluvě.',
    'V ceně je oběd a pitný režim. Platba až po potvrzení e-mailem, převodem na účet 312451935/0300.', '', [
    ['Varianty kempu', '', ['Varianta', 'Ostatní', 'Hráči I. ČLTK Praha'],
     array_map(fn($v) => ['Varianta ' . $v['nazev'] . ' · ' . $v['cas'], $v['obsah'], $v['cena_ostatni'], $v['cena_hrac_icltk']], $lk['varianty'] ?? [])],
], 4);

/* --- doplňkové služby --- */
$zaloz('doplnkove', 'Doplňkové služby', '', '', '',
    'Beachvolejbalové a multifunkční hřiště je pro členy zdarma, pokud hrají jen členové; s hosty je pronájem o 25 % levnější.', '', [
    ['Hřiště a vstupy', '', ['Služba', 'Cena'], array_map(fn($r) => [$r['polozka'], '', $r['cena']], $cenik['doplnkove_sluzby'] ?? [])],
], 5);

seed_log('Ceníky: ' . (int)val('SELECT COUNT(*) FROM cltk_price_lists') . ' listů, '
       . (int)val('SELECT COUNT(*) FROM cltk_price_rows') . ' řádků.');
