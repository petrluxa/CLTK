<?php
/* Dokumenty ke stažení – Stanovy, Pravidla hraní, Osobní údaje členů,
   ceníky, provozní řády… (ZADANI §6.18). Dokument je buď nahrané PDF
   (uploads/dokumenty/, má přednost), nebo odkaz ven. Zaškrtnuté
   „v patičce“ se ukážou ve sloupci „Dokumenty a sítě“ v patičce webu. */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const DO_STRANKA = 'dokumenty.php';
const DO_KATEGORIE = ['klub' => 'Klub', 'cenik' => 'Ceníky', 'provoz' => 'Provoz areálu', 'clenstvi' => 'Členství', 'skola' => 'Tenisová škola'];

/** Odkaz na dokument: [hodnota pro DB, chyba]. */
function do_odkaz(string $vstup): array {
    $u = trim($vstup);
    if ($u === '') return ['', ''];
    $holy = (string)preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($u, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $holy) && !preg_match('~^https?:~i', $holy)) {
        return [$u, 'Odkaz musí začínat https:// (nebo vést na stránku tohoto webu).'];
    }
    $n = normalizuj_url($u);
    if (preg_match('~^https?://~i', $n)) {
        $host = (string)parse_url($n, PHP_URL_HOST);
        if (preg_match('~\s~u', $n) || !preg_match('/^[\p{L}\p{N}.\-]+\.\p{L}{2,}$/u', $host)) {
            return [$u, 'Odkaz nevypadá jako adresa webu. Zkopírujte ho celý z prohlížeče (https://…).'];
        }
    }
    if (bezpecny_odkaz($n) === '') return [$u, 'Tento odkaz nejde použít.'];
    return [$n, ''];
}

$chyby = [];
$form = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek(DO_STRANKA);
    $akce = vstup('action', 20);
    $id   = (int)vstup_int('id');

    if (in_array($akce, ['prepnout', 'nahoru', 'dolu', 'smazat'], true)) {
        $d = row('SELECT * FROM cltk_dokumenty WHERE id = ?', [$id]);
        if (!$d) redirect(DO_STRANKA, 'Dokument nebyl nalezen.', 'err');
        if ($akce === 'prepnout') {
            $novy = admin_prepni('cltk_dokumenty', $id);
            redirect(DO_STRANKA, $novy ? 'Dokument „' . $d['nazev'] . '“ je na webu.' : 'Dokument „' . $d['nazev'] . '“ je skrytý.');
        }
        if ($akce === 'nahoru' || $akce === 'dolu') {
            admin_posun('cltk_dokumenty', $id, $akce === 'nahoru' ? -1 : 1);
            redirect(DO_STRANKA, 'Pořadí bylo změněno.');
        }
        q('DELETE FROM cltk_dokumenty WHERE id = ?', [$id]);
        admin_smazat_soubory([(string)$d['soubor']]);
        redirect(DO_STRANKA, 'Dokument „' . $d['nazev'] . '“ byl smazán.');
    }

    if ($akce === 'ulozit') {
        $stary = $id ? row('SELECT * FROM cltk_dokumenty WHERE id = ?', [$id]) : null;
        if ($id && !$stary) redirect(DO_STRANKA, 'Dokument mezitím zmizel – možná ho někdo smazal.', 'err');

        $nazev = vstup('nazev', 200);
        $kategorie = vstup('kategorie', 40);
        $popis = vstup('popis', 255);
        [$url, $chUrl] = do_odkaz(vstup('url', 255));
        $vPaticce = vstup_bool('v_paticce');
        $patickaText = vstup('paticka_text', 120);
        $visible = vstup_bool('visible');

        if ($nazev === '') $chyby[] = 'Vyplňte název dokumentu.';
        if (!isset(DO_KATEGORIE[$kategorie])) $chyby[] = 'Vyberte kategorii.';
        if ($chUrl !== '') $chyby[] = $chUrl;
        $form = ['id' => $id, 'nazev' => $nazev, 'kategorie' => $kategorie, 'popis' => $popis, 'url' => $url,
                 'v_paticce' => $vPaticce, 'paticka_text' => $patickaText, 'visible' => $visible,
                 'soubor' => (string)($stary['soubor'] ?? ''), 'soubor_nazev' => (string)($stary['soubor_nazev'] ?? '')];

        $pdf = null;
        if (!$chyby) {
            $pdf = admin_pdf('soubor', $stary['soubor'] ?? '', $stary['soubor_nazev'] ?? '', 'dokumenty');
            if ($pdf['chyba'] !== '') $chyby[] = $pdf['chyba'];
            elseif ($pdf['soubor'] === '' && $url === '') $chyby[] = 'Nahrajte PDF, nebo vyplňte odkaz, kde dokument leží – jinak by na webu nevedl nikam.';
            if ($chyby && $pdf['chyba'] === '' && $pdf['soubor'] !== (string)($stary['soubor'] ?? '') && $pdf['soubor'] !== '') {
                admin_smazat_soubory([$pdf['soubor']]);
            }
        }
        if (!$chyby) {
            $data = ['nazev' => $nazev, 'kategorie' => $kategorie, 'popis' => $popis, 'soubor' => $pdf['soubor'],
                     'soubor_nazev' => $pdf['nazev'], 'url' => $url, 'v_paticce' => $vPaticce,
                     'paticka_text' => $patickaText, 'visible' => $visible, 'updated_at' => ted()];
            try {
                if ($stary) {
                    db_update('cltk_dokumenty', $id, $data);
                } else {
                    $data['poradi'] = admin_dalsi_poradi('cltk_dokumenty');
                    $data['created_at'] = ted();
                    $id = db_insert('cltk_dokumenty', $data);
                }
            } catch (Throwable $e) {
                if ($pdf['soubor'] !== (string)($stary['soubor'] ?? '')) admin_smazat_soubory([$pdf['soubor']]);
                throw $e;
            }
            admin_smazat_soubory($pdf['smazat']);                   // TEPRVE po zápisu do DB
            redirect(DO_STRANKA, 'Dokument „' . $nazev . '“ je uložený.' . ($pdf['soubor'] !== '' && $url !== '' ? ' Na webu vede na nahrané PDF (má přednost před odkazem).' : ''));
        }
    }
    if (!$chyby) redirect(DO_STRANKA, 'Neznámý požadavek.', 'err');
}

