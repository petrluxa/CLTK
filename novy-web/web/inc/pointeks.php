<?php
/* VÝSLEDKY HRÁČŮ ZE SLUŽBY POINTEKS
   Rozhodnutí Petra 15. 10. 2026: na webu jsou JEN výsledky z Pointeks (úvodní stránka, stránka
   vysledky.php, Závodní tenis). Ruční modul „Výsledky hráčů“ (cltk_vysledky) se na webu neukazuje.

   Jak to funguje (smlouva odesílatele = brief Pointeks „cltk-results-feed-brief.md“, 15. 10. 2026):
     – po každém dohraném zápase hráče klubu pošle Pointeks jeden POST s JSON na …/pointeks/feed
       (soubor pointeks-feed.php; web/.htaccess a lokálně nastroje/router.php adresu přepíší
       BEZ přesměrování – odesílatel přesměrování nesleduje a bere je jako chybu),
     – X-Pointeks-Signature = 'sha256=' + hex HMAC-SHA256 SUROVÉHO těla klíčem POINTEKS_SECRET
       (inc/config.php, na serveru cltk-config.php) – nesouhlasí → 401, nic se neuloží,
     – „id“ v PODEPSANÉM těle = jedinečné id výsledku a klíč uložení – každé se uloží jen jednou,
       opakované doručení dostane zase 204 a nic nezmění (hlavička X-Pointeks-Delivery nese stejnou
       hodnotu, ale podpis ji nekryje – slouží jen k porovnání v protokolu, viz pointeks_dorucenka()),
     – event „ping“ = zkouška spojení (204, nic se neukládá), „match.result“ = výsledek,
     – odpověď 2xx do 10 s; jinak Pointeks posílá znovu (12× za ~25 hodin), proto chyba databáze = 500,
     – jedna čtyřhra dvou hráčů klubu = dvě doručení (každý hráč své) → na webu jeden řádek „A / B“
       (sloupec skupina, viz pointeks_skupina()).
   Data: tabulka cltk_pointeks_vysledky (sql/SCHEMA.md), protokol příjmu data/pointeks.log
   (čas, kód, událost, id, důvod – nikdy tělo, hlavičky, klíč ani podpis).
   Výpis na webu: pointeks_seznam() + pointeks_tabule_html() (inc/sablona/komponenty.php),
   správa: admin/pointeks.php. */

const POINTEKS_TABULKA = 'cltk_pointeks_vysledky';
const POINTEKS_MAX_BAJTU = 262144;          // tělo nad 256 kB = 413
const POINTEKS_LOG_MAX = 524288;            // protokol nad 512 kB se přejmenuje na pointeks-stare.log
const POINTEKS_STAV_DOHRANO = 'dohrano';
const POINTEKS_STAV_ZIVE = 'zive';
const POINTEKS_KREDIT_VYCHOZI = 'https://pointeks.com';
const POINTEKS_NA_STRANKU = 30;             // stránka vysledky.php
const POINTEKS_NA_UVODU = 10;               // úvodní stránka
/** Sloupce pro výpis (bez raw_json – celé tělo se na webu nikdy nepoužívá). */
const POINTEKS_SLOUPCE = 'id, delivery_id, event, stav, skupina, player_name, player_slug, partner_name, partner_country, '
    . 'opponents_json, result, outcome, score, sets_json, discipline, round, qualifying, tournament_name, tournament_category, '
    . 'age_category, tournament_city, tournament_country, tournament_surface, tournament_start, completed_at, url, visible, '
    . 'received_at, updated_at';

/* ==================================================================
   PŘÍJEM (pointeks-feed.php)
   ================================================================== */

/** Sdílený tajný klíč (prázdný = není nastavený). Nikdy ho nevypisovat ani nelogovat. */
function pointeks_tajny_klic(): string {
    $k = defined('POINTEKS_SECRET') ? (string)POINTEKS_SECRET : '';
    return trim($k) === '' ? '' : $k;
}

/** Je tajný klíč nastavený? (pro administraci – jen ano/ne) */
function pointeks_ma_klic(): bool {
    return pointeks_tajny_klic() !== '';
}

/**
 * Celý příjem jednoho požadavku. Odpověď je jen stavový kód (bez těla), úspěch = 204.
 *   405 + Allow: POST  jiná metoda než POST
 *   413                tělo nad 256 kB
 *   500                není nastavený POINTEKS_SECRET (nic se neuloží)
 *   401                chybí podpis nebo nesouhlasí (nic se neuloží)
 *   400                tělo není objekt JSON, výsledek bez platného „id“ v těle nebo bez jména hráče
 *   204                ping, uložený výsledek, opakované doručení, neznámá událost (jen do protokolu)
 *                      (výsledek se ukládá jednou podle „id“ z podepsaného těla, ne podle hlavičky)
 *   500                chyba databáze (Pointeks to zkusí znovu)
 * Bez relace, bez cookies, Cache-Control: no-store. Každý požadavek = řádek v data/pointeks.log.
 */
