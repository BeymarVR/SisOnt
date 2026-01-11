<?php
/**
 * Funciones para la gestión de normativas
 */

// ... tus funciones existentes ...

/**
 * Elimina una normativa por ID
 */
function eliminarNormativa($id) {
    $conexion = obtenerConexion();
    
    // Primero obtener información de la normativa para eliminar el archivo
    $normativa = obtenerNormativaPorId($id);
    
    if ($normativa && $normativa['archivo']) {
        $rutaArchivo = "../assets/uploads/normativas/" . $normativa['archivo'];
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
    }
    
    // Eliminar normativa
    $stmt = $conexion->prepare("DELETE FROM normativas WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    return $stmt->execute();
}

/**
 * Obtiene una normativa por ID
 */
function obtenerNormativaPorId($id) {
    $conexion = obtenerConexion();
    
    $stmt = $conexion->prepare("SELECT * FROM normativas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Obtiene todas las normativas
 */
function obtenerTodasNormativas() {
    $conexion = obtenerConexion();
    
    $sql = "SELECT * FROM normativas ORDER BY fecha_publicacion DESC";
    $result = $conexion->query($sql);
    $normativas = [];
    
    while ($row = $result->fetch_assoc()) {
        $normativas[] = $row;
    }
    
    return $normativas;
}
?>