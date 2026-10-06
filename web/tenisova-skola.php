<?php
/* Tenisová škola Markéty Vondroušové – Informace: jak škola funguje (modul Tenisová
   škola → Informace), kategorie podle věku, jak se přihlásit, kontakt a rozcestník
   na další stránky školy. Texty jsou bloky stránky „tenisova-skola“ (modul Stránky:
   uvod, informace, kategorie, prihlaska, kontakt), lidé z modulu Trenéři (zařazení
   „tenisová škola“). Žádná jména dětí. */
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
if (!function_exists('tenis_polozky')) {
    /**
     * Text bloku z editoru rozebraný na skupiny odrážek – šablona je pak vysází jako
     * desku, stupně nebo řádky. Vrací [['nadpis' => html, 'polozky' => [['stitek' => html,
     * 'text' => html, 'cele' => html], …], 'odstavce' => html], …]. Nadpis skupiny = h2–h4
     * před seznamem, štítek položky = tučný začátek odrážky („<strong>2019</strong> mistr ČR“).
     * Vstup projde html_ocistit(), takže výstup tvoří jen povolené holé značky.
     */
    function tenis_polozky(?string $html): array {
        $cisty = html_ocistit((string)$html);
        if ($cisty === '') return [];
        $doc = new DOMDocument('1.0', 'UTF-8');
        $stav = libxml_use_internal_errors(true);
        $ok = $doc->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body><div id="tenis-obal">' . $cisty . '</div></body></html>',
                             LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($stav);
        $obal = $ok ? $doc->getElementById('tenis-obal') : null;
        if (!$obal) return [];
        $html = static function (iterable $uzly) use ($doc): string {
            $h = '';
            foreach ($uzly as $u) $h .= $doc->saveHTML($u);
            return trim($h);
        };
        $skupiny = [];
        $akt = ['nadpis' => '', 'polozky' => [], 'odstavce' => ''];
        $uzavri = static function () use (&$akt, &$skupiny): void {
            if ($akt['nadpis'] !== '' || $akt['polozky'] || $akt['odstavce'] !== '') $skupiny[] = $akt;
            $akt = ['nadpis' => '', 'polozky' => [], 'odstavce' => ''];
        };
        foreach ($obal->childNodes as $u) {
            $z = $u instanceof DOMElement ? strtolower($u->nodeName) : '';
            if (in_array($z, ['h2', 'h3', 'h4'], true)) {
                $uzavri();
                $akt['nadpis'] = $html($u->childNodes);
            } elseif ($z === 'ul' || $z === 'ol') {
                foreach ($u->childNodes as $li) {
                    if (!$li instanceof DOMElement || strtolower($li->nodeName) !== 'li') continue;
                    $deti = iterator_to_array($li->childNodes);
                    $i = 0;
                    while (isset($deti[$i]) && $deti[$i] instanceof DOMText && trim((string)$deti[$i]->nodeValue, " \t\n\r\u{00A0}") === '') $i++;
                    $stitek = '';
                    $zbytek = $deti;
                    if (isset($deti[$i]) && $deti[$i] instanceof DOMElement && strtolower($deti[$i]->nodeName) === 'strong') {
                        $stitek = $html($deti[$i]->childNodes);
                        $zbytek = array_slice($deti, $i + 1);
                    }
                    $text = (string)preg_replace('/^(?:[\s\x{00A0}]|&nbsp;|[–—:\-])+/u', '', $html($zbytek));
                    $akt['polozky'][] = ['stitek' => $stitek, 'text' => $text, 'cele' => $html($li->childNodes)];
                }
            } elseif ($u instanceof DOMElement || trim((string)$u->nodeValue) !== '') {
                $akt['odstavce'] .= $doc->saveHTML($u);
            }
        }
        $uzavri();
        return $skupiny;
    }
}
if (!function_exists('tenis_karta_stranky')) {
    /** Karta odkazu na jinou stránku webu: název stránky, nadpis a perex z jejího bloku „uvod“
     *  (perex zkrácený na celé věty, nejvýš asi 180 znaků). */
    function tenis_karta_stranky(string $soubor, string $nazevStranky): string {
        $b = blok(preg_replace('/\.php$/', '', $soubor), 'uvod');
        $nadpis = trim((string)$b['nadpis']) !== '' ? html_inline((string)$b['nadpis']) : typo($nazevStranky);
        $perex = html_text((string)$b['perex']);
        if (mb_strlen($perex) > 180) {
            $perex = preg_match('/^(.{40,180}?[.!?…])(?=\s+\p{Lu})/u', $perex, $m) ? $m[1] : uryvek($perex, 150);
        }
        return '<li><a class="karta tenis-rozcestnik__karta" href="' . e(url($soubor)) . '">'
             . '<p class="stitek karta__stitek">' . typo($nazevStranky) . '</p>'
             . '<h3 class="karta__nazev">' . $nadpis . '</h3>'
             . ($perex !== '' ? '<p class="tenis-rozcestnik__perex">' . typo($perex) . '</p>' : '')
             . '<p class="tenis-rozcestnik__dal"><span class="odkaz" aria-hidden="true">Otevřít stránku ' . sipka() . '</span></p>'
             . '</a></li>';
    }
}
/* ---------- konec pomůcek ---------- */

