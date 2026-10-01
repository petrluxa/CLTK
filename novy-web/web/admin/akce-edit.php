<?php
/* Kalendář akcí – úprava jedné akce (ZADANI §4.6, §0.3).
   - termín: rok a měsíc vždy (i bez data), datum od–do volitelně, vlastní text termínu,
   - texty: krátký popis do okna kalendáře, delší popis z editoru (akce.php?id=…), fotka, odkaz,
   - přihláška: zaškrtávátko „Zobrazit tlačítko Přihlásit se“ a formulář nastavitelný
     u každé akce zvlášť (telefon, počet osob, poznámka + vlastní pole) → JSON v cltk_akce.formular
     přesně ve tvaru ze SCHEMA.md. Klíč vlastního pole (f1, f2…) se přidělí jednou a nemění se –
     odpovědi v cltk_signups se na něj váží,
   - seznam přihlášených se sloupci podle polí formuláře + export CSV (?id=…&csv=1). */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const AE_SEZNAM = 'akce.php';
const AE_MAX_VLASTNICH = 20;

/** Odkaz z formuláře: [hodnota pro DB, chyba]. */
function aed_odkaz(string $vstup): array {
    $u = trim($vstup);
    if ($u === '') return ['', ''];
    $holy = (string)preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($u, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $holy) && !preg_match('~^(https?:|mailto:|tel:)~i', $holy)) {
        return [$u, 'Odkaz musí vést na web (https://…), e-mail (mailto:…) nebo na stránku tohoto webu (např. prague-open.php).'];
    }
    $n = normalizuj_url($u);
    if (preg_match('~^https?://~i', $n)) {
        $host = (string)parse_url($n, PHP_URL_HOST);
        if (preg_match('~\s~u', $n) || !preg_match('/^[\p{L}\p{N}.\-]+\.\p{L}{2,}$/u', $host)) {
            return [$u, 'Odkaz nevypadá jako adresa webu. Zapište ho celý (https://…) nebo jako stránku webu (letni-kempy.php).'];
        }
    }
    if (bezpecny_odkaz($n) === '') return [$u, 'Tento odkaz nejde použít.'];
    return [$n, ''];
}

/** Klíče vlastních polí, které už akce kdy použila (v nastavení i v odpovědích) – nikdy se nepřidělí znovu. */
function aed_pouzite_klice(array $a): array {
    $k = [];
    foreach ((array)(json_pole((string)($a['formular'] ?? ''))['vlastni'] ?? []) as $p) {
        if (is_array($p) && isset($p['klic'])) $k[] = (string)$p['klic'];
    }
    if (!empty($a['id'])) {
        foreach (rows('SELECT odpovedi FROM cltk_signups WHERE akce_id = ?', [(int)$a['id']]) as $s) {
            foreach (json_pole((string)$s['odpovedi']) as $o) if (isset($o['klic'])) $k[] = (string)$o['klic'];
        }
    }
    return array_values(array_unique($k));
}

/** Další volný klíč fN. */
function aed_novy_klic(array &$pouzite): string {
    $max = 0;
    foreach ($pouzite as $k) if (preg_match('/^f(\d+)$/', $k, $m)) $max = max($max, (int)$m[1]);
    $k = 'f' . ($max + 1);
    $pouzite[] = $k;
    return $k;
}

/* ------------------------------------------------------------------ */

$id = (int)($_GET['id'] ?? 0);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') $id = (int)vstup_int('id');
$stara = $id ? row('SELECT * FROM cltk_akce WHERE id = ?', [$id]) : null;
if ($id && !$stara) redirect(AE_SEZNAM, 'Akce nebyla nalezena – možná ji mezitím někdo smazal.', 'err');
$tady = 'akce-edit.php' . ($id ? '?id=' . $id : '');

/* --- export CSV přihlášených (před jakýmkoli výstupem) --- */
if ($stara && ($_GET['csv'] ?? '') === '1') {
    $prihl = rows('SELECT * FROM cltk_signups WHERE akce_id = ? ORDER BY created_at, id', [$id]);
    $sloupce = signup_sloupce($stara, $prihl);
    $hlavicka = array_merge(array_column($sloupce, 'popisek'), ['Vyřízeno', 'Poznámka kanceláře']);
    $radky = array_map(fn($p) => array_merge(
        array_map(fn($s) => signup_hodnota($p, $s['klic']), $sloupce),
        [(int)$p['vyrizeno'] === 1 ? 'ano' : 'ne', (string)$p['poznamka_admin']]
    ), $prihl);
    csv_export('prihlasky-' . slugify($stara['nazev']) . '-' . date('Y-m-d') . '.csv', $hlavicka, $radky);
}

