<?php
/* Historie klubu – Tři wimbledonské trávy (triptych se skóre a wimbledonská
   linka), kronika po epochách s razítkem dobového jména (návrh 2 „Ostrov
   v čase“), Zlatá deska se záložkami a dvojím metrem (Varianta 4, sekce 9)
   a medailony „Od Žemly po Muchovou“ (Varianta 4, sekce 9b).
   Všechno z databáze: modul Historie (milníky, triptych, osobnosti, Zlatá
   deska) a bloky stránky „historie“ (modul Stránky). */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
track_visit();

/** Blok stránky (modul Stránky). Chybějící blok nedostane štítek „doplní klub“ –
    sekce použije náhradní nadpis, nebo se nevypíše (obsah stránky přitom je). */
function klub_blok(string $stranka, string $klic): array {
    $b = blok($stranka, $klic);
    if (empty($b['existuje'])) $b['doplni_klub'] = 0;
    return $b;
}

/** Položky prvního seznamu v textu bloku: [['hlava_text' => tučný začátek, 'text' => HTML zbytku], …] */
function hist_polozky(?string $html): array {
    $html = html_ocistit((string)$html);
    if ($html === '' || !preg_match('~<(ul|ol)>~', $html)) return [];
    $doc = new DOMDocument('1.0', 'UTF-8');
    $stary = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body><div id="k">' . $html . '</div></body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($stary);
    $v = [];
    foreach ($doc->getElementsByTagName('li') as $li) {
        $hlava = ''; $text = ''; $zacatek = true;
        foreach ($li->childNodes as $x) {
            if ($zacatek && $x->nodeType === XML_TEXT_NODE && trim($x->nodeValue, " \t\n\u{00A0}") === '') continue;
            if ($zacatek && $x instanceof DOMElement && $x->nodeName === 'strong') { $hlava = trim(str_replace("\u{00A0}", ' ', $x->textContent)); $zacatek = false; continue; }
            $zacatek = false;
            $text .= $doc->saveHTML($x);
        }
        $v[] = ['hlava_text' => $hlava, 'text' => trim((string)preg_replace('~^(\s|&nbsp;|\x{00A0})*[–—-]?(\s|&nbsp;|\x{00A0})*~u', '', trim($text))),
                'prosty' => trim(str_replace("\u{00A0}", ' ', $li->textContent))];
    }
    return $v;
}

/** „… doplní klub“ v už escapovaném textu → decentní štítek (jako ve Variantě 4). */
function hist_doplni(string $html): string {
    return (string)preg_replace('~doplní(\s|&nbsp;)+klub\.?~u', doplni_klub(), $html, 1);
}

/** Jedna část pramenu („R13/2 s. 8“, „I.ČLTK Revue 02/2013 – 120 let (https://…) s. 8“, adresa…) → čitelné HTML.
 *  Interní zkratky přepisu Revue (R13/2 = I. ČLTK Revue 02/2013) se rozepíšou, odkaz nese
 *  název pramenu, ne adresu. */
const HIST_WEBY = ['cztenis.cz' => 'registr ČTS', 'umeleckepamatky.udu.cas.cz' => 'Umělecké památky (ÚDU AV ČR)'];

