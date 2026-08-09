<?php
use App\Helpers\Helpers as H;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Models\Catalog;

$statusLabels = ['abierto' => 'Abierto', 'en_atencion' => 'En Atencion', 'cerrado' => 'Cerrado'];
$priorityLabels = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'critica' => 'Critica'];
$fd = $case['form_data_decoded'] ?? [];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
  <div>
    <h3 class="mb-1">Caso #<?= H::e($case['case_number']) ?> <span class="status-badge status-<?= H::e($case['status']) ?>"><?= H::e($statusLabels[$case['status']] ?? $case['status']) ?></span></h3>
    <p class="text-muted mb-0"><?= H::e($case['address'] ?? '') ?> · <?= H::formatDate($case['incident_date']) ?></p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= H::url('/cases/' . $case['id'] . '/pdf') ?>" target="_blank" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> Generar Reporte</a>
    <?php if (Auth::isAdmin() || Auth::can('case.edit_own')): ?>
      <a href="<?= H::url('/cases/' . $case['id'] . '/edit') ?>" class="btn btn-danger"><i class="bi bi-pencil"></i> Editar</a>
    <?php endif; ?>
    <?php if (Auth::can('case.create')): ?>
    <form action="<?= H::url('/cases/' . $case['id'] . '/duplicate') ?>" method="post" class="d-inline">
      <?= Csrf::field() ?>
      <button class="btn btn-outline-secondary"><i class="bi bi-copy"></i> Duplicar</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">

    <div class="section-card mb-3">
      <div class="form-section-title">Informacion General</div>
      <div class="row small">
        <div class="col-md-4 mb-2"><strong>Servicio:</strong><br><?= H::e(Catalog::label('list_servicio', $case['service_type']) ?? '-') ?></div>
        <div class="col-md-4 mb-2"><strong>Comandante:</strong><br><?= H::e(Catalog::label('list_field_349', $case['incident_commander']) ?? '-') ?></div>
        <div class="col-md-4 mb-2"><strong>Prioridad:</strong><br><span class="priority-<?= H::e($case['priority']) ?>"><?= H::e($priorityLabels[$case['priority']] ?? $case['priority']) ?></span></div>
        <div class="col-md-3 mb-2"><strong>Hora de Reporte:</strong><br><?= H::e($case['report_time'] ?? '-') ?></div>
        <div class="col-md-3 mb-2"><strong>Hora de Salida:</strong><br><?= H::e($case['departure_time'] ?? '-') ?></div>
        <div class="col-md-3 mb-2"><strong>Hora de Llegada:</strong><br><?= H::e($case['arrival_time'] ?? '-') ?></div>
        <div class="col-md-3 mb-2"><strong>Hora de Cierre:</strong><br><?= H::e($case['closure_time'] ?? '-') ?></div>
        <div class="col-md-6 mb-2"><strong>Comuna / Barrio:</strong><br><?= H::e($case['comuna'] ?? '-') ?> / <?= H::e($case['barrio'] ?? '-') ?></div>
        <div class="col-md-6 mb-2"><strong>Responsable:</strong><br><?= H::e($case['responsible_name'] ?? 'Sin asignar') ?></div>
        <div class="col-12 mb-2"><strong>Descripcion:</strong><br><?= nl2br(H::e($case['description'] ?? '-')) ?></div>
      </div>
    </div>

    <?php if ($buildings): ?>
    <div class="section-card mb-3">
      <div class="form-section-title">Edificaciones / Vehiculos Afectados</div>
      <?php foreach ($buildings as $b): ?>
        <div class="border rounded p-2 mb-2 small">
          <strong><?= H::e($b['property_type'] ?: 'Inmueble/Vehiculo') ?></strong> — <?= H::e($b['address'] ?: '-') ?><br>
          Uso: <?= H::e($b['building_use'] ?: '-') ?> · Propietario: <?= H::e($b['owner_name'] ?: '-') ?> · Placa: <?= H::e($b['vehicle_plate'] ?: '-') ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($persons): ?>
    <div class="section-card mb-3">
      <div class="form-section-title">Personas Afectadas (<?= count($persons) ?>)</div>
      <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>Nombre</th><th>Edad</th><th>Sexo</th><th>Rescatado</th><th>Con Vida</th></tr></thead>
        <tbody><?php foreach ($persons as $p): ?>
          <tr><td><?= H::e($p['full_name'] ?: '-') ?></td><td><?= H::e($p['age'] ?: '-') ?></td><td><?= H::e($p['sex'] ?: '-') ?></td><td><?= H::e($p['rescued'] ?: '-') ?></td><td><?= H::e($p['alive'] ?: '-') ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </div>
    <?php endif; ?>

    <?php if ($animals): ?>
    <div class="section-card mb-3">
      <div class="form-section-title">Animales Afectados (<?= count($animals) ?>)</div>
      <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>Tipo</th><th>Tamaño</th><th>Sexo</th><th>Rescatado</th><th>Con Vida</th></tr></thead>
        <tbody><?php foreach ($animals as $a): ?>
          <tr><td><?= H::e($a['animal_type'] ?: '-') ?></td><td><?= H::e($a['size'] ?: '-') ?></td><td><?= H::e($a['sex'] ?: '-') ?></td><td><?= H::e($a['rescued'] ?: '-') ?></td><td><?= H::e($a['alive'] ?: '-') ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </div>
    <?php endif; ?>

    <?php foreach ($sections as $slug => $section):
        $rows = array_filter($section['fields'], fn($f) => !empty($fd[$f['name']]) && !is_array($fd[$f['name']]));
        if (!$rows) continue; ?>
      <div class="section-card mb-3">
        <div class="form-section-title"><?= H::e($section['label']) ?></div>
        <div class="row small">
          <?php foreach ($rows as $f): ?>
            <div class="col-md-4 mb-2"><strong><?= H::e($f['label']) ?>:</strong><br>
              <?= H::e($f['list'] ? (Catalog::label($f['list'], $fd[$f['name']]) ?? '-') : (string) $fd[$f['name']]) ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

  </div>

  <div class="col-lg-4">
    <div class="section-card">
      <div class="form-section-title">Linea de Tiempo</div>
      <?php if (empty($history)): ?>
        <p class="text-muted small mb-0">Sin movimientos registrados.</p>
      <?php else: ?>
      <ul class="timeline">
        <?php foreach ($history as $h): ?>
          <li>
            <div class="fw-semibold small"><?= H::e($h['action']) ?></div>
            <div class="small text-muted"><?= H::formatDateTime($h['created_at']) ?> · <?= H::e($h['user_name'] ?? 'Sistema') ?></div>
            <?php if ($h['summary']): ?><div class="small"><?= H::e($h['summary']) ?></div><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
