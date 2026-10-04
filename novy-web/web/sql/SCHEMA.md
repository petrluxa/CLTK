# Schéma databáze I. ČLTK Praha

Závazný popis tabulek pro všechny, kdo staví stránky a moduly administrace.
Zdroj pravdy je `web/sql/schema.sql`; tenhle soubor vysvětluje, co sloupce znamenají
a jak je plnit. **Při změně schématu upravte oba soubory** (a na běžícím serveru
je potřeba migrace – `instalace.php` doplní jen chybějící tabulky, ne sloupce).

## Pravidla, která platí pro všechny tabulky

- **Předpona `cltk_` všude.** Na serveru je databáze sdílená s ostrým webem TK Olymp
  (`uzivatele`, `clanky`, `polozky`, `obsah`, `navstevy`, `prihlaseni_pokusy`, `clanky_foto`).
  Funkce `q()` odmítne `CREATE/ALTER/DROP/TRUNCATE/DELETE/INSERT/UPDATE/REPLACE`
  i `SELECT … FROM/JOIN` na tabulku bez předpony (výjimkou jsou systémové katalogy).
  Na MySQL odmítne `DROP TABLE` vždy. **Do databáze choďte jen přes `q()/row()/rows()/val()`**,
  nikdy přes `db()->query()` napřímo.
- **Dotazy musí běžet v SQLite i v MySQL.** Nepoužívat `strftime`, `DATE_FORMAT`,
  `EXTRACT`, `IFNULL` (použijte `COALESCE`), `LIMIT ?` s parametrem (číslo vložte
  do SQL jako `(int)`), `ON DUPLICATE KEY` / `ON CONFLICT` (na upsert je `db_upsert_hit()`
  jen pro návštěvnost; jinde nejdřív `SELECT`, pak `INSERT` nebo `UPDATE`), `TRUE/FALSE`
  (pište `1/0`), `ILIKE`, `||` pro spojování řetězců.
- **Datum** je `DATE` v podobě `RRRR-MM-DD`, čas `DATETIME` `RRRR-MM-DD HH:MM:SS`
  (vždy z PHP: `date('Y-m-d H:i:s')`). Z formulářů datum vždy přes `normalizuj_datum()`.
  Porovnávat jako řetězce (`datum_od >= ?` s `date('Y-m-d')`) – funguje v obou.
- **TEXT sloupce** mají v SQLite výchozí `''`; na starším MySQL mohou být `NULL`.
  Při čtení vždy `(string)$r['sloupec']`, při zápisu vždy posílejte hodnotu (i `''`).
- **`visible`** = 1 zobrazit na webu, 0 skrýt (v adminu přepínač, „vypínat, ne mazat“).
  **`poradi`** = celé číslo, řadí se `ORDER BY poradi, id`. Šipky pořadí přečíslují
  skupinu 0, 1, 2… (helper `admin_posun()` v `admin/inc/ui.php`).
- **Soubory** (`foto`, `logo`, `obalka`, `soubor` …) jsou **relativní cesty uvnitř
  `web/uploads/`**, např. `galerie/vondrousova-2023.jpg`. Adresu pro HTML dává
  `upload_url($rel)`, WebP kopii `webp_vedle($rel)`. Prázdný řetězec = bez souboru.
- **`fokus`** = CSS `object-position`, např. `44% 28%` (kam se má fotka ořezávat).
- **Inline HTML** (sloupce označené *inline*): smí obsahovat jen `<em>…</em>`, `<strong>`,
  `<br>` a nezlomitelné mezery. Vypisovat **jen přes `html_inline()`**, nikdy `echo` napřímo.
- **Text z editoru** (sloupce označené *html*): ukládat přes `html_k_ulozeni()`
  (= `html_ocistit()` + odkazy na stránky a uploads/ bez `BASE_PATH`, tedy `clenstvi.php`,
  ne `/cltkv2/clenstvi.php` – odkazy přežijí přestěhování webu na cltk.cz) a vypisovat
  přes `html_ocistit()` (čistí se i na výstupu a cestu webu doplní; starší záznamy
  s `/cltkv2/…` opraví sám).
- **Prostý víceřádkový text** (*text*): vypisovat přes `paragraphs()`;
  **řádkové seznamy** (*řádky*): co řádek, to položka – `radky_seznam()`.
- **Sety** (`sety` ve výsledcích a triptychu) = JSON pole řetězců, např. `["6:3","1:6","10:6"]`,
  tiebreak v závorce `"9:8 (7:5)"`, skreč `"1:0 skr."`. Čte `sety_rozloz()`
  (vrací skóre + příznak výhry), z formuláře `sety_z_textu("6:3 1:6 10:6")`.

## Přehled tabulek

