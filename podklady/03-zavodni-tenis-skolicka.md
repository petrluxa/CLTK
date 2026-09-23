# 03 – Závodní tenis & Tenisová školička (podklady k redesignu cltk.cz)

Stav ke dni stažení: **23. 9. 2026**. Vše níže je převzato z uvedených zdrojů; co není ověřené, je označeno **NEOVĚŘENO**.
Surová data (HTML, PDF, JSON): `podklady/_raw/zavodni/` (git-ignored).

---

## 0. Co je dnes na webu (struktura sekcí)

| Stránka | URL | Obsah |
|---|---|---|
| Závodní tenis (rozcestník) | https://cltk.cz/cs/zavodni-tenis/ | Jen věta „Vyberte si prosím z nabídky níže…“ + 3 odkazy: Trenéři, Prague Open 2026, Podpora mládežnického tenisu |
| Trenéři | https://cltk.cz/cs/zavodni-tenis/treneri/ | 12 trenérů s fotkou (náhled 130 px) a odrážkami |
| Prague Open 2026 | https://cltk.cz/cs/zavodni-tenis/prague-open-2026/ | **Pouze text o dotaci NSA** + logo NSA (žádné info o turnaji) |
| Podpora mládežnického tenisu | https://cltk.cz/cs/zavodni-tenis/podpora-mladeznickeho-tenisu/ | 2 věty o dotaci hl. m. Prahy |
| Tenisová školička (rozcestník) | https://cltk.cz/cs/tenisova-skolicka/ | Odkazy: Informace, Ceník, Trenéři, Družstva, Turnaje, Letní kempy (Rozvrhy jsou jen v menu) |
| – Informace | …/tenisova-skolicka/informace/ | Popis školy + kontakty |
| – Ceník | …/tenisova-skolicka/cenik/ | Ceník zima 2026/27 a léto 2026 + podmínky |
| – Rozvrhy | …/tenisova-skolicka/rozvrhy/ | Harmonogram + PDF rozvrh 14.–18. 9. 2026 |
| – Trenéři | …/tenisova-skolicka/treneri/ | 4 trenérky/trenéři (3 s fotkou) |
| – Družstva | …/tenisova-skolicka/druzstva/ | Jen odkaz na cztenis.cz (babytenis) |
| – Turnaje | …/tenisova-skolicka/turnaje/ | Kategorie minitenis / střední kurt / babytenis |
| – Letní kempy | …/tenisova-skolicka/letni-kempy/ | Letní kempy 2026 – termíny, ceny, přihláška |

**Postřeh pro redesign:** závodní sekce je na dnešním webu extrémně chudá (nic o družstvech, výsledcích, hráčích, SCM/SVT/TSM, Extralize). Přitom klub má data, která nikdo jiný nemá (viz kap. 2–7). To je největší příležitost.

**„Výsledky našich hráčů“** (homepage i boční panel všech stránek) **neodkazuje na cztenis.cz** – je to vložený iframe `//scorepresso.com/export/cltk_small.php?v=1` (widget služby **Resultina / scorepresso**), který si stahuje `http://scorepresso.com/export/cltk_feed.php?offset=N` (7 zápasů na stránku). Viz kap. 5.

---

## 1. Trenéři závodního tenisu
Zdroj: https://cltk.cz/cs/zavodni-tenis/treneri/ (nadpis „Trenéři závodního tenisu I.ČLTK Praha“). Fotky staženy v originále 3543×4724 px (300 dpi) do `assets/foto/treneri/`. Všechny portréty jsou jednotné: studiový portrét na světle šedém přechodovém pozadí, černá mikina Mizuno s klubovým erbem (štít s nápisem I.ČLTK a dvěma míčky) – ideální pro jednotnou mřížku trenérů.

Zkratky: **SCM** = Sportovní centrum mládeže, **SVT** = Středisko vrcholového tenisu, **TSM** = Tréninkové středisko mládeže (definice ČTS, viz kap. 2).

### Vedení
**Petr Vaníček** – *sportovní ředitel a vedoucí SCM* · foto `petr-vanicek.jpg`
- trenér II. třídy
- od roku 1984 členem I.ČLTK Praha (od mládežnických družstev po národní ligu)
- od roku 1994 trenér mládeže (I.ČLTK Praha, Liechtenstein)
- působil jako sparingpartner bývalé první hráčky světa Martiny Hingis
- spolupracoval s úspěšnými hráči (např. Radka Bobková, David Rikl, Martin Damm, Karolína Plíšková, Andrea Hlaváčková)
- (navíc: kapitán týmu I.ČLTK Praha v OSTRA Tenisové extralize 2025 – zdroj tenisovaextraliga.cz/soupisky/)

### Trenéři kategorie junioři a dospělí
**Jiří Hřebec** – *trenér SVT a SCM* · `jiri-hrebec.jpg`
- trenér II. třídy
- bývalý československý reprezentant, top ranking 25 (19.4.1974)
- vítěz Galeova poháru (1970), účastník 17 zápasů v Davis Cupu (finalista z roku 1975)
- bývalý trenér Davis Cupu
- spolupracoval s úspěšnými hráči (např. Markéta Vodroušová [sic – překlep na webu], Iveta Benešová, Ivo Minář, Jan Hernych, Marek Routa, Robin Staněk, Vladislav Chramosta, Tomáš Cakl, Jaroslav Levinský)

**Ivo Minář** – *trenér SVT a SCM* · `ivo-minar.jpg`
- bývalý hráč ATP a reprezentant, top ranking 62 (20.7.2009)
- člen vítězného týmu Davis Cupu
- zahrál si finále turnaje okruhu ATP Tour v Sydney, kde prohrál s domácím hráčem Lleytonem Hewittem
- V roce 2000 vyhrál juniorské Mistrovství Evropy
- spolupracuje s úspěšnými hráči (např. Andrew Paulson, Jonáš Forejtek, Jakub Nicod)
- (navíc: druhý kapitán týmu v Extralize 2025 – tenisovaextraliga.cz/soupisky/)

