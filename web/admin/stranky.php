<?php
/* Stránky – editovatelné textové bloky podstránek (cltk_bloky: stránka + klíč).
   Šablony čtou bloky natvrdo podle klíče (blok('areal', 'uvod')), proto se klíče
   NEPŘEJMENOVÁVAJÍ a známé bloky jde jen skrýt, ne smazat; nové bloky přidat lze.
     stranky.php                 – přehled stránek
     stranky.php?stranka=areal   – bloky jedné stránky
     stranky.php?id=12           – úprava bloku (text z editoru, obrázek do textu)
   Text z editoru: na serveru vždy html_ocistit() (každá povolená značka holá),
   do databáze v podobě nezávislé na umístění webu (bez /cltkv2/), viz stranky_html_k_ulozeni().
   Obrázek do textu: POST action=obrazek_do_textu (fetch z admin-obsah.js) → JSON. */
require __DIR__ . '/inc/layout.php';
$user = require_login();

/* ---------- Pomůcky modulů obsahu ----------
   Stejný blok je v modulech Trenéři, Ceníky, Tenisová škola, Areál a služby, Historie,
   Revue, Vedení, CTC a Stránky. Když je jednou převezme jádro (admin/inc/ui.php),
   použijí se jeho verze (function_exists). */
if (!function_exists('obsah_prosty')) {
    /** Prostý text z formuláře: zahodí vše, co vypadá jako HTML značka (<script>…</script>,
     *  <img onerror=…>, komentář). Na webu se text stejně escapuje přes e() – tohle je druhá
     *  pojistka pro případ, že by ho někde někdo vypsal napřímo. „děti < 10 let“ zůstane. */
    function obsah_prosty(string $s): string {
        $s = (string)preg_replace('~<\s*(script|style|iframe|object|embed|svg|math|template)\b[^>]*>.*?<\s*/\s*\1\s*>~is', '', $s);
        $s = (string)preg_replace('~<!--.*?(?:-->|$)~s', '', $s);
        $s = (string)preg_replace('~</?[a-z!?][^<>]*>~i', '', $s, -1, $pocet);
        if ($pocet > 0) $s = (string)preg_replace('/[ \t]{2,}/', ' ', $s);    // mezery po odstraněných značkách
        return trim($s);
    }
}
if (!function_exists('obsah_get')) {
    /** Parametr z adresy (GET), případně z POST: vždy řetězec (pole v adrese „?typ[]=x“ = výchozí hodnota). */
    function obsah_get(string $klic, string $vychozi = '', bool $iPost = false): string {
        $v = $_GET[$klic] ?? ($iPost ? ($_POST[$klic] ?? null) : null);
        return is_scalar($v) ? trim((string)$v) : $vychozi;
    }
}
if (!function_exists('obsah_pole')) {
    /** Prostý text z POST (vstup + obsah_prosty), zkrácený na $max znaků (0 = bez limitu). */
    function obsah_pole(string $klic, int $max = 255): string {
        $v = obsah_prosty(vstup($klic, 0));
        return $max > 0 ? mb_substr($v, 0, $max) : $v;
    }
}
if (!function_exists('obsah_inline')) {
    /** Krátký text, který smí mít <em>, <strong> a <br> (nadpis bloku, čin osobnosti…).
     *  Povolené značky se přepíšou na holé (bez atributů), ostatní zmizí. Vypisuje se html_inline(). */
    function obsah_inline(string $s): string {
        $s = (string)preg_replace('~<\s*(script|style|iframe|object|embed|svg|math|template)\b[^>]*>.*?<\s*/\s*\1\s*>~is', '', $s);
        $s = (string)preg_replace('~<!--.*?(?:-->|$)~s', '', $s);
        $s = (string)preg_replace_callback('~<\s*(/?)\s*(em|strong|b|i|br)\b[^<>]*>~i', static function (array $m): string {
            $z = strtolower($m[2]);
            if ($z === 'br') return '<br>';
            $z = $z === 'b' ? 'strong' : ($z === 'i' ? 'em' : $z);
            return '<' . $m[1] . $z . '>';
        }, $s);
        $s = (string)preg_replace('~<(?!/?(?:em|strong)>|br>)/?[a-z!?][^<>]*>~i', '', $s);
        return trim($s);
    }
}
if (!function_exists('obsah_odkaz')) {
    /** Odkaz z formuláře: '' = bez odkazu, false = nepovolená adresa (javascript: …),
     *  jinak adresa k uložení (doplní https://, stránka webu zůstane „clenstvi.php#prihlaska“). */
    function obsah_odkaz(string $u): string|false {
        $u = trim($u);
        if ($u === '') return '';
        $holy = (string)preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($u, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (preg_match('~^(javascript|data|vbscript|file):~i', $holy) || preg_match('~[<>"\']~', $u)) return false;
        if (preg_match('~^/[a-z0-9][a-z0-9\-_/]*\.php([?#].*)?$~i', $u)) $u = ltrim($u, '/');
        $n = normalizuj_url($u);
        return bezpecny_odkaz($n) !== '' ? $n : false;
    }
}
if (!function_exists('obsah_fokus')) {
    /** Ohnisko fotky „x% y%“ (object-position). Prázdné = výchozí, false = neplatné. */
    function obsah_fokus(string $f, string $vychozi = '50% 50%'): string|false {
        $f = trim((string)preg_replace('/\s+/', ' ', $f));
        if ($f === '') return $vychozi;
        // nejvýš dvě desetinná místa – sloupec fokus má VARCHAR(20)
        if (!preg_match('/^(\d{1,3}(?:\.\d{1,2})?)%\s(\d{1,3}(?:\.\d{1,2})?)%$/', $f, $m) || (float)$m[1] > 100 || (float)$m[2] > 100) return false;
        return $f;
    }
}
if (!function_exists('obsah_smazat_nepouzite')) {
    /** Smaže soubory z uploads/, na které už žádný řádek neodkazuje ($kde = [['cltk_treneri', 'foto'], …]).
     *  Volat AŽ po úspěšném zápisu do databáze (past z Liberce). Jednu fotku můžou sdílet dva
     *  řádky (trenér ve dvou týmech) – smaže se, až když ji nepoužívá nikdo. */
    function obsah_smazat_nepouzite(array $soubory, array $kde): void {
        foreach (array_unique(array_filter(array_map('strval', $soubory), static fn($s) => $s !== '')) as $s) {
            foreach ($kde as [$tabulka, $sloupec]) {
                if (!preg_match('/^[a-z_]+$/', (string)$sloupec)) continue 2;
                if ((int)val('SELECT COUNT(*) FROM ' . cltk_tabulka((string)$tabulka) . ' WHERE ' . $sloupec . ' = ?', [$s]) > 0) continue 2;
            }
            delete_upload($s);
        }
    }
}
if (!function_exists('obsah_chyba')) {
    /** Nápověda pod polem doplněná o chybovou hlášku (vrací HTML; $hint escapujte sami). */
    function obsah_chyba(array $chyby, string $pole, string $hint = ''): string {
        $ch = isset($chyby[$pole]) ? '<span class="pole-chyba">' . e($chyby[$pole]) . '</span>' : '';
        return $ch . ($ch !== '' && $hint !== '' ? ' ' : '') . $hint;
    }
}
if (!function_exists('obsah_chyby_box')) {
    /** Souhrn chyb nad formulářem (po neúspěšném uložení se stránka vypíše znovu s vyplněnými poli). */
    function obsah_chyby_box(array $chyby, bool $bylSoubor = false): string {
        if (!$chyby) return '';
        $t = implode(' ', array_unique(array_map('strval', $chyby)));
        if ($bylSoubor) $t .= ' Vybraný soubor se neuložil – vyberte ho prosím znovu.';
        return '<div class="flash err" role="alert"><b>Neuloženo.</b> ' . e($t) . '</div>';
    }
}
if (!function_exists('obsah_byl_soubor')) {
    /** Poslal formulář nějaký soubor? (po chybě je ho potřeba vybrat znovu) */
    function obsah_byl_soubor(): bool {
        foreach ($_FILES as $f) {
            if (is_array($f) && (int)($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) return true;
        }
        return false;
    }
}
if (!function_exists('obsah_assets')) {
    /** Styl a skript modulů obsahu (vypsat hned za admin_head()). */
    function obsah_assets(): string {
        return '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-obsah.css')) . '">'
             . '<script src="' . e(BASE_PATH . verze('admin/assets/admin-obsah.js')) . '" defer></script>';
    }
}
if (!function_exists('obsah_nahled_foto')) {
    /** Miniatura do tabulky (nebo zástupný rámeček). */
    function obsah_nahled_foto(?string $rel, string $trida = 'thumb-sm', string $fokus = '', string $prazdne = 'bez fotky'): string {
        $rel = (string)$rel;
        if ($rel !== '' && is_file(UPLOAD_DIR . '/' . $rel)) {
            return '<img class="' . e($trida) . '" src="' . e(upload_url($rel)) . '" alt="" loading="lazy"'
                 . ($fokus !== '' ? ' style="object-position:' . e($fokus) . '"' : '') . '>';
        }
        return '<span class="thumb-ph">' . e($rel !== '' ? 'chybí soubor' : $prazdne) . '</span>';
    }
}
/* ---------- konec pomůcek ---------- */

