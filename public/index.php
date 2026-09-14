<?php
// gestion_eventos/public/index.php
require_once __DIR__ . '/../helpers/AuthHelper.php';
AuthHelper::initSession();

$action = $_GET['action'] ?? 'catalogo';

// 1. Rutas públicas (sin requerir inicio de sesión)
$rutas_publicas = [
    'catalogo',
    'detalle_evento',
    'login',
    'do_login',
    'registro',
    'do_registro',
    'recuperar',
    'do_recuperar',
    'verificar_certificado',
    'imprimir_certificado'
];

// 2. Control de acceso perimetral
if (!in_array($action, $rutas_publicas, true) && !AuthHelper::estaAutenticado()) {
    header('Location: index.php?action=login');
    exit();
}

// 3. Despachador de acciones (Lazy Loading de controladores)
switch ($action) {

    // ==========================================
    // PORTAL PÚBLICO Y CATÁLOGO (RF-07 a RF-09)
    // ==========================================
    case 'catalogo':
        require_once __DIR__ . '/../controllers/EventoController.php';
        (new EventoController())->catalogo();
        break;

    case 'detalle_evento':
        require_once __DIR__ . '/../controllers/EventoController.php';
        (new EventoController())->detalle();
        break;

    case 'verificar_certificado':
        require_once __DIR__ . '/../controllers/CertificadoController.php';
        (new CertificadoController())->verificarPublico();
        break;

    case 'imprimir_certificado':
        require_once __DIR__ . '/../controllers/CertificadoController.php';
        (new CertificadoController())->imprimir();
        break;

    // ==========================================
    // AUTENTICACIÓN Y CUENTAS (RF-01 a RF-06)
    // ==========================================
    case 'login':
        require_once __DIR__ . '/../views/auth/login.php';
        break;

    case 'do_login':
        require_once __DIR__ . '/../controllers/AuthController.php';
        (new AuthController())->doLogin();
        break;

    case 'registro':
        require_once __DIR__ . '/../views/auth/registro.php';
        break;

    case 'do_registro':
        require_once __DIR__ . '/../controllers/AuthController.php';
        (new AuthController())->doRegistro();
        break;

    case 'recuperar':
        require_once __DIR__ . '/../views/auth/recuperar.php';
        break;

    case 'do_recuperar':
        require_once __DIR__ . '/../controllers/AuthController.php';
        (new AuthController())->doRecuperar();
        break;

    case 'logout':
        require_once __DIR__ . '/../controllers/AuthController.php';
        (new AuthController())->logout();
        break;

    case 'perfil':
        require_once __DIR__ . '/../views/usuario/perfil.php';
        break;

    case 'actualizar_perfil':
        AuthHelper::requerirRol(['ADMINISTRADOR', 'EXPOSITOR', 'PARTICIPANTE']);
        $usr = AuthHelper::obtenerUsuario();
        require_once __DIR__ . '/../models/Usuario.php';
        (new Usuario())->actualizarPerfil((int)$usr['id_usuario'], $_POST);
        $_SESSION['success'] = "Datos personales actualizados correctamente.";
        header('Location: index.php?action=perfil');
        exit();

    case 'actualizar_password':
        AuthHelper::requerirRol(['ADMINISTRADOR', 'EXPOSITOR', 'PARTICIPANTE']);
        $usr = AuthHelper::obtenerUsuario();
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT password_hash FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([(int)$usr['id_usuario']]);
        $hashActual = $stmt->fetchColumn();

        require_once __DIR__ . '/../models/Usuario.php';
        if (password_verify($_POST['password_actual'] ?? '', $hashActual)) {
            (new Usuario())->reestablecerPassword((int)$usr['id_usuario'], $_POST['password_nueva'] ?? '');
            $_SESSION['success'] = "Contraseña actualizada con éxito.";
        } else {
            $_SESSION['error'] = "La contraseña actual no es correcta.";
        }
        header('Location: index.php?action=perfil');
        exit();

    // ==========================================
    // PARTICIPANTE: INSCRIPCIONES Y CERTIFICADOS (RF-10 a RF-19)
    // ==========================================
    case 'inscribirse_evento':
        require_once __DIR__ . '/../controllers/EventoController.php';
        (new EventoController())->inscribirse();
        break;

    case 'cancelar_inscripcion':
        require_once __DIR__ . '/../controllers/EventoController.php';
        (new EventoController())->cancelarInscripcion();
        break;

    case 'mis_inscripciones':
        AuthHelper::requerirRol(['PARTICIPANTE', 'ADMINISTRADOR']);
        $usuario = AuthHelper::obtenerUsuario();
        require_once __DIR__ . '/../models/Inscripcion.php';
        $inscripciones = (new Inscripcion())->listarPorUsuario((int)$usuario['id_usuario']);
        require_once __DIR__ . '/../views/participante/mis_inscripciones.php';
        break;

    case 'mis_certificados':
        require_once __DIR__ . '/../controllers/CertificadoController.php';
        (new CertificadoController())->misCertificados();
        break;

    case 'solicitar_reimpresion':
        require_once __DIR__ . '/../controllers/CertificadoController.php';
        (new CertificadoController())->procesarSolicitudReimpresion();
        break;

    // ==========================================
    // EXPOSITOR: GESTIÓN Y ASISTENCIA (RF-20 a RF-26)
    // ==========================================
    case 'expositor_eventos':
        require_once __DIR__ . '/../controllers/ExpositorController.php';
        (new ExpositorController())->misEventos();
        break;

    case 'expositor_sesiones':
        require_once __DIR__ . '/../controllers/ExpositorController.php';
        (new ExpositorController())->sesiones();
        break;

    case 'expositor_asistencia':
        require_once __DIR__ . '/../controllers/ExpositorController.php';
        (new ExpositorController())->planillaAsistencia();
        break;

    case 'api_guardar_asistencia':
        require_once __DIR__ . '/../controllers/ExpositorController.php';
        (new ExpositorController())->guardarAsistenciaApi();
        break;

    // ==========================================
    // ADMINISTRACIÓN CENTRAL (RF-29 a RF-70)
    // ==========================================
    case 'admin_dashboard':
        require_once __DIR__ . '/../controllers/AdminController.php';
        (new AdminController())->dashboard();
        break;

    case 'admin_eventos':
        AuthHelper::requerirRol(['ADMINISTRADOR']);
        require_once __DIR__ . '/../models/Evento.php';
        $eventoModel = new Evento();
        $eventos = $eventoModel->listarPublicos();
        $db = Database::getConnection();
        $tipos = $db->query("SELECT * FROM tipos_evento WHERE activo = 1")->fetchAll();
        $categorias = $db->query("SELECT * FROM categorias WHERE activo = 1")->fetchAll();
        $usuarios_participantes = $db->query("SELECT id_usuario, nombres, apellidos, ci FROM usuarios WHERE activo = 1 ORDER BY apellidos ASC")->fetchAll();
        require_once __DIR__ . '/../views/admin/eventos/index.php';
        break;

    case 'admin_eventos_guardar':
        require_once __DIR__ . '/../controllers/EventoController.php';
        (new EventoController())->adminGuardarEvento();
        break;

    case 'admin_evento_cambiar_estado':
        require_once __DIR__ . '/../controllers/EventoController.php';
        (new EventoController())->adminCambiarEstado();
        break;

    case 'admin_inscribir_manual':
        require_once __DIR__ . '/../controllers/AdminController.php';
        (new AdminController())->inscribirManual();
        break;

    case 'admin_usuarios':
        require_once __DIR__ . '/../controllers/AdminController.php';
        (new AdminController())->usuarios();
        break;

    case 'admin_usuario_cambiar_rol':
        require_once __DIR__ . '/../controllers/AdminController.php';
        (new AdminController())->cambiarRolUsuario();
        break;

    case 'admin_usuario_toggle_estado':
        require_once __DIR__ . '/../controllers/AdminController.php';
        (new AdminController())->toggleEstadoUsuario();
        break;

    case 'admin_tipos_eventos':
        require_once __DIR__ . '/../controllers/AdminController.php';
        (new AdminController())->tiposEventos();
        break;

    case 'admin_tipo_evento_guardar':
        require_once __DIR__ . '/../controllers/AdminController.php';
        (new AdminController())->guardarTipoEvento();
        break;

    // ==========================================
    // REPORTES Y EXPORTACIONES (RF-71 a RF-76)
    // ==========================================
    case 'admin_reportes':
        AuthHelper::requerirRol(['ADMINISTRADOR']);
        require_once __DIR__ . '/../views/admin/reportes/index.php';
        break;

    case 'admin_exportar_reporte':
        require_once __DIR__ . '/../controllers/ReporteController.php';
        (new ReporteController())->exportar();
        break;

    default:
        http_response_code(404);
        require_once __DIR__ . '/../views/layouts/header.php';
        echo '<div class="container py-5 text-center">
                <h1 class="display-4 fw-bold text-uab-azul">404</h1>
                <p class="lead text-muted">La ruta solicitada no existe o fue movida.</p>
                <a href="index.php?action=catalogo" class="btn btn-uab-azul mt-3">Volver al inicio</a>
              </div>';
        require_once __DIR__ . '/../views/layouts/footer.php';
        break;

    case 'participante_dashboard':
        require_once __DIR__ . '/../controllers/EventoController.php';
        (new EventoController())->participanteDashboard();
        break;
}