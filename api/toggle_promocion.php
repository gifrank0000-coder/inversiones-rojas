<?php
ob_start();
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

header('Content-Type: application/json; charset=utf-8');

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err !== null) {
        @ob_end_clean();
        http_response_code(500);
        $payload = ['success' => false, 'message' => 'Error fatal en el servidor'];
        if (defined('APP_DEBUG') && APP_DEBUG) $payload['debug'] = $err;
        echo json_encode($payload);
        exit;
    }
    @ob_end_flush();
});

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? 'Cliente') === 'Cliente') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$id = (int)$data['id'];

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception('No se pudo conectar a la base de datos');

    // Obtener estado actual
    $stmt = $conn->prepare('SELECT estado FROM promociones WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Promoción no encontrada']);
        exit;
    }

    $current = (int)$row['estado'];
    // Si se manda estado explícito se usa, si no se invierte
    if (isset($data['estado'])) {
        $new = (int)$data['estado'] ? 1 : 0;
    } else {
        $new = $current ? 0 : 1;
    }

    $stmt = $conn->prepare('UPDATE promociones SET estado = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$new, $id]);

    // Bitácora
    $accion = $new ? 'HABILITAR_PROMOCION' : 'INHABILITAR_PROMOCION';
    $stmt = $conn->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles) VALUES (?, ?, 'promociones', ?, ?::jsonb)");
    $stmt->execute([$_SESSION['user_id'], $accion, $id, json_encode(['id'=>$id])]);

    echo json_encode(['success' => true, 'message' => 'Estado actualizado', 'nuevo_estado' => $new]);
    exit;

} catch (PDOException $e) {
    error_log('Error PDO toggle_promocion: ' . $e->getMessage());
    $msg = 'Error de base de datos';
    if (defined('APP_DEBUG') && APP_DEBUG) $msg = $e->getMessage();
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
} catch (Exception $e) {
    error_log('Error toggle_promocion: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

?>
