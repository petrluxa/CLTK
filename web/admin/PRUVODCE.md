# Průvodce administrací I. ČLTK Praha – pro autory modulů

Jak napsat stránku administrace (`web/admin/<modul>.php`), aby vypadala a chovala se jako
ostatní a nezopakovala chyby z Liberce a Olympu. Jádro (`inc/`, `admin/inc/`, `admin/assets/`)
spravuje jádro webu – když v něm něco chybí, napište to jako požadavek, neopravujte ho u sebe.

Související dokumenty: `web/sql/SCHEMA.md` (tabulky a sloupce – **závazné**),
`web/inc/PRUVODCE-FRONT.md` (pomůcky pro veřejné stránky), `ZADANI.md` §6 (moduly).

---

## 1. Kostra stránky modulu

```php
<?php
/* Aktuality z klubu – fotka, nadpis, krátký popis, odkaz (nejsou to články). */
require __DIR__ . '/inc/layout.php';          // načte auth.php, ui.php i celé jádro inc/functions.php
$user = require_login();                      // nepřihlášeného pošle na login.php?zpet=…

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_post_zacatek('aktuality.php');      // příliš velký soubor + CSRF token (400 při neplatném)
    $akce = vstup('action', 20);
    $id   = vstup_int('id');

    if ($akce === 'prepnout') { admin_prepni('cltk_aktuality', $id); redirect('aktuality.php', 'Uloženo.'); }
    if ($akce === 'nahoru' || $akce === 'dolu') { admin_posun('cltk_aktuality', $id, $akce === 'nahoru' ? -1 : 1); redirect('aktuality.php'); }
    if ($akce === 'smazat') { … smazat řádek, PAK soubory …; redirect('aktuality.php', 'Smazáno.'); }
    if ($akce === 'ulozit') { … viz §4 … }
    redirect('aktuality.php', 'Neznámý požadavek.', 'err');
}

admin_head('Aktuality z klubu', $user, [
    'podnadpis' => 'Tři karty na úvodní stránce. Nejsou to články – jen fotka, nadpis a krátký popis.',
    'akce'      => '<a class="btn btn-primary" href="aktuality.php?nova=1">Přidat aktualitu</a>',
]);
?>
  … obsah …
<?php admin_foot(); ?>
```

Pravidla, která platí bez výjimky:

- **Žádný výstup před `header()`/`redirect()`.** Celé zpracování POST je před `admin_head()`.
  Varování PHP jdou do `web/data/chyby.log` (ne do stránky); po práci ho zkontrolujte.
- **Po každém zápisu `redirect()`** (303), ne vypsání stránky. Hláška: `redirect($url, 'Uloženo.')`,
  chyba: `redirect($url, 'Text chyby.', 'err')`. Druhy hlášek: `ok`, `err`, `warn`, `info`.
- **Každý formulář s `method="post"` má `<?= csrf_field() ?>`.** Pomůcky `tlacitko_*()` ho přidávají samy.
- **Každý výstup přes `e()`** (texty z DB i z URL). Výjimky jen tam, kde to říká SCHEMA.md
  (`html_inline()`, `html_ocistit()`, `paragraphs()`).
- **Do databáze jen `q() / row() / rows() / val() / db_insert() / db_update()`**, nikdy `db()->query()`.
  Pojistka odmítne cokoli, co sahá na tabulku bez předpony `cltk_` (databáze je sdílená s TK Olymp).
  `LIMIT` vkládejte jako `(int)` do SQL, ne jako parametr. Žádné `strftime`, `DATE_FORMAT`, `IFNULL`,
  `ON DUPLICATE KEY`, `||` – musí to běžet v SQLite i MySQL (viz SCHEMA.md).
