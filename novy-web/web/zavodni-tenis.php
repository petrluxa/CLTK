<?php
/* Závodní tenis – pyramida ČTS (Tenisová škola → TSM → SVT → SCM → extraliga),
   sezóna v číslech (žebříček ČTS, reprezentanti), naši hráči a jejich poslední
   výsledky, extraliga (ročníky, soupiska), družstva mládeže a trenérský tým.

   Všechno z databáze: bloky stránky „zavodni-tenis“ (modul Stránky – klíče popisuje
   sql/seed/58-tenis.php), výsledky hráčů (modul Výsledky hráčů) a trenéři (modul
   Trenéři, zařazení „závodní tenis“). Bloky s předponou „pyramida-“ jsou stupně
   pyramidy, „hrac-“ karty hráčů – nový blok s takovým klíčem se na stránce ukáže sám
   (pořadí = šipky v modulu Stránky). Jména dětí (TSM, SVT) se tu neuvádějí. */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
track_visit();

/* ---------- Pomůcky stránek závodního tenisu a Tenisové školy ----------
   Stejné ve všech stránkách sekce (zavodni-tenis*.php, tenisova-skola*.php,
   letni-kempy.php) – kandidát do inc/sablona/komponenty.php. */
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
if (!function_exists('tenis_polozky')) {
    /**
     * Text bloku z editoru rozebraný na skupiny odrážek – šablona je pak vysází jako
     * desku, stupně nebo řádky. Vrací [['nadpis' => html, 'polozky' => [['stitek' => html,
     * 'text' => html, 'cele' => html], …], 'odstavce' => html], …]. Nadpis skupiny = h2–h4
     * před seznamem, štítek položky = tučný začátek odrážky („<strong>2019</strong> mistr ČR“).
     * Vstup projde html_ocistit(), takže výstup tvoří jen povolené holé značky.
     */
    function tenis_polozky(?string $html): array {
        $cisty = html_ocistit((string)$html);
        if ($cisty === '') return [];
        $doc = new DOMDocument('1.0', 'UTF-8');
        $stav = libxml_use_internal_errors(true);
        $ok = $doc->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body><div id="tenis-obal">' . $cisty . '</div></body></html>',
                             LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($stav);
        $obal = $ok ? $doc->getElementById('tenis-obal') : null;
        if (!$obal) return [];
        $html = static function (iterable $uzly) use ($doc): string {
            $h = '';
            foreach ($uzly as $u) $h .= $doc->saveHTML($u);
            return trim($h);
        };
        $skupiny = [];
        $akt = ['nadpis' => '', 'polozky' => [], 'odstavce' => ''];
        $uzavri = static function () use (&$akt, &$skupiny): void {
            if ($akt['nadpis'] !== '' || $akt['polozky'] || $akt['odstavce'] !== '') $skupiny[] = $akt;
            $akt = ['nadpis' => '', 'polozky' => [], 'odstavce' => ''];
        };
        foreach ($obal->childNodes as $u) {
            $z = $u instanceof DOMElement ? strtolower($u->nodeName) : '';
            if (in_array($z, ['h2', 'h3', 'h4'], true)) {
                $uzavri();
                $akt['nadpis'] = $html($u->childNodes);
            } elseif ($z === 'ul' || $z === 'ol') {
                foreach ($u->childNodes as $li) {
                    if (!$li instanceof DOMElement || strtolower($li->nodeName) !== 'li') continue;
                    $deti = iterator_to_array($li->childNodes);
                    $i = 0;
                    while (isset($deti[$i]) && $deti[$i] instanceof DOMText && trim((string)$deti[$i]->nodeValue, " \t\n\r\u{00A0}") === '') $i++;
                    $stitek = '';
                    $zbytek = $deti;
                    if (isset($deti[$i]) && $deti[$i] instanceof DOMElement && strtolower($deti[$i]->nodeName) === 'strong') {
                        $stitek = $html($deti[$i]->childNodes);
                        $zbytek = array_slice($deti, $i + 1);
                    }
                    $text = (string)preg_replace('/^(?:[\s\x{00A0}]|&nbsp;|[–—:\-])+/u', '', $html($zbytek));
                    $akt['polozky'][] = ['stitek' => $stitek, 'text' => $text, 'cele' => $html($li->childNodes)];
                }
            } elseif ($u instanceof DOMElement || trim((string)$u->nodeValue) !== '') {
                $akt['odstavce'] .= $doc->saveHTML($u);
            }
        }
        $uzavri();
        return $skupiny;
    }
}
/* ---------- konec pomůcek ---------- */

