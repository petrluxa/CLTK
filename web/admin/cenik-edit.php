<?php
/* Úprava jednoho ceníku: hlavička, sekce a v každé sekci řádky s cenami (jako
   cenik-edit.php v Liberci). Celá sekce – záhlaví sloupců i všechny ceny – se ukládá
   jedním tlačítkem; nové řádky se píšou do prázdných řádků na konci tabulky.
   Šipky a mazání řádků mají vlastní formuláře (Enter v poli nesmí posunout řádek)
   a pole leží uvnitř svého <form> – žádné form="…" (Safari).
   Prázdné záhlaví sloupce = sloupec se na webu nezobrazí. */
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

/** Cenové sloupce řádku: sloupec => [záhlaví v sekci, popisek v administraci]. */
const CENIK_SLOUPCE = [
    'cena'             => ['hl_cena', 'Cena'],
    'cena_clen'        => ['hl_cena_clen', 'Cena pro členy'],
    'cena_sezona'      => ['hl_cena_sezona', 'Sezóna / předplatné'],
    'cena_sezona_clen' => ['hl_cena_sezona_clen', 'Sezóna – členové'],
];
const CENIK_STRANKY = [
    'kurty-leto' => 'cenik-kurtu.php', 'kurty-zima' => 'cenik-kurtu.php', 'clenstvi' => 'clenstvi.php',
    'skola' => 'tenisova-skola-ceniky.php', 'kempy' => 'letni-kempy.php', 'doplnkove' => 'cenik-kurtu.php',
];
const CENIK_NOVYCH_RADKU = 1;      // další prázdné řádky přidá tlačítko (JS)

$id = (int)($_GET['id'] ?? 0);
$cenik = $id ? row('SELECT * FROM cltk_price_lists WHERE id = ?', [$id]) : null;
if (!$cenik) redirect('ceniky.php', 'Ceník nebyl nalezen – možná byl mezitím smazán.', 'err');
$zpet = 'cenik-edit.php?id=' . $id;

$fh = $cenik;          // hodnoty hlavičky ve formuláři (po chybě z POST)
$chyby = [];

