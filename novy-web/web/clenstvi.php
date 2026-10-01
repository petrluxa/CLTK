<?php
/* Členství – co je v ceně, živý konfigurátor ceny s náhledem Členského listu
   (Varianta 4, sekce 6 a 6b), celý ceník členství, postup přijetí, druhy
   členství podle stanov a přihláška do klubu ve čtyřech krocích.

   Všechny texty a ceny jsou z databáze: bloky stránky „clenstvi“ (modul
   Stránky), ceník „clenstvi“ (modul Ceníky), kontakty kanceláře (Texty
   a údaje), dokumenty (modul Dokumenty). Šablona obsahuje jen popisky polí.

   Přihláška: pole podle dnešní přihlášky klubu (obsah.json → clenstvi.prihlaska_pole)
   + zákonný zástupce u osob mladších 15 let + firma a IČO u firemního členství.
   Ukládá se do cltk_prihlasky_clenstvi (hlavní údaje do sloupců, celý formulář
   jako JSON do `data`). Bez relace a bez cookies: podepsaný token, past na roboty
   a brzda odeslání (verejny_formular_*). Po uložení POST → přesměrování → GET
   s podepsaným odkazem na poděkování (?odeslano=1&p=…), e-mail kanceláři jen při
   vyplněném „E-mailu pro přihlášky“. Bez JavaScriptu je formulář jedna dlouhá
   stránka; s JavaScriptem (assets/js/clenstvi.js) má kroky a shrnutí. */
require __DIR__ . '/inc/rezim.php';
require_once __DIR__ . '/inc/sablona/komponenty.php';
require_once __DIR__ . '/inc/mail.php';

/** Blok stránky (modul Stránky). Chybějící blok nedostane štítek „doplní klub“ –
    sekce použije náhradní nadpis, nebo se nevypíše (obsah stránky přitom je). */
function klub_blok(string $stranka, string $klic): array {
    $b = blok($stranka, $klic);
    if (empty($b['existuje'])) $b['doplni_klub'] = 0;
    return $b;
}

const CLEN_MAX_OSOB = 4;          // „až 4 další osoby“ (přihláška pro pět osob)
const CLEN_VEK_ZASTUPCE = 15;     // u osob mladších 15 let údaje a souhlas zákonného zástupce

/* ==================================================================
   POMŮCKY STRÁNKY
   ================================================================== */

/**
 * Položky prvního seznamu v textu bloku z editoru (vyčištěné html_ocistit):
 * [['hlava' => HTML tučného začátku, 'hlava_text' => prostý text, 'text' => HTML zbytku, 'prosty' => celý text], …]
 */
function clen_polozky(?string $html): array {
    $html = html_ocistit((string)$html);
    if ($html === '' || !preg_match('~<(ul|ol)>~', $html)) return [];
    $doc = new DOMDocument('1.0', 'UTF-8');
    $stary = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body><div id="k">' . $html . '</div></body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($stary);
    $obal = $doc->getElementById('k');
    $seznam = null;
    if ($obal) foreach ($obal->childNodes as $u) {
        if ($u instanceof DOMElement && in_array($u->nodeName, ['ul', 'ol'], true)) { $seznam = $u; break; }
    }
    if (!$seznam) return [];
    $v = [];
    foreach ($seznam->childNodes as $li) {
        if (!$li instanceof DOMElement || $li->nodeName !== 'li') continue;
        $hlava = ''; $hlavaText = ''; $text = ''; $zacatek = true;
        foreach ($li->childNodes as $x) {
            if ($zacatek && $x->nodeType === XML_TEXT_NODE && trim($x->nodeValue, " \t\n\u{00A0}") === '') continue;
            if ($zacatek && $x instanceof DOMElement && $x->nodeName === 'strong') {
                foreach ($x->childNodes as $y) $hlava .= $doc->saveHTML($y);
                $hlavaText = trim(str_replace("\u{00A0}", ' ', $x->textContent));
                $zacatek = false;
                continue;
            }
            $zacatek = false;
            $text .= $doc->saveHTML($x);
        }
        $text = (string)preg_replace('~^(\s|&nbsp;|\x{00A0})*[–—-]?(\s|&nbsp;|\x{00A0})*~u', '', trim($text));
        $v[] = ['hlava' => trim($hlava), 'hlava_text' => $hlavaText, 'text' => trim($text),
                'prosty' => trim(str_replace("\u{00A0}", ' ', $li->textContent))];
    }
    return $v;
}

/** První písmeno textu (i HTML z html_ocistit, kde text nezačíná značkou) velké. */
function clen_velke(string $s): string {
    return (string)preg_replace_callback('/^(\s*)(\p{Ll})/u', fn($m) => $m[1] . mb_strtoupper($m[2], 'UTF-8'), $s, 1);
}

/** „21 000 Kč“ → 21000; bez čísla null. */
function clen_kc(string $s): ?int {
    $c = preg_replace('/\D+/', '', (string)preg_replace('/\/.*$/u', '', $s));
    return $c === '' ? null : (int)$c;
}

/** Malé písmeno bez nezlomitelných mezer – pro rozpoznání řádků ceníku. */
function clen_norm(string $s): string {
    return mb_strtolower(trim((string)preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $s))), 'UTF-8');
}

/**
 * Druh řádku ceníku členství podle názvu:
 *   ['druh' => 'firemni' | 'nehrajici' | 'rodinne' | 'hrajici' | '', 'klic' => 'dospely|do30|senior|mladez' / 'h-n-d']
 * Řádky, které členství nejsou (hosté členů), vrátí druh ''.
 */
function clen_klasifikuj(string $nazev): array {
    $n = clen_norm($nazev);
    if ($n === '' || str_starts_with($n, 'host')) return ['druh' => '', 'klic' => ''];
    if (str_contains($n, 'firem')) return ['druh' => 'firemni', 'klic' => 'firemni'];
    if (str_starts_with($n, 'nehrající')) return ['druh' => 'nehrajici', 'klic' => 'nehrajici'];
    $h = preg_match('/(\d)\s*dospěl\S*\s+hrající/u', $n, $m) ? (int)$m[1] : 0;
    $nh = preg_match('/(\d)\s*dospěl\S*\s+nehrající/u', $n, $m) ? (int)$m[1] : 0;
    $d = preg_match('/(\d)\s*(dítě|děti|dětí)/u', $n, $m) ? (int)$m[1] : 0;
    if (str_contains($n, '+') || $h >= 2 || $nh > 0 || ($h > 0 && $d > 0)) {
        return ($h + $nh + $d) > 0 ? ['druh' => 'rodinne', 'klic' => $h . '-' . $nh . '-' . $d] : ['druh' => '', 'klic' => ''];
    }
    if (str_contains($n, 'do 30')) return ['druh' => 'hrajici', 'klic' => 'do30'];
    if (str_contains($n, 'senior')) return ['druh' => 'hrajici', 'klic' => 'senior'];
    if (str_contains($n, 'mládež') || str_contains($n, 'student')) return ['druh' => 'hrajici', 'klic' => 'mladez'];
    if ($h === 1 || preg_match('/^(1\s*)?dospěl\S*\s+hrající/u', $n)) return ['druh' => 'hrajici', 'klic' => 'dospely'];
    return ['druh' => '', 'klic' => ''];
}

/** Krátký název druhu členství („Rodinné členství“) – pro Členský list. */
function clen_druh_nazev(string $druh): string {
    return ['hrajici' => 'Hrající členství', 'rodinne' => 'Rodinné členství', 'nehrajici' => 'Nehrající členství',
            'firemni' => 'Firemní členství'][$druh] ?? 'Členství';
}

/**
 * Ceník členství z databáze → model pro stránku a pro konfigurátor (app.js, CLTK.clenstvi):
 *   rok, hrajici {dospely|do30|senior|mladez: {cena, typ, id}}, rodinne {"h-n-d": {cena, typ, id}},
 *   nehrajici/firemni {cena, typ, podminka, id} | null, typy (řádky pro výběr v přihlášce), lze_konfigurovat.
 */
function clen_model(?array $cenik): array {
    $m = ['rok' => (int)date('Y'), 'hrajici' => [], 'rodinne' => [], 'nehrajici' => null, 'firemni' => null,
          'typy' => [], 'skupiny' => [], 'lze_konfigurovat' => false];
    if (!$cenik) return $m;
    $l = $cenik['list'];
    if (preg_match('/(19|20)\d{2}/', (string)$l['obdobi'] . ' ' . (string)$l['nazev'], $r)) $m['rok'] = (int)$r[0];
    foreach ($cenik['sekce'] as $s) {
        $skupina = [];
        foreach ($s['radky'] as $r) {
            $nazev = trim((string)$r['nazev']);
            $k = clen_klasifikuj($nazev);
            if ($k['druh'] === '' || $nazev === '') continue;
            $cena = clen_kc((string)$r['cena']);
            $polozka = ['cena' => $cena, 'typ' => $nazev, 'id' => (int)$r['id']];
            if ($k['druh'] === 'hrajici' && $cena !== null && !isset($m['hrajici'][$k['klic']])) $m['hrajici'][$k['klic']] = $polozka;
            if ($k['druh'] === 'rodinne' && $cena !== null && !isset($m['rodinne'][$k['klic']])) $m['rodinne'][$k['klic']] = $polozka;
            if (in_array($k['druh'], ['nehrajici', 'firemni'], true) && $cena !== null && !$m[$k['druh']]) {
                $m[$k['druh']] = $polozka + ['podminka' => trim((string)$r['poznamka'])];
            }
            $osob = 1;
            if ($k['druh'] === 'rodinne') $osob = array_sum(array_map('intval', explode('-', $k['klic'])));
            $typ = ['id' => (int)$r['id'], 'nazev' => $nazev, 'cena' => $cena, 'cena_text' => trim((string)$r['cena']),
                    'poznamka' => trim((string)$r['poznamka']), 'druh' => $k['druh'], 'osob' => max(1, $osob)];
            $m['typy']['r' . (int)$r['id']] = $typ;
            $skupina[] = $typ;
        }
        if ($skupina) $m['skupiny'][] = ['nazev' => trim((string)$s['nazev']), 'typy' => $skupina];
    }
    // konfigurátor jen s úplným základem ceníku (jinak by app.js počítal se starými výchozími cenami)
    $m['lze_konfigurovat'] = isset($m['hrajici']['dospely'], $m['hrajici']['mladez']);
    return $m;
}

