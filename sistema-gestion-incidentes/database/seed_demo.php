<?php
/**
 * Carga datos de demostracion para probar dashboard, estadisticas,
 * graficos, reportes y filtros. Todos los registros quedan marcados
 * con is_demo = 1 para poder identificarlos y eliminarlos facilmente:
 *
 *   DELETE FROM cases WHERE is_demo = 1;
 *
 * Uso: php database/seed_demo.php [cantidad]
 */
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Models\CaseRecord;
use App\Models\Catalog;
use App\Helpers\Database;

$count = (int) ($argv[1] ?? 40);
$db = Database::connection();

$adminId = (int) $db->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1")->fetchColumn();
if (!$adminId) {
    die("Cree primero el usuario administrador (database/seed_admin.php)\n");
}

$services = Catalog::items('list_servicio');
$comunas = Catalog::items('list_comuna');
$barrios = Catalog::items('list_barrio');
$commanders = Catalog::items('list_field_349');

if (!$services || !$comunas) {
    die("No se encontraron catalogos. Ejecute primero database/seed_catalogs.sql\n");
}

$statuses = ['abierto', 'en_atencion', 'cerrado', 'cerrado', 'cerrado']; // mas cerrados que abiertos
$priorities = ['baja', 'media', 'media', 'alta', 'critica'];
$streets = ['Calle 45', 'Carrera 30', 'Avenida Oriental', 'Calle 10', 'Carrera 65', 'Transversal 12', 'Calle 33'];

$model = new CaseRecord();
$created = 0;

for ($i = 0; $i < $count; $i++) {
    $daysAgo = random_int(0, 365);
    $date = date('Y-m-d', strtotime("-$daysAgo days"));
    $service = $services[array_rand($services)];
    $comuna = $comunas[array_rand($comunas)];
    $barrio = $barrios ? $barrios[array_rand($barrios)] : null;
    $commander = $commanders ? $commanders[array_rand($commanders)] : null;

    $core = [
        'service_type' => $service['item_value'],
        'incident_date' => $date,
        'report_time' => sprintf('%02d:%02d:00', random_int(0, 23), random_int(0, 59)),
        'departure_time' => sprintf('%02d:%02d:00', random_int(0, 23), random_int(0, 59)),
        'arrival_time' => sprintf('%02d:%02d:00', random_int(0, 23), random_int(0, 59)),
        'closure_time' => sprintf('%02d:%02d:00', random_int(0, 23), random_int(0, 59)),
        'incident_commander' => $commander['item_value'] ?? null,
        'address' => $streets[array_rand($streets)] . ' # ' . random_int(1, 99) . '-' . random_int(1, 99),
        'comuna' => $comuna['item_value'],
        'barrio' => $barrio['item_value'] ?? null,
        'description' => 'Registro de demostracion generado automaticamente para pruebas del sistema.',
        'status' => $statuses[array_rand($statuses)],
        'priority' => $priorities[array_rand($priorities)],
        'responsible_user_id' => $adminId,
    ];

    $id = $model->create($core, [], $adminId, true);

    if (random_int(0, 3) === 0) {
        $model->replacePersons($id, [[
            'full_name' => 'Persona de Prueba ' . ($i + 1),
            'age' => random_int(5, 80),
            'sex' => null, 'rescued' => null, 'alive' => null,
        ]]);
    }

    $created++;
}

echo "Se crearon {$created} casos de demostracion (is_demo = 1).\n";
echo "Para eliminarlos: DELETE FROM cases WHERE is_demo = 1;\n";
