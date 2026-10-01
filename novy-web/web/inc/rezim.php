<?php
/* Režim přípravy (z Liberce) + náhledové heslo.

   Každá veřejná stránka začíná:   require __DIR__ . '/inc/rezim.php';
   (vloží i functions.php). Když je v Textech a údajích zapnutý
   „Režim přípravy“:
     – běžný návštěvník dostane přípravnou stránku (HTTP 503),
     – přihlášený správce vidí celý web s pruhem nahoře,
     – kdo na přípravné stránce zadá NÁHLEDOVÉ HESLO, dostane cookie
       a vidí web taky (pro lidi z klubu, kteří testují bez účtu),
     – všude se posílá noindex (hlavička X-Robots-Tag i meta značka
       přes rezim_meta_robots()).
   Pruh pro správce vypíše šablona hned za <body>: echo rezim_pruh(); */

require_once __DIR__ . '/functions.php';

const NAHLED_COOKIE = 'cltk_nahled';

function rezim_je_zapnuty(): bool {
    return setting('rezim_pripravy') === '1';
}

/** Je přihlášený někdo z administrace? Relaci otevře, jen když má návštěvník její cookie. */
function rezim_je_spravce(): bool {
    static $vysledek = null;
    if ($vysledek !== null) return $vysledek;
    if (!cltk_ma_relaci()) return $vysledek = false;
    cltk_session_start();
    return $vysledek = relace_spravce() !== null;      // ověří i otisk hesla (po změně hesla neplatí)
}

/** Hodnota cookie náhledu – podpis aktuálního hashe hesla (změna hesla = staré cookie neplatí). */
function nahled_cookie_hodnota(): string {
    $hash = setting('nahled_heslo_hash');
    return $hash === '' ? '' : podpis('nahled|' . $hash);
}

/** Má návštěvník platné náhledové cookie? */
function rezim_ma_nahled(): bool {
    $ocekavana = nahled_cookie_hodnota();
    $c = $_COOKIE[NAHLED_COOKIE] ?? '';
    return $ocekavana !== '' && is_string($c) && hash_equals($ocekavana, $c);
}

/** Nastaví (nebo prázdným řetězcem zruší) náhledové heslo. Ukládá se jen hash. */
function nahled_heslo_nastav(string $heslo): void {
    setting_set('nahled_heslo_hash', $heslo === '' ? '' : password_hash($heslo, PASSWORD_DEFAULT),
        ['label' => 'Náhledové heslo', 'grp' => 'rezim', 'typ' => 'heslo']);
}

/** Smí tento návštěvník vidět web? */
function rezim_smi_videt_web(): bool {
    return !rezim_je_zapnuty() || rezim_je_spravce() || rezim_ma_nahled();
}

/** <meta name="robots"> pro hlavičku stránky – v režimu přípravy noindex. */
function rezim_meta_robots(): string {
    return rezim_je_zapnuty() ? '<meta name="robots" content="noindex, nofollow">' : '';
}

/** Pruh nahoře pro správce / náhled v režimu přípravy (jinak prázdný řetězec). */
function rezim_pruh(): string {
    if (!rezim_je_zapnuty()) return '';
    $spravce = rezim_je_spravce();
    $kdo = $spravce ? 'Jste přihlášeni jako správce' : 'Prohlížíte si web s náhledovým heslem';
    $odkaz = $spravce
        ? ' · <a href="' . e(url('admin/nastaveni.php')) . '" style="color:#fbfaf6;text-decoration:underline;text-underline-offset:3px">vypnout v Textech a údajích</a>'
        : '';
    return '<div class="rezim-pruh" role="status" style="background:#0e1b30;color:#caac79;font:500 13px/1.5 Jost,system-ui,sans-serif;'
         . 'letter-spacing:.06em;text-align:center;padding:9px 16px;border-bottom:1px solid #b3935a;position:relative;z-index:1000">'
         . '<b style="color:#fbfaf6;font-weight:500">Režim přípravy</b> – návštěvníci zatím vidí přípravnou stránku. '
         . e($kdo) . '.' . $odkaz . '</div>';
}

/* ---------- formulář náhledového hesla ---------- */

/** Token formuláře bez relace (návštěvník přípravné stránky nedostane žádnou cookie). */
function nahled_token(): string {
    $t = (string)time();
    return $t . '.' . substr(podpis('nahled-formular|' . $t), 0, 32);
}

function nahled_token_ok(string $token): bool {
    if (!preg_match('/^(\d{9,11})\.([a-f0-9]{32})$/', $token, $m)) return false;
    $vek = time() - (int)$m[1];
    if ($vek < 0 || $vek > 7200) return false;
    return hash_equals(substr(podpis('nahled-formular|' . $m[1]), 0, 32), $m[2]);
}

