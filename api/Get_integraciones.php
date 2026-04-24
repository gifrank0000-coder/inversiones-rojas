<?php
// ============================================================
// get_integraciones.php  →  /api/get_integraciones.php
// Devuelve el estado actual de cada integración desde la BD
// ============================================================
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success'=>false]));
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    // Verificar que la tabla existe
    $conn->exec("
        CREATE TABLE IF NOT EXISTS configuracion_integraciones (
            clave VARCHAR(80) PRIMARY KEY,
            valor TEXT NOT NULL DEFAULT '',
            updated_at TIMESTAMPTZ DEFAULT NOW()
        )
    ");

    $rows = $conn->query("SELECT clave, valor FROM configuracion_integraciones")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Contar pedidos de hoy por canal de comunicación (si existe la columna)
    $pedidosHoy = 0;
    try {
        $pedidosHoy = (int)$conn->query(
            "SELECT COUNT(*) FROM pedidos_online WHERE DATE(created_at)=CURRENT_DATE"
        )->fetchColumn();
    } catch(Exception $e) {}

    // Contar notificaciones internas pendientes
    $notifPendientes = 0;
    try {
        $notifPendientes = (int)$conn->query(
            "SELECT COUNT(*) FROM notificaciones_vendedor WHERE leida=false"
        )->fetchColumn();
    } catch(Exception $e) {}

    $cfg = fn($k, $def='') => $rows[$k] ?? (defined('INTEGRATION_'.strtoupper($k)) ? constant('INTEGRATION_'.strtoupper($k)) : $def);

    echo json_encode([
        'success' => true,
        'integraciones' => [
            'whatsapp' => [
                'enabled'  => $cfg('whatsapp_enabled','0') === '1',
                'numero'   => $cfg('whatsapp_number',''),
                'pedidos_hoy' => $pedidosHoy,
            ],
            'email' => [
                'enabled'  => $cfg('email_enabled','0') === '1',
                'destino'  => $cfg('email_notifications',''),
            ],
            'telegram' => [
                'enabled'   => $cfg('telegram_enabled','0') === '1',
                'username'  => $cfg('telegram_username',''),
                'chat_id'   => $cfg('telegram_chat_id',''),
                'tiene_token' => !empty($cfg('telegram_bot_token','')),
            ],
            'notificaciones' => [
                'enabled'   => $cfg('internal_notifications_enabled','1') === '1',
                'pendientes' => $notifPendientes,
            ],
        ],
    ]);

} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}