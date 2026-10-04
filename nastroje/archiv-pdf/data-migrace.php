<?php
/* Sestaví datový soubor migrace web/sql/migrace/2026-10-02-dokumenty-archiv.data.json
   z výstupů předchozích kroků – jen lokálně z příkazové řádky:

     CLTK_DB_FILE=web/data/server-stav.sqlite ./_php/php.exe nastroje/archiv-pdf/data-migrace.php

   (databáze ve stavu serveru – z ní se čtou dnešní hodnoty pramenů cltk_milniky / cltk_ctc;
    když už žádný pramen s cltk.cz nemá, převezmou se z dosavadního datového souboru)

   Vstupy:
     podklady/data/mapa-souboru.json         – stará adresa (files.cltk.cz) → soubor v uploads/ (Revue, newslettery…)
     podklady/data/prilohy-nove.json         – další přílohy: jubilejní Revue 1893–2023, archiv turnajů, rozvrhy
     podklady/dokumenty-texty/dokumenty.json + *.html + stanovy-znak.jpg – texty dokumentů
     podklady/dokumenty-texty/rozvrhy.json   – rozvrhy Tenisové školy (bez jmen dětí)
     C:/Users/Asus/cltk-navrhy/podklady/data/clanky.json – názvy a data starých článků (prameny)
   Výstup: data.json migrace + obrázky do textů dokumentů v web/uploads/dokumenty/
   (stanovy-znak.jpg, plan-arealu.jpg). Texty se uloží už vyčištěné přes html_k_ulozeni().

   Klient 2. 10. 2026: dokumenty klubu (stanovy, pravidla, řády, ceník, plán) NEJSOU PDF –
   jsou to stránky webu (dokument.php). PDF zůstávají jen u Revue, newsletterů a archivu turnajů. */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../../web/inc/functions.php';

$koren = dirname(__DIR__, 2);
$cil = $koren . '/web/sql/migrace/2026-10-02-dokumenty-archiv.data.json';
$cti = static function (string $rel) use ($koren): array {
    $d = json_decode((string)@file_get_contents($koren . '/' . $rel), true);
    if (!is_array($d)) { fwrite(STDERR, "Vstup $rel chybí nebo je poškozený.\n"); exit(1); }
    return $d;
};
$mapa = $cti('podklady/data/mapa-souboru.json');
$prilohy = $cti('podklady/data/prilohy-nove.json');
$doky = $cti('podklady/dokumenty-texty/dokumenty.json');
$rozvrhyZdroj = $cti('podklady/dokumenty-texty/rozvrhy.json');
$clankyCesta = (getenv('CLTK_NAVRHY') ?: dirname($koren, 3) . '/cltk-navrhy') . '/podklady/data/clanky.json';
$clanky = is_file($clankyCesta) ? (json_decode((string)file_get_contents($clankyCesta), true)['articles'] ?? []) : [];

$chyby = 0;
$soubory = [];
$pridej = static function (string $rel) use (&$soubory, &$chyby): void {
    $p = UPLOAD_DIR . '/' . $rel;
    if (!is_file($p)) { fwrite(STDERR, "CHYBÍ uploads/$rel\n"); $chyby++; return; }
    $soubory[$rel] = filesize($p);
};

/* ---------- obrázky do textů dokumentů (vlastní kopie – blok areal/plan si fotku může vyměnit) ---------- */
$obrazky = [
    'dokumenty/stanovy-znak.jpg' => $koren . '/podklady/dokumenty-texty/stanovy-znak.jpg',
    'dokumenty/plan-arealu.jpg'  => UPLOAD_DIR . '/bloky/plan-arealu.jpg',
];
foreach ($obrazky as $rel => $zdroj) {
    if (!is_file($zdroj)) { fwrite(STDERR, "CHYBÍ zdroj obrázku $zdroj\n"); $chyby++; continue; }
    if (!is_file(UPLOAD_DIR . '/' . $rel) || filesize(UPLOAD_DIR . '/' . $rel) !== filesize($zdroj)) {
        @mkdir(dirname(UPLOAD_DIR . '/' . $rel), 0775, true);
        copy($zdroj, UPLOAD_DIR . '/' . $rel);
        echo "zkopírováno uploads/$rel\n";
    }
    $pridej($rel);
}

