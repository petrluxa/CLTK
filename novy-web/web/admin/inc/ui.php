<?php
/* Pomůcky pro stránky administrace (formulářová pole, tlačítka řádků,
   zpracování obrázků a PDF, pořadí, CSV). Načítá je inc/layout.php.
   Návod s příklady: admin/PRUVODCE.md.

   Pravidla, která pomůcky hlídají za vás:
   - šipky pořadí, přepínač zobrazení a mazání mají VLASTNÍ formulář
     (Enter v poli formuláře řádku pak spustí jen Uložit),
   - každý formulář dostane CSRF token,
   - starý soubor se maže až po zápisu do databáze (admin_obrazek vrací
     seznam ke smazání, smaže ho admin_smazat_soubory() po UPDATE). */

/* ==================================================================
   FORMULÁŘOVÁ POLE – vracejí HTML, vypisujte přes echo
   Společné volby $o: hint, required, placeholder, maxlength, id,
   sirka ('cela' = přes celou šířku mřížky), attrs (pole dalších atributů)
   ================================================================== */

function ui_id(string $name, array $o): string {
    return (string)($o['id'] ?? ('f-' . preg_replace('/[^a-z0-9_-]+/i', '-', $name)));
}

function ui_attrs(array $attrs): string {
    $s = '';
    foreach ($attrs as $k => $v) {
        if ($v === null || $v === false) continue;
        $s .= $v === true ? ' ' . e((string)$k) : ' ' . e((string)$k) . '="' . e((string)$v) . '"';
    }
    return $s;
}

function ui_obal(string $id, string $label, string $vnitrek, array $o): string {
    $tridy = 'field' . (($o['sirka'] ?? '') === 'cela' ? ' field--cela' : '');
    $povinne = !empty($o['required']) ? ' <span class="povinne" aria-hidden="true">*</span>' : '';
    $hint = isset($o['hint']) && $o['hint'] !== '' ? '<div class="hint" id="' . e($id) . '-hint">' . $o['hint'] . '</div>' : '';
    return '<div class="' . $tridy . '"><label for="' . e($id) . '">' . e($label) . $povinne . '</label>' . $vnitrek . $hint . '</div>';
}

/** Textové pole. $o['type'] = text | email | url | tel | number | password | date | time. Hint smí obsahovat HTML (je váš). */
function pole_text(string $name, string $label, $value = '', array $o = []): string {
    $id = ui_id($name, $o);
    $a = array_merge([
        'type' => $o['type'] ?? 'text', 'id' => $id, 'name' => $name, 'value' => (string)$value,
        'required' => !empty($o['required']), 'placeholder' => $o['placeholder'] ?? null,
        'maxlength' => $o['maxlength'] ?? (($o['type'] ?? 'text') === 'text' ? 255 : null),
        'autocomplete' => $o['autocomplete'] ?? null,
        'aria-describedby' => isset($o['hint']) && $o['hint'] !== '' ? $id . '-hint' : null,
    ], $o['attrs'] ?? []);
    return ui_obal($id, $label, '<input' . ui_attrs($a) . '>', $o);
}

/** Datum: type=date; když prohlížeč kalendář nenabídne, projde i „5. 9. 2026“ (normalizuj_datum na serveru). */
function pole_datum(string $name, string $label, ?string $ymd, array $o = []): string {
    $o['type'] = 'date';
    $o['hint'] = $o['hint'] ?? '';
    $o['attrs'] = array_merge(['pattern' => '\d{4}-\d{2}-\d{2}|\d{1,2}\.\s*\d{1,2}\.\s*\d{4}', 'inputmode' => 'numeric'], $o['attrs'] ?? []);
    return pole_text($name, $label, $ymd ? substr($ymd, 0, 10) : '', $o);
}

