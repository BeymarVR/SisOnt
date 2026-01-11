<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos del cuerpo de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['popup_id']) || !isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

// Registrar que el usuario ha visto el pop-up
$conexion = obtenerConexion();
$stmt = $conexion->prepare("
    INSERT INTO pop_ups_visto (pop_up_id, usuario_id, fecha_visto) 
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE fecha_visto = NOW()
");
$stmt->bind_param("ii", $data['popup_id'], $data['user_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
?>