| Tabulka | Modul administrace | Kde se zobrazuje |
|---|---|---|
| `cltk_users` | Účet | – |
| `cltk_login_attempts` | (brzda přihlášení) | – |
| `cltk_settings` | Texty a údaje | všude (`setting()`) |
| `cltk_visits`, `cltk_visit_log` | Návštěvnost, Přehled | – |
| `cltk_oznameni` | Informační lišta | pruh pod menu |
| `cltk_uvodni_galerie` | Úvodní galerie | úvod – galerie se štítem |
| `cltk_aktuality` | Aktuality z klubu | úvod |
| `cltk_vysledky` | Výsledky hráčů | úvod (vodorovný pás), případně závodní tenis |
| `cltk_akce`, `cltk_signups` | Kalendář akcí | úvod (kalendář), `akce.php?id=` |
| `cltk_prihlasky_clenstvi` | Přihlášky | `clenstvi.php` (formulář) |
| `cltk_treneri` | Trenéři | trenérské týmy, privátní trenéři |
| `cltk_price_lists/sections/rows` | Ceníky | ceník kurtů, členství, škola, kempy |
| `cltk_skola` | Tenisová škola | stránky tenisové školy |
| `cltk_sluzby` | Areál a služby | `areal.php`, úvod (štítky) |
| `cltk_milniky`, `cltk_triptych`, `cltk_osobnosti`, `cltk_deska_zaznamy` | Historie | `historie.php`, úvod (triptych) |
| `cltk_revue`, `cltk_newslettery` | Revue a newslettery | `revue.php` |
| `cltk_vedeni`, `cltk_ctc` | Vedení a CTC | `vedeni.php`, `ctc.php`, `kontakt.php` |
| `cltk_bloky` | Stránky | texty všech podstránek |
| `cltk_partneri` | Partneři | úvod (mřížka 5 × 5) |
| `cltk_dokumenty` | Dokumenty | patička, `klub.php`, ceníky |

---

## Správa a nastavení

### `cltk_users` – účty administrace
| sloupec | typ | význam |
|---|---|---|
| id | int | |
| email | varchar(190), unikátní | přihlašovací jméno (e-mail) |
| password_hash | varchar | `password_hash()` |
| jmeno | varchar | zobrazované jméno v administraci |
| role | varchar | `admin` (jiné role zatím nejsou) |
| last_login, created_at | datetime | |

Lokálně seed zakládá `admin@cltk.local` / `cltk-test-2026`. Na serveru první účet
zakládá `instalace.php` z formuláře.

### `cltk_login_attempts` – brzda proti hádání hesla
`ip`, `tried_at`. Max. 5 pokusů za 15 minut na IP (viz `admin/inc/auth.php`).

### `cltk_settings` – Texty a údaje
| sloupec | význam |
|---|---|
| skey (PK) | klíč, čte se `setting('klic', 'výchozí')` |
| sval | hodnota (text) |
| label | popisek pole v administraci |
| grp | skupina panelu v „Texty a údaje“ (viz níže) |
| typ | `text` · `textarea` · `inline` (smí `<em>`) · `url` · `email` · `tel` · `bool` (0/1) · `heslo` (ukládá se hash, pole se nevyplňuje) · `soubor` (cesta v uploads/) · `cislo` |
| napoveda | text pod polem |
| poradi | pořadí v rámci skupiny |

Skupiny `grp` a jejich pořadí v administraci: `kontakt` (Kontakty a adresa) · `paticka`
(Patička) · `odkazy` (Odkazy a rezervace) · `site` (Sociální sítě) · `uvod` (Úvodní strana – video)
· `prihlasky` (Přihlášky a e-mail) · `rezim` (Režim přípravy a náhled). Skupina **`system`
se v administraci nezobrazuje** (tajný klíč, verze schématu) – modul Texty a údaje ji musí přeskočit.

Přehled klíčů (výchozí hodnoty plní `sql/seed/10-nastaveni.php`):

