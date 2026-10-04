<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Stránky závodního tenisu a Tenisové školy – další textové bloky (modul Stránky).
   Doplňuje 60-bloky.php (ten zakládá „uvod“ a základní bloky stránek). Tady jsou
   bloky, které šablony stránek čtou navíc:

     zavodni-tenis     pyramida-*  stupně pyramidy (štítek = věk, nadpis = stupeň,
                                   perex = popis, text = odrážky „<strong>Popisek</strong> hodnota“)
                       zebricky    žebříček ČTS (nadpisy „Ženy“/„Muži“ + odrážky „<strong>1.</strong> jméno“)
                       reprezentanti  reprezentanti ČR (odrážky se jmény dospělých)
                       hraci       nadpis sekce hráčů
                       hrac-*      karty hráčů (štítek = žebříček s datem, perex = v klubu od…,
                                   text = odrážky úspěchů, fotka 4 : 5)
                       extraliga-rocniky  odrážky „<strong>rok</strong> výsledek“
                       soupiska    soupiska extraligy + týmová fotka
                       druzstva    družstva mládeže („Sezóna 2026“ + odrážky „<strong>kategorie</strong> výsledek“)
     tenisova-skola    informace, kategorie, prihlaska
     tenisova-skola-rozvrhy  harmonogram
     letni-kempy       terminy, ceny, prihlaska

   Fakta jen z ověřených podkladů: cltk-navrhy/README.md, podklady/data/obsah.json
   (zavodni_tenis, sin_slavy, cenik), podklady/03-zavodni-tenis-skolicka.md.
   Jména dětí (TSM, SVT, rozvrhy) se nepřebírají. Vkládá se jen dvojice stránka + klíč,
   která ještě není; fotky se kopírují do uploads/bloky/ (vlastní kopie – modul Stránky
   smí starou fotku bloku smazat, nesmí tím přijít o portrét trenéra ani snímek galerie). */

$n = 0;
$b = static function (string $stranka, string $klic, array $data, int $poradi) use (&$n): void {
    if (seed_blok($stranka, $klic, $data, $poradi)) $n++;
};
$existuje = static fn(string $stranka, string $klic): bool => (bool)row('SELECT id FROM cltk_bloky WHERE stranka = ? AND klic = ?', [$stranka, $klic]);

/**
 * Fotka bloku oříznutá na poměr $pomer (šířka / výška) kolem bodu $cx, $cy (0–1).
 * Bloky nemají sloupec „fokus“, proto se ořez dělá už tady. Vrací cestu v uploads/ nebo ''.
 */
$foto = static function (string $zdroj, string $jmeno, float $pomer, float $cx, float $cy, int $maxW, int $maxH): string {
    foreach (['jpg', 'png'] as $p) {
        if (is_file(UPLOAD_DIR . '/bloky/' . $jmeno . '.' . $p)) return 'bloky/' . $jmeno . '.' . $p;
    }
    if (!is_file($zdroj) || !function_exists('imagecreatefromjpeg')) return seed_obrazek($zdroj, 'bloky', $jmeno, $maxW, $maxH);
    $im = @imagecreatefromjpeg($zdroj);
    if (!$im) return seed_obrazek($zdroj, 'bloky', $jmeno, $maxW, $maxH);
    $w = imagesx($im);
    $h = imagesy($im);
    if ($w / $h > $pomer) { $cw = (int)round($h * $pomer); $ch = $h; } else { $cw = $w; $ch = (int)round($w / $pomer); }
    $x = (int)max(0, min($w - $cw, round($cx * $w - $cw / 2)));
    $y = (int)max(0, min($h - $ch, round($cy * $h - $ch / 2)));
    $orez = imagecrop($im, ['x' => $x, 'y' => $y, 'width' => $cw, 'height' => $ch]);
    imagedestroy($im);
    if (!$orez) return seed_obrazek($zdroj, 'bloky', $jmeno, $maxW, $maxH);
    $tmp = tempnam(sys_get_temp_dir(), 'cltk-orez-') . '.jpg';
    imagejpeg($orez, $tmp, 92);
    imagedestroy($orez);
    $rel = seed_obrazek($tmp, 'bloky', $jmeno, $maxW, $maxH);
    @unlink($tmp);
    return $rel;
};
$portret = static fn(string $zdroj, string $jmeno, float $cx, float $cy): string => $foto(seed_navrhy($zdroj), $jmeno, 4 / 5, $cx, $cy, 960, 1200);

