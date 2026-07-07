<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/models/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : null;
$estado = isset($_POST['estado']) ? ($_POST['estado'] == '1' || $_POST['estado'] === 'true') : true;
$moneda = isset($_POST['moneda']) ? trim($_POST['moneda']) : 'AMBOS';

if (empty($nombre)) {
    echo json_encode(['success' => false, 'message' => 'Nombre es obligatorio']);
    exit;
}

// Validar que moneda sea uno de los valores permitidos
if (!in_array($moneda, ['USD', 'BS', 'AMBOS'])) {
    $moneda = 'AMBOS';
}

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("INSERT INTO metodos_pago (nombre, descripcion, estado, moneda, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP) RETURNING id");
    $stmt->execute([$nombre, $descripcion, $estado, $moneda]);
    $newId = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'id' => (int)$newId,
        'nombre' => $nombre,
        'descripcion' => $descripcion,
        'moneda' => $moneda
    ]);
} catch (Exception $e) {
    error_log('add_metodo_pago error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al guardar el método de pago']);
}

?>