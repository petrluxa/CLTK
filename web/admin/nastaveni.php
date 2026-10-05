<?php
/* Texty a údaje – kontakty, adresa, patička, odkazy (Restaurace, Prague Open,
   rezervace), sociální sítě, video na úvodu, e-mail pro přihlášky a režim
   přípravy s náhledovým heslem (ZADANI §6.19).

   Pole se kreslí podle cltk_settings (grp, typ, label, napoveda, poradi).
   Skupina „system“ (tajný klíč, verze schématu) se tu nikdy neukazuje
   a nedá se přepsat. Ukládá se jen přes setting_set() – obnoví i paměť
   setting(). Náhledové heslo se ukládá jen jako hash (nahled_heslo_nastav())
   a pole se nikdy nepředvyplňuje. Všechno se nejdřív ověří; když je někde
   chyba, neuloží se nic a formulář se vrátí s vyplněnými hodnotami.

   Skupina „lide“ (Na koho se obrátit) = stránka Kontakt: údaje karet Recepce
   a Kancelář (nastavení) a pod nimi lidé z cltk_vedeni, skupiny kancelar
   a kontakt (jméno, funkce, telefon, e-mail, zobrazit kontakt / na webu,
   pořadí, smazat, jeden prázdný řádek na přidání). Ukládají se stejným
   tlačítkem a stejně všechno, nebo nic. Upravit / smazat jde jen řádek, který
   do těchto dvou skupin opravdu patří (výbor ani podvržené id formulář
   nezmění); fotka smazané osoby zmizí až po zápisu. Fotky a delší texty
   zůstávají v modulu Vedení (vedeni.php). */
require __DIR__ . '/inc/layout.php';
$user = require_login();
/* nahled_heslo_nastav() je v inc/rezim.php – načíst BEZ jeho automatického
   spuštění (to patří jen veřejným stránkám) */
defined('CLTK_BEZ_REZIMU') || define('CLTK_BEZ_REZIMU', true);
require_once WEB_ROOT . '/inc/rezim.php';

const NS_STRANKA = 'nastaveni.php';

/** Skupiny v pořadí podle SCHEMA.md: klíč => [nadpis, popis]. Neznámé (kromě system) spadnou do „Ostatní“. */
const NS_SKUPINY = [
    'kontakt'   => ['Kontakty a adresa', 'Název, adresa, příjezd a účty klubu – patička, stránka Kontakt a přípravná stránka.'],
    'lide'      => ['Na koho se obrátit', 'Stránka Kontakt: karty Recepce a Kancelář a lidé pod nadpisem Na koho se obrátit. Telefon recepce se ukazuje i v patičce, v mobilní liště a na přípravné stránce.'],
    'paticka'   => ['Patička', 'Texty v patičce webu.'],
    'odkazy'    => ['Odkazy a rezervace', 'Tlačítko Rezervovat kurt a položky menu, které mohou vést na samostatný web. Odkaz Obsazenost kurtů je ve skupině Na koho se obrátit.'],
    'site'      => ['Sociální sítě', 'Odkazy ve sloupci „Dokumenty a sítě“ v patičce. Prázdné se nezobrazí.'],
    'uvod'      => ['Úvodní strana – video', 'Video v sekci Členství na úvodu. Soubory leží ve složce uploads/.'],
    'prihlasky' => ['Přihlášky a e-mail', 'Přihlášky k akcím a do klubu se vždy uloží do administrace (Přihlášky).'],
    'rezim'     => ['Režim přípravy a náhled', 'Dokud web není hotový, vidí návštěvníci jen přípravnou stránku.'],
    'obecne'    => ['Ostatní', ''],
];

/** Typy souborů v nastavení „soubor“. */
/** Textové údaje, které bývají dlouhé – pole přes celou šířku i s krátkou hodnotou. */
const NS_SIROKE = ['paticka_pruh', 'kancelar_popis', 'recepce_popis', 'rezim_nadpis', 'klub_nazev', 'adresa_ulice'];
const NS_VIDEO = ['mp4', 'webm'];
const NS_OBRAZEK = ['jpg', 'jpeg', 'png', 'webp'];

/** Údaje, které web už nikde nezobrazuje – v administraci se neukazují (hodnota v databázi zůstává).
    prijezd_kratce: krátký popis příjezdu býval v patičce, Petr ho 5. 10. 2026 z patičky odebral. */
const NS_SKRYTE = ['prijezd_kratce'];
/** Popisky, které se od uložených v databázi liší (údaj změnil místo na webu). */
const NS_POPISKY = [
    'paticka_ctc' => ['Věta u dokumentů na stránce Kontakt', 'Např. členství v Centenary Tennis Clubs a v ČTS. V patičce se už nezobrazuje.'],
];

/** Skupina „lide“: mezititulky nad nastaveními (klíče, které sem nepatří, spadnou pod poslední). */
const NS_LIDE_CASTI = [
    'Recepce'  => ['recepce_popis', 'recepce_telefon', 'recepce_email', 'obsazenost_url'],
    'Kancelář' => ['kancelar_jmeno', 'kancelar_popis', 'kancelar_telefon', 'kancelar_email'],
];
/** Lidé ze stránky Kontakt (cltk_vedeni.skupina => nadpis). Výbor (vybor) se tu needituje. */
const NS_LIDE_SKUPINY = ['kancelar' => 'Kancelář klubu', 'kontakt' => 'Další kontakty'];
const NS_LIDE_TEL = '/^[+\d][\d \-\/()]{5,}$/';
const NS_LIDE_POPISKY = ['jmeno' => 'jméno', 'funkce' => 'funkce', 'telefon' => 'telefon', 'email' => 'e-mail', 'poradi' => 'pořadí'];

if (!function_exists('obsah_prosty')) {
    /** Prostý text z formuláře bez HTML značek (stejná pomůcka jako v modulech obsahu, např. vedeni.php). */
    function obsah_prosty(string $s): string {
        $s = (string)preg_replace('~<\s*(script|style|iframe|object|embed|svg|math|template)\b[^>]*>.*?<\s*/\s*\1\s*>~is', '', $s);
        $s = (string)preg_replace('~<!--.*?(?:-->|$)~s', '', $s);
        $s = (string)preg_replace('~</?[a-z!?][^<>]*>~i', '', $s, -1, $pocet);
        if ($pocet > 0) $s = (string)preg_replace('/[ \t]{2,}/', ' ', $s);
        return trim($s);
    }
}