/* ================= ZÁVODNÍ TENIS – pyramida ČTS =================
   Zdroj: PDF ČTS 2026 (seznamy hráčů SCM/SVT/TSM) přes obsah.json → zavodni_tenis.pyramida.
   „14 + 2“ = počet hráčů podle PDF ČTS (význam „+ VK“ neověřen, proto bez výkladu).
   Hlavní trenér SCM podle ČTS je Zdeněk Kubík, vedoucí SCM podle klubu Petr Vaníček – obě role. */
$b('zavodni-tenis', 'pyramida-skola', ['stitek' => '3–9 let', 'nadpis' => 'Tenisová škola',
    'perex' => 'Tenisová škola Markéty Vondroušové – minitenis, střední kurt a babytenis. Nábory jsou vždy na jaře a na podzim.',
    'text' => '<ul><li><strong>Dětí</strong> kolem stovky (podle klubu)</li><li><strong>Vedoucí trenér</strong> Mgr. Jan Pecha, Ph.D.</li></ul>',
    'odkaz' => 'tenisova-skola.php', 'odkaz_text' => 'Tenisová škola'], 10);
$b('zavodni-tenis', 'pyramida-tsm', ['stitek' => '10–14 let', 'nadpis' => 'TSM',
    'perex' => 'Tréninkové středisko mládeže Českého tenisového svazu.',
    'text' => '<ul><li><strong>Hráčů</strong> 14 + 2</li><li><strong>Hlavní trenér</strong> Bc. Antonín Štěpánek</li><li><strong>Trenéři</strong> Ing. Jaroslav Jandus, Magdaléna Zemanová</li></ul>'], 11);
$b('zavodni-tenis', 'pyramida-svt', ['stitek' => '15–18 let', 'nadpis' => 'SVT',
    'perex' => 'Středisko vrcholového tenisu ČTS – jedno ze šesti v Česku.',
    'text' => '<ul><li><strong>Hráčů</strong> 16 + 2</li><li><strong>Hlavní trenér</strong> Daniel Vaněk</li><li><strong>Trenéři</strong> Ing. Jan Vacek, Milan Trněný</li></ul>'], 12);
$b('zavodni-tenis', 'pyramida-scm', ['stitek' => '15–21 let', 'nadpis' => 'SCM',
    'perex' => 'Sportovní centrum mládeže ČTS, nejvyšší stupeň přípravy. V Česku jsou jen tři: I. ČLTK Praha, TK Sparta Praha a TK Prostějov.',
    'text' => '<ul><li><strong>Hráčů</strong> 9 + 2</li><li><strong>Hlavní trenér</strong> Zdeněk Kubík (podle ČTS)</li><li><strong>Vedoucí SCM</strong> Petr Vaníček, sportovní ředitel (podle klubu)</li><li><strong>Trenéři</strong> Ing. Jan Vacek, Milan Trněný</li></ul>'], 13);
$b('zavodni-tenis', 'pyramida-vrchol', ['stitek' => 'Dospělí', 'nadpis' => 'Extraliga, WTA a ATP',
    'perex' => 'Mistr republiky v extralize smíšených družstev 2018 a 2019. Hráčky a hráči klubu na okruzích WTA, ATP a ITF.',
    'text' => '<ul><li><strong>Kapitáni</strong> Petr Vaníček a Ivo Minář</li></ul>',
    'odkaz' => '#extraliga', 'odkaz_text' => 'Extraliga'], 14);

