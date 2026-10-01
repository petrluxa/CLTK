<?php
/* Výsledky hráčů – úprava jednoho výsledku. Seznam: vysledky.php.
   Sety se zapisují jedním polem („6:3 1:6 10:6“, „9:8 (7:5)“, „6:1 1:0 skr.“)
   a ukládají jako JSON pole přes sety_z_textu(). */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const VE_SEZNAM = 'vysledky.php';
const VE_VERDIKTY = ['Titul', 'Finále', 'Semifinále', 'Čtvrtfinále', 'Osmifinále', 'Výhra', 'Postup', 'Bronz'];

/** Odkaz z formuláře: [hodnota pro DB, chyba]. */
function ve_odkaz(string $vstup): array {
    $u = trim($vstup);
    if ($u === '') return ['', ''];
    $holy = (string)preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($u, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $holy) && !preg_match('~^(https?:|mailto:|tel:)~i', $holy)) {
        return [$u, 'Odkaz na zprávu musí začínat https:// (nebo vést na stránku tohoto webu).'];
    }
    $n = normalizuj_url($u);
    if (preg_match('~^https?://~i', $n)) {
        $host = (string)parse_url($n, PHP_URL_HOST);
        if (preg_match('~\s~u', $n) || !preg_match('/^[\p{L}\p{N}.\-]+\.\p{L}{2,}$/u', $host)) {
            return [$u, 'Odkaz na zprávu nevypadá jako adresa webu. Zkopírujte ho celý z prohlížeče.'];
        }
    }
    if (bezpecny_odkaz($n) === '') return [$u, 'Tento odkaz nejde použít.'];
    return [$n, ''];
}

$id = (int)($_GET['id'] ?? 0);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') $id = (int)vstup_int('id');
$stary = $id ? row('SELECT * FROM cltk_vysledky WHERE id = ?', [$id]) : null;
if ($id && !$stary) redirect(VE_SEZNAM, 'Výsledek nebyl nalezen – možná ho mezitím někdo smazal.', 'err');

$v = $stary ?? ['id' => 0, 'datum' => dnes(), 'misto' => '', 'stitek' => '', 'hraci' => '', 'souper' => '', 'text' => '',
                'sety' => '[]', 'verdikt' => '', 'odkaz' => '', 'foto' => '', 'visible' => 1];
$setyText = implode(' ', array_map('strval', json_pole((string)$v['sety'])));
$chyby = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek('vysledky-edit.php' . ($id ? '?id=' . $id : ''));
    if (vstup('action', 20) !== 'ulozit') redirect(VE_SEZNAM, 'Neznámý požadavek.', 'err');

    $datumRaw = vstup('datum', 20);
    $datum = normalizuj_datum($datumRaw);
    $v['misto']   = vstup('misto', 80);
    $v['stitek']  = vstup('stitek', 200);
    $v['hraci']   = vstup('hraci', 200);
    $v['souper']  = vstup('souper', 200);
    $v['text']    = vstup('text', 1000);
    $v['verdikt'] = vstup('verdikt', 40);
    [$v['odkaz'], $chOdkaz] = ve_odkaz(vstup('odkaz', 255));
    $v['visible'] = vstup_bool('visible');
    $setyText = vstup('sety', 200);
    $sety = sety_z_textu($setyText);
    $v['datum'] = $datum === false || $datum === null ? $datumRaw : $datum;

    if ($datum === null) $chyby[] = 'Vyplňte datum zápasu – podle něj se výsledky řadí.';
    if ($datum === false) $chyby[] = 'Datum nerozumím. Zapište ho jako 27. 9. 2026.';
    if ($v['stitek'] === '') $chyby[] = 'Vyplňte štítek turnaje (např. „US Open · finále · smíšená čtyřhra“).';
    if ($v['hraci'] === '') $chyby[] = 'Vyplňte hráče (např. „Karolína Muchová a Jakub Menšík“).';
    if ($setyText !== '' && json_pole($sety) === []) $chyby[] = 'Sety se nepodařilo přečíst. Pište je za sebou, např. 6:3 1:6 10:6.';
    if (strlen($sety) > 255) $chyby[] = 'Setů je příliš mnoho.';
    if ($chOdkaz !== '') $chyby[] = $chOdkaz;

    $foto = null;
    if (!$chyby) {
        $foto = admin_obrazek('foto', $stary['foto'] ?? '', 'vysledky', 1600, 1600, $v['hraci'] . ' ' . $v['stitek']);
        if ($foto['chyba'] !== '') $chyby[] = $foto['chyba'] . ' Vyberte prosím fotku znovu.';
    }
    if (!$chyby) {
        $data = ['datum' => $datum, 'misto' => $v['misto'], 'stitek' => $v['stitek'], 'hraci' => $v['hraci'], 'souper' => $v['souper'],
                 'text' => $v['text'], 'sety' => $sety, 'verdikt' => $v['verdikt'], 'odkaz' => $v['odkaz'],
                 'foto' => $foto['soubor'], 'visible' => $v['visible'], 'updated_at' => ted()];
        try {
            if ($stary) {
                if (substr((string)$stary['datum'], 0, 10) !== $datum) {
                    $data['poradi'] = admin_dalsi_poradi('cltk_vysledky', 'datum', $datum);   // nový den = na konec dne
                }
                db_update('cltk_vysledky', $id, $data);
            } else {
                $data['poradi'] = admin_dalsi_poradi('cltk_vysledky', 'datum', $datum);
                $data['created_at'] = ted();
                $id = db_insert('cltk_vysledky', $data);
            }
        } catch (Throwable $e) {
            if ($foto['soubor'] !== (string)($stary['foto'] ?? '')) admin_smazat_soubory([$foto['soubor']]);
            throw $e;
        }
        admin_smazat_soubory($foto['smazat']);
        $naUvodu = in_array($id, array_map(fn($r) => (int)$r['id'], posledni_vysledky(8)), true);
        redirect(VE_SEZNAM, 'Výsledek je uložený.' . ($v['visible'] && !$naUvodu ? ' Na úvodu ale není – je starší než osm novějších výsledků.' : ''));
    }
}

