<?php
/* Tenisová škola – rozvrhy a harmonogram. Harmonogram sezóny (modul Tenisová škola →
   Harmonogram: začátek rozvrhů, prázdniny, dny bez tréninku) s vyznačením toho, co už
   proběhlo a co právě platí; rozvrhy tréninků (→ Rozvrhy: jedna buňka = den, čas, kurt,
   skupina, trenéři) jako týdenní mřížka dny × hodiny pro každý kurt, na mobilu po dnech.
   Víc platných rozvrhů (přechodný týden + celá zima) = záložky; rozvrh po konci platnosti
   zmizí sám. Informace pro rodiče a kontaktní trenéři: blok „rozvrhy“ (modul Stránky).
   Jména dětí se na web nedávají – rozvrh je jen po hodinách a skupinách. */
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

const ROZVRH_DNY = ['pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota', 'neděle'];

/** „16:00–17:00“ → [16, 17] (celé hodiny), jinak null. */
function rozvrh_hodiny(string $cas): ?array {
    $c = str_replace(["\u{00A0}", ' '], '', $cas);
    if (!preg_match('/^(\d{1,2}):00[–—-](\d{1,2}):00$/u', $c, $m)) return null;
    $od = (int)$m[1]; $do = (int)$m[2];
    return $do > $od && $do <= 24 ? [$od, $do] : null;
}

/** Den s velkým písmenem („Pondělí“). */
function rozvrh_den(string $den): string {
    return mb_strtoupper(mb_substr($den, 0, 1)) . mb_substr($den, 1);
}

/**
 * Mřížka jednoho kurtu: ['hodiny' => [12, …, 18], 'dny' => ['pondělí' => [12 => ['span' => 1, 'polozky' => [řádky]], 13 => null…]]].
 * null = čas buňky není v celých hodinách – stránka pak ukáže prostý seznam po dnech.
 */
function rozvrh_mrizka(array $radky): ?array {
    $min = 99; $max = 0; $poDnech = [];
    foreach ($radky as $r) {
        $h = rozvrh_hodiny((string)$r['cas']);
        $den = mb_strtolower(trim((string)$r['den']));
        if (!$h || !in_array($den, ROZVRH_DNY, true)) return null;
        $min = min($min, $h[0]); $max = max($max, $h[1]);
        $poDnech[$den][$h[0]][] = $r + ['_do' => $h[1]];
    }
    if (!$poDnech) return null;
    $dny = [];
    foreach (ROZVRH_DNY as $den) {
        if (!isset($poDnech[$den])) continue;
        $bunky = [];
        $kryto = 0;
        for ($h = $min; $h < $max; $h++) {
            if ($h < $kryto) {                                      // pokračování buňky přes víc hodin
                if (!empty($poDnech[$den][$h])) $bunky[$posledni]['polozky'] = array_merge($bunky[$posledni]['polozky'], $poDnech[$den][$h]);
                continue;
            }
            $p = $poDnech[$den][$h] ?? [];
            $konec = $p ? max(array_column($p, '_do')) : $h + 1;
            $bunky[$h] = ['span' => $konec - $h, 'polozky' => $p];
            $kryto = $konec;
            $posledni = $h;
        }
        $dny[$den] = $bunky;
    }
    return ['hodiny' => range($min, $max - 1), 'dny' => $dny];
}

/** Obsah buňky mřížky: trénink (skupina, trenéři, poznámka) nebo obsazený kurt; souběžné skupiny pod sebou. */
function rozvrh_bunka(array $polozky): string {
    $h = '';
    foreach ($polozky as $r) {
        if (skola_rozvrh_obsazeno($r)) { $h .= '<span class="rozvrh-b rozvrh-b--obsazeno">obsazeno</span>'; continue; }
        $trener = trim((string)$r['trener']);
        $vice = (bool)preg_match('/^\s*[3-9]\s*tren/u', $trener);
        $h .= '<span class="rozvrh-b' . ($vice ? ' rozvrh-b--vice' : '') . '">'
            . '<b>' . (trim((string)$r['skupina']) !== '' ? typo((string)$r['skupina']) : 'trénink') . '</b>'
            . ($trener !== '' ? '<small>' . typo($trener) . '</small>' : '')
            . (trim((string)$r['text']) !== '' ? '<small class="rozvrh-b__pozn">' . typo((string)$r['text']) . '</small>' : '')
            . '</span>';
    }
    return $h;
}

/** Jedna položka seznamu po dnech (mobil, prostý rozvrh): „16:00–17:00 · trénink · 3 trenéři“.
 *  $soubezne > 1 = tolik stejných skupin ve stejné hodině („2 souběžné skupiny“). */
