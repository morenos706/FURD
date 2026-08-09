<?php

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\CaseController;
use App\Controllers\ReportController;
use App\Controllers\ExportController;
use App\Controllers\UserController;
use App\Controllers\AuditController;
use App\Controllers\SettingsController;
use App\Controllers\AnalyticsController;
use App\Controllers\ProfileController;

$router = new Router();

// Auth
$router->get('/login', [new AuthController(), 'showLogin']);
$router->post('/login', [new AuthController(), 'login']);
$router->post('/logout', [new AuthController(), 'logout']);

// Home
$router->get('/', function () {
    \App\Helpers\Helpers::redirect('/dashboard');
});

// Dashboard
$router->get('/dashboard', [new DashboardController(), 'index']);
$router->get('/dashboard/data', [new DashboardController(), 'data']);

// Analitica
$router->get('/analytics', [new AnalyticsController(), 'index']);
$router->get('/analytics/data', [new AnalyticsController(), 'data']);

// Casos
$router->get('/cases', [new CaseController(), 'index']);
$router->get('/cases/create', [new CaseController(), 'create']);
$router->post('/cases', [new CaseController(), 'store']);
$router->get('/cases/{id}', [new CaseController(), 'show']);
$router->get('/cases/{id}/edit', [new CaseController(), 'edit']);
$router->post('/cases/{id}', [new CaseController(), 'update']);
$router->post('/cases/{id}/delete', [new CaseController(), 'destroy']);
$router->post('/cases/{id}/duplicate', [new CaseController(), 'duplicate']);
$router->get('/cases/{id}/pdf', [new CaseController(), 'pdf']);
$router->get('/cases/{id}/files/{attachmentId}', [new CaseController(), 'file']);
$router->post('/cases/{id}/files/{attachmentId}/delete', [new CaseController(), 'deleteAttachment']);
$router->post('/cases/{id}/assign', [new CaseController(), 'assign']);
$router->post('/cases/{id}/sign', [new CaseController(), 'sign']);
$router->get('/cases/{id}/sign-code', [new CaseController(), 'signCode']);
$router->post('/cases/{id}/approve', [new CaseController(), 'approve']);

// Perfil (firma digital guardada, PIN de seguridad)
$router->get('/profile', [new ProfileController(), 'show']);
$router->post('/profile/signature', [new ProfileController(), 'uploadSignature']);
$router->get('/profile/{userId}/signature-file', [new ProfileController(), 'signatureFile']);
$router->post('/profile/pin', [new ProfileController(), 'setPin']);

// Reportes
$router->get('/reports', [new ReportController(), 'index']);
$router->post('/reports/generate', [new ReportController(), 'generate']);

// Exportaciones
$router->get('/export', [new ExportController(), 'index']);
$router->get('/export/excel', [new ExportController(), 'excel']);

// Usuarios (solo admin)
$router->get('/users', [new UserController(), 'index']);
$router->get('/users/create', [new UserController(), 'create']);
$router->post('/users', [new UserController(), 'store']);
$router->get('/users/{id}/edit', [new UserController(), 'edit']);
$router->post('/users/{id}', [new UserController(), 'update']);
$router->post('/users/{id}/toggle', [new UserController(), 'toggle']);

// Auditoria (solo admin)
$router->get('/audit', [new AuditController(), 'index']);

// Configuracion (solo admin)
$router->get('/settings', [new SettingsController(), 'index']);
$router->post('/settings', [new SettingsController(), 'update']);
$router->get('/settings/catalogs', [new SettingsController(), 'catalogs']);
$router->post('/settings/catalogs', [new SettingsController(), 'catalogsUpdate']);
$router->post('/settings/catalogs/delete', [new SettingsController(), 'catalogsDelete']);
$router->get('/settings/backup', [new SettingsController(), 'backupDownload']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
