<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

verificarRol('admin');

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$conexion = obtenerConexion();
$stmt = $conexion->prepare("SELECT * FROM pop_ups WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result()->fetch_assoc();

if ($resultado) {
    echo json_encode(['success' => true, 'popup' => $resultado]);
} else {
    echo json_encode(['success' => false, 'message' => 'Pop-up no encontrado']);
}
?>