<?php
/* Výsledky (Pointeks) – výsledky zápasů hráčů klubu, které posílá služba Pointeks (inc/pointeks.php).
   Nahradil modul „Výsledky hráčů“ (vysledky.php – rozhodnutí Petra 15. 10. 2026: na webu jen výsledky
   z Pointeks). Nic se tu ručně nezadává: výsledky přicházejí samy na adresu …/pointeks/feed.
   Správce tu vidí přijaté výsledky (nejnovější první, filtr podle hráče), může jednotlivý výsledek
   skrýt / znovu zobrazit (přepínač ve vlastním formuláři s CSRF; u spojené čtyřhry dvou hráčů klubu
   přepne oba záznamy – na webu je to jeden řádek), vidí počty, čas posledního
   přijatého výsledku, přesnou adresu pro Pointeks, jestli je nastavený tajný klíč (jen ano / ne,
   nikdy hodnota) a konec protokolu příjmu data/pointeks.log. */
require __DIR__ . '/inc/layout.php';
$user = require_login();

const PK_STRANKA = 'pointeks.php';
const PK_NA_STRANKU = 30;

$hraci = pointeks_hraci(true);
/** Adresa modulu se zachovaným filtrem a stránkou (jen ověřené hodnoty). */
$pkUrl = static function (string $hrac, int $strana = 1) use ($hraci): string {
    $q = [];
    if ($hrac !== '' && isset($hraci[$hrac])) $q['hrac'] = $hrac;
    if ($strana > 1) $q['strana'] = (string)$strana;
    return PK_STRANKA . ($q ? '?' . http_build_query($q) : '');
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    admin_post_zacatek(PK_STRANKA);
    $akce = vstup('action', 20);
    $id = (int)vstup_int('id');
    $zpet = $pkUrl(vstup('hrac', 120), max(1, (int)vstup_int('strana', 1)));
    $r = $id ? row('SELECT id, player_name, tournament_name, visible, skupina FROM cltk_pointeks_vysledky WHERE id = ?', [$id]) : null;
    if (!$r) redirect($zpet, 'Výsledek nebyl nalezen.', 'err');
    if ($akce === 'prepnout') {
        /* Spojená čtyřhra (dva hráči klubu = dvě doručení se stejnou skupinou, na webu jeden řádek „A / B“):
           přepíná se celá skupina – po skrytí jen jednoho záznamu by zápas zůstal na webu i se jménem
           skrytého hráče (z pole partner druhého záznamu). Výpis webu skupinu se skrytým záznamem vynechá. */
        $skupina = (string)$r['skupina'];
        $spojene = $skupina !== ''
            ? rows('SELECT player_name, visible FROM cltk_pointeks_vysledky WHERE skupina = ? AND id <> ? ORDER BY id', [$skupina, $id]) : [];
        $jeNaWebu = (int)$r['visible'] === 1;                 // zápas je na webu, jen když nic ze skupiny není skryté
        foreach ($spojene as $x) $jeNaWebu = $jeNaWebu && (int)$x['visible'] === 1;
        $novy = $jeNaWebu ? 0 : 1;
        if ($spojene) {
            q('UPDATE cltk_pointeks_vysledky SET visible = ? WHERE skupina = ?', [$novy, $skupina]);
        } else {
            q('UPDATE cltk_pointeks_vysledky SET visible = ? WHERE id = ?', [$novy, $id]);
        }
        $co = trim($r['player_name'] . ($r['tournament_name'] !== '' ? ' – ' . $r['tournament_name'] : ''));
        $s = $spojene ? implode(', ', array_map(static fn(array $x): string => (string)$x['player_name'], $spojene)) : '';
        redirect($zpet . '#v' . $id, $novy
            ? 'Výsledek „' . $co . '“ je zase na webu' . ($s !== '' ? ' i se spojeným záznamem čtyřhry (' . $s . ')' : '') . '.'
            : 'Výsledek „' . $co . '“ je skrytý' . ($s !== '' ? ' i se spojeným záznamem čtyřhry (' . $s . ')' : '') . ' – na webu se neukazuje.');
    }
    redirect($zpet, 'Neznámý požadavek.', 'err');
}

/* ---------- výpis ---------- */
/** Počet řádků s pojistkou (bez tabulky = 0). */
function pk_pocet(string $sql, array $p = []): int {
    try { return (int)val($sql, $p); } catch (Throwable $e) { return 0; }
}

$hrac = trim(vstup_get('hrac'));
if ($hrac !== '' && !isset($hraci[$hrac])) $hrac = '';
$strana = trim(vstup_get('strana'));
$strana = preg_match('/^[1-9]\d{0,4}$/', $strana) ? (int)$strana : 1;

