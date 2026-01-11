<?php
require_once 'config.php';

function obtenerConexion() {
    static $conexion = null;
    
    if ($conexion === null) {
        $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conexion->connect_error) {
            die("Error de conexión: " . $conexion->connect_error);
        }
        
        $conexion->set_charset("utf8mb4");
    }
    
    return $conexion;   
}

function obtenerUsuario($email) {
    $conexion = obtenerConexion();
    $stmt = $conexion->prepare("
        SELECT u.*, r.nombre as rol_nombre 
        FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        WHERE u.email = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}


function obtenerResultados($sql, $params = []) {
    $conexion = obtenerConexion();
    $stmt = $conexion->prepare($sql);
    
    if (!empty($params)) {
        $types = '';
        $values = [];
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';  // integer
            } elseif (is_double($param)) {
                $types .= 'd';  // double
            } else {
                $types .= 's';  // string
            }
            $values[] = $param;
        }
        
        $stmt->bind_param($types, ...$values);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    return $rows;
}

function obtenerUnicoResultado($sql, $params = []) {
    $resultados = obtenerResultados($sql, $params);
    return $resultados[0] ?? null;
}

function insertarDatos($tabla, $datos) {
    $conexion = obtenerConexion();
    $campos = implode(', ', array_keys($datos));
    $placeholders = implode(', ', array_fill(0, count($datos), '?'));
    $valores = array_values($datos);
    
    $sql = "INSERT INTO $tabla ($campos) VALUES ($placeholders)";
    $stmt = $conexion->prepare($sql);
    
    $types = str_repeat('s', count($datos));
    $stmt->bind_param($types, ...$valores);
    
    return $stmt->execute();
}

// ================= FUNCIONES DE ESTADÍSTICAS PARA DASHBOARD =================

