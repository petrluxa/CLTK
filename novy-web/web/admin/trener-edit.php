<?php
/* Úprava jednoho trenéra (nový: trener-edit.php?zarazeni=skola, úprava: ?id=12).
   Pole podle SCHEMA.md – cltk_treneri. Fotka: nahrát → zapsat do DB → teprve pak
   smazat starou (a jen když ji nepoužívá jiný řádek – trenér ve dvou týmech).
   Při chybě se formulář vypíše znovu s vyplněnými hodnotami. */
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

const TRENER_TYMY = [
    'zavodni'  => ['Závodní tenis', 'zavodni-tenis-treneri.php'],
    'skola'    => ['Tenisová škola', 'tenisova-skola-treneri.php'],
    'privatni' => ['Privátní trenéři', 'privatni-treneri.php'],
];

$id = (int)($_GET['id'] ?? 0);
$t  = $id ? row('SELECT * FROM cltk_treneri WHERE id = ?', [$id]) : null;
if ($id && !$t) redirect('treneri.php', 'Takový trenér tu není – možná byl mezitím smazán.', 'err');

$tymNovy = obsah_get('zarazeni', 'zavodni');
if (!isset(TRENER_TYMY[$tymNovy])) $tymNovy = 'zavodni';

$f = [
    'jmeno'    => (string)($t['jmeno'] ?? ''),
    'role'     => (string)($t['role'] ?? ''),
    'zarazeni' => (string)($t['zarazeni'] ?? $tymNovy),
    'skupina'  => (string)($t['skupina'] ?? ''),
    'foto'     => (string)($t['foto'] ?? ''),
    'fokus'    => (string)($t['fokus'] ?? '50% 22%'),
    'fakta'    => (string)($t['fakta'] ?? ''),
    'text'     => (string)($t['text'] ?? ''),
    'telefon'  => (string)($t['telefon'] ?? ''),
    'email'    => (string)($t['email'] ?? ''),
    'kontakt'  => (string)($t['kontakt'] ?? ''),
    'visible'  => (int)($t['visible'] ?? 1),
];
if (!isset(TRENER_TYMY[$f['zarazeni']])) $f['zarazeni'] = 'zavodni';
$chyby = [];
$bylSoubor = false;
$tady = $t ? 'trener-edit.php?id=' . $id : 'trener-edit.php?zarazeni=' . $tymNovy;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek($tady);
    $akce = vstup('action', 20);

    /* ----- smazání (vlastní formulář dole na stránce) ----- */
    if ($akce === 'smazat' && $t) {
        q('DELETE FROM cltk_treneri WHERE id = ?', [$id]);
        obsah_smazat_nepouzite([(string)$t['foto']], [['cltk_treneri', 'foto']]);
        redirect('treneri.php?zarazeni=' . $f['zarazeni'], 'Trenér ' . $t['jmeno'] . ' byl smazán.');
    }

    /* ----- kopie do dalšího týmu (stejná fotka, texty se dají upravit zvlášť) ----- */
    if ($akce === 'kopirovat' && $t) {
        $cil = vstup('cil', 20);
        if (!isset(TRENER_TYMY[$cil]) || $cil === $t['zarazeni']) redirect($tady, 'Vyberte jiný tým, než ve kterém trenér už je.', 'err');
        if (row('SELECT id FROM cltk_treneri WHERE jmeno = ? AND zarazeni = ?', [$t['jmeno'], $cil])) {
            redirect($tady, $t['jmeno'] . ' už v týmu ' . TRENER_TYMY[$cil][0] . ' je.', 'warn');
        }
        $kopie = $t;
        unset($kopie['id']);
        $kopie['zarazeni'] = $cil;
        $kopie['skupina'] = (string)(val('SELECT skupina FROM cltk_treneri WHERE zarazeni = ? ORDER BY poradi DESC, id DESC', [$cil]) ?? '');
        $kopie['poradi'] = admin_dalsi_poradi('cltk_treneri', 'zarazeni', $cil);
        $kopie['created_at'] = ted();
        $kopie['updated_at'] = ted();
        $noveId = db_insert('cltk_treneri', $kopie);
        redirect('trener-edit.php?id=' . $noveId, $t['jmeno'] . ' je teď i v týmu ' . TRENER_TYMY[$cil][0]
            . '. Tady můžete upravit roli a podnadpis pro tento tým – změny se druhého řádku netýkají.');
    }

    if ($akce !== 'ulozit') redirect($tady, 'Neznámý požadavek.', 'err');

    $bylSoubor = obsah_byl_soubor();
    $f['jmeno']    = obsah_pole('jmeno', 120);
    $f['role']     = obsah_pole('role', 160);
    $f['zarazeni'] = vstup('zarazeni', 20);
    $f['skupina']  = obsah_pole('skupina', 120);
    $f['fakta']    = obsah_pole('fakta', 4000);
    $f['text']     = obsah_pole('text', 8000);
    $f['telefon']  = obsah_pole('telefon', 40);
    $f['email']    = mb_strtolower(obsah_pole('email', 160));
    $f['kontakt']  = obsah_pole('kontakt', 255);
    $f['visible']  = vstup_bool('visible');
    $fokus = obsah_fokus(vstup('fokus', 30), '50% 22%');
    $f['fokus'] = $fokus === false ? vstup('fokus', 20) : $fokus;

    if ($f['jmeno'] === '') $chyby['jmeno'] = 'Vyplňte prosím jméno trenéra.';
    if (!isset(TRENER_TYMY[$f['zarazeni']])) { $chyby['zarazeni'] = 'Vyberte tým.'; $f['zarazeni'] = 'zavodni'; }
    if ($f['email'] !== '' && !je_email($f['email'])) $chyby['email'] = 'E-mail nevypadá správně (např. jmeno@cltk.cz).';
    if ($f['telefon'] !== '' && !preg_match('/^[+\d][\d \-\/()]{5,}$/', $f['telefon'])) $chyby['telefon'] = 'Telefon nevypadá jako telefonní číslo (např. +420 721 663 118).';
    if ($fokus === false) $chyby['fokus'] = 'Ohnisko zapište jako „50% 20%“ (vodorovně a svisle, 0–100 %).';

    if (!$chyby) {
        $foto = admin_obrazek('foto', $t['foto'] ?? '', 'treneri', 1200, 1600, $f['jmeno']);
        if ($foto['chyba'] !== '') {
            $chyby['foto'] = $foto['chyba'];
        } else {
            $data = [
                'jmeno' => $f['jmeno'], 'role' => $f['role'], 'zarazeni' => $f['zarazeni'], 'skupina' => $f['skupina'],
                'foto' => $foto['soubor'], 'fokus' => $f['fokus'], 'fakta' => $f['fakta'], 'text' => $f['text'],
                'telefon' => $f['telefon'], 'email' => $f['email'], 'kontakt' => $f['kontakt'], 'visible' => $f['visible'],
                'updated_at' => ted(),
            ];
            if ($t) {
                // přesun do jiného týmu = na konec nového týmu
                if ($f['zarazeni'] !== $t['zarazeni']) $data['poradi'] = admin_dalsi_poradi('cltk_treneri', 'zarazeni', $f['zarazeni']);
                db_update('cltk_treneri', $id, $data);
                $zprava = 'Změny u trenéra ' . $f['jmeno'] . ' jsou uložené.';
            } else {
                $data['poradi'] = admin_dalsi_poradi('cltk_treneri', 'zarazeni', $f['zarazeni']);
                $data['created_at'] = ted();
                $id = db_insert('cltk_treneri', $data);
                $zprava = 'Trenér ' . $f['jmeno'] . ' byl přidán do týmu ' . TRENER_TYMY[$f['zarazeni']][0] . '.';
            }
            obsah_smazat_nepouzite($foto['smazat'], [['cltk_treneri', 'foto']]);     // až po zápisu
            redirect('treneri.php?zarazeni=' . $f['zarazeni'] . '#t' . $id, $zprava);
        }
    }
}