$celkem = pk_pocet('SELECT COUNT(*) FROM cltk_pointeks_vysledky');
$naWebu = pk_pocet('SELECT COUNT(*) FROM cltk_pointeks_vysledky WHERE ' . POINTEKS_NA_WEBU_SQL);
$zive   = pk_pocet("SELECT COUNT(*) FROM cltk_pointeks_vysledky WHERE stav = 'zive'");
$posledni = pointeks_posledni_prijem();
$adresa = site_url('pointeks/feed');
$maKlic = pointeks_ma_klic();
$protokol = array_reverse(pointeks_log_konec(20));

$kde = $hrac !== '' ? ' WHERE player_slug = ?' : '';
$p = $hrac !== '' ? [$hrac] : [];
$vyber = pk_pocet('SELECT COUNT(*) FROM cltk_pointeks_vysledky' . $kde, $p);
$stran = max(1, (int)ceil($vyber / PK_NA_STRANKU));
$strana = min($strana, $stran);
$radky = [];
try {
    $radky = rows('SELECT ' . POINTEKS_SLOUPCE . ' FROM cltk_pointeks_vysledky' . $kde
        . " ORDER BY CASE WHEN stav = 'zive' THEN 0 ELSE 1 END, " . POINTEKS_CAS_SQL . ' DESC, id DESC'
        . ' LIMIT ' . PK_NA_STRANKU . ' OFFSET ' . (($strana - 1) * PK_NA_STRANKU), $p);
} catch (Throwable $e) {
    pointeks_chyba_db($e, 'administrace');
}

/* Záznamy spojené na webu do jednoho řádku (čtyřhra dvou hráčů klubu – stejná skupina): u řádku se ukáže,
   s kým je spojený, a stav platí pro celý zápas (na webu je, jen když není skrytý žádný jeho záznam). */
$skupinaClenove = [];                                // skupina => [['id', 'player_name', 'visible'], …]
$skupinyStrany = array_values(array_unique(array_filter(array_map(static fn(array $r): string => (string)$r['skupina'], $radky),
    static fn(string $s): bool => $s !== '')));
if ($skupinyStrany) {
    try {
        foreach (rows('SELECT id, skupina, player_name, visible FROM cltk_pointeks_vysledky WHERE skupina IN ('
                 . implode(', ', array_fill(0, count($skupinyStrany), '?')) . ') ORDER BY id', $skupinyStrany) as $x) {
            $skupinaClenove[(string)$x['skupina']][] = $x;
        }
    } catch (Throwable $e) {
        pointeks_chyba_db($e, 'administrace – skupiny');
    }
}

admin_head('Výsledky (Pointeks)', $user, [
    'podnadpis' => 'Výsledky zápasů hráčů klubu, které automaticky posílá služba Pointeks. Na webu je jich 10 nejnovějších na úvodní stránce a všechny na stránce <a href="' . e(url('vysledky.php')) . '" target="_blank" rel="noopener">Výsledky</a>. Ručně se nic nezadává – výsledek, který nemá být vidět, skryjte.',
]);
echo '<link rel="stylesheet" href="' . e(BASE_PATH . verze('admin/assets/admin-pointeks.css')) . '">';
?>

<?php if (!pointeks_tabulka_existuje()): ?>
<div class="flash err" role="alert"><b>V databázi zatím chybí tabulka výsledků.</b> Na serveru je potřeba spustit migraci
  sql/migrace/2026-10-15-pointeks.php – do té doby příjem odpovídá chybou 500 a Pointeks výsledky posílá znovu (zkouší to asi 25 hodin).</div>
<?php endif; ?>

<div class="stats">
  <div class="stat"><b><?= cislo($naWebu) ?></b><span>Na webu</span><small><?= cislo($celkem) ?> přijatých celkem</small></div>
  <div class="stat"><b><?= cislo($celkem - $naWebu) ?></b><span>Skryté</span></div>
  <div class="stat"><b><?= cislo(count($hraci)) ?></b><span>Hráčů s výsledkem</span></div>
  <div class="stat"><b class="pk-stat-cas"><?= $posledni ? e($posledni->format('j. n.')) : '–' ?></b><span>Poslední výsledek přišel</span><small><?= $posledni ? e($posledni->format('j. n. Y \v G:i')) : 'zatím žádný' ?></small></div>
</div>

