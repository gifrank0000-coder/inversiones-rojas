<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
        exit;
    }

    // Leer parámetros (POST o JSON)
    $params = $_POST;
    if (empty($params)) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $params = $decoded;
        }
    }

    // Debug: log parameters
    error_log("search_sales.php - Params received: " . json_encode($params));

    $q = isset($params['q']) ? trim($params['q']) : '';
    $dateRange = isset($params['date_range']) ? trim($params['date_range']) : '';
    $dateFrom = isset($params['date_from']) ? trim($params['date_from']) : '';
    $dateTo = isset($params['date_to']) ? trim($params['date_to']) : '';
    $estado  = isset($params['estado']) ? trim($params['estado']) : '';
    $categoriaId = isset($params['categoria']) ? (int)$params['categoria'] : null;
    $metodoPago = isset($params['metodo_pago']) ? trim($params['metodo_pago']) : '';
    $recent  = isset($params['recent']) ? (bool)$params['recent'] : false;

    $where = ['1=1'];
    $bindings = [];

    if ($q !== '') {
        $where[] = '(v.codigo_venta ILIKE :q OR c.nombre_completo ILIKE :q)';
        $bindings[':q'] = '%' . $q . '%';
    }

    // Rangos de fecha
    if ($recent) {
        // Habilitado sólo para cargar ventas recientes
    } elseif ($dateRange === 'today') {
        $where[] = "DATE(v.created_at) = CURRENT_DATE";
        error_log("Applied today filter: DATE(v.created_at) = CURRENT_DATE");
    } elseif ($dateRange === 'yesterday') {
        $where[] = "DATE(v.created_at) = CURRENT_DATE - INTERVAL '1 day'";
        error_log("Applied yesterday filter: DATE(v.created_at) = CURRENT_DATE - INTERVAL '1 day'");
    } elseif ($dateRange === 'week') {
        $where[] = "v.created_at >= CURRENT_DATE - INTERVAL '7 days'";
        error_log("Applied week filter: v.created_at >= CURRENT_DATE - INTERVAL '7 days'");
    } elseif ($dateRange === 'month') {
        $where[] = "DATE_PART('month', v.created_at) = DATE_PART('month', CURRENT_DATE) AND DATE_PART('year', v.created_at) = DATE_PART('year', CURRENT_DATE)";
        error_log("Applied month filter");
    } elseif ($dateRange === 'last_month') {
        $where[] = "DATE_PART('month', v.created_at) = DATE_PART('month', CURRENT_DATE - INTERVAL '1 month') AND DATE_PART('year', v.created_at) = DATE_PART('year', CURRENT_DATE - INTERVAL '1 month')";
        error_log("Applied last_month filter");
    } elseif ($dateRange === 'custom' && $dateFrom && $dateTo) {
        $where[] = 'DATE(v.created_at) BETWEEN :dateFrom AND :dateTo';
        $bindings[':dateFrom'] = $dateFrom;
        $bindings[':dateTo'] = $dateTo;
        error_log("Applied custom filter: $dateFrom to $dateTo");
    } else {
        error_log("No date filter applied. dateRange: '$dateRange', dateFrom: '$dateFrom', dateTo: '$dateTo'");
    }

    if ($estado !== '') {
        $where[] = 'v.estado_venta = :estado';
        $bindings[':estado'] = $estado;
    }

    if ($categoriaId) {
        // Filtrar ventas que incluyan al menos un producto de la categoría seleccionada
        $where[] = "EXISTS (
            SELECT 1
            FROM detalle_ventas dv
            JOIN productos p ON dv.producto_id = p.id
            WHERE dv.venta_id = v.id AND p.categoria_id = :categoria_id
        )";
        $bindings[':categoria_id'] = $categoriaId;
    }

    if ($metodoPago !== '') {
        $where[] = 'v.metodo_pago_id = :metodo_pago';
        $bindings[':metodo_pago'] = $metodoPago;
    }

    $whereSql = implode(' AND ', $where);

    $sql = "SELECT v.id, v.codigo_venta, v.created_at, v.total, v.estado_venta, 
                   c.nombre_completo AS cliente_nombre, 
                   mp.nombre AS metodo_pago_nombre, 
                   u.nombre_completo AS vendedor
            FROM ventas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN metodos_pago mp ON v.metodo_pago_id = mp.id
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            WHERE $whereSql
            ORDER BY v.created_at DESC";

    if ($recent) {
        $sql .= ' LIMIT 10';
    } else {
        $sql .= ' LIMIT 200';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($bindings);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'ventas' => $ventas]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