$rokVychozi = preg_match('/^\d{4}$/', vstup_get('rok')) ? (int)vstup_get('rok') : (int)substr(dnes(), 0, 4);
$a = $stara ?? [
    'id' => 0, 'nazev' => '', 'rok' => $rokVychozi, 'mesic' => '', 'datum_od' => '', 'datum_do' => '', 'termin_text' => '',
    'cas' => '', 'misto' => '', 'stitek' => '', 'perex' => '', 'popis' => '', 'foto' => '', 'odkaz' => '', 'odkaz_text' => '',
    'prihlaseni_povoleno' => 0, 'formular' => '', 'prihlaseni_do' => '', 'kapacita' => null, 'poznamka_interni' => '', 'visible' => 1,
];
$cfg = akce_formular($a);                  // normalizované nastavení formuláře pro zobrazení
$chyby = [];
$varovani = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek($tady);
    $akce = vstup('action', 20);

    /* --- přihlášený: vyřízeno ano/ne (vlastní formulář u řádku) --- */
    if ($akce === 'vyrizeno' && $stara) {
        $sid = (int)vstup_int('prihlaska');
        $s = row('SELECT * FROM cltk_signups WHERE id = ? AND akce_id = ?', [$sid, $id]);
        if (!$s) redirect($tady . '#prihlaseni', 'Přihláška nebyla nalezena.', 'err');
        $novy = (int)$s['vyrizeno'] === 1 ? 0 : 1;
        q('UPDATE cltk_signups SET vyrizeno = ? WHERE id = ?', [$novy, $sid]);
        redirect($tady . '#prihlaseni', 'Přihláška ' . ($s['jmeno'] !== '' ? $s['jmeno'] : 'bez jména') . ($novy ? ' je vyřízená.' : ' je zase nevyřízená.'));
    }
    if ($akce === 'vyrizeno_vse' && $stara) {
        q('UPDATE cltk_signups SET vyrizeno = 1 WHERE akce_id = ? AND vyrizeno = 0', [$id]);
        redirect($tady . '#prihlaseni', 'Všechny přihlášky k akci jsou označené jako vyřízené.');
    }
    if ($akce === 'smazat_prihlasky' && $stara) {
        $n = (int)val('SELECT COUNT(*) FROM cltk_signups WHERE akce_id = ?', [$id]);
        q('DELETE FROM cltk_signups WHERE akce_id = ?', [$id]);
        redirect($tady . '#prihlaseni', $n > 0 ? 'Smazáno ' . $n . ' ' . sklonuj($n, 'přihláška', 'přihlášky', 'přihlášek') . '.' : 'Akce neměla žádné přihlášky.');
    }

    if ($akce !== 'ulozit') redirect(AE_SEZNAM, 'Neznámý požadavek.', 'err');

    /* --- termín --- */
    $a['nazev'] = vstup('nazev', 200);
    $a['stitek'] = vstup('stitek', 80);
    $rokRaw = vstup('rok', 4);
    $mesicRaw = vstup('mesic', 2);
    $odRaw = vstup('datum_od', 20);
    $doRaw = vstup('datum_do', 20);
    $od = normalizuj_datum($odRaw);
    $do = normalizuj_datum($doRaw);
    $a['termin_text'] = vstup('termin_text', 120);
    $a['cas'] = vstup('cas', 60);
    $a['misto'] = vstup('misto', 160);
    if ($rokRaw === '' && is_string($od)) $rokRaw = substr($od, 0, 4);
    if ($mesicRaw === '' && is_string($od)) $mesicRaw = (string)(int)substr($od, 5, 2);
    $a['rok'] = $rokRaw;
    $a['mesic'] = $mesicRaw;
    $a['datum_od'] = $od === false ? $odRaw : (string)$od;
    $a['datum_do'] = $do === false ? $doRaw : (string)$do;

    if ($a['nazev'] === '') $chyby[] = 'Vyplňte název akce.';
    if (!preg_match('/^\d{4}$/', $rokRaw) || (int)$rokRaw < 2000 || (int)$rokRaw > 2100) $chyby[] = 'Vyplňte rok, do kterého akce v kalendáři patří (např. 2026).';
    if (!preg_match('/^\d{1,2}$/', $mesicRaw) || (int)$mesicRaw < 1 || (int)$mesicRaw > 12) $chyby[] = 'Vyberte měsíc – akce bez data se v kalendáři ukáže jen s měsícem.';
    if ($od === false) $chyby[] = 'Datum „od“ nerozumím. Zapište ho jako 24. 5. 2026.';
    if ($do === false) $chyby[] = 'Datum „do“ nerozumím. Zapište ho jako 28. 8. 2026.';
    if ($do && !$od) $chyby[] = 'Vyplňte i datum „od“ – samotné datum „do“ nedává smysl.';
    if ($od && $do && $do < $od) $chyby[] = 'Datum „do“ nemůže být dřív než datum „od“.';

    /* --- texty --- */
    $a['perex'] = vstup('perex', 1500);
    $a['popis'] = html_k_ulozeni(vstup('popis', 0));   // bez BASE_PATH – přežije přestěhování webu
    if (strlen($a['popis']) > 60000) $chyby[] = 'Delší popis je příliš dlouhý.';
    [$a['odkaz'], $chOdkaz] = aed_odkaz(vstup('odkaz', 255));
    if ($chOdkaz !== '') $chyby[] = $chOdkaz;
    $a['odkaz_text'] = vstup('odkaz_text', 80);
    $a['poznamka_interni'] = vstup('poznamka_interni', 2000);
    $a['visible'] = vstup_bool('visible');

    /* --- přihláška --- */
    $a['prihlaseni_povoleno'] = vstup_bool('prihlaseni_povoleno');
    $prDoRaw = vstup('prihlaseni_do', 20);
    $prDo = normalizuj_datum($prDoRaw);
    $a['prihlaseni_do'] = $prDo === false ? $prDoRaw : (string)$prDo;
    if ($prDo === false) $chyby[] = 'Uzávěrku přihlášek nerozumím. Zapište ji jako 20. 5. 2026.';
    $kapRaw = vstup('kapacita', 6);
    $a['kapacita'] = $kapRaw;
    if ($kapRaw !== '' && (!preg_match('/^\d+$/', $kapRaw) || (int)$kapRaw < 1)) $chyby[] = 'Kapacitu zapište jako celé číslo (počet osob), nebo nechte prázdnou.';

    $pouzite = aed_pouzite_klice($stara ?? []);
    $platneStare = array_map(fn($p) => (string)($p['klic'] ?? ''), array_filter((array)(json_pole((string)($stara['formular'] ?? ''))['vlastni'] ?? []), 'is_array'));
    $vlastni = [];
    $videne = [];
    $vstupVl = is_array($_POST['vl'] ?? null) ? $_POST['vl'] : [];
    $poziceVl = 0;
    $cfgVlastni = [];              // pro znovuvykreslení po chybě
    foreach ($vstupVl as $radek) {
        if (!is_array($radek)) continue;
        $poziceVl++;
        $t = fn(string $k, int $max) => is_scalar($radek[$k] ?? null) ? mb_substr(trim(str_replace("\r\n", "\n", (string)$radek[$k])), 0, $max) : '';
        $klic = preg_replace('/[^a-z0-9_]/', '', strtolower($t('klic', 20)));
        $popisek = $t('popisek', 160);
        $typ = $t('typ', 20);
        if (!isset(AKCE_TYPY_POLI[$typ])) $typ = 'text';
        $moznosti = array_slice(array_values(array_unique(array_filter(array_map(fn($m) => mb_substr(trim($m), 0, 120), preg_split('/\R/', $t('moznosti', 4000))), fn($m) => $m !== ''))), 0, 30);
        $povinne = !empty($radek['povinne']);
        $odebrat = !empty($radek['odebrat']);
        $cfgVlastni[] = ['klic' => $klic, 'popisek' => $popisek, 'typ' => $typ, 'moznosti' => $moznosti, 'povinne' => $povinne, 'odebrat' => $odebrat];
        if ($odebrat) continue;
        if ($popisek === '') {
            if ($moznosti || $klic !== '') $chyby[] = 'Vlastní pole č. ' . $poziceVl . ' nemá popisek – vyplňte ho, nebo pole odeberte.';
            continue;                                             // prázdný nový řádek se tiše přeskočí
        }
        if ($typ === 'vyber' && count($moznosti) < 2) $chyby[] = 'U pole „' . $popisek . '“ (výběr z možností) vyplňte aspoň dvě možnosti – každou na nový řádek.';
        /* klíč: stávající pole si ho nechá, nové dostane další volný fN */
        if ($klic === '' || !in_array($klic, $platneStare, true) || in_array($klic, $videne, true)) $klic = aed_novy_klic($pouzite);
        $videne[] = $klic;
        $pole = ['klic' => $klic, 'popisek' => $popisek, 'typ' => $typ];
        if ($typ === 'vyber') $pole['moznosti'] = $moznosti;
        $pole['povinne'] = $povinne;
        $vlastni[] = $pole;
    }
    if (count($vlastni) > AE_MAX_VLASTNICH) $chyby[] = 'Vlastních polí je moc – nejvýš ' . AE_MAX_VLASTNICH . '.';

    $formular = [
        'pole'     => ['telefon' => (bool)vstup_bool('pole_telefon'), 'pocet' => (bool)vstup_bool('pole_pocet'), 'poznamka' => (bool)vstup_bool('pole_poznamka')],
        'povinne'  => ['telefon' => (bool)vstup_bool('povinne_telefon'), 'poznamka' => (bool)vstup_bool('povinne_poznamka')],
        'vlastni'  => $vlastni,
        'tlacitko' => vstup('tlacitko', 60),
        'poznamka' => vstup('form_poznamka', 255),
    ];
    if (!$formular['pole']['telefon']) $formular['povinne']['telefon'] = false;
    if (!$formular['pole']['poznamka']) $formular['povinne']['poznamka'] = false;
    $a['formular'] = json_ulozit($formular);
    if (strlen($a['formular']) > 60000) $chyby[] = 'Nastavení formuláře je příliš dlouhé – zkraťte popisky nebo možnosti výběru.';   // MySQL TEXT = 64 kB
    $cfg = akce_formular($a);
    $cfg['_radky'] = $cfgVlastni;

    /* --- fotka (až když je zbytek v pořádku) --- */
    $foto = null;
    if (!$chyby) {
        $foto = admin_obrazek('foto', $stara['foto'] ?? '', 'akce', 1600, 1600, $a['nazev']);
        if ($foto['chyba'] !== '') $chyby[] = $foto['chyba'] . ' Vyberte prosím fotku znovu.';
    }

    if (!$chyby) {
        $data = [
            'nazev' => $a['nazev'], 'rok' => (int)$rokRaw, 'mesic' => (int)$mesicRaw, 'datum_od' => $od ?: null, 'datum_do' => $do ?: null,
            'termin_text' => $a['termin_text'], 'cas' => $a['cas'], 'misto' => $a['misto'], 'stitek' => $a['stitek'],
            'perex' => $a['perex'], 'popis' => $a['popis'], 'foto' => $foto['soubor'], 'odkaz' => $a['odkaz'],
            'odkaz_text' => $a['odkaz'] !== '' ? $a['odkaz_text'] : '', 'prihlaseni_povoleno' => $a['prihlaseni_povoleno'],
            'formular' => $a['formular'], 'prihlaseni_do' => $prDo ?: null, 'kapacita' => $kapRaw !== '' ? (int)$kapRaw : null,
            'poznamka_interni' => $a['poznamka_interni'], 'visible' => $a['visible'], 'updated_at' => ted(),
        ];
        try {
            if ($stara) {
                if ((int)$stara['rok'] !== (int)$rokRaw || (int)$stara['mesic'] !== (int)$mesicRaw) {
                    $data['poradi'] = (int)val('SELECT COALESCE(MAX(poradi), -1) + 1 FROM cltk_akce WHERE rok = ? AND mesic = ?', [(int)$rokRaw, (int)$mesicRaw]);
                }
                db_update('cltk_akce', $id, $data);
            } else {
                $data['poradi'] = (int)val('SELECT COALESCE(MAX(poradi), -1) + 1 FROM cltk_akce WHERE rok = ? AND mesic = ?', [(int)$rokRaw, (int)$mesicRaw]);
                $data['created_at'] = ted();
                $id = db_insert('cltk_akce', $data);
            }
        } catch (Throwable $e) {
            if ($foto['soubor'] !== (string)($stara['foto'] ?? '')) admin_smazat_soubory([$foto['soubor']]);
            throw $e;
        }
        admin_smazat_soubory($foto['smazat']);                    // TEPRVE po zápisu do DB

        $ulozena = akce_doplnit(row('SELECT * FROM cltk_akce WHERE id = ?', [$id]));
        $hlaska = 'Akce „' . $a['nazev'] . '“ je uložená.';
        $druh = 'ok';
        if ($ulozena['prihlaseni_povoleno'] && !$ulozena['prihlaseni']) {
            $hlaska .= ' Tlačítko Přihlásit se ale návštěvníci neuvidí – ' . ($ulozena['probehla'] ? 'akce už proběhla.' : (akce_obsazeno($ulozena) ? 'akce je obsazená.' : 'uzávěrka přihlášek už minula.'));
            $druh = 'warn';
        }
        if ($ulozena['kapacita'] !== null && akce_obsazenost($id) > (int)$ulozena['kapacita']) {
            $hlaska .= ' Přihlášených je už víc, než je kapacita.';
            $druh = 'warn';
        }
        redirect('akce-edit.php?id=' . $id, $hlaska, $druh);
    }
}

