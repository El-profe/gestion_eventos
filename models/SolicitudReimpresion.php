<?php
// models/SolicitudReimpresion.php
require_once __DIR__ . '/../config/Database.php';

class SolicitudReimpresion {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * RF-17 y RF-18: El participante solicita la reimpresión indicando motivo justificado
     */
    public function registrarSolicitud(int $id_certificado, int $id_usuario_solicitante, string $motivo): int {
        // 1. Validar que el certificado exista, pertenezca al usuario y se encuentre EMITIDO
        $sqlCert = "SELECT id_certificado, id_usuario, estado 
                    FROM certificados 
                    WHERE id_certificado = :id_certificado LIMIT 1";
        $stmtCert = $this->db->prepare($sqlCert);
        $stmtCert->execute([':id_certificado' => $id_certificado]);
        $cert = $stmtCert->fetch();

        if (!$cert || (int)$cert['id_usuario'] !== $id_usuario_solicitante) {
            throw new Exception("El certificado indicado no pertenece a su cuenta.");
        }

        if ($cert['estado'] !== 'EMITIDO') {
            throw new Exception("No es posible solicitar reimpresión de un certificado anulado.");
        }

        // 2. Verificar que no exista otra solicitud previa en estado PENDIENTE
        $sqlCheck = "SELECT COUNT(*) FROM solicitudes_reimpresion 
                     WHERE id_certificado = :id_certificado AND estado = 'PENDIENTE'";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([':id_certificado' => $id_certificado]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            throw new Exception("Ya cuenta con una solicitud de reimpresión en revisión para este documento.");
        }

        // 3. Crear solicitud
        $sqlInsert = "INSERT INTO solicitudes_reimpresion (
                        id_certificado, id_usuario_solicitante, motivo, estado, fecha_solicitud
                      ) VALUES (
                        :id_certificado, :id_usuario, :motivo, 'PENDIENTE', NOW()
                      )";
        $stmtInsert = $this->db->prepare($sqlInsert);
        $stmtInsert->execute([
            ':id_certificado' => $id_certificado,
            ':id_usuario'     => $id_usuario_solicitante,
            ':motivo'         => trim($motivo)
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * RF-19: Consultar el estado de solicitudes propias del participante
     */
    public function listarPorUsuario(int $id_usuario): array {
        $sql = "SELECT 
                    sr.*,
                    c.codigo_unico,
                    e.titulo AS evento_titulo
                FROM solicitudes_reimpresion sr
                INNER JOIN certificados c ON sr.id_certificado = c.id_certificado
                INNER JOIN eventos e ON c.id_evento = e.id_evento
                WHERE sr.id_usuario_solicitante = :id_usuario
                ORDER BY sr.fecha_solicitud DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        return $stmt->fetchAll();
    }

    /**
     * RF-60 y RF-61: Lista de solicitudes pendientes para revisión del administrador
     */
    public function listarPendientes(): array {
        $sql = "SELECT 
                    sr.*,
                    c.codigo_unico,
                    c.fecha_emision,
                    e.titulo AS evento_titulo,
                    u.ci, u.nombres, u.apellidos, u.correo
                FROM solicitudes_reimpresion sr
                INNER JOIN certificados c ON sr.id_certificado = c.id_certificado
                INNER JOIN eventos e ON c.id_evento = e.id_evento
                INNER JOIN usuarios u ON sr.id_usuario_solicitante = u.id_usuario
                WHERE sr.estado = 'PENDIENTE'
                ORDER BY sr.fecha_solicitud ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * RF-62 y RF-63: Resolver administrativamente la solicitud (APROBADA o RECHAZADA)
     */
    public function resolver(int $id_solicitud, int $id_admin, string $decision, ?string $motivoRechazo = null): bool {
        if (!in_array($decision, ['APROBADA', 'RECHAZADA'], true)) {
            throw new InvalidArgumentException("Decisión inválida para la solicitud: $decision");
        }

        $sql = "UPDATE solicitudes_reimpresion 
                SET estado = :estado,
                    id_usuario_resolucion = :id_admin,
                    motivo_rechazo = :motivo_rechazo,
                    fecha_resolucion = NOW()
                WHERE id_solicitud = :id_solicitud AND estado = 'PENDIENTE'";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado'          => $decision,
            ':id_admin'        => $id_admin,
            ':motivo_rechazo'  => ($decision === 'RECHAZADA') ? trim($motivoRechazo ?? '') : null,
            ':id_solicitud'    => $id_solicitud
        ]);
    }

    /**
     * RF-66: Registrar la entrega material del nuevo ejemplar del certificado al solicitante
     */
    public function registrarEntrega(int $id_solicitud, int $id_admin): bool {
        $sql = "UPDATE solicitudes_reimpresion 
                SET estado = 'ENTREGADA',
                    fecha_entrega = NOW(),
                    responsable_entrega = :id_admin
                WHERE id_solicitud = :id_solicitud AND estado = 'APROBADA'";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_admin'     => $id_admin,
            ':id_solicitud' => $id_solicitud
        ]);
    }
}