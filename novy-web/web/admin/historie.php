<?php
/* Historie – čtyři části stránky historie.php (+ triptych na úvodu):
     kronika   – milníky (cltk_milniky), řadí se podle roku, šipky jen v rámci roku,
     triptych  – „Tři wimbledonské trávy“ (cltk_triptych), sety = JSON,
     osobnosti – medailony „Od Žemly po Muchovou“ (cltk_osobnosti), čin smí <em>,
     deska     – Zlatá deska (cltk_deska_zaznamy) po kategoriích, řádky desky a výčty jmen.
   Texty k záložkám desky a hlavičky sekcí jsou ve Stránkách (stránka historie).
   Adresy: historie.php?cast=deska&kat=oh (seznam), …&nova=1, historie.php?cast=kronika&id=5. */
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

const HIST_CASTI = [
    'kronika'   => ['Kronika', 'cltk_milniky', 'milník'],
    'triptych'  => ['Tři wimbledonské trávy', 'cltk_triptych', 'panel triptychu'],
    'osobnosti' => ['Osobnosti', 'cltk_osobnosti', 'medailon'],
    'deska'     => ['Zlatá deska', 'cltk_deska_zaznamy', 'záznam desky'],
];
const HIST_DESKA = [
    'grandslam'  => 'Grand Slam',
    'cestni'     => 'Čestní členové',
    'mistri'     => 'Mistři republiky',
    'oh'         => 'Olympijské hry',
    'zasluzili'  => 'Zasloužilí členové',
    'prezidenti' => 'Prezidenti',
];
const HIST_SKUPINY = ['hlavni' => 'Řádek desky (rok · jméno · čin)', 'jmena' => 'Jen jméno do souvislého výčtu'];
/* Od 8. 10. 2026 (postřehy klienta) web neukazuje „V den titulu hrál/a za“ u triptychu (hral_za, hral_za_text)
   ani dvojí metr u Grand Slamu (metr). Formuláře tato pole nemají a při uložení nechávají uloženou hodnotu. */
const HIST_JISTOTA_OSOB = ['overeno' => 'Ověřeno', 'klub' => 'Podle klubu'];
/** Soubory fotek mohou ležet v téže složce – před smazáním hlídat všechny tři tabulky. */
const HIST_FOTKY = [['cltk_milniky', 'foto'], ['cltk_triptych', 'foto'], ['cltk_osobnosti', 'foto']];

$cast = obsah_get('cast', 'kronika', true);
if (!isset(HIST_CASTI[$cast])) $cast = 'kronika';
[$castNazev, $tabulka, $castJedn] = HIST_CASTI[$cast];
$kat = obsah_get('kat', 'grandslam', true);
if (!isset(HIST_DESKA[$kat])) $kat = 'grandslam';

$id = (int)($_GET['id'] ?? 0);
$z  = $id ? row('SELECT * FROM ' . $tabulka . ' WHERE id = ?', [$id]) : null;
if ($id && !$z) redirect('historie.php?cast=' . $cast, 'Záznam nebyl nalezen – možná byl mezitím smazán.', 'err');
if ($z && $cast === 'deska' && isset(HIST_DESKA[$z['kategorie']])) $kat = (string)$z['kategorie'];
$rezim = $z ? 'uprava' : (!empty($_GET['nova']) ? 'nova' : 'seznam');
$seznamUrl = 'historie.php?cast=' . $cast . ($cast === 'deska' ? '&kat=' . $kat : '');

/* výchozí hodnoty formuláře podle části */
$vychozi = [
    'kronika'   => ['rok' => '', 'rok_text' => '', 'era' => '', 'titulek' => '', 'text' => '', 'foto' => '', 'foto_popisek' => '', 'zdroj' => '', 'jistota' => '', 'visible' => 1],
    'triptych'  => ['rok' => '', 'jmeno' => '', 'disciplina' => '', 'foto' => '', 'fokus' => '50% 30%', 'alt' => '', 'vitez' => '', 'souper' => '', 'sety' => '', 'hral_za' => '', 'hral_za_text' => '', 'visible' => 1],
    'osobnosti' => ['jmeno' => '', 'kategorie' => '', 'roky' => '', 'cin' => '', 'foto' => '', 'fokus' => '50% 10%', 'alt' => '', 'pramen' => '', 'jistota' => 'overeno', 'jistota_text' => 'ověřeno', 'visible' => 1],
    'deska'     => ['kategorie' => $kat, 'skupina' => obsah_get('skupina') === 'jmena' ? 'jmena' : 'hlavni', 'rok' => '', 'jmeno' => '', 'cin' => '', 'pramen' => '', 'metr' => '', 'historie' => 0, 'visible' => 1],
][$cast];
$f = $vychozi;
if ($z) foreach ($f as $k => $_) $f[$k] = is_int($vychozi[$k]) ? (int)$z[$k] : (string)$z[$k];
if ($z && $cast === 'triptych') $f['sety'] = implode(' ', array_map('strval', json_pole((string)$z['sety'])));
$chyby = [];
$bylSoubor = false;

