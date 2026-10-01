# Nový web I. ČLTK Praha – zadání stavby

Platí od 1. 10. 2026. Tenhle soubor je **závazné zadání** pro všechny, kdo na webu pracují.
Když se něco v zadání změní, upraví se tady.

## 0. ZMĚNY 1. 10. 2026 dopoledne – mají přednost před zbytkem textu

1. **Logo:** klient dodal **logo ke 130 letům** (vektor). Používá se VŠUDE místo 3D znaku:
   `podklady/klient-loga/logo-cltk-130-let.svg` + PNG `-64/-128/-256/-512/-1200.png` (průhledné pozadí).
   Do webu jako `web/assets/img/logo.svg` + `logo-256.png` / `logo-512.png` / favicon z `-64.png`;
   jedno místo v kódu. Slepotisk v patičce = jednobarevná varianta téhož tvaru (CSS maska / filtr),
   nebo dosavadní `cltk-znak-mono.svg`, pokud tvar sedí. 3D znak už nepoužívat.
2. **Loga partnerů:** klient dodal hotová jednobarevná loga (béžová, průhledná) –
   `podklady/klient-loga/partneri/*.png` (25 souborů, pořadí řádků jako v §4.9: vivus, jt-banka,
   pankrac, aston, green-gas · noncore, hodinarstvi-bechyne, sport-construction, iso, smartwings ·
   cpp, crane-constancy-capital, tukas, rpm-facility, advantage-cars · babolat, mizuno, peach,
   glenfiddich, messy-play · praha, praha-7, victoria, narodni-sportovni-agentura, cesky-tenis).
   Použít **tak, jak jsou** (nepřebarvovat). Automatické přebarvení zůstává jen pro logo nově
   nahrané v administraci.
3. **Přihlášky k akcím budou u různých akcí různé** (klient formuláře teprve dodá). Formulář
   přihlášky proto musí jít **nastavit u každé akce zvlášť**: v administraci akce zaškrtávátko
   „Zobrazit tlačítko Přihlásit se“ + výběr polí formuláře (jméno a e-mail vždy; volitelně telefon,
   počet osob, poznámka) + **vlastní pole** (popisek, typ text / číslo / výběr z možností /
   zaškrtávátko, povinné ano/ne) uložená u akce jako JSON; odpovědi v `cltk_signups` jako JSON.
   V administraci seznam přihlášených u akce se sloupci podle polí + export CSV.
4. **Kalendář na úvodu:** výchozí detail vpravo = **nejbližší nadcházející akce podle dnešního
   data** (automaticky). **Ťuknutí / kliknutí na akci v seznamu zobrazí vpravo její detail
   i s přihláškou** (formulář přímo v okně nebo tlačítko, které ho otevře), bez přenačtení stránky.
   Na mobilu se detail zobrazí pod seznamem a stránka k němu sjede.

## 1. Co stavíme

Kompletní nový web klubu **I. Český Lawn-Tennis Klub Praha** (cltk.cz) s administrací.
Dočasně poběží na testu na hostingu TK Olymp ve složce `/cltkv2/`, po dokončení se přestěhuje
na cltk.cz. Web proto nesmí mít nikde natvrdo doménu ani cestu `/cltkv2/`.

