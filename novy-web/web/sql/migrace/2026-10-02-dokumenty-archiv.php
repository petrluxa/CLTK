<?php
/* JEDNORÁZOVÁ migrace 2. 10. 2026 – nový web nesmí odkazovat na starý web cltk.cz
   ani na files.cltk.cz (vypne se). Data: 2026-10-02-dokumenty-archiv.data.json (vedle).
   Rozhodnutí klienta: dokumenty klubu (stanovy, pravidla hraní a rezervací, provozní řády,
   osobní údaje členů, ceník, plán areálu) jsou STRÁNKY WEBU v klubovém stylu, ne PDF.
   PDF zůstávají jen u Revue, newsletterů a v archivu klubových turnajů.

   Co udělá (jen tabulky cltk_, SQLite i MySQL, opakované spuštění nic nezmění):
     1. přidá sloupce cltk_dokumenty.slug, .text, .rok, .skupina, .stran
        a cltk_newslettery.pdf_cs_soubor, .pdf_en_soubor (jen když chybí),
     2. Revue a newslettery: PDF z files.cltk.cz → soubory nahrané na web (pdf_soubor,
        pdf_cs_soubor, pdf_en_soubor – cesta v uploads/, jako u PDF nahraného v administraci),
     3. dokumenty 1–8: adresa stránky (slug) a text na webu (text jen když je prázdný),
        odkaz na starý web se vymaže – žádné PDF,
     4. ceník zimní sezóny a blok „Plán areálu“: odkaz na PDF → stránka dokumentu k vytištění,
     5. texty bloků, které mluvily o PDF ke stažení, a texty stránky rozvrhů; nové bloky stránky Dokumenty,
     6. výsledky hráčů: odkaz na článek cltk.cz/cs/clanky/… se vymaže (karta je soběstačná),
     7. prameny kroniky a CTC: adresy starých stránek a článků → prostý text s názvem,
        PDF Revue → soubor webu,
     8. jubilejní Revue 1893–2023 (speciální číslo, cislo = 0) do kiosku Revue,
     9. archiv klubových turnajů (23 PDF) do Dokumentů (kategorie „turnaje“),
    10. rozvrhy Tenisové školy (zima 2026/27 a přechodný týden) do modulu Tenisová škola.
   Přepisuje VŽDY jen hodnotu, která se pořád PŘESNĚ rovná staré hodnotě (úpravy z administrace
   mezitím zůstanou); nové řádky vloží, jen když tam ještě nejsou. Když na disku chybí některý
   soubor z uploads/ (nebo nemá čekanou velikost), neudělá nic – databáze nesmí ukazovat na
   nenahrané soubory. Na konci vypíše HOTOVO.

   Spuštění:
     lokálně      CLTK_DB_FILE=web/data/x.sqlite ./_php/php.exe web/sql/migrace/2026-10-02-dokumenty-archiv.php [--nanecisto]
     na serveru   soubor + .data.json zkopírovat do kořene webu (sql/ je zvenku zavřené) a otevřít
                  …/2026-10-02-dokumenty-archiv.php?token=<token>   (&nanecisto=1 = jen ukázat, co by se změnilo)
                  Po doběhnutí OBA soubory z kořene webu smazat.
   Funguje z kořene webu i ze sql/migrace/ (jádro inc/functions.php najde o patra výš).
   Výchozí obsah (sql/seed/95-archiv-pdf.php) volá stejnou logiku – nový seed má hned nový stav. */

/* Token pro spuštění přes prohlížeč (32 znaků). Repozitář je veřejný (GitHub), proto je tu jen
   jeho otisk SHA-256 – token sám zná orchestrátor. Porovnává se hash_equals(). */
const MIGRACE_TOKEN_SHA256 = '96209b567baf74dde4ca37aa6a060abb6b1fc84cded979188c00c11840ed5e15';
const MIGRACE_ARCHIV_DATA = '2026-10-02-dokumenty-archiv.data.json';

