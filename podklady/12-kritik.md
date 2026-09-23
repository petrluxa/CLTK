# 12 – Kontrola úplnosti podkladů (kritik)

Stav k **23. 9. 2026**. Kontroloval jsem `README.md`, `podklady/data/obsah.json`, `11-design-brief.md` a zběžně podklady 01–10 a ostatní JSON. Porovnával jsem je s živým webem cltk.cz a s jeho `sitemap.xml`.
Surové soubory z kontroly jsou v `podklady/_raw/kritik/` (sitemap, seznamy URL se stavovými kódy, záloha `obsah.before.json`).

---

## 1. Co jsem ověřil strojově

| Kontrola | Výsledek |
|---|---|
| Validita JSON (`python json.load`) | Všech **12 souborů** v `podklady/data/` je v pořádku, žádný není rozbitý. |
| Cesty k souborům v `obsah.json` | **296 odkazů**, všechny existují. Jediná „chybějící“ cesta je šablona `assets/revue/revue-<rok>-<cislo>.jpg`. |
| Cesty v ostatních JSON | clanky 40, fotky 45, historicke-fotky 61, kronika 35, partneri 36, revue 41 a treneri 15. Všechny existují. |
| Obrázky v briefu a README | Všechny existují. |
| Pole `rozmer` v `obsah.json` × skutečný soubor | Žádný nesoulad. |
| Rozměry ve `fotky.json` × skutečný soubor | **20 nesouladů**. `width/height` tam popisují originál v `_raw/foto-original/`, ne soubor v `assets/` (viz P2-9). |
| Odkazy na cltk.cz a files.cltk.cz v `obsah.json` | **110 URL**, všechny vrací 200/206. Neexistující stránka vrací 404, takže nejde o falešné 200. |
| Externí odkazy v `obsah.json` | 68 URL. Jeden vracel 404 (hl. m. Praha, opraveno). Jeden vrací 403 jen robotům (Hodinářství Bechyně). |
| Sitemap: články | 238 URL článků. **Všechny** jsou v `clanky.json`. Nejnovější článek na webu je z 10. 9. 2026, shoduje se s podklady. |
| Sitemap: ostatní stránky | 76 stránek CS/EN. Všechny sekce jsou popsané v 01–03 (viz kap. 4). Podstránku Hospodský kvíz 28. 6. 2023 podklad 02 přehlédl (doplněno). |
| Assety bez odkazu z `obsah.json` | 46 souborů: 19 fotek článků, 21 historických snímků a 6 velikostí 3D znaku a favicon. Nejde o chybu, jsou vedené v `clanky.json` a `historicke-fotky.json`. |

## 2. Co jsem opravil nebo doplnil

Všechny změny v `obsah.json` jsou zapsané v `meta.zmeny_revize` s prefixem `[kritik]`. Záloha původní verze je v `_raw/kritik/obsah.before.json`.

**obsah.json**

