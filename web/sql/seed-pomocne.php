<?php
/* Pomůcky pro naplnění databáze výchozím obsahem.
   Používá je sql/seed.php (lokálně) i instalace.php (server).

   Dílčí sady jsou v sql/seed/NN-nazev.php a načítají se podle abecedy.
   Každá sada musí jít pustit opakovaně a nesmí nic přepsat:
     – seznamy plní, jen když je tabulka prázdná (seed_prazdna),
     – nastavení a bloky jen chybějící klíče (seed_nastaveni, seed_blok).
   Obrázky se berou ze zdrojů (cltk-navrhy, podklady/) a ukládají do
   uploads/ pod stálým jménem. Na serveru zdroje nejsou – tam se uploads/
   nahrají zvlášť a seed jen najde hotový soubor (obrazek_import). */

require_once __DIR__ . '/../inc/functions.php';

/* Zdroje obsahu – jen lokálně. Na serveru (MySQL) neexistují; cesta pak míří
   do neexistující složky uvnitř webu, aby is_file() nenarazilo na open_basedir. */
if (DB_DRIVER === 'sqlite') {
    defined('SEED_NAVRHY')   || define('SEED_NAVRHY', (getenv('CLTK_NAVRHY') ?: dirname(PROJEKT_ROOT, 3) . '/cltk-navrhy'));
    defined('SEED_PODKLADY') || define('SEED_PODKLADY', PROJEKT_ROOT . '/podklady');
} else {
    defined('SEED_NAVRHY')   || define('SEED_NAVRHY', WEB_ROOT . '/sql/_bez-zdroju');
    defined('SEED_PODKLADY') || define('SEED_PODKLADY', WEB_ROOT . '/sql/_bez-zdroju');
}

/** Soubor s obsahem připraveným lokálně (vyrábí sql/export-dat.php, čte instalace.php). */
defined('SEED_DATA_JSON') || define('SEED_DATA_JSON', __DIR__ . '/data.json');

/** Tabulky, které se mezi databázemi NIKDY nepřenášejí (účty, osobní údaje, statistika). */
const SEED_NEPRENASET = ['cltk_users', 'cltk_login_attempts', 'cltk_visits', 'cltk_visit_log',
                         'cltk_signups', 'cltk_prihlasky_clenstvi'];

/** Nastavení, která se nepřenášejí (tajný klíč a náhledové heslo má každá instalace vlastní,
 *  režim přípravy nastavuje instalace). */
const SEED_NASTAVENI_NEPRENASET = ['system_klic', 'nahled_heslo_hash', 'rezim_pripravy'];

/** Záznam do protokolu (seed.php ho vypisuje, instalace.php ukazuje v přehledu). */
function seed_log(string $zprava): void {
    $GLOBALS['SEED_PROTOKOL'][] = $zprava;
    if (PHP_SAPI === 'cli' && empty($GLOBALS['SEED_TICHO'])) echo $zprava, "\n";
}

/** Cesta do repozitáře návrhů (cltk-navrhy). */
function seed_navrhy(string $rel): string {
    return SEED_NAVRHY . '/' . ltrim($rel, '/');
}

/** Cesta do podkladů projektu (podklady/klient-pdf …). */
function seed_podklady(string $rel): string {
    return SEED_PODKLADY . '/' . ltrim($rel, '/');
}

/** JSON z cltk-navrhy/podklady/data (s pamětí). Když soubor chybí, vrátí []. */
function seed_json(string $soubor): array {
    static $cache = [];
    if (!isset($cache[$soubor])) {
        $cesta = seed_navrhy('podklady/data/' . $soubor);
        $cache[$soubor] = is_file($cesta) ? (json_decode((string)file_get_contents($cesta), true) ?: []) : [];
    }
    return $cache[$soubor];
}

/** Je tabulka prázdná? */
function seed_prazdna(string $tabulka): bool {
    return (int)val('SELECT COUNT(*) FROM ' . cltk_tabulka($tabulka)) === 0;
}

