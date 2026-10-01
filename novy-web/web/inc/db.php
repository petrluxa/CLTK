<?php
/* Připojení k databázi a zkratky pro dotazy.

   POJISTKA PRO SDÍLENOU DATABÁZI
   Na serveru je databáze společná s ostrým webem TK Olymp. Každý dotaz
   proto prochází funkcí sql_pojistka(): příkaz, který zakládá, mění nebo
   maže (CREATE, ALTER, DROP, TRUNCATE, DELETE, INSERT, UPDATE, REPLACE,
   RENAME), i čtení (FROM, JOIN) smí jít jen na tabulku s předponou cltk_.
   DROP TABLE se na MySQL neprovede nikdy. Do databáze proto choďte jen
   přes q() / rows() / row() / val() / db_exec(), ne přes db() napřímo. */

require_once __DIR__ . '/config.php';

const CLTK_PREDPONA = 'cltk_';

/** Systémové katalogy, které smí číst zjišťování stavu databáze. */
const SQL_SYSTEMOVE = ['sqlite_master', 'sqlite_schema', 'sqlite_sequence', 'information_schema', 'dual'];

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (DB_DRIVER === 'mysql') {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_czech_ci");
    } else {
        $dir = dirname(DB_FILE);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $opt[PDO::ATTR_TIMEOUT] = 5;                 // dva lokální servery nad jedním souborem
        $pdo = new PDO('sqlite:' . DB_FILE, null, null, $opt);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
    }
    return $pdo;
}

function db_je_mysql(): bool {
    return DB_DRIVER === 'mysql';
}

/** Ověří název tabulky – musí mít předponu cltk_ a jen bezpečné znaky. Vrací ho zpět. */
function cltk_tabulka(string $nazev): string {
    $n = trim($nazev, " `\"[]");
    if (!preg_match('/^' . CLTK_PREDPONA . '[a-z0-9_]+$/', $n)) {
        throw new RuntimeException('Pojistka databáze: tabulka „' . $nazev . '“ nemá předponu ' . CLTK_PREDPONA . ' – odmítnuto.');
    }
    return $n;
}

/**
 * Pojistka sdílené databáze. Rozloží SQL na slova (bez řetězců a komentářů –
 * jednou podle pravidel MySQL, jednou podle SQLite, obojí musí projít) a ověří
 * KAŽDOU tabulku, na kterou příkaz sahá: všechny tabulky v seznamu za FROM,
 * JOIN i za čárkou (i za podmínkou ON), v závorkách („FROM (x)“, „JOIN(x)“),
 * cíle UPDATE, DELETE, INSERT/REPLACE (i bez INTO), INTO, REFERENCES, LIKE
 * u CREATE TABLE, tabulky u CREATE/ALTER/DROP/TRUNCATE/RENAME a CREATE INDEX … ON.
 * Povolené jsou jen příkazy SELECT, INSERT, UPDATE, DELETE, REPLACE, CREATE,
 * ALTER, DROP, TRUNCATE, RENAME a SET NAMES – HANDLER, DESCRIBE, SHOW, LOAD,
 * CALL, EXPLAIN, WITH, LOCK, GRANT … se odmítnou vždy, stejně jako víc příkazů
 * najednou, spustitelný komentář MySQL („/*!“) a LOAD_FILE().
 * Při porušení vyhodí výjimku (dotaz se neprovede).
 * Regresní test: nastroje/test-pojistka.php.
 */
function sql_pojistka(string $sql): void {
    if (preg_match('~/\*[!+]~', $sql)) {
        throw new RuntimeException('Pojistka databáze: spustitelný komentář MySQL je zakázaný – odmítnuto.');
    }
    foreach ([true, false] as $podleMysql) {
        sql_pojistka_slova(sql_pojistka_rozloz($sql, $podleMysql));
    }
}

/**
 * SQL → seznam slov a znamének. Řetězce se nahradí prázdným '' (jejich obsah
 * se nikdy neprovede), komentáře mezerou; identifikátory v `…` (a v SQLite
 * v "…" a […]) zůstanou jako slova. $podleMysql: \ v řetězci escapuje,
 * "…" je řetězec, # je komentář, „--“ je komentář jen s mezerou za sebou.
 */
