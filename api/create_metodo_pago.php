<?php
// /inversiones-rojas/api/create_metodo_pago.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/models/database.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit; }

$nombre      = trim($_POST['nombre']      ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$moneda      = strtoupper(trim($_POST['moneda'] ?? 'AMBOS'));

if (!$nombre) { echo json_encode(['ok'=>false,'error'=>'El nombre es obligatorio']); exit; }
if (!in_array($moneda, ['BS','USD','AMBOS'])) $moneda = 'AMBOS';

try {
    $db   = new Database();
    $conn = $db->getConnection();

    // Nombre único
    $chk = $conn->prepare("SELECT id FROM metodos_pago WHERE nombre ILIKE ? LIMIT 1");
    $chk->execute([$nombre]);
    if ($chk->fetch()) { echo json_encode(['ok'=>false,'error'=>'Ya existe un método de pago con ese nombre']); exit; }

    $stmt = $conn->prepare("INSERT INTO metodos_pago (nombre, descripcion, moneda, estado, created_at) VALUES (?, ?, ?, true, NOW()) RETURNING id");
    $stmt->execute([$nombre, $descripcion ?: null, $moneda]);
    $newId = $stmt->fetchColumn();

    echo json_encode(['ok'=>true,'message'=>'Método de pago creado correctamente','id'=>$newId]);
} catch (Exception $e) {
    error_log('create_metodo_pago: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Error interno']);
}
