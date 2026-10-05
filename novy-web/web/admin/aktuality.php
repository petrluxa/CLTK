<?php
/* Aktuality z klubu – tři karty na úvodní stránce (ZADANI §4.4).
   NEJSOU to články: jen fotka, datum, nadpis, krátký popis a volitelně odkaz,
   bez stránky s detailem. Datum je nepovinné (nová aktualita má předvyplněný dnešek). Na úvodu se ukážou první tři zobrazené
   podle pořadí (aktuality(3) v inc/data.php). */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const AK_STRANKA = 'aktuality.php';
const AK_NA_UVODU = 3;

/** Odkaz z formuláře: [hodnota pro DB, chyba]. Povolí https://…, mailto:, tel:, #kotvu a stránku webu. */
function ak_odkaz(string $vstup): array {
    $u = trim($vstup);
    if ($u === '') return ['', ''];
    $holy = (string)preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($u, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $holy) && !preg_match('~^(https?:|mailto:|tel:)~i', $holy)) {
        return [$u, 'Odkaz musí vést na web (https://…), e-mail (mailto:…), telefon (tel:…) nebo na stránku tohoto webu (např. areal.php).'];
    }
    $n = normalizuj_url($u);
    if (preg_match('~^https?://~i', $n)) {
        $host = (string)parse_url($n, PHP_URL_HOST);
        if (preg_match('~\s~u', $n) || !preg_match('/^[\p{L}\p{N}.\-]+\.\p{L}{2,}$/u', $host)) {
            return [$u, 'Odkaz nevypadá jako adresa webu. Zapište ho celý (https://…) nebo jako stránku webu (areal.php).'];
        }
    }
    if (bezpecny_odkaz($n) === '') return [$u, 'Tento odkaz nejde použít.'];
    return [$n, ''];
}

/** Pořadí aktualit, které jsou právě na úvodu (první tři zobrazené). */
function ak_na_uvodu(array $radky): array {
    $ids = [];
    foreach ($radky as $r) {
        if ((int)$r['visible'] === 1 && count($ids) < AK_NA_UVODU) $ids[] = (int)$r['id'];
    }
    return $ids;
}

$chyby = [];
$form = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek(AK_STRANKA);
    $akce = vstup('action', 20);
    $id   = (int)vstup_int('id');

    if ($akce === 'prepnout') {
        if (!row('SELECT id FROM cltk_aktuality WHERE id = ?', [$id])) redirect(AK_STRANKA, 'Aktualita nebyla nalezena.', 'err');
        $novy = admin_prepni('cltk_aktuality', $id);
        redirect(AK_STRANKA, $novy ? 'Aktualita je zobrazená.' : 'Aktualita je skrytá, na webu se neukazuje.');
    }
    if ($akce === 'nahoru' || $akce === 'dolu') {
        admin_posun('cltk_aktuality', $id, $akce === 'nahoru' ? -1 : 1);
        redirect(AK_STRANKA, 'Pořadí bylo změněno.');
    }
    if ($akce === 'smazat') {
        $r = row('SELECT * FROM cltk_aktuality WHERE id = ?', [$id]);
        if (!$r) redirect(AK_STRANKA, 'Aktualita už neexistuje.', 'err');
        q('DELETE FROM cltk_aktuality WHERE id = ?', [$id]);
        admin_smazat_soubory([(string)$r['foto']]);             // až po smazání řádku
        redirect(AK_STRANKA, 'Aktualita „' . $r['nadpis'] . '“ byla smazána.');
    }
    if ($akce === 'ulozit') {
        $stary = $id ? row('SELECT * FROM cltk_aktuality WHERE id = ?', [$id]) : null;
        if ($id && !$stary) redirect(AK_STRANKA, 'Aktualita mezitím zmizela – možná ji někdo smazal.', 'err');

        $nadpis = vstup('nadpis', 160);
        $popis  = vstup('popis', 600);
        [$odkaz, $chOdkaz] = ak_odkaz(vstup('odkaz', 255));
        $odkazText = vstup('odkaz_text', 80);
        $fokus  = vstup('fokus', 20);
        $datum  = normalizuj_datum(vstup('datum', 20));
        $visible = vstup_bool('visible');

        if ($nadpis === '') $chyby[] = 'Vyplňte nadpis.';
        if ($popis === '') $chyby[] = 'Vyplňte krátký popis – jedna nebo dvě věty.';
        if ($chOdkaz !== '') $chyby[] = $chOdkaz;
        if ($datum === false) { $chyby[] = 'Datum nedává smysl – vyberte ho v kalendáři nebo napište např. 5. 10. 2026.'; $datum = null; }
        if ($fokus === '') $fokus = '50% 50%';
        if (!preg_match('/^\d{1,3}(\.\d+)?%\s+\d{1,3}(\.\d+)?%$/', $fokus)) $chyby[] = 'Ohnisko fotky zapište jako dvě procenta, např. 50% 40%.';

        $form = ['id' => $id, 'nadpis' => $nadpis, 'popis' => $popis, 'odkaz' => $odkaz, 'odkaz_text' => $odkazText,
                 'fokus' => $fokus, 'datum' => $datum ?? vstup('datum', 20), 'visible' => $visible, 'foto' => (string)($stary['foto'] ?? '')];

        $foto = null;
        if (!$chyby) {
            $foto = admin_obrazek('foto', $stary['foto'] ?? '', 'aktuality', 2000, 2000, $nadpis);
            if ($foto['chyba'] !== '') $chyby[] = $foto['chyba'] . ' Vyberte prosím fotku znovu.';
        }
        if (!$chyby) {
            $data = ['nadpis' => $nadpis, 'popis' => $popis, 'foto' => $foto['soubor'], 'fokus' => $fokus, 'datum' => $datum,
                     'odkaz' => $odkaz, 'odkaz_text' => $odkaz !== '' ? $odkazText : '', 'visible' => $visible, 'updated_at' => ted()];
            try {
                if ($stary) {
                    db_update('cltk_aktuality', $id, $data);
                } else {
                    $data['poradi'] = admin_dalsi_poradi('cltk_aktuality');
                    $data['created_at'] = ted();
                    $id = db_insert('cltk_aktuality', $data);
                }
            } catch (Throwable $e) {
                // zápis neprošel → nově nahraná fotka by zůstala viset; stará zůstává
                if ($foto['soubor'] !== (string)($stary['foto'] ?? '')) admin_smazat_soubory([$foto['soubor']]);
                throw $e;
            }
            admin_smazat_soubory($foto['smazat']);                  // TEPRVE po zápisu do DB
            $hlaska = 'Aktualita „' . $nadpis . '“ je uložená.';
            if ($foto['soubor'] === '') $hlaska .= ' Nemá fotku – karta na webu bude bez obrázku.';
            redirect(AK_STRANKA, $hlaska, $foto['soubor'] === '' ? 'warn' : 'ok');
        }
    }
    if (!$chyby) redirect(AK_STRANKA, 'Neznámý požadavek.', 'err');
}

