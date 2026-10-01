<?php
/* Komponenty veřejného webu I. ČLTK Praha – pomůcky pro šablonu a stránky.

   Vkládá je hlavicka.php (require_once), stránka si je může vložit dřív sama:
       require_once __DIR__ . '/inc/sablona/komponenty.php';
   Všechny funkce vracejí HTML jako řetězec (vypisuje se <?= … ?>) a všechno,
   co jde z databáze, escapují samy (e(), html_inline(), paragraphs(),
   html_ocistit()). Přehled a příklady: web/PRUVODCE-STRANKY.md. */

require_once dirname(__DIR__) . '/rezim.php';

/* ------------------------------------------------------------------
   LADĚNÍ DATA – jen lokálně (SQLite a požadavek z tohoto počítače):
   ?dnes=2026-11-02 nastaví „dnešek“ pro kalendář a informační lištu.
   Na serveru (MySQL) ho dnes() vůbec nepřečte, takže nic neovlivní.
   ------------------------------------------------------------------ */
function sablona_ladeni_dnes(): string {
    static $hotovo = null;
    if ($hotovo !== null) return $hotovo;
    $hotovo = '';
    if (PHP_SAPI === 'cli' || DB_DRIVER !== 'sqlite') return $hotovo;
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if (!in_array($ip, ['127.0.0.1', '::1'], true) && !str_starts_with($ip, '127.')) return $hotovo;
    $d = vstup_get('dnes');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) || !checkdate((int)substr($d, 5, 2), (int)substr($d, 8, 2), (int)substr($d, 0, 4))) return $hotovo;
    putenv('CLTK_DNES=' . $d);           // dnes() ho přečte (jen SQLite); po požadavku PHP prostředí vrátí
    return $hotovo = $d;
}
sablona_ladeni_dnes();

/* ------------------------------------------------------------------
   BEZPEČNOSTNÍ HLAVIČKY VEŘEJNÉHO WEBU (Content-Security-Policy)
   Jediný vložený skript je SABLONA_SKRIPT_HLAVICKY (třída „js“ a volby
   přístupnosti dřív, než se stránka vykreslí); CSP ho pouští podle otisku.
   Cizí skript podstrčený do stránky (XSS) by se nespustil – na sdílené
   doméně s webem TK Olymp je to důležité. Nový vložený skript = upravit
   tuhle konstantu (otisk se spočítá sám), jinak patří do assets/js/.
   ------------------------------------------------------------------ */
const SABLONA_SKRIPT_HLAVICKY = "(function(h){h.classList.add('js');try{var p=localStorage.getItem('cltk-a:pohyb'),k=localStorage.getItem('cltk-a:kontrast');if(p)h.setAttribute('data-pohyb',p);if(k)h.setAttribute('data-kontrast',k);}catch(e){}})(document.documentElement);";

function sablona_bezpecnostni_hlavicky(): void {
    if (PHP_SAPI === 'cli' || headers_sent()) return;
    $otisk = base64_encode(hash('sha256', SABLONA_SKRIPT_HLAVICKY, true));
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'sha256-" . $otisk . "'; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; "
         . "img-src 'self' data: https:; media-src 'self'; connect-src 'self'; frame-src 'self' https:; "
         . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
}

/* ==================================================================
   DROBNOSTI
   ================================================================== */

/** Decentní štítek „doplní klub“ (obsah zatím chybí – nevymýšlet). */
function doplni_klub(string $text = 'doplní klub'): string {
    return '<span class="doplni">' . e($text) . '</span>';
}

/** Šipka kreslená linkou; $druh: '' (→), 'ven' (↗), 'zpet', 'nahoru'. */
function sipka(string $druh = ''): string {
    return '<span class="sipka' . ($druh !== '' ? ' sipka--' . e($druh) : '') . '" aria-hidden="true"></span>';
}

/**
 * Tlačítko nebo odkaz se šipkou. $url projde bezpecny_odkaz() (odkaz z DB i „stranka.php“).
 *   tlacitko('clenstvi.php', 'Stát se členem')                       → navy tlačítko ↗
 *   tlacitko($b['odkaz2'], $b['odkaz2_text'], 'odkaz')               → podtržený odkaz →
 *   volby: 'trida' (navíc), 'sipka' ('ven' | '' | 'zadna'), 'attr' (pole atributů)
 * Odkaz ven dostane target="_blank" rel="noopener" a skrytou poznámku „(v novém okně)“.
 */
function tlacitko(?string $url, ?string $text, string $druh = 'btn', array $volby = []): string {
    $href = bezpecny_odkaz($url);
    $text = trim((string)$text);
    if ($href === '' || $text === '') return '';
    $ven = odkaz_je_externi($href);
    $trida = ($druh === 'odkaz' ? 'odkaz' : 'btn') . (isset($volby['trida']) ? ' ' . $volby['trida'] : '');
    $sipkaDruh = $volby['sipka'] ?? ($druh === 'odkaz' ? ($ven ? 'ven' : '') : 'ven');
    $attr = '';
    foreach ((array)($volby['attr'] ?? []) as $k => $v) $attr .= ' ' . e((string)$k) . '="' . e((string)$v) . '"';
    return '<a class="' . e($trida) . '" href="' . e($href) . '"' . odkaz_attr($href) . $attr . '>'
         . ($sipkaDruh === 'zpet' ? sipka('zpet') . ' ' : '')
         . typo($text) . ($sipkaDruh === 'zadna' || $sipkaDruh === 'zpet' ? '' : ' ' . sipka($sipkaDruh))
         . ($ven ? '<span class="vh"> (v novém okně)</span>' : '') . '</a>';
}

