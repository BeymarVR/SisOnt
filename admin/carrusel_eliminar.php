<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/carrusel_functions.php';

// Verificar sesión y rol usando tu función consistente
verificarRol('admin');

if (isset($_GET['id'])) {
    eliminarSlide($_GET['id']);
}

header("Location: carrusel.php");
exit();