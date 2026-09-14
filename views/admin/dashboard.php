<?php 
// views/admin/dashboard.php
$titulo_pagina = "Panel Administrativo — UAB Eventos";
$seccion_activa = "dashboard";
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php'; 

// Consultar los últimos eventos gestionados si no vinieron precargados
if (!isset($ultimosEventos)) {
    $db = Database::getConnection();
    $stmtUltimos = $db->query("SELECT e.*, te.nombre AS tipo_evento_nombre,
        (SELECT COUNT(*) FROM inscripciones i WHERE i.id_evento = e.id_evento AND i.estado = 'INSCRITO') AS total_inscritos
        FROM eventos e
        INNER JOIN tipos_evento te ON e.id_tipo_evento = te.id_tipo_evento
        ORDER BY e.fecha_creacion DESC LIMIT 5");
    $ultimosEventos = $stmtUltimos->fetchAll();
}

$totalEventos = array_sum($eventosPorEstado ?? []);
?>

<main class="container py-4">
  <div class="row g-4">
    <!-- Menú lateral unificado -->
    <aside class="col-lg-3">
      <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    </aside>

    <!-- Contenido principal del Dashboard -->
    <section class="col-lg-9">
      <!-- Banner de bienvenida estilo Hero -->
      <div class="hero-uab rounded-3 p-4 p-md-5 mb-4 shadow-sm">
        <span class="uab-logo mb-3">UAB</span>
        <h1 class="h3 fw-bold mb-1">Bienvenido/a, <?= htmlspecialchars($usuarioSesion['nombres'] . ' ' . $usuarioSesion['apellidos']) ?></h1>
        <p class="mb-0 text-white-50 small">Panel Central de Coordinación y Gestión Académica — UAB</p>
      </div>

      <!-- Tarjetas de Métricas Rápidas (KPIs) -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
          <a class="text-decoration-none" href="index.php?action=admin_eventos">
            <div class="card stat-card stat-dark border-0 shadow-sm h-100">
              <div class="card-body">
                <i class="bi bi-calendar-event text-uab-azul fs-4"></i>
                <div class="stat-num"><?= $totalEventos ?></div>
                <div class="small text-muted">Eventos registrados</div>
              </div>
            </div>
          </a>
        </div>

        <div class="col-6 col-xl-3">
          <a class="text-decoration-none" href="index.php?action=admin_usuarios">
            <div class="card stat-card border-0 shadow-sm h-100">
              <div class="card-body">
                <i class="bi bi-people text-uab-azul fs-4"></i>
                <div class="stat-num"><?= $totalUsuarios ?? 0 ?></div>
                <div class="small text-muted">Usuarios activos</div>
              </div>
            </div>
          </a>
        </div>

        <div class="col-6 col-xl-3">
          <a class="text-decoration-none" href="index.php?action=admin_reportes">
            <div class="card stat-card stat-green border-0 shadow-sm h-100">
              <div class="card-body">
                <i class="bi bi-journal-check text-uab-verde fs-4"></i>
                <div class="stat-num"><?= $totalInscripciones ?? 0 ?></div>
                <div class="small text-muted">Inscripciones activas</div>
              </div>
            </div>
          </a>
        </div>

        <div class="col-6 col-xl-3">
          <a class="text-decoration-none" href="index.php?action=admin_reportes">
            <div class="card stat-card stat-red border-0 shadow-sm h-100">
              <div class="card-body">
                <i class="bi bi-award text-danger fs-4"></i>
                <div class="stat-num"><?= $totalCertificados ?? 0 ?></div>
                <div class="small text-muted">Certificados emitidos</div>
              </div>
            </div>
          </a>
        </div>
      </div>

      <!-- Alerta de solicitudes pendientes de reimpresión (RF-60) -->
      <?php if (($solicitudesPend ?? 0) > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between p-3 mb-4">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-circle-fill fs-4 text-warning"></i>
            <div>
              <strong><?= $solicitudesPend ?> solicitud(es) de reimpresión pendiente(s)</strong> de revisión.
            </div>
          </div>
          <a href="index.php?action=admin_reportes" class="btn btn-sm btn-dark">Revisar solicitudes</a>
        </div>
      <?php endif; ?>

      <!-- Tabla: Últimos Eventos Gestionados -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-uab-azul">
            <i class="bi bi-calendar2-range me-2"></i>Últimos Eventos Gestionados
          </h2>
          <div class="d-flex gap-2">
            <a href="index.php?action=admin_eventos" class="btn btn-uab-azul btn-sm">
              <i class="bi bi-gear me-1"></i> Administrar eventos
            </a>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Evento</th>
                <th>Fecha Inicio</th>
                <th>Inscritos / Cupo</th>
                <th>Estado</th>
                <th class="text-end">Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($ultimosEventos)): ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">No existen eventos creados recientemente.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($ultimosEventos as $ev): ?>
                  <tr>
                    <td>
                      <strong><?= htmlspecialchars($ev['titulo']) ?></strong>
                      <div class="small text-muted">
                        <code><?= htmlspecialchars($ev['codigo']) ?></code> · <?= htmlspecialchars($ev['tipo_evento_nombre']) ?> (<?= htmlspecialchars($ev['modalidad']) ?>)
                      </div>
                    </td>
                    <td class="small">
                      <?= date('d/m/Y', strtotime($ev['fecha_inicio'])) ?>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border">
                        <?= $ev['total_inscritos'] ?> / <?= $ev['cupo_maximo'] > 0 ? $ev['cupo_maximo'] : '∞' ?>
                      </span>
                    </td>
                    <td>
                      <?php
                      $badgeEstado = match ($ev['estado']) {
                          'PUBLICADO'  => 'bg-success',
                          'BORRADOR'   => 'bg-secondary',
                          'EN_CURSO'   => 'bg-primary',
                          'FINALIZADO' => 'bg-dark',
                          'CANCELADO'  => 'bg-danger',
                          default      => 'bg-light text-dark'
                      };
                      ?>
                      <span class="badge <?= $badgeEstado ?>"><?= $ev['estado'] ?></span>
                    </td>
                    <td class="text-end">
                      <a href="index.php?action=detalle_evento&id=<?= $ev['id_evento'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Ver detalle">
                        <i class="bi bi-eye"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Bitácora de Auditoría Administrativa Reciente (RF-70) -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
          <h2 class="h6 fw-bold mb-0 text-uab-azul">
            <i class="bi bi-shield-check me-2"></i>Actividad Administrativa Reciente (Auditoría)
          </h2>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Fecha y Hora</th>
                <th>Administrador</th>
                <th>Módulo</th>
                <th>Acción Realizada</th>
                <th>Detalles</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($ultimasAuditorias)): ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">Sin registros recientes de auditoría.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($ultimasAuditorias as $aud): ?>
                  <tr>
                    <td class="small text-muted text-nowrap"><?= date('d/m/Y H:i', strtotime($aud['fecha_registro'])) ?></td>
                    <td><strong class="small"><?= htmlspecialchars($aud['nombres'] . ' ' . $aud['apellidos']) ?></strong></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($aud['modulo']) ?></span></td>
                    <td><code class="small"><?= htmlspecialchars($aud['accion']) ?></code></td>
                    <td class="small text-secondary"><?= htmlspecialchars($aud['detalles'] ?? '—') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>