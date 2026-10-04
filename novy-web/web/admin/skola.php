<?php
/* Tenisová škola – informace, harmonogram sezóny, rozvrhy tréninků a termíny letních kempů
   (cltk_skola, typ = info | harmonogram | rozvrh | kemp). Ceny školy a kempů jsou
   v Cenících (klíče skola, kempy), texty hlaviček stránek ve Stránkách.
   Rozvrh: jeden řádek = jedna buňka mřížky (den, čas, kurt, skupina / „obsazeno“, trenéři);
   řádky se stejným názvem tvoří rozvrh, platnost (datum_od/do) se ukládá pro celý rozvrh.
   Jména dětí do rozvrhu nepatří.
   Adresy: skola.php?typ=kemp (seznam), …&nova=1 (nový), skola.php?id=5 (úprava),
   skola.php?typ=rozvrh&rozvrh=Zima%202026%2F27 (buňky jednoho rozvrhu). */
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

/** typ => [záložka, stránka webu, popis, tlačítko Přidat, nadpis nového, nadpis úpravy]. */
const SKOLA_TYPY = [
    'info'        => ['Informace', 'tenisova-skola.php', 'Odstavce na stránce Tenisová škola – pro koho, co děláme, platba, počasí…',
                      'Přidat odstavec', 'Nový odstavec informací', 'Odstavec informací'],
    'harmonogram' => ['Harmonogram', 'tenisova-skola-rozvrhy.php', 'Termíny sezóny: začátek a konec rozvrhů, prázdniny, dny, kdy se nehraje.',
                      'Přidat termín', 'Nový termín harmonogramu', 'Termín harmonogramu'],
    'rozvrh'      => ['Rozvrhy', 'tenisova-skola-rozvrhy.php', 'Rozvrhy tréninků – co řádek, to buňka týdenní mřížky (den, čas, kurt). Web z nich skládá mřížku dny × hodiny pro každý kurt; rozvrh po konci platnosti zmizí sám. Jména dětí sem nepatří.',
                      'Přidat hodinu', 'Nová hodina rozvrhu', 'Hodina rozvrhu'],
    'kemp'        => ['Letní kempy', 'letni-kempy.php', 'Termíny letních kempů. Ceny variant jsou v Cenících (ceník „kempy“), texty ve Stránkách.',
                      'Přidat termín kempu', 'Nový termín kempu', 'Termín kempu'],
];
const SKOLA_DNY = ['' => '–', 'pondělí' => 'pondělí', 'úterý' => 'úterý', 'středa' => 'středa', 'čtvrtek' => 'čtvrtek',
                   'pátek' => 'pátek', 'sobota' => 'sobota', 'neděle' => 'neděle'];

$id = (int)($_GET['id'] ?? 0);
$z  = $id ? row('SELECT * FROM cltk_skola WHERE id = ?', [$id]) : null;
if ($id && !$z) redirect('skola.php', 'Záznam nebyl nalezen – možná byl mezitím smazán.', 'err');
$typ = $z ? (string)$z['typ'] : obsah_get('typ', 'info', true);
if (!isset(SKOLA_TYPY[$typ])) $typ = 'info';
$rezim = $z ? 'uprava' : (!empty($_GET['nova']) ? 'nova' : 'seznam');
$seznamUrl = 'skola.php?typ=' . $typ;
/* rozvrhy: vybraný rozvrh (název) – seznam ukazuje hodiny jednoho rozvrhu */
$rozvrhNazvy = $typ === 'rozvrh' ? array_map('strval', array_column(rows("SELECT nazev, MIN(datum_od) AS od, MAX(datum_do) AS dd FROM cltk_skola WHERE typ = 'rozvrh' GROUP BY nazev ORDER BY MIN(datum_od), MAX(datum_do), nazev"), 'nazev')) : [];
$rozvrhVybrany = $typ === 'rozvrh' ? ($z ? (string)$z['nazev'] : obsah_get('rozvrh', $rozvrhNazvy[0] ?? '', true)) : '';
if ($typ === 'rozvrh' && $rozvrhNazvy && !in_array($rozvrhVybrany, $rozvrhNazvy, true) && $rezim === 'seznam') $rozvrhVybrany = $rozvrhNazvy[0];
if ($typ === 'rozvrh' && $rozvrhVybrany !== '') $seznamUrl .= '&rozvrh=' . rawurlencode($rozvrhVybrany);

