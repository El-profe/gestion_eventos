<?php
// controllers/ReporteController.php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';

class ReporteController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * RF-71: Reporte consolidado de eventos según filtros
     */
    public function reporteEventos(array $filtros = []): array {
        $where = ["1 = 1"];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[] = "e.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['id_tipo_evento'])) {
            $where[] = "e.id_tipo_evento = :tipo";
            $params[':tipo'] = (int)$filtros['id_tipo_evento'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "e.fecha_inicio >= :desde";
            $params[':desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "e.fecha_fin <= :hasta";
            $params[':hasta'] = $filtros['fecha_hasta'];
        }

        $sql = "SELECT 
                    e.codigo,
                    e.titulo,
                    te.nombre AS tipo_evento,
                    c.nombre AS categoria,
                    e.modalidad,
                    e.estado,
                    e.fecha_inicio,
                    e.fecha_fin,
                    e.cupo_maximo,
                    (SELECT COUNT(*) FROM inscripciones i WHERE i.id_evento = e.id_evento AND i.estado = 'INSCRITO') AS inscritos_actuales,
                    (SELECT COUNT(*) FROM certificados cert WHERE cert.id_evento = e.id_evento AND cert.estado = 'EMITIDO') AS certificados_emitidos
                FROM eventos e
                INNER JOIN tipos_evento te ON e.id_tipo_evento = te.id_tipo_evento
                INNER JOIN categorias c ON e.id_categoria = c.id_categoria
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.fecha_inicio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * RF-72: Reporte detallado de participantes por evento
     */
    public function reporteParticipantesPorEvento(int $id_evento): array {
        $sql = "SELECT 
                    i.codigo_inscripcion,
                    u.ci,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.telefono,
                    i.estado AS estado_inscripcion,
                    i.origen,
                    i.porcentaje_asistencia,
                    i.habilitado_certificado,
                    i.fecha_inscripcion
                FROM inscripciones i
                INNER JOIN usuarios u ON i.id_usuario = u.id_usuario
                WHERE i.id_evento = :id_evento
                ORDER BY u.apellidos ASC, u.nombres ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_evento' => $id_evento]);
        return $stmt->fetchAll();
    }

    /**
     * RF-73: Reporte de asistencia y sesiones de un evento
     */
    public function reporteAsistencia(int $id_evento): array {
        $sql = "SELECT 
                    se.titulo AS sesion_titulo,
                    se.fecha,
                    se.hora_inicio,
                    u.ci,
                    u.nombres,
                    u.apellidos,
                    COALESCE(a.estado, 'SIN_REGISTRO') AS estado_asistencia,
                    a.observacion,
                    a.fecha_registro
                FROM sesiones_evento se
                INNER JOIN inscripciones i ON se.id_evento = i.id_evento AND i.estado = 'INSCRITO'
                INNER JOIN usuarios u ON i.id_usuario = u.id_usuario
                LEFT JOIN asistencias a ON a.id_sesion = se.id_sesion AND a.id_inscripcion = i.id_inscripcion
                WHERE se.id_evento = :id_evento
                ORDER BY se.fecha ASC, u.apellidos ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_evento' => $id_evento]);
        return $stmt->fetchAll();
    }

    /**
     * RF-74: Reporte de certificados emitidos
     */
    public function reporteCertificados(array $filtros = []): array {
        $where = ["1 = 1"];
        $params = [];

        if (!empty($filtros['id_evento'])) {
            $where[] = "c.id_evento = :id_evento";
            $params[':id_evento'] = (int)$filtros['id_evento'];
        }

        if (!empty($filtros['estado'])) {
            $where[] = "c.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        $sql = "SELECT 
                    c.codigo_unico,
                    e.codigo AS codigo_evento,
                    e.titulo AS evento_titulo,
                    u.ci,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    c.tipo_participacion,
                    c.fecha_emision,
                    c.estado,
                    c.motivo_anulacion
                FROM certificados c
                INNER JOIN eventos e ON c.id_evento = e.id_evento
                INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.fecha_emision DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * RF-75: Reporte de solicitudes y reimpresiones
     */
    public function reporteReimpresiones(): array {
        $sql = "SELECT 
                    sr.id_solicitud,
                    c.codigo_unico,
                    e.titulo AS evento_titulo,
                    u.ci AS solicitante_ci,
                    CONCAT(u.nombres, ' ', u.apellidos) AS solicitante_nombre,
                    sr.motivo,
                    sr.estado,
                    sr.fecha_solicitud,
                    sr.fecha_resolucion,
                    CONCAT(admin.nombres, ' ', admin.apellidos) AS resolutor_nombre,
                    sr.motivo_rechazo,
                    sr.fecha_entrega
                FROM solicitudes_reimpresion sr
                INNER JOIN certificados c ON sr.id_certificado = c.id_certificado
                INNER JOIN eventos e ON c.id_evento = e.id_evento
                INNER JOIN usuarios u ON sr.id_usuario_solicitante = u.id_usuario
                LEFT JOIN usuarios admin ON sr.id_usuario_resolucion = admin.id_usuario
                ORDER BY sr.fecha_solicitud DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * RF-76: Reporte de expositores y actividades impartidas
     */
    public function reporteExpositores(): array {
        $sql = "SELECT 
                    u.ci,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.telefono,
                    ee.rol_expositor,
                    e.codigo AS evento_codigo,
                    e.titulo AS evento_titulo,
                    e.modalidad,
                    e.fecha_inicio,
                    e.fecha_fin,
                    e.estado AS evento_estado
                FROM usuarios u
                INNER JOIN evento_expositores ee ON u.id_usuario = ee.id_usuario
                INNER JOIN eventos e ON ee.id_evento = e.id_evento
                ORDER BY u.apellidos ASC, e.fecha_inicio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Utilidad para forzar la descarga de datos en formato CSV compatible con Excel
     */
    public function exportarCsv(string $nombreArchivo, array $encabezados, array $datos): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '_' . date('Ymd_His') . '.csv"');

        $salida = fopen('php://output', 'w');
        // BOM UTF-8 para que Microsoft Excel reconozca tildes y caracteres especiales
        fprintf($salida, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($salida, $encabezados);

        foreach ($datos as $fila) {
            fputcsv($salida, array_values($fila));
        }

        fclose($salida);
        exit();
    }

    /**
     * Endpoint para exportar el reporte seleccionado a CSV
     */
    public function exportar(): void {
        AuthHelper::requerirRol(['ADMINISTRADOR']);

        $tipo = trim($_GET['tipo'] ?? '');

        match ($tipo) {
            'eventos' => $this->exportarCsv(
                'reporte_eventos',
                ['Código', 'Título', 'Tipo', 'Categoría', 'Modalidad', 'Estado', 'Fecha Inicio', 'Fecha Fin', 'Cupo Máx', 'Inscritos', 'Certificados'],
                $this->reporteEventos()
            ),
            'participantes' => $this->exportarCsv(
                'reporte_participantes',
                ['Folio', 'CI', 'Nombres', 'Apellidos', 'Correo', 'Teléfono', 'Estado', 'Origen', '% Asistencia', 'Habilitado Cert.', 'Fecha Inscripción'],
                $this->reporteParticipantesPorEvento((int)($_GET['id_evento'] ?? 0))
            ),
            'certificados' => $this->exportarCsv(
                'reporte_certificados',
                ['Código Certificado', 'Código Evento', 'Evento', 'CI', 'Nombres', 'Apellidos', 'Correo', 'Rol', 'Fecha Emisión', 'Estado', 'Motivo Anulación'],
                $this->reporteCertificados()
            ),
            'reimpresiones' => $this->exportarCsv(
                'reporte_reimpresiones',
                ['ID', 'Código Certificado', 'Evento', 'CI Solicitante', 'Nombre Solicitante', 'Motivo', 'Estado', 'Fecha Solicitud', 'Fecha Resolución', 'Resolutor', 'Motivo Rechazo', 'Fecha Entrega'],
                $this->reporteReimpresiones()
            ),
            'expositores' => $this->exportarCsv(
                'reporte_expositores',
                ['CI', 'Nombres', 'Apellidos', 'Correo', 'Teléfono', 'Rol Expositor', 'Código Evento', 'Evento', 'Modalidad', 'Fecha Inicio', 'Fecha Fin', 'Estado Evento'],
                $this->reporteExpositores()
            ),
            default => die("Tipo de reporte no válido para exportación.")
        };
    }
}