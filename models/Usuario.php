<?php
// models/Usuario.php
require_once __DIR__ . '/../config/Database.php';

class Usuario {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * RF-02: Obtiene usuario con su rol para autenticación
     */
    public function obtenerPorCorreo(string $correo): ?array {
        $sql = "SELECT u.*, r.nombre AS rol_nombre 
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.correo = :correo 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':correo' => trim($correo)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function obtenerPorId(int $id_usuario): ?array {
        $sql = "SELECT u.id_usuario, u.ci, u.nombres, u.apellidos, u.correo, u.telefono, u.id_rol, u.activo, r.nombre AS rol_nombre
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.id_usuario = :id_usuario LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * RF-01: Registro de nuevos participantes
     */
    public function registrar(array $datos): bool {
        $sql = "INSERT INTO usuarios (ci, nombres, apellidos, correo, telefono, password_hash, id_rol, activo)
                VALUES (:ci, :nombres, :apellidos, :correo, :telefono, :password_hash, :id_rol, 1)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':ci'            => trim($datos['ci']),
            ':nombres'       => trim($datos['nombres']),
            ':apellidos'     => trim($datos['apellidos']),
            ':correo'        => strtolower(trim($datos['correo'])),
            ':telefono'      => trim($datos['telefono'] ?? ''),
            ':password_hash' => password_hash($datos['password'], PASSWORD_BCRYPT),
            ':id_rol'        => $datos['id_rol'] ?? 3 // 3 = PARTICIPANTE
        ]);
    }

    /**
     * RF-05: Modificación de datos personales
     */
    public function actualizarPerfil(int $id_usuario, array $datos): bool {
        $sql = "UPDATE usuarios 
                SET nombres = :nombres, apellidos = :apellidos, telefono = :telefono
                WHERE id_usuario = :id_usuario";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombres'    => trim($datos['nombres']),
            ':apellidos'  => trim($datos['apellidos']),
            ':telefono'   => trim($datos['telefono'] ?? ''),
            ':id_usuario' => $id_usuario
        ]);
    }

    /**
     * RF-04: Establece token temporal para recuperación de contraseña
     */
    public function guardarTokenRecuperacion(string $correo, string $token, string $expiracion): bool {
        $sql = "UPDATE usuarios 
                SET token_recuperacion = :token, token_expiracion = :expiracion 
                WHERE correo = :correo AND activo = 1";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':token'      => $token,
            ':expiracion' => $expiracion,
            ':correo'     => $correo
        ]);
    }

    public function validarToken(string $token): ?array {
        $sql = "SELECT id_usuario FROM usuarios 
                WHERE token_recuperacion = :token 
                  AND token_expiracion > NOW() 
                  AND activo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function reestablecerPassword(int $id_usuario, string $nuevaPassword): bool {
        $sql = "UPDATE usuarios 
                SET password_hash = :password_hash, token_recuperacion = NULL, token_expiracion = NULL 
                WHERE id_usuario = :id_usuario";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':password_hash' => password_hash($nuevaPassword, PASSWORD_BCRYPT),
            ':id_usuario'    => $id_usuario
        ]);
    }

    public function existeCorreoOCI(string $correo, string $ci, ?int $excluirId = null): bool {
        $sql = "SELECT id_usuario FROM usuarios WHERE (correo = :correo OR ci = :ci)";
        $params = [':correo' => $correo, ':ci' => $ci];
        
        if ($excluirId) {
            $sql .= " AND id_usuario != :id_usuario";
            $params[':id_usuario'] = $excluirId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }
}