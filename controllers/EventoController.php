<?php
// controllers/EventoController.php
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../models/SesionEvento.php';
require_once __DIR__ . '/../models/Inscripcion.php';
require_once __DIR__ . '/../models/Material.php';
require_once __DIR__ . '/../models/Auditoria.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

class EventoController {
    private Evento $eventoModel;
    private SesionEvento $sesionModel;
    private Inscripcion $inscripcionModel;
    private Material $materialModel;

    public function __construct() {
        $this->eventoModel = new Evento();
        $this->sesionModel = new SesionEvento();
        $this->inscripcionModel = new Inscripcion();
        $this->materialModel = new Material();
    }

    /**
     * RF-07 y RF-08: Muestra el catálogo con filtros de búsqueda
     */
    public function catalogo(): void {
        AuthHelper::initSession();

        $filtros = [
            'buscar'         => $_GET['buscar'] ?? '',
            'id_tipo_evento' => $_GET['id_tipo_evento'] ?? '',
            'id_categoria'   => $_GET['id_categoria'] ?? '',
            'fecha'          => $_GET['fecha'] ?? ''
        ];

        $eventos = $this->eventoModel->listarPublicos($filtros);

        // Catálogos para los selects del filtro
        $db = Database::getConnection();
        $tipos = $db->query("SELECT * FROM tipos_evento WHERE activo = 1")->fetchAll();
        $categorias = $db->query("SELECT * FROM categorias WHERE activo = 1")->fetchAll();

        require_once __DIR__ . '/../views/publico/catalogo.php';
    }

    /**
     * RF-09: Ficha técnica detallada del evento
     */
    public function detalle(): void {
        AuthHelper::initSession();

        $id_evento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id_evento) {
            header('Location: index.php?action=catalogo');
            exit();
        }

        $evento = $this->eventoModel->obtenerDetalle($id_evento);
        if (!$evento) {
            http_response_code(404);
            die("Evento no encontrado.");
        }

        $sesiones = $this->sesionModel->listarPorEvento($id_evento);
        $materiales = $this->materialModel->listarPorEvento($id_evento);
        $disponibilidad = $this->eventoModel->verificarDisponibilidadInscripcion($id_evento);

        // Verificar si el usuario en sesión ya se encuentra inscrito
        $estaInscrito = false;
        if (AuthHelper::estaAutenticado()) {
            $usuario = AuthHelper::obtenerUsuario();
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT id_inscripcion FROM inscripciones WHERE id_evento = ? AND id_usuario = ? AND estado = 'INSCRITO'");
            $stmt->execute([$id_evento, $usuario['id_usuario']]);
            $estaInscrito = (bool)$stmt->fetch();
        }

