<?php
// admin/encuestas.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/encuestas_functions.php';
verificarRol('admin'); // o permitir editor también si quieres

$encuestas = obtenerEncuestas();

// acciones (activar/desactivar/eliminar)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    if ($action === 'activar') {
        cambiarEstadoEncuesta($id, 'activa');
        header("Location: encuestas.php"); exit;
    } elseif ($action === 'desactivar') {
        cambiarEstadoEncuesta($id, 'inactiva');
        header("Location: encuestas.php"); exit;
    } elseif ($action === 'eliminar') {
        eliminarEncuesta($id);
        header("Location: encuestas.php"); exit;
    }
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
          
       <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title">Gestión de Encuestas</h1>
            </div>
            
        </header>


      
        <div class="content-area">
            
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-header">
                        <div class="stat-icon primary">
                            <i class="bi bi-list-check"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?= count($encuestas) ?></h3>
                        <p>Total Encuestas</p>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($encuestas, fn($e) => $e['estado'] === 'activa')) ?></h3>
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
                        <h3><?= count(array_filter($encuestas, fn($e) => $e['estado'] === 'inactiva')) ?></h3>
                        <p>Inactivas</p>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-icon info">
                            <i class="bi bi-graph-up"></i>
                        </div>
                    </div>
                    <div class="stat-content">
                        <h3><?= array_sum(array_map(fn($e) => obtenerNumeroRespuestas($e['id']), $encuestas)) ?></h3>
                        <p>Total Respuestas</p>
                    </div>
                </div>
            </div>
            
<!-- Actions Bar -->
            <div class="content-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="content-title">Lista de Encuestas</h2>
                        <p class="content-subtitle">Gestiona las encuestas y cuestionarios del sistema</p>
                    </div>
                    <div class="header-actions">
                        <a href="encuesta_editar.php" class="btn-ont primary">
                             <i class="bi bi-plus-circle"></i>
                            Nueva Encuesta
                        </a>
                    </div>
                </div>
            </div>

             <div class="admin-card">
               
                <div class="data-table">
    <div class="table-header d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Lista de Encuestas</h5>
    <div class="table-actions">
        <input type="text" class="form-control search-input" placeholder="Buscar encuestas..." 
               data-target=".data-table table tbody"
               style="width: 250px;">
    </div>
</div>
   

    <?php if (empty($encuestas)): ?>
        <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-circle"></i> No hay encuestas registradas.
        </div>
    <?php else: ?>
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Creador</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($encuestas as $e): ?>
                <tr class="searchable-item">
                    <td class="fw-semibold">
                        <i class="bi bi-clipboard-check me-2 text-primary"></i>
                        <?= htmlspecialchars($e['titulo']) ?>
                    </td>
                    <td>
                        <div class="user-info-mini">
                            
                            <span><?= htmlspecialchars($e['creador'] ?? 'Admin') ?></span>
                        </div>
                    </td>
                    <td>
                        <span class="badge-ont <?= $e['estado'] === 'activa' ? 'success' : 'warning' ?>">
                            <?= ucfirst($e['estado']) ?>
                        </span>
                    </td>
                    <td>
                        <small class="text-muted">
                            <?= date('d/m/Y', strtotime($e['fecha_creacion'])) ?><br>
                            <?= date('H:i', strtotime($e['fecha_creacion'])) ?>
                        </small>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="encuesta_resultados.php?id=<?= $e['id'] ?>" 
                               class="btn btn-sm btn-outline-info" title="Ver Resultados">
                                <i class="bi bi-bar-chart"></i>
                            </a>

                            <?php if (!encuestaTieneRespuestas($e['id'])): ?>
                                <a href="encuesta_editar.php?id=<?= $e['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-secondary" 
                                        disabled title="No se puede editar (tiene respuestas)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            <?php endif; ?>

                            <?php if ($e['estado'] === 'activa'): ?>
                                <a href="?action=desactivar&id=<?= $e['id'] ?>" 
                                   class="btn btn-sm btn-outline-secondary" title="Desactivar">
                                    <i class="bi bi-pause"></i>
                                </a>
                            <?php else: ?>
                                <a href="?action=activar&id=<?= $e['id'] ?>" 
                                   class="btn btn-sm btn-outline-success" title="Activar">
                                    <i class="bi bi-play"></i>
                                </a>
                            <?php endif; ?>

                            <a href="?action=eliminar&id=<?= $e['id'] ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('¿Está seguro de eliminar esta encuesta?')"
                               title="Eliminar">
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


                <?php if (empty($encuestas)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-list-check"></i>
                </div>
                <h3>No hay encuestas registradas</h3>
                <p>Comienza creando tu primera encuesta para recopilar información de los usuarios.</p>
                <a href="encuesta_editar.php" class="btn-ont primary">
                    <i class="bi bi-plus-lg"></i>
                    Crear Primera Encuesta
                </a>
            </div>
            <?php endif; ?>
            </div>
 
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
