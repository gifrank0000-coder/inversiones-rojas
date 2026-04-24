<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/models/database.php';

$search = $_GET['search'] ?? '';
$estado = $_GET['estado'] ?? null;
$fecha_from = $_GET['fecha_from'] ?? '';
$fecha_to = $_GET['fecha_to'] ?? '';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $sql = "SELECT id, rif, razon_social, persona_contacto, telefono_principal, telefono_alternativo, email, direccion, estado, created_at
            FROM proveedores WHERE 1=1";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (LOWER(razon_social) LIKE LOWER(:search) 
                  OR LOWER(rif) LIKE LOWER(:search) 
                  OR LOWER(email) LIKE LOWER(:search)
                  OR LOWER(persona_contacto) LIKE LOWER(:search))";
        $params[':search'] = "%{$search}%";
    }
    
    if ($estado !== null && $estado !== '') {
        $sql .= " AND estado = :estado";
        $params[':estado'] = $estado === 'true' ? true : false;
    }
    
    if (!empty($fecha_from)) {
        $sql .= " AND created_at >= :fecha_from";
        $params[':fecha_from'] = $fecha_from . ' 00:00:00';
    }
    
    if (!empty($fecha_to)) {
        $sql .= " AND created_at <= :fecha_to";
        $params[':fecha_to'] = $fecha_to . ' 23:59:59';
    }
    
    $sql .= " ORDER BY razon_social ASC LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'ok' => true,
        'proveedores' => $proveedores
    ]);
} catch (Exception $e) {
    error_log('ERROR get_proveedores: ' . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}