<?php
require_once './includes/auth.php';
require_once './includes/database.php';

// Asegurarse de que la conexión a la base de datos esté establecida
$conexion = obtenerConexion();
if (!$conexion) {
    die("Error: No se pudo conectar a la base de datos.");
}

// Configuración de paginación
$porPagina = 9;
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina - 1) * $porPagina;

// Variables para filtros
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'fecha';

// Construir query dinámica
$where = "WHERE estado = 'activo'";
$params = [];
$types = "";

// Filtro de búsqueda
if (!empty($busqueda)) {
    $where .= " AND (titulo LIKE ? OR descripcion LIKE ?)";
    $searchTerm = "%{$busqueda}%";
    $params = [$searchTerm, $searchTerm];
    $types = "ss";
}

// Ordenamiento
$orderBy = "ORDER BY fecha_publicacion DESC";
switch ($orden) {
    case 'titulo':
        $orderBy = "ORDER BY titulo ASC";
        break;
    case 'reciente':
        $orderBy = "ORDER BY fecha_publicacion DESC";
        break;
    case 'antiguo':
        $orderBy = "ORDER BY fecha_publicacion ASC";
        break;
}

// Obtener total de normativas con filtros
$countQuery = "SELECT COUNT(*) as total FROM normativas {$where}";
$countStmt = $conexion->prepare($countQuery);

if (!$countStmt) {
    die("Error en la preparación de la consulta: " . $conexion->error);
}

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalResult = $countStmt->get_result()->fetch_assoc();
$totalNormativas = $totalResult['total'];
$totalPaginas = ceil($totalNormativas / $porPagina);

// Obtener normativas para la página actual
$query = "SELECT * FROM normativas {$where} {$orderBy} LIMIT ? OFFSET ?";
$stmt = $conexion->prepare($query);

if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conexion->error);
}

// Agregar parámetros de paginación
$limitOffset = [$porPagina, $offset];
$limitTypes = "ii";

if (!empty($params)) {
    $allParams = array_merge($params, $limitOffset);
    $allTypes = $types . $limitTypes;
    $stmt->bind_param($allTypes, ...$allParams);
} else {
    $stmt->bind_param($limitTypes, ...$limitOffset);
}