| klíč | skupina | obsah |
|---|---|---|
| `klub_nazev`, `klub_zkratka`, `zalozeno`, `ico` | kontakt | I. Český Lawn-Tennis Klub Praha · I. ČLTK Praha · 1893 · 45243077 |
| `adresa_ulice`, `adresa_mesto` | kontakt | Ostrov Štvanice 38 · 170 00 Praha 7 |
| `prijezd_kratce` | kontakt | text do patičky (Hlávkův most, HolKa, parkoviště) |
| `prijezd_text` | kontakt | podrobný popis příjezdu (kontakt.php, areal.php) |
| `mapa_url` | kontakt | odkaz „Mapa“ (Google Maps) |
| `recepce_popis`, `recepce_telefon`, `recepce_email` | kontakt | Recepce – rezervace kurtů |
| `kancelar_jmeno`, `kancelar_popis`, `kancelar_telefon`, `kancelar_email` | kontakt | Eva Štefková |
| `ucet_clenstvi`, `ucet_iban`, `ucet_skola` | kontakt | bankovní účty |
| `paticka_ctc` | paticka | „Jediný český člen Centenary Tennis Clubs · …“ |
| `paticka_pristupnost` | paticka | text u přepínačů přístupnosti |
| `paticka_pruh` | paticka | text spodního pruhu bez „© rok“ (rok doplní šablona: `'© ' . date('Y') . ' ' . …`) |
| `rezervace_url` | odkazy | Roger Online (tlačítko Rezervovat kurt, nové okno) |
| `obsazenost_url` | odkazy | obsazenost kurtů |
| `restaurace_url` | odkazy | **prázdné = menu vede na `restaurace.php`** |
| `prague_open_url` | odkazy | **prázdné = menu vede na `prague-open.php`** |
| `kempy_prihlaska_url` | odkazy | přihláška na letní kempy (Google formulář) |
| `facebook_url`, `instagram_url`, `fotogalerie_url`, `youtube_url` | site | sítě |
| `video_1080`, `video_720`, `video_poster` | uvod | soubory videa členství v uploads/ (`video/cltk-promo-1080.mp4` …) |
| `prihlasky_email` | prihlasky | kam posílat upozornění na přihlášky; **prázdné = jen ukládat** |
| `rezim_pripravy` | rezim | 1 = návštěvník vidí přípravnou stránku |
| `rezim_nadpis`, `rezim_text` | rezim | texty přípravné stránky |
| `nahled_heslo_hash` | rezim | hash náhledového hesla (`typ=heslo`; nastavuje se přes `nahled_heslo_nastav()`) |
| `system_klic` | system | tajný klíč pro podpisy cookies (generuje se při instalaci) |
| `schema_verze` | system | číslo verze schématu |

### `cltk_visits`, `cltk_visit_log` – návštěvnost bez cookies
`visits(day, path, hits)` – zobrazení stránek po dnech; `visit_log(day, visitor)` – denní
nevratný otisk návštěvníka (unikátní návštěvníci). Plní `track_visit()`; roboti se nepočítají.
`path` = **název stránky** (`/`, `/klub.php` – soubor skriptu, ne adresa z požadavku), takže `/index.php/cokoli`
ani parametry nové řádky nezakládají. Nejvýš 20 000 otisků za den, záznamy starší 13 měsíců maže `navstevnost_uklid()`.

---

## Úvodní stránka

### `cltk_oznameni` – informační lišta
| sloupec | význam |
|---|---|
| text | text zprávy (prostý, šablona ho vysází prostrkanými verzálkami) |
| odkaz | volitelný odkaz (`https://…` nebo `stranka.php`) |
| plati_od, plati_do | DATE nebo NULL (bez omezení); platí včetně obou dnů |
| visible | zapnuto |
| poradi | při více aktivních zprávách |

Aktivní zprávy vrací `aktivni_oznameni()`; **když je pole prázdné, lišta se nevypíše vůbec.**

### `cltk_uvodni_galerie` – galerie se štítovým přechodem
| sloupec | význam |
|---|---|
| typ | `foto` nebo `deska` (navy deska s výsledkem místo fotky) |
| rejstrik | krátký štítek v rejstříku pod galerií („Vondroušová“, „BJK Cup 2026“) |
| foto, foto_w, foto_h | soubor v uploads/galerie/ a jeho rozměr (pro `width/height`) |
| fokus | object-position, např. `44% 28%` |
| alt | alternativní text fotky / aria-label desky |
| popisek | *inline* – popisek pod galerií (`<em>Markéta Vondroušová</em> s mísou …`) |
| kredit | „foto Martin Sidorják“ |
| deska_stitek | „Billie Jean King Cup · 2026“ |
| deska_titul | *inline* – „Česko<br><em>šampionem</em>“ |
| deska_tym_a, deska_skore, deska_tym_b | „Česko“ · „2 : 0“ · „Ukrajina“ |
| deska_hrac, deska_souper, deska_sety | „Karolína Muchová“ · „Anhelina Kalininová“ · „6:2 · 6:3“ |
| deska_misto | „Šen-čen · 27. září 2026 · dvanáctý titul Česka“ |

Načíst: `rows("SELECT * FROM cltk_uvodni_galerie WHERE visible = 1 ORDER BY poradi, id")`.
Římské číslice rejstříku generuje šablona (`rimske()`).

### `cltk_aktuality` – aktuality z klubu (nejsou to články)
`nadpis`, `popis` (*text*, krátký), `foto` (uploads/aktuality/), `fokus`, `odkaz` + `odkaz_text`
(nepovinné; bez odkazu se karta jen zobrazí), `visible`, `poradi`.

