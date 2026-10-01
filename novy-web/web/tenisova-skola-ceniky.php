<?php
/* Tenisová škola – ceníky. Ceník školy z modulu Ceníky (klíč „skola“: zimní a letní
   období, členství Tenisové školy), vedle něj podmínky platby (Tenisová škola →
   Informace, odstavce o platbě a počasí), účet z Textů a údajů a odkaz na ceník
   letních kempů (klíč „kempy“). Hlavička = blok „tenisova-skola-ceniky / uvod“. */
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
/* ---------- konec pomůcek ---------- */

$uvod   = blok('tenisova-skola-ceniky', 'uvod');
$cenik  = cenik('skola');
$kempy  = cenik('kempy');
$ucet   = setting('ucet_skola');
$dokumenty = dokumenty('skola');

/* Podmínky k ceníku: odstavce Informací školy, které mluví o platbě, ceně a náhradách. */
$podminky = array_values(array_filter(skola('info'), static fn(array $r): bool =>
    (bool)preg_match('/platb|plat[íi]|cen[ay]|náhrad|počas|vyúčt/iu', (string)$r['nazev'] . ' ' . (string)$r['text'])));

$sablona = [
    'titulek' => 'Ceník Tenisové školy',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-tenis.css'],
    'trida'   => 'stranka-tenis stranka-tenisova-skola-ceniky',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky(array_merge($uvod, ['odkaz' => '', 'odkaz2' => '']), ['drobky' => [['Tenisová škola', 'tenisova-skola.php'], ['Ceníky']], 'nadpis' => 'Ceník Tenisové školy', 'navic' => blok_tlacitka($uvod) . tenis_podnav()]) ?>

<section class="sekce sekce--bez-horni" id="cenik" aria-labelledby="cenik-nadpis">
  <div class="wrap">
    <h2 class="vh" id="cenik-nadpis">Ceník a&nbsp;podmínky</h2>
    <div class="mrizka">
      <div class="sl-8 tenis-cenik">
        <?= cenik_html($cenik, ['id' => 'cenik-skola', 'nadpis' => false]) ?>
      </div>
      <aside class="sl-4 zasobnik tenis-ceniky-aside" aria-label="Podmínky a platba">
        <?php if ($podminky || $ucet !== ''): ?>
        <div class="karta karta--papir2">
          <p class="stitek karta__stitek">Podmínky a&nbsp;platba</p>
          <?php foreach ($podminky as $r): ?>
          <h3 class="karta__nazev"><?= typo((string)$r['nazev']) ?></h3>
          <div class="tenis-karta-text"><?= paragraphs((string)$r['text']) ?></div>
          <?php endforeach; ?>
          <?php if ($ucet !== ''): ?>
          <ul class="radky tenis-ucet">
            <li class="radek"><span class="radek__nazev">Účet Tenisové školy</span><span class="radek__hodnota"><?= e($ucet) ?></span></li>
          </ul>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($kempy): ?>
        <a class="karta karta--zlata tenis-rozcestnik__karta" href="<?= e(url('letni-kempy.php') . '#ceny') ?>">
          <p class="stitek karta__stitek">Letní kempy</p>
          <h3 class="karta__nazev"><?= typo((string)$kempy['list']['nazev']) ?></h3>
          <?php if (trim((string)$kempy['list']['obdobi']) !== ''): ?><p class="tenis-rozcestnik__perex"><?= typo((string)$kempy['list']['obdobi']) ?></p><?php endif; ?>
          <p class="tenis-rozcestnik__dal"><span class="odkaz" aria-hidden="true">Ceník kempů <?= sipka() ?></span></p>
        </a>
        <?php endif; ?>
        <?php if ($dokumenty): ?>
        <div>
          <p class="stitek tenis-kontakt__nadpis">Ke stažení</p>
          <?= dokumenty_html($dokumenty) ?>
        </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
