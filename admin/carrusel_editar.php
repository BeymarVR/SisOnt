<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/carrusel_functions.php';

// Verificar sesión y rol usando tu función consistente
verificarRol('admin');

$slide = ['id' => 0, 'titulo' => '', 'subtitulo' => '', 'imagen' => '', 
          'texto_boton_1' => '', 'url_boton_1' => '', 'texto_boton_2' => '', 
          'url_boton_2' => '', 'activo' => 1];

if (isset($_GET['id'])) {
    $slide = obtenerSlidePorId($_GET['id']);
    if (!$slide) {
        header("Location: carrusel.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id' => $_POST['id'] ?? 0,
        'titulo' => $_POST['titulo'],
        'subtitulo' => $_POST['subtitulo'],
        'texto_boton_1' => $_POST['texto_boton_1'],
        'url_boton_1' => $_POST['url_boton_1'],
        'texto_boton_2' => $_POST['texto_boton_2'],
        'url_boton_2' => $_POST['url_boton_2'],
       
        'activo' => isset($_POST['activo']) ? 1 : 0,
        'usuario_id' => $_SESSION['user_id']
    ];

    // Manejar la imagen
    if (!empty($_FILES['imagen']['name'])) {
        $imagen = subirImagenCarrusel($_FILES['imagen']);
        if ($imagen) {
            // Eliminar imagen anterior si existe
            if ($slide['imagen'] && file_exists("../assets/uploads/carrusel/{$slide['imagen']}")) {
                unlink("../assets/uploads/carrusel/{$slide['imagen']}");
            }
            $data['imagen'] = $imagen;
        }
    } else {
        $data['imagen'] = $slide['imagen'];
    }

    if ($data['id'] > 0) {
        actualizarSlide($data);
    } else {
        agregarSlide($data);
    }

    header("Location: carrusel.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - ONT Bolivia</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>


  <?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="main-content" id="mainContent">

<div class="container py-4">
    <h1><?= $slide['id'] > 0 ? 'Editar' : 'Agregar' ?> Slide del Carrusel</h1>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $slide['id'] ?>">
        
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   value="<?= htmlspecialchars($slide['titulo']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subtitulo" class="form-label">Subtítulo</label>
                            <textarea class="form-control" id="subtitulo" name="subtitulo" 
                                      rows="3"><?= htmlspecialchars($slide['subtitulo']) ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="texto_boton_1" class="form-label">Texto Botón 1</label>
                                <input type="text" class="form-control" id="texto_boton_1" name="texto_boton_1" 
                                       value="<?= htmlspecialchars($slide['texto_boton_1']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="url_boton_1" class="form-label">URL Botón 1</label>
                                <input type="url" class="form-control" id="url_boton_1" name="url_boton_1" 
                                       value="<?= htmlspecialchars($slide['url_boton_1']) ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="texto_boton_2" class="form-label">Texto Botón 2</label>
                                <input type="text" class="form-control" id="texto_boton_2" name="texto_boton_2" 
                                       value="<?= htmlspecialchars($slide['texto_boton_2']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="url_boton_2" class="form-label">URL Botón 2</label>
                                <input type="url" class="form-control" id="url_boton_2" name="url_boton_2" 
                                       value="<?= htmlspecialchars($slide['url_boton_2']) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="imagen" class="form-label">Imagen</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" 
                                   accept="image/*" <?= $slide['id'] == 0 ? 'required' : '' ?>>
                            <?php if ($slide['imagen']): ?>
                                <div class="mt-2">
                                    <img src="../assets/uploads/carrusel/<?= $slide['imagen'] ?>" 
                                         alt="Imagen actual" class="img-thumbnail" style="max-height: 200px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                           <?= $slide['activo'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="activo">Activo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-between">
            <a href="carrusel.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-ont-primary">
                <i class="bi bi-save"></i> Guardar
            </button>
        </div>
    </form>
</div>
</main>
</body>