/** Víceřádkové pole. $o['rows'], $o['vysoke'] = true pro dlouhé texty. */
function pole_textarea(string $name, string $label, $value = '', array $o = []): string {
    $id = ui_id($name, $o);
    $a = array_merge([
        'id' => $id, 'name' => $name, 'rows' => $o['rows'] ?? 4, 'required' => !empty($o['required']),
        'placeholder' => $o['placeholder'] ?? null, 'maxlength' => $o['maxlength'] ?? null,
        'class' => !empty($o['vysoke']) ? 'tall' : null,
        'aria-describedby' => isset($o['hint']) && $o['hint'] !== '' ? $id . '-hint' : null,
    ], $o['attrs'] ?? []);
    return ui_obal($id, $label, '<textarea' . ui_attrs($a) . '>' . e((string)$value) . '</textarea>', $o);
}

/**
 * Textový editor (tučné, kurzíva, nadpisy, seznamy, odkaz, citace).
 * Bez JavaScriptu zůstane obyčejné textové pole s HTML. Na serveru vždy:
 *   $html = html_k_ulozeni(vstup('pole', 0));   // vyčistí + odkazy bez BASE_PATH
 */
function pole_editor(string $name, string $label, ?string $html, array $o = []): string {
    $id = ui_id($name, $o);
    $o['sirka'] = $o['sirka'] ?? 'cela';
    $vnitrek = '<textarea id="' . e($id) . '" name="' . e($name) . '" rows="10" class="tall" data-editor'
             . (isset($o['hint']) && $o['hint'] !== '' ? ' aria-describedby="' . e($id) . '-hint"' : '') . '>'
             . e(html_ocistit((string)$html)) . '</textarea>';
    return ui_obal($id, $label, $vnitrek, $o);
}

/** Výběr z možností: $moznosti = [hodnota => popisek]. */
function pole_select(string $name, string $label, $value, array $moznosti, array $o = []): string {
    $id = ui_id($name, $o);
    $h = '<select' . ui_attrs(array_merge(['id' => $id, 'name' => $name, 'required' => !empty($o['required'])], $o['attrs'] ?? [])) . '>';
    foreach ($moznosti as $k => $v) {
        $h .= '<option value="' . e((string)$k) . '"' . ((string)$k === (string)$value ? ' selected' : '') . '>' . e((string)$v) . '</option>';
    }
    return ui_obal($id, $label, $h . '</select>', $o);
}

/** Zaškrtávátko (posílá 1). Čtěte vstup_bool('name'). */
function pole_check(string $name, string $label, bool $checked, array $o = []): string {
    $id = ui_id($name, $o);
    $hint = isset($o['hint']) && $o['hint'] !== '' ? '<div class="hint">' . $o['hint'] . '</div>' : '';
    return '<div class="field' . (($o['sirka'] ?? '') === 'cela' ? ' field--cela' : '') . '"><label class="check" for="' . e($id) . '">'
         . '<input type="checkbox" id="' . e($id) . '" name="' . e($name) . '" value="1"' . ($checked ? ' checked' : '')
         . ui_attrs($o['attrs'] ?? []) . '><span>' . e($label) . '</span></label>' . $hint . '</div>';
}

/**
 * Obrázek: náhled současného, výběr nového, zaškrtávátko „odebrat“.
 * Zpracování: admin_obrazek('foto', $stary, 'aktuality').
 * $o['nahled'] = 'siroky' (výchozí) | 'ctverec' | 'logo'; $o['odebrat'] = false skryje odebrání.
 */