/* --- vykreslení --- */
$nova = (int)$a['id'] === 0;
$prihl = !$nova ? rows('SELECT * FROM cltk_signups WHERE akce_id = ? ORDER BY created_at, id', [(int)$a['id']]) : [];
$sloupce = !$nova ? signup_sloupce($stara ?? $a, $prihl) : [];
$osob = array_sum(array_map(fn($p) => (int)$p['pocet'], $prihl));
$nevyrizeno = count(array_filter($prihl, fn($p) => (int)$p['vyrizeno'] === 0));
$nahled = $stara ? akce_doplnit($stara) : null;
$radkyVl = $cfg['_radky'] ?? array_map(fn($p) => $p + ['odebrat' => false], $cfg['vlastni']);
$mesice = ['' => '— vyberte —'] + CZ_MESICE;

admin_head($nova ? 'Nová akce' : $a['nazev'], $user, [
    'zpet' => [AE_SEZNAM . '?rok=' . (int)($stara['rok'] ?? $a['rok'] ?: substr(dnes(), 0, 4)), 'Kalendář akcí'],
    'podnadpis' => $nova ? 'Akce v klubovém kalendáři na úvodu. Stačí název a měsíc – termín a ostatní můžete doplnit později.'
                         : ($nahled ? e(cz_mesic((int)$nahled['mesic']) . ' ' . $nahled['rok'] . ' · ' . akce_termin($nahled, true)) . ' · ' . ((int)$nahled['visible'] === 1 ? 'v kalendáři' : 'skrytá') : ''),
    'akce' => !$nova ? '<a class="btn btn-ghost" href="' . e(url('akce.php?id=' . (int)$a['id'])) . '" target="_blank" rel="noopener">Zobrazit na webu ↗</a>' : '',
]);
echo '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-uvod.css')) . '">';
?>
<?php if ($chyby): ?>
  <ul class="uv-chyby" role="alert"><li><b>Akci se nepodařilo uložit:</b></li><?php foreach ($chyby as $ch): ?><li><?= e($ch) ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<?php if (trim((string)($stara['poznamka_interni'] ?? '')) !== ''): ?>
  <div class="uv-interni" style="margin-bottom:22px"><b>Interní poznámka – jen pro administraci</b><?= nl2br(e($stara['poznamka_interni']), false) ?></div>
