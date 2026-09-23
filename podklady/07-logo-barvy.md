# 07 – Klubový znak I. ČLTK Praha a barvy značky

Stav: 23. 9. 2026. Výzkum a příprava podkladů, žádný návrh webu.

## Shrnutí

- **Znak je k dispozici jako originální vektor.** Web cltk.cz má jen malé PNG (194×209 px). V PDF Klubové revue je ale znak vložený jako originální soubor z Illustratoru, tedy jako vektorové křivky: `CLTK_logo_varianty_k.eps`, Adobe Illustrator CS5 / 15.0, autor v metadatech „Katerina“, XMP CreateDate 2014-09-23. Křivky jsem z PDF vytáhl beze ztráty. SVG i PNG tedy vycházejí přímo z originálu, žádnou rekonstrukci jsem nekreslil.
- **Nejlepší soubor:** `assets/logo/cltk-znak.svg` (vektor). Pro rastr slouží `assets/logo/cltk-znak.png` (1396×1600 px).
  - Průhlednost je skutečná, protože PNG se renderuje rovnou z vektoru s alfa kanálem. Odstraňovat pozadí tedy nebylo potřeba. Nejsou tam halo efekty ani díry, ověřeno na tmavě modrém, bílém, zlatém i černém podkladu.
- **Znak existuje ve třech oficiálních barevných provedeních** a všechna jsem vytáhl:
  1. **se zlatým okrajem a zlatými linkami.** Tiskoviny ji používají na obálkách Revue a na pozvánce ke 125 letům. Tuto variantu jsem uložil jako hlavní `cltk-znak.*`, protože se nejlépe hodí ke světlému luxusnímu webu.
  2. **s černým okrajem a černými linkami** (`CLTK_logo_black_line_k.eps`). Tuto variantu dnes používá web cltk.cz (logo.png), Wikimedia a Revue 02/2024 na stránkách Výbor klubu a Trenéři.
  3. **jednobarevná** (v tiskovinách bílá na olivově zlatém podkladu), na straně partnerů v Revue.
- **Pořadí variant je moje rozhodnutí.** Která varianta je „primární“ podle klubového manuálu, se mi ověřit nepodařilo (NEOVĚŘENO). Manuál jsem nenašel.

## Výstupní soubory

### Zadané výstupy (`C:/Users/Asus/cltk-navrhy/assets/logo/`)

| soubor | rozměr | velikost | poznámka |
|---|---|---|---|
| `cltk-znak.svg` | viewBox `0 0 68.01 77.925` (pt), výchozí 544×623 | 35 499 B | čistý vektor bez rastru. Zlatý okraj je `radialGradient` přepočtený z PDF shadingu. Edge ho vykreslí shodně s referenčním renderem MuPDF (průměrný rozdíl < 1/255, liší se jen antialiasing hran). |
| `cltk-znak.png` | 1396 × 1600 px | 457 784 B | RGBA, průhledné pozadí, oříznuto na těsno |
| `cltk-znak-512.png` | 447 × 512 px | 132 978 B | zmenšeno z 4× supersamplu v premultiplied alfě (LANCZOS) |
| `cltk-znak-128.png` | 112 × 128 px | 24 179 B | dtto |

Poměr stran znaku je šířka : výška = 0,873 : 1 (68,01 × 77,925 pt).

### Doplňkové varianty (`C:/Users/Asus/cltk-navrhy/podklady/_raw/logo/export/`)

Nebyly v zadání, takže leží v `_raw` (je git-ignored). Kdo je chce použít, musí je zkopírovat do `assets/logo/`.

| soubor | rozměr | poznámka |
|---|---|---|
| `cltk-znak-cerne-linky.svg` | viewBox `0 0 34.17 39.165` | varianta s černými linkami (jako web cltk.cz), čistý vektor |
| `cltk-znak-cerne-linky.png` | 1396 × 1600 px | RGBA průhledné |
| `cltk-znak-cerne-linky-512.png` | 447 × 512 px | |
| `cltk-znak-cerne-linky-128.png` | 112 × 128 px | |
| `cltk-znak-mono.svg` | viewBox `0 0 71.04 81.435` | jednobarevný znak, `fill="currentColor"`. Při vložení inline se barví přes CSS `color` (zlatá, navy, bílá…). Křivky jsou z Revue 01/2026, PDF str. 2. |
| `cltk-znak-mono-bily.png` | 1396 × 1600 px | bílý jednobarevný znak na průhledném pozadí |
| `cltk-znak-mono-bily-512.png` | 447 × 512 px | |

Jednobarevná varianta má o něco jiný obrys (vnější bílý lem kolem štítu), proto má jiný viewBox.

### Soubor, který jsem nevytvářel

