<?php
declare(strict_types=1);

namespace KD\Http;

use KD\Support\Env;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim((string) Env::get('APP_BASE_PATH', ''), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        return rawurldecode($path);
    }

    public static function input(string $key, string $default = ''): string
    {
        $v = $_POST[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    public static function query(string $key, string $default = ''): string
    {
        $v = $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $default;
    }

    /** Positive integer from the query string, or null. */
    public static function queryInt(string $key): ?int
    {
        $v = self::query($key);
        return preg_match('/^[1-9][0-9]{0,8}$/', $v) ? (int) $v : null;
    }

    public static function inputInt(string $key): ?int
    {
        $v = self::input($key);
        return preg_match('/^[0-9]{1,9}$/', $v) ? (int) $v : null;
    }

    /** @return list<int> */
    public static function inputIntList(string $key): array
    {
        $v = $_POST[$key] ?? [];
        if (!is_array($v)) {
            return [];
        }
        return array_values(array_unique(array_map('intval', array_filter($v, fn ($x) => is_string($x) && preg_match('/^[1-9][0-9]{0,8}$/', $x)))));
    }

    public static function file(string $key): ?array
    {
        $f = $_FILES[$key] ?? null;
        return is_array($f) && !is_array($f['name'] ?? null) ? $f : null;
    }

    /**
     * The client IP. Forwarded headers are only trusted when the request comes
     * from a proxy listed in TRUSTED_PROXIES; otherwise anyone could spoof them.
     */
    public static function ip(): string
    {
        $remote  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $trusted = array_filter(array_map('trim', explode(',', (string) Env::get('TRUSTED_PROXIES', ''))));
        if ($trusted && in_array($remote, $trusted, true) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = array_map('trim', explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']));
            $ip    = end($parts);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return $remote;
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        return Env::bool('APP_FORCE_HTTPS');
    }

    /** Raw POST data with only the listed keys, for re-filling forms. */
    public static function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = self::input($k);
        }
        return $out;
    }
}