/** Lidé skupin kancelar a kontakt: skupina => [id => řádek] v pořadí jako na webu. */
function ns_lide_nacti(): array {
    $v = array_fill_keys(array_keys(NS_LIDE_SKUPINY), []);
    foreach (rows("SELECT * FROM cltk_vedeni WHERE skupina IN ('kancelar', 'kontakt') ORDER BY poradi, id") as $r) {
        $v[(string)$r['skupina']][(int)$r['id']] = $r;
    }
    return $v;
}

/** Hodnoty řádku osoby do formuláře (pořadí = místo v seznamu od 1). */
function ns_lide_radek_form(array $r, int $misto): array {
    return ['jmeno' => (string)$r['jmeno'], 'funkce' => (string)$r['funkce'], 'telefon' => (string)$r['telefon'],
            'email' => (string)$r['email'], 'zobrazit_kontakt' => (int)$r['zobrazit_kontakt'] === 1 ? 1 : 0,
            'visible' => (int)$r['visible'] === 1 ? 1 : 0, 'poradi' => (string)$misto, 'smazat' => 0];
}

/** Prázdný řádek „přidat osobu“. */
function ns_lide_novy_form(): array {
    return ['jmeno' => '', 'funkce' => '', 'telefon' => '', 'email' => '', 'zobrazit_kontakt' => 1, 'visible' => 1, 'poradi' => '', 'smazat' => 0];
}

/** Řádek osoby z POST: ořezané hodnoty + chyby [pole => hláška]. */
function ns_lide_z_postu(array $x): array {
    $t = static function (string $k, int $max) use ($x): string {
        $v = $x[$k] ?? '';
        return is_scalar($v) ? trim(mb_substr(obsah_prosty(str_replace("\r\n", "\n", (string)$v)), 0, $max)) : '';
    };
    $b = static fn(string $k): int => (is_scalar($x[$k] ?? null) && (string)$x[$k] === '1') ? 1 : 0;
    $f = ['jmeno' => $t('jmeno', 120), 'funkce' => $t('funkce', 160), 'telefon' => $t('telefon', 40),
          'email' => mb_strtolower($t('email', 160)), 'zobrazit_kontakt' => $b('zobrazit_kontakt'), 'visible' => $b('visible'),
          'poradi' => $t('poradi', 6), 'smazat' => $b('smazat')];
    $ch = [];
    if ($f['email'] !== '' && !je_email($f['email'])) $ch['email'] = 'E-mail nevypadá správně (např. kancelar@cltk.cz).';
    if ($f['telefon'] !== '' && !preg_match(NS_LIDE_TEL, $f['telefon'])) $ch['telefon'] = 'Telefon zapište číslicemi, např. +420 777 123 456.';
    if ($f['poradi'] !== '' && (!preg_match('/^\d{1,3}$/', $f['poradi']) || (int)$f['poradi'] < 1)) $ch['poradi'] = 'Pořadí je číslo místa v seznamu: 1, 2, 3…';
    return [$f, $ch];
}

/**
 * Nové pořadí skupiny. $radky = [['klic' => id|'novy', 'puvodni' => místo od 1 | null, 'nove' => číslo | null], …]
 * v dosavadním pořadí. Číslo = místo, kam osoba patří: kdo se posouvá nahoru, předběhne osobu se stejným
 * číslem, kdo dolů, zařadí se za ni; nová osoba bez čísla jde na konec. Vrací klíče v novém pořadí.
 */
function ns_lide_serad(array $radky): array {
    $klice = [];
    foreach (array_values($radky) as $i => $r) {
        $p = $r['puvodni'];
        $n = $r['nove'] ?? $p;
        if ($n === null) { $klice[] = [PHP_INT_MAX, 1, $i, $r['klic']]; continue; }        // nová bez čísla
        $smer = $p === null || $n < $p ? 0 : ($n > $p ? 2 : 1);                         // 0 nahoru, 1 stojí, 2 dolů
        $klice[] = [$n, $smer, $i, $r['klic']];
    }
    usort($klice, static fn($a, $b) => [$a[0], $a[1], $a[2]] <=> [$b[0], $b[1], $b[2]]);
    return array_column($klice, 3);
}

/** Smaže fotky smazaných osob z uploads/ – jen když je už žádný řádek cltk_vedeni nepoužívá. Volat AŽ po zápisu. */
function ns_lide_smazat_fotky(array $fotky): void {
    foreach (array_unique(array_filter(array_map('strval', $fotky), static fn($s) => $s !== '')) as $f) {
        if ((int)val('SELECT COUNT(*) FROM cltk_vedeni WHERE foto = ?', [$f]) === 0) delete_upload($f);
    }
}

/** Jedno pole řádku osoby (štítek se na širokém displeji schová do záhlaví sloupců). */
function ns_lide_pole(string $name, string $id, string $label, string $hodnota, string $typ, int $max, ?string $chyba, string $trida, array $attrs = []): string {
    $a = array_merge(['type' => $typ, 'id' => $id, 'name' => $name, 'value' => $hodnota, 'maxlength' => $typ === 'number' ? null : $max,
                      'aria-describedby' => $chyba !== null ? $id . '-chyba' : null, 'aria-invalid' => $chyba !== null ? 'true' : null], $attrs);
    return '<div class="field uv-os__pole ' . e($trida) . ($chyba !== null ? ' je-chyba' : '') . '">'
         . '<label class="uv-os__stitek" for="' . e($id) . '">' . e($label) . '</label><input' . ui_attrs($a) . '>'
         . ($chyba !== null ? '<div class="hint" id="' . e($id) . '-chyba"><span class="uv-chyba" role="alert">' . e($chyba) . '</span></div>' : '') . '</div>';
}

/** Zaškrtávátko řádku osoby (posílá 1; skrytá nula před ním = odškrtnuto). */
function ns_lide_check(string $name, string $id, string $label, bool $zaskrtnuto, string $trida = ''): string {
    return '<input type="hidden" name="' . e($name) . '" value="0"><label class="check' . ($trida !== '' ? ' ' . e($trida) : '') . '" for="' . e($id) . '">'
         . '<input type="checkbox" id="' . e($id) . '" name="' . e($name) . '" value="1"' . ($zaskrtnuto ? ' checked' : '') . '><span>' . e($label) . '</span></label>';
}

