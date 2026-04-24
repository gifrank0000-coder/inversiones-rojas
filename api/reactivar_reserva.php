<?php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $codigo_reserva = $input['codigo_reserva'] ?? null;

    if (!$codigo_reserva) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Código de reserva requerido']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Verificar que la reserva existe y está en estado CANCELADA
    $stmt = $conn->prepare("SELECT id, estado_reserva FROM reservas WHERE codigo_reserva = ?");
    $stmt->execute([$codigo_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
        exit;
    }

    // Validar estado - solo CANCELADA puede ser reactivada
    if ($reserva['estado_reserva'] !== 'CANCELADA') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Solo reservas canceladas pueden ser reactivadas']);
        exit;
    }

    // Actualizar reserva a PENDIENTE
    $conn->beginTransaction();

    try {
        $stmt = $conn->prepare("UPDATE reservas SET estado_reserva = 'PENDIENTE', updated_at = NOW() WHERE codigo_reserva = ?");
        $stmt->execute([$codigo_reserva]);

        $conn->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "Reserva reactivada exitosamente",
            'data' => [
                'codigo_reserva' => $codigo_reserva,
                'nuevo_estado' => 'PENDIENTE'
            ]
        ]);
    } catch (PDOException $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log('Error en reactivar_reserva.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Error en reactivar_reserva.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>