<?php
use App\Helpers\Helpers as H;
use App\Models\Catalog;

$fmtMin = function ($m) {
    if ($m === null) return '-';
    $total = (int) round((float) $m);
    return $total >= 60 ? (intdiv($total, 60) . 'h ' . ($total % 60) . 'min') : ($total . ' min');
};
$statusLabels = ['abierto' => 'Abierto', 'asignado' => 'Asignado', 'en_atencion' => 'En Atencion', 'pendiente_revision' => 'Pendiente de Revision', 'pendiente_aprobacion' => 'Pendiente de Aprobacion', 'cerrado' => 'Cerrado'];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1c2333; }
  .header { border-bottom: 3px solid #c0392b; padding-bottom: 10px; margin-bottom: 14px; }
  .header table { width: 100%; }
  .entity-name { font-size: 16px; font-weight: bold; color: #c0392b; }
  .system-name { font-size: 10px; color: #666; }
  .report-title { font-size: 15px; font-weight: bold; margin-top: 6px; }
  h2.section { background: #f4f6fb; border-left: 4px solid #c0392b; padding: 4px 8px; font-size: 12px; margin: 14px 0 6px; }
  .kpi-row { width: 100%; margin-bottom: 6px; }
  .kpi-box { display: inline-block; width: 23%; margin-right: 1%; padding: 8px; background: #f4f6fb; border-radius: 4px; text-align: center; }
  .kpi-box .val { font-size: 18px; font-weight: bold; color: #c0392b; }
  .kpi-box .lbl { font-size: 9px; color: #555; }
  table.list { width:100%; border-collapse: collapse; }
  table.list th, table.list td { border: 1px solid #ddd; padding: 4px 6px; font-size: 10px; text-align:left; }
  table.list th { background: #f4f6fb; }
  .footer { margin-top: 20px; font-size: 9px; color: #888; border-top: 1px solid #ddd; padding-top: 6px; }
</style>
</head>
<body>
  <div class="header">
    <table>
      <tr>
        <?php if (!empty($logoDataUri)): ?>
        <td style="width:60px;"><img src="<?= $logoDataUri ?>" style="max-height:50px;max-width:55px;"></td>
        <?php endif; ?>
        <td>
          <div class="entity-name"><?= H::e($entityName) ?></div>
          <div class="system-name"><?= H::e($systemName) ?></div>
        </td>
        <td style="text-align:right;">
          <div>Generado: <?= H::e($generatedAt) ?></div>
          <div>Por: <?= H::e($generatedBy) ?></div>
        </td>
      </tr>
    </table>
    <div class="report-title">Informe Ejecutivo &nbsp;·&nbsp; <?= H::formatDate($dateFrom) ?> a <?= H::formatDate($dateTo) ?></div>
  </div>

  <h2 class="section">Resumen General</h2>
  <div class="kpi-row">
    <div class="kpi-box"><div class="val"><?= (int)($kpis['total'] ?? 0) ?></div><div class="lbl">TOTAL CASOS</div></div>
    <div class="kpi-box"><div class="val"><?= max(0, (int)($kpis['total'] ?? 0) - (int)($kpis['cerrados'] ?? 0)) ?></div><div class="lbl">ABIERTOS</div></div>
    <div class="kpi-box"><div class="val"><?= (int)($kpis['cerrados'] ?? 0) ?></div><div class="lbl">CERRADOS</div></div>
    <div class="kpi-box"><div class="val"><?= $fmtMin($responseTime['avg_minutes'] ?? null) ?></div><div class="lbl">TIEMPO RESP. PROMEDIO</div></div>
  </div>
  <div class="kpi-row">
    <div class="kpi-box"><div class="val"><?= (int)($affected['personas_afectadas'] ?? 0) ?></div><div class="lbl">PERSONAS AFECTADAS</div></div>
    <div class="kpi-box"><div class="val"><?= (int)($affected['personas_rescatadas'] ?? 0) ?></div><div class="lbl">PERSONAS RESCATADAS</div></div>
    <div class="kpi-box"><div class="val"><?= (int)($affected['animales_afectados'] ?? 0) ?></div><div class="lbl">ANIMALES AFECTADOS</div></div>
    <div class="kpi-box"><div class="val"><?= (int)($affected['edificaciones_afectadas'] ?? 0) ?></div><div class="lbl">EDIF./VEHIC. AFECTADOS</div></div>
  </div>

  <h2 class="section">Tiempo de Respuesta (Salida &rarr; Llegada)</h2>
  <?php if (empty($responseTime['con_datos'])): ?>
    <p>No hay casos con Hora de Salida y Hora de Llegada registradas en este periodo.</p>
  <?php else: ?>
    <p>Promedio: <strong><?= $fmtMin($responseTime['avg_minutes']) ?></strong> &nbsp;·&nbsp;
       Minimo: <?= $fmtMin($responseTime['min_minutes']) ?> &nbsp;·&nbsp;
       Maximo: <?= $fmtMin($responseTime['max_minutes']) ?> &nbsp;·&nbsp;
       <?= (int)$responseTime['con_datos'] ?> caso(s) con datos</p>
    <?php if ($responseTimeByService): ?>
    <table class="list">
      <tr><th>Servicio</th><th>Casos</th><th>Promedio</th></tr>
      <?php foreach ($responseTimeByService as $r): ?>
        <tr><td><?= H::e(Catalog::label('list_servicio', $r['label']) ?? $r['label']) ?></td><td><?= (int)$r['total'] ?></td><td><?= $fmtMin($r['avg_minutes']) ?></td></tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  <?php endif; ?>

  <h2 class="section">Casos por Estado</h2>
  <table class="list">
    <tr><th>Estado</th><th>Casos</th></tr>
    <?php foreach ($byStatus as $r): ?>
      <tr><td><?= H::e($statusLabels[$r['label']] ?? $r['label']) ?></td><td><?= (int)$r['total'] ?></td></tr>
    <?php endforeach; ?>
  </table>

  <h2 class="section">Casos por Servicio</h2>
  <table class="list">
    <tr><th>Servicio</th><th>Casos</th></tr>
    <?php foreach ($byService as $r): ?>
      <tr><td><?= H::e(Catalog::label('list_servicio', $r['label']) ?? $r['label']) ?></td><td><?= (int)$r['total'] ?></td></tr>
    <?php endforeach; ?>
  </table>

  <?php if ($byComuna): ?>
  <h2 class="section">Casos por Comuna</h2>
  <table class="list">
    <tr><th>Comuna</th><th>Casos</th></tr>
    <?php foreach ($byComuna as $r): ?>
      <tr><td><?= H::e(Catalog::label('list_comuna', $r['label']) ?? $r['label']) ?></td><td><?= (int)$r['total'] ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <?php if ($byResponsible): ?>
  <h2 class="section">Casos por Responsable</h2>
  <table class="list">
    <tr><th>Responsable</th><th>Casos</th></tr>
    <?php foreach ($byResponsible as $r): ?>
      <tr><td><?= H::e($r['label']) ?></td><td><?= (int)$r['total'] ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <div class="footer">
    Informe Ejecutivo generado automaticamente por <?= H::e($systemName) ?> · <?= H::e($entityName) ?> · <?= H::e($generatedAt) ?>
  </div>
</body>
</html>
