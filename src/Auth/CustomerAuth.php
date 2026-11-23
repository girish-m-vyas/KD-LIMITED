<?php
declare(strict_types=1);

namespace KD\Auth;

use KD\Database;
use KD\Support\Password;
use KD\Support\Session;

final class CustomerAuth
{
    /** Failed logins allowed per IP address in the window (shared networks need more than per account). */
    private const IP_LIMIT = 30;

    public function __construct(
        private readonly Database $db,
        private readonly RateLimiter $limiter,
    ) {}

    /** @return 'ok'|'invalid'|'locked' */
    public function attempt(string $email, string $password, string $ip): string
    {
        $email     = strtolower(trim($email));
        $accountKey = 'customer:' . $email;
        $ipKey      = 'customer-ip:' . $ip;

        if ($this->limiter->isLocked($accountKey) || $this->limiter->isLocked($ipKey, self::IP_LIMIT)) {
            return 'locked';
        }

        $row  = $this->db->fetch('SELECT id, password_hash FROM customers WHERE email = ?', [$email]);
        $hash = $row['password_hash'] ?? Password::dummyHash();
        $ok   = Password::verify($password, $hash) && $row !== null;

        if (!$ok) {
            $this->limiter->hit($accountKey);
            $this->limiter->hit($ipKey);
            return 'invalid';
        }

        if (Password::needsRehash($hash)) {
            $this->db->execute('UPDATE customers SET password_hash = ? WHERE id = ?', [Password::hash($password), $row['id']]);
        }
        $this->limiter->clear($accountKey);
        $this->login((int) $row['id']);
        return 'ok';
    }

    public function login(int $customerId): void
    {
        Session::regenerate();
        $_SESSION['customer_id'] = $customerId;
    }

    public function logout(): void
    {
        unset($_SESSION['customer_id']);
        Session::regenerate();
    }

    public function id(): ?int
    {
        $id = $_SESSION['customer_id'] ?? null;
        return is_int($id) ? $id : null;
    }

    /** @return array<string, mixed>|null */
    public function current(): ?array
    {
        $id = $this->id();
        if ($id === null) {
            return null;
        }
        $row = $this->db->fetch('SELECT id, name, email, contact, city, address, image FROM customers WHERE id = ?', [$id]);
        if ($row === null) {
            unset($_SESSION['customer_id']);
        }
        return $row;
    }
}
