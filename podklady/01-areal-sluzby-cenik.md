# 01 – Areál, služby, ceník, kontakty, partneři, galerie

Podklad pro redesign www.cltk.cz (I. Český Lawn-Tennis Klub Praha). Vše přečteno 23. 9. 2026 přímo ze stávajícího webu (curl), z odkazovaných PDF a z klubové fotogalerie cltk.dphoto.com. U každého údaje je zdroj. Co jsem nemohl ověřit, je označeno **NEOVĚŘENO**. Čísla, ceny a data jsou opsané přesně.

Surová data (HTML, PDF, originály fotek, JSON z fotogalerie) leží v `podklady/_raw/areal/` (git-ignored).

Výstupní soubory tohoto kroku:
- `podklady/01-areal-sluzby-cenik.md` (tento soubor)
- `podklady/data/fotky.json` – 44 fotek + plán areálu (popis, rozměr, zdroj, použití)
- `podklady/data/partneri.json` – 36 záznamů = 34 partnerů (31 ze stránky Partneři + 3 jen z pásu log na homepage) + 2 alternativní varianty loga (tmavý podklad)
- `assets/foto/*.jpg` (44 fotek + `plan-arealu.jpg`), `assets/partneri/*` (36 log)

---

## 1. Základní identita a kontakt (rychlý přehled)

| Údaj | Hodnota | Zdroj |
|---|---|---|
| Název (hlavička webu) | I. Český Lawn - Tennis Klub Praha | https://cltk.cz/cs/ (h1 v hlavičce) |
| Název (ceník členství) | I. Český Lawn-Tennis Klub Praha | https://cltk.cz/cs/podminky-clenstvi/ |
| Zkratka | I. ČLTK Praha / I.ČLTK Praha | celý web |
| Adresa | Štvanice 38, 170 00 Praha 7 (na stránce členství: „Ostrov Štvanice 38, 170 00 Praha 7“) | https://cltk.cz/cs/kontakty/ , https://cltk.cz/cs/podminky-clenstvi/ |
| Telefon recepce (rezervace kurtů) | 608 974 974 (v patičce „+420 608 974 974“) | https://cltk.cz/cs/kontakty/ , homepage |
| E-mail recepce | recepce@cltk.cz | homepage – blok „Rychlý kontakt“ |
| IČ | 45243077 | PDF provozní řády bazénu/posilovny/wellness |
| Telefon v provozních řádech (PDF) | 222 316 317 – starší číslo? **NEOVĚŘENO**, zda platí | PDF provozní řády |
| Facebook | https://www.facebook.com/cltk.fb | hlavička webu, https://cltk.cz/cs/o-nas/49/ |
| Instagram | https://www.instagram.com/cltk.insta/ | hlavička webu |
| Online rezervace | https://www.rogeronline.cz/v2/index.php?klub=181 (odkaz „rezervace“ v hlavičce) | homepage |
| Obsazenost kurtů | https://onlinehq.cz/r/courtst.php?klub=181 | https://cltk.cz/cs/co-nabizime/tenisove-kurty/ |
| Jazykové verze | cs + en (`/cs/`, `/en/`, hreflang) | homepage |
| CMS / autor stávajícího webu | MySuitu CMS, SUITU websites SE, © 2026 | patička |
| Členství v asociaci | Centenary Tennis Clubs (logo „CTC“ v patičce; menu Klub › CTC) | patička, menu |

---

## 2. Homepage https://cltk.cz/cs/ – co je kde

**Hlavička (horní lišta):** vlevo textový název „I. Český Lawn - Tennis Klub Praha“, uprostřed logo (`//files.cltk.cz/site/logo.png`), vpravo ikona telefonu + „608 974 974“, ikona + odkaz **„rezervace“** → RogerOnline (nové okno), ikony Facebook a Instagram, přepínač jazyků cs/en, hamburger (off-canvas menu).

**Hlavní menu (kompletní strom):**
- O nás → Plán areálu · Galerie · Partneři · Plánované uzavírky · Facebook I.ČLTK Praha
- Co nabízíme → Pronájem tenisových kurtů · Výuka tenisu · Beachvolejbalové a multifunkční hřiště · Fitness centrum · Venkovní bazén · Wellness centrum · Fyzioterapie a masáže · Tenis shop · Firemní akce
- Klub → O klubu · Členství v klubu · Klubové turnaje a akce · Klubový časopis - I.ČLTK Revue · Klubový newsletter · Výkonný výbor · Zasloužilí členové · CTC
- Ceník
- Závodní tenis → Trenéři
- Tenisová školička → Informace · Ceník · Rozvrhy · Trenéři · Družstva · Turnaje · Letní kempy
- Kontakty

**Hero slider** (6 snímků, jQuery ResponsiveSlides; desktop verze / mobilní varianta s příponou „m“). Všechny alt = „Sample pic“:

| # | Soubor (desktop) | Rozměr | Obsah | Uloženo jako |
|---|---|---|---|---|
| 1 | //files.cltk.cz/site/8.jpg | 1920×1210 | letecký pohled shora na letní areál (antuka, velký stadion, bazén) | `letecky-kurty-leto-shora.jpg` |
| 2 | //files.cltk.cz/site/1MS_1570_1.jpg | 1920×1210 | Markéta Vondroušová s wimbledonskou mísou (stejná fotka je v albu „Wimbledon 2023“, tam v 6412×4275) | `hracka-vondrousova-wimbledon-2023-trofej.jpg` (z originálu) |
| 3 | //files.cltk.cz/site/3MS_0145.jpg | 1920×1210 | hráčka v červeném dresu slaví na antuce – pravděpodobně Karolína Muchová, **NEOVĚŘENO** | `hracka-antuka-radost-hero.jpg` |
| 4 | //files.cltk.cz/site/9.jpg | 1920×1280 | letecký pohled na kurty a bazén | `letecky-kurty-bazen-leto.jpg` |
| 5 | //files.cltk.cz/site/2.jpg | 1500×1000 | venkovní bazén s lehátky | (nestaženo zvlášť – podobný záběr `bazen-travnik-lehatka.jpg`) |
| 6 | //files.cltk.cz/site/6.jpg | 2100×1400 | fitness centrum | (nestaženo zvlášť – podobný `fitness-centrum.jpg`) |

**Obsahové bloky pod sliderem (pořadí):**
1. Karta **„Pronájem tenisových kurtů“** – „Ceny, rezervace, informace“ → `/cs/co-nabizime/tenisove-kurty/`
2. Karta **„Členství v klubu I.ČLTK Praha“** – „Podmínky, Výhody, Ceny“ → `/cs/klub/clenstvi-v-klubu/`
3. **„Výsledky našich hráčů“** – iframe `//scorepresso.com/export/cltk_small.php?v=1` (widget „CLTK widget“ od Resultiny – živé výsledky hráčů klubu z turnajů ITF/ATP, např. 23. 9. 2026: „Plovdiv €97,640 - ATP Challenger – Forejtek def. Gima 64 46 63“). Pod ním odkaz na aplikaci Resultina (iPhone/Android).
4. **„I. ČLTK Revue“** – obálka `//files.cltk.cz/site/revue/revue-05-26.jpg` (alt „I.ČLTK Revue 05/2026“), tlačítko „Prohlédnout vydání“ → `/klub/klubovy-casopis-icltk-revue/`
5. Karta **„Kalendář událostí“** – „Akce konané v klubu“ → `/cs/klub/kalendar-udalosti/`
6. **„Aktuality“** (→ `/clanky`) – 4 poslední články (stav 23. 9. 2026): „Justýna Reifová vyhrála turnaj ITF J30“ (10. 9. 2026), „Starší žáci vezou z MČR družstev bronz“ (31. 8. 2026), „Denisa Žoldáková ve finále turnaje ITF W75 v Polsku“ (31. 8. 2026), „Karolína Muchová ovládla smíšenou čtyřhru na US Open“ + odkaz „Starší články >>>“
7. **Pás log partnerů** (30 log, `//files.cltk.cz/site/...`) – viz sekce 13
8. **„Rychlý kontakt“**: Štvanice 38, 170 00 Praha 7 · +420 608 974 974 · recepce@cltk.cz · tlačítko „Další kontakty“
9. **Patička**: logo klubu + logo „Centenary Tennis Clubs“ (`CTC_k_white.png`), „Vytvořeno v SUITU websites SE • Běží na MySuitu CMS • © 2026“, opakované menu.

Pozn.: na homepage je prvek `notification-wrapper` (prázdný) – místo pro provozní oznámení.

---

## 3. Poloha a příjezd (https://cltk.cz/cs/o-nas/plan-arealu/)

Citace ze stránky:
> „Tenisový areál I.ČLTK Praha se nachází na ostrově Štvanice s možným přístupem z Hlávkova mostu nebo z Karlína i Holešovic přes nově vybudovanou lávku Holka.
> Na Hlávkově mostě je zastávka tramvaje č.14 a stanice metra B i C jsou vzdálené jen několik minut chůze. Brána pro pěší vstup se nachází přímo u příchodu z mostu.
> Pro příjezd autem je lepší po sjezdu z magistrály areál zprava objet, zaparkovat na vyhrazeném parkovišti označeném na plánku a do areálu vejít zadním vchodem.“

- **Parkování:** vyhrazené parkoviště u Negrelliho viaduktu (na plánu ikona „PARKOVIŠTĚ“ + vstup). Stránka uzavírek zmiňuje „vnitřní parkoviště a oblouky viaduktu“. Kapacita parkoviště **NEOVĚŘENO** (na webu neuvedena).
- **MHD:** tramvaj č. 14 (zastávka na Hlávkově mostě), metro B i C „několik minut chůze“ (konkrétní stanice web neuvádí).

---

## 4. Tenisové kurty (https://cltk.cz/cs/co-nabizime/tenisove-kurty/)

„V tenisovém areálu Štvanice se nachází celkem **19 tenisových kurtů**.“

