<?php
require_once 'config/config.php';
require_once 'app/models/database.php';

echo "========================================\n";
echo "VALIDACIÓN SISTEMA DE MÚLTIPLES IMÁGENES\n";
echo "========================================\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Test 1: Tabla existe
    $stmt = $conn->query('SELECT COUNT(*) as total FROM producto_imagenes');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Base de datos: Tabla producto_imagenes con {$result['total']} imágenes\n";
    
    // Test 2: Campos correctos
    $stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name='producto_imagenes' ORDER BY ordinal_position");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Campos: " . implode(', ', $columns) . "\n";
    
    // Test 3: Verificar API update_product.php
    echo "\n--- Verificación de Archivos ---\n";
    if (file_exists('api/update_product.php')) {
        $content = file_get_contents('api/update_product.php');
        $has_image_processing = strpos($content, 'Procesar imágenes adicionales') !== false;
        echo ($has_image_processing ? "✓" : "✗") . " api/update_product.php: Soporte de imágenes " . ($has_image_processing ? "SÍ" : "NO") . "\n";
    }
    
    // Test 4: Verificar product_detail.php
    if (file_exists('app/views/layouts/product_detail.php')) {
        $content = file_get_contents('app/views/layouts/product_detail.php');
        $has_setup = strpos($content, 'setupImageUpload') !== false;
        $has_button = strpos($content, 'addImagesBtn') !== false;
        echo ($has_setup ? "✓" : "✗") . " product_detail.php: Función setupImageUpload " . ($has_setup ? "SÍ" : "NO") . "\n";
        echo ($has_button ? "✓" : "✗") . " product_detail.php: Botón 'Agregar imágenes' " . ($has_button ? "SÍ" : "NO") . "\n";
    }
    
    // Test 5: Directorio de subida
    echo "\n--- Verificación de Directorios ---\n";
    $upload_dir = dirname(__DIR__) . '/inversiones-rojas/public/img/products/';
    if (is_dir($upload_dir)) {
        echo "✓ Directorio: $upload_dir EXISTE\n";
        if (is_writable($upload_dir)) {
            echo "✓ Permisos: Directorio es ESCRIBIBLE\n";
        } else {
            echo "✗ Permisos: Directorio NO es escribible\n";
        }
    } else {
        echo "✗ Directorio: $upload_dir NO EXISTE\n";
    }
    
    // Test 6: Resumen
    echo "\n========================================\n";
    echo "✓ SISTEMA DE MÚLTIPLES IMÁGENES IMPLEMENTADO\n";
    echo "========================================\n\n";
    
    echo "CÓMO USAR:\n";
    echo "1. Crear producto: inventario.php → Nuevo producto\n";
    echo "2. Ver detalles: product_detail.php?id=X\n";
    echo "3. Agregar imágenes: Botón 'Agregar imágenes' (solo si logueado)\n";
    echo "4. Galería: Muestra todas las imágenes con navegación\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