/** Hlavní a vedlejší tlačítko bloku (odkaz/odkaz_text → btn, odkaz2/odkaz2_text → odkaz). */
function blok_tlacitka(array $b, string $trida = 'akce'): string {
    $h = tlacitko($b['odkaz'] ?? '', $b['odkaz_text'] ?? '') . tlacitko($b['odkaz2'] ?? '', $b['odkaz2_text'] ?? '', 'odkaz');
    return $h === '' ? '' : '<div class="' . e($trida) . '">' . $h . '</div>';
}

/** Data pro JavaScript: <script type="application/json" id="…">. */
function json_skript(string $id, $data): string {
    return '<script type="application/json" id="' . e($id) . '">' . json_do_stranky($data) . '</script>';
}

/** Obrázek z uploads/ s WebP: obr($r['foto'], 'Popis', ['class' => 'foto', 'fokus' => $r['fokus']]). */
function obr(?string $rel, string $alt = '', array $attr = []): string {
    if (trim((string)$rel) === '' || !is_file(UPLOAD_DIR . '/' . ltrim((string)$rel, '/'))) return '';
    $fokus = trim((string)($attr['fokus'] ?? ''));
    unset($attr['fokus']);
    if ($fokus !== '' && preg_match('/^[\d.]+%\s+[\d.]+%$/', $fokus)) {
        $attr['style'] = trim(($attr['style'] ?? '') . ';object-position:' . $fokus, ';');
    }
    return obrazek_html($rel, array_merge(['alt' => $alt], $attr));
}

/* ==================================================================
   HLAVA STRÁNKY A HLAVY SEKCÍ
   ================================================================== */

/**
 * Hlava podstránky z bloku 'uvod' (stitek, nadpis s <em>, perex, foto, tlačítka, doplni_klub).
 *   <?= hlava_stranky(blok('areal', 'uvod'), ['drobky' => [['Areál a služby']]]) ?>
 * Volby:
 *   'drobky'   => [['Klub', 'klub.php'], ['Historie']]  – drobečková navigace (Úvod se doplní sám,
 *                 poslední položka = tato stránka bez odkazu); false = nevypisovat
 *   'nadpis'   => náhradní nadpis, když blok chybí (prostý text)
 *   'stitek'   => náhradní štítek
 *   'foto'     => true (výchozí: když má blok foto) / false
 *   'trida'    => další třídy (např. 'hlava-stranky--papir2')
 *   'navic'    => HTML vložené pod perex (už escapované!)
 */
function hlava_stranky(array $b, array $volby = []): string {
    $stitek = trim((string)($b['stitek'] ?? '')) ?: (string)($volby['stitek'] ?? '');
    $nadpis = trim((string)($b['nadpis'] ?? ''));
    $nadpisHtml = $nadpis !== '' ? html_inline($nadpis) : typo((string)($volby['nadpis'] ?? ''));
    $foto = (string)($b['foto'] ?? '');
    $sFotkou = ($volby['foto'] ?? true) && $foto !== '' && is_file(UPLOAD_DIR . '/' . ltrim($foto, '/'));
    $trida = 'hlava-stranky' . ($sFotkou ? ' hlava-stranky--s-fotkou' : '') . (isset($volby['trida']) ? ' ' . $volby['trida'] : '');

    $h = '<header class="' . e($trida) . '"><div class="wrap hlava-stranky__plocha"><div class="hlava-stranky__text">';
    if (($volby['drobky'] ?? []) !== false) $h .= drobky((array)($volby['drobky'] ?? []));
    if ($stitek !== '') $h .= '<p class="hlava__znacka"><span class="stitek">' . typo($stitek) . '</span></p>';
    $h .= '<h1 class="h1 hlava-stranky__nadpis">' . $nadpisHtml . '</h1>';
    if (trim((string)($b['perex'] ?? '')) !== '') $h .= '<div class="perex hlava-stranky__perex">' . paragraphs((string)$b['perex']) . '</div>';
    if ((int)($b['doplni_klub'] ?? 0) === 1) $h .= '<p class="hlava-stranky__doplni">' . doplni_klub('obsah doplní klub') . '</p>';
    if (!empty($volby['navic'])) $h .= (string)$volby['navic'];
    $h .= blok_tlacitka($b);
    $h .= '</div>';
    if ($sFotkou) {
        $h .= '<figure class="hlava-stranky__foto ramec ramec--linka"><div class="ramec__obraz">'
            . obr($foto, html_text((string)($b['foto_popisek'] ?? '')) ?: html_text($nadpis), ['class' => 'foto', 'loading' => 'eager', 'fetchpriority' => 'high'])
            . '</div>' . (trim((string)($b['foto_popisek'] ?? '')) !== '' ? '<figcaption class="popisek"><span>' . html_inline((string)$b['foto_popisek']) . '</span></figcaption>' : '')
            . '</figure>';
    }
    return $h . '</div></header>';
}

