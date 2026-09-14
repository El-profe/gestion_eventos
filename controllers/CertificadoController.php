<?php
// controllers/CertificadoController.php
require_once __DIR__ . '/../models/Certificado.php';
require_once __DIR__ . '/../models/SolicitudReimpresion.php';
require_once __DIR__ . '/../models/Inscripcion.php';
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../models/Auditoria.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/QrHelper.php';

class CertificadoController {
    private Certificado $certificadoModel;
    private SolicitudReimpresion $reimpresionModel;
    private Inscripcion $inscripcionModel;
    private Evento $eventoModel;

    public function __construct() {
        $this->certificadoModel = new Certificado();
        $this->reimpresionModel = new SolicitudReimpresion();
        $this->inscripcionModel = new Inscripcion();
        $this->eventoModel = new Evento();
    }

    /**
     * RF-15 y RF-16: Panel del participante para ver sus certificados obtenidos
     */
    public function misCertificados(): void {
        AuthHelper::requerirRol(['PARTICIPANTE', 'ADMINISTRADOR']);
        $usuario = AuthHelper::obtenerUsuario();

        $certificados = $this->certificadoModel->listarPorUsuario((int)$usuario['id_usuario']);
        $solicitudes = $this->reimpresionModel->listarPorUsuario((int)$usuario['id_usuario']);

        require_once __DIR__ . '/../views/participante/mis_certificados.php';
    }

    /**
     * RF-17 y RF-18: El participante solicita la reimpresión indicando motivo
     */
    public function procesarSolicitudReimpresion(): void {
        AuthHelper::requerirRol(['PARTICIPANTE', 'ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=mis_certificados');
            exit();
        }

        $usuario = AuthHelper::obtenerUsuario();
        $id_certificado = filter_input(INPUT_POST, 'id_certificado', FILTER_VALIDATE_INT);
        $motivo = trim($_POST['motivo'] ?? '');

        if (!$id_certificado || empty($motivo)) {
            $_SESSION['error'] = "Debe indicar un motivo justificado para la reimpresión.";
            header('Location: index.php?action=mis_certificados');
            exit();
        }

        try {
            $this->reimpresionModel->registrarSolicitud($id_certificado, (int)$usuario['id_usuario'], $motivo);
            $_SESSION['success'] = "Solicitud de reimpresión enviada a revisión administrativa.";
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: index.php?action=mis_certificados');
        exit();
    }

    /**
     * RF-52 y RF-53: Emisión individual por parte del Administrador
     */
    public function adminEmitirIndividual(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_eventos');
            exit();
        }

        $id_inscripcion = filter_input(INPUT_POST, 'id_inscripcion', FILTER_VALIDATE_INT);
        $admin = AuthHelper::obtenerUsuario();

        try {
            $idCert = $this->certificadoModel->generarIndividual($id_inscripcion, (int)$admin['id_usuario']);

            Auditoria::registrar(
                (int)$admin['id_usuario'],
                'EMITIR_CERTIFICADO',
                'CERTIFICADOS',
                "Se generó el certificado ID #{$idCert} para la inscripción #{$id_inscripcion}"
            );

            $_SESSION['success'] = "Certificado emitido exitosamente.";
        } catch (Exception $e) {
            $_SESSION['error'] = "No se pudo emitir el certificado: " . $e->getMessage();
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php?action=admin_eventos';
        header("Location: {$referer}");
        exit();
    }

    /**
     * RF-55: Emisión masiva para todos los alumnos aprobados del evento
     */
    public function adminEmitirMasivo(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_eventos');
            exit();
        }

        $id_evento = filter_input(INPUT_POST, 'id_evento', FILTER_VALIDATE_INT);
        $admin = AuthHelper::obtenerUsuario();

        try {
            $resultado = $this->certificadoModel->generarMasivo($id_evento, (int)$admin['id_usuario']);

            Auditoria::registrar(
                (int)$admin['id_usuario'],
                'EMISION_MASIVA_CERTIFICADOS',
                'CERTIFICADOS',
                "Emisión masiva en evento #{$id_evento}: {$resultado['total_emitidos']} emitidos de {$resultado['total_procesados']} procesados."
            );

            $_SESSION['success'] = "Proceso concluido: {$resultado['total_emitidos']} certificado(s) generado(s).";
        } catch (Exception $e) {
            $_SESSION['error'] = "Fallo en la emisión masiva: " . $e->getMessage();
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php?action=admin_eventos';
        header("Location: {$referer}");
        exit();
    }

    /**
     * RF-59: Anulación administrativa de un certificado
     */
    public function adminAnular(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_certificados');
            exit();
        }

        $id_certificado = filter_input(INPUT_POST, 'id_certificado', FILTER_VALIDATE_INT);
        $motivo = trim($_POST['motivo_anulacion'] ?? '');
        $admin = AuthHelper::obtenerUsuario();

        if (!$id_certificado || empty($motivo)) {
            $_SESSION['error'] = "Debe indicar el motivo formal de la anulación.";
            header('Location: index.php?action=admin_certificados');
            exit();
        }

        if ($this->certificadoModel->anular($id_certificado, (int)$admin['id_usuario'], $motivo)) {
            Auditoria::registrar(
                (int)$admin['id_usuario'],
                'ANULAR_CERTIFICADO',
                'CERTIFICADOS',
                "Certificado ID #{$id_certificado} anulado. Motivo: {$motivo}"
            );
            $_SESSION['success'] = "El certificado fue marcado como ANULADO en el historial.";
        } else {
            $_SESSION['error'] = "No fue posible anular el certificado seleccionado.";
        }

        header('Location: index.php?action=admin_certificados');
        exit();
    }