<section class="panel" id="napojeni">
  <div class="panel-head"><h2>Napojení na Pointeks</h2><?= $maKlic ? badge('Příjem je zapnutý', 'ok') : badge('Chybí tajný klíč', 'err') ?></div>
  <div class="panel-body">
    <dl class="pk-napojeni">
      <dt>Adresa pro Pointeks</dt>
      <dd><code class="pk-adresa"><?= e($adresa) ?></code>
        <div class="hint">Tuhle adresu (přesně, bez lomítka na konci) dejte Pointeks. Odpovídá přímo, bez přesměrování; jen požadavky POST s platným podpisem.</div></dd>
      <dt>Tajný klíč nastavený</dt>
      <dd><?= $maKlic ? badge('ano', 'ok') : badge('ne', 'err') ?>
        <div class="hint"><?= $maKlic
            ? 'Klíč je uložený na serveru (cltk-config.php). Jeho hodnota se nikde nezobrazuje.'
            : 'Bez klíče příjem odpovídá chybou 500 a nic neukládá. Klíč dá Pointeks soukromě a správce webu ho zapíše na server do cltk-config.php (POINTEKS_SECRET).' ?></div></dd>
      <?php if ($zive > 0): ?>
      <dt>Živé zápasy</dt>
      <dd><?= badge(cislo($zive) . ' ' . sklonuj($zive, 'zápas', 'zápasy', 'zápasů'), 'err') ?></dd>
      <?php endif; ?>
    </dl>
  </div>
</section>

