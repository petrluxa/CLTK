<?php
/* Vedení klubu – Výkonný výbor, kancelář klubu a další kontakty (cltk_vedeni).
   zobrazit_kontakt = 0 → telefon a e-mail se na webu nevypíšou (klient nechce
   na webu čísla Petra Vaníčka a Vladislava Šavrdy – ZADANI §4.10).
   Prezidenti klubu jsou ve Zlaté desce (Historie). Centenary Tennis Clubs: ctc.php.
   Adresy: vedeni.php, ?nova=1&skupina=kancelar, ?id=5. */
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

const VEDENI_SKUPINY = [
    'vybor'    => ['Výkonný výbor', 'člen výboru'],
    'kancelar' => ['Kancelář klubu', 'osoba kanceláře'],
    'kontakt'  => ['Další kontakty', 'kontakt'],
];

$id = (int)($_GET['id'] ?? 0);
$v  = $id ? row('SELECT * FROM cltk_vedeni WHERE id = ?', [$id]) : null;
if ($id && !$v) redirect('vedeni.php', 'Osoba nebyla nalezena – možná byla mezitím smazána.', 'err');
$rezim = $v ? 'uprava' : (!empty($_GET['nova']) ? 'nova' : 'seznam');
$skupinaNova = obsah_get('skupina', 'vybor');
if (!isset(VEDENI_SKUPINY[$skupinaNova])) $skupinaNova = 'vybor';

$f = [
    'skupina' => (string)($v['skupina'] ?? $skupinaNova), 'jmeno' => (string)($v['jmeno'] ?? ''), 'funkce' => (string)($v['funkce'] ?? ''),
    'telefon' => (string)($v['telefon'] ?? ''), 'email' => (string)($v['email'] ?? ''), 'zobrazit_kontakt' => (int)($v['zobrazit_kontakt'] ?? 1),
    'foto' => (string)($v['foto'] ?? ''), 'text' => (string)($v['text'] ?? ''), 'visible' => (int)($v['visible'] ?? 1),
];
if (!isset(VEDENI_SKUPINY[$f['skupina']])) $f['skupina'] = 'vybor';
$chyby = [];
$bylSoubor = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek('vedeni.php');
    $akce = vstup('action', 20);

    if ($akce === 'ulozit') {
        $bylSoubor = obsah_byl_soubor();
        $f = [
            'skupina' => vstup('skupina', 20), 'jmeno' => obsah_pole('jmeno', 120), 'funkce' => obsah_pole('funkce', 160),
            'telefon' => obsah_pole('telefon', 40), 'email' => mb_strtolower(obsah_pole('email', 160)),
            'zobrazit_kontakt' => vstup_bool('zobrazit_kontakt'), 'foto' => $f['foto'], 'text' => obsah_pole('text', 6000),
            'visible' => vstup_bool('visible'),
        ];
        if (!isset(VEDENI_SKUPINY[$f['skupina']])) { $chyby['skupina'] = 'Vyberte skupinu.'; $f['skupina'] = 'vybor'; }
        if ($f['jmeno'] === '') $chyby['jmeno'] = 'Vyplňte jméno.';
        if ($f['email'] !== '' && !je_email($f['email'])) $chyby['email'] = 'E-mail nevypadá správně (např. kancelar@cltk.cz).';
        if ($f['telefon'] !== '' && !preg_match('/^[+\d][\d \-\/()]{5,}$/', $f['telefon'])) $chyby['telefon'] = 'Telefon nevypadá jako telefonní číslo (např. +420 777 123 456).';
        if (!$chyby) {
            $foto = admin_obrazek('foto', $v['foto'] ?? '', 'vedeni', 1200, 1600, $f['jmeno']);
            if ($foto['chyba'] !== '') {
                $chyby['foto'] = $foto['chyba'];
            } else {
                $data = $f;
                $data['foto'] = $foto['soubor'];
                if ($v) {
                    if ($f['skupina'] !== $v['skupina']) $data['poradi'] = admin_dalsi_poradi('cltk_vedeni', 'skupina', $f['skupina']);
                    db_update('cltk_vedeni', $id, $data);
                } else {
                    $data['poradi'] = admin_dalsi_poradi('cltk_vedeni', 'skupina', $f['skupina']);
                    $id = db_insert('cltk_vedeni', $data);
                }
                obsah_smazat_nepouzite($foto['smazat'], [['cltk_vedeni', 'foto']]);
                redirect('vedeni.php#v' . $id, $v ? 'Změny u „' . $f['jmeno'] . '“ jsou uložené.' : '„' . $f['jmeno'] . '“ – přidáno do skupiny ' . VEDENI_SKUPINY[$f['skupina']][0] . '.');
            }
        }
        $rezim = $v ? 'uprava' : 'nova';
    } else {
        $rid = (int)vstup_int('id');
        $x = $rid ? row('SELECT * FROM cltk_vedeni WHERE id = ?', [$rid]) : null;
        if (!$x) redirect('vedeni.php', 'Osoba nebyla nalezena – možná byla mezitím smazána.', 'err');
        if ($akce === 'prepnout') {
            $novy = admin_prepni('cltk_vedeni', $rid);
            redirect('vedeni.php#v' . $rid, $novy ? $x['jmeno'] . ' je teď na webu vidět.' : $x['jmeno'] . ' se na webu nezobrazuje (zůstává uložený záznam).');
        }
        if ($akce === 'kontakt') {
            $novy = (int)$x['zobrazit_kontakt'] === 1 ? 0 : 1;
            q('UPDATE cltk_vedeni SET zobrazit_kontakt = ? WHERE id = ?', [$novy, $rid]);
            redirect('vedeni.php#v' . $rid, $novy ? 'Telefon a e-mail osoby ' . $x['jmeno'] . ' se na webu zobrazí.' : 'Telefon a e-mail osoby ' . $x['jmeno'] . ' se na webu nezobrazí.');
        }
        if ($akce === 'nahoru' || $akce === 'dolu') {
            admin_posun('cltk_vedeni', $rid, $akce === 'nahoru' ? -1 : 1, 'skupina');
            redirect('vedeni.php#v' . $rid, 'Pořadí bylo změněno.');
        }
        if ($akce === 'smazat') {
            q('DELETE FROM cltk_vedeni WHERE id = ?', [$rid]);
            obsah_smazat_nepouzite([(string)$x['foto']], [['cltk_vedeni', 'foto']]);
            redirect('vedeni.php', 'Záznam „' . $x['jmeno'] . '“ byl smazán.');
        }
        redirect('vedeni.php', 'Neznámý požadavek.', 'err');
    }
}

