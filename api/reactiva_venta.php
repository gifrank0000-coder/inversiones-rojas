<?php
// api/reactiva_venta.php - Reactivar venta cancelada
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

    // Verificar que existe y está cancelada
    $stmt = $pdo->prepare("SELECT id, estado_venta, observaciones FROM ventas WHERE id = ?");
    $stmt->execute([$venta_id]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Venta no encontrada']);
        exit;
    }

    if ($venta['estado_venta'] === 'COMPLETADA') {
        echo json_encode(['success' => false, 'message' => 'La venta ya está activa']);
        exit;
    }

    // Reactivar venta
    $stmt = $pdo->prepare("UPDATE ventas SET estado_venta = 'COMPLETADA' WHERE id = ?");
    $stmt->execute([$venta_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Venta reactivada correctamente'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error reactiva_venta: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>