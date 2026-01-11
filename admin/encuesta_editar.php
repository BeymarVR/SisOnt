<?php
// admin/encuesta_editar.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/encuestas_functions.php';
verificarRol('admin');

$editing = false;
$encuesta = null;
if (isset($_GET['id'])) {
    $editing = true;
    $encuesta = obtenerEncuestaPorId(intval($_GET['id']));
    if (!$encuesta) { header("Location: encuestas.php"); exit; }
    if (encuestaTieneRespuestas($encuesta['id'])) {
        // no permitir editar si tiene respuestas
        $mensaje = "Esta encuesta ya tiene respuestas. No puede editarse.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    // recoger preguntas desde POST: estructura esperada questions[][texto], questions[][tipo], options[][i][] etc.
    $questions = $_POST['questions'] ?? [];
    $preguntasArray = [];
    foreach ($questions as $q) {
        $p = [
            'texto' => trim($q['texto']),
            'tipo' => $q['tipo'],
            'opciones' => []
        ];
        if (($q['tipo'] === 'opcion_unica' || $q['tipo'] === 'opcion_multiple') && isset($q['opciones'])) {
            foreach ($q['opciones'] as $op) {
                $op = trim($op);
                if ($op !== '') $p['opciones'][] = $op;
            }
        }
        $preguntasArray[] = $p;
    }

    if ($editing) {
        if (!encuestaTieneRespuestas($encuesta['id'])) {
            $ok = actualizarEncuesta($encuesta['id'], $titulo, $descripcion, $preguntasArray);
            header("Location: encuestas.php"); exit;
        } else {
            $error = "No se puede editar: la encuesta ya tiene respuestas.";
        }
    } else {
        $creado_por = $_SESSION['user_id'];
        $id = crearEncuesta($titulo, $descripcion, $creado_por, $preguntasArray);
        if ($id) header("Location: encuestas.php"); else $error = "Error al crear encuesta";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $editing ? 'Editar' : 'Crear' ?> Encuesta - Panel ONT</title>
    <link rel="icon" type="image/png" href="../assets/images/logo_sup.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
   
<?php include __DIR__ . '/partials/sidebar.php'; ?>
    <main class="main-content" id="mainContent">
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title"><?= $editing ? 'Editar' : 'Crear' ?> Encuesta</h1>
            </div>
            <div class="header-right">
                <a href="encuestas.php" class="btn-ont secondary">
                    <i class="bi bi-arrow-left"></i>
                    Volver
                </a>
            </div>
        </header>

        <div class="content-area">
            <?php if (!empty($mensaje)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <?= $mensaje ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST" id="encuestaForm">
                <!-- Basic Information -->
                <div class="admin-card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-info-circle"></i>
                            Información Básica
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label required">Título de la Encuesta</label>
                                <input name="titulo" 
                                       class="form-control" 
                                       required 
                                       placeholder="Ingrese el título de la encuesta"
                                       value="<?= $editing ? htmlspecialchars($encuesta['titulo']) : '' ?>">
                                <div class="form-text">Un título claro y descriptivo para la encuesta</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" 
                                          class="form-control" 
                                          rows="4"
                                          placeholder="Descripción opcional de la encuesta"><?= $editing ? htmlspecialchars($encuesta['descripcion']) : '' ?></textarea>
                                <div class="form-text">Proporcione contexto adicional sobre el propósito de la encuesta</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="admin-card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title">
                                <i class="bi bi-question-circle"></i>
                                Preguntas de la Encuesta
                            </h3>
                            <button type="button" class="btn-ont success" id="addQuestionBtn">
                                <i class="bi bi-plus-lg"></i>
                                Agregar Pregunta
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="questionsContainer">
                            <!-- Las preguntas se cargarán aquí via JavaScript -->
                        </div>
                        <div class="empty-questions" id="emptyQuestions" style="display: none;">
                            <div class="text-center py-5">
                                <i class="bi bi-question-circle display-1 text-muted"></i>
                                <h4 class="mt-3">No hay preguntas agregadas</h4>
                                <p class="text-muted">Haga clic en "Agregar Pregunta" para comenzar</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="admin-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="encuestas.php" class="btn-ont secondary">
                                <i class="bi bi-arrow-left"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn-ont primary">
                                <i class="bi bi-check-lg"></i>
                                <?= $editing ? 'Actualizar' : 'Crear' ?> Encuesta
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        // Datos de la encuesta desde PHP (si estamos editando)
        const encuestaData = <?= $editing && $encuesta ? json_encode($encuesta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : 'null' ?>;
        let questionCounter = 0;
        
        // Debug: Ver qué datos estamos recibiendo
        console.log('Datos de encuesta:', encuestaData);

        // Función para crear HTML de una opción
        function createOptionHTML(valor = '', index = 0) {
            return `
                <div class="input-group mb-2 option-item">
                    <input type="text" 
                           class="form-control option-input" 
                           placeholder="Texto de la opción"
                           value="${htmlEscape(valor)}">
                    <button type="button" class="btn btn-outline-danger remove-option">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
        }

        // Función para crear HTML de una pregunta
        function createQuestionHTML(pregunta = null, index = 0) {
            const texto = pregunta ? pregunta.texto : '';
            const tipo = pregunta ? pregunta.tipo : 'texto';
            
            // Procesar opciones correctamente
            let opciones = [];
            if (pregunta && pregunta.opciones) {
                // Si opciones es un string JSON, parsearlo
                if (typeof pregunta.opciones === 'string') {
                    try {
                        opciones = JSON.parse(pregunta.opciones);
                    } catch(e) {
                        console.error('Error parsing opciones:', e);
                        opciones = [];
                    }
                } 
                // Si es un array, usarlo directamente
                else if (Array.isArray(pregunta.opciones)) {
                    opciones = pregunta.opciones;
                }
                // Si es un object, convertir sus valores a array
                else if (typeof pregunta.opciones === 'object' && pregunta.opciones !== null) {
                    opciones = Object.values(pregunta.opciones);
                }
            }
            
            console.log('Opciones procesadas para pregunta', index, ':', opciones);
            
            const opcionesHTML = opciones.length > 0 
                ? opciones.map(op => {
                    // Asegurar que op es un string
                    const opcionTexto = typeof op === 'string' ? op : (op.texto || op.value || String(op));
                    return createOptionHTML(opcionTexto);
                }).join('') 
                : '';
            
            const showOptions = tipo === 'opcion_unica' || tipo === 'opcion_multiple';
            
            return `
                <div class="question-block admin-card mb-3" data-question-index="${index}">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-question-circle"></i>
                                Pregunta ${index + 1}
                            </h5>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-question">
                                <i class="bi bi-trash"></i>
                                Eliminar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label required">Texto de la Pregunta</label>
                                <input name="questions[${index}][texto]" 
                                       class="form-control q-text" 
                                       placeholder="Escriba su pregunta aquí" 
                                       value="${htmlEscape(texto)}" 
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Tipo de Respuesta</label>
                                <select name="questions[${index}][tipo]" class="form-select q-tipo">
                                    <option value="texto" ${tipo === 'texto' ? 'selected' : ''}>Texto (respuesta abierta)</option>
                                    <option value="opcion_unica" ${tipo === 'opcion_unica' ? 'selected' : ''}>Opción única (radio)</option>
                                    <option value="opcion_multiple" ${tipo === 'opcion_multiple' ? 'selected' : ''}>Opción múltiple (checkbox)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="options-container" style="display: ${showOptions ? 'block' : 'none'};">
                                    <label class="form-label">Opciones de Respuesta</label>
                                    <div class="options-list mb-2">
                                        ${opcionesHTML}
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary add-option">
                                        <i class="bi bi-plus"></i>
                                        Agregar Opción
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Función para escapar HTML
        function htmlEscape(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        // Función para agregar una nueva pregunta
        function addQuestion(pregunta = null) {
            const container = document.getElementById('questionsContainer');
            const index = questionCounter++;
            
            const questionHTML = createQuestionHTML(pregunta, index);
            container.insertAdjacentHTML('beforeend', questionHTML);
            
            // Configurar eventos para la nueva pregunta
            const newQuestion = container.lastElementChild;
            setupQuestionEvents(newQuestion);
            
            updateEmptyState();
            updateQuestionNumbers();
        }

        // Función para configurar eventos de una pregunta
        function setupQuestionEvents(questionElement) {
            // Evento para cambio de tipo
            const tipoSelect = questionElement.querySelector('.q-tipo');
            const optionsContainer = questionElement.querySelector('.options-container');
            
            tipoSelect.addEventListener('change', function() {
                const showOptions = this.value === 'opcion_unica' || this.value === 'opcion_multiple';
                optionsContainer.style.display = showOptions ? 'block' : 'none';
            });

            // Evento para agregar opción
            const addOptionBtn = questionElement.querySelector('.add-option');
            addOptionBtn.addEventListener('click', function() {
                const optionsList = questionElement.querySelector('.options-list');
                optionsList.insertAdjacentHTML('beforeend', createOptionHTML());
            });

            // Evento para eliminar pregunta
            const removeQuestionBtn = questionElement.querySelector('.remove-question');
            removeQuestionBtn.addEventListener('click', function() {
                questionElement.remove();
                updateEmptyState();
                updateQuestionNumbers();
            });
        }

        // Función para actualizar números de preguntas
        function updateQuestionNumbers() {
            const questions = document.querySelectorAll('.question-block');
            questions.forEach((question, index) => {
                const title = question.querySelector('.card-title');
                title.innerHTML = `<i class="bi bi-question-circle"></i> Pregunta ${index + 1}`;
                
                // Actualizar atributos name
                question.setAttribute('data-question-index', index);
                question.querySelector('.q-text').setAttribute('name', `questions[${index}][texto]`);
                question.querySelector('.q-tipo').setAttribute('name', `questions[${index}][tipo]`);
            });
        }

        // Función para actualizar estado vacío
        function updateEmptyState() {
            const container = document.getElementById('questionsContainer');
            const emptyDiv = document.getElementById('emptyQuestions');
            
            if (container.children.length === 0) {
                emptyDiv.style.display = 'block';
            } else {
                emptyDiv.style.display = 'none';
            }
        }

        // Delegación de eventos para opciones (usando event delegation)
        document.getElementById('questionsContainer').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-option') || e.target.closest('.remove-option')) {
                e.target.closest('.option-item').remove();
            }
        });

        // Event listener para agregar pregunta
        document.getElementById('addQuestionBtn').addEventListener('click', function() {
            addQuestion();
        });

        // Event listener para el submit del formulario
        document.getElementById('encuestaForm').addEventListener('submit', function(e) {
            // Actualizar los hidden inputs para opciones antes del envío
            const questions = document.querySelectorAll('.question-block');
            
            questions.forEach((question, qIndex) => {
                // Remover inputs hidden previos para opciones
                const existingHiddens = question.querySelectorAll('input[name^="questions[' + qIndex + '][opciones]"]');
                existingHiddens.forEach(input => input.remove());
                
                // Crear nuevos inputs hidden para cada opción
                const optionInputs = question.querySelectorAll('.option-input');
                optionInputs.forEach((optionInput, oIndex) => {
                    if (optionInput.value.trim()) {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = `questions[${qIndex}][opciones][]`;
                        hiddenInput.value = optionInput.value.trim();
                        question.appendChild(hiddenInput);
                    }
                });
            });
            
            // Actualizar números finales
            updateQuestionNumbers();
        });

        // Inicialización cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // Si estamos editando una encuesta, cargar los datos existentes
            if (encuestaData && encuestaData.preguntas) {
                encuestaData.preguntas.forEach(pregunta => {
                    addQuestion(pregunta);
                });
            } else {
                // Si es nueva encuesta, agregar una pregunta vacía por defecto
                addQuestion();
            }
            
            updateEmptyState();
        });
    </script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($editing && isset($opciones_respuesta) && is_array($opciones_respuesta)): ?>
        var opcionesGuardadas = <?php echo json_encode($opciones_respuesta); ?>;
        opcionesGuardadas.forEach(function(opcion) {
            agregarOpcionRespuesta(opcion.texto);
        });
    <?php endif; ?>
});

function agregarOpcionRespuesta(valor = '') {
    var contenedor = document.getElementById('opciones-container');
    var div = document.createElement('div');
    div.className = 'opcion-respuesta';
    div.innerHTML = `
        <input type="text" name="opciones[]" value="${valor}" placeholder="Opción de respuesta">
        <button type="button" class="btn-eliminar-opcion">Eliminar</button>
    `;
    contenedor.appendChild(div);
}
</script>
</body>
</html>