<?php
require_once 'config/config.php';
require_once 'app/models/database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query('SELECT producto_id, es_principal, COUNT(*) as total FROM producto_imagenes GROUP BY producto_id, es_principal ORDER BY producto_id');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo 'Distribución de imágenes por producto:' . PHP_EOL;
    foreach ($results as $row) {
        $tipo = $row['es_principal'] == 't' ? 'Principal' : 'Secundaria';
        echo "Producto {$row['producto_id']}: $tipo = {$row['total']} imágenes" . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>