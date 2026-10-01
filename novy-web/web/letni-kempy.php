<?php
/* Letní kempy Tenisové školy. Termíny z modulu Tenisová škola → Letní kempy (proběhlé
   se ztlumí; když žádný termín nečeká, stránka ukáže „termíny <příští rok> doplní klub“
   a schová tlačítko přihlášky, ať se nikdo nehlásí na proběhlý ročník), varianty
   a ceny z modulu Ceníky (klíč „kempy“), informace a přihláška z bloků stránky
   „letni-kempy“ (modul Stránky), odkaz na formulář z Textů a údajů (kempy_prihlaska_url),
   kontakt z bloku „tenisova-skola / kontakt“. */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
track_visit();

/* ---------- Pomůcky stránek závodního tenisu a Tenisové školy ----------
   Stejné ve všech stránkách sekce – kandidát do inc/sablona/komponenty.php. */
if (!function_exists('tenis_podnav')) {
    /** Pilulky se stránkami sekce podle hlavního menu (menu_hlavni); aktuální stránka navy. */
    function tenis_podnav(): string {
        $tady = here();
        foreach (menu_hlavni() as $m) {
            if (empty($m['podmenu']) || !in_array($tady, (array)$m['soubory'], true)) continue;
            $polozky = $m['podmenu'];
            if (!in_array($m['soubor'], array_column($polozky, 'soubor'), true)) {
                array_unshift($polozky, ['nazev' => $m['nazev'], 'url' => $m['url'], 'soubor' => $m['soubor'], 'aktivni' => $tady === $m['soubor']]);
            }
            $h = '';
            foreach ($polozky as $p) {
                $h .= '<li><a class="pilulka" href="' . e((string)$p['url']) . '"' . (!empty($p['aktivni']) ? ' aria-current="page"' : '') . '>' . typo((string)$p['nazev']) . '</a></li>';
            }
            return '<nav class="tenis-podnav" aria-label="' . e('Stránky sekce ' . $m['nazev']) . '"><ul class="pilulky" role="list">' . $h . '</ul></nav>';
        }
        return '';
    }
}
if (!function_exists('tenis_termin')) {
    /** Termín „29. 6. – 3. 7. 2026“ jako HTML – uvnitř data nezlomitelné mezery. */
    function tenis_termin(?string $od, ?string $do, bool $sRokem = true): string {
        $t = (string)preg_replace('/\.\s(?=\d)/u', ".\u{00A0}", cz_range($od, $do, $sRokem));
        return str_replace("\u{00A0}", '&nbsp;', e($t));
    }
}
/* ---------- konec pomůcek ---------- */

$S = 'letni-kempy';
$uvod       = blok($S, 'uvod');
$bTerminy   = blok($S, 'terminy');
$bCeny      = blok($S, 'ceny');
$bInfo      = blok($S, 'informace');
$bPrihlaska = blok($S, 'prihlaska');
$bKontakt   = blok('tenisova-skola', 'kontakt');
$cenik      = cenik('kempy');
/* Blok, který slouží jen jako nadpis sekce (obsah je z jiného modulu): když chybí, nemá hlásit „doplní klub“. */
$jenHlava = static fn(array $b): array => $b['existuje'] ? $b : ['doplni_klub' => 0] + $b;
$dnes = dnes();

/* Termíny: proběhlý = konec (datum_do, jinak datum_od) před dneškem. Přihlášku otevírá jen termín
   s datem, který ještě neskončil; termín bez data (jen text) si klub popisuje sám. */
$terminy = [];
$cekaji = 0;
$bezData = 0;
$posledniRok = 0;
foreach (skola('kemp') as $r) {
    $od = (string)($r['datum_od'] ?? '');
    $do = (string)($r['datum_do'] ?? '') ?: $od;
    $r['probehl'] = $od !== '' && $do < $dnes;
    $r['probiha'] = $od !== '' && $od <= $dnes && $do >= $dnes;
    if ($od === '') $bezData++;
    elseif (!$r['probehl']) $cekaji++;
    if ($od !== '') $posledniRok = max($posledniRok, (int)substr($od, 0, 4));
    $terminy[] = $r;
}
/* Rok, na který klub termíny teprve vypíše: po posledním proběhlém ročníku další rok. */
$dalsiRok = $posledniRok > 0 ? $posledniRok + 1 : ((int)substr($dnes, 5, 2) > 8 ? (int)substr($dnes, 0, 4) + 1 : (int)substr($dnes, 0, 4));
$prihlaskaUrl = bezpecny_odkaz(setting('kempy_prihlaska_url')) ?: bezpecny_odkaz((string)$uvod['odkaz']);
$prihlaskaText = trim((string)$uvod['odkaz_text']) ?: 'Přihláška na kemp';