/** Fotka u milníku, triptychu a osobnosti: [soubor, chyba, smazat]. */
function hist_foto(string $cast, ?array $z, string $jmeno): array {
    $slozka = $cast === 'osobnosti' ? 'osobnosti' : 'historie';
    return admin_obrazek('foto', $z['foto'] ?? '', $slozka, $cast === 'kronika' ? 1800 : 1600, 1800, $jmeno);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek($seznamUrl);
    $akce = vstup('action', 20);

    if ($akce === 'ulozit') {
        $bylSoubor = obsah_byl_soubor();
        $data = [];
        if ($cast === 'kronika') {
            $f = ['rok' => vstup('rok', 6), 'rok_text' => obsah_pole('rok_text', 60), 'era' => obsah_pole('era', 120),
                  'titulek' => obsah_pole('titulek', 200), 'text' => obsah_pole('text', 8000), 'foto' => $f['foto'],
                  'foto_popisek' => obsah_pole('foto_popisek', 255), 'zdroj' => obsah_pole('zdroj', 2000),
                  'jistota' => obsah_pole('jistota', 255), 'visible' => vstup_bool('visible')];
            if (!preg_match('/^\d{3,4}$/', $f['rok'])) $chyby['rok'] = 'Rok zapište číslem (např. 1893) – podle něj se kronika řadí.';
            if ($f['titulek'] === '') $chyby['titulek'] = 'Vyplňte titulek milníku.';
            $data = ['rok' => (int)$f['rok'], 'rok_text' => $f['rok_text'], 'era' => $f['era'], 'titulek' => $f['titulek'], 'text' => $f['text'],
                     'foto_popisek' => $f['foto_popisek'], 'zdroj' => $f['zdroj'], 'jistota' => $f['jistota'], 'visible' => $f['visible']];
            $jmenoFoto = 'hist-' . $f['rok'] . '-' . $f['titulek'];
        } elseif ($cast === 'triptych') {
            $f = ['rok' => obsah_pole('rok', 10), 'jmeno' => obsah_pole('jmeno', 120), 'disciplina' => obsah_pole('disciplina', 160),
                  'foto' => $f['foto'], 'fokus' => vstup('fokus', 20), 'alt' => obsah_pole('alt', 255), 'vitez' => obsah_pole('vitez', 80),
                  'souper' => obsah_pole('souper', 80), 'sety' => obsah_pole('sety', 200),
                  'hral_za' => $f['hral_za'], 'hral_za_text' => $f['hral_za_text'],        // ve formuláři nejsou – uložená hodnota zůstává
                  'visible' => vstup_bool('visible')];
            if ($f['rok'] === '') $chyby['rok'] = 'Vyplňte rok (např. 1954).';
            if ($f['jmeno'] === '') $chyby['jmeno'] = 'Vyplňte jméno vítěze.';
            $sety = sety_z_textu($f['sety']);
            if ($f['sety'] !== '' && $sety === '[]') $chyby['sety'] = 'Sety zapište jako „6:4 6:4“, tiebreak v závorce „9:8 (7:5)“.';
            if (strlen($sety) > 255) $chyby['sety'] = 'Setů je příliš mnoho – zapište jen sety finále.';
            $fokus = obsah_fokus($f['fokus'], '50% 30%');
            if ($fokus === false) $chyby['fokus'] = 'Ohnisko zapište jako „50% 30%“.';
            $data = ['rok' => $f['rok'], 'jmeno' => $f['jmeno'], 'disciplina' => $f['disciplina'], 'fokus' => (string)$fokus, 'alt' => $f['alt'],
                     'vitez' => $f['vitez'], 'souper' => $f['souper'], 'sety' => $sety, 'hral_za' => $f['hral_za'],
                     'hral_za_text' => $f['hral_za_text'], 'visible' => $f['visible']];
            $jmenoFoto = 'triptych-' . $f['rok'] . '-' . $f['jmeno'];
        } elseif ($cast === 'osobnosti') {
            $f = ['jmeno' => obsah_pole('jmeno', 120), 'kategorie' => obsah_pole('kategorie', 80), 'roky' => obsah_pole('roky', 80),
                  'cin' => obsah_inline(vstup('cin', 2000)), 'foto' => $f['foto'], 'fokus' => vstup('fokus', 20), 'alt' => obsah_pole('alt', 255),
                  'pramen' => obsah_pole('pramen', 255), 'jistota' => vstup('jistota', 20), 'jistota_text' => obsah_pole('jistota_text', 80),
                  'visible' => vstup_bool('visible')];
            if ($f['jmeno'] === '') $chyby['jmeno'] = 'Vyplňte jméno osobnosti.';
            if (!isset(HIST_JISTOTA_OSOB[$f['jistota']])) $f['jistota'] = 'overeno';
            if ($f['jistota_text'] === '') $f['jistota_text'] = $f['jistota'] === 'klub' ? 'podle klubu' : 'ověřeno';
            $fokus = obsah_fokus($f['fokus'], '50% 10%');
            if ($fokus === false) $chyby['fokus'] = 'Ohnisko zapište jako „50% 10%“.';
            $data = ['jmeno' => $f['jmeno'], 'kategorie' => $f['kategorie'], 'roky' => $f['roky'], 'cin' => $f['cin'], 'fokus' => (string)$fokus,
                     'alt' => $f['alt'], 'pramen' => $f['pramen'], 'jistota' => $f['jistota'], 'jistota_text' => $f['jistota_text'], 'visible' => $f['visible']];
            $jmenoFoto = 'osobnost-' . $f['jmeno'];
        } else {
            $f = ['kategorie' => vstup('kategorie', 20), 'skupina' => vstup('skupina', 20), 'rok' => obsah_pole('rok', 30),
                  'jmeno' => obsah_pole('jmeno', 160), 'cin' => obsah_pole('cin', 255), 'pramen' => obsah_pole('pramen', 120),
                  'metr' => $f['metr'], 'historie' => vstup_bool('historie'), 'visible' => vstup_bool('visible')];   // metr ve formuláři není
            if (!isset(HIST_DESKA[$f['kategorie']])) { $chyby['kategorie'] = 'Vyberte kategorii desky.'; $f['kategorie'] = $kat; }
            if (!isset(HIST_SKUPINY[$f['skupina']])) $f['skupina'] = 'hlavni';
            if ($f['kategorie'] !== 'grandslam') $f['metr'] = '';
            if ($f['jmeno'] === '') $chyby['jmeno'] = 'Vyplňte jméno.';
            $data = $f;
        }

        if (!$chyby && $cast !== 'deska') {
            $foto = hist_foto($cast, $z, $jmenoFoto);
            if ($foto['chyba'] !== '') $chyby['foto'] = $foto['chyba'];
            else $data['foto'] = $foto['soubor'];
        }
        if (!$chyby) {
            if ($z) {
                // přesun do jiného roku / kategorie = na konec nové skupiny
                if ($cast === 'kronika' && (int)$data['rok'] !== (int)$z['rok']) $data['poradi'] = admin_dalsi_poradi($tabulka, 'rok', (int)$data['rok']);
                if ($cast === 'deska' && ($data['kategorie'] !== $z['kategorie'] || $data['skupina'] !== $z['skupina'])) {
                    $data['poradi'] = (int)val('SELECT COALESCE(MAX(poradi), -1) + 1 FROM cltk_deska_zaznamy WHERE kategorie = ? AND skupina = ?', [$data['kategorie'], $data['skupina']]);
                }
                db_update($tabulka, $id, $data);
            } else {
                $data['poradi'] = match ($cast) {
                    'kronika' => admin_dalsi_poradi($tabulka, 'rok', (int)$data['rok']),
                    'deska'   => (int)val('SELECT COALESCE(MAX(poradi), -1) + 1 FROM cltk_deska_zaznamy WHERE kategorie = ? AND skupina = ?', [$data['kategorie'], $data['skupina']]),
                    default   => admin_dalsi_poradi($tabulka),
                };
                $id = db_insert($tabulka, $data);
            }
            if (isset($foto)) obsah_smazat_nepouzite($foto['smazat'], HIST_FOTKY);     // až po zápisu
            $cil = 'historie.php?cast=' . $cast . ($cast === 'deska' ? '&kat=' . $data['kategorie'] : '');
            redirect($cil . '#h' . $id, $z ? 'Uloženo.' : 'Přidáno.');
        }
        $rezim = $z ? 'uprava' : 'nova';
    } else {
        $rid = (int)vstup_int('id');
        $x = $rid ? row('SELECT * FROM ' . $tabulka . ' WHERE id = ?', [$rid]) : null;
        if (!$x) redirect($seznamUrl, 'Záznam nebyl nalezen – možná byl mezitím smazán.', 'err');
        if ($cast === 'deska') $seznamUrl = 'historie.php?cast=deska&kat=' . (isset(HIST_DESKA[$x['kategorie']]) ? $x['kategorie'] : 'grandslam');
        $popis = (string)($x['titulek'] ?? $x['jmeno'] ?? '');
        if ($akce === 'prepnout') {
            $novy = admin_prepni($tabulka, $rid);
            redirect($seznamUrl . '#h' . $rid, 'Záznam „' . $popis . '“ je teď na webu ' . ($novy ? 'vidět.' : 'skrytý.'));
        }
        if ($akce === 'nahoru' || $akce === 'dolu') {
            $smer = $akce === 'nahoru' ? -1 : 1;
            if ($cast === 'kronika') admin_posun($tabulka, $rid, $smer, 'rok');
            elseif ($cast === 'deska') admin_posun($tabulka, $rid, $smer, 'kategorie', 'skupina = ?', [(string)$x['skupina']]);
            else admin_posun($tabulka, $rid, $smer);
            redirect($seznamUrl . '#h' . $rid, 'Pořadí bylo změněno.');
        }
        if ($akce === 'smazat') {
            q('DELETE FROM ' . $tabulka . ' WHERE id = ?', [$rid]);
            if ($cast !== 'deska') obsah_smazat_nepouzite([(string)$x['foto']], HIST_FOTKY);
            redirect($seznamUrl, 'Záznam „' . $popis . '“ byl smazán.');
        }
        redirect($seznamUrl, 'Neznámý požadavek.', 'err');
    }
}

