<?php
declare(strict_types=1);

namespace KD\Services;

use KD\Database;

/** Append-only record of who did what, and when. There is no delete in the UI. */
final class AuditLog
{
    public function __construct(private readonly Database $db) {}

    public function record(?int $adminId, string $action, ?string $entity = null, ?int $entityId = null, array $details = [], ?string $ip = null): void
    {
        $this->db->insert(
            'INSERT INTO audit_log (admin_id, action, entity, entity_id, details, ip, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$adminId, $action, $entity, $entityId, json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}', $ip, now()]
        );
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit, int $offset, ?string $action = null): array
    {
        $sql    = 'SELECT l.*, a.name AS admin_name, a.email AS admin_email FROM audit_log l LEFT JOIN admins a ON a.id = l.admin_id';
        $params = [];
        if ($action !== null && $action !== '') {
            $sql     .= ' WHERE l.action = ?';
            $params[] = $action;
        }
        $sql     .= ' ORDER BY l.id DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function count(?string $action = null): int
    {
        if ($action !== null && $action !== '') {
            return (int) $this->db->value('SELECT COUNT(*) FROM audit_log WHERE action = ?', [$action]);
        }
        return (int) $this->db->value('SELECT COUNT(*) FROM audit_log');
    }

    /** @return list<string> */
    public function actions(): array
    {
        return array_column($this->db->fetchAll('SELECT DISTINCT action FROM audit_log ORDER BY action'), 'action');
    }
}
