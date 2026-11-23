<?php
declare(strict_types=1);

namespace KD\Auth;

use KD\Database;
use KD\Services\AuditLog;
use KD\Support\Password;
use KD\Support\Session;

/**
 * Admin login. The admin's role and active flag are re-read from the database
 * on every request, so deactivating someone or changing their role takes
 * effect immediately, and editing the session can't grant extra rights.
 */
final class AdminAuth
{
    /** @var array<string, mixed>|null|false false = not loaded yet */
    private array|null|false $cached = false;

    /** Failed logins allowed per IP address in the window (shared networks need more than per account). */
    private const IP_LIMIT = 30;

    public function __construct(
        private readonly Database $db,
        private readonly RateLimiter $limiter,
        private readonly AuditLog $audit,
    ) {}

    /** @return 'ok'|'invalid'|'locked' */
    public function attempt(string $email, string $password, string $ip): string
    {
        $email      = strtolower(trim($email));
        $accountKey = 'admin:' . $email;
        $ipKey      = 'admin-ip:' . $ip;

        if ($this->limiter->isLocked($accountKey) || $this->limiter->isLocked($ipKey, self::IP_LIMIT)) {
            $this->audit->record(null, 'admin.login_locked', 'admin', null, ['email' => $email], $ip);
            return 'locked';
        }

        $row  = $this->db->fetch('SELECT id, password_hash, is_active FROM admins WHERE email = ?', [$email]);
        $hash = $row['password_hash'] ?? Password::dummyHash();
        $ok   = Password::verify($password, $hash) && $row !== null && (int) $row['is_active'] === 1;

        if (!$ok) {
            $this->limiter->hit($accountKey);
            $this->limiter->hit($ipKey);
            $this->audit->record($row ? (int) $row['id'] : null, 'admin.login_failed', 'admin', $row ? (int) $row['id'] : null, ['email' => $email], $ip);
            return 'invalid';
        }

        $id = (int) $row['id'];
        if (Password::needsRehash($hash)) {
            $this->db->execute('UPDATE admins SET password_hash = ? WHERE id = ?', [Password::hash($password), $id]);
        }
        $this->limiter->clear($accountKey);
        $this->db->execute('UPDATE admins SET last_login_at = ? WHERE id = ?', [now(), $id]);

        Session::regenerate();
        $_SESSION['admin_id'] = $id;
        $this->cached = false;
        $this->audit->record($id, 'admin.login', 'admin', $id, [], $ip);
        return 'ok';
    }

    public function logout(?string $ip = null): void
    {
        $id = $this->id();
        if ($id !== null) {
            $this->audit->record($id, 'admin.logout', 'admin', $id, [], $ip);
        }
        unset($_SESSION['admin_id']);
        $this->cached = false;
        Session::regenerate();
    }

    public function id(): ?int
    {
        $id = $_SESSION['admin_id'] ?? null;
        return is_int($id) ? $id : null;
    }

    /** @return array<string, mixed>|null the active admin, or null */
    public function current(): ?array
    {
        if ($this->cached !== false && ($this->cached === null || (int) $this->cached['id'] === $this->id())) {
            return $this->cached;
        }
        $id = $this->id();
        if ($id === null) {
            return $this->cached = null;
        }
        $row = $this->db->fetch(
            'SELECT id, name, email, role, image, job, contact, about, is_active, must_change_password FROM admins WHERE id = ?',
            [$id]
        );
        if ($row === null || (int) $row['is_active'] !== 1 || !Rbac::isRole((string) $row['role'])) {
            unset($_SESSION['admin_id']);
            return $this->cached = null;
        }
        return $this->cached = $row;
    }

    public function can(string $permission): bool
    {
        $admin = $this->current();
        return $admin !== null && Rbac::can((string) $admin['role'], $permission);
    }

    public function refresh(): void
    {
        $this->cached = false;
    }
}
