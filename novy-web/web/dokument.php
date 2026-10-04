<?php
/* Dokument klubu jako stránka webu v klubovém stylu: stanovy, pravidla hraní a rezervací,
   provozní řády, osobní údaje členů, ceník, plán areálu (dokument.php?d=stanovy).
   Klient 2. 10. 2026: dokumenty nejsou PDF – stránka vypadá jako klubový hlavičkový papír
   (list papíru se znakem, nadpisem a patičkou klubu) a její tisk (Vytisknout / Ctrl+P) PDF nahrazuje.
   Obsah: administrace → Dokumenty („Adresa stránky“ = slug, „Text dokumentu“ z editoru,
   vypisuje se přes html_ocistit()). Sazba (číslované body, články, závěrečné upozornění…)
   se doplní jen pro výpis v dokument_sazba() – v databázi zůstávají holé značky.
   – neznámá adresa = 404,
   – dokument bez textu (výjimečně jen odkaz) přesměruje na svůj odkaz,
   – skrytý dokument vidí jen přihlášený správce (náhled s upozorněním, noindex).
   Vzhled a tisk: assets/css/dokument.css. */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';

$slug = is_string($_GET['d'] ?? null) ? strtolower(trim((string)$_GET['d'])) : '';
$spravce = $slug !== '' && rezim_je_spravce();
$d = $slug !== '' ? dokument_podle_slugu($slug, $spravce) : null;
if ($d && !dokument_ma_text($d)) {
    $jinam = dokument_soubor_url($d);
    if ($jinam !== '' && (int)$d['visible'] === 1) redirect($jinam);
    $d = null;
}
if (!$d) {
    stranka_nenalezena('Tento dokument jsme <em>nenašli</em>.',
        'Dokument mohl být přejmenován, nebo už na webu není. Všechny dokumenty klubu najdete na stránce Dokumenty.');
}
track_visit();

/** Text pro porovnání nadpisů: malá písmena, bez diakritiky, číslic, interpunkce a mezer navíc. */
function dokument_srovnej(string $s): string {
    $s = mb_strtolower(html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $s = (string)iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace("\u{00A0}", ' ', $s));
    return trim((string)preg_replace('/[^a-z]+/', ' ', strtolower($s)));
}

/**
 * Sazba textu dokumentu pro výpis (vstup = už vyčištěné HTML z html_ocistit()):
 *  – úvodní <h2> shodný s názvem nebo krátkým popisem dokumentu a řádek jen s názvem klubu
 *    (obojí je v záhlaví listu) zmizí,
 *  – odstavec vydavatele před formálním nadpisem („… jako provozovatel objektu vydává …“) dostane třídu,
 *  – nadpis článku „Článek I.<br>Základní údaje“ / „I. Základní ustanovení“ / „3.1. Druhy členství“ → číslo zvlášť,
 *  – číslované body („1.“, „2.1.“, „3.1.1.“, „a)“, „ii)“) s visícím číslem a odsazením podle úrovně,
 *  – poznámky pod čarou „* …“, závěrečné upozornění (poslední odstavec celý tučně) v rámečku,
 *  – tabulky do posuvného obalu, dlouhý výčet krátkých položek (popisky plánu) do sloupců.
 * Vrací [html, siroky] – široký list pro tabulky a obrázky.
 */