/** Řádek osoby ve formuláři. $klic = id | 'novy-<skupina>'. */
function ns_lide_radek_html(string $klic, string $skupina, array $f, array $chyby, ?array $db, string $kartaJmeno): string {
    $novy = $db === null;
    $base = $novy ? 'on[' . $skupina . ']' : 'o[' . $klic . ']';
    $idz = 'set-os-' . $klic . '-';
    $ch = static fn(string $p): ?string => $chyby['os-' . $klic . '-' . $p] ?? null;
    $tridy = 'uv-os' . ($novy ? ' uv-os--novy' : '') . (!$novy && !(int)$db['visible'] ? ' je-skryte' : '');
    $popis = $novy ? 'Přidat osobu – ' . NS_LIDE_SKUPINY[$skupina] : ((string)$db['jmeno'] !== '' ? (string)$db['jmeno'] : 'Osoba bez jména');
    $h = '<div class="' . $tridy . '" role="group" aria-label="' . e($popis) . '"' . ($novy ? '' : ' id="os-' . e($klic) . '"') . '>';
    if ($novy) $h .= '<p class="uv-os__novy">Přidat osobu <span class="uv-tlumene">– vyplňte aspoň jméno; bez pořadí přijde na konec, prázdný řádek se neuloží.</span></p>';
    $h .= '<input type="hidden" name="' . e($base) . '[byl]" value="1">';
    $h .= ns_lide_pole($base . '[jmeno]', $idz . 'jmeno', 'Jméno', $f['jmeno'], 'text', 120, $ch('jmeno'), 'uv-os__jmeno', ['autocomplete' => 'off']);
    $h .= ns_lide_pole($base . '[funkce]', $idz . 'funkce', 'Funkce', $f['funkce'], 'text', 160, $ch('funkce'), 'uv-os__funkce');
    $h .= ns_lide_pole($base . '[telefon]', $idz . 'telefon', 'Telefon', $f['telefon'], 'tel', 40, $ch('telefon'), 'uv-os__telefon');
    $h .= ns_lide_pole($base . '[email]', $idz . 'email', 'E-mail', $f['email'], 'email', 160, $ch('email'), 'uv-os__email', ['autocomplete' => 'off']);
    $h .= ns_lide_pole($base . '[poradi]', $idz . 'poradi', 'Pořadí', $f['poradi'], 'number', 3, $ch('poradi'), 'uv-os__poradi',
                       ['min' => '1', 'max' => '999', 'step' => '1', 'inputmode' => 'numeric']);
    $h .= '<div class="uv-os__volby">'
        . ns_lide_check($base . '[zobrazit_kontakt]', $idz . 'kontakt', 'Telefon a e-mail zobrazit na webu', (bool)$f['zobrazit_kontakt'])
        . ns_lide_check($base . '[visible]', $idz . 'visible', 'Zobrazit na webu', (bool)$f['visible']);
    if (!$novy) {
        $h .= ns_lide_check($base . '[smazat]', $idz . 'smazat', 'Smazat', (bool)$f['smazat'], 'uv-os__smazat');
        if ($skupina === 'kancelar' && $kartaJmeno !== '' && mb_strtolower(trim((string)$db['jmeno'])) === $kartaJmeno) {
            $h .= '<span class="uv-os__pozn">' . badge('karta Kancelář', 'info') . ' <span class="uv-tlumene">na webu je nahoře na kartě, v seznamu se neopakuje</span></span>';
        }
        if ((string)$db['foto'] !== '' || trim((string)$db['text']) !== '') {
            $h .= '<a class="uv-os__vedeni" href="vedeni.php?id=' . (int)$db['id'] . '">Fotka a text ve Vedení</a>';
        }
    }
    return $h . '</div></div>';
}

/** Odkaz z formuláře: [hodnota pro DB, chyba]. Povolí https://…, mailto:, tel:, #kotvu a stránku webu. */
function ns_odkaz(string $vstup): array {
    $u = trim($vstup);
    if ($u === '') return ['', ''];
    $holy = (string)preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($u, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $holy) && !preg_match('~^(https?:|mailto:|tel:)~i', $holy)) {
        return [$u, 'Odkaz musí začínat https:// (nebo vést na stránku tohoto webu, např. restaurace.php).'];
    }
    $n = normalizuj_url($u);
    if (preg_match('~^https?://~i', $n)) {
        $host = (string)parse_url($n, PHP_URL_HOST);
        if (preg_match('~\s~u', $n) || !preg_match('/^[\p{L}\p{N}.\-]+\.\p{L}{2,}$/u', $host)) {
            return [$u, 'Odkaz nevypadá jako adresa webu. Zkopírujte ho celý z prohlížeče (https://…).'];
        }
    }
    if (bezpecny_odkaz($n) === '') return [$u, 'Tento odkaz nejde použít.'];
    return [$n, ''];
}

/** Druh souboru pro nastavení typu „soubor“: video | obrazek. */
function ns_druh_souboru(string $klic, string $hodnota): string {
    $pripona = strtolower(pathinfo($hodnota, PATHINFO_EXTENSION));
    if (in_array($pripona, NS_VIDEO, true)) return 'video';
    if (in_array($pripona, NS_OBRAZEK, true)) return 'obrazek';
    return (str_contains($klic, 'video') && !str_contains($klic, 'poster') && !str_contains($klic, 'plakat')) ? 'video' : 'obrazek';
}