<?php endif; ?>

<form method="post" class="form wide" enctype="multipart/form-data" data-hlidat-zmeny>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="ulozit">
  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">

  <section class="panel">
    <div class="panel-head"><h2>Akce a termín</h2></div>
    <div class="panel-body form">
      <?= pole_text('nazev', 'Název akce', $a['nazev'], ['required' => true, 'maxlength' => 200, 'placeholder' => 'Klubový den – čtyřhry, barbecue, kvíz']) ?>
      <?= pole_text('stitek', 'Štítek v okně kalendáře', $a['stitek'], ['maxlength' => 80, 'placeholder' => 'Klubová akce',
            'hint' => 'Drobný zlatý nadpis nad názvem – Turnaj, Klubová akce, Tenisová škola…']) ?>
      <?= pole_radek([
            pole_text('rok', 'Rok v kalendáři', (string)$a['rok'], ['required' => true, 'maxlength' => 4, 'attrs' => ['inputmode' => 'numeric', 'pattern' => '\d{4}']]),
            pole_select('mesic', 'Měsíc v kalendáři', (string)$a['mesic'], $mesice, ['required' => true,
                'hint' => 'Pod tímto měsícem se akce ukáže, i když datum ještě není.']),
          ]) ?>
      <?= pole_radek([
            pole_datum('datum_od', 'Datum od', (string)$a['datum_od'], ['attrs' => ['data-akce-datum-od' => '1'], 'hint' => 'Prázdné = „termín doplní klub“.']),
            pole_datum('datum_do', 'Datum do', (string)$a['datum_do'], ['hint' => 'Jen u vícedenních akcí.']),
          ]) ?>
      <?= pole_text('termin_text', 'Vlastní text termínu (nepovinné)', $a['termin_text'], ['maxlength' => 120, 'placeholder' => 'přetlakové haly od 5. 10.',
            'hint' => 'Nahradí v seznamu datum – např. „potvrzeno“. Prázdné = složí se z data, a když chybí, „termín doplní klub“.']) ?>
      <?= pole_radek([
            pole_text('cas', 'Čas', $a['cas'], ['maxlength' => 60, 'placeholder' => 'od 19:00']),
            pole_text('misto', 'Místo', $a['misto'], ['maxlength' => 160, 'placeholder' => 'Letenský zámeček']),
          ]) ?>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head"><h2>Texty a fotka</h2></div>
    <div class="panel-body form">
      <?= pole_textarea('perex', 'Krátký popis do okna kalendáře', $a['perex'], ['rows' => 3, 'maxlength' => 1500, 'attrs' => ['data-pocitadlo' => '260'],
            'hint' => 'Dvě tři věty, které se ukážou vpravo v okně kalendáře.']) ?>
      <?= pole_editor('popis', 'Delší popis (nepovinné)', $a['popis'], ['hint' => 'Na stránce akce pod krátkým popisem – program, co s sebou, ceny. Formátování se při uložení vyčistí.']) ?>
      <?= pole_obrazek('foto', 'Fotka (nepovinné)', $a['foto']) ?>
      <?= pole_radek([
            pole_text('odkaz', 'Odkaz (nepovinné)', $a['odkaz'], ['maxlength' => 255, 'attrs' => ['inputmode' => 'url'], 'placeholder' => 'prague-open.php nebo https://…']),
            pole_text('odkaz_text', 'Text odkazu', $a['odkaz_text'], ['maxlength' => 80, 'placeholder' => 'Web turnaje']),
          ]) ?>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head"><h2>Přihláška</h2><span class="hint">Formulář je u každé akce jiný.</span></div>
    <div class="panel-body form">
      <?= pole_check('prihlaseni_povoleno', 'Zobrazit tlačítko Přihlásit se', (int)$a['prihlaseni_povoleno'] === 1, [
            'hint' => 'V okně kalendáře i na stránce akce se ukáže tlačítko a formulář. Po skončení akce, uzávěrce nebo naplnění kapacity se samo schová.']) ?>
      <div class="form">
        <fieldset>
          <legend>Pole formuláře</legend>
          <p class="hint" style="margin-top:0">Jméno, e-mail a souhlas se zpracováním údajů jsou ve formuláři vždy.</p>
          <div class="form-mrizka">
            <div class="field">
              <?= pole_check('pole_telefon', 'Telefon', $cfg['pole']['telefon']) ?>
              <?= pole_check('povinne_telefon', 'telefon je povinný', $cfg['povinne']['telefon']) ?>
            </div>
            <div class="field">
              <?= pole_check('pole_pocet', 'Počet osob', $cfg['pole']['pocet'], ['hint' => 'Počítá se do kapacity.']) ?>
            </div>
            <div class="field">
              <?= pole_check('pole_poznamka', 'Poznámka', $cfg['pole']['poznamka']) ?>
              <?= pole_check('povinne_poznamka', 'poznámka je povinná', $cfg['povinne']['poznamka']) ?>
            </div>
          </div>
        </fieldset>

        <fieldset>
          <legend>Vlastní pole</legend>
          <p class="hint" style="margin-top:0">Třeba úroveň hry, spoluhráč, počet dětí nebo „mám zájem o barbecue“. Popisek můžete kdykoli opravit – odpovědi zůstanou u pole.</p>
          <div class="uv-pole-seznam" data-vlastni-pole>
            <?php foreach ($radkyVl as $i => $p): $n = 'e' . $i; ?>
              <div class="uv-pole<?= !empty($p['odebrat']) ? ' je-odebrane' : '' ?>">
                <div class="uv-pole__hlava">
                  <b>Pole <span data-pole-cislo><?= $i + 1 ?></span></b>
                  <?php if ($p['klic'] !== ''): ?><small>klíč <?= e($p['klic']) ?></small><?php endif; ?>
                  <span class="uv-pole-akce">
                    <button type="button" class="btn btn-sm btn-ghost" data-pole-nahoru aria-label="Posunout pole výš" title="Posunout výš">↑</button>
                    <button type="button" class="btn btn-sm btn-ghost" data-pole-dolu aria-label="Posunout pole níž" title="Posunout níž">↓</button>
                  </span>
                </div>
                <input type="hidden" name="vl[<?= $n ?>][klic]" value="<?= e($p['klic']) ?>">
                <div class="form-mrizka">
                  <?= pole_text('vl[' . $n . '][popisek]', 'Popisek', $p['popisek'], ['maxlength' => 160, 'id' => 'vl-' . $n . '-popisek', 'placeholder' => 'Úroveň hry']) ?>
                  <?= pole_select('vl[' . $n . '][typ]', 'Typ pole', $p['typ'], AKCE_TYPY_POLI, ['id' => 'vl-' . $n . '-typ', 'attrs' => ['data-pole-typ' => '1']]) ?>
                </div>
                <div class="uv-pole__moznosti">
                  <?= pole_textarea('vl[' . $n . '][moznosti]', 'Možnosti výběru – každá na nový řádek', implode("\n", $p['moznosti']), ['rows' => 3, 'id' => 'vl-' . $n . '-moznosti', 'placeholder' => "začátečník\npokročilý"]) ?>
                </div>
                <div class="btn-row">
                  <?= pole_check('vl[' . $n . '][povinne]', 'Povinné', (bool)$p['povinne'], ['id' => 'vl-' . $n . '-povinne']) ?>
                  <?= pole_check('vl[' . $n . '][odebrat]', 'Odebrat pole z formuláře', !empty($p['odebrat']), ['id' => 'vl-' . $n . '-odebrat', 'attrs' => ['data-pole-odebrat' => '1']]) ?>
                </div>
              </div>
            <?php endforeach; ?>
            <?php /* bez JavaScriptu: jeden prázdný řádek k vyplnění (s JavaScriptem tlačítko „Přidat vlastní pole“) */ ?>
              <noscript>
                <div class="uv-pole">
                  <div class="uv-pole__hlava"><b>Nové pole</b></div>
                  <input type="hidden" name="vl[novy][klic]" value="">
                  <div class="form-mrizka">
                    <?= pole_text('vl[novy][popisek]', 'Popisek', '', ['maxlength' => 160, 'id' => 'vl-novy-popisek']) ?>
                    <?= pole_select('vl[novy][typ]', 'Typ pole', 'text', AKCE_TYPY_POLI, ['id' => 'vl-novy-typ']) ?>
                  </div>
                  <?= pole_textarea('vl[novy][moznosti]', 'Možnosti výběru – každá na nový řádek (jen u výběru)', '', ['rows' => 3, 'id' => 'vl-novy-moznosti']) ?>
                  <?= pole_check('vl[novy][povinne]', 'Povinné', false, ['id' => 'vl-novy-povinne']) ?>
                </div>
              </noscript>
          </div>
          <p class="hint" data-vlastni-prazdno<?= $radkyVl ? ' hidden' : '' ?>>Zatím žádné vlastní pole.</p>
          <div class="btn-row uv-mezera"><button type="button" class="btn btn-zlato" data-pridat-pole hidden>+ Přidat vlastní pole</button></div>
        </fieldset>

        <div class="form-mrizka">
          <?= pole_text('tlacitko', 'Text tlačítka', $cfg['tlacitko'] === 'Přihlásit se' && trim((string)(json_pole((string)$a['formular'])['tlacitko'] ?? '')) === '' ? '' : $cfg['tlacitko'],
                ['maxlength' => 60, 'placeholder' => 'Přihlásit se', 'hint' => 'Prázdné = „Přihlásit se“.']) ?>
          <?= pole_text('form_poznamka', 'Poznámka pod formulářem', $cfg['poznamka'], ['maxlength' => 255, 'placeholder' => 'Přihlášky do 20. 5.']) ?>
          <?= pole_datum('prihlaseni_do', 'Uzávěrka přihlášek', (string)$a['prihlaseni_do'], ['hint' => 'Po tomto dni se tlačítko schová. Prázdné = do konce akce.']) ?>
          <?= pole_text('kapacita', 'Kapacita (osob)', $a['kapacita'] === null ? '' : (string)$a['kapacita'], ['maxlength' => 6, 'attrs' => ['inputmode' => 'numeric', 'pattern' => '\d*'],
                'hint' => 'Prázdné = bez omezení. Po naplnění se přihlašování zavře.']) ?>
        </div>
      </div>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head"><h2>Pro administraci</h2></div>
    <div class="panel-body form">
      <?= pole_textarea('poznamka_interni', 'Interní poznámka', $a['poznamka_interni'], ['rows' => 3, 'maxlength' => 2000,
            'hint' => 'Na webu se nikdy neukáže – třeba „ověřit název belgického klubu“. V seznamu akcí svítí žlutě.']) ?>
      <?= pole_check('visible', 'Zobrazit v kalendáři na webu', (int)$a['visible'] === 1) ?>
    </div>
  </section>

  <?= tlacitka_formulare($nova ? 'Přidat akci' : 'Uložit změny', AE_SEZNAM . '?rok=' . (int)($a['rok'] ?: substr(dnes(), 0, 4)), 'Zpět na kalendář') ?>
