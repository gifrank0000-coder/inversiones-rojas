<?php
session_start();
// Cargar configuración global
require_once __DIR__ . '/../../../config/config.php';
// Usar la clase Database central definida en app/models/database.php
require_once __DIR__ . '/../../models/database.php';
// Incluir helper de permisos
require_once __DIR__ . '/../../helpers/permissions.php';
// Incluir helper de moneda
require_once __DIR__ . '/../../helpers/moneda_helper.php';

// Helper pequeño para generar URL de assets con cache-busting (filemtime) y verificar existencia
$projectRoot = realpath(__DIR__ . '/../../../');
function asset_url_versioned($relativePath)
{
    global $projectRoot;
    $clean = ltrim($relativePath, '/');
    $fileOnDisk = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean);
    if (file_exists($fileOnDisk)) {
        $ver = filemtime($fileOnDisk);
    } else {
        // Si no existe, usar tiempo actual para forzar reintento y evitar cache viejo
        $ver = time();
        error_log("Asset missing: $fileOnDisk");
    }
    // Respetar la configuración ASSET_VERSIONING: si está desactivada, devolver URL limpia
    $base = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
    if (defined('ASSET_VERSIONING') && constant('ASSET_VERSIONING') === false) {
        return $base . '/' . $clean;
    }
    return $base . '/' . $clean . '?v=' . $ver;
}

// Conectar a la base de datos
$database = new Database();
$conn = $database->getConnection();

// Si el usuario es Cliente, no debe acceder al dashboard administrativo: redirigir a inicio mostrando sesión
$user_role = $_SESSION['user_rol'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
if ($user_role === 'Cliente') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/app/views/layouts/inicio.php');
    exit;
}

// Si se solicita un módulo por query string, verificar permiso
if (isset($_GET['module'])) {
    $module = trim($_GET['module']);
    // Normalizar nombre simple: usar la misma clave que en permissions (inventario, ventas, etc.)
    require_permission($module);
}

// Función para ejecutar consultas
function ejecutarConsulta($conn, $sql, $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Error en consulta: " . $e->getMessage());
        return false;
    }
}

// Obtener estadísticas del dashboard
$stats = [
    'ventas_hoy' => 0,
    'pedidos_pendientes' => 0,
    'productos_activos' => 0,
    'clientes_registrados' => 0
];

