<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/models/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '1';

if (empty($id)) {
    echo json_encode(['ok' => false, 'error' => 'ID de proveedor requerido']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Convertir boolean a integer (0 ó 1) para PostgreSQL
    $estado_int = ($estado === '1' || $estado === 'true') ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE proveedores SET estado = :estado, updated_at = NOW() WHERE id = :id");
    $stmt->execute([
        ':estado' => $estado_int,
        ':id' => $id
    ]);
    
    $nuevo_estado = $estado === '1' || $estado === 'true' ? 'activado' : 'inhabilitado';
    
    echo json_encode([
        'ok' => true,
        'message' => "Proveedor {$nuevo_estado} correctamente"
    ]);
} catch (Exception $e) {
    error_log('ERROR update_proveedor_estado: ' . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}