$f = [
    'nazev' => (string)($z['nazev'] ?? ''), 'datum_od' => (string)($z['datum_od'] ?? ''), 'datum_do' => (string)($z['datum_do'] ?? ''),
    'termin_text' => (string)($z['termin_text'] ?? ''), 'den' => (string)($z['den'] ?? ''), 'cas' => (string)($z['cas'] ?? ''),
    'skupina' => (string)($z['skupina'] ?? ''), 'misto' => (string)($z['misto'] ?? ''), 'trener' => (string)($z['trener'] ?? ''),
    'cena' => (string)($z['cena'] ?? ''), 'text' => (string)($z['text'] ?? ''), 'odkaz' => (string)($z['odkaz'] ?? ''),
    'visible' => (int)($z['visible'] ?? 1),
];
if ($typ === 'rozvrh' && !$z) {
    // nová hodina do vybraného rozvrhu: název, platnost a kurt se převezmou
    $vzor = $rozvrhVybrany !== '' ? row("SELECT nazev, datum_od, datum_do, misto FROM cltk_skola WHERE typ = 'rozvrh' AND nazev = ? ORDER BY id LIMIT 1", [$rozvrhVybrany]) : null;
    if ($vzor) { $f['nazev'] = (string)$vzor['nazev']; $f['datum_od'] = (string)$vzor['datum_od']; $f['datum_do'] = (string)$vzor['datum_do']; $f['misto'] = (string)$vzor['misto']; }
}
$f['druh'] = skola_rozvrh_obsazeno($f) ? 'obsazeno' : 'trenink';
$chyby = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek($seznamUrl);
    $akce = vstup('action', 20);

    if ($akce === 'ulozit') {
        $f = [
            'nazev' => obsah_pole('nazev', 200), 'datum_od' => vstup('datum_od', 20), 'datum_do' => vstup('datum_do', 20),
            'termin_text' => obsah_pole('termin_text', 120), 'den' => vstup('den', 30), 'cas' => obsah_pole('cas', 60),
            'skupina' => obsah_pole('skupina', 120), 'misto' => obsah_pole('misto', 120), 'trener' => obsah_pole('trener', 160),
            'cena' => obsah_pole('cena', 120), 'text' => obsah_pole('text', 6000), 'odkaz' => vstup('odkaz', 255),
            'visible' => vstup_bool('visible'), 'druh' => vstup('druh', 20) === 'obsazeno' ? 'obsazeno' : 'trenink',
        ];
        if ($typ === 'rozvrh') {
            // „16:00 - 17:00“ / „16–17“ → „16:00–17:00“; obsazený kurt = skupina „obsazeno“
            $cas = str_replace(["\u{00A0}", ' '], '', $f['cas']);
            if (preg_match('/^(\d{1,2})(?::(\d{2}))?[–—-](\d{1,2})(?::(\d{2}))?$/u', $cas, $mc)) {
                $f['cas'] = sprintf('%d:%s–%d:%s', (int)$mc[1], $mc[2] !== '' ? $mc[2] : '00', (int)$mc[3], ($mc[4] ?? '') !== '' ? $mc[4] : '00');
            }
            if ($f['druh'] === 'obsazeno') $f['skupina'] = 'obsazeno';
            elseif (skola_rozvrh_obsazeno($f)) $f['skupina'] = '';
        }
        $od = normalizuj_datum($f['datum_od']);
        $do = normalizuj_datum($f['datum_do']);
        if ($od === false) $chyby['datum_od'] = 'Datum „od“ nedává smysl – zadejte ho jako 29. 6. 2026.';
        if ($do === false) $chyby['datum_do'] = 'Datum „do“ nedává smysl – zadejte ho jako 3. 7. 2026.';
        if ($od && $do && $do < $od) $chyby['datum_do'] = 'Konec je dřív než začátek.';
        // platné datum se po chybě vypíše v podobě, kterou pole s kalendářem umí ukázat
        if ($od) $f['datum_od'] = $od;
        if ($do) $f['datum_do'] = $do;
        if ($do && !$od) $chyby['datum_od'] = 'Vyplňte i začátek (nebo nechte obě data prázdná).';
        if (!isset(SKOLA_DNY[$f['den']])) $f['den'] = '';
        $odkaz = obsah_odkaz($f['odkaz']);
        if ($odkaz === false) $chyby['odkaz'] = 'Odkaz musí začínat https://, mailto: nebo být stránka webu (např. letni-kempy.php).';

        if (in_array($typ, ['info', 'harmonogram', 'kemp'], true) && $f['nazev'] === '') {
            $chyby['nazev'] = $typ === 'info' ? 'Vyplňte nadpis odstavce.' : ($typ === 'kemp' ? 'Vyplňte název termínu (např. „1. termín“).' : 'Napište, co se v termínu děje.');
        }
        if ($typ === 'info' && $f['text'] === '') $chyby['text'] = 'Vyplňte text odstavce.';
        if (in_array($typ, ['harmonogram', 'kemp'], true) && !$od && $f['termin_text'] === '' && !isset($chyby['datum_od'])) {
            $chyby['datum_od'] = 'Vyplňte datum, nebo aspoň text termínu.';
        }
        if ($typ === 'rozvrh') {
            if ($f['nazev'] === '') $chyby['nazev'] = 'Vyplňte název rozvrhu (např. „Zima 2026/27“) – hodiny se stejným názvem tvoří jeden rozvrh.';
            if ($f['den'] === '') $chyby['den'] = 'Vyberte den.';
            if (!preg_match('/^\d{1,2}:\d{2}–\d{1,2}:\d{2}$/u', $f['cas'])) $chyby['cas'] = 'Čas zapište jako 16:00–17:00 (od–do).';
            if ($f['misto'] === '') $chyby['misto'] = 'Vyplňte kurt (např. „Kurt 5 · antuka“) – podle něj se rozvrh dělí na mřížky.';
        }

        if (!$chyby) {
            $data = [
                'typ' => $typ, 'nazev' => $f['nazev'], 'datum_od' => $od ?: null, 'datum_do' => $do ?: null,
                'termin_text' => $f['termin_text'], 'den' => $f['den'], 'cas' => $f['cas'], 'skupina' => $f['skupina'],
                'misto' => $f['misto'], 'trener' => $f['trener'], 'cena' => $f['cena'], 'text' => $f['text'],
                'odkaz' => (string)$odkaz, 'visible' => $f['visible'], 'updated_at' => ted(),
            ];
            $zpet = $typ === 'rozvrh' ? 'skola.php?typ=rozvrh&rozvrh=' . rawurlencode($f['nazev']) : $seznamUrl;
            $celyRozvrh = '';
            if ($typ === 'rozvrh') {
                // platnost patří celému rozvrhu – srovná se u všech jeho hodin
                $jinak = 0;
                foreach (rows("SELECT id, datum_od, datum_do FROM cltk_skola WHERE typ = 'rozvrh' AND nazev = ? AND id <> ?", [$f['nazev'], $id]) as $x) {
                    if ((string)substr((string)$x['datum_od'], 0, 10) !== (string)($od ?: '') || (string)substr((string)$x['datum_do'], 0, 10) !== (string)($do ?: '')) $jinak++;
                }
                if ($jinak > 0) {
                    q("UPDATE cltk_skola SET datum_od = ?, datum_do = ?, updated_at = ? WHERE typ = 'rozvrh' AND nazev = ?", [$od ?: null, $do ?: null, ted(), $f['nazev']]);
                    $celyRozvrh = ' Platnost se upravila u celého rozvrhu „' . $f['nazev'] . '“.';
                }
            }
            if ($z) {
                db_update('cltk_skola', $id, $data);
                redirect($zpet . '#z' . $id, 'Uloženo.' . $celyRozvrh);
            }
            $data['poradi'] = admin_dalsi_poradi('cltk_skola', 'typ', $typ);
            $data['created_at'] = ted();
            $noveId = db_insert('cltk_skola', $data);
            redirect($zpet . '#z' . $noveId, ($typ === 'rozvrh' ? 'Hodina je přidaná do rozvrhu.' : 'Přidáno na konec seznamu.') . $celyRozvrh);
        }
        $rezim = $z ? 'uprava' : 'nova';
    } elseif (in_array($akce, ['rozvrh_skryt', 'rozvrh_zobrazit', 'rozvrh_smazat'], true) && $typ === 'rozvrh') {
        /* celý rozvrh najednou (např. přechodný týden po skončení platnosti) */
        $nazevRozvrhu = vstup('rozvrh', 200);
        $pocet = (int)val("SELECT COUNT(*) FROM cltk_skola WHERE typ = 'rozvrh' AND nazev = ?", [$nazevRozvrhu]);
        if ($pocet === 0) redirect('skola.php?typ=rozvrh', 'Rozvrh nebyl nalezen – možná byl mezitím smazán.', 'err');
        if ($akce === 'rozvrh_smazat') {
            q("DELETE FROM cltk_skola WHERE typ = 'rozvrh' AND nazev = ?", [$nazevRozvrhu]);
            redirect('skola.php?typ=rozvrh', 'Rozvrh „' . $nazevRozvrhu . '“ (' . $pocet . ' ' . sklonuj($pocet, 'hodina', 'hodiny', 'hodin') . ') byl smazán.');
        }
        q("UPDATE cltk_skola SET visible = ?, updated_at = ? WHERE typ = 'rozvrh' AND nazev = ?", [$akce === 'rozvrh_zobrazit' ? 1 : 0, ted(), $nazevRozvrhu]);
        redirect('skola.php?typ=rozvrh&rozvrh=' . rawurlencode($nazevRozvrhu), 'Rozvrh „' . $nazevRozvrhu . '“ je ' . ($akce === 'rozvrh_zobrazit' ? 'na webu vidět.' : 'na webu skrytý.'));
    } elseif ($akce === 'seradit' && in_array($typ, ['harmonogram', 'kemp'], true)) {
        /* seřadit podle data (záznamy bez data na konec, v dosavadním pořadí) */
        $r = rows('SELECT id, datum_od FROM cltk_skola WHERE typ = ? ORDER BY poradi, id', [$typ]);
        usort($r, static fn($a, $b) => [$a['datum_od'] === null || $a['datum_od'] === '' ? '9999' : (string)$a['datum_od']]
                                    <=> [$b['datum_od'] === null || $b['datum_od'] === '' ? '9999' : (string)$b['datum_od']]);
        foreach ($r as $i => $x) q('UPDATE cltk_skola SET poradi = ? WHERE id = ?', [$i, (int)$x['id']]);
        redirect($seznamUrl, 'Seřazeno podle data.');
    } else {
        $rid = (int)vstup_int('id');
        $x = $rid ? row('SELECT * FROM cltk_skola WHERE id = ?', [$rid]) : null;
        if (!$x) redirect($seznamUrl, 'Záznam nebyl nalezen – možná byl mezitím smazán.', 'err');
        $seznamUrl = 'skola.php?typ=' . (isset(SKOLA_TYPY[$x['typ']]) ? $x['typ'] : 'info') . ($x['typ'] === 'rozvrh' ? '&rozvrh=' . rawurlencode((string)$x['nazev']) : '');
        $popis = $x['nazev'] !== '' ? $x['nazev'] : $x['skupina'];
        if ($akce === 'prepnout') {
            $novy = admin_prepni('cltk_skola', $rid);
            redirect($seznamUrl . '#z' . $rid, 'Záznam „' . $popis . '“ je teď na webu ' . ($novy ? 'vidět.' : 'skrytý.'));
        }
        if ($akce === 'nahoru' || $akce === 'dolu') {
            admin_posun('cltk_skola', $rid, $akce === 'nahoru' ? -1 : 1, 'typ');
            redirect($seznamUrl . '#z' . $rid, 'Pořadí bylo změněno.');
        }
        if ($akce === 'smazat') {
            q('DELETE FROM cltk_skola WHERE id = ?', [$rid]);
            redirect($seznamUrl, 'Záznam „' . $popis . '“ byl smazán.');
        }
        redirect($seznamUrl, 'Neznámý požadavek.', 'err');
    }
}