function pole_obrazek(string $name, string $label, ?string $rel, array $o = []): string {
    $id = ui_id($name, $o);
    $rel = (string)$rel;
    $typ = $o['nahled'] ?? 'siroky';
    $h = '<div class="obr-pole obr-pole--' . e($typ) . '" data-obr-pole>';
    if ($rel !== '' && is_file(UPLOAD_DIR . '/' . $rel)) {
        $h .= '<div class="obr-prev"><img src="' . e(upload_url($rel)) . '" alt="Současný obrázek" data-obr-nahled>';
        if (($o['odebrat'] ?? true) !== false) {
            $h .= '<label class="check"><input type="checkbox" name="' . e($name) . '_odebrat" value="1"><span>Odebrat obrázek</span></label>';
        }
        $h .= '</div>';
    } elseif ($rel !== '') {
        $h .= '<div class="hint hint--varovani">Uložený soubor <b>' . e($rel) . '</b> na serveru chybí. Nahrajte ho znovu.</div>'
            . '<div class="obr-prev" hidden><img src="" alt="" data-obr-nahled></div>';
    } else {
        $h .= '<div class="obr-prev" hidden><img src="" alt="Náhled vybraného obrázku" data-obr-nahled></div>';
    }
    $h .= '<input type="file" id="' . e($id) . '" name="' . e($name) . '" accept="image/jpeg,image/png,image/webp,image/gif" data-obr-vstup></div>';
    $o['hint'] = $o['hint'] ?? 'JPG, PNG nebo WEBP. Velikost a natočení se upraví automaticky.';
    return ui_obal($id, $label, $h, $o);
}

/**
 * Ohnisko fotky (object-position): klepnutím do náhledu se nastaví „x% y%“.
 * $foto = současný obrázek (relativní cesta) – bez něj jen textové pole.
 */
function pole_fokus(string $name, string $label, ?string $value, ?string $foto, array $o = []): string {
    $id = ui_id($name, $o);
    $v = trim((string)$value) !== '' ? (string)$value : '50% 50%';
    $h = '<input type="text" id="' . e($id) . '" name="' . e($name) . '" value="' . e($v) . '" maxlength="20" pattern="\d{1,3}(\.\d+)?%\s+\d{1,3}(\.\d+)?%" data-fokus-vstup>';
    if ($foto && is_file(UPLOAD_DIR . '/' . $foto)) {
        $h = '<div class="fokus" data-fokus><div class="fokus__obraz"><img src="' . e(upload_url($foto)) . '" alt="Klepněte do fotky na místo, které má zůstat vidět">'
           . '<span class="fokus__bod" aria-hidden="true"></span></div>' . $h . '</div>';
    }
    $o['hint'] = $o['hint'] ?? 'Klepněte do fotky na místo, které má zůstat vidět i při ořezu (např. obličej). Zapisuje se jako „vodorovně% svisle%“.';
    return ui_obal($id, $label, $h, $o);
}

/** PDF: odkaz na současný soubor, výběr nového, odebrání. Zpracování: admin_pdf('soubor', $stary). */
function pole_pdf(string $name, string $label, ?string $rel, ?string $nazev = '', array $o = []): string {
    $id = ui_id($name, $o);
    $rel = (string)$rel;
    $h = '';
    if ($rel !== '') {
        $h .= '<div class="doc-prev"><a href="' . e(upload_url($rel)) . '" target="_blank" rel="noopener">' . e($nazev ?: basename($rel)) . '</a>'
            . '<label class="check"><input type="checkbox" name="' . e($name) . '_odebrat" value="1"><span>Odebrat soubor</span></label></div>';
    }
    // data-max-bajtu: admin.js upozorní na příliš velký soubor hned po výběru (ne až po dlouhém nahrávání)
    $limit = upload_limit_bajtu();
    $h .= '<input type="file" id="' . e($id) . '" name="' . e($name) . '" accept="application/pdf,.pdf" data-max-bajtu="' . $limit . '"'
        . ' data-max-text="' . e(velikost_text($limit)) . '">';
    $o['hint'] = $o['hint'] ?? 'Jen PDF, nejvýš ' . e(velikost_text($limit)) . '.';
    return ui_obal($id, $label, $h, $o);
}

/** Mřížka polí vedle sebe (na mobilu pod sebou). */
function pole_radek(array $pole, int $sloupcu = 2): string {
    return '<div class="row' . max(1, min(4, $sloupcu)) . '">' . implode('', $pole) . '</div>';
}

