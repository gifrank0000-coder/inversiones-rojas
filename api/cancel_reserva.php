<?php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/reserva_mail_helper.php';

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
    $motivo_cancelacion = $input['motivo'] ?? 'Cancelado por el administrador';

    if (!$codigo_reserva) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Código de reserva requerido']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT r.*, c.nombre_completo, c.email FROM reservas r LEFT JOIN clientes c ON r.cliente_id = c.id WHERE r.codigo_reserva = ?");
    $stmt->execute([$codigo_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
        exit;
    }

    if (!in_array($reserva['estado_reserva'], ['PENDIENTE', 'PRORROGADA'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Reserva no puede ser cancelada en estado ' . $reserva['estado_reserva']]);
        exit;
    }

    $conn->beginTransaction();

    try {
        $stmt = $conn->prepare("UPDATE reservas SET estado_reserva = 'CANCELADA', updated_at = NOW() WHERE codigo_reserva = ?");
        $stmt->execute([$codigo_reserva]);

        $conn->commit();

        $email = $reserva['email'] ?? '';
        $nombre_completo = $reserva['nombre_completo'] ?? 'Cliente';
        $partes = explode(' ', $nombre_completo, 2);
        $nombre = $partes[0] ?? '';
        $apellido = $partes[1] ?? '';
        
        $correo_enviado = false;
        if (!empty($email)) {
            $correo_enviado = enviarCorreoReserva(
                $email,
                $nombre,
                $apellido,
                'cancelacion',
                $codigo_reserva,
                [],
                0,
                '',
                $motivo_cancelacion
            );
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "Reserva cancelada exitosamente",
            'email_enviado' => $correo_enviado,
            'data' => [
                'codigo_reserva' => $codigo_reserva,
                'nuevo_estado' => 'CANCELADA',
                'motivo' => $motivo_cancelacion
            ]
        ]);
    } catch (PDOException $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log('Error en cancel_reserva.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Error en cancel_reserva.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
