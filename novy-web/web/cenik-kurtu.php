<?php
/* Ceník kurtů – letní a zimní ceník (záložky, výchozí podle dnešní sezóny),
   kalkulačka ceny s přepínačem „Jsem člen klubu“ (cenik.js, podle návrhu 3),
   pravidla rezervací a trvalých rezervací, doplňkové služby a rezervace.
   Obsah: modul Ceníky (kurty-leto, kurty-zima, doplnkove), Stránky (bloky
   „cenik-kurtu“), Dokumenty (ceníky v PDF, pravidla hraní) a Texty a údaje
   (rezervace, obsazenost, recepce). Natvrdo jsou jen popisky struktury.
   Adresa cenik-kurtu.php#zima / #leto otevře záložku, ?kurt=5#kalkulacka
   předvybere v kalkulačce halu s kurtem 5 (odkaz z plánu areálu). */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
track_visit();

/* ---------- data ---------- */
$uvod      = blok('cenik-kurtu', 'uvod');
$bCeniky   = blok('cenik-kurtu', 'ceniky');
$bKalk     = blok('cenik-kurtu', 'kalkulacka');
$bPravidla = blok('cenik-kurtu', 'pravidla');
$bDopl     = blok('cenik-kurtu', 'doplnkove');
$bRez      = blok('cenik-kurtu', 'rezervace');

$leto  = cenik('kurty-leto');
$zima  = cenik('kurty-zima');
$dopl  = cenik('doplnkove');

/** První dvě data „d. m. rrrr“ v textu → ['2026-09-28', '2027-04-04'] (stejné pravidlo jako cenik.js). */
function cenik_kurtu_data(string $t): array {
    preg_match_all('/(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})/u', str_replace("\u{00A0}", ' ', $t), $m, PREG_SET_ORDER);
    $v = [];
    foreach (array_slice($m, 0, 2) as $x) {
        if (checkdate((int)$x[2], (int)$x[1], (int)$x[3])) $v[] = sprintf('%04d-%02d-%02d', $x[3], $x[2], $x[1]);
    }
    return $v;
}

/**
 * Která sezóna je dnes: 'zima', když dnešek leží v období zimního ceníku
 * (pole Období, jinak nejmenší začátek – největší konec z popisů sekcí), jinak 'leto'.
 * Stejně počítá cenik.js (kalkulačka) i mapa.js (plán areálu).
 */
function cenik_kurtu_sezona(?array $leto, ?array $zima): string {
    if (!$zima || !$zima['sekce']) return 'leto';
    if (!$leto || !$leto['sekce']) return 'zima';
    $o = cenik_kurtu_data((string)$zima['list']['obdobi']);
    if (count($o) < 2) {
        $o = [];
        foreach ($zima['sekce'] as $s) {
            $x = cenik_kurtu_data((string)$s['popis']);
            if (count($x) < 2) continue;
            $o[0] = isset($o[0]) ? min($o[0], $x[0]) : $x[0];
            $o[1] = isset($o[1]) ? max($o[1], $x[1]) : $x[1];
        }
    }
    return count($o) === 2 && dnes() >= $o[0] && dnes() <= $o[1] ? 'zima' : 'leto';
}

/** Název záložky z názvu ceníku: „Kurty – zimní sezóna 2026/27“ → „Zimní sezóna 2026/27“. */
function cenik_kurtu_nazev(?array $c, string $vychozi): string {
    $n = trim((string)preg_replace('/^\s*kurty\s*[–-]\s*/iu', '', (string)($c['list']['nazev'] ?? '')));
    return $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) . mb_substr($n, 1) : $vychozi;
}

/** Slova textu (3+ písmena) pro porovnání vět. */
function cenik_kurtu_slova(string $s): array {
    preg_match_all('/[\p{L}\d]{3,}/u', mb_strtolower(html_text($s)), $m);
    return array_values(array_unique($m[0]));
}

/**
 * Poznámka ceníku bez vět, které už stojí v bloku Pravidla rezervací (zimní ceník
 * je z PDF klubu opakuje skoro doslova). Odstavec, jehož slova se z poloviny
 * a víc kryjí s některou položkou pravidel, se pod tabulkou nevypíše – zůstane
 * v pravidlech. Když klub text změní, ukáže se zase (nic se tiše neztratí).
 */
function cenik_kurtu_bez_opakovani(string $poznamka, string $pravidlaHtml): string {
    $pravidla = [];
    if (preg_match_all('~<(li|p)>(.*?)</\1>~su', html_ocistit($pravidlaHtml), $m)) {
        foreach ($m[2] as $x) $pravidla[] = cenik_kurtu_slova($x);
    }
    if (!$pravidla) return $poznamka;
    $zbyva = [];
    foreach (preg_split('/\n\s*\n/u', trim($poznamka)) as $odst) {
        $s = cenik_kurtu_slova($odst);
        $kryje = false;
        foreach ($pravidla as $p) {
            if ($s && $p && count(array_intersect($s, $p)) / min(count($s), count($p)) >= .5) { $kryje = true; break; }
        }
        if (!$kryje && trim($odst) !== '') $zbyva[] = trim($odst);
    }
    return implode("\n\n", $zbyva);
}