- **Vypínat, ne mazat.** Položky mají `visible`; mazání až jako poslední možnost, vždy s potvrzením.
- Moduly jsou v postranním panelu v pořadí ZADANI §6 (`admin_moduly()` v `inc/layout.php`).
  Soubory: `navstevnost.php, oznameni.php, galerie.php, aktuality.php, vysledky.php, akce.php
  (+ akce-edit.php), prihlasky.php, treneri.php, ceniky.php, skola.php, sluzby.php, historie.php,
  revue.php, vedeni.php (+ ctc.php jako podpoložka), stranky.php, partneri.php, dokumenty.php,
  nastaveni.php, ucet.php`. Kdo potřebuje další soubor pod stejnou položkou (zvýraznění v menu),
  napíše požadavek na úpravu `admin_moduly()`.

## 2. `admin_head($titulek, $user, $volby)` a `admin_foot()`

| volba | význam |
|---|---|
| `podnadpis` | HTML pod nadpisem (escapujte sami: `'… ' . e($x)`) |
| `akce` | HTML tlačítek vpravo od nadpisu (`<a class="btn btn-primary" …>`) |
| `zpet` | `['akce.php', 'Kalendář akcí']` – odkaz zpět nad nadpisem (editační stránky) |
| `sirka` | `'uzka'` = užší obsah (formulář na celou stránku) |

Flash hláška z `redirect()` se vypíše sama pod nadpisem. `admin_foot()` připojí `admin.js` a `editor.js`.

## 3. Formulářová pole (`admin/inc/ui.php`) – vracejí HTML, vypisujte `<?= … ?>`

Společné volby `$o`: `hint` (HTML pod polem – váš text, escapujte), `required`, `placeholder`,
`maxlength`, `id`, `sirka => 'cela'` (přes celou šířku mřížky), `attrs` (další atributy), `autocomplete`.

| pomůcka | k čemu | zpracování na serveru |
|---|---|---|
| `pole_text($name, $label, $value, $o)` | text; `$o['type']` = `text, email, url, tel, number, password, time` | `vstup('name', 255)`, `vstup_int('name')` |
| `pole_datum($name, $label, $ymd, $o)` | `<input type=date>`, projde i „5. 9. 2026“ | `normalizuj_datum(vstup('name', 20))` → `'RRRR-MM-DD'` / `null` / `false` (= chyba) |
| `pole_textarea($name, $label, $value, $o)` | víceřádkový prostý text; `rows`, `vysoke => true` | `vstup('name', 0)` (0 = bez limitu) |
| `pole_editor($name, $label, $html, $o)` | editor (tučné, kurzíva, nadpisy, seznamy, odkaz, citace, HTML) | **`html_k_ulozeni(vstup('name', 0))`** – vždy (vyčistí a uloží odkazy bez `/cltkv2/`) |
| `pole_select($name, $label, $value, [hodnota => popisek], $o)` | výběr | ověřte, že hodnota je v seznamu |
| `pole_check($name, $label, $checked, $o)` | zaškrtávátko (posílá 1) | `vstup_bool('name')` |
| `pole_obrazek($name, $label, $rel, $o)` | náhled, výběr nového, „odebrat“; `nahled => siroky/ctverec/logo` | `admin_obrazek(…)` – §4 |
| `pole_fokus($name, $label, $fokus, $foto, $o)` | ohnisko fotky klepnutím do náhledu → „44% 28%“ | `vstup('name', 20)` + kontrola `/^\d{1,3}(\.\d+)?%\s+\d{1,3}(\.\d+)?%$/` |
| `pole_pdf($name, $label, $rel, $nazev, $o)` | PDF: odkaz, výběr, odebrat | `admin_pdf(…)` – §4 |
| `pole_radek([$pole1, $pole2], 2)` | pole vedle sebe (na mobilu pod sebou), 1–4 sloupce | – |
| `tlacitka_formulare('Uložit', 'aktuality.php', 'Zpět')` | jediné odesílací tlačítko + odkaz zpět | – |

