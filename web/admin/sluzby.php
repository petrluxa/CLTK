<?php
/* Areál a služby – služby v areálu (cltk_sluzby): sekce stránky areal.php
   (kotva = id sekce) a štítky „Služby v areálu“ na úvodní stránce (je_stitek = 1,
   každý vede na areal.php#kotva). Pořadí platí pro stránku i pro štítky.
   Adresy: sluzby.php (seznam), ?nova=1, ?id=5. */
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

$id = (int)($_GET['id'] ?? 0);
$sl = $id ? row('SELECT * FROM cltk_sluzby WHERE id = ?', [$id]) : null;
if ($id && !$sl) redirect('sluzby.php', 'Služba nebyla nalezena – možná byla mezitím smazána.', 'err');
$rezim = $sl ? 'uprava' : (!empty($_GET['nova']) ? 'nova' : 'seznam');

$f = [
    'nazev' => (string)($sl['nazev'] ?? ''), 'kotva' => (string)($sl['kotva'] ?? ''), 'perex' => (string)($sl['perex'] ?? ''),
    'text' => (string)($sl['text'] ?? ''), 'fakta' => (string)($sl['fakta'] ?? ''), 'foto' => (string)($sl['foto'] ?? ''),
    'fokus' => (string)($sl['fokus'] ?? '50% 50%'), 'casy' => (string)($sl['casy'] ?? ''), 'odkaz' => (string)($sl['odkaz'] ?? ''),
    'je_stitek' => (int)($sl['je_stitek'] ?? 0), 'visible' => (int)($sl['visible'] ?? 1),
];
$chyby = [];
$bylSoubor = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek($sl ? 'sluzby.php?id=' . $id : 'sluzby.php');
    $akce = vstup('action', 20);

    if ($akce === 'ulozit') {
        $bylSoubor = obsah_byl_soubor();
        $f = [
            'nazev' => obsah_pole('nazev', 120), 'kotva' => vstup('kotva', 60), 'perex' => obsah_pole('perex', 255),
            'text' => obsah_pole('text', 8000), 'fakta' => obsah_pole('fakta', 4000), 'foto' => $f['foto'],
            'fokus' => vstup('fokus', 20), 'casy' => obsah_pole('casy', 160), 'odkaz' => vstup('odkaz', 255),
            'je_stitek' => vstup_bool('je_stitek'), 'visible' => vstup_bool('visible'),
        ];
        if ($f['nazev'] === '') $chyby['nazev'] = 'Vyplňte název služby.';
        $kotva = $f['kotva'] !== '' ? $f['kotva'] : ($f['nazev'] !== '' ? trim(substr(slugify($f['nazev']), 0, 60), '-') : '');
        if ($kotva !== '' && !preg_match('/^[a-z0-9][a-z0-9-]{0,59}$/', $kotva)) {
            $chyby['kotva'] = 'Kotva smí mít jen malá písmena bez diakritiky, číslice a pomlčky (např. venkovni-bazen).';
        } elseif ($kotva !== '' && row('SELECT id FROM cltk_sluzby WHERE kotva = ? AND id <> ?', [$kotva, $id])) {
            $chyby['kotva'] = 'Kotvu „' . $kotva . '“ už má jiná služba – zvolte jinou.';
        }
        $f['kotva'] = $kotva;
        $fokus = obsah_fokus($f['fokus']);
        if ($fokus === false) $chyby['fokus'] = 'Ohnisko zapište jako „50% 40%“.';
        $odkaz = obsah_odkaz($f['odkaz']);
        if ($odkaz === false) $chyby['odkaz'] = 'Odkaz musí začínat https://, mailto:, tel: nebo být stránka webu (např. cenik-kurtu.php).';

        if (!$chyby) {
            $foto = admin_obrazek('foto', $sl['foto'] ?? '', 'sluzby', 2400, 2400, $f['nazev']);
            if ($foto['chyba'] !== '') {
                $chyby['foto'] = $foto['chyba'];
            } else {
                $data = [
                    'nazev' => $f['nazev'], 'kotva' => $kotva, 'perex' => $f['perex'], 'text' => $f['text'], 'fakta' => $f['fakta'],
                    'foto' => $foto['soubor'], 'fokus' => $fokus, 'casy' => $f['casy'], 'odkaz' => (string)$odkaz,
                    'je_stitek' => $f['je_stitek'], 'visible' => $f['visible'], 'updated_at' => ted(),
                ];
                if ($sl) {
                    db_update('cltk_sluzby', $id, $data);
                } else {
                    $data['poradi'] = admin_dalsi_poradi('cltk_sluzby');
                    $data['created_at'] = ted();
                    $id = db_insert('cltk_sluzby', $data);
                }
                obsah_smazat_nepouzite($foto['smazat'], [['cltk_sluzby', 'foto']]);
                redirect('sluzby.php#s' . $id, ($sl ? 'Služba „' : 'Přidána služba „') . $f['nazev'] . '“' . ($sl ? ' je uložená.' : '.'));
            }
        }
        $rezim = $sl ? 'uprava' : 'nova';
    } else {
        $rid = (int)vstup_int('id');
        $x = $rid ? row('SELECT * FROM cltk_sluzby WHERE id = ?', [$rid]) : null;
        if (!$x) redirect('sluzby.php', 'Služba nebyla nalezena – možná byla mezitím smazána.', 'err');
        if ($akce === 'prepnout') {
            $novy = admin_prepni('cltk_sluzby', $rid);
            redirect('sluzby.php#s' . $rid, 'Služba „' . $x['nazev'] . '“ je teď na webu ' . ($novy ? 'vidět.' : 'skrytá – nezobrazí se ani na stránce Areál, ani jako štítek.'));
        }
        if ($akce === 'stitek') {
            $novy = (int)$x['je_stitek'] === 1 ? 0 : 1;
            q('UPDATE cltk_sluzby SET je_stitek = ?, updated_at = ? WHERE id = ?', [$novy, ted(), $rid]);
            redirect('sluzby.php#s' . $rid, $novy ? '„' . $x['nazev'] . '“ je teď štítkem na úvodní stránce.' : '„' . $x['nazev'] . '“ už na úvodní stránce mezi štítky není.');
        }
        if ($akce === 'nahoru' || $akce === 'dolu') {
            admin_posun('cltk_sluzby', $rid, $akce === 'nahoru' ? -1 : 1);
            redirect('sluzby.php#s' . $rid, 'Pořadí bylo změněno.');
        }
        if ($akce === 'smazat') {
            q('DELETE FROM cltk_sluzby WHERE id = ?', [$rid]);
            obsah_smazat_nepouzite([(string)$x['foto']], [['cltk_sluzby', 'foto']]);
            redirect('sluzby.php', 'Služba „' . $x['nazev'] . '“ byla smazána.');
        }
        redirect('sluzby.php', 'Neznámý požadavek.', 'err');
    }
}

