# Průvodce jádrem pro veřejné stránky I. ČLTK Praha

Co jádro (`web/inc/`) nabízí stránkám webu a jak ho používat. Tabulky a sloupce popisuje
`web/sql/SCHEMA.md` (závazné), administraci `web/admin/PRUVODCE.md`, design `ZADANI.md` §1–4.
Šablona (hlavička, patička) patří do `web/inc/sablona/` – jádro jen dodává data.

---

## 1. Kostra stránky

```php
<?php
/* Areál a služby. */
require __DIR__ . '/inc/rezim.php';      // PRVNÍ řádek: načte celé jádro + režim přípravy
track_visit();                            // návštěvnost bez cookies (roboti se nepočítají)

$uvod   = blok('areal', 'uvod');
$sluzby = sluzby();
// … všechna data a případné zpracování POST / header() / redirect() PŘED jakýmkoli výstupem …
?><!DOCTYPE html>
<html lang="cs">
<head>
  …
  <?= rezim_meta_robots() ?>              <!-- v režimu přípravy noindex (hlavičku X-Robots-Tag posílá rezim.php sám) -->
</head>
<body>
<?= rezim_pruh() ?>                       <!-- pruh „Režim přípravy“ pro správce / náhled, jinak nic -->
…
```

- `inc/rezim.php` při zapnutém režimu přípravy pošle návštěvníkovi **přípravnou stránku (503)**
  a skončí; přihlášený správce a kdo zadal **náhledové heslo** vidí celý web. Přípravná stránka
  je hotová (`rezim_zobrazit_pripravu()` – ČLTK design, rezervace, recepce, formulář náhledu).
  Stránka, která se režimem přípravy řídit nemá, nastaví `define('CLTK_BEZ_REZIMU', true)` před `require`.
- **Žádný výstup před `header()`** – ani mezera před `<?php`. Varování PHP jdou do `web/data/chyby.log`.
- Kódování UTF-8, `lang="cs"`, texty s diakritikou a „…“.

## 2. Adresy – web běží v podsložce (`/cltkv2/`), nic nesmí začínat „/“