$upravit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($form === null && $upravit > 0) {
    $form = row('SELECT * FROM cltk_dokumenty WHERE id = ?', [$upravit]);
    if (!$form) redirect(DO_STRANKA, 'Dokument nebyl nalezen.', 'err');
}
if ($form === null && isset($_GET['nova'])) {
    $form = ['id' => 0, 'nazev' => '', 'kategorie' => 'klub', 'popis' => '', 'url' => '', 'v_paticce' => 0,
             'paticka_text' => '', 'visible' => 1, 'soubor' => '', 'soubor_nazev' => ''];
}

$css = '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';
$js  = '<script src="' . e(BASE_PATH . verze('admin/assets/admin-uvod.js')) . '" defer></script>';

if ($form !== null):
    $nova = (int)$form['id'] === 0;
    admin_head($nova ? 'Nový dokument' : 'Úprava dokumentu', $user, [
        'zpet' => [DO_STRANKA, 'Dokumenty'], 'sirka' => 'uzka',
        'podnadpis' => 'PDF ke stažení, nebo odkaz na dokument jinde. Nahrané PDF má přednost před odkazem.',
    ]);
    echo $css;
?>
<?php if ($chyby): ?>
  <ul class="uv-chyby" role="alert"><li><b>Dokument se nepodařilo uložit:</b></li><?php foreach ($chyby as $ch): ?><li><?= e($ch) ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
      <?= pole_text('nazev', 'Název dokumentu', $form['nazev'], ['required' => true, 'maxlength' => 200, 'placeholder' => 'Stanovy I. ČLTK Praha']) ?>
      <?= pole_radek([
            pole_select('kategorie', 'Kategorie', $form['kategorie'], DO_KATEGORIE, ['hint' => 'Podle kategorie se dokument ukáže na příslušné stránce (Klub, ceníky…).']),
            pole_text('popis', 'Krátký popis (nepovinné)', $form['popis'], ['maxlength' => 255, 'placeholder' => 'Ve znění z 6. 7. 2025']),
          ]) ?>
      <?= pole_pdf('soubor', 'Soubor PDF', $form['soubor'], $form['soubor_nazev'],
            ['hint' => 'Jen PDF, nejvýš ' . e(ini_get('upload_max_filesize')) . 'B. Nové PDF nahradí to současné.']) ?>
      <?= pole_text('url', 'Nebo odkaz na dokument', $form['url'], ['maxlength' => 255, 'attrs' => ['inputmode' => 'url'], 'placeholder' => 'https://files.cltk.cz/…',
            'hint' => 'Když dokument leží jinde (např. na úložišti klubu). Použije se, jen když není nahrané PDF.']) ?>
      <fieldset>
        <legend>Patička webu</legend>
        <?= pole_check('v_paticce', 'Ukázat v patičce ve sloupci „Dokumenty a sítě“', (int)$form['v_paticce'] === 1,
              ['attrs' => ['data-odkryva' => 'pole-paticka']]) ?>
        <div id="pole-paticka">
          <?= pole_text('paticka_text', 'Text odkazu v patičce', $form['paticka_text'], ['maxlength' => 120, 'placeholder' => 'Stanovy klubu (PDF)',
                'hint' => 'Prázdné = název dokumentu.']) ?>
        </div>
      </fieldset>
      <?= pole_check('visible', 'Zobrazit na webu', (int)$form['visible'] === 1) ?>
      <?= tlacitka_formulare($nova ? 'Přidat dokument' : 'Uložit změny', DO_STRANKA, 'Zpět na seznam') ?>
    </form>
  </div>
