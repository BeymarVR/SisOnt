<?php
/**
 * Funciones para la gestión de noticias
 */

// ... tus funciones existentes ...

/**
 * Elimina una noticia por ID
 */
function eliminarNoticia($id) {
    $conexion = obtenerConexion();
    
    try {
        // Primero obtener información de la noticia para eliminar archivos
        $noticia = obtenerNoticiaPorId($id);
        
        if (!$noticia) {
            return false;
        }
        
        // Eliminar imagen principal si existe
        if ($noticia['imagen_portada']) {
            $rutaImagen = __DIR__ . "/../assets/uploads/noticias/" . $noticia['imagen_portada'];
            if (file_exists($rutaImagen)) {
                unlink($rutaImagen);
            }
        }
        
        // Eliminar video archivo si existe
        if ($noticia['video_archivo']) {
            $rutaVideo = __DIR__ . "/../assets/uploads/videos/" . $noticia['video_archivo'];
            if (file_exists($rutaVideo)) {
                unlink($rutaVideo);
            }
        }
        
        // Eliminar imágenes de bloques de contenido
        if ($noticia['contenido_json']) {
            $bloques = json_decode($noticia['contenido_json'], true);
            if (is_array($bloques)) {
                foreach ($bloques as $bloque) {
                    if (isset($bloque['type']) && $bloque['type'] === 'image' && 
                        isset($bloque['content']['filename'])) {
                        $rutaImagenBloque = __DIR__ . "/../assets/uploads/noticias/" . $bloque['content']['filename'];
                        if (file_exists($rutaImagenBloque)) {
                            unlink($rutaImagenBloque);
                        }
                    }
                    if (isset($bloque['type']) && $bloque['type'] === 'video' && 
                        isset($bloque['content']['filename'])) {
                        $rutaVideoBloque = __DIR__ . "/../assets/uploads/videos/" . $bloque['content']['filename'];
                        if (file_exists($rutaVideoBloque)) {
                            unlink($rutaVideoBloque);
                        }
                    }
                }
            }
        }
        
        // Eliminar noticia de la base de datos
        $stmt = $conexion->prepare("DELETE FROM noticias WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Error al eliminar noticia: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene una noticia por ID
 */
function obtenerNoticiaPorId($id) {
    $conexion = obtenerConexion();
    
    $stmt = $conexion->prepare("SELECT * FROM noticias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Obtiene todas las noticias
 */
function obtenerTodasNoticias() {
    $conexion = obtenerConexion();
    
    $sql = "SELECT * FROM noticias ORDER BY fecha_publicacion DESC";
    $result = $conexion->query($sql);
    $noticias = [];
    
    while ($row = $result->fetch_assoc()) {
        $noticias[] = $row;
    }
    
    return $noticias;
}
?>