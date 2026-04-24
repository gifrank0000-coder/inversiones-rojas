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
    
    $sql = "SELECT id, cedula_rif, nombre_completo, telefono_principal, telefono_alternativo, email, direccion, estado, fecha_registro
            FROM clientes WHERE 1=1";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (LOWER(nombre_completo) LIKE LOWER(:search) 
                  OR LOWER(cedula_rif) LIKE LOWER(:search) 
                  OR LOWER(email) LIKE LOWER(:search)
                  OR LOWER(telefono_principal) LIKE LOWER(:search))";
        $params[':search'] = "%{$search}%";
    }
    
    if ($estado !== null && $estado !== '') {
        $sql .= " AND estado = :estado";
        $params[':estado'] = $estado === 'true' ? true : false;
    }
    
    if (!empty($fecha_from)) {
        $sql .= " AND fecha_registro >= :fecha_from";
        $params[':fecha_from'] = $fecha_from;
    }
    
    if (!empty($fecha_to)) {
        $sql .= " AND fecha_registro <= :fecha_to";
        $params[':fecha_to'] = $fecha_to;
    }
    
    $sql .= " ORDER BY nombre_completo ASC LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'ok' => true,
        'clientes' => $clientes
    ]);
} catch (Exception $e) {
    error_log('ERROR get_clientes: ' . $e->getMessage());
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}