V `assets/logo/` už ležely soubory `cltk-znak-3d*.png` a `favicon-32/64.png` (čas 17:07). Je to 3D „kovový“ odznak. Jejich původ jsem neověřoval (NEOVĚŘENO) a nejsou to oficiální podklady klubu. Nechal jsem je beze změny.

### Skripty pro znovusestavení (`podklady/_raw/logo/work/`)

- `extract_emblem.py`: z obsahu stránky PDF vyřízne sekci `/EmbeddedDocument /MCx BDC … EMC` (vložené EPS) a uloží ji jako samostatnou stránku. Na stránce pak zůstane jen znak, bez fotky a pásů pod ním.
- `build_logo.py`: provede export SVG přes MuPDF, nahradí rastrovaný shading za `radialGradient` a vyrenderuje PNG s alfou.
- `compare_edge.sh`: vyrenderuje SVG v headless Edge a porovná ho po pixelech s PNG.
- Mezisoubory: `znak-gold-r26.pdf`, `znak-blackline-r242.pdf`, `znak-mono-redraw.pdf`.

## Prověřené zdroje a výsledky

### 1. Web cltk.cz / files.cltk.cz

- `https://files.cltk.cz/site/logo.png`: 194×209 px, RGBA, 72 dpi. Jde o variantu s **černými linkami** a vrženým stínem dole. Používá se v hlavičce i v patičce CS a EN webu (`alt="I. Český LAWN - Tenis Klub Praha"`).
- `https://files.cltk.cz/site/style.css`: žádný odkaz na logo. Odkazuje jen na `comment-alert-outline.svg`, `map-desktop.jpg` a `map-mobile.jpg`.
- EN web `https://cltk.cz/en/` používá stejné `logo.png`.
- `og:image` na `https://cltk.cz/cs/` je fotografie (`https://files.cltk.cz/nli7rr5kh08/P6260023.jpg baz 26.jpg`), ne logo.
- `https://cltk.cz/favicon.ico` má jen 16×16 px. `apple-touch-icon*.png` a `favicon.png` vracejí 404.
- Zkoušel jsem v `files.cltk.cz/site/` tyto názvy: `logo.svg`, `logo@2x.png`, `logo-big.png`, `logo_big.png`, `logo-large.png`, `logo2.png`, `logo-white.png`, `logo.jpg`, `logo.pdf`, `logo.ai`, `logo.eps`, `znak.png`, `znak.svg`, `favicon.ico`, `favicon.png`, `apple-touch-icon.png`, `logo-cltk.png`, `cltk-logo.png`, `cltk.png`, `logo_cltk.png`, `logo-hd.png`. Všechny vracejí 404.
  - Existují jen `logo-01.png` (249×40, Babolat) a `logo-02.png` (291×41, Smartwings). Obojí jsou loga partnerů, ne znak klubu.

### 2. Web mimo klub

- **Wikimedia Commons:** `File:Logo_I._ČLTK_Praha.png`, 470×470 px, RGB na bílém pozadí, varianta s černými linkami.
  - URL: https://upload.wikimedia.org/wikipedia/commons/9/95/Logo_I._%C4%8CLTK_Praha.png
  - Podle stránky souboru je zdrojem fotka z facebookového profilu klubu. Nahrál ji uživatel Xgeorg 8. 4. 2020. Licence „PD-textlogo“ s upozorněním na ochrannou známku.
  - Soubor je používaný v infoboxu anglické Wikipedie https://en.wikipedia.org/wiki/I._%C4%8CLTK_Prague.
  - Barvy v něm odpovídají standardnímu převodu CMYK→RGB v Adobe: #2E3192, #ED1C24, #00A651, #231F20.
- **pragueopen.net** (Wix): černý jednobarevný znak na bílém pozadí, 152×152 px.
  - URL: https://static.wixstatic.com/media/41962f_58b63e72c3074090a071316d7604c6c9~mv2.png
- **cztenis.cz:** přes adresář klubů (`/adresar-klubu`) jsem stránku klubu s logem nenašel (NEOVĚŘENO).
- **Facebook a Instagram:** nezkoumal jsem, protože se bez přihlášení nedají stáhnout (NEOVĚŘENO).
- Všechny tyto zdroje jsou rastry nižší kvality než vektor z Revue.

### 3. PDF Klubové revue – vektorový originál (nejlepší zdroj)

- **Revue 01/2026**, https://files.cltk.cz/2so4wzo52sl01/REVUE_2026_1_web.pdf (13 270 760 B), obálka (PDF str. 1):
  - Znak je vektorový, vložený jako `CLTK_logo_varianty_k.eps` (`/Creator (Adobe Illustrator(R) 15.0)`, `/Author (Katerina)`).
  - Sedí v hlavičce „I.ČLTK [znak] REVUE“ na rozhraní zlatého a tmavě modrého pásu.
  - Tvoří ho 56 vektorových cest a jeden radiální shading (zlatý okraj).
  - Tento zdroj je podkladem pro `cltk-znak.*`.
