<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/encuestas_functions.php';

verificarRol('usuario');

$encuesta_id = $_GET['id'] ?? 0;
$conexion = obtenerConexion();

// Obtener encuesta
$stmt = $conexion->prepare("SELECT * FROM encuestas WHERE id = ? AND estado = 'activa'");
$stmt->bind_param("i", $encuesta_id);
$stmt->execute();
$encuesta = $stmt->get_result()->fetch_assoc();

if (!$encuesta) {
    header("Location: encuestas.php");
    exit();
}

// Verificar si ya respondió
$stmt = $conexion->prepare("SELECT id FROM respuestas WHERE encuesta_id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $encuesta_id, $_SESSION['user_id']);
$stmt->execute();
$ya_respondio = $stmt->get_result()->fetch_assoc();

// Obtener preguntas con sus opciones
$stmt = $conexion->prepare("SELECT * FROM preguntas WHERE encuesta_id = ? ORDER BY orden");
$stmt->bind_param("i", $encuesta_id);
$stmt->execute();
$preguntas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($preguntas as &$pregunta) {
    if (in_array($pregunta['tipo'], ['opcion_unica', 'opcion_multiple'])) {
        $stmt_opciones = $conexion->prepare("SELECT id, texto FROM opciones WHERE pregunta_id = ? ORDER BY orden");
        $stmt_opciones->bind_param("i", $pregunta['id']);
        $stmt_opciones->execute();
        $pregunta['opciones'] = $stmt_opciones->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $pregunta['opciones'] = []; // Para preguntas de texto
    }
}
unset($pregunta);

// Procesar envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$ya_respondio) {
    $respuestas = $_POST['respuestas'] ?? [];
    
    foreach ($respuestas as $pregunta_id => $respuesta) {
        // Buscar la pregunta correspondiente
        $pregunta_actual = null;
        foreach ($preguntas as $p) {
            if ($p['id'] == $pregunta_id) {
                $pregunta_actual = $p;
                break;
            }
        }
        
        if (!$pregunta_actual) continue;
        
        // Procesar según el tipo de pregunta con validaciones reforzadas
        switch ($pregunta_actual['tipo']) {
            case 'texto':
                // Solo guardar si es una cadena no vacía
                if (is_string($respuesta) && !empty(trim($respuesta))) {
                    $stmt = $conexion->prepare("INSERT INTO respuestas (usuario_id, encuesta_id, pregunta_id, respuesta_texto) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiis", $_SESSION['user_id'], $encuesta_id, $pregunta_id, trim($respuesta));
                    if (!$stmt->execute()) {
                        error_log("Error al guardar respuesta de texto: " . $stmt->error);
                    }
                }
                break;
                
            case 'opcion_unica':
                // Asegurarse que es un ID válido y que la opción pertenece a la pregunta
                if (is_numeric($respuesta)) {
                    $opcion_valida = false;
                    foreach ($pregunta_actual['opciones'] as $opcion) {
                        if ($opcion['id'] == $respuesta) {
                            $opcion_valida = true;
                            break;
                        }
                    }
                    
                    if ($opcion_valida) {
                        $stmt = $conexion->prepare("INSERT INTO respuestas_opciones (usuario_id, pregunta_id, opcion_id) VALUES (?, ?, ?)");
                        $stmt->bind_param("iii", $_SESSION['user_id'], $pregunta_id, $respuesta);
                        if (!$stmt->execute()) {
                            error_log("Error al guardar opción única: " . $stmt->error);
                        }
                    } else {
                        error_log("Opción inválida $respuesta para pregunta $pregunta_id");
                    }
                } else {
                    error_log("Respuesta no numérica para opción única en pregunta $pregunta_id");
                }
                break;
                
            case 'opcion_multiple':
                if (is_array($respuesta)) {
                    foreach ($respuesta as $opcion_id) {
                        if (is_numeric($opcion_id)) {
                            $opcion_valida = false;
                            foreach ($pregunta_actual['opciones'] as $opcion) {
                                if ($opcion['id'] == $opcion_id) {
                                    $opcion_valida = true;
                                    break;
                                }
                            }
                            
                            if ($opcion_valida) {
                                $stmt = $conexion->prepare("INSERT INTO respuestas_opciones (usuario_id, pregunta_id, opcion_id) VALUES (?, ?, ?)");
                                $stmt->bind_param("iii", $_SESSION['user_id'], $pregunta_id, $opcion_id);
                                if (!$stmt->execute()) {
                                    error_log("Error al guardar opción múltiple: " . $stmt->error);
                                }
                            }
                        }
                    }
                }
                break;
                
            default:
                error_log("Tipo de pregunta desconocido: " . $pregunta_actual['tipo']);
                break;
        }
    }
    
    header("Location: encuesta_completada.php?id=" . $encuesta_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($encuesta['titulo']) ?> - ONT Bolivia</title>
    <meta name="description" content="Responde la encuesta del Observatorio Nacional del Trabajo de Bolivia">
    <link rel="icon" type="image/png" href="assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    
      
        <style>
        body {
            background: #f8f9fa;
        }
        .navbar-ont {
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
        }
        .btn-ont-primary {
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            color: white;
            border: none;
        }
        .btn-ont-primary:hover {
            background: linear-gradient(135deg, #2a2550 0%, #c44903 100%);
            color: white;
        }
        .card-ont {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
      
        .social-links a {
            font-size: 1.25rem;
        }
        .encuesta-icon {
            width: 24px;
            height: 24px;
        }
        .encuesta-icon-lg {
            width: 32px;
            height: 32px;
        }
        .encuesta-icon-xl {
            width: 48px;
            height: 48px;
        }
        .question-number {
            background: linear-gradient(135deg, #e45504 0%, #ff6b1a 100%);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        .question-card {
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            background: white;
        }
        .question-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .scale-option {
            width: 50px;
            height: 50px;
            border: 3px solid #dee2e6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 5px;
            background: white;
        }
        .scale-option:hover {
            border-color: #352f62;
            background-color: rgba(53, 47, 98, 0.1);
        }
        .scale-option.selected {
            border-color: #352f62;
            background-color: #352f62;
            color: white;
        }
        .progress-custom {
            height: 8px;
            background-color: rgba(255,255,255,0.3);
            border-radius: 4px;
        }
        .progress-bar-custom {
            background: linear-gradient(135deg, #e45504 0%, #ff6b1a 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        .hero-section {
            background: linear-gradient(135deg, #352f62 0%, #e45504 100%);
            padding: 120px 0 60px 0;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50">
<!-- Navigation -->
     <!--navegacion-->
    <?php include './partials/navbar.php'; ?>

    <section class="hero-section">
        <div class="container text-white text-center">
            <div class="d-flex align-items-center justify-content-center mb-4">
                <div class="bg-white bg-opacity-25 rounded-circle p-3 me-3">
                    <i class="bi bi-clipboard-check" style="font-size: 2rem;"></i>
                </div>
                <div class="text-start">
                    <h1 class="display-5 fw-bold mb-1"><?= htmlspecialchars($encuesta['titulo']) ?></h1>
                    <p class="mb-0 opacity-75">
                        <i class="bi bi-clock me-1"></i>Tiempo estimado: 5-10 minutos
                    </p>
                </div>
            </div>
    
            <?php if (!empty($encuesta['descripcion'])): ?>
                <p class="lead mb-4 opacity-90"><?= nl2br(htmlspecialchars($encuesta['descripcion'])) ?></p>
            <?php endif; ?>
            
            <div class="progress-custom mb-2" style="max-width: 400px; margin: 0 auto;">
                <div class="progress-bar-custom" style="width: 0%" id="progress-bar"></div>
            </div>
            <!-- <small class="opacity-75" id="progress-text">
                Pregunta 1 de <?= count($preguntas) ?>
            </small> -->
        </div>
    </section>

    <section class="section-ont py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <?php if ($ya_respondio): ?>
                        <div class="text-center py-5">
                            <div class="card card-ont p-5">
                                <div class="mb-4">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                                </div>
                                <h2 class="mb-3">¡Encuesta Completada!</h2>
                                <p class="mb-4">
                                    Ya has respondido esta encuesta anteriormente. ¡Gracias por tu valiosa participación!
                                </p>
                                <div class="d-flex gap-3 justify-content-center flex-wrap">
                                    <a href="encuestas.php" class="btn btn-ont-primary">
                                        <i class="bi bi-list-ul me-2"></i>Ver Otras Encuestas
                                    </a>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="bi bi-house-door me-2"></i>Ir al Inicio
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card card-ont">
                            <div class="card-body p-4">
                                <form method="POST" id="survey-form">
                                    <?php foreach ($preguntas as $index => $pregunta): ?>
                                        <div class="question-card" data-question="<?= $index + 1 ?>">
                                            <div class="d-flex align-items-start">
                                                <div class="question-number">
                                                    <?= $index + 1 ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h5 class="fw-bold mb-3">
                                                        <?= htmlspecialchars($pregunta['texto']) ?>
                                                        <?php if ($pregunta['requerida'] ?? false): ?>
                                                            <span class="text-danger">*</span>
                                                        <?php endif; ?>
                                                    </h5>
                                                    
                                                    <?php if ($pregunta['tipo'] === 'texto'): ?>
                                                        <input type="text" name="respuestas[<?= $pregunta['id'] ?>]" class="form-control" required>
                                                    
                                                    <?php elseif ($pregunta['tipo'] === 'opcion_unica'): ?>
                                                        <?php foreach ($pregunta['opciones'] as $opcion): ?>
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="radio" 
                                                                       name="respuestas[<?= $pregunta['id'] ?>]" 
                                                                       value="<?= $opcion['id'] ?>" required>
                                                                <label class="form-check-label"><?= htmlspecialchars($opcion['texto']) ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    
                                                    <?php elseif ($pregunta['tipo'] === 'opcion_multiple'): ?>
                                                        <?php foreach ($pregunta['opciones'] as $opcion): ?>
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="checkbox" 
                                                                       name="respuestas[<?= $pregunta['id'] ?>][]" 
                                                                       value="<?= $opcion['id'] ?>">
                                                                <label class="form-check-label"><?= htmlspecialchars($opcion['texto']) ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($pregunta['requerida'] ?? false): ?>
                                                        <small class="text-danger mt-2 d-block">
                                                            <i class="bi bi-exclamation-circle me-1"></i>Este campo es obligatorio
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <div class="border-top pt-4 mt-4">
                                        <div class="bg-light p-4 rounded">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                                <div class="mb-2 mb-md-0">
                                                    <h6 class="mb-1">¿Listo para enviar?</h6>
                                                    <small class="text-muted">Revisa tus respuestas antes de continuar</small>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <a href="encuestas.php" class="btn btn-secondary">
                                                        <i class="bi bi-x-lg me-1"></i>Cancelar
                                                    </a>
                                                    <button type="submit" class="btn btn-ont-primary">
                                                        <i class="bi bi-send me-1"></i>Enviar Respuestas
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

       <!-- Footer -->
    <?php include './partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('survey-form');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const questions = document.querySelectorAll('.question-card');
            const totalQuestions = questions.length;
            
            // Scale rating functionality
            document.querySelectorAll('.scale-option').forEach(option => {
                option.addEventListener('click', function() {
                    const input = this.querySelector('input[type="radio"]');
                    const name = input.name;
                    
                    // Reset all options in this group
                    document.querySelectorAll(`input[name="${name}"]`).forEach(radio => {
                        radio.closest('.scale-option').classList.remove('selected');
                    });
                    
                    // Select this option
                    input.checked = true;
                    this.classList.add('selected');
                    updateProgress();
                });
            });
            
            // Progress tracking
            function updateProgress() {
                let answeredQuestions = 0;
                questions.forEach(question => {
                    const inputs = question.querySelectorAll('input, textarea');
                    const hasAnswer = Array.from(inputs).some(input => {
                        if (input.type === 'radio' || input.type === 'checkbox') {
                            return input.checked;
                        }
                        return input.value.trim() !== '';
                    });
                    
                    if (hasAnswer) answeredQuestions++;
                });
                
                const progress = (answeredQuestions / totalQuestions) * 100;
                progressBar.style.width = progress + '%';
                //progressText.textContent = `${answeredQuestions} de ${totalQuestions} preguntas respondidas`;
            }
            
            // Add event listeners for progress tracking
            document.querySelectorAll('input, textarea').forEach(input => {
                input.addEventListener('change', updateProgress);
                input.addEventListener('input', updateProgress);
            });
            
            updateProgress();
        });
    </script>
</body>
</html>