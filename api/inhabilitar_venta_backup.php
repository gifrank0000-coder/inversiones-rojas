<?php
// /inversiones-rojas/api/inhabilitar_venta.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Por favor inicio sesión.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Use POST.'
    ]);
    exit();
}

require_once __DIR__ . '/../app/models/database.php';

$venta_id = isset($_POST['venta_id']) ? intval($_POST['venta_id']) : 0;

if (!$venta_id) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de venta inválido'
    ]);
    exit();
}

try {
    $pdo = Database::getInstance();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, estado_venta, observaciones FROM ventas WHERE id = ?");
    $stmt->execute([$venta_id]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Venta no encontrada'
        ]);
        exit();
    }

    if ($venta['estado_venta'] === 'INHABILITADO') {
        echo json_encode([
            'success' => true,
            'message' => 'Ya está cancelada'
        ]);
        exit();
    }

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
        
        // Marcar reserva como CANCELADA
        $stmtReserva = $pdo->prepare("UPDATE reservas SET estado_reserva = 'CANCELADA', updated_at = NOW() WHERE codigo_reserva = ?");
        $stmtReserva->execute([$codigo_reserva]);
    }

    $stmt = $pdo->prepare("UPDATE ventas SET estado_venta = 'INHABILITADO' WHERE id = ?");
    $stmt->execute([$venta_id]);

    $pdo->commit();

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => $codigo_reserva 
                ? 'Venta cancelada. Stock restaurado.' 
                : 'Venta cancelada correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo cancela la venta'
        ]);
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error PDO cancelar venta: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error cancelar venta: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ]);
}