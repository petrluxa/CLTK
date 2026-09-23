# I. ČLTK Praha – návrhy nového webu

Podklady a návrhy nového webu **www.cltk.cz** pro **I. Český Lawn-Tennis Klub Praha** na ostrově Štvanice. Chystáme tři návrhy ve světlých barvách, luxusní a neobvyklé. Klub se má prezentovat jako výstavní klub českého tenisu.

Stav podkladů: **23. 9. 2026**.

## Co je v repozitáři

| Cesta | Obsah |
|---|---|
| `podklady/01–10-*.md` | Rešerše: areál a ceník, klub a členství, závodní tenis, články, Revue, historie, znak, výsledky, inspirace. Dva soubory `10-overeni-*` ověřují fakta proti nezávislým zdrojům. |
| `podklady/11-design-brief.md` | Zadání tří návrhů: koncepty, palety, písma, sekce a data. |
| `podklady/12-kritik.md` | Kontrola úplnosti: co z dnešního webu chybí, rozpory, stav odkazů a souborů, zbývající problémy podle důležitosti. |
| `podklady/data/obsah.json` | **Kurátorský obsah pro návrhy.** Jediný zdroj textů, čísel a cest k fotkám pro mockupy. |
| `podklady/data/*.json` | Strukturovaná data: 238 článků, 41 čísel Revue, 78 newsletterů, kronika, síň slávy, tituly, trenéři, partneři a popisy fotek. Pozor: `fotky.json` má u 20 fotek v `width`/`height` rozměr originálu v `_raw/foto-original/`, ne souboru v `assets/`. Platné rozměry jsou v `obsah.json`. |
| `assets/foto/`, `assets/historie/` | Fotky areálu, akcí a hráčů jako webové verze (max. 2400 px). Dále 61 historických snímků z výročních Revue. |
| `assets/revue/` | 41 obálek Revue 2006–2026. |
| `assets/logo/` | Znak: 3D verze, vektor SVG, jednobarevná SVG a PNG. |
| `assets/partneri/` | 36 log partnerů. |
| `podklady/_raw/` | Git-ignored. HTML, PDF, texty Revue, originály fotek (`foto-original/`) a obálky ročenek Prague Open. |
| `nastroje/snimek.mjs` | Kontrolní screenshoty návrhů přes Playwright. Umí desktop, mobil a WebKit a hlásí chyby a přetečení. |

**Pravidla práce**

- Žádná fakta si nevymýšlíme. Co není v tomto README nebo v `obsah.json`, do návrhu nepatří. Chybějící údaj se v návrhu označí jako „doplní klub“.
- Fotky jsou pracovní. Zadavatel slíbil vlastní a práva k agenturním snímkům zatím nejsou vyřešená.
- Citlivá témata jsou na konci sekce Fakta.

---

## Fakta o klubu

**Legenda:**

- Údaj bez značky je **ověřený**: z primárního zdroje (web klubu o vlastní věci, registr ČTS, archiv extraligy, turnajový web, ÚDU AV ČR, ČTK) nebo ze dvou nezávislých zdrojů.
- *(klub)* znamená, že údaj dokládají jen klubové prameny (Revue, newsletter, web). Smí se použít, ale bez superlativů.
- **NEOVĚŘENO** znamená rozpor v pramenech nebo chybějící zdroj. Nepoužívat jako fakt.
- Zdroje k jednotlivým údajům jsou v `podklady/data/obsah.json` a v souborech 01–10.

### Identita

- **Název:** I. Český Lawn-Tennis Klub Praha. **Zkratka:** I. ČLTK Praha (na webu i „I.ČLTK Praha“). Hovorově „Štvanice“, „štvanický klub“.
- **Založení 1893.** Zdroje: Národní muzeum, ceskytenis.info a ČTK.
  - Zakladatelé: Josef Cífka, Karel Cífka a Josef Rössler-Ořovský (cs Wikipedie).
  - Národní muzeum a Encyklopedie Prahy 2 připisují založení Rösslerovi-Ořovskému.
- **Na Štvanici od roku 1901.** Tehdy tu bylo pět kurtů, dřevěná klubovna a restaurace (ÚDU AV ČR).
  - Předchozí sídla podle cs Wikipedie (podle Lichnera 1985): 1893 Židovský ostrov → 1894 Střelecký ostrov → 1896 Holešovice-Bubny.
  - Klubové prameny uvádějí pořadí i roky jinak (**NEOVĚŘENO**).
- **Doporučené formulace:** „Od roku 1893“ · „Na Štvanici od roku 1901“ · „Nejstarší tenisový klub v Praze“ · „Jediný český člen Centenary Tennis Clubs“.
- **Nepoužívat „nejstarší klub v ČR“.** Stejně se prezentuje I. ČLTK Plzeň (leden 1893).
- **Názvy klubu** *(klub)*:
  - 1893 I. Český Lawn-Tennis Klub;
  - 1949 oddíl pod Sokol Jinonice, pak TJ Motorlet;
  - Spartak Praha Motorlet (klub uvádí 1956, jiné prameny 1953 nebo 1957);
  - TJ Dopravní podnik (klub uvádí 1969, cs Wikipedie 1970);
  - 1990 zpět I. ČLTK Praha.
  - Klub píše o čtyřech změnách názvu, iROZHLAS o třech. „Spartak“ není Sparta.
- **Údaje:** IČO 45243077. Adresa Ostrov Štvanice 38, 170 00 Praha 7.
- **Členství v organizacích:**
  - Centenary Tennis Clubs od roku 2000 *(klub)*. Klub je **jediným členem z ČR** (seznam členů CTC). CTC sdružuje 98 klubů starších 100 let (web klubu 2026).
  - Český tenisový svaz, v adresáři jako klub č. 52.
- **Počet členů:** „na čtyři stovky, převážně rekreačních hráčů“ *(klub, 2023)*. Aktuální počet **NEOVĚŘENO**.
- **Znak:**
  - Podoba: štít s modrou a červenou polovinou, nápis „I.ČLTK“, zkřížené rakety se zelenými listy a dva míče s vlnitým pruhem.
  - Druh listů ani symboliku prameny nevysvětlují.
  - Tiskoviny pod znak sázejí „ZALOŽEN 1893“.
  - **Hlavní znak pro web je 3D stříbrná verze**, kterou dodal zadavatel (`assets/logo/cltk-znak-3d.png`, jen 402×445 px).
  - Vektor z originálního EPS (Revue 01/2026): `cltk-znak.svg`.
  - Jednobarevné verze: `cltk-znak-mono.svg` a `cltk-znak-mono-plny.svg`.
