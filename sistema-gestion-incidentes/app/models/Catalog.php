<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;

class Catalog
{
    private static array $cache = [];

    public static function items(string $listName, bool $onlyActive = true): array
    {
        $cacheKey = $listName . ($onlyActive ? '_active' : '_all');
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        $db = Database::connection();
        $sql = 'SELECT id, item_value, item_label, sort_order, active FROM catalog_items WHERE list_name = :list';
        if ($onlyActive) {
            $sql .= ' AND active = 1';
        }
        $sql .= ' ORDER BY sort_order, item_label';
        $stmt = $db->prepare($sql);
        $stmt->execute(['list' => $listName]);
        $rows = $stmt->fetchAll();
        self::$cache[$cacheKey] = $rows;
        return $rows;
    }

    public static function label(string $listName, ?string $value): ?string
    {
        if ($value === null || $value === '') return null;
        foreach (self::items($listName, false) as $item) {
            if ($item['item_value'] === $value) {
                return $item['item_label'];
            }
        }
        return $value;
    }

    public static function allListNames(): array
    {
        $db = Database::connection();
        return $db->query('SELECT DISTINCT list_name FROM catalog_items ORDER BY list_name')->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function create(string $listName, string $value, string $label, int $sortOrder = 0): int
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO catalog_items (list_name, item_value, item_label, sort_order) VALUES (:l, :v, :lbl, :s)'
        );
        $stmt->execute(['l' => $listName, 'v' => $value, 'lbl' => $label, 's' => $sortOrder]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, string $label, bool $active): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE catalog_items SET item_label = :lbl, active = :a WHERE id = :id');
        $stmt->execute(['lbl' => $label, 'a' => $active ? 1 : 0, 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('DELETE FROM catalog_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
