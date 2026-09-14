<?php
// models/Evento.php
require_once __DIR__ . '/../config/Database.php';

class Evento {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * RF-29: Crea un nuevo evento en estado BORRADOR
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO eventos (
                    codigo, titulo, descripcion, id_tipo_evento, id_categoria, modalidad, 
                    lugar, enlace_virtual, cupo_maximo, fecha_inicio_inscripcion, 
                    fecha_fin_inscripcion, fecha_inicio, fecha_fin, emite_certificado, 
                    horas_academicas, porcentaje_asistencia_minimo, nota_minima_aprobacion, 
                    estado, id_usuario_creador
                ) VALUES (
                    :codigo, :titulo, :descripcion, :id_tipo_evento, :id_categoria, :modalidad, 
                    :lugar, :enlace_virtual, :cupo_maximo, :fecha_inicio_inscripcion, 
                    :fecha_fin_inscripcion, :fecha_inicio, :fecha_fin, :emite_certificado, 
                    :horas_academicas, :porcentaje_asistencia_minimo, :nota_minima_aprobacion, 
                    'BORRADOR', :id_usuario_creador
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo'                        => trim($datos['codigo']),
            ':titulo'                        => trim($datos['titulo']),
            ':descripcion'                  => trim($datos['descripcion']),
            ':id_tipo_evento'                => (int)$datos['id_tipo_evento'],
            ':id_categoria'                  => (int)$datos['id_categoria'],
            ':modalidad'                     => $datos['modalidad'],
            ':lugar'                         => !empty($datos['lugar']) ? trim($datos['lugar']) : null,
            ':enlace_virtual'                => !empty($datos['enlace_virtual']) ? trim($datos['enlace_virtual']) : null,
            ':cupo_maximo'                   => (int)($datos['cupo_maximo'] ?? 0),
            ':fecha_inicio_inscripcion'      => $datos['fecha_inicio_inscripcion'],
            ':fecha_fin_inscripcion'        => $datos['fecha_fin_inscripcion'],
            ':fecha_inicio'                  => $datos['fecha_inicio'],
            ':fecha_fin'                     => $datos['fecha_fin'],
            ':emite_certificado'             => !empty($datos['emite_certificado']) ? 1 : 0,
            ':horas_academicas'              => (int)($datos['horas_academicas'] ?? 0),
            ':porcentaje_asistencia_minimo' => (float)($datos['porcentaje_asistencia_minimo'] ?? 80.00),
            ':nota_minima_aprobacion'        => (float)($datos['nota_minima_aprobacion'] ?? 0.00),
            ':id_usuario_creador'            => (int)$datos['id_usuario_creador']
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * RF-30: Actualiza la información básica y operativa del evento
     */
    public function actualizar(int $id_evento, array $datos): bool {
        $sql = "UPDATE eventos SET
                    titulo = :titulo,
                    descripcion = :descripcion,
                    id_tipo_evento = :id_tipo_evento,
                    id_categoria = :id_categoria,
                    modalidad = :modalidad,
                    lugar = :lugar,
                    enlace_virtual = :enlace_virtual,
                    cupo_maximo = :cupo_maximo,
                    fecha_inicio_inscripcion = :fecha_inicio_inscripcion,
                    fecha_fin_inscripcion = :fecha_fin_inscripcion,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    emite_certificado = :emite_certificado,
                    horas_academicas = :horas_academicas,
                    porcentaje_asistencia_minimo = :porcentaje_asistencia_minimo,
                    nota_minima_aprobacion = :nota_minima_aprobacion
                WHERE id_evento = :id_evento";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':titulo'                        => trim($datos['titulo']),
            ':descripcion'                  => trim($datos['descripcion']),
            ':id_tipo_evento'                => (int)$datos['id_tipo_evento'],
            ':id_categoria'                  => (int)$datos['id_categoria'],
            ':modalidad'                     => $datos['modalidad'],
            ':lugar'                         => !empty($datos['lugar']) ? trim($datos['lugar']) : null,
            ':enlace_virtual'                => !empty($datos['enlace_virtual']) ? trim($datos['enlace_virtual']) : null,
            ':cupo_maximo'                   => (int)($datos['cupo_maximo'] ?? 0),
            ':fecha_inicio_inscripcion'      => $datos['fecha_inicio_inscripcion'],
            ':fecha_fin_inscripcion'        => $datos['fecha_fin_inscripcion'],
            ':fecha_inicio'                  => $datos['fecha_inicio'],
            ':fecha_fin'                     => $datos['fecha_fin'],
            ':emite_certificado'             => !empty($datos['emite_certificado']) ? 1 : 0,
            ':horas_academicas'              => (int)($datos['horas_academicas'] ?? 0),
            ':porcentaje_asistencia_minimo' => (float)($datos['porcentaje_asistencia_minimo'] ?? 80.00),
            ':nota_minima_aprobacion'        => (float)($datos['nota_minima_aprobacion'] ?? 0.00),
            ':id_evento'                     => $id_evento
        ]);
    }

    /**
     * RF-31 a RF-34: Transiciones del ciclo de vida
     * ('BORRADOR', 'PUBLICADO', 'EN_CURSO', 'FINALIZADO', 'CANCELADO')
     */
    public function cambiarEstado(int $id_evento, string $nuevoEstado): bool {
        $estadosPermitidos = ['BORRADOR', 'PUBLICADO', 'EN_CURSO', 'FINALIZADO', 'CANCELADO'];
        if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
            throw new InvalidArgumentException("Estado de evento no válido: $nuevoEstado");
        }

        $sql = "UPDATE eventos SET estado = :estado WHERE id_evento = :id_evento";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado'    => $nuevoEstado,
            ':id_evento' => $id_evento
        ]);
    }

    /**
     * RF-07 y RF-08: Catálogo de eventos para participantes con búsqueda y filtros
     */
    public function listarPublicos(array $filtros = []): array {
        $where = ["e.estado = 'PUBLICADO'"];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $where[] = "(e.titulo LIKE :buscar OR e.descripcion LIKE :buscar)";
            $params[':buscar'] = '%' . trim($filtros['buscar']) . '%';
        }

        if (!empty($filtros['id_tipo_evento'])) {
            $where[] = "e.id_tipo_evento = :id_tipo_evento";
            $params[':id_tipo_evento'] = (int)$filtros['id_tipo_evento'];
        }

        if (!empty($filtros['id_categoria'])) {
            $where[] = "e.id_categoria = :id_categoria";
            $params[':id_categoria'] = (int)$filtros['id_categoria'];
        }

        if (!empty($filtros['fecha'])) {
            $where[] = "(:fecha BETWEEN e.fecha_inicio AND e.fecha_fin)";
            $params[':fecha'] = $filtros['fecha'];
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT 
                    e.*,
                    te.nombre AS tipo_evento_nombre,
                    c.nombre AS categoria_nombre,
                    (SELECT COUNT(*) FROM inscripciones i WHERE i.id_evento = e.id_evento AND i.estado = 'INSCRITO') AS total_inscritos
                FROM eventos e
                INNER JOIN tipos_evento te ON e.id_tipo_evento = te.id_tipo_evento
                INNER JOIN categorias c ON e.id_categoria = c.id_categoria
                WHERE $whereSql
                ORDER BY e.fecha_inicio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * RF-09: Detalle completo de un evento individual
     */
    public function obtenerDetalle(int $id_evento): ?array {
        $sql = "SELECT 
                    e.*,
                    te.nombre AS tipo_evento_nombre,
                    c.nombre AS categoria_nombre,
                    (SELECT COUNT(*) FROM inscripciones i WHERE i.id_evento = e.id_evento AND i.estado = 'INSCRITO') AS total_inscritos
                FROM eventos e
                INNER JOIN tipos_evento te ON e.id_tipo_evento = te.id_tipo_evento
                INNER JOIN categorias c ON e.id_categoria = c.id_categoria
                WHERE e.id_evento = :id_evento
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_evento' => $id_evento]);
        $evento = $stmt->fetch();

        if (!$evento) {
            return null;
        }

        // Cargar expositores asignados
        $evento['expositores'] = $this->obtenerExpositores($id_evento);

        return $evento;
    }

    /**
     * RF-42: Asignación de expositores al evento
     */
    public function asignarExpositor(int $id_evento, int $id_usuario, string $rolExpositor = 'Expositor Principal'): bool {
        $sql = "INSERT INTO evento_expositores (id_evento, id_usuario, rol_expositor)
                VALUES (:id_evento, :id_usuario, :rol_expositor)
                ON DUPLICATE KEY UPDATE rol_expositor = :rol_expositor_up";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_evento'          => $id_evento,
            ':id_usuario'         => $id_usuario,
            ':rol_expositor'      => $rolExpositor,
            ':rol_expositor_up'   => $rolExpositor
        ]);
    }

    public function desasignarExpositor(int $id_evento, int $id_usuario): bool {
        $sql = "DELETE FROM evento_expositores WHERE id_evento = :id_evento AND id_usuario = :id_usuario";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_evento'  => $id_evento,
            ':id_usuario' => $id_usuario
        ]);
    }

    public function obtenerExpositores(int $id_evento): array {
        $sql = "SELECT u.id_usuario, u.ci, u.nombres, u.apellidos, u.correo, ee.rol_expositor
                FROM evento_expositores ee
                INNER JOIN usuarios u ON ee.id_usuario = u.id_usuario
                WHERE ee.id_evento = :id_evento";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_evento' => $id_evento]);
        return $stmt->fetchAll();
    }

    /**
     * RF-20: Eventos asignados a un expositor específico
     */
    public function listarPorExpositor(int $id_usuario_expositor): array {
        $sql = "SELECT e.*, te.nombre AS tipo_evento_nombre, ee.rol_expositor
                FROM eventos e
                INNER JOIN evento_expositores ee ON e.id_evento = ee.id_evento
                INNER JOIN tipos_evento te ON e.id_tipo_evento = te.id_tipo_evento
                WHERE ee.id_usuario = :id_usuario
                ORDER BY e.fecha_inicio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario_expositor]);
        return $stmt->fetchAll();
    }

    /**
     * RF-10 y RF-11: Validar condiciones de inscripción (cupo y periodo hábil)
     */
    public function verificarDisponibilidadInscripcion(int $id_evento): array {
        $evento = $this->obtenerDetalle($id_evento);
        if (!$evento) {
            return ['habilitado' => false, 'motivo' => 'El evento solicitado no existe.'];
        }

        if ($evento['estado'] !== 'PUBLICADO') {
            return ['habilitado' => false, 'motivo' => 'El evento no se encuentra abierto a inscripciones.'];
        }

        $ahora = date('Y-m-d H:i:s');
        if ($ahora < $evento['fecha_inicio_inscripcion']) {
            return ['habilitado' => false, 'motivo' => 'El periodo de inscripción aún no ha comenzado.'];
        }

        if ($ahora > $evento['fecha_fin_inscripcion']) {
            return ['habilitado' => false, 'motivo' => 'El periodo de inscripción ha finalizado.'];
        }

        if ($evento['cupo_maximo'] > 0 && $evento['total_inscritos'] >= $evento['cupo_maximo']) {
            return ['habilitado' => false, 'motivo' => 'No existen cupos disponibles para este evento.'];
        }

        return ['habilitado' => true, 'evento' => $evento];
    }
}