function dokument_sazba(string $html, string $nazev, string $popis = ''): array {
    $html = trim($html);
    $klub = [dokument_srovnej(setting('klub_zkratka', 'I. ČLTK Praha')), dokument_srovnej(setting('klub_nazev', 'I. Český Lawn-Tennis Klub Praha'))];
    if (preg_match('~^<p>(.*?)</p>\s*~su', $html, $m) && in_array(dokument_srovnej($m[1]), $klub, true)) {
        $html = (string)substr($html, strlen($m[0]));
    }
    if (preg_match('~^<h2>(.*?)</h2>\s*~su', $html, $m)) {
        $a = dokument_srovnej($m[1]);
        $b = dokument_srovnej($nazev);
        $c = dokument_srovnej($popis);                       // „Oznámení o zpracování…“ = popis pod nadpisem listu
        if ($a !== '' && ($a === $b || str_contains($b, $a) || str_contains($a, $b) || $a === $c)) $html = (string)substr($html, strlen($m[0]));
    }
    // vydavatel řádu: první odstavec, za kterým hned stojí formální nadpis
    $html = (string)preg_replace('~^<p>(.*?)</p>(\s*<h2>)~su', '<p class="dokument__vydavatel">$1</p>$2', $html, 1);

    $mezera = '(?:\s|&nbsp;|\x{00A0})+';
    // nadpisy článků; dvojtečka na konci oddílu („Obecná pravidla:“) v kapitálkách s linkou jen překáží
    $html = (string)preg_replace_callback('~<h3>(.*?)</h3>~su', static function (array $m) use ($mezera): string {
        $v = trim($m[1]);
        if (preg_match('~^(.*?)<br>(.*)$~su', $v, $x)) {
            return '<h3 class="s-clankem"><span class="dokument__clanek">' . trim($x[1]) . '</span><span class="dokument__h3">' . trim($x[2]) . '</span></h3>';
        }
        $v = (string)preg_replace('~\s*:$~u', '', $v);
        if (preg_match('~^((?:[IVXL]+|\d+)\.)' . $mezera . '(.+)$~su', $v, $x)) {
            return '<h3><span class="dokument__cislo">' . $x[1] . '</span> ' . $x[2] . '</h3>';
        }
        return '<h3>' . $v . '</h3>';
    }, $html);
    $html = (string)preg_replace('~<h4>((?:Ad' . $mezera . ')?\d+(?:\.\d+)*\.(?:' . $mezera . '[a-z]\))?)' . $mezera . '~u',
        '<h4><span class="dokument__cislo">$1</span> ', $html);

    // číslované body; „i)“ za „h)“ je písmeno, „i)“ jinde římská číslice
    $pismeno = '';
    $html = (string)preg_replace_callback('~<p>(.*?)</p>~su', static function (array $m) use ($mezera, &$pismeno): string {
        $v = $m[1];
        if (preg_match('~^(\d{1,2}(?:\.\d{1,2}){0,3}\.)' . $mezera . '(.+)$~su', $v, $x)) {
            $hloubka = min(3, substr_count($x[1], '.'));
            $pismeno = '';
            return '<p class="bod bod--c' . $hloubka . '"><span class="bod__c">' . $x[1] . '</span><span class="bod__t">' . $x[2] . '</span></p>';
        }
        if (preg_match('~^([a-z]|i{1,3}|iv|vi{0,3}|ix|x)\)' . $mezera . '(.+)$~su', $v, $x)) {
            $dalsi = $pismeno === '' ? 'a' : chr(ord($pismeno) + 1);
            $rimska = strlen($x[1]) > 1 || ($x[1] !== $dalsi && in_array($x[1], ['i', 'v', 'x'], true));
            if (!$rimska) $pismeno = $x[1];
            return '<p class="bod ' . ($rimska ? 'bod--r' : 'bod--p') . '"><span class="bod__c">' . $x[1] . ')</span><span class="bod__t">' . $x[2] . '</span></p>';
        }
        if (preg_match('~^(\*{1,3})' . $mezera . '(.+)$~su', $v, $x)) {
            return '<p class="dokument__pozn"><span class="bod__c">' . $x[1] . '</span><span class="bod__t">' . $x[2] . '</span></p>';
        }
        return $m[0];
    }, $html);

    // závěrečné upozornění: poslední odstavec, který je celý tučně
    $html = (string)preg_replace('~<p><strong>((?:(?!</?strong>).)+)</strong></p>\s*$~su', '<p class="dokument__zaver">$1</p>', $html);
    // podpis na konci („Prezident I. ČLTK Praha<br>Ing. Petr Šimůnek“) – funkce a jméno vpravo nad linkou
    $html = (string)preg_replace('~<p>([^<]{2,60})<br>([^<]{2,60})</p>\s*$~u',
        '<p class="dokument__podpis"><span class="dokument__podpis-funkce">$1</span><span class="dokument__podpis-jmeno">$2</span></p>', $html);
    // krátký samostatný řádek verzálkami celý tučně („STANOVY:“ za preambulí) – vyhlášení na střed
    $html = (string)preg_replace_callback('~<p><strong>([^<]{2,40})</strong></p>~u', static function (array $m): string {
        $t = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return mb_strtoupper($t) === $t && preg_match('~\p{Lu}{3}~u', $t) ? '<p class="dokument__vyhlaseni">' . $m[1] . '</p>' : $m[0];
    }, $html);

    // široký list: tabulky (ceník) nebo dokument, který je hlavně obrázkem (plán areálu)
    $siroky = str_contains($html, '<table>') || str_starts_with($html, '<figure>');
    $html = dokument_tabulky($html);                         // vlastní obal tabulky (místo html_tabulky_obal)
    $html = (string)preg_replace_callback('~<ul>(.*?)</ul>~su', static function (array $x): string {
        preg_match_all('~<li>(.*?)</li>~su', $x[1], $li);
        $kratke = count($li[1]) >= 12 && max(array_map(static fn(string $t): int => mb_strlen(html_text($t)), $li[1])) <= 40;
        return $kratke ? '<ul class="dokument__sloupce">' . $x[1] . '</ul>' : $x[0];
    }, $html);
    return [$html, $siroky];
}

