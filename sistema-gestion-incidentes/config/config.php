<?php
/**
 * Carga las variables de entorno (.env) y expone la configuracion global.
 */

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        if (is_string($value)) {
            $lower = strtolower($value);
            if ($lower === 'true') return true;
            if ($lower === 'false') return false;
        }
        return $value;
    }
}

$basePath = dirname(__DIR__);
$envFile = $basePath . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"'");
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

return [
    'app' => [
        'name'  => env('APP_NAME', 'Sistema de Gestion'),
        'env'   => env('APP_ENV', 'production'),
        'debug' => env('APP_DEBUG', false),
        'url'   => env('APP_URL', 'http://localhost'),
    ],
    'db' => [
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'sistema_incidentes'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],
    'session' => [
        'lifetime' => (int) env('SESSION_LIFETIME', 120),
    ],
    'base_path' => $basePath,
];
