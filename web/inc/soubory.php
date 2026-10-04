<?php
/* Soubory: nahrávání a úprava obrázků, PDF, WebP kopie, loga partnerů.

   Každý nahraný obrázek se přes GD načte a ULOŽÍ ZNOVU – tím se zahodí
   cokoli, co v souboru nemá co dělat (skript přilepený za obrázek,
   EXIF s GPS…). Fotky se zmenší na rozumnou velikost a vedle originálu
   vznikne úspornější WebP kopie (stránka ji nabídne přes <picture>).

   Pořadí při ukládání formuláře (past z Liberce): nový soubor nahrát →
   zapsat do databáze → TEPRVE PAK smazat starý soubor. */

/** Obsah ochranného .htaccess pro uploads/ (stejný jako web/uploads/.htaccess). */
const UPLOADS_HTACCESS = <<<'HTA'
# Soubory nahrané přes administraci – nikdy je nespouštět
RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phar .pht .phps .cgi .pl .py
RemoveType .php .phtml .php3 .php4 .php5 .php7 .php8 .phar .pht .phps
AddType text/plain .php .phtml .phar .pht
<FilesMatch "\.(php|phtml|php\d|phar|pht|phps|cgi|pl|py|sh|htaccess|html?|svg|js)$">
  Require all denied
</FilesMatch>
<IfModule mod_php.c>
  php_flag engine off
</IfModule>
<IfModule mod_php7.c>
  php_flag engine off
</IfModule>
<IfModule mod_php8.c>
  php_flag engine off
</IfModule>
Options -Indexes -ExecCGI
<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  # obrázek ani video nesmí nic spustit, i kdyby ho prohlížeč otevřel samostatně
  Header set Content-Security-Policy "default-src 'none'; img-src 'self'; media-src 'self'; style-src 'unsafe-inline'; sandbox"
  # PDF bez CSP – vestavěný prohlížeč PDF v Chrome a Firefoxu by se se „sandbox“ neotevřel;
  # PDF chrání kontrola obsahu při nahrání (upload_document) a nosniff
  <FilesMatch "\.pdf$">
    Header unset Content-Security-Policy
  </FilesMatch>
</IfModule>

HTA;

/** Zajistí, že v uploads/ leží .htaccess, který zakazuje spouštět skripty.
 *  (uploads/ není v gitu a na server se nahrává zvlášť – pojistka pro případ, že by chyběl.) */
function uploads_zajisti_ochranu(): void {
    $soubor = UPLOAD_DIR . '/.htaccess';
    if (is_file($soubor)) return;
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
    @file_put_contents($soubor, UPLOADS_HTACCESS);
}

/** Načte obrázek přes GD (JPG, PNG, WEBP, GIF). Vrací [obraz, mime] nebo [null, chyba]. */
function obrazek_nacti(string $cesta): array {
    $info = @getimagesize($cesta);
    if (!$info || empty($info[0]) || empty($info[1])) return [null, 'Soubor není obrázek.'];
    if ($info[0] * $info[1] > 60_000_000) return [null, 'Obrázek je příliš velký (víc než 60 megapixelů).'];
    $mime = (string)$info['mime'];
    $im = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($cesta),
        'image/png'  => @imagecreatefrompng($cesta),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($cesta) : false,
        'image/gif'  => @imagecreatefromgif($cesta),
        default      => false,
    };
    if (!$im) return [null, 'Nepodporovaný formát (povolené jsou JPG, PNG, WEBP a GIF).'];
    if (!imageistruecolor($im)) imagepalettetotruecolor($im);

    // otočení podle EXIF – fotky z telefonu jinak leží na boku
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $ex = @exif_read_data($cesta);
        $rot = match ((int)($ex['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($rot !== 0) {
            $otoceny = imagerotate($im, $rot, 0);
            if ($otoceny) $im = $otoceny;
        }
    }
    return [$im, $mime];
}