- **Barvy znaku** (převod z CMYK v PDF):
  - modrá #2E3092 (Adobe #2E3192), červená #ED1C24, zelená #00A650;
  - zlatá linková #89792F, zlatý okraj s přechodem #B88C30 → #7A6935.
  - Hlavička Revue: zlatá #B9A355 a navy #00275D, olivová #7D7254.
  - Grafický manuál ani Pantone neexistují, nebo nebyly nalezeny.

### Kontakty a lidé

| Role | Jméno | Telefon | E-mail |
|---|---|---|---|
| Recepce, rezervace kurtů | – | +420 608 974 974 | recepce@cltk.cz |
| Sekretářka: stálé rezervace, platby členství (kancelář Po–Pá 9–17) | Eva Štefková | +420 737 215 012 | stefkova@cltk.cz |
| Generální manažer | Vladislav Šavrda | +420 603 409 475 | savrda@cltk.cz |
| Sportovní manažer | Mgr. Petr Šavrda | +420 603 890 969 | petr.savrda@cltk.cz |
| Provozní manažer | Mgr. Martin Šustr | +420 724 432 073 | sustr@cltk.cz |
| Sportovní ředitel | Petr Vaníček | +420 739 220 233 | vanicek@cltk.cz |
| Vedoucí trenér Tenisové školy (do 9 let) | Mgr. Jan Pecha, Ph.D. | +420 721 663 118 | pecha@cltk.cz |
| Wellness a fyzioterapie | Bc. Joseph B. Truesdale | +420 704 737 187 | leftfld14@hotmail.com |
| Tenis shop | Tomáš Truneček | – | – |
| Webmaster | – | – | webmaster@cltk.cz |

- **Vedení:**
  - Prezident: Ing. Petr Šimůnek, zvolen 14. 7. 2022. V té době byl 32 let členem výboru a je ekonomem klubu.
  - Předchozí prezident: prof. Václav Klaus (2011–2022).
  - Šimůnek zastupuje klub ve vedení CTC od roku 2005. Viceprezidentem CTC je od roku 2011, nebo od roku 2014 (rozpor v klubových textech).
- **Výkonný výbor (9 členů):** Ing. Petr Šimůnek (prezident), JUDr. Tomáš Havel, JUDr. Zdeněk Krampera, Mgr. Václav Kučera, Ing. Dušan Palcr, Mgr. Jan Pecha, Ph.D., Vladislav Šavrda, Mgr. Martin Šustr, Petr Vaníček.
- **Online služby:**
  - Rezervace: https://www.rogeronline.cz/v2/index.php?klub=181
  - Obsazenost kurtů: https://onlinehq.cz/r/courtst.php?klub=181
  - Facebook: https://www.facebook.com/cltk.fb · Instagram: https://www.instagram.com/cltk.insta/
  - Fotogalerie: https://cltk.dphoto.com/albums (50 alb, 8 680 fotek)
- **Účty (ČSOB):**
  - Členství a tréninky: 1471509/0300, IBAN CZ20 0300 0000 0000 0147 1509.
  - Tenisová škola a kempy: 312451935/0300.
- Telefon 222 316 317 z PDF provozních řádů: **NEOVĚŘENO**, zda ještě platí.

### Areál a zázemí

**Poloha a příjezd:**

- Ostrov Štvanice ve Vltavě. Přístup z Hlávkova mostu (brána pro pěší) nebo z Karlína a Holešovic po lávce **HolKa**.
- Lávka HolKa byla otevřena 28. 7. 2023, měří asi 300 m a má bronzové zábradlí se sochami zajíců, býků a koní.
- Na Hlávkově mostě staví tramvaj č. 14. Stanice metra B a C jsou „několik minut chůze“.
- Autem: vyhrazené parkoviště u Negrelliho viaduktu a zadní vchod. Pod oblouky viaduktu jsou 4 oblouky po 5 místech *(klub, Revue 02/2025)*. Celková kapacita **NEOVĚŘENO**.

**Kurty: 19 celkem**

| Kurt | Léto | Zima |
|---|---|---|
| 1 „malý centr“ (web klubu: cca 1000 míst) | antuka | přetlaková hala, tvrdý povrch |
| 2, 3, 4 trojkurt (malé tribuny, jediné s večerním svícením) | antuka | přetlaková trojhala, tvrdý povrch (MIBOsport 2024) |
| 5, 6 (kurt 5 jen pro členy) | antuka | přetlaková dvojhala s antukou |
| 7, 8 (tvrdý povrch a nová hala od 2024, přes 14 mil. Kč) | tvrdý | přetlaková dvojhala |
| 9 (nový hard Novasport 2025) | tvrdý | přetlaková hala |
| 10–16 „Slavoj“ za Negrelliho viaduktem | antuka | uzavřeno |
| C velký centrální dvorec (patří ČTS) | tvrdý | přetlaková hala |
| P1, P2 pevná hala Novasport pod ochozem centru | tvrdý | celoročně |

- **Souhrnně:** v létě 13 venkovních antukových kurtů (3 s osvětlením) a 3 tvrdé kurty, k tomu 2 kurty v pevné hale. V zimě 12 krytých kurtů: 10 tvrdých a 2 antukové.
- Dnešní EN verze webu má počty zastaralé.
- **Stadion 1986:**
  - Otevřen 16. 6. 1986 jako „nejmodernější tenisový areál v tehdejším Československu“ (ČTK). Premiérou byl Pohár federace.
  - Architekti Josef Kales a Jana Novotná (Sportprojekt), literatura uvádí i Jaroslava Paroubka.
  - Materiály: šedý beton, stříbřitá ocel a hněď. Sedadla byla původně červená a žlutá, dnes jsou modrá.
  - Socha *Tenista* od Ladislava Janoucha (1986).
  - **Kapacita:** 7 000 míst k sezení (ÚDU AV ČR), ČTK píše o 8 000 divácích a web klubu o 8 000 místech.
  - Centrální dvorec patří **Českému tenisovému svazu**, ostatní kurty klubu. Po povodni 2002 se svaz a klub dohodli na rozdělení správy.
