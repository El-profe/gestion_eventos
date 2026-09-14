<?php
// helpers/AuthHelper.php

class AuthHelper {
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function estaAutenticado(): bool {
        self::initSession();
        return !empty($_SESSION['usuario_id']) && !empty($_SESSION['autenticado']);
    }

    public static function obtenerUsuario(): ?array {
        self::initSession();
        return self::estaAutenticado() ? $_SESSION['usuario'] : null;
    }

    public static function obtenerRol(): ?string {
        self::initSession();
        return $_SESSION['usuario']['rol_nombre'] ?? null;
    }

    /**
     * Valida si el usuario en sesión tiene uno de los roles permitidos (RF-06)
     */
    public static function requerirRol(array $rolesPermitidos): void {
        self::initSession();
        if (!self::estaAutenticado()) {
            header('Location: index.php?action=login');
            exit();
        }

        $rolActual = self::obtenerRol();
        if (!in_array($rolActual, $rolesPermitidos, true)) {
            http_response_code(403);
            die("Acceso denegado: No cuenta con los privilegios requeridos para esta sección.");
        }
    }

    /**
     * Asienta la sesión con regeneración de ID para mitigar Session Fixation
     */
    public static function autenticar(array $usuario): void {
        self::initSession();
        session_regenerate_id(true);
        $_SESSION['usuario_id']   = $usuario['id_usuario'];
        $_SESSION['autenticado']  = true;
        $_SESSION['usuario']      = [
            'id_usuario' => $usuario['id_usuario'],
            'ci'         => $usuario['ci'],
            'nombres'    => $usuario['nombres'],
            'apellidos'  => $usuario['apellidos'],
            'correo'     => $usuario['correo'],
            'id_rol'     => $usuario['id_rol'],
            'rol_nombre' => $usuario['rol_nombre']
        ];
    }

    public static function cerrarSesion(): void {
        self::initSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}