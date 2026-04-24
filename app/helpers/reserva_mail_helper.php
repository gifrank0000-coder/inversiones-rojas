<?php
require_once __DIR__ . '/EmailHelper.php';
require_once __DIR__ . '/moneda_helper.php';

function enviarCorreoReserva(
    string $email,
    string $nombre,
    string $apellido,
    string $tipo,
    string $codigo_reserva,
    array $productos = [],
    float $total = 0,
    string $metodo_pago = '',
    string $motivo = ''
): bool {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("[reserva_mail_helper] Email inválido: {$email}");
        return false;
    }

    $nombre_completo = trim($nombre . ' ' . $apellido);
    $empresa = 'Inversiones Rojas 2016 C.A.';
    $from_email = '2016rojasinversiones@gmail.com';
    $from_name = 'Inversiones Rojas';
    $telefono = '0243-2343044';
    $fecha_actual = date('d/m/Y');
    $tasa_cambio = getTasaCambio();

    $filas_productos = '';
    $subtotal = 0;
    foreach ($productos as $p) {
        $nombre_prod = htmlspecialchars($p['producto_nombre'] ?? $p['nombre'] ?? 'Producto');
        $codigo = htmlspecialchars($p['codigo_interno'] ?? $p['codigo'] ?? '-');
        $cant = (int)($p['cantidad'] ?? 0);
        $precio_usd = (float)($p['precio_venta'] ?? $p['precio'] ?? 0);
        $precio_bs = $precio_usd * $tasa_cambio;
        $linea_bs = round($cant * $precio_bs, 2);
        $subtotal += $linea_bs;
        $filas_productos .= "
        <tr>
            <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;'>{$codigo}</td>
            <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;'>{$nombre_prod}</td>
            <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;text-align:center;'>{$cant}</td>
            <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;text-align:right;'>Bs " . number_format($precio_bs, 2) . "</td>
            <td style='padding:9px 12px;border-bottom:1px solid #f0f0f0;font-size:13px;text-align:right;'>Bs " . number_format($linea_bs, 2) . "</td>
        </tr>";
    }

    switch ($tipo) {
        case 'creacion':
            $asunto = "Reserva {$codigo_reserva} creada - Inversiones Rojas";
            $html = construirCorreoReservaHTML($nombre_completo, $codigo_reserva, $filas_productos, $subtotal, $tipo, $empresa, $from_email, $telefono, $fecha_actual);
            break;
        case 'aprobacion':
            $asunto = "Tu reserva {$codigo_reserva} ha sido completada - Inversiones Rojas";
            $html = construirCorreoAprobacionSimpleHTML($nombre_completo, $codigo_reserva, $empresa, $from_email, $telefono, $fecha_actual, $metodo_pago);
            break;
        case 'cancelacion':
            $asunto = "Reserva {$codigo_reserva} cancelada - Inversiones Rojas";
            $html = construirCorreoCancelacionHTML($nombre_completo, $codigo_reserva, $motivo, $empresa, $from_email, $telefono, $fecha_actual);
            break;
        default:
            error_log("[reserva_mail_helper] Tipo inválido: {$tipo}");
            return false;
    }

    error_log("[reserva_mail_helper] Enviando {$tipo} a {$email}, código: {$codigo_reserva}");

    $result = enviarEmailSMTP($email, $nombre_completo, $asunto, $html);

    if ($result['success']) {
        error_log("[reserva_mail_helper] Enviado OK a {$email}");
        return true;
    } else {
        error_log("[reserva_mail_helper] Error: " . $result['message']);
        return false;
    }
}

