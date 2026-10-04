<?php
/* Klub – o klubu: ověřená čísla s prameny, klub uprostřed města, pět jmen
   a cesta na Štvanici, rodokmen stoletých klubů a rozcestník na podstránky
   (Členství, Historie, Vedení, CTC, Revue). Jazyk Varianty 4 (sekce 3 a 3b).
   Všechen obsah je z databáze: bloky stránky „klub“ (modul Stránky),
   rodokmen z modulu Vedení a CTC, dokumenty z modulu Dokumenty. */
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

/**
 * Položky prvního seznamu (<ul>/<ol>) v textu bloku z editoru.
 * Vrací [['hlava' => HTML tučného začátku, 'hlava_text' => prostý text, 'text' => HTML zbytku], …].
 * Text se nejdřív vyčistí (html_ocistit), takže výstup je bezpečný HTML.
 */
function klub_polozky(?string $html): array {
    $html = html_ocistit((string)$html);
    if ($html === '' || !preg_match('~<(ul|ol)>~', $html)) return [];
    $doc = new DOMDocument('1.0', 'UTF-8');
    $stary = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body><div id="k">' . $html . '</div></body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($stary);
    $obal = $doc->getElementById('k');
    $seznam = null;
    if ($obal) foreach ($obal->childNodes as $u) {
        if ($u instanceof DOMElement && in_array($u->nodeName, ['ul', 'ol'], true)) { $seznam = $u; break; }
    }
    if (!$seznam) return [];
    $v = [];
    foreach ($seznam->childNodes as $li) {
        if (!$li instanceof DOMElement || $li->nodeName !== 'li') continue;
        $hlava = ''; $hlavaText = ''; $text = ''; $zacatek = true;
        foreach ($li->childNodes as $x) {
            if ($zacatek && $x->nodeType === XML_TEXT_NODE && trim($x->nodeValue, " \t\n\u{00A0}") === '') continue;
            if ($zacatek && $x instanceof DOMElement && $x->nodeName === 'strong') {
                foreach ($x->childNodes as $y) $hlava .= $doc->saveHTML($y);
                $hlavaText = trim(str_replace("\u{00A0}", ' ', $x->textContent));
                $zacatek = false;
                continue;
            }
            $zacatek = false;
            $text .= $doc->saveHTML($x);
        }
        $text = (string)preg_replace('~^(\s|&nbsp;|\x{00A0})*[–—-]?(\s|&nbsp;|\x{00A0})*~u', '', trim($text));
        $v[] = ['hlava' => trim($hlava), 'hlava_text' => $hlavaText, 'text' => trim($text)];
    }
    return $v;
}

/** Štítek bloku, který jen opakuje nadpis („Pět jmen, jeden klub“), nahradí obecným. */
function klub_stitek(array $b, string $nahradni): array {
    $s = mb_strtolower(trim((string)$b['stitek']), 'UTF-8');
    $n = mb_strtolower(rtrim(html_text((string)$b['nadpis']), ' .'), 'UTF-8');
    if ($s === '' || $s === $n) $b['stitek'] = $nahradni;
    return $b;
}

/* ---------- data ---------- */
$uvod       = klub_blok('klub', 'uvod');
$bCisla     = klub_blok('klub', 'cisla');
$bPrameny   = klub_blok('klub', 'prameny');
$bOstrov    = klub_blok('klub', 'ostrov');
$bOKlubu    = klub_blok('klub', 'o-klubu');
$bJmena     = klub_blok('klub', 'jmena');
$bCesta     = klub_blok('klub', 'cesta');
$bRozcestnik = klub_blok('klub', 'rozcestnik');
$bRodokmen  = klub_blok('ctc', 'rodokmen');
$bDokumenty = klub_blok('vedeni', 'dokumenty');

$cisla    = klub_polozky((string)$bCisla['text']);
$prameny  = klub_polozky((string)$bPrameny['text']);
$jmena    = klub_polozky((string)$bJmena['text']);
$cesta    = klub_polozky((string)$bCesta['text']);
$rodokmen = ctc('rodokmen');
$dokumentyKlub = dokumenty('klub');

/* Text pod seznamem jmen (poznámka „Roky změn jmen uvádíme podle klubu…“) */
$jmenaPozn = trim((string)preg_replace('~<(ul|ol)>.*?</\1>~s', '', html_ocistit((string)$bJmena['text'])));

/* Hlava stránky: když úvodní blok nemá fotku, vezme se letecký snímek z bloku „ostrov“ */
if (trim((string)$uvod['foto']) === '' && trim((string)$bOstrov['foto']) !== '') {
    $uvod['foto'] = $bOstrov['foto'];
    $uvod['foto_popisek'] = $bOstrov['foto_popisek'];
}