| Kurt | Léto (povrch, popis) | Zima | Poznámka |
|---|---|---|---|
| **1** | „malý centr, kapacita cca 1000 míst“ – antuka | přetlaková hala s pevným povrchem | na plánu obklopen tribunami, u hlavní budovy |
| **2, 3, 4** | „trojkurt s malými tribunami umístěný mírně pod úrovní povrchu“ – antuka | přetlaková trojhala s pevným povrchem | jediné kurty s večerním svícením (150 Kč/hod, viz ceník) |
| **5, 6** | antuka | přetlaková dvojhala s antukou | kurt č. 5 je „vyhrazen pouze pro členy klubu“ (PDF Pravidla 2026) |
| **7, 8** | pevný povrch | přetlaková dvojhala s pevným povrchem | |
| **9** | pevný povrch | přetlaková hala s pevným povrchem | |
| **10–16** („Slavoj“) | „umístěné v zadní části areálu za Negrelliho viaduktem tzv. "Slavoj"“ – antuka | v zimní sezoně uzavřeno | |
| **C** – velký centrální dvorec | „kapacita 8000 míst“ – pevný povrch | přetlaková hala s pevným povrchem | v rezervačním systému „Center - Nova Sport“ |
| **P1, P2** | „Pevná hala pod ochozem velkého centru – dva kurty s pevným povrchem“ | celoročně (hala) | v zimním ceníku „Pevná hala Novasport – tvrdý povrch (kurty P1,P2)“ |

Souhrny z jiných stránek (souhlasí s tabulkou):
- Stránka podmínek členství: „v letní sezoně **13 venkovních antukových kurtů, z toho 3 s umělým osvětlením a 3 kurty s tvrdým povrchem**“ (antuka 1, 2–4, 5–6, 10–16 = 13; tvrdé 7–9 = 3; osvětlené = 2, 3, 4).
- „v zimě k dispozici **12 krytých kurtů (10 s umělým povrchem a 2 antukové)**“ (C, 1, 2, 3, 4, 7, 8, 9 + P1, P2 = 10 tvrdých; 5, 6 = 2 antukové).
- Rezervace kurtů: „Kurty se rezervují přes recepci, tel.č. 608 974 974“, obsazenost online (onlinehq). Pravidla hraní: PDF https://files.cltk.cz/7km6d916kbl01/Pravidla%20pro%20rezervace%20kurt%C5%AF%202026.pdf
- Skupiny kurtů v RogerOnline: „Kurt C, P1, P2, 7-9“ · „Kurt 1-6“ · „Slavoj 10-14“ · „Slavoj 15-16, Hřiště, Beach“ (+ v onlinehq „Kondice Fyzio Posilovna Masáž“). Časová mřížka rezervací 06:00–23:30 (to je mřížka systému, ne oficiální otevírací doba). 23. 9. 2026 byl kurt „Center“ blokován celý den s poznámkou „Blokace: STAVBA HALY“ (stavba zimní haly).

**Pravidla hraní (PDF „Pravidla hraní na tenisových kurtech“ 2026) – hlavní body:**
- Začátek zimní i letní sezóny stanovuje management podle počasí a stavu kurtů.
- V létě hrají členové zdarma; v zimě se hala platí (trvalá rezervace nebo jednotlivé hodiny za zvýhodněnou cenu).
- Bez aktivního členství nelze udělat bezplatnou „členskou“ rezervaci – lze rezervovat v režimu „prodej“ a zaplatit.
- V létě nejsou trvalé rezervace (výjimka: kluboví privátní trenéři, schvaluje management; neplatí pro kurt č. 5).
- Členská rezervace = min. dvě jména v systému.
- Hrací jednotka zahrnuje úpravu dvorce (hrablo, síťovačka).
- Člen s hrajícím členstvím: rezervace zdarma max. týden dopředu, max. 1,5 hod. denně v jedné rezervaci, nanejvýš dvě platné rezervace.
- **Kurt č. 5 je vyhrazen pouze pro členy klubu** (člen + člen / člen + host).

---

## 5. Plán areálu (podklad pro interaktivní mapu)

- Obrázek: `assets/foto/plan-arealu.jpg` (7033×4143 px, převedeno z CMYK do RGB, verze „09-2025“). Zdroj: https://files.cltk.cz/funkmfz6aqi01/Pl%C3%A1n%20are%C3%A1lu%20%2009-2025%20na%20web.jpg ; PDF: https://files.cltk.cz/l8wemjw6xri01/Pl%C3%A1n%20are%C3%A1lu%20%2009-2025%20na%20web.pdf (PDF obsahuje jen tentýž rastr, žádné vektory ani text).
- Styl plánu: plochá infografika – Vltava světle modrá (nahoře a dole), zeleň jasně zelená s tmavě zelenými kruhy stromů, komunikace světle šedé, zázemí tmavě šedé. **Antukové kurty oranžovočervené, tvrdé kurty modré**, tribuny zelenošedé pruhy. Tmavé piktogramy s popiskem, růžové šipky „VSTUP“. Severka na plánu není.

**Rozložení (zleva doprava):**
1. **Levá část – „SLAVOJ“** (šipka s popiskem „SLAVOJ“): řada 7 antukových kurtů **16, 15, 14, 13, 12, 11, 10** (ve dvojicích 16–15, 14–13, 12–11 a samostatný 10); pod nimi **MULTIFUNKČNÍ HŘIŠTĚ** (zelené, fotbalové čáry) a **HŘIŠTĚ NA BEACH VOLEJBAL** (žluté).
2. **NEGRELLIHO VIADUKT** – svislý pás (železnice + silnice) dělí Slavoj od hlavního areálu. U viaduktu **PARKOVIŠTĚ** (ikona auta) a **VSTUP** (zadní vchod od parkoviště); podél silnice čárky parkovacích stání.
3. **Hlavní budova / zázemí** (tmavě šedá plocha): uprostřed **kurt 1** (antuka s tribunami). Kolem piktogramy: **FITNESS 2.NP**, **REGENERACE 2.NP**, **SPORTOVNÍ LÉKAŘSTVÍ**, **ŠATNY D2, M1, M2, M3**, **MANAGEMENT 2.NP**, **KADEŘNICTVÍ**, **ŠATNA D1**, **RECEPCE**. Níže **ŠATNY 58-59**, **TENIS SHOP**, **SALÓNEK A DĚTSKÝ KOUTEK**, **RESTAURACE S TERASOU**. Dole **PEVNÁ HALA** (P1, P2 – modré) a vedle **velký centrální dvorec „C“** (modrý, s tribunami), u něj **KANCELÁŘE ČTS** (Český tenisový svaz).
4. **Pravá část:** **BAZÉN** (na zeleném trávníku), kurty **5, 6** (antuka), **7, 8** a **9** (modré, tvrdé); pod nimi **trojkurt 2, 3, 4** (antuka, tribuny po stranách), pod ním **ODRAZOVÁ STĚNA** (modrá) a malé **MULTIFUNKČNÍ HŘIŠTĚ** (zelené).
5. **Vstupy (růžové šipky „VSTUP“):** u parkoviště za viaduktem (západní strana plánu), mezi hlavní budovou a bazénem (vede k recepci), na pravém okraji a vpravo dole u odrazové stěny. Který je „brána pro pěší u příchodu z mostu“ – **NEOVĚŘENO** (plán to neuvádí).

**Přibližné souřadnice prvků v % šířky/výšky obrázku** (odhad z náhledu 1800×1060, levý horní roh = 0/0; vhodné pro první verzi klikací mapy, doladit na originále):

| Prvek | x od–do (%) | y od–do (%) | střed (x, y) |
|---|---|---|---|
| Kurty 16 / 15 | 1,2–10,3 | 26,9–41,5 | 16: (3,6; 34) · 15: (8,1; 34) |
| Kurty 14 / 13 | 10,7–19,7 | 26,9–41,5 | 14: (13,1; 34) · 13: (17,4; 34) |
| Kurty 12 / 11 | 20,1–29,2 | 26,9–41,5 | 12: (22,5; 34) · 11: (26,8; 34) |
| Kurt 10 | 29,5–34,2 | 26,9–41,5 | (31,8; 34) |
| Multifunkční hřiště (Slavoj) | 16,5–24,9 | 44,1–52,3 | (20,7; 48,2) |
| Beach volejbal | 25,7–34,2 | 44,1–52,3 | (30,0; 48,2) |
| Negrelliho viadukt | 35,0–41,7 | 0–100 | – |
| Parkoviště + vstup | – | – | parkoviště (39,2; 54,2), vstup (42,1; 54,4) |
| Kurt 1 (s tribunami) | 46,9–54,4 | 29,2–44,8 | (50,7; 37,0) |
| Fitness 2.NP / Regenerace 2.NP | – | – | (49,2; 25,5) / (52,8; 25,5) |
| Sportovní lékařství / Šatny D2,M1–M3 / Management 2.NP | – | – | (56,6; 28,8) / (56,6; 35,4) / (56,6; 42,0) |
| Kadeřnictví / Šatna D1 | – | – | (44,4; 35,4) / (44,4; 42,0) |
| Recepce | – | – | (53,1; 48,6) |
| Vstup k recepci | – | – | (60,8; 50,0) |
| Šatny 58-59 / Tenis shop / Salónek a dětský koutek / Restaurace s terasou | – | – | (47,1; 59,2) / (53,3; 59,2) / (56,9; 59,2) / (60,4; 59,2) |
| Pevná hala (P1, P2) | 42,2–51,1 | 62,7–79,7 | P1 (47,6; 68,7) · P2 (47,6; 74,5) |
| Velký centrální dvorec C | 51,2–58,9 | 63,0–83,5 | (55,1; 73,3) |
| Kanceláře ČTS | – | – | (61,4; 74,1) |
| Bazén | 59,2–65,7 | 27,8–42,2 | (62,5; 35,0) |
| Kurty 5 / 6 | 66,1–74,7 | 27,8–42,2 | 5: (68,3; 35) · 6: (72,5; 35) |
| Kurty 7 / 8 | 75,1–83,7 | 27,8–42,2 | 7: (77,4; 35) · 8: (81,4; 35) |
| Kurt 9 | 84,1–88,9 | 27,8–42,2 | (86,4; 35) |
| Trojkurt 2 / 3 / 4 (s tribunami) | 67,7–83,5 | 47,2–60,4 | 2: (71,6; 53) · 3: (75,4; 53) · 4: (79,3; 53) |
| Odrazová stěna | 69,6–72,2 | 60,8–68,9 | (70,9; 64,9) |
| Multifunkční hřiště (malé) | 72,5–77,3 | 60,8–65,4 | (74,9; 63,1) |
| Vstup vpravo / vpravo dole | – | – | (92,2; 45,8) / (74,4; 70,6) |