/** Text ceny / buňky z pole řádku (prostý text, max. 80 znaků). */
function cenik_bunka(array $data, string $klic, int $max = 80): string {
    $v = $data[$klic] ?? '';
    return is_scalar($v) ? mb_substr(obsah_prosty(trim(str_replace("\r\n", "\n", (string)$v))), 0, $max) : '';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek($zpet);
    $akce = vstup('action', 30);

    /* ---------- hlavička ceníku ---------- */
    if ($akce === 'hlavicka') {
        $fh = array_merge($cenik, [
            'nazev' => obsah_pole('nazev', 160), 'podnazev' => obsah_pole('podnazev', 200), 'obdobi' => obsah_pole('obdobi', 120),
            'poznamka_nahore' => obsah_pole('poznamka_nahore', 4000), 'poznamka_dole' => obsah_pole('poznamka_dole', 4000),
            'pdf_url' => vstup('pdf_url', 255), 'visible' => vstup_bool('visible'),
        ]);
        if ($fh['nazev'] === '') $chyby['nazev'] = 'Název ceníku nesmí zůstat prázdný.';
        $pdf = obsah_odkaz($fh['pdf_url']);
        if ($pdf === false) $chyby['pdf_url'] = 'Odkaz musí být stránka tohoto webu (dokument.php?d=…), nebo začínat https://. Nebo ho nechte prázdný.';
        if (!$chyby) {
            db_update('cltk_price_lists', $id, [
                'nazev' => $fh['nazev'], 'podnazev' => $fh['podnazev'], 'obdobi' => $fh['obdobi'],
                'poznamka_nahore' => $fh['poznamka_nahore'], 'poznamka_dole' => $fh['poznamka_dole'],
                'pdf_url' => (string)$pdf, 'visible' => $fh['visible'], 'updated_at' => ted(),
            ]);
            redirect($zpet, 'Hlavička ceníku je uložená.');
        }
    }

    /* ---------- nová sekce ---------- */
    elseif ($akce === 'sekce_pridat') {
        $nazev = obsah_pole('nazev', 160);
        if ($nazev === '') redirect($zpet . '#nova-sekce', 'Vyplňte nadpis nové sekce.', 'err');
        $vzor = vstup('hlavicky', 20);
        $hl = ['hl_nazev' => 'Položka', 'hl_cena' => 'Cena', 'hl_cena_clen' => '', 'hl_cena_sezona' => '', 'hl_cena_sezona_clen' => ''];
        if ($vzor === 'clen') $hl = array_merge($hl, ['hl_cena' => 'Veřejnost', 'hl_cena_clen' => 'Člen']);
        if (preg_match('/^s(\d+)$/', $vzor, $m)) {
            $s = row('SELECT * FROM cltk_price_sections WHERE id = ? AND list_id = ?', [(int)$m[1], $id]);
            if ($s) foreach (array_keys($hl) as $k) $hl[$k] = (string)$s[$k];
        }
        $sid = db_insert('cltk_price_sections', array_merge([
            'list_id' => $id, 'nazev' => $nazev, 'popis' => obsah_pole('popis', 255),
            'poradi' => admin_dalsi_poradi('cltk_price_sections', 'list_id', $id),
        ], $hl));
        q('UPDATE cltk_price_lists SET updated_at = ? WHERE id = ?', [ted(), $id]);
        redirect($zpet . '#sekce-' . $sid, 'Sekce „' . $nazev . '“ je přidaná – vyplňte do ní řádky s cenami a uložte.');
    }

    else {
        /* všechno ostatní se týká sekce nebo řádku tohoto ceníku */
        $co = vstup('co', 10);
        $rid = 0;
        if ($co === 'radek') {
            $rid = (int)vstup_int('id');
            $sid = (int)val('SELECT r.section_id FROM cltk_price_rows r JOIN cltk_price_sections s ON s.id = r.section_id WHERE r.id = ? AND s.list_id = ?', [$rid, $id]);
        } else {
            $sid = (int)vstup_int($akce === 'sekce_ulozit' ? 'sekce_id' : 'id');
        }
        $sekce = $sid ? row('SELECT * FROM cltk_price_sections WHERE id = ? AND list_id = ?', [$sid, $id]) : null;
        if (!$sekce) redirect($zpet, 'Sekce nebo řádek už v ceníku není – možná ho mezitím někdo smazal.', 'err');
        $kotva = '#sekce-' . $sid;

        if ($co === 'radek' && ($akce === 'nahoru' || $akce === 'dolu')) {
            admin_posun('cltk_price_rows', $rid, $akce === 'nahoru' ? -1 : 1, 'section_id');
            redirect($zpet . $kotva, 'Pořadí řádků bylo změněno.');
        }
        if ($co === 'radek' && $akce === 'smazat') {
            q('DELETE FROM cltk_price_rows WHERE id = ? AND section_id = ?', [$rid, $sid]);
            redirect($zpet . $kotva, 'Řádek byl smazán.');
        }
        if ($akce === 'nahoru' || $akce === 'dolu') {
            admin_posun('cltk_price_sections', $sid, $akce === 'nahoru' ? -1 : 1, 'list_id');
            redirect($zpet . $kotva, 'Pořadí sekcí bylo změněno.');
        }
        if ($akce === 'smazat') {
            q('DELETE FROM cltk_price_rows WHERE section_id = ?', [$sid]);
            q('DELETE FROM cltk_price_sections WHERE id = ?', [$sid]);
            redirect($zpet, 'Sekce „' . $sekce['nazev'] . '“ byla smazána i se svými řádky.');
        }
        if ($akce === 'sekce_ulozit') {
            db_update('cltk_price_sections', $sid, [
                'nazev' => obsah_pole('nazev', 160), 'popis' => obsah_pole('popis', 255),
                'hl_nazev' => obsah_pole('hl_nazev', 60), 'hl_cena' => obsah_pole('hl_cena', 60),
                'hl_cena_clen' => obsah_pole('hl_cena_clen', 60), 'hl_cena_sezona' => obsah_pole('hl_cena_sezona', 60),
                'hl_cena_sezona_clen' => obsah_pole('hl_cena_sezona_clen', 60),
            ]);
            /* stávající řádky – každý musí do této sekce opravdu patřit */
            $upraveno = 0;
            $radky = $_POST['radek'] ?? [];
            if (is_array($radky)) {
                $patri = array_map('intval', array_column(rows('SELECT id FROM cltk_price_rows WHERE section_id = ?', [$sid]), 'id'));
                foreach ($radky as $r => $data) {
                    if (!is_array($data) || !in_array((int)$r, $patri, true)) continue;
                    db_update('cltk_price_rows', (int)$r, [
                        'nazev' => cenik_bunka($data, 'nazev', 255), 'poznamka' => cenik_bunka($data, 'poznamka', 255),
                        'cena' => cenik_bunka($data, 'cena'), 'cena_clen' => cenik_bunka($data, 'cena_clen'),
                        'cena_sezona' => cenik_bunka($data, 'cena_sezona'), 'cena_sezona_clen' => cenik_bunka($data, 'cena_sezona_clen'),
                    ]);
                    $upraveno++;
                }
            }
            /* nové řádky z prázdných řádků na konci tabulky (paralelní pole novy_*[]) */
            $pridano = 0;
            $nove = [];
            foreach (array_merge(['nazev', 'poznamka'], array_keys(CENIK_SLOUPCE)) as $k) {
                $p = $_POST['novy_' . $k] ?? [];
                $nove[$k] = is_array($p) ? array_values($p) : [];
            }
            $poradi = admin_dalsi_poradi('cltk_price_rows', 'section_id', $sid);
            foreach ($nove['nazev'] as $i => $_) {
                $data = [];
                foreach ($nove as $k => $hodnoty) $data[$k] = $hodnoty[$i] ?? '';
                $radek = [
                    'nazev' => cenik_bunka($data, 'nazev', 255), 'poznamka' => cenik_bunka($data, 'poznamka', 255),
                    'cena' => cenik_bunka($data, 'cena'), 'cena_clen' => cenik_bunka($data, 'cena_clen'),
                    'cena_sezona' => cenik_bunka($data, 'cena_sezona'), 'cena_sezona_clen' => cenik_bunka($data, 'cena_sezona_clen'),
                ];
                if (implode('', $radek) === '') continue;                         // prázdný řádek se přeskočí
                db_insert('cltk_price_rows', array_merge(['section_id' => $sid, 'poradi' => $poradi++], $radek));
                $pridano++;
            }
            q('UPDATE cltk_price_lists SET updated_at = ? WHERE id = ?', [ted(), $id]);
            redirect($zpet . $kotva, 'Sekce je uložená' . ($upraveno ? ' – upraveno ' . $upraveno . ' ' . sklonuj($upraveno, 'řádek', 'řádky', 'řádků') : '')
                . ($pridano ? ', přidáno ' . $pridano . ' ' . sklonuj($pridano, 'nový řádek', 'nové řádky', 'nových řádků') : '') . '.');
        }
        redirect($zpet, 'Neznámý požadavek.', 'err');
    }
}