- **Revue 01/2026, PDF str. 2** (strana „I.ČESKÝ LAWN-TENNIS KLUB PRAHA / OFICIÁLNÍ PARTNEŘI“):
  - bílý jednobarevný znak s textem „ZALOŽEN 1893“ na olivově zlatém podkladu, 32 vektorových cest.
  - Tento zdroj je podkladem pro `cltk-znak-mono.*`.
- **Revue 02/2025**, https://files.cltk.cz/o6xjsls6zgj01/2025_2_web.pdf (13 675 082 B):
  - obálka má stejný vložený `CLTK_logo_varianty_k.eps`;
  - PDF str. 2 má stejnou jednobarevnou stranu partnerů.
- **Revue 02/2024**, https://files.cltk.cz/fa4cnmbc2qc01/REVUE_2024_2_web.pdf (lokálně `_raw/revue/pdf/revue-2024-2.pdf`):
  - tištěná s. 18 „Výbor klubu“ (PDF str. 20) a s. 19 „Trenéři klubu“ obsahují malý znak s textem „ZALOŽEN 1893“;
  - znak je vložený jako **`CLTK_logo_black_line_k.eps`** (varianta s černými linkami);
  - tento zdroj je podkladem pro `cltk-znak-cerne-linky.*`.
- **Revue 01/2023 a 02/2023** obsahují navíc **`130_CLTK-gold_1.eps`**. Je to výroční značka: zlatý kruh s číslem „130“ a pod ním text „130 LET I.ČLTK PRAHA“.
  - V 01/2023 je na obálce, na s. 1 (editorial) a na s. 10 („SLAVÍME V ČERVENCI!“).
  - Vytáhl jsem ji do `_raw/logo/work/130-gold-r231.pdf`, ale neexportoval.
- **Kde všude je `CLTK_logo_varianty_k.eps`** (sken lokálních PDF v `_raw/revue/pdf/` a `_raw/historie/`):
  - Revue 2017-1, 2017-2, 2018-1 až 2020-2, 2021-2, 2022-1, 2022-2, 2023-1, 2023-2, 2024-1, 2024-2, 2025-1, 2025-2 a 2026-1;
  - dále `pozvanka-125.pdf` a `revue-1893-2023.pdf`.
  - Revue z let 2006–2015 a 2021-1 vložený soubor s tímto názvem nemají. Jestli v nich je znak jako rastr, jsem nezkoumal.
- **Metadata `CLTK_logo_varianty_k.eps`** (XMP v Revue 01/2026):
  - `dc:title` = „CLTK_logo_varianty“, CreatorTool „Adobe Illustrator CS5“, CreateDate 2014-09-23T15:55:23+02:00.
  - Historie: uloženo 2014-04-27 a 2014-07-09 v Illustratoru CS6 (Macintosh), 2014-09-23 v Illustratoru CS5.
  - Artboard 209,90 × 297,04 mm (A4). XMP náhled (184×256 px) ukazuje na artboardu jen tento jediný znak.

## Barvy značky (přesně ze zdrojového vektoru)

Znak je v PDF definovaný čistými procesními CMYK barvami. Uvádím:

- **CMYK** přesně podle PDF;
- **sRGB přes MuPDF (ICC):** takhle se barvy vykreslí v našich souborech, jsou v SVG;
- **sRGB podle Adobe:** standardní převod, stejné hodnoty má i obrázek na Wikimedia;
- **web cltk.cz:** barvy naměřené z dnešního `logo.png`, které jsou zkreslené kompresí.

| prvek | CMYK (z PDF) | sRGB přes MuPDF (ICC) | sRGB podle Adobe (Wikimedia) | web `logo.png` |
|---|---|---|---|---|
| **modrá** (levý pruh štítu, pruh na levém míči) | C100 M100 Y0 K0 | **#2E3092** | #2E3192 | #312B81 |
| **červená** (pravý pruh, pruh na pravém míči) | C0 M100 Y100 K0 | **#ED1C24** | #ED1C24 | #E10F21 |
| **zelená** (listy, stonky, bobule) | C100 M0 Y100 K0 | **#00A650** (render #00A550) | #00A651 | #009544 |
| **zlatá linková** (písmo „I. ČLTK“, rakety, obruče míčů) | C40 M39,6 Y96,5 K21 | **#89792F** (render #88782E) | – | – |
| **zlatý okraj, světlá** (střed až 62,8 % poloměru přechodu) | C24,4 M41,5 Y96,5 K7,5 | **#B88C30** | – | – |
| **zlatý okraj, tmavá** (okraj přechodu, za ním stejná) | C55 M55 Y96,5 K13 | **#7A6935** | – | – |
| **černá** (varianta s černými linkami) | C0 M0 Y0 K100 | #221F1F | #231F20 | #1D1D1B |
| bílá (střední pruh, lem) | C0 M0 Y0 K0 | #FFFFFF | #FFFFFF | #FFFFFF |

