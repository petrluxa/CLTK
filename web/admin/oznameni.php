<?php
/* Informační lišta – zlatý pruh pod menu na celém webu (ZADANI §4.2).
   Zpráva má text, volitelný odkaz, platnost od–do a vypínač. Když dnes
   neplatí žádná zapnutá zpráva, lišta se na webu nevypíše vůbec
   (aktivni_oznameni() v inc/data.php). Při více platných zprávách
   rozhoduje pořadí. */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const OZ_STRANKA = 'oznameni.php';

/** Stav zprávy k dnešku: [text štítku, druh štítku]. Stejné pravidlo jako aktivni_oznameni(). */
function oz_stav(array $r, string $dnes): array {
    $od = substr((string)$r['plati_od'], 0, 10);
    $do = substr((string)$r['plati_do'], 0, 10);
    if ((int)$r['visible'] !== 1) return ['Vypnuto', 'off'];
    if (trim((string)$r['text']) === '') return ['Bez textu', 'warn'];
    if ($od !== '' && $od > $dnes) return ['Naplánováno', 'info'];
    if ($do !== '' && $do < $dnes) return ['Prošlé', 'off'];
    return ['Na webu', 'ok'];
}

/** Platnost lidsky: „1. 10. – 8. 10. 2026“, „od 1. 10. 2026“, „bez omezení“. */
function oz_platnost(array $r): string {
    $od = substr((string)$r['plati_od'], 0, 10);
    $do = substr((string)$r['plati_do'], 0, 10);
    if ($od !== '' && $do !== '') return cz_range($od, $do);
    if ($od !== '') return 'od ' . cz_date($od);
    if ($do !== '') return 'do ' . cz_date($do);
    return 'bez omezení';
}

/** Odkaz z formuláře: [hodnota pro DB, chyba]. Povolí https://…, mailto:, tel:, #kotvu a stránku webu. */
function oz_odkaz(string $vstup): array {
    $u = trim($vstup);
    if ($u === '') return ['', ''];
    $holy = (string)preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($u, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $holy) && !preg_match('~^(https?:|mailto:|tel:)~i', $holy)) {
        return [$u, 'Odkaz musí vést na web (https://…), e-mail (mailto:…), telefon (tel:…) nebo na stránku tohoto webu (např. restaurace.php).'];
    }
    $n = normalizuj_url($u);
    if (preg_match('~^https?://~i', $n)) {
        $host = (string)parse_url($n, PHP_URL_HOST);
        if (preg_match('~\s~u', $n) || !preg_match('/^[\p{L}\p{N}.\-]+\.\p{L}{2,}$/u', $host)) {
            return [$u, 'Odkaz nevypadá jako adresa webu. Zapište ho celý (zkopírujte z prohlížeče https://…), nebo jako stránku webu (restaurace.php).'];
        }
    }
    if (bezpecny_odkaz($n) === '') return [$u, 'Tento odkaz nejde použít.'];
    return [$n, ''];
}

