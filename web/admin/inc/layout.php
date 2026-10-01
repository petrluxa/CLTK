<?php
/* Společná hlavička a patička administrace I. ČLTK Praha.

   Vzor stránky modulu (podrobně v admin/PRUVODCE.md):

       require __DIR__ . '/inc/layout.php';
       $user = require_login();
       if ($_SERVER['REQUEST_METHOD'] === 'POST') {
           admin_post_zacatek('aktuality.php');     // velikost požadavku + CSRF
           …zápis…
           redirect('aktuality.php', 'Uloženo.');
       }
       admin_head('Aktuality z klubu', $user);
       …
       admin_foot();
*/

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ui.php';

/**
 * Moduly v postranním panelu – pořadí podle ZADANI §6.
 * [soubor, název, další soubory, které patří pod stejnou položku]
 */
function admin_moduly(): array {
    return [
        ['index.php',       'Přehled',              []],
        ['navstevnost.php', 'Návštěvnost',          []],
        ['oznameni.php',    'Informační lišta',     []],
        ['galerie.php',     'Úvodní galerie',       []],
        ['aktuality.php',   'Aktuality z klubu',    []],
        ['vysledky.php',    'Výsledky hráčů',       []],
        ['akce.php',        'Kalendář akcí',        ['akce-edit.php']],
        ['prihlasky.php',   'Přihlášky',            []],
        ['treneri.php',     'Trenéři',              []],
        ['ceniky.php',      'Ceníky',               []],
        ['skola.php',       'Tenisová škola',       []],
        ['sluzby.php',      'Areál a služby',       []],
        ['historie.php',    'Historie',             []],
        ['revue.php',       'Revue a newslettery',  []],
        ['vedeni.php',      'Vedení a CTC',         ['ctc.php']],
        ['stranky.php',     'Stránky',              []],
        ['partneri.php',    'Partneři',             []],
        ['dokumenty.php',   'Dokumenty',            []],
        ['nastaveni.php',   'Texty a údaje',        []],
        ['ucet.php',        'Účet',                 []],
    ];
}

