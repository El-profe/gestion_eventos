<?php
// views/layouts/sidebar.php
require_once __DIR__ . '/../../helpers/AuthHelper.php';
$usuarioActual = AuthHelper::obtenerUsuario();
$rol = $usuarioActual['rol_nombre'] ?? 'PARTICIPANTE';
$seccionActiva = $seccion_activa ?? '';
?>

<!-- Botón de apertura en celular y tablet (< 992px) -->
<div class="d-lg-none mb-3">
  <button class="btn btn-dark btn-sm w-100 d-flex align-items-center justify-content-center gap-2 py-2 rounded-3 shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
    <i class="bi bi-list fs-5"></i>
    <span class="fw-semibold">Menú de Navegación</span>
  </button>
</div>

<!-- Barra lateral compacta que se ajusta a su contenido -->
<div class="offcanvas-lg offcanvas-start sidebar-responsive bg-white rounded-3 shadow-sm p-3" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
  
  <div class="offcanvas-header border-bottom d-lg-none py-2 px-0 mb-3">
    <div class="d-flex align-items-center gap-2">
      <span class="uab-logo uab-logo-dark">UAB</span>
      <span class="fw-bold text-dark">Navegación</span>
    </div>
    <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Cerrar"></button>
  </div>

  <!-- Perfil resumido -->
  <div class="d-flex align-items-center gap-2 pb-2 mb-2 border-bottom">
    <div class="user-avatar-sm">
      <?= strtoupper(substr($usuarioActual['nombres'] ?? 'U', 0, 1)) ?>
    </div>
    <div class="lh-sm text-truncate">
      <div class="fw-bold text-dark small text-truncate" title="<?= htmlspecialchars(($usuarioActual['nombres'] ?? '') . ' ' . ($usuarioActual['apellidos'] ?? '')) ?>">
        <?= htmlspecialchars($usuarioActual['nombres'] ?? 'Usuario') ?>
      </div>
      <span class="badge bg-light text-muted border text-uppercase" style="font-size: 0.62rem;">
        <?= htmlspecialchars($rol) ?>
      </span>
    </div>
  </div>

  <!-- Enlaces de navegación -->
  <nav class="nav flex-column gap-1">
    <?php if ($rol === 'ADMINISTRADOR'): ?>
      <a class="nav-link nav-link-compact <?= ($seccionActiva === 'dashboard') ? 'active' : '' ?>" href="index.php?action=admin_dashboard">
        <i class="bi bi-speedometer2 me-2"></i> Resumen General
      </a>
      <a class="nav-link nav-link-compact <?= ($seccionActiva === 'eventos') ? 'active' : '' ?>" href="index.php?action=admin_eventos">
        <i class="bi bi-calendar-event me-2"></i> Gestión de Eventos
      </a>
      <a class="nav-link nav-link-compact <?= ($seccionActiva === 'usuarios') ? 'active' : '' ?>" href="index.php?action=admin_usuarios">
        <i class="bi bi-people me-2"></i> Gestión de Usuarios
      </a>
      <a class="nav-link nav-link-compact <?= ($seccionActiva === 'reportes') ? 'active' : '' ?>" href="index.php?action=admin_reportes">
        <i class="bi bi-bar-chart me-2"></i> Reportes
      </a>
      <hr class="my-1 text-muted">
    <?php elseif ($rol === 'EXPOSITOR'): ?>
      <a class="nav-link nav-link-compact <?= ($seccionActiva === 'mis_eventos') ? 'active' : '' ?>" href="index.php?action=expositor_eventos">
        <i class="bi bi-clipboard-check me-2"></i> Mis Clases
      </a>
      <hr class="my-1 text-muted">
    <?php endif; ?>

    <a class="nav-link nav-link-compact <?= ($seccionActiva === 'dashboard_participante') ? 'active' : '' ?>" href="index.php?action=participante_dashboard">
      <i class="bi bi-compass me-2"></i> Explorar Eventos
    </a>
    <a class="nav-link nav-link-compact <?= ($seccionActiva === 'mis_inscripciones') ? 'active' : '' ?>" href="index.php?action=mis_inscripciones">
      <i class="bi bi-journal-bookmark me-2"></i> Mis Inscripciones
    </a>
    <a class="nav-link nav-link-compact <?= ($seccionActiva === 'mis_certificados') ? 'active' : '' ?>" href="index.php?action=mis_certificados">
      <i class="bi bi-award me-2"></i> Mis Certificados
    </a>
    <a class="nav-link nav-link-compact <?= ($seccionActiva === 'perfil') ? 'active' : '' ?>" href="index.php?action=perfil">
      <i class="bi bi-person me-2"></i> Mi Perfil
    </a>
    <hr class="my-1 text-muted">
    <a class="nav-link nav-link-compact text-danger" href="index.php?action=logout">
      <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
    </a>
  </nav>
</div>