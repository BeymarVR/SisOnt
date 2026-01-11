<?php
require_once __DIR__ . '/../includes/auth.php';

verificarRol('admin');

$filepath = $_POST['filepath'] ?? '';

if (!$filepath) {
    echo json_encode(['success' => false, 'message' => 'Ruta no especificada']);
    exit;
}

// Validar que la ruta esté dentro de uploads
$realpath = realpath(__DIR__ . '/../' . $filepath);
$uploadsPath = realpath(__DIR__ . '/../assets/uploads');

if (!$realpath || strpos($realpath, $uploadsPath) !== 0) {
    echo json_encode(['success' => false, 'message' => 'Ruta inválida']);
    exit;
}

if (file_exists($realpath)) {
    if (unlink($realpath)) {
        echo json_encode(['success' => true, 'message' => 'Archivo eliminado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el archivo']);
    }
} else {
    echo json_encode(['success' => true, 'message' => 'Archivo no encontrado (ya eliminado)']);
}
?>