/** Karta hráče (blok hrac-*) ve stylu karet lidí (.osoba): portrét 4 : 5, žebříček, jméno, v klubu od…, úspěchy. */
function zt_hrac_html(array $b): string {
    $jmeno = html_text((string)$b['nadpis']);
    $h = '<li class="osoba tenis-hrac">';
    $img = obr((string)$b['foto'], html_text((string)$b['foto_popisek']) ?: $jmeno);
    $h .= $img !== ''
        ? '<div class="osoba__foto">' . $img . '</div>'
        : '<div class="osoba__foto osoba__foto--prazdne" aria-hidden="true"><span>' . e(mb_substr($jmeno, 0, 1)) . '</span></div>';
    if (trim((string)$b['stitek']) !== '') $h .= '<p class="osoba__role">' . typo((string)$b['stitek']) . '</p>';
    $h .= '<h3 class="osoba__jmeno">' . html_inline((string)$b['nadpis']) . '</h3>';
    if (trim((string)$b['perex']) !== '') $h .= '<div class="osoba__text">' . paragraphs((string)$b['perex']) . '</div>';
    $polozky = [];
    $odstavce = '';
    foreach (tenis_polozky((string)$b['text']) as $sk) {
        foreach ($sk['polozky'] as $p) $polozky[] = $p['cele'];
        $odstavce .= $sk['odstavce'];
    }
    if ($polozky) $h .= '<ul class="osoba__fakta">' . implode('', array_map(fn($x) => '<li>' . $x . '</li>', $polozky)) . '</ul>';
    if ($odstavce !== '') $h .= '<div class="osoba__text">' . $odstavce . '</div>';
    $odkaz = tlacitko((string)$b['odkaz'], (string)$b['odkaz_text'], 'odkaz');
    if ($odkaz !== '') $h .= '<p class="osoba__kontakt">' . $odkaz . '</p>';
    if ((int)$b['doplni_klub'] === 1) $h .= '<p class="osoba__kontakt">' . doplni_klub() . '</p>';
    return $h . '</li>';
}

/* ---------- data ---------- */
$S = 'zavodni-tenis';
$uvod       = blok($S, 'uvod');
$bPyramida  = blok($S, 'pyramida');
$bCisla     = blok($S, 'cisla');
$bZebricky  = blok($S, 'zebricky');
$bRepre     = blok($S, 'reprezentanti');
$bHraci     = blok($S, 'hraci');
$bExtraliga = blok($S, 'extraliga');
$bRocniky   = blok($S, 'extraliga-rocniky');
$bSoupiska  = blok($S, 'soupiska');
$bDruzstva  = blok($S, 'druzstva');
$bTreneri   = blok('zavodni-tenis-treneri', 'uvod');
/* Blok, který slouží jen jako nadpis sekce (obsah je z jiného modulu): když chybí, nemá hlásit „doplní klub“. */
$jenHlava = static fn(array $b): array => $b['existuje'] ? $b : ['doplni_klub' => 0] + $b;

$vsechnyBloky = bloky($S);
$sPredponou = static fn(string $p): array => array_values(array_filter($vsechnyBloky, fn($x) => str_starts_with((string)$x['klic'], $p)));
$stupne = $sPredponou('pyramida-');
$hraci  = $sPredponou('hrac-');