/** Data pro <script id="clenstvi-ceny"> (tvar CLTK.clenstvi v app.js + id řádku ceníku). */
function clen_ceny_js(array $m): array {
    $v = ['rok' => $m['rok'], 'hrajici' => $m['hrajici'], 'rodinne' => (object)$m['rodinne']];
    if ($m['nehrajici']) $v['nehrajici'] = $m['nehrajici'];
    if ($m['firemni']) $v['firemni'] = $m['firemni'];
    return $v;
}

/** Totéž jako CLTK.clenstvi.spocitej() v app.js – pro výchozí stav a pro odeslání bez JavaScriptu. */
function clen_spocitej(array $m, array $v): array {
    if (($v['druh'] ?? '') === 'firemni') {
        if (!$m['firemni']) return ['stav' => 'nezname', 'zprava' => 'Tuto kombinaci ceník neuvádí. Ozveme se vám s nabídkou na míru.'];
        $f = $m['firemni'];
        return ['stav' => 'ok', 'cena' => $f['cena'], 'typ' => clen_druh_nazev('firemni') . ($f['podminka'] !== '' ? ' – ' . $f['podminka'] : ''), 'druh' => 'firemni', 'id' => $f['id']];
    }
    $h = max(0, min(2, (int)($v['h'] ?? 1)));
    $n = max(0, min(1, (int)($v['n'] ?? 0)));
    $d = max(0, min(2, (int)($v['d'] ?? 0)));
    $zv = (string)($v['zv'] ?? '');
    if ($h + $n + $d === 0) return ['stav' => 'prazdne', 'zprava' => 'Zvolte alespoň jednu osobu.'];
    if ($h === 0 && $n > 0) return ['stav' => 'neplatne', 'zprava' => 'Nehrající členství lze sjednat jen v návaznosti na hrající členství.'];
    if ($h === 1 && $n === 0 && $d === 0 && isset($m['hrajici']['dospely'])) {
        $j = $m['hrajici'][$zv] ?? $m['hrajici']['dospely'];
        return ['stav' => 'ok', 'cena' => $j['cena'], 'typ' => $j['typ'], 'druh' => 'hrajici', 'id' => $j['id']];
    }
    if ($h === 0 && $n === 0 && $d === 1 && isset($m['hrajici']['mladez'])) {
        $j = $m['hrajici']['mladez'];
        return ['stav' => 'ok', 'cena' => $j['cena'], 'typ' => $j['typ'], 'druh' => 'hrajici', 'id' => $j['id']];
    }
    $r = $m['rodinne'][$h . '-' . $n . '-' . $d] ?? null;
    if ($r) return ['stav' => 'ok', 'cena' => $r['cena'], 'typ' => $r['typ'], 'druh' => 'rodinne', 'id' => $r['id']];
    return ['stav' => 'nezname', 'zprava' => 'Tuto kombinaci ceník neuvádí. Ozveme se vám s nabídkou na míru.'];
}

/** Věk v celých letech k datu $k (RRRR-MM-DD). */
function clen_vek(string $narozeni, string $k): int {
    [$r, $m, $d] = array_map('intval', explode('-', $narozeni));
    [$kr, $km, $kd] = array_map('intval', explode('-', $k));
    return $kr - $r - (($km < $m || ($km === $m && $kd < $d)) ? 1 : 0);
}

/** Podepsaný odkaz na poděkování (id přihlášky + platnost 1 hodina), bez osobních údajů v adrese. */
function clen_ref(int $id): string {
    $plati = time() + 3600;
    return $id . '-' . $plati . '-' . substr(podpis('clenstvi-dekujeme|' . $id . '|' . $plati), 0, 20);
}

/** Ověří odkaz z clen_ref(); vrací id přihlášky nebo 0. */
function clen_ref_over(string $ref): int {
    if (!preg_match('/^(\d{1,9})-(\d{10})-([a-f0-9]{20})$/', $ref, $m)) return 0;
    if ((int)$m[2] < time()) return 0;
    return hash_equals(substr(podpis('clenstvi-dekujeme|' . $m[1] . '|' . $m[2]), 0, 20), $m[3]) ? (int)$m[1] : 0;
}

/** Hodnota z POST (i vnořená: clen_post(['osoby', 1, 'jmeno'])) jako oříznutý řetězec. */
function clen_post(array|string $cesta, int $max = 160): string {
    $v = $_POST;
    foreach ((array)$cesta as $k) {
        if (!is_array($v) || !array_key_exists($k, $v)) return '';
        $v = $v[$k];
    }
    if (!is_scalar($v)) return '';
    $v = trim(str_replace("\r\n", "\n", (string)$v));
    return mb_substr($v, 0, $max);
}

/* ---------- vykreslení polí formuláře (vše escapované) ---------- */

/** id prvku z klíče chyby: 'osoby.2.jmeno' → 'p-o2-jmeno' */
function clen_id(string $klic): string {
    return 'p-' . str_replace(['osoby.', '.'], ['o', '-'], $klic);
}

/**
 * Jedno pole: clen_pole('email', 'E-mail', ['typ' => 'email', 'povinne' => true, …])
 * Volby: typ (text|email|tel|date|select|textarea), name, povinne (bool – atribut required),
 *        znacka (bool – hvězdička bez required), hodnota, chyba, napoveda, moznosti (select:
 *        [['skupina', [[hodnota, popisek, attr[]], …]] | [hodnota, popisek, attr[]], …]),
 *        autocomplete, attr (další atributy), trida (třída obalu)
 */
function clen_pole(string $klic, string $popisek, array $o = []): string {
    $id = clen_id($klic);
    $typ = (string)($o['typ'] ?? 'text');
    $name = (string)($o['name'] ?? $klic);
    $hod = (string)($o['hodnota'] ?? '');
    $chyba = (string)($o['chyba'] ?? '');
    $napoveda = (string)($o['napoveda'] ?? '');
    $povinne = !empty($o['povinne']);
    $znacka = $povinne || !empty($o['znacka']);
    $popisy = [];
    if ($napoveda !== '') $popisy[] = $id . '-napoveda';
    if ($chyba !== '') $popisy[] = $id . '-chyba';
    $attr = ' id="' . e($id) . '" name="' . e($name) . '"' . ($povinne ? ' required' : '')
          . ($chyba !== '' ? ' aria-invalid="true"' : '') . ($popisy ? ' aria-describedby="' . e(implode(' ', $popisy)) . '"' : '');
    if (!empty($o['autocomplete'])) $attr .= ' autocomplete="' . e((string)$o['autocomplete']) . '"';
    foreach ((array)($o['attr'] ?? []) as $k => $v) $attr .= ' ' . e((string)$k) . ($v === true ? '' : '="' . e((string)$v) . '"');

    $h = '<div class="pole' . ($chyba !== '' ? ' pole--chyba' : '') . (!empty($o['trida']) ? ' ' . e((string)$o['trida']) : '') . '">';
    $h .= '<label for="' . e($id) . '">' . $popisek . ($znacka ? '<span class="povinne" aria-hidden="true">*</span>' : '') . '</label>';
    if ($typ === 'select') {
        $h .= '<select' . $attr . '>';
        foreach ((array)($o['moznosti'] ?? []) as $mo) {
            if (isset($mo[1]) && is_array($mo[1])) {
                $h .= '<optgroup label="' . e((string)$mo[0]) . '">';
                foreach ($mo[1] as $x) $h .= clen_option($x, $hod);
                $h .= '</optgroup>';
            } else {
                $h .= clen_option($mo, $hod);
            }
        }
        $h .= '</select>';
    } elseif ($typ === 'textarea') {
        $h .= '<textarea' . $attr . ' rows="4">' . e($hod) . '</textarea>';
    } else {
        $h .= '<input type="' . e($typ) . '"' . $attr . ' value="' . e($hod) . '">';
    }
    if ($napoveda !== '') $h .= '<p class="pole__napoveda" id="' . e($id) . '-napoveda">' . $napoveda . '</p>';
    if ($chyba !== '') $h .= '<p class="pole__chyba" id="' . e($id) . '-chyba">' . e($chyba) . '</p>';
    return $h . '</div>';
}

function clen_option(array $x, string $vybrano): string {
    $a = '';
    foreach ((array)($x[2] ?? []) as $k => $v) $a .= ' ' . e((string)$k) . '="' . e((string)$v) . '"';
    return '<option value="' . e((string)$x[0]) . '"' . ((string)$x[0] === $vybrano ? ' selected' : '') . $a . '>' . e((string)$x[1]) . '</option>';
}

