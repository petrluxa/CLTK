<?php
/* Bezpečné HTML.

   1) html_ocistit() – text z editoru v administraci (bloky stránek, popisy akcí).
      HTML se rozebere na strom a SESTAVÍ ZNOVU: každá povolená značka se
      vypíše holá (<p>, <strong>…), bez jediného atributu z původního textu.
      Jen u odkazu se doplní ověřená adresa a u obrázku cesta do uploads/.
      (strip_tags() nestačí – nechává atributy, takže by prošlo <p onclick=…>.)
      Čistí se při ukládání i při výpisu.

   2) html_inline() – krátké texty s kurzívou (nadpisy „Členem se může stát
      <em>každý</em>.“, popisky galerie). Všechno se escapuje a zpět se
      vrátí jen <em>, <strong>, <br> a &nbsp;. */

/** Značky, které smí zůstat v textu z editoru (vypisují se holé).
 *  Tabulky (ceník v dokumentu) bez atributů – colspan/rowspan se zahodí. Pro výpis je
 *  zabalí do posuvného obalu html_tabulky_obal() (blok_text(), dokument.php). */
const HTML_POVOLENE = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h2', 'h3', 'h4',
                       'ul', 'ol', 'li', 'blockquote', 'figure', 'figcaption', 'a', 'img', 'hr',
                       'table', 'caption', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td'];

/** Značky, které se zahodí i s obsahem. */
const HTML_ZAHODIT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
                      'select', 'textarea', 'svg', 'math', 'noscript', 'template', 'head', 'title',
                      'meta', 'link', 'base', 'frame', 'frameset', 'applet', 'video', 'audio', 'canvas'];

/** Prázdné značky (bez uzavírací). */
const HTML_PRAZDNE = ['br', 'img', 'hr'];

function html_ocistit(?string $html): string {
    $html = trim((string)$html);
    if ($html === '') return '';

    $doc = new DOMDocument('1.0', 'UTF-8');
    $staryStav = libxml_use_internal_errors(true);
    $ok = $doc->loadHTML(
        '<?xml encoding="UTF-8"><!DOCTYPE html><html><body><div id="cltk-obal">' . $html . '</div></body></html>',
        LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
    );
    libxml_clear_errors();
    libxml_use_internal_errors($staryStav);
    if (!$ok) return '';

    $obal = $doc->getElementById('cltk-obal');
    if (!$obal) return '';

    $vystup = '';
    foreach ($obal->childNodes as $dite) {
        $vystup .= html_sestav_uzel($dite, 0);
    }
    // prázdné odstavce z editoru pryč
    $vystup = preg_replace('~<p>(\s|&nbsp;|<br>)*</p>~u', '', $vystup);
    return trim((string)$vystup);
}

/** Text z editoru K ULOŽENÍ do databáze: html_ocistit() a adresy webu bez BASE_PATH
 *  („/cltkv2/clenstvi.php“ → „clenstvi.php“). Cestu webu doplní html_ocistit() až při
 *  výpisu, takže odkazy přežijí přestěhování webu z /cltkv2/ do kořene cltk.cz. */
function html_k_ulozeni(?string $html): string {
    $h = html_ocistit($html);
    if (BASE_PATH === '/' || BASE_PATH === '') {
        return (string)preg_replace('~(href|src)="/(?!/)~', '$1="', $h);
    }
    return str_replace(['href="' . e(BASE_PATH), 'src="' . e(BASE_PATH)], ['href="', 'src="'], $h);
}