**Milan Trněný** – *trenér SVT a SCM* · `milan-trneny.jpg` (pozn.: na webu má fotka chybný alt „Vaněk Daniel“, soubor se ale jmenuje „Trněný Milan.jpg“)
- trenér II. třídy
- bývalý hráč ATP a juniorský reprezentant
- dvojnásobný vítěz extraligy družstev, finalista Pardubické juniorky
- top ATP ranking ve dvouhře 471 (1995), ve čtyřhře 264 (1993)
- spolupracoval s úspěšnými hráči (např. Jiří Vaněk, Michal Vrbenský)

**Ing. Jan Vacek** – *trenér SVT a SCM* · `jan-vacek.jpg`
- trenér I. třídy
- bývalý hráč ATP a reprezentant, top ranking 61 (5.8.2002)
- na okruhu ATP Tour vyhrál jeden turnaj ve dvouhře, když triumfoval na Brasil Open 2001, na grandslamu se nejdále probojoval do čtvrtého kola ve Wimbledonu v roce 2002
- spolupracoval s úspěšnými hráči (např. Tatsuma Ito, Marco Chiudinelli, Tereza Martincová, Jan Hernych)

**Daniel Vaněk** – *trenér SVT a SCM* · `daniel-vanek.jpg`
- trenér II. třídy
- bývalý ligový hráč a juniorský reprezentant
- spolupracoval s úspěšnými hráči (např. Roman Jebavý, Ksenia Lykina, Michaela Krajíčková, Kateřina Böhmová, Jana Lubasová)
- (dle ČTS je hlavním trenérem SVT při I.ČLTK Praha – viz kap. 2. Pozor: v SVT je i stejnojmenný hráč Daniel Vaněk, roč. 2008.)

### Trenéři kategorie do 14 let
**Ing. Jaroslav Jandus** – *hlavní trenér mládeže a TSM* · `jaroslav-jandus.jpg`
- trenér II. třídy
- šéftrenér I.ČLTK Praha v letech 2000-2016
- bývalý trenér Fed Cupu
- spolupracoval s úspěšnými hráči (např. Iveta Benešová, Michaela Krajíčková, Zarina Diyas, Daniela Bedáňová, Denisa Chládková, Sandra Kleinová, Jitka Schönfeldová)

**Bc. Antonín Štěpánek** – *trenér mládeže a vedoucí TSM* · `antonin-stepanek.jpg`
- trenér I. třídy (titul Bc. z FTVS UK v oboru Trenér)
- vystudované 2,5 roku bakalářského studia v oboru Kondiční trenér na FTVS a 1 rok v navazujícím Mgr. studiu v oboru Kondiční trenér na Masarykově Univerzitě v Brně
- nejvyšší umístění na žebříčku ATP - 1235
- mistr republiky ve dvouhře v kategorii dorost, 5x mistr republiky ve čtyřhře v kategorii dospělí
- spolupracoval s úspěšnými hráči (např. Linda Fruhvirtová)

**Ing. Lubomír Štych** – *trenér mládeže a TSM* · `lubomir-stych.jpg` (soubor na webu „Štych Luboš.jpg“)
- trenér II. třídy
- bývalý ligový hráč
- trenér reprezentace dívek do 14 let
- spolupracoval s úspěšnými hráči (např. Karolína Plíšková, Kristýna Plíšková, Andrea Hlaváčková, Barbora Strýcová, Petra Rohanová)

**Magdaléna Zemanová** – *trenérka mládeže a TSM* · `magdalena-zemanova.jpg`
- bývalá ligová hráčka
- spolupracovala s úspěšnými hráči (např. Markéta Vondroušová, Jan Šátral, Denisa Hindová, Tomáš Piskáček, Marek Routa, Robin Staněk, Filip Zeman, Kateřina Kramperová)

### Kondiční trenéři
**Mgr. Pavel Janda** – *kondiční trenér SVT a TSM* · `pavel-janda.jpg`
- absolvent UK FTVS
- diplomovaný trenér I. třídy
- spolupracoval s úspěšnými hráči (např. Markéta Vodroušová [sic], Karolína Muchová, Karolína Plíšková, Kristýna Plíšková, Lucie Hradecká, Lukáš Rosol)

**Mgr. Richard Pavluv** (web píše „Pavluv“, soubor fotky „Pavlův Richard.jpg“ – správný tvar **NEOVĚŘENO**) – *kondiční trenér SCM a SVT* · `richard-pavluv.jpg` (alt na webu chybně „Janda Pavel“)
- absolvent magisterského studia UK FTVS, obor TVS, specializace tenis
- trenér I. třídy
- bývalý hráč 1. ligy
- mistr České republiky v kickboxu v kategorii fullcontact
- spolupracuje s úspěšnými hráči (Nikola Bartůňková, Jonáš Forejtek, Toby Kodat, Andrew Paulson a Jakub Nicod)

