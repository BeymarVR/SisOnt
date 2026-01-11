<?php
// auth/logout.php
require_once '../includes/config.php';
require_once '../includes/database.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Registrar el cierre de sesión (opcional, para auditoría)
if (isset($_SESSION['user_id'])) {
    $usuarioId = $_SESSION['user_id'];
    
    // Puedes registrar el cierre de sesión en la base de datos si lo deseas
    registrarCierreSesion($usuarioId);
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la sesión completamente, borrar también la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al inicio con mensaje de éxito
header("Location: ../index.php?logout=success");
exit();

/**
 * Registra el cierre de sesión en la base de datos (opcional)
 */
function registrarCierreSesion($usuarioId) {
    $conexion = obtenerConexion();
    
    $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
}
?>