$stmt->execute();
$normativas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function truncateText($text, $length = 120) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudios - ONT Bolivia</title>
    <meta name="description" content="Estudios laborales y documentos relevantes del Observatorio Nacional del Trabajo de Bolivia">
    <link rel="icon" type="image/png" href="assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    
    <style>
        .normativa-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            height: 100%;
            cursor: pointer;
            position: relative;
            background: white;
        }
        
        .normativa-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(53, 47, 98, 0.05) 0%, rgba(228, 85, 4, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 1;
        }
        
        .normativa-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 40px rgba(53, 47, 98, 0.2);
        }
        
        .normativa-card:hover::before {
            opacity: 1;
        }
        
        .normativa-card:active {
            transform: translateY(-8px) scale(1.01);
        }
        
        .card-pdf-preview {
            position: relative;
            height: 280px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-bottom: 3px solid #e45504;
        }
        
        .pdf-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: white;
        }
        
        .pdf-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #666;
            font-size: 0.9rem;
            z-index: 1;
        }
        
        .pdf-loading i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .pdf-preview-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.7) 100%);
            display: flex;
            align-items: flex-end;
            padding: 1.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
        }
        
        .normativa-card:hover .pdf-preview-overlay {
            opacity: 1;
        }
        
        .preview-icon-container {
            position: relative;
            width: 140px;
            height: 180px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            transition: transform 0.3s ease;
        }
        
        .normativa-card:hover .preview-icon-container {
            transform: scale(1.05);
        }
        
        .preview-icon-container::before {
            content: '';
            position: absolute;
            top: -5px;
            left: 10px;
            right: 10px;
            height: 5px;
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            border-radius: 8px 8px 0 0;
        }
        
        .preview-icon-container i {
            font-size: 4rem;
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .preview-pages {
            position: absolute;
            bottom: 10px;
            font-size: 0.7rem;
            color: #666;
            font-weight: 500;
        }
        
        .download-indicator {
            color: white;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .download-indicator i {
            font-size: 1.2rem;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .card-body {
            padding: 1.5rem;
            position: relative;
            z-index: 2;
        }
        
        .card-title {
            color: #352f62;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.8rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 50px;
        }
        
        .card-text {
            color: #666;
            line-height: 1.6;
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        
        .card-footer {
            background: transparent;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            padding: 1rem 1.5rem;
            position: relative;
            z-index: 2;
        }
        
        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .date-badge {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: #666;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .date-badge i {
            color: #e45504;
            font-size: 1rem;
        }
        
        .category-badge {
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            color: white;
            font-size: 0.75rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            padding: 120px 0;
        }
        
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .result-count {
            color: #352f62;
            font-weight: 500;
            margin-bottom: 1rem;
        }
        
        .filters-active {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            border-radius: 6px;
            margin-top: 15px;
        }
        
        .btn-ont-primary {
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            border: none;
            color: white;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .btn-ont-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(53, 47, 98, 0.3);
            color: white;
        }
        
        .pagination .page-link {
            color: #352f62;
            border: 1px solid #dee2e6;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #352f62;
            border-color: #352f62;
        }
        
        .pagination .page-link:hover {
            background-color: rgba(53, 47, 98, 0.1);
            color: #352f62;
        }
        
        .click-hint {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.95);
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #352f62;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 3;
        }
        
        .normativa-card:hover .click-hint {
            opacity: 1;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
        }

        
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include './partials/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 mb-3 text-white">
                        <i class="bi bi-file-earmark-text me-3"></i>Estudios
                    </h1>
                    <p class="lead text-white">Accede a los estudios laborales y documentos relevantes del Observatorio Nacional del Trabajo</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container py-5">
        <!-- Filtros y Búsqueda -->
        <div class="filter-section">
            <form method="GET" action="normativas.php" class="row g-3 align-items-end">
                <!-- Búsqueda -->
                <div class="col-md-5">
                    <label for="buscar" class="form-label">
                        <i class="bi bi-search me-2"></i>Buscar estudio
                    </label>
                    <input type="text" id="buscar" name="buscar" class="form-control" 
                           placeholder="Por título o descripción..." 
                           value="<?= htmlspecialchars($busqueda) ?>">
                </div>

                <!-- Ordenar -->
                <div class="col-md-4">
                    <label for="orden" class="form-label">
                        <i class="bi bi-sort-down me-2"></i>Ordenar por
                    </label>
                    <select id="orden" name="orden" class="form-select">
                        <option value="reciente" <?= $orden === 'reciente' ? 'selected' : '' ?>>Más recientes</option>
                        <option value="antiguo" <?= $orden === 'antiguo' ? 'selected' : '' ?>>Más antiguos</option>
                        <option value="titulo" <?= $orden === 'titulo' ? 'selected' : '' ?>>Título (A-Z)</option>
                    </select>
                </div>

                <!-- Botones -->
                <div class="col-md-3 text-end">
                    <button type="submit" class="btn btn-ont-primary w-100">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                </div>

                <!-- Limpiar -->
                <div class="col-md-3">
                    <a href="normativas.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle me-1"></i>Limpiar
                    </a>
                </div>
            </form>

            <!-- Información de filtros activos -->
            <?php if (!empty($busqueda) || $orden !== 'reciente'): ?>
            <div class="filters-active">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Filtros activos:</strong>
                <?php if (!empty($busqueda)): ?>
                    <span class="badge bg-secondary">Búsqueda: "<?= htmlspecialchars($busqueda) ?>"</span>
                <?php endif; ?>
                <?php if ($orden !== 'reciente'): ?>
                    <span class="badge bg-secondary">Orden: <?= ucfirst($orden) ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Contador de resultados -->
            <div class="row mt-3">
                <div class="col-12">
                    <p class="result-count">
                        <i class="bi bi-file-earmark me-2"></i>
                        Mostrando <strong><?= count($normativas) ?></strong> de <strong><?= $totalNormativas ?></strong> 
                        estudio<?= $totalNormativas != 1 ? 's' : '' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Listado de Normativas -->
        <div class="row">
            <div class="col-12">
                <?php if (empty($normativas)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h3 class="text-muted mb-3">No se encontraron estudios</h3>
                        <p class="text-muted mb-4">No hay estudios disponibles con los filtros seleccionados.</p>
                        <a href="normativas.php" class="btn btn-ont-primary">
                            <i class="bi bi-arrow-clockwise me-2"></i>Ver todos los estudios
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row" id="normativas-container">
                        <?php foreach ($normativas as $normativa): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <a href="./assets/uploads/normativas/<?= htmlspecialchars($normativa['archivo']) ?>" 
                                   class="text-decoration-none" 
                                   target="_blank" 
                                   download>
                                    <div class="card normativa-card">
                                        <div class="click-hint">
                                            <i class="bi bi-mouse me-1"></i>Click para descargar
                                        </div>
                                        
                    <!-- Vista previa del PDF -->
                            <div class="card-pdf-preview">
                                <!-- Canvas para mostrar la primera página del PDF -->
                                <canvas class="pdf-canvas" 
                                        data-pdf-url="./assets/uploads/normativas/<?= htmlspecialchars($normativa['archivo']) ?>"
                                        style="width: 100%; height: 100%;"></canvas>
                                
                                <!-- Loading indicator -->
                                <div class="pdf-loading" id="loading-<?= $normativa['id'] ?>">
                                    <i class="bi bi-hourglass-split"></i>
                                    <span>Cargando vista previa...</span>
                                </div>
                                
                                <!-- Overlay con indicador de descarga -->
                                <div class="pdf-preview-overlay">
                                    <div class="download-indicator">
                                        <i class="bi bi-download"></i>
                                        <span>Descargar estudio</span>
                                    </div>
                                </div>
                            </div>
                                        
                                        <!-- Contenido de la tarjeta -->
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($normativa['titulo']) ?></h5>
                                            <p class="card-text">
                                                <?= truncateText(htmlspecialchars($normativa['descripcion'])) ?>
                                            </p>
                                        </div>
                                        
                                        <!-- Footer con metadata -->
                                        <div class="card-footer">
                                            <div class="card-meta">
                                                <div class="date-badge">
                                                    <i class="bi bi-calendar-event"></i>
                                                    <span><?= date('d/m/Y', strtotime($normativa['fecha_publicacion'])) ?></span>
                                                </div>
                                                <div class="category-badge">
                                                    <i class="bi bi-bookmark-fill"></i>
                                                    <span>Estudio</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Paginación -->
        <?php if ($totalPaginas > 1): ?>
        <div class="row mt-4">
            <div class="col-lg-8 mx-auto">
                <nav aria-label="Paginación de estudios">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="normativas.php?pagina=<?= $pagina - 1 ?>&buscar=<?= urlencode($busqueda) ?>&orden=<?= $orden ?>">
                                    <i class="bi bi-chevron-left me-1"></i>Anterior
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <a class="page-link" href="#">
                                    <i class="bi bi-chevron-left me-1"></i>Anterior
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                <a class="page-link" href="normativas.php?pagina=<?= $i ?>&buscar=<?= urlencode($busqueda) ?>&orden=<?= $orden ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagina < $totalPaginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="normativas.php?pagina=<?= $pagina + 1 ?>&buscar=<?= urlencode($busqueda) ?>&orden=<?= $orden ?>">
                                    Siguiente<i class="bi bi-chevron-right ms-1"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <a class="page-link" href="#">
                                    Siguiente<i class="bi bi-chevron-right ms-1"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php include './partials/footer.php'; ?>

    <!-- En el head de normativas.php -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Configurar PDF.js
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

document.addEventListener('DOMContentLoaded', function() {
    const pdfCanvases = document.querySelectorAll('.pdf-canvas');
    
    pdfCanvases.forEach(async (canvas) => {
        const pdfUrl = canvas.getAttribute('data-pdf-url');
        
        if (!pdfUrl) return;
        
        try {
            // Cargar el PDF
            const loadingTask = pdfjsLib.getDocument(pdfUrl);
            const pdf = await loadingTask.promise;
            
            // Obtener la primera página
            const page = await pdf.getPage(1);
            
            // Configurar el viewport
            const viewport = page.getViewport({ scale: 0.8 });
            const context = canvas.getContext('2d');
            
            // Establecer dimensiones del canvas
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            
            // Renderizar la página
            const renderContext = {
                canvasContext: context,
                viewport: viewport
            };
            
            await page.render(renderContext).promise;
            
            // Ocultar loading indicator
            const loadingId = canvas.closest('.card-pdf-preview').querySelector('.pdf-loading');
            if (loadingId) {
                loadingId.style.display = 'none';
            }
            
        } catch (error) {
            console.error('Error al cargar PDF:', error);
            
            // Mostrar icono de PDF como fallback
            const cardPreview = canvas.closest('.card-pdf-preview');
            if (cardPreview) {
                const fallback = document.createElement('div');
                fallback.className = 'pdf-fallback';
                fallback.innerHTML = `
                    <i class="bi bi-file-earmark-pdf" style="font-size: 4rem; color: #dc3545;"></i>
                    <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">Vista previa no disponible</p>
                `;
                fallback.style.position = 'absolute';
                fallback.style.top = '50%';
                fallback.style.left = '50%';
                fallback.style.transform = 'translate(-50%, -50%)';
                fallback.style.textAlign = 'center';
                
                cardPreview.appendChild(fallback);
                
                // Ocultar loading
                const loadingId = cardPreview.querySelector('.pdf-loading');
                if (loadingId) {
                    loadingId.style.display = 'none';
                }
            }
        }
    });
});
</script>

</body>
</html>