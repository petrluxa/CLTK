<?php
/* Partneři – mřížka 5 × 5 jednobarevných log v teplé béžové na úvodu
   (ZADANI §4.9, §0.2). Na webu se zobrazuje logo_mono. Loga dodaná
   klientem jsou hotová a nepřebarvují se; logo NOVĚ nahrané tady se
   přebarví automaticky (partner_logo_mono() – stejná barva #a3947e
   a krytí 60 % jako dodaná loga). Hotové jednobarevné PNG jde nahrát
   i přímo – pak se nepřebarvuje. */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const PA_STRANKA = 'partneri.php';
const PA_V_RADE = 5;

/** Odkaz na web partnera: [hodnota pro DB, chyba]. */
function pa_odkaz(string $vstup): array {
    $u = trim($vstup);
    if ($u === '') return ['', ''];
    $holy = (string)preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($u, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $holy) && !preg_match('~^https?:~i', $holy)) {
        return [$u, 'Odkaz na web partnera musí začínat https:// (nebo http://).'];
    }
    $n = normalizuj_url($u);
    $host = (string)parse_url($n, PHP_URL_HOST);
    if (!preg_match('~^https?://~i', $n) || preg_match('~\s~u', $n) || !preg_match('/^[\p{L}\p{N}.\-]+\.\p{L}{2,}$/u', $host)) {
        return [$u, 'Odkaz nevypadá jako adresa webu, např. www.partner.cz.'];
    }
    return [$n, ''];
}

