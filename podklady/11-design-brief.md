# 11 – Design brief: tři návrhy nového webu I. ČLTK Praha

Stav k 23. 9. 2026. Brief je pro návrháře, lidi i agenty.

- **Data:** jen `podklady/data/obsah.json`. Klíče jsou v textu uvedené v `kódu`.
- **Fakta a formulace:** `README.md`.
- **Nápady:** `09-inspirace-a-napady.md`. Označení N1–N26 odkazuje na jeho kapitolu 2.
- **Fotky:** `assets/`. Ve všech návrzích smí být jen soubory, které tam skutečně jsou.

---

## 1. Zadání klienta

- Luxusní, neobvyklý a mimořádný web ve **světlých barvách**, **decentní luxus**. Má to být „něco, co nikdo nemá“ – výstavní klub českého tenisu s ohromnými výsledky.
- **Jedna** varianta smí vyjít z palety Prague Open 03: teplá bílá `#fbfaf6`, navy `#152744` a zlatá `#b3935a`, písma **Cormorant Garamond + Jost**.
- **Logo zachovat.** Hlavním znakem je 3D stříbrná verze od zadavatele (`assets/logo/cltk-znak-3d.png`).
- Fotky jsou zatím ze stávajícího webu a z PDF Revue. Zadavatel dodá vlastní.
- Předchozí kolo z června 2026 je ve složce `C:\Users\Asus\cltk-web-navrhy\`: `01-cltk-luxe` (navy a zlatá, Cormorant + Jost) a `02-cltk-antuka` (Space Grotesk, kost a cihlová).
  - **Nové návrhy nesmí být jejich přebarvením.** Musí se lišit strukturou, hlavním motivem i interakcemi.

## 2. Strategie: čím to bude „jako nikdo“

- **Světové kluby mají veřejné weby skromné.**
  - Queen's a Longwood běží na šabloně Jonas Club Software.
  - Hurlingham má na úvodní stránce pět dlaždic.
  - Laťku nastavují turnaje (Wimbledon, Roland-Garros) a luxusní značky (Aman, Rolex, Hermès).
  - Český standard tvoří dlaždice s ikonami, sportovní verzálky, stěny log a výsledkové widgety.
  - Redakční ani muzejní přístup nemá žádný ze zkoumaných webů (09 kap. 0).
- **I. ČLTK má obsah, který nikdo jiný nemá:**
  - 41 čísel Revue za 20 let s vybratelným textem;
  - ostrov s historií od štvanic po lávku HolKa;
  - čtyři dobová jména klubu;
  - tři wimbledonští vítězové, kteří vyrostli na Štvanici;
  - **10 grandslamových titulů v barvách klubu** v den triumfu;
  - jako jediný český klub člen Centenary Tennis Clubs;
  - jeden ze tří klubů v ČR s celou pyramidou TSM → SVT → SCM;
  - 238 článků a 8 680 fotek.
- Každý koncept proto staví na jiném pokladu:
  - **A – Zlatá deska:** čest a členství, klub jako vstupní hala s čestnými deskami.
  - **B – Ostrov v čase:** archiv a místo, první digitální muzeum tenisového klubu.
  - **C – Živá Štvanice:** dnešní provoz a výkon, klub jako concierge velkého hotelu.
- **Poctivost je součástí luxusu.** Každý koncept ukazuje, co je ověřené a co tvrdí klub. Příklady: přepínač „dvojí metr“ nebo muzejní štítek s pramenem. Tohle nedělá nikdo a zároveň to chrání klub před chybami ze starých textů.

---

## 3. Společná pravidla pro A, B i C

### 3.1 Obsah a fakta

- Čísla, jména, data a ceny přebírat **jen z `obsah.json`**, doslova.
- Když údaj chybí, návrh ukáže decentní štítek „doplní klub“. Nikdy nevymýšlet otevírací doby, termíny akcí 2026 po 23. 9. ani kapacitu parkoviště.
- **Formulace:** podle `klub.doporucene_formulace` a `klub.nepouzivat`.
  - Psát „Od roku 1893“ a „nejstarší tenisový klub v Praze“, ne „v ČR“.
  - Drobný a Kodeš: „ve štvanické historii“, ne „klubové tituly“.
- **16 titulů mistra republiky** vždy s rozpisem (12× Spartak Praha Motorlet, 1975, 1990, 2018, 2019) a se štítkem „podle klubu“.
- **Hlas webu** vychází z článků a newsletterů: „štvanický“, „naši hráči“, rodinná hrdost a gratulace.
  - Bez emoji, vykřičníkových sérií a „!!“.
  - Přezdívky (Kája, Maky, Niki) jen v citacích.

### 3.2 Citlivá témata

- **Vondroušová** je v historii (Wimbledon 2023), ale **nesmí být tváří webu**.
  - Každý panel s ní musí fungovat i bez portrétu, tedy v typografické verzi (rok, skóre, trofej).
  - Portrét se použije jen v mockupu a s poznámkou „po souhlasu klubu“.
  - Aktuální tváří je Karolína Muchová.
- Tresty Bartůňkové a Mináře ani spory ČTS nezmiňovat.
- Centrkurt patří ČTS. Nepsat „náš stadion“, psát „štvanický stadion“.
- Jména dětí z TSM, SVT a rozvrhů nepoužívat. Údaj „maminka na recepci“ nepoužívat.
- Odkazovat jen na **pragueopen.net**, nikdy na pragueopen.org (unesená doména).

### 3.3 Znak a identita

- **Hlavička:** `cltk-znak-3d.png` do výšky zhruba 64–96 px, na retině 2×. Soubor má jen 402×445 px, víc nezvětšovat.
- **Větší plochy a tisk:** vektor `cltk-znak.svg`.
- **Vodoznak, slepotisk a patička:** `cltk-znak-mono.svg` nebo `-mono-plny.svg`. Oba mají `currentColor`, takže se barví přes CSS.
- Pod znak sázet „ZALOŽEN 1893“ tenkými verzálkami s prostrkáním. Tak to dělá Revue.
- Znak nepřekreslovat, nepřebarvovat, neořezávat a nedávat na něj efekty.
- **Autentické barvy klubu** jako drobné akcenty (07):
  - trikolora štítu: modrá `#2E3192`, bílá, červená `#ED1C24`;
  - zlatá linková `#89792F`;
  - Revue: zlatá `#B9A355` a navy `#00275D`.