/** Vloží řádky (asociativní pole). Když chybí 'poradi', doplní pořadí podle pozice. Vrací počet. */
function seed_vloz(string $tabulka, array $radky): int {
    $n = 0;
    foreach (array_values($radky) as $i => $r) {
        if (!array_key_exists('poradi', $r) && seed_ma_sloupec($tabulka, 'poradi')) $r['poradi'] = $i;
        db_insert($tabulka, $r);
        $n++;
    }
    return $n;
}

/** Má tabulka sloupec? (podle schema.sql, bez dotazu do katalogu) */
function seed_ma_sloupec(string $tabulka, string $sloupec): bool {
    static $schema = null;
    if ($schema === null) $schema = (string)file_get_contents(__DIR__ . '/schema.sql');
    if (!preg_match('/CREATE TABLE ' . preg_quote($tabulka, '/') . ' \((.*?)\n\);/s', $schema, $m)) return false;
    return (bool)preg_match('/^\s*' . preg_quote($sloupec, '/') . '\s/m', $m[1]);
}

/** Data ze starého webu (texty dokumentů klubu, PDF Revue a newsletterů v uploads/, archiv turnajů,
 *  rozvrhy, jubilejní Revue): datový soubor migrace sql/migrace/2026-10-02-dokumenty-archiv.data.json
 *  (s pamětí). Sada 70-dokumenty z něj bere texty, sada 95-archiv-pdf pouští celou migraci. */
defined('SEED_ARCHIV_DATA') || define('SEED_ARCHIV_DATA', __DIR__ . '/migrace/2026-10-02-dokumenty-archiv.data.json');

function seed_archiv_data(): array {
    static $d = null;
    if ($d === null) $d = is_file(SEED_ARCHIV_DATA) ? (json_decode((string)file_get_contents(SEED_ARCHIV_DATA), true) ?: []) : [];
    return $d;
}

/** Převezme obrázek do uploads/<podslozka>/<jmeno>.jpg|png (zmenší, znovu uloží, WebP). Vrací cestu nebo ''. */
function seed_obrazek(string $zdroj, string $podslozka, string $jmeno, int $maxW = 2400, int $maxH = 2400): string {
    $rel = obrazek_import($zdroj, $podslozka, $jmeno, $maxW, $maxH);
    if ($rel === '') seed_log('  ! obrázek chybí: ' . str_replace('\\', '/', $zdroj));
    return $rel;
}

/** Zkopíruje soubor beze změny (video, PDF, hotová loga) do uploads/<cil>. Vrací cestu nebo ''. */
function seed_kopiruj(string $zdroj, string $cilRel): string {
    $cilRel = ltrim($cilRel, '/');
    $cil = UPLOAD_DIR . '/' . $cilRel;
    if (is_file($cil) && (!is_file($zdroj) || filesize($cil) === filesize($zdroj))) return $cilRel;
    if (!is_file($zdroj)) {
        seed_log('  ! soubor chybí: ' . str_replace('\\', '/', $zdroj));
        return '';
    }
    if (!is_dir(dirname($cil))) @mkdir(dirname($cil), 0775, true);
    return @copy($zdroj, $cil) ? $cilRel : '';
}

/**
 * Nastavení: vloží jen klíče, které ještě nejsou (hodnoty klubu se nepřepisují).
 * Řádek: [klíč, hodnota, popisek, skupina, typ, nápověda]
 */
function seed_nastaveni(array $radky): int {
    $n = 0;
    foreach (array_values($radky) as $i => $r) {
        [$k, $v, $label, $grp] = $r;
        $typ = $r[4] ?? 'text';
        $napoveda = $r[5] ?? '';
        if (row('SELECT skey FROM cltk_settings WHERE skey = ?', [$k])) continue;
        q('INSERT INTO cltk_settings (skey, sval, label, grp, typ, napoveda, poradi) VALUES (?,?,?,?,?,?,?)',
          [$k, (string)$v, $label, $grp, $typ, $napoveda, $i]);
        $n++;
    }
    setting_cache(null, true);
    return $n;
}

