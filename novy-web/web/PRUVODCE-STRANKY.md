# Jak postavit podstránku webu I. ČLTK Praha

Návod pro všechny, kdo staví stránky webu (`klub.php`, `areal.php`, `historie.php` …).
Šablona (hlavička, menu, informační lišta, patička, lepivá lišta na mobilu), designový systém
a úvodní stránka jsou hotové. **Nic nového nevymýšlejte – skládejte z tříd a pomůcek níže**
(jazyk Varianty 4 „Zlatá deska“). Data a sloupce: `web/sql/SCHEMA.md`, datové pomůcky jádra:
`web/inc/PRUVODCE-FRONT.md`, závazné zadání: `ZADANI.md`.

Živé ukázky: `index.php` (sekce, karty, pás výsledků, kalendář, video, pilulky, partneři),
`akce.php` (hlava stránky, formulář, hlášky), `404.php`.

---

## 1. Kostra stránky

```php
<?php
/* Areál a služby – přehled areálu, služby s kotvami, plán, příjezd. */
require __DIR__ . '/inc/rezim.php';                       // PRVNÍ řádek: jádro + režim přípravy
require_once __DIR__ . '/inc/sablona/komponenty.php';     // pomůcky šablony (hlava_stranky, cenik_html…)
track_visit();                                            // návštěvnost bez cookies

$uvod   = blok('areal', 'uvod');
$sluzby = sluzby();
// … všechna data, zpracování POST, header() / redirect() PŘED jakýmkoli výstupem …

$sablona = [
    'titulek' => 'Areál a služby',             // <title>: „Areál a služby – I. ČLTK Praha“
    'popis'   => html_text($uvod['perex']),    // meta description (zkrátí se na 160 znaků)
    'css'     => ['stranky-areal.css'],        // vlastní styly sekce (assets/css/…), nepovinné
    'js'      => [],                           // vlastní skripty (assets/js/…, defer), nepovinné
    'obrazek' => $uvod['foto'],                // fotka pro sdílení (cesta v uploads/), nepovinné
    'trida'   => 'stranka-areal',              // třída na <body>, nepovinné
];
require __DIR__ . '/inc/sablona/hlavicka.php';            // <!DOCTYPE> … menu … <main id="obsah">
?>

<?= hlava_stranky($uvod, ['drobky' => [['Areál a služby']]]) ?>

<section class="sekce sekce--papir2" id="kurty" aria-labelledby="kurty-nadpis">
  <div class="wrap">
    <?= hlava_sekce(blok('areal', 'kurty'), ['cislo' => 1, 'id' => 'kurty-nadpis']) ?>
    …
  </div>
</section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
```

Další volby `$sablona`: `'noindex' => true` (stránka nemá do vyhledávačů), `'predpripojit' =>
[['href' => upload_url(…), 'as' => 'image', 'type' => 'image/webp']]` (velká fotka nahoře).
Režim přípravy, `noindex`, favicon, písma, `styl.css` a `app.js` řeší šablona sama.

**Pravidla:**
- Soubor stránky = název z menu (`menu_hlavni()` v `inc/data.php`): podle něj se zvýrazní položka
  v menu i v patičce. Stránky mimo menu (`kontakt.php`) zvýrazní patička.
- Žádný výstup před `header()`, ani mezera před `<?php`.
- Odkazy jen `url('stranka.php')`, soubory `asset()` / `upload_url()` / `obr()`. Nikde `/…` natvrdo
  (web běží v `/cltkv2/`). Odkaz z databáze vždy přes `bezpecny_odkaz()` nebo `tlacitko()`.
- Každý text z DB přes `e()` / `typo()` / `paragraphs()` / `html_inline()` / `html_ocistit()`
  (viz PRUVODCE-FRONT §3). Pomůcky z `komponenty.php` escapují samy.
- Chybí údaj → `doplni_klub()` („doplní klub“). **Nevymýšlet fakta.**
- Každá sekce má `aria-labelledby` na svůj nadpis a smysluplné `id` (kotvy z menu a z úvodu).
- Česky, „…“, pomlčky –, nezlomitelné mezery dělá `typo()` sám.

