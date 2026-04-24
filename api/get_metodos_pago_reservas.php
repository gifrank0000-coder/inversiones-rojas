<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/models/database.php';

// Pública - no requiere autenticación para ver métodos de reserva

try {
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    $stmt = $conn->prepare("SELECT id, tipo, banco, codigo_banco, cedula, telefono, numero_cuenta FROM metodos_pago_reservas WHERE estado = true ORDER BY tipo, banco");
    $stmt->execute();
    $metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'metodos' => $metodos]);
} catch (Exception $e) {
    error_log('ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
exit;
?>