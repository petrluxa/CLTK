<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Doplňkové bloky stránek sekce Areál a služby, Restaurace, Prague Open a Kontakt
   (šablony areal.php, cenik-kurtu.php, privatni-treneri.php, body-solution.php,
   sportovni-lekarstvi.php, prague-open.php, kontakt.php). Základní bloky (uvod,
   kurty, plan, prijezd, pravidla, vitezove …) jsou v 60-bloky.php – tady jen ty,
   které ty stránky čtou navíc. Klíče se s 60-bloky.php nesmí překrývat
   (tahle sada běží dřív a vložený blok už 60-bloky nepřepíše).

   Fakta jen z ověřených podkladů: cltk-navrhy/README.md, obsah.json → areal
   (uzavirky_2026, oteviraci_doby, leto_zima_fotky), prague_open; ZADANI.md §4.2
   (uzávěrka restaurace 1.–8. 10. 2026). Co chybí, má doplni_klub = 1.
   Vkládá se jen dvojice stránka + klíč, která ještě není – co klub upravil, zůstane. */

$n = 0;
$b = static function (string $stranka, string $klic, array $data, int $poradi) use (&$n): void {
    if (seed_blok($stranka, $klic, $data, $poradi)) $n++;
};

/* ================= AREÁL A SLUŽBY (areal.php) ================= */
$b('areal', 'casy', ['stitek' => 'Provoz', 'nadpis' => 'Otevírací <em>doby</em>',
    'perex' => 'Časy jednotlivých služeb. Další doplní klub.'], 10);

/* Uzávěrky: co položka seznamu, to uzávěrka – datum tučně na začátku
   („<strong>16.–22. 8. 2026</strong> důvod“). Šablona podle data sama pozná,
   co právě platí, co teprve přijde a co už proběhlo. */
$b('areal', 'uzavirky', ['stitek' => 'Uzávěrky', 'nadpis' => 'Plánované <em>uzávěrky</em>',
    'perex' => 'Kdy je areál nebo jeho část uzavřená kvůli turnajům a akcím.',
    'text' => '<ul>'
        . '<li><strong>20.–26. 7. 2026</strong> Livesport Prague Open (WTA 250)</li>'
        . '<li><strong>1.–2. 8. 2026</strong> Oktagon 92 – od 14:00 uzavřeny šatny a fitness, od 10:00 vnitřní parkoviště a oblouky viaduktu; vše opět přístupné 2. 8. od 10:00</li>'
        . '<li><strong>16.–22. 8. 2026</strong> Sekyra Group Prague Open 2026</li>'
        . '<li><strong>1.–8. 10. 2026</strong> Klubová restaurace uzavřena</li>'
        . '</ul>'], 11);

$b('areal', 'sluzby', ['stitek' => 'Služby v areálu', 'nadpis' => 'Všechno na jednom <em>ostrově</em>.',
    'perex' => 'Bazén, wellness, fitness, fyzioterapie, restaurace Tiebreak s terasou, tenis shop, beach volejbal i firemní akce – pár minut od centra Prahy.'], 12);

/* Letecké snímky vedle plánu – přepínají se spolu s plánem Léto / Zima */
$b('areal', 'letecky-leto', ['nadpis' => 'Léto shora',
    'foto' => seed_obrazek(seed_navrhy('assets/foto/letecky-kurty-leto-shora.jpg'), 'bloky', 'areal-letecky-leto'),
    'foto_popisek' => 'Léto shora: antukové kurty, bazén a centrální dvorec'], 13);
$b('areal', 'letecky-zima', ['nadpis' => 'Zima shora',
    'foto' => seed_obrazek(seed_navrhy('assets/foto/letecky-zimni-haly-shora.jpg'), 'bloky', 'areal-letecky-zima'),
    'foto_popisek' => 'Zima shora: přetlakové haly nad kurty a centrálním dvorcem'], 14);

/* ================= CENÍK KURTŮ (cenik-kurtu.php) ================= */
$b('cenik-kurtu', 'ceniky', ['stitek' => 'Léto a zima', 'nadpis' => 'Ceny za hodinu i za <em>sezónu</em>.'], 10);
$b('cenik-kurtu', 'kalkulacka', ['stitek' => 'Kalkulačka', 'nadpis' => 'Kolik stojí <em>hodina</em>.',
    'perex' => 'Vyberte sezónu, kurty, čas a formu. Přepínač „Jsem člen klubu“ ukáže členskou cenu a kolik ušetříte.'], 11);
$b('cenik-kurtu', 'doplnkove', ['stitek' => 'Doplňkové služby', 'nadpis' => 'Hřiště, bazén a <em>fitness</em>.'], 12);
$b('cenik-kurtu', 'rezervace', ['stitek' => 'Rezervace', 'nadpis' => 'Kurt si zarezervujete <em>online</em>.',
    'perex' => 'V rezervačním systému klubu, nebo na recepci po telefonu i e-mailem.'], 13);