/** Jeden soubor z $_FILES['soubor'][$klic] v podobě, jakou čeká upload_image(). */
function ns_soubor_z_formulare(string $klic): ?array {
    $f = $_FILES['soubor'] ?? null;
    if (!is_array($f) || !is_array($f['name'] ?? null) || !array_key_exists($klic, $f['name'])) return null;
    $s = [];
    foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $c) $s[$c] = $f[$c][$klic] ?? null;
    return (int)($s['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE ? null : $s;
}

/** Nahrání videa (MP4/WebM): kontrola obsahu, ne přípony. Vrací ['ok', 'file', 'error']. */
function ns_nahraj_video(array $f, string $podslozka, string $jmeno): array {
    $chyba = (int)($f['error'] ?? 0);
    if ($chyba === UPLOAD_ERR_INI_SIZE || $chyba === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'error' => 'Video je příliš velké – server přijme nejvýš ' . ini_get('upload_max_filesize') . 'B. Větší video nahrajte přes FTP do složky uploads/ a sem napište jen jeho cestu.'];
    }
    if ($chyba !== UPLOAD_ERR_OK) return ['ok' => false, 'error' => 'Nahrání videa se nezdařilo (kód ' . $chyba . ').'];
    $tmp = (string)$f['tmp_name'];
    if (PHP_SAPI !== 'cli' && !is_uploaded_file($tmp)) return ['ok' => false, 'error' => 'Video se nepodařilo přečíst.'];
    $hlava = (string)@file_get_contents($tmp, false, null, 0, 16);
    $pripona = '';
    if (strlen($hlava) >= 12 && substr($hlava, 4, 4) === 'ftyp') $pripona = 'mp4';
    elseif (str_starts_with($hlava, "\x1A\x45\xDF\xA3")) $pripona = 'webm';
    if ($pripona === '') return ['ok' => false, 'error' => 'Soubor není video MP4 ani WebM.'];
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $fi ? (string)finfo_file($fi, $tmp) : '';
        if ($mime !== '' && !str_starts_with($mime, 'video/') && $mime !== 'application/octet-stream') {
            return ['ok' => false, 'error' => 'Soubor se tváří jako video, ale není to video (' . $mime . ').'];
        }
    }
    uploads_zajisti_ochranu();
    $podslozka = trim((string)preg_replace('~[^a-z0-9_\-/]~i', '', $podslozka), '/') ?: 'video';
    $dir = UPLOAD_DIR . '/' . $podslozka;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) return ['ok' => false, 'error' => 'Složku pro video nelze založit.'];
    $zaklad = slugify($jmeno);
    $j = $zaklad; $i = 2;
    while (is_file($dir . '/' . $j . '.' . $pripona)) $j = $zaklad . '-' . $i++;
    $cil = $dir . '/' . $j . '.' . $pripona;
    $ok = PHP_SAPI === 'cli' ? @copy($tmp, $cil) : @move_uploaded_file($tmp, $cil);
    if (!$ok) return ['ok' => false, 'error' => 'Video se nepodařilo uložit.'];
    return ['ok' => true, 'file' => $podslozka . '/' . $j . '.' . $pripona];
}

/* --- načtení nastavení (bez skupiny system) --- */
$vse = array_values(array_filter(rows("SELECT * FROM cltk_settings WHERE grp <> 'system' ORDER BY poradi, skey"),
    fn($s) => !in_array((string)$s['skey'], NS_SKRYTE, true)));
foreach ($vse as &$s) {
    if (isset(NS_POPISKY[$s['skey']])) [$s['label'], $s['napoveda']] = NS_POPISKY[$s['skey']];
}
unset($s);
$podleSkupin = [];
foreach ($vse as $s) {
    $g = isset(NS_SKUPINY[$s['grp']]) ? $s['grp'] : 'obecne';
    $podleSkupin[$g][] = $s;
}

$chyby = [];          // klíč => hláška
$chybyNazvy = [];     // klíč chyby => název do souhrnu nahoře (lidé – nejsou v cltk_settings)
$hodnoty = [];        // hodnoty do formuláře (po chybě odeslané)
foreach ($vse as $s) $hodnoty[$s['skey']] = (string)$s['sval'];

