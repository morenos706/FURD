<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Helpers as H;
use App\Helpers\View;
use App\Models\AuditLog;
use App\Models\User;

class ProfileController
{
    public function show(): void
    {
        Auth::requireLogin();
        $model = new User();
        $user = $model->find(Auth::id());

        View::render('profile/index', [
            'pageTitle' => 'Mi Perfil',
            'pageSubtitle' => 'Firma digital guardada y PIN de seguridad',
            'active' => 'profile',
            'user' => $user,
        ]);
    }

    /** Sube (o reemplaza) la imagen de firma guardada en el perfil. */
    public function uploadSignature(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        if (empty($_FILES['signature_file']) || $_FILES['signature_file']['error'] !== UPLOAD_ERR_OK) {
            H::flash('danger', 'Seleccione una imagen de firma valida.');
            H::redirect('/profile');
        }

        $mime = mime_content_type($_FILES['signature_file']['tmp_name']) ?: '';
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            H::flash('danger', 'Formato no soportado. Use JPG, PNG o WEBP.');
            H::redirect('/profile');
        }

        $userId = Auth::id();
        $dir = BASE_PATH . '/storage/uploads/users/' . $userId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $filename = 'firma_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
        if (move_uploaded_file($_FILES['signature_file']['tmp_name'], $dir . '/' . $filename)) {
            $model = new User();
            $old = $model->find($userId)['signature_path'] ?? null;
            $model->setSignaturePath($userId, $filename);
            if ($old && is_file($dir . '/' . $old)) {
                unlink($dir . '/' . $old);
            }
            AuditLog::record($userId, 'ACTUALIZAR_FIRMA_PERFIL', 'user', (string) $userId, null, null);
            H::flash('success', 'Firma guardada correctamente. Se usara al firmar casos con el metodo "Mi Firma".');
        } else {
            H::flash('danger', 'No se pudo guardar el archivo.');
        }
        H::redirect('/profile');
    }

    public function signatureFile(string $userId): void
    {
        Auth::requireLogin();
        if ((int) $userId !== Auth::id() && !Auth::isAdmin()) {
            http_response_code(403);
            exit('No autorizado.');
        }
        $model = new User();
        $user = $model->find((int) $userId);
        $path = $user['signature_path'] ?? null;
        $file = BASE_PATH . '/storage/uploads/users/' . $userId . '/' . $path;
        if (!$path || !is_file($file)) {
            http_response_code(404);
            exit('Sin firma guardada.');
        }
        header('Content-Type: ' . (mime_content_type($file) ?: 'image/png'));
        readfile($file);
        exit;
    }

    /** Define o cambia el PIN de seguridad (requiere la clave de inicio de sesion actual). */
    public function setPin(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $currentPassword = (string) H::input('current_password');
        $pin = trim((string) H::input('pin'));
        $pinConfirm = trim((string) H::input('pin_confirm'));

        $model = new User();
        $user = $model->find(Auth::id());

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            H::flash('danger', 'Su clave de inicio de sesion actual no es correcta.');
            H::redirect('/profile');
        }
        if (!preg_match('/^\d{4,6}$/', $pin)) {
            H::flash('danger', 'El PIN debe tener entre 4 y 6 digitos numericos.');
            H::redirect('/profile');
        }
        if ($pin !== $pinConfirm) {
            H::flash('danger', 'Los dos PIN ingresados no coinciden.');
            H::redirect('/profile');
        }

        $model->setSecurityPin(Auth::id(), $pin);
        AuditLog::record(Auth::id(), 'ACTUALIZAR_PIN_PERFIL', 'user', (string) Auth::id(), null, null);
        H::flash('success', 'PIN de seguridad configurado correctamente.');
        H::redirect('/profile');
    }
}