        require_once __DIR__ . '/../views/publico/detalle_evento.php';
    }

    /**
     * RF-10 y RF-11: Procesa la inscripción del participante
     */
    public function inscribirse(): void {
        AuthHelper::requerirRol(['PARTICIPANTE', 'ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=catalogo');
            exit();
        }

        $id_evento = filter_input(INPUT_POST, 'id_evento', FILTER_VALIDATE_INT);
        $usuario = AuthHelper::obtenerUsuario();

        if (!$id_evento) {
            $_SESSION['error'] = "Identificador de evento inválido.";
            header('Location: index.php?action=catalogo');
            exit();
        }

        try {
            $idInscripcion = $this->inscripcionModel->registrar($id_evento, (int)$usuario['id_usuario'], 'WEB_PARTICIPANTE');
            $_SESSION['success'] = "¡Inscripción confirmada exitosamente!";
            header("Location: index.php?action=mis_inscripciones");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: index.php?action=detalle_evento&id={$id_evento}");
            exit();
        }
    }

    /**
     * RF-12: Procesa la cancelación voluntaria
     */
    public function cancelarInscripcion(): void {
        AuthHelper::requerirRol(['PARTICIPANTE', 'ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=mis_inscripciones');
            exit();
        }

        $id_inscripcion = filter_input(INPUT_POST, 'id_inscripcion', FILTER_VALIDATE_INT);
        $motivo = trim($_POST['motivo'] ?? 'Cancelado voluntariamente por el usuario');
        $usuario = AuthHelper::obtenerUsuario();

        if ($this->inscripcionModel->cancelar($id_inscripcion, (int)$usuario['id_usuario'], $motivo)) {
            $_SESSION['success'] = "Inscripción cancelada correctamente.";
        } else {
            $_SESSION['error'] = "No se pudo cancelar la inscripción solicitada.";
        }

        header('Location: index.php?action=mis_inscripciones');
        exit();
    }

    /**
     * RF-29: Guardado de nuevo evento (Administrador)
     */
    public function adminGuardarEvento(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_eventos');
            exit();
        }

        $admin = AuthHelper::obtenerUsuario();

        try {
            $datos = [
                'codigo'                        => trim($_POST['codigo']),
                'titulo'                        => trim($_POST['titulo']),
                'descripcion'                  => trim($_POST['descripcion']),
                'id_tipo_evento'                => (int)$_POST['id_tipo_evento'],
                'id_categoria'                  => (int)$_POST['id_categoria'],
                'modalidad'                     => $_POST['modalidad'],
                'lugar'                         => $_POST['lugar'] ?? null,
                'enlace_virtual'                => $_POST['enlace_virtual'] ?? null,
                'cupo_maximo'                   => (int)($_POST['cupo_maximo'] ?? 0),
                'fecha_inicio_inscripcion'      => $_POST['fecha_inicio_inscripcion'],
                'fecha_fin_inscripcion'        => $_POST['fecha_fin_inscripcion'],
                'fecha_inicio'                  => $_POST['fecha_inicio'],
                'fecha_fin'                     => $_POST['fecha_fin'],
                'emite_certificado'             => isset($_POST['emite_certificado']) ? 1 : 0,
                'horas_academicas'              => (int)($_POST['horas_academicas'] ?? 0),
                'porcentaje_asistencia_minimo' => (float)($_POST['porcentaje_asistencia_minimo'] ?? 80.00),
                'nota_minima_aprobacion'        => (float)($_POST['nota_minima_aprobacion'] ?? 0.00),
                'id_usuario_creador'            => (int)$admin['id_usuario']
            ];

            $idEvento = $this->eventoModel->crear($datos);

            // Registro de Auditoría (RF-70)
            Auditoria::registrar(
                (int)$admin['id_usuario'],
                'CREAR_EVENTO',
                'EVENTOS',
                "Se creó el evento ID #{$idEvento} con código {$datos['codigo']}"
            );

            $_SESSION['success'] = "Evento registrado exitosamente en estado BORRADOR.";
            header("Location: index.php?action=admin_evento_editar&id={$idEvento}");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al crear el evento: " . $e->getMessage();
            header('Location: index.php?action=admin_eventos');
            exit();
        }
    }

    /**
     * RF-31 a RF-34: Transiciones de estado administrativas
     */
    public function adminCambiarEstado(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_eventos');
            exit();
        }

        $id_evento    = filter_input(INPUT_POST, 'id_evento', FILTER_VALIDATE_INT);
        $nuevo_estado = trim($_POST['estado'] ?? '');
        $admin        = AuthHelper::obtenerUsuario();

        try {
            $this->eventoModel->cambiarEstado($id_evento, $nuevo_estado);

            Auditoria::registrar(
                (int)$admin['id_usuario'],
                'CAMBIO_ESTADO_EVENTO',
                'EVENTOS',
                "El evento ID #{$id_evento} cambió su estado a {$nuevo_estado}"
            );

            $_SESSION['success'] = "Estado del evento actualizado a {$nuevo_estado}.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al actualizar estado: " . $e->getMessage();
        }

        header("Location: index.php?action=admin_eventos");
        exit();
    }


    /**
     * RF-07, RF-08, RF-10: Dashboard principal del participante con eventos abiertos
     */
    public function participanteDashboard(): void {
        AuthHelper::requerirRol(['PARTICIPANTE', 'ADMINISTRADOR']);
        $usuario = AuthHelper::obtenerUsuario();
        $db = Database::getConnection();

        // 1. Obtener eventos PUBLICADOS con inscripciones abiertas en fecha y hora actual
        $sqlAbiertos = "SELECT 
                            e.*,
                            te.nombre AS tipo_evento_nombre,
                            c.nombre AS categoria_nombre,
                            (SELECT COUNT(*) FROM inscripciones i WHERE i.id_evento = e.id_evento AND i.estado = 'INSCRITO') AS total_inscritos
                        FROM eventos e
                        INNER JOIN tipos_evento te ON e.id_tipo_evento = te.id_tipo_evento
                        INNER JOIN categorias c ON e.id_categoria = c.id_categoria
                        WHERE e.estado = 'PUBLICADO'
                          AND NOW() BETWEEN e.fecha_inicio_inscripcion AND e.fecha_fin_inscripcion
                        ORDER BY e.fecha_inicio ASC";
        $stmtAbiertos = $db->query($sqlAbiertos);
        $eventosAbiertos = $stmtAbiertos->fetchAll();

        // 2. Obtener IDs de eventos en los que el usuario ya está registrado
        $stmtMisEvt = $db->prepare("SELECT id_evento FROM inscripciones WHERE id_usuario = :id AND estado != 'CANCELADO'");
        $stmtMisEvt->execute([':id' => $usuario['id_usuario']]);
        $misEventosIds = $stmtMisEvt->fetchAll(PDO::FETCH_COLUMN);

        // 3. Métricas rápidas del usuario para el encabezado
        $stmtTotalIns = $db->prepare("SELECT COUNT(*) FROM inscripciones WHERE id_usuario = :id AND estado = 'INSCRITO'");
        $stmtTotalIns->execute([':id' => $usuario['id_usuario']]);
        $totalMisInscripciones = (int)$stmtTotalIns->fetchColumn();

        $stmtTotalCert = $db->prepare("SELECT COUNT(*) FROM certificados WHERE id_usuario = :id AND estado = 'EMITIDO'");
        $stmtTotalCert->execute([':id' => $usuario['id_usuario']]);
        $totalMisCertificados = (int)$stmtTotalCert->fetchColumn();

        // 4. Catálogos para filtros rápidos
        $tipos = $db->query("SELECT * FROM tipos_evento WHERE activo = 1")->fetchAll();

        require_once __DIR__ . '/../views/participante/dashboard.php';
    }
}