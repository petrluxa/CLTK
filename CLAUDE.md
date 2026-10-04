# CLAUDE.md – web I. ČLTK Praha (návod pro další sezení)

Nový web + administrace klubu I. ČLTK Praha. PHP 8 + PDO bez frameworku, lokálně SQLite,
na serveru MySQL (Forpsi, PHP 8.5). Vše česky se správnou diakritikou („…“, –, nezlomitelné mezery).
**Závazné zadání: `ZADANI.md`** (sekce 0 = změny 1. 10. 2026, mají přednost). Přehled pro lidi: `README.md`.

## Stav (1. 10. 2026)
Hotovo a ověřeno: všechny stránky z menu (ZADANI §3), úvod podle PDF, 20 modulů administrace,
seed, instalace (`web/instalace.php`) a přenos obsahu (`web/sql/data.json`). Web zatím není na serveru
(nasazení dělá jen orchestrátor). Chybějící obsah = štítek „doplní klub“ (README §9) – nevymýšlet.

**4. 10. 2026 – bez starého webu (ZADANI §0b):** dokumenty klubu = stránky `dokument.php?d=slug` (text v
`cltk_dokumenty.text`, tisk na A4 místo PDF), rozcestník `dokumenty.php`, archiv klubových turnajů (PDF),
Revue + newslettery jako PDF v `uploads/`, jubilejní Revue 1893–2023 (`cltk_revue.cislo = 0`), rozvrhy
Tenisové školy (`cltk_skola` typ `rozvrh`). Na cltk.cz / files.cltk.cz se nesmí odkazovat (kromě e-mailů).
Běžící server se převádí migrací `web/sql/migrace/2026-10-02-dokumenty-archiv.php` (+ `.data.json`).

## Tvrdá pravidla
- **Tabulky jen s předponou `cltk_`.** Produkční DB je sdílená s ostrým webem TK Olymp. Do DB jen přes
  `q()/row()/rows()/val()/db_insert()/db_update()` (pojistka `sql_pojistka()` v `web/inc/db.php`).
  Dotazy musí běžet v SQLite i MySQL (žádné `strftime`, `IFNULL`, `||`, `LIMIT ?`, `ON CONFLICT` mimo db.php).
