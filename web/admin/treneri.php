<?php
/* Trenéři – trenérské týmy Závodního tenisu a Tenisové školy a privátní trenéři.
   Tým = sloupec zarazeni; kdo je ve dvou týmech, má dva řádky (sdílí fotku – ta se
   smaže, až ji nepoužívá nikdo). Pořadí se řadí v rámci týmu; podnadpis (skupina)
   dělí tým na webu do skupin („Trenéři kategorie do 14 let“…).
   Úprava jednoho trenéra je v trener-edit.php. */
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

/** Týmy: zarazeni => [název, stránka webu]. */
const TRENERI_TYMY = [
    'zavodni'  => ['Závodní tenis', 'zavodni-tenis-treneri.php'],
    'skola'    => ['Tenisová škola', 'tenisova-skola-treneri.php'],
    'privatni' => ['Privátní trenéři', 'privatni-treneri.php'],
];

$tym = obsah_get('zarazeni', 'zavodni');
if (!isset(TRENERI_TYMY[$tym])) $tym = 'zavodni';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $zpetTym = vstup('zarazeni', 20);
    $zpet = 'treneri.php?zarazeni=' . (isset(TRENERI_TYMY[$zpetTym]) ? $zpetTym : 'zavodni');
    admin_post_zacatek($zpet);
    $akce = vstup('action', 20);
    $id   = (int)vstup_int('id');
    $t    = $id ? row('SELECT * FROM cltk_treneri WHERE id = ?', [$id]) : null;
    if (!$t) redirect($zpet, 'Tento trenér už v seznamu není – možná ho mezitím někdo smazal.', 'err');
    $zpet = 'treneri.php?zarazeni=' . (isset(TRENERI_TYMY[$t['zarazeni']]) ? $t['zarazeni'] : 'zavodni');

    if ($akce === 'prepnout') {
        $novy = admin_prepni('cltk_treneri', $id);
        redirect($zpet, $novy ? $t['jmeno'] . ' je teď na webu vidět.' : $t['jmeno'] . ' je skrytý – na webu se nezobrazuje, ale zůstává uložený.');
    }
    if ($akce === 'nahoru' || $akce === 'dolu') {
        admin_posun('cltk_treneri', $id, $akce === 'nahoru' ? -1 : 1, 'zarazeni');
        redirect($zpet . '#t' . $id, 'Pořadí bylo změněno.');
    }
    if ($akce === 'smazat') {
        q('DELETE FROM cltk_treneri WHERE id = ?', [$id]);
        obsah_smazat_nepouzite([(string)$t['foto']], [['cltk_treneri', 'foto']]);
        redirect($zpet, 'Trenér ' . $t['jmeno'] . ' byl smazán.');
    }
    redirect($zpet, 'Neznámý požadavek.', 'err');
}

$pocty = array_fill_keys(array_keys(TRENERI_TYMY), 0);
foreach (rows('SELECT zarazeni, COUNT(*) AS n FROM cltk_treneri GROUP BY zarazeni') as $r) {
    if (isset($pocty[$r['zarazeni']])) $pocty[$r['zarazeni']] = (int)$r['n'];
}
$radky = rows('SELECT * FROM cltk_treneri WHERE zarazeni = ? ORDER BY poradi, id', [$tym]);
$naWebu = count(array_filter($radky, static fn($r) => (int)$r['visible'] === 1));
/* jména, která jsou ve víc týmech (jen pro informaci v seznamu) */
$veVice = [];
foreach (rows('SELECT jmeno FROM cltk_treneri GROUP BY jmeno HAVING COUNT(DISTINCT zarazeni) > 1') as $r) $veVice[$r['jmeno']] = true;

$zalozky = [];
foreach (TRENERI_TYMY as $k => [$nazev]) $zalozky['treneri.php?zarazeni=' . $k] = $nazev . ' · ' . $pocty[$k];

admin_head('Trenéři', $user, [
    'podnadpis' => 'Trenérské týmy na stránkách Závodní tenis, Tenisová škola a Privátní trenéři. Šipkami měníte pořadí uvnitř týmu – stejně se trenéři seřadí na webu.',
    'akce'      => '<a class="btn btn-primary" href="trener-edit.php?zarazeni=' . e($tym) . '">Přidat trenéra</a>',
]);
echo obsah_assets();
?>

<?= admin_zalozky($zalozky, 'treneri.php?zarazeni=' . $tym) ?>

<section class="panel">
  <div class="panel-head">
    <h2><?= e(TRENERI_TYMY[$tym][0]) ?> <small><?= cislo($naWebu) ?> na webu z <?= cislo(count($radky)) ?></small></h2>
    <a class="btn btn-sm btn-ghost" href="<?= e(url(TRENERI_TYMY[$tym][1])) ?>" target="_blank" rel="noopener">Zobrazit na webu <span aria-hidden="true">↗</span></a>
  </div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('V týmu zatím nikdo není', 'Přidejte prvního trenéra – stránka týmu na webu se naplní sama.',
        '<a class="btn btn-primary" href="trener-edit.php?zarazeni=' . e($tym) . '">Přidat trenéra</a>') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Foto</th><th>Jméno a role</th><th>Kontakt</th><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php $skupina = null; $posl = count($radky) - 1;
        foreach ($radky as $i => $r):
            $rid = (int)$r['id'];
            if ($r['skupina'] !== $skupina): $skupina = (string)$r['skupina']; ?>
          <tr class="tr-skupina"><td colspan="5"><?= $skupina !== '' ? e($skupina) : 'Bez podnadpisu' ?></td></tr>
        <?php endif; ?>
          <tr id="t<?= $rid ?>"<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
            <td data-label="Foto"><?= obsah_nahled_foto($r['foto'], 'thumb-portret', (string)$r['fokus']) ?></td>
            <td data-label="Jméno a role" class="td-nazev">
              <b><a href="trener-edit.php?id=<?= $rid ?>"><?= e($r['jmeno']) ?></a></b>
              <small><?= $r['role'] !== '' ? e($r['role']) : 'role nevyplněna' ?></small>
              <?php if (isset($veVice[$r['jmeno']])): ?><small><?= badge('i v jiném týmu', 'info') ?></small><?php endif; ?>
            </td>
            <td data-label="Kontakt" class="td-mala">
              <?php $k = array_filter([(string)$r['telefon'], (string)$r['email'], (string)$r['kontakt']], static fn($x) => $x !== ''); ?>
              <?= $k ? implode('<br>', array_map('e', $k)) : '–' ?>
            </td>
            <td data-label="Stav"><?= stav_badge($r['visible']) ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitka_poradi($rid, $i === 0, $i === $posl, ['zarazeni' => $tym]) ?>
              <a class="btn btn-sm btn-ghost" href="trener-edit.php?id=<?= $rid ?>">Upravit</a>
              <?= tlacitko_prepnout($rid, $r['visible'], ['zarazeni' => $tym]) ?>
              <?= tlacitko_smazat($rid, 'Smazat trenéra „' . $r['jmeno'] . '“ z týmu ' . TRENERI_TYMY[$tym][0] . '? Nejde to vrátit – když ho chcete jen schovat, použijte Skrýt.', ['zarazeni' => $tym]) ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<p class="hint">Podnadpis (např. „Trenéři kategorie do 14 let“) se nastavuje u každého trenéra. Trenéři se stejným podnadpisem
  by měli jít v pořadí za sebou – web je pod podnadpisem seskupí tak, jak jdou v seznamu.</p>

<?php admin_foot(); ?>
