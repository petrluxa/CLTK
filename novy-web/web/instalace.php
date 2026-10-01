<?php
/* INSTALACE WEBU I. ČLTK PRAHA NA SERVERU – jednorázově.

   Otevírá se jako  https://…/instalace.php  a INSTALL_KEY z cltk-config.php
   se zadá do formuláře (posílá se jen POSTem – v adrese by zůstal v protokolech
   serveru a v historii prohlížeče). Bez klíče, se špatným klíčem nebo
   s prázdným INSTALL_KEY nic neudělá. Hádání klíče brzdí 5 pokusů / 15 minut.

   Co dělá (a nic jiného):
     1. založí CHYBĚJÍCÍ tabulky cltk_ (CREATE TABLE IF NOT EXISTS) –
        nikdy nic nemaže ani nemění, cizích tabulek (TK Olymp) se nedotkne,
        hlídá to pojistka v inc/db.php,
     2. naplní prázdné tabulky obsahem ze sql/data.json (export z lokální
        databáze – sql/export-dat.php); co v exportu není, doplní výchozí
        sady sql/seed/NN-*.php (jen do prázdných tabulek a chybějících klíčů),
     3. zapne režim přípravy,
     4. založí PRVNÍ účet správce z formuláře.
   Když už nějaký účet existuje, odmítne cokoli dělat.

   PO INSTALACI SOUBOR ZE SERVERU SMAŽTE – nejdřív ho přebijte souborem
   <?php http_response_code(404); exit; ověřte, že vrací 404 (cache PHP
   na Forpsi), a teprve pak ho smažte. Zkušební účet z lokálního seedu
   se tu NIKDY nezakládá. */

require_once __DIR__ . '/inc/functions.php';

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store, private');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src https://fonts.gstatic.com; img-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
}

/** Stránka instalátoru v designu administrace; $obsah = hotové HTML. */
function instalace_stranka(string $nadpis, string $obsah, int $kod = 200): never {
    http_response_code($kod);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="cs"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<meta name="robots" content="noindex, nofollow">'
       . '<title>' . e($nadpis) . ' – instalace webu I. ČLTK Praha</title>'
       . '<link rel="icon" href="' . e(logo_url('favicon')) . '" type="image/png">'
       . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;1,400&family=Cormorant+SC:wght@600&family=Jost:wght@400;500&display=swap&subset=latin-ext">'
       . '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin.css')) . '">'
       . '<style>.login-box{max-width:600px}.instalace-log{font-size:13.5px;color:#6b6f7e;line-height:1.6;max-height:340px;overflow:auto;'
       . 'background:#fbfaf6;border:1px solid #e4e0d6;padding:12px 14px;margin-bottom:18px;white-space:pre-wrap}'
       . '.instalace-stav{list-style:none;font-size:14px;margin-bottom:20px;border-top:1px solid #efebe2}'
       . '.instalace-stav li{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid #efebe2}'
       . '.instalace-stav b{font-weight:500}.ok{color:#2f6b4f}.ne{color:#8f2d2d}</style>'
       . '</head><body><div class="stuha" aria-hidden="true"><span></span><span></span></div>'
       . '<main class="login-wrap"><div class="login-box">'
       . '<img src="' . e(logo_url('svg')) . '" alt="Znak I. ČLTK Praha" width="72" height="82">'
       . '<p class="klub">I. ČLTK Praha</p><h1>' . e($nadpis) . '</h1><p class="sub">Založen 1893 · instalace</p>'
       . $obsah
       . '</div></main></body></html>';
    exit;
}

