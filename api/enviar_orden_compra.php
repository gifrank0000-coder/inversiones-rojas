<?php
// ============================================================
// enviar_orden_compra.php  →  /api/enviar_orden_compra.php
// Envía la orden de compra por email al proveedor usando
// el EmailHelper (SMTP puro, sin PHPMailer).
// POST JSON: compra_id, codigo_compra, proveedor_email,
//            proveedor_nombre, total, fecha_entrega, productos[]
// ============================================================
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'No autenticado']));
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/EmailHelper.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'JSON inválido']));
}

$compra_id       = (int)($data['compra_id']       ?? 0);
$codigo_compra   = trim($data['codigo_compra']     ?? '');
$proveedor_email = trim($data['proveedor_email']   ?? '');
$proveedor_nombre= trim($data['proveedor_nombre']  ?? 'Proveedor');
$total           = (float)($data['total']          ?? 0);
$fecha_entrega   = trim($data['fecha_entrega']     ?? '');
$productos       = $data['productos']              ?? [];

error_log("[enviar_orden_compra] Iniciando envío - Email: {$proveedor_email}, Nombre: {$proveedor_nombre}");

if (!$proveedor_email || !filter_var($proveedor_email, FILTER_VALIDATE_EMAIL)) {
    error_log("[enviar_orden_compra] Email inválido: {$proveedor_email}");
    die(json_encode(['success' => false, 'message' => 'El proveedor no tiene un correo electrónico válido registrado']));
}

// ── Obtener nombre del usuario que crea la orden ─────────────
$emisor_nombre = $_SESSION['user_name'] ?? 'Inversiones Rojas';

// ── Construir tabla de productos en HTML ─────────────────────
$filas_productos = '';
$subtotal = 0;
foreach ($productos as $p) {
    $nombre   = htmlspecialchars($p['nombre']       ?? 'Producto');
    $codigo   = htmlspecialchars($p['codigo']       ?? '-');
    $cant     = (int)($p['cantidad']                ?? 0);
    $precio   = (float)($p['precio_unitario']       ?? 0);
    $linea    = round($cant * $precio, 2);
    $subtotal += $linea;
    $filas_productos .= "
        <tr>
            <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;'>{$codigo}</td>
            <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;'>{$nombre}</td>
            <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;text-align:center;'>{$cant}</td>
            <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;text-align:right;'>Bs " . number_format($precio, 2) . "</td>
            <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;text-align:right;'>Bs " . number_format($linea, 2) . "</td>
        </tr>";
}
$iva        = round($subtotal * 0.16, 2);
$total_calc = round($subtotal + $iva, 2);

$fecha_formateada = $fecha_entrega
    ? date('d/m/Y', strtotime($fecha_entrega))
    : 'Por confirmar';

$fecha_emision = date('d/m/Y');