function sql_pojistka_rozloz(string $sql, bool $podleMysql): array {
    $n = strlen($sql);
    $s = '';
    for ($i = 0; $i < $n;) {
        $c = $sql[$i];
        $dva = substr($sql, $i, 2);
        if ($dva === '/*') {
            $k = strpos($sql, '*/', $i + 2);
            $i = $k === false ? $n : $k + 2;
            $s .= ' ';
            continue;
        }
        if (($dva === '--' && (!$podleMysql || $i + 2 >= $n || ord($sql[$i + 2]) <= 32)) || ($podleMysql && $c === '#')) {
            $k = strpos($sql, "\n", $i);
            $i = $k === false ? $n : $k + 1;
            $s .= ' ';
            continue;
        }
        if ($c === "'" || $c === '"' || $c === '`' || (!$podleMysql && $c === '[')) {
            $konec = $c === '[' ? ']' : $c;
            $obsah = '';
            $j = $i + 1;
            while ($j < $n) {
                $d = $sql[$j];
                if ($podleMysql && $d === '\\' && $c !== '`') { $obsah .= substr($sql, $j, 2); $j += 2; continue; }
                if ($d === $konec) {
                    if ($c !== '[' && $j + 1 < $n && $sql[$j + 1] === $konec) { $obsah .= $d; $j += 2; continue; }
                    break;
                }
                $obsah .= $d;
                $j++;
            }
            $i = $j + 1;
            $identifikator = $c === '`' || $c === '[' || ($c === '"' && !$podleMysql);
            $s .= $identifikator ? ' ' . $obsah . ' ' : " '' ";
            continue;
        }
        $s .= $c;
        $i++;
    }
    $s = (string)preg_replace('~(?<=[\p{L}\p{N}_$])\s*\.\s*(?=[\p{L}\p{N}_$*])~u', '.', $s);   // db . tabulka → db.tabulka
    preg_match_all("~[\\p{L}\\p{N}_$]+(?:\\.(?:[\\p{L}\\p{N}_$]+|\\*))*|''|\\S~u", $s, $m);
    return $m[0];
}

/** Ověří jeden název tabulky (i „databáze.tabulka“). $jenCteni: smí to být systémový katalog. */
function sql_pojistka_tabulka(string $slovo, bool $jenCteni): void {
    $t = strtolower($slovo);
    $zaklad = str_contains($t, '.') ? substr($t, 0, strpos($t, '.')) : $t;
    $jmeno = str_contains($t, '.') ? substr($t, strrpos($t, '.') + 1) : $t;
    if ($jenCteni && (in_array($zaklad, SQL_SYSTEMOVE, true) || in_array($t, SQL_SYSTEMOVE, true))) return;
    if (!str_starts_with($jmeno, CLTK_PREDPONA)) {
        throw new RuntimeException('Pojistka databáze: dotaz sahá na tabulku „' . $slovo . '“ bez předpony ' . CLTK_PREDPONA . ' – odmítnuto.');
    }
}

