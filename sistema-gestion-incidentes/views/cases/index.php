<?php
use App\Helpers\Helpers as H;
use App\Helpers\Auth;
use App\Models\Catalog;

$statusLabels = ['abierto' => 'Abierto', 'asignado' => 'Asignado', 'en_atencion' => 'En Atencion', 'pendiente_aprobacion' => 'Pendiente de Aprobacion', 'cerrado' => 'Cerrado'];
$priorityLabels = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'critica' => 'Critica'];
$queryBase = $filters;
?>
<form method="get" class="filter-bar">
  <div class="row g-2">
    <div class="col-12 col-md-3">
      <label class="form-label small mb-1">Busqueda global</label>
      <input type="text" name="q" value="<?= H::e($filters['q'] ?? '') ?>" class="form-control form-control-sm" placeholder="Numero de caso, direccion, descripcion...">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small mb-1">Fecha inicial</label>
      <input type="date" name="date_from" value="<?= H::e($filters['date_from'] ?? '') ?>" class="form-control form-control-sm">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small mb-1">Fecha final</label>
      <input type="date" name="date_to" value="<?= H::e($filters['date_to'] ?? '') ?>" class="form-control form-control-sm">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small mb-1">Categoria (Servicio)</label>
      <select name="service_type" class="form-select form-select-sm">
        <option value="">Todas</option>
        <?php foreach ($services as $s): ?>
          <option value="<?= H::e($s['item_value']) ?>" <?= ($filters['service_type'] ?? '') === $s['item_value'] ? 'selected' : '' ?>><?= H::e($s['item_label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small mb-1">Estado</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">Todos</option>
        <?php foreach ($statusLabels as $code => $label): ?>
          <option value="<?= $code ?>" <?= ($filters['status'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small mb-1">Comuna</label>
      <select name="comuna" class="form-select form-select-sm">
        <option value="">Todas</option>
        <?php foreach ($comunas as $c): ?>
          <option value="<?= H::e($c['item_value']) ?>" <?= ($filters['comuna'] ?? '') === $c['item_value'] ? 'selected' : '' ?>><?= H::e($c['item_label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small mb-1">Responsable</label>
      <select name="responsible_user_id" class="form-select form-select-sm">
        <option value="">Todos</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= $u['id'] ?>" <?= ((string)($filters['responsible_user_id'] ?? '')) === (string)$u['id'] ? 'selected' : '' ?>><?= H::e($u['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label small mb-1">Prioridad</label>
      <select name="priority" class="form-select form-select-sm">
        <option value="">Todas</option>
        <?php foreach ($priorityLabels as $code => $label): ?>
          <option value="<?= $code ?>" <?= ($filters['priority'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md-2 d-flex align-items-end gap-2">
      <button class="btn btn-danger btn-sm flex-fill"><i class="bi bi-search"></i> Buscar</button>
      <a href="<?= H::url('/cases') ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a>
    </div>
  </div>
</form>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="section-title mb-0"><?= (int)$total ?> caso(s) encontrado(s)</div>
    <div class="d-flex gap-2">
      <a href="<?= H::url('/export/excel?' . http_build_query($filters)) ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Exportar Excel</a>
      <?php if (Auth::can('case.create')): ?>
        <a href="<?= H::url('/cases/create') ?>" class="btn btn-danger btn-sm"><i class="bi bi-plus-lg"></i> Nuevo Caso</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (empty($cases)): ?>
    <div class="empty-state"><i class="bi bi-inbox"></i>No se encontraron casos con los filtros seleccionados.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle data-table" style="width:100%">
      <thead>
        <tr>
          <th>Caso</th><th>Servicio</th><th>Fecha</th><th>Direccion</th><th>Comuna</th>
          <th>Responsable</th><th>Prioridad</th><th>Estado</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($cases as $c): ?>
        <tr>
          <td class="fw-semibold"><?= H::e($c['case_number']) ?></td>
          <td><?= H::e(Catalog::label('list_servicio', $c['service_type']) ?? '-') ?></td>
          <td><?= H::formatDate($c['incident_date']) ?></td>
          <td><?= H::e($c['address'] ?? '-') ?></td>
          <td><?= H::e($c['comuna'] ?? '-') ?></td>
          <td><?= H::e($c['responsible_name'] ?? '-') ?></td>
          <td><span class="priority-<?= H::e($c['priority']) ?>"><?= H::e($priorityLabels[$c['priority']] ?? $c['priority']) ?></span></td>
          <td><span class="status-badge status-<?= H::e($c['status']) ?>"><?= H::e($statusLabels[$c['status']] ?? $c['status']) ?></span></td>
          <td class="text-nowrap">
            <a href="<?= H::url('/cases/' . $c['id']) ?>" class="btn btn-sm btn-outline-danger" title="Ver"><i class="bi bi-eye"></i></a>
            <?php if (Auth::can('case.edit_own') || Auth::isAdmin()): ?>
            <a href="<?= H::url('/cases/' . $c['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if (Auth::isAdmin()): ?>
            <form action="<?= H::url('/cases/' . $c['id'] . '/delete') ?>" method="post" class="d-inline" data-confirm="¿Eliminar el caso <?= H::e($c['case_number']) ?>? Esta accion no se puede deshacer.">
              <?= \App\Helpers\Csrf::field() ?>
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
