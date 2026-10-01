<?php
/* Přihlášení do administrace (e-mail + heslo). */
require_once __DIR__ . '/inc/auth.php';

/* kam se vrátit po přihlášení – jen na stránku administrace */
$zpet = $_POST['zpet'] ?? $_GET['zpet'] ?? '';
$zpet = is_string($zpet) ? $zpet : '';                  // „?zpet[]=x“ nesmí plnit chyby.log
if (!preg_match('/^[a-z0-9\-]+\.php(\?[A-Za-z0-9_=&%\-.]*)?$/', $zpet) || str_starts_with($zpet, 'login.php') || str_starts_with($zpet, 'logout.php')) {
    $zpet = 'index.php';
}

if (current_user()) redirect($zpet);

$bezUctu = false;
try {
    $bezUctu = db_installed() && (int)val('SELECT COUNT(*) FROM cltk_users') === 0;
} catch (Throwable $e) {
    $bezUctu = true;
}

$zbyva = login_zbyva_pokusu();
$chyba = '';
$email = '';
$zamceno = 'Příliš mnoho neúspěšných pokusů. Zkuste to prosím znovu za ' . LOGIN_OKNO_MINUT . ' minut.';

if ($zbyva <= 0) {
    $chyba = $zamceno;
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_check();
    $email = vstup('email', 190);
    $heslo = vstup_heslo('heslo');

    if ($email === '' || $heslo === '') {
        $chyba = 'Vyplňte e-mail i heslo.';
    } else {
        login_zapsat_pokus();                   // nejdřív zapsat, pak počítat – souběžné pokusy brzdu neobejdou
        $pokusu = login_pocet_pokusu();
        if ($pokusu > LOGIN_MAX_POKUSU) {
            $zbyva = 0;
            $chyba = $zamceno;
        } elseif (login($email, $heslo)) {
            login_vymazat_pokusy();
            redirect($zpet);
        } else {
            usleep(600000);                     // zdrží automatické zkoušení
            $zbyva = max(0, LOGIN_MAX_POKUSU - $pokusu);
            $chyba = $zbyva <= 0
                ? 'Příliš mnoho neúspěšných pokusů. Přihlašování je na ' . LOGIN_OKNO_MINUT . ' minut zamčené.'
                : 'Nesprávný e-mail nebo heslo. Zbývá ' . $zbyva . ' ' . sklonuj($zbyva, 'pokus', 'pokusy', 'pokusů') . '.';
        }
    }
}
header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Přihlášení – administrace I. ČLTK Praha</title>
<link rel="icon" href="<?= e(logo_url('favicon')) ?>" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;1,400&family=Cormorant+SC:wght@600&family=Jost:wght@400;500&display=swap&subset=latin-ext">
<link rel="stylesheet" href="<?= e(BASE_PATH . verze('admin/assets/admin.css')) ?>">
</head>
<body>
<div class="stuha" aria-hidden="true"><span></span><span></span></div>
<main class="login-wrap">
  <div class="login-box">
    <img src="<?= e(logo_url('svg')) ?>" alt="Znak I. ČLTK Praha" width="72" height="82">
    <p class="klub">I. ČLTK Praha</p>
    <h1>Administrace</h1>
    <p class="sub">Založen 1893</p>

    <?php if ($chyba !== ''): ?><div class="flash err" role="alert"><?= e($chyba) ?></div><?php endif; ?>
    <?php if ($bezUctu): ?>
      <div class="flash warn">Zatím tu není žádný účet. Na serveru ho založí <b>instalace.php</b>, lokálně <b>web/sql/seed.php</b>.</div>
    <?php endif; ?>

    <form method="post" class="form" action="login.php">
      <?= csrf_field() ?>
      <input type="hidden" name="zpet" value="<?= e($zpet) ?>">
      <?= pole_login('email', 'E-mail', $email, 'email', 'username') ?>
      <?= pole_login('heslo', 'Heslo', '', 'password', 'current-password') ?>
      <button class="btn btn-primary" type="submit"<?= $zbyva <= 0 ? ' disabled' : '' ?>>Přihlásit se</button>
    </form>
    <p class="pata"><a href="<?= e(url('')) ?>">← Zpět na web</a></p>
  </div>
</main>
</body>
</html>
<?php
/** Pole přihlašovacího formuláře (login.php nenačítá layout.php). */
function pole_login(string $name, string $label, string $value, string $type, string $autocomplete): string {
    return '<div class="field"><label for="f-' . e($name) . '">' . e($label) . '</label>'
         . '<input type="' . e($type) . '" id="f-' . e($name) . '" name="' . e($name) . '" value="' . e($value) . '"'
         . ' autocomplete="' . e($autocomplete) . '" required' . ($name === 'email' ? ' autofocus inputmode="email" autocapitalize="off" spellcheck="false"' : '') . '></div>';
}