/* ---------- 1) instalační klíč ---------- */
if (INSTALL_KEY === '' || strlen(INSTALL_KEY) < 16) {
    instalace_stranka('Instalace je zamčená',
        '<div class="flash warn">Instalace je vypnutá. V souboru <b>cltk-config.php</b> nad složkou webu chybí '
      . '<b>INSTALL_KEY</b> (aspoň 16 znaků). Na dobu instalace ho doplňte, po instalaci ho odeberte.</div>', 403);
}
/** Formulář pro zadání instalačního klíče (POST – klíč nepatří do adresy). */
function instalace_formular_klice(string $hlaska = ''): string {
    return $hlaska
         . '<form method="post" class="form" action="instalace.php" autocomplete="off">' . csrf_field()
         . '<div class="field"><label for="f-klic">Instalační klíč (INSTALL_KEY z cltk-config.php)</label>'
         . '<input type="password" id="f-klic" name="klic" required minlength="16" autocomplete="off"></div>'
         . '<button class="btn btn-primary" type="submit">Pokračovat</button></form>';
}

$klic = vstup_heslo('klic');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || $klic === '') {
    $pozn = isset($_GET['klic'])
        ? '<div class="flash warn">Klíč se do adresy nepíše (zůstal by v protokolech serveru a v historii prohlížeče). Zadejte ho prosím do pole níže.</div>'
        : '<p class="hint" style="margin-bottom:18px">Zadejte instalační klíč z <b>cltk-config.php</b>.</p>';
    instalace_stranka('Instalace webu', instalace_formular_klice($pozn));
}
csrf_check();

/* brzda proti hádání klíče (tabulka ještě nemusí existovat – pak jen zdržení) */
$brzdaIp = 'instalace:' . client_ip_skupina();
$brzdaOd = date('Y-m-d H:i:s', time() - 15 * 60);
$brzdaPocet = 0;
try {
    q('INSERT INTO cltk_login_attempts (ip, tried_at) VALUES (?,?)', [$brzdaIp, ted()]);
    $brzdaPocet = (int)val('SELECT COUNT(*) FROM cltk_login_attempts WHERE ip = ? AND tried_at > ?', [$brzdaIp, $brzdaOd]);
} catch (Throwable $e) { /* před instalací tabulka chybí */ }
if ($brzdaPocet > 5) {
    instalace_stranka('Instalace je zamčená',
        '<div class="flash err">Příliš mnoho pokusů. Zkuste to prosím znovu za 15 minut.</div>', 429);
}
if (!hash_equals(INSTALL_KEY, $klic)) {
    usleep(400000);
    instalace_stranka('Instalace je zamčená', instalace_formular_klice('<div class="flash err" role="alert">Instalační klíč nesouhlasí.</div>'), 403);
}
try { q('DELETE FROM cltk_login_attempts WHERE ip = ?', [$brzdaIp]); } catch (Throwable $e) {}

/* ---------- 2) stav databáze ---------- */
$chybaDb = '';
$tabulky = [];
$ucty = 0;
try {
    $tabulky = db_tabulky();
    if (in_array('cltk_users', $tabulky, true)) $ucty = (int)val('SELECT COUNT(*) FROM cltk_users');
} catch (Throwable $e) {
    $chybaDb = $e->getMessage();
}
if ($chybaDb !== '') {
    error_log('[instalace] ' . $chybaDb);
    instalace_stranka('Databáze nedostupná',
        '<div class="flash err">K databázi se nepodařilo připojit. Zkontrolujte přístupy v <b>cltk-config.php</b> '
      . '(DB_DRIVER, DB_HOST, DB_NAME, DB_USER, DB_PASS).</div><p class="hint">' . e(mb_substr($chybaDb, 0, 300)) . '</p>', 500);
}
if ($ucty > 0) {
    instalace_stranka('Web je už nainstalovaný',
        '<div class="flash warn">V databázi už je účet správce, instalaci proto nelze spustit znovu a nic se nezměnilo.</div>'
      . '<p class="hint" style="margin-bottom:18px">Soubor <b>instalace.php</b> ze serveru smažte (nejdřív ho přebijte souborem, '
      . 'který vrací 404, ověřte adresu a pak ho smažte) a z <b>cltk-config.php</b> odeberte INSTALL_KEY.</p>'
      . '<div class="btn-row"><a class="btn btn-primary" href="admin/">Do administrace</a>'
      . '<a class="btn btn-ghost" href="' . e(url('')) . '">Zobrazit web</a></div>', 403);
}

