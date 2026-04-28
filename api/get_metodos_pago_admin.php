<?php
// /inversiones-rojas/api/get_metodos_pago_admin.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/models/database.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit; }

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $search   = trim($_GET['search']    ?? '');
    $estado   = $_GET['estado']          ?? '';
    $moneda   = trim($_GET['moneda']    ?? '');
    $dateFrom = trim($_GET['fecha_from'] ?? '');
    $dateTo   = trim($_GET['fecha_to']   ?? '');

    $where  = [];
    $params = [];

    if ($search !== '') {
        $where[]  = "(m.nombre ILIKE ? OR m.descripcion ILIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    if ($estado === 'true')  { $where[] = 'm.estado = true';  }
    if ($estado === 'false') { $where[] = 'm.estado = false'; }
    if ($moneda !== '')      { $where[] = 'm.moneda = ?'; $params[] = $moneda; }
    if ($dateFrom !== '')    { $where[] = 'DATE(m.created_at) >= ?'; $params[] = $dateFrom; }
    if ($dateTo !== '')      { $where[] = 'DATE(m.created_at) <= ?'; $params[] = $dateTo; }

    $sql = "SELECT m.* FROM metodos_pago m"
         . (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '')
         . " ORDER BY m.nombre ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'metodos' => $metodos]);

} catch (Exception $e) {
    error_log('get_metodos_pago_admin: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno']);
}
