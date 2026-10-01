<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Dokumenty ke stažení. Zdroj: obsah.json → klub.dokumenty (odkazy na files.cltk.cz,
   ověřené 23. 9. 2026) a klub.gdpr. Patička podle PDF klienta: „Stanovy klubu (PDF)“,
   „Pravidla hraní 2026 (PDF)“, „Osobní údaje členů“ (v_paticce = 1).
   PDF zatím nejsou nahraná – vedou odkazem na dnešní úložiště klubu. Klub je může
   v administraci nahradit vlastním souborem (nahrané PDF má přednost před odkazem).

   POZOR: „Osobní údaje členů“ vede zatím na stránku dnešního webu cltk.cz/cs/gdpr/.
   Po přestěhování nového webu na cltk.cz ta adresa zmizí – klub musí dodat PDF. */

if (!seed_prazdna('cltk_dokumenty')) return;

$ted = ted();
$dok = seed_json('obsah.json')['klub']['dokumenty'] ?? [];
$url = [];
foreach ($dok as $d) $url[(string)$d['nazev']] = (string)$d['url'];
$gdpr = seed_json('obsah.json')['klub']['gdpr']['url'] ?? 'https://cltk.cz/cs/gdpr/';

$radky = [
    // název, kategorie, popis, odkaz, v patičce, text v patičce
    ['Stanovy I. ČLTK Praha', 'klub', 'Stanovy spolku ve znění z 6. 7. 2025',
     $url['Stanovy I.ČLTK Praha'] ?? 'https://files.cltk.cz/szt6azj7muh01/Stanovy%20I.%C4%8CLTK%20Praha.pdf', 1, 'Stanovy klubu (PDF)'],
    ['Pravidla hraní na tenisových kurtech 2026', 'provoz', 'Pravidla pro rezervace a hraní na kurtech klubu',
     $url['Pravidla hraní na tenisových kurtech 2026'] ?? 'https://files.cltk.cz/7km6d916kbl01/Pravidla%20pro%20rezervace%20kurt%C5%AF%202026.pdf', 1, 'Pravidla hraní 2026 (PDF)'],
    ['Osobní údaje členů', 'clenstvi', 'Oznámení o zpracování osobních údajů pro členy I. ČLTK Praha',
     $gdpr, 1, 'Osobní údaje členů'],
    ['Ceník zimní halové sezóny 2026/2027', 'cenik', 'Kurty v halách 28. 9. 2026 – 4. 4. 2027',
     $url['Ceník zimní halové sezóny 2026/2027'] ?? 'https://files.cltk.cz/9lawqgo4cjn01/Cen%C3%ADk%20zimn%C3%AD%20sezona%2026-27.pdf', 0, ''],
    ['Plán areálu', 'provoz', 'Plán areálu na ostrově Štvanice, verze 09-2025',
     $url['Plán areálu 09-2025 (PDF)'] ?? 'https://files.cltk.cz/l8wemjw6xri01/Pl%C3%A1n%20are%C3%A1lu%20%2009-2025%20na%20web.pdf', 0, ''],
    ['Provozní řád – bazén', 'provoz', '',
     $url['Provozní řád – bazén'] ?? '', 0, ''],
    ['Provozní řád – posilovna (fitness)', 'provoz', 'Účinnost od 20. 6. 2017',
     $url['Provozní řád – posilovna (fitness)'] ?? '', 0, ''],
    ['Provozní řád – wellness', 'provoz', 'Účinnost od 1. 2. 2019',
     $url['Provozní řád – wellness'] ?? '', 0, ''],
];

$vlozit = [];
foreach ($radky as $i => [$nazev, $kat, $popis, $odkaz, $paticka, $patickaText]) {
    if ($odkaz === '') continue;
    $vlozit[] = ['nazev' => $nazev, 'kategorie' => $kat, 'popis' => $popis, 'soubor' => '', 'soubor_nazev' => '',
                 'url' => $odkaz, 'v_paticce' => $paticka, 'paticka_text' => $patickaText,
                 'visible' => 1, 'poradi' => $i, 'created_at' => $ted, 'updated_at' => $ted];
}
seed_log('Dokumenty: ' . seed_vloz('cltk_dokumenty', $vlozit) . ' (zatím odkazy na files.cltk.cz).');
