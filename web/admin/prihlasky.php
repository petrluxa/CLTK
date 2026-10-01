<?php
/* Přihlášky (ZADANI §6.8):
   – k akcím z kalendáře (cltk_signups): vyřízeno ano/ne, poznámka kanceláře,
     filtr podle akce, export CSV; akce mohla být mezitím smazaná (LEFT JOIN),
   – do klubu z clenstvi.php (cltk_prihlasky_clenstvi): stav nová / vyřízená /
     zamítnutá, poznámka, celý formulář z JSON `data` v detailu. Rodné číslo
     se ukazuje JEN v detailu (a do CSV jen na výslovné přání).
   Počet nevyřízených ukazuje štítek v postranním panelu. */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const PR_STRANKA = 'prihlasky.php';
const PR_LIMIT = 300;
const PR_STAVY = ['nova' => ['Nová', 'zlato'], 'vyrizena' => ['Vyřízená', 'ok'], 'zamitnuta' => ['Zamítnutá', 'off']];

/** Popisky polí přihlášky do klubu (klíče JSON `data`). Neznámý klíč se zobrazí „lidsky“. */
const PR_POPISKY = [
    'jmeno' => 'Jméno', 'prijmeni' => 'Příjmení', 'titul' => 'Titul', 'pohlavi' => 'Pohlaví', 'datum_narozeni' => 'Datum narození',
    'narozeni' => 'Datum narození', 'rodne_cislo' => 'Rodné číslo', 'rc' => 'Rodné číslo', 'obcanstvi' => 'Státní občanství',
    'statni_obcanstvi' => 'Státní občanství', 'typ_clenstvi' => 'Typ členství', 'clenstvi' => 'Typ členství', 'cena' => 'Cena (Kč/rok)',
    'pocet_osob' => 'Počet osob', 'osoby' => 'Další osoby', 'dalsi_osoby' => 'Další osoby', 'adresa' => 'Adresa', 'ulice' => 'Ulice a číslo',
    'mesto' => 'Město', 'obec' => 'Obec', 'psc' => 'PSČ', 'stat' => 'Stát', 'telefon' => 'Telefon', 'email' => 'E-mail',
    'profese' => 'Současná profese', 'zdroj' => 'Jak se o nás dozvěděl/a', 'jak_jste_se_dozvedeli' => 'Jak se o nás dozvěděl/a',
    'dozvedeli' => 'Jak se o nás dozvěděl/a', 'souhlas_gdpr' => 'Souhlas se zpracováním osobních údajů',
    'souhlas_podminky' => 'Souhlas s podmínkami členství', 'schvaleni' => 'Bere na vědomí schvalovací proces',
    'souhlas_schvaleni' => 'Bere na vědomí schvalovací proces', 'zakonny_zastupce' => 'Zákonný zástupce', 'zastupce' => 'Zákonný zástupce',
    'firma' => 'Firma', 'nazev_firmy' => 'Firma', 'ico' => 'IČO', 'dic' => 'DIČ', 'poznamka' => 'Poznámka', 'vztah' => 'Vztah',
    'vek' => 'Věk', 'hraje' => 'Hrající', 'hrajici' => 'Hrající',
];

function pr_je_rc(string $klic): bool {
    return (bool)preg_match('/^(rc|rodne_?cislo)$|rodne/i', $klic);
}

function pr_popisek(string $klic): string {
    if (isset(PR_POPISKY[$klic])) return PR_POPISKY[$klic];
    $t = trim(str_replace(['_', '-'], ' ', $klic));
    return $t === '' ? $klic : mb_strtoupper(mb_substr($t, 0, 1)) . mb_substr($t, 1);
}

/** Hodnota pro zobrazení (bez escapování): ano/ne, datum po česku… */
function pr_hodnota(string $klic, $v): string {
    if (is_bool($v)) return $v ? 'ano' : 'ne';
    if ($v === null) return '';
    $v = (string)$v;
    if (pr_je_rc($klic) && citlive_je_sifra($v)) {
        // rodné číslo je v databázi zašifrované (SIFROVACI_KLIC v cltk-config.php)
        return citlive_desifruj($v) ?? '(zašifrované – na tomto serveru chybí šifrovací klíč)';
    }
    if (preg_match('/^souhlas|schvaleni/i', $klic) && ($v === '1' || $v === '0')) return $v === '1' ? 'ano' : 'ne';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return cz_date($v);
    return $v;
}

