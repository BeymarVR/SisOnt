<?php
require_once __DIR__ . '/../includes/google-auth-functions.php';

// Redirigir a Google para autenticación
header('Location: ' . getGoogleAuthUrl());
exit;
?>