$pocty = [];
foreach (HIST_CASTI as $k => [$n, $t]) $pocty[$k] = (int)val('SELECT COUNT(*) FROM ' . $t);
$zalozky = [];
foreach (HIST_CASTI as $k => [$n]) $zalozky['historie.php?cast=' . $k] = $n . ' · ' . $pocty[$k];

$novaUrl = 'historie.php?cast=' . $cast . ($cast === 'deska' ? '&kat=' . $kat : '') . '&nova=1';
if ($rezim === 'seznam') {
    admin_head('Historie', $user, [
        'podnadpis' => 'Kronika, tři wimbledonské trávy, osobnosti a Zlatá deska na stránce Historie. Nadpisy a texty sekcí (i perexy záložek desky) jsou ve <a href="stranky.php?stranka=historie">Stránkách</a>.',
        'akce' => '<a class="btn btn-primary" href="' . e($novaUrl) . '">Přidat ' . e($castJedn) . '</a>',
    ]);
} else {
    admin_head(($z ? 'Upravit ' : 'Nový ') . $castJedn, $user, [
        'zpet' => [$seznamUrl, $castNazev . ($cast === 'deska' ? ' – ' . HIST_DESKA[$kat] : '')], 'sirka' => 'uzka',
    ]);
}
echo obsah_assets();