- **Plán areálu:** `assets/foto/plan-arealu.jpg`, verze 09-2025, bez severky. Souřadnice kurtů v % jsou v `obsah.json → areal.kurty[].mapa`.
  - Zleva: Slavoj | viadukt a parkoviště | hlavní budova s kurtem 1 a stadionem C | bazén, kurty 5–9 a trojkurt.
  - Na plánu jsou dvě multifunkční hřiště. Které z nich se pronajímá, **NEOVĚŘENO**.

**Služby**

- **Venkovní bazén:** v areálu „již více než 20 let“, provoz zhruba květen–září. Jen pro členy (zdarma) a jejich hosty (400 Kč/den).
  - Citát z webu: „Takto blízko centra Prahy naleznete jen velmi málo podobně příjemných a klidných míst…“
- **Fitness:** od roku 2017 (I. etapa nástavby na ochozu malého centru). Členové zdarma, host 400 Kč.
- **Wellness:** od března 2019. Vířivka, sauna s odpočívárnou, infrasauna, Kneippovy lázně a ice bath (jen pro závodní hráče). Otevřeno v **Po–Pá 16:00–20:00**, jen pro členy, rekreační členové max. 2× týdně.
- **Fyzioterapie:** J. Truesdale. Sportovní lékařství Zdravý sport (centrum-inmotion.cz). Od června 2026 ordinace Body Solution Clinic *(klub, Revue 01/2026)*.
- **Restaurace Tiebreak s terasou:**
  - nová terasa podle ateliéru Adama Fröhlicha, 60 míst *(klub)*;
  - salónek s dětským koutkem, dětské hřiště.
  - Otevírací doba, kontakt ani menu na webu nejsou.
- **Tenis shop:** ve vestibulu od listopadu 2023, Po–Pá 9–12 a 13–17. Vyplétání, Mizuno, Babolat a klubový merch.
- **Beachvolejbal a multifunkční hřiště:** na Slavoji. Pokud hrají jen členové, je vstup zdarma. S hosty je pronájem o 25 % levnější.
- **Klubové prostory:** vestibul s velkou černobílou fototapetou (mj. hráčka s wimbledonskou mísou), klubová místnost a recepce.
  - Ve foyeru jsou koláž tenisových es a pamětní deska Jaroslava Drobného, odhalená 14. 6. 2012.
- **Firemní akce:** kurty, zázemí, rauty a doplňkové služby. Ceny na webu nejsou.
- **Nástavba malého centru 2015–2020:** architekt Vladimír Lacina. Fitness, wellness a ordinace za „téměř 50 mil. Kč“ *(klub)*.
- **Uzavírky 2026:**
  - 20.–26. 7. Livesport Prague Open;
  - 1.–2. 8. Oktagon 92;
  - 16.–22. 8. Sekyra Group Prague Open.
- Otevírací doba areálu, recepce a restaurace: **web ji neuvádí.** Uvádí jen, že veřejnost si může venkovní dvorce pronajmout denně 7:00–22:00 (stránka O klubu, ověřeno 23. 9. 2026).
- **Dokumenty ke stažení** (Stanovy, Pravidla hraní 2026, zimní ceník, provozní řády bazénu, fitness a wellness, plán areálu v PDF) a stránka GDPR jsou v `obsah.json → klub.dokumenty` a `klub.gdpr`. GDPR stránka se týká jen členů. Zásady cookies pro návštěvníky webu klub nemá.

### Služby a ceny (2026)

**Léto 2026** (členové hrají na venkovních kurtech zdarma):

- kurty 1–9: 500 Kč/h
- Slavoj 10–16: 400 Kč/h
- pevná hala P1, P2: 600 Kč/h, pro členy 500 Kč/h
- svícení na kurtech 2–4: 150 Kč/h

**Doplňkové služby:** multifunkční hřiště 900 Kč/h · beach 400 Kč/h · host člena ve fitness 400 Kč · host člena u bazénu 400 Kč.

**Zima 2026/27**

Ceny v tabulce jsou za hodinu (veřejnost / člen) a za předplatné jedné hodiny týdně na celé období (veřejnost / člen). Předplatné platí jen při platbě předem.

| Hala · období | Pásmo | Hodina | Předplatné |
|---|---|---|---|
| Přetlaková, antuka (5, 6) · 5. 10. 2026 – 4. 4. 2027, 26 týdnů | všední 7–14 | 540 / 430 | 12 480 / 10 140 |
| | všední 14–21 | 730 / 590 | 17 160 / 13 780 |
| | víkend 8–21 | 450 / 390 | 10 660 / 8 580 |
| Přetlaková, tvrdá (C, 1–4, 7–9) · 5. 10. 2026 – 4. 4. 2027, 26 týdnů | všední 7–14 | 730 / 590 | 17 160 / 13 780 |
| | všední 14–22 | 860 / 690 | 20 280 / 16 380 |
| | víkend 7–22 | 600 / 490 | 14 040 / 11 440 |
| Pevná hala Novasport (P1, P2) · 28. 9. 2026 – 4. 4. 2027, 27 týdnů | všední 7–22 | 890 / 800 | 21 870 / 19 710 |
| | víkend 7–22 | 700 / 630 | 17 010 / 15 120 |

**Tenisová škola**

- Zima 2026/27 (7. 9. 2026 – 2. 4. 2027), cena na osobu a hodinu:
  - individuálně 1 350 Kč
  - individuálně na půl kurtu 900 Kč
  - ve dvou 675 Kč
  - ve třech 625 Kč
  - ve čtyřech 475 Kč
  - skupina začátečníků 375 Kč
- Léto 2026: 950 / 475 / 300 Kč.
- Základní členství v TŠ: 1 000 Kč ročně.

**Letní kempy 2026**

- 6 termínů od 29. 6. do 28. 8., pro děti zhruba 4–9 let.
- Varianta A celodenní (8:30–16:30): 6 000 Kč pro hráče klubu, 8 500 Kč pro ostatní.
- Varianta B dopolední (8:30–13:30): 5 000 / 7 500 Kč.
- V ceně je oběd a pitný režim.

### Členství 2026

