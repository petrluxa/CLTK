<?php
/* Centenary Tennis Clubs – členství klubu v CTC, hra v klubech CTC (reciprocita),
   mezinárodní utkání a kluby, které hrály na Štvanici, poháry (Carrickmines Cup,
   I. ČLTK Praha Cup U14, CTC Senior) a rodokmen stoletých klubů.
   Všechno z modulu Vedení a CTC (cltk_ctc: fakt, klub, utkani, soutez, rodokmen)
   a z bloků stránky „ctc“ (modul Stránky). Pohár U14 odkazuje na akci
   v klubovém kalendáři, když tam je. */
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

/** Pramen záznamu CTC – adresy jako odkazy, zbytek jako text. */
function ctc_pramen(string $zdroj): string {
    $zdroj = trim($zdroj);
    if ($zdroj === '') return '';
    $t = typo($zdroj);
    return (string)preg_replace_callback('~https?://[^\s;,<>"]+[^\s;,.<>")]~u', static function (array $m): string {
        $u = html_entity_decode($m[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $ukazat = rawurldecode((string)preg_replace('~^https?://(www\.)?~', '', $u));
        return '<a href="' . e($u) . '" target="_blank" rel="noopener">' . e(mb_strimwidth($ukazat, 0, 42, '…')) . '<span class="vh"> (v novém okně)</span></a>';
    }, $t);
}

/* ---------- data ---------- */
$uvod      = klub_blok('ctc', 'uvod');
$bClenstvi = klub_blok('ctc', 'clenstvi');
$bFoto     = klub_blok('ctc', 'foto');
$bUtkani   = klub_blok('ctc', 'utkani');
$bSouteze  = klub_blok('ctc', 'souteze');
$bRodokmen = klub_blok('ctc', 'rodokmen');

$fakty    = ctc('fakt');
$kluby    = ctc('klub');
$utkani   = ctc('utkani');
$souteze  = ctc('soutez');
$rodokmen = ctc('rodokmen');

/* soutěž s odkazem „akce.php“ bez čísla → akce v kalendáři se stejnou kategorií („U14“) */
$akceRoku = akce_rok();
foreach ($souteze as &$s) {
    $s['odkaz_url'] = '';
    $odkaz = trim((string)$s['odkaz']);
    if ($odkaz === 'akce.php' || $odkaz === 'akce') {
        if (preg_match('/\(([^()]+)\)/u', (string)$s['nazev'], $m)) {
            foreach ($akceRoku as $a) {
                if (str_contains(mb_strtolower((string)$a['nazev']), mb_strtolower(trim($m[1])))) { $s['odkaz_url'] = 'akce.php?id=' . (int)$a['id']; break; }
            }
        }
    } elseif ($odkaz !== '') {
        $s['odkaz_url'] = $odkaz;            // tlacitko() ho prověří bezpecny_odkaz()
    }
}
unset($s);

$kotvy = [];
if ($fakty) $kotvy[] = [trim(html_text((string)$bClenstvi['stitek'])) ?: 'Členství v CTC', '#clenstvi'];
if ($utkani || $kluby) $kotvy[] = [trim(html_text((string)$bUtkani['stitek'])) !== '' && trim(html_text((string)$bUtkani['stitek'])) !== 'Na Štvanici' ? trim(html_text((string)$bUtkani['stitek'])) : 'Utkání', '#utkani'];
if ($souteze) $kotvy[] = [trim(html_text((string)$bSouteze['stitek'])) ?: 'Soutěže', '#souteze'];
if ($rodokmen) $kotvy[] = [trim(html_text((string)$bRodokmen['stitek'])) ?: 'Rodokmen stoletých', '#rodokmen'];

$cislo = 0;
$sablona = [
    'titulek' => 'Centenary Tennis Clubs',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-klub.css'],
    'obrazek' => (string)$bFoto['foto'],
    'trida'   => 'stranka-ctc',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvod, [
    'drobky' => [['Klub', 'klub.php'], ['CTC']],
    'nadpis' => 'Centenary Tennis Clubs',
    'navic'  => $kotvy ? '<nav class="kotvy" aria-label="Části stránky">' . pilulky_html($kotvy) . '</nav>' : '',
]) ?>

<?php if ($fakty): ?>
  <!-- I · Členství v CTC a hra v klubech sdružení -->
  <section class="sekce sekce--linka ctc-clenstvi" id="clenstvi" aria-labelledby="clenstvi-nadpis">
    <div class="wrap mrizka">
      <div class="sl-5">
        <?= hlava_sekce($bClenstvi, ['cislo' => ++$cislo, 'id' => 'clenstvi-nadpis', 'stitek' => 'Členství v CTC', 'nadpis' => 'Centenary Tennis Clubs']) ?>
        <?php $roky = array_filter($fakty, fn($f) => preg_match('/^\d{4}$/', trim((string)$f['rok']))); ?>
        <?php if ($roky): ?>
        <ul class="cisla ctc-cisla" style="--sloupcu:<?= min(3, count($roky)) ?>">
          <?php foreach ($roky as $f): ?>
          <li class="cisla__polozka"><span class="cisla__hodnota onum"><?= e(trim((string)$f['rok'])) ?></span><span class="cisla__popis"><?= typo((string)$f['nazev']) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
      <div class="sl-6 od-7">
        <ol class="rimsky ctc-fakty">
          <?php foreach ($fakty as $f):
            $odkaz = bezpecny_odkaz((string)$f['odkaz']); ?>
          <li>
            <b><?= typo((string)$f['nazev']) ?></b>
            <?= trim((string)$f['text']) !== '' ? '<span class="ctc-fakty__text">' . typo((string)$f['text']) . '</span>' : '' ?>
            <?php if ($odkaz !== '' || trim((string)$f['zdroj']) !== ''): ?>
            <span class="ctc-fakty__pramen m-pramen"><?php if (trim((string)$f['zdroj']) !== ''): ?>Pramen: <?= ctc_pramen((string)$f['zdroj']) ?><?php endif; ?><?php if ($odkaz !== ''): ?><?= trim((string)$f['zdroj']) !== '' ? ' · ' : '' ?><a href="<?= e($odkaz) ?>"<?= odkaz_attr($odkaz) ?>><?= e(mb_strimwidth(rawurldecode((string)preg_replace('~^https?://(www\.)?~', '', $odkaz)), 0, 48, '…')) ?><?= odkaz_je_externi($odkaz) ? '<span class="vh"> (v novém okně)</span>' : '' ?></a><?php endif; ?></span>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($utkani || $kluby): ?>
  <!-- II · Mezinárodní utkání a kluby, které hrály na Štvanici -->
  <section class="sekce sekce--papir2 ctc-utkani" id="utkani" aria-labelledby="utkani-nadpis">
    <div class="wrap">
      <?= hlava_sekce($bUtkani, ['cislo' => ++$cislo, 'id' => 'utkani-nadpis', 'stitek' => 'Na Štvanici', 'nadpis' => 'Mezinárodní utkání', 'radek' => true]) ?>
      <div class="ctc-utkani__mrizka">
        <?php if ($utkani): ?>
        <div>
          <h3 class="h5 ctc-utkani__titul">Utkání posledních let</h3>
          <ol class="ctc-utkani__seznam">
            <?php foreach ($utkani as $u): ?>
            <li class="ctc-utkani__radek">
              <span class="ctc-utkani__rok onum"><?= e(trim((string)$u['rok'])) ?></span>
              <span class="ctc-utkani__nazev"><?= typo((string)$u['nazev']) ?><?php if (trim((string)$u['misto']) !== ''): ?><small><?= typo((string)$u['misto']) ?></small><?php endif; ?></span>
              <span class="ctc-utkani__kdy"><?= typo((string)$u['text']) ?></span>
            </li>
            <?php endforeach; ?>
          </ol>
        </div>
        <?php endif; ?>
        <?php if ($kluby): ?>
        <div>
          <h3 class="h5 ctc-utkani__titul">Kluby, které hrály na Štvanici</h3>
          <ul class="ctc-kluby">
            <?php foreach ($kluby as $k): ?>
            <li class="ctc-klub"><span class="ctc-klub__nazev"><?= typo((string)$k['nazev']) ?></span><?php if (trim((string)$k['misto']) !== ''): ?><span class="ctc-klub__misto"><?= typo((string)$k['misto']) ?></span><?php endif; ?></li>
            <?php endforeach; ?>
          </ul>
          <?php $zdrojKlubu = trim((string)($kluby[0]['zdroj'] ?? '')); ?>
          <?php if ($zdrojKlubu !== ''): ?><p class="m-pramen ctc-kluby__pramen">Pramen: <?= ctc_pramen($zdrojKlubu) ?></p><?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($souteze): ?>
  <!-- III · Poháry: Carrickmines Cup, I. ČLTK Praha Cup U14, CTC Senior -->
  <section class="sekce ctc-souteze" id="souteze" aria-labelledby="souteze-nadpis">
    <div class="wrap">
      <?= hlava_sekce($bSouteze, ['cislo' => ++$cislo, 'id' => 'souteze-nadpis', 'stitek' => 'Soutěže', 'nadpis' => 'Soutěže CTC', 'radek' => true]) ?>
      <div class="ctc-souteze__mrizka<?= obr((string)$bFoto['foto']) !== '' ? ' ctc-souteze__mrizka--s-fotkou' : '' ?>">
        <ul class="ctc-pohary">
          <?php foreach ($souteze as $s): ?>
          <li class="ctc-pohar">
            <?php if (preg_match('/^\d{4}$/', trim((string)$s['rok']))): ?><p class="ctc-pohar__rok">od roku <span class="onum"><?= e(trim((string)$s['rok'])) ?></span></p><?php endif; ?>
            <h3 class="ctc-pohar__nazev"><?= typo((string)$s['nazev']) ?></h3>
            <p class="ctc-pohar__text"><?= typo((string)$s['text']) ?></p>
            <?php if (trim((string)$s['zdroj']) !== ''): ?><p class="m-pramen ctc-pohar__pramen">Pramen: <?= ctc_pramen((string)$s['zdroj']) ?></p><?php endif; ?>
            <?php if ($s['odkaz_url'] !== ''): ?><p class="ctc-pohar__odkaz"><?= tlacitko($s['odkaz_url'], str_contains($s['odkaz_url'], 'akce.php') ? 'V klubovém kalendáři' : 'Více', 'odkaz') ?></p><?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <?= blok_foto($bFoto, 'pomer-3x2', ['trida' => 'ctc-souteze__foto']) ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($rodokmen): ?>
  <!-- IV · Rodokmen stoletých klubů (V4 sekce 3b) -->
  <section class="sekce sekce--papir2 ctc-rodokmen" id="rodokmen" aria-labelledby="rodokmen-nadpis">
    <div class="wrap mrizka">
      <div class="sl-5">
        <?= hlava_sekce(array_merge($bRodokmen, ['nadpis' => trim((string)$bRodokmen['nadpis']) !== '' ? (string)$bRodokmen['nadpis'] : 'Rodokmen <em>stoletých</em> klubů']), ['cislo' => ++$cislo, 'id' => 'rodokmen-nadpis', 'stitek' => 'Rodokmen stoletých']) ?>
      </div>
      <div class="sl-7 od-6">
        <ol class="rodokmen">
          <?php foreach ($rodokmen as $r): $my = (int)$r['zvyraznit'] === 1; ?>
          <li class="rodokmen__klub<?= $my ? ' rodokmen__klub--my' : '' ?>">
            <span class="rodokmen__rok onum"><?= e((string)$r['rok']) ?></span>
            <span class="rodokmen__nazev"><?= $my ? '<b class="sc">' . typo((string)$r['nazev']) . '</b>' : typo((string)$r['nazev']) ?><?php if (!$my && trim((string)$r['misto']) !== ''): ?><small><?= typo((string)$r['misto']) ?></small><?php endif; ?></span>
            <span class="rodokmen__pozn"><?= typo((string)$r['text']) ?></span>
          </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
