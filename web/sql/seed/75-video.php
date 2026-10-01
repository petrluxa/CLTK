<?php
defined('SEED_DATA_JSON') || exit;   // jen přes sql/seed.php nebo instalace.php, nikdy přímo
/* Video „Členem se může stát každý“ na úvodu (ZADANI §4.7).
   Soubory z podklady/video/ se kopírují BEZE ZMĚNY do uploads/video/
   (stejné cesty mají nastavení video_1080, video_720 a video_poster).
   Na serveru zdroje nejsou – uploads/ se tam nahrávají zvlášť
   a seed_kopiruj() jen najde hotový soubor. */

$n = 0;
foreach (['cltk-promo-1080.mp4', 'cltk-promo-720.mp4', 'cltk-promo-poster.jpg'] as $soubor) {
    if (seed_kopiruj(seed_podklady('video/' . $soubor), 'video/' . $soubor) !== '') $n++;
}
/* plakát i jako WebP (stránka ho může nabídnout přes <picture>) */
if (is_file(UPLOAD_DIR . '/video/cltk-promo-poster.jpg') && webp_vedle('video/cltk-promo-poster.jpg') === '') {
    vytvor_webp('video/cltk-promo-poster.jpg');
}
seed_log('Video členství: ' . $n . ' ze 3 souborů v uploads/video/.');