---

## 6. Služby (sekce „Co nabízíme“)

### 6.1 Výuka tenisu – https://cltk.cz/cs/co-nabizime/vyuka-tenisu/
„V klubu I.ČLTK Praha probíhá výuka tenisu na všech výkonnostních úrovních.“
- Výuka dětí na závodní i rekreační úrovni (do 10 let) → Tenisová školička
- Tréninky závodních a výkonnostních hráčů → Závodní tenis
- Tréninky a výuka rekreačních hráčů → níže
- Ze stránky členství: „výuka tenisu - privátní trenéři, **Tenisová škola Markéty Vondroušové**, dětské letní kempy“.

„V klubu působí **5 trenérů**, kteří primárně poskytují privátní hodiny rekreačním hráčům.“

| Trenér | Rok nar. | Jazyky | Praxe | Info | Telefon | E-mail |
|---|---|---|---|---|---|---|
| Ondřej Macek | 1971 | čeština, angličtina, němčina, ruština | 30 let | – | 776 587 683 | tenis.troja@seznam.cz |
| Jan Pavlíček | 1986 | čeština, angličtina, němčina | 15 let | bývalý ligový hráč | 603 874 063 | j.pavlicek86@gmail.com |
| Roman Bicek | 1986 | čeština, angličtina | (neuvedeno) | bývalý ligový hráč | 602 403 280 | bicekroman@seznam.cz |
| Jan Kurz | 1944 | čeština, angličtina, němčina, ruština | přes 50 let | bývalý ligový hráč | 603 409 471 | jan.kurz@seznam.cz |
| Jiří Hřebec | 1950 | čeština, angličtina, němčina | 45 let | bývalý československý reprezentant, top ATP ranking 25, bývalý trenér Davis Cupu | 605 257 151 | jiri.hrebec@seznam.cz |

### 6.2 Beachvolejbalové a multifunkční hřiště – https://cltk.cz/cs/co-nabizime/beachvolejbalove-a-multifunkcni-hriste/
- „Obě tato hřiště se nachází v zadní části areálu za Negrelliho viaduktem“ (Slavoj). Na plánu je navíc malé multifunkční hřiště u trojkurtu 2–4 – nesoulad, viz sekce 16.
- Rezervace přes recepci, lze pak využít šatny a sprchy v hlavní budově.
- Ceník: multifunkční hřiště **900,- / hod**, beachvolleybalové hřiště **400,- / hod**.
- „Pokud hřiště využijí pouze členové klubu, mají vstup zcela zdarma.“ „Přivede-li si člen hosty a budou využívat hřiště společně, cena hodinového pronájmu je o 25% nižší.“
- „Beachvolleybalové hřiště mohou přes svůj rezervační systém využívat i členové spolku "Vlny Štvanice".“ Dostupnost je proto třeba ověřit telefonicky na recepci.

### 6.3 Fitness centrum – https://cltk.cz/cs/co-nabizime/posilovna/
- „Nové prostory pro cvičení byly otevřeny díky **I. etapě nástavby na ochozu malého centrálního dvorce dokončené v roce 2017**.“ Slouží závodním i rekreačním hráčům.
- Členové: vstup zdarma „jen s mírným časovým omezením během klubové kondice závodních hráčů“.
- Host člena: jednorázový vstup **400 Kč**.
- Provozní řád (PDF, účinnost 20.6.2017): otevírací doba = otevírací doba budovy; vstup jen členům nebo hostům po zaplacení 400 Kč na recepci; děti do 12 let jen s dospělým; zákaz vstupu v tenisové obuvi od antuky; kamerový systém. Na plánu: FITNESS 2.NP.

### 6.4 Venkovní bazén – https://cltk.cz/cs/co-nabizime/venkovni-bazen/
- „nedílnou součásti štvanického areálu již **více než 20 let**“; zdarma polohovatelná lehátka, ručníky za poplatek na recepci, občerstvení z klubové restaurace „v těsné blízkosti“.
- „Takto blízko centra Prahy naleznete jen velmi málo podobně příjemných a klidných míst…“
- Členové zdarma; každý člen může pozvat **jednoho hosta za 400 Kč** (dle provozního řádu 400,- Kč/1 den bez ohledu na čas; dítě do 2 let zdarma; v rodinném členství může nehrající rodič bazén zdarma).
- Provoz „přibližně **od května do září**“ (dle počasí).
- Provozní řád: jen pro členy a jejich hosty; do 12 let jen s rodiči; zákaz skákání, skla; koupání bez plavčíka na vlastní riziko.

### 6.5 Wellness centrum – https://cltk.cz/cs/co-nabizime/wellness-centrum/
- Otevřeno v **březnu 2019** „díky II. etapě nástavby na ochozu malého centrálního dvorce“.
- Vybavení: **vířivá vana Jacuzzi, sauna s odpočívárnou, infrasauna, Kneippovy lázně, ice bath** (jen pro závodní hráče), prostory pro masáže a fyzioterapii.
- **Otevírací doba: ve všední dny 16:00–20:00**, bez rezervace. Sauna se v těchto hodinách roztápí automaticky; v období **květen–září** je nutné návštěvu sauny nahlásit **min. hodinu předem** (roztápí se na vyžádání).
- Vedoucí wellness a odpovědná osoba za provoz od 1.6.2024: **Joe Truesdale (+420 704 737 187)**.
- Jen pro členy I. ČLTK, vstup zdarma; kromě závodních hráčů **max. 2× týdně**.
- Pravidla (výběr): nahlásit se na recepci (vstupní čip), klíč od skříňky z recepce, osprchovat se, do vířivky v plavkách, do sauny zakrytí (společný prostor mužů a žen), ice bath nepoužívat po sauně. Zvláštní režim ověřit v klubovém rezervačním systému (odkaz `rs.cltk.cz/online/fyzio.php…`). Provozní řád PDF (účinnost 1.2.2019). Na plánu: REGENERACE 2.NP.

### 6.6 Fyzioterapie a masáže – https://cltk.cz/cs/co-nabizime/fyzioterapie-a-masaze/
- Prostory ve wellness centru (otevřeno 2019), pro závodní hráče, rekreační členy „i širokou veřejnost“.
- **Fyzioterapie:** Bc. Joseph B. Truesdale, tel. 704 737 187, e-mail leftfld14@hotmail.com
- **Sportovní lékařství, masáže, fyzioterapie:** „Zdravý sport“ – web https://www.centrum-inmotion.cz/ (na plánu SPORTOVNÍ LÉKAŘSTVÍ).

### 6.7 Tenis shop – https://cltk.cz/cs/co-nabizime/tenis-shop/
- „Od **listopadu 2023** je ve vestibulu otevřen nový tenisový obchod“ – vyplétání raket, prodej zboží značek **Mizuno, Babolat** a další, klubový merchandising I. ČLTK.
- Kontaktní osoba: **Tomáš Truneček**.
- Otevírací doba:

| Den | Dopoledne | Odpoledne |
|---|---|---|
| PO | 9:00 - 12:00 | 13:00 - 17:00 |
| ÚT | 9:00 - 12:00 | 13:00 - 17:00 |
| ST | 9:00 - 12:00 | 13:00 - 17:00 |
| ČT | 9:00 - 12:00 | 13:00 - 17:00 |
| PÁ | 9:00 - 12:00 | 13:00 - 17:00 |

### 6.8 Firemní akce – https://cltk.cz/cs/co-nabizime/firemni-akce/
„I. ČLTK Praha disponuje širokou nabídkou služeb a je ideálním místem pro uspořádání vaší firemní akce, tenisového turnaje či oslavy narozenin.“ Nabídka: dostatek kurtů; kvalitní zázemí šaten i společenských prostorů; **restaurace s venkovní terasou a s nabídkou kvalitních jídel i rautů**; doplňkově multifunkční/beachvolejbalové hřiště, **odrazová zeď**, venkovní bazén, fitness a wellness, masáže/fyzioterapie, trenéři pro rekreační hráče i děti. Kontakt/ceny na stránce **nejsou** – jen „Neváhejte nás kontaktovat“.

### 6.9 Restaurace a další zázemí (z různých stránek)
- **Restaurace Tiebreak s venkovní terasou** (podminky-clenstvi; galerie „Restaurace Tiebreak“; na plánu RESTAURACE S TERASOU). Samostatná stránka restaurace, otevírací doba ani kontakt na webu **nejsou** – **NEOVĚŘENO**.
- **Dětské hřiště** v blízkosti terasy restaurace a **dětský koutek** v předsálí restaurace; **klubová místnost** „pro možnost odpočinku, studia či pracovních schůzek“ (podminky-clenstvi).
- Na plánu dále: **KADEŘNICTVÍ**, **KANCELÁŘE ČTS**, šatny D1, D2, M1, M2, M3 a 58-59, MANAGEMENT 2.NP.
- Pořádání akcí (podminky-clenstvi): „mezinárodní turnaje ITF a ATP, celostátní turnaje všech věkových kategorií, firemní turnaje“; klubové akce: „celosezónní soutěž pro rekreační hráče, klubové dny, mikulášská besídka pro děti, vánoční party, golfový turnaj apod.“

---

## 7. Otevírací doby – co web uvádí

| Co | Doba | Zdroj |
|---|---|---|
| Wellness | všední dny 16:00–20:00 | wellness-centrum |
| Tenis shop | Po–Pá 9:00–12:00 a 13:00–17:00 | tenis-shop |
| Kancelář klubu (sekretářka E. Štefková, platby hotově) | všední dny 9:00 – 17:00 | podminky-clenstvi |
| Bazén | sezónně cca květen–září | venkovni-bazen |
| Letní sezóna / zimní halová sezóna 2026/27 | zima: 5.10.26 – 4.4.27 (přetlakové haly, 26 týdnů), 28.9.26 – 4.4.27 (pevná hala P1, P2, 27 týdnů) | PDF zimní ceník |
| Areál, recepce, restaurace | **na webu neuvedeno – NEOVĚŘENO** | – |

---

## 8. Plánované uzavírky – https://cltk.cz/cs/o-nas/planovane-uzavirky/

