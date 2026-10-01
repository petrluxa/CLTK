<?php
/* Centenary Tennis Clubs – obsah stránky ctc.php (cltk_ctc, typ = fakt | klub | utkani |
   soutez | rodokmen). U rodokmenu stoletých klubů zvyraznit = 1 označí I. ČLTK Praha.
   Podpoložka modulu Vedení a CTC. Adresy: ctc.php?typ=klub, …&nova=1, ctc.php?id=5. */
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

/** typ => [záložka, popis, tlačítko Přidat, nadpis formuláře]. */
const CTC_TYPY = [
    'fakt'     => ['O sdružení', 'Odstavce o Centenary Tennis Clubs a členství klubu.', 'Přidat odstavec', 'odstavec'],
    'klub'     => ['Kluby na Štvanici', 'Kluby CTC, které hrály na Štvanici přátelské utkání.', 'Přidat klub', 'klub'],
    'utkani'   => ['Utkání', 'Mezinárodní utkání a návštěvy klubů na Štvanici.', 'Přidat utkání', 'utkání'],
    'soutez'   => ['Soutěže', 'Pravidelné soutěže – Carrickmines Cup, I. ČLTK Praha Cup, CTC Senior.', 'Přidat soutěž', 'soutěž'],
    'rodokmen' => ['Rodokmen stoletých', 'Rok založení stoletých klubů. Zvýrazněný řádek = I. ČLTK Praha.', 'Přidat klub do rodokmenu', 'řádek rodokmenu'],
];

$id = (int)($_GET['id'] ?? 0);
$z  = $id ? row('SELECT * FROM cltk_ctc WHERE id = ?', [$id]) : null;
if ($id && !$z) redirect('ctc.php', 'Záznam nebyl nalezen – možná byl mezitím smazán.', 'err');
$typ = $z ? (string)$z['typ'] : obsah_get('typ', 'fakt', true);
if (!isset(CTC_TYPY[$typ])) $typ = 'fakt';
$rezim = $z ? 'uprava' : (!empty($_GET['nova']) ? 'nova' : 'seznam');
$seznamUrl = 'ctc.php?typ=' . $typ;

$f = [
    'nazev' => (string)($z['nazev'] ?? ''), 'rok' => (string)($z['rok'] ?? ''), 'misto' => (string)($z['misto'] ?? ''),
    'text' => (string)($z['text'] ?? ''), 'odkaz' => (string)($z['odkaz'] ?? ''), 'zdroj' => (string)($z['zdroj'] ?? ''),
    'zvyraznit' => (int)($z['zvyraznit'] ?? 0), 'visible' => (int)($z['visible'] ?? 1),
];
$chyby = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek($seznamUrl);
    $akce = vstup('action', 20);

    if ($akce === 'ulozit') {
        $f = [
            'nazev' => obsah_pole('nazev', 200), 'rok' => obsah_pole('rok', 30), 'misto' => obsah_pole('misto', 120),
            'text' => obsah_pole('text', 6000), 'odkaz' => vstup('odkaz', 255), 'zdroj' => obsah_pole('zdroj', 255),
            'zvyraznit' => $typ === 'rodokmen' ? vstup_bool('zvyraznit') : 0, 'visible' => vstup_bool('visible'),
        ];
        if ($f['nazev'] === '') $chyby['nazev'] = $typ === 'fakt' ? 'Vyplňte nadpis odstavce.' : 'Vyplňte název.';
        if ($typ === 'rodokmen' && $f['rok'] === '') $chyby['rok'] = 'U rodokmenu vyplňte rok založení klubu.';
        $odkaz = obsah_odkaz($f['odkaz']);
        if ($odkaz === false) $chyby['odkaz'] = 'Odkaz musí začínat https:// nebo být stránka webu (např. akce.php?id=12).';
        if (!$chyby) {
            $data = array_merge($f, ['typ' => $typ, 'odkaz' => (string)$odkaz]);
            if ($z) db_update('cltk_ctc', $id, $data);
            else { $data['poradi'] = admin_dalsi_poradi('cltk_ctc', 'typ', $typ); $id = db_insert('cltk_ctc', $data); }
            redirect($seznamUrl . '#c' . $id, 'Záznam „' . $f['nazev'] . '“ ' . ($z ? 'je uložený.' : 'je přidaný na konec seznamu.'));
        }
        $rezim = $z ? 'uprava' : 'nova';
    } else {
        $rid = (int)vstup_int('id');
        $x = $rid ? row('SELECT * FROM cltk_ctc WHERE id = ?', [$rid]) : null;
        if (!$x) redirect($seznamUrl, 'Záznam nebyl nalezen – možná byl mezitím smazán.', 'err');
        $seznamUrl = 'ctc.php?typ=' . (isset(CTC_TYPY[$x['typ']]) ? $x['typ'] : 'fakt');
        if ($akce === 'prepnout') {
            $novy = admin_prepni('cltk_ctc', $rid);
            redirect($seznamUrl . '#c' . $rid, 'Záznam „' . $x['nazev'] . '“ je teď na webu ' . ($novy ? 'vidět.' : 'skrytý.'));
        }
        if ($akce === 'nahoru' || $akce === 'dolu') {
            admin_posun('cltk_ctc', $rid, $akce === 'nahoru' ? -1 : 1, 'typ');
            redirect($seznamUrl . '#c' . $rid, 'Pořadí bylo změněno.');
        }
        if ($akce === 'smazat') {
            q('DELETE FROM cltk_ctc WHERE id = ?', [$rid]);
            redirect($seznamUrl, 'Záznam „' . $x['nazev'] . '“ byl smazán.');
        }
        redirect($seznamUrl, 'Neznámý požadavek.', 'err');
    }
}

