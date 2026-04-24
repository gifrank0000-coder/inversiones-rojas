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

// Sólo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$descripcion = trim($input['descripcion'] ?? '');
$urgencia = $input['urgencia'] ?? 'media';
$modulo = $input['modulo_sistema'] ?? null;

if ($descripcion === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Descripcion vacia']);
    exit;
}

$current_user_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? null;
$cliente_id = null;
if ($current_user_id) {
    try {
        $stmt = $pdo->prepare('SELECT cliente_id FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $current_user_id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r && $r['cliente_id']) $cliente_id = $r['cliente_id'];
    } catch (PDOException $e) {
        // continuar sin cliente_id
    }
}

try {
    // Usar transacción: insertar fila, después actualizar codigo_incidencia basado en id
    $pdo->beginTransaction();

    $ins = $pdo->prepare('INSERT INTO incidencias_soporte (codigo_incidencia, cliente_id, usuario_id, descripcion, urgencia, modulo_sistema, estado, created_at, updated_at) VALUES (:codigo, :cliente_id, :usuario_id, :descripcion, :urgencia, :modulo, :estado, now(), now()) RETURNING id');
    // temporal codigo vacío, lo actualizaremos
    $tempCodigo = '';
    $ins->execute([
        ':codigo' => $tempCodigo,
        ':cliente_id' => $cliente_id,
        ':usuario_id' => $current_user_id,
        ':descripcion' => $descripcion,
        ':urgencia' => $urgencia,
        ':modulo' => $modulo,
        ':estado' => 'pendiente'
    ]);
    $new = $ins->fetch(PDO::FETCH_ASSOC);
    $newId = $new['id'] ?? null;
    if (!$newId) throw new Exception('No id after insert');

    // Generar codigo legible: INC-###
    $codigo = 'INC-' . str_pad($newId, 3, '0', STR_PAD_LEFT);
    $upd = $pdo->prepare('UPDATE incidencias_soporte SET codigo_incidencia = :codigo WHERE id = :id');
    $upd->execute([':codigo' => $codigo, ':id' => $newId]);

    // Recuperar fila completa
    $stmt = $pdo->prepare("SELECT i.id, i.codigo_incidencia, i.cliente_id, i.usuario_id, i.descripcion, i.urgencia, i.modulo_sistema, i.estado, i.asignado_a, i.notas_solucion, to_char(i.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at FROM incidencias_soporte i WHERE i.id = :id LIMIT 1");
    $stmt->execute([':id' => $newId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    echo json_encode(['success' => true, 'incidencia' => $row]);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    error_log('add_incidencia error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error saving incidencia']);
    exit;
}

?>