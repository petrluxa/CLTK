<?php
/* Dokumenty – Stanovy, Pravidla hraní a rezervací, Osobní údaje členů, provozní řády,
   ceník, plán areálu (ZADANI §6.18) a archiv klubových turnajů.
   Klient 2. 10. 2026: dokumenty klubu NEJSOU PDF – každý je stránka webu v klubovém stylu
   (dokument.php?d=<adresa stránky>) s textem z editoru, tisk stránky PDF nahrazuje.
   PDF se nahrává JEN do archivu klubových turnajů (kategorie „turnaje“: pozvánky,
   rozlosování, výsledky – rok + název turnaje, počet stran se zjistí sám).
   Odkaz jinam (url) jen výjimečně, když dokument leží na jiném webu.
   Zaškrtnuté „v patičce“ se ukážou ve sloupci „Dokumenty a sítě“ v patičce webu.
   Obrázek do textu nahrává stejný koncový bod jako modul Stránky (stranky.php,
   uploads/bloky/text/); po uložení se smažou obrázky, které z textu zmizely
   a nikde jinde se nepoužívají. */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const DO_STRANKA = 'dokumenty.php';
const DO_KATEGORIE = DOKUMENTY_KATEGORIE;
const DO_TEXT_MAX = 60000;                // bajtů – sloupec TEXT v MySQL pojme 65 535

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

/** Obrázky vložené do textu dokumentu (uploads/bloky/text/ – nahrává je stranky.php). */
function do_obrazky_textu(string $html): array {
    preg_match_all('~<img\b[^>]*\ssrc="([^"]+)"~i', $html, $m);
    $v = [];
    foreach ($m[1] as $src) {
        $src = html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('~(?:^|/)uploads/(bloky/text/[a-z0-9\-_.]+)$~i', $src, $x) && !str_contains($x[1], '..')) $v[] = $x[1];
    }
    return array_values(array_unique($v));
}

/** Smaže obrázky z textu, které už nepoužívá žádný dokument, blok stránky ani popis akce. Až po zápisu do DB. */
function do_smazat_obrazky_textu(array $soubory): void {
    foreach ($soubory as $s) {
        $vzor = '%' . $s . '%';
        $pouzito = (int)val('SELECT COUNT(*) FROM cltk_dokumenty WHERE text LIKE ?', [$vzor]);
        $pouzito += (int)val('SELECT COUNT(*) FROM cltk_bloky WHERE text LIKE ?', [$vzor]);
        try { $pouzito += (int)val('SELECT COUNT(*) FROM cltk_akce WHERE popis LIKE ?', [$vzor]); } catch (Throwable $e) { $pouzito++; }
        if ($pouzito === 0) delete_upload($s);
    }
}

/** Adresa stránky dokumentu: z vyplněné hodnoty, jinak z názvu; obsazenou automaticky doplní -2, -3…
 *  Vrací [slug, chyba]. */
function do_slug(string $vstup, string $nazev, int $id, bool $potreba): array {
    $vlastni = trim($vstup) !== '';
    $slug = $vlastni ? slugify($vstup) : ($potreba ? slugify($nazev) : '');
    if ($slug === '') return ['', ''];
    $slug = trim(substr($slug, 0, 110), '-');
    if (!dokument_slug_platny($slug)) return [$slug, 'Adresa stránky smí mít jen malá písmena bez diakritiky, číslice a pomlčky.'];
    $obsazena = static fn(string $s): bool => (bool)row('SELECT id FROM cltk_dokumenty WHERE slug = ? AND id <> ?', [$s, $id]);
    if (!$obsazena($slug)) return [$slug, ''];
    if ($vlastni) return [$slug, 'Adresu stránky „' . $slug . '“ už má jiný dokument – zvolte jinou.'];
    for ($i = 2; $i < 100; $i++) if (!$obsazena($slug . '-' . $i)) return [$slug . '-' . $i, ''];
    return [$slug, 'Adresu stránky se nepodařilo vymyslet – vyplňte ji prosím sami.'];
}