/* lidé ze stránky Kontakt (skupina „lide“) – jen kancelar a kontakt */
$lide = ns_lide_nacti();
$lideForm = [];       // id => hodnoty řádku do formuláře
$lideNove = [];       // skupina => hodnoty prázdného řádku
foreach ($lide as $sk => $radky) {
    $m = 0;
    foreach ($radky as $rid => $r) $lideForm[$rid] = ns_lide_radek_form($r, ++$m);
    $lideNove[$sk] = ns_lide_novy_form();
}
$kartaJmeno = mb_strtolower(trim(setting('kancelar_jmeno')));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek(NS_STRANKA);
    if (vstup('action', 20) !== 'ulozit') redirect(NS_STRANKA, 'Neznámý požadavek.', 'err');

    $odeslane = is_array($_POST['s'] ?? null) ? $_POST['s'] : [];
    $nove = [];            // klíč => nová hodnota (jen změněné)
    $nahrane = [];         // soubory nahrané v tomto požadavku (při chybě se uklidí)
    $kSmazani = [];        // nahrazené soubory – smažou se až po zápisu

    foreach ($vse as $s) {
        $k = (string)$s['skey'];
        $typ = (string)$s['typ'];
        $stara = (string)$s['sval'];
        if ($typ === 'heslo') continue;                       // zvlášť níže
        if (!array_key_exists($k, $odeslane) || !is_scalar($odeslane[$k])) continue;   // klíč, který formulář neposlal, se nemění
        $v = trim(str_replace("\r\n", "\n", (string)$odeslane[$k]));

        switch ($typ) {
            case 'bool':
                $v = $v === '1' ? '1' : '0';
                break;
            case 'email':
                $v = mb_substr($v, 0, 190);
                if ($v !== '' && !je_email($v)) $chyby[$k] = 'Tohle nevypadá jako e-mailová adresa.';
                break;
            case 'tel':
                $v = mb_substr($v, 0, 40);
                if ($v !== '' && !preg_match('/^[+\d][\d \-\/()]{5,}$/', $v)) $chyby[$k] = 'Telefon zapište číslicemi, např. +420 608 974 974.';
                break;
            case 'url':
                [$v, $ch] = ns_odkaz(mb_substr($v, 0, 255));
                if ($ch !== '') $chyby[$k] = $ch;
                break;
            case 'cislo':
                $v = mb_substr($v, 0, 20);
                if ($v !== '' && !preg_match('/^\d+$/', $v)) $chyby[$k] = 'Sem patří jen číslo.';
                break;
            case 'soubor':
                $v = ltrim(str_replace('\\', '/', mb_substr($v, 0, 255)), '/');
                if (str_starts_with($v, 'uploads/')) $v = substr($v, 8);
                $soubor = ns_soubor_z_formulare($k);
                if ($soubor !== null) {
                    $druh = ns_druh_souboru($k, $stara);
                    $podslozka = $stara !== '' ? str_replace('\\', '/', dirname($stara)) : 'video';
                    if ($podslozka === '.' || $podslozka === '' || $podslozka === '/') $podslozka = 'video';
                    $jmeno = pathinfo((string)$soubor['name'], PATHINFO_FILENAME) ?: $k;
                    if ($druh === 'video') {
                        $up = ns_nahraj_video($soubor, $podslozka, $jmeno);
                    } else {
                        $up = upload_image($soubor, $podslozka, 2400, 2400, $jmeno);
                    }
                    if (!$up['ok']) { $chyby[$k] = $up['error']; break; }
                    $nahrane[] = $up['file'];
                    $v = $up['file'];
                    if ($stara !== '' && $stara !== $v) $kSmazani[] = $stara;
                } elseif ($v !== '' && (str_contains($v, '..') || !preg_match('~^[a-z0-9][a-z0-9\-_/.]*\.(' . implode('|', array_merge(NS_VIDEO, NS_OBRAZEK)) . ')$~i', $v))) {
                    $chyby[$k] = 'Cesta k souboru má tvar např. video/cltk-promo-720.mp4 (složka uvnitř uploads/, bez mezer a diakritiky).';
                }
                break;
            case 'textarea':
                $v = mb_substr($v, 0, 4000);
                break;
            default:                                           // text, inline
                $v = mb_substr($v, 0, 500);
        }
        $hodnoty[$k] = $v;
        if (!isset($chyby[$k]) && $v !== $stara) $nove[$k] = $v;
    }

    /* náhledové heslo – jen hash, pole se nikdy nepředvyplňuje */
    $hesloNove = vstup_heslo('nahled_heslo_nove');
    $hesloZrusit = vstup_bool('nahled_heslo_zrusit');
    $hesloZmena = null;
    if ($hesloZrusit) {
        if (setting('nahled_heslo_hash') !== '') $hesloZmena = '';
    } elseif ($hesloNove !== '') {
        if (mb_strlen($hesloNove) < 6) $chyby['nahled_heslo_hash'] = 'Náhledové heslo musí mít aspoň 6 znaků.';
        elseif (mb_strlen($hesloNove) > 200) $chyby['nahled_heslo_hash'] = 'Náhledové heslo je příliš dlouhé.';
        else $hesloZmena = $hesloNove;
    }

    /* lidé ze stránky Kontakt – řádky se berou jen podle $lide (skupiny kancelar a kontakt
       z databáze); id z formuláře, které tam není (výbor, cizí, mezitím smazané), se nepoužije */
    $osPost = is_array($_POST['o'] ?? null) ? $_POST['o'] : [];
    $osNovePost = is_array($_POST['on'] ?? null) ? $_POST['on'] : [];
    $osZapis = [];        // id => [skupina, změněné sloupce]
    $osNove = [];         // skupina => data nové osoby
    $osSmazat = [];       // id => řádek z databáze
    $osPoradiSkupin = 0;  // ve kolika skupinách se změnilo pořadí
    foreach ($lide as $sk => $radky) {
        $plan = [];
        $m = 0;
        foreach ($radky as $rid => $r) {
            $m++;
            $x = $osPost[$rid] ?? null;
            if (!is_array($x) || ($x['byl'] ?? '') !== '1') { $plan[] = ['klic' => $rid, 'puvodni' => $m, 'nove' => null]; continue; }
            [$f, $ch] = ns_lide_z_postu($x);
            $lideForm[$rid] = $f;
            if ($f['smazat']) { $osSmazat[$rid] = $r; continue; }          // mazaný řádek se neověřuje
            if ($f['jmeno'] === '') $ch['jmeno'] = 'Vyplňte jméno (nebo zaškrtněte Smazat).';
            $nazev = NS_LIDE_SKUPINY[$sk] . ', ' . ((string)$r['jmeno'] !== '' ? (string)$r['jmeno'] : 'osoba bez jména');
            foreach ($ch as $pole => $hlaska) {
                $chyby['os-' . $rid . '-' . $pole] = $hlaska;
                $chybyNazvy['os-' . $rid . '-' . $pole] = $nazev . ' – ' . NS_LIDE_POPISKY[$pole];
            }
            $data = [];
            foreach (['jmeno', 'funkce', 'telefon', 'email', 'zobrazit_kontakt', 'visible'] as $sl) {
                if ((string)$f[$sl] !== (string)$r[$sl]) $data[$sl] = $f[$sl];
            }
            if ($data) $osZapis[$rid] = [$sk, $data];
            $plan[] = ['klic' => $rid, 'puvodni' => $m, 'nove' => $f['poradi'] !== '' && !isset($ch['poradi']) ? (int)$f['poradi'] : null];
        }
        $x = $osNovePost[$sk] ?? null;
        if (is_array($x) && ($x['byl'] ?? '') === '1') {
            [$f, $ch] = ns_lide_z_postu($x);
            $f['smazat'] = 0;
            $lideNove[$sk] = $f;
            if ($f['jmeno'] !== '' || $f['funkce'] !== '' || $f['telefon'] !== '' || $f['email'] !== '') {     // prázdný řádek = nic
                if ($f['jmeno'] === '') $ch['jmeno'] = 'Vyplňte jméno nové osoby (nebo řádek vymažte).';
                foreach ($ch as $pole => $hlaska) {
                    $chyby['os-novy-' . $sk . '-' . $pole] = $hlaska;
                    $chybyNazvy['os-novy-' . $sk . '-' . $pole] = NS_LIDE_SKUPINY[$sk] . ', nová osoba – ' . NS_LIDE_POPISKY[$pole];
                }
                $osNove[$sk] = ['skupina' => $sk, 'jmeno' => $f['jmeno'], 'funkce' => $f['funkce'], 'telefon' => $f['telefon'],
                                'email' => $f['email'], 'zobrazit_kontakt' => $f['zobrazit_kontakt'], 'foto' => '', 'text' => '',
                                'visible' => $f['visible'], 'poradi' => 0];
                $plan[] = ['klic' => 'novy', 'puvodni' => null, 'nove' => $f['poradi'] !== '' && !isset($ch['poradi']) ? (int)$f['poradi'] : null];
            }
        }
        /* pořadí: když zůstává (jen bez smazaných, nová na konci), čísla v databázi se nepřepisují */
        $serazene = ns_lide_serad($plan);
        $beze = array_column($plan, 'klic');
        if ($serazene !== $beze) {
            $osPoradiSkupin++;
            foreach ($serazene as $i => $k) {
                if ($k === 'novy') { $osNove[$sk]['poradi'] = $i; continue; }
                if ((int)$radky[$k]['poradi'] === $i) continue;
                $osZapis[$k] = [$sk, ($osZapis[$k][1] ?? []) + ['poradi' => $i]];
            }
        } elseif (isset($osNove[$sk])) {
            $osNove[$sk]['poradi'] = $radky ? max(array_map('intval', array_column($radky, 'poradi'))) + 1 : 0;
        }
    }

    if (!$chyby) {
        /* všechno, nebo nic – kdyby zápis někde selhal, nezůstane polovina změn */
        db()->beginTransaction();
        try {
            foreach ($nove as $k => $v) setting_set($k, $v);
            if ($hesloZmena !== null) nahled_heslo_nastav($hesloZmena);
            foreach ($osSmazat as $rid => $r) q('DELETE FROM cltk_vedeni WHERE id = ? AND skupina = ?', [$rid, (string)$r['skupina']]);
            foreach ($osZapis as $rid => [$sk, $data]) {
                $sloupce = array_intersect_key($data, array_flip(['jmeno', 'funkce', 'telefon', 'email', 'zobrazit_kontakt', 'visible', 'poradi']));
                if (!$sloupce) continue;
                q('UPDATE cltk_vedeni SET ' . implode(', ', array_map(static fn($c) => $c . ' = ?', array_keys($sloupce)))
                  . ' WHERE id = ? AND skupina = ?', array_merge(array_values($sloupce), [$rid, $sk]));
            }
            foreach ($osNove as $data) db_insert('cltk_vedeni', $data);
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            setting_cache(null, true);
            admin_smazat_soubory($nahrane);
            throw $e;
        }
        /* nahrazené soubory pryč – jen když na ně už žádné nastavení neukazuje */
        $pouzite = array_map('strval', array_column(rows("SELECT sval FROM cltk_settings WHERE typ = 'soubor'"), 'sval'));
        admin_smazat_soubory(array_values(array_diff(array_unique($kSmazani), $pouzite)));
        ns_lide_smazat_fotky(array_column($osSmazat, 'foto'));          // fotky smazaných lidí až po zápisu

        $osUpraveno = count(array_filter($osZapis, static fn($z) => array_diff(array_keys($z[1]), ['poradi']) !== []));
        $pocet = count($nove) + ($hesloZmena !== null ? 1 : 0) + $osUpraveno + count($osNove) + count($osSmazat) + $osPoradiSkupin;
        if ($pocet === 0) redirect(NS_STRANKA, 'Nic se nezměnilo – všechny údaje už byly uložené.', 'info');
        $hlaska = 'Uloženo. ' . ($pocet === 1 ? 'Změnil se 1 údaj.' : 'Změnily se ' . $pocet . ' údaje.');
        if ($pocet > 4) $hlaska = 'Uloženo. Změnilo se ' . $pocet . ' údajů.';
        if (isset($nove['rezim_pripravy'])) {
            $hlaska .= $nove['rezim_pripravy'] === '1'
                ? ' Režim přípravy je ZAPNUTÝ – návštěvníci vidí jen přípravnou stránku.'
                : ' Režim přípravy je vypnutý – web vidí všichni.';
        }
        if ($hesloZmena === '') $hlaska .= ' Náhledové heslo je zrušené.';
        elseif ($hesloZmena !== null) $hlaska .= ' Náhledové heslo je nastavené (dřívější náhledy přestaly platit).';
        $osDetail = array_filter([
            $osNove ? 'přidáno: ' . implode(', ', array_column($osNove, 'jmeno')) : '',
            $osSmazat ? 'smazáno: ' . implode(', ', array_map(static fn($r) => (string)$r['jmeno'], $osSmazat)) : '',
            $osPoradiSkupin ? 'nové pořadí' : '',
        ]);
        if ($osDetail) $hlaska .= ' Na koho se obrátit – ' . implode('; ', $osDetail) . '.';
        redirect(NS_STRANKA . (($osNove || $osSmazat || $osZapis) && !$nove ? '#sk-lide' : ''), $hlaska);
    }
    admin_smazat_soubory($nahrane);                               // nic se neuložilo → nahrané soubory pryč
}