/** Stránky webu: klíč (= soubor bez .php) => [název, skupina]. */
const STRANKY = [
    'index'                  => ['Úvodní stránka', 'Úvodní stránka'],
    'klub'                   => ['Klub', 'Klub'],
    'clenstvi'               => ['Členství', 'Klub'],
    'historie'               => ['Historie', 'Klub'],
    'vedeni'                 => ['Vedení', 'Klub'],
    'ctc'                    => ['CTC – Centenary Tennis Clubs', 'Klub'],
    'revue'                  => ['Revue a newslettery', 'Klub'],
    'areal'                  => ['Areál a služby', 'Areál a služby'],
    'cenik-kurtu'            => ['Ceník kurtů', 'Areál a služby'],
    'privatni-treneri'       => ['Privátní trenéři', 'Areál a služby'],
    'body-solution'          => ['Body Solution', 'Areál a služby'],
    'sportovni-lekarstvi'    => ['Sportovní lékařství', 'Areál a služby'],
    'zavodni-tenis'          => ['Závodní tenis', 'Závodní tenis'],
    'zavodni-tenis-treneri'  => ['Závodní tenis – trenérský tým', 'Závodní tenis'],
    'tenisova-skola'         => ['Tenisová škola – informace', 'Tenisová škola'],
    'tenisova-skola-ceniky'  => ['Tenisová škola – ceníky', 'Tenisová škola'],
    'tenisova-skola-rozvrhy' => ['Tenisová škola – rozvrhy', 'Tenisová škola'],
    'tenisova-skola-treneri' => ['Tenisová škola – trenérský tým', 'Tenisová škola'],
    'letni-kempy'            => ['Letní kempy', 'Tenisová škola'],
    'restaurace'             => ['Restaurace', 'Restaurace a Prague Open'],
    'prague-open'            => ['Prague Open', 'Restaurace a Prague Open'],
    'kontakt'                => ['Kontakt', 'Ostatní'],
    'formulare'              => ['Společné texty formulářů', 'Ostatní'],
    '404'                    => ['Stránka nenalezena (404)', 'Ostatní'],
];