$skupiny = array_column(rows("SELECT DISTINCT skupina FROM cltk_treneri WHERE skupina <> '' ORDER BY skupina"), 'skupina');
$tymy = array_map(static fn($x) => $x[0], TRENER_TYMY);
$dalsiTymy = $t ? array_filter($tymy, static fn($k) => $k !== $t['zarazeni'], ARRAY_FILTER_USE_KEY) : [];
$jinde = $t ? rows('SELECT id, zarazeni FROM cltk_treneri WHERE jmeno = ? AND id <> ?', [$t['jmeno'], $id]) : [];

admin_head($t ? $t['jmeno'] : 'Nový trenér', $user, [
    'zpet'  => ['treneri.php?zarazeni=' . $f['zarazeni'], 'Trenéři'],
    'sirka' => 'uzka',
    'podnadpis' => $t ? 'Tým ' . e(TRENER_TYMY[$t['zarazeni']][0] ?? '') . ' · na webu: <a href="' . e(url(TRENER_TYMY[$t['zarazeni']][1] ?? '')) . '" target="_blank" rel="noopener">stránka týmu ↗</a>' : '',
]);
echo obsah_assets();
?>

<?= obsah_chyby_box($chyby, $bylSoubor) ?>
<?php if ($jinde): ?>
  <div class="upozorneni">Tento trenér je i v týmu
    <?= implode(', ', array_map(static fn($j) => '<a href="trener-edit.php?id=' . (int)$j['id'] . '">' . e(TRENER_TYMY[$j['zarazeni']][0] ?? $j['zarazeni']) . '</a>', $jinde)) ?>
    – tam má vlastní řádek (roli a podnadpis). Fotku mají společnou.</div>
<?php endif; ?>