function pointeks_prijmi(): never {
    ignore_user_abort(true);                 // přerušené spojení nesmí zápis zastavit uprostřed
    @set_time_limit(20);
    ini_set('default_mimetype', '');         // PHP k odpovědi nepřidá Content-Type: text/html
    $udalost = '';
    $protokolId = '';                        // id do protokolu (id z těla, bez něj hlavička) – NE klíč uložení
    $konec = static function (int $kod, string $duvod) use (&$udalost, &$protokolId): never {
        pointeks_log($kod, $udalost, $protokolId, $duvod);
        pointeks_odpoved($kod);
    };

    $metoda = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? ''));
    if ($metoda !== 'POST') {
        if (!headers_sent()) header('Allow: POST');
        $konec(405, 'metoda ' . (preg_match('/^[A-Z]{1,10}$/', $metoda) ? $metoda : '?'));
    }
    $delka = (string)($_SERVER['CONTENT_LENGTH'] ?? '');
    if ($delka !== '' && (!ctype_digit($delka) || strlen($delka) > 12 || (int)$delka > POINTEKS_MAX_BAJTU)) {
        $konec(413, 'tělo nad 256 kB');
    }
    $raw = @file_get_contents('php://input', false, null, 0, POINTEKS_MAX_BAJTU + 1);   // surové tělo, jen jednou
    $raw = is_string($raw) ? $raw : '';
    if (strlen($raw) > POINTEKS_MAX_BAJTU) $konec(413, 'tělo nad 256 kB');

    $klic = pointeks_tajny_klic();
    if ($klic === '') $konec(500, 'není nastavený POINTEKS_SECRET – nic neuloženo');
    $podpis = pointeks_hlavicka('X-Pointeks-Signature');
    if (trim($podpis) === '') $konec(401, 'chybí podpis');
    if (!hash_equals('sha256=' . hash_hmac('sha256', $raw, $klic), $podpis)) $konec(401, 'podpis nesouhlasí');

    $t = ltrim($raw);
    $data = $t !== '' && $t[0] === '{' ? json_decode($raw, true, 32) : null;
    if (!is_array($data)) $konec(400, 'tělo není objekt JSON');

    $u = $data['event'] ?? null;
    $udalost = is_string($u) && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,39}$/', $u) ? $u : (is_string($u) ? '?' : '');
    $dorucenka = pointeks_dorucenka($data);                    // klíč uložení = „id“ z podepsaného těla
    $hlavicka = trim(pointeks_hlavicka('X-Pointeks-Delivery'));
    $protokolId = $dorucenka !== '' ? $dorucenka : (pointeks_id_platne($hlavicka) ? $hlavicka : '');
    /* Hlavička se od id z těla liší (podle briefu se to nestane) → výsledek se uloží pod PODEPSANÝM id
       a do protokolu se to poznamená. Přehraný požadavek s vymyšlenou hlavičkou tak nic nezdvojí. */
    $jinaHlavicka = $dorucenka !== '' && $hlavicka !== '' && $hlavicka !== $dorucenka
        ? ' (hlavička X-Pointeks-Delivery se liší od id – platí id z těla)' : '';

    if ($udalost === 'ping') $konec(204, 'ping – spojení v pořádku, nic neuloženo');

    if ($udalost === 'match.result') {
        if ($dorucenka === '') $konec(400, 'výsledek bez platného id v těle');
        $radek = pointeks_radek_z_dat($data);
        if ($radek['player_name'] === '') $konec(400, 'výsledek bez jména hráče');
        try {
            $co = pointeks_uloz($dorucenka, $radek, $raw, POINTEKS_STAV_DOHRANO);
        } catch (Throwable $e) {
            error_log('[pointeks] uložení výsledku ' . $dorucenka . ': ' . $e->getMessage());
            $konec(500, 'chyba databáze – Pointeks to zkusí znovu');
        }
        $konec(204, match ($co) {
            'novy'     => 'uloženo',
            'nahrazen' => 'konečný výsledek nahradil živý zápas',
            default    => 'opakované doručení – beze změny',
        } . $jinaHlavicka);
    }

    /* ROZŠÍŘENÍ – ŽIVÉ ZÁPASY. Pointeks zatím neurčil, jak je bude posílat. Až to bude známé,
       přibude tu větev pro jejich událost (např. 'match.live'), která zavolá
           pointeks_uloz($dorucenka, pointeks_radek_z_dat($data), $raw, POINTEKS_STAV_ZIVE);
       Klíčem je i tady $dorucenka = id z PODEPSANÉHO těla, nikdy hlavička (přehraný požadavek
       s jinou hlavičkou by jinak přepsal cizí řádek). Stejné id se bude opakovaně PŘEPISOVAT
       (průběžné sety, stav 'zive') a konečný 'match.result' se stejným id živý řádek nahradí –
       obojí už umí pointeks_uloz(). Web živé řádky ukazuje (červená tečka „živě“, řadí se první,
       rozehraný poslední set se nezvýrazňuje). S příjmem doplnit i pojistku proti „věčně živým“
       řádkům (zápas přerušený a nedohraný – konečný výsledek nikdy nepřijde): např. řádek 'zive'
       bez změny déle než N hodin přestane být první a ztratí „živě“ (pointeks_seznam(),
       pointeks_zapas()). Do té doby: každá jiná událost = 204, nic se neuloží a v protokolu
       je vidět, co a kdy přišlo. */
    $konec(204, $udalost === '' ? 'bez události – nic neuloženo' : 'neznámá událost – nic neuloženo');
}

