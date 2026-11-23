<?php
declare(strict_types=1);

/**
 * PSR-4 style autoloader for the KD\ namespace. No Composer needed,
 * so the project runs as-is on XAMPP, shared hosting or `php -S`.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'KD\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require_once __DIR__ . '/helpers.php';
