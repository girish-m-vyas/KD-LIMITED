<?php
declare(strict_types=1);

namespace Tests\Integration;

use KD\Auth\AdminAuth;
use KD\Auth\CustomerAuth;
use KD\Auth\RateLimiter;
use KD\Database;
use KD\Services\AuditLog;
use KD\Support\Password;
use Tests\TestCase;

final class DatabaseTest extends TestCase
{
    public function testPreparedStatementsTreatInjectionAsData(): void
    {
        $db = $this->freshDb();
        $db->insert('INSERT INTO boxes (title, description) VALUES (?, ?)', ["x' OR '1'='1", 'd']);
        $db->insert('INSERT INTO boxes (title, description) VALUES (?, ?)', ['normal', 'd']);

        $rows = $db->fetchAll('SELECT * FROM boxes WHERE title = ?', ["' OR '1'='1"]);
        $this->assertCount(0, $rows, 'injection string must not match every row');

        $row = $db->fetch('SELECT * FROM boxes WHERE title = ?', ["x' OR '1'='1"]);
        $this->assertSame("x' OR '1'='1", $row['title']);
    }

    public function testIntegerLimitBinding(): void
    {
        $db = $this->freshDb();
        for ($i = 0; $i < 5; $i++) {
            $db->insert('INSERT INTO boxes (title, description) VALUES (?, ?)', ["b{$i}", 'd']);
        }
        $this->assertCount(2, $db->fetchAll('SELECT * FROM boxes ORDER BY id LIMIT ? OFFSET ?', [2, 1]));
    }

    public function testTransactionRollsBackOnError(): void
    {
        $db = $this->freshDb();
        try {
            $db->transaction(function (Database $db) {
                $db->insert('INSERT INTO boxes (title, description) VALUES (?, ?)', ['t', 'd']);
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
        }
        $this->assertSame(0, (int) $db->value('SELECT COUNT(*) FROM boxes'));
    }
}

final class RateLimiterTest extends TestCase
{
    public function testLocksAfterMaxAttemptsAndUnlocksAfterWindow(): void
    {
        $now = 1_000_000;
        $rl  = new RateLimiter($this->freshDb(), 3, 60, function () use (&$now) { return $now; });
        $this->assertFalse($rl->isLocked('k'));
        $rl->hit('k');
        $rl->hit('k');
        $this->assertFalse($rl->isLocked('k'));
        $rl->hit('k');
        $this->assertTrue($rl->isLocked('k'));
        $this->assertFalse($rl->isLocked('other'), 'keys are independent');

        $now += 61;
        $this->assertFalse($rl->isLocked('k'), 'window expired');
    }

    public function testClearResets(): void
    {
        $rl = new RateLimiter($this->freshDb(), 2, 60);
        $rl->hit('k');
        $rl->hit('k');
        $rl->clear('k');
        $this->assertFalse($rl->isLocked('k'));
    }
}

final class CustomerAuthTest extends TestCase
{
    private Database $db;
    private CustomerAuth $auth;

    public function setUp(): void
    {
        parent::setUp();
        $this->db = $this->freshDb();
        $this->db->insert(
            'INSERT INTO customers (name, email, password_hash, contact, city, address, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)',
            ['Asha', 'asha@example.com', Password::hash('Monsoon-Train-42'), '9999999999', 'Thane', 'Addr', '2026-01-01 00:00:00', '2026-01-01 00:00:00']
        );
        $this->auth = new CustomerAuth($this->db, new RateLimiter($this->db, 5, 900));
    }

    public function testCorrectLoginSetsSession(): void
    {
        $this->assertSame('ok', $this->auth->attempt('ASHA@example.com ', 'Monsoon-Train-42', '1.1.1.1'));
        $this->assertSame(1, $_SESSION['customer_id']);
    }

    public function testSqlInjectionLoginFails(): void
    {
        $this->assertSame('invalid', $this->auth->attempt("asha@example.com' -- ", 'x', '1.1.1.1'));
        $this->assertSame('invalid', $this->auth->attempt("' OR '1'='1", "' OR '1'='1", '1.1.1.1'));
        $this->assertFalse(isset($_SESSION['customer_id']));
    }

    public function testUnknownEmailAndWrongPasswordLookTheSame(): void
    {
        $this->assertSame('invalid', $this->auth->attempt('nobody@example.com', 'whatever-123', '1.1.1.1'));
        $this->assertSame('invalid', $this->auth->attempt('asha@example.com', 'wrong-pass-123', '1.1.1.1'));
    }

    public function testLockoutAfterFiveFailuresEvenWithRightPassword(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->auth->attempt('asha@example.com', 'wrong-pass-123', '1.1.1.' . $i);
        }
        $this->assertSame('locked', $this->auth->attempt('asha@example.com', 'Monsoon-Train-42', '9.9.9.9'));
        $this->assertFalse(isset($_SESSION['customer_id']));
    }

    public function testLogoutClearsIdentity(): void
    {
        $this->auth->attempt('asha@example.com', 'Monsoon-Train-42', '1.1.1.1');
        $this->auth->logout();
        $this->assertNull($this->auth->id());
    }
}

final class AdminAuthTest extends TestCase
{
    private Database $db;
    private AdminAuth $auth;

