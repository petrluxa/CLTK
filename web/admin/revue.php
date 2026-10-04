<?php
/* Revue a newslettery – čísla klubového časopisu I.ČLTK Revue (cltk_revue: obálka,
   titulky, obsah čísla, PDF) a klubové newslettery (cltk_newslettery: PDF česky
   a anglicky). PDF se nahrávají na web (uploads/revue/pdf/, uploads/newslettery/);
   odkaz ven jen tehdy, když PDF leží jinde. Čísla Revue se na webu řadí samy podle
   roku a čísla, newslettery podle roku a uvnitř roku šipkami.
   Adresy: revue.php?cast=revue|newslettery, …&nova=1, revue.php?cast=revue&id=5. */
require __DIR__ . '/inc/layout.php';
$user = require_login();

/* ---------- Pomůcky modulů obsahu ----------
   Stejný blok je v modulech Trenéři, Ceníky, Tenisová škola, Areál a služby, Historie,
   Revue, Vedení, CTC a Stránky. Když je jednou převezme jádro (admin/inc/ui.php),
   použijí se jeho verze (function_exists). */
if (!function_exists('obsah_prosty')) {
    /** Prostý text z formuláře: zahodí vše, co vypadá jako HTML značka (<script>…</script>,
     *  <img onerror=…>, komentář). Na webu se text stejně escapuje přes e() – tohle je druhá
     *  pojistka pro případ, že by ho někde někdo vypsal napřímo. „děti < 10 let“ zůstane. */
    function obsah_prosty(string $s): string {
        $s = (string)preg_replace('~<\s*(script|style|iframe|object|embed|svg|math|template)\b[^>]*>.*?<\s*/\s*\1\s*>~is', '', $s);
        $s = (string)preg_replace('~<!--.*?(?:-->|$)~s', '', $s);
        $s = (string)preg_replace('~</?[a-z!?][^<>]*>~i', '', $s, -1, $pocet);
        if ($pocet > 0) $s = (string)preg_replace('/[ \t]{2,}/', ' ', $s);    // mezery po odstraněných značkách
        return trim($s);
    }
}
if (!function_exists('obsah_get')) {
    /** Parametr z adresy (GET), případně z POST: vždy řetězec (pole v adrese „?typ[]=x“ = výchozí hodnota). */
    function obsah_get(string $klic, string $vychozi = '', bool $iPost = false): string {
        $v = $_GET[$klic] ?? ($iPost ? ($_POST[$klic] ?? null) : null);
        return is_scalar($v) ? trim((string)$v) : $vychozi;
    }
}
if (!function_exists('obsah_pole')) {
    /** Prostý text z POST (vstup + obsah_prosty), zkrácený na $max znaků (0 = bez limitu). */
    function obsah_pole(string $klic, int $max = 255): string {
        $v = obsah_prosty(vstup($klic, 0));
        return $max > 0 ? mb_substr($v, 0, $max) : $v;
    }
}
if (!function_exists('obsah_inline')) {
    /** Krátký text, který smí mít <em>, <strong> a <br> (nadpis bloku, čin osobnosti…).
     *  Povolené značky se přepíšou na holé (bez atributů), ostatní zmizí. Vypisuje se html_inline(). */
    function obsah_inline(string $s): string {
        $s = (string)preg_replace('~<\s*(script|style|iframe|object|embed|svg|math|template)\b[^>]*>.*?<\s*/\s*\1\s*>~is', '', $s);
        $s = (string)preg_replace('~<!--.*?(?:-->|$)~s', '', $s);
        $s = (string)preg_replace_callback('~<\s*(/?)\s*(em|strong|b|i|br)\b[^<>]*>~i', static function (array $m): string {
            $z = strtolower($m[2]);
            if ($z === 'br') return '<br>';
            $z = $z === 'b' ? 'strong' : ($z === 'i' ? 'em' : $z);
            return '<' . $m[1] . $z . '>';
        }, $s);
        $s = (string)preg_replace('~<(?!/?(?:em|strong)>|br>)/?[a-z!?][^<>]*>~i', '', $s);
        return trim($s);
    }
}
if (!function_exists('obsah_odkaz')) {
    /** Odkaz z formuláře: '' = bez odkazu, false = nepovolená adresa (javascript: …),
     *  jinak adresa k uložení (doplní https://, stránka webu zůstane „clenstvi.php#prihlaska“). */
    function obsah_odkaz(string $u): string|false {
        $u = trim($u);
        if ($u === '') return '';
        $holy = (string)preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($u, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (preg_match('~^(javascript|data|vbscript|file):~i', $holy) || preg_match('~[<>"\']~', $u)) return false;
        if (preg_match('~^/[a-z0-9][a-z0-9\-_/]*\.php([?#].*)?$~i', $u)) $u = ltrim($u, '/');
        $n = normalizuj_url($u);
        return bezpecny_odkaz($n) !== '' ? $n : false;
    }
}
if (!function_exists('obsah_fokus')) {
    /** Ohnisko fotky „x% y%“ (object-position). Prázdné = výchozí, false = neplatné. */
    function obsah_fokus(string $f, string $vychozi = '50% 50%'): string|false {
        $f = trim((string)preg_replace('/\s+/', ' ', $f));
        if ($f === '') return $vychozi;
        // nejvýš dvě desetinná místa – sloupec fokus má VARCHAR(20)
        if (!preg_match('/^(\d{1,3}(?:\.\d{1,2})?)%\s(\d{1,3}(?:\.\d{1,2})?)%$/', $f, $m) || (float)$m[1] > 100 || (float)$m[2] > 100) return false;
        return $f;
    }
}
if (!function_exists('obsah_smazat_nepouzite')) {
    /** Smaže soubory z uploads/, na které už žádný řádek neodkazuje ($kde = [['cltk_treneri', 'foto'], …]).
     *  Volat AŽ po úspěšném zápisu do databáze (past z Liberce). Jednu fotku můžou sdílet dva
     *  řádky (trenér ve dvou týmech) – smaže se, až když ji nepoužívá nikdo. */
    function obsah_smazat_nepouzite(array $soubory, array $kde): void {
        foreach (array_unique(array_filter(array_map('strval', $soubory), static fn($s) => $s !== '')) as $s) {
            foreach ($kde as [$tabulka, $sloupec]) {
                if (!preg_match('/^[a-z_]+$/', (string)$sloupec)) continue 2;
                if ((int)val('SELECT COUNT(*) FROM ' . cltk_tabulka((string)$tabulka) . ' WHERE ' . $sloupec . ' = ?', [$s]) > 0) continue 2;
            }
            delete_upload($s);
        }
    }
}
if (!function_exists('obsah_chyba')) {
    /** Nápověda pod polem doplněná o chybovou hlášku (vrací HTML; $hint escapujte sami). */
    function obsah_chyba(array $chyby, string $pole, string $hint = ''): string {
        $ch = isset($chyby[$pole]) ? '<span class="pole-chyba">' . e($chyby[$pole]) . '</span>' : '';
        return $ch . ($ch !== '' && $hint !== '' ? ' ' : '') . $hint;
    }
}
if (!function_exists('obsah_chyby_box')) {
    /** Souhrn chyb nad formulářem (po neúspěšném uložení se stránka vypíše znovu s vyplněnými poli). */
    function obsah_chyby_box(array $chyby, bool $bylSoubor = false): string {
        if (!$chyby) return '';
        $t = implode(' ', array_unique(array_map('strval', $chyby)));
        if ($bylSoubor) $t .= ' Vybraný soubor se neuložil – vyberte ho prosím znovu.';
        return '<div class="flash err" role="alert"><b>Neuloženo.</b> ' . e($t) . '</div>';
    }
}
if (!function_exists('obsah_byl_soubor')) {
    /** Poslal formulář nějaký soubor? (po chybě je ho potřeba vybrat znovu) */
    function obsah_byl_soubor(): bool {
        foreach ($_FILES as $f) {
            if (is_array($f) && (int)($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) return true;
        }
        return false;
    }
}
if (!function_exists('obsah_assets')) {
    /** Styl a skript modulů obsahu (vypsat hned za admin_head()). */
    function obsah_assets(): string {
        return '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-obsah.css')) . '">'
             . '<script src="' . e(BASE_PATH . verze('admin/assets/admin-obsah.js')) . '" defer></script>';
    }
}
if (!function_exists('obsah_nahled_foto')) {
    /** Miniatura do tabulky (nebo zástupný rámeček). */
    function obsah_nahled_foto(?string $rel, string $trida = 'thumb-sm', string $fokus = '', string $prazdne = 'bez fotky'): string {
        $rel = (string)$rel;
        if ($rel !== '' && is_file(UPLOAD_DIR . '/' . $rel)) {
            return '<img class="' . e($trida) . '" src="' . e(upload_url($rel)) . '" alt="" loading="lazy"'
                 . ($fokus !== '' ? ' style="object-position:' . e($fokus) . '"' : '') . '>';
        }
        return '<span class="thumb-ph">' . e($rel !== '' ? 'chybí soubor' : $prazdne) . '</span>';
    }
}
/* ---------- konec pomůcek ---------- */

