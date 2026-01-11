<?php
require_once './includes/auth.php';
require_once './includes/database.php';
require_once './includes/comentarios_functions.php';

// Verificar si se está viendo una publicación específica
$tipoPublicacion = $_GET['tipo'] ?? '';
$idPublicacion = $_GET['id'] ?? 0;
$comentarios = [];

if ($tipoPublicacion && $idPublicacion) {
    $comentarios = obtenerComentariosPublicacion($tipoPublicacion, $idPublicacion);
}

// Procesar nuevo comentario
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    if (empty(trim($_POST['comentario']))) {
        $error = 'El comentario no puede estar vacío';
    } else {
        $datos = [
            'contenido' => trim($_POST['comentario']),
            'usuario_id' => $_SESSION['user_id'],
            'noticia_id' => ($tipoPublicacion === 'noticia') ? $idPublicacion : null,
            'normativa_id' => ($tipoPublicacion === 'normativa') ? $idPublicacion : null,
            'encuesta_id' => ($tipoPublicacion === 'encuesta') ? $idPublicacion : null
        ];
        
        if (guardarComentario($datos)) {
            $mensaje = 'Comentario enviado para moderación. Será publicado una vez aprobado.';
            // Recargar para evitar reenvío del formulario
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $error = 'Error al enviar el comentario. Inténtalo de nuevo.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comentarios - ONT Bolivia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    <style>
        .comentario-card {
            border-left: 4px solid #352f62;
            transition: transform 0.2s;
        }
        .comentario-card:hover {
            transform: translateX(5px);
        }
        .usuario-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .container {
            margin-top: 100px;
        }
        
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include './partials/navbar.php'; ?>

    <main class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">
                        <i class="bi bi-chat-dots me-2"></i>
                        Sistema de Comentarios
                    </h1>
                    <a href="index.php" class="btn btn-ont-primary">
                        <i class="bi bi-arrow-left me-1"></i>Volver al inicio
                    </a>
                </div>

                <?php if ($tipoPublicacion && $idPublicacion): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Comentarios para: <strong>
                    <?php 
                    switch ($tipoPublicacion) {
                        case 'noticia': echo 'Noticia'; break;
                        case 'normativa': echo 'Normativa'; break;
                        case 'encuesta': echo 'Encuesta'; break;
                    }
                    ?>
                    </strong>
                </div>
                <?php endif; ?>

                <!-- Formulario de comentario -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Deja tu comentario</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($mensaje): ?>
                        <div class="alert alert-success"><?= $mensaje ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <textarea class="form-control" name="comentario" rows="4" 
                                          placeholder="Escribe tu comentario aquí..." 
                                          required></textarea>
                            </div>
                            <button type="submit" class="btn btn-ont-primary">
                                <i class="bi bi-send me-1"></i>Enviar comentario
                            </button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Debes <a href="auth/login.php">iniciar sesión</a> para dejar comentarios.
                </div>
                <?php endif; ?>

                <!-- Lista de comentarios -->
                <h4 class="mb-3">Comentarios (<?= count($comentarios) ?>)</h4>
                
                <?php if (empty($comentarios)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-chat me-2"></i>
                    No hay comentarios aún. ¡Sé el primero en comentar!
                </div>
                <?php else: ?>
                    <?php foreach ($comentarios as $comentario): ?>
                    <div class="card comentario-card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="usuario-avatar me-3">
                                    <?= strtoupper(substr($comentario['usuario_nombre'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($comentario['usuario_nombre']) ?></h6>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])) ?>
                                    </small>
                                </div>
                            </div>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($comentario['contenido'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include './partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>