<?php
// Desactivar display_errors para evitar que HTML se mezcle con JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/reserva_mail_helper.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado. Debe iniciar sesión.']);
    exit;
}

// Obtener datos de la solicitud (JSON o POST/multipart)
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        $jsonInput = json_decode($raw_input, true);
        if (is_array($jsonInput)) {
            $input = $jsonInput;
        }
    }
}

$codigo_reserva = $input['codigo_reserva'] ?? null;
$metodo_pago_id = $input['metodo_pago_id'] ?? null;
$referencia_pago = trim($input['referencia_pago'] ?? '');

if (!$codigo_reserva) {
    echo json_encode(['success' => false, 'message' => 'Código de reserva requerido']);
    exit;
}

// Validar método de pago (si no se proporciona, usar efectivo por defecto)
if (!$metodo_pago_id) {
    // Buscar método de pago por defecto (Efectivo)
    try {
        $pdo_temp = Database::getInstance();
        $stmt = $pdo_temp->prepare("SELECT id FROM metodos_pago WHERE nombre ILIKE '%efectivo%' AND estado = true LIMIT 1");
        $stmt->execute();
        $metodo_default = $stmt->fetch(PDO::FETCH_ASSOC);
        $metodo_pago_id = $metodo_default ? $metodo_default['id'] : 1; // Fallback a ID 1
    } catch (Exception $e) {
        $metodo_pago_id = 1; // Fallback
    }
}