// ── HTML del email ────────────────────────────────────────────
$html = "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f4;padding:30px 0;'>
<tr><td align='center'>
<table width='620' cellpadding='0' cellspacing='0' style='background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);'>

    <!-- Header -->
    <tr>
        <td style='background:#1F9166;padding:28px 32px;'>
            <table width='100%'><tr>
                <td>
                    <div style='color:white;font-size:20px;font-weight:700;'>INVERSIONES ROJAS 2016 C.A.</div>
                    <div style='color:rgba(255,255,255,.8);font-size:12px;margin-top:3px;'>RIF: J-40888806-8 | Tel: 0243-2343044</div>
                </td>
                <td align='right'>
                    <div style='background:rgba(255,255,255,.18);border-radius:8px;padding:10px 16px;text-align:center;'>
                        <div style='color:rgba(255,255,255,.7);font-size:10px;text-transform:uppercase;letter-spacing:1px;'>Orden de Compra</div>
                        <div style='color:white;font-size:18px;font-weight:700;margin-top:3px;'>{$codigo_compra}</div>
                    </div>
                </td>
            </tr></table>
        </td>
    </tr>

    <!-- Saludo -->
    <tr>
        <td style='padding:28px 32px 0;'>
            <p style='margin:0;font-size:15px;color:#333;'>Estimado/a <strong>" . htmlspecialchars($proveedor_nombre) . "</strong>,</p>
            <p style='margin:12px 0 0;font-size:14px;color:#555;line-height:1.6;'>
                Por medio del presente, le hacemos llegar la siguiente orden de compra emitida el
                <strong>{$fecha_emision}</strong>, con fecha estimada de entrega el <strong>{$fecha_formateada}</strong>.
                Por favor confirme la recepción de este correo y la disponibilidad de los productos.
            </p>
        </td>
    </tr>

    <!-- Tabla de productos -->
    <tr>
        <td style='padding:24px 32px 0;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='border-radius:8px;overflow:hidden;border:1px solid #eee;'>
                <tr style='background:#f8f9fa;'>
                    <th style='padding:10px 12px;text-align:left;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;'>Código</th>
                    <th style='padding:10px 12px;text-align:left;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;'>Producto</th>
                    <th style='padding:10px 12px;text-align:center;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;'>Cant.</th>
                    <th style='padding:10px 12px;text-align:right;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;'>P. Unit.</th>
                    <th style='padding:10px 12px;text-align:right;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;'>Subtotal</th>
                </tr>
                {$filas_productos}
            </table>
        </td>
    </tr>

    <!-- Totales -->
    <tr>
        <td style='padding:16px 32px 0;'>
            <table width='100%'><tr><td width='60%'></td>
            <td>
                <table width='100%' style='font-size:13px;'>
                    <tr>
                        <td style='padding:5px 0;color:#666;'>Subtotal:</td>
                        <td style='padding:5px 0;text-align:right;'>Bs " . number_format($subtotal, 2) . "</td>
                    </tr>
                    <tr>
                        <td style='padding:5px 0;color:#666;'>IVA (16%):</td>
                        <td style='padding:5px 0;text-align:right;'>Bs " . number_format($iva, 2) . "</td>
                    </tr>
                    <tr style='border-top:2px solid #1F9166;'>
                        <td style='padding:10px 0 5px;font-weight:700;font-size:15px;color:#1F9166;'>TOTAL:</td>
                        <td style='padding:10px 0 5px;text-align:right;font-weight:700;font-size:15px;color:#1F9166;'>Bs " . number_format($total_calc, 2) . "</td>
                    </tr>
                </table>
            </td></tr></table>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style='padding:28px 32px;margin-top:24px;'>
            <p style='margin:0;font-size:13px;color:#555;'>Para consultas comuníquese a:</p>
            <p style='margin:6px 0 0;font-size:13px;color:#333;'>
                📞 0243-2343044 &nbsp;|&nbsp;
                📧 2016rojasinversiones@gmail.com
            </p>
            <p style='margin:16px 0 0;font-size:12px;color:#aaa;border-top:1px solid #f0f0f0;padding-top:14px;'>
                Este correo fue generado automáticamente por el sistema de gestión de Inversiones Rojas 2016 C.A.
                &mdash; Emitido por: {$emisor_nombre}
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>";

// ── Texto plano (fallback) ────────────────────────────────────
$texto_plano = "ORDEN DE COMPRA {$codigo_compra} - INVERSIONES ROJAS 2016 C.A.\n\n"
    . "Proveedor: {$proveedor_nombre}\n"
    . "Fecha emisión: {$fecha_emision}\n"
    . "Fecha entrega estimada: {$fecha_formateada}\n\n"
    . "PRODUCTOS:\n";
foreach ($productos as $p) {
    $texto_plano .= "- {$p['nombre']} x{$p['cantidad']} @ Bs " . number_format($p['precio_unitario'] ?? 0, 2) . "\n";
}
$texto_plano .= "\nTOTAL: Bs " . number_format($total_calc, 2) . "\n\n"
    . "Contacto: 0243-2343044 | 2016rojasinversiones@gmail.com";

// ── Enviar vía EmailHelper ────────────────────────────────────
try {
    error_log("[enviar_orden_compra] Llamando a enviarEmailSMTP para {$proveedor_email}");
    $result = enviarEmailSMTP(
        $proveedor_email,
        $proveedor_nombre,
        "Orden de Compra {$codigo_compra} - Inversiones Rojas 2016 C.A.",
        $html
    );

    error_log("[enviar_orden_compra] Resultado: " . json_encode($result));

    if ($result['success']) {
        // Registrar en bitácora
        try {
            $db   = Database::getInstance();
            $db->prepare(
                "INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at)
                 VALUES (?, 'EMAIL_ORDEN_COMPRA', 'compras', ?, ?::jsonb, NOW())"
            )->execute([
                $_SESSION['user_id'],
                $compra_id,
                json_encode(['codigo' => $codigo_compra, 'email_destino' => $proveedor_email])
            ]);
        } catch (Exception $e) {}

        echo json_encode(['success' => true, 'message' => 'Email enviado correctamente a ' . $proveedor_email]);
    } else {
        error_log("[enviar_orden_compra] Fallo SMTP: " . $result['message']);
        echo json_encode(['success' => false, 'message' => 'No se pudo enviar el email: ' . $result['message']]);
    }

} catch (Exception $e) {
    error_log('[enviar_orden_compra] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al enviar email: ' . $e->getMessage()]);
}