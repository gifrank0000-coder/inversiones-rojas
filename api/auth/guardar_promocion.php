<?php
// API: guardar_promocion.php
// Responde siempre JSON; valida tipo de promoción y usa RETURNING id (Postgres)

ob_start();
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

header('Content-Type: application/json; charset=utf-8');

// Shutdown handler para capturar errores fatales y devolver JSON
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err !== null) {
        @ob_end_clean();
        http_response_code(500);
        $payload = ['success' => false, 'message' => 'Error fatal en el servidor'];
        if (defined('APP_DEBUG') && APP_DEBUG) $payload['debug'] = $err;
        echo json_encode($payload);
        exit;
    }
    @ob_end_flush();
});

// Permisos básicos
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? 'Cliente') === 'Cliente') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Leer entrada: puede ser JSON (application/json) o multipart/form-data (en cuyo caso usar $_POST)
$rawInput = file_get_contents('php://input');
$data = null;
if ($rawInput !== '') {
    $data = json_decode($rawInput, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
        exit;
    }
} elseif (!empty($_POST)) {
    // peticiones multipart/form-data llegarán en $_POST y $_FILES
    $data = $_POST;
}

if (empty($data) || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Campos requeridos y sanitización
$data['nombre'] = trim($data['nombre'] ?? '');
$data['descripcion'] = trim($data['descripcion'] ?? '');

if (empty($data['nombre']) || empty($data['tipo']) || empty($data['fecha_inicio']) || empty($data['fecha_fin'])) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit;
}

// Validación de longitud mínima del nombre
if (strlen($data['nombre']) < 3) {
    echo json_encode(['success' => false, 'message' => 'El nombre de la promoción debe tener al menos 3 caracteres']);
    exit;
}

// Validación de fecha de inicio (no puede ser anterior a hoy)
$fecha_inicio = new DateTime($data['fecha_inicio']);
$fecha_hoy = new DateTime();
$fecha_hoy->setTime(0, 0, 0); // Solo fecha, sin hora
if ($fecha_inicio < $fecha_hoy) {
    echo json_encode(['success' => false, 'message' => 'La fecha de inicio no puede ser anterior a la fecha actual']);
    exit;
}

// Validación de fecha fin posterior a inicio
$fecha_fin = new DateTime($data['fecha_fin']);
if ($fecha_fin <= $fecha_inicio) {
    echo json_encode(['success' => false, 'message' => 'La fecha de fin debe ser posterior a la fecha de inicio']);
    exit;
}

// Campos requeridos
if (empty($data['nombre']) || empty($data['tipo']) || empty($data['fecha_inicio']) || empty($data['fecha_fin'])) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit;
}

if (empty($data['productos']) || !is_array($data['productos'])) {
    echo json_encode(['success' => false, 'message' => 'Debe seleccionar al menos un producto']);
    exit;
}

// Valores permitidos en la BD (según constraint existente)
$allowed = ['DESCUENTO','2X1','PORCENTAJE'];
// Mapear valores de formulario a los permitidos
$map = [
    'descuento' => 'DESCUENTO',
    'monto' => 'DESCUENTO',
    'combo' => 'DESCUENTO',
    'envio' => 'DESCUENTO',
    'regalo' => 'DESCUENTO',
    '2x1' => '2X1',
    'porcentaje' => 'PORCENTAJE',
    'porcentaje' => 'PORCENTAJE'
];

$tipo_input = strtolower((string)$data['tipo']);
if (!isset($map[$tipo_input]) || !in_array($map[$tipo_input], $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de promoción no válido. Valores permitidos: ' . implode(', ', $allowed)]);
    exit;
}
$tipo_bd = $map[$tipo_input];

// Validación de nombre único
$sql_check = "SELECT COUNT(*) FROM promociones WHERE nombre = ?";
$params_check = [$data['nombre']];
if (!empty($data['id'])) {
    $sql_check .= " AND id != ?";
    $params_check[] = $data['id'];
}
$stmt_check = $conn->prepare($sql_check);
$stmt_check->execute($params_check);
if ($stmt_check->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'Ya existe una promoción con este nombre']);
    exit;
}