/** Sestaví HTML z jednoho uzlu – rekurzivně, jen z povolených značek. */
function html_sestav_uzel(DOMNode $uzel, int $hloubka): string {
    if ($hloubka > 40) return '';                         // pojistka proti absurdnímu zanoření

    if ($uzel->nodeType === XML_TEXT_NODE || $uzel->nodeType === XML_CDATA_SECTION_NODE) {
        return str_replace("\u{00A0}", '&nbsp;', e(typo_text($uzel->nodeValue)));
    }
    if ($uzel->nodeType !== XML_ELEMENT_NODE) {
        return '';                                        // komentáře, instrukce… pryč
    }

    $znacka = strtolower($uzel->nodeName);
    if (in_array($znacka, HTML_ZAHODIT, true)) return '';

    $vnitrek = '';
    foreach ($uzel->childNodes as $dite) {
        $vnitrek .= html_sestav_uzel($dite, $hloubka + 1);
    }

    if (!in_array($znacka, HTML_POVOLENE, true)) {
        // nepovolená značka (span, div, font…) zmizí, text uvnitř zůstane;
        // blokové obaly dostanou aspoň mezeru, ať se slova neslepí
        return in_array($znacka, ['div', 'section', 'article', 'h1', 'h5', 'h6', 'pre'], true)
            ? ($znacka === 'h1' || $znacka === 'h5' || $znacka === 'h6' ? '<h3>' . $vnitrek . '</h3>' : $vnitrek . ' ')
            : $vnitrek;
    }

    // sjednocení: <b> → <strong>, <i> → <em>
    if ($znacka === 'b') $znacka = 'strong';
    if ($znacka === 'i') $znacka = 'em';

    /** @var DOMElement $uzel */
    if ($znacka === 'a') {
        $href = html_adresa_odkazu((string)$uzel->getAttribute('href'));
        if ($href === '') return $vnitrek;                // odkaz bez bezpečné adresy = jen text
        $ven = preg_match('~^https?://~i', $href) ? ' target="_blank" rel="noopener"' : '';
        return '<a href="' . e($href) . '"' . $ven . '>' . $vnitrek . '</a>';
    }
    if ($znacka === 'img') {
        $src = html_adresa_obrazku((string)$uzel->getAttribute('src'));
        if ($src === '') return '';
        $alt = mb_substr(trim((string)$uzel->getAttribute('alt')), 0, 200);
        return '<img src="' . e($src) . '" alt="' . e($alt) . '" loading="lazy">';
    }
    if (in_array($znacka, HTML_PRAZDNE, true)) {
        return '<' . $znacka . '>';
    }
    return '<' . $znacka . '>' . $vnitrek . '</' . $znacka . '>';
}

/** Adresa odkazu z editoru: http(s), mailto, tel, #kotva nebo stránka webu. */
function html_adresa_odkazu(string $url): string {
    $u = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $holy = (string)preg_replace('/[\x00-\x20\x7F]+/', '', $u);
    if ($holy === '' || preg_match('~^(javascript|data|vbscript|file):~i', $holy)) return '';
    if (preg_match('~^(https?://|mailto:|tel:)~i', $holy)) return $holy;
    if (str_starts_with($holy, '#')) return $holy;
    // stránka webu – relativně (bez lomítka na začátku, web běží v podsložce)
    $rel = html_vnitrni_cesta($holy);
    if (preg_match('~^[a-z0-9][a-z0-9\-_/]*\.php([?#][^\s"<>]*)?$~i', $rel)) return url($rel);
    if (preg_match('~^uploads/[a-z0-9\-_/.]+$~i', $rel)) return url($rel);
    return '';
}

/**
 * Adresa uvnitř webu bez základu: „/cltkv2/historie.php#x“ → „historie.php#x“.
 * Text z editoru se ukládá i vypisuje přes html_ocistit(), takže v databázi
 * zůstane odkaz s tehdejším BASE_PATH (např. /cltkv2/ na testu). Po přestěhování
 * webu do kořene cltk.cz by takový odkaz vedl do neexistující podsložky – proto
 * se kromě aktuálního základu odřízne i jakákoli úvodní složka, za kterou
 * následuje existující stránka webu nebo uploads/.
 */