/** Pohlaví – dvě volby (radio) ve skupině. */
function clen_pohlavi(string $klic, string $name, string $hod, string $chyba, bool $povinne): string {
    $id = clen_id($klic);
    $h = '<fieldset class="pole pole--volby' . ($chyba !== '' ? ' pole--chyba' : '') . '"' . ($chyba !== '' ? ' aria-describedby="' . e($id) . '-chyba"' : '') . '>';
    $h .= '<legend class="pole__label">Pohlaví<span class="povinne" aria-hidden="true">*</span></legend><div class="volby-radek">';
    foreach (['žena' => 'žena', 'muž' => 'muž'] as $v => $t) {
        $h .= '<label class="volba" for="' . e($id . '-' . slugify($v)) . '"><input type="radio" id="' . e($id . '-' . slugify($v)) . '" name="' . e($name) . '" value="' . e($v) . '"'
            . ($hod === $v ? ' checked' : '') . ($povinne ? ' required' : '') . ($chyba !== '' ? ' aria-invalid="true"' : '') . '><span>' . e($t) . '</span></label>';
    }
    $h .= '</div>' . ($chyba !== '' ? '<p class="pole__chyba" id="' . e($id) . '-chyba">' . e($chyba) . '</p>' : '') . '</fieldset>';
    return $h;
}

/** Zaškrtávátko (souhlasy). $text je už escapované HTML. */
function clen_zaskrtavatko(string $klic, string $text, bool $zaskrtnuto, string $chyba, array $attr = []): string {
    $id = clen_id($klic);
    $a = '';
    foreach ($attr as $k => $v) $a .= ' ' . e((string)$k) . ($v === true ? '' : '="' . e((string)$v) . '"');
    return '<div class="pole' . ($chyba !== '' ? ' pole--chyba' : '') . '"><label class="volba" for="' . e($id) . '">'
         . '<input type="checkbox" id="' . e($id) . '" name="' . e($klic) . '" value="1"' . ($zaskrtnuto ? ' checked' : '') . $a
         . ($chyba !== '' ? ' aria-invalid="true" aria-describedby="' . e($id) . '-chyba"' : '') . '>'
         . '<span>' . $text . '<span class="povinne" aria-hidden="true">*</span></span></label>'
         . ($chyba !== '' ? '<p class="pole__chyba" id="' . e($id) . '-chyba">' . e($chyba) . '</p>' : '') . '</div>';
}

/* ==================================================================
   DATA
   ================================================================== */
$uvod       = klub_blok('clenstvi', 'uvod');
$bVCene     = klub_blok('clenstvi', 'v-cene');
$bZima      = klub_blok('clenstvi', 'zvyhodneni');
$bKonf      = klub_blok('clenstvi', 'konfigurator');
$bList      = klub_blok('clenstvi', 'clensky-list');
$bPostup    = klub_blok('clenstvi', 'postup');
$bDruhy     = klub_blok('clenstvi', 'druhy');
$bPrihlaska = klub_blok('clenstvi', 'prihlaska');
$bDekujeme  = klub_blok('clenstvi', 'dekujeme');
$bSouhlas   = klub_blok('formulare', 'souhlas');

$cenik = cenik('clenstvi');
$model = clen_model($cenik);
$vCene = clen_polozky((string)$bVCene['text']);
$zima  = clen_polozky((string)$bZima['text']);
$postup = clen_polozky((string)$bPostup['text']);
$druhy = clen_polozky((string)$bDruhy['text']);
$dokumenty = array_merge(dokumenty('clenstvi'), dokumenty('klub'));
$stanovy = null;
foreach (dokumenty('klub') as $d) { if (str_contains(clen_norm((string)$d['nazev']), 'stanov')) { $stanovy = $d; break; } }
$gdprDok = dokumenty('clenstvi')[0] ?? null;
$dnes = dnes();

/* Hlava stránky: bez vlastní fotky si vezme fotku vstupu z bloku členství na úvodu */
if (trim((string)$uvod['foto']) === '') {
    $bIndex = klub_blok('index', 'clenstvi');
    if (trim((string)$bIndex['foto']) !== '') { $uvod['foto'] = $bIndex['foto']; $uvod['foto_popisek'] = $bIndex['foto_popisek']; }
}

/* Konfigurátor: výchozí volba, nebo co přišlo z konfigurátoru odeslaného bez JavaScriptu (GET) */
$konfVstup = [
    'druh' => ($_GET['druh'] ?? '') === 'firemni' && $model['firemni'] ? 'firemni' : 'osobni',
    'h' => isset($_GET['h']) && is_scalar($_GET['h']) ? max(0, min(2, (int)$_GET['h'])) : 1,
    'n' => isset($_GET['n']) && is_scalar($_GET['n']) ? max(0, min(1, (int)$_GET['n'])) : 0,
    'd' => isset($_GET['d']) && is_scalar($_GET['d']) ? max(0, min(2, (int)$_GET['d'])) : 0,
    'zv' => isset($_GET['zv']) && is_string($_GET['zv']) && isset($model['hrajici'][$_GET['zv']]) && $_GET['zv'] !== 'dospely' ? $_GET['zv'] : '',
];
if (!($konfVstup['h'] === 1 && $konfVstup['n'] === 0 && $konfVstup['d'] === 0)) $konfVstup['zv'] = '';
$konfVysledek = clen_spocitej($model, $konfVstup);
$zKonfiguratoru = isset($_GET['h']) || isset($_GET['druh']);

/* ==================================================================
   ODESLANÁ PŘIHLÁŠKA (před jakýmkoli výstupem)
   ================================================================== */
$hod = [];        // hodnoty pro znovuvyplnění
$chyby = [];      // klíč pole → hláška
$chyba = '';      // obecná hláška nad formulářem
$tokenChyba = false;
/* Rodné číslo se sbírá, jen když ho jde uložit zašifrované (SIFROVACI_KLIC v cltk-config.php).
   Databáze je na serveru sdílená s jiným webem – v čitelné podobě tam nepatří. */