$pocty = array_fill_keys(array_keys(SKOLA_TYPY), 0);
foreach (rows('SELECT typ, COUNT(*) AS n FROM cltk_skola GROUP BY typ') as $r) if (isset($pocty[$r['typ']])) $pocty[$r['typ']] = (int)$r['n'];
$zalozky = [];
foreach (SKOLA_TYPY as $k => $t) $zalozky['skola.php?typ=' . $k] = $t[0] . ' · ' . $pocty[$k];

/** Termín pro tabulku: data, nebo vlastní text. */
function skola_termin(array $r): string {
    $t = trim((string)$r['termin_text']);
    $d = !empty($r['datum_od']) ? cz_range((string)$r['datum_od'], (string)($r['datum_do'] ?? '')) : '';
    return trim($d . ($d !== '' && $t !== '' ? ' · ' : '') . $t) ?: 'termín doplní klub';
}

if ($rezim === 'seznam') {
    admin_head('Tenisová škola', $user, [
        'podnadpis' => 'Informace, harmonogram, rozvrhy a termíny kempů Tenisové školy Markéty Vondroušové. Ceny jsou v <a href="ceniky.php">Cenících</a>, úvodní texty stránek ve <a href="stranky.php">Stránkách</a>.',
        'akce'      => '<a class="btn btn-primary" href="skola.php?typ=' . e($typ) . '&amp;nova=1">' . e(SKOLA_TYPY[$typ][3]) . '</a>',
    ]);
} else {
    admin_head($z ? SKOLA_TYPY[$typ][5] : SKOLA_TYPY[$typ][4], $user, [
        'zpet' => [$seznamUrl, 'Tenisová škola – ' . SKOLA_TYPY[$typ][0]], 'sirka' => 'uzka',
    ]);
}
echo obsah_assets();

