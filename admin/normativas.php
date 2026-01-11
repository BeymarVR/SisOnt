<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/normativas_functions.php';
verificarRol('admin');

// Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    
    if (eliminarNormativa($id)) {
        $_SESSION['mensaje'] = "Estudio eliminado correctamente";
        $_SESSION['tipo_mensaje'] = "danger";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar el Estudio";
        $_SESSION['tipo_mensaje'] = "danger";
    }
    
    header("Location: normativas.php");
    exit();
}

// Manejar acciones
$action = $_GET['action'] ?? 'list';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'new' || $action === 'edit') {
        $titulo = $_POST['titulo'];
        $descripcion = $_POST['descripcion'];
        // $categoria = $_POST['categoria'] ?? 'general';
        $estado = $_POST['estado'] ?? 'activo';

        // Subir archivo PDF
$archivo = null;
if ($_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['application/pdf'];
    $fileType = $_FILES['archivo']['type'];
    
    if (in_array($fileType, $allowedTypes)) {
        // Validar tamaño - AUMENTADO A 100MB
        $maxSize = 100 * 1024 * 1024; // 100MB en bytes
        if ($_FILES['archivo']['size'] > $maxSize) {
            $message = 'El archivo es demasiado grande. Máximo 100MB permitido.';
            $messageType = 'error';
        } else {
            $extension = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
            $nombreArchivo = uniqid() . '.' . $extension;
            $rutaArchivo = __DIR__ . '/../assets/uploads/normativas/' . $nombreArchivo;
            
            if (move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaArchivo)) {
                $archivo = $nombreArchivo;
            }
        }
    } else {
        $message = 'Solo se permiten archivos PDF';
        $messageType = 'error';
    }
}

        if ($messageType !== 'error') {
            $conexion = obtenerConexion();
            
            if ($action === 'new') {
    $stmt = $conexion->prepare("
        INSERT INTO normativas 
        (titulo, descripcion, archivo, estado, usuario_id) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssi", $titulo, $descripcion, $archivo, $estado, $_SESSION['user_id']);
    $stmt->execute();
    $message = 'Estudio creado exitosamente';
    $messageType = 'success';
} elseif ($action === 'edit') {
    $id = $_POST['id'];
    if ($archivo) {
        $stmt = $conexion->prepare("
            UPDATE normativas 
            SET titulo = ?, descripcion = ?, archivo = ?, estado = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssi", $titulo, $descripcion, $archivo, $estado, $id);
    } else {
        $stmt = $conexion->prepare("
            UPDATE normativas 
            SET titulo = ?, descripcion = ?, estado = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $titulo, $descripcion, $estado, $id);
    }
    $stmt->execute();
    $message = 'Estudio actualizado exitosamente';
    $messageType = 'success';
}

            
            if ($messageType === 'success') {
                header("Location: normativas.php?message=" . urlencode($message) . "&type=" . $messageType);
                exit();
            }
        }
    }
}

// Obtener datos para edición
$normativa = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conexion = obtenerConexion();
    $stmt = $conexion->prepare("SELECT * FROM normativas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $normativa = $result->fetch_assoc();
}

// Obtener lista de normativas
$normativas = obtenerResultados("
    SELECT n.*, u.nombre as autor_nombre 
    FROM normativas n 
    LEFT JOIN usuarios u ON n.usuario_id = u.id 
    ORDER BY n.fecha_publicacion DESC
");

// Mensajes de la URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'info';
}