Jak je zlatý okraj postavený:

- Je to radiální přechod: `ShadingType 3`, stitching funkce s `Bounds [0.628348 1]`.
- Střed leží ve středu znaku, poloměr je 36,59 pt při výšce znaku 77,9 pt.
- Na bocích štítu je proto okraj světlejší (blíž k #B88C30) a nahoře, dole a v rozích tmavý (#7A6935).
- SVG používá tyto zastávky: 0 a 0,6283 → #B88C30, dál plynule přes 5 mezikroků, 1 → #7A6935.

### Barvy tiskovin Revue kolem znaku (pro inspiraci paletou)

| prvek | CMYK (z PDF) | sRGB (MuPDF) |
|---|---|---|
| zlatý pás hlavičky Revue (za „I.ČLTK“) | C30 M31 Y80 K1 | **#B9A355** |
| tmavě modrý pás hlavičky Revue (za „REVUE“) | C100 M72 Y0 K56 | **#00275D** |
| olivově zlatý podklad strany partnerů (bílý znak) | C48 M46 Y70 K18 | **#7D7254** |

Zdroj: Revue 01/2026, obálka a PDF str. 2. Hodnoty jsou z operátorů `k` v obsahu stránky.

## Doporučení pro použití (technická)

- **Do HTML dávat SVG** (`cltk-znak.svg`). Je ostré v jakékoli velikosti a má 35 kB. Pro og:image a e-maily použít PNG.
- **Znak má vlastní bílé pole.** Na světlém pozadí (krémová, bílá) vynikne hlavně zlatý okraj, na tmavé navy působí jako odznak. Obojí je ověřené renderem.
- Ve 128 px jsou všechny detaily ještě čitelné (ověřeno renderem). Pro menší velikosti (favicon) jsem nic netestoval (odhad, NEOVĚŘENO). Tam bude asi lepší jednobarevná varianta.
- **Jednobarevné SVG** (`cltk-znak-mono.svg`, currentColor) se vloží inline a obarví přes CSS, například `color:#89792F` na krémové nebo `#FFFFFF` na fotce či navy.

## Nápady pro design (z nalezeného materiálu)

- **Zlatý „slepotisk“ znaku:** jednobarevný znak (`cltk-znak-mono.svg`) v tónu #89792F na krémovém papíře. Klub sám používá jednobarevnou variantu v tiskovinách (bílá na olivově zlaté). Hodí se jako vodoznak, pečeť v patičce nebo ražba na kartě člena.
- **Hlavička Revue jako motiv hlavičky webu:** „I.ČLTK [znak] REVUE“ na rozděleném pásu zlatá #B9A355 | navy #00275D, znak sedí přesně na švu. Je to autentický klubový lockup, který web dnes nemá.
- **Zlatý kov ze znaku:** radiální přechod okraje #B88C30 → #7A6935 lze použít na tenké linky, rámečky a čísla titulů. Barvy odpovídají samotnému znaku, nejsou vymyšlené.
- **Trikolora štítu** (modrá | bílá | červená, svislé pruhy) jako decentní akcent, například 3px svislý proužek u nadpisů sekcí.
- **Lockup „ZALOŽEN 1893“ pod znakem:** takhle ho klub sám sází v Revue (strana partnerů, Výbor klubu, Trenéři).
- **Míčky s vlnitým pruhem** (modrý a červený) a **zelené listy** jako drobné ikonky nebo odrážky, případně animovaný načítací prvek.
- **Výroční značka „130“** (zlatý kruh, 2023) ukazuje, že klub používá tenké bezpatkové zlaté písmo na výročí.

## Nedohledáno a nejistoty

- Originální `.ai` nebo `.eps` jsem jako samostatný soubor nenašel. Máme jen jeho vložené provedení v PDF. Vektor je ale úplný (cesty i přechod).
- Grafický manuál klubu a údaje o Pantone jsem nenašel. Známe jen CMYK hodnoty z PDF. Převod do sRGB závisí na ICC profilu a u modré se liší o jednotky (#2E3092 v MuPDF proti #2E3192 v Adobe).
- Která varianta (zlaté nebo černé linky) je podle klubu „hlavní“, je NEOVĚŘENO.
- Facebook, Instagram a stránku klubu na cztenis.cz jsem neprověřil (NEOVĚŘENO).
- Původ souborů `assets/logo/cltk-znak-3d*.png` je NEOVĚŘENO. Nejsou z tohoto kroku.
