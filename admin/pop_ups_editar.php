<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

verificarRol('admin');

$conexion = obtenerConexion();
$popup = null;
$isEdit = false;

// Si hay ID, es edición
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conexion->prepare("SELECT * FROM pop_ups WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $popup = $stmt->get_result()->fetch_assoc();
    $isEdit = true;
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $descripcion = $_POST['descripcion'];
    $tipo = $_POST['tipo'];
    $activo = $_POST['activo'];
    $fecha_inicio = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
    $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;
    $mostrar_una_vez = isset($_POST['mostrar_una_vez']) ? 1 : 0;
    $posicion = $_POST['posicion'];
    $ancho = $_POST['ancho'];
    $alto = $_POST['alto'];
    
    // --- AQUÍ DEBE IR LA LÓGICA PARA ELIMINAR ARCHIVO ANTERIOR ---
    if ($isEdit && $popup) {
        // Si se está subiendo una nueva imagen, eliminar la anterior
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            // Eliminar archivo anterior si existe
            if (!empty($popup['archivo'])) {
                $rutaAnterior = $_SERVER['DOCUMENT_ROOT'] . '/SIS_ONT/assets/uploads/popups/' . $popup['archivo'];
                if (file_exists($rutaAnterior) && is_file($rutaAnterior)) {
                    unlink($rutaAnterior);
                }
            }
        }
    }

    // Variables según el tipo
    $contenido = $_POST['contenido'] ?? '';
    $url_externa = $_POST['url_externa'] ?? '';
    $nombreArchivo = $popup['archivo']; // Mantener archivo actual por defecto
    
    // Procesar según el tipo
    if ($tipo === 'imagen') {
        // Si se subió una nueva imagen
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/SIS_ONT/assets/uploads/popups/';
            
            // Validar que sea una imagen
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['archivo']['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                die("Error: Solo se permiten imágenes JPEG, PNG, GIF y WebP.");
            }
            
            // Generar nombre único
            $extension = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
            $nombreBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($_FILES['archivo']['name'], PATHINFO_FILENAME));
            $fileName = uniqid() . '_' . $nombreBase . '.' . $extension;
            $uploadPath = $uploadDir . $fileName;
            
            // Mover el archivo
            if (move_uploaded_file($_FILES['archivo']['tmp_name'], $uploadPath)) {
                // Eliminar imagen anterior si existe
                if (!empty($popup['archivo']) && file_exists($uploadDir . $popup['archivo'])) {
                    unlink($uploadDir . $popup['archivo']);
                }
                $nombreArchivo = $fileName;
            } else {
                die("Error al subir la imagen.");
            }
        }
    } 
    elseif ($tipo === 'video') {
        // Para video, procesar la URL
        if (!empty($_POST['url_externa'])) {
            $url_externa = convertirUrlVideo($_POST['url_externa']);
        }
    }
    elseif ($tipo === 'texto') {
        // Para texto, usar el contenido
        $contenido = $_POST['contenido'];
    }
    
    // Actualizar en la base de datos
    $stmt = $conexion->prepare("UPDATE pop_ups SET titulo=?, descripcion=?, tipo=?, contenido=?, url_externa=?, archivo=?, activo=?, fecha_inicio=?, fecha_fin=?, mostrar_una_vez=?, posicion=?, ancho=?, alto=? WHERE id=?");
    $stmt->bind_param("ssssssississii", $titulo, $descripcion, $tipo, $contenido, $url_externa, $nombreArchivo, $activo, $fecha_inicio, $fecha_fin, $mostrar_una_vez, $posicion, $ancho, $alto, $id);
    
    if ($stmt->execute()) {
        header("Location: pop_ups.php?success=edit");
        exit;
    } else {
        die("Error al actualizar el pop-up: " . $conexion->error);
    }
}