/** Drobečková navigace: drobky([['Klub', 'klub.php'], ['Historie']]) – „Úvod“ se doplní sám. */
function drobky(array $cesta): string {
    if (!$cesta) return '';
    $h = '<nav class="drobky" aria-label="Kde jste"><ol><li><a href="' . e(url('index.php')) . '">Úvod</a></li>';
    $n = count($cesta);
    foreach (array_values($cesta) as $i => $c) {
        $nazev = (string)($c[0] ?? '');
        $odkaz = (string)($c[1] ?? '');
        if ($i === $n - 1 || $odkaz === '') $h .= '<li' . ($i === $n - 1 ? ' aria-current="page"' : '') . '>' . typo($nazev) . '</li>';
        else $h .= '<li><a href="' . e(url($odkaz)) . '">' . typo($nazev) . '</a></li>';
    }
    return $h . '</ol></nav>';
}

/**
 * Hlava sekce (trikolora · římská číslice · štítek, h2, perex) z bloku.
 *   <?= hlava_sekce(blok('areal', 'kurty'), ['cislo' => 2, 'id' => 'kurty-nadpis']) ?>
 * Volby: 'cislo' (int → římská číslice), 'id' (id nadpisu pro aria-labelledby), 'tag' (h2),
 *        'stred' (bool), 'radek' (bool – perex vpravo vedle nadpisu), 'nadpis'/'stitek' náhradní,
 *        'bez_znacky' (bool – bez trikolory)
 */
function hlava_sekce(array $b, array $volby = []): string {
    $tag = in_array($volby['tag'] ?? 'h2', ['h2', 'h3'], true) ? ($volby['tag'] ?? 'h2') : 'h2';
    $stitek = trim((string)($b['stitek'] ?? '')) ?: (string)($volby['stitek'] ?? '');
    $nadpis = trim((string)($b['nadpis'] ?? ''));
    $nadpisHtml = $nadpis !== '' ? html_inline($nadpis) : typo((string)($volby['nadpis'] ?? ''));
    $perex = trim((string)($b['perex'] ?? ''));
    $id = isset($volby['id']) ? ' id="' . e((string)$volby['id']) . '"' : '';
    $radek = !empty($volby['radek']) && $perex !== '';
    $trida = 'hlava' . (!empty($volby['stred']) ? ' stred' : '') . ($radek ? ' hlava--radek' : '');

    $znacka = '';
    if ($stitek !== '' || isset($volby['cislo'])) {
        $znacka = '<p class="hlava__znacka' . (!empty($volby['bez_znacky']) ? ' hlava__znacka--bez' : '') . '">'
                . (isset($volby['cislo']) ? '<span class="hlava__cislo">' . e(rimske((int)$volby['cislo'])) . '</span>' : '')
                . ($stitek !== '' ? '<span class="stitek">' . typo($stitek) . '</span>' : '') . '</p>';
    }
    $nad = $nadpisHtml !== '' ? '<' . $tag . ' class="h2"' . $id . '>' . $nadpisHtml . '</' . $tag . '>' : '';
    $per = $perex !== '' ? '<div class="perex">' . paragraphs($perex) . '</div>' : '';
    $dopl = (int)($b['doplni_klub'] ?? 0) === 1 ? '<p class="hlava__doplni">' . doplni_klub() . '</p>' : '';
    if ($radek) return '<header class="' . $trida . '"><div>' . $znacka . $nad . '</div>' . $per . $dopl . '</header>';
    return '<header class="' . $trida . '">' . $znacka . $nad . $per . $dopl . '</header>';
}

/**
 * Text bloku z editoru (html_ocistit) v obalu .prose; prázdný text + doplni_klub → štítek.
 *   <?= blok_text(blok('body-solution', 'uvod')) ?>
 */
function blok_text(array $b, string $trida = 'prose'): string {
    $text = html_ocistit((string)($b['text'] ?? ''));
    if ($text === '') {
        return (int)($b['doplni_klub'] ?? 0) === 1 ? '<p class="blok-doplni">' . doplni_klub('obsah doplní klub') . '</p>' : '';
    }
    return '<div class="' . e($trida) . '">' . $text . '</div>';
}

/** Fotka bloku v rámečku s popiskem (prázdné, když blok fotku nemá). */
function blok_foto(array $b, string $pomer = 'pomer-3x2', array $volby = []): string {
    $img = obr((string)($b['foto'] ?? ''), html_text((string)($b['foto_popisek'] ?? '')) ?: html_text((string)($b['nadpis'] ?? '')), ['class' => 'foto']);
    if ($img === '') return '';
    $pop = trim((string)($b['foto_popisek'] ?? ''));
    return '<figure class="ramec ramec--linka' . (isset($volby['trida']) ? ' ' . e($volby['trida']) : '') . '"><div class="ramec__obraz ' . e($pomer) . '">' . $img . '</div>'
         . ($pop !== '' ? '<figcaption class="popisek"><span>' . html_inline($pop) . '</span></figcaption>' : '') . '</figure>';
}