const REVUE_PRAZDNYCH_RADKU = 2;     // prázdné řádky obsahu čísla navíc (další přidá tlačítko)

$cast = obsah_get('cast', 'revue', true);
if (!in_array($cast, ['revue', 'newslettery'], true)) $cast = 'revue';
$tabulka = $cast === 'revue' ? 'cltk_revue' : 'cltk_newslettery';
$id = (int)($_GET['id'] ?? 0);
$z  = $id ? row('SELECT * FROM ' . $tabulka . ' WHERE id = ?', [$id]) : null;
if ($id && !$z) redirect('revue.php?cast=' . $cast, 'Číslo nebylo nalezeno – možná bylo mezitím smazáno.', 'err');
$rezim = $z ? 'uprava' : (!empty($_GET['nova']) ? 'nova' : 'seznam');
$seznamUrl = 'revue.php?cast=' . $cast;
const REVUE_SOUBORY = [['cltk_revue', 'obalka'], ['cltk_revue', 'pdf_soubor']];
const NEWSLETTER_SOUBORY = [['cltk_newslettery', 'pdf_cs_soubor'], ['cltk_newslettery', 'pdf_en_soubor']];

if ($cast === 'revue') {
    $f = [
        'rok' => (string)($z['rok'] ?? date('Y')), 'cislo' => (string)($z['cislo'] ?? ''), 'oznaceni' => (string)($z['oznaceni'] ?? ''),
        'obalka' => (string)($z['obalka'] ?? ''), 'obalka_popis' => (string)($z['obalka_popis'] ?? ''),
        'titulky' => implode("\n", array_map('strval', json_pole((string)($z['titulky'] ?? '')))),
        'obsah' => array_values(array_filter(json_pole((string)($z['obsah'] ?? '')), 'is_array')),
        'stran' => isset($z['stran']) && $z['stran'] !== null ? (string)$z['stran'] : '', 'naklad' => (string)($z['naklad'] ?? ''),
        'uzaverka' => (string)($z['uzaverka'] ?? ''), 'pdf_url' => (string)($z['pdf_url'] ?? ''), 'pdf_soubor' => (string)($z['pdf_soubor'] ?? ''),
        'pdf_mb' => (string)($z['pdf_mb'] ?? ''), 'visible' => (int)($z['visible'] ?? 1),
    ];
} else {
    $f = [
        'rok' => (string)($z['rok'] ?? date('Y')), 'cislo' => (string)($z['cislo'] ?? ''), 'oznaceni' => (string)($z['oznaceni'] ?? ''),
        'nazev' => (string)($z['nazev'] ?? ''), 'pdf_cs' => (string)($z['pdf_cs'] ?? ''), 'pdf_en' => (string)($z['pdf_en'] ?? ''),
        'pdf_cs_soubor' => (string)($z['pdf_cs_soubor'] ?? ''), 'pdf_en_soubor' => (string)($z['pdf_en_soubor'] ?? ''),
        'visible' => (int)($z['visible'] ?? 1),
    ];
}
$chyby = [];
$bylSoubor = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek($seznamUrl);
    $akce = vstup('action', 20);

    if ($akce === 'ulozit' && $cast === 'revue') {
        $bylSoubor = obsah_byl_soubor();
        $obsah = [];
        $strany = is_array($_POST['obsah_strana'] ?? null) ? array_values($_POST['obsah_strana']) : [];
        $tituly = is_array($_POST['obsah_titulek'] ?? null) ? array_values($_POST['obsah_titulek']) : [];
        foreach ($tituly as $i => $t) {
            $t = is_scalar($t) ? mb_substr(obsah_prosty((string)$t), 0, 200) : '';
            $s = is_scalar($strany[$i] ?? '') ? mb_substr(obsah_prosty((string)($strany[$i] ?? '')), 0, 10) : '';
            if ($t !== '') $obsah[] = [$s, $t];
        }
        // když jsou všechny strany čísla, seřadit podle strany (nový řádek může být kdekoli)
        if ($obsah && count(array_filter($obsah, static fn($o) => preg_match('/^\d+$/', $o[0]))) === count($obsah)) {
            usort($obsah, static fn($a, $b) => (int)$a[0] <=> (int)$b[0]);
        }
        $f = [
            'rok' => vstup('rok', 6), 'cislo' => vstup('cislo', 3), 'oznaceni' => obsah_pole('oznaceni', 20), 'obalka' => $f['obalka'],
            'obalka_popis' => obsah_pole('obalka_popis', 255), 'titulky' => obsah_pole('titulky', 2000), 'obsah' => $obsah,
            'stran' => vstup('stran', 5), 'naklad' => obsah_pole('naklad', 60), 'uzaverka' => obsah_pole('uzaverka', 60),
            'pdf_url' => vstup('pdf_url', 255), 'pdf_soubor' => $f['pdf_soubor'], 'pdf_mb' => obsah_pole('pdf_mb', 10),
            'visible' => vstup_bool('visible'),
        ];
        if (!preg_match('/^(19|20)\d\d$/', $f['rok'])) $chyby['rok'] = 'Rok zapište čtyřmi číslicemi (např. 2026).';
        if (!preg_match('/^[0-9]$/', $f['cislo'])) $chyby['cislo'] = 'Číslo v roce je 1 nebo 2 (Revue vychází dvakrát ročně), 0 = speciální číslo.';
        if ($f['cislo'] === '0' && $f['oznaceni'] === '') $chyby['oznaceni'] = 'U speciálního čísla vyplňte označení (např. „1893–2023“).';
        if ($f['stran'] !== '' && !preg_match('/^\d{1,4}$/', $f['stran'])) $chyby['stran'] = 'Počet stran zapište číslem.';
        if (!$chyby && row('SELECT id FROM cltk_revue WHERE rok = ? AND cislo = ? AND id <> ?', [(int)$f['rok'], (int)$f['cislo'], $id])) {
            $chyby['cislo'] = 'Číslo ' . $f['cislo'] . '/' . $f['rok'] . ' už v seznamu je.';
        }
        $pdfUrl = obsah_odkaz($f['pdf_url']);
        if ($pdfUrl === false) $chyby['pdf_url'] = 'Odkaz na PDF musí začínat https://.';
        if ($f['oznaceni'] === '' && !isset($chyby['rok']) && !isset($chyby['cislo'])) $f['oznaceni'] = sprintf('%02d/%d', (int)$f['cislo'], (int)$f['rok']);

        if (!$chyby) {
            $obalka = admin_obrazek('obalka', $z['obalka'] ?? '', 'revue', 1200, 1600, 'revue-' . $f['rok'] . '-' . $f['cislo']);
            if ($obalka['chyba'] !== '') $chyby['obalka'] = $obalka['chyba'];
        }
        if (!$chyby) {
            $pdf = admin_pdf('pdf_soubor', $z['pdf_soubor'] ?? '', '', 'revue');
            if ($pdf['chyba'] === '' && mb_strlen($pdf['soubor']) > 255) {          // sloupec má VARCHAR(255)
                delete_upload($pdf['soubor']);
                $pdf['chyba'] = 'Název souboru PDF je příliš dlouhý – přejmenujte ho prosím (např. revue-2026-1.pdf) a nahrajte znovu.';
            }
            if ($pdf['chyba'] !== '') {
                $chyby['pdf_soubor'] = $pdf['chyba'];
                if ($obalka['soubor'] !== ($z['obalka'] ?? '')) delete_upload($obalka['soubor']);   // nová obálka by zůstala viset
            }
        }
        if (!$chyby) {
            $mb = $f['pdf_mb'];
            if ($pdf['soubor'] !== '' && $pdf['soubor'] !== ($z['pdf_soubor'] ?? '')) {
                $mb = number_format((float)@filesize(UPLOAD_DIR . '/' . $pdf['soubor']) / 1048576, 1, ',', '');
            }
            $data = [
                'rok' => (int)$f['rok'], 'cislo' => (int)$f['cislo'], 'oznaceni' => $f['oznaceni'], 'obalka' => $obalka['soubor'],
                'obalka_popis' => $f['obalka_popis'], 'titulky' => json_ulozit(radky($f['titulky'])), 'obsah' => json_ulozit($obsah),
                'stran' => $f['stran'] === '' ? null : (int)$f['stran'], 'naklad' => $f['naklad'], 'uzaverka' => $f['uzaverka'],
                'pdf_url' => (string)$pdfUrl, 'pdf_soubor' => $pdf['soubor'], 'pdf_mb' => $mb, 'visible' => $f['visible'],
            ];
            if ($z) db_update('cltk_revue', $id, $data);
            else { $data['poradi'] = admin_dalsi_poradi('cltk_revue'); $id = db_insert('cltk_revue', $data); }
            obsah_smazat_nepouzite(array_merge($obalka['smazat'], $pdf['smazat']), REVUE_SOUBORY);
            redirect($seznamUrl . '#r' . $id, 'Číslo Revue ' . $f['oznaceni'] . ($z ? ' je uložené.' : ' je přidané.'));
        }
        $rezim = $z ? 'uprava' : 'nova';
    } elseif ($akce === 'ulozit') {
        $bylSoubor = obsah_byl_soubor();
        $f = [
            'rok' => vstup('rok', 6), 'cislo' => obsah_pole('cislo', 10), 'oznaceni' => obsah_pole('oznaceni', 40),
            'nazev' => obsah_pole('nazev', 160), 'pdf_cs' => vstup('pdf_cs', 255), 'pdf_en' => vstup('pdf_en', 255),
            'pdf_cs_soubor' => $f['pdf_cs_soubor'], 'pdf_en_soubor' => $f['pdf_en_soubor'],
            'visible' => vstup_bool('visible'),
        ];
        if (!preg_match('/^(19|20)\d\d$/', $f['rok'])) $chyby['rok'] = 'Rok zapište čtyřmi číslicemi (např. 2026).';
        if ($f['cislo'] === '' && $f['oznaceni'] === '') $chyby['cislo'] = 'Vyplňte číslo vydání (nebo aspoň označení, např. „130 let“).';
        $cs = obsah_odkaz($f['pdf_cs']);
        $en = obsah_odkaz($f['pdf_en']);
        if ($cs === false) $chyby['pdf_cs'] = 'Odkaz musí začínat https://.';
        if ($en === false) $chyby['pdf_en'] = 'Odkaz musí začínat https://.';
        if ($f['oznaceni'] === '' && !isset($chyby['rok'])) $f['oznaceni'] = $f['cislo'] . '/' . $f['rok'];
        $pdfCs = $pdfEn = null;
        if (!$chyby) {
            $pdfCs = admin_pdf('pdf_cs_soubor', $z['pdf_cs_soubor'] ?? '', '', 'newslettery');
            if ($pdfCs['chyba'] !== '') $chyby['pdf_cs_soubor'] = $pdfCs['chyba'];
        }
        if (!$chyby) {
            $pdfEn = admin_pdf('pdf_en_soubor', $z['pdf_en_soubor'] ?? '', '', 'newslettery');
            if ($pdfEn['chyba'] !== '') {
                $chyby['pdf_en_soubor'] = $pdfEn['chyba'];
                if ($pdfCs['soubor'] !== ($z['pdf_cs_soubor'] ?? '')) delete_upload($pdfCs['soubor']);   // nové české PDF by zůstalo viset
            }
        }
        if (!$chyby && $pdfCs['soubor'] === '' && $pdfEn['soubor'] === '' && $cs === '' && $en === '') {
            $chyby['pdf_cs_soubor'] = 'Nahrajte aspoň jedno PDF (česky nebo anglicky), případně vyplňte odkaz.';
        }
        if (!$chyby) {
            $data = ['rok' => (int)$f['rok'], 'cislo' => $f['cislo'], 'oznaceni' => $f['oznaceni'], 'nazev' => $f['nazev'],
                     'pdf_cs' => (string)$cs, 'pdf_en' => (string)$en, 'pdf_cs_soubor' => $pdfCs['soubor'], 'pdf_en_soubor' => $pdfEn['soubor'],
                     'visible' => $f['visible']];
            if ($z) {
                if ((int)$z['rok'] !== (int)$f['rok']) $data['poradi'] = admin_dalsi_poradi('cltk_newslettery', 'rok', (int)$f['rok']);
                db_update('cltk_newslettery', $id, $data);
            } else {
                // nové vydání na začátek svého roku (nejnovější nahoře)
                q('UPDATE cltk_newslettery SET poradi = poradi + 1 WHERE rok = ?', [(int)$f['rok']]);
                $data['poradi'] = 0;
                $id = db_insert('cltk_newslettery', $data);
            }
            obsah_smazat_nepouzite(array_merge($pdfCs['smazat'], $pdfEn['smazat']), NEWSLETTER_SOUBORY);   // až po zápisu
            redirect($seznamUrl . '#n' . $id, 'Newsletter ' . $f['oznaceni'] . ($z ? ' je uložený.' : ' je přidaný.'));
        }
        $rezim = $z ? 'uprava' : 'nova';
    } else {
        $rid = (int)vstup_int('id');
        $x = $rid ? row('SELECT * FROM ' . $tabulka . ' WHERE id = ?', [$rid]) : null;
        if (!$x) redirect($seznamUrl, 'Číslo nebylo nalezeno – možná bylo mezitím smazáno.', 'err');
        $popis = ($cast === 'revue' ? 'Číslo Revue ' : 'Newsletter ') . $x['oznaceni'];
        [$skryty, $smazany] = $cast === 'revue' ? ['skryté', 'bylo smazáno'] : ['skrytý', 'byl smazán'];
        $kotva = ($cast === 'revue' ? '#r' : '#n') . $rid;
        if ($akce === 'prepnout') {
            $novy = admin_prepni($tabulka, $rid);
            redirect($seznamUrl . $kotva, $popis . ' je teď na webu ' . ($novy ? 'vidět.' : $skryty . '.'));
        }
        if (($akce === 'nahoru' || $akce === 'dolu') && $cast === 'newslettery') {
            admin_posun('cltk_newslettery', $rid, $akce === 'nahoru' ? -1 : 1, 'rok');
            redirect($seznamUrl . $kotva, 'Pořadí bylo změněno.');
        }
        if ($akce === 'smazat') {
            q('DELETE FROM ' . $tabulka . ' WHERE id = ?', [$rid]);
            if ($cast === 'revue') obsah_smazat_nepouzite([(string)$x['obalka'], (string)$x['pdf_soubor']], REVUE_SOUBORY);
            else obsah_smazat_nepouzite([(string)($x['pdf_cs_soubor'] ?? ''), (string)($x['pdf_en_soubor'] ?? '')], NEWSLETTER_SOUBORY);
            redirect($seznamUrl, $popis . ' ' . $smazany . '.');
        }
        redirect($seznamUrl, 'Neznámý požadavek.', 'err');
    }
}