„V tabulce níže naleznete případné plánované uzavírky kurtů či jiných částí areálu. Za případné komplikace spojené s těmito uzavírkami se předem omlouváme.“

| Termín | Důvod |
|---|---|
| 20.-26.7.2026 | Livesport Prague Open |
| 1.-2.8.2026 | Oktagon 92 - od 14:00 uzavřeny šatny a fitness, od 10:00 uzavřeno vnitřní parkoviště a oblouky viaduktu. Vše opět přístupné 2.8. od 10:00 |
| 16.-22.8.2026 | Sekyra Group Prague Open 2026 |

(Pozn.: v areálu tedy běží dva profesionální turnaje „Prague Open“ – Livesport Prague Open (kategorie na webu neuvedena – **NEOVĚŘENO**) a Sekyra Group Prague Open 2026 by Advantage Cars = „ATP Challenger 75 & ITF World Tennis Tour W50“ dle alba ve fotogalerii.)

---

## 9. Ceník – https://cltk.cz/cs/cenik/ (kompletně)

Struktura stránky: dva odkazy nahoře („Ceník členství v klubu pro rok 2026 - zde“ → `/cs/podminky-clenstvi/`; „Ceník pronájmu tenisových kurtů v zimní sezoně 2026/2027 v halách - zde“ → PDF), pravidla, letní ceník, ceník doplňkových služeb.

### 9.1 Pravidla k zimním/trvalým rezervacím (text ze stránky Ceník)
- „Trvalé rezervace je nutné zrušit telefonicky nejpozději 24 hod předem.“
- „Náhrady za včas zrušené hodiny je možné čerpat v období maximálně 4 týdnů.“
- „Management klubu si také vyhrazuje možnost přesunu hodin v halách na základě ekonomičtějšího využití vícekurtových hal. Je tím míněn pouze přesun pronajaté hodiny ve stejném čase a na stejném povrchu. Děkujeme za pochopení.“

### 9.2 Ceník pronájmu tenisových kurtů v letní sezoně 2026

| Položka | Cena |
|---|---|
| Kurty v hlavním areálu (č. 1-9) po celý den vč. víkendů | 500 Kč/hod |
| Kurty na "Slavoji" (č.10-16) po celý den vč. víkendů | 400 Kč/hod |
| Kurty v pevné hale (P1, P2) po celý den vč. víkendů | 600 Kč/hod, (500 Kč/hod pro členy klubu.) |
| Večerní svícení na kurtech 2,3,4 | 150 Kč/hod |

„(Členové klubu mají přístup na venkovní kurty v letní sezoně zdarma!)“

### 9.3 Ceník doplňkových služeb štvanického areálu

| Položka | Cena |
|---|---|
| Pronájem multifunkčního hřiště | 900 Kč/hod |
| Pronájem beachvolejbalového hřiště | 400 Kč/hod |
| Jednorázový vstup do fitness pro hosty členů | 400 Kč |
| Vstup k venkovnímu bazénu pro hosta člena klubu | 400 Kč |

### 9.4 Ceník zimní halové sezóny 2026/2027 (PDF https://files.cltk.cz/9lawqgo4cjn01/Cen%C3%ADk%20zimn%C3%AD%20sezona%2026-27.pdf , 1 strana)

**Přetlaková hala - antuka (kurty 5,6)** – Hrací období 5.10.26 - 4.4.27 (26 týdnů)

| Hrací čas | Jednotlivé hodiny – veřejnost | Jednotlivé hodiny – členové I.ČLTK | Předplatné na celé hrací období – veřejnost | Předplatné – členové I.ČLTK |
|---|---|---|---|---|
| všední den 7:00 - 14:00 | 540 Kč | 430 Kč | 12 480 Kč (480,-/hod) | 10 140 Kč (390,-/hod) |
| všední den 14:00 - 21:00 | 730 Kč | 590 Kč | 17 160 Kč (660,-/hod) | 13 780 Kč (530,-/hod) |
| víkend 8:00 - 21:00 | 450 Kč | 390 Kč | 10 660 Kč (410,-/hod) | 8 580 Kč (330,-/hod) |

**Přetlaková hala - tvrdý povrch (kurty C,1,2,3,4,7,8,9)** – Hrací období 5.10.26 - 4.4.27 (26 týdnů)

| Hrací čas | Jednotlivé hodiny – veřejnost | Jednotlivé hodiny – členové I.ČLTK | Předplatné – veřejnost | Předplatné – členové I.ČLTK |
|---|---|---|---|---|
| všední den 7:00 - 14:00 | 730 Kč | 590 Kč | 17 160 Kč (660,-/hod) | 13 780 Kč (530,-/hod) |
| všední den 14:00 - 22:00 | 860 Kč | 690 Kč | 20 280 Kč (780,-/hod) | 16 380 Kč (630,-/hod) |
| víkend 7:00 - 22:00 | 600 Kč | 490 Kč | 14 040 Kč (540,-/hod) | 11 440 Kč (440,-/hod) |

**Pevná hala Novasport - tvrdý povrch (kurty P1,P2)** – Hrací období 28.9.26 - 4.4.27 (27 týdnů)

| Hrací čas | Jednotlivé hodiny – veřejnost | Jednotlivé hodiny – členové I.ČLTK | Předplatné – veřejnost | Předplatné – členové I.ČLTK |
|---|---|---|---|---|
| všední den 7:00 - 22:00 | 890 Kč | 800 Kč | 21 870 Kč (810,-/hod) | 19 710 Kč (730,-/hod) |
| víkend 7:00 - 22:00 | 700 Kč | 630 Kč | 17 010 Kč (630,-/hod) | 15 120 Kč (560,-/hod) |

Poznámky v PDF:
- „* Zvýhodněná cena za předplatné na celé hrací období platí pouze při platbě předem a za celé období.“
- „** Klub si vyhrazuje právo přesunout rezervaci na jiný dvorec nebo do jiné haly, přičemž musí dodržet rezervovaný čas a typ povrchu.“

Barvy tabulek v PDF: antuka červeně, přetlaková hala tvrdý povrch zeleně, pevná hala tmavě modře.

### 9.5 Ceník členství 2026 – https://cltk.cz/cs/podminky-clenstvi/ (odkaz ze stránky Ceník)

*(Detailní rozbor členství patří spíš do podkladu „Klub/členství“; zde kompletní ceník, protože na něj odkazuje stránka Ceník.)*

**SLUŽBY V RÁMCI ČLENSTVÍ PRO ROK 2026**
- v letní sezoně 13 venkovních antukových kurtů, z toho 3 s umělým osvětlením a 3 kurty s tvrdým povrchem
- venkovní bazén s travnatou plochou k relaxaci
- fitness centrum
- regenerační linka s vířivkou, saunou, infrasaunou a odpočívárnou
- hřiště na plážový volejbal a multifunkční hřiště (zdarma pouze v případě využití členy klubu)
- pořádání a organizace sportovně společenských akcí (celosezónní soutěž pro rekreační hráče, klubové dny, mikulášská besídka pro děti, vánoční party, golfový turnaj apod.)
- veškeré informace o akcích a dění v klubu prostřednictvím e-mailu
- klubový časopis I.ČLTK Revue (2x ročně)
- klubový dvouměsíčník I.ČLTK Newsletter (i v angličtině)
- možnost zahrát si v klubech sdružených v Centenary Tennis Clubs

**ZVÝHODNĚNÉ PLACENÉ SLUŽBY PRO ČLENY KLUBU**
- v zimě k dispozici 12 krytých kurtů (10 s umělým povrchem a 2 antukové) – pro členy přednostní možnost rezervace kurtů
- zvýhodněná cena jednotlivých pronájmů i pronájmu celého zimního období pro hrající členy klubu
- sleva na klubový merchandising a další speciální akce pro členy v klubovém shopu
- pronájem venkovního dvorce pro hosta řádného člena klubu: 100 Kč/hod (řádný člen klubu 8x za rok, člen se zvýhodněným členstvím 4x za rok)
- jednorázový vstup pro hosta člena klubu do fitness nebo k bazénu: 400 Kč/den

**OSTATNÍ SLUŽBY**
- restaurace Tiebreak s venkovní terasou
- centrum sportovního lékařství – Zdravý sport (www.centrum-inmotion.cz)
- masérské a rehabilitační služby, fyzioterapie
- dětské hřiště v blízkosti terasy restaurace a dětský koutek v předsálí restaurace
- klubová místnost pro možnost odpočinku, studia či pracovních schůzek
- pořádání a organizace sportovních akcí (mezinárodní turnaje ITF a ATP, celostátní turnaje všech věkových kategorií, firemní turnaje)
- výuka tenisu - privátní trenéři, Tenisová škola Markéty Vondroušové, dětské letní kempy

**TYPY ČLENSTVÍ PRO ROK 2026**

Roční členství hrající:

| Typ | Cena |
|---|---|
| 1 dospělý hrající | 21 000 Kč |
| 1 dospělý hrající do 30 let | 15 500 Kč |
| seniorky od 67 let / senioři od 72 let (hraní v po-pá pouze do 14:00, so-ne bez omezení) | 10 000 Kč |
| mládež 4-18 let / studující do 26 let | 9 500 Kč |

„* Je určeno pro fyzické osoby a zajišťuje bezplatné využívání venkovních dvorců a všech klubových prostorů, kromě tenisových hal v zimním období. Hrající členové obdrží členské karty opravňující ke vstupu do prostorů šaten a fitness.“

Roční členství rodinné:

| Typ | Cena |
|---|---|
| 1 dospělý hrající + 1 dospělý nehrající (rodinní příslušníci) | 28 000 Kč |
| 1 dospělý hrající + 1 dítě (4-18 let nebo studující) | 27 000 Kč |
| 1 dospělý hrající + 1 dospělý nehrající + 1 dítě (4-18 let nebo studující) | 34 000 Kč |
| 1 dospělý hrající + 1 dospělý nehrající + 2 děti (4-18 let nebo studující) | 40 000 Kč |
| 2 dospělí hrající (rodinní příslušníci) | 35 500 Kč |
| 2 dospělí hrající + 1 dítě (4-18 let nebo studující) | 41 500 Kč |
| 2 dospělí hrající + 2 děti (4-18 let nebo studující) | 47 500 Kč |