/** Odpověď bez těla a konec. Nic se necachuje, žádná cookie. */
function pointeks_odpoved(int $kod): never {
    if (!headers_sent()) {
        http_response_code($kod);
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        header_remove('Set-Cookie');
    }
    exit;
}

/** Hlavička požadavku (X-Pointeks-…) – z $_SERVER, po přepsání adresy i s předponou REDIRECT_,
 *  nouzově z getallheaders(). Chybí = ''. */
function pointeks_hlavicka(string $nazev): string {
    $klic = 'HTTP_' . strtoupper(str_replace('-', '_', $nazev));
    foreach ([$klic, 'REDIRECT_' . $klic] as $k) {
        if (isset($_SERVER[$k]) && is_string($_SERVER[$k])) return $_SERVER[$k];
    }
    if (function_exists('getallheaders')) {
        foreach ((array)getallheaders() as $k => $v) {
            if (is_string($k) && strcasecmp($k, $nazev) === 0 && is_string($v)) return $v;
        }
    }
    return '';
}

/**
 * Id výsledku = klíč uložení: „id“ z PODEPSANÉHO těla (brief: „Use it as the storage key“). Hlavička
 * X-Pointeks-Delivery nese podle briefu stejnou hodnotu, ale podpis ji nekryje – kdo by zachytil jeden
 * pravý požadavek, mohl by tytéž bajty s tímtéž podpisem poslat znovu s jinou hlavičkou, a kdyby byla
 * klíčem hlavička, výsledek by se uložil víckrát. Proto jen id z těla; hlavička se jen porovná (protokol).
 * Jen rozumná délka a znaky (pointeks_id_platne()), jinak ''.
 */
function pointeks_dorucenka(array $data): string {
    $b = $data['id'] ?? null;
    $id = is_string($b) ? trim($b) : (is_int($b) ? (string)$b : '');
    return pointeks_id_platne($id) ? $id : '';
}

/** Rozumné id výsledku: písmena bez diakritiky, číslice a . _ : - (na začátku písmeno / číslice), nejvýš 100 znaků. */
function pointeks_id_platne(string $id): bool {
    return (bool)preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,99}$/D', $id);
}

/** Řádek protokolu data/pointeks.log: čas | kód | událost | id | důvod. Nic jiného (ani tělo, ani hlavičky, klíč
 *  či podpis); id je „id“ z těla, a když chybí, ověřená hodnota hlavičky X-Pointeks-Delivery (pointeks_id_platne()). */
function pointeks_log(int $kod, string $udalost, string $dorucenka, string $duvod): void {
    $soubor = DATA_DIR . '/pointeks.log';
    if (@filesize($soubor) > POINTEKS_LOG_MAX) @rename($soubor, DATA_DIR . '/pointeks-stare.log');   // předchozí starý se přepíše
    $cist = static fn(string $s, int $max): string => mb_substr(trim((string)preg_replace('/[\x00-\x1F\x7F|]+/', ' ', $s)), 0, $max);
    $radek = date('Y-m-d H:i:s') . ' | ' . $kod . ' | ' . ($udalost !== '' ? $cist($udalost, 40) : '-')
           . ' | ' . ($dorucenka !== '' ? $cist($dorucenka, 100) : '-') . ' | ' . $cist($duvod, 140) . "\n";
    @file_put_contents($soubor, $radek, FILE_APPEND | LOCK_EX);
}

/** Posledních $n řádků protokolu (nejnovější poslední) – pro administraci. */
function pointeks_log_konec(int $n = 20): array {
    $soubor = DATA_DIR . '/pointeks.log';
    $velikost = (int)@filesize($soubor);
    if ($velikost <= 0) return [];
    $f = @fopen($soubor, 'rb');
    if (!$f) return [];
    $kus = min($velikost, 65536);
    fseek($f, -$kus, SEEK_END);
    $text = (string)fread($f, $kus);
    fclose($f);
    $radky = preg_split('/\r?\n/', rtrim($text, "\r\n"));
    if ($kus < $velikost) array_shift($radky);        // první řádek kusu může být useknutý
    return array_slice(array_values(array_filter($radky, static fn($r) => trim((string)$r) !== '')), -max(1, $n));
}

/* ---------- převod těla na sloupce (všechno je nedůvěryhodný text) ---------- */

/** Text z JSON: jen řetězec / číslo, bez řídicích a směrových znaků, oříznutý, nejvýš $max znaků.
 *  Jinak beze změny – jména se ukazují přesně, jak přišla (brief, pravidlo 7). */
function pointeks_text($v, int $max): string {
    if (!is_string($v) && !is_int($v) && !is_float($v)) return '';
    $s = (string)preg_replace('/[\x{0000}-\x{001F}\x{007F}-\x{009F}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]+/u', ' ', (string)$v);
    return mb_substr(trim($s), 0, $max);
}

/** Kód země (CZE, SVK …) velkými písmeny, jinak ''. */
function pointeks_zeme($v): string {
    return is_string($v) && preg_match('/^[A-Za-z]{2,3}$/', trim($v)) ? strtoupper(trim($v)) : '';
}

