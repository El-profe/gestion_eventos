<?php 
$titulo_pagina = htmlspecialchars($evento['titulo']) . " — UAB Eventos";
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php'; 

$porcentajeOcupacion = ($evento['cupo_maximo'] > 0) ? min(100, round(($evento['total_inscritos'] / $evento['cupo_maximo']) * 100)) : 0;
?>

<main class="container py-5">
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php?action=catalogo">Catálogo</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($evento['titulo']) ?></li>
    </ol>
  </nav>

  <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger py-2 mb-4"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Columna Principal: Información General y Sesiones -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm p-4 mb-4">
        <div class="d-flex flex-wrap gap-2 mb-3">
          <span class="badge bg-uab-azul fs-6"><?= htmlspecialchars($evento['tipo_evento_nombre']) ?></span>
          <span class="badge bg-light text-dark border fs-6"><?= htmlspecialchars($evento['modalidad']) ?></span>
          <span class="badge bg-light text-secondary border fs-6"><?= htmlspecialchars($evento['categoria_nombre']) ?></span>
        </div>

        <h1 class="h3 fw-bold text-uab-azul"><?= htmlspecialchars($evento['titulo']) ?></h1>
        <p class="mt-3 text-secondary lh-lg"><?= nl2br(htmlspecialchars($evento['descripcion'])) ?></p>

        <!-- Cronograma de Sesiones Independientes (RF-24) -->
        <h2 class="h5 fw-bold text-uab-azul mt-4 mb-3"><i class="bi bi-clock-history me-2"></i>Cronograma de Clases y Sesiones</h2>
        <?php if (empty($sesiones)): ?>
          <div class="alert alert-light border small">Cronograma de horarios en proceso de confirmación.</div>
        <?php else: ?>
          <div class="list-group shadow-none mb-4">
            <?php foreach ($sesiones as $idx => $ses): ?>
              <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                <div>
                  <h6 class="mb-1 fw-bold"><?= htmlspecialchars($ses['titulo']) ?></h6>
                  <small class="text-muted">
                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ses['lugar_especifico'] ?? 'Lugar general') ?>
                  </small>
                </div>
                <div class="text-end">
                  <span class="badge bg-light text-dark border"><?= date('d/m/Y', strtotime($ses['fecha'])) ?></span>
                  <div class="small text-muted mt-1"><?= substr($ses['hora_inicio'], 0, 5) ?> - <?= substr($ses['hora_fin'], 0, 5) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Materiales de Apoyo (RF-28) -->
        <?php if (!empty($materiales)): ?>
          <h2 class="h5 fw-bold text-uab-azul mt-3 mb-3"><i class="bi bi-folder2-open me-2"></i>Materiales Descargables</h2>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($materiales as $mat): ?>
              <a href="<?= htmlspecialchars($mat['ruta_archivo']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-arrow-down text-uab-azul"></i>
                <span><?= htmlspecialchars($mat['titulo']) ?></span>
                <span class="badge bg-light text-dark border"><?= $mat['tipo_archivo'] ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Columna Lateral: Ficha Técnica y Panel de Inscripción -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm p-4 sticky-top" style="top: 80px;">
        <h3 class="h6 fw-bold text-uppercase text-muted mb-3" style="letter-spacing:1px;">Condiciones del Evento</h3>
        
        <div class="small mb-3">
          <div class="mb-2"><i class="bi bi-calendar-range me-2 text-uab-azul"></i><strong>Fechas:</strong> <?= date('d/m/Y', strtotime($evento['fecha_inicio'])) ?> al <?= date('d/m/Y', strtotime($evento['fecha_fin'])) ?></div>
          <div class="mb-2"><i class="bi bi-hourglass-split me-2 text-uab-azul"></i><strong>Carga Horaria:</strong> <?= $evento['horas_academicas'] ?> horas académicas</div>
          <div class="mb-2"><i class="bi bi-percent me-2 text-uab-azul"></i><strong>Asistencia Mínima:</strong> <?= $evento['porcentaje_asistencia_minimo'] ?>% para certificación</div>
          
          <?php if (!empty($evento['expositores'])): ?>
            <div class="mt-3 pt-2 border-top">
              <strong>Docentes / Expositores:</strong>
              <?php foreach ($evento['expositores'] as $exp): ?>
                <div class="text-secondary mt-1"><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($exp['nombres'] . ' ' . $exp['apellidos']) ?> (<?= htmlspecialchars($exp['rol_expositor']) ?>)</div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Barra de Disponibilidad de Cupos (RF-35) -->
        <div class="mb-3">
          <div class="d-flex justify-content-between small fw-semibold mb-1">
            <span>Ocupación de Cupos</span>
            <span><?= $evento['total_inscritos'] ?> / <?= $evento['cupo_maximo'] ?: '∞' ?></span>
          </div>
          <div class="progress" style="height:8px;">
            <div class="progress-bar <?= ($porcentajeOcupacion >= 100) ? 'bg-danger' : 'bg-uab-verde' ?>" style="width: <?= $porcentajeOcupacion ?>%;"></div>
          </div>
        </div>

        <!-- Acciones del Participante (RF-10, RF-11) -->
        <div class="mt-3 pt-3 border-top">
          <?php if (!AuthHelper::estaAutenticado()): ?>
            <a href="index.php?action=login" class="btn btn-uab-azul w-100 py-2">
              <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar sesión para inscribirse
            </a>
            <div class="text-center small mt-2">
              ¿No tiene cuenta? <a href="index.php?action=registro">Crear cuenta</a>
            </div>
          <?php elseif ($estaInscrito): ?>
            <div class="alert alert-success small py-2 d-flex align-items-center gap-2 mb-2">
              <i class="bi bi-check-circle-fill fs-5"></i>
              <div>Usted ya se encuentra formalmente inscrito en este evento.</div>
            </div>
            <a href="index.php?action=mis_inscripciones" class="btn btn-outline-secondary btn-sm w-100">Ver mis inscripciones</a>
          <?php elseif (!$disponibilidad['habilitado']): ?>
            <div class="alert alert-warning small py-2">
              <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($disponibilidad['motivo']) ?>
            </div>
            <button class="btn btn-secondary w-100 py-2" disabled>Inscripciones no disponibles</button>
          <?php else: ?>
            <form action="index.php?action=inscribirse_evento" method="POST">
              <input type="hidden" name="id_evento" value="<?= $evento['id_evento'] ?>">
              <button type="submit" class="btn btn-uab-verde w-100 py-2 fw-semibold">
                <i class="bi bi-pencil-square me-1"></i>Confirmar Inscripción
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>