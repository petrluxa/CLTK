<?php
/* Sportovní lékařství – fyzioterapie, masáže a sportovní lékařství ve wellness centru areálu.
   Obsah zatím doplňuje klub (blok „uvod“ má doplni_klub = 1).
   Obsah: Stránky (bloky „sportovni-lekarstvi“ – uvod, kontakt a každý další blok,
   který klub na stránce založí), Areál a služby (služba s kotvou
   „fyzioterapie“ – text, fakta, časy, fotka; wellness), Vedení (další kontakty:
   osoba, jejíž funkce zmiňuje fyzioterapii, se ukáže jako kontakt). */
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
if (!function_exists('sluzba_v_arealu_html')) {
    /** Karta služby z modulu Areál a služby (název, text, fakta, časy nebo „doplní klub“, odkaz na areál). */
    function sluzba_v_arealu_html(?array $s, string $stitek = 'V areálu'): string {
        if (!$s) return '';
        $foto = obr((string)$s['foto'], (string)$s['nazev'], ['class' => 'foto', 'fokus' => (string)$s['fokus']]);
        return '<div class="karta karta--papir2 sluzba-karta">'
            . ($foto !== '' ? '<figure class="ramec ramec--linka sluzba-karta__foto"><div class="ramec__obraz pomer-3x2">' . $foto . '</div></figure>' : '')
            . '<p class="stitek karta__stitek">' . typo($stitek) . '</p>'
            . '<h3 class="karta__nazev">' . typo((string)$s['nazev']) . '</h3>'
            . (trim((string)$s['text']) !== '' ? '<div class="male">' . paragraphs((string)$s['text']) . '</div>' : (trim((string)$s['perex']) !== '' ? '<p class="male">' . typo((string)$s['perex']) . '</p>' : ''))
            . radky_seznam_typo((string)$s['fakta'], 'sluzba__fakta')
            . '<p class="sluzba__casy"><span class="stitek">Časy</span><span>' . (trim((string)$s['casy']) !== '' ? typo((string)$s['casy']) : doplni_klub()) . '</span></p>'
            . '<div class="odkazy-radek">' . tlacitko('areal.php#' . $s['kotva'], 'V plánu areálu', 'odkaz') . '</div>'
            . '</div>';
    }
}

/* ---------- data ---------- */
$uvod     = blok('sportovni-lekarstvi', 'uvod');
$bKontakt = blok('sportovni-lekarstvi', 'kontakt');
$podleKotvy = [];
foreach (sluzby() as $s) $podleKotvy[(string)$s['kotva']] = $s;
$sluzba   = $podleKotvy['fyzioterapie'] ?? null;
$wellness = $podleKotvy['wellness'] ?? null;
/* kontakt na fyzioterapii z modulu Vedení (další kontakty), když tam je */
$fyzio = array_values(array_filter(vedeni('kontakt'), fn($o) => preg_match('/fyzioterap|lékař/iu', (string)$o['funkce'])));

$sablona = [
    'titulek' => html_text((string)$uvod['stitek']) ?: 'Sportovní lékařství',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-areal.css'],
    'trida'   => 'stranka-sportovni-lekarstvi',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvod, ['drobky' => [['Areál a služby', 'areal.php'], ['Sportovní lékařství']], 'nadpis' => 'Sportovní lékařství']) ?>

<!-- I · Kontakt a objednání + služba v areálu -->
<section class="sekce sekce--linka" id="kontakt" aria-labelledby="kontakt-nadpis">
  <div class="wrap v-arealu">
    <div class="v-arealu__text">
      <?= hlava_sekce($bKontakt, ['cislo' => 1, 'id' => 'kontakt-nadpis', 'nadpis' => 'Kontakt a objednání', 'stitek' => 'Kontakt']) ?>
      <?= trim(html_text((string)$bKontakt['text'])) !== '' ? blok_text($bKontakt) : '' ?>
      <?php if ($fyzio): ?>
      <ul class="osoby osoby--bez-fotek osoby--kontakt"><?php foreach ($fyzio as $o) echo osoba_html($o, ['foto' => false]); ?></ul>
      <?php endif; ?>
      <?= blok_tlacitka($bKontakt) ?>
      <div class="odkazy-radek dalsi-odkazy">
        <?= tlacitko('body-solution.php', 'Body Solution', 'odkaz') ?>
        <?= $wellness ? tlacitko('areal.php#' . $wellness['kotva'], (string)$wellness['nazev'], 'odkaz') : '' ?>
      </div>
    </div>
    <?php if ($sluzba): ?><div><?= sluzba_v_arealu_html($sluzba) ?></div><?php endif; ?>
  </div>
</section>

<?= stranka_dalsi_bloky('sportovni-lekarstvi', ['uvod', 'kontakt']) ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
