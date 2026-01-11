<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

verificarRol('admin');

$noticia_id = intval($_POST['id'] ?? 0);

if ($noticia_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$conexion = obtenerConexion();

// Obtener todos los archivos de la noticia
$stmt = $conexion->prepare("
    SELECT tipo, archivo FROM bloques_contenido 
    WHERE noticia_id = ? AND archivo IS NOT NULL
");
$stmt->bind_param("i", $noticia_id);
$stmt->execute();
$archivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Eliminar archivos del servidor
foreach ($archivos as $archivo) {
    $filepath = __DIR__ . '/../assets/uploads/' . $archivo['tipo'] . '/' . basename($archivo['archivo']);
    if (file_exists($filepath)) {
        @unlink($filepath);
    }
}

// Eliminar bloques de contenido
$stmt = $conexion->prepare("DELETE FROM bloques_contenido WHERE noticia_id = ?");
$stmt->bind_param("i", $noticia_id);
$stmt->execute();

// Eliminar noticia
$stmt = $conexion->prepare("DELETE FROM noticias WHERE id = ?");
$stmt->bind_param("i", $noticia_id);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Noticia eliminada correctamente']);
?>