/** Známé bloky (šablony je čtou natvrdo) – „stránka|klíč“ => kde se na stránce ukazují. */
const STRANKY_BLOKY = [
    'index|uvod' => 'Úvod – deska s nadpisem a tlačítky Stát se členem / Ceník kurtů (štítek nad nadpisem = „Ostrov Štvanice, Praha 7“)',
    'index|aktuality' => 'Nadpis sekce Aktuality z klubu', 'index|vysledky' => 'Nadpis sekce výsledků hráčů',
    'index|kalendar' => 'Klubový kalendář – nadpis a perex', 'index|clenstvi' => 'Členství – nadpis a tlačítko nad videem',
    'index|sluzby' => 'Nadpis štítků Služby v areálu', 'index|historie' => 'Naše historie – tlačítko Kompletní historie',
    'index|partneri' => 'Nadpis sekce Partneři',
    'klub|o-klubu' => 'Klub uprostřed města', 'klub|jmena' => 'Pět jmen, jeden klub',
    'clenstvi|v-cene' => 'Co je v ceně členství', 'clenstvi|zvyhodneni' => 'Výhody členů v zimě', 'clenstvi|postup' => 'Jak se stát členem',
    'clenstvi|druhy' => 'Druhy členství podle stanov', 'clenstvi|prihlaska' => 'Nadpis a perex přihlášky do klubu',
    'historie|triptych' => 'Tři wimbledonské trávy – nadpis', 'historie|kronika' => 'Kronika – nadpis', 'historie|deska' => 'Zlatá deska – nadpis',
    'historie|deska-grandslam' => 'Záložka desky Grand Slam', 'historie|deska-cestni' => 'Záložka desky Čestní členové',
    'historie|deska-mistri' => 'Záložka desky Mistři republiky', 'historie|deska-oh' => 'Záložka desky Olympijské hry',
    'historie|deska-zasluzili' => 'Záložka desky Zasloužilí členové', 'historie|deska-prezidenti' => 'Záložka desky Prezidenti',
    'historie|osobnosti' => 'Osobnosti klubu – nadpis',
    'vedeni|dokumenty' => 'Stanovy a dokumenty', 'ctc|rodokmen' => 'Rodokmen stoletých klubů', 'revue|newslettery' => 'Newslettery – nadpis',
    'areal|kurty' => 'Kurty v létě a v zimě', 'areal|plan' => 'Plán areálu (fotka + odkaz na PDF)', 'areal|prijezd' => 'Jak se k nám dostanete',
    'cenik-kurtu|pravidla' => 'Pravidla rezervací',
    'zavodni-tenis|pyramida' => 'Od školy po extraligu', 'zavodni-tenis|cisla' => 'Sezóna v číslech', 'zavodni-tenis|extraliga' => 'Extraliga smíšených družstev',
    'tenisova-skola|kontakt' => 'Kontakt na vedoucího trenéra', 'tenisova-skola-rozvrhy|rozvrhy' => 'Zimní rozvrhy skupin',
    'letni-kempy|informace' => 'Co je dobré vědět', 'restaurace|informace' => 'Terasa a salónek', 'restaurace|kontakt' => 'Otevírací doba a kontakt',
    'prague-open|vitezove' => 'Vítězové ročníku', 'formulare|souhlas' => 'Text souhlasu se zpracováním údajů (perex) u všech přihlášek',
    /* bloky, které čtou šablony podstránek navíc (sady seedu 56-areal, 58-klub, 58-tenis) */
    'areal|casy' => 'Otevírací doby (časy jednotlivých služeb jsou u služeb v modulu Areál a služby)',
    'areal|uzavirky' => 'Plánované uzávěrky – co položka seznamu, to uzávěrka: tučně datum („16.–22. 8. 2026“), za ním důvod',
    'areal|sluzby' => 'Všechno na jednom ostrově – nadpis přehledu služeb', 'areal|letecky-leto' => 'Letecký snímek u plánu – léto',
    'areal|letecky-zima' => 'Letecký snímek u plánu – zima',
    'cenik-kurtu|ceniky' => 'Ceny za hodinu i za sezónu – nadpis ceníků léto / zima', 'cenik-kurtu|kalkulacka' => 'Kalkulačka ceny hodiny',
    'cenik-kurtu|doplnkove' => 'Doplňkové služby – nadpis', 'cenik-kurtu|rezervace' => 'Rezervace online – text a tlačítko',
    'body-solution|kontakt' => 'Kontakt a objednání', 'sportovni-lekarstvi|kontakt' => 'Kontakt a objednání',
    'privatni-treneri|treneri' => 'Trenéři pro rekreační hráče – nadpis', 'privatni-treneri|kontakt' => 'Ceny a kontakt',
    'prague-open|rocnik' => 'Ročník 2026 – údaje turnaje', 'prague-open|historie' => 'Historie turnaje',
    'kontakt|lide' => 'Lidé a kontakty – nadpis', 'kontakt|prijezd' => 'Adresa a příjezd', 'kontakt|fakturace' => 'Fakturační údaje a účty',
    'kontakt|dokumenty' => 'Dokumenty ke stažení – nadpis',
    'clenstvi|konfigurator' => 'Spočítejte si členství – nadpis konfigurátoru', 'clenstvi|clensky-list' => 'Členský list (náhled vedle konfigurátoru)',
    'clenstvi|dekujeme' => 'Poděkování po odeslání přihlášky',
    'ctc|clenstvi' => 'Členství v CTC', 'ctc|foto' => 'Fotka stránky CTC', 'ctc|utkani' => 'Mezinárodní utkání – nadpis', 'ctc|souteze' => 'Soutěže – nadpis',
    'historie|linka' => 'Wimbledonská linka – co položka seznamu, to bod: tučně rok, za ním „jméno · výsledek“ (vítěz = plný bod)',
    'historie|deska-prameny' => 'Prameny Zlaté desky (poznámka pod deskou)',
    'klub|cisla' => 'Klub v číslech – co položka seznamu, to číslo: tučně hodnota, za ním popis (prameny ve stejném pořadí v bloku Odkud čísla bereme)',
    'klub|prameny' => 'Odkud čísla bereme – číslovaný seznam pramenů', 'klub|ostrov' => 'Ostrov Štvanice – fotka',
    'klub|cesta' => 'Cesta na Štvanici – co položka seznamu, to místo: tučně rok', 'klub|rozcestnik' => 'Rozcestník Členství, historie a lidé klubu – nadpis',
    'revue|kiosek' => 'Kiosek Revue – nadpis', 'revue|pribehy' => 'Příběh v obálkách – co položka seznamu, to jméno',
    'vedeni|prezident' => 'Prezident klubu', 'vedeni|vybor' => 'Výkonný výbor – nadpis', 'vedeni|kancelar' => 'Kancelář klubu – nadpis',
    'vedeni|kontakty' => 'Další kontakty – nadpis', 'vedeni|prezidenti' => 'Prezidenti klubu od roku 1893 – nadpis',
    'tenisova-skola|informace' => 'Jak škola funguje', 'tenisova-skola|kategorie' => 'Kategorie podle věku', 'tenisova-skola|prihlaska' => 'Jak se do školy přihlásit',
    'tenisova-skola-rozvrhy|harmonogram' => 'Termíny sezóny – nadpis harmonogramu',
    'letni-kempy|terminy' => 'Termíny kempů – nadpis', 'letni-kempy|ceny' => 'Varianty a ceny', 'letni-kempy|prihlaska' => 'Přihláška a kontakt',
    'zavodni-tenis|zebricky' => 'Žebříček ČTS – nadpisy „Ženy“ / „Muži“ a pod nimi seznam „tučně pořadí, jméno“',
    'zavodni-tenis|reprezentanti' => 'Reprezentanti ČR – seznam jmen', 'zavodni-tenis|hraci' => 'Naši hráči – nadpis sekce karet hráčů',
    'zavodni-tenis|extraliga-rocniky' => 'Ročníky extraligy – co položka seznamu, to ročník: tučně rok, za ním výsledek',
    'zavodni-tenis|soupiska' => 'Soupiska extraligy a týmová fotka', 'zavodni-tenis|druzstva' => 'Družstva mládeže – medaile z mistrovství republiky',
];

/** Bloky ÚVODNÍ STRÁNKY: index.php je čte natvrdo, pořadí sekcí i jejich zobrazení určuje šablona.
 *  Proto u nich nejsou šipky ani „Skrýt“ a formulář ukáže jen pole, která sekce opravdu používá:
 *  pole => [popisek, výchozí text (když pole zůstane prázdné), nápověda]. Ostatní pole bloku se nemění. */
const STRANKY_UVOD_POLE = [
    'index|uvod' => [
        'stitek'  => ['Štítek nad galerií vlevo', '', 'Malý text verzálkami nad fotkami, např. „Ostrov Štvanice, Praha 7“. Prázdné = bez štítku.'],
        'nadpis'  => ['Nadpis na desce', 'Tenis na ostrově uprostřed Prahy od roku <em>1893</em>.', ''],
        'perex'   => ['Krátký text pod nadpisem', '', 'Nepovinný. V PDF klienta tu žádný text není.'],
        'odkaz'   => ['Navy tlačítko na desce', 'Stát se členem|clenstvi.php', 'Prázdný text tlačítka = tlačítko se neukáže.'],
        'odkaz2'  => ['Podtržený odkaz vedle tlačítka', 'Ceník kurtů|cenik-kurtu.php', 'Prázdný text = odkaz se neukáže.'],
    ],
    'index|aktuality' => ['stitek' => ['Nadpis sekce aktualit (zlaté verzálky)', 'Aktuality z klubu', 'Samotné aktuality se spravují v modulu Aktuality z klubu.']],
    'index|vysledky'  => ['stitek' => ['Nadpis pásu výsledků (zlaté verzálky)', 'Aktuální výsledky našich hráčů', 'Samotné výsledky se spravují v modulu Výsledky hráčů.']],
    'index|kalendar'  => [
        'stitek'  => ['Štítek nad nadpisem kalendáře', 'Klubový kalendář', ''],
        'nadpis'  => ['Nadpis kalendáře', 'Na Štvanici se <em>potkáváme</em>.', ''],
        'perex'   => ['Popisek nad seznamem akcí', 'Klubový rok – vyberte událost', 'Akce samotné se spravují v modulu Kalendář akcí.'],
    ],
    'index|clenstvi'  => [
        'nadpis'  => ['Nadpis nad videem', 'Členem se může stát <em>každý</em>.', ''],
        'odkaz'   => ['Tlačítko vedle nadpisu', 'Stát se členem|clenstvi.php', 'Prázdné = výchozí tlačítko Stát se členem. Video se mění v Textech a údajích.'],
    ],
    'index|sluzby'    => ['stitek' => ['Nadpis štítků pod videem', 'Služby v areálu', 'Štítky samotné se zapínají u služeb v modulu Areál a služby.']],
    'index|historie'  => [
        'stitek'  => ['Nadpis sekce historie', 'Naše historie', 'Tři fotky s roky se spravují v modulu Historie → Tři wimbledonské trávy.'],
        'odkaz'   => ['Tlačítko pod fotkami', 'Kompletní historie|historie.php', 'Prázdné = výchozí tlačítko Kompletní historie.'],
    ],
    'index|partneri'  => ['stitek' => ['Nadpis sekce partnerů', 'Partneři', 'Loga se spravují v modulu Partneři.']],
];