/** JSON přihlášky do klubu → HTML (definiční seznam, další osoby jako karty). */
function pr_data_html(array $data, int $hloubka = 0): string {
    if ($hloubka > 4) return '';
    $h = '<dl class="uv-dl">';
    foreach ($data as $k => $v) {
        $k = (string)$k;
        $h .= '<dt>' . e(pr_popisek($k)) . '</dt><dd>';
        if (is_array($v)) {
            if ($v === []) { $h .= '<span class="uv-tlumene">–</span>'; }
            elseif (array_is_list($v) && !array_filter($v, 'is_array')) { $h .= e(implode(', ', array_map(fn($x) => pr_hodnota($k, $x), $v))); }
            elseif (array_is_list($v)) {
                foreach ($v as $i => $osoba) {
                    $h .= '<div class="uv-osoba"><b>' . e(($k === 'osoby' || $k === 'dalsi_osoby' ? 'Osoba ' : pr_popisek($k) . ' ') . ($i + 1)) . '</b>'
                        . (is_array($osoba) ? pr_data_html($osoba, $hloubka + 1) : e(pr_hodnota($k, $osoba))) . '</div>';
                }
            } else { $h .= pr_data_html($v, $hloubka + 1); }
        } else {
            $t = pr_hodnota($k, $v);
            $h .= $t === '' ? '<span class="uv-tlumene">–</span>' : (pr_je_rc($k) ? '<span class="uv-citlive">' . e($t) . '</span>' : e($t));
        }
        $h .= '</dd>';
    }
    return $h . '</dl>';
}

/** JSON přihlášky do klubu → ploché [popisek => hodnota] pro CSV. */
function pr_data_ploche(array $data, bool $sRc, string $predpona = ''): array {
    $v = [];
    foreach ($data as $k => $x) {
        $k = (string)$k;
        if (!$sRc && pr_je_rc($k)) continue;
        $popis = $predpona . pr_popisek($k);
        if (is_array($x)) {
            if (array_is_list($x) && !array_filter($x, 'is_array')) { $v[$popis] = implode(', ', array_map(fn($y) => pr_hodnota($k, $y), $x)); }
            elseif (array_is_list($x)) {
                foreach ($x as $i => $o) {
                    $p2 = $predpona . (($k === 'osoby' || $k === 'dalsi_osoby') ? 'Osoba ' : pr_popisek($k) . ' ') . ($i + 1) . ' – ';
                    $v += is_array($o) ? pr_data_ploche($o, $sRc, $p2) : [rtrim($p2, ' –') => pr_hodnota($k, $o)];
                }
            } else { $v += pr_data_ploche($x, $sRc, $popis . ' – '); }
        } else {
            $v[$popis] = pr_hodnota($k, $x);
        }
    }
    return $v;
}

/** Odpovědi na vlastní pole přihlášky k akci → [[popisek, hodnota], …]. */
function pr_odpovedi(array $s): array {
    $v = [];
    foreach (json_pole((string)$s['odpovedi']) as $o) {
        if (!is_array($o)) continue;
        $v[] = [(string)($o['popisek'] ?? $o['klic'] ?? ''), (string)($o['hodnota'] ?? '')];
    }
    return $v;
}

/* ------------------------------------------------------------------ */

$druh = ($_GET['druh'] ?? 'akce') === 'clenstvi' ? 'clenstvi' : 'akce';
$detail = (int)($_GET['id'] ?? 0);
$hledat = mb_substr(trim(vstup_get('q')), 0, 80);

/* filtr přihlášek k akcím */
$fAkce = vstup_get('akce');
if ($fAkce !== 'smazane' && !preg_match('/^\d+$/', $fAkce)) $fAkce = '';
$fStav = vstup_get('stav');
$stavyAkce = ['nove' => 'Nevyřízené', 'vyrizene' => 'Vyřízené'];
$stavyClen = ['nova' => 'Nové', 'vyrizena' => 'Vyřízené', 'zamitnuta' => 'Zamítnuté'];
if ($druh === 'akce' && !isset($stavyAkce[$fStav])) $fStav = '';
if ($druh === 'clenstvi' && !isset($stavyClen[$fStav])) $fStav = '';

$qs = array_filter(['druh' => $druh, 'akce' => $druh === 'akce' ? $fAkce : '', 'stav' => $fStav, 'q' => $hledat], fn($x) => $x !== '');
$seznamUrl = PR_STRANKA . '?' . http_build_query($qs);

