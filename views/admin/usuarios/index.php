<?php 
$titulo_pagina = "Gestión de Usuarios — Admin UAB";
$seccion_activa = "usuarios";
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
          <h1 class="h3 fw-bold text-uab-azul mb-0">Gestión de Usuarios</h1>
          <p class="text-muted small mb-0">Control de acceso institucional, roles y activación de cuentas</p>
        </div>
      </div>

      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2 small mb-3"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger py-2 small mb-3"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <!-- Filtro por Rol -->
      <form action="index.php" method="GET" class="card border-0 shadow-sm p-3 mb-3 bg-white">
        <input type="hidden" name="action" value="admin_usuarios">
        <div class="row g-2 align-items-center">
          <div class="col-md-5">
            <select name="rol" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="">Todos los roles</option>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id_rol'] ?>" <?= (isset($_GET['rol']) && $_GET['rol'] == $r['id_rol']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($r['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </form>

      <div class="table-responsive card border-0 shadow-sm">
        <table class="table table-uab table-hover align-middle mb-0">
          <thead>
            <tr>
              <th scope="col">Usuario</th>
              <th scope="col">Cédula (CI)</th>
              <th scope="col">Correo Electrónico</th>
              <th scope="col">Rol Asignado</th>
              <th scope="col">Estado</th>
              <th scope="col" class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $u): 
              $esPropio = ((int)$u['id_usuario'] === (int)$usuarioSesion['id_usuario']);
            ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($u['apellidos'] . ', ' . $u['nombres']) ?></strong>
                  <?php if ($esPropio): ?>
                    <span class="badge bg-uab-azul ms-1">Usted</span>
                  <?php endif; ?>
                </td>
                <td><code><?= htmlspecialchars($u['ci']) ?></code></td>
                <td class="small"><?= htmlspecialchars($u['correo']) ?></td>
                <td>
                  <span class="badge <?= $u['rol_nombre'] === 'ADMINISTRADOR' ? 'bg-dark' : ($u['rol_nombre'] === 'EXPOSITOR' ? 'bg-uab-verde' : 'bg-secondary') ?>">
                    <?= htmlspecialchars($u['rol_nombre']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= $u['activo'] ? 'bg-success' : 'bg-danger' ?>">
                    <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                  </span>
                </td>
                <td class="text-end text-nowrap">
                  <?php if (!$esPropio): ?>
                    <!-- Cambiar Rol -->
                    <button class="btn btn-sm btn-outline-uab-azul" data-bs-toggle="modal" data-bs-target="#modalCambiarRol" onclick="prepararCambioRol(<?= $u['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($u['nombres'] . ' ' . $u['apellidos'])) ?>', <?= $u['id_rol'] ?>)">
                      <i class="bi bi-shield-lock" title="Cambiar rol"></i>
                    </button>

                    <!-- Toggle Activar/Desactivar -->
                    <form action="index.php?action=admin_usuario_toggle_estado" method="POST" class="d-inline">
                      <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                      <button type="submit" class="btn btn-sm <?= $u['activo'] ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="<?= $u['activo'] ? 'Desactivar cuenta' : 'Activar cuenta' ?>">
                        <i class="bi <?= $u['activo'] ? 'bi-person-slash' : 'bi-person-check' ?>"></i>
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</main>

<!-- Modal: Cambiar Rol -->
<div class="modal fade" id="modalCambiarRol" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="index.php?action=admin_usuario_cambiar_rol" method="POST">
        <div class="modal-header bg-dark text-white">
          <h5 class="modal-title"><i class="bi bi-shield-lock me-1"></i> Asignar Rol de Usuario</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" name="id_usuario" id="rol_id_usuario">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Usuario</label>
            <input type="text" id="rol_usuario_nombre" class="form-control bg-light" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nuevo Rol *</label>
            <select name="id_rol" id="rol_select" class="form-select" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre']) ?> — <?= htmlspecialchars($r['descripcion']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-uab-azul btn-sm">Actualizar Privilegios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function prepararCambioRol(idUsuario, nombre, idRolActual) {
  document.getElementById('rol_id_usuario').value = idUsuario;
  document.getElementById('rol_usuario_nombre').value = nombre;
  document.getElementById('rol_select').value = idRolActual;
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>