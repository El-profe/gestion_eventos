<?php 
$titulo_pagina = "Registro de Participante — UAB Eventos";
require_once __DIR__ . '/../layouts/header.php'; 
?>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="row g-0">
      <div class="col-md-4 auth-side d-none d-md-flex">
        <div>
          <span class="uab-logo uab-logo-lg mb-3">UAB</span>
          <h2 class="h4 fw-bold">Comunidad Académica</h2>
          <p class="small text-white-50 mt-3">Regístrese para inscribirse a seminarios, congresos y capacitaciones especializadas.</p>
        </div>
        <a href="index.php?action=catalogo" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Explorar catálogo</a>
      </div>

      <div class="col-md-8 p-4 p-md-5">
        <h1 class="h3 fw-bold text-uab-azul">Crear cuenta de participante</h1>
        <p class="text-muted small">Complete sus datos para la correcta emisión de sus certificaciones.</p>

        <?php if (!empty($_SESSION['error'])): ?>
          <div class="alert alert-danger py-2 small mb-3"><?= htmlspecialchars($_SESSION['error']) ?></div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="index.php?action=do_registro" method="POST">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="ci" class="form-label small fw-semibold">Cédula de Identidad (CI) *</label>
              <input type="text" name="ci" id="ci" class="form-control" required placeholder="Ej: 100003">
            </div>
            <div class="col-md-6">
              <label for="telefono" class="form-label small fw-semibold">Teléfono / WhatsApp</label>
              <input type="tel" name="telefono" id="telefono" class="form-control" placeholder="+591 ...">
            </div>
            <div class="col-md-6">
              <label for="nombres" class="form-label small fw-semibold">Nombres *</label>
              <input type="text" name="nombres" id="nombres" class="form-control" required placeholder="Nombres">
            </div>
            <div class="col-md-6">
              <label for="apellidos" class="form-label small fw-semibold">Apellidos *</label>
              <input type="text" name="apellidos" id="apellidos" class="form-control" required placeholder="Apellidos">
            </div>
            <div class="col-12">
              <label for="correo" class="form-label small fw-semibold">Correo Institucional / Personal *</label>
              <input type="email" name="correo" id="correo" class="form-control" required placeholder="correo@ejemplo.edu">
            </div>
            <div class="col-md-6">
              <label for="password" class="form-label small fw-semibold">Contraseña *</label>
              <input type="password" name="password" id="password" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
            </div>
            <div class="col-md-6">
              <label for="password_confirm" class="form-label small fw-semibold">Confirmar Contraseña *</label>
              <input type="password" name="password_confirm" id="password_confirm" class="form-control" required minlength="6" placeholder="Repita contraseña">
            </div>
          </div>

          <button type="submit" class="btn btn-uab-azul w-100 py-2 mb-3">
            <i class="bi bi-person-check-fill me-1"></i>Confirmar Registro
          </button>
          
          <div class="text-center small">
            ¿Ya posee cuenta registrada? <a href="index.php?action=login">Iniciar sesión</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>