if ($rezim === 'seznam' && $typ === 'rozvrh'):
    /* ---------- rozvrhy: výběr rozvrhu a jeho hodiny po kurtech, dnech a časech ---------- */
    $dnyPoradi = array_flip(['pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota', 'neděle']);
    $radky = $rozvrhVybrany !== '' ? rows("SELECT * FROM cltk_skola WHERE typ = 'rozvrh' AND nazev = ? ORDER BY id", [$rozvrhVybrany]) : [];
    $kurtyPoradi = [];
    foreach ($radky as $r) $kurtyPoradi[(string)$r['misto']] ??= count($kurtyPoradi);
    usort($radky, static fn($a, $b) => [$kurtyPoradi[(string)$a['misto']], $dnyPoradi[mb_strtolower((string)$a['den'])] ?? 99, strnatcmp((string)$a['cas'], (string)$b['cas']), (int)$a['id']]
                                    <=> [$kurtyPoradi[(string)$b['misto']], $dnyPoradi[mb_strtolower((string)$b['den'])] ?? 99, 0, (int)$b['id']]);
    $naWebu = count(array_filter($radky, static fn($r) => (int)$r['visible'] === 1));
    $prvni = $radky[0] ?? null;
    $skoncil = $prvni && !empty($prvni['datum_do']) && substr((string)$prvni['datum_do'], 0, 10) < dnes();