### Vlastní styly a skripty stránky
Sdílené věci jsou ve `styl.css` (+ `v2.css` pro kiosek a medailony). Co je opravdu jen pro jednu
stránku nebo sekci, patří do **`web/assets/css/stranky-<sekce>.css`** (dnes `stranky-klub.css`,
`stranky-areal.css`, `stranky-tenis.css`) a skripty do **`web/assets/js/<funkce>.js`** (`cenik.js`, `mapa.js`,
`clenstvi.js`, `historie.js` …) – načtení přes `$sablona['css'] = ['stranky-klub.css']`, `$sablona['js'] = ['historie.js']`. Používejte tokeny (`var(--navy)`, `var(--gold)`,
`var(--s-5)` …), žádné nové barvy ani písma. V CSS jen relativní `url(../img/…)`.
Když něco chybí ve společném systému, napište požadavek – nepřepisujte `styl.css`.

---

## 2. Hlava stránky a hlavy sekcí

### `hlava_stranky($blok, $volby)` – úvod podstránky (blok `uvod` z modulu Stránky)
Vypíše drobečkovou navigaci, štítek s trikolorou, `<h1>` (s `<em>` zlatou kurzívou), perex,
tlačítka (`odkaz` = navy tlačítko, `odkaz2` = podtržený odkaz), štítek „obsah doplní klub“ při
`doplni_klub = 1` a fotku vpravo, když ji blok má.

```php
<?= hlava_stranky(blok('historie', 'uvod'), [
    'drobky' => [['Klub', 'klub.php'], ['Historie']],    // Úvod se doplní sám; poslední = tato stránka
    'trida'  => 'hlava-stranky--papir2',                  // nebo hlava-stranky--linka
]) ?>
```
Volby: `drobky` (false = bez), `nadpis`/`stitek` (náhradní, když blok chybí), `foto` (false = bez
fotky), `trida`, `navic` (HTML pod perex – už escapované!). Chybějící blok se vypíše s náhradním nadpisem.

### `hlava_sekce($blok, $volby)` – hlava sekce
```php
<?= hlava_sekce(blok('zavodni-tenis', 'pyramida'), ['cislo' => 2, 'id' => 'pyramida-nadpis']) ?>
<?= hlava_sekce(['stitek' => 'Trenérský tým', 'nadpis' => 'Trenéři <em>závodního tenisu</em>.'], ['radek' => true]) ?>
```
Volby: `cislo` (římská číslice), `id` (pro `aria-labelledby`), `tag` (`h2`/`h3`), `stred` (na střed),
`radek` (perex vpravo vedle nadpisu), `bez_znacky`, `nadpis`/`stitek` náhradní.
Ručně: `<header class="hlava"><p class="hlava__znacka"><span class="hlava__cislo">II</span><span class="stitek">Štítek</span></p><h2 class="h2">Nadpis <em>kurzívou</em>.</h2><div class="perex">…</div></header>`.

### Ostatní pomůcky bloků
| pomůcka | co vypíše |
|---|---|
| `blok_text($b)` | text z editoru v `<div class="prose">` (vyčištěný); prázdný + doplni_klub → štítek |
| `blok_foto($b, 'pomer-3x2')` | fotka bloku v rámečku s popiskem (prázdné, když fotka není) |
| `blok_tlacitka($b)` | `<div class="akce">` s tlačítky bloku |
| `tlacitko($url, $text, 'btn'\|'odkaz', ['sipka' => 'ven'\|''\|'zpet'\|'zadna', 'trida' => …])` | navy tlačítko / podtržený odkaz se šipkou; odkaz ven = nové okno + skrytá poznámka |
| `sipka('ven')` | šipka kreslená linkou (`''` →, `ven` ↗, `zpet` ←, `nahoru` ↑) |
| `doplni_klub('termín doplní klub')` | decentní štítek s přerušovaným rámečkem |
| `obr($rel, $alt, ['class' => 'foto', 'fokus' => $r['fokus'], 'loading' => 'eager'])` | `<picture>` s WebP, rozměry z disku, `object-position` z fokusu; neexistující soubor = `''` |
| `json_skript('id', $data)` | data pro JS do `<script type="application/json">` |
| `drobky([['Klub', 'klub.php'], ['CTC']])` | samotná drobečková navigace |

---

## 3. Rozvržení

