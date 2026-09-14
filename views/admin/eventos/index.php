<?php 
$titulo_pagina = "Gestión de Eventos — Admin UAB";
$seccion_activa = "eventos";
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/navbar.php'; 
?>

<main class="container py-4">
  <div class="row g-4">
    <aside class="col-lg-3">
      <?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>
    </aside>

    <section class="col-lg-9">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h1 class="h3 fw-bold text-uab-azul mb-0">Gestión de Eventos</h1>
          <p class="text-muted small mb-0">Administración de capacitaciones, cupos y ciclo de publicación</p>
        </div>
        <button class="btn btn-uab-azul" data-bs-toggle="modal" data-bs-target="#modalNuevoEvento">
          <i class="bi bi-plus-circle me-1"></i> Nuevo Evento
        </button>
      </div>

      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2 small mb-3"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2 small mb-3"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <div class="table-responsive card border-0 shadow-sm">
        <table class="table table-uab table-hover align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Evento</th>
              <th scope="col">Fechas</th>
              <th scope="col">Cupo</th>
              <th scope="col">Estado</th>
              <th scope="col" class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($eventos)): ?>
              <tr><td colspan="5" class="text-center py-4 text-muted">No existen eventos registrados en el sistema.</td></tr>
            <?php else: ?>
              <?php foreach ($eventos as $ev): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($ev['titulo']) ?></strong>
                    <div class="small text-muted">
                      <code><?= htmlspecialchars($ev['codigo']) ?></code> · <?= htmlspecialchars($ev['tipo_evento_nombre']) ?> (<?= htmlspecialchars($ev['modalidad']) ?>)
                    </div>
                  </td>
                  <td class="small">
                    <div><?= date('d/m/Y', strtotime($ev['fecha_inicio'])) ?></div>
                    <span class="text-muted"><?= date('d/m/Y', strtotime($ev['fecha_fin'])) ?></span>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">
                      <?= $ev['total_inscritos'] ?? 0 ?> / <?= $ev['cupo_maximo'] > 0 ? $ev['cupo_maximo'] : '∞' ?>
                    </span>
                  </td>
                  <td>
                    <!-- Switch para cambiar entre BORRADOR y PUBLICADO -->
                    <form action="index.php?action=admin_evento_cambiar_estado" method="POST" class="d-inline">
                      <input type="hidden" name="id_evento" value="<?= $ev['id_evento'] ?>">
                      <input type="hidden" name="estado" value="<?= ($ev['estado'] === 'PUBLICADO') ? 'BORRADOR' : 'PUBLICADO' ?>">
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" onchange="this.form.submit()" <?= ($ev['estado'] === 'PUBLICADO') ? 'checked' : '' ?>>
                        <label class="form-check-label small text-muted"><?= $ev['estado'] ?></label>
                      </div>
                    </form>
                  </td>
                  <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary" href="index.php?action=detalle_evento&id=<?= $ev['id_evento'] ?>" title="Ver público" target="_blank">
                      <i class="bi bi-eye"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalInscripcionManual" onclick="prepararInscripcionManual(<?= $ev['id_evento'] ?>, '<?= htmlspecialchars(addslashes($ev['titulo'])) ?>')">
                      <i class="bi bi-person-plus"></i>
                    </button>
                    <a class="btn btn-sm btn-outline-uab-azul" href="index.php?action=admin_evento_editar&id=<?= $ev['id_evento'] ?>">
                      <i class="bi bi-pencil"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</main>

<!-- Modal: Nuevo Evento -->
<div class="modal fade" id="modalNuevoEvento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form action="index.php?action=admin_eventos_guardar" method="POST">
        <div class="modal-header bg-uab-azul text-white">
          <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>Registrar Nuevo Evento</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Código Único *</label>
              <input type="text" name="codigo" class="form-control" required placeholder="EVT-2026-001">
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-semibold">Título del Evento *</label>
              <input type="text" name="titulo" class="form-control" required minlength="8">
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-semibold">Tipo *</label>
              <select name="id_tipo_evento" class="form-select" required>
                <?php foreach ($tipos as $t): ?>
                  <option value="<?= $t['id_tipo_evento'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Categoría *</label>
              <select name="id_categoria" class="form-select" required>
                <?php foreach ($categorias as $c): ?>
                  <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Modalidad *</label>
              <select name="modalidad" class="form-select" required>
                <option value="PRESENCIAL">Presencial</option>
                <option value="VIRTUAL">Virtual</option>
                <option value="HIBRIDA">Híbrida</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-semibold">Cupo Máximo (0 = libre)</label>
              <input type="number" name="cupo_maximo" class="form-control" min="0" value="50" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Horas Académicas</label>
              <input type="number" name="horas_academicas" class="form-control" min="1" value="20" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">% Asistencia Mínimo</label>
              <input type="number" step="0.1" name="porcentaje_asistencia_minimo" class="form-control" value="80.0" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold">Inicio Inscripciones *</label>
              <input type="datetime-local" name="fecha_inicio_inscripcion" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Cierre Inscripciones *</label>
              <input type="datetime-local" name="fecha_fin_inscripcion" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold">Fecha Inicio Evento *</label>
              <input type="date" name="fecha_inicio" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Fecha Fin Evento *</label>
              <input type="date" name="fecha_fin" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold">Lugar Físico</label>
              <input type="text" name="lugar" class="form-control" placeholder="Auditorio Central">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Enlace Virtual</label>
              <input type="url" name="enlace_virtual" class="form-control" placeholder="https://meet.google.com/...">
            </div>

            <div class="col-12">
              <label class="form-label small fw-semibold">Descripción del Evento *</label>
              <textarea name="descripcion" class="form-control" rows="3" required></textarea>
            </div>

            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="emite_certificado" value="1" id="checkCert" checked>
                <label class="form-check-label small fw-semibold" for="checkCert">Este evento otorga certificado oficial</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-uab-azul btn-sm">Guardar en Borrador</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Inscripción Manual -->
<div class="modal fade" id="modalInscripcionManual" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="index.php?action=admin_inscribir_manual" method="POST">
        <div class="modal-header bg-uab-verde text-white">
          <h5 class="modal-title"><i class="bi bi-person-plus me-1"></i> Inscripción Manual Administrativa</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" name="id_evento" id="manual_id_evento">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Evento Seleccionado</label>
            <input type="text" id="manual_evento_titulo" class="form-control bg-light" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Seleccione el Participante *</label>
            <select name="id_usuario" class="form-select" required>
              <option value="" disabled selected>Seleccione usuario registrado...</option>
              <?php foreach ($usuarios_participantes as $u): ?>
                <option value="<?= $u['id_usuario'] ?>">
                  <?= htmlspecialchars($u['apellidos'] . ' ' . $u['nombres']) ?> (CI: <?= htmlspecialchars($u['ci']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-uab-verde btn-sm">Confirmar Inscripción</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function prepararInscripcionManual(idEvento, titulo) {
  document.getElementById('manual_id_evento').value = idEvento;
  document.getElementById('manual_evento_titulo').value = titulo;
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>