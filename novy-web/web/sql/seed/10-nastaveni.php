<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Texty a údaje (cltk_settings). Zdroj: obsah.json → klub.*, PDF klienta (patička).
   Vkládají se jen chybějící klíče – co klub změnil, zůstane. */

$n = seed_nastaveni([
    // klíč, hodnota, popisek, skupina, typ, nápověda[, pořadí – výchozí = místo v tomto seznamu]

    /* --- Kontakty a adresa --- */
    ['klub_nazev', 'I. Český Lawn-Tennis Klub Praha', 'Název klubu', 'kontakt'],
    ['klub_zkratka', 'I. ČLTK Praha', 'Zkratka názvu', 'kontakt'],
    ['zalozeno', '1893', 'Rok založení', 'kontakt', 'cislo'],
    ['ico', '45243077', 'IČO', 'kontakt'],
    ['adresa_ulice', 'Ostrov Štvanice 38', 'Adresa – ulice', 'kontakt'],
    ['adresa_mesto', '170 00 Praha 7', 'Adresa – PSČ a město', 'kontakt'],
    ['prijezd_kratce', 'Z Hlávkova mostu (tramvaj č. 14, brána pro pěší) nebo po lávce HolKa z Karlína a Holešovic. Autem na parkoviště u Negrelliho viaduktu, zadním vchodem.',
        'Příjezd – krátce (patička)', 'kontakt', 'textarea'],
    ['prijezd_text', 'Tenisový areál I. ČLTK Praha se nachází na ostrově Štvanice s možným přístupem z Hlávkova mostu nebo z Karlína i Holešovic přes lávku HolKa. Na Hlávkově mostě je zastávka tramvaje č. 14 a stanice metra B i C jsou vzdálené jen několik minut chůze. Brána pro pěší vstup se nachází přímo u příchodu z mostu. Pro příjezd autem je lepší po sjezdu z magistrály areál zprava objet, zaparkovat na vyhrazeném parkovišti a do areálu vejít zadním vchodem.',
        'Příjezd – podrobně (kontakt, areál)', 'kontakt', 'textarea'],
    ['mapa_url', 'https://www.google.com/maps/search/?api=1&query=Ostrov+%C5%A0tvanice+38+Praha', 'Odkaz „Mapa“', 'kontakt', 'url'],
    /* Na koho se obrátit (skupina „lide“, karty Recepce a Kancelář na stránce Kontakt). Řádky zůstávají
       na původním místě (pořadí ostatních klíčů se tak nemění); pořadí ve skupině je napevno (7. sloupec),
       ať odkaz Obsazenost kurtů stojí hned pod recepcí. Stejný stav nastaví na serveru migrace
       sql/migrace/2026-10-05-na-koho-se-obratit.php. */
    ['recepce_popis', 'rezervace kurtů', 'Recepce – popis', 'lide', 'text', '', 9],
    ['recepce_telefon', '+420 608 974 974', 'Recepce – telefon', 'lide', 'tel', 'Zobrazuje se v patičce, v mobilní liště „Zavolat recepci“ a na přípravné stránce.', 10],
    ['recepce_email', 'recepce@cltk.cz', 'Recepce – e-mail', 'lide', 'email', '', 11],
    ['kancelar_jmeno', 'Eva Štefková', 'Kancelář – jméno', 'lide', 'text', 'Tato osoba má na webu vlastní kartu Kancelář, v seznamu lidí níže se už neopakuje.', 13],
    ['kancelar_popis', 'kancelář, členství, stálé rezervace · Po–Pá 9:00–17:00', 'Kancelář – popis', 'lide', 'text', '', 14],
    ['kancelar_telefon', '+420 737 215 012', 'Kancelář – telefon', 'lide', 'tel', '', 15],
    ['kancelar_email', 'stefkova@cltk.cz', 'Kancelář – e-mail', 'lide', 'email', '', 16],
    ['ucet_clenstvi', '1471509/0300', 'Účet pro členství a tréninky', 'kontakt', 'text', 'ČSOB Praha a.s.'],
    ['ucet_iban', 'CZ20 0300 0000 0000 0147 1509', 'IBAN účtu pro členství', 'kontakt'],
    ['ucet_skola', '312451935/0300', 'Účet Tenisové školy a kempů', 'kontakt'],

    /* --- Patička --- */
    ['paticka_ctc', 'Jediný český člen Centenary Tennis Clubs · člen Českého tenisového svazu, klub č. 52', 'Patička – věta pod dokumenty', 'paticka', 'textarea'],
    ['paticka_pristupnost', 'Web respektuje nastavení vašeho zařízení. Volbu si zapamatujeme.', 'Patička – text u přístupnosti', 'paticka', 'textarea'],
    ['paticka_pruh', 'I. Český Lawn-Tennis Klub Praha · IČO 45243077', 'Patička – spodní pruh (za „© rok“)', 'paticka', 'text', '„© rok“ se doplní automaticky.'],

    /* --- Odkazy a rezervace --- */
    ['rezervace_url', 'https://www.rogeronline.cz/v2/index.php?klub=181', 'Rezervace kurtů (tlačítko Rezervovat kurt)', 'odkazy', 'url', 'Otevírá se v novém okně.'],
    ['obsazenost_url', 'https://www.rogeronline.cz/v2/index.php?klub=181', 'Obsazenost kurtů – odkaz', 'lide', 'url',
        'Ukazuje se na kartě Recepce na stránce Kontakt, v horní liště webu a na stránce Ceník kurtů. Otevírá se v novém okně.', 12],
    ['restaurace_url', '', 'Web restaurace (položka menu Restaurace)', 'odkazy', 'url', 'Prázdné = menu vede na stránku Restaurace na tomto webu („připravujeme“).'],
    ['prague_open_url', '', 'Web Prague Open (položka menu Prague Open)', 'odkazy', 'url', 'Prázdné = menu vede na stránku Prague Open na tomto webu.'],
    ['kempy_prihlaska_url', 'https://forms.gle/JvMQNKJJmoBareVa7', 'Přihláška na letní kempy', 'odkazy', 'url'],

    /* --- Sociální sítě --- */
    ['facebook_url', 'https://www.facebook.com/cltk.fb', 'Facebook', 'site', 'url'],
    ['instagram_url', 'https://www.instagram.com/cltk.insta/', 'Instagram', 'site', 'url'],
    ['youtube_url', '', 'YouTube', 'site', 'url'],
    ['fotogalerie_url', 'https://cltk.dphoto.com/albums', 'Fotogalerie klubu', 'site', 'url'],

    /* --- Úvodní strana – video členství --- */
    ['video_1080', 'video/cltk-promo-1080.mp4', 'Video členství – 1080p (soubor v uploads/)', 'uvod', 'soubor'],
    ['video_720', 'video/cltk-promo-720.mp4', 'Video členství – 720p pro mobil (soubor v uploads/)', 'uvod', 'soubor'],
    ['video_poster', 'video/cltk-promo-poster.jpg', 'Video členství – plakát (soubor v uploads/)', 'uvod', 'soubor'],

    /* --- Přihlášky a e-mail --- */
    ['prihlasky_email', '', 'E-mail pro upozornění na přihlášky', 'prihlasky', 'email',
        'Prázdné = přihlášky se jen ukládají do administrace a nic se neposílá.'],
    ['prihlasky_mazat_mesicu', '12', 'Mazat staré přihlášky po (měsících)', 'prihlasky', 'cislo',
        'Vyřízené a zamítnuté přihlášky do klubu a všechny přihlášky k akcím starší než tolik měsíců se samy smažou – osobní údaje se nemají držet déle, než je potřeba. Nové přihlášky do klubu zůstávají vždy. 0 = nemazat.'],

    /* --- Režim přípravy a náhled --- */
    ['rezim_pripravy', '1', 'Režim přípravy', 'rezim', 'bool',
        'Zapnuto = návštěvníci vidí přípravnou stránku; přihlášený správce a lidé s náhledovým heslem vidí celý web.'],
    ['rezim_nadpis', 'Připravujeme <em>nový</em> web', 'Přípravná stránka – nadpis', 'rezim', 'inline'],
    ['rezim_text', 'Pracujeme na nové podobě stránek I. Českého Lawn-Tennis Klubu Praha. Kurty si mezitím můžete rezervovat online nebo na recepci.',
        'Přípravná stránka – text', 'rezim', 'textarea'],
    ['nahled_heslo_hash', '', 'Náhledové heslo', 'rezim', 'heslo',
        'Kdo heslo zadá na přípravné stránce, uvidí web i bez účtu (např. lidé z klubu). Ukládá se jen otisk hesla.'],

    /* --- Systém (v administraci se nezobrazuje) --- */
    ['system_klic', bin2hex(random_bytes(32)), 'Tajný klíč (needitovat)', 'system'],
    ['schema_verze', '1', 'Verze schématu databáze', 'system'],
]);
seed_log('Nastavení: ' . $n . ' nových údajů.');
