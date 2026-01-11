<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$error = '';
$success = '';

// Verificar si viene de registro exitoso
if (isset($_GET['registro']) && $_GET['registro'] === 'exitoso') {
    $success = 'Registro exitoso. Ahora puedes iniciar sesión.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    try {
        $conexion = obtenerConexion();
        
        // Consulta para obtener usuario con su rol
        $sql = "SELECT u.id, u.nombre, u.email, u.password, u.activo, r.nombre as rol 
                FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                WHERE u.email = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verificar contraseña
            if ($password === $user['password'] || password_verify($password, $user['password'])) {
                if ($user['activo']) {
                    // Establecer sesión
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nombre'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['rol'];
                    
                    // Configurar cookie "recordarme" si está marcado
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 días
                        
                        // Guardar token en BD (opcional)
                        $stmt = $conexion->prepare("UPDATE usuarios SET remember_token = ? WHERE id = ?");
                        $stmt->bind_param("si", $token, $user['id']);
                        $stmt->execute();
                    }
                    
                    // Redirección según rol
                    switch ($user['rol']) {
                        case 'admin':
                            header("Location: ../admin/index.php");
                            break;
                        case 'editor':
                            header("Location: ../editor/index.php");
                            break;
                        default:
                            header("Location: ../index.php");
                    }
                    exit();
                } else {
                    $error = "Tu cuenta está desactivada. Contacta al administrador.";
                }
            } else {
                $error = "Credenciales incorrectas.";
            }
        } else {
            $error = "Credenciales incorrectas.";
        }
    } catch (Exception $e) {
        $error = "Error en el sistema. Intenta más tarde.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - ONT Bolivia</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_sup.png">
    <meta name="description" content="Accede a tu cuenta del Observatorio Nacional del Trabajo de Bolivia">
    
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
      
    <link href="../assets/css/auth.css" rel="stylesheet">
    <style>

        /* Botón de Google */
.social-login {
    margin: 24px 0;
}

.divider {
    position: relative;
    text-align: center;
    margin: 20px 0;
}

.divider::before {
    content: '';
    position: relative;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e1e5e9;
}

.divider span {
    background: white;
    padding: 0 16px;
    color: #6c757d;
    font-size: 14px;
    position: relative;
    z-index: 1;
}

.btn-google {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
    padding: 12px 24px;
    border: 2px solid #dadce0;
    border-radius: 8px;
    background: white;
    color: #3c4043;
    font-weight: 500;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn-google:hover {
    background: #f8f9fa;
    border-color: #c1c7cd;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    text-decoration: none;
    color: #3c4043;
}

.btn-google:active {
    background: #f1f3f4;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    transform: translateY(1px);
}

.google-icon {
    flex-shrink: 0;
}

/* Versión alternativa más moderna */
.btn-google-modern {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
    padding: 14px 24px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #4285f4 0%, #34a853 25%, #fbbc05 50%, #ea4335 75%);
    background-size: 400% 400%;
    color: white;
    font-weight: 600;
    font-size: 15px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(66, 133, 244, 0.3);
    position: relative;
    overflow: hidden;
}

.btn-google-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.1);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.btn-google-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(66, 133, 244, 0.4);
    animation: gradient 2s ease infinite;
    text-decoration: none;
    color: white;
}

.btn-google-modern:hover::before {
    opacity: 1;
}

@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
    </style>
</head>
<body>
    <div class="auth-container animate-fade-in-up">
        
        <div class="auth-brand">
            <div class="auth-brand-content">
                <div class="auth-logo">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h1>ONT Bolivia</h1>
                <p>Observatorio Nacional del Trabajo</p>
                <p>Información que transforma el trabajo, datos que impulsan el progreso</p>
                
                <div class="auth-features">
                    <div class="auth-feature">
                        <i class="bi bi-shield-check"></i>
                        <span>Acceso seguro y confiable</span>
                    </div>
                    <div class="auth-feature">
                        <i class="bi bi-graph-up"></i>
                        <span>Datos actualizados del mercado laboral</span>
                    </div>
                    <div class="auth-feature">
                        <i class="bi bi-people"></i>
                        <span>Comunidad de profesionales</span>
                    </div>
                    <div class="auth-feature">
                        <i class="bi bi-award"></i>
                        <span>Certificaciones y estudios</span>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="auth-form animate-slide-in-right">
            <div class="auth-header">
                <h2>Iniciar Sesión</h2>
                <p>Accede a tu cuenta para continuar</p>
            </div>

            <?php if ($error): ?>
                <div class="alert error">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success">
                    <i class="bi bi-check-circle"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm" novalidate>
                  
                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <div class="input-group">
                        <i class="input-icon bi bi-envelope"></i>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="tu@email.com"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                               required>
                    </div>
                    <div class="form-message error" id="emailError" style="display: none;">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>Por favor ingresa un email válido</span>
                    </div>
                </div>

                  
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <i class="input-icon bi bi-lock"></i>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Tu contraseña"
                               required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-message error" id="passwordError" style="display: none;">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>La contraseña es requerida</span>
                    </div>
                </div>

                 
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="checkbox-group">
                        <div class="checkbox">
                            <input type="checkbox" id="remember" name="remember">
                            <span class="checkbox-custom"></span>
                        </div>
                        <label for="remember" class="checkbox-label">Recordarme</label>
                    </div>
                    <a href="forgot-password.php" class="auth-link">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="submitBtn">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Iniciar Sesión</span>
                </button>

<!-- Agrega esto donde quieras mostrar el botón de Google -->
<div class="social-login">
    <div class="divider">
        <span>O inicia sesión con</span>
    </div>
    <a href="google-login.php" class="btn-google">
        <svg class="google-icon" viewBox="0 0 24 24" width="20" height="20">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Continuar con Google
    </a>
</div>

<!-- También agrega manejo de errores -->
<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger mt-3">
    <?php
    switch ($_GET['error']) {
        case 'google_auth':
            echo 'Error en la autenticación con Google. Intenta nuevamente.';
            break;
        case 'google_access_denied':
            echo 'Cancelaste la autenticación con Google.';
            break;
        default:
            echo 'Error desconocido en la autenticación.';
    }
    ?>
</div>
<?php endif; ?>
            </form>

            <div class="divider">
                <span>¿No tienes una cuenta?</span>
            </div>

            <a href="register.php" class="btn btn-outline btn-full">
                <i class="bi bi-person-plus"></i>
                <span>Crear Cuenta Nueva</span>
            </a>

            <div class="auth-footer">
                <p>Al iniciar sesión, aceptas nuestros 
                   <a href="#" class="auth-link">Términos de Servicio</a> y 
                   <a href="#" class="auth-link">Política de Privacidad</a>
                </p>
                <p class="mt-2">
                    <a href="../index.php" class="auth-link">
                        <i class="bi bi-arrow-left"></i> Volver al sitio web
                    </a>
                </p>
            </div>
        </div>
    </div>

    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/auth.js"></script>
</body>
</html>
