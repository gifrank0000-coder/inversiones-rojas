<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$cliente_id = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : 0;

if (!$cliente_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de cliente no proporcionado']);
    exit;
}

try {
    // Obtener ventas y pedidos del cliente
    $sql = "
        -- Ventas
        SELECT 
            v.id,
            v.codigo_venta as codigo,
            'venta' as tipo,
            v.created_at as fecha
        FROM ventas v
        WHERE v.cliente_id = :cliente_id
        UNION ALL
        -- Pedidos online
        SELECT 
            p.id,
            p.codigo_pedido as codigo,
            'pedido' as tipo,
            p.created_at as fecha
        FROM pedidos_online p
        WHERE p.cliente_id = :cliente_id
        ORDER BY fecha DESC
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cliente_id' => $cliente_id]);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'ventas' => $ventas,
        'count' => count($ventas)
    ]);

} catch (Exception $e) {
    error_log('Error en get_cliente_ventas.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener las ventas']);
}
?>