$sekce = rows('SELECT * FROM cltk_price_sections WHERE list_id = ? ORDER BY poradi, id', [$id]);
$radkyVse = $sekce ? rows('SELECT r.* FROM cltk_price_rows r JOIN cltk_price_sections s ON s.id = r.section_id WHERE s.list_id = ? ORDER BY r.poradi, r.id', [$id]) : [];
$radkyPodle = [];
foreach ($radkyVse as $r) $radkyPodle[(int)$r['section_id']][] = $r;
$stranka = CENIK_STRANKY[$cenik['klic']] ?? '';
$poslSekce = count($sekce) - 1;

$vzory = ['zakladni' => 'Položka a cena', 'clen' => 'Položka, veřejnost a člen'];
foreach ($sekce as $s) $vzory['s' . (int)$s['id']] = 'Stejná záhlaví jako „' . ($s['nazev'] !== '' ? $s['nazev'] : 'sekce bez názvu') . '“';

admin_head($cenik['nazev'], $user, [
    'zpet'      => ['ceniky.php', 'Ceníky'],
    'podnadpis' => 'Klíč <span class="klic">' . e($cenik['klic']) . '</span>'
        . ($stranka !== '' ? ' · na webu: <a href="' . e(url($stranka)) . '" target="_blank" rel="noopener">' . e($stranka) . ' ↗</a>' : ' · tento ceník web zatím nečte')
        . ' · ' . ((int)$cenik['visible'] ? 'zobrazený' : '<b>skrytý</b>'),
    'akce'      => '<a class="btn btn-ghost" href="#nova-sekce">Přidat sekci</a>',
]);
echo obsah_assets();
?>