if ($rezim === 'seznam') {
    admin_head('Areál a služby', $user, [
        'podnadpis' => 'Služby v areálu – sekce stránky Areál a služby a štítky „Služby v areálu“ na úvodní stránce. Pořadí platí pro obojí.',
        'akce' => '<a class="btn btn-primary" href="sluzby.php?nova=1">Přidat službu</a>',
    ]);
} else {
    admin_head($sl ? $sl['nazev'] : 'Nová služba', $user, ['zpet' => ['sluzby.php', 'Areál a služby'], 'sirka' => 'uzka',
        'podnadpis' => $sl && $sl['kotva'] !== '' ? 'Na webu: <a href="' . e(url('areal.php') . '#' . $sl['kotva']) . '" target="_blank" rel="noopener">areal.php#' . e($sl['kotva']) . ' ↗</a>' : '']);
}
echo obsah_assets();

if ($rezim === 'seznam'):
    $radky = rows('SELECT * FROM cltk_sluzby ORDER BY poradi, id');
    $stitky = array_values(array_filter($radky, static fn($r) => (int)$r['je_stitek'] === 1));
    $posl = count($radky) - 1;
?>
<section class="panel">
  <div class="panel-head">
    <h2>Štítky na úvodní stránce <small><?= cislo(count(array_filter($stitky, static fn($r) => (int)$r['visible'] === 1))) ?> zobrazených</small></h2>
    <a class="btn btn-sm btn-ghost" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">Úvodní stránka <span aria-hidden="true">↗</span></a>
  </div>
  <div class="panel-body">
    <?php if ($stitky): ?>
      <div class="stitky">
        <?php foreach ($stitky as $r): ?>
          <a class="stitek<?= (int)$r['visible'] ? '' : ' je-skryte' ?>" href="sluzby.php?id=<?= (int)$r['id'] ?>" title="<?= (int)$r['visible'] ? 'Upravit' : 'Skrytá služba – štítek se nezobrazuje' ?>"><?= e($r['nazev']) ?></a>
        <?php endforeach; ?>
      </div>
      <p class="hint" style="margin-top:12px">Takhle jdou pilulky za sebou na úvodní stránce; každá vede na svou část stránky Areál. Štítek zapnete nebo vypnete tlačítkem „Na úvod“ v seznamu.</p>
    <?php else: ?>
      <p class="hint">Žádná služba nemá zapnutý štítek – sekce „Služby v areálu“ na úvodu bude prázdná.</p>
    <?php endif; ?>
  </div>
</section>