/** Jedna z povolených hodnot (WIN/LOSS, SINGLES/DOUBLES), jinak ''. */
function pointeks_volba($v, array $povolene): string {
    $s = is_string($v) ? strtoupper(trim($v)) : '';
    return in_array($s, $povolene, true) ? $s : '';
}

/** Kód z velkých písmen (outcome FINISHED/RETIRED/WALKOVER, povrch CLAY …) – i budoucí hodnoty, jinak ''. */
function pointeks_kod($v): string {
    $s = is_string($v) ? strtoupper(trim($v)) : '';
    return preg_match('/^[A-Z][A-Z_]{0,11}$/', $s) ? $s : '';
}

/** RRRR-MM-DD (platné datum), jinak null. */
function pointeks_datum($v): ?string {
    if (!is_string($v) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim($v), $m)) return null;
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? trim($v) : null;
}

/** Čas ISO 8601 („2026-10-11T13:58:00.000Z“) → 'RRRR-MM-DD HH:MM:SS' v UTC, jinak null. Bez zóny = UTC. */
function pointeks_cas_utc($v): ?string {
    if (!is_string($v)) return null;
    $s = trim($v);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2})(?:\.\d{1,9})?)?(Z|[+-]\d{2}:?\d{2})?$/i', $s, $m)) return null;
    if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1]) || (int)$m[4] > 23 || (int)$m[5] > 59 || (int)($m[6] ?? 0) > 59) return null;
    if ((int)$m[1] < 2000 || (int)$m[1] > 2100) return null;
    try {
        $t = new DateTimeImmutable($s, new DateTimeZone('UTC'));
    } catch (Exception $e) {
        return null;
    }
    return $t->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

/** Počet gemů / bodů 0–99, jinak null. */
function pointeks_cislo($v): ?int {
    if (is_int($v)) return $v >= 0 && $v <= 99 ? $v : null;
    if (is_string($v) && preg_match('/^\d{1,2}$/', trim($v))) return (int)trim($v);
    return null;
}

/** Sety z JSON → [['player' => 6, 'opponent' => 3, 'tiebreak' => ['player' => 7, 'opponent' => 4]], …] (nejvýš 7). */
function pointeks_sety($v): array {
    if (!is_array($v)) return [];
    $sety = [];
    foreach (array_slice(array_values($v), 0, 7) as $s) {
        if (!is_array($s)) continue;
        $h = pointeks_cislo($s['player'] ?? null);
        $o = pointeks_cislo($s['opponent'] ?? null);
        if ($h === null || $o === null) continue;
        $set = ['player' => $h, 'opponent' => $o];
        if (is_array($s['tiebreak'] ?? null)) {
            $th = pointeks_cislo($s['tiebreak']['player'] ?? null);
            $to = pointeks_cislo($s['tiebreak']['opponent'] ?? null);
            if ($th !== null && $to !== null) $set['tiebreak'] = ['player' => $th, 'opponent' => $to];
        }
        $sety[] = $set;
    }
    return $sety;
}

/** Soupeři → [['name' => …, 'country' => 'SVK'], …] (jen se jménem, nejvýš 4). */
function pointeks_souperi($v): array {
    if (!is_array($v)) return [];
    $souperi = [];
    foreach (array_values($v) as $o) {
        if (!is_array($o)) continue;
        $jmeno = pointeks_text($o['name'] ?? null, 160);
        if ($jmeno === '') continue;
        $souperi[] = ['name' => $jmeno, 'country' => pointeks_zeme($o['country'] ?? null)];
        if (count($souperi) >= 4) break;
    }
    return $souperi;
}

/** Odkaz pro řádek s poděkováním: jen http(s), nejvýš 255 znaků, jinak ''. */
function pointeks_url($v): string {
    if (!is_string($v)) return '';
    $u = trim($v);
    if ($u === '' || strlen($u) > 255 || !preg_match('~^https?://[^\s"\'<>\\\\`]+$~i', $u) || !filter_var($u, FILTER_VALIDATE_URL)) return '';
    return bezpecny_odkaz($u) === $u ? $u : '';
}

/** Ano/ne z JSON (true, 1, "1", "true"). */
function pointeks_ano($v): int {
    return $v === true || $v === 1 || $v === '1' || (is_string($v) && strtolower(trim($v)) === 'true') ? 1 : 0;
}

