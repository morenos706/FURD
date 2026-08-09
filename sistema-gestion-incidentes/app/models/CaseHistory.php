<?php

namespace App\Models;

use App\Helpers\Database;

class CaseHistory
{
    public static function log(int $caseId, ?int $userId, string $action, ?string $summary, ?array $changes): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO case_history (case_id, user_id, action, summary, changes)
             VALUES (:case_id, :user_id, :action, :summary, :changes)'
        );
        $stmt->execute([
            'case_id' => $caseId,
            'user_id' => $userId,
            'action' => $action,
            'summary' => $summary,
            'changes' => $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public static function forCase(int $caseId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT h.*, u.full_name AS user_name FROM case_history h
             LEFT JOIN users u ON u.id = h.user_id
             WHERE h.case_id = :id ORDER BY h.created_at DESC'
        );
        $stmt->execute(['id' => $caseId]);
        return $stmt->fetchAll();
    }
}