### 3.4 Typografie a čeština

- Kontrolní render každého nadpisového řezu: „ŘÍJEN NA ŠTVANICI – ŽEMLA · KOŽELUH · ŮČTY ĎÁBLA, ŤUK“. Pravidla v 09 kap. 4.1.
- Načítat subsety *latin* i *latin-ext*.
- **Žádný `background-clip:text` na českých nadpisech.** Pokud by byl nutný, pak s `padding-top:.16em; margin-top:-.16em` a render ověřit okem.
- Verzálkové titulky mají mít `line-height` aspoň 1,08, u Cormorantu stačí 0,95.
- Skóre a ceny sázet s `font-variant-numeric: lining-nums tabular-nums`.
- České uvozovky „…“ a nezlomitelná mezera po jednoznakových předložkách (v, k, s, z, o, u, a, i).
- Text 17–19 px, řádek 60–70 znaků.
- Popisky 11–12 px ve verzálkách s prostrkáním .18–.24 em.

### 3.5 Luxus je zdrženlivost

**Ano:**

- teplé světlé pozadí, nikdy čistá `#fff` jako pozadí stránky;
- žádná čistá černá;
- **jeden akcent na obrazovku**;
- nejvýš 1–2 tmavé bloky na stránku;
- velké mezery;
- podtržené textové odkazy místo tlačítek (kromě CTA „Rezervovat kurt“);
- fotky s teplou gradací a lehce sníženou sytostí;
- historické snímky v rámech a výřezech, nikdy přes celou šířku (mají jen 300–1600 px).

**Ne** (09 kap. 3):

- stock rakety a míčky;
- neonová žlutá;
- úzké sportovní verzálky;
- dlaždice s ikonami jako hlavní obsah;
- automatické slidery s tečkami;
- barevná stěna log;
- vyskakovací okna;
- vložené sociální sítě;
- „Vítejte v …“ jako H1;
- falešné zlaté přechody, mramor a psací písmo ve verzálkách;
- informace dostupné jen po najetí myší.

### 3.6 Použitelnost

- **„Rezervovat kurt“** (`klub.rezervace_url`) a telefon **608 974 974** (`klub.telefon`) musí být vždy do jednoho kliknutí, na mobilu jako lepivá lišta.
- Přepínač **CS / EN** v hlavičce. EN obsah smí být v mockupu jen naznačený.
- **Přístupnost** (N25): v patičce přepínače „Omezit pohyb“ a „Vyšší kontrast“. Respektovat `prefers-reduced-motion` a mít plnou obsluhu klávesnicí.
  - Každý hover prvek je zároveň `<button>` (`aria-expanded`).
  - Posuvníky jsou `<input type="range">` s `aria-live`.
- **Mobil:** 375 px bez vodorovného přetečení. Tabulky ceníku a výsledků smí vodorovně rolovat jen uvnitř svého boxu.
- **Formuláře:** Enter nesmí přeskočit krok ani odeslat formulář předčasně. Výchozí tlačítko musí být „Pokračovat“ nebo „Odeslat“, nikdy „Zpět“ či šipky.

### 3.7 Výstup mockupu

- Jeden HTML soubor na koncept v kořeni repozitáře:
  - `01-zlata-deska.html`
  - `02-ostrov-v-case.html`
  - `03-ziva-stvanice.html`
- Každý soubor obsahuje homepage a 3–5 podstránek jako pohledy přes `#hash`.
- CSS a JS inline, obrázky relativně z `assets/`, písma z Google Fonts.
- Data se opisují z `obsah.json` do inline JSON nebo do HTML. Nevymýšlet.
- **Kontrola:**
  - `node nastroje/snimek.mjs <soubor> <png> --w=1440 --full`
  - totéž s `--mobile`
  - `--engine=webkit` pro Safari
  - bez chyb v konzoli, bez 404 a bez přetečení.

---

## 4. Koncept A – „Zlatá deska“ (klubový dům)

**Věta:** Web jako vstupní hala soukromého klubu se 130letou tradicí. Papír, zlaté písmo, rytiny a čestné desky, ale i jasná cesta k členství.

**V čem je výjimečný:**

- Klubovou čest převádí na předměty: čestnou desku, wimbledonský triptych a tisknutelný členský list.
- Jako jediný český klubový web ukazuje dvojí metr úspěchů.
- Paleta navazuje na hlavičku Revue, kterou klub používá 20 let (zlatá, navy, znak).

### Paleta (klientova, rozšířená o tokeny s ověřeným kontrastem)

| Token | Hex | Použití | Kontrast na `#fbfaf6` |
|---|---|---|---|
| `--paper` | `#fbfaf6` | pozadí stránky | – |
| `--paper-2` | `#f3f0e9` | desky, karty, pásy | – |
| `--ink` | `#1a2233` | text | 15,23 : 1 |
| `--navy` | `#152744` | nadpisy, 1–2 tmavé bloky | 14,31 : 1 |
| `--navy-deep` | `#0e1b30` | patička | – |
| `--gold` | `#b3935a` | linky, ornamenty, velká čísla **jen na navy** | 2,78 : 1 (na navy 5,15 : 1) |
| `--gold-soft` | `#caac79` | zlatý text na navy | 6,89 : 1 na navy |
| `--gold-text` | `#8a6d3b` | zlatý **text** na papíře (kurzíva v titulku, štítky) | 4,64 : 1 |
| `--muted` | `#6b6f7e` | popisky | 4,79 : 1 (pozor: `#7b8092` má jen 3,76) |
| `--line` | `#e4e0d6` | vlasové linky | – |
| trikolora | `#2E3192` · bílá · `#ED1C24` | 3px svislý proužek u nadpisů sekcí, nic víc | – |