/**
 * Tabulky dokumentu (ceník) pro výpis: buňky dostanou data-label z hlavičky sloupce (na mobilu
 * se řádek skládá pod sebe jako karta s popisky) a cena za hodinu za zalomením „(480,–/hod)“
 * vlastní obal (na obrazovce pod cenou, v tisku na stejném řádku – tabulka je nižší).
 */
function dokument_tabulky(string $html): string {
    if (!str_contains($html, '<table>')) return $html;
    return (string)preg_replace_callback('~<table>(.*?)</table>~su', static function (array $t): string {
        $vnitrek = $t[1];
        $popisky = [];
        if (preg_match('~<thead>\s*<tr>(.*?)</tr>~su', $vnitrek, $h) && preg_match_all('~<th>(.*?)</th>~su', $h[1], $th)) {
            $popisky = array_map(static fn(string $x): string => html_text($x), $th[1]);
        }
        $vnitrek = (string)preg_replace_callback('~<tbody>(.*?)</tbody>~su', static function (array $b) use ($popisky): string {
            return '<tbody>' . preg_replace_callback('~<tr>(.*?)</tr>~su', static function (array $r) use ($popisky): string {
                $i = 0;
                return '<tr>' . preg_replace_callback('~<td>(.*?)</td>~su', static function (array $td) use (&$i, $popisky): string {
                    $l = $popisky[$i] ?? '';
                    $prvni = $i++ === 0;
                    // „12 480 Kč<br>(480,–/hod)“ → cena a cena za hodinu
                    $obsah = (string)preg_replace('~<br>\s*(\([^<()]*\))\s*$~u', ' <span class="dokument__za-hod">$1</span>', trim($td[1]));
                    if ($prvni || $l === '') return '<td>' . $obsah . '</td>';
                    return '<td data-label="' . e($l) . '"><span class="dokument__hodnota">' . $obsah . '</span></td>';
                }, $r[1]) . '</tr>';
            }, $b[1]) . '</tbody>';
        }, $vnitrek);
        return '<div class="tabulka-box"><table class="tabulka tabulka--karty">' . $vnitrek . '</table></div>';
    }, $html);
}

$nazev = (string)$d['nazev'];
$skryty = (int)$d['visible'] !== 1;
$kategorie = DOKUMENTY_KATEGORIE[(string)$d['kategorie']] ?? 'Dokument klubu';
$popis = trim((string)$d['popis']);
[$text, $siroky] = dokument_sazba(html_ocistit((string)$d['text']), $nazev, $popis);
// adresa stránky pod vytištěným dokumentem – bez „https://www.“, jen k opsání
$adresaStranky = (string)preg_replace('~^https?://(www\.)?~i', '', site_url('dokument.php?d=' . rawurlencode((string)$d['slug'])));

$klubNazev = setting('klub_nazev', 'I. Český Lawn-Tennis Klub Praha');
$zalozeno = setting('zalozeno', '1893');
$adresa = trim(setting('adresa_ulice') . ', ' . setting('adresa_mesto'), ', ');
$recTel = setting('recepce_telefon');
$recMail = setting('recepce_email');
$ico = setting('ico');