/* ---------- 3) instalace (POST) ---------- */
$chyby = [];
$email = '';
$jmeno = '';
if (vstup('krok', 20) === 'instalovat') {
    $email = mb_strtolower(vstup('email', 190));
    $jmeno = vstup('jmeno', 120);
    $heslo = vstup_heslo('heslo');
    $heslo2 = vstup_heslo('heslo2');
    if (!je_email($email)) $chyby[] = 'Zadejte platný e-mail správce.';
    if (mb_strlen($heslo) < 10) $chyby[] = 'Heslo musí mít alespoň 10 znaků.';
    if ($heslo !== $heslo2) $chyby[] = 'Heslo a jeho kontrola se neshodují.';

    if (!$chyby) {
        @set_time_limit(300);
        require_once __DIR__ . '/sql/seed-pomocne.php';
        $GLOBALS['SEED_PROTOKOL'] = [];
        $chybaInstalace = '';
        try {
            uploads_zajisti_ochranu();                     // uploads/.htaccess – nic se tam nesmí spouštět
            seed_log(is_file(UPLOAD_DIR . '/.htaccess') ? 'Ochrana uploads/.htaccess: v pořádku.' : 'POZOR: uploads/.htaccess se nepodařilo založit – nahrajte ho ručně.');
            $nove = db_install();
            seed_log($nove ? 'Založeny tabulky: ' . implode(', ', $nove) . '.' : 'Všechny tabulky cltk_ už existovaly.');

            if (is_file(SEED_DATA_JSON)) {
                seed_log('Obsah ze sql/data.json:');
                $n = seed_import_dat(SEED_DATA_JSON);
                seed_log('Naimportováno ' . $n . ' ' . sklonuj($n, 'řádek', 'řádky', 'řádků') . '.');
            } else {
                seed_log('sql/data.json chybí – použije se jen výchozí obsah.');
            }
            seed_log('Výchozí obsah (doplní jen prázdné části):');
            seed_spust_sady();

            setting_set('rezim_pripravy', '1');
            cltk_klic();                                   // tajný klíč webu, pokud ještě není
            seed_log('Režim přípravy: zapnutý.');

            /* účet až na konec – když něco výše selže, jde instalaci zopakovat */
            if ((int)val('SELECT COUNT(*) FROM cltk_users') > 0) {
                throw new RuntimeException('Mezitím vznikl jiný účet – instalace se zastavila.');
            }
            db_insert('cltk_users', [
                'email' => $email,
                'password_hash' => password_hash($heslo, PASSWORD_DEFAULT),
                'jmeno' => $jmeno !== '' ? $jmeno : 'Správce webu',
                'role' => 'admin',
                'created_at' => ted(),
            ]);
            seed_log('Účet správce: ' . $email);
        } catch (Throwable $e) {
            $chybaInstalace = $e->getMessage();
            error_log('[instalace] ' . $chybaInstalace);
        }
        $protokol = implode("\n", $GLOBALS['SEED_PROTOKOL']);

        if ($chybaInstalace !== '') {
            instalace_stranka('Instalace se nezdařila',
                '<div class="flash err">' . e($chybaInstalace) . '</div>'
              . '<div class="instalace-log">' . e($protokol) . '</div>'
              . '<p class="hint">Účet správce se nezaložil, instalaci můžete po opravě spustit znovu – hotové části přeskočí.</p>', 500);
        }
        instalace_stranka('HOTOVO',
            '<div class="flash ok">HOTOVO – web je nainstalovaný a čeká v&nbsp;režimu přípravy.</div>'
          . '<div class="instalace-log">' . e($protokol) . '</div>'
          . '<div class="flash warn">Teď ze serveru odstraňte <b>instalace.php</b> (nejdřív přebít souborem, který vrací 404, '
          . 'ověřit, pak smazat) a z <b>cltk-config.php</b> odeberte INSTALL_KEY.</div>'
          . '<div class="btn-row"><a class="btn btn-primary" href="admin/">Přihlásit se do administrace</a>'
          . '<a class="btn btn-ghost" href="' . e(url('')) . '">Zobrazit web</a></div>');
    }
}