/** Mapa úvodní stránky: co se kde mění (sekce v pořadí na webu → modul / blok). */
const STRANKY_UVOD_MAPA = [
    ['Informační lišta pod menu', 'oznameni.php', 'Informační lišta', ''],
    ['Úvod – deska s nadpisem a tlačítky', '', '', 'uvod'],
    ['Úvod – galerie fotek se štítovým přechodem', 'galerie.php', 'Úvodní galerie', ''],
    ['Aktuality z klubu', 'aktuality.php', 'Aktuality z klubu', 'aktuality'],
    ['Aktuální výsledky našich hráčů', 'vysledky.php', 'Výsledky hráčů', 'vysledky'],
    ['Klubový kalendář a přihlášky k akcím', 'akce.php', 'Kalendář akcí', 'kalendar'],
    ['Členství – nadpis a tlačítko', '', '', 'clenstvi'],
    ['Video členství', 'nastaveni.php#sk-uvod', 'Texty a údaje → video', ''],
    ['Služby v areálu – štítky', 'sluzby.php', 'Areál a služby', 'sluzby'],
    ['Naše historie – tři fotky s roky', 'historie.php?cast=triptych', 'Historie → Tři wimbledonské trávy', 'historie'],
    ['Partneři – loga', 'partneri.php', 'Partneři', 'partneri'],
    ['Patička – adresa, kontakty, odkazy', 'nastaveni.php', 'Texty a údaje', ''],
];

/** Pole bloku úvodní stránky, která šablona používá (null = obecný blok). */
function stranky_pevna_pole(string $stranka, string $klic): ?array {
    return STRANKY_UVOD_POLE[$stranka . '|' . $klic] ?? null;
}

/** Bloky, které šablona čte podle předpony klíče (dají se přidávat i mazat). */
const STRANKY_PREDPONY = [
    'zavodni-tenis|pyramida-' => 'Stupeň pyramidy závodního tenisu (štítek = věk, nadpis = stupeň, perex = popis, text = odrážky „tučně popisek, hodnota“)',
    'zavodni-tenis|hrac-'     => 'Karta hráče / hráčky v sekci Naši hráči (štítek = žebříček, perex = v klubu od…, text = odrážky úspěchů, fotka 4 : 5)',
];
/** Klíče, které má každá stránka (hlavička). */
const STRANKY_UVOD = 'Hlavička stránky – štítek, nadpis, perex, případně fotka a tlačítka';

/** Je blok známý (šablona ho čte natvrdo)? Takový se nesmí smazat ani přejmenovat. */
function stranky_znamy(string $stranka, string $klic): bool {
    if ($klic === 'uvod' && $stranka !== 'formulare') return isset(STRANKY[$stranka]);
    return isset(STRANKY_BLOKY[$stranka . '|' . $klic]);
}
function stranky_popis(string $stranka, string $klic): string {
    if (isset(STRANKY_BLOKY[$stranka . '|' . $klic])) return STRANKY_BLOKY[$stranka . '|' . $klic];
    if ($klic === 'uvod' && $stranka !== 'formulare') return STRANKY_UVOD;
    foreach (STRANKY_PREDPONY as $predpona => $popis) {
        if (str_starts_with($stranka . '|' . $klic, $predpona)) return $popis;
    }
    return 'Vlastní blok (šablona ho zobrazí, jen když ho čte)';
}
function stranky_url(string $stranka): string {
    if ($stranka === 'formulare') return '';
    return url($stranka . '.php');
}

/**
 * Text z editoru k uložení: html_ocistit() (sestaví HTML z holých povolených značek)
 * a pak adresy webu bez BASE_PATH. html_ocistit() vrací odkazy na stránky a obrázky
 * s cestou webu („/cltkv2/clenstvi.php“, „/cltkv2/uploads/…“) – po přestěhování
 * na cltk.cz by takové odkazy vedly do prázdna. V databázi proto zůstane
 * „clenstvi.php“ a „uploads/…“ a cestu doplní html_ocistit() až při výpisu.
 */
function stranky_html_k_ulozeni(string $html): string {
    return html_k_ulozeni($html);        // jádro (inc/html.php)
}

/** Obrázky vložené do textu (jen z uploads/bloky/text/) – relativní cesty v uploads/. */
function stranky_obrazky_textu(string $html): array {
    preg_match_all('~<img\b[^>]*\ssrc="([^"]+)"~i', $html, $m);
    $v = [];
    foreach ($m[1] as $src) {
        $src = html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('~(?:^|/)uploads/(bloky/text/[a-z0-9\-_.]+)$~i', $src, $x) && !str_contains($x[1], '..')) $v[] = $x[1];
    }
    return array_values(array_unique($v));
}

/** Smaže obrázky z textu, které už žádný blok (ani popis akce) nepoužívá. Volat po zápisu do DB. */
function stranky_smazat_obrazky_textu(array $soubory): void {
    foreach ($soubory as $s) {
        $vzor = '%' . $s . '%';
        $pouzito = (int)val('SELECT COUNT(*) FROM cltk_bloky WHERE text LIKE ?', [$vzor]);
        try { $pouzito += (int)val('SELECT COUNT(*) FROM cltk_akce WHERE popis LIKE ?', [$vzor]); } catch (Throwable $e) { $pouzito++; }
        if ($pouzito === 0) delete_upload($s);
    }
}

