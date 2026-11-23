<?php
declare(strict_types=1);

/**
 * Creates the database tables.
 *
 *   php bin/migrate.php           create tables (database must be empty)
 *   php bin/migrate.php --fresh   SQLite only: delete the database file first
 */

require __DIR__ . '/bootstrap-cli.php';

use KD\Database;
use KD\Support\Env;

$opts   = cli_options($argv);
$driver = (string) Env::get('DB_DRIVER', 'mysql');

if ($driver === 'sqlite') {
    $path = (string) (Env::get('DB_PATH') ?: APP_ROOT . '/storage/database.sqlite');
    if (isset($opts['fresh']) && is_file($path)) {
        unlink($path);
        echo "Deleted {$path}\n";
    }
    if (!is_file($path)) {
        touch($path);
        chmod($path, 0640);
    }
}

$db = Database::fromEnv();
$existing = $driver === 'sqlite'
    ? $db->value("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'admins'")
    : $db->value("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'admins'");

if ((int) $existing > 0) {
    echo "Tables already exist. Nothing to do.\n";
    exit(0);
}

$db->runSqlFile(APP_ROOT . '/database/schema.' . ($driver === 'sqlite' ? 'sqlite' : 'mysql') . '.sql');
echo "Database tables created.\n";
echo "Next: php bin/create-admin.php   (and optionally php bin/seed.php for demo data)\n";