„* Cena za pronájem venkovního dvorce pro nehrajícího člena je totožná s cenou pro veřejnost.“

Ostatní typy ročních členství:

| Typ | Podmínka | Cena |
|---|---|---|
| nehrající | pouze v návaznosti na hrající členství nebo při změně z hrajícího na nehrající | 10 000 Kč |
| firemní | pro firmy, které mají k dispozici min. 2 přenosné klubové karty | 40 000 Kč |

Poplatky za hosty člena:

| Položka | Cena |
|---|---|
| host člena na venkovním dvorci (maximálně 8x za rok) | 100 Kč |
| host člena u bazénu (jeden člen může pozvat jednoho hosta) | 400 Kč |
| host člena ve fitness (jeden člen může pozvat jednoho hosta) | 400 Kč |

**Způsob úhrady:**
- „v hotovosti – v kanceláři klubu u sekretářky E. Štefkové (+420 737 215 012) ve všední dny 9:00 – 17:00) nebo na recepci klubu (telefon +420 608 974 974 - mimo výše uvedené dny a hodiny)“
- „bankovním převodem – na účet klubu číslo 1471509/0300, ČSOB Praha a.s., ve zprávě pro příjemce uveďte své jméno - členství“

Patička stránky: „I. Český Lawn-Tennis Klub Praha, Ostrov Štvanice 38, 170 00 Praha 7, web: www.cltk.cz / telefon: +420 737 215 012“

*(Ceník Tenisové školičky je na `/cs/tenisova-skolicka/cenik/` – mimo rozsah tohoto podkladu.)*

---

## 10. Kontakty – https://cltk.cz/cs/kontakty/ (kompletně)

| Role | Jméno | Telefon | E-mail |
|---|---|---|---|
| Recepce (rezervace kurtů) | – | 608 974 974 | recepce@cltk.cz (e-mail jen z homepage) |
| Sekretářka – stálé rezervace + platba členství | Eva Štefková | 737 215 012 | stefkova@cltk.cz |
| Privátní trenéři I.ČLTK Praha | viz Výuka tenisu (sekce 6.1) | | |
| Webmaster | – | – | webmaster@cltk.cz |
| Hlavní manažer klubu | Vladislav Šavrda | 603 409 475 | savrda@cltk.cz |
| Sportovní manažer | Mgr. Petr Šavrda | 603 890 969 | petr.savrda@cltk.cz |
| Provozní manažer | Mgr. Martin Šustr | 724 432 073 | sustr@cltk.cz |
| Sportovní ředitel | Petr Vaníček | 739 220 233 | vanicek@cltk.cz |
| Vedoucí trenér tenisové školy (pro hráče do 9 let) | Mgr. Jan Pecha, Ph.D. | 721 663 118 | pecha@cltk.cz |

Další kontakty z jiných stránek:

| Role | Jméno | Telefon | E-mail / web | Zdroj |
|---|---|---|---|---|
| Vedoucí wellness (od 1.6.2024) / fyzioterapie | Joe Truesdale = Bc. Joseph B. Truesdale | +420 704 737 187 | leftfld14@hotmail.com | wellness-centrum, fyzioterapie-a-masaze |
| Tenis shop – kontaktní osoba | Tomáš Truneček | – | – | tenis-shop |
| Sportovní lékařství, masáže, fyzioterapie | Zdravý sport | – | https://www.centrum-inmotion.cz/ | fyzioterapie-a-masaze |

**Adresa:** I. ČLTK Praha, Štvanice 38, 170 00 Praha 7

**Bankovní účty:**
- Číslo bankovního účtu klubu: **1471509/0300** (pouze pro platby za tréninky a členství); IBAN **CZ20 0300 0000 0000 0147 1509**; banka ČSOB Praha a.s. (dle podminky-clenstvi)
- Číslo bankovního účtu Tenisové školy: **312451935/0300** (pouze za platby v rámci tenisové školy)

---

## 11. Facebook stránka – https://cltk.cz/cs/o-nas/49/
„Staňte se našimi fanoušky na Facebooku! Zajímavé zprávy z klubu, fotky z tréninků či zápasů našich hráčů, z víkendových turnajů, dětských kempů a podobných zajímavých akcí tak budete mít vždy hned po ruce.“ Odkaz: https://www.facebook.com/cltk.fb

## 12. „O nás“ – https://cltk.cz/cs/o-nas/
Stránka nemá vlastní text – jen rozcestník na Plán areálu, Galerie, Partneři, Plánované uzavírky, Facebook I.ČLTK Praha.

---

## 13. Partneři – https://cltk.cz/cs/o-nas/partneri/

Text stránky: „Děkujeme všem partnerům klubu za podporu!“ – následuje 31 log s odkazy (bez rozdělení na generální/hlavní/mediální partnery). Na homepage je samostatný pás 30 log (`//files.cltk.cz/site/…`), většina se překrývá. Loga v `assets/partneri/`, data v `podklady/data/partneri.json`.

Pořadí na stránce Partneři (= pořadí v JSON):

| # | Partner | Web (odkaz na webu klubu) | Soubor |
|---|---|---|---|
| 1 | Vivus | https://vivus.cz/ | vivus.jpg |
| 2 | J&T Banka | http://www.jtfg.com/ | jt-banka.jpg |
| 3 | Aston – služby v ekologii | https://www.aston-eco.cz/ | aston.jpg |
| 4 | Pankrác a.s. | https://www.pankrac-as.cz/ | pankrac.jpg |
| 5 | GreenGas | https://www.dpb.cz/ | green-gas.jpg |
| 6 | Hodinářství Bechyně | https://www.hodinarstvibechyne.cz/cs/ | hodinarstvi-bechyne.jpg (+ `hodinarstvi-bechyne-tmave.png`) |
| 7 | ISO Praha – stavebniny | http://www.iso-praha.cz/ | iso-praha.jpg |
| 8 | Noncore | http://www.noncore.cz/index.html | noncore.jpg |
| 9 | TUkas | https://www.tukas.cz | tukas.png |
| 10 | Smartwings | https://www.smartwings.com/ | smartwings.jpg |
| 11 | Messy Play | https://www.messyplay.cz/ | messy-play.jpg |
| 12 | Sport Construction | https://www.ekkl.cz/ | sport-construction.jpg |
| 13 | ČPP – Vienna Insurance Group | https://www.cpp.cz/ | cpp.jpg |
| 14 | Advantage Cars | https://www.advantage-cars.cz/ | advantage-cars.jpg |
| 15 | RPM Facility | https://rpmfacility.cz/ | rpm-facility.jpg |
| 16 | Sporttechnik Bohemia | https://www.sport-technik.cz/ | sporttechnik-bohemia.png |
| 17 | Crane Constancy Capital | http://cranece.com/ | crane-constancy-capital.png |
| 18 | Mizuno | https://www.levelsportkoncept.cz/znacka/mizuno | mizuno.jpg |
| 19 | Babolat | https://matchpoint.cz/ | babolat.png |
| 20 | Národní sportovní agentura | https://nsa.gov.cz/ | narodni-sportovni-agentura.jpg |
| 21 | Český tenis (Český tenisový svaz) | http://www.cztenis.cz/ | cesky-tenis.png |
| 22 | Victoria – Vysokoškolské sportovní centrum MŠMT | https://www.vsc.cz/ | victoria-vsc.jpg |
| 23 | Hlavní město Praha | https://www.praha.eu/… | praha.png |
| 24 | Městská část Praha 7 | https://www.praha7.cz/ | praha-7.png |
| 25 | Pražský tenisový svaz (PTS) | http://www.prazsky.cztenis.cz/?attachment_id=4415 | prazsky-tenisovy-svaz.png |
| 26 | Peach Distribution | https://www.peach-distribution.cz/ | peach.jpg |
| 27 | Glenfiddich | (bez odkazu) | glenfiddich.png (+ `glenfiddich-tmave.jpg`) |
| 28 | Astron studio | http://astron.cz/ | astron-studio.jpg |
| 29 | Pelmi – The logistics company | http://www.pelmi.cz/ | pelmi.jpg |
| 30 | Resultina | http://www.resultina.com/ | resultina.jpg |
| 31 | Protenis | http://www.protenis.cz/ | protenis.png |
| + | Moravia Steel | – (jen pás na homepage) | moravia-steel.jpg |
| + | Kontron (S&T Group) | – (jen pás na homepage) | kontron.jpg |
| + | Analytics Data Factory | – (jen pás na homepage, alt „adf“) | analytics-data-factory.jpg |

Pozn.: Na homepage chybí oproti stránce Partneři: Messy Play, Sporttechnik Bohemia, Peach, Pelmi. Úrovně partnerství (generální apod.) web neuvádí – **NEOVĚŘENO**. Z názvů alb ve fotogalerii: „Advantage Cars Prague Open 2019 by MONETA Money Bank“, „I.ČLTK Prague Open 2020/2021/2022 by Moneta Money Bank“, „Advantage Cars Prague Open 2023 by Moneta Money Bank“, „Advantage Cars Prague Open 2024 by Sport-Technik Bohemia“, „Advantage Cars Prague Open 2025“, „Sekyra Group Prague Open 2026 by Advantage Cars“; na fotce areálu jsou i plachty „Advantage Cars Prague Open 2016“.

---

## 14. Galerie – https://cltk.cz/cs/o-nas/galerie/

Stránka má dvě části:
1. Odkaz „Fotogalerie akcí a turnajů klubu zde“ → **https://cltk.dphoto.com/albums** (služba DPHOTO/Lightbox, galerie „I.ČLTK Praha Photos“, patička galerie „www.cltk.cz“; alba mají povolené stahování originálů).
2. Vlastní fotky přímo na webu ve 4 sekcích (lightbox, verze w960, originály až 8256 px):
   - **Areál I. ČLTK Praha** – 19 fotek (kurty, bazén, haly, vestibul, recepce, shop, multifunkční hřiště)
   - **Letecké záběry areálu** – 18 fotek (5× iPhone 960 px, 13× dron DJI 2000 px; léto i podzim, zimní haly)
   - **Fitness a wellness cetrum** (sic) – 17 fotek
   - **Restaurace Tiebreak** – 8 fotek

