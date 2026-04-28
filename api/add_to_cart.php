<?php
// Buffer para evitar salida accidental (HTML, warnings)
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// En la carpeta /api, la raíz del proyecto es dirname(__DIR__)
$projectRoot = dirname(__DIR__);

header('Content-Type: application/json; charset=utf-8');
require_once $projectRoot . '/config/config.php';
require_once $projectRoot . '/app/models/database.php';
require_once $projectRoot . '/app/helpers/promocion_helper.php';

// Capturar errores fatales que puedan imprimir HTML y convertirlos a JSON
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        if (ob_get_length()) { ob_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor (fatal)']);
    }
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = $input['product_id'] ?? null;
    $quantity = $input['quantity'] ?? 1;

    if (!$product_id || !is_numeric($product_id) || $quantity < 1) {
        http_response_code(400);
        if (ob_get_length()) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    // Verificar si el producto existe y está disponible
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        error_log('Error: No se pudo conectar a la base de datos');
        throw new Exception('Error de conexión a la base de datos');
    }

    $stmt = $conn->prepare("SELECT p.id, p.nombre, p.precio_venta, p.stock_actual, p.estado,
                                   COALESCE(pi.imagen_url, '') as imagen_url,
                                   pr.tipo_promocion AS promo_tipo_promocion,
                                   pr.valor AS promo_valor,
                                   pr.nombre AS promo_nombre
                            FROM productos p
                            LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
                            LEFT JOIN producto_promociones pp ON pp.producto_id = p.id
                            LEFT JOIN promociones pr ON pr.id = pp.promocion_id
                              AND pr.estado = true
                              AND pr.fecha_inicio <= CURRENT_DATE
                              AND pr.fecha_fin >= CURRENT_DATE
                            WHERE p.id = ? AND p.estado = true
                            ORDER BY pr.fecha_fin ASC
                            LIMIT 1");
    $stmt->execute([$product_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        http_response_code(404);
        if (ob_get_length()) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }

    if ($producto['stock_actual'] < $quantity) {
        http_response_code(400);
        if (ob_get_length()) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'Stock insuficiente']);
        exit;
    }

    $precioUnitario = floatval($producto['precio_venta']);
    if (!empty($producto['promo_tipo_promocion'])) {
        $precioUnitario = calcularPrecioConPromocion($precioUnitario, $producto['promo_tipo_promocion'], $producto['promo_valor']);
    }

    // Inicializar carrito si no existe
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    // Agregar o actualizar cantidad en el carrito
    if (isset($_SESSION['carrito'][$product_id])) {
        $_SESSION['carrito'][$product_id]['quantity'] += $quantity;
        $_SESSION['carrito'][$product_id]['cantidad'] = $_SESSION['carrito'][$product_id]['quantity'];
    } else {
        $_SESSION['carrito'][$product_id] = [
            'id' => $producto['id'],
            'nombre' => $producto['nombre'],
            'precio' => $precioUnitario,
            'precio_original' => floatval($producto['precio_venta']),
            'cantidad' => $quantity,
            'quantity' => $quantity,
            'stock' => $producto['stock_actual'],
            'imagen' => $producto['imagen_url'] ?? '',
            'promocion' => [
                'tipo' => $producto['promo_tipo_promocion'] ?? null,
                'valor' => $producto['promo_valor'] ?? null,
                'nombre' => $producto['promo_nombre'] ?? null,
            ]
        ];
    }

    // Calcular total de items en carrito
    $total_items = array_sum(array_column($_SESSION['carrito'], 'quantity'));
    $producto_qty = $_SESSION['carrito'][$product_id]['quantity'] ?? $quantity;
    
    // Stock restante = stock disponible en BD menos lo que ya está en el carrito
    // Esto muestra al usuario cuánto más puede agregar
    $stock_remaining = intval($producto['stock_actual']) - intval($producto_qty);
    if ($stock_remaining < 0) $stock_remaining = 0;

    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'success' => true,
        'ok' => true,
        'message' => 'Producto agregado al carrito',
        'total_items' => $total_items,
        'cart_count' => $total_items,
        'producto_qty' => $producto_qty,
        'producto_nombre' => $producto['nombre'],
        'producto_precio' => $precioUnitario,
        'producto_imagen' => $producto['imagen_url'] ?? '',
        'stock_remaining' => $stock_remaining
    ]);

} catch (Exception $e) {
    error_log('Error en add_to_cart.php: ' . $e->getMessage());
    http_response_code(500);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>