/* ==================================================================
   OBSAHOVÉ KOMPONENTY
   ================================================================== */

/**
 * Ceník z administrace (cenik('kurty-leto')) jako tabulka V4: sekce = skupina řádků,
 * sloupce podle neprázdných hlaviček sekce (hl_cena, hl_cena_clen, hl_cena_sezona, hl_cena_sezona_clen).
 *   <?= cenik_html(cenik('kurty-zima')) ?>
 * Volby: 'nadpis' (bool, výchozí true – název listu nad tabulkou), 'id' (kotva)
 * Ceník, který neexistuje nebo je skrytý, vrátí štítek „ceník doplní klub“.
 */
function cenik_html(?array $c, array $volby = []): string {
    if (!$c) return '<p>' . doplni_klub('ceník doplní klub') . '</p>';
    $l = $c['list'];
    $id = isset($volby['id']) ? ' id="' . e((string)$volby['id']) . '"' : '';
    $h = '<div class="cenik"' . $id . '>';
    if ($volby['nadpis'] ?? true) {
        $h .= '<div class="cenik__hlava"><h3 class="cenik__nazev">' . typo((string)$l['nazev']) . '</h3>'
            . (trim((string)$l['obdobi']) !== '' ? '<p class="cenik__obdobi">' . typo((string)$l['obdobi']) . '</p>' : '') . '</div>';
        if (trim((string)$l['podnazev']) !== '') $h .= '<p class="drobne">' . typo((string)$l['podnazev']) . '</p>';
    }
    if (trim((string)$l['poznamka_nahore']) !== '') $h .= '<div class="cenik__pozn">' . paragraphs((string)$l['poznamka_nahore']) . '</div>';
    foreach ($c['sekce'] as $s) {
        $sloupce = [];
        foreach (['cena' => 'hl_cena', 'cena_clen' => 'hl_cena_clen', 'cena_sezona' => 'hl_cena_sezona', 'cena_sezona_clen' => 'hl_cena_sezona_clen'] as $pole => $hl) {
            if (trim((string)($s[$hl] ?? '')) !== '') $sloupce[$pole] = (string)$s[$hl];
        }
        if (!$sloupce) $sloupce = ['cena' => 'Cena'];
        $h .= '<div class="tabulka-box"><table class="tabulka">';
        $h .= '<caption>' . typo((string)$s['nazev']) . (trim((string)$s['popis']) !== '' ? '<small>' . typo((string)$s['popis']) . '</small>' : '') . '</caption>';
        $h .= '<thead><tr><th scope="col">' . typo(trim((string)($s['hl_nazev'] ?? '')) ?: 'Položka') . '</th>';
        foreach ($sloupce as $pole => $nazev) $h .= '<th scope="col" class="c' . (str_contains($pole, 'clen') ? ' clen' : '') . '">' . typo($nazev) . '</th>';
        $h .= '</tr></thead><tbody>';
        foreach ($s['radky'] as $r) {
            $h .= '<tr><th scope="row">' . typo((string)$r['nazev']) . (trim((string)$r['poznamka']) !== '' ? '<small>' . typo((string)$r['poznamka']) . '</small>' : '') . '</th>';
            foreach (array_keys($sloupce) as $pole) {
                $v = trim((string)($r[$pole] ?? ''));
                $h .= '<td class="c' . (str_contains($pole, 'clen') ? ' clen' : '') . '">' . ($v !== '' ? typo($v) : '–') . '</td>';
            }
            $h .= '</tr>';
        }
        if (!$s['radky']) $h .= '<tr><td colspan="' . (count($sloupce) + 1) . '">' . doplni_klub() . '</td></tr>';
        $h .= '</tbody></table></div>';
    }
    if (trim((string)$l['poznamka_dole']) !== '') $h .= '<div class="cenik__pozn">' . paragraphs((string)$l['poznamka_dole']) . '</div>';
    $pdf = bezpecny_odkaz((string)($l['pdf_url'] ?? ''));
    if ($pdf !== '') $h .= '<p class="cenik__pozn">' . tlacitko($pdf, 'Ceník v PDF', 'odkaz') . '</p>';
    return $h . '</div>';
}

/**
 * Karta člověka (trenér z treneri(), člen vedení z vedeni()).
 *   <ul class="osoby"><?php foreach (treneri('skola') as $t) echo osoba_html($t); ?></ul>
 * Volby: 'tag' (li | div), 'foto' (bool). Telefon a e-mail se nevypíšou, když
 * řádek má zobrazit_kontakt = 0 (vedení).
 */