### Alba ve fotogalerii cltk.dphoto.com (stav 23. 9. 2026)
Počty fotek zjištěny přes API galerie; data = rozsah EXIF dat snímků (orientační).

| # | Album | Popis alba | Fotek | Datum snímků (EXIF, orientačně) | URL |
|---|---|---|---|---|---|
| 1 | Sekyra Group Prague Open 2026 by Advantage Cars | ATP Challenger 75 & ITF World Tennis Tour W50 / Photos copyright Sekyra Group Prague Open 2026 by Advantage Cars / Photos by Martin Sidorják & Jan Pecha | 612 | 15. 08. 2026 – 23. 08. 2026 | https://cltk.dphoto.com/album/eoqksndp |
| 2 | Tenisová škola Markéty Vondroušové 2026 (složka) | 2026 | 205 |  | https://cltk.dphoto.com/album/o54c6s0a |
| 3 | ↳ Letní tenisové kempy 2026 #6 |  | 26 | 25. 08. 2026 – 28. 08. 2026 | https://cltk.dphoto.com/album/q45aods9 |
| 4 | ↳ Letní tenisové kempy 2026 #5 |  | 13 | 10. 08. 2026 – 14. 08. 2026 | https://cltk.dphoto.com/album/jv52ivn3 |
| 5 | ↳ Letní tenisové kempy 2026 #4 |  | 31 | 03. 08. 2026 – 07. 08. 2026 | https://cltk.dphoto.com/album/948nj2m4 |
| 6 | ↳ Letní tenisové kempy 2026 #3 |  | 54 | 27. 07. 2026 – 31. 07. 2026 | https://cltk.dphoto.com/album/tlm8gtdq |
| 7 | ↳ Letní tenisové kempy 2026 #2 |  | 31 | 06. 07. 2026 – 10. 07. 2026 | https://cltk.dphoto.com/album/hkbnjodj |
| 8 | ↳ Letní tenisové kempy 2026 #1 |  | 36 | 29. 06. 2026 – 01. 07. 2026 | https://cltk.dphoto.com/album/gfckbran |
| 9 | ↳ Party po mistrácích 2026 | Kategorie babytenis a střední kurt | 14 | 21. 06. 2026 | https://cltk.dphoto.com/album/f136r2d5 |
| 10 | Festival oranžové úrovně 2026 na Štvanici | 3. ročník / foto: Český tenisový svaz / Pavel Lebeda (sport-pics.cz) | 175 | 27. 06. 2026 – 28. 06. 2026 | https://cltk.dphoto.com/album/hji9cl2a |
| 11 | Klubový den 2026 | květen 2026 | 241 | 24. 05. 2026 | https://cltk.dphoto.com/album/8n8rc7tf |
| 12 | Nadace Markéty Vondroušové | Charitativní den pro děti | 150 | 21. 12. 2025 – 07. 01. 2026 | https://cltk.dphoto.com/album/7e53v0nv |
| 13 | Tenisová extraliga 2025 | Day 1 (Photo: Martin Sidorják) | 87 | 16. 12. 2025 | https://cltk.dphoto.com/album/0j5b9xzc |
| 14 | Vánoční párty 2025 | Letenský zámeček (Photo: Martin Sidorják) | 59 | 12. 12. 2025 | https://cltk.dphoto.com/album/cysd1b4n |
| 15 | Večer talentů 2025 | (Photo: Martin Sidorják) | 35 | 10. 12. 2025 | https://cltk.dphoto.com/album/57d4ssao |
| 16 | Mikulášská besídka | (Photo: Martin Sidorják) | 55 | 04. 12. 2025 | https://cltk.dphoto.com/album/3vnjp6uv |
| 17 | CTC U14 2025 |  | 10 | 08. 11. 2025 – 11. 11. 2025 | https://cltk.dphoto.com/album/15x5rv1v |
| 18 | Tenisová škola Markéty Vondroušové 2025 (složka) | 2025 | 560 |  | https://cltk.dphoto.com/album/s3p0v049 |
| 19 | ↳ Mistrovství ČR jednotlivců v babytenise 2025 | Valerie Křivanová (1. místo) a Júki Hálek (2. místo) | 15 | 21. 09. 2025 – 15. 01. 2026 | https://cltk.dphoto.com/album/3eogimxs |
| 20 | ↳ Party po mistrácích 2025 | Kategorie babytenis a střední kurt | 61 | 22. 06. 2025 – 09. 07. 2025 | https://cltk.dphoto.com/album/qlc15j2k |
| 21 | ↳ Letní tenisové kempy 2025 (složka) |  | 434 |  | https://cltk.dphoto.com/album/0koron56 |
| 22 | ↳ ↳ Letní tenisové kempy 2025 #1 |  | 169 | 08. 07. 2025 – 17. 07. 2025 | https://cltk.dphoto.com/album/a9x4wsgp |
| 23 | ↳ ↳ Letní tenisové kempy 2025 #2 |  | 146 | 14. 07. 2025 – 17. 07. 2025 | https://cltk.dphoto.com/album/driry2kv |
| 24 | ↳ ↳ Letní tenisové kempy 2025 #3 |  | 39 | 28. 07. 2025 – 01. 08. 2025 | https://cltk.dphoto.com/album/2vwlwr8l |
| 25 | ↳ ↳ Letní tenisové kempy 2025 #4 |  | 52 | 11. 08. 2025 – 15. 08. 2025 | https://cltk.dphoto.com/album/g15b7a3i |
| 26 | ↳ ↳ Letní tenisové kempy 2025 #5 |  | 28 | 26. 08. 2025 – 29. 08. 2025 | https://cltk.dphoto.com/album/mb496epv |
| 27 | ↳ Mistrovství ČR družstev v babytenise 2025 | Prostějov (5. místo) | 50 | 02. 09. 2025 – 29. 10. 2025 | https://cltk.dphoto.com/album/l8a9rp3u |
| 28 | CTC Senior Competition 2025 | 35+/45+ Europe | 70 | 30. 08. 2025 | https://cltk.dphoto.com/album/nqrsruyg |
| 29 | Klubový den 2025 | září 2025 | 231 | 07. 09. 2025 | https://cltk.dphoto.com/album/b3a0ss5o |
| 30 | Festival oranžové úrovně 2025 na Štvanici |  | 106 | 28. 06. 2025 – 09. 07. 2025 | https://cltk.dphoto.com/album/vchd9okq |
| 31 | AELTC na Štvanici 2025 |  | 293 | 07. 06. 2025 | https://cltk.dphoto.com/album/rpjlgste |
| 32 | Advantage Cars Prague Open 2025 | ATP Challenger 75 & ITF World Tennis Tour W75 | 593 | 02. 05. 2025 – 11. 05. 2025 | https://cltk.dphoto.com/album/15sd4y |
| 33 | Vánoční večírek I.ČLTK Praha 2024 | Letenský zámeček (Foto: Martin Sidorják) | 38 | 13. 12. 2024 | https://cltk.dphoto.com/album/v3x6q7 |
| 34 | Večer talentů 2024 |  | 16 | 11. 12. 2024 | https://cltk.dphoto.com/album/dm72u2 |
| 35 | Focení s Tomášem Berdychem pro #jsmeceskytenis | Nový daviscupový kapitán | 11 | 10. 12. 2024 | https://cltk.dphoto.com/album/88ganh |
| 36 | CTC U14 2024 | 7-10 November 2024 | 10 | 09. 11. 2024 | https://cltk.dphoto.com/album/en62s0 |
| 37 | M ČR družstev v babytenise 2024 | 5. místo (3 výhry a 1 prohra) | 17 | 03. 09. 2024 – 12. 12. 2024 | https://cltk.dphoto.com/album/y3aoz5 |
| 38 | Letní tenisové kempy 2024 (složka) |  | 101 |  | https://cltk.dphoto.com/album/84hh2q |
| 39 | ↳ Letní tenisové kempy 2024 "1" | Tenisová škola Markéty Vondroušové | 22 | 09. 07. 2024 – 13. 07. 2024 | https://cltk.dphoto.com/album/oio70w |
| 40 | ↳ Letní tenisové kempy 2024 "2" | Tenisová škola Markéty Vondroušové | 23 | 15. 07. 2024 – 21. 07. 2024 | https://cltk.dphoto.com/album/z8nhcc |
| 41 | ↳ Letní tenisové kempy 2024 "3" | Tenisová škola Markéty Vondroušové | 21 | 30. 07. 2024 – 02. 08. 2024 | https://cltk.dphoto.com/album/0p9lkc |
| 42 | ↳ Letní tenisové kempy 2024 "4" | Tenisová škola Markéty Vondroušové | 19 | 12. 08. 2024 – 16. 08. 2024 | https://cltk.dphoto.com/album/8n6hzk |
| 43 | ↳ Letní tenisové kempy 2024 "5" | Tenisová škola Markéty Vondroušové | 16 | 28. 08. 2024 – 30. 08. 2024 | https://cltk.dphoto.com/album/81qtu9 |
| 44 | Advantage Cars Prague Open 2024 by Sport-Technik Bohemia | 5-12 May 2024 | 373 | 06. 05. 2024 – 12. 05. 2024 | https://cltk.dphoto.com/album/4l6fof |
| 45 | Soustředění babytenistů na Štvanici | 25 - 28 dubna 2024 | 17 | 25. 04. 2024 – 29. 04. 2024 | https://cltk.dphoto.com/album/i88i64 |
| 46 | 30. Masters v babytenise / 2008-2024 (složka) | I.ČLTK Praha Cup by Babolat Tour 2023/24 / Photo: Martin Sidorják / www.sidorjakphoto.com | 98 |  | https://cltk.dphoto.com/album/kbl1he |
| 47 | ↳ 30. Masters v babytenise - ceremoniál | Photo: Martin Sidorják / www.sidorjakphoto.com | 27 | 24. 03. 2024 | https://cltk.dphoto.com/album/0p4tgw |
| 48 | ↳ 30. Masters v babytenise - herní fotky | Možnost objednávky herních fotek ve vysokém rozlišení na www.sidorjakphoto.com / Photo: Martin Sidorják | 71 | 24. 03. 2024 | https://cltk.dphoto.com/album/7l67gx |
| 49 | Vánoční večírek 2023 | Letenský zámeček | 127 | 16. 12. 2023 | https://cltk.dphoto.com/album/rfl03w |
| 50 | Tenisová škola Markéty Vondroušové |  | 6 | 25. 09. 2023 – 07. 12. 2023 | https://cltk.dphoto.com/album/xkmsvq |
| 51 | CTC U14 2023 | I.ČLTK Praha Cup 2023 | 18 | 03. 11. 2023 – 04. 11. 2023 | https://cltk.dphoto.com/album/tsa19a |
| 52 | US Open 2023 | Photos: Martin Sidorják | 38 | 03. 01. 2024 | https://cltk.dphoto.com/album/mpp95u |
| 53 | Tenisová škola 2023 (složka) |  | 121 |  | https://cltk.dphoto.com/album/q0lb1r |
| 54 | ↳ Mistrovství ČR družstev v babytenise 2023 | Prostějov (5. místo) | 20 | 08. 09. 2023 – 10. 09. 2023 | https://cltk.dphoto.com/album/ijw5zy |
| 55 | ↳ I.ČLTK Praha Cup by Babolat 2023 | 29. série turnajů kategorie babytenis a střední kurt | 27 | 01. 07. 2023 – 19. 08. 2023 | https://cltk.dphoto.com/album/jwbf1n |
| 56 | ↳ Družstva 2023 | Photo: Martin Sidorják | 44 | 24. 06. 2023 | https://cltk.dphoto.com/album/5puljo |
| 57 | ↳ Kempy 2023 | Photo: Martin Sidorják | 30 | 10. 07. 2023 – 18. 08. 2023 | https://cltk.dphoto.com/album/kgqp6d |
| 58 | 130 let I.ČLTK Praha - výběr | 1893 - 2023 | 29 | 22. 07. 2023 | https://cltk.dphoto.com/album/ulyicj |
| 59 | 130 let I.ČLTK Praha - turnaj | 1893 - 2023 | 97 | 22. 07. 2023 | https://cltk.dphoto.com/album/t7tpxu |
| 60 | 130 let I.ČLTK Praha - společenský večer | 1893 - 2023 | 90 | 22. 07. 2023 | https://cltk.dphoto.com/album/3nm6m0 |
| 61 | Wimbledon 2023 | Markéta Vondroušová Champion (Photos: Martin Sidorják) | 43 | 20. 12. 2023 – 03. 01. 2024 | https://cltk.dphoto.com/album/j23l50 |
| 62 | Roland Garros 2023 | Karolína Muchová Runner-up (Photos: Martin Sidorják) | 32 | 10. 06. 2023 – 03. 01. 2024 | https://cltk.dphoto.com/album/yupj9r |
| 63 | Advantage Cars Prague Open 2023 by Moneta Money Bank | 30 April - 7 May, 2023 | 220 | 29. 04. 2023 – 07. 05. 2023 | https://cltk.dphoto.com/album/jybam3 |
| 64 | I.ČLTK PRAGUE OPEN 2022 by Moneta Money Bank | ATP Challenger 80 / ITF World Tennis Tour W60 | 215 | 30. 04. 2022 – 08. 05. 2022 | https://cltk.dphoto.com/album/9hisf0 |
| 65 | Tenisová škola 2021 (složka) |  | 17 |  | https://cltk.dphoto.com/album/b3semv |
| 66 | ↳ I.ČLTK Praha Cup by Babolat Tour - Masters |  | 7 | 22. 02. 2022 | https://cltk.dphoto.com/album/ziacku |
| 67 | ↳ Mistrovství ČR družstev v babytenise |  | 4 | 22. 02. 2022 | https://cltk.dphoto.com/album/g3aqmg |
| 68 | ↳ Letní tenisové kempy |  | 6 | 22. 02. 2022 | https://cltk.dphoto.com/album/1eln3g |
| 69 | I.ČLTK Prague Open 2021 by Moneta Money Bank (složka) | ATP Challenger 80 / ITF W25 / 2 - 9 / May / 2021 / Photo: Martin Sidorják / Instagram: martin.sidorjak / Web: www.sidorjakphoto.com | 1091 |  | https://cltk.dphoto.com/album/2ccd6k |
| 70 | ↳ DAY 8 / I.ČLTK Prague Open 2021 by Moneta Money Bank |  | 149 | 08. 05. 2021 – 09. 05. 2021 | https://cltk.dphoto.com/album/02495g |
| 71 | ↳ DAY 7 / I.ČLTK Prague Open 2021 by Moneta Money Bank |  | 116 | 07. 05. 2021 – 08. 05. 2021 | https://cltk.dphoto.com/album/8d40fy |
| 72 | ↳ I.ČLTK PRAGUE OPEN 2022 by Moneta Money Bank | ATP Challenger 80 / ITF World Tennis Tour W60 | 1 | 03. 05. 2022 | https://cltk.dphoto.com/album/m9nt7x |
| 73 | ↳ DAY 6 / I.ČLTK Prague Open 2021 by Moneta Money Bank |  | 94 | 06. 05. 2021 – 07. 05. 2021 | https://cltk.dphoto.com/album/03ffds |
| 74 | ↳ DAY 5 / I.ČLTK Prague Open 2021 by Moneta Money Bank |  | 117 | 05. 05. 2021 – 06. 05. 2021 | https://cltk.dphoto.com/album/678a9w |
| 75 | ↳ DAY 4 / I.ČLTK Prague Open 2021 by Moneta Money Bank |  | 124 | 04. 05. 2021 – 05. 05. 2021 | https://cltk.dphoto.com/album/91cf4k |
| 76 | ↳ DAY 3 / I.ČLTK Prague Open 2021 by Moneta Money Bank |  | 207 | 03. 05. 2021 – 04. 05. 2021 | https://cltk.dphoto.com/album/3457ey |
| 77 | ↳ DAY 2 / I.ČLTK Prague Open 2021 by Moneta Money Bank |  | 148 | 02. 05. 2021 – 03. 05. 2021 | https://cltk.dphoto.com/album/e6356m |
| 78 | ↳ DAY 1 / I.ČLTK Prague Open 2021 by Moneta Money Bank |  | 75 | 01. 05. 2021 – 02. 05. 2021 | https://cltk.dphoto.com/album/e6ee7v |
| 79 | ↳ PRACTICE ON SATURDAY / I.ČLTK Prague Open 2021 by Moneta Money Bank |  | 40 | 30. 04. 2021 – 01. 05. 2021 | https://cltk.dphoto.com/album/3a43dt |
| 80 | ↳ PRACTICE ON FRIDAY / I.ČLTK Prague Open 2021 by Moneta Money Bank |  | 20 | 30. 04. 2021 | https://cltk.dphoto.com/album/e368ex |
| 81 | Tenisová extraliga 2020 | 12 - 16 / 12 /2020 / Photo: Martin Sidorják | 125 | 14. 12. 2020 | https://cltk.dphoto.com/album/cca79z |
| 82 | Tenisová škola 2020 (složka) |  | 31 |  | https://cltk.dphoto.com/album/7b448y |
| 83 | ↳ I.ČLTK Praha Cup by Babolat Tour |  | 18 | 27. 06. 2020 – 29. 08. 2020 | https://cltk.dphoto.com/album/4f9a1w |
| 84 | ↳ Soutěže družstev a Přebory Prahy |  | 2 | 19. 06. 2020 – 14. 09. 2020 | https://cltk.dphoto.com/album/87cc3j |
| 85 | ↳ Letní tenisové kempy |  | 11 | 03. 07. 2020 – 28. 08. 2020 | https://cltk.dphoto.com/album/a42fcw |
| 86 | I.ČLTK Prague Open 2020 by Moneta Money Bank (složka) | ATP Challenger15 - 22 / 8 / 2020Photo: Martin Sidorják | 746 |  | https://cltk.dphoto.com/album/7134fg |
| 87 | ↳ DAY 1 / I.ČLTK Prague Open 2020 by Moneta Money Bank | ATP Challenger / 15 - 22 / 8 /2020 / Photo: Martin Sidorják | 107 | 15. 08. 2020 – 23. 08. 2020 | https://cltk.dphoto.com/album/33235s |
| 88 | ↳ DAY 2 / I.ČLTK Prague Open 2020 by Moneta Money Bank | ATP Challenger15 - 22 / 8 / 2020Photo: Martin Sidorják | 99 | 16. 08. 2020 | https://cltk.dphoto.com/album/29db1u |
| 89 | ↳ DAY 3 / I.ČLTK Prague Open 2020 by Moneta Money Bank | ATP Challenger15 - 22 / 8 /2020Photo: Martin Sidorják | 76 | 17. 08. 2020 | https://cltk.dphoto.com/album/83747h |
| 90 | ↳ DAY 4 / I.ČLTK Prague Open 2020 by Moneta Money Bank | ATP Challenger15 - 22 / 8 / 2020Photo: Martin Sidorják | 63 | 18. 08. 2020 | https://cltk.dphoto.com/album/78a3cr |
| 91 | ↳ DAY 5 / I.ČLTK Prague Open 2020 by Moneta Money Bank | ATP Challenger15 - 22 / 8 / 2020Photo: Martin Sidorják | 136 | 19. 08. 2020 | https://cltk.dphoto.com/album/b89bau |
| 92 | ↳ DAY 6 / I.ČLTK Prague Open 2020 by Moneta Money Bank |  | 80 | 20. 08. 2020 | https://cltk.dphoto.com/album/0d045t |
| 93 | ↳ DAY 7 / I.ČLTK Prague Open 2020 by Moneta Money Bank | ATP Challenger15 - 22 / 8 / 2020Photo: Martin Sidorják | 90 | 21. 08. 2020 | https://cltk.dphoto.com/album/5dd10n |
| 94 | ↳ DAY 8 / I.ČLTK Prague Open by Moneta Money Bank | ATP Challenger15 - 22 / 8 / 2020Photo: Martin Sidorják | 95 | 22. 08. 2020 | https://cltk.dphoto.com/album/6316co |
| 95 | Tenisová extraliga 2019 | 1. místo v soutěži družstev (Foto: Martin Sidorják) | 241 | 14. 12. 2019 – 18. 12. 2019 | https://cltk.dphoto.com/album/169f9q |
| 96 | Vánoční párty 2019 |  | 65 | 14. 12. 2019 | https://cltk.dphoto.com/album/2a563k |
| 97 | Tisková konference 2019 | Jonáš Forejtek (ITF No. 1)Nikola Bartůňková (TE No. 1) | 33 | 19. 09. 2019 | https://cltk.dphoto.com/album/0dfbft |
| 98 | Golfový turnaj 2019 | Albatross | 119 | 24. 09. 2019 | https://cltk.dphoto.com/album/80140v |
| 99 | Klubový den 2019 | podzim 2019 | 153 | 22. 09. 2019 | https://cltk.dphoto.com/album/c848fk |
| 100 | Letní tenisové kempy 2019 | Tenisová škola I.ČLTK Praha by Babolat | 7 | 07. 09. 2019 | https://cltk.dphoto.com/album/67b93w |
| 101 | Letní série turnajů kategorie babytenis 2019 | I.ČLTK Praha Cup by Babolat | 4 | 07. 09. 2019 | https://cltk.dphoto.com/album/ce04al |
| 102 | Advantage Cars Prague Open 2019 by MONETA Money Bank (složka) |  | 849 |  | https://cltk.dphoto.com/album/e365du |
| 103 | ↳ DAY 1 / Advantage Cars Prague Open 2019 by MONETA Money Bank | 22 - 28 July 2019 (Photo: Martin Sidorják) | 152 | 19. 07. 2019 – 23. 07. 2019 | https://cltk.dphoto.com/album/zjc1md |
| 104 | ↳ DAY 2 / Advantage Cars Prague Open 2019 by MONETA Money Bank | 22 - 28 July 2019 (Photo: Martin Sidorják) | 154 | 23. 07. 2019 – 24. 07. 2019 | https://cltk.dphoto.com/album/5eabcz |
| 105 | ↳ DAY 3 / Advantage Cars Prague Open 2019 by MONETA Money Bank |  | 114 | 24. 07. 2019 – 25. 07. 2019 | https://cltk.dphoto.com/album/09045g |
| 106 | ↳ DAY 4 / Advantage Cars Prague Open 2019 by MONETA Money Bank |  | 97 | 25. 07. 2019 – 26. 07. 2019 | https://cltk.dphoto.com/album/46aeas |
| 107 | ↳ DAY 5 / Advantage Cars Prague Open 2019 by MONETA Money Bank |  | 135 | 26. 07. 2019 – 27. 07. 2019 | https://cltk.dphoto.com/album/17250m |
| 108 | ↳ DAY 6 / Advantage Cars Prague Open 2019 by MONETA Money Bank |  | 79 | 27. 07. 2019 – 28. 07. 2019 | https://cltk.dphoto.com/album/bf54dw |
| 109 | ↳ DAY 7 / Advantage Cars Prague Open 2019 by MONETA Money Bank |  | 118 | 28. 07. 2019 – 29. 07. 2019 | https://cltk.dphoto.com/album/7980dn |

