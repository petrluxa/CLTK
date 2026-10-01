<?php
/* Privátní trenéři – výuka pro rekreační hráče. Obsah zatím doplňuje klub
   (blok „uvod“ má doplni_klub = 1 → štítek „obsah doplní klub“).
   Obsah: modul Trenéři (zařazení „Privátní trenéři“, podnadpisy podle sloupce
   skupina), Stránky (bloky „privatni-treneri“ – uvod, treneri, kontakt a každý
   další blok, který klub na stránce založí), Areál a služby (výuka tenisu),
   Texty a údaje (recepce). */
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
    /**
     * Bloky, které klub založí v modulu Stránky navíc (mimo klíče, které šablona
     * čte sama) – každý jako sekce: štítek, nadpis, perex, text z editoru, fotka, tlačítka.
     */
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
$uvod    = blok('privatni-treneri', 'uvod');
$bTreneri = blok('privatni-treneri', 'treneri');
$bKontakt = blok('privatni-treneri', 'kontakt');
$treneri = treneri('privatni');
$vyuka   = array_values(array_filter(sluzby(), fn($s) => $s['kotva'] === 'vyuka'))[0] ?? null;

/* trenéři podle podnadpisu (sloupec skupina), v pořadí z administrace */
$skupiny = [];
foreach ($treneri as $t) {
    /* fakta vypisuje osoba_html() přes radky_seznam() bez typografie – nezlomitelné
       mezery (U+00A0) se doplní předem, e() je ponechá */
    $t['fakta'] = typo_text((string)$t['fakta']);
    $skupiny[trim((string)$t['skupina'])][] = $t;
}

$recTel  = setting('recepce_telefon');
$recMail = setting('recepce_email');

$sablona = [
    'titulek' => html_text((string)$uvod['stitek']) ?: 'Privátní trenéři',
    'popis'   => html_text((string)$uvod['perex']) . ($treneri ? ' Trenéři: ' . implode(', ', array_column($treneri, 'jmeno')) . '.' : ''),
    'css'     => ['stranky-areal.css'],
    'trida'   => 'stranka-treneri',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvod, ['drobky' => [['Areál a služby', 'areal.php'], ['Privátní trenéři']], 'nadpis' => 'Privátní trenéři']) ?>

<!-- I · Trenéři -->
<section class="sekce sekce--papir2" id="treneri" aria-labelledby="treneri-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bTreneri, ['cislo' => 1, 'id' => 'treneri-nadpis', 'nadpis' => 'Trenéři', 'stitek' => 'Trenéři']) ?>
    <?php if ($skupiny): ?>
      <?php foreach ($skupiny as $nazevSkupiny => $osoby): ?>
      <div class="skupina-osob">
        <?php if ($nazevSkupiny !== '' && count($skupiny) > 1): ?><h3 class="h5 skupina-osob__nazev"><?= typo($nazevSkupiny) ?></h3><?php endif; ?>
        <ul class="osoby"><?php foreach ($osoby as $t) echo osoba_html($t); ?></ul>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p><?= doplni_klub('seznam trenérů doplní klub') ?></p>
    <?php endif; ?>
  </div>
</section>

<!-- II · Ceny, kontakt a výuka v areálu -->
<section class="sekce" id="kontakt" aria-labelledby="kontakt-nadpis">
  <div class="wrap v-arealu">
    <div class="v-arealu__text">
      <?= hlava_sekce($bKontakt, ['cislo' => 2, 'id' => 'kontakt-nadpis', 'nadpis' => 'Ceny a kontakt', 'stitek' => 'Kontakt']) ?>
      <?= trim(html_text((string)$bKontakt['text'])) !== '' ? blok_text($bKontakt) : '' ?>
      <?php if ($recTel !== '' || je_email($recMail)): ?>
      <ul class="radky">
        <li class="radek radek--vodici"><span class="radek__nazev">Kurt k&nbsp;výuce</span><span class="radek__hodnota"><a href="<?= e(url('cenik-kurtu.php')) ?>">Ceník kurtů</a></span><span class="radek__pozn">Pronájem kurtu přes recepci nebo online.</span></li>
        <?php if ($recTel !== ''): ?><li class="radek radek--vodici"><span class="radek__nazev">Recepce</span><span class="radek__hodnota"><a href="<?= e(tel_href($recTel)) ?>"><?= e($recTel) ?></a></span></li><?php endif; ?>
        <?php if (je_email($recMail)): ?><li class="radek radek--vodici"><span class="radek__nazev">E-mail</span><span class="radek__hodnota"><a href="mailto:<?= e($recMail) ?>"><?= e($recMail) ?></a></span></li><?php endif; ?>
      </ul>
      <?php endif; ?>
      <?= blok_tlacitka($bKontakt) ?>
    </div>
    <?php if ($vyuka): ?>
    <div>
      <div class="karta karta--papir2 sluzba-karta">
        <p class="stitek karta__stitek">V&nbsp;areálu</p>
        <h3 class="karta__nazev"><?= typo((string)$vyuka['nazev']) ?></h3>
        <?php if (trim((string)$vyuka['text']) !== ''): ?><div class="male"><?= paragraphs((string)$vyuka['text']) ?></div><?php endif; ?>
        <?= radky_seznam_typo((string)$vyuka['fakta'], 'sluzba__fakta') ?>
        <div class="odkazy-radek">
          <?= tlacitko('tenisova-skola.php', 'Tenisová škola', 'odkaz') ?>
          <?= tlacitko('zavodni-tenis.php', 'Závodní tenis', 'odkaz') ?>
          <?= tlacitko('letni-kempy.php', 'Letní kempy', 'odkaz') ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?= stranka_dalsi_bloky('privatni-treneri', ['uvod', 'treneri', 'kontakt']) ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
