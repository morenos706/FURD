<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Helpers as H;
use App\Helpers\View;
use App\Models\CaseRecord;
use App\Models\Catalog;

class AnalyticsController
{
    public function index(): void
    {
        Auth::requireAbility('stats.view');
        $model = new CaseRecord();
        $insights = $this->buildInsights($model);

        View::render('analytics/index', [
            'pageTitle' => 'Analitica',
            'pageSubtitle' => 'Tendencias y comparativos calculados en tiempo real desde la base de datos',
            'active' => 'analytics',
            'insights' => $insights,
            'byYear' => $model->countByYear(),
            'byService' => $model->countByField('service_type', [], 10),
            'byPriority' => $model->countByField('priority', [], 5),
        ]);
    }

    public function data(): void
    {
        Auth::requireAbility('stats.view');
        $model = new CaseRecord();
        H::jsonResponse(['insights' => $this->buildInsights($model)]);
    }

    private function buildInsights(CaseRecord $model): array
    {
        $thisMonth = $model->kpis(['date_from' => date('Y-m-01'), 'date_to' => date('Y-m-d')]);
        $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
        $lastMonthEnd = date('Y-m-d', strtotime('last day of last month'));
        $lastMonth = $model->kpis(['date_from' => $lastMonthStart, 'date_to' => $lastMonthEnd]);

        $thisYear = $model->kpis(['date_from' => date('Y-01-01'), 'date_to' => date('Y-m-d')]);
        $lastYear = $model->kpis(['date_from' => (date('Y') - 1) . '-01-01', 'date_to' => (date('Y') - 1) . '-12-31']);

        $totalAll = (int) ($model->kpis()['total'] ?? 0);
        $topServices = $model->countByField('service_type', [], 5);
        $topComuna = $model->countByField('comuna', [], 1);
        $byMonth = $model->countByMonth([], 24);

        $monthChangePct = $this->percentChange((int) ($lastMonth['total'] ?? 0), (int) ($thisMonth['total'] ?? 0));
        $yearChangePct = $this->percentChange((int) ($lastYear['total'] ?? 0), (int) ($thisYear['total'] ?? 0));

        $peakMonth = null;
        if ($byMonth) {
            usort($byMonth, fn($a, $b) => $b['total'] <=> $a['total']);
            $peakMonth = $byMonth[0];
        }

        $servicePercentages = array_map(function ($row) use ($totalAll) {
            $row['pct'] = $totalAll > 0 ? round(($row['total'] / $totalAll) * 100, 1) : 0;
            return $row;
        }, $topServices);

        return [
            'total_all' => $totalAll,
            'this_month' => (int) ($thisMonth['total'] ?? 0),
            'last_month' => (int) ($lastMonth['total'] ?? 0),
            'month_change_pct' => $monthChangePct,
            'this_year' => (int) ($thisYear['total'] ?? 0),
            'last_year' => (int) ($lastYear['total'] ?? 0),
            'year_change_pct' => $yearChangePct,
            'peak_month' => $peakMonth,
            'top_comuna' => $topComuna[0] ?? null,
            'top_service' => $topServices[0] ?? null,
            'service_percentages' => $servicePercentages,
            'avg_per_month' => $byMonth ? round(array_sum(array_column($byMonth, 'total')) / count($byMonth), 1) : 0,
        ];
    }

    private function percentChange(int $before, int $after): ?float
    {
        if ($before === 0) {
            return $after > 0 ? 100.0 : 0.0;
        }
        return round((($after - $before) / $before) * 100, 1);
    }
}