</form>

<template data-sablona-pole>
  <div class="uv-pole">
    <div class="uv-pole__hlava">
      <b>Pole <span data-pole-cislo></span> <small>(nové)</small></b>
      <span class="uv-pole-akce">
        <button type="button" class="btn btn-sm btn-ghost" data-pole-nahoru aria-label="Posunout pole výš" title="Posunout výš">↑</button>
        <button type="button" class="btn btn-sm btn-ghost" data-pole-dolu aria-label="Posunout pole níž" title="Posunout níž">↓</button>
        <button type="button" class="btn btn-sm btn-danger" data-pole-zrusit>Zrušit</button>
      </span>
    </div>
    <input type="hidden" name="vl[__N__][klic]" value="">
    <div class="form-mrizka">
      <?= pole_text('vl[__N__][popisek]', 'Popisek', '', ['maxlength' => 160, 'id' => 'vl-__N__-popisek', 'placeholder' => 'Úroveň hry']) ?>
      <?= pole_select('vl[__N__][typ]', 'Typ pole', 'text', AKCE_TYPY_POLI, ['id' => 'vl-__N__-typ', 'attrs' => ['data-pole-typ' => '1']]) ?>
    </div>
    <div class="uv-pole__moznosti">
      <?= pole_textarea('vl[__N__][moznosti]', 'Možnosti výběru – každá na nový řádek', '', ['rows' => 3, 'id' => 'vl-__N__-moznosti', 'placeholder' => "začátečník\npokročilý"]) ?>
    </div>
    <div class="btn-row">
      <?= pole_check('vl[__N__][povinne]', 'Povinné', false, ['id' => 'vl-__N__-povinne']) ?>
    </div>
  </div>