/** Tělo „match.result“ → sloupce tabulky (bez delivery_id, stavu a časů zápisu). Neznámá pole se ignorují. */
function pointeks_radek_z_dat(array $d): array {
    $hrac = is_array($d['player'] ?? null) ? $d['player'] : [];
    $partner = is_array($d['partner'] ?? null) ? $d['partner'] : [];
    $turnaj = is_array($d['tournament'] ?? null) ? $d['tournament'] : [];
    $jmeno = pointeks_text($hrac['name'] ?? null, 160);
    $slug = pointeks_text($d['playerSlug'] ?? null, 120);
    if ($slug !== '' && !preg_match('/^[a-z0-9][a-z0-9-]{0,119}$/', $slug)) $slug = mb_substr(slugify($slug), 0, 120);
    if ($slug === '' && $jmeno !== '') $slug = mb_substr(slugify($jmeno), 0, 120);
    $souperi = pointeks_souperi($d['opponents'] ?? null);
    $partnerJmeno = pointeks_text($partner['name'] ?? null, 160);
    $disciplina = pointeks_volba($d['discipline'] ?? null, ['SINGLES', 'DOUBLES']);
    if ($disciplina === '') $disciplina = $partnerJmeno !== '' || count($souperi) > 1 ? 'DOUBLES' : 'SINGLES';
    $vek = pointeks_text($turnaj['ageCategory'] ?? null, 20);
    return [
        'player_name'         => $jmeno,
        'player_slug'         => $slug,
        'partner_name'        => $partnerJmeno,
        'partner_country'     => pointeks_zeme($partner['country'] ?? null),
        'opponents_json'      => json_ulozit($souperi),
        'result'              => pointeks_volba($d['result'] ?? null, ['WIN', 'LOSS']),
        'outcome'             => pointeks_kod($d['outcome'] ?? null),
        'score'               => pointeks_text($d['score'] ?? null, 120),
        'sets_json'           => json_ulozit(pointeks_sety($d['sets'] ?? null)),
        'discipline'          => $disciplina,
        'round'               => pointeks_text($d['round'] ?? null, 60),
        'qualifying'          => pointeks_ano($d['qualifying'] ?? null),
        'tournament_name'     => pointeks_text($turnaj['name'] ?? null, 200),
        'tournament_category' => pointeks_text($turnaj['category'] ?? null, 120),
        'age_category'        => $vek !== '' ? $vek : null,
        'tournament_city'     => pointeks_text($turnaj['city'] ?? null, 120),
        'tournament_country'  => pointeks_zeme($turnaj['country'] ?? null),
        'tournament_surface'  => pointeks_kod($turnaj['surface'] ?? null),
        'tournament_start'    => pointeks_datum($turnaj['startDate'] ?? null),
        'completed_at'        => pointeks_cas_utc($d['completedAt'] ?? null),
        'url'                 => pointeks_url($d['url'] ?? null),
    ];
}

/**
 * Klíč skupiny = jeden řádek na webu. Čtyřhra: oba hráči klubu jedné dvojice dostanou od Pointeks
 * každý své doručení – spojí se, když sedí konec zápasu, turnaj, kolo, disciplína, soupeři a skóre.
 * Dvouhra (a čtyřhra bez dost údajů) = vlastní skupina podle id doručení.
 */
function pointeks_skupina(array $r, string $dorucenka): string {
    $souperi = [];
    foreach (json_pole((string)($r['opponents_json'] ?? '')) as $o) {
        if (is_array($o) && trim((string)($o['name'] ?? '')) !== '') $souperi[] = mb_strtolower(trim((string)$o['name']));
    }
    $dost = $souperi && ((string)($r['completed_at'] ?? '') !== '' || trim((string)($r['tournament_name'] ?? '')) !== '');
    if (($r['discipline'] ?? '') !== 'DOUBLES' || !$dost) return sha1('id|' . $dorucenka);
    sort($souperi, SORT_STRING);
    return sha1(json_encode(['DOUBLES', (string)($r['completed_at'] ?? ''), mb_strtolower(trim((string)($r['tournament_name'] ?? ''))),
        mb_strtolower(trim((string)($r['round'] ?? ''))), $souperi, trim((string)($r['score'] ?? ''))], JSON_UNESCAPED_UNICODE));
}

/**
 * Uloží výsledek jednou podle delivery_id. Vrací 'novy' | 'opakovani' | 'nahrazen' | 'prubeh'.
 *   – id ještě není → nový řádek ($stav 'dohrano', u budoucích živých zápasů 'zive'),
 *   – řádek je ŽIVÝ → přepíše se (průběžný stav i konečný výsledek; viditelnost zůstane),
 *   – řádek je DOHRANÝ → nic se nemění (opakované doručení, pozdní živá zpráva).
 * Souběžné doručení téhož id zachytí unikátní klíč (druhý zápis = opakování).
 * Nové doručení čtyřhry, jejíž druhý záznam (spoluhráč z klubu) správce skryl, se uloží taky skryté –
 * skrytí platí pro celý zápas (admin/pointeks.php přepíná celou skupinu, výpis skrytou skupinu vynechá).
 */
function pointeks_uloz(string $dorucenka, array $radek, string $raw, string $stav = POINTEKS_STAV_DOHRANO): string {
    $stav = $stav === POINTEKS_STAV_ZIVE ? POINTEKS_STAV_ZIVE : POINTEKS_STAV_DOHRANO;
    $radek['skupina'] = pointeks_skupina($radek, $dorucenka);
    $ted = gmdate('Y-m-d H:i:s');
    $udalost = $stav === POINTEKS_STAV_ZIVE ? 'match.live' : 'match.result';
    $byl = row('SELECT id, stav FROM cltk_pointeks_vysledky WHERE delivery_id = ?', [$dorucenka]);
    if ($byl) {
        if ((string)$byl['stav'] !== POINTEKS_STAV_ZIVE) return 'opakovani';
        db_update(POINTEKS_TABULKA, (int)$byl['id'], $radek + ['event' => $udalost, 'stav' => $stav, 'raw_json' => $raw, 'updated_at' => $ted]);
        return $stav === POINTEKS_STAV_ZIVE ? 'prubeh' : 'nahrazen';
    }
    $viditelny = 1;
    if (($radek['discipline'] ?? '') === 'DOUBLES'
        && (int)val('SELECT COUNT(*) FROM cltk_pointeks_vysledky WHERE skupina = ? AND visible = 0', [$radek['skupina']]) > 0) {
        $viditelny = 0;
    }
    try {
        db_insert(POINTEKS_TABULKA, ['delivery_id' => $dorucenka, 'event' => $udalost, 'stav' => $stav] + $radek
            + ['raw_json' => $raw, 'visible' => $viditelny, 'received_at' => $ted, 'updated_at' => $ted]);
    } catch (PDOException $e) {
        if ((string)$e->getCode() === '23000' && row('SELECT id FROM cltk_pointeks_vysledky WHERE delivery_id = ?', [$dorucenka])) {
            return 'opakovani';
        }
        throw $e;
    }
    return 'novy';
}

