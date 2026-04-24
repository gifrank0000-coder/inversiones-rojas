<?php
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

try {
    $pdo = Database::getInstance();
    
    $stmt = $pdo->prepare("
        SELECT id, nombre, descripcion 
        FROM metodos_pago 
        WHERE estado = true 
        ORDER BY nombre
    ");
    
    $stmt->execute();
    $metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'metodos' => $metodos
    ]);
    
} catch (Exception $e) {
    error_log('Error en get_metodos_pago.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar métodos de pago'
    ]);
}
?>