<?php
/* Datové pomůcky pro veřejné stránky.
   Vracejí jen viditelné záznamy (visible = 1) ve správném pořadí.
   Chyba databáze stránku neshodí – zapíše se do data/chyby.log
   a funkce vrátí prázdný výsledek. Přehled: inc/PRUVODCE-FRONT.md. */

/** rows() s pojistkou: při chybě zapíše do protokolu a vrátí []. */
function data_rows(string $sql, array $p = []): array {
    try {
        return rows($sql, $p);
    } catch (Throwable $e) {
        error_log('[data] ' . $e->getMessage() . ' | ' . $sql);
        return [];
    }
}

function data_row(string $sql, array $p = []): ?array {
    $r = data_rows($sql, $p);
    return $r[0] ?? null;
}

/* ==================================================================
   MENU A ODKAZY
   ================================================================== */

/** Kam vede položka Restaurace: vlastní web z nastavení, jinak restaurace.php. */
function odkaz_restaurace(): array {
    $u = bezpecny_odkaz(setting('restaurace_url'));
    return $u !== '' ? ['url' => $u, 'externi' => odkaz_je_externi($u)] : ['url' => url('restaurace.php'), 'externi' => false];
}

/** Kam vede položka Prague Open: web turnaje z nastavení, jinak prague-open.php. */
function odkaz_prague_open(): array {
    $u = bezpecny_odkaz(setting('prague_open_url'));
    return $u !== '' ? ['url' => $u, 'externi' => odkaz_je_externi($u)] : ['url' => url('prague-open.php'), 'externi' => false];
}

/** Rezervační systém (tlačítko Rezervovat kurt – vždy do nového okna). */
function rezervace_url(): string {
    return bezpecny_odkaz(setting('rezervace_url', 'https://www.rogeronline.cz/v2/index.php?klub=181'));
}

/**
 * Hlavní menu podle ZADANI §3. Každá položka:
 *   nazev, url, soubor (hlavní stránka), soubory (všechny stránky větve – pro zvýraznění),
 *   strana ('l' vlevo od znaku / 'p' vpravo), externi (bool), aktivni (bool),
 *   podmenu: [ [nazev, url, soubor, aktivni], … ] (u Restaurace a Prague Open prázdné)
 */
function menu_hlavni(): array {
    $definice = [
        ['Klub', 'klub.php', 'l', [
            ['Členství', 'clenstvi.php'], ['Historie', 'historie.php'], ['Vedení', 'vedeni.php'],
            ['CTC', 'ctc.php'], ['Revue', 'revue.php'],
        ]],
        ['Areál a služby', 'areal.php', 'l', [
            ['Ceník kurtů', 'cenik-kurtu.php'], ['Privátní trenéři', 'privatni-treneri.php'],
            ['Body Solution', 'body-solution.php'], ['Sportovní lékařství', 'sportovni-lekarstvi.php'],
        ]],
        ['Závodní tenis', 'zavodni-tenis.php', 'l', [
            ['Trenérský tým', 'zavodni-tenis-treneri.php'],
        ]],
        ['Tenisová škola', 'tenisova-skola.php', 'l', [
            ['Informace', 'tenisova-skola.php'], ['Ceníky', 'tenisova-skola-ceniky.php'],
            ['Rozvrhy', 'tenisova-skola-rozvrhy.php'], ['Trenérský tým', 'tenisova-skola-treneri.php'],
            ['Letní kempy', 'letni-kempy.php'],
        ]],
    ];
    // stránky větve mimo podmenu (zvýrazní položku menu): dokumenty klubu patří pod Klub
    $navic = ['klub.php' => ['dokumenty.php', 'dokument.php']];
    $tady = here();
    $menu = [];
    foreach ($definice as [$nazev, $soubor, $strana, $pod]) {
        $soubory = array_merge([$soubor], array_column($pod, 1), $navic[$soubor] ?? []);
        $podmenu = [];
        foreach ($pod as [$pn, $ps]) {
            $podmenu[] = ['nazev' => $pn, 'url' => url($ps), 'soubor' => $ps, 'aktivni' => $tady === $ps];
        }
        $menu[] = [
            'nazev' => $nazev, 'url' => url($soubor), 'soubor' => $soubor, 'soubory' => $soubory,
            'strana' => $strana, 'externi' => false, 'aktivni' => in_array($tady, $soubory, true),
            'podmenu' => $podmenu,
        ];
    }
    foreach ([['Restaurace', odkaz_restaurace(), 'restaurace.php'], ['Prague Open', odkaz_prague_open(), 'prague-open.php']] as [$nazev, $o, $soubor]) {
        $menu[] = [
            'nazev' => $nazev, 'url' => $o['url'], 'soubor' => $o['externi'] ? '' : $soubor, 'soubory' => [$soubor],
            'strana' => 'p', 'externi' => $o['externi'], 'aktivni' => !$o['externi'] && $tady === $soubor,
            'podmenu' => [],
        ];
    }
    return $menu;
}

/** Sloupec patičky „Důležité informace“ (ZADANI §4.10). */
function menu_paticka(): array {
    $polozky = [
        ['Členství', 'clenstvi.php'], ['Ceník kurtů', 'cenik-kurtu.php'], ['Závodní tenis', 'zavodni-tenis.php'],
        ['Tenisová škola', 'tenisova-skola.php'], ['Letní kempy', 'letni-kempy.php'],
        ['Privátní trenéři', 'privatni-treneri.php'], ['Historie', 'historie.php'],
    ];
    return array_map(fn($p) => ['nazev' => $p[0], 'url' => url($p[1]), 'soubor' => $p[1]], $polozky);
}