1. `sin_slavy_poznamka`: počet „35 zasloužilých“ jsem upřesnil na 34 podle webu + Ivo Minář. README a brief uvádějí 34, JSON má 35 záznamů.
2. `cenik.zima_2026_27`: doplnil jsem přepočet předplatného na hodinu (`sezona_verejnost_za_hod`, `sezona_clen_za_hod`), jak ho uvádí PDF ceníku v závorce. Všech 8 pásem sedí na cena ÷ týdny. Hodí se pro kalkulačku v konceptu C.
3. **Nové** `klub.dokumenty`: Stanovy, Pravidla hraní 2026, zimní ceník, provozní řády bazénu, fitness a wellness a plán areálu v PDF. **Nové** `klub.gdpr`. Všechny odkazy jsem ověřil. Obsah GDPR stránky jsem přečetl: týká se jen členů, cookies nezmiňuje.
4. **Nové** `klub.rodokmen_stoletych` (brief A, prvek N7). Roky byly dosud jen v 09.
5. `klub.clenstvi_v_organizacich[CTC]`: doplnil jsem `mezinarodni_utkani`, `souteze` a `vedeni_ctc` (brief C, signature 7). CTC senioři 2025 na Štvanici byli trojzápas s Padovou a Cumberlandem, vyhrála Padova (Revue 02/2025).
6. **Nové** `hrali_na_stvanici`: 57 jmen „stěny es“ (brief B, signature 4). K nim jsem přidal doložené příležitosti, rozpor v počtu (54/58/59/60+) a seznam hráčů, kteří nebyli členy. Dosud to bylo jen v 06.
7. `aktuality` (Memphis): v perexu na webu je překlep „Liuovou“. Opravil jsem ho na „Liutovou“ podle textu článku 383.
8. `vysledky_highlights` (Martincová): „v hale klubu“ jsem změnil na „v pevné hale na Štvanici (kurty P1, P2)“. Kdo halu vlastní, není doloženo (viz P1-7).
9. `prague_open.termin_puvodni`: květnový ročník 2026 byl zrušen, protože dotace NSA nepřišla včas (Revue 01/2026, s. 2). Srpen je náhradní termín.
10. `zavodni_tenis.extraliga` 2025: „dělené 3. místo“ bylo odvozené z formátu soutěže. Jisté je jen 2. místo ve skupině (08 kap. 3).
11. `sin_slavy` (Viďmanova) a `pyramida_poznamka`: zapsal jsem rozdíly v pravopisu a to, že u SCM uvádějí prameny dvě různé role (Kubík × Vaníček).
12. `areal.oteviraci_doby`: „venkovní dvorce 7:00–22:00“ jsem ověřil na stránce O klubu a doplnil doslovnou citaci.
13. `akce.pravidelne`: doplnil jsem Babolat Amateur Tour. Byla jen v `nadchazejici`, i s vítězi 2018–2024. Dále jsem aktualizoval 35. Masters babytenisu (23. 8. 2026) a doplnil Hospodský kvíz 2023.
14. `zavodni_tenis.pyramida[0]`: doplnil jsem turnaje, kontakt a podmínky Tenisové školy (web TŠ).
15. `partneri`: odkaz na hl. m. Prahu vracel 404, nahradil jsem ho `https://praha.eu/`. U Hodinářství Bechyně je poznámka o chybě 403.

**README.md**

- Do tabulky jsem přidal řádek `12-kritik.md` a upozornění, že rozměry ve `fotky.json` patří originálům.
- Areál: doplnil jsem dobu pronájmu venkovních dvorců 7:00–22:00, dokumenty ke stažení a GDPR.
- Dvojí metr: štítek „Vyrostli na Štvanici · 20“ jsem přejmenoval na „Hráči Štvanice (klubová definice)“ s vysvětlením. Číslo zahrnuje Drobného-emigranta, krátce registrovaného D. Vacka i Složila.
- Kronika 2026: upřesnil jsem, co znamená „WTA poprvé na Štvanici“. Livesport Prague Open se tu hraje poprvé, ale WTA se na ostrově hrála naposledy v roce 2010. Doplnil jsem i zrušený květnový Prague Open.
- Martincová: „v hale klubu“ jsem změnil na „v pevné hale na Štvanici“ (viz obsah.json bod 8). Přidal jsem poznámku k pravopisu Viďmanovy a 35 záznamů zasloužilých.
- Sekce „Co chybí“ a „Ověřit fakta“: doplnil jsem sezónu 2027, zásady cookies, vlastnictví pevné haly, vazbu Složila a pořadí v extralize 2025.

---

## 3. Zbývající problémy podle důležitosti

### P1 – vysoká (ovlivní návrhy nebo důvěryhodnost webu)

