<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

// Ruta robusta compatible con cualquier ubicación dentro del proyecto
foreach ([
    __DIR__ . '/../../models/database.php',
    __DIR__ . '/../app/models/database.php',
    __DIR__ . '/../../app/models/database.php',
] as $path) {
    if (file_exists($path)) { require_once $path; break; }
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception('No se pudo conectar a la base de datos');

    // Parámetros de búsqueda
    $q          = trim($_GET['q'] ?? $_POST['q'] ?? '');
    $estado     = trim($_GET['estado']     ?? $_POST['estado']     ?? '');
    $tipo       = trim($_GET['tipo']       ?? $_POST['tipo']       ?? '');
    $date_from  = trim($_GET['date_from']  ?? $_POST['date_from']  ?? '');
    $date_to    = trim($_GET['date_to']    ?? $_POST['date_to']    ?? '');
    $proximas   = !empty($_GET['proximas']) || !empty($_POST['proximas']);

    // SQL principal
    $sql = "SELECT pr.*,
                   COALESCE((
                       SELECT COUNT(*)
                       FROM producto_promociones pp
                       WHERE pp.promocion_id = pr.id
                   ), 0) AS total_productos,
                   COALESCE((
                       SELECT COUNT(DISTINCT dv.venta_id)
                       FROM detalle_ventas dv
                       INNER JOIN producto_promociones pp ON dv.producto_id = pp.producto_id
                       WHERE pp.promocion_id = pr.id
                   ), 0) AS ventas_generadas,
                   COALESCE((
                       SELECT SUM(dv.subtotal)
                       FROM detalle_ventas dv
                       INNER JOIN producto_promociones pp ON dv.producto_id = pp.producto_id
                       WHERE pp.promocion_id = pr.id
                   ), 0) AS monto_generado
            FROM promociones pr WHERE 1=1 ";

    $params = [];

    // Búsqueda por texto
    if ($q !== '') {
        $sql .= " AND (pr.nombre ILIKE ? OR pr.descripcion ILIKE ?) ";
        $params[] = "%{$q}%";
        $params[] = "%{$q}%";
    }

    // Filtro de estado
    if ($estado !== '') {
        if ($estado === 'activa') {
            $sql .= " AND pr.estado = true AND pr.fecha_inicio <= CURRENT_DATE AND pr.fecha_fin >= CURRENT_DATE ";
        } elseif ($estado === 'inactiva') {
            $sql .= " AND (pr.estado = false OR pr.fecha_fin < CURRENT_DATE) ";
        } elseif ($estado === 'programada') {
            $sql .= " AND pr.estado = true AND pr.fecha_inicio > CURRENT_DATE ";
        }
    }

    // Filtro de tipo
    if ($tipo !== '') {
        $sql .= " AND LOWER(pr.tipo_promocion) = ? ";
        $params[] = strtolower($tipo);
    }

    // Filtro de fechas
    if ($date_from !== '' && $date_to !== '') {
        $sql .= " AND pr.fecha_inicio <= ? AND pr.fecha_fin >= ? ";
        $params[] = $date_to;
        $params[] = $date_from;
    }

    // Próximas a vencer
    if ($proximas) {
        $sql .= " AND pr.estado = true AND pr.fecha_fin BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '7 days' ";
    }

    $sql .= ' ORDER BY pr.created_at DESC';

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'promociones' => $promociones]);
    exit;

} catch (Exception $e) {
    error_log('[search_promociones] ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al buscar promociones: ' . $e->getMessage()
    ]);
    exit;
}
?>