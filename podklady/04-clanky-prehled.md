# 04 – Archiv článků (aktuality) cltk.cz – přehled

Zdroj: všechny články z `https://cltk.cz/cs/clanky/` (stav k 23. 9. 2026).
Data: `podklady/data/clanky.json` (238 článků, od nejnovějšího) · skript: `podklady/_raw/clanky/scrape.py` · pomocné statistiky: `podklady/_raw/clanky/stats.py`.
Fotky: `assets/foto/clanky/` (40 souborů, pole `foto_file` v JSON).

---

## 1. Rozsah a kvalita dat

| | |
|---|---|
| Počet článků na webu | **238** (sitemap.xml = 238 URL pod /cs/clanky/, výpis /cs/clanky/ = 24 stránek × max. 10 = 238; obě sady jsou totožné, žádný článek navíc) |
| Nejnovější | 10. 9. 2026 – „Justýna Reifová vyhrála turnaj ITF J30“ |
| Nejstarší | ID 6 „Lukáš Velík a Marek Pazdera v Rakovníku vítězně“ – v archivu hromadně 2. 7. 2019, podle textu událost „na konci dubna“ 2019 |
| Pokrytí | reálně **duben 2019 – září 2026** (7,5 roku). Starší zprávy (web 2015–2016 pod /home/, WordPress) na dnešním webu nejsou – existují jen ve web.archive.org (NEPROCHÁZENO). |
| Články, které z webu zmizely | **19** (2020–2025), známe jen titulek/datum/perex z archivovaných výpisů → `removed_articles_wayback` v JSON (např. „Češky bojovaly o účast ve finále BJKC Finals 2022“, „Reakce I.ČLTK Praha na článek … Seznam Zprávy ze 7. listopadu 2022“, „Klubový den 2022“, „BAZÉN ZNOVU OTEVŘEN“) |

**Pozor na data vydání.** CMS (MySUITU) hromadně přepsal data: **117 článků** má na webu datum **17. 3. 2023** (převod webu), 18 článků **22. 1. 2025**, 6 článků **6. 10. 2025**, a čl. ID 109 z října 2019 má 26. 1. 2024. Skutečná data jsem dohledal v archivovaných výpisech a kopiích článků na web.archive.org (105 snímků výpisů a úvodní strany + 107 kopií článků, cache v `_raw/clanky/wayback/`):

| `date_quality` | počet | význam |
|---|---|---|
| `web` | 96 | datum na stránce je věrohodné |
| `wayback` | 114 | původní datum z archivu (zdroj v `date_source`) |
| `wayback-hromadne (NEOVĚŘENO)` | 8 | i nejstarší kopie (18. 7. 2019) ukazuje hromadně 2. 7. 2019 – články vyšly dřív (duben–červen 2019) |
| `odhad-z-id (NEOVĚŘENO)` | 20 | nedohledáno; odhad podle sousedních ID, interval v `date_range_estimate` |

Další problémy starého obsahu: **63 článků** má v textu prázdné (ztracené) obrázky `//files.cltk.cz//w130/` (pole `images_broken`), **133 článků nemá žádný funkční obrázek**, 24 článků nemá perex (doplněn 1. odstavcem, `perex_source: "text"`), 4 články jsou prakticky prázdné (jen PDF nebo plakát). Fotogalerie klub neukládá na web, ale odkazuje na **cltk.dphoto.com** (8×) a jednou na cltk.rajce.idnes.cz.

## 2. Počty podle roku a kategorie

Kategorie jsem přidělil ručně po přečtení všech titulků a perexů (heuristika ve skriptu slouží jen pro budoucí články):
`vysledky-profi` = dospělí / profi okruh (WTA, ATP, ITF žen a mužů, GS, OH, BJK Cup, domácí turnaje dospělých) · `vysledky-mladez` = jednotlivci do 18 let (ITF Juniors, Tennis Europe, ČTS „A“, MČR mládeže) a juniorské reprezentace · `druzstva` = klubová družstva (extraliga, MČR družstev) · `klub-akce` = život klubu · `skolicka` = tenisová škola, babytenis, kempy · `turnaje-v-klubu` = turnaje pořádané na Štvanici · `ostatni`.

