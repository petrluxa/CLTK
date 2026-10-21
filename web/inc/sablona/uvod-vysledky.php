<?php
/* ÚVODNÍ STRÁNKA – tmavomodrý pás „Naši hráči na turnajích“ hned pod úvodem (návrh C, Petr vybral 18. 10. 2026)
   Volá index.php: uvod_vysledky_html('pod-uvodem', $vysledkyPas) – 8 nejnovějších zápasů z pointeks_seznam()
   (Petr 21. 10. 2026: dvě řady; světlá tabule „Další výsledky“ z úvodu je zrušená – všechny výsledky
   jsou na stránce Výsledky pod Závodním tenisem).

     – vlevo nadpis „Naši hráči na turnajích“ a pod ním decentní pozvánka „Každý bod živě
       v aplikaci Pointeks ↗“ (pointeks_pozvanka_url(…, 'uvod-pas'), nové okno),
       vpravo „Všechny výsledky →“ (na mobilu pod tabulemi),
     – pod tím malé výsledkové tabule (nejvýš 8): od 1180 px dvě řady po čtyřech, 700–1179 px po dvou,
       na mobilu pod sebou jen 4 nejnovější (ať úvod není nekonečný); méně výsledků = méně širších sloupců.
   Jména všech hráčů jedním písmem (Jost, Petr 21. 10. 2026: „stejný font“) – hráč klubu tučně se znakem.
   Jedna tabule: město kapitálkami a datum (živý zápas: červená tečka „živě“), kategorie · kolo
   (· kvalifikace · výhra / prohra skrečí; štítek Titul / Finále místo slova kola – pointeks_soutez_html(),
   stejně jako tabule v sekci 3), pak dva řádky se sloupci setů –
   nahoře strana klubu (čtyřhra = jména pod sebou, spoluhráč mimo klub obyčejným řezem), dole
   soupeř se zkratkou země. Za jménem každého hráče klubu malý znak klubu (pointeks_znak_html(),
   ne u spoluhráče odjinud ani u soupeřů), s posledním slovem jména v jednom celku – sám na nový
   řádek neodskočí. Jména klubu jsou vždy nejvýraznější jména tabule (i při prohře); výsledek
   ukazují sety: v každém je zvýrazněné vyšší číslo (u živého zápasu ne rozehraný poslední set),
   tiebreak horním indexem u prohraného setu, zápas bez boje = „výhra / prohra bez boje“ místo setů.

   Pravidla stejná jako pointeks_zapas_html() (inc/sablona/komponenty.php): jména přesně, jak
   přišla (jen e()), všechno z Pointeks escapované, datum d. m. v pražském čase (rok, když není
   letošní), živé zápasy první (řadí pointeks_seznam()). Pro čtečky celá věta pointeks_zapas_veta(),
   kreslená tabule je aria-hidden. Bez výsledků se pás nevypíše (vrací '').
   Jen čerstvé výsledky: index.php pás nejdřív projde uvod_vysledky_cerstve() – nejnovější zápas starší než
   UVOD_VYSLEDKY_DNI (mimo sezónu, výpadek příjmu) = pás se nevypíše (výsledky jsou dál na stránce Výsledky).
   Styl: assets/css/uvod-vysledky.css (všechno pod .uvc, úvod jen přes .uvod:has(+ .uvc)). */

const UVOD_VYSLEDKY_DNI = 30;

/** Zápasy pro pás pod úvodem, jen když jsou čerstvé: živý zápas, nebo nejnovější zápas (první v seznamu
 *  z pointeks_seznam()) nejvýš UVOD_VYSLEDKY_DNI dní starý; jinak [] (pás se nevypíše). Zápas bez známého
 *  času stáří nerozhoduje. */
function uvod_vysledky_cerstve(array $zapasy): array {
    $prvni = $zapasy[0] ?? null;
    if (!is_array($prvni) || !empty($prvni['zive'])) return $zapasy;
    $cas = $prvni['cas'] ?? null;
    if (!$cas instanceof DateTimeImmutable) return $zapasy;
    $hranice = (new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))->modify('-' . UVOD_VYSLEDKY_DNI . ' days');
    return $cas < $hranice ? [] : $zapasy;
}

