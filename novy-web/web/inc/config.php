<?php
/* ---------------------------------------------------------------
   NASTAVENÍ WEBU I. ČLTK PRAHA

   Tento soubor je v gitu a obsahuje jen VÝCHOZÍ hodnoty.
   Přístupy k databázi sem NEPATŘÍ. Načtou se z prvního souboru,
   který existuje:

     1. <kořen FTP účtu>/cltk-config.php
        (= dirname(__DIR__, 3) – na serveru leží nad složkou www/,
        vedle config.php webu TK Olymp, který se NIKDY neotvírá)
     2. web/inc/config.local.php  (lokálně, mimo git)

   Vzor souboru s přístupy:

       <?php
       define('DB_DRIVER', 'mysql');
       define('DB_HOST', 'a068um.forpsi.com');
       define('DB_NAME', 'f201572');
       define('DB_USER', '…');
       define('DB_PASS', '…');
       define('INSTALL_KEY', 'dlouhy-nahodny-retezec');   // jen na dobu instalace
       define('SITE_URL', 'https://www.example.cz/cltkv2'); // POVINNÉ na serveru – odkazy v e-mailech, og:url
       define('SIFROVACI_KLIC', '…64 náhodných znaků…');   // šifruje rodná čísla z přihlášek;
                                                          // bez něj se rodné číslo nesbírá. NIKDY neměnit
                                                          // (stará rodná čísla by nešla přečíst).

   Lokální config.local.php se NIKDY nenahrává na server – agenti si ho
   přepisují na SQLite a na serveru by to shodilo web.

   Co se nastaví tam, má přednost před hodnotami níže
   (zápis „defined() || define()“ = nastav, jen když to ještě nikdo
   nenastavil).
   --------------------------------------------------------------- */

foreach ([dirname(__DIR__, 3) . '/cltk-config.php', __DIR__ . '/config.local.php'] as $cltkConfigSoubor) {
    if (is_file($cltkConfigSoubor)) {
        require $cltkConfigSoubor;
        break;                                   // jen první existující
    }
}
unset($cltkConfigSoubor);

/* Kořen webu (složka web/) a kořen projektu (o patro výš). */
defined('WEB_ROOT')    || define('WEB_ROOT', dirname(__DIR__));
defined('PROJEKT_ROOT') || define('PROJEKT_ROOT', dirname(__DIR__, 2));

/* --- Databáze ---
   'sqlite' lokálně (výchozí), 'mysql' na serveru (nastaví cltk-config.php). */
defined('DB_DRIVER') || define('DB_DRIVER', 'sqlite');
defined('DB_HOST')   || define('DB_HOST', 'localhost');
defined('DB_NAME')   || define('DB_NAME', '');
defined('DB_USER')   || define('DB_USER', '');
defined('DB_PASS')   || define('DB_PASS', '');

/* SQLite: web/data/cltk.sqlite, nebo soubor z proměnné prostředí CLTK_DB_FILE
   (každý agent má vlastní kopii, např. CLTK_DB_FILE=web/data/agent-areal.sqlite).
   Relativní cesta se bere od kořene projektu, a když tam nedává smysl,
   od kořene webu. */
if (!defined('DB_FILE')) {
    $cltkDb = (string)getenv('CLTK_DB_FILE');
    if ($cltkDb === '') {
        $cltkDb = WEB_ROOT . '/data/cltk.sqlite';
    } elseif (!preg_match('~^([A-Za-z]:[\\\\/]|/)~', $cltkDb)) {
        $zProjektu = PROJEKT_ROOT . '/' . $cltkDb;
        $cltkDb = is_dir(dirname($zProjektu)) ? $zProjektu : WEB_ROOT . '/' . $cltkDb;
    }
    define('DB_FILE', $cltkDb);
    unset($cltkDb, $zProjektu);
}

/* Instalační klíč pro instalace.php. Prázdný = instalace je zamčená.
   Lokálně (jen SQLite) ho lze pro vyzkoušení instalace zadat proměnnou
   prostředí CLTK_INSTALL_KEY; na serveru platí jen cltk-config.php. */