/** Ceník pro JavaScript – jen veřejné sloupce. */
function cenik_kurtu_js(?array $c): ?array {
    if (!$c) return null;
    $l = $c['list'];
    return [
        'list'  => ['nazev' => (string)$l['nazev'], 'obdobi' => (string)$l['obdobi'], 'poznamka_dole' => (string)$l['poznamka_dole']],
        'sekce' => array_map(fn($s) => [
            'id' => (int)$s['id'], 'nazev' => (string)$s['nazev'], 'popis' => (string)$s['popis'],
            'radky' => array_map(fn($r) => [
                'id' => (int)$r['id'], 'nazev' => (string)$r['nazev'], 'poznamka' => (string)$r['poznamka'],
                'cena' => (string)$r['cena'], 'cena_clen' => (string)$r['cena_clen'],
                'cena_sezona' => (string)$r['cena_sezona'], 'cena_sezona_clen' => (string)$r['cena_sezona_clen'],
            ], $s['radky']),
        ], $c['sekce']),
    ];
}

$sezona = cenik_kurtu_sezona($leto, $zima);
$pravidlaText = (string)$bPravidla['text'];

/* záložky v pořadí Léto · Zima; vybraná = dnešní sezóna */
$zalozky = [];
foreach ([['leto', $leto, 'Léto'], ['zima', $zima, 'Zima']] as [$klic, $c, $vychozi]) {
    if (!$c) continue;
    if ($bPravidla['existuje'] && trim(html_text($pravidlaText)) !== '') {
        $c['list']['poznamka_dole'] = cenik_kurtu_bez_opakovani((string)$c['list']['poznamka_dole'], $pravidlaText);
    }
    $zalozky[] = ['klic' => $klic, 'cenik' => $c, 'nazev' => cenik_kurtu_nazev($c, $vychozi)];
}
if ($zalozky && !in_array($sezona, array_column($zalozky, 'klic'), true)) $sezona = $zalozky[0]['klic'];

/* dokumenty: ceníky v PDF a pravidla hraní (kategorie provoz, název „Pravidla …“) */
$doky = array_merge(
    dokumenty('cenik'),
    array_values(array_filter(dokumenty('provoz'), fn($d) => mb_stripos((string)$d['nazev'], 'pravidl') !== false))
);

$rezervace  = rezervace_url();
$obsazenost = bezpecny_odkaz(setting('obsazenost_url'));
$recTel     = setting('recepce_telefon');
$recMail    = setting('recepce_email');
$maKalk     = ($leto && $leto['sekce']) || ($zima && $zima['sekce']);

/* Hlava: vedlejší odkaz bloku, když ho klub nevyplnil, = Rezervovat kurt (ceník je místo, kde dává smysl) */
$uvodHlava = $uvod;
if (trim((string)$uvodHlava['odkaz2']) === '' && $rezervace !== '') {
    $uvodHlava['odkaz2'] = $rezervace;
    $uvodHlava['odkaz2_text'] = 'Rezervovat kurt';
}

$kalkData = [
    'leto' => cenik_kurtu_js($leto), 'zima' => cenik_kurtu_js($zima),
    'dnes' => dnes(), 'ladeni' => sablona_ladeni_dnes() !== '',
    'rezervace' => $rezervace, 'telefon' => $recTel, 'telefon_text' => $recTel, 'cenik_url' => url('cenik-kurtu.php'),
];

$sablona = [
    'titulek' => html_text((string)$uvod['stitek']) ?: 'Ceník kurtů',
    'popis'   => trim(html_text((string)$uvod['perex']) . ' ' . implode(' · ', array_column($zalozky, 'nazev'))),
    'css'     => ['stranky-areal.css'],
    'js'      => $maKalk ? ['cenik.js'] : [],
    'trida'   => 'stranka-cenik',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvodHlava, ['drobky' => [['Areál a služby', 'areal.php'], ['Ceník kurtů']], 'nadpis' => 'Ceník kurtů']) ?>