<section class="panel" id="hlavicka">
  <div class="panel-head"><h2>Hlavička ceníku</h2></div>
  <div class="panel-body">
    <?= obsah_chyby_box($chyby) ?>
    <form method="post" class="form wide" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="hlavicka">
      <?= pole_radek([
            pole_text('nazev', 'Název', $fh['nazev'], ['required' => true, 'maxlength' => 160, 'hint' => obsah_chyba($chyby, 'nazev')]),
            pole_text('podnazev', 'Podnázev', $fh['podnazev'], ['maxlength' => 200]),
          ]) ?>
      <?= pole_radek([
            pole_text('obdobi', 'Období', $fh['obdobi'], ['maxlength' => 120, 'placeholder' => '28. 9. 2026 – 4. 4. 2027']),
            pole_text('pdf_url', 'Odkaz „Ceník k vytištění“', $fh['pdf_url'], ['maxlength' => 255, 'placeholder' => 'dokument.php?d=cenik-zima-2026-2027',
                'hint' => obsah_chyba($chyby, 'pdf_url', 'Nepovinné – web pod ceník přidá odkaz. Ceník k tisku je stránka v modulu Dokumenty: sem napište její adresu (dokument.php?d=…). PDF se nenahrává; výjimečně celá adresa https://….')]),
          ]) ?>
      <?= pole_radek([
            pole_textarea('poznamka_nahore', 'Poznámka nad tabulkami', $fh['poznamka_nahore'], ['rows' => 3]),
            pole_textarea('poznamka_dole', 'Poznámka pod tabulkami', $fh['poznamka_dole'], ['rows' => 3, 'hint' => 'Odstavce oddělte prázdným řádkem.']),
          ]) ?>
      <?= pole_check('visible', 'Zobrazit ceník na webu', (bool)$fh['visible']) ?>
      <?= tlacitka_formulare('Uložit hlavičku') ?>
    </form>
  </div>
</section>

<?php if (!$sekce): ?>
  <section class="panel"><?= prazdny_stav('Ceník zatím nemá žádnou sekci', 'Sekce je jedna tabulka cen – třeba „Přetlaková hala · antuka“. Přidejte první dole.') ?></section>
<?php endif; ?>

<?php foreach ($sekce as $si => $s):
    $sid = (int)$s['id'];
    $radky = $radkyPodle[$sid] ?? [];
    $poslRadek = count($radky) - 1;
    $sloupce = [];                                   // cenové sloupce, které mají záhlaví (zobrazí se)
    foreach (CENIK_SLOUPCE as $k => [$hl, $popis]) if (trim((string)$s[$hl]) !== '') $sloupce[$k] = (string)$s[$hl];
    $hlNazev = trim((string)$s['hl_nazev']) !== '' ? (string)$s['hl_nazev'] : 'Položka';