/** Návrat po POST jen na tuto stránku. */
function pr_zpet(): string {
    $z = vstup('zpet', 300);
    return preg_match('~^prihlasky\.php(\?[A-Za-z0-9_=&%\-.+]*)?$~', $z) ? $z : PR_STRANKA;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek(PR_STRANKA);
    $akce = vstup('action', 20);
    $id = (int)vstup_int('id');
    $zpet = pr_zpet();
    $typ = vstup('typ', 10) === 'clenstvi' ? 'clenstvi' : 'akce';

    if ($typ === 'akce') {
        $s = row('SELECT * FROM cltk_signups WHERE id = ?', [$id]);
        if (!$s) redirect($zpet, 'Přihláška nebyla nalezena.', 'err');
        $kdo = $s['jmeno'] !== '' ? $s['jmeno'] : 'bez jména';
        if ($akce === 'vyrizeno') {
            $novy = (int)$s['vyrizeno'] === 1 ? 0 : 1;
            q('UPDATE cltk_signups SET vyrizeno = ? WHERE id = ?', [$novy, $id]);
            redirect($zpet, 'Přihláška ' . $kdo . ($novy ? ' je vyřízená.' : ' je zase nevyřízená.'));
        }
        if ($akce === 'ulozit') {
            q('UPDATE cltk_signups SET vyrizeno = ?, poznamka_admin = ? WHERE id = ?', [vstup_bool('vyrizeno'), vstup('poznamka_admin', 4000), $id]);
            redirect($zpet, 'Přihláška ' . $kdo . ' je uložená.');
        }
        if ($akce === 'smazat') {
            q('DELETE FROM cltk_signups WHERE id = ?', [$id]);
            redirect(preg_replace('~([?&])id=\d+&?~', '$1', $zpet), 'Přihláška ' . $kdo . ' byla smazána.');
        }
    } else {
        $c = row('SELECT * FROM cltk_prihlasky_clenstvi WHERE id = ?', [$id]);
        if (!$c) redirect($zpet, 'Přihláška nebyla nalezena.', 'err');
        $kdo = trim($c['jmeno'] . ' ' . $c['prijmeni']) ?: 'bez jména';
        if ($akce === 'stav' || $akce === 'ulozit') {
            $stav = vstup('stav', 20);
            if (!isset(PR_STAVY[$stav])) redirect($zpet, 'Neznámý stav přihlášky.', 'err');
            $data = ['stav' => $stav, 'updated_at' => ted()];
            if ($akce === 'ulozit') $data['poznamka_admin'] = vstup('poznamka_admin', 4000);
            db_update('cltk_prihlasky_clenstvi', $id, $data);
            redirect($zpet, 'Přihláška ' . $kdo . ' je ' . mb_strtolower(PR_STAVY[$stav][0]) . '.');
        }
        if ($akce === 'smazat') {
            q('DELETE FROM cltk_prihlasky_clenstvi WHERE id = ?', [$id]);
            redirect(preg_replace('~([?&])id=\d+&?~', '$1', $zpet), 'Přihláška ' . $kdo . ' byla smazána.');
        }
    }
    redirect($zpet, 'Neznámý požadavek.', 'err');
}

/* ------------------------------------------------------------------
   Dotazy seznamů (stejné pro výpis i CSV)
   ------------------------------------------------------------------ */
function pr_dotaz_akce(string $fAkce, string $fStav, string $hledat, bool $vse = false): array {
    $kde = [];
    $p = [];
    if ($fAkce === 'smazane') $kde[] = 'a.id IS NULL';
    elseif ($fAkce !== '') { $kde[] = 's.akce_id = ?'; $p[] = (int)$fAkce; }
    if ($fStav === 'nove') $kde[] = 's.vyrizeno = 0';
    if ($fStav === 'vyrizene') $kde[] = 's.vyrizeno = 1';
    if ($hledat !== '') { $kde[] = '(s.jmeno LIKE ? OR s.email LIKE ? OR s.telefon LIKE ?)'; array_push($p, "%$hledat%", "%$hledat%", "%$hledat%"); }
    $sql = 'SELECT s.*, a.nazev AS akce_nazev, a.rok AS akce_rok, a.mesic AS akce_mesic, a.datum_od AS akce_od, a.datum_do AS akce_do,
                   a.termin_text AS akce_termin_text
              FROM cltk_signups s LEFT JOIN cltk_akce a ON a.id = s.akce_id'
         . ($kde ? ' WHERE ' . implode(' AND ', $kde) : '')
         . ' ORDER BY s.created_at DESC, s.id DESC' . ($vse ? '' : ' LIMIT ' . (PR_LIMIT + 1));
    return rows($sql, $p);
}

function pr_dotaz_clenstvi(string $fStav, string $hledat, bool $vse = false): array {
    $kde = [];
    $p = [];
    if ($fStav !== '') { $kde[] = 'stav = ?'; $p[] = $fStav; }
    if ($hledat !== '') { $kde[] = '(jmeno LIKE ? OR prijmeni LIKE ? OR email LIKE ? OR telefon LIKE ?)'; array_push($p, "%$hledat%", "%$hledat%", "%$hledat%", "%$hledat%"); }
    return rows('SELECT * FROM cltk_prihlasky_clenstvi' . ($kde ? ' WHERE ' . implode(' AND ', $kde) : '')
              . ' ORDER BY created_at DESC, id DESC' . ($vse ? '' : ' LIMIT ' . (PR_LIMIT + 1)), $p);
}