/* ==================================================================
   VÝPIS NA WEBU
   ================================================================== */

/** Podmínka „je na webu“: viditelný záznam ze skupiny, ve které správce nic neskryl. Skrytí jednoho
 *  záznamu spojené čtyřhry tak schová celý zápas (i jméno skrytého hráče, které by jinak zůstalo
 *  v poli partner druhého záznamu). Poddotaz jen nad cltk_ – projde pojistkou (sql_pojistka). */
const POINTEKS_NA_WEBU_SQL = 'visible = 1 AND skupina NOT IN (SELECT skupina FROM cltk_pointeks_vysledky WHERE visible = 0)';

/** Podmínka WHERE podle filtru: 'hrac' (slug), 'kategorie' 'juniori' (ageCategory vyplněná) | 'dospeli' (null),
 *  'i_skryte' (admin) – jinak jen záznamy na webu (POINTEKS_NA_WEBU_SQL). */
function pointeks_filtr_sql(array $f): array {
    $kde = empty($f['i_skryte']) ? [POINTEKS_NA_WEBU_SQL] : ['1 = 1'];
    $p = [];
    $hrac = (string)($f['hrac'] ?? '');
    if ($hrac !== '') { $kde[] = 'player_slug = ?'; $p[] = $hrac; }
    $kat = (string)($f['kategorie'] ?? '');
    if ($kat === 'juniori') $kde[] = 'age_category IS NOT NULL';
    elseif ($kat === 'dospeli') $kde[] = 'age_category IS NULL';
    return [implode(' AND ', $kde), $p];
}

/**
 * Výsledky pro web: zápasy (skupiny – spojená čtyřhra je jeden zápas) od nejnovějšího podle konce
 * zápasu (completed_at, když chybí, čas přijetí), živé zápasy první, jen viditelné (skupina se skrytým
 * záznamem se vynechá celá – POINTEKS_NA_WEBU_SQL).
 *   $f: 'hrac', 'kategorie' (viz pointeks_filtr_sql), 'limit' (výchozí 30), 'strana' (od 1)
 * Vrací ['zapasy' => [pointeks_zapas()…], 'celkem' => počet zápasů, 'stran' => n, 'strana' => n].
 * Chyba databáze web neshodí (zapíše se do data/chyby.log, vrátí prázdný výsledek).
 */
