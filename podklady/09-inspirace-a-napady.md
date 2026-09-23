# 09 – Inspirace a nápady: co dělají nejprestižnější kluby světa a čím může být nový cltk.cz „něco, co nikdo nemá“

Rešerše pro redesign www.cltk.cz, stav k 23. 9. 2026. Jde o **průzkum, ne o návrh**. Fakta o klubech a webech pocházejí ze stránek, které jsem skutečně otevřel. U každého bodu je zdroj. Co jsem neověřil, je označeno **NEOVĚŘENO**. Jako nápady jsou výslovně označené **moje návrhy** (barvy, rozdělení do variant).

**Metoda.** Každý web jsem otevřel v headless prohlížeči (Playwright; blokované weby přes Microsoft Edge) v okně 1440 × 900. Pořídil jsem až 8 snímků obrazovky při postupném rolování a z DOM jsem vytáhl skutečně načtená písma, vypočtené styly nadpisů, barvu pozadí a texty nadpisů.
- Snímky: `podklady/_raw/inspirace/web/<slug>-hero.png`, `-s1…s7.png`
- Náhledové montáže: `podklady/_raw/inspirace/montaze/<slug>.jpg`
- Data (písma, barvy, texty): `podklady/_raw/inspirace/web/<slug>.json`

Písma jsem ověřil ve třech krocích: metadata Google Fonts, pak kontrola glyfů v TTF (fontTools) a nakonec vizuální render české ukázky (`podklady/_raw/inspirace/fonty/`).

---

## 0. Hlavní zjištění (TL;DR)

1. **Nejexkluzivnější kluby mají veřejné weby překvapivě skromné.**
   - The Queen's Club i Longwood běží na šabloně „Powered by Jonas Club Software“ (patička obou webů).
   - Hurlingham má na úvodní stránce jen pět dlaždic s tlačítkem VIEW.
   - Prestiž tu vyjadřuje zdrženlivost, ne funkce. Laťku digitálního zážitku nastavují **turnaje** (Wimbledon, Roland-Garros) a **luxusní značky** (Aman, Rolex, Hermès), ne kluby.
   - Závěr: web klubu na úrovni muzea a magazínu zatím nemá nikdo.
2. **Historii kluby zpracovávají obecnými nástroji.**
   - RCTB Barcelona má chronologii v obecné časové ose TimelineJS.
   - Rot-Weiss má svislou červenou osu s dlouhými odstavci.
   - Monte-Carlo má dlouhý text a galerii černobílých fotek.
   - Parioli nabízí „Albo d'oro“ a seznam prezidentů jen jako PDF ke stažení.
   - Rot-Weiss má archiv klubového magazínu jako mřížku obálek s tlačítkem „ePaper lesen“.
   - **Nikdo nepropojuje historii, lidi, archiv a současnost do jednoho příběhu.**
3. **„Decentní luxus“ má na webu opakovatelné znaky.** Vesměs jsem je naměřil:
   - teplé téměř bílé pozadí: Aman `#F3EEE7`, Hermès `#FCF7F1`, Soho House `#FFFEF7`, Annabel's `#F3F1ED`,
   - jedno serifové písmo + jedno tiché bezserifové,
   - drobné verzálkové popisky s prostrkáním,
   - místo tlačítek podtržené textové odkazy („Discover more“ u Amanu),
   - básnické mikrotexty (Hermès „FOLLOW PEGASUS“, „SET THE DRAPE FREE“),
   - pauza u videa (Soho House), přepínač „Reduce motion“ a „Contrast active“ v patičce (Rolex).
4. **Standard v ČR** je tmavě modrá nebo fialová a sportovní akcenty (červená, žlutá), verzálky v Roboto, Helvetica nebo Barlow Condensed a jako hlavní prvek dlaždice s ikonami (Rezervace, Ceník…). Dál:
   - výsledkové widgety, velké stěny log partnerů, šablony Divi/WordPress,
   - TK Agrofert Prostějov má stále layout z doby tabulek.
   - Redakční nebo muzejní přístup **nemá ze zkoumaných českých webů nikdo**.
5. **Konkurence začíná hrát na „Wimbledon“.**
   - tkprerov.cz má v hero sekci Lindu Noskovou s wimbledonskou mísou a text „Trénuj v klubu, kde se připravuje vítězka Wimbledonu Linda Nosková!“.
   - wimbledon.com k tomu 11. 7. 2026 publikoval článek „'Just me, myself and I': Noskova reveals winning mindset“.
   - Unikát I. ČLTK **„klub tří wimbledonských vítězů“** (Drobný 1954, Kodeš 1973, Vondroušová 2023; zdroj [R23/2 s. 19] v `06-historie-kronika.md`) proto musí být vidět hned a ostře.
