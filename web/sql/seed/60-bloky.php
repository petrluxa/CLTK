<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Textové bloky podstránek (modul Stránky). Klíč „uvod“ = úvodní hlavička
   stránky, další klíče podle sekcí. Texty jen z ověřených podkladů
   (README, obsah.json, Varianta 4); co chybí, má doplni_klub = 1.
   Vkládá se jen dvojice stránka + klíč, která ještě není. */

$n = 0;
$b = static function (string $stranka, string $klic, array $data, int $poradi = 0) use (&$n): void {
    if (seed_blok($stranka, $klic, $data, $poradi)) $n++;
};

/* ================= ÚVODNÍ STRÁNKA ================= */
$b('index', 'uvod', ['stitek' => 'Ostrov Štvanice, Praha 7', 'nadpis' => 'Tenis na ostrově uprostřed Prahy od roku <em>1893</em>.',
    'odkaz' => 'clenstvi.php', 'odkaz_text' => 'Stát se členem', 'odkaz2' => 'cenik-kurtu.php', 'odkaz2_text' => 'Ceník kurtů'], 0);
$b('index', 'aktuality', ['stitek' => 'Aktuality z klubu'], 1);
$b('index', 'vysledky', ['stitek' => 'Aktuální výsledky našich hráčů'], 2);
$b('index', 'kalendar', ['stitek' => 'Klubový kalendář', 'nadpis' => 'Na Štvanici se <em>potkáváme</em>.', 'perex' => 'Klubový rok – vyberte událost'], 3);
$b('index', 'clenstvi', ['nadpis' => 'Členem se může stát <em>každý</em>.', 'odkaz' => 'clenstvi.php', 'odkaz_text' => 'Stát se členem',
    'foto' => seed_obrazek(seed_podklady('klient-pdf/clenstvi-vstup-cedule.jpg'), 'bloky', 'clenstvi-vstup-cedule'),
    'foto_popisek' => 'Vstup do areálu I. Českého Lawn-Tennis Klubu Praha'], 4);
$b('index', 'sluzby', ['stitek' => 'Služby v areálu'], 5);
$b('index', 'historie', ['stitek' => 'Naše historie', 'odkaz' => 'historie.php', 'odkaz_text' => 'Kompletní historie'], 6);
$b('index', 'partneri', ['stitek' => 'Partneři'], 7);

/* ================= KLUB ================= */
$b('klub', 'uvod', ['stitek' => 'Klub', 'nadpis' => 'Nejstarší tenisový klub v&nbsp;<em>Praze</em>.',
    'perex' => 'I. Český Lawn-Tennis Klub Praha byl založen v roce 1893 a na ostrově Štvanice sídlí od roku 1901. V klubu paralelně funguje závodní i rekreační tenis a členem se může stát každý.',
    'odkaz' => 'clenstvi.php', 'odkaz_text' => 'Stát se členem', 'odkaz2' => 'historie.php', 'odkaz2_text' => 'Historie klubu'], 0);
$b('klub', 'o-klubu', ['nadpis' => 'Klub uprostřed <em>města</em>',
    'text' => '<p>Areál na ostrově Štvanice nabízí 19 tenisových kurtů – v létě 13 venkovních antukových a 3 s tvrdým povrchem, k tomu 2 kurty v pevné hale a velký centrální dvorec. V zimě je pod halami 12 krytých kurtů.</p>'
            . '<p>Klub je jediným českým členem sdružení Centenary Tennis Clubs, které spojuje tenisové kluby starší 100 let, a v adresáři Českého tenisového svazu je veden jako klub č. 52.</p>'], 1);
/* jména a roky klubu podle klubu (postřehy klienta 8. 10. 2026) */
$b('klub', 'jmena', ['stitek' => 'Sedm jmen, jeden klub', 'nadpis' => 'Sedm jmen, jeden <em>klub</em>.',
    'text' => '<ul><li><strong>1893</strong> I. Český Lawn-Tennis Klub</li><li><strong>1948–1950</strong> DSO Spartak</li><li><strong>1951–1953</strong> Sokol Šverma Jinonice</li><li><strong>1954–1966</strong> Spartak Praha Motorlet</li><li><strong>1966–1969</strong> Motorlet Praha</li><li><strong>kolem 1969</strong> TJ Dopravní podnik</li><li><strong>1990</strong> I. ČLTK Praha</li></ul><p>Roky změn jmen uvádíme podle klubu, prameny se v nich liší.</p>'], 2);

