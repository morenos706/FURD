<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\AuditLog;

class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 300;

    public static function attempt(string $username, string $password): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $attemptsKey = 'login_attempts_' . md5($ip . $username);
        $attempts = $_SESSION[$attemptsKey] ?? ['count' => 0, 'time' => 0];

        if ($attempts['count'] >= self::MAX_ATTEMPTS && (time() - $attempts['time']) < self::LOCK_SECONDS) {
            $wait = self::LOCK_SECONDS - (time() - $attempts['time']);
            return [false, "Demasiados intentos fallidos. Intente nuevamente en {$wait} segundos."];
        }

        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $attempts['count'] = ($attempts['count'] ?? 0) + 1;
            $attempts['time'] = time();
            $_SESSION[$attemptsKey] = $attempts;
            AuditLog::record(null, 'LOGIN_FALLIDO', 'auth', $username, null, null);
            return [false, 'Usuario o contraseña incorrectos.'];
        }

        if (!$user['active']) {
            AuditLog::record($user['id'], 'LOGIN_BLOQUEADO', 'auth', $username, null, null);
            return [false, 'Este usuario se encuentra desactivado. Contacte al administrador.'];
        }

        unset($_SESSION[$attemptsKey]);
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'        => $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role_code'],
            'role_name' => $user['role_name'],
        ];

        $userModel->touchLastLogin((int) $user['id']);
        AuditLog::record((int) $user['id'], 'LOGIN', 'auth', $user['username'], null, null);

        return [true, 'OK'];
    }

    public static function logout(): void
    {
        if (self::check()) {
            AuditLog::record(self::id(), 'LOGOUT', 'auth', self::user()['username'], null, null);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function can(string $ability): bool
    {
        $role = self::role();
        $matrix = [
            'admin' => ['*'],
            // Crea el caso y lo asigna a un bombero.
            'radio_operador' => ['case.create', 'case.view', 'case.assign', 'case.edit_own', 'stats.view', 'search'],
            // Diligencia y firma los casos que tiene asignados.
            'bombero' => ['case.view', 'case.edit_own', 'case.sign', 'stats.view', 'search'],
            // Supervisa el turno: puede asignar/reasignar y sacar reportes.
            'coordinador_turno' => ['case.view', 'case.assign', 'case.report', 'case.reopen', 'export.own', 'stats.view', 'search'],
            // Aprueba el cierre de casos ya firmados.
            'subcomandancia' => ['case.view', 'case.approve', 'case.report', 'case.reopen', 'export.own', 'stats.view', 'search'],
        ];
        $allowed = $matrix[$role] ?? [];
        return in_array('*', $allowed, true) || in_array($ability, $allowed, true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Helpers::redirect('/login');
        }
    }

    public static function requireAbility(string $ability): void
    {
        self::requireLogin();
        if (!self::can($ability)) {
            http_response_code(403);
            require dirname(__DIR__, 2) . '/views/errors/403.php';
            exit;
        }
    }
}