/** Kontakty do patičky – jen Recepce a kancelář (Eva Štefková), vše z Textů a údajů. */
function kontakty_paticka(): array {
    return [
        ['nazev' => 'Recepce', 'popis' => setting('recepce_popis', 'rezervace kurtů'),
         'telefon' => setting('recepce_telefon'), 'email' => setting('recepce_email')],
        ['nazev' => setting('kancelar_jmeno', 'Eva Štefková'), 'popis' => setting('kancelar_popis'),
         'telefon' => setting('kancelar_telefon'), 'email' => setting('kancelar_email')],
    ];
}

/** Sociální sítě a galerie z nastavení (jen vyplněné): [[nazev, url], …] */
function site_odkazy(): array {
    $v = [];
    foreach ([['Facebook', 'facebook_url'], ['Instagram', 'instagram_url'], ['YouTube', 'youtube_url'], ['Fotogalerie klubu', 'fotogalerie_url']] as [$n, $k]) {
        $u = bezpecny_odkaz(setting($k));
        if ($u !== '') $v[] = ['nazev' => $n, 'url' => $u, 'klic' => $k];
    }
    return $v;
}

/* ==================================================================
   ÚVODNÍ STRÁNKA
   ================================================================== */

/** Aktivní zprávy informační lišty. Prázdné pole = lištu vůbec nevypisovat. */
function aktivni_oznameni(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $d = dnes();
    return $cache = data_rows(
        'SELECT * FROM cltk_oznameni
          WHERE visible = 1 AND text <> \'\'
            AND (plati_od IS NULL OR plati_od <= ?)
            AND (plati_do IS NULL OR plati_do >= ?)
       ORDER BY poradi, id', [$d, $d]);
}

function uvodni_galerie(): array {
    return data_rows('SELECT * FROM cltk_uvodni_galerie WHERE visible = 1 ORDER BY poradi, id');
}

function aktuality(int $limit = 3): array {
    return data_rows('SELECT * FROM cltk_aktuality WHERE visible = 1 ORDER BY poradi, id LIMIT ' . max(1, $limit));
}

/** Poslední výsledky (nejnovější první); každý řádek má navíc 'sety_pole' ze sety_rozloz(). */
function posledni_vysledky(int $limit = 8): array {
    $r = data_rows('SELECT * FROM cltk_vysledky WHERE visible = 1 ORDER BY datum DESC, poradi, id LIMIT ' . max(1, $limit));
    foreach ($r as &$v) $v['sety_pole'] = sety_rozloz((string)$v['sety']);
    return $r;
}

/**
 * Sety z JSON → [['text'=>'9:8 (7:5)', 'hlavni'=>'9:8', 'doplnek'=>'(7:5)', 'vyhra'=>true], …]
 * Výhra = první číslo větší než druhé (z pohledu našeho hráče).
 */
function sety_rozloz(?string $json): array {
    $pole = json_pole($json);
    if (!$pole && trim((string)$json) !== '' && !str_starts_with(trim((string)$json), '[')) {
        $pole = json_pole(sety_z_textu((string)$json));
    }
    $v = [];
    foreach ($pole as $s) {
        $s = trim((string)$s);
        if ($s === '') continue;
        if (preg_match('/^\[?(\d+)\s*[:\-]\s*(\d+)\]?\s*(.*)$/u', $s, $m)) {
            $v[] = ['text' => $s, 'hlavni' => $m[1] . ':' . $m[2], 'doplnek' => trim($m[3]), 'vyhra' => (int)$m[1] > (int)$m[2]];
        } else {
            $v[] = ['text' => $s, 'hlavni' => $s, 'doplnek' => '', 'vyhra' => false];
        }
    }
    return $v;
}

/** „6:3 1:6 10:6“ / „6-3, 1-6, [10-6]“ / „9:8 (7:5)“ / „1:0 skr.“ → JSON pole pro sloupec sety. */
function sety_z_textu(string $text): string {
    preg_match_all('/\[?\d+\s*[:\-]\s*\d+\]?(?:\s*\(\s*\d+\s*[:\-]\s*\d+\s*\))?(?:\s*skr\.?)?/u', $text, $m);
    $sety = [];
    foreach ($m[0] as $s) {
        $s = trim(str_replace(['[', ']'], '', $s));
        $s = preg_replace('/(\d+)\s*[:\-]\s*(\d+)/', '$1:$2', $s);
        $s = preg_replace('/\s*\(\s*(\d+:\d+)\s*\)/', ' ($1)', $s);
        $s = preg_replace('/\s*skr\.?$/', ' skr.', $s);
        $sety[] = $s;
    }
    return json_ulozit($sety);
}

/* ==================================================================
   KALENDÁŘ AKCÍ
   Řazení a „nejbližší akce“ musí sedět na serveru i v JS:
     začátek akce = datum_od, a když chybí, POSLEDNÍ den měsíce
                    (akce s neznámým dnem se řadí až za akce s datem téhož měsíce)
     konec akce   = datum_do, jinak datum_od, jinak poslední den měsíce
     proběhlá     = konec < dnes
     nejbližší    = akce s nejmenším začátkem, který je >= dnes (nadcházející);
                    když žádná nadcházející není, právě probíhající akce
                    (začátek < dnes <= konec) s nejpozdějším začátkem;
                    při shodě rozhoduje pořadí v seznamu (měsíc, začátek, poradi, id)
   ================================================================== */

function akce_posledni_den_mesice(int $rok, int $mesic): string {
    if ($rok < 1900 || $mesic < 1 || $mesic > 12) return '9999-12-31';
    return date('Y-m-t', mktime(0, 0, 0, $mesic, 1, $rok));
}

function akce_zacatek(array $a): string {
    if (!empty($a['datum_od'])) return substr((string)$a['datum_od'], 0, 10);
    return akce_posledni_den_mesice((int)$a['rok'], (int)$a['mesic']);
}

function akce_konec(array $a): string {
    if (!empty($a['datum_do'])) return substr((string)$a['datum_do'], 0, 10);
    if (!empty($a['datum_od'])) return substr((string)$a['datum_od'], 0, 10);
    return akce_posledni_den_mesice((int)$a['rok'], (int)$a['mesic']);
}

function akce_probehla(array $a): bool {
    return akce_konec($a) < dnes();
}

/** Text termínu do seznamu: vlastní text, jinak data („8.–15. 2.“), jinak „termín doplní klub“. */
function akce_termin(array $a, bool $sRokem = false): string {
    $t = trim((string)($a['termin_text'] ?? ''));
    if ($t !== '') return $t;
    if (!empty($a['datum_od'])) return cz_range((string)$a['datum_od'], (string)($a['datum_do'] ?? ''), $sRokem);
    return 'termín doplní klub';
}

/** Doplní k řádku akce vypočtené údaje (termin, zacatek, konec, probehla, mesic_nazev, prihlaseni). */
function akce_doplnit(array $a): array {
    $a['termin']      = akce_termin($a);
    $a['zacatek']     = akce_zacatek($a);
    $a['konec']       = akce_konec($a);
    $a['probehla']    = akce_probehla($a);
    $a['mesic_nazev'] = cz_mesic((int)$a['mesic']);
    $a['bez_data']    = empty($a['datum_od']);
    $a['prihlaseni']  = akce_prihlaseni_otevreno($a);
    return $a;
}

/** Akce jednoho roku pro kalendář (výchozí = letošní rok), seřazené podle měsíce a data. */
function akce_rok(?int $rok = null): array {
    $rok = $rok ?? (int)substr(dnes(), 0, 4);
    $r = data_rows('SELECT * FROM cltk_akce WHERE visible = 1 AND rok = ? ORDER BY mesic, poradi, id', [$rok]);
    $r = array_map('akce_doplnit', $r);
    usort($r, fn($x, $y) => [(int)$x['mesic'], $x['zacatek'], (int)$x['poradi'], (int)$x['id']]
                        <=> [(int)$y['mesic'], $y['zacatek'], (int)$y['poradi'], (int)$y['id']]);
    return $r;
}

/** Nejbližší nadcházející (nebo právě probíhající) akce. Bez argumentu hledá v letošním a příštím roce. */
function nejblizsi_akce(?array $seznam = null): ?array {
    if ($seznam === null) {
        $rok = (int)substr(dnes(), 0, 4);
        $seznam = array_merge(akce_rok($rok), akce_rok($rok + 1));
    }
    $dnes = dnes();
    $nadchazejici = null;
    $probihajici = null;
    foreach ($seznam as $a) {
        if (!isset($a['zacatek'])) $a = akce_doplnit($a);
        if ($a['konec'] < $dnes) continue;
        if ($a['zacatek'] >= $dnes) {
            if ($nadchazejici === null || $a['zacatek'] < $nadchazejici['zacatek']) $nadchazejici = $a;
        } elseif ($probihajici === null || $a['zacatek'] > $probihajici['zacatek']) {
            $probihajici = $a;
        }
    }
    return $nadchazejici ?? $probihajici;
}

/**
 * Akce jako data pro JavaScript kalendáře (okno s detailem a přihláškou bez
 * přenačtení stránky). Obsahuje jen veřejné údaje – poznamka_interni ne.
 * Texty jsou už připravené do HTML (perex_html, popis_html) nebo prosté (ostatní).
 */
function akce_pro_js(array $a): array {
    if (!isset($a['zacatek'])) $a = akce_doplnit($a);
    $f = akce_formular($a);
    return [
        'id'          => (int)$a['id'],
        'nazev'       => (string)$a['nazev'],
        'rok'         => (int)$a['rok'],
        'mesic'       => (int)$a['mesic'],
        'mesic_nazev' => (string)$a['mesic_nazev'],
        'termin'      => (string)$a['termin'],
        'termin_dlouze' => akce_termin($a, true),
        'zacatek'     => (string)$a['zacatek'],
        'konec'       => (string)$a['konec'],
        'bez_data'    => (bool)$a['bez_data'],
        'probehla'    => (bool)$a['probehla'],
        'cas'         => (string)$a['cas'],
        'misto'       => (string)$a['misto'],
        'stitek'      => (string)$a['stitek'],
        'perex_html'  => paragraphs((string)$a['perex']),
        'popis_html'  => html_ocistit((string)$a['popis']),
        'foto'        => upload_url((string)$a['foto']),
        'odkaz'       => bezpecny_odkaz((string)$a['odkaz']),
        'odkaz_text'  => (string)$a['odkaz_text'],
        'odkaz_externi' => odkaz_je_externi((string)$a['odkaz']),
        'detail_url'  => url('akce.php?id=' . (int)$a['id']),
        'prihlaseni'  => (bool)$a['prihlaseni'],
        'prihlaseni_zapnuto' => (int)$a['prihlaseni_povoleno'] === 1,
        'obsazeno'    => (int)$a['prihlaseni_povoleno'] === 1 && akce_obsazeno($a),
        'formular'    => $f,
    ];
}

/**
 * Data celého kalendáře pro úvodní stránku:
 *   ['rok', 'dnes', 'nejblizsi_id' (nadcházející / probíhající, nebo null),
 *    'vychozi_id' (co ukázat v okně: nejbližší, jinak poslední akce roku, nebo null),
 *    'akce' => [akce_pro_js(), …]]
 * Vložte je do stránky jako <script type="application/json" id="kalendar-data">
 * <?= json_do_stranky(kalendar_data()) ?></script>. JS má nejbližší akci
 * přepočítat podle dnešního data návštěvníka stejným pravidlem (viz výše).
 */
function kalendar_data(?int $rok = null): array {
    $rok = $rok ?? (int)substr(dnes(), 0, 4);
    $seznam = akce_rok($rok);
    $nejblizsi = nejblizsi_akce($seznam);
    $posledni = $seznam ? $seznam[count($seznam) - 1] : null;
    return [
        'rok'          => $rok,
        'dnes'         => dnes(),
        'nejblizsi_id' => $nejblizsi ? (int)$nejblizsi['id'] : null,
        'vychozi_id'   => $nejblizsi ? (int)$nejblizsi['id'] : ($posledni ? (int)$posledni['id'] : null),
        'akce'         => array_map('akce_pro_js', $seznam),
    ];
}

/** Jedna viditelná akce podle id (pro akce.php). */
function akce_detail(int $id): ?array {
    $a = data_row('SELECT * FROM cltk_akce WHERE id = ? AND visible = 1', [$id]);
    return $a ? akce_doplnit($a) : null;
}

/** Kolik osob je na akci přihlášeno (součet „počet osob“). */
function akce_obsazenost(int $akceId): int {
    try {
        return (int)val('SELECT COALESCE(SUM(pocet), 0) FROM cltk_signups WHERE akce_id = ?', [$akceId]);
    } catch (Throwable $e) {
        return 0;
    }
}

function akce_obsazeno(array $a): bool {
    $k = $a['kapacita'] ?? null;
    if ($k === null || $k === '' || (int)$k <= 0) return false;
    return akce_obsazenost((int)$a['id']) >= (int)$k;
}

/** Lze se na akci právě přihlásit? (zapnuté tlačítko, akce neproběhla, uzávěrka, kapacita) */
function akce_prihlaseni_otevreno(array $a): bool {
    if ((int)($a['prihlaseni_povoleno'] ?? 0) !== 1) return false;
    if (akce_konec($a) < dnes()) return false;
    if (!empty($a['prihlaseni_do']) && substr((string)$a['prihlaseni_do'], 0, 10) < dnes()) return false;
    return !akce_obsazeno($a);
}

/* ---------- formulář přihlášky (u každé akce jiný) ---------- */

const AKCE_TYPY_POLI = ['text' => 'Text', 'cislo' => 'Číslo', 'vyber' => 'Výběr z možností', 'zaskrtavatko' => 'Zaškrtávátko'];

/** Normalizované nastavení formuláře akce (viz SCHEMA.md – cltk_akce.formular). */
function akce_formular(array $a): array {
    $cfg = json_pole((string)($a['formular'] ?? ''));
    $prazdne = !$cfg;
    $pole = (array)($cfg['pole'] ?? []);
    $povinne = (array)($cfg['povinne'] ?? []);
    $vysledek = [
        'pole' => [
            'telefon'  => $prazdne ? true : !empty($pole['telefon']),
            'pocet'    => $prazdne ? true : !empty($pole['pocet']),
            'poznamka' => $prazdne ? true : !empty($pole['poznamka']),
        ],
        'povinne' => [
            'telefon'  => !empty($povinne['telefon']),
            'pocet'    => false,
            'poznamka' => !empty($povinne['poznamka']),
        ],
        'vlastni'  => [],
        'tlacitko' => trim((string)($cfg['tlacitko'] ?? '')) ?: 'Přihlásit se',
        'poznamka' => trim((string)($cfg['poznamka'] ?? '')),
    ];
    $klice = [];
    foreach ((array)($cfg['vlastni'] ?? []) as $i => $p) {
        if (!is_array($p)) continue;
        $popisek = trim((string)($p['popisek'] ?? ''));
        if ($popisek === '') continue;
        $typ = (string)($p['typ'] ?? 'text');
        if (!isset(AKCE_TYPY_POLI[$typ])) $typ = 'text';
        $klic = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($p['klic'] ?? ''))) ?: 'f' . ($i + 1);
        while (in_array($klic, $klice, true)) $klic .= 'x';
        $klice[] = $klic;
        $moznosti = array_values(array_filter(array_map(fn($m) => trim((string)$m), (array)($p['moznosti'] ?? [])), fn($m) => $m !== ''));
        if ($typ === 'vyber' && !$moznosti) $typ = 'text';
        $vysledek['vlastni'][] = ['klic' => $klic, 'popisek' => mb_substr($popisek, 0, 160), 'typ' => $typ,
                                  'moznosti' => $moznosti, 'povinne' => !empty($p['povinne'])];
    }
    return $vysledek;
}

