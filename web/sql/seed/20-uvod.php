<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Úvodní stránka: informační lišta, galerie, aktuality, výsledky.
   Zdroj: ZADANI §4 (PDF klienta s opravami), Varianta 4 (galerie), obsah.json. */

$ted = ted();

/* ---------- informační lišta ---------- */
if (seed_prazdna('cltk_oznameni')) {
    seed_vloz('cltk_oznameni', [[
        'text' => 'Klubová restaurace je uzavřena od 1. 10. do 8. 10.',
        'odkaz' => '', 'plati_od' => '2026-10-01', 'plati_do' => '2026-10-08',
        'visible' => 1, 'created_at' => $ted, 'updated_at' => $ted,
    ]]);
    seed_log('Informační lišta: 1 zpráva.');
}

/* ---------- úvodní galerie (4 snímky z Varianty 4) ---------- */
if (seed_prazdna('cltk_uvodni_galerie')) {
    $snimky = [
        ['foto', 'Vondroušová', seed_navrhy('assets/foto/hracka-vondrousova-wimbledon-2023-trofej.jpg'), 'vondrousova-wimbledon-2023', '70% 28%',
         'Markéta Vondroušová líbá mísu Venus Rosewater Dish pro vítězku Wimbledonu 2023',
         '<em>Markéta Vondroušová</em> s mísou Venus Rosewater Dish · Wimbledon 2023', 'foto Martin Sidorják'],
        ['foto', 'Muchová', seed_navrhy('assets/foto/clanky/karolina-muchova-ve-finale-wimbledonu.jpg'), 'muchova-wimbledon-2026', '50% 26%',
         'Karolína Muchová s talířem pro finalistku Wimbledonu 2026',
         '<em>Karolína Muchová</em> s talířem pro finalistku · Wimbledon 2026', 'foto Martin Sidorják'],
        ['foto', 'Muchová & Menšík', seed_navrhy('assets/foto/clanky/karolina-muchova-ovladla-smisenou-ctyrhru-na-us-open.jpg'), 'muchova-mensik-us-open-2026', '60% 22%',
         'Karolína Muchová a Jakub Menšík s trofejí pro vítěze smíšené čtyřhry US Open 2026',
         '<em>Karolína Muchová a Jakub Menšík</em> · vítězové smíšené čtyřhry US Open 2026', 'foto Getty Images'],
    ];
    $radky = [];
    foreach ($snimky as [$typ, $rejstrik, $zdroj, $jmeno, $fokus, $alt, $popisek, $kredit]) {
        $foto = seed_obrazek($zdroj, 'galerie', $jmeno);
        [$w, $h] = obrazek_rozmer($foto);
        $radky[] = ['typ' => $typ, 'rejstrik' => $rejstrik, 'foto' => $foto, 'foto_w' => $w, 'foto_h' => $h,
                    'fokus' => $fokus, 'alt' => $alt, 'popisek' => $popisek, 'kredit' => $kredit,
                    'visible' => 1, 'created_at' => $ted, 'updated_at' => $ted];
    }
    $radky[] = [
        'typ' => 'deska', 'rejstrik' => 'BJK Cup 2026', 'foto' => '', 'foto_w' => 0, 'foto_h' => 0, 'fokus' => '50% 50%',
        'alt' => 'Billie Jean King Cup 2026: Česko vyhrálo finále nad Ukrajinou 2:0, Karolína Muchová porazila Anhelinu Kalininovou 6:2, 6:3',
        'popisek' => '<em>Billie Jean King Cup 2026</em> · finále v Šen-čenu, 27. 9. 2026',
        'kredit' => 'fotografii z finále doplní klub',
        'deska_stitek' => 'Billie Jean King Cup · 2026', 'deska_titul' => 'Česko<br><em>šampionem</em>',
        'deska_tym_a' => 'Česko', 'deska_skore' => '2 : 0', 'deska_tym_b' => 'Ukrajina',
        'deska_hrac' => 'Karolína Muchová', 'deska_souper' => 'Anhelina Kalininová', 'deska_sety' => '6:2 · 6:3',
        'deska_misto' => 'Šen-čen · 27. září 2026 · dvanáctý titul Česka',
        'visible' => 1, 'created_at' => $ted, 'updated_at' => $ted,
    ];
    seed_log('Úvodní galerie: ' . seed_vloz('cltk_uvodni_galerie', $radky) . ' snímky.');
}