    /**
     * RF-57, RF-58 y RF-64: Vista formal imprimible / guardado en PDF
     */
    public function imprimir(): void {
        $codigo = trim($_GET['codigo'] ?? '');
        if (empty($codigo)) {
            die("Código de certificado no proporcionado.");
        }

        $certificado = $this->certificadoModel->obtenerPorCodigo($codigo);
        if (!$certificado) {
            http_response_code(404);
            die("El certificado solicitado no existe en el sistema.");
        }

        // Obtener configuración institucional
        $db = Database::getConnection();
        $config = $db->query("SELECT * FROM configuracion_institucion WHERE id_configuracion = 1 LIMIT 1")->fetch();

        $urlValidacion = QrHelper::generarUrlVerificacion($certificado['codigo_unico']);
        $qrImagen = QrHelper::generarImagenQr($urlValidacion, 160);

        // Renderizado del formato diploma apaisado
        require_once __DIR__ . '/../views/publico/imprimir_certificado.php';
    }

    /**
     * RF-16 y Validación Pública por QR (Acceso público sin sesión)
     */
    public function verificarPublico(): void {
        $codigo = trim($_GET['codigo'] ?? '');
        $certificado = null;

        if (!empty($codigo)) {
            $certificado = $this->certificadoModel->obtenerPorCodigo($codigo);
        }

        require_once __DIR__ . '/../views/publico/verificar.php';
    }

    /**
     * RF-60, RF-61, RF-62 y RF-63: Resolver solicitudes de reimpresión (Admin)
     */
    public function adminResolverReimpresion(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_reimpresiones');
            exit();
        }

        $id_solicitud = filter_input(INPUT_POST, 'id_solicitud', FILTER_VALIDATE_INT);
        $decision = trim($_POST['decision'] ?? ''); // 'APROBADA' o 'RECHAZADA'
        $motivo_rechazo = trim($_POST['motivo_rechazo'] ?? '');
        $admin = AuthHelper::obtenerUsuario();

        try {
            $this->reimpresionModel->resolver($id_solicitud, (int)$admin['id_usuario'], $decision, $motivo_rechazo);

            Auditoria::registrar(
                (int)$admin['id_usuario'],
                'RESOLVER_REIMPRESION',
                'CERTIFICADOS',
                "Solicitud #{$id_solicitud} resuelta como {$decision}."
            );

            $_SESSION['success'] = "Solicitud marcada como {$decision}.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al resolver solicitud: " . $e->getMessage();
        }

        header('Location: index.php?action=admin_reimpresiones');
        exit();
    }

    /**
     * RF-66: Registrar la entrega física del certificado reimpreso (Admin)
     */
    public function adminEntregarReimpresion(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=admin_reimpresiones');
            exit();
        }

        $id_solicitud = filter_input(INPUT_POST, 'id_solicitud', FILTER_VALIDATE_INT);
        $admin = AuthHelper::obtenerUsuario();

        if ($this->reimpresionModel->registrarEntrega($id_solicitud, (int)$admin['id_usuario'])) {
            Auditoria::registrar(
                (int)$admin['id_usuario'],
                'ENTREGA_REIMPRESION',
                'CERTIFICADOS',
                "Se registró la entrega material de la reimpresión #{$id_solicitud}."
            );
            $_SESSION['success'] = "Acta de entrega registrada con éxito.";
        } else {
            $_SESSION['error'] = "No se pudo asentar la entrega del documento.";
        }

        header('Location: index.php?action=admin_reimpresiones');
        exit();
    }
}