<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/moneda_helper.php';

// Desactivar mostrar errores en producción
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

// Verificar sesión de usuario
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Obtener ID de la venta
$venta_id = isset($_GET['venta_id']) ? intval($_GET['venta_id']) : 0;

if (!$venta_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de venta no proporcionado']);
    exit;
}

try {
    // NO validar que pertenezca al cliente - es para admin
    
    // Obtener productos de la venta con cantidad disponible para devolución
    $sql = "
        WITH devoluciones_por_producto AS (
            SELECT 
                d.producto_id,
                COALESCE(SUM(d.cantidad), 0) as total_devuelto
            FROM devoluciones d
            WHERE d.venta_id = :venta_id
                AND d.estado_devolucion IN ('PENDIENTE', 'APROBADO')
            GROUP BY d.producto_id
        )
        SELECT 
            dv.id,
            dv.producto_id,
            p.nombre,
            p.codigo_interno,
            dv.cantidad as cantidad_original,
            COALESCE(dpp.total_devuelto, 0) as total_devuelto,
            (dv.cantidad - COALESCE(dpp.total_devuelto, 0)) as cantidad_disponible,
            dv.precio_unitario,
            pi.imagen_url
        FROM detalle_ventas dv
        INNER JOIN productos p ON p.id = dv.producto_id
        LEFT JOIN devoluciones_por_producto dpp ON dpp.producto_id = dv.producto_id
        LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
        WHERE dv.venta_id = :venta_id
            AND (dv.cantidad - COALESCE(dpp.total_devuelto, 0)) > 0
        ORDER BY p.nombre ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':venta_id' => $venta_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);

} catch (Exception $e) {
    error_log('Error en get_venta_products_admin.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener los productos']);
}
?>
