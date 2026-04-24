<?php
// session_start SIEMPRE primero — antes de cualquier output o header
if (session_status() === PHP_SESSION_NONE) session_start();

// C:/xampp/htdocs/inversiones-rojas/api/procesar_venta.php
// display_errors OFF para que los errores PHP no rompan el JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Archivo de log detallado
$log_file = __DIR__ . '/debug_ventas.log';
$log_content = "=== " . date('Y-m-d H:i:s') . " ===\n";

try {
    // Log 1: Información básica
    $log_content .= "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
    
    // Log 2: Leer datos JSON
    $raw_input = file_get_contents('php://input');
    $log_content .= "RAW INPUT (primeros 500 chars):\n" . substr($raw_input, 0, 500) . "\n";
    
    if (empty($raw_input)) {
        throw new Exception('No se recibieron datos');
    }
    
    $input = json_decode($raw_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $log_content .= "JSON DECODE ERROR: " . json_last_error_msg() . "\n";
        throw new Exception('Error JSON: ' . json_last_error_msg());
    }
    
    if (!$input) {
        throw new Exception('JSON decodificado como null');
    }
    
    $log_content .= "JSON decode OK\n";
    $log_content .= "Productos recibidos: " . (isset($input['productos']) ? count($input['productos']) : 0) . "\n";
    $log_content .= "Cliente ID: " . ($input['cliente_id'] ?? 'null') . "\n";
    $log_content .= "Método Pago ID: " . ($input['metodo_pago_id'] ?? 'null') . "\n";
    
    // Validación mínima
    if (empty($input['productos']) || !is_array($input['productos'])) {
        throw new Exception('No hay productos en la venta');
    }
    
    // Log 3: Incluir database.php
    $log_content .= "Incluyendo database.php...\n";
    require_once __DIR__ . '/../app/models/database.php';
    $log_content .= "database.php incluido OK\n";
    
    // Log 4: Obtener conexión
    $log_content .= "Obteniendo instancia de Database...\n";
    $pdo = Database::getInstance();
    
    if (!$pdo) {
        throw new Exception('No se pudo obtener conexión a la base de datos');
    }
    
    $log_content .= "Conexión PDO obtenida OK\n";
    
    // Verificar driver
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $log_content .= "Driver: " . $driver . "\n";
    
    // Generar código de venta
    $codigo_venta = 'V-' . date('YmdHis') . '-' . rand(100, 999);
    $log_content .= "Código venta generado: " . $codigo_venta . "\n";
    
    // Manejar cliente
    $cliente_id = null;
    if (!empty($input['cliente_id'])) {
        $cliente_str = (string) $input['cliente_id'];
        if (strpos($cliente_str, 'cf-') === 0) {
            // Cliente de facturación
            $cliente_id = null;
            $log_content .= "Cliente de facturación (cf-), usando NULL\n";
        } else {
            $cliente_id = (int) $input['cliente_id'];
            $log_content .= "Cliente regular ID: " . $cliente_id . "\n";
            
            // Verificar si existe
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = ? AND estado = true");
            $stmt->execute([$cliente_id]);
            if (!$stmt->fetch()) {
                $cliente_id = null;
                $log_content .= "Cliente no encontrado, usando NULL\n";
            }
        }
    }
    
    // Método de pago
    $metodo_pago_id = (int) ($input['metodo_pago_id'] ?? 1);
    $log_content .= "Método pago ID final: " . $metodo_pago_id . "\n";
    
    // Moneda de pago
    $moneda_pago = $input['moneda_pago'] ?? 'BS';
    $tasa_cambio = (float) ($input['tasa_cambio'] ?? 1);
    $monto_bs = (float) ($input['monto_bs'] ?? $total);
    $monto_usd = (float) ($input['monto_usd'] ?? $total);
    $log_content .= "Moneda: $moneda_pago, Tasa: $tasa_cambio, Bs: $monto_bs, Usd: $monto_usd\n";
    
    // Usuario
    $usuario_id = $_SESSION['user_id'] ?? 1;
$log_content .= "Usuario ID: " . $usuario_id . "\n";
    
    // Valores
    $subtotal = (float) ($input['subtotal'] ?? 0);
    $iva = (float) ($input['iva'] ?? 0);
    $total = (float) ($input['total'] ?? 0);
    $log_content .= "Valores: subtotal=$subtotal, iva=$iva, total=$total\n";
    
    // Observaciones
    $observaciones = '';
    if (!empty($input['efectivo_recibido'])) {
        $observaciones = "Efectivo: $" . number_format($input['efectivo_recibido'], 2);
    }
    
    // INICIAR TRANSACCIÓN
    $log_content .= "Iniciando transacción...\n";
    $pdo->beginTransaction();
    
    try {
        // 1. INSERTAR VENTA
        $log_content .= "Preparando INSERT en ventas...\n";
        $sql_venta = "
            INSERT INTO ventas (
                codigo_venta, cliente_id, usuario_id, metodo_pago_id,
                subtotal, iva, total, estado_venta, observaciones, created_at,
                moneda_pago, tasa_cambio, monto_bs, monto_usd
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'COMPLETADA', ?, NOW(), ?, ?, ?, ?)
            RETURNING id
        ";
        
        $log_content .= "SQL Venta: " . $sql_venta . "\n";
        
        $stmt_venta = $pdo->prepare($sql_venta);
        
        // Bind parameters
        $params = [
            $codigo_venta,
            $cliente_id,
            $usuario_id,
            $metodo_pago_id,
            $subtotal,
            $iva,
            $total,
            $observaciones,
            $moneda_pago,
            $tasa_cambio,
            $monto_bs,
            $monto_usd
        ];
        
        $log_content .= "Parámetros venta: " . print_r($params, true) . "\n";
        
        if (!$stmt_venta->execute($params)) {
            $error = $stmt_venta->errorInfo();
            throw new Exception('Error ejecutando INSERT venta: ' . ($error[2] ?? 'Error desconocido'));
        }
        
        $venta_id = $stmt_venta->fetchColumn();
        $log_content .= "Venta insertada ID: " . $venta_id . "\n";
        
        // 2. INSERTAR DETALLES
        $log_content .= "Insertando detalles de venta...\n";
        foreach ($input['productos'] as $index => $producto) {
            $producto_id = (int) $producto['id'];
            $cantidad = (int) $producto['quantity'];
            
            $log_content .= "  Producto $index: ID=$producto_id, Cant=$cantidad\n";
            
            // Obtener precio del producto
            $stmt_precio = $pdo->prepare("SELECT precio_venta, nombre, stock_actual FROM productos WHERE id = ?");
            $stmt_precio->execute([$producto_id]);
            $producto_db = $stmt_precio->fetch();
            
            if (!$producto_db) {
                throw new Exception("Producto ID $producto_id no existe");
            }
            
            $precio = (float) ($producto['precio_unitario'] ?? $producto_db['precio_venta']);
            $precio_bs = (float) ($producto['precio_unitario_bs'] ?? ($precio * $tasa_cambio));
            $precio_usd = (float) ($producto['precio_unitario_usd'] ?? $precio);
            $subtotal_producto = $precio * $cantidad;
            $subtotal_producto_bs = $precio_bs * $cantidad;
            
            // Verificar stock
            if ($producto_db['stock_actual'] < $cantidad) {
                throw new Exception("Stock insuficiente: {$producto_db['nombre']}. Stock actual: {$producto_db['stock_actual']}");
            }
            
            
            // Insertar detalle
            $sql_detalle = "
                INSERT INTO detalle_ventas (
                    venta_id, producto_id, cantidad, precio_unitario, subtotal, created_at,
                    precio_unitario_bs, precio_unitario_usd
                ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
            ";
            
            $stmt_detalle = $pdo->prepare($sql_detalle);
            $params_detalle = [
                $venta_id,
                $producto_id,
                $cantidad,
                $precio,
                $subtotal_producto,
                $precio_bs,
                $precio_usd
            ];
            
            $log_content .= "  SQL Detalle: " . $sql_detalle . "\n";
            $log_content .= "  Params Detalle: " . print_r($params_detalle, true) . "\n";
            
            if (!$stmt_detalle->execute($params_detalle)) {
                $error = $stmt_detalle->errorInfo();
                throw new Exception('Error insertando detalle: ' . ($error[2] ?? 'Error desconocido'));
            }

            // Actualizar stock del producto luego de registrar el detalle
            $stmt_stock = $pdo->prepare("UPDATE productos SET stock_actual = GREATEST(0, stock_actual - ?) WHERE id = ?");
            $stmt_stock->execute([$cantidad, $producto_id]);

            // Registrar movimiento de inventario (no crítico)
            try {
                $stock_post = (int) $pdo->query("SELECT stock_actual FROM productos WHERE id = {$producto_id}")->fetchColumn();
                $stmt_mov = $pdo->prepare("INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, motivo, referencia, usuario_id, created_at)
                    VALUES (?, 'SALIDA', ?, ?, ?, ?, ?, ?, NOW())");
                $stmt_mov->execute([
                    $producto_id,
                    $cantidad,
                    $producto_db['stock_actual'],
                    $stock_post,
                    "Venta {$codigo_venta}",
                    $venta_id,
                    $usuario_id
                ]);
            } catch (Exception $e) {
                $log_content .= "Movimiento inventario falló (no crítico): " . $e->getMessage() . "\n";
            }
        }
        
        // 3. COMMIT
        $pdo->commit();
        $log_content .= "Transacción COMMIT exitoso\n";
        
        // 4. Respuesta exitosa
        $response = [
            'success' => true,
            'message' => 'Venta registrada exitosamente',
            'codigo_venta' => $codigo_venta,
            'venta_id' => $venta_id,
            'total' => $total,
            'productos_count' => count($input['productos'])
        ];
        
        $log_content .= "Respuesta: " . json_encode($response) . "\n";
        $log_content .= "=== VENTA EXITOSA ===\n\n";
        
        // Guardar log y enviar respuesta
        file_put_contents($log_file, $log_content, FILE_APPEND);
        echo json_encode($response);
        
    } catch (Exception $e) {
        // ROLLBACK si hay error
        $pdo->rollBack();
        $log_content .= "ERROR en transacción: " . $e->getMessage() . "\n";
        $log_content .= "ROLLBACK ejecutado\n";
        file_put_contents($log_file, $log_content, FILE_APPEND);
        throw $e;
    }
    
} catch (Exception $e) {
    // Error general
    $log_content .= "ERROR GENERAL: " . $e->getMessage() . "\n";
    $log_content .= "TRACE: " . $e->getTraceAsString() . "\n";
    $log_content .= "=== PROCESO FALLIDO ===\n\n";
    
    file_put_contents($log_file, $log_content, FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>