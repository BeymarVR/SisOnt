<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/noticias_functions.php';
verificarRol('admin');

$action = $_GET['action'] ?? 'list';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'new' || $action === 'edit') {
        $titulo = $_POST['titulo'];
        $subtitulo = $_POST['subtitulo'] ?? '';
        $contenido = $_POST['contenido'] ?? '';
        $contenido_json = $_POST['contenido_json'] ?? '';
        $json = json_decode($contenido_json, true) ?? [];

        // Process block media uploads - VERSIÓN CORREGIDA
foreach ($json as &$block) {
    $blockId = $block['id'] ?? '';
    if (!$blockId) continue;
    
    // Asegurar que los directorios existen
    $dirNoticias = __DIR__ . '/../assets/uploads/noticias/';
    $dirVideos = __DIR__ . '/../assets/uploads/videos/';
    
    if (!is_dir($dirNoticias)) {
        mkdir($dirNoticias, 0755, true);
    }
    if (!is_dir($dirVideos)) {
        mkdir($dirVideos, 0755, true);
    }
    
    if ($block['type'] === 'image') {
        // DEBUG: Verificar si hay archivo
        $hasFile = isset($_FILES['block_images']['name'][$blockId]) && 
                  !empty($_FILES['block_images']['name'][$blockId]);
        
        error_log("DEBUG - Bloque imagen {$blockId}: ¿Tiene archivo? " . ($hasFile ? 'SÍ' : 'NO'));
        
        if ($hasFile && $_FILES['block_images']['error'][$blockId] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $_FILES['block_images']['name'][$blockId],
                'type' => $_FILES['block_images']['type'][$blockId],
                'tmp_name' => $_FILES['block_images']['tmp_name'][$blockId],
                'error' => $_FILES['block_images']['error'][$blockId],
                'size' => $_FILES['block_images']['size'][$blockId]
            ];
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $nombreImagen = uniqid() . '.' . $extension;
            $rutaImagen = $dirNoticias . $nombreImagen;
            
            error_log("DEBUG - Subiendo imagen: {$file['name']} -> {$rutaImagen}");
            
            if (move_uploaded_file($file['tmp_name'], $rutaImagen)) {
                // Eliminar la imagen anterior si existe
                if (isset($block['content']['filename']) && $block['content']['filename']) {
                    $rutaAnterior = $dirNoticias . $block['content']['filename'];
                    if (file_exists($rutaAnterior)) {
                        @unlink($rutaAnterior);
                    }
                }
                $block['content']['filename'] = $nombreImagen;
                error_log("DEBUG - Imagen subida exitosamente: {$nombreImagen}");
            } else {
                error_log("ERROR - No se pudo mover la imagen: {$file['tmp_name']} -> {$rutaImagen}");
            }
        } else {
            // Preservar el filename existente si no se subió nueva imagen
            if (isset($block['content']['filename']) && $block['content']['filename']) {
                error_log("DEBUG - Conservando imagen existente: {$block['content']['filename']}");
                // Mantener el filename existente - no hacer nada
            } else {
                error_log("DEBUG - No hay imagen para el bloque {$blockId}");
            }
        }
    } 
    elseif ($block['type'] === 'video') {
        $videoType = $block['content']['video_type'] ?? 'url';
        
        if ($videoType === 'url') {
            // Para video URL, eliminar cualquier filename existente
            unset($block['content']['filename']);
        } else {
            // Para video upload
            unset($block['content']['url']);
            
            $hasFile = isset($_FILES['block_videos']['name'][$blockId]) && 
                      !empty($_FILES['block_videos']['name'][$blockId]);
            
            error_log("DEBUG - Bloque video {$blockId}: ¿Tiene archivo? " . ($hasFile ? 'SÍ' : 'NO'));
            
            if ($hasFile && $_FILES['block_videos']['error'][$blockId] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['block_videos']['name'][$blockId],
                    'type' => $_FILES['block_videos']['type'][$blockId],
                    'tmp_name' => $_FILES['block_videos']['tmp_name'][$blockId],
                    'error' => $_FILES['block_videos']['error'][$blockId],
                    'size' => $_FILES['block_videos']['size'][$blockId]
                ];
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $nombreVideo = uniqid() . '.' . $extension;
                $rutaVideo = $dirVideos . $nombreVideo;
                
                error_log("DEBUG - Subiendo video: {$file['name']} -> {$rutaVideo}");
                
                if (move_uploaded_file($file['tmp_name'], $rutaVideo)) {
                    // Eliminar el video anterior si existe
                    if (isset($block['content']['filename']) && $block['content']['filename']) {
                        $rutaAnterior = $dirVideos . $block['content']['filename'];
                        if (file_exists($rutaAnterior)) {
                            @unlink($rutaAnterior);
                        }
                    }
                    $block['content']['filename'] = $nombreVideo;
                    error_log("DEBUG - Video subido exitosamente: {$nombreVideo}");
                } else {
                    error_log("ERROR - No se pudo mover el video: {$file['tmp_name']} -> {$rutaVideo}");
                }
            } else {
                // Preservar el filename existente si no se subió nuevo video
                if (isset($block['content']['filename']) && $block['content']['filename']) {
                    error_log("DEBUG - Conservando video existente: {$block['content']['filename']}");
                    // Mantener el filename existente - no hacer nada
                } else {
                    error_log("DEBUG - No hay video para el bloque {$blockId}");
                }
            }
        }
    }
}
// === ELIMINAR ARCHIVOS DE BLOQUES BORRADOS ===
if (isset($_POST['deleted_files'])) {
    $dirNoticias = __DIR__ . '/../assets/uploads/noticias/';
    $dirVideos = __DIR__ . '/../assets/uploads/videos/';
    
    // Eliminar imágenes marcadas para borrar
    if (isset($_POST['deleted_files']['image'])) {
        foreach ($_POST['deleted_files']['image'] as $imagenBorrar) {
            $rutaImagen = $dirNoticias . $imagenBorrar;
            if (file_exists($rutaImagen) && is_file($rutaImagen)) {
                @unlink($rutaImagen);
                error_log("✅ Imagen de bloque eliminada: {$imagenBorrar}");
            }
        }
    }
    
    // Eliminar videos marcados para borrar
    if (isset($_POST['deleted_files']['video'])) {
        foreach ($_POST['deleted_files']['video'] as $videoBorrar) {
            $rutaVideo = $dirVideos . $videoBorrar;
            if (file_exists($rutaVideo) && is_file($rutaVideo)) {
                @unlink($rutaVideo);
                error_log("✅ Video de bloque eliminado: {$videoBorrar}");
            }
        }
    }
}

        $contenido_json = json_encode($json);
        
        $destacada = isset($_POST['destacada']) ? 1 : 0;
        $estado = $_POST['estado'] ?? 'borrador';
        $video_url = $_POST['video_url'] ?? '';
        $meta_descripcion = $_POST['meta_descripcion'] ?? '';

      // Handle main image upload
