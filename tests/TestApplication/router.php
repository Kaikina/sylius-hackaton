<?php

declare(strict_types=1);

/*
 * Router for PHP's built-in server:
 *   php -S 127.0.0.1:8000 -t vendor/sylius/test-application/public tests/TestApplication/router.php
 *
 * Without it, php -S answers 404 itself for any missing path with a file extension, so URLs such as
 * LiipImagine's /media/cache/resolve/<filter>/<image>.webp never reach Symfony and thumbnails stay broken.
 */
$publicDir = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url($_SERVER['REQUEST_URI'], \PHP_URL_PATH);

if (is_string($path) && '/' !== $path && is_file($publicDir . $path)) {
    return false;
}

$_SERVER['SCRIPT_FILENAME'] = $publicDir . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require $publicDir . '/index.php';
