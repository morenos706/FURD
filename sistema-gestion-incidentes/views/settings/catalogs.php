<?php use App\Helpers\Helpers as H; use App\Helpers\Csrf; ?>
<div class="row g-3">
  <div class="col-lg-3">
    <div class="section-card" style="max-height:75vh; overflow-y:auto;">
      <div class="form-section-title">Listas (<?= count($lists) ?>)</div>
      <div class="list-group list-group-flush">
        <?php foreach ($lists as $l): ?>
          <a href="<?= H::url('/settings/catalogs?list=' . urlencode($l)) ?>" class="list-group-item list-group-item-action <?= $l === $currentList ? 'active' : '' ?> small"><?= H::e($l) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-9">
    <div class="section-card mb-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-section-title mb-0">Lista: <?= H::e($currentList) ?></div>
        <span class="badge-role"><?= count($items) ?> opciones</span>
      </div>

      <form method="post" action="<?= H::url('/settings/catalogs') ?>" class="row g-2 mb-3">
        <?= Csrf::field() ?>
        <input type="hidden" name="form_action" value="create">
        <input type="hidden" name="list_name" value="<?= H::e($currentList) ?>">
        <div class="col-md-4"><input type="text" name="item_value" class="form-control form-control-sm" placeholder="Valor" required></div>
        <div class="col-md-5"><input type="text" name="item_label" class="form-control form-control-sm" placeholder="Etiqueta visible" required></div>
        <div class="col-md-3"><button class="btn btn-danger btn-sm w-100"><i class="bi bi-plus-lg"></i> Agregar Opcion</button></div>
      </form>

      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Valor</th><th>Etiqueta</th><th>Activo</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td class="small text-muted"><?= H::e($item['item_value']) ?></td>
              <td>
                <form method="post" action="<?= H::url('/settings/catalogs') ?>" class="d-flex gap-2 align-items-center">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="form_action" value="update">
                  <input type="hidden" name="list_name" value="<?= H::e($currentList) ?>">
                  <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                  <input type="text" name="item_label" value="<?= H::e($item['item_label']) ?>" class="form-control form-control-sm">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="active" <?= $item['active'] ? 'checked' : '' ?>>
                  </div>
                  <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-check2"></i></button>
                </form>
              </td>
              <td><?= $item['active'] ? '<span class="status-badge status-cerrado">Si</span>' : '<span class="status-badge status-abierto">No</span>' ?></td>
              <td>
                <form method="post" action="<?= H::url('/settings/catalogs/delete') ?>" data-confirm="¿Eliminar esta opcion de la lista?">
                  <?= Csrf::field() ?>
                  <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                  <input type="hidden" name="list_name" value="<?= H::e($currentList) ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
