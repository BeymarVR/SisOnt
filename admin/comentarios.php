<?php
// admin/comentarios.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/comentarios_functions.php';

verificarRol('admin');

$comentarios = obtenerComentarios();
$estadisticas = obtenerEstadisticasComentarios();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Comentarios - Panel ONT</title>
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
                <h1 class="page-title">Gestión de Comentarios</h1>
            </div>
        </header>

        <div class="content-area">
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-header">
                        <div class="stat-icon primary">
                            <i class="bi-chat-dots"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?= $estadisticas['total'] ?></h3>
                        <p>Total Comentarios</p>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="bi-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?= $estadisticas['aprobados'] ?></h3>
                        <p>Aprobados</p>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-icon warning">
                            <i class="bi-clock-history"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?= $estadisticas['pendientes'] ?></h3>
                        <p>Pendientes</p>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-icon info">
                            <i class="bi-x-circle"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?= $estadisticas['rechazados'] ?></h3>
                        <p>Rechazados</p>
                    </div>
                </div>
            </div>

                    <div>
                        <h2 class="content-title">Lista de Comentarios</h2>
                        <p class="content-subtitle">Aprueba o Rechaza los comentarios</p>
                    </div>

            <!-- Lista de comentarios -->
            <div class="data-table">
                <div class="table-header d-flex justify-content-between align-items-center mb-3">
                    <h5><i class="bi bi-chat-dots me-2"></i>Comentarios</h5>
                    <div class="table-actions">
                        <select class="form-select filter-select" id="filtroEstado">
                            <option value="todos">Todos</option>
                            <option value="pendiente">Pendientes</option>
                            <option value="aprobado">Aprobados</option>
                            <option value="rechazado">Rechazados</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($comentarios)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No hay comentarios aún.
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Contenido</th>
                                <th>Publicación</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comentarios as $comentario): ?>
                                <tr data-estado="<?= $comentario['estado'] ?>">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="user-avatar">
                                                <?php 
                                                    // Verificar si el usuario tiene foto de avatar (Google o cargada)
                                                    $fotoUsuario = !empty($comentario['foto_perfil']) && file_exists(__DIR__ . '/../assets/uploads/avatares/' . $comentario['foto_perfil']) 
                                                        ? './assets/uploads/avatares/' . htmlspecialchars($comentario['foto_perfil'])
                                                        : null;
                                                    
                                                    if ($fotoUsuario):
                                                ?>
                                                    <img src="<?= $fotoUsuario ?>" alt="<?= htmlspecialchars($comentario['nombre_usuario']) ?>" title="<?= htmlspecialchars($comentario['nombre_usuario']) ?>">
                                                <?php else: ?>
                                                    <?= strtoupper(substr($comentario['nombre_usuario'], 0, 1)) ?>
                                                <?php endif; ?>
                                            </div>
                                            <span><?= htmlspecialchars($comentario['nombre_usuario']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="comment-preview">
                                            <?= htmlspecialchars(substr($comentario['contenido'], 0, 100)) ?>
                                            <?= strlen($comentario['contenido']) > 100 ? '...' : '' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($comentario['titulo_publicacion']) ?>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge-ont <?= 
                                            $comentario['estado'] == 'aprobado' ? 'success' : 
                                            ($comentario['estado'] == 'pendiente' ? 'warning' : 'danger') 
                                        ?>">
                                            <?= ucfirst($comentario['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary view-comment" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewCommentModal"
                                                    data-id="<?= $comentario['id'] ?>"
                                                    data-content="<?= htmlspecialchars($comentario['contenido']) ?>"
                                                    data-user="<?= htmlspecialchars($comentario['nombre_usuario']) ?>"
                                                    data-date="<?= date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])) ?>"
                                                    data-publicacion="<?= htmlspecialchars($comentario['titulo_publicacion']) ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($comentario['estado'] == 'pendiente'): ?>
                                                <button class="btn btn-sm btn-outline-success approve-comment" 
                                                        data-id="<?= $comentario['id'] ?>">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger reject-comment" 
                                                        data-id="<?= $comentario['id'] ?>">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger delete-comment" 
                                                    data-id="<?= $comentario['id'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

    <!-- Modal para ver comentario completo -->
    <div class="modal fade" id="viewCommentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Comentario completo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Usuario:</strong> <span id="modal-user"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Publicación:</strong> <span id="modal-publicacion"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Fecha:</strong> <span id="modal-date"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Comentario:</strong>
                        <div class="border p-3 mt-2 rounded" id="modal-content"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        // Filtrado por estado
        document.getElementById('filtroEstado').addEventListener('change', function() {
            const filtro = this.value;
            const filas = document.querySelectorAll('tbody tr');
            
            filas.forEach(fila => {
                const estado = fila.getAttribute('data-estado');
                
                if (filtro === 'todos' || estado === filtro) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        });

        // Ver comentario completo
        document.querySelectorAll('.view-comment').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('modal-user').textContent = this.dataset.user;
                document.getElementById('modal-publicacion').textContent = this.dataset.publicacion;
                document.getElementById('modal-date').textContent = this.dataset.date;
                document.getElementById('modal-content').textContent = this.dataset.content;
            });
        });

        // Aprobar comentario
        document.querySelectorAll('.approve-comment').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('¿Estás seguro de aprobar este comentario?')) {
                    const id = this.dataset.id;
                    fetch('comentario_accion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${id}&accion=aprobar`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
                }
            });
        });

        // Rechazar comentario
        document.querySelectorAll('.reject-comment').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('¿Estás seguro de rechazar este comentario?')) {
                    const id = this.dataset.id;
                    fetch('comentario_accion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${id}&accion=rechazar`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
                }
            });
        });

        // Eliminar comentario
        document.querySelectorAll('.delete-comment').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('¿Estás seguro de eliminar este comentario? Esta acción no se puede deshacer.')) {
                    const id = this.dataset.id;
                    fetch('comentario_accion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${id}&accion=eliminar`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>