<?php
/* Úvodní galerie – úprava jednoho snímku (fotka, nebo navy deska s výsledkem).
   Seznam a pořadí: galerie.php. Popisek a titul desky jsou *inline*
   (smí <em>, vypisují se přes html_inline()). */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const GE_SEZNAM = 'galerie.php';
const GE_TYPY = ['foto' => 'Fotka', 'deska' => 'Navy deska s výsledkem'];

$id = (int)($_GET['id'] ?? 0);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') $id = (int)vstup_int('id');

$stary = $id ? row('SELECT * FROM cltk_uvodni_galerie WHERE id = ?', [$id]) : null;
if ($id && !$stary) redirect(GE_SEZNAM, 'Snímek nebyl nalezen – možná ho mezitím někdo smazal.', 'err');

$s = $stary ?? [
    'id' => 0, 'typ' => 'foto', 'rejstrik' => '', 'foto' => '', 'foto_w' => 0, 'foto_h' => 0, 'fokus' => '50% 50%',
    'alt' => '', 'popisek' => '', 'kredit' => '', 'deska_stitek' => '', 'deska_titul' => '', 'deska_tym_a' => '',
    'deska_skore' => '', 'deska_tym_b' => '', 'deska_hrac' => '', 'deska_souper' => '', 'deska_sety' => '',
    'deska_misto' => '', 'visible' => 1,
];
$chyby = [];

/* textová pole snímku: sloupec => max. délka (podle schema.sql) */
const GE_TEXTY = [
    'rejstrik' => 60, 'alt' => 255, 'popisek' => 255, 'kredit' => 160,
    'deska_stitek' => 120, 'deska_titul' => 160, 'deska_tym_a' => 60, 'deska_skore' => 20, 'deska_tym_b' => 60,
    'deska_hrac' => 120, 'deska_souper' => 120, 'deska_sety' => 60, 'deska_misto' => 160,
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek('galerie-edit.php' . ($id ? '?id=' . $id : ''));
    if (vstup('action', 20) !== 'ulozit') redirect(GE_SEZNAM, 'Neznámý požadavek.', 'err');

    $typ = vstup('typ', 10);
    if (!isset(GE_TYPY[$typ])) $typ = 'foto';
    $s['typ'] = $typ;
    foreach (GE_TEXTY as $sloupec => $max) $s[$sloupec] = vstup($sloupec, $max);
    $s['fokus'] = vstup('fokus', 20) ?: '50% 50%';
    $s['visible'] = vstup_bool('visible');

    if ($s['rejstrik'] === '') $chyby[] = 'Vyplňte krátký štítek do rejstříku pod galerií (např. „Vondroušová“).';
    if (!preg_match('/^\d{1,3}(\.\d+)?%\s+\d{1,3}(\.\d+)?%$/', $s['fokus'])) $chyby[] = 'Ohnisko fotky zapište jako dvě procenta, např. 50% 30%.';
    if ($s['alt'] === '') {
        $chyby[] = $typ === 'deska'
            ? 'Vyplňte popis desky pro čtečky obrazovky (celý výsledek jednou větou).'
            : 'Vyplňte popis fotky pro čtečky obrazovky (co je na fotce).';
    }
    if ($typ === 'deska' && $s['deska_titul'] === '' && $s['deska_tym_a'] === '') {
        $chyby[] = 'Deska potřebuje aspoň titul (např. „Česko <em>šampionem</em>“) nebo týmy a skóre.';
    }

    $foto = null;
    if (!$chyby) {
        $foto = admin_obrazek('foto', $stary['foto'] ?? '', 'galerie', 2400, 2400, $s['rejstrik']);
        if ($foto['chyba'] !== '') $chyby[] = $foto['chyba'] . ' Vyberte prosím fotku znovu.';
        elseif ($typ === 'foto' && $foto['soubor'] === '') $chyby[] = 'Snímek typu fotka potřebuje fotku. Nahrajte ji, nebo přepněte typ na desku.';
        if ($chyby && $foto['soubor'] !== (string)($stary['foto'] ?? '') && $foto['soubor'] !== '') {
            admin_smazat_soubory([$foto['soubor']]);              // nová fotka by zůstala viset
        }
    }

    if (!$chyby) {
        $data = [];
        foreach (array_keys(GE_TEXTY) as $sloupec) $data[$sloupec] = $s[$sloupec];
        $data += ['typ' => $typ, 'foto' => $foto['soubor'], 'foto_w' => (int)$foto['w'], 'foto_h' => (int)$foto['h'],
                  'fokus' => $s['fokus'], 'visible' => $s['visible'], 'updated_at' => ted()];
        try {
            if ($stary) {
                db_update('cltk_uvodni_galerie', $id, $data);
            } else {
                $data['poradi'] = admin_dalsi_poradi('cltk_uvodni_galerie');
                $data['created_at'] = ted();
                $id = db_insert('cltk_uvodni_galerie', $data);
            }
        } catch (Throwable $e) {
            if ($foto['soubor'] !== (string)($stary['foto'] ?? '')) admin_smazat_soubory([$foto['soubor']]);
            throw $e;
        }
        admin_smazat_soubory($foto['smazat']);                    // TEPRVE po zápisu do DB
        redirect(GE_SEZNAM, 'Snímek „' . $s['rejstrik'] . '“ je uložený.');
    }
}