if ($rezim === 'seznam'):
?>
<?= admin_zalozky($zalozky, 'historie.php?cast=' . $cast) ?>

<?php if ($cast === 'kronika'):
    $radky = rows('SELECT * FROM cltk_milniky ORDER BY rok, poradi, id');
    $vRoce = [];
    foreach ($radky as $r) $vRoce[(int)$r['rok']][] = (int)$r['id'];
?>
<section class="panel">
  <div class="panel-head">
    <h2>Kronika <small><?= cislo(count($radky)) ?> milníků · řadí se podle roku</small></h2>
    <a class="btn btn-sm btn-ghost" href="<?= e(url('historie.php')) ?>#kronika" target="_blank" rel="noopener">Zobrazit na webu <span aria-hidden="true">↗</span></a>
  </div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Kronika je prázdná', '', '<a class="btn btn-primary" href="' . e($novaUrl) . '">Přidat milník</a>') ?>
  <?php else: ?>
  <div class="tbl-wrap tbl-karty">
    <table>
      <thead><tr><th>Rok</th><th>Foto</th><th>Milník a jistota údaje</th><th>Stav</th><th class="right">Akce</th></tr></thead>
      <tbody>
      <?php $era = null; foreach ($radky as $r): $rid = (int)$r['id']; $sk = $vRoce[(int)$r['rok']]; $pos = array_search($rid, $sk, true);
        if ($r['era'] !== $era): $era = (string)$r['era']; ?>
        <tr class="tr-skupina"><td colspan="5"><?= $era !== '' ? e($era) : 'Bez doby' ?></td></tr>
      <?php endif; ?>
        <tr id="h<?= $rid ?>"<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
          <td data-label="Rok"><span class="td-rok<?= $r['rok_text'] !== '' ? ' td-rok--text' : '' ?>"><?= e($r['rok_text'] !== '' ? $r['rok_text'] : (string)$r['rok']) ?></span></td>
          <td data-label="Foto"><?= obsah_nahled_foto($r['foto'], 'thumb-sm', '', '–') ?></td>
          <td data-label="Milník a jistota údaje" class="td-nazev"><b><a href="historie.php?cast=kronika&amp;id=<?= $rid ?>"><?= e($r['titulek']) ?></a></b><small><?= e(uryvek($r['text'], 110)) ?></small>
            <?php if ($r['jistota'] !== ''): ?><small><?= badge(uryvek($r['jistota'], 50), str_starts_with((string)$r['jistota'], 'ověřeno') ? 'ok' : 'warn') ?></small><?php endif; ?></td>
          <td data-label="Stav"><?= stav_badge($r['visible']) ?></td>
          <td data-label="Akce" class="right"><div class="akce-radku">
            <?php if (count($sk) > 1): ?><?= tlacitka_poradi($rid, $pos === 0, $pos === count($sk) - 1, ['cast' => 'kronika']) ?><?php endif; ?>
            <a class="btn btn-sm btn-ghost" href="historie.php?cast=kronika&amp;id=<?= $rid ?>">Upravit</a>
            <?= tlacitko_prepnout($rid, $r['visible'], ['cast' => 'kronika']) ?>
            <?= tlacitko_smazat($rid, 'Smazat milník „' . $r['titulek'] . '“? Nejde to vrátit.', ['cast' => 'kronika']) ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<p class="hint">Milníky se na webu řadí podle roku. Šipky se objeví jen u let, kde je milníků víc – určují pořadí uvnitř roku.</p>

