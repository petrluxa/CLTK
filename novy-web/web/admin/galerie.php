<?php
/* Úvodní galerie – snímky, které se na úvodu střídají s přechodem tvarem
   klubového štítu (ZADANI §4.3). Snímek je buď fotka, nebo navy deska
   s výsledkem (BJK Cup 2026, dokud klub nedodá fotku). Úprava snímku
   je v galerie-edit.php. */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const GA_STRANKA = 'galerie.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek(GA_STRANKA);
    $akce = vstup('action', 20);
    $id   = (int)vstup_int('id');
    $r    = $id ? row('SELECT * FROM cltk_uvodni_galerie WHERE id = ?', [$id]) : null;
    if (!$r) redirect(GA_STRANKA, 'Snímek nebyl nalezen.', 'err');

    if ($akce === 'prepnout') {
        $novy = admin_prepni('cltk_uvodni_galerie', $id);
        redirect(GA_STRANKA, $novy ? 'Snímek „' . $r['rejstrik'] . '“ je v galerii.' : 'Snímek „' . $r['rejstrik'] . '“ je skrytý.');
    }
    if ($akce === 'nahoru' || $akce === 'dolu') {
        admin_posun('cltk_uvodni_galerie', $id, $akce === 'nahoru' ? -1 : 1);
        redirect(GA_STRANKA, 'Pořadí bylo změněno.');
    }
    if ($akce === 'smazat') {
        q('DELETE FROM cltk_uvodni_galerie WHERE id = ?', [$id]);
        admin_smazat_soubory([(string)$r['foto']]);              // až po smazání řádku
        redirect(GA_STRANKA, 'Snímek „' . $r['rejstrik'] . '“ byl smazán.');
    }
    redirect(GA_STRANKA, 'Neznámý požadavek.', 'err');
}

$radky = rows('SELECT * FROM cltk_uvodni_galerie ORDER BY poradi, id');
$viditelne = array_values(array_filter($radky, fn($r) => (int)$r['visible'] === 1));
$poziceNaWebu = [];
foreach ($viditelne as $i => $r) $poziceNaWebu[(int)$r['id']] = $i + 1;

admin_head('Úvodní galerie', $user, [
    'podnadpis' => 'Snímky vpravo nahoře na úvodní stránce. Střídají se samy, přechod má tvar klubového štítu. Pod galerií je rejstřík s římskými číslicemi a popisek snímku.',
    'akce'      => '<a class="btn btn-primary" href="galerie-edit.php">Přidat snímek</a>',
]);
echo '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';
?>

<?php if (!$viditelne): ?>
  <div class="uv-info uv-info--warn">V galerii teď není žádný zobrazený snímek – vpravo na úvodu by zůstalo prázdné místo. Zobrazte aspoň jeden snímek.</div>
<?php elseif (count($viditelne) < 2): ?>
  <div class="uv-info uv-info--warn">Zobrazený je jen jeden snímek, galerie se tedy nebude střídat. Klub počítá se čtyřmi.</div>
<?php endif; ?>

<section class="panel">
  <div class="panel-head">
    <h2>Snímky <small><?= count($viditelne) ?> v galerii · <?= count($radky) ?> celkem</small></h2>
    <span class="hint">Pořadí šipkami = pořadí na webu (I, II, III …).</span>
  </div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Galerie je prázdná', 'Přidejte první snímek – fotku, nebo navy desku s výsledkem.',
          '<a class="btn btn-primary" href="galerie-edit.php">Přidat snímek</a>') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Náhled</th><th>Rejstřík a popisek</th><th>Typ</th><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($radky as $i => $r):
            $id = (int)$r['id'];
            $jeFoto = $r['typ'] !== 'deska'; ?>
          <tr<?= (int)$r['visible'] === 1 ? '' : ' class="je-skryte"' ?>>
            <td data-label="Náhled">
              <?php if ($jeFoto && (string)$r['foto'] !== ''): ?>
                <img class="thumb-sm" src="<?= e(upload_url($r['foto'])) ?>" alt="" style="object-position:<?= e($r['fokus']) ?>">
              <?php elseif ($jeFoto): ?>
                <span class="thumb-ph">bez fotky</span>
              <?php else: ?>
                <span class="uv-deska uv-deska--mini" aria-hidden="true"><span><?= e($r['deska_skore'] !== '' ? $r['deska_skore'] : 'deska') ?></span></span>
              <?php endif; ?>
            </td>
            <td data-label="Rejstřík a popisek" class="td-nazev">
              <b><?php if (isset($poziceNaWebu[$id])): ?><span class="uv-rejstrik"><?= rimske($poziceNaWebu[$id]) ?></span><?php endif; ?><?= e($r['rejstrik'] !== '' ? $r['rejstrik'] : 'bez rejstříku') ?></b>
              <?php if ((string)$r['popisek'] !== ''): ?><div class="uv-popisek"><?= html_inline($r['popisek']) ?></div><?php endif; ?>
              <?php if ((string)$r['kredit'] !== ''): ?><small><?= e($r['kredit']) ?></small><?php endif; ?>
            </td>
            <td data-label="Typ"><?= $jeFoto ? badge('Fotka', 'info') : badge('Deska', 'navy') ?></td>
            <td data-label="Stav"><?= stav_badge($r['visible'], 'V galerii') ?>
              <?php if ($jeFoto && ((string)$r['foto'] === '' || !is_file(UPLOAD_DIR . '/' . $r['foto']))): ?> <?= badge('Chybí fotka', 'err') ?><?php endif; ?>
            </td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitka_poradi($id, $i === 0, $i === count($radky) - 1) ?>
              <a class="btn btn-sm btn-ghost" href="galerie-edit.php?id=<?= $id ?>">Upravit</a>
              <?= tlacitko_prepnout($id, $r['visible']) ?>
              <?= tlacitko_smazat($id, 'Smazat snímek „' . $r['rejstrik'] . '“' . ($jeFoto ? ' i s fotkou' : '') . '? Nejde to vrátit – když ho chcete jen schovat, použijte Skrýt.') ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php admin_foot(); ?>
