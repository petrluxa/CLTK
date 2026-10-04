<?php
/* Kontakt – recepce a kancelář, lidé a role, adresa a příjezd s plánem
   areálu, fakturační údaje a dokumenty. Stránka mimo hlavní menu (odkaz
   „Všechny kontakty“ v patičce).
   Obsah: Texty a údaje (kontakty, adresa, příjezd, IČO, účty, mapa, sítě),
   modul Vedení (kancelář a další kontakty – u koho je zobrazit_kontakt = 0,
   telefon ani e-mail se nevypíše: Petr Vaníček, Vladislav Šavrda), Stránky
   (bloky „kontakt“, plán z bloku areal/plan) a Dokumenty. */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
track_visit();

/* ---------- data ---------- */
$uvod     = blok('kontakt', 'uvod');
$bLide    = blok('kontakt', 'lide');
$bPrijezd = blok('kontakt', 'prijezd');
$bFakt    = blok('kontakt', 'fakturace');
$bDoky    = blok('kontakt', 'dokumenty');
$bPlan    = blok('areal', 'plan');

$nazev   = setting('klub_nazev', 'I. Český Lawn-Tennis Klub Praha');
$ulice   = setting('adresa_ulice');
$mesto   = setting('adresa_mesto');
$mapaUrl = bezpecny_odkaz(setting('mapa_url'));
$rezervace  = rezervace_url();
$obsazenost = bezpecny_odkaz(setting('obsazenost_url'));
[$recepce, $kancelar] = kontakty_paticka();

/* Lidé: kancelář (bez osoby, která má vlastní kartu nahoře) a další kontakty */
$kartaJmeno = mb_strtolower(trim(setting('kancelar_jmeno')));
$lideKancelar = array_values(array_filter(vedeni('kancelar'), fn($o) => mb_strtolower(trim((string)$o['jmeno'])) !== $kartaJmeno));
$lideDalsi = vedeni('kontakt');

$planFoto = (string)$bPlan['foto'];
$maPlan = $planFoto !== '' && is_file(UPLOAD_DIR . '/' . $planFoto);

/* Fakturační údaje – jen z Textů a údajů; co chybí, „doplní klub“ */
$fakturace = [
    ['Název', typo($nazev), ''],
    ['Sídlo', typo(trim($ulice . ', ' . $mesto, ', ')), ''],
    ['IČO', e(setting('ico')), ''],
    ['DIČ', '', ''],
];
$ucty = [];
foreach ([['ucet_clenstvi', 'Účet pro členství a tréninky'], ['ucet_iban', 'IBAN'], ['ucet_skola', 'Účet Tenisové školy a kempů']] as [$k, $popis]) {
    if (setting($k) !== '') $ucty[] = [$popis, e(setting($k))];
}

$doky = dokumenty();
$site = site_odkazy();

/** Karta kontaktu (recepce / kancelář): název, popis, telefon, e-mail, případně tlačítka. */
function kontakt_karta(array $k, string $navic = ''): string {
    $h = '<div class="karta karta--zlata kontakt-karta">';
    $h .= '<h3 class="kontakt-karta__nazev">' . typo((string)$k['nazev']) . '</h3>';
    if (trim((string)$k['popis']) !== '') $h .= '<p class="kontakt-karta__popis">' . typo((string)$k['popis']) . '</p>';
    $spojeni = '';
    if (trim((string)$k['telefon']) !== '') $spojeni .= '<a class="kontakt-karta__tel" href="' . e(tel_href((string)$k['telefon'])) . '">' . e((string)$k['telefon']) . '</a>';
    if (je_email((string)$k['email'])) $spojeni .= '<a href="mailto:' . e((string)$k['email']) . '">' . e((string)$k['email']) . '</a>';
    $h .= $spojeni !== '' ? '<p class="kontakt-karta__spojeni">' . $spojeni . '</p>' : '<p class="kontakt-karta__spojeni">' . doplni_klub('kontakt doplní klub') . '</p>';
    return $h . $navic . '</div>';
}

