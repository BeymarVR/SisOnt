<?php
/**
 * Funciones para la gestión de perfiles de usuario
 */

/**
 * Obtiene un usuario por ID con información extendida
 */
function obtenerUsuarioPorId($id) {
    $conexion = obtenerConexion();
    
    $sql = "SELECT u.*, r.nombre as rol_nombre 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            WHERE u.id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Actualiza el perfil de un usuario
 */
function actualizarPerfil($id, $nombre, $email, $telefono = null, $biografia = null) {
    $conexion = obtenerConexion();
    
    $sql = "UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, biografia = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $email, $telefono, $biografia, $id);
    
    return $stmt->execute();
}

/**
 * Obtiene estadísticas del usuario
 */
function obtenerEstadisticasUsuario($usuarioId) {
    $conexion = obtenerConexion();
    
    $estadisticas = [
        'total_comentarios' => 0,
        'total_encuestas' => 0,
        'dias_registro' => 0
    ];
    
    // Total de comentarios
    $sql = "SELECT COUNT(*) as total FROM comentarios WHERE usuario_id = ? AND estado = 'aprobado'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $result = $stmt->get_result();
    $estadisticas['total_comentarios'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Total de encuestas respondidas
    $sql = "SELECT COUNT(DISTINCT encuesta_id) as total FROM respuestas WHERE usuario_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $result = $stmt->get_result();
    $estadisticas['total_encuestas'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Días desde el registro
    $sql = "SELECT DATEDIFF(NOW(), fecha_registro) as dias FROM usuarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $result = $stmt->get_result();
    $estadisticas['dias_registro'] = $result->fetch_assoc()['dias'] ?? 0;
    
    return $estadisticas;
}

/**
 * Obtiene la actividad reciente del usuario
 */
function obtenerActividadUsuario($usuarioId, $limite = 10) {
    $conexion = obtenerConexion();
    
    $actividad = [];
    
    // Actividad de comentarios
    $sql = "SELECT 'comentario' as tipo, c.contenido as titulo, 
                   CONCAT('Comentario en: ', COALESCE(n.titulo, no.titulo, e.titulo, 'Publicación')) as descripcion,
                   c.fecha_creacion as fecha
            FROM comentarios c
            LEFT JOIN noticias n ON c.noticia_id = n.id
            LEFT JOIN normativas no ON c.normativa_id = no.id
            LEFT JOIN encuestas e ON c.encuesta_id = e.id
            WHERE c.usuario_id = ? AND c.estado = 'aprobado'
            ORDER BY c.fecha_creacion DESC
            LIMIT ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $usuarioId, $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $actividad[] = $row;
    }
    
    // Si no hay suficiente actividad de comentarios, agregar registro de encuestas
    if (count($actividad) < $limite) {
        $limite_encuestas = $limite - count($actividad);
        
        $sql = "SELECT 'encuesta' as tipo, e.titulo, 
                       'Encuesta respondida' as descripcion,
                       r.fecha_respuesta as fecha
                FROM respuestas r
                JOIN encuestas e ON r.encuesta_id = e.id
                WHERE r.usuario_id = ?
                ORDER BY r.fecha_respuesta DESC
                LIMIT ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ii", $usuarioId, $limite_encuestas);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $actividad[] = $row;
        }
    }
    
    // Ordenar por fecha
    usort($actividad, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
    
    return array_slice($actividad, 0, $limite);
}

/**
 * Verifica si el email ya existe (excepto para el usuario actual)
 */
function emailExiste($email, $usuarioId) {
    $conexion = obtenerConexion();
    
    $sql = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $email, $usuarioId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}
?>