/** Má obrázek opravdu průhlednost? (projde řídkou mřížkou bodů) */
function obrazek_ma_pruhlednost(GdImage $im): bool {
    $w = imagesx($im);
    $h = imagesy($im);
    $krok = max(1, (int)floor(min($w, $h) / 60));
    for ($y = 0; $y < $h; $y += $krok) {
        for ($x = 0; $x < $w; $x += $krok) {
            if (((imagecolorat($im, $x, $y) >> 24) & 0x7F) > 0) return true;
        }
    }
    return false;
}

/** Zmenší obrázek, aby se vešel do $maxW × $maxH (nikdy nezvětšuje). */
function obrazek_zmensi(GdImage $im, int $maxW, int $maxH): GdImage {
    $w = imagesx($im);
    $h = imagesy($im);
    $pomer = min($maxW / $w, $maxH / $h, 1);
    if ($pomer >= 1) return $im;
    $nw = max(1, (int)round($w * $pomer));
    $nh = max(1, (int)round($h * $pomer));
    $novy = imagecreatetruecolor($nw, $nh);
    imagealphablending($novy, false);
    imagesavealpha($novy, true);
    imagefilledrectangle($novy, 0, 0, $nw, $nh, imagecolorallocatealpha($novy, 0, 0, 0, 127));
    imagecopyresampled($novy, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
    return $novy;
}

/**
 * Uloží obrázek do uploads/<podsložka>/<jméno>.(jpg|png) a vytvoří WebP kopii.
 * PNG zůstane jen u obrázků s průhledností, ostatní jdou do JPG.
 * Vrací ['ok'=>true, 'file'=>'podslozka/jmeno.jpg', 'w'=>…, 'h'=>…, 'webp'=>'…'] nebo ['ok'=>false,'error'=>…].
 */
function obrazek_uloz(GdImage $im, string $podslozka, string $jmeno, int $maxW = 2400, int $maxH = 2400, int $kvalita = 84): array {
    $im = obrazek_zmensi($im, $maxW, $maxH);
    $podslozka = trim(preg_replace('~[^a-z0-9_\-/]~i', '', $podslozka), '/');
    $jmeno = slugify($jmeno);
    uploads_zajisti_ochranu();
    $dir = UPLOAD_DIR . '/' . $podslozka;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        return ['ok' => false, 'error' => 'Složku pro obrázky nelze založit (práva k zápisu).'];
    }

    $png = obrazek_ma_pruhlednost($im);
    $rel = $podslozka . '/' . $jmeno . ($png ? '.png' : '.jpg');
    $cil = UPLOAD_DIR . '/' . $rel;

    if ($png) {
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $ok = @imagepng($im, $cil, 7);
    } else {
        // průhledná místa (u JPG nejdou) podložit papírovou barvou webu
        $plat = imagecreatetruecolor(imagesx($im), imagesy($im));
        imagefilledrectangle($plat, 0, 0, imagesx($im), imagesy($im), imagecolorallocate($plat, 0xfb, 0xfa, 0xf6));
        imagecopy($plat, $im, 0, 0, 0, 0, imagesx($im), imagesy($im));
        imageinterlace($plat, true);                       // progresivní JPG
        $ok = @imagejpeg($plat, $cil, $kvalita);
        $im = $plat;
    }
    if (!$ok) return ['ok' => false, 'error' => 'Obrázek se nepodařilo uložit.'];

    $w = imagesx($im);
    $h = imagesy($im);
    /* imagedestroy() se nevolá – od PHP 8.0 nemá účinek a od 8.5 hlásí zastaralost,
       což by se vypsalo do stránky a rozbilo přesměrování. */
    unset($im, $plat);

    $webp = vytvor_webp($rel) ? webp_jmeno($rel) : '';
    return ['ok' => true, 'file' => $rel, 'w' => $w, 'h' => $h, 'webp' => $webp];
}

