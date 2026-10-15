<?php
/* JEDNORÁZOVÁ migrace 15. 10. 2026 – výsledky hráčů ze služby Pointeks (rozhodnutí Petra: na webu
   jen výsledky z Pointeks). Založí tabulku cltk_pointeks_vysledky, do které ukládá příjem
   pointeks/feed (pointeks-feed.php) a ze které čte úvodní stránka, stránka Výsledky a modul
   administrace Výsledky (Pointeks).

   Co udělá (jen tabulka cltk_pointeks_vysledky, SQLite i MySQL, opakované spuštění nic nezmění):
     1. založí tabulku cltk_pointeks_vysledky, jen když chybí (sloupce přesně jako v sql/schema.sql),
     2. ověří unikátní index na delivery_id (každý výsledek se uloží jen jednou); když chybí, založí ho,
     3. ověří, že tabulka má všechny sloupce, které nový kód čte (chybějící jen vypíše jako POZOR, nic nemaže).
   Data se nepřenášejí – výsledky na server pošle Pointeks sám. Ruční výsledky (cltk_vysledky) zůstávají beze změny.
   Spouští se PŘED nahráním nového kódu (starý kód tabulku nepotřebuje; nový bez ní zapisuje chyby
   do data/chyby.log a příjem odpovídá 500). Funguje se starým i novým kódem webu.

   Spuštění:
     lokálně      CLTK_DB_FILE=web/data/x.sqlite ./_php/php.exe web/sql/migrace/2026-10-15-pointeks.php [--nanecisto]
     na serveru   soubor zkopírovat do kořene webu (sql/ je zvenku zavřené) a otevřít
                  …/2026-10-15-pointeks.php?token=<token>   (&nanecisto=1 = jen ukázat, co by se změnilo)
                  Po doběhnutí soubor z kořene webu smazat.
   Token (32 znaků) zná orchestrátor; repozitář je veřejný, proto je tu jen jeho otisk SHA-256. */
const MIGRACE_TOKEN_SHA256 = '237ad8af9422d33ad41a6b27b2bc9b819cfece706a57ee85732ae1c923578e4c';

if (PHP_SAPI !== 'cli') {
    $token = $_GET['token'] ?? '';
    if (!is_string($token) || strlen($token) !== 32 || !hash_equals(MIGRACE_TOKEN_SHA256, hash('sha256', $token))) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        exit("403 – bez platného tokenu se migrace nespustí.\n");
    }
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    @set_time_limit(60);
    ignore_user_abort(true);
}
$jadro = '';
for ($d = __DIR__, $i = 0; $i <= 4; $i++, $d = dirname($d)) {
    if (is_file($d . '/inc/functions.php') && is_file($d . '/inc/db.php')) { $jadro = $d . '/inc/functions.php'; break; }
}
if ($jadro === '') { echo "CHYBA: nenašel jsem inc/functions.php (soubor patří do kořene webu nebo do sql/migrace/).\n"; exit(1); }
require_once $jadro;
$nanecisto = PHP_SAPI === 'cli' ? in_array('--nanecisto', (array)($argv ?? []), true) : (($_GET['nanecisto'] ?? '') === '1');

const PK_MIGRACE_TABULKA = 'cltk_pointeks_vysledky';
/** Totéž jako CREATE TABLE cltk_pointeks_vysledky v sql/schema.sql (při změně upravit obojí).
 *  Zápis pro konkrétní databázi doplní pk_migrace_ddl() stejně jako db_install(). */