/* ---------- 4) formulář ---------- */
$zapisUploads = is_dir(UPLOAD_DIR) ? is_writable(UPLOAD_DIR) : is_writable(WEB_ROOT);
$zapisData = is_dir(DATA_DIR) && is_writable(DATA_DIR);
$maData = is_file(__DIR__ . '/sql/data.json');
$gd = function_exists('imagecreatetruecolor');
$webp = function_exists('imagewebp');
$ovladac = DB_DRIVER === 'mysql' ? 'MySQL · ' . DB_NAME : 'SQLite (jen pro zkoušení)';
$stav = static fn(bool $ok, string $ano, string $ne) => '<span class="' . ($ok ? 'ok' : 'ne') . '">' . e($ok ? $ano : $ne) . '</span>';

$h = '';
foreach ($chyby as $c) $h .= '<div class="flash err" role="alert">' . e($c) . '</div>';
$h .= '<ul class="instalace-stav">'
    . '<li><b>Databáze</b><span>' . e($ovladac) . '</span></li>'
    . '<li><b>Tabulky cltk_</b><span>' . count($tabulky) . ' z 29 existuje</span></li>'
    . '<li><b>Obsah sql/data.json</b>' . $stav($maData, 'připravený', 'chybí – jen výchozí obsah') . '</li>'
    . '<li><b>Složka uploads/</b>' . $stav($zapisUploads, 'lze zapisovat', 'nelze zapisovat') . '</li>'
    . '<li><b>Ochrana uploads/.htaccess</b>' . $stav(is_file(UPLOAD_DIR . '/.htaccess'), 'je na místě', 'chybí – instalace ji založí') . '</li>'
    . '<li><b>Složka data/ (protokol chyb)</b>' . $stav($zapisData, 'lze zapisovat', 'nelze zapisovat') . '</li>'
    . '<li><b>Úprava obrázků (GD / WebP)</b>' . $stav($gd, $webp ? 'GD i WebP' : 'GD bez WebP', 'GD chybí') . '</li>'
    . '</ul>'
    . '<p class="hint" style="margin-bottom:18px">Instalace založí jen chybějící tabulky s předponou <b>cltk_</b>, naplní prázdné '
    . 'tabulky obsahem webu a založí první účet správce. Nic nemaže a cizích tabulek se nedotkne.</p>'
    . '<form method="post" class="form" action="instalace.php">'
    . csrf_field()
    . '<input type="hidden" name="klic" value="' . e($klic) . '">'
    . '<input type="hidden" name="krok" value="instalovat">'
    . '<div class="field"><label for="f-email">E-mail správce</label><input type="email" id="f-email" name="email" value="' . e($email) . '" required autocomplete="username" maxlength="190"></div>'
    . '<div class="field"><label for="f-jmeno">Jméno</label><input type="text" id="f-jmeno" name="jmeno" value="' . e($jmeno) . '" autocomplete="name" maxlength="120"></div>'
    . '<div class="field"><label for="f-heslo">Heslo (aspoň 10 znaků)</label><input type="password" id="f-heslo" name="heslo" required minlength="10" autocomplete="new-password"></div>'
    . '<div class="field"><label for="f-heslo2">Heslo pro kontrolu</label><input type="password" id="f-heslo2" name="heslo2" required minlength="10" autocomplete="new-password"></div>'
    . '<button class="btn btn-primary" type="submit">Nainstalovat web</button>'
    . '</form>';
instalace_stranka('Instalace webu', $h);
