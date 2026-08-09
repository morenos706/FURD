<?php
/**
 * Crea (o restablece) el usuario administrador inicial.
 * Uso:
 *   php database/seed_admin.php admin admin@empresa.com "ClaveSegura#1" "Administrador del Sistema"
 */
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Models\User;

$username = $argv[1] ?? 'admin';
$email    = $argv[2] ?? 'admin@localhost';
$password = $argv[3] ?? null;
$fullName = $argv[4] ?? 'Administrador';

if (!$password) {
    $password = bin2hex(random_bytes(6));
    echo "No se indico contraseña, se genero una aleatoria.\n";
}

$db = \App\Helpers\Database::connection();
$roleId = $db->query("SELECT id FROM roles WHERE code = 'admin'")->fetchColumn();
if (!$roleId) {
    die("No existe el rol 'admin'. Ejecute primero database/schema.sql\n");
}

$existing = $db->prepare('SELECT id FROM users WHERE username = :u');
$existing->execute(['u' => $username]);

if ($row = $existing->fetch()) {
    $stmt = $db->prepare('UPDATE users SET password_hash = :p, active = 1, must_change_password = 0 WHERE id = :id');
    $stmt->execute(['p' => password_hash($password, PASSWORD_DEFAULT), 'id' => $row['id']]);
    echo "Usuario '$username' ya existia: contraseña restablecida.\n";
} else {
    $userModel = new User();
    $userModel->create([
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'full_name' => $fullName,
        'role_id' => $roleId,
        'active' => 1,
        'must_change_password' => 0,
    ]);
    echo "Usuario administrador '$username' creado correctamente.\n";
}

echo "Usuario: $username\n";
echo "Clave:   $password\n";
echo "Guarde esta contraseña; no volvera a mostrarse.\n";
