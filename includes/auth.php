<?php
require_once 'database.php';
// Agregamos la inclusión de las funciones de Google
require_once 'google-auth-functions.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function iniciarSesion($usuario) {
    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['user_name'] = $usuario['nombre'];
    $_SESSION['user_email'] = $usuario['email'];
    $_SESSION['user_role'] = $usuario['rol_nombre'];
    $_SESSION['user_avatar'] = $usuario['avatar'] ?? null;
    $_SESSION['provider'] = $usuario['provider'] ?? 'local';
}

function verificarAutenticacion() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /SIS_ONT/auth/login.php");
        exit();
    }
}

function verificarRol($rolRequerido) {
    verificarAutenticacion();
    
    // Obtener el ID del rol actual desde la base de datos
    $conexion = obtenerConexion();
    $stmt = $conexion->prepare("SELECT rol_id FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    
    // Si el rol requerido es 'usuario' (ID 3), permitir acceso
    if ($rolRequerido === 'usuario' && $usuario['rol_id'] == 3) {
        return; // Acceso permitido para usuarios normales
    }
    
    // Para otros roles, verificar por nombre (compatibilidad hacia atrás)
    if ($_SESSION['user_role'] !== $rolRequerido) {
        header("Location: /SIS_ONT/acceso-denegado.php");
        exit();
    }
}

function redirigirSegunRol() {
    if (!isset($_SESSION['user_role'])) return;
    
    $base_url = '/SIS_ONT';
    switch ($_SESSION['user_role']) {
        case 'admin':
            header("Location: $base_url/admin/index.php");
            break;
        case 'editor':
            header("Location: $base_url/editor/index.php");
            break;
        default:
            header("Location: $base_url/usuario/index.php");
    }
    exit();
}

function validarArchivoSubido($file, $tiposPermitidos) {
    // Verificar errores
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Verificar tipo
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $tiposPermitidos)) {
        return false;
    }
    
    // Verificar que es un archivo subido real
    return is_uploaded_file($file['tmp_name']);
}

/**
 * Función para manejar el callback de Google OAuth
 */

