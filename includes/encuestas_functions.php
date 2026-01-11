<?php
require_once __DIR__ . '/database.php';

function obtenerNumeroRespuestas($encuesta_id) {
    $mysqli = obtenerConexion();
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) as c 
        FROM respuestas r 
        JOIN preguntas p ON r.pregunta_id = p.id 
        WHERE p.encuesta_id = ?
    ");
    $stmt->bind_param("i", $encuesta_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $total = $res ? (int)$res['c'] : 0;

    $stmt2 = $mysqli->prepare("
        SELECT COUNT(*) as c2 
        FROM respuestas_opciones ro 
        JOIN preguntas p2 ON ro.pregunta_id = p2.id 
        WHERE p2.encuesta_id = ?
    ");
    $stmt2->bind_param("i", $encuesta_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result()->fetch_assoc();
    $total += $res2 ? (int)$res2['c2'] : 0;

    return $total;
}

function obtenerEncuestas($estado = null) {
    $mysqli = obtenerConexion();
    if ($estado) {
        $stmt = $mysqli->prepare("
            SELECT e.*, u.nombre as creador 
            FROM encuestas e 
            LEFT JOIN usuarios u ON e.creado_por = u.id 
            WHERE e.estado = ? 
            ORDER BY e.fecha_creacion DESC
        ");
        $stmt->bind_param("s", $estado);
    } else {
        $stmt = $mysqli->prepare("
            SELECT e.*, u.nombre as creador 
            FROM encuestas e 
            LEFT JOIN usuarios u ON e.creado_por = u.id 
            ORDER BY e.fecha_creacion DESC
        ");
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function obtenerEncuestaPorId($id) {
    $mysqli = obtenerConexion();
    $stmt = $mysqli->prepare("
        SELECT e.*, u.nombre as creador 
        FROM encuestas e 
        LEFT JOIN usuarios u ON e.creado_por = u.id 
        WHERE e.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $enc = $stmt->get_result()->fetch_assoc();

    if ($enc) {
        $stmt2 = $mysqli->prepare("
            SELECT * 
            FROM preguntas 
            WHERE encuesta_id = ? 
            ORDER BY orden ASC, id ASC
        ");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $preguntas = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($preguntas as &$p) {
            if ($p['tipo'] === 'opcion_unica' || $p['tipo'] === 'opcion_multiple') {
                $stmt3 = $mysqli->prepare("
                    SELECT * 
                    FROM opciones 
                    WHERE pregunta_id = ? 
                    ORDER BY orden ASC, id ASC
                ");
                $stmt3->bind_param("i", $p['id']);
                $stmt3->execute();
                $p['opciones'] = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
            } else {
                $p['opciones'] = [];
            }
        }
        $enc['preguntas'] = $preguntas;
    }
    return $enc;
}

function crearEncuesta($titulo, $descripcion, $creado_por, $preguntasArray) {
    $mysqli = obtenerConexion();
    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("
            INSERT INTO encuestas (titulo, descripcion, estado, creado_por) 
            VALUES (?, ?, 'inactiva', ?)
        ");
        $stmt->bind_param("ssi", $titulo, $descripcion, $creado_por);
        $stmt->execute();
        $encuesta_id = $mysqli->insert_id;

        $orden = 0;
        foreach ($preguntasArray as $p) {
            $orden++;
            $stmtP = $mysqli->prepare("
                INSERT INTO preguntas (encuesta_id, texto, tipo, orden) 
                VALUES (?, ?, ?, ?)
            ");
            $stmtP->bind_param("issi", $encuesta_id, $p['texto'], $p['tipo'], $orden);
            $stmtP->execute();
            $pregunta_id = $mysqli->insert_id;

            if (($p['tipo'] === 'opcion_unica' || $p['tipo'] === 'opcion_multiple') && !empty($p['opciones'])) {
                $ordenOp = 0;
                foreach ($p['opciones'] as $op) {
                    $ordenOp++;
                    $stmtO = $mysqli->prepare("
                        INSERT INTO opciones (pregunta_id, texto, orden) 
                        VALUES (?, ?, ?)
                    ");
                    $stmtO->bind_param("isi", $pregunta_id, $op, $ordenOp);
                    $stmtO->execute();
                }
            }
        }

        $mysqli->commit();
        return $encuesta_id;
    } catch (Exception $e) {
        $mysqli->rollback();
        return false;
    }
}

function encuestaTieneRespuestas($encuesta_id) {
    $mysqli = obtenerConexion();
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) as c 
        FROM respuestas r 
        JOIN preguntas p ON r.pregunta_id = p.id 
        WHERE p.encuesta_id = ?
    ");
    $stmt->bind_param("i", $encuesta_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()['c'] > 0) return true;

    $stmt2 = $mysqli->prepare("
        SELECT COUNT(*) as c2 
        FROM respuestas_opciones ro 
        JOIN preguntas p2 ON ro.pregunta_id = p2.id 
        WHERE p2.encuesta_id = ?
    ");
    $stmt2->bind_param("i", $encuesta_id);
    $stmt2->execute();
    return $stmt2->get_result()->fetch_assoc()['c2'] > 0;
}

function actualizarEncuesta($encuesta_id, $titulo, $descripcion, $preguntasArray) {
    if (encuestaTieneRespuestas($encuesta_id)) return false;
    $mysqli = obtenerConexion();
    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("
            UPDATE encuestas SET titulo = ?, descripcion = ? WHERE id = ?
        ");
        $stmt->bind_param("ssi", $titulo, $descripcion, $encuesta_id);
        $stmt->execute();

        $mysqli->query("
            DELETE FROM opciones 
            WHERE pregunta_id IN (SELECT id FROM preguntas WHERE encuesta_id = $encuesta_id)
        ");
        $mysqli->query("DELETE FROM preguntas WHERE encuesta_id = $encuesta_id");

        $orden = 0;
        foreach ($preguntasArray as $p) {
            $orden++;
            $stmtP = $mysqli->prepare("
                INSERT INTO preguntas (encuesta_id, texto, tipo, orden) 
                VALUES (?, ?, ?, ?)
            ");
            $stmtP->bind_param("issi", $encuesta_id, $p['texto'], $p['tipo'], $orden);
            $stmtP->execute();
            $pregunta_id = $mysqli->insert_id;

            if (($p['tipo'] === 'opcion_unica' || $p['tipo'] === 'opcion_multiple') && !empty($p['opciones'])) {
                $ordenOp = 0;
                foreach ($p['opciones'] as $op) {
                    $ordenOp++;
                    $stmtO = $mysqli->prepare("
                        INSERT INTO opciones (pregunta_id, texto, orden) 
                        VALUES (?, ?, ?)
                    ");
                    $stmtO->bind_param("isi", $pregunta_id, $op, $ordenOp);
                    $stmtO->execute();
                }
            }
        }

        $mysqli->commit();
        return true;
    } catch (Exception $e) {
        $mysqli->rollback();
        return false;
    }
}
function cambiarEstadoEncuesta($encuesta_id, $estado) {
    $mysqli = obtenerConexion();
    $stmt = $mysqli->prepare("UPDATE encuestas SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $estado, $encuesta_id);
    return $stmt->execute();
}

// Elimina una encuesta y todas sus preguntas y opciones asociadas
function eliminarEncuesta($encuesta_id) {
    $mysqli = obtenerConexion();
    $mysqli->begin_transaction();
    try {
        // Eliminar opciones de preguntas
        $mysqli->query("DELETE FROM opciones WHERE pregunta_id IN (SELECT id FROM preguntas WHERE encuesta_id = $encuesta_id)");
        // Eliminar preguntas
        $mysqli->query("DELETE FROM preguntas WHERE encuesta_id = $encuesta_id");
        // Eliminar la encuesta
        $stmt = $mysqli->prepare("DELETE FROM encuestas WHERE id = ?");
        $stmt->bind_param("i", $encuesta_id);
        $stmt->execute();
        $mysqli->commit();
        return true;
    } catch (Exception $e) {
        $mysqli->rollback();
        return false;
    }
}

function obtenerResultadosEncuesta($encuesta_id) {
    $mysqli = obtenerConexion();
    $results = [];
    
    // Obtener todas las preguntas de la encuesta
    $stmt = $mysqli->prepare("SELECT * FROM preguntas WHERE encuesta_id = ? ORDER BY orden");
    $stmt->bind_param("i", $encuesta_id);
    $stmt->execute();
    $preguntas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($preguntas as $pregunta) {
        $item = [
            'id' => $pregunta['id'],
            'texto' => $pregunta['texto'],
            'tipo' => $pregunta['tipo'],
            'total' => 0,
            'opciones' => []
        ];
        
        if ($pregunta['tipo'] === 'texto') {
            // Contar respuestas de texto
            $stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM respuestas WHERE pregunta_id = ?");
            $stmt->bind_param("i", $pregunta['id']);
            $stmt->execute();
            $item['total'] = $stmt->get_result()->fetch_assoc()['total'];
        } else {
            // Para preguntas con opciones
            $stmt = $mysqli->prepare("SELECT id, texto FROM opciones WHERE pregunta_id = ? ORDER BY orden");
            $stmt->bind_param("i", $pregunta['id']);
            $stmt->execute();
            $opciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            foreach ($opciones as $opcion) {
                // Contar votos por opción
                $stmt = $mysqli->prepare("SELECT COUNT(*) as votos FROM respuestas_opciones WHERE opcion_id = ?");
                $stmt->bind_param("i", $opcion['id']);
                $stmt->execute();
                $votos = $stmt->get_result()->fetch_assoc()['votos'];
                
                $item['opciones'][] = [
                    'id' => $opcion['id'],
                    'texto' => $opcion['texto'],
                    'votos' => $votos
                ];
                
                $item['total'] += $votos;
            }
        }
        
        $results[] = $item;
    }
    
    return $results;
}

function exportarResultadosCSV($encuesta_id) {
    $mysqli = obtenerConexion();
    $results = obtenerResultadosEncuesta($encuesta_id);
    $encuesta = obtenerEncuestaPorId($encuesta_id);
    
    $output = fopen('php://output', 'w');
    ob_start();
    
    // Encabezado
    fputcsv($output, ['Resultados de Encuesta: ' . $encuesta['titulo']]);
    fputcsv($output, ['']); // Línea vacía
    
    foreach ($results as $pregunta) {
        fputcsv($output, [$pregunta['texto']]);
        
        if ($pregunta['tipo'] === 'texto') {
            fputcsv($output, ['Tipo: Respuesta de texto']);
            fputcsv($output, ['Total respuestas:', $pregunta['total']]);
            
            // Obtener todas las respuestas de texto
            $stmt = $mysqli->prepare("SELECT respuesta_texto FROM respuestas WHERE pregunta_id = ?");
            $stmt->bind_param("i", $pregunta['id']);
            $stmt->execute();
            $respuestas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            foreach ($respuestas as $respuesta) {
                fputcsv($output, ['Respuesta:', $respuesta['respuesta_texto']]);
            }
        } else {
            fputcsv($output, ['Tipo: ' . ($pregunta['tipo'] === 'opcion_unica' ? 'Opción única' : 'Opción múltiple')]);
            fputcsv($output, ['Total respuestas:', $pregunta['total']]);
            
            foreach ($pregunta['opciones'] as $opcion) {
                $porcentaje = $pregunta['total'] > 0 ? round(($opcion['votos'] / $pregunta['total']) * 100, 2) : 0;
                fputcsv($output, [
                    $opcion['texto'],
                    $opcion['votos'] . ' votos',
                    $porcentaje . '%'
                ]);
            }
        }
        
        fputcsv($output, ['']); // Línea vacía entre preguntas
    }
    
    fclose($output);
    return ob_get_clean();
}

function obtenerRespondentesEncuesta($encuesta_id) {
    $conexion = obtenerConexion();
    
    $query = "SELECT DISTINCT u.id, u.nombre, u.email, MAX(r.fecha_respuesta) as fecha_respuesta
              FROM respuestas r
              JOIN usuarios u ON r.usuario_id = u.id
              WHERE r.encuesta_id = ?
              GROUP BY u.id
              ORDER BY fecha_respuesta DESC";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $encuesta_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene las respuestas de un usuario específico para una encuesta
 */
function obtenerRespuestasUsuario($encuesta_id, $usuario_id) {
    $conexion = obtenerConexion();
    
    // Respuestas de texto
    $query_texto = "SELECT r.*, p.texto as pregunta_texto, p.tipo as pregunta_tipo
                   FROM respuestas r
                   JOIN preguntas p ON r.pregunta_id = p.id
                   WHERE r.encuesta_id = ? AND r.usuario_id = ?
                   ORDER BY p.orden";
    
    $stmt_texto = $conexion->prepare($query_texto);
    $stmt_texto->bind_param("ii", $encuesta_id, $usuario_id);
    $stmt_texto->execute();
    $respuestas_texto = $stmt_texto->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Respuestas de opciones (únicas/múltiples)
    $query_opciones = "SELECT 
                          ro.usuario_id, 
                          ro.pregunta_id, 
                          ro.opcion_id, 
                          ro.fecha_respuesta,
                          p.texto as pregunta_texto,
                          p.tipo as pregunta_tipo,
                          o.texto as opcion_texto
                       FROM respuestas_opciones ro
                       JOIN preguntas p ON ro.pregunta_id = p.id
                       JOIN opciones o ON ro.opcion_id = o.id
                       WHERE p.encuesta_id = ? AND ro.usuario_id = ?
                       ORDER BY p.orden";
    
    $stmt_opciones = $conexion->prepare($query_opciones);
    $stmt_opciones->bind_param("ii", $encuesta_id, $usuario_id);
    $stmt_opciones->execute();
    $respuestas_opciones = $stmt_opciones->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Combinar resultados
    $respuestas_completas = array_merge($respuestas_texto, $respuestas_opciones);
    
    // Agrupar por pregunta para opciones múltiples
    $respuestas_agrupadas = [];
    foreach ($respuestas_completas as $respuesta) {
        $pregunta_id = $respuesta['pregunta_id'];
        
        if (!isset($respuestas_agrupadas[$pregunta_id])) {
            $respuestas_agrupadas[$pregunta_id] = [
                'pregunta_id' => $pregunta_id,
                'pregunta_texto' => $respuesta['pregunta_texto'],
                'pregunta_tipo' => $respuesta['pregunta_tipo'],
                'fecha_respuesta' => $respuesta['fecha_respuesta'],
                'respuestas' => []
            ];
        }
        
        if ($respuesta['pregunta_tipo'] === 'texto') {
            $respuestas_agrupadas[$pregunta_id]['respuestas'][] = [
                'tipo' => 'texto',
                'valor' => $respuesta['respuesta_texto']
            ];
        } else {
            $respuestas_agrupadas[$pregunta_id]['respuestas'][] = [
                'tipo' => 'opcion',
                'opcion_id' => $respuesta['opcion_id'],
                'opcion_texto' => $respuesta['opcion_texto']
            ];
        }
    }
    
    return array_values($respuestas_agrupadas);
}

/**
 * Obtiene las opciones seleccionadas por un usuario para una pregunta
 */
function obtenerOpcionesSeleccionadas($pregunta_id, $usuario_id) {
    $conexion = obtenerConexion();
    
    $query = "SELECT o.texto 
              FROM respuestas_opciones ro
              JOIN opciones o ON ro.opcion_id = o.id
              WHERE ro.pregunta_id = ? AND ro.usuario_id = ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $pregunta_id, $usuario_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtiene todas las preguntas de una encuesta con sus opciones
 */
function obtenerPreguntasEncuesta($encuesta_id) {
    $conexion = obtenerConexion();
    
    $query = "SELECT p.* 
              FROM preguntas p
              WHERE p.encuesta_id = ?
              ORDER BY p.orden";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $encuesta_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $preguntas = $result->fetch_all(MYSQLI_ASSOC);
    
    // Obtener opciones para cada pregunta
    foreach ($preguntas as &$pregunta) {
        if (in_array($pregunta['tipo'], ['opcion_unica', 'opcion_multiple'])) {
            $query_opciones = "SELECT id, texto FROM opciones WHERE pregunta_id = ? ORDER BY orden";
            $stmt_opciones = $conexion->prepare($query_opciones);
            $stmt_opciones->bind_param("i", $pregunta['id']);
            $stmt_opciones->execute();
            $pregunta['opciones'] = $stmt_opciones->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
    
    return $preguntas;
}