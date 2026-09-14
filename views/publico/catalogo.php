<?php 
$titulo_pagina = "Catálogo de Eventos Académicos — UAB";
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php'; 
?>

<main class="container py-5">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold text-uab-azul mb-1">Eventos y Actividades Académicas</h1>
      <p class="text-muted mb-0 small">Seminarios, congresos, talleres y capacitaciones de extensión universitaria.</p>
    </div>
  </div>

  <!-- Formulario de Filtros Dinámicos -->
  <form action="index.php" method="GET" class="row g-3 mb-4 bg-white p-3 rounded-3 shadow-sm border">
    <input type="hidden" name="action" value="catalogo">
    
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Tipo de Evento</label>
      <select name="id_tipo_evento" class="form-select form-select-sm">
        <option value="">Todos los tipos</option>
        <?php foreach ($tipos as $t): ?>
          <option value="<?= $t['id_tipo_evento'] ?>" <?= (isset($_GET['id_tipo_evento']) && $_GET['id_tipo_evento'] == $t['id_tipo_evento']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label small fw-semibold">Categoría</label>
      <select name="id_categoria" class="form-select form-select-sm">
        <option value="">Todas las categorías</option>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= $c['id_categoria'] ?>" <?= (isset($_GET['id_categoria']) && $_GET['id_categoria'] == $c['id_categoria']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label small fw-semibold">Buscar por título o descripción</label>
      <input type="search" name="buscar" class="form-control form-control-sm" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
    </div>

    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" class="btn btn-uab-azul btn-sm w-100">
        <i class="bi bi-filter me-1"></i>Filtrar
      </button>
    </div>
  </form>

  <!-- Grilla de Eventos -->
  <?php if (empty($eventos)): ?>
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2">
      <i class="bi bi-info-circle-fill fs-4"></i>
      <div>No se encontraron eventos activos con los criterios de búsqueda especificados.</div>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($eventos as $ev): 
        $lleno = ($ev['cupo_maximo'] > 0 && $ev['total_inscritos'] >= $ev['cupo_maximo']);
      ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card event-card shadow-sm h-100 border-0">
            <div class="card-body d-flex flex-column p-4">
              <div class="d-flex flex-wrap gap-1 mb-2">
                <span class="badge bg-uab-azul"><?= htmlspecialchars($ev['tipo_evento_nombre']) ?></span>
                <span class="badge bg-light text-dark border"><?= htmlspecialchars($ev['modalidad']) ?></span>
                <span class="badge bg-light text-secondary border"><?= htmlspecialchars($ev['categoria_nombre']) ?></span>
              </div>

              <h2 class="h5 card-title fw-bold text-uab-azul mb-2"><?= htmlspecialchars($ev['titulo']) ?></h2>
              
              <div class="small text-muted mb-3">
                <div class="mb-1"><i class="bi bi-calendar3 me-2"></i><?= date('d/m/Y', strtotime($ev['fecha_inicio'])) ?> al <?= date('d/m/Y', strtotime($ev['fecha_fin'])) ?></div>
                <div class="mb-1"><i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars($ev['lugar'] ?? 'Modalidad Virtual') ?></div>
                <div><i class="bi bi-people me-2"></i>Cupo: <?= $ev['total_inscritos'] ?> / <?= $ev['cupo_maximo'] ?: 'Ilimitado' ?>
                  <?php if ($lleno): ?>
                    <span class="badge bg-danger ms-1">Agotado</span>
                  <?php endif; ?>
                </div>
              </div>

              <p class="card-text small text-secondary flex-grow-1">
                <?= htmlspecialchars(substr($ev['descripcion'], 0, 130)) ?>...
              </p>

              <a href="index.php?action=detalle_evento&id=<?= $ev['id_evento'] ?>" class="btn btn-uab-azul w-100 mt-auto">
                <i class="bi bi-eye me-1"></i>Ver detalles e inscripción
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>