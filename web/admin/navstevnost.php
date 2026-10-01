<?php
/* Návštěvnost – zobrazení stránek a unikátní návštěvníci po dnech.
   Data sbírá track_visit() (inc/functions.php) BEZ cookies: ukládá se jen
   počet zobrazení cesty za den (cltk_visits) a nevratný denní otisk
   návštěvníka (cltk_visit_log) – žádná IP adresa. Roboti se nepočítají.
   Díky tomu web nepotřebuje cookie lištu. */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const NV_OBDOBI = [7 => '7 dní', 30 => '30 dní', 90 => '90 dní'];

$obdobi = (int)($_GET['obdobi'] ?? 30);
if (!isset(NV_OBDOBI[$obdobi])) $obdobi = 30;

$dnes  = date('Y-m-d');                       // track_visit() zapisuje skutečné datum
$vcera = date('Y-m-d', strtotime('-1 day'));
$od    = date('Y-m-d', strtotime('-' . ($obdobi - 1) . ' days'));
$od7   = date('Y-m-d', strtotime('-6 days'));
$od30  = date('Y-m-d', strtotime('-29 days'));
$odMin = min($od, $od30);

$zobrazeni = [];
$lide = [];
try {
    foreach (rows('SELECT day, SUM(hits) AS h FROM cltk_visits WHERE day >= ? GROUP BY day', [$odMin]) as $r) {
        $zobrazeni[substr((string)$r['day'], 0, 10)] = (int)$r['h'];
    }
    foreach (rows('SELECT day, COUNT(*) AS c FROM cltk_visit_log WHERE day >= ? GROUP BY day', [$odMin]) as $r) {
        $lide[substr((string)$r['day'], 0, 10)] = (int)$r['c'];
    }
} catch (Throwable $e) {
    error_log('[navstevnost] ' . $e->getMessage());
}

/** Součet hodnot mapy den => číslo od $od do dneška. */
function nv_soucet(array $mapa, string $od, string $do): int {
    $s = 0;
    foreach ($mapa as $d => $n) if ($d >= $od && $d <= $do) $s += $n;
    return $s;
}

$dny = [];
for ($i = $obdobi - 1; $i >= 0; $i--) $dny[] = date('Y-m-d', strtotime("-$i days"));
$max = 1;
foreach ($dny as $d) $max = max($max, $zobrazeni[$d] ?? 0);

$hitsObdobi = nv_soucet($zobrazeni, $od, $dnes);
$lideObdobi = nv_soucet($lide, $od, $dnes);
$hits7 = nv_soucet($zobrazeni, $od7, $dnes);
$lide7 = nv_soucet($lide, $od7, $dnes);
$hits30 = nv_soucet($zobrazeni, $od30, $dnes);
$lide30 = nv_soucet($lide, $od30, $dnes);

$stranky = [];
if ($hitsObdobi > 0) {
    try {
        $stranky = rows('SELECT path, SUM(hits) AS h FROM cltk_visits WHERE day >= ? GROUP BY path ORDER BY h DESC, path LIMIT 25', [$od]);
    } catch (Throwable $e) { $stranky = []; }
}
$maxStr = $stranky ? max(1, (int)$stranky[0]['h']) : 1;

/** Čitelný název stránky podle cesty. */
function nv_nazev(string $cesta): string {
    $mapa = ['/' => 'Úvodní stránka', '/index.php' => 'Úvodní stránka', '/klub.php' => 'Klub', '/clenstvi.php' => 'Členství',
             '/historie.php' => 'Historie', '/vedeni.php' => 'Vedení', '/ctc.php' => 'CTC', '/revue.php' => 'Revue',
             '/areal.php' => 'Areál a služby', '/cenik-kurtu.php' => 'Ceník kurtů', '/privatni-treneri.php' => 'Privátní trenéři',
             '/body-solution.php' => 'Body Solution', '/sportovni-lekarstvi.php' => 'Sportovní lékařství',
             '/zavodni-tenis.php' => 'Závodní tenis', '/zavodni-tenis-treneri.php' => 'Závodní tenis – trenéři',
             '/tenisova-skola.php' => 'Tenisová škola', '/tenisova-skola-ceniky.php' => 'Tenisová škola – ceníky',
             '/tenisova-skola-rozvrhy.php' => 'Tenisová škola – rozvrhy', '/tenisova-skola-treneri.php' => 'Tenisová škola – trenéři',
             '/letni-kempy.php' => 'Letní kempy', '/restaurace.php' => 'Restaurace', '/prague-open.php' => 'Prague Open',
             '/kontakt.php' => 'Kontakt', '/akce.php' => 'Detail akce', '/404.php' => 'Stránka nenalezena'];
    return $mapa[$cesta] ?? '';
}