/** Volné jméno souboru v podsložce (přidá -2, -3… když už existuje). */
function obrazek_volne_jmeno(string $podslozka, string $jmeno): string {
    $zaklad = slugify($jmeno);
    $j = $zaklad;
    $i = 2;
    while (is_file(UPLOAD_DIR . '/' . $podslozka . '/' . $j . '.jpg') || is_file(UPLOAD_DIR . '/' . $podslozka . '/' . $j . '.png')) {
        $j = $zaklad . '-' . $i++;
    }
    return $j;
}

/**
 * Nahrání obrázku z formuláře ($_FILES['pole']).
 * $jmeno = základ názvu souboru (např. nadpis aktuality); prázdný = datum a náhodný kód.
 */
function upload_image(array $file, string $podslozka, int $maxW = 2400, int $maxH = 2400, string $jmeno = ''): array {
    if (!isset($file['tmp_name']) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Nebyl vybrán žádný soubor.'];
    }
    $chyba = (int)($file['error'] ?? 0);
    if ($chyba === UPLOAD_ERR_INI_SIZE || $chyba === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'error' => 'Obrázek je příliš velký – server přijme nejvýš ' . ini_get('upload_max_filesize') . 'B.'];
    }
    if ($chyba !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Nahrání se nezdařilo (kód ' . $chyba . ').'];
    }
    if (PHP_SAPI !== 'cli' && !is_uploaded_file((string)$file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Soubor se nepodařilo přečíst.'];
    }
    [$im, $mime] = obrazek_nacti((string)$file['tmp_name']);
    if (!$im) return ['ok' => false, 'error' => $mime];

    $zaklad = $jmeno !== '' ? mb_substr($jmeno, 0, 60) : date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    $podslozka = trim($podslozka, '/');
    return obrazek_uloz($im, $podslozka, obrazek_volne_jmeno($podslozka, $zaklad), $maxW, $maxH);
}

/**
 * Převezme obrázek z disku (seed, import). Stejná úprava jako při nahrání.
 * Když zdroj neexistuje, ale cílový soubor už v uploads/ leží (na serveru se
 * uploads/ nahrávají zvlášť), vrátí cestu k němu. Jinak vrací ''.
 */
function obrazek_import(string $zdroj, string $podslozka, string $jmeno, int $maxW = 2400, int $maxH = 2400): string {
    $podslozka = trim($podslozka, '/');
    $jmeno = slugify($jmeno);
    foreach (['jpg', 'png'] as $pripona) {
        $hotovy = $podslozka . '/' . $jmeno . '.' . $pripona;
        if (is_file(UPLOAD_DIR . '/' . $hotovy) && (!is_file($zdroj) || filemtime(UPLOAD_DIR . '/' . $hotovy) >= filemtime($zdroj))) {
            return $hotovy;
        }
    }
    if (!is_file($zdroj)) return '';
    [$im] = obrazek_nacti($zdroj);
    if (!$im) return '';
    $r = obrazek_uloz($im, $podslozka, $jmeno, $maxW, $maxH);
    return $r['ok'] ? $r['file'] : '';
}

/** Rozměry nahraného obrázku [w, h] (0, 0 když soubor není). */
function obrazek_rozmer(?string $rel): array {
    $rel = ltrim((string)$rel, '/');
    if ($rel === '') return [0, 0];
    $i = @getimagesize(UPLOAD_DIR . '/' . $rel);
    return $i ? [(int)$i[0], (int)$i[1]] : [0, 0];
}

/* ---------------- WebP ---------------- */

/** galerie/x.jpg → galerie/x.webp */
function webp_jmeno(string $rel): string {
    return preg_replace('/\.[A-Za-z0-9]+$/', '', $rel) . '.webp';
}

