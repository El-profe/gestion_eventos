<?php
// controllers/AuthController.php
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

class AuthController {
    private Usuario $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
    }

    /**
     * RF-02: Procesa autenticación
     */
    public function doLogin(): void {
        AuthHelper::initSession();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=login');
            exit();
        }

        $correo   = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($correo) || empty($password)) {
            $_SESSION['error'] = "Todos los campos son requeridos.";
            header('Location: index.php?action=login');
            exit();
        }

        $usuario = $this->usuarioModel->obtenerPorCorreo($correo);

        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            $_SESSION['error'] = "Credenciales de acceso incorrectas.";
            header('Location: index.php?action=login');
            exit();
        }

        if ((int)$usuario['activo'] !== 1) {
            $_SESSION['error'] = "Su cuenta está inactiva. Contacte a la administración.";
            header('Location: index.php?action=login');
            exit();
        }

        AuthHelper::autenticar($usuario);

        // En controllers/AuthController.php (dentro de doLogin):
        match ($usuario['rol_nombre']) {
            'ADMINISTRADOR' => header('Location: index.php?action=admin_dashboard'),
            'EXPOSITOR'     => header('Location: index.php?action=expositor_eventos'),
            default         => header('Location: index.php?action=participante_dashboard')
        };
        exit();
    }

    /**
     * RF-01: Procesa el registro de nuevos participantes
     */
    public function doRegistro(): void {
        AuthHelper::initSession();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=registro');
            exit();
        }

        $ci        = trim($_POST['ci'] ?? '');
        $nombres   = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $correo    = trim($_POST['correo'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $password  = $_POST['password'] ?? '';

        if (empty($ci) || empty($nombres) || empty($apellidos) || empty($correo) || empty($password)) {
            $_SESSION['error'] = "Debe llenar todos los campos obligatorios.";
            header('Location: index.php?action=registro');
            exit();
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "El formato de correo no es válido.";
            header('Location: index.php?action=registro');
            exit();
        }

        if ($this->usuarioModel->existeCorreoOCI($correo, $ci)) {
            $_SESSION['error'] = "El número de CI o el correo ya se encuentran registrados.";
            header('Location: index.php?action=registro');
            exit();
        }

        $resultado = $this->usuarioModel->registrar([
            'ci'        => $ci,
            'nombres'   => $nombres,
            'apellidos' => $apellidos,
            'correo'    => $correo,
            'telefono'  => $telefono,
            'password'  => $password,
            'id_rol'    => 3 // PARTICIPANTE
        ]);

        if ($resultado) {
            $_SESSION['success'] = "Registro completado con éxito. Puede iniciar sesión.";
            header('Location: index.php?action=login');
        } else {
            $_SESSION['error'] = "Ocurrió un error al crear la cuenta.";
            header('Location: index.php?action=registro');
        }
        exit();
    }

    /**
     * RF-03: Cierre de sesión seguro
     */
    public function logout(): void {
        AuthHelper::cerrarSesion();
        header('Location: index.php?action=login');
        exit();
    }

    /**
     * RF-04: Generar token de recuperación
     */
    public function doRecuperar(): void {
        AuthHelper::initSession();
        $correo = trim($_POST['correo'] ?? '');

        if (!empty($correo)) {
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $this->usuarioModel->guardarTokenRecuperacion($correo, $token, $expira);
            // Simulación de envío: en producción se enviaría por correo electrónico
            $_SESSION['success'] = "Si el correo coincide con nuestros registros, recibirá las instrucciones para reestablecer su acceso.";
        }
        header('Location: index.php?action=recuperar');
        exit();
    }
}