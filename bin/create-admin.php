<?php
declare(strict_types=1);

/**
 * Creates an admin account. There is no default admin and no password in the repo.
 *
 * Interactive:      php bin/create-admin.php
 * Non-interactive:  KD_ADMIN_PASSWORD='…' php bin/create-admin.php --email=you@example.com --name="Your Name" --role=super_admin
 *
 * The password is never accepted as a command-line argument, because those
 * end up in shell history and process lists.
 */

require __DIR__ . '/bootstrap-cli.php';

use KD\Auth\Rbac;
use KD\Database;
use KD\Services\AuditLog;
use KD\Support\Password;

$opts  = cli_options($argv);
$email = strtolower((string) ($opts['email'] ?? cli_ask('Admin email: ')));
$name  = (string) ($opts['name'] ?? cli_ask('Full name: '));
$role  = (string) ($opts['role'] ?? 'super_admin');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "That is not a valid email address.\n");
    exit(1);
}
if ($name === '') {
    fwrite(STDERR, "Name is required.\n");
    exit(1);
}
if (!Rbac::isRole($role)) {
    fwrite(STDERR, 'Role must be one of: ' . implode(', ', array_keys(Rbac::ROLES)) . "\n");
    exit(1);
}

$password = getenv('KD_ADMIN_PASSWORD') ?: '';
if ($password === '') {
    $password = cli_ask('Password: ', true);
    if (!hash_equals($password, cli_ask('Repeat password: ', true))) {
        fwrite(STDERR, "Passwords do not match.\n");
        exit(1);
    }
}
if ($err = Password::policyError($password, $email)) {
    fwrite(STDERR, $err . "\n");
    exit(1);
}

$db = Database::fromEnv();
if ($db->value('SELECT 1 FROM admins WHERE LOWER(email) = ?', [$email]) !== null) {
    fwrite(STDERR, "An admin with that email already exists.\n");
    exit(1);
}
$now = now();
$id = $db->insert(
    'INSERT INTO admins (name, email, password_hash, role, about, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
    [$name, $email, Password::hash($password), $role, '', $now, $now]
);
(new AuditLog($db))->record(null, 'admin.created_cli', 'admin', $id, ['email' => $email, 'role' => $role]);

echo "Created {$role} {$email} (id {$id}). Log in at /admin/login\n";
