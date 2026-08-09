<?php use App\Helpers\Helpers as H; use App\Models\Catalog; ?>
<div class="section-card mb-3">
  <div class="form-section-title">Resumen Inteligente</div>
  <?php if ($insights['total_all'] === 0): ?>
    <div class="empty-state"><i class="bi bi-graph-up"></i>Aun no hay suficiente informacion registrada para generar analitica. Registre casos para ver tendencias aqui.</div>
  <?php else: ?>
  <ul class="mb-0">
    <li class="mb-2">
      Durante <?= H::e(date('F')) ?> se han registrado <strong><?= $insights['this_month'] ?></strong> casos,
      <?php if ($insights['month_change_pct'] > 0): ?>
        un incremento del <strong><?= $insights['month_change_pct'] ?>%</strong> respecto al mes anterior (<?= $insights['last_month'] ?> casos).
      <?php elseif ($insights['month_change_pct'] < 0): ?>
        una disminucion del <strong><?= abs($insights['month_change_pct']) ?>%</strong> respecto al mes anterior (<?= $insights['last_month'] ?> casos).
      <?php else: ?>
        el mismo numero de casos que el mes anterior.
      <?php endif; ?>
    </li>
    <li class="mb-2">
      En lo corrido del año se han registrado <strong><?= $insights['this_year'] ?></strong> casos
      <?php if ($insights['last_year'] > 0): ?>
        (<?= $insights['year_change_pct'] >= 0 ? '+' : '' ?><?= $insights['year_change_pct'] ?>% frente al año anterior, con <?= $insights['last_year'] ?> casos).
      <?php else: ?>
        (no hay datos del año anterior para comparar).
      <?php endif; ?>
    </li>
    <?php if ($insights['top_service']): ?>
    <li class="mb-2">
      La categoria predominante es <strong><?= H::e(Catalog::label('list_servicio', $insights['top_service']['label']) ?? $insights['top_service']['label']) ?></strong>,
      con <?= $insights['top_service']['total'] ?> casos registrados
      (<?= $insights['service_percentages'][0]['pct'] ?? 0 ?>% del total).
    </li>
    <?php endif; ?>
    <?php if ($insights['peak_month']): ?>
    <li class="mb-2">
      El periodo con mayor cantidad de casos fue <strong><?= H::e($insights['peak_month']['ym']) ?></strong>, con <?= $insights['peak_month']['total'] ?> registros.
    </li>
    <?php endif; ?>
    <?php if ($insights['top_comuna']): ?>
    <li class="mb-2">
      La comuna con mas incidentes registrados es <strong><?= H::e($insights['top_comuna']['label']) ?></strong> (<?= $insights['top_comuna']['total'] ?> casos).
    </li>
    <?php endif; ?>
    <li>
      Promedio mensual de casos (ultimos 24 meses con datos): <strong><?= $insights['avg_per_month'] ?></strong>.
    </li>
  </ul>
  <p class="text-muted small mt-3 mb-0"><i class="bi bi-info-circle"></i> Todas las metricas se calculan en tiempo real desde la base de datos, sin valores estimados.</p>
  <?php endif; ?>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="section-card h-100">
      <div class="section-title">Comparacion Anual</div>
      <canvas id="chartYear" height="120"></canvas>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="section-card h-100">
      <div class="section-title">Distribucion por Prioridad</div>
      <canvas id="chartPriority" height="120"></canvas>
    </div>
  </div>
</div>

<div class="section-card">
  <div class="section-title">Participacion por Categoria de Servicio</div>
  <?php foreach ($insights['service_percentages'] as $row): ?>
    <div class="mb-2">
      <div class="d-flex justify-content-between small mb-1">
        <span><?= H::e(Catalog::label('list_servicio', $row['label']) ?? $row['label']) ?></span>
        <span class="fw-semibold"><?= $row['pct'] ?>% (<?= $row['total'] ?>)</span>
      </div>
      <div class="progress" style="height:8px;"><div class="progress-bar bg-danger" style="width: <?= $row['pct'] ?>%"></div></div>
    </div>
  <?php endforeach; ?>
</div>

<?php
$extraScripts = '<script>
document.addEventListener("DOMContentLoaded", function () {
  new Chart(document.getElementById("chartYear"), {
    type: "bar",
    data: { labels: ' . json_encode(array_column($byYear, 'label')) . ', datasets: [{ label: "Casos", data: ' . json_encode(array_column($byYear, 'total')) . ', backgroundColor: "#c0392b" }] },
    options: { plugins: { legend: { display: false } } }
  });
  new Chart(document.getElementById("chartPriority"), {
    type: "pie",
    data: { labels: ' . json_encode(array_column($byPriority, 'label')) . ', datasets: [{ data: ' . json_encode(array_column($byPriority, 'total')) . ', backgroundColor: ["#4b7","#d69e2e","#dd6b20","#c0392b"] }] }
  });
});
</script>';
?>
