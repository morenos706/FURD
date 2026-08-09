<?php
use App\Helpers\Helpers as H;
use App\Helpers\Csrf;
?>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="section-card mb-3">
      <div class="form-section-title">Mi Firma Digital Guardada</div>
      <p class="text-muted small">Subi una imagen de tu firma una sola vez. Despues, al firmar un caso vas a poder usar el metodo "Mi Firma" para aplicarla directo, sin dibujarla ni subirla cada vez.</p>

      <?php if (!empty($user['signature_path'])): ?>
        <div class="mb-3">
          <img src="<?= H::url('/profile/' . $user['id'] . '/signature-file') ?>" alt="Firma guardada" style="max-height:100px;border:1px solid #dee2e6;border-radius:.5rem;padding:6px;background:#fff;">
        </div>
      <?php else: ?>
        <div class="alert alert-warning small">Todavia no tenes una firma guardada.</div>
      <?php endif; ?>

      <form action="<?= H::url('/profile/signature') ?>" method="post" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <div class="input-group">
          <input type="file" name="signature_file" class="form-control" accept="image/jpeg,image/png,image/webp" required>
          <button class="btn btn-danger" type="submit"><i class="bi bi-upload"></i> Guardar Firma</button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="section-card">
      <div class="form-section-title">PIN de Seguridad</div>
      <p class="text-muted small">
        <?php if (!empty($user['security_pin_hash'])): ?>
          Ya tenes un PIN configurado. Se te va a pedir como segunda clave al firmar, aprobar, o editar un caso ya firmado/cerrado.
        <?php else: ?>
          Todavia no configuraste tu PIN. Es un codigo de 4 a 6 digitos, distinto de tu clave de inicio de sesion, que se usa como confirmacion extra para acciones sensibles (firmar, aprobar casos, editar un caso ya firmado o cerrado).
        <?php endif; ?>
      </p>

      <form action="<?= H::url('/profile/pin') ?>" method="post">
        <?= Csrf::field() ?>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Su clave actual de inicio de sesion</label>
          <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Nuevo PIN (4 a 6 digitos)</label>
          <input type="password" name="pin" class="form-control" inputmode="numeric" pattern="\d{4,6}" required autocomplete="off">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Confirme el PIN</label>
          <input type="password" name="pin_confirm" class="form-control" inputmode="numeric" pattern="\d{4,6}" required autocomplete="off">
        </div>
        <button class="btn btn-danger w-100" type="submit"><i class="bi bi-shield-lock"></i> Guardar PIN</button>
      </form>
    </div>
  </div>
</div>