/* ================= ZÁVODNÍ TENIS – žebříčky a reprezentace =================
   obsah.json → zavodni_tenis.zebricky_leto_2026 (cztenis.cz/zebricky, léto 2026),
   reprezentanti_2026 (PDF ČTS „Seznam reprezentantů 2026“: U14 4, U18 10, dospělí 12). */
$b('zavodni-tenis', 'zebricky', ['nadpis' => 'Žebříček ČTS', 'perex' => 'Pořadí hráčů klubu v žebříčcích dospělých · léto 2026',
    'text' => '<h3>Ženy</h3><ul><li><strong>1.</strong> Karolína Muchová</li><li><strong>6.</strong> Markéta Vondroušová</li><li><strong>9.</strong> Nikola Bartůňková</li><li><strong>10.</strong> Darja Viďmanová</li></ul>'
            . '<h3>Muži</h3><ul><li><strong>10.</strong> Jonáš Forejtek</li><li><strong>17.</strong> Jakub Nicod</li><li><strong>18.</strong> Tadeáš Paroulek</li><li><strong>19.</strong> Andrew Paulson</li></ul>'
            . '<p>Pramen: žebříčky Českého tenisového svazu, léto 2026.</p>'], 20);
$b('zavodni-tenis', 'reprezentanti', ['nadpis' => 'Reprezentanti 2026',
    'perex' => 'Ze 146 reprezentantů České republiky jich 26 hraje za I. ČLTK Praha. Čtyři v kategorii do 14 let, deset do 18 let a dvanáct mezi dospělými.',
    'text' => '<h3>Dospělí</h3><ul><li>Nikola Bartůňková</li><li>Anastasia Detiuc</li><li>Sarah Melany Fajmonová</li><li>Karolína Muchová</li><li>Darja Viďmanová</li><li>Markéta Vondroušová</li>'
            . '<li>Radek Chodora</li><li>Jakub Filip</li><li>Matyáš Kozlovský</li><li>Jakub Nicod</li><li>Oliver Sanders</li><li>Matěj Vocel</li></ul>'], 21);

/* ================= ZÁVODNÍ TENIS – naši hráči =================
   obsah.json → sin_slavy (kariérní maxima WTA s datem, v klubu od…), zebricky_leto_2026;
   Martincová 20. místo: podklady/03 kap. 4 (cztenis.cz, léto 2026). Fotky z článků klubu. */
$b('zavodni-tenis', 'hraci', ['stitek' => 'Naši hráči', 'nadpis' => 'Hráčky a hráči v sezóně <em>2026</em>.',
    'perex' => 'Pořadí v žebříčku Českého tenisového svazu platí pro léto 2026, kariérní maxima ve světovém žebříčku WTA k uvedenému datu.'], 30);
