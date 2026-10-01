<?php
/* Kalendář akcí – výpis celého roku na úvodu (měsíc · název · termín)
   a okno s detailem nejbližší akce (ZADANI §4.6, §0.3, §0.4).
   Řadí se jako na webu: měsíc, začátek (bez data = konec měsíce), pořadí.
   Šipky mění pořadí jen u akcí se stejným měsícem a začátkem (typicky
   několik prosincových akcí, jejichž termín doplní klub).
   Úprava akce, formulář přihlášky a seznam přihlášených: akce-edit.php. */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const AE_STRANKA = 'akce.php';

/** Akce měsíce seřazené jako na webu (akce_rok), včetně skrytých. */
function ae_mesic(int $rok, int $mesic): array {
    $r = array_map('akce_doplnit', rows('SELECT * FROM cltk_akce WHERE rok = ? AND mesic = ?', [$rok, $mesic]));
    usort($r, fn($x, $y) => [$x['zacatek'], (int)$x['poradi'], (int)$x['id']] <=> [$y['zacatek'], (int)$y['poradi'], (int)$y['id']]);
    return $r;
}

/** Posune akci o místo mezi akcemi se stejným začátkem v měsíci a přečísluje pořadí měsíce. */
function ae_posun(array $a, int $smer): bool {
    $seznam = ae_mesic((int)$a['rok'], (int)$a['mesic']);
    $ids = array_map(fn($x) => (int)$x['id'], $seznam);
    $i = array_search((int)$a['id'], $ids, true);
    if ($i === false) return false;
    $j = $i + ($smer < 0 ? -1 : 1);
    if ($j < 0 || $j >= count($seznam) || $seznam[$i]['zacatek'] !== $seznam[$j]['zacatek']) return false;
    [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
    foreach ($ids as $pos => $id) q('UPDATE cltk_akce SET poradi = ? WHERE id = ?', [$pos, $id]);
    return true;
}

$dnes = dnes();
$rokDnes = (int)substr($dnes, 0, 4);
$rok = vstup_get('rok', (string)$rokDnes);
if ($rok !== 'vse' && !preg_match('/^\d{4}$/', $rok)) $rok = (string)$rokDnes;
$zpet = AE_STRANKA . '?rok=' . rawurlencode($rok);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek(AE_STRANKA);
    $akce = vstup('action', 20);
    $id   = (int)vstup_int('id');
    $rokZpet = vstup('rok', 4);
    if ($rokZpet !== '' && ($rokZpet === 'vse' || preg_match('/^\d{4}$/', $rokZpet))) $zpet = AE_STRANKA . '?rok=' . $rokZpet;
    $a = $id ? row('SELECT * FROM cltk_akce WHERE id = ?', [$id]) : null;
    if (!$a) redirect($zpet, 'Akce nebyla nalezena.', 'err');

    if ($akce === 'prepnout') {
        $novy = admin_prepni('cltk_akce', $id);
        redirect($zpet, $novy ? 'Akce „' . $a['nazev'] . '“ je v kalendáři.' : 'Akce „' . $a['nazev'] . '“ je skrytá, na webu se neukazuje.');
    }
    if ($akce === 'prihlaseni') {
        $novy = (int)$a['prihlaseni_povoleno'] === 1 ? 0 : 1;
        q('UPDATE cltk_akce SET prihlaseni_povoleno = ?, updated_at = ? WHERE id = ?', [$novy, ted(), $id]);
        redirect($zpet, $novy ? 'U akce „' . $a['nazev'] . '“ se ukáže tlačítko Přihlásit se.' : 'Přihlašování na akci „' . $a['nazev'] . '“ je vypnuté.');
    }
    if ($akce === 'nahoru' || $akce === 'dolu') {
        $ok = ae_posun($a, $akce === 'nahoru' ? -1 : 1);
        redirect($zpet, $ok ? 'Pořadí bylo změněno.' : 'Pořadí určuje termín – posunout jde jen akce se stejným termínem.', $ok ? 'ok' : 'warn');
    }
    if ($akce === 'kopirovat') {
        $kopie = $a;
        unset($kopie['id']);
        $kopie['nazev'] = mb_substr($a['nazev'] . ' (kopie)', 0, 200);
        $kopie['visible'] = 0;
        $kopie['created_at'] = $kopie['updated_at'] = ted();
        $kopie['poradi'] = (int)val('SELECT COALESCE(MAX(poradi), -1) + 1 FROM cltk_akce WHERE rok = ? AND mesic = ?', [(int)$a['rok'], (int)$a['mesic']]);
        $kopie['foto'] = '';
        if ((string)$a['foto'] !== '' && is_file(UPLOAD_DIR . '/' . $a['foto'])) {
            // vlastní soubor – kdyby obě akce sdílely jednu fotku, smazání jedné by ji vzalo i druhé
            $kopie['foto'] = obrazek_import(UPLOAD_DIR . '/' . $a['foto'], 'akce', obrazek_volne_jmeno('akce', $a['nazev'] . ' kopie'), 1600, 1600);
        }
        $noveId = db_insert('cltk_akce', $kopie);
        redirect('akce-edit.php?id=' . $noveId, 'Vznikla kopie akce – je zatím skrytá. Upravte termín, případně rok, a zapněte „Zobrazit v kalendáři“.', 'info');
    }
    if ($akce === 'smazat') {
        $pocet = (int)val('SELECT COUNT(*) FROM cltk_signups WHERE akce_id = ?', [$id]);
        q('DELETE FROM cltk_akce WHERE id = ?', [$id]);
        admin_smazat_soubory([(string)$a['foto']]);
        redirect($zpet, 'Akce „' . $a['nazev'] . '“ byla smazána.' . ($pocet > 0 ? ' Její přihlášky (' . $pocet . ') zůstaly v Přihláškách pod „smazané akce“ – můžete je stáhnout a smazat tam.' : ''));
    }
    redirect($zpet, 'Neznámý požadavek.', 'err');
}

