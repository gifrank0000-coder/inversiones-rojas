<?php
// ============================================================
// process_reserva.php  →  /api/process_reserva.php
// Crea una reserva (apartado) desde el carrito del cliente.
// ============================================================
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('process_reserva shutdown: ' . json_encode($err));
        if (ob_get_level()) ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
});

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Debes iniciar sesión para crear una reserva']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

$raw   = file_get_contents('php://input');
$input = [];
if ($raw) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $input = $decoded;
}

$telefono       = trim($input['telefono']      ?? '');
$observaciones  = trim($input['observaciones'] ?? '');
$items_payload  = $input['items']              ?? [];
$subtotal_input = (float)($input['subtotal']   ?? 0);
$iva_input      = (float)($input['iva']        ?? 0);
$total_input    = (float)($input['total']      ?? 0);

try {
    $db   = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception('Sin conexión a la base de datos');

    $usuario_id = (int)$_SESSION['user_id'];

    // ── Obtener cliente del usuario (3 métodos) ───────────────
    $cliente = null;

    $st = $conn->prepare("SELECT id, nombre_completo, email, telefono_principal FROM clientes WHERE usuario_id = ? AND estado = true LIMIT 1");
    $st->execute([$usuario_id]);
    $cliente = $st->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $st2 = $conn->prepare("SELECT cliente_id FROM usuarios WHERE id = ?");
        $st2->execute([$usuario_id]);
        $row2 = $st2->fetch(PDO::FETCH_ASSOC);
        if ($row2 && $row2['cliente_id']) {
            $st3 = $conn->prepare("SELECT id, nombre_completo, email, telefono_principal FROM clientes WHERE id = ? AND estado = true");
            $st3->execute([$row2['cliente_id']]);
            $cliente = $st3->fetch(PDO::FETCH_ASSOC);
        }
    }

    if (!$cliente && !empty($_SESSION['user_email'])) {
        $st4 = $conn->prepare("SELECT id, nombre_completo, email, telefono_principal FROM clientes WHERE email = ? AND estado = true LIMIT 1");
        $st4->execute([$_SESSION['user_email']]);
        $cliente = $st4->fetch(PDO::FETCH_ASSOC);
    }

    if (!$cliente) {
        ob_end_clean();
        die(json_encode(['success' => false, 'message' => 'No se encontró tu perfil de cliente. Completa tu perfil primero.']));
    }

    $cliente_id = (int)$cliente['id'];
    if (!$telefono && !empty($cliente['telefono_principal'])) {
        $telefono = $cliente['telefono_principal'];
    }

    // ── Obtener productos: del payload o de la sesión ─────────
    $productos_a_reservar = [];

    if (!empty($items_payload)) {
        foreach ($items_payload as $item) {
            $pid  = (int)($item['id']       ?? 0);
            $cant = (int)($item['cantidad'] ?? 0);
            if ($pid <= 0 || $cant <= 0) continue;
            $stP = $conn->prepare("SELECT id, nombre, precio_venta, stock_actual FROM productos WHERE id = ? AND estado = true");
            $stP->execute([$pid]);
            $prow = $stP->fetch(PDO::FETCH_ASSOC);
            if (!$prow) {
                ob_end_clean();
                die(json_encode(['success' => false, 'message' => "Producto ID {$pid} no encontrado o inactivo"]));
            }
            $productos_a_reservar[] = [
                'id'           => $pid,
                'nombre'       => $prow['nombre'],
                'cantidad'     => $cant,
                'precio_venta' => (float)$prow['precio_venta'],
                'stock_actual' => (int)$prow['stock_actual'],
            ];
        }
    } elseif (!empty($_SESSION['carrito'])) {
        $ids  = array_keys($_SESSION['carrito']);
        $ph   = implode(',', array_fill(0, count($ids), '?'));
        $stAll = $conn->prepare("SELECT id, nombre, precio_venta, stock_actual FROM productos WHERE id IN ({$ph}) AND estado = true");
        $stAll->execute($ids);
        foreach ($stAll->fetchAll(PDO::FETCH_ASSOC) as $prow) {
            $pid  = $prow['id'];
            $cant = (int)$_SESSION['carrito'][$pid]['quantity'];
            if ($cant <= 0) continue;
            $productos_a_reservar[] = [
                'id'           => $pid,
                'nombre'       => $prow['nombre'],
                'cantidad'     => $cant,
                'precio_venta' => (float)$prow['precio_venta'],
                'stock_actual' => (int)$prow['stock_actual'],
            ];
        }
    }

    if (empty($productos_a_reservar)) {
        ob_end_clean();
        die(json_encode(['success' => false, 'message' => 'El carrito está vacío']));
    }

    // ── Verificar stock ───────────────────────────────────────
    foreach ($productos_a_reservar as $p) {
        if ($p['stock_actual'] < $p['cantidad']) {
            ob_end_clean();
            die(json_encode([
                'success' => false,
                'message' => "Stock insuficiente para \"{$p['nombre']}\". Disponible: {$p['stock_actual']} unidad(es)"
            ]));
        }
    }

    // ── Calcular totales ──────────────────────────────────────
    $subtotal = 0;
    foreach ($productos_a_reservar as $p) $subtotal += $p['precio_venta'] * $p['cantidad'];
    $iva   = round($subtotal * 0.16, 2);
    $total = round($subtotal + $iva, 2);

    if ($subtotal == 0 && $subtotal_input > 0) {
        $subtotal = $subtotal_input; $iva = $iva_input; $total = $total_input;
    }

    // ── Generar código único ──────────────────────────────────
    do {
        $codigo = 'RES-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $stDup = $conn->prepare("SELECT 1 FROM reservas WHERE codigo_reserva = ? LIMIT 1");
        $stDup->execute([$codigo]);
    } while ($stDup->fetch());

    $fecha_hoy    = date('Y-m-d');
    $fecha_limite = date('Y-m-d', strtotime('+7 days'));
    $obs_final    = $telefono ? 'Tel: ' . $telefono . ($observaciones ? ' | ' . $observaciones : '') : $observaciones;

    // ── Transacción ───────────────────────────────────────────
    $conn->beginTransaction();

    $stIns = $conn->prepare("
        INSERT INTO reservas
            (codigo_reserva, cliente_id, producto_id, cantidad,
             fecha_reserva, fecha_limite, estado_reserva, observaciones,
             created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'ACTIVA', ?, NOW(), NOW())
    ");

    foreach ($productos_a_reservar as $p) {
        $stIns->execute([$codigo, $cliente_id, $p['id'], $p['cantidad'], $fecha_hoy, $fecha_limite, $obs_final]);
    }

    try {
        $conn->prepare(
            "INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at)
             VALUES (?, 'CREAR_RESERVA', 'reservas', 0, ?::jsonb, NOW())"
        )->execute([$usuario_id, json_encode(['codigo' => $codigo, 'total' => $total])]);
    } catch (Exception $e) {}

    $conn->commit();

    $_SESSION['carrito'] = [];

    // ── Respuesta con los campos exactos que espera el JS ─────
    ob_end_clean();
    echo json_encode([
        'success'                => true,
        'tipo'                   => 'apartado',
        'codigo'                 => $codigo,
        'fecha_limite'           => $fecha_limite,
        'fecha_limite_formateada'=> date('d/m/Y', strtotime($fecha_limite)),
        'subtotal'               => round($subtotal, 2),
        'iva'                    => round($iva, 2),
        'total'                  => round($total, 2),
        'message'                => "Reserva {$codigo} creada correctamente",
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log('[process_reserva] ' . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al procesar la reserva: ' . $e->getMessage()]);
}