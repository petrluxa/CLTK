<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Dokumenty klubu – stránky webu v klubovém stylu (dokument.php?d=<slug>), NE PDF
   (rozhodnutí klienta 2. 10. 2026; tisk stránky PDF nahrazuje).
   Texty jsou v datovém souboru migrace sql/migrace/2026-10-02-dokumenty-archiv.data.json
   (seed_archiv_data()): převedené z PDF stanov, pravidel hraní a rezervací, ceníku, plánu
   a provozních řádů a ze stránky GDPR starého webu (podklady/dokumenty-texty/, kontrola
   doslovnosti). Na starý web cltk.cz se neodkazuje – vypne se.
   Patička podle PDF klienta: „Stanovy klubu“, „Pravidla hraní 2026“, „Osobní údaje členů“.
   Archiv klubových turnajů (PDF, kategorie „turnaje“) doplní sada 95-archiv-pdf. */

if (!seed_prazdna('cltk_dokumenty')) return;

$ted = ted();
$archiv = [];
foreach ((array)(seed_archiv_data()['dokumenty'] ?? []) as $d) $archiv[(string)$d['slug']] = $d;
if (!$archiv) seed_log('  ! dokumenty: datový soubor archivu chybí – dokumenty budou bez textu.');

$radky = [
    // adresa stránky, název, kategorie, popis, v patičce, text v patičce
    ['stanovy', 'Stanovy I. ČLTK Praha', 'klub', 'Úplné znění schválené Valnou hromadou 25. 6. 2025', 1, 'Stanovy klubu'],
    ['pravidla-hrani-2026', 'Pravidla hraní na tenisových kurtech 2026', 'provoz', 'Pravidla pro rezervace a hraní na kurtech klubu', 1, 'Pravidla hraní 2026'],
    ['osobni-udaje-clenu', 'Osobní údaje členů', 'clenstvi', 'Oznámení o zpracování osobních údajů pro členy I. ČLTK Praha', 1, 'Osobní údaje členů'],
    ['cenik-zima-2026-2027', 'Ceník zimní halové sezóny 2026/2027', 'cenik', 'Kurty v halách 28. 9. 2026 – 4. 4. 2027', 0, ''],
    ['plan-arealu', 'Plán areálu', 'provoz', 'Plán areálu na ostrově Štvanice, verze 09-2025', 0, ''],
    ['provozni-rad-bazen', 'Provozní řád – bazén', 'provoz', '', 0, ''],
    ['provozni-rad-posilovna', 'Provozní řád – posilovna (fitness)', 'provoz', 'Účinnost od 20. 6. 2017', 0, ''],
    ['provozni-rad-wellness', 'Provozní řád – wellness', 'provoz', 'Účinnost od 1. 2. 2019', 0, ''],
];

$vlozit = [];
foreach ($radky as $i => [$slug, $nazev, $kat, $popis, $paticka, $patickaText]) {
    $text = isset($archiv[$slug]) ? html_k_ulozeni((string)$archiv[$slug]['text']) : '';
    if ($text === '') continue;                              // dokument bez textu by nikam nevedl
    $vlozit[] = ['nazev' => $nazev, 'kategorie' => $kat, 'popis' => $popis, 'slug' => $slug, 'text' => $text,
                 'soubor' => '', 'soubor_nazev' => '', 'url' => '', 'v_paticce' => $paticka, 'paticka_text' => $patickaText,
                 'visible' => 1, 'poradi' => $i, 'created_at' => $ted, 'updated_at' => $ted];
}
seed_log('Dokumenty: ' . seed_vloz('cltk_dokumenty', $vlozit) . ' (stránky s textem, bez PDF).');
