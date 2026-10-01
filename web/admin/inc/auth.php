<?php
/* Přihlášení do administrace, ochrana stránek, brzda proti hádání hesla.
   Relace: cltk_admin s cookie jen pro cestu webu (BASE_PATH), vlastní složka
   data/relace/, přísný režim a klíče pod $_SESSION['cltk'] – viz
   cltk_session_start(), relace() a relace_spravce() v inc/functions.php.
   CSRF, flash() a redirect() jsou taky v inc/functions.php (sdílí je veřejné
   formuláře). */

require_once __DIR__ . '/../../inc/functions.php';

cltk_session_start();

/* Administrace se nikdy nemá dostat do vyhledávačů ani do cache.
   CSP: administrace nemá žádný vložený skript – cizí skript (XSS) by se nespustil. */
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store, private');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob: https:; media-src 'self' blob:; connect-src 'self'; "
         . "frame-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
}

/** Přihlášený uživatel, nebo null (po změně hesla jinde je relace neplatná). */
function current_user(): ?array {
    return relace_spravce();
}

/** Na stránky administrace pustí jen přihlášeného; ostatní pošle na login.php. */
function require_login(): array {
    $u = current_user();
    if (!$u) {
        $zpet = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $q = (string)($_SERVER['QUERY_STRING'] ?? '');
        $kam = 'login.php';
        if ($zpet !== '' && $zpet !== 'login.php' && preg_match('/^[a-z0-9\-]+\.php$/', $zpet)) {
            $kam .= '?zpet=' . rawurlencode($zpet . ($q !== '' ? '?' . $q : ''));
        }
        header('Location: ' . $kam, true, 303);
        exit;
    }
    return $u;
}

/* ---------- brzda proti hádání hesla ----------
   Počítá se podle IP (u IPv6 podle sítě /64) v databázi, ne v relaci – jinak
   by stačilo smazat cookie a zkoušet znovu. Pokus se ZAPÍŠE DŘÍV, než se
   heslo ověří, a teprve pak se počítá: ani souběžné požadavky tak neprojdou
   víc než LOGIN_MAX_POKUSU hesel za LOGIN_OKNO_MINUT minut. */

const LOGIN_MAX_POKUSU = 5;
const LOGIN_OKNO_MINUT = 15;

/** Náhradní hash pro neexistující e-mail – ověření trvá stejně dlouho jako u skutečného účtu
 *  (podle toho, jak dlouho přihlášení trvá, se nedá poznat, jestli e-mail v administraci je). */
const LOGIN_NAHRADNI_HASH = ['$2y$12$gDKc1kJ4bpPGAgASfZzH4OuKN599ed/YLKXVHrm9gxBIjKn.nogUu',
                             '$2y$10$Zfek3KCInFZEINjtcxd5xeCmgcvoSOqHrsXTqQ4b43jrt3yqz54U.'];

function login_pocet_pokusu(): int {
    try {
        $od = date('Y-m-d H:i:s', time() - LOGIN_OKNO_MINUT * 60);
        return (int)val('SELECT COUNT(*) FROM cltk_login_attempts WHERE ip = ? AND tried_at > ?', [client_ip_skupina(), $od]);
    } catch (Throwable $e) {
        return 0;                         // když tabulka chybí, přihlašování nesmí zamrznout
    }
}

function login_zbyva_pokusu(): int {
    return max(0, LOGIN_MAX_POKUSU - login_pocet_pokusu());
}

function login_zapsat_pokus(): void {
    try {
        q('INSERT INTO cltk_login_attempts (ip, tried_at) VALUES (?,?)', [client_ip_skupina(), ted()]);
        if (random_int(1, 20) === 1) {     // občasný úklid
            q('DELETE FROM cltk_login_attempts WHERE tried_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
        }
    } catch (Throwable $e) { /* nesmí shodit přihlašování */ }
}

function login_vymazat_pokusy(): void {
    try {
        q('DELETE FROM cltk_login_attempts WHERE ip = ?', [client_ip_skupina()]);
    } catch (Throwable $e) { /* nevadí */ }
}

/** Přihlásí podle e-mailu a hesla. */
function login(string $email, string $heslo): bool {
    $u = row('SELECT * FROM cltk_users WHERE LOWER(email) = ?', [mb_strtolower(trim($email))]);
    if (!$u) {
        $nahradni = password_needs_rehash(LOGIN_NAHRADNI_HASH[0], PASSWORD_DEFAULT) ? LOGIN_NAHRADNI_HASH[1] : LOGIN_NAHRADNI_HASH[0];
        password_verify($heslo, $nahradni);
        return false;
    }
    if (!password_verify($heslo, (string)$u['password_hash'])) return false;

    session_regenerate_id(true);           // brání podstrčení relace
    $hash = (string)$u['password_hash'];
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $hash = password_hash($heslo, PASSWORD_DEFAULT);
        q('UPDATE cltk_users SET password_hash = ? WHERE id = ?', [$hash, (int)$u['id']]);
    }
    relace_nastav('uid', (int)$u['id']);
    relace_nastav('otisk', relace_otisk_hesla($hash));
    relace_nastav('csrf', null);           // nový token po přihlášení
    q('UPDATE cltk_users SET last_login = ? WHERE id = ?', [ted(), (int)$u['id']]);
    relace_spravce(true);
    return true;
}

/** Po změně vlastního hesla: tahle relace platí dál, všechny ostatní se odhlásí (nesedí jim otisk). */
function login_po_zmene_hesla(string $novyHash): void {
    session_regenerate_id(true);
    relace_nastav('otisk', relace_otisk_hesla($novyHash));
    relace_spravce(true);
}

function logout(): void {
    $_SESSION = [];
    if (PHP_SAPI !== 'cli' && ini_get('session.use_cookies')) {
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => BASE_PATH,
            'httponly' => true, 'secure' => je_https(), 'samesite' => 'Lax']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}
