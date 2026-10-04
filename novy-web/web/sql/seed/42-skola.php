<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Tenisová škola Markéty Vondroušové: informace, harmonogram sezóny, termíny kempů.
   Zdroj: podklady/03 kap. 9 (stránky Informace, Ceník, Rozvrhy, Letní kempy),
   obsah.json → cenik.letni_kempy_2026. Rozvrhy tréninků (zima 2026/27, přechodný týden)
   z PDF starého webu (bez jmen dětí) vloží sada 95-archiv-pdf z datového souboru migrace. */

if (!seed_prazdna('cltk_skola')) return;

$ted = ted();
$r = [];
$add = static function (array $x) use (&$r, $ted): void {
    $r[] = array_merge(['typ' => 'info', 'nazev' => '', 'datum_od' => null, 'datum_do' => null, 'termin_text' => '', 'den' => '', 'cas' => '',
                        'skupina' => '', 'misto' => '', 'trener' => '', 'cena' => '', 'text' => '', 'odkaz' => '', 'visible' => 1,
                        'created_at' => $ted, 'updated_at' => $ted], $x);
};

/* --- informace a podmínky (věrně podle dnešního webu klubu) --- */
$add(['nazev' => 'Pro koho', 'text' => 'Pro děti od 3 do 9 let, zejména na výkonnostní úrovni. Talentované děti postupně užším výběrem reprezentují klub v soutěžích družstev i jednotlivců. Nábory jsou vždy na jaře a na podzim.']);
$add(['nazev' => 'Co děláme', 'text' => 'Pravidelná výuka, turnaje dětí v minitenise, na středním kurtu a v babytenise, mistrovská utkání družstev, kempy a soustředění.']);
$add(['nazev' => 'Platba', 'text' => 'Cena zahrnuje trenéra i pronájem kurtu. Platí se kurzovné za celé období, nebo podle skutečnosti – podle trenéra. Vyúčtování přichází koncem měsíce e-mailem. Základní členství Tenisové školy 1 000 Kč se hradí vždy na začátku roku.']);
$add(['nazev' => 'Počasí a náhrady', 'text' => 'Při nepříznivém počasí se trénuje v hale; když je hala obsazená, trénink se ruší a neúčtuje. Náhrady jen po dohodě a ve volných kapacitách.']);
$add(['nazev' => 'Rodiče', 'text' => 'Během tréninků se prosím zdržujte mimo dvorec.']);

/* --- harmonogram sezóny 2026/27 --- */
$harm = [
    ['Tréninky v provizorním režimu (testování zimních rozvrhů)', '2026-09-07', null, ''],
    ['Schůzka s rodiči – sraz na recepci od 18:30', '2026-09-07', null, ''],
    ['Nehraje se – stavba hal', '2026-09-21', '2026-09-28', ''],
    ['Tréninky podle zimních rozvrhů', '2026-09-29', '2027-04-02', ''],
    ['Podzimní prázdniny', '2026-10-26', '2026-10-30', ''],
    ['Státní svátek – nehraje se', '2026-11-17', null, ''],
    ['Vánoční prázdniny', '2026-12-23', '2027-01-03', ''],
];
foreach ($harm as [$n, $od, $do, $t]) {
    $add(['typ' => 'harmonogram', 'nazev' => $n, 'datum_od' => $od, 'datum_do' => $do, 'termin_text' => $t, 'text' => '']);
}

/* --- termíny letních kempů 2026 --- */
$kempy = [
    ['2026-06-29', '2026-07-03'], ['2026-07-06', '2026-07-10'], ['2026-07-27', '2026-07-31'],
    ['2026-08-03', '2026-08-07'], ['2026-08-10', '2026-08-14'], ['2026-08-24', '2026-08-28'],
];
foreach ($kempy as $i => [$od, $do]) {
    $add(['typ' => 'kemp', 'nazev' => ($i + 1) . '. termín', 'datum_od' => $od, 'datum_do' => $do, 'cas' => 'A 8:30–16:30 · B 8:30–13:30']);
}

$poradi = [];
foreach ($r as &$x) { $poradi[$x['typ']] = ($poradi[$x['typ']] ?? -1) + 1; $x['poradi'] = $poradi[$x['typ']]; }
unset($x);
seed_log('Tenisová škola: ' . seed_vloz('cltk_skola', $r) . ' záznamů (informace, harmonogram, kempy).');