1. **Markéta Vondroušová: rozhodnutí klubu stále chybí.** Týká se triptychu tří wimbledonských vítězů, názvu „Tenisová škola Markéty Vondroušové“, portrétu, citátu v `citaty[1]` a čísla „3 wimbledonští vítězové“ v `cisla`. ITIA jí uložila zákaz 22. 6. 2026, odvolání k CAS běží. Brief s typografickou záložní verzí počítá, rozhodnutí ale musí udělat klub.
2. **„16 titulů mistra republiky“ dokládají jen klubové prameny.** Nezávisle jsou doložené 4 tituly (1975, 1990, 2018, 2019; ČTK 2019: „počtvrté“). Roky 12 titulů Motorletu nikdo nevypisuje. Klub sám zmiňuje foto „Mistr ligy 1966“ (R13/2 s. 11) a podle newsletteru 130 let byly na pódiu týmy z let **1968**, 1975 a 1990. Jak postupovat: vždy psát s rozpisem a štítkem „podle klubu“, roky ověřit v Lichnerovi (1985, s. 47–48).
3. **Číslo „20 grandslamových titulů“ v přepínači dvojího metru.** Brief (koncept A, signature 2) ho stále popisuje jako „Vyrostli na Štvanici · 20“, a to je nepřesné. Klubová definice A zahrnuje každého, kdo tu kdy hrál, a patří sem i **Složil (RG mix 1978)**, kterého cs Wikipedie vede jako hráče Slavie. Bez Složila by číslo bylo 19. Jak postupovat: v briefu a mockupu psát „Hráči Štvanice · 20 (podle klubu)“, nebo Složila vyřadit. Číslo 10 „v barvách klubu“ je ověřené.
4. **Brief porušuje vlastní pravidlo „data jen z obsah.json“.** Rodokmen stoletých, CTC pas a stěnu es bral z README, 06 a 09. Teď jsou v `obsah.json` pod klíči `klub.rodokmen_stoletych`, `klub.clenstvi_v_organizacich[0].mezinarodni_utkani` / `souteze` a `hrali_na_stvanici`. Do patičky patří i `klub.dokumenty` a `klub.gdpr`. Odkazy v briefu (kap. 4–6 a matice kap. 7) je potřeba přepsat na tyto klíče. Brief jsem needitoval, není můj.
5. **Chybí provozní data a data jsou k 23. 9. 2026 už minulá.**
   - Web neuvádí otevírací dobu areálu, recepce ani restaurace Tiebreak. Restaurace nemá kontakt ani menu.
   - Oba klubové kalendáře jsou po 23. 9. prázdné.
   - **Kempy 2026, letní ceník 2026 i letní ceník TŠ už proběhly.** Koncept C, sekce 9 „Tenisová škola a kempy“, je staví jako aktuální nabídku. Mockup musí psát „Léto 2026“ a „termíny 2027 doplní klub“.
6. **Fotky: práva a rozlišení.**
   - Licence agenturních a turnajových snímků nejsou vyřešené (Getty, Sidorják, Sekyra Group, ČTS/Lebeda) a u historických snímků z Revue neznáme autora ani původ.
   - Hero panorama `letecky-stvanice-panorama-prahy.jpg` má jen 2000 px, 3D znak jen 402×445 px a historické snímky 300–1600 px.
   - „Tehdy a teď“ (koncept B): snímek `letecky-zimni-haly-shora.jpg` ukazuje Negrelliho viadukt **v rekonstrukci** (lešení, jeřáby), takže nepředstavuje dnešní stav. Povodeň 2002 je navíc focená z opačné strany. Chce to novou leteckou fotku.
7. **Vlastnictví stadionu a haly.** Centrkurt patří ČTS. Kdo vlastní a spravuje pevnou halu P1/P2, **nevíme**. Tisk po povodni píše, že svaz spravuje „centrální dvorec, halu a kanceláře“. Klubová Revue přesto píše o Conseq Prague Open „na kurtech svého klubu“. Totéž platí pro „19 kurtů“: je to počet kurtů v areálu Štvanice (tak to píše web) včetně svazového centrkurtu, ne „19 kurtů klubu“.

### P2 – střední (rozpory a nejistoty, které se musí v textech ošetřit)