/* Čísla sezóny: odrážky „26 reprezentantů…“ → velké číslo + popis; odrážky bez čísla pod pás. */
$cisla = [];
$cislaJine = [];
foreach (tenis_polozky((string)$bCisla['text']) as $sk) {
    foreach ($sk['polozky'] as $p) {
        if ($p['stitek'] !== '' && preg_match('/^\d[\d\s\x{00A0}]*$/u', html_text($p['stitek']))) {
            $cisla[] = [$p['stitek'], $p['text']];
        } elseif (preg_match('/^(\d+(?:[\s\x{00A0}]\d{3})*)(?:[\s\x{00A0}]|&nbsp;)+(.+)$/us', $p['cele'], $m)) {
            $cisla[] = [$m[1], $m[2]];
        } else {
            $cislaJine[] = $p['cele'];
        }
    }
}
$zebricky = tenis_polozky((string)$bZebricky['text']);
$repre    = tenis_polozky((string)$bRepre['text']);
$rocniky  = tenis_polozky((string)$bRocniky['text']);
$soupiska = tenis_polozky((string)$bSoupiska['text']);
$druzstva = tenis_polozky((string)$bDruzstva['text']);

$vysledky = posledni_vysledky(4);
$treneriNahled = array_slice(array_values(array_filter(treneri('zavodni'),
    fn($t) => trim((string)$t['foto']) !== '' && is_file(UPLOAD_DIR . '/' . ltrim((string)$t['foto'], '/')))), 0, 6);

