<?php
// views/layouts/navbar.php
require_once __DIR__ . '/../../helpers/AuthHelper.php';
AuthHelper::initSession();
?>
<nav class="navbar navbar-expand-lg navbar-dark border-bottom border-secondary border-opacity-25 shadow-sm sticky-top py-2" style="background-color: #0b0f19 !important;">
  <div class="container-fluid px-3 px-md-4 px-xl-5">
    <!-- Logotipo institucional -->
    <a class="navbar-brand d-flex align-items-center gap-2 m-0 text-white" href="index.php?action=catalogo">
      <span class="uab-logo uab-logo-dark">UAB</span>
      <div class="lh-1">
        <span class="fw-bold text-white d-block" style="font-size:1.05rem;">EVENTOS UAB</span>
        <small class="text-white-50" style="font-size:0.72rem;">Extensión Universitaria</small>
      </div>
    </a>

    <!-- Botón hamburguesa para móvil y tablet -->
    <button class="navbar-toggler border-0 shadow-none text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Enlaces posicionados al extremo derecho -->
    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1 gap-lg-2 mt-2 mt-lg-0">
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold px-2 py-1 rounded" href="index.php?action=catalogo">
            <i class="bi bi-calendar-event me-1 text-info"></i> Catálogo
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white fw-semibold px-2 py-1 rounded" href="index.php?action=verificar_certificado">
            <i class="bi bi-patch-check me-1 text-info"></i> Verificar Certificado
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>