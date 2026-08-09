<?php
use App\Helpers\Helpers as H;
use App\Models\Catalog;

$statusLabels = ['abierto' => 'Abierto', 'asignado' => 'Asignado', 'en_atencion' => 'En Atencion', 'pendiente_revision' => 'Pendiente de Revision', 'pendiente_aprobacion' => 'Pendiente de Aprobacion', 'cerrado' => 'Cerrado'];
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<form method="get" class="filter-bar row g-2 align-items-end">
  <div class="col-6 col-md-2">
    <label class="form-label small mb-1">Rango rapido</label>
    <select name="range" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">Personalizado</option>
      <option value="today">Hoy</option>
      <option value="7d">Ultimos 7 dias</option>
      <option value="30d">Ultimos 30 dias</option>
      <option value="month">Este mes</option>
      <option value="year">Este año</option>
    </select>
  </div>
  <div class="col-6 col-md-2">
    <label class="form-label small mb-1">Desde</label>
    <input type="date" name="date_from" value="<?= H::e($filters['date_from'] ?? '') ?>" class="form-control form-control-sm">
  </div>
  <div class="col-6 col-md-2">
    <label class="form-label small mb-1">Hasta</label>
    <input type="date" name="date_to" value="<?= H::e($filters['date_to'] ?? '') ?>" class="form-control form-control-sm">
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label small mb-1">Servicio</label>
    <select name="service_type" class="form-select form-select-sm">
      <option value="">Todos</option>
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
  <div class="col-12 col-md-1">
    <button class="btn btn-danger btn-sm w-100"><i class="bi bi-funnel"></i> Filtrar</button>
  </div>
</form>

<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#c0392b;"><i class="bi bi-folder2-open"></i></div>
      <div><div class="kpi-value"><?= (int)($kpis['total'] ?? 0) ?></div><div class="kpi-label">Total Casos</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#2c3e6b;"><i class="bi bi-calendar-check"></i></div>
      <div><div class="kpi-value"><?= (int)($kpis['este_mes'] ?? 0) ?></div><div class="kpi-label">Casos este Mes</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#d69e2e;"><i class="bi bi-exclamation-triangle"></i></div>
      <div><div class="kpi-value"><?= max(0, (int)($kpis['total'] ?? 0) - (int)($kpis['cerrados'] ?? 0)) ?></div><div class="kpi-label">Casos Abiertos</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#1a936f;"><i class="bi bi-check2-circle"></i></div>
      <div><div class="kpi-value"><?= (int)($kpis['cerrados'] ?? 0) ?></div><div class="kpi-label">Casos Cerrados</div></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#7c3aed;"><i class="bi bi-people"></i></div>
      <div><div class="kpi-value"><?= (int)($affected['personas_afectadas'] ?? 0) ?></div><div class="kpi-label">Personas Afectadas</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#0d9488;"><i class="bi bi-life-preserver"></i></div>
      <div><div class="kpi-value"><?= (int)($affected['personas_rescatadas'] ?? 0) ?></div><div class="kpi-label">Personas Rescatadas</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#b45309;"><i class="bi bi-paw"></i></div>
      <div><div class="kpi-value"><?= (int)($affected['animales_afectados'] ?? 0) ?></div><div class="kpi-label">Animales Afectados</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kpi-card">
      <div class="kpi-icon" style="background:#475569;"><i class="bi bi-building"></i></div>
      <div><div class="kpi-value"><?= (int)($affected['edificaciones_afectadas'] ?? 0) ?></div><div class="kpi-label">Edificaciones/Vehiculos</div></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="section-card h-100">
      <div class="section-title">Casos por mes</div>
      <canvas id="chartByMonth" height="90"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="section-card h-100">
      <div class="section-title">Casos por estado</div>
      <canvas id="chartByStatus" height="90"></canvas>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="section-card h-100">
      <div class="section-title">Casos por servicio</div>
      <canvas id="chartByService" height="110"></canvas>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="section-card h-100">
      <div class="section-title">Casos por comuna</div>
      <canvas id="chartByComuna" height="110"></canvas>
    </div>
  </div>
</div>

<?php
  $fmtMin = function ($m) {
      if ($m === null) return '-';
      $total = (int) round((float) $m);
      return $total >= 60 ? (intdiv($total, 60) . 'h ' . ($total % 60) . 'min') : ($total . ' min');
  };
