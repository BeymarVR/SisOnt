<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/popups_functions.php';

verificarRol('admin'); // Solo admin puede gestionar pop-ups

$conexion = obtenerConexion();

if (!isset($_GET['id'])) {
    header("Location: pop_ups.php");
    exit;
}

$id = (int)$_GET['id'];

// Obtener información del pop-up
$stmt = $conexion->prepare("SELECT * FROM pop_ups WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$popup = $result->fetch_assoc();

if (!$popup) {
    header("Location: pop_ups.php?error=not_found");
    exit;
}

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    deletePopup($conexion, $id);
    header("Location: pop_ups.php?success=deleted");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Pop-up - Panel ONT</title>
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
                <h1 class="page-title">Eliminar Pop-up</h1>
            </div>
            <div class="header-right">
                <a href="pop_ups.php" class="btn-ont secondary">
                    <i class="bi bi-arrow-left"></i>
                    Volver
                </a>
            </div>
        </header>

        <div class="content-area">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="form-card">
                        <div class="form-card-header">
                            <h5><i class="bi bi-exclamation-triangle text-danger me-2"></i>Confirmar Eliminación</h5>
                        </div>
                        <div class="form-card-body">
                            <div class="alert-ont warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <div>
                                    <strong>¡Atención!</strong>
                                    <p class="mb-0">Esta acción no se puede deshacer. El pop-up y todos sus datos asociados serán eliminados permanentemente.</p>
                                </div>
                            </div>

                            <div class="popup-preview">
                                <h6>Información del Pop-up:</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Título:</strong></td>
                                        <td><?= htmlspecialchars($popup['titulo']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tipo:</strong></td>
                                        <td>
                                            <span class="badge-ont <?php 
                                                echo $popup['tipo'] == 'imagen' ? 'primary' : 
                                                    ($popup['tipo'] == 'video' ? 'success' : 
                                                    ($popup['tipo'] == 'documento' ? 'warning' : 'info')); 
                                            ?>">
                                                <?= ucfirst($popup['tipo']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Estado:</strong></td>
                                        <td>
                                            <span class="badge-ont <?= $popup['activo'] ? 'success' : 'secondary' ?>">
                                                <?= $popup['activo'] ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Creado:</strong></td>
                                        <td><?= date('d/m/Y H:i', strtotime($popup['fecha_creacion'])) ?></td>
                                    </tr>
                                    <?php if ($popup['descripcion']): ?>
                                    <tr>
                                        <td><strong>Descripción:</strong></td>
                                        <td><?= htmlspecialchars($popup['descripcion']) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>

                            <form method="POST" class="mt-4">
                                <div class="d-flex gap-3 justify-content-end">
                                    <a href="pop_ups.php" class="btn-ont secondary">
                                        <i class="bi bi-x-lg"></i>
                                        Cancelar
                                    </a>
                                    <button type="submit" class="btn-ont danger">
                                        <i class="bi bi-trash"></i>
                                        Sí, Eliminar Pop-up
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>