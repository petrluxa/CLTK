<?php
/* Tenisová škola – rozvrhy a harmonogram. Harmonogram sezóny (modul Tenisová škola →
   Harmonogram: začátek rozvrhů, prázdniny, dny bez tréninku) s vyznačením toho, co už
   proběhlo a co právě platí; rozvrhy skupin (→ Rozvrhy: den, čas, skupina, kurt, trenér)
   po dnech. Dokud klub rozvrhy nezadá, stránka ukáže jejich strukturu a „doplní klub“.
   Jména dětí se na web nedávají – rozvrh je jen po skupinách. */
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
if (!function_exists('tenis_termin')) {
    /** Termín „29. 6. – 3. 7. 2026“ jako HTML – uvnitř data nezlomitelné mezery. */
    function tenis_termin(?string $od, ?string $do, bool $sRokem = true): string {
        $t = (string)preg_replace('/\.\s(?=\d)/u', ".\u{00A0}", cz_range($od, $do, $sRokem));
        return str_replace("\u{00A0}", '&nbsp;', e($t));
    }
}
/* ---------- konec pomůcek ---------- */

$S = 'tenisova-skola-rozvrhy';
$uvod         = blok($S, 'uvod');
$bHarmonogram = blok($S, 'harmonogram');
$bRozvrhy     = blok($S, 'rozvrhy');
$bKontakt     = blok('tenisova-skola', 'kontakt');
/* Blok, který slouží jen jako nadpis sekce (obsah je z jiného modulu): když chybí, nemá hlásit „doplní klub“. */
$jenHlava = static fn(array $b): array => $b['existuje'] ? $b : ['doplni_klub' => 0] + $b;
$dnes = dnes();

/* Harmonogram: stav podle dnešního data (konec = datum_do, jinak datum_od). */
$harmonogram = [];
foreach (skola('harmonogram') as $r) {
    $od = (string)($r['datum_od'] ?? '');
    $do = (string)($r['datum_do'] ?? '') ?: $od;
    $r['stav'] = '';
    if ($od !== '') {
        if ($do < $dnes) $r['stav'] = 'probehlo';
        elseif ($od <= $dnes) $r['stav'] = 'ted';
    }
    $harmonogram[] = $r;
}

/* Rozvrh po dnech (pořadí dnů v týdnu, bez dne na konci); prázdné sloupce se nevypisují.
   Stav „doplní klub“ ukazuje tabulka sama, hlava sekce ho proto neopakuje. */
$poradiDnu = array_flip(['pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota', 'neděle']);
$rozvrh = skola('rozvrh');
$poDnech = [];
foreach ($rozvrh as $r) $poDnech[trim((string)$r['den'])][] = $r;
uksort($poDnech, static fn($a, $b) => ($poradiDnu[$a] ?? 99) <=> ($poradiDnu[$b] ?? 99));
$sloupce = ['cas' => 'Čas', 'skupina' => 'Skupina', 'misto' => 'Kurt', 'trener' => 'Trenér', 'text' => 'Poznámka'];
if ($rozvrh) {
    foreach (array_keys($sloupce) as $k) {
        if (!array_filter($rozvrh, fn($r) => trim((string)$r[$k]) !== '')) unset($sloupce[$k]);
    }
    $bRozvrhy['doplni_klub'] = 0;          // rozvrhy už jsou – štítek „doplní klub“ by mátl
}

