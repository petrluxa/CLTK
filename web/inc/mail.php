<?php
/* Upozornění e-mailem na nové přihlášky.

   POSÍLÁ SE JEN TEHDY, když je v Textech a údajích vyplněný
   „E-mail pro přihlášky“ (prihlasky_email). Výchozí je prázdný
   = přihlášky se jen ukládají do administrace a nic se neposílá.

   Selhání odeslání NIKDY nesmí shodit stránku ani zahodit uloženou
   přihlášku – proto se vše chytá a tiše vrací false. */

require_once __DIR__ . '/functions.php';

/** Adresa, kam chodí upozornění; prázdná = neposílat. */
function prihlasky_email(): string {
    $e = trim(setting('prihlasky_email'));
    return je_email($e) ? $e : '';
}

/** Odkaz do administrace pro e-mail. Na serveru jen ze SITE_URL (cltk-config.php) –
 *  nikdy z hlavičky Host, kterou posílá návštěvník. Bez SITE_URL odkaz vynechá
 *  a zapíše varování do chyby.log. */
function mail_odkaz(string $cesta): string {
    if (SITE_URL !== '') return rtrim(SITE_URL, '/') . '/' . ltrim($cesta, '/');
    if (DB_DRIVER === 'mysql') {
        error_log('[mail] V cltk-config.php chybí SITE_URL – e-mail odchází bez odkazu do administrace.');
        return '';
    }
    return site_url($cesta);             // lokálně (SQLite) stačí adresa z požadavku
}

/** Odešle prostý e-mail v UTF-8. */
function send_mail(string $komu, string $predmet, string $telo, string $odpovedetNa = ''): bool {
    if (!je_email($komu)) return false;
    $host = (string)(SITE_URL !== '' ? (parse_url(SITE_URL, PHP_URL_HOST) ?: 'cltk.cz') : 'cltk.cz');
    $host = preg_replace('/^www\./', '', $host);
    if (!preg_match('/^[a-z0-9.\-]+$/i', $host) || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) $host = 'cltk.cz';
    $od = 'web@' . $host;

    $hlavicky = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: ' . mb_encode_mimeheader(setting('klub_zkratka', SITE_NAME), 'UTF-8') . ' <' . $od . '>',
        'X-Mailer: CLTK web',
    ];
    if (je_email($odpovedetNa)) $hlavicky[] = 'Reply-To: ' . str_replace(["\r", "\n"], '', $odpovedetNa);

    try {
        return @mail($komu, mb_encode_mimeheader($predmet, 'UTF-8'), $telo, implode("\r\n", $hlavicky));
    } catch (Throwable $e) {
        return false;
    }
}

/** Upozorní klub na novou přihlášku k akci (jen když je nastavený e-mail). */
function upozorni_prihlaska_akce(array $akce, array $radek): bool {
    $komu = prihlasky_email();
    if ($komu === '') return false;
    $r = [
        'Na webu přišla nová přihláška k akci.',
        '',
        'Akce:       ' . ($akce['nazev'] ?? '') . ' (' . akce_termin($akce, true) . ')',
        'Jméno:      ' . ($radek['jmeno'] ?? ''),
        'E-mail:     ' . ($radek['email'] ?? ''),
    ];
    if (($radek['telefon'] ?? '') !== '') $r[] = 'Telefon:    ' . $radek['telefon'];
    if ((int)($radek['pocet'] ?? 1) > 1)  $r[] = 'Počet osob: ' . (int)$radek['pocet'];
    if (trim((string)($radek['poznamka'] ?? '')) !== '') $r[] = 'Poznámka:   ' . trim((string)$radek['poznamka']);
    foreach (json_pole((string)($radek['odpovedi'] ?? '')) as $o) {
        $r[] = ($o['popisek'] ?? '') . ': ' . ($o['hodnota'] ?? '');
    }
    $r[] = '';
    $odkaz = mail_odkaz('admin/prihlasky.php');
    $r[] = $odkaz !== '' ? 'Přihlášky v administraci: ' . $odkaz : 'Přihlášky najdete v administraci webu (modul Přihlášky).';
    return send_mail($komu, 'Přihláška: ' . ($akce['nazev'] ?? 'akce'), implode("\n", $r), (string)($radek['email'] ?? ''));
}

/** Upozorní kancelář na novou přihlášku do klubu (jen když je nastavený e-mail). */
function upozorni_prihlaska_clenstvi(array $radek): bool {
    $komu = prihlasky_email();
    if ($komu === '') return false;
    $r = [
        'Na webu přišla nová přihláška do klubu.',
        '',
        'Žadatel:   ' . trim(($radek['jmeno'] ?? '') . ' ' . ($radek['prijmeni'] ?? '')),
        'E-mail:    ' . ($radek['email'] ?? ''),
        'Telefon:   ' . ($radek['telefon'] ?? ''),
        'Členství:  ' . ($radek['typ_clenstvi'] ?? ''),
        'Osob:      ' . (int)($radek['pocet_osob'] ?? 1),
        '',
    ];
    $odkaz = mail_odkaz('admin/prihlasky.php?druh=clenstvi');
    $r[] = $odkaz !== '' ? 'Celá přihláška je v administraci: ' . $odkaz : 'Celá přihláška je v administraci webu (modul Přihlášky).';
    return send_mail($komu, 'Přihláška do klubu: ' . trim(($radek['jmeno'] ?? '') . ' ' . ($radek['prijmeni'] ?? '')), implode("\n", $r), (string)($radek['email'] ?? ''));
}