/* --- data --- */
$roky = array_map(fn($r) => (int)$r['rok'], rows('SELECT DISTINCT rok FROM cltk_akce ORDER BY rok DESC'));
if (!in_array($rokDnes, $roky, true)) $roky[] = $rokDnes;
rsort($roky);

$radky = $rok === 'vse'
    ? rows('SELECT * FROM cltk_akce ORDER BY rok DESC, mesic, poradi, id')
    : rows('SELECT * FROM cltk_akce WHERE rok = ? ORDER BY mesic, poradi, id', [(int)$rok]);
$radky = array_map('akce_doplnit', $radky);
usort($radky, fn($x, $y) => [-(int)$x['rok'], (int)$x['mesic'], $x['zacatek'], (int)$x['poradi'], (int)$x['id']]
                        <=> [-(int)$y['rok'], (int)$y['mesic'], $y['zacatek'], (int)$y['poradi'], (int)$y['id']]);

$prihl = [];
foreach (rows('SELECT akce_id, COUNT(*) AS n, COALESCE(SUM(pocet), 0) AS osob, SUM(CASE WHEN vyrizeno = 0 THEN 1 ELSE 0 END) AS nove
                 FROM cltk_signups GROUP BY akce_id') as $p) {
    $prihl[(int)$p['akce_id']] = ['n' => (int)$p['n'], 'osob' => (int)$p['osob'], 'nove' => (int)$p['nove']];
}
$sirotci = (int)val('SELECT COUNT(*) FROM cltk_signups s LEFT JOIN cltk_akce a ON a.id = s.akce_id WHERE a.id IS NULL');
$nejblizsi = nejblizsi_akce();
$nejblizsiId = $nejblizsi ? (int)$nejblizsi['id'] : 0;

$nadchazejici = count(array_filter($radky, fn($a) => !$a['probehla'] && (int)$a['visible'] === 1));
$sPrihlasenim = count(array_filter($radky, fn($a) => (int)$a['prihlaseni_povoleno'] === 1));

$zalozky = [];
foreach ($roky as $r) $zalozky[AE_STRANKA . '?rok=' . $r] = (string)$r;
$zalozky[AE_STRANKA . '?rok=vse'] = 'Všechny roky';

admin_head('Kalendář akcí', $user, [
    'podnadpis' => 'Klubový kalendář na úvodu („Na Štvanici se potkáváme“) – výpis roku vlevo, vpravo okno s nejbližší akcí, spočítanou automaticky podle dnešního data. U akce lze zapnout tlačítko Přihlásit se s vlastním formulářem.',
    'akce'      => '<a class="btn btn-ghost" href="prihlasky.php?druh=akce">Přihlášky k akcím</a><a class="btn btn-primary" href="akce-edit.php' . ($rok !== 'vse' ? '?rok=' . (int)$rok : '') . '">Přidat akci</a>',
]);
echo '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';
?>

<div class="stats">
  <div class="stat"><b><?= cislo(count($radky)) ?></b><span>Akcí <?= $rok === 'vse' ? 'celkem' : 'v roce ' . e($rok) ?></span></div>
  <div class="stat"><b><?= cislo($nadchazejici) ?></b><span>Nadcházející</span><small>zobrazené, neproběhlé</small></div>
  <div class="stat"><b><?= cislo($sPrihlasenim) ?></b><span>S přihlášením</span><small>tlačítko Přihlásit se</small></div>
  <a class="stat" href="prihlasky.php?druh=akce"><b><?= cislo(array_sum(array_column($prihl, 'nove'))) ?></b><span>Nevyřízené přihlášky</span><small>ke všem akcím</small></a>
</div>

<div class="uv-info">
  <?php if ($nejblizsi): ?>
    Okno kalendáře na úvodu teď ukazuje <b><?= e($nejblizsi['nazev']) ?></b> (<?= e(akce_termin($nejblizsi, true)) ?>) – nejbližší akci od dneška <?= e(cz_date($dnes)) ?>. Mění se samo podle data.
  <?php else: ?>
    V kalendáři není žádná nadcházející zobrazená akce – okno na úvodu ukáže poslední akci roku.
  <?php endif; ?>
</div>

<?php if ($sirotci > 0): ?>
  <div class="uv-info uv-info--warn"><?= $sirotci ?> <?= sklonuj($sirotci, 'přihláška patří', 'přihlášky patří', 'přihlášek patří') ?> ke smazaným akcím. <a href="prihlasky.php?druh=akce&amp;akce=smazane">Zobrazit</a></div>
<?php endif; ?>

<?= admin_zalozky($zalozky, AE_STRANKA . '?rok=' . $rok) ?>

<section class="panel">
  <div class="panel-head">
    <h2><?= $rok === 'vse' ? 'Všechny akce' : 'Rok ' . e($rok) ?> <small><?= count($radky) ?></small></h2>
    <span class="hint">Pořadí jako na webu. Proběhlé akce jsou ztlumené (na webu taky).</span>
  </div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('V tomto roce zatím žádná akce', 'Přidejte první akci – stačí název a měsíc, termín můžete doplnit později.',
          '<a class="btn btn-primary" href="akce-edit.php' . ($rok !== 'vse' ? '?rok=' . (int)$rok : '') . '">Přidat akci</a>') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Měsíc</th><th>Akce</th><th>Termín</th><th>Přihlášky</th><th>Stav</th><th class="right">Úpravy</th></tr></thead>
        <tbody>
        <?php foreach ($radky as $i => $a):
            $id = (int)$a['id'];
            $p = $prihl[$id] ?? ['n' => 0, 'osob' => 0, 'nove' => 0];
            $predchozi = $radky[$i - 1] ?? null;
            $dalsi = $radky[$i + 1] ?? null;
            $stejny = fn($b) => $b && (int)$b['rok'] === (int)$a['rok'] && (int)$b['mesic'] === (int)$a['mesic'] && $b['zacatek'] === $a['zacatek'];
            $muzeNahoru = $stejny($predchozi);
            $muzeDolu = $stejny($dalsi);
            $tridy = trim(((int)$a['visible'] === 1 ? '' : 'je-skryte') . ($a['probehla'] ? ' uv-probehla' : '') . ($id === $nejblizsiId ? ' uv-na-uvodu' : '')); ?>
          <tr<?= $tridy !== '' ? ' class="' . $tridy . '"' : '' ?>>
            <td data-label="Měsíc" class="uv-nowrap"><span class="uv-mesic"><?= e(cz_mesic((int)$a['mesic'])) ?></span><?= $rok === 'vse' ? '<br><small class="uv-tlumene">' . (int)$a['rok'] . '</small>' : '' ?></td>
            <td data-label="Akce" class="td-nazev">
              <?php if ((string)$a['stitek'] !== ''): ?><span class="uv-stitek-zlaty"><?= e($a['stitek']) ?></span><?php endif; ?>
              <b><a href="akce-edit.php?id=<?= $id ?>" style="text-decoration:none"><?= e($a['nazev']) ?></a></b>
              <?php if ((string)$a['misto'] !== '' || (string)$a['cas'] !== ''): ?><small><?= e(trim($a['misto'] . ((string)$a['cas'] !== '' ? ' · ' . $a['cas'] : ''), ' ·')) ?></small><?php endif; ?>
              <?php if (trim((string)$a['poznamka_interni']) !== ''): ?><div class="uv-interni"><b>Interní poznámka</b><?= e($a['poznamka_interni']) ?></div><?php endif; ?>
            </td>
            <td data-label="Termín" class="uv-nowrap"><?= $a['bez_data'] && trim((string)$a['termin_text']) === '' ? '<span class="uv-tlumene">termín doplní klub</span>' : e($a['termin']) ?></td>
            <td data-label="Přihlášky">
              <?php if ((int)$a['prihlaseni_povoleno'] === 1): ?>
                <?= $a['prihlaseni'] ? badge('Otevřené', 'ok') : badge(akce_obsazeno($a) ? 'Obsazeno' : 'Uzavřené', 'warn') ?>
              <?php else: ?>
                <span class="uv-tlumene">bez přihlášení</span>
              <?php endif; ?>
              <?php if ($p['n'] > 0): ?>
                <br><a href="akce-edit.php?id=<?= $id ?>#prihlaseni"><?= $p['n'] ?> <?= sklonuj($p['n'], 'přihláška', 'přihlášky', 'přihlášek') ?> · <?= $p['osob'] ?> os.</a>
                <?= $p['nove'] > 0 ? badge($p['nove'] . ' nevyřízené', 'zlato') : '' ?>
              <?php endif; ?>
            </td>
            <td data-label="Stav">
              <?= stav_badge($a['visible']) ?>
              <?php if ($id === $nejblizsiId): ?> <?= badge('Nejbližší', 'navy') ?><?php elseif ($a['probehla']): ?> <?= badge('Proběhlo', 'off') ?><?php endif; ?>
            </td>
            <td data-label="Úpravy" class="right"><div class="akce-radku">
              <?php if ($muzeNahoru || $muzeDolu): ?><?= tlacitka_poradi($id, !$muzeNahoru, !$muzeDolu, ['rok' => $rok]) ?><?php endif; ?>
              <a class="btn btn-sm btn-ghost" href="akce-edit.php?id=<?= $id ?>">Upravit</a>
              <?= tlacitko_prepnout($id, $a['visible'], ['rok' => $rok]) ?>
              <?= tlacitko_akce('prihlaseni', $id, (int)$a['prihlaseni_povoleno'] === 1 ? 'Vypnout přihlášky' : 'Zapnout přihlášky', 'btn-ghost', ['rok' => $rok], '',
                    (int)$a['prihlaseni_povoleno'] === 1 ? 'Skrýt tlačítko Přihlásit se' : 'Zobrazit tlačítko Přihlásit se') ?>
              <?= tlacitko_akce('kopirovat', $id, 'Kopírovat', 'btn-ghost', ['rok' => $rok], '', 'Vytvořit kopii akce (např. na příští rok)') ?>
              <?= tlacitko_smazat($id, 'Smazat akci „' . $a['nazev'] . '“?' . ($p['n'] > 0 ? ' Její přihlášky (' . $p['n'] . ') zůstanou v Přihláškách.' : '') . ' Nejde to vrátit – když ji chcete jen schovat, použijte Skrýt.', ['rok' => $rok]) ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<p class="hint">Akce bez data mají v kalendáři jen měsíc a text „termín doplní klub“ (nebo vlastní text termínu). Řadí se na konec svého měsíce.</p>

<?php admin_foot(); ?>