| třída | použití |
|---|---|
| `.sekce` | svislý rytmus sekce (velké mezery); `--tesna` menší, `--bez-horni`, `--bez-dolni` |
| `.sekce--papir2` | tmavší papír `#f3f0e9` – střídejte s bílým papírem jako na úvodu |
| `.sekce--linka` | vlasová linka nahoře (dvě sekce stejné barvy za sebou) |
| `.sekce--navy` | tmavá navy sekce (max. jedna na stránce; texty a odkazy se přebarví samy) |
| `.wrap` | obsah 1320 px s okraji (16 px na mobilu); `--uzky` 1184 px (sekce úvodu), `--text` 46 rem, `--siroky` |
| `.mrizka > .sl-N .od-N` | 12 sloupců od 900 px (`.sl-7.od-6` …), pod 900 px vše pod sebou |
| `.dvojice` | 5 : 7 (`--7-5`, `--pul`, `--4-8`) |
| `.trojice`, `.ctverice`, `.karty` | 3 sloupce / automaticky ≥ 14 rem / karty ≥ 18 rem |
| `.zasobnik`, `.zasobnik--velky` | svislé mezery mezi potomky |
| `.akce` | řada tlačítek a odkazů |
| `.stred`, `.nowrap`, `.vh` (jen pro čtečky) | drobnosti |

## 4. Typografie

`.display`, `.h1`, `.h2`, `.h3`, `.h4`, `.h5` (verzálky), `.perex` (serif, větší), `.stitek` (zlaté
prostrkané verzálky; `--navy`, `--tlumeny`, `--velky`), `.drobne`, `.male`, `.kurziva`, `.sc` (kapitálky),
`.zlato`, `.navy`, `.tlumene`, `.tnum` (tabulkové číslice), `.onum` (starodávné číslice).
`<em>` v nadpisech = zlatá kurzíva (z DB přes `html_inline()`).
**Text z editoru** vždy do `<div class="prose">` (odstavce, h2–h4, seznamy, citát, obrázek; `prose--siroka`).
Ornament: `<div class="ozdoba" aria-hidden="true"><span></span></div>`, dvojitá linka `<hr class="linka-dvoji">`.
Citát: `<figure class="citat"><blockquote><p>…</p></blockquote><figcaption>Kdo</figcaption></figure>` (`citat--velky`).

## 5. Tlačítka, odkazy, štítky

- `.btn` (navy, hlavní akce), `.btn--obrys`, `.btn--svetla` (na navy), `.btn--mala`, `.btn--velka`.
- `.odkaz` (verzálky se zlatou linkou), `.odkaz--text` (obyčejný text), `.odkaz--velky`.
- Štítky poctivosti: `.doplni` (chybí údaj), `.pramen` („podle klubu“, „pravděpodobně“), `.overeno`.
- `.poznamka` (zlatá linka vlevo), `.rubrika` (Revue), `.razitko` (dobové jméno klubu).
- **Rezervovat kurt** je v hlavičce a na mobilu v liště – na stránkách ho opakujte jen tam,
  kde dává smysl (ceník kurtů): `tlacitko(rezervace_url(), 'Rezervovat kurt')`.

## 6. Fotky

```html
<figure class="ramec ramec--linka">                       <!-- vnitřní světlá linka jako v PDF -->
  <div class="ramec__obraz pomer-3x2"><?= obr($r['foto'], $r['nazev'], ['class' => 'foto', 'fokus' => $r['fokus']]) ?></div>
  <figcaption class="popisek"><span>Popisek s <em>kurzívou</em></span><span class="kredit">foto …</span></figcaption>
</figure>
```
Poměry `pomer-3x2 4x3 4x5 2x3 16x9 1x1 3x4 21x9 15x16`. `.ramec--stin` = stín pod fotkou (karty
aktualit), `.ramec--pasparta` = historické snímky v paspartě. `.foto` = jemný filtr palety,
`.foto--hist` / `.foto--cb` = černobílá. Muzejní štítek: `<p class="stitek-muzeum"><b>1902</b>Popis</p>`.
Obrázky vždy přes `obr()` (WebP + rozměry + lazy). První velkou fotku stránky `'loading' => 'eager'`.

## 7. Tabulky, ceníky, řádky

