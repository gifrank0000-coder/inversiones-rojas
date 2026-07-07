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

$pedido_id = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;

if (!$pedido_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de pedido no proporcionado']);
    exit;
}

try {
    // Obtener productos del pedido con cantidad disponible para devolución
    $sql = "
        WITH devoluciones_por_producto AS (
            SELECT 
                d.producto_id,
                COALESCE(SUM(d.cantidad), 0) as total_devuelto
            FROM devoluciones d
            WHERE d.pedido_id = :pedido_id
                AND d.estado_devolucion IN ('PENDIENTE', 'APROBADO')
            GROUP BY d.producto_id
        )
        SELECT 
            dpo.id,
            dpo.producto_id,
            p.nombre,
            p.codigo_interno,
            dpo.cantidad as cantidad_original,
            COALESCE(dpp.total_devuelto, 0) as total_devuelto,
            (dpo.cantidad - COALESCE(dpp.total_devuelto, 0)) as cantidad_disponible,
            dpo.precio_unitario,
            pi.imagen_url
        FROM detalle_pedidos_online dpo
        INNER JOIN productos p ON p.id = dpo.producto_id
        LEFT JOIN devoluciones_por_producto dpp ON dpp.producto_id = dpo.producto_id
        LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
        WHERE dpo.pedido_id = :pedido_id
            AND (dpo.cantidad - COALESCE(dpp.total_devuelto, 0)) > 0
        ORDER BY p.nombre ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pedido_id' => $pedido_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);

} catch (Exception $e) {
    error_log('Error en get_pedido_products.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener los productos']);
}
?>