if (!$existuje('zavodni-tenis', 'hrac-muchova')) {
    $b('zavodni-tenis', 'hrac-muchova', ['stitek' => '1. v žebříčku ČTS žen · léto 2026', 'nadpis' => 'Karolína Muchová',
        'perex' => 'Za I. ČLTK Praha hraje od sezóny 2019. Kariérní maximum WTA č. 6 (13. 7. 2026).',
        'text' => '<ul><li>US Open 2026 – vítězka smíšené čtyřhry s Jakubem Menšíkem</li><li>finále Wimbledonu 2026</li><li>tituly WTA 1000 Dauhá a WTA 500 Bad Homburg 2026</li><li>finále Roland Garros 2023</li></ul>',
        'foto' => $portret('assets/foto/clanky/karolina-muchova-ve-finale-wimbledonu.jpg', 'hrac-muchova', .53, .45),
        'foto_popisek' => 'Karolína Muchová s talířem pro finalistku Wimbledonu 2026'], 31);
}
if (!$existuje('zavodni-tenis', 'hrac-vondrousova')) {
    $b('zavodni-tenis', 'hrac-vondrousova', ['stitek' => '6. v žebříčku ČTS žen · léto 2026', 'nadpis' => 'Markéta Vondroušová',
        'perex' => 'Na Štvanici od roku 2006, za I. ČLTK Praha od sezóny 2011. Čestná členka klubu, Tenisová škola nese její jméno.',
        'text' => '<ul><li>Wimbledon 2023 – první nenasazená šampionka v historii</li><li>stříbro na olympijských hrách v Tokiu (hráno 2021)</li><li>finále Roland Garros 2019</li><li>WTA 500 Berlín 2025</li></ul>',
        'foto' => $portret('assets/foto/hracka-vondrousova-wimbledon-2023-trofej.jpg', 'hrac-vondrousova', .47, .45),
        'foto_popisek' => 'Markéta Vondroušová s mísou Venus Rosewater Dish · Wimbledon 2023'], 32);
}
if (!$existuje('zavodni-tenis', 'hrac-bartunkova')) {
    $b('zavodni-tenis', 'hrac-bartunkova', ['stitek' => '9. v žebříčku ČTS žen · léto 2026', 'nadpis' => 'Nikola Bartůňková',
        'perex' => 'I. ČLTK Praha je její domovský oddíl, v klubu hraje od roku 2017. Kariérní maximum WTA č. 34 (14. 9. 2026).',
        'text' => '<ul><li>3. kolo Australian Open 2026 z kvalifikace</li><li>semifinále WTA 500 Guadalajara 2025</li><li>finále WTA 125 Samsun 2025</li><li>finále juniorského Wimbledonu 2023</li></ul>',
        'foto' => $portret('assets/foto/clanky/bartunkova-si-zahraje-hlavni-soutez-australian-open.jpg', 'hrac-bartunkova', .42, .5),
        'foto_popisek' => 'Nikola Bartůňková na Australian Open 2026'], 33);
}
if (!$existuje('zavodni-tenis', 'hrac-vidmanova')) {
    $b('zavodni-tenis', 'hrac-vidmanova', ['stitek' => '10. v žebříčku ČTS žen · léto 2026', 'nadpis' => 'Darja Viďmanová',
        'perex' => 'Štvanická hráčka od juniorských let, za I. ČLTK Praha od roku 2014. V elitní stovce WTA od 22. 6. 2026, kariérní maximum č. 75 (31. 8. 2026).',
        'text' => '<ul><li>první titul WTA – WTA 125 Figueira da Foz 2026</li><li>finále WTA 250 Memphis 2026</li></ul>',
        'foto' => $portret('assets/foto/clanky/darja-vidmanova-ve-finale-turnaje-wta-250-v-memphisu.jpg', 'hrac-vidmanova', .55, .5),
        'foto_popisek' => 'Darja Viďmanová ve finále turnaje WTA 250 v Memphisu'], 34);
}
if (!$existuje('zavodni-tenis', 'hrac-martincova')) {
    $b('zavodni-tenis', 'hrac-martincova', ['stitek' => '20. v žebříčku ČTS žen · léto 2026', 'nadpis' => 'Tereza Martincová',
        'perex' => 'V klubu od roku 2011, mistryně extraligy 2018 a 2019. Kariérní maximum WTA č. 40 (únor 2022).',
        'text' => '<ul><li>Conseq Prague Open 2026 (ITF W75) – titul z kvalifikace, v hale na Štvanici</li></ul>',
        'foto' => $portret('assets/foto/clanky/tereza-martincova-ovladla-conseq-prague-open-26.jpg', 'hrac-martincova', .5, .45),
        'foto_popisek' => 'Tereza Martincová s trofejí z Conseq Prague Open 2026'], 35);
}
$b('zavodni-tenis', 'hrac-forejtek', ['stitek' => '10. v žebříčku ČTS mužů · léto 2026', 'nadpis' => 'Jonáš Forejtek',
    'perex' => 'Odchovanec klubu – za I. ČLTK Praha hrál v letech 2012–2018 a znovu hraje od roku 2022.',
    'text' => '<ul><li>juniorská světová jednička (2019, tehdy za TK Škoda Plzeň)</li><li>vítěz juniorského US Open 2019</li><li>čtyřhra ATP Challenger 125 Sevilla 2025</li></ul>'], 36);

