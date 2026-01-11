<?php
// pop_ups_guardar.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

verificarRol('admin');

$conexion = obtenerConexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $tipo = $_POST['tipo']; // Nuevo campo para el tipo
    $activo = $_POST['activo'];
    $fecha_inicio = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
    $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
    $mostrar_una_vez = isset($_POST['mostrar_una_vez']) ? 1 : 0;
    $posicion = $_POST['posicion'];
    $ancho = $_POST['ancho'];
    $alto = $_POST['alto'];
    $creado_por = $_SESSION['user_id'];
    
    // Variables según el tipo
    $contenido = $_POST['contenido'] ?? '';
    $url_externa = $_POST['url_externa'] ?? '';
    $nombreArchivo = null;
    
    // Procesar según el tipo
    if ($tipo === 'imagen') {
        // Procesar la imagen
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/SIS_ONT/assets/uploads/popups/';
            
            // Crear directorio si no existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Validar que sea una imagen
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['archivo']['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                header("Location: pop_ups.php?error=invalid_image");
                exit;
            }
            
            // Generar nombre único
            $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
            $nombreBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($_FILES['archivo']['name'], PATHINFO_FILENAME));
            $fileName = uniqid() . '_' . $nombreBase . '.' . $extension;
            $uploadPath = $uploadDir . $fileName;
            
            // Mover el archivo
            if (move_uploaded_file($_FILES['archivo']['tmp_name'], $uploadPath)) {
                $nombreArchivo = $fileName;
            } else {
                header("Location: pop_ups.php?error=upload_failed");
                exit;
            }
        } else {
            header("Location: pop_ups.php?error=no_image");
            exit;
        }
    } 
    elseif ($tipo === 'video') {
        // Para video, procesar la URL
        if (!empty($_POST['url_externa'])) {
            $url_externa = convertirUrlVideo($_POST['url_externa']);
        } else {
            header("Location: pop_ups.php?error=no_video_url");
            exit;
        }
    }
    elseif ($tipo === 'texto') {
        // Para texto, asegurar que hay contenido
        if (empty($_POST['contenido'])) {
            header("Location: pop_ups.php?error=no_content");
            exit;
        }
        $contenido = $_POST['contenido'];
    }
    
    // Insertar en la base de datos
    $stmt = $conexion->prepare("INSERT INTO pop_ups (titulo, descripcion, tipo, contenido, url_externa, archivo, activo, fecha_inicio, fecha_fin, mostrar_una_vez, posicion, ancho, alto, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssississii", $titulo, $descripcion, $tipo, $contenido, $url_externa, $nombreArchivo, $activo, $fecha_inicio, $fecha_fin, $mostrar_una_vez, $posicion, $ancho, $alto, $creado_por);
    
    if ($stmt->execute()) {
        header("Location: pop_ups.php?success=1");
        exit;
    } else {
        header("Location: pop_ups.php?error=db_error");
        exit;
    }
} else {
    header("Location: pop_ups.php");
    exit;
}

// Función para convertir URLs de video a formato embed (la misma que tenías antes)
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
    
    // Si no es un video reconocido, devolver la URL original
    return $url;
}
?>