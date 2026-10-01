<?php
/* Detail akce z klubového kalendáře a přihláška k ní (akce.php?id=…).
   Formulář je u každé akce jiný (ZADANI §0.3) – pole skládá akce_formular().
   Přihláška se ukládá do cltk_signups. Bez relace a bez cookies: podepsaný
   token + past na roboty + brzda 8 přihlášek / hodinu / IP (inc/functions.php).
   Okno kalendáře na úvodu sem posílá formulář fetch()em s Accept:
   application/json a dostane JSON; bez JavaScriptu se po uložení přesměruje
   na ?odeslano=1 (vzor POST → přesměrování → GET). */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
require_once __DIR__ . '/inc/mail.php';

$id = (int)($_GET['id'] ?? 0);
$a = $id > 0 ? akce_detail($id) : null;
if (!$a) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && chce_json()) {
        odpoved_json(['ok' => false, 'chyba' => 'Tuto akci jsme nenašli – mohla být zrušena.', 'chyby' => []], 404);
    }
    stranka_nenalezena('Tuto akci jsme <em>nenašli</em>.', 'Akce mohla být zrušena nebo přesunuta. Podívejte se do klubového kalendáře na úvodní stránce.');
}

/* ---------- odeslaná přihláška (před jakýmkoli výstupem) ---------- */
$hodnoty = [];
$chyby = [];
$chyba = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $chyba = verejny_formular_over('akce-' . (int)$a['id']);
    $tokenChyba = $chyba !== '';
    $r = akce_formular_zpracuj($a, $_POST);
    if ($chyba === '' && !$a['prihlaseni']) $chyba = akce_stav_prihlasek($a + ['prihlaseni_zapnuto' => (int)$a['prihlaseni_povoleno'] === 1, 'obsazeno' => akce_obsazeno($a)]) ?: 'Přihlašování na tuto akci je uzavřené.';
    if ($chyba === '' && $r['ok']) {
        try {
            db_insert('cltk_signups', $r['radek']);
        } catch (Throwable $e) {
            error_log('[akce.php] přihláška se neuložila: ' . $e->getMessage());
            $chyba = 'Přihlášku se teď nepodařilo uložit. Zkuste to prosím za chvíli znovu, nebo zavolejte na recepci.';
        }
        if ($chyba === '') {
            verejny_formular_zapis('akce-' . (int)$a['id']);
            try { upozorni_prihlaska_akce($a, $r['radek']); } catch (Throwable $e) { error_log('[akce.php] e-mail: ' . $e->getMessage()); }
            if (chce_json()) odpoved_json(['ok' => true, 'zprava' => 'Děkujeme, přihláška je uložená.']);
            redirect(url('akce.php?id=' . (int)$a['id'] . '&odeslano=1') . '#prihlaska');
        }
    }
    if (chce_json()) {
        odpoved_json(['ok' => false, 'chyba' => $chyba, 'chyby' => $tokenChyba ? [] : $r['chyby']], $tokenChyba ? 400 : 422);
    }
    $hodnoty = $r['hodnoty'];
    $chyby = $tokenChyba ? [] : $r['chyby'];
    if ($tokenChyba) http_response_code(400);
}
track_visit();

$odeslano = ($_GET['odeslano'] ?? '') === '1' && $_SERVER['REQUEST_METHOD'] !== 'POST';
$js = akce_pro_js($a);
$termin = akce_termin($a, true);
$meta = akce_meta_text($a);
$stavPrihlasek = akce_stav_prihlasek($js);
$popis = html_ocistit((string)$a['popis']);

$sablona = [
    'titulek' => (string)$a['nazev'],
    'popis'   => trim((string)$a['perex']) !== '' ? (string)$a['perex'] : $a['nazev'] . ' – ' . $termin . '. Klubový kalendář I. ČLTK Praha.',
    'css'     => ['index.css'],
    'trida'   => 'stranka-akce',
    'obrazek' => (string)$a['foto'],
    'noindex' => $odeslano || $_SERVER['REQUEST_METHOD'] === 'POST',
];
require __DIR__ . '/inc/sablona/hlavicka.php';

