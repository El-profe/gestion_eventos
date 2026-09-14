<?php
// controllers/AdminController.php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../models/Inscripcion.php';
require_once __DIR__ . '/../models/Asistencia.php';
require_once __DIR__ . '/../models/Certificado.php';
require_once __DIR__ . '/../models/SolicitudReimpresion.php';
require_once __DIR__ . '/../models/Auditoria.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

class AdminController {
    private PDO $db;
    private Usuario $usuarioModel;
    private Evento $eventoModel;
    private Inscripcion $inscripcionModel;
    private Asistencia $asistenciaModel;
    private Certificado $certificadoModel;
    private SolicitudReimpresion $reimpresionModel;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->usuarioModel = new Usuario();
        $this->eventoModel = new Evento();
        $this->inscripcionModel = new Inscripcion();
        $this->asistenciaModel = new Asistencia();
        $this->certificadoModel = new Certificado();
        $this->reimpresionModel = new SolicitudReimpresion();
    }

    /**
     * Métricas principales (KPIs) y resumen global para el Dashboard
     */
    public function dashboard(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        // Conteo de eventos por estado
        $stmtEventos = $this->db->query("SELECT estado, COUNT(*) as total FROM eventos GROUP BY estado");
        $eventosPorEstado = $stmtEventos->fetchAll(PDO::FETCH_KEY_PAIR);

        // Indicadores clave
        $totalInscripciones = (int)$this->db->query("SELECT COUNT(*) FROM inscripciones WHERE estado = 'INSCRITO'")->fetchColumn();
        $totalCertificados  = (int)$this->db->query("SELECT COUNT(*) FROM certificados WHERE estado = 'EMITIDO'")->fetchColumn();
        $solicitudesPend    = (int)$this->db->query("SELECT COUNT(*) FROM solicitudes_reimpresion WHERE estado = 'PENDIENTE'")->fetchColumn();
        $totalUsuarios      = (int)$this->db->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1")->fetchColumn();

        // Últimos registros de auditoría
        $ultimasAuditorias = (new Auditoria())->listar(8, 0);

        require_once __DIR__ . '/../views/admin/dashboard.php';
    }

    /**
     * RF-67: Listado general de usuarios con filtros por rol y estado
     */
    public function usuarios(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        $rolFiltro = filter_input(INPUT_GET, 'rol', FILTER_VALIDATE_INT);
        $sql = "SELECT u.id_usuario, u.ci, u.nombres, u.apellidos, u.correo, u.telefono, u.activo, u.fecha_registro, r.id_rol, r.nombre AS rol_nombre
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol";
        
        if ($rolFiltro) {
            $sql .= " WHERE u.id_rol = :rol";
        }
        $sql .= " ORDER BY u.fecha_registro DESC";

        $stmt = $this->db->prepare($sql);
        if ($rolFiltro) {
            $stmt->execute([':rol' => $rolFiltro]);
        } else {
            $stmt->execute();
        }
        $usuarios = $stmt->fetchAll();
        $roles = $this->db->query("SELECT * FROM roles ORDER BY id_rol ASC")->fetchAll();

        require_once __DIR__ . '/../views/admin/usuarios/index.php';
    }

    /**
     * RF-68: Asignación y cambio de rol de usuario
     */
    public function cambiarRolUsuario(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_usuarios');
            exit();
        }

        $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
        $nuevo_rol  = filter_input(INPUT_POST, 'id_rol', FILTER_VALIDATE_INT);
        $admin      = AuthHelper::obtenerUsuario();

        if ($id_usuario && $nuevo_rol) {
            // Impedir que un administrador se degrade a sí mismo
            if ($id_usuario === (int)$admin['id_usuario']) {
                $_SESSION['error'] = "No puede modificar su propio nivel de privilegios.";
            } else {
                $stmt = $this->db->prepare("UPDATE usuarios SET id_rol = :id_rol WHERE id_usuario = :id_usuario");
                $stmt->execute([':id_rol' => $nuevo_rol, ':id_usuario' => $id_usuario]);

                Auditoria::registrar(
                    (int)$admin['id_usuario'],
                    'CAMBIAR_ROL',
                    'USUARIOS',
                    "Se asignó el rol ID #{$nuevo_rol} al usuario ID #{$id_usuario}"
                );
                $_SESSION['success'] = "Rol de usuario actualizado exitosamente.";
            }
        }
        header('Location: index.php?action=admin_usuarios');
        exit();
    }

    /**
     * RF-69: Activar o desactivar cuentas de usuario
     */
    public function toggleEstadoUsuario(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_usuarios');
            exit();
        }

        $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
        $admin      = AuthHelper::obtenerUsuario();

        if ($id_usuario === (int)$admin['id_usuario']) {
            $_SESSION['error'] = "No puede suspender su propia cuenta de acceso.";
            header('Location: index.php?action=admin_usuarios');
            exit();
        }

        $stmtActual = $this->db->prepare("SELECT activo, correo FROM usuarios WHERE id_usuario = ?");
        $stmtActual->execute([$id_usuario]);
        $usuario = $stmtActual->fetch();

        if ($usuario) {
            $nuevoEstado = $usuario['activo'] ? 0 : 1;
            $stmtUpdate = $this->db->prepare("UPDATE usuarios SET activo = ? WHERE id_usuario = ?");
            $stmtUpdate->execute([$nuevoEstado, $id_usuario]);

            $accionTxt = $nuevoEstado ? "ACTIVAR_CUENTA" : "DESACTIVAR_CUENTA";
            Auditoria::registrar(
                (int)$admin['id_usuario'],
                $accionTxt,
                'USUARIOS',
                "Cuenta {$usuario['correo']} (ID #{$id_usuario}) establecida en estado activo = {$nuevoEstado}"
            );

            $_SESSION['success'] = "El estado de la cuenta fue modificado correctamente.";
        }

        header('Location: index.php?action=admin_usuarios');
        exit();
    }

    /**
     * RF-38 y RF-39: Gestión de Tipos de Eventos
     */
    public function tiposEventos(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);
        $tipos = $this->db->query("SELECT * FROM tipos_evento ORDER BY nombre ASC")->fetchAll();
        require_once __DIR__ . '/../views/admin/tipos_evento/index.php';
    }

    public function guardarTipoEvento(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_tipos_eventos');
            exit();
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $admin  = AuthHelper::obtenerUsuario();

        if (!empty($nombre)) {
            $stmt = $this->db->prepare("INSERT INTO tipos_evento (nombre, activo) VALUES (:nombre, 1) ON DUPLICATE KEY UPDATE activo = 1");
            $stmt->execute([':nombre' => $nombre]);

            Auditoria::registrar(
                (int)$admin['id_usuario'],
                'CREAR_TIPO_EVENTO',
                'EVENTOS',
                "Registro de nuevo tipo de evento: {$nombre}"
            );
            $_SESSION['success'] = "Tipo de evento guardado con éxito.";
        }

        header('Location: index.php?action=admin_tipos_eventos');
        exit();
    }

    /**
     * RF-45: Inscripción administrativa manual por parte del administrador
     */
    public function inscribirManual(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_eventos');
            exit();
        }

        $id_evento  = filter_input(INPUT_POST, 'id_evento', FILTER_VALIDATE_INT);
        $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
        $admin      = AuthHelper::obtenerUsuario();

        try {
            $idInscripcion = $this->inscripcionModel->registrar($id_evento, $id_usuario, 'ADMINISTRATIVO');

            Auditoria::registrar(
                (int)$admin['id_usuario'],
                'INSCRIPCION_MANUAL',
                'INSCRIPCIONES',
                "Inscripción manual forzada para el usuario ID #{$id_usuario} en el evento #{$id_evento} (Inscripción #{$idInscripcion})"
            );

            $_SESSION['success'] = "Participante inscrito administrativamente con éxito.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error en inscripción manual: " . $e->getMessage();
        }

        header("Location: index.php?action=admin_evento_inscritos&id={$id_evento}");
        exit();
    }

    /**
     * RF-47: Modificar estado de inscripción administrativamente
     */
    public function cambiarEstadoInscripcion(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_eventos');
            exit();
        }

        $id_inscripcion = filter_input(INPUT_POST, 'id_inscripcion', FILTER_VALIDATE_INT);
        $nuevo_estado   = trim($_POST['estado'] ?? '');
        $motivo         = trim($_POST['motivo'] ?? 'Resolución administrativa');
        $admin          = AuthHelper::obtenerUsuario();

        if ($this->inscripcionModel->actualizarEstadoAdministrativo($id_inscripcion, $nuevo_estado, $motivo)) {
            Auditoria::registrar(
                (int)$admin['id_usuario'],
                'MODIFICAR_INSCRIPCION',
                'INSCRIPCIONES',
                "Inscripción #{$id_inscripcion} cambiada a estado {$nuevo_estado}. Motivo: {$motivo}"
            );
            $_SESSION['success'] = "Estado de inscripción actualizado.";
        } else {
            $_SESSION['error'] = "No se pudo actualizar el estado de la inscripción.";
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php?action=admin_dashboard';
        header("Location: {$referer}");
        exit();
    }

    /**
     * RF-49 y RF-50: Asistencia registrada o corregida administrativamente
     */
    public function corregirAsistencia(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_eventos');
            exit();
        }

        $id_sesion      = filter_input(INPUT_POST, 'id_sesion', FILTER_VALIDATE_INT);
        $id_inscripcion = filter_input(INPUT_POST, 'id_inscripcion', FILTER_VALIDATE_INT);
        $estado         = trim($_POST['estado'] ?? 'PRESENTE');
        $observacion    = trim($_POST['observacion'] ?? 'Corrección administrativa directa');
        $admin          = AuthHelper::obtenerUsuario();

        if ($this->asistenciaModel->registrarOActualizar($id_sesion, $id_inscripcion, $estado, (int)$admin['id_usuario'], $observacion)) {
            Auditoria::registrar(
                (int)$admin['id_usuario'],
                'CORRECCION_ASISTENCIA',
                'ASISTENCIA',
                "Asistencia modificada en Sesión #{$id_sesion} para Inscripción #{$id_inscripcion} a estado {$estado}"
            );
            $_SESSION['success'] = "Asistencia corregida y porcentaje recalculado.";
        } else {
            $_SESSION['error'] = "Error al corregir asistencia.";
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php?action=admin_dashboard';
        header("Location: {$referer}");
        exit();
    }
}