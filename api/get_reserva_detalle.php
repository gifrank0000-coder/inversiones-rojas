<?php
// get_reserva_detalle.php - VERSIÓN CORREGIDA (Sin r.subtotal)
header('Content-Type: application/json; charset=utf-8');

// Desactivar mostrar errores en pantalla
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Incluir conexión a base de datos
require_once __DIR__ . '/../app/models/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    // Obtener código de la reserva
    $codigo_reserva = trim($_GET['codigo'] ?? '');

    if (empty($codigo_reserva)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Código de reserva requerido']);
        exit;
    }

    // CONSULTA CORREGIDA - Sin r.subtotal
    $sql = "SELECT 
                r.id, 
                r.codigo_reserva, 
                r.cliente_id,
                r.producto_id,
                r.cantidad, 
                r.fecha_reserva, 
                r.fecha_limite, 
                r.estado_reserva, 
                r.observaciones,
                r.monto_adelanto,
                r.monto_restante,
                r.estado_pago,
                r.metodo_pago,
                r.referencia_pago,
                r.comprobante_url,
                r.fecha_cuota,
                r.metodo_pago_resto,
                r.referencia_pago_resto,
                r.comprobante_url_resto,
                r.monto_pagado_resto,
                r.fecha_pago_resto,
                r.subtotal,
                r.iva,
                r.monto_total,
                r.created_at,
                r.updated_at,
                c.nombre_completo AS cliente_nombre, 
                c.email AS cliente_email, 
                c.telefono_principal AS cliente_telefono,
                c.cedula_rif AS cliente_rif_ci,
                p.nombre AS producto_nombre, 
                p.codigo_interno AS producto_codigo,
                p.precio_venta
            FROM reservas r
            LEFT JOIN clientes c ON r.cliente_id = c.id
            LEFT JOIN productos p ON r.producto_id = p.id
            WHERE r.codigo_reserva = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$codigo_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
        exit;
    }

    // Calcular totales - usar valores guardados o calcular si no existen
    $precio = (float)$reserva['precio_venta'];
    $cantidad = (int)$reserva['cantidad'];
    $storedSubtotal = (float)$reserva['subtotal'];
    $storedIva = (float)$reserva['iva'];
    $storedTotal = (float)$reserva['monto_total'];
    
    // Usar valores guardados, o calcular si existen reservas antiguas sin estos valores
    if ($storedSubtotal > 0 && $storedTotal > 0) {
        $subtotal = $storedSubtotal;
        $iva = $storedIva;
        $total = $storedTotal;
    } else {
        $subtotal = $precio * $cantidad;
        $iva = round($subtotal * 0.16, 2);
        $total = round($subtotal + $iva, 2);
    }
    
    // Formatear fechas
    $fecha_reserva = date('d/m/Y', strtotime($reserva['fecha_reserva']));
    $fecha_limite = date('d/m/Y', strtotime($reserva['fecha_limite']));
    
    // Estructura de respuesta
    $respuesta = [
        'success' => true,
        'reserva' => [
            'id' => $reserva['id'],
            'codigo_reserva' => $reserva['codigo_reserva'],
            'cliente_id' => $reserva['cliente_id'],
            'producto_id' => $reserva['producto_id'],
            'cantidad' => $reserva['cantidad'],
            'fecha_reserva' => $reserva['fecha_reserva'],
            'fecha_limite' => $reserva['fecha_limite'],
            'estado_reserva' => $reserva['estado_reserva'],
            'observaciones' => $reserva['observaciones'],
            'fecha_reserva_formateada' => $fecha_reserva,
            'fecha_limite_formateada' => $fecha_limite,
            'precio_unitario' => $precio,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
            'monto_adelanto' => (float)$reserva['monto_adelanto'],
            'monto_restante' => (float)$reserva['monto_restante'],
            'estado_pago' => $reserva['estado_pago'],
            'metodo_pago' => $reserva['metodo_pago'],
            'referencia_pago' => $reserva['referencia_pago'],
            'comprobante_url' => $reserva['comprobante_url'],
            'fecha_cuota' => $reserva['fecha_cuota'],
            'metodo_pago_resto' => $reserva['metodo_pago_resto'],
            'referencia_pago_resto' => $reserva['referencia_pago_resto'],
            'comprobante_url_resto' => $reserva['comprobante_url_resto'],
            'monto_pagado_resto' => (float)$reserva['monto_pagado_resto'],
            'fecha_pago_resto' => $reserva['fecha_pago_resto'],
            'subtotal' => (float)$reserva['subtotal'],
            'iva' => (float)$reserva['iva'],
            'total' => (float)$reserva['monto_total']
        ],
        'cliente' => [
            'id' => $reserva['cliente_id'],
            'nombre_completo' => $reserva['cliente_nombre'],
            'email' => $reserva['cliente_email'],
            'telefono_principal' => $reserva['cliente_telefono'],
            'cedula_rif' => $reserva['cliente_rif_ci']
        ],
        'producto' => [
            'id' => $reserva['producto_id'],
            'nombre' => $reserva['producto_nombre'],
            'codigo_interno' => $reserva['producto_codigo'],
            'precio_venta' => $precio
        ]
    ];
    
    echo json_encode($respuesta);

} catch (Exception $e) {
    error_log('ERROR en get_reserva_detalle: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}
?>