- **Kdo se může stát členem:** kdokoli. Přihlášku posuzuje Výkonný výbor a členství vzniká dnem jeho rozhodnutí, zpravidla na nejbližším zasedání (Stanovy čl. 3.2.5).
- **Postup:** žádost → rozhodnutí výboru → platba → členská karta.
- **Hrající:**
  - dospělý 21 000 Kč
  - dospělý do 30 let 15 500 Kč
  - senioři 10 000 Kč (ženy od 67 let, muži od 72; ve všední dny hrají do 14:00)
  - mládež 4–18 let a studenti do 26 let 9 500 Kč
- **Rodinné:**
  - 1 hrající + 1 nehrající: 28 000 Kč
  - 1 hrající + 1 dítě: 27 000 Kč
  - 1 hrající + 1 nehrající + 1 dítě: 34 000 Kč
  - 1 hrající + 1 nehrající + 2 děti: 40 000 Kč
  - 2 hrající: 35 500 Kč
  - 2 hrající + 1 dítě: 41 500 Kč
  - 2 hrající + 2 děti: 47 500 Kč
- **Ostatní:** nehrající 10 000 Kč (jen ve spojení s hrajícím členstvím). Firemní 40 000 Kč (min. 2 přenosné karty).
- **Hosté člena:** venkovní dvorec 100 Kč/h (řádný člen 8× za rok, zvýhodněné členství 4×). Bazén nebo fitness 400 Kč.
- **V ceně:**
  - venkovní kurty v létě, bazén, fitness a regenerace;
  - beach a multifunkční hřiště;
  - klubové akce;
  - Revue (2× ročně) a newsletter (dvouměsíčník, i v angličtině);
  - hra zdarma v klubech CTC po doporučení generálního manažera.
- **Druhy podle Stanov:**
  - řádné;
  - zasloužilé (uděluje výbor);
  - čestné (uděluje Valná hromada) – za vítězství v grandslamové nebo olympijské dvouhře, nebo pro prezidenta či premiéra ČR a primátora Prahy.
- Newsletter 5/2025 ohlásil „postupné zvyšování ceny členských příspěvků“.

### Závodní tenis a mládež

- **Pyramida ČTS v klubu** (PDF ČTS 2026). I. ČLTK je **jeden ze tří klubů v ČR** (se Spartou a Prostějovem), které mají celou pyramidu.
  - Tenisová škola Markéty Vondroušové: 3–9 let.
  - **TSM** (10–14 let): 14 + 2 hráčů, hlavní trenér Antonín Štěpánek.
  - **SVT** (15–18 let): 16 + 2 hráčů, hlavní trenér Daniel Vaněk.
  - **SCM** (15–21 let): 9 + 2 hráčů, hlavní trenér Zdeněk Kubík. V ČR jsou jen 3 SCM.
- **26 reprezentantů ČR 2026** ze 146. Víc jich mají jen Prostějov a Sparta.
- **121 hráčů v žebříčcích ČTS** (léto 2026).
- **4 z TOP 10 českého žebříčku žen** (léto 2026): Muchová 1., Vondroušová 6., Bartůňková 9., Viďmanova 10. U mužů Forejtek 10.
- **Extraliga smíšených družstev:**
  - **mistr ČR 2018** (finále 19. 12. v Říčanech, Prostějov 5:4, první titul po 28 letech);
  - **mistr ČR 2019** (finále 18. 12., Přerov 5:2).
  - Finalista 2011 a 2015.
  - 2020–2025 bez finále: 2021 dělené 3. místo, 2024 3.–4. místo. Mistrem 2025 byla Sparta.
  - Soupiska 2025: Muchová, Vondroušová, Bartůňková, Viďmanová, Martincová, Forejtek, Paulson, Nicod, Vocel a další. Kapitáni Petr Vaníček a Ivo Minář.
- **Celkový počet titulů:** 16 titulů mistra republiky *(klub)*: 12 jako Spartak Praha Motorlet, dále 1975, 1990, 2018 a 2019.
  - Nezávisle jsou doloženy jen 1975, 1990, 2018 a 2019 (ČTK 2019: „počtvrté“). Roky 12 titulů Motorletu nikdo nevypisuje.
  - Na webu psát vždy s rozpisem. **Nepsat „11 v řadě v 60. a 70. letech“**, Sparta byla mistrem 1970–1974.
- **Družstva mládeže, MČR:**
  - 2026: babytenis 2. místo z 12, starší žactvo bronz, mladší žactvo 3. místo, dorost 3. místo.
  - 2025: dorost vicemistr (finále se Spartou 2:5), starší žactvo bronz. Pražské ligy st. i ml. žactva vyhrál klub.
- **Trenéři závodního tenisu:**
  - Petr Vaníček (sportovní ředitel, sparingpartner M. Hingisové)
  - Jiří Hřebec (ATP 25, Galeův pohár 1970)
  - Ivo Minář (ATP 62, vítěz Davis Cupu)
  - Ing. Jan Vacek (ATP 61, Brasil Open 2001)
  - Milan Trněný
  - Daniel Vaněk
  - Ing. Jaroslav Jandus
  - Bc. Antonín Štěpánek
  - Ing. Lubomír Štych
  - Magdaléna Zemanová
  - kondiční trenéři Mgr. Pavel Janda a Mgr. Richard Pavluv (tvar „Pavlův“ **NEOVĚŘENO**)
  - Portréty jsou jednotné studiové fotky v `assets/foto/treneri/`.
- **Tenisová škola Markéty Vondroušové:**
  - „již více než 15 let přímou součástí I.ČLTK Praha“, jméno nese od roku 2023;
  - „kolem stovky dětí“ *(klub)*, nábory na jaře a na podzim;
  - pořádá turnaje v minitenisu, na středním kurtu a v babytenisu;
  - série I.ČLTK Praha Cup by Babolat běží od konce roku 2008 (30. Masters v březnu 2024).
- **Dotace 2026:** NSA na Prague Open 2 890 401 Kč. Hl. m. Praha na mládež 1 539 555 Kč (poměrná část).

### Výsledky a síň slávy

**Dvojí metr.** Klubové texty počítají úspěchy všech, kdo na Štvanici vyrostli. Poctivě je proto rozlišovat:

