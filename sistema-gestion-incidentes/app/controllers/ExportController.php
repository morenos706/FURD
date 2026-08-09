<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Helpers as H;
use App\Helpers\View;
use App\Models\CaseRecord;
use App\Models\Catalog;
use App\Models\User;
use App\Services\ExcelExporter;

class ExportController
{
    public function index(): void
    {
        Auth::requireAbility('export.own');
        View::render('reports/export', [
            'pageTitle' => 'Exportaciones',
            'pageSubtitle' => 'Exportar registros a Excel con distintos criterios',
            'active' => 'export',
            'services' => Catalog::items('list_servicio'),
            'comunas' => Catalog::items('list_comuna'),
            'users' => (new User())->all(),
        ]);
    }

    public function excel(): void
    {
        Auth::requireAbility('export.own');

        $filters = array_filter([
            'q' => H::input('q'),
            'date_from' => H::input('date_from'),
            'date_to' => H::input('date_to'),
            'service_type' => H::input('service_type'),
            'status' => H::input('status'),
            'comuna' => H::input('comuna'),
            'barrio' => H::input('barrio'),
            'responsible_user_id' => H::input('responsible_user_id'),
            'priority' => H::input('priority'),
        ], fn($v) => $v !== null && $v !== '');

        $model = new CaseRecord();
        $cases = $model->all($filters);

        \App\Models\AuditLog::record(Auth::id(), 'EXPORTAR_EXCEL', 'case', null, null, $filters);

        ExcelExporter::export($cases, ExcelExporter::defaultColumns(), $filters, 'casos_' . date('Ymd_His'));
    }
}