/* --- export CSV (před výstupem) --- */
if (($_GET['csv'] ?? '') === '1') {
    if ($druh === 'akce') {
        $prihl = array_reverse(pr_dotaz_akce($fAkce, $fStav, $hledat, true));
        $akceRow = preg_match('/^\d+$/', $fAkce) ? row('SELECT * FROM cltk_akce WHERE id = ?', [(int)$fAkce]) : null;
        if ($akceRow) {
            $sloupce = signup_sloupce($akceRow, $prihl);
            $hlavicka = array_merge(array_column($sloupce, 'popisek'), ['Vyřízeno', 'Poznámka kanceláře']);
            $radky = array_map(fn($p) => array_merge(array_map(fn($s) => signup_hodnota($p, $s['klic']), $sloupce),
                [(int)$p['vyrizeno'] === 1 ? 'ano' : 'ne', (string)$p['poznamka_admin']]), $prihl);
            csv_export('prihlasky-' . slugify($akceRow['nazev']) . '-' . date('Y-m-d') . '.csv', $hlavicka, $radky);
        }
        /* víc akcí najednou: vlastní pole se slučují podle popisku (klíče f1, f2… má každá akce svoje) */
        $vlastni = [];
        foreach ($prihl as $p) foreach (pr_odpovedi($p) as [$popis]) if ($popis !== '') $vlastni[$popis] = true;
        $vlastni = array_keys($vlastni);
        $hlavicka = array_merge(['Akce', 'Termín akce', 'Jméno', 'E-mail', 'Telefon', 'Počet osob', 'Poznámka'], $vlastni, ['Přihlášeno', 'Vyřízeno', 'Poznámka kanceláře']);
        $radky = [];
        foreach ($prihl as $p) {
            $odp = [];
            foreach (pr_odpovedi($p) as [$popis, $hodnota]) $odp[$popis] = $hodnota;
            $termin = $p['akce_nazev'] !== null ? akce_termin(['termin_text' => $p['akce_termin_text'], 'datum_od' => $p['akce_od'], 'datum_do' => $p['akce_do']], true) : '';
            $radky[] = array_merge(
                [$p['akce_nazev'] ?? 'akce smazána', $termin, $p['jmeno'], $p['email'], $p['telefon'], (string)$p['pocet'], $p['poznamka']],
                array_map(fn($k) => $odp[$k] ?? '', $vlastni),
                [signup_hodnota($p, 'created_at'), (int)$p['vyrizeno'] === 1 ? 'ano' : 'ne', (string)$p['poznamka_admin']]);
        }
        csv_export('prihlasky-k-akcim-' . date('Y-m-d') . '.csv', $hlavicka, $radky);
    }
    $sRc = ($_GET['rc'] ?? '') === '1';
    $prihl = array_reverse(pr_dotaz_clenstvi($fStav, $hledat, true));
    $zaklad = ['Přišla', 'Stav', 'Jméno', 'Příjmení', 'E-mail', 'Telefon', 'Typ členství', 'Cena (Kč/rok)', 'Počet osob'];
    $radkyData = [];
    $dalsi = [];
    foreach ($prihl as $c) {
        $d = pr_data_ploche(json_pole((string)$c['data']), $sRc);
        foreach (['Jméno', 'Příjmení', 'E-mail', 'Telefon', 'Typ členství', 'Počet osob', 'Cena (Kč/rok)'] as $dup) unset($d[$dup]);
        $radkyData[] = $d;
        foreach (array_keys($d) as $k) $dalsi[$k] = true;
    }
    $dalsi = array_keys($dalsi);
    $hlavicka = array_merge($zaklad, $dalsi, ['Souhlas GDPR', 'Souhlas s podmínkami', 'Poznámka kanceláře']);
    $radky = [];
    foreach ($prihl as $i => $c) {
        $radky[] = array_merge(
            [cz_datum_cas((string)$c['created_at']), PR_STAVY[$c['stav']][0] ?? $c['stav'], $c['jmeno'], $c['prijmeni'], $c['email'], $c['telefon'],
             $c['typ_clenstvi'], $c['cena'] === null ? '' : (string)$c['cena'], (string)$c['pocet_osob']],
            array_map(fn($k) => $radkyData[$i][$k] ?? '', $dalsi),
            [(int)$c['souhlas_gdpr'] === 1 ? 'ano' : 'ne', (int)$c['souhlas_podminky'] === 1 ? 'ano' : 'ne', (string)$c['poznamka_admin']]);
    }
    csv_export('prihlasky-do-klubu-' . date('Y-m-d') . ($sRc ? '-vcetne-rc' : '') . '.csv', $hlavicka, $radky);
}

$css = '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';
$noveAkce = (int)val('SELECT COUNT(*) FROM cltk_signups WHERE vyrizeno = 0');
$noveClen = (int)val("SELECT COUNT(*) FROM cltk_prihlasky_clenstvi WHERE stav = 'nova'");
$zalozky = admin_zalozky([
    PR_STRANKA . '?druh=akce'     => 'K akcím' . ($noveAkce ? ' (' . $noveAkce . ')' : ''),
    PR_STRANKA . '?druh=clenstvi' => 'Do klubu' . ($noveClen ? ' (' . $noveClen . ')' : ''),
], PR_STRANKA . '?druh=' . $druh);

/* ==================================================================
   DETAIL – přihláška k akci
   ================================================================== */
if ($detail > 0 && $druh === 'akce'):
    $s = row('SELECT s.*, a.nazev AS akce_nazev, a.id AS akce_existuje FROM cltk_signups s LEFT JOIN cltk_akce a ON a.id = s.akce_id WHERE s.id = ?', [$detail]);
    if (!$s) redirect($seznamUrl, 'Přihláška nebyla nalezena.', 'err');
    $akceRow = $s['akce_existuje'] !== null ? row('SELECT * FROM cltk_akce WHERE id = ?', [(int)$s['akce_id']]) : null;
    $zpetDetail = PR_STRANKA . '?' . http_build_query(['druh' => 'akce', 'id' => $detail] + array_diff_key($qs, ['druh' => 1]));
    admin_head('Přihláška k akci', $user, [
        'zpet' => [$seznamUrl, 'Přihlášky k akcím'], 'sirka' => 'uzka',
        'podnadpis' => e($s['jmeno']) . ' · ' . ($akceRow ? e($akceRow['nazev']) : 'akce byla smazána') . ' · ' . e(cz_datum_cas((string)$s['created_at'])),
    ]);
    echo $css;