### Písma

| Písmo | Role | Poznámka |
|---|---|---|
| **Cormorant Garamond** 300–600 + kurzíva | nadpisy | Jedno slovo v titulku zlatou kurzívou. Letopočty minuskovými číslicemi, v tabulkách `lnum tnum`. |
| **Cormorant SC** | čestná deska | Skutečné kapitálky, `font-synthesis:none`. latin-ext podle metadat GF, glyfy ověřit renderem. |
| **Jost** 300–500 | UI, text, verzálkové popisky | `tnum` na ceny. |

Česká diakritika u Cormorant Garamond a Jost je ověřená kontrolou glyfů (`_raw/inspirace/fonty-kontrola.txt`).

### Hero

- **Foto:** `assets/foto/letecky-stvanice-panorama-prahy.jpg` – ostrov ve Vltavě s panoramatem Prahy, zhruba 72vh, teplá gradace.
  - Alternativa: `terasa-promenada-hodiny.jpg` (klubovna, zlaté sloupové hodiny).
- **H1:** „Tenis na ostrově uprostřed Prahy. *Od roku 1893.*“ Slovo „1893“ zlatou kurzívou.
- **Podtitul:** „I. Český Lawn-Tennis Klub Praha – na Štvanici od roku 1901.“
- **CTA:** „Rezervovat kurt“ (vyplněné navy) a „Stát se členem“ (podtržený odkaz).

### Signature prvky

1. **Tři wimbledonské trávy (N1, 1954 · 1973 · 2023).**
   - Tři vysoké panely v poměru 2:3.
   - Fotky:
     - Drobný: `hist-1954-drobny-s-wimbledonskym-poharem.jpg`
     - Kodeš: `hist-1970s-jan-kodes-na-stvanici.jpg`
     - Vondroušová: `hracka-vondrousova-wimbledon-2023-trofej.jpg`, **s typografickou záložní verzí**
   - Pod fotkou rok 120 px a ryté skóre finále:
     - Drobný: 13-11 · 4-6 · 6-2 · 9-7 (Rosewall)
     - Kodeš: 6-1 · 9-8 · 6-3 (Metreveli)
     - Vondroušová: 6:4 · 6:4 (Jabeurová)
   - Ťuknutí nebo najetí: ČB fotka se zbarví a rozbalí se řádek „V den titulu hrál za: Egypt / Sparta / I. ČLTK Praha“.
   - Pod triptychem tenká „wimbledonská linka“ s body 1962 (Pužejová, finále) a 2026 (Muchová, finále).
   - Data: `sin_slavy[]` (Drobný, Kodeš, Vondroušová, Suková-Pužejová, Muchová) a `kronika[]`.
2. **Zlatá deska (N2 + N6).**
   - Digitální zlacená čestná tabule na `--paper-2`: řádky „rok · jméno · čin“ s tečkovanými vodicími linkami a rytinovým `text-shadow`.
   - **Záložky:**
     - Čestní členové (4 novodobí a 9 historických od 1893)
     - Zasloužilí členové (34 jmen a in memoriam)
     - Mistři republiky: 2019, 2018, 1990, 1975 a řádek „12× Spartak Praha Motorlet – podle klubu“
     - Grand Slam
     - Olympijské hry
     - Prezidenti od 1893
   - **Přepínač dvojího metru:** „Vyrostli na Štvanici · 20“ × „V barvách klubu · 10“. Řádky, které do zvoleného metru nepatří, zešednou.
   - Data: `sin_slavy`, `sin_slavy_poznamka`, `cestni_clenove_historicti`, `prezidenti`, `zavodni_tenis.extraliga`, `zavodni_tenis.tituly_druzstev_celkem`. Úplný seznam zasloužilých je v `clenove-sine-slavy.json`.
3. **Konfigurátor členství, přihláška a členský list (N19 + N24).**
   - **Krokovače:** hrající dospělí 0–2, nehrající 0–1, děti 0–2, zvýhodnění (do 30 let, senior, student) a záložka „Firemní“.
   - **Živá cena** z `clenstvi.hrajici`, `clenstvi.rodinne` a `clenstvi.ostatni`. Každá kombinace, kterou ceník nezná, vede na „ozveme se“.
   - Vedle ceny „Co je v ceně“ (`clenstvi.v_cene`) a časová osa „Žádost → Výbor → Platba → Karta“ (`clenstvi.proces_prijeti`).
   - **Přihláška ve 4 krocích:** osoby, adresa a kontakt, souhlasy, shrnutí.
     - U dítěte pod 15 let pole zákonného zástupce, podle Stanov.
     - „Jak jste se o nás dozvěděli“ bez předvybrané volby.
   - Po odeslání tisknutelný **„Členský list – I. Český Lawn-Tennis Klub Praha – Založen 1893“** přes `@media print`: slepotisk znaku, jméno, rok přijetí.
4. **Revue salon (N13).**
   - Blok „Právě vyšlo“: velká obálka `revue[0]`, `titulky_obalky` a tři položky z `obsah`.
   - Pás 20 let obálek.
   - Šablona článku: zlatý štítek rubriky (INTERVIEW / Z KLUBU / OSOBNOST), iniciála, velká citace a editorial s podpisem „Vladislav Šavrda, generální manažer“.
   - Data: `revue`, `revue_info`.