/** Zpracuje odeslané náhledové heslo. Vrací chybovou hlášku, nebo při úspěchu přesměruje. */
function rezim_zpracuj_heslo(): string {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isset($_POST['nahled_heslo'])) return '';
    $token = $_POST['_nahled'] ?? '';
    if (!is_string($token) || !nahled_token_ok($token)) return 'Formulář vypršel, zkuste to prosím znovu.';

    $hash = setting('nahled_heslo_hash');
    if ($hash === '') return 'Náhled webu teď není povolený.';

    // brzda proti hádání – stejná tabulka jako přihlášení do administrace; pokus se zapíše
    // DŘÍV, než se heslo ověří, takže ani souběžné požadavky nezkusí víc než 5 hesel za 15 minut
    $ip = 'nahled:' . client_ip_skupina();
    try {
        $od = date('Y-m-d H:i:s', time() - 15 * 60);
        if ((int)val('SELECT COUNT(*) FROM cltk_login_attempts WHERE ip = ? AND tried_at > ?', [$ip, $od]) >= 5) {
            return 'Příliš mnoho pokusů. Zkuste to prosím za 15 minut.';
        }
        q('INSERT INTO cltk_login_attempts (ip, tried_at) VALUES (?,?)', [$ip, ted()]);
        if ((int)val('SELECT COUNT(*) FROM cltk_login_attempts WHERE ip = ? AND tried_at > ?', [$ip, $od]) > 5) {
            return 'Příliš mnoho pokusů. Zkuste to prosím za 15 minut.';
        }
    } catch (Throwable $e) { /* bez tabulky aspoň bez brzdy */ }

    $heslo = vstup_heslo('nahled_heslo');
    if ($heslo !== '' && password_verify($heslo, $hash)) {
        try { q('DELETE FROM cltk_login_attempts WHERE ip = ?', [$ip]); } catch (Throwable $e) {}
        setcookie(NAHLED_COOKIE, nahled_cookie_hodnota(), [
            'expires'  => time() + 30 * 86400,
            'path'     => BASE_PATH,
            'httponly' => true,
            'secure'   => je_https(),
            'samesite' => 'Lax',
        ]);
        $zpet = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if ($zpet === '' || !str_starts_with($zpet, BASE_PATH)) $zpet = BASE_PATH;
        header('Location: ' . $zpet, true, 303);
        exit;
    }
    usleep(500000);
    return 'Heslo nesouhlasí.';
}