function construirCorreoReservaHTML(
    string $nombre,
    string $codigo,
    string $filas_productos,
    float $subtotal,
    string $tipo,
    string $empresa,
    string $email_empresa,
    string $telefono,
    string $fecha,
    string $metodo_pago = ''
) {
    $iva = round($subtotal * 0.16, 2);
    $total = round($subtotal + $iva, 2);

    $badge_tipo = $tipo === 'creacion'
        ? '<div style="background:#1F9166;border-radius:8px;padding:10px 16px;display:inline-block;">
            <div style="color:rgba(255,255,255,.7);font-size:10px;text-transform:uppercase;letter-spacing:1px;">Reserva Creada</div>
            <div style="color:white;font-size:18px;font-weight:700;margin-top:3px;">' . $codigo . '</div>
        </div>'
        : '<div style="background:#1F9166;border-radius:8px;padding:10px 16px;display:inline-block;">
            <div style="color:rgba(255,255,255,.7);font-size:10px;text-transform:uppercase;letter-spacing:1px;">Reserva Aprobada</div>
            <div style="color:white;font-size:18px;font-weight:700;margin-top:3px;">' . $codigo . '</div>
        </div>';

    $mensaje_principal = $tipo === 'creacion'
        ? 'Tu reserva ha sido creada exitosamente. A continuación los detalles:'
        : '¡Felicitaciones! Tu reserva ha sido APROBADA. Ya puedes retirar tus productos.';

    return "
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
                    <div style='color:white;font-size:20px;font-weight:700;'>{$empresa}</div>
                    <div style='color:rgba(255,255,255,.8);font-size:12px;margin-top:3px;'>RIF: J-40888806-8 | Tel: {$telefono}</div>
                </td>
                <td align='right'>
                    {$badge_tipo}
                </td>
            </tr></table>
        </td>
    </tr>

    <!-- Saludo -->
    <tr>
        <td style='padding:28px 32px 0;'>
            <p style='margin:0;font-size:15px;color:#333;'>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
            <p style='margin:12px 0 0;font-size:14px;color:#555;line-height:1.6;'>
                {$mensaje_principal}
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
                        <td style='padding:10px 0 5px;text-align:right;font-weight:700;font-size:15px;color:#1F9166;'>Bs " . number_format($total, 2) . "</td>
                    </tr>
                </table>
            </td></tr></table>
        </td>
    </tr>

    <!-- Nota -->
    <tr>
        <td style='padding:24px 32px 0;'>
            <div style='background:#e8f6f1;padding:15px;border-radius:8px;border-left:4px solid #1F9166;'>
                <p style='margin:0;color:#1F9166;font-weight:bold;'>Nota</p>
                <p style='margin:8px 0 0;color:#555;'>Presenta este código en nuestra tienda para retirar tus productos.</p>
            </div>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style='padding:28px 32px;margin-top:24px;'>
            <p style='margin:0;font-size:13px;color:#555;'>Para consultas comuníquese a:</p>
            <p style='margin:6px 0 0;font-size:13px;color:#333;'>
                📞 {$telefono} &nbsp;|&nbsp;
                📧 {$email_empresa}
            </p>
            <p style='margin:16px 0 0;font-size:12px;color:#aaa;border-top:1px solid #f0f0f0;padding-top:14px;'>
                Este correo fue generado automáticamente por el sistema de gestión de {$empresa}
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>";
}

