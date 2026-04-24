<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

// Autenticación mínima
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/../app/models/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
    exit;
}

$new_status = null;
if (isset($_POST['new_status'])) {
    $v = $_POST['new_status'];
    $new_status = ($v === '1' || $v === 'true' || $v === 't');
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception('No connection to DB');

    if ($new_status === null) {
        // Obtener estado actual y alternar
        $q = $conn->prepare('SELECT estado FROM productos WHERE id = ?');
        $q->execute([$id]);
        $cur = $q->fetchColumn();
        $new_status = !$cur;
    }

    $upd = $conn->prepare('UPDATE productos SET estado = ? , updated_at = NOW() WHERE id = ?');
    $upd->execute([$new_status ? 't' : 'f', $id]);

    echo json_encode(['success' => true, 'new_status' => $new_status]);
    exit;
} catch (Exception $e) {
    error_log('ERROR toggle_product.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno al cambiar estado']);
    exit;
}