<?php elseif ($cast === 'triptych' || $cast === 'osobnosti'):
    $radky = rows('SELECT * FROM ' . $tabulka . ' ORDER BY poradi, id');
    $posl = count($radky) - 1;
?>
<section class="panel">
  <div class="panel-head">
    <h2><?= e($castNazev) ?> <small><?= cislo(count($radky)) ?></small></h2>
    <a class="btn btn-sm btn-ghost" href="<?= e(url('historie.php')) ?>" target="_blank" rel="noopener">Zobrazit na webu <span aria-hidden="true">↗</span></a>
  </div>
  <?php if ($cast === 'triptych'): ?><div class="panel-body"><p class="hint">Tři panely se ukazují na úvodní stránce (sekce Naše historie) i na stránce Historie. Fotky jsou černobílé z podkladů klienta.</p></div><?php endif; ?>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím tu nic není', '', '<a class="btn btn-primary" href="' . e($novaUrl) . '">Přidat ' . e($castJedn) . '</a>') ?>
  <?php else: ?>
  <div class="tbl-wrap tbl-karty">
    <table>
      <thead><tr><th>Foto</th><th><?= $cast === 'triptych' ? 'Rok a vítěz' : 'Osobnost' ?></th><th><?= $cast === 'triptych' ? 'Finále' : 'Čin' ?></th><th>Stav</th><th class="right">Akce</th></tr></thead>
      <tbody>
      <?php foreach ($radky as $i => $r): $rid = (int)$r['id']; ?>
        <tr id="h<?= $rid ?>"<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
          <td data-label="Foto"><?= obsah_nahled_foto($r['foto'], 'thumb-portret', (string)$r['fokus']) ?></td>
          <?php if ($cast === 'triptych'): ?>
            <td data-label="Rok a vítěz" class="td-nazev"><span class="td-rok"><?= e($r['rok']) ?></span><b><a href="historie.php?cast=triptych&amp;id=<?= $rid ?>"><?= e($r['jmeno']) ?></a></b><small><?= e($r['disciplina']) ?></small></td>
            <td data-label="Finále" class="td-mala"><b><?= e($r['vitez']) ?></b> – <?= e($r['souper']) ?><br><?= e(implode(' ', array_map('strval', json_pole((string)$r['sety'])))) ?></td>
          <?php else: ?>
            <td data-label="Osobnost" class="td-nazev"><b><a href="historie.php?cast=osobnosti&amp;id=<?= $rid ?>"><?= e($r['jmeno']) ?></a></b><small><?= e(implode(' · ', array_filter([(string)$r['kategorie'], (string)$r['roky']], static fn($x) => $x !== ''))) ?></small></td>
            <td data-label="Čin" class="td-mala"><?= html_inline($r['cin']) ?><br><?= badge($r['jistota_text'] !== '' ? $r['jistota_text'] : $r['jistota'], $r['jistota'] === 'overeno' ? 'ok' : 'warn') ?></td>
          <?php endif; ?>
          <td data-label="Stav"><?= stav_badge($r['visible']) ?></td>
          <td data-label="Akce" class="right"><div class="akce-radku">
            <?= tlacitka_poradi($rid, $i === 0, $i === $posl, ['cast' => $cast]) ?>
            <a class="btn btn-sm btn-ghost" href="historie.php?cast=<?= e($cast) ?>&amp;id=<?= $rid ?>">Upravit</a>
            <?= tlacitko_prepnout($rid, $r['visible'], ['cast' => $cast]) ?>
            <?= tlacitko_smazat($rid, 'Smazat „' . $r['jmeno'] . '“? Nejde to vrátit.', ['cast' => $cast]) ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<?php else: /* deska */
    $vKat = [];
    foreach (rows('SELECT kategorie, COUNT(*) AS n FROM cltk_deska_zaznamy GROUP BY kategorie') as $r) $vKat[$r['kategorie']] = (int)$r['n'];
    $podzalozky = [];
    foreach (HIST_DESKA as $k => $n) $podzalozky['historie.php?cast=deska&kat=' . $k] = $n . ' · ' . ($vKat[$k] ?? 0);