/* ---------- obrázek do textu (fetch z editoru, odpověď JSON) ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && chce_json() && !$_POST && !$_FILES && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    // příliš velký soubor: PHP zahodí celý požadavek i s tokenem
    odpoved_json(['ok' => false, 'chyba' => 'Obrázek je příliš velký – server přijme najednou nejvýš ' . ini_get('post_max_size') . 'B. Zmenšete ho prosím.'], 413);
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'obrazek_do_textu') {
    $t = $_POST['_token'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        odpoved_json(['ok' => false, 'chyba' => 'Formulář vypršel – obnovte prosím stránku (rozepsaný text si předtím zkopírujte).'], 400);
    }
    $soubor = $_FILES['obrazek'] ?? null;
    if (!is_array($soubor)) odpoved_json(['ok' => false, 'chyba' => 'Nedorazil žádný soubor – možná je příliš velký (server přijme nejvýš ' . ini_get('upload_max_filesize') . 'B).'], 400);
    $jmeno = pathinfo(basename(str_replace('\\', '/', (string)($soubor['name'] ?? ''))), PATHINFO_FILENAME);
    $up = upload_image($soubor, 'bloky/text', 1600, 1600, $jmeno !== '' ? 'text-' . $jmeno : '');
    if (!$up['ok']) odpoved_json(['ok' => false, 'chyba' => $up['error']], 400);
    odpoved_json(['ok' => true, 'url' => upload_url($up['file']), 'soubor' => 'uploads/' . $up['file'], 'w' => $up['w'], 'h' => $up['h']]);
}

$id = (int)($_GET['id'] ?? 0);
$b  = $id ? row('SELECT * FROM cltk_bloky WHERE id = ?', [$id]) : null;
if ($id && !$b) redirect('stranky.php', 'Blok nebyl nalezen – možná byl mezitím smazán.', 'err');
$stranka = $b ? (string)$b['stranka'] : obsah_get('stranka', '', true);
$vDb = array_column(rows('SELECT DISTINCT stranka FROM cltk_bloky'), 'stranka');
if ($stranka !== '' && !isset(STRANKY[$stranka]) && !in_array($stranka, $vDb, true)) $stranka = '';
$rezim = $b ? 'uprava' : ($stranka !== '' ? 'stranka' : 'prehled');
$strankaUrl = 'stranky.php?stranka=' . rawurlencode($stranka);
$strankaNazev = STRANKY[$stranka][0] ?? $stranka;

$f = [
    'stitek' => (string)($b['stitek'] ?? ''), 'nadpis' => (string)($b['nadpis'] ?? ''), 'perex' => (string)($b['perex'] ?? ''),
    'text' => (string)($b['text'] ?? ''), 'foto' => (string)($b['foto'] ?? ''), 'foto_popisek' => (string)($b['foto_popisek'] ?? ''),
    'odkaz' => (string)($b['odkaz'] ?? ''), 'odkaz_text' => (string)($b['odkaz_text'] ?? ''), 'odkaz2' => (string)($b['odkaz2'] ?? ''),
    'odkaz2_text' => (string)($b['odkaz2_text'] ?? ''), 'doplni_klub' => (int)($b['doplni_klub'] ?? 0), 'visible' => (int)($b['visible'] ?? 1),
];
$chyby = [];
$bylSoubor = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek($b ? 'stranky.php?id=' . $id : ($stranka !== '' ? $strankaUrl : 'stranky.php'));
    $akce = vstup('action', 20);

    if ($akce === 'ulozit' && $b) {
        $bylSoubor = obsah_byl_soubor();
        $f = [
            'stitek' => obsah_pole('stitek', 120), 'nadpis' => mb_substr(obsah_inline(vstup('nadpis', 0)), 0, 255),
            'perex' => obsah_pole('perex', 6000), 'text' => stranky_html_k_ulozeni(vstup('text', 0)), 'foto' => $f['foto'],
            'foto_popisek' => obsah_pole('foto_popisek', 255), 'odkaz' => vstup('odkaz', 255), 'odkaz_text' => obsah_pole('odkaz_text', 80),
            'odkaz2' => vstup('odkaz2', 255), 'odkaz2_text' => obsah_pole('odkaz2_text', 80),
            'doplni_klub' => vstup_bool('doplni_klub'), 'visible' => vstup_bool('visible'),
        ];
        /* blok úvodní stránky: formulář posílá jen pole, která sekce používá – ostatní zůstanou,
           jak jsou, a blok je vždy zobrazený (skrytím by z úvodu tiše zmizela tlačítka a štítky) */
        $pevna = stranky_pevna_pole($stranka, (string)$b['klic']);
        if ($pevna !== null) {
            foreach (['stitek', 'nadpis', 'perex', 'text', 'foto_popisek', 'odkaz', 'odkaz_text', 'odkaz2', 'odkaz2_text'] as $pole) {
                $klicPole = in_array($pole, ['odkaz_text', 'odkaz2_text'], true) ? substr($pole, 0, -5) : $pole;
                if (!isset($pevna[$klicPole])) $f[$pole] = (string)($b[$pole] ?? '');
            }
            $f['doplni_klub'] = 0;
            $f['visible'] = 1;
        }
        if (strlen($f['text']) > 60000) $chyby['text'] = 'Text je příliš dlouhý (víc než 60 000 znaků) – rozdělte ho prosím do dvou bloků.';
        $o1 = obsah_odkaz($f['odkaz']);
        $o2 = obsah_odkaz($f['odkaz2']);
        if ($o1 === false) $chyby['odkaz'] = 'Odkaz musí začínat https://, mailto:, tel:, # nebo být stránka webu (např. clenstvi.php#prihlaska).';
        if ($o2 === false) $chyby['odkaz2'] = 'Odkaz musí začínat https://, mailto:, tel:, # nebo být stránka webu.';
        if ($o1 === '' && $f['odkaz_text'] !== '') $chyby['odkaz'] = 'Tlačítko „' . $f['odkaz_text'] . '“ nemá kam vést – doplňte odkaz, nebo smažte text tlačítka.';
        if ($o2 === '' && $f['odkaz2_text'] !== '') $chyby['odkaz2'] = 'Tlačítko „' . $f['odkaz2_text'] . '“ nemá kam vést – doplňte odkaz, nebo smažte text tlačítka.';
        if (!$chyby) {
            $foto = admin_obrazek('foto', $b['foto'] ?? '', 'bloky', 2400, 2400, $stranka . '-' . $b['klic']);
            if ($foto['chyba'] !== '') {
                $chyby['foto'] = $foto['chyba'];
            } else {
                db_update('cltk_bloky', $id, [
                    'stitek' => $f['stitek'], 'nadpis' => $f['nadpis'], 'perex' => $f['perex'], 'text' => $f['text'], 'foto' => $foto['soubor'],
                    'foto_popisek' => $f['foto_popisek'], 'odkaz' => (string)$o1, 'odkaz_text' => $f['odkaz_text'], 'odkaz2' => (string)$o2,
                    'odkaz2_text' => $f['odkaz2_text'], 'doplni_klub' => $f['doplni_klub'], 'visible' => $f['visible'], 'updated_at' => ted(),
                ]);
                // až po zápisu: stará fotka bloku a obrázky, které z textu zmizely
                obsah_smazat_nepouzite($foto['smazat'], [['cltk_bloky', 'foto']]);
                stranky_smazat_obrazky_textu(array_diff(stranky_obrazky_textu((string)$b['text']), stranky_obrazky_textu($f['text'])));
                redirect($strankaUrl . '#b' . $id, 'Blok „' . $b['klic'] . '“ stránky ' . $strankaNazev . ' je uložený.');
            }
        }
    } elseif ($akce === 'pridat') {
        $klic = vstup('klic', 60);
        $klicS = slugify($klic);
        if ($stranka === '' || !isset(STRANKY[$stranka])) redirect('stranky.php', 'Vyberte stránku, ke které blok patří.', 'err');
        if ($klic === '' || !preg_match('/^[a-z0-9][a-z0-9-]{0,59}$/', $klicS)) redirect($strankaUrl . '#novy', 'Klíč bloku smí mít jen malá písmena bez diakritiky, číslice a pomlčky.', 'err');
        if (row('SELECT id FROM cltk_bloky WHERE stranka = ? AND klic = ?', [$stranka, $klicS])) redirect($strankaUrl . '#novy', 'Blok „' . $klicS . '“ už na stránce je.', 'err');
        $noveId = db_insert('cltk_bloky', [
            'stranka' => $stranka, 'klic' => $klicS, 'stitek' => '', 'nadpis' => mb_substr(obsah_inline(vstup('nadpis', 0)), 0, 255),
            'perex' => '', 'text' => '', 'foto' => '', 'foto_popisek' => '', 'odkaz' => '', 'odkaz_text' => '', 'odkaz2' => '', 'odkaz2_text' => '',
            'doplni_klub' => 0, 'visible' => 1, 'poradi' => admin_dalsi_poradi('cltk_bloky', 'stranka', $stranka), 'updated_at' => ted(),
        ]);
        redirect('stranky.php?id=' . $noveId, 'Blok „' . $klicS . '“ je založený – vyplňte ho a uložte. Na webu se ukáže, až ho šablona stránky začne číst.');
    } else {
        $rid = (int)vstup_int('id');
        $x = $rid ? row('SELECT * FROM cltk_bloky WHERE id = ?', [$rid]) : null;
        if (!$x) redirect('stranky.php', 'Blok nebyl nalezen – možná byl mezitím smazán.', 'err');
        $zpet = 'stranky.php?stranka=' . rawurlencode((string)$x['stranka']);
        if ($akce === 'prepnout') {
            $novy = admin_prepni('cltk_bloky', $rid);
            redirect($zpet . '#b' . $rid, 'Blok „' . $x['klic'] . '“ je teď na webu ' . ($novy ? 'vidět.' : 'skrytý – šablona se k němu chová, jako by nebyl.'));
        }
        if ($akce === 'nahoru' || $akce === 'dolu') {
            admin_posun('cltk_bloky', $rid, $akce === 'nahoru' ? -1 : 1, 'stranka');
            redirect($zpet . '#b' . $rid, 'Pořadí bloků bylo změněno.');
        }
        if ($akce === 'smazat') {
            if (stranky_znamy((string)$x['stranka'], (string)$x['klic'])) {
                redirect($zpet . '#b' . $rid, 'Blok „' . $x['klic'] . '“ čte šablona stránky – smazat ho nejde, můžete ho skrýt.', 'warn');
            }
            q('DELETE FROM cltk_bloky WHERE id = ?', [$rid]);
            obsah_smazat_nepouzite([(string)$x['foto']], [['cltk_bloky', 'foto']]);
            stranky_smazat_obrazky_textu(stranky_obrazky_textu((string)$x['text']));
            redirect($zpet, 'Blok „' . $x['klic'] . '“ byl smazán.');
        }
        redirect($zpet, 'Neznámý požadavek.', 'err');
    }
}