function construirCorreoCancelacionHTML(
    string $nombre,
    string $codigo,
    string $motivo,
    string $empresa,
    string $email_empresa,
    string $telefono,
    string $fecha
) {
    return "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f4;padding:30px 0;'>
<tr><td align='center'>
<table width='620' cellpadding='0' cellspacing='0' style='background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);'>

    <!-- Header -->
    <tr>
        <td style='background:#dc3545;padding:28px 32px;'>
            <table width='100%'><tr>
                <td>
                    <div style='color:white;font-size:20px;font-weight:700;'>{$empresa}</div>
                    <div style='color:rgba(255,255,255,.8);font-size:12px;margin-top:3px;'>RIF: J-40888806-8 | Tel: {$telefono}</div>
                </td>
                <td align='right'>
                    <div style='background:rgba(255,255,255,.18);border-radius:8px;padding:10px 16px;text-align:center;'>
                        <div style='color:rgba(255,255,255,.7);font-size:10px;text-transform:uppercase;letter-spacing:1px;'>Reserva Cancelada</div>
                        <div style='color:white;font-size:18px;font-weight:700;margin-top:3px;'>{$codigo}</div>
                    </div>
                </td>
            </tr></table>
        </td>
    </tr>

    <!-- Saludo -->
    <tr>
        <td style='padding:28px 32px 0;'>
            <p style='margin:0;font-size:15px;color:#333;'>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
            <p style='margin:12px 0 0;font-size:14px;color:#555;line-height:1.6;'>
                Tu reserva <strong>{$codigo}</strong> ha sido <span style='color:#dc3545;font-weight:bold;'>CANCELADA</span>.
            </p>
        </td>
    </tr>

    <!-- Motivo -->
    <tr>
        <td style='padding:24px 32px 0;'>
            <div style='background:#f8d7da;padding:15px;border-radius:8px;border-left:4px solid #dc3545;'>
                <p style='margin:0;color:#721c24;font-weight:bold;'>Motivo de cancelación:</p>
                <p style='margin:8px 0 0;color:#721c24;'>" . htmlspecialchars($motivo) . "</p>
            </div>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style='padding:28px 32px;margin-top:24px;'>
            <p style='margin:0;font-size:13px;color:#555;'>Si deseas más información o realizar una nueva reserva, contáctanos:</p>
            <p style='margin:6px 0 0;font-size:13px;color:#333;'>
                📞 {$telefono} &nbsp;|&nbsp;
                📧 {$email_empresa}
            </p>
            <p style='margin:16px 0 0;font-size:12px;color:#aaa;border-top:1px solid #f0f0f0;padding-top:14px;'>
                Este correo fue generado automáticamente por el sistema de gestión de {$empresa}
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>";
}

function construirCorreoAprobacionSimpleHTML(
    string $nombre,
    string $codigo,
    string $empresa,
    string $email_empresa,
    string $telefono,
    string $fecha,
    string $metodo_pago = ''
) {
    return "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f4;padding:30px 0;'>
<tr><td align='center'>
<table width='620' cellpadding='0' cellspacing='0' style='background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);'>

    <tr>
        <td style='background:#1F9166;padding:28px 32px;'>
            <table width='100%'><tr>
                <td>
                    <div style='color:white;font-size:20px;font-weight:700;'>{$empresa}</div>
                    <div style='color:rgba(255,255,255,.8);font-size:12px;margin-top:3px;'>RIF: J-40888806-8 | Tel: {$telefono}</div>
                </td>
                <td align='right'>
                    <div style='background:rgba(255,255,255,.18);border-radius:8px;padding:10px 16px;text-align:center;'>
                        <div style='color:rgba(255,255,255,.7);font-size:10px;text-transform:uppercase;letter-spacing:1px;'>Proceso Completado</div>
                        <div style='color:white;font-size:18px;font-weight:700;margin-top:3px;'>{$codigo}</div>
                    </div>
                </td>
            </tr></table>
        </td>
    </tr>

    <tr>
        <td style='padding:32px;'>
            <p style='margin:0;font-size:15px;color:#333;'>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
            <p style='margin:15px 0 0;font-size:14px;color:#555;line-height:1.6;'>
                Tu reserva <strong>{$codigo}</strong> ha sido marcada como completada exitosamente por nuestro equipo.
            </p>
            <p style='margin:15px 0 0;font-size:14px;color:#555;line-height:1.6;'>
                Todo está en orden. Gracias por tu compra y por confiar en Inversiones Rojas 2016 C.A.
            </p>
            <p style='margin:15px 0 0;font-size:14px;color:#555;line-height:1.6;'>
                No necesitas hacer nada más. Este es solo un comprobante de cierre.
            </p>
        </td>
    </tr>

    <tr>
        <td style='padding:28px 32px;margin-top:24px;'>
            <p style='margin:0;font-size:13px;color:#555;'>Para cualquier consulta, contáctanos:</p>
            <p style='margin:6px 0 0;font-size:13px;color:#333;'>
                📞 {$telefono} &nbsp;|&nbsp;
                📧 {$email_empresa}
            </p>
            <p style='margin:16px 0 0;font-size:12px;color:#aaa;border-top:1px solid #f0f0f0;padding-top:14px;'>
                Este correo fue generado automáticamente por el sistema de gestión de {$empresa}
                <br>Gracias por tu preferencia.
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>";
}