?>
<?= admin_zalozky($podzalozky, 'historie.php?cast=deska&kat=' . $kat) ?>
<?php foreach (HIST_SKUPINY as $sk => $skNazev):
    $radky = rows('SELECT * FROM cltk_deska_zaznamy WHERE kategorie = ? AND skupina = ? ORDER BY poradi, id', [$kat, $sk]);
    if (!$radky && $sk === 'jmena') continue;
    $posl = count($radky) - 1;
?>
<section class="panel">
  <div class="panel-head">
    <h2><?= e(HIST_DESKA[$kat]) ?> <small><?= $sk === 'hlavni' ? 'řádky desky' : 'výčet jmen' ?> · <?= cislo(count($radky)) ?></small></h2>
    <a class="btn btn-sm btn-ghost" href="<?= e('historie.php?cast=deska&kat=' . $kat . '&skupina=' . $sk . '&nova=1') ?>">Přidat <?= $sk === 'hlavni' ? 'řádek' : 'jméno' ?></a>
  </div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Kategorie je zatím prázdná', 'Přidejte první řádek desky.') ?>
  <?php else: ?>
  <div class="tbl-wrap tbl-karty">
    <table>
      <thead><tr><?php if ($sk === 'hlavni'): ?><th>Rok</th><?php endif; ?><th>Jméno</th><?php if ($sk === 'hlavni'): ?><th>Čin</th><?php endif; ?><th>Stav</th><th class="right">Akce</th></tr></thead>
      <tbody>
      <?php foreach ($radky as $i => $r): $rid = (int)$r['id']; ?>
        <tr id="h<?= $rid ?>"<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
          <?php if ($sk === 'hlavni'): ?><td data-label="Rok"><span class="td-rok"><?= e($r['rok'] !== '' ? $r['rok'] : '–') ?></span></td><?php endif; ?>
          <td data-label="Jméno" class="td-nazev"><b><a href="historie.php?cast=deska&amp;id=<?= $rid ?>"><?= e($r['jmeno']) ?></a></b>
            <?php if ($r['pramen'] !== ''): ?><small><?= e($r['pramen']) ?></small><?php endif; ?></td>
          <?php if ($sk === 'hlavni'): ?>
            <td data-label="Čin" class="td-mala"><?= e($r['cin']) ?>
              <?php if ((int)$r['historie']): ?><br><?= badge('tlumený řádek', 'off') ?><?php endif; ?></td>
          <?php endif; ?>
          <td data-label="Stav"><?= stav_badge($r['visible']) ?></td>
          <td data-label="Akce" class="right"><div class="akce-radku">
            <?= tlacitka_poradi($rid, $i === 0, $i === $posl, ['cast' => 'deska', 'kat' => $kat]) ?>
            <a class="btn btn-sm btn-ghost" href="historie.php?cast=deska&amp;id=<?= $rid ?>">Upravit</a>
            <?= tlacitko_prepnout($rid, $r['visible'], ['cast' => 'deska', 'kat' => $kat]) ?>
            <?= tlacitko_smazat($rid, 'Smazat „' . $r['jmeno'] . '“ ze Zlaté desky? Nejde to vrátit.', ['cast' => 'deska', 'kat' => $kat]) ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php endforeach; ?>
<p class="hint">Text nad záložkou (perex) upravíte ve <a href="stranky.php?stranka=historie">Stránkách</a> – blok „deska-<?= e($kat) ?>“.
  <?php if ($kat === 'grandslam'): ?> Na webu je jeden seznam všech, kdo na Štvanici vyrostli, bez ohledu na klub v den titulu. Poznámka „pravděpodobně“ (dřív: titul jen pravděpodobně v barvách klubu) se u Grand Slamu na webu neukazuje.<?php endif; ?></p>