/* ================= ZÁVODNÍ TENIS – extraliga a družstva =================
   obsah.json → zavodni_tenis.extraliga, extraliga_starsi_finale, druzstva_mladez;
   soupiska 2025: tenisovaextraliga.cz/soupisky (hostující hráči a junioři neuvedeni). */
$b('zavodni-tenis', 'extraliga-rocniky', ['stitek' => 'Ročníky extraligy',
    'text' => '<ul>'
            . '<li><strong>2025</strong> 2. místo ve skupině (se Spartou 1:5, s Plíšková Tennis Academy 6:3)</li>'
            . '<li><strong>2024</strong> 3.–4. místo</li>'
            . '<li><strong>2023</strong> vyhrané předkolo, 2. místo ve skupině</li>'
            . '<li><strong>2022</strong> poslední místo ve skupině</li>'
            . '<li><strong>2021</strong> dělené 3. místo</li>'
            . '<li><strong>2020</strong> 2. místo ve skupině (se Spartou 3:6)</li>'
            . '<li><strong>2019</strong> <em>mistr České republiky</em> – finále 18. 12. v Říčanech, s Přerovem 5:2</li>'
            . '<li><strong>2018</strong> <em>mistr České republiky</em> – finále 19. 12. v Říčanech, s Prostějovem 5:4, první titul po 28 letech</li>'
            . '<li><strong>2015</strong> finalista – finále s Prostějovem 0:6</li>'
            . '<li><strong>2011</strong> finalista – finále s Prostějovem 2:5</li>'
            . '</ul>'], 40);
if (!$existuje('zavodni-tenis', 'soupiska')) {
    $b('zavodni-tenis', 'soupiska', ['stitek' => 'Soupiska 2025', 'nadpis' => 'Družstvo v extralize <em>2025</em>',
        'perex' => 'Kapitáni týmu Petr Vaníček a Ivo Minář.',
        'text' => '<h3>Ženy</h3><ul><li>Karolína Muchová</li><li>Markéta Vondroušová</li><li>Nikola Bartůňková</li><li>Darja Viďmanová</li><li>Tereza Martincová</li><li>Sarah Melany Fajmonová</li></ul>'
                . '<h3>Muži</h3><ul><li>Jonáš Forejtek</li><li>Tadeáš Paroulek</li><li>Jakub Nicod</li><li>Andrew Paulson</li><li>Jakub Filip</li><li>Matěj Vocel</li></ul>',
        'foto' => seed_obrazek(seed_navrhy('assets/foto/extraliga-2025-tymova-fotografie.jpg'), 'bloky', 'extraliga-2025-tym', 2400, 1600),
        'foto_popisek' => 'Družstvo I. ČLTK Praha v Tenisové extralize 2025 · foto Martin Sidorják',
        'odkaz' => 'https://tenisovaextraliga.cz/soupisky/', 'odkaz_text' => 'Celá soupiska na tenisovaextraliga.cz'], 41);
}
$b('zavodni-tenis', 'druzstva', ['stitek' => 'Družstva mládeže', 'nadpis' => 'Medaile z mistrovství <em>republiky</em>',
    'perex' => 'Klub hraje soutěže družstev ve všech věkových kategoriích – od babytenisu a středního kurtu přes žactvo a dorost po dospělé.',
    'text' => '<h3>Sezóna 2026</h3><ul><li><strong>Babytenis</strong> MČR družstev – 2. místo z 12 (Prostějov, 4.–6. 9.)</li><li><strong>Starší žactvo</strong> MČR družstev – 3. místo (Ostrava, 28.–30. 8.)</li><li><strong>Mladší žactvo</strong> MČR družstev – 3. místo z 8 (Zlín, 8.–11. 7.)</li><li><strong>Dorost</strong> MČR družstev – 3. místo (TK Sparta Praha, 9.–12. 9.)</li></ul>'
            . '<h3>Sezóna 2025</h3><ul><li><strong>Dorost</strong> vicemistr České republiky – finále se Spartou 2:5</li><li><strong>Starší žactvo</strong> MČR družstev – 3. místo (Rakovník)</li><li><strong>Pražská liga</strong> starší i mladší žactvo – 1. místo</li></ul>'], 50);

