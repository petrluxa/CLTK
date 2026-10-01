<?php
/* Regresní test pojistky sdílené databáze (sql_pojistka() ve web/inc/db.php).
   NIC se neprovádí – zkouší se jen řetězce SQL.

     ./_php/php.exe nastroje/test-pojistka.php

   1. Dotazy, které MUSÍ být odmítnuté (cizí tabulky TK Olymp v jakékoli podobě,
      zakázané příkazy) – včetně obchvatů nalezených při revizi 1. 10. 2026.
   2. Dotazy, které MUSÍ projít (tabulky cltk_, čtení katalogu).
   3. Všechny doslovné SQL z kódu webu (web/**.php) – pojistka je nesmí odmítnout.
   Návratový kód 0 = vše v pořádku, 1 = chyba. */

if (PHP_SAPI !== 'cli') exit;
require __DIR__ . '/../web/inc/db.php';

$chyb = 0;
$ok = 0;
function odmitne(string $sql): bool {
    try { sql_pojistka($sql); return false; } catch (RuntimeException $e) { return true; }
}

$odmitnout = [
    // revize 1. 10. 2026 (podklady/_raw/qa/revize-bezpecnost/pojistka.php)
    'SELECT * FROM uzivatele',
    'SELECT * FROM cltk_users, uzivatele',
    'SELECT * FROM cltk_users c, `uzivatele` u',
    'SELECT * FROM cltk_users WHERE id IN (SELECT id FROM uzivatele)',
    'SELECT (SELECT heslo FROM uzivatele LIMIT 1)',
    'UPDATE cltk_users, uzivatele SET uzivatele.heslo = 1',
    'DELETE cltk_users, uzivatele FROM cltk_users JOIN uzivatele',
    'DELETE uzivatele FROM cltk_users, uzivatele',
    'INSERT INTO cltk_x SELECT * FROM uzivatele',
    'SELECT * FROM f201572.uzivatele',
    'SELECT * FROM `f201572`.`uzivatele`',
    'SELECT * FROM/**/uzivatele',
    "SELECT * FROM\nuzivatele",
    'SELECT * FROM (uzivatele)',
    'SELECT * FROM cltk_users NATURAL JOIN(uzivatele)',
    'DROP TABLE uzivatele',
    'TRUNCATE uzivatele',
    'ALTER TABLE uzivatele ADD x INT',
    'CREATE TABLE cltk_x AS SELECT * FROM uzivatele',
    'RENAME TABLE uzivatele TO cltk_x',
    'CREATE VIEW cltk_v AS SELECT 1',
    'LOAD DATA INFILE "/etc/passwd" INTO TABLE uzivatele',
    'SELECT LOAD_FILE("/etc/passwd")',
    'SELECT 1 INTO OUTFILE "/www/x.php"',
    'HANDLER uzivatele OPEN',
    'SHOW TABLES',
    'DESCRIBE uzivatele',
    'DESC uzivatele',
    'EXPLAIN SELECT * FROM cltk_users',
    'CALL smaz_vse()',
    'SET @x = 1',
    'SET GLOBAL general_log = 1',
    'WITH x AS (SELECT 1) SELECT * FROM x',
    'SELECT * FROM cltk_users UNION SELECT * FROM uzivatele',
    "SELECT * FROM cltk_users WHERE a = \"it's\" OR b IN (SELECT 1 FROM uzivatele)",
    // další obchvaty
    'SELECT * FROM cltk_users a JOIN cltk_users b ON a.id = b.id, uzivatele',
    'SELECT * FROM cltk_users, (SELECT 1) x, uzivatele',
    'SELECT * FROM ((uzivatele))',
    'SELECT * FROM cltk_users JOIN (cltk_akce, uzivatele)',
    'SELECT * FROM cltk_users USE INDEX (i), uzivatele',
    'SELECT 1 /* \' */ FROM uzivatele /* \' */',
    'SELECT 1--1 FROM uzivatele',
    'SELECT 1 /*!50000 , (SELECT heslo FROM uzivatele) */',
    "SELECT * FROM cltk_users WHERE a = 'x\\' FROM uzivatele'",
    'INSERT uzivatele (heslo) VALUES (1)',
    'REPLACE uzivatele (heslo) VALUES (1)',
    'INSERT LOW_PRIORITY uzivatele SET heslo = 1',
    'UPDATE LOW_PRIORITY uzivatele SET heslo = 1',
    'UPDATE cltk_users u JOIN uzivatele x ON x.id = u.id SET x.heslo = 1',
    'DELETE FROM cltk_users USING cltk_users JOIN uzivatele',
    'DELETE QUICK FROM uzivatele',
    'CREATE TABLE cltk_x LIKE uzivatele',
    'CREATE TABLE cltk_x (id INT REFERENCES uzivatele(id))',
    'ALTER TABLE cltk_x RENAME TO uzivatele',
    'ALTER TABLE cltk_x RENAME uzivatele',
    'DROP TABLE cltk_x, uzivatele',
    'RENAME TABLE cltk_a TO cltk_b, uzivatele TO cltk_c',
    'CREATE TRIGGER t BEFORE INSERT ON cltk_x FOR EACH ROW SET @a = 1',
    'CREATE PROCEDURE p() SELECT 1',
    'LOCK TABLES uzivatele WRITE',
    'TABLE uzivatele',
    'VALUES ROW(1)',
    'SELECT * FROM cltk_users; DROP TABLE uzivatele',
    'SELECT * FROM cltk_users; SELECT * FROM cltk_akce',
    'SELECT * FROM mysql.user',
    'INSERT INTO cltk_x SELECT * FROM information_schema.tables',
    'GRANT ALL ON *.* TO x',
    'CREATE DATABASE x',
    "DELETE FROM /* komentář */ uzivatele",
    'delete from f201572.uzivatele',
    'SELECT * FROM "uzivatele"',
    'SELECT * FROM [uzivatele]',
    'SELECT * FROM cltk_users WHERE id = 1 # ; SELECT 1' . "\n" . 'UNION SELECT * FROM uzivatele',
];
$propustit = [
    'SELECT * FROM cltk_users',
    "SELECT name FROM sqlite_master WHERE type = 'table' AND name LIKE 'cltk_%'",
    "SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'cltk\\_%'",
    "SELECT * FROM cltk_akce WHERE nazev = 'DELETE FROM uzivatele'",
    'INSERT INTO cltk_signups (jmeno) VALUES (?)',
    'UPDATE cltk_settings SET sval = ? WHERE skey = ?',
    'CREATE TABLE IF NOT EXISTS cltk_test (id INTEGER PRIMARY KEY AUTOINCREMENT, a TEXT NOT NULL DEFAULT \'\', UNIQUE (id, a))',
    'CREATE UNIQUE INDEX cltk_i ON cltk_test (a)',
    'ALTER TABLE cltk_test ADD COLUMN b TEXT',
    'SELECT COALESCE(SUM(pocet), 0) FROM cltk_signups WHERE akce_id = ?',
    "SELECT * FROM cltk_akce WHERE popis LIKE '%from clanky%'",
    'SELECT s.*, a.nazev FROM cltk_signups s LEFT JOIN cltk_akce a ON a.id = s.akce_id WHERE s.vyrizeno = 0 ORDER BY s.created_at DESC, s.id DESC LIMIT 50',
    'SELECT * FROM cltk_akce WHERE id IN (SELECT akce_id FROM cltk_signups) AND rok = ?',
    'INSERT INTO cltk_visits (day, path, hits) VALUES (?,?,1) ON DUPLICATE KEY UPDATE hits = hits + 1',
    'INSERT INTO cltk_visits (day, path, hits) VALUES (?,?,1) ON CONFLICT(day, path) DO UPDATE SET hits = hits + 1',
    'INSERT IGNORE INTO cltk_visit_log (day, visitor) VALUES (?,?)',
    'INSERT OR IGNORE INTO cltk_visit_log (day, visitor) VALUES (?,?)',
    'DELETE FROM cltk_login_attempts WHERE tried_at < ?',
    'SELECT day, COUNT(*) AS c FROM cltk_visit_log WHERE day >= ? GROUP BY day',
    'SELECT 1 FROM DUAL',
    "SELECT * FROM cltk_users WHERE a = 'x' -- ' FROM uzivatele",
    'SET NAMES utf8mb4',
    '(SELECT id FROM cltk_akce) UNION (SELECT id FROM cltk_aktuality)',
    'SELECT * FROM cltk_akce;',
];

