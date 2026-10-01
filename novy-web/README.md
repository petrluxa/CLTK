# Web I. ČLTK Praha (cltk.cz) – nový web s administrací

Nový web klubu **I. Český Lawn-Tennis Klub Praha** (ostrov Štvanice, založen 1893) včetně
administrace, ve které klub spravuje veškerý obsah sám. Vzhled vychází z návrhu 1 „Zlatá deska“,
Varianty 4 (`C:\Users\Asus\cltk-navrhy\04-varianta-4\`), úvodní stránka z PDF klienta
(`podklady/klient-pdf/stranka-00…06.jpg`). Závazné zadání je v **`ZADANI.md`**.

- **Technika:** PHP 8 bez frameworku a bez composeru, PDO. Lokálně SQLite, na serveru MySQL/MariaDB
  (Forpsi, PHP 8.5). Každý dotaz musí běžet v obou.
- **Kde poběží:** nejdřív na testu na hostingu TK Olymp ve složce `/cltkv2/`, po schválení na cltk.cz.
  Web nemá nikde natvrdo doménu ani cestu – vše jde přes `url()` / `asset()` / `upload_url()` a `BASE_PATH`
  (zjistí se sám).
- **Stav k 1. 10. 2026:** web, administrace (20 modulů), výchozí obsah, instalace i přenos dat jsou hotové
  a ověřené (viz „Ověření“ níže). Chybí jen obsah, který dodá klub (seznam na konci).

> **POZOR – sdílená databáze.** Na serveru je databáze `f201572` **společná s ostrým webem TK Olymp**.
> Všechny tabulky ČLTK mají předponu **`cltk_`** a kód nesmí sáhnout na žádnou jinou tabulku
> (Olymp má `uzivatele`, `clanky`, `polozky`, `obsah`, `navstevy`, `prihlaseni_pokusy`, `clanky_foto`).
> Hlídá to pojistka v `web/inc/db.php` (`sql_pojistka()`, `cltk_tabulka()`), na MySQL navíc nikdy
> neprovede `DROP`. Do databáze se chodí jen přes `q()`, `row()`, `rows()`, `val()`, `db_insert()`,
> `db_update()` – nikdy přes `db()->query()`.

---

## 1. Spuštění lokálně (Windows, Git Bash, z kořene projektu)

```bash
nastroje/novy-seed.sh                                  # čerstvá web/data/cltk.sqlite s výchozím obsahem (~10 s, s prázdnými uploads ~1 min)
nastroje/kopie-db.sh moje                              # vlastní kopie → web/data/moje.sqlite (hlavní DB nepřepisovat testy)
nastroje/spustit.sh 8810 web/data/moje.sqlite          # dva servery PHP: :8810 a :8910 (vestavěný server je jednovláknový)
nastroje/spustit.sh 8810 web/data/moje.sqlite /cltkv2  # totéž v podsložce jako na testu → http://127.0.0.1:8810/cltkv2/
nastroje/zastavit.sh 8810                              # zastaví oba servery (jen php.exe na tom portu)
```

- Administrace: `/admin/` – **admin@cltk.local / cltk-test-2026** (zakládá jen lokální seed, na serveru nikdy).
- Lokálně je režim přípravy vypnutý; na serveru ho zapne instalace.
- PHP je přenosné v `_php/php.exe` (8.3). Směrovač `nastroje/router.php` napodobuje `.htaccess`
  (403 na `inc/`, `data/`, `sql/`, citlivé přípony a skripty v `uploads/`, neexistující adresa → `404.php`).
- Když port na 127.0.0.1 drží cizí proces (např. lokální náhled LTK Liberec na 8801/8802):
  `CLTK_HOST=127.0.0.2 nastroje/spustit.sh …` (a stejně `zastavit.sh`).
- Jiný „dnešek“ pro kalendář a informační lištu: `CLTK_DNES=2026-12-20 nastroje/spustit.sh …` (jen SQLite).
- Varování a chyby PHP se nepíšou do stránky, ale do **`web/data/chyby.log`** – po práci má být prázdný.

## 2. Struktura

```
ZADANI.md                 závazné zadání (česky) – při změně zadání upravit tady
README.md, CLAUDE.md      tento přehled / návod pro další sezení s AI
web/                      celý web = to, co se nahrává na server
  index.php …             veřejné stránky (seznam níže), 404.php, akce.php?id=…
  instalace.php           jednorázová instalace na serveru (po instalaci smazat)
  inc/                    jádro: config.php, db.php (pojistka cltk_), functions.php, html.php (sanitizér),
                          soubory.php (obrázky přes GD, PDF, WebP, loga partnerů), data.php (data pro stránky),
                          rezim.php (režim přípravy + náhledové heslo), mail.php, sablona/ (hlavička, patička, komponenty)
  admin/                  administrace (moduly, inc/layout.php + ui.php + auth.php, assets/)
  assets/                 css/ (styl.css = designový systém Varianty 4, index.css, v2.css, stranky-*.css),
                          js/ (app.js + skripty sekcí), img/ (LOGO – jediné místo, viz níže)
  sql/                    schema.sql, SCHEMA.md, seed.php + seed/NN-*.php, seed-pomocne.php,
                          export-dat.php, data.json (+ data.sql) – výchozí obsah pro instalaci
  uploads/                obrázky, PDF a video obsahu (mimo git kromě .htaccess; na server zvlášť)
  data/                   lokální SQLite a chyby.log (mimo git kromě .htaccess; na server NIKDY)
nastroje/                 spustit.sh, zastavit.sh, novy-seed.sh, kopie-db.sh, router.php (jen lokálně)
podklady/                 podklady klienta (PDF, loga, video); _raw/ = pracovní výstupy testů (mimo git)
deploy/                   přístupy a nahrávání na server – mimo git, smí jen orchestrátor
```

Podrobné návody: `web/inc/PRUVODCE-FRONT.md` (jádro pro stránky), `web/PRUVODCE-STRANKY.md`
(jak postavit podstránku, třídy designu), `web/admin/PRUVODCE.md` (jak napsat modul administrace),
`web/sql/SCHEMA.md` (tabulky a sloupce – závazné).

## 3. Stránky webu

| Menu | Stránky |
|---|---|
| Klub | `klub.php` · Členství `clenstvi.php` (konfigurátor ceny + přihláška do klubu) · Historie `historie.php` (triptych, kronika, Zlatá deska, medailony) · Vedení `vedeni.php` · CTC `ctc.php` · Revue `revue.php` (kiosek 41 čísel, newslettery) |
| Areál a služby | `areal.php` (služby s kotvami, plán areálu léto/zima, uzávěrky, příjezd) · Ceník kurtů `cenik-kurtu.php` (léto/zima, kalkulačka) · Privátní trenéři · Body Solution · Sportovní lékařství |
| Závodní tenis | `zavodni-tenis.php` (pyramida, hráči, extraliga, výsledky) · Trenérský tým `zavodni-tenis-treneri.php` |
| Tenisová škola | `tenisova-skola.php` · Ceníky · Rozvrhy · Trenérský tým · Letní kempy `letni-kempy.php` |
| Restaurace | nastavení `restaurace_url` (vlastní web), prázdné → `restaurace.php` |
| Prague Open | nastavení `prague_open_url`, prázdné → `prague-open.php` |

Mimo menu: `kontakt.php` (z patičky), `akce.php?id=…` (detail akce s přihláškou), `404.php`,
přípravná stránka (režim přípravy). Úvodní stránka `index.php`: informační lišta, galerie se štítovým
přechodem, aktuality (ne články), pás výsledků, klubový kalendář s přihláškou v okně, video
a Služby v areálu, triptych historie, partneři 5 × 5, patička podle PDF.

**Logo** (ke 130 letům) je jen ve `web/assets/img/` (`logo.svg`, `logo-64/128/256/512.png`, `logo.png`)
a odkazuje se jen přes `logo_url()` ve `web/inc/functions.php` → výměna loga = přepsat tyto soubory.

## 4. Administrace (`/admin/`)

Moduly v pořadí postranního panelu: Přehled · Návštěvnost · Informační lišta · Úvodní galerie · Aktuality
z klubu · Výsledky hráčů · Kalendář akcí (u každé akce vlastní formulář přihlášky, seznam přihlášených, CSV) ·
Přihlášky (k akcím + do klubu, stav, poznámka, CSV) · Trenéři · Ceníky · Tenisová škola · Areál a služby ·
Historie · Revue a newslettery · Vedení a CTC · Stránky (textové bloky všech podstránek) · Partneři ·
Dokumenty · Texty a údaje (kontakty, odkazy, sítě, video, e-mail pro přihlášky, **režim přípravy
a náhledové heslo**) · Účet.

Zásady: vypínat místo mazat, po každém uložení přesměrování s hláškou, CSRF na každém POST,
tlačítka šipek/přepnout/smazat ve vlastních formulářích, pole ≥ 16 px, cíle ≥ 44 px, funguje na mobilu.
Brzda hádání hesla 5 pokusů / 15 min / IP (`cltk_login_attempts`). Relace `cltk_admin` s cookie jen
pro cestu webu (nekoliduje s `olymp_admin`).

## 5. Data, výchozí obsah a soubory

- **Schéma:** `web/sql/schema.sql` (jen `CREATE TABLE`, 29 tabulek `cltk_`), popis `web/sql/SCHEMA.md`.
  `db_install()` z něj zakládá jen **chybějící** tabulky (`CREATE TABLE IF NOT EXISTS`) – nic nemaže ani
  nemění. Nový sloupec na běžícím serveru = ruční migrace (instalace sloupce nedoplní).
- **Seed:** `web/sql/seed.php` (jen CLI, jen SQLite) spustí sady `web/sql/seed/NN-*.php` podle abecedy.
  Každá sada plní jen prázdnou tabulku / chybějící klíč, takže jde pustit opakovaně a nic nepřepíše.
  Obrázky bere ze zdrojů (`C:\Users\Asus\cltk-navrhy\assets`, `podklady/klient-pdf`, `podklady/klient-loga`,
  `podklady/video`), přes GD je znovu uloží do `web/uploads/…` (+ WebP). Loga partnerů a video kopíruje beze změny.
- **Přenos obsahu na server:** `./_php/php.exe web/sql/export-dat.php [--sql]` → `web/sql/data.json`
  (+ `data.sql` pro ruční import). Nepřenáší účty, přihlášky, návštěvnost, tajný klíč, náhledové heslo
  ani režim přípravy. **Po každé změně výchozího obsahu znovu vyexportovat** – `instalace.php` plní
  databázi z `data.json` (zdroje seedu na serveru nejsou). Lokálně stejnou cestu vyzkouší
  `nastroje/novy-seed.sh web/data/x.sqlite --z-dat`.
- **Soubory:** v databázi jsou jen relativní cesty uvnitř `uploads/` (`galerie/vondrousova-wimbledon-2023.jpg`).
  Text z editoru se ukládá přes `html_k_ulozeni()` – odkazy bez `/cltkv2/`, takže přežijí přestěhování.
  `uploads/` je mimo git a na server se nahrává zvlášť; `uploads/.htaccess` zakazuje spouštění skriptů
  (instalace ho založí, když chybí).
- **Návštěvnost** bez cookies (denní nevratný otisk, roboti a headless prohlížeče se nepočítají) → web
  nepotřebuje cookie lištu. Ukládá se jen **název stránky** (ne adresa z požadavku), nejvýš 20 000 otisků
  za den, záznamy starší 13 měsíců se mažou (tabulky leží v databázi sdílené s TK Olymp).
  Veřejné formuláře (přihláška k akci, do klubu) mají podepsaný token bez relace (platí 2 hodiny a jen
  z adresy, kde vznikl; IPv6 po síti /64), past na roboty a brzdu 8 odeslání / hodinu / IP –
  **na veřejném webu nevzniká žádná cookie** (jen náhledové heslo a přihlášení správce).
- **Osobní údaje v přihláškách:** rodné číslo se ukládá **zašifrované** klíčem `SIFROVACI_KLIC`
  z `cltk-config.php` (mimo `www/`); bez klíče formulář rodné číslo vůbec nenabídne. Lokálně si klíč web
  založí sám v `web/data/sifrovaci-klic.txt`. Vyřízené / zamítnuté přihlášky do klubu a přihlášky k akcím
  starší než „Mazat staré přihlášky po (měsících)“ (Texty a údaje, výchozí 12) se samy smažou.
- **E-mail:** upozornění na přihlášky se posílá, jen když je v Textech a údajích vyplněný „E-mail pro
  přihlášky“; výchozí prázdný = přihlášky se jen ukládají do administrace.

## 6. Nasazení (dělá jen orchestrátor – agenti ne)

1. **Nikdy nenahrávat:** `web/inc/config.local.php` (lokální config si agenti přepisují na SQLite – na serveru
   by to shodilo web), `web/data/`, `*.sqlite`, `podklady/`, `nastroje/`, `deploy/`. `uploads/` jen
   samostatně (přepínač `--uploads`). Před přepsáním existujícího souboru na serveru stáhnout zálohu
   (i `.htaccess`). Cílová složka natvrdo `/www/cltkv2/`, jinam nic.
2. **Přístupy** jsou v `cltk-config.php` v kořeni FTP účtu, nad `www/` (`dirname(__DIR__, 3)` od `web/inc/`),
   vedle `config.php` Olympu, který se **neotevírá**. Vzor je v hlavičce `web/inc/config.php`
   (`DB_DRIVER mysql`, `DB_HOST`, `DB_NAME f201572`, `DB_USER`, `DB_PASS`, na dobu instalace
   `INSTALL_KEY` o délce ≥ 16 znaků). **Nově povinné:** `SITE_URL` (odkazy v e-mailech se neberou z hlavičky
   Host; bez něj e-mail odejde bez odkazu a do `chyby.log` se zapíše varování) a `SIFROVACI_KLIC`
   (64 náhodných znaků, např. `bin2hex(random_bytes(32))`; **nikdy ho neměnit** – stará rodná čísla by nešla
   přečíst; bez něj se rodné číslo v přihlášce nesbírá).
3. Nahrát `web/` včetně `web/sql/data.json`, pak `uploads/`.
4. Otevřít `https://…/cltkv2/instalace.php` a **instalační klíč zadat do formuláře** (posílá se jen POSTem –
   v adrese by zůstal v protokolech serveru a historii prohlížeče; `?klic=` se už nepřijímá; hádání brzdí
   5 pokusů / 15 min). Instalace založí chybějící tabulky `cltk_`, naplní je z `data.json`, doplní výchozí sady,
   zapne režim přípravy, vygeneruje tajný klíč a z formuláře založí **první účet** správce. Když účet existuje,
   odmítne (403).
