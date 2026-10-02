<?php
/* Areál a služby – rozcestník služeb, otevírací doby a uzávěrky, plán areálu
   Léto / Zima (mapa.js podle návrhu 3), všechny služby s kotvami pro štítky
   „Služby v areálu“ z úvodu (areal.php#bazen …) a příjezd.
   Obsah: moduly Areál a služby (cltk_sluzby), Stránky (bloky „areal“),
   Ceníky (kurty léto / zima – ceny a zimní haly na plánu), Dokumenty (provoz)
   a Texty a údaje (adresa, mapa). Natvrdo jsou jen popisky struktury. */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
track_visit();

if (!function_exists('radky_seznam_typo')) {
    /** Jako radky_seznam() (co řádek, to položka), ale s typografií – nezlomitelná mezera po předložkách. */
    function radky_seznam_typo(?string $s, string $trida = ''): string {
        $r = radky($s);
        if (!$r) return '';
        return '<ul' . ($trida !== '' ? ' class="' . e($trida) . '"' : '') . '>'
             . implode('', array_map(fn($x) => '<li>' . typo($x) . '</li>', $r)) . '</ul>';
    }
}
/* ---------- data ---------- */
$uvod     = blok('areal', 'uvod');
$bCasy    = blok('areal', 'casy');
$bUzav    = blok('areal', 'uzavirky');
$bPlan    = blok('areal', 'plan');
$bKurty   = blok('areal', 'kurty');
$bSluzby  = blok('areal', 'sluzby');
$bPrijezd = blok('areal', 'prijezd');
$bLetoF   = blok('areal', 'letecky-leto');
$bZimaF   = blok('areal', 'letecky-zima');

$sluzby     = sluzby();
$provozDoky = dokumenty('provoz');
$cenikLeto  = cenik('kurty-leto');
$cenikZima  = cenik('kurty-zima');


/**
 * Uzávěrky z bloku areal/uzavirky: co položka seznamu, to uzávěrka
 * („<strong>16.–22. 8. 2026</strong> důvod“). Vrací položky se stavem podle
 * dnešního data: 'ted' (právě platí), 'prijde', 'probehla', '' (bez data).
 * HTML jde z html_ocistit() (holé povolené značky), takže ho lze rozebrat
 * a části znovu vypsat.
 */
function areal_uzavirky(string $html): array {
    $html = html_ocistit($html);
    if ($html === '' || stripos($html, '<li>') === false) return [];
    $doc = new DOMDocument('1.0', 'UTF-8');
    $stav = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body>' . $html . '</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($stav);
    $dnes = dnes();
    $vysledek = [];
    foreach ($doc->getElementsByTagName('li') as $li) {
        $kdy = '';
        $co = '';
        foreach ($li->childNodes as $uzel) {
            if ($kdy === '' && $uzel instanceof DOMElement && in_array(strtolower($uzel->nodeName), ['strong', 'b'], true)) {
                foreach ($uzel->childNodes as $x) $kdy .= $doc->saveHTML($x);
                continue;
            }
            $co .= $doc->saveHTML($uzel);
        }
        $co = trim((string)preg_replace('/^(\s|&nbsp;|[–:-])+/u', '', $co));
        [$od, $do] = areal_obdobi(html_text($kdy !== '' ? $kdy : $co));
        $s = '';
        if ($do !== null) $s = $do < $dnes ? 'probehla' : ($od !== null && $od <= $dnes ? 'ted' : 'prijde');
        $vysledek[] = ['kdy' => trim($kdy), 'co' => $co, 'od' => $od, 'do' => $do, 'stav' => $s];
    }
    return $vysledek;
}


/* Uzávěrky: platné a nadcházející nahoře, proběhlé schované v rozbalovacím bloku */
$uzavirky = areal_uzavirky((string)$bUzav['text']);
$uzavAktualni = array_values(array_filter($uzavirky, fn($u) => $u['stav'] !== 'probehla'));
$uzavProbehle = array_reverse(array_values(array_filter($uzavirky, fn($u) => $u['stav'] === 'probehla')));
usort($uzavAktualni, fn($a, $b) => [$a['od'] ?? '9999', $a['do'] ?? '9999'] <=> [$b['od'] ?? '9999', $b['do'] ?? '9999']);