/* --- vykreslení jednoho pole --- */
function ns_pole(array $s, string $hodnota, ?string $chyba): string {
    $k = (string)$s['skey'];
    $typ = (string)$s['typ'];
    $label = (string)$s['label'] !== '' ? (string)$s['label'] : $k;
    $hint = (string)$s['napoveda'] !== '' ? e((string)$s['napoveda']) : '';
    $name = 's[' . $k . ']';
    $id = 'set-' . preg_replace('/[^a-z0-9_-]/i', '-', $k);
    $chybaHtml = $chyba !== null ? '<span class="uv-chyba" role="alert">' . e($chyba) . '</span> ' : '';
    $o = ['id' => $id, 'hint' => $chybaHtml . $hint];

    switch ($typ) {
        case 'bool':
            return '<div class="field field--cela' . ($chyba !== null ? ' je-chyba' : '') . '"><input type="hidden" name="' . e($name) . '" value="0">'
                 . '<label class="check" for="' . e($id) . '"><input type="checkbox" id="' . e($id) . '" name="' . e($name) . '" value="1"' . ($hodnota === '1' ? ' checked' : '') . '>'
                 . '<span>' . e($label) . '</span></label>' . ($o['hint'] !== '' ? '<div class="hint">' . $o['hint'] . '</div>' : '') . '</div>';
        case 'textarea':
            return pole_textarea($name, $label, $hodnota, $o + ['rows' => 4, 'sirka' => 'cela', 'maxlength' => 4000]);
        case 'email':
            return pole_text($name, $label, $hodnota, $o + ['type' => 'email', 'maxlength' => 190, 'autocomplete' => 'off']);
        case 'tel':
            return pole_text($name, $label, $hodnota, $o + ['type' => 'tel', 'maxlength' => 40]);
        case 'url':
            return pole_text($name, $label, $hodnota, $o + ['maxlength' => 255, 'attrs' => ['inputmode' => 'url'], 'placeholder' => 'https://…', 'sirka' => 'cela']);
        case 'cislo':
            return pole_text($name, $label, $hodnota, $o + ['maxlength' => 20, 'attrs' => ['inputmode' => 'numeric', 'pattern' => '\d*']]);
        case 'inline':
            $o['hint'] .= ($o['hint'] !== '' ? ' ' : '') . 'Zlatou kurzívu zapíšete jako <code>&lt;em&gt;slovo&lt;/em&gt;</code>.';
            return pole_text($name, $label, $hodnota, $o + ['maxlength' => 500, 'sirka' => 'cela']);
        case 'soubor':
            $druh = ns_druh_souboru($k, $hodnota);
            $nahled = '';
            $existuje = $hodnota !== '' && is_file(UPLOAD_DIR . '/' . $hodnota);
            if ($existuje && $druh === 'video') {
                $nahled = '<div class="uv-soubor-nahled"><video src="' . e(upload_url($hodnota)) . '" preload="none" controls muted playsinline></video></div>';
            } elseif ($existuje) {
                $nahled = '<div class="uv-soubor-nahled"><img src="' . e(upload_url($hodnota)) . '" alt="Současný obrázek" loading="lazy"></div>';
            } elseif ($hodnota !== '') {
                $nahled = '<div class="hint hint--varovani">Soubor <b>' . e($hodnota) . '</b> na serveru zatím není. Nahrajte ho níže, nebo přes FTP do složky uploads/.</div>';
            }
            $accept = $druh === 'video' ? 'video/mp4,video/webm,.mp4,.webm' : 'image/jpeg,image/png,image/webp';
            $max = ini_get('upload_max_filesize');
            $vnitrek = $nahled
                . '<input type="text" id="' . e($id) . '" name="' . e($name) . '" value="' . e($hodnota) . '" maxlength="255" spellcheck="false" aria-describedby="' . e($id) . '-hint">'
                . '<label class="uv-mezera" style="display:block;font-size:13px;color:var(--seda)" for="' . e($id) . '-soubor">Nahrát nový soubor (' . ($druh === 'video' ? 'MP4, nejvýš ' . e($max) . 'B' : 'JPG, PNG nebo WEBP') . ')</label>'
                . '<input type="file" id="' . e($id) . '-soubor" name="soubor[' . e($k) . ']" accept="' . e($accept) . '">';
            return '<div class="field field--cela' . ($chyba !== null ? ' je-chyba' : '') . '"><label for="' . e($id) . '">' . e($label) . '</label>' . $vnitrek
                 . '<div class="hint" id="' . e($id) . '-hint">' . $o['hint'] . ($o['hint'] !== '' ? ' ' : '')
                 . 'Cesta uvnitř složky uploads/. Velké video nahrajte raději přes FTP a sem napište jen cestu.</div></div>';
        default:
            // delší texty (patička, popisy kanceláře a recepce…) přes celou šířku, ať je vidět celé
            $dlouhy = mb_strlen($hodnota) > 34 || in_array($k, NS_SIROKE, true);
            return pole_text($name, $label, $hodnota, $o + ['maxlength' => 500] + ($dlouhy ? ['sirka' => 'cela'] : []));
    }
}