$upravit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($form === null && $upravit > 0) {
    $form = row('SELECT * FROM cltk_aktuality WHERE id = ?', [$upravit]);
    if (!$form) redirect(AK_STRANKA, 'Aktualita nebyla nalezena.', 'err');
}
if ($form === null && isset($_GET['nova'])) {
    $form = ['id' => 0, 'nadpis' => '', 'popis' => '', 'odkaz' => '', 'odkaz_text' => '', 'fokus' => '50% 50%', 'datum' => dnes(), 'visible' => 1, 'foto' => ''];
}

$css = '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';
$js  = '<script src="' . e(BASE_PATH . verze('admin/assets/admin-uvod.js')) . '" defer></script>';

if ($form !== null):
    $nova = (int)$form['id'] === 0;
    admin_head($nova ? 'Nová aktualita' : 'Úprava aktuality', $user, [
        'zpet' => [AK_STRANKA, 'Aktuality z klubu'], 'sirka' => 'uzka',
        'podnadpis' => 'Fotka, datum, nadpis a krátký popis. Na webu nemá aktualita vlastní stránku – kdo chce víc, klikne na odkaz.',
    ]);
    echo $css;
?>
<?php if ($chyby): ?>
  <ul class="uv-chyby" role="alert"><li><b>Aktualitu se nepodařilo uložit:</b></li><?php foreach ($chyby as $ch): ?><li><?= e($ch) ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
      <?= pole_datum('datum', 'Datum', (string)($form['datum'] ?? ''), ['hint' => 'Ukáže se na kartě nad nadpisem, např. „5. října 2026“. Prázdné = bez data.']) ?>
      <?= pole_text('nadpis', 'Nadpis', $form['nadpis'], ['required' => true, 'maxlength' => 160, 'placeholder' => 'Hrajeme v hale',
            'attrs' => ['data-pocitadlo' => '40'], 'hint' => 'Dvě až čtyři slova – na webu je nadpis velkým písmem.']) ?>
      <?= pole_textarea('popis', 'Krátký popis', $form['popis'], ['required' => true, 'rows' => 3, 'maxlength' => 600,
            'placeholder' => 'Halová sezóna začíná od 5. 10.',
            'attrs' => ['data-pocitadlo' => '140'], 'hint' => 'Jedna, nejvýš dvě věty.']) ?>
      <?= pole_obrazek('foto', 'Fotka', $form['foto'], ['hint' => 'Na šířku, aspoň 1200 px. JPG, PNG nebo WEBP – velikost a natočení se upraví samy. Na webu je fotka ve zlatém rámečku.']) ?>
      <?= pole_fokus('fokus', 'Ohnisko fotky', $form['fokus'], $form['foto']) ?>
      <?= pole_radek([
            pole_text('odkaz', 'Odkaz (nepovinné)', $form['odkaz'], ['maxlength' => 255, 'attrs' => ['inputmode' => 'url'],
                'placeholder' => 'areal.php nebo https://…', 'hint' => 'Stránka tohoto webu (např. <b>areal.php</b>) nebo adresa jinam.']),
            pole_text('odkaz_text', 'Text odkazu', $form['odkaz_text'], ['maxlength' => 80, 'placeholder' => 'Více o areálu',
                'hint' => 'Prázdné = „Více“.']),
          ]) ?>
      <?= pole_check('visible', 'Zobrazit na webu', (int)$form['visible'] === 1,
            ['hint' => 'Na úvodu se ukazují první tři zobrazené aktuality podle pořadí v seznamu.']) ?>
      <?= tlacitka_formulare($nova ? 'Přidat aktualitu' : 'Uložit změny', AK_STRANKA, 'Zpět na seznam') ?>
    </form>
  </div>
