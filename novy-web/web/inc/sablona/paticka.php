<?php
/* Patička veřejného webu podle PDF klienta (stranka-05/06): slepotisk znaku,
   název klubu, „Založen 1893“, čtyři sloupce (Adresa a příjezd · Kontakty ·
   Důležité informace · Dokumenty a sítě), Přístupnost a tmavý spodní pruh.
   Všechny texty a kontakty jsou z Textů a údajů (setting(), data.php).
   Uzavírá <main> otevřený v hlavicka.php a končí </html>. */

$ptNazev = setting('klub_nazev', 'I. Český Lawn-Tennis Klub Praha');
$ptUlice = setting('adresa_ulice', 'Ostrov Štvanice 38');
$ptMesto = setting('adresa_mesto', '170 00 Praha 7');
$ptMapa = bezpecny_odkaz(setting('mapa_url'));
$ptKontakty = kontakty_paticka();
$ptDokumenty = dokumenty_paticka();
$ptSite = site_odkazy();
$ptSiteHlavni = array_values(array_filter($ptSite, fn($s) => $s['klic'] !== 'fotogalerie_url'));
$ptGalerie = array_values(array_filter($ptSite, fn($s) => $s['klic'] === 'fotogalerie_url'));
$ptRezervace = rezervace_url();
$ptTel = setting('recepce_telefon');
$ptOdkazVen = fn(string $u) => odkaz_je_externi($u) ? ' target="_blank" rel="noopener"' : '';
?>
</main>

