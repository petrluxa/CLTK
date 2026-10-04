<?php
/* I.ČLTK Revue a newsletter – kiosek Revue z Varianty 4 (návrh 2 „Ostrov v čase“):
   právě vyšlo, hledání v titulcích obálek a obsazích, příběh v obálkách, polička
   všech čísel po letech a detail čísla v dialogu; pod tím seznam všech čísel
   (funguje i bez JavaScriptu) a klubový newsletter česky i anglicky.
   Data: modul Revue a newslettery (cltk_revue, cltk_newslettery), texty z bloků
   stránky „revue“ (modul Stránky). Kiosek: assets/js/kiosek.js + v2.css. */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
track_visit();

/** Blok stránky (modul Stránky). Chybějící blok nedostane štítek „doplní klub“ –
    sekce použije náhradní nadpis, nebo se nevypíše (obsah stránky přitom je). */
function klub_blok(string $stranka, string $klic): array {
    $b = blok($stranka, $klic);
    if (empty($b['existuje'])) $b['doplni_klub'] = 0;
    return $b;
}

/** Text bez diakritiky malými písmeny (kmen jména pro „příběh v obálkách“). */
function revue_bez_diakritiky(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    return strtr($s, ['á' => 'a', 'ä' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'í' => 'i', 'ľ' => 'l', 'ĺ' => 'l', 'ň' => 'n',
                      'ó' => 'o', 'ö' => 'o', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u', 'ů' => 'u', 'ü' => 'u', 'ý' => 'y', 'ž' => 'z']);
}

/** Kmen příjmení: „Muchová“ → „muchov“, „Hradecká“ → „hradeck“, „Kodeš“ → „kodes“ (najde i Muchové, Kodešem…). */
function revue_kmen(string $jmeno): string {
    $casti = preg_split('/\s+/u', trim($jmeno)) ?: [];
    $k = revue_bez_diakritiky((string)end($casti));
    $k = (string)preg_replace('/[^a-z]/', '', $k);
    if (strlen($k) > 4 && str_ends_with($k, 'a')) $k = substr($k, 0, -1);
    return $k;
}

/** Titulek z obálky čísla jako HTML. Když je stejný článek v obsahu čísla, vezme se jeho
 *  zápis („VELKÝ TITUL PRO KAROLÍNU“ → „Velký titul pro Karolínu“). Jinak zůstanou verzálky
 *  z obálky, jen drobněji a prostrkaně (jako v kiosku Varianty 4) – automatické malé písmo
 *  by zkazilo vlastní jména („Markéta vondroušová“, „Prague open“). $t = jiný titulek čísla. */
function revue_titulek_html(array $c, ?string $t = null): string {
    $t = trim((string)($t ?? ($c['titulky_pole'][0] ?? '')));
    if ($t === '') return '';
    foreach ((array)$c['obsah_pole'] as $o) {
        if (is_array($o) && mb_strtolower(trim((string)($o[1] ?? ''))) === mb_strtolower($t)) return typo(trim((string)$o[1]));
    }
    if ($t === mb_strtoupper($t) && preg_match('~\p{Lu}~u', $t)) return '<span class="titulek-obalky">' . typo($t) . '</span>';
    return typo($t);
}

/** Položky seznamu z textu bloku (jména pro „příběh v obálkách“). */
function revue_polozky(?string $html): array {
    $html = html_ocistit((string)$html);
    if (!preg_match_all('~<li>(.*?)</li>~su', $html, $m)) return [];
    return array_values(array_filter(array_map(fn($x) => trim(html_entity_decode(strip_tags($x), ENT_QUOTES | ENT_HTML5, 'UTF-8')), $m[1])));
}

/** Velikost PDF „13,3“ → „13,3 MB“. */
function revue_mb($mb): string {
    $mb = trim((string)$mb);
    return $mb === '' ? '' : str_replace('.', ',', $mb) . '&nbsp;MB';
}

/* ---------- data ---------- */
$uvod        = klub_blok('revue', 'uvod');
$bKiosek     = klub_blok('revue', 'kiosek');
$bPribehy    = klub_blok('revue', 'pribehy');
$bNewsletter = klub_blok('revue', 'newslettery');

$cisla = revue_cisla();
/* redakční poznámka z přepisu obálek („VITĚZKA [sic] WIMBLEDONU“) na web klubu nepatří */
foreach ($cisla as &$c) {
    $c['titulky_pole'] = array_map(fn($t) => is_string($t) ? trim((string)preg_replace('/\s*\[sic\]/iu', '', $t)) : $t, (array)$c['titulky_pole']);
}
unset($c);
$posledni = $cisla[0] ?? null;
$pribehy = revue_polozky((string)$bPribehy['text']);
$bezPdf = array_values(array_filter($cisla, fn($c) => trim((string)$c['pdf']) === ''));
$newslettery = newslettery();
$nlRoky = [];
foreach ($newslettery as $n) $nlRoky[(int)$n['rok']][] = $n;
krsort($nlRoky);

/* polička po letech od nejstaršího čísla (jako ve Variantě 4) */
$policka = [];
foreach (array_reverse($cisla) as $c) $policka[(int)$c['rok']][] = $c;
ksort($policka);
foreach ($policka as &$r) usort($r, fn($a, $b) => (int)$a['cislo'] <=> (int)$b['cislo']);
unset($r);
$rokOd = $policka ? (int)array_key_first($policka) : 0;
$rokDo = $policka ? (int)array_key_last($policka) : 0;
$let = $rokOd ? $rokDo - $rokOd + 1 : 0;

$kotvy = [];
if ($cisla) $kotvy[] = [trim(html_text((string)$bKiosek['stitek'])) ?: 'Kiosek', '#kiosek'];
if ($cisla) $kotvy[] = ['Všechna čísla', '#archiv'];
if ($newslettery) $kotvy[] = [trim(html_text((string)$bNewsletter['stitek'])) ?: 'Newsletter', '#newsletter'];

$cislo = 0;
$sablona = [
    'titulek' => 'I.ČLTK Revue a newsletter',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['v2.css', 'stranky-klub.css'],
    'js'      => ['kiosek.js'],
    'obrazek' => $posledni ? (string)$posledni['obalka'] : '',
    'trida'   => 'stranka-revue',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvod, [
    'drobky' => [['Klub', 'klub.php'], ['Revue']],
    'nadpis' => 'I.ČLTK Revue',
    'navic'  => $kotvy ? '<nav class="kotvy" aria-label="Části stránky">' . pilulky_html($kotvy) . '</nav>' : '',
]) ?>

<?php if ($cisla): ?>
  <!-- I · Kiosek I.ČLTK Revue (V4 sekce 8, převzato z návrhu 2) -->
  <section class="sekce sekce--papir2 v2 kiosek-sekce" id="kiosek" aria-labelledby="kiosek-nadpis">
    <div class="wrap">
      <?= hlava_sekce(array_merge($bKiosek, ['nadpis' => trim((string)$bKiosek['nadpis']) !== '' ? (string)$bKiosek['nadpis'] : 'Kiosek <em>I.ČLTK Revue</em>']), ['cislo' => ++$cislo, 'id' => 'kiosek-nadpis', 'radek' => true, 'stitek' => 'Kiosek']) ?>

      <?= json_skript('revue-data', revue_data_js($cisla)) ?>
      <div class="kiosek" data-kiosek="revue-dialog">
        <div class="kiosek-hlava">
          <?php if ($posledni):
            $img = obr((string)$posledni['obalka'], '', ['loading' => 'eager']);
            $pdf = (string)$posledni['pdf']; ?>
          <div class="prave-vyslo">
            <?php $popisObalky = 'Otevřít detail I.ČLTK Revue ' . $posledni['oznaceni']; ?>
            <?php if ($pdf !== ''): ?>
            <a class="prave-vyslo-obalka" href="<?= e($pdf) ?>"<?= odkaz_attr($pdf) ?> data-kiosek-cislo="<?= e((string)$posledni['oznaceni']) ?>" aria-label="<?= e($popisObalky) ?>"><?= $img ?></a>
            <?php else: ?>
            <span class="prave-vyslo-obalka"><?= $img ?></span>
            <?php endif; ?>
            <div>
              <p class="nadtitul">Právě vyšlo · <?= e((string)$posledni['oznaceni']) ?><?= (int)$posledni['stran'] > 0 ? ' · ' . (int)$posledni['stran'] . '&nbsp;stran' : '' ?></p>
              <h3><?= revue_titulek_html($posledni) ?></h3>
              <?php $obsah = array_slice(array_filter((array)$posledni['obsah_pole'], 'is_array'), 0, 6); ?>
              <?php if ($obsah): ?>
              <ol>
                <?php foreach ($obsah as $o): ?><li><span><?= e((string)($o[0] ?? '')) ?></span><?= typo((string)($o[1] ?? '')) ?></li><?php endforeach; ?>
              </ol>
              <?php endif; ?>
              <?php if ($pdf !== ''): ?>
              <p class="prave-vyslo-pdf"><a class="odkaz" href="<?= e($pdf) ?>"<?= odkaz_attr($pdf) ?>>Číst PDF<?= revue_mb($posledni['pdf_mb']) !== '' ? ' <span class="cislice">(' . revue_mb($posledni['pdf_mb']) . ')</span>' : '' ?> <?= sipka(odkaz_je_externi($pdf) ? 'ven' : '') ?><?= odkaz_je_externi($pdf) ? '<span class="vh"> (v novém okně)</span>' : '' ?></a></p>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <form class="kiosek-hledani" role="search" action="<?= e(url('revue.php')) ?>#archiv" method="get" data-kiosek-jen-js hidden>
            <label class="nadtitul" for="revue-hledat">Hledat v<?= $let > 1 ? '&nbsp;' . $let . '&nbsp;letech' : '' ?> Revue</label>
            <div class="hledat"><input type="search" id="revue-hledat" name="q" data-kiosek-hledat placeholder="Drobný, terasa, Wimbledon…" autocomplete="off" enterkeyhint="search"></div>
            <p class="kiosek-stav" data-kiosek-stav aria-live="polite"></p>
            <ol class="kiosek-vysledky" data-kiosek-vysledky></ol>
            <?php if (trim((string)$bKiosek['text']) !== ''): ?><div class="kiosek-pozn"><?= html_ocistit((string)$bKiosek['text']) ?></div><?php endif; ?>
            <?php if ($pribehy): ?>
            <div class="kiosek-pribeh">
              <p class="nadtitul"><?= trim((string)$bPribehy['nadpis']) !== '' ? html_inline((string)$bPribehy['nadpis']) : 'Příběh v&nbsp;obálkách' ?></p>
              <div class="cipy">
                <?php foreach ($pribehy as $p): ?>
                <button type="button" class="cip" data-kiosek-pribeh="<?= e(slugify($p)) ?>" data-kmen="<?= e(revue_kmen($p)) ?>" aria-pressed="false"><?= typo($p) ?></button>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </form>
        </div>

        <ol class="policka" data-kiosek-policka aria-label="Polička <?= count($cisla) ?> čísel I.ČLTK Revue<?= $rokOd ? ', od roku ' . $rokOd : '' ?>">
          <?php foreach ($policka as $rok => $rocnik): ?>
          <li class="policka-rok"><div class="policka-obalky">
            <?php foreach ($rocnik as $c):
              $pdf = (string)$c['pdf'];
              $popis = 'I.ČLTK Revue ' . $c['oznaceni'] . (!empty($c['titulky_pole'][0]) ? ' – ' . mb_strtolower((string)$c['titulky_pole'][0]) : '') . ($pdf === '' ? ' (bez PDF)' : '');
              $img = obr((string)$c['obalka']);
              $vnitrek = $img !== '' ? $img : '<span class="r-obalka-bez">' . e((string)$c['oznaceni']) . '</span>'; ?>
            <?php if ($pdf !== ''): ?>
            <a class="r-obalka" href="<?= e($pdf) ?>"<?= odkaz_attr($pdf) ?> data-kiosek-cislo="<?= e((string)$c['oznaceni']) ?>" aria-label="<?= e($popis) ?>"><?= $vnitrek ?></a>
            <?php else: ?>
            <a class="r-obalka r-obalka-bez-pdf" href="#archiv-<?= e(str_replace('/', '-', (string)$c['oznaceni'])) ?>" data-kiosek-cislo="<?= e((string)$c['oznaceni']) ?>" aria-label="<?= e($popis) ?>"><?= $vnitrek ?></a>
            <?php endif; ?>
            <?php endforeach; ?>
          </div><span class="policka-rok-cislo" aria-hidden="true"><?= e((string)$rok) ?></span></li>
          <?php endforeach; ?>
        </ol>
        <?php if ($bezPdf): ?>
        <p class="policka-pozn"><span class="m-pramen"><?= count($bezPdf) === 1 ? 'Číslo ' : 'Čísla ' ?><?= typo(implode(count($bezPdf) === 2 ? ' a ' : ', ', array_map(fn($c) => (string)$c['oznaceni'], array_reverse($bezPdf)))) ?> v&nbsp;archivu PDF chybí</span></p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <dialog class="dialog v2" id="revue-dialog" aria-labelledby="revue-dialog-titul">
    <div class="dialog-hlava"><p class="nadtitul" id="revue-dialog-titul" data-dialog-titul>Detail</p><button type="button" class="dialog-zavrit" data-dialog-zavrit>Zavřít</button></div>
    <div class="dialog-obsah" data-dialog-obsah></div>
  </dialog>

  <!-- II · Všechna čísla v seznamu (i bez JavaScriptu) -->
  <section class="sekce sekce--tesna revue-archiv" id="archiv" aria-labelledby="archiv-nadpis">
    <div class="wrap">
      <details class="rozbal revue-archiv__rozbal"<?= isset($_GET['q']) ? ' open' : '' ?>>
        <summary><span id="archiv-nadpis">Všechna čísla v&nbsp;seznamu <span class="tlumene">· <?= count($cisla) ?> čísel<?= $rokOd ? ', ' . $rokOd . '–' . $rokDo : '' ?></span></span></summary>
        <div class="rozbal__obsah">
          <ol class="revue-archiv__seznam">
            <?php foreach ($cisla as $c):
              $pdf = (string)$c['pdf']; ?>
            <li class="revue-archiv__cislo" id="archiv-<?= e(str_replace('/', '-', (string)$c['oznaceni'])) ?>">
              <span class="revue-archiv__oznaceni onum"><?= e((string)$c['oznaceni']) ?></span>
              <span class="revue-archiv__titulky"><?= implode(' · ', array_map(fn($t) => revue_titulek_html($c, (string)$t), array_slice((array)$c['titulky_pole'], 0, 3))) ?></span>
              <span class="revue-archiv__pdf"><?php if ($pdf !== ''): ?><a href="<?= e($pdf) ?>"<?= odkaz_attr($pdf) ?>>PDF<?= revue_mb($c['pdf_mb']) !== '' ? ' · ' . revue_mb($c['pdf_mb']) : '' ?><span class="vh"> – I.ČLTK Revue <?= e((string)$c['oznaceni']) ?><?= odkaz_je_externi($pdf) ? ' (v novém okně)' : '' ?></span></a><?php else: ?><?= doplni_klub('PDF doplní klub') ?><?php endif; ?></span>
            </li>
            <?php endforeach; ?>
          </ol>
        </div>
      </details>
    </div>
  </section>
<?php endif; ?>

<?php if ($nlRoky): ?>
  <!-- III · Klubový newsletter česky a anglicky -->
  <section class="sekce sekce--papir2 revue-newsletter" id="newsletter" aria-labelledby="newsletter-nadpis">
    <div class="wrap">
      <?= hlava_sekce($bNewsletter, ['cislo' => ++$cislo, 'id' => 'newsletter-nadpis', 'radek' => true, 'stitek' => 'Newsletter', 'nadpis' => 'I.ČLTK Newsletter']) ?>
      <div class="newslettery">
        <?php foreach ($nlRoky as $rok => $cisla2): ?>
        <section class="newslettery__rok" aria-labelledby="nl-<?= (int)$rok ?>">
          <h3 class="newslettery__rocnik onum" id="nl-<?= (int)$rok ?>"><?= (int)$rok ?></h3>
          <ul class="newslettery__seznam">
            <?php foreach ($cisla2 as $n):
              $cs = (string)$n['cs_url'];
              $en = (string)$n['en_url'];
              $nazev = trim((string)$n['oznaceni']) !== '' ? (string)$n['oznaceni'] : (string)$n['cislo']; ?>
            <li class="newsletter">
              <span class="newsletter__cislo"><?= typo($nazev) ?><?php if (trim((string)$n['nazev']) !== ''): ?><small><?= typo((string)$n['nazev']) ?></small><?php endif; ?></span>
              <span class="newsletter__jazyky">
                <?php if ($cs !== ''): ?><a href="<?= e($cs) ?>"<?= odkaz_attr($cs) ?> hreflang="cs" lang="cs">česky<span class="vh"> – newsletter <?= e($nazev) ?>, PDF<?= odkaz_je_externi($cs) ? ' (v novém okně)' : '' ?></span></a><?php endif; ?>
                <?php if ($en !== ''): ?><a href="<?= e($en) ?>"<?= odkaz_attr($en) ?> hreflang="en" lang="en">English<span class="vh" lang="cs"> – newsletter <?= e($nazev) ?>, PDF<?= odkaz_je_externi($en) ? ' (v novém okně)' : '' ?></span></a><?php endif; ?>
              </span>
            </li>
            <?php endforeach; ?>
          </ul>
        </section>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