| rok | profi | mládež | družstva | klub/akce | školička | turnaje v klubu | ostatní | **celkem** | (+ smazané) |
|---|---|---|---|---|---|---|---|---|---|
| 2019 | 17 | 28 | 4 | 6 | 3 | 2 | 5 | **65** | – |
| 2020 | 10 | 12 | 1 | 7 | 1 | 6 | 2 | **39** | +6 |
| 2021 | 4 | 3 | 1 | 1 | 1 | 1 | 0 | **11** | +2 |
| 2022 | 0 | 0 | 0 | 1 | 0 | 1 | 0 | **2** | +7 |
| 2023 | 2 | 0 | 0 | 1 | 1 | 0 | 1 | **5** | +2 |
| 2024 | 7 | 18 | 1 | 3 | 3 | 1 | 1 | **34** | +1 |
| 2025 | 20 | 23 | 3 | 7 | 2 | 1 | 0 | **56** | +1 |
| 2026 | 13 | 7 | 1 | 1 | 2 | 2 | 0 | **26** (do 10. 9.) | – |
| **Σ** | **73** | **91** | **11** | **27** | **13** | **14** | **9** | **238** | 19 |

Postřehy: dvě silné vlny (2019–2020 a 2024–2026), mezi nimi útlum (2021–2023: 18 článků, a ještě 11 z tehdejších bylo později smazáno). Paradoxně **Wimbledon 2023 Vondroušové a finále Roland Garros Muchové** spadají do nejslabšího období webu. 69 % článků jsou výsledky hráčů (164/238), mládež mírně převažuje nad profíky.

## 3. Třicet nejvýznamnějších úspěchů v archivu

Seřazeno podle sportové váhy (ne podle data). Čísla a skóre přesně podle článků. Odkaz = `https://cltk.cz/cs/clanky/<slug>:<ID>/`.

