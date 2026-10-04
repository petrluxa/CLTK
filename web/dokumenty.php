<?php
/* Dokumenty klubu – rozcestník (Klub › Dokumenty; odkaz v patičce „Všechny dokumenty“,
   na stránkách Kontakt a Klub). Dokumenty jsou stránky webu v klubovém stylu (dokument.php),
   ne PDF (klient 2. 10. 2026); PDF zůstávají jen v archivu klubových turnajů a u Revue.
     I   Pravidla a provozní řády (kategorie provoz)
     II  Klub a spolek (klub + clenstvi)
     III Ceníky (cenik + skola)
     IV  Archiv klubových turnajů – PDF po turnajích a ročnících (rok, název, strany, velikost)
     V   Jubilejní Revue 1893–2023 a odkaz na archiv Revue a newsletterů
   Obsah: modul Dokumenty, texty z bloků stránky „dokumenty“ (modul Stránky; chybějící blok
   = výchozí nadpis, žádné „doplní klub“), obálka a PDF jubilejní Revue z modulu Revue. */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
track_visit();

/** Blok stránky; chybějící blok nedostane štítek „doplní klub“ (sekce má výchozí nadpis). */
function dok_blok(string $klic): array {
    $b = blok('dokumenty', $klic);
    if (empty($b['existuje'])) $b['doplni_klub'] = 0;
    return $b;
}

/* ---------- data ---------- */
$uvod = dok_blok('uvod');
if (empty($uvod['existuje'])) {
    $uvod = array_merge($uvod, ['stitek' => 'Dokumenty', 'nadpis' => 'Dokumenty <em>klubu</em>.',
        'perex' => 'Stanovy, pravidla hraní a provozní řády areálu přímo na webu – každý dokument si můžete i vytisknout. Níže je archiv klubových turnajů a jubilejní Revue.']);
}

/* skupiny dokumentů: klíč bloku => [kategorie, kotva, výchozí nadpis, výchozí perex] */
$skupiny = [
    'pravidla' => [['provoz'], 'pravidla', 'Pravidla a provozní <em>řády</em>', 'Pravidla hraní a rezervací kurtů, provozní řády bazénu, posilovny a wellness a plán areálu.'],
    'klub'     => [['klub', 'clenstvi'], 'klub-spolek', 'Klub a <em>spolek</em>', 'Stanovy spolku a oznámení o zpracování osobních údajů členů.'],
    'ceniky'   => [['cenik', 'skola'], 'ceniky', 'Ceníky', 'Ceník k vytištění. Aktuální ceny kurtů v létě i v zimě jsou na stránce Ceník kurtů.'],
];
$sekce = [];
foreach ($skupiny as $klic => [$kategorie, $kotva, $nadpis, $perex]) {
    $docs = [];
    foreach ($kategorie as $k) $docs = array_merge($docs, dokumenty($k));
    $docs = array_values(array_filter($docs, fn($d) => dokument_url($d) !== ''));
    if (!$docs) continue;
    $b = dok_blok($klic);
    if (empty($b['existuje'])) { $b['nadpis'] = $nadpis; $b['perex'] = $perex; }
    $sekce[] = ['klic' => $klic, 'kotva' => $kotva, 'blok' => $b, 'docs' => $docs];
}

$archiv = dokumenty_archiv();
$bArchiv = dok_blok('archiv');
if (empty($bArchiv['existuje'])) {
    $bArchiv['nadpis'] = 'Archiv klubových <em>turnajů</em>';
    $bArchiv['perex'] = 'Pozvánky, rozlosování a výsledky klubových turnajů a akcí. Soubory PDF, jak je klub vydal.';
}
$archivPocet = 0;
$archivRoky = [];
foreach ($archiv as $t) foreach ($t['roky'] as $rok => $docs) { $archivPocet += count($docs); if ($rok > 0) $archivRoky[] = $rok; }

/* jubilejní Revue (cislo 0) a archiv Revue */
$revue = revue_cisla();
$jubilejni = null;
foreach ($revue as $c) { if ((int)$c['cislo'] === 0 && (string)$c['pdf'] !== '') { $jubilejni = $c; break; } }
$bRevue = dok_blok('revue');
if (empty($bRevue['existuje'])) {
    $bRevue['nadpis'] = 'Kompletní historie v jubilejní <em>Revue</em>';
    $bRevue['perex'] = 'Speciální číslo I.ČLTK Revue ke 130. výročí klubu – dějiny klubu od roku 1893, prezidenti, legendy Štvanice.';
}

$kotvy = [];
foreach ($sekce as $s) $kotvy[] = [trim(html_text((string)$s['blok']['nadpis'])), '#' . $s['kotva']];
if ($archiv) $kotvy[] = ['Archiv turnajů', '#archiv-turnaju'];
if ($revue) $kotvy[] = ['I.ČLTK Revue', '#revue'];

