<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/comentarios_functions.php';

verificarRol('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_POST['id']) || !isset($_POST['accion'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$id = intval($_POST['id']);
$accion = $_POST['accion'];
$moderadorId = $_SESSION['user_id'];

try {
    switch ($accion) {
        case 'aprobar':
            $resultado = cambiarEstadoComentario($id, 'aprobado', $moderadorId);
            $mensaje = $resultado ? 'Comentario aprobado correctamente' : 'Error al aprobar comentario';
            break;
            
        case 'rechazar':
            $motivo = $_POST['motivo'] ?? 'Violación de las normas de la comunidad';
            $resultado = cambiarEstadoComentario($id, 'rechazado', $moderadorId, $motivo);
            $mensaje = $resultado ? 'Comentario rechazado correctamente' : 'Error al rechazar comentario';
            break;
            
        case 'eliminar':
            $resultado = eliminarComentario($id);
            $mensaje = $resultado ? 'Comentario eliminado correctamente' : 'Error al eliminar comentario';
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            exit;
    }
    
    echo json_encode(['success' => $resultado, 'message' => $mensaje]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>