1. **Markéta Vondroušová – vítězka Wimbledonu 2023** (dvouhra). Článek: „z pohledu štvanické historie tak navázala na triumf Jana Kodeše z roku 1973“; ve finále porazila Ons Jabeurovou, „po Janě Novotné a Petře Kvitové teprve třetí českou hráčkou, která získala Mísu Venus Rosewaterové“. – ID 247, 16. 7. 2023, …/marketa-vondrousova-vitezkou-wimbledonu-2023:247/
2. **Karolína Muchová (s Jakubem Menšíkem) – vítězka smíšené čtyřhry US Open 2026**, finále 6:3, 1:6, 10:6 proti Bencic/Cobolli; „premiérový titul z turnajů velké čtyřky v jakékoli disciplíně“; do turnaje s divokou kartou. – ID 385, 27. 8. 2026
3. **Muchová – finalistka Wimbledonu 2026**, „historicky vůbec první ryze české grandslamové finále“, prohra s Lindou Noskovou (Nosková vyhrála 6:2, 5:7, 6:3); Muchová se posune na 6. místo žebříčku (kariérní maximum). – ID 381, 14. 7. 2026
4. **Muchová – finalistka Roland Garros 2023**, prohra se světovou jedničkou Igou Šwiatekovou 2:6, 7:5, 4:6; posun na 16. místo. – ID 241, 12. 6. 2023
5. **Vondroušová – stříbrná medaile z OH Tokio 2020**; tisková konference po příletu v I.ČLTK Praha 2. 8. 2021. – ID 209, 1. 8. 2021 (wayback)
6. **Muchová – titul WTA 1000 Dauhá 2026**, finále s Victorií Mboko 6:4, 7:5; „druhý a dosud nejvýznamnější titul kariéry“. – ID 364, 16. 2. 2026
7. **Muchová – titul WTA 500 Bad Homburg 2026** (finále s Naomi Osakou, skreč za stavu 6:1, 1:0), třetí titul kariéry, první na trávě, návrat do top 10 (9. místo). – ID 377, 27. 6. 2026
8. **Vondroušová – titul WTA 500 Berlín 2025** (tráva), v semifinále porazila světovou jedničku Sabalenkovou, finále se Sin-jü Wang; „třetí titul na okruhu WTA“, skok ze 164. na 73. místo. – ID 323, 23. 6. 2025
9. **Muchová – první titul WTA, Soul 2019**, finále s Magdou Linette 6/1 6/1, posun na 37. místo. – ID 104, 26. 9. 2019 (wayback)
10. **Jonáš Forejtek – vítěz juniorského US Open 2019 (dvouhra)**, „po letošních vítězstvích ve čtyřhře na Australian Open a Wimbledonu … třetí grandslamový titul“. – ID 100, 9. 9. 2019 (wayback); čtyřhra Wimbledonu i v ID 74
11. **Muchová – semifinále Australian Open 2021** (cestou porazila světovou jedničku Bartyovou; posun na 22. místo) – ID 192; **semifinále US Open 2024** po desetiměsíční pauze (ID 285); **finále WTA 1000 Peking 2024** (výhra nad Sabalenkou, ID 293); **čtvrtfinále US Open 2025** (ID 345)
12. **Juniorské mistryně světa družstev 2019 (Prostějov)** – Linda Fruhvirtová, **Nikola Bartůňková**, Brenda Fruhvirtová; „Po 16 letech se tak titul světových šampionek vrací do České Republiky“; junioři (Vojtěch Petr, Lukáš Velík, Jakub Menšík) bronz. – ID 86, 16. 8. 2019
13. **Nikola Bartůňková – semifinále WTA 500 Guadalajara 2025** z divoké karty, výhra nad obhájkyní Frechovou, posun na 140. místo – ID 343; **debut na Australian Open 2026 až do 3. kola** (výhry nad Kasatkinou a olympijskou vítězkou Benčič) – ID 360; **finále WTA 125 Samsun** – ID 347; **nominace do Billie Jean King Cupu** (baráž Varaždín, kapitán Petr Pála) – ID 348; **titul ITF W75 Hechingen** – ID 332
14. **Darja Viďmanová – první titul WTA** (WTA 125 Figueira da Foz 2026), debut v top 100 (90. místo) – ID 375, 25. 6. 2026; **finále WTA 250 Memphis 2026** – ID 383 (článek uvádí posun „ze 114. místa … na 92. příčku“ – rozpor s ID 375, NEOVĚŘENO); v roce 2025 série titulů ITF (W35 Santo Domingo, W75 Sumter, **W100 Cary** – „třetí titul v řadě a skóre 15:0 na zápasy“, ID 326)
15. **Matěj Vocel – čtvrtfinále čtyřhry US Open 2025** (s Tomášem Macháčem, porazili druhé nasazené Arevalo/Pavić), posun na 85. místo – ID 341; první deblový titul ATP Challenger doma na Prague Open 2025 – ID 314
16. **Lucie Hradecká – titul ve čtyřhře WTA Cincinnati 2019** (s Andrejou Klepac, v semifinále porazily sestry Plíškovy) – ID 89; titul ve čtyřhře WTA Birmingham 2021 (s Marií Bouzkovou) – ID 206
17. **I.ČLTK Praha – vítěz MONETA Tenisové extraligy 2019 a obhájce titulu z 2018** (finále s TK PRECHEZA Přerov 5:2) – ID 132; v roce 2021 dělené 3. místo – ID 220
18. **Filip Ladman – mistr Evropy družstev do 16 let** (Summer Cup 2025, Le Touquet, finále s Německem 2:1) – ID 335
19. **Radek Chodora – mistr ČR do 18 let** (98. Pardubická juniorka 2025, dvouhra i čtyřhra; finále „čistě štvanickou bitvou“ proti Sebastianu Chodurovi 6:2 6:2) – ID 336
20. **Dorost I.ČLTK Praha – stříbro na MČR smíšených družstev 2025** (finále s TK Sparta Praha 2:5; „po dlouhé době finále“) – ID 344
21. **Tereza Martincová – vítězka Conseq Prague Open ´26** (ITF W75 na Štvanici, z kvalifikace, finále s Krausovou 6:3, 6:4; první titul od 2019) – ID 363; v roce 2021 v top 100 (87. místo) – ID 206
22. **Emily Bukalová – zlato na Olympiádě dětí a mládeže 2026** (reprezentovala Jihomoravský kraj), Filip Wollner stříbro – ID 376
23. **Forejtek – výhra nad Marinem Čiličem** při debutu na okruhu ATP (Sofie 2020, 6/3 6/2) – ID 187; titul ve čtyřhře ATP Challenger 125 Sevilla 2025 – ID 342
24. **Bartůňková & Amélie Šmejkalová – deblový pár roku 2019 Tennis Europe** – ID 129; Bartůňková **1. hráčkou žebříčku Tennis Europe** – ID 98
25. **Vondroušová – Sportovec roku 2023** („Triumf na slavném turnaji přispěl v roce 1973 ke korunovaci Jana Kodeše … letos Markétě Vondroušové“) – ID 255
26. **Denisa Žoldáková – finále čtyřhry juniorek Australian Open 2026** – ID 360; první profi titul ITF W15 – ID 370; finále ITF W75 v Polsku – ID 386
27. **Česká děvčata s Darjou Viďmanovou – stříbro na MS družstev do 16 let (Orlando 2019)** – ID 110; stříbro Summer Cup 16 let 2019 – ID 83
28. **Halová mistrovství ČR jednotlivců**: Gabriela Šulcová (starší žákyně 2025, ID 307), Sebastian Schamberger (starší žáci 2026, „neztratil ani set“, ID 368), Eliška Forejtková (mladší žákyně 2020, ID 140); Kateřina Kubíková mistryně ČR mladšího žactva ve čtyřhře (ID 259) a Hráčka roku I.ČLTK 2024 (ID 297)
29. **Medaile klubových družstev mládeže na MČR**: starší žáci bronz 2025 (ID 339) i 2026 (článek bez ID), mladší žáci bronz 2019 (ID 75), 2020 (ID 165), 2024 (ID 271), starší žactvo bronz 2019 (ID 99); Hanzelín a Švojgr bronz z Halového ME družstev do 14 let 2020 (ID 146)
30. **Babytenis (Tenisová škola Markéty Vondroušové)**: Valerie Křivanová mistryní ČR 2025 (ID 346), Viktor Zhuk halovým mistrem ČR 2026 a Jakub Kožuský finalistou (ID 365); 30. ročník štvanického Masters v babytenise (ID 264)

