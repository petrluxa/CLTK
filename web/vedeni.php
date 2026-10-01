<?php
/* Vedení klubu – prezident, Výkonný výbor, kancelář klubu, další kontakty,
   prezidenti klubu od roku 1893 a stanovy.
   Lidé z modulu Vedení a CTC (cltk_vedeni; telefon a e-mail jen při
   zobrazit_kontakt = 1), prezidenti ze Zlaté desky (modul Historie,
   kategorie „prezidenti“), texty z bloků stránky „vedeni“ (modul Stránky),
   dokumenty z modulu Dokumenty. */
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

/** Začátek úřadu z textu roku („1990–2011“ → 1990, „2022–“ → 2022); bez čísla 0. */
function vedeni_od(string $rok): int {
    return preg_match('/(1[89]\d{2}|20\d{2})/', $rok, $m) ? (int)$m[1] : 0;
}

/** „1990–2011“ → „1990 – 2011“ s nezlomitelnými mezerami; „2022–“ → „od 2022“. */
function vedeni_roky(string $rok): string {
    $rok = trim($rok);
    if (preg_match('/^(\d{4})\s*[–-]\s*$/u', $rok, $m)) return 'od&nbsp;' . e($m[1]);
    return str_replace(['–', ' '], ['&#8202;–&#8202;', '&nbsp;'], e($rok));
}

/* ---------- data ---------- */
$uvod       = klub_blok('vedeni', 'uvod');
$bPrezident = klub_blok('vedeni', 'prezident');
$bVybor     = klub_blok('vedeni', 'vybor');
$bKancelar  = klub_blok('vedeni', 'kancelar');
$bKontakty  = klub_blok('vedeni', 'kontakty');
$bPrezidenti = klub_blok('vedeni', 'prezidenti');
$bDokumenty = klub_blok('vedeni', 'dokumenty');
$bDeskaPrez = klub_blok('historie', 'deska-prezidenti');

$vybor    = vedeni('vybor');
$kancelar = vedeni('kancelar');
$kontakty = vedeni('kontakt');
$dokumentyKlub = dokumenty('klub');

/* prezident = člen výboru s funkcí „prezident…“ */
$prezident = null;
foreach ($vybor as $v) {
    if (str_starts_with(mb_strtolower(trim((string)$v['funkce'])), 'prezident')) { $prezident = $v; break; }
}

/* prezidenti od roku 1893: nejdřív první léta bez roků úřadu, pak od nejstaršího */
$prezidentiRoky = deska('prezidenti', 'hlavni');
usort($prezidentiRoky, fn($a, $b) => vedeni_od((string)$a['rok']) <=> vedeni_od((string)$b['rok']));
$prezidentiPrvni = deska('prezidenti', 'jmena');
$vUradu = null;
if ($prezident) {
    foreach ($prezidentiRoky as $p) {
        if (trim((string)$p['jmeno']) === trim((string)$prezident['jmeno'])) { $vUradu = $p; break; }
    }
}
/* poznámka k prvním letům („Prvních 36 let – roky úřadu doplní klub.“) z bloku Zlaté desky */
$poznPrvni = trim(html_text((string)$bDeskaPrez['text']));

/* výbor bez kontaktů (kontakty jsou u kanceláře) */
$vyborBezKontaktu = array_map(fn($v) => array_merge($v, ['telefon' => '', 'email' => '']), $vybor);

$kotvy = [];
if ($vybor) $kotvy[] = [trim(html_text((string)$bVybor['nadpis'])) ?: 'Výkonný výbor', '#vybor'];
if ($kancelar) $kotvy[] = [trim(html_text((string)$bKancelar['nadpis'])) ?: 'Kancelář klubu', '#kancelar'];
if ($prezidentiRoky || $prezidentiPrvni) $kotvy[] = [trim(html_text((string)$bPrezidenti['nadpis'])) ?: 'Prezidenti klubu', '#prezidenti'];
if ($dokumentyKlub) $kotvy[] = [trim(html_text((string)$bDokumenty['nadpis'])) ?: 'Stanovy a dokumenty', '#dokumenty'];