?>
<?= admin_zalozky($zalozky, 'skola.php?typ=rozvrh') ?>
<section class="panel">
  <div class="panel-head">
    <h2>Rozvrhy tréninků <small><?= cislo(count($rozvrhNazvy)) ?> <?= sklonuj(count($rozvrhNazvy), 'rozvrh', 'rozvrhy', 'rozvrhů') ?></small></h2>
    <div class="btn-row">
      <a class="btn btn-sm btn-ghost" href="<?= e(url('tenisova-skola-rozvrhy.php#rozvrhy')) ?>" target="_blank" rel="noopener">Zobrazit na webu <span aria-hidden="true">↗</span></a>
    </div>
  </div>
  <div class="panel-body">
    <p class="hint"><?= e(SKOLA_TYPY['rozvrh'][2]) ?> Informace pro rodiče a kontaktní trenéři pod rozvrhem jsou ve <a href="stranky.php?stranka=tenisova-skola-rozvrhy">Stránkách</a> (blok Rozvrhy).</p>
    <?php if (count($rozvrhNazvy) > 1): ?>
    <nav class="zalozky zalozky--male" aria-label="Rozvrhy">
      <?php foreach ($rozvrhNazvy as $n): ?><a href="skola.php?typ=rozvrh&amp;rozvrh=<?= e(rawurlencode($n)) ?>"<?= $n === $rozvrhVybrany ? ' aria-current="page"' : '' ?>><?= e($n !== '' ? $n : 'bez názvu') ?></a><?php endforeach; ?>
    </nav>
    <?php endif; ?>
  </div>
</section>

<?php if (!$radky): ?>
<section class="panel">
  <?= prazdny_stav('Zatím žádný rozvrh', 'Přidejte první hodinu – název rozvrhu (např. „Zima 2026/27“), platnost, den, čas a kurt. Stránka Rozvrhy do té doby ukazuje štítek „doplní klub“.',
      '<a class="btn btn-primary" href="skola.php?typ=rozvrh&amp;nova=1">Přidat hodinu</a>') ?>
