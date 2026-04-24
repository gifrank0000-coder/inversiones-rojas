<?php
// get_reservas.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

// Incluir conexión a base de datos
require_once __DIR__ . '/../app/models/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    // Parámetros de paginación y filtros
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 20; // registros por página
    $offset = ($page - 1) * $per_page;

    // Filtros
    $busqueda = trim($_GET['busqueda'] ?? '');
    $estado = trim($_GET['estado'] ?? '');
    $categoria = trim($_GET['categoria'] ?? '');
    $fecha_desde = trim($_GET['fecha_desde'] ?? '');
    $fecha_hasta = trim($_GET['fecha_hasta'] ?? '');

    // Construir consulta base
    $where_conditions = [];
    $params = [];

    // Filtro de búsqueda (código, cliente, producto)
    if (!empty($busqueda)) {
        $where_conditions[] = "(r.codigo_reserva ILIKE ? OR c.nombre_completo ILIKE ? OR p.nombre ILIKE ?)";
        $search_param = "%{$busqueda}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    // Filtro de estado - por defecto excluir canceladas/inhabilitadas
    if (!empty($estado)) {
        $where_conditions[] = "r.estado_reserva = ?";
        $params[] = $estado;
    } else {
        // Excluir estados cancelados/inactivos por defecto
        $where_conditions[] = "r.estado_reserva NOT IN ('CANCELADA', 'INHABILITADA', 'INACTIVO')";
    }

    // Filtro de categoría
    if (!empty($categoria)) {
        $where_conditions[] = "p.categoria_id = ?";
        $params[] = $categoria;
    }

    // Filtro de fecha desde
    if (!empty($fecha_desde)) {
        $where_conditions[] = "r.fecha_reserva >= ?";
        $params[] = $fecha_desde;
    }

    // Filtro de fecha hasta
    if (!empty($fecha_hasta)) {
        $where_conditions[] = "r.fecha_reserva <= ?";
        $params[] = $fecha_hasta . ' 23:59:59';
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Consulta para contar total de registros
    $count_sql = "
        SELECT COUNT(DISTINCT r.id) as total
        FROM reservas r
        LEFT JOIN clientes c ON r.cliente_id = c.id
        LEFT JOIN productos p ON r.producto_id = p.id
        {$where_clause}
    ";

    $stmt_count = $conn->prepare($count_sql);
    $stmt_count->execute($params);
    $total_records = (int) $stmt_count->fetchColumn();
    $total_pages = ceil($total_records / $per_page);

    // Consulta principal para obtener reservas
    $sql = "
        SELECT
            r.id,
            r.codigo_reserva,
            r.cliente_id,
            c.nombre_completo as cliente_nombre,
            r.producto_id,
            p.nombre as producto_nombre,
            r.cantidad,
            r.fecha_reserva,
            r.fecha_limite,
            r.estado_reserva,
            r.observaciones,
            r.created_at,
            r.updated_at,
            -- Calcular tiempo restante
            CASE
                WHEN r.fecha_limite >= CURRENT_DATE THEN
                    EXTRACT(EPOCH FROM (r.fecha_limite - CURRENT_DATE)) / 86400
                ELSE 0
            END as dias_restantes,
            -- Calcular total
            (r.cantidad * p.precio_venta) as total
        FROM reservas r
        LEFT JOIN clientes c ON r.cliente_id = c.id
        LEFT JOIN productos p ON r.producto_id = p.id
        {$where_clause}
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    $query_params = array_merge($params, [$per_page, $offset]);
    $stmt->execute($query_params);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar resultados
    foreach ($reservas as &$reserva) {
        // Formatear fechas
        $reserva['fecha_reserva_formateada'] = date('d/m/Y', strtotime($reserva['fecha_reserva']));
        $reserva['fecha_limite_formateada'] = date('d/m/Y', strtotime($reserva['fecha_limite']));

        // Determinar estado de tiempo
        $dias_restantes = (int)$reserva['dias_restantes'];
        if ($dias_restantes > 3) {
            $reserva['estado_tiempo'] = 'normal';
        } elseif ($dias_restantes > 0) {
            $reserva['estado_tiempo'] = 'urgente';
        } else {
            $reserva['estado_tiempo'] = 'vencida';
        }

        // Texto de tiempo restante
        if ($dias_restantes < 0) {
            $reserva['tiempo_restante_texto'] = 'Vencida';
        } elseif ($dias_restantes === 0) {
            $reserva['tiempo_restante_texto'] = 'Vence hoy';
        } elseif ($dias_restantes === 1) {
            $reserva['tiempo_restante_texto'] = '1 día';
        } else {
            $reserva['tiempo_restante_texto'] = $dias_restantes . ' días';
        }
    }

    // Información de paginación
    $pagination = [
        'page' => $page,
        'per_page' => $per_page,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'has_prev' => $page > 1,
        'has_next' => $page < $total_pages
    ];

    // Respuesta exitosa
    echo json_encode([
        'ok' => true,
        'data' => $reservas,
        'pagination' => $pagination,
        'filtros_aplicados' => [
            'busqueda' => $busqueda,
            'estado' => $estado,
            'categoria' => $categoria,
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta
        ]
    ]);

} catch (Exception $e) {
    error_log('ERROR al obtener reservas: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor']);
}
?>