### `cltk_vysledky` – výsledky hráčů
| sloupec | význam |
|---|---|
| datum | DATE zápasu (řadí se podle něj sestupně) |
| misto | „Šen-čen“, „New York“ |
| stitek | zlatý štítek verzálkami: „Billie Jean King Cup · finále · Česko – Ukrajina 2:0“ |
| hraci | „Karolína Muchová a Jakub Menšík“ |
| souper | soupeř(i), nepovinné |
| text | *text* – krátký komentář |
| sety | JSON pole, viz `sety_rozloz()` |
| verdikt | „Titul“, „Finále“, „Semifinále“ … |
| odkaz | volitelný odkaz na zprávu |
| foto | volitelná fotka (uploads/vysledky/) |

Načíst: `posledni_vysledky(8)` (viditelné, `ORDER BY datum DESC, poradi, id`).

---

## Kalendář akcí a přihlášky

### `cltk_akce`
| sloupec | význam |
|---|---|
| nazev | „Klubový den – čtyřhry, barbecue, kvíz“ |
| rok, mesic | rok a měsíc (1–12) – **vždy vyplněné**, i když datum chybí („termín doplní klub“) |
| datum_od, datum_do | DATE nebo NULL; `datum_do` jen u vícedenních |
| termin_text | vlastní text termínu místo data („přetlakové haly od 5. 10.“, „potvrzeno“); prázdné = složí se z dat, a když nejsou, „termín doplní klub“ – viz `akce_termin()` |
| cas, misto | „od 19:00“, „Letenský zámeček“ |
| stitek | štítek v okně detailu (např. „Klubová akce“) |
| perex | *text* – krátký popis do okna kalendáře |
| popis | *html* – delší popis (`akce.php`) |
| foto | volitelná fotka (uploads/akce/) |
| odkaz, odkaz_text | odkaz ven (web turnaje …) |
| prihlaseni_povoleno | 1 = zobrazit tlačítko **Přihlásit se** a formulář |
| formular | JSON nastavení přihlášky této akce (viz níže); prázdné = výchozí pole (telefon, počet osob, poznámka) |
| prihlaseni_do | DATE nebo NULL – uzávěrka přihlášek |
| kapacita | NULL = bez omezení; jinak max. součet `pocet` z přihlášek |
| poznamka_interni | jen pro administraci (např. „ověřit název belgického klubu“) |

Pomůcky: `akce_rok($rok)` (seznam pro kalendář), `nejblizsi_akce()` (výchozí okno),
`kalendar_data()` a `akce_pro_js($a)` (data pro JS kalendáře), `akce_probehla($a)`, `akce_termin($a)`,
`akce_prihlaseni_otevreno($a)`, `akce_obsazeno($a)`, `akce_obsazenost($id)` (součet osob).

**Formulář přihlášky (`cltk_akce.formular`)** – u každé akce jiný (klient formuláře teprve dodá).
Jméno, e-mail a souhlas se zpracováním údajů jsou vždy. JSON:

```json
{
  "pole": {"telefon": true, "pocet": true, "poznamka": true},
  "povinne": {"telefon": false},
  "vlastni": [
    {"klic": "f1", "popisek": "Úroveň hry", "typ": "vyber", "moznosti": ["začátečník", "pokročilý"], "povinne": true},
    {"klic": "f2", "popisek": "Počet dětí", "typ": "cislo", "povinne": false},
    {"klic": "f3", "popisek": "Mám zájem o barbecue", "typ": "zaskrtavatko", "povinne": false},
    {"klic": "f4", "popisek": "Spoluhráč", "typ": "text", "povinne": false}
  ],
  "tlacitko": "Přihlásit se",
  "poznamka": "Přihlášky do 20. 5."
}
```

`typ`: `text` · `cislo` · `vyber` (s `moznosti`) · `zaskrtavatko`. `klic` je stálý identifikátor
pole (`f1`, `f2`… – nemění se při přejmenování popisku). Normalizovaný tvar vrací
`akce_formular($akce)`; odeslaný formulář ověří `akce_formular_zpracuj($akce, $_POST)`
(vrací `['ok' => bool, 'chyby' => [pole => hláška], 'radek' => [sloupce pro INSERT do cltk_signups]]`).

### `cltk_signups` – přihlášky k akcím
`akce_id`, `jmeno`, `email`, `telefon`, `pocet` (počet osob), `poznamka`, `souhlas` (0/1),
`odpovedi` (JSON pole odpovědí na vlastní pole: `[{"klic":"f1","popisek":"Úroveň hry","hodnota":"pokročilý"}]`
– popisek se ukládá s odpovědí, aby export CSV seděl i po úpravě formuláře), `vyrizeno` (0/1),
`poznamka_admin`, `created_at`. Akce může být mezitím smazaná → v adminu `LEFT JOIN cltk_akce`.
Sloupce pro tabulku a CSV dává `signup_sloupce($akce, $prihlasky)` (standardní pole podle
nastavení akce + všechna vlastní pole, která se v přihláškách vyskytla) a hodnotu buňky
`signup_hodnota($prihlaska, $klic)`.