/* ---------- PDF Revue a newsletterů (dokumenty a ceník PDF nemají) ---------- */
$revue = $newslettery = [];
$staraUrlDokumentu = [];
$odkazy = [];
foreach ($mapa as $m) {
    $t = (string)$m['tabulka']; $s = (string)$m['sloupec'];
    if ($t === 'cltk_revue' && $s === 'pdf_url') {
        $pridej((string)$m['nova_cesta']);
        $revue[$m['stara_url']] = ['stara_url' => $m['stara_url'], 'soubor' => $m['nova_cesta'],
            'mb' => number_format((int)$m['velikost_nova'] / 1048576, 1, ',', '')];
    } elseif ($t === 'cltk_newslettery') {
        $pridej((string)$m['nova_cesta']);
        $j = $s === 'pdf_en' ? 'en' : 'cs';
        $newslettery[$j . '|' . $m['stara_url']] = ['jazyk' => $j, 'stara_url' => $m['stara_url'], 'soubor' => $m['nova_cesta']];
    } elseif ($t === 'cltk_dokumenty') {
        $staraUrlDokumentu[(int)$m['id']] = (string)$m['stara_url'];
    } elseif ($t === 'cltk_price_lists' && $s === 'pdf_url') {
        // ceník k vytištění = stránka dokumentu, ne PDF
        $odkazy[] = ['tabulka' => $t, 'sloupec' => $s, 'stara' => $m['stara_url'], 'nova' => 'dokument.php?d=cenik-zima-2026-2027'];
    } elseif ($t === 'cltk_bloky' && $s === 'odkaz') {
        // blok areal/plan: plán k vytištění = stránka dokumentu „Plán areálu“
        $odkazy[] = ['tabulka' => $t, 'sloupec' => $s, 'stara' => $m['stara_url'], 'nova' => 'dokument.php?d=plan-arealu'];
    }
}

/* ---------- texty dokumentů ---------- */
$vlozit = [
    // za větu „e) Symbolem klubu je níže vyobrazený znak“ patří obrázek znaku z PDF stanov
    'stanovy' => ['<p>e) Symbolem klubu je níže vyobrazený znak</p>',
                  '<p>e) Symbolem klubu je níže vyobrazený znak</p>' . "\n" . '<figure><img src="uploads/dokumenty/stanovy-znak.jpg" alt="Znak I. ČLTK Praha"></figure>'],
];
$dokumenty = [];
foreach ($doky as $d) {
    $html = (string)file_get_contents($koren . '/podklady/dokumenty-texty/' . $d['html_file']);
    if (isset($vlozit[$d['slug']])) {
        [$hledat, $nahradit] = $vlozit[$d['slug']];
        if (substr_count($html, $hledat) !== 1) { fwrite(STDERR, "Ve stanovách se nenašlo místo pro znak.\n"); $chyby++; }
        $html = str_replace($hledat, $nahradit, $html);
    }
    if ($d['slug'] === 'plan-arealu') {
        // PDF je jen obrázek – stránka ukáže plán (stejný obrázek jako na areal.php, vlastní kopie)
        // a pod ním popisky z plánu jako textovou alternativu
        $html = '<figure><img src="uploads/dokumenty/plan-arealu.jpg" alt="Plán areálu I. ČLTK Praha na ostrově Štvanice">'
              . '<figcaption>Plán areálu I. ČLTK Praha, verze 09-2025</figcaption></figure>' . "\n"
              . '<h3>Popisky v plánu</h3>' . "\n" . $html;
    }
    $text = html_k_ulozeni($html);
    if (strlen($text) > 60000) { fwrite(STDERR, "Text {$d['slug']} je delší než 60 000 bajtů.\n"); $chyby++; }
    $stara = $staraUrlDokumentu[(int)$d['id']] ?? (string)$d['zdroj_url'];
    if ($stara !== $d['zdroj_url']) { fwrite(STDERR, "Dokument {$d['id']}: adresa v mapě a v dokumenty.json nesedí.\n"); $chyby++; }
    $radek = ['id' => (int)$d['id'], 'nazev' => $d['nazev'], 'slug' => $d['slug'], 'stara_url' => $stara, 'text' => $text];
    if ((int)$d['id'] === 1) {
        // popis v DB byl datum vytvoření PDF; stanovy samy uvádějí schválení Valnou hromadou 25. 6. 2025 (čl. 10.1)
        $radek['popis_stary'] = 'Stanovy spolku ve znění z 6. 7. 2025';
        $radek['popis_novy'] = 'Úplné znění schválené Valnou hromadou 25. 6. 2025';
    }
    // patička: odkaz vede na stránku dokumentu – „(PDF)“ z textu pryč
    $paticka = [1 => ['Stanovy klubu (PDF)', 'Stanovy klubu'], 2 => ['Pravidla hraní 2026 (PDF)', 'Pravidla hraní 2026']];
    if (isset($paticka[(int)$d['id']])) {
        [$radek['paticka_stary'], $radek['paticka_novy']] = $paticka[(int)$d['id']];
    }
    $dokumenty[] = $radek;
}

