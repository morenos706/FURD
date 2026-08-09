<?php

namespace App\Services;

use App\Helpers\Database;
use App\Models\CaseHistory;
use App\Models\CaseRecord;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Importa el Excel exportado de Survey123/ArcGIS (hoja "survey_0", 242 columnas)
 * como casos historicos (status=cerrado).
 *
 * Las columnas se mapean por INDICE (no por texto de encabezado), porque el
 * Excel tiene encabezados repetidos (ej. "Descripcion" aparece dos veces con
 * distinto significado, "Tipo de Propiedad" tambien) que no se pueden
 * distinguir de forma confiable solo por el nombre.
 */
class Survey123Importer
{
    private const CLASE_SERVICIO_COLS = [
        8 => 'clase_de_servicio_1_incendios', 9 => 'clase_de_servicio_2_matpel',
        10 => 'clase_de_servicio_3explosion', 11 => 'clase_de_servicio_4rescates',
        12 => 'clase_de_servicio_5_fenomeno_de', 13 => 'clase_de_servicio_6_inundacione',
        14 => 'clase_de_servicio_7_incidentes', 15 => 'clase_de_servicio_8_falla_estru',
        16 => 'clase_de_servicio_10_falla_elec', 17 => 'clase_de_servicio_11_atencion_p',
        18 => 'clase_de_servicio_12quemas_proh', 19 => 'clase_de_servicio_14_reduccion',
        20 => 'clase_de_servicio_15_caida_de_o',
    ];

