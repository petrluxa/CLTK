<?php
/* Přístupy – správce webu zakládá další účty do administrace, mění jejich oprávnění,
   vydává nové heslo a účty maže. Jen pro roli admin (ADMIN_JEN_SPRAVCE v inc/auth.php).

   Heslo pro nový účet (i nové heslo) vygeneruje web a ukáže ho JEDNOU, hned po uložení –
   v databázi je jen jeho hash. Správce ho předá člověku a ten si ho změní v Účtu.
   Nové heslo zneplatní všechna přihlášení toho účtu (otisk hesla v relaci nesedí).
   Pojistky: sám sebe nikdo nesmaže ani si nesebere roli a vždy zůstane aspoň jeden správce. */
require_once __DIR__ . '/inc/layout.php';
$user = require_login();

/** Čitelné náhodné heslo bez snadno zaměnitelných znaků (0/O, 1/l/I), 3 × 4 znaky. */
function uz_nove_heslo(): string {
    $abc = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < 12; $i++) $s .= $abc[random_int(0, strlen($abc) - 1)];
    return implode('-', str_split($s, 4));
}

function uz_pocet_spravcu(): int {
    return (int)val("SELECT COUNT(*) FROM cltk_users WHERE role = 'admin'");
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek('uzivatele.php');
    $akce = vstup('action', 20);
    $id = (int)vstup_int('id', 0);
    $cil = $id > 0 ? row('SELECT * FROM cltk_users WHERE id = ?', [$id]) : null;

    if ($akce === 'pridat') {
        $jmeno = vstup('jmeno', 120);
        $email = mb_strtolower(vstup('email', 190));
        $role = vstup('role', 20);
        if ($jmeno === '') redirect('uzivatele.php', 'Vyplňte jméno – podle něj účet v seznamu poznáte.', 'err');
        if (!je_email($email)) redirect('uzivatele.php', 'Zadejte platný e-mail – slouží k přihlášení.', 'err');
        if (!isset(ADMIN_ROLE[$role])) redirect('uzivatele.php', 'Vyberte oprávnění.', 'err');
        if (row('SELECT id FROM cltk_users WHERE LOWER(email) = ?', [$email])) redirect('uzivatele.php', 'Účet s tímto e-mailem už existuje.', 'err');
        $heslo = uz_nove_heslo();
        db_insert('cltk_users', ['email' => $email, 'password_hash' => password_hash($heslo, PASSWORD_DEFAULT),
            'jmeno' => $jmeno, 'role' => $role, 'created_at' => ted()]);
        relace_nastav('uz_heslo', ['email' => $email, 'jmeno' => $jmeno, 'heslo' => $heslo, 'novy' => true]);
        redirect('uzivatele.php', 'Účet pro ' . $jmeno . ' je založený.');
    }

    if (!$cil) redirect('uzivatele.php', 'Účet nebyl nalezen.', 'err');
    $jaSam = (int)$cil['id'] === (int)$user['id'];

    if ($akce === 'role') {
        $role = vstup('role', 20);
        if (!isset(ADMIN_ROLE[$role])) redirect('uzivatele.php', 'Vyberte oprávnění.', 'err');
        if ($jaSam) redirect('uzivatele.php', 'Vlastní oprávnění změnit nejde – požádejte jiného správce.', 'err');
        if ($cil['role'] === 'admin' && $role !== 'admin' && uz_pocet_spravcu() <= 1) redirect('uzivatele.php', 'Musí zůstat aspoň jeden správce webu.', 'err');
        q('UPDATE cltk_users SET role = ? WHERE id = ?', [$role, (int)$cil['id']]);
        redirect('uzivatele.php', 'Oprávnění pro ' . ($cil['jmeno'] ?: $cil['email']) . ': ' . role_nazev($role) . '.');
    }

    if ($akce === 'heslo') {
        if ($jaSam) redirect('ucet.php', 'Vlastní heslo si změňte tady v Účtu.', 'err');
        $heslo = uz_nove_heslo();
        q('UPDATE cltk_users SET password_hash = ? WHERE id = ?', [password_hash($heslo, PASSWORD_DEFAULT), (int)$cil['id']]);
        relace_nastav('uz_heslo', ['email' => (string)$cil['email'], 'jmeno' => (string)$cil['jmeno'], 'heslo' => $heslo, 'novy' => false]);
        redirect('uzivatele.php', 'Nové heslo je nastavené, staré přestalo platit.');
    }

    if ($akce === 'smazat') {
        if ($jaSam) redirect('uzivatele.php', 'Vlastní účet smazat nejde.', 'err');
        if ($cil['role'] === 'admin' && uz_pocet_spravcu() <= 1) redirect('uzivatele.php', 'Musí zůstat aspoň jeden správce webu.', 'err');
        q('DELETE FROM cltk_users WHERE id = ?', [(int)$cil['id']]);
        redirect('uzivatele.php', 'Účet ' . ($cil['jmeno'] ?: $cil['email']) . ' je smazaný – do administrace se už nepřihlásí.');
    }

    redirect('uzivatele.php', 'Neznámý požadavek.', 'err');
}