?>
<section class="panel">
  <div class="panel-head"><h2>Údaje z formuláře</h2><?= (int)$s['vyrizeno'] === 1 ? badge('Vyřízeno', 'ok') : badge('Nová', 'zlato') ?></div>
  <div class="panel-body">
    <dl class="uv-dl">
      <dt>Akce</dt><dd><?= $akceRow ? '<a href="akce-edit.php?id=' . (int)$akceRow['id'] . '#prihlaseni">' . e($akceRow['nazev']) . '</a> · ' . e(akce_termin($akceRow, true)) : badge('Akce smazána', 'off') ?></dd>
      <dt>Jméno</dt><dd><?= e($s['jmeno']) ?></dd>
      <dt>E-mail</dt><dd><?= $s['email'] !== '' ? '<a href="mailto:' . e($s['email']) . '">' . e($s['email']) . '</a>' : '–' ?></dd>
      <dt>Telefon</dt><dd><?= $s['telefon'] !== '' ? '<a href="' . e(tel_href($s['telefon'])) . '">' . e($s['telefon']) . '</a>' : '–' ?></dd>
      <dt>Počet osob</dt><dd><?= (int)$s['pocet'] ?></dd>
      <dt>Poznámka</dt><dd><?= trim((string)$s['poznamka']) !== '' ? e($s['poznamka']) : '–' ?></dd>
      <?php foreach (pr_odpovedi($s) as [$popis, $hodnota]): ?>
        <dt><?= e($popis) ?></dt><dd><?= $hodnota !== '' ? e($hodnota) : '–' ?></dd>
      <?php endforeach; ?>
      <dt>Souhlas s údaji</dt><dd><?= (int)$s['souhlas'] === 1 ? 'ano' : 'ne' ?></dd>
      <dt>Přišla</dt><dd><?= e(cz_datum_cas((string)$s['created_at'])) ?></dd>
    </dl>
  </div>
</section>

<section class="panel">
  <div class="panel-head"><h2>Vyřízení</h2></div>
  <div class="panel-body">
    <form method="post" class="form" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="typ" value="akce">
      <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
      <input type="hidden" name="zpet" value="<?= e($zpetDetail) ?>">
      <?= pole_textarea('poznamka_admin', 'Poznámka kanceláře', $s['poznamka_admin'], ['rows' => 3, 'maxlength' => 4000,
            'hint' => 'Jen pro administraci, třeba „zaplaceno“, „volal, přijde o 30 min později“.']) ?>
      <?= pole_check('vyrizeno', 'Vyřízeno', (int)$s['vyrizeno'] === 1) ?>
      <?= tlacitka_formulare('Uložit', $seznamUrl, 'Zpět na seznam') ?>
    </form>
  </div>
</section>

<div class="btn-row">
  <?= tlacitko_akce('smazat', (int)$s['id'], 'Smazat přihlášku', 'btn-danger', ['typ' => 'akce', 'zpet' => $seznamUrl],
        'Smazat přihlášku ' . ($s['jmeno'] !== '' ? $s['jmeno'] : 'bez jména') . '? Nejde to vrátit.') ?>
</div>
<?php
    admin_foot();
    exit;
endif;

/* ==================================================================
   DETAIL – přihláška do klubu
   ================================================================== */
if ($detail > 0 && $druh === 'clenstvi'):
    $c = row('SELECT * FROM cltk_prihlasky_clenstvi WHERE id = ?', [$detail]);
    if (!$c) redirect($seznamUrl, 'Přihláška nebyla nalezena.', 'err');
    $data = json_pole((string)$c['data']);
    $kdo = trim($c['jmeno'] . ' ' . $c['prijmeni']) ?: 'bez jména';
    $zpetDetail = PR_STRANKA . '?' . http_build_query(['druh' => 'clenstvi', 'id' => $detail] + array_diff_key($qs, ['druh' => 1]));
    [$stavText, $stavDruh] = PR_STAVY[$c['stav']] ?? [$c['stav'], 'off'];
    admin_head('Přihláška do klubu', $user, [
        'zpet' => [$seznamUrl, 'Přihlášky do klubu'], 'sirka' => 'uzka',
        'podnadpis' => e($kdo) . ' · ' . e($c['typ_clenstvi'] !== '' ? $c['typ_clenstvi'] : 'typ členství neuveden') . ' · ' . e(cz_datum_cas((string)$c['created_at'])),
    ]);
    echo $css;