const PK_MIGRACE_DDL = "CREATE TABLE IF NOT EXISTS cltk_pointeks_vysledky (
  id                  INTEGER PRIMARY KEY AUTO_INCREMENT,
  delivery_id         VARCHAR(100) NOT NULL,
  event               VARCHAR(40)  NOT NULL DEFAULT 'match.result',
  stav                VARCHAR(10)  NOT NULL DEFAULT 'dohrano',
  skupina             VARCHAR(40)  NOT NULL DEFAULT '',
  player_name         VARCHAR(160) NOT NULL DEFAULT '',
  player_slug         VARCHAR(120) NOT NULL DEFAULT '',
  partner_name        VARCHAR(160) NOT NULL DEFAULT '',
  partner_country     VARCHAR(8)   NOT NULL DEFAULT '',
  opponents_json      TEXT         NOT NULL DEFAULT '',
  result              VARCHAR(8)   NOT NULL DEFAULT '',
  outcome             VARCHAR(12)  NOT NULL DEFAULT '',
  score               VARCHAR(120) NOT NULL DEFAULT '',
  sets_json           TEXT         NOT NULL DEFAULT '',
  discipline          VARCHAR(10)  NOT NULL DEFAULT '',
  round               VARCHAR(60)  NOT NULL DEFAULT '',
  qualifying          INTEGER      NOT NULL DEFAULT 0,
  tournament_name     VARCHAR(200) NOT NULL DEFAULT '',
  tournament_category VARCHAR(120) NOT NULL DEFAULT '',
  age_category        VARCHAR(20)  NULL,
  tournament_city     VARCHAR(120) NOT NULL DEFAULT '',
  tournament_country  VARCHAR(8)   NOT NULL DEFAULT '',
  tournament_surface  VARCHAR(12)  NOT NULL DEFAULT '',
  tournament_start    DATE         NULL,
  completed_at        DATETIME     NULL,
  url                 VARCHAR(255) NOT NULL DEFAULT '',
  raw_json            MEDIUMTEXT   NULL,
  visible             INTEGER      NOT NULL DEFAULT 1,
  received_at         DATETIME     NULL,
  updated_at          DATETIME     NULL,
  UNIQUE (delivery_id)
)";
const PK_MIGRACE_SLOUPCE = ['id', 'delivery_id', 'event', 'stav', 'skupina', 'player_name', 'player_slug', 'partner_name',
    'partner_country', 'opponents_json', 'result', 'outcome', 'score', 'sets_json', 'discipline', 'round', 'qualifying',
    'tournament_name', 'tournament_category', 'age_category', 'tournament_city', 'tournament_country', 'tournament_surface',
    'tournament_start', 'completed_at', 'url', 'raw_json', 'visible', 'received_at', 'updated_at'];

/** DDL pro tuto databázi: MySQL – TEXT s výchozí hodnotou podle verze, InnoDB, utf8mb4_czech_ci; SQLite – AUTOINCREMENT. */
function pk_migrace_ddl(): string {
    $sql = PK_MIGRACE_DDL;
    if (db_je_mysql()) {
        $sql = (string)preg_replace("/\bTEXT(\s+)NOT NULL DEFAULT ''/", db_mysql_text_vychozi(), $sql);
        return $sql . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci';
    }
    return str_replace('INTEGER PRIMARY KEY AUTO_INCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
}

/** Sloupce tabulky (malými písmeny). */
function pk_migrace_sloupce(): array {
    if (db_je_mysql()) {
        $r = rows('SELECT column_name AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?', [PK_MIGRACE_TABULKA]);
        return array_map(static fn(array $x): string => strtolower((string)($x['c'] ?? $x['C'] ?? $x['COLUMN_NAME'] ?? '')), $r);
    }
    $sql = (string)val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [PK_MIGRACE_TABULKA]);
    $v = [];
    foreach (PK_MIGRACE_SLOUPCE as $s) {
        if (preg_match('/[(,]\s*[`"\[]?' . preg_quote($s, '/') . '[`"\]]?\s/i', $sql)) $v[] = $s;
    }
    return $v;
}

