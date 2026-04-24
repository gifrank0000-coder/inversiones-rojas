<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Cargar dependencias con rutas robustas ────────────────────────────────────
foreach ([
    __DIR__ . '/../config/config.php',
    __DIR__ . '/../../config/config.php',
] as $path) {
    if (file_exists($path)) { require_once $path; break; }
}

foreach ([
    __DIR__ . '/../app/models/database.php',
    __DIR__ . '/../../models/database.php',
    __DIR__ . '/../../app/models/database.php',
] as $path) {
    if (file_exists($path)) { require_once $path; break; }
}

foreach ([
    __DIR__ . '/../app/helpers/EmailHelper.php',
    __DIR__ . '/../../app/helpers/EmailHelper.php',
    __DIR__ . '/helpers/EmailHelper.php',
] as $path) {
    if (file_exists($path)) { require_once $path; break; }
}

// ── Respuesta estandar ────────────────────────────────────────────────────────
function responder($ok, $mensaje, $code = 200) {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'success' => $ok, 'message' => $mensaje, 'error' => $ok ? null : $mensaje]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método no permitido', 405);
}

if (empty($_SESSION['user_id'])) {
    responder(false, 'No autenticado', 401);
}

$input  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id     = isset($input['id'])     ? intval($input['id'])                 : null;
$estado = isset($input['estado']) ? strtoupper(trim($input['estado']))   : null;

if (!$id || $id <= 0)                              responder(false, 'ID de devolución inválido', 400);
if (!in_array($estado, ['APROBADO','RECHAZADO','PENDIENTE'])) responder(false, 'Estado no válido', 400);