/* ================= PRIVÁTNÍ TRENÉŘI, BODY SOLUTION, SPORTOVNÍ LÉKAŘSTVÍ ================= */
$b('privatni-treneri', 'treneri', ['stitek' => 'Trenéři', 'nadpis' => 'Trenéři pro <em>rekreační</em> hráče.'], 10);
$b('privatni-treneri', 'kontakt', ['nadpis' => 'Ceny a <em>kontakt</em>',
    'perex' => 'Ceny hodin a kontakty na jednotlivé trenéry doplní klub.', 'doplni_klub' => 1], 11);
$b('body-solution', 'kontakt', ['nadpis' => 'Kontakt a <em>objednání</em>',
    'perex' => 'Kontakt, ordinační hodiny a způsob objednání doplní klub.', 'doplni_klub' => 1], 10);
$b('sportovni-lekarstvi', 'kontakt', ['nadpis' => 'Kontakt a <em>objednání</em>',
    'perex' => 'Kontakt, ordinační hodiny a způsob objednání doplní klub.', 'doplni_klub' => 1], 10);

/* ================= PRAGUE OPEN (prague-open.php) ================= */
/* Ročník v kostce: co položka, to údaj – název tučně na začátku. */
$b('prague-open', 'rocnik', ['stitek' => 'Ročník 2026', 'nadpis' => 'Sekyra Group Prague Open 2026 by Advantage Cars',
    'text' => '<ul>'
        . '<li><strong>Termín</strong> 16.–22. 8. 2026</li>'
        . '<li><strong>Kategorie</strong> ATP Challenger 75 (muži) a ITF World Tennis Tour W50 (ženy)</li>'
        . '<li><strong>Povrch</strong> antuka</li>'
        . '<li><strong>Dotace</strong> muži 97 640 EUR, ženy 40 000 USD</li>'
        . '<li><strong>Vstup</strong> zdarma</li>'
        . '<li><strong>Pořadatel</strong> I. Český Lawn-Tennis Klub Praha a společnost Perinvest</li>'
        . '</ul>'], 10);
$b('prague-open', 'historie', ['stitek' => 'Historie turnaje', 'nadpis' => 'Prague Open na <em>Štvanici</em>.',
    'perex' => 'Od roku 2001 se turnaj hraje znovu na Štvanici a od tohoto roku klub počítá ročníky.',
    'text' => '<ul>'
        . '<li><strong>1991–1999</strong> Prague Challenger na Štvanici – první ročník vyhrál Jan Kodeš ml.</li>'
        . '<li><strong>Od 2001</strong> ECM Cup, ECM Prague Open (2003–2008), Advantage Cars Prague Open (2013–2019), I.ČLTK Prague Open (2020–2022), Advantage Cars Prague Open (2023–2025), od 2026 Sekyra Group Prague Open</li>'
        . '<li><strong>Vítězové</strong> mj. Sjeng Schalken 2003, Jan Hernych 2004, 2005 a 2008, Diego Schwartzman 2014, Stan Wawrinka 2020, Jiří Veselý 2024, Filip Misolic 2025 a Jan Kumstát 2026</li>'
        . '<li><strong>Ženy</strong> WTA ECM Prague Open 2005–2010 (mj. Safinová 2005, Zvonarevová 2008), od 2011 turnaj ITF – Lucie Hradecká 2011, Markéta Vondroušová 2017 (finále s Karolínou Muchovou 7:5, 6:1)</li>'
        . '</ul><p>Přehled podle Wikipedie a klubových zpráv.</p>',
    'foto' => seed_obrazek(seed_navrhy('assets/foto/prague-open-2026-stadion.jpg'), 'bloky', 'prague-open-2026-stadion'),
    'foto_popisek' => 'Centrální dvorec během Sekyra Group Prague Open 2026 · foto fotogalerie klubu'], 11);

/* ================= KONTAKT (kontakt.php) ================= */
$b('kontakt', 'lide', ['stitek' => 'Lidé a kontakty', 'nadpis' => 'Na koho se <em>obrátit</em>.',
    'odkaz' => 'vedeni.php', 'odkaz_text' => 'Výkonný výbor a vedení klubu'], 10);
$b('kontakt', 'prijezd', ['stitek' => 'Adresa a příjezd', 'nadpis' => 'Jak se k nám <em>dostanete</em>'], 11);
$b('kontakt', 'fakturace', ['stitek' => 'Fakturační údaje', 'nadpis' => 'Fakturační údaje a <em>účty</em>'], 12);
$b('kontakt', 'dokumenty', ['stitek' => 'Dokumenty', 'nadpis' => 'Dokumenty <em>klubu</em>'], 13);

seed_log('Bloky areálu, ceníku, Prague Open a kontaktu: ' . $n . ' nových.');