- **Nikdy:** FTP/server/síťové nasazení, čtení `deploy/`, cokoli proti MySQL. Jen lokální SQLite.
- `C:\Users\Asus\Dokumenty\Code\ltkliberec-web\` (vzor) je **ostrý web jiného klubu – jen číst**.
- Nikdy nenahrávat/neměnit pro server `web/inc/config.local.php` (lokální config → shodil by server).
- Testy jen nad **vlastní kopií DB** (`nastroje/kopie-db.sh jmeno` → `CLTK_DB_FILE=web/data/jmeno.sqlite`)
  a na vlastním portu (+100 druhý server). Servery po sobě zastavit (`nastroje/zastavit.sh <port>`).
  Porty 8801/8802 na 127.0.0.1 drží lokální náhled LTK Liberec – nesahat (`CLTK_HOST=127.0.0.2`).
- Nemazat pomocí `rm -rf` s globem (bezpečnostní kontrola to zablokuje) – mazat konkrétní soubory.
- Po změně `sql_pojistka()` (web/inc/db.php) vždy `./_php/php.exe nastroje/test-pojistka.php` (obchvaty z revize
  + všechny doslovné dotazy z kódu). Nový druh dotazu, který pojistka odmítne, se nepovoluje „obejitím“ – upravit pojistku.
- **CSP:** veřejný web smí mít jediný vložený skript `SABLONA_SKRIPT_HLAVICKY` (komponenty.php, otisk se počítá sám),
  administrace žádný; žádné `onclick=` apod. – skripty patří do `assets/js/` a `admin/assets/`.
- **Relace** jen přes `relace('klic')` / `relace_nastav()` (klíče pod `$_SESSION['cltk']`), přihlášený správce
  `current_user()` / `relace_spravce()` (ověří otisk hesla). Soubory relací v `web/data/relace/`.

## Kde co je
- Jádro `web/inc/`: `functions.php` (výstup, typografie, data, url/asset/upload_url, `setting()` s pamětí,
  CSRF, veřejné formuláře bez cookies, návštěvnost), `data.php` (menu, kalendář, ceníky, bloky…),
  `html.php` (sanitizér, `html_k_ulozeni()`), `soubory.php` (GD, WebP, PDF, loga partnerů), `rezim.php`.
- Šablona `web/inc/sablona/` (head, hlavička, patička, `komponenty.php` = `hlava_stranky()`, `hlava_sekce()`,
  `obr()`, `cenik_html()`, `osoba_html()`, `tlacitko()`, `doplni_klub()`…).
- Design: `web/assets/css/styl.css` (Varianta 4 – tokeny, komponenty), `index.css` (úvod), `v2.css`
  (kiosek, medailony), `stranky-klub/areal/tenis.css`; JS `app.js` (menu, záložky, dialog, zámek rolování
  na `<html>`, přístupnost) + skripty sekcí. Logo jen přes `logo_url()` (`web/assets/img/logo*`).
- Admin `web/admin/` (`inc/layout.php` – pořadí modulů `admin_moduly()`, `inc/ui.php` – pole a tlačítka).
  Modul Stránky: známé bloky v `STRANKY_BLOKY` (nejdou smazat/přejmenovat), bloky podle předpony v `STRANKY_PREDPONY`.
- Data: `web/sql/schema.sql` + `SCHEMA.md` (měnit obojí), sady `web/sql/seed/NN-*.php`
  (dvojice stránka+klíč bloku se mezi sadami nesmí opakovat), export `web/sql/export-dat.php`.
- Návody: `web/inc/PRUVODCE-FRONT.md`, `web/PRUVODCE-STRANKY.md`, `web/admin/PRUVODCE.md`.
- Testovací skripty: `podklady/_raw/qa/<agent>/` (mimo git), integrační v `podklady/_raw/qa/integrace/`.

## Pasti (z Liberce, Olympu a z této stavby)
- `strip_tags` nechává atributy → `html_ocistit()` skládá HTML z holých značek. Text z editoru ukládat
  `html_k_ulozeni()` (odkazy bez `/cltkv2/`), vypisovat `html_ocistit()`.
- `e()` na každý výstup; odkazy z adminu přes `bezpecny_odkaz()`; obrázky jen z `uploads/`.
- CSRF na každém POST v adminu; na veřejném webu **ne** `csrf_field()` (založí cookie) →
  `verejny_formular_pole/over/zapis()`.
- Šipky pořadí / přepnout / smazat ve **vlastních formulářích** (Enter v poli jinak odešle šipku); žádný `form=`.
- Pole v adminu ≥ 16 px (Safari zoom), cíle ≥ 44 px; zámek rolování na `<html>`, ne `<body>` (Safari).
- Datum z formuláře vždy `normalizuj_datum()`; žádný výstup před `header()`; po zápisu `redirect()`.
- Starý soubor mazat **až po** úspěšném zápisu do DB; nahrané obrázky znovu uložit přes GD.
- `setting()` má statickou paměť – zapisovat jen `setting_set()` (obnoví ji).
- `background-clip:text` ukrojí háčky (padding-top .1em). Diakritika v WebKitu na Windows se v Playwrightu
  vykresluje s posunutými čárkami – artefakt, ne chyba webu.
- Celostránkové snímky v Chromiu kreslí pevnou hlavičku doprostřed a líné obrázky prázdné – před snímkem
  přepnout `img[loading=lazy]` na eager a hlavičku na `position:absolute` (dělá `integrace/snimky.mjs`).
- iPhone profil Playwrightu nemá v UA „headless“ → počítá se do návštěvnosti (testovací DB pak mají záznamy).
- Kalendář: „nejbližší akce“ počítá server (`nejblizsi_akce()`) i JS (`kalendar.js`) stejným pravidlem
  (bez data = poslední den měsíce). Text v okně má nezlomitelné mezery (`typo()` v JS) – testy porovnávat
  po nahrazení nezlomitelné mezery (U+00A0) obyčejnou mezerou.
- Část souborů (od agentů) má konce řádků CRLF: skript, který nahrazuje víceřádkové kusy, musí CRLF zachovat
  (`str_contains($s, "\r\n")` → převést hledaný i nový text). Nástroj Edit to dělá sám.
- `typo_text()` dělá i „I. ČLTK“ (nbsp po římské číslici), „ - “ → „ – “, „...“ → „…“, „5x “ → „5× “;
  `radky_seznam()` už typografii má. Data proto neopravovat ručně, jen když je to věcná chyba („VITĚZKA [sic]“).
- Návštěvnost ukládá název stránky (`/`, `/klub.php`), ne adresu; iPhone profil Playwrightu se počítá →
  před `e2e-uvod.mjs` (vkládá vlastní řádky návštěvnosti) smazat dnešní záznamy v testovací DB
  (`podklady/_raw/qa/fixer/uklid-navstev.php` má napevno `fixer.sqlite` – pro jinou kopii
  `CLTK_DB_FILE=web/data/x.sqlite ./_php/php.exe -r 'require "web/inc/functions.php"; q("DELETE FROM cltk_visits WHERE id > 0");'`).
- Rodné číslo z přihlášky se ukládá jen zašifrované (`citlive_zasifruj()`, klíč `SIFROVACI_KLIC` v `cltk-config.php`,
  lokálně `web/data/sifrovaci-klic.txt`). Bez klíče se pole ve formuláři neukáže.
- Bloky úvodní stránky v modulu Stránky: pole a popisky podle `STRANKY_UVOD_POLE` (admin/stranky.php), bez šipek
  a bez „Skrýt“; nový blok úvodu = přidat i sem a do `STRANKY_UVOD_MAPA`.
- **Dokumenty:** žádné PDF dokumentů klubu (ani „Stáhnout PDF“) – jen text a tisk stránky. Sazbu (číslované body,
  „Článek I.“, závěrečný rámeček, tabulky ceníku s `data-label` pro mobil) dělá jen výpis `dokument_sazba()` /
  `dokument_tabulky()` v `dokument.php`; v DB zůstává holé HTML ze sanitizéru (tabulky bez atributů).
- **Migrace na serveru:** nový sloupec = migrace podle `sql/migrace/2026-10-02-dokumenty-archiv.php` (ADD COLUMN jen
  když chybí – PRAGMA / information_schema, přepis jen hodnot rovných původním, odmítne běh bez nahraných souborů,
  token v prohlížeči – v souboru je jen jeho SHA-256, repo je veřejné). `sql/` je zvenku zavřené → na běh se
  soubor + `.data.json` kopírují do kořene webu a pak se smažou. Nový stav vždy promítnout i do seedu.
- Snímky dokumentů: `podklady/_raw/qa/dokumenty/render.mjs <base> "dokument.php?d=stanovy" d,m,t,pdf` (desktop,
  mobil, tisk, `page.pdf` A4) a `kousky.py` (rozřeže snímek / PDF na PNG k prohlédnutí); WebKit neumí snímek
  delší než 32 767 px (stanovy).

## Jak ověřit změnu
```bash
nastroje/kopie-db.sh moje && nastroje/spustit.sh 8810 web/data/moje.sqlite /cltkv2
node podklady/_raw/qa/integrace/prochazka.mjs http://127.0.0.1:8810/cltkv2/          # odkazy, kotvy, soubory, hlavička/patička
node podklady/_raw/qa/integrace/snimky.mjs http://127.0.0.1:8810/cltkv2/ vse c1440,iphone,wk-iphone,wk1440
CLTK_DB_FILE=web/data/moje.sqlite ./_php/php.exe podklady/_raw/qa/fixer/test-jadro.php   # (kopie s tokenem vázaným na adresu)
./_php/php.exe nastroje/test-pojistka.php                                                # pojistka sdílené DB
bash podklady/_raw/qa/fixer/bezpecnost.sh                                                # hlavičky, relace, brzda (port 8811, fixer.sqlite)
cat web/data/chyby.log                                                                # musí být prázdný
nastroje/zastavit.sh 8810
```
Snímky si opravdu prohlédnout (Read) a porovnat s Variantou 4 a PDF klienta. Bez přetečení na 390 i 320 px,
s omezeným pohybem (`RM=1`) musí být vidět všechen obsah. Po změně výchozího obsahu:
`nastroje/novy-seed.sh` a `./_php/php.exe web/sql/export-dat.php --sql` (data.json jde na server s webem).
Ověřit i stávající funkce, ne jen novou (admin e2e: `podklady/_raw/qa/integrace/e2e-uvod.mjs` s `UV_BASE=…`, `e2e-obsah.mjs` s `BASE=…` –
kopie jsou napevno nad `web/data/integrace.sqlite`, originály agentů v `admin-uvod/`, `admin-obsah/`).

## Nasazení na test – stav 2. 10. 2026

- Běží na **https://www.tkolymppraha.cz/cltkv2/** (hosting TK Olymp, Forpsi), **režim přípravy zapnutý**
  (návštěvník vidí přípravnou stránku 503 + noindex, přihlášený správce celý web).
- FTP účet `www.tkolymppraha.cz` (kořen = složka domény: `config.php` Olympu – NESAHAT, `cltk-config.php`
  ČLTK, `www/`). Web je v `/www/cltkv2/`. Přístupy v `deploy/` (mimo git).
- Databáze MySQL `f201572` je **sdílená s webem TK Olymp** – ČLTK má jen tabulky `cltk_` (29 tabulek).
- Nahrávání: `python nastroje/nahrat.py` (zkušebně) / `--ostra` / `--ostra --uploads` /
  `--ostra --soubory index.php inc/data.php`. Skript zálohuje přepisované soubory do `deploy/zalohy/`.
- Proxy Forpsi (aruba-proxy) **drží chvíli staré odpovědi** – po nasazení ověřovat s hlavičkou
  `Cache-Control: no-cache` a `?t=náhodné`. HTTPS vynucuje proxy sama, vlastní přesměrování
  v `web/.htaccess` je proto na testu vypnuté (jinak hrozí smyčka).
- `instalace.php` po instalaci ze serveru smazána, `INSTALL_KEY` z `cltk-config.php` odebrán.
  Účet správce: petr.luxa@gmail.com (heslo v `deploy/ucet-admin.txt`).
- Ověření po nasazení: `node podklady/_raw/qa/server/prochazka.mjs` (přihlásí se a projde 45 stránek)
  a stažení `/www/cltkv2/data/chyby.log` přes FTP – musí být prázdný.
- GitHub: kód se přenáší do `github.com/petrluxa/CLTK` jako složka `novy-web/`
  (`cd C:/Users/Asus/cltk-navrhy && git subtree pull --prefix=novy-web C:/Users/Asus/Dokumenty/Code/cltk-web main`
  a `git push`). Repo je veřejné – před pushem kontrola hesel.
