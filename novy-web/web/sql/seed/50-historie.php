<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Historie: kronika (40 milníků), triptych „Tři wimbledonské trávy“, medailony
   „Od Žemly po Muchovou“ a Zlatá deska. Zdroj: obsah.json → kronika,
   Varianta 4 (triptych, medailony, záložky desky), PDF klienta (fotky triptychu).
   Texty k záložkám desky jsou v bloky (60-bloky.php, stránka historie).
   Postřehy klienta 8. 10. 2026 (jména klubu 1948–1969, tituly Motorletu, Složil, Damm, Benešová,
   Šimek mezi prezidenty) jsou zapsané tady; server je dostal migrací
   sql/migrace/2026-10-08-historie-postrehy.php. Řádky, které přibyly, se vkládají až za ostatní,
   aby id dřívějších řádků zůstala stejná jako na serveru (migrace je přidala na konec) –
   na webu řadí rok (kronika) a poradi (deska), ne id. */

$ted = ted();

/* ---------- kronika ---------- */
if (seed_prazdna('cltk_milniky')) {
    /* Opravy podle klubu nad podklady průzkumu – obsah.json (cltk-navrhy) zůstává, jak byl. */
    $eraOprava = ['Motorlet (od 1949)' => 'Spartak a Motorlet (1949–1969)'];
    $opravy = [   // „rok|původní titulek“ => nové hodnoty
        '1949|Emigrace a nové jméno' => [
            'text' => 'V červenci 1949 zůstává Drobný ve švýcarském Gstaadu. Klub je začleněn jako oddíl pod tělovýchovnou jednotu, která postupně nese jména DSO Spartak (1948–1950), Sokol Šverma Jinonice (1951–1953), Spartak Praha Motorlet (1954–1966) a Motorlet Praha (1966–1969).'],
        '1966|Mistři ligy s Kodešem' => [   // rok 1956: řadí se mezi 1954 a 1962, velký rok 1956 a pod ním „1956–1968“
            'rok' => 1956, 'rok_text' => '1956–1968', 'titulek' => 'Dvanáct titulů mistra republiky',
            'text' => 'Spartak Praha Motorlet je v letech 1956–1965 desetkrát mistrem republiky smíšených družstev, Motorlet Praha přidává tituly v letech 1966 a 1968. Od roku 1963 hraje v prvním týmu mladý Jan Kodeš.',
            'foto_popisek' => 'Mistři ligy 1966'],
        '2011|Lucie Hradecká a Fed Cup' => [
            'titulek' => 'Hradecká, Benešová a Fed Cup',
            'text' => 'Hradecká vyhrává s Hlaváčkovou Roland Garros a rozhodující čtyřhrou finále Fed Cup; Iveta Benešová vyhrává s Jürgenem Melzerem mix ve Wimbledonu. V roce 2012 Hradecká přiváží olympijské stříbro, Ivo Minář vyhrává Davis Cup a ve foyer je 14. 6. 2012 odhalena deska Jaroslava Drobného.'],
    ];
    $nove = [     // nové milníky (rok, doba, titulek, text, pramen, jistota) – až za ostatní
        [1978, 'TJ Dopravní podnik (kolem 1970–1990)', 'Složil vítězem mixu na Roland Garros', 'Pavel Složil vyhrává s Renátou Tomanovou mix na Roland Garros.',
         'en.wikipedia 1978 French Open – Mixed doubles', 'titul ověřen; vazba na Štvanici podle klubu'],
        [2006, 'I. ČLTK Praha (1990–dnes)', 'Damm vítězem čtyřhry na US Open', 'Martin Damm vyhrává s Leanderem Paesem čtyřhru na US Open.',
         "en.wikipedia 2006 US Open – Men's doubles", 'ověřeno'],
    ];

    $radky = [];
    $poradiRoku = [];
    foreach (seed_json('obsah.json')['kronika'] ?? [] as $k) {
        $foto = '';
        if (!empty($k['foto_file'])) {
            $foto = seed_obrazek(seed_navrhy($k['foto_file']), 'historie', pathinfo($k['foto_file'], PATHINFO_FILENAME), 1800, 1800);
        }
        $oprava = $opravy[(int)$k['rok'] . '|' . $k['titulek']] ?? [];
        $rok = (int)($oprava['rok'] ?? $k['rok']);       // opravený rok – pořadí se počítá v něm
        $poradiRoku[$rok] = ($poradiRoku[$rok] ?? -1) + 1;
        $era = (string)($k['era'] ?? '');
        $radky[] = array_merge([
            'rok' => $rok, 'rok_text' => (string)($k['rok_text'] ?? ''), 'era' => $eraOprava[$era] ?? $era,
            'titulek' => (string)$k['titulek'], 'text' => (string)$k['text'], 'foto' => $foto,
            'foto_popisek' => $rok === 1979 ? 'Davis Cup ČSSR – Švédsko na starém centrkurtu, 1979' : '',
            'zdroj' => (string)($k['zdroj'] ?? ''), 'jistota' => (string)($k['jistota'] ?? ''),
            'visible' => 1, 'poradi' => $poradiRoku[$rok],
        ], $oprava);
    }
    if ($radky) {                 // bez podkladů průzkumu (server) kronika nevznikne vůbec – ani jen ze dvou nových řádků
        foreach ($nove as [$rok, $era, $titulek, $text, $zdroj, $jistota]) {
            $poradiRoku[$rok] = ($poradiRoku[$rok] ?? -1) + 1;
            $radky[] = ['rok' => $rok, 'rok_text' => '', 'era' => $era, 'titulek' => $titulek, 'text' => $text, 'foto' => '',
                        'foto_popisek' => '', 'zdroj' => $zdroj, 'jistota' => $jistota, 'visible' => 1, 'poradi' => $poradiRoku[$rok]];
        }
    }
    seed_log('Kronika: ' . seed_vloz('cltk_milniky', $radky) . ' milníků.');
}

