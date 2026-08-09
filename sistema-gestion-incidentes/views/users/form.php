<?php use App\Helpers\Helpers as H; use App\Helpers\Csrf; ?>
<?php $isEdit = !empty($user); ?>
<div class="section-card" style="max-width:640px;">
  <form method="post" action="<?= H::url($isEdit ? '/users/' . $user['id'] : '/users') ?>">
    <?= Csrf::field() ?>
    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label small fw-semibold">Nombre Completo *</label>
        <input type="text" name="full_name" class="form-control" required value="<?= H::e($user['full_name'] ?? '') ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label small fw-semibold">Nombre de Usuario *</label>
        <?php if ($isEdit): ?>
          <input type="text" class="form-control" value="<?= H::e($user['username']) ?>" disabled>
        <?php else: ?>
          <input type="text" name="username" class="form-control" required>
        <?php endif; ?>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label small fw-semibold">Correo Electronico *</label>
        <input type="email" name="email" class="form-control" required value="<?= H::e($user['email'] ?? '') ?>">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label small fw-semibold">Rol *</label>
        <select name="role_id" class="form-select" required>
          <?php foreach ($roles as $r): ?>
            <option value="<?= $r['id'] ?>" <?= ((string)($user['role_id'] ?? '')) === (string)$r['id'] ? 'selected' : '' ?>><?= H::e($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label small fw-semibold"><?= $isEdit ? 'Nueva Contraseña (opcional)' : 'Contraseña *' ?></label>
        <input type="password" name="password" class="form-control" <?= $isEdit ? '' : 'required minlength="8"' ?>>
        <div class="form-text">Minimo 8 caracteres.</div>
      </div>
      <div class="col-md-6 mb-3 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="active" id="active" <?= (!isset($user) || $user['active']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="active">Usuario activo</label>
        </div>
      </div>
    </div>
    <div class="d-flex justify-content-end gap-2">
      <a href="<?= H::url('/users') ?>" class="btn btn-outline-secondary">Cancelar</a>
      <button type="submit" class="btn btn-danger">Guardar</button>
    </div>
  </form>
</div>