### `cltk_prihlasky_clenstvi` – přihlášky do klubu
| sloupec | význam |
|---|---|
| jmeno, prijmeni, email, telefon | hlavní žadatel (pro přehled a hledání) |
| typ_clenstvi | text z ceníku („1 dospělý hrající + 1 dítě“) |
| cena | spočtená roční cena v Kč, nebo NULL |
| pocet_osob | 1–5 |
| data | **JSON celého formuláře** (pohlaví, datum narození, rodné číslo, občanství, adresa, profese, „jak jste se o nás dozvěděli“, `osoby` = pole dalších osob, zákonný zástupce, firma/IČO …) – rodné číslo jen sem a **zašifrované** (`s1:…` / `o1:…`, `citlive_zasifruj()` klíčem `SIFROVACI_KLIC` z `cltk-config.php`; bez klíče se rodné číslo nesbírá) |
| souhlas_gdpr, souhlas_podminky | 0/1 |
| stav | `nova` · `vyrizena` · `zamitnuta` |
| poznamka_admin | poznámka kanceláře |

Vyřízené a zamítnuté přihlášky do klubu i přihlášky k akcím (`cltk_signups`) starší než nastavení
`prihlasky_mazat_mesicu` (výchozí 12, 0 = nemazat) se samy mažou (`admin_uklid_prihlasek()` v `admin/inc/layout.php`).

---

## Trenéři, ceníky, škola, areál

### `cltk_treneri`
| sloupec | význam |
|---|---|
| jmeno, role | „Petr Vaníček“ · „sportovní ředitel a vedoucí SCM“ |
| zarazeni | `zavodni` (Závodní tenis) · `skola` (Tenisová škola) · `privatni` (Privátní trenéři). Kdo je ve dvou týmech, má dva řádky. |
| skupina | podnadpis uvnitř týmu („Trenéři kategorie do 14 let“, „Kondiční trenéři“) |
| foto, fokus | portrét (uploads/treneri/) |
| fakta | *řádky* – co řádek, to fakt |
| text | *text* – volný popis |
| telefon, email, kontakt | kontakt; `kontakt` = volný text (např. „přes recepci“) |

### `cltk_price_lists` / `cltk_price_sections` / `cltk_price_rows` – ceníky
List podle `klic`: `kurty-leto`, `kurty-zima`, `clenstvi`, `skola`, `kempy`, `doplnkove`.
Načíst: `cenik('kurty-zima')` → list se sekcemi a řádky (viz PRUVODCE-FRONT.md).