$rcSbirat = citlive_lze_sifrovat();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $chyba = verejny_formular_over('clenstvi');
    $tokenChyba = $chyba !== '';

    $p = static fn(string $k, int $max = 160): string => clen_post($k, $max);
    foreach (['typ', 'jmeno', 'prijmeni', 'pohlavi', 'datum_narozeni', 'rodne_cislo', 'obcanstvi', 'firma', 'ico',
              'ulice', 'psc', 'mesto', 'stat', 'telefon', 'email', 'profese', 'zdroj',
              'zastupce_jmeno', 'zastupce_vztah', 'zastupce_telefon', 'zastupce_email'] as $k) {
        $hod[$k] = $p($k);
    }
    $hod['poznamka'] = $p('poznamka', 2000);
    if (!$rcSbirat) $hod['rodne_cislo'] = '';
    foreach (['souhlas_gdpr', 'souhlas_podminky', 'souhlas_schvaleni', 'souhlas_zastupce'] as $k) $hod[$k] = vstup_bool($k);

    /* typ členství z ceníku (cenu bere server z databáze, ne z formuláře) */
    $typ = $model['typy'][$hod['typ']] ?? null;
    if (!$typ && $hod['typ'] !== 'jine') $chyby['typ'] = 'Vyberte prosím typ členství.';
    $firemni = $typ && $typ['druh'] === 'firemni';

    /* hlavní žadatel */
    $osobaPole = static function (array $o, string $pre, bool $jeFirma = false) use (&$chyby, $dnes): ?string {
        if ($o['jmeno'] === '') $chyby[$pre . 'jmeno'] = 'Vyplňte prosím jméno.';
        if ($o['prijmeni'] === '') $chyby[$pre . 'prijmeni'] = 'Vyplňte prosím příjmení.';
        if (!in_array($o['pohlavi'], ['žena', 'muž'], true)) $chyby[$pre . 'pohlavi'] = 'Vyberte prosím pohlaví.';
        $dat = normalizuj_datum($o['datum_narozeni']);
        if ($dat === null) $chyby[$pre . 'datum_narozeni'] = 'Vyplňte prosím datum narození.';
        elseif ($dat === false || $dat > $dnes || $dat < '1900-01-01') $chyby[$pre . 'datum_narozeni'] = 'Datum narození nedává smysl – zadejte ho prosím jako 5. 9. 1990.';
        if ($o['rodne_cislo'] !== '' && !preg_match('~^\d{6}\s*/?\s*\d{3,4}$~', $o['rodne_cislo'])) $chyby[$pre . 'rodne_cislo'] = 'Rodné číslo má tvar 000000/0000 – nebo pole nechte prázdné.';
        if ($o['obcanstvi'] === '') $chyby[$pre . 'obcanstvi'] = 'Vyplňte prosím státní občanství.';
        return is_string($dat) ? $dat : null;
    };
    $hlavni = ['jmeno' => mb_substr($hod['jmeno'], 0, 80), 'prijmeni' => mb_substr($hod['prijmeni'], 0, 80), 'pohlavi' => $hod['pohlavi'],
               'datum_narozeni' => $hod['datum_narozeni'], 'rodne_cislo' => mb_substr($hod['rodne_cislo'], 0, 16), 'obcanstvi' => mb_substr($hod['obcanstvi'], 0, 60)];
    $narozeniHlavni = $osobaPole($hlavni, '');
    $vekNejmladsi = $narozeniHlavni ? clen_vek($narozeniHlavni, $dnes) : 99;

    /* další osoby (vyplněné jen částečně = chyba, prázdné se přeskočí) */
    $osoby = [];
    $hod['osoby'] = [];
    for ($i = 1; $i <= CLEN_MAX_OSOB; $i++) {
        $o = [];
        foreach (['jmeno' => 80, 'prijmeni' => 80, 'pohlavi' => 10, 'datum_narozeni' => 20, 'rodne_cislo' => 16, 'obcanstvi' => 60] as $k => $max) {
            $o[$k] = clen_post(['osoby', (string)$i, $k], $max);
        }
        if (!$rcSbirat) $o['rodne_cislo'] = '';
        $hod['osoby'][$i] = $o;
        if (implode('', $o) === '') continue;
        $dat = $osobaPole($o, 'osoby.' . $i . '.');
        if ($dat) { $o['datum_narozeni'] = $dat; $vekNejmladsi = min($vekNejmladsi, clen_vek($dat, $dnes)); }
        $osoby[] = $o;
    }

    /* firma u firemního členství */
    if ($firemni) {
        if ($hod['firma'] === '') $chyby['firma'] = 'Vyplňte prosím název firmy.';
        if (!preg_match('/^\d{8}$/', str_replace(' ', '', $hod['ico']))) $chyby['ico'] = 'IČO má osm číslic.';
    }

    /* adresa a kontakt */
    if ($hod['ulice'] === '') $chyby['ulice'] = 'Vyplňte prosím ulici a číslo domu.';
    if ($hod['mesto'] === '') $chyby['mesto'] = 'Vyplňte prosím obec.';
    if (!preg_match('/^[0-9A-Za-z][0-9A-Za-z \-]{2,9}$/', $hod['psc'])) $chyby['psc'] = 'Vyplňte prosím PSČ.';
    if (!preg_match('~^\+?[0-9][0-9 ()/\-]{7,}$~', $hod['telefon'])) $chyby['telefon'] = 'Vyplňte prosím telefon (např. 608 974 974).';
    if (!je_email($hod['email'])) $chyby['email'] = 'Vyplňte prosím platný e-mail.';
    if ($hod['profese'] === '') $chyby['profese'] = 'Vyplňte prosím současnou profesi.';
    if ($hod['zdroj'] === '') $chyby['zdroj'] = 'Napište nám prosím, jak jste se o nás dozvěděli.';

    /* zákonný zástupce, když je některé osobě méně než 15 let */
    $zastupceNutny = $vekNejmladsi < CLEN_VEK_ZASTUPCE;
    if ($zastupceNutny) {
        if ($hod['zastupce_jmeno'] === '') $chyby['zastupce_jmeno'] = 'Vyplňte prosím jméno a příjmení zákonného zástupce.';
        if (!preg_match('~^\+?[0-9][0-9 ()/\-]{7,}$~', $hod['zastupce_telefon'])) $chyby['zastupce_telefon'] = 'Vyplňte prosím telefon zákonného zástupce.';
        if (!je_email($hod['zastupce_email'])) $chyby['zastupce_email'] = 'Vyplňte prosím e-mail zákonného zástupce.';
        if (!$hod['souhlas_zastupce']) $chyby['souhlas_zastupce'] = 'Bez souhlasu zákonného zástupce přihlášku nelze přijmout.';
    }

    /* souhlasy */
    if (!$hod['souhlas_gdpr']) $chyby['souhlas_gdpr'] = 'Bez souhlasu se zpracováním údajů přihlášku nelze vyřídit.';
    if (!$hod['souhlas_podminky']) $chyby['souhlas_podminky'] = 'Potvrďte prosím souhlas s podmínkami členství.';
    if (!$hod['souhlas_schvaleni']) $chyby['souhlas_schvaleni'] = 'Potvrďte prosím, že berete na vědomí schvalovací proces.';

    if ($chyba === '' && !$chyby) {
        $data = [
            'jmeno' => $hlavni['jmeno'], 'prijmeni' => $hlavni['prijmeni'], 'pohlavi' => $hlavni['pohlavi'],
            'datum_narozeni' => $narozeniHlavni, 'rodne_cislo' => citlive_zasifruj($hlavni['rodne_cislo']), 'obcanstvi' => $hlavni['obcanstvi'],
            'typ_clenstvi' => $typ ? $typ['nazev'] : 'Jiná kombinace – cenu potvrdí kancelář',
            'cena' => $typ ? $typ['cena'] : null,
            'pocet_osob' => 1 + count($osoby),
        ];
        if ($firemni) { $data['firma'] = mb_substr($hod['firma'], 0, 160); $data['ico'] = str_replace(' ', '', $hod['ico']); }
        if ($osoby) $data['osoby'] = array_map(static fn(array $o): array => array_merge($o, ['rodne_cislo' => citlive_zasifruj((string)$o['rodne_cislo'])]), $osoby);
        $data['adresa'] = ['ulice' => $hod['ulice'], 'psc' => $hod['psc'], 'mesto' => $hod['mesto'], 'stat' => $hod['stat']];
        $data += ['telefon' => $hod['telefon'], 'email' => $hod['email'], 'profese' => $hod['profese'], 'zdroj' => $hod['zdroj']];
        if ($zastupceNutny) {
            $data['zakonny_zastupce'] = ['jmeno' => $hod['zastupce_jmeno'], 'vztah' => $hod['zastupce_vztah'],
                                         'telefon' => $hod['zastupce_telefon'], 'email' => $hod['zastupce_email'], 'souhlas' => '1'];
        }
        $data += ['souhlas_gdpr' => '1', 'souhlas_podminky' => '1', 'souhlas_schvaleni' => '1'];
        if ($hod['poznamka'] !== '') $data['poznamka'] = $hod['poznamka'];

        $radek = [
            'jmeno' => $hlavni['jmeno'], 'prijmeni' => $hlavni['prijmeni'],
            'email' => mb_substr($hod['email'], 0, 160), 'telefon' => mb_substr($hod['telefon'], 0, 40),
            'typ_clenstvi' => mb_substr((string)$data['typ_clenstvi'], 0, 160), 'cena' => $data['cena'],
            'pocet_osob' => $data['pocet_osob'], 'data' => json_ulozit($data),
            'souhlas_gdpr' => 1, 'souhlas_podminky' => 1, 'stav' => 'nova', 'poznamka_admin' => '',
            'created_at' => ted(), 'updated_at' => ted(),
        ];
        $noveId = 0;
        try {
            $noveId = db_insert('cltk_prihlasky_clenstvi', $radek);
        } catch (Throwable $e) {
            error_log('[clenstvi.php] přihláška se neuložila: ' . $e->getMessage());
            $chyba = 'Přihlášku se teď nepodařilo uložit. Zkuste to prosím za chvíli znovu, nebo zavolejte do kanceláře klubu.';
        }
        if ($chyba === '') {
            verejny_formular_zapis('clenstvi');
            try { upozorni_prihlaska_clenstvi($radek); } catch (Throwable $e) { error_log('[clenstvi.php] e-mail: ' . $e->getMessage()); }
            redirect(url('clenstvi.php?odeslano=1&p=' . clen_ref($noveId)) . '#prihlaska');
        }
    }
    if ($tokenChyba) http_response_code(400);
    elseif ($chyby) http_response_code(422);
}
track_visit();

/* Poděkování po odeslání (GET ?odeslano=1) – Členský list jen s platným podepsaným odkazem */
$odeslano = ($_GET['odeslano'] ?? '') === '1' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST';
$poslana = null;
if ($odeslano) {
    $refId = clen_ref_over(is_string($_GET['p'] ?? null) ? $_GET['p'] : '');
    if ($refId > 0) {
        try { $poslana = row('SELECT jmeno, prijmeni, typ_clenstvi, cena, created_at FROM cltk_prihlasky_clenstvi WHERE id = ?', [$refId]); }
        catch (Throwable $e) { $poslana = null; }
    }
}

/* Výchozí hodnoty formuláře: typ z konfigurátoru (GET bez JavaScriptu) */
if (!isset($hod['typ'])) {
    $hod['typ'] = $zKonfiguratoru ? ($konfVysledek['stav'] === 'ok' ? 'r' . $konfVysledek['id'] : ($konfVysledek['stav'] === 'nezname' ? 'jine' : '')) : '';
}
$hod += ['osoby' => []];
$h = static fn(string $k): string => (string)($hod[$k] ?? '');
$ch = static fn(string $k): string => (string)($chyby[$k] ?? '');
$osobyZKonf = $zKonfiguratoru && $konfVysledek['stav'] === 'ok' && $konfVysledek['druh'] === 'rodinne'
    ? max(0, $konfVstup['h'] + $konfVstup['n'] + $konfVstup['d'] - 1) : 0;

/* Možnosti výběru typu členství (podle ceníku) */
$moznostiTypu = [['', 'Vyberte typ členství…']];
foreach ($model['skupiny'] as $sk) {
    $moz = [];
    foreach ($sk['typy'] as $t) {
        $moz[] = ['r' . $t['id'], $t['nazev'] . ($t['cena'] !== null ? ' – ' . cena_kc($t['cena'], false) : ''),
                  ['data-druh' => $t['druh'], 'data-osob' => (string)$t['osob']]];
    }
    $moznostiTypu[] = [$sk['nazev'] !== '' ? $sk['nazev'] : 'Členství', $moz];
}
$moznostiTypu[] = ['jine', 'Jiná kombinace – cenu potvrdí kancelář', ['data-druh' => 'jine', 'data-osob' => '0']];

/* Kotvy pod hlavou stránky */
$kotvy = [];
if ($bVCene['existuje']) $kotvy[] = [trim(html_text((string)$bVCene['nadpis'])) ?: 'V ceně členství', '#v-cene'];
if ($cenik) $kotvy[] = ['Ceník ' . $model['rok'], '#cenik'];
if ($postup) $kotvy[] = [trim(html_text((string)$bPostup['nadpis'])) ?: 'Postup přijetí', '#postup'];
$kotvy[] = ['Přihláška', '#prihlaska'];