<footer class="paticka" id="paticka">
  <div class="wrap">
    <div class="paticka__pecet">
      <div class="pecet" aria-hidden="true"><img src="<?= e(logo_url('svg')) ?>" width="224" height="256" alt="" loading="lazy" decoding="async"></div>
      <p class="paticka__nazev"><?= preg_replace('~(\S+-\S+)~u', '<span class="nowrap">$1</span>', typo($ptNazev)) ?></p>
      <p class="paticka__zalozen">Založen <?= e(setting('zalozeno', '1893')) ?></p>
    </div>

    <div class="paticka__mrizka">
      <section aria-labelledby="pat-adresa">
        <h2 id="pat-adresa">Adresa a&nbsp;příjezd</h2>
        <address>
          <p><b><?= typo($ptUlice) ?></b><br><?= typo($ptMesto) ?></p>
        </address>
        <?php if (setting('prijezd_kratce') !== ''): ?><p class="paticka__prijezd"><?= typo(setting('prijezd_kratce')) ?></p><?php endif; ?>
        <p><a href="<?= e(url('areal.php#plan')) ?>">Plánek areálu</a><?php if ($ptMapa !== ''): ?> · <a href="<?= e($ptMapa) ?>"<?= $ptOdkazVen($ptMapa) ?>>Mapa<span class="vh"> (v novém okně)</span></a><?php endif; ?></p>
      </section>

      <section aria-labelledby="pat-kontakty">
        <h2 id="pat-kontakty">Kontakty</h2>
        <?php foreach ($ptKontakty as $k): if (trim((string)$k['nazev']) === '') continue; ?>
        <p class="paticka__kontakt">
          <b><?= typo((string)$k['nazev']) ?></b>
          <?php if (trim((string)$k['popis']) !== ''): ?><span><?= typo((string)$k['popis']) ?></span><?php endif; ?>
          <?php if (trim((string)$k['telefon']) !== ''): ?><a href="<?= e(tel_href((string)$k['telefon'])) ?>"><?= str_replace(' ', '&nbsp;', e((string)$k['telefon'])) ?></a><?php endif; ?>
          <?php if (je_email((string)$k['email'])): ?><a href="mailto:<?= e((string)$k['email']) ?>"><?= e((string)$k['email']) ?></a><?php endif; ?>
        </p>
        <?php endforeach; ?>
        <p><a href="<?= e(url('kontakt.php')) ?>">Všechny kontakty</a></p>
      </section>

      <section aria-labelledby="pat-informace">
        <h2 id="pat-informace">Důležité informace</h2>
        <ul>
          <?php foreach (menu_paticka() as $p): ?>
          <li><a href="<?= e($p['url']) ?>"<?= nav_active($p['soubor']) ?>><?= typo($p['nazev']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </section>

      <section aria-labelledby="pat-dokumenty">
        <h2 id="pat-dokumenty">Dokumenty a&nbsp;sítě</h2>
        <ul>
          <?php foreach ($ptDokumenty as $dk): $dkUrl = dokument_url($dk); if ($dkUrl === '') continue; ?>
          <li><a href="<?= e($dkUrl) ?>"<?= $ptOdkazVen($dkUrl) ?>><?= typo(trim((string)$dk['paticka_text']) ?: (string)$dk['nazev']) ?><?= odkaz_je_externi($dkUrl) ? '<span class="vh"> (v novém okně)</span>' : '' ?></a></li>
          <?php endforeach; ?>
          <?php if ($ptSiteHlavni): ?>
          <li><?= implode(' · ', array_map(fn($s) => '<a href="' . e($s['url']) . '" target="_blank" rel="noopener">' . e($s['nazev']) . '<span class="vh"> (v novém okně)</span></a>', $ptSiteHlavni)) ?></li>
          <?php endif; ?>
          <?php foreach ($ptGalerie as $s): ?>
          <li><a href="<?= e($s['url']) ?>" target="_blank" rel="noopener"><?= e($s['nazev']) ?><span class="vh"> (v novém okně)</span></a></li>
          <?php endforeach; ?>
        </ul>
        <?php if (setting('paticka_ctc') !== ''): ?><p class="paticka__ctc drobne"><?= typo(setting('paticka_ctc')) ?></p><?php endif; ?>
      </section>
    </div>

    <section class="paticka__pristupnost-sekce" aria-labelledby="pat-pristupnost">
      <h2 id="pat-pristupnost">Přístupnost</h2>
      <p class="drobne" style="max-width:24rem"><?= typo(setting('paticka_pristupnost', 'Web respektuje nastavení vašeho zařízení. Volbu si zapamatujeme.')) ?></p>
      <div class="paticka__pristupnost">
        <button class="prepinac-pristupnost" type="button" data-prepinac="pohyb" aria-pressed="false">Omezit pohyb</button>
        <button class="prepinac-pristupnost" type="button" data-prepinac="kontrast" aria-pressed="false">Vyšší kontrast</button>
      </div>
    </section>
  </div>
  <div class="paticka__pruh">
    <div class="wrap">
      <p>© <?= date('Y') ?> <?= typo(setting('paticka_pruh', $ptNazev . ' · IČO ' . setting('ico', '45243077'))) ?></p>
      <p><a href="<?= e(url('admin/')) ?>" rel="nofollow">Správa webu</a></p>
    </div>
  </div>
</footer>

<?php if ($ptRezervace !== '' || $ptTel !== ''): ?>
<nav class="lista-mobil" aria-label="Rezervace a telefon">
  <?php if ($ptRezervace !== ''): ?><a class="lista-mobil__rezervace" href="<?= e($ptRezervace) ?>" target="_blank" rel="noopener">Rezervovat kurt<small>online</small><span class="vh"> (v novém okně)</span></a><?php endif; ?>
  <?php if ($ptTel !== ''): ?><a class="lista-mobil__telefon" href="<?= e(tel_href($ptTel)) ?>">Zavolat recepci<small><?= str_replace(' ', '&nbsp;', e(tel_kratce($ptTel))) ?></small></a><?php endif; ?>
</nav>
<?php endif; ?>
<svg class="defs-skryte" aria-hidden="true" focusable="false" width="0" height="0" style="position:absolute;width:0;height:0;overflow:hidden">
  <defs>
    <filter id="duotone-navy" color-interpolation-filters="sRGB">
      <feColorMatrix type="matrix" values="0.2126 0.7152 0.0722 0 0  0.2126 0.7152 0.0722 0 0  0.2126 0.7152 0.0722 0 0  0 0 0 1 0"/>
      <feComponentTransfer>
        <feFuncR type="table" tableValues="0.082 0.984"/>
        <feFuncG type="table" tableValues="0.153 0.980"/>
        <feFuncB type="table" tableValues="0.267 0.965"/>
      </feComponentTransfer>
    </filter>
  </defs>
</svg>
</body>
</html>