/* ================= ČLENSTVÍ ================= */
$b('clenstvi', 'uvod', ['stitek' => 'Členství 2026', 'nadpis' => 'Členem se může stát <em>každý</em>.',
    'perex' => 'Členství v klubu I. ČLTK Praha je nabízeno široké veřejnosti a není podmíněno žádnými dodatečnými kritérii. O přijetí rozhoduje Výkonný výbor, zpravidla na nejbližším zasedání.',
    'odkaz' => '#prihlaska', 'odkaz_text' => 'Vyplnit přihlášku'], 0);
$b('clenstvi', 'v-cene', ['nadpis' => 'V ceně členství',
    'text' => '<ul><li>venkovní kurty v letní sezóně zdarma – 13 antukových (3 s osvětlením) a 3 tvrdé</li><li>venkovní bazén s travnatou plochou</li><li>fitness centrum</li><li>regenerace: vířivka, sauna, infrasauna, odpočívárna</li><li>beach volejbal a multifunkční hřiště (zdarma, hrají-li jen členové)</li><li>klubové akce: celosezónní soutěž pro rekreační hráče, klubové dny, mikulášská besídka, vánoční večírek, golfový turnaj</li><li>klubový časopis I.ČLTK Revue (2× ročně) a dvouměsíčník I.ČLTK Newsletter, i v angličtině</li><li>hra zdarma v klubech Centenary Tennis Clubs po doporučení generálního manažera</li></ul>'], 1);
$b('clenstvi', 'zvyhodneni', ['nadpis' => 'Výhody členů v zimě',
    'text' => '<ul><li>přednostní rezervace 12 krytých kurtů</li><li>zvýhodněné ceny hodin i celé zimní sezóny</li><li>sleva v klubovém shopu</li><li>pronájem venkovního dvorce pro hosta 100 Kč/hod</li><li>host do fitness nebo k bazénu 400 Kč/den</li></ul>'], 2);
$b('clenstvi', 'postup', ['nadpis' => 'Jak se stát <em>členem</em>',
    'text' => '<ol><li><strong>Žádost</strong> – přihláška až pro pět osob; u osob mladších 15 let údaje a souhlas zákonného zástupce, právnická osoba přikládá výpis z rejstříku.</li><li><strong>Výkonný výbor</strong> – členství vzniká dnem jeho rozhodnutí, zpravidla na nejbližším zasedání.</li><li><strong>Platba</strong> – v kanceláři (Eva Štefková, Po–Pá 9:00–17:00), na recepci nebo převodem na účet 1471509/0300, zpráva „jméno – členství“.</li><li><strong>Členská karta</strong> – hrající členové ji dostanou pro vstup do šaten a fitness.</li></ol>'], 3);
$b('clenstvi', 'druhy', ['nadpis' => 'Druhy členství podle stanov',
    'text' => '<ul><li><strong>Řádné</strong> – každá fyzická nebo právnická osoba.</li><li><strong>Zasloužilé</strong> – uděluje Výkonný výbor za úspěšnou sportovní reprezentaci klubu nebo dlouhodobou dobrovolnou práci pro klub.</li><li><strong>Čestné</strong> – uděluje Valná hromada za vítězství v grandslamové či olympijské dvouhře, také prezidentu a premiérovi ČR a primátorovi Prahy; čestní členové neplatí příspěvek.</li></ul>'], 4);
$b('clenstvi', 'prihlaska', ['stitek' => 'Přihláška', 'nadpis' => 'Přihláška do <em>klubu</em>',
    'perex' => 'Přihlášku posoudí Výkonný výbor. Ozveme se vám e-mailem nebo telefonicky s dalším postupem a platebními údaji.'], 5);

/* ================= HISTORIE ================= */
$b('historie', 'uvod', ['stitek' => 'Historie', 'nadpis' => 'Od roku 1893 na ostrovech <em>Prahy</em>.',
    'perex' => 'Založení v roce 1893, od roku 1901 Štvanice, tři wimbledonští vítězové, kteří tu vyrostli, a jména, která zůstávají. Co tvrdí jen klubové prameny, označujeme „podle klubu“.'], 0);