function pointeks_seznam(array $f = []): array {
    $limit = max(1, min(100, (int)($f['limit'] ?? POINTEKS_NA_STRANKU)));
    $prazdne = ['zapasy' => [], 'celkem' => 0, 'stran' => 0, 'strana' => 1];
    [$kde, $p] = pointeks_filtr_sql($f);
    try {
        $celkem = (int)val('SELECT COUNT(DISTINCT skupina) FROM cltk_pointeks_vysledky WHERE ' . $kde, $p);
    } catch (Throwable $e) {
        pointeks_chyba_db($e, 'výpis');
        return $prazdne;
    }
    if ($celkem === 0) return $prazdne;
    $stran = (int)ceil($celkem / $limit);
    $strana = max(1, min($stran, (int)($f['strana'] ?? 1)));
    $skupiny = data_rows("SELECT skupina, MAX(CASE WHEN stav = 'zive' THEN 1 ELSE 0 END) AS zive,
                                 MAX(COALESCE(completed_at, received_at)) AS cas, MAX(id) AS posledni
                            FROM cltk_pointeks_vysledky WHERE " . $kde . '
                        GROUP BY skupina
                        ORDER BY zive DESC, cas DESC, posledni DESC
                           LIMIT ' . $limit . ' OFFSET ' . (($strana - 1) * $limit), $p);
    $klice = array_values(array_unique(array_map(static fn(array $s): string => (string)$s['skupina'], $skupiny)));
    if (!$klice) return ['zapasy' => [], 'celkem' => $celkem, 'stran' => $stran, 'strana' => $strana];
    /* všechny viditelné řádky skupin (i spoluhráče, když filtr ukazuje jen jednoho hráče) */
    $radky = data_rows('SELECT ' . POINTEKS_SLOUPCE . ' FROM cltk_pointeks_vysledky WHERE ' . (empty($f['i_skryte']) ? 'visible = 1 AND ' : '')
                     . 'skupina IN (' . implode(', ', array_fill(0, count($klice), '?')) . ') ORDER BY id', $klice);
    $podle = [];
    foreach ($radky as $r) $podle[(string)$r['skupina']][] = $r;
    $zapasy = [];
    foreach ($klice as $k) {
        if (!empty($podle[$k])) $zapasy[] = pointeks_zapas($podle[$k]);
    }
    return ['zapasy' => $zapasy, 'celkem' => $celkem, 'stran' => $stran, 'strana' => $strana];
}

/** Hráči klubu, kteří mají výsledek: [slug => jméno], seřazeno podle jména (česky). $iSkryte = i skryté (admin). */
function pointeks_hraci(bool $iSkryte = false): array {
    try {
        $radky = rows('SELECT player_slug, MAX(player_name) AS jmeno FROM cltk_pointeks_vysledky WHERE '
                      . ($iSkryte ? '' : POINTEKS_NA_WEBU_SQL . ' AND ') . "player_slug <> '' GROUP BY player_slug");
    } catch (Throwable $e) {
        pointeks_chyba_db($e, 'hráči');
        return [];
    }
    $v = [];
    foreach ($radky as $r) $v[(string)$r['player_slug']] = (string)$r['jmeno'];
    uasort($v, 'pointeks_porovnej_jmena');
    return $v;
}

/** Je tabulka výsledků v databázi? (na serveru ji zakládá migrace 2026-10-15-pointeks.php) */
function pointeks_tabulka_existuje(): bool {
    static $ano = null;
    if ($ano === null) {
        try {
            $ano = in_array(POINTEKS_TABULKA, db_tabulky(), true);
        } catch (Throwable $e) {
            $ano = false;
        }
    }
    return $ano;
}

/** Chyba databáze při výpisu: do data/chyby.log – kromě chybějící tabulky (migrace ještě neproběhla,
 *  web se pak tváří, že výsledky nejsou; příjem v tu chvíli odpovídá 500 a do protokolu chyb zapíše). */
function pointeks_chyba_db(Throwable $e, string $kde): void {
    if (pointeks_tabulka_existuje()) error_log('[pointeks] ' . $kde . ': ' . $e->getMessage());
}

/** Řazení jmen po česku (Collator, když je k dispozici, jinak bez diakritiky). */
function pointeks_porovnej_jmena(string $a, string $b): int {
    static $c = null;
    if ($c === null) $c = class_exists('Collator') ? new Collator('cs_CZ') : false;
    if ($c) return (int)$c->compare($a, $b);
    return strcmp(slugify($a), slugify($b)) ?: strcmp($a, $b);
}

/** Čas posledního přijatého výsledku (Praha) nebo null. */
function pointeks_posledni_prijem(): ?DateTimeImmutable {
    try {
        return pointeks_cas_praha((string)val('SELECT MAX(received_at) FROM cltk_pointeks_vysledky'));
    } catch (Throwable $e) {
        pointeks_chyba_db($e, 'poslední příjem');
        return null;
    }
}

/** UTC 'RRRR-MM-DD HH:MM:SS' → čas v Praze (SEČ / SELČ podle data), jinak null. */
function pointeks_cas_praha(?string $utc): ?DateTimeImmutable {
    $utc = trim((string)$utc);
    if ($utc === '' || !preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $utc)) return null;
    try {
        return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Prague'));
    } catch (Exception $e) {
        return null;
    }
}

/** Kolo po česku: Final → finále, Semi-final → semifinále, Quarter-final → čtvrtfinále, R16 → osmifinále;
 *  ostatní (R32, „1. kolo“ …) zůstane, jak přišlo. */
function pointeks_kolo(string $kolo): string {
    return match (pointeks_kolo_klic($kolo)) {
        'final', 'f'                => 'finále',
        'semifinal', 'sf'           => 'semifinále',
        'quarterfinal', 'qf'        => 'čtvrtfinále',
        'r16', 'roundof16', 'last16' => 'osmifinále',
        default                     => trim($kolo),
    };
}

function pointeks_kolo_klic(string $kolo): string {
    return (string)preg_replace('/[\s_\-.]+/', '', mb_strtolower(trim($kolo)));
}

/** Je kolo finále (hlavní soutěže – kvalifikaci řeší volající)? */
function pointeks_je_finale(string $kolo): bool {
    return in_array(pointeks_kolo_klic($kolo), ['final', 'f', 'finale', 'finále'], true);
}

/**
 * Sety pro výpis: [['h' => naše gemy, 's' => soupeřovy, 'th' => tiebreak u nás, 'ts' => u soupeře], …].
 * Horní index tiebreaku jen u strany, která set prohrála (zápis 7-6(4)). Bez setů v JSON se zkusí skóre „6-3 4-6 7-6(4)“.
 */
function pointeks_sety_pro_vypis(array $sety, string $skore = ''): array {
    $v = [];
    foreach ($sety as $s) {
        if (!is_array($s) || !isset($s['player'], $s['opponent'])) continue;
        $h = (int)$s['player'];
        $o = (int)$s['opponent'];
        $th = $ts = null;
        if (is_array($s['tiebreak'] ?? null) && isset($s['tiebreak']['player'], $s['tiebreak']['opponent'])) {
            if ($h <= $o) $th = (int)$s['tiebreak']['player'];
            if ($o <= $h) $ts = (int)$s['tiebreak']['opponent'];
        }
        $v[] = ['h' => $h, 's' => $o, 'th' => $th, 'ts' => $ts];
    }
    if ($v || trim($skore) === '') return $v;
    preg_match_all('/\[?(\d{1,2})\s*[-:]\s*(\d{1,2})\]?(?:\s*\((\d{1,2})\))?/', $skore, $m, PREG_SET_ORDER);
    foreach (array_slice($m, 0, 7) as $x) {
        $h = (int)$x[1];
        $o = (int)$x[2];
        $tb = isset($x[3]) && $x[3] !== '' ? (int)$x[3] : null;
        $v[] = ['h' => $h, 's' => $o, 'th' => $tb !== null && $h < $o ? $tb : null, 'ts' => $tb !== null && $o < $h ? $tb : null];
    }
    return $v;
}

