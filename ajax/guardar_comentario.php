<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/comentarios_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para comentar']);
    exit;
}

// Validar datos
if (empty($_POST['comentario']) || empty($_POST['tipo']) || empty($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$comentario = trim($_POST['comentario']);
$tipo = $_POST['tipo'];
$id = intval($_POST['id']);

// Validar longitud
if (strlen($comentario) < 5) {
    echo json_encode(['success' => false, 'message' => 'El comentario debe tener al menos 5 caracteres']);
    exit;
}

if (strlen($comentario) > 1000) {
    echo json_encode(['success' => false, 'message' => 'El comentario no puede exceder los 1000 caracteres']);
    exit;
}

// Preparar datos
$datos = [
    'contenido' => $comentario,
    'usuario_id' => $_SESSION['user_id'],
    'noticia_id' => ($tipo === 'noticia') ? $id : null,
    'normativa_id' => ($tipo === 'normativa') ? $id : null,
    'encuesta_id' => ($tipo === 'encuesta') ? $id : null
];

try {
    if (guardarComentario($datos)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Comentario enviado para moderación. Será publicado una vez aprobado.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al guardar el comentario']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>