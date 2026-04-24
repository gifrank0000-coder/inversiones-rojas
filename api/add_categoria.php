<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación mínima
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/../app/models/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tipo_producto = trim($_POST['tipo_producto'] ?? 'GENERAL');

    // Tipos válidos
    $tipos_validos = ['GENERAL', 'MOTO', 'REPUESTO', 'ACCESORIO'];
    if (!in_array($tipo_producto, $tipos_validos)) {
        $tipo_producto = 'GENERAL';
    }

    $errors = [];
    if ($nombre === '') {
        $errors['nombre'] = 'El nombre es requerido';
    } elseif (mb_strlen($nombre) > 100) {
        $errors['nombre'] = 'El nombre no puede exceder 100 caracteres';
    }

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'errors' => $errors]);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception('No se pudo conectar a la base de datos');

    // PRIMERO: Obtener o crear el tipo_producto correspondiente
    $tipo_id = null;
    if ($tipo_producto !== 'GENERAL') {
        $stmtTipo = $conn->prepare('SELECT id FROM tipos_producto WHERE nombre = ? LIMIT 1');
        $stmtTipo->execute([$tipo_producto]);
        $result = $stmtTipo->fetchColumn();
        if ($result !== false) {
            $tipo_id = (int)$result;
        }

        // Si no existe y el tipo es MOTO, intentar con VEHICULO (compatibilidad histórica)
        if ($tipo_id === null && $tipo_producto === 'MOTO') {
            $stmtTipo->execute(['VEHICULO']);
            $result = $stmtTipo->fetchColumn();
            if ($result !== false) {
                $tipo_id = (int)$result;
            }
        }

        // Si aún no existe el tipo, crearlo (para evitar valores NULL)
        if ($tipo_id === null) {
            $stmtInsertTipo = $conn->prepare('INSERT INTO tipos_producto (nombre, created_at) VALUES (?, NOW()) RETURNING id');
            $stmtInsertTipo->execute([$tipo_producto]);
            $result = $stmtInsertTipo->fetchColumn();
            if ($result !== false) {
                $tipo_id = (int)$result;
            }
        }
    }

    // SEGUNDO: Verificar existencia (case-insensitive)
    $stmt = $conn->prepare('SELECT id, nombre FROM categorias WHERE LOWER(nombre) = LOWER(?) AND tipo_producto_id IS NOT DISTINCT FROM ? LIMIT 1');
    $stmt->execute([$nombre, $tipo_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Retornar existente para que el frontend lo seleccione
        http_response_code(200);
        echo json_encode([
            'ok' => true, 
            'id' => (int)$existing['id'], 
            'nombre' => $existing['nombre'],
            'tipo_producto' => $tipo_producto,
            'created' => false, 
            'message' => 'Ya existe una categoría con este nombre y tipo'
        ]);
        exit;
    }

    // TERCERO: Insertar nueva categoría
    $ins = $conn->prepare('INSERT INTO categorias (nombre, descripcion, tipo_producto_id, estado, created_at, updated_at) 
                          VALUES (?, ?, ?, true, NOW(), NOW()) RETURNING id, nombre');
    $ins->execute([$nombre, $descripcion, $tipo_id]);
    $result = $ins->fetch(PDO::FETCH_ASSOC);
    
    $newId = (int)$result['id'];
    $newNombre = $result['nombre'];

    // CUARTO: Registrar bitácora
    try {
        $bit_sql = "INSERT INTO bitacora_sistema 
                    (usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address, user_agent, created_at) 
                    VALUES (?, 'CREAR_CATEGORIA', 'categorias', ?, ?, ?, ?, NOW())";
        $bit_stmt = $conn->prepare($bit_sql);
        $detalles = json_encode([
            'nombre' => $newNombre,
            'tipo_producto' => $tipo_producto,
            'tipo_id' => $tipo_id,
            'descripcion' => $descripcion
        ]);
        $bit_stmt->execute([
            $_SESSION['user_id'], 
            $newId, 
            $detalles, 
            $_SERVER['REMOTE_ADDR'] ?? '', 
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log('Warning: no se pudo registrar bitacora: ' . $e->getMessage());
    }

    http_response_code(201);
    echo json_encode([
        'ok' => true, 
        'id' => $newId, 
        'nombre' => $newNombre,
        'tipo_producto' => $tipo_producto,
        'created' => true, 
        'message' => 'Categoría creada exitosamente'
    ]);

} catch (Exception $e) {
    error_log('Error en add_categoria.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor: ' . $e->getMessage()]);
}

exit;
?>