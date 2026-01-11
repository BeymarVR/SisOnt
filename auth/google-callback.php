<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/google-auth-functions.php';
require_once __DIR__ . '/../includes/auth.php';

// ⭐ NO llamar a session_start() aquí si ya está iniciada en auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['code'])) {
    try {
        // 1. Obtener token de acceso
        $accessToken = getGoogleAccessToken($_GET['code']);
        if (!$accessToken) {
            throw new Exception('No se pudo obtener el token de acceso');
        }

        // 2. Obtener información del usuario
        $userInfo = getGoogleUserInfo($accessToken);
        if (!$userInfo || !isset($userInfo['email'])) {
            throw new Exception('No se pudo obtener la información del usuario');
        }

        // 3. Manejar usuario en nuestra base de datos
        $userId = handleGoogleUser($userInfo);
        if (!$userId) {
            throw new Exception('Error al procesar el usuario');
        }

        // 4. ⭐ CARGAR SESIÓN DESDE BD (incluye rol actualizado)
        if (!setUserSessionFromDb((int)$userId)) {
            throw new Exception('Error al cargar datos de sesión');
        }

        // 5. Añadir proveedor a sesión
        $_SESSION['provider'] = 'google';

        // 6. ⭐ REDIRIGIR SEGÚN ROL
        redirectAfterLogin();
        
    } catch (Exception $e) {
        error_log("Error en Google callback: " . $e->getMessage());
        header('Location: ../auth/login.php?error=google_auth');
        exit;
    }
} else if (isset($_GET['error'])) {
    header('Location: ../auth/login.php?error=google_access_denied');
    exit;
} else {
    header('Location: ../auth/login.php');
    exit;
}
?>