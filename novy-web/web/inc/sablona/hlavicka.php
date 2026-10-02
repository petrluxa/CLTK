<?php
/* Hlavička veřejného webu: <!DOCTYPE>, <head>, stuha + menu se znakem,
   informační lišta a začátek <main id="obsah">. Konec vypíše paticka.php.

   Stránka nejdřív připraví data (a zpracuje POST / header()), pak:
       $sablona = ['titulek' => 'Areál a služby', 'popis' => '…', 'css' => [], 'js' => []];
       require __DIR__ . '/inc/sablona/hlavicka.php';
       … obsah stránky (sekce) …
       require __DIR__ . '/inc/sablona/paticka.php';
   Popis voleb $sablona je v head.php, celý návod ve web/PRUVODCE-STRANKY.md. */

require_once __DIR__ . '/komponenty.php';

$sablona = $sablona ?? [];
$hlMenu = menu_hlavni();
$hlRezervace = rezervace_url();
$hlTel = setting('recepce_telefon');
$hlNazev = setting('klub_nazev', 'I. Český Lawn-Tennis Klub Praha');
$hlZkratka = setting('klub_zkratka', 'I. ČLTK Praha');
$hlOznameni = aktivni_oznameni();
$hlTridaHtml = rezim_je_zapnuty() ? 's-pruhem' : '';
$hlTridaBody = trim((string)($sablona['trida'] ?? ''));
sablona_bezpecnostni_hlavicky();          // Content-Security-Policy – musí odejít před prvním výstupem

