<?php
require_once './includes/auth.php';

// Si quieres, puedes validar aquí si el usuario tiene permiso.
// if (!usuarioEsAdmin()) {
//     // Mostrar acceso denegado
// }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - ONT Bolivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .access-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.15);
            padding: 50px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .access-icon {
            font-size: 70px;
            color: #e45504;
        }
        .btn-ont-primary {
            background-color: #352f62;
            color: #fff;
            border: none;
        }
        .btn-ont-primary:hover {
            background-color: #2a2451;
        }
        .btn-ont-secondary {
            background-color: #e45504;
            color: #fff;
            border: none;
        }
        .btn-ont-secondary:hover {
            background-color: #c94703;
        }
    </style>
</head>
<body>

    <div class="access-card">
        <i class="bi bi-shield-lock-fill access-icon mb-4"></i>
        <h2 class="mb-3">Acceso Denegado</h2>
        <p class="mb-4 text-muted">
            No tienes permisos para acceder a esta sección o el contenido aún no está disponible.<br>
            Por favor, regresa al panel de administración o inicia sesión nuevamente.
        </p>

        <div class="d-flex justify-content-center gap-3">
            <a href="auth\login.php" class="btn btn-ont-secondary btn-lg">
                <i class="bi bi-box-arrow-in-right me-2"></i>Ir al Login
            </a>
            <a href="admin/index.php" class="btn btn-ont-primary btn-lg">
                <i class="bi bi-speedometer2 me-2"></i>Ir al Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