- **„Hráči Štvanice“ (klubová definice):** 20 grandslamových titulů, z toho 7 ve dvouhře. Počítají se hráči, kteří tu vyrostli, kdykoli za klub hráli, nebo jsou čestní či zasloužilí členové. Proto sem patří i Drobný 1951–1954, Kodeš 1970–1973, krátce registrovaný D. Vacek a Složil (RG mix 1978), jehož vazbu na klub cs Wikipedie nepotvrzuje. Štítek „vyrostli na Štvanici“ je pro číslo 20 nepřesný.
- **„V barvách klubu v den triumfu“:** 10 titulů, ověřeno registrem ČTS.

**Grandslamová dvouhra ve štvanické historii**

- **Jaroslav Drobný:** RG 1951, 1952 a **Wimbledon 1954** (finále s Rosewallem 13-11, 4-6, 6-2, 9-7).
  - Tituly vyhrál jako emigrant za Egypt. Na Štvanici vyrostl jako syn správce.
- **Jan Kodeš:** RG 1970, 1971 a **Wimbledon 1973** (finále s Metrevelim 6-1, 9-8, 6-3).
  - Byl to odchovanec klubu, ale tituly vyhrál jako hráč Sparty. Trénoval ho štvanický Pavel Korda.
- **Markéta Vondroušová:** **Wimbledon 2023**, první nenasazená vítězka. Ve finále porazila Jabeurovou 6:4, 6:4 *(newsletter)*.
  - Vyhrála ho jako hráčka I. ČLTK.
- Formulace „tři wimbledonští vítězové vyrostli na Štvanici“ sedí. V den titulu ale byla hráčkou klubu jen Vondroušová.

**10 grandslamových titulů v barvách I. ČLTK**

- Vondroušová: W 2023
- Hradecká: RG 2011 čtyřhra, US Open 2013 čtyřhra, RG 2013 mix
- Benešová: W 2011 mix
- Damm: US Open 2006 čtyřhra
- Daniel Vacek: RG 1996, RG 1997 a US Open 1997, čtyřhra s Kafelnikovem
- Muchová: US Open 2026 mix s Menšíkem (Menšík hraje za Prostějov)

Pravděpodobně v barvách klubu (před rokem 1996, kdy začíná registr): Drobný RG 1948 čtyřhra a mix, Pužejová a Javorský RG 1957 mix.

**Finále dvouhry**

- Věra Suková-Pužejová: W 1962, první československá finalistka.
- Vondroušová: RG 2019.
- Muchová: RG 2023 (Šwiateková 2:6, 7:5, 4:6).
- **Muchová: Wimbledon 2026** (Nosková 2:6, 7:5, 3:6; 11. 7. 2026). Bylo to první ryze české finále ženské dvouhry.

**Juniorské grandslamy**

- V barvách klubu: K. Plíšková AO 2010, Kr. Plíšková W 2010 a Vondroušová AO + RG 2015 (čtyřhra s Kolodziejovou).
- Jonáš Forejtek vyhrál juniorský US Open 2019 a čtyřhry na AO a ve Wimbledonu 2019. Byl tehdy registrován za TK Škoda Plzeň, takže je **odchovanec**, ne „hráč v době titulu“.
- Podle klubu: Mišková W 1948, Holíková W 1985, Strnadová W 1989 a 1990.

**Olympijské hry**

- V barvách klubu: Hradecká stříbro 2012 (čtyřhra) a bronz 2016 (mix), Vondroušová stříbro Tokio 2020 (hráno 2021, finále 5:7, 6:2, 3:6).
- Vazba jen podle klubu: Žemla bronz 1920 (mix se Skrbkovou), Šrejber bronz 1988 (s Mečířem). Bronz Žemlových z Atén 1906 pochází z olympijských meziher, které MOV neuznává.

**Týmové soutěže reprezentace**

- Davis Cup: 2012 s Ivo Minářem (jisté). 1980 s Kodešem a Složilem (Evropská zóna), trenérem byl Korda (pravděpodobné).
- Fed Cup: Hradecká 2011, 2012, 2014, 2015 a 2016. Benešová 2011 (semifinále s Belgií).

**Síně slávy a ocenění**

- Mezinárodní tenisová síň slávy: Drobný 1983, Kodeš 1990.
- Drobný je v Síni slávy IIHF (1997). Je jediným člověkem, který vyhrál Wimbledon i MS v hokeji (1947, hrálo se na Štvanici), a jediným mužem, který vyhrál Wimbledon v brýlích.
- Vondroušová: Sportovec roku 2023 a Zlatý kanár 2023.

**Současné hvězdy**

- **Karolína Muchová** (v klubu od sezony 2019):
  - 2026: WTA 1000 Dauhá (Mboko 6:4, 7:5), WTA 500 Bad Homburg (Osaková skrečovala za stavu 6:1, 1:0), finále Wimbledonu a US Open mix;
  - kariérní maximum č. 6 (13. 7. 2026).
  - Citát: „V nejstarším klubu v Praze. … Je rodinný. Opravdu se tam cítím jako v rodině.“ (Aktuálně.cz 2023)
- **Nikola Bartůňková** (v klubu od 2017):
  - 3. kolo AO 2026 z kvalifikace (porazila Kasatkinovou a Bencicovou);
  - semifinále WTA 500 Guadalajara 2025 z divoké karty, finále WTA 125 Samsun 2025;
  - maximum č. 34 (14. 9. 2026).
- **Darja Viďmanova:** první titul WTA (WTA 125 Figueira da Foz 2026), finále WTA 250 Memphis 2026, maximum č. 75 (31. 8. 2026).
- **Darja Viďmanova / Viďmanová:** registr ČTS píše „Viďmanova“, klubové články většinou „Viďmanová“. Tvar sjednotit s klubem.
- **Tereza Martincová:** vyhrála z kvalifikace Conseq Prague Open 2026 (ITF W75) v pevné hale na Štvanici (kurty P1, P2, pořadatel ČTS). Kdo halu vlastní a spravuje, **NEOVĚŘENO**.
- **Mládež:**
  - Denisa Žoldáková: finále čtyřhry juniorek AO 2026;
  - Radek Chodora: mistr ČR do 18 let 2025;
  - Filip Ladman: mistr Evropy družstev U16 2025;
  - Emily Bukalová: zlato na Olympiádě dětí a mládeže 2026.

**Čestní a zasloužilí členové**