1. **Názvy a roky v kronice.** Názvy klubu mají v pramenech různé roky (Spartak Praha Motorlet 1953/1956/1957, TJ Dopravní podnik 1969/1970) a liší se i počet změn (klub 4, iROZHLAS 3). Místo založení je sporné (Židovský × Střelecký ostrov), stejně jako roky Bubny 1895 × 1896, centrkurt 1926 × 1927, klubovna 1929 × 1930 a návrat Kodeše 1978 × 1979. Pozor na design: kronika řadí **Drobného Wimbledon 1954 do epochy „Motorlet“**. Razítko s dobovým jménem nesmí naznačit, že za Motorlet hrál, protože startoval za Egypt.
2. **Kapacity.** Stadion má podle ÚDU 7 000 míst k sezení, ČTK a klub uvádějí 8 000. Malý centr „cca 1000 míst“ uvádí jen klub. Kapacita parkoviště není známá.
3. **„Víc než šedesát“ hvězd na Štvanici.** Prameny uvádějí 54, 58, 59 nebo 60+ a seznam 57 jmen je jen klubový. Nadpis stěny es psát bez čísla (`hrali_na_stvanici.pocet_v_pramenech`).
4. **Pravopis jmen se v podkladech liší.**
   - Viďmanova (registr ČTS) × Viďmanová (většina klubových článků).
   - Pavluv × Pavlův.
   - Magdalena (PDF ČTS) × Magdaléna (web).
   - Marie Neumannová-Pinterová (web klubu) × Pinterová-Neumannová (`sin_slavy`) × Pintérová-Neumannová (Revue 02/2023).
   - Vlasta Vopičková (web) × Vopičková-Kodešová (`sin_slavy`).
   - Sjednotit s klubem.
