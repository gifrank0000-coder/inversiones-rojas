<?php
// inventory_stats.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) session_start();

// Verificar autenticación
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Intentar cargar la conexión a la base de datos
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/moneda_helper.php';

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo conectar a la base de datos']);
    exit;
}

try {
    // Parámetros de filtrado
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $type = isset($_GET['type']) && $_GET['type'] !== '' ? trim($_GET['type']) : null;
    $category = isset($_GET['category']) && $_GET['category'] !== '' ? $_GET['category'] : null;
    $estado = isset($_GET['estado']) && $_GET['estado'] !== '' ? $_GET['estado'] : 'active';

    // Período para el gráfico de movimiento de stock (meses)
    $period = isset($_GET['period']) ? intval($_GET['period']) : 3;
    if (!in_array($period, [3, 6, 12], true)) {
        $period = 3;
    }

    // Rango de fechas para filtrar productos
    $dateRange = isset($_GET['date_range']) ? trim($_GET['date_range']) : '';
    $createdFrom = isset($_GET['created_from']) ? trim($_GET['created_from']) : null;
    $createdTo = isset($_GET['created_to']) ? trim($_GET['created_to']) : null;

    // Debug: log all parameters
    error_log("inventory_stats.php - Parameters received:");
    error_log("  q: '$q'");
    error_log("  category: '$category'");
    error_log("  estado: '$estado'");
    error_log("  period: $period");
    error_log("  dateRange: '$dateRange'");
    error_log("  createdFrom: '$createdFrom'");
    error_log("  createdTo: '$createdTo'");

    // Procesar rangos de fecha predefinidos
    if ($dateRange === 'today') {
        $createdFrom = date('Y-m-d');
        $createdTo = date('Y-m-d');
        error_log("Applied today filter: $createdFrom to $createdTo");
    } elseif ($dateRange === 'yesterday') {
        $createdFrom = date('Y-m-d', strtotime('-1 day'));
        $createdTo = date('Y-m-d', strtotime('-1 day'));
        error_log("Applied yesterday filter: $createdFrom to $createdTo");
    } elseif ($dateRange === 'week') {
        $createdFrom = date('Y-m-d', strtotime('-7 days'));
        $createdTo = date('Y-m-d');
        error_log("Applied week filter: $createdFrom to $createdTo");
    } elseif ($dateRange === 'month') {
        $createdFrom = date('Y-m-01');
        $createdTo = date('Y-m-d');
        error_log("Applied month filter: $createdFrom to $createdTo");
    } elseif ($dateRange === 'last_month') {
        $createdFrom = date('Y-m-01', strtotime('first day of last month'));
        $createdTo = date('Y-m-t', strtotime('last month'));
        error_log("Applied last_month filter: $createdFrom to $createdTo");
    } else {
        error_log("No predefined date filter. dateRange: '$dateRange'");
    }

    // Construir SQL dinámico
    $where = [];
    $params = [];

    if ($estado === 'active') {
        $where[] = 'p.estado = true';
    } elseif ($estado === 'inactive') {
        $where[] = 'p.estado = false';
    }

    if ($q !== '') {
        $where[] = "(p.nombre ILIKE :q OR p.codigo_interno ILIKE :q OR p.descripcion ILIKE :q)";
        $params[':q'] = '%' . $q . '%';
    }

    if (!is_null($category)) {
        if (is_numeric($category)) {
            $where[] = 'p.categoria_id = :catid';
            $params[':catid'] = intval($category);
        }
    }

    // Filtrar por tipo de producto (vehiculo, repuesto, accesorio)
    if (!is_null($type)) {
        $type = strtolower($type);
        $tipo_mapping = [
            'vehiculo' => 'VEHICULO',
            'repuesto' => 'REPUESTO',
            'accesorio' => 'ACCESORIO'
        ];
        if (isset($tipo_mapping[$type])) {
            $tipo_nombre = $tipo_mapping[$type];
            // Obtener el tipo_id de la tabla tipos_producto
            $stmtTipo = $conn->prepare("SELECT id FROM tipos_producto WHERE LOWER(nombre) = LOWER(:tipo)");
            $stmtTipo->execute([':tipo' => $tipo_nombre]);
            $tipo_id = $stmtTipo->fetchColumn();
            if ($tipo_id) {
                $where[] = 'p.tipo_id = :tipo_id';
                $params[':tipo_id'] = $tipo_id;
            }
        }
    }

    // Filtrar por rango de fechas de creación (opcional)
    if ($createdFrom) {
        $date = DateTime::createFromFormat('Y-m-d', $createdFrom);
        if ($date) {
            $where[] = 'p.created_at >= :created_from';
            $params[':created_from'] = $date->format('Y-m-d') . ' 00:00:00';
        }
    }
    if ($createdTo) {
        $date = DateTime::createFromFormat('Y-m-d', $createdTo);
        if ($date) {
            $where[] = 'p.created_at <= :created_to';
            $params[':created_to'] = $date->format('Y-m-d') . ' 23:59:59';
        }
    }

    $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Debug: log applied filters
    error_log("inventory_stats.php - Applied filters:");
    error_log("  WHERE clause: '$whereSql'");
    error_log("  Params: " . json_encode($params));

    // Consulta principal de productos (proveedor preferido obtenido desde producto_proveedor)
    $sql = "SELECT 
                p.id, 
                p.codigo_interno, 
                p.nombre, 
                p.descripcion, 
                p.stock_actual, 
                p.stock_minimo, 
                p.stock_maximo, 
                p.precio_compra, 
                p.precio_compra_bs, 
                p.precio_compra_usd,
                p.precio_venta, 
                p.precio_venta_bs, 
                p.precio_venta_usd, 
                p.estado, 
                p.created_at, 
                p.updated_at,
                p.categoria_id,
                COALESCE(pp.proveedor_id, p.proveedor_id) as proveedor_id,
                c.id as cat_id, 
                c.nombre as categoria_nombre,
                pr.razon_social as proveedor_nombre,
                tp.nombre as tipo_nombre
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN producto_proveedor pp ON pp.producto_id = p.id AND pp.es_principal = true AND pp.activo = true
            LEFT JOIN proveedores pr ON pp.proveedor_id = pr.id
            LEFT JOIN tipos_producto tp ON p.tipo_id = tp.id
            {$whereSql}
            ORDER BY p.nombre ASC
            LIMIT 1000";

    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rowsHtml = '';
    
    if (empty($products)) {
        // Si no hay productos, mostrar mensaje
        $rowsHtml = '<tr><td colspan="11" style="text-align: center; padding: 40px; color: #666;">
            <i class="fas fa-box-open fa-2x"></i>
            <p style="margin-top: 10px;">No hay productos registrados</p>
        </td></tr>';
    } else {
        // Obtener IDs de productos para consultas adicionales
        $productIds = array_column($products, 'id');
        $productImages = [];
        $productDetails = [];
        
        // 1. Obtener imágenes principales si hay productos
        if (!empty($productIds)) {
            $placeholders = [];
            foreach ($productIds as $k => $v) {
                $placeholders[] = ':id' . $k;
            }
            $inClause = implode(',', $placeholders);
            
            $imgSql = "SELECT DISTINCT ON (producto_id) producto_id, imagen_url 
                      FROM producto_imagenes 
                      WHERE producto_id IN ({$inClause}) 
                      ORDER BY producto_id, es_principal DESC, orden ASC, id ASC";
            $imgStmt = $conn->prepare($imgSql);
            foreach ($productIds as $k => $v) {
                $imgStmt->bindValue(':id' . $k, intval($v), PDO::PARAM_INT);
            }
            $imgStmt->execute();
            $imgs = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($imgs as $im) {
                $productImages[intval($im['producto_id'])] = $im['imagen_url'];
            }
        }

        // 2. Obtener información específica de productos según su tipo
        foreach ($productIds as $productId) {
            $productDetails[$productId] = null;
            
            // Verificar si es vehículo
            $vehSql = "SELECT * FROM vehiculos WHERE producto_id = :id LIMIT 1";
            $vehStmt = $conn->prepare($vehSql);
            $vehStmt->bindValue(':id', $productId, PDO::PARAM_INT);
            $vehStmt->execute();
            $vehiculo = $vehStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($vehiculo) {
                $productDetails[$productId] = [
                    'tipo' => 'vehiculo',
                    'datos' => $vehiculo
                ];
                continue;
            }
            
            // Verificar si es repuesto
            $repSql = "SELECT * FROM repuestos WHERE producto_id = :id LIMIT 1";
            $repStmt = $conn->prepare($repSql);
            $repStmt->bindValue(':id', $productId, PDO::PARAM_INT);
            $repStmt->execute();
            $repuesto = $repStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($repuesto) {
                $productDetails[$productId] = [
                    'tipo' => 'repuesto',
                    'datos' => $repuesto
                ];
                continue;
            }
            
            // Verificar si es accesorio
            $accSql = "SELECT * FROM accesorios WHERE producto_id = :id LIMIT 1";
            $accStmt = $conn->prepare($accSql);
            $accStmt->bindValue(':id', $productId, PDO::PARAM_INT);
            $accStmt->execute();
            $accesorio = $accStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($accesorio) {
                $productDetails[$productId] = [
                    'tipo' => 'accesorio',
                    'datos' => $accesorio
                ];
            }
        }

        // 3. Generar filas HTML para la tabla
        foreach ($products as $prod) {
            $estadoLabel = $prod['estado'] ? 'Activo' : 'Inactivo';
            $estadoClass = $prod['estado'] ? 'status-active' : 'status-inactive';

            $codigo = htmlspecialchars($prod['codigo_interno'] ?? '', ENT_QUOTES, 'UTF-8');
            $nombre = htmlspecialchars($prod['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
            $categoria = htmlspecialchars($prod['categoria_nombre'] ?? '-', ENT_QUOTES, 'UTF-8');
            $stockActual = intval($prod['stock_actual'] ?? 0);
            $stockMin = intval($prod['stock_minimo'] ?? 0);
            $stockMax = intval($prod['stock_maximo'] ?? 0);
            $precioCompra = floatval($prod['precio_compra'] ?? 0);
            $precioVenta = floatval($prod['precio_venta'] ?? 0);
            $precioCompraDual = formatearMonedaDualHTML($precioCompra);
            $precioVentaDual = formatearMonedaDualHTML($precioVenta);

            // Determinar tipo para el botón de ver
            $tipoDetalle = null;
            if (isset($productDetails[$prod['id']])) {
                $tipoDetalle = $productDetails[$prod['id']]['tipo'];
            }

            $rowsHtml .= "<tr data-product-id=\"{$prod['id']}\">";
            
            // Imagen
            $imgUrl = isset($productImages[intval($prod['id'])]) ? $productImages[intval($prod['id'])] : '';
            if ($imgUrl) {
                $safeImg = htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8');
                $safeAlt = htmlspecialchars($prod['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
                $rowsHtml .= "<td class=\"prod-image-cell\"><img src=\"{$safeImg}\" alt=\"{$safeAlt}\" class=\"prod-thumb\"></td>";
            } else {
                $rowsHtml .= "<td class=\"prod-image-cell\"><div class=\"prod-thumb placeholder\"><i class=\"fas fa-box\"></i></div></td>";
            }

            $rowsHtml .= "<td><strong>{$codigo}</strong></td>";
            $rowsHtml .= "<td>{$nombre}</td>";
            $rowsHtml .= "<td>{$categoria}</td>";
            $rowsHtml .= "<td>{$stockActual}</td>";
            $rowsHtml .= "<td>{$stockMin}</td>";
            $rowsHtml .= "<td>{$stockMax}</td>";
            $rowsHtml .= "<td>{$precioCompraDual}</td>";
            $rowsHtml .= "<td>{$precioVentaDual}</td>";
            $rowsHtml .= "<td><span class='" . $estadoClass . "'>{$estadoLabel}</span></td>";

            // Acciones
            $rowsHtml .= "<td class=\"actions-cell\">";
            
            // Preparar datos para el modal de detalles
            $detallesData = [
                'general' => [
                    'id' => $prod['id'] ?? null,
                    'codigo_interno' => $prod['codigo_interno'] ?? '',
                    'nombre' => $prod['nombre'] ?? '',
                    'descripcion' => $prod['descripcion'] ?? '',
                    'categoria_nombre' => $prod['categoria_nombre'] ?? '',
                    'proveedor_nombre' => $prod['proveedor_nombre'] ?? '',
                    'tipo_nombre' => $prod['tipo_nombre'] ?? '',
                    'stock_actual' => $prod['stock_actual'] ?? 0,
                    'stock_minimo' => $prod['stock_minimo'] ?? 0,
                    'stock_maximo' => $prod['stock_maximo'] ?? 0,
                    'precio_compra' => $prod['precio_compra'] ?? 0,
                    'precio_compra_bs' => $prod['precio_compra_bs'] ?? null,
                    'precio_compra_usd' => $prod['precio_compra_usd'] ?? null,
                    'precio_venta' => $prod['precio_venta'] ?? 0,
                    'precio_venta_bs' => $prod['precio_venta_bs'] ?? null,
                    'precio_venta_usd' => $prod['precio_venta_usd'] ?? null,
                    'estado' => $prod['estado'] ?? false,
                    'created_at' => $prod['created_at'] ?? '',
                    'updated_at' => $prod['updated_at'] ?? ''
                ],
                'especifico' => isset($productDetails[$prod['id']]) ? $productDetails[$prod['id']] : null,
                'imagenes' => isset($productImages[$prod['id']]) ? [$productImages[$prod['id']]] : []
            ];
            
            // Generar JSON seguro
            $detallesJson = json_encode($detallesData, 
                JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | 
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            
            $detallesJsonEscaped = str_replace("'", "&#39;", $detallesJson);
            
            $rowsHtml .= "<button class=\"btn btn-outline btn-sm btn-view\" data-detalles='{$detallesJsonEscaped}' title=\"Ver detalles\" aria-label=\"Ver detalles\"><i class=\"fas fa-eye\"></i></button> ";

            // Botón toggle estado
            $prodId = htmlspecialchars($prod['id'] ?? '', ENT_QUOTES, 'UTF-8');
            if ($prod['estado']) {
                $rowsHtml .= "<button class=\"btn btn-secondary btn-sm btn-toggle\" data-id=\"{$prodId}\" data-estado=\"1\" title=\"Inhabilitar\" aria-label=\"Inhabilitar\"><i class=\"fas fa-toggle-on\"></i></button>";
            } else {
                $rowsHtml .= "<button class=\"btn btn-secondary btn-sm btn-toggle\" data-id=\"{$prodId}\" data-estado=\"0\" title=\"Habilitar\" aria-label=\"Habilitar\"><i class=\"fas fa-toggle-off\"></i></button>";
            }

            $rowsHtml .= "</td>";
            $rowsHtml .= "</tr>";
        }
    }

    // 4) Estadísticas básicas
    $stats = ['totalProducts' => 0, 'lowStock' => 0, 'outOfStock' => 0, 'totalValue' => 0];

$sqlStats = "SELECT
                    COUNT(*) FILTER (WHERE estado = true) as total_products,
                    COUNT(*) FILTER (WHERE stock_actual <= stock_minimo AND estado = true) as low_stock,
                    COUNT(*) FILTER (WHERE stock_actual = 0 AND estado = true) as out_of_stock,
                    COALESCE(SUM(stock_actual * precio_compra) FILTER (WHERE estado = true AND stock_actual > 0 AND precio_compra > 0),0) as total_value_usd,
                    COALESCE(SUM(CASE WHEN precio_compra_bs > 0 THEN stock_actual * precio_compra_bs ELSE stock_actual * precio_compra * " . floatval(getTasaCambio()) . " END) FILTER (WHERE estado = true AND stock_actual > 0 AND (precio_compra_bs > 0 OR precio_compra > 0)),0) as total_value_bs
                FROM productos";
    $st = $conn->query($sqlStats);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if ($r) {
        $valorUsd = floatval($r['total_value_usd']);
        $valorBs = floatval($r['total_value_bs']);
        $stats['totalProducts'] = intval($r['total_products']);
        $stats['lowStock'] = intval($r['low_stock']);
        $stats['outOfStock'] = intval($r['out_of_stock']);
        $stats['totalValue'] = '$' . number_format($valorUsd, 2, ',', '.');
        $stats['totalValueFormatted'] = '<span class="moneda-usd">$' . number_format($valorUsd, 2, ',', '.') . '</span>' .
            '<span class="moneda-bs">Bs ' . number_format($valorBs, 2, ',', '.') . '</span>';
    }

    // 5) Datos por TIPO DE PRODUCTO (para el doughnut)
    $tiposData = [
        ['tipo_nombre' => 'Vehículos', 'cantidad' => 0],
        ['tipo_nombre' => 'Repuestos', 'cantidad' => 0],
        ['tipo_nombre' => 'Accesorios', 'cantidad' => 0],
        ['tipo_nombre' => 'Sin Tipo', 'cantidad' => 0]
    ];
    
    // Contar vehículos
    $vehStmt = $conn->query("SELECT COUNT(*) as cnt FROM vehiculos v JOIN productos p ON v.producto_id = p.id WHERE p.estado = true");
    $vehCount = $vehStmt->fetch(PDO::FETCH_ASSOC);
    $tiposData[0]['cantidad'] = intval($vehCount['cnt'] ?? 0);
    
    // Contar repuestos
    $repStmt = $conn->query("SELECT COUNT(*) as cnt FROM repuestos r JOIN productos p ON r.producto_id = p.id WHERE p.estado = true");
    $repCount = $repStmt->fetch(PDO::FETCH_ASSOC);
    $tiposData[1]['cantidad'] = intval($repCount['cnt'] ?? 0);
    
    // Contar accesorios
    $accStmt = $conn->query("SELECT COUNT(*) as cnt FROM accesorios a JOIN productos p ON a.producto_id = p.id WHERE p.estado = true");
    $accCount = $accStmt->fetch(PDO::FETCH_ASSOC);
    $tiposData[2]['cantidad'] = intval($accCount['cnt'] ?? 0);
    
    // Contar productos sin tipo específico
    $sinTipoStmt = $conn->query("
        SELECT COUNT(*) as cnt 
        FROM productos p 
        WHERE p.estado = true 
        AND NOT EXISTS (SELECT 1 FROM vehiculos v WHERE v.producto_id = p.id)
        AND NOT EXISTS (SELECT 1 FROM repuestos r WHERE r.producto_id = p.id)
        AND NOT EXISTS (SELECT 1 FROM accesorios a WHERE a.producto_id = p.id)
    ");
    $sinTipoCount = $sinTipoStmt->fetch(PDO::FETCH_ASSOC);
    $tiposData[3]['cantidad'] = intval($sinTipoCount['cnt'] ?? 0);
    
    // Filtrar tipos con 0 productos
    $tiposData = array_filter($tiposData, function($tipo) {
        return $tipo['cantidad'] > 0;
    });
    
    // Preparar datos para gráfica por tipo
    $tipoLabels = [];
    $tipoData = [];
    $tipoColors = [];
    
    $presetColors = ['#1F9166','#3498db','#9b59b6','#e67e22','#e74c3c','#2ecc71','#f1c40f','#34495e'];
    $i = 0;
    
    foreach ($tiposData as $tipo) {
        $tipoLabels[] = $tipo['tipo_nombre'] . " (" . $tipo['cantidad'] . ")";
        $tipoData[] = intval($tipo['cantidad']);
        $tipoColors[] = $presetColors[$i % count($presetColors)];
        $i++;
    }

    // 6) Datos de movimiento de stock (para la gráfica)
    $stockMovement = ['labels' => [], 'datasets' => []];

    try {
        // Determinar rango de fechas para el gráfico
        $endDate = new DateTime('now');
        $endDate->setTime(23, 59, 59);

        $startDate = null;
        if ($createdFrom) {
            $date = DateTime::createFromFormat('Y-m-d', $createdFrom);
            if ($date) {
                $startDate = $date->setTime(0, 0, 0);
            }
        }
        if ($createdTo) {
            $date = DateTime::createFromFormat('Y-m-d', $createdTo);
            if ($date) {
                $endDate = $date->setTime(23, 59, 59);
            }
        }

        if (!$startDate) {
            // Si no hay rango de fechas explícito, usar el período seleccionado
            $startDate = (new DateTime('first day of this month'))->setTime(0, 0, 0)->modify('-' . ($period - 1) . ' months');
        }

        // Generar etiquetas de meses para el período
        $monthNames = [
            '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr',
            '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'
        ];

        $periodStart = (clone $startDate)->modify('first day of this month')->setTime(0, 0, 0);
        $periodEnd = (clone $endDate)->modify('first day of next month')->setTime(0, 0, 0);

        $daterange = new DatePeriod($periodStart, new DateInterval('P1M'), $periodEnd);
        $monthlyKeys = [];
        foreach ($daterange as $dt) {
            $key = $dt->format('Y-m');
            $monthlyKeys[$key] = ['entradas' => 0, 'salidas' => 0];
            $stockMovement['labels'][] = ($monthNames[$dt->format('m')] ?? $dt->format('M')) . ' ' . $dt->format('Y');
        }

        // Consultar movimientos de inventario dentro del rango y con los filtros aplicados
        $movWhere = $where;
        $movParams = $params;

        $movWhere[] = 'mi.created_at >= :mov_start';
        $movWhere[] = 'mi.created_at <= :mov_end';
        $movParams[':mov_start'] = $periodStart->format('Y-m-d H:i:s');
        $movParams[':mov_end'] = $endDate->format('Y-m-d H:i:s');

        $movWhereSql = count($movWhere) ? 'WHERE ' . implode(' AND ', $movWhere) : '';

        $movSql = "SELECT to_char(mi.created_at, 'YYYY-MM') as ym, 
                          SUM(CASE WHEN mi.tipo_movimiento = 'ENTRADA' THEN mi.cantidad ELSE 0 END) as entradas, 
                          SUM(CASE WHEN mi.tipo_movimiento = 'SALIDA' THEN mi.cantidad ELSE 0 END) as salidas 
                   FROM movimientos_inventario mi 
                   JOIN productos p ON p.id = mi.producto_id 
                   {$movWhereSql} 
                   GROUP BY ym 
                   ORDER BY ym";

        $movStmt = $conn->prepare($movSql);
        foreach ($movParams as $k => $v) {
            $movStmt->bindValue($k, $v);
        }
        $movStmt->execute();
        while ($row = $movStmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['ym'];
            if (isset($monthlyKeys[$key])) {
                $monthlyKeys[$key]['entradas'] = intval($row['entradas'] ?? 0);
                $monthlyKeys[$key]['salidas'] = intval($row['salidas'] ?? 0);
            }
        }

        // Construir datasets
        $stockMovement['datasets'] = [
            [
                'label' => 'Entradas',
                'data' => array_values(array_map(fn($m) => $m['entradas'], $monthlyKeys)),
                'borderColor' => '#1F9166',
                'backgroundColor' => 'rgba(31, 145, 102, 0.1)',
                'tension' => 0.4,
                'fill' => true
            ],
            [
                'label' => 'Salidas',
                'data' => array_values(array_map(fn($m) => $m['salidas'], $monthlyKeys)),
                'borderColor' => '#e74c3c',
                'backgroundColor' => 'rgba(231, 76, 60, 0.1)',
                'tension' => 0.4,
                'fill' => true
            ]
        ];

    } catch (Exception $e) {
        error_log('WARNING inventory_stats.php: no se pudo obtener movimiento de stock: ' . $e->getMessage());
    }

    // 7) Datos por categoría para filtros
    $catsStmt = $conn->query("SELECT c.id, c.nombre as label, COUNT(p.id) as cnt 
                              FROM categorias c 
                              LEFT JOIN productos p ON p.categoria_id = c.id AND p.estado = true 
                              WHERE c.estado = true
                              GROUP BY c.id 
                              ORDER BY c.nombre ASC 
                              LIMIT 100");
    $cats = $catsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar lista de categorías
    $catList = [];
    
    foreach ($cats as $c) {
        $catList[] = ['id' => intval($c['id']), 'nombre' => $c['label']];
    }

    // 7) Obtener categorías por tipo para el formulario
    $catByType = [
        'VEHICULO' => [],
        'REPUESTO' => [],
        'ACCESORIO' => [],
        'GENERAL' => []
    ];
    
    // Primero, obtenemos todas las categorías con su tipo_producto_id
    $allCatsStmt = $conn->query("
        SELECT c.id, c.nombre, COALESCE(tp.nombre, 'GENERAL') as tipo_nombre
        FROM categorias c
        LEFT JOIN tipos_producto tp ON c.tipo_producto_id = tp.id
        WHERE c.estado = true
        ORDER BY c.nombre
    ");
    
    while ($cat = $allCatsStmt->fetch(PDO::FETCH_ASSOC)) {
        $tipo = strtoupper($cat['tipo_nombre']);
        
        // Mapear 'MOTO' a 'VEHICULO'
        if ($tipo === 'MOTO') {
            $tipo = 'VEHICULO';
        }
        
        if (isset($catByType[$tipo])) {
            $catByType[$tipo][] = [
                'id' => $cat['id'],
                'nombre' => $cat['nombre']
            ];
        } else {
            $catByType['GENERAL'][] = [
                'id' => $cat['id'],
                'nombre' => $cat['nombre']
            ];
        }
    }

    // 8) Construir la respuesta completa
    $response = [
        'stats' => $stats,
        'rowsHtml' => $rowsHtml,
        'stockByType' => [
            'labels' => $tipoLabels,
            'data' => $tipoData,
            'colors' => $tipoColors
        ],
        'stockMovement' => $stockMovement,
        'categoriesList' => $catList,
        'categoriesByType' => $catByType
    ];

    // 9) Lista de productos con stock bajo (para alertas rápidas en UI)
    $lowList = [];
    try {
        $lowStmt = $conn->prepare("SELECT id, codigo_interno, nombre, stock_actual, stock_minimo FROM productos WHERE estado = true AND stock_actual <= stock_minimo ORDER BY stock_actual ASC LIMIT 50");
        $lowStmt->execute();
        $lowRows = $lowStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lowRows as $lr) {
            $lowList[] = [
                'id' => intval($lr['id']),
                'codigo' => $lr['codigo_interno'] ?? '',
                'nombre' => $lr['nombre'] ?? '',
                'stock_actual' => intval($lr['stock_actual'] ?? 0),
                'stock_minimo' => intval($lr['stock_minimo'] ?? 0)
            ];
        }
    } catch (Exception $e) {
        // No interrumpir si falla la consulta de alerta
        error_log('WARNING inventory_stats.php: no se pudo obtener low stock list: ' . $e->getMessage());
    }

    $response['lowStockProducts'] = $lowList;

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

exit;
?>