if ($rezim === 'seznam') {
    admin_head('Vedení klubu', $user, [
        'podnadpis' => 'Výkonný výbor, kancelář a další kontakty na stránce Vedení (a v kontaktech). Prezidenti klubu jsou ve <a href="historie.php?cast=deska&amp;kat=prezidenti">Zlaté desce</a>, úvodní text stránky ve <a href="stranky.php?stranka=vedeni">Stránkách</a>.',
        'akce' => '<a class="btn btn-primary" href="vedeni.php?nova=1">Přidat osobu</a>',
    ]);
} else {
    admin_head($v ? $v['jmeno'] : 'Nová osoba', $user, ['zpet' => ['vedeni.php', 'Vedení klubu'], 'sirka' => 'uzka']);
}
echo obsah_assets();

if ($rezim === 'seznam'):
    foreach (VEDENI_SKUPINY as $sk => [$skNazev, $skJedn]):
        $radky = rows('SELECT * FROM cltk_vedeni WHERE skupina = ? ORDER BY poradi, id', [$sk]);
        $posl = count($radky) - 1;
?>
<section class="panel">
  <div class="panel-head">
    <h2><?= e($skNazev) ?> <small><?= cislo(count($radky)) ?></small></h2>
    <div class="btn-row">
      <a class="btn btn-sm btn-ghost" href="vedeni.php?nova=1&amp;skupina=<?= e($sk) ?>">Přidat</a>
      <?php if ($sk === 'vybor'): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('vedeni.php')) ?>" target="_blank" rel="noopener">Zobrazit na webu <span aria-hidden="true">↗</span></a><?php endif; ?>
    </div>
  </div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím nikdo', '', '<a class="btn btn-ghost" href="vedeni.php?nova=1&amp;skupina=' . e($sk) . '">Přidat</a>') ?>
  <?php else: ?>
  <div class="tbl-wrap tbl-karty">
    <table>
      <thead><tr><th>Jméno a funkce</th><th>Kontakt</th><th>Stav</th><th class="right">Akce</th></tr></thead>
      <tbody>
      <?php foreach ($radky as $i => $r): $rid = (int)$r['id']; ?>
        <tr id="v<?= $rid ?>"<?= (int)$r['visible'] ? '' : ' class="je-skryte"' ?>>
          <td data-label="Jméno a funkce" class="td-nazev"><b><a href="vedeni.php?id=<?= $rid ?>"><?= e($r['jmeno']) ?></a></b><small><?= e($r['funkce']) ?></small></td>
          <td data-label="Kontakt" class="td-mala">
            <?php $k = array_filter([(string)$r['telefon'], (string)$r['email']], static fn($x) => $x !== ''); ?>
            <?= $k ? implode('<br>', array_map('e', $k)) : '–' ?>
            <?php if ($k && !(int)$r['zobrazit_kontakt']): ?><br><?= badge('na webu skrytý', 'warn') ?><?php endif; ?>
          </td>
          <td data-label="Stav"><?= stav_badge($r['visible']) ?></td>
          <td data-label="Akce" class="right"><div class="akce-radku">
            <?= tlacitka_poradi($rid, $i === 0, $i === $posl) ?>
            <a class="btn btn-sm btn-ghost" href="vedeni.php?id=<?= $rid ?>">Upravit</a>
            <?php if ($k): ?>
              <?= tlacitko_akce('kontakt', $rid, (int)$r['zobrazit_kontakt'] ? 'Skrýt kontakt' : 'Ukázat kontakt', 'btn-ghost', [], '',
                  (int)$r['zobrazit_kontakt'] ? 'Telefon a e-mail na webu nevypisovat' : 'Telefon a e-mail na webu vypsat') ?>
            <?php endif; ?>
            <?= tlacitko_prepnout($rid, $r['visible']) ?>
            <?= tlacitko_smazat($rid, 'Smazat „' . $r['jmeno'] . '“ ze skupiny ' . $skNazev . '? Nejde to vrátit.') ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php endforeach; ?>