function hist_pramen_cast(string $c, array &$jmena = []): string {
    $c = trim($c);
    if ($c === '') return '';
    $c = (string)preg_replace('~\bI\.\s*ČLTK\b~u', 'I. ČLTK', $c);
    $c = (string)preg_replace_callback('~\bR(\d{2})/(\d)\b~', static fn(array $m): string => 'I. ČLTK Revue 0' . $m[2] . '/20' . $m[1], $c);
    $odkaz = static function (string $u, string $text) use (&$jmena): string {
        $jmena[$text] = ($jmena[$text] ?? 0) + 1;              // stejný název podruhé → „registr ČTS (2)“
        if ($jmena[$text] > 1) $text .= ' (' . $jmena[$text] . ')';
        return '<a href="' . e($u) . '" target="_blank" rel="noopener">' . typo($text) . '<span class="vh"> (v novém okně)</span></a>';
    };
    // „Název (https://…) zbytek“ → odkaz s názvem
    if (preg_match('~^(.+?)\s*\((https?://[^\s()]+(?:\([^\s()]*\)[^\s()]*)*)\)\s*(.*)$~u', $c, $m)) {
        return $odkaz($m[2], trim($m[1])) . ($m[3] !== '' ? ' ' . typo($m[3]) : '');
    }
    // holá adresa → odkaz s čitelným názvem webu (Wikipedie i s názvem článku)
    if (preg_match('~^(.*?)(https?://\S+)\s*(.*)$~u', $c, $m)) {
        $u = rtrim($m[2], '.,;');
        $host = (string)preg_replace('~^www\.~', '', (string)parse_url($u, PHP_URL_HOST));
        $nazev = HIST_WEBY[$host] ?? ($host !== '' ? $host : $u);
        if (preg_match('~^([a-z]{2})\.wikipedia\.org$~', $host, $w) && preg_match('~/wiki/([^?#]+)~', (string)parse_url($u, PHP_URL_PATH), $a)) {
            $nazev = $w[1] . '.wikipedia – ' . str_replace('_', ' ', rawurldecode($a[1]));
        }
        return (trim($m[1]) !== '' ? typo(trim($m[1])) . ' ' : '') . $odkaz($u, $nazev) . ($m[3] !== '' ? ' ' . typo($m[3]) : '');
    }
    return typo($c);
}

/** Míra jistoty milníku → krátký štítek pro web („ověřeno“ / „zčásti ověřeno“ / „podle klubu“ /
 *  „prameny se liší“). Podrobná poznámka (proč, co je neověřené) zůstává jen v administraci. */
function hist_jistota_stitek(string $jistota): array {
    $j = mb_strtolower(trim($jistota));
    if ($j === '') return ['', ''];
    $overeno = (bool)preg_match('~(^|[\s(;])ověřen~u', $j);
    $vyhrada = (bool)preg_match('~neověřen|podle|rozpor|spíš|nejist|pravděpodob~u', $j);
    if ($overeno && !$vyhrada) return ['ověřeno', 'overeno'];
    if ($overeno) return ['zčásti ověřeno', 'pramen'];
    if (str_contains($j, 'rozpor')) return ['prameny se liší', 'pramen'];
    return ['podle klubu', 'pramen'];
}

/** Pramen a míra jistoty milníku: „Pramen · …“ jako ve Variantě 4 + krátký štítek. */
function hist_pramen(string $zdroj, string $jistota): string {
    $casti = [];
    $posledniWeb = '';
    $jmena = [];
    foreach (preg_split('~\s;\s*|;\s+~u', trim($zdroj)) ?: [] as $c) {
        $c = trim($c);
        // „https://www.cztenis.cz/hrac/31168 ; /31169“ – zkrácená druhá adresa (jiný konec téže adresy)
        if ($posledniWeb !== '' && preg_match('~^/([^\s/]+)$~', $c, $m)) $c = (string)preg_replace('~/[^/]*$~', '/' . $m[1], $posledniWeb);
        if (preg_match('~(https?://[^\s()]+)~', $c, $m)) $posledniWeb = rtrim($m[1], '.,;');
        $x = hist_pramen_cast($c, $jmena);
        if ($x !== '') $casti[] = $x;
    }
    [$stitek, $trida] = hist_jistota_stitek($jistota);
    $h = $casti ? '<span class="osa__pramen-text">Pramen · ' . implode(' · ', $casti) . '</span>' : '';
    if ($stitek !== '') $h .= ($h !== '' ? ' ' : '') . '<span class="' . $trida . '">' . e($stitek) . '</span>';
    return $h === '' ? '' : '<p class="osa__pramen prelom">' . $h . '</p>';
}