$chyby = [];
$form = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek(PA_STRANKA);
    $akce = vstup('action', 20);
    $id   = (int)vstup_int('id');

    if (in_array($akce, ['prepnout', 'nahoru', 'dolu', 'smazat'], true)) {
        $p = row('SELECT * FROM cltk_partneri WHERE id = ?', [$id]);
        if (!$p) redirect(PA_STRANKA, 'Partner nebyl nalezen.', 'err');
        if ($akce === 'prepnout') {
            $novy = admin_prepni('cltk_partneri', $id);
            redirect(PA_STRANKA, $novy ? 'Logo „' . $p['nazev'] . '“ je na webu.' : 'Logo „' . $p['nazev'] . '“ je skryté.');
        }
        if ($akce === 'nahoru' || $akce === 'dolu') {
            admin_posun('cltk_partneri', $id, $akce === 'nahoru' ? -1 : 1);
            redirect(PA_STRANKA, 'Pořadí bylo změněno.');
        }
        q('DELETE FROM cltk_partneri WHERE id = ?', [$id]);
        admin_smazat_soubory(array_filter([(string)$p['logo'], (string)$p['logo_mono']]));
        redirect(PA_STRANKA, 'Partner „' . $p['nazev'] . '“ byl smazán i s logy.');
    }

    if ($akce === 'ulozit') {
        $p = $id ? row('SELECT * FROM cltk_partneri WHERE id = ?', [$id]) : null;
        if ($id && !$p) redirect(PA_STRANKA, 'Partner mezitím zmizel – možná ho někdo smazal.', 'err');
        $p = $p ?? ['id' => 0, 'logo' => '', 'logo_mono' => ''];

        $nazev = vstup('nazev', 120);
        [$url, $chUrl] = pa_odkaz(vstup('url', 255));
        $visible = vstup_bool('visible');
        $prebarvit = vstup_bool('prebarvit');

        if ($nazev === '') $chyby[] = 'Vyplňte název partnera.';
        if ($chUrl !== '') $chyby[] = $chUrl;
        $form = ['id' => $id, 'nazev' => $nazev, 'url' => $url, 'visible' => $visible, 'logo' => $p['logo'], 'logo_mono' => $p['logo_mono']];

        $nove = [];          // soubory nahrané v tomto požadavku – při chybě se uklidí
        $smazat = [];        // staré soubory – smažou se až po zápisu do DB
        $logo = (string)$p['logo'];
        $mono = (string)$p['logo_mono'];

        if (!$chyby) {
            /* 1) barevné logo (originál) */
            $up = admin_obrazek('logo', $p['logo'], 'partneri', 1600, 1600, $nazev);
            if ($up['chyba'] !== '') {
                $chyby[] = 'Logo: ' . $up['chyba'];
            } else {
                $logo = $up['soubor'];
                $smazat = array_merge($smazat, $up['smazat']);
                $noveLogo = $logo !== '' && $logo !== (string)$p['logo'];
                if ($noveLogo) $nove[] = $logo;

                /* 2) jednobarevná verze: hotové PNG > nové logo / přebarvit znovu > beze změny */
                $monoUp = $_FILES['logo_mono'] ?? null;
                $hotoveMono = is_array($monoUp) && (int)($monoUp['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                if ($hotoveMono) {
                    $m = admin_obrazek('logo_mono', $p['logo_mono'], 'partneri/mono', 1200, 600, $nazev);
                    if ($m['chyba'] !== '') {
                        $chyby[] = 'Jednobarevné logo: ' . $m['chyba'];
                    } elseif (!str_ends_with(strtolower($m['soubor']), '.png')) {
                        $nove[] = $m['soubor'];
                        $chyby[] = 'Jednobarevné logo musí mít průhledné pozadí (PNG). Tohle průhlednost nemá – nahrajte ho raději do pole „Logo“, jednobarevná verze se vyrobí sama.';
                    } else {
                        $nove[] = $m['soubor'];
                        if ($mono !== '') $smazat[] = $mono;
                        $mono = $m['soubor'];
                    }
                } elseif ($noveLogo || ($prebarvit && $logo !== '')) {
                    $novaMono = partner_logo_mono($logo, $nazev);   // vždy NOVÝ soubor v partneri/mono/
                    if ($novaMono === '') {
                        $chyby[] = 'Z loga se nepodařilo vyrobit jednobarevnou verzi (je možná celé bílé nebo průhledné). Zkuste jiný soubor, nebo nahrajte hotové jednobarevné PNG.';
                    } else {
                        $nove[] = $novaMono;
                        if ($mono !== '') $smazat[] = $mono;
                        $mono = $novaMono;
                    }
                }
                /* odebrané barevné logo: jednobarevná verze zůstává – na webu se zobrazuje ona */
                if (!empty($_POST['logo_mono_odebrat']) && !$hotoveMono && $mono !== '' && $mono === (string)$p['logo_mono']) {
                    $smazat[] = $mono;
                    $mono = '';
                }
            }
        }

        if (!$chyby) {
            $data = ['nazev' => $nazev, 'url' => $url, 'logo' => $logo, 'logo_mono' => $mono, 'visible' => $visible, 'updated_at' => ted()];
            try {
                if ((int)$p['id'] > 0) {
                    db_update('cltk_partneri', $id, $data);
                } else {
                    $data['poradi'] = admin_dalsi_poradi('cltk_partneri');
                    $data['created_at'] = ted();
                    $id = db_insert('cltk_partneri', $data);
                }
            } catch (Throwable $e) {
                admin_smazat_soubory($nove);
                throw $e;
            }
            admin_smazat_soubory(array_values(array_diff($smazat, [$logo, $mono])));   // TEPRVE po zápisu do DB
            $hlaska = 'Partner „' . $nazev . '“ je uložený.';
            $druh = 'ok';
            if ($mono === '' && $logo === '') { $hlaska .= ' Nemá logo – na webu bude prázdné místo.'; $druh = 'warn'; }
            elseif ($mono === '') { $hlaska .= ' Pozor: chybí jednobarevné logo, web ukáže barevný originál.'; $druh = 'warn'; }
            redirect(PA_STRANKA . '?id=' . $id, $hlaska, $druh);
        }
        admin_smazat_soubory(array_values(array_diff($nove, [(string)$p['logo'], (string)$p['logo_mono']])));
    }
    if (!$chyby) redirect(PA_STRANKA, 'Neznámý požadavek.', 'err');
}

