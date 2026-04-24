<?php
// Buffer y evitar output HTML
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

// Shutdown handler para errores fatales
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor (fatal)']);
    }
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Vaciar carrito de sesión
    if (isset($_SESSION['carrito'])) {
        unset($_SESSION['carrito']);
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Carrito vaciado',
        'subtotal' => 0,
        'envio' => 0,
        'total' => 0,
        'total_items' => 0
    ]);
} catch (Exception $e) {
    error_log('Error en clear_cart.php: ' . $e->getMessage());
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

?>