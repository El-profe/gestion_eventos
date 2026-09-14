<?php 
$titulo_pagina = "Iniciar Sesión — UAB Eventos";
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="row g-0">
      <div class="col-md-5 auth-side d-none d-md-flex">
        <div>
          <span class="uab-logo uab-logo-lg mb-4">UAB</span>
          <h2 class="h4 fw-bold">Plataforma de Eventos y Certificaciones</h2>
          <p class="small text-white-50 mt-3">
            Universidad Autónoma del Beni "José Ballivián"<br>
            Cursos, congresos, talleres y capacitaciones con emisión de certificados oficiales verificables mediante QR.
          </p>
        </div>
        <ul class="list-unstyled small mb-0 text-white-50">
          <li class="mb-2"><i class="bi bi-check-circle-fill text-uab-verde me-2"></i>Inscripción con control de cupos en tiempo real</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill text-uab-verde me-2"></i>Control de asistencia sesión por sesión</li>
          <li><i class="bi bi-check-circle-fill text-uab-verde me-2"></i>Descarga e impresión de certificados con folio</li>
        </ul>
      </div>

      <div class="col-md-7 p-4 p-md-5">
        <h1 class="h3 fw-bold text-uab-azul">Iniciar sesión</h1>
        <p class="text-muted small">Ingrese con sus credenciales institucionales registradas.</p>

        <!-- Mensajes Flash de Sesión -->
        <?php if (!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger py-2 small d-flex align-items-center mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?= htmlspecialchars($_SESSION['error']) ?></div>
          </div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
          <div class="alert alert-success py-2 small d-flex align-items-center mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div><?= htmlspecialchars($_SESSION['success']) ?></div>
          </div>
          <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form action="index.php?action=do_login" method="POST">
          <div class="mb-3">
            <label for="correo" class="form-label small fw-semibold">Correo Electrónico</label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
              <input type="email" name="correo" id="correo" class="form-control" required placeholder="ejemplo@eventos.edu">
            </div>
          </div>

          <div class="mb-4">
            <label for="password" class="form-label small fw-semibold">Contraseña</label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
              <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
            </div>
          </div>

          <button type="submit" class="btn btn-uab-azul w-100 py-2 mb-3">
            <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar a la plataforma
          </button>
        </form>

        <div class="d-flex justify-content-between small">
          <a href="index.php?action=registro" class="text-decoration-none">Crear cuenta nueva</a>
          <a href="index.php?action=recuperar" class="text-muted text-decoration-none">¿Olvidó su contraseña?</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>