    /**
     * @return array{imported:int, skipped:int, errors:string[]}
     */
    public function import(string $path, bool $wipe, ?callable $onProgress = null): array
    {
        $db = Database::connection();
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        if ($wipe) {
            $db->exec('DELETE FROM cases');
            $db->exec('TRUNCATE TABLE case_counters');
        }

        $caseModel = new CaseRecord();

        $stmtCase = $db->prepare(
            "INSERT INTO cases (
                case_number, service_type, incident_date, report_time, departure_time, arrival_time,
                closure_time, station_time, incident_commander, address, comuna, barrio,
                latitude, longitude, description, shift_chief, status, priority,
                form_data, is_demo, created_at, updated_at, closed_at
            ) VALUES (
                :case_number, :service_type, :incident_date, :report_time, :departure_time, :arrival_time,
                :closure_time, :station_time, :incident_commander, :address, :comuna, :barrio,
                :latitude, :longitude, :description, :shift_chief, 'cerrado', 'media',
                :form_data, 0, :created_at, :updated_at, :closed_at
            )"
        );
        $stmtBuilding = $db->prepare(
            "INSERT INTO case_buildings (case_id, seq, property_type, address, building_use, upper_levels, lower_levels,
                comuna, barrio, stratum, owner_name, owner_phone, occupant_name, occupant_phone, company_name,
                vehicle_type, vehicle_brand, vehicle_model, vehicle_plate, vehicle_color, extra_data)
             VALUES (:case_id, :seq, :property_type, :address, :building_use, :upper_levels, :lower_levels,
                :comuna, :barrio, :stratum, :owner_name, :owner_phone, :occupant_name, :occupant_phone, :company_name,
                :vehicle_type, :vehicle_brand, :vehicle_model, :vehicle_plate, :vehicle_color, :extra_data)"
        );
        $stmtPerson = $db->prepare(
            "INSERT INTO case_persons (case_id, seq, full_name, age, sex, rescued, alive)
             VALUES (:case_id, :seq, :full_name, :age, :sex, :rescued, :alive)"
        );
        $stmtAnimal = $db->prepare(
            "INSERT INTO case_animals (case_id, seq, animal_type, size, sex, rescued, alive)
             VALUES (:case_id, :seq, :animal_type, :size, :sex, :rescued, :alive)"
        );
        $stmtFirefighter = $db->prepare(
            "INSERT INTO case_firefighters (case_id, seq, firefighter_name, role, vehicle_value)
             VALUES (:case_id, :seq, :firefighter_name, :role, :vehicle_value)"
        );
        $stmtSci = $db->prepare(
            "INSERT INTO case_sci_objectives (case_id, seq, objective, strategy_tactic)
             VALUES (:case_id, 1, :objective, :strategy_tactic)"
        );

        $imported = 0;
        $skipped = 0;
        $errors = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                $c = fn(int $col) => $this->cell($sheet, $col, $row);

                $servicio = $this->cleanText($c(7));
                $direccion = $this->cleanText($c(30));
                if (!$servicio && !$direccion) {
                    $skipped++;
                    continue; // fila vacia
                }

                $formData = [];
                foreach (self::CLASE_SERVICIO_COLS as $col => $key) {
                    $v = $this->cleanText($c($col));
                    if ($v !== null) $formData[$key] = $v;
                }
                $numeroRecordOriginal = $this->cleanText($c(21));
                if ($numeroRecordOriginal !== null) $formData['numero_record'] = $numeroRecordOriginal;

                $comandante = $this->cleanText($c(28)) ?? $this->cleanText($c(29));

                $buildingBases = [32, 58, 84];
                $buildings = [];
                foreach ($buildingBases as $base) {
                    $addr = $this->cleanText($c($base + 1));
                    $propType = $this->cleanText($c($base));
                    if (!$addr && !$propType) continue;
                    $buildings[] = [
                        'property_type' => $propType,
                        'address' => $addr,
                        'building_use' => $this->cleanText($c($base + 2)) ?? $this->cleanText($c($base + 3)),
                        'upper_levels' => $this->toDecimal($c($base + 4)),
                        'lower_levels' => $this->toDecimal($c($base + 5)),
                        'barrio' => $this->cleanText($c($base + 6)) ?? $this->cleanText($c($base + 7)),
                        'comuna' => $this->cleanText($c($base + 8)),
                        'stratum' => $this->cleanText($c($base + 9)),
                        'owner_name' => $this->cleanText($c($base + 14)),
                        'owner_phone' => $this->cleanText($c($base + 15)),
                        'occupant_name' => $this->cleanText($c($base + 16)),
                        'occupant_phone' => $this->cleanText($c($base + 17)),
                        'company_name' => $this->cleanText($c($base + 18)),
                        'vehicle_type' => $this->cleanText($c($base + 19)) ?? $this->cleanText($c($base + 20)),
                        'vehicle_brand' => $this->cleanText($c($base + 21)),
                        'vehicle_model' => $this->cleanText($c($base + 22)),
                        'vehicle_plate' => $this->cleanText($c($base + 23)),
                        'vehicle_color' => $this->cleanText($c($base + 24)),
                        'extra_data' => json_encode(array_filter([
                            'tipo_de_propiedad' => $this->cleanText($c($base + 10)) ?? $this->cleanText($c($base + 11)),
                            'nombre_del_arrendador' => $this->cleanText($c($base + 12)),
                            'telefono_del_arrendador' => $this->cleanText($c($base + 13)),
                            'servicio' => $this->cleanText($c($base + 25)),
                        ]), JSON_UNESCAPED_UNICODE),
                    ];
                }

                foreach ([
                    110 => '_rea_afectada', 111 => 'zona_expuesta', 112 => 'tipo_de_vegetaci_n',
                    113 => 'etapa_de_desarrollo_del_fuego', 114 => '_rea_o_punto_de_origen_del_ince',
                    115 => 'clasificacion_de_la_causa_para', 116 => 'descripcion',
                ] as $col => $key) {
                    $v = $this->cleanText($c($col));
                    if ($v !== null) $formData[$key] = $v;
                }

                $persons = [];
                for ($i = 0; $i < 8; $i++) {
                    $base = 118 + $i * 6;
                    $name = $this->cleanText($c($base));
                    if (!$name) continue;
                    $persons[] = [
                        'full_name' => $name,
                        'age' => $this->toDecimal($c($base + 1)),
                        'sex' => $this->cleanText($c($base + 2)) ?? $this->cleanText($c($base + 3)),
                        'rescued' => $this->cleanText($c($base + 4)),
                        'alive' => $this->cleanText($c($base + 5)),
                    ];
                }

                $animals = [];
                for ($i = 0; $i < 4; $i++) {
                    $base = 167 + $i * 5;
                    $type = $this->cleanText($c($base));
                    if (!$type) continue;
                    $animals[] = [
                        'animal_type' => $type,
                        'size' => $this->cleanText($c($base + 1)),
                        'sex' => $this->cleanText($c($base + 2)),
                        'rescued' => $this->cleanText($c($base + 3)),
                        'alive' => $this->cleanText($c($base + 4)),
                    ];
                }

                $descripcionAcciones = $this->cleanText($c(187));
                if ($descripcionAcciones !== null) $formData['descripcion_'] = $descripcionAcciones;
                $continueDescripcion = $this->cleanText($c(240));
                if ($continueDescripcion !== null) $formData['continue_con_la_descripcion'] = $continueDescripcion;

                $jefeDeTurno = $this->cleanText($c(188)) ?? $this->cleanText($c(189));
                if ($jefeDeTurno !== null) $formData['jefe_de_turno'] = $jefeDeTurno;
                $vehiculosRaw = $this->cleanText($c(190));
                if ($vehiculosRaw !== null) $formData['vehiculos'] = array_map('trim', explode(',', $vehiculosRaw));

                $firefighters = [];
                if ($jefeDeTurno !== null) {
                    $firefighters[] = ['firefighter_name' => $jefeDeTurno, 'role' => 'Jefe de Turno', 'vehicle_value' => null];
                }
                for ($i = 0; $i < 15; $i++) {
                    $base = 191 + $i * 2;
                    $name = $this->cleanText($c($base)) ?? $this->cleanText($c($base + 1));
                    if (!$name) continue;
                    $firefighters[] = ['firefighter_name' => $name, 'role' => 'Bombero', 'vehicle_value' => null];
                }

                $nombreIncidente = $this->cleanText($c(221));
                if ($nombreIncidente !== null) $formData['nombre_del_incidente'] = $nombreIncidente;
                $lugarIncidente = $this->cleanText($c(222));
                if ($lugarIncidente !== null) $formData['lugar_del_incidente'] = $lugarIncidente;
                $fechaHoraIncidente = $this->toDateTime($c(223));
                if ($fechaHoraIncidente !== null) $formData['fecha_y_hora_del_incidente'] = $fechaHoraIncidente;
                foreach ([
                    224 => 'field_330', 225 => 'barrio_', 226 => 'naturaleza_del_incidente',
                    227 => 'amenazas_del_incidente', 228 => 'field_336', 229 => 'aislamiento',
                    232 => 'ubicaci_n_del_puesto_de_comando', 233 => '_rea_de_espera',
                    234 => 'ruta_de_egreso', 235 => 'mensaje_de_seguridad',
                    236 => 'acepto', 239 => 'cedula_de_ciudadania',
                ] as $col => $key) {
                    $v = $this->cleanText($c($col));
                    if ($v !== null) $formData[$key] = $v;
                }
                $objetivos = $this->cleanText($c(230));
                $estrategias = $this->cleanText($c(231));
                if ($objetivos !== null) $formData['objetivos'] = $objetivos;
                if ($estrategias !== null) $formData['estretegias_y_tacticas'] = $estrategias;

                $nombreCompleto = $this->cleanText($c(237)) ?? $this->cleanText($c(238));
                if ($nombreCompleto !== null) $formData['nombre_completo'] = $nombreCompleto;

                $lng = $this->toDecimal($c(241));
                $lat = $this->toDecimal($c(242));

                $creationDate = $this->toDateTime($c(3)) ?? date('Y-m-d H:i:s');
                $editDate = $this->toDateTime($c(5)) ?? $creationDate;
                $creator = $this->cleanText($c(4));
                $editor = $this->cleanText($c(6));
                if ($creator !== null) $formData['creador_original'] = $creator;
                if ($editor !== null) $formData['editor_original'] = $editor;

                $descriptionCore = $descripcionAcciones ?? $this->cleanText($c(116));

                $incidentDate = $this->toDate($c(22));
                $year = $incidentDate ? (int) substr($incidentDate, 0, 4) : (int) date('Y', strtotime($creationDate));
                $caseNumber = $caseModel->generateCaseNumber($year);

                $stmtCase->execute([
                    'case_number' => $caseNumber,
                    'service_type' => $servicio,
                    'incident_date' => $incidentDate,
                    'report_time' => $this->toTime($c(23)),
                    'departure_time' => $this->toTime($c(24)),
                    'arrival_time' => $this->toTime($c(25)),
                    'closure_time' => $this->toTime($c(26)),
                    'station_time' => $this->toTime($c(27)),
                    'incident_commander' => $comandante,
                    'address' => $direccion,
                    'comuna' => $buildings[0]['comuna'] ?? null,
                    'barrio' => $buildings[0]['barrio'] ?? null,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'description' => $descriptionCore,
                    'shift_chief' => $jefeDeTurno,
                    'form_data' => json_encode($formData, JSON_UNESCAPED_UNICODE),
                    'created_at' => $creationDate,
                    'updated_at' => $editDate,
                    'closed_at' => $editDate,
                ]);
                $caseId = (int) $db->lastInsertId();

                foreach ($buildings as $seq => $b) {
                    $stmtBuilding->execute(array_merge(['case_id' => $caseId, 'seq' => $seq + 1], $b));
                }
                foreach ($persons as $seq => $p) {
                    $stmtPerson->execute(array_merge(['case_id' => $caseId, 'seq' => $seq + 1], $p));
                }
                foreach ($animals as $seq => $a) {
                    $stmtAnimal->execute(array_merge(['case_id' => $caseId, 'seq' => $seq + 1], $a));
                }
                foreach ($firefighters as $seq => $f) {
                    $stmtFirefighter->execute(array_merge(['case_id' => $caseId, 'seq' => $seq + 1], $f));
                }
                if ($objetivos !== null || $estrategias !== null) {
                    $stmtSci->execute(['case_id' => $caseId, 'objective' => $objetivos, 'strategy_tactic' => $estrategias]);
                }

                CaseHistory::log($caseId, null, 'IMPORTADO', 'Caso importado desde archivo historico Survey123/ArcGIS', null);

                $imported++;
                if ($onProgress && $imported % 50 === 0) $onProgress($imported);
            } catch (\Throwable $e) {
                $errors[] = "Fila $row: " . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function cell($sheet, int $col, int $row): ?string
    {
        $letter = Coordinate::stringFromColumnIndex($col);
        $v = $sheet->getCell($letter . $row)->getFormattedValue();
        if ($v === null) return null;
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    private function cleanText(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        $v = trim($v, "_ \t\n\r\0\x0B");
        return $v === '' ? null : $v;
    }

    private function toDate($v): ?string
    {
        if ($v === null || $v === '') return null;
        $ts = strtotime((string) $v);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function toDateTime($v): ?string
    {
        if ($v === null || $v === '') return null;
        $ts = strtotime((string) $v);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function toTime($v): ?string
    {
        if ($v === null || $v === '') return null;
        $ts = strtotime((string) $v);
        return $ts ? date('H:i:s', $ts) : null;
    }

    private function toDecimal($v): ?float
    {
        if ($v === null || $v === '' || !is_numeric($v)) return null;
        return (float) $v;
    }
}
