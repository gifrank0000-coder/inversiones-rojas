<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/models/database.php';

$nombre = $_GET['nombre'] ?? $_POST['nombre'] ?? '';

if (empty($nombre)) {
    echo json_encode(['available' => false, 'error' => 'Nombre requerido']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id FROM categorias WHERE LOWER(nombre) = LOWER(:nombre) LIMIT 1");
    $stmt->execute([':nombre' => $nombre]);
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'available' => !$existe,
        'exists' => (bool)$existe
    ]);
} catch (Exception $e) {
    error_log('ERROR check_categoria: ' . $e->getMessage());
    echo json_encode(['available' => false, 'error' => $e->getMessage()]);
}