<?php
// get_reservas_chart.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/../app/models/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Estadísticas de estados
    $stmt = $conn->prepare("
        SELECT
            COUNT(CASE WHEN estado_reserva = 'ACTIVA' AND fecha_limite >= CURRENT_DATE THEN 1 END) as activas,
            COUNT(CASE WHEN estado_reserva = 'COMPLETADA' THEN 1 END) as completadas,
            COUNT(CASE WHEN estado_reserva = 'CANCELADA' THEN 1 END) as canceladas,
            COUNT(CASE WHEN estado_reserva = 'PRORROGADA' THEN 1 END) as prorrogadas
        FROM reservas
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Tendencia mensual (últimos 6 meses)
    $stmt = $conn->prepare("
        SELECT
            TO_CHAR(fecha_reserva, 'Mon YYYY') as mes,
            COUNT(*) as total
        FROM reservas
        WHERE fecha_reserva >= CURRENT_DATE - INTERVAL '6 months'
        GROUP BY TO_CHAR(fecha_reserva, 'Mon YYYY'), EXTRACT(YEAR FROM fecha_reserva), EXTRACT(MONTH FROM fecha_reserva)
        ORDER BY EXTRACT(YEAR FROM fecha_reserva), EXTRACT(MONTH FROM fecha_reserva)
    ");
    $stmt->execute();
    $tendencia = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'activas' => (int)($stats['activas'] ?? 0),
        'completadas' => (int)($stats['completadas'] ?? 0),
        'canceladas' => (int)($stats['canceladas'] ?? 0),
        'prorrogadas' => (int)($stats['prorrogadas'] ?? 0),
        'tendencia' => $tendencia
    ]);

} catch (Exception $e) {
    error_log('Error en get_reservas_chart.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>