$upravit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($form === null && $upravit > 0) {
    $form = row('SELECT * FROM cltk_partneri WHERE id = ?', [$upravit]);
    if (!$form) redirect(PA_STRANKA, 'Partner nebyl nalezen.', 'err');
}
if ($form === null && isset($_GET['nova'])) {
    $form = ['id' => 0, 'nazev' => '', 'url' => '', 'visible' => 1, 'logo' => '', 'logo_mono' => ''];
}

$css = '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';

if ($form !== null):
    $nova = (int)$form['id'] === 0;
    admin_head($nova ? 'Nový partner' : 'Úprava partnera', $user, [
        'zpet' => [PA_STRANKA, 'Partneři'], 'sirka' => 'uzka',
        'podnadpis' => 'Na webu je každé logo jednobarevné v teplé béžové, aby mřížka působila klidně. Barevné logo stačí nahrát – jednobarevná verze se vyrobí sama.',
    ]);
    echo $css;
    $monoUrl = (string)$form['logo_mono'] !== '' && is_file(UPLOAD_DIR . '/' . $form['logo_mono']) ? upload_url($form['logo_mono']) : '';
?>
<?php if ($chyby): ?>
  <ul class="uv-chyby" role="alert"><li><b>Partnera se nepodařilo uložit:</b></li><?php foreach ($chyby as $ch): ?><li><?= e($ch) ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<?php if (!$nova): ?>
<section class="panel">
  <div class="panel-head"><h2>Na webu</h2><span class="hint">Jednobarevné logo na papíře webu, jak ho uvidí návštěvník.</span></div>
  <div class="panel-body">
    <?php if ($monoUrl !== ''): ?>
      <div class="uv-logo-web"><img src="<?= e($monoUrl) ?>" alt="Logo <?= e($form['nazev']) ?> na webu"></div>
    <?php elseif ((string)$form['logo'] !== ''): ?>
      <div class="uv-logo-web"><img src="<?= e(upload_url($form['logo'])) ?>" alt="Logo <?= e($form['nazev']) ?> na webu"></div>
      <p class="hint hint--varovani">Jednobarevná verze chybí, web teď ukazuje barevný originál. Zaškrtněte níže „Vyrobit jednobarevnou verzi znovu“.</p>
    <?php else: ?>
      <div class="uv-logo-web uv-logo-web--prazdne">Bez loga</div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
      <?= pole_text('nazev', 'Název partnera', $form['nazev'], ['required' => true, 'maxlength' => 120, 'placeholder' => 'J&T Banka',
            'hint' => 'Na webu jako popis loga pro čtečky obrazovky.']) ?>
      <?= pole_text('url', 'Web partnera (nepovinné)', $form['url'], ['maxlength' => 255, 'attrs' => ['inputmode' => 'url'], 'placeholder' => 'www.partner.cz',
            'hint' => 'Bez odkazu se logo jen zobrazí (např. Glenfiddich). Otevírá se v novém okně.']) ?>
      <?= pole_obrazek('logo', 'Logo (barevné, jak ho partner dodal)', $form['logo'], ['nahled' => 'logo',
            'hint' => 'PNG s průhledným pozadím je nejlepší, ale poradí si i JPG s bílým nebo barevným pozadím – pozadí se odmaže a logo se přebarví na béžovou webu.']) ?>
      <?php if ((string)$form['logo'] !== ''): ?>
        <?= pole_check('prebarvit', 'Vyrobit jednobarevnou verzi znovu z tohoto loga', false,
              ['hint' => 'Přepíše současnou jednobarevnou verzi. U log, která dodal klient hotová, to není potřeba.']) ?>
      <?php endif; ?>
      <fieldset>
        <legend>Hotové jednobarevné logo</legend>
        <?= pole_obrazek('logo_mono', 'Jednobarevné PNG (nepovinné)', $form['logo_mono'], ['nahled' => 'logo',
              'hint' => 'Jen když máte logo už přebarvené (béžová, průhledné pozadí) – použije se tak, jak je. Jinak nechte prázdné.']) ?>
      </fieldset>
      <?= pole_check('visible', 'Zobrazit na webu', (int)$form['visible'] === 1) ?>
      <?= tlacitka_formulare($nova ? 'Přidat partnera' : 'Uložit změny', PA_STRANKA, 'Zpět na seznam') ?>
    </form>
  </div>