/* heslo ukázat jen jednou – hned se z relace maže */
$noveHeslo = relace('uz_heslo');
if ($noveHeslo !== null) relace_nastav('uz_heslo', null);
if (!headers_sent()) header('Cache-Control: no-store, private');

$ucty = rows("SELECT * FROM cltk_users ORDER BY CASE role WHEN 'admin' THEN 0 ELSE 1 END, jmeno, email");
$moznosti = ADMIN_ROLE;

admin_head('Přístupy', $user);
?>

<?php if (is_array($noveHeslo)): ?>
<section class="panel" style="border-color:var(--gold)">
  <div class="panel-head"><h2><?= $noveHeslo['novy'] ? 'Přihlašovací údaje nového účtu' : 'Nové heslo' ?></h2></div>
  <div class="panel-body">
    <p>Předejte je prosím <b><?= e($noveHeslo['jmeno'] !== '' ? $noveHeslo['jmeno'] : $noveHeslo['email']) ?></b> – osobně, SMS nebo telefonem (heslo raději ne stejným e-mailem jako adresu).
       <b>Heslo se ukazuje jen teď</b>; po opuštění stránky ho už nikdo neuvidí, jde jen vydat nové.</p>
    <div class="tbl-wrap">
      <table><tbody>
        <tr><th style="width:200px">Adresa</th><td><?= e(site_url('admin/')) ?></td></tr>
        <tr><th>E-mail</th><td><?= e($noveHeslo['email']) ?></td></tr>
        <tr><th>Heslo</th><td><b style="font:600 1.15rem/1.3 ui-monospace,Consolas,monospace;letter-spacing:.04em;user-select:all"><?= e($noveHeslo['heslo']) ?></b></td></tr>
      </tbody></table>
    </div>
    <p class="hint">Po prvním přihlášení si heslo změní v <b>Účet → Změna hesla</b>.</p>
  </div>
</section>
<?php endif; ?>

<section class="panel">
  <div class="panel-head"><h2>Účty v administraci</h2></div>
  <div class="panel-body tight">
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>Jméno a e-mail</th><th>Oprávnění</th><th>Poslední přihlášení</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($ucty as $u): $ja = (int)$u['id'] === (int)$user['id']; ?>
          <tr>
            <td><b><?= e($u['jmeno'] !== '' ? $u['jmeno'] : '—') ?></b><?= $ja ? ' ' . badge('to jste vy', 'zlato') : '' ?><br><span class="hint"><?= e($u['email']) ?></span></td>
            <td>
              <?php if ($ja): ?>
                <?= badge(role_nazev((string)$u['role']), $u['role'] === 'admin' ? 'navy' : 'info') ?>
              <?php else: ?>
                <form method="post" class="form-inline">
                  <?= csrf_field() ?><?= ui_skryte(['action' => 'role', 'id' => (int)$u['id']]) ?>
                  <select name="role" aria-label="Oprávnění pro <?= e($u['jmeno'] ?: $u['email']) ?>">
                    <?php foreach ($moznosti as $k => $t): ?><option value="<?= e($k) ?>"<?= $u['role'] === $k ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-sm btn-ghost">Změnit</button>
                </form>
              <?php endif; ?>
            </td>
            <td><?= !empty($u['last_login']) ? e(cz_datum_cas((string)$u['last_login'])) : '<span class="hint">zatím nikdy</span>' ?></td>
            <td style="white-space:nowrap">
              <?php if ($ja): ?>
                <a class="btn btn-sm btn-ghost" href="ucet.php">Můj účet</a>
              <?php else: ?>
                <?= tlacitko_akce('heslo', (int)$u['id'], 'Nové heslo', 'btn-ghost', [], 'Vydat nové heslo? Staré hned přestane platit a účet se všude odhlásí.') ?>
                <?= tlacitko_smazat((int)$u['id'], 'Opravdu smazat účet ' . ($u['jmeno'] ?: $u['email']) . '? Do administrace se už nepřihlásí.') ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="panel">
  <div class="panel-head"><h2>Přidat přístup</h2></div>
  <div class="panel-body">
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="pridat">
      <?= pole_radek([
            pole_text('jmeno', 'Jméno a příjmení', '', ['maxlength' => 120, 'required' => true, 'autocomplete' => 'off']),
            pole_text('email', 'E-mail (slouží k přihlášení)', '', ['type' => 'email', 'maxlength' => 190, 'required' => true, 'autocomplete' => 'off']),
          ]) ?>
      <?= pole_select('role', 'Oprávnění', 'redaktor', $moznosti, ['hint' => 'Redaktor upravuje obsah webu. Správce webu navíc spravuje tyhle Přístupy.']) ?>
      <p class="hint">Heslo vytvoří web sám a ukáže ho jen jednou, hned po uložení.</p>
      <?= tlacitka_formulare('Založit účet') ?>
    </form>
  </div>
</section>

<?php admin_foot(); ?>