K Davis Cupu: žádný článek o účasti klubového hráče; zmínka jen v nekrologu Pavla Kordy („Československý tým dovedl k vítězství v Davis Cupu, Jana Kodeše ke třem grandslamovým titulům“, ID 58). K BJK Cupu: nominace Bartůňkové (ID 348) a smazaný článek o BJKC Finals 2022 (jen ve Wayback).

## 4. Kdo se v archivu objevuje nejčastěji

Počet článků, kde je jméno zmíněno (slovník jmen se skloňováním v `scrape.py`; pole `players`). Vazba na klub = jak ji popisují samotné články.

| hráč(ka) | článků (z toho v titulku) | rozpětí | vazba na klub podle článků |
|---|---|---|---|
| Nikola Bartůňková (2006) | 25 (16) | 2019–2026 | „Hráčka I.ČLTK Praha“ už jako juniorka (ID 92, 98); hrála štvanickou babytenisovou sérii (ID 264) → **odchovankyně** |
| Karolína Muchová | 21 (13) | 2019–2026 | „Hráčka I.ČLTK Praha“ (ID 59, 116, 241), „štvanická hráčka“; o původu v klubu články nemluví |
| Markéta Vondroušová | 20 (7) | 2019–2026 | „vyrůstala v našem klubu od věku baby tenisu“, klub je „jejím mateřským klubem“, dala jméno dětské tenisové škole (ID 374) → **odchovankyně** |
| Darja Viďmanová (2003) | 19 (11) | 2019–2026 | „Hráčka I.ČLTK Praha“ od juniorských let (ID 61, 69), studovala na University of Georgia (ID 294, 321) |
| Jonáš Forejtek (2001) | 18 (14) | 2019–2025 | „hráč I.ČLTK Praha“ (ID 166), hrál štvanickou babytenisovou sérii (ID 264) → **odchovanec** |
| Sofie Jiráková (2012) | 13 (6) | 2019–2026 | „štvanická hráčka“ (ID 330); zmínka „Jiráková“ v babytenisové nominaci 2019 (ID 67) je bez křestního jména – NEOVĚŘENO, zda Sofie |
| Kateřina Kubíková (2012) | 10 (5) | 2019–2025 | Hráčka roku I.ČLTK 2024 (ID 297) |
| Gabriela Šulcová (2011) | 8 (5) | 2024–2025 | „naše jednička“ v družstvu starších žáků (ID 339) |
| Eliška Forejtková (2008) | 8 | 2019–2025 | „štvanická hráčka“ (ID 333) |
| Vojtěch Valeš | 8 | 2019–2024 | „štvanický hráč“ (ID 290) |
| Tereza Martincová | 7 (2) | 2019–2026 | „Druhá štvanická hvězda“ (ID 220), „Tenistka klubu I. ČLTK Praha“ (ID 363) |
| Filip Wollner, Marek Šmejcký (2010), Kryštof Švojgr, Katrin Pávková | 7 | | mládež klubu |
| Jakub Vrtílka (2008), Veronika Sekerková (2009), Lukáš Velík, Jan Šátral (1990), Filip Hanzelín, Marek Pazdera | 6 | | Šátral „štvanický hráč“ (ID 145) |
| Denisa Žoldáková (2008) | 5 (4) | 2025–2026 | „nově štvanická hráčka“ (ID 360, únor 2026) → **přestup** |
| Andrew Paulson (2001) | 4 | 2021–2025 | „Další náš hráč“ (ID 333), štvanická babytenisová série (ID 264) |

