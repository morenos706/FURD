<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;

class CaseRecord
{
    private PDO $db;

    /** Campos "core" que viven como columnas reales en la tabla cases. */
    public const CORE_FIELDS = [
        'service_type', 'incident_date', 'report_time', 'departure_time',
        'arrival_time', 'closure_time', 'station_time', 'incident_commander',
        'address', 'comuna', 'barrio', 'latitude', 'longitude', 'description',
        'shift_chief', 'status', 'priority', 'responsible_user_id',
    ];

    public function __construct()
    {
        $this->db = Database::connection();
    }

    // -----------------------------------------------------------------
    // Numeracion unica de caso: CAS-YYYY-000001
    // -----------------------------------------------------------------
    public function generateCaseNumber(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT last_seq FROM case_counters WHERE year = :y FOR UPDATE');
            $stmt->execute(['y' => $year]);
            $row = $stmt->fetch();

            if ($row === false) {
                $this->db->prepare('INSERT INTO case_counters (year, last_seq) VALUES (:y, 1)')->execute(['y' => $year]);
                $seq = 1;
            } else {
                $seq = (int) $row['last_seq'] + 1;
                $this->db->prepare('UPDATE case_counters SET last_seq = :s WHERE year = :y')->execute(['s' => $seq, 'y' => $year]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return sprintf('CAS-%d-%06d', $year, $seq);
    }

    // -----------------------------------------------------------------
    // CRUD
    // -----------------------------------------------------------------
    public function create(array $core, array $formData, int $userId, bool $isDemo = false): int
    {
        $caseNumber = $this->generateCaseNumber();
        $sql = 'INSERT INTO cases (
                    case_number, service_type, incident_date, report_time, departure_time,
                    arrival_time, closure_time, station_time, incident_commander, address,
                    comuna, barrio, latitude, longitude, description, shift_chief, status,
                    priority, responsible_user_id, form_data, is_demo, created_by, updated_by,
                    closed_at
                ) VALUES (
                    :case_number, :service_type, :incident_date, :report_time, :departure_time,
                    :arrival_time, :closure_time, :station_time, :incident_commander, :address,
                    :comuna, :barrio, :latitude, :longitude, :description, :shift_chief, :status,
                    :priority, :responsible_user_id, :form_data, :is_demo, :created_by, :updated_by,
                    :closed_at
                )';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->bindCore($core, [
            'case_number' => $caseNumber,
            'form_data' => json_encode($formData, JSON_UNESCAPED_UNICODE),
            'is_demo' => $isDemo ? 1 : 0,
            'created_by' => $userId,
            'updated_by' => $userId,
            'closed_at' => ($core['status'] ?? 'abierto') === 'cerrado' ? date('Y-m-d H:i:s') : null,
        ]));
        $id = (int) $this->db->lastInsertId();

        CaseHistory::log($id, $userId, 'CREADO', "Caso {$caseNumber} creado", null);
        AuditLog::record($userId, 'CREAR_CASO', 'case', $caseNumber, null, $core);

        return $id;
    }

    private function bindCore(array $core, array $extra): array
    {
        $defaults = [
            'service_type' => null, 'incident_date' => null, 'report_time' => null,
            'departure_time' => null, 'arrival_time' => null, 'closure_time' => null,
            'station_time' => null, 'incident_commander' => null, 'address' => null,
            'comuna' => null, 'barrio' => null, 'latitude' => null, 'longitude' => null,
            'description' => null, 'shift_chief' => null, 'status' => 'abierto',
            'priority' => 'media', 'responsible_user_id' => null,
        ];
        foreach ($defaults as $k => $v) {
            $defaults[$k] = $core[$k] ?? $v;
            if ($defaults[$k] === '') $defaults[$k] = null;
        }
        return array_merge($defaults, $extra);
    }

    public function update(int $id, array $core, array $formData, int $userId): void
    {
        $before = $this->find($id);
        if (!$before) return;

        $closedAt = $before['closed_at'];
        if (($core['status'] ?? $before['status']) === 'cerrado' && $before['status'] !== 'cerrado') {
            $closedAt = date('Y-m-d H:i:s');
        } elseif (($core['status'] ?? $before['status']) !== 'cerrado') {
            $closedAt = null;
        }

        $sql = 'UPDATE cases SET
                    service_type = :service_type, incident_date = :incident_date, report_time = :report_time,
                    departure_time = :departure_time, arrival_time = :arrival_time, closure_time = :closure_time,
                    station_time = :station_time, incident_commander = :incident_commander, address = :address,
                    comuna = :comuna, barrio = :barrio, latitude = :latitude, longitude = :longitude,
                    description = :description, shift_chief = :shift_chief, status = :status, priority = :priority,
                    responsible_user_id = :responsible_user_id, form_data = :form_data, updated_by = :updated_by,
                    closed_at = :closed_at
                WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->bindCore($core, [
            'form_data' => json_encode($formData, JSON_UNESCAPED_UNICODE),
            'updated_by' => $userId,
            'closed_at' => $closedAt,
            'id' => $id,
        ]));

        $changes = [];
        foreach (self::CORE_FIELDS as $field) {
            $oldVal = $before[$field] ?? null;
            $newVal = $core[$field] ?? null;
            if ((string) $oldVal !== (string) $newVal) {
                $changes[$field] = [$oldVal, $newVal];
            }
        }

        CaseHistory::log($id, $userId, 'EDITADO', 'Caso actualizado', $changes ?: null);
        AuditLog::record($userId, 'EDITAR_CASO', 'case', $before['case_number'], $before, $core);
    }

    public function softDelete(int $id, int $userId): void
    {
        $case = $this->find($id);
        if (!$case) return;
        $stmt = $this->db->prepare('UPDATE cases SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
        CaseHistory::log($id, $userId, 'ELIMINADO', 'Caso eliminado (baja logica)', null);
        AuditLog::record($userId, 'ELIMINAR_CASO', 'case', $case['case_number'], $case, null);
    }

    public function find(int $id, bool $includeDeleted = false): ?array
    {
        $sql = 'SELECT c.*, u.full_name AS responsible_name,
                    ua.full_name AS assigned_name, us.full_name AS signed_name,
                    ur.full_name AS reviewed_name, uap.full_name AS approved_name
                FROM cases c
                LEFT JOIN users u ON u.id = c.responsible_user_id
                LEFT JOIN users ua ON ua.id = c.assigned_to
                LEFT JOIN users us ON us.id = c.signed_by
                LEFT JOIN users ur ON ur.id = c.reviewed_by
                LEFT JOIN users uap ON uap.id = c.approved_by
                WHERE c.id = :id';
        if (!$includeDeleted) {
            $sql .= ' AND c.deleted_at IS NULL';
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row && $row['form_data']) {
            $row['form_data_decoded'] = json_decode($row['form_data'], true) ?: [];
        } else if ($row) {
            $row['form_data_decoded'] = [];
        }
        return $row ?: null;
    }

    public function findByNumber(string $caseNumber): ?array
    {
        $stmt = $this->db->prepare('SELECT id FROM cases WHERE case_number = :n LIMIT 1');
        $stmt->execute(['n' => $caseNumber]);
        $row = $stmt->fetch();
        return $row ? $this->find((int) $row['id']) : null;
    }

    // -----------------------------------------------------------------
    // Listado con filtros (busqueda global + filtro global combinable)
    // -----------------------------------------------------------------
    public function buildFilters(array $filters, string $prefix = ''): array
    {
        $where = ['c.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "MATCH(c.case_number, c.address, c.description, c.incident_commander) AGAINST (:{$prefix}q IN BOOLEAN MODE)";
            $params["{$prefix}q"] = '+' . str_replace(' ', '* +', trim($filters['q'])) . '*';
        }
        if (!empty($filters['date_from'])) {
            $where[] = "c.incident_date >= :{$prefix}date_from";
            $params["{$prefix}date_from"] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "c.incident_date <= :{$prefix}date_to";
            $params["{$prefix}date_to"] = $filters['date_to'];
        }
        if (!empty($filters['service_type'])) {
            $where[] = "c.service_type = :{$prefix}service_type";
            $params["{$prefix}service_type"] = $filters['service_type'];
        }
        if (!empty($filters['status'])) {
            $where[] = "c.status = :{$prefix}status";
            $params["{$prefix}status"] = $filters['status'];
        }
        if (!empty($filters['comuna'])) {
            $where[] = "c.comuna = :{$prefix}comuna";
            $params["{$prefix}comuna"] = $filters['comuna'];
        }
        if (!empty($filters['barrio'])) {
            $where[] = "c.barrio = :{$prefix}barrio";
            $params["{$prefix}barrio"] = $filters['barrio'];
        }
        if (!empty($filters['responsible_user_id'])) {
            $where[] = "c.responsible_user_id = :{$prefix}responsible_user_id";
            $params["{$prefix}responsible_user_id"] = $filters['responsible_user_id'];
        }
        if (!empty($filters['priority'])) {
            $where[] = "c.priority = :{$prefix}priority";
            $params["{$prefix}priority"] = $filters['priority'];
        }

        return [implode(' AND ', $where), $params];
    }

    public function paginate(array $filters, int $page = 1, int $perPage = 25, string $orderBy = 'c.incident_date DESC, c.id DESC'): array
    {
        [$whereSql, $params] = $this->buildFilters($filters);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM cases c WHERE $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT c.*, u.full_name AS responsible_name
                FROM cases c LEFT JOIN users u ON u.id = c.responsible_user_id
                WHERE $whereSql ORDER BY $orderBy LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function all(array $filters, string $orderBy = 'c.incident_date DESC, c.id DESC'): array
    {
        [$whereSql, $params] = $this->buildFilters($filters);
        $sql = "SELECT c.*, u.full_name AS responsible_name
                FROM cases c LEFT JOIN users u ON u.id = c.responsible_user_id
                WHERE $whereSql ORDER BY $orderBy";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // -----------------------------------------------------------------
    // Grupos repetibles (edificaciones, personas, animales, personal)
    // -----------------------------------------------------------------
    public function replaceBuildings(int $caseId, array $buildings): void
    {
        $this->db->prepare('DELETE FROM case_buildings WHERE case_id = :id')->execute(['id' => $caseId]);
        $stmt = $this->db->prepare(
            'INSERT INTO case_buildings (case_id, seq, property_type, address, building_use, upper_levels,
                lower_levels, comuna, barrio, stratum, owner_name, owner_phone, occupant_name, occupant_phone,
                company_name, vehicle_type, vehicle_brand, vehicle_model, vehicle_plate, vehicle_color, extra_data)
             VALUES (:case_id, :seq, :property_type, :address, :building_use, :upper_levels, :lower_levels,
                :comuna, :barrio, :stratum, :owner_name, :owner_phone, :occupant_name, :occupant_phone,
                :company_name, :vehicle_type, :vehicle_brand, :vehicle_model, :vehicle_plate, :vehicle_color, :extra_data)'
        );
        $seq = 1;
        foreach ($buildings as $b) {
            if (empty(array_filter($b))) continue;
            $stmt->execute([
                'case_id' => $caseId, 'seq' => $seq++,
                'property_type' => $b['property_type'] ?? null, 'address' => $b['address'] ?? null,
                'building_use' => $b['building_use'] ?? null, 'upper_levels' => $b['upper_levels'] ?: null,
                'lower_levels' => $b['lower_levels'] ?: null, 'comuna' => $b['comuna'] ?? null,
                'barrio' => $b['barrio'] ?? null, 'stratum' => $b['stratum'] ?? null,
                'owner_name' => $b['owner_name'] ?? null, 'owner_phone' => $b['owner_phone'] ?? null,
                'occupant_name' => $b['occupant_name'] ?? null, 'occupant_phone' => $b['occupant_phone'] ?? null,
                'company_name' => $b['company_name'] ?? null, 'vehicle_type' => $b['vehicle_type'] ?? null,
                'vehicle_brand' => $b['vehicle_brand'] ?? null, 'vehicle_model' => $b['vehicle_model'] ?? null,
                'vehicle_plate' => $b['vehicle_plate'] ?? null, 'vehicle_color' => $b['vehicle_color'] ?? null,
                'extra_data' => !empty($b['extra_data']) ? json_encode($b['extra_data'], JSON_UNESCAPED_UNICODE) : null,
            ]);
        }
    }

    public function replacePersons(int $caseId, array $persons): void
    {
        $this->db->prepare('DELETE FROM case_persons WHERE case_id = :id')->execute(['id' => $caseId]);
        $stmt = $this->db->prepare(
            'INSERT INTO case_persons (case_id, seq, full_name, age, sex, rescued, alive)
             VALUES (:case_id, :seq, :full_name, :age, :sex, :rescued, :alive)'
        );
        $seq = 1;
        foreach ($persons as $p) {
            if (empty(array_filter($p))) continue;
            $stmt->execute([
                'case_id' => $caseId, 'seq' => $seq++,
                'full_name' => $p['full_name'] ?? null, 'age' => $p['age'] ?: null,
                'sex' => $p['sex'] ?? null, 'rescued' => $p['rescued'] ?? null, 'alive' => $p['alive'] ?? null,
            ]);
        }
    }

    public function replaceAnimals(int $caseId, array $animals): void
    {
        $this->db->prepare('DELETE FROM case_animals WHERE case_id = :id')->execute(['id' => $caseId]);
        $stmt = $this->db->prepare(
            'INSERT INTO case_animals (case_id, seq, animal_type, size, sex, rescued, alive)
             VALUES (:case_id, :seq, :animal_type, :size, :sex, :rescued, :alive)'
        );
        $seq = 1;
        foreach ($animals as $a) {
            if (empty(array_filter($a))) continue;
            $stmt->execute([
                'case_id' => $caseId, 'seq' => $seq++,
                'animal_type' => $a['animal_type'] ?? null, 'size' => $a['size'] ?? null,
                'sex' => $a['sex'] ?? null, 'rescued' => $a['rescued'] ?? null, 'alive' => $a['alive'] ?? null,
            ]);
        }
    }

    public function replaceFirefighters(int $caseId, array $firefighters): void
    {
        $this->db->prepare('DELETE FROM case_firefighters WHERE case_id = :id')->execute(['id' => $caseId]);
        $stmt = $this->db->prepare(
            'INSERT INTO case_firefighters (case_id, seq, firefighter_name, role, vehicle_value)
             VALUES (:case_id, :seq, :name, :role, :vehicle_value)'
        );
        $seq = 1;
        foreach ($firefighters as $f) {
            if (empty($f['name'])) continue;
            $stmt->execute([
                'case_id' => $caseId, 'seq' => $seq++, 'name' => $f['name'], 'role' => $f['role'] ?? null,
                'vehicle_value' => $f['vehicle_value'] ?: null,
            ]);
        }
    }

    // -----------------------------------------------------------------
    // Flujo: asignacion (radio operador -> bombero), firma (bombero),
    // aprobacion (subcomandancia) y cierre automatico.
    // -----------------------------------------------------------------
    public function assign(int $caseId, int $assignedTo, int $assignedBy): void
    {
        $stmt = $this->db->prepare(
            "UPDATE cases SET assigned_to = :to, assigned_by = :by, assigned_at = NOW(), status = 'asignado'
             WHERE id = :id"
        );
        $stmt->execute(['to' => $assignedTo, 'by' => $assignedBy, 'id' => $caseId]);
        CaseHistory::log($caseId, $assignedBy, 'ASIGNADO', 'Caso asignado a un bombero', ['assigned_to' => [null, $assignedTo]]);
    }

    /** El bombero firma el registro: guarda y cambia de estado automaticamente a "pendiente de revision". */
    public function sign(int $caseId, int $signedBy, string $method, ?string $signaturePath): void
    {
        $stmt = $this->db->prepare(
            "UPDATE cases SET signed_by = :by, signed_at = NOW(), sign_method = :method,
                signature_path = :path, status = 'pendiente_revision'
             WHERE id = :id"
        );
        $stmt->execute(['by' => $signedBy, 'method' => $method, 'path' => $signaturePath, 'id' => $caseId]);
        CaseHistory::log($caseId, $signedBy, 'FIRMADO', "Caso firmado ({$method}), pendiente de revision del Coordinador de Turno", null);
    }

    /** El Coordinador de Turno revisa lo firmado: pasa a pendiente de aprobacion de Subcomandancia. */
    public function review(int $caseId, int $reviewedBy): void
    {
        $stmt = $this->db->prepare(
            "UPDATE cases SET reviewed_by = :by, reviewed_at = NOW(), status = 'pendiente_aprobacion'
             WHERE id = :id"
        );
        $stmt->execute(['by' => $reviewedBy, 'id' => $caseId]);
        CaseHistory::log($caseId, $reviewedBy, 'REVISADO', 'Caso revisado por el Coordinador de Turno, pendiente de aprobacion de Subcomandancia', null);
    }

    /** Subcomandancia aprueba: cierra el caso automaticamente. */
    public function approve(int $caseId, int $approvedBy): void
    {
        $stmt = $this->db->prepare(
            "UPDATE cases SET approved_by = :by, approved_at = NOW(), status = 'cerrado', closed_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute(['by' => $approvedBy, 'id' => $caseId]);
        CaseHistory::log($caseId, $approvedBy, 'APROBADO', 'Caso aprobado por Subcomandancia y cerrado automaticamente', null);
    }

    /** Reabre un caso cerrado/pendiente: vuelve al flujo (asignado/abierto) y limpia firma/revision/aprobacion. Solo Subcomandancia o admin. */
    public function reopen(int $caseId, int $userId): void
    {
        $case = $this->find($caseId);
        if (!$case) return;
        $newStatus = $case['assigned_to'] ? 'asignado' : 'abierto';
        $stmt = $this->db->prepare(
            "UPDATE cases SET status = :status, closed_at = NULL,
                signed_by = NULL, signed_at = NULL, sign_method = NULL, signature_path = NULL,
                reviewed_by = NULL, reviewed_at = NULL,
                approved_by = NULL, approved_at = NULL
             WHERE id = :id"
        );
        $stmt->execute(['status' => $newStatus, 'id' => $caseId]);
        CaseHistory::log($caseId, $userId, 'REABIERTO', 'Caso reabierto por Subcomandancia, firma/revision/aprobacion anteriores fueron limpiadas', null);
    }

    // -----------------------------------------------------------------
    // Adjuntos (fotos de evidencia, censo, firma)
    // -----------------------------------------------------------------
    public function addAttachment(int $caseId, string $kind, string $filePath, ?string $originalName, int $uploadedBy): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO case_attachments (case_id, kind, file_path, original_name, uploaded_by)
             VALUES (:case_id, :kind, :file_path, :original_name, :uploaded_by)'
        );
        $stmt->execute([
            'case_id' => $caseId, 'kind' => $kind, 'file_path' => $filePath,
            'original_name' => $originalName, 'uploaded_by' => $uploadedBy,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getAttachments(int $caseId, ?string $kind = null): array
    {
        $sql = 'SELECT * FROM case_attachments WHERE case_id = :id';
        $params = ['id' => $caseId];
        if ($kind !== null) {
            $sql .= ' AND kind = :kind';
            $params['kind'] = $kind;
        }
        $sql .= ' ORDER BY id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findAttachment(int $caseId, int $attachmentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM case_attachments WHERE id = :aid AND case_id = :cid');
        $stmt->execute(['aid' => $attachmentId, 'cid' => $caseId]);
        return $stmt->fetch() ?: null;
    }

    public function deleteAttachment(int $attachmentId): void
    {
        $this->db->prepare('DELETE FROM case_attachments WHERE id = :id')->execute(['id' => $attachmentId]);
    }

    public function replaceSciObjectives(int $caseId, array $rows): void
    {
        $this->db->prepare('DELETE FROM case_sci_objectives WHERE case_id = :id')->execute(['id' => $caseId]);
        $stmt = $this->db->prepare(
            'INSERT INTO case_sci_objectives (case_id, seq, objective, strategy_tactic)
             VALUES (:case_id, :seq, :objective, :strategy_tactic)'
        );
        $seq = 1;
        foreach ($rows as $r) {
            if (empty(array_filter($r))) continue;
            $stmt->execute([
                'case_id' => $caseId, 'seq' => $seq++,
                'objective' => $r['objective'] ?? null, 'strategy_tactic' => $r['strategy_tactic'] ?? null,
            ]);
        }
    }

    public function getSciObjectives(int $caseId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM case_sci_objectives WHERE case_id = :id ORDER BY seq');
        $stmt->execute(['id' => $caseId]);
        return $stmt->fetchAll();
    }

    public function getBuildings(int $caseId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM case_buildings WHERE case_id = :id ORDER BY seq');
        $stmt->execute(['id' => $caseId]);
        return $stmt->fetchAll();
    }

    public function getPersons(int $caseId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM case_persons WHERE case_id = :id ORDER BY seq');
        $stmt->execute(['id' => $caseId]);
        return $stmt->fetchAll();
    }

    public function getAnimals(int $caseId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM case_animals WHERE case_id = :id ORDER BY seq');
        $stmt->execute(['id' => $caseId]);
        return $stmt->fetchAll();
    }

    public function getFirefighters(int $caseId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM case_firefighters WHERE case_id = :id ORDER BY seq');
        $stmt->execute(['id' => $caseId]);
        return $stmt->fetchAll();
    }

    // -----------------------------------------------------------------
    // KPIs / Dashboard / Analitica -- SIEMPRE calculado en vivo desde BD
    // -----------------------------------------------------------------
    public function kpis(array $filters = []): array
    {
        [$whereSql, $params] = $this->buildFilters($filters);
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'abierto' THEN 1 ELSE 0 END) AS abiertos,
                    SUM(CASE WHEN status = 'en_atencion' THEN 1 ELSE 0 END) AS en_atencion,
                    SUM(CASE WHEN status = 'cerrado' THEN 1 ELSE 0 END) AS cerrados,
                    SUM(CASE WHEN MONTH(incident_date) = MONTH(CURDATE()) AND YEAR(incident_date) = YEAR(CURDATE()) THEN 1 ELSE 0 END) AS este_mes,
                    SUM(CASE WHEN incident_date = CURDATE() THEN 1 ELSE 0 END) AS hoy
                FROM cases c WHERE $whereSql";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: [];
    }

    public function countByMonth(array $filters = [], int $months = 12): array
    {
        [$whereSql, $params] = $this->buildFilters($filters);
        $sql = "SELECT DATE_FORMAT(incident_date, '%Y-%m') AS ym, COUNT(*) AS total
                FROM cases c WHERE $whereSql AND incident_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY ym ORDER BY ym";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue('months', $months, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByField(string $field, array $filters = [], int $limit = 12): array
    {
        $allowed = ['service_type', 'status', 'comuna', 'barrio', 'priority', 'responsible_user_id'];
        if (!in_array($field, $allowed, true)) {
            throw new \InvalidArgumentException('Campo no permitido para agrupar');
        }
        [$whereSql, $params] = $this->buildFilters($filters);
        $sql = "SELECT c.$field AS label, COUNT(*) AS total FROM cases c
                WHERE $whereSql AND c.$field IS NOT NULL AND c.$field <> ''
                GROUP BY c.$field ORDER BY total DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByYear(array $filters = []): array
    {
        [$whereSql, $params] = $this->buildFilters($filters);
        $sql = "SELECT YEAR(incident_date) AS label, COUNT(*) AS total FROM cases c
                WHERE $whereSql AND incident_date IS NOT NULL GROUP BY label ORDER BY label";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Coordenadas de los casos con ubicacion registrada, para el mapa de calor. */
    public function coordinates(array $filters = []): array
    {
        [$whereSql, $params] = $this->buildFilters($filters);
        $sql = "SELECT c.latitude, c.longitude, c.case_number, c.address
                FROM cases c
                WHERE $whereSql AND c.latitude IS NOT NULL AND c.longitude IS NOT NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function affectedTotals(array $filters = []): array
    {
        [$where1, $p1] = $this->buildFilters($filters, 'p1_');
        [$where2, $p2] = $this->buildFilters($filters, 'p2_');
        [$where3, $p3] = $this->buildFilters($filters, 'p3_');
        [$where4, $p4] = $this->buildFilters($filters, 'p4_');
        $sql = "SELECT
                    (SELECT COUNT(*) FROM case_persons p JOIN cases c ON c.id = p.case_id WHERE $where1) AS personas_afectadas,
                    (SELECT COUNT(*) FROM case_persons p JOIN cases c ON c.id = p.case_id WHERE $where2 AND p.rescued IS NOT NULL) AS personas_rescatadas,
                    (SELECT COUNT(*) FROM case_animals a JOIN cases c ON c.id = a.case_id WHERE $where3) AS animales_afectados,
                    (SELECT COUNT(*) FROM case_buildings b JOIN cases c ON c.id = b.case_id WHERE $where4) AS edificaciones_afectadas";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($p1, $p2, $p3, $p4));
        return $stmt->fetch() ?: [];
    }
}
