<?php
/* Směrovač pro lokální vestavěný server PHP (php -S … nastroje/router.php).

   Napodobuje to, co na serveru dělají soubory .htaccess:
     – inc/, data/, sql/, admin/inc/ a citlivé přípony (.sqlite, .sql, .md, .log …) → 403,
     – v uploads/ se nic nespouští (.php, .html, .svg, .js → 403),
     – neexistující adresa → 404.php (jako RewriteRule ^ 404.php).
   Volitelně napodobí i běh v PODSLOŽCE (na testu /cltkv2/): proměnná
   prostředí CLTK_PODSLOZKA=/cltkv2 → web je k vidění na
   http://127.0.0.1:PORT/cltkv2/ a odhalí se každá natvrdo zapsaná cesta „/…“.

   Stránka webu se vkládá na nejvyšší úrovni (ne ve funkci), aby běžela
   ve stejném globálním rozsahu jako na serveru; proměnné směrovače mají
   předponu $__r.

   Spouští ho nastroje/spustit.sh. Na server se nenahrává (není ve web/). */

$__rKoren = rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');
$__rPodslozka = str_replace('\\', '/', (string)getenv('CLTK_PODSLOZKA'));
if (preg_match('~^[A-Za-z]:/~', $__rPodslozka)) $__rPodslozka = basename($__rPodslozka);   // Git Bash z „/cltkv2“ udělá cestu Windows
$__rPodslozka = '/' . trim($__rPodslozka, '/');
if ($__rPodslozka === '/') $__rPodslozka = '';

$__rCesta = rawurldecode((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH));
if (str_contains($__rCesta, "\0") || str_contains($__rCesta, '..')) {
    http_response_code(400);
    return true;
}

/* podsložka: /cltkv2/… → /…; mimo ni nic není */
if ($__rPodslozka !== '') {
    if ($__rCesta === $__rPodslozka) {
        header('Location: ' . $__rPodslozka . '/', true, 301);
        return true;
    }
    if (!str_starts_with($__rCesta, $__rPodslozka . '/')) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Web běží v podsložce $__rPodslozka/ – otevřete http://" . ($_SERVER['HTTP_HOST'] ?? '127.0.0.1') . "$__rPodslozka/";
        return true;
    }
    $__rCesta = substr($__rCesta, strlen($__rPodslozka));
}

/* zákazy jako v .htaccess */
if (preg_match('~^/(inc|data|sql|admin/inc)(/|$)~i', $__rCesta)
    || preg_match('~/\.(htaccess|htpasswd|git|env)~i', $__rCesta)
    || preg_match('~\.(sqlite|sqlite-journal|sqlite-wal|sqlite-shm|sql|md|log|ini|sh|bak|dist)$~i', $__rCesta)
    || preg_match('~(^|/)config\.local\.php$~i', $__rCesta)
    || preg_match('~^/uploads/.*\.(php\d?|phtml|phar|pht|phps|cgi|pl|py|sh|html?|svg|js)$~i', $__rCesta)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '403 – zakázáno (stejně jako na serveru přes .htaccess)';
    return true;
}

$__rSoubor = $__rKoren . $__rCesta;
if (is_dir($__rSoubor)) {
    if (!str_ends_with($__rCesta, '/')) {
        header('Location: ' . $__rPodslozka . $__rCesta . '/', true, 301);
        return true;
    }
    $__rSoubor .= 'index.php';
    $__rCesta .= 'index.php';
}

$__rSpustit = null;                                   // [soubor, cesta, kód] stránky PHP, která se má vložit
if (is_file($__rSoubor)) {
    if (preg_match('~\.php$~i', $__rSoubor)) {
        if ($__rPodslozka === '') return false;      // vestavěný server to zvládne sám
        $__rSpustit = [$__rSoubor, $__rCesta, 200];
    } elseif ($__rPodslozka === '') {
        return false;                                 // statický soubor bez podsložky
    } else {
        /* statický soubor v podsložce – vydat sami (i s Range kvůli videu v Safari) */
        $__rTypy = ['css' => 'text/css; charset=UTF-8', 'js' => 'text/javascript; charset=UTF-8', 'json' => 'application/json',
                    'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif',
                    'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'mp4' => 'video/mp4', 'pdf' => 'application/pdf',
                    'woff2' => 'font/woff2', 'txt' => 'text/plain; charset=UTF-8', 'xml' => 'application/xml'];
        $__rVelikost = (int)filesize($__rSoubor);
        header('Content-Type: ' . ($__rTypy[strtolower(pathinfo($__rSoubor, PATHINFO_EXTENSION))] ?? 'application/octet-stream'));
        header('Accept-Ranges: bytes');
        $__rOd = 0;
        $__rDo = $__rVelikost - 1;
        if (preg_match('/^bytes=(\d*)-(\d*)$/', (string)($_SERVER['HTTP_RANGE'] ?? ''), $__rM) && ($__rM[1] !== '' || $__rM[2] !== '')) {
            if ($__rM[1] === '') {
                $__rOd = max(0, $__rVelikost - (int)$__rM[2]);
            } else {
                $__rOd = (int)$__rM[1];
                if ($__rM[2] !== '') $__rDo = min($__rDo, (int)$__rM[2]);
            }
            if ($__rOd > $__rDo || $__rOd >= $__rVelikost) {
                http_response_code(416);
                header('Content-Range: bytes */' . $__rVelikost);
                return true;
            }
            http_response_code(206);
            header("Content-Range: bytes $__rOd-$__rDo/$__rVelikost");
        }
        header('Content-Length: ' . ($__rDo - $__rOd + 1));
        $__rF = fopen($__rSoubor, 'rb');
        fseek($__rF, $__rOd);
        for ($__rZbyva = $__rDo - $__rOd + 1; $__rZbyva > 0 && !feof($__rF); $__rZbyva -= strlen($__rKus)) {
            $__rKus = (string)fread($__rF, (int)min(65536, $__rZbyva));
            echo $__rKus;
        }
        fclose($__rF);
        return true;
    }
} elseif (is_file($__rKoren . '/404.php')) {
    $__rSpustit = [$__rKoren . '/404.php', '/404.php', 404];   // neexistuje → 404.php
} else {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '404 – stránka neexistuje';
    return true;
}

/* vložení stránky na nejvyšší úrovni se SCRIPT_NAME jako na serveru (kvůli BASE_PATH) */
if ($__rSpustit[2] !== 200) http_response_code($__rSpustit[2]);
$_SERVER['SCRIPT_NAME'] = $__rPodslozka . $__rSpustit[1];
$_SERVER['PHP_SELF'] = $__rPodslozka . $__rSpustit[1];
$_SERVER['SCRIPT_FILENAME'] = $__rSpustit[0];
chdir(dirname($__rSpustit[0]));
$__rSoubor = $__rSpustit[0];
unset($__rKoren, $__rPodslozka, $__rCesta, $__rSpustit);
require $__rSoubor;
return true;