- **Čestní členové (4):** Markéta Vondroušová, prof. Václav Klaus (od 1993), Jan Kodeš, Jaroslav Drobný (1921–2001).
- Historičtí čestní členové: 9 jmen od roku 1893, mj. Josef Cífka a Josef Rössler-Ořovský.
- **Zasloužilí členové:** 34 jmen na webu. Jiří Fencl in memoriam (2025). `clenove-sine-slavy.json` má 35 záznamů, protože navíc obsahuje Ivo Mináře.
- Ivo Minář dostal podle newsletteru 130 let zlatý odznak v roce 2023, na webu ale chybí. Ověřit s klubem.

**Klubové ceny:**

- **Anketa Jaroslava Drobného**, 2025: Muchová, Vocel; junioři Fajmonová a Chodora; postup roku Bartůňková.
- **Večer talentů** (od 2024), hráčka roku: 2024 Kateřina Kubíková, 2025 Gabriela Šulcová.

**Nesmějí se uvádět jako „naši“**

- Za I. ČLTK nikdy registrováni: Siniaková, Krejčíková, Nosková, Kvitová, Macháč, Lehečka, Menšík, Svrčina, Bejlek a sestry Fruhvirtovy.
- Členství nedoloženo: Navrátilová, H. Suková, Mandlíková, P. Korda, Lendl, Menzel.

**Opravy klubových textů**

- Drobný:
  - na ZOH 1948 získal **stříbro** (ne „1947 zlato“);
  - finále Wimbledonu hrál 1949 a 1952;
  - britské občanství má od 1959;
  - Řím vyhrál 1950, 1951 a 1953, ne „třikrát za sebou“;
  - emigroval „v červenci 1949“.
- Muchová je v klubu od 2019, ne od 2017.
- Vondroušová je na Štvanici od 2006 (od 7 let).
- Složil vyhrál mix RG v roce 1978.
- Vackův třetí titul je US Open.
- Budařová a Skuherská vyhrály Fed Cup 1983 a 1984. Budařová přišla do klubu až v roce 1986.
- Minář nemá I. ČLTK jako „mateřský klub“.
- Hecht odešel do USA v roce 1939.
- Kodeš vedl Czech Open **12 ročníků**. Nepsat, že Czech Open byl první profesionální turnaj v ČSSR (už v roce 1973 se v Praze hrál turnaj Grand Prix).
- Datum odchodu Javorského: 1968 × 1977 (**NEOVĚŘENO**).

### Historie – hlavní milníky

- **Počátek 18. století:** na ostrov Velké Benátky se přesunuly zápasy zvířat, „štvanice“, a ostrov dostal jméno *(klub)*. Zakázány byly na počátku 19. století; prameny uvádějí roky 1802, 1805, 1806 i 1816.
- **1846–1849:** Negrelliho viadukt, provoz od 1. 6. 1850.
- **1893:** založení klubu.
- **1895:** první mistrovství zemí Koruny české.
- **1901:** klub se usazuje na Štvanici.
- **1906:** klub spoluzakládá Českou lawn-tennisovou asociaci. Bratři Žemlové berou bronz na mezihrách v Aténách.
- **1912:** turnaj pod oblouky viaduktu (foto). Ladislav Žemla skončil 4. na OH ve Stockholmu (bez medaile).
- **1920:** Žemla a Skrbková bronz v Antverpách. Žemla je prvním mistrem ČSR.
- **1921:** první utkání Davis Cupu na čs. půdě, ČSR – Belgie 2:3.
- **1926/1927:** dřevěný centrkurt; rok se v pramenech liší, kapacita 5 000 je *(klub)*.
- **1929:** modřínová klubovna architekta Šuly *(klub; cs Wikipedie spíš 1930)*.
- **1931–2011:** zimní stadion na ostrově (není klubový). MS v hokeji 1947 vyhrálo Československo i s Drobným.
- **1948:** Drobný vyhrál RG ve čtyřhře a mixu. V Davis Cupu v Praze porazil Bergelina.
- **1949:** Drobný emigroval v Gstaadu. Klub přešel pod Sokol Jinonice a Motorlet *(klub)*.
- **1954:** Drobný vítězem Wimbledonu.
- **1957:** Pužejová a Javorský vyhráli mix RG.
- **1962:** Pužejová ve finále Wimbledonu.
- **1971–1972:** Davis Cup na starém centru. V roce 1971 ČSSR porazila Španělsko 3:2, v roce 1972 porazil Kodeš Borga 6:2, 6:3, 7:5.
- **1973:** Kodeš vítězem Wimbledonu (za Spartu).
- **1975:** mistrovský titul. V semifinále Davis Cupu na Štvanici otočil Hřebec zápas s Rochem.
- **1976:** finále Poháru mistrů v Paříži *(klub)*.
- **1979:** vzniká Tendiv, klubové tenisové divadlo *(klub)*. Davis Cup ČSSR – Švédsko 3:2 na Štvanici.
- **1983:** demolice starého areálu kvůli stavbě metra.
- **1986:** nový stadion (16. 6.). Pohár federace ve dnech 20.–27. 7.: Navrátilová se vrátila po emigraci a ve finále porazila Mandlíkovou 7:5, 6:1.
- **1987–1999:** ATP Czech Open. Vítězi byli mj. Muster, Bruguera a Kafelnikov.
- **1990:** návrat k názvu I. ČLTK Praha *(klub)* a mistrovský titul.
- **1991:** první Prague Challenger, vyhrál ho Jan Kodeš ml.
- **2001:** Prague Open znovu na Štvanici; od tohoto roku klub počítá ročníky.
- **2002:** povodeň.
  - V hale bylo přes 4 m vody a škody dosáhly 100–120 mil. Kč.
  - Obnovu umožnila pojistka, kterou svaz uzavřel pět let předtím.
  - Areál se znovu otevřel koncem května 2003.