**Ceník z administrace** (ceník kurtů léto/zima, členství, škola, kempy, doplňkové služby):
```php
<?= cenik_html(cenik('kurty-zima'), ['id' => 'zima']) ?>     // nadpis listu, období, sekce = tabulky, poznámky, PDF
<?= cenik_html(cenik('clenstvi'), ['nadpis' => false]) ?>     // bez nadpisu listu (máte vlastní hlavu)
```
Sloupce se berou z neprázdných hlaviček sekce (`hl_cena`, `hl_cena_clen` …), členské ceny zlatě.
Neexistující / skrytý ceník → „ceník doplní klub“.

**Vlastní tabulka:** `<div class="tabulka-box"><table class="tabulka">` + `<caption>` (+ `<small>`),
`th scope`, `.c` (vpravo), `.clen` (zlatě), `tr.skupina > th[colspan]` (mezititulek). Box roluje do
strany sám, stránka nikdy (app.js mu přidá fokus, když přetéká). `.tabulka--siroka` = min. 40 rem.

**Účetní řádky** (časy, služby, kontakty): `<ul class="radky"><li class="radek [radek--vodici]"><span class="radek__nazev">…</span><span class="radek__hodnota">…</span><span class="radek__pozn">…</span></li></ul>`; `a.radek` = celý řádek odkazem.
**Římský seznam:** `<ol class="rimsky"><li><b>Nadpis</b>text</li></ol>`.
**Čísla:** `<ul class="cisla" style="--sloupcu:4"><li class="cisla__polozka"><span class="cisla__hodnota">19<small>kurtů</small></span><span class="cisla__popis">…</span></li></ul>`.
**Postup:** `<ol class="proces" style="--kroku:4"><li><b>Žádost</b>text</li>…</ol>`.
**Dokumenty:** `<?= dokumenty_html(dokumenty('klub')) ?>` (PDF / odkaz, nové okno).
**Rozbalovací blok:** `<details class="rozbal"><summary>Nadpis</summary><div class="rozbal__obsah">…</div></details>` (`rozbal--male`).

## 8. Lidé (trenéři, vedení)

```php
<div class="skupina-osob">
  <h3 class="h4 skupina-osob__nazev">Trenéři kategorie do 14 let</h3>
  <ul class="osoby"><?php foreach ($skupina as $t) echo osoba_html($t); ?></ul>
</div>
```
`osoba_html()` = portrét 4 : 5 (bez fotky iniciála), role zlatě, jméno, fakta (*řádky*), text,
telefon / e-mail (u vedení jen při `zobrazit_kontakt = 1`), volný `kontakt`. Volby `['foto' => false]`
(seznam bez portrétů, k tomu `<ul class="osoby osoby--bez-fotek">`), `['tag' => 'div']`.
Skupiny trenérů: seskupte `treneri('zavodni')` podle sloupce `skupina`.

## 9. Pilulky a kotvy

`<?= pilulky_html([['Recepce', 'areal.php#recepce'], …]) ?>` – kulaté štítky s odkazem
(`aria-current` / `aria-pressed="true"` = vybraná). Na úvodu vedou „Služby v areálu“ na kotvy
**`areal.php#<kotva>`** ze sloupce `cltk_sluzby.kotva`: `recepce, bazen, fitness, wellness,
fyzioterapie, tenis-shop, salonek, restaurace, parkoviste, beach-volejbal, hriste-slavoj,
hriste-trojkurt` – každá služba na `areal.php` musí mít `id="<?= e($s['kotva']) ?>"`.
Patička odkazuje na **`areal.php#plan`** (plán areálu). Kotvy nesmí zmizet pod pevnou hlavičkou –
`scroll-padding-top` je nastavené globálně.

## 10. Karty

`<div class="karta [karta--zlata] [karta--papir2]"><p class="stitek karta__stitek">…</p><h3 class="karta__nazev">…</h3>…</div>`,
mřížka `<div class="karty">`. Karta turnaje (Prague Open) = `karta karta--zlata` + `.tabulka`.
Karta výsledku do pásu: `vysledek_karta_html($v)` (viz úvod), řádek výsledku pod sebou: `.vysledek` (V4).

## 11. Časové osy

**Svislá kronika** (historie):
```html
<ol class="osa">
  <li class="osa__bod [osa__bod--hlavni]">
    <p class="osa__rok [osa__rok--text]">1893<small>podle klubu</small></p>   <!-- --text pro „počátek 18. století“ -->
    <span class="razitko">I. Český Lawn-Tennis Klub</span>
    <h3 class="osa__titulek">Založení klubu</h3>
    <div class="osa__text"><?= paragraphs($m['text']) ?></div>
    <figure class="osa__foto ramec ramec--pasparta">…</figure>
    <p class="osa__pramen">Pramen: …</p>
  </li>
</ol>
```
**Vodorovná linka let** (wimbledonská linka): `.linka-let` (V4, na mobilu svisle).