$rezim = ($hodnoty['rezim_pripravy'] ?? setting('rezim_pripravy')) === '1';
$maHeslo = setting('nahled_heslo_hash') !== '';

admin_head('Texty a údaje', $user, [
    'podnadpis' => 'Kontakty, adresa, na koho se obrátit (recepce, kancelář a lidé ze stránky Kontakt), patička, odkazy, sociální sítě, video na úvodu, e-mail pro přihlášky a režim přípravy. Změny se na webu projeví hned po uložení.',
    'akce'      => '<a class="btn btn-ghost" href="' . e(url('')) . '" target="_blank" rel="noopener">Zobrazit web ↗</a>',
]);
echo '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';
?>
<?php if ($chyby): ?>
  <ul class="uv-chyby" role="alert"><li><b>Nic se neuložilo – opravte prosím <?= count($chyby) === 1 ? 'jeden údaj' : count($chyby) . ' údaje' ?>:</b></li>
    <?php foreach ($chyby as $k => $ch): ?><li><a href="#set-<?= e(preg_replace('/[^a-z0-9_-]/i', '-', $k)) ?>"><?= e(($chybyNazvy[$k] ?? ($k === 'nahled_heslo_hash' ? 'Náhledové heslo' : (array_column($vse, 'label', 'skey')[$k] ?? $k))) . ': ' . $ch) ?></a></li><?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php if (!$vse): ?>
  <section class="panel"><?= prazdny_stav('Zatím tu nic není', 'Texty a údaje se do databáze nahrají při instalaci (výchozí obsah). Požádejte správce webu.') ?></section>
<?php else: ?>

<nav class="zalozky" aria-label="Skupiny údajů">
  <?php foreach (NS_SKUPINY as $g => [$nadpis]): if (empty($podleSkupin[$g]) && $g !== 'lide') continue; ?>
    <a href="#<?= $g === 'rezim' ? 'rezim' : 'sk-' . e($g) ?>"><?= e($nadpis) ?></a>
  <?php endforeach; ?>
</nav>