### Další trenéři, kteří v klubu působí, ale na stránce Trenéři nejsou
- **Zdeněk Kubík** – dle ČTS *hlavní trenér SCM při I.Český Lawn-Tennis Klub Praha* (PDF „Seznam hráčů SCM pro rok 2026“, cesky-tenis.cz); zástupce kapitána dorostu na MČR 2026 (cztenis.cz/soutez/9976).
- **Lukáš Vejvara** – trenér týmu starších žáků na MČR 2026 (článek https://cltk.cz/cs/clanky/starsi-zaci-vezou-z-mcr-druzstev-bronz/); zástupce kapitána mladšího žactva (cztenis.cz/soutez/9545, /9974).
- Kapitáni družstev dle cztenis.cz: dospělí B – Jan Michálek (zástupce 2026 Petr Šavrda, 2025 David Šimůnek).

---

## 2. Struktura závodního programu (ČTS střediska)
Zdroj definic: https://www.cztenis.cz/reprezentace2 (sekce Talentovaná mládež). Seznamy hráčů: PDF ČTS z 1. 1. 2026 – `https://cesky-tenis.cz/uploaded/reprezentace/2026/2026-hraci-scm.pdf`, `…-svt.pdf`, `…-tsm.pdf`.

| Stupeň ČTS | Věk | Kolik jich v ČR je | I.ČLTK Praha |
|---|---|---|---|
| PS – přípravné středisko | do 9 let | neomezeně | (Tenisová škola MV – 3–9 let; zda je formálně PS: **NEOVĚŘENO**) |
| **TSM** – tréninkové středisko mládeže | 10–14 let | „15-20 středisek“ | **ANO** – TSM při I. Český Lawn-Tennis Klub Praha: 14 hráčů + 2 „VK“; hlavní trenér **Antonín Štěpánek**, trenéři Jaroslav Jandus, Magdalena Zemanová |
| **SVT** – středisko vrcholového tenisu | 15–18 let | „Celkově je zřízeno 6 středisek SVT“ | **ANO** – SVT při I.ČLTK: 16 hráčů + 2 VK; hlavní trenér **Daniel Vaněk**, trenéři Jan Vacek, Milan Trněný |
| **SCM** – sportovní centrum mládeže | 15–21 let, „nejvyšší stupeň přípravy“ | „Celkově jsou zřízena 3 střediska SCM“ | **ANO** – SCM při I.Český Lawn-Tennis Klub Praha (IČO 45243077): 9 hráčů + 2 VK; hlavní trenér **Zdeněk Kubík**, trenéři Jan Vacek, Milan Trněný |

- Tři SCM v ČR 2026: **I.ČLTK Praha, TK Sparta Praha, TK Prostějov**. Šest SVT: TK Slavia Plzeň, TK Prostějov, TK PRECHEZA Přerov, Liberecký TK, **I.ČLTK Praha**, TK Sparta Praha. TSM je v PDF 20 (mj. I.ČLTK, Sparta, Prostějov, Olymp Praha, LTC Modřany…). → **I.ČLTK je jedním ze tří klubů v ČR (spolu se Spartou a Prostějovem), které mají celou pyramidu ČTS TSM → SVT → SCM** (ověřeno v PDF ČTS 2026).
- Hráči SCM I.ČLTK 2026 (dospělí/junioři, veřejně publikováno ČTS): Nikola Bartůňková (2006, WTA 130), Radek Chodora (2007, ITF 155), Vendula Valdmannová (2007, ITF 18 / WTA 343), Sarah Melany Fajmonová (2007, ITF 33 / WTA 791), Jakub Šmejcký (2008, ITF 119), Denisa Žoldáková (2008, ITF 72), Sofie Hettlerová (2009, ITF 43), Veronika Sekerková (2009, ITF 105), Marek Šmejcký (2010, ITF 719); VK: Filip Ladman (2009), Eliška Ticháčková (2006). (Žebříčky platné k datu PDF.)
- SVT a TSM obsahují děti 2008–2016 – jména do webu nepřebírat bez souhlasu klubu; stačí počty.
- Význam „VK“ v PDF: **NEOVĚŘENO**.

**Reprezentace ČR 2026** (PDF ČTS „Seznam reprezentantů“ 2026, https://cesky-tenis.cz/uploaded/reprezentace/2026/seznam_reprezentantu_2026.pdf): z 146 jmen je **26 z I. Český Lawn Tennis Klub Praha** (U12: 0, U14: 4, U18: 10, dospělí: 12) – 3. nejvíc po TK Prostějov (39) a TK Sparta Praha (38). Dospělí reprezentanti z klubu: Radek Chodora, Oliver Sanders, Matyáš Kozlovský, Sarah Melany Fajmonová, Nikola Bartůňková, Karolína Muchová, Markéta Vondroušová, Darja Viďmanová, Matěj Vocel, Anastasia Detiuc, Jakub Nicod, Jakub Filip. (V PDF je u Filipa Ladmana chybně ročník „2099“.)

---

## 3. Soutěže družstev 2025 a 2026 (cztenis.cz)
Klub v adresáři ČTS: **„I.ČLTK Praha“, číslo klubu 52** – https://www.cztenis.cz/adresar/detail/52 (tel. 737215012, info@cltk.cz, Ostrov Štvanice 38, 17000 Praha 7, **dvorců v létě 18, v zimě 12**, kontaktní osoba Mgr. Petr Šavrda). Týmy nalezeny vyhledáváním https://www.cztenis.cz/vyhledavani?a=druzstva&q=I.ČLTK%20Praha. Počet registrovaných hráčů klubu cztenis.cz **neuvádí** (viz kap. 5 – počet hráčů v žebříčcích).

### Dospělí
| Sezóna | Soutěž | Tým | Výsledek | Zdroj |
|---|---|---|---|---|
| 2025 (prosinec) | **OSTRA Tenisová extraliga 2025** (= Mistrovství ČR smíšených družstev, halová, 14.–19. 12. 2025, Prostějov/Říčany/Olomouc) | I.ČLTK Praha | Skupina PRAHA (hala Říčany): 16. 12. TK Sparta Praha – I.ČLTK Praha **5:1**; 17. 12. I.ČLTK Praha – Plíšková Tennis Academy **6:3** → 2. ve skupině, do finále nepostoupil. Mistr: TK Sparta Praha (finále se Přerovem 5:2). | tenisovaextraliga.cz; cztenis.cz/clanky/o-finalistech-rozhodnuto-sparta-a-prerov-postupuji |
| 2024 | OSTRA Tenisová extraliga 2024 | I.ČLTK Praha | Pořadí: 1. TK Sparta Praha, 2. TK PRECHEZA Přerov, **3-4. I.ČLTK Praha**, 3-4. TK Agrofert Prostějov… (na webu pod nadpisem „Pořadí 2024“, ale s textem „Konečné pořadí tenisové extraligy 2023“ – rok **NEOVĚŘENO**, obsah odpovídá finále 2024 Sparta–Přerov) | tenisovaextraliga.cz |
| 2023 | Extraliga 2023 | I.ČLTK Praha | Předkolo vyhrál; „Sparta porazila I.ČLTK Praha 5:1“; „I.ČLTK Praha udolala Spoje“ 5:4 | titulky článků cztenis.cz |
| 2026 | 01. Pražská divize | I.ČLTK Praha B | 8. z 8 (2 výhry, 5 porážek) | cztenis.cz/soutez/9542 |
| 2025 | 01. Pražská divize | I.ČLTK Praha B | 6. z 8 (3–4) | cztenis.cz/soutez/9029 |

**Soupiska I.ČLTK v Extralize 2025** (finální, tenisovaextraliga.cz/soupisky/): muži – Marek Gengel (V), Jonáš Forejtek, Tadeáš Paroulek, Jakub Nicod, Andrew Paulson (na webu překlep „Anrew“), Jakub Filip, Matěj Vocel, Jiří Barnat; ženy – **Karolína Muchová, Markéta Vondroušová, Nikola Bartůňková, Darja Viďmanová, Tereza Martincová**, Sarah Melany Fajmonová, Denisa Žoldáková (V). Kapitán: Petr Vaníček, Ivo Minář. ((V) = hostování.) Článek ČTS: „velmi silnou ženskou sestavu pro extraligu má klub I. ČLTK Praha“; „Mezi nejnabitější týmy patří jako už tradičně TK Agrofert Prostějov, TK Sparta Praha a I. ČLTK Praha.“ (cztenis.cz/clanky/ostra-tenisova-extraliga-startuje-uz-v-nedeli-titul-obhajuje-sparta). Zápis ze schůze AETK se konal „Praha-Štvanice, 7.10.2025“.
Extraliga 2026 (prosinec 2026) zatím neproběhla. Historický počet extraligových titulů klubu jsem v přečtených zdrojích nenašel (**NEOVĚŘENO** – řeší asi podklady o historii).

### Dorost (MČR smíšených družstev)
| Sezóna | Soutěž | Výsledek | Kapitán / zástupce | Zdroj |
|---|---|---|---|---|
| 2026 | 300 MČR družstev – TK Sparta Praha ČF, SF 9.–12. 9. 2026 | **3. místo** (1. Sparta, 2. Prostějov) | Milan Trněný / Zdeněk Kubík | cztenis.cz/soutez/9976 |
| 2025 | 300 MČR družstev ČF, SF + 304 finále o 1.–2. místo (14. 9. 2025) | vítěz semifinálové skupiny, finále I.ČLTK – Sparta **2:5** → **2. místo (vicemistr ČR)** | Daniel Vaněk / Jan Vacek | cztenis.cz/soutez/9497, /9526 |

### Starší žactvo
| Sezóna | Soutěž | Výsledek | Zdroj |
|---|---|---|---|
| 2026 | 51. Pražská liga O pohár předsedy ČTS | I.ČLTK Praha A **2.** (5–1) | cztenis.cz/soutez/9544 |
| 2026 | 500 MČR družstev ČF a SF – Ostrava 28.–30. 8. 2026 | **3. místo – bronz** (ČF 7:2 Plíšková TA-Říčany, SF 3:5 Agrofert Prostějov, o 3. místo 5:4 LTC Modřany); trenéři Antonín Štěpánek, Lukáš Vejvara, Jaroslav Jandus | cztenis.cz/soutez/9975; https://cltk.cz/cs/clanky/starsi-zaci-vezou-z-mcr-druzstev-bronz/ (31. 8. 2026) |
| 2025 | 30. Pražská liga O pohár předsedy ČTS | **1. místo** (6–0, body 50:4) | cztenis.cz/soutez/9031 |
| 2025 | MČR družstev Rakovník (500/501/534) | **3. místo – bronz** | cztenis.cz/soutez/9498, /9510 |

### Mladší žactvo
| Sezóna | Soutěž | Výsledek | Zdroj |
|---|---|---|---|
| 2026 | 70. Pražská liga O pohár předsedy ČTS | 3. (4–2) | cztenis.cz/soutez/9545 |
| 2026 | 700 MČR družstev – TK Zlín 8.–11. 7. 2026 | **3. z 8** | cztenis.cz/soutez/9974 |
| 2025 | 40. Pražská liga O pohár předsedy ČTS | **1. místo** (5–1) | cztenis.cz/soutez/9032 |
| 2025 | 700 MČR družstev – TK Zlín | 4. z 8 | cztenis.cz/soutez/9489 |

### Babytenis a střední kurt (Praha, „Memoriál Zdeňka Kocmana“) – týmy Tenisové školy
Kapitán všech týmů: Mgr. Jan Pecha, Ph.D., zástupce Andrea Vašíčková.
| Sezóna | Soutěž | Týmy I.ČLTK a umístění | Zdroj (cztenis.cz/soutez/…) |
|---|---|---|---|
| 2026 | **Memoriál Z. Kocmana XX. MČR družstev v babytenisu – Prostějov 4.–6. 9. 2026** | **2. místo z 12** (4 výhry, 1 porážka – 1:5 s Agrofert Prostějov) | 9995 |
| 2026 | 90. Liga – Baby tenis | A **2.** (7–1), B 5. (z 9) | 9748 |
| 2026 | 94. 1. třída D – Baby tenis | C **2.** | 9753 |
| 2026 | 95. 1. třída E – Baby tenis | D 3. | 9805 |
| 2026 | 96. Baby tenis – střední kurt A | A **1.** (6–0), E 3. | 9756 |
| 2026 | 97. Baby tenis – střední kurt B | B 5. | 9757 |
| 2026 | 98. Baby tenis – střední kurt C | D **2.**, C 3. | 9758 |
| 2025 | 50-V- Baby tenis 1. výkonnostní třída | A **1.** (6–0), B 5. | 9033 |
| 2025 | 53-V- 2. výkonnostní třída C | C **1.** (6–0, body 36:0) | 9214 |
| 2025 | 54-V- 2. výkonnostní třída D | D **2.** | 9215 |
| 2025 | 55. střední kurt (UX) | A 4., B 5. | 9219 |
| 2025 | 56. střední kurt | C 5. | 9217 |

Shrnutí 2026: klub hraje ve **všech věkových kategoriích** – babytenis/střední kurt (týmy A–E), mladší žactvo, starší žactvo, dorost, dospělí (Extraliga + B-tým v Pražské divizi). Medaile z MČR družstev 2026: babytenis stříbro, starší žactvo bronz, mladší žactvo 3. místo, dorost 3. místo. 2025: dorost stříbro, starší žactvo bronz.

---

## 4. Hráči klubu v žebříčcích ČTS (léto 2026)
Zdroj: https://www.cztenis.cz/zebricky/{muzi,zeny,dorostenci,dorostenky,starsi-zaci,starsi-zakyne,mladsi-zaci,mladsi-zakyne}, sezóna „léto 2026“, staženo celé pořadí a spočítány řádky s klubem „I.ČLTK Praha“.

| Žebříček | hráčů I.ČLTK | z toho v TOP 10 | v TOP 50 |
|---|---|---|---|
| Muži | 35 | 1 | 7 |
| Ženy | 30 | 4 | 7 |
| Dorostenci | 27 | 1 | 9 |
| Dorostenky | 19 | 3 | 6 |
| Starší žáci | 24 | 1 | 6 |
| Starší žákyně | 18 | 4 | 6 |
| Mladší žáci | 20 | 0 | 7 |
| Mladší žákyně | 19 | 0 | 8 |
| **Unikátních hráčů celkem** | **121** | | |

- **Ženy léto 2026: 1. Karolína Muchová, 6. Markéta Vondroušová, 9. Nikola Bartůňková, 10. Darja Viďmanova** → **4 z TOP 10 českého ženského žebříčku jsou ze Štvanice.** Dále 20. Tereza Martincová, 41. Anastasia Detiuc.
- Muži: 10. Jonáš Forejtek, 17. Jakub Nicod, 18. Tadeáš Paroulek, 19. Andrew Paulson, 25. Matěj Vocel, 39. Jiří Barnat.
- Babytenis/minitenis nemají celostátní žebříček → hráči TŠ v počtu nejsou. Celkový počet registrovaných hráčů klubu: **NEOVĚŘENO** (ČTS ho veřejně neuvádí).

---

## 5. Widget „Výsledky našich hráčů“ (Resultina / scorepresso)
- iframe `//scorepresso.com/export/cltk_small.php?v=1`, data `http://scorepresso.com/export/cltk_feed.php?offset=0,7,14…` (HTML fragmenty: turnaj, kolo, hráči, skóre, čas; hráč klubu je tučně, v atributu `title` je jeho ATP/WTA/ITF ranking). Prázdná stránka vrací „Žádné další výsledky štvanických hráčů“.
- Ukázka (staženo 23. 9. 2026): Maria-Lanzendorf J100 – Filip Ladman; Pardubice $30,000 ITF – Jakub Filip, Jakub Vrtílka, Max Froelich, Daniel Vaněk, Daniel Bien; Plovdiv ATP Challenger – Jonáš Forejtek (ATP 426) d. Gima; Ljubljana $115,000 – Denisa Žoldáková; Guadalajara WTA – Darja Viďmanova; Tulln ATP Challenger – Andrew Paulson (ATP 606); **New York – Grandslam: Nikola Bartůňková (WTA 38), Karolína Muchová (WTA 7)**; Opava – Ivo Šenkyřík, Eliška Strachová… (rankingy z feedu, oficiálně **NEOVĚŘENO**).
- Pro nový web: feed je použitelný jako **živý „ticker“ výsledků štvanických hráčů po celém světě** (pozor: jen http, žádné API/CORS – bude potřeba proxy nebo nový dodavatel; **NEOVĚŘENO**, zda lze feed legálně/technicky přebírat).

---

## 6. Úspěchy hráčů 2026 z aktualit klubu (https://cltk.cz/cs/clanky/)
(data = datum u článku)
- 26. 6. 2026 – Emily Bukalová (2013) zlatá na Olympiádě dětí a mládeže (dvouhra dívek), Filip Wollner stříbro (dvouhra i čtyřhra) – /clanky/emily-bukalova-zlata-na-oh-mladeze-filip-wollner-ziskal-stribro:376/
- 27. 6. 2026 – **Karolína Muchová vyhrála WTA 500 Bad Homburg** (ve finále Naomi Osaka, skreč za 6:1 1:0); „druhý titul v letošní sezoně i třetí v kariéře“ (Soul 2019, Dauhá 2026); návrat do TOP 10 (9. místo) – :377/
- 14. 7. 2026 – **Karolína Muchová ve finále Wimbledonu** (prohra s Lindou Noskovou 2:6, 7:5, 3:6; „historicky první ryze české grandslamové finále“) – :381/
- 5. 8. 2026 – Darja Viďmanova finále WTA 250 Memphis (prohra s K. Liutovou 6:1, 1:6, 3:6), posun na kariérní maximum 92. místo – :383/
- 16. 8. 2026 – Prague Open 2026 (viz kap. 7) – :384/
- 27. 8. 2026 – **Karolína Muchová s Jakubem Menšíkem vyhráli smíšenou čtyřhru US Open** (finále 6:3, 1:6, 10:6 nad Bencic/Cobolli; na Arthur Ashe Stadium) – :385/
- 31. 8. 2026 – Denisa Žoldáková finále ITF W75 v Polsku (z kvalifikace; prohra s B. Palicovou) – :386/
- 31. 8. 2026 – Starší žáci bronz na MČR družstev (Ostrava)
- 10. 9. 2026 – Justýna Reifová vyhrála ITF J30 Humenné (dvouhra + čtyřhra); Klára Štěpánková double na J30 v Jasech
- ⚠️ 23. 6. 2026 – „Prohlášení vedení klubu k případu Markéty Vondroušové“ – klub odsuzuje „nespravedlivý a likvidační trest“ (ITIA) a vyjadřuje jí plnou podporu; Vondroušová vyrůstala v klubu od babytenisu a dala jméno tenisové školičce. **Citlivé téma** – při návrhu webu s jejím jménem/tváří zacházet opatrně (konzultovat s klubem). Detaily trestu na webu klubu nejsou.

---

## 7. Prague Open 2026
Zdroje: https://cltk.cz/cs/zavodni-tenis/prague-open-2026/ ; článek https://cltk.cz/cs/clanky/sekyra-group-prague-open-2026-by-advantage-cars:384/ ; oficiální web https://www.pragueopen.net (Wix, „© 2026 by Jan Pecha“) ; PDF pavouky z pragueopen.net/draw.

- Název: **SEKYRA GROUP PRAGUE OPEN 2026 by ADVANTAGE CARS**; pořádá I. Český Lawn-Tennis Klub Praha a společnost Perinvest.
- Termín **16.–22. srpna 2026**, Ostrov Štvanice; **muži ATP Challenger 75 + ženy ITF World Tennis Tour W50**; antuka (Clay). Web: „26 YEARS OF THE INTERNATIONAL TENNIS TOURNAMENT“, historie „2001 - 2025“.
- Vstup zdarma; finále v sobotu 22. srpna živě na TV Prima Sport; stream na webu turnaje. Parkování pod Hlávkovým mostem, MHD Florenc/Vltavská, lávka HolKa.
- **Prize money:** muži **EURO 97 640** (Main draw singles: R32 €955 / 0 b.; R16 €1,590 / 6; QF €2,825 / 12; SF €5,030 / 22; F €8,760 / 44; vítěz €15,510 / 75 bodů). Ženy **Prize Money US$ 40000** (Tourn. Key W-ITF-CZE-2026-006). ATP Supervisor Jiri Adamovsky, ITF supervisor Martina Rezacova.
- **Výsledky 2026 (z PDF pavouků):**
  - Dvouhra muži: vítěz **Jan Kumstát (CZE, SE)** – finále vs. Chun-Hsin Tseng (TPE) **5-7 6-3 6-0**. (Nasazená 1 V. Sachko odstoupil, J. Forejtek odhlášen – zápěstí.)
  - Čtyřhra muži: **Andrew Paulson (CZE, hráč I.ČLTK) / Joran Vliegen (BEL)** – finále vs. Grevelius/Heinonen [4] **6-4 6-3**.
  - Dvouhra ženy: vítězka **Jana Kovačková (CZE, WC)** – finále vs. Xinyu Gao [6] **6-1 6-1**. Denisa Žoldáková (WC, I.ČLTK) došla do 2. kola.
  - Čtyřhra ženy: **Alena Kovačková / Jana Kovačková [3]** – finále vs. Strakhova/Tikhonova [1] **6-2 6-3**.
- Dotace NSA: „NSA podporuje akci Prague Open 2026 částkou **2 890 401 Kč**“ (oblast: Podpora významných sportovních akcí nižších sportovních kategorií, VSA s celkovými náklady min. 2 000 000 Kč).
- Grafika plakátu 2026 (files.cltk.cz/t99bnuw4i4n01/2026.png): tmavá fotografie antuky se siluetou hráče s raketou, bílé písmo, loga ATP Challenger a ITF World Tennis Tour W50 Prague, pás partnerů.

---

## 8. Podpora mládežnického tenisu (dotace)
Zdroj: https://cltk.cz/cs/zavodni-tenis/podpora-mladeznickeho-tenisu/
- Pražský tenisový svaz je příjemcem prostředků od Hlavního města Prahy – Program podpory sportu a tělovýchovy v hl. m. Praze pro rok 2026, opatření I., „Sportovní činnost mládeže v tenisových klubech 2026“.
- I.ČLTK Praha se účastní jako realizátor; přidělena poměrná část dotace **1 539 555 Kč**.
- (Na stránce Prague Open je navíc dotace NSA 2 890 401 Kč – viz kap. 7.)

---

## 9. Tenisová školička – „Tenisová škola Markéty Vondroušové“ (TŠ MV)
### Informace (https://cltk.cz/cs/tenisova-skolicka/informace/)
- „Tenisová škola je již více než 15 let přímou součástí I.ČLTK Praha a v roce 2023 s ní spojila své jméno wimbledonská vítězka Markéta Vondroušová.“
- Aktivity: pravidelná výuka, organizace turnajů dětí a mistrovských utkání družstev, kempy a soustředění.
- Věk **3–9 let**, „zejména na výkonnostní úrovni“; talentované děti postupně užším výběrem reprezentují klub v soutěžích družstev a jednotlivců. **Nábory vždy na jaře a na podzim.**
- Kontakt: +420 721 663 118, pecha@cltk.cz.
- Obrázek na stránce (`//files.cltk.cz/da1drgnduib01/sig TŠ MV 2.jpg`, originál 2618×1732, kopie v `_raw/zavodni/sig-ts-mv.jpg`): skupinové foto cca 45 dětí v bílých tričkách s klubovým erbem na antukovém kurtu s trenérkou, dole bílé logo-monogram „MV“ + „TENISOVÁ ŠKOLA MARKÉTY VONDROUŠOVÉ“.

### Trenéři TŠ (https://cltk.cz/cs/tenisova-skolicka/treneri/)
**Mgr. Jan Pecha, Ph.D.** – *vedoucí trenér tenisové školy* (osobní web https://janpecha.com) · `jan-pecha.jpg` · mobil +420 721 663 118, pecha@cltk.cz
- absolvent postgraduálního doktorského studia v oboru kinantropologie na UK FTVS (zaměření na dlouhodobou koncepci sportovního tréninku)
- diplomovaný trenér ČTS
- asistent manažera klubu, člen organizačního výboru při mezinárodním turnaji Prague Open, působil jako manažer sekce tenis v rámci VICTORIA Vysokoškolského sportovního centra MŠMT
- lektorská činnost pro ČTS a PTS
- člen výboru I. ČLTK Praha, Pražského tenisového svazu a Rady Českého tenisového svazu (člen Trenérsko-metodické komise PTS a ČTS)
- účastník řady tuzemských a zahraničních vědeckých konferencí se zaměřením na sportovní trénink
- od roku 2008 trenérem v I. ČLTK Praha; od roku 2015 člen reprezentačních výjezdů ČTS
- spolupracoval s úspěšnými hráči (např. Jonáš Forejtek, Andrew Paulson, Darja Viďmanova, Nikola Bartůňková)
- (navíc: autor webu pragueopen.net – patička „© 2026 by Jan Pecha“)

**Andrea Vašíčková** – *zástupce vedoucího trenéra tenisové školy* · `andrea-vasickova.jpg` · mobil +420 602 819 899
- koordinátorka sběračů při Prague Open 2022-24
- trenér II. třídy ČTS
- dva roky působila jako trenér kategorie U10 v TK Sparta Praha, od roku 2009 v I. ČLTK Praha

**Bc. Adéla Vašíčková** – *trenérka tenisové školy* · `adela-vasickova.jpg` · mobil +420 725 480 412
- absolventka bakalářského studia na UK FTVS (téma bakalářské práce: Vybrané somatické faktory u elitních hráčů a hráček tenisu)
- trenér II. třídy ČTS; trenérka v I.ČLTK Praha od roku 2021
- bývalá ligová hráčka ČTS, vítězka dvouhry přeborů Prahy ve všech věkových kategoriích do 18 let, přední hráčka celostátního žebříčku ČTS v kategoriích do 12 let (CŽ 5), 14 let (CŽ 11) a 18 let (CŽ 15)
- členka české reprezentace v padelu

**Berenika Urbanová** – *asist. trenérka tenisové školy* (bez fotky a bez popisu)

### Ceník (https://cltk.cz/cs/tenisova-skolicka/cenik/)
**Zimní období 2026/27** – tréninkové období od 7.9.2026 do 2.4.2027; tréninkové volno 21. - 28. září 2026, 26. - 30. října 2026, 17. listopadu 2026, 23. prosince 2026 - 3. ledna 2027
| Trénink | Cena |
|---|---|
| individuální, celý kurt, 60 min, 1 trenér | 1.350,- Kč / jednotlivec |
| individuální, 1/2 kurtu, 60 min, 1 trenér | 900,- Kč / jednotlivec |
| ve 2 hráčích, celý kurt, 60 min, 1 trenér | 675,- Kč / jednotlivec |
| ve 3 hráčích, celý kurt, 60 min, 2 trenéři | 625,- Kč / jednotlivec |
| ve 4 hráčích, celý kurt, 60 min, 2 trenéři | 475,- Kč / jednotlivec |
| ve skupině (začátečníci), 60 min | 375,- Kč / jednotlivec |

**Letní období 2026** – od 7.4.2026 do 26.6.2026; volno 1. května 2026, 8. května 2026
| Trénink | Cena |
|---|---|
| individuální, celý kurt, 60 min, 1 trenér | 950,- Kč / jednotlivec |
| ve 2 hráčích, celý kurt, 60 min, 1 trenér | 475,- Kč / jednotlivec |
| ve skupině (začátečníci), 60 min | 300,- Kč / jednotlivec |

Podmínky (zkráceně, věrně): tréninky evidovány v přehledech trenérů a v IS I.ČLTK Praha; cena zahrnuje trenéra i pronájem kurtu; platba formou kursovného za celé období (tr. Andrea a Adéla Vašíčkovy) nebo dle skutečnosti (tr. Pecha); při nepříznivém počasí v hale (při obsazení haly zrušeno a neúčtováno); náhrady po dohodě jen ve volných kapacitách; vyúčtování koncem měsíce e-mailem; rodiče se mají během tréninků zdržovat mimo dvorec; **základní členství TŠ 1.000,- Kč** (hradí se vždy na začátku roku).

### Rozvrhy / harmonogram (https://cltk.cz/cs/tenisova-skolicka/rozvrhy/)
- 7. září 2026 … tréninky (provizorní režim, testování zimních rozvrhů)
- 7. září 2026 … schůzka s rodiči (sraz na recepci, od 18:30 hodin)
- 21. - 28. září 2026 … nehraje se (stavba hal)
- 29. září 2026 - 2.4.2027 … tréninky dle zimních rozvrhů
- Prázdniny: 26. - 30. října 2026 podzimní; 17. listopadu 2026 státní svátek; 23. prosince 2026 - 3. ledna 2027 vánoční. „Poznámka: změny vyhrazeny“
- PDF „rozvrh 14-18 zari 2026.pdf“ (verze k 12/9/2026): 2 strany A4 na šířku, **Kurt 15** (kontakt Jan Pecha) a **Kurt 7** (kontakty Andrea a Adéla Vašíčkovy), pondělí–pátek, bloky po hodině **13–19 h**, v buňkách příjmení dětí a počet trenérů (2–3 trenéři). → Rozvrh se dnes publikuje jako PDF tabulka se jmény dětí (pro nový web: spíš přehled časů bez jmen / přihlášení rodičů).
- Poznámka: „stavba hal“ 21.–28. 9. = sezónní nafukovací/zimní haly (**NEOVĚŘENO**, že jde o nafukovací haly).

### Družstva (https://cltk.cz/cs/tenisova-skolicka/druzstva/)
Jen odkaz „Přehled termínů utkání včetně výsledků všech družstev I.ČLTK Praha v kategorii babytenis a střední kurt naleznete zde“ → https://cztenis.cz/babytenis/druzstva (přesměruje na cesky-tenis.cz/druzstva/babytenis). Výsledky viz kap. 3.

### Turnaje (https://cltk.cz/cs/tenisova-skolicka/turnaje/)
„Tenisová škola v I.ČLTK Praha pořádá každoročně turnaje pro hráče do 9 let.“
- **Minitenis** – do 7 let (dle ročníku); přihlášky přes IS ČTS (kategorie minitenis)
- **Střední kurt** – do 8 let; přihlášky přes IS ČTS (kategorie babytenis)
- **Babytenis** – do 9 let; přihlášky přes IS ČTS (kategorie babytenis)
(obrázky: 3 malé screenshoty velikostí kurtů z r. 2016)

### Letní kempy 2026 (https://cltk.cz/cs/tenisova-skolicka/letni-kempy/)
Termíny:
1. 29. června – 3. července 2026
2. 6. – 10. července 2026
3. 27. – 31. července 2026
4. 3. – 7. srpna 2026
5. 10. – 14. srpna 2026
6. 24. – 28. srpna 2026

- Pro děti **od cca 4 do 9 let** (minitenis, střední kurt, babytenis), vedou profesionální trenéři I. ČLTK Praha.
- Podmínka: minimálně jednoroční pravidelná tenisová příprava (úplní začátečníci po předchozí domluvě).
- **Varianta A celodenní** – 2 fáze tenisu + 2 fáze kondiční přípravy, pro děti cca 5–9 let (výkonnostní i rekreační), skupiny zpravidla po 3 až 4, **8:30 – 16:30**.
- **Varianta B dopolední** – 1 fáze tenisu, pro děti kolem 5 let, skupinový trénink, **8:30 – 13:30**.
- **Ceny (oběd a pitný režim v ceně), pětidenní:** A … **6.000,- Kč** pro hráče I. ČLTK Praha / **8.500,-** pro ostatní; B … **5.000,- Kč** / **7.500,-**.
- S sebou: raketa, sportovní oblečení, plavky a ručník, obuv na antuku/pevný povrch, láhev s nápojem, svačinka.
- Platba až po potvrzení e-mailem, převodem na účet 312451935/0300, VS = rodné číslo dítěte, zpráva: jméno + číslo termínu.
- Přihláška: Google formulář https://forms.gle/JvMQNKJJmoBareVa7 ; kontakt Mgr. Jan Pecha, Ph.D., 721 663 118, pecha@cltk.cz; adresa I. ČLTK Praha, Ostrov Štvanice 38, Praha.
- Fotogalerie kempů 2026: https://cltk.dphoto.com/album/o54c6s0a ; video YouTube „I. ČLTK Praha Video Gallery“ http://www.youtube.com/watch?v=H-D4mO6qFx8

---

## 10. Stažené soubory
- `assets/foto/treneri/` – 15 originálních portrétů 3543×4724 px (1,1–5,0 MB/ks – pro web nutno zmenšit): petr-vanicek, jiri-hrebec, ivo-minar, milan-trneny, jan-vacek, daniel-vanek, jaroslav-jandus, antonin-stepanek, lubomir-stych, magdalena-zemanova, pavel-janda, richard-pavluv, jan-pecha, andrea-vasickova, adela-vasickova (.jpg). Originály jsou na files.cltk.cz po odstranění segmentu `/w130/` z URL.
- `podklady/data/treneri.json` – strukturovaný seznam trenérů.
- V `_raw/zavodni/`: skupinové foto TŠ MV (`sig-ts-mv.jpg`, 2618×1732), foto týmu starších žáků MČR 2026 (`tym-starsich-zaku.jpg`, 1127×846, tým v bílých bundách Mizuno s erbem, s trofejí), plakát PO 2026 (`po2026-clanek.png`), PDF pavouky PO 2026, PDF ČTS (SCM/SVT/TSM/reprezentace), JSON výsledků soutěží (`cztenis/souteze_detail.json`, `cztenis/rank_club.json`).

---

## 11. Nápady pro design (z obsahu, který nikdo jiný nemá)
1. **„Pyramida Štvanice“** – vizuální cesta hráče: Tenisová škola MV (3–9 let) → TSM (10–14) → SVT (15–18) → SCM (15–21) → Extraliga / WTA / ATP. Klub je jeden ze 3 v ČR s kompletní pyramidou ČTS; u každého stupně trenéři (fotky jsou jednotné) a počet hráčů (TSM 14+2, SVT 16+2, SCM 9+2).
2. **„4 z TOP 10“** – číslo jako typografický prvek: 4 z 10 nejlepších Češek dle žebříčku ČTS (léto 2026) hrají za Štvanici (Muchová 1., Vondroušová 6., Bartůňková 9., Viďmanova 10.). Dále 26 reprezentantů ČR 2026, 121 hráčů v žebříčcích ČTS.
3. **Živý „světový“ ticker výsledků** (feed Resultina/scorepresso) – „Štvanice právě hraje: New York, Plovdiv, Maria-Lanzendorf…“ – mapa/pás měst, kde štvaničtí hráči právě hrají.
4. **Medailová stěna družstev** – sezóna 2026: babytenis MČR 2. místo, starší žactvo bronz, mladší žactvo 3., dorost 3.; 2025: dorost vicemistr, starší žactvo bronz. Plus týmy A–E Tenisové školy v pražských ligách.
5. **Extraliga jako „klubový zápas roku“** – soupiska 2025 s hvězdami (Muchová, Vondroušová, Bartůňková, Viďmanova, Martincová, Forejtek, Paulson…), kapitáni Vaníček a Minář, prosincové halové finále.
6. **Galerie trenérů jako „síň mistrů“** – trenéři s kariérními čísly: Hřebec (top 25, Galeův pohár 1970, finále DC 1975), Minář (ATP 62, vítěz Davis Cupu), Vacek (ATP 61, titul Brasil Open 2001, 4. kolo Wimbledonu 2002), Vaníček (sparingpartner Martiny Hingis). Čísla jako velká typografie, portréty v jednotném stylu.
7. **Tenisová školička jako samostatný „mini-web“** s vlastním monogramem MV, kalendářem (harmonogram, prázdniny), ceníkem jako přehlednou kartou a přihláškou na kempy (6 termínů, varianty A/B, cena člen/nečlen).
8. **Prague Open týden** – odpočet / výsledková tabule vítězů 2026 (Kumstát, Kovačková, Paulson/Vliegen, Kovačková/Kovačková), prize money, „26 let turnaje“, vstup zdarma.

## 12. Nejasnosti, NEOVĚŘENO a mezery
- Počet **registrovaných hráčů** klubu cztenis.cz neuvádí; mám jen 121 unikátních hráčů v žebříčcích ČTS (léto 2026; bez babytenisu/minitenisu).
- Klub na webu neuvádí **historický počet titulů v Extralize** ani historii družstev – v přečtených zdrojích nenalezeno.
- Extraliga 2025: celkové pořadí I.ČLTK (pravděpodobně 3.–4., protože skončil 2. ve skupině) **NEOVĚŘENO**; 2024 „3-4.“ je z webu tenisovaextraliga.cz, který má u tabulky matoucí nadpis (2023/2024).
- „Soutěže smíšených družstev ČTS“ na cztenis.cz Extraligu neobsahují (je vedena na tenisovaextraliga.cz); 1. ani 2. liga dospělých I.ČLTK neobsahuje (klub tam nemá tým).
- Dorost: v Pražské lize dorostu 2025/2026 I.ČLTK ve výsledcích vyhledávání není – zda hraje jen MČR, **NEOVĚŘENO**.
- MČR babytenis 2025 – I.ČLTK ve výsledcích vyhledávání není (**NEOVĚŘENO**, zda se neúčastnil).
- Význam zkratky „VK“ v seznamech SCM/SVT/TSM **NEOVĚŘENO**; zda TŠ MV je formálně „PS – přípravné středisko“ ČTS **NEOVĚŘENO**.
- Správný tvar jména „Richard Pavluv / Pavlův“ – web a soubor se liší. Překlepy na webu: „Vodroušová“ (2×), „Anrew“ Paulson (soupiska extraligy).
- Rankingy ve feedu scorepresso (např. Muchová WTA 7, Bartůňková WTA 38) nejsou ověřeny vůči oficiálním žebříčkům WTA/ATP.
- Stránka pragueopen.net/history je jen obrázková („2001 - 2025“) – historii vítězů nečetla (patří do podkladů o Prague Open/historii).
- Případ M. Vondroušové (trest ITIA, 2026) – detail neznám, klub ji veřejně podporuje; tenisová škola nese její jméno → citlivé.
