<?php
session_start();
require_once __DIR__ . '/../app/models/database.php';
header('Content-Type: application/json');

$pdo = \Database::getInstance();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = isset($input['id']) ? intval($input['id']) : 0;
$operador_id = isset($input['operador_id']) ? intval($input['operador_id']) : 0;

if (!$id || !$operador_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing parameters: id='.$id.', operador_id='.$operador_id]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Actualizar la incidencia
    $upd = $pdo->prepare('UPDATE incidencias_soporte 
                         SET asignado_a = :opid, estado = :estado, updated_at = now() 
                         WHERE id = :id');
    $upd->execute([
        ':opid' => $operador_id, 
        ':estado' => 'proceso', 
        ':id' => $id
    ]);

    // Recuperar fila actualizada (CORREGIDO EL ESCAPE DE COMILLAS)
    $sql = "SELECT i.id, i.codigo_incidencia, i.cliente_id, i.usuario_id, 
                   i.descripcion, i.urgencia, i.modulo_sistema, i.estado, 
                   i.asignado_a, u.nombre_completo AS asignado_nombre, 
                   to_char(i.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at 
            FROM incidencias_soporte i 
            LEFT JOIN usuarios u ON i.asignado_a = u.id 
            WHERE i.id = :id 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Registrar seguimiento
    if (isset($_SESSION['user_id']) || isset($_SESSION['usuario_id'])) {
        $usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'];
        $seg = $pdo->prepare('INSERT INTO seguimiento_incidencias 
                             (incidencia_id, usuario_id, accion, descripcion, created_at) 
                             VALUES (:inc, :usr, :acc, :desc, now())');
        $seg->execute([
            ':inc' => $id, 
            ':usr' => $usuario_id, 
            ':acc' => 'asignacion', 
            ':desc' => 'Incidencia asignada al operador ID ' . $operador_id
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'incidencia' => $row,
        'message' => 'Incidencia asignada correctamente'
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log('assign_incidencia error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Error asignando incidencia: ' . $e->getMessage()
    ]);
    exit;
}
?>