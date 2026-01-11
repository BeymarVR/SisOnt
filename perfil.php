<?php
require_once './includes/auth.php';
require_once './includes/database.php';
require_once './includes/perfil_functions.php';

verificarAutenticacion();

// Obtener datos del usuario actual
$usuario = obtenerUsuarioPorId($_SESSION['user_id']);

// Procesar actualización de perfil
$mensaje = '';
$tipoMensaje = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono'] ?? '');
    $biografia = trim($_POST['biografia'] ?? '');
    
    // Validaciones
    if (empty($nombre)) {
        $errores[] = 'El nombre es obligatorio';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no es válido';
    }
    
    if (empty($errores)) {
        if (actualizarPerfil($_SESSION['user_id'], $nombre, $email, $telefono, $biografia)) {
            // Actualizar sesión
            $_SESSION['user_name'] = $nombre;
            $_SESSION['user_email'] = $email;
            
            $mensaje = 'Perfil actualizado correctamente';
            $tipoMensaje = 'success';
            
            // Recargar datos
            $usuario = obtenerUsuarioPorId($_SESSION['user_id']);
        } else {
            $mensaje = 'Error al actualizar el perfil';
            $tipoMensaje = 'danger';
        }
    }
}

// Obtener estadísticas del usuario
$estadisticas = obtenerEstadisticasUsuario($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - ONT Bolivia</title>
    <link rel="icon" type="image/png" href="assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    
    <style>
        .profile-header {
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-card {
            border-left: 4px solid #352f62;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            border-radius: 20px;
        }
        .nav-pills .nav-link {
            color: #495057;
            border-radius: 20px;
            margin: 5px 0;
        }
        

/* Estilos para las pestañas del perfil */
.profile-header {
    background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
    color: white;
    border-radius: 15px 15px 0 0;
}
.profile-avatar {
    width: 120px;
    height: 120px;
    border: 4px solid white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.stat-card {
    border-left: 4px solid #352f62;
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* ESTILOS CORREGIDOS PARA LAS PESTAÑAS */
.nav-pills .nav-link {
    color: #495057 !important;
    background-color: #f8f9fa !important;
    border-radius: 20px;
    margin: 5px;
    padding: 10px 20px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
}

.nav-pills .nav-link:hover {
    background-color: #e9ecef !important;
    color: #352f62 !important;
    border-color: #352f62;
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #352f62 0%, #e45504 100%) !important;
    color: white !important;
    border-color: #352f62;
    box-shadow: 0 4px 15px rgba(53, 47, 98, 0.3);
}

.nav-pills {
    background: white;
    padding: 15px;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

/* Mejorar la visibilidad del contenido de las pestañas */
.tab-content {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

/* Asegurar que el contenido sea visible */
.tab-pane {
    opacity: 1 !important;
    visibility: visible !important;
}

/* Estilos para la línea de tiempo de actividad */
.timeline-item {
    border-left: 3px solid #352f62;
    padding-left: 20px;
    margin-left: 10px;
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 0;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #352f62;
}

.timeline-badge {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
/* Nuevos estilos para avatar de Google */
        .profile-avatar-img {
            width: 120px;
            height: 120px;
            border: 4px solid white;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .google-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: white;
            border-radius: 50%;
            padding: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .provider-badge {
            background: linear-gradient(135deg, #4285F4 0%, #34A853 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
</style>
</head>
<body>
    <!-- Navigation -->
    <?php include './partials/navbar.php'; ?>

    <main class="container py-5">
        <div class="row">
            <div class="col-lg-12">
                 <!-- Header del Perfil -->
                <div class="card profile-header mb-4">
                    <div class="card-body text-center py-5">
                        <div class="mb-3 position-relative d-inline-block">
                            <?php if ($usuario['avatar']): ?>
                                <!-- Mostrar avatar de Google -->
                                <img src="<?= htmlspecialchars($usuario['avatar']) ?>" 
                                     alt="<?= htmlspecialchars($usuario['nombre']) ?>"
                                     class="profile-avatar-img">
                              
                            <?php else: ?>
                                <!-- Mostrar avatar por defecto -->
                                <div class="profile-avatar rounded-circle d-inline-flex align-items-center justify-content-center bg-white text-primary fs-1 fw-bold">
                                    <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h1 class="h2 mb-2"><?= htmlspecialchars($usuario['nombre']) ?></h1>
                        <p class="mb-1">
                            <i class="bi bi-envelope me-2"></i><?= htmlspecialchars($usuario['email']) ?>
                        </p>
                        <p class="mb-0">
                            <span class="badge bg-light text-dark me-2">
                                <i class="bi bi-person-badge me-1"></i>
                                <?= ucfirst($usuario['rol_nombre']) ?>
                            </span>
                            <?php if ($usuario['provider'] === 'google'): ?>
                                <span class="provider-badge">
                                    <i class="bi bi-google me-1"></i>Google
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- Menú de Navegación -->
                <div class="card mb-4">
                    <div class="card-body">
                        <ul class="nav nav-pills justify-content-center" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="info-tab" data-bs-toggle="pill" data-bs-target="#info" type="button" role="tab">
                                    <i class="bi bi-info-circle me-2"></i>Información
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="stats-tab" data-bs-toggle="pill" data-bs-target="#stats" type="button" role="tab">
                                    <i class="bi bi-bar-chart me-2"></i>Estadísticas
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="activity-tab" data-bs-toggle="pill" data-bs-target="#activity" type="button" role="tab">
                                    <i class="bi bi-clock-history me-2"></i>Actividad
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">
                                    <i class="bi bi-shield-lock me-2"></i>Seguridad
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Contenido de las Pestañas -->
                <div class="tab-content" id="profileTabsContent">
                    <!-- Pestaña de Información -->
                    <div class="tab-pane fade show active" id="info" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-8 mx-auto">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-person-circle me-2"></i>Información Personal
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($mensaje): ?>
                                            <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show">
                                                <?= $mensaje ?>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($errores)): ?>
                                            <div class="alert alert-danger">
                                                <ul class="mb-0">
                                                    <?php foreach ($errores as $error): ?>
                                                        <li><?= $error ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>

                                        <form method="POST">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Nombre completo *</label>
                                                    <input type="text" class="form-control" name="nombre" 
                                                           value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Email *</label>
                                                    <input type="email" class="form-control" name="email" 
                                                           value="<?= htmlspecialchars($usuario['email']) ?>" required>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Teléfono</label>
                                                    <input type="tel" class="form-control" name="telefono" 
                                                           value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Rol</label>
                                                    <input type="text" class="form-control" 
                                                           value="<?= ucfirst($usuario['rol_nombre']) ?>" disabled>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Biografía</label>
                                                <textarea class="form-control" name="biografia" rows="4"
                                                          placeholder="Cuéntanos algo sobre ti..."><?= htmlspecialchars($usuario['biografia'] ?? '') ?></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Fecha de registro</label>
                                                <input type="text" class="form-control" 
                                                       value="<?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?>" disabled>
                                            </div>

                                            <button type="submit" class="btn btn-ont-primary">
                                                <i class="bi bi-check-circle me-2"></i>Guardar cambios
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña de Estadísticas -->
                    <div class="tab-pane fade" id="stats" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-10 mx-auto">
                                <div class="row">
                                    <!-- Tarjeta de Comentarios -->
                                    <div class="col-md-4 mb-4">
                                        <div class="card stat-card h-100">
                                            <div class="card-body text-center">
                                                <div class="text-primary mb-3">
                                                    <i class="bi bi-chat-dots display-6"></i>
                                                </div>
                                                <h3 class="card-title"><?= $estadisticas['total_comentarios'] ?></h3>
                                                <p class="card-text">Comentarios realizados</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tarjeta de Encuestas -->
                                    <div class="col-md-4 mb-4">
                                        <div class="card stat-card h-100">
                                            <div class="card-body text-center">
                                                <div class="text-success mb-3">
                                                    <i class="bi bi-clipboard-check display-6"></i>
                                                </div>
                                                <h3 class="card-title"><?= $estadisticas['total_encuestas'] ?></h3>
                                                <p class="card-text">Encuestas respondidas</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tarjeta de Actividad -->
                                    <div class="col-md-4 mb-4">
                                        <div class="card stat-card h-100">
                                            <div class="card-body text-center">
                                                <div class="text-warning mb-3">
                                                    <i class="bi bi-clock-history display-6"></i>
                                                </div>
                                                <h3 class="card-title"><?= $estadisticas['dias_registro'] ?></h3>
                                                <p class="card-text">Días como miembro</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gráfico de actividad (placeholder) -->
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-bar-chart me-2"></i>Actividad Mensual
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center py-4">
                                            <i class="bi bi-bar-chart display-1 text-muted"></i>
                                            <p class="text-muted mt-3">Gráfico de actividad en desarrollo</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña de Actividad -->
                    <div class="tab-pane fade" id="activity" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-8 mx-auto">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-clock-history me-2"></i>Historial de Actividad
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php $actividad = obtenerActividadUsuario($_SESSION['user_id']); ?>
                                        
                                        <?php if (empty($actividad)): ?>
                                            <div class="text-center py-4">
                                                <i class="bi bi-inbox display-1 text-muted"></i>
                                                <p class="text-muted mt-3">No hay actividad registrada</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="timeline">
                                                <?php foreach ($actividad as $evento): ?>
                                                    <div class="timeline-item mb-3">
                                                        <div class="d-flex">
                                                            <div class="timeline-badge bg-<?= 
                                                                $evento['tipo'] === 'comentario' ? 'primary' : 
                                                                ($evento['tipo'] === 'encuesta' ? 'success' : 'info') 
                                                            ?> me-3">
                                                                <i class="bi bi-<?= 
                                                                    $evento['tipo'] === 'comentario' ? 'chat' : 
                                                                    ($evento['tipo'] === 'encuesta' ? 'clipboard-check' : 'person') 
                                                                ?>"></i>
                                                            </div>
                                                            <div class="timeline-content">
                                                                <h6 class="mb-1"><?= htmlspecialchars($evento['titulo']) ?></h6>
                                                                <p class="text-muted mb-1"><?= htmlspecialchars($evento['descripcion']) ?></p>
                                                                <small class="text-muted">
                                                                    <i class="bi bi-clock me-1"></i>
                                                                    <?= date('d/m/Y H:i', strtotime($evento['fecha'])) ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña de Seguridad -->
                    <div class="tab-pane fade" id="security" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-6 mx-auto">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-shield-lock me-2"></i>Seguridad de la Cuenta
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Para cambiar tu contraseña, contacta con un administrador.
                                        </div>

                                        <div class="mb-4">
                                            <h6 class="mb-3">Sesiones activas</h6>
                                            <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                                <div>
                                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                    <span>Sesión actual</span>
                                                </div>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y H:i') ?>
                                                </small>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <h6 class="mb-3">Estado de la cuenta</h6>
                                            <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                                <div>
                                                    <i class="bi bi-shield-check me-2"></i>
                                                    <span>Cuenta verificada</span>
                                                </div>
                                                <span class="badge bg-success">Activa</span>
                                            </div>
                                        </div>

                                        <div class="text-center">
                                            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar todas las sesiones
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Cerrar Sesión -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cerrar todas las sesiones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas cerrar todas las sesiones activas? Se te pedirá que inicies sesión nuevamente.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="./auth/logout.php?all=1" class="btn btn-danger">Cerrar todas las sesiones</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include './partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activar pestañas
        const triggerTabList = document.querySelectorAll('#profileTabs button');
        triggerTabList.forEach(triggerEl => {
            new bootstrap.Tab(triggerEl);
        });

        // Validación de formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const nombre = document.querySelector('input[name="nombre"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            
            if (!nombre) {
                e.preventDefault();
                alert('El nombre es obligatorio');
                return false;
            }
            
            if (!email || !email.includes('@')) {
                e.preventDefault();
                alert('Por favor, introduce un email válido');
                return false;
            }
        });
    </script>
</body>
</html>