function html_vnitrni_cesta(string $adresa): string {
    $rel = ltrim($adresa, '/');
    $zaklad = ltrim(BASE_PATH, '/');
    if ($zaklad !== '' && str_starts_with($rel, $zaklad)) $rel = substr($rel, strlen($zaklad));
    if (preg_match('~^(?:[a-z0-9\-_]+/)+(uploads/.+)$~i', $rel, $m)) return $m[1];
    $soubor = (string)preg_replace('~[?#].*$~', '', $rel);
    if (str_contains($soubor, '..') || !preg_match('~^[a-z0-9\-_/]+\.php$~i', $soubor) || is_file(WEB_ROOT . '/' . $soubor)) return $rel;
    $zbytek = $soubor;
    while (preg_match('~^[a-z0-9\-_]+/(.+)$~i', $zbytek, $m)) {      // odřezávat úvodní složky, dokud nevyjde stránka webu
        $zbytek = $m[1];
        if (is_file(WEB_ROOT . '/' . $zbytek)) return substr($rel, strlen($soubor) - strlen($zbytek));
    }
    return $rel;
}

/** Obrázek v textu jen z vlastních uploads/ – cizí adresy by prozrazovaly návštěvníky. */
function html_adresa_obrazku(string $src): string {
    $s = html_vnitrni_cesta(trim(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if (!preg_match('~^uploads/([a-z0-9\-_/]+\.(jpe?g|png|webp|gif))$~i', $s, $m)) return '';
    if (str_contains($m[1], '..')) return '';
    return upload_url($m[1]);
}

/**
 * Krátký text s kurzívou: escapuje vše a vrátí jen <em>, <strong>, <br> a &nbsp;.
 * Použití: <h2><?= html_inline($blok['nadpis']) ?></h2>
 */
function html_inline(?string $s): string {
    $t = e(typo_text(str_replace('&nbsp;', "\u{00A0}", (string)$s)));
    $t = str_replace(['&lt;em&gt;', '&lt;/em&gt;', '&lt;strong&gt;', '&lt;/strong&gt;',
                      '&lt;i&gt;', '&lt;/i&gt;', '&lt;b&gt;', '&lt;/b&gt;'],
                     ['<em>', '</em>', '<strong>', '</strong>', '<em>', '</em>', '<strong>', '</strong>'], $t);
    $t = preg_replace('~&lt;br\s*/?&gt;~i', '<br>', $t);
    $t = str_replace(['&amp;nbsp;', "\u{00A0}"], '&nbsp;', $t);
    // neuzavřené značky uzavřít, ať kurzíva nepřeteče do zbytku stránky
    foreach (['em', 'strong'] as $z) {
        $rozdil = substr_count($t, '<' . $z . '>') - substr_count($t, '</' . $z . '>');
        if ($rozdil > 0) $t .= str_repeat('</' . $z . '>', $rozdil);
        if ($rozdil < 0) $t = preg_replace('~</' . $z . '>~', '', $t, -$rozdil);
    }
    return $t;
}

/** Tabulky v už vyčištěném HTML (html_ocistit) do posuvného obalu webu – jen pro VÝPIS,
 *  v databázi zůstávají holé značky. Na mobilu se široká tabulka posouvá do strany. */
function html_tabulky_obal(string $html): string {
    if (!str_contains($html, '<table>')) return $html;
    return str_replace(['<table>', '</table>'], ['<div class="tabulka-box"><table class="tabulka">', '</table></div>'], $html);
}

/** Krátký text bez značek (pro meta description, title, aria-label). */
function html_text(?string $html): string {
    $t = preg_replace('~</(p|h2|h3|h4|li|blockquote|figcaption|caption|th|td)>|<br\s*/?>~i', ' ', (string)$html);
    $t = html_entity_decode(strip_tags((string)$t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string)preg_replace('/\s+/u', ' ', $t));
}

/** Úryvek textu na $delka znaků, zakončený „…“. */
function uryvek(?string $text, int $delka = 180): string {
    $t = html_text($text);
    if (mb_strlen($t) <= $delka) return $t;
    $rez = mb_substr($t, 0, $delka);
    $mezera = mb_strrpos($rez, ' ');
    if ($mezera !== false && $mezera > $delka * 0.6) $rez = mb_substr($rez, 0, $mezera);
    return rtrim($rez, ' ,.;:–') . '…';
}
