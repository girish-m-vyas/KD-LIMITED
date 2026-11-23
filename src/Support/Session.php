<?php
declare(strict_types=1);

namespace KD\Support;

/**
 * Hardened session start: strict mode, HttpOnly + SameSite cookies (Secure on
 * HTTPS), idle and absolute timeouts, and a fresh ID on every privilege change.
 */
final class Session
{
    public static function start(bool $https): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');

        session_name($https ? '__Host-kd_session' : 'kd_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        self::enforceTimeouts();
    }

    private static function enforceTimeouts(): void
    {
        $now      = time();
        $isAdmin  = isset($_SESSION['admin_id']);
        $idle     = $isAdmin ? (int) Env::get('ADMIN_IDLE_TIMEOUT', 1800) : (int) Env::get('SESSION_IDLE_TIMEOUT', 7200);
        $absolute = (int) Env::get('SESSION_ABSOLUTE_TIMEOUT', 43200);

        $last    = (int) ($_SESSION['_last_seen'] ?? $now);
        $started = (int) ($_SESSION['_started'] ?? $now);

        if (($now - $last) > $idle || ($now - $started) > $absolute) {
            $hadLogin = isset($_SESSION['admin_id']) || isset($_SESSION['customer_id']);
            $_SESSION = [];
            session_regenerate_id(true);
            $started = $now;
            if ($hadLogin) {
                $_SESSION['_flash'][] = ['type' => 'info', 'message' => 'Your session expired. Please log in again.'];
            }
        }
        $_SESSION['_last_seen'] = $now;
        $_SESSION['_started']   = $started;
    }

    /** Call after login, logout and any role change to stop session fixation. */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['_started'] = time();
        Csrf::rotate();
    }

    /** @return list<array{type: string, message: string}> */
    public static function pullFlash(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return is_array($f) ? $f : [];
    }

    /** @return array<string, string> */
    public static function pullErrors(): array
    {
        $e = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);
        return is_array($e) ? $e : [];
    }

    public static function clearOld(): void
    {
        unset($_SESSION['_old']);
    }
}
