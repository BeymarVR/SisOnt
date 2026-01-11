<?php
// Trunca un texto a una longitud máxima y añade '...' si es necesario
if (!function_exists('truncateText')) {
    function truncateText($text, $maxLength = 50) {
        if (mb_strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength - 3) . '...';
        }
        return $text;
    }
}
require_once __DIR__ . '/database.php';

function obtenerSlidesCarrusel($soloActivos = false) {
    $conexion = obtenerConexion();

    if ($soloActivos) {
        $sql = "SELECT * FROM carrusel WHERE activo = 1 ORDER BY id ASC";
    } else {
        $sql = "SELECT * FROM carrusel ORDER BY id ASC";
    }

    $stmt = $conexion->prepare($sql);
    if ($stmt === false) {
        // registrar error y devolver array vacío en lugar de lanzar excepción
        error_log('Error prepare SQL carrusel: ' . $conexion->error);
        return [];
    }

    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        error_log('Error get_result carrusel: ' . $conexion->error);
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function obtenerSlidePorId($id) {
    $mysqli = obtenerConexion();
    $stmt = $mysqli->prepare("SELECT * FROM carrusel WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc() : null;
}

function agregarSlide($data) {
    $mysqli = obtenerConexion();
    $stmt = $mysqli->prepare("INSERT INTO carrusel 
    (titulo, subtitulo, imagen, texto_boton_1, url_boton_1, texto_boton_2, url_boton_2, activo, usuario_id) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
    "sssssssii",
    $data['titulo'],
    $data['subtitulo'],
    $data['imagen'],
    $data['texto_boton_1'],
    $data['url_boton_1'],
    $data['texto_boton_2'],
    $data['url_boton_2'],
    $data['activo'],
    $data['usuario_id']
);

    $stmt->execute();
}

function actualizarSlide($data) {
    $conexion = obtenerConexion();
    
    $query = "UPDATE carrusel SET 
              titulo = ?, 
              subtitulo = ?, 
              imagen = ?, 
              texto_boton_1 = ?, 
              url_boton_1 = ?, 
              texto_boton_2 = ?, 
              url_boton_2 = ?, 
              activo = ?, 
              usuario_id = ? 
              WHERE id = ?";
    
    $stmt = $conexion->prepare($query);
    
    // Verificar que todos los campos necesarios estén presentes
    $required_fields = ['titulo', 'subtitulo', 'imagen', 'texto_boton_1', 'url_boton_1', 
                       'texto_boton_2', 'url_boton_2', 'activo', 'usuario_id', 'id'];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    // Vincular parámetros
    $stmt->bind_param(
        "sssssssiii", 
        $data['titulo'],
        $data['subtitulo'],
        $data['imagen'],
        $data['texto_boton_1'],
        $data['url_boton_1'],
        $data['texto_boton_2'],
        $data['url_boton_2'],
        $data['activo'],
        $data['usuario_id'],
        $data['id']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar slide: " . $stmt->error);
    }
    
    return $stmt->affected_rows > 0;
}

function subirImagenCarrusel($file) {
    $uploadDir = __DIR__ . '/../assets/uploads/carrusel/';
    
    // Crear directorio si no existe
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    // Validar tipo de imagen
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }
    
    return false;
}

function eliminarSlide($id) {
    $mysqli = obtenerConexion();
    // Obtener slide para eliminar imagen
    $slide = obtenerSlidePorId($id);
    if ($slide && $slide['imagen']) {
        $imagePath = __DIR__ . '/../assets/uploads/carrusel/' . $slide['imagen'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    $stmt = $mysqli->prepare("DELETE FROM carrusel WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

