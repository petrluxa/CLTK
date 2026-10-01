<?php
/* Restaurace Tiebreak – stránka „připravujeme“, dokud restaurace nemá vlastní
   web (nastavení restaurace_url; když je vyplněné, menu vede rovnou tam).
   Základní ověřené údaje: otevření, terasa, salónek s dětským koutkem.
   Obsah: Stránky (bloky „restaurace“ – uvod s fotkou terasy, informace,
   kontakt a každý další blok, který klub na stránce založí), Areál a služby
   (služby s kotvou „restaurace“ a „salonek“ – fotky, časy, text). */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
track_visit();

if (!function_exists('stranka_dalsi_bloky')) {
    /** Bloky, které klub založí v modulu Stránky navíc (mimo klíče, které šablona čte sama) – každý jako sekce. */
    function stranka_dalsi_bloky(string $stranka, array $zname): string {
        $h = '';
        foreach (bloky($stranka) as $b) {
            $klic = (string)$b['klic'];
            if (in_array($klic, $zname, true)) continue;
            $id = 'blok-' . preg_replace('/[^a-z0-9-]/', '', $klic);
            $foto = blok_foto($b, 'pomer-4x3');
            $maNadpis = trim((string)$b['nadpis']) !== '';
            $h .= '<section class="sekce sekce--linka blok-sekce" id="' . e($id) . '"'
                . ($maNadpis ? ' aria-labelledby="' . e($id) . '-nadpis"' : ' aria-label="' . e(html_text((string)$b['stitek']) ?: 'Další informace') . '"') . '>'
                . '<div class="wrap mrizka"><div class="' . ($foto !== '' ? 'sl-7' : 'sl-8') . '">'
                . hlava_sekce($b, ['id' => $id . '-nadpis'])
                . blok_text($b) . blok_tlacitka($b)
                . '</div>' . ($foto !== '' ? '<div class="sl-4 od-9">' . $foto . '</div>' : '') . '</div></section>';
        }
        return $h;
    }
}

/* ---------- data ---------- */
$uvod     = blok('restaurace', 'uvod');
$bInfo    = blok('restaurace', 'informace');
$bKontakt = blok('restaurace', 'kontakt');

$podleKotvy = [];
foreach (sluzby() as $s) $podleKotvy[(string)$s['kotva']] = $s;
$restaurace = $podleKotvy['restaurace'] ?? null;
$prostory = array_values(array_filter([$restaurace, $podleKotvy['salonek'] ?? null],
    fn($s) => $s && trim((string)$s['foto']) !== '' && is_file(UPLOAD_DIR . '/' . $s['foto'])));
$firemni = $podleKotvy['firemni-akce'] ?? null;
$casy = $restaurace ? trim((string)$restaurace['casy']) : '';

$sablona = [
    'titulek' => html_text((string)$uvod['stitek']) ?: 'Restaurace',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-areal.css'],
    'obrazek' => (string)$uvod['foto'],
    'trida'   => 'stranka-restaurace',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvod, ['drobky' => [['Restaurace']], 'nadpis' => 'Restaurace']) ?>

<!-- I · Terasa a salónek -->
<section class="sekce sekce--papir2" id="prostory" aria-labelledby="info-nadpis">
  <div class="wrap restaurace-info">
    <div>
      <?= hlava_sekce($bInfo, ['cislo' => 1, 'id' => 'info-nadpis', 'nadpis' => 'Terasa a salónek', 'stitek' => 'Restaurace a terasa']) ?>
      <?= blok_text($bInfo) ?>
      <?= blok_tlacitka($bInfo) ?>
    </div>
    <?php if ($prostory): ?>
    <div class="prostory">
      <?php foreach ($prostory as $s): ?>
      <figure class="ramec ramec--linka">
        <div class="ramec__obraz pomer-4x3"><?= obr((string)$s['foto'], (string)$s['nazev'], ['class' => 'foto', 'fokus' => (string)$s['fokus']]) ?></div>
        <figcaption class="popisek"><span><b><?= typo((string)$s['nazev']) ?></b><?= trim((string)$s['perex']) !== '' ? ' – ' . typo(rtrim((string)$s['perex'], '.')) : '' ?></span></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- II · Otevírací doba a kontakt -->
<section class="sekce" id="kontakt" aria-labelledby="kontakt-nadpis">
  <div class="wrap mrizka">
    <div class="sl-6">
      <?= hlava_sekce($bKontakt, ['cislo' => 2, 'id' => 'kontakt-nadpis', 'nadpis' => 'Otevírací doba a kontakt', 'stitek' => 'Kontakt']) ?>
      <?= trim(html_text((string)$bKontakt['text'])) !== '' ? blok_text($bKontakt) : '' ?>
      <?= blok_tlacitka($bKontakt) ?>
    </div>
    <div class="sl-5 od-8">
      <ul class="radky">
        <li class="radek radek--vodici"><span class="radek__nazev">Otevírací doba</span><span class="radek__hodnota"><?= $casy !== '' ? typo($casy) : doplni_klub() ?></span></li>
      </ul>
      <div class="odkazy-radek">
        <?= $restaurace ? tlacitko('areal.php#' . $restaurace['kotva'], 'Restaurace v areálu', 'odkaz') : '' ?>
        <?= $firemni ? tlacitko('areal.php#' . $firemni['kotva'], (string)$firemni['nazev'], 'odkaz') : '' ?>
      </div>
    </div>
  </div>
</section>

<?= stranka_dalsi_bloky('restaurace', ['uvod', 'informace', 'kontakt']) ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
