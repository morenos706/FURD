<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.code AS role_code, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.username = :u LIMIT 1'
        );
        $stmt->execute(['u' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, r.code AS role_code, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT u.*, r.code AS role_code, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             ORDER BY u.full_name'
        );
        return $stmt->fetchAll();
    }

    public function touchLastLogin(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role_id, active, must_change_password)
             VALUES (:username, :email, :password_hash, :full_name, :role_id, :active, :must_change_password)'
        );
        $stmt->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'full_name' => $data['full_name'],
            'role_id' => $data['role_id'],
            'active' => $data['active'] ?? 1,
            'must_change_password' => $data['must_change_password'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = ['email = :email', 'full_name = :full_name', 'role_id = :role_id', 'active = :active'];
        $params = [
            'id' => $id,
            'email' => $data['email'],
            'full_name' => $data['full_name'],
            'role_id' => $data['role_id'],
            'active' => $data['active'] ?? 1,
        ];
        if (!empty($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE users SET active = :active WHERE id = :id');
        $stmt->execute(['active' => $active ? 1 : 0, 'id' => $id]);
    }

    public function roles(): array
    {
        return $this->db->query('SELECT * FROM roles ORDER BY id')->fetchAll();
    }

    public function allByRole(string $roleCode): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.* FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.code = :code AND u.active = 1 ORDER BY u.full_name'
        );
        $stmt->execute(['code' => $roleCode]);
        return $stmt->fetchAll();
    }
}