5. **Lockup a pečeť.**
   - Nad hlavičkou 6px pás rozdělený na `--gold` a `--navy`, na švu malý znak. Odkazuje na hlavičku Revue.
   - V patičce slepotisk `cltk-znak-mono.svg`: barva `--line` na `--paper-2` s jemným vnitřním stínem, pod ním „ZALOŽEN 1893“.
   - Na podstránce O klubu malý **„Rodokmen stoletých“** (N7): Hurlingham 1869 · Longwood (první lawn-tenisový dvorec 1878) · Queen's 1886 · West Side 1892 · **I. ČLTK 1893** · Monte-Carlo 1893 · Rot-Weiss 1897 · RCTB 1899 · Parioli 1906.

### Pořadí sekcí homepage

| # | Sekce | Obsah | Data |
|---|---|---|---|
| 1 | Hlavička | Lockup, navigace (Klub · Členství · Areál a ceník · Závodní tenis · Revue · Síň slávy · Kontakt), Rezervovat, telefon, CS/EN | `klub` |
| 2 | Hero | Panorama, H1 a dvě CTA | `fotky.hero[0]`, `klub.rezervace_url` |
| 3 | Pás čísel | Šest čísel ve vlasových rámečcích: 1893 · 1901 · 19 kurtů · 10 GS titulů v barvách klubu · 1 ze 3 SCM · jediný český člen CTC | `cisla` |
| 4 | Tři wimbledonské trávy | Signature 1 | `sin_slavy`, `kronika` |
| 5 | Z kurtů | Tři aktuality jako skórová typografie: velké serifové skóre, kolo, město, datum | `aktuality[0..5]`, `vysledky_highlights` |
| 6 | Členství | Mini-konfigurátor a čtyři výhody (bazén, wellness, CTC, Revue) → `#clenstvi` | `clenstvi` |
| 7 | Areál | Tři redakční fotky (kurty, bazén, terasa), seznam služeb s časy a letní ceník | `areal.sluzby`, `areal.oteviraci_doby`, `cenik.leto_2026` |
| 8 | Revue (navy blok) | Právě vyšlo a šest obálek | `revue` |
| 9 | Zlatá deska | Náhled záložky Čestní členové → `#sin-slavy` | `sin_slavy` |
| 10 | Prague Open 2026 | Výsledková karta: 26. ročník, vítězové, kategorie | `prague_open` |
| 11 | Partneři | Názvy v serifové kurzívě, nebo jednobarevná loga v jedné řadě | `partneri` |
| 12 | Patička | Adresa, čtyři hlavní kontakty, příjezd, CTC, pečeť, přístupnost | `klub.kontakty`, `klub.prijezd` |

**Podstránky:**

- `#clenstvi`: konfigurátor, přihláška a členský list.
- `#sin-slavy`: celá deska a dvojí metr.
- `#revue`: salon a ukázka článku.
- `#areal`: služby, ceník léto i zima, plán jako obraz.
- `#kontakty`

**Pohyb:** jen pomalé roztmívání (400–600 ms) a zbarvení v triptychu. Na desce žádné třpytky.

**Pozor:** nesmí to vypadat jako `01-cltk-luxe`. Hlavní objekty jsou tady deska, triptych a konfigurátor, a rytmus je redakční, žádné karty v mřížce.

---

## 5. Koncept B – „Ostrov v čase“ (muzeum a archiv na ostrově)

**Věta:** Web jako první digitální muzeum tenisového klubu. Návštěvník projde ostrov a sto třicet let klubu jako výstavu, se štítky a prameny, a na konci si zarezervuje kurt.

**V čem je výjimečný:**

- Žádný klub na světě nepropojuje historii, místo, lidi a archiv do jednoho příběhu (09 kap. 0).
- Mapa ostrova s posuvníkem let, kronika, ve které se mění jméno klubu, a fulltext ve 20 letech Revue jsou tři unikáty najednou.

### Paleta „Vltava a porcelán“

| Token | Hex | Použití | Kontrast na `#f7f8f6` |
|---|---|---|---|
| `--porcelan` | `#f7f8f6` | pozadí | – |
| `--porcelan-2` | `#eef0ec` | panely, vitríny | – |
| `--vltava` | `#1f3440` | text, nadpisy, tmavé bloky | 12,14 : 1 |
| `--rici` | `#4e6b72` | sekundární text, odkazy | 5,37 : 1 |
| `--rici-svetla` | `#5f7f86` | jen velký text a mapa | 4,05 : 1 |
| `--razitko` | `#a3242b` | červená ze znaku: razítko s dobovým jménem, štítky „podle klubu“ | 6,93 : 1 |
| `--zlato-tmave` | `#7a6935` | tmavá zlatá z okraje znaku: letopočty, akcent textu | 5,06 : 1 |
| `--zlato-linka` | `#89792f` | linková zlatá znaku: jen linky a velká čísla | 4,07 : 1 |
| `--linka` | `#dfe3de` | vlasové linky | – |

Archivní fotky se sázejí v **duotónu** `--vltava` → `--porcelan` (SVG filtr), aby 300–1600px snímky působily jako jednotná sbírka. Originál barevně až po kliknutí.

### Písma

| Písmo | Role | Poznámka |
|---|---|---|
| **Newsreader** (opsz 6–72) | nadpisy 300 při opsz 72, text 400 při opsz 16, kurzíva na popisky | Má `tnum`. |
| **Instrument Sans** 400–600 | UI, navigace | Osa šířky `wdth 85` na úzké štítky. |
| **Geist Mono** 400 | archivní štítky ve verzálkách, 11 px | Např. „PRAMEN · REVUE 02/2013 · S. 8“ nebo „INV. 1912“. |

Česká diakritika je ověřená u všech tří písem.

### Hero

- **Rozdělený hero:**
  - Vlevo obří „1893“ (Newsreader 300, `--zlato-tmave`) a H1 „Na ostrově se hraje od roku *1901*.“
  - Vpravo interaktivní mapa ostrova (signature 1), výchozí stav 1901: pět kurtů a klubovna.
