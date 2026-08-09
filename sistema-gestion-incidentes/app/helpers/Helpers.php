<?php

namespace App\Helpers;

class Helpers
{
    /** Escapa texto para salida segura en HTML (proteccion XSS). */
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /** Limpia una cadena de espacios y etiquetas peligrosas. */
    public static function clean(?string $value): ?string
    {
        if ($value === null) return null;
        $value = trim($value);
        $value = strip_tags($value);
        return $value === '' ? null : $value;
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . self::url($path));
        exit;
    }

    public static function url(string $path = ''): string
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
        if ($base === '/' || $base === '\\') {
            $base = '';
        }
        return $base . '/' . ltrim($path, '/');
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function getFlashes(): array
    {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }

    public static function formatDate(?string $date): string
    {
        if (!$date) return '-';
        $ts = strtotime($date);
        return $ts ? date('d/m/Y', $ts) : '-';
    }

    public static function formatDateTime(?string $date): string
    {
        if (!$date) return '-';
        $ts = strtotime($date);
        return $ts ? date('d/m/Y H:i', $ts) : '-';
    }

    public static function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function jsonResponse($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function slug(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
        $text = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
        return $text ?: 'item';
    }
}