/**
 * Ověří odeslaný formulář přihlášky. Názvy polí ve formuláři:
 *   jmeno, email, telefon, pocet, poznamka, souhlas, pole_<klic> (vlastní pole).
 * Vrací ['ok'=>bool, 'chyby'=>[pole=>hláška], 'hodnoty'=>[pro znovuvyplnění], 'radek'=>[pro INSERT]].
 */
function akce_formular_zpracuj(array $a, array $post): array {
    $cfg = akce_formular($a);
    $txt = static function (string $k, int $max) use ($post): string {
        $v = $post[$k] ?? '';
        return is_scalar($v) ? mb_substr(trim(str_replace("\r\n", "\n", (string)$v)), 0, $max) : '';
    };
    $chyby = [];
    $h = [
        'jmeno'    => $txt('jmeno', 160),
        'email'    => $txt('email', 160),
        'telefon'  => $cfg['pole']['telefon'] ? $txt('telefon', 40) : '',
        'pocet'    => $cfg['pole']['pocet'] ? $txt('pocet', 4) : '1',
        'poznamka' => $cfg['pole']['poznamka'] ? $txt('poznamka', 2000) : '',
        'souhlas'  => !empty($post['souhlas']) ? 1 : 0,
    ];
    if ($h['jmeno'] === '') $chyby['jmeno'] = 'Vyplňte prosím jméno a příjmení.';
    if ($h['email'] === '' || !je_email($h['email'])) $chyby['email'] = 'Vyplňte prosím platný e-mail.';
    if ($cfg['pole']['telefon'] && $cfg['povinne']['telefon'] && $h['telefon'] === '') $chyby['telefon'] = 'Vyplňte prosím telefon.';
    if ($h['telefon'] !== '' && !preg_match('/^[+\d][\d \-\/()]{5,}$/', $h['telefon'])) $chyby['telefon'] = 'Telefon nevypadá jako telefonní číslo.';
    $pocet = 1;
    if ($cfg['pole']['pocet']) {
        $pocet = (int)$h['pocet'];
        if ($pocet < 1 || $pocet > 50) $chyby['pocet'] = 'Počet osob musí být 1 až 50.';
    }
    if ($cfg['pole']['poznamka'] && $cfg['povinne']['poznamka'] && $h['poznamka'] === '') $chyby['poznamka'] = 'Vyplňte prosím poznámku.';
    if (!$h['souhlas']) $chyby['souhlas'] = 'Bez souhlasu se zpracováním údajů přihlášku nemůžeme přijmout.';

    $odpovedi = [];
    foreach ($cfg['vlastni'] as $p) {
        $k = 'pole_' . $p['klic'];
        $v = $p['typ'] === 'zaskrtavatko' ? (!empty($post[$k]) ? 'ano' : '') : $txt($k, 500);
        $h[$k] = $v;
        if ($p['povinne'] && $v === '') {
            $chyby[$k] = $p['typ'] === 'zaskrtavatko' ? 'Toto je potřeba potvrdit.' : 'Vyplňte prosím pole „' . $p['popisek'] . '“.';
        } elseif ($v !== '' && $p['typ'] === 'cislo' && !preg_match('/^-?\d+([.,]\d+)?$/', $v)) {
            $chyby[$k] = 'Do pole „' . $p['popisek'] . '“ patří číslo.';
        } elseif ($v !== '' && $p['typ'] === 'vyber' && !in_array($v, $p['moznosti'], true)) {
            $chyby[$k] = 'Vyberte prosím jednu z nabízených možností.';
        }
        $odpovedi[] = ['klic' => $p['klic'], 'popisek' => $p['popisek'], 'hodnota' => $p['typ'] === 'zaskrtavatko' ? ($v === 'ano' ? 'ano' : 'ne') : $v];
    }

    if (!$chyby && !empty($a['kapacita']) && (int)$a['kapacita'] > 0) {
        $volno = (int)$a['kapacita'] - akce_obsazenost((int)$a['id']);
        if ($volno <= 0) $chyby['pocet'] = 'Akce je bohužel už plně obsazená.';
        elseif ($pocet > $volno) $chyby['pocet'] = 'Volných míst zbývá jen ' . $volno . '.';
    }

    return [
        'ok'      => !$chyby,
        'chyby'   => $chyby,
        'hodnoty' => $h,
        'radek'   => [
            'akce_id'        => (int)$a['id'],
            'jmeno'          => $h['jmeno'],
            'email'          => $h['email'],
            'telefon'        => $h['telefon'],
            'pocet'          => $pocet,
            'poznamka'       => $h['poznamka'],
            'souhlas'        => $h['souhlas'],
            'odpovedi'       => json_ulozit($odpovedi),
            'vyrizeno'       => 0,
            'poznamka_admin' => '',
            'created_at'     => ted(),
        ],
    ];
}

