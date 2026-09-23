# 05 – I.ČLTK Revue: archiv klubového časopisu (2006–2026)

Rešerše pro redesign webu cltk.cz. Stav ke dni 23. 9. 2026.
Hlavní zdroj: https://cltk.cz/cs/klub/klubovy-casopis-icltk-revue/. Navíc všech 39 PDF, která jsou na files.cltk.cz ke stažení.
Strojově čitelná data jsou v `podklady/data/revue.json` (41 záznamů), obálky v `assets/revue/`.
Surová data (HTML, PDF, texty, pracovní JSON) leží v `podklady/_raw/revue/`.

---

## 1. Shrnutí v číslech

- **41 čísel na webu:** každý rok 01 a 02 v letech 2006–2025 a k tomu 01/2026. Časopis vychází **dvakrát ročně**. Jarní číslo se uzavírá v dubnu nebo květnu, obvykle kolem Prague Open. Podzimní číslo se uzavírá v říjnu nebo listopadu (uzávěrky jsou v tiráži, viz JSON).
- **PDF je u 39 čísel.** Čísla **01/2016 a 02/2016 mají na webu prázdný odkaz** (`<a href="">`), takže je tam jen obálka. Prázdný odkaz byl už ve snapshotu Wayback Machine z 18. 7. 2019 a PDF se v Archive.org nenašlo.
- **Rozsah:** standardně **44 stran**. Výjimky:
  - 02/2023 a 02/2025 mají 52 stran,
  - 01/2009 a 02/2009 mají 48 stran,
  - 01/2023 má 72 stran PDF: Revue na PDF str. 1–28 a k ní přivázaný **Bulletin ke 130. výročí klubu** na str. 29–72 (viz kap. 6).
- **Formát PDF:**
  - čísla 2006–2013 jsou v **dvoustranách** (22 nebo 24 listů PDF; 1. list = zadní + přední obálka),
  - od 2014 jde o jednotlivé strany.
  - Velikost souborů je 6–49 MB, dohromady ~616 MB.
- **Text je všude vybratelný** (PyMuPDF), takže celý archiv jde fulltextově prohledávat. Ve starých PDF (2006–2014) je chybně zakódované „ď/ť“: znaky „+“, „&“, „,“ ve slovech jako „Hu+ka“ (= Huťka), „Bu+me“ (= Buďme) nebo „,ábel“ (= Ďábel). V JSON jsem to opravil a ověřil renderem stránky.
- **Náklad podle tiráží:**
  - **500 výtisků** v letech 2006–2013,
  - **700 výtisků** od 01/2014 do 02/2023,
  - **400 kusů** od 01/2024 do 01/2026,
  - Bulletin 2023: 300 výtisků.
- **Tvůrci podle tiráží:**
  - texty: **Jiljí Kubec** (po celou dobu),
  - grafická úprava: **Kateřina Kuželová** (po celou dobu),
  - hlavní fotograf: **Martin Sidorják** (skoro všechny titulní fotky od 2009),
  - produkce: **EagleMedia** (do 01/2024), od 02/2024 **Putt PD s.r.o.**,
  - tisk: Realtisk (2006–2012), Triangl, a.s. (2014), Astron (2015–2026).
- 01/2026 samo sebe popisuje takto: *„I.ČLTK REVUE je tu s námi už dvacet let“* a *„Titulních stran bylo už čtyřicet“* (Revue 01/2026, s. 32–35 = PDF str. 34–37, článek „Revue nás baví. A vás?“, s přehlídkou všech 40 obálek).

## 2. Jak je archiv na současném webu udělaný

- Stránka má jeden textový odstavec: „Na těchto stránkách naleznete klubový časopis I.ČLTK Revue v elektronické formě.“ Pod ním jsou dlaždice `div.revue-links-item`. Každá dlaždice je jeden odkaz `<a href="//files.cltk.cz/…pdf">` a v něm obrázek obálky, `<h2>I.ČLTK Revue</h2>` a `<p>01/2026</p>`. Obálka tedy patří ke svému PDF přímo přes HTML strukturu, žádné párování podle názvu není potřeba.
- **Pořadí dlaždic:** 01/2026, 01/2025, 02/2025, 01/2024, 02/2024 … až 01/2006, 02/2006. Uvnitř roku jdou čísla vzestupně, roky sestupně.
- **Označení není jednotné:** „01/2026“ proti „01 / 2024“.
- **HTML obsahuje chybu:** `<img <img src=…>`.
- **Obrázky obálek:**
  - Novější obálky (2019–2025) jsou často **snímky obrazovky** (soubory „Snímek obrazovky 2024-10-31 v 11.57.45.png“).
  - Originály starších obálek (bez `/optimized/`) jsou **CMYK JPG** o rozměru ~2470×3300 px. Verze `/optimized/` jsou RGB ve stejném rozlišení. Použil jsem RGB verze, aby nebyly posunuté barvy.
  - Obálky 01/2007 a 02/2007 mají na webu jen **150×209 px**, 02/2021 jen **576×768 px**. Tyto tři jsem vyrenderoval z PDF.
- Úvodní strana webu má blok `c-revue` s obrázkem `//files.cltk.cz/site/revue/revue-05-26.jpg` a popiskem „I.ČLTK Revue 05/2026“. Revue se tedy už teď propaguje na homepage.
- Na files.cltk.cz jsou podle Wayback CDX také PDF `newsletter 2016-01` … `2019-01` a `PO_bulletin_2020_web.pdf` (bulletin Prague Open 2020). To není Revue, ale může jít o „buletin“ zmíněný zadavatelem. Tuto sekci jsem nezpracovával.

## 3. Kompletní seznam čísel

Stran = strany časopisu (u dvoustran je v závorce počet listů PDF). Plný obsah každého čísla (OBSAH se stránkami) je v `revue.json` → `obsah`. Všechny titulky na obálkách jsou v → `titulni_strana`.

