<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Helpers as H;
use App\Helpers\View;
use App\Models\CaseRecord;
use App\Models\Catalog;

class DashboardController
{
    public function index(): void
    {
        Auth::requireAbility('stats.view');
        $model = new CaseRecord();
        $filters = $this->currentFilters();

        $kpis = $model->kpis($filters);
        $affected = $model->affectedTotals($filters);
        $byMonth = $model->countByMonth($filters, 12);
        $byService = $model->countByField('service_type', $filters, 8);
        $byStatus = $model->countByField('status', $filters, 5);
        $byComuna = $model->countByField('comuna', $filters, 10);
        $recent = $model->paginate($filters, 1, 8)['data'];
        $heatPoints = $model->coordinates($filters);

        View::render('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'pageSubtitle' => 'Resumen general de la operacion',
            'active' => 'dashboard',
            'kpis' => $kpis,
            'affected' => $affected,
            'byMonth' => $byMonth,
            'byService' => $byService,
            'byStatus' => $byStatus,
            'byComuna' => $byComuna,
            'recent' => $recent,
            'heatPoints' => $heatPoints,
            'filters' => $filters,
            'services' => Catalog::items('list_servicio'),
        ]);
    }

    public function data(): void
    {
        Auth::requireAbility('stats.view');
        $model = new CaseRecord();
        $filters = $this->currentFilters();

        H::jsonResponse([
            'kpis' => $model->kpis($filters),
            'affected' => $model->affectedTotals($filters),
            'byMonth' => $model->countByMonth($filters, 12),
            'byService' => $model->countByField('service_type', $filters, 8),
            'byStatus' => $model->countByField('status', $filters, 5),
            'byComuna' => $model->countByField('comuna', $filters, 10),
            'heatPoints' => $model->coordinates($filters),
        ]);
    }

    private function currentFilters(): array
    {
        $range = H::input('range', '');
        $filters = [
            'date_from' => H::input('date_from'),
            'date_to' => H::input('date_to'),
            'service_type' => H::input('service_type'),
            'status' => H::input('status'),
        ];

        if ($range && empty($filters['date_from']) && empty($filters['date_to'])) {
            $today = date('Y-m-d');
            switch ($range) {
                case 'today':
                    $filters['date_from'] = $today; $filters['date_to'] = $today; break;
                case '7d':
                    $filters['date_from'] = date('Y-m-d', strtotime('-6 days')); $filters['date_to'] = $today; break;
                case '30d':
                    $filters['date_from'] = date('Y-m-d', strtotime('-29 days')); $filters['date_to'] = $today; break;
                case 'month':
                    $filters['date_from'] = date('Y-m-01'); $filters['date_to'] = $today; break;
                case 'year':
                    $filters['date_from'] = date('Y-01-01'); $filters['date_to'] = $today; break;
            }
        }

        return array_filter($filters, fn($v) => $v !== null && $v !== '');
    }
}