$pocetR = (int)val('SELECT COUNT(*) FROM cltk_revue');
$pocetN = (int)val('SELECT COUNT(*) FROM cltk_newslettery');
$zalozky = ['revue.php?cast=revue' => 'I.ČLTK Revue · ' . $pocetR, 'revue.php?cast=newslettery' => 'Newslettery · ' . $pocetN];

if ($rezim === 'seznam') {
    admin_head('Revue a newslettery', $user, [
        'podnadpis' => 'Archiv klubového časopisu I.ČLTK Revue a newsletterů na stránce Revue. Úvodní texty stránky jsou ve <a href="stranky.php?stranka=revue">Stránkách</a>.',
        'akce' => '<a class="btn btn-primary" href="revue.php?cast=' . e($cast) . '&amp;nova=1">' . ($cast === 'revue' ? 'Přidat číslo Revue' : 'Přidat newsletter') . '</a>',
    ]);
} else {
    $nadpis = $cast === 'revue' ? ($z ? 'Revue ' . $z['oznaceni'] : 'Nové číslo Revue') : ($z ? 'Newsletter ' . $z['oznaceni'] : 'Nový newsletter');
    admin_head($nadpis, $user, ['zpet' => [$seznamUrl, $cast === 'revue' ? 'I.ČLTK Revue' : 'Newslettery'], 'sirka' => 'uzka']);
}
echo obsah_assets();