echo "Pojistka musí odmítnout:\n";
foreach ($odmitnout as $sql) {
    if (odmitne($sql)) { $ok++; } else { $chyb++; echo "  CHYBA – prošlo: $sql\n"; }
}
echo "Pojistka musí propustit:\n";
foreach ($propustit as $sql) {
    try { sql_pojistka($sql); $ok++; } catch (RuntimeException $e) { $chyb++; echo "  CHYBA – odmítnuto: $sql\n    " . $e->getMessage() . "\n"; }
}

/* Doslovné SQL z kódu webu: řetězce začínající SELECT/INSERT/UPDATE/DELETE/CREATE
   (spojené s proměnnými jen do první proměnné – zbytek doplní „cltk_x“). */
echo "Doslovné SQL z kódu webu:\n";
$soubory = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/web', FilesystemIterator::SKIP_DOTS));
$pocet = 0;
foreach ($soubory as $soubor) {
    $cesta = str_replace('\\', '/', $soubor->getPathname());
    if (!str_ends_with($cesta, '.php') || str_contains($cesta, '/uploads/') || str_contains($cesta, '/data/')) continue;
    foreach (token_get_all((string)file_get_contents($cesta)) as $tok) {
        if (!is_array($tok) || $tok[0] !== T_CONSTANT_ENCAPSED_STRING) continue;
        $s = stripcslashes(substr($tok[1], 1, -1));
        if ($tok[1][0] === "'") $s = str_replace(["\\'", '\\\\'], ["'", '\\'], substr($tok[1], 1, -1));
        if (!preg_match('~^\s*(SELECT|INSERT|UPDATE|DELETE)\s+.*\b(FROM|INTO|SET)\b~is', $s)) continue;
        if (preg_match('~\b(FROM|INTO|JOIN|UPDATE)\s*$~i', $s)) $s .= ' cltk_x';     // „… FROM ' . cltk_tabulka($t)“
        if (preg_match('~\b(WHERE|AND|OR|ORDER BY|SET|IN|LIMIT)\s*$~i', $s) || substr_count($s, '(') !== substr_count($s, ')')) continue;  // neúplný kus
        $pocet++;
        try { sql_pojistka($s); $ok++; }
        catch (RuntimeException $e) { $chyb++; echo "  CHYBA – odmítnuto (" . basename($cesta) . "): $s\n    " . $e->getMessage() . "\n"; }
    }
}
echo "  prověřeno $pocet dotazů z kódu\n";

echo $chyb === 0 ? "HOTOVO: $ok kontrol v pořádku.\n" : "CHYBY: $chyb (v pořádku $ok).\n";
exit($chyb === 0 ? 0 : 1);