function rozvrh_polozka(array $r, int $soubezne = 1): string {
    $obs = skola_rozvrh_obsazeno($r);
    $co = $obs ? 'obsazeno' : (trim((string)$r['skupina']) !== '' ? typo((string)$r['skupina']) : 'trénink');
    $navic = array_filter([$soubezne > 1 ? $soubezne . ' ' . sklonuj($soubezne, 'souběžná skupina', 'souběžné skupiny', 'souběžných skupin') : '',
                           trim((string)$r['trener']), trim((string)$r['text'])], static fn($x) => $x !== '');
    return '<li class="' . ($obs ? 'je-obsazeno' : (preg_match('/^\s*[3-9]\s*tren/u', (string)$r['trener']) ? 'je-vice' : '')) . '">'
        . '<span class="rozvrh-den__cas">' . e(str_replace('-', '–', trim((string)$r['cas'])) ?: '–') . '</span>'
        . '<span class="rozvrh-den__co">' . $co . ($navic ? ' <small>' . implode(' · ', array_map('typo', $navic)) . '</small>' : '') . '</span></li>';
}

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

/* Rozvrhy: platné a budoucí, po kurtech; každý kurt jako mřížka (nebo seznam po dnech). */
$rozvrhy = skola_rozvrhy($dnes);
foreach ($rozvrhy as $i => &$rv) {
    $rv['id'] = 'rozvrh-' . ($i + 1);
    $rv['titulek'] = $rv['nazev'] !== '' ? $rv['nazev'] : 'Rozvrh tréninků';
    $rv['platnost'] = $rv['od'] !== '' ? tenis_termin($rv['od'], $rv['do'] !== '' ? $rv['do'] : null) : '';
    $rv['plati'] = ($rv['od'] === '' || $rv['od'] <= $dnes);
    $rv['mrizky'] = [];
    $trenerek = [];
    foreach ($rv['kurty'] as $kurt => $radky) {
        $rv['mrizky'][] = ['kurt' => $kurt, 'mrizka' => rozvrh_mrizka($radky), 'radky' => $radky];
        foreach ($radky as $r) if (trim((string)$r['trener']) !== '' && !skola_rozvrh_obsazeno($r)) $trenerek[trim((string)$r['trener'])] = true;
    }
    $rv['legenda'] = array_keys($trenerek);
    $rv['obsazeno'] = (bool)array_filter(array_merge(...array_values($rv['kurty'])), 'skola_rozvrh_obsazeno');
}
unset($rv);
if ($rozvrhy) $bRozvrhy['doplni_klub'] = 0;          // rozvrhy už jsou – štítek „doplní klub“ by mátl
$vychoziRozvrh = 0;
foreach ($rozvrhy as $i => $rv) { if ($rv['plati']) { $vychoziRozvrh = $i; break; } }

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
<!-- Rozvrhy tréninků: mřížka dny × hodiny pro každý kurt -->
<section class="sekce sekce--papir2" id="rozvrhy" aria-labelledby="rozvrhy-nadpis">
  <div class="wrap">
    <?= hlava_sekce(array_merge($bRozvrhy, ['doplni_klub' => 0]), ['cislo' => $sekce, 'id' => 'rozvrhy-nadpis', 'stitek' => 'Rozvrhy', 'nadpis' => 'Rozvrhy tréninků', 'radek' => true]) ?>

    <?php if (!$rozvrhy): ?>
    <p class="rozvrh-prazdny"><?= doplni_klub('rozvrhy tréninků doplní klub') ?></p>
    <?php else: ?>
    <div class="rozvrhy"<?= count($rozvrhy) > 1 ? ' data-zalozky' : '' ?>>
      <?php if (count($rozvrhy) > 1): ?>
      <div class="zalozky__seznam rozvrhy__zalozky" role="tablist" aria-label="Rozvrhy">
        <?php foreach ($rozvrhy as $i => $rv): $vybrany = $i === $vychoziRozvrh; ?>
        <button class="zalozka" type="button" role="tab" id="t-<?= e($rv['id']) ?>" aria-controls="<?= e($rv['id']) ?>" aria-selected="<?= $vybrany ? 'true' : 'false' ?>"<?= $vybrany ? '' : ' tabindex="-1"' ?> data-hash="<?= e($rv['id']) ?>"><?= typo($rv['titulek']) ?></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php foreach ($rozvrhy as $i => $rv): ?>
      <div class="rozvrh"<?= count($rozvrhy) > 1 ? ' role="tabpanel" aria-labelledby="t-' . e($rv['id']) . '" tabindex="0"' . ($i === $vychoziRozvrh ? '' : ' hidden') : '' ?> id="<?= e($rv['id']) ?>">
        <div class="rozvrh__hlava">
          <h3 class="rozvrh__nazev"><?= typo($rv['titulek']) ?></h3>
          <?php if ($rv['platnost'] !== ''): ?><p class="rozvrh__platnost"><?= $rv['plati'] ? 'Platí' : 'Bude platit' ?> <?= $rv['platnost'] ?></p><?php endif; ?>
        </div>

        <?php foreach ($rv['mrizky'] as $k): $mr = $k['mrizka']; $kurtNazev = $k['kurt'] !== '' ? $k['kurt'] : 'Kurt'; ?>
        <section class="rozvrh-kurt" aria-label="<?= e($rv['titulek'] . ' – ' . $kurtNazev) ?>">
          <h4 class="rozvrh-kurt__nazev"><?= typo($kurtNazev) ?></h4>
          <?php if ($mr): ?>
          <div class="tabulka-box rozvrh-mrizka-box" tabindex="0" role="region" aria-label="<?= e('Týdenní rozvrh – ' . $kurtNazev) ?>">
            <table class="rozvrh-mrizka" style="--hodin: <?= count($mr['hodiny']) ?>">
              <caption class="vh"><?= typo($rv['titulek'] . ' – ' . $kurtNazev) ?>: dny v týdnu a hodiny tréninků</caption>
              <thead>
                <tr><th scope="col"><span class="vh">Den</span></th><?php foreach ($mr['hodiny'] as $h): ?><th scope="col"><?= $h ?>–<?= $h + 1 ?></th><?php endforeach; ?></tr>
              </thead>
              <tbody>
                <?php foreach ($mr['dny'] as $den => $bunky): ?>
                <tr>
                  <th scope="row"><?= e(rozvrh_den($den)) ?></th>
                  <?php foreach ($bunky as $h => $b): ?>
                  <td<?= $b['span'] > 1 ? ' colspan="' . (int)$b['span'] . '"' : '' ?> class="<?= $b['polozky'] ? (count($b['polozky']) > 1 ? 'je-vic' : '') : 'je-volno' ?>"><?php if ($b['polozky']): ?><span class="vh"><?= e(rozvrh_den($den)) ?> <?= $h ?>:00–<?= $h + $b['span'] ?>:00: </span><?= rozvrh_bunka($b['polozky']) ?><?php else: ?><span class="vh">volno</span><?php endif; ?></td>
                  <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
          <?php /* seznam po dnech: na mobilu místo mřížky, bez mřížky (jiné než celé hodiny) vždy */
            $poDnech = [];
            foreach ($k['radky'] as $r) $poDnech[mb_strtolower(trim((string)$r['den']))][] = $r;
            uksort($poDnech, static fn($a, $b) => (array_search($a, ROZVRH_DNY, true) === false ? 99 : array_search($a, ROZVRH_DNY, true)) <=> (array_search($b, ROZVRH_DNY, true) === false ? 99 : array_search($b, ROZVRH_DNY, true))); ?>
          <ol class="rozvrh-dny<?= $mr ? ' rozvrh-dny--mobil' : '' ?>" role="list">
            <?php foreach ($poDnech as $den => $radky):
              usort($radky, static fn($a, $b) => strnatcmp((string)$a['cas'], (string)$b['cas'])); ?>
            <li class="rozvrh-den">
              <p class="rozvrh-den__nazev"><?= $den !== '' ? e(rozvrh_den($den)) : '–' ?></p>
              <ul class="rozvrh-den__hodiny" role="list"><?php
                /* souběžné stejné skupiny v jedné hodině (kurt 5 má hodinu na půlky) = jeden řádek s počtem */
                $skup = [];
                foreach ($radky as $r) {
                    $klic = implode('|', [trim((string)$r['cas']), skola_rozvrh_obsazeno($r) ? 'o' : '', trim((string)$r['skupina']), trim((string)$r['trener']), trim((string)$r['text'])]);
                    $skup[$klic] ??= ['r' => $r, 'n' => 0];
                    $skup[$klic]['n']++;
                }
                foreach ($skup as $x) echo rozvrh_polozka($x['r'], $x['n']); ?></ul>
            </li>
            <?php endforeach; ?>
          </ol>
        </section>
        <?php endforeach; ?>

        <?php if ($rv['legenda'] || $rv['obsazeno']): ?>
        <ul class="rozvrh-legenda" role="list" aria-label="Legenda rozvrhu">
          <li><span class="rozvrh-legenda__vzor" aria-hidden="true"></span>trénink</li>
          <?php foreach ($rv['legenda'] as $l): ?><li><span class="rozvrh-legenda__vzor<?= preg_match('/^\s*[3-9]\s*tren/u', $l) ? ' rozvrh-legenda__vzor--vice' : '' ?>" aria-hidden="true"></span><?= typo($l) ?></li><?php endforeach; ?>
          <?php if ($rv['obsazeno']): ?><li><span class="rozvrh-legenda__vzor rozvrh-legenda__vzor--obsazeno" aria-hidden="true"></span>kurt obsazený</li><?php endif; ?>
          <li class="rozvrh-legenda__pozn">dvě položky v&nbsp;jedné hodině = dvě souběžné skupiny</li>
        </ul>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php $infoText = blok_text(array_merge($bRozvrhy, ['doplni_klub' => 0]), 'prose rozvrh-info__text'); ?>
    <?php if ($infoText !== '' || trim((string)$bKontakt['text']) !== ''): ?>
    <div class="mrizka rozvrh-info">
      <?php if ($infoText !== ''): ?><div class="sl-7"><?= $infoText ?></div><?php endif; ?>
      <?php if (trim((string)$bKontakt['text']) !== ''): ?>
      <div class="<?= $infoText !== '' ? 'sl-4 od-9' : 'sl-5' ?>">
        <div class="karta tenis-rozvrh__kontakt">
          <p class="stitek karta__stitek"><?= typo(trim((string)$bKontakt['nadpis']) !== '' ? html_text((string)$bKontakt['nadpis']) : 'Kontakt') ?></p>
          <?= blok_text($bKontakt) ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