/** Vykreslí přípravnou stránku a ukončí zpracování. */
function rezim_zobrazit_pripravu(string $chyba = ''): never {
    $nazev   = setting('klub_nazev', 'I. Český Lawn-Tennis Klub Praha');
    $nadpis  = setting('rezim_nadpis', 'Připravujeme nový web');
    $text    = setting('rezim_text', 'Pracujeme na nové podobě stránek klubu. Brzy je tu najdete.');
    $rezUrl  = rezervace_url();
    $tel     = setting('recepce_telefon');
    $email   = setting('recepce_email');
    $adresa  = trim(setting('adresa_ulice', 'Ostrov Štvanice 38') . ', ' . setting('adresa_mesto', '170 00 Praha 7'), ', ');
    $nahled  = setting('nahled_heslo_hash') !== '';

    http_response_code(503);
    header('Retry-After: 86400');
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    header("Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src https://fonts.gstatic.com; img-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
    ?><!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($nadpis) ?> – <?= e(setting('klub_zkratka', 'I. ČLTK Praha')) ?></title>
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#fbfaf6">
<link rel="icon" href="<?= e(logo_url('favicon')) ?>" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;1,400;1,500&family=Cormorant+SC:wght@500&family=Jost:wght@400;500&display=swap&subset=latin-ext">
<style>
:root{--papir:#fbfaf6;--papir2:#f3f0e9;--inkoust:#1a2233;--navy:#152744;--navy2:#0e1b30;--zlato:#b3935a;--zlato-navy:#caac79;--zlato-text:#8a6d3b;--seda:#6b6f7e;--linka:#e4e0d6}
*{box-sizing:border-box;margin:0;padding:0}
html{-webkit-text-size-adjust:100%}
body{min-height:100vh;min-height:100dvh;background:var(--papir);color:var(--inkoust);font:400 17px/1.7 Jost,system-ui,sans-serif;
  display:flex;flex-direction:column;-webkit-font-smoothing:antialiased}
.stuha{display:flex;height:8px}.stuha span{flex:1}.stuha span:first-child{background:var(--zlato)}.stuha span:last-child{background:var(--navy)}
main{flex:1;display:grid;place-items:center;padding:clamp(32px,7vw,80px) 16px}
.deska{width:100%;max-width:640px;text-align:center;background:var(--papir);border:1px solid var(--linka);
  padding:clamp(32px,6vw,64px) clamp(20px,5vw,56px);position:relative;box-shadow:0 30px 60px -40px rgba(21,39,68,.35)}
.deska::before{content:"";position:absolute;inset:10px;border:1px solid rgba(179,147,90,.45);pointer-events:none}
.znak{width:96px;height:auto;margin:0 auto 22px;display:block}
.klub{font:500 15px/1.3 "Cormorant SC",serif;letter-spacing:.14em;color:var(--navy)}
.zalozen{font-size:11px;letter-spacing:.32em;text-transform:uppercase;color:var(--zlato-text);margin-top:6px}
.ozdoba{width:120px;height:1px;background:var(--zlato);margin:26px auto;position:relative}
.ozdoba::after{content:"";position:absolute;left:50%;top:50%;width:7px;height:7px;background:var(--papir);border:1px solid var(--zlato);transform:translate(-50%,-50%) rotate(45deg)}
h1{font:400 clamp(34px,6vw,52px)/1.1 "Cormorant Garamond",Georgia,serif;color:var(--navy);margin-bottom:18px;padding-top:.1em}
h1 em{color:var(--zlato-text)}
.text{color:#3b4152;max-width:30em;margin:0 auto 30px}
.akce{display:flex;gap:14px 26px;justify-content:center;align-items:center;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:14px 28px;background:var(--navy);color:#fff;
  text-decoration:none;font-size:13px;letter-spacing:.2em;text-transform:uppercase;transition:background .2s}
.btn:hover,.btn:focus-visible{background:var(--navy2)}
.odkaz{color:var(--navy);text-decoration:none;font-size:13px;letter-spacing:.2em;text-transform:uppercase;border-bottom:1px solid var(--zlato);padding:6px 0}
.kontakt{margin-top:30px;font-size:15px;color:var(--seda);line-height:1.9}
.kontakt a{color:var(--navy);text-decoration:none;border-bottom:1px solid var(--linka)}
.kontakt a:hover{border-color:var(--zlato)}
details{margin-top:30px;border-top:1px solid var(--linka);padding-top:18px;text-align:center}
summary{cursor:pointer;font-size:12px;letter-spacing:.2em;text-transform:uppercase;color:var(--seda);list-style:none;display:inline-block;padding:10px 4px;min-height:44px}
summary::-webkit-details-marker{display:none}
form{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:12px}
input[type=password]{font:inherit;font-size:16px;padding:11px 14px;border:1px solid var(--linka);background:#fff;color:var(--inkoust);min-height:48px;width:min(260px,100%)}
input[type=password]:focus{outline:2px solid var(--zlato);outline-offset:1px}
button{font:inherit;font-size:12px;letter-spacing:.2em;text-transform:uppercase;background:transparent;color:var(--navy);border:1px solid var(--navy);padding:12px 20px;min-height:48px;cursor:pointer}
button:hover{background:var(--navy);color:#fff}
.chyba{color:#8f2d2d;font-size:14px;margin-top:10px}
footer{text-align:center;padding:18px 16px 26px;font-size:12px;color:var(--seda);letter-spacing:.04em}
footer a{color:var(--seda)}
.vh{position:absolute!important;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
</style>
</head>
<body>
<div class="stuha" aria-hidden="true"><span></span><span></span></div>
<main>
  <div class="deska">
    <img class="znak" src="<?= e(logo_url('svg')) ?>" width="224" height="256" alt="Znak <?= e($nazev) ?>">
    <p class="klub"><?= typo($nazev) ?></p>
    <p class="zalozen">Založen <?= e(setting('zalozeno', '1893')) ?></p>
    <div class="ozdoba" aria-hidden="true"></div>
    <h1><?= html_inline($nadpis) ?></h1>
    <p class="text"><?= nl2br(typo($text), false) ?></p>
    <div class="akce">
      <?php if ($rezUrl !== ''): ?>
        <a class="btn" href="<?= e($rezUrl) ?>" target="_blank" rel="noopener">Rezervovat kurt<span class="vh"> (rezervační systém v novém okně)</span></a>
      <?php endif; ?>
      <?php if ($tel !== ''): ?>
        <a class="odkaz" href="<?= e(tel_href($tel)) ?>">Recepce <?= e(tel_kratce($tel)) ?></a>
      <?php endif; ?>
    </div>
    <p class="kontakt">
      <?= e($adresa) ?>
      <?php if ($email !== ''): ?><br><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
    </p>
    <?php if ($nahled): ?>
      <details<?= $chyba !== '' ? ' open' : '' ?>>
        <summary>Náhled pro klub</summary>
        <form method="post" action="">
          <input type="hidden" name="_nahled" value="<?= e(nahled_token()) ?>">
          <label class="vh" for="nahled-heslo">Náhledové heslo</label>
          <input type="password" id="nahled-heslo" name="nahled_heslo" autocomplete="current-password" placeholder="Náhledové heslo" required>
          <button type="submit">Zobrazit web</button>
        </form>
        <?php if ($chyba !== ''): ?><p class="chyba" role="alert"><?= e($chyba) ?></p><?php endif; ?>
      </details>
    <?php endif; ?>
  </div>
</main>
<footer>© <?= date('Y') ?> <?= e($nazev) ?> · <a href="<?= e(url('admin/')) ?>">Správa webu</a></footer>
</body>
</html>
<?php
    exit;
}

/* ------------------------------------------------------------------ */
/* Spustí se automaticky při vložení do stránky.                       */
if (PHP_SAPI !== 'cli' && !defined('CLTK_BEZ_REZIMU') && rezim_je_zapnuty()) {
    if (!headers_sent()) header('X-Robots-Tag: noindex, nofollow');
    if (!rezim_smi_videt_web()) {
        rezim_zobrazit_pripravu(rezim_zpracuj_heslo());
    }
}