$b('historie', 'triptych', ['stitek' => 'Tři wimbledonské trávy', 'nadpis' => 'Tři vítězové Wimbledonu vyrostli <em>na Štvanici</em>.',
    'perex' => '1954, 1973 a 2023.'], 1);
$b('historie', 'kronika', ['stitek' => 'Kronika', 'nadpis' => 'Kronika <em>Štvanice</em>.',
    'perex' => 'Epochy nesou přibližné roky – data změn názvu klubu se v pramenech liší.'], 2);
/* Zlatá deska podle postřehů klienta 8. 10. 2026: úspěchy všech, kdo na Štvanici vyrostli, bez dvojího
   metru; roky udělení čestného a zasloužilého členství ani roky úřadu prvních prezidentů se doplňovat nebudou */
$b('historie', 'deska', ['stitek' => 'Síň slávy', 'nadpis' => 'Jména, která <em>zůstávají</em>.',
    'perex' => 'Na desce jsou úspěchy všech, kdo na Štvanici vyrostli – bez ohledu na to, za který klub zrovna hráli.'], 3);
/* perex je prostý text – nezlomitelná mezera jako znak U+00A0 („20 titulů“, „7 ve dvouhře“), v HTML textu jako &nbsp; */
$b('historie', 'deska-grandslam', ['nadpis' => 'Grand Slam',
    'perex' => "Všichni, kdo na Štvanici vyrostli – bez ohledu na to, za který klub v době vítězství hráli nebo kde žili: 20\u{00A0}titulů, z toho 7\u{00A0}ve dvouhře."], 4);
$b('historie', 'deska-cestni', ['nadpis' => 'Čestní členové',
    'perex' => 'Čestné členství uděluje Valná hromada – za vítězství v grandslamové či olympijské dvouhře, prezidentu a premiérovi ČR a primátorovi Prahy.',
    'text' => '<p>Historičtí čestní členové od roku 1893.</p>'], 5);
$b('historie', 'deska-mistri', ['nadpis' => 'Mistři republiky',
    'perex' => '16 titulů mistra republiky ve smíšených družstvech (podle klubu).',
    'text' => '<p>Rozpis: 10×&nbsp;Spartak Praha Motorlet (1956–1965), 2×&nbsp;Motorlet Praha (1966, 1968), 1975, 1990, 2018 a 2019. Nezávisle jsou doloženy tituly 1975, 1990, 2018 a 2019.</p>'], 6);
$b('historie', 'deska-oh', ['nadpis' => 'Olympijské hry', 'perex' => 'Tři olympijské medaile v barvách klubu a dvě ze štvanické historie.'], 7);
$b('historie', 'deska-zasluzili', ['nadpis' => 'Zasloužilí členové', 'perex' => '34 zasloužilých členů podle seznamu klubu.'], 8);
$b('historie', 'deska-prezidenti', ['nadpis' => 'Prezidenti', 'perex' => 'Prezidenti klubu od roku 1893 (podle klubu).',
    'text' => '<p>Prvních 36 let – roky úřadu neznáme.</p>'], 9);
$b('historie', 'osobnosti', ['stitek' => 'Osobnosti klubu', 'nadpis' => 'Od Žemly po <em>Muchovou</em>.',
    'perex' => 'Jen osobnosti s doloženou vazbou na klub. Podle registru ČTS získali hráči v barvách klubu v den triumfu 10 grandslamových titulů; tituly Drobného a Kodeše patří do štvanické historie.'], 10);

/* ================= VEDENÍ, CTC, REVUE ================= */
$b('vedeni', 'uvod', ['stitek' => 'Vedení klubu', 'nadpis' => 'Kdo klub <em>vede</em>.',
    'perex' => 'Prezidentem klubu je od 14. 7. 2022 Ing. Petr Šimůnek. Klub řídí devítičlenný Výkonný výbor, provoz zajišťuje kancelář klubu.'], 0);