$cislo = 0;
$sablona = [
    'titulek' => 'Vedení klubu',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-klub.css'],
    'trida'   => 'stranka-vedeni',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvod, [
    'drobky' => [['Klub', 'klub.php'], ['Vedení']],
    'nadpis' => 'Vedení klubu',
    'navic'  => $kotvy ? '<nav class="kotvy" aria-label="Části stránky">' . pilulky_html($kotvy) . '</nav>' : '',
]) ?>

<?php if ($vybor): ?>
  <!-- I · Prezident a Výkonný výbor -->
  <section class="sekce sekce--linka vedeni-vybor" id="vybor" aria-labelledby="vybor-nadpis">
    <div class="wrap">
      <?php if ($prezident): ?>
      <div class="vedeni-prezident">
        <span class="vedeni-prezident__erb" aria-hidden="true"><img src="<?= e(logo_url('svg')) ?>" alt="" width="60" height="69" loading="lazy" decoding="async"></span>
        <div class="vedeni-prezident__text">
          <p class="stitek"><?= typo(trim((string)$bPrezident['stitek']) ?: 'Prezident klubu') ?></p>
          <p class="vedeni-prezident__jmeno"><?= typo((string)$prezident['jmeno']) ?></p>
          <?php if ($vUradu && vedeni_od((string)$vUradu['rok']) > 0): ?>
          <p class="vedeni-prezident__od">v&nbsp;čele klubu <?= vedeni_roky((string)$vUradu['rok']) ?></p>
          <?php endif; ?>
          <?php if (trim((string)$prezident['text']) !== ''): ?><div class="vedeni-prezident__pozn"><?= paragraphs((string)$prezident['text']) ?></div><?php endif; ?>
          <?php if (trim((string)$bPrezident['perex']) !== ''): ?><div class="vedeni-prezident__pozn"><?= paragraphs((string)$bPrezident['perex']) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?= hlava_sekce($bVybor, ['cislo' => ++$cislo, 'id' => 'vybor-nadpis', 'stitek' => 'Výkonný výbor', 'nadpis' => 'Výkonný výbor', 'radek' => true]) ?>
      <ul class="osoby osoby--bez-fotek vedeni-osoby">
        <?php foreach ($vyborBezKontaktu as $v) echo osoba_html($v, ['foto' => false]); ?>
      </ul>
    </div>
  </section>
<?php endif; ?>

