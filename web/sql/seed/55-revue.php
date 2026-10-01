<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* I.ČLTK Revue (41 čísel 2006–2026) a klubové newslettery (2015–2026, CS/EN).
   Zdroj: Varianta 4 → js/data-revue.js (opis obsah.json → revue), obálky
   assets/revue/, podklady/data/newslettery.json. */

/* ---------- Revue ---------- */
if (seed_prazdna('cltk_revue')) {
    $js = (string)@file_get_contents(seed_navrhy('04-varianta-4/js/data-revue.js'));
    $cisla = [];
    if (preg_match('/window\.CLTK_REVUE\s*=\s*(\[.*?\]);\s*\n/s', $js, $m)) {
        $cisla = json_decode($m[1], true) ?: [];
    }
    if (!$cisla) {
        // záloha: přímo z obsah.json
        foreach (seed_json('obsah.json')['revue'] ?? [] as $r) {
            $cisla[] = ['o' => $r['oznaceni'], 'rok' => $r['rok'], 'c' => $r['cislo'], 'obalka' => $r['na_obalce'] ?? '',
                        'titulky' => $r['titulky_obalky'] ?? [], 'obsah' => $r['obsah'] ?? [], 'stran' => $r['pocet_stran'] ?? null,
                        'naklad' => $r['naklad'] ?? '', 'uzaverka' => $r['uzaverka'] ?? '', 'pdf' => $r['pdf_url'] ?? '',
                        'mb' => $r['pdf_mb'] ?? '', 'img' => $r['cover_file'] ?? ''];
        }
    }
    $radky = [];
    foreach ($cisla as $i => $c) {
        $jmeno = 'revue-' . (int)$c['rok'] . '-' . (int)$c['c'];
        $obalka = seed_obrazek(seed_navrhy('assets/revue/' . $jmeno . '.jpg'), 'revue', $jmeno, 1200, 1200);
        $obsah = [];
        foreach ((array)($c['obsah'] ?? []) as $polozka) {
            if (is_array($polozka) && count($polozka) >= 2) $obsah[] = [(string)$polozka[0], (string)$polozka[1]];
        }
        $radky[] = [
            'rok' => (int)$c['rok'], 'cislo' => (int)$c['c'], 'oznaceni' => (string)$c['o'],
            'obalka' => $obalka, 'obalka_popis' => (string)($c['obalka'] ?? ''),
            // přepis obálek si chybu z tisku značil „[sic]“ – na web patří opravený text
            'titulky' => json_ulozit(array_values(array_map(static fn($t): string => str_replace('VITĚZKA [sic]', 'VÍTĚZKA', (string)$t),
                (array)($c['titulky'] ?? [])))),
            'obsah' => json_ulozit($obsah), 'stran' => isset($c['stran']) ? (int)$c['stran'] : null,
            'naklad' => (string)($c['naklad'] ?? ''), 'uzaverka' => (string)($c['uzaverka'] ?? ''),
            'pdf_url' => (string)($c['pdf'] ?? ''), 'pdf_soubor' => '',
            'pdf_mb' => isset($c['mb']) && $c['mb'] !== null && $c['mb'] !== '' ? str_replace('.', ',', (string)$c['mb']) : '',
            'visible' => 1, 'poradi' => $i,
        ];
    }
    seed_log('Revue: ' . seed_vloz('cltk_revue', $radky) . ' čísel.');
}

/* ---------- newslettery ---------- */
if (seed_prazdna('cltk_newslettery')) {
    $skupiny = [];
    foreach (seed_json('newslettery.json') as $n) {
        $klic = $n['rok'] . '|' . $n['cislo'];
        if (!isset($skupiny[$klic])) {
            $cislo = (string)$n['cislo'];
            $ciselne = preg_match('/^\d+$/', $cislo);
            $skupiny[$klic] = [
                'rok' => (int)$n['rok'], 'cislo' => $ciselne ? $cislo : '',
                'oznaceni' => $ciselne ? $cislo . '/' . $n['rok'] : $cislo . (str_contains($cislo, (string)$n['rok']) ? '' : ' ' . $n['rok']),
                'nazev' => str_starts_with($cislo, '130 let') ? 'Speciální vydání ke 130 letům klubu' : '',
                'pdf_cs' => '', 'pdf_en' => '', 'visible' => 1,
            ];
        }
        $pole = strtoupper((string)$n['jazyk']) === 'EN' ? 'pdf_en' : 'pdf_cs';
        $skupiny[$klic][$pole] = (string)$n['pdf_url'];
    }
    seed_log('Newslettery: ' . seed_vloz('cltk_newslettery', array_values($skupiny)) . ' vydání.');
}
