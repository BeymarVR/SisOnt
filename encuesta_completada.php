<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/encuestas_functions.php';
require_once __DIR__ . '/includes/config.php';

verificarRol('usuario');

$encuesta_id = $_GET['id'] ?? 0;
$conexion = obtenerConexion();

// Obtener información de la encuesta
$stmt = $conexion->prepare("SELECT titulo FROM encuestas WHERE id = ?");
$stmt->bind_param("i", $encuesta_id);
$stmt->execute();
$encuesta = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encuesta Completada - ONT Bolivia</title>
    <meta name="description" content="Encuesta completada exitosamente - Observatorio Nacional del Trabajo de Bolivia">
    <link rel="icon" type="image/png" href="assets/images/logo_sup.png">
    <!-- Replaced Tailwind with Bootstrap and ONT styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    
    <style>
        .success-animation {
            animation: bounceIn 0.8s ease-out;
        }
        
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #e45504;
            animation: confetti-fall 3s linear infinite;
        }
        
        .confetti:nth-child(odd) {
            background: #352f62;
            animation-delay: 0.5s;
        }
        
        @keyframes confetti-fall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        
        .celebration-card {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border: 2px solid #e9ecef;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!--navegacion-->
    <?php include './partials/navbar.php'; ?>

    <!-- Added hero section with ONT gradient -->
    <section class="hero-section" style="background: linear-gradient(135deg, #352f62 0%, #e45504 100%); padding: 120px 0 60px 0;">
        <div class="container text-white text-center">
            <div class="success-animation">
                <i class="bi bi-check-circle-fill" style="font-size: 5rem; color: #28a745;"></i>
            </div>
            <h1 class="display-4 fw-bold mt-4 mb-3">¡Encuesta Completada!</h1>
            <p class="lead mb-0">Tu participación ha sido registrada exitosamente</p>
        </div>
    </section>

    <!-- Updated main content with Bootstrap cards and ONT styling -->
    <section class="section-ont py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="celebration-card p-5 text-center mb-5">
                        <div class="mb-4">
                            <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-trophy-fill text-success" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                        
                        <h2 class="fw-bold mb-3">¡Gracias por tu participación!</h2>
                        <p class="text-muted mb-2">
                            Has completado exitosamente la encuesta:
                        </p>
                        <h4 class="text-primary mb-4">
                            <i class="bi bi-clipboard-check me-2"></i>
                            "<?= htmlspecialchars($encuesta['titulo'] ?? 'Encuesta ONT') ?>"
                        </h4>
                        
                        <div class="bg-light p-4 rounded mb-4">
                            <div class="row text-center">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <i class="bi bi-check-circle text-success fs-3 mb-2 d-block"></i>
                                    <small class="text-muted">Respuestas<br>Guardadas</small>
                                </div>
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <i class="bi bi-graph-up text-primary fs-3 mb-2 d-block"></i>
                                    <small class="text-muted">Datos<br>Procesados</small>
                                </div>
                                <div class="col-md-4">
                                    <i class="bi bi-heart-fill text-danger fs-3 mb-2 d-block"></i>
                                    <small class="text-muted">Contribución<br>Valiosa</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="encuestas.php" class="btn btn-ont-primary">
                                <i class="bi bi-list-ul me-2"></i>Ver Más Encuestas
                            </a>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-house-door me-2"></i>Ir al Inicio
                            </a>
                        </div>
                    </div>
                    
                    <!-- Added information card with ONT styling -->
                    <div class="card card-ont">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3 flex-shrink-0">
                                    <i class="bi bi-info-circle text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">¿Qué sigue ahora?</h5>
                                    <p class="card-text text-muted mb-3">
                                        Los resultados de esta encuesta serán analizados por nuestro equipo especializado 
                                        para generar insights valiosos que contribuyan al desarrollo del mercado laboral boliviano.
                                    </p>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <i class="bi bi-check-circle text-success me-2"></i>
                                            Tus respuestas se mantendrán confidenciales
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-check-circle text-success me-2"></i>
                                            Los resultados se publicarán en informes agregados
                                        </li>
                                        <li>
                                            <i class="bi bi-check-circle text-success me-2"></i>
                                            Recibirás notificaciones sobre nuevas encuestas
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

           <!-- Footer -->
    <?php include './partials/footer.php'; ?>

    <!-- Updated scripts and added celebration effects -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/main.js"></script>
    <script>
        // Celebration confetti effect
        document.addEventListener('DOMContentLoaded', function() {
            // Create confetti
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.animationDelay = Math.random() * 3 + 's';
                    confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                    document.body.appendChild(confetti);
                    
                    // Remove confetti after animation
                    setTimeout(() => {
                        confetti.remove();
                    }, 5000);
                }, i * 100);
            }
        });
    </script>
</body>
</html>