if (!defined('ARCHIV_MIGRACE_KNIHOVNA')) {
    if (PHP_SAPI !== 'cli') {
        $token = $_GET['token'] ?? '';
        if (!is_string($token) || strlen($token) !== 32 || !hash_equals(MIGRACE_TOKEN_SHA256, hash('sha256', $token))) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Robots-Tag: noindex, nofollow');
            exit("403 – bez platného tokenu se migrace nespustí.\n");
        }
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        @set_time_limit(300);
        ignore_user_abort(true);             // zavřené okno / výpadek proxy migraci nepřeruší uprostřed
    }
    $jadro = archiv_najdi_jadro(__DIR__);
    if ($jadro === '') { echo "CHYBA: nenašel jsem inc/functions.php (soubor patří do kořene webu nebo do sql/migrace/).\n"; exit(1); }
    require_once $jadro;
    $nanecisto = PHP_SAPI === 'cli' ? in_array('--nanecisto', (array)($argv ?? []), true) : (($_GET['nanecisto'] ?? '') === '1');
    $ok = archiv_migrace_spust(__DIR__ . '/' . MIGRACE_ARCHIV_DATA, $nanecisto, static function (string $s): void { echo $s, "\n"; });
    exit($ok ? 0 : 1);
}

/** Najde jádro webu: inc/functions.php v této složce nebo o 1–4 patra výš. */
function archiv_najdi_jadro(string $odkud): string {
    $d = $odkud;
    for ($i = 0; $i <= 4; $i++) {
        if (is_file($d . '/inc/functions.php') && is_file($d . '/inc/db.php')) return $d . '/inc/functions.php';
        $d = dirname($d);
    }
    return '';
}

/** Má tabulka sloupec? (SQLite: CREATE TABLE v sqlite_master, MySQL: information_schema) */
function archiv_ma_sloupec(string $tabulka, string $sloupec): bool {
    $tabulka = cltk_tabulka($tabulka);
    if (!preg_match('/^[a-z_][a-z0-9_]*$/', $sloupec)) throw new RuntimeException('Neplatný sloupec ' . $sloupec);
    if (db_je_mysql()) {
        return (int)val('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$tabulka, $sloupec]) > 0;
    }
    $sql = (string)val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$tabulka]);
    return (bool)preg_match('/[(,]\s*[`"\[]?' . preg_quote($sloupec, '/') . '[`"\]]?\s/i', $sql);
}

/**
 * Celá migrace. $log dostává řádky výpisu. $nanecisto = nic nezapisuje, jen vypíše, co by udělala.
 * $seed = volá ji výchozí obsah (sql/seed/95-archiv-pdf.php): chybějící soubor se jen ohlásí
 * a jeho řádky se přeskočí, kontrola nového kódu se vynechá. Vrací true = v pořádku.
 */
