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
$notas_solucion = isset($input['notas_solucion']) ? trim($input['notas_solucion']) : '';

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing incidencia ID']);
    exit;
}

if (empty($notas_solucion)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Las notas de solución son obligatorias']);
    exit;
}

try {
    $pdo->beginTransaction();

    // CORRECCIÓN: Quitar fecha_resolucion ya que no existe en la tabla
    $upd = $pdo->prepare('UPDATE incidencias_soporte 
                         SET estado = :estado, 
                             notas_solucion = :notas, 
                             updated_at = now()
                         WHERE id = :id');
    $upd->execute([
        ':estado' => 'resuelto', 
        ':notas' => $notas_solucion,
        ':id' => $id
    ]);

    // Registrar seguimiento
    if (isset($_SESSION['user_id']) || isset($_SESSION['usuario_id'])) {
        $usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'];
        $seg = $pdo->prepare('INSERT INTO seguimiento_incidencias 
                             (incidencia_id, usuario_id, accion, descripcion, created_at) 
                             VALUES (:inc, :usr, :acc, :desc, now())');
        $seg->execute([
            ':inc' => $id, 
            ':usr' => $usuario_id, 
            ':acc' => 'resolucion', 
            ':desc' => 'Incidencia marcada como resuelta: ' . substr($notas_solucion, 0, 100)
        ]);
    }

    // Recuperar incidencia actualizada
    $sql = "SELECT i.id, i.codigo_incidencia, i.cliente_id, i.usuario_id, 
                   i.descripcion, i.urgencia, i.modulo_sistema, i.estado, 
                   i.asignado_a, i.notas_solucion,
                   u.nombre_completo AS asignado_nombre, 
                   to_char(i.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at,
                   to_char(i.updated_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS updated_at
            FROM incidencias_soporte i 
            LEFT JOIN usuarios u ON i.asignado_a = u.id 
            WHERE i.id = :id 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'incidencia' => $row,
        'message' => 'Incidencia marcada como resuelta correctamente'
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log('resolve_incidencia error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Error resolviendo incidencia: ' . $e->getMessage()
    ]);
    exit;
}
?>