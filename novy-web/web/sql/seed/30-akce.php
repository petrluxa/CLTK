<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Klubový kalendář 2026 – seznam z PDF klienta (ZADANI §8) + opravený text CTC U14.
   Přihlášení zapnuté u Klubových dnů a Vánočního večírku (ZADANI §4.6). */

if (!seed_prazdna('cltk_akce')) return;

$ted = ted();
$akce = [
    // nazev, mesic, od, do, termin_text, cas, misto, stitek, perex, prihlaseni, odkaz, odkaz_text, poznamka_interni
    ['Conseq Prague Open, ITF W75 v hale', 2, '2026-02-08', '2026-02-15', '', '', 'pevná hala, kurty P1 a P2', 'Turnaj',
     'Druhý ročník halového turnaje žen kategorie ITF W75 na Štvanici. Titul získala hráčka klubu Tereza Martincová, která prošla z kvalifikace.', 0, '', '', ''],
    ['Klubový den – čtyřhry, barbecue, kvíz', 5, '2026-05-24', null, '', '', 'areál klubu', 'Klubová akce',
     'Čtyřhry ve výkonnostních skupinách, podvečerní barbecue a hospodský kvíz.', 1, '', '', ''],
    ['Valná hromada', 6, '2026-06-25', null, '', '18:00', 'klubová restaurace', 'Klub',
     'Valná hromada I. ČLTK Praha.', 0, '', '', ''],
    ['Letní dětské kempy, 6 termínů', 7, '2026-06-29', '2026-08-28', '', '', 'areál klubu', 'Tenisová škola',
     'Šest pětidenních kempů pro děti zhruba od 4 do 9 let – celodenní i dopolední varianta, oběd a pitný režim v ceně.', 0, 'letni-kempy.php', 'Letní kempy', ''],
    ['Sekyra Group Prague Open, 26. ročník', 8, '2026-08-16', '2026-08-22', '', '', 'antukové kurty Štvanice', 'Turnaj',
     'Turnaj ATP Challenger 75 a ITF W50 na antuce, pořádaný klubem se společností Perinvest. Vstup zdarma.', 0, 'prague-open.php', 'Prague Open', ''],
    ['Klubový den', 9, '2026-09-06', null, '', '', 'areál klubu', 'Klubová akce',
     'Čtyřhry ve výkonnostních skupinách, podvečerní barbecue a hospodský kvíz.', 1, '', '', ''],
    ['Zimní sezóna v halách', 10, '2026-10-05', '2027-04-04', 'přetlakové haly od 5. 10.', '', 'přetlakové haly a pevná hala', 'Areál',
     'Přetlakové haly od 5. 10. 2026, pevná hala (kurty P1, P2) už od 28. 9. 2026. Zimní sezóna potrvá do 4. 4. 2027.', 0, 'cenik-kurtu.php', 'Ceník kurtů', ''],
    ['Tenisová škola podle zimních rozvrhů', 10, null, null, 'potvrzeno', '', '', 'Tenisová škola',
     'Tréninky Tenisové školy podle zimních rozvrhů od 29. 9. 2026 do 2. 4. 2027.', 0, 'tenisova-skola-rozvrhy.php', 'Rozvrhy', ''],
    ['CTC U14 – I. ČLTK Praha Cup', 11, null, null, '', '', '', 'Centenary Tennis Clubs',
     'Od roku 2008 se na Štvanici koná mezinárodní přátelské utkání dětí U14 v rámci asociace Centenary Tennis Clubs. Akce se zúčastní španělský Real Club de Tenis Barcelona, irský Carrickmines a belgický Royal Léopold Club.',
     0, 'ctc.php', 'Centenary Tennis Clubs', 'Ověřit s klubem název belgického klubu („Royal Léopold Club“ – v PDF „Real club de Leopold“) a termín.'],
    ['Mikulášská besídka', 12, null, null, '', '', 'klubová restaurace', 'Klubová akce',
     'Mikuláš, čert a anděl, básničky a sladkosti pro děti.', 0, '', '', 'Termín doplnit. Loni čt 4. 12. 2025 od 16:30 v klubové restauraci, přihlášky na pecha@cltk.cz.'],
    ['Večer talentů a Vánoční večírek v Letenském zámečku', 12, null, null, '', '', 'Letenský zámeček', 'Klubová akce',
     'Ocenění mladých hráčů klubu, shrnutí sezóny a předání Cen ankety Jaroslava Drobného.', 1, '', '', 'Termín doplnit. Loni Večer talentů 11. 12. 2025 a Vánoční večírek 12. 12. 2025 od 19:00.'],
    ['OSTRA Tenisová extraliga', 12, null, null, '', '', '', 'Závodní tenis',
     'Mistrovství republiky smíšených družstev. I. ČLTK Praha hraje semifinálovou skupinu.', 0, '', '', 'Termín doplnit.'],
];

$radky = [];
foreach ($akce as $i => [$nazev, $mesic, $od, $do, $termin, $cas, $misto, $stitek, $perex, $prihl, $odkaz, $odkazText, $pozn]) {
    $radky[] = [
        'nazev' => $nazev, 'rok' => 2026, 'mesic' => $mesic, 'datum_od' => $od, 'datum_do' => $do,
        'termin_text' => $termin, 'cas' => $cas, 'misto' => $misto, 'stitek' => $stitek,
        'perex' => $perex, 'popis' => '', 'foto' => '', 'odkaz' => $odkaz, 'odkaz_text' => $odkazText,
        'prihlaseni_povoleno' => $prihl, 'formular' => '', 'prihlaseni_do' => null, 'kapacita' => null,
        'poznamka_interni' => $pozn, 'visible' => 1, 'poradi' => $i, 'created_at' => $ted, 'updated_at' => $ted,
    ];
}
seed_log('Kalendář akcí: ' . seed_vloz('cltk_akce', $radky) . ' akcí.');
