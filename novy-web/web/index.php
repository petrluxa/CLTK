<?php
/* Úvodní stránka I. ČLTK Praha – podle PDF klienta (ZADANI.md §4,
   podklady/klient-pdf/stranka-00 … 06) v jazyce Varianty 4.
   Pořadí: úvod s galerií · aktuality · výsledky · kalendář · členství a video
           · služby v areálu · naše historie · partneři · patička.
   Všechen obsah je z databáze (moduly administrace) a z Textů a údajů. */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
track_visit();

/* ---------- data ---------- */
$bUvod      = blok('index', 'uvod');
$bAktuality = blok('index', 'aktuality');
$bVysledky  = blok('index', 'vysledky');
$bKalendar  = blok('index', 'kalendar');
$bClenstvi  = blok('index', 'clenstvi');
$bSluzby    = blok('index', 'sluzby');
$bHistorie  = blok('index', 'historie');
$bPartneri  = blok('index', 'partneri');

$galerie   = uvodni_galerie();
$aktuality = aktuality(3);
$vysledky  = posledni_vysledky(8);
$sluzbyUvod = sluzby(true);
/* Plán areálu pod videem (mapa.js, kompaktní rozložení); „Podrobnosti“ u služby vedou na areal.php#kotva */
$mapaUvod = mapa_data(null, null, null, url('areal.php'));
$triptych  = triptych();
$partneri  = partneri();

/* Kalendář: výchozí okno = nejbližší nadcházející akce podle data (server);
   kalendar.js totéž přepočítá podle data návštěvníka stejným pravidlem. */
$kalendar = kalendar_data();
foreach ($kalendar['akce'] as $i => $ka) {
    if ($ka['prihlaseni']) $kalendar['akce'][$i]['token'] = verejny_token('akce-' . $ka['id']);   // podepsaný token bez relace
}
$kalendar['souhlas'] = trim((string)blok('formulare', 'souhlas')['perex'])
    ?: 'Souhlasím se zpracováním uvedených osobních údajů I. Českým Lawn-Tennis Klubem Praha za účelem vyřízení přihlášky.';
$kalendar['ladeni'] = sablona_ladeni_dnes() !== '';    // ?dnes= jen lokálně – JS pak počítá s datem serveru
$kalVychozi = null;
foreach ($kalendar['akce'] as $ka) if ($ka['id'] === $kalendar['vychozi_id']) $kalVychozi = $ka;

/* Video členství (Texty a údaje → Úvodní strana) */
$video720  = setting('video_720');
$video1080 = setting('video_1080');
$videoPoster = setting('video_poster');
$maVideo = ($video720 !== '' && is_file(UPLOAD_DIR . '/' . $video720)) || ($video1080 !== '' && is_file(UPLOAD_DIR . '/' . $video1080));
$posterUrl = '';
if ($videoPoster !== '') $posterUrl = upload_url(webp_vedle($videoPoster) ?: $videoPoster);

/* Fokus fotky (object-position) jen v bezpečném tvaru „44% 28%“ */
$fokus = static fn(?string $f, string $vychozi = '50% 40%') => preg_match('/^\d{1,3}(\.\d+)?%\s+\d{1,3}(\.\d+)?%$/', trim((string)$f)) ? trim((string)$f) : $vychozi;

/* Hero fotka do preloadu (WebP, když je) */
$predpripojit = [];
if ($galerie && $galerie[0]['typ'] === 'foto' && $galerie[0]['foto'] !== '') {
    $prvni = webp_vedle($galerie[0]['foto']);
    $predpripojit[] = ['href' => upload_url($prvni ?: $galerie[0]['foto']), 'as' => 'image', 'type' => $prvni ? 'image/webp' : '', 'fetchpriority' => 'high'];
}