/* ---------- výpis ---------- */
if ($rezim === 'prehled') {
    admin_head('Stránky', $user, [
        'podnadpis' => 'Texty podstránek, které nemají vlastní modul – hlavičky stránek, sekce, Body Solution, Sportovní lékařství, Privátní trenéři, Restaurace, Prague Open… Vyberte stránku.',
    ]);
} elseif ($rezim === 'stranka') {
    $u = stranky_url($stranka);
    admin_head($strankaNazev, $user, [
        'zpet' => ['stranky.php', 'Stránky'],
        'podnadpis' => $stranka === 'index'
            ? 'Nadpisy a tlačítka sekcí <a href="' . e($u) . '" target="_blank" rel="noopener">úvodní stránky ↗</a>. Pořadí sekcí i to, které se ukazují, určuje šablona podle PDF klienta – obsah sekcí (fotky, aktuality, výsledky, akce…) se mění ve vlastních modulech, viz mapa níže.'
            : 'Bloky stránky' . ($u !== '' ? ' <a href="' . e($u) . '" target="_blank" rel="noopener">' . e($stranka) . '.php ↗</a>' : ' (texty bez vlastní stránky)') . '. Pořadí bloků určuje šablona stránky; šipky jsou jen u bloků, které šablona vypisuje za sebou.',
    ]);
} else {
    $u = stranky_url($stranka);
    admin_head($strankaNazev . ' · ' . $b['klic'], $user, [
        'zpet' => [$strankaUrl, $strankaNazev], 'sirka' => 'uzka',
        'podnadpis' => e(stranky_popis($stranka, (string)$b['klic'])) . ($u !== '' ? ' · <a href="' . e($u) . '" target="_blank" rel="noopener">zobrazit stránku ↗</a>' : ''),
    ]);
}
echo obsah_assets();

