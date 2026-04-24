<?php
// /inversiones-rojas/api/get_factura.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado. Por favor inicie sesión.']);
    exit();
}

// Incluir la conexión a la base de datos
$database_path = __DIR__ . '/../app/models/database.php';
if (!file_exists($database_path)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la configuración del sistema: Archivo de base de datos no encontrado.']);
    exit();
}

require_once $database_path;

try {
    // Obtener el ID de la venta
    $venta_id = 0;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $data = json_decode($input, true);
            if (isset($data['venta_id'])) {
                $venta_id = intval($data['venta_id']);
            }
        }
        
        if ($venta_id === 0 && isset($_POST['venta_id'])) {
            $venta_id = intval($_POST['venta_id']);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['venta_id'])) {
        $venta_id = intval($_GET['venta_id']);
    }

    if ($venta_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de venta no válido o no proporcionado.']);
        exit();
    }

    // Obtener instancia de la base de datos
    $pdo = Database::getInstance();
    
    // Obtener información de la venta
    $stmt = $pdo->prepare("
        SELECT 
            v.id,
            v.codigo_venta,
            v.cliente_id,
            v.usuario_id,
            v.metodo_pago_id,
            v.subtotal,
            v.iva,
            v.total,
            v.estado_venta,
            v.observaciones,
            v.created_at,
            v.tasa_cambio,
            v.monto_bs,
            c.nombre_completo as cliente_nombre,
            c.cedula_rif as cliente_cedula,
            c.telefono_principal as cliente_telefono,
            c.direccion as cliente_direccion,
            mp.nombre as metodo_pago_nombre,
            u.nombre_completo as vendedor
        FROM ventas v
        LEFT JOIN clientes c ON v.cliente_id = c.id
        LEFT JOIN metodos_pago mp ON v.metodo_pago_id = mp.id
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.id = ?
    ");
    
    if (!$stmt->execute([$venta_id])) {
        throw new Exception('Error al ejecutar la consulta de venta');
    }
    
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$venta) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Venta no encontrada con ID: ' . $venta_id]);
        exit();
    }
    
    // Obtener detalles de la venta
    $stmt = $pdo->prepare("
        SELECT 
            dv.id,
            dv.producto_id,
            dv.cantidad,
            dv.precio_unitario,
            dv.subtotal as detalle_subtotal,
            p.codigo_interno as producto_codigo,
            p.nombre as producto_nombre,
            p.descripcion as producto_descripcion
        FROM detalle_ventas dv
        JOIN productos p ON dv.producto_id = p.id
        WHERE dv.venta_id = ?
        ORDER BY dv.id
    ");
    
    if (!$stmt->execute([$venta_id])) {
        throw new Exception('Error al ejecutar la consulta de detalles');
    }
    
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener tasa de cambio de la venta o tasa vigente
    $tasa = !empty($venta['tasa_cambio']) ? floatval($venta['tasa_cambio']) : 35.50;
    
    // Preparar respuesta con valores en Bs convertidos
    $response = [
        'success' => true,
        'message' => 'Factura obtenida exitosamente',
        'factura' => [
            'id' => $venta['id'],
            'codigo_venta' => $venta['codigo_venta'],
            'cliente_id' => $venta['cliente_id'],
            'cliente_nombre' => $venta['cliente_nombre'],
            'cliente_cedula' => $venta['cliente_cedula'],
            'cliente_telefono' => $venta['cliente_telefono'],
            'cliente_direccion' => $venta['cliente_direccion'],
            'vendedor' => $venta['vendedor'],
            'metodo_pago_nombre' => $venta['metodo_pago_nombre'],
            // Valores en USD (original)
            'subtotal' => floatval($venta['subtotal']),
            'iva' => floatval($venta['iva']),
            'total' => floatval($venta['total']),
            // Tasa de cambio usada
            'tasa_cambio' => $tasa,
            // Valores convertidos a Bs
            'subtotal_bs' => floatval($venta['subtotal']) * $tasa,
            'iva_bs' => floatval($venta['iva']) * $tasa,
            'total_bs' => !empty($venta['monto_bs']) ? floatval($venta['monto_bs']) : (floatval($venta['total']) * $tasa),
            'estado_venta' => $venta['estado_venta'],
            'observaciones' => $venta['observaciones'],
            'created_at' => $venta['created_at'],
            'detalles' => array_map(function($detalle) use ($tasa) {
                return [
                    'id' => $detalle['id'],
                    'producto_id' => $detalle['producto_id'],
                    'producto_codigo' => $detalle['producto_codigo'],
                    'producto_nombre' => $detalle['producto_nombre'],
                    'cantidad' => intval($detalle['cantidad']),
                    // Precio en USD
                    'precio_unitario' => floatval($detalle['precio_unitario']),
                    'subtotal' => floatval($detalle['detalle_subtotal']),
                    // Precio convertido a Bs
                    'precio_unitario_bs' => floatval($detalle['precio_unitario']) * $tasa,
                    'subtotal_bs' => floatval($detalle['detalle_subtotal']) * $tasa
                ];
            }, $detalles)
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Error en get_factura.php: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>