<!-- I · Ceníky léto / zima -->
<section class="sekce sekce--bez-horni" id="ceniky" aria-labelledby="ceniky-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bCeniky, ['cislo' => 1, 'id' => 'ceniky-nadpis', 'nadpis' => 'Ceník kurtů']) ?>
    <?php if ($zalozky): ?>
    <div class="zalozky cenik-sezony" data-zalozky>
      <?php if (count($zalozky) > 1): ?>
      <div class="zalozky__seznam" role="tablist" aria-label="Sezóna ceníku">
        <?php foreach ($zalozky as $z): $vybrana = $z['klic'] === $sezona; ?>
        <button class="zalozka" type="button" role="tab" id="t-<?= e($z['klic']) ?>" aria-controls="p-<?= e($z['klic']) ?>" aria-selected="<?= $vybrana ? 'true' : 'false' ?>"<?= $vybrana ? '' : ' tabindex="-1"' ?> data-hash="<?= e($z['klic']) ?>"><?= typo($z['nazev']) ?></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php foreach ($zalozky as $z): $l = $z['cenik']['list']; $vybrana = $z['klic'] === $sezona; ?>
      <div class="zalozky__panel" role="tabpanel" id="p-<?= e($z['klic']) ?>" aria-labelledby="t-<?= e($z['klic']) ?>" tabindex="0"<?= $vybrana || count($zalozky) === 1 ? '' : ' hidden' ?>>
        <div class="cenik-sezony__hlava">
          <h3 class="cenik__nazev"><?= typo((string)$l['nazev']) ?></h3>
          <?php if (trim((string)$l['obdobi']) !== ''): ?><p class="cenik__obdobi"><?= typo((string)$l['obdobi']) ?></p><?php endif; ?>
          <?php if (trim((string)$l['podnazev']) !== ''): ?><p class="drobne"><?= typo((string)$l['podnazev']) ?></p><?php endif; ?>
        </div>
        <?= cenik_html($z['cenik'], ['nadpis' => false]) ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p><?= doplni_klub('ceník doplní klub') ?></p>
    <?php endif; ?>
    <div class="odkazy-radek">
      <?= tlacitko('#kalkulacka', 'Spočítat cenu', 'odkaz') ?>
      <?= tlacitko('#pravidla', 'Pravidla rezervací', 'odkaz') ?>
      <?= tlacitko('areal.php#plan', 'Kde který kurt najdete', 'odkaz') ?>
    </div>
  </div>
</section>

<?php if ($maKalk): ?>
<!-- II · Kalkulačka -->
<section class="sekce sekce--papir2" id="kalkulacka" aria-labelledby="kalk-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bKalk, ['cislo' => 2, 'id' => 'kalk-nadpis', 'radek' => true, 'nadpis' => 'Kalkulačka ceny']) ?>
    <div class="kalk" data-kalkulacka hidden></div>
    <noscript><p class="kalk-nahrada">Kalkulačka potřebuje zapnutý JavaScript. Všechny ceny najdete v&nbsp;<a href="#ceniky">ceníku výše</a>.</p></noscript>
    <?= json_skript('ceniky-kurtu', $kalkData) ?>
  </div>
</section>
<?php endif; ?>

<!-- III · Pravidla rezervací -->
<section class="sekce" id="pravidla" aria-labelledby="pravidla-nadpis">
  <div class="wrap mrizka">
    <div class="sl-6">
      <?= hlava_sekce($bPravidla, ['cislo' => $maKalk ? 3 : 2, 'id' => 'pravidla-nadpis', 'nadpis' => 'Pravidla rezervací', 'stitek' => 'Rezervace a předplatné']) ?>
      <?= blok_text($bPravidla, 'prose pravidla') ?>
      <?= blok_tlacitka($bPravidla) ?>
    </div>
    <?php if ($doky): ?>
    <div class="sl-5 od-8">
      <h3 class="h5 mala-hlava">Ceníky a&nbsp;pravidla ke stažení</h3>
      <?= dokumenty_html($doky) ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($dopl && $dopl['sekce']): ?>
<!-- IV · Doplňkové služby -->
<section class="sekce sekce--papir2" id="doplnkove" aria-labelledby="dopl-nadpis">
  <div class="wrap mrizka">
    <div class="sl-5">
      <?= hlava_sekce($bDopl, ['cislo' => $maKalk ? 4 : 3, 'id' => 'dopl-nadpis', 'nadpis' => (string)$dopl['list']['nazev']]) ?>
      <?php if (trim((string)$bDopl['perex']) === '' && trim((string)$dopl['list']['podnazev']) !== ''): ?><p class="perex"><?= typo((string)$dopl['list']['podnazev']) ?></p><?php endif; ?>
      <div class="odkazy-radek"><?= tlacitko('areal.php#sluzby', 'Služby v areálu', 'odkaz') ?></div>
    </div>
    <div class="sl-7 od-6">
      <?= cenik_html($dopl, ['nadpis' => false]) ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- V · Rezervace -->
<section class="sekce sekce--navy rezervace-pas" id="rezervace" aria-labelledby="rez-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bRez, ['id' => 'rez-nadpis', 'nadpis' => 'Rezervace kurtů', 'stitek' => 'Rezervace']) ?>
    <div class="rezervace-pas__kontakty">
      <?= $rezervace !== '' ? tlacitko($rezervace, 'Rezervovat kurt', 'btn', ['trida' => 'btn--svetla']) : '' ?>
      <?php if ($recTel !== ''): ?><a class="rezervace-pas__tel" href="<?= e(tel_href($recTel)) ?>"><?= e($recTel) ?><span class="vh"> – recepce</span></a><?php endif; ?>
      <?php if (je_email($recMail)): ?><a href="mailto:<?= e($recMail) ?>"><?= e($recMail) ?></a><?php endif; ?>
      <?php if ($obsazenost !== ''): ?><?= tlacitko($obsazenost, 'Obsazenost kurtů', 'odkaz') ?><?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