$b('vedeni', 'dokumenty', ['nadpis' => 'Stanovy a dokumenty', 'perex' => 'Stanovy klubu a další dokumenty si přečtete přímo na webu – každý jde i vytisknout.'], 1);
$b('ctc', 'uvod', ['stitek' => 'Centenary Tennis Clubs', 'nadpis' => 'Jediný český člen <em>stoletých</em> klubů.',
    'perex' => 'Centenary Tennis Clubs sdružuje tenisové kluby starší 100 let. I. ČLTK Praha je členem od roku 2000 a jediným klubem z České republiky.'], 0);
$b('ctc', 'rodokmen', ['stitek' => 'Rodokmen stoletých', 'perex' => 'Rok založení 1893 sdílí klub s Lawn Tennis de Monte-Carlo. Kdo z nich byl první, prameny neurčí – přesné datum založení I. ČLTK neuvádějí. Roky podle webů jednotlivých klubů.'], 1);
$b('revue', 'uvod', ['stitek' => 'I.ČLTK Revue', 'nadpis' => 'Dvacet let Revue v jedné <em>poličce</em>.',
    'perex' => '41 čísel klubového časopisu od roku 2006, dvakrát ročně, a jubilejní speciál 1893–2023. Texty Jiljí Kubec, grafika Kateřina Kuželová, hlavní fotograf Martin Sidorják. Čísla 01/2016 a 02/2016 v archivu PDF chybí.'], 0);
$b('revue', 'newslettery', ['stitek' => 'Newsletter', 'nadpis' => 'Klubový <em>newsletter</em>.',
    'perex' => 'Klubový dvouměsíčník od roku 2015, česky i anglicky. Editor Mgr. Jan Pecha, Ph.D.'], 1);

/* ================= AREÁL A SLUŽBY ================= */
$b('areal', 'uvod', ['stitek' => 'Areál a služby', 'nadpis' => 'Devatenáct kurtů, bazén a&nbsp;<em>klid</em> uprostřed Prahy.',
    'perex' => '„Takto blízko centra Prahy naleznete jen velmi málo podobně příjemných a klidných míst…“',
    'odkaz' => 'cenik-kurtu.php', 'odkaz_text' => 'Ceník kurtů'], 0);
$b('areal', 'kurty', ['nadpis' => 'Kurty v létě a v <em>zimě</em>',
    'text' => '<p><strong>Léto:</strong> 13 venkovních antukových kurtů (3 s umělým osvětlením – kurty 2, 3, 4), 3 kurty s tvrdým povrchem (7, 8, 9), 2 kurty v pevné hale (P1, P2) a velký centrální dvorec C.</p>'
            . '<p><strong>Zima:</strong> 12 krytých kurtů – 10 s tvrdým povrchem (C, 1, 2, 3, 4, 7, 8, 9, P1, P2) a 2 antukové (5, 6). Kurty na Slavoji (10–16) jsou v zimě uzavřené.</p>'
            . '<p>Velký centrální dvorec patří Českému tenisovému svazu, ostatní kurty klubu.</p>'], 1);
$b('areal', 'plan', ['nadpis' => 'Plán areálu', 'perex' => 'Zleva: Slavoj s kurty 10–16, Negrelliho viadukt a parkoviště, hlavní budova s kurtem 1 a stadionem, bazén, kurty 5–9 a trojkurt.',
    'foto' => seed_obrazek(seed_navrhy('assets/foto/plan-arealu.jpg'), 'bloky', 'plan-arealu', 3600, 3600), 'foto_popisek' => 'Plán areálu, verze 09-2025',
    'odkaz' => 'dokument.php?d=plan-arealu', 'odkaz_text' => 'Plán areálu k vytištění'], 2);
$b('areal', 'prijezd', ['nadpis' => 'Jak se k nám <em>dostanete</em>',
    'perex' => 'Přístup z Hlávkova mostu (tramvaj č. 14, brána pro pěší) nebo z Karlína a Holešovic po lávce HolKa. Autem na vyhrazené parkoviště u Negrelliho viaduktu a zadním vchodem do areálu.'], 3);
$b('cenik-kurtu', 'uvod', ['stitek' => 'Ceník kurtů', 'nadpis' => 'Ceník <em>kurtů</em>.',
    'perex' => 'Kurty si rezervujete online nebo na recepci. Členové klubu hrají v letní sezóně na venkovních kurtech zdarma.',
    'odkaz' => 'clenstvi.php', 'odkaz_text' => 'Výhody členství'], 0);
