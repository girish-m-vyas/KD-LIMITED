<?php
declare(strict_types=1);

namespace KD\Support;

final class Password
{
    public const MIN_LENGTH = 10;
    /** bcrypt only uses the first 72 bytes, so longer passwords are refused rather than silently cut. */
    public const MAX_BYTES = 72;

    private const COMMON = [
        'password', 'password1', 'password123', '1234567890', '12345678910', 'qwertyuiop',
        'iloveyou123', 'admin12345', 'welcome123', 'letmein123', 'abc1234567', 'qwerty12345',
    ];

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /** Returns an error message, or null when the password is acceptable. */
    public static function policyError(string $password, string $email = ''): ?string
    {
        if (mb_strlen($password) < self::MIN_LENGTH) {
            return 'Password must be at least ' . self::MIN_LENGTH . ' characters.';
        }
        if (strlen($password) > self::MAX_BYTES) {
            return 'Password must be at most ' . self::MAX_BYTES . ' bytes.';
        }
        if (!preg_match('/\pL/u', $password) || !preg_match('/\d/', $password)) {
            return 'Password must contain letters and at least one number.';
        }
        if (in_array(strtolower($password), self::COMMON, true)) {
            return 'That password is too common. Choose another.';
        }
        if ($email !== '') {
            $local = strtolower(strstr($email, '@', true) ?: $email);
            if (mb_strlen($local) >= 4 && str_contains(strtolower($password), $local)) {
                return 'Password must not contain your email address.';
            }
        }
        return null;
    }

    /** A hash to verify against when the user doesn't exist, so timing doesn't reveal valid emails. */
    public static function dummyHash(): string
    {
        static $hash = null;
        return $hash ??= password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    }
}