Další užitečné vazby z textů: Emily Bukalová „do letošní sezóny hrála za SK Tenis Tišnov“ (ID 376); Jan Havlík je ze Sportcentra Mladá Boleslav, „částečně se připravuje u nás v klubu a reprezentuje nás v soutěži družstev“ (ID 372); Justýna Reifová „letos hostuje v našem klubu z Modřan“ (ID 330); Jakub Nicod hrál v roce 2020 za TK Sparta Praha (ID 139), v roce 2025 je „štvanický hráč“ (ID 322). Babytenisovou sérii na Štvanici (od konce roku 2008) podle ID 264 hráli také Tomáš Macháč, Jiří Lehečka, Sára Bejlek a sestry Fruhvirtovy – dnes nejsou hráči klubu, ale je to silný příběh „tady začínali“.

Osobnosti klubu v článcích: Jan Kodeš (4×, Wimbledon 1973), Vlasta Vopičková (členka klubu, vítězka Trofeo Bonfiglio, ID 63), Pavel Korda (zasloužilý člen, † v 84 letech, ID 58), Jiří Fencl (zasloužilý člen in memoriam, ID 302), prezident Ing. Petr Šimůnek (od 14. 7. 2022, viceprezident CTC od 2014, ID 231), prof. Václav Klaus (prezident klubu 11 let, čestný člen od 1993, ID 231), generální manažer Vladislav Šavrda, vedoucí trenér tenisové školy Jan Pecha.

## 5. Tón a délka

- **Délka:** medián **88,5 slova**, průměr 119; 136 článků má méně než 100 slov, jen 6 přes 400. Články rostou: medián 58 slov (2019) → 152,5 (2025) → 192 (2026). Nejdelší: finále Wimbledonu 2026 (736 slov, převzato z cztenis.cz), „Výsledky našich hráčů“ 2021 (579), MČR družstev dorostu 2025 (569).
- **Struktura 2019–2021:** telegrafická zpráva – titulek „X vítězně / bojoval o titul“, 1–2 věty s výsledkem (často ve formátu 6/3 6/4 a s žebříčkem v závorce „(ATP 474)“), „Gratulujeme k úspěchu!“ a „Kompletní výsledky naleznete zde“ (42×) s odkazem na itftennis.com / tenniseurope.org / cztenis.cz.
- **Struktura 2024–2026:** sportovní reportáž – průběh zápasu po setech, citace hráček, dopad na žebříček, ročník narození v závorce „(2008)“, 14 článků odkazuje na cztenis.cz, 11 z nich je výslovně převzato („Zdroj: www.cztenis.cz“).
- **Tón:** hrdý, rodinný, gratulační – „Gratulujeme“ 75×, „!!“ 26×, oslovení přezdívkami („Maky, gratulujeme Ti…“, „Kájo, obrovská gratulace!!“, „NIKI FANTAZIE, OBROVSKÁ GRATULACE“, „Dášo“, „Verčo“), „děkujeme za vynikající reprezentaci klubu“. Místo názvu klubu se píše **„štvanický/á“** (82×) – Štvanice je identita.
- **Fotky:** hlavně telefonní snímky z předávání cen (portrét, trofej, reklamní stěna turnaje), u velkých úspěchů agenturní foto s uvedeným autorem: Getty Images (ID 385), Martin Sidorjak (ID 381, 360, 285, 209), ITF / Daniel Kopatsch (ID 360), ČTS / Pavel Lebeda (ID 363), Mehmet Murat Onel / Anadolu (ID 274) → **pro nový web nutno řešit licence**, pole `foto_credit`.

## 6. Jak archiv prezentovat výjimečně (návrhy)