`price_lists`: `klic`, `nazev`, `podnazev`, `obdobi`, `poznamka_nahore` (*text*), `poznamka_dole` (*text*),
`pdf_url` (odkaz „Ceník k tisku“ pod ceníkem: stránka dokumentu `dokument.php?d=cenik-zima-2026-2027`, nebo
https://…; ceníky se jako PDF nenahrávají), `visible`, `poradi`.

`price_sections`: `list_id`, `nazev` (např. „Přetlaková hala · antuka“), `popis` („kurty 5, 6 · od 5. 10. 2026“),
hlavičky sloupců `hl_nazev`, `hl_cena`, `hl_cena_clen`, `hl_cena_sezona`, `hl_cena_sezona_clen`
(**prázdná hlavička = sloupec se nezobrazí**), `poradi`.

`price_rows`: `section_id`, `nazev`, `poznamka`, `cena`, `cena_clen`, `cena_sezona`, `cena_sezona_clen`
(ceny jako text, např. `500 Kč/hod`, `21 000 Kč`), `poradi`.

### `cltk_skola` – tenisová škola
`typ`: `info` (odstavce informací), `harmonogram` (termíny sezóny, prázdniny – `datum_od/do`, `termin_text`),
`rozvrh` (jedna buňka rozvrhu – viz níže), `kemp` (termín kempu – `datum_od/do`,
`nazev`, `cena`, `text`). Další sloupce: `text` (*text*), `odkaz`, `visible`, `poradi`.
Ceny kempů a školy jsou v cenících (`kempy`, `skola`), tady jsou termíny.

Řádek `rozvrh` = jedna hodina skupiny na jednom kurtu (stránka `tenisova-skola-rozvrhy.php` z nich skládá
týdenní mřížku: dny × hodiny, pro každý kurt zvlášť):

| sloupec | význam |
|---|---|
| nazev | název rozvrhu – řádky se stejným názvem tvoří jeden rozvrh („Zima 2026/27“, „Týden 29. 9. – 2. 10. 2026“) |
| datum_od, datum_do | platnost rozvrhu (stejná u všech jeho řádků); rozvrh po `datum_do` se na webu neukáže |
| den | `pondělí` … `neděle` |
| cas | „16:00–17:00“ (přes dvě hodiny „17:00–19:00“) |
| misto | kurt („Kurt 5 · antuka“) – podle něj se rozvrh dělí na mřížky |
| skupina | název skupiny (nepovinný); **`obsazeno`** = kurt je v tu dobu obsazený (šedá buňka, ne trénink školy) |
| trener | „3 trenéři“ / jméno trenéra (nepovinné) |
| text | poznámka v buňce („samostatný sparing“) |

Dvě buňky se stejným dnem, časem a kurtem = dvě souběžné skupiny (kurt 5 má hodinu rozdělenou na půlky).
**Jména dětí do rozvrhu nepatří** – rozvrh je jen po hodinách a skupinách.

### `cltk_sluzby` – služby v areálu
| sloupec | význam |
|---|---|
| nazev | „Venkovní bazén“ |
| kotva | id sekce na `areal.php` (`bazen`) → odkaz `url('areal.php') . '#' . kotva` |
| perex | jedna věta |
| text | *text* – popis |
| fakta | *řádky* |
| foto, fokus | uploads/sluzby/ |
| casy | otevírací doba („všední dny 16:00–20:00“), prázdné = „doplní klub“ |
| odkaz | volitelný odkaz (např. rezervace) |
| je_stitek | 1 = pilulka v „Služby v areálu“ na úvodu |

---

## Historie

### `cltk_milniky` – kronika
`rok` (int, řazení), `rok_text` (zobrazený text, když je jiný než rok – „1846–1849“, „počátek 18. století“),
`era` (dobové jméno klubu – razítko), `titulek`, `text` (*text*), `foto`, `foto_popisek`,
`zdroj` (pramen), `jistota` (např. „ověřeno“, „podle klubu“), `visible`, `poradi` (v rámci roku).
Řadit `ORDER BY rok, poradi, id`.

### `cltk_triptych` – tři wimbledonské trávy
`rok` („1954“), `jmeno`, `disciplina` („Wimbledon · dvouhra mužů · finále“), `foto` (černobílá
z PDF klienta), `fokus`, `alt`, `vitez` a `souper` (příjmení do tabulky skóre), `sety` (JSON),
`hral_za` („Egypt“), `hral_za_text` (*text*), `visible`, `poradi`.

### `cltk_osobnosti` – medailony
`jmeno`, `kategorie` („Čestný člen“), `roky` („1921–2001“), `cin` (*inline*), `foto`, `fokus`, `alt`,
`pramen`, `jistota` (`overeno` · `klub`), `jistota_text` („ověřeno“, „vazba podle klubu“).

### `cltk_deska_zaznamy` – Zlatá deska
| sloupec | význam |
|---|---|
| kategorie | `grandslam` · `cestni` · `mistri` · `oh` · `zasluzili` · `prezidenti` |
| skupina | `hlavni` = řádek desky (rok · jméno · čin) · `jmena` = jen jméno do výčtu (historičtí čestní členové, zasloužilí, prvních 36 let prezidentů) |
| rok | text („1948“, „2011–2022“, „12×“, „2022–“) |
| jmeno, cin | |
| pramen | drobná poznámka („pravděpodobně“, „podle klubu“, „ve štvanické historii“) |
| metr | jen grandslam: `stvanice` nebo `stvanice klub` (dvojí metr – klubová definice / v barvách klubu) |
| historie | 1 = tlumený řádek ze štvanické historie |

Texty desky (perexy záložek, poznámky) jsou v `cltk_bloky` se `stranka = 'historie'`.

---

## Revue, newslettery, vedení, CTC

### `cltk_revue` – I.ČLTK Revue
`rok`, `cislo` (1 = jarní, 2 = podzimní, **0 = speciální číslo** – jubilejní Revue 1893–2023),
`oznaceni` („01/2026“, „1893–2023“), `obalka` (uploads/revue/revue-2026-1.jpg),
`obalka_popis`, `titulky` (JSON pole), `obsah` (JSON pole dvojic `["06","Velký titul pro Karolínu"]`),
`stran`, `naklad`, `uzaverka`, `pdf_soubor` (nahrané PDF v uploads/revue/pdf/ – „revue/pdf/revue-2026-1.pdf“,
má přednost), `pdf_url` (odkaz ven, jen když PDF leží jinde), `pdf_mb` („12,7“), `visible`, `poradi`.
Řadit `ORDER BY rok DESC, cislo DESC`. Adresu PDF dává `revue_cisla()` → `$c['pdf']`.

### `cltk_newslettery`
`rok`, `cislo` („5“), `oznaceni` („5/2025“, „130 let“), `nazev` (volitelný), `pdf_cs_soubor`,
`pdf_en_soubor` (nahraná PDF v uploads/newslettery/ – „newslettery/newsletter-2025-5.pdf“, mají přednost),
`pdf_cs`, `pdf_en` (odkazy ven, jen když PDF leží jinde), `visible`, `poradi`.
Adresy dává `newslettery()` → `$n['cs_url']`, `$n['en_url']`.

Archiv Revue a newsletterů ze starého webu (files.cltk.cz) je od 2. 10. 2026 nahraný na webu
(migrace `sql/migrace/2026-10-02-dokumenty-archiv.php`). Starý web se vypne – odkazy na cltk.cz
a files.cltk.cz do databáze nepatří (výjimkou jsou e-mailové adresy @cltk.cz).

### `cltk_vedeni`
`skupina`: `vybor` (Výkonný výbor), `kancelar` (kancelář klubu), `kontakt` (další kontakty – wellness,
tenis shop, webmaster). `jmeno`, `funkce`, `telefon`, `email`, `zobrazit_kontakt` (0 = telefon a e-mail
na webu nevypisovat), `foto`, `text`. Prezidenti jsou v Zlaté desce (`kategorie = prezidenti`).

### `cltk_ctc` – Centenary Tennis Clubs
`typ`: `fakt` (odstavce o CTC), `klub` (kluby, které hrály na Štvanici), `utkani` (mezinárodní utkání),
`soutez` (Carrickmines Cup, I. ČLTK Praha Cup, CTC Senior), `rodokmen` (rodokmen stoletých klubů –
`rok`, `nazev`, `misto`, `text`; `zvyraznit = 1` u I. ČLTK). `odkaz`, `zdroj`.

---

## Stránky, partneři, dokumenty

### `cltk_bloky` – texty podstránek
Unikátní dvojice `stranka` + `klic`. `stranka` = název souboru bez `.php` (`klub`, `areal`,
`body-solution`, `index` …). Načíst `blok('areal', 'uvod')` (vrací řádek nebo prázdný blok)
nebo `bloky('areal')` (všechny viditelné bloky stránky podle `poradi`).

| sloupec | význam |
|---|---|
| stitek | malý štítek nad nadpisem („Areál a služby“) |
| nadpis | *inline* – nadpis s `<em>` („Devatenáct kurtů, bazén a <em>klid</em>…“) |
| perex | *text* |
| text | *html* z editoru |
| foto, foto_popisek | |
| odkaz, odkaz_text, odkaz2, odkaz2_text | tlačítka (hlavní a vedlejší) – stránka webu (`clenstvi.php#prihlaska`, `dokument.php?d=plan-arealu`), soubor webu (`uploads/…`) nebo https://… |
| doplni_klub | 1 = obsah zatím chybí → šablona ukáže štítek „doplní klub“ |

Seznam výchozích bloků je v `sql/seed/60-bloky.php` a v PRUVODCE-FRONT.md.

### `cltk_partneri`
`nazev`, `url` (prázdné = logo bez odkazu, např. Glenfiddich), `logo` (barevný originál v uploads/partneri/),
`logo_mono` (jednobarevné průhledné PNG v uploads/partneri/mono/ – **na webu se zobrazuje to**), `visible`, `poradi`.
Výchozích 25 partnerů má `logo_mono` = loga dodaná klientem (`podklady/klient-loga/partneri/`, béžová
#a3947e při krytí 60 %) **beze změny**. `partner_logo_mono()` se volá jen u loga nově nahraného
v administraci a vyrábí stejný odstín; vždy založí nový soubor (staré logo se maže až po uložení).

### `cltk_dokumenty`
Dokumenty klubu jsou **stránky webu v klubovém stylu, ne PDF** (rozhodnutí klienta 2. 10. 2026): stanovy,
pravidla hraní a rezervací, provozní řády, osobní údaje členů, ceník, plán areálu → `dokument.php?d=slug`
(hlavičkový papír klubu, tisk na A4 místo PDF). PDF zůstávají jen u **příloh archivu klubových turnajů**
(kategorie `turnaje` – pozvánky, rozlosování, výsledky) a u Revue a newsletterů (vlastní tabulky).

| sloupec | význam |
|---|---|
| nazev | „Stanovy I. ČLTK Praha“ (nadpis stránky dokumentu i položka v seznamech); u archivu celý název „Babolat Non Profi Cup 2018 – pozvánka a pravidla soutěže“ |
| kategorie | `klub` · `clenstvi` · `provoz` · `cenik` · `skola` · `turnaje` (archiv) – `DOKUMENTY_KATEGORIE` v inc/data.php |
| popis | krátký popis do seznamů a řádek pod nadpisem dokumentu („Účinnost od 20. 6. 2017“) |
| slug | adresa stránky `dokument.php?d=slug` (malá písmena, číslice, pomlčky; jedinečnost hlídá administrace, ne databáze) |
| text | *html* z editoru – celý dokument (tabulky jako holé `table/tr/th/td`); vypisuje se přes `html_ocistit()` |
| rok, skupina | jen archiv: rok a název turnaje / řady („Babolat Non Profi Cup“) – podle nich se archiv seskupuje |
| stran | jen archiv: počet stran PDF (zjistí se při nahrání, `pdf_pocet_stran()`) |
| soubor, soubor_nazev | jen archiv: nahrané PDF v uploads/dokumenty/turnaje/ a jeho původní název |
| url | odkaz jinam – výjimečně, když dokument leží na jiném webu |
| v_paticce, paticka_text | 1 = ve sloupci „Dokumenty a sítě“ v patičce; text odkazu |
| visible, poradi | |

Adresy: `dokument_url($r)` = stránka s textem (když má `slug` i `text`), jinak nahrané PDF, jinak odkaz;
`dokument_podle_slugu()`. Seznamy: `dokumenty($kategorie)` (bez archivu), `dokumenty_archiv()` (archiv turnajů
po turnajích a letech), rozcestník všech dokumentů `dokumenty.php`.

---

## Výchozí obsah, instalace a přenos dat

| soubor | co dělá |
|---|---|
| `sql/schema.sql` | jen `CREATE TABLE` (bez indexů navíc), `db_install()` ho převede na `CREATE TABLE IF NOT EXISTS` pro SQLite i MySQL |
| `sql/seed.php` | lokálně (CLI): `--novy` = nová SQLite, `--z-dat` = obsah ze `sql/data.json`; založí zkušební účet `admin@cltk.local` / `cltk-test-2026` a vypne režim přípravy |
| `sql/seed-pomocne.php` | pomůcky sad: `seed_prazdna()`, `seed_vloz()`, `seed_nastaveni()`, `seed_blok()`, `seed_obrazek()`, `seed_kopiruj()`, `seed_import_dat()` |
| `sql/seed/NN-*.php` | dílčí sady podle abecedy; každá plní jen prázdnou tabulku / chybějící klíče, jde pustit opakovaně |
| `sql/export-dat.php` | lokálně (CLI): obsah do `sql/data.json` (+ `--sql` → `sql/data.sql` pro ruční import do MySQL); ověří délky textů proti `VARCHAR` |
| `instalace.php` | server: klíč `INSTALL_KEY` → chybějící tabulky → `data.json` do prázdných tabulek → výchozí sady → režim přípravy zapnout → první účet z formuláře; s existujícím účtem odmítne |

Sady: `10-nastaveni` · `20-uvod` (lišta, galerie, aktuality, výsledky) · `30-akce` · `35-treneri` ·
`40-ceniky` · `42-skola` · `45-sluzby` · `50-historie` · `55-revue` (+ newslettery) · `56-areal` (další bloky
areálu, ceníku kurtů, Restaurace, Prague Open a Kontaktu) · `57-vedeni-ctc` · `58-klub` (další bloky sekce Klub) ·
`58-tenis` (další bloky závodního tenisu a Tenisové školy) · `60-bloky` (základní bloky všech stránek) ·
`65-partneri` · `70-dokumenty` · `75-video`. Dvojice stránka + klíč se mezi sadami nesmí opakovat
(vložený blok další sada nepřepíše) – dnes 128 bloků, žádná duplicita.

**Nepřenáší se nikdy** (`SEED_NEPRENASET`): `cltk_users`, `cltk_login_attempts`, `cltk_visits`,
`cltk_visit_log`, `cltk_signups`, `cltk_prihlasky_clenstvi` (účty, osobní údaje, statistika) a nastavení
`system_klic`, `nahled_heslo_hash`, `rezim_pripravy` – každá instalace má vlastní.

Obrázky a video (`uploads/`) v datech nejsou – na server se nahrávají zvlášť (deploy `--uploads`).
Na serveru zdroje seedu (`cltk-navrhy`, `podklady/`) nejsou; sady tam jen najdou hotové soubory v `uploads/`.

**Když přibude tabulka nebo sloupec:** upravit `schema.sql` i tento soubor, doplnit sadu seedu
a na běžícím serveru migraci (instalace doplní jen chybějící tabulky, ne sloupce). Export
(`export-dat.php`) bere všechny tabulky `cltk_` automaticky.

**Brzdy v `cltk_login_attempts`** (sloupec `ip` s předponou): `<ip>` = přihlášení do administrace
(5 / 15 min), `nahled:<ip>` = náhledové heslo (5 / 15 min), `form:<ip>` = odeslané veřejné formuláře
(8 / hodinu, `verejny_formular_over()`).
