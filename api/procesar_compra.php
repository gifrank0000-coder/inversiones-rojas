<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'No autenticado']); exit; }

require_once __DIR__ . '/../app/models/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); echo json_encode(['success'=>false,'message'=>'Método no permitido']); exit;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!$data) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'JSON inválido']); exit; }

  
    $proveedor_id = isset($data['proveedor_id']) ? (int)$data['proveedor_id'] : null;
    $fecha_estimada = trim($data['fecha_estimada_entrega'] ?? '');
    $observaciones = trim($data['observaciones'] ?? '');
    $subtotal = isset($data['subtotal']) ? (float)$data['subtotal'] : 0.0;
    $iva = isset($data['iva']) ? (float)$data['iva'] : 0.0;
    $total = isset($data['total']) ? (float)$data['total'] : 0.0;
    $productos = $data['productos'] ?? [];

    $errors = [];
    if (!$proveedor_id) $errors[] = 'Proveedor requerido';
    if (!$fecha_estimada) $errors[] = 'Fecha estimada requerida';
    if (empty($productos) || !is_array($productos)) $errors[] = 'Debe agregar al menos un producto';

    if (!empty($errors)) { http_response_code(422); echo json_encode(['success'=>false,'message'=>implode('; ',$errors)]); exit; }

    $db = new Database(); $conn = $db->getConnection(); if (!$conn) throw new Exception('No se pudo conectar a la base de datos');

 
    $conn->beginTransaction();


    $codigo = 'OC' . date('Ymd') . strtoupper(substr(md5(uniqid('', true)), 0, 6));

    $sql = "INSERT INTO compras (codigo_compra, proveedor_id, usuario_id, subtotal, iva, total, estado_compra, fecha_estimada_entrega, observaciones, created_at) VALUES (?, ?, ?, ?, ?, ?, 'PENDIENTE', ?, ?, NOW()) RETURNING id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$codigo, $proveedor_id, $_SESSION['user_id'], $subtotal, $iva, $total, $fecha_estimada, $observaciones]);
    $compra_id = $stmt->fetchColumn();

    // Insertar detalle_compras
    $detSql = "INSERT INTO detalle_compras (compra_id, producto_id, cantidad, precio_unitario, subtotal, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $detStmt = $conn->prepare($detSql);
    foreach ($productos as $p) {
        $pid = (int)($p['id'] ?? 0);
        $cant = (int)($p['cantidad'] ?? 0);
        $precio = (float)($p['precio_unitario'] ?? 0.0);
        $sub = round($precio * $cant, 2);
        if ($pid <= 0 || $cant <= 0) continue;
        $detStmt->execute([$compra_id, $pid, $cant, $precio, $sub]);
    }

    // Registrar en bitacora
    try {
        $bit_sql = "INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address, user_agent, created_at) VALUES (?, 'CREAR_COMPRA', 'compras', ?, ?, ?, ?, NOW())";
        $bit_stmt = $conn->prepare($bit_sql);
        $detalles = json_encode(['codigo' => $codigo, 'proveedor_id' => $proveedor_id, 'total' => $total]);
        $bit_stmt->execute([$_SESSION['user_id'], $compra_id, $detalles, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
    } catch (Exception $e) { error_log('Warning bitacora crear compra: '.$e->getMessage()); }

    $conn->commit();

    echo json_encode(['success'=>true, 'id'=>$compra_id, 'codigo_compra'=>$codigo, 'total'=>$total]);
    exit;

} catch (Exception $e) {
    if (!empty($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log('Error procesar_compra.php: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error interno del servidor']);
    exit;
}

?>