/* ---------- jubilejní Revue 1893–2023 (speciální číslo = cislo 0) a archiv turnajů ---------- */
$special = null;
$archiv = [];
$druhPoradi = ['pozvanka' => 0, 'rozlosovani' => 1, 'tabulka' => 2, 'tabulky' => 2, 'vysledky' => 3];
foreach ($prilohy as $p) {
    if ($p['typ'] === 'revue-special') {
        $n = $p['navrh_zaznamu_revue'];
        $pridej((string)$n['pdf_soubor']);
        $pridej((string)$n['obalka']);
        if (!empty($p['obalka']['webp'])) $pridej((string)$p['obalka']['webp']);
        $special = [
            'stara_url' => $p['stara_url_ke_stazeni'],
            'rok' => (int)$n['rok'], 'cislo' => 0, 'oznaceni' => (string)$n['oznaceni'],
            'obalka' => (string)$n['obalka'], 'obalka_popis' => (string)$n['obalka_popis'],
            'titulky' => array_values((array)$n['titulky']), 'obsah' => array_values((array)$n['obsah']),
            'stran' => (int)$n['stran'], 'naklad' => (string)$n['naklad'], 'uzaverka' => '',
            'pdf_soubor' => (string)$n['pdf_soubor'], 'pdf_mb' => (string)$n['pdf_mb'],
        ];
    } elseif ($p['typ'] === 'turnaj') {
        $rel = (string)$p['nova_cesta'];
        $pridej($rel);
        $druh = (string)preg_replace('~^.*-(pozvanka|rozlosovani|tabulka|tabulky-skupin|vysledky|vysledky-debla)\.pdf$~', '$1', $rel);
        $druh = explode('-', $druh)[0];
        $puvodni = rawurldecode(basename((string)parse_url((string)$p['stara_url_ke_stazeni'], PHP_URL_PATH)));
        $archiv[] = [
            'nazev' => (string)$p['nazev'], 'skupina' => (string)$p['turnaj'], 'rok' => (int)$p['rok'],
            'soubor' => $rel, 'soubor_nazev' => mb_substr($puvodni, 0, 160), 'stran' => (int)$p['stran'],
            'poradi' => $druhPoradi[$druh] ?? 5, 'stara_url' => (string)$p['stara_url_ke_stazeni'],
        ];
        $kontrola = pdf_pocet_stran(UPLOAD_DIR . '/' . $rel);
        if ($kontrola !== null && $kontrola !== (int)$p['stran']) { fwrite(STDERR, "Počet stran $rel: v podkladech {$p['stran']}, pdf_pocet_stran() $kontrola\n"); }
    }
}
if (!$special) { fwrite(STDERR, "V prilohy-nove.json chybí jubilejní Revue.\n"); $chyby++; }
usort($archiv, static fn($a, $b) => [$b['rok'], $a['skupina'], $a['poradi'], $a['soubor']] <=> [$a['rok'], $b['skupina'], $b['poradi'], $b['soubor']]);

