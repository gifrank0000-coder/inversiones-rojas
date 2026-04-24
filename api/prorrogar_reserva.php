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
    $days = $input['days'] ?? 3;
    
    // Validar días
    $days = (int)$days;
    if ($days < 1 || $days > 3) {
        $days = 3;
    }
    
    if (!$codigo_reserva) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Código de reserva requerido']);
        exit;
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verificar que la reserva existe y está en estado permitido
    $stmt = $conn->prepare("SELECT id, fecha_limite, estado_reserva FROM reservas WHERE codigo_reserva = ?");
    $stmt->execute([$codigo_reserva]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
        exit;
    }
    
    // Validar estado
    if (!in_array($reserva['estado_reserva'], ['PENDIENTE', 'PRORROGADA'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Reserva no puede ser prorrogada en estado ' . $reserva['estado_reserva']]);
        exit;
    }
    
    // Calcular nueva fecha límite
    $fecha_actual = new DateTime($reserva['fecha_limite']);
    $fecha_nueva = $fecha_actual->add(new DateInterval('P' . $days . 'D'));
    $fecha_nueva_str = $fecha_nueva->format('Y-m-d');
    
    // Actualizar reserva
    $conn->beginTransaction();
    
    try {
        $stmt = $conn->prepare("UPDATE reservas SET fecha_limite = ?, estado_reserva = 'PRORROGADA', updated_at = NOW() WHERE codigo_reserva = ?");
        $stmt->execute([$fecha_nueva_str, $codigo_reserva]);
        
        $conn->commit();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "Reserva prorrogada exitosamente hasta $fecha_nueva_str",
            'data' => [
                'codigo_reserva' => $codigo_reserva,
                'nueva_fecha_limite' => $fecha_nueva_str,
                'dias_agregados' => $days
            ]
        ]);
    } catch (PDOException $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Error en prorrogar_reserva.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Error en prorrogar_reserva.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