$sablona = [
    'titulek' => 'Závodní tenis',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-tenis.css'],
    'obrazek' => (string)$bSoupiska['foto'],
    'trida'   => 'stranka-tenis stranka-zavodni-tenis',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
$sekce = 0;
?>

<?= hlava_stranky(array_merge($uvod, ['odkaz' => '', 'odkaz2' => '']), ['drobky' => [['Závodní tenis']], 'nadpis' => 'Závodní tenis', 'navic' => blok_tlacitka($uvod) . tenis_podnav()]) ?>

<?php if ($stupne || $bPyramida['existuje']): $sekce++; ?>
<!-- Pyramida ČTS: stupně od Tenisové školy po extraligu -->
<section class="sekce sekce--bez-horni" id="pyramida" aria-labelledby="pyramida-nadpis">
  <div class="wrap">
    <?= hlava_sekce($stupne ? $jenHlava($bPyramida) : $bPyramida, ['cislo' => $sekce, 'id' => 'pyramida-nadpis', 'stitek' => 'Pyramida ČTS', 'nadpis' => 'Od školy po extraligu']) ?>
    <?php if ($stupne): ?>
    <ol class="pyramida" style="--stupnu: <?= count($stupne) ?>">
      <?php foreach ($stupne as $i => $st):
        $fakta = [];
        $jine = '';
        foreach (tenis_polozky((string)$st['text']) as $sk) {
            foreach ($sk['polozky'] as $p) $fakta[] = $p;
            $jine .= $sk['odstavce'];
        }
        $posledni = $i === count($stupne) - 1; ?>
      <li class="pyramida__stupen<?= $posledni ? ' pyramida__stupen--vrchol' : '' ?>" style="--i: <?= $i ?>">
        <p class="pyramida__cislo" aria-hidden="true"><?= e(rimske($i + 1)) ?></p>
        <?php if (trim((string)$st['stitek']) !== ''): ?><p class="pyramida__vek"><?= typo((string)$st['stitek']) ?></p><?php endif; ?>
        <h3 class="pyramida__nazev"><?= html_inline((string)$st['nadpis']) ?></h3>
        <?php if (trim((string)$st['perex']) !== ''): ?><div class="pyramida__popis"><?= paragraphs((string)$st['perex']) ?></div><?php endif; ?>
        <?php if ($fakta): ?>
        <dl class="pyramida__fakta">
          <?php foreach ($fakta as $f): ?>
          <div><?php if ($f['stitek'] !== ''): ?><dt><?= $f['stitek'] ?></dt><?php endif; ?><dd><?= $f['stitek'] !== '' ? $f['text'] : $f['cele'] ?></dd></div>
          <?php endforeach; ?>
        </dl>
        <?php endif; ?>
        <?php if ($jine !== ''): ?><div class="pyramida__popis"><?= $jine ?></div><?php endif; ?>
        <?php $stFoto = obr((string)$st['foto'], html_text((string)$st['foto_popisek']) ?: html_text((string)$st['nadpis'])); ?>
        <?php if ($stFoto !== ''): ?>
        <figure class="pyramida__foto"><div class="osoba__foto"><?= $stFoto ?></div><?php if (trim((string)$st['foto_popisek']) !== ''): ?><figcaption class="popisek"><span><?= html_inline((string)$st['foto_popisek']) ?></span></figcaption><?php endif; ?></figure>
        <?php endif; ?>
        <?php $stOdkaz = tlacitko((string)$st['odkaz'], (string)$st['odkaz_text'], 'odkaz'); ?>
        <?php if ($stOdkaz !== ''): ?><p class="pyramida__odkaz"><?= $stOdkaz ?></p><?php endif; ?>
        <?php if ((int)$st['doplni_klub'] === 1): ?><p class="pyramida__odkaz"><?= doplni_klub() ?></p><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php else: ?>
    <?= blok_text($bPyramida) ?>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($cisla || $cislaJine || $zebricky || $repre || $bCisla['existuje']): $sekce++; ?>
<!-- Sezóna v číslech: pás čísel, žebříček ČTS a reprezentanti -->
<section class="sekce sekce--papir2" id="cisla" aria-labelledby="cisla-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bCisla, ['cislo' => $sekce, 'id' => 'cisla-nadpis', 'stitek' => 'Čísla sezóny', 'nadpis' => 'Sezóna v číslech']) ?>
    <?php if ($cisla): ?>
    <ul class="cisla tenis-cisla" style="--sloupcu: <?= min(4, count($cisla)) ?>">
      <?php foreach ($cisla as [$hodnota, $popis]): ?>
      <li class="cisla__polozka"><span class="cisla__hodnota tnum"><?= $hodnota ?></span><span class="cisla__popis"><?= $popis ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <?php foreach ($cislaJine as $jine): ?>
    <p class="tenis-cisla__pozn"><?= $jine ?></p>
    <?php endforeach; ?>
    <?php if (!$cisla && !$cislaJine): ?><?= blok_text($bCisla) ?><?php endif; ?>

    <?php if ($zebricky || $repre): ?>
    <div class="tenis-desky<?= $zebricky && $repre ? ' tenis-desky--dve' : '' ?>">
      <?php if ($zebricky): ?>
      <article class="deska tenis-deska" aria-labelledby="zebricky-nadpis">
        <header class="deska__hlava">
          <h3 class="deska__nadpis" id="zebricky-nadpis"><?= trim((string)$bZebricky['nadpis']) !== '' ? html_inline((string)$bZebricky['nadpis']) : 'Žebříček ČTS' ?></h3>
          <?php if (trim((string)$bZebricky['perex']) !== ''): ?><p class="deska__podnadpis"><?= typo((string)$bZebricky['perex']) ?></p><?php endif; ?>
        </header>
        <?php $pata = ''; foreach ($zebricky as $sk): $pata .= $sk['odstavce']; if (!$sk['polozky']) continue; ?>
        <?php if ($sk['nadpis'] !== ''): ?><h4 class="deska__mezititulek"><?= $sk['nadpis'] ?></h4><?php endif; ?>
        <ol class="deska__radky tenis-deska__radky">
          <?php foreach ($sk['polozky'] as $p): ?>
          <li class="deska__radek"><?php if ($p['stitek'] !== ''): ?><span class="deska__rok"><?= $p['stitek'] ?></span><?php endif; ?><span class="deska__jmeno"><?= $p['stitek'] !== '' ? $p['text'] : $p['cele'] ?></span></li>
          <?php endforeach; ?>
        </ol>
        <?php endforeach; ?>
        <?php if ($pata !== ''): ?><div class="deska__pata tenis-deska__pata"><?= $pata ?></div><?php endif; ?>
      </article>
      <?php endif; ?>

      <?php if ($repre): ?>
      <article class="deska tenis-deska" aria-labelledby="repre-nadpis">
        <header class="deska__hlava">
          <h3 class="deska__nadpis" id="repre-nadpis"><?= trim((string)$bRepre['nadpis']) !== '' ? html_inline((string)$bRepre['nadpis']) : 'Reprezentanti' ?></h3>
        </header>
        <?php if (trim((string)$bRepre['perex']) !== ''): ?><div class="tenis-deska__perex"><?= paragraphs((string)$bRepre['perex']) ?></div><?php endif; ?>
        <?php $pata = ''; foreach ($repre as $sk): $pata .= $sk['odstavce']; if (!$sk['polozky']) continue; ?>
        <?php if ($sk['nadpis'] !== ''): ?><h4 class="deska__mezititulek"><?= $sk['nadpis'] ?></h4><?php endif; ?>
        <p class="deska__jmena"><?php foreach ($sk['polozky'] as $p): ?><span><?= $p['cele'] ?></span> <?php endforeach; ?></p>
        <?php endforeach; ?>
        <?php if ($pata !== ''): ?><div class="deska__pata tenis-deska__pata"><?= $pata ?></div><?php endif; ?>
      </article>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($hraci || $vysledky): $sekce++; ?>
<!-- Naši hráči: karty hráčů (bloky hrac-*) a poslední výsledky (modul Výsledky hráčů) -->
<section class="sekce" id="hraci" aria-labelledby="hraci-nadpis">
  <div class="wrap">
    <?= hlava_sekce($jenHlava($bHraci), ['cislo' => $sekce, 'id' => 'hraci-nadpis', 'radek' => true, 'stitek' => 'Naši hráči', 'nadpis' => 'Naši hráči']) ?>
    <?php if ($hraci): ?>
    <ul class="osoby tenis-hraci" role="list">
      <?php foreach ($hraci as $h) echo zt_hrac_html($h); ?>
    </ul>
    <?php endif; ?>

    <?php if ($vysledky): ?>
    <div class="tenis-vysledky">
      <div class="tenis-vysledky__hlava">
        <h3 class="stitek stitek--velky" id="vysledky-nadpis">Poslední výsledky</h3>
        <a class="odkaz" href="<?= e(url('index.php#vysledky')) ?>">Další výsledky na úvodní stránce <?= sipka() ?></a>
      </div>
      <div class="tenis-vysledky__seznam">
        <?php foreach ($vysledky as $v):
          $vOdkaz = bezpecny_odkaz((string)$v['odkaz']);
          $vTag = $vOdkaz !== '' ? 'a' : 'div'; ?>
          <<?= $vTag ?> class="vysledek"<?= $vOdkaz !== '' ? ' href="' . e($vOdkaz) . '"' . odkaz_attr($vOdkaz) : '' ?>>
            <p class="vysledek__kdy"><?php if (cz_date((string)$v['datum']) !== ''): ?><time datetime="<?= e(substr((string)$v['datum'], 0, 10)) ?>"><?= str_replace(' ', '&nbsp;', e(cz_date((string)$v['datum']))) ?></time><?php endif; ?><?= typo((string)$v['misto']) ?></p>
            <div>
              <?php if (trim((string)$v['stitek']) !== ''): ?><p class="vysledek__kolo"><?= typo((string)$v['stitek']) ?></p><?php endif; ?>
              <p class="vysledek__kdo"><?= typo((string)$v['hraci']) ?><?= $vOdkaz !== '' && odkaz_je_externi($vOdkaz) ? '<span class="vh"> – zpráva v&nbsp;novém okně</span>' : '' ?></p>
              <?php if (trim((string)$v['text']) !== ''): ?><p class="vysledek__text"><?= typo((string)$v['text']) ?></p><?php endif; ?>
            </div>
            <?php if ($v['sety_pole']): ?>
            <p class="vysledek__skore"><span class="vh">Výsledek <?= e(sety_text($v['sety_pole'])) ?></span><?php foreach ($v['sety_pole'] as $set): ?><span<?= $set['vyhra'] ? '' : ' class="p"' ?> aria-hidden="true"><?= e((string)$set['hlavni']) ?><?php if (trim((string)$set['doplnek']) !== ''): ?><small><?= e(trim((string)$set['doplnek'], '()')) ?></small><?php endif; ?></span><?php endforeach; ?></p>
            <?php endif; ?>
            <?php if (trim((string)$v['verdikt']) !== ''): ?><span class="vysledek__verdikt"><?= typo((string)$v['verdikt']) ?></span><?php endif; ?>
          </<?= $vTag ?>>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($bExtraliga['existuje'] || $rocniky || $soupiska): $sekce++; ?>
<!-- Extraliga smíšených družstev: text, ročníky, týmová fotka a soupiska -->
<section class="sekce sekce--navy" id="extraliga" aria-labelledby="extraliga-nadpis">
  <div class="wrap">
    <div class="mrizka tenis-extraliga">
      <div class="sl-5 tenis-extraliga__text">
        <?= hlava_sekce($rocniky || $soupiska ? $jenHlava($bExtraliga) : $bExtraliga, ['cislo' => $sekce, 'id' => 'extraliga-nadpis', 'stitek' => 'Extraliga', 'nadpis' => 'Extraliga smíšených družstev']) ?>
        <?= $bExtraliga['existuje'] || (!$rocniky && !$soupiska) ? blok_text($bExtraliga) : '' ?>
        <?php if ($rocniky): ?>
        <h3 class="stitek tenis-podtitul"><?= typo(trim((string)$bRocniky['stitek']) ?: 'Ročníky extraligy') ?></h3>
        <?php foreach ($rocniky as $sk): if (!$sk['polozky']) continue; ?>
        <ol class="tenis-rocniky" role="list">
          <?php foreach ($sk['polozky'] as $p): ?>
          <li><?php if ($p['stitek'] !== ''): ?><span class="tenis-rocniky__rok"><?= $p['stitek'] ?></span><?php endif; ?><span class="tenis-rocniky__text"><?= $p['stitek'] !== '' ? $p['text'] : $p['cele'] ?></span></li>
          <?php endforeach; ?>
        </ol>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php if ($soupiska || trim((string)$bSoupiska['foto']) !== ''): ?>
      <div class="sl-6 od-7 tenis-extraliga__tym">
        <?= blok_foto($bSoupiska, 'pomer-3x2') ?>
        <div class="tenis-soupiska">
          <?php if (trim((string)$bSoupiska['stitek']) !== ''): ?><p class="stitek"><?= typo((string)$bSoupiska['stitek']) ?></p><?php endif; ?>
          <?php if (trim((string)$bSoupiska['nadpis']) !== ''): ?><h3 class="h3 tenis-soupiska__nadpis"><?= html_inline((string)$bSoupiska['nadpis']) ?></h3><?php endif; ?>
          <?php if (trim((string)$bSoupiska['perex']) !== ''): ?><div class="tenis-soupiska__perex"><?= paragraphs((string)$bSoupiska['perex']) ?></div><?php endif; ?>
          <?php if ($soupiska): ?>
          <div class="tenis-soupiska__sloupce">
            <?php $pata = ''; foreach ($soupiska as $sk): $pata .= $sk['odstavce']; if (!$sk['polozky']) continue; ?>
            <div>
              <?php if ($sk['nadpis'] !== ''): ?><h4 class="tenis-soupiska__skupina"><?= $sk['nadpis'] ?></h4><?php endif; ?>
              <ul class="tenis-soupiska__jmena" role="list"><?php foreach ($sk['polozky'] as $p): ?><li><?= $p['cele'] ?></li><?php endforeach; ?></ul>
            </div>
            <?php endforeach; ?>
          </div>
          <?php if ($pata !== ''): ?><div class="prose tenis-soupiska__pata"><?= $pata ?></div><?php endif; ?>
          <?php endif; ?>
          <?php $sOdkazy = tlacitko((string)$bSoupiska['odkaz'], (string)$bSoupiska['odkaz_text'], 'odkaz') . tlacitko((string)$bSoupiska['odkaz2'], (string)$bSoupiska['odkaz2_text'], 'odkaz'); ?>
          <?php if ($sOdkazy !== ''): ?><div class="akce tenis-soupiska__akce"><?= $sOdkazy ?></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($druzstva || $bDruzstva['existuje']): $sekce++; ?>
<!-- Družstva mládeže: výsledky soutěží družstev podle sezón -->
<section class="sekce sekce--papir2" id="druzstva" aria-labelledby="druzstva-nadpis">
  <div class="wrap">
    <div class="mrizka">
      <div class="sl-4">
        <?= hlava_sekce($bDruzstva, ['cislo' => $sekce, 'id' => 'druzstva-nadpis', 'stitek' => 'Družstva mládeže', 'nadpis' => 'Družstva mládeže']) ?>
      </div>
      <div class="sl-7 od-6">
        <?php if ($druzstva): ?>
        <div class="tenis-druzstva">
          <?php foreach ($druzstva as $sk): ?>
          <div class="tenis-druzstva__sezona">
            <?php if ($sk['nadpis'] !== ''): ?><h3 class="tenis-druzstva__nadpis"><?= $sk['nadpis'] ?></h3><?php endif; ?>
            <?php if ($sk['polozky']): ?>
            <ul class="tenis-druzstva__seznam" role="list">
              <?php foreach ($sk['polozky'] as $p): ?>
              <li><?php if ($p['stitek'] !== ''): ?><span class="tenis-druzstva__kat"><?= $p['stitek'] ?></span><?php endif; ?><span class="tenis-druzstva__vysl"><?= $p['stitek'] !== '' ? $p['text'] : $p['cele'] ?></span></li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <?php if ($sk['odstavce'] !== ''): ?><div class="prose"><?= $sk['odstavce'] ?></div><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <?= blok_text($bDruzstva) ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $sekce++; ?>
<!-- Trenérský tým: náhled a odkaz na celý tým -->
<section class="sekce" id="treneri" aria-labelledby="treneri-nadpis">
  <div class="wrap">
    <?= hlava_sekce($jenHlava($bTreneri), ['cislo' => $sekce, 'id' => 'treneri-nadpis', 'radek' => true, 'stitek' => 'Trenérský tým', 'nadpis' => 'Trenéři závodního tenisu']) ?>
    <?php if ($treneriNahled): ?>
    <ul class="osoby tenis-osoby--male" role="list">
      <?php foreach ($treneriNahled as $t) echo osoba_html(['jmeno' => $t['jmeno'], 'role' => $t['role'], 'foto' => $t['foto'], 'fokus' => $t['fokus']]); ?>
    </ul>
    <?php else: ?>
    <p><?= doplni_klub('trenérský tým doplní klub') ?></p>
    <?php endif; ?>
    <div class="akce tenis-akce">
      <?= tlacitko('zavodni-tenis-treneri.php', 'Celý trenérský tým', 'btn', ['sipka' => '']) ?>
      <?= tlacitko('tenisova-skola.php', 'Tenisová škola', 'odkaz') ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