/** Počet nevyřízených přihlášek (akce + členství) pro štítek v menu a na Přehledu. */
function admin_pocet_novych_prihlasek(): int {
    try {
        return (int)val('SELECT COUNT(*) FROM cltk_signups WHERE vyrizeno = 0')
             + (int)val("SELECT COUNT(*) FROM cltk_prihlasky_clenstvi WHERE stav = 'nova'");
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Úklid osobních údajů: přihlášky do klubu ve stavu vyřízená / zamítnutá a přihlášky
 * k akcím starší než „Mazat přihlášky po (měsících)“ (Texty a údaje, výchozí 12)
 * se smažou. Nové (nevyřízené) přihlášky do klubu zůstávají vždy. 0 = nemazat.
 * Spouští se občas při otevření administrace (a vždy na stránce Přihlášky).
 */
function admin_uklid_prihlasek(bool $vzdy = false): int {
    $mesicu = (int)setting('prihlasky_mazat_mesicu', '12');
    if ($mesicu <= 0 || (!$vzdy && random_int(1, 20) !== 1)) return 0;
    $hranice = date('Y-m-d H:i:s', strtotime('-' . min($mesicu, 120) . ' months'));
    try {
        $n = q("DELETE FROM cltk_prihlasky_clenstvi WHERE stav IN ('vyrizena', 'zamitnuta') AND COALESCE(updated_at, created_at) < ?", [$hranice])->rowCount();
        $n += q('DELETE FROM cltk_signups WHERE created_at < ?', [$hranice])->rowCount();
        return $n;
    } catch (Throwable $e) {
        error_log('[admin] úklid přihlášek: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Hlavička administrace.
 * $o: 'podnadpis' => text pod nadpisem, 'akce' => HTML tlačítek vpravo od nadpisu,
 *     'zpet' => [url, text] odkaz zpět nad nadpisem, 'sirka' => 'uzka' pro formuláře.
 */
function admin_head(string $title, ?array $user = null, array $o = []): void {
    $f = flash();
    $cur = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($user) admin_uklid_prihlasek($cur === 'prihlasky.php');
    $nove = $user ? admin_pocet_novych_prihlasek() : 0;
    $rezim = setting('rezim_pripravy') === '1';
    if (!headers_sent()) header('Content-Type: text/html; charset=UTF-8');
    ?><!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> – administrace I. ČLTK Praha</title>
<link rel="icon" href="<?= e(logo_url('favicon')) ?>" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Cormorant+SC:wght@500;600&family=Jost:wght@400;500;600&display=swap&subset=latin-ext">
<link rel="stylesheet" href="<?= e(BASE_PATH . verze('admin/assets/admin.css')) ?>">
</head>
<body class="admin">
<a class="preskocit" href="#obsah">Přeskočit na obsah</a>
<div class="stuha" aria-hidden="true"><span></span><span></span></div>
<header class="mobil-lista">
  <a href="index.php" class="mobil-lista__znak"><img src="<?= e(logo_url('svg')) ?>" alt="" width="28" height="32"><span>I. ČLTK Praha</span></a>
  <button class="menu-btn" id="menuBtn" type="button" aria-expanded="false" aria-controls="side">
    <span class="menu-btn__car" aria-hidden="true"></span><span class="menu-btn__text">Menu</span>
  </button>
</header>
<div class="shell">
  <aside class="side" id="side" aria-label="Moduly administrace">
    <a href="index.php" class="side-brand">
      <img src="<?= e(logo_url('svg')) ?>" alt="" width="44" height="50">
      <span><b>I. ČLTK Praha</b><small>Administrace</small></span>
    </a>
    <nav class="side-nav">
      <?php foreach (admin_moduly() as $i => [$href, $label, $dalsi]):
        $on = $cur === $href || in_array($cur, $dalsi, true); ?>
        <a href="<?= e($href) ?>"<?= $on ? ' class="on" aria-current="page"' : '' ?>>
          <i aria-hidden="true"><?= rimske($i + 1) ?></i><span><?= e($label) ?></span>
          <?php if ($href === 'prihlasky.php' && $nove > 0): ?><em class="side-pocet" title="Nevyřízené přihlášky"><?= (int)$nove ?></em><?php endif; ?>
        </a>
        <?php if ($href === 'vedeni.php' && $on): ?>
          <a href="vedeni.php" class="side-sub<?= $cur === 'vedeni.php' ? ' on' : '' ?>"><span>Vedení klubu</span></a>
          <a href="ctc.php" class="side-sub<?= $cur === 'ctc.php' ? ' on' : '' ?>"><span>Centenary Tennis Clubs</span></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <?php if ($rezim): ?>
        <a class="side-rezim" href="nastaveni.php#rezim">Režim přípravy je zapnutý</a>
      <?php endif; ?>
      <a href="<?= e(url('')) ?>" target="_blank" rel="noopener" class="side-link">Zobrazit web <span aria-hidden="true">↗</span></a>
      <?php if ($user): ?>
        <span class="side-who"><?= e($user['jmeno'] !== '' ? $user['jmeno'] : $user['email']) ?></span>
        <form method="post" action="logout.php" class="side-odhlasit">
          <?= csrf_field() ?>
          <button type="submit" class="side-link side-link--btn">Odhlásit se</button>
        </form>
      <?php endif; ?>
    </div>
  </aside>
  <div class="side-clona" id="sideClona" hidden></div>

  <main class="main<?= ($o['sirka'] ?? '') === 'uzka' ? ' main--uzka' : '' ?>" id="obsah" tabindex="-1">
    <div class="topbar">
      <div class="topbar__nadpis">
        <?php if (!empty($o['zpet'])): ?>
          <a class="topbar__zpet" href="<?= e($o['zpet'][0]) ?>">← <?= e($o['zpet'][1] ?? 'Zpět') ?></a>
        <?php endif; ?>
        <h1><?= e($title) ?></h1>
        <?php if (!empty($o['podnadpis'])): ?><p class="topbar__pod"><?= $o['podnadpis'] ?></p><?php endif; ?>
      </div>
      <?php if (!empty($o['akce'])): ?><div class="topbar__akce btn-row"><?= $o['akce'] ?></div><?php endif; ?>
    </div>

    <?php if ($f): ?>
      <div class="flash <?= e($f['type']) ?>" role="<?= $f['type'] === 'err' ? 'alert' : 'status' ?>"><?= e($f['msg']) ?></div>
    <?php endif; ?>
<?php
}

function admin_foot(): void {
    ?>
  </main>
</div>
<script src="<?= e(BASE_PATH . verze('admin/assets/admin.js')) ?>" defer></script>
<script src="<?= e(BASE_PATH . verze('admin/assets/editor.js')) ?>" defer></script>
</body>
</html>
<?php
}

/* --- drobné pomůcky (kompatibilita se vzory z Liberce) --- */
function old(string $klic, $vychozi = '') {
    return $_POST[$klic] ?? $vychozi;
}