</section>
<?php
    echo $js;
    admin_foot();
    exit;
endif;

/* --- seznam --- */
$radky = rows('SELECT * FROM cltk_dokumenty ORDER BY poradi, id');
$vPaticce = array_values(array_filter($radky, fn($d) => (int)$d['visible'] === 1 && (int)$d['v_paticce'] === 1));

admin_head('Dokumenty', $user, [
    'podnadpis' => 'PDF ke stažení na webu – stanovy, pravidla, ceníky, provozní řády. Pořadí šipkami platí na stránkách i v patičce.',
    'akce'      => '<a class="btn btn-primary" href="' . DO_STRANKA . '?nova=1">Přidat dokument</a>',
]);
echo $css;
?>

<section class="panel">
  <div class="panel-head"><h2>V patičce webu <small><?= count($vPaticce) ?></small></h2><span class="hint">Sloupec „Dokumenty a sítě“.</span></div>
  <?php if ($vPaticce): ?>
      <ul class="seznam-radku">
        <?php foreach ($vPaticce as $d): $u = dokument_url($d); ?>
          <li><span><b><?= e($d['paticka_text'] !== '' ? $d['paticka_text'] : $d['nazev']) ?></b><br>
            <small><?= (string)$d['soubor'] !== '' ? 'nahrané PDF' : 'odkaz' ?> · <?php if ($u !== ''): ?><a href="<?= e($u) ?>" target="_blank" rel="noopener">otevřít</a><?php else: ?>bez adresy<?php endif; ?></small></span></li>
        <?php endforeach; ?>
      </ul>
  <?php else: ?>
    <div class="panel-body"><p class="hint">V patičce teď není žádný dokument. Zaškrtněte u dokumentu „Ukázat v patičce“.</p></div>
  <?php endif; ?>
</section>

<section class="panel">
  <div class="panel-head"><h2>Všechny dokumenty <small><?= count($radky) ?></small></h2></div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím žádný dokument', 'Nahrajte první PDF – třeba stanovy nebo pravidla hraní.',
          '<a class="btn btn-primary" href="' . DO_STRANKA . '?nova=1">Přidat dokument</a>') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Název</th><th>Kategorie</th><th>Soubor</th><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($radky as $i => $d):
            $id = (int)$d['id']; $u = dokument_url($d);
            $chybi = (string)$d['soubor'] !== '' && !is_file(UPLOAD_DIR . '/' . $d['soubor']); ?>
          <tr<?= (int)$d['visible'] === 1 ? '' : ' class="je-skryte"' ?>>
            <td data-label="Název" class="td-nazev"><b><?= e($d['nazev']) ?></b><?php if ((string)$d['popis'] !== ''): ?><small><?= e($d['popis']) ?></small><?php endif; ?></td>
            <td data-label="Kategorie" class="tlumene"><?= e(DO_KATEGORIE[$d['kategorie']] ?? $d['kategorie']) ?></td>
            <td data-label="Soubor" class="tlumene uv-cesta">
              <?php if ((string)$d['soubor'] !== ''): ?>
                <?= badge('PDF', 'navy') ?> <?php if ($u !== '' && !$chybi): ?><a href="<?= e($u) ?>" target="_blank" rel="noopener"><?= e($d['soubor_nazev'] !== '' ? $d['soubor_nazev'] : basename($d['soubor'])) ?></a><?php else: ?><?= badge('soubor chybí', 'err') ?><?php endif; ?>
              <?php elseif ($u !== ''): ?>
                <?= badge('Odkaz', 'info') ?> <a href="<?= e($u) ?>" target="_blank" rel="noopener"><?= e(uryvek(preg_replace('~^https?://~i', '', (string)$d['url']), 40)) ?></a>
              <?php else: ?>
                <?= badge('nikam nevede', 'err') ?>
              <?php endif; ?>
            </td>
            <td data-label="Stav"><?= stav_badge($d['visible']) ?><?= (int)$d['v_paticce'] === 1 ? ' ' . badge('V patičce', 'zlato') : '' ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitka_poradi($id, $i === 0, $i === count($radky) - 1) ?>
              <a class="btn btn-sm btn-ghost" href="<?= DO_STRANKA ?>?id=<?= $id ?>">Upravit</a>
              <?= tlacitko_prepnout($id, $d['visible']) ?>
              <?= tlacitko_smazat($id, 'Smazat dokument „' . $d['nazev'] . '“' . ((string)$d['soubor'] !== '' ? ' i s nahraným PDF' : '') . '? Nejde to vrátit – když ho chcete jen schovat, použijte Skrýt.') ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php admin_foot(); ?>