if ($rezim === 'prehled'):
    $stat = [];
    foreach (rows('SELECT stranka, COUNT(*) AS n, SUM(CASE WHEN visible = 0 THEN 1 ELSE 0 END) AS skryte,
                          SUM(CASE WHEN doplni_klub = 1 THEN 1 ELSE 0 END) AS doplnit
                     FROM cltk_bloky GROUP BY stranka') as $r) $stat[$r['stranka']] = $r;
    $skupiny = [];
    foreach (STRANKY as $k => [$n, $sk]) $skupiny[$sk][$k] = $n;
    foreach ($vDb as $k) if (!isset(STRANKY[$k])) $skupiny['Ostatní'][$k] = $k;
?>
<section class="panel">
  <div class="tbl-wrap tbl-karty">
    <table>
      <thead><tr><th>Stránka</th><th>Bloky</th><th>Stav</th><th class="right">Akce</th></tr></thead>
      <tbody>
      <?php foreach ($skupiny as $sk => $polozky): ?>
        <tr class="tr-skupina"><td colspan="4"><?= e($sk) ?></td></tr>
        <?php foreach ($polozky as $k => $n): $s = $stat[$k] ?? ['n' => 0, 'skryte' => 0, 'doplnit' => 0]; $u = stranky_url((string)$k); ?>
          <tr>
            <td data-label="Stránka" class="td-nazev"><b><a href="stranky.php?stranka=<?= e(rawurlencode((string)$k)) ?>"><?= e($n) ?></a></b><small><?= $u !== '' ? e($k . '.php') : 'bez vlastní stránky' ?></small></td>
            <td data-label="Bloky" class="td-mala"><?= cislo((int)$s['n']) ?> <?= sklonuj((int)$s['n'], 'blok', 'bloky', 'bloků') ?></td>
            <td data-label="Stav">
              <?= (int)$s['doplnit'] ? badge('doplní klub · ' . (int)$s['doplnit'], 'warn') : '' ?>
              <?= (int)$s['skryte'] ? badge('skryté · ' . (int)$s['skryte'], 'off') : '' ?>
              <?= !(int)$s['doplnit'] && !(int)$s['skryte'] && (int)$s['n'] ? badge('Hotovo', 'ok') : '' ?>
            </td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <a class="btn btn-sm btn-primary" href="stranky.php?stranka=<?= e(rawurlencode((string)$k)) ?>">Upravit texty</a>
              <?php if ($u !== ''): ?><a class="btn btn-sm btn-ghost" href="<?= e($u) ?>" target="_blank" rel="noopener">Na webu <span aria-hidden="true">↗</span></a><?php endif; ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<p class="hint">Štítek „doplní klub“ = obsah zatím chybí a web u bloku ukazuje decentní poznámku. Když text doplníte, zaškrtávátko „Obsah zatím chybí“ u bloku zrušte.</p>

<?php elseif ($rezim === 'stranka'):
    $radky = rows('SELECT * FROM cltk_bloky WHERE stranka = ? ORDER BY poradi, id', [$stranka]);
    /* šipky jen u bloků, které šablona vypisuje za sebou (vlastní a „předponové“), ne u pevných */
    $posunovatelne = array_values(array_filter($radky, fn($r) => !stranky_znamy($stranka, (string)$r['klic'])));
    $prvniPosun = $posunovatelne ? (int)$posunovatelne[0]['id'] : 0;
    $posledniPosun = $posunovatelne ? (int)$posunovatelne[count($posunovatelne) - 1]['id'] : 0;
    $idPodleKlice = array_column($radky, 'id', 'klic');
?>
<?php if ($stranka === 'index'): ?>
<section class="panel" id="mapa">
  <div class="panel-head"><h2>Co se kde mění na úvodní stránce</h2><span class="hint">Sekce v pořadí, jak jdou na webu shora dolů.</span></div>
  <div class="tbl-wrap tbl-karty">
    <table>
      <thead><tr><th>Sekce na webu</th><th>Obsah sekce</th><th>Nadpis a tlačítka</th></tr></thead>
      <tbody>
      <?php foreach (STRANKY_UVOD_MAPA as [$sekce, $modulUrl, $modulNazev, $blokKlic]): ?>
        <tr>
          <td data-label="Sekce" class="td-nazev"><b><?= e($sekce) ?></b></td>
          <td data-label="Obsah"><?= $modulUrl !== '' ? '<a href="' . e($modulUrl) . '">' . e($modulNazev) . '</a>' : '<span class="uv-tlumene">–</span>' ?></td>
          <td data-label="Nadpis"><?= $blokKlic !== '' && isset($idPodleKlice[$blokKlic]) ? '<a href="stranky.php?id=' . (int)$idPodleKlice[$blokKlic] . '">blok ' . e($blokKlic) . '</a>' : '<span class="uv-tlumene">–</span>' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>
<section class="panel">
  <div class="panel-head"><h2>Bloky <small><?= cislo(count($radky)) ?></small></h2></div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Stránka zatím nemá žádný blok', 'Šablona stránky si chybějící blok nahradí výchozím textem. Blok můžete přidat níž.') ?>
  <?php else: ?>
  <div class="tbl-wrap tbl-karty">
    <table>
      <thead><tr><th>Blok</th><th>Obsah</th><th>Foto</th><th>Stav</th><th class="right">Akce</th></tr></thead>
      <tbody>
      <?php foreach ($radky as $i => $r): $rid = (int)$r['id']; $znamy = stranky_znamy($stranka, (string)$r['klic']);
            $pevny = stranky_pevna_pole($stranka, (string)$r['klic']) !== null; ?>
        <tr id="b<?= $rid ?>"<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
          <td data-label="Blok" class="td-nazev"><b><a href="stranky.php?id=<?= $rid ?>"><span class="klic"><?= e($r['klic']) ?></span></a></b><small><?= e(stranky_popis($stranka, (string)$r['klic'])) ?></small></td>
          <td data-label="Obsah" class="td-mala">
            <?php if ($r['stitek'] !== ''): ?><span class="stranky-skupina"><?= e($r['stitek']) ?></span><br><?php endif; ?>
            <?php if ($r['nadpis'] !== ''): ?><b><?= html_inline($r['nadpis']) ?></b><br><?php endif; ?>
            <?= e(uryvek($r['perex'] !== '' ? $r['perex'] : $r['text'], 110)) ?>
            <?php if ($r['stitek'] === '' && $r['nadpis'] === '' && $r['perex'] === '' && $r['text'] === ''): ?><i>prázdný</i><?php endif; ?>
          </td>
          <td data-label="Foto"><?= $r['foto'] !== '' ? obsah_nahled_foto($r['foto'], 'thumb-sm') : '–' ?></td>
          <td data-label="Stav"><?= stav_badge($r['visible']) ?> <?= (int)$r['doplni_klub'] ? badge('doplní klub', 'warn') : '' ?></td>
          <td data-label="Akce" class="right"><div class="akce-radku">
            <?php if (!$znamy): ?><?= tlacitka_poradi($rid, $rid === $prvniPosun, $rid === $posledniPosun, ['stranka' => $stranka]) ?><?php endif; ?>
            <a class="btn btn-sm btn-primary" href="stranky.php?id=<?= $rid ?>">Upravit</a>
            <?php if (!$pevny || !(int)$r['visible']): ?><?= tlacitko_prepnout($rid, $r['visible'], ['stranka' => $stranka]) ?><?php endif; ?>
            <?php if (!$znamy): ?><?= tlacitko_smazat($rid, 'Smazat blok „' . $r['klic'] . '“? Nejde to vrátit.', ['stranka' => $stranka]) ?><?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<?php if (isset(STRANKY[$stranka]) && $stranka !== 'index'): ?>
<section class="panel" id="novy">
  <div class="panel-head"><h2>Přidat blok</h2></div>
  <div class="panel-body">
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="pridat">
      <input type="hidden" name="stranka" value="<?= e($stranka) ?>">
      <?= pole_radek([
            pole_text('klic', 'Klíč bloku', '', ['required' => true, 'maxlength' => 60, 'placeholder' => 'např. otviraci-doba',
                'hint' => 'Malá písmena bez diakritiky a pomlčky. Klíč pak už neměňte.']),
            pole_text('nadpis', 'Nadpis', '', ['maxlength' => 255]),
          ]) ?>
      <p class="hint">Nový blok se na webu ukáže, až ho šablona stránky začne vypisovat (vlastní bloky vypisují stránky, které berou všechny bloky za sebou). Bloky, které šablona čte natvrdo, jdou jen skrýt, ne smazat.</p>
      <?= tlacitka_formulare('Přidat blok') ?>
    </form>
  </div>
</section>
<?php endif; ?>

<?php else: /* ---------- úprava bloku ---------- */ ?>
<?= obsah_chyby_box($chyby, $bylSoubor) ?>
<?php if ((int)$b['doplni_klub']): ?><div class="upozorneni">Obsah bloku zatím chybí – web u něj ukazuje štítek „doplní klub“. Až text doplníte, zrušte zaškrtnutí „Obsah zatím chybí“.</div><?php endif; ?>
<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <?php $pevna = stranky_pevna_pole($stranka, (string)$b['klic']);
      if ($pevna !== null):
          /* blok úvodní stránky – jen pole, která sekce používá, s popisky podle sekce */
          $vychozi = static fn(string $p): string => (string)($pevna[$p][1] ?? '');
          $napoveda = static function (string $p, string $navic = '') use ($pevna, $chyby): string {
              $t = e((string)($pevna[$p][2] ?? ''));
              $v = (string)($pevna[$p][1] ?? '');
              if ($v !== '' && !str_contains($v, '|')) $t .= ($t !== '' ? ' ' : '') . 'Prázdné = výchozí text „' . e($v) . '“.';
              return obsah_chyba($chyby, $p, trim($t . ' ' . $navic));
          };
          foreach (['stitek', 'nadpis', 'perex'] as $p) {
              if (!isset($pevna[$p])) continue;
              if ($p === 'perex') {
                  echo pole_textarea('perex', $pevna[$p][0], $f['perex'], ['rows' => 3, 'hint' => $napoveda('perex')]);
              } else {
                  echo pole_text($p, $pevna[$p][0], $f[$p], ['maxlength' => $p === 'stitek' ? 120 : 255, 'sirka' => 'cela',
                      'placeholder' => html_text($vychozi($p)),
                      'hint' => $napoveda($p, $p === 'nadpis' ? 'Zlatou kurzívou zvýrazníte slovo takto: &lt;em&gt;slovo&lt;/em&gt; (nebo slovo označte a klepněte na Zlatá kurzíva).' : '')]);
              }
          }
          foreach (['odkaz' => ['odkaz_text', 'f-o1t', 'f-o1'], 'odkaz2' => ['odkaz2_text', 'f-o2t', 'f-o2']] as $p => [$pText, $idText, $idOdkaz]) {
              if (!isset($pevna[$p])) continue;
              [$vText, $vOdkaz] = array_pad(explode('|', $vychozi($p), 2), 2, '');
              echo '<fieldset><legend>' . e($pevna[$p][0]) . '</legend><div class="form-mrizka">'
                 . pole_text($pText, 'Text', $f[$pText], ['maxlength' => 80, 'placeholder' => $vText, 'id' => $idText])
                 . pole_text($p, 'Kam vede', $f[$p], ['maxlength' => 255, 'placeholder' => $vOdkaz, 'id' => $idOdkaz, 'hint' => obsah_chyba($chyby, $p)])
                 . '</div><p class="hint">' . e((string)($pevna[$p][2] ?? '')) . ' Stránka webu se zapisuje jen názvem souboru (' . e($vOdkaz !== '' ? $vOdkaz : 'clenstvi.php') . '), odkaz ven celou adresou https://…</p></fieldset>';
          }
      ?>
      <p class="hint">Sekce se na úvodní stránce ukazuje vždy – její obsah se mění v modulu uvedeném v <a href="<?= e($strankaUrl) ?>#mapa">mapě úvodní stránky</a>.</p>
      <?= tlacitka_formulare('Uložit', $strankaUrl, 'Zpět bez uložení') ?>
      <?php else: ?>
      <?= pole_text('stitek', 'Štítek nad nadpisem', $f['stitek'], ['maxlength' => 120, 'placeholder' => 'Areál a služby', 'hint' => 'Malý zlatý text verzálkami.']) ?>
      <?= pole_text('nadpis', 'Nadpis', $f['nadpis'], ['maxlength' => 255, 'sirka' => 'cela',
            'hint' => 'Zlatou kurzívou zvýrazníte slovo takto: Tenis od roku &lt;em&gt;1893&lt;/em&gt;. Jiné značky se nepovolí.']) ?>
      <?= pole_textarea('perex', 'Perex', $f['perex'], ['rows' => 4, 'hint' => 'Krátký úvodní text pod nadpisem. Odstavce oddělte prázdným řádkem.']) ?>
      <div data-obrazky-url="stranky.php">
        <?= pole_editor('text', 'Text', $f['text'], ['hint' => obsah_chyba($chyby, 'text', 'Enter = nový odstavec. Ikonou obrázku vložíte fotku přímo do textu, ikonou řetízku z označeného textu uděláte odkaz. Vložený text ze schránky se vloží bez cizího formátování.')]) ?>
      </div>
      <?= pole_obrazek('foto', 'Fotka bloku', $f['foto'], ['hint' => obsah_chyba($chyby, 'foto', 'Nepovinná – použije ji jen šablona, která u bloku fotku ukazuje (např. plán areálu, Restaurace).' . ($f['foto'] !== '' ? ' Nová fotka nahradí stávající.' : ''))]) ?>
      <?= pole_text('foto_popisek', 'Popisek fotky', $f['foto_popisek'], ['maxlength' => 255]) ?>
      <fieldset>
        <legend>Tlačítka</legend>
        <div class="form-mrizka">
          <?= pole_text('odkaz_text', 'Hlavní tlačítko – text', $f['odkaz_text'], ['maxlength' => 80, 'placeholder' => 'Stát se členem', 'id' => 'f-o1t']) ?>
          <?= pole_text('odkaz', 'Hlavní tlačítko – odkaz', $f['odkaz'], ['maxlength' => 255, 'placeholder' => 'clenstvi.php nebo https://…', 'id' => 'f-o1', 'hint' => obsah_chyba($chyby, 'odkaz')]) ?>
          <?= pole_text('odkaz2_text', 'Vedlejší odkaz – text', $f['odkaz2_text'], ['maxlength' => 80, 'placeholder' => 'Ceník kurtů', 'id' => 'f-o2t']) ?>
          <?= pole_text('odkaz2', 'Vedlejší odkaz – adresa', $f['odkaz2'], ['maxlength' => 255, 'id' => 'f-o2', 'hint' => obsah_chyba($chyby, 'odkaz2')]) ?>
        </div>
        <p class="hint">Stránka webu se zapisuje jen názvem souboru (clenstvi.php, areal.php#bazen), odkazy ven celou adresou https://… – otevřou se v novém okně.</p>
      </fieldset>
      <?= pole_check('doplni_klub', 'Obsah zatím chybí (web ukáže štítek „doplní klub“)', (bool)$f['doplni_klub']) ?>
      <?= pole_check('visible', 'Zobrazit na webu', (bool)$f['visible'], ['hint' => 'Skrytý blok se na webu chová, jako by nebyl – šablona ukáže výchozí obsah nebo nic.']) ?>
      <?= tlacitka_formulare('Uložit blok', $strankaUrl, 'Zpět bez uložení') ?>
      <?php endif; ?>
    </form>
  </div>
</section>
<?php if (!stranky_znamy($stranka, (string)$b['klic'])): ?>
  <section class="panel panel--nebezpeci">
    <div class="panel-head"><h2>Smazat blok</h2></div>
    <div class="panel-body"><div class="btn-row">
      <?= tlacitko_smazat($id, 'Opravdu smazat blok „' . $b['klic'] . '“? Nejde to vrátit.', ['stranka' => $stranka], 'Smazat blok') ?>
    </div></div>
  </section>
<?php endif; ?>
<?php endif; ?>

<?php admin_foot(); ?>
