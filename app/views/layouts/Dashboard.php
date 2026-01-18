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
    // Respetar la configuración ASSET_VERSIONING: si está desactivada, devolver URL limpia
    $base = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
    if (defined('ASSET_VERSIONING') && ASSET_VERSIONING === false) {
        return $base . '/' . $clean;
    }
    return $base . '/' . $clean . '?v=' . $ver;
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
    <link rel="stylesheet" href="/inversiones-rojas/public/css/admin/dashboard.css">
    <link rel="stylesheet" href="/inversiones-rojas/public/css/base.css">
    <link rel="stylesheet" href="/inversiones-rojas/public/css/admin.css">
    <link rel="stylesheet" href="/inversiones-rojas/public/css/pages/home.css">
    <link rel="stylesheet" href="/inversiones-rojas/public/css/components/user-panel.css">
    <link rel="stylesheet" href="/inversiones-rojas/public/css/pages/auth.css">
        </head>
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
                    <button class="header-btn" id="notificationsBtn" title="Notificaciones">
                        <i class="fas fa-bell"></i>
   
                    </button>


                    <button class="header-btn" id="fullscreenBtn" title="Pantalla completa">
                        <i class="fas fa-expand"></i>
                    </button>

                </div>
                  <?php require __DIR__ . '/partials/user_panel.php'; ?>
            </div>
        </header>

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
            meses: <?php echo json_encode($meses); ?>,
            datosGrafico: <?php echo json_encode($datosGrafico); ?>
            
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
    <script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/components/user-panel.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/components/sidebar.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/script.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/admin/dashboard.js"></script>
    
</body>
</html>