/** Textový blok stránky: vloží, jen když dvojice stránka + klíč ještě není. */
function seed_blok(string $stranka, string $klic, array $data, int $poradi = 0): bool {
    if (row('SELECT id FROM cltk_bloky WHERE stranka = ? AND klic = ?', [$stranka, $klic])) return false;
    $vychozi = ['stitek' => '', 'nadpis' => '', 'perex' => '', 'text' => '', 'foto' => '', 'foto_popisek' => '',
                'odkaz' => '', 'odkaz_text' => '', 'odkaz2' => '', 'odkaz2_text' => '', 'doplni_klub' => 0, 'visible' => 1];
    $r = array_merge($vychozi, $data, ['stranka' => $stranka, 'klic' => $klic, 'poradi' => $poradi, 'updated_at' => ted()]);
    if ($r['text'] !== '') $r['text'] = html_k_ulozeni($r['text']);
    db_insert('cltk_bloky', $r);
    return true;
}

/**
 * Naplní databázi obsahem ze sql/data.json (export z lokální databáze).
 * Do tabulky se vkládá, jen když je prázdná; u nastavení jen chybějící klíče.
 * Sloupce, které schéma nezná, se přeskočí. Nic nemaže ani nepřepisuje.
 * Vrací počet vložených řádků.
 */
function seed_import_dat(string $soubor = SEED_DATA_JSON): int {
    if (!is_file($soubor)) return 0;
    $d = json_decode((string)file_get_contents($soubor), true);
    if (!is_array($d) || !is_array($d['tabulky'] ?? null)) {
        seed_log('  ! ' . basename($soubor) . ' je poškozený – obsah se z něj nenačte.');
        return 0;
    }
    $existujici = db_tabulky();
    $celkem = 0;
    foreach ($d['tabulky'] as $tabulka => $radky) {
        $tab = cltk_tabulka((string)$tabulka);
        if (in_array($tab, SEED_NEPRENASET, true) || !is_array($radky)) continue;
        if (!in_array($tab, $existujici, true)) {
            seed_log('  ! ' . $tab . ' v databázi není – přeskočeno.');
            continue;
        }
        $jenChybejici = $tab === 'cltk_settings';
        if (!$jenChybejici && !seed_prazdna($tab)) {
            seed_log('  ' . $tab . ': už obsahuje data – ponecháno beze změny.');
            continue;
        }
        $n = 0;
        db()->beginTransaction();
        try {
            foreach ($radky as $r) {
                if (!is_array($r)) continue;
                if ($jenChybejici) {
                    $k = (string)($r['skey'] ?? '');
                    if ($k === '' || in_array($k, SEED_NASTAVENI_NEPRENASET, true)) continue;
                    if (row('SELECT skey FROM cltk_settings WHERE skey = ?', [$k])) continue;
                }
                $data = [];
                foreach ($r as $sloupec => $hodnota) {
                    if (is_string($sloupec) && seed_ma_sloupec($tab, $sloupec)) {
                        $data[$sloupec] = is_array($hodnota) ? json_ulozit($hodnota) : $hodnota;
                    }
                }
                if (!$data) continue;
                db_insert($tab, $data);
                $n++;
            }
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            seed_log('  ! ' . $tab . ': import selhal (' . $e->getMessage() . ') – tabulka zůstala prázdná.');
            continue;
        }
        if ($n > 0) seed_log('  ' . $tab . ': ' . $n . ' ' . sklonuj($n, 'řádek', 'řádky', 'řádků'));
        $celkem += $n;
    }
    setting_cache(null, true);
    return $celkem;
}

/** Spustí všechny sady sql/seed/NN-*.php podle abecedy. */
function seed_spust_sady(): void {
    $sady = glob(__DIR__ . '/seed/[0-9][0-9]-*.php') ?: [];
    sort($sady, SORT_STRING);
    foreach ($sady as $sada) {
        $jmeno = basename($sada);
        try {
            (static function (string $soubor): void { require $soubor; })($sada);
        } catch (Throwable $e) {
            seed_log('  ! sada ' . $jmeno . ' selhala: ' . $e->getMessage());
            if (PHP_SAPI === 'cli') throw $e;
        }
    }
}