/** Relativní cesta k WebP kopii, pokud opravdu leží na disku, jinak ''. */
function webp_vedle(?string $rel): string {
    $rel = ltrim((string)$rel, '/');
    if ($rel === '' || str_ends_with(strtolower($rel), '.webp')) return '';
    $w = webp_jmeno($rel);
    return is_file(UPLOAD_DIR . '/' . $w) ? $w : '';
}

/** Vytvoří WebP kopii vedle obrázku. Hosting bez WebP v GD kopii prostě nevyrobí. */
function vytvor_webp(?string $rel, int $kvalita = 82): bool {
    $rel = ltrim((string)$rel, '/');
    if ($rel === '' || !function_exists('imagewebp')) return false;
    $zdroj = UPLOAD_DIR . '/' . $rel;
    if (!is_file($zdroj)) return false;
    [$im] = obrazek_nacti($zdroj);
    if (!$im) return false;
    imagealphablending($im, false);
    imagesavealpha($im, true);
    $ok = @imagewebp($im, UPLOAD_DIR . '/' . webp_jmeno($rel), $kvalita);
    unset($im);
    return (bool)$ok;
}

/**
 * <picture> s WebP a záložním JPG/PNG. $attr = další atributy <img> jako pole
 * (alt, class, loading, style, sizes…). Rozměry se doplní z disku.
 */
function obrazek_html(?string $rel, array $attr = []): string {
    $rel = ltrim((string)$rel, '/');
    if ($rel === '') return '';
    [$w, $h] = obrazek_rozmer($rel);
    $a = array_merge(['alt' => '', 'loading' => 'lazy', 'decoding' => 'async'], $attr);
    if ($w && !isset($a['width']))  $a['width'] = (string)$w;
    if ($h && !isset($a['height'])) $a['height'] = (string)$h;
    $img = '<img src="' . e(upload_url($rel)) . '"';
    foreach ($a as $k => $v) {
        if ($v === null || $v === false) continue;
        $img .= ' ' . e((string)$k) . '="' . e((string)$v) . '"';
    }
    $img .= '>';
    $webp = webp_vedle($rel);
    if ($webp === '') return $img;
    return '<picture><source type="image/webp" srcset="' . e(upload_url($webp)) . '">' . $img . '</picture>';
}

/* ---------------- Mazání ---------------- */

/** Smaže soubor z uploads/ (i jeho WebP kopii). Mimo uploads/ nikdy nic nesmaže. */
function delete_upload(?string $rel): void {
    $rel = ltrim(str_replace('\\', '/', (string)$rel), '/');
    if ($rel === '' || str_contains($rel, '..') || preg_match('~^https?://~i', $rel)) return;
    $koren = realpath(UPLOAD_DIR);
    foreach ([$rel, webp_jmeno($rel)] as $r) {
        $p = realpath(UPLOAD_DIR . '/' . $r);
        if ($p && $koren && str_starts_with($p, $koren . DIRECTORY_SEPARATOR) && is_file($p)) {
            @unlink($p);
        }
    }
}

/* ---------------- PDF ---------------- */

/**
 * Nahrání PDF. Soubor se neupravuje, jen se ověří, že je to opravdu PDF
 * (hlavička %PDF- i MIME podle obsahu), a uloží pod bezpečným názvem.
 * Vrací ['ok'=>true, 'file'=>'dokumenty/…pdf', 'name'=>'Původní název.pdf'].
 */
