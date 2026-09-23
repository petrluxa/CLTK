# 08 – Výsledky, tituly a prestiž I. ČLTK Praha (externí rešerše)

> Podklad pro redesign www.cltk.cz – co z klubu dělá „výstavní klub českého tenisu“. Rešerše proběhla **23. 9. 2026**.
> Tento soubor **ověřuje zvenčí** a doplňuje klubové prameny zpracované v `06-historie-kronika.md` (hlavně období 2018–2026, trofejní vitrínu, Štvanici a Prague Open) a **opravuje chyby**, které v klubových textech jsou.
>
> **Jistota:** **jisté** = primární zdroj (tenisovaextraliga.cz, ITIA, usopen.org, turnajový web, klubový článek o vlastní akci) nebo dva nezávislé zdroje · **pravděpodobné** = jeden seriózní sekundární zdroj (Wikipedie, jedno médium) nebo pouze tvrzení klubu · **nejisté** = rozpor zdrojů / slabý zdroj. Co nešlo ověřit, je označeno **NEOVĚŘENO**.
>
> **Strojová data:** `podklady/data/tituly.json` (77 záznamů – tituly, medaile, finále, síně slávy) a `podklady/data/sin-slavy.json` (34 osobností s ověřenou vazbou na klub). Surové stažené stránky a wikitexty: `podklady/_raw/vysledky-prestiz/` (git-ignored).
>
> **Zkratky pramenů:** R23/1 = I.ČLTK Revue 01/2023 s bulletinem ke 130 letům (https://files.cltk.cz/zmtia6qkem301/2023_1_web%20%28kopie%29.pdf, číslo = strana PDF), R13/2 = I.ČLTK Revue 02/2013 (https://files.cltk.cz/k2f8xh6h3402/2_13_web.pdf), TEX = oficiální web extraligy tenisovaextraliga.cz, W-cs / W-en = Wikipedie (česká / anglická; čteno přes `action=raw` 23. 9. 2026).

---

## 0. Čísla do hero sekcí a infografik (ověřená)

| Údaj | Hodnota | Jistota | Zdroj |
|---|---|---|---|
| Založení | **1893** (Praha; od **1901** na Štvanici) | jisté | W-cs https://cs.wikipedia.org/wiki/I._%C4%8CLTK_Praha_(tenis); R23/1 s. 31 |
| Tituly mistra republiky družstev | **16** (12× jako Spartak Praha Motorlet 1956–1969, 1975, 1990, 2018, 2019) | jisté (počet) | R23/1 s. 3, 12, 63; W-cs klub („čtrnáct titulů, naposledy 1990“ + 2018 + 2019) |
| Wimbledonští vítězové ve dvouhře z klubu | **3** – Drobný 1954, Kodeš 1973, Vondroušová 2023 | jisté (tituly) | W-en jednotlivých hráčů; R23/2 s. 19 |
| Grandslamové tituly ve dvouhře hráčů spjatých s klubem | **7** (Drobný 3, Kodeš 3, Vondroušová 1) – pozor na definici, viz kap. 1 | jisté | W-en Drobný, Kodeš, Vondroušová |
| Grandslamové tituly celkem (dvouhra + čtyřhra + mix, hráči spjatí s klubem) | **20** | jisté (tituly) / různá jistota vazby | kap. 1, `tituly.json` |
| Nejčerstvější GS trofej | **US Open 2026 – smíšená čtyřhra**, Karolína Muchová (s J. Menšíkem) | jisté | https://www.usopen.org/en_US/news/articles/2026-08-26/muchova-mensik_win_2026_us_open_mixed_doubles_title.html; https://cltk.cz/cs/clanky/karolina-muchova-ovladla-smisenou-ctyrhru-na-us-open:385/ |
| Olympijské medaile hráčů klubu | **5** oficiálních (1920, 1988, 2012, 2016, 2021) + **2** z mezihry Atény 1906 (MOV je neuznává) | jisté | kap. 1.3 |
| Davisův pohár s hráčem klubu | **2** (1980, 2012) | jisté (tituly) | W-en 1980 Davis Cup; W-en Ivo Minář |
| Fed Cup / BJK Cup s hráčkou spjatou s klubem | **8** (1983, 1984, 1985, 2011, 2012, 2014, 2015, 2016); z toho **5** (2011–2016) prokazatelně jako členka klubu (Hradecká) | jisté (tituly) | kap. 1.5 |
| Mezinárodní síň slávy (Newport) | **2** – Drobný 1983, Kodeš 1990 | jisté | W-en Drobný, Kodeš |
| Centenary Tennis Clubs | **jediný člen z ČR** | jisté | http://www.centenarytennisclubs.com/members.htm |
| Centrální dvorec (1986) | **8 000** diváků (běžně uváděno) / **7 000 sedících** (ÚDU AV ČR) | pravděpodobné (rozpor) | W-cs areál; https://umeleckepamatky.udu.cas.cz/objekt/311-tenisovy-dvorec-na-stvanici |
| Prague Open 2026 | **26. ročník** (klub počítá od ECM Cupu 2001) | jisté | https://www.pragueopen.net/ |
| Hokejový oddíl I. ČLTK | **mistr Protektorátu 1940/41**, 3× stříbro v čs. lize 1945–48 | pravděpodobné | https://cs.wikipedia.org/wiki/I._%C4%8CLTK_Praha_(ledn%C3%AD_hokej) |

---

## 1. Trofejní vitrína – definice a počty

Klubové texty sčítají úspěchy všech hráčů, kteří s klubem **kdykoli** měli něco společného (vyrostli zde, jsou čestnými/zasloužilými členy). Pro web doporučuji **uvádět obě čísla poctivě** – je to nečekané a důvěryhodné (viz kap. 10, nápad „vitrína s dvojím metrem“):

- **Definice A – „hráči Štvanice“:** hráč, který v klubu vyrostl, hrál za něj nebo je jeho čestným/zasloužilým členem (klubová definice).
- **Definice B – „v barvách klubu“:** hráč byl v době triumfu prokazatelně (jisté) nebo velmi pravděpodobně členem I. ČLTK.

### 1.1 Grand Slam – dvouhra (7 titulů podle A, 1 podle B)

| Rok | Turnaj | Hráč | Vazba v době titulu | Jistota vazby |
|---|---|---|---|---|
| 1951, 1952 | Roland Garros | Jaroslav Drobný | Emigrant od 15. 7. 1949, startoval **za Egypt** (1950–1959) | jisté, že NEhrál za klub |
| 1954 | Wimbledon | Jaroslav Drobný | Za Egypt; v té době už žil v Anglii | jisté, že NEhrál za klub |
| 1970, 1971 | Roland Garros | Jan Kodeš | Hráč **Sparty Praha** (přestup 1966, návrat na Štvanici 1979) | jisté (W-cs Kodeš, W-cs Sparta, R23/1 s. 70) |
| 1973 | Wimbledon | Jan Kodeš | Sparta | jisté |
| 2023 | Wimbledon | **Markéta Vondroušová** | Členka od dětství, čestná členka; první nenasazená šampionka Wimbledonu | jisté |

Finále dvouhry bez titulu: **Věra Pužejová-Suková** Wimbledon 1962 (první čs. finalistka), **Vondroušová** RG 2019, **Muchová** RG 2023 a **Wimbledon 2026** (prohra s Lindou Noskovou 2:6, 7:5, 3:6 dne 11. 7. 2026 – první ryze české finále ženské dvouhry ve Wimbledonu), Drobný RG 1946, 1948, 1950 a Wimbledon 1949, 1952, Kodeš US Open 1971 a 1973. Zdroje: W-en hráčů; https://cltk.cz/cs/clanky/karolina-muchova-ve-finale-wimbledonu:381/; https://www.olympics.com/en/news/wimbledon-2026-womens-singles-final-results-karolina-muchova-linda-noskova.

### 1.2 Grand Slam – čtyřhra a mix (13 titulů podle A)

| Rok | Turnaj / soutěž | Hráč(i) klubu | Vazba v době titulu | Zdroj |
|---|---|---|---|---|
| 1948 | RG – čtyřhra (s L. Bergelinem) | Jaroslav Drobný | Ještě v ČSR, hrál za I. ČLTK – **pravděpodobné**. **Klubové přehledy tento titul NEUVÁDĚJÍ** | https://en.wikipedia.org/wiki/Jaroslav_Drobn%C3%BD |
| 1948 | RG – mix (s P. Canning Toddovou) | Jaroslav Drobný | dtto – **nový objev pro web** | tamtéž |
| 1957 | RG – mix | Věra Pužejová-Suková + Jiří Javorský | oba hráči klubu – pravděpodobné | https://en.wikipedia.org/wiki/V%C4%9Bra_Sukov%C3%A1; https://cs.wikipedia.org/wiki/Ji%C5%99%C3%AD_Javorsk%C3%BD |
| 1978 | RG – mix (s R. Tomanovou) | Pavel Složil | nejisté (viz rozpory kap. 9) | https://en.wikipedia.org/wiki/Pavel_Slo%C5%BEil |
| 1996, 1997 | RG – čtyřhra (s J. Kafelnikovem) | Daniel Vacek | v klubu jen krátce kolem 1990 – nejisté | https://en.wikipedia.org/wiki/Daniel_Vacek |
| 1997 | US Open – čtyřhra (s J. Kafelnikovem) | Daniel Vacek | dtto | tamtéž |
| 2006 | US Open – čtyřhra (s L. Paesem) | Martin Damm | v klubu od 1984 (klub) – pravděpodobné | https://en.wikipedia.org/wiki/Martin_Damm; R23/1 s. 61 |
| 2011 | RG – čtyřhra (s A. Hlaváčkovou) | Lucie Hradecká | členka od 2001 – jisté | https://cs.wikipedia.org/wiki/Lucie_Hradeck%C3%A1 |
| 2011 | Wimbledon – mix (s J. Melzerem) | Iveta Benešová | zasloužilá členka – pravděpodobné | https://en.wikipedia.org/wiki/Iveta_Bene%C5%A1ov%C3%A1 |
| 2013 | US Open – čtyřhra (s A. Hlaváčkovou) | Lucie Hradecká | jisté | https://en.wikipedia.org/wiki/Lucie_Hradeck%C3%A1 |
| 2013 | RG – mix (s F. Čermákem) | Lucie Hradecká | jisté | tamtéž |
| 2026 | US Open – mix (s J. Menšíkem) | **Karolína Muchová** | hráčka klubu od 2019 – jisté | usopen.org; cltk.cz čl. 385 |

**Součet:** A = 7 (dvouhra) + 13 (čtyřhra/mix) = **20 grandslamových titulů**. B jistě = **5** (Vondroušová 2023, Hradecká 3×, Muchová 2026); B včetně „pravděpodobné“ = **10** (+ Drobný 1948 2×, Suková/Javorský 1957, Benešová 2011, Damm 2006).

### 1.3 Olympijské hry

| Rok | Místo | Medaile | Hráč | Poznámka | Jistota | Zdroj |
|---|---|---|---|---|---|---|
| 1906 | Atény (mezihry) | bronz – čtyřhra | Ladislav a Zdeněk Žemlovi | mezihry MOV oficiálně neuznává | jisté (výsledek) | https://en.wikipedia.org/wiki/Bohemia_at_the_1906_Intercalated_Games |
| 1906 | Atény (mezihry) | bronz – dvouhra | Zdeněk Žemla | klub tuto medaili neuvádí | pravděpodobné | tamtéž |
| 1912 | Stockholm | **bez medaile** – 4. místo ve dvouhře i ve čtyřhře (Žemla/Just) | Ladislav Žemla | **cs Wikipedie klubu chybně píše o bronzu Žemla/Just** – prohráli zápas o 3. místo | jisté | https://en.wikipedia.org/wiki/Tennis_at_the_1912_Summer_Olympics_%E2%80%93_Men%27s_outdoor_doubles |
| 1920 | Antverpy | bronz – mix | Ladislav Žemla s Miladou Skrbkovou (první žena reprezentující ČSR na OH) | | jisté | https://en.wikipedia.org/wiki/Ladislav_%C5%BDemla; https://en.wikipedia.org/wiki/Milada_Skrbkov%C3%A1 |
| 1988 | Soul | bronz – čtyřhra | Milan Šrejber (s M. Mečířem) | Šrejber na Štvanici od 19 let | jisté | https://en.wikipedia.org/wiki/Milan_%C5%A0rejber |
| 2012 | Londýn | stříbro – čtyřhra | Lucie Hradecká (s A. Hlaváčkovou) | | jisté | https://en.wikipedia.org/wiki/Lucie_Hradeck%C3%A1 |
| 2016 | Rio | bronz – mix | Lucie Hradecká (s R. Štěpánkem) | | jisté | tamtéž |
| 2021 | Tokio 2020 | stříbro – dvouhra | Markéta Vondroušová | finále s Bencicovou 5:7, 6:2, 3:6 | jisté | https://en.wikipedia.org/wiki/Mark%C3%A9ta_Vondrou%C5%A1ov%C3%A1 |

Klub sám píše, že tenis měl na OH „předlouhou přestávku (1924–1988)“ (R23/1 s. 35) – dobrá kontextová věta do infografiky.

### 1.4 Davisův pohár

- **1980** – ČSSR vyhrála (finále s Itálií 4:1, **Sportovní hala** v Praze, ne Štvanice). Z klubu: **Jan Kodeš** (na Štvanici se vrátil 1979 na prosbu předsedy Klimenta, R23/1 s. 70 → pravděpodobně už hráč klubu), **Pavel Složil** (vazba nejistá), **Pavel Korda** jako trenér (jen klubový zdroj). Další hráči Lendl a Šmíd vazbu na klub nemají. Zdroje: https://en.wikipedia.org/wiki/1980_Davis_Cup; https://en.wikipedia.org/wiki/Pavel_Slo%C5%BEil; R23/1 s. 35.
- **2012** – ČR vyhrála (finále se Španělskem v Praze). **Ivo Minář** (mateřský klub I. ČLTK, vyrůstal na štvanickém zdymadle) odehrál v ročníku dvouhru proti Argentině (prohra s Mónacem). Zdroje: https://en.wikipedia.org/wiki/Ivo_Min%C3%A1%C5%99; https://en.wikipedia.org/wiki/2012_Davis_Cup_World_Group; https://cs.wikipedia.org/wiki/Ivo_Min%C3%A1%C5%99. – jisté.
- Kodeš byl v ČSSR týmu i při finále **1975** (prohra se Švédskem 2:3), Hřebec rovněž (W-cs Kodeš, W-en Hřebec).

### 1.5 Pohár federace / Fed Cup / Billie Jean King Cup

| Rok | Hráčka(y) spjaté s klubem | Vazba v době titulu | Zdroj |
|---|---|---|---|
| 1983 | Iva Budařová, Marcela Skuherská (čtyřhra) | **Budařová přišla do klubu až 1986** (klubová vizitka R23/1 s. 60) → v době titulu nečlenka; Skuherská nejisté | https://en.wikipedia.org/wiki/1983_Federation_Cup_(tennis); https://en.wikipedia.org/wiki/Marcela_Skuhersk%C3%A1 |
| 1984 | Iva Budařová, Marcela Skuherská (ve finále hrály Mandlíková/Suková) | dtto | https://en.wikipedia.org/wiki/1984_Federation_Cup_(tennis) |
| 1985 | Andrea Holíková (čtyřhry s R. Maršíkovou) | pravděpodobné | https://en.wikipedia.org/wiki/1985_Federation_Cup_(tennis) |
| 2011 | Lucie Hradecká (s Peschkeovou rozhodující bod finále v Moskvě), Iveta Benešová (1. kolo) | jisté / pravděpodobné | https://en.wikipedia.org/wiki/2011_Fed_Cup_World_Group; W-cs Hradecká |
| 2012, 2014, 2015, 2016 | Lucie Hradecká | jisté | https://en.wikipedia.org/wiki/Lucie_Hradeck%C3%A1 |

Klub chybně uvádí Budařovou a Skuherskou jako vítězky „v letech 1982 a 1983“ (R23/1 s. 63) – ČSSR vyhrála 1983, 1984 a 1985 (1982 vyhrály USA). Ve vítězném týmu 2018 žádná hráčka klubu nebyla (W-en 2018 Fed Cup World Group).

### 1.6 Juniorské grandslamy

| Rok | Turnaj | Hráč | Jistota | Zdroj |
|---|---|---|---|---|
| 1948 | Wimbledon – dvouhra dívek | Olga Mišková (první juniorský GS pro ČSR) | pravděpodobné (jen klub) | R23/1 s. 63 |
| 1985 | Wimbledon – dvouhra dívek | Andrea Holíková | jisté | https://en.wikipedia.org/wiki/Andrea_Hol%C3%ADkov%C3%A1 |
| 1989, 1990 | Wimbledon – dvouhra dívek | Andrea Strnadová | pravděpodobné | R23/1 s. 63; W-en Strnadová (kategorie Wimbledon junior champions) |
| 2010 | Australian Open – dvouhra dívek | Karolína Plíšková | jisté (titul) / pravděpodobné (vazba) | https://en.wikipedia.org/wiki/Karol%C3%ADna_Pl%C3%AD%C5%A1kov%C3%A1 |
| 2010 | Wimbledon – dvouhra dívek | Kristýna Plíšková | jisté (titul) / pravděpodobné (vazba) | https://en.wikipedia.org/wiki/Krist%C3%BDna_Pl%C3%AD%C5%A1kov%C3%A1 |
| 2019 | AO – čtyřhra chlapců (s D. Svrčinou), Wimbledon – čtyřhra chlapců, **US Open – dvouhra chlapců** | Jonáš Forejtek (juniorská světová jednička 9. 9. 2019) | jisté | https://en.wikipedia.org/wiki/Jon%C3%A1%C5%A1_Forejtek |

Vondroušová má podle W-en „dva juniorské grandslamové tituly ve čtyřhře“ – které, NEOVĚŘENO. Bartůňková: finalistka juniorského Wimbledonu 2023 (dvouhra) a RG 2022 (čtyřhra) – W-cs Bartůňková.

### 1.7 Síně slávy a ocenění

- **Jaroslav Drobný** – Mezinárodní tenisová síň slávy 1983, Síň slávy IIHF 1997; podle W-en **jediný člověk, který vyhrál Wimbledon i mistrovství světa v ledním hokeji** (MS 1947 se hrálo v Praze na Štvanici). – jisté (https://en.wikipedia.org/wiki/Jaroslav_Drobn%C3%BD)
- **Jan Kodeš** – Mezinárodní tenisová síň slávy 1990; Čs. sportovec roku 1973 (tehdy hráč Sparty). – jisté (W-en/W-cs Kodeš)
- **Ladislav Hecht** – Síň slávy slovenského tenisu 2007, Mezinárodní síň slávy židovského sportu 2005. – pravděpodobné (W-cs/W-en Hecht)
- **Markéta Vondroušová** – Sportovec roku 2023 (https://cltk.cz/cs/clanky/titul-sportovec-roku-2023-ziskala-marketa-vondrousova:255/), Zlatý kanár 2023 – podle W-cs první člen klubu, který vyhrál hlavní kategorii.

---

## 2. Mistrovské tituly družstev (liga / extraliga smíšených družstev, hraje se od 1951)

**Celkem 16 titulů** – klub i W-cs se shodují (W-cs: „čtrnáct titulů, naposledy v roce 1990“ + 2018 + 2019).

| Období / rok | Titul | Jistota | Zdroj |
|---|---|---|---|
| 1956–1969 (jako **Spartak Praha Motorlet**) | **12 titulů, z toho 11 v řadě** | jisté (počet), roky NEOVĚŘENY | R23/1 s. 3 (editorial: přejmenování 1956, „pod jménem továrny na výrobu leteckých motorů získal 12 mistrovských titulů“), s. 12, s. 44 („jedenáct titulů mistra republiky“) |
| 1963, 1964, 1965 | mistr ČSSR (s Kodešem v týmu) | pravděpodobné | W-cs Kodeš („Spartak Praha Motorlet (I.ČLTK Praha) (tituly: 1963, 64, 65, 66)“); R23/1 s. 69 („čtyřikrát za sebou s ním vyhrál ligu“) |
| 1966 | mistr ČSSR | jisté | tamtéž + foto „Mistr ligy 1966“ (R13/2 s. 11; R23/1 s. 45) |
| 1975 | mistr ČSSR – jediný titul éry TJ Dopravní podnik (1970–1990) | jisté | R23/1 s. 3, 12; W-cs klub |
| 1976 | **finále PMEZ** (Pohár mistrů evropských zemí, muži) v Paříži – prohra s Primerose/Primrose Brusel | jisté | R23/1 s. 45, 63; W-cs klub |
| 1990 | mistr ČSFR (Kodeš ml., Hovorka, Rikl, D. Vacek, A. Strnadová, Lásková, J. Strnadová) | jisté | R23/1 s. 12 |
| 2011 | finalista extraligy | pravděpodobné | W-en https://en.wikipedia.org/wiki/Czech_Extraliga_(tennis); R13/2 s. 7 |
| 2015 | finalista extraligy | nejisté (jen W-en bez reference) | W-en Czech Extraliga |
| **2018** | **mistr ČR** – po 28 letech | jisté | TEX (kap. 3) |
| **2019** | **mistr ČR** – obhajoba | jisté | TEX (kap. 3) |

Pozor na chronologii: Sparta Praha byla mistrem **1951, 1970–1974, 1976, 1978, 1980** a českou extraligu vyhrála **2000, 2020, 2024, 2025** (https://cs.wikipedia.org/wiki/Tenisov%C3%BD_klub_Sparta_Praha). Věta klubu „v 60. a na začátku 70. let získali jedenáct titulů v řadě“ (R23/1 s. 12) tedy nesedí s tituly Sparty 1970–74 – řada ČLTK musela skončit nejpozději 1969. Foto „tým 1973, kdy vyhrál I. ligu“ (R13/2 s. 7) je proto téměř jistě chybně popsané (R23/1 s. 63 ho uvádí jako „Mistrovský tým z roku 1975“).

---

## 3. Extraliga 2018–2026 – současný status (primární zdroj: tenisovaextraliga.cz)

Formát posledních let: 8 týmů, dvě semifinálové skupiny (Praha – hala Říčany, Morava – Prostějov), vítězové skupin hrají finále; poslední ve skupině hraje příští rok předkolo.

| Ročník | Výsledek I. ČLTK | Klíčová utkání | Mistr | Zdroj |
|---|---|---|---|---|
| **2018** | **1. místo** | skupina: Sparta 5:1 (Vondroušová – Siniaková 6:2, 7:6); finále 19. 12. Říčany: **Prostějov 5:4** (Hradecká/Krajicek supertiebreak 11:9, rozhodl debl Šátral/Staněk) | I. ČLTK | https://tenisovaextraliga.cz/tenisova-extraliga-2018/stvanice-porazila-spartu-a-ma-naslapnuto-do-finale-extraligy/ ; https://tenisovaextraliga.cz/tk-agrofert-prostejov/vitezem-dramatickeho-finale-je-i-cltk-praha/ |
| **2019** | **1. místo** | skupina: Sparta 5:4 (rozhodl debl Kližan/Forejtek 10:8), Jihlava 5:1; finále 18. 12. Říčany: **Přerov 5:2**; prémie 250 tis. Kč | I. ČLTK | https://tenisovaextraliga.cz/tk-agrofert-prostejov/infarktova-utkani-vyhrala-stvanice-a-prostejov/ ; https://tenisovaextraliga.cz/i-cltk-praha/i-cltk-praha-obhajil-titul-v-moneta-tenisove-extralize-2019/ |
| 2020 | 2. ve skupině (≈ 3.–4.) | Sparta 3:6 (Vondroušová porazila Kvitovou 6:3, 6:2), Říčany 5:4 | Sparta | https://tenisovaextraliga.cz/tk-sparta-praha/moneta-tenisova-extraliga-2020-sparta-porazila-stvanici/ ; https://tenisovaextraliga.cz/tk-agrofert-prostejov/druhym-finalistou-moneta-tenisove-extraligy-2020-je-prostejov/ |
| 2021 | dělené 3. místo | Sparta 2:6 (bez zraněné Vondroušové), RPM Praha výhra | Prostějov | https://cltk.cz/cs/clanky/moneta-tenisova-extraliga-2021:220/ ; https://tenisovaextraliga.cz/tk-agrofert-prostejov/sparta-porazila-stvanici-62-prostejov-udolal-prerov-63/ |
| 2022 | poslední ve skupině → 2023 předkolo | Sparta 0:9, Spoje 2:5 (bez Vondroušové, Muchové, Forejtka, Paulsona) | Prostějov | https://tenisovaextraliga.cz/tk-agrofert-prostejov/sparta-porazila-stvanici-prostejov-porazil-prerov/ ; https://tenisovaextraliga.cz/i-cltk-praha/spoje-porazily-stvanici-a-zahraji-si-se-spartou-o-finale/ |
| 2023 | předkolo (Most, výhra) → 2. ve skupině (≈ 3.–4.) | Sparta 1:5, Spoje 5:4 | Prostějov (finále se Spartou) | https://tenisovaextraliga.cz/i-cltk-praha/predkola-vyhrali-i-cltk-praha-a-ltc-pardubice/ ; https://tenisovaextraliga.cz/nezarazene/sparta-porazila-i-cltk-praha-51-a-je-prvnim-finalistou-extraligy/ ; https://tenisovaextraliga.cz/nezarazene/druhym-finalistou-je-prostejov-i-cltk-praha-udolala-spoje/ |
| 2024 | **3.–4. místo** (oficiální pořadí) | Sparta 4:5, Plíšková Tennis Academy 6:3 | Sparta (finále s Přerovem 5:1) | https://tenisovaextraliga.cz/nezarazene/prerov-porazil-prostejov-sparta-porazila-stvanici/ ; https://tenisovaextraliga.cz/nezarazene/prerov-porazil-pardubice-a-je-ve-finale-extraligy/ ; tabulka „Pořadí 2024“ na https://tenisovaextraliga.cz/ |
| 2025 | 2. ve skupině (≈ 3.–4.) | 16. 12. Sparta 1:5 (Valentová – Muchová 6:2, 6:2), 17. 12. Plíšková TA 6:3 | **Sparta** (finále 19. 12. Říčany s Přerovem 5:2) | https://tenisovaextraliga.cz/ (výsledky 2025) |
| 2026 | teprve se odehraje (prosinec 2026) – I. ČLTK má jistou účast v semifinálové skupině (v roce 2025 nebyl poslední) | – | – | odvozeno z formátu, NEOVĚŘENO termín |

**Soupiska I. ČLTK pro extraligu 2025** (https://tenisovaextraliga.cz/soupisky/): muži Gengel Marek (V), Forejtek Jonáš, Paroulek Tadeáš, Nicod Jakub, Paulson Andrew, Filip Jakub, Vocel Matěj, Barnat Jiří; ženy **Muchová Karolína, Vondroušová Markéta, Bartůňková Nikola, Viďmanová Darja, Martincová Tereza**, Fajmonová Sarah Melany, Žoldáková Denisa (V); kapitáni Petr Vaníček, **Ivo Minář**. (V = zřejmě hráč na hostování – NEOVĚŘENO význam zkratky.)

Postřeh: I. ČLTK má na papíře nejsilnější ženskou pětici v zemi (dvě grandslamové finalistky + dvě hráčky kolem TOP 50), ale od 2020 se do finále nedostal – rozhoduje hloubka mužské části.

---

## 4. Současní profesionálové – u koho je vazba ověřená

### 4.1 S ověřenou vazbou (podrobně v `sin-slavy.json`)

| Hráč | Vazba | Jistota | Zdroj |
|---|---|---|---|
| **Markéta Vondroušová** | Na Štvanici od mistrovství ČR v mini tenisu (2006, cca 7–8 let); nejdřív dojížděla ze Sokolova, od 15 let bydlela v klubu (pokoj vedle Miriam Kolodziejové); čestná členka; klubová školička nese název **Tenisová škola Markéty Vondroušové**; maminka pracuje na recepci klubu | jisté | https://www.irozhlas.cz/sport/olympijske-hry/marketa-vondrousova-tenisove-zacatky-na-stvanici_2406261516_mim ; https://en.wikipedia.org/wiki/Mark%C3%A9ta_Vondrou%C5%A1ov%C3%A1 ; https://cltk.cz/cs/klub/zaslouzili-clenove/ |
| **Karolína Muchová** | Od 2019 v klubu (přestup z TK Precheza Přerov kvůli sparingu v Praze; při vyvázání ze smlouvy pomáhal Jan Kodeš); extraliga za klub 2019 (titul), 2020, 2025 | jisté | https://cs.wikipedia.org/wiki/Karol%C3%ADna_Muchov%C3%A1 ; https://sport.aktualne.cz/tenis/us-open/o-stvanici-uz-vi-i-amerika-muchovou-musel-nekdo-zkrotit-rika/r~7f40f754450011eea25a0cc47ab5f122/ |
| Tereza Martincová | domovský oddíl; 2× mistryně extraligy; Conseq Prague Open '26 (W75) | jisté | W-cs Martincová; https://cltk.cz/cs/clanky/tereza-martincova-ovladla-conseq-prague-open-26:363/ |
| Nikola Bartůňková | domovský oddíl; WTA č. 34 (14. 9. 2026 dle W-en); 3. kolo AO 2026 | jisté | W-cs/W-en Bartůňková; https://cltk.cz/cs/clanky/uspesne-australian-open-pro-stvanicke-hracky:360/ |
| Jonáš Forejtek | hráč klubu; juniorský šampion US Open 2019 | jisté | W-en Forejtek; TEX soupisky |
| Darja Viďmanová | štvanická hráčka; 1. titul WTA 125 (Figueira da Foz 2026), finále WTA 250 Memphis 2026 | jisté | https://cltk.cz/cs/clanky/darja-vidmanova-ma-prvni-titul-z-wta:375/ ; https://cltk.cz/cs/clanky/darja-vidmanova-ve-finale-turnaje-wta-250-v-memphisu:383/ |
| Ivo Minář | mateřský klub, trenér mládeže, kapitán extraligy | jisté | W-cs Minář; TEX soupisky |
| Andrew Paulson, Matěj Vocel, Jakub Nicod, Jiří Barnat, Marek Gengel | extraligová soupiska 2025 | jisté | TEX soupisky |
| Zahraniční posily extraligy: Michael Mmoh, Andrej Martin, Michaëlla Krajicek (2018), Martin Kližan (2019), Jürgen Melzer (2011, dle W-en) | hostující hráči jen pro extraligu – na webu uvádět jen jako „posily“ | jisté (2018–19) / pravděpodobné (Melzer) | TEX 2018, 2019; https://en.wikipedia.org/wiki/I._%C4%8CLTK_Prague |

Muchová 2026 (klub + externí zdroje): titul **WTA 1000 Dauhá** (únor), **WTA 500 Bad Homburg** (červen), **finále Wimbledonu**, **US Open mix s Menšíkem** (srpen), kariérní maximum **č. 6** (červenec 2026 – jen W-en, pravděpodobné). Klubové články: https://cltk.cz/cs/clanky/karolina-muchova-vyhrala-tisicovku-v-dauha:364/ ; https://cltk.cz/cs/clanky/karolina-muchova-ziskala-titul-na-wta-premier-500-v-bad-homburgu:377/ .

Citát pro web (Muchová v Cincinnati 2023, Aktuálně.cz 28. 8. 2023): *„V nejstarším klubu v Praze. Jsem sice z jiné části republiky, před čtyřmi, pěti lety jsem se ale přestěhovala do Prahy a vybrala si Štvanici. … Je rodinný. Opravdu se tam cítím jako v rodině. Není tam nic zase tak speciálního, nemáme nějakou úžasnou tělocvičnu nebo úžasné vybavení. Je to docela skromné, ale ta energie, ta je skvělá.“*

### 4.2 Prověřeno – vazba na I. ČLTK NENALEZENA (na webu neuvádět jako „naše“)

| Hráč | Kde hraje / co bylo nalezeno | Zdroj |
|---|---|---|
| Kateřina Siniaková | TK Sparta Praha (extraliga 2018, 2019, 2020, soupiska 2025) | TEX 2018, 2019, 2020; https://tenisovaextraliga.cz/soupisky/ |
| Barbora Krejčíková | v soupiskách extraligy 2025 nefiguruje; na Štvanici hrála jen WTA Livesport Prague Open 2026 (2. nasazená) | TEX soupisky; https://en.wikipedia.org/wiki/2026_Prague_Open |
| Linda Nosková | TK Precheza Přerov (extraliga 2019, 2021, 2022, 2024, 2025), v roce 2023 za TK Agrofert Prostějov | TEX; https://tenisovaextraliga.cz/team-view/tk-agrofert-prostejov-2023/ |
| Tomáš Macháč | Sparta (2018, 2020, 2021), v roce 2025 na soupisce TK Agrofert Prostějov | TEX |
| Jiří Lehečka | TK Agrofert Prostějov (2020–2022, soupiska 2025) | TEX |
| Jakub Menšík | TK Agrofert Prostějov (2022, 2023) – s klubem ho spojuje jen spoluhráčství s Muchovou (US Open mix 2026) | TEX 2022, 2023 |
| Dalibor Svrčina | TK Agrofert Prostějov (2019–2025); s Forejtkem vyhrál juniorský AO 2019 ve čtyřhře | TEX; W-en Forejtek |
| Petra Kvitová | Sparta (hrála proti ČLTK 2020, 2022) | TEX |

---

## 5. Historické legendy – ověření vazby

### 5.1 Vazba potvrzená (detail v `sin-slavy.json`)
- **Jaroslav Drobný (1921–2001)** – na Štvanici od dětství (začínal jako sběrač míčků, W-en; „hrál v něm od čtyř let věku“, W-cs klub). Za **hokejový oddíl I. ČLTK** hrál 1936–1949 (W-cs Drobný). Emigroval 15. 7. 1949 v Gstaadu s V. Černíkem, občan Egypta 1950–1959, pak britský. Pamětní deska na Štvanici odhalena **14. 6. 2012** za účasti prezidenta Klause (W-cs Drobný). Čestný člen. – jisté
- **Jan Kodeš (nar. 1946)** – odchovanec (Čechie/Dukla Karlín → I. ČLTK), s Motorletem 4 tituly družstev 1963–66, **1966 přestup do Sparty** (W-cs Sparta: kvůli vztahu s budoucí manželkou Lenkou Rösslerovou, slíbený byt, návrat otce do administrativy), **1979 návrat** na Štvanici; 1982–1986 podíl na přestavbě areálu, první provozní ředitel, prezident klubu 1987–1990, ředitel ATP turnaje Czech Open. Čestný člen. – jisté (W-cs Kodeš; R23/1 s. 69–70; https://cs.wikipedia.org/wiki/Tenisov%C3%BD_are%C3%A1l_%C5%A0tvanice)
- **Věra Pužejová-Suková (1931–1982)** – „na klubové úrovni hrála za I. ČLTK Praha“ (W-cs), finále Wimbledonu 1962, mix RG 1957 s Javorským. – jisté
- **Jiří Javorský, Pavel Korda, Vlasta Vopičková, Marie Pinterová-Neumannová, Jiří Hřebec, Milan Šrejber, David Rikl, Martin Damm, Lucie Hradecká, Iveta Benešová, Jan Hernych, Iva Budařová, Ladislav Hecht** – všichni na seznamu **zasloužilých členů** (https://cltk.cz/cs/klub/zaslouzili-clenove/, převzato i W-cs klub). – jisté
- **Ladislav Hecht (1909–2004)** – „tenisově vyrostl v I. ČLTK“ (R23/1 s. 58); W-en: Davis Cup 1930–1939, s Menzelem semifinále čtyřhry Wimbledonu 1937, 1938 čtvrtfinále dvouhry Wimbledonu, 1932 zlato na Makabiádě; odmítl nabídku hrát za Německo (1938); tři dny před okupací 1939 odjel do USA. – pravděpodobné
- **Ladislav a Zdeněk Žemlovi** – kmenoví hráči rané éry (W-cs klub, R23/1 s. 58). Ladislav: první mistr ČSR ve dvouhře 1920 (na Štvanici), Davis Cup 1921–1927 (W-en). – jisté

### 5.2 Prověřeno – vazba na I. ČLTK NENALEZENA
| Jméno | Co je doloženo | Zdroj |
|---|---|---|
| Roderich Menzel | Rodák z Liberce, nejúspěšnější čs. daviscupař ve dvouhře; s klubem jen jako deblový partner Hechta (Wimbledon 1937) a mezinárodní mistr ČSR ve čtyřhře s Hechtem 1935–36 | W-cs Hecht; https://sever.rozhlas.cz/roderich-menzel-nejuspesnejsi-reprezentant-ceskoslovenska-v-tenise-bourlivak-z-9083813 (jen výsledek vyhledávání) |
| Martina Navrátilová | Klub Sparta (W-cs Sparta); na Štvanici hrála **Pohár federace 1986 za USA** (první návrat po emigraci) a ECM Prague Open 2006 | W-cs Sparta; https://en.wikipedia.org/wiki/1986_Federation_Cup_(tennis); https://cs.wikipedia.org/wiki/Prague_Open |
| Ivan Lendl, Tomáš Šmíd | Spoluhráči Kodeše a Složila v Davis Cupu 1980; Šmíd finalista Čedok Open 1987 na Štvanici | W-en 1980 Davis Cup; W-cs Czech Open |
| Helena Suková, Hana Mandlíková | Sparta (W-cs Sparta); na Štvanici prohrály finále Fed Cupu 1986; Suková je dcerou klubové legendy Věry Sukové a darovala raketu do expozice síně slávy na Štvanici (2026, nejisté) | W-cs Sparta; https://sportyzive.cz/tenis/martina-navratilova-na-stvanici-vzpominani-s-kodesem-a-sukovou/ |
| Jana Novotná | Vyhrála WTA Prague 1998 (W-cs Prague Open) – místo konání ročníku 1998 NEOVĚŘENO | W-cs Prague Open |
| Miloslav Mečíř | Jen deblový partner Šrejbera na OH 1988 | W-en Šrejber |
| Petr Korda | Sparta (W-cs Sparta); na Štvanici hrál Czech Open 1998 jako světová dvojka | W-cs Czech Open |

---

## 6. Štvanice – ostrov, stadion a „genius loci“

### 6.1 Ostrov (základní fakta)
- Plocha **15,53 ha**, délka 1,25 km, šířka 200 m, pobřeží 2,6 km (W-cs https://cs.wikipedia.org/wiki/%C5%A0tvanice_(ostrov)); Deník/ČTK 2016 uvádí „14 hektarů“ (rozpor, pravděpodobné W-cs). Katastr Holešovice, původně patřil ke Karlínu. Dřívější jména **Velký ostrov, Velké Benátky**, německy **Hetzinsel**.
- **Jméno:** koncem 17. stol. dřevěná aréna, kde psi „štvali“ medvědy, býky, jeleny i krávy; štvanice se konaly (opakovaně zakazované) do roku **1816** (W-cs ostrov). Areálový článek W-cs uvádí zákaz císařem Františkem I. **1802**, klubová revue „císařský dekret 1805“ (R23/1 s. 24) – ROZPOR.
- **1877** varieté Eugenia Averina; **1883** dřevěné ledárny; na konci 19. stol. tři restaurace; **1901** sem přesídlil I. ČLTK (W-cs ostrov).
- **Hlávkův most** 1908–1911 (dnešní podoba 1958–1962), zdymadlo a **vodní elektrárna 1913–1914** (arch. Alois Dlabač), rekonstrukce 1984–1987 s kanálem pro **vodní slalom** (W-cs ostrov). Negrelliho viadukt protíná ostrov uprostřed – pod oblouky parkoviště u tenisu.
- Klasicistní dům čp. 858 z r. **1824** (bývalý taneční sál) – od 2014 **VILA Štvanice**, „ostrovní scéna“ spolku Tygr v tísni (W-cs ostrov).
- Na místě dnešních kurtů stávala od konce 19. stol. **porodnice** (zbourána kolem 1980). Anekdota primátora Bohuslava Svobody (4. 4. 2025): *„Porodnice, kdy se otevřelo okno a z toho okna zavolala porodní bába na kurt, že přijel porod, a hráč-lékař položil raketu a šel k porodu.“* (https://prazsky.denik.cz/zpravy-region/praha-tenis-areal-stvanice-obnova-strecha-modernizace/) – jisté (citace), anekdota sama pravděpodobná.
- **Zimní stadion Štvanice** (arch. Josef Fuchs): první umělá ledová plocha v ČSR, první zápas **17. 1. 1931**; čtyři MS v hokeji **1933 (bronz), 1938 (bronz), 1947 (zlato – první titul mistrů světa), 1959 (bronz)**; 11. 2. 1955 první televizní přenos hokeje; kulturní památka od 2000; **zbořen v květnu 2011** (bourání od 28. 5. 2011) přes protesty; zbyla chátrající vstupní budova čp. 1125 s kavárnou (https://cs.wikipedia.org/wiki/Zimn%C3%AD_stadi%C3%B3n_%C5%A0tvanice; https://en.wikipedia.org/wiki/%C5%A0tvanice_Stadium; https://www.irozhlas.cz/zpravy-domov/praha-ostrov-stvanice-zmena-rekonstrukce-verejnost_2101181427_tzr). Domácí halou tu byl i hokejový oddíl I. ČLTK.
- Kultura a volný čas: krytý **skatepark** (od 1996 Mystic Skates, závod **Mystic Sk8 Cup** – součást World Cup of Skateboarding, od 1994), v 90. letech **nudistická pláž** u koupaliště, které **smetla povodeň 2002**; plovárna Baden Baden (2025); socha „Sedící dívka“ J. Horejce (1965); přívoz P7 2015–2023 (W-cs ostrov; https://en.wikipedia.org/wiki/%C5%A0tvanice).
- **Štvanická lávka „HolKa“** otevřena **28. 7. 2023**: ~300 m, 352 mil. Kč, dvacáté přemostění Vltavy v celé šíři, autoři Petr Tej a Marek Blank; **bronzové zábradlí se sochami zajíců na Štvanici, býků v Holešovicích a koní v Karlíně**; socha Řeka (Jan Hendrych) z vysokopevnostního betonu (https://ct24.ceskatelevize.cz/clanek/regiony/hlavni-mesto-praha/z-karlina-do-holesovic-pres-stvanici-praha-ma-novou-lavku-pro-pesi-a-cyklisty-3406); lávka vyhrála **Stavbu roku 2023** (zmínka v Pražském deníku 2025). – jisté
- **Vlajka ostrova** (neoficiální, soutěž 2025, autorka Petra Waldauf Slabá, vyvěšena 18. 9. 2025, zapsána do Národního registru vlajek 7. 3. 2026): zelený list, modrý vlnitý pruh, plážový míč, **lovecký pes ve skoku a laň ve skoku** (W-cs ostrov). – pravděpodobné

### 6.2 Tenisový areál – od dřevěného centrkurtu k betonu
- **1901** příchod na Štvanici; tenistům sloužilo pět kurtů, dřevěná klubovna a restaurace (ÚDU). **1926/1927** dřevěný centrální dvorec s provizorními tribunami u Negrelliho viaduktu (W-cs areál: 1926; W-cs klub: rekonstrukce ukončená 1927, kapacita 5 000); klubovna podle arch. Šuly za 750 tis. Kč (W-cs klub). Atmosféru „vždy umocňovaly houkající (a dříve i notně kouřící) vlaky“ (https://www.denik.cz/tenis/tenisova-stvanice-po-triceti-letech-od-modernizace-20160614.html).
- **1983** demolice starého areálu kvůli ražbě metra pod Vltavou; **16. 6. 1986** otevřen „nejmodernější tenisový areál v tehdejším Československu“ (Deník/ČTK 2016). – jisté
- **Architektura 1986** (Ústav dějin umění AV ČR, https://umeleckepamatky.udu.cas.cz/objekt/311-tenisovy-dvorec-na-stvanici): architekti **Josef Kales a Jana Novotná (Sportprojekt)**, realizace Metroprojekt, investor ČSTV; inspirace západními stadiony, tenisový svaz vedl Jan Kodeš; požadavek ÚHA, „aby stavba zapadala do prostoru, nekonkurovala městu a hmota stadionu byla zdrobněna plasticitou“. Materiály **šedý beton, stříbřitá ocel, doplněné hnědí**; **původní sedadla červená a žlutá, dnes modrá**; centrální dvorec pro **7 000 sedících**, semifinálový dvorec **800** diváků, dalších 8 antukových hřišť; socha **Tenista od Ladislava Janoucha (1986)** s deskou letopočtů založení tří tenisových organizací (W-cs ostrov). Stavba „patří mezi nejkvalitnější realizace českých sportovních staveb z 80. let“; zařazena i do knihy *Beton, Břasy, Boletice – Praha na vlně brutalismu* (2020). Literatura: Architektura ČSR 1987, č. 5, s. 417–427. – jisté
- **Vlastnictví:** centrální dvorec patří **Českému tenisovému svazu**, ostatní kurty **I. ČLTK** (W-cs areál; Šavrda pro TN.cz 2016 citován v iSportu). – jisté
- **2002 – povodeň:** areál „naprosto zdevastovaly“, v tenisové hale **více než 4 metry vody**, škody **100–120 mil. Kč** (Deník/ČTK 2016). Kvůli povodni odložena i revitalizace ostrova firmou Club Meridian Company (vybrána 2000, cca 1 mld. Kč / 45 měsíců – W-cs ostrov). Zimní stadion po povodni znovu otevřen v říjnu 2002 (W-en). – jisté
- **2013** centrální dvorec přestavěn z antuky na tvrdý povrch (W-cs areál; potvrzuje Šavrda 2016: „tam je hard court (beton)“, ČTS: kvůli údržbě a zimnímu zakrývání). – pravděpodobné (rok)
- **2016:** centrkurt „chátrající tenisový chrám“ – švédští filmaři na něm zaseli umělou trávu a natáčeli **Borg vs McEnroe** (Wimbledon 1980, Shia LaBeouf jako McEnroe); 17. 9. 2016 první florbalový superligový zápas pod širým nebem; 2014 turnaj Světové série v beachvolejbalu (vyhrály Sluková/Kolocová); „poslední tři roky centrkurt vrcholový tenis neviděl“ (https://isport.blesk.cz/clanek/isport-life/280335/vzbud-se-chatrajici-stvanice-borga-a-mcenroea-vystrida-florbalovy-unikat.html; https://www.expats.cz/czech-news/article/shia-labeouf-shooting-tennis-movie-in-prague). – jisté
- Dnešní klubová část: 13 venkovních antukových kurtů (3 s osvětlením), 3 dvorce s tvrdým povrchem, 2 kurty v pevné hale, v zimě 12 krytých dvorců (klub, https://cltk.cz/cs/klub/o-klubu/ – viz 06). Deník 2016 uváděl 17 antukových + 1 tvrdý + 2 v hale, v zimě 10 krytých – čísla se mění, na webu brát jen z klubu.

### 6.3 Současný stav a plány (2021–2026)
- **Město:** Magistrát (OHM) zadal kanceláři **RKAW** „Manuál vize ostrova“ (vítěz mezinárodní soutěže 2013 s více než 80 návrhy); participace s IPR Praha červen–září 2021; ostrov má sloužit „krátkodobé městské rekreaci“; ve hře i nový zimní stadion (https://iprpraha.cz/page/3945; iROZHLAS 18. 1. 2021). – jisté
- **ČTS, 4. 4. 2025:** vize obnovy „národního tenisového centra“ ve třech fázích – (1) oprava sociálního zázemí za cca 5 mil. Kč a otevření ochozu veřejnosti se stánky, (2) sanace stadionu, (3) **zastřešení** („v řádech nižších miliard korun… hudba budoucnosti“); architekt Martin Štrouf (Apostof), spolupráce s RKAW; první akce koncert **Janka Ledeckého s Filharmonií Hradec Králové 22. 6. 2025**; plán **tenisové síně slávy** na Štvanici; podpora primátora Svobody (https://sport.ceskatelevize.cz/clanek/tenis/tenisovy-svaz-chysta-postupnou-obnovu-stvanice-6990 ; https://prazsky.denik.cz/zpravy-region/praha-tenis-areal-stvanice-obnova-strecha-modernizace/ ; https://tn.nova.cz/sport/clanek/606057-teniste-chteji-obnovit-areal-na-stvanici-chybi-ale-finance). – jisté
- **ČTS, 16. 3. 2026 (J. Kotrba pro ČRo):** kvůli dotační kauze hrozí svazu platba 77 mil. Kč jistiny + příslušenství (až přes 100 mil.); *„Stoprocentně budeme muset zastavit stadion na Štvanici… Je to jediné aktivum, co máme.“* Pro rok 2026 pronájem Štvanice „pěti nebo šesti komerčním subjektům“ – Světový pohár v lezení, OKTAGON, turnaj WTA; ženský Prague Open se definitivně stěhuje ze Stromovky na Štvanici; Davis Cup Česko–USA (18.–19. 9. 2026) šel do O2 areny, protože Štvanici chybí osvětlení a střecha; síň slávy se má otevřít při WTA turnaji v červenci s artefakty od rodiny Suků (https://www.irozhlas.cz/sport/tenis/stvanici-chceme-vylepsit-ale-ted-nehori-hlavne-musi-fungovat-svaz-rika-sef_2603162059_rej). – jisté (citace); **citlivé** – na webu klubu nezmiňovat bez konzultace.
- **Červenec 2026:** WTA 250 **Livesport Prague Open** se poprvé hrál na Štvanici (20.–26. 7. 2026, tvrdý povrch, 17. ročník turnaje; vítězka Lilli Tagger, 2. nasazená Krejčíková) – https://livesportpragueopen.cz/ („se letos uskuteční na Štvanici na tvrdém povrchu“); https://en.wikipedia.org/wiki/2026_Prague_Open. – jisté. Při turnaji vzpomínka na **40 let Poháru federace 1986** (setkání Maršíkové a Holíkové – titulek na livesportpragueopen.cz) a výstava v prostorách budoucí **Síně slávy českého tenisu** (originální výstřižky, vstupenky, hotelový účet z 1986, raketa H. Sukové); následná návštěva Martiny Navrátilové s Kodešem a Sukovou – https://sportyzive.cz/tenis/martina-navratilova-na-stvanici-vzpominani-s-kodesem-a-sukovou/ (17. 8. 2026). – pravděpodobné (slabší web)
- Pozn.: WTA turnaj na Štvanici pořádá svaz/pořadatel ze Stromovky (adresa na webu turnaje Za Císařským mlýnem = TK Sparta), **ne I. ČLTK**.

---

## 7. Turnaje na Štvanici a Prague Open

### 7.1 Velká reprezentační utkání
- **1920** první mistrovství ČSR (Žemla dvouhra, Žemla/Just čtyřhra, Waffková ženy) – W-cs areál. – pravděpodobné
- **1921** první utkání Davis Cupu na čs. půdě: ČSR – Belgie 2:3, tým výhradně z I. ČLTK (Žemla, Ardelt, Just) – W-cs klub a areál; https://www.expats.cz/... – pravděpodobné
- **1975** mezizónové finále (semifinále) Davis Cupu **ČSSR – Austrálie** na Štvanici (W-en https://en.wikipedia.org/wiki/1975_Davis_Cup, odkaz na daviscup.com); klub: navýšeno na 6 000 diváků (R13/2 s. 17). – jisté
- Další DC utkání podle klubu (1947 Francie, 1948, 1964 Maďarsko, 1971 Španělsko, 1972 Švédsko, 1978 Švédsko, říjen 1986 Švédsko) – jen R13/2, externě NEOVĚŘENO.
- **Pohár federace 1986** (20.–27. 7. 1986, celý turnaj na antuce I. ČLTK): USA porazily obhájce ČSSR 3:0; Navrátilová ve finále porazila Mandlíkovou 7:5, 6:1 – první návrat po emigraci (https://en.wikipedia.org/wiki/1986_Federation_Cup_(tennis); Deník/ČTK 2016). – jisté
- **BJK Cup 2022, kvalifikace:** ČR – Velká Británie 3:2, antuka, I. ČLTK (https://en.wikipedia.org/wiki/2022_Billie_Jean_King_Cup_qualifying_round). – jisté

### 7.2 ATP turnaj Czech Open 1987–1999 (na dvorcích I. ČLTK)
První profesionální turnaj v ČSSR, okruh Grand Prix (1987–89), pak ATP World Series; 32 hráčů; dotace 150 000 USD (1987) → 500 000 USD (1999); ředitel **Jan Kodeš** („ve všech třinácti ročnících“ – W-cs; klub píše „12 ročníků“ – ROZPOR). Názvy: Čedok Open 1987–89, Czechoslovak Open, Škoda (Czecho)slovak Open, Paegas Czech Open 1997–98, Tento Czech Open 1999. Vítězové mj. **Thomas Muster 1988, Karel Nováček 1991–92, Sergi Bruguera 1993–94, Jevgenij Kafelnikov 1996**; startovali tu budoucí světové jedničky Kafelnikov, Safin, Ríos, Kuerten, Muster, Wilander; Petr Korda 1998 a Kafelnikov 1999 jako světové dvojky. Turnaj zanikl po 1999 kvůli nesplnění garance 400 000 USD. (https://cs.wikipedia.org/wiki/Czech_Open_(1987%E2%80%931999)) – pravděpodobné

### 7.3 Prague Open – mužský challenger a ženský turnaj
- **Muži (ATP Challenger):** 1991–1999 „Prague Challenger“ (25 000 USD) na Štvanici těsně před Czech Openem – první ročník 1991 vyhrál **Jan Kodeš ml.** nad Thomasem Enqvistem, 1996 finále Gustavo Kuerten; 2000 na Spartě; **od 2001 zpět na Štvanici** (ECM Cup, ECM Prague Open 2003–2008), 2009–2010 nehráno, 2011 Strabag, 2012 CNGvitall, 2013–2019 Advantage Cars (Milan Vopička ml., synovec Jana Kodeše), **2020–2022 „I.ČLTK Prague Open“**, 2023–2025 Advantage Cars, **2026 Sekyra Group Prague Open by Advantage Cars**. Nejvýše postavený vítěz Sjeng Schalken (2003, č. 12); dále mj. Diego Schwartzman 2014, **Stan Wawrinka 2020** (Challenger 125), Tallon Griekspoor 2021, Dominic Stricker 2023, Jiří Veselý 2024, Filip Misolic 2025, **Jan Kumstát 2026**. (https://cs.wikipedia.org/wiki/Prague_Open) – pravděpodobné
- **Ženy:** 1992–1998 WTA Tier IV (1996 v Karlových Varech; 1998 vyhrála **Jana Novotná**), 1999–2004 nehráno, **2005–2010 ECM Prague Open (WTA)** – vítězky Safinová 2005, Pe'erová 2006, Morigamiová 2007, Zvonarevová 2008, Bammerová 2009, Szávayová 2010; od 2011 okruh ITF (2011 **Lucie Hradecká**, 2017 **Markéta Vondroušová** nad Muchovou 7:5, 6:1). V exhibicích Navrátilová, Hingisová, Cash, Ivanišević, Krajicek (W-cs Prague Open); obálka 2009 dokládá exhibici Pat Cash / Henri Leconte / Mansour Bahrami. Místo konání ženských ročníků 1992–1998 po letech NEOVĚŘENO.
- **Počítání ročníků klubem:** 2019 = „19. ročník“, 2020 = „jubilejní 20. ročník“, 2021 = „21. ročník“ → klub počítá **od roku 2001** (https://cltk.cz/cs/clanky/turnaj-prague-open-2019-zna-viteze:77/ ; https://cltk.cz/cs/clanky/zive-icltk-prague-open-2020-by-moneta-money-bank:171/ ; https://cltk.cz/cs/clanky/icltk-prague-open-2021-by-moneta-money-bank:198/). Web turnaje 2026: „**26 YEARS OF THE INTERNATIONAL TENNIS TOURNAMENT**“. – jisté
- **Ročník 2026** (https://www.pragueopen.net/): **16.–22. 8. 2026**, **ATP Challenger 75 + ITF W50**, zvou „I. Český Lawn-Tennis Klub Praha a společnost Perinvest“, vstup zdarma, finále v sobotu 22. 8. v TV Prima Sport, parkování pod Hlávkovým mostem, doprava i lávkou HolKa. – jisté
- Kategorie a termíny posledních let (klubové články): 2019 22.–28. 7. (ATP Challenger 43 000 € + ITF 60 000 $); 2020 zrušen v březnu, pak 15.–22. 8. jen muži; 2021 ATP Challenger 80 + ITF W25; 2022 1.–8. 5.; 2024 5.–12. 5.; 2025 4.–11. 5. (ATP Challenger 75 + ITF W75). – jisté
- **Conseq Prague Open** – halový turnaj žen **ITF W75** v areálu klubu (pořadatel ČTS, klub spolupořadatel): 2025 poprvé, 8.–15. 2. 2026 podruhé, vítězka **Tereza Martincová** z kvalifikace (https://cltk.cz/cs/clanky/conseq-prague-open-2026:362/). – jisté
- **Safina Cup** – tradiční lednový juniorský turnaj ITF na dvorcích klubu (R13/1 s. 6 – viz 06).

**Stažené obálky ročenek Prague Open 2001–2025** (25 souborů, originální rozlišení cca 1000×1400 px, 2024–2025 až 1750×2496 px): `podklady/_raw/vysledky-prestiz/plakaty-prague-open/prague-open-plakat-2001.jpg … -2025.png`, zdroj https://www.pragueopen.net/history. Každý list = obálka programu vlevo + koláž fotek a jmen hlavních hvězd ročníku (např. 2006: Navrátilová, Bartoli, Azarenka, Stosur, Šafářová, A. Radwańská, Pe'er; 2009: Bammer, Schiavone, Benešová, Kvitová, Hradecká, Vaidišová). Autorská práva k fotkám – ověřit s klubem (web turnaje spravuje Jan Pecha, pecha@cltk.cz podle klubových článků).

---

## 8. Citlivá témata (před publikací konzultovat s klubem)

1. **Markéta Vondroušová – čtyřletý zákaz ITIA** (oznámeno 22. 6. 2026, do **21. 6. 2030**) za odmítnutí dopingové kontroly doma 3. 12. 2025 kolem 20:00; tribunál nenašel „no compelling justification“; možnost odvolání k CAS; během trestu nesmí hrát, trénovat ani navštěvovat akce ITF/WTA/ATP/GS **ani národního svazu** (https://www.itia.tennis/news/sanctions/marketa-vondrousova-suspended-for-refusing-anti-doping-test/). Vedení klubu 23. 6. 2026 ostře odsoudilo „neadekvátní“ a „likvidační trest“ a vyjádřilo jí plnou podporu (https://cltk.cz/cs/clanky/prohlaseni-vedeni-klubu-k-pripadu-markety-vondrousove:374/). → Wimbledon 2023 je fakt a patří do historie; aktuální „tvář“ klubu ale spíš Muchová.
2. **Nikola Bartůňková** – 2024 šestiměsíční trest ITIA za neúmyslné užití trimetazidinu (kontaminovaný doplněk), trest přijala (W-cs/W-en Bartůňková).
3. **Ivo Minář** – 2009 osmiměsíční zákaz ITF (metylhexanamin z doplňku) (W-cs Minář).
4. **Český tenisový svaz a Štvanice** – dotační kauza, možné zastavení stadionu (kap. 6.3); vztahy ČTS × I. ČLTK byly podle iSportu 2016 „složité“.
5. **Doména pragueopen.org je unesená** – dnes na ní běží indonéský hazardní web („KUDAMAS11 SLOT“). Klubové články z let 2019–2024 (ID 76, 171, 198, 224, 265) na ni stále odkazují; oficiální web turnaje je **https://www.pragueopen.net/**. Nový web nesmí na .org odkazovat a staré odkazy je třeba při migraci přepsat. (Ověřeno stažením 23. 9. 2026, `_raw/vysledky-prestiz/po-pragueopen.org.html`.)

---

## 9. Rozpory a opravy klubových textů (důležité pro copywriting)

| # | Klubový text | Správně / externě | Zdroj opravy |
|---|---|---|---|
| 1 | Složil – mix RG „v Paříži 1975“ (R23/1 s. 35) | **1978** (s R. Tomanovou) | W-en Složil |
| 2 | Vacek – „dva tituly ve čtyřhře v Paříži a jeden ve Wimbledonu“ (R23/1 s. 35) | RG 1996, RG 1997 a **US Open 1997** | W-en Vacek |
| 3 | Budařová a Skuherská vítězky Fed Cupu „1982 a 1983“ (R23/1 s. 63) | **1983 a 1984**; Budařová navíc v klubu až od 1986 | W-en 1983/1984 Federation Cup; R23/1 s. 60 |
| 4 | cs Wikipedie klubu: Žemla s Justem „bronzové medaile ve čtyřhře“ 1912 | **4. místo** (prohra v zápase o bronz) | W-en 1912 men's outdoor doubles |
| 5 | Klubový web u Drobného: ZOH 1947, zlato; wimbledonská finále 1952 a 1948 | ZOH **1948**, **stříbro**; finále Wimbledonu **1949** a 1952 | W-en Drobný |
| 6 | Klubové přehledy GS titulů Drobného (jen dvouhra) | Chybí **RG 1948 čtyřhra i mix** – jediné jeho GS tituly z doby, kdy byl ještě hráčem klubu | W-en Drobný |
| 7 | Kodeš ředitelem „12 ročníků turnaje ATP Tour“ (R23/1 s. 70) | W-cs: ředitelem **všech 13 ročníků** Czech Open 1987–1999 | W-cs Czech Open |
| 8 | Složil „strávil na Štvanici tři sezony“ | W-cs: „na klubové úrovni hráčem Slavie Praha IPS“ | W-cs Složil |
| 9 | „11 titulů v řadě v 60. a na začátku 70. let“ (R23/1 s. 12) | Sparta mistrem 1970–1974 → řada ČLTK skončila nejpozději 1969 | W-cs Sparta |
| 10 | cs Wikipedie: klub je „druhý nejstarší“ v ČR (plzeňský odbor Cífky z ledna 1893) | Klub se prezentuje jako nejstarší – na webu psát „nejstarší tenisový klub v Praze“ / „od roku 1893“ | W-cs klub |
| 11 | Kapacita centrkurtu 8 000 | ÚDU AV ČR: 7 000 sedících; iSport 2016: 7 000 | ÚDU; iSport |
| 12 | Zákaz štvanic: 1805 (klub) | W-cs ostrov: štvanice do 1816; W-cs areál: zákaz 1802 | – |
| 13 | Plocha ostrova 14 ha (Deník 2016) | 15,53 ha (W-cs) | – |
| 14 | W-en klubu: „Chairman: Vladislav Šavrda“ | Šavrda je dlouholetý (generální) manažer; prezidentem je od 7/2022 **Petr Šimůnek** | W-cs klub; Aktuálně 2023 |

---

## 10. Nápady pro design – co nikdo nemá

1. **„Vitrína s dvojím metrem“** – interaktivní trofejní síň s přepínačem *„Vyrostli na Štvanici“* (20 GS titulů, 7 ve dvouhře) × *„V barvách klubu v den triumfu“* (5 jistých). Poctivost jako luxus – žádný klub to takhle nedělá.
2. **Wimbledonská linka Štvanice:** 1954 (Drobný) · 1962 (finále Suková) · 1973 (Kodeš) · 2023 (Vondroušová) · 2026 (finále Muchová) – jedna tenká zlatá/travnatá čára přes celou stránku.
3. **„Klub, který vyhrál i hokej“** – Drobný jako jediný člověk s Wimbledonem i titulem mistra světa v hokeji; hokejový oddíl I. ČLTK mistrem 1940/41; MS 1947 na Štvanici. Nečekaná kapitola pro „O klubu“.
4. **Zajíc jako tichý symbol ostrova** – štvanice (hon) dala ostrovu jméno a bronzoví zajíci zdobí zábradlí lávky HolKa (2023). Drobná liniová ikona zajíce místo obligátního míčku (např. v patičce, loaderu, 404).
5. **Paleta z brutalistního centrkurtu 1986:** šedý beton + stříbřitá ocel + hněď, akcent **původní červené a žluté sedačky** – decentní, světlá, architektonicky podložená paleta pro jednu z variant (vedle antukové oranže).
6. **25 obálek Prague Open 2001–2025** (stažené) jako horizontální „stěna ročníků“; 26. ročník 2026 jako živá dlaždice.
7. **Mapa ostrova ve vrstvách času:** aréna štvanic (17. stol.–1816) → Averinovo varieté 1877 → ledárny 1883 → porodnice → zimní stadion 1931–2011 → koupaliště do 2002 → centrkurt 1986 → HolKa 2023. Posuvník let.
8. **Hladina povodně 2002** – jemná vodorovná linka „4 m vody v hale“ v sekci historie areálu.
9. **„Borg vs McEnroe se točil tady“** – filmová zajímavost (2016), Wimbledon 1980 rekonstruovaný na Štvanici.
10. **Odznak Centenary Tennis Clubs** – „jediný český člen sdružení stoletých klubů“ (vedle Queen's, Kooyong, Longwood, Rot-Weiss Berlin…).
11. **Prosincový „extraligový mód“ webu** – živé výsledky/soupiska (Muchová, Bartůňková, Martincová, Viďmanová, Forejtek) z tenisovaextraliga.cz.
12. **Fed Cup 1986 – 40 let** (2026): příběh otevření stadionu a návratu Navrátilové; ve spojení se vznikající Síní slávy českého tenisu na Štvanici.

---

## 11. Mezery (co se nepodařilo ověřit)

- Přesné roky 12 titulů z éry Motorletu (1956–1969) – doloženy jen 1963–1966 (Kodeš) a 1966 (foto). Pramen: Lichner, *Malá encyklopedie tenisu* (1985), s. 47–48 – není online.
- Finále extraligy 2015 (jen W-en bez zdroje); konečné pořadí ČLTK v letech 2020, 2022, 2023, 2025 (oficiálně jen 2024 = 3.–4.).
- Příslušnost ke klubu v době titulu: Složil (1978, 1980), Damm (2006), Benešová (2011), Skuherská (1983–84), Holíková (1985).
- Olga Mišková – juniorský Wimbledon 1948 externě neověřen (Wikipedie omezila přístup API); Vondroušová – které dva juniorské GS ve čtyřhře.
- Seznam všech utkání Davis Cupu na Štvanici (externě jen 1921 a 1975).
- Místo konání ženských WTA ročníků 1992–1998.
- Kodeš – 12 × 13 ročníků Czech Open; rok příchodu na Štvanici (1957 × ≈1959).
- Muchová – kariérní maximum č. 6 (červenec 2026) jen z Wikipedie; výsledky Prague Open 2026 (Kumstát, Kovačková) jen z W-cs.
- iDNES rozhovor s V. Šavrdou (2. 11. 2023) – za paywallem, nečteno.
- Status I. ČLTK jako střediska Národního tenisového centra (zmínka jen v W-cs TK Agrofert Prostějov) – NEOVĚŘENO.

## 12. Prameny (hlavní)

- Klub: https://cltk.cz/cs/klub/zaslouzili-clenove/ ; články https://cltk.cz/cs/clanky/… (ID 77, 132, 171, 198, 209, 220, 224, 255, 265, 311, 323, 348, 360, 362, 363, 364, 374, 375, 377, 381, 383, 384, 385); revue R23/1, R13/2 (viz hlavička).
- Extraliga: https://tenisovaextraliga.cz/ ; https://tenisovaextraliga.cz/soupisky/ ; články 2018–2024 (odkazy v kap. 3); WP API výpis příspěvků.
- ITIA: https://www.itia.tennis/news/sanctions/marketa-vondrousova-suspended-for-refusing-anti-doping-test/
- US Open: https://www.usopen.org/en_US/news/articles/2026-08-26/muchova-mensik_win_2026_us_open_mixed_doubles_title.html
- Olympics.com: https://www.olympics.com/en/news/wimbledon-2026-womens-singles-final-results-karolina-muchova-linda-noskova
- Turnaje: https://www.pragueopen.net/ , https://www.pragueopen.net/history , https://livesportpragueopen.cz/
- Centenary Tennis Clubs: http://www.centenarytennisclubs.com/members.htm
- Média: ČT sport 4. 4. 2025; Pražský deník 4. 4. 2025; TN.cz 4. 4. 2025; iROZHLAS 18. 1. 2021, 26. 6. 2024, 16. 3. 2026; Aktuálně.cz 28. 8. 2023; Deník/ČTK 14. 6. 2016; iSport 13. 9. 2016; Expats.cz 20. 8. 2016; ČT24 28. 7. 2023; sportyzive.cz 17. 8. 2026.
- Odborné: ÚDU AV ČR Umělecké památky Prahy – https://umeleckepamatky.udu.cas.cz/objekt/311-tenisovy-dvorec-na-stvanici ; IPR Praha – https://iprpraha.cz/page/3945
- Wikipedie (sekundární, čteno 23. 9. 2026): cs – I. ČLTK Praha (tenis), I. ČLTK Praha (lední hokej), Tenisový areál Štvanice, Štvanice (ostrov), Zimní stadión Štvanice, Czech Open (1987–1999), Prague Open, Tenisový klub Sparta Praha, TK Agrofert Prostějov, Jan Kodeš, Jaroslav Drobný (tenista), Věra Suková, Jiří Javorský, Ladislav Hecht, Karolína Muchová, Lucie Hradecká, Tereza Martincová, Nikola Bartůňková, Ivo Minář, Pavel Složil; en – I. ČLTK Prague, Štvanice, Štvanice Stadium, Czech Extraliga (tennis), 2026 Prague Open, Jaroslav Drobný, Jan Kodeš, Markéta Vondroušová, Karolína Muchová, Lucie Hradecká, Milan Šrejber, Ladislav Žemla, Milada Skrbková, Bohemia at the 1906 Intercalated Games, Tennis at the 1912 Summer Olympics – Men's outdoor doubles, 1975/1980 Davis Cup, 2012 Davis Cup World Group, 1983/1984/1985/1986 Federation Cup, 2011 Fed Cup World Group, 2022 BJK Cup qualifying round, Ivo Minář, Pavel Složil, Iva Budařová, Marcela Skuherská, Andrea Holíková, Iveta Benešová, Martin Damm, David Rikl, Daniel Vacek, Andrea Strnadová, Karolína/Kristýna Plíšková, Jonáš Forejtek, Nikola Bartůňková, Jiří Hřebec, Marie Pinterová, Věra Suková, Ladislav Hecht.