Celkem 50 alb nejvyšší úrovně, 109 alb vč. podalb, 8680 souborů.

---

## 15. Stažené fotky (`assets/foto/`, popis v `podklady/data/fotky.json`)

44 fotek + plán areálu. Pravidla zpracování: vždy největší dostupná verze (na files.cltk.cz odstraněn segment `/w960/`, `/h130/`, `/optimized/`; z cltk.dphoto.com originál přes funkci „Stáhnout“, kterou alba povolují). Soubory nad 3840 px zmenšeny na 3840 px delší strany (JPEG q88); CMYK převedeno do RGB. Originály (až 8256×5504) zůstávají v `podklady/_raw/areal/orig/` a `…/cltkfoto/`.

| Skupina | Soubory |
|---|---|
| Kurty / areál | kurty-antuka-panorama-rybi-oko, kurty-antuka-turnaj-divaci, kurty-antuka-panorama-stadion (2000×717 – panorama), kurt-antuka-tribuna-destniky, detail-sit-logo-cltk-antuka, kurty-antuka-stromy-leto, kurt-antuka-zivy-plot-tribuny |
| Haly | hala-pevna-kurty-interier, hala-pretlakova-interier (3840) |
| Klubovna / interiéry | klubove-prostory-lounge (3840), vestibul-vchod, recepce, tenis-shop, terasa-promenada-hodiny (3840), terasa-restaurace-stoly (3840) |
| Sportoviště, bazén | multifunkcni-hriste (3840), beachvolejbal-hriste (3840), bazen-lehatka-destniky (3840), bazen-travnik-lehatka |
| Letecké | letecky-stvanice-panorama-prahy, letecky-stvanice-ostrov-podzim, letecky-slavoj-kurty-podzim, letecky-zimni-haly-shora, letecky-stadion-kolmo-shora, letecky-kurty-leto-shora, letecky-kurty-bazen-leto, letecky-stvanice-svisle (portrét) |
| Fitness / wellness | fitness-centrum, wellness-virivka-lehatka (3840), wellness-kneippovy-lazne (3840), wellness-sauna-virivka |
| Restaurace Tiebreak | restaurace-tiebreak-interier, restaurace-salonek-detsky-koutek |
| Akce / společenský život | vyroci-130-let-spolecna-fotografie (3840), vyroci-130-let-oceneni-clenu (3840), vanocni-vecirek-2025-letensky-zamecek (3840), ctc-senior-2025-centenary-banner, extraliga-2025-tymova-fotografie (3840) |
| Turnaje / umělecké detaily antuky | prague-open-2026-stadion (3840), prague-open-2026-stin-hrace-antuka (3840), prague-open-2025-stin-site-antuka (3000), prague-open-2025-stin-nohy-antuka (3000) |
| Hráčky | hracka-vondrousova-wimbledon-2023-trofej (3840, z originálu 6412×4275), hracka-antuka-radost-hero (1920) |
| Plán | plan-arealu.jpg (7033×4143) |