?>
<div class="row g-3 mb-3">
  <div class="col-lg-4">
    <div class="kpi-card h-100">
      <div class="kpi-icon" style="background:#0891b2;"><i class="bi bi-stopwatch"></i></div>
      <div>
        <div class="kpi-value"><?= $fmtMin($responseTime['avg_minutes'] ?? null) ?></div>
        <div class="kpi-label">Tiempo de Respuesta Promedio (Salida &rarr; Llegada)</div>
        <?php if (!empty($responseTime['con_datos'])): ?>
          <div class="text-muted small">Min: <?= $fmtMin($responseTime['min_minutes']) ?> · Max: <?= $fmtMin($responseTime['max_minutes']) ?> · <?= (int)$responseTime['con_datos'] ?> caso(s) con datos</div>
        <?php else: ?>
          <div class="text-muted small">Todavia no hay casos con Hora de Salida y Hora de Llegada registradas.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="section-card h-100">
      <div class="section-title">Tiempo de Respuesta Promedio por Servicio</div>
      <?php if (empty($responseTimeByService)): ?>
        <div class="empty-state"><i class="bi bi-stopwatch"></i>Sin datos suficientes todavia.</div>
      <?php else: ?>
        <div class="table-responsive"><table class="table table-sm mb-0">
          <thead><tr><th>Servicio</th><th>Casos</th><th>Promedio</th></tr></thead>
          <tbody>
          <?php foreach ($responseTimeByService as $r): ?>
            <tr><td><?= H::e(Catalog::label('list_servicio', $r['label']) ?? $r['label']) ?></td><td><?= (int)$r['total'] ?></td><td><?= $fmtMin($r['avg_minutes']) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="section-card">
      <div class="section-title">Mapa de Calor de Emergencias (por ubicacion)</div>
      <?php if (empty($heatPoints)): ?>
        <div class="empty-state"><i class="bi bi-geo-alt"></i>Todavia no hay casos con coordenadas registradas. Los casos nuevos que marquen el punto en el mapa (pestaña Ubicacion) van a aparecer aca.</div>
      <?php else: ?>
        <div id="heatMap" style="height:380px;border-radius:.5rem;"></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="section-title mb-0">Casos recientes</div>
    <a href="<?= H::url('/cases') ?>" class="btn btn-sm btn-outline-secondary">Ver todos</a>
  </div>
  <?php if (empty($recent)): ?>
    <div class="empty-state"><i class="bi bi-inbox"></i>No hay casos registrados todavia.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Caso</th><th>Servicio</th><th>Fecha</th><th>Direccion</th><th>Estado</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($recent as $c): ?>
        <tr>
          <td class="fw-semibold"><?= H::e($c['case_number']) ?></td>
          <td><?= H::e(Catalog::label('list_servicio', $c['service_type']) ?? '-') ?></td>
          <td><?= H::formatDate($c['incident_date']) ?></td>
          <td><?= H::e($c['address'] ?? '-') ?></td>
          <td><span class="status-badge status-<?= H::e($c['status']) ?>"><?= H::e($statusLabels[$c['status']] ?? $c['status']) ?></span></td>
          <td><a href="<?= H::url('/cases/' . $c['id']) ?>" class="btn btn-sm btn-outline-danger">Ver</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php
$extraScripts = (!empty($heatPoints) ? '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>' : '') . '
<script>
const dashboardData = ' . json_encode([
    'byMonth' => $byMonth, 'byStatus' => $byStatus, 'byService' => $byService, 'byComuna' => $byComuna,
    'heatPoints' => array_map(fn($p) => [(float) $p['latitude'], (float) $p['longitude']], $heatPoints),
], JSON_UNESCAPED_UNICODE) . ';
document.addEventListener("DOMContentLoaded", function () {
  const palette = ["#c0392b","#2c3e6b","#d69e2e","#1a936f","#7c3aed","#0d9488","#b45309","#475569","#e74c3c","#3498db"];

  new Chart(document.getElementById("chartByMonth"), {
    type: "line",
    data: {
      labels: dashboardData.byMonth.map(r => r.ym),
      datasets: [{ label: "Casos", data: dashboardData.byMonth.map(r => r.total), borderColor: "#c0392b", backgroundColor: "rgba(192,57,43,.12)", fill: true, tension: .3 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });

  new Chart(document.getElementById("chartByStatus"), {
    type: "doughnut",
    data: {
      labels: dashboardData.byStatus.map(r => r.label),
      datasets: [{ data: dashboardData.byStatus.map(r => r.total), backgroundColor: palette }]
    }
  });

  new Chart(document.getElementById("chartByService"), {
    type: "bar",
    data: {
      labels: dashboardData.byService.map(r => r.label),
      datasets: [{ label: "Casos", data: dashboardData.byService.map(r => r.total), backgroundColor: "#c0392b" }]
    },
    options: { indexAxis: "y", plugins: { legend: { display: false } } }
  });

  new Chart(document.getElementById("chartByComuna"), {
    type: "bar",
    data: {
      labels: dashboardData.byComuna.map(r => r.label),
      datasets: [{ label: "Casos", data: dashboardData.byComuna.map(r => r.total), backgroundColor: "#2c3e6b" }]
    },
    options: { plugins: { legend: { display: false } } }
  });

  try {
    const heatEl = document.getElementById("heatMap");
    if (heatEl && window.L && window.L.heatLayer && dashboardData.heatPoints.length) {
      const map = L.map("heatMap").setView(dashboardData.heatPoints[0], 12);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { attribution: "&copy; OpenStreetMap", maxZoom: 19 }).addTo(map);
      const bounds = L.latLngBounds(dashboardData.heatPoints);
      map.fitBounds(bounds.pad(0.2));
      L.heatLayer(dashboardData.heatPoints, { radius: 28, blur: 20, maxZoom: 15 }).addTo(map);
      setTimeout(() => map.invalidateSize(), 300);
    }
  } catch (err) {
    console.error("Error inicializando el mapa de calor:", err);
  }
});
</script>';
?>
