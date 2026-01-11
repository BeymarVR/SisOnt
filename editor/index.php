<?php
require_once '../includes/auth.php';
verificarRol('editor');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editor Dashboard</title>
</head>
<body>
    <h1>Bienvenido Editor</h1>
    <p>Sesión iniciada como: <?= $_SESSION['user_email'] ?></p>
    <a href="../auth/logout.php">Cerrar sesión</a>
</body>
</html>