</section>
<?php else: ?>
<section class="panel">
  <div class="panel-head">
    <h2><?= e($rozvrhVybrany !== '' ? $rozvrhVybrany : 'Rozvrh') ?> <small><?= cislo($naWebu) ?> na webu z <?= cislo(count($radky)) ?> · platí <?= e($prvni && $prvni['datum_od'] ? cz_range((string)$prvni['datum_od'], (string)($prvni['datum_do'] ?? '')) : 'bez data') ?></small></h2>
    <div class="btn-row">
      <a class="btn btn-sm btn-primary" href="skola.php?typ=rozvrh&amp;rozvrh=<?= e(rawurlencode($rozvrhVybrany)) ?>&amp;nova=1">Přidat hodinu</a>
      <?php if ($naWebu > 0): ?>
        <?= tlacitko_akce('rozvrh_skryt', 0, 'Skrýt celý rozvrh', 'btn-ghost', ['typ' => 'rozvrh', 'rozvrh' => $rozvrhVybrany]) ?>
      <?php else: ?>
        <?= tlacitko_akce('rozvrh_zobrazit', 0, 'Zobrazit celý rozvrh', 'btn-ghost', ['typ' => 'rozvrh', 'rozvrh' => $rozvrhVybrany]) ?>
      <?php endif; ?>
      <?= tlacitko_akce('rozvrh_smazat', 0, 'Smazat celý rozvrh', 'btn-danger', ['typ' => 'rozvrh', 'rozvrh' => $rozvrhVybrany], 'Smazat celý rozvrh „' . $rozvrhVybrany . '“ (' . count($radky) . ' hodin)? Nejde to vrátit.') ?>
    </div>
  </div>
  <?php if ($skoncil): ?><div class="panel-body"><p class="hint"><?= badge('platnost skončila', 'warn') ?> Rozvrh platil do <?= e(cz_date((string)$prvni['datum_do'])) ?> – na webu se už neukazuje. Můžete ho smazat.</p></div><?php endif; ?>
  <div class="tbl-wrap tbl-karty">
    <table>
      <thead><tr><th>Kurt</th><th>Den a čas</th><th>Co se děje</th><th>Stav</th><th class="right">Akce</th></tr></thead>
      <tbody>
      <?php foreach ($radky as $r): $rid = (int)$r['id']; $obs = skola_rozvrh_obsazeno($r); ?>
        <tr id="z<?= $rid ?>"<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
          <td data-label="Kurt" class="td-mala"><?= e((string)$r['misto'] !== '' ? (string)$r['misto'] : '–') ?></td>
          <td data-label="Den a čas" class="nowrap"><b><?= e($r['den'] !== '' ? $r['den'] : '–') ?></b> <span class="td-mala"><?= e($r['cas']) ?></span></td>
          <td data-label="Co se děje" class="td-nazev"><?php if ($obs): ?><?= badge('obsazeno', 'off') ?><?php else: ?><b><?= e($r['skupina'] !== '' ? $r['skupina'] : 'trénink') ?></b><?php endif; ?><?php $navic = array_filter([(string)$r['trener'], (string)$r['text']], static fn($x) => trim($x) !== ''); if ($navic): ?><small><?= e(implode(' · ', $navic)) ?></small><?php endif; ?></td>
          <td data-label="Stav"><?= stav_badge($r['visible']) ?></td>
          <td data-label="Akce" class="right"><div class="akce-radku">
            <a class="btn btn-sm btn-ghost" href="skola.php?id=<?= $rid ?>">Upravit</a>
            <?= tlacitko_prepnout($rid, $r['visible'], ['typ' => 'rozvrh']) ?>
            <?= tlacitko_smazat($rid, 'Smazat hodinu ' . $r['den'] . ' ' . $r['cas'] . ' (' . $r['misto'] . ')? Nejde to vrátit.', ['typ' => 'rozvrh']) ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<?php elseif ($rezim === 'seznam'):
    $radky = rows('SELECT * FROM cltk_skola WHERE typ = ? ORDER BY poradi, id', [$typ]);
    $posl = count($radky) - 1;
    $naWebu = count(array_filter($radky, static fn($r) => (int)$r['visible'] === 1));