function osoba_html(array $o, array $volby = []): string {
    $tag = ($volby['tag'] ?? 'li') === 'div' ? 'div' : 'li';
    $jmeno = (string)($o['jmeno'] ?? '');
    $role = (string)($o['role'] ?? ($o['funkce'] ?? ''));
    $h = '<' . $tag . ' class="osoba">';
    if ($volby['foto'] ?? true) {
        $img = obr((string)($o['foto'] ?? ''), $jmeno, ['fokus' => (string)($o['fokus'] ?? '')]);
        $h .= $img !== ''
            ? '<div class="osoba__foto">' . $img . '</div>'
            : '<div class="osoba__foto osoba__foto--prazdne" aria-hidden="true"><span>' . e(mb_substr($jmeno, 0, 1)) . '</span></div>';
    }
    if ($role !== '') $h .= '<p class="osoba__role">' . typo($role) . '</p>';
    $h .= '<h3 class="osoba__jmeno">' . typo($jmeno) . '</h3>';
    if (trim((string)($o['fakta'] ?? '')) !== '') $h .= radky_seznam((string)$o['fakta'], 'osoba__fakta');
    if (trim((string)($o['text'] ?? '')) !== '') $h .= '<div class="osoba__text">' . paragraphs((string)$o['text']) . '</div>';
    $kontakt = '';
    $smi = !isset($o['zobrazit_kontakt']) || (int)$o['zobrazit_kontakt'] === 1;
    if ($smi && trim((string)($o['telefon'] ?? '')) !== '') $kontakt .= '<a href="' . e(tel_href((string)$o['telefon'])) . '">' . e((string)$o['telefon']) . '</a>';
    if ($smi && je_email((string)($o['email'] ?? ''))) $kontakt .= '<a href="mailto:' . e((string)$o['email']) . '">' . e((string)$o['email']) . '</a>';
    if (trim((string)($o['kontakt'] ?? '')) !== '') $kontakt .= '<span class="drobne">' . typo((string)$o['kontakt']) . '</span>';
    if ($kontakt !== '') $h .= '<p class="osoba__kontakt">' . $kontakt . '</p>';
    return $h . '</' . $tag . '>';
}

/** Pilulky s odkazy: pilulky_html([['Recepce', 'areal.php#recepce'], …]). */
function pilulky_html(array $polozky, string $trida = 'pilulky'): string {
    $h = '';
    foreach ($polozky as $p) {
        $href = bezpecny_odkaz((string)($p[1] ?? ''));
        $h .= '<li>' . ($href !== ''
            ? '<a class="pilulka" href="' . e($href) . '"' . odkaz_attr($href) . '>' . typo((string)$p[0]) . '</a>'
            : '<span class="pilulka">' . typo((string)$p[0]) . '</span>') . '</li>';
    }
    return $h === '' ? '' : '<ul class="' . e($trida) . '" role="list">' . $h . '</ul>';
}

/** Seznam dokumentů ke stažení (dokumenty('klub')). */
function dokumenty_html(array $docs): string {
    if (!$docs) return '';
    $h = '<ul class="dokumenty">';
    foreach ($docs as $d) {
        $href = dokument_url($d);
        if ($href === '') continue;
        $pdf = !empty($d['soubor']) || preg_match('~\.pdf($|\?)~i', $href);
        $h .= '<li><a class="dokument" href="' . e($href) . '"' . odkaz_attr($href) . '>'
            . '<span class="dokument__nazev">' . typo((string)$d['nazev']) . '</span>'
            . '<span class="dokument__typ">' . ($pdf ? 'PDF' : 'odkaz') . (odkaz_je_externi($href) ? '<span class="vh"> (v novém okně)</span>' : '') . '</span>'
            . (trim((string)($d['popis'] ?? '')) !== '' ? '<span class="dokument__popis">' . typo((string)$d['popis']) . '</span>' : '')
            . '</a></li>';
    }
    return $h . '</ul>';
}

/** Sety výsledku (sety_pole ze sety_rozloz()) jako velká čísla; tiebreak horním indexem. */
function sety_html(array $sety): string {
    $h = '';
    foreach ($sety as $s) {
        $dopl = trim((string)$s['doplnek']);
        $navic = '';
        if (preg_match('/^\(?(\d+:\d+)\)?$/', $dopl, $m)) $navic = '<sup>' . e($m[1]) . '</sup>';
        elseif ($dopl !== '') $navic = '<small>' . e($dopl) . '</small>';
        $h .= '<span class="set' . ($s['vyhra'] ? '' : ' set--p') . '" aria-hidden="true">' . e((string)$s['hlavni']) . $navic . '</span>';
    }
    return $h;
}

/** Textový zápis setů pro čtečky: „6:3, 1:6, 10:6“. */
function sety_text(array $sety): string {
    return implode(', ', array_map(fn($s) => (string)$s['text'], $sety));
}

/**
 * Karta výsledku do vodorovného pásu (úvod, případně závodní tenis).
 *   <?= vysledek_karta_html($v) ?>   ($v z posledni_vysledky())
 */