function obtenerEstadisticasDashboard() {
    $conexion = obtenerConexion();
    
    $stats = [];
    
    // Usuarios
    $query = $conexion->query("SELECT COUNT(*) as total FROM usuarios");
    $stats['total_usuarios'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
    $stats['usuarios_activos'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 0");
    $stats['usuarios_inactivos'] = $query->fetch_assoc()['total'] ?? 0;
    
    // Distribución por rol
    $rolesQuery = $conexion->query("
        SELECT r.nombre, COUNT(u.id) as cantidad 
        FROM usuarios u 
        JOIN roles r ON u.rol_id = r.id 
        GROUP BY r.id
    ");
    $stats['distribucion_roles'] = [];
    if ($rolesQuery) {
        while ($row = $rolesQuery->fetch_assoc()) {
            $stats['distribucion_roles'][] = $row;
        }
    }
    
    // Método de registro
    $query = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE provider = 'local'");
    $stats['usuarios_local'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE provider = 'google'");
    $stats['usuarios_google'] = $query->fetch_assoc()['total'] ?? 0;
    
    // Comentarios
    $query = $conexion->query("SELECT COUNT(*) as total FROM comentarios");
    $stats['total_comentarios'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM comentarios WHERE estado = 'aprobado'");
    $stats['comentarios_aprobados'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM comentarios WHERE estado = 'pendiente'");
    $stats['comentarios_pendientes'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM comentarios WHERE estado = 'rechazado'");
    $stats['comentarios_rechazados'] = $query->fetch_assoc()['total'] ?? 0;
    
    // Contenido
    $query = $conexion->query("SELECT COUNT(*) as total FROM noticias");
    $stats['total_noticias'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM noticias WHERE estado = 'publicado'");
    $stats['noticias_publicadas'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM noticias WHERE estado = 'borrador'");
    $stats['noticias_borrador'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM noticias WHERE destacada = 1");
    $stats['noticias_destacadas'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM normativas");
    $stats['total_normativas'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM normativas WHERE estado = 'activo'");
    $stats['normativas_activas'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM encuestas");
    $stats['total_encuestas'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM encuestas WHERE estado = 'activa'");
    $stats['encuestas_activas'] = $query->fetch_assoc()['total'] ?? 0;
    
    // Pop-ups
    $query = $conexion->query("SELECT COUNT(*) as total FROM pop_ups WHERE activo = 1");
    $stats['popups_activos'] = $query->fetch_assoc()['total'] ?? 0;
    
    // Vistas totales de noticias
    $query = $conexion->query("SELECT SUM(vistas) as total FROM noticias");
    $result = $query->fetch_assoc();
    $stats['total_vistas'] = $result['total'] ?? 0;
    
    // Carrusel
    $query = $conexion->query("SELECT COUNT(*) as total FROM carrusel WHERE activo = 1");
    $stats['carrusel_activo'] = $query->fetch_assoc()['total'] ?? 0;
    
    return $stats;
}

function obtenerEstadisticasUsuarios() {
    $conexion = obtenerConexion();
    
    $stats = [];
    
    // Usuarios activos en los últimos 30 días
    $query = $conexion->query("
        SELECT COUNT(DISTINCT usuario_id) as total 
        FROM (
            SELECT usuario_id FROM comentarios WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            UNION
            SELECT usuario_id FROM respuestas WHERE fecha_respuesta >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ) as actividad
    ");
    $stats['usuarios_activos_30dias'] = $query ? ($query->fetch_assoc()['total'] ?? 0) : 0;
    
    // Usuarios con más comentarios
    $query = $conexion->query("
        SELECT u.nombre, u.email, COUNT(c.id) as cantidad_comentarios
        FROM usuarios u
        LEFT JOIN comentarios c ON u.id = c.usuario_id
        WHERE c.estado = 'aprobado'
        GROUP BY u.id
        ORDER BY cantidad_comentarios DESC
        LIMIT 5
    ");
    $stats['top_comentaristas'] = [];
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $stats['top_comentaristas'][] = $row;
        }
    }
    
    return $stats;
}

function obtenerEstadisticasContenido() {
    $conexion = obtenerConexion();
    
    $stats = [];
    
    // Noticias más comentadas
    $query = $conexion->query("
        SELECT n.id, n.titulo, COUNT(c.id) as cantidad_comentarios
        FROM noticias n
        LEFT JOIN comentarios c ON n.id = c.noticia_id AND c.estado = 'aprobado'
        WHERE n.estado = 'publicado'
        GROUP BY n.id
        ORDER BY cantidad_comentarios DESC
        LIMIT 5
    ");
    $stats['noticias_mas_comentadas'] = [];
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $stats['noticias_mas_comentadas'][] = $row;
        }
    }
    
    // Encuestas con más respuestas
    $query = $conexion->query("
        SELECT e.id, e.titulo, COUNT(DISTINCT r.usuario_id) as participantes
        FROM encuestas e
        LEFT JOIN respuestas r ON e.id = r.encuesta_id
        WHERE e.estado = 'activa'
        GROUP BY e.id
        ORDER BY participantes DESC
        LIMIT 5
    ");
    $stats['encuestas_mas_populares'] = [];
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $stats['encuestas_mas_populares'][] = $row;
        }
    }
    
    // Noticias más vistas
    $query = $conexion->query("
        SELECT id, titulo, vistas, fecha_publicacion
        FROM noticias
        WHERE estado = 'publicado'
        ORDER BY vistas DESC
        LIMIT 5
    ");
    $stats['noticias_mas_vistas'] = [];
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $stats['noticias_mas_vistas'][] = $row;
        }
    }
    
    return $stats;
}

function obtenerTopUsuariosActivos() {
    $conexion = obtenerConexion();
    
    $query = $conexion->query("
        SELECT 
            u.id,
            u.nombre,
            u.email,
            u.ultimo_acceso,
            (SELECT COUNT(*) FROM comentarios c WHERE c.usuario_id = u.id AND c.estado = 'aprobado') as comentarios,
            (SELECT COUNT(*) FROM respuestas r WHERE r.usuario_id = u.id) as respuestas_encuestas,
            u.fecha_registro
        FROM usuarios u
        WHERE u.activo = 1
        ORDER BY ultimo_acceso DESC
        LIMIT 10
    ");
    
    $usuarios = [];
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }
    
    return $usuarios;
}

function obtenerTopNoticiasVistas() {
    $conexion = obtenerConexion();
    
    $query = $conexion->query("
        SELECT 
            id,
            titulo,
            vistas,
            fecha_publicacion,
            (SELECT COUNT(*) FROM comentarios c WHERE c.noticia_id = noticias.id AND c.estado = 'aprobado') as comentarios
        FROM noticias
        WHERE estado = 'publicado'
        ORDER BY vistas DESC
        LIMIT 10
    ");
    
    $noticias = [];
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $noticias[] = $row;
        }
    }
    
    return $noticias;
}

function obtenerActividadReciente($limite = 10) {
    $sql = "
        SELECT 'noticia' as tipo, titulo as descripcion, fecha_publicacion as fecha, 'Admin' as usuario 
        FROM noticias 
        WHERE fecha_publicacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'normativa' as tipo, titulo as descripcion, fecha_publicacion as fecha, 'Admin' as usuario 
        FROM normativas 
        WHERE fecha_publicacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'usuario' as tipo, CONCAT('Nuevo usuario: ', nombre) as descripcion, fecha_registro as fecha, 'Sistema' as usuario 
        FROM usuarios 
        WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'comentario' as tipo, CONCAT('Comentario de: ', u.nombre) as descripcion, c.fecha_creacion as fecha, u.nombre as usuario 
        FROM comentarios c
        JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY fecha DESC 
        LIMIT ?
    ";
    
    return obtenerResultados($sql, [$limite]);
}

function obtenerTendenciasMensuales() {
    $conexion = obtenerConexion();
    
    $tendencias = [];
    
    $fechaActual = date('Y-m-01');
    $fechaAnterior = date('Y-m-01', strtotime('-1 month'));
    
    // Noticias
    $query = $conexion->query("SELECT COUNT(*) as total FROM noticias WHERE fecha_publicacion >= '$fechaActual'");
    $tendencias['noticias_este_mes'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM noticias WHERE fecha_publicacion >= '$fechaAnterior' AND fecha_publicacion < '$fechaActual'");
    $tendencias['noticias_mes_anterior'] = $query->fetch_assoc()['total'] ?? 0;
    
    $tendencias['tendencia_noticias'] = $tendencias['noticias_mes_anterior'] > 0 
        ? round((($tendencias['noticias_este_mes'] - $tendencias['noticias_mes_anterior']) / $tendencias['noticias_mes_anterior']) * 100) 
        : 0;
    
    // Normativas
    $query = $conexion->query("SELECT COUNT(*) as total FROM normativas WHERE fecha_publicacion >= '$fechaActual'");
    $tendencias['normativas_este_mes'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM normativas WHERE fecha_publicacion >= '$fechaAnterior' AND fecha_publicacion < '$fechaActual'");
    $tendencias['normativas_mes_anterior'] = $query->fetch_assoc()['total'] ?? 0;
    
    $tendencias['tendencia_normativas'] = $tendencias['normativas_mes_anterior'] > 0 
        ? round((($tendencias['normativas_este_mes'] - $tendencias['normativas_mes_anterior']) / $tendencias['normativas_mes_anterior']) * 100) 
        : 0;
    
    // Usuarios
    $query = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE fecha_registro >= '$fechaActual'");
    $tendencias['usuarios_este_mes'] = $query->fetch_assoc()['total'] ?? 0;
    
    $query = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE fecha_registro >= '$fechaAnterior' AND fecha_registro < '$fechaActual'");
    $tendencias['usuarios_mes_anterior'] = $query->fetch_assoc()['total'] ?? 0;
    
    $tendencias['tendencia_usuarios'] = $tendencias['usuarios_mes_anterior'] > 0 
        ? round((($tendencias['usuarios_este_mes'] - $tendencias['usuarios_mes_anterior']) / $tendencias['usuarios_mes_anterior']) * 100) 
        : 0;
    
    return $tendencias;
}
?>