/* Položka menu (desktop i mobil jsou tentýž seznam) */
$hlPolozka = static function (array $m, int $i): string {
    $ma = !empty($m['podmenu']);
    $idPod = 'podmenu-' . ($i + 1);
    $tridy = 'nav__polozka' . ($ma ? ' ma-podmenu' : '') . (!empty($m['aktivni']) ? ' je-aktivni' : '');
    $aktualni = !$m['externi'] && $m['soubor'] !== '' && here() === $m['soubor'] ? ' aria-current="page"' : '';
    $h = '<li class="' . $tridy . '"><div class="nav__radek">';
    $h .= '<a class="nav__odkaz" href="' . e($m['url']) . '"' . $aktualni . ($m['externi'] ? ' target="_blank" rel="noopener"' : '') . '>'
        . typo($m['nazev']) . ($m['externi'] ? '<span class="nav__ven" aria-hidden="true"></span><span class="vh"> (web v novém okně)</span>' : '') . '</a>';
    if ($ma) {
        $h .= '<button class="nav__rozbal" type="button" aria-expanded="false" aria-controls="' . $idPod . '">'
            . '<span class="vh">Podmenu ' . e($m['nazev']) . '</span></button>';
    }
    $h .= '</div>';
    if ($ma) {
        $h .= '<ul class="podmenu" id="' . $idPod . '">';
        foreach ($m['podmenu'] as $p) {
            $h .= '<li><a href="' . e($p['url']) . '"' . (!empty($p['aktivni']) ? ' aria-current="page"' : '') . '>' . typo($p['nazev']) . '</a></li>';
        }
        $h .= '</ul>';
    }
    return $h . '</li>';
};
?><!DOCTYPE html>
<html lang="cs"<?= $hlTridaHtml !== '' ? ' class="' . e($hlTridaHtml) . '"' : '' ?>>
<head>
<?php require __DIR__ . '/head.php'; ?>
</head>
<body<?= $hlTridaBody !== '' ? ' class="' . e($hlTridaBody) . '"' : '' ?>>
<?= rezim_pruh() ?>
<a class="preskocit" href="#obsah">Přeskočit na obsah</a>
<header class="hlavicka" id="hlavicka">
  <div class="stuha" aria-hidden="true"><span class="stuha__zlato"></span><span class="stuha__navy"></span></div>
  <?php /* Horní lišta z návrhu 1: při rolování zajede a logo se zmenší (jen desktop). */ ?>
  <div class="hlavicka__servis wrap">
    <p class="hlavicka__jmeno"><span class="sc"><?= typo($hlNazev) ?></span><span class="zalozen">Založen 1893</span></p>
    <ul class="servis">
      <?php if ($hlTel !== ''): ?><li><a class="tel" href="<?= e(tel_href($hlTel)) ?>">Recepce <?= e(preg_replace('/\s+/', "\u{00A0}", trim(preg_replace('/^\+420\s*/', '', trim($hlTel))))) ?></a></li><?php endif; ?>
      <?php $hlObsazenost = bezpecny_odkaz(setting('obsazenost_url')); if ($hlObsazenost !== ''): ?><li><a href="<?= e($hlObsazenost) ?>" target="_blank" rel="noopener">Obsazenost kurtů<span class="vh"> (v novém okně)</span></a></li><?php endif; ?>
      <li><a href="<?= e(url('kontakt.php')) ?>"<?= here() === 'kontakt.php' ? ' aria-current="page"' : '' ?>>Kontakt</a></li>
    </ul>
  </div>
  <div class="hlavicka__hlavni wrap">
    <a class="znak" href="<?= e(url('index.php')) ?>"<?= here() === 'index.php' ? ' aria-current="page"' : '' ?>>
      <img src="<?= e(logo_url('svg')) ?>" width="224" height="256" alt="<?= e($hlZkratka) ?> – úvodní stránka">
      <span class="znak__text" aria-hidden="true"><span class="znak__jmeno"><?= typo($hlZkratka) ?></span><span class="znak__rok">Založen <?= e(setting('zalozeno', '1893')) ?></span></span>
    </a>
    <nav class="nav" id="menu" aria-label="Hlavní navigace">
      <ul class="nav__seznam nav__seznam--l">
        <?php foreach ($hlMenu as $i => $m) if ($m['strana'] === 'l') echo $hlPolozka($m, $i); ?>
      </ul>
      <ul class="nav__seznam nav__seznam--p">
        <?php foreach ($hlMenu as $i => $m) if ($m['strana'] === 'p') echo $hlPolozka($m, $i); ?>
        <?php if ($hlRezervace !== ''): ?>
        <li class="jen-desktop"><a class="btn btn--mala" href="<?= e($hlRezervace) ?>" target="_blank" rel="noopener">Rezervovat kurt<span class="vh"> (rezervační systém v novém okně)</span></a></li>
        <?php endif; ?>
      </ul>
      <div class="nav__pata">
        <?php if ($hlRezervace !== ''): ?>
        <a class="btn" href="<?= e($hlRezervace) ?>" target="_blank" rel="noopener">Rezervovat kurt <?= sipka('ven') ?><span class="vh"> (rezervační systém v novém okně)</span></a>
        <?php endif; ?>
        <div class="nav__pata-radek">
          <?php if ($hlTel !== ''): ?><a href="<?= e(tel_href($hlTel)) ?>">Recepce <?= str_replace(' ', '&nbsp;', e(tel_kratce($hlTel))) ?></a><?php endif; ?>
          <a href="<?= e(url('kontakt.php')) ?>"<?= nav_active('kontakt.php') ?>>Kontakt</a>
        </div>
      </div>
    </nav>
    <button class="menu-tlacitko" type="button" aria-expanded="false" aria-controls="menu"><span class="menu-tlacitko__text">Menu</span><span class="menu-tlacitko__car" aria-hidden="true"></span></button>
  </div>
</header>
<?php if ($hlOznameni): ?>
<div class="lista-info" role="region" aria-label="Oznámení klubu" data-lista-info>
  <?php /* Běžící text: app.js sadu zpráv naklonuje (kopie aria-hidden) a rozjede ji.
           Bez JS nebo s omezeným pohybem zůstanou zprávy stát uprostřed. */ ?>
  <div class="lista-info__okno">
    <div class="lista-info__pas">
      <div class="lista-info__sada">
        <?php foreach ($hlOznameni as $o):
          $oOdkaz = bezpecny_odkaz((string)$o['odkaz']); ?>
        <p class="lista-info__zprava"><?= $oOdkaz !== '' ? '<a href="' . e($oOdkaz) . '"' . odkaz_attr($oOdkaz) . '>' . typo((string)$o['text']) . '</a>' : typo((string)$o['text']) ?></p>
        <span class="lista-info__oddel" aria-hidden="true"></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <button class="lista-info__pauza" type="button" aria-pressed="false" hidden><span class="lista-info__pauza-ikona" aria-hidden="true"></span><span class="vh">Zastavit běžící oznámení</span></button>
</div>
<?php endif; ?>
<main id="obsah" tabindex="-1">