function vysledek_karta_html(array $v, string $tag = 'li'): string {
    $odkaz = bezpecny_odkaz((string)$v['odkaz']);
    $kdo = typo((string)$v['hraci']);
    $h = '<' . $tag . ' class="vysledek-karta">';
    $h .= '<p class="vysledek-karta__stitek">' . typo((string)$v['stitek']) . '</p>';
    $h .= '<h3 class="vysledek-karta__kdo">' . ($odkaz !== ''
        ? '<a href="' . e($odkaz) . '"' . odkaz_attr($odkaz) . '>' . $kdo . (odkaz_je_externi($odkaz) ? '<span class="vh"> – zpráva v novém okně</span>' : '') . '</a>'
        : $kdo) . '</h3>';
    $h .= '<p class="vysledek-karta__text">' . typo((string)$v['text']) . '</p>';
    if ($v['sety_pole'] || trim((string)$v['verdikt']) !== '') {
        $h .= '<div class="vysledek-karta__pata">';
        if ($v['sety_pole']) $h .= '<p class="vysledek-karta__skore"><span class="vh">Výsledek ' . e(sety_text($v['sety_pole'])) . '</span>' . sety_html($v['sety_pole']) . '</p>';
        if (trim((string)$v['verdikt']) !== '') $h .= '<span class="vysledek-karta__verdikt">' . typo((string)$v['verdikt']) . '</span>';
        $h .= '</div>';
    }
    $kdy = array_filter([cz_date((string)$v['datum']) !== '' ? '<time datetime="' . e(substr((string)$v['datum'], 0, 10)) . '">' . e(cz_date((string)$v['datum'])) . '</time>' : '', trim((string)$v['misto']) !== '' ? typo((string)$v['misto']) : '']);
    if ($kdy) $h .= '<p class="vysledek-karta__kdy">' . implode(' · ', $kdy) . '</p>';
    return $h . '</' . $tag . '>';
}

/* ==================================================================
   KALENDÁŘ A PŘIHLÁŠKA K AKCI
   Detail v okně kalendáře vykresluje server (výchozí akce) i JavaScript
   (kalendar.js) – oba ze stejných dat akce_pro_js() a stejným HTML.
   ================================================================== */

/** Řádek „Klubová akce · areál klubu · od 19:00“ pod názvem akce. */
function akce_meta_text(array $a): string {
    return implode(' · ', array_filter(array_map('trim', [(string)($a['stitek'] ?? ''), (string)($a['misto'] ?? ''), (string)($a['cas'] ?? '')])));
}

/** Stav přihlašování pro návštěvníka ('' = lze se přihlásit nebo přihlašování není). */
function akce_stav_prihlasek(array $a): string {
    if (empty($a['prihlaseni_zapnuto']) && (int)($a['prihlaseni_povoleno'] ?? 0) !== 1) return '';
    if (!empty($a['prihlaseni'])) return '';
    if (!empty($a['probehla'])) return 'Akce už proběhla.';
    if (!empty($a['obsazeno'])) return 'Kapacita akce je naplněná – přihlašování je uzavřené.';
    return 'Přihlašování na tuto akci je uzavřené.';
}

/** Detail akce v okně kalendáře; $a = akce_pro_js(). */
function kalendar_detail_html(array $a): string {
    $h = '<article class="kal-detail" data-akce-id="' . (int)$a['id'] . '" aria-labelledby="kal-detail-nazev">';
    $h .= '<p class="stitek kal-detail__termin">' . typo((string)$a['termin_dlouze']) . '</p>';
    $h .= '<h3 class="kal-detail__nazev" id="kal-detail-nazev">' . typo((string)$a['nazev']) . '</h3>';
    $meta = akce_meta_text($a);
    if ($meta !== '') $h .= '<p class="kal-detail__meta">' . typo($meta) . '</p>';
    if (!empty($a['probehla'])) $h .= '<p class="kal-detail__stav"><span class="doplni">proběhlo</span></p>';
    if ((string)$a['perex_html'] !== '') $h .= '<div class="kal-detail__perex">' . $a['perex_html'] . '</div>';
    $akce = '';
    if (!empty($a['prihlaseni'])) {
        $akce .= '<a class="btn" href="' . e($a['detail_url'] . '#prihlaska') . '" data-prihlasit>'
               . typo((string)($a['formular']['tlacitko'] ?? 'Přihlásit se')) . ' ' . sipka('ven') . '</a>';
    }
    if ((string)$a['odkaz'] !== '' && (string)$a['odkaz_text'] !== '') {
        $akce .= '<a class="odkaz" href="' . e((string)$a['odkaz']) . '"' . odkaz_attr((string)$a['odkaz']) . '>' . typo((string)$a['odkaz_text'])
               . ' ' . sipka(!empty($a['odkaz_externi']) ? 'ven' : '') . (!empty($a['odkaz_externi']) ? '<span class="vh"> (v novém okně)</span>' : '') . '</a>';
    }
    if ($akce !== '') $h .= '<div class="kal-detail__akce">' . $akce . '</div>';
    $stav = akce_stav_prihlasek($a);
    if ($stav !== '' && empty($a['probehla'])) $h .= '<p class="kal-detail__stav drobne">' . e($stav) . '</p>';
    if ((string)$a['popis_html'] !== '') $h .= '<p class="kal-detail__vice"><a href="' . e((string)$a['detail_url']) . '">Více o akci</a></p>';
    return $h . '</article>';
}

/**
 * Formulář přihlášky k akci (akce.php). Pole podle akce_formular($a) – u každé akce jiná.
 * $hodnoty a $chyby z akce_formular_zpracuj() (znovuvyplnění po chybě).
 * Bez relace a bez cookies: verejny_formular_pole() = podepsaný token + past na roboty.
 */