- **Vzhled:** přesně jazyk návrhu 1 „Zlatá deska“, resp. jeho rozpracované Varianty 4
  (`C:\Users\Asus\cltk-navrhy\04-varianta-4\` – index.html, css/styl.css, css/index.css, js/app.js,
  js/galerie.js, js/kiosek.js, js/data-revue.js). Paleta: papír `#fbfaf6` / `#f3f0e9`, inkoust `#1a2233`,
  navy `#152744` / `#0e1b30`, zlatá `#b3935a` (linky), `#caac79` (na navy), `#8a6d3b` (zlatý text na papíře),
  šedá `#6b6f7e`, linky `#e4e0d6`. Písma Cormorant Garamond + Cormorant SC + Jost (Google Fonts, latin-ext).
  **Nic nového nevymýšlet – převzít komponenty a třídy z Varianty 4.**
- **Úvodní stránka:** podle klientova PDF `podklady/klient-pdf/stranka-00.jpg … stranka-06.jpg`
  (vykreslené PDF `C:\Users\Asus\Downloads\CLTK web_V2.pdf`) – viz §4. Fotky z PDF jsou vytažené
  v `podklady/klient-pdf/*.jpg`.
- **Administrace:** stejná logika a moduly jako u LTK Liberec
  (`C:\Users\Asus\Dokumenty\Code\ltkliberec-web\web\admin\` – **jen ke čtení, je to ostrý web jiného klubu,
  nic tam neměnit**), ale v designu ČLTK (viz §6).

## 2. Technika (převzato z LTK Liberec)

- PHP 8 bez frameworku a composeru, PDO. **Lokálně SQLite, na serveru MySQL** – každý dotaz musí běžet v obou.
  Na serveru je PHP 8.5 (Forpsi), lokálně přenosné PHP 8.3: `./_php/php.exe` (z kořene projektu).
- Lokální běh: `./_php/php.exe -S 127.0.0.1:<port> -t web`. Server je jednovláknový – pro testy
  s prohlížečem spouštěj **dva servery** (port a port+100) jako v Liberci. Každý agent má přidělený
  vlastní port a **vlastní kopii databáze** přes proměnnou prostředí `CLTK_DB_FILE`
  (např. `CLTK_DB_FILE=web/data/agent-areal.sqlite`), ať se agenti nepřepisují.
- **SDÍLENÁ DATABÁZE S TK OLYMP (ostrý web jiného klubu!).** Na serveru je databáze `f201572` společná
  s webem TK Olymp. Proto:
  - **všechny tabulky ČLTK mají předponu `cltk_`** (`cltk_users`, `cltk_settings`, …);
  - žádný kód nesmí sáhnout na tabulku bez předpony `cltk_` (Olymp má `uzivatele`, `clanky`, `polozky`,
    `obsah`, `navstevy`, `prihlaseni_pokusy`, `clanky_foto`);
  - instalace a migrace musí mít pojistku: každý `CREATE/ALTER/DROP/TRUNCATE/DELETE/INSERT/UPDATE` ve
    skriptu jde jen na tabulku začínající `cltk_` (funkce `cltk_tabulka()` / kontrola v `q()` u DDL);
  - nikdy `DROP TABLE` na serveru.
- **Konfigurace:** `web/inc/config.php` (v gitu, výchozí hodnoty) načte přístupy z prvního existujícího:
  1. `dirname(__DIR__, 3) . '/cltk-config.php'` – na serveru leží v kořeni FTP účtu, nad `www/`
     (vedle `config.php` Olympu – **ten neotvírat**);
  2. `web/inc/config.local.php` – lokálně, mimo git.
  Výchozí ovladač SQLite (`web/data/cltk.sqlite` nebo `getenv('CLTK_DB_FILE')`).
  **Lokální config se nikdy nenahrává na server** (agenti si ho přepisují na SQLite → shodilo by to web).
- **Cesty:** web běží v podsložce. Všechny odkazy přes `url('stranka.php')` a soubory přes `asset('…')`,
  které berou základ z `BASE_PATH` (zjištěný z `SCRIPT_NAME` vůči kořeni webu, přepsatelný v configu).
  Žádné absolutní `/…` cesty v HTML, CSS ani JS (v CSS jen relativní `url(../img/…)`).
- **Session:** `session_name('cltk_admin')`, cookie `path` = `BASE_PATH`, httponly, secure při HTTPS,
  SameSite=Lax. (Olymp používá `olymp_admin` s path `/` – nesmí se to tlouct.)
- **Režim přípravy** (z Liberce `inc/rezim.php`): po instalaci zapnutý. Návštěvník vidí přípravnou stránku,
  přihlášený správce celý web s pruhem nahoře. Navíc **náhledové heslo** (nastavení v administraci,
  uložené jako hash): kdo ho zadá na přípravné stránce, dostane cookie a vidí web (pro lidi z klubu,
  co testují bez účtu). V režimu přípravy všude `noindex` (meta + hlavička `X-Robots-Tag`).
- **Bezpečnost** (převzít z Liberce a nezhoršit): PDO s parametry, `e()` na každý výstup, CSRF token na
  každém POST, `password_hash`, brzda proti hádání hesla (`cltk_login_attempts`), sanitizace HTML z editoru
  tak, že se **každá povolená značka přepíše na holou** (`strip_tags` nechává atributy – past z Olympu),
  nahrávané obrázky přes GD znovu uložit (zahodí se cokoli skrytého), PDF kontrolovat MIME,
  `uploads/` bez spouštění PHP (`.htaccess`), `inc/`, `data/`, `sql/` zvenčí zavřené.
- **Návštěvnost bez cookies** jako v Liberci/Olympu (denní otisk, roboti se nepočítají) → web nepotřebuje
  cookie lištu. Tuhle vlastnost neshodit.
- **Formuláře:** Enter nesmí odeslat jiné tlačítko než Uložit/Odeslat (šipky a mazání ve vlastních
  formulářích), pole v adminu aspoň 16 px, cíle 44 px, datum normalizovat na serveru (`normalizuj_datum`),
  starý soubor mazat až po úspěšném zápisu do DB, žádný výstup před `header()`.
- Texty pro klub a komentáře v kódu **česky**, správná diakritika, „…“, nezlomitelná mezera po
  jednopísmenných předložkách a mezi číslem a jednotkou.

## 3. Menu a stránky

Hlavička jako v PDF: zlato-navy stuha, vlevo 4 položky, uprostřed znak, vpravo 2 položky a tlačítko
**Rezervovat kurt** (`https://www.rogeronline.cz/v2/index.php?klub=181`, nové okno). Každá položka má
rozbalovací podmenu (na desktopu na najetí i na kliknutí / klávesnici přes `<button aria-expanded>`,
na mobilu burger s rozbalovacími skupinami). Na mobilu lepivá lišta „Rezervovat kurt“ + „Zavolat recepci“.

| Menu | Stránka | Podmenu |
|---|---|---|
| **Klub** | `klub.php` | Členství `clenstvi.php` · Historie `historie.php` · Vedení `vedeni.php` · CTC `ctc.php` · Revue `revue.php` |
| **Areál a služby** | `areal.php` | Ceník kurtů `cenik-kurtu.php` · Privátní trenéři `privatni-treneri.php` · Body Solution `body-solution.php` · Sportovní lékařství `sportovni-lekarstvi.php` |
| **Závodní tenis** | `zavodni-tenis.php` | Trenérský tým `zavodni-tenis-treneri.php` |
| **Tenisová škola** | `tenisova-skola.php` | Informace `tenisova-skola.php` · Ceníky `tenisova-skola-ceniky.php` · Rozvrhy `tenisova-skola-rozvrhy.php` · Trenérský tým `tenisova-skola-treneri.php` · Letní kempy `letni-kempy.php` |
| **Restaurace** | odkaz z nastavení `restaurace_url` (vlastní web, zatím neexistuje) → když je prázdné, `restaurace.php` („připravujeme“ + základní údaje) | – |
| **Prague Open** | odkaz z nastavení `prague_open_url` (vlastní web, zatím neexistuje) → když je prázdné, `prague-open.php` („připravujeme“ + základní údaje turnaje) | – |

Další stránky mimo menu: `kontakt.php` (odkaz v patičce), `akce.php?id=…` (detail akce s přihláškou),
`404.php`, přípravná stránka. Družstva a Turnaje tenisové školy klient **škrtl** – nedělat.

Obsah podstránek: z podkladů průzkumu (`C:\Users\Asus\cltk-navrhy\podklady\data\obsah.json`, další JSON
tamtéž, `C:\Users\Asus\cltk-navrhy\README.md` = ověřená fakta) a z hotových sekcí návrhů 1–3
(`C:\Users\Asus\cltk-navrhy\0X-…\index.html`). Kde údaj chybí, decentní štítek „doplní klub“ – nevymýšlet.
**Body Solution, Sportovní lékařství, Privátní trenéři: obsah zatím nemáme**, klub ho doplní později –
postavit jako stránky s editovatelnými bloky a štítkem „doplní klub“.

Kam se přesunulo, co z úvodu Varianty 4 zmizelo: kiosek Revue (41 čísel) → `revue.php`;
Zlatá deska (záložky + dvojí metr), „Od Žemly po Muchovou“ (medailony), kronika s dobovými jmény,
triptych se skóre → `historie.php`; konfigurátor ceny a přihláška → `clenstvi.php`; ceníky kurtů
léto/zima (a kalkulačka z návrhu 3) → `cenik-kurtu.php`; plán areálu → `areal.php`.

**Markéta Vondroušová:** klient ji výslovně chce zobrazovat normálně (úvodní galerie, historie, kiosek).
O trestech, sporech ČTS ani jménech dětí na webu nic.

## 4. Úvodní stránka (podle PDF klienta, s opravami)

1. **Hlavička** (§3).
2. **Informační lišta** pod menu (zlatý pruh s textem v prostrkaných verzálkách). Spravuje se
   v administraci: text, volitelný odkaz, platnost od–do, zapnuto. **Když není žádná aktivní zpráva,
   lišta se nezobrazí vůbec.** Výchozí zpráva: „Klubová restaurace je uzavřena od 1. 10. do 8. 10.“
   (platnost 1. 10. – 8. 10. 2026).
3. **Úvod:** vlevo „Ostrov Štvanice, Praha 7“, deska s nadpisem „Tenis na ostrově uprostřed Prahy
   od roku *1893*.“ (rok zlatou kurzívou), tlačítka **Stát se členem** (navy, → clenstvi.php) a
   **Ceník kurtů** (podtržený odkaz se šipkou, → cenik-kurtu.php). Vpravo **galerie 4 fotek se střídáním
   a přechodem tvarem klubového štítu** – převzít z Varianty 4 (`js/galerie.js`, CSS `.galerie`).
   Snímky ze správy: Vondroušová (Wimbledon 2023), Muchová (Wimbledon 2026), Muchová + Menšík (US Open
   2026), Billie Jean King Cup 2026 (zatím navy deska s výsledkem, dokud klub nedodá fotku – snímek
   může být typu „fotka“ nebo „deska“).
4. **Aktuality z klubu** – **nejsou to články**, jen fotka + nadpis + krátký popis (bez detailu,
   volitelně odkaz). Tři vedle sebe, fotka s vnitřním rámečkem, na pozadí velký světlý vodoznak znaku.
   Správa: fotka, nadpis, popis, odkaz, pořadí, zobrazit. Výchozí 3 položky z PDF:
   „Hrajeme v hale“ – „Halová sezóna začíná od 5. 10.“ (foto `aktuality-hala.jpg`);
   „Nová restaurace“ – „Od 1. 11. se můžete těšit na novou klubovou restauraci Tiebreak.“ (`aktuality-tiebreak.jpg`);
   „Terasa v novém“ – „Venkovní terasa restaurace prošla rekonstrukcí.“ (`aktuality-terasa.jpg`).
5. **Aktuální výsledky našich hráčů** – **víc než 3 výsledky** (klient: „musí jich tam být víc“;
   přesnou podobu ještě doladí s generálním manažerem). Postav to jako sloupce podle PDF
   (štítek turnaje zlatě verzálkami, jméno serifem, krátký text, velké zlaté sety, verdikt TITUL/FINÁLE…)
   v **vodorovném pásu, který jde posouvat** (šipky + tažení/scroll-snap, na mobilu po jednom), zobrazit
   třeba 6–8 posledních. Správa: datum, štítek, hráč(i), text, sety (pole), verdikt, odkaz, zobrazit, pořadí
   podle data. Výchozí data (opravená proti PDF):
   - 27. 9. 2026 · Billie Jean King Cup · finále · Česko – Ukrajina 2:0 · Karolína Muchová · „V úvodní dvouhře
     finále porazila Anhelinu Kalininovou. Česko získalo dvanáctý titul v soutěži.“ · **6:2 6:3** · Titul
   - 27. 8. 2026 · US Open · finále · smíšená čtyřhra · Karolína Muchová a Jakub Menšík · „Česká dvojice
     získala senzační grandslamový titul, když ve finále porazila švýcarsko-italský pár Bencicová, Cobolli.“ · 6:3 1:6 10:6 · Titul
   - 2. 8. 2026 · WTA 250 Memphis · finále · Darja Viďmanová · „Finále s Kristinou Liutovou a posun do elitní
     stovky.“ · 6:1 1:6 3:6 · Finále  (PDF tu mělo omylem štítek BJK Cupu)
   - dál z `obsah.json` → `vysledky_highlights` (Wimbledon 2026 Muchová, Figueira da Foz Viďmanová,
     Dauhá Muchová, Conseq Prague Open Martincová, Bad Homburg …) – jen skutečné výsledky se skóre.
6. **Klubový kalendář** „Na Štvanici se *potkáváme*.“ Vlevo výpis celého roku (měsíc · název · termín),
   vpravo okno s detailem. **Výchozí stav okna = nejbližší nadcházející akce, spočítaná automaticky
   z data** (server i JS; žádné ruční přepínání). Klik na akci v seznamu zobrazí v okně její detail
   (bez přenačtení stránky, s fallbackem odkazem na `akce.php?id=`). Proběhlé akce v seznamu ztlumené.
   Akce bez data („termín doplní klub“) mají jen měsíc. Každá akce má v administraci zaškrtávátko
   **„Zobrazit tlačítko Přihlásit se“** (Klubový den, golfový turnaj, Vánoční večírek…) → formulář
   přihlášky (jméno, e-mail, telefon, počet osob, poznámka, souhlas) → uloží se do `cltk_signups`,
   v administraci seznam přihlášených u akce + export CSV. Výchozí akce: seznam z PDF
   (`podklady/klient-pdf/stranka-01/02.jpg`, text v §8) + CTC U14 s opraveným textem:
   „Od roku 2008 se na Štvanici koná mezinárodní přátelské utkání dětí U14 v rámci asociace Centenary
   Tennis Clubs. Akce se zúčastní španělský Real Club de Tenis Barcelona, irský Carrickmines a belgický
   Royal Léopold Club.“ (název belgického klubu je **neověřený** – v administraci poznámka „ověřit“).
7. **Členství:** „Členem se může stát *každý*.“ + tlačítko **Stát se členem** (→ clenstvi.php), pod tím
   **video** (`podklady/video/cltk-promo-1080.mp4` / `-720.mp4` / `-poster.jpg`, bez zvuku, smyčka,
   automaticky jen bez omezení pohybu, jinak plakát s tlačítkem přehrát; video se nesmí stahovat na mobilu
   před zobrazením – `preload="none"` + spuštění až při zobrazení), přes celou šířku obsahu jako fotka v PDF.
   Pod videem **„Služby v areálu“** jako štítky (pilulky) z PDF: Recepce, Venkovní bazén, Fitness,
   Regenerace a wellness, Fyzioterapie a sportovní lékařství, Tenis shop, Salónek a dětský koutek,
   Restaurace Tiebreak, Parkoviště, Beach volejbal, Multifunkční hřiště (Slavoj), Multifunkční hřiště
   (u trojkurtu) – každý odkazuje na svou část `areal.php` (kotva). Spravovatelné.
8. **Naše historie:** přesně jako v PDF – tři černobílé fotky (`historie-drobny.jpg`, `historie-kodes.jpg`,
   `historie-vondrousova.jpg` z PDF), velký rok, jméno, „Wimbledon · dvouhra … · finále“:
   1954 Jaroslav Drobný, 1973 Jan Kodeš, **2023** Markéta Vondroušová (PDF mělo chybně 1975).
   Pod tím tlačítko **Kompletní historie** → historie.php.
9. **Partneři:** mřížka 5 × 5 jednobarevných log v teplé béžovo-zlaté (jako v PDF). Loga máme
   v `C:\Users\Asus\cltk-navrhy\assets\partneri\` (všech 25 z PDF: Vivus, J&T Banka, Pankrác, Aston, GreenGas,
   Noncore, Bechyně, Sport Construction, ISO, Smartwings, ČPP, Crane Constancy Capital, TUkas, RPM Facility,
   Advantage Cars, Babolat, Mizuno, Peach, Glenfiddich, Messy Play, Praha, Praha 7, Victoria, NSA, Český tenis).
   Připravit jednobarevné průhledné PNG (odmazat bílé pozadí, přebarvit) a totéž dělat automaticky při nahrání
   loga v administraci (GD). Klient pošle loga i v PDF – pak se jen vymění.
10. **Patička** podle PDF: velký slepotisk znaku, „I. Český Lawn-Tennis Klub Praha“, „Založen 1893“,
    4 sloupce – Adresa a příjezd · Kontakty (**jen Recepce a Eva Štefková**, čísla Petra Vaníčka a
    Vladislava Šavrdy klient chce pryč) · **Důležité informace** (Členství, Ceník kurtů, Závodní tenis,
    Tenisová škola, Letní kempy, Privátní trenéři, Historie) · Dokumenty a sítě – a Přístupnost
    (Omezit pohyb, Vyšší kontrast). Spodní tmavý pruh „© 2026 I. Český Lawn-Tennis Klub Praha · IČO 45243077“.
    Všechny texty a kontakty spravovatelné v „Texty a údaje“.

**Logo:** klient chce logo z výročí 100 let (pošle). Do té doby současný 3D znak
`C:\Users\Asus\cltk-navrhy\assets\logo\cltk-znak-3d*.png`. Logo musí jít vyměnit **jedním souborem**
(`web/assets/img/logo.png` + `logo-256.png`, odkazované jen z jednoho místa).

## 5. Data a výchozí obsah

- `web/sql/schema.sql` – úplné schéma (SQLite i MySQL varianta nebo kompatibilní DDL), vše `cltk_`.
- `web/sql/seed.php` – z příkazové řádky vytvoří čerstvou lokální DB a naplní ji výchozím obsahem;
  dílčí sady jsou v `web/sql/seed/NN-nazev.php` (načítají se podle abecedy), aby mohli přidávat
  různí agenti bez konfliktů.
- Obsahové obrázky (galerie, aktuality, trenéři, služby, historie, obálky Revue…) jsou v `web/uploads/…`
  (mimo git, na server se nahrají zvlášť). Seed je kopíruje ze zdrojů (`cltk-navrhy\assets`,
  `podklady/klient-pdf`, `podklady/video`). Designové obrázky (logo, ornamenty) v `web/assets/img/`.
- Stará agenda článků z cltk.cz (238 článků v `cltk-navrhy\podklady\data\clanky.json`) se **teď
  nepřevádí** – klient chce místo článků krátké aktuality. Data zůstávají připravená pro pozdější archiv.

## 6. Administrace (`web/admin/`)

Stejné vzory jako v Liberci (`require inc/layout.php`, `require_login()`, `csrf_check()`, `admin_head()`
/ `admin_foot()`, po zápisu `redirect()`, vypínat místo mazat, tabulka u jednoduchých položek / panel
u složitých). Vzhled ČLTK: postranní panel s 3D znakem a názvem „I. ČLTK Praha · Administrace“,
papírové pozadí, navy aktivní položka a tlačítka, zlaté vlasové linky, nadpisy Cormorant Garamond,
UI Jost, bílé karty s jemnou linkou, štítky stavu (Zobrazeno / Skryto / Publikováno). Funguje na mobilu.

Moduly (pořadí v postranním panelu):
1. **Přehled** – dlaždice s čísly, nové přihlášky (akce + členství), návštěvnost za 7 dní.
2. **Návštěvnost**
3. **Informační lišta**
4. **Úvodní galerie** (4+ snímky, typ fotka/deska, ohnisko fotky, popisek, kredit, pořadí)
5. **Aktuality z klubu**
6. **Výsledky hráčů**
7. **Kalendář akcí** (+ přihlášky k akci, CSV)
8. **Přihlášky** (do klubu z clenstvi.php + k akcím; stav vyřízeno, poznámka)
9. **Trenéři** (zařazení: závodní tenis / tenisová škola / privátní trenéři; foto, role, text, kontakt)
10. **Ceníky** (kurty léto, kurty zima, členství, tenisová škola, kempy, doplňkové služby – sekce a řádky
    jako `price_lists/price_sections/price_rows` v Liberci)
11. **Tenisová škola** (informace, rozvrhy, kempy – termíny)
12. **Areál a služby** (služby: název, kotva, text, foto, časy; štítky „Služby v areálu“)
13. **Historie** (kronika – milníky; triptych; osobnosti/medailony; Zlatá deska – čestní, zasloužilí,
    mistři republiky, grandslamy, olympiáda, prezidenti)
14. **Revue a newslettery** (čísla: rok, číslo, obálka, PDF odkaz, titulky, obsah; newslettery CS/EN)
15. **Vedení** (výkonný výbor, kancelář) a **CTC**
16. **Stránky** – editovatelné textové bloky podstránek (nadpis, perex, text z editoru, foto) pro všechno,
    co nemá vlastní modul (Body Solution, Sportovní lékařství, Privátní trenéři, Restaurace, Prague Open,
    úvodní texty stránek …).
17. **Partneři** (logo → automaticky jednobarevné, odkaz, pořadí)
18. **Dokumenty** (PDF ke stažení: Stanovy, Pravidla hraní, Osobní údaje členů, ceníky…)
19. **Texty a údaje** (kontakty, adresa, příjezd, sítě, odkazy Restaurace/Prague Open, rezervace,
    režim přípravy + náhledové heslo, e-mail pro přihlášky – výchozí prázdný = jen ukládat, neposílat)
20. **Účet** (změna hesla)

## 7. Nasazení (dělá jen orchestrátor, ne agenti)

- **Agenti nesmí sahat na FTP ani na server.** Přístupy jsou v `deploy/` (mimo git) a agenti je nečtou.
- `deploy/nahrat.php` (podle Liberce) – zkušební běh výchozí, `--ostra` opravdu nahraje; cílová cesta
  natvrdo `/www/cltkv2/`, jinam odmítne; nikdy nenahraje `inc/config.local.php`, `data/`, `*.sqlite`,
  a `uploads/` jen s přepínačem `--uploads`; před přepsáním existujícího souboru stáhne zálohu.
- `web/instalace.php` – vyžaduje instalační klíč z configu, založí jen chybějící tabulky `cltk_`,
  naplní výchozí obsah, založí účet; když účet existuje, odmítne. Po instalaci se ze serveru maže
  (nejdřív přebít souborem s `http_response_code(404)`, ověřit GET, pak smazat – past s cache PHP).
- Po nasazení ověřit, že web vrací 200 a Olymp (`https://www.tkolymppraha.cz/`) taky.

## 8. Text kalendáře z PDF (výchozí akce 2026)

únor – Conseq Prague Open, ITF W75 v hale – 8.–15. 2. · květen – Klubový den – čtyřhry, barbecue, kvíz –
24. 5. · červen – Valná hromada – 25. 6. · červenec – Letní dětské kempy, 6 termínů – 29. 6. – 28. 8. ·
srpen – Sekyra Group Prague Open, 26. ročník – 16.–22. 8. · září – Klubový den – 6. 9. · říjen – Zimní
sezóna v halách – přetlakové haly od 5. 10. · říjen – Tenisová škola podle zimních rozvrhů – potvrzeno ·
listopad – CTC U14 – I. ČLTK Praha Cup – termín doplní klub · prosinec – Mikulášská besídka – termín doplní
klub · prosinec – Večer talentů a Vánoční večírek v Letenském zámečku – termín doplní klub · prosinec –
OSTRA Tenisová extraliga – termín doplní klub.
