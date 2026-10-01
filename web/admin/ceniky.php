<?php
/* Ceníky – přehled. Ceník (cltk_price_lists) → sekce → řádky jako v Liberci;
   sekce a ceny se upravují v cenik-edit.php. Stránky webu čtou ceník podle klíče
   (cenik('kurty-zima')), proto se klíč u hotových ceníků nemění a vypínání
   má přednost před mazáním. */
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

/** Klíče, které čtou stránky webu: klíč => [popis, stránka]. */
const CENIKY_KLICE = [
    'kurty-leto' => ['Kurty – letní sezóna', 'cenik-kurtu.php'],
    'kurty-zima' => ['Kurty – zimní sezóna', 'cenik-kurtu.php'],
    'clenstvi'   => ['Členské příspěvky', 'clenstvi.php'],
    'skola'      => ['Tenisová škola', 'tenisova-skola-ceniky.php'],
    'kempy'      => ['Letní kempy', 'letni-kempy.php'],
    'doplnkove'  => ['Doplňkové služby (hřiště, vstupy)', 'cenik-kurtu.php'],
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek('ceniky.php');
    $akce = vstup('action', 20);
    $id   = (int)vstup_int('id');

    if ($akce === 'pridat') {
        $nazev = obsah_pole('nazev', 160);
        $klic  = vstup('klic', 40);
        if ($nazev === '') redirect('ceniky.php#novy', 'Vyplňte název ceníku.', 'err');
        if ($klic === '_vlastni') {
            $vlastni = vstup('klic_vlastni', 40);
            if ($vlastni === '') redirect('ceniky.php#novy', 'Napište vlastní klíč, nebo vyberte jeden ze známých.', 'err');
            $klic = slugify($vlastni);
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{1,39}$/', $klic)) redirect('ceniky.php#novy', 'Klíč smí mít jen malá písmena bez diakritiky, číslice a pomlčky.', 'err');
        if (row('SELECT id FROM cltk_price_lists WHERE klic = ?', [$klic])) redirect('ceniky.php#novy', 'Ceník s klíčem „' . $klic . '“ už existuje – upravte ten.', 'err');
        $noveId = db_insert('cltk_price_lists', [
            'klic' => $klic, 'nazev' => $nazev, 'podnazev' => obsah_pole('podnazev', 200), 'obdobi' => '',
            'poznamka_nahore' => '', 'poznamka_dole' => '', 'pdf_url' => '', 'visible' => vstup_bool('visible'),
            'poradi' => admin_dalsi_poradi('cltk_price_lists'), 'updated_at' => ted(),
        ]);
        redirect('cenik-edit.php?id=' . $noveId, 'Ceník „' . $nazev . '“ je založený. Teď do něj přidejte sekce a ceny.');
    }

    $c = $id ? row('SELECT * FROM cltk_price_lists WHERE id = ?', [$id]) : null;
    if (!$c) redirect('ceniky.php', 'Ceník nebyl nalezen – možná byl mezitím smazán.', 'err');

    if ($akce === 'prepnout') {
        $novy = admin_prepni('cltk_price_lists', $id);
        redirect('ceniky.php', 'Ceník „' . $c['nazev'] . '“ je teď na webu ' . ($novy ? 'zobrazený.' : 'skrytý (zůstává uložený i s cenami).'));
    }
    if ($akce === 'nahoru' || $akce === 'dolu') {
        admin_posun('cltk_price_lists', $id, $akce === 'nahoru' ? -1 : 1);
        redirect('ceniky.php', 'Pořadí ceníků bylo změněno.');
    }
    if ($akce === 'smazat') {
        // se ceníkem zmizí i jeho sekce a řádky
        foreach (rows('SELECT id FROM cltk_price_sections WHERE list_id = ?', [$id]) as $s) {
            q('DELETE FROM cltk_price_rows WHERE section_id = ?', [(int)$s['id']]);
        }
        q('DELETE FROM cltk_price_sections WHERE list_id = ?', [$id]);
        q('DELETE FROM cltk_price_lists WHERE id = ?', [$id]);
        redirect('ceniky.php', 'Ceník „' . $c['nazev'] . '“ byl smazán i se všemi cenami.');
    }
    redirect('ceniky.php', 'Neznámý požadavek.', 'err');
}

$ceniky = rows('SELECT * FROM cltk_price_lists ORDER BY poradi, id');
$sekci = [];
foreach (rows('SELECT list_id, COUNT(*) AS n FROM cltk_price_sections GROUP BY list_id') as $r) $sekci[(int)$r['list_id']] = (int)$r['n'];
$radku = [];
foreach (rows('SELECT s.list_id, COUNT(r.id) AS n FROM cltk_price_rows r JOIN cltk_price_sections s ON s.id = r.section_id GROUP BY s.list_id') as $r) $radku[(int)$r['list_id']] = (int)$r['n'];
$pouzite = array_column($ceniky, 'klic');
$volneKlice = [];
foreach (CENIKY_KLICE as $k => [$popis]) if (!in_array($k, $pouzite, true)) $volneKlice[$k] = $popis . ' (' . $k . ')';
$volneKlice['_vlastni'] = 'Vlastní klíč…';
$zapnute = count(array_filter($ceniky, static fn($c) => (int)$c['visible'] === 1));
$posl = count($ceniky) - 1;

admin_head('Ceníky', $user, [
    'podnadpis' => 'Ceníky kurtů v létě a v zimě, členství, tenisové školy, kempů a doplňkových služeb. Každý ceník má sekce a v nich řádky s cenami.',
    'akce'      => '<a class="btn btn-ghost" href="#novy">Nový ceník</a>',
]);
echo obsah_assets();
?>

<div class="stats">
  <div class="stat"><b><?= cislo(count($ceniky)) ?></b><span>Ceníků</span><small><?= cislo($zapnute) ?> na webu</small></div>
  <div class="stat"><b><?= cislo(array_sum($sekci)) ?></b><span>Sekcí</span></div>
  <div class="stat"><b><?= cislo(array_sum($radku)) ?></b><span>Řádků s cenou</span></div>
</div>

<section class="panel">
  <div class="panel-head"><h2>Ceníky na webu</h2></div>
  <?php if (!$ceniky): ?>
    <?= prazdny_stav('Zatím tu není žádný ceník', 'Založte první formulářem dole.') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Ceník</th><th>Kde na webu</th><th>Obsah</th><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($ceniky as $i => $c): $cid = (int)$c['id']; $znamy = CENIKY_KLICE[$c['klic']] ?? null; ?>
          <tr<?= (int)$c['visible'] ? '' : ' class="je-skryte"' ?>>
            <td data-label="Ceník" class="td-nazev">
              <b><a href="cenik-edit.php?id=<?= $cid ?>"><?= e($c['nazev']) ?></a></b>
              <small><?= $c['podnazev'] !== '' ? e($c['podnazev']) : '' ?><?= $c['obdobi'] !== '' ? ($c['podnazev'] !== '' ? ' · ' : '') . e($c['obdobi']) : '' ?></small>
            </td>
            <td data-label="Kde na webu" class="td-mala">
              <span class="klic"><?= e($c['klic']) ?></span><br>
              <?php if ($znamy): ?>
                <a href="<?= e(url($znamy[1])) ?>" target="_blank" rel="noopener"><?= e($znamy[1]) ?> ↗</a>
              <?php else: ?>
                <?= badge('web ho zatím nečte', 'warn') ?>
              <?php endif; ?>
            </td>
            <td data-label="Obsah" class="td-mala nowrap">
              <?php $ns = $sekci[$cid] ?? 0; $nr = $radku[$cid] ?? 0; ?>
              <?= cislo($ns) ?> <?= sklonuj($ns, 'sekce', 'sekce', 'sekcí') ?>,
              <?= cislo($nr) ?> <?= sklonuj($nr, 'řádek', 'řádky', 'řádků') ?>
            </td>
            <td data-label="Stav"><?= stav_badge($c['visible']) ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitka_poradi($cid, $i === 0, $i === $posl) ?>
              <a class="btn btn-sm btn-primary" href="cenik-edit.php?id=<?= $cid ?>">Upravit ceny</a>
              <?= tlacitko_prepnout($cid, $c['visible']) ?>
              <?= tlacitko_smazat($cid, 'Smazat ceník „' . $c['nazev'] . '“ i se všemi sekcemi a cenami? Nejde to vrátit.'
                  . ($znamy ? ' Stránka ' . $znamy[1] . ' pak tento ceník neukáže – když ho chcete jen schovat, použijte Skrýt.' : '')) ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="panel" id="novy">
  <div class="panel-head"><h2>Nový ceník</h2></div>
  <div class="panel-body">
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="pridat">
      <?= pole_radek([
            pole_text('nazev', 'Název', '', ['required' => true, 'maxlength' => 160, 'placeholder' => 'Kurty – zimní sezóna 2027/28']),
            pole_text('podnazev', 'Podnázev', '', ['maxlength' => 200, 'placeholder' => 'Cena za hodinu a předplatné']),
          ]) ?>
      <?= pole_radek([
            pole_select('klic', 'Klíč (kde se ceník ukáže)', array_key_first($volneKlice), $volneKlice,
                ['hint' => 'Stránky webu hledají ceník podle klíče. Šest známých klíčů je už použitých – nový ceník s vlastním klíčem web neukáže, dokud ho stránka nezačne číst.']),
            pole_text('klic_vlastni', 'Vlastní klíč', '', ['maxlength' => 40, 'placeholder' => 'např. turnaje', 'hint' => 'Jen když výše vyberete „Vlastní klíč“. Malá písmena bez diakritiky a pomlčky.']),
          ]) ?>
      <?= pole_check('visible', 'Rovnou zobrazit na webu', false) ?>
      <?= tlacitka_formulare('Založit ceník') ?>
    </form>
  </div>
</section>

<?php admin_foot(); ?>