/* ---------- aktuality z klubu (PDF klienta) ---------- */
if (seed_prazdna('cltk_aktuality')) {
    $akt = [
        ['Hrajeme v hale', 'Halová sezóna začíná od 5. 10.', 'aktuality-hala.jpg', 'hrajeme-v-hale', '50% 55%'],
        ['Nová restaurace', 'Od 1. 11. se můžete těšit na novou klubovou restauraci Tiebreak.', 'aktuality-tiebreak.jpg', 'nova-restaurace-tiebreak', '50% 45%'],
        ['Terasa v novém', 'Venkovní terasa restaurace prošla rekonstrukcí.', 'aktuality-terasa.jpg', 'terasa-v-novem', '50% 50%'],
    ];
    $radky = [];
    foreach ($akt as [$nadpis, $popis, $soubor, $jmeno, $fokus]) {
        $radky[] = ['nadpis' => $nadpis, 'popis' => $popis, 'foto' => seed_obrazek(seed_podklady('klient-pdf/' . $soubor), 'aktuality', $jmeno),
                    'fokus' => $fokus, 'odkaz' => '', 'odkaz_text' => '', 'datum' => substr($ted, 0, 10), 'visible' => 1, 'created_at' => $ted, 'updated_at' => $ted];
    }
    seed_log('Aktuality: ' . seed_vloz('cltk_aktuality', $radky) . '.');
}

/* ---------- výsledky hráčů (jen skutečné výsledky se skóre) ----------
   Data zápasů: BJK, US Open, Memphis a Wimbledon podle Varianty 4; Bad Homburg
   (finále v sobotu 27. 6.), Figueira da Foz („nedělní finále“ = 21. 6.), Dauhá
   („této soboty“ = 14. 2.) a Conseq Prague Open („v neděli“ = 15. 2.) podle
   textů klubových článků. Bez odkazu – karta je soběstačná a články starého webu
   cltk.cz po jeho vypnutí zmizí. */
if (seed_prazdna('cltk_vysledky')) {
    $vysl = [
        ['2026-09-27', 'Šen-čen', 'Billie Jean King Cup · finále · Česko – Ukrajina 2:0', 'Karolína Muchová', 'Anhelina Kalininová',
         'V úvodní dvouhře finále porazila Anhelinu Kalininovou. Česko získalo dvanáctý titul v soutěži.', '6:2 6:3', 'Titul', ''],
        ['2026-08-27', 'New York', 'US Open · finále · smíšená čtyřhra', 'Karolína Muchová a Jakub Menšík', 'Belinda Bencicová, Flavio Cobolli',
         'Česká dvojice získala senzační grandslamový titul, když ve finále porazila švýcarsko-italský pár Bencicová, Cobolli.', '6:3 1:6 10:6', 'Titul', ''],
        ['2026-08-02', 'Memphis', 'WTA 250 Memphis · finále', 'Darja Viďmanová', 'Kristina Liutová',
         'Finále s Kristinou Liutovou a posun do elitní stovky.', '6:1 1:6 3:6', 'Finále', ''],
        ['2026-07-11', 'Londýn', 'Wimbledon · finále · dvouhra žen', 'Karolína Muchová', 'Linda Nosková',
         'První ryze české finále ženské dvouhry ve Wimbledonu. Dva dny nato kariérní maximum – 6. místo žebříčku WTA.', '2:6 7:5 3:6', 'Finále', ''],
        ['2026-06-27', 'Bad Homburg', 'WTA 500 Bad Homburg · finále · tráva', 'Karolína Muchová', 'Naomi Osaka',
         'Ve finále vedla 6:1, 1:0, když Naomi Osaka skrečovala. Premiérový titul na trávě a návrat do elitní desítky.', '6:1 1:0 skr.', 'Titul', ''],
        ['2026-06-21', 'Figueira da Foz', 'WTA 125 Figueira da Foz · finále', 'Darja Viďmanová', 'Ayla Aksuová',
         'Ve finále porazila Turkyni Aylu Aksuovou. První titul z okruhu WTA.', '6:2 6:3', 'Titul', ''],
        ['2026-02-15', 'Praha', 'ITF W75 Conseq Prague Open · finále · Štvanice', 'Tereza Martincová', 'Sinja Krausová',
         'Doma na Štvanici prošla z kvalifikace až k titulu, ve finále porazila nejvýše nasazenou Sinju Krausovou.', '6:3 6:4', 'Titul', ''],
        ['2026-02-14', 'Dauhá', 'WTA 1000 Dauhá · finále', 'Karolína Muchová', 'Victoria Mboko',
         'Ve finále porazila Kanaďanku Victorii Mboko a získala dosud největší titul kariéry.', '6:4 7:5', 'Titul', ''],
    ];
    $radky = [];
    foreach ($vysl as [$datum, $misto, $stitek, $hraci, $souper, $text, $sety, $verdikt, $odkaz]) {
        $radky[] = ['datum' => $datum, 'misto' => $misto, 'stitek' => $stitek, 'hraci' => $hraci, 'souper' => $souper,
                    'text' => $text, 'sety' => sety_z_textu($sety), 'verdikt' => $verdikt, 'odkaz' => $odkaz, 'foto' => '',
                    'visible' => 1, 'created_at' => $ted, 'updated_at' => $ted];
    }
    seed_log('Výsledky hráčů: ' . seed_vloz('cltk_vysledky', $radky) . '.');
}