Formulář: `<form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>`
(`data-hlidat-zmeny` = varování při odchodu s neuloženými změnami). Mřížka polí: `<div class="form-mrizka">`.
Další třídy: `fieldset` + `legend`, `.mezititulek`, `.oddelovac`, `.hint`, `.form.wide` (bez max. šířky).

**Pole mají vždy aspoň 16 px** (jinak Safari na iPhonu přiblíží stránku) a tlačítka aspoň 44 px na dotyk –
hlídá to `admin.css`, nepřebíjejte to vlastním stylem.

## 4. Soubory: obrázky a PDF – starý soubor mazat AŽ po zápisu do DB

```php
$stary = row('SELECT * FROM cltk_aktuality WHERE id = ?', [$id]);
$foto  = admin_obrazek('foto', $stary['foto'] ?? '', 'aktuality', 2400, 2400, vstup('nadpis', 60));
if ($foto['chyba'] !== '') redirect('aktuality.php?id=' . $id, $foto['chyba'], 'err');   // nic se nezměnilo
db_update('cltk_aktuality', $id, ['nadpis' => …, 'foto' => $foto['soubor'], 'updated_at' => ted()]);
admin_smazat_soubory($foto['smazat']);         // TEPRVE TEĎ (past z Liberce)
redirect('aktuality.php', 'Uloženo.');
```

- `admin_obrazek($pole, $stary, $podslozka, $maxW, $maxH, $jmeno)` → `['soubor', 'chyba', 'smazat', 'w', 'h']`.
  Obrázek se přes GD **načte a uloží znovu** (zahodí se skripty, EXIF s GPS), otočí podle EXIF, zmenší,
  JPG (nebo PNG při průhlednosti) + WebP kopie. Podsložky: `galerie, aktuality, vysledky, akce, treneri,
  sluzby, historie, osobnosti, revue, bloky, partneri, vedeni`.
- `admin_pdf($pole, $stary, $staryNazev, 'dokumenty')` → `['soubor', 'nazev', 'chyba', 'smazat']`.
  Kontroluje hlavičku `%PDF-` i MIME podle obsahu; jiný soubor odmítne.