if ($conn) {
    // Total de ventas del día
    $sql = "SELECT COALESCE(SUM(total), 0) as total_ventas 
            FROM ventas 
            WHERE DATE(created_at) = CURRENT_DATE 
            AND estado_venta = 'COMPLETADA'";
    $stmt = ejecutarConsulta($conn, $sql);
    if ($stmt) {
        $result = $stmt->fetch();
        $stats['ventas_hoy'] = $result['total_ventas'];
    }
    
    // Pedidos pendientes
    $sql = "SELECT COUNT(*) as total_pedidos 
            FROM pedidos_online 
            WHERE estado_pedido = 'PENDIENTE'";
    if ($user_role === 'Vendedor' && $user_id) {
        $sql .= " AND vendedor_asignado_id = ?";
        $stmt = ejecutarConsulta($conn, $sql, [$user_id]);
    } else {
        $stmt = ejecutarConsulta($conn, $sql);
    }
    if ($stmt) {
        $result = $stmt->fetch();
        $stats['pedidos_pendientes'] = $result['total_pedidos'];
    }
    
    // Total de productos activos
    $sql = "SELECT COUNT(*) as total_productos 
            FROM productos 
            WHERE estado = true";
    $stmt = ejecutarConsulta($conn, $sql);
    if ($stmt) {
        $result = $stmt->fetch();
        $stats['productos_activos'] = $result['total_productos'];
    }
    
    // Total de clientes registrados
    $sql = "SELECT COUNT(*) as total_clientes 
            FROM clientes 
            WHERE estado = true";
    $stmt = ejecutarConsulta($conn, $sql);
    if ($stmt) {
        $result = $stmt->fetch();
        $stats['clientes_registrados'] = $result['total_clientes'];
    }
    
    // Obtener productos más vendidos
    $sql = "SELECT p.nombre, p.codigo_interno, 
                   COUNT(dv.id) as total_ventas, 
                   COALESCE(SUM(dv.subtotal), 0) as total_ingresos
            FROM productos p
            LEFT JOIN detalle_ventas dv ON p.id = dv.producto_id
            LEFT JOIN ventas v ON dv.venta_id = v.id AND v.estado_venta = 'COMPLETADA'
            WHERE p.estado = true
            GROUP BY p.id, p.nombre, p.codigo_interno
            ORDER BY total_ventas DESC, total_ingresos DESC
            LIMIT 4";
    $stmt = ejecutarConsulta($conn, $sql);
    $productosPopulares = $stmt ? $stmt->fetchAll() : [];
    
    // Obtener pedidos recientes
    $sql = "SELECT po.codigo_pedido, c.nombre_completo as cliente, 
                   po.total, po.estado_pedido, po.created_at
            FROM pedidos_online po
            LEFT JOIN clientes c ON po.cliente_id = c.id";
    
    if ($user_role === 'Vendedor' && $user_id) {
        $sql .= " WHERE po.vendedor_asignado_id = ?";
        $stmt = ejecutarConsulta($conn, $sql . " ORDER BY po.created_at DESC LIMIT 4", [$user_id]);
    } else {
        $sql .= " ORDER BY po.created_at DESC LIMIT 4";
        $stmt = ejecutarConsulta($conn, $sql);
    }
    $pedidosRecientes = $stmt ? $stmt->fetchAll() : [];
    
    // Obtener productos con stock bajo
    $sql = "SELECT nombre, stock_actual, stock_minimo
            FROM productos 
            WHERE estado = true 
            AND stock_actual <= stock_minimo
            ORDER BY stock_actual ASC
            LIMIT 4";
    $stmt = ejecutarConsulta($conn, $sql);
    $productosStockBajo = $stmt ? $stmt->fetchAll() : [];
    
    // Obtener datos para el gráfico de ventas últimos 7 días
    $sql = "SELECT TO_CHAR(created_at, 'YYYY-MM-DD') as fecha, COALESCE(SUM(total), 0) as total_ventas
            FROM ventas
            WHERE estado_venta = 'COMPLETADA'
                AND created_at >= CURRENT_DATE - INTERVAL '6 days'
            GROUP BY TO_CHAR(created_at, 'YYYY-MM-DD')
            ORDER BY fecha";
    $stmt = ejecutarConsulta($conn, $sql);
    $ventas7Dias = $stmt ? $stmt->fetchAll() : [];

    // Obtener datos para el gráfico de ventas últimos 30 días
    $sql = "SELECT TO_CHAR(created_at, 'YYYY-MM-DD') as fecha, COALESCE(SUM(total), 0) as total_ventas
            FROM ventas
            WHERE estado_venta = 'COMPLETADA'
                AND created_at >= CURRENT_DATE - INTERVAL '29 days'
            GROUP BY TO_CHAR(created_at, 'YYYY-MM-DD')
            ORDER BY fecha";
    $stmt = ejecutarConsulta($conn, $sql);
    $ventas30Dias = $stmt ? $stmt->fetchAll() : [];

    // Obtener datos para el gráfico de ventas últimos 12 meses
    $sql = "SELECT TO_CHAR(created_at, 'YYYY-MM') as mes, COALESCE(SUM(total), 0) as total_ventas
            FROM ventas
            WHERE estado_venta = 'COMPLETADA'
                AND created_at >= CURRENT_DATE - INTERVAL '12 months'
            GROUP BY TO_CHAR(created_at, 'YYYY-MM')
            ORDER BY mes";
    $stmt = ejecutarConsulta($conn, $sql);
    $ventas12Meses = $stmt ? $stmt->fetchAll() : [];

    // Construir rangos y datos
    $ventas7DiasMap = array_column($ventas7Dias, 'total_ventas', 'fecha');
    $ventas30DiasMap = array_column($ventas30Dias, 'total_ventas', 'fecha');
    $ventas12MesesMap = array_column($ventas12Meses, 'total_ventas', 'mes');

    $labels7 = [];
    $datos7 = [];
    for ($i = 6; $i >= 0; $i--) {
        $dia = date('Y-m-d', strtotime("-$i days"));
        $labels7[] = date('d M', strtotime($dia));
        $datos7[] = floatval($ventas7DiasMap[$dia] ?? 0);
    }

    $labels30 = [];
    $datos30 = [];
    for ($i = 29; $i >= 0; $i--) {
        $dia = date('Y-m-d', strtotime("-$i days"));
        $labels30[] = date('d M', strtotime($dia));
        $datos30[] = floatval($ventas30DiasMap[$dia] ?? 0);
    }

    $ultimos12Meses = [];
    $datos12 = [];
    for ($i = 11; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-$i months"));
        $ultimos12Meses[] = date('M', strtotime($m . '-01'));
        $datos12[] = floatval($ventas12MesesMap[$m] ?? 0);
    }

    $meses = $ultimos12Meses;
    $datosGrafico = $datos12;
    $dashboardChartData = [
        '7' => ['labels' => $labels7, 'data' => $datos7],
        '30' => ['labels' => $labels30, 'data' => $datos30],
        '12' => ['labels' => $ultimos12Meses, 'data' => $datos12],
    ];
} else {
    // Si no hay conexión, no usar datos simulados; presentar vacíos.
    $productosPopulares = [];
    $pedidosRecientes = [];
    $productosStockBajo = [];
    $datosGrafico = [];
    $meses = [];
    $dashboardChartData = ['7' => ['labels' => [], 'data' => []], '30' => ['labels' => [], 'data' => []], '12' => ['labels' => [], 'data' => []]];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Inversiones Rojas</title>
    <link rel="icon" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/css/admin/dashboard.css">
    <link rel="stylesheet" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/css/admin.css">
    <link rel="stylesheet" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/css/pages/home.css">
    <link rel="stylesheet" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/css/components/user-panel.css">
    <link rel="stylesheet" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/css/pages/auth.css">    <style>
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Estilos para el panel de notificaciones */
        .notifications-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 380px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #e9ecef;
        }
        .notifications-panel.active {
            right: 0;
        }
        .notifications-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }
        .notifications-header h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .close-notifications {
            background: none;
            border: none;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
        }
        .close-notifications:hover {
            background: rgba(0,0,0,0.1);
        }
        .notifications-body {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }
        .notification-loading {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        .notification-loading i {
            font-size: 24px;
            margin-bottom: 10px;
        }
        /* Estilos para las notificaciones individuales */
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
            cursor: pointer;
        }
        .notification-item:hover {
            background: #f8f9fa;
        }
        .notification-item.unread {
            background: rgba(31, 145, 102, 0.05);
            /* Ya no se utiliza línea verde para diferenciar, mantiene un fondo suave homogéneo */
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .notification-icon.warning {
            background: #fff3cd;
            color: #856404;
        }
        .notification-icon.danger {
            background: #f8d7da;
            color: #721c24;
        }
        .notification-icon.info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .notification-content {
            flex: 1;
        }
        .notification-title {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 4px;
        }
        .notification-message {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }
        .notification-time {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
        }
        .notification-empty {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .notification-empty i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        .notification-empty p {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .notification-empty span {
            font-size: 14px;
        }
        /* Overlay para el panel */
        .notifications-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .notifications-overlay.active {
            opacity: 1;
            visibility: visible;
        }
    </style>        </head>
<body>
    <!-- Sidebar Navigation -->
    <?php require __DIR__ . '/partials/sidebar_menu.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-left">
                <h1>Dashboard</h1>
                <nav class="breadcrumb">
                    <a href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/">Inicio</a>
                    <span>/</span>
                    <span>Dashboard</span>
                </nav>
            </div>
            
            <div class="header-right">
                <div class="header-actions">
                    <button class="header-btn" id="notificationsBtn" title="Notificaciones" style="position: relative;">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none;"></span>
                    </button>


                    <button class="header-btn" id="fullscreenBtn" title="Pantalla completa">
                        <i class="fas fa-expand"></i>
                    </button>

                </div>
                  <?php require __DIR__ . '/partials/user_panel.php'; ?>
            </div>
        </header>

    <!-- Panel de notificaciones global -->
    <div class="notifications-panel" id="notificationsPanel">
        <div class="notifications-header">
            <h3><i class="fas fa-bell"></i> Notificaciones</h3>
            <button class="close-notifications" id="closeNotifications">&times;</button>
        </div>
        <div class="notifications-body" id="notificationsBody">
            <div class="notification-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Cargando notificaciones...</p>
            </div>
        </div>
    </div>
    <div class="notifications-overlay" id="notificationsOverlay"></div>

        <!-- Dashboard Content -->
        <div class="admin-content">
            <?php
            // Si se solicitó un módulo por query string, incluir la vista correspondiente
            if (isset($module) && !empty($module)) {
                // Lista blanca de módulos permitidos y su archivo correspondiente
                $allowedModules = [
                    'ventas' => 'ventas.php',
                    'compras' => 'compras.php',
                    'inventario' => 'inventario.php',
                    'pedidos' => 'pedidos.php',
                    'reservas' => 'reserva.php',
                    'promociones' => 'promociones.php',
                    'devoluciones' => 'devoluciones.php',
                    'clientes' => 'clientes.php',
                 'configuracion' => 'configuracion.php',
                 'perfil' => 'perfil.php'
                 , 'soporte' => 'soporte.php'
                    
                ];

                $moduleKey = strtolower($module);
                if (isset($allowedModules[$moduleKey])) {
                    $moduleFile = __DIR__ . '/' . $allowedModules[$moduleKey];
                    if (file_exists($moduleFile)) {
                        // Mostrar el módulo dentro de un iframe para mantener el layout del dashboard
                        $iframeSrc = rtrim(defined('BASE_URL') ? BASE_URL : '', '/') . '/app/views/layouts/' . $allowedModules[$moduleKey];
                        echo '<div class="module-iframe-wrapper" style="width:100%; height:calc(100vh - 220px);">';
                        echo '<iframe src="' . htmlspecialchars($iframeSrc) . '" style="width:100%; height:100%; border:0; border-radius:8px;" title="Modulo ' . htmlspecialchars($moduleKey) . '"></iframe>';
                        echo '</div>';
                    } else {
                        echo "<div class=\"module-missing\">Módulo solicitado ('" . htmlspecialchars($moduleKey) . "') no está disponible.</div>";
                    }
                } else {
                    echo "<div class=\"module-unauthorized\">Módulo no permitido.</div>";
                }
            
                // No renderizar el dashboard por defecto cuando se muestra un módulo
            } else {
                // Cerrar el bloque PHP para renderizar el HTML del dashboard
                ?>
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon sales">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?php echo number_format($stats['ventas_hoy'], 2); ?></h3>
                        <p>Ventas Hoy</p>
                        <span class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            12.5%
                        </span>
                    </div>
                </div>
            
                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pedidos_pendientes']; ?></h3>
                        <p>Pedidos Activos</p>
                        <span class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            8.2%
                        </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon products">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['productos_activos']; ?></h3>
                        <p>Productos Activos</p>
                        <span class="stat-trend negative">
                            <i class="fas fa-arrow-down"></i>
                            3.1%
                        </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon customers">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['clientes_registrados']; ?></h3>
                        <p>Clientes Registrados</p>
                        <span class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            5.7%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Charts and Tables Grid -->
            <div class="dashboard-grid">
                <!-- Ventas Chart -->
                <div class="grid-card large">
                    <div class="card-header">
                        <h3>Ventas</h3>
                        <div class="card-actions">
                            <select id="salesRangeFilter" class="chart-filter">
                                <option value="7">Últimos 7 días</option>
                                <option value="30">Últimos 30 días</option>
                                <option value="12" selected>Últimos 12 meses</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Productos Más Vendidos -->
                <div class="grid-card">
                    <div class="card-header">
                        <h3>Productos Populares</h3>
                        <a href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/app/views/layouts/Dashboard.php?module=inventario" class="view-all">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <div class="product-list">
                            <?php if (!empty($productosPopulares)): ?>
                                <?php foreach ($productosPopulares as $producto): ?>
                                <div class="product-item">
                                    <div class="product-info">
                                        <div class="product-avatar">
                                            <i class="fas fa-motorcycle"></i>
                                        </div>
                                        <div class="product-details">
                                            <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                            <span><?php echo $producto['total_ventas']; ?> ventas</span>
                                        </div>
                                    </div>
                                    <span class="product-sales">
                                            <?php 
                                            $precios = formatearMonedaDual($producto['total_ingresos']);
                                            echo '<span class="moneda-bs">' . $precios['bs'] . '</span> ';
                                            echo '<span class="moneda-usd">(' . $precios['usd'] . ')</span>';
                                            ?>
                                        </span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="product-item">
                                    <div class="product-info">
                                        <div class="product-avatar">
                                            <i class="fas fa-box-open"></i>
                                        </div>
                                        <div class="product-details">
                                            <strong>No hay productos vendidos aún</strong>
                                            <span>Información actualizada en tiempo real</span>
                                        </div>
                                    </div>
                                    <span class="product-sales">$0.00</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Pedidos Recientes -->
                <div class="grid-card large">
                    <div class="card-header">
                        <h3>Pedidos Recientes</h3>
                        <a href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/app/views/layouts/Dashboard.php?module=pedidos" class="view-all">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID Pedido</th>
                                        <th>Cliente</th>
                                        <th>Fecha</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pedidosRecientes)): ?>
                                        <?php foreach ($pedidosRecientes as $pedido): ?>
                                        <tr>
                                            <td><?php echo $pedido['codigo_pedido']; ?></td>
                                            <td><?php echo htmlspecialchars($pedido['cliente']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($pedido['created_at'])); ?></td>
                                            <td>
                                                <?php 
                                                $precios = formatearMonedaDual($pedido['total']);
                                                echo '<span class="moneda-bs">' . $precios['bs'] . '</span> ';
                                                echo '<span class="moneda-usd">(' . $precios['usd'] . ')</span>';
                                                ?>
                                            </td>
                                            <td><span class="status-badge <?php echo strtolower($pedido['estado_pedido']); ?>"><?php echo $pedido['estado_pedido']; ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center;">No hay pedidos recientes</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Actividad Reciente -->
                <div class="grid-card">
                    <div class="card-header">
                        <h3>Alertas de Stock</h3>
                    </div>
                    <div class="card-body">
                        <div class="activity-feed">
                            <?php if (!empty($productosStockBajo)): ?>
                                <?php foreach ($productosStockBajo as $producto): ?>
                                <div class="activity-item">
                                    <div class="activity-icon warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p>Stock bajo alerta</p>
                                        <span><?php echo htmlspecialchars($producto['nombre']); ?> - Solo <?php echo $producto['stock_actual']; ?> unidades</span>
                                        <small>Stock mínimo: <?php echo $producto['stock_minimo']; ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="activity-item">
                                    <div class="activity-icon success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p>Stock normal</p>
                                        <span>No hay alertas de stock bajo</span>
                                        <small>Todos los productos tienen stock suficiente</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
                            
    <?php
    }
    ?>

    <!-- Settings Modal -->
    <div class="modal" id="settingsModal" aria-hidden="true" role="dialog" aria-labelledby="settingsModalTitle">
        <div class="modal-backdrop" id="settingsModalBackdrop"></div>
        <div class="modal-content" role="document">
            <div class="modal-header">
                <h2 id="settingsModalTitle">Configuración</h2>
                <button class="modal-close" id="settingsModalClose" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <div class="settings-actions">
                    <a class="btn btn-primary" id="manageUsersBtn" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/app/views/layouts/Dashboard.php?module=empleados">
                        <i class="fas fa-user-cog"></i> Gestión de Usuarios
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Toggle del sidebar (se ejecuta en páginas que cargan este partial)
document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('sidebarToggle');
    if (btn) {
        btn.addEventListener('click', function(){
            var sidebar = document.querySelector('.admin-sidebar');
            if (sidebar) sidebar.classList.toggle('collapsed');
        });
    }
});
        // Exponer datos necesarios al JS
        window.DASHBOARD_DATA = {
            salesChartData: <?php echo json_encode($dashboardChartData ?? ['7' => ['labels'=>[], 'data'=>[]], '30' => ['labels'=>[], 'data'=>[]], '12' => ['labels'=>[], 'data'=>[]]]); ?>,
            defaultSalesRange: '12'
        };
    </script>
    <script>
    // Escuchar mensajes desde módulos cargados en iframe para actualizar header/breadcrumb
    (function(){
        const setHeader = (title, crumbs) => {
            const h = document.querySelector('.header-left h1');
            const bc = document.querySelector('.breadcrumb');
            if (h) h.textContent = title || 'Dashboard';
            if (bc) {
                if (Array.isArray(crumbs) && crumbs.length) {
                    // Construir breadcrumb: primer elemento link a base, luego separadores
                    const base = '<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>';
                    bc.innerHTML = '';
                    crumbs.forEach((c, i) => {
                        if (i === 0) {
                            bc.innerHTML += '<a href="' + base + '">' + c + '</a>';
                        } else {
                            bc.innerHTML += '<span>/</span><span>' + c + '</span>';
                        }
                    });
                }
            }
            if (title) document.title = title + ' - Admin Inversiones Rojas';
        };

        window.addEventListener('message', function(ev){
            // Aceptar sólo mensajes del mismo origen por seguridad
            try {
                if (ev.origin !== window.location.origin) return;
            } catch(e){ return; }

            let data = ev.data;
            if (typeof data === 'string') {
                try { data = JSON.parse(data); } catch(e) { return; }
            }
            if (!data || !data.irModuleHeader) return;
            setHeader(data.title || 'Dashboard', data.breadcrumb || ['Inicio', data.title || '']);
        }, false);

        // Fallback: si el iframe carga, inferir título por nombre de archivo
        const iframe = document.querySelector('.module-iframe-wrapper iframe');
        if (iframe) {
            iframe.addEventListener('load', function(){
                try {
                    const path = new URL(iframe.src).pathname.split('/').pop();
                    const map = {
                        'configuracion.php': 'Configuración',
                        'compras.php': 'Compras',
                        'ventas.php': 'Ventas',
                        'inventario.php': 'Inventario',
                        'pedidos.php': 'Pedidos',
                        'promociones.php': 'Promociones',
                        'devoluciones.php': 'Devoluciones',
                        'clientes.php': 'Clientes'
                    };
                    const title = map[path] || path.replace('.php','');
                    setHeader(title, ['Inicio', title]);
                } catch(e){}
            });
        }
    })();
    </script>
    <script src="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/js/main.js"></script>
    <script src="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/js/components/user-panel.js"></script>
    <script src="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/js/components/sidebar.js"></script>
    <script src="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/js/script.js"></script>
    <script src="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/js/admin/dashboard.js"></script>
    <script>
    // Gestión del panel de notificaciones
    document.addEventListener('DOMContentLoaded', function() {
        const notificationsBtn = document.getElementById('notificationsBtn');
        const notificationsPanel = document.getElementById('notificationsPanel');
        const notificationsOverlay = document.getElementById('notificationsOverlay');
        const closeBtn = document.getElementById('closeNotifications');
        const badge = document.getElementById('notificationBadge');

        function openNotifications() {
            notificationsPanel.classList.add('active');
            notificationsOverlay.classList.add('active');
        }
        function closeNotificationsPanel() {
            notificationsPanel.classList.remove('active');
            notificationsOverlay.classList.remove('active');
        }

        if (notificationsBtn) {
            notificationsBtn.addEventListener('click', function() {
                if (notificationsPanel.classList.contains('active')) {
                    closeNotificationsPanel();
                } else {
                    openNotifications();
                    loadNotifications();
                }
            });
        }
        if (closeBtn) closeBtn.addEventListener('click', closeNotificationsPanel);
        if (notificationsOverlay) notificationsOverlay.addEventListener('click', closeNotificationsPanel);

        function loadNotifications() {
            fetch('<?php echo rtrim(defined("BASE_URL")?BASE_URL:"", "/"); ?>/api/get_notifications.php')
                .then(res => res.json())
                .then(data => {
                    renderNotifications(data);
                    // Marcar como leídas
                    fetch('<?php echo rtrim(defined("BASE_URL")?BASE_URL:"", "/"); ?>/api/mark_notifications_read.php', {
                        method: 'POST'
                    }).catch(err => console.error('Error marcando notificaciones', err));
                })
                .catch(err => console.error('Error cargando notificaciones', err));
        }
        function renderNotifications(items) {
            const body = document.getElementById('notificationsBody');
            if (!body) return;
            body.innerHTML = '';
            if (!items || items.length === 0) {
                body.innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash"></i><p>No hay notificaciones</p></div>';
                return;
            }
            const totalCount = items.length;
            let unreadCount = 0;
            items.forEach(n => {
                const div = document.createElement('div');
                div.className = 'notification-item' + (n.unread ? ' unread' : '');
                const iconClass = n.icon || 'bell';
                const typeClass = n.type || 'info';
                const timeText = n.time || 'Ahora';

                div.innerHTML = '<div class="notification-icon ' + typeClass + '"><i class="fas fa-' + iconClass + '"></i></div>' +
                              '<div class="notification-content">' +
                              '<div class="notification-title">' + n.title + '</div>' +
                              '<div class="notification-message">' + n.message + '</div>' +
                              '<div class="notification-time">' + timeText + '</div>' +
                              '</div>';
                body.appendChild(div);
                if (n.unread) unreadCount++;
            });
            if (badge) {
                if (totalCount > 0) {
                    badge.textContent = totalCount;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        }

        // cargar inicialmente para actualizar el badge
        loadNotifications();
    });
    </script>
</body>
</html>