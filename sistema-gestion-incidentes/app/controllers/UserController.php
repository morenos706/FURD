<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Helpers as H;
use App\Helpers\View;
use App\Models\AuditLog;
use App\Models\User;

class UserController
{
    private function requireAdmin(): void
    {
        Auth::requireLogin();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            require BASE_PATH . '/views/errors/403.php';
            exit;
        }
    }

    public function index(): void
    {
        $this->requireAdmin();
        $model = new User();
        View::render('users/index', [
            'pageTitle' => 'Usuarios',
            'pageSubtitle' => 'Administracion de usuarios y roles',
            'active' => 'users',
            'users' => $model->all(),
        ]);
    }

    public function create(): void
    {
        $this->requireAdmin();
        $model = new User();
        View::render('users/form', [
            'pageTitle' => 'Nuevo Usuario',
            'active' => 'users',
            'user' => null,
            'roles' => $model->roles(),
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        Csrf::verifyRequest();

        $data = [
            'username' => trim((string) H::input('username')),
            'email' => trim((string) H::input('email')),
            'password' => (string) H::input('password'),
            'full_name' => trim((string) H::input('full_name')),
            'role_id' => (int) H::input('role_id'),
            'active' => H::input('active') ? 1 : 0,
        ];

        if (strlen($data['password']) < 8) {
            H::flash('danger', 'La contraseña debe tener al menos 8 caracteres.');
            H::redirect('/users/create');
        }

        $model = new User();
        if ($model->findByUsername($data['username'])) {
            H::flash('danger', 'Ya existe un usuario con ese nombre de usuario.');
            H::redirect('/users/create');
        }

        $id = $model->create($data);
        AuditLog::record(Auth::id(), 'CREAR_USUARIO', 'user', $data['username'], null, ['username' => $data['username'], 'role_id' => $data['role_id']]);

        H::flash('success', 'Usuario creado correctamente.');
        H::redirect('/users');
    }

    public function edit(string $id): void
    {
        $this->requireAdmin();
        $model = new User();
        $user = $model->find((int) $id);
        if (!$user) { H::redirect('/users'); }

        View::render('users/form', [
            'pageTitle' => 'Editar Usuario',
            'active' => 'users',
            'user' => $user,
            'roles' => $model->roles(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAdmin();
        Csrf::verifyRequest();

        $data = [
            'email' => trim((string) H::input('email')),
            'full_name' => trim((string) H::input('full_name')),
            'role_id' => (int) H::input('role_id'),
            'active' => H::input('active') ? 1 : 0,
            'password' => (string) H::input('password'),
        ];

        $model = new User();
        $model->update((int) $id, $data);
        AuditLog::record(Auth::id(), 'EDITAR_USUARIO', 'user', $id, null, ['role_id' => $data['role_id'], 'active' => $data['active']]);

        H::flash('success', 'Usuario actualizado correctamente.');
        H::redirect('/users');
    }

    public function toggle(string $id): void
    {
        $this->requireAdmin();
        Csrf::verifyRequest();
        $model = new User();
        $user = $model->find((int) $id);
        if (!$user) { H::redirect('/users'); }

        if ((int) $user['id'] === Auth::id()) {
            H::flash('danger', 'No puede desactivar su propio usuario.');
            H::redirect('/users');
        }

        $newState = !$user['active'];
        $model->setActive((int) $id, $newState);
        AuditLog::record(Auth::id(), $newState ? 'ACTIVAR_USUARIO' : 'DESACTIVAR_USUARIO', 'user', $id, null, null);

        H::flash('success', 'Estado del usuario actualizado.');
        H::redirect('/users');
    }
}
