<?php
// Buffer para evitar salida accidental (HTML, warnings)
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

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
    $quantity = $input['quantity'] ?? null;

    if (!$product_id || !is_numeric($product_id)) {
        http_response_code(400);
        if (ob_get_length()) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
        exit;
    }

    if (!isset($_SESSION['carrito']) || !isset($_SESSION['carrito'][$product_id])) {
        http_response_code(404);
        if (ob_get_length()) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado en el carrito']);
        exit;
    }

    if ($quantity === 0) {
        // Eliminar producto
        unset($_SESSION['carrito'][$product_id]);
    } elseif ($quantity > 0) {
        // Actualizar cantidad
        $_SESSION['carrito'][$product_id]['quantity'] = $quantity;
    } else {
        http_response_code(400);
        if (ob_get_length()) { ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'Cantidad inválida']);
        exit;
    }

    // Calcular totales
    $subtotal = 0;
    $total_items = 0;
    $precios_unitarios = [];
    foreach ($_SESSION['carrito'] as $pid => $item) {
        $subtotal += $item['precio'] * $item['quantity'];
        $total_items += $item['quantity'];
        $precios_unitarios[$pid] = $item['precio'];
    }

    $iva = round($subtotal * 0.16, 2);
    $total = $subtotal + $iva;

    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'success' => true,
        'message' => $quantity === 0 ? 'Producto eliminado del carrito' : 'Cantidad actualizada',
        'subtotal' => $subtotal,
        'iva' => $iva,
        'total' => $total,
        'total_items' => $total_items,
        'precios_unitarios' => $precios_unitarios
    ]);

} catch (Exception $e) {
    error_log('Error en update_cart.php: ' . $e->getMessage());
    http_response_code(500);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>