<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <?= pole_radek([
            pole_text('jmeno', 'Jméno a příjmení', $f['jmeno'], ['required' => true, 'maxlength' => 120, 'hint' => obsah_chyba($chyby, 'jmeno', 'I s tituly, jak se má ukázat na webu (Mgr. Jan Pecha, Ph.D.).')]),
            pole_text('role', 'Role', $f['role'], ['maxlength' => 160, 'placeholder' => 'např. trenér SVT', 'hint' => 'Krátce pod jménem.']),
          ]) ?>
      <?= pole_radek([
            pole_select('zarazeni', 'Tým', $f['zarazeni'], $tymy, ['hint' => obsah_chyba($chyby, 'zarazeni', 'Na které stránce webu se trenér ukáže.')]),
            pole_text('skupina', 'Podnadpis ve skupině', $f['skupina'], ['maxlength' => 120, 'placeholder' => 'např. Trenéři kategorie do 14 let',
                'attrs' => ['list' => 'skupiny-treneru'], 'hint' => 'Nepovinné. Trenéři se stejným podnadpisem jsou na webu pod sebou.']),
          ]) ?>
      <datalist id="skupiny-treneru"><?php foreach ($skupiny as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?></datalist>

      <?= pole_obrazek('foto', 'Portrét', $f['foto'], ['nahled' => 'ctverec', 'hint' => obsah_chyba($chyby, 'foto',
            'Fotka na výšku (portrét). JPG, PNG nebo WEBP – velikost a natočení se upraví samy.' . ($f['foto'] !== '' ? ' Nová fotka nahradí stávající.' : ''))]) ?>
      <?= pole_fokus('fokus', 'Ohnisko portrétu', $f['fokus'], $f['foto'], ['hint' => obsah_chyba($chyby, 'fokus',
            'Klepněte do fotky na obličej – při ořezu na webu zůstane vidět.')]) ?>

      <?= pole_textarea('fakta', 'Fakta', $f['fakta'], ['rows' => 5, 'hint' => 'Co řádek, to jeden fakt (např. „bývalý daviscupový reprezentant“).']) ?>
      <?= pole_textarea('text', 'Text', $f['text'], ['rows' => 5, 'hint' => 'Nepovinný delší popis. Odstavce oddělte prázdným řádkem.']) ?>

      <fieldset>
        <legend>Kontakt</legend>
        <div class="form-mrizka">
          <?= pole_text('telefon', 'Telefon', $f['telefon'], ['type' => 'tel', 'maxlength' => 40, 'placeholder' => '+420 …', 'hint' => obsah_chyba($chyby, 'telefon')]) ?>
          <?= pole_text('email', 'E-mail', $f['email'], ['type' => 'email', 'maxlength' => 160, 'hint' => obsah_chyba($chyby, 'email')]) ?>
          <?= pole_text('kontakt', 'Jiný kontakt', $f['kontakt'], ['maxlength' => 255, 'placeholder' => 'např. přes recepci', 'sirka' => 'cela',
                'hint' => 'Volný text místo telefonu a e-mailu, když trenér nechce mít na webu vlastní čísla.']) ?>
        </div>
      </fieldset>

      <?= pole_check('visible', 'Zobrazit na webu', (bool)$f['visible'], ['hint' => 'Odškrtnutý trenér zůstane uložený, jen ho návštěvníci neuvidí.']) ?>
      <?= tlacitka_formulare($t ? 'Uložit změny' : 'Přidat trenéra', 'treneri.php?zarazeni=' . $f['zarazeni'], 'Zpět bez uložení') ?>
    </form>
  </div>
</section>

<?php if ($t): ?>
  <?php if ($dalsiTymy): ?>
  <section class="panel">
    <div class="panel-head"><h2>Trénuje i v jiném týmu?</h2></div>
    <div class="panel-body">
      <form method="post" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="kopirovat">
        <?= pole_select('cil', 'Přidat i do týmu', '', $dalsiTymy, ['hint' => 'Vznikne druhý řádek se stejnou fotkou a texty; roli a podnadpis pak upravíte zvlášť.']) ?>
        <div class="form-actions"><button type="submit" class="btn btn-ghost">Přidat i do tohoto týmu</button></div>
      </form>
    </div>
  </section>
  <?php endif; ?>

  <section class="panel panel--nebezpeci">
    <div class="panel-head"><h2>Smazat trenéra</h2></div>
    <div class="panel-body">
      <p class="hint">Smaže se jen tento řádek (tým <?= e(TRENER_TYMY[$t['zarazeni']][0] ?? '') ?>). Když trenéra chcete jen dočasně schovat, odškrtněte „Zobrazit na webu“.</p>
      <div class="btn-row" style="margin-top:12px">
        <?= tlacitko_smazat($id, 'Opravdu smazat trenéra „' . $t['jmeno'] . '“? Nejde to vrátit.', [], 'Smazat trenéra') ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php admin_foot(); ?>