6. **Zajímavá paralela.** Předchůdce Monte-Carlo Country Clubu, „Lawn Tennis de Monte-Carlo“, byl otevřen **2. dubna 1893**, tedy ve stejném roce, kdy vznikl I. ČLTK (https://www.mccc.mc/en/history-57). Hodí se to pro prvek „mezi vrstevníky“ (nápad 16).
7. **Příležitost pro I. ČLTK.** Klub má obsah, který nemají ani světové kluby: 41 čísel Revue od roku 2006, ostrov s historií od štvanic po povodně, čtyři názvy klubu, 16 titulů, „víc než šedesát“ grandslamových vítězů, kteří na Štvanici hráli, a jediné české členství v CTC. Spojením **redakčního magazínu, muzejní vitríny a živého klubového provozu** vznikne web, který opravdu nikdo nemá.

---

## 1. Reference – konkrétní pozorování

### 1.1 Wimbledon / All England Lawn Tennis Club
URL: https://www.wimbledon.com/, https://www.wimbledon.com/en_GB/museum_and_tours/index.html, https://www.wimbledon.com/en_GB/gallery/the_champions_of_wimbledon. Snímky: `montaze/wimbledon-home.jpg`, `wimbledon-museum.jpg`.
- **Hierarchie nadpisů „eyebrow + titulek“.** Nad každou sekcí je malý fialový serifový nadtitulek („THE CHAMPIONSHIPS“, „NEWS AND INFORMATION“, „VISIT AND TOURS“). Pod ním je verzálkový titulek v Gothamu („EDITOR'S PICKS“, „LATEST“). Načtená písma jsou Gotham a ACaslonPro, zelená nadpisů je `rgb(0,85,43)`.
- **Karty přes fotografii.** Bílý obdélník uprostřed plné fotky nese nadtitulek, titulek, dvě věty a odkaz („Play your way to Wimbledon“, „The Wimbledon Foundation“, „Wimbledon Lawn Tennis Museum“). V pravém dolním rohu hera sedí sponzorský widget s hodinami Rolex („London time / your time“).
- **Muzeum jako zážitek.** Text sekce The Museum zve „sit on the bench used by Roger Federer in the Gentlemen's Dressing Room“ a „Flick through the pages of Arthur Ashes' diary“. Probíhá výstava „A SLICE OF HISTORY“ (listopad 2025 – podzim 2026) s popisem „An interactive journey through the rich culinary traditions of The Championships“. Ceník je vysázený jako prostý centrovaný text (Museum & Tour Adult £32, Museum only Adult £20).
- Stránka aeltc.com se v headless prohlížeči vykreslila prázdná, klubová část AELTC je tedy **NEOVĚŘENO**. Veřejný web je prakticky jen turnajový.

### 1.2 The Queen's Club (Londýn)
URL: https://www.queensclub.co.uk/, https://www.queensclub.co.uk/About_the_Club. Snímky: `montaze/queens.jpg`, `queens-about.jpg`.
- Šablonový web „Powered by Jonas Club Software“ s úzkým tmavě modrým pruhem nahoře (telefon, „Member Login“). V heru je fotka cihlového klubovního domu s poloprůhledným rámečkem „Welcome to The Queen's Club“. Nadpisy jsou v **EB Garamond** (48 px, `#1A3150`), text v Open Sans.
- Text o klubu: „Established in 1886, The Queen's Club was the first multipurpose sports complex ever to be built, anywhere in the world.“ Klubovna podle webu obsahuje „an elegant restaurant, bar, **museum**, and The President's Room“. Muzeum ale na webu vidět není.
- Úvodní stránka je jen seznam novinek se stránkováním, mapa Google, kontaktní formulář a tři šedé dlaždice. **Prestiž tu nese jen písmo a znak.**

### 1.3 The Hurlingham Club (Londýn)
URL: https://www.hurlinghamclub.org.uk/, …/about-hurlingham. Snímky: `montaze/hurlingham.jpg`, `hurlingham-about.jpg`.
- Bílá stránka s fotkou georgiánské klubovny na trávníku a nadpisem „WELCOME TO **THE HURLINGHAM CLUB**“. Nadpis je v Gill Sans (38 px, prostrkání 5,77 px), zvýrazněná je jen tučná část. Tyrkysová tlačítka VIEW.
- Text: „Situated in 42 acres of landscaped grounds adjacent to the River Thames… Since its opening in 1869… regarded as the birthplace of polo“. **Řeka a zahrada jsou hlavní obraz**, což je paralela ke Štvanici ve Vltavě.
- Web je minimalistický až strohý: 5 dlaždic (About, Events, Sports Hire, Local Road Restrictions, Contact) a kruhová pečeť klubu nad patičkou.

### 1.4 Roland-Garros / FFT
URL: https://www.rolandgarros.com/en-us/, https://www.rolandgarros.com/en-us/3D-art-museum-infosys, https://www.fft.fr/. Snímky: `montaze/rolandgarros.jpg`, `rg-3dmuseum.jpg`.
- **„Stories“ jako na Instagramu.** Řada svislých karet 9:16 pod herem, vedle nich „News feed“ jako seznam s daty.
- **Infosys 3D Art Museum.** Tři „místnosti“ vystavené jako zarámované obrazy na betonové zdi s tlačítkem „Visit this Museum“: „Yannick Noah – Special“, „Racquets & Posters“, „Iconic Symbols“. Je to digitální muzeum turnaje, ale 3D prohlídka je technicky těžká.
- Barvy jsou antukově oranžová a tmavě zelená. Vlastní písma (rg_text, rg_titlebold) a Google Sans Flex. Vlevo je trvale připnutý widget přístupnosti a vpravo nahoře opět hodiny Rolex.
- FFT (fft.fr) je světlá (pozadí `#F7F4EE`) a používá Roboto Flex. Obsahuje sekci „La FFT en chiffres“ (čísla) a vyhledávač klubů „Jouez près de chez vous“.

### 1.5 Monte-Carlo Country Club
URL: https://www.mccc.mc/en/index, https://www.mccc.mc/en/history-57. Snímky: `montaze/mccc.jpg`, `mccc-history.jpg`.
- Vlevo je **trvalý svislý pruh** se svislým logotypem „Monte-Carlo Country Club“ a malou pečetí. Nadpisy jsou v **Ivy Mode** (62 px, váha 350, navy `#14173A`), text v DM Sans.
- **Hero slider s obloukovou čarou.** Tenká zakřivená linka s tečkami spojuje popisky „Tennis · Relaxation · Pro-Shop · Restaurant · Sport“ a působí jako navigační oblouk. Na úvodní stránce jsou „President's welcoming words“.
- **Stránka History.** Dlouhý souvislý text s citátem uprostřed („Her Star status deserves a Jewel not only a simple roof on top of a garage“ – G. Butler, 1925). Pod ním je dlouhá mozaika černobílých archivních fotek se zaoblenými rohy: stavba teras, Art Deco bar, hráči.
- Fakta z webu:
  - 2. 4. 1893 byl otevřen „Lawn Tennis de Monte-Carlo“, první tenisový klub Monaka,
  - areál s terasami navrhl architekt Letrosne „in an entirely new Art Déco style“, s 20 kurty (12 pro mezinárodní soutěže, 8 pro rekreaci) a s „more than 1500 labourers“,
  - otevřen byl v únoru 1928.
- Rušivé: cookie okno přes celou stránku, které se vrací při každém rolování (viz snímek před kliknutím).

### 1.6 Real Club de Tenis Barcelona-1899
URL: https://www.rctb1899.es/es, https://www.rctb1899.es/es/el-club/historia/cronologia. Snímky: `montaze/rctb.jpg`, `rctb-cronologia.jpg`.
- **„Cronología“ je vložená TimelineJS** (Knight Lab). Nahoře je zoomovatelná osa 1870–1930 s náhledy, pod ní snímek s rokem a textem („1899 · Mabel Parsons“, „Rosa Torras, la primera olímpica del RCTB-1899“, „Salvador Dalí y Kim Novak en la Verbena de San Juan del RCTB“, „Nadal se cuelga la medalla de oro en Beijing“…). Obsahově skvělé, vizuálně generické.
- **Užitečné widgety pro členy.** „Temperatura de la piscina **28°C**“ (teplota bazénu), předpověď počasí z meteo.cat, kalendář „Agenda“ s tlačítkem „Descarga el calendario anual de actividades“.
- Zlaté tlačítko „El Godó del Socio/a“ v menu (turnajová nabídka pro členy), zlatý horní pruh, Montserrat. Tři řady log partnerů přes celou šířku.

### 1.7 LTTC „Rot-Weiß“ Berlin
URL: https://www.rot-weiss-berlin.de/, …/lttc-rot-weiss/chronik-des-clubs, …/lttc-rot-weiss/club-publikationen. Snímky: `montaze/rotweiss*.jpg`.
- Hero video s detailem výpletu a míčku a logem „LTTC ROT-WEISS · WIR LEBEN TENNIS.“ Titillium Web, červená `#E30613`. V kódu jsou knihovny GSAP a Lenis (podle výpisu načtených skriptů), ale web působí jako běžná korporátní šablona.
- **„Chronik des Clubs“ je svislá červená osa** se štítky roků (1897, 1935, 1948, 2004, 2009, 2019) a šedými boxy s dlouhým textem. Pěkná historka: název „Rot-Weiß“ vznikl, protože členové nosili „rot-weiße Bänder um ihre Strohhüte“.
- **„Club-Publikationen“.** Mřížka obálek Club-Magazinu 2012–2025 a programových sešitů juniorského turnaje 2009–2019, každá s červeným tlačítkem „ePaper lesen“. **Je to nejbližší obdoba archivu I.ČLTK Revue**, ale bez hledání, rubrik a příběhů.

### 1.8 Kitzbüheler Tennisclub (KTC)
URL: https://www.ktc.at/ (původní adresa /de/tennisclub-kitzbuehel.html vrací 404). Snímky: `montaze/ktc-home.jpg`.
- Hero s fotkou skupiny dětí na antuce, velká verzálka v Poppins „KITZBÜHELER TENNISCLUB“ (70 px) a heslo „Sport – Nachwuchsförderung – Geselligkeit“.
- Stálé CTA „Platzreservierung – Sichern Sie sich jetzt Ihren Tennisplatz“ v červeném boxu se šipkou a tlačítko „Generali Open Tickets“ v hlavičce.
- Text „Über uns“ a „Unser Ziel“, fotopás, sponzoři. Jednoduchý, čistý a regionální web bez práce s historií.

### 1.9 Tennis Club Parioli (Řím)
URL: https://tcparioli.it/, https://tcparioli.it/la-storia-del-tennis-club-parioli/. Snímky: `montaze/parioli.jpg`, `parioli-storia.jpg`.
- Barvy lahvově zelená `#1F382E` a zlatá `#BC8D0A`. Nadpisy v **Adobe Garamond** (Typekit), verzálkové menu se širokým prostrkáním, uprostřed hlavičky štít s vlčicí a letopočtem 1906. Letecká fotka areálu ve Villa Ada.
- Historie je krátký text: založení 1906 jako „Lawn Tennis Club Parioli“, přesun do Villa Ada v roce 1960. Seznam slavných hráčů zahrnuje Panattu, Pietrangeliho a Vinciovou. „ALBO D'ORO“ a „PRESIDENTI“ jsou **jen PDF ke stažení**.
- Web uvádí „oltre 1000 soci“ a 20 tenisových kurtů. Klub se hlásí ke „Circoli Storici Centenari“ a k Centenary Tennis Clubs.

### 1.10 West Side Tennis Club (Forest Hills)
URL: https://thewestsidetennisclub.com/, …/About_Us/Our_History. Snímky: `montaze/wstc.jpg`, `wstc-history.jpg`.
- Nadpisy v **Lustria** (55 px) a text v Kumbh Sans, navy `#00002A` a žlutá. Každý blok je plná fotka s textem vpravo nebo vlevo („Play at Your Own Level“, „Make it a Daylong Experience“, „Watch Premiere Entertainment“, „Eat Well, Stay Longer“). Vyprávění stojí na **životním stylu, ne na výsledcích**.
- Stadion jako kulturní scéna: „Our 14,000-seat stadium once hosted the U.S. Open and historic performances by The Beatles, Jimi Hendrix, and Bob Dylan. Today, it brings over 30 concerts annually“.
- Historie: „founded in 1892“, „In 1923, we built America's first tennis stadium“, „38 courts across grass, clay, and hard surfaces“ a Arthur Ashe v roce 1968. Černobílá mozaika fotek.
- **Negativní příklad:** vyskakovací okno „Keep the Music Playing – Unlock your membership preview“ se objevuje opakovaně při rolování.

### 1.11 Longwood Cricket Club (Boston)
URL: https://www.longwoodcricket.com/, …/About_Us. Snímky: `montaze/longwood.jpg`.
- Šablona Jonas Club Software, zelená lišta, Raleway a Lato. Hero slider s bílou klubovnou nad travnatými dvorci posekanými do šipek.
- **„Court Updates“ jako živý kanál** (vložený Mastodon @LongwoodCourtUpdate), například „Clay Courts Open on Schedule / Grass Courts Open Starting Approximately 11am“ (23. 9. 2026). Primitivně vložené, ale **informace o stavu kurtů je pro členy nejužitečnější obsah**.
- About: „25 grass and 19 clay tennis courts“, „700+ members“, první lawn-tenisový dvorec v roce 1878, Dwight Davis (zakladatel Davis Cupu) mezi ranými členy. Motto „Pioneering History Vibrant Present“ a citát „This corner of the earth, beyond all others, delights me most.“ Údaj z vyhledávače („more than 1,000 members and 44 courts“) se od webu liší, věřím webu.

### 1.12 International Tennis Hall of Fame (Newport)
URL: https://www.tennisfame.com/, https://www.tennisfame.com/hall-of-famers/inductees. Snímky: `montaze/tennisfame.jpg`, `ithf-inductees.jpg`.
- Úvodní stránka: velký nápis „272 HALL OF FAMERS FROM 28 COUNTRIES – THE ULTIMATE HONOR IN TENNIS“. Svislý nápis „HALL OF FAME INDUCTEES“ po levém okraji. Gotham verzálky a neonově žlutá.
- **Adresář inductees:** vyhledávání („Name or Class Year“), řazení (rok, abeceda) a filtry země (včetně Czech Republic), kategorie (Recent Player, Master Player, Contributor, Wheelchair Tennis) a pohlaví. Karta nese fotku, vlaječku, „CLASS OF 2026“, jméno verzálkami a „VIEW PROFILE →“. Pás černobílých náhledů, z nichž aktuální je barevný.
- Profily klubových legend existují:
  - https://www.tennisfame.com/hall-of-famers/inductees/jaroslav-drobny – „CLASS OF 1983“; finále Wimbledonu 1954 proti Rosewallovi „13-11, 4-6, 6-2, 9-7“, „The 58 games remained the longest final played until the mid-1970s“ a „the first to win wearing eyeglasses“,
  - https://www.tennisfame.com/hall-of-famers/inductees/jan-kodes – „CLASS OF 1990“; finále 1973 „6-1, 9-8 (7-5), 6-3“ proti Metrevelimu.
- **Rozpory s webem cltk.cz:**
  - ITHF uvádí u Kodeše „11 singles and 17 doubles career titles“, cltk.cz „8 titulů ve dvouhře a 17 ve čtyřhře“ (viz `02-klub-clenstvi-akce.md` 5.1),
  - u Drobného ITHF píše, že se stal britským občanem „in 1959“, cltk.cz uvádí „od roku 1960“ → **NEOVĚŘENO, ověřit s klubem**.

### 1.13 Aman
URL: https://www.aman.com/. Snímky: `montaze/aman.jpg`.
- Pozadí `#F3EEE7` (teplý len), serif **Lyon Text/Display** a bezserifový **Whitney** (drobné verzálky 9,8 px). Jediné tmavé tlačítko „Reserve“ vpravo nahoře, jinak podtržené odkazy „Discover more“.
- **Hero video s antukovým kurtem** (hráč v bílém na antuce). Titulek „Stillness and Strength: A Novak Djokovic Retreat“ (Amanjena, Maroko). Důkaz, že **antuka sama je luxusní obraz**, když je dobře nasvícená a v tlumených barvách.
- Rytmus: široké fotky 2:1 vedle úzkých 2:3, pod nimi drobný verzálkový nadtitulek s místem („AMANJENA, MOROCCO“), serifový titulek a dvě věty. Obrovské mezery. Patička je adresář všech destinací podle kontinentů („across 36 hotels and resorts in 21 countries“).

### 1.14 Hermès
URL: https://www.hermes.com/uk/en/. Vizuál **NEOVĚŘENO**: stránka se po načtení přepnula na „Access is temporarily restricted“ (ochrana proti botům). Data z DOM jsem stihl vytáhnout (`web/hermes.json`).
- Písma: nadpisy **EB Garamond** (34 px, váha 440), text a menu **Manrope**, h1 v Overpass Mono verzálkách. Pozadí `#FCF7F1`. **Kombinaci EB Garamond + Manrope používá Hermès a obě písma jsou zdarma na Google Fonts.**
- Básnické nadpisy a výzvy: „A starry horizon“ → „FOLLOW PEGASUS“, „A canopy in silk“ → „SET THE DRAPE FREE“, „Time as never seen before“ → „EXPLORE THE SHADES“. Každý blok má jednu větu poezie místo obchodního popisu.

### 1.15 Rolex (úvod + historie)
URL: https://www.rolex.com/en-gb, https://www.rolex.com/en-gb/about-rolex/history/1905-1919. Snímky: `montaze/rolex.jpg`, `rolex-history.jpg`.
- **Historie po kapitolách-epochách** (1905–1919, 1926–1945…). Hero s černobílou archivní fotkou a velkým „EARLY YEARS 1905–1919“. Dole plave pilulka „History ⌃“, tedy navigátor kapitol, který se rozbalí.
- Obří letopočty („1905“, „1910“, „1914“) jako nadpisy s krátkým textem a archivními dokumenty (certifikát Kew Observatory). Citát zakladatele o vzniku jména („a genie whispered 'Rolex' in my ear“) a karta „How did the word Rolex originate?“.
- Patička má přepínače **„Reduce motion“ a „Contrast active“**, což je přístupnost jako součást luxusu. Písmo Helvetica Now Text, hero úvodní stránky patří Laver Cupu (tenis). Stránka /world-of-rolex/tennis vrací 404.

### 1.16 Loro Piana → náhrada Brunello Cucinelli
- loropiana.com vrátil „Access Denied“ (403) v Chromiu i v Edge a WebFetch skončil timeoutem → **NEOVĚŘENO**.
- Místo něj Brunello Cucinelli, https://www.brunellocucinelli.com/en/ (snímek `montaze/cucinelli.jpg`):
  - dlaždice 2 × 2 s tenkým serifovým titulkem uprostřed, citát jako titulek („"Beauty is the symbol of the morally good" – I. Kant“),
  - **ručně kreslené perokresby** (Dioscuri, skicy oděvů) střídané s fotkami v pískových tónech,
  - drobné verzálkové podtržené výzvy („EXPLORE“, „DISCOVER MORE“),
  - černobílá fotka pomníku „Tributo alla dignità dell'uomo“.
- Pro ČLTK z toho plyne, že **perokresba (klubovna 1929, viadukt, centrkurt 1927)** umí nést noblesu levněji než focení.

### 1.17 Soho House
URL: https://www.sohohouse.com/. Snímky: `montaze/sohohouse.jpg`.
- Pozadí `#FFFEF7`, nadpisy **Cardo** (56 px), text HK Grotesk (Hanken Grotesk je na Google Fonts, podle stejného studia Hanken Design Co. jde nejspíš o jeho verzi – NEOVĚŘENO). Hero video s viditelným **tlačítkem pauzy** a pilulka „Apply for membership“.
- Čísla v textu místo infografik: „48 Houses in 19 countries… 22 gyms… 11 spas… 29 pools… more than 2,400 monthly events“. Dva pásy fotek interiérů s názvy domů posouvané šipkami.
- „Coming soon: Soho River House… our first Soho House Racquet & Rowing Club“ na Temži. **Tenis + řeka + klub je aktuální luxusní téma.**

### 1.18 Annabel's
URL: https://www.annabels.co.uk/. Snímek: `web/annabels-hero.png`.
- **Celá úvodní stránka je jediná fotografie** (výška stránky 900 px, nic se nerolluje). Bohatý interiér jídelny přes celou obrazovku, malé zelené logo psacím písmem vlevo nahoře a pilulková navigace vpravo („Apply for Membership · Annabel's New York · Useful Information · Contact Us“).
- Lato, tmavě zelená `rgb(0,41,21)`. Ukázka, že **u členského klubu stačí jedna silná fotka a málo slov**.

### 1.19 Muzea s časovou osou a hloubkovým zoomem
- **Rijksmuseum** (https://www.rijksmuseum.nl/en, snímek `montaze/rijksmuseum.jpg`): obří vlastní logotyp „RIJKS MUSEUM“ přes fotku Noční hlídky s návštěvníky a štítek „OPEN TODAY“. Tisková zpráva (https://www.rijksmuseum.nl/en/press/press-releases/rijksmuseum-publishes-717-gigapixel-photograph-of-the-night-watch) popisuje fotografii Noční hlídky o **717 gigapixelech**, složenou z 8 439 snímků a online od 3. 1. 2022. Divák může zoomovat až na zrnka pigmentu. Samotný prohlížeč jsem neotevřel (moje URL vrátila 404).
- **The Met** (https://www.metmuseum.org/essays, snímek `montaze/met-toah.jpg`): „All Essays“ s vyhledáváním, filtrem oddělení a zaškrtávací sérií „Timeline of Art History“. Každý esej má autora a datum, tedy **podepsané texty jako v magazínu**.

### 1.20 České kluby (co je standard)

| Klub | URL / snímek | Co tam je |
|---|---|---|
| **I. ČLTK – dnešní web (výchozí stav)** | https://cltk.cz/cs/ · `montaze/cltk-home.jpg` | SUITU / MySuitu CMS, Open Sans, fialová `#312B81` a `#1F1B52`. Znak uprostřed přes hlavičku, letecká fotka areálu, 3 dlaždice (Pronájem kurtů, Členství, Kalendář), widget „Výsledky našich hráčů“ (Resultina), blok I.ČLTK Revue „Prohlédnout vydání“, Aktuality, loga partnerů, mapa Google, logo CTC v patičce. |
| TK Sparta Praha | https://tkspartapraha.cz/ · `montaze/tkspartapraha.jpg` | Plovoucí zaoblená lišta navy `#0C1A38`, 4 dlaždice s ikonami (Rezervace, Ceník, Padel Pickleball rezervace, Tenisové kempy), **„ZÁPASY SPARŤANŮ“ – živé výsledkové karty** (země, kolo, sety), emoji v titulcích („🎾Květnové úspěchy…“, „👑Sparťanky…“), banner Lenovo, velká stěna partnerů, Helvetica/Roboto verzálky. |
| TK AGROFERT Prostějov | https://www.tkagrofert.cz/ · `montaze/tkagrofert.jpg` | Layout z doby tabulek (~960 px), zelená hlavička s fotkou a podpisem Jiřího Veselého, Arial 12 px, šedé pozadí `#BCBEC0`, oranžová tlačítka „více“, tabulka „Kalendář akcí“, loga v postranním sloupci. |
| TK PRECHEZA Přerov | https://tkprerov.cz/ · `montaze/tkprerov.jpg` | WordPress, hero s **Lindou Noskovou a wimbledonskou mísou**, H1 Roboto 90 px, modrá `#006DB8`. „Trénuj v klubu, kde se připravuje vítězka Wimbledonu Linda Nosková!“, „ÚSPĚCHY KLUBU V ROCE 2025“ (Dospělí 2. místo…), seznam výsledků jako odkazy, služby (kryokomora, masáže, ubytování). |
| TK Neridé (Praha-Hostivař, **ne** Prostějov) | https://neride.cz/ · `montaze/neride.jpg` | Divi (WordPress), Barlow Condensed + Bebas Neue + Poppins, dronová fotka, ceníkové karty („KURT 250 KČ/H“), fotogalerie s logy Wilson. Z webu: „založen v roce 1995… Stal se extraligovým klubem v roce 1997 po fúzi s klubem TK Prostějov „B““. |
| LTC Pardubice | https://www.ltcpardubice.cz/ · `montaze/ltcpardubice.jpg` | WordPress, Overpass, slider s fotkou míčku na lajně (fotobanka), „Co je nového?“ s oranžovými titulky, stránkování „1 2 3 … 42“, „V našem areálu můžete využít 11 antukových kurtů, 1 betonový a tenisovou halu“, velká stěna partnerů. |
| OSTRA Tenisová extraliga | https://www.tenisovaextraliga.cz/ · `montaze/tenisovaextraliga.jpg` | Arial verzálky, výsledky jako prostý text s odkazy na PDF, „Pořadí 2024: 1. TK Sparta Praha, 2. TK Precheza Přerov, 3–4. I.ČLTK Praha…“. |
| TC Brno, TK Slavia Praha (tenis), „LTC Prostějov“ | – | Oficiální web **nenalezen**. TC Brno má jen profily na tenisdetem.cz a sportvokoli.cz. U Slavie vyhledávač vrací zastaralé volny.cz/tkslavia. „LTC Prostějov“ jako samostatný klub jsem nenašel, v Prostějově je TK AGROFERT → **NEOVĚŘENO**. |

**Souhrn českého standardu:** nabídka v dlaždicích, výsledky jako seznam nebo widget, novinky v kartách, hodně log a sportovní verzálky. **Ze zkoumaných webů nikdo nemá:** redakční magazín, práci s historií, typografickou péči, světlou teplou paletu, živý stav areálu ani mezinárodní rozměr (CTC).

### 1.21 Referenční návrh zadavatele – „Prague Open 03 bílá luxusní“
URL: https://magical-crumble-00742c.netlify.app/03-bila-luxusni (lokální kopie `podklady/_raw/reference-prague-open-03-bila-luxusni.html`, snímek `montaze/ref-prague-open-03.jpg`).
- Tokeny ze zdrojového kódu: `--paper:#fbfaf6`, `--paper-2:#f3f0e9`, `--navy:#152744`, `--navy-deep:#0e1b30`, `--gold:#b3935a`, `--gold-soft:#caac79`, `--ink:#1a2233`, `--muted:#7b8092`, `--line:#e4e0d6`. Písma **Cormorant Garamond** (včetně kurzívy) + **Jost**.
- Prvky:
  - v titulcích je jedno slovo zvýrazněné zlatou kurzívou („Tenis tam, kde má v Praze *kořeny*“, „Týden na *Štvanici*“),
  - verzálkové popisky Jost s prostrkáním a zlatou čárkou,
  - boxy s čísly oddělené tenkými linkami (26 · 2 · 7 · 1893),
  - navy blok „Program“, římské číslice I. II. III. a partneři jako text v serifové kurzívě.
- Tuto DNA může převzít jedna ze tří variant (viz kap. 5).

---

## 2. Nápady – 26 konkrétních, v HTML/CSS/JS proveditelných prvků pro I. ČLTK

U každého nápadu uvádím, **co to je**, **proč je unikátní**, **jak to postavit** a z jakých podkladů čerpat. Fakta o klubu jsou převzatá z `02`, `03`, `05` a `06` (tam jsou zdroje).

### A. Příběh a dědictví

1. **Triptych „Tři wimbledonské trávy“ – 1954 · 1973 · 2023.**
   - *Co:* tři svislé panely (Drobný, Kodeš, Vondroušová). Pod každým rok a skóre finále vysázené jako rytina na čestné desce. Po najetí nebo ťuknutí se černobílý portrét zbarví a ukáže se soupeř.
   - *Unikátní:* Přerov má jednu vítězku Wimbledonu, ČLTK tři v rozpětí 69 let a u dvou z nich existuje profil v ITHF.
   - *Stavba:* CSS grid 3 × `aspect-ratio: 2/3`, `filter: grayscale(1)` → 0 přes `transition`, skóre s `font-variant-numeric: lining-nums tabular-nums`. Dotyková zařízení přepínat přes `<button aria-expanded>`, ne jen hover.
   - *Data:* Drobný 13-11, 4-6, 6-2, 9-7 (Rosewall) a Kodeš 6-1, 9-8 (7-5), 6-3 (Metreveli) podle tennisfame.com. Skóre Vondroušové ve finále 2023 v podkladech není → **doplnit ze zdroje**.
   - ⚠ Prezentaci Vondroušové je nutné probrat s klubem kvůli prohlášení k případu ITIA (`02` 5.1).
2. **„Zlatá deska“ – digitální čestná tabule.**
   - *Co:* obdoba zlacených jmenných desek v klubovnách. Sloupce roků a jmen: mistrovské tituly družstev, grandslamy členů, olympijské medaile, čestní a zasloužilí členové.
   - *Unikátní:* Queen's má „Past Winners“ jako prostý seznam, Parioli „Albo d'oro“ jako PDF. Nikdo z toho nedělá předmět.
   - *Stavba:* grid se dvěma sloupci a tečkovanými odkazovými linkami (`::after` s `border-bottom: 1px dotted`), verzálky s prostrkáním 0,12 em, jemná „rytina“ přes `text-shadow: 0 1px 0 rgba(255,255,255,.7), 0 -1px 0 rgba(0,0,0,.12)` na papírovém nebo dřevěném podkladu v barvě `--paper-2`.
   - *Data:* 16 titulů je doloženo souhrnně, ale **úplný seznam 12 let z období 1956–1973 chybí** (GAP v `06` kap. 7). Do té doby zobrazit jen doložené roky (1975, 1990, 2018, 2019, foto 1966) a zbytek jako „11 titulů v řadě v 60. a na začátku 70. let“.
3. **Kronika se čtyřmi jmény klubu.**
   - *Co:* historie po kapitolách-epochách jako u Rolexu. Nahoře je lepivý „razítkový“ štítek s dobovým názvem klubu, který se při rolování mění: I. ČLTK → Motorlet → Dopravní podnik → I. ČLTK.
   - *Unikátní:* Rolex má kapitoly, RCTB obecnou TimelineJS, Rot-Weiss prostý seznam. Změna jména jako dramatický prvek je jen ČLTK.
   - *Stavba:* sekce `<section data-era data-name>`, `IntersectionObserver` přepíná text štítku s krátkým `@keyframes` (blur → ostré). Plovoucí pilulka „Kapitoly ⌃“ s `<details>` nebo Popover API (Chrome 114, Safari 17, Firefox 125 podle MDN BCD 8.1.2).
   - *Data:* `podklady/data/kronika.json`, `06` kap. 2–4.
4. **„Ostrov v čase“ – mapa Štvanice s posuvníkem let.**
   - *Co:* SVG mapa ostrova ve Vltavě s posuvníkem od 18. století po dnešek. Vrstvy: aréna štvanic (18. stol., zákaz 1805), „Velké Benátky“, plovárna 1863, povodeň 29. 3. 1845, kurty od 1901, centrkurt 1927 pro 5 000 diváků, klubovna 1929–1983, nový centr 1986, povodeň 2002, dnešní areál (13 antukových + 3 tvrdé + 2 v hale, v zimě 12 krytých).
   - *Unikátní:* ostrov má jen ČLTK. Hurlingham a Soho River House mají „jen“ řeku.
   - *Stavba:* inline SVG, vrstvy `<g data-od="1901" data-do="1983">`, `<input type="range">` přepíná `opacity` a text popisku (`aria-live="polite"`). Mapový podklad obkreslit zjednodušeně (autorská perokresba, viz nápad 20).
   - *Data:* `06` kap. 5 a 14.
5. **Stěna es „Hráli na Štvanici“.**
   - *Co:* typografická stěna zhruba 60 jmen (Lacoste, Cochet, Budge, Laver, Borg, Navrátilová, Grafová, Wawrinka…) jako jeden velký zarovnaný blok. Po kliknutí se ukáže karta s rokem a příležitostí (jen kde je doloženo).
   - *Unikátní:* ITHF má mřížku fotek, ale nikdo nevizualizuje návštěvníky svého klubu. Je to i digitální obdoba fotokoláže es ve foyer klubovny (`06` kap. 12).
   - *Stavba:* `text-align: justify; text-wrap: balance` (Chrome 130, Safari 17.5, Firefox 121 podle caniuse), jména jako `<button popovertarget>`.
   - *Číslo:* použít „víc než šedesát“ podle nejnovějšího pramene [R23/1 s. 31] a uvést, že jde o stav k roku 2023. Počty 54, 58 a 59 v jiných vydáních si odporují (`06` kap. 16.3).
6. **Síň slávy klubu (čestní + zasloužilí).**
   - *Co:* adresář podle vzoru ITHF (hledání, řazení, filtr kategorie), ale v klidném luxusním provedení: černobílé portréty, jméno ve verzálkách se serifem, „Čestný člen od 1993“ (jen kde je rok doložen). U Drobného a Kodeše štítek „International Tennis Hall of Fame · Class of 1983 / 1990“ s odkazem na profil.
   - *Stavba:* statický JSON → `<ul>` s `data-kategorie`, filtrování přes `input` + `Array.filter`, bez knihovny.
   - *Data:* `podklady/data/clenove-sine-slavy.json`.
   - ⚠ Nutné vyřešit s klubem: portréty na dnešním webu mají jen 80–100 px; Ivo Minář na webu chybí; u Drobného je na webu chyba (ZOH).
7. **„Rodokmen stoletých“ – ČLTK mezi vrstevníky.**
   - *Co:* úzká vodorovná osa roků založení světových klubů s ČLTK zvýrazněným. Kliknutím se otevře karta klubu a vztah k ČLTK (CTC, přátelská utkání).
   - *Roky ze zkoumaných webů:* Hurlingham 1869 · Longwood 1878 (první lawn-tenisový dvorec) · Queen's 1886 · West Side 1892 · **I. ČLTK 1893** · Lawn Tennis de Monte-Carlo 1893 · Rot-Weiss 1897 · RCTB 1899 · Parioli 1906. Sparta 1905 je jen z vyhledávače (prague.eu), NEOVĚŘENO na webu klubu.
   - *Stavba:* flex s poměrným rozestupem podle roku (`left: calc((rok-1860)/170*100%)`).
   - *Unikátní:* zasazuje klub do světové elity, což české weby nedělají.
8. **Mapa CTC s „cestovním pasem“.**
   - *Co:* světová mapa zhruba 98 členských klubů CTC („V současné době CTC sdružuje 98 tenisových klubů“, cltk.cz/cs/klub/ctc). ČLTK je vyznačen jako jediný český člen. Partnerská utkání jsou orazítkovaná „razítka“ v pasu: Barcelona, Dublin (Fitzwilliam, Carrickmines), Bordeaux (Villa Primrose), Haag, AELTC na Štvanici 6.–8. 6. 2025.
   - *Unikátní:* Rot-Weiss má o CTC jen textovou stránku. Reciprocita (hra zdarma v členských klubech) je pro členy silný argument.
   - *Stavba:* zjednodušené SVG světa (Natural Earth, public domain), body z JSON, razítka jako SVG s `mask` šumu a `rotate(-6deg)`.
   - *Data:* `02` kap. 6.
9. **Virtuální vitrína předmětů.**
   - *Co:* muzejní karty předmětů s popiskem jako v muzeu (název · rok · materiál · příběh):
     - plaketa Jaroslava Drobného,
     - zlatý odznak zasloužilého člena (6 000 Kč, zlatnice Lucie Pekárková),
     - odznak s diamantem pro čestného člena (1993),
     - stříbrná pamětní mince ke 130 letům,
     - dřevěná raketa ze štědrovečerního turnaje „dřeváků“,
     - předválečný žebříček ve vitríně ve foyer,
     - obraz Tomáše Bíma ke 130 letům.
   - *Unikátní:* Wimbledon muzeum jen popisuje („sit on the bench used by Roger Federer“) a RG má těžké 3D místnosti. Předměty jako příběh nemá žádný klub.
   - *Stavba:* `<dialog>` s velkou fotkou (zoom přes `transform: scale` podle pozice kurzoru, na mobilu pinch). Volitelně 24–36 snímků pro otáčení o 360° (sprite + `pointermove`).
   - *Potřeba:* fotky předmětů od klubu (**zatím nemáme**).
10. **Hloubkový zoom společné fotografie ke 130 letům.**
    - *Co:* podle Rijksmusea (717 gigapixelů) přiblížitelná fotka zhruba 200 členů na tribuně centrkurtu z oslav 22. 7. 2023. Člen se najde a pošle odkaz s přesným výřezem (souřadnice v URL).
    - *Unikátní:* v klubovém prostředí jsem to neviděl nikde. Velmi osobní „wow“.
    - *Stavba:* OpenSeadragon (na cdnjs) + dlaždice DZI vygenerované předem (libvips `dzsave` nebo Python). Výřez do `location.hash`.
    - *Potřeba:* originál fotky ve vysokém rozlišení. **Bez jmenovek (GDPR)**, případně jen se souhlasem.
11. **„Tehdy a teď“ – porovnávací posuvník.**
    - *Co:* černobílá fotka turnaje pod Negrelliho viaduktem (1912) nebo starého centrkurtu (1978) a přes ni dnešní fotka stejného místa. Tažením se odkrývá.
    - *Stavba:* dva `<img>` nad sebou, `clip-path: inset(0 calc(100% - var(--x)) 0 0)`, `<input type=range>` pro klávesnici. Jako progresivní vylepšení lze odkrývat rolováním přes `animation-timeline: view()` (Chrome 115, Safari 26; ve Firefoxu jen „preview“ podle MDN BCD → nutný záložní stav).
    - *Potřeba:* dnešní fotky ze stejného úhlu (**NEOVĚŘENO**, zda jde nafotit).
12. **Tendiv a „Štvanický Bobík“ – lidská tvář klubu.**
    - *Co:* sekce o klubovém kabaretu (od 1979, zhruba 30 amatérských herců, píseň „Nastupuje Štvanice“) a cenách „Štvanický Bobík“ (od silvestra 2006) jako divadelní plakáty.
    - *Unikátní:* žádný zkoumaný klub nemá humor ani kulturu. West Side staví na koncertech, ČLTK může na vlastním kabaretu.
    - *Stavba:* karty ve stylu plakátu (verzálkový serif, dvoubarevný tisk přes `mix-blend-mode: multiply` na papíře).
    - *Data:* `06` kap. 12.

### B. Revue, bulletin a zpravodajství

13. **Revue jako online magazín s rubrikami a podpisem autora.**
    - *Co:* články s rubrikou (zlatý štítek „INTERVIEW / Z KLUBU / OSOBNOST“ převzatý z tištěné Revue, `05` kap. 8), podpisem a datem jako na metmuseum.org/essays, velkými citáty a iniciálou.
    - *Unikátní:* v ČR nikdo, ve světě spíš muzea a noviny než kluby.
    - *Stavba:* `::first-letter` s `float` (`initial-letter` je jen částečně v Chrome 110+ a Safari, ve Firefoxu chybí → jen jako vylepšení), `text-wrap: pretty` (Chrome 117, Safari 26; Firefox ne), měřítko řádku 62–68 znaků.
    - *Data:* 239 článků z webu (`podklady/data/clanky.json`).
14. **Kiosek 20 let Revue – obálková zeď a fulltext.**
    - *Co:* 41 obálek 2006–2026 jako polička. Po najetí se obálka povytáhne, po kliknutí otevře čtečka. **Fulltext napříč všemi čísly** najde osobu nebo slovo a vede na konkrétní číslo a stranu.
    - *Unikátní:* Rot-Weiss má jen mřížku a „ePaper lesen“ (2012–2025), ČLTK má delší archiv a vyhledávací.
    - *Stavba:* text stran předem vytáhnout z PDF (PyMuPDF; texty jsou vybratelné, u 2006–2014 opravit chybné „ď/ť“, viz `05` kap. 1) do JSON indexu. Hledání v prohlížeči přes MiniSearch nebo Lunr, odkaz `soubor.pdf#page=N`. Polička: `perspective` + `rotateY(-25deg)` na `:hover`/`:focus-visible`.
    - *Data:* `podklady/data/revue.json`, obálky v `assets/revue/`. Čísla 01/2016 a 02/2016 nemají PDF.
15. **„Příběh v obálkách“.**
    - *Co:* výběr osoby (Vondroušová od „Talentu“ 01/2010 po Wimbledon 02/2023, Muchová 2018 → 2026, Kodeš) a pás jejích obálek a článků v čase.
    - *Stavba:* filtr nad `revue.json` (štítky osob) → vodorovný pás se `scroll-snap-type: x mandatory`.
    - *Unikátní:* archiv, který sám vypráví kariéry.
16. **„Dopis z ostrova“ – newsletter jako korespondence.**
    - *Co:* 78 newsletterů od roku 2015 (CS/EN) jako dopisy s poštovním razítkem „Ostrov Štvanice 38, Praha 7“ a datem. Přihlášení k odběru vypadá jako korespondenční lístek.
    - *Stavba:* CSS karta s perforací (`radial-gradient` maska na okraji), razítko jako SVG.
    - *Data:* `podklady/data/newslettery.json`.

### C. Živý klub (provoz a servis v luxusním provedení)

17. **„Dnes na Štvanici“ – živý stav areálu.**
    - *Co:* úzký pruh pod hlavičkou: stav antuky (otevřeno / zavřeno po dešti), haly (sezóna nafouknutí), teplota bazénu, počasí, západ slunce (kolik času zbývá na venkovních kurtech; osvětlení mají 3 kurty) a otevírací doba recepce.
    - *Inspirace:* Longwood „Court Updates“ (Mastodon: „Clay Courts Open on Schedule“), RCTB „Temperatura de la piscina 28°C“ + meteo.cat.
    - *Unikátní:* v ČR nikdo. Pro členy nejužitečnější věc a zároveň decentní „život“ na stránce.
    - *Stavba:* malý JSON, který mění recepce v administraci (nebo sdílená tabulka). Počasí přes Open-Meteo API (zdarma, bez klíče), východ a západ slunce spočítat v prohlížeči (SunCalc na cdnjs).
    - *Potřeba:* domluvit, kdo stav aktualizuje.
18. **Ciferník sezóny – roční cyklus klubu.**
    - *Co:* kruhový „ciferník“ 12 měsíců s ručičkou na dnešním datu. Obsah: otevření antuky, klubové dny (květen, září), valná hromada (červen), kempy, Prague Open, CTC U14 (listopad), Mikuláš, Večer talentů a vánoční večírek v Letenském zámečku (prosinec), zima v halách. Tradici „sezona začíná na Josefa“ (19. 3., podle Bečky, `06` kap. 15) přidat jen po ověření s klubem.
    - *Unikátní:* elegantní a „hodinářský“ prvek (odkaz na Rolex), ale s obsahem klubu.
    - *Stavba:* SVG oblouky generované v JS (`describeArc`), `conic-gradient` jako podklad, `<ol>` záloha pro čtečky.
    - *Data:* `02` kap. 8, 12.
19. **Konfigurátor členství + vícekroková přihláška.**
    - *Co:* složení domácnosti (hrající a nehrající dospělí, děti) s živou cenou podle ceníku 2026 a časovou osou „žádost → výbor na nejbližším zasedání → platba → členská karta“. Nápad pochází z `02` kap. 12.
    - *Unikátní:* Sparta má jen tlačítko „Staň se členem“, Soho House „Apply for membership“. Transparentní luxusní konfigurátor nemá nikdo.
    - *Stavba:* `<form>` s `<fieldset>` kroky, výpočet v JS, čísla s `tabular-nums`, `<output>` pro cenu.
    - Pozor na atribut `form=` a výchozí tlačítko (Enter nesmí přeskočit krok).
20. **Klubová perokresba jako vizuální jazyk.**
    - *Co:* sada tenkých perokreseb (klubovna 1929 s modřínovým obložením, centrkurt 1927, oblouky Negrelliho viaduktu, siluety topolů z obrazu T. Bíma) pro prázdné stavy, ikony sekcí, patičku a chybovou stránku 404.
    - *Inspirace:* Brunello Cucinelli (Dioscuri, skicy).
    - *Stavba:* SVG `stroke` 1–1,25 px v `--ink`, animace kreslení `stroke-dashoffset` jen bez `prefers-reduced-motion`.
    - *Unikátní:* vlastní ilustrace = žádné fotobanky.
21. **Výsledkový „slonovinový scoreboard“.**
    - *Co:* výsledky hráčů (dnes widget Resultina) přestylované do klidné tabule: vítěz serifem, sety v tabulkových číslicích ve sloupcích, kategorie turnaje jako drobný verzálkový štítek, na mobilu vodorovný posun.
    - *Unikátní:* Sparta má živé karty ve sportovním stylu. Luxusně vysázené výsledky nemá nikdo.
    - *Stavba:* CSS grid `grid-template-columns: 1fr repeat(3, 2.2ch)`, `font-variant-numeric: lining-nums tabular-nums`.
    - *Data:* Resultina; zda má otevřený výstup (API), je **NEOVĚŘENO** (`03` kap. 5).
22. **Stopa míčku na antuce – mikrointerakce.**
    - *Co:* velmi decentní detail, například v jedné variantě na sekci Antuka. Kliknutí nebo tažení zanechá eliptický „otisk míčku“, který za 1,5 s zmizí. Narážka na kontrolu stopy na antuce.
    - *Stavba:* `pointerdown` → absolutně umístěný `<span>` s `radial-gradient` a `@keyframes fade`. Vypnuto při `prefers-reduced-motion` a na hlavních CTA.
    - *Unikátní:* hravé, spojené s povrchem klubu, ne s obecným tenisem.

### D. Detaily, které dělají luxus

23. **Sezónní kůže webu.**
    - *Co:* v prosinci se web jemně přepne do „zimní haly / dřeváků“ (sépiová fotka turnaje „dřeváků“ na Štědrý den, vánoční večírek v Letenském zámečku), v létě do „antuky“.
    - *Stavba:* sada CSS proměnných podle data (`document.documentElement.dataset.sezona = …`).
    - *Unikátní:* žije s rokem klubu. Nic to nestojí.
24. **Tisková kultura: certifikát a tiskové styly.**
    - *Co:* nový člen si může vytisknout „Členský list I. ČLTK Praha – založen 1893“ (jméno, rok přijetí, znak) a každý článek Revue má pečlivý tiskový styl.
    - *Stavba:* `@media print` + `@page { margin: 18mm }`, `window.print()`. Certifikát jako HTML šablona (bez PDF knihovny).
    - *Unikátní:* luxus = tištěné věci. Navazuje na odznaky a pamětní mince klubu.
25. **Přístupnost jako součást noblesy.**
    - *Co:* přepínače „Omezit pohyb“ a „Vyšší kontrast“ v patičce (jako Rolex), pauza u každého videa (jako Soho House), plná obsluha klávesnicí.
    - *Stavba:* `prefers-reduced-motion` a `prefers-contrast` jako výchozí stav, přepínač uložený v `localStorage` (v `try/catch`), `data-motion="off"` na `<html>`.
    - *Unikátní:* v ČR u sportovních klubů neexistuje.
26. **Dvojjazyčnost CS/EN jako rovnocenná.**
    - *Co:* EN verze se stejnou strukturou a stejnými daty, generovaná ze společného JSON. Nutné kvůli hostům CTC a zahraničním členům.
    - *Důvod:* dnešní EN verze je zastaralá, uvádí „15 outdoor clay courts“ proti 13 v CS (`02` kap. 2).
    - *Stavba:* `lang` atributy, přepínač zachovává stránku (`/cs/klub/ctc/` ↔ `/en/club/ctc/`).

---

## 3. Čemu se vyhnout (klišé tenisových webů)

1. **Fotobankový detail rakety nebo míčku jako hero.** Rot-Weiss (výplet s míčkem), LTC Pardubice (míček na lajně), Neridé (míček na raketě). Místo toho vlastní místo: ostrov, antuka, klubovna, lidé.
2. **Neonově žlutá „tenisová“ akcentová barva.** ITHF, žlutá na Spartě. Ve světlém luxusu nepatří.
3. **Úzké sportovní verzálky** (Barlow Condensed, Bebas Neue, Roboto 800–900). Působí jako sportovní obchod.
4. **Dlaždice s ikonami jako hlavní obsah úvodní stránky** (Sparta, dnešní cltk.cz). Rychlé odkazy ano, ale drobně, ne jako hlavní motiv.
5. **Automatické slidery s tečkami** (LTC Pardubice, RCTB, MCCC, Rot-Weiss). Na mobilu nečitelné, obsah na 2. a 3. snímku nikdo neuvidí.
6. **Stěna barevných log partnerů** zabírající půl úvodní stránky (všechny české weby, RCTB tři řady). Místo toho jednobarevný pás nebo samostatná stránka partnerů.
7. **Vložené sociální sítě** (RG: X a Instagram, Longwood: Mastodon, ITHF: Curator.io). Rozbíjejí typografii a zpomalují.
8. **Vyskakovací okna** (opakované „Keep the Music Playing“ na WSTC) a **cookie okno přes celou stránku** (MCCC). Lišta souhlasu má být malá a jednou.
9. **Generické „Vítejte v …“ jako H1** (Queen's, Hurlingham, Longwood, Rot-Weiss, KTC, Neridé – všichni „Welcome/Willkommen/Vítejte“).
10. **Historie jen jako PDF ke stažení nebo sken bez textu** (Parioli, dnešní odkazy na PDF). Nevyhledatelné, nečitelné na mobilu.
11. **Emoji v titulcích** (Sparta „🎾…“, „👑…“) a **tlačítka „více“** u každé zprávy (Agrofert).
12. **Stránkování „1 2 3 … 42“** u novinek. Lepší filtr podle rubriky a roku a „načíst další“.
13. **Falešný luxus:** zlaté přechody, mramor, 3D efekty, psací písma v nadpisech (Great Vibes, Carattere, Allura: v rendru se jim háčky na verzálkách tlačí do písmen, viz `fonty/spec-6.png`).
14. **`background-clip: text` s přechodem na českých nadpisech.** Ukrojí háčky (zkušenost z předchozích projektů). Když už, s dostatečným `padding-top` a kontrolním renderem.
15. **Informace jen po najetí myší.** Na mobilu nedostupné, vždy musí jít i o `<button>`.
16. **Paralaxa a animace všeho.** Pohyb jen tam, kde nese význam (časová osa, porovnání „tehdy a teď“), vždy s `prefers-reduced-motion`.
17. **Text zapečený v obrázcích** (dnešní obálky Revue jako snímky obrazovky; bannery s textem).
18. **Rozporná čísla** (58 / 59 / „víc než šedesát“ GS vítězů; 13 vs 15 antukových kurtů v EN). Vždy jedno doložené číslo s datem.
19. **Zanedbaná EN verze** a **zastaralé údaje na profilech** (Vondroušová „vyhrála dva singlové turnaje“, rozbité portréty).

---

## 4. Typografie a barvy pro „decentní luxus ve světlých barvách“

### 4.1 Ověření češtiny u písem Google Fonts
- **Metadata „latin-ext“ nestačí.** Kontrola glyfů v TTF (fontTools, soubor `podklady/_raw/inspirace/fonty-kontrola.txt`) našla písma, která latin-ext hlásí, ale **chybí jim české znaky s háčkem**:
  - **Julius Sans One**, **Quattrocento** (serif), **Rosarivo**, **Mrs Saint Delafield** a **Monsieur La Doulaise**: chybí Č Ď Ě Ň Ř Ť Ů,
  - bez latin-ext úplně jsou **Italiana**, **Prata**, **Tangerine**, **Playwrite CZ** (!) a **Lustria** (písmo West Side Tennis Clubu) → nepoužívat.
- **Vizuální render** české ukázky se 60 písmy (`fonty/specimen.png`, rozdělené `spec-1…6.png`) potvrdil správné háčky u všech doporučených písem. Výjimka se objevila jen v hromadném testu: **Tenor Sans** tam vykreslil verzálky bez háčků, ale samostatný test (`fonty/tenor.png`) je v pořádku. Ponaučení:
  - čeština potřebuje soubor písma *latin* i *latin-ext*,
  - v produkci písma hostovat u sebe nebo latin-ext přednačíst (`<link rel=preload>`),
  - vždy udělat kontrolní render s „ŘÍJEN NA ŠTVANICI – ŽEMLA · KOŽELUH · ŮČTY ĎÁBLA, ŤUK“.
- **Výška háčků a řádkování.** U Playfair Display, Libre Caslon Display/Text, Bodoni Moda a Castoro sahají háčky na verzálkách vysoko (při `line-height: 1.0` se dotýkají řádku nad) → verzálkové titulky s `line-height` ≥ 1,08–1,12. Cormorant/Cormorant Garamond má háčky nad verzálkami ploché → snese 0,95–1,0.
- **OpenType v Google verzích.** Cormorant Garamond má lnum a tnum (výchozí jsou minuskové „starodávné“ číslice, viz render „1893“) → v tabulkách výsledků přepnout na `font-variant-numeric: lining-nums tabular-nums`. Skutečné kapitálky (smcp) Google verze těchto písem nemají → použít rodinu **Cormorant SC**, nikdy syntetické (`font-synthesis: none`). Newsreader, Source Serif 4, Literata a Spectral mají tnum.

### 4.2 Doporučené dvojice (všechny s ověřenou češtinou)

| # | Nadpisy (display) | Text a popisky | Čísla / data | Charakter | Kdo podobně |
|---|---|---|---|---|---|
| A | **Cormorant Garamond** (300–700 + kurzíva) | **Jost** (300–600, verzálky s prostrkáním) | Jost `tabular-nums` | klasická bílá luxusní, zlatá kurzíva v titulku | referenční návrh Prague Open 03 |
| B | **EB Garamond** (400–800) | **Manrope** (200–800) | Manrope tnum | „Hermès“ – literární, klidná | hermes.com (EB Garamond + Manrope podle DOM) |
| C | **Newsreader** (opsz 6–72, 200–800) | **Hanken Grotesk** nebo **Instrument Sans** | **Geist Mono** / IBM Plex Mono na popisky archiválií | redakční magazín (Revue) | Aman (Lyon + Whitney), Soho (Cardo + HK Grotesk) |
| D | **Bodoni Moda** (opsz 6–96) nebo **Gloock** | **Albert Sans** nebo **Schibsted Grotesk** | Geist Mono | neobvyklý, Art Deco (klubovna 1929, centr 1927) | Monte-Carlo (Ivy Mode + DM Sans) |
| E | **Fraunces** (opsz 9–144, SOFT, WONK) | **Figtree** nebo **Work Sans** | – | teplejší, přátelský, „rodinný klub“ | West Side (Lustria + Kumbh Sans) |
| F | **Cardo** | **Hanken Grotesk** | – | nejbližší Soho House | sohohouse.com |

Další ověřená a vhodná písma: Castoro, Brygada 1918 (polský design s výbornou diakritikou), Libre Caslon Text, Gilda Display, Marcellus, Bellefair, Forum, Instrument Serif (úzký, na velká čísla), Source Serif 4, Literata, Spectral (dlouhé texty). Sans: Inter Tight, Figtree, Onest, Geist, Mona Sans, Red Hat Display, Belleza, Josefin Sans.
Psací písmo jen na podpisy nebo jedno slovo malými písmeny: Pinyon Script, Italianno, Petit Formal Script (česky OK). Nikdy ne verzálky.

**Pravidla sazby (moje doporučení):**
- popisky 11–12 px, verzálky, `letter-spacing: .18–.24em`,
- text 17–19 px / 1,6, šířka řádku 60–70 znaků,
- nadpisy v serifu 300–400 (ne tučně), jedno slovo zlatou kurzívou,
- velké letopočty minuskovými číslicemi, v tabulkách tabulkové,
- české uvozovky „…“ a nedělitelné mezery po jednoznakových předložkách (v, k, s, z, o, u, a, i) přes build skript.

### 4.3 Barvy

**Naměřená pozadí světlých luxusních webů** (z DOM):

| Web | Pozadí |
|---|---|
| Aman | `#F3EEE7` |
| Hermès | `#FCF7F1` |
| Soho House | `#FFFEF7` |
| Annabel's | `#F3F1ED` |
| FFT | `#F7F4EE` |

**Naměřené tmavé barvy písma a značek:**

| Web | Barva |
|---|---|
| MCCC | navy `#14173A` |
| Queen's | navy `#1A3150` |
| West Side | navy `#00002A` |
| Wimbledon | zelená `#00552B` |
| Parioli | zlatá `#BC8D0A`, zelená `#1F382E` |
| Dnešní cltk.cz | fialová `#312B81`, tmavá `#1F1B52` (nejčastější barvy v `files.cltk.cz/site/style.css`) |

**Barevná DNA klubu z podkladů** (zdroje v `05` a `06`):
- znak: štít modrá / červená s černým lemem, zelené listy,
- Revue: 20 let stejná hlavička, zlatá + navy na bílé,
- antuka a Vltava (vlastnosti místa).

**Tři návrhy palet** (moje návrhy; kontrast spočten podle WCAG):
1. **„Bílá luxusní“ (DNA Prague Open 03):**
   - papír `#FBFAF6` / `#F3F0E9`, text `#1A2233` (kontrast 15,2 : 1), navy `#152744` (14,3 : 1), linky `#E4E0D6`,
   - **zlatá `#B3935A` má na papíře jen 2,78 : 1** → jen na linky, ornamenty a velká čísla na navy (na `#152744` je 5,15 : 1),
   - zlatý **text** na papíře tmavší `#8A6D3B` (4,64 : 1 = AA),
   - šedý popis `#7B8092` má 3,76 : 1 (nesplňuje AA pro malý text) → pro text `#6B6F7E` (4,79 : 1).
2. **„Antuka a len“:**
   - len `#F6F1EA` (blízko Amanu), text `#23201C` (14,4 : 1),
   - antuková `#9B4A2A` (5,49 : 1, splní AA i pro text), světlá antuka `#E9CDBB` na plochy,
   - bílé „lajny“ `#FFFFFF` 2 px jako oddělovače, olivově zelená `#5E6B4E` (listy ze znaku, topoly) jako druhý akcent.
3. **„Vltava a porcelán“:**
   - porcelán `#F7F8F6`, hluboká `#1F3440` (12,1 : 1), říční šedozelená `#4E6B72` (5,37 : 1, pro text) nebo `#5F7F86` (4,05 : 1, jen velký text),
   - červená ze znaku `#A3242B` (6,93 : 1) jen jako drobný akcent (tečka, štítek), zlatá jako v paletě 1.

**Pravidla (moje doporučení):**
- žádná čistě bílá `#FFFFFF` jako pozadí stránky (jen karty),
- žádná čistě černá (text v teplé navy nebo inkoustu),
- max. 1 akcent na obrazovku,
- fotky sjednotit teplou gradací a lehce snížit sytost (antuka nesmí „křičet“ oranžovou),
- tmavé bloky (navy) jen jako 1–2 rytmické předěly na stránku.

---

## 5. Návrh, jak nápady rozložit do tří variant (pro orchestrátora; moje doporučení)

| Varianta | Pracovní název | Paleta / písma | Nosné prvky z kap. 2 |
|---|---|---|---|
| 1 – luxusní | **„Bílá luxusní / Ostrov“** | paleta 1, dvojice A (Cormorant Garamond + Jost) | 1 triptych, 2 zlatá deska, 6 síň slávy, 13 magazín Revue, 19 konfigurátor členství, 24 certifikát |
| 2 – neobvyklý | **„Archiv a mapa“** (muzeum na ostrově) | paleta 3, dvojice C nebo D (Newsreader / Bodoni Moda + Geist Mono) | 3 kronika se 4 jmény, 4 ostrov v čase, 5 stěna es, 9 vitrína, 10 hloubkový zoom, 11 tehdy a teď, 14 kiosek 20 let, 20 perokresby |
| 3 – skvělý | **„Antuka a život klubu“** | paleta 2, dvojice B nebo E (EB Garamond + Manrope / Fraunces + Figtree) | 17 dnes na Štvanici, 18 ciferník sezóny, 8 mapa CTC, 21 scoreboard, 22 stopa míčku, 12 Tendiv, 23 sezónní kůže, 16 dopis z ostrova |

Ve všech variantách: 25 přístupnost, 26 CS/EN, 7 rodokmen stoletých (menší prvek).

---

## 6. Mezery a NEOVĚŘENO
- **Hermès:** vizuál nevidět (bot ochrana po načtení), popis jen z DOM.
- **Loro Piana:** 403 a timeout, nahrazeno Brunellem Cucinellim.
- **AELTC (aeltc.com):** prázdná stránka.
- **Rolex tenis** (/world-of-rolex/tennis): 404.
- **Rijksmuseum:** prohlížeč Noční hlídky jsem neotevřel, fakta jen z tiskové zprávy.
- **TC Brno, TK Slavia Praha (tenis), „LTC Prostějov“:** oficiální web nenalezen. „TK Neride Prostějov“ je ve skutečnosti TK Neridé v Praze-Hostivaři.
- **Rozpory ITHF × cltk.cz:** počet titulů Kodeše (11 proti 8 ve dvouhře), rok britského občanství Drobného (1959 proti 1960).
- **Skóre finále Wimbledonu 2023 (Vondroušová):** v podkladech chybí, doplnit ze zdroje.
- **Seznam 12 mistrovských titulů z let 1956–1973:** chybí (viz `06` kap. 7).
- **Tradice „sezona začíná na Josefa“:** jen z jednoho pramene (Bečka), ověřit s klubem.
- **API widgetu Resultina, rezervační systém, stav kurtů:** zda a jak je napojit, neověřeno.
- **Podpora prohlížečů:** podle caniuse (GitHub Fyrd) a MDN BCD 8.1.2 (17. 9. 2026). Scroll-driven animace nemají ve Firefoxu stabilní podporu → jen jako progresivní vylepšení.
- **Obrazová data pro nápady 9–11** (předměty, společná fotka 130 let ve vysokém rozlišení, dnešní fotky „tehdy a teď“) je nutné vyžádat od klubu.

## 7. Soubory z této rešerše (vše v `podklady/_raw/inspirace/`, mimo git)
- `web/*.png` a `web/*.json`: snímky obrazovek (1440 × 900) a data z DOM, 51 záznamů (u několika jen chybová nebo blokovaná stránka: aeltc, loropiana, hermes vizuálně, rolex-tennis, rijks-nightwatch, ktc).
- `montaze/*.jpg`: montáže 2 × N obrazovek pro rychlé prohlížení.
- `html/*.html`, `html/cltk-style.css`, `html/ithf-*.html`: stažené HTML (odkazy, profily ITHF, CSS dnešního webu).
- `gf-metadata.json`, `gf-subsets.txt`: metadata Google Fonts (subsety, řezy, osy).
- `fonty-kontrola.txt`: kontrola českých glyfů a OpenType funkcí v 95 písmech.
- `fonty/specimen.html`, `specimen.png`, `spec-1…6.png`, `tenor.png`: vizuální render české ukázky.
- `caniuse/*.json`: data o podpoře CSS/JS funkcí v prohlížečích.
