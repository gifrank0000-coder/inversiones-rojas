<?php
// ============================================================
// process_order.php  →  /api/process_order.php
// Crea el pedido digital y genera URLs/notificaciones según
// el canal elegido: whatsapp | email | telegram | notificaciones
// ============================================================
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success'=>false,'message'=>'Debes iniciar sesión']));
}
if (empty($_SESSION['carrito'])) {
    die(json_encode(['success'=>false,'message'=>'El carrito está vacío']));
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    die(json_encode(['success'=>false,'message'=>'Datos inválidos']));
}

$tipo         = $input['tipo'] ?? '';
$comunicacion = $input['comunicacion'] ?? 'whatsapp';

if (!in_array($tipo, ['pedido_digital', 'apartado'], true)) {
    die(json_encode(['success'=>false,'message'=>'Tipo no soportado en este endpoint']));
}

// Para reservas ('apartado') usamos la misma lógica de pedido digital, pero marcamos tipo para respuesta.
$isApartado = $tipo === 'apartado';

try {
    $db   = new Database();
    $conn = $db->getConnection();

    // ── Leer configuración de integraciones desde BD ──────────
    $cfg = [];
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS configuracion_integraciones (clave VARCHAR(80) PRIMARY KEY, valor TEXT NOT NULL DEFAULT '', updated_at TIMESTAMPTZ DEFAULT NOW())");
        $cfg = $conn->query("SELECT clave, valor FROM configuracion_integraciones")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch(Exception $e) {}

    $cfgVal = fn($k, $def='') => $cfg[$k] ?? (defined('INTEGRATION_'.strtoupper($k)) ? constant('INTEGRATION_'.strtoupper($k)) : $def);

    $wa_number   = preg_replace('/\D/', '', $cfgVal('whatsapp_number', defined('TIENDA_WHATSAPP') ? constant('TIENDA_WHATSAPP') : '584121304526'));
    $em_dest     = $cfgVal('email_notifications', '2016rojasinversiones@gmail.com');
    $em_from     = $cfgVal('email_from', 'no-reply@inversionesrojas.com');
    $tg_token    = $cfgVal('telegram_bot_token', '');
    $tg_chat_id  = $cfgVal('telegram_chat_id', '');
    $nt_enabled  = $cfgVal('internal_notifications_enabled', '1') === '1';

    $conn->beginTransaction();

    // ── 1. Cliente ────────────────────────────────────────────
    $stmt = $conn->prepare("SELECT id, email, nombre_completo, telefono_principal FROM clientes WHERE usuario_id=? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $conn->rollBack();
        die(json_encode(['success'=>false,'message'=>'Perfil de cliente incompleto. Completa tu perfil primero.']));
    }

    // ── 2. Datos del request ──────────────────────────────────
    $dc           = $input['datos_cliente'] ?? [];
    $telefono     = trim($dc['telefono']     ?? $cliente['telefono_principal'] ?? '');
    $observaciones= trim($dc['observaciones'] ?? '');
    $tipo_entrega = $dc['tipo_entrega']       ?? 'tienda';
    $direccion    = trim($dc['direccion']     ?? '');

    if (empty($telefono))
        die(json_encode(['success'=>false,'message'=>'El teléfono de contacto es obligatorio']));
    if ($tipo_entrega === 'domicilio' && empty($direccion))
        die(json_encode(['success'=>false,'message'=>'La dirección es obligatoria para delivery']));

    // ── 3. Verificar stock ────────────────────────────────────
    $carrito     = $_SESSION['carrito'];
    $pids        = array_keys($carrito);
    $placeholders= implode(',', array_fill(0, count($pids), '?'));

    $stmt = $conn->prepare("SELECT id, nombre, stock_actual, precio_venta FROM productos WHERE id IN ($placeholders) AND estado=true");
    $stmt->execute($pids);
    $prod_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $prod_idx = [];
    foreach ($prod_db as $p) $prod_idx[$p['id']] = $p;

    foreach ($carrito as $pid => $item) {
        if (!isset($prod_idx[$pid]))
            die(json_encode(['success'=>false,'message'=>"Producto ID {$pid} no disponible"]));

        $qty = intval($item['quantity'] ?? $item['cantidad'] ?? 0);
        if ($qty <= 0) {
            die(json_encode(['success' => false, 'message' => "Cantidad inválida para producto ID {$pid}"]));
        }

        if ($prod_idx[$pid]['stock_actual'] < $qty)
            die(json_encode(['success'=>false,'message'=>"Stock insuficiente: {$prod_idx[$pid]['nombre']} (disponible: {$prod_idx[$pid]['stock_actual']}, solicitado: {$qty})"]));

        // Asignar la cantidad validada para uso posterior
        $carrito[$pid]['cantidad_real'] = $qty;
    }

    // ── 4. Totales ────────────────────────────────────────────
    $subtotal = 0;
    foreach ($carrito as $pid => $item) {
        $qty = intval($item['cantidad_real'] ?? $item['quantity'] ?? $item['cantidad'] ?? 0);
        $subtotal += $prod_idx[$pid]['precio_venta'] * $qty;
    }
    $iva      = round($subtotal * 0.16, 2);
    $total    = round($subtotal + $iva, 2);
    $subtotal = round($subtotal, 2);

    // ── 5. Código único ───────────────────────────────────────
    do {
        $codigo = 'PED-'.date('Ymd').'-'.str_pad(mt_rand(1,99999),5,'0',STR_PAD_LEFT);
        $st = $conn->prepare("SELECT id FROM pedidos_online WHERE codigo_pedido=?");
        $st->execute([$codigo]);
    } while ($st->fetch());

    // ── 6. Insertar pedido ────────────────────────────────────
    $stmt = $conn->prepare(
        "INSERT INTO pedidos_online
            (codigo_pedido, cliente_id, subtotal, iva, total, estado_pedido,
             tipo_entrega, direccion_entrega, telefono_contacto, observaciones, canal_comunicacion, created_at)
         VALUES (?,?,?,?,?,'PENDIENTE',?,?,?,?,?,NOW())
         RETURNING id"
    );
    $stmt->execute([$codigo,$cliente['id'],$subtotal,$iva,$total,$tipo_entrega,$direccion,$telefono,$observaciones,$comunicacion]);
    $pedido_id = $stmt->fetchColumn();
    if (!$pedido_id) throw new Exception('No se pudo crear el pedido');

    // ── 7. Detalles ───────────────────────────────────────────
    $stmt_det = $conn->prepare("INSERT INTO detalle_pedidos_online (pedido_id,producto_id,cantidad,precio_unitario,subtotal) VALUES (?,?,?,?,?)");
    foreach ($carrito as $pid => $item) {
        $p   = $prod_idx[$pid];
        $qty = intval($item['cantidad_real'] ?? $item['quantity'] ?? $item['cantidad'] ?? 1);
        $sub = round($p['precio_venta'] * $qty, 2);
        $stmt_det->execute([$pedido_id,$pid,$qty,$p['precio_venta'],$sub]);
    }

    // ── 8. Bitácora ───────────────────────────────────────────
    try {
        $conn->prepare("INSERT INTO bitacora_sistema (usuario_id,accion,tabla_afectada,registro_id,detalles,created_at) VALUES (?,'PEDIDO_DIGITAL','pedidos_online',?,?::jsonb,NOW())")
             ->execute([$_SESSION['user_id'], $pedido_id, json_encode(['codigo'=>$codigo,'total'=>$total,'canal'=>$comunicacion,'productos'=>count($carrito)])]);
    } catch(Exception $e) {}

    // ── 9. Commit ─────────────────────────────────────────────
    $conn->commit();
    unset($_SESSION['carrito']);

    // Asignación automática de vendedor
    try {
        $asignacion_automatica = $conn->query("SELECT valor FROM configuracion_integraciones WHERE clave = 'auto_assign_vendors'")->fetchColumn();
        if ($asignacion_automatica === 'true') {
            // Buscar vendedor disponible (con menos pedidos pendientes)
            $stmt = $conn->prepare("
                SELECT u.id, u.nombre_completo, COUNT(po.id) as pedidos_pendientes
                FROM usuarios u
                LEFT JOIN roles r ON u.rol_id = r.id
                LEFT JOIN pedidos_online po ON po.vendedor_asignado_id = u.id AND po.estado_pedido = 'PENDIENTE'
                WHERE r.nombre ILIKE '%vendedor%' OR r.nombre ILIKE '%venta%'
                GROUP BY u.id, u.nombre_completo
                ORDER BY pedidos_pendientes ASC
                LIMIT 1
            ");
            $stmt->execute();
            $vendedor = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($vendedor) {
                $conn->prepare("UPDATE pedidos_online SET vendedor_asignado_id = ?, fecha_asignacion = NOW() WHERE id = ?")
                     ->execute([$vendedor['id'], $pedido_id]);
                // Notificar al vendedor
                $conn->prepare("INSERT INTO notificaciones_vendedor (pedido_id, titulo, mensaje, tipo, usuario_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())")
                     ->execute([$pedido_id, 'Nuevo pedido asignado', 'Se te ha asignado el pedido ' . $codigo, 'PEDIDO_ASIGNADO', $vendedor['id']]);
            }
        }
    } catch (Exception $e) {
        error_log('Error en asignación automática: ' . $e->getMessage());
    }

    // ── 10. Construir mensajes ────────────────────────────────
    mb_internal_encoding('UTF-8');

    $entrega_txt = $tipo_entrega === 'domicilio' ? 'Delivery' : 'Retiro en tienda';

    // Líneas de productos — sin emojis para WhatsApp
    $lineas_wa = '';
    $lineas_tg = '';
    foreach ($carrito as $pid => $item) {
        $p    = $prod_idx[$pid];
        $sub_usd = $p['precio_venta'] * $item['quantity'];
        $sub_bs = $sub_usd * TASA_CAMBIO;
        $sub  = number_format($sub_bs, 2);
        $lineas_wa .= "- {$p['nombre']} x{$item['quantity']} = Bs {$sub}\n";
        $lineas_tg .= "\xE2\x80\xA2 {$p['nombre']} x{$item['quantity']} = Bs {$sub}\n"; // •
    }

    // ── Totales en Bs ──────────────────────────────
    $subtotal_bs = $subtotal * TASA_CAMBIO;
    $iva_bs = $iva * TASA_CAMBIO;
    $total_bs = $total * TASA_CAMBIO;
    
    // ── Mensaje WhatsApp: sin emojis, texto limpio ────────────
    $msg_wa  = "*PEDIDO DIGITAL - Inversiones Rojas*\n";
    $msg_wa .= "-----------------------------------\n";
    $msg_wa .= "Codigo: *{$codigo}*\n";
    $msg_wa .= "Cliente: {$cliente['nombre_completo']}\n";
    $msg_wa .= "Telefono: {$telefono}\n\n";
    $msg_wa .= "*Productos:*\n{$lineas_wa}\n";
    $msg_wa .= "Subtotal: Bs ".number_format($subtotal_bs,2)."\n";
    $msg_wa .= "IVA (16%): Bs ".number_format($iva_bs,2)."\n";
    $msg_wa .= "*TOTAL: Bs ".number_format($total_bs,2)."*\n\n";
    $msg_wa .= "Entrega: {$entrega_txt}\n";
    if ($tipo_entrega === 'domicilio' && $direccion)
        $msg_wa .= "Direccion: {$direccion}\n";
    if ($observaciones)
        $msg_wa .= "Notas: {$observaciones}\n";
    $msg_wa .= "\nHola, acabo de realizar este pedido en su tienda online. Quedo en espera de contacto para coordinar el pago y la entrega. Gracias!";

    // ── Mensaje Telegram: con emojis (se envía server-side, encoding garantizado) ─
    $msg_tg  = "\xF0\x9F\x9B\x92 *PEDIDO DIGITAL - Inversiones Rojas*\n";   // 🛒
    $msg_tg .= "\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\n";
    $msg_tg .= "\xF0\x9F\x93\x8B C\xC3\xB3digo: *{$codigo}*\n";             // 📋
    $msg_tg .= "\xF0\x9F\x91\xA4 Cliente: {$cliente['nombre_completo']}\n";  // 👤
    $msg_tg .= "\xF0\x9F\x93\x9E Tel\xC3\xA9fono: {$telefono}\n\n";         // 📞
    $msg_tg .= "\xF0\x9F\x9B\x8D *Productos:*\n{$lineas_tg}\n";             // 🛍
    $msg_tg .= "\xF0\x9F\x92\xB0 Subtotal: Bs ".number_format($subtotal_bs,2)."\n"; // 💰
    $msg_tg .= "\xF0\x9F\xA7\xBE IVA (16%): Bs ".number_format($iva_bs,2)."\n";    // 🧾
    $msg_tg .= "\xE2\x9C\x85 *TOTAL: Bs ".number_format($total_bs,2)."*\n\n";      // ✅
    $msg_tg .= "\xF0\x9F\x93\xA6 Entrega: {$entrega_txt}\n";                // 📦
    if ($tipo_entrega === 'domicilio' && $direccion)
        $msg_tg .= "\xF0\x9F\x93\x8D Direcci\xC3\xB3n: {$direccion}\n";    // 📍
    if ($observaciones)
        $msg_tg .= "\xF0\x9F\x93\x9D Notas: {$observaciones}\n";            // 📝
    $msg_tg .= "\n_Pedido recibido desde la tienda online._";

    // msg_plain para email (sin markdown, sin emojis)
    $msg_plain = str_replace(['*', '_'], '', $msg_wa);

    // ── 11. Respuesta base ────────────────────────────────────
    $response = [
        'success'      => true,
        'tipo'         => $tipo,
        'codigo'       => $codigo,
        'pedido_id'    => $pedido_id,
        'subtotal'     => $subtotal,
        'iva'          => $iva,
        'total'        => $total,
        'comunicacion' => $comunicacion,
        'es_apartado'  => $isApartado,
    ];

    // ── 12. URLs / acciones según canal ───────────────────────

    // ─── WhatsApp: mensaje SIN emojis (texto limpio) ──────────
    if ($wa_number) {
        $response['whatsapp_url'] = 'https://wa.me/'.$wa_number.'?text='.rawurlencode($msg_wa);
    }

    // ─── Email: URL mailto con texto plano ────────────────────
    $em_asunto = "Pedido Digital {$codigo} - Inversiones Rojas";
    if ($em_dest) {
        $response['email_url'] = 'mailto:'.$em_dest
            .'?subject='.rawurlencode($em_asunto)
            .'&body='.rawurlencode($msg_plain);
    }

    // ─── Telegram: guardar msg_tg en sesión para send_notificacion_pedido.php ─
    // El mensaje CON emojis lo envía el servidor al dar "Finalizar"
    $_SESSION['pedido_msg_tg_'.$pedido_id] = $msg_tg;
    $response['telegram_url'] = ''; // no se abre desde el cliente

    // ─── Notificaciones internas ──────────────────────────────
    // Solo se crea UNA notificación — pendiente de asignar.
    // Cuando el admin asigne un vendedor, esa notificación se reemplaza
    // por una dirigida específicamente al vendedor (tipo PEDIDO_ASIGNADO).
    if ($comunicacion === 'notificaciones' && $nt_enabled) {
        try {
            $conn->exec("CREATE TABLE IF NOT EXISTS notificaciones_vendedor (
                id SERIAL PRIMARY KEY, pedido_id INT NOT NULL,
                titulo VARCHAR(200) NOT NULL, mensaje TEXT,
                tipo VARCHAR(40) DEFAULT 'pedido', leida BOOLEAN DEFAULT false,
                usuario_id INT, created_at TIMESTAMPTZ DEFAULT NOW()
            )");
            // usuario_id = NULL significa "para todos los admins/vendedores"
            // pero solo mientras no se asigne a un vendedor específico
            $conn->prepare(
                "INSERT INTO notificaciones_vendedor
                    (pedido_id, titulo, mensaje, tipo, usuario_id, created_at)
                 VALUES (?, ?, ?, 'PEDIDO_NUEVO', NULL, NOW())"
            )->execute([
                $pedido_id,
                "Nuevo pedido sin asignar: {$codigo}",
                "Cliente: {$cliente['nombre_completo']} | Total: Bs " . number_format($total, 2) . " | Tel: {$telefono} | Pendiente de asignar a un vendedor"
            ]);
            $response['notificacion_creada'] = true;
        } catch(Exception $e) {
            error_log('[process_order] notificacion: '.$e->getMessage());
            $response['notificacion_creada'] = false;
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log('[process_order] '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error al procesar el pedido: '.$e->getMessage()]);
}

// ══════════════════════════════════════════════════════════════
//  FUNCIONES AUXILIARES
// ══════════════════════════════════════════════════════════════

function _sendTelegram(string $token, string $chatId, string $text): bool {
    $url  = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = http_build_query(['chat_id'=>$chatId,'text'=>$text,'parse_mode'=>'Markdown']);
    $ctx  = stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded\r\n",'content'=>$data,'timeout'=>10]]);
    $res  = @file_get_contents($url, false, $ctx);
    if ($res === false) { error_log('[Telegram] cURL falló'); return false; }
    $j = json_decode($res, true);
    if (!($j['ok'] ?? false)) { error_log('[Telegram] Error: '.($j['description']??$res)); return false; }
    return true;
}

function _buildEmailHtml(string $nombre, string $codigo, array $carrito, array $prod_idx,
                          float $subtotal, float $iva, float $total,
                          string $tipo_entrega, string $direccion, string $telefono, string $obs): string {
    $entrega = $tipo_entrega === 'domicilio' ? "Delivery" : "Retiro en tienda";
    $rows = '';
    foreach ($carrito as $pid => $item) {
        $p   = $prod_idx[$pid];
        $sub = number_format($p['precio_venta'] * $item['quantity'], 2);
        $rows .= "<tr>
            <td style='padding:8px;border-bottom:1px solid #eee;'>{$p['nombre']}</td>
            <td style='padding:8px;border-bottom:1px solid #eee;text-align:center;'>{$item['quantity']}</td>
            <td style='padding:8px;border-bottom:1px solid #eee;text-align:right;'>Bs ".number_format($p['precio_venta'],2)."</td>
            <td style='padding:8px;border-bottom:1px solid #eee;text-align:right;'>Bs {$sub}</td>
        </tr>";
    }
    $dir_html = ($tipo_entrega === 'domicilio' && $direccion) ? "<p><strong>Dirección:</strong> {$direccion}</p>" : '';
    $obs_html = $obs ? "<p><strong>Notas:</strong> {$obs}</p>" : '';
    return "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;color:#333;'>
    <div style='max-width:580px;margin:0 auto;'>
        <div style='background:#1F9166;padding:20px;border-radius:8px 8px 0 0;text-align:center;'>
            <h2 style='color:white;margin:0;'>INVERSIONES ROJAS 2016 C.A.</h2>
            <p style='color:rgba(255,255,255,0.85);margin:6px 0 0;'>Pedido Digital Recibido</p>
        </div>
        <div style='padding:24px;background:#f9f9f9;border:1px solid #e0e0e0;border-top:none;'>
            <p>Hola <strong>{$nombre}</strong>,</p>
            <p>Recibimos tu pedido. Un asesor te contactará para coordinar el pago y la entrega.</p>
            <div style='background:#e8f6f1;border-left:4px solid #1F9166;border-radius:6px;padding:14px;margin:16px 0;font-size:1.1rem;text-align:center;'>
                Código: <strong style='color:#1F9166;'>{$codigo}</strong>
            </div>
            <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                <thead><tr style='background:#1F9166;color:white;'>
                    <th style='padding:10px;text-align:left;'>Producto</th>
                    <th style='padding:10px;text-align:center;'>Cant.</th>
                    <th style='padding:10px;text-align:right;'>Precio</th>
                    <th style='padding:10px;text-align:right;'>Subtotal</th>
                </tr></thead>
                <tbody>{$rows}</tbody>
            </table>
            <div style='text-align:right;padding:12px 0;border-top:2px solid #1F9166;margin-top:8px;'>
                <span style='font-size:13px;color:#555;'>Subtotal: Bs ".number_format($subtotal,2)." | IVA: Bs ".number_format($iva,2)."</span><br>
                <strong style='font-size:1.2rem;color:#1F9166;'>TOTAL: Bs ".number_format($total,2)."</strong>
            </div>
            <div style='background:#f0f0f0;border-radius:6px;padding:12px;margin-top:10px;font-size:13px;'>
                <p style='margin:0;'><strong>📦 Entrega:</strong> {$entrega}</p>
                {$dir_html}
                <p style='margin:4px 0 0;'><strong>📞 Teléfono:</strong> {$telefono}</p>
                {$obs_html}
            </div>
        </div>
        <div style='padding:16px;text-align:center;color:#888;font-size:11px;'>
            © Inversiones Rojas 2016 C.A. — 0243-2343044 — 2016rojasinversiones@gmail.com
        </div>
    </div></body></html>";
}