<?php
/**
 * Importa el Excel exportado de Survey123/ArcGIS (hoja "survey_0", 242 columnas)
 * como casos historicos (status=cerrado) en el sistema.
 *
 * Uso:
 *   php database/import_survey123.php /ruta/al/archivo.xlsx [--wipe]
 *
 *   --wipe   Borra todos los casos existentes (y sus datos relacionados,
 *            por ON DELETE CASCADE) y reinicia la numeracion antes de
 *            importar. Usuarios, roles y catalogos NO se tocan.
 *
 * Tambien se puede importar sin usar la terminal, desde el navegador en
 * Configuracion > Importar Datos Historicos (solo Administrador).
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Services\Survey123Importer;

$path = $argv[1] ?? null;
$wipe = in_array('--wipe', $argv, true);

if (!$path || !is_file($path)) {
    fwrite(STDERR, "Uso: php import_survey123.php /ruta/al/archivo.xlsx [--wipe]\n");
    exit(1);
}

echo "Cargando $path ...\n";
if ($wipe) echo "Se van a borrar todos los casos existentes antes de importar.\n";

$result = (new Survey123Importer())->import($path, $wipe, function (int $count) {
    echo "  ... $count casos importados\n";
});

echo "Importacion completa: {$result['imported']} casos importados, {$result['skipped']} filas vacias omitidas, "
    . count($result['errors']) . " filas con error.\n";
if ($result['errors']) {
    echo "Detalle de errores:\n";
    foreach ($result['errors'] as $e) echo "  - $e\n";
}