- Pod tím řádek „Dnes: 19 kurtů · 13 antukových · 12 krytých v zimě → Rezervovat kurt“.
- Podklad pro kresbu mapy:
  - `assets/historie/hist-2013-ostrov-stvanice-z-letadla.jpg` – celý ostrov kolmo z letadla;
  - `assets/foto/plan-arealu.jpg` – rozmístění kurtů.

### Signature prvky

1. **Ostrov v čase (N4).**
   - Inline SVG ostrova ve Vltavě (obkreslená perokresba) s vrstvami `<g data-od data-do>`.
   - Ovládání: posuvník a klikací letopočty, popisek s `aria-live`.
   - **Vrstvy** (texty z `kronika[]`):
     - počátek 18. století: aréna štvanic (zakázány na počátku 19. století);
     - 1846–1850: Negrelliho viadukt;
     - 1877: Velké Benátky a zábavy (inzerát „33 divých žen, Amazonek“);
     - 1901: pět kurtů a klubovna;
     - 1926/27: dřevěný centrkurt;
     - 1929: klubovna arch. Šuly;
     - 1931–2011: zimní stadion (MS v hokeji 1947);
     - 1983: demolice;
     - 1986: nový stadion;
     - 2002: povodeň, vodorovná linka „v hale přes 4 m vody“;
     - 2023: lávka HolKa s bronzovými zajíci;
     - dnes: 19 kurtů s body z `areal.kurty[].mapa`.
2. **Kronika se čtyřmi jmény (N3).**
   - Kapitoly podle `kronika[].era`: I. Český Lawn-Tennis Klub → Motorlet → TJ Dopravní podnik → I. ČLTK Praha.
   - Lepivé **razítko** v `--razitko` s dobovým jménem se při rolování přepíná (IntersectionObserver, krátké rozostření a zaostření). Plovoucí pilulka „Kapitoly“ funguje jako navigátor, podobně jako u Rolexu.
   - Každý záznam má velký letopočet, titulek, text, fotku v rámu a **muzejní štítek pramene** v Geist Mono.
   - Pole `jistota` se ukáže jako drobný štítek „podle klubu“ nebo „ověřeno“. Poctivost je tady součástí vzhledu.
3. **Kiosek 20 let Revue a fulltext (N14 + N15).**
   - Polička 41 obálek (`revue[].cover_file`) podle roků. Při najetí nebo zaměření se obálka povytáhne a klik otevře detail čísla (obsah, strany, PDF).
   - Pole **„Hledat ve 20 letech Revue“** vrací výsledky ve tvaru „číslo · strana · úryvek“.
     - V mockupu hledá v `revue[].titulky_obalky` a `revue[].obsah`.
     - V ostré verzi poběží MiniSearch nad indexem z `_raw/revue/txt/` a odkaz povede na `pdf#page=N`.
   - Filtr **„Příběh v obálkách“**: Muchová 2018 → 2026, Hradecká, Kodeš 2006 → 2026, Bartůňková. Vondroušová (01/2010 → 02/2023) až po souhlasu klubu.
   - Detail: obálka 02/2013 je Drobný 1954.
4. **Hráli na Štvanici – stěna es (N5).**
   - Blok asi 57 jmen (06 kap. 10) v jednom typografickém bloku, `text-wrap: balance`.
   - Nadpis bez sporného čísla, nebo s dovětkem „podle Bulletinu 2023 víc než šedesát“.
   - Kliknout jde jen na jména s doloženou příležitostí, např.:
     - Borg (Davis Cup 1972)
     - Navrátilová (Pohár federace 1986)
     - Muster (Czech Open 1988)
     - Bruguera (Czech Open 1993–94)
     - Kafelnikov (Czech Open 1996)
     - Wawrinka (Prague Open 2020)
     - Safinová (WTA 2005)
     - Zvonarevová (WTA 2008)
   - Pod stěnou věta: „Členy klubu nebyli“ – Navrátilová, Lendl a další podle README.
5. **Vitrína předmětů (N9).**
   - Muzejní karty ve formátu „název · rok · technika · pramen“. Zoom v `<dialog>`.
   - Předměty:
     - zlatý odznak a stříbrná pamětní mince ke 130 letům (`hist-2023-zlaty-odznak-a-pametni-mince.jpg`);
     - obraz Tomáše Bíma (`hist-2023-obraz-tomas-bim-130-let.jpg`);
     - korespondenční lístek 1902 (`hist-1902-pohlednice-micovy-turnaj.jpg`);
     - inzerát Velkých Benátek (`hist-1800s-inzerat-velke-benatky-amazonky.jpg`);
     - první Revue 01/2006 (`revue-2006-1.jpg`);
     - deska Drobného – bez fotky, štítek „doplní klub“.
6. **Tehdy a teď (N11).**
   - Posuvník dvou snímků přes `clip-path` a `<input type=range>`.
   - Pár pro mockup: `hist-2002-povoden-zaplaveny-areal.jpg` × `letecky-zimni-haly-shora.jpg`. Oba jsou šikmé letecké záběry se stadionem a viaduktem.
   - Druhý pár: `hist-1912-turnaj-pod-negrelliho-viaduktem.jpg` × dnešní oblouky, fotku dodá klub.

**Detaily:**

- Liniový **zajíc** jako tichý symbol ostrova: jméno od štvanic, bronzoví zajíci na lávce HolKa. Použít v patičce, na stránce 404 a v loaderu.
- Perokresby klubovny z roku 1929 a oblouků viaduktu pro prázdné stavy (N20).
- Citát v patičce: „…vše v krásné staré zahradě uprostřed Prahy.“ (Tennis Golf Revue 1929).

### Pořadí sekcí homepage