$nova = (int)$s['id'] === 0;
admin_head($nova ? 'Nový snímek galerie' : 'Úprava snímku galerie', $user, [
    'zpet' => [GE_SEZNAM, 'Úvodní galerie'], 'sirka' => 'uzka',
    'podnadpis' => 'Snímek může být fotka, nebo navy deska s výsledkem – třeba dokud klub nemá fotku z finále. Fotka zůstane uložená, i když snímek přepnete na desku.',
]);
echo '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';
?>
<?php if ($chyby): ?>
  <ul class="uv-chyby" role="alert"><li><b>Snímek se nepodařilo uložit:</b></li><?php foreach ($chyby as $ch): ?><li><?= e($ch) ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="ulozit">
  <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">

  <section class="panel">
    <div class="panel-head"><h2>Snímek</h2></div>
    <div class="panel-body form">
      <?= pole_select('typ', 'Typ snímku', $s['typ'], GE_TYPY, ['attrs' => ['data-typ-prepinac' => 'galerie']]) ?>
      <?= pole_text('rejstrik', 'Štítek v rejstříku', $s['rejstrik'], ['required' => true, 'maxlength' => 60, 'placeholder' => 'Vondroušová',
            'hint' => 'Jedno dvě slova pod galerií vedle římské číslice (I Vondroušová · II Muchová …).']) ?>
      <?= pole_text('popisek', 'Popisek pod galerií', $s['popisek'], ['maxlength' => 255,
            'placeholder' => '<em>Markéta Vondroušová</em> s mísou Venus Rosewater Dish · Wimbledon 2023',
            'hint' => 'Jméno zlatou kurzívou zapíšete jako <code>&lt;em&gt;Jméno&lt;/em&gt;</code>. Jiné značky web nezobrazí.']) ?>
      <?= pole_text('kredit', 'Kredit', $s['kredit'], ['maxlength' => 160, 'placeholder' => 'foto Martin Sidorják',
            'hint' => 'Autor fotky drobným písmem za popiskem.']) ?>
      <?= pole_textarea('alt', 'Popis pro čtečky obrazovky', $s['alt'], ['required' => true, 'rows' => 2, 'maxlength' => 255,
            'hint' => 'U fotky co je na ní („Markéta Vondroušová líbá mísu…“), u desky celý výsledek jednou větou. Nevidomí návštěvníci uslyší tenhle text.']) ?>
      <?= pole_check('visible', 'Zobrazit v galerii', (int)$s['visible'] === 1) ?>
    </div>
  </section>

  <section class="panel uv-typ-cast" data-pro-typ="foto">
    <div class="panel-head"><h2>Fotka</h2></div>
    <div class="panel-body form">
      <?= pole_obrazek('foto', 'Fotka', $s['foto'], ['hint' => 'Na šířku, ideálně 2400 px (3 : 2). JPG, PNG nebo WEBP – zmenší a otočí se samo, uloží se i úspornější WebP.']) ?>
      <?= pole_fokus('fokus', 'Ohnisko fotky', $s['fokus'], $s['foto'], ['hint' => 'Klepněte do fotky na obličej nebo trofej – to zůstane vidět i v úzkém výřezu na telefonu.']) ?>
      <?php if ((int)$s['foto_w'] > 0): ?><p class="hint">Uložená fotka má <?= (int)$s['foto_w'] ?> × <?= (int)$s['foto_h'] ?> px.</p><?php endif; ?>
    </div>
  </section>

  <section class="panel uv-typ-cast" data-pro-typ="deska">
    <div class="panel-head"><h2>Navy deska s výsledkem</h2><span class="hint">Náhled se mění při psaní.</span></div>
    <div class="panel-body form">
      <div class="uv-deska" aria-hidden="true">
        <p class="uv-deska__stitek" data-zrcadlo="f-deska_stitek" data-zrcadlo-prazdne="Štítek"><?= e($s['deska_stitek'] ?: 'Štítek') ?></p>
        <p class="uv-deska__titul" data-zrcadlo="f-deska_titul" data-zrcadlo-inline data-zrcadlo-prazdne="Titul"><?= $s['deska_titul'] !== '' ? html_inline($s['deska_titul']) : 'Titul' ?></p>
        <p class="uv-deska__zapas"><span data-zrcadlo="f-deska_tym_a"><?= e($s['deska_tym_a']) ?></span><span class="uv-deska__skore" data-zrcadlo="f-deska_skore"><?= e($s['deska_skore']) ?></span><span data-zrcadlo="f-deska_tym_b"><?= e($s['deska_tym_b']) ?></span></p>
        <p class="uv-deska__detail"><span data-zrcadlo="f-deska_hrac"><?= e($s['deska_hrac']) ?></span> – <span data-zrcadlo="f-deska_souper"><?= e($s['deska_souper']) ?></span><span class="uv-deska__sety" data-zrcadlo="f-deska_sety"><?= e($s['deska_sety']) ?></span></p>
        <p class="uv-deska__misto" data-zrcadlo="f-deska_misto"><?= e($s['deska_misto']) ?></p>
      </div>
      <?= pole_text('deska_stitek', 'Štítek nahoře', $s['deska_stitek'], ['maxlength' => 120, 'placeholder' => 'Billie Jean King Cup · 2026']) ?>
      <?= pole_text('deska_titul', 'Titul', $s['deska_titul'], ['maxlength' => 160, 'placeholder' => 'Česko<br><em>šampionem</em>',
            'hint' => 'Zlatá kurzíva <code>&lt;em&gt;…&lt;/em&gt;</code>, nový řádek <code>&lt;br&gt;</code>.']) ?>
      <?= pole_radek([
            pole_text('deska_tym_a', 'Tým / hráč vlevo', $s['deska_tym_a'], ['maxlength' => 60, 'placeholder' => 'Česko']),
            pole_text('deska_skore', 'Skóre', $s['deska_skore'], ['maxlength' => 20, 'placeholder' => '2 : 0']),
            pole_text('deska_tym_b', 'Tým / hráč vpravo', $s['deska_tym_b'], ['maxlength' => 60, 'placeholder' => 'Ukrajina']),
          ], 3) ?>
      <?= pole_radek([
            pole_text('deska_hrac', 'Naše hráčka / hráč', $s['deska_hrac'], ['maxlength' => 120, 'placeholder' => 'Karolína Muchová']),
            pole_text('deska_souper', 'Soupeř', $s['deska_souper'], ['maxlength' => 120, 'placeholder' => 'Anhelina Kalininová']),
            pole_text('deska_sety', 'Sety', $s['deska_sety'], ['maxlength' => 60, 'placeholder' => '6:2 · 6:3']),
          ], 3) ?>
      <?= pole_text('deska_misto', 'Místo a datum', $s['deska_misto'], ['maxlength' => 160, 'placeholder' => 'Šen-čen · 27. září 2026 · dvanáctý titul Česka']) ?>
    </div>
  </section>

  <?= tlacitka_formulare($nova ? 'Přidat snímek' : 'Uložit změny', GE_SEZNAM, 'Zpět na seznam') ?>
</form>

<?php
echo '<script src="' . e(BASE_PATH . verze('admin/assets/admin-uvod.js')) . '" defer></script>';
admin_foot();
