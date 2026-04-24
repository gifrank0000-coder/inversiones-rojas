<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'No autenticado']); exit; }

require_once __DIR__ . '/../app/models/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Método no permitido']); exit; }

    $compra_id = isset($_POST['compra_id']) ? (int)$_POST['compra_id'] : 0;
    if (!$compra_id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'compra_id requerido']); exit; }

    $db = new Database(); $conn = $db->getConnection(); if (!$conn) throw new Exception('No se pudo conectar');

    // Verificar compra
    $stmt = $conn->prepare('SELECT id, estado_compra FROM compras WHERE id = ?');
    $stmt->execute([$compra_id]);
    $compra = $stmt->fetch();
    if (!$compra) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Compra no encontrada']); exit; }
    if ($compra['estado_compra'] === 'RECIBIDA') { echo json_encode(['success'=>true,'message'=>'Ya estaba recibida']); exit; }

    $conn->beginTransaction();

    // Actualizar estado
    $upd = $conn->prepare("UPDATE compras SET estado_compra = 'RECIBIDA', updated_at = NOW() WHERE id = ?");
    $upd->execute([$compra_id]);

    // Recuperar detalle
    $dstmt = $conn->prepare('SELECT producto_id, cantidad FROM detalle_compras WHERE compra_id = ?');
    $dstmt->execute([$compra_id]);
    $rows = $dstmt->fetchAll();

    $movStmt = $conn->prepare('INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, motivo, referencia, usuario_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $prodUpd = $conn->prepare('UPDATE productos SET stock_actual = stock_actual + ? , updated_at = NOW() WHERE id = ?');

    foreach ($rows as $r) {
        $pid = (int)$r['producto_id'];
        $cant = (int)$r['cantidad'];
        if ($pid <= 0 || $cant <= 0) continue;

        // obtener stock anterior
        $sstmt = $conn->prepare('SELECT stock_actual FROM productos WHERE id = ?');
        $sstmt->execute([$pid]);
        $srow = $sstmt->fetch();
        $stockAnterior = $srow ? (int)$srow['stock_actual'] : 0;
        $stockActual = $stockAnterior + $cant;

        // actualizar producto
        $prodUpd->execute([$cant, $pid]);

        // insertar movimiento
        $motivo = 'Recepción de compra #' . $compra_id;
        $referencia = 'COMPRA:' . $compra_id;
        $movStmt->execute([$pid, 'ENTRADA_COMPRA', $cant, $stockAnterior, $stockActual, $motivo, $referencia, $_SESSION['user_id']]);
    }

    // bitacora
    try {
        $bit_sql = "INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address, user_agent, created_at) VALUES (?, 'RECIBIR_COMPRA', 'compras', ?, ?, ?, ?, NOW())";
        $bit_stmt = $conn->prepare($bit_sql);
        $detalles = json_encode(['compra_id'=>$compra_id]);
        $bit_stmt->execute([$_SESSION['user_id'], $compra_id, $detalles, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
    } catch (Exception $e) { error_log('Warning bitacora recibir compra: '.$e->getMessage()); }

    $conn->commit();
    echo json_encode(['success'=>true,'message'=>'Orden marcada como recibida']);
    exit;

} catch (Exception $e) {
    if (!empty($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log('Error marcar_recibida.php: '.$e->getMessage());
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'Error interno']); exit;
}

?>
