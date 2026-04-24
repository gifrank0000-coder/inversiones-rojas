<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

// Ruta robusta compatible con cualquier ubicación
foreach ([
    __DIR__ . '/../../models/database.php',
    __DIR__ . '/../app/models/database.php',
    __DIR__ . '/../../app/models/database.php',
] as $path) {
    if (file_exists($path)) { require_once $path; break; }
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception('No se pudo conectar a la base de datos');

    // Obtener datos (JSON o POST)
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = intval($data['id'] ?? 0);

    if (!$id) {
        throw new Exception('ID de promoción requerido');
    }

    // Verificar que la promoción exista
    $sql = "SELECT id, nombre FROM promociones WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promo) {
        throw new Exception('Promoción no encontrada');
    }

    // Iniciar transacción
    $conn->beginTransaction();

    // Eliminar asociaciones con productos
    $sql = "DELETE FROM producto_promociones WHERE promocion_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Soft delete: marcar como inactiva en lugar de eliminar
    $sql = "UPDATE promociones SET estado = false, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Registrar en bitácora
    $sql = "INSERT INTO bitacora_sistema (usuario_id, accion, tabla, registro_id, detalles, created_at)
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        'ELIMINAR',
        'promociones',
        $id,
        json_encode(['nombre' => $promo['nombre']])
    ]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Promoción eliminada correctamente'
    ]);

} catch (Exception $e) {
    if ($conn) $conn->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