/* Rozcestník: podstránky Klubu z menu + jejich úvodní bloky (štítek, nadpis, perex) */
$podstranky = [];
foreach (menu_hlavni() as $m) {
    if ($m['soubor'] !== 'klub.php') continue;
    foreach ($m['podmenu'] as $p) {
        $b = klub_blok(basename($p['soubor'], '.php'), 'uvod');
        $podstranky[] = ['nazev' => $p['nazev'], 'url' => $p['url'], 'nadpis' => (string)$b['nadpis'], 'perex' => (string)$b['perex']];
    }
}

$sablona = [
    'titulek' => 'Klub',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-klub.css'],
    'obrazek' => (string)$uvod['foto'],
    'trida'   => 'stranka-klub',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvod, ['drobky' => [['Klub']], 'nadpis' => 'Klub']) ?>

<?php if ($cisla): ?>
  <!-- I · Klub v číslech (V4 sekce 3) -->
  <section class="sekce sekce--tesna sekce--linka klub-cisla" id="cisla" aria-labelledby="cisla-nadpis">
    <div class="wrap">
      <h2 class="vh" id="cisla-nadpis"><?= typo(trim((string)$bCisla['stitek']) ?: 'Klub v číslech') ?></h2>
      <ul class="cisla" style="--sloupcu:<?= min(6, max(2, count($cisla))) ?>">
        <?php foreach ($cisla as $i => $c):
          // „19 kurtů“ → 19 + malá jednotka; první slovo je hodnota
          $casti = preg_split('/\s+/u', $c['hlava_text'], 2);
          $hodnota = (string)($casti[0] ?? '');
          $jednotka = (string)($casti[1] ?? '');
          $maPramen = isset($prameny[$i]); ?>
        <li class="cisla__polozka">
          <span class="cisla__hodnota <?= preg_match('/^\d{4}$/', $hodnota) ? 'onum' : 'tnum' ?>"><?= e($hodnota) ?><?php if ($jednotka !== ''): ?><small><?= typo($jednotka) ?></small><?php endif; ?></span>
          <span class="cisla__popis"><?= $c['text'] ?><?php if ($maPramen): ?><sup><a href="#pramen-<?= $i + 1 ?>" aria-label="Pramen <?= $i + 1 ?>"><?= $i + 1 ?></a></sup><?php endif; ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php if ($prameny): ?>
      <details class="rozbal rozbal--male prameny" id="prameny">
        <summary><?= typo(trim(html_text((string)$bPrameny['nadpis'])) ?: 'Odkud čísla bereme') ?></summary>
        <ol class="prameny__seznam rozbal__obsah">
          <?php foreach ($prameny as $i => $p): ?>
          <li id="pramen-<?= $i + 1 ?>"><?= $p['hlava'] !== '' ? '<strong>' . $p['hlava'] . '</strong> ' : '' ?><?= $p['text'] ?></li>
          <?php endforeach; ?>
        </ol>
      </details>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php if ($bOKlubu['existuje']): ?>
  <!-- II · Klub uprostřed města -->
  <section class="sekce sekce--papir2 klub-mesto" id="o-klubu" aria-labelledby="o-klubu-nadpis">
    <div class="wrap mrizka">
      <div class="sl-5">
        <?= hlava_sekce($bOKlubu, ['cislo' => 1, 'id' => 'o-klubu-nadpis', 'stitek' => 'O klubu']) ?>
      </div>
      <div class="sl-6 od-7 klub-mesto__text">
        <?= blok_text($bOKlubu) ?>
        <?= blok_tlacitka($bOKlubu) ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($jmena || $rodokmen): ?>
  <!-- III · Pět jmen, cesta na ostrov, rodokmen stoletých (V4 sekce 3b) -->
  <section class="sekce klub-rod-sekce" id="jmena" aria-labelledby="jmena-nadpis">
    <div class="wrap klub-rod">
      <div class="klub-rod__jmena">
        <?= hlava_sekce(klub_stitek($bJmena, 'Klub'), ['cislo' => 2, 'id' => 'jmena-nadpis', 'nadpis' => 'Pět jmen, jeden klub.']) ?>
        <?php if ($jmena): ?>
        <ol class="jmena-klubu">
          <?php foreach ($jmena as $i => $j):
            $rokHtml = preg_match('/^(kolem|asi|cca|po roce|od)\s+(.+)$/iu', $j['hlava_text'], $m)
                ? '<small>' . e($m[1]) . '</small> <span class="onum">' . e($m[2]) . '</span>'
                : '<span class="onum">' . e($j['hlava_text']) . '</span>'; ?>
          <li<?= $i === count($jmena) - 1 ? ' class="jmena-klubu__dnes"' : '' ?>><span class="jmena-klubu__rok"><?= $rokHtml ?></span><span class="jmena-klubu__nazev"><?= $j['text'] ?></span></li>
          <?php endforeach; ?>
        </ol>
        <?php endif; ?>
        <?php if ($jmenaPozn !== ''): ?><div class="drobne klub-rod__pozn"><?= $jmenaPozn ?></div><?php endif; ?>

        <?php if ($cesta): ?>
        <div class="cesta-ostrov">
          <p class="stitek stitek--tlumeny"><?= typo(trim((string)$bCesta['stitek']) ?: 'Cesta na Štvanici') ?></p>
          <p class="cesta-ostrov__trasa">
            <?php foreach ($cesta as $i => $c): ?><span<?= $i === count($cesta) - 1 ? ' class="cesta-ostrov__cil"' : '' ?>><b class="onum"><?= e($c['hlava_text']) ?></b> <?= $c['text'] ?></span><?php endforeach; ?>
          </p>
          <?php if (trim((string)$bCesta['perex']) !== ''): ?><div class="drobne cesta-ostrov__pozn"><?= paragraphs((string)$bCesta['perex']) ?></div><?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($rodokmen): ?>
      <div class="klub-rod__rodokmen">
        <p class="stitek"><?= typo(trim((string)$bRodokmen['stitek']) ?: 'Rodokmen stoletých') ?></p>
        <?php if (trim((string)$bRodokmen['perex']) !== ''): ?><div class="klub-rod__perex"><?= paragraphs((string)$bRodokmen['perex']) ?></div><?php endif; ?>
        <ol class="rodokmen">
          <?php foreach ($rodokmen as $r): $my = (int)$r['zvyraznit'] === 1; ?>
          <li class="rodokmen__klub<?= $my ? ' rodokmen__klub--my' : '' ?>">
            <span class="rodokmen__rok onum"><?= e((string)$r['rok']) ?></span>
            <span class="rodokmen__nazev"><?= $my ? '<b class="sc">' . typo((string)$r['nazev']) . '</b>' : typo((string)$r['nazev']) ?><?php if (!$my && trim((string)$r['misto']) !== ''): ?><small><?= typo((string)$r['misto']) ?></small><?php endif; ?></span>
            <span class="rodokmen__pozn"><?= typo((string)$r['text']) ?></span>
          </li>
          <?php endforeach; ?>
        </ol>
        <p class="drobne"><?= tlacitko('ctc.php', 'Centenary Tennis Clubs', 'odkaz') ?></p>
      </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php if ($podstranky): ?>
  <!-- IV · Rozcestník na podstránky Klubu -->
  <section class="sekce sekce--linka rozcestnik-sekce" id="rozcestnik" aria-labelledby="rozcestnik-nadpis">
    <div class="wrap">
      <?= hlava_sekce($bRozcestnik, ['cislo' => 3, 'id' => 'rozcestnik-nadpis', 'stitek' => 'Klub', 'nadpis' => 'Členství, historie a lidé klubu.']) ?>
      <ol class="rozcestnik">
        <?php foreach ($podstranky as $i => $p): ?>
        <li>
          <a class="rozcestnik__polozka" href="<?= e($p['url']) ?>">
            <span class="rozcestnik__cislo" aria-hidden="true"><?= e(rimske($i + 1)) ?>.</span>
            <span class="rozcestnik__text">
              <span class="stitek"><?= typo($p['nazev']) ?></span>
              <span class="rozcestnik__nadpis"><?= trim($p['nadpis']) !== '' ? html_inline($p['nadpis']) : typo($p['nazev']) ?></span>
              <?php if (trim($p['perex']) !== ''): ?><span class="rozcestnik__perex"><?= typo(uryvek($p['perex'], 170)) ?></span><?php endif; ?>
            </span>
            <?= sipka() ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ol>

      <?php if ($dokumentyKlub): ?>
      <div class="klub-dokumenty">
        <h3 class="h4"><?= trim((string)$bDokumenty['nadpis']) !== '' ? html_inline((string)$bDokumenty['nadpis']) : 'Stanovy a dokumenty' ?></h3>
        <?= dokumenty_html($dokumentyKlub) ?>
        <p class="klub-dokumenty__vse"><?= tlacitko('dokumenty.php', 'Všechny dokumenty klubu', 'odkaz') ?></p>
      </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