$b('cenik-kurtu', 'pravidla', ['nadpis' => 'Pravidla rezervací',
    'text' => '<ul><li>Trvalé rezervace je nutné zrušit telefonicky nejpozději 24 hodin předem.</li><li>Náhrady za včas zrušené hodiny lze čerpat nejvýše 4 týdny.</li><li>Zvýhodněná cena předplatného na celé hrací období platí jen při platbě předem a za celé období.</li><li>Klub si vyhrazuje právo přesunout rezervaci na jiný dvorec nebo do jiné haly při zachování času a typu povrchu.</li></ul>'], 1);
$b('privatni-treneri', 'uvod', ['stitek' => 'Privátní trenéři', 'nadpis' => 'Privátní <em>trenéři</em>.',
    'perex' => 'Výuka tenisu pro rekreační hráče všech výkonnostních úrovní. Podrobnosti, ceny a kontakty doplní klub.', 'doplni_klub' => 1], 0);
$b('body-solution', 'uvod', ['stitek' => 'Body Solution', 'nadpis' => 'Body <em>Solution</em>.',
    'perex' => 'Ordinace Body Solution Clinic v areálu klubu. Podrobnosti, nabídku a kontakty doplní klub.', 'doplni_klub' => 1], 0);
$b('sportovni-lekarstvi', 'uvod', ['stitek' => 'Sportovní lékařství', 'nadpis' => 'Sportovní <em>lékařství</em>.',
    'perex' => 'Fyzioterapie, masáže a sportovní lékařství ve wellness centru areálu – pro závodní hráče, členy i veřejnost. Podrobnosti a kontakty doplní klub.', 'doplni_klub' => 1], 0);

/* ================= ZÁVODNÍ TENIS ================= */
$b('zavodni-tenis', 'uvod', ['stitek' => 'Závodní tenis', 'nadpis' => 'Celá pyramida <em>závodního</em> tenisu.',
    'perex' => 'I. ČLTK Praha je jedním ze tří klubů v Česku (se Spartou a Prostějovem), které mají celou pyramidu středisek Českého tenisového svazu: TSM, SVT a SCM.'], 0);
$b('zavodni-tenis', 'pyramida', ['nadpis' => 'Od školy po <em>extraligu</em>',
    'text' => '<ul><li><strong>Tenisová škola Markéty Vondroušové</strong> – 3–9 let</li><li><strong>TSM</strong> – tréninkové středisko mládeže, 10–14 let, hlavní trenér Antonín Štěpánek</li><li><strong>SVT</strong> – středisko vrcholového tenisu, 15–18 let, hlavní trenér Daniel Vaněk</li><li><strong>SCM</strong> – sportovní centrum mládeže, 15–21 let, hlavní trenér Zdeněk Kubík; vedoucím SCM je podle klubu sportovní ředitel Petr Vaníček</li><li><strong>Extraliga, WTA a ATP</strong></li></ul>'], 1);
$b('zavodni-tenis', 'cisla', ['nadpis' => 'Sezóna 2026 v <em>číslech</em>',
    'text' => '<ul><li>26 reprezentantů České republiky ze 146</li><li>121 hráčů v žebříčcích ČTS (léto 2026)</li><li>4 z TOP 10 českého žebříčku žen (léto 2026)</li><li>MČR družstev 2026: babytenis 2. místo, starší žactvo 3. místo, mladší žactvo 3. místo, dorost 3. místo</li></ul>'], 2);
$b('zavodni-tenis', 'extraliga', ['nadpis' => 'Extraliga smíšených <em>družstev</em>',
    'text' => '<p>Mistr ČR 2018 (finále 19. 12. v Říčanech, Prostějov 5:4 – první titul po 28 letech) a 2019 (finále 18. 12., Přerov 5:2). Finalista 2011 a 2015. Kapitáni týmu Petr Vaníček a Ivo Minář.</p>'], 3);
$b('zavodni-tenis-treneri', 'uvod', ['stitek' => 'Trenérský tým', 'nadpis' => 'Trenéři <em>závodního</em> tenisu.',
    'perex' => 'Bývalí reprezentanti, daviscupoví hráči a trenéři, kteří vedli hráče světové špičky.'], 0);

