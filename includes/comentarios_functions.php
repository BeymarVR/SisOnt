<?php
require_once 'database.php';

/**
 * Obtiene todos los comentarios con información de usuario y publicación
 */
function obtenerComentarios($filtroEstado = 'todos') {
    $conexion = obtenerConexion();
    
    $sql = "SELECT c.*, 
                   u.nombre as nombre_usuario,
                   COALESCE(n.titulo, no.titulo, e.titulo) as titulo_publicacion,
                   CASE 
                     WHEN c.noticia_id IS NOT NULL THEN 'noticia'
                     WHEN c.normativa_id IS NOT NULL THEN 'normativa' 
                     WHEN c.encuesta_id IS NOT NULL THEN 'encuesta'
                     ELSE 'general'
                   END as tipo_publicacion
            FROM comentarios c
            INNER JOIN usuarios u ON c.usuario_id = u.id
            LEFT JOIN noticias n ON c.noticia_id = n.id
            LEFT JOIN normativas no ON c.normativa_id = no.id
            LEFT JOIN encuestas e ON c.encuesta_id = e.id";
    
    if ($filtroEstado !== 'todos') {
        $sql .= " WHERE c.estado = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $filtroEstado);
    } else {
        $stmt = $conexion->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $comentarios = [];
    
    while ($row = $result->fetch_assoc()) {
        $comentarios[] = $row;
    }
    
    return $comentarios;
}

/**
 * Obtiene estadísticas de comentarios
 */
function obtenerEstadisticasComentarios() {
    $conexion = obtenerConexion();
    
    $sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazados
    FROM comentarios";
    
    $result = $conexion->query($sql);
    return $result->fetch_assoc();
}

/**
 * Obtiene comentarios para una publicación específica
 */
function obtenerComentariosPublicacion($tipo, $id, $soloAprobados = true) {
    $conexion = obtenerConexion();
    
    $campoId = "";
    switch ($tipo) {
        case 'noticia': $campoId = "noticia_id"; break;
        case 'normativa': $campoId = "normativa_id"; break;
        case 'encuesta': $campoId = "encuesta_id"; break;
        default: return [];
    }
    
    $sql = "SELECT c.*, u.nombre as usuario_nombre
            FROM comentarios c
            INNER JOIN usuarios u ON c.usuario_id = u.id
            WHERE c.$campoId = ?";
    
    if ($soloAprobados) {
        $sql .= " AND c.estado = 'aprobado'";
    }
    
    $sql .= " ORDER BY c.fecha_creacion DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comentarios = [];
    while ($row = $result->fetch_assoc()) {
        $comentarios[] = $row;
    }
    
    return $comentarios;
}

/**
 * Guarda un nuevo comentario
 */
function guardarComentario($datos) {
    $conexion = obtenerConexion();
    
    $sql = "INSERT INTO comentarios (contenido, usuario_id, noticia_id, normativa_id, encuesta_id, estado) 
            VALUES (?, ?, ?, ?, ?, 'pendiente')";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("siiii", 
        $datos['contenido'],
        $datos['usuario_id'],
        $datos['noticia_id'],
        $datos['normativa_id'],
        $datos['encuesta_id']
    );
    
    return $stmt->execute();
}

/**
 * Cambia el estado de un comentario
 */
function cambiarEstadoComentario($id, $estado, $moderadorId, $motivo = null) {
    $conexion = obtenerConexion();
    
    $sql = "UPDATE comentarios 
            SET estado = ?, moderador_id = ?, fecha_moderacion = NOW(), motivo_rechazo = ?
            WHERE id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sisi", $estado, $moderadorId, $motivo, $id);
    
    return $stmt->execute();
}

/**
 * Elimina un comentario
 */
function eliminarComentario($id) {
    $conexion = obtenerConexion();
    
    $sql = "DELETE FROM comentarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

/**
 * Obtiene un comentario por ID
 */
function obtenerComentarioPorId($id) {
    $conexion = obtenerConexion();
    
    $sql = "SELECT c.*, u.nombre as nombre_usuario
            FROM comentarios c
            INNER JOIN usuarios u ON c.usuario_id = u.id
            WHERE c.id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}
?>