1. **„Štvanická osa 1973 → 2023“** – úvodní motiv archivu: dva wimbledonské tituly se stejnou adresou, Jan Kodeš 1973 a Markéta Vondroušová 2023, oba i Sportovec roku (ID 247, 255). Horizontálně rolovaná časová osa, kde nejnovější zprávy navazují na 130 let historie (oslavy 130 let 22. 7. 2023, ID 249).
2. **Výsledková typografie místo náhledů** – 133 článků nemá fotku, ale skoro každý má skóre. Karta článku = velké serifové skóre („6:3 · 1:6 · 10:6“), kolo („FINÁLE“), turnaj a město; fotka jen tam, kde je. Působí luxusně, ne jako prázdné místo.
3. **Síň trofejí jako filtr** – úspěchy tagované podle úrovně (Grand Slam · OH · WTA 1000/500 · WTA 125/Challenger · ITF · juniorský GS · MČR · družstva) a podle typu (titul / finále / semifinále). Data jsou v JSON, stačí doplnit úroveň.
4. **„Generace Štvanice“** – svislá osa ročníků narození, které články uvádějí: 1990 Šátral · 2001 Forejtek, Paulson · 2003 Viďmanová · 2006 Bartůňková · 2007 Chodora, Kozlovský, Sanders, Fajmonová · 2008 Žoldáková, Vrtílka, J. Šmejcký, Blažková, Forejtková, Brückner · 2009 Ladman, Sekerková · 2010 M. Šmejcký, Filipová · 2011 Šulcová, Reifová, Tvrzská, Toman · 2012 Jiráková, Schamberger, Kubíková · 2013 Bukalová, Hoffelner, Hertlová, Šenkyřík · 2016 babytenis. Tohle nemá žádný jiný klubový web.
5. **„Tady začínali“** – cesta od babytenisu k profi: štvanický babytenisový Masters (30 ročníků, ID 264) → Tenisová škola Markéty Vondroušové → mládež → WTA/ATP. Profily hráčů s milníky z archivu (první titul, první GS, žebříček).
6. **Mapa světa „kde vyhrávali štvaničtí“** – Soul, Dauhá, Bad Homburg, Berlín, Figueira da Foz, Hechingen, Sunderland, Santo Domingo, Cary, Monterrey, Oslo, Jerevan, Baku, Le Touquet… Jemná rytinová mapa se zlatými body, klik = článek.
7. **Profil hráče generovaný z archivu** – všech 25 článků o Bartůňkové nebo 21 o Muchové jako osobní kronika (s daty z `players`), včetně citátů z textů.
8. **Sezóna jako kapitola** – „Rok na Štvanici 2025“: nejlepší výsledky, družstva, akce klubu (Večer talentů, Mikulášská, Vánoční večírek na Letenském zámečku), galerie z dphoto.
9. **„Tento den v historii klubu“** – drobný modul na homepage z dat archivu (po doplnění skutečných dat).
10. **Živý pulz** – na dnešním webu je v postranním panelu iframe „Výsledky našich hráčů“ (scorepresso.com/export/cltk_small.php); v novém webu jako elegantní live ticker nad archivem.
11. **Oddělit provozní oznámení od příběhů** – uzavírky, ceníky, COVID opatření, omezení kvůli Oktagonu (ID 183, 191, 334, 362) patří do sekce „Provoz areálu“, ne mezi úspěchy.

Před spuštěním archivu na novém webu je potřeba: opravit data (použít `date` z JSON, u 28 článků ověřit), 63 ztracených obrázků nahradit nebo skrýt, ověřit práva k agenturním fotkám a rozhodnout, zda vrátit 19 smazaných článků.

## 7. Stažené fotky (assets/foto/clanky/)

Hlavní obrázek 40 nejnovějších článků, které obrázek mají (z nejnovějších 40 článků má obrázek 30; zbytek je z článků do 7. 7. 2025). Originály mají až 7982 × 5321 px / 16 MB → uloženo zmenšené na max. 3000 px (JPEG q88), originální rozměry v `foto_original_px`. Stejný slug dvou článků „Starší žáci vezou z MČR družstev bronz“ (2025 a 2026) → soubor 2025 má příponu `-339`. Nejvhodnější na velké plochy (na šířku, ≥ 2000 px): `karolina-muchova-ovladla-smisenou-ctyrhru-na-us-open.jpg` (Getty), `karolina-muchova-ve-finale-wimbledonu.jpg`, `uspesne-australian-open-pro-stvanicke-hracky.jpg`, `bartunkova-si-zahraje-hlavni-soutez-australian-open.jpg`, `viktor-zhuk-vyhral-hm-cr-v-babytenise-2026-jakub-kozusky-ve-finale.jpg`, `juniori-na-mcr-druzstev-stribrni.jpg`, `fantasticky-andrew-paulson-i-matej-vocel.jpg`. Většina ostatních jsou telefonní portréty s trofejí (na výšku, 1200–2048 px) a 4 grafiky/plakáty (Conseq Prague Open 2026, Prague Open 2026, pozvánka na Mikulášskou besídku).