/** Projde slova jednoho příkazu (viz sql_pojistka). */
function sql_pojistka_slova(array $t): void {
    $n = count($t);
    $U = array_map('strtoupper', $t);
    $jeSlovo = static fn(int $i): bool => $i < $n && $t[$i] !== "''" && (bool)preg_match('~^[\p{L}\p{N}_$]~u', $t[$i]);
    $chyba = static function (string $proc): never {
        throw new RuntimeException('Pojistka databáze: ' . $proc . ' – odmítnuto.');
    };

    // první slovo příkazu (i „(SELECT …) UNION (SELECT …)“)
    $p = 0;
    while ($p < $n && $t[$p] === '(') $p++;
    $prikaz = $U[$p] ?? '';
    if ($prikaz === 'DROP' && db_je_mysql()) $chyba('DROP se na serveru neprovádí nikdy');
    if (!in_array($prikaz, ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'REPLACE', 'CREATE', 'ALTER', 'DROP', 'TRUNCATE', 'RENAME', 'SET'], true)) {
        $chyba('příkaz „' . ($t[$p] ?? '') . '“ není povolený');
    }
    for ($i = 0; $i < $n; $i++) {
        if ($t[$i] === ';' && $i < $n - 1) $chyba('víc příkazů najednou');
        if ($U[$i] === 'LOAD_FILE') $chyba('LOAD_FILE()');
    }
    if ($prikaz === 'SET') {
        if (($U[$p + 1] ?? '') !== 'NAMES' || in_array(',', $t, true)) $chyba('SET je povolený jen jako SET NAMES');
        return;
    }
    if (in_array($prikaz, ['CREATE', 'DROP', 'ALTER'], true)) {
        $druh = $U[$p + 1] ?? '';
        if (in_array($druh, ['TEMPORARY', 'UNIQUE', 'IGNORE'], true)) $druh = $U[$p + 2] ?? '';
        if (!in_array($druh, ['TABLE', 'INDEX'], true)) $chyba($prikaz . ' ' . $druh . ' je zakázaný (jen TABLE a INDEX)');
    }
    $cteni = $prikaz === 'SELECT';

    /* místa, kde stojí jeden název tabulky */
    $jedna = static function (int $i) use ($t, $jeSlovo, $cteni, $chyba): void {
        if (!$jeSlovo($i)) $chyba('za „' . ($t[$i - 1] ?? '') . '“ nestojí název tabulky');
        sql_pojistka_tabulka($t[$i], $cteni);
    };
    for ($i = $p; $i < $n; $i++) {
        $u = $U[$i];
        if ($u === 'INTO' || $u === 'REFERENCES') $jedna($i + 1);
        if ($u === 'LIKE' && $prikaz === 'CREATE') $jedna($i + 1);
        if ($u === 'TABLE' || $u === 'TABLES') {
            $j = $i + 1;
            if (($U[$j] ?? '') === 'IF') { $j++; if (($U[$j] ?? '') === 'NOT') $j++; if (($U[$j] ?? '') === 'EXISTS') $j++; }
            $jedna($j);
            for ($j++; $j < $n; $j++) {                      // DROP TABLE a, b · RENAME TABLE a TO b, c TO d
                if ($t[$j] === ',' || $U[$j] === 'TO') { $jedna($j + 1); $j++; continue; }
                break;
            }
        }
        if ($u === 'RENAME' && $prikaz === 'ALTER' && !in_array($U[$i + 1] ?? '', ['COLUMN', 'INDEX', 'KEY'], true)) {
            $j = $i + 1;
            if (in_array($U[$j] ?? '', ['TO', 'AS'], true)) $j++;
            $jedna($j);
        }
        if ($u === 'TRUNCATE' && $i === $p && ($U[$i + 1] ?? '') !== 'TABLE') $jedna($i + 1);
        if ($u === 'ON' && ($prikaz === 'CREATE' || $prikaz === 'DROP') && in_array('INDEX', array_slice($U, $p, 4), true)) {
            $jedna($i + 1);
        }
        if (($u === 'INSERT' || $u === 'REPLACE') && $i === $p) {
            $j = $i + 1;
            while (in_array($U[$j] ?? '', ['LOW_PRIORITY', 'DELAYED', 'HIGH_PRIORITY', 'IGNORE'], true)) $j++;
            if (($U[$j] ?? '') === 'OR') $j += 2;                // SQLite: INSERT OR IGNORE
            if (($U[$j] ?? '') !== 'INTO') $jedna($j);         // MySQL dovolí „INSERT tabulka (…)“ bez INTO
        }
    }

    /* Seznamy tabulek: FROM …, JOIN …, UPDATE …, DELETE t1, t2 FROM …, DELETE … USING …
       Oblast = seznam na jedné úrovni závorek; název tabulky se čeká na začátku,
       za čárkou a za JOIN (i když předtím byla podmínka ON). Závorka na místě
       tabulky je buď poddotaz (jeho FROM se ověří zvlášť), nebo skupina tabulek. */
    $konce = ['WHERE', 'GROUP', 'ORDER', 'LIMIT', 'HAVING', 'UNION', 'EXCEPT', 'INTERSECT', 'WINDOW', 'FOR', 'LOCK', 'INTO',
              'PROCEDURE', 'SET', 'VALUES', 'VALUE', 'SELECT', 'RETURNING'];
    $oblasti = [];
    $hloubka = 0;

    $j0 = $p + 1;
    if ($prikaz === 'UPDATE') {
        while (in_array($U[$j0] ?? '', ['LOW_PRIORITY', 'IGNORE'], true)) $j0++;
        if (($U[$j0] ?? '') === 'OR') $j0 += 2;
        $oblasti[] = ['hloubka' => 0, 'cekam' => true];
    } elseif ($prikaz === 'DELETE') {
        while (in_array($U[$j0] ?? '', ['LOW_PRIORITY', 'QUICK', 'IGNORE'], true)) $j0++;
        if (($U[$j0] ?? '') !== 'FROM') $oblasti[] = ['hloubka' => 0, 'cekam' => true];   // DELETE t1, t2 FROM …
    }
    for ($i = $j0; $i < $n; $i++) {
        $x = $t[$i];
        $u = $U[$i];
        $posl = count($oblasti) - 1;
        $o = $posl >= 0 ? $oblasti[$posl] : null;

        if ($x === '(') {
            if ($o !== null && $o['hloubka'] === $hloubka && $o['cekam']) {
                $oblasti[$posl]['cekam'] = false;
                if (!in_array($U[$i + 1] ?? '', ['SELECT', 'WITH', 'VALUES', 'TABLE'], true)) {
                    $oblasti[] = ['hloubka' => $hloubka + 1, 'cekam' => true];      // „FROM (a, b)“, „JOIN(x)“
                }
            }
            $hloubka++;
            continue;
        }
        if ($x === ')') {
            $hloubka--;
            while ($oblasti && $oblasti[count($oblasti) - 1]['hloubka'] > $hloubka) array_pop($oblasti);
            continue;
        }
        if ($u === 'FROM' || ($u === 'USING' && $prikaz === 'DELETE' && $hloubka === 0)) {
            while ($oblasti && $oblasti[count($oblasti) - 1]['hloubka'] >= $hloubka) array_pop($oblasti);
            $oblasti[] = ['hloubka' => $hloubka, 'cekam' => true];
            continue;
        }
        if ($o === null || $o['hloubka'] !== $hloubka) continue;     // hlubší úroveň (podmínka, poddotaz) patří jinam
        if ($o['cekam']) {
            if (!$jeSlovo($i)) $chyba('za FROM/JOIN/čárkou nestojí název tabulky („' . $x . '“)');
            sql_pojistka_tabulka($x, $cteni);
            $oblasti[$posl]['cekam'] = false;
            continue;
        }
        if (in_array($u, $konce, true)) {
            array_pop($oblasti);
            continue;
        }
        if ($x === ',' || $u === 'JOIN' || $u === 'STRAIGHT_JOIN') {
            $oblasti[$posl]['cekam'] = true;
        }
    }
}