function upload_document(array $file, string $podslozka = 'dokumenty', int $maxMB = UPLOAD_PDF_MAX_MB): array {
    if (!isset($file['tmp_name']) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Nebyl vybrán žádný soubor.'];
    }
    $chyba = (int)($file['error'] ?? 0);
    $zmensit = ' Zmenšete prosím PDF (v Acrobatu „Uložit jako jiný → Zmenšený soubor PDF“, z InDesignu export „pro web“) a nahrajte ho znovu.';
    if ($chyba === UPLOAD_ERR_INI_SIZE || $chyba === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'error' => 'Soubor je příliš velký – server přijme nejvýš ' . velikost_text(upload_limit_bajtu($maxMB)) . '.' . $zmensit];
    }
    if ($chyba === UPLOAD_ERR_PARTIAL) return ['ok' => false, 'error' => 'Soubor dorazil jen zčásti (přerušené spojení). Zkuste ho nahrát znovu.'];
    if ($chyba !== UPLOAD_ERR_OK) return ['ok' => false, 'error' => 'Nahrání se nezdařilo (kód ' . $chyba . ').'];
    if (($file['size'] ?? 0) > $maxMB * 1048576) {
        return ['ok' => false, 'error' => 'Soubor má ' . velikost_text((int)$file['size']) . ', web přijme nejvýš ' . $maxMB . "\u{00A0}MB." . $zmensit];
    }
    $tmp = (string)$file['tmp_name'];
    if (PHP_SAPI !== 'cli' && !is_uploaded_file($tmp)) return ['ok' => false, 'error' => 'Soubor se nepodařilo přečíst.'];

    // kontrola obsahu, ne přípony – přejmenovaný skript tak neprojde
    // %PDF- musí stát NA ZAČÁTKU (nanejvýš za BOM a mezerami) – HTML s „%PDF-“ někde uvnitř neprojde
    $zacatek = (string)@file_get_contents($tmp, false, null, 0, 1024);
    if (!preg_match('~^(?:\xEF\xBB\xBF)?[\x00\s]{0,16}%PDF-\d~', $zacatek)) return ['ok' => false, 'error' => 'Nahrát lze jen soubor PDF.'];
    if (preg_match('~<\s*(html|script|body|head|svg|iframe|object|embed|meta)\b|<!doctype~i', $zacatek)) {
        return ['ok' => false, 'error' => 'Soubor se tváří jako PDF, ale obsahuje kód webové stránky – nahrát ho nejde.'];
    }
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $fi ? (string)finfo_file($fi, $tmp) : '';
        if ($mime !== '' && $mime !== 'application/pdf' && $mime !== 'application/x-pdf') {
            return ['ok' => false, 'error' => 'Soubor se tváří jako PDF, ale není to PDF (' . $mime . ').'];
        }
    }

    $puvodni = basename(str_replace('\\', '/', (string)($file['name'] ?? '')));
    $puvodni = trim((string)preg_replace('~[^\p{L}\p{N}._ ()-]+~u', '', $puvodni));
    if ($puvodni === '' || $puvodni === '.') $puvodni = 'dokument.pdf';
    if (!preg_match('/\.pdf$/i', $puvodni)) $puvodni .= '.pdf';

    uploads_zajisti_ochranu();
    $podslozka = trim(preg_replace('~[^a-z0-9_\-/]~i', '', $podslozka), '/');
    $dir = UPLOAD_DIR . '/' . $podslozka;
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) return ['ok' => false, 'error' => 'Složku pro dokumenty nelze založit.'];

    $zaklad = slugify(preg_replace('/\.pdf$/i', '', $puvodni));
    $jmeno = $zaklad . '.pdf';
    $i = 2;
    while (is_file($dir . '/' . $jmeno)) $jmeno = $zaklad . '-' . $i++ . '.pdf';

    $ok = PHP_SAPI === 'cli' ? @copy($tmp, $dir . '/' . $jmeno) : @move_uploaded_file($tmp, $dir . '/' . $jmeno);
    if (!$ok) return ['ok' => false, 'error' => 'Soubor se nepodařilo uložit.'];
    return ['ok' => true, 'file' => $podslozka . '/' . $jmeno, 'name' => mb_substr($puvodni, 0, 160)];
}

/** Má dokument vlastní stránku s textem (dokument.php?d=slug)? */
function dokument_ma_text(array $d): bool {
    return trim((string)($d['slug'] ?? '')) !== '' && trim((string)($d['text'] ?? '')) !== '';
}

