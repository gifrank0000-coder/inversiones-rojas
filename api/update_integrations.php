<?php
// ============================================================
// update_integrations.php  →  /api/update_integrations.php
// Guarda la configuración de integraciones en la BD
// Tabla: configuracion_integraciones (clave → valor)
// ============================================================
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success'=>false,'message'=>'No autenticado']));
}

// Solo admins pueden cambiar integraciones
$rolPermitido = ['Administrador','administrador','Admin','admin'];
if (!in_array($_SESSION['user_rol'] ?? '', $rolPermitido)) {
    http_response_code(403);
    die(json_encode(['success'=>false,'message'=>'Sin permisos']));
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    // Crear tabla si no existe
    $conn->exec("
        CREATE TABLE IF NOT EXISTS configuracion_integraciones (
            clave   VARCHAR(80) PRIMARY KEY,
            valor   TEXT        NOT NULL DEFAULT '',
            updated_at TIMESTAMPTZ DEFAULT NOW()
        )
    ");

    // Recoger todos los campos del POST o JSON
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        if ($raw) $input = json_decode($raw, true) ?? [];
    }

    // Mapa clave → campo POST
    $campos = [
        'whatsapp_number'                   => $input['whatsapp_number']                   ?? '',
        'whatsapp_enabled'                  => isset($input['whatsapp_enabled']) ? '1' : '0',
        'email_notifications'               => $input['email_notifications']               ?? '',
        'email_from'                        => $input['email_from']                        ?? '',
        'email_enabled'                     => isset($input['email_enabled'])  ? '1' : '0',
        'telegram_bot_token'                => $input['telegram_bot_token']                ?? '',
        'telegram_chat_id'                  => $input['telegram_chat_id']                  ?? '',
        'telegram_username'                 => $input['telegram_username']                 ?? '',
        'telegram_enabled'                  => isset($input['telegram_enabled']) ? '1' : '0',
        'internal_notifications_enabled'    => isset($input['internal_notifications_enabled']) ? '1' : '1',
        'auto_assign_vendors'               => isset($input['auto_assign_vendors']) ? '1' : '0',
    ];

    $stmt = $conn->prepare("
        INSERT INTO configuracion_integraciones (clave, valor, updated_at)
        VALUES (:clave, :valor, NOW())
        ON CONFLICT (clave) DO UPDATE SET valor = EXCLUDED.valor, updated_at = NOW()
    ");

    foreach ($campos as $clave => $valor) {
        $stmt->execute([':clave' => $clave, ':valor' => $valor]);
    }

    // Bitácora
    try {
        $conn->prepare(
            "INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, detalles, created_at)
             VALUES (?, 'ACTUALIZAR_INTEGRACIONES', 'configuracion_integraciones', ?::jsonb, NOW())"
        )->execute([$_SESSION['user_id'], json_encode(array_keys($campos))]);
    } catch(Exception $e) {}

    echo json_encode(['success'=>true,'message'=>'Configuración guardada correctamente']);

} catch (Exception $e) {
    error_log('[update_integrations] '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}