function akce_formular_html(array $a, array $hodnoty = [], array $chyby = [], string $akceUrl = ''): string {
    $f = akce_formular($a);
    $id = 'pr-' . (int)$a['id'] . '-';
    $hod = fn(string $k, string $vych = '') => (string)($hodnoty[$k] ?? $vych);
    $chybaAttr = fn(string $k) => isset($chyby[$k]) ? ' aria-invalid="true" aria-describedby="' . e($id . $k . '-chyba') . '"' : '';
    $chybaText = fn(string $k) => isset($chyby[$k]) ? '<p class="pole__chyba" id="' . e($id . $k . '-chyba') . '">' . e($chyby[$k]) . '</p>' : '';
    $povinne = '<span class="povinne" aria-hidden="true">*</span>';

    $h = '<form class="formular" method="post" action="' . e($akceUrl !== '' ? $akceUrl : url('akce.php?id=' . (int)$a['id'])) . '#prihlaska" novalidate>';
    $h .= verejny_formular_pole('akce-' . (int)$a['id']);
    $h .= '<div class="formular-radek">';
    $h .= '<div class="pole' . (isset($chyby['jmeno']) ? ' pole--chyba' : '') . '"><label for="' . $id . 'jmeno">Jméno a příjmení' . $povinne . '</label>'
        . '<input type="text" id="' . $id . 'jmeno" name="jmeno" value="' . e($hod('jmeno')) . '" autocomplete="name" required maxlength="160"' . $chybaAttr('jmeno') . '>' . $chybaText('jmeno') . '</div>';
    $h .= '<div class="pole' . (isset($chyby['email']) ? ' pole--chyba' : '') . '"><label for="' . $id . 'email">E-mail' . $povinne . '</label>'
        . '<input type="email" id="' . $id . 'email" name="email" value="' . e($hod('email')) . '" autocomplete="email" inputmode="email" required maxlength="160"' . $chybaAttr('email') . '>' . $chybaText('email') . '</div>';
    $h .= '</div>';
    if ($f['pole']['telefon'] || $f['pole']['pocet']) {
        $h .= '<div class="formular-radek">';
        if ($f['pole']['telefon']) {
            $h .= '<div class="pole' . (isset($chyby['telefon']) ? ' pole--chyba' : '') . '"><label for="' . $id . 'telefon">Telefon' . ($f['povinne']['telefon'] ? $povinne : '') . '</label>'
                . '<input type="tel" id="' . $id . 'telefon" name="telefon" value="' . e($hod('telefon')) . '" autocomplete="tel" inputmode="tel" maxlength="40"' . ($f['povinne']['telefon'] ? ' required' : '') . $chybaAttr('telefon') . '>' . $chybaText('telefon') . '</div>';
        }
        if ($f['pole']['pocet']) {
            $h .= '<div class="pole' . (isset($chyby['pocet']) ? ' pole--chyba' : '') . '"><label for="' . $id . 'pocet">Počet osob</label>'
                . '<input type="number" id="' . $id . 'pocet" name="pocet" value="' . e($hod('pocet', '1')) . '" min="1" max="50" inputmode="numeric"' . $chybaAttr('pocet') . '>' . $chybaText('pocet') . '</div>';
        }
        $h .= '</div>';
    }
    foreach ($f['vlastni'] as $p) {
        $k = 'pole_' . $p['klic'];
        $pid = $id . $k;
        $req = $p['povinne'] ? ' required' : '';
        $h .= '<div class="pole' . (isset($chyby[$k]) ? ' pole--chyba' : '') . '">';
        if ($p['typ'] === 'zaskrtavatko') {
            $h .= '<label class="volba" for="' . e($pid) . '"><input type="checkbox" id="' . e($pid) . '" name="' . e($k) . '" value="1"' . ($hod($k) !== '' ? ' checked' : '') . $req . $chybaAttr($k) . '><span>' . typo($p['popisek']) . ($p['povinne'] ? $povinne : '') . '</span></label>';
        } elseif ($p['typ'] === 'vyber') {
            $h .= '<label for="' . e($pid) . '">' . typo($p['popisek']) . ($p['povinne'] ? $povinne : '') . '</label><select id="' . e($pid) . '" name="' . e($k) . '"' . $req . $chybaAttr($k) . '><option value="">Vyberte…</option>';
            foreach ($p['moznosti'] as $m) $h .= '<option value="' . e($m) . '"' . ($hod($k) === $m ? ' selected' : '') . '>' . e($m) . '</option>';
            $h .= '</select>';
        } else {
            $typ = $p['typ'] === 'cislo' ? 'text" inputmode="decimal' : 'text';
            $h .= '<label for="' . e($pid) . '">' . typo($p['popisek']) . ($p['povinne'] ? $povinne : '') . '</label><input type="' . $typ . '" id="' . e($pid) . '" name="' . e($k) . '" value="' . e($hod($k)) . '" maxlength="500"' . $req . $chybaAttr($k) . '>';
        }
        $h .= $chybaText($k) . '</div>';
    }
    if ($f['pole']['poznamka']) {
        $h .= '<div class="pole' . (isset($chyby['poznamka']) ? ' pole--chyba' : '') . '"><label for="' . $id . 'poznamka">Poznámka' . ($f['povinne']['poznamka'] ? $povinne : '') . '</label>'
            . '<textarea id="' . $id . 'poznamka" name="poznamka" rows="4" maxlength="2000"' . ($f['povinne']['poznamka'] ? ' required' : '') . $chybaAttr('poznamka') . '>' . e($hod('poznamka')) . '</textarea>' . $chybaText('poznamka') . '</div>';
    }
    $souhlas = trim((string)blok('formulare', 'souhlas')['perex']) ?: 'Souhlasím se zpracováním uvedených osobních údajů I. Českým Lawn-Tennis Klubem Praha za účelem vyřízení přihlášky.';
    $h .= '<div class="pole' . (isset($chyby['souhlas']) ? ' pole--chyba' : '') . '"><label class="volba" for="' . $id . 'souhlas"><input type="checkbox" id="' . $id . 'souhlas" name="souhlas" value="1"' . (!empty($hodnoty['souhlas']) ? ' checked' : '') . ' required' . $chybaAttr('souhlas') . '><span>' . typo($souhlas) . $povinne . '</span></label>' . $chybaText('souhlas') . '</div>';
    if ($f['poznamka'] !== '') $h .= '<p class="formular__pozn">' . typo($f['poznamka']) . '</p>';
    $h .= '<div class="formular__akce"><button class="btn" type="submit">' . typo($f['tlacitko']) . ' ' . sipka() . '</button>'
        . '<span class="formular__pozn"><span aria-hidden="true">*</span> povinné údaje</span></div>';
    return $h . '</form>';
}

