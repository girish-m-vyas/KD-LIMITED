<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/src/autoload.php';

KD\Support\Env::load(APP_ROOT . '/.env');
date_default_timezone_set((string) KD\Support\Env::get('APP_TIMEZONE', 'Asia/Kolkata'));

function cli_ask(string $question, bool $hidden = false): string
{
    echo $question;
    if ($hidden && DIRECTORY_SEPARATOR === '/' && posix_isatty(STDIN)) {
        shell_exec('stty -echo');
        $value = (string) fgets(STDIN);
        shell_exec('stty echo');
        echo PHP_EOL;
    } else {
        $value = (string) fgets(STDIN);
    }
    return trim($value);
}

/** @return array<string, string|bool> */
function cli_options(array $argv): array
{
    $opts = [];
    foreach (array_slice($argv, 1) as $arg) {
        if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $arg, $m)) {
            $opts[$m[1]] = $m[2] ?? true;
        }
    }
    return $opts;
}