try {
    $pdo = Database::getInstance();
    
    if (!$pdo) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    
    $pdo->beginTransaction();

    // ============================================
    // 1. Obtener TODOS los items de la reserva
    // ============================================
    $stmt = $pdo->prepare("
        SELECT 
            r.id as reserva_id,
            r.codigo_reserva,
            r.cliente_id,
            r.producto_id,
            r.cantidad,
            ROUND(r.cantidad * p.precio_venta, 2) as subtotal_linea,
            ROUND(r.cantidad * p.precio_venta * 0.16, 2) as iva_linea,
            ROUND(r.cantidad * p.precio_venta * 1.16, 2) as total_linea,
            p.nombre as producto_nombre,
            p.precio_venta,
            p.stock_actual,
            p.codigo_interno,
            p.stock_actual + COALESCE(p.stock_reservado, 0) as stock_total
        FROM reservas r
        INNER JOIN productos p ON r.producto_id = p.id
        WHERE r.codigo_reserva = ? AND r.estado_reserva IN ('PENDIENTE', 'PRORROGADA')
        ORDER BY r.id
    ");
    
    $stmt->execute([$codigo_reserva]);
    $items_reserva = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items_reserva)) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'message' => 'Reserva no encontrada o no está activa',
            'codigo' => $codigo_reserva
        ]);
        exit;
    }

    // ============================================
    // 2. Calcular totales generales de la reserva
    // ============================================
    $cliente_id = $items_reserva[0]['cliente_id'];
    $subtotal_total = 0;
    $iva_total = 0;
    $total_total = 0;
    
    foreach ($items_reserva as $item) {
        $subtotal_total += round((float)$item['subtotal_linea'], 2);
        $iva_total += round((float)$item['iva_linea'], 2);
        $total_total += round((float)$item['total_linea'], 2);
    }
    
    // Redondear totales finales
    $subtotal_total = round($subtotal_total, 2);
    $iva_total = round($iva_total, 2);
    $total_total = round($total_total, 2);

    // ============================================
    // 3. Verificar stock para TODOS los productos
    // ============================================
    $errores_stock = [];
    foreach ($items_reserva as $item) {
        if ((int)$item['cantidad'] > (int)$item['stock_actual']) {
            $errores_stock[] = "{$item['producto_nombre']} (Stock: {$item['stock_actual']}, Solicitado: {$item['cantidad']})";
        }
    }
    
    if (!empty($errores_stock)) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Stock insuficiente para los siguientes productos:',
            'errores' => $errores_stock
        ]);
        exit;
    }

    // ============================================
    // 4. Generar código de venta
    // ============================================
    $codigo_venta = 'VEN-' . date('Ymd') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    
    // Verificar que el código no exista
    $stmt_check = $pdo->prepare("SELECT id FROM ventas WHERE codigo_venta = ?");
    $stmt_check->execute([$codigo_venta]);
    while ($stmt_check->fetch()) {
        // Si ya existe, generar otro
        $codigo_venta = 'VEN-' . date('Ymd') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $stmt_check->execute([$codigo_venta]);
    }

    // ============================================
    // 5. Manejar archivos de comprobante y crear la venta
    // ============================================
    $comprobante_urls = [];
    if (!empty($_FILES['comprobante'])) {
        $uploadDir = dirname(__DIR__) . '/public/uploads/reservas_comprobantes/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new Exception('No se pudo crear el directorio de comprobantes');
        }

        $files = $_FILES['comprobante'];
        $count = is_array($files['name']) ? count($files['name']) : 0;

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            if (!is_uploaded_file($files['tmp_name'][$i])) {
                continue;
            }

            $originalName = pathinfo($files['name'][$i], PATHINFO_FILENAME);
            $extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
            $filename = uniqid('comprobante_', true) . '_' . $safeName . '.' . strtolower($extension);
            $destination = $uploadDir . $filename;

            if (move_uploaded_file($files['tmp_name'][$i], $destination)) {
                $comprobante_urls[] = '/public/uploads/reservas_comprobantes/' . $filename;
            }
        }
    }

    $observaciones = "Venta generada desde reserva: $codigo_reserva";
    if (!empty($referencia_pago)) {
        $observaciones .= " | Referencia: {$referencia_pago}";
    }
    if (!empty($comprobante_urls)) {
        $observaciones .= " | Comprobantes: " . implode(', ', $comprobante_urls);
    }
    
    $stmt_venta = $pdo->prepare("
        INSERT INTO ventas (
            codigo_venta, 
            cliente_id, 
            usuario_id, 
            metodo_pago_id, 
            subtotal, 
            iva, 
            total, 
            estado_venta, 
            observaciones
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'COMPLETADA', ?)
        RETURNING id
    ");
    
    $stmt_venta->execute([
        $codigo_venta,
        $cliente_id,
        $_SESSION['user_id'],
        $metodo_pago_id,
        $subtotal_total,
        $iva_total,
        $total_total,
        $observaciones
    ]);
    
    $venta_id = $stmt_venta->fetchColumn();

    if (!$venta_id) {
        throw new Exception('No se pudo crear la venta');
    }

    // ============================================
    // 6. Insertar detalles de venta para CADA producto
    // ============================================
    $stmt_detalle = $pdo->prepare("
        INSERT INTO detalle_ventas (
            venta_id, 
            producto_id, 
            cantidad, 
            precio_unitario, 
            subtotal, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");

    // NO se resta stock aqui - ya se restó al crear la reserva

    $stmt_movimiento = $pdo->prepare("
        INSERT INTO movimientos_inventario (
            producto_id, 
            tipo_movimiento, 
            cantidad, 
            stock_anterior, 
            stock_actual, 
            motivo, 
            usuario_id, 
            created_at
        ) VALUES (?, 'SALIDA', ?, ?, ?, ?, ?, NOW())
    ");

    $detalles_venta = [];
    
    foreach ($items_reserva as $item) {
        $cantidad = (int)$item['cantidad'];
        $precio_unitario = (float)$item['precio_venta'];
        $subtotal_detalle = round($precio_unitario * $cantidad, 2);
        
        // Insertar detalle
        $stmt_detalle->execute([
            $venta_id,
            $item['producto_id'],
            $cantidad,
            $precio_unitario,
            $subtotal_detalle
        ]);
        
        // Guardar para respuesta
        $detalles_venta[] = [
            'producto' => $item['producto_nombre'],
            'cantidad' => $cantidad,
            'precio' => $precio_unitario,
            'subtotal' => $subtotal_detalle
        ];
        
        // Obtener stock actual antes de actualizar
        $stock_anterior = (int)$item['stock_actual'];
        // Stockya fue reducido al crear la reserva (PENDIENTE), no reducir aqui
        $stock_nuevo = $stock_anterior; // Sin cambio, ya se restó al crear
        
        // Registrar movimiento de inventario
        $stmt_movimiento->execute([
            $item['producto_id'],
            $cantidad,
            $stock_anterior,
            $stock_nuevo,
            'Venta desde reserva #' . $codigo_reserva,
            $_SESSION['user_id']
        ]);
    }

// ============================================
    // 7. Marcar TODAS las líneas de la reserva como completadas
    // ============================================
    // Obtener nombre del método de pago de la tabla normal (metodos_pago) para el resto
    $metodo_pago_resto = '';
    if ($metodo_pago_id) {
        $stmt_metodo = $pdo->prepare("SELECT nombre FROM metodos_pago WHERE id = ?");
        $stmt_metodo->execute([$metodo_pago_id]);
        $metodo_data = $stmt_metodo->fetch(PDO::FETCH_ASSOC);
        if ($metodo_data) {
            $metodo_pago_resto = $metodo_data['nombre'];
        }
    }
    
    // Obtener el monto restante
    $stmt_monto = $pdo->prepare("SELECT monto_restante FROM reservas WHERE codigo_reserva = ? LIMIT 1");
    $stmt_monto->execute([$codigo_reserva]);
    $monto_resto = $stmt_monto->fetch(PDO::FETCH_ASSOC);
    $monto_pagado_resto = $monto_resto ? (float)$monto_resto['monto_restante'] : 0;
    
    $stmt_update_reserva = $pdo->prepare("
        UPDATE reservas 
        SET estado_reserva = 'COMPLETADA', 
            metodo_pago_resto = ?,
            referencia_pago_resto = ?,
            monto_pagado_resto = ?,
            fecha_pago_resto = NOW(),
            updated_at = NOW() 
        WHERE codigo_reserva = ?
    ");
    $stmt_update_reserva->execute([$metodo_pago_resto, $referencia_pago, $monto_pagado_resto, $codigo_reserva]);
    
    // Guardar URL del comprobante del resto si existe
    if (!empty($comprobante_urls)) {
        $comprobante_resto = implode(',', $comprobante_urls);
        $stmt_comprobante = $pdo->prepare("UPDATE reservas SET comprobante_url_resto = ? WHERE codigo_reserva = ?");
        $stmt_comprobante->execute([$comprobante_resto, $codigo_reserva]);
    }

    // ============================================
    // 8. Commit de la transacción
    // ============================================
    $pdo->commit();

    // ============================================
    // 9. Registrar en bitácora
    // ============================================
    try {
        $stmt_bitacora = $pdo->prepare("
            INSERT INTO bitacora_sistema (
                usuario_id, 
                accion, 
                tabla_afectada, 
                registro_id, 
                detalles, 
                created_at
            ) VALUES (?, 'COMPLETAR_RESERVA', 'ventas', ?, ?::jsonb, NOW())
        ");
        
        $detalles_bitacora = json_encode([
            'codigo_reserva' => $codigo_reserva,
            'codigo_venta' => $codigo_venta,
            'total' => $total_total,
            'productos' => count($items_reserva),
            'metodo_pago_id' => $metodo_pago_id
        ]);
        
        $stmt_bitacora->execute([$_SESSION['user_id'], $venta_id, $detalles_bitacora]);
    } catch (Exception $e) {
        error_log('Error al registrar en bitácora: ' . $e->getMessage());
    }

    // ============================================
    // 10. Enviar correo de aprobación al cliente
    // ============================================
    error_log('[complete_reserva] Iniciando envío de correo de aprobación para ' . $codigo_reserva);
    
    $stmt_cliente = $pdo->prepare("
        SELECT c.nombre_completo, c.email, c.telefono_principal
        FROM reservas r
        INNER JOIN clientes c ON r.cliente_id = c.id
        WHERE r.codigo_reserva = ? LIMIT 1
    ");
    $stmt_cliente->execute([$codigo_reserva]);
    $cliente_data = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
    
    error_log('[complete_reserva] Datos cliente: ' . json_encode($cliente_data));
    
    if ($cliente_data && !empty($cliente_data['email'])) {
        $nombre_completo = $cliente_data['nombre_completo'] ?? 'Cliente';
        $partes = explode(' ', $nombre_completo, 2);
        $nombre = $partes[0] ?? '';
        $apellido = $partes[1] ?? '';
        
        $correo_enviado = enviarCorreoReserva(
            $cliente_data['email'],
            $nombre,
            $apellido,
            'aprobacion',
            $codigo_reserva,
            $items_reserva,
            $total_total,
            $metodo_pago_resto
        );
        error_log('[complete_reserva] Resultado correo: ' . ($correo_enviado ? 'OK' : 'FALLO'));
    } else {
        error_log('[complete_reserva] No se pudo obtener email del cliente para reserva ' . $codigo_reserva);
    }

    // ============================================
    // 11. Respuesta exitosa
    // ============================================
    echo json_encode([
        'success' => true,
        'message' => '✅ Venta generada exitosamente a partir de la reserva',
        'venta_id' => $venta_id,
        'codigo_venta' => $codigo_venta,
        'codigo_reserva' => $codigo_reserva,
        'total' => $total_total,
        'subtotal' => $subtotal_total,
        'iva' => $iva_total,
        'productos' => $detalles_venta,
        'metodo_pago_id' => $metodo_pago_id,
        'comprobantes' => $comprobante_urls
    ]);

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('ERROR en complete_reserva.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la reserva: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
?>