/* ================= TENISOVÁ ŠKOLA ================= */
$b('tenisova-skola', 'uvod', ['stitek' => 'Tenisová škola', 'nadpis' => 'Tenisová škola Markéty <em>Vondroušové</em>.',
    'perex' => 'Tenisová škola je již více než 15 let přímou součástí I. ČLTK Praha a v roce 2023 s ní spojila své jméno wimbledonská vítězka Markéta Vondroušová. Je pro děti od 3 do 9 let; nábory jsou vždy na jaře a na podzim.',
    'odkaz' => 'tenisova-skola-ceniky.php', 'odkaz_text' => 'Ceník', 'odkaz2' => 'letni-kempy.php', 'odkaz2_text' => 'Letní kempy'], 0);
$b('tenisova-skola', 'kontakt', ['nadpis' => 'Kontakt',
    'text' => '<p>Mgr. Jan Pecha, Ph.D., vedoucí trenér Tenisové školy<br><a href="tel:+420721663118">+420 721 663 118</a> · <a href="mailto:pecha@cltk.cz">pecha@cltk.cz</a></p>'], 1);
$b('tenisova-skola-ceniky', 'uvod', ['stitek' => 'Tenisová škola', 'nadpis' => 'Ceník <em>tenisové školy</em>.',
    'perex' => 'Cena zahrnuje trenéra i pronájem kurtu. Základní členství Tenisové školy 1 000 Kč se hradí vždy na začátku roku.'], 0);
$b('tenisova-skola-rozvrhy', 'uvod', ['stitek' => 'Tenisová škola', 'nadpis' => 'Rozvrhy a <em>harmonogram</em>.',
    'perex' => 'Tréninky podle zimních rozvrhů od 29. 9. 2026 do 2. 4. 2027. Změny vyhrazeny.'], 0);
/* text bloku (informace pro rodiče, kontaktní trenéři kurtů) doplní sada 95-archiv-pdf z datového souboru migrace */
$b('tenisova-skola-rozvrhy', 'rozvrhy', ['nadpis' => 'Rozvrhy <em>tréninků</em>',
    'perex' => 'Týdenní rozvrh Tenisové školy Markéty Vondroušové po kurtech a hodinách. Jména dětí na webu nejsou. Změny vyhrazeny.'], 1);
$b('tenisova-skola-treneri', 'uvod', ['stitek' => 'Tenisová škola', 'nadpis' => 'Trenéři <em>tenisové školy</em>.',
    'perex' => 'Trenérský tým Tenisové školy Markéty Vondroušové.'], 0);
$b('letni-kempy', 'uvod', ['stitek' => 'Tenisová škola', 'nadpis' => 'Letní kempy <em>2026</em>.',
    'perex' => 'Šest pětidenních kempů od 29. 6. do 28. 8. 2026 pro děti zhruba od 4 do 9 let (minitenis, střední kurt, babytenis). Vedou je profesionální trenéři I. ČLTK Praha.',
    'odkaz' => 'https://forms.gle/JvMQNKJJmoBareVa7', 'odkaz_text' => 'Přihláška na kemp'], 0);
$b('letni-kempy', 'informace', ['nadpis' => 'Co je dobré <em>vědět</em>',
    'text' => '<ul><li>Podmínkou je alespoň roční pravidelná tenisová příprava; úplní začátečníci po předchozí domluvě.</li><li><strong>Varianta A celodenní</strong> (8:30–16:30): 2 fáze tenisu a 2 fáze kondiční přípravy, pro děti zhruba 5–9 let, skupiny zpravidla po 3 až 4.</li><li><strong>Varianta B dopolední</strong> (8:30–13:30): 1 fáze tenisu, skupinový trénink, pro děti kolem 5 let.</li><li>V ceně je oběd a pitný režim.</li><li>S sebou: raketa, sportovní oblečení, plavky a ručník, obuv na antuku a pevný povrch, láhev s nápojem, svačinka.</li><li>Platba až po potvrzení e-mailem, převodem na účet 312451935/0300, variabilní symbol = rodné číslo dítěte, zpráva: jméno a číslo termínu.</li></ul>'
            . '<p>Kontakt: Mgr. Jan Pecha, Ph.D., <a href="tel:+420721663118">721 663 118</a>, <a href="mailto:pecha@cltk.cz">pecha@cltk.cz</a></p>'], 1);

