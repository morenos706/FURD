<?php
use App\Helpers\Helpers as H;
use App\Helpers\Csrf;
use App\Helpers\FormRenderer;
use App\Models\Catalog;

$isEdit = !empty($case);
$action = $isEdit ? '/cases/' . $case['id'] : '/cases';
?>
<form method="post" action="<?= H::url($action) ?>" id="caseForm">
  <?= Csrf::field() ?>

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
            <select name="service_type" class="form-select" required>
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
            <input type="text" name="latitude" class="form-control" value="<?= H::e($case['latitude'] ?? '') ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label small fw-semibold">Longitud</label>
            <input type="text" name="longitude" class="form-control" value="<?= H::e($case['longitude'] ?? '') ?>">
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

    <!-- ============ SECCIONES DINAMICAS RESTANTES (forestal, acciones, sci, finalizacion) ============ -->
    <?php foreach ($sections as $slug => $section): if ($slug === 'informacion_general') continue; ?>
    <div class="tab-pane fade" id="tab-<?= H::e($slug) ?>">
      <div class="section-card">
        <div class="form-section-title"><?= H::e($section['label']) ?></div>
        <div class="row">
          <?php foreach ($section['fields'] as $f): echo FormRenderer::field($f, $formData[$f['name']] ?? null); endforeach; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

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
            <div class="border rounded p-2" style="max-height:120px; overflow-y:auto;">
              <?php foreach ($vehiculos as $v): ?>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="checkbox" name="fd[vehiculos][]" value="<?= H::e($v['item_value']) ?>" <?= in_array($v['item_value'], $selectedVeh, true) ? 'checked' : '' ?>>
                  <label class="form-check-label small"><?= H::e($v['item_label']) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="form-section-title mb-0">Bomberos que Actuaron en la Emergencia</div>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="addRepeatRow('firefightersContainer', document.getElementById('firefighterTemplate').innerHTML)"><i class="bi bi-plus-lg"></i> Agregar</button>
        </div>
        <div id="firefightersContainer">
          <?php $ffRows = $firefighters ?: [[]]; foreach ($ffRows as $f): ?>
          <div class="repeat-row d-flex gap-2 mb-2">
            <input type="text" name="firefighter_name[]" class="form-control form-control-sm" placeholder="Nombre del Bombero" value="<?= H::e($f['firefighter_name'] ?? '') ?>">
            <input type="text" name="firefighter_role[]" class="form-control form-control-sm" placeholder="Rol / Cargo" value="<?= H::e($f['role'] ?? '') ?>">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="removeRepeatRow(this)"><i class="bi bi-x"></i></button>
          </div>
          <?php endforeach; ?>
        </div>
        <template id="firefighterTemplate"><div class="repeat-row d-flex gap-2 mb-2">
          <input type="text" name="firefighter_name[]" class="form-control form-control-sm" placeholder="Nombre del Bombero">
          <input type="text" name="firefighter_role[]" class="form-control form-control-sm" placeholder="Rol / Cargo">
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