/** Adresa souboru dokumentu: nahrané PDF (přílohy archivu turnajů), jinak odkaz jinam.
 *  Prázdné = dokument soubor nemá. */
function dokument_soubor_url(array $d): string {
    if (!empty($d['soubor'])) return upload_url((string)$d['soubor']);
    return bezpecny_odkaz((string)($d['url'] ?? ''));
}

/** Adresa dokumentu pro seznamy a patičku: stránka s textem (dokument.php?d=…) – dokumenty klubu
 *  jsou text na webu, ne PDF; soubor/odkaz jen u příloh archivu, nebo když text chybí. */
function dokument_url(array $d): string {
    if (dokument_ma_text($d)) return url('dokument.php?d=' . rawurlencode(trim((string)$d['slug'])));
    return dokument_soubor_url($d);
}

/** Počet stran PDF (přílohy archivu): počet objektů /Type /Page, u komprimovaných
 *  objektových proudů /Count kořene stromu stránek. Nepovede-li se, null (počet se nevypíše). */
function pdf_pocet_stran(string $cesta): ?int {
    if (!is_file($cesta) || filesize($cesta) > 64 * 1048576) return null;
    $obsah = (string)@file_get_contents($cesta);
    if (!preg_match('~^(?:\xEF\xBB\xBF)?[\x00\s]{0,16}%PDF-\d~', substr($obsah, 0, 1024))) return null;
    $n = (int)preg_match_all('~/Type\s*/Page(?![a-zA-Z])~', $obsah);
    if ($n > 0) return $n;
    if (preg_match_all('~/Count\s+(\d+)~', $obsah, $m)) return max(array_map('intval', $m[1])) ?: null;
    return null;
}

/** „3 strany · 244 kB“ pro přílohu archivu (počet stran z DB, velikost z disku). */
function dokument_soubor_info(array $d): string {
    $casti = [];
    $stran = (int)($d['stran'] ?? 0);
    if ($stran > 0) $casti[] = $stran . "\u{00A0}" . sklonuj($stran, 'strana', 'strany', 'stran');
    $rel = (string)($d['soubor'] ?? '');
    if ($rel !== '' && is_file(UPLOAD_DIR . '/' . $rel)) $casti[] = velikost_text((int)filesize(UPLOAD_DIR . '/' . $rel));
    return implode(' · ', $casti);
}

/** Adresa dokumentu pro stránku: „stanovy“, „provozni-rad-bazen“ (malá písmena, číslice, pomlčky). */
function dokument_slug_platny(string $slug): bool {
    return strlen($slug) <= 120 && (bool)preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
}

/* ---------------- Velikost nahrávaných souborů ---------------- */

/** Největší PDF, které web přijme (vlastní pojistka; skutečný strop dává server – upload_limit_bajtu()). */
const UPLOAD_PDF_MAX_MB = 40;