</section>
<?php
    echo $js;
    admin_foot();
    exit;
endif;

/* --- seznam --- */
$radky = rows('SELECT * FROM cltk_aktuality ORDER BY poradi, id');
$naUvodu = ak_na_uvodu($radky);

admin_head('Aktuality z klubu', $user, [
    'podnadpis' => 'Tři karty na úvodní stránce – fotka, datum, nadpis a krátký popis. Nejsou to články.',
    'akce'      => '<a class="btn btn-primary" href="' . AK_STRANKA . '?nova=1">Přidat aktualitu</a>',
]);
echo $css;
?>

<?php if (count($naUvodu) < AK_NA_UVODU && $radky): ?>
  <div class="uv-info uv-info--warn">Na úvodu jsou místa pro tři aktuality, zobrazené jsou teď <?= count($naUvodu) ?>. Zobrazte další nebo přidejte novou.</div>
<?php endif; ?>

<section class="panel">
  <div class="panel-head">
    <h2>Aktuality <small><?= count($naUvodu) ?> na úvodu · <?= count($radky) ?> celkem</small></h2>
    <span class="hint">Pořadí šipkami = pořadí na webu zleva doprava.</span>
  </div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím žádná aktualita', 'Přidejte první – stačí fotka, nadpis a jedna věta.',
          '<a class="btn btn-primary" href="' . AK_STRANKA . '?nova=1">Přidat aktualitu</a>') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Foto</th><th>Nadpis a popis</th><th>Odkaz</th><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($radky as $i => $r):
            $id = (int)$r['id'];
            $uvod = in_array($id, $naUvodu, true);
            $tridy = trim(((int)$r['visible'] === 1 ? '' : 'je-skryte') . ($uvod ? ' uv-na-uvodu' : '')); ?>
          <tr<?= $tridy !== '' ? ' class="' . $tridy . '"' : '' ?>>
            <td data-label="Foto"><?= (string)$r['foto'] !== ''
                ? '<img class="thumb-sm" src="' . e(upload_url($r['foto'])) . '" alt="" style="object-position:' . e($r['fokus']) . '">'
                : '<span class="thumb-ph">bez fotky</span>' ?></td>
            <td data-label="Nadpis a popis" class="td-nazev"><b><?= e($r['nadpis']) ?></b><small><?= !empty($r['datum']) ? e(cz_date_dlouze((string)$r['datum'])) . ' · ' : '' ?><?= e(uryvek($r['popis'], 110)) ?></small></td>
            <td data-label="Odkaz" class="tlumene"><?= (string)$r['odkaz'] !== '' ? e(($r['odkaz_text'] !== '' ? $r['odkaz_text'] . ' · ' : '') . $r['odkaz']) : '–' ?></td>
            <td data-label="Stav"><?= stav_badge($r['visible']) ?><?= $uvod ? ' ' . badge('Na úvodu', 'zlato') : ((int)$r['visible'] === 1 ? ' ' . badge('Mimo úvod', 'info') : '') ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitka_poradi($id, $i === 0, $i === count($radky) - 1) ?>
              <a class="btn btn-sm btn-ghost" href="<?= AK_STRANKA ?>?id=<?= $id ?>">Upravit</a>
              <?= tlacitko_prepnout($id, $r['visible']) ?>
              <?= tlacitko_smazat($id, 'Smazat aktualitu „' . $r['nadpis'] . '“ i s fotkou? Nejde to vrátit – když ji chcete jen schovat, použijte Skrýt.') ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php admin_foot(); ?>