?>
<?= admin_zalozky($zalozky, $seznamUrl) ?>
<section class="panel">
  <div class="panel-head">
    <h2><?= e(SKOLA_TYPY[$typ][0]) ?> <small><?= cislo($naWebu) ?> na webu z <?= cislo(count($radky)) ?></small></h2>
    <div class="btn-row">
      <?php if (in_array($typ, ['harmonogram', 'kemp'], true) && count($radky) > 1): ?>
        <?= tlacitko_akce('seradit', 0, 'Seřadit podle data', 'btn-ghost', ['typ' => $typ]) ?>
      <?php endif; ?>
      <a class="btn btn-sm btn-ghost" href="<?= e(url(SKOLA_TYPY[$typ][1])) ?>" target="_blank" rel="noopener">Zobrazit na webu <span aria-hidden="true">↗</span></a>
    </div>
  </div>
  <div class="panel-body"><p class="hint"><?= e(SKOLA_TYPY[$typ][2]) ?></p></div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím tu nic není', $typ === 'rozvrh' ? 'Zimní rozvrhy skupin doplní klub. Do té doby stránka Rozvrhy ukazuje štítek „doplní klub“.' : '',
        '<a class="btn btn-primary" href="skola.php?typ=' . e($typ) . '&amp;nova=1">' . e(SKOLA_TYPY[$typ][3]) . '</a>') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr>
          <?php if ($typ === 'info'): ?><th>Odstavec</th>
          <?php elseif ($typ === 'rozvrh'): ?><th>Den a čas</th><th>Skupina</th><th>Kurt a trenér</th>
          <?php else: ?><th>Termín</th><th><?= $typ === 'kemp' ? 'Kemp' : 'Co se děje' ?></th><?php if ($typ === 'kemp'): ?><th>Čas a cena</th><?php endif; ?>
          <?php endif; ?>
          <th>Stav</th><th class="right">Akce</th>
        </tr></thead>
        <tbody>
        <?php foreach ($radky as $i => $r): $rid = (int)$r['id']; ?>
          <tr id="z<?= $rid ?>"<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
            <?php if ($typ === 'info'): ?>
              <td data-label="Odstavec" class="td-nazev"><b><?= e($r['nazev']) ?></b><small><?= e(uryvek($r['text'], 140)) ?></small></td>
            <?php elseif ($typ === 'rozvrh'): ?>
              <td data-label="Den a čas" class="nowrap"><b><?= e($r['den'] !== '' ? $r['den'] : '–') ?></b><br><span class="td-mala"><?= e($r['cas']) ?></span></td>
              <td data-label="Skupina" class="td-nazev"><b><?= e($r['skupina'] !== '' ? $r['skupina'] : $r['nazev']) ?></b><?php if ($r['text'] !== ''): ?><small><?= e(uryvek($r['text'], 80)) ?></small><?php endif; ?></td>
              <td data-label="Kurt a trenér" class="td-mala"><?= e(implode(' · ', array_filter([(string)$r['misto'], (string)$r['trener']], static fn($x) => $x !== ''))) ?: '–' ?></td>
            <?php else: ?>
              <td data-label="Termín" class="nowrap"><b><?= e(skola_termin($r)) ?></b></td>
              <td data-label="<?= $typ === 'kemp' ? 'Kemp' : 'Co se děje' ?>" class="td-nazev"><b><?= e($r['nazev']) ?></b><?php if ($r['text'] !== ''): ?><small><?= e(uryvek($r['text'], 90)) ?></small><?php endif; ?></td>
              <?php if ($typ === 'kemp'): ?><td data-label="Čas a cena" class="td-mala"><?= e(implode(' · ', array_filter([(string)$r['cas'], (string)$r['cena']], static fn($x) => $x !== ''))) ?: '–' ?></td><?php endif; ?>
            <?php endif; ?>
            <td data-label="Stav"><?= stav_badge($r['visible']) ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitka_poradi($rid, $i === 0, $i === $posl, ['typ' => $typ]) ?>
              <a class="btn btn-sm btn-ghost" href="skola.php?id=<?= $rid ?>">Upravit</a>
              <?= tlacitko_prepnout($rid, $r['visible'], ['typ' => $typ]) ?>
              <?= tlacitko_smazat($rid, 'Smazat „' . ($r['nazev'] !== '' ? $r['nazev'] : $r['skupina']) . '“? Nejde to vrátit.', ['typ' => $typ]) ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php else: /* ---------- formulář (nový / úprava) ---------- */ ?>
