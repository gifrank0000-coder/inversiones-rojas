<?php
// /inversiones-rojas/api/update_metodo_pago.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/models/database.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit; }

$id          = (int)($_POST['id']          ?? 0);
$nombre      = trim($_POST['nombre']       ?? '');
$descripcion = trim($_POST['descripcion']  ?? '');
$moneda      = strtoupper(trim($_POST['moneda'] ?? 'AMBOS'));

if (!$id || !$nombre) { echo json_encode(['ok'=>false,'error'=>'ID y nombre son obligatorios']); exit; }
if (!in_array($moneda, ['BS','USD','AMBOS'])) $moneda = 'AMBOS';

try {
    $db   = new Database();
    $conn = $db->getConnection();

    // Nombre único excluyendo el propio registro
    $chk = $conn->prepare("SELECT id FROM metodos_pago WHERE nombre ILIKE ? AND id <> ? LIMIT 1");
    $chk->execute([$nombre, $id]);
    if ($chk->fetch()) { echo json_encode(['ok'=>false,'error'=>'Ya existe otro método de pago con ese nombre']); exit; }

    $stmt = $conn->prepare("UPDATE metodos_pago SET nombre=?, descripcion=?, moneda=? WHERE id=?");
    $stmt->execute([$nombre, $descripcion ?: null, $moneda, $id]);

    if ($stmt->rowCount() === 0) { echo json_encode(['ok'=>false,'error'=>'Registro no encontrado']); exit; }

    echo json_encode(['ok'=>true,'message'=>'Método de pago actualizado correctamente']);
} catch (Exception $e) {
    error_log('update_metodo_pago: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Error interno']);
}