if ($rezim === 'seznam'):
?>
<?= admin_zalozky($zalozky, $seznamUrl) ?>
<?php if ($cast === 'revue'):
    $radky = rows('SELECT * FROM cltk_revue ORDER BY rok DESC, cislo DESC, poradi, id');
?>
<section class="panel">
  <div class="panel-head">
    <h2>I.ČLTK Revue <small><?= cislo(count($radky)) ?> čísel · nejnovější nahoře</small></h2>
    <a class="btn btn-sm btn-ghost" href="<?= e(url('revue.php')) ?>" target="_blank" rel="noopener">Zobrazit na webu <span aria-hidden="true">↗</span></a>
  </div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím žádné číslo', '', '<a class="btn btn-primary" href="revue.php?cast=revue&amp;nova=1">Přidat číslo Revue</a>') ?>
  <?php else: ?>
  <div class="tbl-wrap tbl-karty">
    <table>
      <thead><tr><th>Obálka</th><th>Číslo</th><th>Na obálce</th><th>PDF</th><th>Stav</th><th class="right">Akce</th></tr></thead>
      <tbody>
      <?php foreach ($radky as $r): $rid = (int)$r['id']; $tit = json_pole((string)$r['titulky']); ?>
        <tr id="r<?= $rid ?>"<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
          <td data-label="Obálka"><?= obsah_nahled_foto($r['obalka'], 'thumb-obalka', '', 'bez obálky') ?></td>
          <td data-label="Číslo" class="td-nazev"><b><a href="revue.php?cast=revue&amp;id=<?= $rid ?>"><?= e($r['oznaceni']) ?></a></b>
            <small><?= $r['stran'] ? cislo((int)$r['stran']) . ' stran' : '' ?><?= $r['naklad'] !== '' ? ' · ' . e($r['naklad']) : '' ?></small></td>
          <td data-label="Na obálce" class="td-mala"><?= $tit ? e(implode(' · ', array_slice(array_map('strval', $tit), 0, 2))) : '–' ?>
            <br><?= cislo(count(json_pole((string)$r['obsah']))) ?> položek obsahu</td>
          <td data-label="PDF" class="td-mala">
            <?php if ($r['pdf_soubor'] !== ''): ?><?= is_file(UPLOAD_DIR . '/' . $r['pdf_soubor']) ? '<a href="' . e(upload_url($r['pdf_soubor'])) . '" target="_blank" rel="noopener">' . badge('nahrané PDF', 'navy') . '</a>' : badge('soubor chybí', 'err') ?>
            <?php elseif ($r['pdf_url'] !== ''): ?><a href="<?= e(bezpecny_odkaz($r['pdf_url'])) ?>" target="_blank" rel="noopener">odkaz ↗</a>
            <?php else: ?><?= badge('chybí', 'warn') ?><?php endif; ?>
            <?= $r['pdf_mb'] !== '' ? e($r['pdf_mb']) . ' MB' : '' ?>
          </td>
          <td data-label="Stav"><?= stav_badge($r['visible']) ?></td>
          <td data-label="Akce" class="right"><div class="akce-radku">
            <a class="btn btn-sm btn-ghost" href="revue.php?cast=revue&amp;id=<?= $rid ?>">Upravit</a>
            <?= tlacitko_prepnout($rid, $r['visible'], ['cast' => 'revue']) ?>
            <?= tlacitko_smazat($rid, 'Smazat Revue ' . $r['oznaceni'] . ' i s obálkou' . ($r['pdf_soubor'] !== '' ? ' a nahraným PDF' : '') . '? Nejde to vrátit.', ['cast' => 'revue']) ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<p class="hint">Pořadí čísel je dané rokem a číslem v roce – nové číslo se zařadí samo.</p>