/** Tlačítka pod formulářem: Uložit (jediné odesílací) + volitelně odkaz Zpět. */
function tlacitka_formulare(string $ulozit = 'Uložit', ?string $zpet = null, string $zpetText = 'Zpět'): string {
    return '<div class="form-actions"><button type="submit" class="btn btn-primary">' . e($ulozit) . '</button>'
         . ($zpet !== null ? '<a class="btn btn-ghost" href="' . e($zpet) . '">' . e($zpetText) . '</a>' : '') . '</div>';
}

/* ==================================================================
   ŠTÍTKY A TLAČÍTKA ŘÁDKŮ – každé ve vlastním formuláři
   ================================================================== */

/** Štítek: druh ok | off | warn | info | navy | zlato | err. */
function badge(string $text, string $druh = 'ok'): string {
    return '<span class="badge ' . e($druh) . '">' . e($text) . '</span>';
}

/** Zobrazeno / Skryto. */
function stav_badge($visible, string $ano = 'Zobrazeno', string $ne = 'Skryto'): string {
    return (int)$visible === 1 ? badge($ano, 'ok') : badge($ne, 'off');
}

/** Skryté vstupy pro formulář akce. */
function ui_skryte(array $data): string {
    $h = '';
    foreach ($data as $k => $v) {
        $h .= '<input type="hidden" name="' . e((string)$k) . '" value="' . e((string)$v) . '">';
    }
    return $h;
}

/** Malý samostatný formulář s jedním tlačítkem (POST + CSRF). $potvrdit = text dotazu před odesláním. */
function tlacitko_akce(string $action, int $id, string $text, string $trida = 'btn-ghost', array $extra = [], string $potvrdit = '', string $titulek = ''): string {
    return '<form method="post" class="form-inline"' . ($potvrdit !== '' ? ' data-potvrdit="' . e($potvrdit) . '"' : '') . '>'
         . csrf_field() . ui_skryte(array_merge(['action' => $action, 'id' => $id], $extra))
         . '<button type="submit" class="btn btn-sm ' . e($trida) . '"' . ($titulek !== '' ? ' title="' . e($titulek) . '" aria-label="' . e($titulek) . '"' : '') . '>' . $text . '</button></form>';
}

/** Přepínač zobrazení (action = prepnout). */
function tlacitko_prepnout(int $id, $visible, array $extra = []): string {
    return (int)$visible === 1
        ? tlacitko_akce('prepnout', $id, 'Skrýt', 'btn-ghost', $extra, '', 'Skrýt na webu')
        : tlacitko_akce('prepnout', $id, 'Zobrazit', 'btn-ghost', $extra, '', 'Zobrazit na webu');
}

/** Šipky pořadí (action = nahoru / dolu), každá ve vlastním formuláři. */
function tlacitka_poradi(int $id, bool $prvni, bool $posledni, array $extra = []): string {
    $nahoru = $prvni ? '<span class="btn btn-sm btn-ghost is-disabled" aria-hidden="true">↑</span>'
                     : tlacitko_akce('nahoru', $id, '↑', 'btn-ghost', $extra, '', 'Posunout výš');
    $dolu = $posledni ? '<span class="btn btn-sm btn-ghost is-disabled" aria-hidden="true">↓</span>'
                      : tlacitko_akce('dolu', $id, '↓', 'btn-ghost', $extra, '', 'Posunout níž');
    return '<span class="poradi">' . $nahoru . $dolu . '</span>';
}

/** Mazání s potvrzením (action = smazat). */
function tlacitko_smazat(int $id, string $otazka = 'Opravdu smazat? Nejde to vrátit.', array $extra = [], string $text = 'Smazat'): string {
    return tlacitko_akce('smazat', $id, e($text), 'btn-danger', $extra, $otazka);
}

/** Prázdný stav panelu. */
function prazdny_stav(string $nadpis, string $text = '', string $akce = ''): string {
    return '<div class="empty"><b>' . e($nadpis) . '</b>' . ($text !== '' ? '<p>' . $text . '</p>' : '') . $akce . '</div>';
}