/** Hodnota z php.ini („20M“, „512K“, „1G“) v bajtech; 0 = bez omezení / neznámé. */
function ini_bajty(string $v): int {
    $v = trim($v);
    if ($v === '' || $v === '-1') return 0;
    $n = (float)$v;
    $j = strtolower(substr($v, -1));
    $n *= match ($j) { 'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1 };
    return (int)$n;
}

/** Kolik bajtů jde nahrát jedním souborem: menší z upload_max_filesize, post_max_size
 *  a pojistky webu ($maxMB). */
function upload_limit_bajtu(int $maxMB = UPLOAD_PDF_MAX_MB): int {
    $limity = array_filter([ini_bajty((string)ini_get('upload_max_filesize')), ini_bajty((string)ini_get('post_max_size')), $maxMB * 1048576]);
    return $limity ? (int)min($limity) : $maxMB * 1048576;
}

/** Velikost souboru česky: 24 MB, 1,5 MB, 640 kB. */
function velikost_text(int $bajty): string {
    if ($bajty >= 1048576) {
        $mb = $bajty / 1048576;
        return str_replace('.', ',', (string)($mb >= 10 || abs($mb - round($mb)) < 0.05 ? round($mb) : round($mb, 1))) . "\u{00A0}MB";
    }
    return max(1, (int)round($bajty / 1024)) . "\u{00A0}kB";
}

/* ---------------- Loga partnerů ---------------- */

/** Barva a krytí jednobarevných log – změřeno na logách, která dodal klient
 *  (podklady/klient-loga/partneri: #a3947e při krytí 60 %). Automaticky
 *  přebarvené logo tak na webu vypadá stejně jako ta dodaná. */
const PARTNER_LOGO_BARVA = '#a3947e';
const PARTNER_LOGO_KRYTI = 0.60;

/**
 * Jednobarevné průhledné logo partnera (teplá béžovo-zlatá jako v PDF klienta).
 * Používá se jen u loga NOVĚ nahraného v administraci – loga dodaná klientem
 * se berou tak, jak jsou (ZADANI §0.2).
 *
 * Postup: pozadí se odhadne z okrajů obrázku (bílé, barevné i tmavé) a každý bod
 * dostane průhlednost podle toho, jak moc se od pozadí liší. Kde má logo už vlastní
 * průhlednost, násobí se jí. Všechny body se obarví na $barva (krytí nejvýš $kryti),
 * prázdné okraje se ořežou a logo se zmenší na max. 600 × 300 px.
 * Výsledek: uploads/partneri/mono/<jmeno>.png – vždy NOVÝ soubor (při shodě jména
 * -2, -3…), takže smazání starého loga po uložení nikdy nesmaže to nové.
 *
 * $zdroj = cesta na disku NEBO relativní cesta v uploads/. Vrací relativní cestu, nebo ''.
 */
function partner_logo_mono(string $zdroj, string $jmeno = '', string $barva = PARTNER_LOGO_BARVA, float $maxKryti = PARTNER_LOGO_KRYTI): string {
    $cesta = is_file($zdroj) ? $zdroj : UPLOAD_DIR . '/' . ltrim($zdroj, '/');
    if (!is_file($cesta)) return '';
    [$im] = obrazek_nacti($cesta);
    if (!$im) return '';
    $im = obrazek_zmensi($im, 1400, 1400);
    $w = imagesx($im);
    $h = imagesy($im);

    // 1) pozadí z okrajů: medián barev neprůhledných bodů po obvodu
    $okraj = [];
    $pruhledny = 0;
    $krok = max(1, (int)floor(($w + $h) / 400));
    for ($x = 0; $x < $w; $x += $krok) { $okraj[] = imagecolorat($im, $x, 0); $okraj[] = imagecolorat($im, $x, $h - 1); }
    for ($y = 0; $y < $h; $y += $krok) { $okraj[] = imagecolorat($im, 0, $y); $okraj[] = imagecolorat($im, $w - 1, $y); }
    $rs = $gs = $bs = [];
    foreach ($okraj as $c) {
        if ((($c >> 24) & 0x7F) > 100) { $pruhledny++; continue; }
        $rs[] = ($c >> 16) & 255; $gs[] = ($c >> 8) & 255; $bs[] = $c & 255;
    }
    $pozadiPruhledne = $pruhledny > count($okraj) * 0.5;
    $median = static function (array $a): int { if (!$a) return 255; sort($a); return $a[intdiv(count($a), 2)]; };
    [$br, $bg, $bb] = [$median($rs), $median($gs), $median($bs)];
    $pozadiJas = (0.2126 * $br + 0.7152 * $bg + 0.0722 * $bb) / 255;

    // u průhledného loga: je kresba světlá (bílé logo na průhledném)? pak se řídí jen alfou
    $svetlaKresba = false;
    if ($pozadiPruhledne) {
        $soucet = 0; $n = 0;
        for ($y = 0; $y < $h; $y += 3) for ($x = 0; $x < $w; $x += 3) {
            $c = imagecolorat($im, $x, $y);
            if ((($c >> 24) & 0x7F) < 30) { $soucet += (0.2126 * (($c >> 16) & 255) + 0.7152 * (($c >> 8) & 255) + 0.0722 * ($c & 255)) / 255; $n++; }
        }
        $svetlaKresba = $n > 0 && $soucet / $n > 0.82;
    }

    [$tr, $tg, $tb] = sscanf(ltrim($barva, '#'), '%02x%02x%02x');
    $vystup = imagecreatetruecolor($w, $h);
    imagealphablending($vystup, false);
    imagesavealpha($vystup, true);
    imagefilledrectangle($vystup, 0, 0, $w, $h, imagecolorallocatealpha($vystup, $tr, $tg, $tb, 127));

    $minX = $w; $minY = $h; $maxX = -1; $maxY = -1;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $c = imagecolorat($im, $x, $y);
            $a0 = 1 - ((($c >> 24) & 0x7F) / 127);         // vlastní krytí bodu 0–1
            if ($a0 <= 0.01) continue;
            $r = ($c >> 16) & 255; $g = ($c >> 8) & 255; $b = $c & 255;

            if ($pozadiPruhledne) {
                if ($svetlaKresba) {
                    $kryti = $a0;
                } else {
                    // tmavší = sytější; bílé detaily uvnitř loga zůstanou průhledné jako v PDF
                    $jas = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
                    $kryti = $a0 * min(1, max(0, (0.97 - $jas) / 0.55));
                }
            } else {
                $rozdil = max(abs($r - $br), abs($g - $bg), abs($b - $bb)) / 255;
                $kryti = $a0 * min(1, max(0, ($rozdil - 0.07) / 0.45));
            }
            if ($kryti <= 0.02) continue;
            $alfa = (int)round(127 - 127 * $kryti * max(0.05, min(1, $maxKryti)));
            imagesetpixel($vystup, $x, $y, imagecolorallocatealpha($vystup, $tr, $tg, $tb, $alfa));
            if ($kryti > 0.15) {
                if ($x < $minX) $minX = $x; if ($x > $maxX) $maxX = $x;
                if ($y < $minY) $minY = $y; if ($y > $maxY) $maxY = $y;
            }
        }
    }
    if ($maxX < 0) return '';                                // nic nezbylo – logo by bylo neviditelné

    // 2) oříznout prázdné okraje (s malou rezervou)
    $rez = 2;
    $minX = max(0, $minX - $rez); $minY = max(0, $minY - $rez);
    $maxX = min($w - 1, $maxX + $rez); $maxY = min($h - 1, $maxY + $rez);
    $cw = $maxX - $minX + 1; $ch = $maxY - $minY + 1;
    $orez = imagecreatetruecolor($cw, $ch);
    imagealphablending($orez, false);
    imagesavealpha($orez, true);
    imagefilledrectangle($orez, 0, 0, $cw, $ch, imagecolorallocatealpha($orez, $tr, $tg, $tb, 127));
    imagecopy($orez, $vystup, 0, 0, $minX, $minY, $cw, $ch);
    $orez = obrazek_zmensi($orez, 600, 300);

    $dir = UPLOAD_DIR . '/partneri/mono';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) return '';
    $zaklad = slugify($jmeno !== '' ? $jmeno : pathinfo($cesta, PATHINFO_FILENAME));
    $jmeno = $zaklad;
    $i = 2;
    while (is_file($dir . '/' . $jmeno . '.png')) $jmeno = $zaklad . '-' . $i++;
    $rel = 'partneri/mono/' . $jmeno . '.png';
    imagealphablending($orez, false);
    imagesavealpha($orez, true);
    $ok = @imagepng($orez, UPLOAD_DIR . '/' . $rel, 8);
    unset($im, $vystup, $orez);
    return $ok ? $rel : '';
}