/* Otevírací doby: služby s vyplněnými časy */
$sCasy = array_values(array_filter($sluzby, fn($s) => trim((string)$s['casy']) !== ''));
$bezCasu = count($sluzby) - count($sCasy);

/* Názvy stránek webu pro odkazy služeb („sportovni-lekarstvi.php“ → „Sportovní lékařství“) */
$nazvyStranek = ['kontakt.php' => 'Kontakt'];
foreach (menu_hlavni() as $m) {
    if (!$m['externi'] && $m['soubor'] !== '') $nazvyStranek[$m['soubor']] = $m['nazev'];
    foreach ($m['podmenu'] as $p) $nazvyStranek[$p['soubor']] = $p['nazev'];
}
$textOdkazu = static function (string $odkaz) use ($nazvyStranek): string {
    $soubor = strtolower((string)preg_replace('~[?#].*$~', '', $odkaz));
    if (isset($nazvyStranek[$soubor])) return $nazvyStranek[$soubor];
    return odkaz_je_externi($odkaz) ? 'Web služby' : 'Více informací';
};

$sezona = areal_sezona($cenikZima);
$planFoto = (string)$bPlan['foto'];
$maPlan = $planFoto !== '' && is_file(UPLOAD_DIR . '/' . $planFoto);
$fotoLeto = (string)$bLetoF['foto'];
$fotoZima = (string)$bZimaF['foto'];
$maLetecky = $fotoLeto !== '' && is_file(UPLOAD_DIR . '/' . $fotoLeto);
$maLeteckyZima = $maLetecky && $fotoZima !== '' && is_file(UPLOAD_DIR . '/' . $fotoZima);

$mapaData = mapa_data($cenikLeto, $cenikZima, $sluzby);

$mapaUrl = bezpecny_odkaz(setting('mapa_url'));
$planPdf = bezpecny_odkaz((string)$bPlan['odkaz']);

$sablona = [
    'titulek' => html_text((string)$uvod['stitek']) ?: 'Areál a služby',
    'popis'   => html_text((string)$uvod['perex']) . ' ' . implode(', ', array_map(fn($s) => (string)$s['nazev'], array_slice($sluzby, 0, 8))) . '.',
    'css'     => ['stranky-areal.css', 'mapa.css'],
    'js'      => ['cenik.js', 'mapa.js'],
    'trida'   => 'stranka-areal',
    'obrazek' => $maLetecky ? $fotoLeto : '',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvod, ['drobky' => [['Areál a služby']], 'nadpis' => 'Areál a služby']) ?>

<?php if ($sluzby): ?>
<nav class="rozcestnik" aria-label="Služby v areálu">
  <div class="wrap rozcestnik__vnitrek">
    <p class="stitek"><?= typo(trim((string)$bSluzby['stitek']) ?: 'Služby v areálu') ?></p>
    <?= pilulky_html(array_map(fn($s) => [(string)$s['nazev'], '#' . $s['kotva']], $sluzby)) ?>
  </div>
</nav>
<?php endif; ?>

<!-- I · Otevírací doby a uzávěrky -->
<section class="sekce" id="provoz" aria-labelledby="casy-nadpis">
  <div class="wrap provoz">
    <div>
      <?= hlava_sekce($bCasy, ['cislo' => 1, 'id' => 'casy-nadpis', 'tag' => 'h2', 'nadpis' => 'Otevírací doby']) ?>
      <?php if ($sCasy): ?>
      <ul class="radky">
        <?php foreach ($sCasy as $s): ?>
        <li class="radek radek--vodici"><span class="radek__nazev"><a href="#<?= e($s['kotva']) ?>"><?= typo((string)$s['nazev']) ?></a></span><span class="radek__hodnota"><?= typo((string)$s['casy']) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <?php if ($bezCasu > 0): ?>
      <p class="provoz__dalsi">Časy dalších služeb <?= doplni_klub() ?></p>
      <?php endif; ?>
    </div>

    <div>
      <?= hlava_sekce($bUzav, ['id' => 'uzavirky-nadpis', 'tag' => 'h2', 'nadpis' => 'Plánované uzávěrky', 'bez_znacky' => true]) ?>
      <?php if ($uzavirky): ?>
        <?php if ($uzavAktualni): ?>
        <ul class="uzavirky" aria-labelledby="uzavirky-nadpis">
          <?php foreach ($uzavAktualni as $u): ?>
          <li class="uzavirka<?= $u['stav'] === 'ted' ? ' uzavirka--ted' : '' ?>">
            <p class="uzavirka__kdy"><?= $u['kdy'] ?><?php if ($u['stav'] === 'ted'): ?><span class="stitek">právě teď</span><?php endif; ?></p>
            <div class="uzavirka__co"><?= $u['co'] ?></div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="drobne tlumene">Další uzávěrky zatím nejsou zveřejněné.</p>
        <?php endif; ?>
        <?php if ($uzavProbehle): ?>
        <details class="rozbal rozbal--male uzavirky-probehle">
          <summary>Proběhlé uzávěrky (<?= count($uzavProbehle) ?>)</summary>
          <div class="rozbal__obsah">
            <ul class="uzavirky">
              <?php foreach ($uzavProbehle as $u): ?>
              <li class="uzavirka uzavirka--probehla"><p class="uzavirka__kdy"><?= $u['kdy'] ?></p><div class="uzavirka__co"><?= $u['co'] ?></div></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </details>
        <?php endif; ?>
      <?php else: ?>
        <?= blok_text($bUzav) ?: '<p class="drobne tlumene">Uzávěrky zatím nejsou zveřejněné.</p>' ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- II · Plán areálu Léto / Zima -->
