<?php
/* Lokální databáze s výchozím obsahem – jen z příkazové řádky.

     ./_php/php.exe web/sql/seed.php           doplní chybějící tabulky a prázdné části
     ./_php/php.exe web/sql/seed.php --novy    smaže lokální SQLite a založí ji znovu
     ./_php/php.exe web/sql/seed.php --novy --z-dat
                                               totéž, ale obsah vezme ze sql/data.json
                                               (export-dat.php) – stejná cesta jako instalace.php

   Databáze = web/data/cltk.sqlite, nebo soubor z CLTK_DB_FILE.
   Na MySQL se tenhle skript nespustí (server se instaluje přes instalace.php).
   Lokálně založí zkušební účet admin@cltk.local / cltk-test-2026
   a vypne režim přípravy (na serveru ho instalace naopak zapne). */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../inc/config.php';

if (DB_DRIVER !== 'sqlite') {
    fwrite(STDERR, "seed.php běží jen nad lokální SQLite (DB_DRIVER = " . DB_DRIVER . "). Na serveru použijte instalace.php.\n");
    exit(1);
}

$novy = in_array('--novy', $argv, true);
if ($novy && is_file(DB_FILE)) {
    foreach ([DB_FILE, DB_FILE . '-journal', DB_FILE . '-wal', DB_FILE . '-shm'] as $f) {
        if (is_file($f) && !@unlink($f)) {
            fwrite(STDERR, "Soubor $f nejde smazat – neběží nad ním lokální server? Zastavte ho a zkuste to znovu.\n");
            exit(1);
        }
    }
    echo "Smazána stará databáze " . DB_FILE . "\n";
}

require_once __DIR__ . '/seed-pomocne.php';

echo "Databáze: " . DB_FILE . "\n";
$nove = db_install();
echo $nove ? 'Založeny tabulky: ' . implode(', ', $nove) . "\n" : "Tabulky už existují.\n";

if (in_array('--z-dat', $argv, true)) {
    if (!is_file(SEED_DATA_JSON)) {
        fwrite(STDERR, "sql/data.json chybí – nejdřív spusťte web/sql/export-dat.php.\n");
        exit(1);
    }
    echo "Obsah ze sql/data.json:\n";
    $n = seed_import_dat(SEED_DATA_JSON);
    echo "Naimportováno $n řádků.\n";
}

seed_spust_sady();

/* --- jen lokálně: zkušební účet a vypnutý režim přípravy --- */
if (!row('SELECT id FROM cltk_users LIMIT 1')) {
    db_insert('cltk_users', [
        'email' => 'admin@cltk.local',
        'password_hash' => password_hash('cltk-test-2026', PASSWORD_DEFAULT),
        'jmeno' => 'Zkušební správce',
        'role' => 'admin',
        'created_at' => ted(),
    ]);
    echo "Zkušební účet: admin@cltk.local / cltk-test-2026 (jen lokálně)\n";
}
if ($novy || in_array('cltk_settings', $nove, true)) {
    setting_set('rezim_pripravy', '0');
    echo "Režim přípravy: vypnutý (lokální vývoj). Na serveru ho instalace zapne.\n";
}

echo "HOTOVO\n";
