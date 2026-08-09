<?php

define('BASE_PATH', dirname(__DIR__));

$config = require BASE_PATH . '/config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', $config['app']['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/php-error.log');
date_default_timezone_set('America/Bogota');

// ------------------------------------------------------------------
// Autoloader propio para el namespace App\ (sin depender de Composer)
// ------------------------------------------------------------------
spl_autoload_register(function (string $class) {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $relative = substr($class, strlen('App\\'));
    $segments = explode('\\', $relative);
    $file = array_pop($segments); // nombre de clase: respeta mayusculas/minusculas
    $dirs = array_map('strtolower', $segments); // carpetas del proyecto: controllers, models, helpers...
    $path = BASE_PATH . '/app/' . implode('/', $dirs) . ($dirs ? '/' : '') . $file . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

// Autoload de Composer (PhpSpreadsheet, Dompdf, etc.) si esta instalado
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
}

// ------------------------------------------------------------------
// Sesion segura
// ------------------------------------------------------------------
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => $config['session']['lifetime'] * 60,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cabeceras de seguridad basicas
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

return $config;