function uvod_vysledky_html(string $misto, array $zapasy): string {
    if ($misto !== 'pod-uvodem') return '';
    $zapasy = array_slice(array_values(array_filter($zapasy, 'is_array')), 0, 8);   // dvě řady po čtyřech (Petr 21. 10. 2026)
    if (!$zapasy) return '';
    $letos = (new DateTimeImmutable('now', new DateTimeZone('Europe/Prague')))->format('Y');

    /* Jména jedné strany – každé na vlastním řádku (čtyřhra pod sebou), přesně, jak přišla.
       Hráč klubu: za jménem znak klubu, spojený s posledním slovem do nezalomitelného celku, aby znak nikdy
       neodskočil sám na nový řádek; text jména se jen rozdělí na dva kusy, nemění se. Neobvykle dlouhé
       poslední slovo (přes 14 znaků) se přilepí jen posledními šesti znaky, zbytek se smí zlomit (nevyběhne
       do sloupců setů) – stejně jako na výsledkové tabuli webu (komponenty.php).
       Zkratka země za nezlomitelnou mezerou, ať neodskočí od jména na samostatný řádek. */
    $strana = static function (array $lide, bool $nasi): string {
        $h = '';
        foreach ($lide as $c) {
            if (!is_array($c) || trim((string)($c['jmeno'] ?? '')) === '') continue;
            $jmeno = (string)$c['jmeno'];
            $klub = $nasi && !empty($c['klub']);
            if ($klub) {
                $mezera = mb_strrpos(rtrim($jmeno), ' ');           // mezera na konci jména se nepočítá
                $konec = $mezera === false ? $jmeno : mb_substr($jmeno, $mezera + 1);
                if (mb_strlen($konec) > 14) $konec = mb_substr($jmeno, -6);
                $obsah = e(mb_substr($jmeno, 0, mb_strlen($jmeno) - mb_strlen($konec)))
                       . '<span class="uvc-zapas__se-znakem">' . e($konec) . pointeks_znak_html() . '</span>';
            } else {
                $obsah = e($jmeno);
            }
            $zeme = (string)($c['zeme'] ?? '') !== '' ? '&nbsp;<span class="uvc-zapas__zeme">' . e((string)$c['zeme']) . '</span>' : '';
            $h .= '<span class="uvc-zapas__jmeno' . ($klub ? ' uvc-zapas__jmeno--klub' : '') . '">' . $obsah . $zeme . '</span>';
        }
        return $h;
    };

    /* Jedna výsledková tabule (zápas z pointeks_zapas()) */
    $tabule = static function (array $z) use ($strana, $letos): string {
        $cas = ($z['cas'] ?? null) instanceof DateTimeImmutable ? $z['cas'] : null;
        $sety = array_slice(array_values(array_filter((array)($z['sety'] ?? []), 'is_array')), 0, 5);
        $prubeh = (string)($z['prubeh'] ?? '');
        $bezBoje = $prubeh === 'WALKOVER';
        $bezSetu = $bezBoje || !$sety;
        $zive = !empty($z['zive']);
        $vysledek = (string)($z['vysledek'] ?? '');
        $verdikt = $vysledek === 'WIN' ? 'výhra' : ($vysledek === 'LOSS' ? 'prohra' : '');
        $vitez = $zive || $verdikt === '' ? 0 : ($vysledek === 'WIN' ? 1 : 2);       // 1 = strana klubu, 2 = soupeř
        $hlavni = !empty($z['finale']) && empty($z['kvalifikace']);                  // finále kvalifikace není titul

        /* město (když chybí, název turnaje); pod ním řádek soutěže stejně jako na výsledkové tabuli webu */
        $mestoZ = (string)($z['mesto'] ?? '');
        $turnaj = (string)($z['turnaj'] ?? '');
        $mesto = $mestoZ !== '' ? $mestoZ : $turnaj;
        $titulek = $turnaj !== '' && $turnaj !== $mesto ? ' title="' . e($turnaj) . '"' : '';

        if ($zive) {
            $kdy = '<span class="uvc-zive"><span class="uvc-zive__bod"></span>živě</span>';
        } elseif ($cas) {
            $kdy = '<time datetime="' . e($cas->format('Y-m-d')) . '">' . e($cas->format('j.')) . '&nbsp;' . e($cas->format('n.'))
                 . ($cas->format('Y') !== $letos ? '<span class="uvc-zapas__rok">&nbsp;' . e($cas->format('Y')) . '</span>' : '') . '</time>';
        } else {
            $kdy = '';
        }
        /* štítek Titul / Finále stojí v řádku soutěže místo slova kola („ITF M15 · [Titul]“) – pointeks_soutez_html() */
        $stitek = '';
        if (!$zive && $hlavni && $vysledek === 'WIN') $stitek = '<span class="uvc-zapas__stitek uvc-zapas__stitek--titul">Titul</span>';
        elseif (!$zive && $hlavni && $vysledek === 'LOSS') $stitek = '<span class="uvc-zapas__stitek">Finále</span>';
        $soutez = pointeks_soutez_html($z, $stitek);

        /* dvě strany: nahoře klub (data-r 1), dole soupeř (data-r 2) */
        $nasi = $strana((array)($z['hraci'] ?? []), true);
        $souperi = $strana((array)($z['souperi'] ?? []), false);
        if ($souperi === '') $souperi = '<span class="uvc-zapas__jmeno">soupeř neuveden</span>';
        $stav = static fn(int $r): string => $vitez === 0 ? '' : ($vitez === $r ? ' uvc-zapas__strana--vitez' : ' uvc-zapas__strana--porazeny');
        $skore = '<span class="uvc-zapas__strana uvc-zapas__strana--nasi' . $stav(1) . '" data-r="1">' . $nasi . '</span>'
               . '<span class="uvc-zapas__strana uvc-zapas__strana--souperi' . $stav(2) . '" data-r="2">' . $souperi . '</span>';

        $pres = false;                                            // nápis bez setů přes oba řádky (bez dělicí linky v číselníku)
        if ($bezSetu) {
            /* „výhra / prohra bez boje“ v řádku klubu; bez výsledku („začíná“, „bez skóre“) přes oba řádky */
            $text = $bezBoje ? trim($verdikt . ' bez boje') : ($zive ? 'začíná' : 'bez skóre');
            $pres = !($bezBoje && $verdikt !== '');
            $skore .= '<span class="uvc-zapas__bez-setu"' . ($pres ? '' : ' data-r="1"') . '>' . e($text) . '</span>';
        } else {
            $posledni = count($sety) - 1;
            foreach ($sety as $i => $s) {
                $rozehrany = $zive && $i === $posledni;           // živý zápas: poslední set se ještě hraje
                foreach ([1 => ['h', 's', 'th'], 2 => ['s', 'h', 'ts']] as $r => [$moje, $cizi, $tb]) {
                    $vyhra = !$rozehrany && (int)($s[$moje] ?? 0) > (int)($s[$cizi] ?? 0);
                    $sup = isset($s[$tb]) ? '<sup>' . (int)$s[$tb] . '</sup>' : '';
                    $skore .= '<span class="uvc-zapas__set' . ($vyhra ? ' uvc-zapas__set--vyhra' : '') . '" data-r="' . $r . '" data-s="' . ($i + 1) . '">'
                            . '<span class="uvc-zapas__gemy">' . (int)($s[$moje] ?? 0) . $sup . '</span></span>';
                }
            }
        }

        $ctyrhra = !empty($z['ctyrhra']) || count((array)($z['hraci'] ?? [])) > 1 || count((array)($z['souperi'] ?? [])) > 1;
        $tridy = 'uvc-zapas' . ($zive ? ' uvc-zapas--zive' : '') . ($bezSetu ? ' uvc-zapas--bez-setu' : '') . ($ctyrhra ? ' uvc-zapas--ctyrhra' : '');
        return '<li class="' . $tridy . '">'
             . '<p class="vh">' . e(pointeks_zapas_veta($z)) . '</p>'
             . '<p class="uvc-zapas__hlava" aria-hidden="true"><span class="uvc-zapas__mesto"' . $titulek . '>' . e($mesto) . '</span>'
             . ($kdy !== '' ? '<span class="uvc-zapas__kdy">' . $kdy . '</span>' : '') . '</p>'
             . '<p class="uvc-zapas__soutez" aria-hidden="true">' . $soutez . '</p>'
             . '<div class="uvc-zapas__skore' . ($pres ? ' uvc-zapas__skore--pres' : '') . '" aria-hidden="true" data-sety="' . ($bezSetu ? 0 : count($sety)) . '">' . $skore . '</div>'
             . '</li>';
    };

    $karty = '';
    foreach ($zapasy as $z) $karty .= $tabule($z);

    /* Pozvánka do Pointeks: stejná adresa jako poděkování „Výsledky: Pointeks“ v sekci výsledků, navíc
       utm_content=uvod-pas, ať Petr i Pointeks poznají, kolik lidí přišlo z pásu (pointeks_pozvanka_url –
       stejně jako pozvánka v hlavě stránky Výsledky s utm_content=vysledky-hlava). */
    $kredit = pointeks_pozvanka_url($zapasy, 'uvod-pas');
    $ven = odkaz_je_externi($kredit);

    /* sekce--navy: barvy odkazů a obrys fokusu na navy ze styl.css; pozadí a vše ostatní uvod-vysledky.css.
       Pořadí v HTML (čtečky, Tab): nadpis, pozvánka Pointeks, tabule, Všechny výsledky – na počítači stojí
       odkaz Všechny výsledky vpravo v hlavě pásu (grid-area), na mobilu pod tabulemi. Seznam se nikde
       neposouvá do stran, proto nemá tabindex (není to zbytečná zastávka klávesnice). */
    return '<section class="uvc sekce--navy" id="vysledky-uvod" aria-labelledby="uvc-nadpis">'
         . '<div class="wrap uvc__plocha">'
         . '<h2 class="uvc__nadpis" id="uvc-nadpis">Naši hráči <em>na turnajích</em></h2>'
         . '<p class="uvc__pointeks"><a href="' . e($kredit) . '"' . odkaz_attr($kredit) . '>'
         . typo('Každý bod živě v aplikaci') . ' <span class="uvc__znacka">Pointeks</span>' . sipka($ven ? 'ven' : '')
         . ($ven ? '<span class="vh"> (v novém okně)</span>' : '') . '</a></p>'
         . '<ol class="uvc__tabule" role="list" data-pocet="' . count($zapasy) . '">' . $karty . '</ol>'
         . '<p class="uvc__vse"><a class="odkaz" href="' . e(url('vysledky.php')) . '">Všechny výsledky ' . sipka() . '</a></p>'
         . '</div></section>';
}
