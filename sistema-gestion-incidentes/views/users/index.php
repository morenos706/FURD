<?php use App\Helpers\Helpers as H; use App\Helpers\Csrf; use App\Helpers\Auth; ?>
<div class="section-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="section-title mb-0"><?= count($users) ?> usuario(s)</div>
    <a href="<?= H::url('/users/create') ?>" class="btn btn-danger btn-sm"><i class="bi bi-person-plus"></i> Nuevo Usuario</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle data-table" style="width:100%">
      <thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Ultimo Acceso</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td class="fw-semibold"><?= H::e($u['full_name']) ?></td>
          <td><?= H::e($u['username']) ?></td>
          <td><?= H::e($u['email']) ?></td>
          <td><span class="badge-role"><?= H::e($u['role_name']) ?></span></td>
          <td><?= $u['active'] ? '<span class="status-badge status-cerrado">Activo</span>' : '<span class="status-badge status-abierto">Inactivo</span>' ?></td>
          <td class="small text-muted"><?= H::formatDateTime($u['last_login_at']) ?></td>
          <td class="text-nowrap">
            <a href="<?= H::url('/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <?php if ((int)$u['id'] !== Auth::id()): ?>
            <form action="<?= H::url('/users/' . $u['id'] . '/toggle') ?>" method="post" class="d-inline" data-confirm="¿Cambiar el estado de este usuario?">
              <?= Csrf::field() ?>
              <button type="submit" class="btn btn-sm btn-outline-<?= $u['active'] ? 'danger' : 'success' ?>">
                <i class="bi bi-<?= $u['active'] ? 'lock' : 'unlock' ?>"></i>
              </button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
