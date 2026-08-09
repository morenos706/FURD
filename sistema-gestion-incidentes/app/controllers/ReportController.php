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

    /** Informe Ejecutivo (PDF con KPIs, tiempos de respuesta y desgloses) para un rango de fechas. Solo Administrador. */
    public function executive(): void
    {
        Auth::requireLogin();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            require BASE_PATH . '/views/errors/403.php';
            exit;
        }

        $dateFrom = H::input('date_from') ?: date('Y-m-01');
        $dateTo = H::input('date_to') ?: date('Y-m-d');
        $filters = ['date_from' => $dateFrom, 'date_to' => $dateTo];

        $model = new CaseRecord();
        $usersById = [];
        foreach ((new User())->all() as $u) { $usersById[$u['id']] = $u['full_name']; }

        $byResponsible = $model->countByField('responsible_user_id', $filters, 10);
        foreach ($byResponsible as &$r) { $r['label'] = $usersById[$r['label']] ?? ('Usuario #' . $r['label']); }
        unset($r);

        $data = [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'kpis' => $model->kpis($filters),
            'affected' => $model->affectedTotals($filters),
            'byStatus' => $model->countByField('status', $filters, 6),
            'byService' => $model->countByField('service_type', $filters, 10),
            'byComuna' => $model->countByField('comuna', $filters, 10),
            'byResponsible' => $byResponsible,
            'responseTime' => $model->responseTimeStats($filters),
            'responseTimeByService' => $model->responseTimeByService($filters, 10),
            'entityName' => \App\Models\Setting::get('entity_name', 'Cuerpo de Bomberos'),
            'systemName' => \App\Models\Setting::get('system_name', 'Sistema de Gestion de Incidentes'),
            'logoDataUri' => $this->logoDataUri(),
            'generatedBy' => Auth::user()['full_name'] ?? '',
            'generatedAt' => date('d/m/Y H:i'),
        ];
        extract($data);

        ob_start();
        require BASE_PATH . '/views/reports/executive_pdf.php';
        $html = ob_get_clean();

        \App\Models\AuditLog::record(Auth::id(), 'GENERAR_INFORME_EJECUTIVO', 'report', 'ejecutivo', null, $filters);

        if (class_exists('\Dompdf\Dompdf')) {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->render();
            $dompdf->stream('Informe_Ejecutivo_' . $dateFrom . '_a_' . $dateTo . '.pdf', ['Attachment' => false]);
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    private function logoDataUri(): ?string
    {
        $path = \App\Models\Setting::get('logo_path');
        if (!$path) return null;
        $file = BASE_PATH . '/public' . $path;
        if (!is_file($file)) return null;
        $mime = mime_content_type($file) ?: 'image/png';
        if ($mime === 'image/svg+xml') return null;
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file));
    }
}