## 12. Interaktivní prvky (app.js – nic dalšího nenačítáte)

**Záložky** (ceník léto/zima, Zlatá deska):
```html
<div class="zalozky" data-zalozky>
  <div class="zalozky__seznam [zalozky__seznam--stred]" role="tablist" aria-label="Sezóna ceníku">
    <button class="zalozka" type="button" role="tab" id="t-leto" aria-controls="p-leto" aria-selected="true">Léto 2026</button>
    <button class="zalozka" type="button" role="tab" id="t-zima" aria-controls="p-zima" aria-selected="false" tabindex="-1">Zima 2026/27</button>
  </div>
  <div class="zalozky__panel" role="tabpanel" id="p-leto" aria-labelledby="t-leto" tabindex="0">…</div>
  <div class="zalozky__panel" role="tabpanel" id="p-zima" aria-labelledby="t-zima" tabindex="0" hidden>…</div>
</div>
```
Šipky, Home/End fungují; adresa `stranka.php#p-zima` (nebo `data-hash="zima"` na tlačítku) otevře záložku.

**Dialog:**
```html
<button class="btn btn--obrys" type="button" data-dialog-otevrit="d-plan">Plán areálu</button>
<dialog class="dialog" id="d-plan" aria-labelledby="d-plan-titul">
  <div class="dialog__hlava"><p class="stitek" id="d-plan-titul" data-dialog-titul>Plán areálu</p>
    <button class="dialog__zavrit" type="button" data-dialog-zavrit>Zavřít</button></div>
  <div class="dialog__obsah" data-dialog-obsah>…</div>
</dialog>
```
Zavře Esc, tlačítko i klik mimo okno; rolování stránky se zamkne (správně i v Safari) a fokus se vrátí.
Z JS: `CLTK.dialog.otevri(dlg, obsahHtmlNeboUzel, 'Titulek')`, `CLTK.dialog.zavri(dlg)`.

**Další:** `[data-otevrit="id"]` otevře `<details id>`; dvojí metr `[data-metr]`; triptych
`[data-trava]` (V4 historie: `.triptych > article.trava` s `.skore` a tlačítkem „V den titulu hrál za“);
krokovač `[data-krokovac]`; konfigurátor členství `form[data-konfigurator]` – ceny si přečte
z `<?= json_skript('clenstvi-ceny', […]) ?>` (tvar v komentáři v `app.js`), jinak ceník 2026;
vodorovný pás karet `[data-pas]` (`vysledky.js`, viz úvod); video `[data-video]` (`video.js`).
Po vložení nového HTML zavolejte `CLTK.init(koren)`. Zámek rolování: `CLTK.zamek.zamkni()/odemkni()`.
Ukládat do prohlížeče jen přes `CLTK.uloziste` (localStorage v try/catch, žádné cookies).

**Kiosek Revue a medailony** (`revue.php`, `historie.php`): `$sablona['css'] = ['v2.css']`,
`$sablona['js'] = ['kiosek.js']`, data `<?= json_skript('revue-data', revue_data_js()) ?>`, kostra
v hlavičce `assets/js/kiosek.js` a ve Variantě 4 (`index.html`, sekce 8 a 9b – obal `.v2`).
Příběh v obálkách: `<button class="cip" data-kiosek-pribeh="muchova" data-kmen="muchov" aria-pressed="false">`.

**Zlatá deska** (`.deska`, `.deska__radky`, `.deska__radek`, `.deska__jmena`, `.metr`) a **členský list**
(`.clensky-list`) jsou ve `styl.css` beze změny z Varianty 4. Erb / pečeť = logo s filtrem:
`<span class="deska__erb"><img src="<?= e(logo_url('svg')) ?>" alt=""></span>`,
`<div class="clensky-list__pecet"><img src="<?= e(logo_url('svg')) ?>" alt=""></div>`.

## 13. Formuláře (veřejné)

