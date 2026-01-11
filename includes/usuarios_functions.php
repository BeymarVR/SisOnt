<?php
/**
 * Funciones para la gestión de usuarios
 */

/**
 * Obtiene todos los usuarios con información de roles
 */
function obtenerTodosUsuarios() {
    $conexion = obtenerConexion();
    
    $sql = "SELECT u.*, r.nombre as rol_nombre 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            ORDER BY u.fecha_registro DESC";
    
    $result = $conexion->query($sql);
    $usuarios = [];
    
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
    
    return $usuarios;
}

/**
 * Elimina un usuario por ID
 */
function eliminarUsuario($id) {
    $conexion = obtenerConexion();
    
    // Verificar que el usuario existe
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    if (!$stmt->get_result()->fetch_assoc()) {
        return false;
    }
    
    // Eliminar usuario
    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

/**
 * Obtiene un usuario por ID
 */
function obtenerUsuarioPorId($id) {
    $conexion = obtenerConexion();
    
    $sql = "SELECT u.*, r.nombre as rol_nombre 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            WHERE u.id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Verifica si un email ya existe
 */
function emailExiste($email, $excludeId = null) {
    $conexion = obtenerConexion();
    
    if ($excludeId) {
        $sql = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("si", $email, $excludeId);
    } else {
        $sql = "SELECT id FROM usuarios WHERE email = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $email);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}
?>