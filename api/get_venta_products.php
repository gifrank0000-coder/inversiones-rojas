<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

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

// Obtener ID de la venta o pedido
$venta_id = isset($_GET['venta_id']) ? intval($_GET['venta_id']) : 0;
$pedido_id = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;

if (!$venta_id && !$pedido_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de venta o pedido no proporcionado']);
    exit;
}

try {
    // Obtener cliente asociado al usuario
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
        exit;
    }
    
    // Verificar que la venta o pedido pertenece al cliente
    $orderType = $venta_id ? 'venta' : 'pedido';

    if ($orderType === 'venta') {
        $stmt = $pdo->prepare("SELECT id FROM ventas WHERE id = ? AND cliente_id = ?");
        $stmt->execute([$venta_id, $cliente['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Venta no encontrada o no pertenece al cliente']);
            exit;
        }
    } else {
        $stmt = $pdo->prepare("SELECT id FROM pedidos_online WHERE id = ? AND cliente_id = ?");
        $stmt->execute([$pedido_id, $cliente['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Pedido no encontrado o no pertenece al cliente']);
            exit;
        }
    }

    // Versión mejorada - SOLO productos con cantidad disponible
    if ($orderType === 'venta') {
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
                dv.producto_id,
                dv.cantidad as cantidad_original,
                dv.precio_unitario,
                p.nombre as producto_nombre,
                p.codigo_interno,
                -- Calcular cantidad disponible (comprada - devuelta)
                (dv.cantidad - COALESCE(dpp.total_devuelto, 0)) as cantidad_disponible
            FROM detalle_ventas dv
            INNER JOIN productos p ON p.id = dv.producto_id
            LEFT JOIN devoluciones_por_producto dpp ON dpp.producto_id = dv.producto_id
            WHERE dv.venta_id = :venta_id
                AND (dv.cantidad - COALESCE(dpp.total_devuelto, 0)) > 0
            ORDER BY p.nombre ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':venta_id' => $venta_id]);
    } else {
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
                dp.producto_id,
                dp.cantidad as cantidad_original,
                dp.precio_unitario,
                p.nombre as producto_nombre,
                p.codigo_interno,
                -- Calcular cantidad disponible (comprada - devuelta)
                (dp.cantidad - COALESCE(dpp.total_devuelto, 0)) as cantidad_disponible
            FROM detalle_pedidos_online dp
            INNER JOIN productos p ON p.id = dp.producto_id
            LEFT JOIN devoluciones_por_producto dpp ON dpp.producto_id = dp.producto_id
            WHERE dp.pedido_id = :pedido_id
                AND (dp.cantidad - COALESCE(dpp.total_devuelto, 0)) > 0
            ORDER BY p.nombre ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pedido_id' => $pedido_id]);
    }
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear resultados
    $resultados = [];
    foreach ($items as $item) {
        $resultados[] = [
            'producto_id' => intval($item['producto_id']),
            'cantidad_original' => intval($item['cantidad_original']),
            'cantidad_disponible' => intval($item['cantidad_disponible']),
            'precio_unitario' => floatval($item['precio_unitario']),
            'producto_nombre' => $item['producto_nombre'],
            'codigo_interno' => $item['codigo_interno'],
            // Para mantener compatibilidad con el JS actual
            'cantidad' => intval($item['cantidad_disponible'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $resultados,
        'total_productos' => count($resultados)
    ]);
    
} catch (Exception $e) {
    error_log('Error en get_venta_products: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al cargar productos: ' . $e->getMessage()]);
}
?>