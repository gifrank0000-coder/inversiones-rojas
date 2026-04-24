<?php
// API para marcar notificaciones como leídas

header('Content-Type: application/json; charset=UTF-8');

session_start();
require_once __DIR__ . '/../app/models/database.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        // Marcar notificaciones de pedidos como leídas
        $stmt = $conn->prepare('UPDATE notificaciones_vendedor SET leida = true WHERE usuario_id = ? AND leida = false');
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    }
} catch (Exception $e) {
    error_log('Error en mark_notifications_read.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno']);
}