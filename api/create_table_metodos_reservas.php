<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/models/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    $sql = "CREATE TABLE IF NOT EXISTS metodos_pago_reservas (
        id SERIAL PRIMARY KEY,
        tipo VARCHAR(50) NOT NULL,
        banco VARCHAR(100),
        cedula VARCHAR(20),
        telefono VARCHAR(15),
        numero_cuenta VARCHAR(50),
        codigo_banco VARCHAR(10),
        estado BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $conn->exec($sql);

    echo json_encode(['ok' => true, 'message' => 'Tabla creada exitosamente']);
} catch (Exception $e) {
    error_log('ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
exit;
?>