<?php else:
    $radky = rows('SELECT * FROM cltk_newslettery ORDER BY rok DESC, poradi, id');
    $vRoce = [];
    foreach ($radky as $r) $vRoce[(int)$r['rok']][] = (int)$r['id'];
?>
<section class="panel">
  <div class="panel-head">
    <h2>Newslettery <small><?= cislo(count($radky)) ?> vydání</small></h2>
    <a class="btn btn-sm btn-ghost" href="<?= e(url('revue.php')) ?>#newslettery" target="_blank" rel="noopener">Zobrazit na webu <span aria-hidden="true">↗</span></a>
  </div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím žádný newsletter', '', '<a class="btn btn-primary" href="revue.php?cast=newslettery&amp;nova=1">Přidat newsletter</a>') ?>
  <?php else: ?>
  <div class="tbl-wrap tbl-karty">
    <table>
      <thead><tr><th>Vydání</th><th>PDF</th><th>Stav</th><th class="right">Akce</th></tr></thead>
      <tbody>
      <?php $rok = null; foreach ($radky as $r): $rid = (int)$r['id']; $sk = $vRoce[(int)$r['rok']]; $pos = array_search($rid, $sk, true);
        if ((int)$r['rok'] !== $rok): $rok = (int)$r['rok']; ?>
        <tr class="tr-skupina"><td colspan="4"><?= (int)$rok ?></td></tr>
      <?php endif; ?>
        <tr id="n<?= $rid ?>"<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
          <td data-label="Vydání" class="td-nazev"><b><a href="revue.php?cast=newslettery&amp;id=<?= $rid ?>"><?= e($r['oznaceni']) ?></a></b><?php if ($r['nazev'] !== ''): ?><small><?= e($r['nazev']) ?></small><?php endif; ?></td>
          <td data-label="PDF" class="td-mala">
            <?php foreach (['cs' => ['česky', 'CS chybí'], 'en' => ['anglicky', 'EN chybí']] as $j => [$jazyk, $bez]):
                $soubor = (string)($r['pdf_' . $j . '_soubor'] ?? '');
                $u = $soubor !== '' ? upload_url($soubor) : bezpecny_odkaz((string)$r['pdf_' . $j]); ?>
              <?= $j === 'en' ? ' · ' : '' ?><?= $u !== '' ? '<a href="' . e($u) . '" target="_blank" rel="noopener">' . $jazyk . ($soubor !== '' ? '' : ' (odkaz)') . ' ↗</a>' : badge($bez, 'off') ?>
            <?php endforeach; ?>
          </td>
          <td data-label="Stav"><?= stav_badge($r['visible']) ?></td>
          <td data-label="Akce" class="right"><div class="akce-radku">
            <?php if (count($sk) > 1): ?><?= tlacitka_poradi($rid, $pos === 0, $pos === count($sk) - 1, ['cast' => 'newslettery']) ?><?php endif; ?>
            <a class="btn btn-sm btn-ghost" href="revue.php?cast=newslettery&amp;id=<?= $rid ?>">Upravit</a>
            <?= tlacitko_prepnout($rid, $r['visible'], ['cast' => 'newslettery']) ?>
            <?= tlacitko_smazat($rid, 'Smazat newsletter ' . $r['oznaceni'] . '? Nejde to vrátit.', ['cast' => 'newslettery']) ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<p class="hint">Newslettery se řadí podle roku (nejnovější nahoře); šipkami měníte pořadí uvnitř roku. Nové vydání se zařadí na začátek svého roku.</p>
