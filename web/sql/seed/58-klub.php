<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Doplňkové bloky stránek sekce Klub (klub, clenstvi, historie, vedeni, ctc, revue).
   Základní bloky (uvod, o-klubu, jmena, v-cene, postup …) zakládá 60-bloky.php;
   tady jsou jen další sekce, které stránky potřebují. Texty jsou převzaté
   z ověřených podkladů (Varianta 4, README návrhů, obsah.json) – nic nového.
   Bloky se spravují v administraci (Stránky); seznamy v textu mají pevný tvar
   „co položka seznamu, to záznam“, tučně na začátku hodnota / rok:
     klub/cisla      <li><strong>19 kurtů</strong> popis</li>  (prameny ve stejném pořadí v klub/prameny)
     klub/cesta      <li><strong>1901</strong> Štvanice</li>
     historie/linka  <li><strong>1954</strong> Jaroslav Drobný · vítěz</li>  („vítěz…“ = plný bod)
     revue/pribehy   <li>Karolína Muchová</li>  (jména pro „Příběh v obálkách“)
   Vkládá se jen dvojice stránka + klíč, která ještě není. */

$n = 0;
$b = static function (string $stranka, string $klic, array $data, int $poradi = 0) use (&$n): void {
    if (seed_blok($stranka, $klic, $data, $poradi)) $n++;
};

/* ================= KLUB ================= */
$b('klub', 'cisla', ['stitek' => 'Klub v číslech',
    'text' => '<ul>'
        . '<li><strong>1893</strong> rok založení – nejstarší tenisový klub v Praze</li>'
        . '<li><strong>1901</strong> na ostrově Štvanice – tehdy pět kurtů, dřevěná klubovna a restaurace</li>'
        . '<li><strong>19 kurtů</strong> v areálu Štvanice, v zimě 12 krytých</li>'
        . '<li><strong>10</strong> grandslamových titulů v barvách klubu v den triumfu</li>'
        . '<li><strong>1 ze 3</strong> sportovních center mládeže ČTS v Česku, s celou pyramidou TSM, SVT a SCM</li>'
        . '<li><strong>1 jediný</strong> český člen Centenary Tennis Clubs – sdružení 98 klubů starších 100 let</li>'
        . '</ul>'], 3);
$b('klub', 'prameny', ['nadpis' => 'Odkud čísla bereme',
    'text' => '<ol>'
        . '<li>Národní muzeum – inventář pozůstalosti J. Rösslera-Ořovského; ceskytenis.info.</li>'
        . '<li>Ústav dějin umění AV ČR – Umělecké památky, Tenisový dvorec na Štvanici.</li>'
        . '<li>Web klubu: tenisové kurty a podmínky členství. Počet zahrnuje velký centrální dvorec, který patří Českému tenisovému svazu.</li>'
        . '<li>Registr ČTS a en.wikipedia. Včetně čtyřhry a mixu. Podle klubové definice „hráčů Štvanice“ je titulů 20 – viz <a href="historie.php#sin-slavy">Zlatou desku</a>.</li>'
        . '<li>ČTS, Seznam hráčů SCM/SVT/TSM 2026.</li>'
        . '<li>Seznam členů Centenary Tennis Clubs; web klubu 2026.</li>'
        . '</ol>'], 4);
$b('klub', 'ostrov', ['stitek' => 'Ostrov Štvanice',
    'foto' => seed_obrazek(seed_navrhy('assets/foto/letecky-stvanice-panorama-prahy.jpg'), 'bloky', 'klub-stvanice-panorama'),
    'foto_popisek' => 'Ostrov Štvanice s tenisovým areálem uprostřed Vltavy'], 5);
$b('klub', 'cesta', ['stitek' => 'Cesta na Štvanici',
    'perex' => 'Podle cs Wikipedie (Lichner 1985); klubové prameny se v prvních krocích liší.',
    'text' => '<ul><li><strong>1893</strong> Židovský ostrov</li><li><strong>1894</strong> Střelecký ostrov</li>'
        . '<li><strong>1895/96</strong> Holešovice-Bubny</li><li><strong>1901</strong> Štvanice</li></ul>'], 6);
$b('klub', 'rozcestnik', ['stitek' => 'Klub', 'nadpis' => 'Členství, historie a <em>lidé</em> klubu.'], 7);