/* ---------- data ---------- */
$uvod       = klub_blok('historie', 'uvod');
$bTriptych  = klub_blok('historie', 'triptych');
$bLinka     = klub_blok('historie', 'linka');
$bKronika   = klub_blok('historie', 'kronika');
$bDeska     = klub_blok('historie', 'deska');
$bDeskaPram = klub_blok('historie', 'deska-prameny');
$bOsobnosti = klub_blok('historie', 'osobnosti');

$triptych  = triptych();
$linka     = hist_polozky((string)$bLinka['text']);
$milniky   = milniky();
$osobnosti = osobnosti();

/* Kronika po epochách – „I. Český Lawn-Tennis Klub (1893–1949)“ → název + roky */
$epochy = [];
foreach ($milniky as $m) {
    $era = trim((string)$m['era']) ?: 'Kronika';
    if (!isset($epochy[$era])) {
        $nazev = $era; $roky = '';
        if (preg_match('/^(.*?)\s*\(([^()]*)\)\s*$/u', $era, $x)) { $nazev = $x[1]; $roky = $x[2]; }
        $epochy[$era] = ['nazev' => $nazev, 'roky' => $roky, 'milniky' => []];
    }
    $epochy[$era]['milniky'][] = $m;
}
$epochy = array_values($epochy);
$rokyTrav = array_map(fn($t) => (string)$t['rok'], $triptych);
$zalozeno = setting('zalozeno', '1893');

/* Zlatá deska – záložky v pořadí Varianty 4 */
$deskaKategorie = [
    'grandslam'  => 'Grand Slam',
    'cestni'     => 'Čestní členové',
    'mistri'     => 'Mistři republiky',
    'oh'         => 'Olympijské hry',
    'zasluzili'  => 'Zasloužilí členové',
    'prezidenti' => 'Prezidenti',
];
$deska = [];
foreach ($deskaKategorie as $kat => $vychozi) {
    $b = klub_blok('historie', 'deska-' . $kat);
    $hlavni = deska($kat, 'hlavni');
    $jmena = deska($kat, 'jmena');
    if (!$hlavni && !$jmena) continue;
    $deska[$kat] = ['nazev' => trim(html_text((string)$b['nadpis'])) ?: $vychozi, 'blok' => $b, 'hlavni' => $hlavni, 'jmena' => $jmena];
}
$gsStvanice = $gsKlub = 0;
foreach ($deska['grandslam']['hlavni'] ?? [] as $r) {
    $metr = preg_split('/\s+/', trim((string)$r['metr'])) ?: [];
    if (in_array('stvanice', $metr, true)) $gsStvanice++;
    if (in_array('klub', $metr, true)) $gsKlub++;
}

/* Kotvy pod hlavou stránky */
$kotvy = [];
if ($triptych) $kotvy[] = [trim((string)$bTriptych['stitek']) ?: 'Tři wimbledonské trávy', '#triptych'];
if ($epochy) $kotvy[] = [trim((string)$bKronika['stitek']) ?: 'Kronika', '#kronika'];
if ($deska) $kotvy[] = ['Zlatá deska', '#sin-slavy'];
if ($osobnosti) $kotvy[] = [trim((string)$bOsobnosti['stitek']) ?: 'Osobnosti klubu', '#osobnosti'];

$cislo = 0;
$sablona = [
    'titulek' => 'Historie',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['v2.css', 'stranky-klub.css'],
    'js'      => ['historie.js'],
    'obrazek' => $triptych[0]['foto'] ?? '',
    'trida'   => 'stranka-historie',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvod, [
    'drobky' => [['Klub', 'klub.php'], ['Historie']],
    'nadpis' => 'Historie klubu',
    'navic'  => $kotvy ? '<nav class="kotvy" aria-label="Části stránky">' . pilulky_html($kotvy) . '</nav>' : '',
]) ?>