/**
 * Řádky jedné skupiny → zápas pro výpis (pointeks_zapas_html()):
 *   hraci   [['jmeno', 'klub' => true|false, 'zeme'], …] – hráči klubu tučně, spoluhráč mimo klub obyčejně
 *   souperi [['jmeno', 'zeme'], …], sety (pointeks_sety_pro_vypis), cas (DateTimeImmutable v Praze | null),
 *   zive, vysledek (WIN/LOSS/''), prubeh (FINISHED/RETIRED/WALKOVER/''), kolo (česky), finale, kvalifikace,
 *   turnaj, kategorie, vek, mesto, zeme, url, ctyrhra, ids.
 * Jména jsou přesně, jak přišla.
 */
function pointeks_zapas(array $radky): array {
    usort($radky, static fn(array $a, array $b): int => (int)$a['id'] <=> (int)$b['id']);
    $r = $radky[0];
    $klubovi = [];
    $zive = false;
    foreach ($radky as $x) {
        if ((string)$x['stav'] === POINTEKS_STAV_ZIVE) $zive = true;
        $jmeno = trim((string)$x['player_name']);
        $klic = (string)$x['player_slug'] !== '' ? (string)$x['player_slug'] : mb_strtolower($jmeno);
        if ($jmeno === '' || isset($klubovi[$klic])) continue;
        $klubovi[$klic] = ['jmeno' => $jmeno, 'klub' => true, 'zeme' => ''];
    }
    $hraci = array_values($klubovi);
    if (count($hraci) > 1) {
        usort($hraci, static fn(array $a, array $b): int => pointeks_porovnej_jmena($a['jmeno'], $b['jmeno']));
    } elseif (trim((string)$r['partner_name']) !== '') {
        $hraci[] = ['jmeno' => trim((string)$r['partner_name']), 'klub' => false, 'zeme' => (string)$r['partner_country']];
    }
    $souperi = [];
    foreach (json_pole((string)$r['opponents_json']) as $o) {
        if (!is_array($o) || trim((string)($o['name'] ?? '')) === '') continue;
        $souperi[] = ['jmeno' => trim((string)$o['name']), 'zeme' => (string)($o['country'] ?? '')];
    }
    $kolo = trim((string)$r['round']);
    return [
        'ids'         => array_map(static fn(array $x): int => (int)$x['id'], $radky),
        'zive'        => $zive,
        'cas'         => pointeks_cas_praha((string)($r['completed_at'] ?? '') !== '' ? (string)$r['completed_at'] : (string)$r['received_at']),
        'hraci'       => $hraci,
        'souperi'     => $souperi,
        'sety'        => pointeks_sety_pro_vypis(json_pole((string)$r['sets_json']), (string)$r['score']),
        'vysledek'    => (string)$r['result'],
        'prubeh'      => (string)$r['outcome'],
        'skore'       => (string)$r['score'],
        'kolo'        => $kolo !== '' ? pointeks_kolo($kolo) : '',
        'finale'      => $kolo !== '' && pointeks_je_finale($kolo),
        'kvalifikace' => (int)$r['qualifying'] === 1,
        'turnaj'      => trim((string)$r['tournament_name']),
        'kategorie'   => trim((string)$r['tournament_category']),
        'vek'         => trim((string)($r['age_category'] ?? '')),
        'mesto'       => trim((string)$r['tournament_city']),
        'zeme'        => (string)$r['tournament_country'],
        'url'         => (string)$r['url'],
        'ctyrhra'     => (string)$r['discipline'] === 'DOUBLES',
    ];
}

/** Odkaz poděkování „Výsledky: Pointeks“: url nejnovějšího výsledku na webu (podle konce zápasu – stejný
 *  na každé stránce výpisu i s filtrem), bez něj url prvního zápasu výpisu; jen http(s) přes
 *  bezpecny_odkaz, jinak pointeks.com. */
function pointeks_kredit_url(array $zapasy = []): string {
    $u = '';
    try {
        $u = (string)val("SELECT url FROM cltk_pointeks_vysledky WHERE visible = 1 AND url <> ''
                           ORDER BY COALESCE(completed_at, received_at) DESC, id DESC LIMIT 1");
    } catch (Throwable $e) {
        pointeks_chyba_db($e, 'poděkování');
    }
    $z = $zapasy[0] ?? null;
    if ($u === '' && is_array($z)) $u = (string)($z['url'] ?? '');
    $u = bezpecny_odkaz($u);
    return $u !== '' && preg_match('~^https?://~i', $u) ? $u : POINTEKS_KREDIT_VYCHOZI;
}
