<?php
require_once '../config/config.php';
require_once '../app/models/database.php';

// Asegurarse de tener la sesión activa para verificar permisos de administrador
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Verificar permisos (solo admin)
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 1) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
        exit;
    }

    $results = [];

    // Probar WhatsApp (simular enlace)
    if (defined('INTEGRATION_WHATSAPP_ENABLED') && INTEGRATION_WHATSAPP_ENABLED) {
        $results['WhatsApp'] = !empty(INTEGRATION_WHATSAPP_NUMBER);
    } else {
        $results['WhatsApp'] = 'Deshabilitado';
    }

    // Probar Email
    if (defined('INTEGRATION_EMAIL_ENABLED') && INTEGRATION_EMAIL_ENABLED) {
        $testEmail = defined('INTEGRATION_EMAIL_NOTIFICATIONS') ? INTEGRATION_EMAIL_NOTIFICATIONS : '';
        if (!empty($testEmail)) {
            // Enviar email de prueba
            $subject = 'Prueba de Integración - Inversiones Rojas';
            $message = "Esta es una prueba de integración de email.\n\nEnviado desde: " . $_SERVER['HTTP_HOST'] . "\nFecha: " . date('Y-m-d H:i:s');

            $headers = "From: " . (defined('SMTP_FROM') ? SMTP_FROM : 'noreply@inversionesrojas.com') . "\r\n";
            $headers .= "Reply-To: " . (defined('SMTP_FROM') ? SMTP_FROM : 'noreply@inversionesrojas.com') . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            $emailSent = APP_DEBUG ? true : mail($testEmail, $subject, $message, $headers); // En debug, simular envío
            $results['Email'] = $emailSent ? 'Mensaje enviado' : 'Error al enviar';
        } else {
            $results['Email'] = 'Email no configurado';
        }
    } else {
        $results['Email'] = 'Deshabilitado';
    }

    // Probar Telegram
    if (defined('INTEGRATION_TELEGRAM_ENABLED') && INTEGRATION_TELEGRAM_ENABLED) {
        $botToken = defined('INTEGRATION_TELEGRAM_BOT_TOKEN') ? INTEGRATION_TELEGRAM_BOT_TOKEN : '';
        $chatId = defined('INTEGRATION_TELEGRAM_CHAT_ID') ? INTEGRATION_TELEGRAM_CHAT_ID : '';

        if (!empty($botToken) && !empty($chatId)) {
            // Enviar mensaje de prueba a Telegram
            $message = "🧪 Prueba de integración - Inversiones Rojas\n\nEnviado desde: " . $_SERVER['HTTP_HOST'] . "\nFecha: " . date('Y-m-d H:i:s');

            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $data = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ];

            $options = [
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data),
                ],
            ];

            $context = stream_context_create($options);
            $telegramResult = @file_get_contents($url, false, $context);

            if ($telegramResult) {
                $response = json_decode($telegramResult, true);
                $results['Telegram'] = isset($response['ok']) && $response['ok'] ? 'Mensaje enviado' : 'Error en API';
            } else {
                $results['Telegram'] = 'Error de conexión';
            }
        } else {
            $results['Telegram'] = 'Token o Chat ID no configurados';
        }
    } else {
        $results['Telegram'] = 'Deshabilitado';
    }

    // Notificaciones internas siempre disponibles
    $results['Notificaciones Internas'] = defined('INTEGRATION_INTERNAL_NOTIFICATIONS_ENABLED') && INTEGRATION_INTERNAL_NOTIFICATIONS_ENABLED ? 'Habilitado' : 'Deshabilitado';

    echo json_encode([
        'success' => true,
        'message' => 'Pruebas de integraciones completadas',
        'results' => $results
    ]);

} catch (Exception $e) {
    error_log("Error testing integrations: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>