<?php

namespace App\Services;

use App\Models\Catalog;

class ExcelExporter
{
    /**
     * Genera un archivo Excel (o CSV si PhpSpreadsheet no esta instalado)
     * con los casos indicados, encabezados, fecha de generacion y filtros usados.
     */
    public static function export(array $cases, array $columns, array $filtersUsed, string $filename): void
    {
        if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            self::exportXlsx($cases, $columns, $filtersUsed, $filename);
            return;
        }
        self::exportCsv($cases, $columns, $filtersUsed, $filename);
    }

    private static function exportXlsx(array $cases, array $columns, array $filtersUsed, string $filename): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Casos');

        $sheet->setCellValue('A1', 'Sistema de Gestion de Incidentes - Exportacion de Casos');
        $sheet->mergeCells('A1:' . self::colLetter(count($columns)) . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->setCellValue('A2', 'Fecha de generacion: ' . date('d/m/Y H:i'));
        $sheet->setCellValue('A3', 'Filtros utilizados: ' . (self::describeFilters($filtersUsed) ?: 'Ninguno (todos los registros)'));
        $sheet->setCellValue('A4', 'Total de registros: ' . count($cases));

        $headerRow = 6;
        $col = 1;
        foreach ($columns as $label) {
            $sheet->setCellValueByColumnAndRow($col, $headerRow, $label);
            $col++;
        }
        $sheet->getStyle(self::colLetter(1) . $headerRow . ':' . self::colLetter(count($columns)) . $headerRow)
            ->getFont()->setBold(true);
        $sheet->getStyle(self::colLetter(1) . $headerRow . ':' . self::colLetter(count($columns)) . $headerRow)
            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('C0392B');
        $sheet->getStyle(self::colLetter(1) . $headerRow . ':' . self::colLetter(count($columns)) . $headerRow)
            ->getFont()->getColor()->setRGB('FFFFFF');

        $row = $headerRow + 1;
        foreach ($cases as $case) {
            $col = 1;
            foreach (self::rowValues($case) as $value) {
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;
        }

        $sheet->setCellValueByColumnAndRow(1, $row + 1, 'Total registros:');
        $sheet->setCellValueByColumnAndRow(2, $row + 1, count($cases));

        foreach (range(1, count($columns)) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private static function exportCsv(array $cases, array $columns, array $filtersUsed, string $filename): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para acentos en Excel
        fputcsv($out, ['Sistema de Gestion de Incidentes - Exportacion de Casos']);
        fputcsv($out, ['Fecha de generacion:', date('d/m/Y H:i')]);
        fputcsv($out, ['Filtros utilizados:', self::describeFilters($filtersUsed) ?: 'Ninguno (todos los registros)']);
        fputcsv($out, ['Total de registros:', count($cases)]);
        fputcsv($out, []);
        fputcsv($out, $columns);
        foreach ($cases as $case) {
            fputcsv($out, self::rowValues($case));
        }
        fclose($out);
        exit;
    }

    private static function rowValues(array $case): array
    {
        return [
            $case['case_number'],
            Catalog::label('list_servicio', $case['service_type']) ?? '',
            $case['incident_date'] ?? '',
            $case['address'] ?? '',
            $case['comuna'] ?? '',
            $case['barrio'] ?? '',
            $case['responsible_name'] ?? '',
            ucfirst($case['priority'] ?? ''),
            ucfirst(str_replace('_', ' ', $case['status'] ?? '')),
            $case['created_at'] ?? '',
        ];
    }

    public static function defaultColumns(): array
    {
        return ['Numero de Caso', 'Servicio', 'Fecha', 'Direccion', 'Comuna', 'Barrio', 'Responsable', 'Prioridad', 'Estado', 'Creado'];
    }

    private static function describeFilters(array $filters): string
    {
        $labels = [];
        $map = [
            'q' => 'Busqueda', 'date_from' => 'Desde', 'date_to' => 'Hasta', 'service_type' => 'Servicio',
            'status' => 'Estado', 'comuna' => 'Comuna', 'barrio' => 'Barrio', 'priority' => 'Prioridad',
            'responsible_user_id' => 'Responsable',
        ];
        foreach ($map as $key => $label) {
            if (!empty($filters[$key])) {
                $labels[] = "$label: {$filters[$key]}";
            }
        }
        return implode(' | ', $labels);
    }

    private static function colLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }
}
