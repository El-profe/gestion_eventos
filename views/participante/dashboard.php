<?php 
$titulo_pagina = "Portal Académico — Convocatorias Abiertas";
$seccion_activa = "dashboard_participante";
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php'; 
?>

<!-- Contenedor amplio sin márgenes vacíos excesivos -->
<main class="container-fluid px-3 px-md-4 py-3">
  <div class="row g-3 align-items-start">
    
    <!-- Menú Lateral Compacto -->
    <aside class="col-12 col-lg-3 col-xl-2">
      <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    </aside>

    <!-- Área de Contenido Principal -->
    <section class="col-12 col-lg-9 col-xl-10">
      
      <!-- Banner Compacto Institucional -->
      <div class="dashboard-hero rounded-3 p-4 mb-4 text-white shadow-sm position-relative overflow-hidden">
        <div class="row align-items-center">
          <div class="col-md-8">
            <span class="badge bg-white text-uab-azul px-2 py-1 mb-2 fw-semibold" style="font-size: 0.75rem;">
              <i class="bi bi-mortarboard-fill me-1"></i> Formación Continua & Extensión
            </span>
            <h1 class="h4 fw-bold mb-1">Convocatorias Académicas Abiertas</h1>
            <p class="small text-white-50 mb-0">
              Inscríbete en los cursos, talleres y conferencias oficiales con certificación de la Universidad.
            </p>
          </div>
          <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="index.php?action=mis_inscripciones" class="btn btn-outline-light btn-sm rounded-pill px-3 py-2 fw-semibold">
              <i class="bi bi-journal-check me-1"></i> Mis Inscripciones (<?= $totalMisInscripciones ?>)
            </a>
          </div>
        </div>
      </div>

      <!-- Alertas de Sesión -->
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2 small d-flex align-items-center mb-3">
          <i class="bi bi-check-circle-fill me-2 fs-5"></i>
          <div><?= htmlspecialchars($_SESSION['success']) ?></div>
        </div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2 small d-flex align-items-center mb-3">
          <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
          <div><?= htmlspecialchars($_SESSION['error']) ?></div>
        </div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <!-- Barra de Filtros y Búsqueda -->
      <div class="filter-toolbar bg-white p-3 rounded-3 shadow-sm mb-4 border">
        <div class="row g-3 align-items-center">
          <!-- Buscador en Tiempo Real -->
          <div class="col-lg-5">
            <div class="input-group input-group-sm search-group">
              <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
              <input type="text" id="buscadorInput" class="form-control bg-light border-start-0 ps-0" placeholder="Buscar por temática, nombre de evento o expositor..." autocomplete="off">
            </div>
          </div>

          <!-- Selector de Categorías Principales (Cursos, Talleres, Conferencias) -->
          <div class="col-lg-7">
            <div class="d-flex gap-2 overflow-x-auto pb-1 filter-tabs">
              <button class="btn btn-sm btn-category active text-nowrap" onclick="filtrarCategoria('TODOS', this)">
                Todos los tipos
              </button>
              <button class="btn btn-sm btn-category text-nowrap" onclick="filtrarCategoria('CURSO', this)">
                <i class="bi bi-book me-1"></i> Cursos
              </button>
              <button class="btn btn-sm btn-category text-nowrap" onclick="filtrarCategoria('TALLER', this)">
                <i class="bi bi-tools me-1"></i> Talleres
              </button>
              <button class="btn btn-sm btn-category text-nowrap" onclick="filtrarCategoria('CONFERENCIA', this)">
                <i class="bi bi-mic me-1"></i> Conferencias
              </button>
              <button class="btn btn-sm btn-category text-nowrap" onclick="filtrarCategoria('SEMINARIO', this)">
                <i class="bi bi-easel me-1"></i> Seminarios
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Estado Vacío por Búsqueda -->
      <div id="noResultados" class="alert alert-info py-4 text-center border-0 shadow-sm d-none">
        <i class="bi bi-search fs-3 text-muted d-block mb-2"></i>
        No se encontraron capacitaciones que coincidan con el criterio ingresado.
      </div>

      <div class="row g-4 row-cols-1 row-cols-md-2 row-cols-xl-3" id="contenedorEventos">
        <?php foreach ($eventosAbiertos as $ev): 
          $yaInscrito = in_array((int)$ev['id_evento'], $misEventosIds, true);
          $cuposMax = (int)$ev['cupo_maximo'];
          $inscritos = (int)$ev['total_inscritos'];
          $lleno = ($cuposMax > 0 && $inscritos >= $cuposMax);
          $disponibles = ($cuposMax > 0) ? max(0, $cuposMax - $inscritos) : 'Ilimitados';
          $porcentajeOcupado = ($cuposMax > 0) ? min(100, round(($inscritos / $cuposMax) * 100)) : 0;
          $tipoTexto = strtoupper($ev['tipo_evento_nombre']);
          
          // Distintivo de color según tipo de actividad
          $badgeEstilo = match (true) {
            str_contains($tipoTexto, 'CURSO') => 'badge-curso',
            str_contains($tipoTexto, 'TALLER') => 'badge-taller',
            str_contains($tipoTexto, 'CONFERENCIA') => 'badge-conferencia',
            str_contains($tipoTexto, 'SEMINARIO') => 'badge-seminario',
            default => 'badge-default'
          };
        ?>
          <div class="col card-evento-item" data-tipo="<?= $tipoTexto ?>" data-texto="<?= strtolower(htmlspecialchars($ev['titulo'] . ' ' . $ev['descripcion'] . ' ' . $ev['categoria_nombre'])) ?>">
            <div class="card event-card-modern h-100 border-0 shadow-sm rounded-3 bg-white d-flex flex-column">
              
              <!-- Encabezado y etiquetas -->
              <div class="p-3 pb-0">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="badge-tag <?= $badgeEstilo ?>">
                    <?= htmlspecialchars($ev['tipo_evento_nombre']) ?>
                  </span>
                  <span class="badge-status-dot">
                    <span class="dot"></span> Abierto
                  </span>
                </div>

                <h3 class="h6 fw-bold event-card-title mb-2">
                  <a href="index.php?action=detalle_evento&id=<?= $ev['id_evento'] ?>" class="text-decoration-none text-dark">
                    <?= htmlspecialchars($ev['titulo']) ?>
                  </a>
                </h3>

                <div class="d-flex flex-wrap gap-1 mb-2">
                  <span class="meta-tag"><i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($ev['modalidad']) ?></span>
                  <span class="meta-tag"><i class="bi bi-tag me-1"></i><?= htmlspecialchars($ev['categoria_nombre']) ?></span>
                  <?php if ((bool)$ev['emite_certificado']): ?>
                    <span class="meta-tag text-dark fw-semibold" style="background-color: #fef9c3;">
                      <i class="bi bi-patch-check-fill text-warning me-1"></i><?= $ev['horas_academicas'] ?>h
                    </span>
                  <?php endif; ?>
                </div>

                <p class="small text-muted event-description mb-3">
                  <?= htmlspecialchars(substr($ev['descripcion'], 0, 105)) ?>...
                </p>
              </div>

              <!-- Bloque Operativo (Fechas y Cupos) -->
              <div class="mt-auto px-3 py-2 bg-light border-top border-bottom small">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="text-muted"><i class="bi bi-calendar3 me-1 text-uab-azul"></i> Fecha inicio:</span>
                  <span class="fw-semibold"><?= date('d/m/Y', strtotime($ev['fecha_inicio'])) ?></span>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="text-muted"><i class="bi bi-people me-1 text-uab-azul"></i> Cupos disponibles:</span>
                  <span class="fw-bold <?= $lleno ? 'text-danger' : 'text-success' ?>">
                    <?= $disponibles ?> <?= ($cuposMax > 0) ? 'restantes' : '' ?>
                  </span>
                </div>

                <?php if ($cuposMax > 0): ?>
                  <div class="progress" style="height: 4px;">
                    <div class="progress-bar <?= $lleno ? 'bg-danger' : 'bg-success' ?>" style="width: <?= $porcentajeOcupado ?>%;"></div>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Doble Botonera de Acción -->
              <div class="p-3 bg-white">
                <div class="d-flex gap-2">
                  <!-- Botón 1: Ver Detalle Completo -->
                  <a href="index.php?action=detalle_evento&id=<?= $ev['id_evento'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill fw-semibold py-2 flex-fill d-flex align-items-center justify-content-center gap-1" title="Ver programa y requisitos">
                    <i class="bi bi-info-circle"></i> <span>Detalles</span>
                  </a>

                  <!-- Botón 2: Acción de Inscripción / Estado -->
                  <div class="flex-fill">
                    <?php if ($yaInscrito): ?>
                      <button class="btn btn-sm btn-outline-success w-100 rounded-pill fw-semibold py-2 d-flex align-items-center justify-content-center gap-1" disabled>
                        <i class="bi bi-check-circle-fill"></i> <span>Inscrito</span>
                      </button>
                    <?php elseif ($lleno): ?>
                      <button class="btn btn-sm btn-secondary w-100 rounded-pill fw-semibold py-2" disabled title="No quedan cupos disponibles">
                        <span>Agotado</span>
                      </button>
                    <?php else: ?>
                      <form action="index.php?action=inscribirse_evento" method="POST" class="m-0">
                        <input type="hidden" name="id_evento" value="<?= $ev['id_evento'] ?>">
                        <button type="submit" class="btn btn-sm btn-uab-azul w-100 rounded-pill fw-semibold py-2 shadow-sm d-flex align-items-center justify-content-center gap-1">
                          <i class="bi bi-pencil-square"></i> <span>Inscribirme</span>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</main>