try {
    $pdo = Database::getInstance();
    if (!$pdo) responder(false, 'Error de conexión a la base de datos', 500);

    // Verificar que la devolución existe
    $check = $pdo->prepare('SELECT id, estado_devolucion FROM devoluciones WHERE id = :id LIMIT 1');
    $check->execute([':id' => $id]);
    if (!$check->fetch(PDO::FETCH_ASSOC)) responder(false, 'Devolución no encontrada', 404);

    // Actualizar estado
    $pdo->prepare('UPDATE devoluciones SET estado_devolucion = :estado, updated_at = NOW() WHERE id = :id')
        ->execute([':estado' => $estado, ':id' => $id]);

    // ── Email al cliente cuando se APRUEBA ────────────────────────────────────
    if ($estado === 'APROBADO') {
        try {
            $info = $pdo->prepare("
                SELECT  d.codigo_devolucion,
                        d.motivo,
                        d.observaciones,
                        d.cantidad,
                        d.created_at            AS fecha_solicitud,
                        c.nombre_completo,
                        c.email,
                        p.nombre                AS producto_nombre,
                        p.precio_venta,
                        COALESCE(v.codigo_venta, po.codigo_pedido) AS referencia_orden
                FROM  devoluciones d
                LEFT JOIN clientes      c  ON c.id  = d.cliente_id
                LEFT JOIN productos     p  ON p.id  = d.producto_id
                LEFT JOIN ventas        v  ON v.id  = d.venta_id
                LEFT JOIN pedidos_online po ON po.id = d.pedido_id
                WHERE d.id = :id
                LIMIT 1
            ");
            $info->execute([':id' => $id]);
            $row = $info->fetch(PDO::FETCH_ASSOC);

            if ($row && !empty($row['email'])) {
                $toEmail         = $row['email'];
                $toName          = $row['nombre_completo'] ?: 'Cliente';
                $codigo_dev      = $row['codigo_devolucion'] ?? ('DEV-' . $id);
                $subject         = 'Devolución aprobada – ' . $codigo_dev . ' | Inversiones Rojas';
                $fecha_solicitud = !empty($row['fecha_solicitud'])
                                    ? date('d/m/Y', strtotime($row['fecha_solicitud'])) : date('d/m/Y');
                $fecha_aprob     = date('d/m/Y');
                $precio_unit     = floatval($row['precio_venta'] ?? 0);
                $cantidad        = intval($row['cantidad'] ?? 1);
                $monto           = number_format($precio_unit * $cantidad, 2);

                $fila_producto = $row['producto_nombre']
                    ? "<tr>
                           <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;'>"
                               . htmlspecialchars($row['producto_nombre']) . "</td>
                           <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;text-align:center;'>{$cantidad}</td>
                           <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;text-align:right;'>Bs "
                               . number_format($precio_unit, 2) . "</td>
                           <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;text-align:right;font-weight:700;color:#1F9166;'>Bs {$monto}</td>
                       </tr>"
                    : "<tr><td colspan='4' style='padding:9px 12px;color:#888;font-size:13px;'>Sin detalle de producto</td></tr>";

                $ref_html = !empty($row['referencia_orden'])
                    ? "<tr>
                           <td style='padding:5px 0;color:#666;font-size:13px;'>Orden de referencia:</td>
                           <td style='padding:5px 0;text-align:right;font-size:13px;font-weight:600;'>"
                               . htmlspecialchars($row['referencia_orden']) . "</td>
                       </tr>" : '';

                $motivo_html = !empty($row['motivo'])
                    ? "<p style='margin:10px 0 0;font-size:13px;color:#555;'><strong>Motivo:</strong> "
                        . htmlspecialchars($row['motivo']) . "</p>" : '';

                $obs_html = !empty($row['observaciones'])
                    ? "<p style='margin:6px 0 0;font-size:13px;color:#555;'><strong>Observaciones:</strong> "
                        . nl2br(htmlspecialchars($row['observaciones'])) . "</p>" : '';

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
                        <div style='color:rgba(255,255,255,.7);font-size:10px;text-transform:uppercase;letter-spacing:1px;'>Devolución</div>
                        <div style='color:white;font-size:18px;font-weight:700;margin-top:3px;'>{$codigo_dev}</div>
                    </div>
                </td>
            </tr></table>
        </td>
    </tr>

    <!-- Alerta aprobación -->
    <tr>
        <td style='padding:24px 32px 0;'>
            <div style='background:#e8f6f1;border-left:4px solid #1F9166;border-radius:6px;padding:14px 18px;'>
                <div style='font-size:15px;font-weight:700;color:#1F9166;'>✅ Tu devolución ha sido aprobada</div>
                <div style='font-size:12px;color:#555;margin-top:4px;'>Aprobada el {$fecha_aprob} — Solicitud del {$fecha_solicitud}</div>
            </div>
        </td>
    </tr>

    <!-- Saludo -->
    <tr>
        <td style='padding:20px 32px 0;'>
            <p style='margin:0;font-size:15px;color:#333;'>Hola <strong>" . htmlspecialchars($toName) . "</strong>,</p>
            <p style='margin:10px 0 0;font-size:14px;color:#555;line-height:1.6;'>
                Nos complace informarte que tu solicitud de devolución
                <strong>{$codigo_dev}</strong> ha sido revisada y
                <strong style='color:#1F9166;'>aprobada</strong> por nuestro equipo.
            </p>
            {$motivo_html}
            {$obs_html}
        </td>
    </tr>

    <!-- Tabla producto -->
    <tr>
        <td style='padding:20px 32px 0;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='border-radius:8px;overflow:hidden;border:1px solid #eee;'>
                <tr style='background:#f8f9fa;'>
                    <th style='padding:10px 12px;text-align:left;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;'>Producto</th>
                    <th style='padding:10px 12px;text-align:center;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;'>Cant.</th>
                    <th style='padding:10px 12px;text-align:right;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;'>Precio unit.</th>
                    <th style='padding:10px 12px;text-align:right;font-size:12px;color:#888;font-weight:600;text-transform:uppercase;'>Monto a devolver</th>
                </tr>
                {$fila_producto}
            </table>
        </td>
    </tr>

    <!-- Totales -->
    <tr>
        <td style='padding:16px 32px 0;'>
            <table width='100%'><tr><td width='55%'></td>
            <td>
                <table width='100%' style='font-size:13px;'>
                    {$ref_html}
                    <tr style='border-top:2px solid #1F9166;'>
                        <td style='padding:10px 0 5px;font-weight:700;font-size:15px;color:#1F9166;'>Total a devolver:</td>
                        <td style='padding:10px 0 5px;text-align:right;font-weight:700;font-size:15px;color:#1F9166;'>Bs {$monto}</td>
                    </tr>
                </table>
            </td></tr></table>
        </td>
    </tr>

    <!-- Próximos pasos -->
    <tr>
        <td style='padding:20px 32px 0;'>
            <div style='background:#f8f9fa;border-radius:8px;padding:16px 18px;'>
                <div style='font-size:13px;font-weight:700;color:#2c3e50;margin-bottom:8px;'>📋 Próximos pasos</div>
                <ul style='margin:0;padding-left:18px;font-size:13px;color:#555;line-height:1.8;'>
                    <li>Nuestro equipo se pondrá en contacto contigo para coordinar la entrega del producto.</li>
                    <li>El reembolso o cambio se procesará según la política de devoluciones de la tienda.</li>
                    <li>Si tienes dudas, visítanos en tienda o escríbenos.</li>
                </ul>
            </div>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style='padding:24px 32px 28px;'>
            <p style='margin:0;font-size:13px;color:#555;'>Para cualquier consulta comuníquese a:</p>
            <p style='margin:6px 0 0;font-size:13px;color:#333;'>
                📞 0243-2343044 &nbsp;|&nbsp; 📧 2016rojasinversiones@gmail.com
            </p>
            <p style='margin:16px 0 0;font-size:12px;color:#aaa;border-top:1px solid #f0f0f0;padding-top:14px;'>
                Correo generado automáticamente por el sistema de gestión de Inversiones Rojas 2016 C.A.
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>";

                // Enviar email
                if (function_exists('enviarEmailSMTP')) {
                    $res = enviarEmailSMTP($toEmail, $toName, $subject, $html);
                    if (!$res['success']) {
                        error_log('[update_devolucion_estado] Email falló: ' . ($res['message'] ?? ''));
                    } else {
                        error_log('[update_devolucion_estado] Email enviado a: ' . $toEmail);
                    }
                } else {
                    error_log('[update_devolucion_estado] enviarEmailSMTP no disponible — verifica la ruta de EmailHelper.php');
                }
            }
        } catch (Exception $e) {
            // El email nunca bloquea la aprobación
            error_log('[update_devolucion_estado] Error email: ' . $e->getMessage());
        }
    }

    $mensajes = [
        'APROBADO'  => 'Devolución aprobada correctamente',
        'RECHAZADO' => 'Devolución rechazada',
        'PENDIENTE' => 'Devolución marcada como pendiente',
    ];
    responder(true, $mensajes[$estado] ?? 'Estado actualizado correctamente');

} catch (Exception $e) {
    error_log('[update_devolucion_estado] ' . $e->getMessage());
    responder(false, 'Error interno del servidor', 500);
}
?>