admin_head('Návštěvnost', $user, [
    'podnadpis' => 'Kolikrát se stránky webu zobrazily a kolik lidí je navštívilo. Počítá se bez cookies a bez ukládání IP adres, roboti se nezapočítávají – proto web nepotřebuje cookie lištu.',
]);
echo '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';
?>

<div class="stats">
  <div class="stat"><b><?= cislo($zobrazeni[$dnes] ?? 0) ?></b><span>Dnes</span><small><?= cislo($lide[$dnes] ?? 0) ?> <?= sklonuj($lide[$dnes] ?? 0, 'návštěvník', 'návštěvníci', 'návštěvníků') ?></small></div>
  <div class="stat"><b><?= cislo($zobrazeni[$vcera] ?? 0) ?></b><span>Včera</span><small><?= cislo($lide[$vcera] ?? 0) ?> <?= sklonuj($lide[$vcera] ?? 0, 'návštěvník', 'návštěvníci', 'návštěvníků') ?></small></div>
  <div class="stat"><b><?= cislo($hits7) ?></b><span>7 dní</span><small><?= cislo($lide7) ?> <?= sklonuj($lide7, 'návštěva', 'návštěvy', 'návštěv') ?></small></div>
  <div class="stat"><b><?= cislo($hits30) ?></b><span>30 dní</span><small><?= cislo($lide30) ?> <?= sklonuj($lide30, 'návštěva', 'návštěvy', 'návštěv') ?></small></div>
</div>

<?= admin_zalozky(array_combine(array_map(fn($n) => 'navstevnost.php?obdobi=' . $n, array_keys(NV_OBDOBI)), array_map(fn($t) => 'Posledních ' . $t, NV_OBDOBI)),
                  'navstevnost.php?obdobi=' . $obdobi) ?>

