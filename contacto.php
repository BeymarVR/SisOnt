<?php
require_once './includes/auth.php';
require_once './includes/database.php';

// Procesar formulario de contacto
$mensajeEnviado = false;
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $asunto = trim($_POST['asunto']);
    $mensaje = trim($_POST['mensaje']);
    
    // Validaciones
    if (empty($nombre)) {
        $errores[] = 'El nombre es obligatorio';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no es válido';
    }
    
    if (empty($asunto)) {
        $errores[] = 'El asunto es obligatorio';
    }
    
    if (empty($mensaje) || strlen($mensaje) < 10) {
        $errores[] = 'El mensaje debe tener al menos 10 caracteres';
    }
    
    // Si no hay errores, procesar el mensaje
    if (empty($errores)) {
        // Guardar en base de datos (opcional)
        guardarMensajeContacto($nombre, $email, $asunto, $mensaje);
        
        // Enviar email (configurar según tu servidor)
        // enviarEmailContacto($nombre, $email, $asunto, $mensaje);
        
        $mensajeEnviado = true;
    }
}

function guardarMensajeContacto($nombre, $email, $asunto, $mensaje) {
    $conexion = obtenerConexion();
    
    $sql = "INSERT INTO contactos (nombre, email, asunto, mensaje, fecha_creacion) 
            VALUES (?, ?, ?, ?, NOW())";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $email, $asunto, $mensaje);
    
    return $stmt->execute();
}

// Crear tabla contactos si no existe (ejecutar una vez)
function crearTablaContactos() {
    $conexion = obtenerConexion();
    
    $sql = "CREATE TABLE IF NOT EXISTS contactos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        asunto VARCHAR(200) NOT NULL,
        mensaje TEXT NOT NULL,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        leido TINYINT(1) DEFAULT 0
    )";
    
    return $conexion->query($sql);
}

// Ejecutar una vez para crear la tabla:
// crearTablaContactos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - ONT Bolivia</title>
    <meta name="description" content="Contacta con el Observatorio Nacional del Trabajo de Bolivia">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    
    <style>
        .contact-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }
        .contact-info-card {
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            color: white;
            border-radius: 10px;
            height: 100%;
        }
        .form-control:focus {
            border-color: #352f62;
            box-shadow: 0 0 0 0.2rem rgba(53, 47, 98, 0.25);
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
                        <i class="bi bi-envelope me-3"></i>Contacto
                    </h1>
                    <p class="lead">Estamos aquí para ayudarte. Contáctanos para cualquier consulta o información</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if ($mensajeEnviado): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        ¡Mensaje enviado correctamente! Te contactaremos pronto.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Error:</strong>
                        <ul class="mb-0">
                            <?php foreach ($errores as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Formulario de Contacto -->
                    <!-- <div class="col-lg-8">
                        <div class="card contact-form">
                            <div class="card-body p-4">
                                <h3 class="card-title mb-4">
                                    <i class="bi bi-send me-2"></i>Envíanos un mensaje
                                </h3>
                                
                                <form method="POST" novalidate>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nombre" class="form-label">Nombre completo *</label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                                   value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="asunto" class="form-label">Asunto *</label>
                                        <input type="text" class="form-control" id="asunto" name="asunto" 
                                               value="<?= htmlspecialchars($_POST['asunto'] ?? '') ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="mensaje" class="form-label">Mensaje *</label>
                                        <textarea class="form-control" id="mensaje" name="mensaje" 
                                                  rows="5" required><?= htmlspecialchars($_POST['mensaje'] ?? '') ?></textarea>
                                        <div class="form-text">Mínimo 10 caracteres</div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-ont-primary btn-lg">
                                        <i class="bi bi-send me-2"></i>Enviar mensaje
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div> -->

                    <!-- Información de Contacto -->
                    <div class="col-lg-4">
                        <div class="contact-info-card p-4">
                            <h4 class="mb-4">
                                <i class="bi bi-info-circle me-2"></i>Información de contacto
                            </h4>
                            
                            <div class="mb-4">
                                <h5><i class="bi bi-geo-alt me-2"></i>Dirección</h5>
                                <p class="mb-0">Calle Héroes del Acre No. 1855 esquina Landaeta</p>
                                <p>La Paz, Bolivia</p>
                            </div>
                            
                            <div class="mb-4">
                                <h5><i class="bi bi-envelope me-2"></i>Email</h5>
                                <p class="mb-0">
                                    <a href="mailto:info@ont.bolivia.bo" class="text-white">info@ont.bolivia.bo</a>
                                </p>
                            </div>
                            
                            <div class="mb-4">
                                <h5><i class="bi bi-telephone me-2"></i>Teléfono</h5>
                                <p class="mb-0">
                                    <a href="tel:64295040" class="text-white">64295040</a>
                                </p>
                            </div>
                            
                            <div class="mb-4">
                                <h5><i class="bi bi-clock me-2"></i>Horario de atención</h5>
                                <p class="mb-0">Lunes a Viernes: 8:00 - 18:00</p>
                                <p>Sábados: 9:00 - 12:00</p>
                            </div>
                            
                            <div class="social-links">
                                <h5 class="mb-3">Síguenos en:</h5>
                                <div class="d-flex gap-2">
                                    <a href="#" class="btn btn-outline-light btn-sm">
                                        <i class="bi bi-facebook"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-light btn-sm">
                                        <i class="bi bi-twitter"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-light btn-sm">
                                        <i class="bi bi-linkedin"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-light btn-sm">
                                        <i class="bi bi-youtube"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mapa -->
                <!-- <div class="row mt-5">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="ratio ratio-16x9">
                                    <iframe 
                                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3824.23456789!2d-68.12345678901234!3d-16.123456789012345!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTbCsDA3JzI0LjQiUyA2OMKwMDcnMzYuNyJX!5e0!3m2!1ses!2sbo!4v1234567890123!5m2!1ses!2sbo" 
                                        style="border:0;" 
                                        allowfullscreen="" 
                                        loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade">
                                    </iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include './partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>