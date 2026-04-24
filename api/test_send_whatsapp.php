<?php
// Simple script para probar envío WhatsApp vía Twilio helper
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/helpers/whatsapp.php';

// Número de destino (usar formato 'whatsapp:+<códigopaís><número>')
$to = defined('TWILIO_STORE_NUMBER') ? TWILIO_STORE_NUMBER : 'whatsapp:+5804121304526';

$message = "Prueba de WhatsApp desde Inversiones Rojas - mensaje de prueba. Si recibes esto, la integración Twilio funciona correctamente.";

$res = send_whatsapp_message($to, $message);
echo json_encode($res);