/** Záložky nad obsahem stránky: [url => popisek], $aktivni = url aktivní záložky. */
function admin_zalozky(array $polozky, string $aktivni): string {
    $h = '<nav class="zalozky" aria-label="Části modulu">';
    foreach ($polozky as $u => $t) {
        $h .= '<a href="' . e((string)$u) . '"' . ((string)$u === $aktivni ? ' aria-current="page"' : '') . '>' . e((string)$t) . '</a>';
    }
    return $h . '</nav>';
}

/* ==================================================================
   ZPRACOVÁNÍ POST
   ================================================================== */

/** Začátek zpracování POST: hlídá příliš velký soubor (PHP pak zahodí celý požadavek i token) a CSRF. */
function admin_post_zacatek(string $zpet): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
    if (!$_POST && !$_FILES && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $max = ini_bajty((string)ini_get('post_max_size'));
        $zprava = 'Soubor je příliš velký – server přijme najednou nejvýš ' . ($max > 0 ? velikost_text($max) : ini_get('post_max_size') . 'B')
            . '. Zmenšete ho (PDF v Acrobatu „Uložit jako jiný → Zmenšený soubor PDF“, z InDesignu export „pro web“) a zkuste to znovu.';
        if (headers_sent()) {
            // PHP s display_startup_errors vypíše varování o velikosti ještě před skriptem – přesměrovat už nejde
            echo '<p>' . e($zprava) . ' <a href="' . e($zpet) . '">Zpět</a></p>';
            exit;
        }
        redirect($zpet, $zprava, 'err');
    }
    csrf_check();
}

/** Další číslo pořadí (na konec seznamu, volitelně v rámci skupiny). */
function admin_dalsi_poradi(string $tabulka, ?string $sloupec = null, $hodnota = null): int {
    $t = cltk_tabulka($tabulka);
    if ($sloupec !== null) {
        if (!preg_match('/^[a-z_]+$/', $sloupec)) throw new RuntimeException('Neplatný sloupec');
        return (int)val("SELECT COALESCE(MAX(poradi), -1) + 1 FROM $t WHERE $sloupec = ?", [$hodnota]);
    }
    return (int)val("SELECT COALESCE(MAX(poradi), -1) + 1 FROM $t");
}

/** Posune řádek o místo výš (-1) nebo níž (+1) a srovná pořadí do řady 0, 1, 2…
 *  $skupina = sloupec, v jehož rámci se řadí (např. 'zarazeni'), $kde = další podmínka (SQL, parametry). */
