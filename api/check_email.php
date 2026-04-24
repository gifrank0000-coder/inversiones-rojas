<?php
// check_email.php → /api/check_email.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
header('Content-Type: application/json; charset=utf-8');

// Soporte para enviar email via GET o POST JSON
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
}

$email      = strtolower(trim($_GET['email']      ?? $input['email']      ?? ''));
$exclude_id = (int)($_GET['exclude_id'] ?? $input['exclude_id'] ?? 0);
$tabla      = trim($_GET['tabla'] ?? $input['tabla'] ?? 'usuarios'); // usuarios | clientes | proveedores
$tabla      = in_array($tabla, ['usuarios','clientes','proveedores']) ? $tabla : 'usuarios';
if (!$email) { echo json_encode(['exists'=>false]); exit; }
try {
    $db  = Database::getInstance();
    $sql = $exclude_id
        ? "SELECT 1 FROM {$tabla} WHERE LOWER(email) = ? AND id != ? LIMIT 1"
        : "SELECT 1 FROM {$tabla} WHERE LOWER(email) = ? LIMIT 1";
    $params = $exclude_id ? [$email, $exclude_id] : [$email];
    $stmt = $db->prepare($sql); $stmt->execute($params);
    echo json_encode(['success' => true, 'exists' => (bool)$stmt->fetch()]);
} catch(Exception $e) {
    error_log('[api/check_email.php] ' . $e->getMessage());
    echo json_encode(['success' => false, 'exists' => false]);
}