/** Má tabulka unikátní index na delivery_id? */
function pk_migrace_ma_unikat(): bool {
    if (db_je_mysql()) {
        return (int)val('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? AND non_unique = 0',
            [PK_MIGRACE_TABULKA, 'delivery_id']) > 0;
    }
    $tabulka = (string)val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [PK_MIGRACE_TABULKA]);
    if (preg_match('/UNIQUE\s*\(\s*[`"\[]?delivery_id[`"\]]?\s*\)/i', $tabulka) || preg_match('/[(,]\s*delivery_id\s[^,]*\bUNIQUE\b/i', $tabulka)) return true;
    foreach (rows("SELECT sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ?", [PK_MIGRACE_TABULKA]) as $ix) {
        $s = (string)($ix['sql'] ?? '');
        if (preg_match('/^CREATE\s+UNIQUE\s+INDEX\b.*\(\s*[`"\[]?delivery_id[`"\]]?\s*\)\s*$/is', $s)) return true;
    }
    return false;
}

echo 'Migrace 2026-10-15-pointeks · databáze: ', db_je_mysql() ? 'mysql' : 'sqlite', $nanecisto ? ' · NANEČISTO (nic se nezapíše)' : '', "\n\n";
$zmen = 0;
$pozor = 0;

/* 1) tabulka */
$ma = in_array(PK_MIGRACE_TABULKA, db_tabulky(), true);
if ($ma) {
    echo "1) Tabulka cltk_pointeks_vysledky už existuje – přeskočeno.\n";
} elseif ($nanecisto) {
    echo "1) Tabulka cltk_pointeks_vysledky by se založila (" . count(PK_MIGRACE_SLOUPCE) . " sloupců, unikátní delivery_id).\n";
    $zmen++;
} else {
    db_exec(pk_migrace_ddl());
    if (!in_array(PK_MIGRACE_TABULKA, db_tabulky(), true)) { echo "CHYBA: tabulku se nepodařilo založit.\n"; exit(1); }
    echo "1) Tabulka cltk_pointeks_vysledky založena (" . count(PK_MIGRACE_SLOUPCE) . " sloupců, unikátní delivery_id).\n";
    $zmen++;
    $ma = true;
}

/* 2) unikátní index na delivery_id */
if (!$ma) {
    echo "2) Unikátní index na delivery_id vznikne s tabulkou.\n";
} elseif (pk_migrace_ma_unikat()) {
    echo "2) Unikátní index na delivery_id je – v pořádku.\n";
} elseif ($nanecisto) {
    echo "2) Unikátní index na delivery_id chybí – založil by se (cltk_pointeks_vysledky_delivery).\n";
    $zmen++;
} else {
    try {
        db_exec('CREATE UNIQUE INDEX cltk_pointeks_vysledky_delivery ON cltk_pointeks_vysledky (delivery_id)');
        echo "2) Unikátní index na delivery_id založen (cltk_pointeks_vysledky_delivery).\n";
        $zmen++;
    } catch (Throwable $e) {
        echo "2) POZOR: unikátní index se nepodařilo založit (" . $e->getMessage() . ") – v tabulce jsou nejspíš stejná delivery_id dvakrát.\n";
        $pozor++;
    }
}

/* 3) sloupce, které čte nový kód */
if ($ma) {
    $chybi = array_values(array_diff(PK_MIGRACE_SLOUPCE, pk_migrace_sloupce()));
    if ($chybi) {
        echo "3) POZOR: tabulce chybí sloupce " . implode(', ', $chybi) . " – nic jsem nemazal ani neměnil, je potřeba to vyřešit ručně.\n";
        $pozor++;
    } else {
        echo "3) Sloupce v pořádku (" . count(PK_MIGRACE_SLOUPCE) . ").\n";
    }
} else {
    echo "3) Sloupce se ověří po založení tabulky.\n";
}

echo "\nZměn: $zmen", $pozor ? " · POZOR: $pozor" : '', "\n";
echo $nanecisto ? "HOTOVO (nanečisto – nic se nezapsalo)\n" : "HOTOVO\n";
exit($pozor ? 1 : 0);
