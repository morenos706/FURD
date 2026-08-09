<?php
use App\Helpers\Helpers as H;
use App\Helpers\Csrf;
use App\Models\Setting;

$systemName = Setting::get('system_name', 'Sistema de Gestion de Incidentes');
$entityName = Setting::get('entity_name', 'Cuerpo de Bomberos');
$logoPath = Setting::get('logo_path');
$loginBgPath = Setting::get('login_bg_path');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesion · <?= H::e($systemName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
  body {
    <?php if ($loginBgPath): ?>
    background: linear-gradient(135deg, rgba(28,35,51,.82) 0%, rgba(58,33,41,.82) 100%), url('<?= H::url($loginBgPath) ?>') center/cover no-repeat fixed;
    <?php else: ?>
    background: linear-gradient(135deg,#1c2333 0%, #3a2129 100%);
    <?php endif; ?>
    min-height:100vh; display:flex; align-items:center; font-family:'Segoe UI',system-ui,sans-serif;
  }
  .login-card { max-width: 420px; width:100%; margin:auto; border:none; border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,.35); }
  .login-brand { text-align:center; padding:2rem 2rem 0; }
  .login-brand i { font-size:2.4rem; color:#c0392b; }
  .login-brand img { max-height:64px; max-width:100%; }
  .login-brand h1 { font-size:1.15rem; font-weight:700; margin:.5rem 0 0; }
  .login-brand p { font-size:.8rem; color:#6b7280; }
  .btn-danger { background:#c0392b; border-color:#c0392b; }
  .btn-danger:hover { background:#96281b; border-color:#96281b; }
</style>
</head>
<body>
  <div class="card login-card">
    <div class="login-brand">
      <?php if ($logoPath): ?>
        <img src="<?= H::url($logoPath) ?>" alt="Logo">
      <?php else: ?>
        <i class="bi bi-fire"></i>
      <?php endif; ?>
      <h1><?= H::e($systemName) ?></h1>
      <p><?= H::e($entityName) ?></p>
    </div>
    <div class="card-body p-4">
      <?php foreach (H::getFlashes() as $flash): ?>
        <div class="alert alert-<?= H::e($flash['type']) ?>"><?= H::e($flash['message']) ?></div>
      <?php endforeach; ?>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2"><?= H::e($error) ?></div>
      <?php endif; ?>
      <form method="post" action="<?= H::url('/login') ?>">
        <?= Csrf::field() ?>
        <div class="mb-3">
          <label class="form-label">Usuario</label>
          <input type="text" name="username" class="form-control" required autofocus value="<?= H::e($_POST['username'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-danger w-100">Ingresar</button>
      </form>
    </div>
  </div>
</body>
</html>
