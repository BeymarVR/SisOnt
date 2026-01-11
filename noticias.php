<?php
require_once './includes/auth.php';
require_once './includes/database.php';
require_once './includes/comentarios_functions.php';

$conexion = obtenerConexion();

// Si hay ID, mostrar detalle; si no, mostrar lista
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    // Vista de detalle
    $noticiaId = intval($_GET['id']);

    $stmt = $conexion->prepare("SELECT * FROM noticias WHERE id = ? AND estado = 'publicado'");
    $stmt->bind_param("i", $noticiaId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: noticias.php"); // Redirigir a lista si no existe
        exit();
    }

    $noticia = $result->fetch_assoc();
    ?>
    
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($noticia['titulo']) ?> - ONT Bolivia</title>
        <meta name="description" content="<?= htmlspecialchars(substr(strip_tags($noticia['contenido']), 0, 160)) ?>">
        <link rel="icon" type="image/png" href="assets/images/logo_sup.png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <link href="./assets/css/style.css" rel="stylesheet">
          <style>
            .comentario-card {
                border-left: 4px solid #352f62;
                transition: transform 0.2s;
            }
            .comentario-card:hover {
                transform: translateX(5px);
            }
            .usuario-avatar {
                width: 40px;
                height: 40px;
                background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
            }
            .main-content {
    margin-top: 70px; /* ajusta al alto real del navbar */
    }

        </style>
        <meta property="og:title" content="<?= htmlspecialchars($noticia['titulo']) ?>">
        <meta property="og:description" content="<?= htmlspecialchars(substr(strip_tags($noticia['contenido']), 0, 160)) ?>">
        <?php if (!empty($noticia['imagen_portada'])): ?>
        <meta property="og:image" content="./assets/uploads/noticias/<?= htmlspecialchars($noticia['imagen_portada']) ?>">
        <?php endif; ?>
        <meta property="og:type" content="article">

    </head>
    <body>
        <!-- Navigation -->
        <?php include './partials/navbar.php'; ?>
    <div class="main-content">
        <header class="noticia-header">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <a href="noticias.php" class="btn-back mb-3">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h1 class="display-4 mb-3"><?= htmlspecialchars($noticia['titulo']) ?></h1>
                        <div class="d-flex align-items-center text-white-50">
                            <i class="bi bi-calendar3 me-2"></i>
                            <span class="me-4">Publicado el <?= date('d/m/Y', strtotime($noticia['fecha_publicacion'])) ?></span>
                            <i class="bi bi-clock me-2"></i>
                            <span>Lectura: ~<?= ceil(str_word_count(strip_tags($noticia['contenido'])) / 200) ?> min</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>
    </div>
        
        <main class="container my-5">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <article class="noticia-content">
                        <?php if (!empty($noticia['imagen_portada'])): ?>
                            <div class="text-center mb-4">
                                <img src="./assets/uploads/noticias/<?= htmlspecialchars($noticia['imagen_portada']) ?>" 
                                     class="img-fluid rounded shadow" 
                                     alt="<?= htmlspecialchars($noticia['titulo']) ?>"
                                     style="max-height: 400px; width: 100%; object-fit: cover;">
                            </div>
                        <?php endif; ?>
    
                     <div class="content-text" style="font-size: 1.1rem; line-height: 1.8;">
    <?php
    // Función para extraer ID de YouTube
    function obtenerIdYouTube($url) {
        $patrones = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/'
        ];
        
        foreach ($patrones as $patron) {
            if (preg_match($patron, $url, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }

    // Función para renderizar cada bloque
    function renderizarBloqueContenido($bloque) {
        $html = '';
        $tipo = $bloque['type'] ?? '';
        $contenido = $bloque['content'] ?? [];
        $id = $bloque['id'] ?? '';
        
        switch ($tipo) {
            case 'text':
                $texto = $contenido['text'] ?? '';
                $fontFamily = $contenido['fontFamily'] ?? 'inherit';
                $fontSize = $contenido['fontSize'] ?? '1.1rem';
                
                $html = "<div style=\"font-family: {$fontFamily}; font-size: {$fontSize}; margin-bottom: 1.5rem; line-height: 1.8;\">{$texto}</div>";
                break;
                
            case 'image':
                $alt = $contenido['alt'] ?? '';
                $caption = $contenido['caption'] ?? '';
                $filename = $contenido['filename'] ?? '';
                
                if ($filename) {
                    $html = "<figure class=\"text-center my-4\">";
                    $html .= "<img src=\"./assets/uploads/noticias/{$filename}\" class=\"img-fluid rounded shadow\" alt=\"{$alt}\" style=\"max-height: 400px; width: 90%; max-width: 100%;\">";
                    if (!empty($caption)) {
                        $html .= "<figcaption class=\"mt-2 text-muted\"><em>{$caption}</em></figcaption>";
                    }
                    $html .= "</figure>";
                } else {
                    $html = "<div class=\"alert alert-warning text-center\">[Imagen no disponible: {$alt}]</div>";
                }
                break;
                
            case 'video':
                $videoType = $contenido['video_type'] ?? 'url';
                $url = $contenido['url'] ?? '';
                $filename = $contenido['filename'] ?? '';
                
                if ($videoType === 'url' && !empty($url)) {
                    // Convertir URL de YouTube a embed
                    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
                        $videoId = obtenerIdYouTube($url);
                        if ($videoId) {
                            $html = "<div class=\"ratio ratio-16x9 my-4\">";
                            $html .= "<iframe src=\"https://www.youtube.com/embed/{$videoId}\" frameborder=\"0\" allowfullscreen></iframe>";
                            $html .= "</div>";
                        } else {
                            $html = "<div class=\"alert alert-info\"><a href=\"{$url}\" target=\"_blank\">Ver video en YouTube</a></div>";
                        }
                    } else {
                        $html = "<div class=\"alert alert-info\"><a href=\"{$url}\" target=\"_blank\">Ver video externo</a></div>";
                    }
                } elseif ($videoType === 'upload' && !empty($filename)) {
                    $html = "<div class=\"text-center my-4\">";
                    $html .= "<video controls class=\"img-fluid rounded shadow\" style=\"max-height: 400px;\">";
                    $html .= "<source src=\"./assets/uploads/videos/{$filename}\" type=\"video/mp4\">";
                    $html .= "Tu navegador no soporta el elemento video.";
                    $html .= "</video>";
                    $html .= "</div>";
                } else {
                    $html = "<div class=\"alert alert-warning\">[Video no disponible]</div>";
                }
                break;
                
            case 'quote':
                $texto = $contenido['text'] ?? '';
                $autor = $contenido['author'] ?? '';
                
                $html = "<blockquote class=\"blockquote my-4 p-3 bg-light border-start border-4 border-primary\">";
                $html .= "<p class=\"mb-2\">\"{$texto}\"</p>";
                if (!empty($autor)) {
                    $html .= "<footer class=\"blockquote-footer\">{$autor}</footer>";
                }
                $html .= "</blockquote>";
                break;
                
            case 'list':
                $items = $contenido['items'] ?? [];
                $tipoLista = $contenido['type'] ?? 'ul';
                $tag = ($tipoLista === 'ol') ? 'ol' : 'ul';
                
                if (!empty($items)) {
                    $html = "<{$tag} class=\"my-4\">";
                    foreach ($items as $item) {
                        if (!empty(trim($item))) {
                            $html .= "<li>{$item}</li>";
                        }
                    }
                    $html .= "</{$tag}>";
                }
                break;
                
            case 'divider':
                $estilo = $contenido['style'] ?? 'solid';
                $borderStyle = '';
                
                switch ($estilo) {
                    case 'dashed': $borderStyle = 'dashed'; break;
                    case 'dotted': $borderStyle = 'dotted'; break;
                    default: $borderStyle = 'solid';
                }
                
                $html = "<hr style=\"border-top: 2px {$borderStyle} #dee2e6; margin: 2rem 0;\">";
                break;
                
            default:
                $html = "<!-- Bloque de tipo desconocido: {$tipo} -->";
                break;
        }
        
        return $html;
    }

    // Mostrar contenido estructurado desde JSON si existe
    if (!empty($noticia['contenido_json'])) {
        $bloques = json_decode($noticia['contenido_json'], true);
        
        if (is_array($bloques)) {
            foreach ($bloques as $bloque) {
                echo renderizarBloqueContenido($bloque);
            }
        } else {
            // Fallback: mostrar contenido plano
            echo nl2br(htmlspecialchars($noticia['contenido']));
        }
    } else {
        // Mostrar contenido plano si no hay JSON
        echo nl2br(htmlspecialchars($noticia['contenido']));
    }
    ?>
</div>
    
                        
                        <div class="mt-5 pt-4 border-top">
                            <h5 class="mb-3">
                                <i class="bi bi-share me-2"></i>Compartir esta noticia
                            </h5>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-primary btn-sm" 
                                        onclick="shareOnSocial('facebook', window.location.href, '<?= htmlspecialchars($noticia['titulo']) ?>')">
                                    <i class="bi bi-facebook me-1"></i>Facebook
                                </button>
                                <button class="btn btn-info btn-sm" 
                                        onclick="shareOnSocial('twitter', window.location.href, '<?= htmlspecialchars($noticia['titulo']) ?>')">
                                    <i class="bi bi-twitter me-1"></i>Twitter
                                </button>
                                <button class="btn btn-primary btn-sm" 
                                        onclick="shareOnSocial('linkedin', window.location.href, '<?= htmlspecialchars($noticia['titulo']) ?>')">
                                    <i class="bi bi-linkedin me-1"></i>LinkedIn
                                </button>
                                <button class="btn btn-success btn-sm" 
                                        onclick="shareOnSocial('whatsapp', window.location.href, '<?= htmlspecialchars($noticia['titulo']) ?>')">
                                    <i class="bi bi-whatsapp me-1"></i>WhatsApp
                                </button>
                            </div>
                        </div>
                    </article>
    
                     
                    <div class="row mt-5">
                        <div class="col-md-6">
                            <?php
                            // Obtener noticia anterior
                            $stmtPrev = $conexion->prepare("SELECT id, titulo FROM noticias WHERE id < ? AND estado = 'publicado' ORDER BY id DESC LIMIT 1");
                            $stmtPrev->bind_param("i", $noticiaId);
                            $stmtPrev->execute();
                            $prevResult = $stmtPrev->get_result();
                            if ($prevNoticia = $prevResult->fetch_assoc()):
                            ?>
                                <a href="noticias.php?id=<?= $prevNoticia['id'] ?>" class="btn btn-outline-primary w-100 text-start">
                                    <i class="bi bi-arrow-left me-2"></i>
                                    <small class="d-block">Anterior</small>
                                    <strong><?= htmlspecialchars(substr($prevNoticia['titulo'], 0, 50)) ?>...</strong>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <?php
                            // Obtener noticia siguiente
                            $stmtNext = $conexion->prepare("SELECT id, titulo FROM noticias WHERE id > ? AND estado = 'publicado' ORDER BY id ASC LIMIT 1");
                            $stmtNext->bind_param("i", $noticiaId);
                            $stmtNext->execute();
                            $nextResult = $stmtNext->get_result();
                            if ($nextNoticia = $nextResult->fetch_assoc()):
                            ?>
                                <a href="noticias.php?id=<?= $nextNoticia['id'] ?>" class="btn btn-outline-primary w-100 text-end">
                                    <small class="d-block">Siguiente</small>
                                    <strong><?= htmlspecialchars(substr($nextNoticia['titulo'], 0, 50)) ?>...</strong>
                                    <i class="bi bi-arrow-right ms-2"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Sección de comentarios -->
    <section class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="h4">
                        <i class="bi bi-chat-dots me-2"></i>
                        Comentarios
                    </h3>
                    <a href="comentarios.php?tipo=noticia&id=<?= $noticiaId ?>" class="btn btn-sm btn-outline-primary">
                        Ver todos los comentarios
                    </a>
                </div>
    
                <?php
                // Obtener comentarios aprobados para esta noticia
                $comentarios = obtenerComentariosPublicacion('noticia', $noticiaId);
                $comentariosPreview = array_slice($comentarios, 0, 3); // Mostrar solo 3
                ?>
    
                <!-- Formulario rápido de comentario -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="formComentarioRapido">
                            <input type="hidden" name="tipo" value="noticia">
                            <input type="hidden" name="id" value="<?= $noticiaId ?>">
                            <div class="mb-3">
                                <textarea class="form-control" name="comentario" rows="2" 
                                          placeholder="Escribe un comentario..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-ont-primary btn-sm">
                                <i class="bi bi-send me-1"></i>Enviar
                            </button>
                        </form>
                    </div>
                </div>
                <div id="mensajeComentario"></div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <a href="auth/login.php">Inicia sesión</a> para dejar un comentario.
                </div>
                <?php endif; ?>
    
                <!-- Lista de comentarios -->
                <?php if (!empty($comentariosPreview)): ?>
                    <?php foreach ($comentariosPreview as $comentario): ?>
                    <div class="card mb-2">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center mb-1">
                                <div class="usuario-avatar me-2">
                                    <?= strtoupper(substr($comentario['usuario_nombre'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($comentario['usuario_nombre']) ?></h6>
                                    <small class="text-muted">
                                        <?= date('d/m/Y', strtotime($comentario['fecha_creacion'])) ?>
                                    </small>
                                </div>
                            </div>
                            <p class="mb-0 small"><?= htmlspecialchars($comentario['contenido']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($comentarios) > 3): ?>
                    <div class="text-center mt-3">
                        <a href="comentarios.php?tipo=noticia&id=<?= $noticiaId ?>" class="btn btn-sm btn-outline-primary">
                            Ver los <?= count($comentarios) - 3 ?> comentarios restantes
                        </a>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-chat me-2"></i>
                    No hay comentarios aún. ¡Sé el primero en comentar!
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
                                
        <footer class="footer-ont mt-5">
            <div class="container">
                <div class="text-center py-4">
                    <p class="mb-0">&copy; 2025 Observatorio Nacional del Trabajo - ONT Bolivia</p>
                </div>
            </div>
        </footer>
    
          
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="./assets/js/main.js"></script>
        <script>
    // Envío de comentario via AJAX
    document.getElementById('formComentarioRapido')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const mensajeDiv = document.getElementById('mensajeComentario');
        
        fetch('ajax/guardar_comentario.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mensajeDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                this.reset();
                // Recargar después de 2 segundos
                setTimeout(() => location.reload(), 2000);
            } else {
                mensajeDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            mensajeDiv.innerHTML = `<div class="alert alert-danger">Error de conexión</div>`;
        });
    });
    </script>
    </body>
    </html>
    
    <?php
} else {
    // Vista de lista con cards
    $porPagina = 9;
    $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
    $offset = ($pagina - 1) * $porPagina;

    // Variables para filtros
    $busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
    $orden = isset($_GET['orden']) ? $_GET['orden'] : 'fecha';
    $destacada = isset($_GET['destacada']) ? $_GET['destacada'] : '';

    // Construir query dinámica
    $where = "WHERE estado = 'publicado'";
    $params = [];
    $types = "";

    // Filtro de búsqueda
    if (!empty($busqueda)) {
        $where .= " AND (titulo LIKE ? OR contenido LIKE ?)";
        $searchTerm = "%{$busqueda}%";
        $params = [$searchTerm, $searchTerm];
        $types = "ss";
    }

    // Filtro destacadas
    if ($destacada === '1') {
        $where .= " AND destacada = 1";
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

    // Obtener total de noticias con filtros
    $countQuery = "SELECT COUNT(*) as total FROM noticias {$where}";
    $countStmt = $conexion->prepare($countQuery);
    
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    
    $countStmt->execute();
    $totalResult = $countStmt->get_result()->fetch_assoc();
    $totalNoticias = $totalResult['total'];
    $totalPaginas = ceil($totalNoticias / $porPagina);

    // Obtener noticias para la página actual
    $query = "SELECT * FROM noticias {$where} {$orderBy} LIMIT ? OFFSET ?";
    $stmt = $conexion->prepare($query);
    
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
    $noticias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    function truncateText($text, $length = 150) {
        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }
    ?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Noticias - ONT Bolivia</title>
        <meta name="description" content="Noticias laborales del Observatorio Nacional del Trabajo de Bolivia">
        <link rel="icon" type="image/png" href="assets/images/logo_sup.png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <link href="./assets/css/style.css" rel="stylesheet">
        
        <style>
            .noticia-card {
                transition: transform 0.2s;
            }
            .noticia-card:hover {
                transform: translateY(-5px);
            }
            .noticia-imagen {
                height: 200px;
                object-fit: cover;
            }
            .filters-section {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 30px;
            }
            .filter-active {
                background-color: #352f62;
                color: white;
            }
        </style>
    </head>
    <body>
        <!-- Navigation -->
        <?php include './partials/navbar.php'; ?>

        <!-- Hero Section -->
        <section class="hero-section" style="background: linear-gradient(135deg, #352f62 0%, #e45504 100%); padding: 120px 0;">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 mx-auto text-center">
                        <h1 class="display-4 mb-3">
                            <i class="bi bi-newspaper me-3"></i>Noticias
                        </h1>
                        <p class="lead">Mantente informado con las últimas noticias laborales</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <main class="container py-5">
            <!-- Filtros y Búsqueda -->
            <div class="filters-section">
                <form method="GET" action="noticias.php" class="row g-3 align-items-end">
                    <!-- Búsqueda -->
                    <div class="col-md-4">
                        <label for="buscar" class="form-label">
                            <i class="bi bi-search me-2"></i>Buscar noticias
                        </label>
                        <input type="text" id="buscar" name="buscar" class="form-control" 
                               placeholder="Por título o contenido..." 
                               value="<?= htmlspecialchars($busqueda) ?>">
                    </div>

                    <!-- Ordenar -->
                    <div class="col-md-3">
                        <label for="orden" class="form-label">
                            <i class="bi bi-sort-down me-2"></i>Ordenar por
                        </label>
                        <select id="orden" name="orden" class="form-select">
                            <option value="reciente" <?= $orden === 'reciente' ? 'selected' : '' ?>>Más recientes</option>
                            <option value="antiguo" <?= $orden === 'antiguo' ? 'selected' : '' ?>>Más antiguos</option>
                            <option value="titulo" <?= $orden === 'titulo' ? 'selected' : '' ?>>Título (A-Z)</option>
                        </select>
                    </div>

                    <!-- Filtro Destacadas -->
                    <div class="col-md-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="destacada" name="destacada" 
                                   value="1" <?= $destacada === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="destacada">
                                <i class="bi bi-star-fill text-warning me-1"></i>Solo destacadas
                            </label>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="col-md-2 text-end">
                        <button type="submit" class="btn btn-ont-admin btn-ont-admin-primary">
                            <i class="bi bi-funnel me-1"></i>Filtrar
                        </button>
                        <a href="noticias.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Limpiar
                        </a>
                    </div>
                </form>

                <!-- Información de filtros activos -->
                <?php if (!empty($busqueda) || $destacada === '1' || $orden !== 'fecha'): ?>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Filtros activos:</strong>
                    <?php if (!empty($busqueda)): ?>
                        <span class="badge bg-secondary">Búsqueda: "<?= htmlspecialchars($busqueda) ?>"</span>
                    <?php endif; ?>
                    <?php if ($destacada === '1'): ?>
                        <span class="badge bg-warning text-dark">Solo destacadas</span>
                    <?php endif; ?>
                    <?php if ($orden !== 'reciente'): ?>
                        <span class="badge bg-secondary">Orden: <?= ucfirst($orden) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Listado de Noticias -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($noticias)): ?>
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle me-2"></i>
                            No hay noticias disponibles con los filtros seleccionados.
                            <br>
                            <a href="noticias.php" class="btn btn-sm btn-outline-primary mt-2">
                                Ver todas las noticias
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row" id="noticias-container">
                            <?php foreach ($noticias as $noticia): ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card noticia-card h-100">
                                        <?php if (!empty($noticia['imagen_portada'])): ?>
                                            <div class="position-relative">
                                                <img src="./assets/uploads/noticias/<?= htmlspecialchars($noticia['imagen_portada']) ?>" 
                                                     class="card-img-top noticia-imagen" 
                                                     alt="<?= htmlspecialchars($noticia['titulo']) ?>">
                                                <?php if ($noticia['destacada']): ?>
                                                    <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2">
                                                        <i class="bi bi-star-fill me-1"></i>Destacada
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light noticia-imagen">
                                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($noticia['titulo']) ?></h5>
                                            <p class="card-text text-muted small">
                                                <?= truncateText(strip_tags($noticia['contenido'])) ?>
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    <?= date('d/m/Y', strtotime($noticia['fecha_publicacion'])) ?>
                                                </small>
                                                <a href="noticias.php?id=<?= $noticia['id'] ?>" class="btn btn-ont-admin btn-ont-admin-primary btn-sm">
                                                    <i class="bi bi-arrow-right me-1"></i>Leer
                                                </a>
                                            </div>
                                        </div>
                                    </div>
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
                    <nav aria-label="Paginación de noticias">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="noticias.php?pagina=<?= $pagina - 1 ?>&buscar=<?= urlencode($busqueda) ?>&orden=<?= $orden ?>&destacada=<?= $destacada ?>">Anterior</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#">Anterior</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                    <a class="page-link" href="noticias.php?pagina=<?= $i ?>&buscar=<?= urlencode($busqueda) ?>&orden=<?= $orden ?>&destacada=<?= $destacada ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagina < $totalPaginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="noticias.php?pagina=<?= $pagina + 1 ?>&buscar=<?= urlencode($busqueda) ?>&orden=<?= $orden ?>&destacada=<?= $destacada ?>">Siguiente</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#">Siguiente</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
            <?php endif; ?>

            <!-- Información de resultados -->
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <p class="text-muted">
                        Mostrando <strong><?= count($noticias) ?></strong> de <strong><?= $totalNoticias ?></strong> noticias
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php include './partials/footer.php'; ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}