/* ---------- rozvrhy Tenisové školy: buňka mřížky = řádek cltk_skola (typ rozvrh) ---------- */
$nazvyRozvrhu = ['zima-2026-27' => 'Zima 2026/27', 'tyden-2026-09-29' => 'Týden 29. 9. – 2. 10. 2026'];
$rozvrhy = [];
foreach ((array)$rozvrhyZdroj['rozvrhy'] as $rz) {
    $nazev = $nazvyRozvrhu[(string)$rz['slug']] ?? (string)$rz['nazev'];
    $radky = [];
    foreach ((array)$rz['radky'] as $r) {
        if (!in_array($r['stav'], ['trénink', 'obsazeno'], true)) continue;   // volno = prázdná buňka, neukládá se
        $kurt = trim((string)$r['kurt']) . ((string)$r['povrch'] !== '' ? ' · ' . $r['povrch'] : '');
        $radky[] = [
            'den' => mb_strtolower((string)$r['den']), 'cas' => $r['cas_od'] . '–' . $r['cas_do'], 'misto' => $kurt,
            'skupina' => $r['stav'] === 'obsazeno' ? 'obsazeno' : trim((string)($r['skupina'] ?? '')),
            // „3 trenéři“ = žlutá buňka PDF (legenda); jinak bez údaje
            'trener' => $r['stav'] === 'trénink' && (int)($r['treneru'] ?? 0) === 3 ? '3 trenéři' : '',
            'text' => trim((string)($r['poznamka'] ?? '')),
        ];
    }
    // pořadí jako v PDF: kurt, den, čas, horní/dolní polovina
    $rozvrhy[] = ['nazev' => $nazev, 'datum_od' => (string)$rz['platnost_od'], 'datum_do' => (string)$rz['platnost_do'], 'radky' => $radky];
}

/* ---------- bloky: texty, které mluvily o PDF, a texty stránky rozvrhů ---------- */
$infoRozvrhy = '<h3>Důležité informace k tréninkům dětí v halách</h3>'
    . '<ul><li>Prosíme rodiče a doprovod, aby se z organizačních, pedagogicko-psychologických a hygienických důvodů během tréninků zdržovali mimo halu.</li>'
    . '<li>Vstup dětí do haly nejdříve 5 minut před zahájením vlastního tréninku.</li>'
    . '<li>Absence a omluvy z tréninků hlaste přímo trenérce či trenérovi (kontakty níže).</li></ul>'
    . '<h3>Kontaktní trenéři kurtů</h3>';
