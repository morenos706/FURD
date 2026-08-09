<?php use App\Helpers\Helpers as H; use App\Helpers\Csrf; ?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="section-card mb-3">
      <div class="form-section-title">Datos Institucionales y Apariencia</div>
      <form method="post" action="<?= H::url('/settings') ?>">
        <?= Csrf::field() ?>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label small fw-semibold">Nombre del Sistema</label>
            <input type="text" name="system_name" class="form-control" value="<?= H::e($settings['system_name'] ?? '') ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label small fw-semibold">Nombre de la Entidad</label>
            <input type="text" name="entity_name" class="form-control" value="<?= H::e($settings['entity_name'] ?? '') ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label small fw-semibold">NIT / Identificacion</label>
            <input type="text" name="entity_nit" class="form-control" value="<?= H::e($settings['entity_nit'] ?? '') ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label small fw-semibold">Color Primario</label>
            <input type="color" name="primary_color" class="form-control form-control-color" value="<?= H::e($settings['primary_color'] ?? '#c0392b') ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label small fw-semibold">Zona Horaria</label>
            <input type="text" name="timezone" class="form-control" value="<?= H::e($settings['timezone'] ?? 'America/Bogota') ?>">
          </div>
        </div>
        <button type="submit" class="btn btn-danger">Guardar Cambios</button>
      </form>
    </div>

    <div class="section-card">
      <div class="form-section-title">Categorias, Estados y Listas</div>
      <p class="text-muted small">Administre las 95 listas desplegables del formulario original (servicios, barrios, comunas, tipos de vehiculo, etc.) sin necesidad de modificar el codigo.</p>
      <a href="<?= H::url('/settings/catalogs') ?>" class="btn btn-outline-danger"><i class="bi bi-list-check"></i> Administrar Categorias</a>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="section-card">
      <div class="form-section-title">Copias de Seguridad</div>
      <p class="text-muted small">Genere y descargue una copia de seguridad completa de la base de datos.</p>
      <a href="<?= H::url('/settings/backup') ?>" class="btn btn-outline-secondary"><i class="bi bi-cloud-download"></i> Descargar Backup (.sql)</a>
      <div class="form-text mt-2">Requiere que <code>mysqldump</code> este disponible en el servidor. En hosting compartido, puede generar el respaldo desde phpMyAdmin.</div>
    </div>
  </div>
</div>