<section class="panel">
  <div class="panel-head">
    <h2>Služby <small><?= cislo(count($radky)) ?></small></h2>
    <a class="btn btn-sm btn-ghost" href="<?= e(url('areal.php')) ?>" target="_blank" rel="noopener">Stránka Areál <span aria-hidden="true">↗</span></a>
  </div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím žádné služby', '', '<a class="btn btn-primary" href="sluzby.php?nova=1">Přidat službu</a>') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Foto</th><th>Služba a otevírací doba</th><th>Stav a štítek</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($radky as $i => $r): $rid = (int)$r['id']; ?>
          <tr id="s<?= $rid ?>"<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
            <td data-label="Foto"><?= obsah_nahled_foto($r['foto'], 'thumb-sm', (string)$r['fokus']) ?></td>
            <td data-label="Služba a otevírací doba" class="td-nazev">
              <b><a href="sluzby.php?id=<?= $rid ?>"><?= e($r['nazev']) ?></a> <span class="klic">#<?= e($r['kotva']) ?></span></b>
              <small><?= e(uryvek($r['perex'], 90)) ?></small>
              <small><?= $r['casy'] !== '' ? 'otevřeno: ' . e($r['casy']) : 'otevírací doba: ' . badge('doplní klub', 'off') ?></small>
            </td>
            <td data-label="Stav a štítek"><div class="btn-row">
              <?= stav_badge($r['visible']) ?> <?= (int)$r['je_stitek'] ? badge('Štítek na úvodu', 'zlato') : '' ?>
              <?= tlacitko_akce('stitek', $rid, (int)$r['je_stitek'] ? 'Z úvodu pryč' : 'Na úvod', 'btn-ghost', [], '',
                  (int)$r['je_stitek'] ? 'Odebrat štítek z úvodní stránky' : 'Ukázat jako štítek na úvodní stránce') ?>
            </div></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitka_poradi($rid, $i === 0, $i === $posl) ?>
              <a class="btn btn-sm btn-ghost" href="sluzby.php?id=<?= $rid ?>">Upravit</a>
              <?= tlacitko_prepnout($rid, $r['visible']) ?>
              <?= tlacitko_smazat($rid, 'Smazat službu „' . $r['nazev'] . '“? Zmizí ze stránky Areál i ze štítků na úvodu. Nejde to vrátit.') ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php else: /* ---------- formulář ---------- */ ?>
<?= obsah_chyby_box($chyby, $bylSoubor) ?>
<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <?= pole_radek([
            pole_text('nazev', 'Název služby', $f['nazev'], ['required' => true, 'maxlength' => 120, 'hint' => obsah_chyba($chyby, 'nazev', 'Stejný text je i na štítku na úvodní stránce.')]),
            pole_text('kotva', 'Kotva na stránce Areál', $f['kotva'], ['maxlength' => 60, 'placeholder' => 'bazen',
                'hint' => obsah_chyba($chyby, 'kotva', 'Konec adresy areal.php#kotva. Prázdné = vytvoří se z názvu. Kotvu už použitou v odkazech raději neměňte.')]),
          ]) ?>
      <?= pole_text('perex', 'Jedna věta', $f['perex'], ['maxlength' => 255, 'sirka' => 'cela', 'placeholder' => 'Bazén s travnatou plochou – jen pro členy a jejich hosty.']) ?>
      <?= pole_textarea('text', 'Popis', $f['text'], ['rows' => 5, 'hint' => 'Odstavce oddělte prázdným řádkem.']) ?>
      <?= pole_textarea('fakta', 'Fakta', $f['fakta'], ['rows' => 4, 'hint' => 'Co řádek, to jeden fakt (ceny, pravidla, pro koho).']) ?>
      <?= pole_radek([
            pole_text('casy', 'Otevírací doba', $f['casy'], ['maxlength' => 160, 'placeholder' => 'všední dny 16:00–20:00', 'hint' => 'Prázdné = web ukáže „doplní klub“.']),
            pole_text('odkaz', 'Odkaz (nepovinný)', $f['odkaz'], ['maxlength' => 255, 'placeholder' => 'cenik-kurtu.php nebo https://…', 'hint' => obsah_chyba($chyby, 'odkaz')]),
          ]) ?>
      <?= pole_obrazek('foto', 'Fotka', $f['foto'], ['hint' => obsah_chyba($chyby, 'foto', 'Fotka na šířku. JPG, PNG nebo WEBP – zmenší se sama.' . ($f['foto'] !== '' ? ' Nová fotka nahradí stávající.' : ''))]) ?>
      <?= pole_fokus('fokus', 'Ohnisko fotky', $f['fokus'], $f['foto'], ['hint' => obsah_chyba($chyby, 'fokus', 'Klepněte do fotky na místo, které má při ořezu zůstat vidět.')]) ?>
      <?= pole_check('je_stitek', 'Štítek „Služby v areálu“ na úvodní stránce', (bool)$f['je_stitek']) ?>
      <?= pole_check('visible', 'Zobrazit na webu', (bool)$f['visible']) ?>
      <?= tlacitka_formulare($sl ? 'Uložit změny' : 'Přidat službu', 'sluzby.php', 'Zpět bez uložení') ?>
    </form>
  </div>
</section>
<?php if ($sl): ?>
  <section class="panel panel--nebezpeci">
    <div class="panel-head"><h2>Smazat službu</h2></div>
    <div class="panel-body"><div class="btn-row">
      <?= tlacitko_smazat($id, 'Opravdu smazat službu „' . $sl['nazev'] . '“? Nejde to vrátit.', [], 'Smazat službu') ?>
    </div></div>
  </section>
<?php endif; ?>
<?php endif; ?>

<?php admin_foot(); ?>