    public function setUp(): void
    {
        parent::setUp();
        $this->db = $this->freshDb();
        $insert = 'INSERT INTO admins (name, email, password_hash, role, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?,?)';
        $this->db->insert($insert, ['Root', 'root@example.com', Password::hash('Admin-Pass-2026'), 'super_admin', 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00']);
        $this->db->insert($insert, ['Cat', 'cat@example.com', Password::hash('Admin-Pass-2026'), 'catalog_manager', 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00']);
        $this->db->insert($insert, ['Gone', 'gone@example.com', Password::hash('Admin-Pass-2026'), 'super_admin', 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00']);
        $this->auth = new AdminAuth($this->db, new RateLimiter($this->db, 5, 900), new AuditLog($this->db));
    }

    public function testLoginIsAudited(): void
    {
        $this->assertSame('ok', $this->auth->attempt('root@example.com', 'Admin-Pass-2026', '2.2.2.2'));
        $this->auth->attempt('root@example.com', 'nope-nope-1', '2.2.2.2');
        $actions = array_column($this->db->fetchAll('SELECT action FROM audit_log ORDER BY id'), 'action');
        $this->assertSame(['admin.login', 'admin.login_failed'], $actions);
    }

    public function testDeactivatedAdminCannotLogIn(): void
    {
        $this->assertSame('invalid', $this->auth->attempt('gone@example.com', 'Admin-Pass-2026', '2.2.2.2'));
    }

    public function testPermissionsComeFromDatabaseRole(): void
    {
        $this->auth->attempt('cat@example.com', 'Admin-Pass-2026', '2.2.2.2');
        $this->assertTrue($this->auth->can('catalog.manage'));
        $this->assertFalse($this->auth->can('users.manage'));

        // Tampering with the session role does nothing: the role is re-read from the database.
        $_SESSION['admin_role'] = 'super_admin';
        $this->assertFalse($this->auth->can('users.manage'));
    }

    public function testDeactivationEndsExistingSession(): void
    {
        $this->auth->attempt('cat@example.com', 'Admin-Pass-2026', '2.2.2.2');
        $this->db->execute('UPDATE admins SET is_active = 0 WHERE email = ?', ['cat@example.com']);
        $this->assertNull($this->auth->current());
        $this->assertFalse($this->auth->can('catalog.manage'));
    }

    public function testCustomerSessionIsNotAdmin(): void
    {
        $_SESSION['customer_id'] = 1;
        $this->assertNull($this->auth->current());
    }
}

final class IpLimitTest extends TestCase
{
    public function testIpLimitIsHigherThanAccountLimit(): void
    {
        $db = $this->freshDb();
        $auth = new CustomerAuth($db, new RateLimiter($db, 5, 900));
        // 10 failures from one IP across different accounts do not lock the IP...
        for ($i = 0; $i < 10; $i++) {
            $auth->attempt("user{$i}@example.com", 'wrong-pass-123', '5.5.5.5');
        }
        $db->insert(
            'INSERT INTO customers (name, email, password_hash, created_at, updated_at, address) VALUES (?,?,?,?,?,?)',
            ['Z', 'z@example.com', Password::hash('Monsoon-Train-42'), '2026-01-01 00:00:00', '2026-01-01 00:00:00', '']
        );
        $this->assertSame('ok', $auth->attempt('z@example.com', 'Monsoon-Train-42', '5.5.5.5'));
        // ...but 30 do.
        $auth->logout();
        for ($i = 10; $i < 30; $i++) {
            $auth->attempt("user{$i}@example.com", 'wrong-pass-123', '5.5.5.5');
        }
        $this->assertSame('locked', $auth->attempt('z@example.com', 'Monsoon-Train-42', '5.5.5.5'));
    }
}
