<?php
// /inversiones-rojas/api/inhabilitar_venta.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Por favor inicie sesión.'
    ]);
    exit();
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Use POST.'
    ]);
    exit();
}

require_once __DIR__ . '/../app/models/database.php';

$venta_id = isset($_POST['venta_id']) ? intval($_POST['venta_id']) : 0;

if (!$venta_id) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de venta inválido'
    ]);
    exit();
}

try {
    $pdo = Database::getInstance();

    // Verificar que exista la venta
    $stmt = $pdo->prepare("SELECT id, estado_venta FROM ventas WHERE id = ?");
    $stmt->execute([$venta_id]);
    $venta = $stmt->fetch();

    if (!$venta) {
        echo json_encode([
            'success' => false,
            'message' => 'Venta no encontrada'
        ]);
        exit();
    }

    if (strtoupper($venta['estado_venta']) === 'INHABILITADO') {
        echo json_encode([
            'success' => true,
            'message' => 'La venta ya se encuentra inhabilitada.'
        ]);
        exit();
    }

    $stmt = $pdo->prepare("UPDATE ventas SET estado_venta = 'INHABILITADO', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$venta_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Venta inhabilitada correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo inhabilitar la venta'
        ]);
    }
} catch (PDOException $e) {
    error_log('Error PDO inhabilitar venta: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos al inhabilitar la venta',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Error inhabilitar venta: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ]);
}
