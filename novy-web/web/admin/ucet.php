<?php
/* Účet – jméno, přihlašovací e-mail a heslo přihlášeného správce. */
require_once __DIR__ . '/inc/layout.php';
$user = require_login();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek('ucet.php');
    $akce = vstup('action', 20);

    if ($akce === 'jmeno') {
        $jmeno = vstup('jmeno', 120);
        if ($jmeno === '') redirect('ucet.php', 'Jméno nemůže zůstat prázdné.', 'err');
        q('UPDATE cltk_users SET jmeno = ? WHERE id = ?', [$jmeno, (int)$user['id']]);
        redirect('ucet.php', 'Jméno bylo změněno.');
    }

    if ($akce === 'email') {
        $email = mb_strtolower(vstup('email', 190));
        $heslo = vstup_heslo('heslo_kontrola');
        if (!je_email($email)) redirect('ucet.php', 'Zadejte platný e-mail.', 'err');
        if (!password_verify($heslo, (string)$user['password_hash'])) redirect('ucet.php', 'Stávající heslo nesouhlasí – e-mail se nezměnil.', 'err');
        if (row('SELECT id FROM cltk_users WHERE LOWER(email) = ? AND id <> ?', [$email, (int)$user['id']])) {
            redirect('ucet.php', 'Tento e-mail už používá jiný účet.', 'err');
        }
        q('UPDATE cltk_users SET email = ? WHERE id = ?', [$email, (int)$user['id']]);
        redirect('ucet.php', 'Přihlašovací e-mail byl změněn. Příště se přihlaste novým e-mailem.');
    }

    if ($akce === 'heslo') {
        $stare = vstup_heslo('heslo_stare');
        $nove  = vstup_heslo('heslo_nove');
        $nove2 = vstup_heslo('heslo_nove2');
        if ($stare === '' || $nove === '' || $nove2 === '') redirect('ucet.php', 'Vyplňte prosím všechna tři pole hesla.', 'err');
        if (!password_verify($stare, (string)$user['password_hash'])) redirect('ucet.php', 'Stávající heslo nesouhlasí. Zkuste ho zadat znovu.', 'err');
        if (mb_strlen($nove) < 10) redirect('ucet.php', 'Nové heslo je krátké – musí mít alespoň 10 znaků.', 'err');
        if ($nove !== $nove2) redirect('ucet.php', 'Nové heslo a jeho potvrzení se neshodují.', 'err');
        if (password_verify($nove, (string)$user['password_hash'])) redirect('ucet.php', 'Nové heslo je stejné jako to stávající.', 'err');
        $novyHash = password_hash($nove, PASSWORD_DEFAULT);
        q('UPDATE cltk_users SET password_hash = ? WHERE id = ?', [$novyHash, (int)$user['id']]);
        login_po_zmene_hesla($novyHash);       // tahle relace platí dál, ostatní přihlášení se odhlásí
        redirect('ucet.php', 'Heslo bylo změněno. Ostatní přihlášení (jiné prohlížeče a zařízení) jsou odhlášená, při příštím přihlášení použijte nové heslo.');
    }

    redirect('ucet.php', 'Neznámý požadavek.', 'err');
}

$posledni = !empty($user['last_login']) ? cz_datum_cas((string)$user['last_login']) : '';
$zalozen  = !empty($user['created_at']) ? cz_date((string)$user['created_at']) : '';

admin_head('Účet', $user, ['sirka' => 'uzka']);
?>

<section class="panel">
  <div class="panel-head"><h2>Přihlašovací údaje</h2></div>
  <div class="panel-body tight">
    <div class="tbl-wrap">
      <table>
        <tbody>
          <tr><th style="width:240px">Přihlašovací e-mail</th><td><b><?= e($user['email']) ?></b></td></tr>
          <tr><th>Jméno v administraci</th><td><?= $user['jmeno'] !== '' ? e($user['jmeno']) : '<span class="hint">nevyplněno</span>' ?></td></tr>
          <tr><th>Oprávnění</th><td><?= badge('Správce webu', 'navy') ?></td></tr>
          <tr><th>Poslední přihlášení</th><td><?= $posledni !== '' ? e($posledni) : '<span class="hint">první přihlášení</span>' ?></td></tr>
          <?php if ($zalozen !== ''): ?><tr><th>Účet založen</th><td><?= e($zalozen) ?></td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="panel">
  <div class="panel-head"><h2>Jméno</h2></div>
  <div class="panel-body">
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="jmeno">
      <?= pole_text('jmeno', 'Jméno a příjmení', $user['jmeno'], ['maxlength' => 120, 'required' => true, 'autocomplete' => 'name',
          'hint' => 'Zobrazuje se v administraci. Na veřejném webu se neukazuje.']) ?>
      <?= tlacitka_formulare('Uložit jméno') ?>
    </form>
  </div>
</section>

<section class="panel">
  <div class="panel-head"><h2>Přihlašovací e-mail</h2></div>
  <div class="panel-body">
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="email">
      <?= pole_radek([
            pole_text('email', 'Nový e-mail', $user['email'], ['type' => 'email', 'required' => true, 'autocomplete' => 'username']),
            pole_text('heslo_kontrola', 'Stávající heslo pro potvrzení', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password']),
          ]) ?>
      <?= tlacitka_formulare('Změnit e-mail') ?>
    </form>
  </div>
</section>

<section class="panel">
  <div class="panel-head"><h2>Změna hesla</h2></div>
  <div class="panel-body">
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="heslo">
      <?= pole_text('heslo_stare', 'Stávající heslo', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password']) ?>
      <?= pole_radek([
            pole_text('heslo_nove', 'Nové heslo', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password',
                'attrs' => ['minlength' => 10], 'hint' => 'Alespoň 10 znaků. Dobře se pamatuje věta z několika slov.']),
            pole_text('heslo_nove2', 'Nové heslo pro kontrolu', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password',
                'attrs' => ['minlength' => 10]]),
          ]) ?>
      <?= tlacitka_formulare('Změnit heslo') ?>
    </form>
  </div>
</section>

<?php admin_foot(); ?>
