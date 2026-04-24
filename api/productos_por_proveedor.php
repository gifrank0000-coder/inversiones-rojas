<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'No autenticado']); exit; }

require_once __DIR__ . '/../app/models/database.php';

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
    if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Proveedor inválido']); exit; }

    $db = new Database(); $conn = $db->getConnection(); if (!$conn) throw new Exception('DB no disponible');

    $sql = "SELECT p.id, p.codigo_interno, p.nombre, p.stock_actual, pp.precio_compra, pp.sku_proveedor, pp.tiempo_entrega_dias
            FROM producto_proveedor pp
            JOIN productos p ON p.id = pp.producto_id
            WHERE pp.proveedor_id = ? AND pp.activo = true
            ORDER BY p.nombre ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success'=>true,'productos'=>$productos]);
    exit;

} catch (Exception $e) {
    error_log('Error productos_por_proveedor.php: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error interno']);
    exit;
}

?>