$sablona = [
    'titulek' => html_text((string)$uvod['stitek']) ?: 'Kontakt',
    'popis'   => html_text((string)$uvod['perex']) ?: ($nazev . ', ' . $ulice . ', ' . $mesto),
    'css'     => ['stranky-areal.css'],
    'trida'   => 'stranka-kontakt',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<?= hlava_stranky($uvod, ['drobky' => [['Kontakt']], 'nadpis' => 'Kontakt']) ?>

<!-- I · Recepce a kancelář -->
<section class="sekce sekce--bez-horni" id="recepce" aria-label="Recepce a kancelář klubu">
  <div class="wrap kontakt-karty">
    <?= kontakt_karta($recepce, ($rezervace !== '' || $obsazenost !== '')
        ? '<div class="akce">' . ($rezervace !== '' ? tlacitko($rezervace, 'Rezervovat kurt', 'btn', ['trida' => 'btn--mala']) : '') . ($obsazenost !== '' ? tlacitko($obsazenost, 'Obsazenost kurtů', 'odkaz') : '') . '</div>' : '') ?>
    <?= kontakt_karta($kancelar, '<div class="akce">' . tlacitko('clenstvi.php', 'Členství v klubu', 'odkaz') . '</div>') ?>
  </div>
</section>

<?php if ($lideKancelar || $lideDalsi): ?>
<!-- II · Lidé a role -->
<section class="sekce sekce--papir2" id="lide" aria-labelledby="lide-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bLide, ['cislo' => 1, 'id' => 'lide-nadpis', 'nadpis' => 'Kancelář klubu']) ?>
    <?php if ($lideKancelar): ?>
    <div class="skupina-osob">
      <h3 class="h5 skupina-osob__nazev">Kancelář klubu</h3>
      <ul class="osoby osoby--bez-fotek"><?php foreach ($lideKancelar as $o) echo osoba_html($o, ['foto' => false]); ?></ul>
    </div>
    <?php endif; ?>
    <?php if ($lideDalsi): ?>
    <div class="skupina-osob">
      <h3 class="h5 skupina-osob__nazev">Další kontakty</h3>
      <ul class="osoby osoby--bez-fotek"><?php foreach ($lideDalsi as $o) echo osoba_html($o, ['foto' => false]); ?></ul>
    </div>
    <?php endif; ?>
    <?php $odkazLide = tlacitko($bLide['odkaz'], $bLide['odkaz_text'], 'odkaz'); if ($odkazLide !== ''): ?><div class="odkazy-radek"><?= $odkazLide ?></div><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- III · Adresa a příjezd -->
<section class="sekce" id="prijezd" aria-labelledby="prijezd-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bPrijezd, ['cislo' => 2, 'id' => 'prijezd-nadpis', 'nadpis' => 'Adresa a příjezd']) ?>
    <div class="prijezd">
      <div>
        <address class="prijezd__adresa"><?= typo($ulice) ?><br><?= typo($mesto) ?><small><?= typo($nazev) ?></small></address>
        <?php if (setting('prijezd_text') !== ''): ?><div class="prijezd__text"><?= paragraphs(setting('prijezd_text')) ?></div><?php endif; ?>
        <div class="odkazy-radek">
          <?= $mapaUrl !== '' ? tlacitko($mapaUrl, 'Mapa', 'odkaz') : '' ?>
          <?= tlacitko('areal.php#plan', 'Plán areálu', 'odkaz') ?>
          <?= tlacitko('areal.php#parkoviste', 'Parkoviště', 'odkaz') ?>
        </div>
      </div>
      <?php if ($maPlan): ?>
      <div>
        <a class="prijezd__plan" href="<?= e(url('areal.php#plan')) ?>">
          <figure class="ramec ramec--linka">
            <div class="ramec__obraz"><?= obr($planFoto, html_text((string)$bPlan['perex']) ?: 'Plán areálu', ['class' => 'foto']) ?></div>
            <figcaption class="popisek"><span><?= trim((string)$bPlan['foto_popisek']) !== '' ? html_inline((string)$bPlan['foto_popisek']) : 'Plán areálu' ?></span><span>Interaktivní plán <?= sipka() ?></span></figcaption>
          </figure>
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- IV · Fakturační údaje -->
<section class="sekce sekce--papir2" id="fakturace" aria-labelledby="fakt-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bFakt, ['cislo' => 3, 'id' => 'fakt-nadpis', 'nadpis' => 'Fakturační údaje']) ?>
    <div class="fakturace">
      <dl>
        <?php foreach ($fakturace as [$co, $hodnota]): ?>
        <div><dt><?= e($co) ?></dt><dd><?= $hodnota !== '' ? $hodnota : doplni_klub() ?></dd></div>
        <?php endforeach; ?>
      </dl>
      <dl>
        <?php if ($ucty): foreach ($ucty as [$co, $hodnota]): ?>
        <div><dt><?= typo($co) ?></dt><dd><?= $hodnota ?></dd></div>
        <?php endforeach; else: ?>
        <div><dt>Bankovní účet</dt><dd><?= doplni_klub() ?></dd></div>
        <?php endif; ?>
      </dl>
    </div>
  </div>
</section>

<?php if ($doky || $site): ?>
<!-- V · Dokumenty a sítě -->
<section class="sekce" id="dokumenty" aria-labelledby="doky-nadpis">
  <div class="wrap mrizka">
    <div class="sl-5">
      <?= hlava_sekce($bDoky, ['cislo' => 4, 'id' => 'doky-nadpis', 'nadpis' => 'Dokumenty']) ?>
      <?php if ($site): ?>
      <div class="site-odkazy">
        <p class="stitek">Klub na sítích</p>
        <?= pilulky_html(array_map(fn($s) => [$s['nazev'], $s['url']], $site)) ?>
      </div>
      <?php endif; ?>
      <?php if (setting('paticka_ctc') !== ''): ?><p class="drobne kontakt-ctc"><?= typo(setting('paticka_ctc')) ?></p><?php endif; ?>
    </div>
    <?php if ($doky): ?>
    <div class="sl-7 od-6">
      <?= dokumenty_html($doky) ?>
      <p class="kontakt-doky__vse"><?= tlacitko('dokumenty.php', 'Všechny dokumenty a archiv turnajů', 'odkaz') ?></p>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