<?php else: /* ---------- formulář ---------- */ ?>
<?= obsah_chyby_box($chyby, $bylSoubor) ?>
<section class="panel">
  <div class="panel-body">
    <form method="post" class="form" enctype="multipart/form-data" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <?= pole_radek([
            pole_text('jmeno', 'Jméno', $f['jmeno'], ['required' => true, 'maxlength' => 120, 'hint' => obsah_chyba($chyby, 'jmeno', 'I s tituly.')]),
            pole_select('skupina', 'Skupina', $f['skupina'], array_map(static fn($x) => $x[0], VEDENI_SKUPINY), ['hint' => obsah_chyba($chyby, 'skupina')]),
          ]) ?>
      <?= pole_text('funkce', 'Funkce', $f['funkce'], ['maxlength' => 160, 'placeholder' => 'místopředseda / generální manažer']) ?>
      <?= pole_radek([
            pole_text('telefon', 'Telefon', $f['telefon'], ['type' => 'tel', 'maxlength' => 40, 'hint' => obsah_chyba($chyby, 'telefon')]),
            pole_text('email', 'E-mail', $f['email'], ['type' => 'email', 'maxlength' => 160, 'hint' => obsah_chyba($chyby, 'email')]),
          ]) ?>
      <?= pole_check('zobrazit_kontakt', 'Telefon a e-mail zobrazit na webu', (bool)$f['zobrazit_kontakt'],
            ['hint' => 'Odškrtnuté = kontakt zůstane jen v administraci (např. když osoba nechce mít číslo na webu).']) ?>
      <?= pole_obrazek('foto', 'Fotka (nepovinná)', $f['foto'], ['nahled' => 'ctverec', 'hint' => obsah_chyba($chyby, 'foto', 'Portrét. JPG, PNG nebo WEBP.' . ($f['foto'] !== '' ? ' Nová fotka nahradí stávající.' : ''))]) ?>
      <?= pole_textarea('text', 'Text (nepovinný)', $f['text'], ['rows' => 3]) ?>
      <?= pole_check('visible', 'Zobrazit na webu', (bool)$f['visible']) ?>
      <?= tlacitka_formulare($v ? 'Uložit změny' : 'Přidat', 'vedeni.php', 'Zpět bez uložení') ?>
    </form>
  </div>
</section>
<?php if ($v): ?>
  <section class="panel panel--nebezpeci">
    <div class="panel-head"><h2>Smazat</h2></div>
    <div class="panel-body"><div class="btn-row">
      <?= tlacitko_smazat($id, 'Opravdu smazat „' . $v['jmeno'] . '“? Nejde to vrátit.', [], 'Smazat osobu') ?>
    </div></div>
  </section>
<?php endif; ?>
<?php endif; ?>

<?php admin_foot(); ?>
