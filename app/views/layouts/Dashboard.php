<?php
session_start();
// Cargar configuración global
require_once __DIR__ . '/../../../config/config.php';
// Usar la clase Database central definida en app/models/database.php
require_once __DIR__ . '/../../models/database.php';
// Incluir helper de permisos
require_once __DIR__ . '/../../helpers/permissions.php';

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
    return rtrim(defined('BASE_URL') ? BASE_URL : '', '/') . '/' . $clean . '?v=' . $ver;
}

// Conectar a la base de datos
$database = new Database();
$conn = $database->getConnection();

// Si el usuario es Cliente, no debe acceder al dashboard administrativo: redirigir a inicio mostrando sesión
$user_role = $_SESSION['user_rol'] ?? null;
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
    $stmt = ejecutarConsulta($conn, $sql);
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
            LEFT JOIN clientes c ON po.cliente_id = c.id
            ORDER BY po.created_at DESC
            LIMIT 4";
    $stmt = ejecutarConsulta($conn, $sql);
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
    
    // Obtener datos para el gráfico de ventas mensuales
    $sql = "SELECT 
                TO_CHAR(created_at, 'YYYY-MM') as mes,
                COALESCE(SUM(total), 0) as total_ventas
            FROM ventas
            WHERE estado_venta = 'COMPLETADA'
                AND created_at >= CURRENT_DATE - INTERVAL '12 months'
            GROUP BY TO_CHAR(created_at, 'YYYY-MM')
            ORDER BY mes";
    $stmt = ejecutarConsulta($conn, $sql);
    $ventasMensuales = $stmt ? $stmt->fetchAll() : [];
    
    // Preparar datos para el gráfico
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $datosGrafico = array_fill(0, 12, 0);
    
    // Obtener los últimos 12 meses
    $ultimos12Meses = [];
    for ($i = 11; $i >= 0; $i--) {
        $ultimos12Meses[] = date('Y-m', strtotime("-$i months"));
    }
    
    // Mapear ventas a los meses correspondientes
    foreach ($ventasMensuales as $venta) {
        $index = array_search($venta['mes'], $ultimos12Meses);
        if ($index !== false) {
            $datosGrafico[$index] = floatval($venta['total_ventas']);
        }
    }
} else {
    // Datos de ejemplo si no hay conexión
    $productosPopulares = [];
    $pedidosRecientes = [];
    $productosStockBajo = [];
    $datosGrafico = array_fill(0, 12, 0);
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Inversiones Rojas</title>
    <link rel="icon" href="logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="<?php echo asset_url_versioned('public/css/base.css'); ?>">
        <link rel="stylesheet" href="<?php echo asset_url_versioned('public/css/pages/auth.css'); ?>">
        <link rel="stylesheet" href="<?php echo asset_url_versioned('public/css/admin.css'); ?>">
        <link rel="stylesheet" href="<?php echo asset_url_versioned('public/css/pages/home.css'); ?>">
        <link rel="stylesheet" href="<?php echo asset_url_versioned('public/css/admin/dashboard.css'); ?>">
            <link rel="stylesheet" href="<?php echo asset_url_versioned('public/css/components/user-panel.css'); ?>">

</head>
<body class="admin-body">
    <!-- Sidebar Navigation -->
    <nav class="admin-sidebar">
        <div class="sidebar-header">
                <div class="admin-logo">
                    <a href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/" class="sidebar-logo-link">
                        <i class="fas fa-motorcycle"></i>
                        <h2>Inversiones Rojas</h2>
                    </a>
                </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <div class="sidebar-menu">
            <div class="menu-section">
                <h3>Principal</h3>
                <ul>
                    <li class="menu-item active">
                        <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <!-- items reales del menú (sin ejemplos) -->
                    <?php if (role_has_permission($_SESSION['user_rol'] ?? null, 'ventas')): ?>
                    <li class="menu-item">
                        <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php?module=ventas">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Ventas</span>
                            <span class="menu-badge"><?php echo $stats['pedidos_pendientes']; ?></span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (role_has_permission($_SESSION['user_rol'] ?? null, 'pedidos')): ?>
                    <li class="menu-item">
                        <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php?module=pedidos">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Pedidos</span>
                            <span class="menu-badge"><?php echo $stats['pedidos_pendientes']; ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="menu-section">
                <h3>Gestión</h3>
                <ul>
                    <?php if (role_has_permission($_SESSION['user_rol'] ?? null, 'inventario')): ?>
                    <li class="menu-item">
                        <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php?module=productos">
                            <i class="fas fa-motorcycle"></i>
                            <span>Productos</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php?module=categorias">
                            <i class="fas fa-tags"></i>
                            <span>Categorías</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php?module=inventario">
                            <i class="fas fa-boxes"></i>
                            <span>Inventario</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="menu-section">
                <h3>Usuarios</h3>
                <ul>
                    <?php if (role_has_permission($_SESSION['user_rol'] ?? null, 'ventas')): ?>
                    <li class="menu-item">
                        <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php?module=clientes">
                            <i class="fas fa-users"></i>
                            <span>Clientes</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (in_array($_SESSION['user_rol'] ?? '', ['Administrador','Gerente'])): ?>
                    <li class="menu-item">
                        <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php?module=empleados">
                            <i class="fas fa-user-tie"></i>
                            <span>Empleados</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="menu-section">
                <h3>Configuración</h3>
                <ul>
                    <?php if (in_array($_SESSION['user_rol'] ?? '', ['Administrador','Gerente'])): ?>
                    <li class="menu-item">
                        <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php?module=configuracion">
                            <i class="fas fa-cog"></i>
                            <span>Configuración</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php?module=reportes">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reportes</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        </div>
    </nav>

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
                    <button class="header-btn" id="notificationsBtn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-count"><?php echo count($productosStockBajo); ?></span>
                    </button>
                    <button class="header-btn" id="fullscreenBtn">
                        <i class="fas fa-expand"></i>
                    </button>
                  
                </div>
                  <?php require __DIR__ . '/partials/user_panel.php'; ?>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="admin-content">
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
                        <h3>Ventas Mensuales</h3>
                        <div class="card-actions">
                            <select class="chart-filter">
                                <option>Últimos 7 días</option>
                                <option>Últimos 30 días</option>
                                <option selected>Últimos 12 meses</option>
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
                        <a href="productos.html" class="view-all">Ver Todos</a>
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
                                    <span class="product-sales">$<?php echo number_format($producto['total_ingresos'], 2); ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="product-item">
                                    <div class="product-info">
                                        <div class="product-avatar">
                                            <i class="fas fa-motorcycle"></i>
                                        </div>
                                        <div class="product-details">
                                            <strong>Bera BR 200</strong>
                                            <span>0 ventas</span>
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
                        <a href="pedidos.html" class="view-all">Ver Todos</a>
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
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pedidosRecientes)): ?>
                                        <?php foreach ($pedidosRecientes as $pedido): ?>
                                        <tr>
                                            <td><?php echo $pedido['codigo_pedido']; ?></td>
                                            <td><?php echo htmlspecialchars($pedido['cliente']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($pedido['created_at'])); ?></td>
                                            <td>$<?php echo number_format($pedido['total'], 2); ?></td>
                                            <td><span class="status-badge <?php echo strtolower($pedido['estado_pedido']); ?>"><?php echo $pedido['estado_pedido']; ?></span></td>
                                            <td>
                                                <button class="action-btn view" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="action-btn edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" style="text-align: center;">No hay pedidos recientes</td>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Exponer datos necesarios al JS
        window.DASHBOARD_DATA = {
            meses: <?php echo json_encode($meses); ?>,
            datosGrafico: <?php echo json_encode($datosGrafico); ?>
        };
    </script>
    <script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/components/user-panel.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/script.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/admin/dashboard.js"></script>
    
</body>
</html>