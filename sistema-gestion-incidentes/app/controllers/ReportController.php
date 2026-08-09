<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Helpers as H;
use App\Helpers\View;
use App\Models\CaseRecord;
use App\Models\Catalog;
use App\Models\User;
use App\Services\ExcelExporter;

class ReportController
{
    private array $availableColumns = [
        'case_number' => 'Numero de Caso',
        'service_type' => 'Servicio',
        'incident_date' => 'Fecha',
        'report_time' => 'Hora de Reporte',
        'address' => 'Direccion',
        'comuna' => 'Comuna',
        'barrio' => 'Barrio',
        'incident_commander' => 'Comandante',
        'responsible_name' => 'Responsable',
        'shift_chief' => 'Jefe de Turno',
        'priority' => 'Prioridad',
        'status' => 'Estado',
        'description' => 'Descripcion',
        'created_at' => 'Fecha de Creacion',
        'closed_at' => 'Fecha de Cierre',
    ];

    public function index(): void
    {
        Auth::requireAbility('case.report');
        View::render('reports/index', [
            'pageTitle' => 'Reportes',
            'pageSubtitle' => 'Genere reportes por fecha, categoria, estado, responsable o ubicacion',
            'active' => 'reports',
            'services' => Catalog::items('list_servicio'),
            'comunas' => Catalog::items('list_comuna'),
            'users' => (new User())->all(),
            'columns' => $this->availableColumns,
        ]);
    }

    public function generate(): void
    {
        Auth::requireAbility('case.report');
        \App\Helpers\Csrf::verifyRequest();

        $type = H::input('report_type', 'custom');
        $filters = array_filter([
            'date_from' => H::input('date_from'),
            'date_to' => H::input('date_to'),
            'service_type' => H::input('service_type'),
            'status' => H::input('status'),
            'comuna' => H::input('comuna'),
            'responsible_user_id' => H::input('responsible_user_id'),
        ], fn($v) => $v !== null && $v !== '');

        $selectedFields = $_POST['fields'] ?? array_keys($this->availableColumns);
        $selectedFields = array_values(array_intersect($selectedFields, array_keys($this->availableColumns)));
        if (empty($selectedFields)) {
            $selectedFields = array_keys($this->availableColumns);
        }

        $model = new CaseRecord();
        $cases = $model->all($filters);

        $columns = array_map(fn($f) => $this->availableColumns[$f], $selectedFields);
        $rows = array_map(function ($case) use ($selectedFields) {
            $row = [];
            foreach ($selectedFields as $f) {
                $value = $case[$f] ?? '';
                if ($f === 'service_type') $value = Catalog::label('list_servicio', $value) ?? '';
                if ($f === 'status') $value = ucfirst(str_replace('_', ' ', (string) $value));
                if ($f === 'priority') $value = ucfirst((string) $value);
                $row[] = $value;
            }
            return $row;
        }, $cases);

        \App\Models\AuditLog::record(Auth::id(), 'GENERAR_REPORTE', 'report', $type, null, $filters);

        $this->exportCustom($rows, $columns, $filters, 'reporte_' . $type . '_' . date('Ymd_His'));
    }

    private function exportCustom(array $rows, array $columns, array $filters, string $filename): void
    {
        if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte');
            $sheet->fromArray($columns, null, 'A1');
            $sheet->fromArray($rows, null, 'A2');
            $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($columns)) . '1')
                ->getFont()->setBold(true);
            foreach (range(1, count($columns)) as $c) {
                $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
            exit;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $columns);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
