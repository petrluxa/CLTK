<?php
/* Přehled – první stránka po přihlášení. */
require_once __DIR__ . '/inc/layout.php';
$user = require_login();

/** Počet řádků s pojistkou (modul ještě nemusí mít data). */
function prehled_pocet(string $sql, array $p = []): int {
    try { return (int)val($sql, $p); } catch (Throwable $e) { return 0; }
}

$dnes = dnes();
$od7  = date('Y-m-d', strtotime($dnes . ' -6 days'));

$noveAkce    = prehled_pocet('SELECT COUNT(*) FROM cltk_signups WHERE vyrizeno = 0');
$noveClen    = prehled_pocet("SELECT COUNT(*) FROM cltk_prihlasky_clenstvi WHERE stav = 'nova'");
$pocAktualit = prehled_pocet('SELECT COUNT(*) FROM cltk_aktuality WHERE visible = 1');
$pocVysl     = prehled_pocet('SELECT COUNT(*) FROM cltk_vysledky WHERE visible = 1');
$pocPartneru = prehled_pocet('SELECT COUNT(*) FROM cltk_partneri WHERE visible = 1');
$pocTreneru  = prehled_pocet('SELECT COUNT(*) FROM cltk_treneri WHERE visible = 1');
$hits7       = prehled_pocet('SELECT COALESCE(SUM(hits), 0) FROM cltk_visits WHERE day >= ?', [$od7]);
$lide7       = prehled_pocet('SELECT COUNT(*) FROM cltk_visit_log WHERE day >= ?', [$od7]);

$letos = akce_rok((int)substr($dnes, 0, 4));
$nadchazejici = count(array_filter($letos, fn($a) => !$a['probehla']));
$nejblizsi = nejblizsi_akce();
$oznameni = aktivni_oznameni();
$rezim = setting('rezim_pripravy') === '1';