<section class="sekce sekce--papir2" id="plan" aria-labelledby="plan-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bPlan, ['cislo' => 2, 'id' => 'plan-nadpis', 'radek' => true, 'nadpis' => 'Plán areálu']) ?>

    <div class="mapa" data-mapa data-rezim="<?= e($sezona) ?>">
      <?php if ($maPlan): ?>
      <figure class="mapa__zaloha ramec">
        <div class="ramec__obraz"><?= obr($planFoto, html_text((string)$bPlan['perex']) ?: 'Plán areálu', ['class' => 'foto']) ?></div>
        <?php if (trim((string)$bPlan['foto_popisek']) !== ''): ?><figcaption class="popisek"><span><?= html_inline((string)$bPlan['foto_popisek']) ?></span></figcaption><?php endif; ?>
      </figure>
      <?php endif; ?>
    </div>
    <?= json_skript('mapa-data', $mapaData) ?>

    <div class="plan-pod mrizka">
      <div class="sl-7">
        <?php if (trim((string)$bKurty['nadpis']) !== ''): ?><h3 class="h3"><?= html_inline((string)$bKurty['nadpis']) ?></h3><?php endif; ?>
        <?= blok_text($bKurty) ?>
        <div class="odkazy-radek">
          <?= tlacitko('cenik-kurtu.php', 'Ceník kurtů', 'odkaz') ?>
          <?= $planPdf !== '' ? tlacitko($planPdf, trim((string)$bPlan['odkaz_text']) ?: 'Plán areálu v PDF', 'odkaz') : '' ?>
          <?php if ($maPlan): ?><button class="odkaz" type="button" data-dialog-otevrit="d-plan" data-jen-js hidden>Plán areálu jako obrázek <?= sipka() ?></button><?php endif; ?>
        </div>
      </div>
      <?php if ($maLetecky): ?>
      <div class="sl-5 od-8">
        <figure class="letecky ramec ramec--linka" data-letecky data-rezim="<?= e($maLeteckyZima ? $sezona : 'leto') ?>">
          <div class="ramec__obraz">
            <?= obr($fotoLeto, html_text((string)$bLetoF['foto_popisek']) ?: 'Areál v létě shora', ['class' => 'foto letecky__leto']) ?>
            <?= $maLeteckyZima ? obr($fotoZima, html_text((string)$bZimaF['foto_popisek']) ?: 'Areál v zimě shora', ['class' => 'foto letecky__zima']) : '' ?>
          </div>
          <figcaption class="popisek">
            <span><span class="letecky__popis-leto"><?= html_inline((string)$bLetoF['foto_popisek']) ?></span><?php if ($maLeteckyZima): ?><span class="letecky__popis-zima"><?= html_inline((string)$bZimaF['foto_popisek']) ?></span><?php endif; ?></span>
            <?php if ($maLeteckyZima): ?><span class="letecky__prepni"><button type="button" data-mapa-prepni="leto" hidden>Léto</button><button type="button" data-mapa-prepni="zima" hidden>Zima</button></span><?php endif; ?>
          </figcaption>
        </figure>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php if ($maPlan): ?>