<?= obsah_chyby_box($chyby) ?>
<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="typ" value="<?= e($typ) ?>">

      <?php if ($typ === 'info'): ?>
        <?= pole_text('nazev', 'Nadpis odstavce', $f['nazev'], ['required' => true, 'maxlength' => 200, 'placeholder' => 'Pro koho', 'hint' => obsah_chyba($chyby, 'nazev')]) ?>
        <?= pole_textarea('text', 'Text', $f['text'], ['rows' => 6, 'required' => true, 'hint' => obsah_chyba($chyby, 'text', 'Odstavce oddělte prázdným řádkem.')]) ?>
        <?= pole_text('odkaz', 'Odkaz (nepovinný)', $f['odkaz'], ['maxlength' => 255, 'placeholder' => 'https://… nebo tenisova-skola-ceniky.php', 'hint' => obsah_chyba($chyby, 'odkaz')]) ?>

      <?php elseif ($typ === 'rozvrh'):
        $kurty = array_column(rows("SELECT DISTINCT misto FROM cltk_skola WHERE typ = 'rozvrh' AND misto <> '' ORDER BY misto"), 'misto'); ?>
        <fieldset>
          <legend>Rozvrh</legend>
          <?= pole_text('nazev', 'Název rozvrhu', $f['nazev'], ['required' => true, 'maxlength' => 200, 'placeholder' => 'Zima 2026/27', 'attrs' => ['list' => 'rozvrhy-nazvy', 'autocomplete' => 'off'],
                'hint' => obsah_chyba($chyby, 'nazev', 'Hodiny se stejným názvem tvoří jeden rozvrh – na webu záložka s týdenní mřížkou.')]) ?>
          <datalist id="rozvrhy-nazvy"><?php foreach ($rozvrhNazvy as $n): ?><option value="<?= e($n) ?>"></option><?php endforeach; ?></datalist>
          <?= pole_radek([
                pole_datum('datum_od', 'Platí od', $f['datum_od'], ['hint' => obsah_chyba($chyby, 'datum_od')]),
                pole_datum('datum_do', 'Platí do', $f['datum_do'], ['hint' => obsah_chyba($chyby, 'datum_do', 'Po tomto dni se rozvrh na webu neukáže. Platnost se uloží pro celý rozvrh.')]),
              ]) ?>
        </fieldset>
        <fieldset>
          <legend>Hodina</legend>
          <?= pole_radek([
                pole_select('den', 'Den', $f['den'], SKOLA_DNY, ['hint' => obsah_chyba($chyby, 'den')]),
                pole_text('cas', 'Čas', $f['cas'], ['maxlength' => 60, 'placeholder' => '16:00–17:00', 'hint' => obsah_chyba($chyby, 'cas', 'Od–do v celých hodinách (přes dvě hodiny 17:00–19:00).')]),
              ]) ?>
          <?= pole_text('misto', 'Kurt', $f['misto'], ['maxlength' => 120, 'placeholder' => 'Kurt 5 · antuka', 'attrs' => ['list' => 'rozvrhy-kurty', 'autocomplete' => 'off'],
                'hint' => obsah_chyba($chyby, 'misto', 'Podle kurtu se rozvrh na webu dělí na mřížky – pište ho u všech hodin stejně.')]) ?>
          <datalist id="rozvrhy-kurty"><?php foreach ($kurty as $k): ?><option value="<?= e((string)$k) ?>"></option><?php endforeach; ?></datalist>
          <?= pole_select('druh', 'Co se v hodině děje', $f['druh'], ['trenink' => 'Trénink Tenisové školy', 'obsazeno' => 'Kurt je obsazený (jiný program než škola)']) ?>
          <?= pole_radek([
                pole_text('skupina', 'Skupina (nepovinné)', skola_rozvrh_obsazeno($f) ? '' : $f['skupina'], ['maxlength' => 120, 'placeholder' => 'Minitenis – začátečníci', 'hint' => obsah_chyba($chyby, 'skupina', 'Jen název skupiny – jména dětí na web nepatří.')]),
                pole_text('trener', 'Trenéři (nepovinné)', $f['trener'], ['maxlength' => 160, 'placeholder' => '3 trenéři', 'hint' => '„3 trenéři“ web zvýrazní zlatě.']),
              ]) ?>
          <?= pole_textarea('text', 'Poznámka (nepovinná)', $f['text'], ['rows' => 2, 'placeholder' => 'samostatný sparing']) ?>
        </fieldset>

      <?php else: /* harmonogram, kemp */ ?>
        <?= pole_text('nazev', $typ === 'kemp' ? 'Název termínu' : 'Co se děje', $f['nazev'], ['required' => true, 'maxlength' => 200,
              'placeholder' => $typ === 'kemp' ? '1. termín' : 'Podzimní prázdniny', 'hint' => obsah_chyba($chyby, 'nazev')]) ?>
        <?= pole_radek([
              pole_datum('datum_od', 'Od', $f['datum_od'], ['hint' => obsah_chyba($chyby, 'datum_od')]),
              pole_datum('datum_do', 'Do (u jednoho dne nechte prázdné)', $f['datum_do'], ['hint' => obsah_chyba($chyby, 'datum_do')]),
            ]) ?>
        <?= pole_text('termin_text', 'Text termínu místo data', $f['termin_text'], ['maxlength' => 120, 'placeholder' => 'např. termín doplní klub',
              'hint' => 'Nepovinné. Když je vyplněné, web ho ukáže vedle data nebo místo něj.']) ?>
        <?php if ($typ === 'kemp'): ?>
          <?= pole_radek([
                pole_text('cas', 'Čas', $f['cas'], ['maxlength' => 60, 'placeholder' => 'A 8:30–16:30 · B 8:30–13:30']),
                pole_text('cena', 'Cena (nepovinná)', $f['cena'], ['maxlength' => 120, 'hint' => 'Ceny variant jsou v Cenících – sem jen výjimka pro tento termín.']),
              ]) ?>
          <?= pole_text('odkaz', 'Odkaz na přihlášku (nepovinný)', $f['odkaz'], ['maxlength' => 255, 'placeholder' => 'https://forms.gle/…', 'hint' => obsah_chyba($chyby, 'odkaz')]) ?>
        <?php endif; ?>
        <?= pole_textarea('text', 'Poznámka', $f['text'], ['rows' => 3]) ?>
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
      <?= tlacitko_smazat($id, 'Opravdu smazat „' . ($z['nazev'] !== '' ? $z['nazev'] : $z['skupina']) . '“? Nejde to vrátit.', ['typ' => $typ], 'Smazat záznam') ?>
    </div></div>
  </section>
<?php endif; ?>
<?php endif; ?>

<?php admin_foot(); ?>