$S = 'tenisova-skola';
$uvod       = blok($S, 'uvod');
$bInfo      = blok($S, 'informace');
$bKategorie = blok($S, 'kategorie');
$bPrihlaska = blok($S, 'prihlaska');
$bKontakt   = blok($S, 'kontakt');
/* Blok, který slouží jen jako nadpis sekce (obsah je z jiného modulu): když chybí, nemá hlásit „doplní klub“. */
$jenHlava = static fn(array $b): array => $b['existuje'] ? $b : ['doplni_klub' => 0] + $b;

$info      = skola('info');
$kategorie = [];
foreach (tenis_polozky((string)$bKategorie['text']) as $sk) foreach ($sk['polozky'] as $p) $kategorie[] = $p;

/** Míček kategorie – Babolat, sponzor Tenisové školy (Petr 6. 10. 2026; do 5. 10. HEAD): minitenis červený
    (Babolat Red), střední kurt oranžový (Orange), babytenis zelený (Green). Podle názvu kategorie, když ho klub
    přejmenuje, tak podle věku 7 / 8 / 9 let. Obrázky: podklady/klient-micky/babolat/vyrez.py → assets/img/micek-*.webp */
function tenis_micek(string $nazev, string $vek): ?array {
    $micky = ['cerveny' => 'Červený míček Babolat Red', 'oranzovy' => 'Oranžový míček Babolat Orange', 'zeleny' => 'Zelený míček Babolat Green'];
    $n = mb_strtolower(html_text($nazev));
    $barva = str_contains($n, 'mini') ? 'cerveny' : (str_contains($n, 'střed') ? 'oranzovy' : (str_contains($n, 'baby') ? 'zeleny' : ''));
    if ($barva === '' && preg_match('/\b([789])\b/u', html_text($vek), $m)) $barva = ['7' => 'cerveny', '8' => 'oranzovy', '9' => 'zeleny'][$m[1]];
    if ($barva === '' || !is_file(WEB_ROOT . '/assets/img/micek-' . $barva . '.webp')) return null;
    return ['src' => asset('img/micek-' . $barva . '.webp'), 'alt' => $micky[$barva]];
}
$kroky = [];
foreach (tenis_polozky((string)$bPrihlaska['text']) as $sk) foreach ($sk['polozky'] as $p) $kroky[] = $p;

/* Kontakty trenérů školy (telefon / e-mail) – kdo už je v textu bloku „kontakt“, se neopakuje. */
$kontaktCislice = preg_replace('/\D+/', '', html_text((string)$bKontakt['text']));
$kontaktText = mb_strtolower(html_text((string)$bKontakt['text']));
$treneriKontakty = array_values(array_filter(treneri('skola'), static function (array $t) use ($kontaktCislice, $kontaktText): bool {
    $tel = preg_replace('/\D+/', '', (string)$t['telefon']);
    $mail = mb_strtolower(trim((string)$t['email']));
    if ($tel === '' && !je_email($mail)) return false;
    $telBezPredvolby = preg_replace('/^420/', '', $tel);
    if ($telBezPredvolby !== '' && str_contains((string)$kontaktCislice, $telBezPredvolby)) return false;
    if ($mail !== '' && str_contains($kontaktText, $mail)) return false;
    return true;
}));