5. Po instalaci `instalace.php` **přebít souborem** `<?php http_response_code(404); exit;`, ověřit, že vrací 404
   (cache PHP na Forpsi), a teprve pak smazat; z `cltk-config.php` odebrat `INSTALL_KEY`.
6. Ověřit, že web vrací 200 (s režimem přípravy 503 + náhled) **a že ostrý web TK Olymp
   (https://www.tkolymppraha.cz/) běží dál**. `web/.htaccess` přesměrovává **celý web na HTTPS** (302):
   `curl -I http://…/cltkv2/admin/login.php` musí vrátit 302 na `https://…` (a `https://` 200, ne smyčku).
   Hlavička `Content-Security-Policy` přichází z PHP (`curl -I https://…/cltkv2/`).
7. V administraci nastavit náhledové heslo pro lidi z klubu. **Režim přípravy nechat zapnutý po celou dobu
   testu** (přihlášky se v něm nepřijímají – POST vrátí 503), vypnout až po schválení na cltk.cz.

**Přestěhování na cltk.cz:** `BASE_PATH` se zjistí sám (případně ho nastavit v `cltk-config.php`), upravit
`SITE_URL`, v `web/.htaccess` změnit přesměrování na HTTPS z `R=302` na `R=301` a odkomentovat HSTS,
dokument „Osobní údaje členů“ dnes vede na `https://cltk.cz/cs/gdpr/` dnešního webu – nahradit PDF
(v adminu nahrát soubor). **Než web půjde ven (s přihláškami do klubu), dát ČLTK vlastní databázi
a vlastního databázového uživatele** – dokud je databáze sdílená s TK Olymp, kompromitace jednoho webu
odhalí osobní údaje druhého (přihlášky dětí, adresy, data narození). Testovat po co nejkratší dobu,
ideálně na vlastní subdoméně (na sdíleném původu www.tkolymppraha.cz by skript jednoho webu mohl jednat
za přihlášeného správce druhého).

## 7. Bezpečnost (převzato z LTK Liberec a nezhoršovat)

`e()` na každý výstup · PDO s parametry · CSRF token na každém POST v administraci · `password_hash` ·
sanitizér HTML z editoru sestaví text **z holých značek** (`strip_tags` nechává atributy – past z Olympu) ·
odkazy z administrace jen přes `bezpecny_odkaz()` · nahrané obrázky znovu uložené přes GD, PDF musí
**začínat** `%PDF-` a nesmí v prvním kB obsahovat HTML (+ MIME) · starý soubor se maže až po úspěšném zápisu
do DB · `uploads/` bez spouštění skriptů a s CSP `sandbox` (PDF má výjimku kvůli prohlížeči PDF) · `inc/`, `data/`,
`sql/`, `admin/inc/` zvenku zavřené, sady seedu se přímo nespustí · v režimu přípravy `noindex` (meta +
`X-Robots-Tag`) · žádný výstup před `header()`.

Doplněno po revizi 1. 10. 2026:
- **Pojistka sdílené databáze** (`sql_pojistka()`) rozloží SQL na slova (pravidly MySQL i SQLite) a ověří každou
  tabulku: seznamy za FROM/JOIN/čárkou i po ON, závorky, cíle UPDATE/DELETE/INSERT (i bez INTO), INTO,
  REFERENCES, CREATE/ALTER/DROP/TRUNCATE/RENAME. Povolené příkazy jen SELECT, INSERT, UPDATE, DELETE, REPLACE,
  CREATE/ALTER/DROP TABLE/INDEX, TRUNCATE, RENAME a SET NAMES; HANDLER, DESCRIBE, SHOW, LOAD, CALL, EXPLAIN,
  WITH, víc příkazů, `/*! … */` a LOAD_FILE se odmítnou. Regresní test: `./_php/php.exe nastroje/test-pojistka.php`
  (prověří i všechny doslovné dotazy z kódu webu).
- **HTTPS** pro celý web (`web/.htaccess`), `AcceptPathInfo Off`, bez `X-Powered-By`, **Content-Security-Policy**
  (veřejný web: skripty jen ze souborů + otisk jediného vloženého skriptu `SABLONA_SKRIPT_HLAVICKY`;
  administrace a instalace bez vložených skriptů).
- **Relace** ve vlastní složce `data/relace/`, přísný režim (podstrčené ID se nepřijme), klíče pod
  `$_SESSION['cltk']` (`relace()`), otisk hesla v relaci – změna hesla odhlásí všechna ostatní přihlášení.
- **Brzda přihlášení**: pokus se zapíše dřív, než se heslo ověří (souběžné pokusy brzdu neobejdou); neexistující
  e-mail se ověřuje stejně dlouho jako existující. Stejně brzdí náhledové heslo a instalační klíč.
- Parametry v adrese jako pole (`?zpet[]=x`) nezapisují varování; `chyby.log` se nad 5 MB přetočí
  na `chyby-stare.log`.

## 8. Ověření (jak se testovalo, skripty v `podklady/_raw/qa/`)

- `integrace/prochazka.mjs <base>` – projde celý veřejný web po odkazech (menu, podmenu, patička, pilulky,
  tlačítka): stavové kódy, kotvy, všechny odkazované soubory, shodná hlavička a patička, jeden `<h1>`.
- `integrace/snimky.mjs <base> [stránky] [profily]` – snímky všech stránek (Chromium 1440, iPhone 13,
  WebKit iPhone a 1440, 390/320 px), konzole, HTTP ≥ 400, přetékání; `RM=1` = omezený pohyb.
- `integrace/rezim.mjs`, `integrace/kalendar.mjs` – režim přípravy + náhledové heslo; kalendář
  a přihláška z okna až do administrace a CSV.
- `admin-uvod/e2e.mjs`, `admin-obsah/e2e.mjs` (kopie pro integraci `integrace/e2e-*.mjs`) – všechny
  moduly administrace: přidání, úprava, přepnutí, pořadí, mazání, nahrávání, zlomyslné vstupy, CSRF.
- `core/test-jadro.php` – jádro (pojistka `cltk_`, sanitizér, typografie, data, kalendář, tokeny);
  `nastroje/test-pojistka.php` – regresní test pojistky (v gitu).
- `fixer/` – opravy po revizi: `test-opravy.php` (typografie, token vázaný na adresu, IPv6 /64, šifrování
  rodného čísla, kontrola PDF), `bezpecnost.sh` (hlavičky, zavřené složky, návštěvnost, relace, brzda,
  instalace), `xss-prochazka.mjs` nad kopií DB s payloady, kopie integračních testů nad `fixer.sqlite`
  (`test-jadro.php` tam má token už vázaný na adresu).
- Playwright: `import { chromium, webkit, devices } from 'playwright'` (z `C:/Users/Asus/node_modules`).

Výsledek integrace 1. 10. 2026 (web v `/cltkv2/`): 35 stránek, 310 souborů, 0 vadných odkazů / kotev,
0 chyb konzole a HTTP; admin e2e 192 + 26 + 145 (Chromium) + 41 (WebKit iPhone) kontrol v pořádku;
jádro 75/75; seed od nuly i instalace z `data.json` dávají stejný obsah (29 tabulek, 633 řádků obsahu);
`chyby.log` prázdný.

## 9. Co ještě dodá klub (na webu je decentní štítek „doplní klub“)

- Obsah stránek **Body Solution, Sportovní lékařství, Privátní trenéři** (texty, ceny, kontakty),
  otevírací doba a kontakt **Restaurace Tiebreak**, zimní rozvrhy skupin Tenisové školy.
- Otevírací doby u většiny služeb areálu; fotky u služeb bez fotky (Fyzioterapie, Parkoviště, hřiště u trojkurtu).
- Termíny akcí CTC U14, Mikulášská besídka, Večer talentů a Vánoční večírek, OSTRA extraliga;
  **ověřit název belgického klubu** u CTC U14 (poznámka u akce v administraci).
- Seznamy Zlaté desky: čestní členové, zasloužilí členové, prezidenti (doplnění).
- Fotka z finále Billie Jean King Cupu 2026 (snímek galerie je zatím navy deska).
- Formuláře přihlášek k jednotlivým akcím (v administraci se nastaví u každé akce zvlášť).
- PDF „Osobní údaje členů“ (dnes odkaz na stránku dnešního webu), případně další dokumenty
  (dnes odkazy na files.cltk.cz).
- Vlastní weby Restaurace a Prague Open → vyplnit `restaurace_url` / `prague_open_url` v Textech a údajích.
- E-mail pro upozornění na přihlášky (Texty a údaje).