$pocty = array_fill_keys(array_keys(CTC_TYPY), 0);
foreach (rows('SELECT typ, COUNT(*) AS n FROM cltk_ctc GROUP BY typ') as $r) if (isset($pocty[$r['typ']])) $pocty[$r['typ']] = (int)$r['n'];
$zalozky = [];
foreach (CTC_TYPY as $k => $t) $zalozky['ctc.php?typ=' . $k] = $t[0] . ' · ' . $pocty[$k];

if ($rezim === 'seznam') {
    admin_head('Centenary Tennis Clubs', $user, [
        'podnadpis' => 'Obsah stránky CTC – sdružení tenisových klubů starších 100 let. Úvodní text stránky a rodokmenu je ve <a href="stranky.php?stranka=ctc">Stránkách</a>.',
        'akce' => '<a class="btn btn-primary" href="ctc.php?typ=' . e($typ) . '&amp;nova=1">' . e(CTC_TYPY[$typ][2]) . '</a>',
    ]);
} else {
    admin_head(($z ? 'Upravit ' : 'Nový záznam – ') . CTC_TYPY[$typ][3], $user, ['zpet' => [$seznamUrl, 'CTC – ' . CTC_TYPY[$typ][0]], 'sirka' => 'uzka']);
}
echo obsah_assets();

if ($rezim === 'seznam'):
    $radky = rows('SELECT * FROM cltk_ctc WHERE typ = ? ORDER BY poradi, id', [$typ]);
    $posl = count($radky) - 1;