```html
<form class="formular" method="post" action="<?= e(url('clenstvi.php')) ?>#prihlaska" novalidate>
  <?= verejny_formular_pole('clenstvi') ?>                      <!-- podepsaný token + past na roboty, BEZ cookies -->
  <div class="formular-radek">                                   <!-- pole vedle sebe od 700 px -->
    <div class="pole [pole--chyba]"><label for="f-jmeno">Jméno<span class="povinne" aria-hidden="true">*</span></label>
      <input id="f-jmeno" name="jmeno" autocomplete="given-name" required [aria-invalid="true" aria-describedby="f-jmeno-chyba"]>
      <p class="pole__chyba" id="f-jmeno-chyba">Vyplňte prosím jméno.</p></div>
    …
  </div>
  <label class="volba"><input type="checkbox" name="souhlas" value="1" required><span>Souhlasím…</span></label>
  <div class="formular__akce"><button class="btn" type="submit">Odeslat přihlášku <?= sipka() ?></button><span class="formular__pozn">* povinné údaje</span></div>
</form>
```
- Ochrana: `verejny_formular_pole()` / `verejny_formular_over()` / `verejny_formular_zapis()`
  (PRUVODCE-FRONT §7). **`csrf_field()` na veřejném webu nepoužívat** (zakládá cookie).
  Zpracování vždy před výstupem, po uložení `redirect(url('…?odeslano=1') . '#prihlaska')`, chyby
  vypsat u polí s `aria-invalid` a nahoře `<div class="hlaska hlaska--chyba" role="alert">`.
- Vzor celé obsluhy (token, validace, JSON pro fetch, 400 při podvrženém tokenu, přesměrování):
  `akce.php`; HTML formuláře podle nastavení akce: `akce_formular_html()` v `komponenty.php`.
- Pole mají písmo ≥ 16 px (Safari jinak přibližuje), cíle ≥ 44 px. Datum z formuláře přes
  `normalizuj_datum()`. Enter smí odeslat jen hlavní tlačítko (pomocná tlačítka `type="button"`).
- Další prvky: `.segment` (přepínač radio), `.cip` (výběr), `.krokovac`, `.volba` (checkbox/radio),
  `textarea`, `select` (šipka je v CSS), hlášky `.hlaska`, `.hlaska--ok`, `.hlaska--chyba`, `.hlaska__titul`.

## 14. Přístupnost a pohyb

- Jeden `<h1>` na stránku (dělá `hlava_stranky()`), nadpisy bez přeskakování úrovní.
- `prefers-reduced-motion` i přepínač „Omezit pohyb“ v patičce vypnou animace globálně – obsah
  musí být vidět i bez nich (nic neschovávejte za animaci; `[data-reveal]` jen jako vylepšení).
- Vyšší kontrast přepíná tokeny (`:root[data-kontrast="vyssi"]`) – nepoužívejte pevné barvy mimo tokeny.
- Odkazy ven: `target="_blank" rel="noopener"` + `<span class="vh"> (v novém okně)</span>` (dělá `tlacitko()`).
- Vodorovné rolování jen uvnitř `.tabulka-box`, `.pas__drazka`, `.police`, `.zalozky__seznam` – stránka nikdy.

## 15. Ověření před odevzdáním

```bash
nastroje/kopie-db.sh agent-xyz
nastroje/spustit.sh <port> web/data/agent-xyz.sqlite /cltkv2      # web v podsložce jako na testu
#   když port na 127.0.0.1 drží cizí proces: CLTK_HOST=127.0.0.2 nastroje/spustit.sh …
nastroje/zastavit.sh <port>
```
- Playwright (`C:/Users/Asus/node_modules`): 1440 px, `devices['iPhone 13']`, WebKit; konzole bez chyb,
  žádné HTTP ≥ 400, bez vodorovného posunu na 390 px (i 320 px), s `reducedMotion: 'reduce'`.
  Vzorové skripty: `podklady/_raw/qa/front/snimky.mjs` (snímky + chyby), `mobil-dily.mjs`
  (mobil po obrazovkách), `sirky.mjs` (přetékání v šířkách), `interakce.mjs` (menu, kalendář, formuláře).
- Snímky si opravdu prohlédněte a porovnejte s návrhy (`cltk-navrhy/0X-…`) a PDF klienta.
- Kalendář a lišta lokálně pro jiný den: `?dnes=2026-11-02` (jen SQLite a jen z localhostu).
- `web/data/chyby.log` po práci bez vašich chyb.