Slabá místa fotek: fotky areálu na cltk.cz mají max. 1500–2000 px (kromě pár kusů), letecké iPhone snímky jen 960 px (nestaženy). Autor fotek na cltk.cz není uveden (**NEOVĚŘENO**); u alb dphoto je autor uveden v popisu alba (většinou Martin Sidorják). Uživatel slíbil vlastní „pěkné fotky“ – tyto jsou pracovní.

---

## 16. Nesrovnalosti a nejasnosti (NEOVĚŘENO)

1. **Multifunkční hřiště:** text říká, že obě hřiště (multifunkční i beach) jsou „za Negrelliho viaduktem“; plán ukazuje navíc druhé, menší „MULTIFUNKČNÍ HŘIŠTĚ“ u odrazové stěny vedle trojkurtu 2–4. Které se pronajímá za 900 Kč/hod – NEOVĚŘENO.
2. **Název pevné haly:** plán „PEVNÁ HALA“, zimní ceník „Pevná hala Novasport“, rezervační systém „Center - Nova Sport“ (kurt C) + „PevnÁ Hala 1/2“.
3. **Telefon klubu:** provozní řády (PDF) uvádějí tel. 222 316 317; web všude 608 974 974 (recepce) a 737 215 012 (sekretářka).
4. **Otevírací doba areálu, recepce a restaurace** na webu není. Rezervační mřížka má sloty 06:00–23:30, zimní ceník hrací časy 7:00–22:00 (antuková hala do 21:00, víkend od 8:00).
5. **Kurt 1 a dvorce „malý centr“:** fitness (2017) a wellness (2019) vznikly „nástavbou na ochozu malého centrálního dvorce“ – tj. u kurtu 1 (na plánu FITNESS/REGENERACE 2.NP nad kurtem 1).
6. **Hero fotka 3MS_0145** – hráčka je velmi pravděpodobně Karolína Muchová (shodný dres s albem „Roland Garros 2023“), ale web ji nepopisuje.
7. **Kapacity:** kurt 1 „cca 1000 míst“, velký centrální dvorec „8000 míst“ – převzato z webu, nezávisle neověřeno.
8. **Livesport Prague Open** (20.–26. 7. 2026) – kategorie turnaje na webu neuvedena.
9. Odkaz na Revue z homepage vede na `/klub/klubovy-casopis-icltk-revue/` (bez `/cs/`); gallery sekce má překlep „cetrum“.

---

## 17. Podněty pro nový web (z tohoto podkladu)

- **Interaktivní mapa Štvanice:** plán má jasnou strukturu (Slavoj | viadukt | hlavní budova s kurtem 1 a stadionem C | bazén a kurty 2–9). Souřadnice v sekci 5 → SVG mapa s hover kartami (povrch léto/zima, cena, rezervace, fotka). Přepínač **Léto / Zima** – v zimě se kurty 1–9 a C „zabalí“ do přetlakových hal (máme letecké fotky obou stavů: `letecky-kurty-leto-shora.jpg` × `letecky-zimni-haly-shora.jpg`), Slavoj 10–16 zešedne („v zimní sezoně uzavřeno“).
- **Ceník jako elegantní kalkulačka:** zimní ceník má 3 haly × časová pásma × veřejnost/člen × jednotlivě/předplatné – ideální pro přepínače („Jsem člen“ / „Hodina“ vs „Celá sezóna 26 týdnů“) místo PDF.
- **Kotva „ostrov uprostřed Prahy“:** letecké snímky ostrova ve Vltavě + citace „Takto blízko centra Prahy naleznete jen velmi málo podobně příjemných a klidných míst…“.
- **Antukové detaily** (stín hráče, stín sítě, síť s erbem I. ČLTK) jako luxusní, klidné vizuály ve světlém designu – textura antuky jako akcentní barva.
- **Čísla areálu:** 19 kurtů · 13 antukových venku · 12 krytých v zimě · stadion 8000 míst · malý centr cca 1000 míst · bazén „více než 20 let“ · wellness od 2019 · fitness od 2017.
- **Živé prvky, které už klub má:** rezervace RogerOnline, obsazenost kurtů (onlinehq), živé výsledky hráčů (Resultina/scorepresso widget), Revue, kalendář událostí, 50 alb / cca 8 680 fotek v galerii.
- **Stav „dnes v areálu“:** web má prázdný `notification-wrapper` a stránku Plánované uzavírky – dá se z toho udělat decentní stavový pruh (např. „20.–26. 7. Livesport Prague Open – areál částečně uzavřen“).
