<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../../app/models/database.php';
require_once __DIR__ . '/../../config/config.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];
}

$reservaId = $input['reserva_id'] ?? null;
$codigo = $input['codigo_reserva'] ?? null;

if (!$reservaId && !$codigo) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Se requiere reserva_id o codigo_reserva']);
    exit;
}

try {
    $pdo = Database::getInstance();
    if (!$pdo) throw new Exception('No DB');

    $pdo->beginTransaction();

    if ($reservaId) {
        $stmt = $pdo->prepare("SELECT * FROM reservas WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $reservaId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM reservas WHERE codigo_reserva = :cod FOR UPDATE");
        $stmt->execute([':cod' => $codigo]);
    }

    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reserva) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
        exit;
    }

    // actualizar estado a PENDIENTE
    $stmtUpd = $pdo->prepare("UPDATE reservas SET estado_reserva = 'PENDIENTE', vendedor_id = :vendedor, updated_at = now() WHERE id = :id");
    $stmtUpd->execute([':vendedor' => $userId, ':id' => $reserva['id']]);

    $pdo->commit();
    echo json_encode(['success' => true, 'reserva_id' => (int)$reserva['id']]);
    exit;

} catch (Exception $e) {
    try { if ($pdo && $pdo->inTransaction()) $pdo->rollBack(); } catch (Exception $__) {}
    error_log('reactivar_reserva error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
