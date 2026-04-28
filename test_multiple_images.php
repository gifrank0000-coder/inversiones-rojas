<?php
/**
 * TEST_MULTIPLE_IMAGES.php
 * Script para validar el sistema de múltiples imágenes
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/models/database.php';

$test_results = [];
$db = new Database();
$conn = $db->getConnection();

// Test 1: Verificar tabla producto_imagenes
try {
    $stmt = $conn->query("SELECT * FROM producto_imagenes LIMIT 1");
    $test_results['tabla_existe'] = ['ok' => true, 'msg' => 'Tabla producto_imagenes existe'];
} catch (Exception $e) {
    $test_results['tabla_existe'] = ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
}

// Test 2: Verificar campos
try {
    $stmt = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name='producto_imagenes' ORDER BY ordinal_position");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $required_cols = ['id', 'producto_id', 'imagen_url', 'es_principal', 'orden', 'created_at'];
    $missing = array_diff($required_cols, $columns);
    
    if (empty($missing)) {
        $test_results['campos'] = ['ok' => true, 'msg' => 'Todos los campos requeridos existen', 'columns' => $columns];
    } else {
        $test_results['campos'] = ['ok' => false, 'msg' => 'Campos faltantes: ' . implode(', ', $missing)];
    }
} catch (Exception $e) {
    $test_results['campos'] = ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
}

// Test 3: Contar total de imágenes
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM producto_imagenes");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $test_results['total_imagenes'] = ['ok' => true, 'msg' => 'Total de imágenes en BD: ' . $result['total']];
} catch (Exception $e) {
    $test_results['total_imagenes'] = ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
}

// Test 4: Verificar consistencia de imagen principal
try {
    $stmt = $conn->query("SELECT producto_id, COUNT(*) as principal_count FROM producto_imagenes WHERE es_principal = true GROUP BY producto_id HAVING COUNT(*) > 1");
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        $test_results['imagen_principal'] = ['ok' => true, 'msg' => 'Cada producto tiene máximo 1 imagen principal'];
    } else {
        $test_results['imagen_principal'] = ['ok' => false, 'msg' => 'Productos con múltiples imágenes principales: ' . count($duplicates)];
    }
} catch (Exception $e) {
    $test_results['imagen_principal'] = ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
}

// Test 5: Verificar orden secuencial
try {
    $stmt = $conn->query("SELECT producto_id, COUNT(*) as total_imgs FROM producto_imagenes GROUP BY producto_id HAVING COUNT(*) > 1");
    $productos_multi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $orden_issues = [];
    foreach ($productos_multi as $prod) {
        $pid = $prod['producto_id'];
        $stmt = $conn->prepare("SELECT orden FROM producto_imagenes WHERE producto_id = ? ORDER BY orden");
        $stmt->execute([$pid]);
        $ordenes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Verificar que son 1, 2, 3, 4...
        $expected = range(1, count($ordenes));
        if ($ordenes !== $expected) {
            $orden_issues[] = "Producto $pid: órdenes " . implode(',', $ordenes);
        }
    }
    
    if (empty($orden_issues)) {
        $test_results['orden'] = ['ok' => true, 'msg' => 'Todos los órdenes son secuenciales'];
    } else {
        $test_results['orden'] = ['ok' => false, 'msg' => 'Órdenes inconsistentes: ' . implode('; ', $orden_issues)];
    }
} catch (Exception $e) {
    $test_results['orden'] = ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
}

// Test 6: Verificar que los archivos existen
try {
    $stmt = $conn->query("SELECT imagen_url FROM producto_imagenes LIMIT 10");
    $urls = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missing_files = [];
    foreach ($urls as $url) {
        // Convertir URL a ruta local
        $path = __DIR__ . str_replace('/inversiones-rojas', '', $url);
        if (!file_exists($path)) {
            $missing_files[] = $url;
        }
    }
    
    if (empty($missing_files)) {
        $test_results['archivos'] = ['ok' => true, 'msg' => 'Todos los archivos de imagen existen (muestra 10 primeras)'];
    } else {
        $test_results['archivos'] = ['ok' => false, 'msg' => count($missing_files) . ' archivos faltantes'];
    }
} catch (Exception $e) {
    $test_results['archivos'] = ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
}

// Test 7: Verificar directorio de subida
try {
    $upload_dir = dirname(__DIR__) . '/inversiones-rojas/public/img/products/';
    if (is_dir($upload_dir) && is_writable($upload_dir)) {
        $test_results['directorio'] = ['ok' => true, 'msg' => 'Directorio de subida existe y es escribible'];
    } else {
        $test_results['directorio'] = ['ok' => false, 'msg' => 'Directorio no existe o no es escribible'];
    }
} catch (Exception $e) {
    $test_results['directorio'] = ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
}

// Test 8: Productos con múltiples imágenes
try {
    $stmt = $conn->query("SELECT producto_id, COUNT(*) as total_imgs FROM producto_imagenes GROUP BY producto_id HAVING COUNT(*) > 1 ORDER BY total_imgs DESC LIMIT 5");
    $multi_img_prods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($multi_img_prods)) {
        $msg = 'Productos con múltiples imágenes: ';
        foreach ($multi_img_prods as $p) {
            $msg .= "Prod#{$p['producto_id']} ({$p['total_imgs']} imgs) | ";
        }
        $test_results['productos_multi'] = ['ok' => true, 'msg' => rtrim($msg, '| ')];
    } else {
        $test_results['productos_multi'] = ['ok' => false, 'msg' => 'No hay productos con múltiples imágenes aún'];
    }
} catch (Exception $e) {
    $test_results['productos_multi'] = ['ok' => false, 'msg' => 'Error: ' . $e->getMessage()];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Sistema de Múltiples Imágenes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #1F9166;
            border-bottom: 2px solid #1F9166;
            padding-bottom: 10px;
        }
        .test-item {
            margin: 20px 0;
            padding: 15px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .test-item.ok {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .test-item.fail {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .icon {
            font-size: 24px;
            min-width: 30px;
        }
        .test-item.ok .icon {
            color: #28a745;
        }
        .test-item.fail .icon {
            color: #dc3545;
        }
        .test-content {
            flex: 1;
        }
        .test-label {
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }
        .test-message {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .summary {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .summary h2 {
            color: #1565c0;
            margin: 0 0 10px 0;
        }
        .pass-rate {
            font-size: 18px;
            font-weight: bold;
            color: #1F9166;
        }
        .action-buttons {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #1F9166;
            color: white;
        }
        .btn-primary:hover {
            background: #157a4a;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-image"></i> Test - Sistema de Múltiples Imágenes</h1>
        
        <?php
        $passed = count(array_filter($test_results, function($r) { return $r['ok']; }));
        $total = count($test_results);
        $rate = round(($passed / $total) * 100);
        ?>
        
        <div class="summary">
            <h2>Resumen de Pruebas</h2>
            <p>Pruebas pasadas: <span class="pass-rate"><?php echo $passed; ?>/<?php echo $total; ?> (<?php echo $rate; ?>%)</span></p>
        </div>
        
        <?php foreach ($test_results as $test_name => $result): ?>
            <div class="test-item <?php echo $result['ok'] ? 'ok' : 'fail'; ?>">
                <div class="icon">
                    <i class="fas <?php echo $result['ok'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                </div>
                <div class="test-content">
                    <div class="test-label"><?php echo ucfirst(str_replace('_', ' ', $test_name)); ?></div>
                    <div class="test-message"><?php echo htmlspecialchars($result['msg']); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="action-buttons">
            <a href="javascript:location.reload()" class="btn btn-primary">
                <i class="fas fa-sync"></i> Recargar
            </a>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Volver al inicio
            </a>
        </div>
    </div>
</body>
</html>