/* ---------- triptych (úvod + historie) ---------- */
if (seed_prazdna('cltk_triptych')) {
    $t = [
        ['1954', 'Jaroslav Drobný', 'Wimbledon · dvouhra mužů · finále', 'historie-drobny.jpg', 'triptych-1954-drobny', '50% 25%',
         'Jaroslav Drobný s wimbledonským pohárem, 1954', 'Drobný', 'Rosewall', '13:11 4:6 6:2 9:7', 'Egypt',
         'Na Štvanici vyrostl jako syn správce, začínal jako sběrač míčků. Wimbledon vyhrál jako emigrant – a jako jediný muž v brýlích.'],
        ['1973', 'Jan Kodeš', 'Wimbledon · dvouhra mužů · finále', 'historie-kodes.jpg', 'triptych-1973-kodes', '50% 25%',
         'Jan Kodeš s wimbledonským pohárem, 1973', 'Kodeš', 'Metreveli', '6:1 9:8 (7:5) 6:3', 'Sparta',
         'Štvanický odchovanec, tehdy hráč Sparty. Trénoval ho štvanický Pavel Korda. Klubu později předsedal (1987–1990).'],
        ['2023', 'Markéta Vondroušová', 'Wimbledon · dvouhra žen · finále', 'historie-vondrousova.jpg', 'triptych-2023-vondrousova', '45% 30%',
         'Markéta Vondroušová s mísou Venus Rosewater Dish, Wimbledon 2023', 'Vondroušová', 'Jabeurová', '6:4 6:4', 'I. ČLTK Praha',
         'Na Štvanici od roku 2006, za klub registrována od sezóny 2011. První nenasazená vítězka Wimbledonu v historii.'],
    ];
    $radky = [];
    foreach ($t as [$rok, $jmeno, $disc, $soubor, $cil, $fokus, $alt, $vitez, $souper, $sety, $za, $zaText]) {
        $radky[] = ['rok' => $rok, 'jmeno' => $jmeno, 'disciplina' => $disc,
                    'foto' => seed_obrazek(seed_podklady('klient-pdf/' . $soubor), 'historie', $cil, 1800, 1800),
                    'fokus' => $fokus, 'alt' => $alt, 'vitez' => $vitez, 'souper' => $souper, 'sety' => sety_z_textu($sety),
                    'hral_za' => $za, 'hral_za_text' => $zaText, 'visible' => 1];
    }
    seed_log('Triptych: ' . seed_vloz('cltk_triptych', $radky) . ' panely.');
}