<dialog class="dialog plan-dialog" id="d-plan" aria-labelledby="d-plan-titul">
  <div class="dialog__hlava"><p class="stitek" id="d-plan-titul">Plán areálu</p><button class="dialog__zavrit" type="button" data-dialog-zavrit>Zavřít</button></div>
  <div class="dialog__obsah"><?= obr($planFoto, html_text((string)$bPlan['perex']) ?: 'Plán areálu', ['loading' => 'lazy']) ?></div>
</dialog>
<?php endif; ?>

<!-- III · Služby (kotvy = štítky „Služby v areálu“ z úvodu) -->
<section class="sekce" id="sluzby" aria-labelledby="sluzby-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bSluzby, ['cislo' => 3, 'id' => 'sluzby-nadpis', 'radek' => true, 'nadpis' => 'Služby v areálu']) ?>
    <?php if ($sluzby): ?>
    <ul class="sluzby-mrizka" role="list">
      <?php foreach ($sluzby as $i => $s):
        $foto = obr((string)$s['foto'], (string)$s['nazev'], ['class' => 'foto', 'fokus' => (string)$s['fokus']]);
        $odkaz = bezpecny_odkaz((string)$s['odkaz']); ?>
      <li class="sluzba" id="<?= e((string)$s['kotva']) ?>">
        <?php if ($foto !== ''): ?>
        <figure class="ramec ramec--linka sluzba__foto"><div class="ramec__obraz pomer-3x2"><?= $foto ?></div></figure>
        <?php else: ?>
        <div class="ramec ramec--linka sluzba__foto sluzba__foto--prazdne" aria-hidden="true"><div class="ramec__obraz pomer-3x2"><span class="sluzba__monogram"><?= e(mb_substr((string)$s['nazev'], 0, 1)) ?></span></div></div>
        <?php endif; ?>
        <p class="sluzba__znacka"><span class="sluzba__cislo"><?= e(rimske($i + 1)) ?></span></p>
        <h3 class="sluzba__nazev"><?= typo((string)$s['nazev']) ?></h3>
        <?php if (trim((string)$s['perex']) !== ''): ?><p class="sluzba__perex"><?= typo((string)$s['perex']) ?></p><?php endif; ?>
        <?php if (trim((string)$s['text']) !== ''): ?><div class="sluzba__text"><?= paragraphs((string)$s['text']) ?></div><?php endif; ?>
        <?= radky_seznam_typo((string)$s['fakta'], 'sluzba__fakta') ?>
        <div class="sluzba__pata">
          <p class="sluzba__casy"><span class="stitek">Časy</span><span><?= trim((string)$s['casy']) !== '' ? typo((string)$s['casy']) : doplni_klub() ?></span></p>
          <?php if ($odkaz !== ''): ?><p><?= tlacitko($odkaz, $textOdkazu((string)$s['odkaz']), 'odkaz') ?></p><?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p><?= doplni_klub('služby doplní klub') ?></p>
    <?php endif; ?>
  </div>
</section>

<!-- IV · Příjezd a provozní řády -->
<section class="sekce sekce--linka" id="prijezd" aria-labelledby="prijezd-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bPrijezd, ['cislo' => 4, 'id' => 'prijezd-nadpis', 'nadpis' => 'Jak se k nám dostanete']) ?>
    <div class="prijezd">
      <div>
        <address class="prijezd__adresa"><?= typo(setting('adresa_ulice')) ?><br><?= typo(setting('adresa_mesto')) ?><small><?= typo(setting('klub_nazev')) ?></small></address>
        <div class="odkazy-radek">
          <?= $mapaUrl !== '' ? tlacitko($mapaUrl, 'Mapa', 'odkaz') : '' ?>
          <?= tlacitko('kontakt.php#prijezd', 'Podrobný popis příjezdu a kontakty', 'odkaz') ?>
          <?= rezervace_url() !== '' ? tlacitko(rezervace_url(), 'Rezervovat kurt', 'odkaz') : '' ?>
        </div>
      </div>
      <?php if ($provozDoky): ?>
      <div>
        <h3 class="h5 mala-hlava">Provozní řády a&nbsp;pravidla</h3>
        <?= dokumenty_html($provozDoky) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
