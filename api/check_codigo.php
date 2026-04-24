<?php
// check_codigo.php → /api/check_codigo.php
// Verifica si un código de producto ya existe
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['exists'=>false]); exit; }
$codigo     = trim($_GET['codigo']     ?? '');
$exclude_id = (int)($_GET['exclude_id'] ?? 0);
if (!$codigo) { echo json_encode(['exists'=>false]); exit; }
try {
    $db  = Database::getInstance();
    $sql = $exclude_id
        ? "SELECT 1 FROM productos WHERE codigo_interno = ? AND id != ? LIMIT 1"
        : "SELECT 1 FROM productos WHERE codigo_interno = ? LIMIT 1";
    $params = $exclude_id ? [$codigo, $exclude_id] : [$codigo];
    $stmt = $db->prepare($sql); $stmt->execute($params);
    echo json_encode(['exists' => (bool)$stmt->fetch()]);
} catch(Exception $e) { echo json_encode(['exists'=>false]); }