/* ================= RESTAURACE, PRAGUE OPEN, KONTAKT ================= */
$b('restaurace', 'uvod', ['stitek' => 'Restaurace', 'nadpis' => 'Restaurace <em>Tiebreak</em>.',
    'perex' => 'Od 1. 11. se můžete těšit na novou klubovou restauraci Tiebreak. Vlastní web restaurace připravujeme.',
    'foto' => seed_obrazek(seed_navrhy('assets/foto/terasa-restaurace-stoly.jpg'), 'bloky', 'restaurace-terasa'), 'foto_popisek' => 'Terasa klubové restaurace'], 0);
$b('restaurace', 'informace', ['nadpis' => 'Terasa a <em>salónek</em>',
    'text' => '<p>Venkovní terasa restaurace prošla rekonstrukcí – nová terasa podle ateliéru Adama Fröhlicha má 60 míst. K restauraci patří salónek s dětským koutkem, u terasy je dětské hřiště. Restaurace zajistí i raut a firemní akce.</p>'], 1);
$b('restaurace', 'kontakt', ['nadpis' => 'Otevírací doba a kontakt', 'perex' => 'Otevírací dobu, kontakt a menu doplní klub.', 'doplni_klub' => 1], 2);
$b('prague-open', 'uvod', ['stitek' => 'Turnaj klubu', 'nadpis' => 'Prague Open, <em>26.</em> ročník.',
    'perex' => 'Sekyra Group Prague Open 2026 by Advantage Cars – ATP Challenger 75 a ITF W50 na antuce Štvanice, 16.–22. 8. 2026. Pořádá I. Český Lawn-Tennis Klub Praha se společností Perinvest. Vlastní web turnaje připravujeme.',
    'foto' => seed_obrazek(seed_navrhy('assets/foto/prague-open-2026-stin-hrace-antuka.jpg'), 'bloky', 'prague-open-2026-antuka'),
    'foto_popisek' => '© Sekyra Group Prague Open 2026, foto Martin Sidorják a Jan Pecha',
    'odkaz' => 'https://www.pragueopen.net/', 'odkaz_text' => 'pragueopen.net'], 0);
$b('prague-open', 'vitezove', ['nadpis' => 'Vítězové <em>2026</em>',
    'text' => '<ul><li><strong>Dvouhra mužů:</strong> Jan Kumstát – finále s Chun-Hsin Tsengem 5:7, 6:3, 6:0</li><li><strong>Čtyřhra mužů:</strong> Andrew Paulson (I. ČLTK) a Joran Vliegen – 6:4, 6:3</li><li><strong>Dvouhra žen:</strong> Jana Kovačková – finále s Xinyu Gao 6:1, 6:1</li><li><strong>Čtyřhra žen:</strong> Alena a Jana Kovačkovy – 6:2, 6:3</li></ul><p>Klub počítá ročníky od roku 2001. Vstup na turnaj je zdarma.</p>'], 1);
$b('kontakt', 'uvod', ['stitek' => 'Kontakt', 'nadpis' => 'Kontakt a <em>příjezd</em>.',
    'perex' => 'Ostrov Štvanice 38, 170 00 Praha 7. Recepce pro rezervace kurtů, kancelář klubu pro členství a stálé rezervace.'], 0);

/* ================= SPOLEČNÉ TEXTY ================= */
$b('formulare', 'souhlas', ['nadpis' => 'Souhlas se zpracováním údajů',
    'perex' => 'Souhlasím se zpracováním uvedených osobních údajů I. Českým Lawn-Tennis Klubem Praha za účelem vyřízení přihlášky.'], 0);
$b('404', 'uvod', ['stitek' => 'Chyba 404', 'nadpis' => 'Tuhle stránku jsme <em>nenašli</em>.',
    'perex' => 'Adresa se mohla změnit nebo stránka už neexistuje. Zkuste úvodní stránku nebo menu nahoře.',
    'odkaz' => 'index.php', 'odkaz_text' => 'Na úvodní stránku'], 0);

seed_log('Bloky stránek: ' . $n . ' nových.');