/* ================= ČLENSTVÍ ================= */
$b('clenstvi', 'konfigurator', ['stitek' => 'Ceník 2026', 'nadpis' => 'Spočítejte si <em>členství</em>',
    'perex' => 'Zvolte, kdo bude hrát – cenu spočítáme podle ceníku členství. Volbu pak přenesete do přihlášky.'], 6);
$b('clenstvi', 'clensky-list', ['nadpis' => 'Členský list',
    'perex' => 'Po přijetí si Členský list vytisknete. Jeho podobu schvaluje klub.'], 7);
$b('clenstvi', 'dekujeme', ['stitek' => 'Přihláška odeslána', 'nadpis' => 'Děkujeme, přihláška <em>dorazila</em>.',
    'perex' => 'Přihlášku posoudí Výkonný výbor, zpravidla na nejbližším zasedání. Ozveme se vám e-mailem nebo telefonicky s dalším postupem a platebními údaji.'], 8);

/* ================= HISTORIE ================= */
$b('historie', 'linka', ['stitek' => 'Wimbledonská linka · finále dvouhry hráčů Štvanice',
    'perex' => 've štvanické historii, bez ohledu na klub v den finále',
    'text' => '<ul>'
        . '<li><strong>1954</strong> Jaroslav Drobný · vítěz</li>'
        . '<li><strong>1962</strong> Věra Suková-Pužejová · finále, první československá finalistka</li>'
        . '<li><strong>1973</strong> Jan Kodeš · vítěz</li>'
        . '<li><strong>2023</strong> Markéta Vondroušová · vítězka</li>'
        . '<li><strong>2026</strong> Karolína Muchová · finále s Lindou Noskovou 2:6, 7:5, 3:6</li>'
        . '</ul>'], 11);
$b('historie', 'deska-prameny', ['perex' => 'Prameny: seznamy klubu, registr ČTS, Revue 01/2023. Co tvrdí jen klub, nese štítek „podle klubu“.'], 12);

/* ================= VEDENÍ ================= */
$b('vedeni', 'prezident', ['stitek' => 'Prezident klubu'], 2);
$b('vedeni', 'vybor', ['stitek' => 'Výkonný výbor', 'nadpis' => 'Výkonný <em>výbor</em>'], 3);
$b('vedeni', 'kancelar', ['stitek' => 'Kancelář klubu', 'nadpis' => 'Kancelář <em>klubu</em>',
    'perex' => 'Členství, stálé rezervace a provoz areálu.'], 4);
$b('vedeni', 'kontakty', ['stitek' => 'Kontakty', 'nadpis' => 'Další <em>kontakty</em>'], 5);
$b('vedeni', 'prezidenti', ['stitek' => 'Od roku 1893', 'nadpis' => 'Prezidenti <em>klubu</em>'], 6);

/* ================= CTC ================= */
$b('ctc', 'clenstvi', ['stitek' => 'Členství v CTC', 'nadpis' => 'Sdružení klubů starších <em>sta let</em>.'], 2);
$b('ctc', 'foto', [
    'foto' => seed_obrazek(seed_navrhy('assets/foto/ctc-senior-2025-centenary-banner.jpg'), 'bloky', 'ctc-senior-2025'),
    'foto_popisek' => 'CTC Senior Competition 2025'], 3);
$b('ctc', 'utkani', ['stitek' => 'Na Štvanici', 'nadpis' => 'Mezinárodní <em>utkání</em>.',
    'perex' => 'Kluby sdružené v Centenary Tennis Clubs se navzájem zvou k přátelským utkáním.'], 4);
$b('ctc', 'souteze', ['stitek' => 'Soutěže', 'nadpis' => 'Poháry pro děti i <em>seniory</em>.'], 5);

/* ================= REVUE ================= */
$b('revue', 'kiosek', ['stitek' => 'Kiosek',
    'perex' => 'Hledejte v titulcích obálek a v obsazích čísel, nebo sledujte příběh v obálkách.'], 2);
$b('revue', 'pribehy', ['nadpis' => 'Příběh v obálkách',
    'text' => '<ul><li>Karolína Muchová</li><li>Lucie Hradecká</li><li>Jan Kodeš</li><li>Nikola Bartůňková</li><li>Markéta Vondroušová</li></ul>'], 3);

seed_log('Bloky sekce Klub: ' . $n . ' nových.');
