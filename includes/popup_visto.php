<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
$cn = obtenerConexion();

$userId = $_SESSION['user_id'] ?? 0;
$popupId = $_POST['popup_id'] ?? 0;

if ($userId && $popupId) {
    $stmt = $cn->prepare("INSERT IGNORE INTO pop_ups_visto (pop_up_id, usuario_id, fecha_visto) VALUES (?,?,NOW())");
    $stmt->bind_param("ii", $popupId, $userId);
    $stmt->execute();
    echo "ok";
} else {
    echo "error";
}