defined('INSTALL_KEY') || define('INSTALL_KEY', DB_DRIVER === 'sqlite' ? (string)getenv('CLTK_INSTALL_KEY') : '');

defined('SITE_NAME')  || define('SITE_NAME', 'I. ČLTK Praha');
defined('SITE_URL')   || define('SITE_URL', '');      // bez lomítka na konci; prázdné = jen lokálně (zjistí se z požadavku),
                                                       // na serveru bez něj e-maily nedostanou odkaz (viz inc/mail.php)
defined('UPLOAD_DIR') || define('UPLOAD_DIR', WEB_ROOT . '/uploads');
defined('DATA_DIR')   || define('DATA_DIR', WEB_ROOT . '/data');

/* --- Cesta webu ---
   Web běží v podsložce (na testu /cltkv2/, později kořen cltk.cz).
   BASE_PATH se zjistí porovnáním SCRIPT_NAME (adresa) se SCRIPT_FILENAME
   (soubor na disku) vůči kořeni webu. Vždy začíná i končí lomítkem.
   Kdyby to na nějakém hostingu nevyšlo, nastavte BASE_PATH v cltk-config.php. */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', (static function (): string {
        if (PHP_SAPI === 'cli') return '/';
        $adresa = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $soubor = (string)($_SERVER['SCRIPT_FILENAME'] ?? '');
        $koren  = str_replace('\\', '/', (string)(realpath(WEB_ROOT) ?: WEB_ROOT));
        $soubor = str_replace('\\', '/', (string)(realpath($soubor) ?: $soubor));
        if ($adresa === '' || $soubor === '') return '/';

        $vKoreni = '';
        if (stripos($soubor, rtrim($koren, '/') . '/') === 0) {
            $vKoreni = substr($soubor, strlen(rtrim($koren, '/')) + 1);   // např. admin/index.php
        }
        if ($vKoreni !== '' && substr($adresa, -strlen($vKoreni)) === $vKoreni) {
            $zaklad = substr($adresa, 0, strlen($adresa) - strlen($vKoreni));
        } else {
            $zaklad = rtrim(dirname($adresa), '/') . '/';               // nouzově složka skriptu
        }
        $zaklad = '/' . trim($zaklad, '/') . '/';
        return $zaklad === '//' ? '/' : $zaklad;
    })());
}

date_default_timezone_set('Europe/Prague');
mb_internal_encoding('UTF-8');

/* ---------------------------------------------------------------
   Chybová hlášení
   Na webu se nesmí vypisovat do stránky – výpis by kromě jiného
   rozbil přesměrování po uložení formuláře. Píšou se do
   web/data/chyby.log. Ladění zapne CLTK_DEBUG=1 v prostředí.
   --------------------------------------------------------------- */
defined('CLTK_DEBUG') || define('CLTK_DEBUG', (bool)getenv('CLTK_DEBUG'));

/* Složka data/ se na server nenahrává – když chybí, založí se
   i se zámkem proti přístupu zvenku. */
if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0775, true);
}
if (is_dir(DATA_DIR) && !is_file(DATA_DIR . '/.htaccess')) {
    @file_put_contents(DATA_DIR . '/.htaccess', "# Databáze a protokoly – zvenku nepřístupné\nRequire all denied\n");
}

error_reporting(E_ALL);
ini_set('display_errors', CLTK_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
if (!CLTK_DEBUG || PHP_SAPI !== 'cli') {
    /* Protokol leží na disku sdíleném s TK Olymp – nesmí růst donekonečna.
       Nad 5 MB se přejmenuje na chyby-stare.log (předchozí starý se přepíše)
       a začne nový, takže na disku jsou nejvýš asi 10 MB. */
    $cltkLog = DATA_DIR . '/chyby.log';
    if (@filesize($cltkLog) > 5 * 1048576) @rename($cltkLog, DATA_DIR . '/chyby-stare.log');
    ini_set('error_log', $cltkLog);
    unset($cltkLog);
}

/* Verzi PHP nikomu neprozrazovat (expose_php). */
if (PHP_SAPI !== 'cli' && !headers_sent()) header_remove('X-Powered-By');
