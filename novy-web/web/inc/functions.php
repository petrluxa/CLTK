<?php
/* Jádro webu I. ČLTK Praha – pomocné funkce.
   Stačí vložit tenhle soubor; načte i databázi, čištění HTML,
   práci se soubory a datové pomůcky pro stránky.
   Přehled funkcí pro stránky: inc/PRUVODCE-FRONT.md. */

require_once __DIR__ . '/db.php';

/* ==================================================================
   VÝSTUP DO HTML
   ================================================================== */

/** Escapování do HTML – používat VŠUDE, kde jde ven text z databáze nebo od uživatele. */
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Víceřádkový prostý text → odstavce (HTML nepovolí, typografii doplní). */
function paragraphs(?string $s): string {
    $s = trim(str_replace("\r\n", "\n", (string)$s));
    if ($s === '') return '';
    $casti = preg_split('/\n\s*\n/', $s);
    return implode('', array_map(fn($p) => '<p>' . nl2br(typo(trim($p)), false) . '</p>', $casti));
}

/** Text „co řádek, to položka“ → pole neprázdných řádků. */
function radky(?string $s): array {
    $vysledek = [];
    foreach (preg_split('/\R/', (string)$s) as $r) {
        $r = trim($r);
        if ($r !== '') $vysledek[] = $r;
    }
    return $vysledek;
}

/** Text „co řádek, to položka“ → <ul> (escapovaný, s typografií – nbsp, pomlčka, výpustka).
 *  Prázdný text = prázdný řetězec. */
function radky_seznam(?string $s, string $trida = ''): string {
    $r = radky($s);
    if (!$r) return '';
    return '<ul' . ($trida !== '' ? ' class="' . e($trida) . '"' : '') . '>'
         . implode('', array_map(fn($x) => '<li>' . typo($x) . '</li>', $r)) . '</ul>';
}

/** Bezpečné JSON → pole (vadný nebo prázdný JSON = []). */
function json_pole(?string $s): array {
    if ($s === null || trim($s) === '') return [];
    $d = json_decode($s, true);
    return is_array($d) ? $d : [];
}