foreach ((array)($rozvrhyZdroj['rozvrhy'][0]['kurty'] ?? []) as $k) {
    $lide = array_map(static fn($t) => e((string)$t['jmeno']) . ', <a href="' . e(tel_href((string)$t['telefon'])) . '">' . e((string)$t['telefon']) . '</a>', (array)$k['kontaktni_treneri']);
    $infoRozvrhy .= '<p><strong>' . e((string)$k['kurt']) . ' (' . e((string)$k['povrch']) . '):</strong> ' . implode(' · ', $lide) . '</p>';
}
$bloky = [
    ['stranka' => 'kontakt', 'klic' => 'dokumenty', 'zmeny' => ['nadpis' => ['Dokumenty ke <em>stažení</em>', 'Dokumenty <em>klubu</em>']]],
    ['stranka' => 'vedeni', 'klic' => 'dokumenty', 'zmeny' => ['perex' => ['Stanovy klubu a další dokumenty jsou ke stažení v PDF.',
        'Stanovy klubu a další dokumenty si přečtete přímo na webu – každý jde i vytisknout.']]],
    ['stranka' => 'areal', 'klic' => 'plan', 'zmeny' => ['odkaz_text' => ['Plán areálu v PDF', 'Plán areálu k vytištění']]],
    ['stranka' => 'revue', 'klic' => 'uvod', 'zmeny' => ['perex' => [
        '41 čísel klubového časopisu od roku 2006, dvakrát ročně. Texty Jiljí Kubec, grafika Kateřina Kuželová, hlavní fotograf Martin Sidorják. Čísla 01/2016 a 02/2016 v archivu PDF chybí.',
        '41 čísel klubového časopisu od roku 2006, dvakrát ročně, a jubilejní speciál 1893–2023. Texty Jiljí Kubec, grafika Kateřina Kuželová, hlavní fotograf Martin Sidorják. Čísla 01/2016 a 02/2016 v archivu PDF chybí.']]],
    ['stranka' => 'tenisova-skola-rozvrhy', 'klic' => 'rozvrhy', 'zmeny' => [
        'nadpis' => ['Zimní rozvrhy skupin', 'Rozvrhy <em>tréninků</em>'],
        'perex' => ['Rozvrhy jednotlivých skupin doplní klub.', 'Týdenní rozvrh Tenisové školy Markéty Vondroušové po kurtech a hodinách. Jména dětí na webu nejsou. Změny vyhrazeny.'],
        'text' => ['', html_k_ulozeni($infoRozvrhy)],
        'doplni_klub' => [1, 0]]],
];
/* nové bloky stránky dokumenty.php (šablona má stejné výchozí texty – bloky je jen zpřístupní v modulu Stránky) */
$blokyNove = [
    ['stranka' => 'dokumenty', 'klic' => 'uvod', 'poradi' => 0, 'stitek' => 'Dokumenty', 'nadpis' => 'Dokumenty <em>klubu</em>.',
     'perex' => 'Stanovy, pravidla hraní a provozní řády areálu přímo na webu – každý dokument si můžete i vytisknout. Níže je archiv klubových turnajů a jubilejní Revue.'],
    ['stranka' => 'dokumenty', 'klic' => 'pravidla', 'poradi' => 1, 'nadpis' => 'Pravidla a provozní <em>řády</em>',
     'perex' => 'Pravidla hraní a rezervací kurtů, provozní řády bazénu, posilovny a wellness a plán areálu.'],
    ['stranka' => 'dokumenty', 'klic' => 'klub', 'poradi' => 2, 'nadpis' => 'Klub a <em>spolek</em>',
     'perex' => 'Stanovy spolku a oznámení o zpracování osobních údajů členů.'],
    ['stranka' => 'dokumenty', 'klic' => 'ceniky', 'poradi' => 3, 'nadpis' => 'Ceníky',
     'perex' => 'Ceník k vytištění. Aktuální ceny kurtů v létě i v zimě jsou na stránce Ceník kurtů.'],
    ['stranka' => 'dokumenty', 'klic' => 'archiv', 'poradi' => 4, 'stitek' => 'Archiv', 'nadpis' => 'Archiv klubových <em>turnajů</em>',
     'perex' => 'Pozvánky, rozlosování a výsledky klubových turnajů a akcí. Soubory PDF, jak je klub vydal.'],
    ['stranka' => 'dokumenty', 'klic' => 'revue', 'poradi' => 5, 'stitek' => 'I.ČLTK Revue', 'nadpis' => 'Kompletní historie v jubilejní <em>Revue</em>',
     'perex' => 'Speciální číslo I.ČLTK Revue ke 130. výročí klubu – dějiny klubu od roku 1893, prezidenti, legendy Štvanice.'],
];

/* ---------- prameny: staré stránky a články cltk.cz → prostý text, PDF → soubor na webu ---------- */
$naRok = [];
foreach ($clanky as $c) $naRok[(string)$c['url']] = $c;
$nazvyStranek = [
    'https://cltk.cz/cs/klub/ctc/' => 'I. ČLTK Praha, stránka „CTC – Centenary Tennis Clubs“',
    'https://cltk.cz/cs/klub/klubove-turnaje-a-akce:51/oslavy-125-let-klubu/' => 'I. ČLTK Praha, stránka „Oslavy 125 let klubu“',
    'https://cltk.cz/cs/klub/klubove-turnaje-a-akce:51/oslavy-130-let-klubu-2272023/' => 'I. ČLTK Praha, stránka „Oslavy 130 let klubu 22. 7. 2023“',
];
// názvy článků, které mají na starém webu verzálky nebo překlep – na web patří normální podoba
$nazvyClanku = [
    'https://cltk.cz/cs/clanky/ing-petr-simunek-byl-zvolen-novym-prezidentem-klubu:231/' => 'Ing. Petr Šimůnek byl zvolen novým prezidentem klubu',
];
$pdfMapa = [];
foreach ($mapa as $m) $pdfMapa[(string)$m['stara_url']] = (string)$m['nova_cesta'];

