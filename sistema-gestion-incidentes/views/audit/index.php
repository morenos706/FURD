<?php use App\Helpers\Helpers as H; ?>
<form method="get" class="filter-bar row g-2 align-items-end">
  <div class="col-6 col-md-3">
    <label class="form-label small mb-1">Usuario</label>
    <input type="text" name="user" value="<?= H::e($filters['user'] ?? '') ?>" class="form-control form-control-sm">
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label small mb-1">Accion</label>
    <select name="action" class="form-select form-select-sm">
      <option value="">Todas</option>
      <?php foreach ($actions as $a): ?><option value="<?= H::e($a) ?>" <?= ($filters['action'] ?? '') === $a ? 'selected' : '' ?>><?= H::e($a) ?></option><?php endforeach; ?>
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
  <div class="col-12 col-md-2"><button class="btn btn-danger btn-sm w-100"><i class="bi bi-funnel"></i> Filtrar</button></div>
</form>

<div class="section-card">
  <div class="section-title"><?= (int)$total ?> registro(s) de auditoria</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
      <thead><tr><th>Fecha</th><th>Usuario</th><th>Accion</th><th>Entidad</th><th>Referencia</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $log): ?>
        <tr>
          <td class="small text-nowrap"><?= H::formatDateTime($log['created_at']) ?></td>
          <td><?= H::e($log['username'] ?? 'Sistema') ?></td>
          <td><span class="badge-role"><?= H::e($log['action']) ?></span></td>
          <td class="small"><?= H::e($log['entity_type'] ?? '-') ?></td>
          <td class="small"><?= H::e($log['entity_id'] ?? '-') ?></td>
          <td class="small text-muted"><?= H::e($log['ip_address'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($logs)): ?>
        <tr><td colspan="6"><div class="empty-state"><i class="bi bi-shield-check"></i>No hay eventos registrados con estos filtros.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php $pages = (int) ceil($total / $perPage); if ($pages > 1): ?>
  <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
      <li class="page-item <?= $p === $page ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul></nav>
  <?php endif; ?>
</div>