?>
<section class="panel">
  <div class="panel-head"><h2>Žadatel</h2><?= badge($stavText, $stavDruh) ?></div>
  <div class="panel-body">
    <dl class="uv-dl">
      <dt>Jméno a příjmení</dt><dd><?= e($kdo) ?></dd>
      <dt>E-mail</dt><dd><?= $c['email'] !== '' ? '<a href="mailto:' . e($c['email']) . '">' . e($c['email']) . '</a>' : '–' ?></dd>
      <dt>Telefon</dt><dd><?= $c['telefon'] !== '' ? '<a href="' . e(tel_href($c['telefon'])) . '">' . e($c['telefon']) . '</a>' : '–' ?></dd>
      <dt>Typ členství</dt><dd><?= $c['typ_clenstvi'] !== '' ? e($c['typ_clenstvi']) : '–' ?></dd>
      <dt>Roční cena</dt><dd><?= $c['cena'] !== null ? cena_kc((int)$c['cena']) : '–' ?></dd>
      <dt>Počet osob</dt><dd><?= (int)$c['pocet_osob'] ?></dd>
      <dt>Souhlasy</dt><dd>zpracování údajů: <?= (int)$c['souhlas_gdpr'] === 1 ? 'ano' : 'ne' ?> · podmínky členství: <?= (int)$c['souhlas_podminky'] === 1 ? 'ano' : 'ne' ?></dd>
      <dt>Přišla</dt><dd><?= e(cz_datum_cas((string)$c['created_at'])) ?></dd>
    </dl>
  </div>
</section>

<section class="panel">
  <div class="panel-head"><h2>Celý formulář</h2><span class="hint">Všechno, co žadatel vyplnil. Rodné číslo je zvýrazněné – jen pro kancelář.</span></div>
  <div class="panel-body">
    <?= $data ? pr_data_html($data) : '<p class="hint">Formulář neobsahuje žádné další údaje.</p>' ?>
  </div>
</section>

<section class="panel">
  <div class="panel-head"><h2>Vyřízení</h2></div>
  <div class="panel-body">
    <form method="post" class="form" data-hlidat-zmeny>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="ulozit">
      <input type="hidden" name="typ" value="clenstvi">
      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
      <input type="hidden" name="zpet" value="<?= e($zpetDetail) ?>">
      <?= pole_select('stav', 'Stav přihlášky', $c['stav'], array_map(fn($x) => $x[0], PR_STAVY)) ?>
      <?= pole_textarea('poznamka_admin', 'Poznámka kanceláře', $c['poznamka_admin'], ['rows' => 4, 'maxlength' => 4000,
            'hint' => 'Jen pro administraci – třeba „schváleno výborem 12. 10.“, „zaslána faktura“.']) ?>
      <?= tlacitka_formulare('Uložit', $seznamUrl, 'Zpět na seznam') ?>
    </form>
  </div>
</section>

<div class="btn-row">
  <?= tlacitko_akce('smazat', (int)$c['id'], 'Smazat přihlášku', 'btn-danger', ['typ' => 'clenstvi', 'zpet' => $seznamUrl],
        'Smazat přihlášku ' . $kdo . ' včetně všech osobních údajů? Nejde to vrátit.') ?>
</div>
<?php
    admin_foot();
    exit;
endif;

/* ==================================================================
   SEZNAM – přihlášky k akcím
   ================================================================== */
