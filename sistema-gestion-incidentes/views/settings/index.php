<?php use App\Helpers\Helpers as H; use App\Helpers\Csrf; ?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="section-card mb-3">
      <div class="form-section-title">Datos Institucionales y Apariencia</div>
      <form method="post" action="<?= H::url('/settings') ?>" enctype="multipart/form-data">
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
          <div class="col-md-6 mb-3">
            <label class="form-label small fw-semibold">Logo (aparece en el menu, el login y el PDF)</label>
            <?php if (!empty($settings['logo_path'])): ?>
              <div class="mb-2"><img src="<?= H::url($settings['logo_path']) ?>" alt="Logo" style="max-height:60px;background:#fff;border:1px solid #dee2e6;border-radius:.375rem;padding:4px;"></div>
            <?php endif; ?>
            <input type="file" name="logo_file" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label small fw-semibold">Fondo de Pantalla del Login</label>
            <?php if (!empty($settings['login_bg_path'])): ?>
              <div class="mb-2"><img src="<?= H::url($settings['login_bg_path']) ?>" alt="Fondo login" style="max-height:60px;border:1px solid #dee2e6;border-radius:.375rem;"></div>
            <?php endif; ?>
            <input type="file" name="login_bg_file" class="form-control" accept="image/png,image/jpeg,image/webp">
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
    <div class="section-card mb-3">
      <div class="form-section-title">Copias de Seguridad</div>
      <p class="text-muted small">Genere y descargue una copia de seguridad completa de la base de datos.</p>
      <a href="<?= H::url('/settings/backup') ?>" class="btn btn-outline-secondary"><i class="bi bi-cloud-download"></i> Descargar Backup (.sql)</a>
      <div class="form-text mt-2">Requiere que <code>mysqldump</code> este disponible en el servidor. En hosting compartido, puede generar el respaldo desde phpMyAdmin.</div>
    </div>

    <div class="section-card" style="border-left:4px solid #c0392b;">
      <div class="form-section-title"><i class="bi bi-file-earmark-excel me-1"></i>Importar Datos Historicos</div>
      <p class="text-muted small mb-2">Suba el Excel exportado de Survey123/ArcGIS (hoja "survey_0") para cargar casos historicos. Se importan como casos <strong>cerrados</strong>.</p>
      <form method="post" action="<?= H::url('/settings/import-historical') ?>" enctype="multipart/form-data" data-confirm="¿Importar el archivo? Esto puede tardar varios minutos, no cierre la pestaña.">
        <?= Csrf::field() ?>
        <div class="mb-2">
          <input type="file" name="import_file" class="form-control form-control-sm" accept=".xlsx" required>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" name="wipe_before_import" id="wipeBeforeImport" value="1">
          <label class="form-check-label small text-danger" for="wipeBeforeImport">
            Borrar todos los casos actuales antes de importar (irreversible)
          </label>
        </div>
        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-upload"></i> Importar Excel</button>
      </form>
    </div>
  </div>
</div>
