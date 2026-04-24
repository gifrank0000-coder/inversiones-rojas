<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/models/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$cedula = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;

if (empty($cedula) || empty($nombre)) {
    echo json_encode(['success' => false, 'message' => 'Cédula y nombre son obligatorios']);
    exit;
}

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("INSERT INTO clientes_facturacion (cedula, nombre, telefono, creado_en) VALUES (?, ?, ?, CURRENT_TIMESTAMP) RETURNING id");
    $stmt->execute([$cedula, $nombre, $telefono]);
    $newId = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'id' => (int)$newId,
        'cedula' => $cedula,
        'nombre' => $nombre,
        'telefono' => $telefono
    ]);
} catch (Exception $e) {
    error_log('add_cliente_facturacion error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al guardar el cliente de facturación']);
}

?>