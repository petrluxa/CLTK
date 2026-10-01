<?php
/* Výsledky hráčů – vodorovný pás sloupců na úvodní stránce (ZADANI §4.5):
   zlatý štítek turnaje, jméno, krátký text, velké sety, verdikt.
   Řadí se podle data (nejnovější první); na úvodu je posledních osm
   zobrazených (posledni_vysledky(8)). Šipky mění pořadí jen u výsledků
   se stejným datem. Úprava výsledku: vysledky-edit.php. */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const VY_STRANKA = 'vysledky.php';
const VY_NA_UVODU = 8;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek(VY_STRANKA);
    $akce = vstup('action', 20);
    $id   = (int)vstup_int('id');
    $r    = $id ? row('SELECT * FROM cltk_vysledky WHERE id = ?', [$id]) : null;
    if (!$r) redirect(VY_STRANKA, 'Výsledek nebyl nalezen.', 'err');

    if ($akce === 'prepnout') {
        $novy = admin_prepni('cltk_vysledky', $id);
        redirect(VY_STRANKA, $novy ? 'Výsledek je zobrazený.' : 'Výsledek je skrytý, na webu se neukazuje.');
    }
    if ($akce === 'nahoru' || $akce === 'dolu') {
        /* jen v rámci stejného data – jinak rozhoduje datum */
        admin_posun('cltk_vysledky', $id, $akce === 'nahoru' ? -1 : 1, 'datum');
        redirect(VY_STRANKA, 'Pořadí bylo změněno.');
    }
    if ($akce === 'smazat') {
        q('DELETE FROM cltk_vysledky WHERE id = ?', [$id]);
        admin_smazat_soubory([(string)$r['foto']]);
        redirect(VY_STRANKA, 'Výsledek „' . $r['hraci'] . ' – ' . $r['stitek'] . '“ byl smazán.');
    }
    redirect(VY_STRANKA, 'Neznámý požadavek.', 'err');
}

$radky = rows('SELECT * FROM cltk_vysledky ORDER BY datum DESC, poradi, id');
$naUvodu = array_map(fn($r) => (int)$r['id'], posledni_vysledky(VY_NA_UVODU));

admin_head('Výsledky hráčů', $user, [
    'podnadpis' => 'Vodorovný pás na úvodní stránce – štítek turnaje, hráč, krátký text, velké sety a verdikt. Ukazuje se posledních ' . VY_NA_UVODU . ' zobrazených výsledků podle data.',
    'akce'      => '<a class="btn btn-primary" href="vysledky-edit.php">Přidat výsledek</a>',
]);
echo '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';
?>

<?php if (count($naUvodu) < 4 && $radky): ?>
  <div class="uv-info uv-info--warn">Na úvodu jsou teď jen <?= count($naUvodu) ?> výsledky. Klub chce, aby jich v pásu bylo víc – ideálně šest až osm.</div>
<?php endif; ?>

<section class="panel">
  <div class="panel-head">
    <h2>Výsledky <small><?= count($naUvodu) ?> na úvodu · <?= count($radky) ?> celkem</small></h2>
    <span class="hint">Řazeno podle data, nejnovější první.</span>
  </div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím žádný výsledek', 'Přidejte první – datum, turnaj, hráč a sety.',
          '<a class="btn btn-primary" href="vysledky-edit.php">Přidat výsledek</a>') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Datum</th><th>Turnaj a hráč</th><th>Sety</th><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($radky as $i => $r):
            $id = (int)$r['id'];
            $uvod = in_array($id, $naUvodu, true);
            $datum = substr((string)$r['datum'], 0, 10);
            $prvni = $i === 0 || substr((string)$radky[$i - 1]['datum'], 0, 10) !== $datum || $datum === '';
            $posledni = $i === count($radky) - 1 || substr((string)$radky[$i + 1]['datum'], 0, 10) !== $datum || $datum === '';
            $tridy = trim(((int)$r['visible'] === 1 ? '' : 'je-skryte') . ($uvod ? ' uv-na-uvodu' : '')); ?>
          <tr<?= $tridy !== '' ? ' class="' . $tridy . '"' : '' ?>>
            <td data-label="Datum" class="uv-nowrap"><?= $datum !== '' ? e(cz_date($datum)) : badge('bez data', 'warn') ?>
              <?php if ((string)$r['misto'] !== ''): ?><br><small class="uv-tlumene"><?= e($r['misto']) ?></small><?php endif; ?></td>
            <td data-label="Turnaj a hráč" class="td-nazev">
              <span class="uv-stitek-zlaty"><?= e($r['stitek']) ?></span>
              <b><?= e($r['hraci']) ?></b>
              <?php if ((string)$r['souper'] !== ''): ?><small>soupeř: <?= e($r['souper']) ?></small><?php endif; ?>
            </td>
            <td data-label="Sety">
              <span class="uv-sety"><?php foreach (sety_rozloz((string)$r['sety']) as $set): ?><span<?= $set['vyhra'] ? '' : ' class="p"' ?>><?= e($set['hlavni']) ?><?php if ($set['doplnek'] !== ''): ?><small> <?= e($set['doplnek']) ?></small><?php endif; ?></span><?php endforeach; ?></span>
              <?php if ((string)$r['verdikt'] !== ''): ?><br><span class="uv-verdikt"><?= e($r['verdikt']) ?></span><?php endif; ?>
            </td>
            <td data-label="Stav"><?= stav_badge($r['visible']) ?><?= $uvod ? ' ' . badge('Na úvodu', 'zlato') : '' ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?php if (!$prvni || !$posledni): ?><?= tlacitka_poradi($id, $prvni, $posledni) ?><?php endif; ?>
              <a class="btn btn-sm btn-ghost" href="vysledky-edit.php?id=<?= $id ?>">Upravit</a>
              <?= tlacitko_prepnout($id, $r['visible']) ?>
              <?= tlacitko_smazat($id, 'Smazat výsledek „' . $r['hraci'] . ' – ' . $r['stitek'] . '“? Nejde to vrátit – když ho chcete jen schovat, použijte Skrýt.') ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<p class="hint">Pořadí určuje datum. Šipky se ukážou jen u výsledků ze stejného dne – mezi nimi rozhodnete, který bude v pásu první.</p>

<?php admin_foot(); ?>
