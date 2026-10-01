<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Trenéři. Zdroj: obsah.json → treneri (22 osob), kontakty Tenisové školy z rešerše
   (podklady/03 kap. 9, veřejné na dnešním webu klubu). Portréty z assets/foto/treneri
   (originály 3543 × 4724 px se zmenší na 1200 × 1600). */

if (!seed_prazdna('cltk_treneri')) return;

$data = seed_json('obsah.json')['treneri'] ?? [];
if (!$data) { seed_log('  ! obsah.json → treneri chybí'); return; }

$zarazeni = ['zavodni' => 'zavodni', 'skolicka' => 'skola', 'rekreacni' => 'privatni'];
$kontakty = [
    'Mgr. Jan Pecha, Ph.D.' => ['+420 721 663 118', 'pecha@cltk.cz'],
    'Andrea Vašíčková'      => ['+420 602 819 899', ''],
    'Bc. Adéla Vašíčková'   => ['+420 725 480 412', ''],
];
$skupinyPrivatni = 'Privátní trenéři pro rekreační hráče';

$ted = ted();
$radky = [];
$poradi = ['zavodni' => 0, 'skola' => 0, 'privatni' => 0];
foreach ($data as $t) {
    $z = $zarazeni[$t['sekce'] ?? 'zavodni'] ?? 'zavodni';
    $skupina = (string)($t['skupina'] ?? '');
    if ($skupina === 'Mimo stránku Trenéři') $skupina = 'Další trenéři';
    if ($z === 'privatni') $skupina = $skupinyPrivatni;
    if ($z === 'skola') $skupina = '';
    $foto = '';
    if (!empty($t['foto_file'])) {
        $foto = seed_obrazek(seed_navrhy($t['foto_file']), 'treneri', pathinfo($t['foto_file'], PATHINFO_FILENAME), 1200, 1600);
    }
    [$tel, $mail] = $kontakty[$t['jmeno']] ?? ['', ''];
    $radky[] = [
        'jmeno' => $t['jmeno'], 'role' => (string)($t['role'] ?? ''), 'zarazeni' => $z, 'skupina' => $skupina,
        'foto' => $foto, 'fokus' => '50% 22%', 'fakta' => implode("\n", (array)($t['fakta'] ?? [])), 'text' => '',
        'telefon' => $tel, 'email' => $mail, 'kontakt' => '', 'visible' => 1, 'poradi' => $poradi[$z]++,
        'created_at' => $ted, 'updated_at' => $ted,
    ];
}

/* Jiří Hřebec trénuje i rekreační hráče (obsah.json → areal.sluzby „Výuka tenisu“). */
foreach ($radky as $r) {
    if ($r['jmeno'] === 'Jiří Hřebec' && $r['zarazeni'] === 'zavodni') {
        $radky[] = array_merge($r, ['zarazeni' => 'privatni', 'skupina' => $skupinyPrivatni, 'role' => 'privátní trenér pro rekreační hráče',
                                    'poradi' => $poradi['privatni']++]);
        break;
    }
}

seed_log('Trenéři: ' . seed_vloz('cltk_treneri', $radky) . ' záznamů.');