// Función para convertir URLs de video
function convertirUrlVideo($url) {
    // YouTube
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    if (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $url;
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/([0-9]+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    
    return $url;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Editar' : 'Crear' ?> Pop-up - Panel ONT</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar (copia el mismo sidebar de pop_ups.php) -->
    
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content" id="mainContent">
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title"><?= $isEdit ? 'Editar' : 'Crear' ?> Pop-up</h1>
            </div>
            <div class="header-right">
                <a href="pop_ups.php" class="btn-ont secondary">
                    <i class="bi bi-arrow-left"></i>
                    Volver
                </a>
            </div>
        </header>

        <div class="content-area">
            <form method="post" enctype="multipart/form-data">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= $popup['id'] ?>">
                    
                <?php endif; ?>

                
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5><i class="bi bi-info-circle me-2"></i>Información Básica</h5>
                            </div>
                            <div class="form-card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label class="form-label">Título *</label>
                                            <input type="text" name="titulo" class="form-control" 
                                                   value="<?= htmlspecialchars($popup['titulo'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label">Tipo *</label>
                                            <select name="tipo" class="form-control" required onchange="toggleContentFields(this.value)">
                                                <option value="">Seleccionar tipo</option>
                                                <option value="texto" <?= ($popup['tipo'] ?? '') === 'texto' ? 'selected' : '' ?>>Texto</option>
                                                <option value="imagen" <?= ($popup['tipo'] ?? '') === 'imagen' ? 'selected' : '' ?>>Imagen</option>
                                                <option value="video" <?= ($popup['tipo'] ?? '') === 'video' ? 'selected' : '' ?>>Video</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Descripción</label>
                                    <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($popup['descripcion'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-card">
                            <div class="form-card-header">
                                <h5><i class="bi bi-file-earmark me-2"></i>Contenido</h5>
                            </div>
                            <div class="form-card-body">
                                <div id="contenido-texto" class="content-field" style="display: <?= ($popup['tipo'] ?? '') === 'texto' ? 'block' : 'none' ?>;">
                                    <div class="form-group">
                                        <label class="form-label">Contenido (HTML permitido) *</label>
                                        <textarea name="contenido" class="form-control" rows="6"><?= htmlspecialchars($popup['contenido'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <div id="contenido-imagen" class="content-field" style="display: <?= ($popup['tipo'] ?? '') === 'imagen' ? 'block' : 'none' ?>;">
                                    <div class="form-group">
                                        <label class="form-label"><?= $isEdit ? 'Cambiar imagen' : 'Subir imagen' ?> <?= !$isEdit ? '*' : '' ?></label>
                                        <input type="file" name="archivo" class="form-control" accept="image/*" <?= !$isEdit ? 'required' : '' ?>>
                                        <?php if ($isEdit && $popup['tipo'] === 'imagen' && !empty($popup['archivo'])): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">Imagen actual:</small>
                                                <div>
                                                    <img src="../assets/uploads/popups/<?= htmlspecialchars($popup['archivo']) ?>" 
                                                         style="max-width: 200px; max-height: 150px; object-fit: contain;" 
                                                         class="border p-1 mt-1">
                                                </div>
                                                <small class="text-muted"><?= htmlspecialchars($popup['archivo']) ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div id="contenido-video" class="content-field" style="display: <?= ($popup['tipo'] ?? '') === 'video' ? 'block' : 'none' ?>;">
                                    <div class="form-group">
                                        <label class="form-label">URL del video (YouTube, Vimeo, etc.) *</label>
                                        <input type="url" name="url_externa" class="form-control" 
                                               value="<?= htmlspecialchars($popup['url_externa'] ?? '') ?>"
                                               placeholder="https://www.youtube.com/watch?v=ABCDE o https://vimeo.com/12345">
                                        <small class="text-muted">El sistema convertirá automáticamente a formato embed</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-card">
                            <div class="form-card-header">
                                <h5><i class="bi bi-gear me-2"></i>Configuración</h5>
                            </div>
                            <div class="form-card-body">
                                <div class="form-group">
                                    <label class="form-label">Estado</label>
                                    <select name="activo" class="form-control">
                                        <option value="1" <?= ($popup['activo'] ?? 1) ? 'selected' : '' ?>>Activo</option>
                                        <option value="0" <?= !($popup['activo'] ?? 1) ? 'selected' : '' ?>>Inactivo</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Fecha de inicio</label>
                                    <input type="date" name="fecha_inicio" class="form-control" 
                                           value="<?= $popup['fecha_inicio'] ?? '' ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Fecha de fin</label>
                                    <input type="date" name="fecha_fin" class="form-control" 
                                           value="<?= $popup['fecha_fin'] ?? '' ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Posición</label>
                                    <select name="posicion" class="form-control">
                                        <option value="centro" <?= ($popup['posicion'] ?? 'centro') === 'centro' ? 'selected' : '' ?>>Centro</option>
                                        <option value="esquina-superior-derecha" <?= ($popup['posicion'] ?? '') === 'esquina-superior-derecha' ? 'selected' : '' ?>>Esquina Superior Derecha</option>
                                        <option value="esquina-inferior-derecha" <?= ($popup['posicion'] ?? '') === 'esquina-inferior-derecha' ? 'selected' : '' ?>>Esquina Inferior Derecha</option>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="form-label">Ancho (px)</label>
                                            <input type="number" name="ancho" class="form-control" 
                                                   value="<?= $popup['ancho'] ?? 500 ?>" min="200" max="1000">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label class="form-label">Alto (px)</label>
                                            <input type="number" name="alto" class="form-control" 
                                                   value="<?= $popup['alto'] ?? 400 ?>" min="200" max="800">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" name="mostrar_una_vez" class="form-check-input" 
                                               id="mostrarUnaVez" value="1" <?= ($popup['mostrar_una_vez'] ?? 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="mostrarUnaVez">
                                            Mostrar solo una vez por usuario
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-ont primary">
                                <i class="bi bi-check-lg"></i>
                                <?= $isEdit ? 'Actualizar' : 'Crear' ?> Pop-up
                            </button>
                            <a href="pop_ups.php" class="btn-ont secondary">
                                <i class="bi bi-x-lg"></i>
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleContentFields(tipo) {
            document.querySelectorAll('.content-field').forEach(field => field.style.display = 'none');
            if (tipo) {
                const field = document.getElementById('contenido-' + tipo);
                if (field) field.style.display = 'block';
            }

            // Valores por defecto
            if (tipo === 'video') {
                document.querySelector('input[name="ancho"]').value = 800;
                document.querySelector('input[name="alto"]').value = 450;
            } else if (tipo === 'texto') {
                document.querySelector('input[name="ancho"]').value = 600;
                document.querySelector('input[name="alto"]').value = 400;
            } else if (tipo === 'imagen') {
                document.querySelector('input[name="ancho"]').value = 500;
                document.querySelector('input[name="alto"]').value = 400;
            }
        }

        // Inicializar campos al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const tipoSelect = document.querySelector('select[name="tipo"]');
            if (tipoSelect.value) {
                toggleContentFields(tipoSelect.value);
            }
        });
    </script>
</body>
</html>