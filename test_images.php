<?php
require_once 'config/config.php';
require_once 'app/models/database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query('SELECT COUNT(*) as total FROM producto_imagenes');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Total imágenes en BD: ' . $result['total'] . PHP_EOL;

    $stmt2 = $conn->query('SELECT producto_id, COUNT(*) as imgs FROM producto_imagenes GROUP BY producto_id HAVING COUNT(*) > 1');
    $multiples = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo 'Productos con múltiples imágenes: ' . count($multiples) . PHP_EOL;

    if (count($multiples) > 0) {
        echo 'Primeros 5 productos con múltiples imágenes:' . PHP_EOL;
        for ($i = 0; $i < min(5, count($multiples)); $i++) {
            echo "- Producto ID {$multiples[$i]['producto_id']}: {$multiples[$i]['imgs']} imágenes" . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>