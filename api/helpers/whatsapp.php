<?php
// Helper simple para enviar mensajes WhatsApp vía Twilio REST API
// Uso: require_once __DIR__ . '/helpers/whatsapp.php';
// Llamar a send_whatsapp_message('+58XXXXXXXXX', 'Mensaje')

if (!defined('TWILIO_ACCOUNT_SID')) {
    // intentar cargar configuración
    if (file_exists(__DIR__ . '/../../config/config.php')) {
        require_once __DIR__ . '/../../config/config.php';
    }
}

function send_whatsapp_message($to_number_e164, $message_body)
{
    // Esperar formato tipo 'whatsapp:+58...'
    if (!defined('TWILIO_WHATSAPP_ENABLED') || !TWILIO_WHATSAPP_ENABLED) {
        error_log('WhatsApp disabled in config; message not sent.');
        return ['success' => false, 'error' => 'WhatsApp disabled'];
    }

    $accountSid = TWILIO_ACCOUNT_SID;
    $authToken = TWILIO_AUTH_TOKEN;
    $from = TWILIO_WHATSAPP_FROM; // debe incluir prefijo 'whatsapp:'

    if (empty($accountSid) || empty($authToken) || empty($from)) {
        error_log('Twilio credentials missing');
        return ['success' => false, 'error' => 'Missing Twilio credentials'];
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";

    $post = http_build_query([
        'From' => $from,
        'To' => $to_number_e164,
        'Body' => $message_body
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_USERPWD, $accountSid . ':' . $authToken);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($result === false) {
        error_log('cURL error sending WhatsApp: ' . $curlErr);
        return ['success' => false, 'error' => $curlErr];
    }

    $decoded = json_decode($result, true);
    if ($httpcode >= 200 && $httpcode < 300 && isset($decoded['sid'])) {
        return ['success' => true, 'sid' => $decoded['sid'], 'response' => $decoded];
    }

    error_log('Twilio WhatsApp error: HTTP ' . $httpcode . ' - ' . $result);
    return ['success' => false, 'http_code' => $httpcode, 'response' => $decoded];
}
