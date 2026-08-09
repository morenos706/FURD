<?php
use App\Helpers\Helpers as H;
use App\Helpers\Csrf;
use App\Helpers\FormRenderer;
use App\Models\Catalog;

$isEdit = !empty($case);
$action = $isEdit ? '/cases/' . $case['id'] : '/cases';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<?php if ($isEdit && in_array($case['status'], ['pendiente_aprobacion', 'cerrado'], true)): ?>
  <div class="alert alert-warning">
    <i class="bi bi-lock"></i> Este caso ya esta <strong><?= $case['status'] === 'cerrado' ? 'cerrado' : 'pendiente de aprobacion' ?></strong>.
    Para guardar cambios necesita confirmar con su <strong>PIN de seguridad</strong> (configurado en "Mi Perfil").
  </div>
<?php endif; ?>
<form method="post" action="<?= H::url($action) ?>" id="caseForm" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <?php if ($isEdit && in_array($case['status'], ['pendiente_aprobacion', 'cerrado'], true)): ?>
    <div class="mb-3" style="max-width:220px;">
      <label class="form-label small fw-semibold">PIN de Seguridad *</label>
      <input type="password" name="unlock_pin" class="form-control" inputmode="numeric" pattern="\d{4,6}" required autocomplete="off">
    </div>
  <?php endif; ?>

  <ul class="nav nav-pills mb-3 flex-wrap gap-1" id="caseTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-general" type="button">Informacion General</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-buildings" type="button">Edificaciones / Vehiculos</button></li>
    <?php foreach ($sections as $slug => $section): if ($slug === 'informacion_general') continue; ?>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-<?= H::e($slug) ?>" type="button"><?= H::e($section['label']) ?></button></li>
    <?php endforeach; ?>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-persons" type="button">Personas Afectadas</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-animals" type="button">Animales Afectados</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-personnel" type="button">Personal y Equipos</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-gestion" type="button">Gestion del Caso</button></li>
  </ul>

  <div class="tab-content">

    <!-- ============ INFORMACION GENERAL (core + campos del form original) ============ -->
    <div class="tab-pane fade show active" id="tab-general">
      <div class="section-card mb-3">
        <div class="form-section-title">Identificacion del Incidente</div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label small fw-semibold">Servicio *</label>
            <select name="service_type" id="serviceTypeSelect" class="form-select" required>
              <option value="">-- Seleccione --</option>
              <?php foreach (Catalog::items('list_servicio') as $s): ?>
                <option value="<?= H::e($s['item_value']) ?>" <?= ($case['service_type'] ?? '') === $s['item_value'] ? 'selected' : '' ?>><?= H::e($s['item_label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label small fw-semibold">Fecha del Incidente *</label>
            <input type="date" name="incident_date" class="form-control" required value="<?= H::e($case['incident_date'] ?? date('Y-m-d')) ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label small fw-semibold">Comandante del Incidente</label>
            <select name="incident_commander" class="form-select">
              <option value="">-- Seleccione --</option>
              <?php foreach ($comandantes as $c): ?>
                <option value="<?= H::e($c['item_value']) ?>" <?= ($case['incident_commander'] ?? '') === $c['item_value'] ? 'selected' : '' ?>><?= H::e($c['item_label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label small fw-semibold">Hora de Reporte</label>
            <input type="time" name="report_time" class="form-control" value="<?= H::e($case['report_time'] ?? '') ?>">
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label small fw-semibold">Hora de Salida</label>
            <input type="time" name="departure_time" class="form-control" value="<?= H::e($case['departure_time'] ?? '') ?>">
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label small fw-semibold">Hora de Llegada</label>
            <input type="time" name="arrival_time" class="form-control" value="<?= H::e($case['arrival_time'] ?? '') ?>">
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label small fw-semibold">Hora de Cierre</label>
            <input type="time" name="closure_time" class="form-control" value="<?= H::e($case['closure_time'] ?? '') ?>">
          </div>
          <div class="col-md-2 mb-3">
            <label class="form-label small fw-semibold">Hora en Estacion</label>
            <input type="time" name="station_time" class="form-control" value="<?= H::e($case['station_time'] ?? '') ?>">
          </div>
        </div>
      </div>

      <div class="section-card mb-3">
        <div class="form-section-title">Ubicacion</div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label small fw-semibold">Direccion del Incidente *</label>
            <input type="text" name="address" class="form-control" required value="<?= H::e($case['address'] ?? '') ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label small fw-semibold">Comuna</label>
            <select name="comuna" class="form-select">
              <option value="">-- Seleccione --</option>
              <?php foreach (Catalog::items('list_comuna') as $c): ?>
                <option value="<?= H::e($c['item_value']) ?>" <?= ($case['comuna'] ?? '') === $c['item_value'] ? 'selected' : '' ?>><?= H::e($c['item_label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label small fw-semibold">Barrio</label>
            <select name="barrio" class="form-select">
              <option value="">-- Seleccione --</option>
              <?php foreach (Catalog::items('list_barrio') as $b): ?>
                <option value="<?= H::e($b['item_value']) ?>" <?= ($case['barrio'] ?? '') === $b['item_value'] ? 'selected' : '' ?>><?= H::e($b['item_label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label small fw-semibold">Latitud</label>
            <input type="text" name="latitude" id="mapLatitude" class="form-control" value="<?= H::e($case['latitude'] ?? '') ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label small fw-semibold">Longitud</label>
            <input type="text" name="longitude" id="mapLongitude" class="form-control" value="<?= H::e($case['longitude'] ?? '') ?>">
          </div>
          <div class="col-12 mb-3">
            <label class="form-label small fw-semibold">Ubique el punto del incidente en el mapa (clic o arrastre el marcador)</label>
            <div id="incidentMap" style="height:320px; border-radius:.5rem;" data-lat="<?= H::e($case['latitude'] ?? '') ?>" data-lng="<?= H::e($case['longitude'] ?? '') ?>"></div>
          </div>
        </div>
      </div>

      <div class="section-card mb-3">
        <div class="form-section-title">Informacion del Caso</div>
        <div class="row">
          <div class="col-12 mb-3">
            <label class="form-label small fw-semibold">Descripcion</label>
            <textarea name="description" class="form-control" rows="3"><?= H::e($case['description'] ?? '') ?></textarea>
          </div>
        </div>
        <div class="row">
        <?php foreach ($sections['informacion_general']['fields'] as $f):
            if (in_array($f['name'], ['servicio','fecha_del_incidente','field_349','field_349_other','hora_de_reporte','hora_de_salida','hora_de_llegada','hora_de_cierre','hora_en_estaci_n','direccion_del_incidente','coordenadas'], true)) continue;
            echo FormRenderer::field($f, $formData[$f['name']] ?? null);
        endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ============ EDIFICACIONES / VEHICULOS (grupo repetible) ============ -->
    <div class="tab-pane fade" id="tab-buildings">
      <div class="section-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-section-title mb-0">Edificaciones o Vehiculos Afectados</div>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="addRepeatRow('buildingsContainer', document.getElementById('buildingTemplate').innerHTML)"><i class="bi bi-plus-lg"></i> Agregar</button>
        </div>
        <div id="buildingsContainer">
          <?php
          $buildingRows = $buildings ?: [[]];
          foreach ($buildingRows as $i => $b): ?>
          <div class="repeat-row border rounded p-3 mb-3 position-relative">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="removeRepeatRow(this)"></button>
            <div class="row">
              <div class="col-md-3 mb-2"><label class="form-label small">Tipo de Propiedad</label><input type="text" name="building[<?= $i ?>][property_type]" class="form-control form-control-sm" value="<?= H::e($b['property_type'] ?? '') ?>"></div>
              <div class="col-md-5 mb-2"><label class="form-label small">Direccion</label><input type="text" name="building[<?= $i ?>][address]" class="form-control form-control-sm" value="<?= H::e($b['address'] ?? '') ?>"></div>
              <div class="col-md-4 mb-2"><label class="form-label small">Uso de la Edificacion</label>
                <select name="building[<?= $i ?>][building_use]" class="form-select form-select-sm">
                  <option value="">-- Seleccione --</option>
                  <?php foreach (Catalog::items('list_uso_de_la_edificaci_n') as $o): ?>
                    <option value="<?= H::e($o['item_value']) ?>" <?= (($b['building_use'] ?? '') === $o['item_value']) ? 'selected' : '' ?>><?= H::e($o['item_label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2 mb-2"><label class="form-label small">Niveles Sup.</label><input type="number" name="building[<?= $i ?>][upper_levels]" class="form-control form-control-sm" value="<?= H::e($b['upper_levels'] ?? '') ?>"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Niveles Inf.</label><input type="number" name="building[<?= $i ?>][lower_levels]" class="form-control form-control-sm" value="<?= H::e($b['lower_levels'] ?? '') ?>"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Comuna</label><input type="text" name="building[<?= $i ?>][comuna]" class="form-control form-control-sm" value="<?= H::e($b['comuna'] ?? '') ?>"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Barrio</label><input type="text" name="building[<?= $i ?>][barrio]" class="form-control form-control-sm" value="<?= H::e($b['barrio'] ?? '') ?>"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Estrato</label><input type="text" name="building[<?= $i ?>][stratum]" class="form-control form-control-sm" value="<?= H::e($b['stratum'] ?? '') ?>"></div>
              <div class="col-md-4 mb-2"><label class="form-label small">Propietario</label><input type="text" name="building[<?= $i ?>][owner_name]" class="form-control form-control-sm" value="<?= H::e($b['owner_name'] ?? '') ?>"></div>
              <div class="col-md-4 mb-2"><label class="form-label small">Telefono Propietario</label><input type="text" name="building[<?= $i ?>][owner_phone]" class="form-control form-control-sm" value="<?= H::e($b['owner_phone'] ?? '') ?>"></div>
              <div class="col-md-4 mb-2"><label class="form-label small">Ocupante / Conductor</label><input type="text" name="building[<?= $i ?>][occupant_name]" class="form-control form-control-sm" value="<?= H::e($b['occupant_name'] ?? '') ?>"></div>
              <div class="col-md-4 mb-2"><label class="form-label small">Razon Social</label><input type="text" name="building[<?= $i ?>][company_name]" class="form-control form-control-sm" value="<?= H::e($b['company_name'] ?? '') ?>"></div>
              <div class="col-md-4 mb-2"><label class="form-label small">Tipo de Vehiculo</label><input type="text" name="building[<?= $i ?>][vehicle_type]" class="form-control form-control-sm" value="<?= H::e($b['vehicle_type'] ?? '') ?>"></div>
              <div class="col-md-4 mb-2"><label class="form-label small">Placa</label><input type="text" name="building[<?= $i ?>][vehicle_plate]" class="form-control form-control-sm" value="<?= H::e($b['vehicle_plate'] ?? '') ?>"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <template id="buildingTemplate"><div class="repeat-row border rounded p-3 mb-3 position-relative">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="removeRepeatRow(this)"></button>
            <div class="row">
              <div class="col-md-3 mb-2"><label class="form-label small">Tipo de Propiedad</label><input type="text" name="building[__INDEX__][property_type]" class="form-control form-control-sm"></div>
              <div class="col-md-5 mb-2"><label class="form-label small">Direccion</label><input type="text" name="building[__INDEX__][address]" class="form-control form-control-sm"></div>
              <div class="col-md-4 mb-2"><label class="form-label small">Uso de la Edificacion</label><input type="text" name="building[__INDEX__][building_use]" class="form-control form-control-sm"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Niveles Sup.</label><input type="number" name="building[__INDEX__][upper_levels]" class="form-control form-control-sm"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Niveles Inf.</label><input type="number" name="building[__INDEX__][lower_levels]" class="form-control form-control-sm"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Comuna</label><input type="text" name="building[__INDEX__][comuna]" class="form-control form-control-sm"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Barrio</label><input type="text" name="building[__INDEX__][barrio]" class="form-control form-control-sm"></div>
              <div class="col-md-4 mb-2"><label class="form-label small">Propietario</label><input type="text" name="building[__INDEX__][owner_name]" class="form-control form-control-sm"></div>
              <div class="col-md-4 mb-2"><label class="form-label small">Telefono</label><input type="text" name="building[__INDEX__][owner_phone]" class="form-control form-control-sm"></div>
              <div class="col-md-4 mb-2"><label class="form-label small">Placa</label><input type="text" name="building[__INDEX__][vehicle_plate]" class="form-control form-control-sm"></div>
            </div>
        </div></template>
      </div>
    </div>

    <!-- ============ SECCIONES DINAMICAS RESTANTES (forestal, acciones, sci) ============ -->
    <?php foreach ($sections as $slug => $section): if (in_array($slug, ['informacion_general', 'finalizacion'], true)) continue; ?>
    <div class="tab-pane fade" id="tab-<?= H::e($slug) ?>">
      <div class="section-card">
        <div class="form-section-title"><?= H::e($section['label']) ?></div>
        <div class="row">
          <?php foreach ($section['fields'] as $f): echo FormRenderer::field($f, $formData[$f['name']] ?? null); endforeach; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- ============ EVIDENCIAS Y RESPONSABLE (fotos + censo + identificacion) ============ -->
    <div class="tab-pane fade" id="tab-finalizacion">
      <div class="section-card mb-3">
        <div class="form-section-title">Evidencias Fotograficas</div>
        <p class="text-muted small">Puede seleccionar varias fotos a la vez (formatos JPG, PNG o WEBP).</p>
        <input type="file" name="evidencia_files[]" class="form-control mb-3" accept="image/jpeg,image/png,image/webp" multiple>
        <?php if (!empty($evidenceFiles)): ?>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($evidenceFiles as $ev): ?>
          <div class="position-relative">
            <a href="<?= H::url('/cases/' . $case['id'] . '/files/' . $ev['id']) ?>" target="_blank">
              <img src="<?= H::url('/cases/' . $case['id'] . '/files/' . $ev['id']) ?>" alt="Evidencia" style="width:110px;height:110px;object-fit:cover;border-radius:.5rem;border:1px solid #dee2e6;">
            </a>
            <form action="<?= H::url('/cases/' . $case['id'] . '/files/' . $ev['id'] . '/delete') ?>" method="post" class="position-absolute top-0 end-0 m-1" data-confirm="¿Eliminar esta foto?">
              <?= Csrf::field() ?>
              <button type="submit" class="btn btn-sm btn-danger py-0 px-1"><i class="bi bi-x"></i></button>
            </form>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="section-card mb-3">
        <div class="form-section-title">Anexe Censo</div>
        <input type="file" name="censo_file" class="form-control mb-2" accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls,.doc,.docx">
        <?php if (!empty($censoFiles)): ?>
          <?php foreach ($censoFiles as $cf): ?>
            <div class="small"><i class="bi bi-paperclip"></i> <a href="<?= H::url('/cases/' . $case['id'] . '/files/' . $cf['id']) ?>" target="_blank"><?= H::e($cf['original_name'] ?? $cf['file_path']) ?></a>
              <form action="<?= H::url('/cases/' . $case['id'] . '/files/' . $cf['id'] . '/delete') ?>" method="post" class="d-inline" data-confirm="¿Eliminar este archivo?">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn-sm btn-link text-danger p-0 ms-1">Eliminar</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="section-card">
        <div class="form-section-title">Responsable del Registro</div>
        <div class="row">
          <?php foreach ($sections['finalizacion']['fields'] as $f):
              if (in_array($f['name'], ['evidencia_1', 'evidencia_2', 'evidencia_3', 'anexe_censo', 'firma'], true)) continue;
              echo FormRenderer::field($f, $formData[$f['name']] ?? null);
          endforeach; ?>
        </div>
        <?php if ($isEdit): ?>
          <div class="alert alert-info small mb-0"><i class="bi bi-info-circle"></i> La firma digital del responsable se realiza desde la vista del caso (boton "Firmar") una vez guardado este registro, en el estado Asignado o En Atencion.</div>
        <?php else: ?>
          <div class="alert alert-secondary small mb-0"><i class="bi bi-info-circle"></i> Guarde el caso primero; la firma digital se habilita luego desde la vista del caso.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ============ PERSONAS AFECTADAS ============ -->
    <div class="tab-pane fade" id="tab-persons">
      <div class="section-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-section-title mb-0">Afectacion a la Vida o Integridad Fisica de las Personas</div>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="addRepeatRow('personsContainer', document.getElementById('personTemplate').innerHTML)"><i class="bi bi-plus-lg"></i> Agregar Persona</button>
        </div>
        <div id="personsContainer">
          <?php $personRows = $persons ?: [[]]; foreach ($personRows as $i => $p): ?>
          <div class="repeat-row border rounded p-3 mb-2 position-relative">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="removeRepeatRow(this)"></button>
            <div class="row">
              <div class="col-md-4 mb-2"><label class="form-label small">Nombres y Apellidos</label><input type="text" name="person[<?= $i ?>][full_name]" class="form-control form-control-sm" value="<?= H::e($p['full_name'] ?? '') ?>"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Edad</label><input type="number" name="person[<?= $i ?>][age]" class="form-control form-control-sm" value="<?= H::e($p['age'] ?? '') ?>"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Sexo</label>
                <select name="person[<?= $i ?>][sex]" class="form-select form-select-sm"><option value="">--</option>
                <?php foreach (Catalog::items('list_sexo') as $o): ?><option value="<?= H::e($o['item_value']) ?>" <?= (($p['sex'] ?? '') === $o['item_value']) ? 'selected' : '' ?>><?= H::e($o['item_label']) ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2 mb-2"><label class="form-label small">Rescatado</label>
                <select name="person[<?= $i ?>][rescued]" class="form-select form-select-sm"><option value="">--</option>
                <?php foreach (Catalog::items('list_rescatado') as $o): ?><option value="<?= H::e($o['item_value']) ?>" <?= (($p['rescued'] ?? '') === $o['item_value']) ? 'selected' : '' ?>><?= H::e($o['item_label']) ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2 mb-2"><label class="form-label small">Con Vida</label>
                <select name="person[<?= $i ?>][alive]" class="form-select form-select-sm"><option value="">--</option>
                <?php foreach (Catalog::items('list_con_vida') as $o): ?><option value="<?= H::e($o['item_value']) ?>" <?= (($p['alive'] ?? '') === $o['item_value']) ? 'selected' : '' ?>><?= H::e($o['item_label']) ?></option><?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <template id="personTemplate"><div class="repeat-row border rounded p-3 mb-2 position-relative">
          <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="removeRepeatRow(this)"></button>
          <div class="row">
            <div class="col-md-4 mb-2"><label class="form-label small">Nombres y Apellidos</label><input type="text" name="person[__INDEX__][full_name]" class="form-control form-control-sm"></div>
            <div class="col-md-2 mb-2"><label class="form-label small">Edad</label><input type="number" name="person[__INDEX__][age]" class="form-control form-control-sm"></div>
            <div class="col-md-2 mb-2"><label class="form-label small">Sexo</label><input type="text" name="person[__INDEX__][sex]" class="form-control form-control-sm"></div>
            <div class="col-md-2 mb-2"><label class="form-label small">Rescatado</label><input type="text" name="person[__INDEX__][rescued]" class="form-control form-control-sm"></div>
            <div class="col-md-2 mb-2"><label class="form-label small">Con Vida</label><input type="text" name="person[__INDEX__][alive]" class="form-control form-control-sm"></div>
          </div>
        </div></template>
      </div>
    </div>

    <!-- ============ ANIMALES AFECTADOS ============ -->
    <div class="tab-pane fade" id="tab-animals">
      <div class="section-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-section-title mb-0">Afectacion a la Vida de los Animales</div>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="addRepeatRow('animalsContainer', document.getElementById('animalTemplate').innerHTML)"><i class="bi bi-plus-lg"></i> Agregar Animal</button>
        </div>
        <div id="animalsContainer">
          <?php $animalRows = $animals ?: [[]]; foreach ($animalRows as $i => $a): ?>
          <div class="repeat-row border rounded p-3 mb-2 position-relative">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="removeRepeatRow(this)"></button>
            <div class="row">
              <div class="col-md-3 mb-2"><label class="form-label small">Tipo de Animal</label><input type="text" name="animal[<?= $i ?>][animal_type]" class="form-control form-control-sm" value="<?= H::e($a['animal_type'] ?? '') ?>"></div>
              <div class="col-md-3 mb-2"><label class="form-label small">Tamaño</label><input type="text" name="animal[<?= $i ?>][size]" class="form-control form-control-sm" value="<?= H::e($a['size'] ?? '') ?>"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Sexo</label><input type="text" name="animal[<?= $i ?>][sex]" class="form-control form-control-sm" value="<?= H::e($a['sex'] ?? '') ?>"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Rescatado</label><input type="text" name="animal[<?= $i ?>][rescued]" class="form-control form-control-sm" value="<?= H::e($a['rescued'] ?? '') ?>"></div>
              <div class="col-md-2 mb-2"><label class="form-label small">Con Vida</label><input type="text" name="animal[<?= $i ?>][alive]" class="form-control form-control-sm" value="<?= H::e($a['alive'] ?? '') ?>"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <template id="animalTemplate"><div class="repeat-row border rounded p-3 mb-2 position-relative">
          <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="removeRepeatRow(this)"></button>
          <div class="row">
            <div class="col-md-3 mb-2"><label class="form-label small">Tipo de Animal</label><input type="text" name="animal[__INDEX__][animal_type]" class="form-control form-control-sm"></div>
            <div class="col-md-3 mb-2"><label class="form-label small">Tamaño</label><input type="text" name="animal[__INDEX__][size]" class="form-control form-control-sm"></div>
            <div class="col-md-2 mb-2"><label class="form-label small">Sexo</label><input type="text" name="animal[__INDEX__][sex]" class="form-control form-control-sm"></div>
            <div class="col-md-2 mb-2"><label class="form-label small">Rescatado</label><input type="text" name="animal[__INDEX__][rescued]" class="form-control form-control-sm"></div>
            <div class="col-md-2 mb-2"><label class="form-label small">Con Vida</label><input type="text" name="animal[__INDEX__][alive]" class="form-control form-control-sm"></div>
          </div>
        </div></template>
      </div>
    </div>

    <!-- ============ PERSONAL Y EQUIPOS (bomberos) ============ -->
    <div class="tab-pane fade" id="tab-personnel">
      <div class="section-card">
        <div class="form-section-title">Jefe de Turno y Vehiculos</div>
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Jefe de Turno</label>
            <select name="shift_chief" class="form-select">
              <option value="">-- Seleccione --</option>
              <?php foreach ($jefesTurno as $j): ?>
                <option value="<?= H::e($j['item_value']) ?>" <?= ($case['shift_chief'] ?? '') === $j['item_value'] ? 'selected' : '' ?>><?= H::e($j['item_label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label small fw-semibold">Vehiculos</label>
            <?php $selectedVeh = (array) ($formData['vehiculos'] ?? []); ?>
            <div class="border rounded p-2" id="vehiculosChecklist" style="max-height:120px; overflow-y:auto;">
              <?php foreach ($vehiculos as $v): ?>
                <div class="form-check form-check-inline">
                  <input class="form-check-input vehiculo-check" type="checkbox" name="fd[vehiculos][]" value="<?= H::e($v['item_value']) ?>" data-label="<?= H::e($v['item_label']) ?>" <?= in_array($v['item_value'], $selectedVeh, true) ? 'checked' : '' ?>>
                  <label class="form-check-label small"><?= H::e($v['item_label']) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="form-text">Marque los vehiculos con los que se desplazo la tripulacion; luego indique en que vehiculo iba cada bombero.</div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="form-section-title mb-0">Bomberos que Actuaron en la Emergencia</div>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="addRepeatRow('firefightersContainer', document.getElementById('firefighterTemplate').innerHTML); refreshFirefighterVehicleOptions();"><i class="bi bi-plus-lg"></i> Agregar</button>
        </div>
        <div id="firefightersContainer">
          <?php $ffRows = $firefighters ?: [[]]; foreach ($ffRows as $f): ?>
          <div class="repeat-row d-flex gap-2 mb-2">
            <input type="text" name="firefighter_name[]" class="form-control form-control-sm" placeholder="Nombre del Bombero" value="<?= H::e($f['firefighter_name'] ?? '') ?>">
            <input type="text" name="firefighter_role[]" class="form-control form-control-sm" placeholder="Rol / Cargo" value="<?= H::e($f['role'] ?? '') ?>">
            <select name="firefighter_vehicle[]" class="form-select form-select-sm firefighter-vehicle-select" data-selected="<?= H::e($f['vehicle_value'] ?? '') ?>">
              <option value="">-- Vehiculo --</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="removeRepeatRow(this)"><i class="bi bi-x"></i></button>
          </div>
          <?php endforeach; ?>
        </div>
        <template id="firefighterTemplate"><div class="repeat-row d-flex gap-2 mb-2">
          <input type="text" name="firefighter_name[]" class="form-control form-control-sm" placeholder="Nombre del Bombero">
          <input type="text" name="firefighter_role[]" class="form-control form-control-sm" placeholder="Rol / Cargo">
          <select name="firefighter_vehicle[]" class="form-select form-select-sm firefighter-vehicle-select">
            <option value="">-- Vehiculo --</option>
          </select>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="removeRepeatRow(this)"><i class="bi bi-x"></i></button>
        </div></template>
      </div>
    </div>

    <!-- ============ GESTION DEL CASO (campos propios del sistema) ============ -->
    <div class="tab-pane fade" id="tab-gestion">
      <div class="section-card">
        <div class="form-section-title">Estado y Seguimiento</div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label small fw-semibold">Estado</label>
            <select name="status" class="form-select">
              <?php foreach ($statuses as $s): ?>
                <option value="<?= H::e($s['code']) ?>" <?= ($case['status'] ?? 'abierto') === $s['code'] ? 'selected' : '' ?>><?= H::e($s['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label small fw-semibold">Prioridad</label>
            <select name="priority" class="form-select">
              <?php foreach ($priorities as $code => $label): ?>
                <option value="<?= $code ?>" <?= ($case['priority'] ?? 'media') === $code ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label small fw-semibold">Responsable Asignado</label>
            <select name="responsible_user_id" class="form-select">
              <option value="">-- Sin asignar --</option>
              <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ((string)($case['responsible_user_id'] ?? '')) === (string)$u['id'] ? 'selected' : '' ?>><?= H::e($u['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="d-flex justify-content-end gap-2 mt-3 mb-5">
    <a href="<?= H::url($isEdit ? '/cases/' . $case['id'] : '/cases') ?>" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-danger px-4"><i class="bi bi-save"></i> Guardar Registro</button>
  </div>
</form>

<?php $extraScripts = '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Cada funcion corre en su propio listener y con try/catch: si el mapa
// falla (por red, CDN, etc.) no debe romper la cascada de Clase de
// Servicio ni el vinculo bombero-vehiculo.

document.addEventListener("DOMContentLoaded", function () {
  try {
    var mapEl = document.getElementById("incidentMap");
    if (mapEl && window.L) {
      var latInput = document.getElementById("mapLatitude");
      var lngInput = document.getElementById("mapLongitude");
      var startLat = parseFloat(mapEl.dataset.lat) || 4.5709;
      var startLng = parseFloat(mapEl.dataset.lng) || -74.2973;
      var startZoom = (mapEl.dataset.lat && mapEl.dataset.lng) ? 15 : 6;

      var map = L.map("incidentMap").setView([startLat, startLng], startZoom);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap",
        maxZoom: 19
      }).addTo(map);

      var marker = null;
      function placeMarker(lat, lng) {
        if (marker) { marker.setLatLng([lat, lng]); }
        else {
          marker = L.marker([lat, lng], { draggable: true }).addTo(map);
          marker.on("dragend", function () {
            var pos = marker.getLatLng();
            latInput.value = pos.lat.toFixed(6);
            lngInput.value = pos.lng.toFixed(6);
          });
        }
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
      }

      if (mapEl.dataset.lat && mapEl.dataset.lng) {
        placeMarker(startLat, startLng);
      }

      map.on("click", function (e) {
        placeMarker(e.latlng.lat, e.latlng.lng);
      });

      setTimeout(function () { map.invalidateSize(); }, 300);
    }
  } catch (err) {
    console.error("Error inicializando el mapa:", err);
  }
});

document.addEventListener("DOMContentLoaded", function () {
  try {
    var serviceSelect = document.getElementById("serviceTypeSelect");
    function toggleClaseServicio() {
      if (!serviceSelect) return;
      var value = serviceSelect.value || "";
      var match = value.match(/^(\\d+)\\./);
      var num = match ? match[1] : null;
      document.querySelectorAll(".clase-servicio-field").forEach(function (field) {
        field.style.display = (num && field.dataset.claseServicio === num) ? "" : "none";
      });
    }
    if (serviceSelect) {
      serviceSelect.addEventListener("change", toggleClaseServicio);
      toggleClaseServicio();
    }
  } catch (err) {
    console.error("Error en cascada de Clase de Servicio:", err);
  }
});

document.addEventListener("DOMContentLoaded", function () {
  try {
    window.refreshFirefighterVehicleOptions = function () {
      var vehicles = [];
      document.querySelectorAll(".vehiculo-check:checked").forEach(function (cb) {
        vehicles.push({ value: cb.value, label: cb.dataset.label || cb.value });
      });
      document.querySelectorAll(".firefighter-vehicle-select").forEach(function (sel) {
        var current = sel.dataset.selected !== undefined && sel.dataset.selected !== "" ? sel.dataset.selected : sel.value;
        sel.innerHTML = "<option value=\\"\\">-- Vehiculo --</option>";
        vehicles.forEach(function (v) {
          var opt = document.createElement("option");
          opt.value = v.value;
          opt.textContent = v.label;
          if (v.value === current) opt.selected = true;
          sel.appendChild(opt);
        });
        sel.removeAttribute("data-selected");
      });
    };
    document.querySelectorAll(".vehiculo-check").forEach(function (cb) {
      cb.addEventListener("change", window.refreshFirefighterVehicleOptions);
    });
    window.refreshFirefighterVehicleOptions();
  } catch (err) {
    console.error("Error en vinculo bombero-vehiculo:", err);
  }
});
</script>';
?>
