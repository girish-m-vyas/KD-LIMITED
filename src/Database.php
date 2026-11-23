<?php
declare(strict_types=1);

namespace KD;

use KD\Support\Env;
use PDO;
use PDOStatement;

/**
 * The only way the app talks to the database. Every method takes SQL with
 * `?` placeholders and a separate list of values, so user input is always
 * sent as data and never becomes part of the SQL text.
 */
final class Database
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        if ($this->driver() === 'sqlite') {
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        }
    }

    public static function fromEnv(): self
    {
        $driver = (string) Env::get('DB_DRIVER', 'mysql');

        if ($driver === 'sqlite') {
            $path = (string) (Env::get('DB_PATH') ?: APP_ROOT . '/storage/database.sqlite');
            return new self(new PDO('sqlite:' . $path));
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            Env::get('DB_HOST', '127.0.0.1'),
            (int) Env::get('DB_PORT', 3306),
            Env::get('DB_NAME', 'kd')
        );
        $user = (string) Env::get('DB_USER', '');
        if ($user === '' || $user === 'root') {
            throw new \RuntimeException('Set DB_USER in .env to a dedicated database user (not root).');
        }
        $pdo = new PDO($dsn, $user, (string) Env::get('DB_PASS', ''));
        $pdo->exec("SET NAMES utf8mb4");
        $pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES,ONLY_FULL_GROUP_BY,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
        return new self($pdo);
    }

    public function driver(): string
    {
        return (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** @param list<mixed> $params */
    public function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        foreach (array_values($params) as $i => $value) {
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue($i + 1, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    /** @return array<string, mixed>|null */
    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** @return list<array<string, mixed>> */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function value(string $sql, array $params = []): mixed
    {
        $v = $this->run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** @return int rows affected */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /** @return int new row id */
    public function insert(string $sql, array $params = []): int
    {
        $this->run($sql, $params);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @template T
     * @param callable(self): T $fn
     * @return T
     */
    public function transaction(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Runs a trusted SQL file shipped with the app (schema or seed). Never use with user input. */
    public function runSqlFile(string $file): void
    {
        $sql = (string) file_get_contents($file);
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
        foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
            $this->pdo->exec($statement);
        }
    }
}