/** Sloupce tabulky/CSV přihlášek akce: [['klic'=>…, 'popisek'=>…], …] */
function signup_sloupce(?array $akce, array $prihlasky = []): array {
    $cfg = $akce ? akce_formular($akce) : akce_formular([]);
    $s = [['klic' => 'jmeno', 'popisek' => 'Jméno'], ['klic' => 'email', 'popisek' => 'E-mail']];
    if ($cfg['pole']['telefon'])  $s[] = ['klic' => 'telefon', 'popisek' => 'Telefon'];
    if ($cfg['pole']['pocet'])    $s[] = ['klic' => 'pocet', 'popisek' => 'Počet osob'];
    if ($cfg['pole']['poznamka']) $s[] = ['klic' => 'poznamka', 'popisek' => 'Poznámka'];
    $vlastni = [];
    foreach ($cfg['vlastni'] as $p) $vlastni[$p['klic']] = $p['popisek'];
    foreach ($prihlasky as $p) {
        foreach (json_pole((string)($p['odpovedi'] ?? '')) as $o) {
            $k = (string)($o['klic'] ?? '');
            if ($k !== '' && !isset($vlastni[$k])) $vlastni[$k] = (string)($o['popisek'] ?? $k);
        }
    }
    foreach ($vlastni as $k => $popisek) $s[] = ['klic' => 'pole_' . $k, 'popisek' => $popisek];
    $s[] = ['klic' => 'created_at', 'popisek' => 'Přihlášeno'];
    return $s;
}