?>
<?= admin_zalozky($zalozky, $seznamUrl) ?>
<section class="panel">
  <div class="panel-head">
    <h2><?= e(CTC_TYPY[$typ][0]) ?> <small><?= cislo(count($radky)) ?></small></h2>
    <a class="btn btn-sm btn-ghost" href="<?= e(url('ctc.php')) ?>" target="_blank" rel="noopener">Zobrazit na webu <span aria-hidden="true">↗</span></a>
  </div>
  <div class="panel-body"><p class="hint"><?= e(CTC_TYPY[$typ][1]) ?></p></div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím tu nic není', '', '<a class="btn btn-primary" href="ctc.php?typ=' . e($typ) . '&amp;nova=1">' . e(CTC_TYPY[$typ][2]) . '</a>') ?>
  <?php else: ?>
  <div class="tbl-wrap tbl-karty">
    <table>
      <thead><tr><th>Rok</th><th>Název</th><th>Text a pramen</th><th>Stav</th><th class="right">Akce</th></tr></thead>
      <tbody>
      <?php foreach ($radky as $i => $r): $rid = (int)$r['id']; ?>
        <tr id="c<?= $rid ?>"<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
          <td data-label="Rok"><span class="td-rok"><?= e($r['rok'] !== '' ? $r['rok'] : '–') ?></span></td>
          <td data-label="Název" class="td-nazev"><b><a href="ctc.php?id=<?= $rid ?>"><?= e($r['nazev']) ?></a></b>
            <?php if ($r['misto'] !== ''): ?><small><?= e($r['misto']) ?></small><?php endif; ?>
            <?php if ((int)$r['zvyraznit']): ?><small><?= badge('zvýrazněno', 'zlato') ?></small><?php endif; ?></td>
          <td data-label="Text a pramen" class="td-mala"><?= e(uryvek($r['text'], 120)) ?><?php if ($r['zdroj'] !== ''): ?><br><i>pramen: <?= e(uryvek($r['zdroj'], 60)) ?></i><?php endif; ?></td>
          <td data-label="Stav"><?= stav_badge($r['visible']) ?></td>
          <td data-label="Akce" class="right"><div class="akce-radku">
            <?= tlacitka_poradi($rid, $i === 0, $i === $posl, ['typ' => $typ]) ?>
            <a class="btn btn-sm btn-ghost" href="ctc.php?id=<?= $rid ?>">Upravit</a>
            <?= tlacitko_prepnout($rid, $r['visible'], ['typ' => $typ]) ?>
            <?= tlacitko_smazat($rid, 'Smazat „' . $r['nazev'] . '“? Nejde to vrátit.', ['typ' => $typ]) ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<?php else: /* ---------- formulář ---------- */ ?>
<?= obsah_chyby_box($chyby) ?>
<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="typ" value="<?= e($typ) ?>">
      <?= pole_text('nazev', $typ === 'fakt' ? 'Nadpis' : 'Název', $f['nazev'], ['required' => true, 'maxlength' => 200, 'hint' => obsah_chyba($chyby, 'nazev')]) ?>
      <?= pole_radek([
            pole_text('rok', $typ === 'rodokmen' ? 'Rok založení' : 'Rok', $f['rok'], ['maxlength' => 30, 'required' => $typ === 'rodokmen', 'hint' => obsah_chyba($chyby, 'rok')]),
            pole_text('misto', 'Místo', $f['misto'], ['maxlength' => 120, 'placeholder' => 'Dublin']),
          ]) ?>
      <?= pole_textarea('text', 'Text', $f['text'], ['rows' => 4]) ?>
      <?= pole_text('odkaz', 'Odkaz (nepovinný)', $f['odkaz'], ['maxlength' => 255, 'placeholder' => 'https://…', 'hint' => obsah_chyba($chyby, 'odkaz')]) ?>
      <?= pole_text('zdroj', 'Pramen', $f['zdroj'], ['maxlength' => 255, 'hint' => 'Odkud údaj je (web klubu, Revue…). Na webu se ukáže drobně, nebo vůbec.']) ?>
      <?php if ($typ === 'rodokmen'): ?>
        <?= pole_check('zvyraznit', 'Zvýraznit (I. ČLTK Praha)', (bool)$f['zvyraznit']) ?>
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
      <?= tlacitko_smazat($id, 'Opravdu smazat „' . $z['nazev'] . '“? Nejde to vrátit.', ['typ' => $typ], 'Smazat záznam') ?>
    </div></div>
  </section>
<?php endif; ?>
<?php endif; ?>

<?php admin_foot(); ?>
