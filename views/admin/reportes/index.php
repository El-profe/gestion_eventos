<?php 
$titulo_pagina = "Reportes y Estadísticas — Admin UAB";
$seccion_activa = "reportes";
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/navbar.php'; 

require_once __DIR__ . '/../../../controllers/ReporteController.php';
$repCtrl = new ReporteController();
$listaEventos = $repCtrl->reporteEventos();
?>

<main class="container py-4">
  <div class="row g-4">
    <aside class="col-lg-3 no-print">
      <?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>
    </aside>

    <section class="col-lg-9">
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
        <div>
          <h1 class="h3 fw-bold text-uab-azul mb-0">Reportes y Analítica</h1>
          <p class="text-muted small mb-0">Descarga de padrones, asistencia y certificaciones en formato CSV/Excel</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Imprimir Vista
          </button>
        </div>
      </div>

      <!-- Centro de Exportaciones -->
      <div class="card border-0 shadow-sm p-4 mb-4 bg-white no-print">
        <h2 class="h6 fw-bold text-uab-azul mb-3"><i class="bi bi-download me-2"></i>Descarga de Archivos de Datos (CSV / Excel)</h2>
        <div class="row g-2">
          <div class="col-md-4">
            <a href="index.php?action=admin_exportar_reporte&tipo=eventos" class="btn btn-outline-dark btn-sm w-100 py-2">
              <i class="bi bi-filetype-csv me-1 text-success"></i> Exportar Eventos
            </a>
          </div>
          <div class="col-md-4">
            <a href="index.php?action=admin_exportar_reporte&tipo=certificados" class="btn btn-outline-dark btn-sm w-100 py-2">
              <i class="bi bi-filetype-csv me-1 text-success"></i> Exportar Certificados
            </a>
          </div>
          <div class="col-md-4">
            <a href="index.php?action=admin_exportar_reporte&tipo=reimpresiones" class="btn btn-outline-dark btn-sm w-100 py-2">
              <i class="bi bi-filetype-csv me-1 text-success"></i> Exportar Reimpresiones
            </a>
          </div>
          <div class="col-md-4">
            <a href="index.php?action=admin_exportar_reporte&tipo=expositores" class="btn btn-outline-dark btn-sm w-100 py-2">
              <i class="bi bi-filetype-csv me-1 text-success"></i> Exportar Expositores
            </a>
          </div>
        </div>
      </div>

      <!-- Tabla Resumen de Eventos e Indicadores -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
          <h2 class="h6 fw-bold mb-0 text-uab-azul"><i class="bi bi-table me-2"></i>Consolidado de Actividades Activas</h2>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Código</th>
                <th>Evento</th>
                <th>Modalidad</th>
                <th>Estado</th>
                <th class="text-center">Inscritos</th>
                <th class="text-center">Certificados</th>
                <th class="text-end no-print">Padrón</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($listaEventos as $ev): ?>
                <tr>
                  <td><code><?= htmlspecialchars($ev['codigo']) ?></code></td>
                  <td><strong><?= htmlspecialchars($ev['titulo']) ?></strong></td>
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($ev['modalidad']) ?></span></td>
                  <td><span class="badge bg-uab-azul"><?= htmlspecialchars($ev['estado']) ?></span></td>
                  <td class="text-center"><?= $ev['inscritos_actuales'] ?></td>
                  <td class="text-center"><?= $ev['certificados_emitidos'] ?></td>
                  <td class="text-end no-print">
                    <a href="index.php?action=admin_exportar_reporte&tipo=participantes&id_evento=<?= $ev['codigo'] ?>" class="btn btn-sm btn-outline-success" title="Descargar padrón CSV">
                      <i class="bi bi-file-earmark-spreadsheet"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>