$sablona = [
    'titulek' => 'Tenisová škola',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-tenis.css'],
    'obrazek' => (string)$uvod['foto'],
    'trida'   => 'stranka-tenis stranka-tenisova-skola',
];
require __DIR__ . '/inc/sablona/hlavicka.php';
$sekce = 0;
?>

<?= hlava_stranky(array_merge($uvod, ['odkaz' => '', 'odkaz2' => '']), ['drobky' => [['Tenisová škola']], 'nadpis' => 'Tenisová škola', 'navic' => blok_tlacitka($uvod) . tenis_podnav()]) ?>

<?php if ($info || $bInfo['existuje']): $sekce++; ?>
<!-- Jak škola funguje: odstavce z modulu Tenisová škola → Informace -->
<section class="sekce sekce--bez-horni tenis-info" id="informace" aria-labelledby="informace-nadpis">
  <div class="wrap">
    <div class="mrizka">
      <div class="sl-4">
        <?= hlava_sekce($info ? $jenHlava($bInfo) : $bInfo, ['cislo' => $sekce, 'id' => 'informace-nadpis', 'stitek' => 'Informace', 'nadpis' => 'Informace']) ?>
        <?= $bInfo['existuje'] ? blok_text($bInfo) : '' ?>
      </div>
      <div class="sl-7 od-6">
        <?php if ($info): ?>
        <ol class="rimsky">
          <?php foreach ($info as $r):
            $rOdkaz = tlacitko((string)$r['odkaz'], 'Podrobnosti', 'odkaz'); ?>
          <li>
            <b><?= typo((string)$r['nazev']) ?></b>
            <?= paragraphs((string)$r['text']) ?>
            <?php if ($rOdkaz !== ''): ?><p class="tenis-info__odkaz"><?= $rOdkaz ?></p><?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ol>
        <?php else: ?>
        <p><?= doplni_klub('informace doplní klub') ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($kategorie || $bKategorie['existuje']): $sekce++; ?>
<!-- Kategorie podle věku (minitenis, střední kurt, babytenis) -->
<section class="sekce sekce--papir2" id="kategorie" aria-labelledby="kategorie-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bKategorie, ['cislo' => $sekce, 'id' => 'kategorie-nadpis', 'radek' => true, 'stitek' => 'Kategorie podle věku', 'nadpis' => 'Kategorie podle věku']) ?>
    <?php if ($kategorie): ?>
    <ul class="tenis-kategorie" role="list" style="--polozek: <?= min(4, count($kategorie)) ?>">
      <?php foreach ($kategorie as $k):
        /* „do 7 let (podle ročníku)“ → velké „do 7 let“, zbytek drobně */
        $vek = '';
        $zbytek = $k['stitek'] !== '' ? $k['text'] : $k['cele'];
        if (preg_match('/^((?:do|od)(?:\s|&nbsp;|\x{00A0})+\d+(?:\s*[–-]\s*\d+)?(?:\s|&nbsp;|\x{00A0})+let)(.*)$/us', $zbytek, $m)) {
            $vek = (string)preg_replace('/^(do|od)(?:\s|&nbsp;|\x{00A0})+/u', '<small>$1</small>', $m[1]);
            $zbytek = trim((string)preg_replace('/^[\s,]*\((.*)\)\s*$/us', '$1', trim($m[2])));
        } ?>
      <?php $micek = tenis_micek((string)$k['stitek'], $vek); ?>
      <li class="karta karta--zlata<?= $micek ? ' tenis-kategorie--s-mickem' : '' ?>">
        <?php if ($micek): /* barevný míček + zlatavá kopie navrchu (s myší v klidu vidět ta, po najetí zmizí) */ ?><span class="tenis-kategorie__micek"><img src="<?= e($micek['src']) ?>" width="120" height="120" alt="<?= e($micek['alt']) ?>" title="<?= e($micek['alt']) ?>" loading="lazy" decoding="async"><img class="tenis-kategorie__micek-ton" src="<?= e($micek['src']) ?>" width="120" height="120" alt="" aria-hidden="true" loading="lazy" decoding="async"></span><?php endif; ?>
        <?php if ($k['stitek'] !== ''): ?><h3 class="karta__nazev"><?= $k['stitek'] ?></h3><?php endif; ?>
        <?php if ($vek !== ''): ?><span class="tenis-kategorie__vek"><?= $vek ?></span><?php endif; ?>
        <?php if ($zbytek !== ''): ?><p class="tenis-kategorie__text"><?= $zbytek ?></p><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <?php if (!$kategorie) echo blok_text($bKategorie); ?>
  </div>
