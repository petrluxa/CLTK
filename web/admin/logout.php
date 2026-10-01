<?php
/* Odhlášení. Jen přes POST s tokenem (odkaz by šlo podstrčit z cizí stránky);
   na GET se ukáže tlačítko. */
require_once __DIR__ . '/inc/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_check();
    logout();
    header('Location: login.php', true, 303);
    exit;
}

if (!current_user()) {
    header('Location: login.php', true, 303);
    exit;
}
header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Odhlášení – administrace I. ČLTK Praha</title>
<link rel="stylesheet" href="<?= e(BASE_PATH . verze('admin/assets/admin.css')) ?>">
</head>
<body>
<main class="login-wrap">
  <div class="login-box">
    <img src="<?= e(logo_url('svg')) ?>" alt="" width="72" height="82">
    <h1>Odhlásit se?</h1>
    <form method="post" class="form" action="logout.php">
      <?= csrf_field() ?>
      <button class="btn btn-primary" type="submit">Odhlásit se</button>
    </form>
    <p class="pata"><a href="index.php">← Zpět do administrace</a></p>
  </div>
</main>
</body>
</html>