/* ---------- medailony „Od Žemly po Muchovou“ ---------- */
if (seed_prazdna('cltk_osobnosti')) {
    $o = [
        ['Ladislav Žemla', 'Legenda klubu', '1887–1955', 'Bronz OH Antverpy 1920 – mix se Skrbkovou; první mistr ČSR ve dvouhře 1920.',
         'assets/historie/hist-1910s-ladislav-zemla.jpg', '50% 8%', 'Ladislav Žemla', 'en.wikipedia – Ladislav Žemla', 'overeno', 'ověřeno'],
        ['Jaroslav Drobný', 'Čestný člen', '1921–2001', 'Wimbledon 1954 – finále s Rosewallem 13-11, 4-6, 6-2, 9-7. Mistr světa v ledním hokeji 1947.',
         'assets/historie/hist-1954-drobny-s-wimbledonskym-poharem.jpg', '50% 6%', 'Jaroslav Drobný s wimbledonským pohárem', 'tennisfame.com · en.wikipedia · ve štvanické historii', 'overeno', 'ověřeno'],
        ['Věra Suková-Pužejová', 'Legenda klubu', '1931–1982', 'Finále Wimbledonu 1962 – první čs. finalistka. Roland Garros 1957 – mix s Javorským.',
         'assets/historie/hist-1950s-vera-sukova-puzejova.jpg', '50% 6%', 'Věra Suková-Pužejová', 'en.wikipedia – Věra Suková', 'overeno', 'ověřeno'],
        ['Pavel Korda', 'Zasloužilý člen', '1935–2019', 'Trenér daviscupového týmu ČSSR 1970–1981 (Davis Cup 1980) a trenér Jana Kodeše.',
         'assets/historie/hist-1960s-pavel-korda-forhend.jpg', '44% 18%', 'Pavel Korda při forhendu', 'iROZHLAS/ČTK 5. 5. 2019', 'klub', 'vazba podle klubu'],
        ['Ing. Jan Kodeš', 'Čestný člen', 'nar. 1946', 'Wimbledon 1973 – finále s Metrevelim 6-1, 9-8 (7-5), 6-3. Prezident klubu 1987–1990.',
         'assets/historie/hist-1970s-jan-kodes-na-stvanici.jpg', '42% 14%', 'Jan Kodeš na Štvanici', 'tennisfame.com · ve štvanické historii', 'overeno', 'ověřeno'],
        ['Lucie Hradecká', 'Zasloužilá členka', 'za I. ČLTK 2003–2023', 'Roland Garros 2011 a US Open 2013 – čtyřhra s Hlaváčkovou; stříbro OH 2012.',
         'assets/historie/hist-2011-hradecka-pohar-roland-garros.jpg', '62% 22%', 'Lucie Hradecká s pohárem z Roland Garros', 'registr ČTS · en.wikipedia', 'overeno', 'ověřeno'],
        ['Markéta Vondroušová', 'Čestná členka', 'za I. ČLTK od 2011', 'Wimbledon 2023 – první nenasazená šampionka v historii (finále s Jabeurovou 6:4, 6:4).',
         'podklady:klient-pdf/historie-vondrousova.jpg', '45% 22%', 'Markéta Vondroušová s mísou Venus Rosewater Dish', 'registr ČTS · en.wikipedia · newsletter 130 let', 'overeno', 'ověřeno'],
        ['Karolína Muchová', 'Hráčka klubu', 'za I. ČLTK od 2019', 'US Open 2026 – mix s Menšíkem 6:3, 1:6, [10:6]; finále Wimbledonu 2026.',
         'assets/foto/clanky/karolina-muchova-ve-finale-wimbledonu.jpg', '48% 14%', 'Karolína Muchová', 'registr ČTS · usopen.org · wtatennis.com', 'overeno', 'ověřeno'],
    ];
    $radky = [];
    foreach ($o as [$jmeno, $kat, $roky, $cin, $zdroj, $fokus, $alt, $pramen, $jistota, $jistotaText]) {
        $cesta = str_starts_with($zdroj, 'podklady:') ? seed_podklady(substr($zdroj, 9)) : seed_navrhy($zdroj);
        $radky[] = ['jmeno' => $jmeno, 'kategorie' => $kat, 'roky' => $roky, 'cin' => $cin,
                    'foto' => seed_obrazek($cesta, 'osobnosti', 'osobnost-' . slugify($jmeno), 1400, 1600),
                    'fokus' => $fokus, 'alt' => $alt, 'pramen' => $pramen, 'jistota' => $jistota, 'jistota_text' => $jistotaText, 'visible' => 1];
    }
    seed_log('Osobnosti: ' . seed_vloz('cltk_osobnosti', $radky) . ' medailonů.');
}