/* ==================================================================
   REVUE – data pro kiosek (assets/js/kiosek.js)
   ================================================================== */

/** Čísla Revue ve tvaru, který čte kiosek.js: [{o, rok, c, obalka, titulky, obsah, stran, naklad, uzaverka, pdf, mb, img}, …] */
function revue_data_js(?array $cisla = null): array {
    $v = [];
    foreach ($cisla ?? revue_cisla() as $r) {
        $v[] = [
            'o'        => (string)$r['oznaceni'],
            'rok'      => (int)$r['rok'],
            'c'        => (int)$r['cislo'],
            'obalka'   => typo_text((string)$r['obalka_popis']),
            'titulky'  => array_values(array_map(fn($t) => typo_text((string)$t), (array)$r['titulky_pole'])),   // typografie (… –) i v kiosku
            'obsah'    => array_values(array_map(fn($x) => [(string)($x[0] ?? ''), typo_text((string)($x[1] ?? ''))], array_filter((array)$r['obsah_pole'], 'is_array'))),
            'stran'    => (int)$r['stran'] ?: null,
            'naklad'   => (string)$r['naklad'],
            'uzaverka' => (string)$r['uzaverka'],
            'pdf'      => (string)$r['pdf'],
            'mb'       => $r['pdf_mb'] !== null && $r['pdf_mb'] !== '' ? (float)$r['pdf_mb'] : null,
            'img'      => upload_url((string)$r['obalka']),
        ];
    }
    return $v;
}

/* ==================================================================
   STRÁNKA NENALEZENA – sdílí 404.php a akce.php (neexistující akce)
   ================================================================== */

/** Vypíše celou stránku 404 (s hlavičkou a patičkou webu) a skončí. */
function stranka_nenalezena(string $nadpis = '', string $perex = ''): never {
    if (!headers_sent()) http_response_code(404);
    $b = blok('404', 'uvod');
    if ($nadpis !== '') { $b['nadpis'] = $nadpis; $b['existuje'] = true; }
    if ($perex !== '') $b['perex'] = $perex;
    if (!$b['existuje'] && trim((string)$b['nadpis']) === '') {
        $b = array_merge($b, ['stitek' => 'Chyba 404', 'nadpis' => 'Tuhle stránku jsme <em>nenašli</em>.',
            'perex' => 'Adresa se mohla změnit nebo stránka už neexistuje. Zkuste úvodní stránku nebo menu nahoře.',
            'odkaz' => 'index.php', 'odkaz_text' => 'Na úvodní stránku', 'doplni_klub' => 0]);
    }
    $b['doplni_klub'] = 0;
    $sablona = ['titulek' => html_text((string)$b['nadpis']) ?: 'Stránka nenalezena', 'popis' => '', 'noindex' => true, 'trida' => 'stranka-404'];
    require __DIR__ . '/hlavicka.php';
    echo hlava_stranky($b, ['drobky' => false, 'trida' => 'hlava-stranky--404']);
    echo '<section class="sekce sekce--tesna sekce--papir2" aria-label="Kam dál"><div class="wrap">'
       . '<p class="stitek" style="margin-bottom:1.2rem">Kam dál</p>'
       . pilulky_html(array_merge(
             array_map(fn($m) => [$m['nazev'], $m['url']], array_filter(menu_hlavni(), fn($m) => !$m['externi'])),
             [['Kontakt', 'kontakt.php']]))
       . '</div></section>';
    require __DIR__ . '/paticka.php';
    exit;
}