$dnes = dnes();
$chyby = [];
$form = null;          // řádek pro formulář (při chybě s odeslanými hodnotami)

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek(OZ_STRANKA);
    $akce = vstup('action', 20);
    $id   = (int)vstup_int('id');

    if ($akce === 'prepnout') {
        $r = row('SELECT * FROM cltk_oznameni WHERE id = ?', [$id]);
        if (!$r) redirect(OZ_STRANKA, 'Zpráva nebyla nalezena.', 'err');
        $novy = admin_prepni('cltk_oznameni', $id);
        redirect(OZ_STRANKA, $novy ? 'Zpráva je zapnutá – na webu se ukáže v době platnosti.' : 'Zpráva je vypnutá, na webu se neukazuje.');
    }
    if ($akce === 'nahoru' || $akce === 'dolu') {
        admin_posun('cltk_oznameni', $id, $akce === 'nahoru' ? -1 : 1);
        redirect(OZ_STRANKA, 'Pořadí bylo změněno.');
    }
    if ($akce === 'smazat') {
        $r = row('SELECT * FROM cltk_oznameni WHERE id = ?', [$id]);
        if (!$r) redirect(OZ_STRANKA, 'Zpráva už neexistuje.', 'err');
        q('DELETE FROM cltk_oznameni WHERE id = ?', [$id]);
        redirect(OZ_STRANKA, 'Zpráva byla smazána.');
    }
    if ($akce === 'ulozit') {
        $stary = $id ? row('SELECT * FROM cltk_oznameni WHERE id = ?', [$id]) : null;
        if ($id && !$stary) redirect(OZ_STRANKA, 'Zpráva mezitím zmizela – možná ji někdo smazal.', 'err');

        $text = vstup('text', 255);
        [$odkaz, $chOdkaz] = oz_odkaz(vstup('odkaz', 255));
        $odRaw = vstup('plati_od', 20);
        $doRaw = vstup('plati_do', 20);
        $od = normalizuj_datum($odRaw);
        $do = normalizuj_datum($doRaw);
        $visible = vstup_bool('visible');

        if ($text === '') $chyby[] = 'Vyplňte text zprávy.';
        if ($chOdkaz !== '') $chyby[] = $chOdkaz;
        if ($od === false) $chyby[] = 'Datum „platí od“ nerozumím. Zapište ho jako 1. 10. 2026.';
        if ($do === false) $chyby[] = 'Datum „platí do“ nerozumím. Zapište ho jako 8. 10. 2026.';
        if ($od && $do && $do < $od) $chyby[] = 'Konec platnosti nemůže být dřív než začátek.';

        $form = [
            'id' => $id, 'text' => $text, 'odkaz' => $odkaz,
            'plati_od' => $od === false ? $odRaw : (string)$od, 'plati_do' => $do === false ? $doRaw : (string)$do,
            'visible' => $visible,
        ];

        if (!$chyby) {
            $data = ['text' => $text, 'odkaz' => $odkaz, 'plati_od' => $od ?: null, 'plati_do' => $do ?: null,
                     'visible' => $visible, 'updated_at' => ted()];
            if ($stary) {
                db_update('cltk_oznameni', $id, $data);
            } else {
                $data['poradi'] = admin_dalsi_poradi('cltk_oznameni');
                $data['created_at'] = ted();
                $id = db_insert('cltk_oznameni', $data);
            }
            [$stav] = oz_stav(row('SELECT * FROM cltk_oznameni WHERE id = ?', [$id]) ?? $data, $dnes);
            $kdy = match ($stav) {
                'Na webu'     => 'Lišta se na webu zobrazuje.',
                'Naplánováno' => 'Na webu se ukáže ' . cz_date((string)$od) . '.',
                'Prošlé'      => 'Platnost už skončila, na webu se neukáže.',
                default       => 'Zpráva je vypnutá, na webu se neukáže.',
            };
            redirect(OZ_STRANKA, 'Zpráva uložena. ' . $kdy);
        }
    }
    if (!$chyby) redirect(OZ_STRANKA, 'Neznámý požadavek.', 'err');
}

/* --- formulář (nová / úprava) --- */
$upravit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($form === null && $upravit > 0) {
    $form = row('SELECT * FROM cltk_oznameni WHERE id = ?', [$upravit]);
    if (!$form) redirect(OZ_STRANKA, 'Zpráva nebyla nalezena.', 'err');
}
if ($form === null && isset($_GET['nova'])) {
    $form = ['id' => 0, 'text' => '', 'odkaz' => '', 'plati_od' => $dnes, 'plati_do' => '', 'visible' => 1];
}

$css = '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';
$js  = '<script src="' . e(BASE_PATH . verze('admin/assets/admin-uvod.js')) . '" defer></script>';

if ($form !== null):
    $nova = (int)$form['id'] === 0;
    admin_head($nova ? 'Nová zpráva lišty' : 'Úprava zprávy lišty', $user, [
        'zpet' => [OZ_STRANKA, 'Informační lišta'], 'sirka' => 'uzka',
        'podnadpis' => 'Zlatý pruh pod menu na všech stránkách webu. Text se na webu vysází prostrkanými verzálkami.',
    ]);
    echo $css;
?>
<?php if ($chyby): ?>
  <ul class="uv-chyby" role="alert"><li><b>Zprávu se nepodařilo uložit:</b></li><?php foreach ($chyby as $ch): ?><li><?= e($ch) ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<section class="panel">
  <div class="panel-head"><h2>Náhled</h2><span class="hint">Takhle bude lišta vypadat pod menu.</span></div>
  <div class="panel-body">
    <div class="uv-lista" data-zrcadlo="f-text" data-zrcadlo-prazdne="Text zprávy…"><?= $form['text'] !== '' ? e($form['text']) : 'Text zprávy…' ?></div>
  </div>
</section>

