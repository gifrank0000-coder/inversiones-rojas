<?php
// /inversiones-rojas/api/create_categoria.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/models/database.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit; }

$nombre      = trim($_POST['nombre']      ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

if (!$nombre) { echo json_encode(['ok'=>false,'error'=>'El nombre es obligatorio']); exit; }

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $chk = $conn->prepare("SELECT id FROM categorias WHERE nombre ILIKE ? LIMIT 1");
    $chk->execute([$nombre]);
    if ($chk->fetch()) { echo json_encode(['ok'=>false,'error'=>'Ya existe una categoría con ese nombre']); exit; }

    $stmt = $conn->prepare("INSERT INTO categorias (nombre, descripcion, estado, created_at, updated_at) VALUES (?, ?, true, NOW(), NOW()) RETURNING id");
    $stmt->execute([$nombre, $descripcion ?: null]);
    $newId = $stmt->fetchColumn();

    echo json_encode(['ok'=>true,'message'=>'Categoría creada correctamente','id'=>$newId]);
} catch (Exception $e) {
    error_log('create_categoria: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Error interno']);
}