/** Hodnota jedné buňky přihlášky (standardní sloupec nebo pole_<klic>). */
function signup_hodnota(array $p, string $klic): string {
    if (str_starts_with($klic, 'pole_')) {
        $k = substr($klic, 5);
        foreach (json_pole((string)($p['odpovedi'] ?? '')) as $o) {
            if ((string)($o['klic'] ?? '') === $k) return (string)($o['hodnota'] ?? '');
        }
        return '';
    }
    if ($klic === 'created_at') return !empty($p['created_at']) ? date('j. n. Y H:i', strtotime((string)$p['created_at'])) : '';
    return (string)($p[$klic] ?? '');
}

/* ==================================================================
   CENÍKY, BLOKY STRÁNEK A OSTATNÍ OBSAH
   ================================================================== */

/** Ceník podle klíče: ['list'=>…, 'sekce'=>[[…, 'radky'=>[…]], …]] nebo null (neexistuje / skrytý). */
function cenik(string $klic): ?array {
    $list = data_row('SELECT * FROM cltk_price_lists WHERE klic = ? AND visible = 1', [$klic]);
    if (!$list) return null;
    $sekce = data_rows('SELECT * FROM cltk_price_sections WHERE list_id = ? ORDER BY poradi, id', [(int)$list['id']]);
    $radky = $sekce ? data_rows('SELECT r.* FROM cltk_price_rows r JOIN cltk_price_sections s ON s.id = r.section_id
                                 WHERE s.list_id = ? ORDER BY r.poradi, r.id', [(int)$list['id']]) : [];
    $podle = [];
    foreach ($radky as $r) $podle[(int)$r['section_id']][] = $r;
    foreach ($sekce as &$s) $s['radky'] = $podle[(int)$s['id']] ?? [];
    return ['list' => $list, 'sekce' => $sekce];
}

/** Jeden textový blok stránky. Když neexistuje, vrátí prázdný blok s 'existuje' => false. */
function blok(string $stranka, string $klic): array {
    static $cache = [];
    $k = $stranka . '|' . $klic;
    if (isset($cache[$k])) return $cache[$k];
    $r = data_row('SELECT * FROM cltk_bloky WHERE stranka = ? AND klic = ?', [$stranka, $klic]);
    if ($r && (int)$r['visible'] === 1) {
        $r['existuje'] = true;
        return $cache[$k] = $r;
    }
    return $cache[$k] = [
        'existuje' => false, 'stranka' => $stranka, 'klic' => $klic, 'stitek' => '', 'nadpis' => '', 'perex' => '',
        'text' => '', 'foto' => '', 'foto_popisek' => '', 'odkaz' => '', 'odkaz_text' => '', 'odkaz2' => '',
        'odkaz2_text' => '', 'doplni_klub' => 1, 'visible' => 0,
    ];
}

/** Všechny viditelné bloky stránky podle pořadí. */
function bloky(string $stranka): array {
    return data_rows('SELECT * FROM cltk_bloky WHERE stranka = ? AND visible = 1 ORDER BY poradi, id', [$stranka]);
}

/** Trenéři podle zařazení: zavodni | skola | privatni (null = všichni). */
function treneri(?string $zarazeni = null): array {
    return $zarazeni === null
        ? data_rows('SELECT * FROM cltk_treneri WHERE visible = 1 ORDER BY zarazeni, poradi, id')
        : data_rows('SELECT * FROM cltk_treneri WHERE visible = 1 AND zarazeni = ? ORDER BY poradi, id', [$zarazeni]);
}

/** Služby v areálu; $jenStitky = jen pilulky pro úvod. */
function sluzby(bool $jenStitky = false): array {
    return data_rows('SELECT * FROM cltk_sluzby WHERE visible = 1' . ($jenStitky ? ' AND je_stitek = 1' : '') . ' ORDER BY poradi, id');
}

function partneri(): array {
    return data_rows('SELECT * FROM cltk_partneri WHERE visible = 1 ORDER BY poradi, id');
}

/** Adresa loga partnera pro web (jednobarevné, když existuje). */
function partner_logo_url(array $p): string {
    $rel = (string)($p['logo_mono'] ?: $p['logo']);
    return $rel !== '' ? upload_url($rel) : '';
}

/** Kategorie dokumentů (admin Dokumenty, štítek nad nadpisem dokument.php, rozcestník dokumenty.php).
 *  Dokumenty jsou text na webu; PDF mají jen přílohy archivu turnajů (kategorie DOKUMENTY_ARCHIV). */
const DOKUMENTY_KATEGORIE = ['klub' => 'Klub a spolek', 'clenstvi' => 'Členství', 'provoz' => 'Pravidla a provozní řády',
                             'cenik' => 'Ceníky', 'skola' => 'Tenisová škola', 'turnaje' => 'Archiv klubových turnajů'];
const DOKUMENTY_ARCHIV = 'turnaje';

/** Viditelné dokumenty kategorie; bez kategorie všechny KROMĚ archivu turnajů
 *  (ten má vlastní výpis dokumenty_archiv() na stránce dokumenty.php). */
function dokumenty(?string $kategorie = null): array {
    return $kategorie === null
        ? data_rows('SELECT * FROM cltk_dokumenty WHERE visible = 1 AND kategorie <> ? ORDER BY poradi, id', [DOKUMENTY_ARCHIV])
        : data_rows('SELECT * FROM cltk_dokumenty WHERE visible = 1 AND kategorie = ? ORDER BY poradi, id', [$kategorie]);
}

/** Archiv klubových turnajů po turnajích: [['nazev' => 'Babolat Amateur Tour', 'roky' => [2026 => [dok…], …]], …].
 *  Turnaje podle nejnovějšího ročníku (nejčerstvější nahoře), ročníky od nejnovějšího, uvnitř ročníku pořadí. */
function dokumenty_archiv(): array {
    $turnaje = [];
    foreach (data_rows('SELECT * FROM cltk_dokumenty WHERE visible = 1 AND kategorie = ? ORDER BY rok DESC, poradi, id', [DOKUMENTY_ARCHIV]) as $d) {
        if (dokument_url($d) === '') continue;
        $t = trim((string)($d['skupina'] ?? '')) ?: 'Další klubové akce';
        $turnaje[$t]['nazev'] = $t;
        $turnaje[$t]['roky'][(int)($d['rok'] ?? 0)][] = $d;
    }
    $turnaje = array_values($turnaje);
    usort($turnaje, static fn(array $a, array $b): int => [max(array_keys($b['roky'])), count($b['roky'])] <=> [max(array_keys($a['roky'])), count($a['roky'])]);
    return $turnaje;
}

/** Krátký název přílohy archivu bez turnaje a roku: „Babolat Non Profi Cup 2018 – pozvánka a pravidla soutěže“
 *  → „Pozvánka a pravidla soutěže“ (když název tvar „turnaj rok – co“ nemá, vrátí ho celý). */
function dokument_archiv_nazev(array $d): string {
    $n = trim((string)$d['nazev']);
    if (preg_match('/^.+?\s(?:19|20)\d\d\s+[–-]\s+(.+)$/u', $n, $m)) {
        return mb_strtoupper(mb_substr($m[1], 0, 1)) . mb_substr($m[1], 1);
    }
    return $n;
}

/** Pravidla hraní a rezervací kurtů (dokument kategorie provoz s „pravidl“ v názvu) – odkaz u rezervací
 *  na Ceníku kurtů a v Areálu. Null = žádný viditelný dokument s adresou. */
function dokument_pravidla_hrani(): ?array {
    foreach (dokumenty('provoz') as $d) {
        if (mb_stripos((string)$d['nazev'], 'pravidl') !== false && dokument_url($d) !== '') return $d;
    }
    return null;
}

/** Dokumenty do patičky (sloupec „Dokumenty a sítě“). */
function dokumenty_paticka(): array {
    return data_rows('SELECT * FROM cltk_dokumenty WHERE visible = 1 AND v_paticce = 1 ORDER BY poradi, id');
}

/** Dokument podle adresy stránky (dokument.php?d=stanovy). Skrytý dokument jen s $iSkryte
 *  (náhled pro přihlášeného správce). Neplatná adresa nebo neexistující dokument = null. */
function dokument_podle_slugu(string $slug, bool $iSkryte = false): ?array {
    if (!dokument_slug_platny($slug)) return null;
    return data_row('SELECT * FROM cltk_dokumenty WHERE slug = ?' . ($iSkryte ? '' : ' AND visible = 1') . ' ORDER BY visible DESC, id LIMIT 1', [$slug]);
}

function triptych(): array {
    $r = data_rows('SELECT * FROM cltk_triptych WHERE visible = 1 ORDER BY poradi, id');
    foreach ($r as &$t) $t['sety_pole'] = sety_rozloz((string)$t['sety']);
    return $r;
}

function milniky(): array {
    return data_rows('SELECT * FROM cltk_milniky WHERE visible = 1 ORDER BY rok, poradi, id');
}

function osobnosti(): array {
    return data_rows('SELECT * FROM cltk_osobnosti WHERE visible = 1 ORDER BY poradi, id');
}

/** Zlatá deska: záznamy kategorie (grandslam | cestni | mistri | oh | zasluzili | prezidenti). */
function deska(string $kategorie, ?string $skupina = null): array {
    return $skupina === null
        ? data_rows('SELECT * FROM cltk_deska_zaznamy WHERE visible = 1 AND kategorie = ? ORDER BY poradi, id', [$kategorie])
        : data_rows('SELECT * FROM cltk_deska_zaznamy WHERE visible = 1 AND kategorie = ? AND skupina = ? ORDER BY poradi, id', [$kategorie, $skupina]);
}

/** Čísla Revue (nejnovější první); 'titulky_pole' a 'obsah_pole' jsou rozbalené z JSON. */
function revue_cisla(): array {
    $r = data_rows('SELECT * FROM cltk_revue WHERE visible = 1 ORDER BY rok DESC, cislo DESC, poradi, id');
    foreach ($r as &$c) {
        $c['titulky_pole'] = json_pole((string)$c['titulky']);
        $c['obsah_pole'] = json_pole((string)$c['obsah']);
        $c['pdf'] = $c['pdf_soubor'] !== '' ? upload_url((string)$c['pdf_soubor']) : bezpecny_odkaz((string)$c['pdf_url']);
    }
    return $r;
}

/** Newslettery (nejnovější rok první); 'cs_url' a 'en_url' = nahrané PDF, jinak odkaz. */
function newslettery(): array {
    $r = data_rows('SELECT * FROM cltk_newslettery WHERE visible = 1 ORDER BY rok DESC, poradi, id');
    foreach ($r as &$n) {
        foreach (['cs', 'en'] as $j) {
            $soubor = (string)($n['pdf_' . $j . '_soubor'] ?? '');
            $n[$j . '_url'] = $soubor !== '' ? upload_url($soubor) : bezpecny_odkaz((string)($n['pdf_' . $j] ?? ''));
        }
    }
    return $r;
}

/** Vedení: vybor | kancelar | kontakt (null = vše). */
function vedeni(?string $skupina = null): array {
    return $skupina === null
        ? data_rows('SELECT * FROM cltk_vedeni WHERE visible = 1 ORDER BY skupina, poradi, id')
        : data_rows('SELECT * FROM cltk_vedeni WHERE visible = 1 AND skupina = ? ORDER BY poradi, id', [$skupina]);
}

/** CTC: fakt | klub | utkani | soutez | rodokmen (null = vše). */
function ctc(?string $typ = null): array {
    return $typ === null
        ? data_rows('SELECT * FROM cltk_ctc WHERE visible = 1 ORDER BY typ, poradi, id')
        : data_rows('SELECT * FROM cltk_ctc WHERE visible = 1 AND typ = ? ORDER BY poradi, id', [$typ]);
}

/** Tenisová škola: info | harmonogram | rozvrh | kemp. */
function skola(string $typ): array {
    return data_rows('SELECT * FROM cltk_skola WHERE visible = 1 AND typ = ? ORDER BY poradi, id', [$typ]);
}

/** Řádek rozvrhu = obsazený kurt (skupina „obsazeno“), ne trénink školy? */
function skola_rozvrh_obsazeno(array $r): bool {
    return mb_strtolower(trim((string)($r['skupina'] ?? ''))) === 'obsazeno';
}

/**
 * Rozvrhy Tenisové školy (cltk_skola, typ = rozvrh) po rozvrzích: řádky se stejným názvem (`nazev`)
 * a platností tvoří jeden rozvrh, uvnitř po kurtech (`misto`) v pořadí prvního výskytu.
 *   [['nazev' => 'Zima 2026/27', 'od' => '2026-09-29', 'do' => '2027-04-02', 'kurty' => ['Kurt 5 · antuka' => [řádky…]]], …]
 * Rozvrh, jehož platnost skončila (datum_do < $dnes), se vynechá. Řazení: začátek, pak konec platnosti
 * (krátký přechodný rozvrh před celou sezónou), rozvrhy bez data na konec.
 */
function skola_rozvrhy(?string $dnes = null): array {
    $dnes = $dnes ?? dnes();
    $v = [];
    foreach (skola('rozvrh') as $r) {
        $od = substr((string)($r['datum_od'] ?? ''), 0, 10);
        $do = substr((string)($r['datum_do'] ?? ''), 0, 10);
        if ($do !== '' && $do < $dnes) continue;
        $klic = trim((string)$r['nazev']) . '|' . $od . '|' . $do;
        if (!isset($v[$klic])) $v[$klic] = ['nazev' => trim((string)$r['nazev']), 'od' => $od, 'do' => $do, 'kurty' => []];
        $v[$klic]['kurty'][trim((string)$r['misto'])][] = $r;
    }
    $v = array_values($v);
    usort($v, static fn(array $a, array $b): int => [$a['od'] === '' ? '9999' : $a['od'], $a['do'] === '' ? '9999' : $a['do']]
                                                <=> [$b['od'] === '' ? '9999' : $b['od'], $b['do'] === '' ? '9999' : $b['do']]);
    return $v;
}

/* ---------- Plán areálu (mapa.js) – areal.php i úvodní stránka ---------- */

/** Datum z textu „d. m. rrrr“ → RRRR-MM-DD (nebo null). */
function areal_ymd(int $r, int $m, int $d): ?string {
    return checkdate($m, $d, $r) ? sprintf('%04d-%02d-%02d', $r, $m, $d) : null;
}

/** Období z textu: „16.–22. 8. 2026“, „28. 9. – 4. 10. 2026“, „28. 9. 2026 – 4. 4. 2027“, „5. 10. 2026“ → [od, do]. */
function areal_obdobi(string $t): array {
    $t = str_replace(["\u{00A0}", '&nbsp;'], ' ', $t);
    $p = '\s*[–-]\s*';
    if (preg_match('/(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})' . $p . '(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})/u', $t, $m)) {
        return [areal_ymd((int)$m[3], (int)$m[2], (int)$m[1]), areal_ymd((int)$m[6], (int)$m[5], (int)$m[4])];
    }
    if (preg_match('/(\d{1,2})\.\s*(\d{1,2})\.' . $p . '(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})/u', $t, $m)) {
        return [areal_ymd((int)$m[5], (int)$m[2], (int)$m[1]), areal_ymd((int)$m[5], (int)$m[4], (int)$m[3])];
    }
    if (preg_match('/(\d{1,2})\.' . $p . '(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})/u', $t, $m)) {
        return [areal_ymd((int)$m[4], (int)$m[3], (int)$m[1]), areal_ymd((int)$m[4], (int)$m[3], (int)$m[2])];
    }
    if (preg_match('/(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})/u', $t, $m)) {
        $x = areal_ymd((int)$m[3], (int)$m[2], (int)$m[1]);
        return [$x, $x];
    }
    return [null, null];
}

/** Sezóna podle zimního ceníku (období „28. 9. 2026 – 4. 4. 2027“): 'zima' / 'leto'. Stejné pravidlo počítá cenik.js. */
function areal_sezona(?array $zima): string {
    if (!$zima || !$zima['sekce']) return 'leto';
    [$od, $do] = areal_obdobi((string)$zima['list']['obdobi']);
    if ($od === null || $do === null || $od === $do) return 'leto';
    return dnes() >= $od && dnes() <= $do ? 'zima' : 'leto';
}

/** Ceník pro JavaScript – jen veřejné sloupce. */
function areal_cenik_js(?array $c): ?array {
    if (!$c) return null;
    $l = $c['list'];
    return [
        'list'  => ['nazev' => (string)$l['nazev'], 'obdobi' => (string)$l['obdobi'], 'poznamka_dole' => (string)$l['poznamka_dole']],
        'sekce' => array_map(fn($s) => [
            'id' => (int)$s['id'], 'nazev' => (string)$s['nazev'], 'popis' => (string)$s['popis'],
            'radky' => array_map(fn($r) => [
                'id' => (int)$r['id'], 'nazev' => (string)$r['nazev'], 'poznamka' => (string)$r['poznamka'],
                'cena' => (string)$r['cena'], 'cena_clen' => (string)$r['cena_clen'],
                'cena_sezona' => (string)$r['cena_sezona'], 'cena_sezona_clen' => (string)$r['cena_sezona_clen'],
            ], $s['radky']),
        ], $c['sekce']),
    ];
}

/** Data pro mapa.js: ceníky kurtů (zimní haly, ceny), služby s polohou podle kotvy, rezervace.
    $sluzbyUrl = kam vede „Podrobnosti“ u služby ('' = kotva na téže stránce, jinak např. url('areal.php')). */
function mapa_data(?array $cenikLeto = null, ?array $cenikZima = null, ?array $sluzby = null, string $sluzbyUrl = ''): array {
    $cenikLeto = $cenikLeto ?? cenik('kurty-leto');
    $cenikZima = $cenikZima ?? cenik('kurty-zima');
    $sluzby = $sluzby ?? sluzby();
    return [
        'ceniky'     => ['leto' => areal_cenik_js($cenikLeto), 'zima' => areal_cenik_js($cenikZima), 'dnes' => dnes(), 'ladeni' => sablona_ladeni_dnes() !== ''],
        'sluzby'     => array_map(fn($s) => ['kotva' => (string)$s['kotva'], 'nazev' => (string)$s['nazev'], 'perex' => (string)$s['perex'], 'casy' => (string)$s['casy']], $sluzby),
        'rezervace'  => rezervace_url(),
        'cenik_url'  => url('cenik-kurtu.php'),
        'sluzby_url' => $sluzbyUrl,
    ];
}