<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
      <?= pole_text('text', 'Text zprávy', $form['text'], ['required' => true, 'maxlength' => 255,
            'placeholder' => 'Klubová restaurace je uzavřena od 1. 10. do 8. 10.',
            'attrs' => ['data-pocitadlo' => '90'],
            'hint' => 'Krátce, jednou větou – na telefonu se delší text zalomí do více řádků. Velká písmena doplní web sám, pište normálně.']) ?>
      <?= pole_text('odkaz', 'Odkaz (nepovinné)', $form['odkaz'], ['maxlength' => 255, 'attrs' => ['inputmode' => 'url'],
            'placeholder' => 'restaurace.php nebo https://…',
            'hint' => 'Když vyplníte, celá lišta bude odkazem – na stránku tohoto webu (např. <b>restaurace.php</b>) nebo jinam (<b>https://…</b>, nové okno).']) ?>
      <?= pole_radek([
            pole_datum('plati_od', 'Platí od', (string)$form['plati_od'], ['hint' => 'Prázdné = platí hned.']),
            pole_datum('plati_do', 'Platí do (včetně)', (string)$form['plati_do'], ['hint' => 'Prázdné = bez konce. Po tomto dni lišta sama zmizí.']),
          ]) ?>
      <?= pole_check('visible', 'Zpráva je zapnutá', (int)$form['visible'] === 1,
            ['hint' => 'Vypnutá zpráva zůstane tady v administraci, na webu se neukáže ani v době platnosti.']) ?>
      <?= tlacitka_formulare($nova ? 'Přidat zprávu' : 'Uložit změny', OZ_STRANKA, 'Zpět na seznam') ?>
    </form>
  </div>
</section>
<?php
    echo $js;
    admin_foot();
    exit;
endif;

/* --- seznam --- */
$radky = rows('SELECT * FROM cltk_oznameni ORDER BY poradi, id');
$aktivni = aktivni_oznameni();

admin_head('Informační lišta', $user, [
    'podnadpis' => 'Zlatý pruh s krátkou zprávou pod menu na všech stránkách. Když dnes neplatí žádná zapnutá zpráva, lišta se nezobrazí vůbec.',
    'akce'      => '<a class="btn btn-primary" href="' . OZ_STRANKA . '?nova=1">Přidat zprávu</a>',
]);
echo $css;
?>

<section class="panel">
  <div class="panel-head">
    <h2>Na webu právě teď</h2>
    <span class="hint"><?= e(cz_date_dlouze($dnes)) ?></span>
  </div>
  <div class="panel-body">
    <?php if ($aktivni): ?>
      <div class="uv-lista"><?= e($aktivni[0]['text']) ?><?php if ((string)$aktivni[0]['odkaz'] !== ''): ?> <span class="uv-lista__odkaz">→</span><?php endif; ?></div>
      <?php if (count($aktivni) > 1): ?>
        <p class="hint">Dnes platí <?= count($aktivni) ?> zprávy – web ukazuje tu první podle pořadí v seznamu. Ostatní se ukážou, až první přestane platit.</p>
      <?php endif; ?>
    <?php else: ?>
      <div class="uv-lista uv-lista--prazdna">Lišta se na webu teď nezobrazuje – dnes neplatí žádná zapnutá zpráva.</div>
    <?php endif; ?>
  </div>
</section>

<section class="panel">
  <div class="panel-head"><h2>Zprávy <small><?= count($radky) ?></small></h2></div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím žádná zpráva', 'Přidejte první zprávu – třeba o uzavření restaurace nebo začátku halové sezóny.',
          '<a class="btn btn-primary" href="' . OZ_STRANKA . '?nova=1">Přidat zprávu</a>') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Text zprávy</th><th>Platnost</th><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($radky as $i => $r):
            [$stav, $druh] = oz_stav($r, $dnes);
            $id = (int)$r['id']; ?>
          <tr<?= $druh === 'ok' ? '' : ' class="je-skryte"' ?>>
            <td data-label="Text zprávy" class="td-nazev">
              <span class="uv-lista-tabulka"><?= e($r['text']) ?></span>
              <?php if ((string)$r['odkaz'] !== ''): ?><small>odkaz: <?= e($r['odkaz']) ?></small><?php endif; ?>
            </td>
            <td data-label="Platnost" class="uv-nowrap"><?= e(oz_platnost($r)) ?></td>
            <td data-label="Stav"><?= badge($stav, $druh) ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitka_poradi($id, $i === 0, $i === count($radky) - 1) ?>
              <a class="btn btn-sm btn-ghost" href="<?= OZ_STRANKA ?>?id=<?= $id ?>">Upravit</a>
              <?= (int)$r['visible'] === 1
                    ? tlacitko_akce('prepnout', $id, 'Vypnout', 'btn-ghost', [], '', 'Vypnout zprávu')
                    : tlacitko_akce('prepnout', $id, 'Zapnout', 'btn-ghost', [], '', 'Zapnout zprávu') ?>
              <?= tlacitko_smazat($id, 'Smazat zprávu „' . $r['text'] . '“? Nejde to vrátit – když ji chcete jen schovat, použijte Vypnout.') ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<p class="hint">Tip: zprávu nemusíte po skončení mazat – po datu „platí do“ zmizí z webu sama a příště ji jen upravíte.</p>

<?php admin_foot(); ?>
