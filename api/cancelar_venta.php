<?php
// api/cancelar_venta.php - Cancela venta y restaura stock
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../app/models/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$venta_id = $_POST['venta_id'] ?? 0;
if (!$venta_id) {
    echo json_encode(['success' => false, 'message' => 'ID requerido']);
    exit;
}

try {
    $pdo = Database::getInstance();
    $pdo->beginTransaction();

    // Obtener datos de la venta
    $stmt = $pdo->prepare("SELECT id, estado_venta, observaciones FROM ventas WHERE id = ?");
    $stmt->execute([$venta_id]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Venta no encontrada']);
        exit;
    }

    if ($venta['estado_venta'] === 'INHABILITADO') {
        echo json_encode(['success' => true, 'message' => 'Ya está cancelada']);
        exit;
    }

    // Detectar si viene de reserva
    $codigo_reserva = null;
    if (preg_match('/reserva:\s*([A-Z0-9\-]+)/i', $venta['observaciones'] ?? '', $matches)) {
        $codigo_reserva = $matches[1];
    }
    
    if ($codigo_reserva) {
        // Restaurar stock
        $stmtProd = $pdo->prepare("SELECT producto_id, cantidad FROM detalle_ventas WHERE venta_id = ?");
        $stmtProd->execute([$venta_id]);
        while ($prod = $stmtProd->fetch(PDO::FETCH_ASSOC)) {
            $stmtStock = $pdo->prepare("UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?");
            $stmtStock->execute([$prod['cantidad'], $prod['producto_id']]);
        }
        
        // Marcar reserva como CANCELADA (no disponible)
        $stmtReserva = $pdo->prepare("
            UPDATE reservas 
            SET estado_reserva = 'CANCELADA',
                updated_at = NOW() 
            WHERE codigo_reserva = ?
        ");
        $stmtReserva->execute([$codigo_reserva]);
        
        $restaurada_reserva = true;
    }

    // Cancelar venta
    $stmt = $pdo->prepare("UPDATE ventas SET estado_venta = 'INHABILITADO' WHERE id = ?");
    $stmt->execute([$venta_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $codigo_reserva 
            ? 'Venta cancelada. Stock restaurado. Reserve código: ' . $codigo_reserva 
            : 'Venta cancelada correctamente'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error cancelar_venta: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>