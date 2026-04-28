<?php
// /inversiones-rojas/api/update_categoria.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/models/database.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit; }

$id          = (int)($_POST['id']          ?? 0);
$nombre      = trim($_POST['nombre']       ?? '');
$descripcion = trim($_POST['descripcion']  ?? '');

if (!$id || !$nombre) { echo json_encode(['ok'=>false,'error'=>'ID y nombre son obligatorios']); exit; }

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $chk = $conn->prepare("SELECT id FROM categorias WHERE nombre ILIKE ? AND id <> ? LIMIT 1");
    $chk->execute([$nombre, $id]);
    if ($chk->fetch()) { echo json_encode(['ok'=>false,'error'=>'Ya existe otra categoría con ese nombre']); exit; }

    $stmt = $conn->prepare("UPDATE categorias SET nombre=?, descripcion=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$nombre, $descripcion ?: null, $id]);

    if ($stmt->rowCount() === 0) { echo json_encode(['ok'=>false,'error'=>'Registro no encontrado']); exit; }

    echo json_encode(['ok'=>true,'message'=>'Categoría actualizada correctamente']);
} catch (Exception $e) {
    error_log('update_categoria: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Error interno']);
}
