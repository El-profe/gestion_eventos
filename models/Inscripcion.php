<?php
// models/Inscripcion.php
require_once __DIR__ . '/../config/Database.php';

class Inscripcion {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * RF-10 y RF-11: Inscripción atómica con control estricto de cupos y duplicados
     * @throws Exception Si el evento está lleno, fuera de fecha o el usuario ya está inscrito
     */
    public function registrar(int $id_evento, int $id_usuario, string $origen = 'WEB_PARTICIPANTE'): int {
        try {
            $this->db->beginTransaction();

            // 1. Bloquear la fila del evento para lectura/escritura concurrente (Pessimistic Locking)
            $sqlEvento = "SELECT cupo_maximo, estado, fecha_inicio_inscripcion, fecha_fin_inscripcion 
                          FROM eventos 
                          WHERE id_evento = :id_evento 
                          FOR UPDATE";
            $stmtEvento = $this->db->prepare($sqlEvento);
            $stmtEvento->execute([':id_evento' => $id_evento]);
            $evento = $stmtEvento->fetch();

            if (!$evento) {
                throw new Exception("El evento solicitado no existe.");
            }

            if ($evento['estado'] !== 'PUBLICADO' && $origen === 'WEB_PARTICIPANTE') {
                throw new Exception("El evento no admite inscripciones en su estado actual ({$evento['estado']}).");
            }

            // Validar fechas de inscripción
            $ahora = date('Y-m-d H:i:s');
            if ($origen === 'WEB_PARTICIPANTE') {
                if ($ahora < $evento['fecha_inicio_inscripcion']) {
                    throw new Exception("El periodo de inscripción aún no ha iniciado.");
                }
                if ($ahora > $evento['fecha_fin_inscripcion']) {
                    throw new Exception("El periodo de inscripción ha finalizado.");
                }
            }

            // 2. Verificar si el usuario ya se encuentra registrado (RF-11)
            $sqlCheck = "SELECT id_inscripcion, estado FROM inscripciones 
                         WHERE id_evento = :id_evento AND id_usuario = :id_usuario LIMIT 1";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([
                ':id_evento'  => $id_evento,
                ':id_usuario' => $id_usuario
            ]);
            $existente = $stmtCheck->fetch();

            if ($existente) {
                if ($existente['estado'] !== 'CANCELADO') {
                    throw new Exception("El participante ya cuenta con una inscripción activa en este evento.");
                }
                // Si estaba cancelado, se reactiva la inscripción
                $codigoFolio = 'INS-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $sqlReactivar = "UPDATE inscripciones 
                                 SET estado = 'INSCRITO', codigo_inscripcion = :codigo, origen = :origen, fecha_inscripcion = NOW(), motivo_cancelacion = NULL
                                 WHERE id_inscripcion = :id_inscripcion";
                $stmtReactivar = $this->db->prepare($sqlReactivar);
                $stmtReactivar->execute([
                    ':codigo'         => $codigoFolio,
                    ':origen'         => $origen,
                    ':id_inscripcion' => $existente['id_inscripcion']
                ]);

                $this->db->commit();
                return (int)$existente['id_inscripcion'];
            }

            // 3. Validar disponibilidad de cupos (RF-35)
            if ((int)$evento['cupo_maximo'] > 0) {
                $sqlConteo = "SELECT COUNT(*) FROM inscripciones 
                              WHERE id_evento = :id_evento AND estado = 'INSCRITO'";
                $stmtConteo = $this->db->prepare($sqlConteo);
                $stmtConteo->execute([':id_evento' => $id_evento]);
                $totalInscritos = (int)$stmtConteo->fetchColumn();

                if ($totalInscritos >= (int)$evento['cupo_maximo']) {
                    throw new Exception("No hay cupos disponibles. El cupo máximo de {$evento['cupo_maximo']} personas ha sido alcanzado.");
                }
            }

            // 4. Generar folio único visible y asentar la inscripción
            $codigoFolio = 'INS-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $sqlInsert = "INSERT INTO inscripciones (
                            codigo_inscripcion, id_evento, id_usuario, estado, origen, 
                            porcentaje_asistencia, habilitado_certificado, fecha_inscripcion
                          ) VALUES (
                            :codigo, :id_evento, :id_usuario, 'INSCRITO', :origen, 
                            0.00, 0, NOW()
                          )";
            $stmtInsert = $this->db->prepare($sqlInsert);
            $stmtInsert->execute([
                ':codigo'     => $codigoFolio,
                ':id_evento'  => $id_evento,
                ':id_usuario' => $id_usuario,
                ':origen'     => $origen
            ]);

            $idInscripcion = (int)$this->db->lastInsertId();
            $this->db->commit();

            return $idInscripcion;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * RF-12: Cancelación voluntaria de inscripción por parte del participante
     */
    public function cancelar(int $id_inscripcion, int $id_usuario, string $motivo = 'Cancelación por el usuario'): bool {
        $sql = "UPDATE inscripciones 
                SET estado = 'CANCELADO', motivo_cancelacion = :motivo 
                WHERE id_inscripcion = :id_inscripcion AND id_usuario = :id_usuario AND estado = 'INSCRITO'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':motivo'         => trim($motivo),
            ':id_inscripcion' => $id_inscripcion,
            ':id_usuario'     => $id_usuario
        ]);
    }

    /**
     * RF-13 y RF-14: Historial de inscripciones y estados del participante
     */
    public function listarPorUsuario(int $id_usuario): array {
        $sql = "SELECT 
                    i.*,
                    e.codigo AS evento_codigo,
                    e.titulo AS evento_titulo,
                    e.modalidad,
                    e.fecha_inicio,
                    e.fecha_fin,
                    e.emite_certificado,
                    te.nombre AS tipo_evento_nombre
                FROM inscripciones i
                INNER JOIN eventos e ON i.id_evento = e.id_evento
                INNER JOIN tipos_evento te ON e.id_tipo_evento = te.id_tipo_evento
                WHERE i.id_usuario = :id_usuario
                ORDER BY i.fecha_inscripcion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        return $stmt->fetchAll();
    }

    /**
     * RF-44: Lista completa de inscritos para el Administrador o Expositor
     */
    public function listarPorEvento(int $id_evento): array {
        $sql = "SELECT 
                    i.*,
                    u.ci,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.telefono
                FROM inscripciones i
                INNER JOIN usuarios u ON i.id_usuario = u.id_usuario
                WHERE i.id_evento = :id_evento
                ORDER BY u.apellidos ASC, u.nombres ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_evento' => $id_evento]);
        return $stmt->fetchAll();
    }

    /**
     * RF-47: Modificación administrativa del estado de una inscripción
     */
    public function actualizarEstadoAdministrativo(int $id_inscripcion, string $nuevoEstado, ?string $motivo = null): bool {
        $estadosValidos = ['INSCRITO', 'CANCELADO', 'ASISTIO', 'APROBADO', 'REPROBADO'];
        if (!in_array($nuevoEstado, $estadosValidos, true)) {
            throw new InvalidArgumentException("Estado de inscripción no válido: $nuevoEstado");
        }

        $sql = "UPDATE inscripciones 
                SET estado = :estado, motivo_cancelacion = :motivo 
                WHERE id_inscripcion = :id_inscripcion";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado'         => $nuevoEstado,
            ':motivo'         => $motivo,
            ':id_inscripcion' => $id_inscripcion
        ]);
    }
}