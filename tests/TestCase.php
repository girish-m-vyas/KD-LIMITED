<?php
declare(strict_types=1);

namespace Tests;

use KD\Database;

class AssertionFailed extends \RuntimeException {}

abstract class TestCase
{
    public function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
    }

    public function tearDown(): void {}

    /** Fresh in-memory SQLite database with the real schema loaded. */
    protected function freshDb(): Database
    {
        $pdo = new \PDO('sqlite::memory:');
        $db  = new Database($pdo);
        $db->runSqlFile(__DIR__ . '/../database/schema.sqlite.sql');
        return $db;
    }

    protected function fail(string $message): never
    {
        throw new AssertionFailed($message);
    }

    protected function assertTrue(mixed $value, string $message = ''): void
    {
        if ($value !== true) {
            $this->fail($message ?: 'Expected true, got ' . var_export($value, true));
        }
    }

    protected function assertFalse(mixed $value, string $message = ''): void
    {
        if ($value !== false) {
            $this->fail($message ?: 'Expected false, got ' . var_export($value, true));
        }
    }

    protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $this->fail($message ?: 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    }

    protected function assertNotSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected === $actual) {
            $this->fail($message ?: 'Did not expect ' . var_export($actual, true));
        }
    }

    protected function assertNull(mixed $value, string $message = ''): void
    {
        $this->assertSame(null, $value, $message);
    }

    protected function assertNotNull(mixed $value, string $message = ''): void
    {
        if ($value === null) {
            $this->fail($message ?: 'Expected a value, got null');
        }
    }

    protected function assertCount(int $expected, array $items, string $message = ''): void
    {
        $this->assertSame($expected, count($items), $message ?: 'Expected ' . $expected . ' items, got ' . count($items));
    }

    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        if (!array_key_exists($key, $array)) {
            $this->fail($message ?: "Missing key '{$key}'");
        }
    }

    protected function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            $this->fail($message ?: "'{$needle}' not found in output");
        }
    }

    protected function assertStringNotContains(string $needle, string $haystack, string $message = ''): void
    {
        if (str_contains($haystack, $needle)) {
            $this->fail($message ?: "'{$needle}' should not appear in output");
        }
    }

    /** @param class-string<\Throwable> $class */
    protected function assertThrows(string $class, callable $fn, string $message = ''): \Throwable
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            if ($e instanceof $class) {
                return $e;
            }
            $this->fail($message ?: "Expected {$class}, got " . get_class($e) . ': ' . $e->getMessage());
        }
        $this->fail($message ?: "Expected {$class} to be thrown");
    }
}
