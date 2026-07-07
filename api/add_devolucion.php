<?php
session_start();
require_once __DIR__ . '/../app/models/database.php';
header('Content-Type: application/json');

function respondError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(array('success' => false, 'error' => $message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondError('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$venta_id = isset($input['venta_id']) ? intval($input['venta_id']) : null;
$pedido_id = isset($input['pedido_id']) ? intval($input['pedido_id']) : null;
$items = $input['items'] ?? array();
$motivo = trim($input['motivo'] ?? '');
$descripcion = trim($input['descripcion'] ?? '');

if ((!$venta_id && !$pedido_id) || empty($items) || !$motivo) {
    respondError('Parámetros inválidos');
}

$pdo = Database::getInstance();
if (!$pdo) {
    respondError('DB connection failed', 500);
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    respondError('No autenticado', 401);
}

try {
    // Verificar si el usuario es admin (rol_id = 1 es Administrador)
    $stmt = $pdo->prepare('SELECT u.rol_id, r.nombre as rol_nombre FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id WHERE u.id = :uid LIMIT 1');
    $stmt->execute(array(':uid' => $user_id));
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($usuario && ($usuario['rol_id'] == 1 || strtolower($usuario['rol_nombre']) === 'administrador'));
    
    $cliente_id = null;
    
    if (!$isAdmin) {
        // Cliente: obtener su ID
        $stmt = $pdo->prepare('SELECT id FROM clientes WHERE usuario_id = :uid LIMIT 1');
        $stmt->execute(array(':uid' => $user_id));
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
            respondError('Cliente no encontrado', 403);
        }
        $cliente_id = $cliente['id'];
    }
    
    // Determinar tipo de orden
    $orderType = $venta_id ? 'venta' : 'pedido';
    
    // Verificar que la venta/pedido existe y obtener cliente_id
    if ($orderType === 'venta') {
        $stmt = $pdo->prepare('SELECT cliente_id FROM ventas WHERE id = :vid LIMIT 1');
        $stmt->execute(array(':vid' => $venta_id));
        $orden = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orden) {
            respondError('La venta no existe', 404);
        }
        if (!$isAdmin && $orden['cliente_id'] != $cliente_id) {
            respondError('La venta no pertenece al cliente', 403);
        }
        if ($isAdmin && !$cliente_id) {
            $cliente_id = $orden['cliente_id'];
        }
    } else {
        $stmt = $pdo->prepare('SELECT cliente_id FROM pedidos_online WHERE id = :pid LIMIT 1');
        $stmt->execute(array(':pid' => $pedido_id));
        $orden = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orden) {
            respondError('El pedido no existe', 404);
        }
        if (!$isAdmin && $orden['cliente_id'] != $cliente_id) {
            respondError('El pedido no pertenece al cliente', 403);
        }
        if ($isAdmin && !$cliente_id) {
            $cliente_id = $orden['cliente_id'];
        }
    }
    
    if (!$cliente_id) {
        respondError('No se pudo determinar el cliente', 400);
    }
    
    $pdo->beginTransaction();
    $created = array();
    
    foreach ($items as $it) {
        $prod_id = intval($it['producto_id'] ?? 0);
        $cantidad_solicitada = max(1, intval($it['cantidad'] ?? 1));
        if (!$prod_id) continue;
        
        // Verificar que el producto esté en la venta/pedido y calcular disponible
        if ($orderType === 'venta') {
            $check = $pdo->prepare('
                SELECT dv.cantidad, COALESCE((SELECT SUM(d2.cantidad) FROM devoluciones d2 WHERE d2.venta_id = dv.venta_id AND d2.producto_id = dv.producto_id AND d2.estado_devolucion IN (\'PENDIENTE\', \'APROBADO\')), 0) as devuelto
                FROM detalle_ventas dv
                WHERE dv.venta_id = :vid AND dv.producto_id = :pid');
            $check->execute(array(':vid' => $venta_id, ':pid' => $prod_id));
        } else {
            $check = $pdo->prepare('
                SELECT dp.cantidad, COALESCE((SELECT SUM(d2.cantidad) FROM devoluciones d2 WHERE d2.pedido_id = dp.pedido_id AND d2.producto_id = dp.producto_id AND d2.estado_devolucion IN (\'PENDIENTE\', \'APROBADO\')), 0) as devuelto
                FROM detalle_pedidos_online dp
                WHERE dp.pedido_id = :pid AND dp.producto_id = :pid_prod');
            $check->execute(array(':pid' => $pedido_id, ':pid_prod' => $prod_id));
        }
        
        $r = $check->fetch(PDO::FETCH_ASSOC);
        if (!$r) continue;
        
        $cantidad_comprada = intval($r['cantidad']);
        $cantidad_devuelta = intval($r['devuelto']);
        $cantidad_disponible = $cantidad_comprada - $cantidad_devuelta;
        
        if ($cantidad_disponible <= 0) {
            throw new Exception('No hay cantidad disponible para devolución del producto seleccionado');
        }
        if ($cantidad_solicitada > $cantidad_disponible) {
            throw new Exception('La cantidad solicitada (' . $cantidad_solicitada . ') excede la disponible (' . $cantidad_disponible . ')');
        }
        
        $ins = $pdo->prepare('
            INSERT INTO devoluciones 
                (codigo_devolucion, cliente_id, venta_id, pedido_id, producto_id, motivo, observaciones, estado_devolucion, cantidad, created_at, updated_at) 
            VALUES 
                (:codigo, :cliente_id, :venta_id, :pedido_id, :producto_id, :motivo, :observaciones, :estado, :cantidad, NOW(), NOW()) 
            RETURNING id');
        
        $ins->execute(array(
            ':codigo' => '',
            ':cliente_id' => $cliente_id,
            ':venta_id' => $orderType === 'venta' ? $venta_id : null,
            ':pedido_id' => $orderType === 'pedido' ? $pedido_id : null,
            ':producto_id' => $prod_id,
            ':motivo' => $motivo,
            ':observaciones' => $descripcion,
            ':estado' => 'PENDIENTE',
            ':cantidad' => $cantidad_solicitada
        ));
        
        $new = $ins->fetch(PDO::FETCH_ASSOC);
        $newId = $new['id'] ?? null;
        
        if ($newId) {
            $codigo = 'DEV-' . date('Ymd') . '-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
            $upd = $pdo->prepare('UPDATE devoluciones SET codigo_devolucion = :codigo WHERE id = :id');
            $upd->execute(array(':codigo' => $codigo, ':id' => $newId));
            
            $created[] = array(
                'id' => $newId,
                'codigo' => $codigo,
                'producto_id' => $prod_id,
                'cantidad' => $cantidad_solicitada
            );
        }
    }
    
    if (empty($created)) {
        $pdo->rollBack();
        respondError('No se creó ninguna devolución. Verifique los productos seleccionados.');
    }
    
    $pdo->commit();
    echo json_encode(array('success' => true, 'devoluciones' => $created, 'message' => 'Solicitud de devolución creada exitosamente'));
    exit;
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('add_devolucion error: ' . $e->getMessage());
    respondError($e->getMessage(), 500);
}
?>