/** Poslal formulář soubor? (po chybě je ho potřeba vybrat znovu) */
function do_byl_soubor(): bool {
    $f = $_FILES['soubor'] ?? null;
    return is_array($f) && (int)($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

$chyby = [];
$form = null;
$bylSoubor = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek(DO_STRANKA);
    $akce = vstup('action', 20);
    $id   = (int)vstup_int('id');

    if (in_array($akce, ['prepnout', 'nahoru', 'dolu', 'smazat'], true)) {
        $d = row('SELECT * FROM cltk_dokumenty WHERE id = ?', [$id]);
        if (!$d) redirect(DO_STRANKA, 'Dokument nebyl nalezen.', 'err');
        $kotva = '#d' . $id;
        if ($akce === 'prepnout') {
            $novy = admin_prepni('cltk_dokumenty', $id);
            redirect(DO_STRANKA . $kotva, $novy ? 'Dokument „' . $d['nazev'] . '“ je na webu.' : 'Dokument „' . $d['nazev'] . '“ je skrytý.');
        }
        if ($akce === 'nahoru' || $akce === 'dolu') {
            // dokumenty klubu se řadí mezi sebou (stránky i patička), přílohy archivu uvnitř turnaje a ročníku
            if ((string)$d['kategorie'] === DOKUMENTY_ARCHIV) {
                admin_posun('cltk_dokumenty', $id, $akce === 'nahoru' ? -1 : 1, 'skupina', 'kategorie = ? AND rok = ?', [DOKUMENTY_ARCHIV, (int)$d['rok']]);
            } else {
                admin_posun('cltk_dokumenty', $id, $akce === 'nahoru' ? -1 : 1, null, 'kategorie <> ?', [DOKUMENTY_ARCHIV]);
            }
            redirect(DO_STRANKA . $kotva, 'Pořadí bylo změněno.');
        }
        q('DELETE FROM cltk_dokumenty WHERE id = ?', [$id]);
        admin_smazat_soubory([(string)$d['soubor']]);
        do_smazat_obrazky_textu(do_obrazky_textu((string)($d['text'] ?? '')));
        redirect(DO_STRANKA, 'Dokument „' . $d['nazev'] . '“ byl smazán.');
    }

    if ($akce === 'ulozit') {
        $stary = $id ? row('SELECT * FROM cltk_dokumenty WHERE id = ?', [$id]) : null;
        if ($id && !$stary) redirect(DO_STRANKA, 'Dokument mezitím zmizel – možná ho někdo smazal.', 'err');
        $bylSoubor = do_byl_soubor();

        $nazev = vstup('nazev', 200);
        $kategorie = vstup('kategorie', 40);
        $archiv = $kategorie === DOKUMENTY_ARCHIV;
        $popis = vstup('popis', 255);
        $text = html_k_ulozeni(vstup('text', 0));                 // u archivu se nevypisuje (bez adresy stránky), ale nezmizí
        [$url, $chUrl] = do_odkaz(vstup('url', 255));
        $vPaticce = vstup_bool('v_paticce');
        $patickaText = vstup('paticka_text', 120);
        $visible = vstup_bool('visible');
        [$slug, $chSlug] = $archiv ? ['', ''] : do_slug(vstup('slug', 120), $nazev, $id, $text !== '');
        $skupina = $archiv ? trim((string)preg_replace('/\s+/u', ' ', vstup('skupina', 120))) : '';
        $rokText = $archiv ? vstup('rok', 6) : '';
        $rok = preg_match('/^(18|19|20)\d\d$/', $rokText) ? (int)$rokText : null;

        if ($nazev === '') $chyby[] = 'Vyplňte název dokumentu.';
        if (!isset(DO_KATEGORIE[$kategorie])) $chyby[] = 'Vyberte kategorii.';
        if ($chUrl !== '') $chyby[] = $chUrl;
        if ($chSlug !== '') $chyby[] = $chSlug;
        if ($archiv && $skupina === '') $chyby[] = 'Vyplňte název turnaje nebo akce (např. „Babolat Amateur Tour“) – podle něj se archiv seskupuje.';
        if ($archiv && $rok === null) $chyby[] = 'Rok turnaje zapište čtyřmi číslicemi (např. 2026).';
        if (!$archiv && $bylSoubor) $chyby[] = 'PDF se nahrává jen do archivu klubových turnajů. Dokument klubu napište jako text (z PDF ho lze zkopírovat a vložit do editoru) – na webu bude jako stránka a půjde vytisknout.';
        if (strlen($text) > DO_TEXT_MAX) $chyby[] = 'Text dokumentu je příliš dlouhý (' . velikost_text(strlen($text)) . ', nejvýš ' . velikost_text(DO_TEXT_MAX) . ') – rozdělte ho prosím na dva dokumenty.';
        $form = ['id' => $id, 'nazev' => $nazev, 'kategorie' => $kategorie, 'popis' => $popis, 'url' => $url,
                 'slug' => $slug !== '' ? $slug : vstup('slug', 120), 'text' => $text, 'skupina' => $skupina, 'rok' => $rokText,
                 'v_paticce' => $vPaticce, 'paticka_text' => $patickaText, 'visible' => $visible, 'stran' => $stary['stran'] ?? null,
                 'soubor' => (string)($stary['soubor'] ?? ''), 'soubor_nazev' => (string)($stary['soubor_nazev'] ?? '')];

        $pdf = null;
        if (!$chyby) {
            // archiv: nové PDF do uploads/dokumenty/turnaje/; dokument klubu smí jen odebrat staré PDF (dřívější stav)
            $pdf = $archiv
                ? admin_pdf('soubor', $stary['soubor'] ?? '', $stary['soubor_nazev'] ?? '', 'dokumenty/turnaje')
                : admin_pdf('soubor_zadny', $stary['soubor'] ?? '', $stary['soubor_nazev'] ?? '');
            if (!$archiv && !empty($_POST['soubor_odebrat']) && (string)($stary['soubor'] ?? '') !== '') {
                $pdf = ['soubor' => '', 'nazev' => '', 'chyba' => '', 'smazat' => [(string)$stary['soubor']]];
            }
            if ($pdf['chyba'] !== '') $chyby[] = $pdf['chyba'];
            elseif ($archiv && $pdf['soubor'] === '' && $url === '') $chyby[] = 'Nahrajte PDF přílohy (pozvánka, rozlosování, výsledky…).';
            elseif (!$archiv && $text === '' && $url === '' && $pdf['soubor'] === '') $chyby[] = 'Napište text dokumentu – na webu bude jako stránka. (Odkaz jinam jen výjimečně, když dokument leží na jiném webu.)';
            if ($chyby && $pdf['chyba'] === '' && $pdf['soubor'] !== (string)($stary['soubor'] ?? '') && $pdf['soubor'] !== '') {
                admin_smazat_soubory([$pdf['soubor']]);
            }
        }
        if (!$chyby) {
            $stran = $form['stran'];
            if ($pdf['soubor'] === '') $stran = null;
            elseif ($pdf['soubor'] !== (string)($stary['soubor'] ?? '')) $stran = pdf_pocet_stran(UPLOAD_DIR . '/' . $pdf['soubor']);
            $data = ['nazev' => $nazev, 'kategorie' => $kategorie, 'popis' => $popis, 'slug' => $slug, 'text' => $text,
                     'rok' => $archiv ? $rok : null, 'skupina' => $skupina, 'stran' => $stran,
                     'soubor' => $pdf['soubor'], 'soubor_nazev' => $pdf['nazev'], 'url' => $url, 'v_paticce' => $vPaticce,
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
            do_smazat_obrazky_textu(array_diff(do_obrazky_textu((string)($stary['text'] ?? '')), do_obrazky_textu($text)));
            $kam = $archiv ? ' Je v archivu turnajů na stránce Dokumenty.' : ($text !== '' ? ' Na webu je jako stránka dokument.php?d=' . $slug . '.' : '');
            redirect(DO_STRANKA . '#d' . $id, 'Dokument „' . $nazev . '“ je uložený.' . $kam);
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
    $doArchivu = ($_GET['nova'] ?? '') === 'archiv';
    $form = ['id' => 0, 'nazev' => '', 'kategorie' => $doArchivu ? DOKUMENTY_ARCHIV : 'klub', 'popis' => '', 'url' => '', 'slug' => '', 'text' => '',
             'skupina' => '', 'rok' => $doArchivu ? date('Y') : '', 'stran' => null,
             'v_paticce' => 0, 'paticka_text' => '', 'visible' => 1, 'soubor' => '', 'soubor_nazev' => ''];
}

$css = '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">'
     . '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-obsah.css')) . '">';
$js  = '<script src="' . e(BASE_PATH . verze('admin/assets/admin-uvod.js')) . '" defer></script>'
     . '<script src="' . e(BASE_PATH . verze('admin/assets/admin-obsah.js')) . '" defer></script>';

if ($form !== null):
    $nova = (int)$form['id'] === 0;
    $limit = velikost_text(upload_limit_bajtu());
    $stranka = dokument_ma_text($form) ? url('dokument.php?d=' . rawurlencode((string)$form['slug'])) : '';
    $jeArchiv = (string)$form['kategorie'] === DOKUMENTY_ARCHIV;
    $textove = implode(' ', array_diff(array_keys(DO_KATEGORIE), [DOKUMENTY_ARCHIV]));
    $turnaje = array_column(rows("SELECT DISTINCT skupina FROM cltk_dokumenty WHERE kategorie = ? AND skupina <> '' ORDER BY skupina", [DOKUMENTY_ARCHIV]), 'skupina');
    admin_head($nova ? ($jeArchiv ? 'Nová příloha archivu turnajů' : 'Nový dokument') : 'Úprava dokumentu', $user, [
        'zpet' => [DO_STRANKA, 'Dokumenty'], 'sirka' => 'uzka',
        'podnadpis' => 'Dokument klubu je stránka webu v klubovém stylu (text z editoru, jde vytisknout). PDF se nahrává jen do archivu klubových turnajů.',
    ]);
    echo $css;
?>
<?php if ($chyby): ?>
  <ul class="uv-chyby" role="alert"><li><b>Dokument se nepodařilo uložit:</b></li><?php foreach ($chyby as $ch): ?><li><?= e($ch) ?></li><?php endforeach; ?><?php if ($bylSoubor): ?><li>Vybrané PDF se neuložilo – vyberte ho prosím znovu.</li><?php endif; ?></ul>
<?php endif; ?>

<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
      <?= pole_text('nazev', 'Název dokumentu', $form['nazev'], ['required' => true, 'maxlength' => 200, 'placeholder' => $jeArchiv ? 'Babolat Amateur Tour 2026 – pozvánka a pravidla soutěže' : 'Stanovy I. ČLTK Praha',
            'hint' => 'U přílohy archivu celý název „turnaj rok – co to je“ – web pak v ročníku ukáže jen „Pozvánka a pravidla soutěže“.']) ?>
      <?= pole_radek([
            pole_select('kategorie', 'Kategorie', $form['kategorie'], DO_KATEGORIE, ['hint' => 'Podle kategorie se dokument ukáže na stránce Dokumenty a na příslušných stránkách (Klub, Areál, Ceník kurtů…).',
                'attrs' => ['data-typ-prepinac' => 'dokument']]),
            pole_text('popis', 'Krátký popis (nepovinné)', $form['popis'], ['maxlength' => 255, 'placeholder' => 'Účinnost od 20. 6. 2017', 'hint' => 'Do seznamů a pod nadpis dokumentu (např. platnost).']),
          ]) ?>

      <fieldset data-pro-typ="<?= e($textove) ?>">
        <legend>Text dokumentu na webu</legend>
        <div data-obrazky-url="stranky.php">
          <?= pole_editor('text', 'Text dokumentu', (string)$form['text'], ['hint' => 'Celý dokument jako text – web ho vysází na klubový hlavičkový papír a jde vytisknout (nahrazuje PDF). Nadpis = oddíl / článek, podnadpis = pododdíl. Číslování („1.“, „2.1.“, „a)“) pište na začátek odstavce – web ho odsadí. Poslední odstavec celý tučně = zvýrazněné upozornění v rámečku.']) ?>
        </div>
        <?= pole_text('slug', 'Adresa stránky', (string)$form['slug'], ['maxlength' => 120, 'placeholder' => 'stanovy', 'attrs' => ['autocapitalize' => 'off', 'spellcheck' => 'false'],
              'hint' => 'Konec adresy dokument.php?d=… – malá písmena bez diakritiky, číslice a pomlčky. Prázdné = vytvoří se z názvu. Po zveřejnění ji raději neměňte (staré odkazy by přestaly fungovat).'
                      . ($stranka !== '' ? ' <a href="' . e($stranka) . '" target="_blank" rel="noopener">Otevřít stránku dokumentu ↗</a>' : '')]) ?>
        <?php if (!$jeArchiv && (string)$form['soubor'] !== ''): ?>
        <div class="field">
          <span class="label">Dřívější PDF</span>
          <div class="doc-prev"><a href="<?= e(upload_url((string)$form['soubor'])) ?>" target="_blank" rel="noopener"><?= e((string)$form['soubor_nazev'] ?: basename((string)$form['soubor'])) ?></a>
            <label class="check"><input type="checkbox" name="soubor_odebrat" value="1"><span>Odebrat soubor</span></label></div>
          <p class="hint">Dokumenty klubu se jako PDF už nenabízejí – web ukazuje text. Soubor můžete odebrat.</p>
        </div>
        <?php endif; ?>
      </fieldset>

      <fieldset data-pro-typ="<?= e(DOKUMENTY_ARCHIV) ?>">
        <legend>Příloha archivu turnajů (PDF)</legend>
        <?= pole_radek([
              pole_text('skupina', 'Turnaj nebo akce', (string)$form['skupina'], ['maxlength' => 120, 'placeholder' => 'Babolat Amateur Tour', 'attrs' => ['list' => 'archiv-turnaje', 'autocomplete' => 'off'],
                  'hint' => 'Stejný název = stejná skupina v archivu.']),
              pole_text('rok', 'Rok', (string)($form['rok'] ?? ''), ['maxlength' => 4, 'placeholder' => date('Y'), 'attrs' => ['inputmode' => 'numeric', 'pattern' => '[0-9]{4}']]),
            ]) ?>
        <datalist id="archiv-turnaje"><?php foreach ($turnaje as $t): ?><option value="<?= e((string)$t) ?>"></option><?php endforeach; ?></datalist>
        <?= pole_pdf('soubor', 'Soubor PDF', $jeArchiv ? $form['soubor'] : '', $jeArchiv ? $form['soubor_nazev'] : '',
              ['hint' => 'Jen PDF, nejvýš ' . e($limit) . '. Nové PDF nahradí to současné; počet stran se zjistí sám.'
                       . ($jeArchiv && (int)($form['stran'] ?? 0) > 0 ? ' Teď: ' . e(dokument_soubor_info($form)) . '.' : '')]) ?>
      </fieldset>

      <details class="rozbal-admin"<?= trim((string)$form['url']) !== '' ? ' open' : '' ?>>
        <summary>Dokument leží na jiném webu (odkaz místo textu)</summary>
        <?= pole_text('url', 'Odkaz na dokument jinde', $form['url'], ['maxlength' => 255, 'attrs' => ['inputmode' => 'url'], 'placeholder' => 'https://…',
              'hint' => 'Jen výjimečně – použije se, když dokument nemá text (ani PDF v archivu).']) ?>
      </details>

      <fieldset>
        <legend>Patička webu</legend>
        <?= pole_check('v_paticce', 'Ukázat v patičce ve sloupci „Dokumenty a sítě“', (int)$form['v_paticce'] === 1,
              ['attrs' => ['data-odkryva' => 'pole-paticka']]) ?>
        <div id="pole-paticka">
          <?= pole_text('paticka_text', 'Text odkazu v patičce', $form['paticka_text'], ['maxlength' => 120, 'placeholder' => 'Stanovy klubu',
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
$klubove = array_values(array_filter($radky, fn($d) => (string)$d['kategorie'] !== DOKUMENTY_ARCHIV));
$archivRadky = array_values(array_filter($radky, fn($d) => (string)$d['kategorie'] === DOKUMENTY_ARCHIV));
$vPaticce = array_values(array_filter($radky, fn($d) => (int)$d['visible'] === 1 && (int)$d['v_paticce'] === 1));
$druh = static fn(array $d): string => dokument_ma_text($d) ? 'stránka s textem' : ((string)$d['soubor'] !== '' ? 'PDF' : 'odkaz');

/* archiv po turnajích a ročnících (i skryté přílohy) */
$archiv = [];
foreach ($archivRadky as $d) $archiv[(string)$d['skupina'] ?: 'bez turnaje'][(int)$d['rok']][] = $d;
foreach ($archiv as &$roky) krsort($roky);
unset($roky);
uasort($archiv, static fn($a, $b) => max(array_keys($b)) <=> max(array_keys($a)));

admin_head('Dokumenty', $user, [
    'podnadpis' => 'Stanovy, pravidla, provozní řády, ceník – jako stránky webu v klubovém stylu (jdou vytisknout). PDF jen v archivu klubových turnajů. Pořadí šipkami platí na stránkách i v patičce.',
    'akce'      => '<a class="btn btn-primary" href="' . DO_STRANKA . '?nova=1">Přidat dokument</a> <a class="btn btn-ghost" href="' . DO_STRANKA . '?nova=archiv">Přidat přílohu do archivu</a>',
]);
echo $css;
?>

<section class="panel">
  <div class="panel-head"><h2>V patičce webu <small><?= count($vPaticce) ?></small></h2><span class="hint">Sloupec „Dokumenty a sítě“ (pod nimi vždy odkaz „Všechny dokumenty“).</span></div>
  <?php if ($vPaticce): ?>
      <ul class="seznam-radku">
        <?php foreach ($vPaticce as $d): $u = dokument_url($d); ?>
          <li><span><b><?= e($d['paticka_text'] !== '' ? $d['paticka_text'] : $d['nazev']) ?></b><br>
            <small><?= e($druh($d)) ?> · <?php if ($u !== ''): ?><a href="<?= e($u) ?>" target="_blank" rel="noopener">otevřít</a><?php else: ?>bez adresy<?php endif; ?></small></span></li>
        <?php endforeach; ?>
      </ul>
  <?php else: ?>
    <div class="panel-body"><p class="hint">V patičce teď není žádný dokument. Zaškrtněte u dokumentu „Ukázat v patičce“.</p></div>
  <?php endif; ?>
</section>

<section class="panel">
  <div class="panel-head">
    <h2>Dokumenty klubu <small><?= count($klubove) ?></small></h2>
    <a class="btn btn-sm btn-ghost" href="<?= e(url('dokumenty.php')) ?>" target="_blank" rel="noopener">Stránka Dokumenty <span aria-hidden="true">↗</span></a>
  </div>
  <?php if (!$klubove): ?>
    <?= prazdny_stav('Zatím žádný dokument', 'Přidejte první dokument – třeba stanovy nebo pravidla hraní.',
          '<a class="btn btn-primary" href="' . DO_STRANKA . '?nova=1">Přidat dokument</a>') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Název</th><th>Kategorie</th><th>Na webu</th><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($klubove as $i => $d):
            $id = (int)$d['id']; $soubor = dokument_soubor_url($d); ?>
          <tr id="d<?= $id ?>"<?= (int)$d['visible'] === 1 ? '' : ' class="je-skryte"' ?>>
            <td data-label="Název" class="td-nazev"><b><?= e($d['nazev']) ?></b><?php if ((string)$d['popis'] !== ''): ?><small><?= e($d['popis']) ?></small><?php endif; ?></td>
            <td data-label="Kategorie" class="tlumene"><?= e(DO_KATEGORIE[$d['kategorie']] ?? $d['kategorie']) ?></td>
            <td data-label="Na webu" class="tlumene uv-cesta">
              <?php if (dokument_ma_text($d)): ?>
                <?= badge('Stránka', 'zlato') ?> <a href="<?= e(dokument_url($d)) ?>" target="_blank" rel="noopener">dokument.php?d=<?= e((string)$d['slug']) ?></a>
                <?php if ((string)$d['soubor'] !== ''): ?><br><?= badge('dřívější PDF', 'warn') ?> <small>na webu se nenabízí – můžete ho odebrat</small><?php endif; ?>
              <?php elseif ($soubor !== ''): ?>
                <?= badge((string)$d['soubor'] !== '' ? 'PDF' : 'Odkaz', 'info') ?> <a href="<?= e($soubor) ?>" target="_blank" rel="noopener"><?= e(uryvek(preg_replace('~^https?://~i', '', (string)($d['url'] ?: $d['soubor'])), 40)) ?></a>
                <br><small>Bez textu – doplňte text, ať je dokument stránkou webu.</small>
              <?php else: ?>
                <?= badge('nikam nevede', 'err') ?>
              <?php endif; ?>
            </td>
            <td data-label="Stav"><?= stav_badge($d['visible']) ?><?= (int)$d['v_paticce'] === 1 ? ' ' . badge('V patičce', 'zlato') : '' ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitka_poradi($id, $i === 0, $i === count($klubove) - 1) ?>
              <a class="btn btn-sm btn-ghost" href="<?= DO_STRANKA ?>?id=<?= $id ?>">Upravit</a>
              <?= tlacitko_prepnout($id, $d['visible']) ?>
              <?= tlacitko_smazat($id, 'Smazat dokument „' . $d['nazev'] . '“? Nejde to vrátit – když ho chcete jen schovat, použijte Skrýt.') ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="panel">
  <div class="panel-head">
    <h2>Archiv klubových turnajů <small><?= count($archivRadky) ?> PDF</small></h2>
    <div class="btn-row">
      <a class="btn btn-sm btn-primary" href="<?= DO_STRANKA ?>?nova=archiv">Přidat přílohu</a>
      <a class="btn btn-sm btn-ghost" href="<?= e(url('dokumenty.php#archiv-turnaju')) ?>" target="_blank" rel="noopener">Archiv na webu <span aria-hidden="true">↗</span></a>
    </div>
  </div>
  <div class="panel-body"><p class="hint">Pozvánky, rozlosování a výsledky jako PDF (jen PDF, nejvýš <?= e(velikost_text(upload_limit_bajtu())) ?>). Na webu po turnajích a ročnících, nejnovější nahoře. V rozlosování nezveřejňujte soukromé telefony a e-maily hráčů.</p></div>
  <?php if (!$archiv): ?>
    <?= prazdny_stav('Archiv je prázdný', 'Nahrajte první PDF – pozvánku nebo výsledky turnaje.', '<a class="btn btn-primary" href="' . DO_STRANKA . '?nova=archiv">Přidat přílohu</a>') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Turnaj a rok</th><th>Příloha</th><th>Soubor</th><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($archiv as $turnaj => $roky): foreach ($roky as $rok => $docs): foreach ($docs as $j => $d):
            $id = (int)$d['id'];
            $chybi = (string)$d['soubor'] !== '' && !is_file(UPLOAD_DIR . '/' . $d['soubor']); ?>
          <tr id="d<?= $id ?>"<?= (int)$d['visible'] === 1 ? '' : ' class="je-skryte"' ?>>
            <td data-label="Turnaj a rok" class="td-mala"><b><?= e($turnaj) ?></b> <?= $rok > 0 ? (int)$rok : '' ?></td>
            <td data-label="Příloha" class="td-nazev"><b><?= e(dokument_archiv_nazev($d)) ?></b><?php if ((string)$d['popis'] !== ''): ?><small><?= e($d['popis']) ?></small><?php endif; ?></td>
            <td data-label="Soubor" class="tlumene uv-cesta">
              <?php if ((string)$d['soubor'] !== '' && !$chybi): ?><a href="<?= e(upload_url((string)$d['soubor'])) ?>" target="_blank" rel="noopener"><?= e(basename((string)$d['soubor'])) ?></a> <small><?= e(dokument_soubor_info($d)) ?></small>
              <?php elseif ($chybi): ?><?= badge('soubor chybí', 'err') ?>
              <?php elseif ((string)$d['url'] !== ''): ?><?= badge('Odkaz', 'info') ?>
              <?php else: ?><?= badge('nikam nevede', 'err') ?><?php endif; ?>
            </td>
            <td data-label="Stav"><?= stav_badge($d['visible']) ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitka_poradi($id, $j === 0, $j === count($docs) - 1) ?>
              <a class="btn btn-sm btn-ghost" href="<?= DO_STRANKA ?>?id=<?= $id ?>">Upravit</a>
              <?= tlacitko_prepnout($id, $d['visible']) ?>
              <?= tlacitko_smazat($id, 'Smazat přílohu „' . $d['nazev'] . '“ i s PDF? Nejde to vrátit – když ji chcete jen schovat, použijte Skrýt.') ?>
            </div></td>
          </tr>
        <?php endforeach; endforeach; endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php admin_foot(); ?>
