<?php
/* Prague Open – stránka „připravujeme“, dokud turnaj nemá vlastní web
   v nastavení (prague_open_url; když je vyplněné, menu vede rovnou tam).
   Základní údaje ročníku 2026, vítězové a stručná historie turnaje.
   Obsah: Stránky (bloky „prague-open“ – uvod s fotkou a odkazem na web turnaje,
   rocnik, vitezove, historie a každý další blok, který klub na stránce založí).
   Odkazuje se jen na pragueopen.net (doména pragueopen.org je cizí). */
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
$uvod      = blok('prague-open', 'uvod');
$bRocnik   = blok('prague-open', 'rocnik');
$bVitezove = blok('prague-open', 'vitezove');
$bHistorie = blok('prague-open', 'historie');

$webTurnaje = bezpecny_odkaz((string)$uvod['odkaz']);

$sablona = [
    'titulek' => 'Prague Open',          // název položky menu (štítek bloku je „Turnaj klubu“)
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-areal.css'],
    'obrazek' => (string)$uvod['foto'],
    'trida'   => 'stranka-prague-open',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvod, ['drobky' => [['Prague Open']], 'nadpis' => 'Prague Open']) ?>

<?php if ($bRocnik['existuje'] || $bVitezove['existuje']): ?>
<!-- I · Ročník v kostce a vítězové -->
<section class="sekce sekce--papir2" id="rocnik" aria-labelledby="rocnik-nadpis">
  <div class="wrap po-rocnik">
    <div>
      <?= hlava_sekce($bRocnik, ['cislo' => 1, 'id' => 'rocnik-nadpis', 'nadpis' => 'Ročník v kostce', 'tag' => 'h2']) ?>
      <?= blok_text($bRocnik, 'prose udaje') ?>
      <?= blok_tlacitka($bRocnik) ?>
    </div>
    <?php if ($bVitezove['existuje']): ?>
    <div class="karta karta--zlata po-karta">
      <h3 class="h3 po-karta__titul"><?= trim((string)$bVitezove['nadpis']) !== '' ? html_inline((string)$bVitezove['nadpis']) : 'Vítězové' ?></h3>
      <?php if (trim((string)$bVitezove['perex']) !== ''): ?><div class="male"><?= paragraphs((string)$bVitezove['perex']) ?></div><?php endif; ?>
      <?= blok_text($bVitezove, 'prose udaje udaje--vitezove') ?>
      <?= blok_tlacitka($bVitezove) ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($bHistorie['existuje']): ?>
<!-- II · Historie turnaje -->
<section class="sekce" id="historie" aria-labelledby="historie-nadpis">
  <div class="wrap po-historie">
    <div>
      <?= hlava_sekce($bHistorie, ['cislo' => 2, 'id' => 'historie-nadpis', 'nadpis' => 'Historie turnaje']) ?>
      <?= blok_text($bHistorie, 'prose udaje') ?>
      <?= blok_tlacitka($bHistorie) ?>
    </div>
    <?= blok_foto($bHistorie, 'pomer-4x3') ?>
  </div>
</section>
<?php endif; ?>

<?php if ($webTurnaje !== ''): ?>
<!-- Web turnaje -->
<section class="sekce sekce--navy sekce--tesna po-web" aria-label="Web turnaje">
  <div class="wrap po-web__vnitrek">
    <p class="perex">Aktuální informace o&nbsp;turnaji najdete na jeho webu.</p>
    <?= tlacitko($webTurnaje, trim((string)$uvod['odkaz_text']) ?: 'Web turnaje', 'btn', ['trida' => 'btn--svetla']) ?>
  </div>
</section>
<?php endif; ?>

<?= stranka_dalsi_bloky('prague-open', ['uvod', 'rocnik', 'vitezove', 'historie']) ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
