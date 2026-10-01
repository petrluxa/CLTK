<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Areál a služby. Prvních 12 = štítky „Služby v areálu“ z PDF klienta (je_stitek = 1),
   každý vede na svou kotvu na areal.php. Texty a fakta z obsah.json → areal.sluzby,
   areal.oteviraci_doby, klub.prijezd a README (ověřená fakta). Kde údaj chybí,
   zůstává prázdný a stránka ukáže „doplní klub“. */

if (!seed_prazdna('cltk_sluzby')) return;

$ted = ted();
$f = static fn(string $s) => seed_obrazek(seed_navrhy('assets/foto/' . $s), 'sluzby', pathinfo($s, PATHINFO_FILENAME));

$sluzby = [
    // nazev, kotva, perex, text, fakta (řádky), foto, casy, odkaz, stitek
    ['Recepce', 'recepce', 'Rezervace kurtů a informace o areálu.',
     'Recepce rezervuje kurty po telefonu i e-mailem; online si kurt zarezervujete v rezervačním systému klubu.',
     "telefon +420 608 974 974\nrecepce@cltk.cz", $f('recepce.jpg'), '', '', 1],
    ['Venkovní bazén', 'bazen', 'Bazén s travnatou plochou – jen pro členy a jejich hosty.',
     'Nedílná součást štvanického areálu již více než 20 let – trávník, polohovatelná lehátka a občerstvení z klubové restaurace.',
     "provoz přibližně květen–září (podle počasí)\nčlenové zdarma, host člena 400 Kč/den\njen pro členy a jejich hosty", $f('bazen-lehatka-destniky.jpg'), 'cca květen–září', '', 1],
    ['Fitness', 'fitness', 'Fitness centrum pro závodní i rekreační hráče.',
     'Prostory otevřené v roce 2017 díky I. etapě nástavby na ochozu malého centrálního dvorce.',
     "členové zdarma (s omezením během klubové kondice)\nhost člena 400 Kč\notevřeno po dobu provozu budovy", $f('fitness-centrum.jpg'), '', '', 1],
    ['Regenerace a wellness', 'wellness', 'Vířivka, sauna s odpočívárnou, infrasauna a Kneippovy lázně.',
     'Wellness centrum s vířivou vanou Jacuzzi, saunou s odpočívárnou, infrasaunou, Kneippovými lázněmi a ice bath (jen pro závodní hráče). Otevřeno od března 2019 díky II. etapě nástavby na ochozu malého centru.',
     "všední dny 16:00–20:00, bez rezervace\njen pro členy, zdarma; rekreační členové max. 2× týdně\nkvěten–září saunu nahlásit alespoň hodinu předem", $f('wellness-virivka-lehatka.jpg'), 'všední dny 16:00–20:00', '', 1],
    ['Fyzioterapie a sportovní lékařství', 'fyzioterapie', 'Fyzioterapie, masáže a sportovní lékařství přímo v areálu.',
     'Prostory ve wellness centru: fyzioterapie Bc. Joseph B. Truesdale, sportovní lékařství Zdravý sport. Od června 2026 v areálu ordinuje Body Solution Clinic.',
     'pro závodní hráče, členy i veřejnost', '', '', 'sportovni-lekarstvi.php', 1],
    ['Tenis shop', 'tenis-shop', 'Vyplétání raket, Mizuno, Babolat a klubový merchandising.',
     'Od listopadu 2023 ve vestibulu: vyplétání raket, Mizuno, Babolat a klubový merchandising I. ČLTK.',
     "kontaktní osoba Tomáš Truneček\nsleva pro členy", $f('tenis-shop.jpg'), 'Po–Pá 9:00–12:00 a 13:00–17:00', '', 1],
    ['Salónek a dětský koutek', 'salonek', 'Salónek s dětským koutkem u klubové restaurace.',
     'Salónek s dětským koutkem navazuje na klubovou restauraci, u terasy je dětské hřiště.',
     '', $f('restaurace-salonek-detsky-koutek.jpg'), '', '', 1],
    ['Restaurace Tiebreak', 'restaurace', 'Klubová restaurace s venkovní terasou.',
     'Klubová restaurace s venkovní terasou – nová terasa podle ateliéru Adama Fröhlicha má 60 míst. Restaurace zajistí i raut a firemní akce.',
     '', $f('restaurace-tiebreak-interier.jpg'), '', 'restaurace.php', 1],
    ['Parkoviště', 'parkoviste', 'Vyhrazené parkoviště u Negrelliho viaduktu.',
     'Pro příjezd autem je lepší po sjezdu z magistrály areál zprava objet, zaparkovat na vyhrazeném parkovišti a do areálu vejít zadním vchodem. Parkuje se i pod oblouky Negrelliho viaduktu.',
     '', '', '', '', 1],
    ['Beach volejbal', 'beach-volejbal', 'Beachvolejbalové hřiště v zadní části areálu.',
     'Hřiště je v zadní části areálu za Negrelliho viaduktem (Slavoj); šatny a sprchy jsou v hlavní budově.',
     "pronájem 400 Kč/hod\nhrají-li jen členové, zdarma; člen s hosty o 25 % levněji", $f('beachvolejbal-hriste.jpg'), '', '', 1],
    ['Multifunkční hřiště (Slavoj)', 'hriste-slavoj', 'Multifunkční hřiště v zadní části areálu.',
     'Hřiště je v zadní části areálu za Negrelliho viaduktem (Slavoj); šatny a sprchy jsou v hlavní budově.',
     "pronájem 900 Kč/hod\nhrají-li jen členové, zdarma; člen s hosty o 25 % levněji", $f('multifunkcni-hriste.jpg'), '', '', 1],
    ['Multifunkční hřiště (u trojkurtu)', 'hriste-trojkurt', 'Menší multifunkční hřiště u trojkurtu.',
     '', '', '', '', '', 1],

    /* další služby jen na stránce Areál (bez štítku na úvodu) */
    ['Pronájem tenisových kurtů', 'kurty', 'Kurty se rezervují přes recepci nebo online; členové hrají v létě na venkovních kurtech zdarma.',
     'Veřejnost si může venkovní dvorce pronajmout denně od 7:00 do 22:00.',
     "členská rezervace max. týden dopředu, max. 1,5 hodiny denně, nanejvýš dvě platné rezervace, min. dvě jména\nv létě nejsou trvalé rezervace\nhrací jednotka zahrnuje úpravu dvorce",
     $f('kurty-antuka-stromy-leto.jpg'), 'denně 7:00–22:00', 'cenik-kurtu.php', 0],
    ['Výuka tenisu', 'vyuka', 'Výuka na všech výkonnostních úrovních.',
     'Tenisová škola pro děti od 3 do 9 let, závodní tenis a privátní trenéři pro rekreační hráče.',
     'dětské letní kempy (6 termínů)', $f('kurt-antuka-zivy-plot-tribuny.jpg'), '', 'privatni-treneri.php', 0],
    ['Firemní akce', 'firemni-akce', 'Kurty, zázemí, restaurace s terasou a doplňkové služby pro firmy.',
     'Kurty, zázemí šaten i společenských prostor, restaurace s terasou a rauty, doplňkově bazén, wellness a trenéři.',
     'firemní členství 40 000 Kč ročně (min. 2 přenosné klubové karty)', $f('terasa-promenada-hodiny.jpg'), '', '', 0],
    ['Klubové prostory', 'klubove-prostory', 'Vestibul, klubová místnost a recepce.',
     'Vestibul s velkoformátovou černobílou fototapetou, klubová místnost pro odpočinek, studium a schůzky a recepce. Ve foyer je koláž černobílých fotografií tenisových es a pamětní deska Jaroslava Drobného, odhalená 14. 6. 2012.',
     '', $f('klubove-prostory-lounge.jpg'), '', '', 0],
];

$radky = [];
foreach ($sluzby as $i => [$nazev, $kotva, $perex, $text, $fakta, $foto, $casy, $odkaz, $stitek]) {
    $radky[] = ['nazev' => $nazev, 'kotva' => $kotva, 'perex' => $perex, 'text' => $text, 'fakta' => $fakta, 'foto' => $foto,
                'fokus' => '50% 50%', 'casy' => $casy, 'odkaz' => $odkaz, 'je_stitek' => $stitek, 'visible' => 1, 'poradi' => $i,
                'created_at' => $ted, 'updated_at' => $ted];
}
seed_log('Areál a služby: ' . seed_vloz('cltk_sluzby', $radky) . ' služeb (12 štítků na úvodu).');