</template>

<?php if (!$nova): $nahledCfg = akce_formular($stara); ?>
<section class="panel" id="nahled-formulare">
  <div class="panel-head"><h2>Náhled formuláře</h2><span class="hint">Podle uloženého nastavení<?= (int)$stara['prihlaseni_povoleno'] === 1 ? '' : ' – teď vypnuto, návštěvníci ho nevidí' ?>.</span></div>
  <div class="panel-body">
    <div class="uv-nahled-form" aria-hidden="true">
      <div class="uv-nahled-form__pole"><span>Jméno a příjmení *</span><i></i></div>
      <div class="uv-nahled-form__pole"><span>E-mail *</span><i></i></div>
      <?php if ($nahledCfg['pole']['telefon']): ?><div class="uv-nahled-form__pole"><span>Telefon<?= $nahledCfg['povinne']['telefon'] ? ' *' : '' ?></span><i></i></div><?php endif; ?>
      <?php if ($nahledCfg['pole']['pocet']): ?><div class="uv-nahled-form__pole"><span>Počet osob</span><i style="max-width:120px"></i></div><?php endif; ?>
      <?php foreach ($nahledCfg['vlastni'] as $p): ?>
        <?php if ($p['typ'] === 'zaskrtavatko'): ?>
          <div class="uv-nahled-form__check"><?= e($p['popisek']) ?><?= $p['povinne'] ? ' *' : '' ?></div>
        <?php else: ?>
          <div class="uv-nahled-form__pole"><span><?= e($p['popisek']) ?><?= $p['povinne'] ? ' *' : '' ?></span>
            <?php if ($p['typ'] === 'vyber'): ?><small class="uv-tlumene">výběr: <?= e(implode(' · ', $p['moznosti'])) ?></small><?php endif; ?><i<?= $p['typ'] === 'cislo' ? ' style="max-width:120px"' : '' ?>></i></div>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if ($nahledCfg['pole']['poznamka']): ?><div class="uv-nahled-form__pole"><span>Poznámka<?= $nahledCfg['povinne']['poznamka'] ? ' *' : '' ?></span><i class="vysoke"></i></div><?php endif; ?>
      <div class="uv-nahled-form__check">Souhlasím se zpracováním osobních údajů *</div>
      <span class="uv-nahled-form__btn"><?= e($nahledCfg['tlacitko']) ?></span>
      <?php if ($nahledCfg['poznamka'] !== ''): ?><p class="uv-nahled-form__pozn"><?= e($nahledCfg['poznamka']) ?></p><?php endif; ?>
    </div>
  </div>
