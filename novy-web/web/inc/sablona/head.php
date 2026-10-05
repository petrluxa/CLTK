<?php
/* <head> veřejného webu – vkládá ho hlavicka.php. Čte pole $sablona:
     titulek   – název stránky („Areál a služby“); na úvodu prázdný
     popis     – meta description (prostý text, zkrátí se na 160 znaků)
     css       – další styly z assets/css/ (např. ['v2.css', 'stranky-klub.css'])
     js        – další skripty z assets/js/ (defer, např. ['kiosek.js'])
     obrazek   – fotka pro sdílení (cesta v uploads/), jinak logo
     noindex   – true = nezařazovat do vyhledávačů (404, přihláška odeslána)
     predpripojit – [['href' => …, 'as' => 'image', 'type' => 'image/webp'], …] (hero fotka)
   Režim přípravy přidává noindex sám (rezim_meta_robots()). */

$klubZkratka = setting('klub_zkratka', 'I. ČLTK Praha');
$titulekStranky = trim((string)($sablona['titulek'] ?? ''));
$celyTitulek = $titulekStranky !== ''
    ? $titulekStranky . ' – ' . $klubZkratka
    : $klubZkratka . ' – tenis na ostrově Štvanice od roku ' . setting('zalozeno', '1893');
$popisStranky = uryvek((string)($sablona['popis'] ?? ''), 160)
    ?: 'I. Český Lawn-Tennis Klub Praha – nejstarší tenisový klub v Praze, na ostrově Štvanice. Členství, kurty a ceník, tenisová škola, závodní tenis a historie klubu.';
$obrazekSdileni = trim((string)($sablona['obrazek'] ?? '')) !== '' ? upload_url((string)$sablona['obrazek']) : logo_url('512');
$obrazekSdileni = preg_match('~^https?://~i', $obrazekSdileni) ? $obrazekSdileni : site_url(substr($obrazekSdileni, strlen(BASE_PATH)));
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($celyTitulek) ?></title>
<meta name="description" content="<?= e($popisStranky) ?>">
<?= rezim_meta_robots() ?>
<?php if (!empty($sablona['noindex']) && !rezim_je_zapnuty()): ?><meta name="robots" content="noindex, follow">
<?php endif; ?>
<meta name="theme-color" content="#fbfaf6">
<meta name="format-detection" content="telephone=no">
<link rel="icon" href="<?= e(logo_url('favicon')) ?>" type="image/png" sizes="64x64">
<link rel="apple-touch-icon" href="<?= e(logo_url('256')) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="cs_CZ">
<meta property="og:site_name" content="<?= e(setting('klub_nazev', 'I. Český Lawn-Tennis Klub Praha')) ?>">
<meta property="og:title" content="<?= e($celyTitulek) ?>">
<meta property="og:description" content="<?= e($popisStranky) ?>">
<meta property="og:url" content="<?= e(site_url(substr((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), strlen(BASE_PATH)) ?: '')) ?>">
<meta property="og:image" content="<?= e($obrazekSdileni) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300..700;1,300..700&amp;family=Cormorant+SC:wght@400;500;600&amp;family=Jost:wght@300..600&amp;display=swap&amp;subset=latin-ext">
<?php foreach ((array)($sablona['predpripojit'] ?? []) as $pp): if (empty($pp['href'])) continue; ?>
<link rel="preload" href="<?= e((string)$pp['href']) ?>" as="<?= e((string)($pp['as'] ?? 'image')) ?>"<?= !empty($pp['type']) ? ' type="' . e((string)$pp['type']) . '"' : '' ?><?= !empty($pp['fetchpriority']) ? ' fetchpriority="' . e((string)$pp['fetchpriority']) . '"' : '' ?>>
<?php endforeach; ?>
<link rel="stylesheet" href="<?= e(asset('css/styl.css')) ?>">
<?php foreach ((array)($sablona['css'] ?? []) as $cssSoubor): ?>
<link rel="stylesheet" href="<?= e(asset('css/' . ltrim((string)$cssSoubor, '/'))) ?>">
<?php endforeach; ?>
<script><?= SABLONA_SKRIPT_HLAVICKY /* CSP ho pouští podle otisku – viz komponenty.php */ ?></script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php foreach ((array)($sablona['js'] ?? []) as $jsSoubor): ?>
<script src="<?= e(asset('js/' . ltrim((string)$jsSoubor, '/'))) ?>" defer></script>
<?php endforeach; ?>