$ostatni = array_values(array_filter(dokumenty(), fn($x) => (int)$x['id'] !== (int)$d['id'] && dokument_url($x) !== ''));

$sablona = [
    'titulek' => $nazev,
    'popis'   => $popis !== '' ? $nazev . ' – ' . $popis : $nazev . ' – ' . uryvek(html_text($text), 140),
    'css'     => ['dokument.css'],
    'trida'   => 'stranka-dokument',
    'noindex' => $skryty,
];
require __DIR__ . '/inc/sablona/hlavicka.php';
?>

<div class="dokument-stul">
  <div class="wrap dokument-stul__wrap<?= $siroky ? ' dokument-stul__wrap--siroky' : '' ?>">
    <div class="dokument-lista netisknout">
      <?= drobky([['Klub', 'klub.php'], ['Dokumenty', 'dokumenty.php'], [$nazev]]) ?>
      <div class="dokument-lista__akce">
        <button class="odkaz" type="button" data-tisk hidden>Vytisknout <span class="dokument-lista__ikona" aria-hidden="true"></span></button>
        <?= tlacitko('dokumenty.php', 'Všechny dokumenty', 'odkaz') ?>
      </div>
    </div>
    <?php if ($skryty): ?>
    <p class="dokument__skryty netisknout" role="status"><span class="doplni">skrytý dokument</span> Na webu není vidět – vidíte ho jen jako přihlášený správce.</p>
    <?php endif; ?>

    <article class="dokument-list" aria-labelledby="dokument-nadpis">
      <header class="dokument-list__hlava">
        <img class="dokument-list__znak" src="<?= e(logo_url()) ?>" alt="" width="58" height="66">
        <p class="dokument-list__klub"><span class="sc"><?= typo($klubNazev) ?></span><span class="dokument-list__zalozen">Založen <?= e($zalozeno) ?></span></p>
        <div class="ozdoba dokument-list__ozdoba" aria-hidden="true"><span></span></div>
        <p class="stitek dokument-list__kategorie"><?= typo($kategorie) ?></p>
        <h1 class="dokument-list__nadpis" id="dokument-nadpis"><?= typo($nazev) ?></h1>
        <?php if ($popis !== ''): ?><p class="dokument-list__popis"><?= typo($popis) ?></p><?php endif; ?>
      </header>

      <div class="prose dokument-text">
        <?= $text ?>
      </div>

      <footer class="dokument-list__pata">
        <p><span class="sc nowrap"><?= typo($klubNazev) ?></span><?php if ($adresa !== ''): ?> · <span class="nowrap"><?= typo($adresa) ?></span><?php endif; ?><?php if ($ico !== ''): ?> · <span class="nowrap">IČO <?= e($ico) ?></span><?php endif; ?></p>
        <?php if ($recTel !== '' || je_email($recMail)): ?>
        <p><span class="nowrap">Recepce<?php if ($recTel !== ''): ?> <a href="<?= e(tel_href($recTel)) ?>"><?= e($recTel) ?></a><?php endif; ?></span><?php if (je_email($recMail)): ?> · <a href="mailto:<?= e($recMail) ?>"><?= e($recMail) ?></a><?php endif; ?></p>
        <?php endif; ?>
        <p class="jen-tisk dokument-list__adresa"><?= e($adresaStranky) ?></p>
      </footer>
    </article>
  </div>
</div>

<?php if ($ostatni): ?>
<section class="sekce sekce--tesna dokument__dalsi netisknout" aria-labelledby="dalsi-nadpis">
  <div class="wrap mrizka">
    <div class="sl-4">
      <header class="hlava">
        <p class="hlava__znacka"><span class="stitek">Dokumenty</span></p>
        <h2 class="h2" id="dalsi-nadpis">Další dokumenty <em>klubu</em></h2>
      </header>
      <p class="dokument__vse"><?= tlacitko('dokumenty.php', 'Všechny dokumenty a archiv turnajů', 'odkaz') ?></p>
    </div>
    <div class="sl-7 od-6"><?= dokumenty_html($ostatni) ?></div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