$kancelar = ['jmeno' => setting('kancelar_jmeno'), 'popis' => setting('kancelar_popis'),
             'telefon' => setting('kancelar_telefon'), 'email' => setting('kancelar_email')];

$cislo = 0;
$sablona = [
    'titulek' => 'Členství',
    'popis'   => html_text((string)$uvod['perex']),
    'css'     => ['stranky-klub.css'],
    'js'      => ['clenstvi.js'],
    'obrazek' => (string)$uvod['foto'],
    'trida'   => 'stranka-clenstvi',
    'noindex' => $odeslano || ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST',
];
require __DIR__ . '/inc/sablona/hlavicka.php';

/* ---------- Členský list (náhled i tisk) ---------- */
$clenskyList = static function (string $jmeno, string $druh, string $typ, string $cena, bool $nahled, string $id) use ($model): string {
    return '<article class="clensky-list" id="' . e($id) . '" aria-label="' . ($nahled ? 'Náhled Členského listu' : 'Členský list') . '">'
        . '<div class="clensky-list__pecet" aria-hidden="true"><img src="' . e(logo_url('svg')) . '" alt="" width="120" height="138" loading="lazy" decoding="async"></div>'
        . '<p class="clensky-list__druh">Členský list</p>'
        . '<p class="clensky-list__klub">' . typo(setting('klub_nazev', 'I. Český Lawn-Tennis Klub Praha')) . '</p>'
        . '<p class="clensky-list__zalozen">Založen ' . e(setting('zalozeno', '1893')) . '</p>'
        . '<div class="ozdoba" aria-hidden="true"><span></span></div>'
        . '<p class="clensky-list__jmeno"' . ($nahled ? ' data-list-jmeno' : '') . '>' . typo($jmeno) . '</p>'
        . '<p class="clensky-list__typ"><span data-list-druh>' . typo($druh) . '</span> · <span data-list-typ>' . typo($typ) . '</span></p>'
        . '<dl class="clensky-list__udaje"><div><dt>Rok přijetí</dt><dd>' . e((string)$model['rok']) . '</dd></div>'
        . '<div><dt>Příspěvek</dt><dd data-list-cena>' . $cena . '</dd></div></dl>'
        . '<p class="clensky-list__podpis">Po rozhodnutí Výkonného výboru</p>'
        . '</article>';
};
?>

<?= hlava_stranky($uvod, [
    'drobky' => [['Klub', 'klub.php'], ['Členství']],
    'nadpis' => 'Členství',
    'navic'  => '<nav class="kotvy" aria-label="Části stránky">' . pilulky_html($kotvy) . '</nav>',
]) ?>