$sablona = [
    'titulek' => 'Rozvrhy Tenisové školy',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-tenis.css'],
    'trida'   => 'stranka-tenis stranka-tenisova-skola-rozvrhy',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
$sekce = 0;
?>

<?= hlava_stranky(array_merge($uvod, ['odkaz' => '', 'odkaz2' => '']), ['drobky' => [['Tenisová škola', 'tenisova-skola.php'], ['Rozvrhy']], 'nadpis' => 'Rozvrhy Tenisové školy', 'navic' => blok_tlacitka($uvod) . tenis_podnav()]) ?>

<?php $sekce++; ?>
<!-- Harmonogram sezóny -->
<section class="sekce sekce--bez-horni" id="harmonogram" aria-labelledby="harmonogram-nadpis">
  <div class="wrap">
    <div class="mrizka">
      <div class="sl-4">
        <?= hlava_sekce($jenHlava($bHarmonogram), ['cislo' => $sekce, 'id' => 'harmonogram-nadpis', 'stitek' => 'Harmonogram', 'nadpis' => 'Harmonogram sezóny']) ?>
      </div>
      <div class="sl-8">
        <?php if ($harmonogram): ?>
        <ol class="tenis-harmonogram" role="list">
          <?php foreach ($harmonogram as $r):
            $kdy = tenis_termin($r['datum_od'] ?? null, $r['datum_do'] ?? null);
            $termin = trim((string)$r['termin_text']); ?>
          <li class="<?= $r['stav'] === 'probehlo' ? 'je-probehla' : ($r['stav'] === 'ted' ? 'je-ted' : '') ?>">
            <span class="tenis-harmonogram__kdy"><?php if ($kdy !== ''): ?><time datetime="<?= e(substr((string)$r['datum_od'], 0, 10)) ?>"><?= $kdy ?></time><?php if ($termin !== ''): ?><small><?= typo($termin) ?></small><?php endif; ?><?php else: ?><?= $termin !== '' ? typo($termin) : doplni_klub('termín doplní klub') ?><?php endif; ?></span>
            <span class="tenis-harmonogram__co"><?= typo((string)$r['nazev']) ?><?php if (trim((string)$r['text']) !== ''): ?><small><?= typo((string)$r['text']) ?></small><?php endif; ?></span>
            <span class="tenis-harmonogram__stav"><?php if ($r['stav'] === 'ted'): ?><span class="tenis-stav tenis-stav--ted">právě platí</span><?php elseif ($r['stav'] === 'probehlo'): ?><span class="tenis-stav tenis-stav--probehlo">proběhlo</span><?php endif; ?></span>
          </li>
          <?php endforeach; ?>
        </ol>
        <?php else: ?>
        <p><?= doplni_klub('harmonogram sezóny doplní klub') ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php $sekce++; ?>
<!-- Rozvrhy skupin -->
<section class="sekce sekce--papir2" id="rozvrhy" aria-labelledby="rozvrhy-nadpis">
  <div class="wrap">
    <div class="mrizka">
      <div class="sl-4">
        <?= hlava_sekce(array_merge($bRozvrhy, ['doplni_klub' => 0]), ['cislo' => $sekce, 'id' => 'rozvrhy-nadpis', 'stitek' => 'Rozvrhy', 'nadpis' => 'Rozvrhy skupin']) ?>
        <?= blok_text(array_merge($bRozvrhy, ['doplni_klub' => 0])) ?>
      </div>
      <div class="sl-8 tenis-rozvrh">
        <div class="tabulka-box">
          <table class="tabulka">
            <caption class="vh">Rozvrh skupin Tenisové školy po dnech</caption>
            <thead>
              <tr>
                <th scope="col">Den</th>
                <?php foreach ($sloupce as $nazev): ?><th scope="col"><?= e($nazev) ?></th><?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rozvrh): ?>
              <tr class="tenis-rozvrh__prazdne"><td colspan="<?= count($sloupce) + 1 ?>"><?= doplni_klub('rozvrhy skupin doplní klub') ?></td></tr>
              <?php endif; ?>
              <?php foreach ($poDnech as $den => $radky): ?>
                <?php foreach ($radky as $i => $r): ?>
              <tr>
                <?php if ($i === 0): ?><th scope="rowgroup" rowspan="<?= count($radky) ?>"><?= $den !== '' ? e(mb_strtoupper(mb_substr($den, 0, 1)) . mb_substr($den, 1)) : '–' ?></th><?php endif; ?>
                <?php foreach (array_keys($sloupce) as $k): ?>
                <td<?= $k === 'cas' ? ' class="tenis-rozvrh__cas"' : '' ?>><?= trim((string)$r[$k]) !== '' ? typo((string)$r[$k]) : '–' ?></td>
                <?php endforeach; ?>
              </tr>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (trim((string)$bKontakt['text']) !== ''): ?>
        <div class="karta tenis-rozvrh__kontakt">
          <p class="stitek karta__stitek"><?= typo(trim((string)$bKontakt['nadpis']) !== '' ? html_text((string)$bKontakt['nadpis']) : 'Kontakt') ?></p>
          <?= blok_text($bKontakt) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