/* Hlavička: tlačítko přihlášky jen když nějaký termín ještě čeká. */
$uvodHlava = $uvod;
$stavHlavy = '';
if ($cekaji === 0 && $prihlaskaUrl !== '' && bezpecny_odkaz((string)$uvod['odkaz']) === $prihlaskaUrl) {
    $uvodHlava['odkaz'] = '';
    $uvodHlava['odkaz_text'] = '';
}
if ($cekaji === 0 && $bezData === 0) {
    $stavHlavy = '<p class="tenis-stav-hlavy">' . doplni_klub('termíny ' . $dalsiRok . ' doplní klub') . '</p>';
}

$sablona = [
    'titulek' => 'Letní kempy',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-tenis.css'],
    'obrazek' => (string)$uvod['foto'],
    'trida'   => 'stranka-tenis stranka-letni-kempy',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
$sekce = 0;
?>

<?= hlava_stranky(array_merge($uvodHlava, ['odkaz' => '', 'odkaz2' => '']), ['drobky' => [['Tenisová škola', 'tenisova-skola.php'], ['Letní kempy']], 'nadpis' => 'Letní kempy', 'navic' => blok_tlacitka($uvodHlava) . $stavHlavy . tenis_podnav()]) ?>

<?php $sekce++; ?>
<!-- Termíny kempů -->
<section class="sekce sekce--bez-horni" id="terminy" aria-labelledby="terminy-nadpis">
  <div class="wrap">
    <?= hlava_sekce($jenHlava($bTerminy), ['cislo' => $sekce, 'id' => 'terminy-nadpis', 'radek' => true, 'stitek' => 'Termíny', 'nadpis' => 'Termíny kempů']) ?>
    <ol class="tenis-terminy" role="list">
      <?php foreach ($terminy as $r):
        $rok = $r['datum_od'] ? substr((string)$r['datum_od'], 0, 4) : '';
        $kdy = tenis_termin($r['datum_od'] ?? null, $r['datum_do'] ?? null, false);
        $termin = trim((string)$r['termin_text']); ?>
      <li class="tenis-termin<?= $r['probehl'] ? ' je-probehla' : '' ?>">
        <div class="tenis-termin__hlava">
          <h3 class="tenis-termin__nazev"><?= typo((string)$r['nazev']) ?></h3>
          <?php if ($r['probehl']): ?><span class="tenis-stav tenis-stav--probehlo">proběhl</span><?php elseif ($r['probiha']): ?><span class="tenis-stav tenis-stav--ted">právě probíhá</span><?php endif; ?>
        </div>
        <p class="tenis-termin__kdy"><?php if ($kdy !== ''): ?><time datetime="<?= e(substr((string)$r['datum_od'], 0, 10)) ?>"><?= $kdy ?></time><span class="tenis-termin__rok"><?= e($rok) ?></span><?php else: ?><?= $termin !== '' ? typo($termin) : doplni_klub('termín doplní klub') ?><?php endif; ?></p>
        <?php if (trim((string)$r['cas']) !== '' || trim((string)$r['cena']) !== '' || trim((string)$r['text']) !== '' || ($kdy !== '' && $termin !== '')): ?>
        <div class="tenis-termin__info">
          <?php if ($kdy !== '' && $termin !== ''): ?><p><?= typo($termin) ?></p><?php endif; ?>
          <?php if (trim((string)$r['cas']) !== ''): ?><p><?= typo((string)$r['cas']) ?></p><?php endif; ?>
          <?php if (trim((string)$r['cena']) !== ''): ?><p><?= typo((string)$r['cena']) ?></p><?php endif; ?>
          <?php if (trim((string)$r['text']) !== ''): ?><?= paragraphs((string)$r['text']) ?><?php endif; ?>
        </div>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
      <?php if ($cekaji === 0 && $bezData === 0): ?>
      <li class="tenis-termin tenis-termin--doplni">
        <p class="tenis-termin__kdy">Kempy <?= e((string)$dalsiRok) ?></p>
        <p class="tenis-termin__info"><?= doplni_klub('termíny ' . $dalsiRok . ' doplní klub') ?></p>
      </li>
      <?php endif; ?>
    </ol>
    <?php $tOdkazy = tlacitko((string)$bTerminy['odkaz'], (string)$bTerminy['odkaz_text'], 'odkaz') . tlacitko((string)$bTerminy['odkaz2'], (string)$bTerminy['odkaz2_text'], 'odkaz'); ?>
    <?php if ($tOdkazy !== ''): ?><div class="akce tenis-terminy-akce"><?= $tOdkazy ?></div><?php endif; ?>
  </div>
</section>

<?php $sekce++; ?>
<!-- Varianty a ceny (modul Ceníky, klíč „kempy“) -->
<section class="sekce sekce--papir2" id="ceny" aria-labelledby="ceny-nadpis">
  <div class="wrap">
    <?= hlava_sekce($jenHlava($bCeny), ['cislo' => $sekce, 'id' => 'ceny-nadpis', 'radek' => true, 'stitek' => 'Ceník', 'nadpis' => 'Varianty a ceny']) ?>
    <?php if ($cenik): $l = $cenik['list']; ?>
    <?php if (trim((string)$l['nazev']) !== '' || trim((string)$l['obdobi']) !== ''): ?>
    <div class="cenik__hlava"><h3 class="cenik__nazev"><?= typo((string)$l['nazev']) ?></h3><?php if (trim((string)$l['obdobi']) !== ''): ?><p class="cenik__obdobi"><?= typo((string)$l['obdobi']) ?></p><?php endif; ?></div>
    <?php endif; ?>
    <?php if (trim((string)$l['poznamka_nahore']) !== ''): ?><div class="tenis-cenik-pozn"><?= paragraphs((string)$l['poznamka_nahore']) ?></div><?php endif; ?>
    <?php foreach ($cenik['sekce'] as $s):
      $hl = ['cena' => trim((string)$s['hl_cena']), 'cena_clen' => trim((string)$s['hl_cena_clen']),
             'cena_sezona' => trim((string)$s['hl_cena_sezona']), 'cena_sezona_clen' => trim((string)$s['hl_cena_sezona_clen'])];
      $hl = array_filter($hl, fn($x) => $x !== '') ?: ['cena' => 'Cena']; ?>
    <ul class="tenis-varianty" role="list" aria-label="<?= e((string)$s['nazev']) ?>">
      <?php foreach ($s['radky'] as $r):
        $casti = array_map('trim', explode(' · ', (string)$r['nazev'], 2)); ?>
      <li class="karta karta--zlata tenis-varianta">
        <?php if (isset($casti[1]) && $casti[1] !== ''): ?><p class="tenis-varianta__cas"><?= typo($casti[1]) ?></p><?php endif; ?>
        <h3 class="tenis-varianta__nazev"><?= typo($casti[0]) ?></h3>
        <?php if (trim((string)$r['poznamka']) !== ''): ?><p class="tenis-varianta__popis"><?= typo((string)$r['poznamka']) ?></p><?php endif; ?>
        <dl class="tenis-varianta__ceny">
          <?php foreach ($hl as $pole => $nazev): ?>
          <div<?= str_contains($pole, 'clen') ? ' class="clen"' : '' ?>><dt><?= typo($nazev) ?></dt><dd><?= trim((string)$r[$pole]) !== '' ? typo((string)$r[$pole]) : '–' ?></dd></div>
          <?php endforeach; ?>
        </dl>
      </li>
      <?php endforeach; ?>
      <?php if (!$s['radky']): ?><li><?= doplni_klub('ceny doplní klub') ?></li><?php endif; ?>
    </ul>
    <?php endforeach; ?>
    <?php if (trim((string)$l['poznamka_dole']) !== ''): ?><div class="tenis-cenik-pozn"><?= paragraphs((string)$l['poznamka_dole']) ?></div><?php endif; ?>
    <?php $pdf = bezpecny_odkaz((string)$l['pdf_url']); if ($pdf !== ''): ?><p class="tenis-cenik-pozn"><?= tlacitko($pdf, 'Ceník v PDF', 'odkaz') ?></p><?php endif; ?>
    <?php else: ?>
    <p><?= doplni_klub('ceník kempů doplní klub') ?></p>
    <?php endif; ?>
  </div>
</section>

<?php $sekce++; ?>
<!-- Co je dobré vědět + přihláška a kontakt -->
<section class="sekce" id="informace" aria-labelledby="informace-nadpis">
  <div class="wrap">
    <div class="mrizka">
      <div class="sl-7">
        <?= hlava_sekce($bInfo, ['cislo' => $sekce, 'id' => 'informace-nadpis', 'stitek' => 'Informace', 'nadpis' => 'Co je dobré vědět']) ?>
        <?= blok_text($bInfo) ?>
      </div>
      <div class="sl-4 od-9">
        <aside class="karta karta--zlata tenis-prihlaska" id="prihlaska" aria-labelledby="prihlaska-nadpis">
          <p class="stitek"><?= typo(trim((string)$bPrihlaska['stitek']) ?: 'Přihláška') ?></p>
          <h2 class="h3" id="prihlaska-nadpis"><?= trim((string)$bPrihlaska['nadpis']) !== '' ? html_inline((string)$bPrihlaska['nadpis']) : 'Přihláška a kontakt' ?></h2>
          <?php if (trim((string)$bPrihlaska['perex']) !== ''): ?><div class="tenis-prihlaska__perex"><?= paragraphs((string)$bPrihlaska['perex']) ?></div><?php endif; ?>
          <?php if ($cekaji > 0 && $prihlaskaUrl !== ''): ?>
          <div class="akce"><?= tlacitko($prihlaskaUrl, $prihlaskaText) ?></div>
          <?php elseif ($cekaji === 0 && $bezData === 0): ?>
          <p class="tenis-prihlaska__stav"><?= doplni_klub('přihlášky na rok ' . $dalsiRok . ' doplní klub') ?></p>
          <?php endif; ?>
          <?= $bPrihlaska['existuje'] ? blok_text($bPrihlaska) : '' ?>
          <?php if (trim((string)$bKontakt['text']) !== ''): ?>
          <div class="tenis-prihlaska__kontakt"><?= blok_text($bKontakt) ?></div>
          <?php endif; ?>
        </aside>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