/** Pole → JSON pro uložení do databáze (čitelná diakritika). */
function json_ulozit($data): string {
    return (string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/** Data pro JavaScript do <script type="application/json"> – nerozbije se o </script>. */
function json_do_stranky($data): string {
    return (string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/* ==================================================================
   TYPOGRAFIE A ČÍSLA
   ================================================================== */

/** Typografie na PROSTÉM textu (bez escapování): nezlomitelná mezera (U+00A0)
 *  po jednopísmenných předložkách a spojkách, po řadové římské číslici („I. ČLTK“)
 *  a mezi číslem a jednotkou; „...“ → „…“, spojovník mezi slovy („ATP - 1235“) → pomlčka,
 *  „5x mistr“ → „5× mistr“.
 *  Používají ji typo(), paragraphs(), html_inline() i html_ocistit(). */
function typo_text(?string $s): string {
    $t = (string)$s;
    if ($t === '') return '';
    $t = str_replace('...', '…', $t);
    $t = (string)preg_replace('/(?<=[\p{L}\p{N}.,;:)“"’])[ \t]+-[ \t]+(?=[\p{L}\p{N}(„"])/u', ' – ', $t);
    $t = (string)preg_replace('/(?<=\d)x(?=[ \t\x{00A0}])/u', '×', $t);
    $t = (string)preg_replace('/(?<=^|[\s(„“])([ksvzouaiKSVZOUAI])[ \t]+/u', "$1\u{00A0}", $t);
    $t = (string)preg_replace('/(?<=^|[\s(„“])([IVX]{1,4}\.)[ \t]+(?=\p{L})/u', "$1\u{00A0}", $t);
    $t = (string)preg_replace('/(\d)[ \t]+(Kč|km|m|min|h|hod|%|let|×|kurtů|kurty|osob|MB)(?=[\s.,;:)\/–]|$)/u', "$1\u{00A0}$2", $t);
    return $t;
}

/** Nezlomitelná mezera po jednopísmenných předložkách a spojkách a mezi číslem a jednotkou.
 *  Vstup je prostý text, výstup je už escapovaný HTML (s &nbsp;). */
function typo(?string $s): string {
    return str_replace("\u{00A0}", '&nbsp;', e(typo_text($s)));
}

/** 21000 → „21 000 Kč“ (nezlomitelné mezery, jako HTML). */
function cena_kc($castka, bool $html = true): string {
    if ($castka === null || $castka === '') return '';
    $t = number_format((float)$castka, 0, ',', "\u{00A0}") . "\u{00A0}Kč";
    return $html ? str_replace("\u{00A0}", '&nbsp;', e($t)) : $t;
}

/** Číslo po česku: 1 234 */
function cislo($n): string {
    return number_format((float)$n, 0, ',', "\u{00A0}");
}

/** Římská číslice (1–3999) – rejstřík galerie, čísla sekcí. */
function rimske(int $n): string {
    if ($n <= 0 || $n > 3999) return (string)$n;
    $mapa = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90,
             'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
    $v = '';
    foreach ($mapa as $z => $h) {
        while ($n >= $h) { $v .= $z; $n -= $h; }
    }
    return $v;
}

/** České skloňování po číslovce: 1 hráč, 2–4 hráči, 5+ hráčů. */
function sklonuj(int $pocet, string $jeden, string $nekolik, string $mnoho): string {
    if ($pocet === 1) return $jeden;
    return ($pocet >= 2 && $pocet <= 4) ? $nekolik : $mnoho;
}

/** Adresa z názvu, včetně české diakritiky. */
function slugify(string $s): string {
    $map = ['á'=>'a','ä'=>'a','č'=>'c','ď'=>'d','é'=>'e','ě'=>'e','ë'=>'e','í'=>'i','ľ'=>'l','ĺ'=>'l','ň'=>'n',
            'ó'=>'o','ö'=>'o','ô'=>'o','ř'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ů'=>'u','ü'=>'u','ý'=>'y','ž'=>'z'];
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = strtr($s, $map);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? $s : 'polozka';
}

/* ==================================================================
   DATUM
   ================================================================== */

const CZ_MESICE     = [1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'];
const CZ_MESICE_GEN = [1 => 'ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince'];
const CZ_DNY        = [1 => 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota', 'neděle'];

/** Dnešní datum RRRR-MM-DD. Lokálně jde pro zkoušení přepsat proměnnou CLTK_DNES. */
function dnes(): string {
    $d = (string)getenv('CLTK_DNES');
    if ($d !== '' && DB_DRIVER === 'sqlite' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
    return date('Y-m-d');
}

/** Teď pro sloupce DATETIME. */
function ted(): string {
    return date('Y-m-d H:i:s');
}

/** 24. 5. 2026 */
function cz_date(?string $d): string {
    if (!$d) return '';
    $t = strtotime($d);
    return $t ? date('j. n. Y', $t) : '';
}

/** 24. 5. (bez roku) */
function cz_date_kratce(?string $d): string {
    if (!$d) return '';
    $t = strtotime($d);
    return $t ? date('j. n.', $t) : '';
}

/** 27. září 2026 */
function cz_date_dlouze(?string $d): string {
    if (!$d) return '';
    $t = strtotime($d);
    return $t ? date('j. ', $t) . CZ_MESICE_GEN[(int)date('n', $t)] . date(' Y', $t) : '';
}

/** Rozsah: 8.–15. 2. 2026 · 29. 6. – 28. 8. 2026 · 23. 12. 2026 – 3. 1. 2027 */
function cz_range(?string $od, ?string $do, bool $sRokem = true): string {
    if (!$od) return '';
    if (!$do || $do === $od) return $sRokem ? cz_date($od) : cz_date_kratce($od);
    $a = strtotime($od);
    $b = strtotime($do);
    if (!$a || !$b) return cz_date($od);
    $rok = $sRokem ? date(' Y', $b) : '';
    if (date('Y', $a) === date('Y', $b)) {
        if (date('n', $a) === date('n', $b)) return date('j.', $a) . '–' . date('j. n.', $b) . $rok;
        return date('j. n.', $a) . ' – ' . date('j. n.', $b) . $rok;
    }
    return cz_date($od) . ' – ' . cz_date($do);
}

/** Číslo měsíce → „květen“. */
function cz_mesic(int $m): string {
    return CZ_MESICE[$m] ?? '';
}

/* Datum z formuláře na tvar pro databázi (RRRR-MM-DD).
   Moderní prohlížeče posílají z <input type="date"> rovnou správný tvar.
   Starší Safari ale nabídne obyčejné textové pole a člověk napíše
   „5.9.2026“ nebo „5. 9. 2026“. Vrací null pro prázdnou hodnotu
   a false, když datum nedává smysl. */
function normalizuj_datum(?string $vstup): string|null|false {
    $v = trim((string)$vstup);
    if ($v === '') return null;

    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $v, $m)) {
        [, $r, $me, $d] = $m;
    } elseif (preg_match('/^(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})$/u', $v, $m)) {
        [, $d, $me, $r] = $m;
    } else {
        return false;
    }
    if (!checkdate((int)$me, (int)$d, (int)$r)) return false;
    return sprintf('%04d-%02d-%02d', $r, $me, $d);
}

/* ==================================================================
   ADRESY – web běží v podsložce, nic nesmí mít natvrdo „/…“
   ================================================================== */

/** Odkaz na stránku webu: url('clenstvi.php') → /cltkv2/clenstvi.php. Bez argumentu kořen webu. */
function url(string $cesta = ''): string {
    if (preg_match('~^(https?:|mailto:|tel:|#)~i', $cesta)) return $cesta;
    return BASE_PATH . ltrim($cesta, '/');
}

/** Relativní cesta (od kořene webu) s otiskem času změny: assets/css/styl.css?v=1727… */
function verze(string $rel): string {
    $rel = ltrim($rel, '/');
    $cas = @filemtime(WEB_ROOT . '/' . $rel);
    return $rel . ($cas ? '?v=' . $cas : '');
}

/** Statický soubor z assets/ s otiskem času: asset('css/styl.css') i asset('assets/css/styl.css'). */
function asset(string $cesta): string {
    $cesta = ltrim($cesta, '/');
    if (!str_starts_with($cesta, 'assets/') && !str_starts_with($cesta, 'admin/')) {
        $cesta = 'assets/' . $cesta;
    }
    return BASE_PATH . verze($cesta);
}

/** Adresa nahraného souboru: upload_url('galerie/x.jpg') → /cltkv2/uploads/galerie/x.jpg. Prázdné = ''. */
function upload_url(?string $rel): string {
    $rel = ltrim((string)$rel, '/');
    if ($rel === '') return '';
    if (preg_match('~^https?://~i', $rel)) return $rel;
    return BASE_PATH . 'uploads/' . str_replace('%2F', '/', rawurlencode($rel));
}

/** Kompatibilita se vzory z Liberce (druhý argument se ignoruje – BASE_PATH platí i v adminu). */
function img_url(?string $rel, string $prefix = ''): string {
    return upload_url($rel);
}

/**
 * LOGO KLUBU – JEDINÉ místo, odkud se logo odkazuje.
 * Od 5. 10. 2026 nové logo od Petra (podklady/klient-loga/logo-2026-10/, připraví pripravit.py):
 * na stránkách průhledné logo.webp (448 px), PNG pro ikony (64 favicon, 256 apple-touch) a náhled sdílení (512).
 * Výměna loga = přepsat soubory ve web/assets/img/ (logo.webp, logo.png, logo-512/256/128/64.png).
 * Varianty: svg (= hlavní obrázek na stránky, název zůstal z doby vektoru) | 128 | 256 | 512 | 64 | favicon | png.
 */
function logo_url(string $varianta = 'svg'): string {
    $soubor = match ($varianta) {
        '128'            => 'img/logo-128.png',
        '256'            => 'img/logo-256.png',
        '512'            => 'img/logo-512.png',
        '64', 'favicon'  => 'img/logo-64.png',
        'png'            => 'img/logo.png',
        default          => 'img/logo.webp',
    };
    return asset($soubor);
}

/** Absolutní adresa webu (pro e-maily, og:url) – z SITE_URL, nebo z požadavku. */
function site_url(string $cesta = ''): string {
    if (SITE_URL !== '') return rtrim(SITE_URL, '/') . '/' . ltrim($cesta, '/');
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '' || !preg_match('/^[a-z0-9.\-:\[\]]+$/i', $host)) return url($cesta);
    return (je_https() ? 'https://' : 'http://') . $host . url($cesta);
}

function je_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
}

/** Název aktuálního souboru (pro zvýraznění v menu). */
function here(): string {
    return basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
}
function nav_active(string $soubor): string {
    return here() === $soubor ? ' aria-current="page"' : '';
}

/** Je odkaz mimo web? (http/https na jiný server) */
function odkaz_je_externi(?string $url): bool {
    return (bool)preg_match('~^https?://~i', trim((string)$url));
}

/** Atributy pro odkaz ven: target="_blank" rel="noopener". */
function odkaz_attr(?string $url): string {
    return odkaz_je_externi($url) ? ' target="_blank" rel="noopener"' : '';
}

/** Odkaz zadaný v administraci: povolí http(s), mailto, tel, #kotvu a stránku webu
 *  (např. „clenstvi.php#prihlaska“). Cokoli jiného (javascript:…) zahodí. Vrací hodnotu
 *  připravenou do href (stránky webu doplní o BASE_PATH). */
function bezpecny_odkaz(?string $url): string {
    $u = trim(html_entity_decode((string)$url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($u === '') return '';
    // schéma se posuzuje bez mezer a řídicích znaků („java script:“, „&#106;avascript:“)
    $holy = (string)preg_replace('/[\x00-\x20\x7F]+/', '', $u);
    if (preg_match('~^(javascript|data|vbscript|file):~i', $holy)) return '';
    if (preg_match('~^(https?://|mailto:|tel:)~i', $holy)) return preg_match('~[\s"<>]~', $u) ? $holy : $u;
    if (str_starts_with($u, '#')) return $u;
    if (preg_match('~^[a-z0-9][a-z0-9\-_/]*\.php([?#][^\s"<>]*)?$~i', $u)) return url($u);
    if (odkaz_je_soubor_webu($u)) return url($u);
    // adresa, kterou už jednou vrátila url() / upload_url() / dokument_url() („/cltkv2/dokument.php?d=…“,
    // „/cltkv2/uploads/…pdf“) – tlacitko() ji pouští znovu přes bezpecny_odkaz() a nesmí ji zahodit
    if (BASE_PATH !== '' && str_starts_with($u, BASE_PATH) && !str_starts_with($u, '//')) {
        $rel = substr($u, strlen(BASE_PATH));
        if ($rel === '' || preg_match('~^[a-z0-9][a-z0-9\-_/]*\.php([?#][^\s"<>]*)?$~i', $rel)
            || odkaz_je_soubor_webu(rawurldecode($rel))) return $u;
    }
    if (preg_match('~^(www\.)[a-z0-9.\-]+~i', $u)) return 'https://' . $u;
    return '';
}

/** Soubor nahraný na tento web zapsaný jako odkaz: „uploads/dokumenty/cenik.pdf“
 *  (ceník v PDF, plán areálu v PDF …). Jen bezpečné znaky, žádné „..“. */
function odkaz_je_soubor_webu(string $u): bool {
    return (bool)preg_match('~^uploads/[a-z0-9][a-z0-9\-_/]*(\.[a-z0-9]{2,5})$~i', $u) && !str_contains($u, '..');
}

/** Doplní https:// když chybí; prázdné nechá prázdné (pro ukládání odkazů z adminu). */
function normalizuj_url(?string $url): string {
    $u = trim((string)$url);
    if ($u === '') return '';
    if (preg_match('~^(https?://|mailto:|tel:|#)~i', $u)) return mb_substr($u, 0, 255);
    if (preg_match('~^[a-z0-9][a-z0-9\-_/]*\.php([?#].*)?$~i', $u)) return mb_substr($u, 0, 255);
    if (odkaz_je_soubor_webu($u)) return $u;                       // uploads/dokumenty/cenik.pdf
    return mb_substr('https://' . ltrim($u, '/'), 0, 255);
}

/** Telefon → href: „+420 608 974 974“ → „tel:+420608974974“. */
function tel_href(?string $tel): string {
    $t = preg_replace('/[^\d+]/', '', (string)$tel);
    return $t !== '' ? 'tel:' . $t : '';
}

/** Telefon bez předvolby pro zobrazení: „608 974 974“. */
function tel_kratce(?string $tel): string {
    return trim(preg_replace('/^\+420\s*/', '', (string)$tel));
}

function je_email(?string $s): bool {
    return (bool)filter_var(trim((string)$s), FILTER_VALIDATE_EMAIL);
}

/* ==================================================================
   NASTAVENÍ (Texty a údaje)
   setting() si při prvním volání načte celou tabulku do paměti.
   setting_set() zapíše do databáze I do té paměti – po UPDATE tedy
   další setting() vrátí novou hodnotu (past z Liberce).
   ================================================================== */

function setting_cache(?array $nastav = null, bool $reset = false): array {
    static $cache = null;
    if ($reset) $cache = null;
    if ($nastav !== null) {
        $cache = array_merge($cache ?? [], $nastav);
        return $cache;
    }
    if ($cache === null) {
        $cache = [];
        try {
            foreach (rows('SELECT skey, sval FROM cltk_settings') as $r) {
                $cache[$r['skey']] = (string)$r['sval'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return $cache;
}

/** Hodnota nastavení; prázdná hodnota vrací $default. */
function setting(string $klic, string $default = ''): string {
    $v = setting_cache()[$klic] ?? '';
    return $v !== '' ? $v : $default;
}

/** Zapíše nastavení (založí klíč, když chybí) a obnoví paměť. */
function setting_set(string $klic, string $hodnota, array $meta = []): void {
    $existuje = row('SELECT skey FROM cltk_settings WHERE skey = ?', [$klic]);
    if ($existuje) {
        q('UPDATE cltk_settings SET sval = ? WHERE skey = ?', [$hodnota, $klic]);
    } else {
        q('INSERT INTO cltk_settings (skey, sval, label, grp, typ, napoveda, poradi) VALUES (?,?,?,?,?,?,?)', [
            $klic, $hodnota, (string)($meta['label'] ?? $klic), (string)($meta['grp'] ?? 'obecne'),
            (string)($meta['typ'] ?? 'text'), (string)($meta['napoveda'] ?? ''), (int)($meta['poradi'] ?? 0),
        ]);
    }
    setting_cache([$klic => $hodnota]);
}

/** Tajný klíč webu (podpisy cookies a formulářů). Vznikne při prvním použití. */
function cltk_klic(): string {
    $k = setting('system_klic');
    if (strlen($k) >= 32) return $k;
    $k = bin2hex(random_bytes(32));
    try {
        setting_set('system_klic', $k, ['label' => 'Tajný klíč (needitovat)', 'grp' => 'system', 'typ' => 'text']);
    } catch (Throwable $e) {
        /* bez databáze aspoň pro tento požadavek */
    }
    return $k;
}

/** HMAC podpis řetězce tajným klíčem webu. */
function podpis(string $data): string {
    return hash_hmac('sha256', $data, cltk_klic());
}

/* ==================================================================
   RELACE, OCHRANA FORMULÁŘŮ (CSRF), HLÁŠKY
   Relace se jmenuje cltk_admin a má cookie jen pro cestu webu,
   aby se netloukla s relací TK Olymp (olymp_admin, cesta /).
   Na veřejném webu se relace zakládá jen tam, kde je formulář.
   ================================================================== */

/* Relace má vlastní složku data/relace/ (ne společnou se sdíleným hostingem
   TK Olymp), přísný režim (cizí, podstrčené ID relace PHP nepřijme) a všechny
   klíče pod $_SESSION['cltk'] – čtěte a zapisujte je přes relace() / relace_nastav(). */
const RELACE_PLATNOST = 14400;       // nečinná relace správce vyprší po 4 hodinách

function cltk_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    if (PHP_SAPI === 'cli') {
        if (!isset($_SESSION)) $_SESSION = [];
        return;
    }
    if (headers_sent()) return;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.gc_maxlifetime', (string)RELACE_PLATNOST);
    $slozka = DATA_DIR . '/relace';
    if (!is_dir($slozka)) @mkdir($slozka, 0700, true);
    if (is_dir($slozka) && is_writable($slozka)) {
        session_save_path($slozka);
        // ve vlastní složce staré relace neuklidí systém – úklid při každém ~100. spuštění
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
    }
    session_name('cltk_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_PATH,
        'httponly' => true,
        'secure'   => je_https(),
        'samesite' => 'Lax',
    ]);
    @session_start();
}

/** Hodnota z relace webu ČLTK (pod $_SESSION['cltk']). */
function relace(string $klic, $vychozi = null) {
    $r = $_SESSION['cltk'] ?? null;
    return is_array($r) && array_key_exists($klic, $r) ? $r[$klic] : $vychozi;
}

/** Zapíše hodnotu do relace webu ČLTK; null klíč odebere. */
function relace_nastav(string $klic, $hodnota): void {
    if (!isset($_SESSION['cltk']) || !is_array($_SESSION['cltk'])) $_SESSION['cltk'] = [];
    if ($hodnota === null) {
        unset($_SESSION['cltk'][$klic]);
    } else {
        $_SESSION['cltk'][$klic] = $hodnota;
    }
}

/** Otisk hesla do relace: po změně hesla přestanou platit všechna ostatní přihlášení. */
function relace_otisk_hesla(string $hash): string {
    return substr(podpis('relace|' . $hash), 0, 32);
}

/** Přihlášený správce podle relace (ověří i otisk hesla), nebo null. $obnovit = načíst znovu. */
function relace_spravce(bool $obnovit = false): ?array {
    static $u = false;
    if ($obnovit) $u = false;
    if ($u !== false) return $u;
    $u = null;
    $uid = (int)relace('uid', 0);
    if ($uid <= 0) return $u;
    try {
        $r = row('SELECT * FROM cltk_users WHERE id = ?', [$uid]);
    } catch (Throwable $e) {
        $r = null;
    }
    if ($r && hash_equals(relace_otisk_hesla((string)$r['password_hash']), (string)relace('otisk', ''))) {
        $u = $r;
    } else {
        relace_nastav('uid', null);            // účet zmizel nebo se změnilo heslo → odhlásit
        relace_nastav('otisk', null);
    }
    return $u;
}

/** Má návštěvník cookie relace? (bez založení nové relace) */
function cltk_ma_relaci(): bool {
    return session_status() === PHP_SESSION_ACTIVE || isset($_COOKIE['cltk_admin']);
}

function csrf_token(): string {
    cltk_session_start();
    $t = relace('csrf');
    if (!is_string($t) || strlen($t) < 32) {
        $t = bin2hex(random_bytes(16));
        relace_nastav('csrf', $t);
    }
    return $t;
}

/* $formId vyplňte, jen když token leží mimo svůj <form> a hlásí se k němu
   atributem form="…" (raději se tomu vyhněte – viz past v Liberci). */
function csrf_field(?string $formId = null): string {
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '"'
         . ($formId !== null ? ' form="' . e($formId) . '"' : '') . '>';
}

/** Na POST ověří token; jinak nic. Neplatný token = 400 a konec. */
function csrf_check(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
    $t = $_POST['_token'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        header('Content-Type: text/html; charset=UTF-8');
        exit('<!DOCTYPE html><meta charset="utf-8"><title>Formulář vypršel</title>'
           . '<p style="font:16px/1.6 system-ui,sans-serif;max-width:40em;margin:3em auto;padding:0 1em">'
           . 'Formulář vypršel nebo nebyl platný. Vraťte se prosím zpět, obnovte stránku a odešlete ho znovu.</p>');
    }
}

/* ------------------------------------------------------------------
   VEŘEJNÉ FORMULÁŘE (přihláška k akci, přihláška do klubu)
   csrf_field() zakládá relaci = cookie u každého návštěvníka, který
   formulář jen uvidí (kalendář s přihláškou je na úvodu). Web je ale
   bez cookies. Veřejné formuláře proto nesou PODEPSANÝ TOKEN bez relace
   (čas + HMAC tajným klíčem webu, váže se k názvu formuláře) a k tomu
   past na roboty (skryté pole) a brzdu (nejvýš 8 odeslání za hodinu
   z jedné IP adresy).
     ve formuláři:   <?= verejny_formular_pole('akce-12') ?>
     při POST:       $chyba = verejny_formular_over('akce-12');   // '' = v pořádku
     po uložení:     verejny_formular_zapis('akce-12');           // započte do brzdy
   ------------------------------------------------------------------ */

const VEREJNY_FORMULAR_MAX_ZA_HODINU = 8;
const VEREJNY_FORMULAR_PLATNOST = 7200;     // token platí 2 hodiny a jen z adresy (u IPv6 ze sítě /64), kde vznikl

function verejny_token(string $formular, ?int $cas = null): string {
    $t = (string)($cas ?? time());
    return $t . '.' . substr(podpis('formular|' . $formular . '|' . $t . '|' . client_ip_skupina()), 0, 32);
}

/** Skrytá pole veřejného formuláře: podepsaný token + past na roboty. */
function verejny_formular_pole(string $formular): string {
    return '<input type="hidden" name="_ft" value="' . e(verejny_token($formular)) . '">'
         . '<div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden">'
         . '<label>Toto pole nevyplňujte<input type="text" name="web_adresa" value="" tabindex="-1" autocomplete="off"></label></div>';
}

/** Ověří odeslaný veřejný formulář. Vrací '' (v pořádku), nebo hlášku pro návštěvníka. */
function verejny_formular_over(string $formular, ?array $post = null): string {
    $post = $post ?? $_POST;
    $token = is_string($post['_ft'] ?? null) ? $post['_ft'] : '';
    if (!preg_match('/^(\d{9,11})\.([a-f0-9]{32})$/', $token, $m)
        || !hash_equals(verejny_token($formular, (int)$m[1]), $token)) {
        return 'Formulář nebyl platný. Obnovte prosím stránku a odešlete ho znovu.';
    }
    $vek = time() - (int)$m[1];
    if ($vek > VEREJNY_FORMULAR_PLATNOST || $vek < 0) return 'Formulář vypršel. Zkontrolujte prosím údaje a odešlete ho znovu.';
    $past = $post['web_adresa'] ?? '';
    if ($vek < 2 || !is_string($past) || trim($past) !== '') {
        return 'Formulář se nepodařilo odeslat. Zkuste to prosím znovu.';      // robot
    }
    try {
        $od = date('Y-m-d H:i:s', time() - 3600);
        if ((int)val('SELECT COUNT(*) FROM cltk_login_attempts WHERE ip = ? AND tried_at > ?', ['form:' . client_ip_skupina(), $od])
            >= VEREJNY_FORMULAR_MAX_ZA_HODINU) {
            return 'Z vašeho připojení přišlo v poslední hodině příliš mnoho přihlášek. Zkuste to prosím později nebo zavolejte na recepci.';
        }
    } catch (Throwable $e) { /* bez tabulky aspoň bez brzdy */ }
    return '';
}

/** Po úspěšném uložení započte odeslání do brzdy. */
function verejny_formular_zapis(string $formular): void {
    try {
        q('INSERT INTO cltk_login_attempts (ip, tried_at) VALUES (?,?)', ['form:' . client_ip_skupina(), ted()]);
        if (random_int(1, 20) === 1) {     // občasný úklid starých záznamů brzdy
            q('DELETE FROM cltk_login_attempts WHERE tried_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
        }
    } catch (Throwable $e) { /* nevadí */ }
}

/** Odpověď pro JavaScript (fetch): JSON a konec. */
function odpoved_json(array $data, int $kod = 200): never {
    http_response_code($kod);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Chce volající JSON? (fetch s hlavičkou Accept: application/json nebo ?format=json) */
function chce_json(): bool {
    return str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
        || (($_GET['format'] ?? '') === 'json');
}

/** Hláška, která přežije přesměrování (zapsat: flash('Uloženo.'), přečíst: flash()). */
function flash(?string $zprava = null, string $typ = 'ok'): ?array {
    cltk_session_start();
    if ($zprava !== null) {
        relace_nastav('flash', ['msg' => $zprava, 'type' => $typ]);
        return null;
    }
    $f = relace('flash');
    if ($f !== null) relace_nastav('flash', null);
    return is_array($f) ? $f : null;
}

/** Přesměrování (po zápisu vždy přesměrovat, ne vypsat stránku). */
function redirect(string $kam, ?string $zprava = null, string $typ = 'ok'): never {
    if ($zprava !== null) flash($zprava, $typ);
    header('Location: ' . $kam, true, 303);
    exit;
}

/* ==================================================================
   VSTUP Z FORMULÁŘŮ
   ================================================================== */

/** Text z POST: vždy řetězec, oříznutý, zkrácený na $max znaků, sjednocené konce řádků. */
function vstup(string $klic, int $max = 255): string {
    $v = $_POST[$klic] ?? '';
    if (!is_scalar($v)) return '';
    $v = trim(str_replace("\r\n", "\n", (string)$v));
    return $max > 0 ? mb_substr($v, 0, $max) : $v;
}

function vstup_int(string $klic, ?int $vychozi = 0): ?int {
    $v = $_POST[$klic] ?? '';
    if (!is_scalar($v) || trim((string)$v) === '' || !preg_match('/^-?\d+$/', trim((string)$v))) return $vychozi;
    return (int)$v;
}

/** Heslo z POST: přesně, jak ho člověk napsal (bez ořezávání); pole místo textu = ''. */
function vstup_heslo(string $klic): string {
    $v = $_POST[$klic] ?? '';
    return is_string($v) ? $v : '';
}

function vstup_bool(string $klic): int {
    return !empty($_POST[$klic]) ? 1 : 0;
}

/** Parametr z adresy (GET) jako řetězec. Pole v adrese („?zpet[]=x“) = výchozí hodnota
 *  (jinak by (string) zapsal do chyby.log varování při každém takovém požadavku). */
function vstup_get(string $klic, string $vychozi = ''): string {
    $v = $_GET[$klic] ?? null;
    return is_scalar($v) ? (string)$v : $vychozi;
}

function client_ip(): string {
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'neznama'), 0, 64);
}

/** Adresa pro brzdy a tokeny: IPv4 celá, IPv6 jen síť /64 (jedna přípojka má celou /64
 *  a výměnou adresy uvnitř ní by se brzda dala obejít). */
function client_ip_skupina(): string {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $bin = @inet_pton($ip);
        if (is_string($bin) && strlen($bin) === 16) {
            if (str_starts_with($bin, str_repeat("\0", 10) . "\xff\xff")) return (string)inet_ntop(substr($bin, 12));   // ::ffff:1.2.3.4
            return inet_ntop(substr($bin, 0, 8) . str_repeat("\0", 8)) . '/64';
        }
    }
    return client_ip();
}

/* ==================================================================
   NÁVŠTĚVNOST – bez cookies, jen nevratný denní otisk
   Zapisuje se jen NÁZEV STRÁNKY (soubor skriptu), ne adresa z požadavku –
   /index.php/cokoli ani ?parametry tak nové řádky nezakládají. Počet
   otisků za den má strop a záznamy starší 13 měsíců se občas smažou
   (tabulky leží ve sdílené databázi).
   ================================================================== */

const NAVSTEVNOST_MAX_OTISKU_ZA_DEN = 20000;
const NAVSTEVNOST_MESICU = 13;

function track_visit(?string $cesta = null): void {
    if (PHP_SAPI === 'cli') return;
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
    // /index.php/cokoli (cesta za skriptem) se nepočítá – Apache to má vypnuté (AcceptPathInfo Off)
    $adresa = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $skript = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    if ($skript !== '' && str_starts_with($adresa, $skript . '/')) return;
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|lighthouse|preview|curl|wget|python|monitor/i', $ua)) return;

    if ($cesta === null) {
        $soubor = here();
        $cesta = $soubor === 'index.php' ? '/' : '/' . $soubor;
    }
    if (!preg_match('~^/[a-z0-9][a-z0-9\-_.]{0,80}$|^/$~', $cesta)) return;
    $den = date('Y-m-d');
    try {
        db_upsert_hit($den, $cesta);
        if ((int)val('SELECT COUNT(*) FROM cltk_visit_log WHERE day = ?', [$den]) < NAVSTEVNOST_MAX_OTISKU_ZA_DEN) {
            $otisk = substr(hash('sha256', client_ip() . '|' . $ua . '|' . $den . '|' . cltk_klic()), 0, 32);
            db_insert_ignore_visitor($den, $otisk);
        }
        if (random_int(1, 300) === 1) navstevnost_uklid();
    } catch (Throwable $e) {
        /* statistika nikdy nesmí shodit web */
    }
}

/** Smaže záznamy návštěvnosti starší než NAVSTEVNOST_MESICU měsíců. */
function navstevnost_uklid(): void {
    $hranice = date('Y-m-d', strtotime('-' . NAVSTEVNOST_MESICU . ' months'));
    q('DELETE FROM cltk_visit_log WHERE day < ?', [$hranice]);
    q('DELETE FROM cltk_visits WHERE day < ?', [$hranice]);
}

/* ==================================================================
   ŠIFROVÁNÍ CITLIVÝCH ÚDAJŮ (rodné číslo z přihlášky do klubu)
   Klíč je v cltk-config.php mimo www: define('SIFROVACI_KLIC', '…64 náhodných
   znaků…'). Bez klíče web rodné číslo vůbec nesbírá (pole se ve formuláři
   neukáže). Lokálně (SQLite) si klíč založí sám v data/sifrovaci-klic.txt.
   Šifruje sodium (secretbox), když chybí, OpenSSL AES-256-GCM.
   ================================================================== */

function citlive_klic(): string {
    static $k = null;
    if ($k !== null) return $k;
    $zdroj = defined('SIFROVACI_KLIC') ? (string)SIFROVACI_KLIC : '';
    if ($zdroj === '' && DB_DRIVER === 'sqlite') {
        $zdroj = (string)getenv('CLTK_SIFROVACI_KLIC');
        $soubor = DATA_DIR . '/sifrovaci-klic.txt';
        if ($zdroj === '' && is_file($soubor)) $zdroj = trim((string)@file_get_contents($soubor));
        if ($zdroj === '' && is_dir(DATA_DIR) && is_writable(DATA_DIR)) {
            $zdroj = bin2hex(random_bytes(32));
            if (@file_put_contents($soubor, $zdroj . "\n", LOCK_EX) === false) $zdroj = '';
        }
    }
    return $k = strlen($zdroj) >= 32 ? hash('sha256', 'cltk-citlive|' . $zdroj, true) : '';
}

/** Lze citlivé údaje uložit zašifrované? (bez klíče se nesbírají) */
function citlive_lze_sifrovat(): bool {
    return citlive_klic() !== '' && (function_exists('sodium_crypto_secretbox') || function_exists('openssl_encrypt'));
}

/** Zašifruje text („s1:…“ sodium, „o1:…“ OpenSSL). Prázdný text zůstane prázdný. */
function citlive_zasifruj(string $text): string {
    if ($text === '') return '';
    $k = citlive_klic();
    if ($k === '') throw new RuntimeException('Chybí šifrovací klíč (SIFROVACI_KLIC v cltk-config.php).');
    if (function_exists('sodium_crypto_secretbox')) {
        $n = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return 's1:' . base64_encode($n . sodium_crypto_secretbox($text, $n, $k));
    }
    $iv = random_bytes(12);
    $tag = '';
    $c = openssl_encrypt($text, 'aes-256-gcm', $k, OPENSSL_RAW_DATA, $iv, $tag);
    if ($c === false) throw new RuntimeException('Šifrování se nezdařilo.');
    return 'o1:' . base64_encode($iv . $tag . $c);
}

/** Je hodnota zašifrovaná citlive_zasifruj()? */
function citlive_je_sifra(string $s): bool {
    return (bool)preg_match('~^(s1|o1):[A-Za-z0-9+/=]+$~', $s);
}

/** Rozšifruje hodnotu; nezašifrovaný text vrátí, jak je; null = nejde rozšifrovat (chybí klíč). */
function citlive_desifruj(string $s): ?string {
    if (!citlive_je_sifra($s)) return $s;
    $k = citlive_klic();
    $d = base64_decode(substr($s, 3), true);
    if ($k === '' || $d === false) return null;
    if (str_starts_with($s, 's1:')) {
        if (!function_exists('sodium_crypto_secretbox_open') || strlen($d) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) return null;
        $t = sodium_crypto_secretbox_open(substr($d, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($d, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $k);
        return $t === false ? null : $t;
    }
    if (strlen($d) <= 28 || !function_exists('openssl_decrypt')) return null;
    $t = openssl_decrypt(substr($d, 28), 'aes-256-gcm', $k, OPENSSL_RAW_DATA, substr($d, 0, 12), substr($d, 12, 16));
    return $t === false ? null : $t;
}

/* ==================================================================
   OSTATNÍ MODULY JÁDRA
   ================================================================== */

require_once __DIR__ . '/html.php';      // čištění HTML z editoru, html_inline()
require_once __DIR__ . '/soubory.php';   // nahrávání obrázků a PDF, WebP, loga partnerů
require_once __DIR__ . '/data.php';      // datové pomůcky pro stránky (oznámení, akce, ceníky, menu…)