| Číslo | Hlavní titulek obálky | Na obálce | Stran | PDF | Obálka |
|---|---|---|---|---|---|
| 01/2026 | VELKÝ TITUL PRO KAROLÍNU | Karolína Muchová s trofejí z WTA 1000 v Dauhá (foto Paul Zimmer, tiráž) | 44 | [PDF 13.3 MB](https://files.cltk.cz/2so4wzo52sl01/REVUE_2026_1_web.pdf) | `assets/revue/revue-2026-1.jpg` |
| 02/2025 | NIKOLA BARTŮŇKOVÁ – Z PEKLA DO RÁJE | Nikola Bartůňková (foto Martin Sidorják) | 52 | [PDF 13.7 MB](https://files.cltk.cz/o6xjsls6zgj01/2025_2_web.pdf) | `assets/revue/revue-2025-2.jpg` |
| 01/2025 | TRADICE ZŮSTÁVÁ, KULISY SE MĚNÍ | vizualizace vstupu do areálu s novým welcome boardem „I. Český Lawn Tennis Klub Praha 1893“ | 44 | [PDF 10.0 MB](https://files.cltk.cz/n8asxxd98fg01/I.%C4%8CLTK%20Revue%2001_2025.pdf) | `assets/revue/revue-2025-1.jpg` |
| 02/2024 | VÝJIMEČNÁ KAROLÍNA | Karolína Muchová při podání | 44 | [PDF 10.7 MB](https://files.cltk.cz/fa4cnmbc2qc01/REVUE_2024_2_web.pdf) | `assets/revue/revue-2024-2.jpg` |
| 01/2024 | TENISOVÁ ŠKOLA MARKÉTY VONDROUŠOVÉ | Markéta Vondroušová u banneru „Tenisová škola Markéty Vondroušové“, dole foto dětí ze školičky | 44 | [PDF 10.8 MB](https://files.cltk.cz/7bbfxfji4n701/REVUE_2024_1_web.pdf) | `assets/revue/revue-2024-1.jpg` |
| 02/2023 | MARKÉTA VONDROUŠOVÁ VITĚZKA [sic] WIMBLEDONU 2023 | koláž tří hráček s trofejemi (dle titulků Vondroušová, Muchová, Bartůňková) | 52 | [PDF 14.0 MB](https://files.cltk.cz/mgzpjbrkaq401/2023_2_web.pdf) | `assets/revue/revue-2023-2.jpg` |
| 01/2023 | BYLO TO ÚŽASNÉ – LUCIE HRADECKÁ | Lucie Hradecká s trofejemi | 72 | [PDF 14.1 MB](https://files.cltk.cz/zmtia6qkem301/2023_1_web%20%28kopie%29.pdf) | `assets/revue/revue-2023-1.jpg` |
| 02/2022 | PETR ŠIMŮNEK: MÁME VÝBORNÝ TÝM | Petr Šimůnek (prezident klubu) | 44 | [PDF 9.6 MB](https://files.cltk.cz/wuz6a5cj4w34/2022_2_web.pdf) | `assets/revue/revue-2022-2.jpg` |
| 01/2022 | BJK CUP: NEMILOSRDNÁ MARKÉTA | hráčky v objetí na kurtu (titulek „BJK Cup: Nemilosrdná Markéta“; identita NEOVĚŘENO) | 44 | [PDF 9.3 MB](https://files.cltk.cz/kbegd1fjgt34/2022_1_web%20%28kopie%29.pdf) | `assets/revue/revue-2022-1.jpg` |
| 02/2021 | MARKÉTA VONDROUŠOVÁ: NA PŘIVÍTÁNÍ NEZAPOMENU | Markéta Vondroušová s olympijskou stříbrnou medailí (Tokio) | 44 | [PDF 11.9 MB](https://files.cltk.cz/88ezxt1iuz32/2021_2_web%20%28kopie%29.pdf) | `assets/revue/revue-2021-2.jpg` |
| 01/2021 | KAROLÍNA MUCHOVÁ NA VLASTNÍCH NOHOU | Karolína Muchová a dva muži (jména na obálce neuvedena) | 44 | [PDF 11.0 MB](https://files.cltk.cz/rjqd2dshct31/2021_1_web.pdf) | `assets/revue/revue-2021-1.jpg` |
| 02/2020 | TEREZA MARTINCOVÁ: MÁM ÚŽASNÝ RODIČE | Tereza Martincová | 44 | [PDF 11.2 MB](https://files.cltk.cz/pntjr4skon27/2020_2_web.pdf) | `assets/revue/revue-2020-2.jpg` |
| 01/2020 | DALŠÍ CÍL JEDNIČKY NIKI? GRANDSLAM | „Niki“ = Nikola Bartůňková (dle obsahu čísla: „Nový režim = trefa (Nikola Bartůňková)“) | 44 | [PDF 12.3 MB](https://files.cltk.cz/1mdybjtoem22/2020_1_web.pdf) | `assets/revue/revue-2020-1.jpg` |
| 02/2019 | ŽIVOTNÍ ROK JONÁŠE FOREJTKA | Jonáš Forejtek s pohárem | 44 | [PDF 10.9 MB](https://files.cltk.cz/rfo7dwdpz621/2019_2_web.pdf) | `assets/revue/revue-2019-2.jpg` |
| 01/2019 | DÍKY PARTĚ | čtyři osoby se dvěma poháry za 16. extraligový titul (jména na obálce neuvedena) | 44 | [PDF 12.1 MB](https://files.cltk.cz/rk3mxshusn16/1_2019_web.pdf) | `assets/revue/revue-2019-1.jpg` |
| 02/2018 | KAROLÍNA MUCHOVÁ: STOVKA NA DOHLED | Karolína Muchová | 44 | [PDF 12.5 MB](https://files.cltk.cz/66n4o2cu4r16/2_2018_web.pdf) | `assets/revue/revue-2018-2.jpg` |
| 01/2018 | PETR VANÍČEK: ŘEDITELEM V KLUBU SVÉHO SRDCE | Petr Vaníček | 44 | [PDF 12.4 MB](https://files.cltk.cz/35au2pku6m16/1_2018_web.pdf) | `assets/revue/revue-2018-1.jpg` |
| 02/2017 | ANDREA, HONZA & ROMANA: S DĚTMI NÁS TO BAVÍ | Andrea, Honza & Romana s míčky (dle titulku; role a příjmení NEOVĚŘENO) | 44 | [PDF 10.8 MB](https://files.cltk.cz/pcpcsxeugp16/2_2017_web.pdf) | `assets/revue/revue-2017-2.jpg` |
| 01/2017 | ÚCHVATNÝ COMEBACK MARKÉTY VONDROUŠOVÉ | Markéta Vondroušová s trofejí | 44 | [PDF 12.0 MB](https://files.cltk.cz/ymf3ypbgjk02/1_2017_web.pdf) | `assets/revue/revue-2017-1.jpg` |
| 02/2016 | DENISA HINDOVÁ S BEKHENDEM OD PÁNABOHA | Denisa Hindová | – | chybí | `assets/revue/revue-2016-2.jpg` |
| 01/2016 | Jan Kodeš – 70 (velká číslice „70“ a podpis) | Jan Kodeš (70. narozeniny) | – | chybí | `assets/revue/revue-2016-1.jpg` |
| 02/2015 | ROBIN & HONZA NA CESTĚ VZHŮRU | Robin & Honza ve hře (dle titulku; příjmení NEOVĚŘENO) | 44 | [PDF 19.2 MB](https://files.cltk.cz/si4ee9tbpf05/2_15_web.compressed.pdf) | `assets/revue/revue-2015-2.jpg` |
| 01/2015 | MIRI & MAKI S TITULEM MEZI ŽENY | „Miri & Maki“ s pohárem – Maki = Markéta Vondroušová; Miri = zřejmě Miriam Kolodziejová (přezdívku „Miri Kolodziejová“ používá Bulletin 2023, PDF 2023/1 str. 37–38) | 44 | [PDF 19.6 MB](https://files.cltk.cz/d7ghcdqbdh05/1_15_web.compressed.pdf) | `assets/revue/revue-2015-1.jpg` |
| 02/2014 | SKVOSTNÝ ROK MARKÉTY VONDROUŠOVÉ | Markéta Vondroušová | 44 | [PDF 20.6 MB](https://files.cltk.cz/q5pqa3zbfc05/2_14_web.pdf) | `assets/revue/revue-2014-2.jpg` |
| 01/2014 | JAN HERNYCH & JAN MERTL: JEŠTĚ NEKONČÍME! | Jan Hernych a Jan Mertl | 44 | [PDF 19.4 MB](https://files.cltk.cz/97moit5c5905/1_14_web.compressed.pdf) | `assets/revue/revue-2014-1.jpg` |
| 02/2013 | 120 LET I. ČESKÝ LAWN-TENNIS KLUB PRAHA | historická černobílá fotografie hráče u sítě (identita neuvedena – NEOVĚŘENO) | 44 (22 dvoustran) | [PDF 12.3 MB](https://files.cltk.cz/k2f8xh6h3402/2_13_web.pdf) | `assets/revue/revue-2013-2.jpg` |
| 01/2013 | Jürgen & Iveta: Ve Vídni i v Praze je blaze | Jürgen (Melzer) a Iveta (Benešová) – příjmení NEOVĚŘENO z obálky, uvedena jen křestní jména | 44 (22 dvoustran) | [PDF 47.1 MB](https://files.cltk.cz/n3pyf2ihhw01/1_13_web.pdf) | `assets/revue/revue-2013-1.jpg` |
| 02/2012 | Lucie Hradecká: Medaili mám schovanou | Lucie Hradecká s olympijskou medailí | 44 (22 dvoustran) | [PDF 48.8 MB](https://files.cltk.cz/rz4oqiegwi02/REVUE_2_12_web.pdf) | `assets/revue/revue-2012-2.jpg` |
| 01/2012 | Fed Cup patří i Lucii Hradecké | Lucie Hradecká s fedcupovou trofejí | 44 (22 dvoustran) | [PDF 26.6 MB](https://files.cltk.cz/5e8tjxkhtt01/1_12_web.pdf) | `assets/revue/revue-2012-1.jpg` |
| 02/2011 | Václav Klaus prezidentem I. ČLTK | Václav Klaus | 44 (22 dvoustran) | [PDF 17.2 MB](https://files.cltk.cz/jbozuehg9h02/REVUE_2_11_web.pdf) | `assets/revue/revue-2011-2.jpg` |
| 01/2011 | Jan Kodeš: Patřím na Štvanici | Jan Kodeš | 44 (22 dvoustran) | [PDF 11.9 MB](https://files.cltk.cz/wcms57ngyd02/revue_1_2011.pdf) | `assets/revue/revue-2011-1.jpg` |
| 02/2010 | Šedesátník Jiří Hřebec: Nejvíc teď miluju kolo | Jiří Hřebec | 44 (22 dvoustran) | [PDF 6.3 MB](https://files.cltk.cz/3rmn2e9hf202/2_10_revue.pdf) | `assets/revue/revue-2010-2.jpg` |
| 01/2010 | Konec her, »Plišákům« začíná dřina | sestry Plíškovy („Plišáci“) s plyšovými psy | 44 (22 dvoustran) | [PDF 6.8 MB](https://files.cltk.cz/7nlznsnh7s01/1_10_4_ALL.pdf) | `assets/revue/revue-2010-1.jpg` |
| 02/2009 | Lucie Hradecká: Sladké odměny od sestry | Lucie Hradecká | 48 (24 dvoustran) | [PDF 7.0 MB](https://files.cltk.cz/m3b47achrz01/2_09.pdf) | `assets/revue/revue-2009-2.jpg` |
| 01/2009 | Iveta Benešová: »Kanár«, který potěšil | Iveta Benešová se skleněnou trofejí (titulek „»Kanár«, který potěšil“) | 48 (24 dvoustran) | [PDF 6.1 MB](https://files.cltk.cz/2najb6fh5y01/1_2009.pdf) | `assets/revue/revue-2009-1.jpg` |
| 02/2008 | Nejmladší naděje klubu aneb rodičovské štěstí na Štvanici | maminky s miminky na kurtu („Nejmladší naděje klubu“) | 44 (22 dvoustran) | [PDF 22.9 MB](https://files.cltk.cz/sgmf1bkglf02/revue_2_08.pdf) | `assets/revue/revue-2008-2.jpg` |
| 01/2008 | Pavel Huťka: Tohle víno jsme chtěli | Pavel Huťka s lahví klubového vína (tiráž: „Foto na titulu (Pavel Huťka)“) | 44 (22 dvoustran) | [PDF 25.6 MB](https://files.cltk.cz/lmh8a3qgbc02/revue_1_08.pdf) | `assets/revue/revue-2008-1.jpg` |
| 02/2007 | ECM Prague Open: Totální nasazení | Michaella Krajicek na ECM Prague Open 2007 (tiráž) | 44 (22 dvoustran) | [PDF 24.0 MB](https://files.cltk.cz/xwdmeysgna02/2007_2.pdf) | `assets/revue/revue-2007-2.jpg` |
| 01/2007 | Martin Damm: Poslední splněný sen | Martin Damm po vítězství na US Open 2006 (tiráž) | 44 (22 dvoustran) | [PDF 26.6 MB](https://files.cltk.cz/zxczitwg1902/2007_1.pdf) | `assets/revue/revue-2007-1.jpg` |
| 02/2006 | ECM Prague Open: Na Martinu se stála fronta | Martina Navrátilová při ECM Prague Open 2006 (tiráž) | 44 (22 dvoustran) | [PDF 32.5 MB](https://files.cltk.cz/pxqdnpzgd702/2006_2.pdf) | `assets/revue/revue-2006-2.jpg` |
| 01/2006 | ECM Prague Open: Hernych na Štvanici neprohrává | Jan Hernych s trofejí za vítězství v ECM Prague Open 2005 (tiráž) | 44 (22 dvoustran) | [PDF 21.8 MB](https://files.cltk.cz/d25srl3hp502/2006_1.pdf) | `assets/revue/revue-2006-1.jpg` |

Poznámky k obálkám:
- Kdo je na obálce: uvádím jen to, co říká titulek, tiráž („Foto na titulu (…)“) nebo text čísla. Kde to nejde ověřit, je uvedeno NEOVĚŘENO.
- Obálka 02/2020 na webu se textem liší od obálky v PDF. Web: „Vzpomínka na Petra Štrobla“, PDF: „Petr Štrobl – vzpomínka“.
- Na obálce 02/2023 je doslova „VITĚZKA WIMBLEDONU 2023“. Je to překlep v tisku, ověřeno renderem.
- U 01/2021 jsem ořízl bílý pruh nahoře (36 px). Ostatní obálky ořez nepotřebovaly (kontrola jasu okrajů).

## 4. Rubriky časopisu (co by mohlo tvořit „digitální redakci“ webu)

Podle záhlaví stránek a obsahu čísel 2024–2026:

| Rubrika (záhlaví na stránce) | Co obsahuje | Příklad (číslo, strana) |
|---|---|---|
| **editorial** | úvodník generálního manažera Vladislava Šavrdy s podpisem | každé číslo, s. 1 |
| **Aktuálně** / **Zaznamenali jsme** + **OBSAH** | krátké zprávy a tiráž | 01/2026 s. 2 „Prague Open nebude. Nebude?“ |
| **Z klubu** | investice, areál, provoz, klubové akce | 01/2026 s. 3 „Terasa, která láká“ |
| **Stalo se** | stručné zprávy (tituly, zranění, personálie, parkování) | 02/2025 s. 8 „Parkování opět pod oblouky“ |
| **Osobnost** | velký portrét hvězdy nebo legendy | 02/2025 s. 10 Markéta Vondroušová; 01/2026 s. 28 Jan Kodeš 80 |
| **Interview** / **Rozhovor** | dlouhé rozhovory otázka–odpověď (trenéři, hráči, členové, partneři) | 01/2026 s. 10 Václav Šafránek; s. 38 Jan Kurz |
| **Závodní tenis** | portréty juniorů a dětí, trenéři, babytenis | 01/2026 s. 14 Denisa Žoldáková |
| **Nejlepší výsledky hráčů I.ČLTK od …** | pravidelná výsledková strana po kategoriích: Ženy a muži / Dorostenky a dorostenci / Starší žákyně a žáci / Mladší žákyně a žáci / Babytenis | 01/2026 s. 17 (od října 2025) |
| **Turnaj roku** | Prague Open (ATP Challenger + ITF/W75) | 01/2025 s. 3–5 |
| **Klubový den** | deblový turnaj členů + Hospodský kvíz | 02/2025 s. 18–21 |
| **Partner klubu** / **Partneři klubu** | rozhovor s partnerem nebo sponzorem | 01/2026 s. 20 TUkas; 02/2025 s. 32 ASTON |
| **Návštěva** / **CTC** | Centenary Tennis Clubs, All England Club, zahraniční výjezdy seniorů | 02/2025 s. 34–39 |
| **Společnost** | Vánoční party v Letenském zámečku, Ceny Jaroslava Drobného | 01/2026 s. 36 |
| **Golf** | klubové mistrovství v golfu | 02/2025 s. 44 |
| **Dotýká se nás** – „Kapitoly z historie Štvanice“ | seriál o dějinách ostrova (Stanislav J. Václavovic, přetisk z časopisu Hobulet), závěr v 01/2025 | 02/2024 s. 34; 01/2025 s. 30 |
| **Co možná nevíte** | zajímavosti | 02/2025 s. 42 „Romantická stavba“ (I.ČLTPK v Čerčanech) |
| **Vzpomínáme** | nekrology | 01/2025 s. 40 Jiří Fencl |
| **Advertorial** | placený obsah | 02/2025 s. 46 MessyPlay |

Historické rubriky a seriály ze starších čísel (podle OBSAHů):
- „**Štvanické bitvy**“ (2006–2007): „Titul starý víc než třicet let“, „Kodešův »zázrak«“, „Hřebcova vzpoura“.
- „**Ve vzpomínkách Járy Bečky**“ (2008–2012, 2014): Julius Drobný, manželé Součkovi, Ladislav Žemla, Jaroslav Drobný, Jiří Javorský, klubový žebříček, první trenéři.
- „**Hrají na Štvanici**“ (Václav Klaus, Michal Horáček, Pavel Kuchynka, DJ Lucaso, Jiří Hrdina).
- „**Na slovíčko**“, „**Co nového u…**“, „**Talent**“ (01/2010: Markéta Vondroušová), „**Z archivu**“, „**V.I.P. turnaj**“, „**Bowling**“, „**Kvíz: Znáte historii I. ČLTK?**“ (01/2015, 01/2014, 02/2014).

## 5. Čtyři poslední čísla podrobně

Strany = číslování časopisu (u nových čísel platí: strana PDF = strana časopisu + 2; obálka a vnitřní obálka nejsou číslované). Plné texty: `_raw/revue/txt/revue-<rok>-<číslo>_clean.txt`.

### 01/2026 – „Velký titul pro Karolínu“
Údaje z tiráže (s. 2):
- 44 stran PDF,
- uzávěrka 24. dubna 2026,
- náklad 400 kusů,
- foto na titulu Paul Zimmer, ostatní foto Martin Sidorják, Kamil Rodinger, Jan Pecha a archiv,
- produkce Putt PD s.r.o.,
- tisk Astron.

Obsah čísla:
- **editorial** (Vladislav Šavrda): po 25 letech se neuskuteční 26. ročník Prague Open. Terasa a zeleň jsou hotové. V červenci se na Štvanici hraje WTA 250 (pořádá TK Sparta za podpory I.ČLTK). První klubový den je 24. května s kvízem Jakuba Kvášovského.
- **Aktuálně – „Prague Open nebude. Nebude?“:** nepřišla dotace NSA. Klub musel zaplatit nevratnou garanci 100 tisíc korun a do 15. dubna odeslat prize-money 75 tisíc eur.
- **Z klubu – „Terasa, která láká“ (s. 3–5):**
  - terasa restaurace Tiebreak podle ateliéru Adama Fröhlicha, **60 míst**,
  - welcome board u vstupu, nová zeleň (bobkovišně místo tújí u bazénu),
  - „nová Štvanice“ (velký a malý centr) stojí **přesně 40 let**, otevřel ji Pohár federace 1986,
  - ve hře je lehká kondiční hala za kurty 2, 3, 4 (dřívější odhad 10 milionů korun),
  - za parkování pod oblouky a u malého centru klub platí **400 tisíc korun ročně**.
- **Stalo se (s. 6–8):**
  - WTA 250 se na ostrov vrací poprvé od roku 2009 (týden od 18. července),
  - od června bude na Štvanici ordinace Body Solution Clinic,
  - **Karolína Muchová vyhrála WTA 1000 v Dauhá**: ve finále porazila Mbokovou 6:4, 7:5 a posunula se na 11. místo; pak hrála finále ve Stuttgartu,
  - **Tereza Martincová vyhrála Conseq Prague Open ITF W75** z kvalifikace jako 413. hráčka světa, což je „největší turnajové vítězství v kariéře“,
  - Markéta Vondroušová má spor s ITIA kvůli odmítnuté dopingové kontrole.
- **Interview: Václav Šafránek (32)**, kouč Karolíny Muchové, „Vidíme to jinak“ (s. 10–13).
- **Závodní tenis:**
  - Denisa Žoldáková (18), „Servis musí být zbraň“ (s. 14),
  - Nikol Tesárková (10), „Niki jako dárek“ (s. 16),
  - babytenisté Viktor Zhuk a Jakub Kožuský, „Rybář vs. šachista“ (s. 18),
  - Petr Vaníček a Nikola Bartůňková, „Nikdo není boss“ (s. 22).
- **Výsledky:** „Nejlepší výsledky hráčů I.ČLTK od října 2025“ (s. 17).
- **Partner klubu:** TUkas, 35 let na trhu, Michal Zaorálek (s. 20).
- **Interview:** MUDr. Karolína Velebová, Body Solution Clinic (s. 24–26).
- **Osobnost:** 80. narozeniny **Jana Kodeše**, fotoreportáž Martina Sidorjáka (s. 28).
- **Z klubu:** 2. Večer talentů (s. 30).
- **20 let Revue:** přehlídka 40 obálek (s. 32–35).
- **Společnost:** Vánoční party, Ceny Jaroslava Drobného za rok 2025 (s. 36).
- **Nadační fond Markéty Vondroušové:** 300 tisíc korun pro motolskou onkologii (s. 37).
- **Rozhovor: Jan Kurz**, bývalý kouč Heleny Sukové, „Příjemný náraz“ (s. 38–40).

### 02/2025 – „Nikola Bartůňková – Z pekla do ráje“
Údaje z tiráže:
- 52 stran,
- uzávěrka 4. listopadu 2025,
- náklad 400 kusů,
- foto na titulu Martin Sidorják.

Obsah čísla:
- **editorial:**
  - nový hard na „devítce“, nový agregát trojhaly, osvětlení cesty, funkční zóna,
  - „dvě grandslamové čtvrtfinalistky (na US Open)“: Vondroušová a Muchová,
  - tři mezinárodní turnaje: ITF žen v hale v únoru, 25. ročník ATP Challengeru a ITF žen v květnu, juniorský ITF v srpnu,
  - návštěvy z AELTC a IC, CTC seniorů.
- **Aktuálně:** zemřel Ing. arch. Vladimír Lacina (79), autor nástavby na ochozu malého centru.
- **Z klubu – „Na řadě je terasa“:**
  - kurt 9 má nový modrý hard Novasport,
  - funkční zóna vznikla místo golfového simulátoru,
  - trojhala má novou strojovnu.
- **Stalo se:**
  - parkování je opět pod oblouky Negrelliho viaduktu (4 oblouky, 5 míst pod každým),
  - novou sekretářkou je Eva Štefková,
  - dorostenci skončili **2. na M ČR družstev**,
  - trenér Zdeněk Kubík se vrátil.
- **Osobnost:** Markéta Vondroušová: titul WTA 500 v Berlíně a čtvrtfinále US Open, ale opět zranění.
- **Karolína Muchová 2025:** „Zase boj se zdravím“.
- **Rozhovor:** Nikola Bartůňková se vrátila po „dopingové pseudokauze“ (kontaminovaný doplněk stravy), hrála semifinále WTA 500 v Guadalajaře a je 132. na světě.
- **Klubový den:** rekordních **26 deblových párů**, Hospodský kvíz s „Lovcem“ Jakubem Kvášovským.
- **Závodní tenis:**
  - Radek Chodora, vítěz Pardubické juniorky,
  - Milan Trněný o Filipu Ladmanovi,
  - „Babytenisová sklizeň“: Valerie Křivanová mistryní ČR, Festival oranžové úrovně.
- **Výsledky:** „Nejlepší výsledky od května 2025“.
- **Partner:** Jiří Smrž (ASTON).
- **Návštěva:** **All England Lawn Tennis Club na Štvanici** po 13 letech, domácí získali 2 body z 8 zápasů. Promítal se film ke 130. výročí klubu. Příští rok přijede SALK Stockholm.
- **CTC:** finále na Štvanici vyhrála Padova (I.ČLTK – Padova 4:5, I.ČLTK – Cumberland 6:3).
- **Rozhovor:** Vlastislav Bříza.
- **Co možná nevíte:** I. Čerčanský lawn tenisový a plavecký klub (I.ČLTPK) Františka Stejskala.
- **Golf:** mistrem je František Stejskal jun. (70 ran), Petr Vojtíšek zahrál hole-in-one.
- **Advertorial:** MessyPlay.
- **CTC supersenioři:** vyhráli v TC Lido (Benátky).

### 01/2025 – „Tradice zůstává, kulisy se mění“
Údaje z tiráže:
- 44 stran,
- uzávěrka 12. května 2025,
- náklad 400 kusů.

Na obálce je vizualizace nového vstupu s welcome boardem.

Obsah čísla:
- **editorial:** 25. ročník ATP Challenger Tour 75 + W75 jako „ostrý start do 132. sezony“, přehled investic.
- **Zaznamenali jsme:** návštěva AELTC 6.–8. června; úmrtí Cyrila Suka (82).
- **Turnaj roku – Prague Open 2025:**
  - domácí vyhráli deblové tituly: **Denisa Hindová a Matěj Vocel**,
  - singly vyhráli Filip Misolic a Jonesová,
  - ATP chce odměnit pořadatele, protože se Prague Open hraje „**nepřetržitě už 35 let**“: nejprve deset let pod taktovkou Jana Kodeše, poté 25 let díky Damm Sport Agency, respektive I.ČLTK,
  - novinka: kamerový systém Bolt6.
- **Stalo se:** Winter Prague Open ITF W75; zemřel **Jiří Fencl** (54).
- **Osobnost:** Vondroušová, „Nekonečné čekání“.
- **Z klubu – „Těšte se na terasu“:**
  - vestibul s **wimbledonskou fototapetou**, odpočívárna, kuchyňka,
  - nábytek na terasu „skoro za půl milionu“,
  - areál na Slavoji.
- **Závodní tenis:**
  - Jakub Nicod,
  - Gabriela Šulcová (halová mistryně starších žákyň),
  - „3+3 v Masters“: 32. babytenisová série, první vyhrál v letech 2008–2009 Tomáš Macháč,
  - Valerie Křivanová.
- **Výsledky:** od podzimu 2024.
- **Partneři:** Jan Rybín (ISO).
- **Večer talentů (premiéra):** hráčkou roku je Kateřina Kubíková.
- **Interview:** kondiční trenér Richard Pavluv.
- **„Kapitoly z historie Štvanice“ (závěr seriálu):**
  - 1901 si I.ČLTK pronajal pozemky na ostrově,
  - zimní stadion 1930–1932, MS v hokeji 1933, 1938, 1947, 1959, stadion zbořen 2011,
  - Bio Benátky s 1 400 místy.
- **Rozhovor:** **Adam Fröhlich**, architekt koncepce areálu.
- **Vánoční party:** hráč roku 2024 Jonáš Forejtek, hráčka roku Karolína Muchová (chyběla).
- **Vzpomínáme:** Jiří Fencl, „Pan Dokonalý“.

### 02/2024 – „Výjimečná Karolína“
Údaje z tiráže:
- 44 stran,
- uzávěrka 30. října 2024,
- náklad 400 kusů,
- produkce poprvé Putt PD s.r.o.

Obsah čísla:
- **editorial:** nové vedení ČTS, Šavrda je v Dozorčí radě ČTS; nové hardy.
- **Aktuálně:**
  - „O kurty nepřijdeme!“,
  - **dva zápasy s AELTC**: Praha 6.–8. 6. 2025, odveta 2027 na trávě ve Wimbledonu,
  - nový režim wellness.
- **Z klubu – „Paráda v bublině“:**
  - kurty 7 a 8 mají nový tvrdý povrch a novou nafukovací halu za **více než 14 milionů korun** (dotace NSA na čtvrtý pokus, 30 % spoluúčast klubu),
  - trojhala má nový povrch MIBOsport za 5,5 milionu (4 miliony dotace magistrátu).
- **„Rozsvícený foyer“:** vestibul, kuchyňka.
- **Rozhovor:** Lucie Michálková, design interiérů a klubový merch.
- **Stalo se:**
  - Muchová: finále v Palermu, **semifinále US Open**, finále v Pekingu,
  - Vondroušová a Martincová po operacích,
  - Prague Open 2024 vyhráli **Jiří Veselý a Dominika Šalková**.
- **„Výjimečnost Karolíny Muchové“:** komentář Vladislava Šavrdy a výsledky jejích kol na US Open.
- **Návrat trenéra:** Antonín Štěpánek.
- **Výbor klubu a trenéři klubu** (portrétní tabla, s. 18–19):
  - výbor: Ing. Petr Šimůnek (prezident), Vladislav Šavrda (generální manažer), JUDr. Tomáš Havel, JUDr. Zdeněk Krampera, Mgr. Václav Kučera, Ing. Dušan Palcr, Mgr. Jan Pecha, Ph.D., Mgr. Martin Šustr, Petr Vaníček,
  - trenéři: Petr Vaníček (sportovní ředitel), Ivo Minář, Milan Trněný, Daniel Vaněk, Jan Vacek, Jiří Hřebec, Richard Pavlův (kondice), Jaroslav Jandus, Antonín Štěpánek, Lubomír Štych, Magdalena Zemanová, Pavel Janda (kondice), Jan Pecha, Andrea Vašíčková, Adéla Vašíčková, Hana Césarová.
- **Závodní tenis:**
  - Júki Hálek a výsledky od jara 2024,
  - babytenis a kurty Na Hajnovce,
  - letní kempy s Markétou Vondroušovou („Maky obíhačku nevyhrála“),
  - studie BMI (Adéla Vašíčková, trenérka „TŠ Markéty Vondroušové“).
- **CTC:** Padova a Barcelona.
- **B-tým:** 7 zápasů, 7 výher, postup do Pražské divize.
- **Kapitoly z historie Štvanice:** zdymadlo, elektrárna 1913, Hlávkův most.
- **Interview:** Camilla Mraz.
- **Golf:** mistrem je Martin Šustr (77 ran).

## 6. Skrytý poklad: Bulletin I.ČLTK Praha 1893–2023 (v PDF 01/2023, str. 29–72)

Tiráž PDF str. 70: *„Bulletin I.ČLTK Praha ke 130. výročí klubu. Produkce: EagleMedia, s.r.o. Texty: Jiljí Kubec … Náklad 300 výtisků. Tisk: Astron. Vyšlo v dubnu 2023.“*
Na obálce (PDF str. 29) je Markéta Vondroušová s nápisem „STŘÍBRNÁ MEDAILE, OH TOKIO 2021“.
Obsah bulletinu (nadpisy):
- editorial prezidenta Petra Šimůnka (datován „Na Štvanici, 31. března 2023“),
- „Moderna s geniem loci“,
- rozhovor s Markétou Vondroušovou „Neměnila bych“,
- „Kapitoly ze slavných dějin I.ČLTK Praha – Oáza na ostrově“, „Dvojí stěhování“, „Klubovna díky mecenášům“ (klubovna z roku 1929), „Éra Jaroslava Drobného“,
- seznamy **Zakládající členové**, **Prezidenti I.ČLTK Praha od roku 1893**, **Čestní členové** a **Zasloužilí členové**,
- „Legenda o legendě“ (Kodeš o Drobném),
- „Zázemí nové dimenze“ (nástavba malého centru, arch. Vladimír Lacina),
- „Klíčem bylo rozdělení“ (ČTS a Ivo Kaderka),
- „**Ti, kteří šířili slávu**“: medailony hráčů od Ladislava Žemly po Karolínu Muchovou,
- **Davisův pohár, olympijské medaile, grandslamové tituly, juniorské tituly, Fed Cup, ligové tituly**,
- „**Legendy na Štvanici**“: jmenná koláž hvězd, které na Štvanici hrály,
- „„Čemp“ patří na Štvanici“ (Jan Kodeš).

Tento bulletin je nejbohatší souhrnný zdroj historie. Doporučuji ho předat agentovi, který dělá historii klubu. Pozor na nesrovnalosti mezi zdroji:
- Editorial bulletinu (PDF str. 31) uvádí, že na Štvanici hrálo „víc než šedesát tenistů, kteří se během kariéry stali světovými jedničkami nebo vyhráli grandslam“. Kapitola „Legendy na Štvanici“ (PDF str. 64) ale uvádí „59 grandslamových vítězů ve dvouhře či světových jedniček“.
- Editorial bulletinu (str. 31) píše o založení „v roce 1893 na Židovském ostrově“, kapitola „Dvojí stěhování“ (str. 40) o stěhování „ze Střeleckého ostrova“. Obojí je nutné ověřit (NEOVĚŘENO).
- Editorial Revue 01/2023 (PDF str. 3) uvádí 16 titulů mistra republiky a 12 titulů pod názvem Spartak Praha Motorlet. Bulletin (str. 44) píše o jedenácti titulech „v době komunismu“ a na str. 63 o tom, že „od roku 1951 … nejvyšší soutěž celkem šestnáctkrát“.

## 7. Postavy napříč archivem (obálky + OBSAHy)

Počítáno z titulků obálek a OBSAHů v `revue.json`:
- **Markéta Vondroušová:**
  - 01/2010 (rubrika „Talent“, jako dítě),
  - 01/2012 („Pět výher za dva dny!“),
  - obálky 02/2014 „Skvostný rok“, 01/2015 „Miri & Maki“, 01/2017 „Úchvatný comeback“,
  - 02/2021 (olympijské stříbro), 02/2023 (vítězka Wimbledonu 2023), 01/2024 (Tenisová škola Markéty Vondroušové),
  - dále 01/2019, 01/2025, 02/2025.
  - → Od „talentu“ v 01/2010 k wimbledonské obálce 02/2023 vede celý příběh v archivu.
- **Karolína Muchová:** 02/2018 „Stovka na dohled“, 01/2021, 02/2021, 02/2023, 02/2024 „Výjimečná Karolína“, 02/2025, 01/2026 „Velký titul pro Karolínu“.
- **Lucie Hradecká:** 02/2009, 02/2010, 01/2012 (Fed Cup), 02/2012 (olympijská medaile), 01/2014, 01/2018, 01/2023 „Bylo to úžasné“ (konec kariéry).
- **Jan Kodeš:** 02/2006 („Kodešův zázrak“), 02/2007, 01/2011 (obálka „Patřím na Štvanici“), 01/2016 (obálka „70“), 01/2026 (80. narozeniny).
- **Jan Hernych:** obálka 01/2006 (vítěz ECM Prague Open 2005), dále 01/2014 (obálka s Janem Mertlem), 02/2018 a další.
- **Nikola Bartůňková:** 02/2019, 01/2020 (obálka), 02/2023, 02/2025 (obálka).
- **Václav Klaus:** 01/2006 („Hrají na Štvanici“), 02/2007 a 02/2009 (vítěz V.I.P. turnaje), 02/2011 obálka „Václav Klaus prezidentem I. ČLTK“.
- **Jára (Jaromír) Bečka:** 9 čísel (seriál vzpomínek 2008–2012, rozhovor 02/2006).

## 8. Vizuální jazyk časopisu (pozorováno na obálkách a stránkách)

- **Hlavička obálky je 20 let stejná:** zlatá/olivově zlatá plocha „I.ČLTK“, uprostřed klubový erb (trikolóra + monogram) a tmavě modrá (navy) plocha „REVUE“. Hotová značka „zlatá + navy na bílé“ se hodí k světlému luxusnímu webu.
- **Obálky** tvoří velká portrétní fotka přes celou stranu (hráčka s trofejí) a titulky v bílé verzálce. Kombinují tenký a tučný řez („Velký titul / pro Karolínu“). Vlevo dole je číslo „01/2026“ a „www.cltk.cz“.
- **Uvnitř** (renderováno 01/2026, PDF str. 3–4, 12–13, 34–36):
  - bílé stránky, zlatý štítek rubriky vlevo nahoře („INTERVIEW“, „AKTUÁLNĚ“, „Z KLUBU“),
  - tenké verzálkové titulky se zlatým tučným slovem („VIDÍME TO **JINAK**“, „REVUE NÁS BAVÍ. A VÁS?“),
  - velké obří pull-quotes, zlatá čísla stran v OBSAHu s kruhovými fotkami,
  - svislý nápis „editorial“ a vodoznaková číslice („20“) na pozadí.
- Hlavní fotograf **Martin Sidorják** je zdrojem většiny kvalitních fotek v archivu.

## 9. Nápady pro web (co z archivu může být „něco, co nikdo nemá“)

1. **„Revue“ jako digitální magazín klubu:** stejné rubriky (Osobnost, Interview, Z klubu, Závodní tenis, Dotýká se nás, Společnost, Partner klubu), editorial s podpisem, pull-quotes a zlaté štítky rubrik přenesené 1:1 z tištěné grafiky.
2. **Zeď 41 obálek / časová osa 2006–2026:** hover nebo klik ukáže titulky obálky, obsah a odkaz na PDF (`revue.json` má vše). Klub sám tento motiv použil v 01/2026 („Titulních stran bylo už čtyřicet“).
3. **„Příběh šampionky v obálkách“:** Markéta Vondroušová od „Talentu“ 01/2010 po Wimbledon 02/2023. Podobně Muchová 2018 → 2026 a Kodeš 2006 → 2026.
4. **Fulltextové hledání v 20 letech Revue:** texty jsou vybratelné. Hledání podle osoby by vedlo na konkrétní číslo a stranu.
5. **Výsledková strana „Nejlepší výsledky hráčů I.ČLTK od …“** jako strukturovaný feed po kategoriích, s napojením na profily hráčů.
6. **Historie z Bulletinu 1893–2023 a seriálu „Kapitoly z historie Štvanice“:** mapa ostrova v čase (1901 kurty, 1929 klubovna, 1930–32 zimní stadion, Bio Benátky, porodnice, 1986 nový centr, 2011 zbořen zimák), seznam prezidentů od 1893 a „Legendy na Štvanici“.
7. **Listovací náhled čísla** (flipbook z PDF) a **„Číslo vyšlo“ blok na homepage**. Homepage už teď má blok `c-revue`.

## 10. Mezery a NEOVĚŘENO

- **PDF 01/2016 a 02/2016 chybí** (na webu prázdný odkaz, nenalezeno ani ve Wayback CDX). Od těchto čísel není obsah ani počet stran.
- 01/2023: v části Revue chybí strana OBSAH i tiráž (náklad a uzávěrka Revue 01/2023 neznámé). Obsah jsem odvodil z nadpisů.
- U několika obálek nejde z obálky ověřit, kdo je na fotce: 02/2013 (historická fotka), 01/2022, 02/2015 (příjmení „Honzy“), 02/2017 (Andrea, Honza & Romana), 01/2019. Označeno v JSON.
- Uzávěrky z tiráží mají jen čísla 2020/2 a novější (a 01/2021). Starší tiráže je neuvádějí nebo je regex nezachytil. Vyšlo vždy 2× ročně (jaro/podzim), přesná data starších čísel jsem neověřoval.
- Počet stran u dvoustranových PDF (2006–2013) je spočten jako listy × 2 za předpokladu, že 1. list je zadní + přední obálka. Ověřeno u 01/2007 renderem, u ostatních NEOVĚŘENO vizuálně (sedí však s OBSAHy do s. 40–44).
- Newslettery (`newsletter 2016-01` … `2019-01`) a `PO_bulletin_2020_web.pdf` na files.cltk.cz jsem nezpracoval, nejsou součástí archivu Revue.

## 11. Soubory

- `podklady/data/revue.json`: 41 záznamů. Pole:
  - `rok`, `cislo`, `nazev`, `oznaceni_na_webu`,
  - `pdf_url`, `cover_file`, `cover_url_web`, `cover_zdroj`,
  - `pocet_stran`, `pdf_stran`, `pdf_format`, `pdf_mb`,
  - `naklad`, `uzaverka`, `titulni_strana`, `na_obalce`, `obsah`, `temata` (jen 4 poslední čísla), `poznamka`.
- `assets/revue/revue-<rok>-<cislo>.jpg`: 41 obálek, JPG, výška 900 px, kvalita 85, sRGB.
- `podklady/_raw/revue/`:
  - `archiv.html` (stažená stránka),
  - `pdf/` (39 PDF),
  - `txt/` (plné texty po stranách + `_clean` verze),
  - `toc.json`, `items.json`, `cover_texts.txt`,
  - `covers_src/` (originály obálek z webu).
