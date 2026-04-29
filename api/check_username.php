<?php
// check_username.php → /api/check_username.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

$username   = trim($_GET['username'] ?? '');
$exclude_id = (int)($_GET['exclude_id'] ?? 0);

if (!$username) {
    echo json_encode(['exists' => false]);
    exit;
}

try {
    $db = Database::getInstance();
    $sql = $exclude_id
        ? "SELECT 1 FROM usuarios WHERE LOWER(username) = LOWER(?) AND id != ? LIMIT 1"
        : "SELECT 1 FROM usuarios WHERE LOWER(username) = LOWER(?) LIMIT 1";
    $params = $exclude_id ? [$username, $exclude_id] : [$username];
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode(['exists' => (bool)$stmt->fetch()]);
    
} catch(Exception $e) {
    error_log('Error en check_username.php: ' . $e->getMessage());
    echo json_encode(['exists' => false]);
}
?>