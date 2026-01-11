<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

verificarRol('admin');

$conexion = obtenerConexion();

// Obtener estadísticas
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM pop_ups");
$stmt->execute();
$total_popups = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conexion->prepare("SELECT COUNT(*) as activos FROM pop_ups WHERE activo = 1");
$stmt->execute();
$popups_activos = $stmt->get_result()->fetch_assoc()['activos'];

$stmt = $conexion->prepare("SELECT COUNT(*) as inactivos FROM pop_ups WHERE activo = 0");
$stmt->execute();
$popups_inactivos = $stmt->get_result()->fetch_assoc()['inactivos'];

$stmt = $conexion->prepare("SELECT COUNT(DISTINCT usuario_id) as usuarios_interactuaron FROM pop_ups_visto");
$stmt->execute();
$usuarios_interactuaron = $stmt->get_result()->fetch_assoc()['usuarios_interactuaron'];

// Obtener todos los pop-ups
$stmt = $conexion->prepare("
    SELECT p.*, u.nombre as creador_nombre 
    FROM pop_ups p 
    LEFT JOIN usuarios u ON p.creado_por = u.id 
    ORDER BY p.fecha_creacion DESC
");
$stmt->execute();
$popups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pop-ups - Panel ONT</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->

  <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <header class="admin-header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <h1 class="page-title">Gestión de Pop-ups</h1>
                </div>
                
            </header>

            <div class="content-area">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-header">
                            <div class="stat-icon primary">
                                <i class="bi bi-window-stack"></i>
                            </div>
                        </div>
                            <div class="stat-content">
                                <h3><?php echo $total_popups; ?></h3>
                                <p>Total Pop-ups</p>
                                <div class="stat-trend trend-up">
                                    <i class="bi bi-arrow-up"></i> Gestión completa
                                </div>
                            </div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-header">
                            <div class="stat-icon success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                            <div class="stat-content">
                                <h3><?php echo $popups_activos; ?></h3>
                                <p>Activos</p>
                                <div class="stat-trend trend-up">
                                    <i class="bi bi-arrow-up"></i> Visibles ahora
                                </div>
                            </div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div class="stat-icon warning">
                                <i class="bi bi-pause-circle"></i>
                            </div>
                        </div>
                            <div class="stat-content">
                                <h3><?php echo $popups_inactivos; ?></h3>
                                <p>Inactivos</p>
                                <div class="stat-trend trend-neutral">
                                    <i class="bi bi-dash"></i> No visibles
                                </div>
                            </div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-header">
                            <div class="stat-icon info">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                            <div class="stat-content">
                                <h3><?php echo $usuarios_interactuaron; ?></h3>
                                <p>Usuarios Interactuaron</p>
                                <div class="stat-trend trend-up">
                                    <i class="bi bi-arrow-up"></i> Engagement
                                </div>
                            </div>
                    </div>
                     
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                        <h2 class="content-title">Lista de Pop-Ups</h2>
                        <p class="content-subtitle">Gestiona los pop-ups del sistema</p>
                    </div>
                    <a class="btn-ont primary" data-bs-toggle="modal" data-bs-target="#popupModal">
                         <i class="bi bi-plus-circle"></i>
                        Nuevo Pop-up
                    </a>
                            </div>
                <!-- Pop-ups Table -->
                <div class="data-table">
                    <div class="table-header d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="bi bi-window-stack me-2"></i>Pop-ups Registrados</h5>
                        
                        <div class="table-actions">
                            <div class="search-box">
                               
                                <input type="text" id="searchPopups" 
                                data-target=".data-table table tbody" 
                                placeholder="Buscar pop-ups..." 
                                class="form-control" style="padding: 0.375rem 0.75rem; border: 1px solid #dee2e6; border-radius: 6px;"">
                            </div>
                            
                            <div class="filter-controls">
                                <select id="filterEstado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                           
                             
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table" id="popupsTable">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Fechas</th>
                                    <th>Creador</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popups as $popup): ?>
                                <tr>
                                    <td>
                                        <div class="popup-info">
                                            <strong><?php echo htmlspecialchars($popup['titulo']); ?></strong>
                                            <small class="text-muted d-block">
                                                <?php echo htmlspecialchars(substr($popup['descripcion'], 0, 50)) . '...'; ?>
                                            </small>
                                        </div>
                                    </td>
                                   <td>
                                        <span class="badge-ont <?php 
                                            echo $popup['tipo'] == 'imagen' ? 'primary' : 
                                                ($popup['tipo'] == 'video' ? 'success' : 'info'); 
                                        ?>">
                                            <i class="bi bi-<?php 
                                                echo $popup['tipo'] == 'imagen' ? 'image' : 
                                                    ($popup['tipo'] == 'video' ? 'play-circle' : 'chat-text'); 
                                            ?>"></i>
                                            <?php echo ucfirst($popup['tipo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-ont <?php echo $popup['activo'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $popup['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php if ($popup['fecha_inicio']): ?>
                                                Inicio: <?php echo date('d/m/Y', strtotime($popup['fecha_inicio'])); ?><br>
                                            <?php endif; ?>
                                            <?php if ($popup['fecha_fin']): ?>
                                                Fin: <?php echo date('d/m/Y', strtotime($popup['fecha_fin'])); ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($popup['creador_nombre'] ?? 'Sistema'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-ont info" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;" onclick="previewPopup(<?php echo $popup['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <a href="pop_ups_editar.php?id=<?php echo $popup['id']; ?>" class="btn-ont warning" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="pop_ups_eliminar.php?id=<?php echo $popup['id']; ?>" class="btn-ont danger" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal para crear pop-up -->
    <div class="modal fade" id="popupModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Pop-up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="popupForm" action="pop_ups_guardar.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="form-label">Título *</label>
                                    <input type="text" name="titulo" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Descripción</label>
                                    <textarea name="descripcion" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Tipo *</label>
                                    <select name="tipo" class="form-control" required onchange="toggleContentFields(this.value)">
                                        <option value="">Seleccionar tipo</option>
                                        <option value="texto">Texto</option>
                                        <option value="imagen">Imagen</option>
                                        <option value="video">Video</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Estado</label>
                                    <select name="activo" class="form-control">
                                        <option value="1">Activo</option>
                                        <option value="0">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Campos dinámicos según el tipo -->
                        <div id="contenido-texto" class="content-field" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">Contenido (HTML permitido) *</label>
                                <textarea name="contenido" class="form-control" rows="6" placeholder="Puedes usar HTML para formatear el texto"></textarea>
                            </div>
                        </div>

                        <div id="contenido-imagen" class="content-field" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">Subir imagen *</label>
                                <input type="file" name="archivo" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <div id="contenido-video" class="content-field" style="display: none;">
                            <div class="form-group">
                                <label class="form-label">URL del video (YouTube, Vimeo, etc.) *</label>
                                <input type="url" name="url_externa" class="form-control" 
                                       placeholder="https://www.youtube.com/watch?v=ABCDE o https://vimeo.com/12345">
                                <small class="text-muted">El sistema convertirá automáticamente a formato embed</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Fecha de inicio</label>
                                    <input type="date" name="fecha_inicio" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Fecha de fin</label>
                                    <input type="date" name="fecha_fin" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Posición</label>
                                    <select name="posicion" class="form-control">
                                        <option value="centro">Centro</option>
                                        <option value="esquina-superior-derecha">Esquina Superior Derecha</option>
                                        <option value="esquina-inferior-derecha">Esquina Inferior Derecha</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Ancho (px)</label>
                                    <input type="number" name="ancho" class="form-control" value="500" min="200" max="1000">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Alto (px)</label>
                                    <input type="number" name="alto" class="form-control" value="400" min="200" max="800">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="mostrar_una_vez" class="form-check-input" id="mostrarUnaVez" value="1">
                                <label class="form-check-label" for="mostrarUnaVez">
                                    Mostrar solo una vez por usuario
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-ont secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-ont primary">Guardar Pop-up</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para vista previa del pop-up -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Vista Previa del Pop-up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="previewContent"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
        <script>
        function toggleContentFields(tipo) {
            // Ocultar todos los campos de contenido
            document.querySelectorAll('.content-field').forEach(field => {
                field.style.display = 'none';
            });
            
            // Mostrar el campo correspondiente al tipo seleccionado
            if (tipo) {
                const field = document.getElementById('contenido-' + tipo);
                if (field) {
                    field.style.display = 'block';
                }
            }
            // Valores por defecto para VIDEO
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
        

        function previewPopup(id) {
            fetch('get_popup_preview.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const p = data.popup;
                        let content = `
                            <div class="popup-overlay show" style="display:flex; position: relative; background: rgba(0,0,0,0.5); padding: 20px;">
                                <div class="popup-content ${p.tipo === 'video' ? 'video-popup' : ''}"
                                     style="width: ${p.ancho}px; max-width: 90%; height: ${p.alto}px; max-height: 90%; background: white; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column;">
                                    <div class="popup-header" style="padding: 15px; border-bottom: 1px solid #dee2e6; background: #f8f9fa;">
                                        <h5 style="margin: 0;">${escapeHtml(p.titulo)}</h5>
                                    </div> 
                                    <div class="popup-body" style="padding: 20px; flex: 1; overflow-y: auto;">
                `;

                        // Contenido según tipo
                        if (p.tipo === 'texto') {
                            content += `
                                <div class="popup-text-content">
                                    ${p.contenido}
                                </div>
                            `;
                        } else if (p.tipo === 'imagen' && p.archivo) {
                            content += `
                                <img src="../assets/uploads/popups/${escapeHtml(p.archivo)}" 
                                     style="max-width:100%; max-height:100%; object-fit: contain;">
                            `;
                        } else if (p.tipo === 'video' && p.url_externa) {
                            content += `
                                <div class="ratio ratio-16x9">
                                    <iframe src="${escapeHtml(p.url_externa)}" 
                                            allowfullscreen></iframe>
                                </div>
                            `;
                        }

                        // Descripción
                        if (p.descripcion) {
                            content += `
                                <div class="popup-description mt-3">
                                    <p>${escapeHtml(p.descripcion)}</p>
                                </div>
                            `;
                        }

                        content += `
                                    </div>
                                </div>
                            </div>
                        `;

                        document.getElementById('previewContent').innerHTML = content;
                        new bootstrap.Modal(document.getElementById('previewModal')).show();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo cargar la vista previa'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar la vista previa');
                });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Búsqueda en tiempo real
        document.getElementById('searchPopups').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#popupsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Filtros
        document.getElementById('filterTipo').addEventListener('change', function() {
            filterTable();
        });

        document.getElementById('filterEstado').addEventListener('change', function() {
            filterTable();
        });

        function filterTable() {
            const tipoFilter = document.getElementById('filterTipo').value;
            const estadoFilter = document.getElementById('filterEstado').value;
            const rows = document.querySelectorAll('#popupsTable tbody tr');
            
            rows.forEach(row => {
                let showRow = true;
                
                if (tipoFilter) {
                    const tipoCell = row.cells[1].textContent.toLowerCase();
                    if (!tipoCell.includes(tipoFilter.toLowerCase())) {
                        showRow = false;
                    }
                }
                
                if (estadoFilter && showRow) {
                    const estadoCell = row.cells[2].textContent.toLowerCase();
                    const isActive = estadoFilter === '1' ? 'activo' : 'inactivo';
                    if (!estadoCell.includes(isActive)) {
                        showRow = false;
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }
        function toggleContentFields(tipo) {
    document.querySelectorAll('.content-field').forEach(field => field.style.display = 'none');
    if (tipo) {
        const field = document.getElementById('contenido-' + tipo);
        if (field) field.style.display = 'block';
    }

    // Valores por defecto para VIDEO
    if (tipo === 'video') {
        document.querySelector('input[name="ancho"]').value = 800;
        document.querySelector('input[name="alto"]').value = 450;
    }
}

    </script>
    <script>
        function previewPopup(id) {
            alert('Vista previa del pop-up ID: ' + id);
        }

        // Búsqueda en tiempo real
        document.getElementById('searchPopups').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#popupsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Filtros
        document.getElementById('filterEstado').addEventListener('change', function() {
            const estadoFilter = this.value;
            const rows = document.querySelectorAll('#popupsTable tbody tr');
            
            rows.forEach(row => {
                let showRow = true;
                
                if (estadoFilter) {
                    const estadoCell = row.cells[2].textContent.toLowerCase();
                    const isActive = estadoFilter === '1' ? 'activo' : 'inactivo';
                    if (!estadoCell.includes(isActive)) {
                        showRow = false;
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        });
    </script>
</body>
</html>