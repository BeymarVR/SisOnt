<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/encuestas_functions.php';

verificarRol('usuario'); // Solo usuarios autenticados pueden ver encuestas
$encuestas = obtenerEncuestas('activa');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encuestas - ONT Bolivia</title>
    <meta name="description" content="Participa en las encuestas del Observatorio Nacional del Trabajo de Bolivia">
    <link rel="icon" type="image/png" href="assets/images/logo_sup.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <link href="./assets/css/style.css" rel="stylesheet">
    

     <style>
        .encuesta-icon {
            width: 24px;
            height: 24px;
        }
        .encuesta-icon-lg {
            width: 32px;
            height: 32px;
        }
        .encuesta-icon-xl {
            width: 48px;
            height: 48px;
        }
        .encuesta-icon-sm {
            width: 16px;
            height: 16px;
        }
    </style>
</head>
<body class="bg-gray-50">
   
<!-- Navigation -->
        <?php include './partials/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section" style="background: linear-gradient(135deg, #352f62 0%, #e45504 100%); padding: 120px 0;">
        <div class="container text-white text-center">
            <h1 class="display-4 fw-bold mb-4">Encuestas ONT</h1>
            <p class="lead mb-5">Participa en nuestras encuestas y ayúdanos a mejorar el mercado laboral boliviano</p>
        </div>
    </section>

    <!-- Main Content -->
    <section class="section-ont py-5">
        <div class="container">
            <?php if (empty($encuestas)): ?>
                <!-- No Surveys -->
                <div class="text-center py-5">
                    <div class="card card-ont p-5">
                        <div class="mb-4">
                            <i class="bi bi-clipboard-x" style="font-size: 3rem; color: var(--primary-orange);"></i>
                        </div>
                        <h2 class="mb-3">No hay encuestas disponibles</h2>
                        <p class="mb-4">
                            Actualmente no hay encuestas activas. Te notificaremos cuando haya nuevas encuestas disponibles.
                        </p>
                        <a href="index.php" class="btn btn-ont-primary">
                            <i class="bi bi-house-door me-2"></i>Volver al Inicio
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Surveys Grid -->
                <div class="text-center mb-5">
                    <h2 class="section-title">
                        <i class="bi bi-clipboard-check me-3"></i>Encuestas Disponibles
                    </h2>
                    <p class="lead">
                        Selecciona una encuesta para participar y compartir tu opinión
                    </p>
                </div>

                <div class="row">
                    <?php foreach ($encuestas as $encuesta): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card card-ont h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="bg-primary p-3 rounded me-3">
                                            <svg class="encuesta-icon-xl text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H6zm1 2a1 1 0 000 2h6a1 1 0 100-2H7zm6 7a1 1 0 011 1v3a1 1 0 11-2 0v-3a1 1 0 011-1zm-3 3a1 1 0 100 2h.01a1 1 0 100-2H10zm-4 1a1 1 0 011-1h.01a1 1 0 110 2H7a1 1 0 01-1-1zm1-4a1 1 0 100 2h.01a1 1 0 100-2H7zm2 1a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zm4-4a1 1 0 100 2h.01a1 1 0 100-2H13zM9 9a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zM7 8a1 1 0 000 2h.01a1 1 0 000-2H7z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h5 class="card-title"><?= htmlspecialchars($encuesta['titulo']) ?></h5>
                                            <?php if (!empty($encuesta['descripcion'])): ?>
                                                <p class="card-text text-muted"><?= substr(strip_tags($encuesta['descripcion']), 0, 120) ?>...</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?= date('d/m/Y', strtotime($encuesta['fecha_creacion'])) ?>
                                        </small>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Activa
                                        </span>
                                    </div>
                                    
                                    <div class="d-grid mt-3">
                                        <a href="encuesta_responder.php?id=<?= $encuesta['id'] ?>" 
                                           class="btn btn-ont-primary">
                                            <i class="bi bi-pencil-square me-2"></i>Participar Ahora
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

         <!-- Footer -->
    <?php include './partials/footer.php'; ?>

    <!-- Custom JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/main.js"></script>
</body>
</html>