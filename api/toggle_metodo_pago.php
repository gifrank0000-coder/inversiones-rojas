<?php
// /inversiones-rojas/api/toggle_metodo_pago.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/models/database.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit; }

$id     = (int)($_POST['id']     ?? 0);
$estado = filter_var($_POST['estado'] ?? '', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

if (!$id || $estado === null) { echo json_encode(['ok'=>false,'error'=>'Parámetros inválidos']); exit; }

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("UPDATE metodos_pago SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);

    if ($stmt->rowCount() === 0) { echo json_encode(['ok'=>false,'error'=>'Registro no encontrado']); exit; }

    echo json_encode(['ok'=>true,'message'=>'Estado actualizado correctamente']);
} catch (Exception $e) {
    error_log('toggle_metodo_pago: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Error interno']);
}
