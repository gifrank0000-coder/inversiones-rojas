<?php

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/permissions.php';
require_once __DIR__ . '/helpers/whatsapp.php';

// Sólo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$pedido_id = isset($input['pedido_id']) ? (int)$input['pedido_id'] : 0;
$action    = trim($input['action'] ?? '');
$notes     = trim($input['notes'] ?? '');
$paymentReference = trim($input['payment_reference'] ?? '');
$metodo_pago_id = isset($input['metodo_pago_id']) ? (int)$input['metodo_pago_id'] : null;
$seller_id = isset($input['seller_id']) ? (int)$input['seller_id'] : null;

if (!$pedido_id || $action === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'pedido_id y action son requeridos']);
    exit;
}

// Validar acción permitida (no roles necesarios: la ventana ya es accesible sólo para quienes deben verla)
$allowedActions = [
    'upload_proof',
    'approve',
    'inhabilitar',
    'reactivar',
    'toggle_active',
    'assign_seller'
];

if (!in_array($action, $allowedActions, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acción no soportada']);
    exit;
}

// Definir transiciones válidas
$transitions = [
    'PENDIENTE' => [
        'upload_proof' => 'EN_VERIFICACION',
        'inhabilitar'  => 'INHABILITADO',
    ],
    'EN_VERIFICACION' => [
        'approve'      => 'CONFIRMADO',
        'inhabilitar'  => 'INHABILITADO',
    ],
    'CONFIRMADO' => [],
    'INHABILITADO' => [
        'reactivar' => 'PENDIENTE',
    ],
];

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener pedido
    $stmt = $conn->prepare('SELECT id, codigo_pedido, cliente_id, estado_pedido FROM pedidos_online WHERE id = ?');
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }

    $currentState = strtoupper($pedido['estado_pedido'] ?? 'PENDIENTE');

    // Manejar asignación de vendedor por separado
    if ($action === 'assign_seller') {
        if (!$seller_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'seller_id es requerido para asignar vendedor']);
            exit;
        }
        // Actualizar vendedor asignado
        $upd = $conn->prepare('UPDATE pedidos_online SET vendedor_asignado_id = ?, fecha_asignacion = NOW(), updated_at = NOW() WHERE id = ?');
        $upd->execute([$seller_id, $pedido_id]);

        // Insertar notificación para el vendedor
        $notifStmt = $conn->prepare('INSERT INTO notificaciones_vendedor (pedido_id, titulo, mensaje, tipo, usuario_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $notifStmt->execute([
            $pedido_id,
            'Nuevo pedido asignado',
            'Se te ha asignado el pedido ' . $pedido['codigo_pedido'],
            'PEDIDO_ASIGNADO',
            $seller_id
        ]);

        // Bitácora
        $bitStmt = $conn->prepare('INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at) VALUES (?, ?, ?, ?, ?::jsonb, NOW())');
        $bitStmt->execute([
            $_SESSION['user_id'],
            'PEDIDO_ASIGNAR_VENDEDOR',
            'pedidos_online',
            $pedido_id,
            json_encode(['seller_id' => $seller_id])
        ]);

        echo json_encode(['success' => true, 'message' => 'Vendedor asignado correctamente']);
        exit;
    }

    // Acciones de habilitar/inhabilitar (toggle) siempre deben poder ejecutarse
    $isToggleActive = $action === 'toggle_active';
    if ($isToggleActive) {
        // Al inhabilitar marcamos como INHABILITADO, al habilitar volvemos a PENDIENTE (flujo inicial)
        $nextState = in_array($currentState, ['INHABILITADO', 'CANCELADO', 'RECHAZADO'], true) ? 'PENDIENTE' : 'INHABILITADO';
    } else {
        if (!isset($transitions[$currentState][$action])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "No se puede ejecutar '{$action}' desde el estado '{$currentState}'"]);
            exit;
        }
        $nextState = $transitions[$currentState][$action];
    }

    // Si el pedido ya está finalizado, bloquear cambios (salvo reactivar/habilitar desde INHABILITADO)
    if ($currentState === 'CONFIRMADO') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Este pedido ya está finalizado y no puede modificarse.']);
        exit;
    }

    if ($currentState === 'INHABILITADO' && !in_array($action, ['reactivar'], true) && !$isToggleActive) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Este pedido está inhabilitado y debe reactivarse para cambios.']);
        exit;
    }

    // Preparar detalles para notificaciones
    $stmt = $conn->prepare(
        'SELECT d.producto_id, d.cantidad, d.precio_unitario, p.nombre
         FROM detalle_pedidos_online d
         JOIN productos p ON p.id = d.producto_id
         WHERE d.pedido_id = ?'
    );
    $stmt->execute([$pedido_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare('SELECT nombre_completo, email, telefono_principal FROM clientes WHERE id = ?');
    $stmt->execute([$pedido['cliente_id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ejecutar transición dentro de transacción
    $conn->beginTransaction();

    // Ajustes de stock para algunas transiciones
    if ($action === 'approve') {
        // Restar stock al confirmar pedido
        foreach ($productos as $prod) {
            $pid = (int)$prod['producto_id'];
            $cantidad = (int)$prod['cantidad'];

            $stockStmt = $conn->prepare('SELECT stock_actual FROM productos WHERE id = ? FOR UPDATE');
            $stockStmt->execute([$pid]);
            $stockRow = $stockStmt->fetch(PDO::FETCH_ASSOC);
            $stockActual = $stockRow ? (int)$stockRow['stock_actual'] : 0;

            if ($stockActual < $cantidad) {
                $conn->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Stock insuficiente para {$prod['nombre']}. Disponible: {$stockActual}"]);
                exit;
            }

            $newStock = $stockActual - $cantidad;
            $upd = $conn->prepare('UPDATE productos SET stock_actual = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$newStock, $pid]);

            $movStmt = $conn->prepare('INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, motivo, referencia, usuario_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $movStmt->execute([
                $pid,
                'SALIDA_PEDIDO',
                $cantidad,
                $stockActual,
                $newStock,
                'Confirmación de pedido',
                'PEDIDO:' . $pedido['codigo_pedido'],
                $_SESSION['user_id']
            ]);
        }
    }

    $isInhabilitarFlow = ($action === 'inhabilitar' || ($isToggleActive && $nextState === 'INHABILITADO'));
    if ($isInhabilitarFlow && in_array($currentState, ['CONFIRMADO', 'EN_PROCESO', 'ENVIADO', 'ENTREGADO'], true)) {
        // Si ya se descontó stock, recuperarlo
        foreach ($productos as $prod) {
            $pid = (int)$prod['producto_id'];
            $cantidad = (int)$prod['cantidad'];

            $stockStmt = $conn->prepare('SELECT stock_actual FROM productos WHERE id = ? FOR UPDATE');
            $stockStmt->execute([$pid]);
            $stockRow = $stockStmt->fetch(PDO::FETCH_ASSOC);
            $stockActual = $stockRow ? (int)$stockRow['stock_actual'] : 0;

            $newStock = $stockActual + $cantidad;
            $upd = $conn->prepare('UPDATE productos SET stock_actual = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$newStock, $pid]);

            $movStmt = $conn->prepare('INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, motivo, referencia, usuario_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $movStmt->execute([
                $pid,
                'ENTRADA_CANCELACION',
                $cantidad,
                $stockActual,
                $newStock,
                'Cancelación/inhabilitación de pedido',
                'PEDIDO:' . $pedido['codigo_pedido'],
                $_SESSION['user_id']
            ]);
        }
    }

    // Actualizar estado
    $updateSql = 'UPDATE pedidos_online SET estado_pedido = ?, updated_at = NOW()';
    $params = [$nextState];

    // Si se confirma, guardar fecha de pago
    if ($nextState === 'CONFIRMADO') {
        $updateSql .= ', fecha_pago = NOW()';
    }

    // Si se pasa método de pago, almacenarlo (si existe la columna)
    if ($metodo_pago_id) {
        $updateSql .= ', metodo_pago_id = ?';
        $params[] = $metodo_pago_id;
    }

    // Si se sube comprobante o se confirma el pago, guardar referencia en observaciones
    if (in_array($action, ['upload_proof', 'approve'], true) && $paymentReference) {
        $updateSql .= ', observaciones = COALESCE(observaciones, \'\') || ?';
        $params[] = ' | Pago: ' . $paymentReference;
    }

    $updateSql .= ' WHERE id = ?';
    $params[] = $pedido_id;

    $upd = $conn->prepare($updateSql);
    $upd->execute($params);

    // Registrar en bitácora
    try {
        $det = [
            'from' => $currentState,
            'to'   => $nextState,
            'action' => $action,
            'notes' => $notes,
        ];
        if ($action === 'upload_proof' && $paymentReference) {
            $det['payment_reference'] = $paymentReference;
        }

        $bitStmt = $conn->prepare(
            'INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?::jsonb, ?, ?, NOW())'
        );
        $bitStmt->execute([
            $_SESSION['user_id'],
            'PEDIDO_ESTADO',
            'pedidos_online',
            $pedido_id,
            json_encode($det),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log('Warning bitacora pedido estado: ' . $e->getMessage());
    }

    $conn->commit();

 // CÓDIGO NUEVO (elimina completamente)
$estados_solucionados = [
    'CONFIRMADO', 'COMPLETADO', 'ENVIADO', 'ENTREGADO', 
    'CANCELADO', 'INHABILITADO', 'RECHAZADO'
];

if (in_array($nextState, $estados_solucionados, true)) {
    try {
        $deleteStmt = $conn->prepare(
            'DELETE FROM notificaciones_vendedor WHERE pedido_id = ?'
        );
        $deleteStmt->execute([$pedido_id]);
        
        $notifs_eliminadas = $deleteStmt->rowCount();
        error_log("Pedido #{$pedido_id} → {$nextState}: {$notifs_eliminadas} notifs eliminadas");
        
    } catch (Exception $e) {
        error_log('Warning al eliminar notificaciones: ' . $e->getMessage());
    }
}
    // Notificaciones básicas (solo en producción)
    $notified = [];
    if (!APP_DEBUG && !empty($cliente['email'])) {
        $subject = "Estado del pedido {$pedido['codigo_pedido']} - {$nextState}";
        $body = "Hola {$cliente['nombre_completo']},\n\n";
        $body .= "Tu pedido {$pedido['codigo_pedido']} ahora se encuentra en estado: {$nextState}.\n";
        if ($notes) {
            $body .= "Notas: {$notes}\n";
        }
        $body .= "\nGracias por preferirnos.";

        // Anexo: Si SMTP habilitado, usar mail() / PHPMailer directo. Para simplicidad, usamos mail().
        $headers = "From: " . SITE_NAME . " <" . (defined('SMTP_FROM') ? SMTP_FROM : 'no-reply@inversionesrojas.com') . ">\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        if (mail($cliente['email'], $subject, $body, $headers)) {
            $notified['email'] = true;
        } else {
            $notified['email'] = false;
        }
    }

    if (!APP_DEBUG && !empty($cliente['telefono_principal'])) {
        $toCliente = strpos($cliente['telefono_principal'], 'whatsapp:') === 0 ? $cliente['telefono_principal'] : 'whatsapp:' . $cliente['telefono_principal'];
        $msg = "Pedido {$pedido['codigo_pedido']} ahora está en estado: {$nextState}.";
        if ($notes) $msg .= "\nNotas: {$notes}";
        $resWa = send_whatsapp_message($toCliente, $msg);
        $notified['whatsapp_client'] = $resWa['success'] ?? false;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado a ' . $nextState,
        'estado'  => $nextState,
        'notified' => $notified
    ]);
    exit;

} catch (Exception $e) {
    if (!empty($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    $msg = 'Error interno';
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $msg = $e->getMessage();
    }

    error_log('Error update_pedido_estado.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}
