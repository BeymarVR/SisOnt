<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/encuestas_functions.php';
verificarRol('admin');

$encuesta_id = $_GET['encuesta_id'] ?? 0;
$usuario_id = $_GET['usuario_id'] ?? 0;

// Obtener datos de la encuesta
$encuesta = obtenerEncuestaPorId($encuesta_id);
if (!$encuesta) {
    header("Location: encuestas.php");
    exit;
}

// Obtener datos del usuario (versión corregida)
$conexion = obtenerConexion();
$stmt = $conexion->prepare("SELECT id, nombre, email FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    header("Location: encuesta_resultados.php?id=$encuesta_id");
    exit;
}

// Obtener respuestas del usuario
$respuestas = obtenerRespuestasUsuario($encuesta_id, $usuario_id);

// Obtener todas las preguntas con sus opciones
$preguntas = obtenerPreguntasEncuesta($encuesta_id);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuestas de Usuario - Panel ONT</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .response-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .response-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
        }
        .response-body {
            padding: 1.5rem;
        }
        .text-response {
            font-style: italic;
            color: #495057;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
   <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content" id="mainContent">
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title">Respuestas de Usuario</h1>
            </div>
            <div class="header-right">
                <a href="encuesta_resultados.php?id=<?= $encuesta_id ?>" class="btn-ont secondary">
                    <i class="bi bi-arrow-left"></i> Volver a resultados
                </a>
            </div>
        </header>

        <div class="admin-content">
            <div class="container" style="max-width: 1000px;">
                <!-- Información del usuario -->
                <div class="response-card mb-4">
                    <div class="user-header">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar-lg">
                                <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                            </div>
                            <div>
                                <h2 class="mb-1"><?= htmlspecialchars($usuario['nombre']) ?></h2>
                                <p class="mb-0"><?= htmlspecialchars($usuario['email']) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="response-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Encuesta:</strong> <?= htmlspecialchars($encuesta['titulo']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Fecha de respuesta:</strong> 
                                    <?= date('d/m/Y H:i', strtotime($respuestas[0]['fecha_respuesta'] ?? 'now')) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Respuestas del usuario -->
<h3 class="mb-4">Respuestas:</h3>

<?php if (empty($respuestas)): ?>
    <div class="alert alert-info">
        Este usuario no ha respondido ninguna pregunta de esta encuesta
    </div>
<?php else: ?>
    <?php foreach($preguntas as $pregunta): 
        $respuestaUsuario = null;
        foreach($respuestas as $respuesta) {
            if ($respuesta['pregunta_id'] == $pregunta['id']) {
                $respuestaUsuario = $respuesta;
                break;
            }
        }
    ?>
    <div class="response-card">
        <div class="response-header">
            <h4 class="mb-0">
                <i class="bi bi-question-circle me-2"></i>
                <?= htmlspecialchars($pregunta['texto']) ?>
                <small class="text-muted">(<?= ucfirst($pregunta['tipo']) ?>)</small>
            </h4>
        </div>
        <div class="response-body">
            <?php if (!$respuestaUsuario): ?>
                <div class="alert alert-secondary">
                    <i class="bi bi-dash-circle"></i> El usuario no respondió esta pregunta
                </div>
            <?php elseif ($pregunta['tipo'] === 'texto'): ?>
                <div class="text-response">
                    <?= htmlspecialchars($respuestaUsuario['respuestas'][0]['valor']) ?>
                </div>
            <?php else: ?>
                <?php foreach($respuestaUsuario['respuestas'] as $respuesta): ?>
                <div class="alert alert-success d-flex align-items-center mb-2">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars($respuesta['opcion_texto']) ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if ($respuestaUsuario): ?>
            <div class="text-end text-muted small mt-2">
                <i class="bi bi-clock"></i> <?= date('d/m/Y H:i', strtotime($respuestaUsuario['fecha_respuesta'])) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>