function admin_posun(string $tabulka, int $id, int $smer, ?string $skupina = null, string $kde = '', array $kdeP = []): void {
    $t = cltk_tabulka($tabulka);
    $sql = "SELECT id FROM $t";
    $p = [];
    $podm = [];
    if ($skupina !== null) {
        if (!preg_match('/^[a-z_]+$/', $skupina)) throw new RuntimeException('Neplatný sloupec');
        $podm[] = "$skupina = (SELECT $skupina FROM $t WHERE id = ?)";
        $p[] = $id;
    }
    if ($kde !== '') { $podm[] = '(' . $kde . ')'; $p = array_merge($p, $kdeP); }
    if ($podm) $sql .= ' WHERE ' . implode(' AND ', $podm);
    $ids = array_map('intval', array_column(rows($sql . ' ORDER BY poradi, id', $p), 'id'));
    $i = array_search($id, $ids, true);
    if ($i === false) return;
    $j = $i + ($smer < 0 ? -1 : 1);
    if ($j < 0 || $j >= count($ids)) return;
    [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
    foreach ($ids as $pos => $rid) {
        q("UPDATE $t SET poradi = ? WHERE id = ?", [$pos, $rid]);
    }
}

/** Přepne visible 0 ↔ 1. Vrací nový stav. */
function admin_prepni(string $tabulka, int $id): int {
    $t = cltk_tabulka($tabulka);
    $v = (int)val("SELECT visible FROM $t WHERE id = ?", [$id]);
    $novy = $v === 1 ? 0 : 1;
    q("UPDATE $t SET visible = ? WHERE id = ?", [$novy, $id]);
    return $novy;
}

/**
 * Zpracuje pole_obrazek(): nové nahrání, nebo odebrání.
 * Vrací ['soubor' => cesta pro DB, 'chyba' => '' / text, 'smazat' => [staré soubory]].
 * Staré soubory smažte AŽ PO úspěšném zápisu do DB: admin_smazat_soubory($r['smazat']).
 */
function admin_obrazek(string $pole, ?string $stary, string $podslozka, int $maxW = 2400, int $maxH = 2400, string $jmeno = ''): array {
    $stary = (string)$stary;
    $f = $_FILES[$pole] ?? null;
    if (is_array($f) && (int)($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $up = upload_image($f, $podslozka, $maxW, $maxH, $jmeno);
        if (!$up['ok']) return ['soubor' => $stary, 'chyba' => $up['error'], 'smazat' => [], 'w' => 0, 'h' => 0];
        return ['soubor' => $up['file'], 'chyba' => '', 'smazat' => $stary !== '' ? [$stary] : [], 'w' => $up['w'], 'h' => $up['h']];
    }
    if (!empty($_POST[$pole . '_odebrat']) && $stary !== '') {
        return ['soubor' => '', 'chyba' => '', 'smazat' => [$stary], 'w' => 0, 'h' => 0];
    }
    [$w, $h] = obrazek_rozmer($stary);
    return ['soubor' => $stary, 'chyba' => '', 'smazat' => [], 'w' => $w, 'h' => $h];
}

/** Zpracuje pole_pdf(). Vrací ['soubor', 'nazev', 'chyba', 'smazat']. */
function admin_pdf(string $pole, ?string $stary, ?string $staryNazev = '', string $podslozka = 'dokumenty'): array {
    $stary = (string)$stary;
    $f = $_FILES[$pole] ?? null;
    if (is_array($f) && (int)($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $up = upload_document($f, $podslozka);
        if (!$up['ok']) return ['soubor' => $stary, 'nazev' => (string)$staryNazev, 'chyba' => $up['error'], 'smazat' => []];
        return ['soubor' => $up['file'], 'nazev' => $up['name'], 'chyba' => '', 'smazat' => $stary !== '' ? [$stary] : []];
    }
    if (!empty($_POST[$pole . '_odebrat']) && $stary !== '') {
        return ['soubor' => '', 'nazev' => '', 'chyba' => '', 'smazat' => [$stary]];
    }
    return ['soubor' => $stary, 'nazev' => (string)$staryNazev, 'chyba' => '', 'smazat' => []];
}

/** Smaže soubory z uploads/ – volat až po úspěšném zápisu do databáze. */
function admin_smazat_soubory(array $soubory): void {
    foreach ($soubory as $s) delete_upload((string)$s);
}

/**
 * Pošle CSV ke stažení (Excel: UTF-8 s BOM, středník, CRLF) a skončí.
 * Hodnoty začínající = + - @ dostanou apostrof – ochrana před vzorci v Excelu.
 */
function csv_export(string $soubor, array $hlavicka, array $radky): never {
    $soubor = preg_replace('/[^a-z0-9\-_.]/i', '-', $soubor);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $soubor . '"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $bezpecne = static function ($v): string {
        $v = (string)$v;
        return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
    };
    fputcsv($out, array_map($bezpecne, $hlavicka), ';', '"', '', "\r\n");
    foreach ($radky as $r) {
        fputcsv($out, array_map($bezpecne, array_values($r)), ';', '"', '', "\r\n");
    }
    fclose($out);
    exit;
}

/** Datum a čas po česku pro tabulky: 1. 10. 2026 14:05 */
function cz_datum_cas(?string $dt): string {
    if (!$dt) return '';
    $t = strtotime($dt);
    return $t ? date('j. n. Y G:i', $t) : '';
}

/** Číslo po česku (kompatibilita s Libercem). */
function fmt_num($n): string {
    return number_format((float)$n, 0, ',', ' ');
}
