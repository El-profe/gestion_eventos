<?php
// models/SesionEvento.php
require_once __DIR__ . '/../config/Database.php';

class SesionEvento {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * RF-24: Registra una nueva sesión o clase para el evento
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO sesiones_evento (
                    id_evento, titulo, fecha, hora_inicio, hora_fin, lugar_especifico, estado
                ) VALUES (
                    :id_evento, :titulo, :fecha, :hora_inicio, :hora_fin, :lugar_especifico, 'PROGRAMADA'
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_evento'        => (int)$datos['id_evento'],
            ':titulo'           => trim($datos['titulo']),
            ':fecha'            => $datos['fecha'],
            ':hora_inicio'      => $datos['hora_inicio'],
            ':hora_fin'         => $datos['hora_fin'],
            ':lugar_especifico' => !empty($datos['lugar_especifico']) ? trim($datos['lugar_especifico']) : null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Modifica una sesión existente mientras esté programada
     */
    public function actualizar(int $id_sesion, array $datos): bool {
        $sql = "UPDATE sesiones_evento SET
                    titulo = :titulo,
                    fecha = :fecha,
                    hora_inicio = :hora_inicio,
                    hora_fin = :hora_fin,
                    lugar_especifico = :lugar_especifico
                WHERE id_sesion = :id_sesion";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':titulo'           => trim($datos['titulo']),
            ':fecha'            => $datos['fecha'],
            ':hora_inicio'      => $datos['hora_inicio'],
            ':hora_fin'         => $datos['hora_fin'],
            ':lugar_especifico' => !empty($datos['lugar_especifico']) ? trim($datos['lugar_especifico']) : null,
            ':id_sesion'        => $id_sesion
        ]);
    }

    /**
     * Lista cronológica de sesiones de un evento
     */
    public function listarPorEvento(int $id_evento): array {
        $sql = "SELECT * FROM sesiones_evento 
                WHERE id_evento = :id_evento 
                ORDER BY fecha ASC, hora_inicio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_evento' => $id_evento]);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id_sesion): ?array {
        $sql = "SELECT se.*, e.titulo AS evento_titulo, e.estado AS evento_estado
                FROM sesiones_evento se
                INNER JOIN eventos e ON se.id_evento = e.id_evento
                WHERE se.id_sesion = :id_sesion 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_sesion' => $id_sesion]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Cambia el estado de la sesión ('PROGRAMADA', 'EN_CURSO', 'CONCLUIDA', 'CANCELADA')
     */
    public function cambiarEstado(int $id_sesion, string $nuevoEstado): bool {
        $estadosPermitidos = ['PROGRAMADA', 'EN_CURSO', 'CONCLUIDA', 'CANCELADA'];
        if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
            throw new InvalidArgumentException("Estado de sesión no válido: $nuevoEstado");
        }

        $sql = "UPDATE sesiones_evento SET estado = :estado WHERE id_sesion = :id_sesion";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado'    => $nuevoEstado,
            ':id_sesion' => $id_sesion
        ]);
    }

    public function eliminar(int $id_sesion): bool {
        $sql = "DELETE FROM sesiones_evento WHERE id_sesion = :id_sesion";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id_sesion' => $id_sesion]);
    }

    /**
     * RF-51: Conteo de sesiones válidas para el cálculo de porcentaje de asistencia
     */
    public function contarSesionesValidas(int $id_evento): int {
        $sql = "SELECT COUNT(*) FROM sesiones_evento 
                WHERE id_evento = :id_evento AND estado != 'CANCELADA'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_evento' => $id_evento]);
        return (int)$stmt->fetchColumn();
    }
}