// Categorías disponibles
// $categorias = [
//     'general' => 'General',
//     'laboral' => 'Laboral',
//     'seguridad' => 'Seguridad Social',
//     'tributario' => 'Tributario',
//     'comercial' => 'Comercial',
//     'ambiental' => 'Ambiental'
// ];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudios - ONT Bolivia</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
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
                        Nuevo Estudio
                    <?php elseif ($action === 'edit'): ?>
                        Editar Estudio
                    <?php else: ?>
                        Gestión de Estudios
                    <?php endif; ?>
                </h1>
            </div>
            <!-- <div class="header-right">
                <?php if ($action === 'list'): ?>
                    <a href="normativas.php?action=new" class="btn-ont primary">
                        <i class="bi bi-plus"></i>
                        Nueva Normativa
                    </a>
                <?php else: ?>
                    <a href="normativas.php" class="btn-ont info">
                        <i class="bi bi-arrow-left"></i>
                        Volver a Lista
                    </a>
                <?php endif; ?>
            </div> -->
        </header>



        
        <div class="content-area">
             

             <!-- Mostrar mensajes -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?= $_SESSION['tipo_mensaje'] ?> alert-dismissible fade show">
                    <?= $_SESSION['mensaje'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
            <?php endif; ?>
            
             <!-- Added stats grid for Estudios overview -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-header">
                        <div class="stat-icon primary">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?= count($normativas) ?></h3>
                        <p>Total Estudios</p>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($normativas, fn($n) => $n['estado'] === 'activo')) ?></h3>
                        <p>Activas</p>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-icon warning">
                            <i class="bi bi-pause-circle"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($normativas, fn($n) => $n['estado'] === 'inactivo')) ?></h3>
                        <p>Inactivas</p>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-icon info">
                            <i class="bi bi-download"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($normativas, fn($n) => !empty($n['archivo']))) ?></h3>
                        <p>Con Archivo</p>
                    </div>
                </div>
            </div>
              <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="content-title">Lista de Estudios</h2>
                    <p class="content-subtitle">Gestiona los Estudios del sistema</p>
                </div>
                <a href="normativas.php?action=new" class="btn btn-ont primary">
                    <i class="bi bi-plus-circle"></i> Nuevo Estudio
                </a>
            </div>
            <?php if ($message): ?>
                <div class="alert-ont <?= $messageType ?> auto-hide">
                    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                    <div>
                        <strong><?= ucfirst($messageType) ?></strong>
                        <p class="mb-0"><?= htmlspecialchars($message) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($action === 'new' || $action === 'edit'): ?>
                 Formulario de Estudios 
                <div class="form-card">
                    <h4>
                        <i class="bi bi-<?= $action === 'new' ? 'plus-circle' : 'pencil-square' ?> me-2"></i>
                        <?= $action === 'new' ? 'Crear Nuevo Estudio' : 'Editar Estudio' ?>
                    </h4>
                    
                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $normativa['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <label class="form-label">Título *</label>
                                    <input type="text" name="titulo" class="form-control" 
                                           value="<?= $normativa ? htmlspecialchars($normativa['titulo']) : '' ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Por favor ingrese un título para el Estudio.
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Descripción *</label>
                                    <textarea name="descripcion" class="form-control" rows="8" required><?= $normativa ? htmlspecialchars($normativa['descripcion']) : '' ?></textarea>
                                    <div class="invalid-feedback">
                                        Por favor ingrese la descripción de el Estudio.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <!-- <label class="form-label">Categoría</label> -->
                                    <!-- <select name="categoria" class="form-control">
                                        <?php //foreach ($categorias as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= ($normativa && $normativa['categoria'] === $value) ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?//php endforeach; ?>
                                    </select> -->
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Estado</label>
                                    <select name="estado" class="form-control">
                                        <option value="activo" <?= ($normativa && $normativa['estado'] === 'activo') ? 'selected' : '' ?>>Activo</option>
                                        <option value="inactivo" <?= ($normativa && $normativa['estado'] === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Archivo PDF <?= $action === 'new' ? '*' : '' ?></label>
                                    <div class="file-upload">
                                        <input type="file" name="archivo" accept="application/pdf" <?= $action === 'new' ? 'required' : '' ?> style="display: none;">
                                        <div class="text-center">
                                            <i class="bi bi-file-earmark-pdf" style="font-size: 2rem; color: var(--primary-orange);"></i>
                                            <p class="mb-0">Haz clic o arrastra un archivo PDF aquí</p>
                                            <small class="text-muted">Solo archivos PDF (máx. 10MB)</small>
                                        </div>
                                        <div class="file-info mt-2"></div>
                                    </div>
                                    <?php if ($normativa && $normativa['archivo']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Archivo actual:</small><br>
                                            <a href="../assets/uploads/normativas/<?= htmlspecialchars($normativa['archivo']) ?>" 
                                               target="_blank" class="btn-ont info" style="font-size: 0.875rem; padding: 0.375rem 0.75rem;">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                                Ver archivo actual
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn-ont primary">
                                        <i class="bi bi-<?= $action === 'new' ? 'plus' : 'check' ?>"></i>
                                        <?= $action === 'new' ? 'Crear Estudio' : 'Actualizar Estudio' ?>
                                    </button>
                                    <a href="normativas.php" class="btn-ont secondary">
                                        <i class="bi bi-x"></i>
                                        Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

            <?php else: ?>
             
                <div class="data-table">
                    <div class="table-header d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="bi bi-file-earmark-text me-2"></i>Lista de Estudios</h5>
                        <div class="table-actions">
                            <input type="text" class="search-input" placeholder="Buscar Estudios..." 
                                   data-target=".data-table table tbody" style="padding: 0.375rem 0.75rem; border: 1px solid #dee2e6; border-radius: 6px;">
                        </div>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <!-- <th>Categoría</th> -->
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Autor</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($normativas as $normativa): ?>
                                <tr class="searchable-item">
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($normativa['titulo']) ?></div>
                                        <small class="text-muted"><?= substr(strip_tags($normativa['descripcion']), 0, 80) ?>...</small>
                                    </td>
                                    <!-- <td>
                                         <span class="badge-ont info">
                                            <//?= ucfirst($categorias[$normativa['categoria']] ?? $normativa['categoria']) ?>
                                        </span>
                                    </td>  -->
                                    <td>
                                        
                                        <span class="badge-ont <?= $normativa['estado'] === 'activo' ? 'success' : 'warning' ?>"> 
                                            <?= ucfirst($normativa['estado']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($normativa['fecha_publicacion'])) ?></td>
                                    <td><?= htmlspecialchars($normativa['autor_nombre'] ?? 'Sin autor') ?></td>
                                    <td>
                                         <div class="d-flex gap-1">
                                            <a href="../assets/uploads/normativas/<?= $normativa['archivo'] ?>" 
                                               class="btn btn-sm btn-outline-info" title="Descargar" target="_blank">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <a href="normativas.php?action=edit&id=<?= $normativa['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    title="Eliminar"
                                                    onclick="confirmarEliminacion(<?= $normativa['id'] ?>, '<?= htmlspecialchars($normativa['titulo']) ?>')">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        function confirmarEliminacion(id, titulo) {
            if (confirm(`¿Estás seguro de eliminar El Estudio "${titulo}"?\nEsta acción no se puede deshacer.`)) {
                window.location.href = `normativas.php?eliminar=${id}`;
            }
        }
        // Validación específica para archivos PDF
document.addEventListener('DOMContentLoaded', function() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Validar tipo de archivo
                if (file.type !== 'application/pdf') {
                    alert('Solo se permiten archivos PDF');
                    this.value = '';
                    return;
                }
                
                // Validar tamaño - AUMENTADO A 100MB
                $maxSize = 100 * 1024 * 1024; // 100MB en bytes
                if ($_FILES['archivo']['size'] > $maxSize) {
                    $fileSizeMB = round($_FILES['archivo']['size'] / (1024 * 1024), 2);
                    $message = "El archivo es demasiado grande ({$fileSizeMB} MB). Máximo 100MB permitido.";
                    $messageType = 'error';
                }
                
                // Mostrar tamaño en formato legible
                const fileSize = (file.size / (1024 * 1024)).toFixed(2);
                console.log(`Archivo seleccionado: ${file.name} (${fileSize} MB)`);
            }
        });
    });
});
    </script>
</body>
</html>
