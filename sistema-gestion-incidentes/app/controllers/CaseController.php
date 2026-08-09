<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Helpers as H;
use App\Helpers\View;
use App\Models\CaseRecord;
use App\Models\CaseHistory;
use App\Models\Catalog;
use App\Models\Setting;
use App\Models\User;

class CaseController
{
    private CaseRecord $model;

    public function __construct()
    {
        $this->model = new CaseRecord();
    }

    // -----------------------------------------------------------------
    public function index(): void
    {
        Auth::requireAbility('case.view');

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

        $page = max(1, (int) H::input('page', 1));
        $result = $this->model->paginate($filters, $page, 25);

        View::render('cases/index', [
            'pageTitle' => 'Casos',
            'pageSubtitle' => 'Consulta, busqueda y filtrado de casos registrados',
            'active' => 'cases',
            'cases' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 25,
            'filters' => $filters,
            'services' => Catalog::items('list_servicio'),
            'comunas' => Catalog::items('list_comuna'),
            'users' => (new User())->all(),
        ]);
    }

    // -----------------------------------------------------------------
    public function create(): void
    {
        Auth::requireAbility('case.create');
        View::render('cases/form', $this->formViewData(null));
    }

    public function edit(string $id): void
    {
        Auth::requireAbility('case.view');
        $case = $this->model->find((int) $id);
        if (!$case) { H::redirect('/cases'); }
        $this->authorizeEdit($case);

        View::render('cases/form', $this->formViewData($case));
    }

    /**
     * Verifica el PIN de seguridad (segunda clave) para acciones sensibles:
     * firmar, aprobar, o editar un caso ya firmado/cerrado. Si el usuario
     * no tiene PIN configurado o el PIN ingresado es incorrecto, redirige
     * de vuelta con un mensaje y no continua.
     */
    private function requirePin(string $redirectTo, string $fieldName = 'security_pin'): void
    {
        $userModel = new User();
        if (!$userModel->hasPin(Auth::id())) {
            H::flash('danger', 'Primero configure su PIN de seguridad en "Mi Perfil" para poder realizar esta accion.');
            H::redirect($redirectTo);
        }
        $pin = trim((string) H::input($fieldName));
        if (!$pin || !$userModel->verifyPin(Auth::id(), $pin)) {
            H::flash('danger', 'El PIN de seguridad ingresado no es correcto.');
            H::redirect($redirectTo);
        }
    }

    private function authorizeEdit(array $case): void
    {
        if (Auth::isAdmin()) return;
        if (!Auth::can('case.edit_own')) {
            http_response_code(403);
            require BASE_PATH . '/views/errors/403.php';
            exit;
        }
        $mine = (int) ($case['created_by'] ?? 0) === Auth::id()
            || (int) ($case['responsible_user_id'] ?? 0) === Auth::id()
            || (int) ($case['assigned_to'] ?? 0) === Auth::id();
        if (!$mine) {
            http_response_code(403);
            require BASE_PATH . '/views/errors/403.php';
            exit;
        }
    }