- Úvodní galerie ukládá i rozměr: `foto_w = $foto['w']`, `foto_h = $foto['h']`.
- **Partneři:** po nahrání loga vyrobte jednobarevnou verzi a uložte obě cesty:
  ```php
  $logo = admin_obrazek('logo', $p['logo'], 'partneri', 1600, 1600, $nazev);
  if ($logo['chyba'] !== '') redirect(…, $logo['chyba'], 'err');
  $mono = $p['logo_mono']; $smazat = $logo['smazat'];
  if ($logo['soubor'] !== $p['logo'] && $logo['soubor'] !== '') {      // nové logo
      $mono = partner_logo_mono($logo['soubor'], $nazev);             // vždy NOVÝ soubor v partneri/mono/
      if ($mono === '') redirect(…, 'Z loga se nepodařilo vyrobit jednobarevnou verzi.', 'err');
      if ($p['logo_mono'] !== '') $smazat[] = $p['logo_mono'];
  }
  db_update('cltk_partneri', $id, ['logo' => $logo['soubor'], 'logo_mono' => $mono, …]);
  admin_smazat_soubory($smazat);
  ```
  Barva a krytí odpovídají logům, která dodal klient (`PARTNER_LOGO_BARVA` #a3947e, krytí 60 %).
  Volitelně nabídněte i přímé nahrání hotového jednobarevného PNG (klient je tak dodal) – pak se nepřebarvuje.
- Na webu je adresa `upload_url($rel)`, obrázek s WebP `obrazek_html($rel, ['alt' => …])`.

## 5. Tlačítka řádků – každé ve VLASTNÍM formuláři

Enter v poli formuláře (nebo „Přejít“ na mobilní klávesnici) odešle **první** tlačítko formuláře.
Kdyby ve formuláři s polem byla šipka nebo Smazat, rozepsaná změna by se zahodila (past z Liberce).
Proto mají šipky, přepínač i mazání vlastní malý formulář (posílá na stejnou adresu, `action` + `id`):

| pomůcka | posílá `action` |
|---|---|
| `tlacitko_prepnout($id, $visible, $extra)` | `prepnout` → `admin_prepni('cltk_…', $id)` |
| `tlacitka_poradi($id, $prvni, $posledni, $extra)` | `nahoru` / `dolu` → `admin_posun('cltk_…', $id, -1/+1, $skupina)` |
| `tlacitko_smazat($id, 'Opravdu smazat …?', $extra)` | `smazat` (potvrzení přes `data-potvrdit`) |
| `tlacitko_akce($action, $id, $text, $trida, $extra, $potvrdit, $titulek)` | cokoli vlastního |

`$extra` = další skrytá pole (např. `['zarazeni' => 'skola']`, ať se po přesměrování vrátíte na stejnou záložku).
`admin_posun()` přečísluje skupinu na 0, 1, 2…; `$skupina` = sloupec, v jehož rámci se řadí
(`zarazeni` u trenérů, `kategorie` u desky, `list_id`/`section_id` u ceníků…). Nový řádek na konec:
`admin_dalsi_poradi('cltk_…', $skupina, $hodnota)`.

Atribut `form="…"` (pole mimo svůj formulář) **nepoužívejte** – Safari s ním má potíže.

## 6. Tabulky, štítky, záložky, prázdný stav

```php
<section class="panel">
  <div class="panel-head"><h2>Aktuality <small>3 na webu</small></h2></div>
  <div class="tbl-wrap tbl-karty">                 <!-- tbl-karty: na telefonu se řádky překlopí do karet -->
    <table>
      <thead><tr><th>Foto</th><th>Nadpis</th><th>Stav</th><th class="right">Akce</th></tr></thead>
      <tbody>
      <?php foreach ($radky as $i => $r): ?>
        <tr<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
          <td data-label="Foto"><?= $r['foto'] ? '<img class="thumb-sm" src="' . e(upload_url($r['foto'])) . '" alt="">' : '<span class="thumb-ph">bez fotky</span>' ?></td>
          <td data-label="Nadpis" class="td-nazev"><b><?= e($r['nadpis']) ?></b><small><?= e(uryvek($r['popis'], 80)) ?></small></td>
          <td data-label="Stav"><?= stav_badge($r['visible']) ?></td>
          <td data-label="Akce" class="right"><div class="akce-radku">
            <?= tlacitka_poradi((int)$r['id'], $i === 0, $i === count($radky) - 1) ?>
            <a class="btn btn-sm btn-ghost" href="aktuality.php?id=<?= (int)$r['id'] ?>">Upravit</a>
            <?= tlacitko_prepnout((int)$r['id'], $r['visible']) ?>
            <?= tlacitko_smazat((int)$r['id'], 'Smazat aktualitu „' . $r['nadpis'] . '“?') ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
```

- Štítky: `stav_badge($visible)` (Zobrazeno / Skryto), `badge('Publikováno', 'ok')`;
  druhy `ok, off, warn, err, info, navy, zlato`.
- Záložky modulu: `admin_zalozky(['historie.php?cast=kronika' => 'Kronika', …], $aktivniUrl)`.
- Prázdný stav: `prazdny_stav('Zatím žádné výsledky', 'Přidejte první…', '<a class="btn …">…</a>')`.
- Miniatury: `.thumb-sm` (64×46), `.thumb-ctverec`, `.thumb-logo` (logo partnera), `.thumb-ph` (bez obrázku).
- Dlaždice s čísly (jako Přehled): `<div class="stats"><a class="stat" href="…"><b>12</b><span>Popisek</span><small>…</small></a></div>`.
- Panely: `.panel` (bílá karta), `.panel-head`, `.panel-body` (`.tight` bez okraje), `.panel--navy`,
  vedle sebe `.mrizka-panelu`. Tlačítka: `.btn` + `.btn-primary` (navy), `.btn-ghost`, `.btn-zlato`,
  `.btn-danger`, `.btn-sm`; řada `.btn-row`.
- Datum a čas v tabulkách: `cz_date($d)`, `cz_datum_cas($dt)`, rozsah `cz_range($od, $do)`.

## 7. Export CSV (přihlášky k akci)

```php
if (($_GET['csv'] ?? '') === '1') {                     // před admin_head()!
    $akce = row('SELECT * FROM cltk_akce WHERE id = ?', [$id]);
    $prihl = rows('SELECT * FROM cltk_signups WHERE akce_id = ? ORDER BY created_at, id', [$id]);
    $sloupce = signup_sloupce($akce, $prihl);           // standardní pole podle akce + všechna vlastní pole
    csv_export('prihlasky-' . slugify($akce['nazev']) . '.csv',
        array_column($sloupce, 'popisek'),
        array_map(fn($p) => array_map(fn($s) => signup_hodnota($p, $s['klic']), $sloupce), $prihl));
}
```

`csv_export()` píše UTF-8 s BOM, středník a CRLF (Excel) a hodnoty začínající `= + - @` zneškodní apostrofem.

## 8. Moduly se zvláštnostmi

- **Kalendář akcí – formulář přihlášky u každé akce jiný** (ZADANI §0.3). V `akce-edit.php`:
  zaškrtávátko „Zobrazit tlačítko Přihlásit se“ (`prihlaseni_povoleno`), volitelná pole
  (telefon, počet osob, poznámka + „povinné“ u telefonu a poznámky), **vlastní pole** (popisek, typ
  `text / cislo / vyber / zaskrtavatko`, možnosti u výběru – co řádek, to možnost –, povinné), text tlačítka,
  poznámka pod formulářem, uzávěrka `prihlaseni_do`, kapacita. Uložte jako JSON do `cltk_akce.formular`
  přesně ve tvaru ze SCHEMA.md; **`klic` vlastního pole přidělte jednou (`f1`, `f2`…) a při úpravách ho neměňte**
  (odpovědi se na něj váží). Normalizovaný tvar čte `akce_formular($akce)`, typy `AKCE_TYPY_POLI`.
  Termín: `rok` a `mesic` vždy (i bez data), `datum_od/do` přes `normalizuj_datum()`, `termin_text` volitelně.
  U CTC U14 je v `poznamka_interni` „ověřit název belgického klubu“ – zobrazte interní poznámku výrazně.
- **Přihlášky** (`prihlasky.php`): k akcím (`cltk_signups`, `vyrizeno` 0/1, `poznamka_admin`; akce může být
  smazaná → `LEFT JOIN cltk_akce`) a do klubu (`cltk_prihlasky_clenstvi`, `stav` nova/vyrizena/zamitnuta,
  celý formulář v JSON `data` – rozbalit `json_pole()`, rodné číslo zobrazovat jen v detailu). Počet nevyřízených
  ukazuje štítek v menu (`admin_pocet_novych_prihlasek()`).
- **Texty a údaje** (`nastaveni.php`): projděte `cltk_settings` podle `grp` (pořadí skupin ve SCHEMA.md),
  **skupinu `system` vynechte**, pole podle `typ` (`text, textarea, inline, url, email, tel, bool, cislo,
  soubor, heslo`), popisek `label`, nápověda `napoveda`. Ukládejte **jen přes `setting_set($klic, $hodnota)`**
  (obnoví paměť `setting()`). URL přes `normalizuj_url()`. **Náhledové heslo** (`typ = heslo`): pole se nikdy
  nepředvyplňuje; prázdné = beze změny, zaškrtávátko „zrušit náhledové heslo“; nové heslo uložte
  `nahled_heslo_nastav($heslo)` (ukládá jen hash; změna zneplatní stará cookie). `rezim_pripravy` je `bool`
  (kotva `#rezim` – odkazuje na ni pruh i postranní panel). `prihlasky_email` prázdný = jen ukládat.
- **Stránky** (`stranky.php`): bloky `cltk_bloky` podle `stranka` + `klic` (seznam ve PRUVODCE-FRONT.md).
  Klíče bloků **nepřejmenovávat** (šablony je čtou natvrdo); nové bloky přidávat smí. Šipky pořadí jsou jen u vlastních
  bloků (pevné řadí šablona). Bloky úvodní stránky (`STRANKY_UVOD_POLE`) nemají šipky ani „Skrýt“ a formulář ukazuje
  jen pole, která sekce používá; nad nimi je mapa „Co se kde mění na úvodní stránce“ (`STRANKY_UVOD_MAPA`). `nadpis` je *inline*
  (smí `<em>`), `text` z editoru (`html_ocistit`), `doplni_klub` = zaškrtávátko „obsah zatím chybí“.
- **Ceníky**: list → sekce → řádky (jako Liberec `cenik-edit.php`). Hlavička sloupce prázdná = sloupec se skryje.
- **Výsledky**: sety z jednoho pole „6:3 1:6 10:6“ → `sety_z_textu()`; zpět do pole `implode(' ', json_pole($r['sety']))`.
- **Úvodní galerie**: typ `foto` / `deska`; u desky pole `deska_*` (titul je *inline*), u fotky `foto`, `fokus`,
  `alt`, `kredit`; `popisek` je *inline*.
- **Návštěvnost**: `cltk_visits` (zobrazení po dnech a cestách), `cltk_visit_log` (unikátní denní otisky).
  Bez cookies, roboti se nepočítají (`track_visit()`); ukládá se název stránky, ne adresa.
- **Relace**: klíče jen přes `relace('uid')` / `relace_nastav()` (pod `$_SESSION['cltk']`), soubory relací
  v `data/relace/`, přísný režim. Přihlášený správce = `current_user()` (= `relace_spravce()`, ověří i otisk hesla –
  po změně hesla platí jen relace, která ho změnila). Pole s `<em>`/`<br>` dostanou samy tlačítka
  „Zlatá kurzíva“ / „Nový řádek“ (admin.js podle nápovědy nebo vzoru pole).
- **CSP**: administrace nesmí mít vložený `<script>` ani `onclick=` (hlavička z `inc/auth.php` je nepustí) – skripty
  patří do `admin/assets/*.js`.

## 9. Lokální zkoušení

```bash
nastroje/kopie-db.sh agent-galerie                                    # vlastní kopie databáze
nastroje/spustit.sh 8810 web/data/agent-galerie.sqlite                # dva servery: 8810 a 8910
nastroje/spustit.sh 8810 web/data/agent-galerie.sqlite /cltkv2        # totéž jako v podsložce na testu
nastroje/zastavit.sh 8810
```

- Přihlášení: **admin@cltk.local / cltk-test-2026** (jen lokální seed, na serveru nikdy).
- Když váš port na 127.0.0.1 drží cizí proces: `CLTK_HOST=127.0.0.2 nastroje/spustit.sh …` (a stejně `zastavit.sh`).
- Směrovač `nastroje/router.php` napodobuje `.htaccess` (403 na `inc/ data/ sql/`, 404.php).
- Dnešní datum lze pro zkoušení posunout: `CLTK_DNES=2026-12-20` (jen nad SQLite) – kalendář, lišta, Přehled.
- Po práci: `web/data/chyby.log` musí být prázdný; ověřte stránku na 1440 px, `devices['iPhone 13']`
  a ve WebKitu (Playwright), bez vodorovného posunu na 390 px.
- Brzda přihlášení: 5 špatných pokusů za 15 minut z jedné IP → zámek (tabulka `cltk_login_attempts`).