$prevedPramen = static function (string $zdroj) use ($naRok, $nazvyStranek, $nazvyClanku, $pdfMapa, &$chyby): string {
    $v = (string)preg_replace_callback('~https?://(?:www\.)?(?:files\.)?cltk\.cz/[^\s;()]*(?:\([^\s;()]*\)[^\s;()]*)*~u', static function (array $x) use ($naRok, $nazvyStranek, $nazvyClanku, $pdfMapa, &$chyby): string {
        $u = rtrim($x[0], '.,');
        if (isset($pdfMapa[$u])) return 'uploads/' . $pdfMapa[$u];
        if (isset($nazvyStranek[$u])) return $nazvyStranek[$u];
        if (isset($naRok[$u])) {
            $nazev = $nazvyClanku[$u] ?? trim((string)$naRok[$u]['title']);
            return 'I. ČLTK Praha, článek „' . $nazev . '“ (' . substr((string)$naRok[$u]['date'], 0, 4) . ')';
        }
        fwrite(STDERR, "Neznámá adresa v pramenu: $u\n");
        $chyby++;
        return $u;
    }, $zdroj);
    return trim((string)preg_replace(['~\s+;~u', '~;(?=\S)~u', '~\s{2,}~u'], [';', '; ', ' '], $v));
};

$prameny = [];
foreach (['cltk_milniky', 'cltk_ctc'] as $tab) {
    foreach (rows('SELECT id, zdroj FROM ' . $tab . " WHERE zdroj LIKE '%cltk.cz%' ORDER BY id") as $r) {
        $novy = $prevedPramen((string)$r['zdroj']);
        if (preg_match('~https?://[^\s]*cltk\.cz~i', $novy)) { fwrite(STDERR, "$tab {$r['id']}: v pramenu zůstala adresa cltk.cz\n"); $chyby++; }
        $prameny[] = ['tabulka' => $tab, 'id' => (int)$r['id'], 'stary' => (string)$r['zdroj'], 'novy' => $novy];
    }
}
if (!$prameny && is_file($cil)) {
    // databáze už je převedená (po migraci / novém seedu) – prameny se vezmou z dosavadního datového souboru
    $prameny = (array)(json_decode((string)file_get_contents($cil), true)['prameny'] ?? []);
    echo "V databázi už žádný pramen s cltk.cz není – prameny ponechány z dosavadního datového souboru.\n";
}
foreach ($prameny as $p) {
    if (preg_match_all('~uploads/([a-z0-9\-_/]+\.pdf)~i', (string)$p['novy'], $m)) foreach ($m[1] as $rel) $pridej($rel);
}
ksort($soubory);

$data = [
    'migrace' => '2026-10-02-dokumenty-archiv',
    'popis' => 'Web bez odkazů na starý web cltk.cz / files.cltk.cz: dokumenty klubu jako stránky webu (ne PDF), Revue a newslettery jako PDF na webu, archiv klubových turnajů, jubilejní Revue 1893–2023, rozvrhy Tenisové školy, prameny bez odkazů na staré články.',
    'vytvoreno' => date('Y-m-d H:i:s'),
    'soubory' => $soubory,
    'revue' => array_values($revue),
    'newslettery' => array_values($newslettery),
    'dokumenty' => $dokumenty,
    'odkazy' => $odkazy,
    'bloky' => $bloky,
    'bloky_nove' => $blokyNove,
    'prameny' => $prameny,
    'revue_special' => $special,
    'archiv_turnaju' => $archiv,
    'rozvrhy' => $rozvrhy,
];
file_put_contents($cil, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n");
printf("Hotovo: %s\n  souborů %d (%s), Revue %d + speciál, newsletterů %d, dokumentů %d, odkazů %d, bloků %d + %d nových, pramenů %d,\n  archiv turnajů %d PDF, rozvrhy %s\n",
    str_replace('\\', '/', $cil), count($soubory), velikost_text(array_sum($soubory)), count($revue), count($newslettery),
    count($dokumenty), count($odkazy), count($bloky), count($blokyNove), count($prameny), count($archiv),
    implode(', ', array_map(static fn($r) => $r['nazev'] . ' (' . count($r['radky']) . ' hodin)', $rozvrhy)));
if ($chyby) { fwrite(STDERR, "$chyby chyb – data nejsou v pořádku.\n"); exit(1); }