$imagen = null;
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
    $nombreImagen = uniqid() . '.' . $extension;
    $rutaImagen = __DIR__ . '/../assets/uploads/noticias/' . $nombreImagen;
    
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaImagen)) {
        // Eliminar la imagen anterior si estamos editando
        if ($action === 'edit' && $noticia['imagen_portada']) {
            @unlink(__DIR__ . '/../assets/uploads/noticias/' . $noticia['imagen_portada']);
        }
        $imagen = $nombreImagen;
    }
} elseif ($action === 'edit') {
    // Preservar la imagen existente si no se subió una nueva
    // A menos que se haya marcado para eliminar
    if (isset($_POST['eliminar_imagen']) && $_POST['eliminar_imagen']) {
        // Eliminar la imagen existente
        if ($noticia['imagen_portada']) {
            @unlink(__DIR__ . '/../assets/uploads/noticias/' . $noticia['imagen_portada']);
        }
        $imagen = null;
    } else {
        $imagen = $noticia['imagen_portada'];
    }
}

        // Handle main video upload
        $video_archivo = null;
        if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
            $nombreVideo = uniqid() . '.' . $extension;
            $rutaVideo = __DIR__ . '/../assets/uploads/videos/' . $nombreVideo;
            
            if (move_uploaded_file($_FILES['video']['tmp_name'], $rutaVideo)) {
                // Eliminar el video anterior si estamos editando
                if ($action === 'edit' && $noticia['video_archivo']) {
                    @unlink(__DIR__ . '/../assets/uploads/videos/' . $noticia['video_archivo']);
                }
                $video_archivo = $nombreVideo;
            }
        } elseif ($action === 'edit') {
            // Preservar el video existente si no se subió uno nuevo
            $video_archivo = $noticia['video_archivo'];
        }

        $conexion = obtenerConexion();
        
        if ($action === 'new') {
            $stmt = $conexion->prepare("
                INSERT INTO noticias 
                (titulo, subtitulo, contenido, contenido_json, imagen_portada, video_url, video_archivo, usuario_id, destacada, estado, meta_descripcion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssssssisss",
                $titulo,
                $subtitulo,
                $contenido,
                $contenido_json,
                $imagen,
                $video_url,
                $video_archivo,
                $_SESSION['user_id'], // i
                $destacada,           // i
                $estado,
                $meta_descripcion
            );
            $stmt->execute();
            $message = 'Noticia creada exitosamente';
            $messageType = 'success';
        } elseif ($action === 'edit') {
            $id = $_POST['id'];

            // Determinar qué campos actualizar según los archivos subidos
            if ($imagen && $video_archivo) {
                $stmt = $conexion->prepare("
                    UPDATE noticias 
                    SET titulo = ?, subtitulo = ?, contenido = ?, contenido_json = ?, imagen_portada = ?, video_url = ?, video_archivo = ?, destacada = ?, estado = ?, meta_descripcion = ?
                    WHERE id = ?
                ");
                $stmt->bind_param(
                    "sssssssissi",
                    $titulo, $subtitulo, $contenido, $contenido_json,
                    $imagen, $video_url, $video_archivo,
                    $destacada, $estado, $meta_descripcion, $id
                );
            } elseif ($imagen) {
                $stmt = $conexion->prepare("
                    UPDATE noticias 
                    SET titulo = ?, subtitulo = ?, contenido = ?, contenido_json = ?, imagen_portada = ?, video_url = ?, destacada = ?, estado = ?, meta_descripcion = ?
                    WHERE id = ?
                ");
                $stmt->bind_param(
                    "ssssssissi",
                    $titulo, $subtitulo, $contenido, $contenido_json,
                    $imagen, $video_url,
                    $destacada, $estado, $meta_descripcion, $id
                );
            } elseif ($video_archivo) {
                $stmt = $conexion->prepare("
                    UPDATE noticias 
                    SET titulo = ?, subtitulo = ?, contenido = ?, contenido_json = ?, video_url = ?, video_archivo = ?, destacada = ?, estado = ?, meta_descripcion = ?
                    WHERE id = ?
                ");
                $stmt->bind_param(
                    "ssssssissi",
                    $titulo, $subtitulo, $contenido, $contenido_json,
                    $video_url, $video_archivo,
                    $destacada, $estado, $meta_descripcion, $id
                );
            } else {
                $stmt = $conexion->prepare("
                    UPDATE noticias 
                    SET titulo = ?, subtitulo = ?, contenido = ?, contenido_json = ?, video_url = ?, destacada = ?, estado = ?, meta_descripcion = ?
                    WHERE id = ?
                ");
                $stmt->bind_param(
                    "sssssissi",
                    $titulo, $subtitulo, $contenido, $contenido_json,
                    $video_url,
                    $destacada, $estado, $meta_descripcion, $id
                );
            }
            $stmt->execute();
            $message = 'Noticia actualizada exitosamente';
            $messageType = 'success';
        }
        
        if ($messageType === 'success') {
            header("Location: noticias.php?message=" . urlencode($message) . "&type=" . $messageType);
            exit();
        }
    }
}

// Get data for editing
$noticia = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conexion = obtenerConexion();
    $stmt = $conexion->prepare("SELECT * FROM noticias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $noticia = $result->fetch_assoc();
}

// Get news list
$noticias = obtenerResultados("
    SELECT n.*, u.nombre as autor_nombre 
    FROM noticias n 
    LEFT JOIN usuarios u ON n.usuario_id = u.id 
    ORDER BY n.fecha_publicacion DESC
");

// Messages from URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'info';
}


// 🔥 ELIMINAR ESTO - Manejar eliminación de noticias
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Verificar que la noticia existe
    $conexion = obtenerConexion();
    $stmt = $conexion->prepare("SELECT * FROM noticias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $noticia = $result->fetch_assoc();
    
    if ($noticia) {
        // ✅ Eliminar imagen principal
        if ($noticia['imagen_portada']) {
            $rutaImagen = __DIR__ . '/../assets/uploads/noticias/' . $noticia['imagen_portada'];
            if (file_exists($rutaImagen)) {
                unlink($rutaImagen);
            }
        }
        
        // ✅ Eliminar video principal
        if ($noticia['video_archivo']) {
            $rutaVideo = __DIR__ . '/../assets/uploads/videos/' . $noticia['video_archivo'];
            if (file_exists($rutaVideo)) {
                unlink($rutaVideo);
            }
        }
        
        // ✅ Eliminar imágenes de bloques de contenido
        if ($noticia['contenido_json']) {
            $bloques = json_decode($noticia['contenido_json'], true);
            if (is_array($bloques)) {
                foreach ($bloques as $bloque) {
                    if (isset($bloque['type']) && $bloque['type'] === 'image' && 
                        isset($bloque['content']['filename'])) {
                        $rutaImagenBloque = __DIR__ . '/../assets/uploads/noticias/' . $bloque['content']['filename'];
                        if (file_exists($rutaImagenBloque)) {
                            unlink($rutaImagenBloque);
                        }
                    }
                    // ✅ Eliminar videos de bloques de contenido
                    if (isset($bloque['type']) && $bloque['type'] === 'video' && 
                        isset($bloque['content']['filename'])) {
                        $rutaVideoBloque = __DIR__ . '/../assets/uploads/videos/' . $bloque['content']['filename'];
                        if (file_exists($rutaVideoBloque)) {
                            unlink($rutaVideoBloque);
                        }
                    }
                }
            }
        }
        
        // Reemplaza la sección de eliminación (líneas 390 aprox) con esto:

// 🔥 Manejar eliminación de noticias CORREGIDO
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Verificar que la noticia existe
    $conexion = obtenerConexion();
    
    // 🔥 PRIMERO: Eliminar los comentarios relacionados
    try {
        // Comenzar transacción
        $conexion->begin_transaction();
        
        // 1. Eliminar comentarios asociados a la noticia
        $stmt1 = $conexion->prepare("DELETE FROM comentarios WHERE noticia_id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        
        // 2. Obtener datos de la noticia para eliminar archivos
        $stmt2 = $conexion->prepare("SELECT * FROM noticias WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $noticia = $result->fetch_assoc();
        
        if ($noticia) {
            // ✅ Eliminar imagen principal
            if ($noticia['imagen_portada']) {
                $rutaImagen = __DIR__ . '/../assets/uploads/noticias/' . $noticia['imagen_portada'];
                if (file_exists($rutaImagen)) {
                    unlink($rutaImagen);
                }
            }
            
            // ✅ Eliminar video principal
            if ($noticia['video_archivo']) {
                $rutaVideo = __DIR__ . '/../assets/uploads/videos/' . $noticia['video_archivo'];
                if (file_exists($rutaVideo)) {
                    unlink($rutaVideo);
                }
            }
            
            // ✅ Eliminar imágenes de bloques de contenido
            if ($noticia['contenido_json']) {
                $bloques = json_decode($noticia['contenido_json'], true);
                if (is_array($bloques)) {
                    foreach ($bloques as $bloque) {
                        if (isset($bloque['type']) && $bloque['type'] === 'image' && 
                            isset($bloque['content']['filename'])) {
                            $rutaImagenBloque = __DIR__ . '/../assets/uploads/noticias/' . $bloque['content']['filename'];
                            if (file_exists($rutaImagenBloque)) {
                                unlink($rutaImagenBloque);
                            }
                        }
                        // ✅ Eliminar videos de bloques de contenido
                        if (isset($bloque['type']) && $bloque['type'] === 'video' && 
                            isset($bloque['content']['filename'])) {
                            $rutaVideoBloque = __DIR__ . '/../assets/uploads/videos/' . $bloque['content']['filename'];
                            if (file_exists($rutaVideoBloque)) {
                                unlink($rutaVideoBloque);
                            }
                        }
                    }
                }
            }
            
            // 3. FINALMENTE: Eliminar la noticia
            $stmt3 = $conexion->prepare("DELETE FROM noticias WHERE id = ?");
            $stmt3->bind_param("i", $id);
            $stmt3->execute();
            
            // Confirmar la transacción
            $conexion->commit();
            
            // Mensaje de éxito
            $_SESSION['mensaje'] = "Noticia eliminada exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
            
        } else {
            $conexion->rollback();
            $_SESSION['mensaje'] = "La noticia no existe";
            $_SESSION['tipo_mensaje'] = "danger";
        }
        
    } catch (Exception $e) {
        // Revertir en caso de error
        $conexion->rollback();
        $_SESSION['mensaje'] = "Error al eliminar la noticia: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }
    
    // Redireccionar
    header("Location: noticias.php");
    exit();
}
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Noticias - ONT Bolivia</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    
    <!-- Added Sortable.js for drag and drop functionality -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <!-- TinyMCE con tu API key -->
    <script src="https://cdn.tiny.cloud/1/ktv5kcoghotxvpc6o9xwbx63da1j5y8excl7f7bxqnnu2vqe/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    
    <style>
        /* Advanced editor styles for professional content creation */
        .content-builder {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            min-height: 200px;
        }
        
        .content-block {
            background: white;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            cursor: move;
            transition: all 0.3s ease;
        }
        
        .content-block:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
        }
        
        .content-block.active {
            border-color: #0d6efd;
            border-style: solid;
        }
        
        .block-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .block-type {
            font-weight: 600;
            color: #0d6efd;
            font-size: 0.9rem;
        }
        
        .block-actions button {
            border: none;
            background: none;
            color: #6c757d;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .block-actions button:hover {
            background: #f8f9fa;
            color: #0d6efd;
        }
        
        .add-block-menu {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .add-block-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            background: none;
            color: #6c757d;
            text-decoration: none;
            transition: all 0.3s;
            min-width: 80px;
        }
        
        .add-block-btn:hover {
            border-color: #0d6efd;
            color: #0d6efd;
            background: #f8f9fa;
        }
        
        .add-block-btn i {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .media-upload-zone {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .media-upload-zone:hover {
            border-color: #0d6efd;
            background: #f8f9fa;
        }
        
        .media-preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
        }
        
        .typography-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .font-selector {
            padding: 5px 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background: white;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-card-header {
            padding: 20px 20px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .form-card-body {
            padding: 0 20px 20px;
        }
        
        .preview-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .preview-panel.active {
            right: 0;
        }
        
        .sortable-ghost {
            opacity: 0.5;
        }

        .search-input {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            width: 300px;
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content" id="mainContent">
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title">
                    <?php if ($action === 'new'): ?>
                        Nueva Noticia Avanzada
                    <?php elseif ($action === 'edit'): ?>
                        Editar Noticia Avanzada
                    <?php else: ?>
                        Gestión de Noticias
                    <?php endif; ?>
                </h1>
            </div>
            
        </header>

        <div class="content-area">
            <!-- Mostrar mensajes de sesión -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?= $_SESSION['tipo_mensaje'] ?> alert-dismissible fade show">
                    <?= $_SESSION['mensaje'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
            <?php endif; ?>
            
            <!-- Mostrar mensajes de URL -->
            <?php if ($message): ?>
                <div class="alert-ont <?= $messageType ?> auto-hide">
                    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                    <div>
                        <strong><?= ucfirst($messageType) ?></strong>
                        <p class="mb-0"><?= htmlspecialchars($message) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($action !== 'new' && $action !== 'edit'): ?>
                <!-- Stats Grid para resumen de noticias -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-header">
                            <div class="stat-icon primary">
                                <i class="bi bi-newspaper"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?= count($noticias) ?></h3>
                            <p>Total Noticias</p>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-header">
                            <div class="stat-icon success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?= count(array_filter($noticias, fn($n) => $n['estado'] === 'publicado')) ?></h3>
                            <p>Publicadas</p>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div class="stat-icon warning">
                                <i class="bi bi-file-earmark"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?= count(array_filter($noticias, fn($n) => $n['estado'] === 'borrador')) ?></h3>
                            <p>Borradores</p>
                        </div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-header">
                            <div class="stat-icon info">
                                <i class="bi bi-star"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?= count(array_filter($noticias, fn($n) => $n['destacada'])) ?></h3>
                            <p>Destacadas</p>
                        </div>
                    </div>
                </div>

                <!-- Header de la tabla con botón y buscador -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="content-title">Lista de Noticias</h2>
                        <p class="content-subtitle">Gestiona las noticias del sistema</p>
                    </div>
                    <div class="d-flex gap-2">
                      
                        <a href="noticias.php?action=new" class="btn btn-ont primary">
                            <i class="bi bi-plus-circle"></i> Nueva Noticia
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($action === 'new' || $action === 'edit'): ?>
                <!-- Advanced news editor form with rich content blocks -->
                <form method="POST" enctype="multipart/form-data" id="advancedNewsForm">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?= $noticia['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Main Content Area -->
                        <div class="col-lg-8">
                            <div class="form-card">
                                <div class="form-card-header">
                                    <h5><i class="bi bi-file-text me-2"></i>Información Principal</h5>
                                </div>
                                <div class="form-card-body">
                                    <!-- Title and Subtitle -->
                                    <div class="form-group">
                                        <label class="form-label">Título Principal *</label>
                                        <input type="text"
                                                name="titulo"
                                                class="form-control"
                                                value="<?= $noticia ? htmlspecialchars($noticia['titulo']) : '' ?>"
                                                required
                                                maxlength="255"
                                                placeholder="Ingrese el título principal de la noticia">
                                            <small class="text-muted">Máximo 255 caracteres</small>

                                    </div>

                                    <!-- <div class="form-group">
                                        <label class="form-label">Subtítulo</label>
                                        <input type="text" name="subtitulo" class="form-control" 
                                               value="<?= $noticia ? htmlspecialchars($noticia['subtitulo']) : '' ?>" 
                                               placeholder="Subtítulo opcional para complementar el título">
                                    </div> -->

                                    
                                </div>
                            </div>

                            <!-- Content Builder -->
                            <div class="form-card">
                                <div class="form-card-header">
                                    <h5><i class="bi bi-building me-2"></i>Constructor de Contenido</h5>
                                    <small class="text-muted">Arrastra y suelta los elementos para reorganizar</small>
                                </div>
                                <div class="form-card-body">
                                    <!-- Add Block Menu -->
                                    <div class="add-block-menu">
                                        <button type="button" class="add-block-btn" onclick="addBlock('text')">
                                            <i class="bi bi-text-paragraph"></i>
                                            Texto
                                        </button>
                                        <button type="button" class="add-block-btn" onclick="addBlock('image')">
                                            <i class="bi bi-image"></i>
                                            Imagen
                                        </button>
                                        <button type="button" class="add-block-btn" onclick="addBlock('video')">
                                            <i class="bi bi-play-circle"></i>
                                            Video
                                        </button>
                                        <button type="button" class="add-block-btn" onclick="addBlock('quote')">
                                            <i class="bi bi-quote"></i>
                                            Cita
                                        </button>
                                        <button type="button" class="add-block-btn" onclick="addBlock('list')">
                                            <i class="bi bi-list-ul"></i>
                                            Lista
                                        </button>
                                        <button type="button" class="add-block-btn" onclick="addBlock('divider')">
                                            <i class="bi bi-hr"></i>
                                            Separador
                                        </button>
                                    </div>

                                    <!-- Content Blocks Container -->
                                    <div id="contentBlocks" class="content-builder">
                                        <div class="text-center text-muted py-4">
                                            <i class="bi bi-plus-circle" style="font-size: 2rem;"></i>
                                            <p class="mb-0">Haz clic en los botones de arriba para agregar contenido</p>
                                        </div>
                                    </div>

                                    <!-- Hidden inputs for content -->
                                    <input type="hidden" name="contenido_json" id="contenidoJson">
                                    <textarea name="contenido" id="contenidoTexto" style="display: none;"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar Settings -->
                        <div class="col-lg-4">
                            <!-- Publication Settings -->
                            <div class="form-card">
                                <div class="form-card-header">
                                    <h5><i class="bi bi-gear me-2"></i>Configuración</h5>
                                </div>
                                <div class="form-card-body">
                                    <div class="form-group">
                                        <label class="form-label">Estado</label>
                                        <select name="estado" class="form-control">
                                            <option value="borrador" <?= ($noticia && $noticia['estado'] === 'borrador') ? 'selected' : '' ?>>Borrador</option>
                                            <option value="publicado" <?= ($noticia && $noticia['estado'] === 'publicado') ? 'selected' : '' ?>>Publicado</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" name="destacada" class="form-check-input" id="destacada"
                                                   <?= ($noticia && $noticia['destacada']) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="destacada">
                                                Destacar esta noticia
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                           <!-- En la sección de Media Settings -->
<div class="form-card">
    <div class="form-card-header">
        <h5><i class="bi bi-image me-2"></i>Multimedia</h5>
    </div>
    <div class="form-card-body">
        <div class="form-group">
            <label class="form-label">Imagen Principal</label>
            <input type="file" name="imagen" class="form-control" accept="image/*">
            <?php if ($noticia && $noticia['imagen_portada']): ?>
                <div class="mt-2">
                    <img src="../assets/uploads/noticias/<?= htmlspecialchars($noticia['imagen_portada']) ?>" 
                         alt="Imagen actual" class="media-preview">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="eliminar_imagen" class="form-check-input" id="eliminar_imagen">
                        <label class="form-check-label" for="eliminar_imagen">
                            Eliminar imagen actual
                        </label>
                    </div>
                    <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($noticia['imagen_portada']) ?>">
                </div>
            <?php endif; ?>
        </div>

                                    <!-- <div class="form-group">
                                        <label class="form-label">Video URL (YouTube, Vimeo)</label>
                                        <input type="url" name="video_url" class="form-control" 
                                               value="<?= $noticia ? htmlspecialchars($noticia['video_url']) : '' ?>"
                                               placeholder="https://www.youtube.com/watch?v=...">
                                    </div> -->

                                    <!-- <div class="form-group">
                                        <label class="form-label">O subir video</label>
                                        <input type="file" name="video" class="form-control" accept="video/*">
                                        <small class="text-muted">MP4, WebM, OGV (máx. 50MB)</small>
                                    </div> -->
                                </div>
                            </div>

                            <!-- SEO Settings -->
                            <!-- <div class="form-card">
                                <div class="form-card-header">
                                    <h5><i class="bi bi-search me-2"></i>SEO</h5>
                                </div>
                                <div class="form-card-body">
                                    <div class="form-group">
                                        <label class="form-label">Meta Descripción</label>
                                        <textarea name="meta_descripcion" class="form-control" rows="3" 
                                                  placeholder="Descripción breve para motores de búsqueda"><?= $noticia ? htmlspecialchars($noticia['meta_descripcion']) : '' ?></textarea>
                                        <small class="text-muted">Máximo 160 caracteres recomendados</small>
                                    </div>
                                </div>
                            </div> -->

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn-ont primary">
                                    <i class="bi bi-<?= $action === 'new' ? 'plus' : 'check' ?>"></i>
                                    <?= $action === 'new' ? 'Crear Noticia' : 'Actualizar Noticia' ?>
                                </button>
                                <button type="button" class="btn-ont warning" onclick="saveAsDraft()">
                                    <i class="bi bi-file-earmark"></i>
                                    Guardar como Borrador
                                </button>
                                <a href="noticias.php" class="btn-ont secondary">
                                    <i class="bi bi-x"></i>
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

            <?php else: ?>
                <!-- News List -->
                <div class="data-table">
                    <div class="table-header d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="bi bi-newspaper me-2"></i>Noticias</h5>
                        <div class="table-actions">
                            <input type="text" class="search-input" placeholder="Buscar noticias..." 
                                   data-target=".data-table table tbody" style="padding: 0.375rem 0.75rem; border: 1px solid #dee2e6; border-radius: 6px;">
                        </div>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Autor</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Multimedia</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($noticias as $noticia): ?>
                                <tr class="searchable-item">
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($noticia['titulo']) ?></div>
                                        <?php if ($noticia['subtitulo']): ?>
                                            <small class="text-muted"><?= htmlspecialchars($noticia['subtitulo']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($noticia['destacada']): ?>
                                            <span class="badge bg-warning text-dark ms-1">Destacada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($noticia['autor_nombre'] ?? 'Sin autor') ?></td>
                                    <td>
                                        <span class="badge-ont <?= $noticia['estado'] === 'publicado' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($noticia['estado']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($noticia['fecha_publicacion'])) ?></td>
                                    <td>
                                        <?php if ($noticia['imagen_portada']): ?>
                                            <i class="bi bi-image text-primary" title="Tiene imagen"></i>
                                        <?php endif; ?>
                                        <?php if ($noticia['video_url'] || $noticia['video_archivo']): ?>
                                            <i class="bi bi-play-circle text-success" title="Tiene video"></i>
                                        <?php endif; ?>
                                        <?php if ($noticia['contenido_json']): ?>
                                            <i class="bi bi-building text-info" title="Contenido avanzado"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="noticias.php?action=edit&id=<?= $noticia['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="../noticias.php?id=<?= $noticia['id'] ?>" 
                                               class="btn btn-sm btn-outline-info" target="_blank">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmarEliminacion(<?= $noticia['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Preview Panel -->
    <div id="previewPanel" class="preview-panel">
        <div class="p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Vista Previa</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="togglePreview()">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div id="previewContent">
                <!-- Preview content will be generated here -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    
    <!-- Advanced JavaScript for content builder functionality -->
    <script>
    let blockCounter = 0;
    let contentBlocks = [];
    let isEditing = <?= ($action === 'edit' && $noticia && $noticia['contenido_json']) ? 'true' : 'false' ?>;

    // Debug: Verificar si hay contenido para cargar
    <?php if ($noticia && $noticia['contenido_json']): ?>
        console.log('✅ Hay contenido JSON para cargar:', <?= $noticia['contenido_json'] ?>);
    <?php else: ?>
        console.log('❌ No hay contenido JSON para cargar');
    <?php endif; ?>

    // Initialize TinyMCE for rich text editing
    tinymce.init({
        selector: '.rich-text-editor',
        height: 200,
        menubar: false,
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste code help wordcount'
        ],
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        setup: function(editor) {
            editor.on('change', function() {
                updateContentJson();
            });
        }
    });

    // Initialize sortable for drag and drop
    document.addEventListener('DOMContentLoaded', function() {
        const contentContainer = document.getElementById('contentBlocks');
        if (contentContainer) {
            new Sortable(contentContainer, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function() {
                    updateContentJson();
                }
            });
        }

        // Load existing content if editing - NUEVA IMPLEMENTACIÓN
        <?php if ($noticia && $noticia['contenido_json']): ?>
            console.log('🔄 Iniciando carga de contenido existente...');
            
            // Esperar a que todo esté listo
            setTimeout(function() {
                try {
                    const existingContent = JSON.parse(`<?= addslashes($noticia['contenido_json']) ?>`);
                    console.log('📦 Contenido parseado:', existingContent);
                    
                    if (existingContent && existingContent.length > 0) {
                        loadExistingContent(existingContent);
                    } else {
                        console.log('❌ Contenido vacío o inválido');
                    }
                } catch (e) {
                    console.error('❌ Error parsing JSON content:', e);
                }
            }, 1000);
        <?php endif; ?>
    });

    function addBlock(type) {
        blockCounter++;
        const blockId = `block_${blockCounter}`;
        let blockHtml = '';

        // Remove empty state message
        const emptyState = document.querySelector('#contentBlocks .text-center');
        if (emptyState) {
            emptyState.remove();
        }

        switch(type) {
            case 'text':
                blockHtml = createTextBlock(blockId);
                break;
            case 'image':
                blockHtml = createImageBlock(blockId);
                break;
            case 'video':
                blockHtml = createVideoBlock(blockId);
                break;
            case 'quote':
                blockHtml = createQuoteBlock(blockId);
                break;
            case 'list':
                blockHtml = createListBlock(blockId);
                break;
            case 'divider':
                blockHtml = createDividerBlock(blockId);
                break;
        }

        document.getElementById('contentBlocks').insertAdjacentHTML('beforeend', blockHtml);
        
        // Inicializar TinyMCE para el nuevo bloque de texto
        if (type === 'text') {
            initializeTinyMCEForBlock(blockId);
        }
        
        updateContentJson();
    }

    function createTextBlock(blockId, content = {}) {
        return `
            <div class="content-block" data-type="text" data-id="${blockId}">
                <div class="block-toolbar">
                    <span class="block-type"><i class="bi bi-text-paragraph me-1"></i>Texto</span>
                    <div class="block-actions">
                        <button type="button" onclick="moveBlockUp('${blockId}')"><i class="bi bi-arrow-up"></i></button>
                        <button type="button" onclick="moveBlockDown('${blockId}')"><i class="bi bi-arrow-down"></i></button>
                        <button type="button" onclick="removeBlock('${blockId}')"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                <div class="typography-controls mb-2">
                    <select class="font-selector" onchange="updateBlockStyle('${blockId}', 'fontFamily', this.value)">
                        <option value="Arial" ${(content.fontFamily === 'Arial') ? 'selected' : ''}>Arial</option>
                        <option value="Georgia" ${(content.fontFamily === 'Georgia') ? 'selected' : ''}>Georgia</option>
                        <option value="Times New Roman" ${(content.fontFamily === 'Times New Roman') ? 'selected' : ''}>Times New Roman</option>
                        <option value="Roboto" ${(content.fontFamily === 'Roboto') ? 'selected' : ''}>Roboto</option>
                    </select>
                    <select class="font-selector" onchange="updateBlockStyle('${blockId}', 'fontSize', this.value)">
                        <option value="14px" ${(content.fontSize === '14px') ? 'selected' : ''}>14px</option>
                        <option value="16px" ${(!content.fontSize || content.fontSize === '16px') ? 'selected' : ''}>16px</option>
                        <option value="18px" ${(content.fontSize === '18px') ? 'selected' : ''}>18px</option>
                        <option value="20px" ${(content.fontSize === '20px') ? 'selected' : ''}>20px</option>
                    </select>
                </div>
                <textarea class="form-control rich-text-editor" rows="4" placeholder="Escribe tu contenido aquí..." 
                          onchange="updateContentJson()">${content.text || ''}</textarea>
            </div>
        `;
    }

    function createImageBlock(blockId, content = {}) {
        const filenameAttr = content.filename ? `data-filename="${content.filename}"` : '';
        
        let uploadZoneHtml = `<div class="media-upload-zone" onclick="document.getElementById('img_${blockId}').click()">`;
        if (content.filename) {
            uploadZoneHtml += `<img src="../assets/uploads/noticias/${content.filename}" class="media-preview" alt="Imagen actual">`;
        } else {
            uploadZoneHtml += `
                <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #0d6efd;"></i>
                <p class="mb-0">Haz clic para subir imagen</p>
            `;
        }
        uploadZoneHtml += `</div>`;
        
        return `
            <div class="content-block" data-type="image" data-id="${blockId}" ${filenameAttr}>
                <div class="block-toolbar">
                    <span class="block-type"><i class="bi bi-image me-1"></i>Imagen</span>
                    <div class="block-actions">
                        <button type="button" onclick="moveBlockUp('${blockId}')"><i class="bi bi-arrow-up"></i></button>
                        <button type="button" onclick="moveBlockDown('${blockId}')"><i class="bi bi-arrow-down"></i></button>
                        <button type="button" onclick="removeBlock('${blockId}')"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                ${uploadZoneHtml}
                <input type="file" name="block_images[${blockId}]" id="img_${blockId}" accept="image/*" style="display: none;" onchange="handleImageUpload('${blockId}', this)">
                <input type="text" class="form-control mt-2" placeholder="Texto alternativo (alt)" onchange="updateContentJson()" value="${content.alt || ''}">
                <input type="text" class="form-control mt-2" placeholder="Pie de imagen (opcional)" onchange="updateContentJson()" value="${content.caption || ''}">
            </div>
        `;
    }

    function createVideoBlock(blockId, content = {}) {
        const videoType = content.video_type || 'url';
        let urlDisplay = videoType === 'url' ? 'block' : 'none';
        let uploadDisplay = videoType === 'upload' ? 'block' : 'none';
        
        const filenameAttr = content.filename ? `data-filename="${content.filename}"` : '';
        
        let uploadZoneHtml = `<div class="media-upload-zone" onclick="document.getElementById('vid_${blockId}').click()">`;
        if (content.filename) {
            uploadZoneHtml += `
                <video controls class="media-preview">
                    <source src="../assets/uploads/videos/${content.filename}" type="video/mp4">
                </video>
            `;
        } else {
            uploadZoneHtml += `
                <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #0d6efd;"></i>
                <p class="mb-0">Haz clic para subir video</p>
                <small class="text-muted">MP4, WebM, OGV (máx. 50MB)</small>
            `;
        }
        uploadZoneHtml += `</div>`;
        
        return `
            <div class="content-block" data-type="video" data-id="${blockId}" ${filenameAttr}>
                <div class="block-toolbar">
                    <span class="block-type"><i class="bi bi-play-circle me-1"></i>Video</span>
                    <div class="block-actions">
                        <button type="button" onclick="moveBlockUp('${blockId}')"><i class="bi bi-arrow-up"></i></button>
                        <button type="button" onclick="moveBlockDown('${blockId}')"><i class="bi bi-arrow-down"></i></button>
                        <button type="button" onclick="removeBlock('${blockId}')"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo de video:</label>
                    <select class="form-control" onchange="toggleVideoInput('${blockId}', this.value)">
                        <option value="url" ${videoType === 'url' ? 'selected' : ''}>URL de video (YouTube, Vimeo)</option>
                        <option value="upload" ${videoType === 'upload' ? 'selected' : ''}>Subir archivo de video</option>
                    </select>
                </div>
                <div id="video_url_${blockId}" style="display: ${urlDisplay};">
                    <input type="url" class="form-control" placeholder="https://www.youtube.com/watch?v=..." 
                           onchange="updateContentJson()" value="${content.url || ''}">
                </div>
                <div id="video_upload_${blockId}" style="display: ${uploadDisplay};">
                    ${uploadZoneHtml}
                    <input type="file" name="block_videos[${blockId}]" id="vid_${blockId}" accept="video/*" style="display: none;" onchange="handleVideoUpload('${blockId}', this)">
                </div>
            </div>
        `;
    }

    function createQuoteBlock(blockId, content = {}) {
        return `
            <div class="content-block" data-type="quote" data-id="${blockId}">
                <div class="block-toolbar">
                    <span class="block-type"><i class="bi bi-quote me-1"></i>Cita</span>
                    <div class="block-actions">
                        <button type="button" onclick="moveBlockUp('${blockId}')"><i class="bi bi-arrow-up"></i></button>
                        <button type="button" onclick="moveBlockDown('${blockId}')"><i class="bi bi-arrow-down"></i></button>
                        <button type="button" onclick="removeBlock('${blockId}')"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                <textarea class="form-control mb-2" rows="3" placeholder="Texto de la cita..." onchange="updateContentJson()">${content.text || ''}</textarea>
                <input type="text" class="form-control" placeholder="Autor de la cita" onchange="updateContentJson()" value="${content.author || ''}">
            </div>
        `;
    }

    function createListBlock(blockId, content = {}) {
        let listItemsHtml = '';
        const items = content.items || [''];
        
        items.forEach(item => {
            listItemsHtml += `<input type="text" class="form-control mb-2" placeholder="Elemento de lista" onchange="updateContentJson()" value="${item || ''}">`;
        });
        
        return `
            <div class="content-block" data-type="list" data-id="${blockId}">
                <div class="block-toolbar">
                    <span class="block-type"><i class="bi bi-list-ul me-1"></i>Lista</span>
                    <div class="block-actions">
                        <button type="button" onclick="moveBlockUp('${blockId}')"><i class="bi bi-arrow-up"></i></button>
                        <button type="button" onclick="moveBlockDown('${blockId}')"><i class="bi bi-arrow-down"></i></button>
                        <button type="button" onclick="removeBlock('${blockId}')"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Tipo de lista:</label>
                    <select class="form-control" onchange="updateContentJson()">
                        <option value="ul" ${content.type === 'ul' ? 'selected' : ''}>Lista con viñetas</option>
                        <option value="ol" ${content.type === 'ol' ? 'selected' : ''}>Lista numerada</option>
                    </select>
                </div>
                <div id="list_items_${blockId}">
                    ${listItemsHtml}
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addListItem('${blockId}')">
                    <i class="bi bi-plus"></i> Agregar elemento
                </button>
            </div>
        `;
    }

    function createDividerBlock(blockId, content = {}) {
        return `
            <div class="content-block" data-type="divider" data-id="${blockId}">
                <div class="block-toolbar">
                    <span class="block-type"><i class="bi bi-hr me-1"></i>Separador</span>
                    <div class="block-actions">
                        <button type="button" onclick="moveBlockUp('${blockId}')"><i class="bi bi-arrow-up"></i></button>
                        <button type="button" onclick="moveBlockDown('${blockId}')"><i class="bi bi-arrow-down"></i></button>
                        <button type="button" onclick="removeBlock('${blockId}')"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                <hr style="border: 2px solid #dee2e6; margin: 20px 0;">
                <select class="form-control" onchange="updateContentJson()">
                    <option value="solid" ${content.style === 'solid' ? 'selected' : ''}>Línea sólida</option>
                    <option value="dashed" ${content.style === 'dashed' ? 'selected' : ''}>Línea punteada</option>
                    <option value="dotted" ${content.style === 'dotted' ? 'selected' : ''}>Línea de puntos</option>
                </select>
            </div>
        `;
    }

    // NUEVA FUNCIÓN CORREGIDA PARA CARGAR CONTENIDO EXISTENTE
    function loadExistingContent(content) {
        try {
            console.log('🔄 Iniciando loadExistingContent con:', content);
            const contentContainer = document.getElementById('contentBlocks');
            
            if (!contentContainer) {
                console.error('❌ No se encontró el contenedor de bloques');
                return;
            }
            
            // Limpiar contenedor completamente
            contentContainer.innerHTML = '';
            
            // Reset block counter
            blockCounter = 0;
            
            if (!content || content.length === 0) {
                console.log('📭 No hay contenido para cargar');
                showEmptyState();
                return;
            }
            
            console.log(`📦 Cargando ${content.length} bloques...`);
            
            // Crear cada bloque
            content.forEach((blockData, index) => {
                blockCounter++;
                const blockId = `block_${blockCounter}`;
                let blockHtml = '';
                
                console.log(`🔨 Creando bloque ${index + 1}:`, blockData.type, blockData.content);
                
                switch(blockData.type) {
                    case 'text':
                        blockHtml = createTextBlock(blockId, blockData.content || {});
                        break;
                    case 'image':
                        blockHtml = createImageBlock(blockId, blockData.content || {});
                        break;
                    case 'video':
                        blockHtml = createVideoBlock(blockId, blockData.content || {});
                        break;
                    case 'quote':
                        blockHtml = createQuoteBlock(blockId, blockData.content || {});
                        break;
                    case 'list':
                        blockHtml = createListBlock(blockId, blockData.content || {});
                        break;
                    case 'divider':
                        blockHtml = createDividerBlock(blockId, blockData.content || {});
                        break;
                    default:
                        console.warn('⚠️ Tipo de bloque desconocido:', blockData.type);
                        return;
                }
                
                contentContainer.insertAdjacentHTML('beforeend', blockHtml);
                console.log(`✅ Bloque ${index + 1} creado:`, blockData.type);
            });
            
            // Inicializar TinyMCE después de un delay
            setTimeout(() => {
                console.log('🎯 Inicializando TinyMCE para bloques de texto...');
                initializeTinyMCEForExistingBlocks();
                updateContentJson();
                console.log('✅ Carga de contenido completada');
            }, 1500);
            
        } catch (e) {
            console.error('❌ Error en loadExistingContent:', e);
        }
    }

    function showEmptyState() {
        const contentContainer = document.getElementById('contentBlocks');
        if (contentContainer) {
            contentContainer.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bi bi-plus-circle" style="font-size: 2rem;"></i>
                    <p class="mb-0">Haz clic en los botones de arriba para agregar contenido</p>
                </div>
            `;
        }
    }

    function initializeTinyMCEForExistingBlocks() {
        console.log('🔧 Inicializando TinyMCE para bloques existentes...');
        const textBlocks = document.querySelectorAll('.content-block[data-type="text"] textarea');
        
        console.log(`📝 Encontrados ${textBlocks.length} bloques de texto`);
        
        textBlocks.forEach((textarea, index) => {
            const uniqueId = `editor-existing-${Date.now()}-${index}`;
            textarea.id = uniqueId;
            
            console.log(`🔄 Inicializando editor para: ${uniqueId}`);
            
            // Remover cualquier instancia existente de TinyMCE
            if (tinymce.get(uniqueId)) {
                tinymce.get(uniqueId).remove();
            }
            
            // Inicializar nuevo editor
            tinymce.init({
                selector: `#${uniqueId}`,
                height: 200,
                menubar: false,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                setup: function(editor) {
                    editor.on('init', function() {
                        console.log(`✅ Editor ${uniqueId} inicializado`);
                        // El contenido ya está en el textarea, TinyMCE lo carga automáticamente
                    });
                    editor.on('change', function() {
                        updateContentJson();
                    });
                }
            });
        });
    }

    function initializeTinyMCEForBlock(blockId) {
        const textarea = document.querySelector(`[data-id="${blockId}"] textarea`);
        if (textarea && !textarea.id) {
            textarea.id = `editor-${blockId}-${Date.now()}`;
            
            tinymce.init({
                selector: `#${textarea.id}`,
                height: 200,
                menubar: false,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
                content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                setup: function(editor) {
                    editor.on('change', function() {
                        updateContentJson();
                    });
                }
            });
        }
    }
    
    function removeBlock(blockId) {
    if (confirm('¿Estás seguro de eliminar este bloque?')) {
        const block = document.querySelector(`[data-id="${blockId}"]`);
        const blockType = block.dataset.type;
        const filename = block.dataset.filename;
        
        // IMPORTANTE: Si tiene archivo físico, necesitamos notificar al servidor
        if ((blockType === 'image' || blockType === 'video') && filename) {
            // Agregar un campo hidden para notificar la eliminación al servidor
            addFileDeletionMarker(blockType, filename);
        }
        
        // Eliminar editor TinyMCE si existe
        if (blockType === 'text') {
            const textarea = block.querySelector('textarea');
            if (textarea.id && tinymce.get(textarea.id)) {
                tinymce.get(textarea.id).remove();
            }
        }
        
        // Eliminar del DOM
        block.remove();
        updateContentJson();
        
        // Mostrar estado vacío si no hay bloques
        const blocks = document.querySelectorAll('.content-block');
        if (blocks.length === 0) {
            showEmptyState();
        }
    }
}

function addFileDeletionMarker(blockType, filename) {
    // Crear un campo hidden para enviar al servidor
    let container = document.getElementById('deletedFilesContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'deletedFilesContainer';
        container.style.display = 'none';
        document.getElementById('advancedNewsForm').appendChild(container);
    }
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = `deleted_files[${blockType}][]`;
    input.value = filename;
    container.appendChild(input);
}

    function moveBlockUp(blockId) {
        const block = document.querySelector(`[data-id="${blockId}"]`);
        const prevBlock = block.previousElementSibling;
        if (prevBlock && prevBlock.classList.contains('content-block')) {
            block.parentNode.insertBefore(block, prevBlock);
            updateContentJson();
        }
    }

    function moveBlockDown(blockId) {
        const block = document.querySelector(`[data-id="${blockId}"]`);
        const nextBlock = block.nextElementSibling;
        if (nextBlock && nextBlock.classList.contains('content-block')) {
            block.parentNode.insertBefore(nextBlock, block);
            updateContentJson();
        }
    }

    function toggleVideoInput(blockId, type) {
        const urlDiv = document.getElementById(`video_url_${blockId}`);
        const uploadDiv = document.getElementById(`video_upload_${blockId}`);
        
        if (type === 'url') {
            urlDiv.style.display = 'block';
            uploadDiv.style.display = 'none';
        } else {
            urlDiv.style.display = 'none';
            uploadDiv.style.display = 'block';
        }
        updateContentJson();
    }

    function addListItem(blockId) {
        const container = document.getElementById(`list_items_${blockId}`);
        const newItem = document.createElement('input');
        newItem.type = 'text';
        newItem.className = 'form-control mb-2';
        newItem.placeholder = 'Elemento de lista';
        newItem.onchange = updateContentJson;
        container.appendChild(newItem);
    }

    function handleImageUpload(blockId, input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const block = document.querySelector(`[data-id="${blockId}"]`);
                const uploadZone = block.querySelector('.media-upload-zone');
                uploadZone.innerHTML = `<img src="${e.target.result}" class="media-preview" alt="Preview">`;
                block.removeAttribute('data-filename');
                updateContentJson();
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function handleVideoUpload(blockId, input) {
        if (input.files && input.files[0]) {
            const block = document.querySelector(`[data-id="${blockId}"]`);
            const uploadZone = block.querySelector('.media-upload-zone');
            uploadZone.innerHTML = `
                <video controls class="media-preview">
                    <source src="${URL.createObjectURL(input.files[0])}" type="${input.files[0].type}">
                </video>
            `;
            updateContentJson();
        }
    }

    function updateBlockStyle(blockId, styleProperty, value) {
        const block = document.querySelector(`[data-id="${blockId}"]`);
        const textarea = block.querySelector('textarea');
        if (textarea) {
            textarea.style[styleProperty] = value;
        }
    }

    function updateContentJson() {
        const blocks = document.querySelectorAll('.content-block');
        const content = [];
        let textContent = '';
        
        blocks.forEach(block => {
            const blockData = {
                id: block.dataset.id,
                type: block.dataset.type,
                content: {}
            };
            
            switch(block.dataset.type) {
                case 'text':
                    const textarea = block.querySelector('textarea');
                    let textValue = '';
                    if (textarea.classList.contains('rich-text-editor')) {
                        try {
                            textValue = tinymce.get(textarea.id) ? tinymce.get(textarea.id).getContent() : textarea.value;
                        } catch (e) {
                            textValue = textarea.value;
                        }
                    } else {
                        textValue = textarea.value;
                    }
                    blockData.content.text = textValue;
                    textContent += textValue + '\n\n';
                    
                    const fontSelect = block.querySelector('.font-selector');
                    const sizeSelect = block.querySelectorAll('.font-selector')[1];
                    if (fontSelect) blockData.content.fontFamily = fontSelect.value;
                    if (sizeSelect) blockData.content.fontSize = sizeSelect.value;
                    break;
                    
                case 'image':
                    const imgInputs = block.querySelectorAll('input[type="text"]');
                    blockData.content.alt = imgInputs[0]?.value || '';
                    blockData.content.caption = imgInputs[1]?.value || '';
                    if (block.dataset.filename) {
                        blockData.content.filename = block.dataset.filename;
                    }
                    textContent += `[Imagen: ${blockData.content.alt}]\n\n`;
                    break;
                    
                case 'video':
                    const videoSelect = block.querySelector('select');
                    blockData.content.video_type = videoSelect.value;
                    if (blockData.content.video_type === 'url') {
                        blockData.content.url = block.querySelector('input[type="url"]').value || '';
                    }
                    if (block.dataset.filename) {
                        blockData.content.filename = block.dataset.filename;
                    }
                    textContent += `[Video: ${blockData.content.url || 'Video subido'}]\n\n`;
                    break;
                    
                case 'quote':
                    const quoteTextarea = block.querySelector('textarea');
                    const authorInput = block.querySelector('input[type="text"]');
                    blockData.content.text = quoteTextarea?.value || '';
                    blockData.content.author = authorInput?.value || '';
                    textContent += `"${blockData.content.text}" - ${blockData.content.author}\n\n`;
                    break;
                    
                case 'list':
                    const listItems = Array.from(block.querySelectorAll('#list_items_' + block.dataset.id + ' input'))
                                          .map(input => input.value)
                                          .filter(value => value.trim() !== '');
                    blockData.content.items = listItems;
                    blockData.content.type = block.querySelector('select').value;
                    textContent += listItems.map(item => `• ${item}`).join('\n') + '\n\n';
                    break;
                    
                case 'divider':
                    blockData.content.style = block.querySelector('select').value;
                    textContent += '---\n\n';
                    break;
            }
            
            content.push(blockData);
        });
        
        document.getElementById('contenidoJson').value = JSON.stringify(content);
        document.getElementById('contenidoTexto').value = textContent;
        
        console.log('📄 JSON actualizado:', content);
    }

    function saveAsDraft() {
        document.querySelector('select[name="estado"]').value = 'borrador';
        updateContentJson();
        document.getElementById('advancedNewsForm').submit();
    }

    function togglePreview() {
        const panel = document.getElementById('previewPanel');
        panel.classList.toggle('active');
        
        if (panel.classList.contains('active')) {
            generatePreview();
        }
    }

    function generatePreview() {
        const titulo = document.querySelector('input[name="titulo"]').value;
        const subtitulo = document.querySelector('input[name="subtitulo"]').value;
        const blocks = document.querySelectorAll('.content-block');
        
        let previewHtml = `
            <article class="preview-article">
                <h1 style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">${titulo}</h1>
                ${subtitulo ? `<h2 style="font-size: 1.2rem; color: #6c757d; margin-bottom: 2rem;">${subtitulo}</h2>` : ''}
        `;
        
        blocks.forEach(block => {
            const type = block.dataset.type;
            switch(type) {
                case 'text':
                    const textarea = block.querySelector('textarea');
                    let textValue = '';
                    if (textarea.classList.contains('rich-text-editor')) {
                        try {
                            textValue = tinymce.get(textarea.id) ? tinymce.get(textarea.id).getContent() : textarea.value;
                        } catch (e) {
                            textValue = textarea.value;
                        }
                    } else {
                        textValue = textarea.value;
                    }
                    previewHtml += `<div style="margin-bottom: 1rem; line-height: 1.6;">${textValue}</div>`;
                    break;
                case 'image':
                    const imgInputs = block.querySelectorAll('input[type="text"]');
                    const alt = imgInputs[0]?.value || '';
                    const caption = imgInputs[1]?.value || '';
                    
                    if (block.dataset.filename) {
                        previewHtml += `
                            <figure style="margin: 2rem 0;">
                                <img src="../assets/uploads/noticias/${block.dataset.filename}" style="max-width: 100%; border-radius: 8px;" alt="${alt}">
                                ${caption ? `<figcaption style="text-align: center; font-style: italic; margin-top: 0.5rem;">${caption}</figcaption>` : ''}
                            </figure>
                        `;
                    } else {
                        previewHtml += `<p style="color: #6c757d; font-style: italic;">[Imagen: ${alt}]</p>`;
                    }
                    break;
                case 'video':
                    const videoSelect = block.querySelector('select');
                    const videoType = videoSelect.value;
                    
                    if (videoType === 'url') {
                        const url = block.querySelector('input[type="url"]').value || '';
                        if (url) {
                            previewHtml += `
                                <div style="margin: 2rem 0;">
                                    <p>Video embebido: ${url}</p>
                                </div>
                            `;
                        }
                    } else if (block.dataset.filename) {
                        previewHtml += `
                            <div style="margin: 2rem 0;">
                                <video controls style="max-width: 100%;">
                                    <source src="../assets/uploads/videos/${block.dataset.filename}" type="video/mp4">
                                </video>
                            </div>
                        `;
                    }
                    break;
                case 'quote':
                    const quoteText = block.querySelector('textarea').value;
                    const author = block.querySelector('input[type="text"]').value;
                    previewHtml += `
                        <blockquote style="border-left: 4px solid #0d6efd; padding-left: 1rem; margin: 2rem 0; font-style: italic;">
                            <p>"${quoteText}"</p>
                            ${author ? `<cite>— ${author}</cite>` : ''}
                        </blockquote>
                    `;
                    break;
                case 'list':
                    const listItems = Array.from(block.querySelectorAll('#list_items_' + block.dataset.id + ' input'))
                                          .map(input => input.value)
                                          .filter(value => value.trim() !== '');
                    const listType = block.querySelector('select').value;
                    const listTag = listType === 'ol' ? 'ol' : 'ul';
                    previewHtml += `<${listTag} style="margin: 1rem 0;">`;
                    listItems.forEach(item => {
                        previewHtml += `<li style="margin-bottom: 0.5rem;">${item}</li>`;
                    });
                    previewHtml += `</${listTag}>`;
                    break;
                case 'divider':
                    previewHtml += `<hr style="margin: 2rem 0; border: 1px solid #dee2e6;">`;
                    break;
            }
        });
        
        previewHtml += '</article>';
        document.getElementById('previewContent').innerHTML = previewHtml;
    }

    function confirmarEliminacion(id) {
        if (confirm('¿Estás seguro de que deseas eliminar esta noticia?')) {
            window.location.href = `noticias.php?action=delete&id=${id}`;
        }
    }

    // Apply typography changes to title - Solo si los elementos existen
    const titleFont = document.getElementById('titleFont');
    const titleSize = document.getElementById('titleSize');
    const boldTitle = document.getElementById('boldTitle');
    const italicTitle = document.getElementById('italicTitle');

    if (titleFont) {
        titleFont.addEventListener('change', function() {
            const titleInput = document.querySelector('input[name="titulo"]');
            if (titleInput) titleInput.style.fontFamily = this.value;
        });
    }

    if (titleSize) {
        titleSize.addEventListener('change', function() {
            const titleInput = document.querySelector('input[name="titulo"]');
            if (titleInput) titleInput.style.fontSize = this.value;
        });
    }

    if (boldTitle) {
        boldTitle.addEventListener('click', function() {
            const titleInput = document.querySelector('input[name="titulo"]');
            if (titleInput) {
                               titleInput.style.fontWeight = titleInput.style.fontWeight === 'bold' ? 'normal' : 'bold';
                this.classList.toggle('active');
            }
        });
    }

    if (italicTitle) {
        italicTitle.addEventListener('click', function() {
            const titleInput = document.querySelector('input[name="titulo"]');
            if (titleInput) {
                titleInput.style.fontStyle = titleInput.style.fontStyle === 'italic' ? 'normal' : 'italic';
                this.classList.toggle('active');
            }
        });
    }

    // Auto-save functionality
    setInterval(function() {
        if (document.querySelectorAll('.content-block').length > 0) {
            updateContentJson();
        }
    }, 30000);
</script>
</body>
</html>