<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Partneři – mřížka 5 × 5 na úvodu, pořadí řádků podle PDF klienta (ZADANI §4.9).
   Jednobarevná průhledná loga dodal klient (podklady/klient-loga/partneri/*.png)
   a používají se TAK, JAK JSOU – nepřebarvují se (ZADANI §0.2). Kopírují se
   beze změny do uploads/partneri/mono/. Barevný originál z cltk-navrhy/assets/partneri
   se uloží do uploads/partneri/ (pro náhled v administraci a případné nové
   přebarvení přes partner_logo_mono()).
   Odkazy: obsah.json → partneri (ověřené 23. 9. 2026). Glenfiddich odkaz nemá. */

if (!seed_prazdna('cltk_partneri')) return;

$ted = ted();
$partneri = [
    // soubor klienta, název, odkaz, barevný originál v cltk-navrhy/assets/partneri
    ['vivus', 'Vivus', 'https://vivus.cz/', 'vivus.jpg'],
    ['jt-banka', 'J&T Banka', 'http://www.jtfg.com/', 'jt-banka.jpg'],
    ['pankrac', 'Pankrác a.s.', 'https://www.pankrac-as.cz/', 'pankrac.jpg'],
    ['aston', 'Aston – služby v ekologii', 'https://www.aston-eco.cz/', 'aston.jpg'],
    ['green-gas', 'GreenGas', 'https://www.dpb.cz/', 'green-gas.jpg'],

    ['noncore', 'Noncore', 'http://www.noncore.cz/index.html', 'noncore.jpg'],
    ['hodinarstvi-bechyne', 'Hodinářství Bechyně', 'https://www.hodinarstvibechyne.cz/cs/', 'hodinarstvi-bechyne.jpg'],
    ['sport-construction', 'Sport Construction', 'https://www.ekkl.cz/', 'sport-construction.jpg'],
    ['iso', 'ISO Praha – stavebniny', 'http://www.iso-praha.cz/', 'iso-praha.jpg'],
    ['smartwings', 'Smartwings', 'https://www.smartwings.com/', 'smartwings.jpg'],

    ['cpp', 'ČPP – Vienna Insurance Group', 'https://www.cpp.cz/', 'cpp.jpg'],
    ['crane-constancy-capital', 'Crane Constancy Capital', 'http://cranece.com/', 'crane-constancy-capital.png'],
    ['tukas', 'TUkas', 'https://www.tukas.cz', 'tukas.png'],
    ['rpm-facility', 'RPM Facility', 'https://rpmfacility.cz/', 'rpm-facility.jpg'],
    ['advantage-cars', 'Advantage Cars', 'https://www.advantage-cars.cz/', 'advantage-cars.jpg'],

    ['babolat', 'Babolat', 'https://matchpoint.cz/', 'babolat.png'],
    ['mizuno', 'Mizuno', 'https://www.levelsportkoncept.cz/znacka/mizuno', 'mizuno.jpg'],
    ['peach', 'Peach Distribution', 'https://www.peach-distribution.cz/', 'peach.jpg'],
    ['glenfiddich', 'Glenfiddich', '', 'glenfiddich.png'],
    ['messy-play', 'Messy Play', 'https://www.messyplay.cz/', 'messy-play.jpg'],

    ['praha', 'Hlavní město Praha', 'https://praha.eu/', 'praha.png'],
    ['praha-7', 'Městská část Praha 7', 'https://www.praha7.cz/', 'praha-7.png'],
    ['victoria', 'Victoria – Vysokoškolské sportovní centrum MŠMT', 'https://www.vsc.cz/', 'victoria-vsc.jpg'],
    ['narodni-sportovni-agentura', 'Národní sportovní agentura', 'https://nsa.gov.cz/', 'narodni-sportovni-agentura.jpg'],
    ['cesky-tenis', 'Český tenis (Český tenisový svaz)', 'http://www.cztenis.cz/', 'cesky-tenis.png'],
];

$radky = [];
foreach ($partneri as $i => [$soubor, $nazev, $odkaz, $original]) {
    $mono = seed_kopiruj(seed_podklady('klient-loga/partneri/' . $soubor . '.png'), 'partneri/mono/' . $soubor . '.png');
    $logo = seed_obrazek(seed_navrhy('assets/partneri/' . $original), 'partneri', $soubor, 1600, 1600);
    $radky[] = ['nazev' => $nazev, 'url' => $odkaz, 'logo' => $logo, 'logo_mono' => $mono,
                'visible' => 1, 'poradi' => $i, 'created_at' => $ted, 'updated_at' => $ted];
}
seed_log('Partneři: ' . seed_vloz('cltk_partneri', $radky) . ' (loga od klienta beze změny).');
