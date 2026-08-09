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

    private function authorizeEdit(array $case): void
    {
        if (Auth::isAdmin()) return;
        if (!Auth::can('case.edit_own')) {
            http_response_code(403);
            require BASE_PATH . '/views/errors/403.php';
            exit;
        }
        if ((int) ($case['created_by'] ?? 0) !== Auth::id() && (int) ($case['responsible_user_id'] ?? 0) !== Auth::id()) {
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

        [$core, $formData, $buildings, $persons, $animals, $firefighters] = $this->extractInput();

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

        [$core, $formData, $buildings, $persons, $animals, $firefighters] = $this->extractInput();

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

        H::flash('success', 'Registro actualizado correctamente.');
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
        $firefighters = [];
        foreach ($firefighterNames as $i => $name) {
            if (trim((string) $name) === '') continue;
            $firefighters[] = ['name' => $name, 'role' => $firefighterRoles[$i] ?? null];
        }

        return [$core, $formData, $buildings, $persons, $animals, $firefighters];
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

        $data = [
            'case' => $case,
            'buildings' => $this->model->getBuildings((int) $id),
            'persons' => $this->model->getPersons((int) $id),
            'animals' => $this->model->getAnimals((int) $id),
            'firefighters' => $this->model->getFirefighters((int) $id),
            'history' => CaseHistory::forCase((int) $id),
            'sections' => require BASE_PATH . '/config/form_sections.php',
            'entityName' => Setting::get('entity_name', 'Cuerpo de Bomberos'),
            'systemName' => Setting::get('system_name', 'Sistema de Gestion de Incidentes'),
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