/* ---------- Zlatá deska (záložky Varianty 4) ---------- */
if (seed_prazdna('cltk_deska_zaznamy')) {
    $d = [];
    // $pozdeji = řádek přibyl 8. 10. 2026 – vloží se až za ostatní (viz hlavička), pořadí na desce určuje poradi
    $add = static function (string $kat, string $skup, string $rok, string $jmeno, string $cin = '', string $pramen = '', string $metr = '', int $hist = 0, bool $pozdeji = false) use (&$d): void {
        $d[] = ['kategorie' => $kat, 'skupina' => $skup, 'rok' => $rok, 'jmeno' => $jmeno, 'cin' => $cin,
                'pramen' => $pramen, 'metr' => $metr, 'historie' => $hist, 'visible' => 1, '_pozdeji' => $pozdeji];
    };

    // Grand Slam – všichni, kdo na Štvanici vyrostli (sloupec metr = dřívější dvojí metr, web ho od 8. 10. 2026 nečte;
    // stvanice = klubová definice, klub = v barvách klubu v den triumfu)
    foreach ([
        ['1948', 'Jaroslav Drobný', 'Roland Garros · čtyřhra, s Bergelinem', 'pravděpodobně', 'stvanice'],
        ['1948', 'Jaroslav Drobný', 'Roland Garros · mix, s Canning Toddovou', 'pravděpodobně', 'stvanice'],
        ['1951', 'Jaroslav Drobný', 'Roland Garros · dvouhra', '', 'stvanice'],
        ['1952', 'Jaroslav Drobný', 'Roland Garros · dvouhra', '', 'stvanice'],
        ['1954', 'Jaroslav Drobný', 'Wimbledon · dvouhra', '', 'stvanice'],
        ['1957', 'V. Suková-Pužejová, J. Javorský', 'Roland Garros · mix', 'pravděpodobně', 'stvanice'],
        ['1970', 'Jan Kodeš', 'Roland Garros · dvouhra', '', 'stvanice'],
        ['1971', 'Jan Kodeš', 'Roland Garros · dvouhra', '', 'stvanice'],
        ['1973', 'Jan Kodeš', 'Wimbledon · dvouhra', '', 'stvanice'],
        ['1978', 'Pavel Složil', 'Roland Garros · mix, s Renátou Tomanovou', 'vazba podle klubu', 'stvanice'],
        ['1996', 'Daniel Vacek', 'Roland Garros · čtyřhra, s Kafelnikovem', '', 'stvanice klub'],
        ['1997', 'Daniel Vacek', 'Roland Garros · čtyřhra, s Kafelnikovem', '', 'stvanice klub'],
        ['1997', 'Daniel Vacek', 'US Open · čtyřhra, s Kafelnikovem', '', 'stvanice klub'],
        ['2006', 'Martin Damm', 'US Open · čtyřhra, s Paesem', '', 'stvanice klub'],
        ['2011', 'Lucie Hradecká', 'Roland Garros · čtyřhra, s Hlaváčkovou', '', 'stvanice klub'],
        ['2011', 'Iveta Benešová', 'Wimbledon · mix, s Melzerem', '', 'stvanice klub'],
        ['2013', 'Lucie Hradecká', 'US Open · čtyřhra, s Hlaváčkovou', '', 'stvanice klub'],
        ['2013', 'Lucie Hradecká', 'Roland Garros · mix, s Čermákem', '', 'stvanice klub'],
        ['2023', 'Markéta Vondroušová', 'Wimbledon · dvouhra', '', 'stvanice klub'],
        ['2026', 'Karolína Muchová', 'US Open · mix, s Jakubem Menšíkem', '', 'stvanice klub'],
    ] as [$rok, $jm, $cin, $pr, $metr]) $add('grandslam', 'hlavni', $rok, $jm, $cin, $pr, $metr);

    // Čestní členové
    $add('cestni', 'hlavni', '', 'Jaroslav Drobný', 'Wimbledon 1954 · Roland Garros 1951 a 1952');
    $add('cestni', 'hlavni', '', 'Ing. Jan Kodeš', 'Wimbledon 1973 · Roland Garros 1970 a 1971');
    $add('cestni', 'hlavni', '', 'Prof. Ing. Václav Klaus, CSc.', 'čestný člen od roku 1993 · prezident klubu 2011–2022');
    $add('cestni', 'hlavni', '', 'Markéta Vondroušová', 'Wimbledon 2023');
    foreach (['Josef Cífka', 'JUDr. Václav Důras', 'Dr. Jiří Guth', 'Marian šl. z Hochinfelsů', 'Josef L. Rössler-Ořovský',
              'Josef Šeyd', 'Ing. Ludvík Šimek', 'Mag. Pharm. Antonín Toman', 'Jindřich rytíř Vojáček'] as $jm) {
        $add('cestni', 'jmena', '', $jm);
    }

    // Mistři republiky (smíšená družstva)
    $add('mistri', 'hlavni', '2019', 'I. ČLTK Praha', 'extraliga · finále 18. 12., Přerov 5:2');
    $add('mistri', 'hlavni', '2018', 'I. ČLTK Praha', 'extraliga · finále 19. 12. v Říčanech, Prostějov 5:4 – první titul po 28 letech');
    $add('mistri', 'hlavni', '1990', 'I. ČLTK Praha', 'mistrovský titul v roce návratu ke jménu klubu');
    $add('mistri', 'hlavni', '1975', 'TJ Dopravní podnik', 'mistrovský titul');
    $add('mistri', 'hlavni', '1966, 1968', 'Motorlet Praha', '2 tituly', 'podle klubu', '', 0, true);
    $add('mistri', 'hlavni', '1956–1965', 'Spartak Praha Motorlet', '10 titulů', 'podle klubu');

    // Olympijské hry
    $add('oh', 'hlavni', '2021', 'Markéta Vondroušová', 'olympijské stříbro · dvouhra, Tokio');
    $add('oh', 'hlavni', '2016', 'Lucie Hradecká', 'olympijský bronz');
    $add('oh', 'hlavni', '2012', 'Lucie Hradecká', 'olympijské stříbro');
    $add('oh', 'hlavni', '1948', 'Jaroslav Drobný', 'stříbro ZOH · lední hokej', 've štvanické historii', '', 1);
    $add('oh', 'hlavni', '1906', 'Ladislav a Zdeněk Žemlovi', 'bronz ve čtyřhře, olympijské mezihry v Aténách', 'MOV je neuznává', '', 1);

    // Zasloužilí členové (34 jmen podle seznamu klubu)
    foreach (['JUDr. Jaromír Bečka', 'Ing. Pavel Benda', 'Gustav Bürgermeister', 'Jiří Javorský', 'Ladislav Hecht', 'Jiří Hřebec',
              'Ing. Pavel Huťka', 'JUDr. Václav Kliment', 'Pavel Korda', 'Marie Neumannová-Pinterová', 'Vladislav Šavrda',
              'Ing. Petr Šimůnek', 'Miloš Šolc', 'Milan Šrejber', 'Petr Štrobl', 'Ing. Ladislav Týra', 'Vlasta Vopičková',
              'Renata Wikartová', 'Ing. František Stejskal', 'Jana Pikorová', 'Magdaléna Zemanová', 'Iva Budařová-Šimůnková',
              'Martin Damm', 'Sandra Kleinová', 'Jan Hernych', 'Jan Vacek', 'Milan Vopička', 'Lucie Hradecká', 'Iveta Benešová',
              'David Rikl', 'Ing. Jaroslav Jandus', 'Daniel Vaněk', 'JUDr. Zdeněk Krampera', 'Jiří Fencl in memoriam'] as $jm) {
        $add('zasluzili', 'jmena', '', $jm);
    }

    // Prezidenti
    foreach ([
        ['2022–', 'Ing. Petr Šimůnek', 'současný prezident'],
        ['2011–2022', 'Prof. Ing. Václav Klaus, CSc.', '11 let'],
        ['1990–2011', 'Ing. František Stejskal', '21 let'],
        ['1987–1990', 'Ing. Jan Kodeš', '3 roky'],
        ['1980–1986', 'Ing. Miroslav Nárovec', '6 let'],
        ['1972–1980', 'JUDr. Václav Kliment', '8 let'],
        ['1969–1971', 'Ing. Eduard Vít', '2 roky'],
        ['1966–1969', 'JUDr. František Cvetler', '3 roky'],
        ['1957–1966', 'Bedřich Syřínek', '9 let'],
        ['1948–1956', 'JUDr. Vladimír Štůla', '8 let'],
        ['1938–1948', 'Ing. Jaromír Bečka', '10 let'],
        ['1929–1938', 'Karel Robětín', '9 let'],
    ] as [$rok, $jm, $cin]) $add('prezidenti', 'hlavni', $rok, $jm, $cin);
    foreach (['Karel Cífka', 'Josef Rössler-Ořovský', 'Prof. Ing. Ladislav Šimek', 'Mag. Pharm. Antonín Toman', 'JUDr. Václav Důras',
              'Marian Rombald z Hochinfelsen', 'Adolf Solnář', 'PhDr. Jaroslav Just', 'JUDr. Eduard Just'] as $jm) {
        $add('prezidenti', 'jmena', '', $jm, '', '', '', 0, $jm === 'Prof. Ing. Ladislav Šimek');
    }

    $poradi = [];
    foreach ($d as &$x) { $k = $x['kategorie'] . '|' . $x['skupina']; $poradi[$k] = ($poradi[$k] ?? -1) + 1; $x['poradi'] = $poradi[$k]; }
    unset($x);
    // řádky z 8. 10. 2026 až na konec (id ostatních jako na serveru), pořadí zůstává podle poradi
    usort($d, static fn(array $a, array $b): int => (int)$a['_pozdeji'] <=> (int)$b['_pozdeji']);
    $d = array_map(static function (array $x): array { unset($x['_pozdeji']); return $x; }, $d);
    seed_log('Zlatá deska: ' . seed_vloz('cltk_deska_zaznamy', $d) . ' záznamů.');
}