?>
<section class="panel" id="sekce-<?= $sid ?>">
  <div class="panel-head">
    <h2><?= e($s['nazev'] !== '' ? $s['nazev'] : 'Sekce bez nadpisu') ?>
      <small><?= cislo(count($radky)) ?> <?= sklonuj(count($radky), 'řádek', 'řádky', 'řádků') ?></small></h2>
    <div class="akce-radku">
      <?= tlacitka_poradi($sid, $si === 0, $si === $poslSekce, ['co' => 'sekce']) ?>
      <?= tlacitko_smazat($sid, 'Smazat sekci „' . $s['nazev'] . '“ i se všemi ' . count($radky) . ' řádky? Nejde to vrátit.', ['co' => 'sekce'], 'Smazat sekci') ?>
    </div>
  </div>
  <div class="panel-body">
    <form method="post" class="form wide" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="sekce_ulozit">
      <input type="hidden" name="sekce_id" value="<?= $sid ?>">
      <?= pole_radek([
            pole_text('nazev', 'Nadpis sekce', $s['nazev'], ['maxlength' => 160, 'id' => 's' . $sid . '-nazev']),
            pole_text('popis', 'Popis pod nadpisem', $s['popis'], ['maxlength' => 255, 'id' => 's' . $sid . '-popis', 'placeholder' => 'kurty 5, 6 · od 5. 10. 2026']),
          ]) ?>
      <?php $hlSouhrn = implode(' · ', array_filter([(string)$s['hl_nazev'], (string)$s['hl_cena'], (string)$s['hl_cena_clen'], (string)$s['hl_cena_sezona'], (string)$s['hl_cena_sezona_clen']], static fn($x) => trim($x) !== '')); ?>
      <details class="rozbal rozbal--pole">
        <summary>Záhlaví sloupců<?= $hlSouhrn !== '' ? ': ' . e($hlSouhrn) : '' ?></summary>
        <div class="rozbal__obsah">
        <div class="form-mrizka">
          <?= pole_text('hl_nazev', '1. sloupec (název řádku)', $s['hl_nazev'], ['maxlength' => 60, 'id' => 's' . $sid . '-hl0', 'placeholder' => 'Položka']) ?>
          <?php $n = 2; foreach (CENIK_SLOUPCE as $k => [$hl, $popis]): ?>
            <?= pole_text($hl, $n++ . '. sloupec (' . mb_strtolower($popis) . ')', $s[$hl], ['maxlength' => 60, 'id' => 's' . $sid . '-' . $hl]) ?>
          <?php endforeach; ?>
        </div>
        <p class="hint">Prázdné záhlaví = sloupec se na webu nezobrazí. Když záhlaví vyplníte a sekci uložíte, objeví se v tabulce níž pole pro ceny v tomto sloupci.</p>
        </div>
      </details>

      <div class="tbl-wrap tbl-karty tbl-pole">
        <table>
          <thead><tr>
            <th class="sirsi"><?= e($hlNazev) ?></th><th>Poznámka</th>
            <?php foreach ($sloupce as $k => $hl): ?><th><?= e($hl) ?></th><?php endforeach; ?>
          </tr></thead>
          <tbody>
          <?php foreach ($radky as $r): $rid = (int)$r['id']; ?>
            <tr>
              <td data-label="<?= e($hlNazev) ?>" class="sirsi"><input type="text" name="radek[<?= $rid ?>][nazev]" value="<?= e($r['nazev']) ?>" maxlength="255" aria-label="<?= e($hlNazev) ?>"><?php
                // ceny ve sloupcích bez záhlaví se nezobrazují, ale nesmí se uložením smazat
                foreach (CENIK_SLOUPCE as $k => $_) if (!isset($sloupce[$k])) echo '<input type="hidden" name="radek[' . $rid . '][' . $k . ']" value="' . e($r[$k]) . '">'; ?></td>
              <td data-label="Poznámka" class="cela"><input type="text" name="radek[<?= $rid ?>][poznamka]" value="<?= e($r['poznamka']) ?>" maxlength="255" aria-label="Poznámka"></td>
              <?php foreach ($sloupce as $k => $hl): ?>
                <td data-label="<?= e($hl) ?>"><input type="text" name="radek[<?= $rid ?>][<?= $k ?>]" value="<?= e($r[$k]) ?>" maxlength="80" aria-label="<?= e($hl) ?>"></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tbody id="nove-<?= $sid ?>">
          <?php for ($i = 0; $i < CENIK_NOVYCH_RADKU; $i++): ?>
            <tr class="radek-novy">
              <td data-label="Nový řádek – <?= e(mb_strtolower($hlNazev)) ?>" class="sirsi"><input type="text" name="novy_nazev[]" value="" maxlength="255" placeholder="nový řádek…" aria-label="Nový řádek – <?= e($hlNazev) ?>"><?php
                foreach (CENIK_SLOUPCE as $k => $_) if (!isset($sloupce[$k])) echo '<input type="hidden" name="novy_' . $k . '[]" value="">'; ?></td>
              <td data-label="Poznámka" class="cela"><input type="text" name="novy_poznamka[]" value="" maxlength="255" aria-label="Poznámka nového řádku"></td>
              <?php foreach ($sloupce as $k => $hl): ?>
                <td data-label="<?= e($hl) ?>"><input type="text" name="novy_<?= $k ?>[]" value="" maxlength="80" aria-label="<?= e($hl) ?> – nový řádek"></td>
              <?php endforeach; ?>
            </tr>
          <?php endfor; ?>
          </tbody>
        </table>
      </div>
      <div class="btn-row pridat-radek"><button type="button" class="btn btn-sm btn-ghost" data-pridat-radek="nove-<?= $sid ?>" hidden>+ Další prázdný řádek</button></div>
      <p class="hint">Do prázdného řádku na konci tabulky napište novou položku (další přidá tlačítko výš) – prázdné řádky se neuloží. Ceny pište i s jednotkou („500 Kč/hod“, „zdarma“).</p>
      <?= tlacitka_formulare('Uložit sekci i ceny') ?>
    </form>

    <?php if ($radky): ?>
      <details class="rozbal">
        <summary>Přesunout nebo smazat řádky</summary>
        <div class="tbl-wrap tbl-karty">
          <table>
            <tbody>
            <?php foreach ($radky as $ri => $r): $rid = (int)$r['id']; ?>
              <tr>
                <td data-label="Řádek" class="td-nazev"><b><?= e($r['nazev'] !== '' ? $r['nazev'] : '(bez názvu)') ?></b>
                  <small><?= e(implode(' · ', array_filter([(string)$r['cena'], (string)$r['cena_clen']], static fn($x) => $x !== ''))) ?></small></td>
                <td data-label="Akce" class="right"><div class="akce-radku">
                  <?= tlacitka_poradi($rid, $ri === 0, $ri === $poslRadek, ['co' => 'radek']) ?>
                  <?= tlacitko_smazat($rid, 'Smazat řádek „' . ($r['nazev'] !== '' ? $r['nazev'] : 'bez názvu') . '“?', ['co' => 'radek']) ?>
                </div></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </details>
    <?php endif; ?>
  </div>
</section>
<?php endforeach; ?>

<section class="panel" id="nova-sekce">
  <div class="panel-head"><h2>Přidat sekci</h2></div>
  <div class="panel-body">
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="sekce_pridat">
      <?= pole_radek([
            pole_text('nazev', 'Nadpis sekce', '', ['required' => true, 'maxlength' => 160, 'id' => 'ns-nazev', 'placeholder' => 'Přetlaková hala · antuka']),
            pole_text('popis', 'Popis pod nadpisem', '', ['maxlength' => 255, 'id' => 'ns-popis']),
          ]) ?>
      <?= pole_select('hlavicky', 'Záhlaví sloupců', $sekce ? 's' . (int)$sekce[$poslSekce]['id'] : 'zakladni', $vzory, ['id' => 'ns-hlavicky',
            'hint' => 'Záhlaví jde po založení kdykoli změnit.']) ?>
      <?= tlacitka_formulare('Přidat sekci') ?>
    </form>
  </div>
</section>

<?php admin_foot(); ?>
