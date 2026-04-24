<?php
// ============================================================
// process_pedido.php  →  /api/process_pedido.php
// Acepta JSON (whatsapp/email/telegram) y multipart (notificaciones+comprobante)
// Estado: EN_VERIFICACION cuando hay comprobante, PENDIENTE en caso contrario.
// ============================================================
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Debes iniciar sesión']));
}
if (empty($_SESSION['carrito'])) {
    die(json_encode(['success' => false, 'message' => 'El carrito está vacío']));
}

$contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
$isMultipart = (strpos($contentType, 'multipart/form-data') !== false);

if ($isMultipart) {
    $comunicacion        = trim($_POST['comunicacion']       ?? 'notificaciones');
    $tipo                = trim($_POST['tipo']               ?? 'pedido_digital');
    $telefono            = trim($_POST['telefono']           ?? '');
    $observaciones       = trim($_POST['observaciones']      ?? '');
    $tipo_entrega        = trim($_POST['tipo_entrega']       ?? 'tienda');
    $direccion           = trim($_POST['direccion']          ?? '');
    $referencia_pago     = trim($_POST['referencia_pago']    ?? '');
    $metodo_pago_nombre  = trim($_POST['metodo_pago_nombre'] ?? '');
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) die(json_encode(['success' => false, 'message' => 'Datos inválidos']));

    $tipo                = $input['tipo']         ?? 'pedido_digital';
    $comunicacion        = $input['comunicacion'] ?? 'whatsapp';
    $dc                  = $input['datos_cliente'] ?? $input;
    $telefono            = trim($dc['telefono']      ?? '');
    $observaciones       = trim($dc['observaciones'] ?? '');
    $tipo_entrega        = $dc['tipo_entrega']        ?? 'tienda';
    $direccion           = trim($dc['direccion']      ?? '');
    $referencia_pago     = '';
    $metodo_pago_nombre  = '';
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $cfg = [];
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS configuracion_integraciones (clave VARCHAR(80) PRIMARY KEY, valor TEXT NOT NULL DEFAULT '', updated_at TIMESTAMPTZ DEFAULT NOW())");
        $cfg = $conn->query("SELECT clave, valor FROM configuracion_integraciones")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch(Exception $e) {}
    $cfgVal = fn($k,$d='') => $cfg[$k] ?? (defined('INTEGRATION_'.strtoupper($k)) ? constant('INTEGRATION_'.strtoupper($k)) : $d);

    $wa_number  = preg_replace('/\D/', '', $cfgVal('whatsapp_number', defined('TIENDA_WHATSAPP') ? constant('TIENDA_WHATSAPP') : '584121304526'));
    $nt_enabled = $cfgVal('internal_notifications_enabled', '1') === '1';

    // Agregar columnas opcionales si no existen (PostgreSQL IF NOT EXISTS)
    foreach (['comprobante_url VARCHAR(500)', 'metodo_pago VARCHAR(200)', 'referencia_pago VARCHAR(300)'] as $colDef) {
        try { $conn->exec("ALTER TABLE pedidos_online ADD COLUMN IF NOT EXISTS {$colDef}"); } catch(Exception $e) {}
    }

    $conn->beginTransaction();

    // Cliente
    $stmt = $conn->prepare("SELECT id, email, nombre_completo, telefono_principal FROM clientes WHERE usuario_id=? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $uid  = $_SESSION['user_id'];
        $conn->prepare("INSERT INTO clientes (cedula_rif,nombre_completo,email,telefono_principal,usuario_id,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())")
             ->execute(['USR'.$uid, $_SESSION['user_name'] ?? 'Cliente', $_SESSION['user_email'] ?? null, $telefono, $uid]);
        $stmt->execute([$uid]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) { $conn->rollBack(); die(json_encode(['success'=>false,'message'=>'Perfil incompleto'])); }
    }
    if (empty($telefono) && !empty($cliente['telefono_principal'])) $telefono = $cliente['telefono_principal'];
    if (empty($telefono)) die(json_encode(['success'=>false,'message'=>'Teléfono obligatorio']));
    if ($tipo_entrega === 'domicilio' && empty($direccion)) die(json_encode(['success'=>false,'message'=>'Dirección obligatoria para delivery']));

    // Stock
    $carrito = $_SESSION['carrito'];
    $pids    = array_keys($carrito);
    $ph      = implode(',', array_fill(0, count($pids), '?'));
    $stmt    = $conn->prepare("SELECT id, nombre, stock_actual, precio_venta FROM productos WHERE id IN ($ph) AND estado=true");
    $stmt->execute($pids);
    $prod_idx = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) $prod_idx[$p['id']] = $p;
    foreach ($carrito as $pid => $item) {
        if (!isset($prod_idx[$pid])) die(json_encode(['success'=>false,'message'=>"Producto {$pid} no disponible"]));
        if ($prod_idx[$pid]['stock_actual'] < $item['quantity']) die(json_encode(['success'=>false,'message'=>"Stock insuficiente: {$prod_idx[$pid]['nombre']}"]));
    }

    // Totales
    $subtotal = 0;
    foreach ($carrito as $pid => $item) $subtotal += $prod_idx[$pid]['precio_venta'] * $item['quantity'];
    $iva   = round($subtotal * 0.16, 2);
    $total = round($subtotal + $iva, 2);
    $subtotal = round($subtotal, 2);

    // Código único
    do {
        $codigo = 'PED-'.date('Ymd').'-'.str_pad(mt_rand(1,99999),5,'0',STR_PAD_LEFT);
        $st = $conn->prepare("SELECT id FROM pedidos_online WHERE codigo_pedido=?");
        $st->execute([$codigo]);
    } while ($st->fetch());

    // Comprobante (solo multipart)
    $comprobante_url = null;
    if ($isMultipart && !empty($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__) . '/public/uploads/pedidos_comprobantes/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
            $filename = uniqid('ped_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $uploadDir . $filename)) {
                $comprobante_url = '/public/uploads/pedidos_comprobantes/' . $filename;
            }
        }
    }

    // Estado inicial
    $tiene_pago     = !empty($comprobante_url) || !empty($referencia_pago);
    $estado_inicial = ($tiene_pago && $comunicacion === 'notificaciones') ? 'EN_VERIFICACION' : 'PENDIENTE';

    // Insertar pedido
    $stmt = $conn->prepare(
        "INSERT INTO pedidos_online
            (codigo_pedido, cliente_id, subtotal, iva, total, estado_pedido,
             tipo_entrega, direccion_entrega, telefono_contacto, observaciones,
             canal_comunicacion, comprobante_url, metodo_pago, referencia_pago, created_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW()) RETURNING id"
    );
    $stmt->execute([$codigo, $cliente['id'], $subtotal, $iva, $total, $estado_inicial,
        $tipo_entrega, $direccion, $telefono, $observaciones, $comunicacion,
        $comprobante_url, $metodo_pago_nombre ?: null, $referencia_pago ?: null]);
    $pedido_id = $stmt->fetchColumn();
    if (!$pedido_id) throw new Exception('No se pudo crear el pedido');

    // Detalles
    $stmt_det = $conn->prepare("INSERT INTO detalle_pedidos_online (pedido_id,producto_id,cantidad,precio_unitario,subtotal) VALUES (?,?,?,?,?)");
    foreach ($carrito as $pid => $item) {
        $p = $prod_idx[$pid];
        $stmt_det->execute([$pedido_id, $pid, $item['quantity'], $p['precio_venta'], round($p['precio_venta'] * $item['quantity'], 2)]);
    }

    // Bitácora
    try {
        $conn->prepare("INSERT INTO bitacora_sistema (usuario_id,accion,tabla_afectada,registro_id,detalles,created_at) VALUES (?,'PEDIDO_DIGITAL','pedidos_online',?,?::jsonb,NOW())")
             ->execute([$_SESSION['user_id'], $pedido_id, json_encode(['codigo'=>$codigo,'canal'=>$comunicacion,'estado'=>$estado_inicial,'comprobante'=>(bool)$comprobante_url])]);
    } catch(Exception $e) { error_log('[process_pedido] bitacora: '.$e->getMessage()); }

    // Notificación interna
    if ($comunicacion === 'notificaciones' && $nt_enabled) {
        try {
            $conn->exec("CREATE TABLE IF NOT EXISTS notificaciones_vendedor (id SERIAL PRIMARY KEY, pedido_id INT NOT NULL, titulo VARCHAR(200) NOT NULL, mensaje TEXT, tipo VARCHAR(40) DEFAULT 'pedido', leida BOOLEAN DEFAULT false, usuario_id INT, created_at TIMESTAMPTZ DEFAULT NOW())");
            $tasa    = defined('TASA_CAMBIO') ? TASA_CAMBIO : 1;
            $totalBs = number_format($total * $tasa, 2);
            $conn->prepare("INSERT INTO notificaciones_vendedor (pedido_id,titulo,mensaje,tipo,created_at) VALUES (?,?,?,'pedido',NOW())")
                 ->execute([
                     $pedido_id,
                     ($estado_inicial === 'EN_VERIFICACION' ? '📎' : '🛒') . " Pedido {$estado_inicial}: {$codigo}",
                     "Cliente: {$cliente['nombre_completo']} | Bs {$totalBs} | Tel: {$telefono}" . ($comprobante_url ? ' | ✅ Comprobante adjunto' : ''),
                 ]);
        } catch(Exception $e) { error_log('[process_pedido] notificacion: '.$e->getMessage()); }
    }

    // Vaciar carrito
    unset($_SESSION['carrito']);
    $conn->commit();

    // Respuesta
    $tasa = defined('TASA_CAMBIO') ? TASA_CAMBIO : 1;
    $entrega_txt = $tipo_entrega === 'domicilio' ? 'Delivery' : 'Retiro en tienda';
    $lineas = '';
    foreach ($carrito as $pid => $item) {
        $p = $prod_idx[$pid];
        $lineas .= "• {$p['nombre']} x{$item['quantity']} = Bs ".number_format($p['precio_venta'] * $item['quantity'] * $tasa, 2)."\n";
    }
    $msg_wa = "🛒 PEDIDO DIGITAL - Inversiones Rojas\n━━━━━━━━━━━━━━━\nCódigo: {$codigo}\nCliente: {$cliente['nombre_completo']}\nTel: {$telefono}\n\n{$lineas}\nSubtotal: Bs ".number_format($subtotal*$tasa,2)."\nIVA: Bs ".number_format($iva*$tasa,2)."\nTOTAL: Bs ".number_format($total*$tasa,2)."\n\nEntrega: {$entrega_txt}".($tipo_entrega==='domicilio'&&$direccion?"\nDir: {$direccion}":'').($observaciones?"\nNotas: {$observaciones}":'');

    if (!empty($pedido_id)) $_SESSION['pedido_msg_tg_'.$pedido_id] = $msg_wa;

    echo json_encode([
        'success'           => true,
        'tipo'              => 'pedido_digital',
        'codigo'            => $codigo,
        'pedido_id'         => $pedido_id,
        'subtotal'          => $subtotal,
        'iva'               => $iva,
        'total'             => $total,
        'comunicacion'      => $comunicacion,
        'estado_pedido'     => $estado_inicial,
        'tiene_comprobante' => (bool)$comprobante_url,
        'whatsapp_url'      => $wa_number ? 'https://wa.me/'.$wa_number.'?text='.rawurlencode($msg_wa) : '',
        'telegram_url'      => '',
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log('[process_pedido] '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: '.$e->getMessage()]);
}
?>