// Procesar y normalizar el valor según el tipo (acepta '15%', '15', '15.0', '15,0')
$raw_val = isset($data['valor']) ? (string)$data['valor'] : '';
$valor_bd = null;
if (in_array($tipo_bd, ['DESCUENTO','PORCENTAJE'])) {
    // Eliminar todo lo que no sea dígito, coma, punto o signo menos
    $clean = preg_replace('/[^0-9,\.\-]/', '', $raw_val);
    // Normalizar coma a punto
    $clean = str_replace(',', '.', $clean);
    if ($clean === '' || !is_numeric($clean)) {
        // Valor inválido para porcentaje
        echo json_encode(['success'=>false,'message'=>'Valor de promoción inválido para porcentaje']);
        exit;
    }
    $valor_bd = (float)$clean;
    // Asegurar rango 0-100 para porcentajes
    if ($valor_bd < 0) $valor_bd = 0;
    if ($valor_bd > 100) $valor_bd = 100;
} else {
    // Para tipos que no usan porcentaje (2X1, envío, etc.) intentamos parsear número o dejar 0
    $clean = preg_replace('/[^0-9,\.\-]/', '', $raw_val);
    $clean = str_replace(',', '.', $clean);
    $valor_bd = $clean === '' ? 0 : (is_numeric($clean) ? (float)$clean : 0);
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception('No se pudo conectar a la base de datos');

    $conn->beginTransaction();

    // Detectar imagen subida vía multipart/form-data
    $isMultipart = !empty($_FILES);
    if ($isMultipart) {
        // Reemplazar $data con $_POST values for fields
        $post = $_POST;
        $data = array_merge($data ?? [], $post);
    }

    if (!empty($data['id'])) {
        $sql = "UPDATE promociones SET nombre = ?, descripcion = ?, tipo_promocion = ?, valor = ?, fecha_inicio = ?, fecha_fin = ?, estado = ?, imagen_url = ?, tipo_imagen = ?, imagen_banco_key = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        // Preparar valores de imagen si vienen
        $imagen_url = null;
        $tipo_imagen = null;
        $imagen_banco_key = null;
        if ($isMultipart && !empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../public/img/promotions/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $filename = 'promo_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . strtolower($ext);
            $target = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target)) {
                $imagen_url = (defined('BASE_URL') ? BASE_URL : '') . '/public/img/promotions/' . $filename;
                $tipo_imagen = 'manual';
            }
        } elseif (!empty($data['imagen_banco_key'])) {
            $imagen_banco_key = $data['imagen_banco_key'];
            $imagen_url = (defined('BASE_URL') ? BASE_URL : '') . '/public/img/promo_bank/' . $imagen_banco_key;
            $tipo_imagen = 'auto';
        } elseif (!empty($data['imagen_existente'])) {
            // Mantener la imagen existente si el formulario la envía y no se reemplaza
            $imagen_url = $data['imagen_existente'];
            $tipo_imagen = 'manual';
        }

        $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $tipo_bd,
            $valor_bd,
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['estado'],
            $imagen_url,
            $tipo_imagen,
            $imagen_banco_key,
            $data['id']
        ]);
        $promocion_id = $data['id'];
        $stmt = $conn->prepare("DELETE FROM producto_promociones WHERE promocion_id = ?");
        $stmt->execute([$promocion_id]);
        $mensaje = 'Promoción actualizada exitosamente';
    } else {
        // PostgreSQL: usar RETURNING id
        $sql = "INSERT INTO promociones (nombre, descripcion, tipo_promocion, valor, fecha_inicio, fecha_fin, estado, imagen_url, tipo_imagen, imagen_banco_key, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) RETURNING id";
        $stmt = $conn->prepare($sql);
        // Preparar valores imagen para INSERT
        $imagen_url = null;
        $tipo_imagen = null;
        $imagen_banco_key = null;
        if ($isMultipart && !empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../public/img/promotions/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $filename = 'promo_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . strtolower($ext);
            $target = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target)) {
                $imagen_url = (defined('BASE_URL') ? BASE_URL : '') . '/public/img/promotions/' . $filename;
                $tipo_imagen = 'manual';
            }
        } elseif (!empty($data['imagen_banco_key'])) {
            $imagen_banco_key = $data['imagen_banco_key'];
            $imagen_url = (defined('BASE_URL') ? BASE_URL : '') . '/public/img/promo_bank/' . $imagen_banco_key;
            $tipo_imagen = 'auto';
        }

        $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $tipo_bd,
            $valor_bd,
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['estado'],
            $imagen_url,
            $tipo_imagen,
            $imagen_banco_key
        ]);
        $promocion_id = $stmt->fetchColumn();
        if (!$promocion_id) throw new Exception('No se pudo obtener el ID de la promoción creada');
        $mensaje = 'Promoción creada exitosamente';
    }

    // Asociar productos
    $productos_ids = array_map('intval', $data['productos']);
    $productos_ids = array_filter($productos_ids, fn($v)=> $v>0);
    if (empty($productos_ids)) throw new Exception('Lista de productos inválida');

    $sql = "INSERT INTO producto_promociones (producto_id, promocion_id, created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    foreach ($productos_ids as $pid) {
        $stmt->execute([$pid, $promocion_id]);
    }

    // Bitácora
    $accion = !empty($data['id']) ? 'ACTUALIZAR_PROMOCION' : 'CREAR_PROMOCION';
    $sql = "INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles) VALUES (?, ?, 'promociones', ?, ?::jsonb)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], $accion, $promocion_id, json_encode(['nombre'=>$data['nombre']])]);

    $conn->commit();
    echo json_encode(['success'=>true,'message'=>$mensaje,'id'=>$promocion_id]);

} catch (PDOException $e) {
    if (isset($conn)) $conn->rollBack();
    error_log('Error PDO en guardar_promocion: ' . $e->getMessage());
    $msg = 'Error de base de datos';
    if (defined('APP_DEBUG') && APP_DEBUG) $msg = $e->getMessage();
    echo json_encode(['success'=>false,'message'=>$msg]);
    exit;
} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    error_log('Error general en guardar_promocion: ' . $e->getMessage());
    $msg = $e->getMessage();
    echo json_encode(['success'=>false,'message'=>$msg]);
    exit;
}

?>