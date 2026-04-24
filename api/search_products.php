<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de conexión a base de datos']);
        exit;
    }
    
    // Obtener parámetro de búsqueda
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (strlen($query) < 2) {
        http_response_code(400);
        echo json_encode(['error' => 'Mínimo 2 caracteres para buscar']);
        exit;
    }
    
    // Búsqueda en productos: solo por nombre
    $searchTerm = '%' . $query . '%';
    
    $sql = "SELECT 
                p.id,
                p.nombre,
                p.descripcion,
                p.precio_venta,
                c.nombre as categoria,
                pi.imagen_url
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN producto_imagenes pi ON p.id = pi.producto_id AND pi.es_principal = true
            WHERE p.estado = true
            AND LOWER(p.nombre) LIKE LOWER(:search)
            ORDER BY p.nombre ASC
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':search' => $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => count($results),
        'results' => $results
    ]);
    
} catch (Exception $e) {
    error_log('ERROR en search_products.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al buscar productos']);
}
?>
