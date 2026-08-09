<?php
use App\Helpers\Helpers as H;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Models\Catalog;

$statusLabels = ['abierto' => 'Abierto', 'asignado' => 'Asignado', 'en_atencion' => 'En Atencion', 'pendiente_aprobacion' => 'Pendiente de Aprobacion', 'cerrado' => 'Cerrado'];
$priorityLabels = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'critica' => 'Critica'];
$fd = $case['form_data_decoded'] ?? [];

$canAssign = Auth::can('case.assign');
$isAssignedToMe = Auth::isAdmin() || (int) ($case['assigned_to'] ?? 0) === Auth::id();
$canSignThis = Auth::can('case.sign') && $isAssignedToMe && in_array($case['status'], ['asignado', 'en_atencion'], true);
$canApprove = Auth::can('case.approve') && $case['status'] === 'pendiente_aprobacion';
$canReopen = Auth::can('case.reopen') && in_array($case['status'], ['pendiente_aprobacion', 'cerrado'], true);
?>
<?php if ($case['latitude'] && $case['longitude']): ?><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"><?php endif; ?>
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
      <?php if ($case['latitude'] && $case['longitude']): ?>
        <div id="showMap" style="height:260px;border-radius:.5rem;" data-lat="<?= H::e($case['latitude']) ?>" data-lng="<?= H::e($case['longitude']) ?>"></div>
      <?php endif; ?>
    </div>

    <?php if ($evidenceFiles): ?>
    <div class="section-card mb-3">
      <div class="form-section-title">Evidencias Fotograficas</div>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($evidenceFiles as $ev): ?>
          <a href="<?= H::url('/cases/' . $case['id'] . '/files/' . $ev['id']) ?>" target="_blank">
            <img src="<?= H::url('/cases/' . $case['id'] . '/files/' . $ev['id']) ?>" alt="Evidencia" style="width:120px;height:120px;object-fit:cover;border-radius:.5rem;border:1px solid #dee2e6;">
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($firefighters): ?>
    <div class="section-card mb-3">
      <div class="form-section-title">Personal (Bomberos) y Vehiculo</div>
      <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>Nombre</th><th>Rol / Cargo</th><th>Vehiculo</th></tr></thead>
        <tbody><?php foreach ($firefighters as $f): ?>
          <tr><td><?= H::e($f['firefighter_name'] ?: '-') ?></td><td><?= H::e($f['role'] ?: '-') ?></td><td><?= H::e($f['vehicle_value'] ?: '-') ?></td></tr>
        <?php endforeach; ?></tbody>
      </table></div>
    </div>
    <?php endif; ?>

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

    <?php if ($sciObjectives): ?>
    <div class="section-card mb-3">
      <div class="form-section-title">Objetivos, Estrategias y Tacticas (SCI)</div>
      <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>Objetivo</th><th>Estrategia y Tactica</th></tr></thead>
        <tbody><?php foreach ($sciObjectives as $s): ?>
          <tr><td><?= nl2br(H::e($s['objective'] ?: '-')) ?></td><td><?= nl2br(H::e($s['strategy_tactic'] ?: '-')) ?></td></tr>
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
    <div class="section-card mb-3">
      <div class="form-section-title">Flujo del Caso</div>
      <div class="small mb-3">
        <div class="mb-1"><strong>Estado:</strong> <span class="status-badge status-<?= H::e($case['status']) ?>"><?= H::e($statusLabels[$case['status']] ?? $case['status']) ?></span></div>
        <div class="mb-1"><strong>Asignado a:</strong> <?= H::e($case['assigned_name'] ?? 'Sin asignar') ?></div>
        <?php if ($case['signed_at']): ?><div class="mb-1"><strong>Firmado por:</strong> <?= H::e($case['signed_name'] ?? '-') ?> · <?= H::formatDateTime($case['signed_at']) ?></div><?php endif; ?>
        <?php if ($case['approved_at']): ?><div class="mb-1"><strong>Aprobado por:</strong> <?= H::e($case['approved_name'] ?? '-') ?> · <?= H::formatDateTime($case['approved_at']) ?></div><?php endif; ?>
      </div>

      <?php if ($canAssign && $case['status'] !== 'cerrado'): ?>
      <form action="<?= H::url('/cases/' . $case['id'] . '/assign') ?>" method="post" class="mb-3">
        <?= Csrf::field() ?>
        <label class="form-label small fw-semibold">Asignar a Bombero</label>
        <div class="input-group input-group-sm">
          <select name="assigned_to" class="form-select" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($bomberos as $b): ?>
              <option value="<?= $b['id'] ?>" <?= ((int) ($case['assigned_to'] ?? 0) === (int) $b['id']) ? 'selected' : '' ?>><?= H::e($b['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-danger" type="submit"><i class="bi bi-send"></i></button>
        </div>
      </form>
      <?php endif; ?>

      <?php $hasPin = !empty($currentUser['security_pin_hash']); $hasSavedSignature = !empty($currentUser['signature_path']); ?>
      <?php if ($canSignThis): ?>
      <button type="button" class="btn btn-sm btn-danger w-100 mb-2" data-bs-toggle="collapse" data-bs-target="#signPanel"><i class="bi bi-pen"></i> Firmar Caso</button>
      <div class="collapse" id="signPanel">
        <div class="border rounded p-2 mb-3">
          <?php if (!$hasPin): ?>
            <div class="alert alert-warning small py-2">Configure su PIN de seguridad en <a href="<?= H::url('/profile') ?>">Mi Perfil</a> antes de firmar.</div>
          <?php endif; ?>
          <ul class="nav nav-tabs nav-tabs-sm small mb-2" id="signMethodTabs">
            <?php if ($hasSavedSignature): ?><li class="nav-item"><button type="button" class="nav-link active" data-sign-tab="perfil">Mi Firma</button></li><?php endif; ?>
            <li class="nav-item"><button type="button" class="nav-link <?= $hasSavedSignature ? '' : 'active' ?>" data-sign-tab="dibujo">Dibujar</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-sign-tab="foto">Subir Foto</button></li>
            <li class="nav-item"><button type="button" class="nav-link" data-sign-tab="codigo">Codigo</button></li>
          </ul>

          <form action="<?= H::url('/cases/' . $case['id'] . '/sign') ?>" method="post" enctype="multipart/form-data" id="signForm">
            <?= Csrf::field() ?>
            <input type="hidden" name="sign_method" id="signMethodInput" value="<?= $hasSavedSignature ? 'perfil' : 'dibujo' ?>">
            <input type="hidden" name="signature_data" id="signatureDataInput">

            <?php if ($hasSavedSignature): ?>
            <div data-sign-pane="perfil">
              <img src="<?= H::url('/profile/' . $currentUser['id'] . '/signature-file') ?>" alt="Mi firma" style="max-height:90px;border:1px solid #dee2e6;border-radius:.375rem;padding:4px;background:#fff;">
              <div class="form-text">Se va a usar la firma guardada en su perfil.</div>
            </div>
            <?php endif; ?>
            <div data-sign-pane="dibujo" <?= $hasSavedSignature ? 'style="display:none;"' : '' ?>>
              <canvas id="signatureCanvas" width="280" height="140" style="border:1px solid #ced4da;border-radius:.375rem;touch-action:none;background:#fff;"></canvas>
              <div class="mt-1"><button type="button" class="btn btn-sm btn-outline-secondary" id="clearSignatureBtn">Limpiar</button></div>
            </div>
            <div data-sign-pane="foto" style="display:none;">
              <input type="file" name="signature_file" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
            </div>
            <div data-sign-pane="codigo" style="display:none;">
              <button type="button" class="btn btn-sm btn-outline-danger mb-2" id="genCodeBtn">Generar codigo de confirmacion</button>
              <div id="codeDisplay" class="fw-bold fs-5 text-center mb-2"></div>
              <input type="text" name="sign_code" class="form-control form-control-sm" placeholder="Escriba el codigo mostrado arriba">
              <div class="form-text">Confirmacion en pantalla (esta version no envia SMS/correo).</div>
            </div>

            <div class="mt-2">
              <label class="form-label small fw-semibold">PIN de Seguridad *</label>
              <input type="password" name="security_pin" class="form-control form-control-sm" inputmode="numeric" pattern="\d{4,6}" required autocomplete="off" <?= $hasPin ? '' : 'disabled' ?>>
            </div>

            <button type="submit" class="btn btn-sm btn-danger w-100 mt-2" <?= $hasPin ? '' : 'disabled' ?>><i class="bi bi-check2-circle"></i> Confirmar Firma</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($canApprove): ?>
      <?php $adminHasPin = !empty($currentUser['security_pin_hash']); ?>
      <form action="<?= H::url('/cases/' . $case['id'] . '/approve') ?>" method="post" data-confirm="¿Aprobar y cerrar el caso <?= H::e($case['case_number']) ?>?">
        <?= Csrf::field() ?>
        <?php if (!$adminHasPin): ?>
          <div class="alert alert-warning small py-2">Configure su PIN de seguridad en <a href="<?= H::url('/profile') ?>">Mi Perfil</a> antes de aprobar.</div>
        <?php endif; ?>
        <div class="mb-2">
          <label class="form-label small fw-semibold">PIN de Seguridad *</label>
          <input type="password" name="security_pin" class="form-control form-control-sm" inputmode="numeric" pattern="\d{4,6}" required autocomplete="off" <?= $adminHasPin ? '' : 'disabled' ?>>
        </div>
        <button class="btn btn-success w-100" type="submit" <?= $adminHasPin ? '' : 'disabled' ?>><i class="bi bi-check-circle"></i> Aprobar y Cerrar (Subcomandancia)</button>
      </form>
      <?php endif; ?>

      <?php if ($canReopen): ?>
      <?php $reopenHasPin = !empty($currentUser['security_pin_hash']); ?>
      <button type="button" class="btn btn-sm btn-outline-warning w-100 mt-2" data-bs-toggle="collapse" data-bs-target="#reopenPanel"><i class="bi bi-arrow-counterclockwise"></i> Reabrir Caso</button>
      <div class="collapse mt-2" id="reopenPanel">
        <form action="<?= H::url('/cases/' . $case['id'] . '/reopen') ?>" method="post" data-confirm="¿Reabrir el caso <?= H::e($case['case_number']) ?>? Se va a limpiar la firma y aprobacion actuales.">
          <?= Csrf::field() ?>
          <?php if (!$reopenHasPin): ?>
            <div class="alert alert-warning small py-2">Configure su PIN de seguridad en <a href="<?= H::url('/profile') ?>">Mi Perfil</a> antes de reabrir.</div>
          <?php endif; ?>
          <div class="mb-2">
            <label class="form-label small fw-semibold">PIN de Seguridad *</label>
            <input type="password" name="security_pin" class="form-control form-control-sm" inputmode="numeric" pattern="\d{4,6}" required autocomplete="off" <?= $reopenHasPin ? '' : 'disabled' ?>>
          </div>
          <button class="btn btn-warning w-100" type="submit" <?= $reopenHasPin ? '' : 'disabled' ?>><i class="bi bi-arrow-counterclockwise"></i> Confirmar Reapertura</button>
        </form>
      </div>
      <?php endif; ?>
    </div>

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

<?php $extraScripts = ($case['latitude'] && $case['longitude'] ? '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>' : '') . '
<script>
document.addEventListener("DOMContentLoaded", function () {
  var showMapEl = document.getElementById("showMap");
  if (showMapEl && window.L) {
    var lat = parseFloat(showMapEl.dataset.lat), lng = parseFloat(showMapEl.dataset.lng);
    var map = L.map("showMap", { scrollWheelZoom: false }).setView([lat, lng], 15);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { attribution: "&copy; OpenStreetMap", maxZoom: 19 }).addTo(map);
    L.marker([lat, lng]).addTo(map);
    setTimeout(function () { map.invalidateSize(); }, 300);
  }

  // ---- Firma: tabs de metodo ----
  var tabs = document.querySelectorAll("[data-sign-tab]");
  var methodInput = document.getElementById("signMethodInput");
  tabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
      tabs.forEach(function (t) { t.classList.remove("active"); });
      tab.classList.add("active");
      var method = tab.dataset.signTab;
      if (methodInput) methodInput.value = method;
      document.querySelectorAll("[data-sign-pane]").forEach(function (pane) {
        pane.style.display = (pane.dataset.signPane === method) ? "" : "none";
      });
    });
  });

  // ---- Firma: canvas de dibujo ----
  var canvas = document.getElementById("signatureCanvas");
  if (canvas) {
    var ctx = canvas.getContext("2d");
    ctx.lineWidth = 2;
    ctx.lineCap = "round";
    ctx.strokeStyle = "#111";
    var drawing = false;
    var dataInput = document.getElementById("signatureDataInput");

    function pos(e) {
      var rect = canvas.getBoundingClientRect();
      var point = e.touches ? e.touches[0] : e;
      return { x: point.clientX - rect.left, y: point.clientY - rect.top };
    }
    function start(e) { drawing = true; var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
    function move(e) { if (!drawing) return; var p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
    function end() { if (!drawing) return; drawing = false; if (dataInput) dataInput.value = canvas.toDataURL("image/png"); }

    canvas.addEventListener("mousedown", start);
    canvas.addEventListener("mousemove", move);
    window.addEventListener("mouseup", end);
    canvas.addEventListener("touchstart", start);
    canvas.addEventListener("touchmove", move);
    canvas.addEventListener("touchend", end);

    var clearBtn = document.getElementById("clearSignatureBtn");
    if (clearBtn) clearBtn.addEventListener("click", function () {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      if (dataInput) dataInput.value = "";
    });

    var signForm = document.getElementById("signForm");
    if (signForm) signForm.addEventListener("submit", function () {
      if (methodInput && methodInput.value === "dibujo" && dataInput) {
        dataInput.value = canvas.toDataURL("image/png");
      }
    });
  }

  // ---- Firma: codigo de confirmacion ----
  var genCodeBtn = document.getElementById("genCodeBtn");
  if (genCodeBtn) {
    genCodeBtn.addEventListener("click", function () {
      fetch("' . H::url('/cases/' . $case['id'] . '/sign-code') . '")
        .then(function (r) { return r.json(); })
        .then(function (data) {
          document.getElementById("codeDisplay").textContent = data.code;
        });
    });
  }
});
</script>';
?>