<?php if ($triptych): ?>
  <!-- Tři wimbledonské trávy (V4 sekce 4) -->
  <section class="sekce sekce--papir2 travy" id="triptych" aria-labelledby="travy-nadpis">
    <div class="wrap">
      <?= hlava_sekce($bTriptych, ['cislo' => ++$cislo, 'id' => 'travy-nadpis', 'radek' => true, 'nadpis' => 'Tři wimbledonské trávy']) ?>

      <div class="triptych">
        <?php foreach ($triptych as $i => $t):
          $id = 'trava-' . preg_replace('/[^0-9a-z]/', '', strtolower((string)$t['rok'])) . '-' . $i;
          $zaKlub = str_contains(mb_strtolower((string)$t['hral_za']), 'čltk');
          $zena = (bool)preg_match('/(ová|á)$/u', trim((string)$t['jmeno']));
          $sety = $t['sety_pole']; ?>
        <article class="trava<?= $zaKlub ? ' trava--barva' : '' ?>" data-trava>
          <div class="trava__obraz"><?= obr((string)$t['foto'], (string)$t['alt'], ['fokus' => (string)$t['fokus']]) ?></div>
          <p class="trava__rok"><?= e((string)$t['rok']) ?></p>
          <h3 class="trava__jmeno"><?= typo((string)$t['jmeno']) ?></h3>
          <?php if (trim((string)$t['disciplina']) !== ''): ?><p class="trava__disciplina"><?= typo((string)$t['disciplina']) ?></p><?php endif; ?>
          <?php if ($sety && trim((string)$t['vitez']) !== ''): ?>
          <table class="skore">
            <caption class="vh"><?= typo(trim((string)$t['disciplina']) . ' ' . $t['rok'] . ': ' . $t['vitez'] . ' – ' . $t['souper'] . ' ' . sety_text($sety)) ?></caption>
            <tbody>
              <?php foreach ([0, 1] as $strana):
                $jmenoRadku = $strana === 0 ? (string)$t['vitez'] : (string)$t['souper']; ?>
              <tr<?= $strana === 0 ? ' class="vitez"' : '' ?>>
                <th scope="row"><?= typo($jmenoRadku) ?></th>
                <?php foreach ($sety as $s):
                  $cisla = explode(':', (string)$s['hlavni']);
                  $muj = (int)($cisla[$strana] ?? 0);
                  $cizi = (int)($cisla[1 - $strana] ?? 0);
                  $tb = preg_match('/(\d+)\s*:\s*(\d+)/', (string)$s['doplnek'], $tbm) ? ($strana === 0 ? $tbm[1] : $tbm[2]) : ''; ?>
                <td<?= $muj > $cizi ? ' class="v"' : '' ?>><?= e((string)($cisla[$strana] ?? '')) ?><?= $tb !== '' ? '<sup>' . e($tb) . '</sup>' : '' ?></td>
                <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
          <?php if (trim((string)$t['hral_za']) !== ''): ?>
          <div class="trava__za">
            <button class="trava__tl" type="button" data-trava-prepinac aria-expanded="false" aria-controls="<?= e($id) ?>">V&nbsp;den titulu <?= $zena ? 'hrála' : 'hrál' ?> za</button>
            <div class="trava__detail" id="<?= e($id) ?>" hidden>
              <b><?= typo((string)$t['hral_za']) ?></b>
              <?= paragraphs((string)$t['hral_za_text']) ?>
            </div>
          </div>
          <?php endif; ?>
        </article>
        <?php endforeach; ?>
      </div>

      <?php if ($linka):
        $roky = array_map(fn($l) => (int)$l['hlava_text'], $linka);
        $od = (int)(floor(min($roky) / 10) * 10);
        $do = (int)(ceil((max($roky) + 1) / 10) * 10);
        $rozsah = max(10, $do - $od); ?>
      <div class="wimbledonska-linka">
        <p class="stitek stitek--tlumeny"><?= typo(trim((string)$bLinka['stitek']) ?: 'Wimbledonská linka') ?></p>
        <div class="linka-let">
          <div class="linka-let__cara" data-od="<?= $od ?>" data-do="<?= $do ?>" aria-hidden="true"></div>
          <ol>
            <?php foreach ($linka as $i => $l):
              $rok = (int)$l['hlava_text'];
              $x = round(($rok - $od) / $rozsah * 100, 2);
              $tridy = ['linka-let__bod'];
              if (preg_match('/vítěz/u', $l['prosty'])) $tridy[] = 'linka-let__bod--plny';
              if ($i % 2 === 1) $tridy[] = 'linka-let__bod--dole';
              if ($i === 0 && $x < 20) $tridy[] = 'linka-let__bod--vlevo';
              if ($x > 80) $tridy[] = 'linka-let__bod--vpravo';
              if ($i === count($linka) - 1) $tridy[] = 'linka-let__bod--zvyraznit'; ?>
            <li class="<?= implode(' ', $tridy) ?>" style="--x:<?= e((string)$x) ?>%"><span class="linka-let__stitek"><b><?= e($l['hlava_text']) ?></b><?= $l['text'] ?></span></li>
            <?php endforeach; ?>
          </ol>
        </div>
        <p class="drobne wimbledonska-linka__legenda"><span class="tecka tecka--plna" aria-hidden="true"></span> vítězství <span class="tecka" aria-hidden="true"></span> finále<?= trim((string)$bLinka['perex']) !== '' ? ' · ' . typo((string)$bLinka['perex']) : '' ?></p>
      </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php if ($epochy): ?>
  <!-- Kronika po epochách s razítkem dobového jména (návrh 2) -->
  <section class="sekce kronika-sekce" id="kronika" aria-labelledby="kronika-nadpis">
    <div class="wrap">
      <?= hlava_sekce($bKronika, ['cislo' => ++$cislo, 'id' => 'kronika-nadpis', 'radek' => true, 'nadpis' => 'Kronika']) ?>
      <div class="kronika" data-kronika>
        <aside class="kronika__bok" aria-label="Kapitoly kroniky">
          <div class="kronika__razitko" aria-hidden="true">
            <span class="kronika__razitko-horni">Dobové jméno</span>
            <span class="kronika__razitko-jmeno" data-razitko-jmeno><?= typo($epochy[0]['nazev']) ?></span>
            <span class="kronika__razitko-roky" data-razitko-roky><?= typo($epochy[0]['roky']) ?></span>
          </div>
          <p class="stitek stitek--tlumeny kronika__bok-titul">Kapitoly</p>
          <ol class="kronika__kapitoly">
            <?php foreach ($epochy as $i => $ep): ?>
            <li><a href="#kapitola-<?= $i + 1 ?>" data-kapitola-odkaz><span class="kronika__kapitoly-cislo"><?= e(rimske($i + 1)) ?>.</span> <?= typo($ep['nazev']) ?></a></li>
            <?php endforeach; ?>
          </ol>
        </aside>

        <div class="kronika__obsah">
          <?php foreach ($epochy as $i => $ep): ?>
          <section class="kapitola" id="kapitola-<?= $i + 1 ?>" data-era data-era-nazev="<?= e($ep['nazev']) ?>" data-era-roky="<?= e($ep['roky']) ?>" aria-labelledby="kapitola-<?= $i + 1 ?>-nadpis">
            <header class="kapitola__hlava">
              <span class="kapitola__cislo" aria-hidden="true"><?= e(rimske($i + 1)) ?>.</span>
              <h3 class="kapitola__nazev" id="kapitola-<?= $i + 1 ?>-nadpis"><?= typo($ep['nazev']) ?></h3>
              <?php if ($ep['roky'] !== ''): ?><span class="kapitola__roky"><?= typo($ep['roky']) ?></span><?php endif; ?>
            </header>
            <ol class="osa">
              <?php foreach ($ep['milniky'] as $m):
                $rok = (string)$m['rok'];
                $rokText = trim((string)$m['rok_text']);
                $jenText = $rokText !== '' && !str_contains($rokText, $rok);
                $hlavni = $rok === $zalozeno || in_array($rok, $rokyTrav, true);
                $foto = trim((string)$m['foto']); ?>
              <?php $img = $foto !== '' ? obr($foto, trim((string)$m['foto_popisek']) ?: (string)$m['titulek'], ['class' => (int)$rok < 1990 ? 'foto--hist' : 'foto']) : ''; ?>
              <li class="osa__bod<?= $hlavni ? ' osa__bod--hlavni' : '' ?><?= $img !== '' ? ' osa__bod--s-fotkou' : '' ?>">
                <p class="osa__rok<?= $jenText ? ' osa__rok--text' : '' ?>"><?= $jenText ? typo($rokText) : e($rok) . ($rokText !== '' && $rokText !== $rok ? '<small>' . typo($rokText) . '</small>' : '') ?></p>
                <div class="osa__obsah">
                  <h4 class="osa__titulek"><?= typo((string)$m['titulek']) ?></h4>
                  <?php if (trim((string)$m['text']) !== ''): ?><div class="osa__text"><?= paragraphs((string)$m['text']) ?></div><?php endif; ?>
                  <?= hist_pramen((string)$m['zdroj'], (string)$m['jistota']) ?>
                </div>
                <?php if ($img !== ''): ?>
                <figure class="osa__foto ramec ramec--pasparta">
                  <div class="ramec__obraz"><?= $img ?></div>
                  <?php if (trim((string)$m['foto_popisek']) !== ''): ?><figcaption class="popisek"><span><?= typo((string)$m['foto_popisek']) ?></span></figcaption><?php endif; ?>
                </figure>
                <?php endif; ?>
              </li>
              <?php endforeach; ?>
            </ol>
          </section>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($deska): ?>
  <!-- Zlatá deska se záložkami a dvojím metrem (V4 sekce 9) -->
  <section class="sekce sekce--papir2 deska-sekce" id="sin-slavy" aria-labelledby="deska-nadpis">
    <div class="wrap">
      <?= hlava_sekce($bDeska, ['cislo' => ++$cislo, 'id' => 'deska-nadpis', 'stred' => true, 'nadpis' => 'Zlatá deska']) ?>

      <div class="deska">
        <span class="deska__erb" aria-hidden="true"><img src="<?= e(logo_url('svg')) ?>" alt="" width="46" height="53" loading="lazy" decoding="async"></span>
        <div class="deska__hlava">
          <p class="deska__nadpis">Zlatá deska</p>
          <p class="deska__podnadpis"><?= typo(setting('klub_nazev', 'I. Český Lawn-Tennis Klub Praha')) ?> · od roku <?= e($zalozeno) ?></p>
        </div>

        <div class="zalozky" data-zalozky>
          <div class="zalozky__seznam zalozky__seznam--stred" role="tablist" aria-label="Oddíly Zlaté desky">
            <?php $prvni = true; foreach ($deska as $kat => $d): ?>
            <button class="zalozka" type="button" role="tab" id="zd-t-<?= e($kat) ?>" aria-controls="zd-p-<?= e($kat) ?>" aria-selected="<?= $prvni ? 'true' : 'false' ?>" data-hash="<?= e($kat) ?>"<?= $prvni ? '' : ' tabindex="-1"' ?>><?= typo($d['nazev']) ?></button>
            <?php $prvni = false; endforeach; ?>
          </div>

          <?php $prvni = true; foreach ($deska as $kat => $d):
            $b = $d['blok'];
            $perex = trim((string)$b['perex']);
            $text = html_ocistit((string)$b['text']);
            $doplni = (int)$b['doplni_klub'] === 1;
            $poznHtml = $text !== '' ? ($doplni && preg_match('~doplní(\s|&nbsp;)+klub~u', $text) ? hist_doplni($text) : $text . ($doplni ? ' ' . doplni_klub() : '')) : ($doplni ? doplni_klub() : '');
            $poznHtml = (string)preg_replace('~</?p>~', ' ', $poznHtml); ?>
          <div class="zalozky__panel" role="tabpanel" id="zd-p-<?= e($kat) ?>" aria-labelledby="zd-t-<?= e($kat) ?>" tabindex="0"<?= $prvni ? '' : ' hidden' ?>>
            <?php if ($kat === 'grandslam'): ?>
            <div class="metr" data-metr="stvanice">
              <fieldset>
                <legend class="vh">Jak počítat grandslamové tituly</legend>
                <div class="metr__volby">
                  <label class="metr__volba"><input type="radio" name="metr-gs" value="stvanice" data-metr-volba checked><span><b><?= $gsStvanice ?></b><strong>Vyrostli na Štvanici</strong><small>klubová definice · podle klubu</small></span></label>
                  <label class="metr__volba"><input type="radio" name="metr-gs" value="klub" data-metr-volba><span><b><?= $gsKlub ?></b><strong>V&nbsp;barvách klubu</strong><small>v&nbsp;den triumfu · registr ČTS</small></span></label>
                </div>
              </fieldset>
              <?php $tStv = html_text($perex); $tKlub = html_text($text); ?>
              <p class="metr__vysvetleni" data-metr-vystup aria-live="polite" data-text-stvanice="<?= e($tStv) ?>" data-text-klub="<?= e($tKlub !== '' ? $tKlub : $tStv) ?>"><?= typo($tStv) ?></p>
              <ol class="deska__radky">
                <?php foreach ($d['hlavni'] as $r):
                  $metr = trim((string)$r['metr']);
                  $pramen = trim((string)$r['pramen']);
                  $jenMimo = $pramen !== '' && !in_array('klub', preg_split('/\s+/', $metr) ?: [], true) && mb_strtolower($pramen) === 'pravděpodobně'; ?>
                <li class="deska__radek<?= (int)$r['historie'] === 1 ? ' deska__radek--historie' : '' ?>"<?= $metr !== '' ? ' data-metr-patri="' . e($metr) . '"' : '' ?>><span class="deska__rok"><?= e((string)$r['rok']) ?></span><span class="deska__jmeno"><?= typo((string)$r['jmeno']) ?></span><span class="deska__vodici" aria-hidden="true"></span><span class="deska__cin"><?= hist_doplni(typo((string)$r['cin'])) ?><?= $pramen !== '' ? '<span class="pramen' . ($jenMimo ? ' metr__pozn' : '') . '">' . e($pramen) . '</span>' : '' ?></span></li>
                <?php endforeach; ?>
              </ol>
            </div>
            <?php else: ?>
              <?php if ($perex !== ''): ?><p class="metr__vysvetleni"><?= typo(html_text($perex)) ?></p><?php endif; ?>
              <?php if ($d['hlavni']): $sRoky = (bool)array_filter($d['hlavni'], fn($r) => trim((string)$r['rok']) !== ''); ?>
              <ol class="deska__radky<?= $kat === 'prezidenti' ? ' deska__radky--roky' : '' ?>">
                <?php foreach ($d['hlavni'] as $r): $pramen = trim((string)$r['pramen']); ?>
                <li class="deska__radek<?= $sRoky ? '' : ' deska__radek--bez-roku' ?><?= (int)$r['historie'] === 1 ? ' deska__radek--historie' : '' ?>"><?php if ($sRoky): ?><span class="deska__rok"><?= e((string)$r['rok']) ?></span><?php endif; ?><span class="deska__jmeno"><?= typo((string)$r['jmeno']) ?></span><span class="deska__vodici" aria-hidden="true"></span><span class="deska__cin"><?= hist_doplni(typo((string)$r['cin'])) ?><?= $pramen !== '' ? '<span class="pramen">' . e($pramen) . '</span>' : '' ?></span></li>
                <?php endforeach; ?>
              </ol>
              <?php endif; ?>
              <?php if ($d['jmena']): ?>
                <?php if ($d['hlavni'] && $poznHtml !== ''): ?><p class="deska__mezititulek"><?= trim($poznHtml) ?></p><?php $poznHtml = ''; endif; ?>
              <p class="deska__jmena<?= count($d['jmena']) > 16 ? ' deska__jmena--husta' : '' ?>"><?php foreach ($d['jmena'] as $j):
                  $jm = typo((string)$j['jmeno']);
                  $jm = (string)preg_replace('~\s+in(\s|&nbsp;)+memoriam$~u', ' <small>in&nbsp;memoriam</small>', $jm); ?><span><?= $jm ?></span> <?php endforeach; ?></p>
              <?php endif; ?>
              <?php if (trim($poznHtml) !== ''): ?><p class="deska__pozn drobne stred"><?= trim($poznHtml) ?></p><?php endif; ?>
            <?php endif; ?>
          </div>
          <?php $prvni = false; endforeach; ?>
        </div>

        <div class="deska__pata">
          <?php if (trim((string)$bDeskaPram['perex']) !== ''): ?><span><?= typo(html_text((string)$bDeskaPram['perex'])) ?></span><?php endif; ?>
          <?php if ($triptych): ?><a class="odkaz" href="#triptych"><?= typo(trim((string)$bTriptych['stitek']) ?: 'Tři wimbledonské trávy') ?> <?= sipka('nahoru') ?></a><?php endif; ?>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($osobnosti): ?>
  <!-- Od Žemly po Muchovou – medailony (V4 sekce 9b) -->
  <section class="sekce v2 osobnosti" id="osobnosti" aria-labelledby="osobnosti-nadpis">
    <div class="wrap">
      <?= hlava_sekce($bOsobnosti, ['cislo' => ++$cislo, 'id' => 'osobnosti-nadpis', 'radek' => true, 'nadpis' => 'Osobnosti klubu']) ?>
      <ol class="medailony">
        <?php foreach ($osobnosti as $o):
          $img = obr((string)$o['foto'], trim((string)$o['alt']) ?: (string)$o['jmeno'], ['fokus' => (string)$o['fokus']]);
          $jistota = in_array((string)$o['jistota'], ['overeno', 'klub', 'doplni', 'rozpor'], true) ? (string)$o['jistota'] : 'overeno'; ?>
        <li class="medailon">
          <div class="medailon-portret<?= $img !== '' ? ' duotone' : ' medailon-portret--typo' ?>"><?= $img !== '' ? $img : '<span class="medailon-typo" aria-hidden="true"><b>' . e(mb_substr((string)$o['jmeno'], 0, 1)) . '</b></span>' ?></div>
          <?php if (trim((string)$o['kategorie']) !== ''): ?><p class="medailon-kategorie"><?= typo((string)$o['kategorie']) ?></p><?php endif; ?>
          <h3 class="medailon-jmeno"><?= typo((string)$o['jmeno']) ?></h3>
          <?php if (trim((string)$o['roky']) !== ''): ?><p class="medailon-roky"><?= typo((string)$o['roky']) ?></p><?php endif; ?>
          <?php if (trim((string)$o['cin']) !== ''): ?><p class="medailon-cin"><?= html_inline((string)$o['cin']) ?></p><?php endif; ?>
          <?php if (trim((string)$o['pramen']) !== ''): ?><p class="m-pramen">Pramen · <?= typo((string)$o['pramen']) ?></p><?php endif; ?>
          <?php if (trim((string)$o['jistota_text']) !== ''): ?><span class="jistota jistota--<?= e($jistota) ?>"><?= typo((string)$o['jistota_text']) ?></span><?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
