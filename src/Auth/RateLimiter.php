<?php
declare(strict_types=1);

namespace KD\Auth;

use KD\Database;

/** Sliding-window counter for failed logins, stored in the database. */
final class RateLimiter
{
    /** @var callable(): int */
    private $clock;

    public function __construct(
        private readonly Database $db,
        private readonly int $maxAttempts = 5,
        private readonly int $windowSeconds = 900,
        ?callable $clock = null,
    ) {
        $this->clock = $clock ?? 'time';
    }

    /** @param int|null $max override the default limit (e.g. a higher limit per IP address) */
    public function isLocked(string $key, ?int $max = null): bool
    {
        return $this->count($key) >= ($max ?? $this->maxAttempts);
    }

    public function hit(string $key): void
    {
        $now = ($this->clock)();
        $this->db->execute('INSERT INTO login_attempts (key_hash, attempted_at) VALUES (?, ?)', [$this->hash($key), $now]);
        // Housekeeping: drop rows older than a day.
        if (random_int(1, 50) === 1) {
            $this->db->execute('DELETE FROM login_attempts WHERE attempted_at < ?', [$now - 86400]);
        }
    }

    public function clear(string $key): void
    {
        $this->db->execute('DELETE FROM login_attempts WHERE key_hash = ?', [$this->hash($key)]);
    }

    private function count(string $key): int
    {
        $since = ($this->clock)() - $this->windowSeconds;
        return (int) $this->db->value(
            'SELECT COUNT(*) FROM login_attempts WHERE key_hash = ? AND attempted_at > ?',
            [$this->hash($key), $since]
        );
    }

    private function hash(string $key): string
    {
        return hash('sha256', strtolower($key));
    }

    public function windowMinutes(): int
    {
        return (int) ceil($this->windowSeconds / 60);
    }
}
