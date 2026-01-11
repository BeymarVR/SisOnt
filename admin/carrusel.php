<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/carrusel_functions.php';

verificarRol('admin');

$slides = obtenerSlidesCarrusel(); // Sin parámetro = todos (para admin)
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


    <!-- Contenido principal -->
    <main class="main-content" id="mainContent">
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
                <h1 class="page-title">Gestion Carrusel</h1>
            </div>
            <!-- <div class="header-right">
                <a href="../index.php" class="btn-ont info" target="_blank">
                    <i class="bi bi-eye"></i> Ver Sitio
                </a>
            </div> -->
        </header>

        <div class="content-area">

        <!-- Added stats grid for carrusel overview -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-header">
                    <div class="stat-icon primary">
                        <i class="bi bi-images"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <h3><?= count($slides) ?></h3>
                    <p>Total Slides</p>
                </div>
            </div>
            <div class="stat-card success">
                <div class="stat-header">
                    <div class="stat-icon success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <h3><?= count(array_filter($slides, fn($s) => $s['activo'])) ?></h3>
                    <p>Activos</p>
                </div>
            </div>
            <div class="stat-card warning">
                <div class="stat-header">
                    <div class="stat-icon warning">
                        <i class="bi bi-pause-circle"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <h3><?= count(array_filter($slides, fn($s) => !$s['activo'])) ?></h3>
                    <p>Inactivos</p>
                </div>
            </div>
            <div class="stat-card info">
                <div class="stat-header">
                    <div class="stat-icon info">
                        <i class="bi bi-link-45deg"></i>
                    </div>
                </div>
                <div class="stat-content">
                    <h3><?= count(array_filter($slides, fn($s) => !empty($s['url_boton_1']) || !empty($s['url_boton_2']))) ?></h3>
                    <p>Con Enlaces</p>
                </div>
            </div>
        </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                        <h2 class="content-title">Lista de Carrusel</h2>
                        <p class="content-subtitle">Gestiona los carruseles del sistema</p>
                    </div>
                <a href="carrusel_editar.php" class="btn btn-ont primary">
                    <i class="bi bi-plus-circle"></i> Nuevo Slide
                </a>
            </div>

            <div class="data-table">
    <div class="table-header d-flex justify-content-between align-items-center mb-3">
        <h5><i class="bi bi-images me-2"></i>Lista de Slides</h5>
        <div class="table-actions">
            <input type="text" class="search-input" placeholder="Buscar slides..." 
                   data-target=".data-table table tbody" 
                   style="padding: 0.375rem 0.75rem; border: 1px solid #dee2e6; border-radius: 6px;">
        </div>
    </div>

    <?php if (empty($slides)): ?>
        <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-circle"></i> No hay slides cargados aún.
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Título</th>
                    <th>Subtítulo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slides as $slide): ?>
                    <tr class="searchable-item">
                        <td>
                            <img src="../assets/uploads/carrusel/<?= htmlspecialchars($slide['imagen']) ?>"
                                 alt="<?= htmlspecialchars($slide['titulo']) ?>"
                                 style="width: 100px; height: auto; object-fit: cover; border-radius: 6px;">
                        </td>
                        <td class="fw-semibold"><?= htmlspecialchars($slide['titulo']) ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars(truncateText($slide['subtitulo'], 50)) ?></small></td>
                        <td>
                            <span class="badge-ont <?= $slide['activo'] ? 'success' : 'warning' ?>">
                                <?= $slide['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="carrusel_editar.php?id=<?= $slide['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="carrusel_eliminar.php?id=<?= $slide['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger" title="Eliminar"
                                   onclick="return confirm('¿Estás seguro de eliminar este slide?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
