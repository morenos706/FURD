<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Helpers as H;
use App\Helpers\View;
use App\Models\AuditLog;
use App\Models\Catalog;
use App\Models\Setting;

class SettingsController
{
    private function requireAdmin(): void
    {
        Auth::requireLogin();
        if (!Auth::isAdmin()) {
            http_response_code(403);
            require BASE_PATH . '/views/errors/403.php';
            exit;
        }
    }

    public function index(): void
    {
        $this->requireAdmin();
        View::render('settings/index', [
            'pageTitle' => 'Configuracion',
            'pageSubtitle' => 'Datos institucionales, apariencia y respaldo del sistema',
            'active' => 'settings',
            'settings' => Setting::all(),
        ]);
    }

    public function update(): void
    {
        $this->requireAdmin();
        Csrf::verifyRequest();

        $fields = ['system_name', 'entity_name', 'entity_nit', 'primary_color', 'timezone'];
        foreach ($fields as $f) {
            Setting::set($f, H::input($f, ''));
        }

        AuditLog::record(Auth::id(), 'ACTUALIZAR_CONFIGURACION', 'settings', null, null, null);
        H::flash('success', 'Configuracion actualizada correctamente.');
        H::redirect('/settings');
    }

    public function catalogs(): void
    {
        $this->requireAdmin();
        $listName = H::input('list', 'list_servicio');
        View::render('settings/catalogs', [
            'pageTitle' => 'Configuracion · Categorias y Listas',
            'pageSubtitle' => 'Edite las listas desplegables usadas en los formularios',
            'active' => 'settings',
            'lists' => Catalog::allListNames(),
            'currentList' => $listName,
            'items' => Catalog::items($listName, false),
        ]);
    }

    public function catalogsUpdate(): void
    {
        $this->requireAdmin();
        Csrf::verifyRequest();

        $listName = H::input('list_name');
        $action = H::input('form_action', 'create');

        if ($action === 'create') {
            $value = trim((string) H::input('item_value'));
            $label = trim((string) H::input('item_label'));
            if ($value !== '' && $label !== '') {
                Catalog::create($listName, $value, $label);
                AuditLog::record(Auth::id(), 'CREAR_CATEGORIA', 'catalog_item', $listName, null, ['value' => $value, 'label' => $label]);
            }
        } else {
            $id = (int) H::input('item_id');
            $label = trim((string) H::input('item_label'));
            $active = (bool) H::input('active');
            Catalog::update($id, $label, $active);
            AuditLog::record(Auth::id(), 'EDITAR_CATEGORIA', 'catalog_item', (string) $id, null, ['label' => $label, 'active' => $active]);
        }

        H::flash('success', 'Lista actualizada correctamente.');
        H::redirect('/settings/catalogs?list=' . urlencode($listName));
    }

    public function catalogsDelete(): void
    {
        $this->requireAdmin();
        Csrf::verifyRequest();
        $id = (int) H::input('item_id');
        $listName = H::input('list_name');
        Catalog::delete($id);
        AuditLog::record(Auth::id(), 'ELIMINAR_CATEGORIA', 'catalog_item', (string) $id, null, null);
        H::flash('success', 'Elemento eliminado.');
        H::redirect('/settings/catalogs?list=' . urlencode($listName));
    }

    public function backupDownload(): void
    {
        $this->requireAdmin();

        $config = require BASE_PATH . '/config/config.php';
        $db = $config['db'];
        $filename = 'backup_' . $db['database'] . '_' . date('Ymd_His') . '.sql';

        $mysqldump = trim((string) shell_exec('command -v mysqldump'));
        if ($mysqldump === '') {
            H::flash('danger', 'mysqldump no esta disponible en el servidor. Contacte al proveedor de hosting para generar el respaldo desde phpMyAdmin o linea de comandos.');
            H::redirect('/settings');
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment;filename="' . $filename . '"');

        $cmd = sprintf(
            '%s --host=%s --port=%s --user=%s --password=%s %s 2>/dev/null',
            escapeshellcmd($mysqldump),
            escapeshellarg($db['host']),
            escapeshellarg((string) $db['port']),
            escapeshellarg($db['username']),
            escapeshellarg($db['password']),
            escapeshellarg($db['database'])
        );
        passthru($cmd);

        $conn = Database::connection();
        $stmt = $conn->prepare('INSERT INTO backups_log (filename, created_by) VALUES (:f, :u)');
        $stmt->execute(['f' => $filename, 'u' => Auth::id()]);
        AuditLog::record(Auth::id(), 'GENERAR_BACKUP', 'system', null, null, null);
        exit;
    }
}
