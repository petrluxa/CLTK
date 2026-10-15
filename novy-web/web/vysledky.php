<?php
/* Výsledky našich hráčů – všechny výsledky ze služby Pointeks (inc/pointeks.php): nejnovější první
   (konec zápasu v pražském čase, živé zápasy úplně nahoře), 30 zápasů na stránku, filtr podle hráče
   (playerSlug) a kategorie junioři / dospělí (ageCategory vyplněná / prázdná). Stejný řádek zápasu
   jako tabule na úvodní stránce (pointeks_tabule_html() v inc/sablona/komponenty.php).
   V menu pod Závodním tenisem jako „Výsledky“. Hlavička = blok „vysledky / uvod“ (modul Stránky),
   bez něj výchozí texty. Parametry adresy: ?hrac=slug&kategorie=juniori|dospeli&strana=2. */
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

/** Stránkování: ‹ Novější · 1 … 4 5 6 … 12 · Starší › (stránka 1 = nejnovější výsledky). */
function vysl_strankovani(int $strana, int $stran, callable $odkaz): string {
    if ($stran <= 1) return '';
    $cisla = $stran <= 7 ? range(1, $stran) : array_values(array_unique(array_filter(
        [1, 2, $strana - 1, $strana, $strana + 1, $stran - 1, $stran], static fn(int $x): bool => $x >= 1 && $x <= $stran)));
    sort($cisla);
    $h = '<nav aria-label="Stránky výsledků"><ul class="strankovani">';
    if ($strana > 1) $h .= '<li><a class="strankovani__smer" href="' . e($odkaz($strana - 1)) . '" rel="prev">' . sipka('zpet') . ' Novější</a></li>';
    $predchozi = 0;
    foreach ($cisla as $c) {
        if ($predchozi && $c > $predchozi + 1) $h .= '<li><span class="strankovani__mezera" aria-hidden="true">…</span></li>';
        $h .= $c === $strana
            ? '<li><span aria-current="page"><span class="vh">Strana </span>' . $c . '</span></li>'
            : '<li><a href="' . e($odkaz($c)) . '"><span class="vh">Strana </span>' . $c . '</a></li>';
        $predchozi = $c;
    }
    if ($strana < $stran) $h .= '<li><a class="strankovani__smer" href="' . e($odkaz($strana + 1)) . '" rel="next">Starší ' . sipka() . '</a></li>';
    return $h . '</ul></nav>';
}

/* ---------- data a filtr (parametry z adresy se ověří, neznámé = bez filtru) ---------- */
$uvod = blok('vysledky', 'uvod');
if (!$uvod['existuje']) {                             // hlavička má výchozí texty, „doplní klub“ sem nepatří
    $uvod['doplni_klub'] = 0;
    $uvod['perex'] = "Zápasy našich hráčů\u{00A0}– juniorů i dospělých\u{00A0}– v turnajích Tennis Europe, ITF, ATP a WTA. "
                   . 'Výsledky přicházejí automaticky, jakmile zápas skončí.';    // nezlomitelná mezera: pomlčka nezačne řádek
}

const VYSL_KATEGORIE = ['juniori' => 'Junioři', 'dospeli' => 'Dospělí'];
$hraci = pointeks_hraci();
$hrac = vstup_get('hrac');
if ($hrac !== '' && !isset($hraci[$hrac])) $hrac = '';
$kategorie = vstup_get('kategorie');
if (!isset(VYSL_KATEGORIE[$kategorie])) $kategorie = '';
$strana = vstup_get('strana');
$strana = preg_match('/^[1-9]\d{0,4}$/', $strana) ? (int)$strana : 1;