    private function formViewData(?array $case): array
    {
        $sections = require BASE_PATH . '/config/form_sections.php';
        $templates = require BASE_PATH . '/config/repeat_templates.php';

        return [
            'pageTitle' => $case ? 'Editar Caso ' . $case['case_number'] : 'Nuevo Caso',
            'pageSubtitle' => 'Complete la informacion del Formato Unico de Recoleccion de Datos (FURD)',
            'active' => 'case-new',
            'case' => $case,
            'formData' => $case['form_data_decoded'] ?? [],
            'buildings' => $case ? $this->model->getBuildings((int) $case['id']) : [],
            'persons' => $case ? $this->model->getPersons((int) $case['id']) : [],
            'animals' => $case ? $this->model->getAnimals((int) $case['id']) : [],
            'firefighters' => $case ? $this->model->getFirefighters((int) $case['id']) : [],
            'sciObjectives' => $case ? $this->model->getSciObjectives((int) $case['id']) : [],
            'evidenceFiles' => $case ? $this->model->getAttachments((int) $case['id'], 'evidencia') : [],
            'censoFiles' => $case ? $this->model->getAttachments((int) $case['id'], 'censo') : [],
            'sections' => $sections,
            'templates' => $templates,
            'statuses' => $this->statuses(),
            'priorities' => ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'critica' => 'Critica'],
            'users' => (new User())->all(),
            'comandantes' => Catalog::items('list_field_349'),
            'jefesTurno' => Catalog::items('list_jefe_de_turno'),
            'vehiculos' => Catalog::items('list_vehiculos'),
        ];
    }

    private function statuses(): array
    {
        $db = \App\Helpers\Database::connection();
        return $db->query('SELECT code, label FROM case_statuses ORDER BY sort_order')->fetchAll();
    }

    // -----------------------------------------------------------------
    public function store(): void
    {
        Auth::requireAbility('case.create');
        Csrf::verifyRequest();

        [$core, $formData, $buildings, $persons, $animals, $firefighters, $sciObjectives] = $this->extractInput();

        $errors = $this->validate($core);
        if ($errors) {
            H::flash('danger', implode(' ', $errors));
            H::redirect('/cases/create');
        }

        $id = $this->model->create($core, $formData, Auth::id());
        $this->model->replaceBuildings($id, $buildings);
        $this->model->replacePersons($id, $persons);
        $this->model->replaceAnimals($id, $animals);
        $this->model->replaceFirefighters($id, $firefighters);
        $this->model->replaceSciObjectives($id, $sciObjectives);
        $this->storeUploads($id);

        H::flash('success', 'Registro guardado correctamente.');
        H::redirect('/cases/' . $id);
    }

    public function update(string $id): void
    {
        Auth::requireAbility('case.view');
        Csrf::verifyRequest();
        $case = $this->model->find((int) $id);
        if (!$case) { H::redirect('/cases'); }
        $this->authorizeEdit($case);

        if (in_array($case['status'], ['pendiente_aprobacion', 'cerrado'], true)) {
            $this->requirePin('/cases/' . $id . '/edit', 'unlock_pin');
        }

        [$core, $formData, $buildings, $persons, $animals, $firefighters, $sciObjectives] = $this->extractInput();

        $errors = $this->validate($core);
        if ($errors) {
            H::flash('danger', implode(' ', $errors));
            H::redirect('/cases/' . $id . '/edit');
        }

        $this->model->update((int) $id, $core, $formData, Auth::id());
        $this->model->replaceBuildings((int) $id, $buildings);
        $this->model->replacePersons((int) $id, $persons);
        $this->model->replaceAnimals((int) $id, $animals);
        $this->model->replaceFirefighters((int) $id, $firefighters);
        $this->model->replaceSciObjectives((int) $id, $sciObjectives);
        $this->storeUploads((int) $id);

        H::flash('success', 'Registro actualizado correctamente.');
        H::redirect('/cases/' . $id);
    }

    /** Convierte el logo institucional configurado en Settings a data URI para incrustarlo en el PDF. */
    private function logoDataUri(): ?string
    {
        $path = Setting::get('logo_path');
        if (!$path) return null;
        $file = BASE_PATH . '/public' . $path;
        if (!is_file($file)) return null;
        $mime = mime_content_type($file) ?: 'image/png';
        if ($mime === 'image/svg+xml') return null; // Dompdf no soporta bien SVG embebido
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file));
    }

    /** Guarda las fotos de evidencia y el censo anexo subidos con el formulario. */
    private function storeUploads(int $caseId): void
    {
        $dir = BASE_PATH . '/storage/uploads/cases/' . $caseId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $allowedImages = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if (!empty($_FILES['evidencia_files']) && is_array($_FILES['evidencia_files']['tmp_name'])) {
            foreach ($_FILES['evidencia_files']['tmp_name'] as $i => $tmpName) {
                if (!$tmpName || $_FILES['evidencia_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $mime = mime_content_type($tmpName) ?: '';
                if (!isset($allowedImages[$mime])) continue;
                $filename = 'evidencia_' . bin2hex(random_bytes(6)) . '.' . $allowedImages[$mime];
                if (move_uploaded_file($tmpName, $dir . '/' . $filename)) {
                    $this->model->addAttachment($caseId, 'evidencia', $filename, $_FILES['evidencia_files']['name'][$i] ?? null, Auth::id());
                }
            }
        }

        if (!empty($_FILES['censo_file']) && $_FILES['censo_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['censo_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'xls', 'doc', 'docx'], true)) {
                $filename = 'censo_' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (move_uploaded_file($_FILES['censo_file']['tmp_name'], $dir . '/' . $filename)) {
                    $this->model->addAttachment($caseId, 'censo', $filename, $_FILES['censo_file']['name'], Auth::id());
                }
            }
        }
    }

    public function deleteAttachment(string $id, string $attachmentId): void
    {
        Csrf::verifyRequest();
        $case = $this->model->find((int) $id);
        if (!$case) { H::redirect('/cases'); }
        $this->authorizeEdit($case);

        $attachment = $this->model->findAttachment((int) $id, (int) $attachmentId);
        if ($attachment) {
            $path = BASE_PATH . '/storage/uploads/cases/' . $id . '/' . $attachment['file_path'];
            if (is_file($path)) unlink($path);
            $this->model->deleteAttachment((int) $attachmentId);
        }
        H::redirect('/cases/' . $id . '/edit');
    }

    public function file(string $id, string $attachmentId): void
    {
        Auth::requireAbility('case.view');
        $attachment = $this->model->findAttachment((int) $id, (int) $attachmentId);
        if (!$attachment) {
            http_response_code(404);
            exit('Archivo no encontrado.');
        }
        $path = BASE_PATH . '/storage/uploads/cases/' . $id . '/' . $attachment['file_path'];
        if (!is_file($path)) {
            http_response_code(404);
            exit('Archivo no encontrado.');
        }
        header('Content-Type: ' . (mime_content_type($path) ?: 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . basename($attachment['original_name'] ?? $attachment['file_path']) . '"');
        readfile($path);
        exit;
    }

    // -----------------------------------------------------------------
    // Flujo por roles: asignar (radio operador / coordinador), firmar
    // (bombero asignado), aprobar (subcomandancia)
    // -----------------------------------------------------------------
    public function assign(string $id): void
    {
        Auth::requireAbility('case.assign');
        Csrf::verifyRequest();
        $case = $this->model->find((int) $id);
        if (!$case) { H::redirect('/cases'); }

        $assignedTo = (int) H::input('assigned_to');
        $bomberoIds = array_column((new User())->allByRole('bombero'), 'id');
        if (!$assignedTo || !in_array($assignedTo, $bomberoIds, true)) {
            H::flash('danger', 'Seleccione un bombero valido para asignar el caso.');
            H::redirect('/cases/' . $id);
        }

        $this->model->assign((int) $id, $assignedTo, Auth::id());
        H::flash('success', 'Caso asignado correctamente.');
        H::redirect('/cases/' . $id);
    }

    public function sign(string $id): void
    {
        Auth::requireAbility('case.sign');
        Csrf::verifyRequest();
        $case = $this->model->find((int) $id);
        if (!$case) { H::redirect('/cases'); }

        if (!Auth::isAdmin() && (int) ($case['assigned_to'] ?? 0) !== Auth::id()) {
            http_response_code(403);
            require BASE_PATH . '/views/errors/403.php';
            exit;
        }
        if (!in_array($case['status'], ['asignado', 'en_atencion'], true)) {
            H::flash('danger', 'Este caso no esta en un estado que permita firmarlo.');
            H::redirect('/cases/' . $id);
        }

        $this->requirePin('/cases/' . $id);

        $method = H::input('sign_method', 'codigo');
        $signaturePath = null;

        if ($method === 'perfil') {
            $userModel = new User();
            $profile = $userModel->find(Auth::id());
            $savedPath = $profile['signature_path'] ?? null;
            $savedDir = BASE_PATH . '/storage/uploads/users/' . Auth::id();
            if (!$savedPath || !is_file($savedDir . '/' . $savedPath)) {
                H::flash('danger', 'No tiene una firma guardada en su perfil. Vaya a "Mi Perfil" para subir una.');
                H::redirect('/cases/' . $id);
            }
            $ext = pathinfo($savedPath, PATHINFO_EXTENSION);
            $dir = BASE_PATH . '/storage/uploads/cases/' . $id;
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $filename = 'firma_' . bin2hex(random_bytes(6)) . '.' . $ext;
            if (copy($savedDir . '/' . $savedPath, $dir . '/' . $filename)) {
                $this->model->addAttachment((int) $id, 'firma', $filename, 'firma_perfil.' . $ext, Auth::id());
                $signaturePath = $filename;
            }
        } elseif ($method === 'dibujo' && H::input('signature_data')) {
            $data = H::input('signature_data');
            if (preg_match('/^data:image\/png;base64,(.+)$/', $data, $m)) {
                $bytes = base64_decode($m[1]);
                if ($bytes !== false && strlen($bytes) < 2_000_000) {
                    $dir = BASE_PATH . '/storage/uploads/cases/' . $id;
                    if (!is_dir($dir)) mkdir($dir, 0775, true);
                    $filename = 'firma_' . bin2hex(random_bytes(6)) . '.png';
                    file_put_contents($dir . '/' . $filename, $bytes);
                    $attId = $this->model->addAttachment((int) $id, 'firma', $filename, 'firma.png', Auth::id());
                    $signaturePath = $filename;
                }
            }
        } elseif ($method === 'foto' && !empty($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
            $mime = mime_content_type($_FILES['signature_file']['tmp_name']) ?: '';
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (isset($allowed[$mime])) {
                $dir = BASE_PATH . '/storage/uploads/cases/' . $id;
                if (!is_dir($dir)) mkdir($dir, 0775, true);
                $filename = 'firma_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
                if (move_uploaded_file($_FILES['signature_file']['tmp_name'], $dir . '/' . $filename)) {
                    $this->model->addAttachment((int) $id, 'firma', $filename, $_FILES['signature_file']['name'], Auth::id());
                    $signaturePath = $filename;
                }
            }
        } elseif ($method === 'codigo') {
            $expected = $_SESSION['sign_code_' . $id] ?? null;
            $entered = H::input('sign_code');
            if (!$expected || !$entered || !hash_equals((string) $expected, (string) $entered)) {
                H::flash('danger', 'El codigo de confirmacion no es correcto o expiro. Genere uno nuevo e intente otra vez.');
                H::redirect('/cases/' . $id);
            }
            unset($_SESSION['sign_code_' . $id]);
        } else {
            H::flash('danger', 'Debe dibujar la firma, subir una foto de la firma, o confirmar con el codigo.');
            H::redirect('/cases/' . $id);
        }

        $this->model->sign((int) $id, Auth::id(), $method, $signaturePath);
        H::flash('success', 'Caso firmado. Queda pendiente de aprobacion de Subcomandancia.');
        H::redirect('/cases/' . $id);
    }

    /** Genera (o regenera) el codigo de confirmacion de firma para esta sesion. */
    public function signCode(string $id): void
    {
        Auth::requireAbility('case.sign');
        $code = (string) random_int(100000, 999999);
        $_SESSION['sign_code_' . $id] = $code;
        H::jsonResponse(['code' => $code]);
    }

    public function approve(string $id): void
    {
        Auth::requireAbility('case.approve');
        Csrf::verifyRequest();
        $case = $this->model->find((int) $id);
        if (!$case) { H::redirect('/cases'); }

        if ($case['status'] !== 'pendiente_aprobacion') {
            H::flash('danger', 'Este caso no esta pendiente de aprobacion.');
            H::redirect('/cases/' . $id);
        }

        $this->requirePin('/cases/' . $id);

        $this->model->approve((int) $id, Auth::id());
        H::flash('success', 'Caso aprobado y cerrado.');
        H::redirect('/cases/' . $id);
    }

    private function validate(array $core): array
    {
        $errors = [];
        if (empty($core['incident_date'])) {
            $errors[] = 'La fecha del incidente es obligatoria.';
        }
        if (empty($core['service_type'])) {
            $errors[] = 'El servicio es obligatorio.';
        }
        if (empty($core['address'])) {
            $errors[] = 'La direccion del incidente es obligatoria.';
        }
        return $errors;
    }

    private function extractInput(): array
    {
        $core = [
            'service_type' => H::input('service_type'),
            'incident_date' => H::input('incident_date'),
            'report_time' => H::input('report_time') ?: null,
            'departure_time' => H::input('departure_time') ?: null,
            'arrival_time' => H::input('arrival_time') ?: null,
            'closure_time' => H::input('closure_time') ?: null,
            'station_time' => H::input('station_time') ?: null,
            'incident_commander' => H::input('incident_commander'),
            'address' => H::input('address'),
            'comuna' => H::input('comuna'),
            'barrio' => H::input('barrio'),
            'latitude' => H::input('latitude') ?: null,
            'longitude' => H::input('longitude') ?: null,
            'description' => H::input('description'),
            'shift_chief' => H::input('shift_chief'),
            'status' => H::input('status', 'abierto'),
            'priority' => H::input('priority', 'media'),
            'responsible_user_id' => H::input('responsible_user_id') ?: null,
        ];

        // Campos dinamicos del formulario original (form_data JSON)
        $sections = require BASE_PATH . '/config/form_sections.php';
        $formData = [];
        foreach ($sections as $section) {
            foreach ($section['fields'] as $field) {
                $name = $field['name'];
                if ($field['type'] === 'select_multiple') {
                    $formData[$name] = $_POST['fd'][$name] ?? [];
                } else {
                    $formData[$name] = $_POST['fd'][$name] ?? null;
                }
            }
        }

        $buildings = $_POST['building'] ?? [];
        $persons = $_POST['person'] ?? [];
        $animals = $_POST['animal'] ?? [];
        $firefighterNames = $_POST['firefighter_name'] ?? [];
        $firefighterRoles = $_POST['firefighter_role'] ?? [];
        $firefighterVehicles = $_POST['firefighter_vehicle'] ?? [];
        $firefighters = [];
        foreach ($firefighterNames as $i => $name) {
            if (trim((string) $name) === '') continue;
            $firefighters[] = [
                'name' => $name,
                'role' => $firefighterRoles[$i] ?? null,
                'vehicle_value' => $firefighterVehicles[$i] ?? null,
            ];
        }

        $sciObjectives = $_POST['sci_objective'] ?? [];

        return [$core, $formData, $buildings, $persons, $animals, $firefighters, $sciObjectives];
    }

    // -----------------------------------------------------------------
    public function show(string $id): void
    {
        Auth::requireAbility('case.view');
        $case = $this->model->find((int) $id);
        if (!$case) { H::redirect('/cases'); }

        View::render('cases/show', [
            'pageTitle' => 'Caso ' . $case['case_number'],
            'pageSubtitle' => $case['address'] ?? '',
            'active' => 'cases',
            'case' => $case,
            'buildings' => $this->model->getBuildings((int) $id),
            'persons' => $this->model->getPersons((int) $id),
            'animals' => $this->model->getAnimals((int) $id),
            'firefighters' => $this->model->getFirefighters((int) $id),
            'sciObjectives' => $this->model->getSciObjectives((int) $id),
            'evidenceFiles' => $this->model->getAttachments((int) $id, 'evidencia'),
            'censoFiles' => $this->model->getAttachments((int) $id, 'censo'),
            'signatureFiles' => $this->model->getAttachments((int) $id, 'firma'),
            'bomberos' => (new User())->allByRole('bombero'),
            'currentUser' => (new User())->find(Auth::id()),
            'history' => CaseHistory::forCase((int) $id),
            'sections' => require BASE_PATH . '/config/form_sections.php',
        ]);
    }

    public function destroy(string $id): void
    {
        Csrf::verifyRequest();
        $case = $this->model->find((int) $id);
        if (!$case) { H::redirect('/cases'); }
        if (!Auth::isAdmin() && (int) $case['created_by'] !== Auth::id()) {
            http_response_code(403);
            require BASE_PATH . '/views/errors/403.php';
            exit;
        }
        $this->model->softDelete((int) $id, Auth::id());
        H::flash('success', 'Caso eliminado correctamente.');
        H::redirect('/cases');
    }

    public function duplicate(string $id): void
    {
        Auth::requireAbility('case.create');
        Csrf::verifyRequest();
        $case = $this->model->find((int) $id);
        if (!$case) { H::redirect('/cases'); }

        $core = $case;
        $core['status'] = 'abierto';
        $newId = $this->model->create($core, $case['form_data_decoded'] ?? [], Auth::id());
        $this->model->replaceBuildings($newId, $this->model->getBuildings((int) $id));
        $this->model->replacePersons($newId, $this->model->getPersons((int) $id));
        $this->model->replaceAnimals($newId, $this->model->getAnimals((int) $id));

        H::flash('success', 'Caso duplicado correctamente. Revise y guarde los cambios necesarios.');
        H::redirect('/cases/' . $newId . '/edit');
    }

    // -----------------------------------------------------------------
    public function pdf(string $id): void
    {
        Auth::requireAbility('case.report');
        $case = $this->model->find((int) $id);
        if (!$case) { H::redirect('/cases'); }

        $uploadDir = BASE_PATH . '/storage/uploads/cases/' . $id . '/';
        $toDataUri = function (array $attachment) use ($uploadDir): ?string {
            $path = $uploadDir . $attachment['file_path'];
            if (!is_file($path)) return null;
            $mime = mime_content_type($path) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        };

        $evidencePhotos = [];
        foreach ($this->model->getAttachments((int) $id, 'evidencia') as $a) {
            $uri = $toDataUri($a);
            if ($uri) $evidencePhotos[] = ['uri' => $uri, 'name' => $a['original_name']];
        }
        $signatureAttachments = $this->model->getAttachments((int) $id, 'firma');
        $signaturePhoto = null;
        if ($case['signature_path']) {
            $match = array_values(array_filter($signatureAttachments, fn($a) => $a['file_path'] === $case['signature_path']));
            if ($match) $signaturePhoto = $toDataUri($match[0]);
        }

        $data = [
            'case' => $case,
            'buildings' => $this->model->getBuildings((int) $id),
            'persons' => $this->model->getPersons((int) $id),
            'animals' => $this->model->getAnimals((int) $id),
            'firefighters' => $this->model->getFirefighters((int) $id),
            'sciObjectives' => $this->model->getSciObjectives((int) $id),
            'evidencePhotos' => $evidencePhotos,
            'signaturePhoto' => $signaturePhoto,
            'history' => CaseHistory::forCase((int) $id),
            'sections' => require BASE_PATH . '/config/form_sections.php',
            'entityName' => Setting::get('entity_name', 'Cuerpo de Bomberos'),
            'systemName' => Setting::get('system_name', 'Sistema de Gestion de Incidentes'),
            'logoDataUri' => $this->logoDataUri(),
            'generatedBy' => Auth::user()['full_name'] ?? '',
            'generatedAt' => date('d/m/Y H:i'),
        ];
        extract($data);

        ob_start();
        require BASE_PATH . '/views/cases/pdf.php';
        $html = ob_get_clean();

        if (class_exists('\Dompdf\Dompdf')) {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('letter', 'portrait');
            $dompdf->render();
            \App\Models\AuditLog::record(Auth::id(), 'GENERAR_PDF', 'case', $case['case_number'], null, null);
            $dompdf->stream('Reporte_' . $case['case_number'] . '.pdf', ['Attachment' => false]);
            exit;
        }

        // Respaldo si Dompdf no esta instalado (composer install pendiente): entrega el HTML imprimible
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}
