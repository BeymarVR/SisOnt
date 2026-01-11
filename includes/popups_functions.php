<?php
require_once __DIR__ . '/database.php';

/**
 * Obtener todos los popups
 */
function getPopups($cn) {
    $sql = "SELECT * FROM pop_ups ORDER BY fecha_creacion DESC";
    $res = $cn->query($sql);
    return $res->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtener popup por ID
 */
function getPopupById($cn, $id) {
    $stmt = $cn->prepare("SELECT * FROM pop_ups WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Guardar popup (insertar o actualizar)
 */
function savePopupOld($cn, $data, $archivo) {
    // Lógica para manejar la subida de archivos
    $nombreArchivo = null;
    
    if (isset($archivo) && $archivo['error'] === UPLOAD_ERR_OK) {
        // Ruta donde se guardarán los archivos (usa la misma del código antiguo)
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/SIS_ONT/assets/uploads/popups/';
        
        // Asegurar que el directorio existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generar nombre único para el archivo
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $nombreBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($archivo['name'], PATHINFO_FILENAME));
        $fileName = uniqid() . '_' . $nombreBase . '.' . $extension;
        $uploadPath = $uploadDir . $fileName;
        
        // Mover el archivo
        if (move_uploaded_file($archivo['tmp_name'], $uploadPath)) {
            $nombreArchivo = $fileName;
        }
    } elseif (isset($data['id']) && $data['id']) {
        // Si estamos editando y no se subió nuevo archivo, mantener el existente
        $stmt = $cn->prepare("SELECT archivo FROM pop_ups WHERE id = ?");
        $stmt->bind_param("i", $data['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $nombreArchivo = $existing['archivo'] ?? null;
    }
    
    // Preparar datos para la consulta
    $id = $data['id'] ?? null;
    $titulo = $data['titulo'];
    $descripcion = $data['descripcion'];
    $tipo = $data['tipo'];
    $contenido = $data['contenido'] ?? '';
    $url_externa = $data['url_externa'] ?? '';
    $activo = $data['activo'];
    $fecha_inicio = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null;
    $fecha_fin = !empty($data['fecha_fin']) ? $data['fecha_fin'] : null;
    $mostrar_una_vez = $data['mostrar_una_vez'];
    $posicion = $data['posicion'];
    $ancho = $data['ancho'];
    $alto = $data['alto'];
    $creado_por = $data['creado_por'];
    
    if ($id) {
        // Actualizar
        $stmt = $cn->prepare("UPDATE pop_ups SET titulo=?, descripcion=?, tipo=?, contenido=?, 
                             url_externa=?, archivo=?, activo=?, fecha_inicio=?, 
                             fecha_fin=?, mostrar_una_vez=?, posicion=?, ancho=?, alto=? WHERE id=?");
        $stmt->bind_param("ssssssississii", $titulo, $descripcion, $tipo, $contenido, $url_externa, 
                         $nombreArchivo, $activo, $fecha_inicio, $fecha_fin, $mostrar_una_vez, 
                         $posicion, $ancho, $alto, $id);
    } else {
        // Insertar
        $stmt = $cn->prepare("INSERT INTO pop_ups (titulo, descripcion, tipo, contenido, url_externa, 
                             archivo, activo, fecha_inicio, fecha_fin, mostrar_una_vez, posicion, 
                             ancho, alto, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssississii", $titulo, $descripcion, $tipo, $contenido, $url_externa, 
                         $nombreArchivo, $activo, $fecha_inicio, $fecha_fin, $mostrar_una_vez, 
                         $posicion, $ancho, $alto, $creado_por);
    }
    
    return $stmt->execute();
}

/**
 * Obtener popup por ID
 */
function getPopupByIdOld($cn, $id) {
    $stmt = $cn->prepare("SELECT * FROM pop_ups WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Eliminar popup y sus archivos asociados
 */
function deletePopup($cn, $id) {
    // Primero obtener información del popup para saber si tiene archivos
    $stmt = $cn->prepare("SELECT archivo, tipo FROM pop_ups WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $popup = $result->fetch_assoc();
    
    // Si existe un archivo asociado y es de tipo imagen o documento, eliminarlo
    if ($popup && !empty($popup['archivo']) && in_array($popup['tipo'], ['imagen', 'documento'])) {
        $rutaArchivo = $_SERVER['DOCUMENT_ROOT'] . '/SIS_ONT/assets/uploads/popups/' . $popup['archivo'];
        
        // Verificar si el archivo existe y eliminarlo
        if (file_exists($rutaArchivo) && is_file($rutaArchivo)) {
            unlink($rutaArchivo);
        }
    }
    
    // Ahora eliminar el registro de la base de datos
    $stmt = $cn->prepare("DELETE FROM pop_ups WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

/**
 * Obtener popups activos para el frontend
 */
function getPopupsActivos($cn, $userId) {
    $sql = "SELECT p.* FROM pop_ups p
            WHERE p.activo=1
              AND (p.fecha_inicio IS NULL OR p.fecha_inicio<=CURDATE())
              AND (p.fecha_fin IS NULL OR p.fecha_fin>=CURDATE())
              AND (
                  p.mostrar_una_vez=0 OR NOT EXISTS (
                      SELECT 1 FROM pop_ups_visto v WHERE v.pop_up_id=p.id AND v.usuario_id=?
                  )
              )
            ORDER BY p.fecha_creacion DESC";
    $stmt = $cn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Función para convertir URLs de video a formato embed
 */
function convertirUrlVideo($url) {
    // YouTube - varios formatos
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    if (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $url; // Ya está en formato embed
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/([0-9]+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    if (preg_match('/vimeo\.com\/video\/([0-9]+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    
    // Facebook
    if (preg_match('/facebook\.com\/[^\/]+\/videos\/([0-9]+)/', $url, $matches)) {
        return 'https://www.facebook.com/plugins/video.php?href=' . urlencode($url) . '&show_text=0&width=560';
    }
    
    // Dailymotion
    if (preg_match('/dailymotion\.com\/video\/([a-zA-Z0-9]+)/', $url, $matches)) {
        return 'https://www.dailymotion.com/embed/video/' . $matches[1];
    }
    
    // Si no es un video reconocido, devolver la URL original
    return $url;
}

/**
 * Función para detectar si una URL es de video embed
 */
function esUrlDeVideoEmbed($url) {
    $embedDomains = [
        'youtube.com/embed', 'youtu.be', 'player.vimeo.com', 
        'dailymotion.com/embed', 'facebook.com/plugins/video.php'
    ];
    
    foreach ($embedDomains as $domain) {
        if (strpos($url, $domain) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Función para generar miniatura de video para URLs no embed
 */
function generarMiniaturaVideo($url, $titulo) {
    // YouTube
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches) ||
        preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $videoId = $matches[1];
        return "
            <div class='video-preview'>
                <a href='{$url}' target='_blank' class='video-link'>
                    <img src='https://img.youtube.com/vi/{$videoId}/hqdefault.jpg' alt='{$titulo}' class='img-fluid'>
                    <div class='play-button'><i class='bi bi-play-circle-fill'></i></div>
                </a>
                <p class='mt-2'><small>Haz clic para ver el video en YouTube</small></p>
            </div>
        ";
    }
    
    // Vimeo (las miniaturas de Vimeo requieren API, mostramos un enlace simple)
    if (preg_match('/vimeo\.com\/([0-9]+)/', $url, $matches)) {
        return "
            <div class='video-preview'>
                <a href='{$url}' target='_blank' class='btn btn-primary'>
                    <i class='bi bi-play-circle-fill me-2'></i>Ver video en Vimeo
                </a>
            </div>
        ";
    }
    
    // Para otros tipos de video o URLs genéricas
    $host = parse_url($url, PHP_URL_HOST);
    return "
        <div class='video-preview'>
            <a href='{$url}' target='_blank' class='btn btn-primary'>
                <i class='bi bi-play-circle-fill me-2'></i>Ver contenido externo
            </a>
            <p class='mt-2'><small>Enlace: " . htmlspecialchars($host) . "</small></p>
        </div>
    ";
}