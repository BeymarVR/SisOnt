<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $acceptTerms = isset($_POST['accept_terms']);
    
    // Validaciones del servidor
    if (empty($nombre) || empty($email) || empty($password)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del email no es válido.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password !== $confirmPassword) {
        $error = "Las contraseñas no coinciden.";
    } elseif (!$acceptTerms) {
        $error = "Debes aceptar los términos y condiciones.";
    } else {
        // Hash de la contraseña
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $conexion = obtenerConexion();
            
            // Verificar si el email ya existe
            $checkStmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $error = "El email ya está registrado. Intenta con otro email.";
            } else {
                // Por defecto asignamos rol de usuario (id=1)
                $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password, rol_id, activo) VALUES (?, ?, ?, 3, 1)");
                $stmt->bind_param("sss", $nombre, $email, $passwordHash);
                
                if ($stmt->execute()) {
                    header("Location: login.php?registro=exitoso");
                    exit();
                } else {
                    $error = "Error al crear la cuenta. Intenta más tarde.";
                }
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Error en el sistema. Intenta más tarde.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - ONT Bolivia</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_sup.png">
    <meta name="description" content="Crea tu cuenta en el Observatorio Nacional del Trabajo de Bolivia">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <link href="../assets/css/auth.css" rel="stylesheet">
    
 
</head>
<body>
    <div class="auth-container animate-fade-in-up">
      
        <div class="auth-brand">
            <div class="auth-brand-content">
                <div class="auth-logo">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h1>Únete a ONT</h1>
                <p>Observatorio Nacional del Trabajo</p>
                <p>Forma parte de la comunidad que está transformando el mercado laboral boliviano</p>
                
                <div class="auth-features">
                    <div class="auth-feature">
                        <i class="bi bi-clipboard-data"></i>
                        <span>Acceso a encuestas exclusivas</span>
                    </div>
                    <div class="auth-feature">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Descarga de normativas actualizadas</span>
                    </div>
                    <div class="auth-feature">
                        <i class="bi bi-bell"></i>
                        <span>Notificaciones de nuevos estudios</span>
                    </div>
                    <div class="auth-feature">
                        <i class="bi bi-trophy"></i>
                        <span>Certificados de participación</span>
                    </div>
                </div>
            </div>
        </div>

       
        <div class="auth-form animate-slide-in-right">
            <div class="auth-header">
                <h2>Crear Cuenta</h2>
                <p>Completa tus datos para comenzar</p>
            </div>

             
            <?php if ($error): ?>
                <div class="alert error">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm" novalidate>
                
                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre Completo</label>
                    <div class="input-group">
                        <i class="input-icon bi bi-person"></i>
                        <input type="text" 
                               class="form-control" 
                               id="nombre" 
                               name="nombre" 
                               placeholder="Tu nombre completo"
                               value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>"
                               required>
                    </div>
                    <div class="form-message error" id="nombreError" style="display: none;">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>El nombre es requerido</span>
                    </div>
                </div>

                  
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
                    <div class="form-message success" id="emailSuccess" style="display: none;">
                        <i class="bi bi-check-circle"></i>
                        <span>Email disponible</span>
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
                               placeholder="Mínimo 6 caracteres"
                               required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="strength-meter">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="form-message" id="passwordStrength" style="display: none;">
                        <i class="bi bi-info-circle"></i>
                        <span>Fortaleza de la contraseña</span>
                    </div>
                    <div class="form-message error" id="passwordError" style="display: none;">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>La contraseña debe tener al menos 6 caracteres</span>
                    </div>
                </div>

                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                    <div class="input-group">
                        <i class="input-icon bi bi-lock-fill"></i>
                        <input type="password" 
                               class="form-control" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="Repite tu contraseña"
                               required>
                        <button type="button" class="password-toggle" id="toggleConfirmPassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-message error" id="confirmPasswordError" style="display: none;">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>Las contraseñas no coinciden</span>
                    </div>
                    <div class="form-message success" id="confirmPasswordSuccess" style="display: none;">
                        <i class="bi bi-check-circle"></i>
                        <span>Las contraseñas coinciden</span>
                    </div>
                </div>

                
                <div class="checkbox-group">
                    <div class="checkbox">
                        <input type="checkbox" id="accept_terms" name="accept_terms" required>
                        <span class="checkbox-custom"></span>
                    </div>
                    <label for="accept_terms" class="checkbox-label">
                        Acepto los <a href="#" class="auth-link">Términos de Servicio</a> y 
                        <a href="#" class="auth-link">Política de Privacidad</a>
                    </label>
                </div>
                <div class="form-message error" id="termsError" style="display: none;">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>Debes aceptar los términos y condiciones</span>
                </div>

                <button type="submit" class="btn btn-primary btn-full" id="submitBtn">
                    <i class="bi bi-person-plus"></i>
                    <span>Crear Mi Cuenta</span>
                </button>
            </form>

            <div class="divider">
                <span>¿Ya tienes una cuenta?</span>
            </div>

            <a href="login.php" class="btn btn-outline btn-full">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Iniciar Sesión</span>
            </a>

            <div class="auth-footer">
                <p>Al crear una cuenta, aceptas formar parte de nuestra comunidad de profesionales comprometidos con el desarrollo del mercado laboral boliviano.</p>
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
