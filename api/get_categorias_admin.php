<?php
// /inversiones-rojas/api/get_categorias_admin.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/models/database.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit; }

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $search   = trim($_GET['search']    ?? '');
    $estado   = $_GET['estado']          ?? '';
    $dateFrom = trim($_GET['fecha_from'] ?? '');
    $dateTo   = trim($_GET['fecha_to']   ?? '');

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = "(c.nombre ILIKE ? OR c.descripcion ILIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    if ($estado === 'true')  { $where[] = 'c.estado = true';  }
    if ($estado === 'false') { $where[] = 'c.estado = false'; }
    if ($dateFrom !== '')    { $where[] = 'DATE(c.created_at) >= ?'; $params[] = $dateFrom; }
    if ($dateTo !== '')      { $where[] = 'DATE(c.created_at) <= ?'; $params[] = $dateTo; }

    $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM productos p WHERE p.categoria_id = c.id AND p.estado = true) AS total_productos
            FROM categorias c"
         . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
         . " ORDER BY c.nombre ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'categorias' => $categorias]);

} catch (Exception $e) {
    error_log('get_categorias_admin: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno']);
}