/* --- poslední přihlášky: k akcím i do klubu, dohromady podle času --- */
$prihlasky = [];
try {
    foreach (rows('SELECT s.id, s.jmeno, s.pocet, s.vyrizeno, s.created_at, a.nazev AS akce
                     FROM cltk_signups s LEFT JOIN cltk_akce a ON a.id = s.akce_id
                 ORDER BY s.created_at DESC, s.id DESC LIMIT 6') as $r) {
        $prihlasky[] = ['druh' => 'akce', 'jmeno' => $r['jmeno'], 'co' => $r['akce'] ?? '', 'pocet' => (int)$r['pocet'],
                        'nova' => (int)$r['vyrizeno'] === 0, 'kdy' => (string)$r['created_at']];
    }
    foreach (rows('SELECT id, jmeno, prijmeni, typ_clenstvi, stav, created_at FROM cltk_prihlasky_clenstvi
                 ORDER BY created_at DESC, id DESC LIMIT 6') as $r) {
        $prihlasky[] = ['druh' => 'clenstvi', 'jmeno' => trim($r['jmeno'] . ' ' . $r['prijmeni']), 'co' => $r['typ_clenstvi'],
                        'pocet' => 0, 'nova' => $r['stav'] === 'nova', 'kdy' => (string)$r['created_at']];
    }
} catch (Throwable $e) { /* modul přihlášek ještě nemusí mít tabulky */ }
usort($prihlasky, fn($a, $b) => strcmp($b['kdy'], $a['kdy']));
$prihlasky = array_slice($prihlasky, 0, 6);

/* --- návštěvnost po dnech --- */
$dny = [];
for ($i = 6; $i >= 0; $i--) {
    $dny[date('Y-m-d', strtotime($dnes . " -$i days"))] = 0;
}
try {
    foreach (rows('SELECT day, SUM(hits) AS h FROM cltk_visits WHERE day >= ? GROUP BY day', [$od7]) as $r) {
        $d = substr((string)$r['day'], 0, 10);
        if (isset($dny[$d])) $dny[$d] = (int)$r['h'];
    }
} catch (Throwable $e) {}
$max = max(1, max($dny));

$jmeno = $user['jmeno'] !== '' ? $user['jmeno'] : $user['email'];
admin_head('Přehled', $user, ['podnadpis' => 'Vítejte zpět, ' . e($jmeno) . '. Odtud spravujete celý obsah webu I. ČLTK Praha.']);
?>

<div class="stats">
  <a class="stat<?= ($noveAkce + $noveClen) > 0 ? ' stat--zvyraznit' : '' ?>" href="prihlasky.php">
    <b><?= cislo($noveAkce + $noveClen) ?></b><span>Nevyřízené přihlášky</span>
    <small><?= cislo($noveAkce) ?> k akcím · <?= cislo($noveClen) ?> do klubu</small>
  </a>
  <a class="stat" href="akce.php"><b><?= cislo($nadchazejici) ?></b><span>Nadcházející akce</span><small>v kalendáři <?= e(substr($dnes, 0, 4)) ?></small></a>
  <a class="stat" href="aktuality.php"><b><?= cislo($pocAktualit) ?></b><span>Aktuality na webu</span></a>
  <a class="stat" href="vysledky.php"><b><?= cislo($pocVysl) ?></b><span>Výsledky hráčů</span></a>
  <a class="stat" href="treneri.php"><b><?= cislo($pocTreneru) ?></b><span>Trenéři</span></a>
  <a class="stat" href="partneri.php"><b><?= cislo($pocPartneru) ?></b><span>Partneři</span></a>
</div>

<div class="mrizka-panelu">
  <section class="panel">
    <div class="panel-head"><h2>Na webu právě teď</h2></div>
    <ul class="seznam-radku">
      <li>
        <span><b>Informační lišta</b><br>
          <small><?= $oznameni ? e($oznameni[0]['text']) : 'Žádná aktivní zpráva – lišta se na webu nezobrazuje.' ?></small></span>
        <a class="btn btn-sm btn-ghost" href="oznameni.php">Upravit</a>
      </li>
      <li>
        <span><b>Nejbližší akce v kalendáři</b><br>
          <small><?= $nejblizsi ? e($nejblizsi['nazev'] . ' · ' . akce_termin($nejblizsi)) : 'V kalendáři není žádná nadcházející akce.' ?></small></span>
        <a class="btn btn-sm btn-ghost" href="akce.php">Kalendář</a>
      </li>
      <li>
        <span><b>Režim přípravy</b><br>
          <small><?= $rezim ? 'Zapnutý – návštěvníci vidí přípravnou stránku, vy celý web.' : 'Vypnutý – web vidí všichni.' ?></small></span>
        <?= $rezim ? badge('Zapnuto', 'warn') : badge('Web je veřejný', 'ok') ?>
      </li>
    </ul>
  </section>

  <section class="panel">
    <div class="panel-head">
      <h2>Nové přihlášky</h2>
      <a class="btn btn-sm btn-ghost" href="prihlasky.php">Všechny přihlášky</a>
    </div>
    <?php if ($prihlasky): ?>
      <div class="tbl-wrap tbl-karty">
        <table>
          <thead><tr><th>Jméno</th><th>Kam</th><th class="right">Přišla</th></tr></thead>
          <tbody>
          <?php foreach ($prihlasky as $p): ?>
            <tr>
              <td data-label="Jméno"><?= e($p['jmeno'] !== '' ? $p['jmeno'] : 'Bez jména') ?> <?= $p['nova'] ? badge('Nová', 'zlato') : '' ?></td>
              <td data-label="Kam" class="tlumene">
                <?= $p['druh'] === 'clenstvi' ? 'Členství' . ($p['co'] !== '' ? ' · ' . typo($p['co']) : '')
                   : ($p['co'] !== '' ? typo($p['co']) : badge('Akce smazána', 'off')) . ($p['pocet'] > 1 ? ' · ' . (int)$p['pocet'] . ' os.' : '') ?>
              </td>
              <td data-label="Přišla" class="right nowrap tlumene"><?= e(cz_datum_cas($p['kdy'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <?= prazdny_stav('Žádné přihlášky', 'Jakmile někdo odešle přihlášku k akci nebo do klubu, uvidíte ji tady.') ?>
    <?php endif; ?>
  </section>
</div>

<section class="panel">
  <div class="panel-head">
    <h2>Návštěvnost za posledních 7 dní</h2>
    <a class="btn btn-sm btn-ghost" href="navstevnost.php">Podrobná statistika</a>
  </div>
  <div class="panel-body">
    <?php if ($hits7 > 0): ?>
      <p>Stránky webu se zobrazily <b><?= cislo($hits7) ?>×</b>, navštívilo je <b><?= cislo($lide7) ?></b>
         <?= sklonuj($lide7, 'návštěvník', 'návštěvníci', 'návštěvníků') ?>. Počítá se bez cookies, roboti se nezapočítávají.</p>
    <?php else: ?>
      <p class="hint">Zatím žádné návštěvy. Statistika se začne plnit, jakmile web někdo navštíví (bez cookies, roboti se nepočítají).</p>
    <?php endif; ?>
    <div class="chart" role="img" aria-label="Zobrazení stránek po dnech za posledních 7 dní">
      <?php foreach ($dny as $d => $h): ?>
        <div class="bar<?= $d === $dnes ? ' dnes' : '' ?>" style="height:<?= max(2, (int)round($h / $max * 100)) ?>%"><span><?= $h ?></span></div>
      <?php endforeach; ?>
    </div>
    <div class="chart-x" aria-hidden="true">
      <?php foreach ($dny as $d => $h): ?><div><?= e(date('j. n.', strtotime($d))) ?></div><?php endforeach; ?>
    </div>
  </div>
</section>

<?php admin_foot(); ?>