</section>
<?php endif; ?>

<?php if ($kroky || $bPrihlaska['existuje']): $sekce++; ?>
<!-- Jak se přihlásit: kroky z bloku „prihlaska“ -->
<section class="sekce" id="prihlaska" aria-labelledby="prihlaska-nadpis">
  <div class="wrap">
    <?= hlava_sekce($bPrihlaska, ['cislo' => $sekce, 'id' => 'prihlaska-nadpis', 'stitek' => 'Přihláška', 'nadpis' => 'Jak se přihlásit']) ?>
    <?php if ($kroky): ?>
    <ol class="proces tenis-proces" style="--kroku: <?= min(5, count($kroky)) ?>">
      <?php foreach ($kroky as $k): ?>
      <li><?php if ($k['stitek'] !== ''): ?><b><?= $k['stitek'] ?></b><?php endif; ?><?= $k['stitek'] !== '' ? $k['text'] : $k['cele'] ?></li>
      <?php endforeach; ?>
    </ol>
    <?php else: ?>
    <?= blok_text($bPrihlaska) ?>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php $sekce++; ?>
<!-- Kontakt: vedoucí trenér (blok „kontakt“) a další kontakty trenérského týmu -->
<section class="sekce sekce--papir2" id="kontakt" aria-labelledby="kontakt-nadpis">
  <div class="wrap">
    <?= hlava_sekce(array_merge($bKontakt, ['perex' => '']), ['cislo' => $sekce, 'id' => 'kontakt-nadpis', 'stitek' => 'Kontakt', 'nadpis' => 'Kontakt']) ?>
    <div class="tenis-kontakt">
      <div class="karta karta--zlata">
        <?php if (trim((string)$bKontakt['perex']) !== ''): ?><div class="tenis-karta-text"><?= paragraphs((string)$bKontakt['perex']) ?></div><?php endif; ?>
        <?= blok_text($bKontakt, 'prose tenis-kontakt__text') ?>
        <?= blok_tlacitka($bKontakt) ?>
      </div>
      <?php if ($treneriKontakty): ?>
      <div>
        <h3 class="stitek tenis-kontakt__nadpis">Trenérský tým Tenisové školy</h3>
        <ul class="radky">
          <?php foreach ($treneriKontakty as $t):
            $tel = trim((string)$t['telefon']);
            $mail = trim((string)$t['email']); ?>
          <li class="radek">
            <span class="radek__nazev"><?= typo((string)$t['jmeno']) ?></span>
            <span class="radek__hodnota"><?php if ($tel !== ''): ?><a href="<?= e(tel_href($tel)) ?>"><?= str_replace(' ', '&nbsp;', e($tel)) ?></a><?php endif; ?><?php if ($tel === '' && je_email($mail)): ?><a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a><?php endif; ?></span>
            <span class="radek__pozn"><?= typo((string)$t['role']) ?><?php if ($tel !== '' && je_email($mail)): ?> · <a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a><?php endif; ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
        <p class="tenis-pozn"><?= tlacitko('tenisova-skola-treneri.php', 'Celý trenérský tým', 'odkaz') ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Rozcestník: další stránky Tenisové školy (texty z jejich hlaviček) -->
<section class="sekce" id="dalsi" aria-labelledby="dalsi-nadpis">
  <div class="wrap">
    <h2 class="stitek stitek--velky tenis-rozcestnik-nadpis" id="dalsi-nadpis">Tenisová škola</h2>
    <ul class="tenis-rozcestnik" role="list">
      <?= tenis_karta_stranky('tenisova-skola-ceniky.php', 'Ceníky') ?>
      <?= tenis_karta_stranky('tenisova-skola-rozvrhy.php', 'Rozvrhy') ?>
      <?= tenis_karta_stranky('tenisova-skola-treneri.php', 'Trenérský tým') ?>
      <?= tenis_karta_stranky('letni-kempy.php', 'Letní kempy') ?>
    </ul>
  </div>
</section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
