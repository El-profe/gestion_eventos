<?php
// models/Certificado.php
require_once __DIR__ . '/../config/Database.php';

class Certificado {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * RF-54: Genera un identificador seguro y legible para validación externa (ej: CERT-2026-A8F1-B4C2)
     */
    public static function generarCodigoUnico(): string {
        $anio = date('Y');
        $bytes = random_bytes(4); // Criptográficamente seguro
        $hash = strtoupper(bin2hex($bytes));
        return "CERT-{$anio}-" . substr($hash, 0, 4) . '-' . substr($hash, 4, 4);
    }

    /**
     * RF-52 y RF-53: Emisión de certificado individual para un participante habilitado
     * @throws Exception Si el alumno no cumple los requisitos o ya tiene certificado emitido
     */
    public function generarIndividual(int $id_inscripcion, int $id_admin_emisor): int {
        try {
            $this->db->beginTransaction();

            // 1. Validar que la inscripción exista y esté habilitada
            $sqlIns = "SELECT i.id_inscripcion, i.id_evento, i.id_usuario, i.estado, 
                              i.porcentaje_asistencia, i.habilitado_certificado,
                              e.emite_certificado, e.titulo AS evento_titulo
                       FROM inscripciones i
                       INNER JOIN eventos e ON i.id_evento = e.id_evento
                       WHERE i.id_inscripcion = :id_inscripcion 
                       FOR UPDATE";
            $stmtIns = $this->db->prepare($sqlIns);
            $stmtIns->execute([':id_inscripcion' => $id_inscripcion]);
            $inscripcion = $stmtIns->fetch();

            if (!$inscripcion) {
                throw new Exception("Inscripción no encontrada.");
            }

            if ((int)$inscripcion['emite_certificado'] !== 1) {
                throw new Exception("El evento no está configurado para emitir certificados.");
            }

            if ((int)$inscripcion['habilitado_certificado'] !== 1) {
                throw new Exception("El participante no cumple los requisitos de asistencia necesarios.");
            }

            // 2. Verificar que no cuente ya con un certificado activo
            $sqlCheck = "SELECT id_certificado, codigo_unico, estado 
                         FROM certificados 
                         WHERE id_inscripcion = :id_inscripcion LIMIT 1";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([':id_inscripcion' => $id_inscripcion]);
            $existente = $stmtCheck->fetch();

            if ($existente && $existente['estado'] === 'EMITIDO') {
                throw new Exception("Ya existe un certificado activo emitido para esta inscripción ({$existente['codigo_unico']}).");
            }

            // 3. Generar folio único garantizando no colisión (RF-54)
            do {
                $codigoUnico = self::generarCodigoUnico();
                $stmtColision = $this->db->prepare("SELECT COUNT(*) FROM certificados WHERE codigo_unico = ?");
                $stmtColision->execute([$codigoUnico]);
                $colision = (int)$stmtColision->fetchColumn() > 0;
            } while ($colision);

            // 4. Asentar en base de datos
            $sqlInsert = "INSERT INTO certificados (
                            codigo_unico, id_inscripcion, id_evento, id_usuario, 
                            tipo_participacion, fecha_emision, estado
                          ) VALUES (
                            :codigo_unico, :id_inscripcion, :id_evento, :id_usuario, 
                            'PARTICIPANTE', NOW(), 'EMITIDO'
                          )";
            $stmtInsert = $this->db->prepare($sqlInsert);
            $stmtInsert->execute([
                ':codigo_unico'   => $codigoUnico,
                ':id_inscripcion' => $id_inscripcion,
                ':id_evento'      => $inscripcion['id_evento'],
                ':id_usuario'     => $inscripcion['id_usuario']
            ]);

            $idCertificado = (int)$this->db->lastInsertId();
            $this->db->commit();

            return $idCertificado;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * RF-55: Generación masiva de certificados para todos los inscritos habilitados de un evento
     */
    public function generarMasivo(int $id_evento, int $id_admin_emisor): array {
        // Consultar participantes habilitados que NO posean certificado emitido
        $sqlHabilitados = "SELECT i.id_inscripcion 
                           FROM inscripciones i
                           LEFT JOIN certificados c ON i.id_inscripcion = c.id_inscripcion AND c.estado = 'EMITIDO'
                           WHERE i.id_evento = :id_evento 
                             AND i.habilitado_certificado = 1 
                             AND c.id_certificado IS NULL";
        $stmtHab = $this->db->prepare($sqlHabilitados);
        $stmtHab->execute([':id_evento' => $id_evento]);
        $pendientes = $stmtHab->fetchAll(PDO::FETCH_COLUMN);

        $emitidos = 0;
        $errores  = [];

        foreach ($pendientes as $idInscripcion) {
            try {
                $this->generarIndividual((int)$idInscripcion, $id_admin_emisor);
                $emitidos++;
            } catch (Exception $e) {
                $errores[] = "Inscripción #{$idInscripcion}: " . $e->getMessage();
            }
        }

        return [
            'total_procesados' => count($pendientes),
            'total_emitidos'   => $emitidos,
            'errores'          => $errores
        ];
    }

    /**
     * RF-16 y Validación Pública por QR
     */
    public function obtenerPorCodigo(string $codigo_unico): ?array {
        $sql = "SELECT 
                    c.*,
                    u.ci, u.nombres, u.apellidos, u.correo,
                    e.codigo AS evento_codigo, e.titulo AS evento_titulo,
                    e.modalidad, e.fecha_inicio, e.fecha_fin, e.horas_academicas,
                    te.nombre AS tipo_evento_nombre
                FROM certificados c
                INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
                INNER JOIN eventos e ON c.id_evento = e.id_evento
                INNER JOIN tipos_evento te ON e.id_tipo_evento = te.id_tipo_evento
                WHERE c.codigo_unico = :codigo LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':codigo' => trim($codigo_unico)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * RF-15: Certificados emitidos al participante en sesión
     */
    public function listarPorUsuario(int $id_usuario): array {
        $sql = "SELECT 
                    c.*,
                    e.codigo AS evento_codigo,
                    e.titulo AS evento_titulo,
                    e.horas_academicas,
                    te.nombre AS tipo_evento_nombre
                FROM certificados c
                INNER JOIN eventos e ON c.id_evento = e.id_evento
                INNER JOIN tipos_evento te ON e.id_tipo_evento = te.id_tipo_evento
                WHERE c.id_usuario = :id_usuario AND c.estado = 'EMITIDO'
                ORDER BY c.fecha_emision DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        return $stmt->fetchAll();
    }

    /**
     * RF-56 y RF-74: Certificados emitidos por evento para administración
     */
    public function listarPorEvento(int $id_evento): array {
        $sql = "SELECT 
                    c.*,
                    u.ci, u.nombres, u.apellidos, u.correo
                FROM certificados c
                INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
                WHERE c.id_evento = :id_evento
                ORDER BY c.fecha_emision DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_evento' => $id_evento]);
        return $stmt->fetchAll();
    }

    /**
     * RF-59: Anulación administrativa de certificado con motivo de auditoría
     */
    public function anular(int $id_certificado, int $id_admin, string $motivo): bool {
        $sql = "UPDATE certificados 
                SET estado = 'ANULADO',
                    motivo_anulacion = :motivo,
                    fecha_anulacion = NOW(),
                    id_usuario_anulacion = :id_admin
                WHERE id_certificado = :id_certificado AND estado = 'EMITIDO'";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':motivo'         => trim($motivo),
            ':id_admin'       => $id_admin,
            ':id_certificado' => $id_certificado
        ]);
    }
}