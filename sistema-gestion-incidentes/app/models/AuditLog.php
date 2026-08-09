<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;

class AuditLog
{
    public static function record(?int $userId, string $action, ?string $entityType, ?string $entityId, ?array $oldData, ?array $newData): void
    {
        $db = Database::connection();
        $username = $_SESSION['user']['username'] ?? null;
        $stmt = $db->prepare(
            'INSERT INTO audit_logs (user_id, username, action, entity_type, entity_id, ip_address, old_data, new_data)
             VALUES (:user_id, :username, :action, :entity_type, :entity_id, :ip, :old_data, :new_data)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'username' => $username,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'old_data' => $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
            'new_data' => $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public static function paginate(array $filters, int $page, int $perPage): array
    {
        $db = Database::connection();
        $where = [];
        $params = [];

        if (!empty($filters['user'])) {
            $where[] = 'username LIKE :user';
            $params['user'] = '%' . $filters['user'] . '%';
        }
        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params['action'] = $filters['action'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT * FROM audit_logs $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total];
    }

    public static function distinctActions(): array
    {
        $db = Database::connection();
        return $db->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
    }
}