$sablona = [
    'titulek' => '',
    'popis'   => 'I. Český Lawn-Tennis Klub Praha – tenis na ostrově Štvanice uprostřed Prahy od roku ' . setting('zalozeno', '1893')
               . '. Členství, ceník kurtů, tenisová škola, závodní tenis, klubový kalendář a historie klubu.',
    'css'     => ['index.css', 'mapa.css'],
    'js'      => ['galerie.js', 'vysledky.js', 'kalendar.js', 'video.js', 'cenik.js', 'mapa.js'],
    'trida'   => 'stranka-uvod',
    'obrazek' => $galerie && $galerie[0]['typ'] === 'foto' ? $galerie[0]['foto'] : '',
    'predpripojit' => $predpripojit,
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

  <!-- 1 · Úvod: deska s nadpisem a galerie se štítem -->
  <section class="uvod" id="uvod" aria-labelledby="uvod-nadpis">
    <div class="uvod__plocha wrap">
      <?php if (trim((string)$bUvod['stitek']) !== ''): ?>
      <p class="uvod__misto stitek stitek--tlumeny"><?= typo((string)$bUvod['stitek']) ?></p>
      <?php endif; ?>

      <figure class="uvod__obraz ramec ramec--linka galerie" data-galerie data-interval="7000" aria-roledescription="galerie" aria-label="Hráči klubu: <?= e((string)count($galerie)) ?> <?= e(sklonuj(count($galerie), 'snímek', 'snímky', 'snímků')) ?>">
        <div class="ramec__obraz galerie__okno">
          <?php if (!$galerie): ?>
          <div class="galerie__snimek galerie__snimek--deska je-aktivni"><div class="bjk"><p class="bjk__titul"><?= e(setting('klub_zkratka', 'I. ČLTK Praha')) ?></p><p class="bjk__misto">Založen <?= e(setting('zalozeno', '1893')) ?></p></div></div>
          <?php endif; ?>
          <?php foreach ($galerie as $i => $g):
            $gPopisek = html_inline((string)$g['popisek']); ?>
          <?php if ($g['typ'] === 'deska' || trim((string)$g['foto']) === ''): ?>
          <div class="galerie__snimek galerie__snimek--deska<?= $i === 0 ? ' je-aktivni' : '' ?>" data-popisek="<?= e($gPopisek) ?>" data-kredit="<?= e((string)$g['kredit']) ?>">
            <div class="bjk" role="img" aria-label="<?= e((string)$g['alt']) ?>">
              <?php if ($g['deska_stitek'] !== ''): ?><p class="bjk__stitek" aria-hidden="true"><?= typo((string)$g['deska_stitek']) ?></p><?php endif; ?>
              <?php if ($g['deska_titul'] !== ''): ?><p class="bjk__titul" aria-hidden="true"><?= html_inline((string)$g['deska_titul']) ?></p><?php endif; ?>
              <?php if ($g['deska_skore'] !== '' || $g['deska_tym_a'] !== ''): ?>
              <p class="bjk__zapas" aria-hidden="true"><span><?= typo((string)$g['deska_tym_a']) ?></span><span class="bjk__skore"><?= e((string)$g['deska_skore']) ?></span><span><?= typo((string)$g['deska_tym_b']) ?></span></p>
              <?php endif; ?>
              <?php if ($g['deska_hrac'] !== ''): ?>
              <p class="bjk__detail" aria-hidden="true"><span class="sc"><?= typo((string)$g['deska_hrac']) ?></span><?= $g['deska_souper'] !== '' ? ' – ' . typo((string)$g['deska_souper']) : '' ?><?php if ($g['deska_sety'] !== ''): ?> <span class="bjk__sety"><?= e((string)$g['deska_sety']) ?></span><?php endif; ?></p>
              <?php endif; ?>
              <?php if ($g['deska_misto'] !== ''): ?><p class="bjk__misto" aria-hidden="true"><?= typo((string)$g['deska_misto']) ?></p><?php endif; ?>
            </div>
          </div>
          <?php else: ?>
          <div class="galerie__snimek<?= $i === 0 ? ' je-aktivni' : '' ?>" style="--fokus: <?= e($fokus((string)$g['fokus'])) ?>" data-popisek="<?= e($gPopisek) ?>" data-kredit="<?= e((string)$g['kredit']) ?>">
            <?= obr((string)$g['foto'], (string)$g['alt'], $i === 0
                ? ['class' => 'foto', 'loading' => 'eager', 'fetchpriority' => 'high']
                : ['class' => 'foto', 'loading' => 'lazy']) ?>
          </div>
          <?php endif; ?>
          <?php endforeach; ?>
          <svg class="galerie__linka" viewBox="0 0 100 110.7" aria-hidden="true" focusable="false"><path d="M85.6 0.2 L69.9 3.7 L57.2 5.0 L37.8 4.7 L25.9 3.2 L14.7 0.5 L13.4 0.7 L1.2 27.6 L2.5 29.4 L5.7 31.8 L9.7 35.8 L10.9 38.6 L10.9 42.8 L10.2 46.3 L2.2 70.9 L0.5 77.6 L0.5 83.3 L1.0 85.6 L2.2 88.3 L4.5 91.0 L8.0 93.5 L16.7 97.3 L27.4 100.0 L35.8 101.5 L40.5 103.0 L45.5 106.0 L49.5 110.0 L53.5 105.7 L57.2 103.5 L63.4 101.5 L75.6 99.3 L84.6 96.8 L91.5 93.5 L96.3 89.8 L98.5 86.1 L99.3 83.6 L99.3 77.4 L97.5 70.6 L89.3 46.0 L88.3 42.0 L88.3 38.3 L89.6 35.6 L97.8 28.4 L97.8 27.4Z" vector-effect="non-scaling-stroke"/></svg>
        </div>
        <?php if ($galerie): ?>
        <figcaption class="galerie__pata">
          <?php if (count($galerie) > 1): ?>
          <div class="galerie__ovladani">
            <ol class="galerie__rejstrik" aria-label="Snímky galerie">
              <?php foreach ($galerie as $i => $g): ?>
              <li><button type="button" data-snimek="<?= $i ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>><span class="galerie__rim"><?= e(rimske($i + 1)) ?></span><span class="galerie__jmeno"><?= typo((string)$g['rejstrik']) ?></span><span class="vh"> – snímek <?= $i + 1 ?></span><span class="galerie__cas" aria-hidden="true"></span></button></li>
              <?php endforeach; ?>
            </ol>
            <button class="galerie__pauza" type="button" data-galerie-pauza aria-pressed="false"><span class="galerie__pauza-ikona" aria-hidden="true"></span><span class="galerie__pauza-text">Zastavit</span><span class="vh"> střídání snímků</span></button>
          </div>
          <?php endif; ?>
          <p class="galerie__popisek" aria-live="polite"><span class="galerie__popisek-text"><?= html_inline((string)$galerie[0]['popisek']) ?></span><span class="kredit galerie__kredit"><?= e((string)$galerie[0]['kredit']) ?></span></p>
        </figcaption>
        <?php endif; ?>
      </figure>

      <div class="uvod__deska">
        <h1 class="display uvod__nadpis" id="uvod-nadpis"><?= trim((string)$bUvod['nadpis']) !== '' ? html_inline((string)$bUvod['nadpis']) : 'Tenis na ostrově uprostřed Prahy od roku <em>' . e(setting('zalozeno', '1893')) . '</em>.' ?></h1>
        <?php if (trim((string)$bUvod['perex']) !== ''): ?><div class="perex"><?= paragraphs((string)$bUvod['perex']) ?></div><?php endif; ?>
        <?= blok_tlacitka($bUvod) ?>
      </div>
    </div>
  </section>

  <?php if ($aktuality): ?>
  <!-- 2 · Aktuality z klubu (nejsou to články – fotka, nadpis, krátký popis) -->
  <section class="sekce sekce--papir2 aktuality" id="aktuality" aria-labelledby="aktuality-nadpis">
    <div class="aktuality__vodoznak" aria-hidden="true"><img src="<?= e(logo_url('svg')) ?>" width="224" height="256" alt="" loading="lazy" decoding="async"></div>
    <div class="wrap wrap--uzky">
      <h2 class="stitek stitek--velky aktuality__stitek" id="aktuality-nadpis"><?= typo(trim((string)$bAktuality['stitek']) ?: 'Aktuality z klubu') ?></h2>
      <ul class="aktuality__mrizka">
        <?php foreach ($aktuality as $a):
          $aOdkaz = bezpecny_odkaz((string)$a['odkaz']); ?>
        <li class="aktualita">
          <?php $aFoto = obr((string)$a['foto'], '', ['class' => 'foto', 'fokus' => $fokus((string)$a['fokus'], '50% 50%')]); ?>
          <?php if ($aFoto !== ''): ?>
          <div class="aktualita__foto ramec ramec--linka"><div class="ramec__obraz"><?= $aFoto ?></div></div>
          <?php endif; ?>
          <h3 class="aktualita__nadpis"><?php if ($aOdkaz !== ''): ?><a href="<?= e($aOdkaz) ?>"<?= odkaz_attr($aOdkaz) ?>><?= typo((string)$a['nadpis']) ?><?= odkaz_je_externi($aOdkaz) ? '<span class="vh"> (v novém okně)</span>' : '' ?></a><?php else: ?><?= typo((string)$a['nadpis']) ?><?php endif; ?></h3>
          <?php if (trim((string)$a['popis']) !== ''): ?><div class="aktualita__popis"><?= paragraphs((string)$a['popis']) ?></div><?php endif; ?>
          <?php if ($aOdkaz !== '' && trim((string)$a['odkaz_text']) !== ''): ?><p class="aktualita__odkaz"><span class="odkaz" aria-hidden="true"><?= typo((string)$a['odkaz_text']) ?> <?= sipka(odkaz_je_externi($aOdkaz) ? 'ven' : '') ?></span></p><?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($vysledky): ?>
  <!-- 3 · Aktuální výsledky našich hráčů – vodorovný pás se šipkami -->
  <section class="sekce vysledky-sekce" id="vysledky" aria-labelledby="vysledky-nadpis">
    <div class="wrap wrap--uzky">
      <div class="pas" data-pas>
        <div class="pas__hlava">
          <h2 class="stitek stitek--velky vysledky-sekce__stitek" id="vysledky-nadpis"><?= typo(trim((string)$bVysledky['stitek']) ?: 'Aktuální výsledky našich hráčů') ?></h2>
          <div class="pas__sipky">
            <button class="pas__sipka" type="button" data-pas-zpet aria-controls="vysledky-pas" aria-label="Předchozí výsledky"><?= sipka('zpet') ?></button>
            <button class="pas__sipka" type="button" data-pas-dal aria-controls="vysledky-pas" aria-label="Další výsledky"><?= sipka() ?></button>
          </div>
        </div>
        <ul class="pas__drazka" id="vysledky-pas" tabindex="0" role="region" aria-label="Výsledky hráčů klubu – posunujte do strany">
          <?php foreach ($vysledky as $v) echo vysledek_karta_html($v); ?>
        </ul>
        <div class="pas__stav" aria-hidden="true"><span></span></div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- 4 · Klubový kalendář: rok vlevo, okno s detailem nejbližší akce vpravo -->
  <section class="sekce sekce--papir2 kalendar" id="kalendar" aria-labelledby="kalendar-nadpis">
    <div class="wrap wrap--uzky">
      <p class="stitek stitek--velky"><?= typo(trim((string)$bKalendar['stitek']) ?: 'Klubový kalendář') ?></p>
      <h2 class="kalendar__nadpis" id="kalendar-nadpis"><?= trim((string)$bKalendar['nadpis']) !== '' ? html_inline((string)$bKalendar['nadpis']) : 'Na Štvanici se <em>potkáváme</em>.' ?></h2>
      <?php if (!$kalendar['akce']): ?>
      <p class="perex"><?= doplni_klub('kalendář na rok ' . $kalendar['rok'] . ' doplní klub') ?></p>
      <?php else: ?>
      <div class="kalendar__plocha" data-kalendar>
        <div class="kalendar__seznam">
          <h3 class="stitek kalendar__seznam-nadpis" id="kalendar-rok"><?= typo(trim((string)$bKalendar['perex']) ?: 'Klubový rok – vyberte událost') ?><span class="vh"> (<?= (int)$kalendar['rok'] ?>)</span></h3>
          <ol class="kal-seznam" aria-labelledby="kalendar-rok">
            <?php foreach ($kalendar['akce'] as $ka):
              $vybrana = $ka['id'] === $kalendar['vychozi_id']; ?>
            <li class="kal-polozka<?= $ka['probehla'] ? ' je-probehla' : '' ?>" data-akce="<?= (int)$ka['id'] ?>">
              <a href="<?= e($ka['detail_url']) ?>" data-akce-odkaz="<?= (int)$ka['id'] ?>"<?= $vybrana ? ' aria-current="true"' : '' ?>>
                <span class="kal-polozka__mesic"><?= e($ka['mesic_nazev']) ?></span>
                <span class="kal-polozka__nazev"><?= typo($ka['nazev']) ?><?php if ($ka['prihlaseni']): ?><span class="kal-polozka__prihlaska">přihlášky otevřené</span><?php endif; ?></span>
                <span class="kal-polozka__termin<?= $ka['bez_data'] && trim((string)$ka['termin']) === 'termín doplní klub' ? ' kal-polozka__termin--doplni' : '' ?>"><?= typo($ka['termin']) ?><?php if ($ka['probehla']): ?><span class="vh"> – proběhlo</span><?php endif; ?></span>
              </a>
            </li>
            <?php endforeach; ?>
          </ol>
        </div>
        <div class="kalendar__okno" id="kalendar-okno" tabindex="-1" role="region" aria-label="Detail vybrané akce">
          <?= $kalVychozi ? kalendar_detail_html($kalVychozi) : '' ?>
        </div>
      </div>
      <?= json_skript('kalendar-data', $kalendar) ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- 5 · Členství: nadpis, tlačítko a video přes celou šířku obsahu -->
  <section class="sekce sekce--bez-dolni clenstvi-uvod" id="clenstvi" aria-labelledby="clenstvi-nadpis">
    <div class="wrap wrap--uzky clenstvi-uvod__hlava">
      <h2 class="clenstvi-uvod__nadpis" id="clenstvi-nadpis"><?= trim((string)$bClenstvi['nadpis']) !== '' ? html_inline((string)$bClenstvi['nadpis']) : 'Členem se může stát <em>každý</em>.' ?></h2>
      <?= tlacitko(trim((string)$bClenstvi['odkaz']) ?: 'clenstvi.php', trim((string)$bClenstvi['odkaz_text']) ?: 'Stát se členem') ?>
    </div>
    <div class="wrap">
      <?php if ($maVideo): ?>
      <figure class="video ramec ramec--linka" data-video>
        <div class="ramec__obraz">
          <?php /* Zdroje mají jen data-src: video.js je doplní, až se video bude opravdu přehrávat
                   (Safari jinak stahuje i s preload="none"). Bez JavaScriptu přehrávač v <noscript>. */
          $videoZdroje = [];
          if ($video1080 !== '' && is_file(UPLOAD_DIR . '/' . $video1080)) $videoZdroje[] = [upload_url($video1080), '(min-width: 1100px)'];
          if ($video720 !== '' && is_file(UPLOAD_DIR . '/' . $video720)) $videoZdroje[] = [upload_url($video720), ''];
          $videoPoster = $posterUrl !== '' ? ' poster="' . e($posterUrl) . '"' : ''; ?>
          <video class="video__prehravac" muted loop playsinline preload="none"<?= $videoPoster ?> aria-label="Promo video I. ČLTK Praha – areál klubu na Štvanici, bez zvuku">
            <?php foreach ($videoZdroje as [$vUrl, $vMedia]): ?><source data-src="<?= e($vUrl) ?>" type="video/mp4"<?= $vMedia !== '' ? ' media="' . e($vMedia) . '"' : '' ?>><?php endforeach; ?>
          </video>
          <noscript><video class="video__nojs" muted loop playsinline controls preload="none"<?= $videoPoster ?> aria-label="Promo video I. ČLTK Praha, bez zvuku"><?php foreach ($videoZdroje as [$vUrl, $vMedia]): ?><source src="<?= e($vUrl) ?>" type="video/mp4"<?= $vMedia !== '' ? ' media="' . e($vMedia) . '"' : '' ?>><?php endforeach; ?></video></noscript>
          <button class="video__tlacitko" type="button" aria-pressed="false"><span class="video__ikona" aria-hidden="true"></span><span class="video__text">Přehrát video</span></button>
        </div>
      </figure>
      <?php else: ?>
      <?= blok_foto($bClenstvi, 'pomer-16x9') ?>
      <?php endif; ?>
    </div>
  </section>

  <?php if ($sluzbyUvod): ?>
  <!-- 6 · Služby v areálu – interaktivní plán areálu (mapa.js). Klik na službu ukáže místo
       na plánu, který zůstává vedle seznamu (na mobilu se k němu stránka vrátí).
       Bez JavaScriptu zůstanou pilulky s odkazy na části stránky Areál a služby. -->
  <section class="sekce sluzby-uvod" id="sluzby" aria-labelledby="sluzby-nadpis">
    <div class="wrap">
      <h2 class="stitek sluzby-uvod__stitek sluzby-uvod__stitek--mapa" id="sluzby-nadpis"><?= typo(trim((string)$bSluzby['stitek']) ?: 'Služby v areálu') ?></h2>
      <div class="mapa mapa--kompakt" data-mapa>
        <div class="mapa__zaloha sluzby-uvod__radek">
          <?= pilulky_html(array_map(fn($s) => [$s['nazev'], 'areal.php' . (trim((string)$s['kotva']) !== '' ? '#' . rawurlencode((string)$s['kotva']) : '')], $sluzbyUvod)) ?>
        </div>
      </div>
      <?= json_skript('mapa-data', $mapaUvod) ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($triptych): ?>
  <!-- 7 · Naše historie – tři wimbledonské trávy -->
  <section class="sekce sekce--papir2 historie-uvod" id="historie" aria-labelledby="historie-nadpis">
    <div class="wrap wrap--uzky">
      <h2 class="stitek stitek--velky historie-uvod__stitek" id="historie-nadpis"><?= typo(trim((string)$bHistorie['stitek']) ?: 'Naše historie') ?></h2>
    </div>
    <div class="wrap">
      <ul class="tri-travy">
        <?php foreach ($triptych as $t): ?>
        <li class="tri-trava">
          <?php $tFoto = obr((string)$t['foto'], (string)$t['alt'], ['fokus' => $fokus((string)$t['fokus'], '50% 25%')]); ?>
          <?php if ($tFoto !== ''): ?><div class="ramec ramec--linka"><div class="ramec__obraz"><?= $tFoto ?></div></div><?php endif; ?>
          <p class="tri-trava__rok"><?= e((string)$t['rok']) ?></p>
          <h3 class="tri-trava__jmeno"><?= typo((string)$t['jmeno']) ?></h3>
          <p class="tri-trava__disciplina"><?= typo((string)$t['disciplina']) ?></p>
        </li>
        <?php endforeach; ?>
      </ul>
      <p class="historie-uvod__akce"><?= tlacitko(trim((string)$bHistorie['odkaz']) ?: 'historie.php', trim((string)$bHistorie['odkaz_text']) ?: 'Kompletní historie') ?></p>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($partneri): ?>
  <!-- 8 · Partneři – 5 × 5 jednobarevných log -->
  <section class="sekce partneri-sekce" id="partneri" aria-labelledby="partneri-nadpis">
    <div class="wrap wrap--uzky">
      <h2 class="stitek stitek--velky partneri__stitek" id="partneri-nadpis"><?= typo(trim((string)$bPartneri['stitek']) ?: 'Partneři') ?></h2>
      <ul class="partneri__mrizka">
        <?php foreach ($partneri as $p):
          $pLogo = partner_logo_url($p);
          $pUrl = bezpecny_odkaz((string)$p['url']);
          [$pW, $pH] = obrazek_rozmer((string)($p['logo_mono'] ?: $p['logo']));
          $pImg = $pLogo !== ''
              ? '<img src="' . e($pLogo) . '" alt="' . e((string)$p['nazev']) . '"' . ($pW ? ' width="' . $pW . '" height="' . $pH . '"' : '') . ' loading="lazy" decoding="async">'
              : '<span class="sc">' . e((string)$p['nazev']) . '</span>'; ?>
        <li class="partner">
          <?php if ($pUrl !== ''): ?><a href="<?= e($pUrl) ?>"<?= odkaz_attr($pUrl) ?>><?= $pImg ?><?= odkaz_je_externi($pUrl) ? '<span class="vh"> (web partnera v novém okně)</span>' : '' ?></a>
          <?php else: ?><span><?= $pImg ?></span><?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
  <?php endif; ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>