function handleGoogleCallback() {
    if (isset($_GET['code'])) {
        try {
            // 1. Obtener token de acceso
            $accessToken = getGoogleAccessToken($_GET['code']);
            
            if (!$accessToken) {
                throw new Exception('No se pudo obtener el token de acceso de Google');
            }
            
            // 2. Obtener información del usuario
            $userInfo = getGoogleUserInfo($accessToken);
            
            if (!$userInfo || !isset($userInfo['email'])) {
                throw new Exception('No se pudo obtener la información del usuario de Google');
            }
            
            // 3. Buscar o crear usuario en la base de datos
            $conexion = obtenerConexion();
            $google_id = $userInfo['id'];
            $email = $userInfo['email'];
            $name = $userInfo['name'];
            $avatar = $userInfo['picture'] ?? null;
            
            // Verificar si el usuario ya existe
            $stmt = $conexion->prepare("
                SELECT u.*, r.nombre as rol_nombre 
                FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                WHERE u.email = ? OR u.google_id = ?
            ");
            $stmt->bind_param("ss", $email, $google_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $usuario = $result->fetch_assoc();
            
            if ($usuario) {
                // Actualizar usuario existente con datos de Google
                $stmt = $conexion->prepare("
                    UPDATE usuarios 
                    SET google_id = ?, avatar = ?, provider = 'google' 
                    WHERE id = ?
                ");
                $stmt->bind_param("ssi", $google_id, $avatar, $usuario['id']);
                $stmt->execute();
            } else {
                // Crear nuevo usuario
                $default_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $rol_id = 3; // Rol por defecto: usuario
                
                $stmt = $conexion->prepare("
                    INSERT INTO usuarios (nombre, email, password, google_id, avatar, provider, rol_id) 
                    VALUES (?, ?, ?, ?, ?, 'google', ?)
                ");
                $stmt->bind_param("sssssi", $name, $email, $default_password, $google_id, $avatar, $rol_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Error al crear nuevo usuario: ' . $stmt->error);
                }
                
                // Obtener el usuario recién creado
                $user_id = $conexion->insert_id;
                $stmt = $conexion->prepare("
                    SELECT u.*, r.nombre as rol_nombre 
                    FROM usuarios u 
                    INNER JOIN roles r ON u.rol_id = r.id 
                    WHERE u.id = ?
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $usuario = $result->fetch_assoc();
            }
            
            // 4. Iniciar sesión
            if ($usuario) {
                iniciarSesion($usuario);
                return true;
            } else {
                throw new Exception('Error al obtener información del usuario');
            }
            
        } catch (Exception $e) {
            error_log("Error en autenticación Google: " . $e->getMessage());
            return false;
        }
    }
    return false;
}

$googleAuthUrl = getGoogleAuthUrl(); // Esto llama a la función del otro archivo


/**
 * Función para mostrar el avatar del usuario en la interfaz
 */
function mostrarAvatarUsuario() {
    if (isset($_SESSION['user_id'])) {
        $avatar = $_SESSION['user_avatar'] ?? '../assets/images/default-avatar.png';
        $nombre = htmlspecialchars($_SESSION['user_name']);
        
        return '<img src="' . $avatar . '" alt="' . $nombre . '" class="user-avatar" style="width:32px;height:32px;border-radius:50%;margin-right:8px;">' . $nombre;
    }
    return '';
}

/**
 * Función para verificar si el usuario actual está autenticado con Google
 */
function esUsuarioGoogle() {
    return isset($_SESSION['provider']) && $_SESSION['provider'] === 'google';
}

/**
 * Función para cerrar sesión (maneja tanto usuarios locales como Google)
 */
function cerrarSesion() {
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
    
    // Redirigir a login
    header("Location: /SIS_ONT/auth/login.php");
    exit();
}

/**
 * Carga los datos del usuario desde la BD a la sesión.
 * Retorna true si se cargó correctamente.
 */
function setUserSessionFromDb(int $userId): bool {
    try {
        $cn = obtenerConexion();
        if (!$cn) {
            error_log("Error: No se pudo obtener conexión a BD en setUserSessionFromDb");
            return false;
        }

        $stmt = $cn->prepare("
            SELECT u.id, u.nombre, u.email, u.avatar, u.activo, r.nombre AS rol_nombre
            FROM usuarios u
            LEFT JOIN roles r ON u.rol_id = r.id
            WHERE u.id = ?
            LIMIT 1
        ");
        
        if (!$stmt) {
            error_log("Error al preparar consulta: " . $cn->error);
            return false;
        }

        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            error_log("Error al ejecutar consulta: " . $stmt->error);
            return false;
        }

        $res = $stmt->get_result()->fetch_assoc();
        if ($res) {
            // Mantener compatibilidad con claves existentes
            $_SESSION['user_id']     = (int)$res['id'];
            $_SESSION['user_nombre'] = $res['nombre'];
            $_SESSION['user_name']   = $res['nombre'];
            $_SESSION['user_email']  = $res['email'];
            $_SESSION['user_avatar'] = $res['avatar'] ?: null;
            $_SESSION['user_active'] = (int)$res['activo'];
            $_SESSION['user_role']   = $res['rol_nombre'] ?? 'usuario';
            
            error_log("Sesión cargada para usuario ID: $userId, Rol: " . $_SESSION['user_role']);
            return true;
        }
        
        error_log("Usuario ID $userId no encontrado en BD");
        return false;
        
    } catch (Exception $e) {
        error_log("Excepción en setUserSessionFromDb: " . $e->getMessage());
        return false;
    }
}

/**
 * Redirige al usuario según su rol guardado en sesión.
 */
function redirectAfterLogin(): void {
    // Base URL del proyecto (ajusta si el proyecto está en otra carpeta)
    $base_url = '/SIS_ONT';

    $role = strtolower($_SESSION['user_role'] ?? 'usuario');
    error_log("Redirigiendo usuario con rol: $role");

    if ($role === 'admin' || $role === 'administrator') {
        header("Location: {$base_url}/admin/index.php");
        exit;
    } elseif ($role === 'editor') {
        header("Location: {$base_url}/editor/index.php");
        exit;
    } elseif ($role === 'usuario' || $role === 'user') {
        // Usuario normal → front principal del sitio
        header("Location: {$base_url}/index.php");
        exit;
    } else {
        // Fallback seguro al front del sitio
        header("Location: {$base_url}/index.php");
        exit;
    }
}

?>