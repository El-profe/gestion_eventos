<?php
// models/Material.php
require_once __DIR__ . '/../config/Database.php';

class Material {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * RF-28: Procesa la subida física del archivo y guarda su referencia
     */
    public function subir(int $id_evento, int $id_usuario, string $titulo, ?string $descripcion, array $archivo): int {
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al cargar el archivo en el servidor (Código: {$archivo['error']}).");
        }

        // Límite de 20 MB por archivo
        if ($archivo['size'] > 20 * 1024 * 1024) {
            throw new Exception("El archivo excede el tamaño máximo permitido de 20MB.");
        }

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $extensionesPermitidas = ['pdf', 'zip', 'rar', 'pptx', 'docx', 'xlsx', 'txt'];

        if (!in_array($extension, $extensionesPermitidas, true)) {
            throw new Exception("Tipo de archivo no permitido. Solo se aceptan: " . implode(', ', $extensionesPermitidas));
        }

        $directorioDestino = __DIR__ . '/../public/uploads/materiales/';
        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0755, true);
        }

        $nombreLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($archivo['name'], PATHINFO_FILENAME));
        $nombreGuardado = 'mat_' . $id_evento . '_' . time() . '_' . substr($nombreLimpio, 0, 30) . '.' . $extension;
        $rutaCompleta = $directorioDestino . $nombreGuardado;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            throw new Exception("No se pudo mover el archivo al directorio de almacenamiento.");
        }

        $sql = "INSERT INTO materiales_evento (
                    id_evento, id_usuario_subio, titulo, descripcion, ruta_archivo, tipo_archivo, tamano_bytes, fecha_publicacion
                ) VALUES (
                    :id_evento, :id_usuario, :titulo, :descripcion, :ruta, :tipo, :tamano, NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_evento'    => $id_evento,
            ':id_usuario'   => $id_usuario,
            ':titulo'       => trim($titulo),
            ':descripcion' => !empty($descripcion) ? trim($descripcion) : null,
            ':ruta'         => 'uploads/materiales/' . $nombreGuardado,
            ':tipo'         => strtoupper($extension),
            ':tamano'       => (int)$archivo['size']
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * RF-28: Consulta de materiales disponibles para un evento
     */
    public function listarPorEvento(int $id_evento): array {
        $sql = "SELECT 
                    m.*,
                    u.nombres, u.apellidos
                FROM materiales_evento m
                INNER JOIN usuarios u ON m.id_usuario_subio = u.id_usuario
                WHERE m.id_evento = :id_evento
                ORDER BY m.fecha_publicacion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_evento' => $id_evento]);
        return $stmt->fetchAll();
    }

    public function eliminar(int $id_material): bool {
        $sql = "SELECT ruta_archivo FROM materiales_evento WHERE id_material = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id_material]);
        $material = $stmt->fetch();

        if ($material) {
            $archivoFisico = __DIR__ . '/../public/' . $material['ruta_archivo'];
            if (file_exists($archivoFisico)) {
                unlink($archivoFisico);
            }

            $sqlDel = "DELETE FROM materiales_evento WHERE id_material = :id";
            $stmtDel = $this->db->prepare($sqlDel);
            return $stmtDel->execute([':id' => $id_material]);
        }
        return false;
    }
}