<?php endif; ?>

<?php else: /* ---------- formulář ---------- */ ?>
<?= obsah_chyby_box($chyby, $bylSoubor) ?>
<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="cast" value="<?= e($cast) ?>">

      <?php if ($cast === 'kronika'):
        $ery = array_column(rows("SELECT DISTINCT era FROM cltk_milniky WHERE era <> '' ORDER BY era"), 'era'); ?>
        <?= pole_radek([
              pole_text('rok', 'Rok', $f['rok'], ['type' => 'number', 'required' => true, 'attrs' => ['min' => 1000, 'max' => 2100, 'inputmode' => 'numeric'],
                  'hint' => obsah_chyba($chyby, 'rok', 'Podle roku se kronika řadí.')]),
              pole_text('rok_text', 'Rok, jak se má ukázat', $f['rok_text'], ['maxlength' => 60, 'placeholder' => 'např. 1846–1849, počátek 18. století',
                  'hint' => 'Nepovinné – když se zobrazený rok liší od čísla.']),
            ]) ?>
        <?= pole_text('era', 'Doba (razítko s dobovým jménem klubu)', $f['era'], ['maxlength' => 120, 'attrs' => ['list' => 'ery'], 'placeholder' => 'I. Český Lawn-Tennis Klub (1893–1949)']) ?>
        <datalist id="ery"><?php foreach ($ery as $x): ?><option value="<?= e($x) ?>"><?php endforeach; ?></datalist>
        <?= pole_text('titulek', 'Titulek', $f['titulek'], ['required' => true, 'maxlength' => 200, 'hint' => obsah_chyba($chyby, 'titulek')]) ?>
        <?= pole_textarea('text', 'Text', $f['text'], ['rows' => 5]) ?>
        <?= pole_obrazek('foto', 'Fotka (nepovinná)', $f['foto'], ['hint' => obsah_chyba($chyby, 'foto', 'JPG, PNG nebo WEBP.' . ($f['foto'] !== '' ? ' Nová fotka nahradí stávající.' : ''))]) ?>
        <?= pole_text('foto_popisek', 'Popisek fotky', $f['foto_popisek'], ['maxlength' => 255]) ?>
        <?= pole_textarea('zdroj', 'Pramen', $f['zdroj'], ['rows' => 2, 'hint' => 'Odkud údaj je (Revue, web, kniha). Na webu drobně pod milníkem.']) ?>
        <?= pole_text('jistota', 'Jistota údaje', $f['jistota'], ['maxlength' => 255, 'attrs' => ['list' => 'jistoty'], 'placeholder' => 'ověřeno / podle klubu',
              'hint' => 'Co tvrdí jen klubové prameny, označujte „podle klubu“.']) ?>
        <datalist id="jistoty"><option value="ověřeno"><option value="podle klubu"></datalist>

      <?php elseif ($cast === 'triptych'): ?>
        <?= pole_radek([
              pole_text('rok', 'Rok', $f['rok'], ['required' => true, 'maxlength' => 10, 'placeholder' => '1954', 'hint' => obsah_chyba($chyby, 'rok')]),
              pole_text('jmeno', 'Vítěz', $f['jmeno'], ['required' => true, 'maxlength' => 120, 'placeholder' => 'Jaroslav Drobný', 'hint' => obsah_chyba($chyby, 'jmeno')]),
            ]) ?>
        <?= pole_text('disciplina', 'Soutěž', $f['disciplina'], ['maxlength' => 160, 'placeholder' => 'Wimbledon · dvouhra mužů · finále']) ?>
        <?= pole_obrazek('foto', 'Fotka (černobílá)', $f['foto'], ['nahled' => 'ctverec', 'hint' => obsah_chyba($chyby, 'foto', 'JPG, PNG nebo WEBP.' . ($f['foto'] !== '' ? ' Nová fotka nahradí stávající.' : ''))]) ?>
        <?= pole_fokus('fokus', 'Ohnisko fotky', $f['fokus'], $f['foto'], ['hint' => obsah_chyba($chyby, 'fokus', 'Klepněte do fotky na obličej.')]) ?>
        <?= pole_text('alt', 'Popis fotky pro nevidomé', $f['alt'], ['maxlength' => 255]) ?>
        <?= pole_radek([
              pole_text('vitez', 'Vítěz do tabulky skóre', $f['vitez'], ['maxlength' => 80, 'placeholder' => 'Drobný']),
              pole_text('souper', 'Soupeř', $f['souper'], ['maxlength' => 80, 'placeholder' => 'Rosewall']),
            ]) ?>
        <?= pole_text('sety', 'Sety', $f['sety'], ['maxlength' => 200, 'placeholder' => '13:11 4:6 6:2 9:7', 'hint' => obsah_chyba($chyby, 'sety', 'Oddělte mezerou; tiebreak v závorce „9:8 (7:5)“.')]) ?>

      <?php elseif ($cast === 'osobnosti'): ?>
        <?= pole_radek([
              pole_text('jmeno', 'Jméno', $f['jmeno'], ['required' => true, 'maxlength' => 120, 'hint' => obsah_chyba($chyby, 'jmeno')]),
              pole_text('kategorie', 'Kategorie', $f['kategorie'], ['maxlength' => 80, 'placeholder' => 'Čestný člen / Legenda klubu']),
            ]) ?>
        <?= pole_text('roky', 'Roky', $f['roky'], ['maxlength' => 80, 'placeholder' => '1921–2001 / za I. ČLTK od 2011']) ?>
        <?= pole_textarea('cin', 'Čin', $f['cin'], ['rows' => 3, 'hint' => 'Krátce. Kurzívu zapíšete jako &lt;em&gt;…&lt;/em&gt;, jiné značky se nepovolí.']) ?>
        <?= pole_obrazek('foto', 'Portrét', $f['foto'], ['nahled' => 'ctverec', 'hint' => obsah_chyba($chyby, 'foto', 'JPG, PNG nebo WEBP.' . ($f['foto'] !== '' ? ' Nová fotka nahradí stávající.' : ''))]) ?>
        <?= pole_fokus('fokus', 'Ohnisko portrétu', $f['fokus'], $f['foto'], ['hint' => obsah_chyba($chyby, 'fokus', 'Klepněte do fotky na obličej.')]) ?>
        <?= pole_text('alt', 'Popis fotky pro nevidomé', $f['alt'], ['maxlength' => 255]) ?>
        <?= pole_text('pramen', 'Pramen', $f['pramen'], ['maxlength' => 255]) ?>
        <?= pole_radek([
              pole_select('jistota', 'Jistota vazby na klub', $f['jistota'], HIST_JISTOTA_OSOB),
              pole_text('jistota_text', 'Text štítku', $f['jistota_text'], ['maxlength' => 80, 'placeholder' => 'ověřeno / vazba podle klubu']),
            ]) ?>

      <?php else: /* deska */ ?>
        <?= pole_radek([
              pole_select('kategorie', 'Kategorie', $f['kategorie'], HIST_DESKA, ['hint' => obsah_chyba($chyby, 'kategorie')]),
              pole_select('skupina', 'Druh', $f['skupina'], HIST_SKUPINY),
            ]) ?>
        <?= pole_radek([
              pole_text('rok', 'Rok', $f['rok'], ['maxlength' => 30, 'placeholder' => '1954 / 2011–2022 / 1966, 1968', 'hint' => 'U výčtu jmen nechte prázdné.']),
              pole_text('jmeno', 'Jméno', $f['jmeno'], ['required' => true, 'maxlength' => 160, 'hint' => obsah_chyba($chyby, 'jmeno')]),
            ]) ?>
        <?= pole_text('cin', 'Čin', $f['cin'], ['maxlength' => 255, 'sirka' => 'cela', 'placeholder' => 'Wimbledon · dvouhra']) ?>
        <?= pole_text('pramen', 'Drobná poznámka', $f['pramen'], ['maxlength' => 120, 'placeholder' => 'pravděpodobně / podle klubu']) ?>
        <?= pole_check('historie', 'Tlumený řádek ze štvanické historie', (bool)$f['historie'], ['hint' => 'Úspěch, který nepatří do barev klubu (např. medaile v jiném sportu).']) ?>
      <?php endif; ?>

      <?= pole_check('visible', 'Zobrazit na webu', (bool)$f['visible']) ?>
      <?= tlacitka_formulare($z ? 'Uložit změny' : 'Přidat', $seznamUrl, 'Zpět bez uložení') ?>
    </form>
  </div>
</section>
<?php if ($z): ?>
  <section class="panel panel--nebezpeci">
    <div class="panel-head"><h2>Smazat</h2></div>
    <div class="panel-body"><div class="btn-row">
      <?= tlacitko_smazat($id, 'Opravdu smazat „' . (string)($z['titulek'] ?? $z['jmeno'] ?? '') . '“? Nejde to vrátit.', ['cast' => $cast, 'kat' => $kat], 'Smazat záznam') ?>
    </div></div>
  </section>
<?php endif; ?>
<?php endif; ?>

<?php admin_foot(); ?>
