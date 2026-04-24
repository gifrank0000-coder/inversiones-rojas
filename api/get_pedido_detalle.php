<?php
// ============================================================
// get_pedido_detalle.php  →  /api/get_pedido_detalle.php
// Devuelve los datos de un pedido y sus items para el modal de detalle.
// ============================================================

ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'No autenticado']));
}

$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$pedido_id) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'ID de pedido faltante']));
}

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_rol'] ?? '';

try {
    $db   = new Database();
    $conn = $db->getConnection();

    // Cargar pedido
    $stmt = $conn->prepare(
        "SELECT p.*, c.nombre_completo AS cliente_nombre, c.telefono_principal, c.email AS cliente_email
         FROM pedidos_online p
         LEFT JOIN clientes c ON p.cliente_id = c.id
         WHERE p.id = ?"
    );
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Pedido no encontrado']));
    }

    // Si es vendedor, solo puede ver pedidos asignados a él
    if (strtolower($user_role) === 'vendedor' && (int)$pedido['vendedor_asignado_id'] !== $user_id) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'No tienes permiso para ver este pedido']));
    }

    // Obtener items
    $stmt = $conn->prepare(
        "SELECT d.cantidad, d.precio_unitario, d.subtotal, p.nombre
         FROM detalle_pedidos_online d
         LEFT JOIN productos p ON d.producto_id = p.id
         WHERE d.pedido_id = ?"
    );
    $stmt->execute([$pedido_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'pedido'  => $pedido,
        'items'   => $items,
    ]);

} catch (Exception $e) {
    error_log('[get_pedido_detalle] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno']);
}
