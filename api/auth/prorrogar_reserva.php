<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../../app/models/database.php';
require_once __DIR__ . '/../../config/config.php';

// Sólo vendedores/operadores/administradores pueden prorrogar
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_rol'] ?? null; // ajustar según tu sesión
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];
}

 $reservaId = $input['reserva_id'] ?? null;
 $codigo = $input['codigo_reserva'] ?? null;
// días solicitados (por defecto 3, máximo 3)
$requestedDays = isset($input['days']) ? (int)$input['days'] : 3;

if (!$reservaId && !$codigo) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Requiere reserva_id o codigo_reserva']);
    exit;
}

// validar días (máx 3)
$days = max(1, min(3, $requestedDays));

try {
    $pdo = Database::getInstance();
    if (!$pdo) throw new Exception('No DB');

    $pdo->beginTransaction();

    if ($reservaId) {
        $stmt = $pdo->prepare("SELECT * FROM reservas WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $reservaId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM reservas WHERE codigo_reserva = :cod FOR UPDATE");
        $stmt->execute([':cod' => $codigo]);
    }
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reserva) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
        exit;
    }

    $estado = strtoupper($reserva['estado_reserva'] ?? '');
    if (!in_array($estado, ['ACTIVA','PRORROGADA'])) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Sólo reservas activas o prorrogadas pueden extenderse', 'estado' => $estado]);
        exit;
    }

    // calcular nueva fecha_limite (usar campo fecha_limite si existe)
    $fecha_limite_actual = $reserva['fecha_limite'] ?? null;
    if (!$fecha_limite_actual) {
        // fallback: usar created_at + 7 días
        $fecha_limite_actual = date('Y-m-d', strtotime(($reserva['created_at'] ?? 'now') . ' +7 days'));
    }

    $nueva_fecha = date('Y-m-d', strtotime($fecha_limite_actual . " + {$days} days"));

    $stmtUpd = $pdo->prepare("UPDATE reservas SET fecha_limite = :fecha_limite, estado_reserva = :estado, vendedor_id = :vendedor, updated_at = now() WHERE id = :id");
    $stmtUpd->execute([
        ':fecha_limite' => $nueva_fecha,
        ':estado' => 'PRORROGADA',
        ':vendedor' => $userId,
        ':id' => $reserva['id']
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'reserva_id' => (int)$reserva['id'], 'nueva_fecha_limite' => $nueva_fecha]);
    exit;

} catch (Exception $e) {
    try { if ($pdo && $pdo->inTransaction()) $pdo->rollBack(); } catch (Exception $__) {}
    error_log('prorrogar_reserva error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

?>