| # | Sekce | Obsah | Data |
|---|---|---|---|
| 1 | Hlavička | Znak, „I. Český Lawn-Tennis Klub Praha“, navigace (Kronika · Ostrov · Revue · Hráli zde · Klub dnes · Návštěva), Rezervovat, telefon | `klub` |
| 2 | Hero „Ostrov v čase“ | Signature 1 v kompaktní podobě | `kronika`, `areal.kurty` |
| 3 | Klub dnes | Tři řádky s odkazy: 19 kurtů · členství od 9 500 Kč · Tenisová škola 3–9 let | `cisla`, `clenstvi.hrajici`, `zavodni_tenis.pyramida[0]` |
| 4 | Kronika | Čtyři kapitoly, osm milníků, razítko | `kronika` |
| 5 | Tehdy a teď | Signature 6 | `fotky.historie_nejlepsi` |
| 6 | Hráli na Štvanici | Signature 4 | README, 06 kap. 10 |
| 7 | Síň slávy | Osm medailonů v duotónu se štítkem pramene | `sin_slavy` |
| 8 | Kiosek Revue | Signature 3 | `revue`, `revue_info` |
| 9 | Vitrína | Signature 5 | `fotky` |
| 10 | Z ostrova | Čtyři aktuality jako depeše s datem v Geist Mono | `aktuality` |
| 11 | Návštěva | Plán areálu, příjezd, kontakty | `areal.plan`, `klub.prijezd`, `klub.kontakty` |
| 12 | Patička | Citát 1929, zajíc, přístupnost | `citaty` |

**Podstránky:**

- `#kronika`: všech 38 záznamů.
- `#ostrov`: mapa přes celou obrazovku.
- `#revue`: kiosek a detail čísla s obsahem.
- `#hraci`: stěna es, síň slávy a dvojí metr.
- `#navsteva`

**Pohyb:** jen tam, kde nese význam (posuvník, razítko, polička). Jinak statická sazba jako v katalogu výstavy.

**Pozor:** i muzeum musí být klubový web. Lišta „Rezervovat · 608 974 974 · Členství“ je lepivá a sekce „Klub dnes“ stojí hned pod herem.

---

## 6. Koncept C – „Živá Štvanice“ (antuka, provoz, výsledky)

**Věta:** Luxus jako servis. Web ví, jaký je dnes stav areálu, kolik stojí hodina v hale, kde štvaničtí hráči právě hrají a co klub čeká, a všechno to podá klidně jako concierge velkého hotelu.

**V čem je výjimečný:** v ČR nikdo nemá živý stav areálu, mapu areálu s přepnutím léto / zima, ceník jako kalkulačku ani vysázenou výsledkovou tabuli. Ve světě to má jen napůl Longwood („Court Updates“) a RCTB (teplota bazénu).

### Paleta „Antuka a len“

| Token | Hex | Použití | Kontrast na `#f6f1ea` |
|---|---|---|---|
| `--len` | `#f6f1ea` | pozadí | – |
| `--len-2` | `#efe7dc` | panely, karty | – |
| `--inkoust` | `#23201c` | text | 14,43 : 1 |
| `--antuka` | `#9b4a2a` | jediný akcent: CTA, antukové kurty, zvýraznění | 5,49 : 1 (bílá na antuce 6,16 : 1) |
| `--antuka-svetla` | `#e9cdbb` | plochy antuky v mapě, čipy | – |
| `--olivova` | `#5e6b4e` | listy ze znaku a topoly: stav „otevřeno“, sekundární akcent | 5,07 : 1 |
| `--hard` | `#2f5d8a` | jen značky tvrdých kurtů a hal (plán klubu kóduje tvrdé kurty modře) | 6,12 : 1 |
| `--seda` | `#6e675d` | popisky | 4,97 : 1 |
| `--lajna` | `#ffffff` | 2px „lajny“ jako oddělovače, nikdy jako pozadí stránky | – |

**Sezónní kůže (N23):**

- Od 28. 9. do 4. 4. (`areal.sezona_2026_27`) se hero a mapa přepnou do zimy: bílé haly, akcentem je `--hard`, antuka se utlumí.
- V prosinci přibude „extraligový mód“ se soupiskou (08 N11).

### Písma

| Písmo | Role | Poznámka |
|---|---|---|
| **Fraunces** (opsz 144, wght 300–400, SOFT 0, WONK 0) | nadpisy | Teplý, ale ukázněný serif. Odpovídá Muchové: „Je rodinný.“ |
| **Figtree** 400–600 | UI, text, **všechna čísla** | Skóre a ceny v `tnum`, protože Fraunces `tnum` nemá. |

Česká diakritika je u obou ověřená. Záložní dvojice je EB Garamond + Manrope („Hermès“), rovněž ověřená.

### Hero

- **Foto:** přes celou šířku `assets/foto/prague-open-2026-stin-hrace-antuka.jpg` – stín hráče na antuce s bílou čarou.
  - Alternativa: `prague-open-2025-stin-site-antuka.jpg`.
- **Kredit:** © Sekyra Group Prague Open 2026, foto Martin Sidorják a Jan Pecha. Práva ověřit.
- **H1:** „Antuka uprostřed Prahy.“
- **Podtitul:** „13 antukových kurtů v létě, 12 krytých v zimě. Venkovní dvorce pro veřejnost denně 7:00–22:00.“
- **CTA:** velké „Rezervovat kurt“ v `--antuka` a vedle telefon 608 974 974.
- Na herovém poli **stopa míčku** (N22): klik zanechá oválný otisk, který za 1,5 s zmizí. Jen bez `prefers-reduced-motion`.

### Signature prvky