<?php if ($vCene || $zima): ?>
  <!-- I · V ceně členství (V4 sekce 6) a výhody členů v zimě -->
  <section class="sekce sekce--linka clen-vcene" id="v-cene" aria-labelledby="v-cene-nadpis">
    <div class="wrap mrizka">
      <div class="sl-5">
        <?= hlava_sekce($bVCene, ['cislo' => ++$cislo, 'id' => 'v-cene-nadpis', 'stitek' => 'Výhody členů', 'nadpis' => 'V ceně členství']) ?>
        <?php if ($zima): ?>
        <div class="karta karta--papir2 clen-zima">
          <h3 class="h4 clen-zima__nadpis"><?= trim((string)$bZima['nadpis']) !== '' ? html_inline((string)$bZima['nadpis']) : 'Výhody členů v zimě' ?></h3>
          <ul class="radky">
            <?php foreach ($zima as $z): ?><li class="radek"><span class="radek__nazev"><?= $z['hlava'] !== '' ? '<b>' . $z['hlava'] . '</b> ' . $z['text'] : clen_velke($z['text']) ?></span></li><?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
      </div>
      <?php if ($vCene): ?>
      <div class="sl-6 od-7">
        <ol class="rimsky clen-vcene__seznam">
          <?php foreach ($vCene as $v):
            // „venkovní kurty v letní sezóně zdarma – 13 antukových…“ → titulek a doplněk
            $casti = preg_split('/\s+[–—]\s+/u', $v['prosty'], 2);
            $titulek = $v['hlava_text'] !== '' ? $v['hlava_text'] : (string)$casti[0];
            $dopl = $v['hlava_text'] !== '' ? $v['text'] : (isset($casti[1]) ? typo($casti[1]) : '');
            if (preg_match('/^([^:]{3,40}):\s+(.+)$/u', $titulek, $x) && $dopl === '') { $titulek = $x[1]; $dopl = typo($x[2]); } ?>
          <li><b><?= typo(mb_strtoupper(mb_substr($titulek, 0, 1)) . mb_substr($titulek, 1)) ?></b><?= $dopl ?></li>
          <?php endforeach; ?>
        </ol>
      </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php if ($cenik): ?>
  <!-- II · Konfigurátor ceny a Členský list (V4 sekce 6 a 6b), celý ceník -->
  <section class="sekce sekce--papir2 clen-cenik" id="cenik" aria-labelledby="cenik-nadpis">
    <div class="wrap">
      <?= hlava_sekce($bKonf, ['cislo' => ++$cislo, 'id' => 'cenik-nadpis', 'radek' => true, 'stitek' => 'Ceník ' . $model['rok'], 'nadpis' => 'Ceník členství']) ?>

      <?php if ($model['lze_konfigurovat']):
        $konfOk = $konfVysledek['stav'] === 'ok';
        $jenJeden = $konfVstup['h'] === 1 && $konfVstup['n'] === 0 && $konfVstup['d'] === 0; ?>
      <?= json_skript('clenstvi-ceny', clen_ceny_js($model)) ?>
      <div class="clenstvi-sestava">
        <form class="konfigurator" data-konfigurator data-list="list-nahled" action="<?= e(url('clenstvi.php')) ?>#prihlaska" method="get" aria-labelledby="konf-nadpis" novalidate>
          <div class="konfigurator__hlava">
            <h3 class="h3" id="konf-nadpis">Kdo bude <em>hrát</em>?</h3>
            <?php if ($model['firemni']): ?>
            <fieldset class="konfigurator__druh">
              <legend class="vh">Druh členství</legend>
              <div class="segment">
                <label><input type="radio" name="druh" value="osobni"<?= $konfVstup['druh'] !== 'firemni' ? ' checked' : '' ?>><span>Osoby a&nbsp;rodina</span></label>
                <label><input type="radio" name="druh" value="firemni"<?= $konfVstup['druh'] === 'firemni' ? ' checked' : '' ?>><span>Firemní</span></label>
              </div>
            </fieldset>
            <?php endif; ?>
          </div>
          <div class="konfigurator__telo">
            <div class="konfigurator__volby">
              <fieldset data-konf-osoby<?= $konfVstup['druh'] === 'firemni' ? ' disabled' : '' ?>>
                <legend class="vh">Složení domácnosti</legend>
                <?php foreach ([['h', 'Hrající dospělí', '0–2 osoby', 2, 'hrajícího dospělého'],
                                ['n', 'Nehrající dospělý', 'jen spolu s&nbsp;hrajícím členstvím', 1, 'nehrajícího dospělého'],
                                ['d', 'Děti', '4–18 let nebo studující', 2, 'dítě']] as [$k, $popis, $mala, $max, $koho]): ?>
                <div class="krokovac" data-krokovac>
                  <label class="krokovac__popis" for="k-<?= $k ?>"><?= $popis ?><small><?= $mala ?></small></label>
                  <div class="krokovac__ovladani">
                    <button type="button" class="krokovac__tl" data-krok="-1" aria-label="Ubrat <?= e($koho) ?>"></button>
                    <input id="k-<?= $k ?>" name="<?= $k ?>" type="number" inputmode="numeric" min="0" max="<?= $max ?>" value="<?= (int)$konfVstup[$k] ?>">
                    <button type="button" class="krokovac__tl" data-krok="1" aria-label="Přidat <?= e($koho) ?>"></button>
                  </div>
                </div>
                <?php endforeach; ?>
              </fieldset>
              <?php $zvVolby = array_filter(['do30' => 'Do 30&nbsp;let', 'senior' => 'Senior', 'mladez' => 'Mládež, student'], fn($k) => isset($model['hrajici'][$k]), ARRAY_FILTER_USE_KEY); ?>
              <?php if ($zvVolby): ?>
              <fieldset class="zvyhodneni" data-konf-zvyhodneni<?= !$jenJeden || $konfVstup['druh'] === 'firemni' ? ' disabled' : '' ?>>
                <legend class="pole__label">Zvýhodnění <span class="tlumene">– jen pro jednoho hrajícího</span></legend>
                <div class="zvyhodneni__volby">
                  <label class="cip"><input type="radio" name="zv" value=""<?= $konfVstup['zv'] === '' ? ' checked' : '' ?>><span>Bez zvýhodnění</span></label>
                  <?php foreach ($zvVolby as $k => $t): ?>
                  <label class="cip" title="<?= e($model['hrajici'][$k]['typ']) ?>"><input type="radio" name="zv" value="<?= e($k) ?>"<?= $konfVstup['zv'] === $k ? ' checked' : '' ?>><span><?= $t ?></span></label>
                  <?php endforeach; ?>
                </div>
              </fieldset>
              <?php endif; ?>
            </div>
            <div class="konfigurator__vysledek">
              <output class="konfigurator__cena" for="k-h k-n k-d" aria-live="polite">
                <span class="cena--velka" data-konf-cena><?= $konfOk ? cena_kc($konfVysledek['cena']) : ($konfVysledek['stav'] === 'nezname' ? 'Na míru' : '—') ?></span>
                <span class="konfigurator__za" data-konf-za><?= $konfOk ? 'ročně · ceník ' . e((string)$model['rok']) : '' ?></span>
                <span class="konfigurator__popis" data-konf-popis><?= typo($konfOk ? $konfVysledek['typ'] : $konfVysledek['zprava']) ?></span>
                <span class="konfigurator__stav" data-konf-stav><?= $konfVysledek['stav'] === 'nezname' ? 'Stačí odeslat přihlášku, cenu potvrdí kancelář klubu.' : '' ?></span>
              </output>
              <div class="konfigurator__akce">
                <button class="btn" type="submit">Pokračovat<span class="vh"> k&nbsp;přihlášce</span> <?= sipka() ?></button>
                <a class="odkaz odkaz--text" href="#cenik-clenstvi" data-otevrit="cenik-clenstvi">Celý ceník členství</a>
              </div>
            </div>
          </div>
        </form>

        <div class="clenstvi-list">
          <?= $clenskyList('Vaše jméno', $konfOk ? clen_druh_nazev($konfVysledek['druh']) : 'Členství', $konfOk ? $konfVysledek['typ'] : '—', $konfOk ? cena_kc($konfVysledek['cena']) : 'na míru', true, 'list-nahled') ?>
          <?php if (trim((string)$bList['perex']) !== ''): ?><div class="drobne clenstvi-list__pozn"><?= paragraphs((string)$bList['perex']) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php
      $nahore = trim((string)$cenik['list']['poznamka_nahore']);
      if ($nahore !== '' && mb_substr(clen_norm(html_text((string)$uvod['perex'])), 0, 40) === mb_substr(clen_norm($nahore), 0, 40)) $nahore = '';
      $dole = trim((string)$cenik['list']['poznamka_dole']);
      $obsahCeniku = '<div class="cenik-clenstvi__mrizka">';
      foreach ($cenik['sekce'] as $s) {
          if (!$s['radky']) continue;
          $obsahCeniku .= '<div class="cenik-clenstvi__sekce"><h3 class="h5">' . typo((string)$s['nazev']) . '</h3><ul class="radky">';
          foreach ($s['radky'] as $r) {
              $obsahCeniku .= '<li class="radek radek--vodici"><span class="radek__nazev">' . typo((string)$r['nazev']) . '</span><span class="radek__hodnota cena">'
                  . (trim((string)$r['cena']) !== '' ? typo((string)$r['cena']) : doplni_klub()) . '</span>'
                  . (trim((string)$r['poznamka']) !== '' ? '<span class="radek__pozn">' . typo((string)$r['poznamka']) . '</span>' : '') . '</li>';
          }
          $obsahCeniku .= '</ul></div>';
      }
      $obsahCeniku .= '</div>';
      if ($nahore !== '' || $dole !== '') $obsahCeniku .= '<div class="cenik-clenstvi__pozn drobne">' . paragraphs(trim($nahore . "\n\n" . $dole)) . '</div>';
      $pdf = bezpecny_odkaz((string)$cenik['list']['pdf_url']);
      if ($pdf !== '') $obsahCeniku .= '<p class="cenik-clenstvi__pozn">' . tlacitko($pdf, 'Ceník v PDF', 'odkaz') . '</p>';
      ?>
      <?php if ($model['lze_konfigurovat']): ?>
      <details class="rozbal cenik-clenstvi" id="cenik-clenstvi">
        <summary><span><?= typo((string)$cenik['list']['nazev']) ?><?= trim((string)$cenik['list']['podnazev']) !== '' ? ' <span class="tlumene">· ' . typo(mb_strtolower((string)$cenik['list']['podnazev'])) . '</span>' : '' ?></span></summary>
        <div class="rozbal__obsah"><?= $obsahCeniku ?></div>
      </details>
      <?php else: ?>
      <div class="cenik-clenstvi cenik-clenstvi--otevreny" id="cenik-clenstvi"><?= $obsahCeniku ?></div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php if ($postup || $druhy): ?>
  <!-- III · Jak se stát členem (proces přijetí) a druhy členství podle stanov -->
  <section class="sekce clen-postup" id="postup" aria-labelledby="postup-nadpis">
    <div class="wrap">
      <?= hlava_sekce($bPostup, ['cislo' => ++$cislo, 'id' => 'postup-nadpis', 'stitek' => 'Přijetí', 'nadpis' => 'Jak se stát členem']) ?>
      <?php if ($postup): ?>
      <ol class="proces clen-postup__proces" style="--kroku:<?= min(6, count($postup)) ?>">
        <?php foreach ($postup as $k): ?>
        <li><?php if ($k['hlava'] !== ''): ?><b><?= $k['hlava'] ?></b><?php endif; ?><?= $k['text'] !== '' ? clen_velke($k['text']) : ($k['hlava'] === '' ? typo(clen_velke($k['prosty'])) : '') ?></li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>

      <?php if ($druhy): ?>
      <div class="clen-druhy">
        <h3 class="h4 clen-druhy__nadpis"><?= trim((string)$bDruhy['nadpis']) !== '' ? html_inline((string)$bDruhy['nadpis']) : 'Druhy členství' ?></h3>
        <ul class="trojice clen-druhy__seznam">
          <?php foreach ($druhy as $d): ?>
          <li class="clen-druh">
            <?php if ($d['hlava'] !== ''): ?><p class="clen-druh__nazev"><?= $d['hlava'] ?></p><?php endif; ?>
            <p class="clen-druh__text"><?= $d['text'] !== '' ? clen_velke($d['text']) : typo(clen_velke($d['prosty'])) ?></p>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php if ($stanovy): ?><p class="drobne clen-druhy__pramen"><?= tlacitko(dokument_url($stanovy), (string)$stanovy['nazev'], 'odkaz') ?></p><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

  <!-- IV · Přihláška do klubu (čtyři kroky s JavaScriptem, jinak jedna stránka) -->
  <section class="sekce sekce--papir2 clen-prihlaska" id="prihlaska" aria-labelledby="prihlaska-nadpis">
    <div class="wrap">
      <?php if ($odeslano): ?>
        <?php $dek = $bDekujeme['existuje'] ? $bDekujeme : ['stitek' => 'Přihláška odeslána', 'nadpis' => 'Děkujeme, přihláška <em>dorazila</em>.', 'perex' => '', 'doplni_klub' => 0]; ?>
        <div class="clen-dekujeme">
          <div class="clen-dekujeme__text">
            <?= hlava_sekce($dek, ['cislo' => ++$cislo, 'id' => 'prihlaska-nadpis']) ?>
            <div class="hlaska hlaska--ok" role="status" tabindex="-1" id="prihlaska-ok">
              <span class="hlaska__titul">Přihláška je uložená.</span>
              <?php if ($poslana): ?><p>Přihlášku na <b><?= typo((string)$poslana['typ_clenstvi']) ?></b> jsme přijali <?= e(cz_date(substr((string)$poslana['created_at'], 0, 10))) ?>.</p><?php endif; ?>
            </div>
            <?php if ($kancelar['jmeno'] !== ''): ?>
            <p class="clen-dekujeme__kontakt drobne">S dotazem se obraťte na kancelář klubu – <?= typo($kancelar['jmeno']) ?><?php if ($kancelar['telefon'] !== ''): ?>, <a href="<?= e(tel_href($kancelar['telefon'])) ?>"><?= str_replace(' ', '&nbsp;', e(tel_kratce($kancelar['telefon']))) ?></a><?php endif; ?><?php if (je_email($kancelar['email'])): ?>, <a href="mailto:<?= e($kancelar['email']) ?>"><?= e($kancelar['email']) ?></a><?php endif; ?>.</p>
            <?php endif; ?>
            <div class="akce">
              <?php if ($poslana): ?><button class="btn btn--obrys" type="button" data-tisk-listu hidden>Vytisknout Členský list <?= sipka() ?></button><?php endif; ?>
              <?= tlacitko('klub.php', 'Zpět na stránku Klub', 'odkaz', ['sipka' => 'zpet']) ?>
            </div>
          </div>
          <?php if ($poslana):
            $kl = clen_klasifikuj((string)$poslana['typ_clenstvi']); ?>
          <div class="clenstvi-list">
            <?= $clenskyList(trim($poslana['jmeno'] . ' ' . $poslana['prijmeni']), clen_druh_nazev($kl['druh']), (string)$poslana['typ_clenstvi'], $poslana['cena'] !== null ? cena_kc((int)$poslana['cena']) : 'potvrdí kancelář', false, 'list-tisk') ?>
            <p class="drobne clenstvi-list__pozn">Členský list platí po rozhodnutí Výkonného výboru.</p>
          </div>
          <?php endif; ?>
        </div>
      <?php else: ?>
      <div class="clen-prihlaska__mrizka">
        <div class="clen-prihlaska__hlavni">
          <?= hlava_sekce($bPrihlaska, ['cislo' => ++$cislo, 'id' => 'prihlaska-nadpis', 'stitek' => 'Přihláška', 'nadpis' => 'Přihláška do klubu']) ?>

          <?php if ($chyba !== '' || $chyby): ?>
          <div class="hlaska hlaska--chyba clen-chyby" role="alert" tabindex="-1" id="prihlaska-chyby">
            <span class="hlaska__titul"><?= e($chyba !== '' ? $chyba : 'Zkontrolujte prosím zvýrazněná pole.') ?></span>
            <?php if ($chyby && !$tokenChyba): ?>
            <ul class="clen-chyby__seznam">
              <?php foreach ($chyby as $k => $t):
                $kdo = preg_match('/^osoby\.(\d+)\./', $k, $x) ? 'Osoba ' . rimske((int)$x[1] + 1) . ': ' : ''; ?><li><a href="#<?= e(clen_id($k) . ($k === 'pohlavi' || str_ends_with($k, '.pohlavi') ? '-zena' : '')) ?>"><?= e($kdo . $t) ?></a></li><?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <form class="formular prihlaska" id="prihlaska-formular" method="post" action="<?= e(url('clenstvi.php')) ?>#prihlaska" novalidate data-prihlaska data-vek-zastupce="<?= CLEN_VEK_ZASTUPCE ?>" data-dnes="<?= e($dnes) ?>">
            <?= verejny_formular_pole('clenstvi') ?>

            <ol class="kroky" data-kroky hidden aria-label="Kroky přihlášky">
              <?php foreach (['Osoby', 'Adresa a kontakt', 'Souhlasy', 'Shrnutí'] as $i => $n): ?>
              <li class="kroky__krok" data-krok-stav="<?= $i + 1 ?>"><span class="kroky__cislo" aria-hidden="true"><?= rimske($i + 1) ?></span><span class="kroky__nazev"><?= typo($n) ?></span></li>
              <?php endforeach; ?>
            </ol>

            <!-- KROK 1 · Osoby -->
            <div class="krok" id="krok-1" data-krok="1" role="group" aria-labelledby="krok-1-nadpis">
              <h3 class="krok__nadpis" id="krok-1-nadpis" tabindex="-1"><span class="krok__cislo">I.</span> Osoby a&nbsp;typ členství</h3>

              <?= clen_pole('typ', 'Typ členství', ['typ' => 'select', 'povinne' => true, 'hodnota' => $h('typ'), 'chyba' => $ch('typ'), 'moznosti' => $moznostiTypu,
                  'napoveda' => $model['lze_konfigurovat'] ? 'Cenu spočítáte v&nbsp;<a href="#cenik">konfigurátoru</a> výše. Přihláška je až pro pět osob.' : 'Přihláška je až pro pět osob.']) ?>

              <div class="clen-firma" data-jen-firemni>
                <p class="pole__napoveda clen-firma__pozn">Jen u&nbsp;firemního členství. Výpis z&nbsp;obchodního rejstříku pošlete prosím kanceláři klubu.</p>
                <div class="formular-radek">
                  <?= clen_pole('firma', 'Název firmy', ['znacka' => true, 'hodnota' => $h('firma'), 'chyba' => $ch('firma'), 'autocomplete' => 'organization', 'attr' => ['maxlength' => 160, 'data-povinne-firma' => true]]) ?>
                  <?= clen_pole('ico', 'IČO', ['znacka' => true, 'hodnota' => $h('ico'), 'chyba' => $ch('ico'), 'attr' => ['inputmode' => 'numeric', 'maxlength' => 10, 'data-povinne-firma' => true]]) ?>
                </div>
              </div>

              <fieldset class="clen-osoba" data-osoba="0">
                <legend class="clen-osoba__nadpis">Žadatel</legend>
                <div class="formular-radek">
                  <?= clen_pole('jmeno', 'Jméno', ['povinne' => true, 'hodnota' => $h('jmeno'), 'chyba' => $ch('jmeno'), 'autocomplete' => 'given-name', 'attr' => ['maxlength' => 80]]) ?>
                  <?= clen_pole('prijmeni', 'Příjmení', ['povinne' => true, 'hodnota' => $h('prijmeni'), 'chyba' => $ch('prijmeni'), 'autocomplete' => 'family-name', 'attr' => ['maxlength' => 80]]) ?>
                </div>
                <div class="formular-radek">
                  <?= clen_pohlavi('pohlavi', 'pohlavi', $h('pohlavi'), $ch('pohlavi'), true) ?>
                  <?= clen_pole('datum_narozeni', 'Datum narození', ['typ' => 'date', 'povinne' => true, 'hodnota' => (string)(normalizuj_datum($h('datum_narozeni')) ?: $h('datum_narozeni')), 'chyba' => $ch('datum_narozeni'), 'autocomplete' => 'bday', 'attr' => ['min' => '1900-01-01', 'max' => $dnes, 'data-narozeni' => true]]) ?>
                </div>
                <div class="formular-radek">
                  <?php if ($rcSbirat): ?><?= clen_pole('rodne_cislo', 'Rodné číslo', ['hodnota' => $h('rodne_cislo'), 'chyba' => $ch('rodne_cislo'), 'napoveda' => 'Nepovinné.', 'attr' => ['maxlength' => 16, 'inputmode' => 'numeric', 'autocomplete' => 'off']]) ?><?php endif; ?>
                  <?= clen_pole('obcanstvi', 'Státní občanství', ['povinne' => true, 'hodnota' => $h('obcanstvi'), 'chyba' => $ch('obcanstvi'), 'attr' => ['maxlength' => 60]]) ?>
                </div>
              </fieldset>

              <div class="clen-dalsi" data-dalsi-osoby>
                <p class="clen-dalsi__nadpis">Další osoby <span class="tlumene">– až <?= CLEN_MAX_OSOB ?>, u&nbsp;rodinného členství</span></p>
                <?php for ($i = 1; $i <= CLEN_MAX_OSOB; $i++):
                  $o = (array)($hod['osoby'][$i] ?? []);
                  $oh = static fn(string $k): string => (string)($o[$k] ?? '');
                  $plna = implode('', array_map('strval', $o)) !== '' || $i <= $osobyZKonf;
                  $maChybu = (bool)array_filter(array_keys($chyby), fn($k) => str_starts_with($k, 'osoby.' . $i . '.'));
                  $pre = 'osoby.' . $i . '.'; ?>
                <details class="rozbal clen-osoba clen-osoba--dalsi" data-osoba="<?= $i ?>"<?= $plna || $maChybu ? ' open' : '' ?>>
                  <summary><span>Osoba <?= rimske($i + 1) ?><span class="tlumene clen-osoba__souhrn" data-osoba-souhrn></span></span></summary>
                  <div class="rozbal__obsah">
                    <div class="formular-radek">
                      <?= clen_pole($pre . 'jmeno', 'Jméno', ['name' => "osoby[$i][jmeno]", 'znacka' => true, 'hodnota' => $oh('jmeno'), 'chyba' => $ch($pre . 'jmeno'), 'attr' => ['maxlength' => 80, 'data-povinne-osoba' => true, 'autocomplete' => 'off']]) ?>
                      <?= clen_pole($pre . 'prijmeni', 'Příjmení', ['name' => "osoby[$i][prijmeni]", 'znacka' => true, 'hodnota' => $oh('prijmeni'), 'chyba' => $ch($pre . 'prijmeni'), 'attr' => ['maxlength' => 80, 'data-povinne-osoba' => true, 'autocomplete' => 'off']]) ?>
                    </div>
                    <div class="formular-radek">
                      <?= clen_pohlavi($pre . 'pohlavi', "osoby[$i][pohlavi]", $oh('pohlavi'), $ch($pre . 'pohlavi'), false) ?>
                      <?= clen_pole($pre . 'datum_narozeni', 'Datum narození', ['typ' => 'date', 'name' => "osoby[$i][datum_narozeni]", 'znacka' => true, 'hodnota' => (string)(normalizuj_datum($oh('datum_narozeni')) ?: $oh('datum_narozeni')), 'chyba' => $ch($pre . 'datum_narozeni'), 'attr' => ['min' => '1900-01-01', 'max' => $dnes, 'data-narozeni' => true, 'data-povinne-osoba' => true]]) ?>
                    </div>
                    <div class="formular-radek">
                      <?php if ($rcSbirat): ?><?= clen_pole($pre . 'rodne_cislo', 'Rodné číslo', ['name' => "osoby[$i][rodne_cislo]", 'hodnota' => $oh('rodne_cislo'), 'chyba' => $ch($pre . 'rodne_cislo'), 'napoveda' => 'Nepovinné.', 'attr' => ['maxlength' => 16, 'inputmode' => 'numeric', 'autocomplete' => 'off']]) ?><?php endif; ?>
                      <?= clen_pole($pre . 'obcanstvi', 'Státní občanství', ['name' => "osoby[$i][obcanstvi]", 'znacka' => true, 'hodnota' => $oh('obcanstvi'), 'chyba' => $ch($pre . 'obcanstvi'), 'attr' => ['maxlength' => 60, 'data-povinne-osoba' => true]]) ?>
                    </div>
                    <p class="clen-osoba__akce" data-osoba-akce hidden><button class="odkaz odkaz--text" type="button" data-osoba-odebrat>Odebrat osobu <?= rimske($i + 1) ?></button></p>
                  </div>
                </details>
                <?php endfor; ?>
                <p class="clen-dalsi__akce" data-osoba-pridat-obal hidden><button class="btn btn--obrys btn--mala" type="button" data-osoba-pridat>Přidat další osobu</button></p>
              </div>

              <fieldset class="clen-zastupce" data-zastupce<?= $ch('zastupce_jmeno') . $ch('zastupce_telefon') . $ch('zastupce_email') . $ch('souhlas_zastupce') !== '' ? ' data-zastupce-chyba' : '' ?>>
                <legend class="clen-osoba__nadpis">Zákonný zástupce</legend>
                <p class="pole__napoveda" data-zastupce-pozn>Vyplňte, pokud je žadateli nebo některé z&nbsp;dalších osob méně než <?= CLEN_VEK_ZASTUPCE ?>&nbsp;let.</p>
                <div class="formular-radek">
                  <?= clen_pole('zastupce_jmeno', 'Jméno a&nbsp;příjmení zástupce', ['znacka' => true, 'hodnota' => $h('zastupce_jmeno'), 'chyba' => $ch('zastupce_jmeno'), 'autocomplete' => 'name', 'attr' => ['maxlength' => 160, 'data-povinne-zastupce' => true]]) ?>
                  <?= clen_pole('zastupce_vztah', 'Vztah k&nbsp;dítěti', ['hodnota' => $h('zastupce_vztah'), 'napoveda' => 'Nepovinné, např. matka, otec.', 'attr' => ['maxlength' => 60]]) ?>
                </div>
                <div class="formular-radek">
                  <?= clen_pole('zastupce_telefon', 'Telefon zástupce', ['typ' => 'tel', 'znacka' => true, 'hodnota' => $h('zastupce_telefon'), 'chyba' => $ch('zastupce_telefon'), 'attr' => ['inputmode' => 'tel', 'maxlength' => 40, 'data-povinne-zastupce' => true]]) ?>
                  <?= clen_pole('zastupce_email', 'E-mail zástupce', ['typ' => 'email', 'znacka' => true, 'hodnota' => $h('zastupce_email'), 'chyba' => $ch('zastupce_email'), 'attr' => ['inputmode' => 'email', 'maxlength' => 160, 'data-povinne-zastupce' => true]]) ?>
                </div>
                <?= clen_zaskrtavatko('souhlas_zastupce', 'Jako zákonný zástupce souhlasím s&nbsp;přihláškou osob mladších ' . CLEN_VEK_ZASTUPCE . '&nbsp;let do klubu.', (bool)($hod['souhlas_zastupce'] ?? false), $ch('souhlas_zastupce'), ['data-povinne-zastupce' => true]) ?>
              </fieldset>
            </div>

            <!-- KROK 2 · Adresa a kontakt -->
            <div class="krok" id="krok-2" data-krok="2" role="group" aria-labelledby="krok-2-nadpis">
              <h3 class="krok__nadpis" id="krok-2-nadpis" tabindex="-1"><span class="krok__cislo">II.</span> Adresa a&nbsp;kontakt</h3>
              <?= clen_pole('ulice', 'Ulice a&nbsp;číslo domu', ['povinne' => true, 'hodnota' => $h('ulice'), 'chyba' => $ch('ulice'), 'autocomplete' => 'street-address', 'attr' => ['maxlength' => 160]]) ?>
              <div class="formular-radek clen-adresa">
                <?= clen_pole('psc', 'PSČ', ['povinne' => true, 'hodnota' => $h('psc'), 'chyba' => $ch('psc'), 'autocomplete' => 'postal-code', 'attr' => ['maxlength' => 10, 'inputmode' => 'numeric']]) ?>
                <?= clen_pole('mesto', 'Obec', ['povinne' => true, 'hodnota' => $h('mesto'), 'chyba' => $ch('mesto'), 'autocomplete' => 'address-level2', 'attr' => ['maxlength' => 120]]) ?>
                <?= clen_pole('stat', 'Stát', ['hodnota' => $h('stat'), 'napoveda' => 'Nepovinné.', 'autocomplete' => 'country-name', 'attr' => ['maxlength' => 60]]) ?>
              </div>
              <div class="formular-radek">
                <?= clen_pole('telefon', 'Telefon', ['typ' => 'tel', 'povinne' => true, 'hodnota' => $h('telefon'), 'chyba' => $ch('telefon'), 'autocomplete' => 'tel', 'attr' => ['inputmode' => 'tel', 'maxlength' => 40]]) ?>
                <?= clen_pole('email', 'E-mail', ['typ' => 'email', 'povinne' => true, 'hodnota' => $h('email'), 'chyba' => $ch('email'), 'autocomplete' => 'email', 'attr' => ['inputmode' => 'email', 'maxlength' => 160]]) ?>
              </div>
              <div class="formular-radek">
                <?= clen_pole('profese', 'Současná profese', ['povinne' => true, 'hodnota' => $h('profese'), 'chyba' => $ch('profese'), 'autocomplete' => 'organization-title', 'attr' => ['maxlength' => 160]]) ?>
                <?= clen_pole('zdroj', 'Jak jste se o&nbsp;nás dozvěděli', ['povinne' => true, 'hodnota' => $h('zdroj'), 'chyba' => $ch('zdroj'), 'attr' => ['maxlength' => 160]]) ?>
              </div>
            </div>

            <!-- KROK 3 · Souhlasy -->
            <div class="krok" id="krok-3" data-krok="3" role="group" aria-labelledby="krok-3-nadpis">
              <h3 class="krok__nadpis" id="krok-3-nadpis" tabindex="-1"><span class="krok__cislo">III.</span> Souhlasy</h3>
              <?php
              $gdprText = trim((string)$bSouhlas['perex']) !== '' ? typo((string)$bSouhlas['perex']) : 'Souhlasím se zpracováním uvedených osobních údajů za účelem vyřízení přihlášky.';
              if ($gdprDok && dokument_url($gdprDok) !== '') $gdprText .= ' <a class="souhlas-odkaz" href="' . e(dokument_url($gdprDok)) . '"' . odkaz_attr(dokument_url($gdprDok)) . '>' . typo((string)$gdprDok['nazev']) . (odkaz_je_externi(dokument_url($gdprDok)) ? '<span class="vh"> (v novém okně)</span>' : '') . '</a>';
              $podminkyText = 'Souhlasím s&nbsp;podmínkami členství'
                  . ($stanovy && dokument_url($stanovy) !== '' ? ' podle <a href="' . e(dokument_url($stanovy)) . '"' . odkaz_attr(dokument_url($stanovy)) . '>stanov klubu' . (odkaz_je_externi(dokument_url($stanovy)) ? '<span class="vh"> (v novém okně)</span>' : '') . '</a>' : '')
                  . ' a&nbsp;se zaplacením ročního členského příspěvku.';
              ?>
              <?= clen_zaskrtavatko('souhlas_gdpr', $gdprText, (bool)($hod['souhlas_gdpr'] ?? false), $ch('souhlas_gdpr'), ['required' => true]) ?>
              <?= clen_zaskrtavatko('souhlas_podminky', $podminkyText, (bool)($hod['souhlas_podminky'] ?? false), $ch('souhlas_podminky'), ['required' => true]) ?>
              <?= clen_zaskrtavatko('souhlas_schvaleni', 'Beru na vědomí, že žádost bude podstoupena schvalovacímu procesu – o&nbsp;přijetí rozhoduje Výkonný výbor.', (bool)($hod['souhlas_schvaleni'] ?? false), $ch('souhlas_schvaleni'), ['required' => true]) ?>
              <?= clen_pole('poznamka', 'Poznámka pro kancelář', ['typ' => 'textarea', 'hodnota' => $h('poznamka'), 'napoveda' => 'Nepovinné – např. jiná kombinace osob nebo dotaz.', 'attr' => ['maxlength' => 2000], 'trida' => 'clen-poznamka']) ?>
            </div>

            <!-- KROK 4 · Shrnutí (jen s JavaScriptem) -->
            <div class="krok" id="krok-4" data-krok="4" role="group" aria-labelledby="krok-4-nadpis" hidden>
              <h3 class="krok__nadpis" id="krok-4-nadpis" tabindex="-1"><span class="krok__cislo">IV.</span> Shrnutí</h3>
              <p class="drobne">Zkontrolujte prosím údaje. Opravit je můžete tlačítkem Zpět nebo kliknutím na krok nahoře.</p>
              <div class="clen-shrnuti" data-shrnuti></div>
            </div>

            <div class="formular__akce clen-odeslat" data-odeslat>
              <button class="btn" type="submit">Odeslat přihlášku <?= sipka() ?></button>
              <span class="formular__pozn"><span aria-hidden="true">*</span> povinné údaje</span>
            </div>
          </form>
        </div>

        <aside class="clen-prihlaska__bok" aria-label="Kancelář klubu a dokumenty">
          <?php if ($kancelar['jmeno'] !== ''): ?>
          <div class="clen-bok__blok">
            <p class="stitek">Kancelář klubu</p>
            <p class="clen-bok__jmeno"><?= typo($kancelar['jmeno']) ?></p>
            <?php if ($kancelar['popis'] !== ''): ?><p class="drobne"><?= typo($kancelar['popis']) ?></p><?php endif; ?>
            <ul class="radky clen-bok__kontakty">
              <?php if ($kancelar['telefon'] !== ''): ?><li class="radek"><span class="radek__nazev">Telefon</span><a class="radek__hodnota" href="<?= e(tel_href($kancelar['telefon'])) ?>"><?= str_replace(' ', '&nbsp;', e(tel_kratce($kancelar['telefon']))) ?></a></li><?php endif; ?>
              <?php if (je_email($kancelar['email'])): ?><li class="radek"><span class="radek__nazev">E-mail</span><a class="radek__hodnota" href="mailto:<?= e($kancelar['email']) ?>"><?= e($kancelar['email']) ?></a></li><?php endif; ?>
            </ul>
          </div>
          <?php endif; ?>
          <?php if ($dokumenty): ?>
          <div class="clen-bok__blok">
            <p class="stitek">Dokumenty</p>
            <?= dokumenty_html($dokumenty) ?>
          </div>
          <?php endif; ?>
          <?php if (trim((string)$bPrihlaska['text']) !== ''): ?><div class="clen-bok__blok"><?= blok_text($bPrihlaska) ?></div><?php endif; ?>
        </aside>
      </div>
      <?php endif; ?>
    </div>
  </section>

<?php require __DIR__ . '/inc/sablona/paticka.php'; ?>
