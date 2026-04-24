<?php
// Buffer y manejo de errores para asegurar salida JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/promocion_helper.php';

header('Content-Type: application/json; charset=utf-8');

// DEBUG endpoint: añade ?debug=1 para ver el contenido de la sesión y facilitar diagnóstico
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    $cart_debug = $_SESSION['carrito'] ?? [];
    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'ok' => true,
        'debug' => true,
        'session_carrito' => $cart_debug,
        'product_ids' => array_values(array_keys($cart_debug))
    ]);
    exit;
}

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        if (ob_get_length()) { ob_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error interno del servidor (fatal)']);
    }
});

try {
    $carrito = [];
    if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
        $db = new Database();
        $conn = $db->getConnection();

        if (!$conn) throw new Exception('No se pudo conectar a la base de datos');

        $product_ids = array_keys($_SESSION['carrito']);
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';

        $sql = "SELECT DISTINCT ON (p.id) p.id, p.nombre, p.descripcion, p.precio_venta, p.stock_actual,
                       COALESCE(pi.imagen_url, '') as imagen_url,
                       c.nombre as categoria,
                       pr.tipo_promocion AS promo_tipo_promocion,
                       pr.valor AS promo_valor,
                       pr.nombre AS promo_nombre
                FROM productos p
                LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
                LEFT JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN producto_promociones pp ON pp.producto_id = p.id
                LEFT JOIN promociones pr ON pr.id = pp.promocion_id
                    AND pr.estado = true
                    AND pr.fecha_inicio <= CURRENT_DATE
                    AND pr.fecha_fin >= CURRENT_DATE
                WHERE p.id IN ($placeholders) AND p.estado = true
                ORDER BY p.id, pr.fecha_fin ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($product_ids);
        $productos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $seenProducts = [];
        foreach ($productos_db as $producto) {
            $id = $producto['id'];
            if (isset($seenProducts[$id])) continue;
            $seenProducts[$id] = true;

            if (isset($_SESSION['carrito'][$id])) {
                $precioReal = calcularPrecioConPromocion(
                    floatval($producto['precio_venta']),
                    $producto['promo_tipo_promocion'] ?? null,
                    $producto['promo_valor'] ?? null
                );

                $cantidad = $_SESSION['carrito'][$id]['quantity'] ?? $_SESSION['carrito'][$id]['cantidad'] ?? 1;

                $carrito[] = [
                    'id' => $id,
                    'nombre' => $producto['nombre'],
                    'descripcion' => $producto['descripcion'],
                    'categoria' => $producto['categoria'],
                    'precio' => $precioReal,
                    'precio_original' => floatval($producto['precio_venta']),
                    'imagen' => !empty($producto['imagen_url']) ? $producto['imagen_url'] : '',
                    'cantidad' => $cantidad,
                    'stock' => $producto['stock_actual'],
                    'promocion' => [
                        'tipo' => $producto['promo_tipo_promocion'] ?? null,
                        'valor' => $producto['promo_valor'] ?? null,
                        'nombre' => $producto['promo_nombre'] ?? null,
                    ]
                ];
            }
        }
    }

    $subtotal = 0;
    $count = 0;
    foreach ($carrito as $item) {
        $subtotal += $item['precio'] * $item['cantidad'];
        $count += isset($item['cantidad']) ? intval($item['cantidad']) : 0;
    }
    $iva = round($subtotal * 0.16, 2);
    $total = $subtotal + $iva;

    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['ok' => true, 'carrito' => $carrito, 'subtotal' => $subtotal, 'iva' => $iva, 'total' => $total, 'count' => $count]);

} catch (Exception $e) {
    error_log('Error en get_cart.php: ' . $e->getMessage());
    if (ob_get_length()) { ob_clean(); }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor']);
}
?>