/* --- zkratky pro dotazy --- */
function q(string $sql, array $p = []): PDOStatement {
    sql_pojistka($sql);
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st;
}
function rows(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function row(string $sql, array $p = []): ?array  { $r = q($sql, $p)->fetch(); return $r ?: null; }
function val(string $sql, array $p = [])          { $r = q($sql, $p)->fetch(PDO::FETCH_NUM); return $r ? $r[0] : null; }
function last_id(): int { return (int)db()->lastInsertId(); }

/** Provede příkaz bez parametrů (DDL) – taky přes pojistku. */
function db_exec(string $sql): int {
    sql_pojistka($sql);
    return (int)db()->exec($sql);
}

/** Vloží řádek z asociativního pole, vrátí nové id. Názvy sloupců se ověří. */
function db_insert(string $tabulka, array $data): int {
    $t = cltk_tabulka($tabulka);
    $sloupce = array_keys($data);
    foreach ($sloupce as $s) {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $s)) throw new RuntimeException('Neplatný sloupec: ' . $s);
    }
    q('INSERT INTO ' . $t . ' (' . implode(', ', $sloupce) . ') VALUES (' . implode(', ', array_fill(0, count($sloupce), '?')) . ')',
      array_values($data));
    return last_id();
}

/** Upraví řádek podle id z asociativního pole. */
function db_update(string $tabulka, int $id, array $data): void {
    $t = cltk_tabulka($tabulka);
    if (!$data) return;
    $casti = [];
    foreach (array_keys($data) as $s) {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $s)) throw new RuntimeException('Neplatný sloupec: ' . $s);
        $casti[] = $s . ' = ?';
    }
    $p = array_values($data);
    $p[] = $id;
    q('UPDATE ' . $t . ' SET ' . implode(', ', $casti) . ' WHERE id = ?', $p);
}