$nova = (int)$v['id'] === 0;
admin_head($nova ? 'Nový výsledek' : 'Úprava výsledku', $user, [
    'zpet' => [VE_SEZNAM, 'Výsledky hráčů'], 'sirka' => 'uzka',
    'podnadpis' => 'Jeden sloupec v pásu výsledků na úvodu. Jen skutečné výsledky se skóre.',
]);
echo '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';
?>
<?php if ($chyby): ?>
  <ul class="uv-chyby" role="alert"><li><b>Výsledek se nepodařilo uložit:</b></li><?php foreach ($chyby as $ch): ?><li><?= e($ch) ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
      <?= pole_radek([
            pole_datum('datum', 'Datum zápasu', (string)$v['datum'], ['required' => true, 'hint' => 'Podle data se výsledky řadí – nejnovější je první.']),
            pole_text('misto', 'Místo', $v['misto'], ['maxlength' => 80, 'placeholder' => 'New York']),
          ]) ?>
      <?= pole_text('stitek', 'Štítek turnaje', $v['stitek'], ['required' => true, 'maxlength' => 200,
            'placeholder' => 'US Open · finále · smíšená čtyřhra', 'hint' => 'Na webu zlatě verzálkami nad jménem. Části oddělte středovou tečkou „·“.']) ?>
      <?= pole_radek([
            pole_text('hraci', 'Hráč nebo hráči klubu', $v['hraci'], ['required' => true, 'maxlength' => 200, 'placeholder' => 'Karolína Muchová a Jakub Menšík']),
            pole_text('souper', 'Soupeř', $v['souper'], ['maxlength' => 200, 'placeholder' => 'Belinda Bencicová, Flavio Cobolli']),
          ]) ?>
      <?= pole_textarea('text', 'Krátký text', $v['text'], ['rows' => 3, 'maxlength' => 1000, 'attrs' => ['data-pocitadlo' => '170'],
            'hint' => 'Jedna dvě věty pod jménem.']) ?>
      <div class="field">
        <?= pole_text('sety', 'Sety', $setyText, ['maxlength' => 200, 'placeholder' => '6:3 1:6 10:6',
              'hint' => 'Z pohledu našeho hráče, oddělené mezerou. Tiebreak do závorky „7:6 (7:5)“, skreč „6:1 1:0 skr.“. Prohrané sety budou na webu šedé.']) ?>
        <div class="uv-sety-nahled" data-sety-nahled="f-sety" aria-hidden="true"></div>
      </div>
      <div class="field">
        <label for="f-verdikt">Verdikt</label>
        <input type="text" id="f-verdikt" name="verdikt" value="<?= e($v['verdikt']) ?>" maxlength="40" list="verdikty" placeholder="Titul">
        <datalist id="verdikty"><?php foreach (VE_VERDIKTY as $vd): ?><option value="<?= e($vd) ?>"><?php endforeach; ?></datalist>
        <div class="hint">Drobně zlatě pod sety – Titul, Finále, Semifinále…</div>
      </div>
      <?= pole_text('odkaz', 'Odkaz na zprávu (nepovinné)', $v['odkaz'], ['maxlength' => 255, 'attrs' => ['inputmode' => 'url'], 'placeholder' => 'https://…']) ?>
      <?= pole_obrazek('foto', 'Fotka (nepovinné)', $v['foto'], ['hint' => 'Pás výsledků se obejde bez fotky. Když ji nahrajete, šablona ji může použít.']) ?>
      <?= pole_check('visible', 'Zobrazit na webu', (int)$v['visible'] === 1) ?>
      <?= tlacitka_formulare($nova ? 'Přidat výsledek' : 'Uložit změny', VE_SEZNAM, 'Zpět na seznam') ?>
    </form>
  </div>
</section>
<?php
echo '<script src="' . e(BASE_PATH . verze('admin/assets/admin-uvod.js')) . '" defer></script>';
admin_foot();