<?php endif; ?>

<?php elseif ($cast === 'revue'): /* ---------- formulář čísla Revue ---------- */
    $obsahRadky = $f['obsah'];
    for ($i = 0; $i < REVUE_PRAZDNYCH_RADKU; $i++) $obsahRadky[] = ['', ''];
?>
<?= obsah_chyby_box($chyby, $bylSoubor) ?>
<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="cast" value="revue">
      <?= pole_radek([
            pole_text('rok', 'Rok', $f['rok'], ['type' => 'number', 'required' => true, 'attrs' => ['min' => 1990, 'max' => 2100, 'inputmode' => 'numeric'], 'hint' => obsah_chyba($chyby, 'rok')]),
            pole_text('cislo', 'Číslo v roce', $f['cislo'], ['type' => 'number', 'required' => true, 'attrs' => ['min' => 0, 'max' => 9, 'inputmode' => 'numeric'], 'hint' => obsah_chyba($chyby, 'cislo', '1 = jarní, 2 = podzimní číslo, 0 = speciální číslo (jubilejní Revue).')]),
            pole_text('oznaceni', 'Označení', $f['oznaceni'], ['maxlength' => 20, 'placeholder' => '01/2026', 'hint' => obsah_chyba($chyby, 'oznaceni', 'Prázdné = složí se z čísla a roku (u speciálního čísla vyplňte, např. „1893–2023“).')]),
          ], 3) ?>
      <?= pole_obrazek('obalka', 'Obálka', $f['obalka'], ['nahled' => 'ctverec', 'hint' => obsah_chyba($chyby, 'obalka', 'Obrázek titulní strany (JPG, PNG). Zmenší se sám.' . ($f['obalka'] !== '' ? ' Nový obrázek nahradí stávající.' : ''))]) ?>
      <?= pole_text('obalka_popis', 'Kdo je na obálce', $f['obalka_popis'], ['maxlength' => 255, 'placeholder' => 'Karolína Muchová s trofejí… (foto …)']) ?>
      <?= pole_textarea('titulky', 'Titulky na obálce', $f['titulky'], ['rows' => 3, 'hint' => 'Co řádek, to jeden titulek.']) ?>

      <fieldset>
        <legend>Obsah čísla</legend>
        <div class="tbl-wrap tbl-pole">
          <table>
            <thead><tr><th style="width:90px">Strana</th><th>Článek</th></tr></thead>
            <tbody id="revue-obsah">
            <?php foreach ($obsahRadky as $o): $prazdny = ($o[0] ?? '') === '' && ($o[1] ?? '') === ''; ?>
              <tr<?= $prazdny ? ' class="radek-novy"' : '' ?>>
                <td><input type="text" name="obsah_strana[]" value="<?= e((string)($o[0] ?? '')) ?>" maxlength="10" inputmode="numeric" aria-label="Strana" style="min-width:0"></td>
                <td class="sirsi"><input type="text" name="obsah_titulek[]" value="<?= e((string)($o[1] ?? '')) ?>" maxlength="200" aria-label="Název článku"<?= $prazdny ? ' placeholder="nový článek…"' : '' ?>></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="btn-row pridat-radek"><button type="button" class="btn btn-sm btn-ghost" data-pridat-radek="revue-obsah" hidden>+ Další řádek</button></div>
        <p class="hint">Řádek s prázdným názvem článku se smaže. Když jsou všechny strany čísla, obsah se seřadí podle strany.</p>
      </fieldset>

      <?= pole_radek([
            pole_text('stran', 'Počet stran', $f['stran'], ['type' => 'number', 'attrs' => ['min' => 1, 'max' => 999, 'inputmode' => 'numeric'], 'hint' => obsah_chyba($chyby, 'stran')]),
            pole_text('naklad', 'Náklad', $f['naklad'], ['maxlength' => 60, 'placeholder' => '400 kusů']),
            pole_text('uzaverka', 'Uzávěrka', $f['uzaverka'], ['maxlength' => 60, 'placeholder' => '24. dubna 2026']),
          ], 3) ?>

      <fieldset>
        <legend>PDF čísla</legend>
        <div class="form-mrizka">
          <?= pole_pdf('pdf_soubor', 'Nahrát PDF', $f['pdf_soubor'], basename($f['pdf_soubor']), ['sirka' => 'cela', 'hint' => obsah_chyba($chyby, 'pdf_soubor',
                'Jen PDF, nejvýš ' . e(velikost_text(upload_limit_bajtu())) . '. Nahrané PDF má přednost před odkazem. Větší soubor zmenšete (export „pro web“, obrázky 150 dpi) a nahrajte znovu.')]) ?>
          <?= pole_text('pdf_url', 'Nebo odkaz na PDF jinde', $f['pdf_url'], ['type' => 'url', 'maxlength' => 255, 'placeholder' => 'https://…', 'hint' => obsah_chyba($chyby, 'pdf_url', 'Jen když PDF leží na jiném webu.')]) ?>
          <?= pole_text('pdf_mb', 'Velikost (MB)', $f['pdf_mb'], ['maxlength' => 10, 'placeholder' => '13,3', 'hint' => 'U nahraného PDF se doplní sama.']) ?>
        </div>
      </fieldset>
      <?= pole_check('visible', 'Zobrazit na webu', (bool)$f['visible']) ?>
      <?= tlacitka_formulare($z ? 'Uložit změny' : 'Přidat číslo', $seznamUrl, 'Zpět bez uložení') ?>
    </form>
  </div>
