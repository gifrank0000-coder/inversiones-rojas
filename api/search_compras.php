<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/models/database.php';

try {
    $db = new Database(); $conn = $db->getConnection(); if (!$conn) throw new Exception('No DB');

    // Leer parámetros (POST o GET)
    $q = trim($_POST['q'] ?? $_GET['q'] ?? '');
    $date_range = $_POST['date_range'] ?? $_GET['date_range'] ?? '';
    $date_from = $_POST['date_from'] ?? $_GET['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? $_GET['date_to'] ?? '';
    $estado = $_POST['estado'] ?? $_GET['estado'] ?? '';
    $proveedor_id = isset($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : (isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : 0);
    $recent = isset($_POST['recent']) || isset($_GET['recent']);

    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = "(c.codigo_compra ILIKE ? OR p.razon_social ILIKE ? OR u.nombre_completo ILIKE ? )";
        $params[] = "%{$q}%"; $params[] = "%{$q}%"; $params[] = "%{$q}%";
    }

    if ($estado) { $where[] = 'c.estado_compra = ?'; $params[] = $estado; }
    if ($proveedor_id) { $where[] = 'c.proveedor_id = ?'; $params[] = $proveedor_id; }

    if ($date_range && $date_range !== 'custom') {
        if ($date_range === 'today') { $where[] = "DATE(c.created_at) = CURRENT_DATE"; }
        else if ($date_range === 'week') { $where[] = "c.created_at >= CURRENT_DATE - INTERVAL '7 days'"; }
        else if ($date_range === 'month') { $where[] = "DATE_PART('month', c.created_at) = DATE_PART('month', CURRENT_DATE)"; }
    } elseif ($date_range === 'custom' && $date_from && $date_to) {
        $where[] = 'DATE(c.created_at) BETWEEN ? AND ?'; $params[] = $date_from; $params[] = $date_to;
    }

    $sql = "SELECT c.*, p.razon_social as proveedor_nombre, u.nombre_completo as comprador, (SELECT COUNT(*) FROM detalle_compras dc WHERE dc.compra_id = c.id) as productos_count FROM compras c LEFT JOIN proveedores p ON c.proveedor_id = p.id LEFT JOIN usuarios u ON c.usuario_id = u.id";
    if (!empty($where)) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY c.created_at DESC';
    if ($recent) $sql .= ' LIMIT 20';

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $compras = $stmt->fetchAll();

    echo json_encode(['success'=>true, 'compras'=>$compras]); exit;

} catch (Exception $e) {
    error_log('Error search_compras.php: '.$e->getMessage());
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'Error interno']); exit;
}

?>
