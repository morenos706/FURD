<?php
use App\Helpers\Helpers as H;
use App\Helpers\Auth;
use App\Models\Setting;

$systemName = Setting::get('system_name', 'Sistema de Gestion de Incidentes');
$entityName = Setting::get('entity_name', 'Cuerpo de Bomberos');
$user = Auth::user();
$active = $active ?? '';

$menu = [
    ['key' => 'dashboard', 'label' => 'Inicio / Dashboard', 'icon' => 'layout-dashboard', 'href' => '/dashboard', 'ability' => 'stats.view'],
    ['key' => 'cases', 'label' => 'Casos', 'icon' => 'folder-open', 'href' => '/cases', 'ability' => 'case.view'],
    ['key' => 'case-new', 'label' => 'Nuevo Caso', 'icon' => 'file-plus-2', 'href' => '/cases/create', 'ability' => 'case.create'],
    ['key' => 'analytics', 'label' => 'Analitica', 'icon' => 'brain-circuit', 'href' => '/analytics', 'ability' => 'stats.view'],
    ['key' => 'reports', 'label' => 'Reportes', 'icon' => 'file-text', 'href' => '/reports', 'ability' => 'case.report'],
    ['key' => 'export', 'label' => 'Exportaciones', 'icon' => 'download', 'href' => '/export', 'ability' => 'export.own'],
    ['key' => 'users', 'label' => 'Usuarios', 'icon' => 'users', 'href' => '/users', 'ability' => 'admin_only'],
    ['key' => 'audit', 'label' => 'Auditoria', 'icon' => 'shield-check', 'href' => '/audit', 'ability' => 'admin_only'],
    ['key' => 'settings', 'label' => 'Configuracion', 'icon' => 'settings', 'href' => '/settings', 'ability' => 'admin_only'],
    ['key' => 'profile', 'label' => 'Mi Perfil', 'icon' => 'person-badge', 'href' => '/profile', 'ability' => 'logged_in'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= H::e($pageTitle ?? 'Dashboard') ?> · <?= H::e($systemName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.11/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="<?= H::url('/css/app.css') ?>">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <?php $logoPath = Setting::get('logo_path'); ?>
      <?php if ($logoPath): ?>
        <img src="<?= H::url($logoPath) ?>" alt="Logo" class="sidebar-logo">
      <?php else: ?>
        <i class="bi bi-fire"></i>
      <?php endif; ?>
      <div class="brand-text">
        <div class="brand-name"><?= H::e($systemName) ?></div>
        <div class="brand-sub"><?= H::e($entityName) ?></div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <?php foreach ($menu as $item):
          if ($item['ability'] === 'admin_only' && !Auth::isAdmin()) continue;
          if ($item['ability'] === 'logged_in') { /* visible para cualquier usuario logueado */ }
          elseif (!Auth::can($item['ability'])) continue;
      ?>
        <a href="<?= H::url($item['href']) ?>" class="nav-link <?= $active === $item['key'] ? 'active' : '' ?>">
          <i class="bi bi-<?= H::e($item['icon']) ?>"></i>
          <span><?= H::e($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
      <form action="<?= H::url('/logout') ?>" method="post" class="nav-logout-form">
        <?= \App\Helpers\Csrf::field() ?>
        <button type="submit" class="nav-link nav-logout">
          <i class="bi bi-box-arrow-right"></i>
          <span>Cerrar sesion</span>
        </button>
      </form>
    </nav>
  </aside>

  <div class="main-col">
    <header class="topbar">
      <button class="btn-icon d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><?= H::e($pageTitle ?? '') ?></h1>
        <?php if (!empty($pageSubtitle)): ?><p><?= H::e($pageSubtitle) ?></p><?php endif; ?>
      </div>
      <div class="topbar-user">
        <span class="badge-role"><?= H::e($user['role_name'] ?? '') ?></span>
        <div class="user-chip">
          <div class="user-avatar"><?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?></div>
          <div class="user-name"><?= H::e($user['full_name'] ?? '') ?></div>
        </div>
      </div>
    </header>

    <main class="content">
      <?php foreach (H::getFlashes() as $flash): ?>
        <div class="alert alert-<?= H::e($flash['type']) ?> alert-dismissible fade show" role="alert">
          <?= H::e($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endforeach; ?>

      <?= $content ?? '' ?>
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.11/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.11/js/dataTables.bootstrap5.min.js"></script>
<script src="<?= H::url('/js/app.js') ?>"></script>
<?= $extraScripts ?? '' ?>
</body>
</html>
