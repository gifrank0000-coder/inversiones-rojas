<?php
// check_rif.php → /api/check_rif.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { echo json_encode(['exists'=>false]); exit; }
$rif        = strtoupper(trim($_GET['rif']        ?? ''));
$exclude_id = (int)($_GET['exclude_id'] ?? 0);
$tabla      = trim($_GET['tabla'] ?? 'proveedores'); // proveedores | clientes
$tabla      = in_array($tabla, ['proveedores','clientes']) ? $tabla : 'proveedores';
if (!$rif) { echo json_encode(['exists'=>false]); exit; }
try {
    $db  = Database::getInstance();
    $sql = $exclude_id
        ? "SELECT 1 FROM {$tabla} WHERE UPPER(rif) = ? AND id != ? LIMIT 1"
        : "SELECT 1 FROM {$tabla} WHERE UPPER(rif) = ? LIMIT 1";
    $params = $exclude_id ? [$rif, $exclude_id] : [$rif];
    $stmt = $db->prepare($sql); $stmt->execute($params);
    echo json_encode(['exists' => (bool)$stmt->fetch()]);
} catch(Exception $e) { echo json_encode(['exists'=>false]); }