<?php 
$titulo_pagina = "Mi Perfil — UAB Eventos";
$seccion_activa = "perfil";
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php'; 

require_once __DIR__ . '/../../models/Usuario.php';
$usuarioModel = new Usuario();
$u = $usuarioModel->obtenerPorId((int)$usuarioSesion['id_usuario']);

$inicial = strtoupper(substr($u['nombres'] ?? 'U', 0, 1));
$nombreCompleto = htmlspecialchars(($u['nombres'] ?? '') . ' ' . ($u['apellidos'] ?? ''));
$rolNombre = htmlspecialchars($u['rol_nombre'] ?? 'PARTICIPANTE');
?>

<main class="container-fluid px-3 px-md-4 py-3">
  <div class="row g-3">
    
    <!-- Menú Lateral -->
    <aside class="col-12 col-lg-3 col-xl-2">
      <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    </aside>

    <!-- Contenido Principal -->
    <section class="col-12 col-lg-9 col-xl-10">

      <!-- Banner Hero del Perfil -->
      <div class="rounded-4 p-4 p-md-5 mb-4 text-white shadow-sm position-relative overflow-hidden" 
           style="background: linear-gradient(135deg, #0d3b66 0%, #1a428a 50%, #08233d 100%) !important;">
        <div class="row align-items-center g-4 position-relative z-1">
          
          <div class="col-auto">
            <div class="d-flex align-items-center justify-content-center rounded-circle border border-3 border-white border-opacity-25 shadow" 
                 style="width: 80px; height: 80px; background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(8px); font-size: 2rem; font-weight: 700; color: #ffffff;">
              <?= $inicial ?>
            </div>
          </div>

          <div class="col">
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <span class="badge bg-white text-uab-azul px-3 py-1 rounded-pill fw-bold small">
                <i class="bi bi-shield-check me-1"></i> Cuenta Verificada
              </span>
              <span class="badge bg-light bg-opacity-25 text-white border border-white border-opacity-25 px-3 py-1 rounded-pill small">
                <?= $rolNombre ?>
              </span>
            </div>
            <h1 class="h3 fw-bold mb-1 text-white"><?= $nombreCompleto ?></h1>
            <div class="small text-white-50 d-flex align-items-center gap-3 flex-wrap">
              <span><i class="bi bi-person-vcard me-1"></i> CI: <strong><?= htmlspecialchars($u['ci']) ?></strong></span>
              <span><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($u['correo']) ?></span>
              <span><i class="bi bi-calendar-check me-1"></i> Miembro desde: <?= date('d/m/Y', strtotime($u['fecha_registro'] ?? 'now')) ?></span>
            </div>
          </div>

        </div>
      </div>

      <!-- Notificaciones Flash -->
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2 small d-flex align-items-center mb-3 rounded-3 shadow-sm border-0">
          <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
          <div><?= htmlspecialchars($_SESSION['success']) ?></div>
        </div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2 small d-flex align-items-center mb-3 rounded-3 shadow-sm border-0">
          <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
          <div><?= htmlspecialchars($_SESSION['error']) ?></div>
        </div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <!-- Paneles de Configuración -->
      <div class="row g-4">
        
        <!-- Tarjeta 1: Información Personal -->
        <div class="col-12 col-xl-7">
          <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <div class="p-2 rounded-3 bg-light text-uab-azul">
                  <i class="bi bi-person-gear fs-5"></i>
                </div>
                <div>
                  <h2 class="h6 fw-bold mb-0 text-dark">Información Personal</h2>
                  <small class="text-muted">Actualiza tus datos de contacto y acreditación</small>
                </div>
              </div>
            </div>

            <form action="index.php?action=actualizar_perfil" method="POST">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-muted">Cédula de Identidad (CI)</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-card-text"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" value="<?= htmlspecialchars($u['ci']) ?>" readonly disabled>
                  </div>
                  <div class="form-text" style="font-size: 0.72rem;">Número de identidad no editable para certificados.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold text-muted">Correo Institucional / Personal</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control bg-light border-start-0" value="<?= htmlspecialchars($u['correo']) ?>" readonly disabled>
                  </div>
                  <div class="form-text" style="font-size: 0.72rem;">Canal oficial para envío de acreditaciones.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Nombres <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" name="nombres" class="form-control border-start-0" value="<?= htmlspecialchars($u['nombres']) ?>" required>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Apellidos <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" name="apellidos" class="form-control border-start-0" value="<?= htmlspecialchars($u['apellidos']) ?>" required>
                  </div>
                </div>

                <div class="col-12">
                  <label class="form-label small fw-semibold">Teléfono / Celular (WhatsApp)</label>
                  <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-telephone"></i></span>
                    <input type="tel" name="telefono" class="form-control border-start-0" placeholder="+591 ..." value="<?= htmlspecialchars($u['telefono'] ?? '') ?>">
                  </div>
                </div>
              </div>

              <div class="mt-4 pt-2 text-end">
                <button type="submit" class="btn btn-uab-azul rounded-pill px-4 py-2 fw-semibold shadow-sm">
                  <i class="bi bi-floppy me-1"></i> Guardar Cambios
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Tarjeta 2: Seguridad y Contraseña -->
        <div class="col-12 col-xl-5">
          <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <div class="p-2 rounded-3 bg-light text-danger">
                  <i class="bi bi-shield-lock fs-5"></i>
                </div>
                <div>
                  <h2 class="h6 fw-bold mb-0 text-dark">Seguridad de la Cuenta</h2>
                  <small class="text-muted">Actualización periódica de credencial</small>
                </div>
              </div>
            </div>

            <form action="index.php?action=actualizar_password" method="POST" id="formPassword">
              <div class="mb-3">
                <label class="form-label small fw-semibold">Contraseña Actual <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-key"></i></span>
                  <input type="password" name="password_actual" id="passActual" class="form-control border-start-0 border-end-0" required placeholder="••••••••">
                  <button class="btn btn-outline-secondary border-start-0 text-muted" type="button" onclick="togglePasswordVisibility('passActual', this)">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label small fw-semibold">Nueva Contraseña <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                  <input type="password" name="password_nueva" id="passNueva" class="form-control border-start-0 border-end-0" minlength="6" required placeholder="Mínimo 6 caracteres">
                  <button class="btn btn-outline-secondary border-start-0 text-muted" type="button" onclick="togglePasswordVisibility('passNueva', this)">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
                <div class="form-text" style="font-size: 0.72rem;">Usa letras, números y símbolos para mayor protección.</div>
              </div>

              <div class="p-3 rounded-3 bg-light border mb-4">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <i class="bi bi-info-circle-fill text-uab-azul"></i>
                  <span class="fw-bold small text-dark">Sesión y Auditoría</span>
                </div>
                <p class="small text-muted mb-0" style="font-size: 0.75rem;">
                  Cualquier modificación de contraseña queda registrada en la bitácora criptográfica del sistema para tu resguardo.
                </p>
              </div>

              <div class="text-end">
                <button type="submit" class="btn btn-outline-danger rounded-pill px-4 py-2 fw-semibold">
                  <i class="bi bi-arrow-repeat me-1"></i> Modificar Contraseña
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>

    </section>
  </div>
</main>

<script>
function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('bi-eye', 'bi-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('bi-eye-slash', 'bi-eye');
  }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>