$cislo = 0;
$sablona = [
    'titulek' => 'Dokumenty klubu',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-klub.css', 'dokument.css'],
    'trida'   => 'stranka-dokumenty',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvod, [
    'drobky' => [['Klub', 'klub.php'], ['Dokumenty']],
    'nadpis' => 'Dokumenty klubu',
    'navic'  => $kotvy ? '<nav class="kotvy" aria-label="Části stránky">' . pilulky_html($kotvy) . '</nav>' : '',
    'trida'  => 'hlava-stranky--linka',
]) ?>

<?php foreach ($sekce as $i => $s): ?>
<section class="sekce sekce--tesna<?= $i % 2 ? ' sekce--papir2' : '' ?> dokumenty-skupina" id="<?= e($s['kotva']) ?>" aria-labelledby="<?= e($s['kotva']) ?>-nadpis">
  <div class="wrap mrizka">
    <div class="sl-4">
      <?= hlava_sekce($s['blok'], ['cislo' => ++$cislo, 'id' => $s['kotva'] . '-nadpis']) ?>
      <?php if ($s['klic'] === 'ceniky'): ?><p class="dokumenty-skupina__odkaz"><?= tlacitko('cenik-kurtu.php', 'Ceník kurtů – léto i zima', 'odkaz') ?></p><?php endif; ?>
      <?php if ($s['klic'] === 'pravidla' && rezervace_url() !== ''): ?><p class="dokumenty-skupina__odkaz"><?= tlacitko(rezervace_url(), 'Rezervovat kurt', 'odkaz') ?></p><?php endif; ?>
    </div>
    <div class="sl-7 od-6"><?= dokumenty_html($s['docs']) ?></div>
  </div>
</section>
<?php endforeach; ?>

<?php if ($archiv): ?>
<!-- Archiv klubových turnajů – PDF po turnajích a ročnících -->
<section class="sekce<?= count($sekce) % 2 ? ' sekce--papir2' : '' ?> archiv-turnaju" id="archiv-turnaju" aria-labelledby="archiv-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bArchiv, ['cislo' => ++$cislo, 'id' => 'archiv-nadpis', 'radek' => true]) ?>
    <p class="archiv-turnaju__souhrn drobne"><?= $archivPocet ?> <?= sklonuj($archivPocet, 'soubor', 'soubory', 'souborů') ?> PDF<?= $archivRoky ? ' · ' . min($archivRoky) . '–' . max($archivRoky) : '' ?> · <?= count($archiv) ?> <?= sklonuj(count($archiv), 'turnaj či akce', 'turnaje a akce', 'turnajů a akcí') ?></p>
    <div class="archiv-turnaju__mrizka">
      <?php foreach ($archiv as $t): ?>
      <section class="archiv-turnaj" aria-labelledby="turnaj-<?= e(slugify($t['nazev'])) ?>">
        <h3 class="archiv-turnaj__nazev" id="turnaj-<?= e(slugify($t['nazev'])) ?>"><?= typo($t['nazev']) ?></h3>
        <ol class="archiv-turnaj__roky" role="list">
          <?php foreach ($t['roky'] as $rok => $docs): ?>
          <li class="archiv-rocnik">
            <span class="archiv-rocnik__rok"><?= $rok > 0 ? (int)$rok : '–' ?></span>
            <?= dokumenty_html($docs, ['nazev' => 'dokument_archiv_nazev', 'trida' => 'dokumenty--archiv']) ?>
          </li>
          <?php endforeach; ?>
        </ol>
      </section>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($revue): ?>
<!-- Jubilejní Revue 1893–2023 a archiv Revue -->
<section class="sekce sekce--navy dokumenty-revue" id="revue" aria-labelledby="revue-nadpis">
  <div class="wrap mrizka mrizka--stred">
    <?php if ($jubilejni): $obalka = obr((string)$jubilejni['obalka'], 'Obálka jubilejní I.ČLTK Revue ' . $jubilejni['oznaceni']); ?>
    <div class="sl-4">
      <a class="dokumenty-revue__obalka" href="<?= e((string)$jubilejni['pdf']) ?>" type="application/pdf"><?= $obalka !== '' ? $obalka : '<span>' . e((string)$jubilejni['oznaceni']) . '</span>' ?><span class="vh">Jubilejní I.ČLTK Revue <?= e((string)$jubilejni['oznaceni']) ?> (PDF)</span></a>
    </div>
    <?php endif; ?>
    <div class="<?= $jubilejni ? 'sl-7 od-6' : 'sl-8' ?>">
      <?= hlava_sekce($bRevue, ['cislo' => ++$cislo, 'id' => 'revue-nadpis', 'stitek' => 'I.ČLTK Revue']) ?>
      <div class="akce">
        <?php if ($jubilejni): ?>
        <a class="btn btn--svetla" href="<?= e((string)$jubilejni['pdf']) ?>" type="application/pdf">Číst jubilejní Revue<?= trim((string)$jubilejni['pdf_mb']) !== '' ? ' <small>(PDF, ' . e(str_replace('.', ',', (string)$jubilejni['pdf_mb'])) . '&nbsp;MB)</small>' : '' ?> <?= sipka() ?></a>
        <?php endif; ?>
        <?= tlacitko('revue.php#kiosek', 'Všechna čísla Revue a newslettery', 'odkaz') ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