1. **Dnes na Štvanici (N17).** Úzký pruh nad herem. Položky:
   - datum;
   - sezóna;
   - uzavírky (`areal.uzavirky_2026`);
   - wellness dnes 16:00–20:00;
   - bazén (sezónně květen–září);
   - počasí (Open-Meteo, souřadnice doplnit);
   - západ slunce (SunCalc) s poznámkou „světla jen na kurtech 2, 3, 4“.
   - Mockup ukazuje skutečný stav k 23. 9. 2026: „Stavba zimních hal (21.–28. 9.) · Tenisová škola nehraje · pevná hala od 28. 9., přetlakové haly od 5. 10.“
   - V ostré verzi stav mění recepce v malém JSON. Kdo to bude dělat, je otázka pro klub.
2. **Mapa areálu Léto / Zima.**
   - Základem je SVG překreslené z `plan-arealu.jpg` (nebo plán samotný) s body z `areal.kurty[].mapa` a `areal.plan.dalsi_prvky`.
   - Ťuknutí na kurt otevře kartu: číslo, povrch v létě a v zimě, cena z `cenik`, „jen pro členy“ u kurtu 5, odkaz na rezervaci.
   - Přepínač **Léto / Zima**:
     - kurty 1–9, C, P1 a P2 se v zimě „zabalí“ do hal;
     - Slavoj 10–16 zešedne s popiskem „v zimě uzavřeno“;
     - letecká fotka se prolne z `letecky-kurty-leto-shora.jpg` na `letecky-zimni-haly-shora.jpg` (`areal.leto_zima_fotky`).
3. **Ceník jako kalkulačka.**
   - Výběr haly (3), pásma a volby „jednotlivě / předplatné na 26 nebo 27 týdnů“.
   - Přepínač **„Jsem člen“** ukáže úsporu. Příklad: antuka, všední den 14–21 h stojí 730 Kč, pro člena 590 Kč, úspora 140 Kč za hodinu. Předplatné stojí 17 160 Kč, pro člena 13 780 Kč, úspora 3 380 Kč.
   - Pod kalkulačkou letní ceník, doplňkové služby a odkaz na členství.
   - Data: `cenik.zima_2026_27`, `cenik.leto_2026`, `cenik.doplnkove_sluzby`.
4. **„Štvanice právě hraje“ – slonovinová výsledková tabule (N21).**
   - Řádky ve tvaru: město · turnaj · kolo · hráč (Fraunces) vs. soupeř · sety v tabulkových sloupcích (Figtree `tnum`).
   - Vedle jemná mapa světa se zlatými body měst (Dauhá, Bad Homburg, Figueira da Foz, Memphis, New York…).
   - V mockupu nadpis „Poslední výsledky“ a data z `vysledky_highlights` (skutečná skóre).
   - Živý feed Resultina je věc budoucnosti, API **NEOVĚŘENO**.
5. **Cesta Štvanicí (03 nápad 1).**
   - Svislá cesta v pěti krocích:
     - Tenisová škola (3–9 let)
     - TSM (10–14)
     - SVT (15–18)
     - SCM (15–21)
     - extraliga, WTA a ATP
   - U každého kroku věk, počty hráčů (14 + 2, 16 + 2, 9 + 2), hlavní trenér s jednotným portrétem (`treneri[].foto_file`) a věta „1 ze 3 klubů v ČR s celou pyramidou“.
   - Na konci čísla 26 reprezentantů · 4 z TOP 10 žen · 121 hráčů v žebříčcích.
   - Data: `zavodni_tenis.pyramida`, `treneri`, `cisla`, `cisla_rezerva`.
6. **Ciferník sezóny (N18).**
   - SVG ciferník roku s ručičkou na dnešním datu.
   - Oblouky:
     - haly 28. 9. / 5. 10. – 4. 4.
     - Conseq Prague Open v únoru
     - klubové dny v květnu a září
     - Valná hromada v červnu
     - kempy 29. 6. – 28. 8.
     - Prague Open v srpnu
     - CTC U14 v listopadu
     - prosinec: Mikuláš, Večer talentů, Vánoční večírek a extraliga
   - Pod ciferníkem seznam `<ol>` pro čtečky.
   - Termíny 2026 po 23. 9. nejsou zveřejněné, proto štítek „termín doplní klub“.
   - Data: `akce.pravidelne`, `akce.nadchazejici`, `akce.rocni_cyklus_poznamka`.
7. **CTC cestovní pas (N8).**
   - Výhoda členství: „Váš členský průkaz platí ve stoletých klubech po celém světě“. Hra zdarma v klubech CTC po doporučení generálního manažera.
   - Razítka doložených návštěv a utkání:
     - RCT Barcelona-1899
     - Villa Primrose Bordeaux
     - Fitzwilliam LTC a Carrickmines, Dublin
     - HLTC Leimonias, Haag
     - AELTC na Štvanici, 6.–8. 6. 2025
     - CTC Winners Group 2025 na Štvanici (Padova)
   - Razítka jsou SVG pootočená o −6° s maskou šumu.
   - Data: `klub.clenstvi_v_organizacich`, README.

### Pořadí sekcí homepage

| # | Sekce | Obsah | Data |
|---|---|---|---|
| 1 | Dnes na Štvanici | Signature 1 | `areal.sezona_2026_27`, `areal.uzavirky_2026`, `areal.oteviraci_doby` |
| 2 | Hlavička a hero | Antuka, H1, velké CTA, stopa míčku | `fotky.hero`, `klub` |
| 3 | Rychlá volba | Tři textové odkazy (ne dlaždice): Rezervace · Ceník · Členství | `klub.rezervace_url` |
| 4 | Mapa areálu Léto / Zima | Signature 2 | `areal.kurty`, `areal.plan`, `cenik` |
| 5 | Štvanice právě hraje | Signature 4 | `vysledky_highlights`, `aktuality` |
| 6 | Cesta Štvanicí a trenéři | Signature 5 | `zavodni_tenis`, `treneri` |
| 7 | Ceník – kalkulačka a členství | Signature 3 a ceny členství | `cenik`, `clenstvi` |
| 8 | Ciferník sezóny | Signature 6 a tři nejbližší události | `akce` |
| 9 | Tenisová škola a kempy | Šest termínů, varianty A a B, cena pro hráče klubu a pro ostatní, přihláška | `cenik.letni_kempy_2026`, `cenik.tenisova_skola` |
| 10 | Klubový život a CTC | Galerie (130 let, Vánoční večírek, CTC senior), citát Muchové, pas | `fotky.galerie.klubovy_zivot`, `citaty`, signature 7 |
| 11 | Aktuality | Šest položek jako výsledkový seznam | `aktuality` |
| 12 | Partneři a patička | Jednobarevný pás log, kontakty, přístupnost | `partneri`, `klub.kontakty` |