<form method="post" class="form wide" enctype="multipart/form-data" data-hlidat-zmeny>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="ulozit">

  <?php foreach (NS_SKUPINY as $g => [$nadpis, $popis]): if (empty($podleSkupin[$g]) && $g !== 'lide') continue; ?>
    <section class="panel uv-nastaveni-skupina" id="<?= $g === 'rezim' ? 'rezim' : 'sk-' . e($g) ?>">
      <div class="panel-head">
        <h2><?= e($nadpis) ?> <small><?= count($podleSkupin[$g] ?? []) + ($g === 'lide' ? array_sum(array_map('count', $lide)) : 0) ?></small></h2>
        <?php if ($popis !== ''): ?><span class="hint"><?= e($popis) ?></span><?php endif; ?>
      </div>
      <div class="panel-body">
        <?php if ($g === 'odkazy'): $r = odkaz_restaurace(); $po = odkaz_prague_open(); ?>
          <div class="uv-info">
            <p>Menu <b>Restaurace</b> teď vede na: <?= $r['externi'] ? '<a href="' . e($r['url']) . '" target="_blank" rel="noopener">' . e($r['url']) . '</a> (nové okno)' : 'stránku Restaurace na tomto webu („připravujeme“)' ?>.</p>
            <p>Menu <b>Prague Open</b> teď vede na: <?= $po['externi'] ? '<a href="' . e($po['url']) . '" target="_blank" rel="noopener">' . e($po['url']) . '</a> (nové okno)' : 'stránku Prague Open na tomto webu' ?>.</p>
          </div>
        <?php endif; ?>
        <?php if ($g === 'rezim'): ?>
          <div class="uv-rezim<?= $rezim ? ' uv-rezim--zapnuto' : '' ?>" role="status">
            <b><?= $rezim ? 'Režim přípravy je zapnutý.' : 'Režim přípravy je vypnutý – web vidí všichni.' ?></b>
            <?php if ($rezim): ?>
              <span class="hint" style="margin:0">Návštěvníci vidí přípravnou stránku s rezervací a kontaktem na recepci. Vy jako přihlášený správce vidíte celý web s pruhem nahoře<?= $maHeslo ? ', stejně jako každý, kdo zadá náhledové heslo' : '' ?>. Vyhledávače web neindexují.</span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if ($g === 'lide'): ?>
          <?php /* --- Na koho se obrátit: nastavení pod mezititulky Recepce / Kancelář, pak lidé --- */
            $casti = [];
            foreach ($podleSkupin['lide'] ?? [] as $s) {
                $cast = array_key_last(NS_LIDE_CASTI);
                foreach (NS_LIDE_CASTI as $nazevCasti => $klice) if (in_array((string)$s['skey'], $klice, true)) { $cast = $nazevCasti; break; }
                $casti[$cast][] = $s;
            } ?>
          <?php foreach (NS_LIDE_CASTI as $nazevCasti => $klice): if (empty($casti[$nazevCasti])) continue; ?>
            <h3 class="uv-lide-titul"><?= e($nazevCasti) ?></h3>
            <div class="form-mrizka">
              <?php foreach ($casti[$nazevCasti] as $s): ?><?= ns_pole($s, $hodnoty[(string)$s['skey']] ?? '', $chyby[(string)$s['skey']] ?? null) ?><?php endforeach; ?>
            </div>
          <?php endforeach; ?>
          <h3 class="uv-lide-titul">Lidé a kontakty</h3>
          <?php foreach (NS_LIDE_SKUPINY as $sk => $skNazev): ?>
            <div class="uv-lide" id="lide-<?= e($sk) ?>">
              <h4 class="uv-lide__skupina"><?= e($skNazev) ?> <small><?= count($lide[$sk]) ?></small></h4>
              <div class="uv-lide__hlava" aria-hidden="true"><span>Jméno</span><span>Funkce</span><span>Telefon</span><span>E-mail</span><span>Pořadí</span></div>
              <?php foreach ($lide[$sk] as $rid => $r): ?><?= ns_lide_radek_html((string)$rid, $sk, $lideForm[$rid], $chyby, $r, $kartaJmeno) ?><?php endforeach; ?>
              <?= ns_lide_radek_html('novy-' . $sk, $sk, $lideNove[$sk], $chyby, null, $kartaJmeno) ?>
            </div>
          <?php endforeach; ?>
          <div class="uv-info uv-lide-pozn">
            <p>Na webu jsou lidé pod nadpisem „Na koho se obrátit“ – nejdřív Kancelář klubu, pak Další kontakty. Osoba z karty Kancelář<?= setting('kancelar_jmeno') !== '' ? ' (' . e(setting('kancelar_jmeno')) . ')' : '' ?> se v seznamu na webu neopakuje.</p>
            <p>Pořadí: 1 = nahoře. Osobu přesunete tak, že jí napíšete číslo místa, kam patří – ostatní se posunou.</p>
            <p>Fotky a delší texty lidí upravíte v modulu <a href="vedeni.php">Vedení klubu</a>, nadpis sekce na stránce Kontakt ve <a href="stranky.php?stranka=kontakt">Stránkách</a>.</p>
          </div>
        <?php else: ?>
        <div class="form-mrizka uv-mezera">
          <?php foreach ($podleSkupin[$g] as $s):
              $k = (string)$s['skey'];
              if ($s['typ'] === 'heslo'): ?>
            <div class="field field--cela<?= isset($chyby[$k]) ? ' je-chyba' : '' ?>" id="set-<?= e(preg_replace('/[^a-z0-9_-]/i', '-', $k)) ?>">
              <label for="f-nahled-heslo"><?= e($s['label'] !== '' ? $s['label'] : 'Náhledové heslo') ?></label>
              <p style="margin-bottom:8px"><?= $maHeslo ? badge('Nastavené', 'ok') . ' <span class="uv-tlumene">Heslo se z bezpečnosti nezobrazuje. Nové heslo staré nahradí.</span>' : badge('Nenastavené', 'off') . ' <span class="uv-tlumene">Na přípravné stránce se formulář pro náhled neukáže.</span>' ?></p>
              <div class="uv-heslo">
                <input type="password" id="f-nahled-heslo" name="nahled_heslo_nove" value="" autocomplete="new-password" maxlength="200" minlength="6"
                       placeholder="<?= $maHeslo ? 'Nové heslo (prázdné = beze změny)' : 'Nové heslo' ?>" aria-describedby="f-nahled-heslo-hint">
                <button type="button" class="btn btn-ghost" data-ukaz-heslo="f-nahled-heslo" aria-pressed="false" hidden>Zobrazit</button>
              </div>
              <div class="hint" id="f-nahled-heslo-hint"><?= isset($chyby[$k]) ? '<span class="uv-chyba" role="alert">' . e($chyby[$k]) . '</span> ' : '' ?><?= e((string)$s['napoveda']) ?> Aspoň 6 znaků. Změna hesla zneplatní dřívější náhledy.</div>
              <?php if ($maHeslo): ?>
                <?= pole_check('nahled_heslo_zrusit', 'Zrušit náhledové heslo', false, ['hint' => 'Náhled bez účtu přestane fungovat pro všechny.']) ?>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <?= ns_pole($s, $hodnoty[$k] ?? '', $chyby[$k] ?? null) ?>
          <?php endif; endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endforeach; ?>

  <div class="uv-nastaveni-ulozit">
    <button type="submit" class="btn btn-primary">Uložit všechny změny</button>
    <span class="hint">Uloží se všechny skupiny najednou. Když je někde chyba, neuloží se nic.</span>
  </div>
</form>
<?php endif; ?>

<?php
echo '<script src="' . e(BASE_PATH . verze('admin/assets/admin-uvod.js')) . '" defer></script>';
admin_foot();