</section>

<section class="panel" id="prihlaseni">
  <div class="panel-head">
    <h2>Přihlášení <small><?= count($prihl) ?> <?= sklonuj(count($prihl), 'přihláška', 'přihlášky', 'přihlášek') ?> · <?= $osob ?> os.<?= $stara['kapacita'] !== null ? ' z ' . (int)$stara['kapacita'] : '' ?></small></h2>
    <?php if ($prihl): ?>
      <div class="btn-row">
        <a class="btn btn-sm btn-primary" href="akce-edit.php?id=<?= (int)$a['id'] ?>&amp;csv=1">Stáhnout CSV</a>
        <a class="btn btn-sm btn-ghost" href="prihlasky.php?druh=akce&amp;akce=<?= (int)$a['id'] ?>">V Přihláškách</a>
        <?php if ($nevyrizeno > 0): ?><?= tlacitko_akce('vyrizeno_vse', (int)$a['id'], 'Vše vyřízeno', 'btn-ghost', [], 'Označit všech ' . $nevyrizeno . ' nevyřízených přihlášek jako vyřízené?') ?><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php if (!$prihl): ?>
    <?= prazdny_stav('Zatím nikdo', (int)$stara['prihlaseni_povoleno'] === 1
          ? 'Přihlášky, které návštěvníci odešlou z kalendáře nebo ze stránky akce, se objeví tady.'
          : 'U akce je přihlašování vypnuté. Zapněte „Zobrazit tlačítko Přihlásit se“ výše.') ?>
  <?php else: ?>
    <div class="tbl-wrap tbl-karty">
      <table>
        <thead><tr><th>#</th><?php foreach ($sloupce as $s): ?><th><?= e($s['popisek']) ?></th><?php endforeach; ?><th>Stav</th><th class="right">Akce</th></tr></thead>
        <tbody>
        <?php foreach ($prihl as $i => $p): ?>
          <tr<?= (int)$p['vyrizeno'] === 1 ? ' class="je-skryte"' : '' ?>>
            <td data-label="#" class="uv-tlumene"><?= $i + 1 ?></td>
            <?php foreach ($sloupce as $s): $h = signup_hodnota($p, $s['klic']); ?>
              <td data-label="<?= e($s['popisek']) ?>"<?= $s['klic'] === 'created_at' ? ' class="uv-nowrap uv-tlumene"' : '' ?>>
                <?php if ($s['klic'] === 'email' && $h !== ''): ?><a href="mailto:<?= e($h) ?>"><?= e($h) ?></a>
                <?php elseif ($s['klic'] === 'telefon' && $h !== ''): ?><a href="<?= e(tel_href($h)) ?>" class="uv-nowrap"><?= e($h) ?></a>
                <?php else: ?><?= $h !== '' ? nl2br(e($h), false) : '<span class="uv-tlumene">–</span>' ?><?php endif; ?>
              </td>
            <?php endforeach; ?>
            <td data-label="Stav"><?= (int)$p['vyrizeno'] === 1 ? badge('Vyřízeno', 'ok') : badge('Nová', 'zlato') ?>
              <?php if (trim((string)$p['poznamka_admin']) !== ''): ?><br><small class="uv-tlumene" title="Poznámka kanceláře"><?= e(uryvek($p['poznamka_admin'], 60)) ?></small><?php endif; ?></td>
            <td data-label="Akce" class="right"><div class="akce-radku">
              <?= tlacitko_akce('vyrizeno', (int)$a['id'], (int)$p['vyrizeno'] === 1 ? 'Nevyřízeno' : 'Vyřízeno', 'btn-ghost', ['prihlaska' => (int)$p['id']]) ?>
              <a class="btn btn-sm btn-ghost" href="prihlasky.php?druh=akce&amp;id=<?= (int)$p['id'] ?>">Detail</a>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-body">
      <div class="btn-row">
        <?= tlacitko_akce('smazat_prihlasky', (int)$a['id'], 'Smazat všechny přihlášky akce', 'btn-danger', [],
              'Opravdu smazat všech ' . count($prihl) . ' přihlášek k akci „' . $a['nazev'] . '“? Nejde to vrátit – nejdřív si je případně stáhněte do CSV.') ?>
        <span class="hint" style="margin:0">Po akci osobní údaje přihlášených smažte, až je nebudete potřebovat.</span>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php
echo '<script src="' . e(BASE_PATH . verze('admin/assets/admin-uvod.js')) . '" defer></script>';
admin_foot();