/* ================= TENISOVÁ ŠKOLA =================
   podklady/03 kap. 9 (Informace, Turnaje), obsah.json → zavodni_tenis.pyramida[0]. */
$b('tenisova-skola', 'informace', ['stitek' => 'Informace', 'nadpis' => 'Jak škola <em>funguje</em>'], 2);
$b('tenisova-skola', 'kategorie', ['stitek' => 'Kategorie podle věku', 'nadpis' => 'Minitenis, střední kurt a <em>babytenis</em>',
    'perex' => 'Tenisová škola každoročně pořádá turnaje pro hráče do 9 let. Přihlášky na turnaje jsou v informačním systému Českého tenisového svazu.',
    'text' => '<ul><li><strong>Minitenis</strong> do 7 let (podle ročníku narození)</li><li><strong>Střední kurt</strong> do 8 let</li><li><strong>Babytenis</strong> do 9 let</li></ul>'], 3);
$b('tenisova-skola', 'prihlaska', ['stitek' => 'Přihláška', 'nadpis' => 'Jak se do školy <em>přihlásit</em>',
    'perex' => 'Nábory jsou vždy na jaře a na podzim.',
    'text' => '<ol><li><strong>Nábor</strong> vždy na jaře a na podzim</li><li><strong>Domluva</strong> s vedoucím trenérem Tenisové školy – telefonicky nebo e-mailem</li><li><strong>Členství</strong> základní členství Tenisové školy 1&nbsp;000 Kč, hradí se na začátku roku</li><li><strong>Tréninky</strong> podle rozvrhů skupin, cena zahrnuje trenéra i pronájem kurtu</li></ol>'], 4);
$b('tenisova-skola-rozvrhy', 'harmonogram', ['stitek' => 'Harmonogram', 'nadpis' => 'Termíny <em>sezóny</em>',
    'perex' => 'Tréninky, prázdniny a dny, kdy se nehraje. Změny vyhrazeny.'], 2);

/* ================= LETNÍ KEMPY =================
   obsah.json → cenik.letni_kempy_2026; fotogalerie kempů 2026 z webu klubu (podklady/03 kap. 9). */
$b('letni-kempy', 'terminy', ['stitek' => 'Termíny', 'nadpis' => 'Termíny <em>kempů</em>',
    'perex' => 'Pětidenní kempy ve dvou variantách – celodenní (A) a dopolední (B).',
    'odkaz2' => 'https://cltk.dphoto.com/album/o54c6s0a', 'odkaz2_text' => 'Fotogalerie kempů 2026'], 2);
$b('letni-kempy', 'ceny', ['stitek' => 'Ceník', 'nadpis' => 'Varianty a <em>ceny</em>',
    'perex' => 'Ceny za pětidenní kemp. Hráči I. ČLTK Praha mají cenu zvýhodněnou.'], 3);
$b('letni-kempy', 'prihlaska', ['stitek' => 'Přihláška', 'nadpis' => 'Přihláška a <em>kontakt</em>',
    'perex' => 'Přihlašuje se online formulářem. Platí se až po potvrzení přihlášky e-mailem.'], 4);

seed_log('Bloky závodního tenisu a Tenisové školy: ' . $n . ' nových.');
