<?php
// models/Auditoria.php
require_once __DIR__ . '/../config/Database.php';

class Auditoria {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * RF-70: Registra una acción administrativa relevante
     */
    public static function registrar(int $id_usuario, string $accion, string $modulo, ?string $detalles = null): bool {
        try {
            $db = Database::getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $sql = "INSERT INTO auditoria_actividad (id_usuario, accion, modulo, ip_origen, detalles, fecha_registro)
                    VALUES (:id_usuario, :accion, :modulo, :ip, :detalles, NOW())";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':id_usuario' => $id_usuario,
                ':accion'     => strtoupper(trim($accion)),
                ':modulo'     => strtoupper(trim($modulo)),
                ':ip'         => $ip,
                ':detalles'   => $detalles
            ]);
        } catch (Exception $e) {
            error_log("Error al asentar auditoría: " . $e->getMessage());
            return false;
        }
    }

    /**
     * RF-70: Consulta de bitácora para el panel del administrador
     */
    public function listar(int $limite = 50, int $offset = 0): array {
        $sql = "SELECT 
                    a.*,
                    u.ci, u.nombres, u.apellidos, u.correo
                FROM auditoria_actividad a
                INNER JOIN usuarios u ON a.id_usuario = u.id_usuario
                ORDER BY a.fecha_registro DESC
                LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}