5. **SCM: dvě role.** ČTS vede jako hlavního trenéra SCM Zdeňka Kubíka, web klubu uvádí Petra Vaníčka jako „vedoucího SCM“. Kubík navíc **nemá portrét**, takže krok SCM v „Cestě Štvanicí“ (koncept C) nemá jednotnou fotku.
6. **„První ryze české finále ženské dvouhry ve Wimbledonu“ (Muchová – Nosková 2026).** Ve finále 1986 hrály dvě rodačky z Československa, Navrátilová (už za USA) a Mandlíková. Navrátilová vyhrála 7–6, 6–3 (https://en.wikipedia.org/wiki/1986_Wimbledon_Championships_–_Women%27s_singles). Formulace musí znít „první finále dvou českých reprezentantek“. Zda platí i pro čtyřhry, je **NEOVĚŘENO** (10-overeni-hraci kap. 6).
7. **Extraliga 2025.** Konečné pořadí není doložené, jisté je jen 2. místo ve skupině. Termín extraligy 2026 zatím neznáme.
8. **Neověřená identita na fotkách.**
   - `hracka-antuka-radost-hero.jpg` (v `fotky.galerie.hraci_profi`) je „pravděpodobně Muchová“. Bez potvrzení ji nepopisovat jménem.
   - U `vyroci-130-let-oceneni-clenu.jpg` a `hist-1973-ligovi-mistri.jpg` (rok 1973 × 1975) nepoužívat jména ani rok.
   - `hist-1970s-jan-kodes-na-stvanici.jpg` je „na Štvanici“ jen podle popisku v Revue.
9. **`fotky.json` má zastaralé rozměry u 20 fotek.** Jsou to rozměry originálu v `_raw/foto-original/`. Podklad 03 kap. 10 navíc tvrdí, že portréty trenérů v `assets/` mají 3543×4724 px, ve skutečnosti mají 750×1000. Obojí musí opravit autor těchto souborů. `obsah.json` je správně.
10. **Anglická verze.** V podkladech nejsou žádné EN texty a dnešní EN web je zastaralý (uvádí „15 outdoor clay courts“, staré kontakty). Mezi rekreačními hráči je přitom hodně cizinců (Amateur Tour) a newsletter vychází i anglicky. Pro ostrý web je potřeba zdroj EN textů.
11. **Klubový titulek „zlatá na OH mládeže“** (Bukalová) znamená Olympiádu dětí a mládeže, národní akci. Nesmí se zaměnit s olympijskými hrami mládeže (YOG). V návrzích psát celý název.
12. **Počet členů** („na čtyři stovky“, Revue 01/2023) je tři roky starý a počet registrovaných hráčů neznáme.
13. **Plán areálu.** Souřadnice kurtů jsou „odhad z náhledu“. Chybí severka. Nevíme, které multifunkční hřiště se pronajímá, ani kde na plánu je brána od Hlávkova mostu.
14. **Viceprezident CTC od 2011 × 2014.** V novém klíči `vedeni_ctc` je rozpor výslovně uvedený.

### P3 – nízká (dokončit před ostrým webem)

1. **Kronika v `obsah.json` (38 záznamů) nemá** Czech Open 1987–1999, Prague Challenger 1991, návrat Prague Open 2001, WTA 2005–2010 ani AELTC 2025. Fakta jsou ověřená a leží v README a v `prague_open`. Nedoplnil jsem je, aby platil počet „všech 38 záznamů“ v briefu B. Doporučuji je doplnit a počet v briefu opravit.
2. **Zásady cookies a ochrany údajů pro návštěvníky webu** neexistují. GDPR stránka se týká jen členů a česká verze chybí v sitemap.
3. **Archivy s mezerami.**
   - Revue 01/2016 a 02/2016 nemají PDF a fulltext čísel 2006–2014 má chybné kódování „ď/ť“.
   - Stránka Babolat Amateur Tour 2025 vrací 404 a vítězové 2019 a 2020 nejsou vyplnění.
   - Fotogalerie má 50 alb, v `obsah.json` je jen odkaz a počet.
4. **Partneři.** Chybí úrovně a pořadí. Pět partnerů nemá web (Glenfiddich 2×, Moravia Steel, Kontron, Analytics Data Factory). Hodinářství Bechyně vrací robotům 403.
5. **Technika.**
   - Výsledkový feed Resultina/scorepresso běží jen přes http a nemá API.
   - Rezervace běží v RogerOnline a onlinehq a klub zatím neurčil, kdo bude vést stav „Dnes na Štvanici“.
   - Znak pod 128 px není otestovaný.
6. **Drobné rozdíly v registrech.** Hradecká je za klub podle registru ČTS od 2003, 08 uvádí „od 2001“ podle Wikipedie. Damm je v registru od 1996, klub píše „od 1984“. Platí registr, u starších let psát „podle klubu“.
7. **Tradice** „sezona začíná na Josefa“, turnaj dřeváků na Štědrý den, Drobného odznak s diamantem a Tendiv mají jen jeden klubový pramen.
8. **„Tehdy a teď“, druhý pár** (turnaj 1912 pod viaduktem): dnešní fotku oblouků musí dodat klub.

---

## 4. Pokrytí sekcí sitemap.xml

| Sekce dnešního webu | Kde je v podkladech | V `obsah.json` |
|---|---|---|
| Úvod, Aktuality (238 článků) | 04, `clanky.json` | `aktuality`, `vysledky_highlights` |
| O nás: plán areálu, galerie, partneři, uzavírky, Facebook | 01 kap. 3, 5, 11–14 | `areal.plan`, `partneri`, `areal.uzavirky_2026`, `klub.socialni_site` |
| Co nabízíme (9 služeb) | 01 kap. 4, 6 | `areal.sluzby`, `klub.dokumenty` (provozní řády) |
| Ceník | 01 kap. 9 | `cenik` |
| Klub: o klubu, členství, výbor, zasloužilí, CTC | 02 kap. 2–6 | `klub`, `clenstvi`, `sin_slavy`, `klub.clenstvi_v_organizacich` |
| Klubové turnaje a akce (14 podstránek) | 02 kap. 7 | `akce.pravidelne` (včetně Amateur Tour a kvízu 2023) |
| Revue, newsletter | 05, 02 kap. 9 | `revue`, `revue_info`, `newslettery`, `newsletter_info` |
| Kalendář (2 verze) | 02 kap. 8 | `akce.nadchazejici`, `klub.kalendar_ics` |
| Závodní tenis: trenéři, Prague Open 2026, podpora mládeže | 03 kap. 1–8 | `zavodni_tenis`, `treneri`, `prague_open` |
| Tenisová školička (7 podstránek) | 03 kap. 9 | `zavodni_tenis.pyramida[0]`, `cenik.tenisova_skola`, `cenik.letni_kempy_2026`, `treneri` |
| Kontakty | 01 kap. 10 | `klub.kontakty` |
| Členská přihláška, podmínky členství, GDPR | 02 kap. 3 | `clenstvi.prihlaska_pole`, `klub.gdpr` |
| EN verze (13 stránek) | 02 kap. 10 | jen `klub.jazyky` s poznámkou, texty chybí (P2-10) |
