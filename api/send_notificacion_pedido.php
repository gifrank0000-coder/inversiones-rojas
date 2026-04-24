<?php
// ============================================================
// send_notificacion_pedido.php  →  /api/send_notificacion_pedido.php
// El cliente lo llama al dar "Finalizar" en el modal de confirmación.
// Envía el mensaje al canal elegido (telegram, email) server-side.
// WhatsApp y notificaciones internas NO pasan por aquí.
// ============================================================
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/EmailHelper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'No autenticado']));
}

$input     = json_decode(file_get_contents('php://input'), true);
$pedido_id = (int)($input['pedido_id'] ?? 0);
$canal     = trim($input['canal'] ?? '');

if (!$pedido_id || !$canal) {
    die(json_encode(['success' => false, 'message' => 'Parámetros incompletos']));
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    // ── Leer configuración de integraciones ──────────────────
    $cfg = [];
    try {
        $cfg = $conn->query("SELECT clave, valor FROM configuracion_integraciones")
                    ->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {}

    $cfgVal = fn($k, $d = '') => $cfg[$k] ?? (defined('INTEGRATION_' . strtoupper($k)) ? constant('INTEGRATION_' . strtoupper($k)) : $d);

    // ── Cargar pedido con detalles ────────────────────────────
    $stmt = $conn->prepare(
        "SELECT p.*, c.nombre_completo AS cliente_nombre, c.email AS cliente_email
         FROM pedidos_online p
         LEFT JOIN clientes c ON p.cliente_id = c.id
         WHERE p.id = ?"
    );
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        die(json_encode(['success' => false, 'message' => 'Pedido no encontrado']));
    }

    // ── Cargar items ──────────────────────────────────────────
    $stmt = $conn->prepare(
        "SELECT d.cantidad, d.precio_unitario, d.subtotal, pr.nombre
         FROM detalle_pedidos_online d
         JOIN productos pr ON d.producto_id = pr.id
         WHERE d.pedido_id = ?"
    );
    $stmt->execute([$pedido_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Construir mensaje ─────────────────────────────────────
    $entrega_txt = ($pedido['tipo_entrega'] ?? 'tienda') === 'domicilio' ? 'Delivery' : 'Retiro en tienda';
    $lineas = '';
    $lineas_tg = '';
    foreach ($items as $item) {
        $sub_usd = (float)$item['subtotal'];
        $sub_bs = $sub_usd * TASA_CAMBIO;
        $sub    = number_format($sub_bs, 2);
        $lineas .= "• {$item['nombre']} x{$item['cantidad']} = Bs {$sub}\n";
        $lineas_tg .= "\xE2\x80\xA2 {$item['nombre']} x{$item['cantidad']} = Bs {$sub}\n";
    }

    $codigo   = $pedido['codigo_pedido'];
    $nombre   = $pedido['cliente_nombre'] ?? 'Cliente';
    $telefono = $pedido['telefono_contacto'] ?? '';
    $subtotal = (float)$pedido['subtotal'];
    $iva      = (float)$pedido['iva'];
    $total    = (float)$pedido['total'];
    $obs      = $pedido['observaciones'] ?? '';
    $dir      = $pedido['direccion_entrega'] ?? '';
    
    // Convertir a Bs
    $subtotal_bs = $subtotal * TASA_CAMBIO;
    $iva_bs = $iva * TASA_CAMBIO;
    $total_bs = $total * TASA_CAMBIO;
    
    $msg  = "🛒 *PEDIDO DIGITAL - Inversiones Rojas*\n";
    $msg .= "━━━━━━━━━━━━━━━━━━━━━\n";
    $msg .= "📋 Código: *{$codigo}*\n";
    $msg .= "👤 Cliente: {$nombre}\n";
    $msg .= "📞 Teléfono: {$telefono}\n\n";
    $msg .= "🛍 *Productos:*\n{$lineas}\n";
    $msg .= "💰 Subtotal: Bs " . number_format($subtotal_bs, 2) . "\n";
    $msg .= "🧾 IVA (16%): Bs " . number_format($iva_bs, 2) . "\n";
    $msg .= "✅ *TOTAL: Bs " . number_format($total_bs, 2) . "*\n\n";
    $msg .= "📦 Entrega: {$entrega_txt}\n";
    if ($entrega_txt === 'Delivery' && $dir) $msg .= "📍 Dirección: {$dir}\n";
    if ($obs) $msg .= "📝 Notas: {$obs}\n";

    // ── Construir mensaje con emojis para Telegram ───────────
    // Intentar leer el mensaje pre-armado guardado en sesión al procesar el pedido
    $msg_tg = $_SESSION['pedido_msg_tg_'.$pedido_id] ?? null;

    // Si no está en sesión (p.ej. sesión expiró), reconstruirlo
    if (!$msg_tg) {
        mb_internal_encoding('UTF-8');
        $lineas_tg = '';
        foreach ($items as $item) {
            $sub_usd = (float)$item['subtotal'];
            $sub_bs = $sub_usd * TASA_CAMBIO;
            $sub = number_format($sub_bs, 2);
            $lineas_tg .= "\xE2\x80\xA2 {$item['nombre']} x{$item['cantidad']} = Bs {$sub}\n";
        }
        $msg_tg  = "\xF0\x9F\x9B\x92 *PEDIDO DIGITAL - Inversiones Rojas*\n";
        $msg_tg .= "\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\xE2\x94\x80\n";
        $msg_tg .= "\xF0\x9F\x93\x8B C\xC3\xB3digo: *{$codigo}*\n";
        $msg_tg .= "\xF0\x9F\x91\xA4 Cliente: {$nombre}\n";
        $msg_tg .= "\xF0\x9F\x93\x9E Tel\xC3\xA9fono: {$telefono}\n\n";
        $msg_tg .= "\xF0\x9F\x9B\x8D *Productos:*\n{$lineas_tg}\n";
        $msg_tg .= "\xF0\x9F\x92\xB0 Subtotal: Bs ".number_format($subtotal_bs,2)."\n";
        $msg_tg .= "\xF0\x9F\xA7\xBE IVA (16%): Bs ".number_format($iva_bs,2)."\n";
        $msg_tg .= "\xE2\x9C\x85 *TOTAL: Bs ".number_format($total_bs,2)."*\n\n";
        $msg_tg .= "\xF0\x9F\x93\xA6 Entrega: {$entrega_txt}\n";
        if ($entrega_txt === 'Delivery' && $dir)
            $msg_tg .= "\xF0\x9F\x93\x8D Direcci\xC3\xB3n: {$dir}\n";
        if ($obs)
            $msg_tg .= "\xF0\x9F\x93\x9D Notas: {$obs}\n";
        $msg_tg .= "\n_Pedido recibido desde la tienda online._";
    }

    // Limpiar de sesión después de usarlo
    unset($_SESSION['pedido_msg_tg_'.$pedido_id]);

    $resultado = ['success' => false, 'canal' => $canal];

    // ═══════════════════════════════════════════════════════════
    //  TELEGRAM — envío directo al bot/grupo configurado
    // ═══════════════════════════════════════════════════════════
    if ($canal === 'telegram') {
        $token   = $cfgVal('telegram_bot_token', '');
        $chat_id = $cfgVal('telegram_chat_id', '');

        if (!$token || !$chat_id) {
            die(json_encode(['success' => false, 'message' => 'Telegram no está configurado']));
        }

        $url  = "https://api.telegram.org/bot{$token}/sendMessage";
        $data = http_build_query([
            'chat_id'    => $chat_id,
            'text'       => $msg_tg,
            'parse_mode' => 'Markdown',
        ]);
        $ctx  = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $data,
            'timeout' => 12,
        ]]);
        $res  = @file_get_contents($url, false, $ctx);
        $json = $res ? json_decode($res, true) : null;

        if ($json && ($json['ok'] ?? false)) {
            $resultado['success'] = true;
            $resultado['message'] = 'Mensaje enviado al grupo de Telegram';
        } else {
            $err = $json['description'] ?? 'Sin respuesta del servidor de Telegram';
            error_log("[send_notificacion] Telegram error: {$err}");
            $resultado['success'] = false;
            $resultado['message'] = "Error Telegram: {$err}";
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  EMAIL — envío server-side a la tienda y confirmación al cliente
    // ═══════════════════════════════════════════════════════════
    elseif ($canal === 'email') {
        $em_dest  = $cfgVal('email_notifications', '2016rojasinversiones@gmail.com');
        $em_from  = $cfgVal('email_from', 'no-reply@inversionesrojas.com');
        $em_asunto = "Pedido Digital {$codigo} - Inversiones Rojas";

        if (!$em_dest) {
            die(json_encode(['success' => false, 'message' => 'Email de notificaciones no configurado']));
        }

        // Convertir a Bs
        $tasa = TASA_CAMBIO;
        $subtotal_bs = $subtotal * $tasa;
        $iva_bs = $iva * $tasa;
        $total_bs = $total * $tasa;

        // Construir HTML del email con Bs
        $rows = '';
        foreach ($items as $item) {
            $precio_bs = (float)$item['precio_unitario'] * $tasa;
            $sub_bs = (float)$item['subtotal'] * $tasa;
            $rows .= "<tr>
                <td style='padding:8px;border-bottom:1px solid #eee;'>{$item['nombre']}</td>
                <td style='padding:8px;border-bottom:1px solid #eee;text-align:center;'>{$item['cantidad']}</td>
                <td style='padding:8px;border-bottom:1px solid #eee;text-align:right;'>Bs " . number_format($precio_bs, 2) . "</td>
                <td style='padding:8px;border-bottom:1px solid #eee;text-align:right;'>Bs " . number_format($sub_bs, 2) . "</td>
            </tr>";
        }
        $dir_html = ($entrega_txt === 'Delivery' && $dir) ? "<p><strong>Dirección:</strong> {$dir}</p>" : '';
        $obs_html = $obs ? "<p><strong>Notas:</strong> {$obs}</p>" : '';

        $html = "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;color:#333;'>
        <div style='max-width:580px;margin:0 auto;'>
            <div style='background:#1F9166;padding:20px;border-radius:8px 8px 0 0;text-align:center;'>
                <h2 style='color:white;margin:0;'>INVERSIONES ROJAS 2016 C.A.</h2>
                <p style='color:rgba(255,255,255,0.85);margin:4px 0 0;'>Nuevo Pedido Digital</p>
            </div>
            <div style='padding:22px;background:#f9f9f9;border:1px solid #e0e0e0;border-top:none;'>
                <p>Se recibió el pedido de <strong>{$nombre}</strong>.</p>
                <div style='background:#e8f6f1;border-left:4px solid #1F9166;padding:12px;border-radius:6px;margin:14px 0;font-size:1.05rem;text-align:center;'>
                    Código: <strong style='color:#1F9166;'>{$codigo}</strong>
                </div>
                <table style='width:100%;border-collapse:collapse;font-size:13px;'>
                    <thead><tr style='background:#1F9166;color:white;'>
                        <th style='padding:9px;text-align:left;'>Producto</th>
                        <th style='padding:9px;text-align:center;'>Cant.</th>
                        <th style='padding:9px;text-align:right;'>Precio</th>
                        <th style='padding:9px;text-align:right;'>Subtotal</th>
                    </tr></thead>
                    <tbody>{$rows}</tbody>
                </table>
                <div style='text-align:right;padding:10px 0;border-top:2px solid #1F9166;margin-top:6px;'>
                    <span style='font-size:12px;color:#555;'>Subtotal: Bs " . number_format($subtotal_bs, 2) . " | IVA: Bs " . number_format($iva_bs, 2) . "</span><br>
                    <strong style='font-size:1.15rem;color:#1F9166;'>TOTAL: Bs " . number_format($total_bs, 2) . "</strong>
                </div>
                <div style='background:#f0f0f0;border-radius:6px;padding:11px;margin-top:10px;font-size:13px;'>
                    <p style='margin:0;'><strong>📦 Entrega:</strong> {$entrega_txt}</p>
                    {$dir_html}
                    <p style='margin:4px 0 0;'><strong>📞 Teléfono:</strong> {$telefono}</p>
                    {$obs_html}
                </div>
            </div>
            <div style='padding:14px;text-align:center;color:#888;font-size:11px;'>
                © Inversiones Rojas 2016 C.A. — 0243-2343044
            </div>
        </div></body></html>";

        // ── Enviar a la tienda via SMTP ───────────────────────
        $res_tienda = enviarEmailSMTP(
            $em_dest,
            'Inversiones Rojas',
            $em_asunto,
            $html
        );

        // ── Confirmación al cliente si tiene email ────────────
        $res_cliente = ['success' => false];
        if (!empty($pedido['cliente_email'])) {
            $res_cliente = enviarEmailSMTP(
                $pedido['cliente_email'],
                $nombre,
                "Confirmación de pedido: {$codigo}",
                $html
            );
        }

        $resultado['success'] = $res_tienda['success'];
        $resultado['message'] = $res_tienda['success']
            ? 'Email enviado a la tienda' . ($res_cliente['success'] ? ' y confirmación al cliente' : '')
            : $res_tienda['message'];

        // Log si falló para debugging
        if (!$res_tienda['success']) {
            error_log('[send_notificacion_pedido] Email falló: ' . $res_tienda['message']);
        }
    }

    // Canal no requiere envío server-side (whatsapp, notificaciones)
    else {
        $resultado['success'] = true;
        $resultado['message'] = "Canal {$canal}: sin envío server-side necesario";
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    error_log('[send_notificacion_pedido] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}