**Podstránky:**

- `#areal`: mapa přes celou obrazovku a služby s časy.
- `#cenik`: kalkulačka, tabulky a členství.
- `#zavodni-tenis`: cesta, extraliga 2018–2025, soupiska 2025, mřížka 12 trenérů, výsledková tabule.
- `#tenisova-skola`: mini-web s ceníkem zima a léto, kempy, harmonogramem bez jmen dětí a náborem.
- `#kalendar`: ciferník a seznam.

**Pohyb:** stopa míčku, prolnutí léto / zima, ručička ciferníku. Všechno se vypíná přepínačem „Omezit pohyb“.

**Pozor:** živé prvky nesmí působit jako sportovní portál. Žádné blikání „LIVE“, žádné červené tečky, žádná konfety. Tabule je slonovinová, klidná a vysázená jako program koncertu.

---

## 7. Matice: který obsah nese který koncept

`●` = nosná sekce · `○` = menší zmínka nebo podstránka · `–` = nepoužito

| Klíč v `obsah.json` | A Zlatá deska | B Ostrov v čase | C Živá Štvanice |
|---|---|---|---|
| `cisla`, `cisla_rezerva` | ● pás čísel | ○ Klub dnes | ● cesta Štvanicí |
| `sin_slavy`, `cestni_clenove_historicti`, `prezidenti` | ● triptych a deska | ● medailony, stěna es | ○ `#zavodni-tenis` |
| `kronika`, `nazvy_klubu` | ○ O klubu | ● mapa a kronika | – |
| `revue`, `revue_info` | ● salon | ● kiosek a fulltext | ○ odkaz |
| `clenstvi` | ● konfigurátor a přihláška | ○ řádek | ● ceny u kalkulačky |
| `cenik` | ○ `#areal` | ○ Klub dnes | ● kalkulačka |
| `areal.kurty`, `areal.plan` | ○ plán jako obraz | ● podklad mapy | ● interaktivní mapa |
| `areal.sluzby`, `areal.oteviraci_doby` | ● Areál | ○ Návštěva | ● karty mapy, Dnes na Štvanici |
| `zavodni_tenis`, `treneri` | ○ | – | ● |
| `aktuality`, `vysledky_highlights` | ● skórová typografie | ○ depeše | ● výsledková tabule |
| `akce` | ○ | – | ● ciferník |
| `prague_open` | ● výsledková karta | ○ stěna es, kronika | ○ ciferník |
| `citaty` | ○ | ● patička, kronika | ○ klubový život |
| `partneri` | ○ text | ○ patička | ○ pás |
| `fotky.hero` | panorama Prahy | ostrov 2013 (podklad mapy) | stín na antuce |

---

## 8. Kontrola před odevzdáním (každý koncept)

- [ ] Každé číslo, jméno a cena odpovídá `obsah.json`. Nic není vymyšlené a chybějící údaje mají štítek „doplní klub“.
- [ ] Formulace podle `klub.nepouzivat`: žádné „nejstarší v ČR“, žádné „11 v řadě“, Drobný a Kodeš „ve štvanické historii“.
- [ ] Vondroušová má typografickou záložní verzi a nikde není hlavní tváří. Chybí tresty, pragueopen.org i jména dětí.
- [ ] Render „ŘÍJEN NA ŠTVANICI – ŽEMLA · KOŽELUH · ŮČTY ĎÁBLA, ŤUK“ je v pořádku ve všech nadpisových řezech.
- [ ] Na 1440 px, v mobilním zobrazení a ve WebKitu bez chyb, bez 404 a bez vodorovného přetečení.
- [ ] Rezervace a telefon jsou vidět vždy, i na mobilu.
- [ ] Klávesnice, focus stavy, `prefers-reduced-motion`, přepínače „Omezit pohyb“ a „Vyšší kontrast“ a alt texty fungují.
- [ ] Na jedné obrazovce je jeden akcent a nejvýš dva tmavé bloky na stránku. Žádný stock, žádný slider s tečkami.
- [ ] Historické fotky jsou v rámech a s popiskem pramene. Agenturní fotky mají kredit.

## 9. Otázky pro klub (ovlivňují návrh)

1. Jak prezentovat Markétu Vondroušovou: portrét, triptych a název Tenisové školy?
2. Je k dispozici 3D znak ve vyšším rozlišení nebo jako vektor? Dnes má jen 402×445 px.
3. Kdo bude aktualizovat „Dnes na Štvanici“? Lze napojit RogerOnline nebo onlinehq a výsledky Resultina?
4. Fotky:
   - společná fotografie 130 let v plném rozlišení pro hloubkový zoom, N10;
   - předměty do vitríny;
   - portréty čestných členů;
   - dnešní snímky ze stejných úhlů jako historické;
   - vlastní fotky areálu ve vysokém rozlišení.
5. Roky 12 titulů Motorletu. Seznam zasloužilých členů včetně Ivo Mináře.
6. Termíny akcí od října 2026 a otevírací doby areálu, recepce a restaurace Tiebreak.
7. Souhlas s podobou přihlášky podle Stanov a s „Členským listem“.
