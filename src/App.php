<?php
declare(strict_types=1);

namespace KD;

use KD\Auth\AdminAuth;
use KD\Auth\CustomerAuth;
use KD\Auth\RateLimiter;
use KD\Services\AuditLog;
use KD\Services\CustomerService;
use KD\Services\OrderService;
use KD\Support\Upload;

/** Lazily builds and shares the app's services for one request. */
final class App
{
    private static ?Database $db = null;
    private static array $instances = [];

    public static function setDatabase(Database $db): void
    {
        self::$db = $db;
        self::$instances = [];
    }

    public static function db(): Database
    {
        return self::$db ??= Database::fromEnv();
    }

    public static function audit(): AuditLog
    {
        return self::$instances[AuditLog::class] ??= new AuditLog(self::db());
    }

    public static function limiter(): RateLimiter
    {
        return self::$instances[RateLimiter::class] ??= new RateLimiter(self::db(), 5, 900);
    }

    public static function customerAuth(): CustomerAuth
    {
        return self::$instances[CustomerAuth::class] ??= new CustomerAuth(self::db(), self::limiter());
    }

    public static function adminAuth(): AdminAuth
    {
        return self::$instances[AdminAuth::class] ??= new AdminAuth(self::db(), self::limiter(), self::audit());
    }

    public static function orders(): OrderService
    {
        return self::$instances[OrderService::class] ??= new OrderService(self::db());
    }

    public static function customers(): CustomerService
    {
        return self::$instances[CustomerService::class] ??= new CustomerService(self::db());
    }

    public static function uploads(): Upload
    {
        return self::$instances[Upload::class] ??= new Upload(APP_ROOT . '/storage/uploads');
    }
}
