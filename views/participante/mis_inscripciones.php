<?php 
$titulo_pagina = "Mis Inscripciones — UAB Eventos";
$seccion_activa = "mis_inscripciones";
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php'; 

$db = Database::getConnection();
$stmtCert = $db->prepare("SELECT id_inscripcion, codigo_unico FROM certificados WHERE id_usuario = ? AND estado = 'EMITIDO'");
$stmtCert->execute([(int)$usuario['id_usuario']]);
$certificadosPorInscripcion = $stmtCert->fetchAll(PDO::FETCH_KEY_PAIR);

// Métricas del participante
$totalActivas = 0;
$totalCompletadas = 0;
$sumaAsistencias = 0;

foreach ($inscripciones as $i) {
    if ($i['estado'] === 'INSCRITO') $totalActivas++;
    if (in_array($i['estado'], ['APROBADO', 'ASISTIO'], true)) $totalCompletadas++;
    $sumaAsistencias += (float)$i['porcentaje_asistencia'];
}
$promedioAsistencia = !empty($inscripciones) ? round($sumaAsistencias / count($inscripciones), 1) : 0;
?>


<main class="container-fluid px-3 px-md-4 py-3">
  <div class="row g-3">
    
    <!-- Menú Lateral Responsivo -->
    <aside class="col-12 col-lg-3 col-xl-2">
      <?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
    </aside>

    <!-- Contenido Principal -->
    <section class="col-12 col-lg-9 col-xl-10">

      <!-- Banner de Resumen y Métricas Académicas -->
      <div class="rounded-4 p-4 p-md-5 mb-4 text-white shadow-sm position-relative overflow-hidden" 
           style="background: linear-gradient(135deg, #0d3b66 0%, #1a428a 50%, #08233d 100%) !important;">
        <div class="row align-items-center position-relative z-1 g-3">
          <div class="col-lg-7">
            <span class="badge bg-white text-uab-azul px-3 py-1 rounded-pill fw-bold mb-2 small">
              <i class="bi bi-mortarboard-fill me-1"></i> Mi Trayectoria
            </span>
            <h1 class="h3 fw-bold mb-1 text-white">Mis Actividades Académicas</h1>
            <p class="small text-white-50 mb-0">
              Sigue tu porcentaje de asistencia, descarga tus certificados oficiales y gestiona tus cupos.
            </p>
          </div>

          <!-- Mini Tarjetas de Progreso -->
          <div class="col-lg-5">
            <div class="row g-2 text-center">
              <div class="col-4">
                <div class="p-2 rounded-3" style="background: rgba(255, 255, 255, 0.12); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                  <span class="d-block display-6 fw-bold text-white lh-1"><?= $totalActivas ?></span>
                  <span class="small text-white-50 text-uppercase tracking-wider" style="font-size: 0.65rem;">En Curso</span>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 rounded-3" style="background: rgba(255, 255, 255, 0.12); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                  <span class="d-block display-6 fw-bold text-info lh-1"><?= count($certificadosPorInscripcion) ?></span>
                  <span class="small text-white-50 text-uppercase tracking-wider" style="font-size: 0.65rem;">Certificados</span>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 rounded-3" style="background: rgba(255, 255, 255, 0.12); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                  <span class="d-block display-6 fw-bold text-success lh-1"><?= $promedioAsistencia ?>%</span>
                  <span class="small text-white-50 text-uppercase tracking-wider" style="font-size: 0.65rem;">Asistencia Prom.</span>
                </div>
              </div>
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

      <!-- Barra de Filtros por Estado y Buscador -->
      <div class="bg-white p-3 rounded-4 shadow-sm border mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
          
          <!-- Filtro por pestañas -->
          <div class="d-flex gap-2 overflow-x-auto pb-1 filter-tabs">
            <button class="btn btn-sm btn-category active text-nowrap" onclick="filtrarEstado('TODOS', this)">
              Todas (<?= count($inscripciones) ?>)
            </button>
            <button class="btn btn-sm btn-category text-nowrap" onclick="filtrarEstado('INSCRITO', this)">
              <i class="bi bi-play-circle me-1"></i> Activas
            </button>
            <button class="btn btn-sm btn-category text-nowrap" onclick="filtrarEstado('COMPLETADO', this)">
              <i class="bi bi-check2-all me-1"></i> Finalizadas
            </button>
            <button class="btn btn-sm btn-category text-nowrap" onclick="filtrarEstado('CANCELADO', this)">
              <i class="bi bi-x-circle me-1"></i> Canceladas
            </button>
          </div>

          <a href="index.php?action=participante_dashboard" class="btn btn-sm btn-uab-azul rounded-pill px-3 py-2 text-nowrap align-self-start align-self-md-auto">
            <i class="bi bi-plus-circle me-1"></i> Inscribir más eventos
          </a>
        </div>
      </div>

      <!-- Listado Modular de Inscripciones -->
      <?php if (empty($inscripciones)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
          <div class="py-4">
            <div class="empty-icon-circle mx-auto mb-3">
              <i class="bi bi-journal-x fs-1 text-muted"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Aún no te has inscrito a ningún evento</h5>
            <p class="text-muted small mb-3">Descubre los talleres, seminarios y conferencias disponibles para tu carrera.</p>
            <a href="index.php?action=participante_dashboard" class="btn btn-uab-azul btn-sm rounded-pill px-4 py-2">
              <i class="bi bi-compass me-1"></i> Explorar eventos abiertos
            </a>
          </div>
        </div>
      <?php else: ?>
        <div class="row g-3" id="listaInscripciones">
          <?php foreach ($inscripciones as $ins): 
            $codigoCertificado = $certificadosPorInscripcion[$ins['id_inscripcion']] ?? null;
            $pct = (float)$ins['porcentaje_asistencia'];
            $estado = $ins['estado'];

            // Categorización semántica de estado
            $estadoFiltro = match($estado) {
              'INSCRITO' => 'INSCRITO',
              'CANCELADO' => 'CANCELADO',
              default => 'COMPLETADO'
            };

            $badgeColor = match($estado) {
              'INSCRITO'  => 'badge-soft-primary',
              'CANCELADO' => 'badge-soft-danger',
              'APROBADO', 'ASISTIO' => 'badge-soft-success',
              default     => 'badge-soft-secondary'
            };
          ?>
            <div class="col-12 item-inscripcion" data-estado="<?= $estadoFiltro ?>">
              <div class="card card-inscripcion-modern border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white position-relative">
                <div class="row align-items-center g-3">
                  
                  <!-- Bloque Izquierdo: Evento y Fechas -->
                  <div class="col-lg-5">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                      <span class="badge-tag badge-curso"><?= htmlspecialchars($ins['tipo_evento_nombre']) ?></span>
                      <span class="meta-tag"><i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($ins['modalidad']) ?></span>
                      <span class="badge <?= $badgeColor ?> px-2 py-1 rounded-pill small">
                        <?= $estado ?>
                      </span>
                    </div>

                    <h2 class="h6 fw-bold mb-2">
                      <a href="index.php?action=detalle_evento&id=<?= $ins['id_evento'] ?>" class="text-decoration-none text-dark hover-primary">
                        <?= htmlspecialchars($ins['evento_titulo']) ?>
                      </a>
                    </h2>

                    <div class="small text-muted d-flex align-items-center gap-3">
                      <span><i class="bi bi-calendar3 me-1 text-uab-azul"></i><?= date('d/m/Y', strtotime($ins['fecha_inicio'])) ?></span>
                      <span><i class="bi bi-qr-code me-1 text-muted"></i><code><?= htmlspecialchars($ins['codigo_inscripcion']) ?></code></span>
                    </div>
                  </div>

                  <!-- Bloque Central: Progreso de Asistencia -->
                  <div class="col-md-6 col-lg-3">
                    <div class="bg-light p-3 rounded-3 border border-light-subtle">
                      <div class="d-flex justify-content-between align-items-center mb-1 small">
                        <span class="text-muted fw-semibold"><i class="bi bi-clipboard-check me-1"></i> Asistencia:</span>
                        <strong class="<?= $pct >= 80 ? 'text-success' : 'text-warning' ?>"><?= number_format($pct, 0) ?>%</strong>
                      </div>
                      <div class="progress" style="height: 6px;">
                        <div class="progress-bar <?= $pct >= 80 ? 'bg-success' : 'bg-warning' ?> progress-bar-striped progress-bar-animated" 
                             style="width: <?= min(100, $pct) ?>%;"></div>
                      </div>
                      <span class="text-muted d-block mt-1 text-end" style="font-size: 0.68rem;">Mínimo para certificado: 80%</span>
                    </div>
                  </div>

                  <!-- Bloque Derecho: Certificado y Botones de Acción -->
                  <div class="col-md-6 col-lg-4 text-lg-end">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end align-items-center">
                      
                      <!-- Certificado Digital -->
                      <?php if ($codigoCertificado): ?>
                        <a href="index.php?action=imprimir_certificado&codigo=<?= urlencode($codigoCertificado) ?>" target="_blank" class="btn btn-sm btn-uab-verde rounded-pill px-3 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-1">
                          <i class="bi bi-award-fill"></i> Ver Certificado
                        </a>
                      <?php elseif ((bool)$ins['habilitado_certificado']): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill small">
                          <i class="bi bi-check-circle me-1"></i> Certificado Habilitado
                        </span>
                      <?php endif; ?>

                      <!-- Ficha del evento -->
                      <a href="index.php?action=detalle_evento&id=<?= $ins['id_evento'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-2 fw-semibold" title="Ver programa">
                        Detalles
                      </a>

                      <!-- Cancelar Inscripción -->
                      <?php if ($estado === 'INSCRITO'): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalCancelar" 
                                onclick="prepararCancelacion(<?= $ins['id_inscripcion'] ?>, '<?= htmlspecialchars(addslashes($ins['evento_titulo'])) ?>')">
                          <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                      <?php endif; ?>

                    </div>
                  </div>

                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </section>
  </div>
</main>

<!-- Modal Cancelación Voluntaria con Estilo Moderno (RF-12) -->
<div class="modal fade" id="modalCancelar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
      <form action="index.php?action=cancelar_inscripcion" method="POST">
        <div class="modal-header bg-danger text-white border-0 py-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-octagon-fill fs-5"></i>
            <h5 class="modal-title fw-bold">Cancelar Inscripción</h5>
          </div>
          <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" name="id_inscripcion" id="cancelar_id_inscripcion">
          <p class="small text-muted mb-1">Vas a liberar tu cupo del evento:</p>
          <h6 class="fw-bold text-dark mb-3" id="cancelar_evento_titulo"></h6>
          
          <div class="mb-3">
            <label class="form-label small fw-semibold">Motivo de cancelación (opcional)</label>
            <textarea name="motivo" class="form-control rounded-3" rows="2" placeholder="Ej: Choque de horarios, motivos de fuerza mayor..."></textarea>
          </div>
          
          <div class="alert alert-warning py-2 small mb-0 rounded-3 border-0 bg-warning-subtle text-dark">
            <i class="bi bi-info-circle-fill me-1 text-warning"></i> Tu plaza quedará disponible de inmediato para otros compañeros.
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 px-4 pb-4">
          <button type="button" class="btn btn-light rounded-pill btn-sm px-3" data-bs-dismiss="modal">Volver atrás</button>
          <button type="submit" class="btn btn-danger rounded-pill btn-sm px-4 fw-bold">Confirmar baja</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function prepararCancelacion(idInscripcion, titulo) {
  document.getElementById('cancelar_id_inscripcion').value = idInscripcion;
  document.getElementById('cancelar_evento_titulo').textContent = titulo;
}

function filtrarEstado(estado, boton) {
  document.querySelectorAll('.filter-tabs .btn-category').forEach(b => b.classList.remove('active'));
  boton.classList.add('active');

  const items = document.querySelectorAll('.item-inscripcion');
  items.forEach(el => {
    if (estado === 'TODOS' || el.getAttribute('data-estado') === estado) {
      el.style.display = '';
    } else {
      el.style.display = 'none';
    }
  });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>