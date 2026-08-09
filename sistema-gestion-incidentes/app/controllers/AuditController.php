<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Helpers as H;
use App\Helpers\View;
use App\Models\AuditLog;

class AuditController
{
    public function index(): void
    {
        Auth::requireLogin();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            require BASE_PATH . '/views/errors/403.php';
            exit;
        }

        $filters = array_filter([
            'user' => H::input('user'),
            'action' => H::input('action'),
            'date_from' => H::input('date_from'),
            'date_to' => H::input('date_to'),
        ], fn($v) => $v !== null && $v !== '');

        $page = max(1, (int) H::input('page', 1));
        $result = AuditLog::paginate($filters, $page, 40);

        View::render('audit/index', [
            'pageTitle' => 'Auditoria',
            'pageSubtitle' => 'Registro de acciones realizadas en el sistema',
            'active' => 'audit',
            'logs' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 40,
            'filters' => $filters,
            'actions' => AuditLog::distinctActions(),
        ]);
    }
}