</section>
<?php
    admin_foot();
    exit;
endif;

/* --- seznam --- */
$radky = rows('SELECT * FROM cltk_partneri ORDER BY poradi, id');
$viditelnych = count(array_filter($radky, fn($r) => (int)$r['visible'] === 1));

admin_head('Partneři', $user, [
    'podnadpis' => 'Mřížka log na úvodní stránce, pět v řadě. Pořadí šipkami = pořadí v mřížce zleva doprava a shora dolů.',
    'akce'      => '<a class="btn btn-primary" href="' . PA_STRANKA . '?nova=1">Přidat partnera</a>',
]);
echo $css;
?>

<?php if ($radky): ?>
<section class="panel">
  <div class="panel-head"><h2>Mřížka na webu <small><?= $viditelnych ?> log</small></h2><span class="hint">Skrytá loga jsou tady jen naznačená.</span></div>
  <div class="panel-body">
    <div class="uv-mrizka-log">
      <?php foreach ($radky as $r): $u = partner_logo_url($r); ?>
        <span<?= (int)$r['visible'] === 1 ? '' : ' class="je-skryte"' ?> title="<?= e($r['nazev']) ?>"><?= $u !== '' ? '<img src="' . e($u) . '" alt="' . e($r['nazev']) . '" loading="lazy">' : '<small class="uv-tlumene">' . e($r['nazev']) . '</small>' ?></span>
      <?php endforeach; ?>
    </div>
    <?php if ($viditelnych % PA_V_RADE !== 0): ?>
      <p class="hint">Poslední řada mřížky není plná (<?= $viditelnych ?> log, v řadě je <?= PA_V_RADE ?>).</p>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section class="panel">
  <div class="panel-head"><h2>Partneři <small><?= $viditelnych ?> na webu · <?= count($radky) ?> celkem</small></h2></div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím žádný partner', 'Přidejte prvního – stačí název a barevné logo.',
          '<a class="btn btn-primary" href="' . PA_STRANKA . '?nova=1">Přidat partnera</a>') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Logo na webu</th><th>Název</th><th>Odkaz</th><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($radky as $i => $r):
            $id = (int)$r['id']; $u = partner_logo_url($r); ?>
          <tr<?= (int)$r['visible'] === 1 ? '' : ' class="je-skryte"' ?>>
            <td data-label="Logo na webu"><?= $u !== '' ? '<img class="thumb-logo" src="' . e($u) . '" alt="">' : '<span class="thumb-ph">bez loga</span>' ?></td>
            <td data-label="Název" class="td-nazev"><b><?= e($r['nazev']) ?></b>
              <?php if ((string)$r['logo_mono'] === '' && (string)$r['logo'] !== ''): ?><small class="hint--varovani">chybí jednobarevná verze</small><?php endif; ?></td>
            <td data-label="Odkaz" class="tlumene uv-cesta"><?= (string)$r['url'] !== '' ? e(preg_replace('~^https?://(www\.)?~i', '', rtrim($r['url'], '/'))) : 'bez odkazu' ?></td>
            <td data-label="Stav"><?= stav_badge($r['visible']) ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitka_poradi($id, $i === 0, $i === count($radky) - 1) ?>
              <a class="btn btn-sm btn-ghost" href="<?= PA_STRANKA ?>?id=<?= $id ?>">Upravit</a>
              <?= tlacitko_prepnout($id, $r['visible']) ?>
              <?= tlacitko_smazat($id, 'Smazat partnera „' . $r['nazev'] . '“ i s logy? Nejde to vrátit – když ho chcete jen schovat, použijte Skrýt.') ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php admin_foot(); ?>
