<?php
/* Tenisová škola – trenérský tým. Trenéři ze správy (modul Trenéři, zařazení
   „tenisová škola“) s portrétem, rolí, fakty a kontaktem; když mají vyplněnou
   skupinu, seskupí se podle ní. Hlavička = blok „tenisova-skola-treneri / uvod“. */
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
if (!function_exists('tenis_karta_stranky')) {
    /** Karta odkazu na jinou stránku webu: název stránky, nadpis a perex z jejího bloku „uvod“
     *  (perex zkrácený na celé věty, nejvýš asi 180 znaků). */
    function tenis_karta_stranky(string $soubor, string $nazevStranky): string {
        $b = blok(preg_replace('/\.php$/', '', $soubor), 'uvod');
        $nadpis = trim((string)$b['nadpis']) !== '' ? html_inline((string)$b['nadpis']) : typo($nazevStranky);
        $perex = html_text((string)$b['perex']);
        if (mb_strlen($perex) > 180) {
            $perex = preg_match('/^(.{40,180}?[.!?…])(?=\s+\p{Lu})/u', $perex, $m) ? $m[1] : uryvek($perex, 150);
        }
        return '<li><a class="karta tenis-rozcestnik__karta" href="' . e(url($soubor)) . '">'
             . '<p class="stitek karta__stitek">' . typo($nazevStranky) . '</p>'
             . '<h3 class="karta__nazev">' . $nadpis . '</h3>'
             . ($perex !== '' ? '<p class="tenis-rozcestnik__perex">' . typo($perex) . '</p>' : '')
             . '<p class="tenis-rozcestnik__dal"><span class="odkaz" aria-hidden="true">Otevřít stránku ' . sipka() . '</span></p>'
             . '</a></li>';
    }
}
/* ---------- konec pomůcek ---------- */

$uvod = blok('tenisova-skola-treneri', 'uvod');
/* fakta jsou prostý text – typografii (nezlomitelné mezery) doplní typo_text() před výpisem */
$treneri = array_map(static fn(array $t): array => ['fakta' => typo_text((string)$t['fakta'])] + $t, treneri('skola'));
$skupiny = [];
foreach ($treneri as $t) $skupiny[trim((string)$t['skupina'])][] = $t;
$sFotkou = static fn(array $t): bool => trim((string)$t['foto']) !== '' && is_file(UPLOAD_DIR . '/' . ltrim((string)$t['foto'], '/'));
$prvniFoto = '';
foreach ($treneri as $t) if ($sFotkou($t)) { $prvniFoto = (string)$t['foto']; break; }

$sablona = [
    'titulek' => 'Trenéři Tenisové školy',
    'popis'   => html_text((string)$uvod['perex']) ?: 'Trenérský tým Tenisové školy I. ČLTK Praha.',
    'css'     => ['stranky-tenis.css'],
    'obrazek' => $prvniFoto,
    'trida'   => 'stranka-tenis stranka-tenisova-skola-treneri',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky(array_merge($uvod, ['odkaz' => '', 'odkaz2' => '']), ['drobky' => [['Tenisová škola', 'tenisova-skola.php'], ['Trenérský tým']], 'nadpis' => 'Trenéři Tenisové školy', 'navic' => blok_tlacitka($uvod) . tenis_podnav()]) ?>

<section class="sekce sekce--bez-horni" id="tym" aria-label="Trenéři Tenisové školy">
  <div class="wrap">
    <?php if (!$skupiny): ?>
    <p class="perex"><?= doplni_klub('trenérský tým doplní klub') ?></p>
    <?php endif; ?>
    <?php $i = 0; foreach ($skupiny as $nazev => $lide):
      $i++;
      $foto = (bool)array_filter($lide, $sFotkou); ?>
    <div class="skupina-osob tenis-skupina" id="<?= e($nazev !== '' ? 'skupina-' . slugify($nazev) : 'skupina-' . $i) ?>">
      <?php if ($nazev !== ''): ?>
      <h2 class="h4 skupina-osob__nazev"><span><?= typo($nazev) ?></span><span class="tenis-skupina__pocet"><?= count($lide) ?> <?= e(sklonuj(count($lide), 'trenér', 'trenéři', 'trenérů')) ?></span></h2>
      <?php else: ?>
      <h2 class="vh">Trenérský tým</h2>
      <?php endif; ?>
      <ul class="osoby tenis-osoby<?= $foto ? '' : ' osoby--bez-fotek' ?>" role="list">
        <?php foreach ($lide as $t) echo osoba_html($t, ['foto' => $foto]); ?>
      </ul>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="sekce sekce--linka" id="dalsi" aria-labelledby="dalsi-nadpis">
  <div class="wrap">
    <h2 class="stitek stitek--velky tenis-rozcestnik-nadpis" id="dalsi-nadpis">Kam dál</h2>
    <ul class="tenis-rozcestnik" role="list">
      <?= tenis_karta_stranky('tenisova-skola.php', 'Informace') ?>
      <?= tenis_karta_stranky('letni-kempy.php', 'Letní kempy') ?>
      <?= tenis_karta_stranky('zavodni-tenis-treneri.php', 'Trenéři závodního tenisu') ?>
      <?= tenis_karta_stranky('privatni-treneri.php', 'Privátní trenéři') ?>
    </ul>
  </div>
</section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