<section class="panel" id="vysledky">
  <div class="panel-head">
    <h2>Přijaté výsledky <small><?= cislo($vyber) ?><?= $hrac !== '' ? ' · ' . e($hraci[$hrac]) : '' ?></small></h2>
    <?php if ($hraci): ?>
    <form method="get" class="pk-filtr" action="<?= e(PK_STRANKA) ?>#vysledky">
      <label class="vh" for="pk-hrac">Hráč</label>
      <select id="pk-hrac" name="hrac">
        <option value="">Všichni hráči</option>
        <?php foreach ($hraci as $slug => $jmeno): ?>
        <option value="<?= e((string)$slug) ?>"<?= (string)$slug === $hrac ? ' selected' : '' ?>><?= e($jmeno) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-sm btn-ghost">Filtrovat</button>
      <?php if ($hrac !== ''): ?><a class="btn btn-sm btn-ghost" href="<?= e(PK_STRANKA) ?>#vysledky">Všichni</a><?php endif; ?>
    </form>
    <?php endif; ?>
  </div>
  <?php if (!$radky): ?>
    <?= prazdny_stav('Zatím žádný výsledek', 'Výsledky se tu objeví samy, jakmile Pointeks pošle první dohraný zápas hráče klubu. Zkušební spojení („ping“) se neukládá – je vidět jen v protokolu níž.') ?>
  <?php else: ?>
  <div class="tbl-wrap tbl-karty">
    <table class="pk-tabulka">
      <thead><tr><th>Zápas</th><th>Hráč klubu</th><th>Soupeř</th><th>Výsledek</th><th>Stav</th><th>Přijato</th><th class="right">Akce</th></tr></thead>
      <tbody>
      <?php foreach ($radky as $r):
          $id = (int)$r['id'];
          $z = pointeks_zapas([$r]);
          $prijato = pointeks_cas_praha((string)$r['received_at']);
          $sety = implode(' ', array_map(static fn(array $s): string => $s['h'] . ':' . $s['s']
              . ($s['th'] !== null ? '(' . $s['th'] . ')' : ($s['ts'] !== null ? '(' . $s['ts'] . ')' : '')), $z['sety']));
          $soutez = implode(' · ', array_filter([$z['kategorie'], $z['kolo'], $z['kvalifikace'] ? 'kvalifikace' : ''], static fn($x) => $x !== ''));
          $hraciText = implode(' / ', array_map(static fn(array $c): string => $c['jmeno'] . ($c['zeme'] !== '' ? ' (' . $c['zeme'] . ')' : ''), $z['hraci']));
          $souperiText = implode(' / ', array_map(static fn(array $c): string => $c['jmeno'] . ($c['zeme'] !== '' ? ' (' . $c['zeme'] . ')' : ''), $z['souperi']));
          $clenove = $skupinaClenove[(string)$r['skupina']] ?? [];
          $spojeni = array_values(array_filter($clenove, static fn(array $x): bool => (int)$x['id'] !== $id));
          $jeNaWebu = (int)$r['visible'] === 1;
          foreach ($spojeni as $x) $jeNaWebu = $jeNaWebu && (int)$x['visible'] === 1; ?>
        <tr id="v<?= $id ?>"<?= $jeNaWebu ? '' : ' class="je-skryte"' ?>>
          <td data-label="Zápas" class="td-nazev">
            <b><?= $z['cas'] ? e($z['cas']->format('j. n. Y')) : 'bez data' ?><?= $z['cas'] && (string)$r['completed_at'] !== '' && pointeks_cas_zapasu($r) === trim((string)$r['completed_at']) ? ' <span class="pk-cas">' . e($z['cas']->format('G:i')) . '</span>' : '' ?></b>
            <small><?= e(trim($z['turnaj'] . ($z['mesto'] !== '' ? ', ' . $z['mesto'] : '') . ($z['zeme'] !== '' ? ' (' . $z['zeme'] . ')' : ''), ', ')) ?></small>
            <?php if ($soutez !== ''): ?><small><?= e($soutez) ?></small><?php endif; ?>
          </td>
          <td data-label="Hráč klubu" class="td-nazev"><b><?= e($hraciText) ?></b><small class="pk-klic"><?= e((string)$r['player_slug']) ?></small>
            <?php if ($spojeni): ?><small class="pk-spojeno">Spojeno se záznamem: <?= e(implode(', ', array_map(static fn(array $x): string => (string)$x['player_name'], $spojeni))) ?> – na webu jeden řádek čtyřhry, Skrýt / Zobrazit platí pro oba.</small><?php endif; ?></td>
          <td data-label="Soupeř"><?= $souperiText !== '' ? e($souperiText) : '<span class="pk-tlumene">neuveden</span>' ?></td>
          <td data-label="Výsledek" class="pk-vysledek">
            <?= $z['vysledek'] === 'WIN' ? badge('Výhra', 'ok') : ($z['vysledek'] === 'LOSS' ? badge('Prohra', 'off') : '') ?>
            <?php if ($z['prubeh'] === 'WALKOVER'): ?><span class="pk-sety">bez boje</span>
            <?php elseif ($sety !== ''): ?><span class="pk-sety"><?= e($sety) ?></span><?php endif; ?>
            <?php if ($z['prubeh'] === 'RETIRED'): ?><span class="pk-tlumene">skreč</span><?php endif; ?>
          </td>
          <td data-label="Stav">
            <?= stav_badge($jeNaWebu ? 1 : 0, 'Na webu', 'Skryto') ?>
            <?= $z['zive'] ? badge('živě', 'err') : '' ?>
            <?= $z['finale'] && !$z['kvalifikace'] && $z['vysledek'] === 'WIN' ? badge('Titul', 'zlato') : '' ?>
          </td>
          <td data-label="Přijato" class="tlumene"><?= $prijato ? '<span class="pk-nowrap">' . e($prijato->format('j. n. Y')) . '</span> <span class="pk-nowrap">' . e($prijato->format('G:i')) . '</span>' : '' ?><small class="pk-klic" title="id výsledku od Pointeks (z podepsaného těla zprávy)"><?= e((string)$r['delivery_id']) ?></small></td>
          <td data-label="Akce" class="right"><div class="akce-radku"><?= tlacitko_prepnout($id, $jeNaWebu ? 1 : 0, ['hrac' => $hrac, 'strana' => $strana]) ?></div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($stran > 1): ?>
  <nav class="pk-strankovani" aria-label="Stránky výsledků">
    <?php if ($strana > 1): ?><a class="btn btn-sm btn-ghost" href="<?= e($pkUrl($hrac, $strana - 1)) ?>#vysledky">← Novější</a><?php endif; ?>
    <span>Strana <?= $strana ?> z <?= $stran ?></span>
    <?php if ($strana < $stran): ?><a class="btn btn-sm btn-ghost" href="<?= e($pkUrl($hrac, $strana + 1)) ?>#vysledky">Starší →</a><?php endif; ?>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</section>

<section class="panel" id="protokol">
  <div class="panel-head"><h2>Protokol příjmu <small>posledních <?= count($protokol) ?> požadavků, nejnovější nahoře</small></h2></div>
  <div class="panel-body">
    <?php if ($protokol): ?>
    <pre class="pk-log"><?php foreach ($protokol as $radek) echo e($radek), "\n"; ?></pre>
    <p class="hint">Čas · kód odpovědi (204 = v pořádku, 401 = nesouhlasí podpis, 500 = chyba na webu – Pointeks to zkusí znovu) · událost · id výsledku · co se stalo. Obsah zpráv ani klíč se nezapisují.</p>
    <?php else: ?>
    <p class="hint">Zatím sem nepřišel žádný požadavek.</p>
    <?php endif; ?>
  </div>
</section>

<?php admin_foot(); ?>