function archiv_migrace_spust(string $soubor, bool $nanecisto, callable $log, bool $seed = false): bool {
    $data = is_file($soubor) ? json_decode((string)file_get_contents($soubor), true) : null;
    if (!is_array($data) || ($data['migrace'] ?? '') !== '2026-10-02-dokumenty-archiv') {
        $log('CHYBA: datový soubor ' . basename($soubor) . ' chybí nebo je poškozený – musí ležet vedle migrace.');
        return false;
    }
    if (!$seed) {
        $log('Migrace 2026-10-02-dokumenty-archiv · databáze: ' . DB_DRIVER . ($nanecisto ? ' · NANEČISTO (nic se nezapíše)' : ''));
        $log('');
    }

    /* ---------- 0) kontroly: nový kód, tabulky, soubory ---------- */
    if (!$seed) {
        $kodOk = function_exists('dokument_ma_text') && function_exists('dokumenty_archiv') && function_exists('skola_rozvrhy')
              && function_exists('pdf_pocet_stran') && in_array('table', HTML_POVOLENE, true)
              && is_file(WEB_ROOT . '/dokument.php') && is_file(WEB_ROOT . '/dokumenty.php');
        if (!$kodOk) {
            $log('CHYBA: na serveru je starý kód webu (chybí dokument.php, dokumenty.php nebo nové inc/data.php, inc/soubory.php, inc/html.php).');
            $log('Nejdřív nahrajte nové soubory webu, pak migraci spusťte znovu. Nic se nezměnilo.');
            return false;
        }
    }
    $potreba = ['cltk_revue', 'cltk_newslettery', 'cltk_dokumenty', 'cltk_price_lists', 'cltk_bloky', 'cltk_vysledky', 'cltk_milniky', 'cltk_ctc', 'cltk_skola'];
    $chybiTab = array_diff($potreba, db_tabulky());
    if ($chybiTab) {
        $log('CHYBA: v databázi chybí tabulky ' . implode(', ', $chybiTab) . ' – je web nainstalovaný? Nic se nezměnilo.');
        return false;
    }
    $chybiSoubory = [];
    foreach ((array)($data['soubory'] ?? []) as $rel => $velikost) {
        $p = UPLOAD_DIR . '/' . $rel;
        if (!is_file($p)) $chybiSoubory[$rel] = 'chybí';
        elseif ((int)filesize($p) !== (int)$velikost) $chybiSoubory[$rel] = 'má ' . filesize($p) . ' B místo ' . $velikost . ' B (nahrané jen zčásti?)';
    }
    if ($chybiSoubory && !$seed) {
        $log('CHYBA: v uploads/ chybí ' . count($chybiSoubory) . ' ' . sklonuj(count($chybiSoubory), 'soubor', 'soubory', 'souborů') . ' – nejdřív je nahrajte (nahrat.py --ostra --soubory … jen tyto soubory, ne celé uploads/). Nic se nezměnilo.');
        $i = 0;
        foreach ($chybiSoubory as $rel => $proc) { if (++$i > 30) { $log('  … a ' . (count($chybiSoubory) - 30) . ' dalších'); break; } $log('  uploads/' . $rel . ' – ' . $proc); }
        return false;
    }
    if ($chybiSoubory) {
        seed_log('  ! archiv PDF: v uploads/ chybí ' . count($chybiSoubory) . ' souborů – jejich řádky zůstanou s původní adresou / nevloží se.');
    }
    if (!$seed) $log('Kontrola: nový kód webu je nahraný, ' . count((array)$data['soubory']) . ' souborů v uploads/ sedí na bajt.');
    $maSoubor = static fn(string $rel): bool => $rel !== '' && !isset($chybiSoubory[$rel]);
    $zapis = static function (string $s) use ($log, $seed): void { if (!$seed || $s !== '') $log($s); };

    /* ---------- 1) sloupce ---------- */
    $textTyp = db_je_mysql() ? db_mysql_text_vychozi() : "TEXT NOT NULL DEFAULT ''";
    $sloupce = [
        ['cltk_dokumenty', 'slug', "VARCHAR(120) NOT NULL DEFAULT ''"],
        ['cltk_dokumenty', 'text', $textTyp],
        ['cltk_dokumenty', 'rok', 'INTEGER NULL'],
        ['cltk_dokumenty', 'skupina', "VARCHAR(120) NOT NULL DEFAULT ''"],
        ['cltk_dokumenty', 'stran', 'INTEGER NULL'],
        ['cltk_newslettery', 'pdf_cs_soubor', "VARCHAR(255) NOT NULL DEFAULT ''"],
        ['cltk_newslettery', 'pdf_en_soubor', "VARCHAR(255) NOT NULL DEFAULT ''"],
    ];
    $maSloupec = [];
    $pridano = $preskocenoSl = [];
    foreach ($sloupce as [$tab, $sl, $typ]) {
        if (archiv_ma_sloupec($tab, $sl)) { $maSloupec[$tab . '.' . $sl] = true; $preskocenoSl[] = "$tab.$sl"; continue; }
        if ($nanecisto) { $maSloupec[$tab . '.' . $sl] = false; $pridano[] = "$tab.$sl"; continue; }
        q("ALTER TABLE $tab ADD COLUMN $sl $typ");
        $maSloupec[$tab . '.' . $sl] = true;
        $pridano[] = "$tab.$sl";
    }
    if (!$seed) {
        if ($pridano) $log('1) Sloupce ' . ($nanecisto ? 'by se přidaly' : 'přidány') . ': ' . implode(', ', $pridano));
        if ($preskocenoSl) $log('1) Sloupce už existují – přeskočeno: ' . implode(', ', $preskocenoSl));
    }
    $sl = static fn(string $k): bool => !empty($maSloupec[$k]);

    $pdo = db();
    $vTransakci = false;
    if (!$nanecisto) { $pdo->beginTransaction(); $vTransakci = true; }
    try {
        /* ---------- 2a) Revue ---------- */
        $zmena = $preskoceno = 0; $vlastni = [];
        foreach ((array)$data['revue'] as $r) {
            if (!$maSoubor($r['soubor'])) { $preskoceno++; continue; }
            $radky = rows('SELECT id, oznaceni, pdf_soubor FROM cltk_revue WHERE pdf_url = ?', [$r['stara_url']]);
            if (!$radky) { $preskoceno++; continue; }
            foreach ($radky as $x) {
                if ((string)$x['pdf_soubor'] === '') {
                    if (!$nanecisto) q("UPDATE cltk_revue SET pdf_soubor = ?, pdf_url = '', pdf_mb = ? WHERE id = ? AND pdf_url = ?", [$r['soubor'], $r['mb'], (int)$x['id'], $r['stara_url']]);
                    $zmena++;
                } else {
                    // klub mezitím nahrál vlastní PDF – to zůstává, jen se smaže starý odkaz
                    if (!$nanecisto) q("UPDATE cltk_revue SET pdf_url = '' WHERE id = ? AND pdf_url = ?", [(int)$x['id'], $r['stara_url']]);
                    $vlastni[] = (string)$x['oznaceni'];
                }
            }
        }
        $zapis("2) Revue: $zmena " . sklonuj($zmena, 'číslo', 'čísla', 'čísel') . ' → PDF v uploads/revue/pdf/'
            . ($vlastni ? ', ' . count($vlastni) . ' s vlastním nahraným PDF (jen smazán odkaz: ' . implode(', ', $vlastni) . ')' : '')
            . ($preskoceno ? ", přeskočeno $preskoceno (odkaz už není původní – převedeno dřív nebo upraveno v administraci)" : ''));

        /* ---------- 2b) newslettery ---------- */
        $zmena = $preskoceno = 0; $vlastni = 0;
        foreach ((array)$data['newslettery'] as $n) {
            $j = $n['jazyk'] === 'en' ? 'en' : 'cs';
            if (!$maSoubor($n['soubor'])) { $preskoceno++; continue; }
            $slS = 'pdf_' . $j . '_soubor';
            $vyber = $sl('cltk_newslettery.' . $slS) ? $slS : "'' AS $slS";
            $radky = rows("SELECT id, $vyber FROM cltk_newslettery WHERE pdf_$j = ?", [$n['stara_url']]);
            if (!$radky) { $preskoceno++; continue; }
            foreach ($radky as $x) {
                if ((string)$x[$slS] === '') {
                    if (!$nanecisto) q("UPDATE cltk_newslettery SET $slS = ?, pdf_$j = '' WHERE id = ? AND pdf_$j = ?", [$n['soubor'], (int)$x['id'], $n['stara_url']]);
                    $zmena++;
                } else {
                    if (!$nanecisto) q("UPDATE cltk_newslettery SET pdf_$j = '' WHERE id = ? AND pdf_$j = ?", [(int)$x['id'], $n['stara_url']]);
                    $vlastni++;
                }
            }
        }
        $zapis("2) Newslettery: $zmena PDF → uploads/newslettery/" . ($vlastni ? ", $vlastni s vlastním nahraným PDF (jen smazán odkaz)" : '')
            . ($preskoceno ? ", přeskočeno $preskoceno (odkaz už není původní)" : ''));

        /* ---------- 3) dokumenty klubu: stránky s textem, žádné PDF ---------- */
        $sTextem = $sl('cltk_dokumenty.text') && $sl('cltk_dokumenty.slug');
        foreach ((array)$data['dokumenty'] as $dok) {
            $r = row('SELECT * FROM cltk_dokumenty WHERE id = ?', [(int)$dok['id']]);
            $sedi = static fn(?array $x): bool => $x !== null && ((string)$x['url'] === $dok['stara_url']
                || (string)$x['nazev'] === $dok['nazev'] || (string)($x['slug'] ?? '') === $dok['slug']);
            if (!$sedi($r)) {
                $r = row('SELECT * FROM cltk_dokumenty WHERE url = ? ORDER BY id LIMIT 1', [$dok['stara_url']]);
                if (!$sedi($r)) { $zapis('3) Dokument „' . $dok['nazev'] . '“ (id ' . $dok['id'] . ') v databázi není nebo se mezitím změnil – přeskočeno'); continue; }
            }
            $zmeny = [];
            $popisZmen = [];
            if ((string)($r['slug'] ?? '') === '') {
                $obsazeno = $sTextem && row('SELECT id FROM cltk_dokumenty WHERE slug = ? AND id <> ?', [$dok['slug'], (int)$r['id']]);
                if ($obsazeno) $popisZmen[] = 'adresu „' . $dok['slug'] . '“ už má jiný dokument – nevyplněna';
                else { $zmeny['slug'] = $dok['slug']; $popisZmen[] = 'stránka dokument.php?d=' . $dok['slug']; }
            }
            $textTed = trim((string)($r['text'] ?? ''));
            if ($textTed === '') {
                $zmeny['text'] = html_k_ulozeni((string)$dok['text']);
                $popisZmen[] = 'text (' . velikost_text(strlen($zmeny['text'])) . ')';
            }
            if ((string)$r['url'] === $dok['stara_url'] && ($textTed !== '' || isset($zmeny['text']))) {
                $zmeny['url'] = '';                                   // PDF / stránka starého webu → stránka s textem
                $popisZmen[] = 'odkaz na starý web zrušen';
            }
            if (!empty($dok['popis_stary']) && (string)$r['popis'] === $dok['popis_stary']) {
                $zmeny['popis'] = $dok['popis_novy'];
                $popisZmen[] = 'popis „' . $dok['popis_novy'] . '“';
            }
            if (!empty($dok['paticka_stary']) && (string)$r['paticka_text'] === $dok['paticka_stary']) {
                $zmeny['paticka_text'] = $dok['paticka_novy'];          // „Stanovy klubu (PDF)“ – odkaz už vede na stránku
                $popisZmen[] = 'text v patičce „' . $dok['paticka_novy'] . '“';
            }
            if (!$zmeny) { $zapis('3) „' . $r['nazev'] . '“: beze změny – už převedeno'); continue; }
            $zmeny['updated_at'] = ted();
            if (!$nanecisto) {
                if (!$sTextem) { unset($zmeny['slug'], $zmeny['text']); }
                db_update('cltk_dokumenty', (int)$r['id'], $zmeny);
            }
            $zapis('3) „' . $r['nazev'] . '“: ' . implode(', ', $popisZmen));
        }

        /* ---------- 4) odkazy na PDF (ceník, plán areálu) → stránky dokumentů k vytištění ---------- */
        foreach ((array)$data['odkazy'] as $o) {
            $tab = cltk_tabulka((string)$o['tabulka']);
            $sloupec = (string)$o['sloupec'];
            if (!in_array($tab . '.' . $sloupec, ['cltk_price_lists.pdf_url', 'cltk_bloky.odkaz', 'cltk_bloky.odkaz2'], true)) continue;
            $radky = rows("SELECT id FROM $tab WHERE $sloupec = ?", [$o['stara']]);
            foreach ($radky as $x) if (!$nanecisto) q("UPDATE $tab SET $sloupec = ? WHERE id = ? AND $sloupec = ?", [$o['nova'], (int)$x['id'], $o['stara']]);
            $zapis("4) $tab.$sloupec: " . ($radky ? count($radky) . ' × → ' . $o['nova'] : 'přeskočeno – odkaz už není původní'));
        }

        /* ---------- 5) texty bloků (jen co je pořád původní) a nové bloky stránky Dokumenty ---------- */
        $bZmena = $bPreskoceno = 0;
        foreach ((array)$data['bloky'] as $b) {
            $r = row('SELECT * FROM cltk_bloky WHERE stranka = ? AND klic = ?', [$b['stranka'], $b['klic']]);
            if (!$r) { $bPreskoceno++; continue; }
            $zmeny = [];
            foreach ((array)$b['zmeny'] as $sloupec => [$stara, $nova]) {
                if (!in_array($sloupec, ['stitek', 'nadpis', 'perex', 'text', 'odkaz_text', 'odkaz2_text', 'doplni_klub'], true)) continue;
                if ((string)$r[$sloupec] === (string)$stara) $zmeny[$sloupec] = $sloupec === 'text' ? html_k_ulozeni((string)$nova) : $nova;
            }
            if (!$zmeny) { $bPreskoceno++; continue; }
            $zmeny['updated_at'] = ted();
            if (!$nanecisto) db_update('cltk_bloky', (int)$r['id'], $zmeny);
            $bZmena++;
            $zapis('5) Blok ' . $b['stranka'] . '/' . $b['klic'] . ': ' . implode(', ', array_diff(array_keys($zmeny), ['updated_at'])));
        }
        $bNove = 0;
        foreach ((array)($data['bloky_nove'] ?? []) as $b) {
            if (row('SELECT id FROM cltk_bloky WHERE stranka = ? AND klic = ?', [$b['stranka'], $b['klic']])) continue;
            $radek = array_merge(['stitek' => '', 'nadpis' => '', 'perex' => '', 'text' => '', 'foto' => '', 'foto_popisek' => '',
                'odkaz' => '', 'odkaz_text' => '', 'odkaz2' => '', 'odkaz2_text' => '', 'doplni_klub' => 0, 'visible' => 1], $b, ['updated_at' => ted()]);
            if (!$nanecisto) db_insert('cltk_bloky', $radek);
            $bNove++;
        }
        $zapis('5) Bloky: ' . $bZmena . ' ' . sklonuj($bZmena, 'upraven', 'upraveny', 'upraveno') . ', ' . $bNove . ' ' . sklonuj($bNove, 'nový', 'nové', 'nových') . ' (stránka Dokumenty)'
            . ($bPreskoceno ? ", $bPreskoceno přeskočeno (text už není původní)" : ''));

        /* ---------- 6) výsledky: odkaz na článek starého webu pryč ---------- */
        $n = 0;
        foreach (rows("SELECT id, odkaz FROM cltk_vysledky WHERE odkaz LIKE '%cltk.cz/cs/clanky/%'") as $x) {
            if (!preg_match('~^https?://(www\.)?cltk\.cz/cs/clanky/~i', (string)$x['odkaz'])) continue;
            if (!$nanecisto) q("UPDATE cltk_vysledky SET odkaz = '' WHERE id = ? AND odkaz = ?", [(int)$x['id'], $x['odkaz']]);
            $n++;
        }
        $zapis("6) Výsledky hráčů: " . ($n ? "$n " . sklonuj($n, 'odkaz', 'odkazy', 'odkazů') . ' na články cltk.cz/cs/clanky/ vymazáno' : 'žádný odkaz na články starého webu – přeskočeno'));

        /* ---------- 7) prameny kroniky a CTC ---------- */
        $hotovo = [];
        $zmena = $preskoceno = 0;
        foreach ((array)$data['prameny'] as $p) {
            $tab = cltk_tabulka((string)$p['tabulka']);
            if (!in_array($tab, ['cltk_milniky', 'cltk_ctc'], true)) continue;
            $klic = $tab . '|' . $p['stary'];
            if (isset($hotovo[$klic])) continue;
            $hotovo[$klic] = true;
            if (preg_match_all('~uploads/([a-z0-9\-_/]+\.pdf)~i', (string)$p['novy'], $m)) {
                foreach ($m[1] as $rel) if (!$maSoubor($rel)) continue 2;
            }
            $radky = rows("SELECT id FROM $tab WHERE zdroj = ?", [$p['stary']]);
            if (!$radky) { $preskoceno++; continue; }
            foreach ($radky as $x) if (!$nanecisto) q("UPDATE $tab SET zdroj = ? WHERE id = ? AND zdroj = ?", [$p['novy'], (int)$x['id'], $p['stary']]);
            $zmena += count($radky);
        }
        $zapis("7) Prameny kroniky a CTC: $zmena " . sklonuj($zmena, 'řádek', 'řádky', 'řádků') . ' převedeno na text / soubor webu'
            . ($preskoceno ? ", přeskočeno $preskoceno (pramen už není původní)" : ''));

        /* ---------- 8) jubilejní Revue 1893–2023 ---------- */
        $s = $data['revue_special'] ?? null;
        if (is_array($s)) {
            $je = row('SELECT id, oznaceni FROM cltk_revue WHERE pdf_soubor = ? OR pdf_url = ? OR (rok = ? AND cislo = 0)', [$s['pdf_soubor'], $s['stara_url'], (int)$s['rok']]);
            if ($je) {
                $zapis('8) Jubilejní Revue ' . $s['oznaceni'] . ': už v kiosku je (číslo ' . $je['oznaceni'] . ') – přeskočeno');
            } elseif (!$maSoubor($s['pdf_soubor']) || !$maSoubor($s['obalka'])) {
                $zapis('8) Jubilejní Revue: PDF nebo obálka chybí v uploads/ – přeskočeno');
            } else {
                if (!$nanecisto) db_insert('cltk_revue', [
                    'rok' => (int)$s['rok'], 'cislo' => 0, 'oznaceni' => (string)$s['oznaceni'], 'obalka' => (string)$s['obalka'],
                    'obalka_popis' => (string)$s['obalka_popis'], 'titulky' => json_ulozit(array_values((array)$s['titulky'])),
                    'obsah' => json_ulozit(array_values((array)$s['obsah'])), 'stran' => (int)$s['stran'], 'naklad' => (string)$s['naklad'],
                    'uzaverka' => (string)$s['uzaverka'], 'pdf_url' => '', 'pdf_soubor' => (string)$s['pdf_soubor'], 'pdf_mb' => (string)$s['pdf_mb'],
                    'visible' => 1, 'poradi' => (int)val('SELECT COALESCE(MAX(poradi), -1) + 1 FROM cltk_revue'),
                ]);
                $zapis('8) Jubilejní Revue ' . $s['oznaceni'] . ' (speciální číslo, ' . (int)$s['stran'] . ' stran) přidána do kiosku Revue');
            }
        }

        /* ---------- 9) archiv klubových turnajů ---------- */
        $vlozeno = $uz = $bez = 0;
        $poradi = (int)val('SELECT COALESCE(MAX(poradi), -1) + 1 FROM cltk_dokumenty');
        foreach ((array)($data['archiv_turnaju'] ?? []) as $a) {
            if (row('SELECT id FROM cltk_dokumenty WHERE soubor = ? OR url = ?', [$a['soubor'], $a['stara_url']])) { $uz++; continue; }
            if (!$maSoubor($a['soubor'])) { $bez++; continue; }
            $radek = ['nazev' => (string)$a['nazev'], 'kategorie' => DOKUMENTY_ARCHIV, 'popis' => '', 'soubor' => (string)$a['soubor'],
                      'soubor_nazev' => (string)$a['soubor_nazev'], 'url' => '', 'v_paticce' => 0, 'paticka_text' => '', 'visible' => 1,
                      'poradi' => $poradi + (int)$a['poradi'], 'created_at' => ted(), 'updated_at' => ted()];
            if ($sl('cltk_dokumenty.rok')) $radek += ['rok' => (int)$a['rok'], 'skupina' => (string)$a['skupina'], 'stran' => (int)$a['stran']];
            if (!$nanecisto) db_insert('cltk_dokumenty', $radek);
            $vlozeno++;
        }
        $zapis('9) Archiv klubových turnajů: ' . $vlozeno . ' PDF ' . sklonuj($vlozeno, 'přidáno', 'přidána', 'přidáno')
            . ($uz ? ", $uz už v archivu je – přeskočeno" : '') . ($bez ? ", $bez chybí v uploads/ – přeskočeno" : ''));

        /* ---------- 10) rozvrhy Tenisové školy ---------- */
        foreach ((array)($data['rozvrhy'] ?? []) as $rv) {
            $pocet = (int)val("SELECT COUNT(*) FROM cltk_skola WHERE typ = 'rozvrh' AND nazev = ?", [$rv['nazev']]);
            if ($pocet > 0) { $zapis('10) Rozvrh „' . $rv['nazev'] . "“: už v modulu Tenisová škola je ($pocet hodin) – přeskočeno"); continue; }
            // rozvrh, jehož platnost už skončila (přechodný týden), by web stejně neukázal – do administrace nepatří
            if ((string)($rv['datum_do'] ?? '') !== '' && (string)$rv['datum_do'] < dnes()) {
                $zapis('10) Rozvrh „' . $rv['nazev'] . '“: platil jen do ' . cz_date((string)$rv['datum_do']) . ' – nevkládá se');
                continue;
            }
            $start = (int)val("SELECT COALESCE(MAX(poradi), -1) + 1 FROM cltk_skola WHERE typ = 'rozvrh'");
            foreach (array_values((array)$rv['radky']) as $i => $r) {
                if ($nanecisto) continue;
                db_insert('cltk_skola', [
                    'typ' => 'rozvrh', 'nazev' => (string)$rv['nazev'], 'datum_od' => $rv['datum_od'] ?: null, 'datum_do' => $rv['datum_do'] ?: null,
                    'termin_text' => '', 'den' => (string)$r['den'], 'cas' => (string)$r['cas'], 'skupina' => (string)$r['skupina'],
                    'misto' => (string)$r['misto'], 'trener' => (string)$r['trener'], 'cena' => '', 'text' => (string)$r['text'], 'odkaz' => '',
                    'visible' => 1, 'poradi' => $start + $i, 'created_at' => ted(), 'updated_at' => ted(),
                ]);
            }
            $zapis('10) Rozvrh „' . $rv['nazev'] . '“ (' . cz_range($rv['datum_od'], $rv['datum_do']) . '): ' . count((array)$rv['radky']) . ' hodin přidáno');
        }

        if ($vTransakci) { $pdo->commit(); $vTransakci = false; }
    } catch (Throwable $e) {
        if ($vTransakci && $pdo->inTransaction()) $pdo->rollBack();
        $log('CHYBA: ' . $e->getMessage());
        $log('Změny dat se vrátily (ROLLBACK). Přidané sloupce zůstaly – nevadí to, při dalším spuštění se přeskočí.');
        return false;
    }

    /* ---------- 11) kontrola: zbývající zmínky o cltk.cz ---------- */
    if (!$seed) {
        $log('');
        $odkazy = $zminky = $vypsano = 0;
        $nepresouvat = ['cltk_users', 'cltk_login_attempts', 'cltk_visits', 'cltk_visit_log', 'cltk_signups', 'cltk_prihlasky_clenstvi'];
        foreach (array_diff(db_tabulky(), $nepresouvat) as $tab) {
            foreach (rows('SELECT * FROM ' . cltk_tabulka($tab)) as $x) {
                foreach ($x as $sloupec => $v) {
                    if (!is_string($v) || stripos($v, 'cltk.cz') === false) continue;
                    $bezEmailu = (string)preg_replace('~[\w.+\-]+@(?:[\w\-]+\.)*cltk\.cz~iu', '', $v);
                    if (stripos($bezEmailu, 'cltk.cz') === false) continue;
                    $odkaz = (bool)preg_match('~(https?:)?//(?:[\w\-]+\.)*cltk\.cz~i', $bezEmailu);
                    $odkaz ? $odkazy++ : $zminky++;
                    // nanečisto jsou v databázi ještě všechny staré odkazy – stačí jejich počet
                    if ($nanecisto && $odkaz) continue;
                    if (++$vypsano > 40) continue;                       // dlouhý výpis zkrátit
                    $kde = $tab . '.' . $sloupec . ' (' . ($x['id'] ?? ($x['skey'] ?? '?')) . ')';
                    $log('11) ' . ($odkaz ? 'ODKAZ na starý web: ' : 'zmínka v textu (ne odkaz): ') . $kde . ' – „'
                        . mb_strimwidth((string)preg_replace('~\s+~u', ' ', (string)preg_replace('~^.*?(.{0,40}cltk\.cz.{0,30}).*$~su', '$1', $bezEmailu)), 0, 90, '…') . '“');
                }
            }
        }
        if ($vypsano > 40) $log('11) … a ' . ($vypsano - 40) . ' dalších');
        if ($nanecisto) {
            $log('11) Nanečisto: databáze je pořád v původním stavu – odkazů na cltk.cz / files.cltk.cz je v ní ' . $odkazy . '.');
        } else {
            $log('11) Kontrola databáze: ' . ($odkazy ? "zbývá $odkazy " . sklonuj($odkazy, 'odkaz', 'odkazy', 'odkazů') . ' na cltk.cz (viz výše – upraveno v administraci, nebo nové)' : 'žádný odkaz na cltk.cz / files.cltk.cz')
                . ($zminky ? ", $zminky " . sklonuj($zminky, 'zmínka', 'zmínky', 'zmínek') . ' v textu (e-maily @cltk.cz se nepočítají)' : ', e-maily @cltk.cz zůstávají'));
        }
        $log('');
        $log($nanecisto ? 'HOTOVO (nanečisto – nic se nezapsalo)' : 'HOTOVO');
    }
    return true;
}