- **2005–2010:** WTA ECM Prague Open. Vítězkami byly mj. Safinová a Zvonarevová.
- **2011–2016:** éra Lucie Hradecké.
- **2012:** deska Drobného (14. 6.). Ivo Minář vyhrál Davis Cup.
- **2018:** 125 let (10. 6.) a titul v extralize po 28 letech.
- **2019:** obhajoba titulu. Wellness otevřeno v březnu.
- **2022:** kvalifikace BJK Cupu ČR – Velká Británie 3:2 na klubové antuce. Prezidentem se stává Šimůnek.
- **2023:** Vondroušová vyhrála Wimbledon. 22. 7. oslavy 130 let se společnou fotografií asi 200 členů. 28. 7. otevřena lávka HolKa.
- **2025:** AELTC na Štvanici (6.–8. 6.). CTC Senior Winners Group se hrála v klubu.
- **2026:**
  - rok Muchové;
  - WTA 250 Livesport Prague Open (turnaj TK Sparta) se poprvé hraje na Štvanici. Podle ČTK (15. 6. 2026) se tím WTA na ostrov vrací. Poslední ročník ECM Prague Open (WTA) se tu hrál v roce 2010, ČTK nepřesně uvádí 2009;
  - Prague Open plánovaný na začátek května se nekonal, dotace NSA nepřišla včas (Revue 01/2026, s. 2). Hrál se v náhradním termínu 16.–22. 8.;
  - nový stadion slaví 40 let;
  - podle newsletteru se na Štvanici otevírá Síň slávy českého tenisu.

**Prezidenti klubu s roky** *(klub)*:

| Období | Prezident |
|---|---|
| 1929–1938 | Robětín |
| 1938–1948 | Ing. J. Bečka |
| 1948–1956 | Štůla |
| 1957–1966 | Syřínek |
| 1966–1969 | Cvetler |
| 1969–1971 | Vít |
| 1972–1980 | Kliment |
| 1980–1986 | Nárovec |
| 1987–1990 | Ing. Jan Kodeš |
| 1990–2011 | Ing. František Stejskal |
| 2011–2022 | prof. Václav Klaus |
| od 2022 | Ing. Petr Šimůnek |

Starší předsedy uvádí klub bez let.

### I.ČLTK Revue a newsletter

- **I.ČLTK Revue:**
  - vychází od roku 2006 dvakrát ročně, **41 čísel**, standardně 44 stran;
  - 39 čísel je v PDF (chybí 01/2016 a 02/2016) a text všech je vybratelný, takže archiv jde fulltextově prohledávat;
  - náklad 500 / 700 / 400 výtisků;
  - texty Jiljí Kubec, grafika Kateřina Kuželová, hlavní fotograf Martin Sidorják.
  - Hlavička obálky je 20 let stejná: zlatý pás „I.ČLTK“, znak a navy pás „REVUE“.
  - Rubriky: editorial GM, Z klubu, Stalo se, Osobnost, Interview, Závodní tenis, Nejlepší výsledky, Partner klubu, Společnost, Kapitoly z historie Štvanice.
  - Archiv: https://cltk.cz/cs/klub/klubovy-casopis-icltk-revue/
- **Bulletin 1893–2023 ke 130. výročí:** je v PDF Revue 01/2023 na s. 29–72. Obsahuje zakládající členy, prezidenty, čestné členy, medailony hráčů a „Legendy na Štvanici“. Je to nejbohatší souhrn historie.
- **Newsletter:**
  - **78 PDF** (56 CS, 22 EN) od ledna 2015; klub ho uvádí jako „dvouměsíčník“;
  - 2–4 strany, editor Jan Pecha;
  - rubriky: Slovo manažera klubu, hlavní sportový příběh, Kalendář akcí, Klubový život.
- **Opakující se akce:**
  - klubové dny v květnu a září (čtyřhry, barbecue, hospodský kvíz);
  - Valná hromada v červnu;
  - letní kempy;
  - CTC U14 („I. ČLTK Praha Cup“) první listopadový víkend;
  - Mikulášská besídka;
  - Večer talentů;
  - Vánoční večírek v Letenském zámečku;
  - Babolat Amateur Tour (18. ročník v roce 2026).
  - Termíny akcí po 23. 9. 2026 klub zatím nezveřejnil.

### Prague Open a turnaje na Štvanici

- **Sekyra Group Prague Open 2026 by Advantage Cars:**
  - 26. ročník (klub počítá od roku 2001), 16.–22. 8. 2026. Původně se měl hrát v prvním květnovém týdnu, ale byl zrušen, protože dotace NSA nepřišla včas (Revue 01/2026, s. 2). Srpen je tedy náhradní termín, nepsat „tradiční srpnový turnaj“;
  - muži **ATP Challenger 75**, ženy **ITF W50**, antuka, vstup zdarma;
  - pořádá I. ČLTK a Perinvest;
  - prize money: muži €97 640, ženy $40 000;
  - vítězové 2026: Jan Kumstát, Jana Kovačková, čtyřhry **Andrew Paulson (I. ČLTK)** s Vliegenem a sestry Kovačkovy.
  - Web: https://www.pragueopen.net/ – **nikdy neodkazovat na pragueopen.org**, doména je unesená.
- **Historie Prague Open:**
  - vítězi byli mj. Schalken 2003, Hernych 2004, 2005 a 2008, Schwartzman 2014, Wawrinka 2020, Veselý 2024, Misolic 2025;
  - ženy: WTA 2005–2010, od 2011 ITF (Hradecká 2011, Vondroušová 2017 ve finále s Muchovou).
  - 25 obálek ročenek 2001–2025 je v `_raw`, práva je třeba ověřit.
- **Další turnaje na Štvanici 2026:**
  - WTA 250 **Livesport Prague Open** (20.–26. 7., tvrdý povrch): je to turnaj TK Sparta, I. ČLTK byl smluvním spoluorganizátorem a členové měli vstupenky zdarma;
  - **Conseq Prague Open** ITF W75 v hale (8.–15. 2. 2026, pořádá ČTS);
  - Oktagon 92 na centrálním dvorci.

### Partneři

- 34 partnerů a 2 varianty loga (`assets/partneri/`, `obsah.json → partneri`). Úrovně partnerství web neuvádí.
- **Soukromí partneři:** Vivus, J&T Banka, Aston, Pankrác a.s., GreenGas, Hodinářství Bechyně, ISO Praha, Noncore, TUkas, Smartwings, Messy Play, Sport Construction, ČPP, Advantage Cars, RPM Facility, Sporttechnik Bohemia, Crane Constancy Capital, Mizuno, Babolat, Peach Distribution, Glenfiddich, Astron studio, Pelmi, Resultina, Protenis, Moravia Steel, Kontron, Analytics Data Factory.
- **Veřejné instituce:** NSA, hl. m. Praha, MČ Praha 7, ČTS, PTS, Victoria VSC.