if ($druh === 'akce'):
    $radky = pr_dotaz_akce($fAkce, $fStav, $hledat);
    $vic = count($radky) > PR_LIMIT;
    $radky = array_slice($radky, 0, PR_LIMIT);
    $akceVyber = rows('SELECT a.id, a.nazev, a.rok, COUNT(s.id) AS n FROM cltk_akce a LEFT JOIN cltk_signups s ON s.akce_id = a.id
                        GROUP BY a.id, a.nazev, a.rok ORDER BY a.rok DESC, a.nazev');
    $sirotci = (int)val('SELECT COUNT(*) FROM cltk_signups s LEFT JOIN cltk_akce a ON a.id = s.akce_id WHERE a.id IS NULL');
    $moznostiAkci = ['' => 'Všechny akce'];
    foreach ($akceVyber as $av) {
        if ((int)$av['n'] === 0 && (string)$av['id'] !== $fAkce) continue;      // jen akce s přihláškami
        $moznostiAkci[(string)$av['id']] = $av['rok'] . ' · ' . $av['nazev'] . ' (' . (int)$av['n'] . ')';
    }
    if ($sirotci > 0 || $fAkce === 'smazane') $moznostiAkci['smazane'] = 'Smazané akce (' . $sirotci . ')';
    $osob = array_sum(array_map(fn($r) => (int)$r['pocet'], $radky));
    $csvUrl = PR_STRANKA . '?' . http_build_query($qs + ['csv' => '1']);

    admin_head('Přihlášky', $user, [
        'podnadpis' => 'Přihlášky k akcím z kalendáře a přihlášky do klubu ze stránky Členství. Vyřízené označte – nevyřízené ukazuje štítek v menu.',
        'akce' => $radky ? '<a class="btn btn-primary" href="' . e($csvUrl) . '">Stáhnout CSV</a>' : '',
    ]);
    echo $css;
    echo $zalozky;
?>
<section class="panel">
  <div class="panel-body">
    <form method="get" class="uv-filtr" action="<?= PR_STRANKA ?>">
      <input type="hidden" name="druh" value="akce">
      <?= pole_select('akce', 'Akce', $fAkce, $moznostiAkci, ['id' => 'f-filtr-akce']) ?>
      <?= pole_select('stav', 'Stav', $fStav, ['' => 'Všechny'] + $stavyAkce, ['id' => 'f-filtr-stav']) ?>
      <?= pole_text('q', 'Hledat', $hledat, ['type' => 'search', 'maxlength' => 80, 'placeholder' => 'jméno, e-mail, telefon', 'id' => 'f-filtr-q']) ?>
      <div class="btn-row"><button type="submit" class="btn btn-primary">Zobrazit</button><?php if (count($qs) > 1): ?><a class="btn btn-ghost" href="<?= PR_STRANKA ?>?druh=akce">Zrušit filtr</a><?php endif; ?></div>
    </form>
  </div>
</section>

<section class="panel">
  <div class="panel-head">
    <h2>Přihlášky k akcím <small><?= count($radky) ?><?= $vic ? '+' : '' ?> · <?= $osob ?> os.</small></h2>
    <?php if ($fAkce !== '' && $fAkce !== 'smazane'): ?><a class="btn btn-sm btn-ghost" href="akce-edit.php?id=<?= (int)$fAkce ?>#prihlaseni">Akce v kalendáři</a><?php endif; ?>
  </div>
  <?php if (!$radky): ?>
    <?= count($qs) > 1
          ? prazdny_stav('Filtru neodpovídá žádná přihláška', 'Zkuste jinou akci nebo stav, případně filtr zrušte.')
          : prazdny_stav('Zatím žádná přihláška k akci', 'Přihlášky se objeví, jakmile je někdo odešle z kalendáře na webu. U akce musí být zapnuté „Zobrazit tlačítko Přihlásit se“.') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Přišla</th><th>Akce</th><th>Jméno a kontakt</th><th class="right">Osob</th><th>Odpovědi a poznámka</th><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($radky as $s): $sid = (int)$s['id']; $odp = pr_odpovedi($s); ?>
          <tr<?= (int)$s['vyrizeno'] === 1 ? ' class="je-skryte"' : '' ?>>
            <td data-label="Přišla" class="uv-nowrap uv-tlumene"><?= e(cz_datum_cas((string)$s['created_at'])) ?></td>
            <td data-label="Akce"><?= $s['akce_nazev'] !== null
                ? '<a href="' . e(PR_STRANKA . '?' . http_build_query(['druh' => 'akce', 'akce' => (int)$s['akce_id']])) . '">' . e($s['akce_nazev']) . '</a>'
                : badge('Akce smazána', 'off') ?></td>
            <td data-label="Jméno a kontakt" class="td-nazev"><b><?= e($s['jmeno'] !== '' ? $s['jmeno'] : 'bez jména') ?></b>
              <small><?php if ($s['email'] !== ''): ?><a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a><?php endif; ?><?= $s['telefon'] !== '' ? ' · ' . e($s['telefon']) : '' ?></small></td>
            <td data-label="Osob" class="right"><?= (int)$s['pocet'] ?></td>
            <td data-label="Odpovědi a poznámka">
              <?php if ($odp): ?><ul class="uv-odpovedi"><?php foreach ($odp as [$popis, $hodnota]): ?><li><?= e($popis) ?>: <b><?= $hodnota !== '' ? e($hodnota) : '–' ?></b></li><?php endforeach; ?></ul><?php endif; ?>
              <?php if (trim((string)$s['poznamka']) !== ''): ?><small class="uv-tlumene"><?= e(uryvek($s['poznamka'], 120)) ?></small><?php endif; ?>
              <?php if (!$odp && trim((string)$s['poznamka']) === ''): ?><span class="uv-tlumene">–</span><?php endif; ?>
            </td>
            <td data-label="Stav"><?= (int)$s['vyrizeno'] === 1 ? badge('Vyřízeno', 'ok') : badge('Nová', 'zlato') ?>
              <?php if (trim((string)$s['poznamka_admin']) !== ''): ?><br><small class="uv-tlumene"><?= e(uryvek($s['poznamka_admin'], 60)) ?></small><?php endif; ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitko_akce('vyrizeno', $sid, (int)$s['vyrizeno'] === 1 ? 'Nevyřízeno' : 'Vyřízeno', 'btn-ghost', ['typ' => 'akce', 'zpet' => $seznamUrl]) ?>
              <a class="btn btn-sm btn-ghost" href="<?= e(PR_STRANKA . '?' . http_build_query(['druh' => 'akce', 'id' => $sid] + array_diff_key($qs, ['druh' => 1]))) ?>">Detail</a>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($vic): ?><div class="panel-body"><p class="hint">Zobrazeno prvních <?= PR_LIMIT ?> přihlášek. Zužte filtr, nebo stáhněte všechny do CSV.</p></div><?php endif; ?>
  <?php endif; ?>
</section>
<?php
    admin_foot();
    exit;
endif;

/* ==================================================================
   SEZNAM – přihlášky do klubu
   ================================================================== */
$radky = pr_dotaz_clenstvi($fStav, $hledat);
$vic = count($radky) > PR_LIMIT;
$radky = array_slice($radky, 0, PR_LIMIT);
$csvUrl = PR_STRANKA . '?' . http_build_query($qs + ['csv' => '1']);

admin_head('Přihlášky', $user, [
    'podnadpis' => 'Přihlášky k akcím z kalendáře a přihlášky do klubu ze stránky Členství. Vyřízené označte – nevyřízené ukazuje štítek v menu.',
    'akce' => $radky ? '<a class="btn btn-primary" href="' . e($csvUrl) . '">Stáhnout CSV</a>' : '',
]);
echo $css;
echo $zalozky;
?>
<section class="panel">
  <div class="panel-body">
    <form method="get" class="uv-filtr" action="<?= PR_STRANKA ?>">
      <input type="hidden" name="druh" value="clenstvi">
      <?= pole_select('stav', 'Stav', $fStav, ['' => 'Všechny'] + $stavyClen, ['id' => 'f-filtr-stav']) ?>
      <?= pole_text('q', 'Hledat', $hledat, ['type' => 'search', 'maxlength' => 80, 'placeholder' => 'jméno, příjmení, e-mail', 'id' => 'f-filtr-q']) ?>
      <div class="btn-row"><button type="submit" class="btn btn-primary">Zobrazit</button><?php if (count($qs) > 1): ?><a class="btn btn-ghost" href="<?= PR_STRANKA ?>?druh=clenstvi">Zrušit filtr</a><?php endif; ?></div>
    </form>
  </div>
</section>

<section class="panel">
  <div class="panel-head">
    <h2>Přihlášky do klubu <small><?= count($radky) ?><?= $vic ? '+' : '' ?></small></h2>
    <?php if ($radky): ?><a class="btn btn-sm btn-ghost" href="<?= e(PR_STRANKA . '?' . http_build_query($qs + ['csv' => '1', 'rc' => '1'])) ?>" title="CSV obsahuje i rodná čísla – ukládejte ho bezpečně">CSV včetně rodných čísel</a><?php endif; ?>
  </div>
  <?php if (!$radky): ?>
    <?= count($qs) > 1
          ? prazdny_stav('Filtru neodpovídá žádná přihláška', 'Zkuste jiný stav, případně filtr zrušte.')
          : prazdny_stav('Zatím žádná přihláška do klubu', 'Přihlášky ze stránky Členství se objeví tady. Celý formulář uvidíte v detailu.') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>Přišla</th><th>Žadatel</th><th>Typ členství</th><th class="right">Osob</th><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($radky as $c):
            $cid = (int)$c['id'];
            [$stavText, $stavDruh] = PR_STAVY[$c['stav']] ?? [$c['stav'], 'off']; ?>
          <tr<?= $c['stav'] !== 'nova' ? ' class="je-skryte"' : '' ?>>
            <td data-label="Přišla" class="uv-nowrap uv-tlumene"><?= e(cz_datum_cas((string)$c['created_at'])) ?></td>
            <td data-label="Žadatel" class="td-nazev"><b><?= e(trim($c['jmeno'] . ' ' . $c['prijmeni']) ?: 'bez jména') ?></b>
              <small><?php if ($c['email'] !== ''): ?><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a><?php endif; ?><?= $c['telefon'] !== '' ? ' · ' . e($c['telefon']) : '' ?></small></td>
            <td data-label="Typ členství"><?= $c['typ_clenstvi'] !== '' ? e($c['typ_clenstvi']) : '<span class="uv-tlumene">–</span>' ?>
              <?php if ($c['cena'] !== null): ?><br><small class="uv-tlumene"><?= cena_kc((int)$c['cena']) ?> / rok</small><?php endif; ?></td>
            <td data-label="Osob" class="right"><?= (int)$c['pocet_osob'] ?></td>
            <td data-label="Stav"><?= badge($stavText, $stavDruh) ?>
              <?php if (trim((string)$c['poznamka_admin']) !== ''): ?><br><small class="uv-tlumene"><?= e(uryvek($c['poznamka_admin'], 60)) ?></small><?php endif; ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?php if ($c['stav'] === 'nova'): ?>
                <?= tlacitko_akce('stav', $cid, 'Vyřízeno', 'btn-ghost', ['typ' => 'clenstvi', 'stav' => 'vyrizena', 'zpet' => $seznamUrl]) ?>
              <?php else: ?>
                <?= tlacitko_akce('stav', $cid, 'Zpět na novou', 'btn-ghost', ['typ' => 'clenstvi', 'stav' => 'nova', 'zpet' => $seznamUrl]) ?>
              <?php endif; ?>
              <a class="btn btn-sm btn-ghost" href="<?= e(PR_STRANKA . '?' . http_build_query(['druh' => 'clenstvi', 'id' => $cid] + array_diff_key($qs, ['druh' => 1]))) ?>">Detail</a>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($vic): ?><div class="panel-body"><p class="hint">Zobrazeno prvních <?= PR_LIMIT ?> přihlášek. Zužte filtr, nebo stáhněte všechny do CSV.</p></div><?php endif; ?>
  <?php endif; ?>
</section>

<?php admin_foot(); ?>
