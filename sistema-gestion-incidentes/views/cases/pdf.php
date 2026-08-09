<?php
use App\Helpers\Helpers as H;
use App\Models\Catalog;

$statusLabels = ['abierto' => 'Abierto', 'asignado' => 'Asignado', 'en_atencion' => 'En Atencion', 'pendiente_revision' => 'Pendiente de Revision', 'pendiente_aprobacion' => 'Pendiente de Aprobacion', 'cerrado' => 'Cerrado'];
$fd = $case['form_data_decoded'] ?? [];
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
  .case-title { font-size: 15px; font-weight: bold; margin-top: 6px; }
  h2.section { background: #f4f6fb; border-left: 4px solid #c0392b; padding: 4px 8px; font-size: 12px; margin: 14px 0 6px; }
  table.grid { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
  table.grid td { padding: 3px 6px; vertical-align: top; border-bottom: 1px solid #eee; }
  table.grid td.label { width: 30%; font-weight: bold; color: #444; }
  table.list { width:100%; border-collapse: collapse; }
  table.list th, table.list td { border: 1px solid #ddd; padding: 4px 6px; font-size: 10px; text-align:left; }
  table.list th { background: #f4f6fb; }
  .footer { margin-top: 20px; font-size: 9px; color: #888; border-top: 1px solid #ddd; padding-top: 6px; }
  .status-tag { display:inline-block; padding:2px 8px; border-radius: 8px; background:#eef1f8; font-weight:bold; }
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
    <div class="case-title">Reporte de Caso #<?= H::e($case['case_number']) ?> &nbsp; <span class="status-tag"><?= H::e($statusLabels[$case['status']] ?? $case['status']) ?></span></div>
  </div>

  <h2 class="section">Informacion General</h2>
  <table class="grid">
    <tr><td class="label">Servicio</td><td><?= H::e(Catalog::label('list_servicio', $case['service_type']) ?? '-') ?></td>
        <td class="label">Fecha del Incidente</td><td><?= H::formatDate($case['incident_date']) ?></td></tr>
    <tr><td class="label">Comandante</td><td><?= H::e(Catalog::label('list_field_349', $case['incident_commander']) ?? '-') ?></td>
        <td class="label">Prioridad</td><td><?= H::e(ucfirst($case['priority'])) ?></td></tr>
    <tr><td class="label">Hora Reporte</td><td><?= H::e($case['report_time'] ?? '-') ?></td>
        <td class="label">Hora Salida</td><td><?= H::e($case['departure_time'] ?? '-') ?></td></tr>
    <tr><td class="label">Hora Llegada</td><td><?= H::e($case['arrival_time'] ?? '-') ?></td>
        <td class="label">Hora Cierre</td><td><?= H::e($case['closure_time'] ?? '-') ?></td></tr>
    <tr><td class="label">Direccion</td><td colspan="3"><?= H::e($case['address'] ?? '-') ?></td></tr>
    <tr><td class="label">Comuna / Barrio</td><td><?= H::e($case['comuna'] ?? '-') ?> / <?= H::e($case['barrio'] ?? '-') ?></td>
        <td class="label">Responsable</td><td><?= H::e($case['responsible_name'] ?? '-') ?></td></tr>
    <tr><td class="label">Descripcion</td><td colspan="3"><?= nl2br(H::e($case['description'] ?? '-')) ?></td></tr>
  </table>

  <?php if ($buildings): ?>
  <h2 class="section">Edificaciones / Vehiculos Afectados</h2>
  <table class="list">
    <tr><th>Tipo</th><th>Direccion</th><th>Uso</th><th>Propietario</th><th>Placa</th></tr>
    <?php foreach ($buildings as $b): ?>
      <tr><td><?= H::e($b['property_type'] ?: '-') ?></td><td><?= H::e($b['address'] ?: '-') ?></td><td><?= H::e($b['building_use'] ?: '-') ?></td><td><?= H::e($b['owner_name'] ?: '-') ?></td><td><?= H::e($b['vehicle_plate'] ?: '-') ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <?php if ($persons): ?>
  <h2 class="section">Personas Afectadas</h2>
  <table class="list">
    <tr><th>Nombre</th><th>Edad</th><th>Sexo</th><th>Rescatado</th><th>Con Vida</th></tr>
    <?php foreach ($persons as $p): ?>
      <tr><td><?= H::e($p['full_name'] ?: '-') ?></td><td><?= H::e($p['age'] ?: '-') ?></td><td><?= H::e($p['sex'] ?: '-') ?></td><td><?= H::e($p['rescued'] ?: '-') ?></td><td><?= H::e($p['alive'] ?: '-') ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <?php if ($animals): ?>
  <h2 class="section">Animales Afectados</h2>
  <table class="list">
    <tr><th>Tipo</th><th>Tamaño</th><th>Sexo</th><th>Rescatado</th><th>Con Vida</th></tr>
    <?php foreach ($animals as $a): ?>
      <tr><td><?= H::e($a['animal_type'] ?: '-') ?></td><td><?= H::e($a['size'] ?: '-') ?></td><td><?= H::e($a['sex'] ?: '-') ?></td><td><?= H::e($a['rescued'] ?: '-') ?></td><td><?= H::e($a['alive'] ?: '-') ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <?php if (!empty($sciObjectives)): ?>
  <h2 class="section">Objetivos, Estrategias y Tacticas (SCI)</h2>
  <table class="list">
    <tr><th>Objetivo</th><th>Estrategia y Tactica</th></tr>
    <?php foreach ($sciObjectives as $s): ?>
      <tr><td><?= nl2br(H::e($s['objective'] ?: '-')) ?></td><td><?= nl2br(H::e($s['strategy_tactic'] ?: '-')) ?></td></tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>

  <?php foreach ($sections as $slug => $section):
      $rows = array_filter($section['fields'], fn($f) => !empty($fd[$f['name']]) && !is_array($fd[$f['name']]));
      if (!$rows) continue; ?>
    <h2 class="section"><?= H::e($section['label']) ?></h2>
    <table class="grid">
      <?php foreach ($rows as $f): ?>
        <tr><td class="label"><?= H::e($f['label']) ?></td><td><?= H::e($f['list'] ? (Catalog::label($f['list'], $fd[$f['name']]) ?? '-') : (string) $fd[$f['name']]) ?></td></tr>
      <?php endforeach; ?>
    </table>
  <?php endforeach; ?>

  <?php if ($evidencePhotos): ?>
  <h2 class="section">Evidencias Fotograficas</h2>
  <table class="grid"><tr>
    <?php foreach ($evidencePhotos as $i => $photo): ?>
      <td style="width:25%;"><img src="<?= $photo['uri'] ?>" style="width:100%;max-height:120px;object-fit:cover;border:1px solid #ddd;"></td>
      <?php if (($i + 1) % 4 === 0): ?></tr><tr><?php endif; ?>
    <?php endforeach; ?>
  </tr></table>
  <?php endif; ?>

  <h2 class="section">Responsable del Registro y Firma</h2>
  <table class="grid">
    <tr><td class="label">Nombre</td><td><?= H::e($case['signed_name'] ?? Catalog::label('list_nombre_completo', $fd['nombre_completo'] ?? '') ?? '-') ?></td>
        <td class="label">Cedula</td><td><?= H::e($fd['cedula_de_ciudadania'] ?? '-') ?></td></tr>
    <tr><td class="label">Firmado</td><td><?= $case['signed_at'] ? H::formatDateTime($case['signed_at']) . ' (' . H::e($case['sign_method']) . ')' : 'Sin firmar' ?></td>
        <td class="label">Aprobado</td><td><?= $case['approved_at'] ? H::formatDateTime($case['approved_at']) . ' por ' . H::e($case['approved_name'] ?? '') : 'Pendiente' ?></td></tr>
  </table>
  <?php if ($signaturePhoto): ?>
    <img src="<?= $signaturePhoto ?>" style="max-height:100px;border:1px solid #ddd;padding:4px;">
  <?php endif; ?>

  <h2 class="section">Historial de Modificaciones</h2>
  <table class="list">
    <tr><th>Fecha</th><th>Accion</th><th>Usuario</th><th>Detalle</th></tr>
    <?php foreach ($history as $h): ?>
      <tr><td><?= H::formatDateTime($h['created_at']) ?></td><td><?= H::e($h['action']) ?></td><td><?= H::e($h['user_name'] ?? 'Sistema') ?></td><td><?= H::e($h['summary'] ?? '-') ?></td></tr>
    <?php endforeach; ?>
  </table>

  <div class="footer">
    Documento generado automaticamente por <?= H::e($systemName) ?> · <?= H::e($entityName) ?> · <?= H::e($generatedAt) ?>
  </div>
</body>
</html>
