<?php
// ¡ASEGÚRATE DE QUE NO HAY ESPACIOS ANTES DE ESTA LÍNEA!
session_start();
require_once __DIR__ . '/../app/models/database.php';
header('Content-Type: application/json');

// Función para responder con error
function respondError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondError('Method not allowed', 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$venta_id = isset($input['venta_id']) ? intval($input['venta_id']) : null;
$pedido_id = isset($input['pedido_id']) ? intval($input['pedido_id']) : null;
$items = $input['items'] ?? [];
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
    // Obtener cliente asociado al usuario
    $stmt = $pdo->prepare('SELECT id FROM clientes WHERE usuario_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $user_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cliente) {
        respondError('Cliente no encontrado', 403);
    }
    $cliente_id = $cliente['id'];

    // Verificar que la venta o pedido pertenece a este cliente
    $orderType = $venta_id ? 'venta' : 'pedido';

    if ($orderType === 'venta') {
        $stmt = $pdo->prepare('SELECT id FROM ventas WHERE id = :vid AND cliente_id = :cid LIMIT 1');
        $stmt->execute([':vid' => $venta_id, ':cid' => $cliente_id]);
        $v = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$v) {
            respondError('La venta no pertenece al cliente', 403);
        }
    } else {
        $stmt = $pdo->prepare('SELECT id FROM pedidos_online WHERE id = :pid AND cliente_id = :cid LIMIT 1');
        $stmt->execute([':pid' => $pedido_id, ':cid' => $cliente_id]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) {
            respondError('El pedido no pertenece al cliente', 403);
        }
    }

    $pdo->beginTransaction();
    $created = [];

    foreach ($items as $it) {
        $prod_id = intval($it['producto_id'] ?? 0);
        $cantidad_solicitada = max(1, intval($it['cantidad'] ?? 1));
        if (!$prod_id) continue;

// Verificar que el producto esté en la venta/pedido y calcular disponible
        if ($orderType === 'venta') {
            $check = $pdo->prepare('
                SELECT 
                    dv.cantidad,
                    COALESCE((
                        SELECT SUM(d2.cantidad) 
                        FROM devoluciones d2 
                        WHERE d2.venta_id = dv.venta_id 
                        AND d2.producto_id = dv.producto_id 
                        AND d2.estado_devolucion IN (\'PENDIENTE\', \'APROBADO\')
                    ), 0) as devuelto
                FROM detalle_ventas dv
                WHERE dv.venta_id = :vid 
                AND dv.producto_id = :pid
            ');
            $check->execute([
                ':vid' => $venta_id, 
                ':pid' => $prod_id
            ]);
        } else {
            $check = $pdo->prepare('
                SELECT 
                    dp.cantidad,
                    COALESCE((
                        SELECT SUM(d2.cantidad) 
                        FROM devoluciones d2 
                        WHERE d2.pedido_id = dp.pedido_id 
                        AND d2.producto_id = dp.producto_id 
                        AND d2.estado_devolucion IN (\'PENDIENTE\', \'APROBADO\')
                    ), 0) as devuelto
                FROM detalle_pedidos_online dp
                WHERE dp.pedido_id = :pid 
                AND dp.producto_id = :pid_prod
            ');
            $check->execute([
                ':pid' => $pedido_id, 
                ':pid_prod' => $prod_id
            ]);
        }

        $r = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$r) {
            continue; // Producto no pertenece a la venta/pedido
        }
        
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
            RETURNING id
        ');
        
        $ins->execute([
            ':codigo' => '', // Se generará después
            ':cliente_id' => $cliente_id,
            ':venta_id' => $orderType === 'venta' ? $venta_id : null,
            ':pedido_id' => $orderType === 'pedido' ? $pedido_id : null,
            ':producto_id' => $prod_id,
            ':motivo' => $motivo,
            ':observaciones' => $descripcion,
            ':estado' => 'PENDIENTE',
            ':cantidad' => $cantidad_solicitada
        ]);
        
        $new = $ins->fetch(PDO::FETCH_ASSOC);
        $newId = $new['id'] ?? null;
        
        if ($newId) {
            $codigo = 'DEV-' . date('Ymd') . '-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
            $upd = $pdo->prepare('UPDATE devoluciones SET codigo_devolucion = :codigo WHERE id = :id');
            $upd->execute([':codigo' => $codigo, ':id' => $newId]);

            $created[] = [
                'id' => $newId, 
                'codigo' => $codigo, 
                'producto_id' => $prod_id,
                'cantidad' => $cantidad_solicitada
            ];
        }
    }

    if (empty($created)) {
        $pdo->rollBack();
        respondError('No se creó ninguna devolución. Verifique los productos seleccionados.');
    }

    $pdo->commit();
    echo json_encode([
        'success' => true, 
        'devoluciones' => $created,
        'message' => 'Solicitud de devolución creada exitosamente'
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('add_devolucion error: ' . $e->getMessage());
    respondError($e->getMessage(), 500);
}
?>