</section>
<?php if ($z): ?>
  <section class="panel panel--nebezpeci">
    <div class="panel-head"><h2>Smazat číslo</h2></div>
    <div class="panel-body"><div class="btn-row">
      <?= tlacitko_smazat($id, 'Opravdu smazat Revue ' . $z['oznaceni'] . ' i s obálkou? Nejde to vrátit.', ['cast' => 'revue'], 'Smazat číslo') ?>
    </div></div>
  </section>
<?php endif; ?>

<?php else: /* ---------- formulář newsletteru ---------- */ ?>
<?= obsah_chyby_box($chyby, $bylSoubor) ?>
<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="cast" value="newslettery">
      <?= pole_radek([
            pole_text('rok', 'Rok', $f['rok'], ['type' => 'number', 'required' => true, 'attrs' => ['min' => 2000, 'max' => 2100, 'inputmode' => 'numeric'], 'hint' => obsah_chyba($chyby, 'rok')]),
            pole_text('cislo', 'Číslo', $f['cislo'], ['maxlength' => 10, 'placeholder' => '4', 'hint' => obsah_chyba($chyby, 'cislo')]),
            pole_text('oznaceni', 'Označení', $f['oznaceni'], ['maxlength' => 40, 'placeholder' => '4/2026', 'hint' => 'Prázdné = „číslo/rok“.']),
          ], 3) ?>
      <?= pole_text('nazev', 'Název (nepovinný)', $f['nazev'], ['maxlength' => 160, 'placeholder' => 'Speciální vydání ke 130 letům klubu']) ?>
      <fieldset>
        <legend>PDF česky</legend>
        <div class="form-mrizka">
          <?= pole_pdf('pdf_cs_soubor', 'Nahrát PDF česky', $f['pdf_cs_soubor'], basename($f['pdf_cs_soubor']), ['sirka' => 'cela',
                'hint' => obsah_chyba($chyby, 'pdf_cs_soubor', 'Jen PDF, nejvýš ' . e(velikost_text(upload_limit_bajtu())) . '. Nahrané PDF má přednost před odkazem.')]) ?>
          <?= pole_text('pdf_cs', 'Nebo odkaz na PDF jinde', $f['pdf_cs'], ['type' => 'url', 'maxlength' => 255, 'placeholder' => 'https://…', 'hint' => obsah_chyba($chyby, 'pdf_cs', 'Jen když PDF leží na jiném webu.')]) ?>
        </div>
      </fieldset>
      <fieldset>
        <legend>PDF anglicky</legend>
        <div class="form-mrizka">
          <?= pole_pdf('pdf_en_soubor', 'Nahrát PDF anglicky', $f['pdf_en_soubor'], basename($f['pdf_en_soubor']), ['sirka' => 'cela',
                'hint' => obsah_chyba($chyby, 'pdf_en_soubor', 'Nepovinné – anglická verze, když vyšla.')]) ?>
          <?= pole_text('pdf_en', 'Nebo odkaz na PDF jinde', $f['pdf_en'], ['type' => 'url', 'maxlength' => 255, 'placeholder' => 'https://…', 'hint' => obsah_chyba($chyby, 'pdf_en')]) ?>
        </div>
      </fieldset>
      <?= pole_check('visible', 'Zobrazit na webu', (bool)$f['visible']) ?>
      <?= tlacitka_formulare($z ? 'Uložit změny' : 'Přidat newsletter', $seznamUrl, 'Zpět bez uložení') ?>
    </form>
  </div>
</section>
<?php if ($z): ?>
  <section class="panel panel--nebezpeci">
    <div class="panel-head"><h2>Smazat newsletter</h2></div>
    <div class="panel-body"><div class="btn-row">
      <?= tlacitko_smazat($id, 'Opravdu smazat newsletter ' . $z['oznaceni'] . '? Nejde to vrátit.', ['cast' => 'newslettery'], 'Smazat newsletter') ?>
    </div></div>
  </section>
<?php endif; ?>
<?php endif; ?>

<?php admin_foot(); ?>
