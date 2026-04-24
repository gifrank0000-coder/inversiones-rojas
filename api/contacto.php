<?php
require_once __DIR__ . '/../../../config/config.php';
// Cargar productos de la categoría accesorios
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../helpers/promocion_helper.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';
require_once __DIR__ . '/../app/helpers/EmailHelper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$input = json_decode(file_get_contents('php://input'), true);

$nombre = trim($input['nombre'] ?? '');
$email = trim($input['email'] ?? '');
$telefono = trim($input['telefono'] ?? '');
$asunto = trim($input['asunto'] ?? '');
$mensaje = trim($input['mensaje'] ?? '');

// Validaciones
$errores = [];

if (empty($nombre)) {
    $errores[] = 'El nombre es requerido';
}

if (empty($email)) {
    $errores[] = 'El correo electrónico es requerido';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El correo electrónico no es válido';
}

if (empty($asunto)) {
    $errores[] = 'El asunto es requerido';
}

if (empty($mensaje)) {
    $errores[] = 'El mensaje es requerido';
} elseif (strlen($mensaje) < 10) {
    $errores[] = 'El mensaje debe tener al menos 10 caracteres';
}

if (!empty($errores)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Errores de validación', 'errores' => $errores]);
    exit;
}

// Construir el HTML del email (igual que en pedidos)
$telefono_html = !empty($telefono) ? "<p><strong>Teléfono:</strong> {$telefono}</p>" : '';

$html = "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;color:#333;'>
<div style='max-width:580px;margin:0 auto;'>
    <div style='background:#1F9166;padding:20px;border-radius:8px 8px 0 0;text-align:center;'>
        <h2 style='color:white;margin:0;'>INVERSIONES ROJAS 2016 C.A.</h2>
        <p style='color:rgba(255,255,255,0.85);margin:4px 0 0;'>Nuevo Mensaje de Contacto</p>
    </div>
    <div style='padding:22px;background:#f9f9f9;border:1px solid #e0e0e0;border-top:none;'>
        <p>Se recibió un mensaje desde el formulario de contacto.</p>
        <div style='background:#e8f6f1;border-left:4px solid #1F9166;padding:12px;border-radius:6px;margin:14px 0;'>
            <p style='margin:0;'><strong>👤 Nombre:</strong> {$nombre}</p>
            <p style='margin:4px 0 0;'><strong>📧 Email:</strong> {$email}</p>
            {$telefono_html}
            <p style='margin:4px 0 0;'><strong>📋 Asunto:</strong> {$asunto}</p>
        </div>
        <div style='background:#f0f0f0;border-radius:6px;padding:15px;margin-top:15px;'>
            <strong>💬 Mensaje:</strong><br>
            <p style='margin:8px 0 0;white-space:pre-line;'>{$mensaje}</p>
        </div>
    </div>
    <div style='padding:14px;text-align:center;color:#888;font-size:11px;'>
        © Inversiones Rojas 2016 C.A. — 0243-2343044
    </div>
</div></body></html>";

// Enviar el correo usando la misma función que los pedidos
$resultado = enviarEmailSMTP(
    'inversionesroja123@gmail.com',
    'Inversiones Rojas',
    "Mensaje de contacto: {$asunto}",
    $html
);

if ($resultado['success']) {
    echo json_encode(['success' => true, 'message' => 'Mensaje enviado con éxito']);
} else {
    error_log("Error enviando correo de contacto: " . $resultado['message']);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje. Por favor, intenta nuevamente.']);
}
?>