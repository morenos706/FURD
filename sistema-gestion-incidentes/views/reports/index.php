<?php use App\Helpers\Helpers as H; use App\Helpers\Csrf; ?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="section-card mb-3">
      <div class="form-section-title">Tipo de Reporte</div>
      <div class="list-group">
        <button class="list-group-item list-group-item-action active" data-type="fechas" onclick="selectReportType(this,'fechas')"><i class="bi bi-calendar-range me-2"></i>Por Fechas</button>
        <button class="list-group-item list-group-item-action" data-type="categoria" onclick="selectReportType(this,'categoria')"><i class="bi bi-tags me-2"></i>Por Categoria (Servicio)</button>
        <button class="list-group-item list-group-item-action" data-type="estado" onclick="selectReportType(this,'estado')"><i class="bi bi-flag me-2"></i>Por Estado</button>
        <button class="list-group-item list-group-item-action" data-type="responsable" onclick="selectReportType(this,'responsable')"><i class="bi bi-person-badge me-2"></i>Por Responsable</button>
        <button class="list-group-item list-group-item-action" data-type="ubicacion" onclick="selectReportType(this,'ubicacion')"><i class="bi bi-geo-alt me-2"></i>Por Ubicacion</button>
        <button class="list-group-item list-group-item-action" data-type="custom" onclick="selectReportType(this,'custom')"><i class="bi bi-sliders me-2"></i>Personalizado</button>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="section-card">
      <div class="form-section-title">Parametros del Reporte</div>
      <form method="post" action="<?= H::url('/reports/generate') ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="report_type" id="report_type" value="fechas">

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Desde</label>
            <input type="date" name="date_from" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Hasta</label>
            <input type="date" name="date_to" class="form-control">
          </div>
          <div class="col-md-6 mt-2">
            <label class="form-label small fw-semibold">Servicio (Categoria)</label>
            <select name="service_type" class="form-select">
              <option value="">Todas</option>
              <?php foreach ($services as $s): ?><option value="<?= H::e($s['item_value']) ?>"><?= H::e($s['item_label']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 mt-2">
            <label class="form-label small fw-semibold">Estado</label>
            <select name="status" class="form-select">
              <option value="">Todos</option>
              <option value="abierto">Abierto</option>
              <option value="asignado">Asignado</option>
              <option value="en_atencion">En Atencion</option>
              <option value="pendiente_aprobacion">Pendiente de Aprobacion</option>
              <option value="cerrado">Cerrado</option>
            </select>
          </div>
          <div class="col-md-6 mt-2">
            <label class="form-label small fw-semibold">Comuna / Ubicacion</label>
            <select name="comuna" class="form-select">
              <option value="">Todas</option>
              <?php foreach ($comunas as $c): ?><option value="<?= H::e($c['item_value']) ?>"><?= H::e($c['item_label']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 mt-2">
            <label class="form-label small fw-semibold">Responsable</label>
            <select name="responsible_user_id" class="form-select">
              <option value="">Todos</option>
              <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"><?= H::e($u['full_name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-section-title">Campos a Incluir</div>
        <div class="row mb-3">
          <?php foreach ($columns as $key => $label): ?>
            <div class="col-md-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="fields[]" value="<?= H::e($key) ?>" id="col_<?= H::e($key) ?>" checked>
                <label class="form-check-label small" for="col_<?= H::e($key) ?>"><?= H::e($label) ?></label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-danger"><i class="bi bi-file-earmark-excel"></i> Generar Reporte (Excel)</button>
      </form>
    </div>
  </div>
</div>

<?php $extraScripts = '<script>
function selectReportType(btn, type) {
  document.querySelectorAll(".list-group-item").forEach(el => el.classList.remove("active"));
  btn.classList.add("active");
  document.getElementById("report_type").value = type;
}
</script>'; ?>