| funkce | příklad | výsledek |
|---|---|---|
| `url($soubor)` | `url('clenstvi.php#prihlaska')` | `/cltkv2/clenstvi.php#prihlaska` (http/mailto/tel/# vrátí beze změny) |
| `asset($cesta)` | `asset('css/styl.css')` | `/cltkv2/assets/css/styl.css?v=1727…` (otisk času = žádná stará cache) |
| `upload_url($rel)` | `upload_url($r['foto'])` | `/cltkv2/uploads/aktuality/hrajeme-v-hale.jpg` ('' když prázdné) |
| `obrazek_html($rel, $attr)` | `obrazek_html($r['foto'], ['alt' => $r['nadpis'], 'class' => 'foto', 'style' => 'object-position:' . $r['fokus']])` | `<picture>` s WebP + `<img>` s `width/height` z disku, `loading=lazy` |
| `logo_url($varianta)` | `logo_url('svg')`, `'256'`, `'512'`, `'128'`, `'64'`/`'favicon'`, `'png'` | **jediné místo, odkud se logo odkazuje** (logo ke 130 letům) |
| `site_url($soubor)` | do e-mailů, `og:url` | absolutní adresa |
| `here()`, `nav_active('klub.php')` | zvýraznění v menu | `' aria-current="page"'` |
| `bezpecny_odkaz($u)` | odkaz zadaný v administraci | `''` pro `javascript:` apod., stránka webu → `url()` |
| `odkaz_attr($u)` | `<a href="…"<?= odkaz_attr($u) ?>>` | ` target="_blank" rel="noopener"` u odkazů ven |
| `tel_href($tel)`, `tel_kratce($tel)` | `tel:+420608974974`, `608 974 974` | |

V CSS jen relativní cesty (`url(../img/ornament.svg)`), v JS adresy z `data-` atributů nebo z JSON
vloženého do stránky (`json_do_stranky()`), nikdy natvrdo `/…`.

## 3. Výstup a typografie

| funkce | kdy |
|---|---|
| `e($s)` | **každý** text z DB, z URL, z formuláře |
| `typo($s)` | prostý text + nezlomitelné mezery po jednopísmenných předložkách a mezi číslem a jednotkou (už escapované) |
| `paragraphs($s)` | víceřádkový prostý text (*text* ve SCHEMA.md) → `<p>`; typografii doplní |
| `html_inline($s)` | krátké texty s `<em>` (*inline*: nadpisy bloků, popisky galerie, titul desky) |
| `html_ocistit($html)` | text z editoru (*html*: `bloky.text`, `akce.popis`) – vždy i na výstupu |
| `radky($s)`, `radky_seznam($s, $trida)` | „co řádek, to položka“ (*řádky*: `fakta`) |
| `html_text($html)`, `uryvek($text, 160)` | do `<meta name=description>`, `title`, `aria-label` |
| `cena_kc(21000)` · `cislo(1234)` · `rimske(4)` · `sklonuj($n, 'hráč', 'hráči', 'hráčů')` | čísla |
| `cz_date`, `cz_date_kratce`, `cz_date_dlouze`, `cz_range($od, $do, $sRokem)`, `cz_mesic(5)` | data („24. 5. 2026“, „8.–15. 2.“, „27. září 2026“, „květen“) |
| `dnes()` | dnešní datum (lokálně jde přepsat `CLTK_DNES=2026-12-20`, jen SQLite) |
| `json_do_stranky($data)` | data pro JS do `<script type="application/json">` (nerozbije se o `</script>`) |

Nadpisy se zlatou kurzívou (`Tenis … od roku <em>1893</em>.`) mají v DB `<em>` a vypisují se
`<h1><?= html_inline($uvod['nadpis']) ?></h1>`. Pozor na `background-clip:text` u diakritiky
(ukrojí háčky – `padding-top:.1em; margin-top:-.1em`).

## 4. Nastavení klubu – `setting($klic, $vychozi)`

Hodnoty z „Texty a údaje“ (seznam klíčů ve SCHEMA.md). Prázdná hodnota vrací `$vychozi`.
Nejčastější: `klub_nazev`, `klub_zkratka`, `zalozeno`, `ico`, `adresa_ulice`, `adresa_mesto`,
`prijezd_kratce`, `prijezd_text`, `mapa_url`, `recepce_popis/telefon/email`, `kancelar_jmeno/popis/telefon/email`,
`ucet_clenstvi`, `ucet_iban`, `ucet_skola`, `paticka_ctc`, `paticka_pristupnost`, `paticka_pruh`
(spodní pruh: `'© ' . date('Y') . ' ' . setting('paticka_pruh')`), `obsazenost_url`, `kempy_prihlaska_url`,
`video_1080`, `video_720`, `video_poster` (cesty v uploads/ → `upload_url()`).

## 5. Menu, patička, kontakty (`inc/data.php`)

- **`menu_hlavni()`** – hlavička podle ZADANI §3. Pole položek:
  `nazev, url, soubor, soubory (celá větev), strana ('l' = vlevo od znaku, 'p' = vpravo), externi (bool),
  aktivni (bool), podmenu => [[nazev, url, soubor, aktivni], …]`.
  Vlevo Klub · Areál a služby · Závodní tenis · Tenisová škola, vpravo Restaurace · Prague Open.
  **Restaurace a Prague Open** vedou na web z nastavení (`restaurace_url`, `prague_open_url`, pak
  `externi = true` → nové okno), a když je prázdné, na `restaurace.php` / `prague-open.php`. Podmenu nemají.
- **`rezervace_url()`** – tlačítko **Rezervovat kurt** (vždy `target="_blank" rel="noopener"`).
  Mobilní lepivá lišta: Rezervovat kurt + `tel_href(setting('recepce_telefon'))` „Zavolat recepci“.
- **`menu_paticka()`** – sloupec „Důležité informace“ (Členství … Historie).
- **`kontakty_paticka()`** – jen Recepce a Eva Štefková: `[nazev, popis, telefon, email]`.
- **`dokumenty_paticka()`** – sloupec „Dokumenty a sítě“ (`paticka_text`, adresa `dokument_url($d)`);
  **`site_odkazy()`** – Facebook, Instagram, YouTube, Fotogalerie (jen vyplněné) `[nazev, url, klic]`.
- Plánek areálu v patičce: `url('areal.php#plan')` nebo dokument „Plán areálu“; Mapa: `setting('mapa_url')`.

## 6. Data pro stránky (vše jen viditelné záznamy, správně seřazené; chyba DB = prázdný výsledek + protokol)

| funkce | vrací |
|---|---|
| `aktivni_oznameni()` | zprávy informační lišty platné dnes – **prázdné pole = lištu vůbec nevypisovat** (`text`, `odkaz`) |
| `uvodni_galerie()` | snímky galerie (`typ` foto/deska, `foto`, `foto_w/h`, `fokus`, `alt`, `popisek`*inline*, `kredit`, `rejstrik`, `deska_*`) |
| `aktuality(3)` | karty aktualit (`nadpis`, `popis`, `foto`, `fokus`, `odkaz`, `odkaz_text`) – nejsou to články, bez detailu |
| `posledni_vysledky(8)` | výsledky od nejnovějšího + `sety_pole` = `[[text, hlavni '6:3', doplnek '(7:5)', vyhra bool], …]` |
| `triptych()` | tři wimbledonské trávy + `sety_pole` |
| `akce_rok($rok)`, `nejblizsi_akce()`, `akce_detail($id)`, `kalendar_data()` | kalendář – §7 |
| `cenik('kurty-leto')` | `['list' => …, 'sekce' => [[…, 'radky' => […]], …]]`; klíče `kurty-leto, kurty-zima, clenstvi, skola, kempy, doplnkove`; prázdná hlavička sekce (`hl_*`) = sloupec nezobrazovat |
| `blok($stranka, $klic)`, `bloky($stranka)` | texty stránek – §8 |
| `treneri('zavodni' / 'skola' / 'privatni')` | trenéři týmu (`skupina` = podnadpis, `fakta` *řádky*, `foto`, `fokus`, kontakt) |
| `sluzby()`, `sluzby(true)` | služby areálu; `true` = jen 12 štítků „Služby v areálu“ na úvod. Odkaz štítku: `url('areal.php') . '#' . $s['kotva']`; `casy` prázdné = „doplní klub“ |
| `skola('info' / 'harmonogram' / 'rozvrh' / 'kemp')` | tenisová škola |
| `milniky()`, `osobnosti()`, `deska($kategorie, $skupina)` | historie (`deska('grandslam')`, `deska('cestni', 'jmena')` …) |
| `revue_cisla()`, `newslettery()` | Revue (+ `titulky_pole`, `obsah_pole`, `pdf` = hotová adresa), newslettery (`pdf_cs`, `pdf_en`) |
| `vedeni('vybor' / 'kancelar' / 'kontakt')` | lidé; **`zobrazit_kontakt = 0` → telefon a e-mail nevypisovat** |
| `ctc('fakt' / 'klub' / 'utkani' / 'soutez' / 'rodokmen')` | Centenary Tennis Clubs (`zvyraznit` = I. ČLTK v rodokmenu) |
| `partneri()`, `partner_logo_url($p)` | 25 partnerů v pořadí mřížky 5 × 5; logo je už jednobarevné béžové – **nepřebarvovat filtrem** |
| `dokumenty($kategorie)`, `dokument_url($d)` | dokumenty (`klub, cenik, provoz, clenstvi, skola`) |

Úvodní video (ZADANI §4.7): `upload_url(setting('video_720'))` / `video_1080` / `video_poster`
(WebP plakátu: `webp_vedle(setting('video_poster'))`). `preload="none"`, spustit až při zobrazení a jen bez omezení pohybu.

## 7. Kalendář akcí a přihláška k akci

**Pravidlo „nejbližší akce“** (server i JS musí počítat stejně, viz komentář v `inc/data.php`):
začátek = `datum_od`, jinak **poslední den měsíce**; konec = `datum_do`, jinak začátek; proběhlá = konec < dnes;
nejbližší = nejmenší začátek ≥ dnes, jinak právě probíhající (začátek < dnes ≤ konec) s nejpozdějším začátkem.

```php
<script type="application/json" id="kalendar-data"><?= json_do_stranky(kalendar_data()) ?></script>
```

`kalendar_data()` → `['rok', 'dnes', 'nejblizsi_id', 'vychozi_id', 'akce' => [akce_pro_js(), …]]`.
Každá akce: `id, nazev, rok, mesic, mesic_nazev, termin ('8.–15. 2.' / 'termín doplní klub'), termin_dlouze,
zacatek, konec, bez_data, probehla, cas, misto, stitek, perex_html, popis_html (už vyčištěné), foto (adresa),
odkaz, odkaz_text, odkaz_externi, detail_url (akce.php?id=…), prihlaseni (lze se právě přihlásit),
prihlaseni_zapnuto, obsazeno, formular` (`akce_formular()` – viz níže). Interní poznámka se nevydává.
Výchozí okno = `vychozi_id` (nejbližší, jinak poslední akce roku); JS ho přepočítá podle data návštěvníka.
Bez JavaScriptu vede každá položka seznamu odkazem na `akce.php?id=`.

**Formulář přihlášky je u každé akce jiný** (ZADANI §0.3): `akce_formular($akce)` →
`['pole' => [telefon, pocet, poznamka => bool], 'povinne' => [telefon, poznamka => bool],
'vlastni' => [[klic, popisek, typ (text/cislo/vyber/zaskrtavatko), moznosti, povinne], …], 'tlacitko', 'poznamka']`.
Jméno, e-mail a souhlas jsou vždy. Názvy polí: `jmeno, email, telefon, pocet, poznamka, souhlas, pole_<klic>`.
Text souhlasu: `blok('formulare', 'souhlas')['perex']`.

**Veřejné formuláře bez cookies.** `csrf_field()` zakládá relaci (cookie) – na veřejných stránkách ho
**nepoužívejte** (kalendář s přihláškou je na úvodu a web nemá mít cookies). Místo něj:

```php
// ve formuláři (uvnitř <form method="post">):
<?= verejny_formular_pole('akce-' . $a['id']) ?>      // podepsaný token + skrytá past na roboty

// zpracování (akce.php, před výstupem):
require_once __DIR__ . '/inc/mail.php';
$a = akce_detail((int)($_GET['id'] ?? 0));
if (!$a) { http_response_code(404); … }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chyba = verejny_formular_over('akce-' . $a['id']);           // '' = v pořádku
    $r = akce_formular_zpracuj($a, $_POST);                       // ['ok', 'chyby' => [pole => hláška], 'hodnoty', 'radek']
    if ($chyba === '' && !$a['prihlaseni']) $chyba = 'Přihlašování na tuto akci je uzavřené.';
    if ($chyba === '' && $r['ok']) {
        db_insert('cltk_signups', $r['radek']);
        verejny_formular_zapis('akce-' . $a['id']);              // brzda: max. 8 přihlášek / hodinu / IP
        upozorni_prihlaska_akce($a, $r['radek']);                 // e-mail jen když je vyplněný prihlasky_email
        if (chce_json()) odpoved_json(['ok' => true, 'zprava' => 'Děkujeme, přihláška je uložená.']);
        redirect(url('akce.php?id=' . $a['id'] . '&odeslano=1'));
    }
    if (chce_json()) odpoved_json(['ok' => false, 'chyba' => $chyba, 'chyby' => $r['chyby']], 422);
    // jinak vypsat formulář znovu s $r['hodnoty'] a chybami u polí
}
```

`chce_json()` = požadavek z `fetch()` s `Accept: application/json` (nebo `?format=json`) – okno kalendáře
tak může odeslat přihlášku bez přenačtení stránky. Token platí 2 hodiny, jen z adresy, kde vznikl (u IPv6 ze sítě /64),
a odmítne odeslání do 2 s (roboti). Brzda 8 odeslání / hodinu počítá IPv6 taky po /64 (`client_ip_skupina()`).

**Přihláška do klubu** (`clenstvi.php`): pole podle `obsah.json → clenstvi.prihlaska_pole` (jméno, příjmení,
pohlaví, datum narození, rodné číslo, státní občanství, typ členství, až 4 další osoby, adresa, telefon,
e-mail, současná profese, jak jste se o nás dozvěděli, souhlasy GDPR a podmínky, potvrzení schvalovacího
procesu). Hlavní údaje do sloupců `cltk_prihlasky_clenstvi`, **celý formulář jako JSON do `data`**
(`json_ulozit($pole)`), `stav = 'nova'`, `created_at = ted()`; pak `upozorni_prihlaska_clenstvi($radek)`.
Stejná ochrana `verejny_formular_pole('clenstvi')` / `verejny_formular_over('clenstvi')`. Datum narození
přes `normalizuj_datum()`.

## 8. Texty stránek – `blok($stranka, $klic)`

Vrací řádek `cltk_bloky` (`stitek, nadpis`*inline*`, perex`*text*`, text`*html*`, foto, foto_popisek,
odkaz, odkaz_text, odkaz2, odkaz2_text, doplni_klub`) nebo prázdný blok s `existuje => false` (skrytý blok
se chová jako neexistující). **`doplni_klub = 1` → decentní štítek „doplní klub“** (obsah zatím chybí –
nevymýšlet). Odkazy tlačítek přes `bezpecny_odkaz($b['odkaz'])` + `odkaz_attr()`.

Výchozí bloky (`*` = doplni_klub, `+foto` = má fotku). Klíč `uvod` = hlavička stránky.

| stránka | klíče |
|---|---|
| `index` | `uvod` (štítek „Ostrov Štvanice, Praha 7“, nadpis, Stát se členem, Ceník kurtů), `aktuality`, `vysledky`, `kalendar`, `clenstvi+foto`, `sluzby`, `historie` (tlačítko Kompletní historie), `partneri` |
| `klub` | `uvod`, `o-klubu`, `jmena` |
| `clenstvi` | `uvod`, `v-cene`, `zvyhodneni`, `postup`, `druhy`, `prihlaska` |
| `historie` | `uvod`, `triptych`, `kronika`, `deska`, `deska-grandslam`, `deska-cestni*`, `deska-mistri`, `deska-oh`, `deska-zasluzili*`, `deska-prezidenti*`, `osobnosti` |
| `vedeni` | `uvod`, `dokumenty` |
| `ctc` | `uvod`, `rodokmen` |
| `revue` | `uvod`, `newslettery` |
| `areal` | `uvod`, `kurty`, `plan+foto`, `prijezd` |
| `cenik-kurtu` | `uvod`, `pravidla` |
| `privatni-treneri` | `uvod*` |
| `body-solution` | `uvod*` |
| `sportovni-lekarstvi` | `uvod*` |
| `zavodni-tenis` | `uvod`, `pyramida`, `cisla`, `extraliga` |
| `zavodni-tenis-treneri` | `uvod` |
| `tenisova-skola` | `uvod`, `kontakt` |
| `tenisova-skola-ceniky` | `uvod` |
| `tenisova-skola-rozvrhy` | `uvod`, `rozvrhy*` |
| `tenisova-skola-treneri` | `uvod` |
| `letni-kempy` | `uvod`, `informace` |
| `restaurace` | `uvod+foto`, `informace`, `kontakt*` |
| `prague-open` | `uvod+foto`, `vitezove` |
| `kontakt` | `uvod` |
| `formulare` | `souhlas` (text souhlasu se zpracováním údajů) |
| `404` | `uvod` |

Potřebujete další blok? Napište požadavek na seed (`web/sql/seed/60-bloky.php`) – modul Stránky ho pak
nabídne k úpravě. Šablona musí zvládnout i chybějící blok (`existuje = false`).

## 9. Ostatní

- **404**: `.htaccess` posílá neexistující adresy na `404.php` (lokálně totéž dělá `nastroje/router.php`);
  stránka 404 sama nastaví `http_response_code(404)` a použije `blok('404', 'uvod')`.
- **Přístupnost**: přepínače „Omezit pohyb“ a „Vyšší kontrast“ si pamatují volbu jen v `localStorage`
  (ne cookie). Režim omezeného pohybu musí ukázat všechen obsah.
- **Bezpečnost**: `e()` všude; `html_ocistit()` sestaví HTML z holých značek (`strip_tags` nestačí –
  nechává atributy); odkazy z DB jen přes `bezpecny_odkaz()`; obrázky jen z `uploads/`.
- **Databáze**: jen `q() / row() / rows() / val()` (pojistka hlídá předponu `cltk_`), pro stránky raději
  hotové pomůcky z `inc/data.php`. Dotazy musí běžet v SQLite i MySQL.

## 10. Lokální běh a ověřování

```bash
nastroje/kopie-db.sh agent-front                                     # vlastní kopie databáze
nastroje/spustit.sh 8820 web/data/agent-front.sqlite                 # servery 8820 + 8920
nastroje/spustit.sh 8820 web/data/agent-front.sqlite /cltkv2         # web v podsložce jako na testu
CLTK_DNES=2026-12-20 nastroje/spustit.sh 8820 web/data/agent-front.sqlite   # jiný „dnešek“ (kalendář)
nastroje/zastavit.sh 8820
nastroje/novy-seed.sh web/data/agent-front.sqlite                    # čerstvá DB s výchozím obsahem
```

Režim přípravy je lokálně vypnutý (`rezim_pripravy = 0`); na serveru ho instalace zapne.
Přihlášení do administrace admin@cltk.local / cltk-test-2026. Ověřujte 1440 px, `devices['iPhone 13']`
a WebKit (Playwright z `C:/Users/Asus/node_modules`), konzoli bez chyb, žádné HTTP ≥ 400, bez vodorovného
posunu na 390 px, s omezeným pohybem; `web/data/chyby.log` po práci prázdný.
