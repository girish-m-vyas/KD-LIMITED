<?php
declare(strict_types=1);

// Single entry point. Only this folder (public/) is exposed by the web server;
// code, config, the database and uploads all live outside it.

define('APP_ROOT', dirname(__DIR__));

// When using `php -S`, let the built-in server send real static files directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

require APP_ROOT . '/src/autoload.php';

KD\Kernel::boot();
KD\Kernel::handle();