$hlava = [
    'stitek' => $termin, 'nadpis' => e((string)$a['nazev']), 'perex' => (string)$a['perex'],
    'foto' => (string)$a['foto'], 'foto_popisek' => '', 'doplni_klub' => 0,
    'odkaz' => $a['prihlaseni'] && !$odeslano ? '#prihlaska' : '', 'odkaz_text' => $js['formular']['tlacitko'],
    'odkaz2' => (string)$a['odkaz'], 'odkaz2_text' => (string)$a['odkaz_text'],
];
$navic = ($meta !== '' ? '<p class="kal-detail__meta" style="margin-top:1rem">' . typo($meta) . '</p>' : '')
       . ($a['probehla'] ? '<p class="kal-detail__stav"><span class="doplni">proběhlo</span></p>' : '');
?>

<?= hlava_stranky($hlava, ['drobky' => [['Klubový kalendář', 'index.php#kalendar'], [(string)$a['nazev']]], 'navic' => $navic]) ?>

<?php if ($popis !== ''): ?>
  <section class="sekce sekce--tesna sekce--linka" aria-label="Popis akce">
    <div class="wrap"><div class="prose"><?= $popis ?></div></div>
  </section>
<?php endif; ?>

<?php if ((int)$a['prihlaseni_povoleno'] === 1 || $odeslano): ?>
  <section class="sekce sekce--papir2 akce-prihlaska" id="prihlaska" aria-labelledby="prihlaska-nadpis">
    <div class="wrap wrap--text">
      <header class="hlava">
        <p class="hlava__znacka"><span class="stitek">Přihláška</span></p>
        <h2 class="h2" id="prihlaska-nadpis"><?= $odeslano ? 'Děkujeme, <em>jste přihlášeni</em>.' : 'Přihlaste se na <em>akci</em>.' ?></h2>
      </header>
      <?php if ($odeslano): ?>
        <div class="hlaska hlaska--ok" role="status" tabindex="-1" id="prihlaska-ok">
          <span class="hlaska__titul">Přihláška je uložená.</span>
          <p>Na akci <b><?= typo((string)$a['nazev']) ?></b> (<?= typo($termin) ?>) jsme si vás zapsali. Kdyby se něco změnilo, ozveme se vám e-mailem.</p>
        </div>
        <p style="margin-top:2rem"><?= tlacitko('index.php#kalendar', 'Zpět do kalendáře', 'odkaz', ['sipka' => 'zpet']) ?></p>
      <?php elseif ($a['prihlaseni']): ?>
        <?php if ($chyba !== '' || $chyby): ?>
        <div class="hlaska hlaska--chyba" role="alert" tabindex="-1" style="margin-bottom:2rem">
          <span class="hlaska__titul"><?= e($chyba !== '' ? $chyba : 'Zkontrolujte prosím zvýrazněná pole.') ?></span>
        </div>
        <?php endif; ?>
        <div class="karta karta--zlata"><?= akce_formular_html($a, $hodnoty, $chyby) ?></div>
      <?php else: ?>
        <div class="hlaska" role="status"><span class="hlaska__titul"><?= e($chyba !== '' ? $chyba : ($stavPrihlasek ?: 'Přihlašování na tuto akci je uzavřené.')) ?></span>
          <p>S dotazem vám rádi pomůžeme na recepci<?php if (setting('recepce_telefon') !== ''): ?> – <a href="<?= e(tel_href(setting('recepce_telefon'))) ?>"><?= str_replace(' ', '&nbsp;', e(setting('recepce_telefon'))) ?></a><?php endif; ?>.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

  <section class="sekce sekce--tesna" aria-label="Další akce">
    <div class="wrap">
      <p><?= tlacitko('index.php#kalendar', 'Všechny akce v kalendáři', 'odkaz', ['sipka' => 'zpet']) ?></p>
    </div>
  </section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
