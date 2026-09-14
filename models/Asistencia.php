<?php
// models/Asistencia.php
require_once __DIR__ . '/../config/Database.php';

class Asistencia {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * RF-23, RF-24 y RF-25: Asienta o modifica la asistencia individual por sesión
     */
    public function registrarOActualizar(int $id_sesion, int $id_inscripcion, string $estado, int $id_usuario_registro, ?string $observacion = null): bool {
        $estadosPermitidos = ['PRESENTE', 'FALTA', 'ATRASO', 'JUSTIFICADO'];
        if (!in_array($estado, $estadosPermitidos, true)) {
            throw new InvalidArgumentException("Estado de asistencia no válido: $estado");
        }

        $sql = "INSERT INTO asistencias (
                    id_sesion, id_inscripcion, estado, id_usuario_registro, observacion, fecha_registro
                ) VALUES (
                    :id_sesion, :id_inscripcion, :estado, :id_usuario_registro, :observacion, NOW()
                )
                ON DUPLICATE KEY UPDATE 
                    estado = :estado_up,
                    id_usuario_registro = :id_usuario_up,
                    observacion = :observacion_up,
                    fecha_registro = NOW()";

        $stmt = $this->db->prepare($sql);
        $ejecutado = $stmt->execute([
            ':id_sesion'             => $id_sesion,
            ':id_inscripcion'        => $id_inscripcion,
            ':estado'                => $estado,
            ':id_usuario_registro'   => $id_usuario_registro,
            ':observacion'           => $observacion,
            ':estado_up'             => $estado,
            ':id_usuario_up'         => $id_usuario_registro,
            ':observacion_up'        => $observacion
        ]);

        if ($ejecutado) {
            // Obtener el ID del evento asociado para recalcular los porcentajes
            $sqlEvento = "SELECT id_evento FROM inscripciones WHERE id_inscripcion = :id_inscripcion LIMIT 1";
            $stmtEvt = $this->db->prepare($sqlEvento);
            $stmtEvt->execute([':id_inscripcion' => $id_inscripcion]);
            $idEvento = (int)$stmtEvt->fetchColumn();

            if ($idEvento > 0) {
                $this->calcularYActualizarPorcentaje($id_inscripcion, $idEvento);
            }
        }

        return $ejecutado;
    }

    /**
     * RF-51 y RF-52: Calcula el porcentaje de asistencia acumulado y habilita para certificación
     */
    public function calcularYActualizarPorcentaje(int $id_inscripcion, int $id_evento): array {
        // 1. Conteo de sesiones válidas (no canceladas)
        $sqlTotal = "SELECT COUNT(*) FROM sesiones_evento 
                     WHERE id_evento = :id_evento AND estado != 'CANCELADA'";
        $stmtTotal = $this->db->prepare($sqlTotal);
        $stmtTotal->execute([':id_evento' => $id_evento]);
        $totalSesiones = (int)$stmtTotal->fetchColumn();

        if ($totalSesiones === 0) {
            return ['porcentaje' => 0.00, 'habilitado' => false];
        }

        // 2. Conteo de asistencias válidas del participante (PRESENTE o JUSTIFICADO cuentan para cumplimiento)
        $sqlAsistidas = "SELECT COUNT(*) 
                         FROM asistencias a
                         INNER JOIN sesiones_evento se ON a.id_sesion = se.id_sesion
                         WHERE a.id_inscripcion = :id_inscripcion 
                           AND se.id_evento = :id_evento 
                           AND se.estado != 'CANCELADA'
                           AND a.estado IN ('PRESENTE', 'JUSTIFICADO')";
        $stmtAsistidas = $this->db->prepare($sqlAsistidas);
        $stmtAsistidas->execute([
            ':id_inscripcion' => $id_inscripcion,
            ':id_evento'      => $id_evento
        ]);
        $sesionesAsistidas = (int)$stmtAsistidas->fetchColumn();

        $porcentaje = round(($sesionesAsistidas / $totalSesiones) * 100, 2);

        // 3. Obtener regla de aprobación del evento
        $sqlRegla = "SELECT porcentaje_asistencia_minimo, emite_certificado, nota_minima_aprobacion 
                     FROM eventos WHERE id_evento = :id_evento LIMIT 1";
        $stmtRegla = $this->db->prepare($sqlRegla);
        $stmtRegla->execute([':id_evento' => $id_evento]);
        $regla = $stmtRegla->fetch();

        $minimoRequerido = (float)($regla['porcentaje_asistencia_minimo'] ?? 80.00);
        $habilitado = ($porcentaje >= $minimoRequerido) && ((int)$regla['emite_certificado'] === 1);

        // 4. Actualizar tabla de inscripciones
        $sqlActualizar = "UPDATE inscripciones 
                          SET porcentaje_asistencia = :porcentaje,
                              habilitado_certificado = :habilitado
                          WHERE id_inscripcion = :id_inscripcion";
        $stmtAct = $this->db->prepare($sqlActualizar);
        $stmtAct->execute([
            ':porcentaje'     => $porcentaje,
            ':habilitado'     => $habilitado ? 1 : 0,
            ':id_inscripcion' => $id_inscripcion
        ]);

        return [
            'total_sesiones'     => $totalSesiones,
            'sesiones_asistidas' => $sesionesAsistidas,
            'porcentaje'         => $porcentaje,
            'habilitado'         => $habilitado
        ];
    }

    /**
     * RF-22 y RF-48: Lista de asistencia para una sesión específica
     */
    public function listarPorSesion(int $id_sesion, int $id_evento): array {
        $sql = "SELECT 
                    i.id_inscripcion,
                    i.codigo_inscripcion,
                    u.ci,
                    u.nombres,
                    u.apellidos,
                    a.id_asistencia,
                    COALESCE(a.estado, 'SIN_REGISTRO') AS estado_asistencia,
                    a.observacion,
                    a.fecha_registro
                FROM inscripciones i
                INNER JOIN usuarios u ON i.id_usuario = u.id_usuario
                LEFT JOIN asistencias a ON a.id_inscripcion = i.id_inscripcion AND a.id_sesion = :id_sesion
                WHERE i.id_evento = :id_evento AND i.estado = 'INSCRITO'
                ORDER BY u.apellidos ASC, u.nombres ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_sesion' => $id_sesion,
            ':id_evento' => $id_evento
        ]);
        return $stmt->fetchAll();
    }
}