/** Seznam existujících tabulek cltk_ (jiné se ani nevypisují). */
function db_tabulky(): array {
    if (db_je_mysql()) {
        $r = rows("SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'cltk\\_%'");
    } else {
        $r = rows("SELECT name AS t FROM sqlite_master WHERE type = 'table' AND name LIKE 'cltk_%'");
    }
    $vysledek = [];
    foreach ($r as $x) {
        $n = (string)($x['t'] ?? $x['T'] ?? $x['TABLE_NAME'] ?? '');
        if (str_starts_with($n, CLTK_PREDPONA)) $vysledek[] = $n;
    }
    sort($vysledek);
    return $vysledek;
}

/** Je databáze už založená? */
function db_installed(): bool {
    try {
        return in_array('cltk_settings', db_tabulky(), true);
    } catch (Throwable $e) {
        return false;
    }
}

/** Jak zapsat výchozí '' u sloupce TEXT na tomhle MySQL. */
function db_mysql_text_vychozi(): string {
    try {
        $v = (string)db()->query('SELECT VERSION()')->fetchColumn();
    } catch (Throwable $e) {
        return 'TEXT NULL';
    }
    if (stripos($v, 'mariadb') !== false) {
        return version_compare(preg_replace('/^(\d+\.\d+\.\d+).*/', '$1', $v), '10.2.1', '>=')
            ? "TEXT NOT NULL DEFAULT ''" : 'TEXT NULL';
    }
    return version_compare(preg_replace('/^(\d+\.\d+\.\d+).*/', '$1', $v), '8.0.13', '>=')
        ? "TEXT NOT NULL DEFAULT ('')" : 'TEXT NULL';
}

/**
 * Založí CHYBĚJÍCÍ tabulky ze schema.sql (CREATE TABLE IF NOT EXISTS).
 * Nikdy nic nemaže ani nemění. Vrací seznam nově založených tabulek.
 */
function db_install(): array {
    $sql = (string)file_get_contents(WEB_ROOT . '/sql/schema.sql');
    // komentáře pryč dřív, než se dělí podle středníku – komentář ho může obsahovat
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $sql = preg_replace('/\s--\s.*$/m', '', $sql);

    if (db_je_mysql()) {
        $sql = preg_replace("/\bTEXT(\s+)NOT NULL DEFAULT ''/", db_mysql_text_vychozi(), $sql);
    } else {
        $sql = str_replace('INTEGER PRIMARY KEY AUTO_INCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/\bAUTO_INCREMENT\b/', '', $sql);
    }
    $sql = preg_replace('/^CREATE TABLE (\w+)/m', 'CREATE TABLE IF NOT EXISTS $1', $sql);

    $predtim = db_tabulky();
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $prikaz) {
        if ($prikaz === '') continue;
        if (!preg_match('/^CREATE TABLE IF NOT EXISTS\s+(\w+)/i', $prikaz, $m)) {
            throw new RuntimeException('schema.sql smí obsahovat jen CREATE TABLE: ' . mb_substr($prikaz, 0, 60));
        }
        cltk_tabulka($m[1]);
        if (db_je_mysql()) {
            $prikaz .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci';
        }
        db_exec($prikaz);
    }
    return array_values(array_diff(db_tabulky(), $predtim));
}

/** Přičte zobrazení stránky (upsert, který umí oba ovladače). */
function db_upsert_hit(string $den, string $cesta): void {
    if (db_je_mysql()) {
        q('INSERT INTO cltk_visits (day, path, hits) VALUES (?,?,1) ON DUPLICATE KEY UPDATE hits = hits + 1', [$den, $cesta]);
    } else {
        q('INSERT INTO cltk_visits (day, path, hits) VALUES (?,?,1) ON CONFLICT(day, path) DO UPDATE SET hits = hits + 1', [$den, $cesta]);
    }
}

/** Zapíše otisk návštěvníka, pokud ho ten den ještě nemáme. */
function db_insert_ignore_visitor(string $den, string $otisk): void {
    if (db_je_mysql()) {
        q('INSERT IGNORE INTO cltk_visit_log (day, visitor) VALUES (?,?)', [$den, $otisk]);
    } else {
        q('INSERT OR IGNORE INTO cltk_visit_log (day, visitor) VALUES (?,?)', [$den, $otisk]);
    }
}