$seznam = pointeks_seznam(['hrac' => $hrac, 'kategorie' => $kategorie, 'strana' => $strana, 'limit' => POINTEKS_NA_STRANKU]);
$filtr = $hrac !== '' || $kategorie !== '';
$odkazStrany = static function (int $s) use ($hrac, $kategorie): string {
    $q = array_filter(['hrac' => $hrac, 'kategorie' => $kategorie, 'strana' => $s > 1 ? (string)$s : ''], static fn(string $x): bool => $x !== '');
    return url('vysledky.php' . ($q ? '?' . http_build_query($q) : ''));
};
$nadpisTabule = $hrac !== '' ? $hraci[$hrac] : 'Všechny výsledky';
$stavTabule = cislo($seznam['celkem']) . ' ' . sklonuj($seznam['celkem'], 'zápas', 'zápasy', 'zápasů')
    . ($kategorie !== '' ? ' · ' . mb_strtolower(VYSL_KATEGORIE[$kategorie]) : '')
    . ($seznam['stran'] > 1 ? ' · strana ' . $seznam['strana'] . ' z ' . $seznam['stran'] : '');

$sablona = [
    'titulek' => 'Výsledky našich hráčů' . ($hrac !== '' ? ' – ' . $hraci[$hrac] : '') . ($seznam['strana'] > 1 ? ' (strana ' . $seznam['strana'] . ')' : ''),
    'popis'   => html_text((string)$uvod['perex']) ?: 'Výsledky zápasů hráčů I. ČLTK Praha – juniorů i dospělých – z turnajů, kde hrají: Tennis Europe, ITF, ATP a WTA.',
    'css'     => ['stranky-tenis.css', 'vysledky.css'],
    'trida'   => 'stranka-tenis stranka-vysledky',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky(array_merge($uvod, ['odkaz' => '', 'odkaz2' => '']), ['drobky' => [['Závodní tenis', 'zavodni-tenis.php'], ['Výsledky']],
    'stitek' => 'Závodní tenis', 'nadpis' => 'Výsledky našich hráčů', 'navic' => blok_tlacitka($uvod) . tenis_podnav()]) ?>

<section class="sekce sekce--bez-horni" id="vysledky" aria-labelledby="vysledky-nadpis">
  <div class="wrap"><!-- stejný levý okraj jako hlavička stránky (drobečky, nadpis, pilulky) -->
    <h2 class="vh" id="vysledky-nadpis">Výsledky zápasů</h2>
    <?php if ($hraci): ?>
    <form class="vysl-filtr" method="get" action="<?= e(url('vysledky.php')) ?>#vysledky" role="search" aria-label="Filtr výsledků">
      <div class="pole">
        <label for="vysl-hrac">Hráč</label>
        <select id="vysl-hrac" name="hrac">
          <option value="">Všichni hráči</option>
          <?php foreach ($hraci as $slug => $jmeno): ?>
          <option value="<?= e((string)$slug) ?>"<?= (string)$slug === $hrac ? ' selected' : '' ?>><?= e($jmeno) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="pole">
        <label for="vysl-kategorie">Kategorie</label>
        <select id="vysl-kategorie" name="kategorie">
          <option value="">Junioři i dospělí</option>
          <?php foreach (VYSL_KATEGORIE as $k => $n): ?>
          <option value="<?= e($k) ?>"<?= $k === $kategorie ? ' selected' : '' ?>><?= e($n) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="vysl-filtr__akce">
        <button class="btn" type="submit">Zobrazit</button>
        <?php if ($filtr): ?><a class="odkaz" href="<?= e(url('vysledky.php')) ?>">Zrušit filtr</a><?php endif; ?>
      </div>
    </form>
    <?php endif; ?>

    <?php if ($seznam['zapasy']): ?>
    <?= pointeks_tabule_html($seznam['zapasy'], ['nadpis' => $nadpisTabule, 'stav' => $stavTabule]) ?>
    <?= vysl_strankovani($seznam['strana'], $seznam['stran'], $odkazStrany) ?>
    <?php elseif ($filtr && $seznam['celkem'] === 0 && $hraci): ?>
    <div class="vysl-prazdne">
      <p>Pro tenhle výběr zatím žádný výsledek nemáme.</p>
      <p><a class="odkaz" href="<?= e(url('vysledky.php')) ?>">Všechny výsledky <?= sipka() ?></a></p>
    </div>
    <?php else: ?>
    <div class="vysl-prazdne">
      <p>Výsledky se zatím nenačetly.</p>
      <p>Výsledky zápasů našich hráčů sem přicházejí automaticky, jakmile zápas skončí.</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