<section class="panel">
  <div class="panel-head">
    <h2>Zobrazení po dnech <small><?= cislo($hitsObdobi) ?> za <?= e(NV_OBDOBI[$obdobi]) ?></small></h2>
    <span class="hint">Najeďte na sloupec pro přesné číslo. Navy sloupec je dnešek.</span>
  </div>
  <div class="panel-body">
    <?php if ($hitsObdobi === 0): ?>
      <p class="hint" style="margin:0 0 12px">Za toto období zatím žádné návštěvy. Statistika se začne plnit, jakmile web někdo navštíví.<?= setting('rezim_pripravy') === '1' ? ' Dokud je zapnutý režim přípravy, návštěvníci vidí jen přípravnou stránku a ta se nepočítá.' : '' ?></p>
    <?php endif; ?>
    <div class="chart uv-graf<?= $obdobi > 30 ? ' uv-graf--husty' : '' ?>" role="img" aria-label="Zobrazení stránek po dnech za posledních <?= e(NV_OBDOBI[$obdobi]) ?>, celkem <?= (int)$hitsObdobi ?>">
      <?php foreach ($dny as $d): $h = $zobrazeni[$d] ?? 0; ?>
        <div class="bar<?= $d === $dnes ? ' dnes' : '' ?><?= $h === 0 ? ' nula' : '' ?>" style="height:<?= max(2, (int)round($h / $max * 100)) ?>%" title="<?= e(cz_date($d) . ': ' . $h . '× · ' . ($lide[$d] ?? 0) . ' návštěvníků') ?>"><span><?= (int)$h ?></span></div>
      <?php endforeach; ?>
    </div>
    <?php /* popisky osy: absolutně podle pozice sloupce, krajní zarovnané dovnitř – nikdy nepřetečou */
      $krok = $obdobi <= 7 ? 1 : ($obdobi <= 30 ? 5 : 15);
      $n = count($dny); ?>
    <div class="uv-graf-osa" aria-hidden="true">
      <?php foreach ($dny as $i => $d): if (($n - 1 - $i) % $krok !== 0) continue;
          if ($i / $n < 0.08) $styl = 'left:' . round($i / $n * 100, 2) . '%';
          elseif (($i + 1) / $n > 0.92) $styl = 'right:' . round(($n - 1 - $i) / $n * 100, 2) . '%';
          else $styl = 'left:' . round(($i + 0.5) / $n * 100, 2) . '%;transform:translateX(-50%)'; ?>
        <span style="<?= $styl ?>"><?= e(date('j. n.', strtotime($d))) ?></span>
      <?php endforeach; ?>
    </div>
    <details class="uv-mezera">
      <summary class="hint" style="cursor:pointer;min-height:44px;display:flex;align-items:center">Čísla po dnech v tabulce</summary>
      <div class="tbl-wrap">
        <table>
          <thead><tr><th>Den</th><th class="right">Zobrazení</th><th class="right">Návštěvníci</th></tr></thead>
          <tbody>
          <?php foreach (array_reverse($dny) as $d): ?>
            <tr><td><?= e(CZ_DNY[(int)date('N', strtotime($d))] . ' ' . cz_date($d)) ?></td><td class="right"><?= cislo($zobrazeni[$d] ?? 0) ?></td><td class="right"><?= cislo($lide[$d] ?? 0) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </details>
  </div>
</section>

<section class="panel">
  <div class="panel-head">
    <h2>Nejnavštěvovanější stránky</h2>
    <span class="hint">Za posledních <?= e(NV_OBDOBI[$obdobi]) ?> · <?= cislo($lideObdobi) ?> <?= sklonuj($lideObdobi, 'návštěva', 'návštěvy', 'návštěv') ?></span>
  </div>
  <?php if (!$stranky): ?>
    <?= prazdny_stav('Zatím nemáme co ukazovat', 'Jakmile web někdo navštíví, uvidíte tady, které stránky lidé otevírají nejčastěji.') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Stránka</th><th class="right">Zobrazení</th><th style="width:30%">Podíl</th></tr></thead>
        <tbody>
        <?php foreach ($stranky as $p):
            $cesta = (string)$p['path'];
            $nazev = nv_nazev($cesta);
            $odkaz = preg_match('~^/[a-z0-9\-_/.]*$~i', $cesta) && !str_contains($cesta, '..') ? url(ltrim($cesta, '/')) : ''; ?>
          <tr>
            <td data-label="Stránka" class="td-nazev uv-cesta">
              <b><?= e($nazev !== '' ? $nazev : $cesta) ?></b>
              <small><?php if ($odkaz !== ''): ?><a href="<?= e($odkaz) ?>" target="_blank" rel="noopener"><?= e($cesta) ?></a><?php else: ?><?= e($cesta) ?><?php endif; ?></small>
            </td>
            <td data-label="Zobrazení" class="right uv-nowrap"><?= cislo((int)$p['h']) ?></td>
            <td data-label="Podíl"><span class="uv-pruh" style="width:<?= max(1, (int)round((int)$p['h'] / $maxStr * 100)) ?>%"></span>
              <small class="uv-tlumene"><?= $hitsObdobi > 0 ? (int)round((int)$p['h'] / $hitsObdobi * 100) : 0 ?> %</small></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<p class="hint">Jak se počítá: každé zobrazení stránky se přičte k dané adrese a dni. Návštěvník se pozná podle nevratného otisku, který se každý den spočítá znovu z adresy připojení a prohlížeče – samotná adresa se neukládá a z otisku se nedá zjistit, kdo to byl. Součet za více dní je proto počet návštěv, ne různých lidí. Administrace, přípravná stránka a roboti se nepočítají.</p>

<?php admin_foot(); ?>