<!-- Lógica de Filtro Reactivo e Instantáneo -->
<script>
let categoriaSeleccionada = 'TODOS';

function filtrarCategoria(tipo, boton) {
  categoriaSeleccionada = tipo;
  document.querySelectorAll('.filter-tabs .btn-category').forEach(b => b.classList.remove('active'));
  boton.classList.add('active');
  aplicarFiltros();
}

document.getElementById('buscadorInput').addEventListener('input', function() {
  aplicarFiltros();
});

function aplicarFiltros() {
  const query = document.getElementById('buscadorInput').value.trim().toLowerCase();
  const tarjetas = document.querySelectorAll('.card-evento-item');
  let coincidencias = 0;

  tarjetas.forEach(tarjeta => {
    const tipo = tarjeta.getAttribute('data-tipo');
    const texto = tarjeta.getAttribute('data-texto');

    const cumpleCategoria = (categoriaSeleccionada === 'TODOS') || tipo.includes(categoriaSeleccionada);
    const cumpleBusqueda = (query === '') || texto.includes(query);

    if (cumpleCategoria && cumpleBusqueda) {
      tarjeta.style.display = '';
      coincidencias++;
    } else {
      tarjeta.style.display = 'none';
    }
  });

  const alertaVacio = document.getElementById('noResultados');
  if (coincidencias === 0 && tarjetas.length > 0) {
    alertaVacio.classList.remove('d-none');
  } else {
    alertaVacio.classList.add('d-none');
  }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>