<?php if ($kancelar || $kontakty): ?>
  <!-- II · Kancelář klubu a další kontakty -->
  <section class="sekce sekce--papir2 vedeni-kancelar" id="kancelar" aria-labelledby="kancelar-nadpis">
    <div class="wrap">
      <?php if ($kancelar): ?>
      <?= hlava_sekce($bKancelar, ['cislo' => ++$cislo, 'id' => 'kancelar-nadpis', 'stitek' => 'Kancelář klubu', 'nadpis' => 'Kancelář klubu', 'radek' => true]) ?>
      <ul class="osoby osoby--bez-fotek vedeni-osoby">
        <?php foreach ($kancelar as $v) echo osoba_html($v, ['foto' => false]); ?>
      </ul>
      <?php endif; ?>

      <?php if ($kontakty): ?>
      <div class="vedeni-kontakty" id="kontakty">
        <h3 class="h4 vedeni-kontakty__nadpis"><?= trim((string)$bKontakty['nadpis']) !== '' ? html_inline((string)$bKontakty['nadpis']) : 'Další kontakty' ?></h3>
        <ul class="radky vedeni-kontakty__seznam">
          <?php foreach ($kontakty as $k):
            $smi = (int)$k['zobrazit_kontakt'] === 1;
            $tel = $smi ? trim((string)$k['telefon']) : '';
            $mail = $smi && je_email((string)$k['email']) ? (string)$k['email'] : ''; ?>
          <li class="radek vedeni-kontakt">
            <span class="radek__nazev"><b><?= typo((string)$k['jmeno']) ?></b><?php if (trim((string)$k['funkce']) !== ''): ?><small><?= typo((string)$k['funkce']) ?></small><?php endif; ?></span>
            <span class="radek__hodnota vedeni-kontakt__spojeni">
              <?php if ($tel !== ''): ?><a href="<?= e(tel_href($tel)) ?>"><?= str_replace(' ', '&nbsp;', e($tel)) ?></a><?php endif; ?>
              <?php if ($mail !== ''): ?><a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a><?php endif; ?>
            </span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php if ($prezidentiRoky || $prezidentiPrvni): ?>
  <!-- III · Prezidenti klubu od roku 1893 -->
  <section class="sekce vedeni-prezidenti" id="prezidenti" aria-labelledby="prezidenti-nadpis">
    <div class="wrap">
      <?= hlava_sekce($bPrezidenti, ['cislo' => ++$cislo, 'id' => 'prezidenti-nadpis', 'stitek' => 'Od roku ' . setting('zalozeno', '1893'), 'nadpis' => 'Prezidenti klubu', 'radek' => true]) ?>
      <ol class="prezidenti">
        <?php if ($prezidentiPrvni): ?>
        <li class="prezidenti__radek prezidenti__radek--prvni">
          <span class="prezidenti__roky"><?= e(setting('zalozeno', '1893')) ?>&#8202;–&#8202;<?= $prezidentiRoky ? e((string)vedeni_od((string)$prezidentiRoky[0]['rok'])) : '' ?></span>
          <span class="prezidenti__jmena"><?php foreach ($prezidentiPrvni as $i => $p): ?><span><?= typo((string)$p['jmeno']) ?></span><?php endforeach; ?></span>
          <span class="prezidenti__pozn"><?= $poznPrvni !== '' ? typo((string)preg_replace('/\s*doplní klub\.?$/u', '', $poznPrvni)) . ' ' : '' ?><?= (int)$bDeskaPrez['doplni_klub'] === 1 || str_contains($poznPrvni, 'doplní klub') ? doplni_klub() : '' ?></span>
        </li>
        <?php endif; ?>
        <?php foreach ($prezidentiRoky as $p):
          $soucasny = $prezident && trim((string)$p['jmeno']) === trim((string)$prezident['jmeno']); ?>
        <li class="prezidenti__radek<?= $soucasny ? ' prezidenti__radek--dnes' : '' ?>">
          <span class="prezidenti__roky"><?= vedeni_roky((string)$p['rok']) ?></span>
          <span class="prezidenti__jmeno"><?= typo((string)$p['jmeno']) ?></span>
          <span class="prezidenti__pozn"><?= typo((string)$p['cin']) ?><?php if (trim((string)$p['pramen']) !== ''): ?> <span class="pramen"><?= typo((string)$p['pramen']) ?></span><?php endif; ?></span>
        </li>
        <?php endforeach; ?>
      </ol>
      <p class="drobne vedeni-prezidenti__pata"><?= tlacitko('historie.php#sin-slavy', 'Zlatá deska klubu', 'odkaz') ?></p>
    </div>
  </section>
<?php endif; ?>

<?php if ($dokumentyKlub): ?>
  <!-- IV · Stanovy a dokumenty -->
  <section class="sekce sekce--papir2 sekce--tesna vedeni-dokumenty" id="dokumenty" aria-labelledby="dokumenty-nadpis">
    <div class="wrap mrizka">
      <div class="sl-5">
        <?= hlava_sekce($bDokumenty, ['cislo' => ++$cislo, 'id' => 'dokumenty-nadpis', 'stitek' => 'Dokumenty', 'nadpis' => 'Stanovy a dokumenty']) ?>
      </div>
      <div class="sl-6 od-7"><?= dokumenty_html($dokumentyKlub) ?></div>
    </div>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
