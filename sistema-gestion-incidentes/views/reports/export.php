<?php use App\Helpers\Helpers as H; ?>
<div class="section-card mb-3">
  <div class="form-section-title">Exportacion Rapida</div>
  <p class="text-muted small">Descargue registros en Excel segun distintos criterios. Estas exportaciones incluyen encabezados, fecha de generacion, filtros utilizados y totales.</p>
  <div class="row g-2">
    <div class="col-md-3"><a class="btn btn-outline-success w-100" href="<?= H::url('/export/excel') ?>"><i class="bi bi-file-earmark-excel"></i> Exportar Todos</a></div>
    <div class="col-md-3"><a class="btn btn-outline-success w-100" href="<?= H::url('/export/excel?status=abierto') ?>"><i class="bi bi-file-earmark-excel"></i> Solo Abiertos</a></div>
    <div class="col-md-3"><a class="btn btn-outline-success w-100" href="<?= H::url('/export/excel?status=cerrado') ?>"><i class="bi bi-file-earmark-excel"></i> Solo Cerrados</a></div>
    <div class="col-md-3"><a class="btn btn-outline-success w-100" href="<?= H::url('/export/excel?date_from=' . date('Y-m-01') . '&date_to=' . date('Y-m-d')) ?>"><i class="bi bi-file-earmark-excel"></i> Este Mes</a></div>
  </div>
</div>

<div class="section-card">
  <div class="form-section-title">Exportacion con Filtros Personalizados</div>
  <form method="get" action="<?= H::url('/export/excel') ?>">
    <div class="row g-2">
      <div class="col-md-3">
        <label class="form-label small">Desde</label>
        <input type="date" name="date_from" class="form-control form-control-sm">
      </div>
      <div class="col-md-3">
        <label class="form-label small">Hasta</label>
        <input type="date" name="date_to" class="form-control form-control-sm">
      </div>
      <div class="col-md-3">
        <label class="form-label small">Servicio</label>
        <select name="service_type" class="form-select form-select-sm">
          <option value="">Todos</option>
          <?php foreach ($services as $s): ?><option value="<?= H::e($s['item_value']) ?>"><?= H::e($s['item_label']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small">Comuna</label>
        <select name="comuna" class="form-select form-select-sm">
          <option value="">Todas</option>
          <?php foreach ($comunas as $c): ?><option value="<?= H::e($c['item_value']) ?>"><?= H::e($c['item_label']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small">Responsable</label>
        <select name="responsible_user_id" class="form-select form-select-sm">
          <option value="">Todos</option>
          <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"><?= H::e($u['full_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-success btn-sm w-100"><i class="bi bi-download"></i> Exportar</button>
      </div>
    </div>
  </form>
</div>