### Citlivá témata (před publikací konzultovat s klubem)

1. **Markéta Vondroušová:**
   - ITIA jí 22. 6. 2026 uložila čtyřletý zákaz do 21. 6. 2030. V srpnu 2026 se odvolala k CAS.
   - Klub 23. 6. 2026 vydal prohlášení na její podporu.
   - Wimbledon 2023 patří do historie. Aktuální tváří webu ale má být Muchová a každé použití portrétu Vondroušové je třeba odsouhlasit.
   - Údaj o její mamince na recepci je **NEOVĚŘENÝ** a soukromý, na web nepatří.
2. **Tresty:** Bartůňková (2024) a Minář (2009). Nezmiňovat.
3. **ČTS a Štvanice:** svaz řeší dotační kauzu a jeho předseda mluvil o možném zastavení stadionu (ČRo 16. 3. 2026). Centrkurt nepsat jako majetek klubu.
4. **Jména dětí** z TSM, SVT a rozvrhů nepřebírat.
5. **Práva k fotkám:** Getty Images, Martin Sidorják, Sekyra Group Prague Open, ČTS / Pavel Lebeda.

---

## Co chybí pro ostrý web

**Od klubu – obsah**

- **Otevírací doby:** areál, recepce a restaurace Tiebreak. Restaurace nemá kontakt, menu ani vlastní stránku.
- **Kalendář akcí** od října 2026: CTC U14, Mikuláš, Večer talentů, Vánoční večírek, extraliga. Oba klubové kalendáře jsou po 23. 9. prázdné.
- **Rozhodnutí o Vondroušové:** portrét, triptych wimbledonských vítězů a název Tenisové školy.
- **Síň slávy:**
  - roky udělení čestného členství (kromě Klause);
  - seznam zasloužilých včetně Ivo Mináře;
  - portréty čestných členů – dnešní mají jen 80–100 px, u Vondroušové chybí.
- **Historie:**
  - roky 12 titulů Motorletu (Lichner, *Malá encyklopedie tenisu*, 1985, s. 47–48);
  - rok návratu Kodeše (1978 × 1979);
  - místo založení (Židovský × Střelecký ostrov);
  - rok 1929 × 1930 u klubovny;
  - historie znaku.
- **Počet členů** a počet registrovaných hráčů.
- **Kapacita parkoviště.**
- Které multifunkční hřiště se pronajímá, a který vstup na plánu je brána od Hlávkova mostu.
- **Trenéři:** fotky a medailony Zdeňka Kubíka, Lukáše Vejvary a Bereniky Urbanové. Správný tvar jména „Pavluv / Pavlův“.
- **Partneři:** úrovně (generální, hlavní, mediální) a pořadí.
- **Anglické texty:** dnešní EN web je zastaralý (počty kurtů, ceny 2025, kontakty) a mnoho sekcí v něm chybí.
- **Babolat Amateur Tour 2025:** stránka vrací 404. Vítězové NON Profi Cupu 2019 a 2020 nejsou vyplnění.
- **Sezóna 2027:** termíny letních kempů, letní ceník kurtů a letní ceník Tenisové školy. Data 2026 jsou k 23. 9. 2026 už minulá.
- **Zásady ochrany osobních údajů a cookies pro návštěvníky webu.** Dnešní GDPR stránka se týká jen členů.
- **Vlastnictví a správa pevné haly P1, P2** (klub × ČTS). Kvůli tomu, jak psát „v hale klubu“.

**Fotky a práva**

- **Vlastní fotky od zadavatele.** Dnes je většina fotek areálu max. 1500–2000 px. Letecký panoramatický hero má jen 2000 px.
- **Licence:** agenturní a turnajové snímky (Getty, Sidorják, Sekyra Group, ČTS) a obálky ročenek Prague Open.
- **Pro nápady v návrzích:**
  - předměty (odznaky, pamětní mince, deska Drobného, obraz Tomáše Bíma v originále, dřevěné rakety);
  - společná fotka 130 let v plném rozlišení pro hloubkový zoom, jmenovky jen se souhlasem;
  - dnešní snímky ze stejných úhlů jako historické (pro „tehdy a teď“);
  - historické fotky ve vyšším rozlišení z archivu klubu (dnes 300–1600 px).
- **Autorství fotek** na cltk.cz není uvedené.

**Technika a integrace**

- **Výsledky hráčů:** widget Resultina / scorepresso běží jen přes http a nemá API ani CORS. Zda lze feed převzít technicky i právně, **NEOVĚŘENO**.
- **Rezervace:** napojení na RogerOnline a onlinehq (obsazenost). Rozhodnout, kdo bude vést stav areálu („Dnes na Štvanici“).
- **Fulltext Revue:** index z PDF textů v `_raw/revue/txt/`. U čísel 2006–2014 je chybné kódování „ď/ť“, které je třeba opravit.
- **Migrace archivu článků:**
  - 117 článků má hromadné datum 17. 3. 2023 (opravená data jsou v `clanky.json`);
  - 63 článků má ztracené obrázky;
  - 19 článků bylo smazáno;
  - staré odkazy na pragueopen.org je třeba přepsat.
- **Formulář přihlášky** doplnit podle Stanov: zákonný zástupce u dětí, firma a IČO, rodinná kombinace s cenou. Odstranit předvyplněné „Jak jste se o nás dozvěděli“.
- **Favicon a malé velikosti znaku:** pod 128 px netestováno.

**Ověřit fakta**

- 16 titulů (nezávisle doloženy 4).
- „Víc než šedesát“ grandslamových vítězů, kteří hráli na Štvanici. Podle vydání pramene je to 54, 58, 59 nebo „víc než 60“.
- Viceprezident CTC od 2011 × 2014.
- Kapacita stadionu 7 000 × 8 000.
- Rok přestavby centrkurtu na tvrdý povrch.
- Tradice „sezona začíná na Josefa“ (jediný pramen).
- Drobného návrat v roce 1993 a odznak s diamantem *(klub)*.
- Vazba Pavla Složila na klub (klub: „3 sezony na Štvanici“, titul 1975; cs Wikipedie: Slavia Praha IPS). Ovlivňuje číslo 20 titulů „hráčů Štvanice“.
- Konečné pořadí v extralize 2025 (jisté je jen 2. místo ve skupině).
