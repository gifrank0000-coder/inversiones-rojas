<?php
// =============================================
// IMPORTANTE: Todo el PHP debe ir ANTES del HTML
// =============================================

// Iniciar buffer de salida para evitar errores de headers
ob_start();

// Configuración de sesión debe ir ANTES de cualquier salida
session_start();

// Incluir configuración después de iniciar sesión
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';
// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

// Obtener datos del usuario
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_role = $_SESSION['user_rol'] ?? null;

// Esta pantalla solo es accesible para usuarios autorizados desde el menú principal.
$canManage = true;
$isClient  = false;
$isStaff   = true;
$isAdmin   = ($user_role !== 'Vendedor');

// Cargar datos de pedidos
$pedidos = [];
$pedidos_stats = [
    'hoy' => 0,
    'pendientes' => 0,
    'en_verificacion' => 0,
    'valor_pendiente' => 0,
    'clientes_online' => 0
];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        // Estadísticas
        $stmt = $conn->prepare("SELECT COUNT(*) FROM pedidos_online WHERE DATE(created_at) = CURRENT_DATE");
        $stmt->execute();
        $pedidos_stats['hoy'] = (int)($stmt->fetchColumn() ?: 0);

        $stmt = $conn->prepare("SELECT COUNT(*) FROM pedidos_online WHERE estado_pedido = 'PENDIENTE'");
        $stmt->execute();
        $pedidos_stats['pendientes'] = (int)($stmt->fetchColumn() ?: 0);

        $stmt = $conn->prepare("SELECT COUNT(*) FROM pedidos_online WHERE estado_pedido = 'EN_VERIFICACION'");
        $stmt->execute();
        $pedidos_stats['en_verificacion'] = (int)($stmt->fetchColumn() ?: 0);

        $stmt = $conn->prepare("SELECT COALESCE(SUM(total),0) FROM pedidos_online WHERE estado_pedido = 'PENDIENTE'");
        $stmt->execute();
        $pedidos_stats['valor_pendiente'] = (float)($stmt->fetchColumn() ?: 0);

        $stmt = $conn->prepare("SELECT COUNT(DISTINCT cliente_id) FROM pedidos_online WHERE created_at >= CURRENT_DATE");
        $stmt->execute();
        $pedidos_stats['clientes_online'] = (int)($stmt->fetchColumn() ?: 0);

        // Listado de pedidos - CORREGIDO: eliminado campo 'activo'
        $baseSql = "SELECT p.id, p.codigo_pedido, p.cliente_id, c.nombre_completo as cliente_nombre, 
                   COALESCE(p.telefono_contacto,'') as telefono_contacto,
                   p.created_at, p.estado_pedido, p.total, COUNT(d.id) as num_items,
                   STRING_AGG(pr.nombre || ' x' || d.cantidad, ', ') as productos,
                   p.vendedor_asignado_id, u.nombre_completo as vendedor_nombre,
                   p.canal_comunicacion
            FROM pedidos_online p
            LEFT JOIN clientes c ON p.cliente_id = c.id
            LEFT JOIN detalle_pedidos_online d ON d.pedido_id = p.id
            LEFT JOIN productos pr ON pr.id = d.producto_id
            LEFT JOIN usuarios u ON p.vendedor_asignado_id = u.id";

        // Si el usuario logueado es vendedor, solo ver sus pedidos asignados
        if ($user_role === 'Vendedor') {
            $baseSql .= " WHERE p.vendedor_asignado_id = ?";
        }

        $baseSql .= " GROUP BY p.id, c.nombre_completo, p.vendedor_asignado_id, u.nombre_completo, p.canal_comunicacion
            ORDER BY p.created_at DESC";

        $stmt = $conn->prepare($baseSql);
        if ($user_role === 'Vendedor') {
            $stmt->execute([$user_id]);
        } else {
            $stmt->execute();
        }
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log('Error cargando pedidos: ' . $e->getMessage());
    $pedidos = [];
}

// Verificar si mostrar columna de vendedor (solo para pedidos con notificaciones)
$mostrar_columna_vendedor = false;
foreach ($pedidos as $p) {
    if ($p['canal_comunicacion'] === 'notificaciones') {
        $mostrar_columna_vendedor = true;
        break;
    }
}

// Obtener lista de vendedores
$vendedores = [];
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, nombre_completo FROM usuarios WHERE rol_id IN (SELECT id FROM roles WHERE nombre ILIKE '%vendedor%' OR nombre ILIKE '%venta%') ORDER BY nombre_completo");
        $stmt->execute();
        $vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $vendedores = [];
    }
}

// Mapeos para estados
$statusLabels = [
    'PENDIENTE'       => 'Pendiente',
    'EN_VERIFICACION' => 'En Verificación',
    'COMPLETADO'      => 'Completado',
    // Compatibilidad con datos existentes en BD
    'CONFIRMADO'  => 'Completado',
    'EN_PROCESO'  => 'Completado',
    'ENVIADO'     => 'Completado',
    'ENTREGADO'   => 'Completado',
    'RECHAZADO'   => 'Rechazado',
    'CANCELADO'   => 'Cancelado',
    'INHABILITADO'=> 'Cancelado',
];

$statusClasses = [
    'PENDIENTE'       => 'status-pending',
    'EN_VERIFICACION' => 'status-verification',
    'COMPLETADO'      => 'status-completed',
    'CONFIRMADO'      => 'status-completed',
    'EN_PROCESO'      => 'status-completed',
    'ENVIADO'         => 'status-completed',
    'ENTREGADO'       => 'status-completed',
    'RECHAZADO'       => 'status-inhabilitado',
    'CANCELADO'       => 'status-inhabilitado',
    'INHABILITADO'    => 'status-inhabilitado',
];

// 3 acciones simples:
// PENDIENTE      → esperar que cliente suba comprobante (ninguna acción del vendedor)
// EN_VERIFICACION → vendedor aprueba (completa) o rechaza
// COMPLETADO     → nada más
$actionLabels = [
    'aprobar'  => 'Aprobar y completar',
    'rechazar' => 'Rechazar pago',
];

$transitionMap = [
    'PENDIENTE'       => [],               // Solo el cliente puede mover este estado
    'EN_VERIFICACION' => ['aprobar', 'rechazar'],
    'COMPLETADO'      => [],
    'RECHAZADO'       => [],
    'CANCELADO'       => [],
];
$base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Digitales - Inversiones Rojas</title>
    <script>
        var APP_BASE = '<?php echo $base_url; ?>';
        var TASA_CAMBIO = <?php echo getTasaCambio(); ?>;
        
        function formatCurrencyDual(amount) {
            var bs = (amount * TASA_CAMBIO).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            var usd = amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return '<span class="moneda-bs">Bs ' + bs + '</span> <span class="moneda-usd">($' + usd + ')</span>';
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo $base_url; ?>/public/js/inv-notifications.js"></script>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/layouts/pedidos.css">
    <style>
        /* Estilos para moneda dual */
        .moneda-bs { color: #1F9166; font-weight: 700; }
        .moneda-usd { color: #6c757d; font-size: 0.85em; }
        
        /* Estilos para botones de acciones */
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-decoration: none;
        }

        .btn-icon {
            padding: 6px;
            width: 32px;
            height: 32px;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        .btn-full {
            width: 100%;
            min-width: 150px;
            padding: 10px 14px;
            font-size: 0.95rem;
        }

        .btn-icon.btn-sm {
            width: 28px;
            height: 28px;
            padding: 4px;
        }

        .btn-primary {
            background: #1F9166;
            color: white;
        }

        .btn-primary:hover {
            background: #187a54;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        .btn-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .btn-warning:hover {
            background: #ffeaa7;
        }

        .btn-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-danger:hover {
            background: #f1b0b7;
        }

        .btn-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .btn-success:hover {
            background: #c3e6cb;
        }

        .btn-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Canal badges */
        .canal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .canal-badge i {
            font-size: 12px;
        }
        .canal-whatsapp {
            background: rgba(37, 211, 102, 0.12);
            color: #25D366;
        }
        .canal-email {
            background: rgba(217, 48, 37, 0.12);
            color: #d93025;
        }
        .canal-telegram {
            background: rgba(0, 136, 204, 0.12);
            color: #0088cc;
        }
        .canal-notificaciones {
            background: rgba(243, 156, 18, 0.15);
            color: #f39c12;
        }
        .canal-web {
            background: rgba(31, 145, 102, 0.12);
            color: #1F9166;
        }

        .btn-info:hover {
            background: #bee5eb;
        }

        .btn-purple {
            background: #e0d4f5;
            color: #5e2e9e;
            border: 1px solid #c9b6e8;
        }

        .btn-purple:hover {
            background: #c9b6e8;
        }

        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            justify-content: center;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 18px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Estados */
        .status-badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .status-verification {
            background: #ffeaa7;
            color: #d63031;
            border: 1px solid #fdcb6e;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-inhabilitado {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-preparing {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }

        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .status-delivered {
            background: #e0d4f5;
            color: #5e2e9e;
            border: 1px solid #c9b6e8;
        }

        .status-completed {
            background: #d1e7dd;
            color: #0a3622;
            border: 1px solid #a3cfbb;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="admin-content">
        <!-- Stats Cards -->
        <div class="pedidos-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pedidos_stats['hoy']; ?></h3>
                    <p>Pedidos Hoy</p>
                    <div class="stat-trend trend-up"><i class="fas fa-arrow-up"></i> 22.5%</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(243,156,18,.15);">
                    <i class="fas fa-clock" style="color:#f39c12;"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pedidos_stats['pendientes']; ?></h3>
                    <p>Pendientes</p>
                    <div class="stat-trend trend-down"><i class="fas fa-arrow-down"></i> 8.3%</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(214,48,49,.12);">
                    <i class="fas fa-search-dollar" style="color:#d63031;"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pedidos_stats['en_verificacion']; ?></h3>
                    <p>En Verificación</p>
                    <div class="stat-trend" style="color:#d63031;font-size:11px;">
                        <i class="fas fa-exclamation-circle"></i> Requieren revisión
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <?php $precios = formatearMonedaDual($pedidos_stats['valor_pendiente']); ?>
                    <h3><span class="moneda-bs"><?php echo $precios['bs']; ?></span> <span class="moneda-usd">(<?php echo $precios['usd']; ?>)</span></h3>
                    <p>Valor Pendiente</p>
                    <div class="stat-trend trend-up"><i class="fas fa-arrow-up"></i> 15.7%</div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Pedidos por Canal</h3>
                    <div class="chart-actions">
                        <select class="chart-filter">
                            <option>Este mes</option>
                            <option>Mes anterior</option>
                        </select>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="canalesChart"></canvas>
                </div>
            </div>
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Tasa de Conversión</h3>
                </div>
                <div class="chart-wrapper">
                    <canvas id="conversionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Integration Status — datos reales desde BD -->
        <div class="integration-section" id="integrationSection">
            <h3 style="margin-bottom: 16px; color: #2c3e50; font-size:15px;">
                Estado de Integraciones
                <span style="font-size:11px;color:#888;font-weight:400;margin-left:8px;"></span>
            </h3>
            <div class="integration-grid" id="integrationGrid">
                <!-- Se carga dinámicamente via JS -->
                <?php
                // Cargar config de integraciones desde BD
                $cfg_int = [];
                try {
                    $conn->exec("CREATE TABLE IF NOT EXISTS configuracion_integraciones (clave VARCHAR(80) PRIMARY KEY, valor TEXT NOT NULL DEFAULT '', updated_at TIMESTAMPTZ DEFAULT NOW())");
                    $cfg_rows = $conn->query("SELECT clave, valor FROM configuracion_integraciones")->fetchAll(PDO::FETCH_KEY_PAIR);
                    $cfg_int = $cfg_rows;
                } catch(Exception $e) { $cfg_int = []; }

                $cfgVal = fn($k, $def='') => $cfg_int[$k] ?? (defined('INTEGRATION_'.strtoupper($k)) ? constant('INTEGRATION_'.strtoupper($k)) : $def);

                $wa_on  = $cfgVal('whatsapp_enabled','0') === '1';
                $em_on  = $cfgVal('email_enabled','0')    === '1';
                $tg_on  = $cfgVal('telegram_enabled','0') === '1';
                $nt_on  = $cfgVal('internal_notifications_enabled','1') === '1';

                $wa_num  = $cfgVal('whatsapp_number','Sin configurar');
                $em_dest = $cfgVal('email_notifications','Sin configurar');
                $tg_user = $cfgVal('telegram_username','');
                $tg_cid  = $cfgVal('telegram_chat_id','');

                // Notificaciones internas pendientes
                $notif_count = 0;
                try { $notif_count = (int)$conn->query("SELECT COUNT(*) FROM notificaciones_vendedor WHERE leida=false")->fetchColumn(); } catch(Exception $e) {}

                $pedidos_hoy = $pedidos_stats['hoy'];

                $items = [
                    [
                        'icon'    => 'fab fa-whatsapp',
                        'clase'   => 'whatsapp',
                        'titulo'  => 'WhatsApp Business',
                        'enabled' => $wa_on,
                        'detalle' => $wa_on  ? $wa_num : 'Deshabilitado',
                        'badge'   => $wa_on  ? "{$pedidos_hoy} pedidos hoy" : null,
                    ],
                    [
                        'icon'    => 'fas fa-envelope',
                        'clase'   => 'email',
                        'titulo'  => 'Email Automático',
                        'enabled' => $em_on,
                        'detalle' => $em_on  ? $em_dest : 'Deshabilitado',
                        'badge'   => null,
                    ],
                    [
                        'icon'    => 'fab fa-telegram',
                        'clase'   => 'telegram',
                        'titulo'  => 'Telegram',
                        'enabled' => $tg_on,
                        'detalle' => $tg_on  ? ($tg_user ? '@'.$tg_user : 'Chat ID: '.$tg_cid) : 'Deshabilitado',
                        'badge'   => null,
                    ],
                    [
                        'icon'    => 'fas fa-bell',
                        'clase'   => 'notifications',
                        'titulo'  => 'Notificaciones Internas',
                        'enabled' => $nt_on,
                        'detalle' => $nt_on  ? 'Panel de vendedores activo' : 'Deshabilitado',
                        'badge'   => $nt_on && $notif_count > 0 ? "{$notif_count} pendientes" : null,
                    ],
                ];
                foreach ($items as $it):
                    $dot   = $it['enabled'] ? '#1F9166' : '#ccc';
                    $txt   = $it['enabled'] ? 'Activo' : 'Inactivo';
                ?>
                <div class="integration-item <?php echo !$it['enabled'] ? 'integration-disabled' : ''; ?>">
                    <div class="integration-icon <?php echo $it['clase']; ?>" style="<?php echo !$it['enabled'] ? 'opacity:0.4;filter:grayscale(1)' : ''; ?>">
                        <i class="<?php echo $it['icon']; ?>"></i>
                    </div>
                    <div class="integration-content">
                        <h4 style="display:flex;align-items:center;gap:7px;margin:0 0 3px;">
                            <?php echo $it['titulo']; ?>
                            <span style="width:8px;height:8px;border-radius:50%;background:<?php echo $dot; ?>;display:inline-block;"></span>
                            <span style="font-size:10px;color:<?php echo $it['enabled']?'#1F9166':'#aaa'; ?>;font-weight:400;"><?php echo $txt; ?></span>
                        </h4>
                        <p style="font-size:12px;color:#666;margin:0;"><?php echo htmlspecialchars($it['detalle']); ?></p>
                        <?php if ($it['badge']): ?>
                            <span style="font-size:11px;background:#e8f6f1;color:#1F9166;padding:2px 8px;border-radius:10px;margin-top:4px;display:inline-block;"><?php echo $it['badge']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="search-filters" style="margin: 20px 0;">
            <form id="pedidosSearchForm" class="search-box" onsubmit="event.preventDefault(); filtrarTabla();" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                <input type="search" id="searchInput" placeholder="Buscar pedidos..." style="flex:1; min-width:220px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" />

                <select id="dateFilter" class="filter-select" style="min-width:160px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;" name="date_range">
                    <option value="">Todos los días</option>
                    <option value="today">Hoy</option>
                    <option value="yesterday">Ayer</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mes</option>
                    <option value="last_month">Mes anterior</option>
                    <option value="custom">Rango personalizado</option>
                </select>

                <select class="filter-select" id="estadoFilter" style="min-width:160px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="">Todos los estados</option>
                <?php
                $estadoOptions = [
                    'PENDIENTE' => 'Pendiente',
                    'EN_VERIFICACION' => 'En Verificación',
                    'COMPLETADO' => 'Completado',
                    'RECHAZADO' => 'Rechazado',
                    'CANCELADO' => 'Cancelado',
                    'INHABILITADO' => 'Inhabilitado',
                ];
                foreach ($estadoOptions as $key => $label):
                ?>
                    <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
                </select>

                <select class="filter-select" id="canalFilter" style="min-width:180px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="">Todos los canales</option>
                    <?php
                    $canalOptions = [
                        'web' => 'Web',
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                        'telegram' => 'Telegram',
                        'notificaciones' => 'Notificación'
                    ];
                    foreach ($canalOptions as $key => $label):
                    ?>
                    <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="button" class="btn btn-secondary" onclick="filtrarTabla()" style="padding: 10px 20px; background: #1F9166; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.3s; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </form>

            <!-- Rango de fechas personalizado (oculto por defecto) -->
            <div id="customDateRange" style="display: none; width: 100%; margin-top: 10px; padding: 15px; background: #f8f9fa; border-radius: 6px; border: 1px solid #ddd;">
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px;">Desde:</label>
                        <input type="date" id="fechaDesdePed" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px;">Hasta:</label>
                        <input type="date" id="fechaHastaPed" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    <button type="button" onclick="filtrarTabla()" style="margin-top: 22px; padding: 8px 16px; background: #1F9166; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                        Aplicar
                    </button>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pedidos-actions">
            <div class="action-buttons">
                <button class="btn btn-primary" id="nuevoPedidoBtn">
                    <i class="fas fa-plus"></i>
                    Nuevo Pedido
                </button>

            </div>
        </div>

        <!-- Pedidos Table - responsive con scroll horizontal -->
        <div class="pedidos-table-wrapper">
        <div class="pedidos-table<?php echo $mostrar_columna_vendedor ? ' has-seller' : ''; ?>">
            <div class="table-header">
                <div>Pedido #</div>
                <div>Cliente</div>
                <?php if ($mostrar_columna_vendedor): ?>
                <div>Vendedor</div>
                <?php endif; ?>
                <div>Canal</div>
                <div>Productos</div>
                <div>Total</div>
                <div>Fecha</div>
                <div>Estado</div>
                <div>Acciones</div>
            </div>
            
            <?php if (!empty($pedidos)): ?>
                <?php foreach ($pedidos as $p): 
                    $fecha = !empty($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : '';
                    $precios = formatearMonedaDual((float)$p['total']);
                    $estado = strtoupper($p['estado_pedido'] ?? 'PENDIENTE');
                    $labelEstado = $statusLabels[$estado] ?? ucfirst(strtolower($estado));
                    $classEstado = $statusClasses[$estado] ?? 'status-pending';

                    // Canal de comunicación real desde la BD
                    $canalRaw = strtolower($p['canal_comunicacion'] ?? 'web');
                    $canalMap = [
                        'whatsapp'       => ['label'=>'WhatsApp',       'icon'=>'fab fa-whatsapp',  'color'=>'#25D366'],
                        'email'          => ['label'=>'Email',           'icon'=>'fas fa-envelope',  'color'=>'#d93025'],
                        'telegram'       => ['label'=>'Telegram',        'icon'=>'fab fa-telegram',  'color'=>'#0088cc'],
                        'notificaciones' => ['label'=>'Notificación',    'icon'=>'fas fa-bell',       'color'=>'#f39c12'],
                        'web'            => ['label'=>'Web',             'icon'=>'fas fa-globe',      'color'=>'#1F9166'],
                    ];
                    $canalInfo  = $canalMap[$canalRaw] ?? $canalMap['web'];
                    $canalLabel = $canalInfo['label'];
                    $canalIcon  = $canalInfo['icon'];
                    $canalClass = 'canal-' . preg_replace('/[^a-z0-9]+/', '-', $canalRaw);
                    $canalHtml  = "<span class='canal-badge {$canalClass}'><i class='{$canalIcon}'></i> {$canalLabel}</span>";

                    $actions = [];
                    $esNotificacion   = ($canalRaw === 'notificaciones');
                    $estaFinalizado   = in_array($estado, ['CONFIRMADO','COMPLETADO']);
                    $estaInhabilitado = in_array($estado, ['CANCELADO','INHABILITADO','RECHAZADO']);

                    if ($isStaff) {
                        // ── Slot 1: 👁️ Ver detalles
                        // Para notificaciones activas el ojo lleva el confirmar DENTRO del modal (ver_detalle_con_confirm)
                        $eyeAction = ($esNotificacion && !$estaFinalizado && !$estaInhabilitado) ? 'ver_detalle_con_confirm' : 'ver_detalle';
                        $actions[] = '<button class="btn btn-icon btn-sm btn-info" data-action="'.$eyeAction.'" data-id="'.$p['id'].'" title="Ver detalles" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;"><i class="fas fa-eye"></i></button>';

                        // ── Slot 2: acción principal según canal y estado ──
                        if ($esNotificacion) {
                            // Canal notificaciones: slot 2 = asignar vendedor (si aplica) o verificar
                            if ($isAdmin && empty($p['vendedor_asignado_id']) && !$estaFinalizado && !$estaInhabilitado) {
                                $actions[] = '<button class="btn btn-icon btn-sm btn-purple" data-action="assign_seller" data-id="'.$p['id'].'" title="Asignar vendedor" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;"><i class="fas fa-user-plus"></i></button>';
                            } elseif ($estado === 'EN_VERIFICACION') {
                                $actions[] = '<button class="btn btn-icon btn-sm btn-success" data-action="approve" data-id="'.$p['id'].'" title="Verificar pago" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;"><i class="fas fa-search-dollar"></i></button>';
                            }
                        } else {
                            // Canal directo: slot 2 = confirmar o verificar
                            if ($estado === 'PENDIENTE') {
                                $actions[] = '<button class="btn btn-icon btn-sm btn-success" data-action="confirmar_directo" data-id="'.$p['id'].'" title="Confirmar pago" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;"><i class="fas fa-check-circle"></i></button>';
                            } elseif ($estado === 'EN_VERIFICACION') {
                                $actions[] = '<button class="btn btn-icon btn-sm btn-success" data-action="approve" data-id="'.$p['id'].'" title="Verificar pago" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;"><i class="fas fa-search-dollar"></i></button>';
                            }
                        }

                        // ── Slot 3: 🚫 cancelar (admin) o 🔄 reactivar ────
                        if ($isAdmin) {
                            if (!$estaFinalizado && !$estaInhabilitado) {
                                $actions[] = '<button class="btn btn-icon btn-sm btn-danger" data-action="cancelar_pedido" data-id="'.$p['id'].'" title="Cancelar pedido" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;"><i class="fas fa-ban"></i></button>';
                            }
                            if ($estaInhabilitado) {
                                $actions[] = '<button class="btn btn-icon btn-sm btn-success" data-action="reactivar_pedido" data-id="'.$p['id'].'" title="Reactivar pedido" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;"><i class="fas fa-redo"></i></button>';
                            }
                        }
                    }

                    $actionsHtml = implode(' ', $actions) ?: '—';
                ?>
                <div class="table-row" data-pedido-id="<?php echo $p['id']; ?>">
                    <div data-label="Pedido #">
                        <strong><?php echo htmlspecialchars($p['codigo_pedido']); ?></strong>
                    </div>
                    <div data-label="Cliente"><?php echo htmlspecialchars($p['cliente_nombre'] ?? $p['telefono_contacto'] ?? '—'); ?></div>
                    <?php if ($mostrar_columna_vendedor): ?>
                    <div data-label="Vendedor"><?php echo htmlspecialchars($p['vendedor_nombre'] ?? 'Sin asignar'); ?></div>
                    <?php endif; ?>
                    <div data-label="Canal"><?php echo $canalHtml; ?></div>
                    <div data-label="Productos"><?php echo htmlspecialchars($p['productos'] ? $p['productos'] : ($p['num_items'] . ' productos')); ?></div>
                    <div data-label="Total"><span class="moneda-bs"><?php echo $precios['bs']; ?></span> <span class="moneda-usd">(<?php echo $precios['usd']; ?>)</span></div>
                    <div data-label="Fecha"><?php echo $fecha; ?></div>
                    <div data-label="Estado">
                        <span class="status-badge <?php echo $classEstado; ?>"><?php echo $labelEstado; ?></span>
                    </div>
                    <div data-label="Acciones" class="actions"><?php echo $actionsHtml ?: '—'; ?></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="table-row">
                    <div colspan="<?php echo $mostrar_columna_vendedor ? 9 : 8; ?>" style="text-align: center; padding: 40px;">No hay pedidos registrados.</div>
                </div>
            <?php endif; ?>
        </div>
        </div> <!-- cierre de pedidos-table-wrapper -->
    </div>

    <!-- Modales -->
    <div class="modal-overlay" id="modalPedidoPago">
        <div class="modal">
            <div class="modal-header">
                <h3>Comprobante de pago</h3>
                <button class="modal-close" id="closeModalPedidoPagoIcon">&times;</button>
            </div>
            <div class="modal-body">
                <p>Ingrese la referencia o número de comprobante para este pedido:</p>
                <input type="text" id="paymentReference" placeholder="Ej. #123456, transferencia, pago en efectivo" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 10px;">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelModalPedidoPagoBtn">Cancelar</button>
                <button class="btn btn-primary" id="confirmarPagoBtn">Guardar</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalActionPicker">
        <div class="modal">
            <div class="modal-header">
                <h3>Cambiar estado de pedido</h3>
                <button class="modal-close" id="closeActionPickerIcon">&times;</button>
            </div>
            <div class="modal-body">
                <p>Selecciona la acción que quieres aplicar:</p>
                <input type="hidden" id="pickerPedidoId" value="">
                <select id="actionPickerSelect" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-top: 10px;"></select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelActionPickBtn">Cancelar</button>
                <button class="btn btn-primary" id="confirmActionPickBtn">Aplicar</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalApprovePayment">
        <div class="modal" style="max-width:520px;">
            <div class="modal-header" style="background:#1F9166;border-radius:8px 8px 0 0;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;color:#fff;font-size:16px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-search-dollar"></i> Verificar Comprobante de Pago
                </h3>
                <button class="modal-close" id="closeModalApprovePayment" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:30px;height:30px;border-radius:50%;font-size:20px;cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body" style="padding:0;max-height:72vh;overflow-y:auto;">

                <!-- Info del pedido -->
                <div style="padding:14px 18px;background:#f8fafb;border-bottom:1px solid #e9ecef;display:grid;grid-template-columns:1fr 1fr;gap:8px 16px;">
                    <div>
                        <p style="margin:0;font-size:10px;text-transform:uppercase;color:#888;font-weight:700;letter-spacing:.04em;">Código</p>
                        <p id="approveCodigoPedido" style="margin:2px 0 0;font-weight:700;color:#1F9166;font-size:14px;">—</p>
                    </div>
                    <div>
                        <p style="margin:0;font-size:10px;text-transform:uppercase;color:#888;font-weight:700;letter-spacing:.04em;">Cliente</p>
                        <p id="approveClienteNombre" style="margin:2px 0 0;font-size:13px;color:#333;">—</p>
                    </div>
                    <div>
                        <p style="margin:0;font-size:10px;text-transform:uppercase;color:#888;font-weight:700;letter-spacing:.04em;">Teléfono</p>
                        <p id="approveTelefono" style="margin:2px 0 0;font-size:13px;color:#333;">—</p>
                    </div>
                    <div>
                        <p style="margin:0;font-size:10px;text-transform:uppercase;color:#888;font-weight:700;letter-spacing:.04em;">Total</p>
                        <p id="approveTotalPedido" style="margin:2px 0 0;font-size:13px;font-weight:700;color:#1F9166;">—</p>
                    </div>
                </div>

                <div style="padding:16px 18px;">

                    <!-- Referencia de pago -->
                    <div style="background:#f0f9f4;border-left:4px solid #1F9166;border-radius:6px;padding:13px 15px;margin-bottom:14px;">
                        <p style="font-size:11px;font-weight:700;color:#1F9166;margin:0 0 5px;text-transform:uppercase;letter-spacing:.04em;">
                            <i class="fas fa-hashtag"></i> Referencia de pago
                        </p>
                        <p id="clienteReferencia" style="font-size:14px;color:#333;margin:0;word-break:break-all;font-weight:600;">Cargando...</p>
                        <p id="approveMetodoPago" style="font-size:11px;color:#666;margin:4px 0 0;"></p>
                    </div>

                    <!-- Comprobante imagen -->
                    <div id="approveComprobanteZone" style="display:none;margin-bottom:14px;">
                        <p style="font-size:11px;font-weight:700;color:#555;margin:0 0 8px;text-transform:uppercase;letter-spacing:.04em;">
                            <i class="fas fa-camera" style="color:#1F9166;"></i> Comprobante adjunto
                        </p>
                        <div style="border:2px solid #1F9166;border-radius:10px;overflow:hidden;background:#000;cursor:zoom-in;text-align:center;"
                             onclick="window.open(document.getElementById('approveComprobanteImg').src,'_blank')">
                            <img id="approveComprobanteImg" src="" alt="Comprobante"
                                 style="max-width:100%;max-height:260px;object-fit:contain;display:block;margin:0 auto;">
                        </div>
                        <p style="font-size:10px;color:#888;text-align:center;margin:5px 0 0;">Clic para ampliar</p>
                    </div>

                    <div id="approveSinComprobante" style="display:none;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:11px 14px;margin-bottom:14px;">
                        <p style="margin:0;font-size:13px;color:#856404;">
                            <i class="fas fa-exclamation-triangle"></i>
                            El cliente no adjuntó imagen. Verifica la referencia manualmente.
                        </p>
                    </div>

                    <!-- Método de pago del sistema -->
                    <div>
                        <label style="font-weight:700;font-size:12px;display:block;margin-bottom:6px;color:#555;text-transform:uppercase;letter-spacing:.03em;">
                            Registrar como método de pago <span style="color:#888;font-weight:400;">(opcional)</span>
                        </label>
                        <select id="approvePaymentMethod" style="width:100%;padding:9px;border:1px solid #ddd;border-radius:6px;font-size:13px;"></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="gap:10px;padding:14px 18px;">
                <button class="btn btn-danger" id="rejectApprovePaymentBtn" style="margin-right:auto;">
                    <i class="fas fa-times"></i> Rechazar
                </button>
                <button class="btn btn-secondary" id="cancelApprovePaymentBtn">Cancelar</button>
                <button class="btn btn-primary" id="confirmApprovePaymentBtn" style="background:#1F9166;color:white;border:none;">
                    <i class="fas fa-check"></i> Aprobar pago
                </button>
            </div>
        </div>
    </div>

    <!-- Modal para asignar vendedor -->
    <div class="modal-overlay" id="modalAssignSeller">
        <div class="modal">
            <div class="modal-header">
                <h3>Asignar Vendedor</h3>
                <button class="modal-close" onclick="closeAssignSellerModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Selecciona un vendedor para asignar a este pedido:</p>
                <select id="sellerSelect" class="form-control" style="width: 100%; padding: 8px; margin: 10px 0;">
                    <option value="">-- Seleccionar Vendedor --</option>
                    <?php foreach ($vendedores as $v): ?>
                        <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['nombre_completo']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="assignSellerError" style="color: red; display: none;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeAssignSellerModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="confirmAssignSeller()">Asignar</button>
            </div>
        </div>
    </div>

    <!-- Modal confirmar pago directo (WhatsApp / Email / Telegram) -->
    <div class="modal-overlay" id="modalConfirmarDirecto">
        <div class="modal" style="max-width:460px;">
            <div class="modal-header">
                <h3><i class="fas fa-check-circle" style="color:#1F9166;margin-right:6px;"></i>Confirmar Pago</h3>
                <button class="modal-close" onclick="closeConfirmarDirectoModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size:13px;color:#555;margin-bottom:16px;">
                    Ingresa los datos del pago recibido del cliente para completar el pedido.
                </p>

                <!-- Tipo de pago -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
                    <label id="cdTipoMovil" onclick="cdSeleccionarTipo('movil')"
                        style="border:2px solid #e0e0e0;border-radius:10px;padding:12px;cursor:pointer;text-align:center;transition:all .2s;">
                        <i class="fas fa-mobile-alt" style="font-size:1.3rem;color:#1F9166;display:block;margin-bottom:5px;"></i>
                        <span style="font-size:13px;font-weight:600;">Pago Móvil</span>
                    </label>
                    <label id="cdTipoTransf" onclick="cdSeleccionarTipo('transferencia')"
                        style="border:2px solid #e0e0e0;border-radius:10px;padding:12px;cursor:pointer;text-align:center;transition:all .2s;">
                        <i class="fas fa-university" style="font-size:1.3rem;color:#2c7be5;display:block;margin-bottom:5px;"></i>
                        <span style="font-size:13px;font-weight:600;">Transferencia</span>
                    </label>
                </div>
                <input type="hidden" id="cdTipoSeleccionado" value="">

                <div id="cdCamposMovil" style="display:none;">
                    <div style="margin-bottom:10px;">
                        <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Banco</label>
                        <input type="text" id="cdMovilBanco" placeholder="Ej: Banesco, Mercantil..."
                            style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Teléfono del cliente</label>
                        <input type="text" id="cdMovilTelefono" placeholder="Ej: 0412-1234567"
                            style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">N° Referencia / Confirmación <span style="color:#e74c3c;">*</span></label>
                        <input type="text" id="cdMovilRef" placeholder="Número de confirmación"
                            style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box;">
                    </div>
                </div>

                <div id="cdCamposTransf" style="display:none;">
                    <div style="margin-bottom:10px;">
                        <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Banco origen</label>
                        <input type="text" id="cdTransfBanco" placeholder="Ej: Banesco, Mercantil..."
                            style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box;">
                    </div>
                    <div style="margin-bottom:10px;">
                        <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">N° Referencia <span style="color:#e74c3c;">*</span></label>
                        <input type="text" id="cdTransfRef" placeholder="Ej: 00123456789"
                            style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Monto recibido (Bs)</label>
                        <input type="number" id="cdTransfMonto" placeholder="Ej: 46.40" step="0.01"
                            style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box;">
                    </div>
                </div>

                <div id="cdError" style="display:none;color:#e74c3c;font-size:12px;margin-top:10px;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeConfirmarDirectoModal()">Cancelar</button>
                <button class="btn btn-primary" id="cdConfirmarBtn">
                    <i class="fas fa-check"></i> Completar pedido
                </button>
            </div>
        </div>
    </div>

    <script>
        // Gráficas
        const canalesCtx = document.getElementById('canalesChart').getContext('2d');
        new Chart(canalesCtx, {
            type: 'doughnut',
            data: {
                labels: ['WhatsApp', 'Web', 'Email', 'Teléfono'],
                datasets: [{
                    data: [45, 35, 15, 5],
                    backgroundColor: ['#25D366', '#1F9166', '#EA4335', '#FF6B00'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        const conversionCtx = document.getElementById('conversionChart').getContext('2d');
        new Chart(conversionCtx, {
            type: 'bar',
            data: {
                labels: ['Ene', 'Feb', 'Mar'],
                datasets: [{
                    label: 'Tasa de Conversión (%)',
                    data: [12, 15, 18],
                    backgroundColor: '#1F9166',
                    borderColor: '#187a54',
                    borderWidth: 2,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 25,
                        ticks: { callback: v => v + '%' }
                    }
                }
            }
        });

        // Variables globales
        let currentPedidoId = null;
        const currentUserRole = '<?php echo addslashes($user_role); ?>';
        const apiUpdatePedido = '<?php echo rtrim(BASE_URL, "/"); ?>/api/update_pedido_estado.php';
        const actionLabels = <?php echo json_encode($actionLabels); ?>;
        const transitionMap = <?php echo json_encode($transitionMap); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // Botones principales
            document.getElementById('nuevoPedidoBtn')?.addEventListener('click', () => {
                if (window.Toast) Toast.info('Usa el formulario de nueva venta para crear pedidos manuales', 'Nuevo Pedido');
                else alert('Redirigiendo al formulario de nuevo pedido...');
            });
            document.getElementById('whatsappBtn')?.addEventListener('click', () => {
                if (window.Toast) Toast.info('Configura WhatsApp Business en la sección de Configuración > Integraciones', 'WhatsApp');
                else alert('Funcionalidad de WhatsApp');
            });
            document.getElementById('emailBtn')?.addEventListener('click', () => {
                if (window.Toast) Toast.info('Configura Email en la sección de Configuración > Integraciones', 'Email');
                else alert('Funcionalidad de Email');
            });


            // Acciones de pedidos — flujo por canal y rol
            document.querySelectorAll('button[data-action]').forEach(btn => {
                btn.addEventListener('click', function() {
                    const pedidoId = this.getAttribute('data-id');
                    const action   = this.getAttribute('data-action');
                    if (action === 'approve') {
                        openApproveModal(pedidoId);
                    } else if (action === 'confirmar_directo') {
                        openConfirmarDirectoModal(pedidoId);
                    } else if (action === 'assign_seller') {
                        openAssignSellerModal(pedidoId);
                    } else if (action === 'ver_detalle' || action === 'ver_detalle_con_confirm') {
                        abrirModalDetalle(pedidoId, action === 'ver_detalle_con_confirm');
                    } else if (action === 'cancelar_pedido') {
                        confirmarCancelarPedido(pedidoId);
                    } else if (action === 'reactivar_pedido') {
                        confirmarReactivarPedido(pedidoId);
                    } else {
                        changePedidoStatus(pedidoId, action);
                    }
                });
            });

            // Botones del modal verificar comprobante
            document.getElementById('confirmApprovePaymentBtn')?.addEventListener('click', async function() {
                if (!currentPedidoId) return;
                const pedidoId = currentPedidoId;
                const metodo = document.getElementById('approvePaymentMethod')?.value || null;
                closeApproveModal();
                try {
                    const body = { pedido_id: parseInt(pedidoId), action: 'approve' };
                    if (metodo) body.metodo_pago_id = parseInt(metodo);
                    const r = await fetch(apiUpdatePedido, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(body)
                    });
                    const d = await r.json();
                    if (d.success) {
                        Toast.success(d.message || 'Pago aprobado — pedido completado', '¡Completado!');
                        setTimeout(() => location.reload(), 1400);
                    } else {
                        Toast.error(d.message || 'No se pudo aprobar el pago', 'Error');
                    }
                } catch(e) {
                    Toast.error('Error de conexión', 'Error de red');
                }
            });


            document.getElementById('cancelApprovePaymentBtn')?.addEventListener('click', closeApproveModal);
            document.getElementById('closeModalApprovePayment')?.addEventListener('click', closeApproveModal);
            document.getElementById('modalApprovePayment')?.addEventListener('click', e => {
                if (e.target === e.currentTarget) closeApproveModal();
            });

            // Rechazar pago desde el modal de verificación
            document.getElementById('rejectApprovePaymentBtn')?.addEventListener('click', async function() {
                if (!currentPedidoId) return;
                const pedidoId = currentPedidoId;
                const confirmed = await showConfirm({
                    title: 'Rechazar pago',
                    message: 'El pedido quedará en estado RECHAZADO y el cliente será notificado.',
                    type: 'danger', confirmText: 'Sí, rechazar', cancelText: 'Cancelar'
                });
                if (!confirmed) return;
                closeApproveModal();
                try {
                    const r = await fetch(apiUpdatePedido, {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ pedido_id: parseInt(pedidoId), action: 'rechazar' })
                    });
                    const d = await r.json();
                    if (d.success) { Toast.canceled(d.message || 'Pedido rechazado', 'Rechazado'); setTimeout(() => location.reload(), 1400); }
                    else Toast.error(d.message || 'No se pudo rechazar', 'Error');
                } catch(e) { Toast.error('Error de conexión', 'Error'); }
            });

            // Búsqueda y filtros
            document.getElementById('searchInput')?.addEventListener('keyup', filtrarTabla);
            document.getElementById('estadoFilter')?.addEventListener('change', filtrarTabla);
            document.getElementById('canalFilter')?.addEventListener('change', filtrarTabla);

            // Configurar filtro de rango de fechas (similar a ventas/compras/inventario)
            const dateFilter = document.getElementById('dateFilter');
            const customDateRange = document.getElementById('customDateRange');
            const fechaDesde = document.getElementById('fechaDesdePed');
            const fechaHasta = document.getElementById('fechaHastaPed');

            const formatDate = d => d.toISOString().slice(0, 10);

            function applyDateRange(range) {
                const today = new Date();
                if (!dateFilter) return;

                const setRange = (from, to) => {
                    fechaDesde.value = from;
                    fechaHasta.value = to;
                    customDateRange.style.display = 'none';
                };

                if (range === 'today') {
                    const todayStr = formatDate(today);
                    setRange(todayStr, todayStr);
                } else if (range === 'yesterday') {
                    const yesterday = new Date(today);
                    yesterday.setDate(today.getDate() - 1);
                    const yStr = formatDate(yesterday);
                    setRange(yStr, yStr);
                } else if (range === 'week') {
                    const startOfWeek = new Date(today);
                    startOfWeek.setDate(today.getDate() - today.getDay() + 1);
                    setRange(formatDate(startOfWeek), formatDate(today));
                } else if (range === 'month') {
                    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    setRange(formatDate(startOfMonth), formatDate(today));
                } else if (range === 'last_month') {
                    const startOfLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const endOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                    setRange(formatDate(startOfLastMonth), formatDate(endOfLastMonth));
                } else if (range === 'custom') {
                    fechaDesde.value = '';
                    fechaHasta.value = '';
                    customDateRange.style.display = 'block';
                } else {
                    fechaDesde.value = '';
                    fechaHasta.value = '';
                    customDateRange.style.display = 'none';
                }

                filtrarTabla();
            }

            dateFilter?.addEventListener('change', function() {
                applyDateRange(this.value);
            });
            fechaDesde?.addEventListener('change', filtrarTabla);
            fechaHasta?.addEventListener('change', filtrarTabla);
        });

        function openPaymentModal(pedidoId) {
            currentPedidoId = pedidoId;
            document.getElementById('paymentReference').value = '';
            document.getElementById('modalPedidoPago').classList.add('active');
        }

        function closePaymentModal() {
            document.getElementById('modalPedidoPago').classList.remove('active');
            currentPedidoId = null;
        }

        function openActionPicker(pedidoId, currentState) {
            let actions = transitionMap[currentState] || [];

            // Los vendedores solo deben poder marcar pago / confirmar pago.
            // No deben ver la opción de inhabilitar.
            if (currentUserRole === 'Vendedor') {
                actions = actions.filter(a => a !== 'inhabilitar');
            }

            const select = document.getElementById('actionPickerSelect');
            select.innerHTML = '';

            if (!actions.length) {
                if (window.Toast) Toast.warning('No hay acciones disponibles para el estado actual del pedido', 'Sin acciones');
                else alert('No hay acciones disponibles para el estado actual.');
                return;
            }

            actions.forEach(act => {
                const option = document.createElement('option');
                option.value = act;
                option.textContent = actionLabels[act] || act;
                select.appendChild(option);
            });

            document.getElementById('pickerPedidoId').value = pedidoId;
            document.getElementById('modalActionPicker').classList.add('active');
        }

        function closeActionPicker() {
            document.getElementById('modalActionPicker').classList.remove('active');
        }

        async function loadPaymentMethods() {
            try {
                const resp = await fetch('<?php echo rtrim(BASE_URL, "/"); ?>/api/get_metodos_pago.php');
                const json = await resp.json();
                const select = document.getElementById('approvePaymentMethod');
                if (!select) return;
                select.innerHTML = '<option value="">-- Seleccionar método --</option>';
                if (json && json.success && Array.isArray(json.metodos)) {
                    json.metodos.forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.nombre;
                        select.appendChild(opt);
                    });
                }
            } catch (err) {
                console.error('Error cargando métodos de pago:', err);
            }
        }

        async function openApproveModal(pedidoId) {
            currentPedidoId = pedidoId;

            // Reset UI
            document.getElementById('clienteReferencia').textContent  = 'Cargando...';
            document.getElementById('approveCodigoPedido').textContent = '—';
            document.getElementById('approveClienteNombre').textContent = '—';
            document.getElementById('approveTelefono').textContent     = '—';
            document.getElementById('approveTotalPedido').innerHTML    = '—';
            document.getElementById('approveMetodoPago').textContent   = '';
            document.getElementById('approveComprobanteZone').style.display = 'none';
            document.getElementById('approveSinComprobante').style.display  = 'none';

            document.getElementById('modalApprovePayment').classList.add('active');

            try {
                const r = await fetch(`<?php echo rtrim(BASE_URL,"/"); ?>/api/get_pedido_detalle.php?id=${pedidoId}`);
                const j = await r.json();

                if (j.success && j.pedido) {
                    const p = j.pedido;
                    const tasa = TASA_CAMBIO;
                    const fmtBs = v => 'Bs ' + (parseFloat(v||0)*tasa).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,'.');
                    const fmtUsd = v => '($' + parseFloat(v||0).toFixed(2) + ')';

                    // Datos del pedido
                    document.getElementById('approveCodigoPedido').textContent  = p.codigo_pedido || '—';
                    document.getElementById('approveClienteNombre').textContent = p.cliente_nombre || '—';
                    document.getElementById('approveTelefono').textContent      = p.telefono_contacto || '—';
                    document.getElementById('approveTotalPedido').innerHTML     =
                        `<span class="moneda-bs">${fmtBs(p.total)}</span> <span class="moneda-usd">${fmtUsd(p.total)}</span>`;

                    // Referencia de pago
                    const rp  = (p.referencia_pago || '').trim();
                    const obs = (p.observaciones   || '').trim();
                    const enObs = obs.includes('Pago:') ? obs.replace(/^[^|]*\|\s*/, '').trim() : '';
                    const ref = rp || enObs;
                    const refEl = document.getElementById('clienteReferencia');
                    if (ref) {
                        refEl.textContent = ref;
                        refEl.style.color = '#1F9166';
                    } else {
                        refEl.textContent = '⚠️ Sin referencia — puede ser efectivo o pendiente de confirmar';
                        refEl.style.color = '#856404';
                    }

                    // Método de pago del cliente
                    if (p.metodo_pago) {
                        document.getElementById('approveMetodoPago').textContent = 'Método: ' + p.metodo_pago;
                    }

                    // Comprobante imagen
                    if (p.comprobante_url) {
                        const imgSrc = (APP_BASE || '') + p.comprobante_url;
                        document.getElementById('approveComprobanteImg').src = imgSrc;
                        document.getElementById('approveComprobanteZone').style.display = 'block';
                    } else {
                        document.getElementById('approveSinComprobante').style.display = 'block';
                    }
                } else {
                    document.getElementById('clienteReferencia').textContent = 'Error al cargar datos del pedido.';
                }
            } catch(e) {
                document.getElementById('clienteReferencia').textContent = 'Error de conexión.';
            }

            loadPaymentMethods();
        }

        function closeApproveModal() {
            document.getElementById('modalApprovePayment').classList.remove('active');
            currentPedidoId = null;
        }

        async function changePedidoStatus(pedidoId, action, extra = {}) {
            if (!pedidoId || !action) return;
            const _ti = { 'aprobar':'¿Aprobar y completar?', 'approve':'¿Confirmar el pago verificado?', 'rechazar':'¿Rechazar pago?', 'cancelar':'¿Cancelar pedido?', 'inhabilitar':'¿Inhabilitar pedido?' };
            const _ic = { 'rechazar':'danger', 'cancelar':'warning', 'inhabilitar':'danger' };
            const confirmed = await showConfirm({
                title:       _ti[action] || '¿Confirmar acción?',
                message:     'Esta acción cambiará el estado del pedido.',
                type:        _ic[action] || 'info',
                confirmText: 'Sí, continuar',
                cancelText:  'Cancelar'
            });
            if (!confirmed) return;
            try {
                const response = await fetch(apiUpdatePedido, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pedido_id: parseInt(pedidoId), action, ...extra })
                });
                const text = await response.text();
                let result;
                try { result = JSON.parse(text); }
                catch (e) { Toast.error('Respuesta inválida del servidor', 'Error'); return; }
                if (result.success) {
                    const _ms = { 'aprobar':['¡Aprobado!','success'], 'approve':['¡Completado!','success'], 'rechazar':['Rechazado','canceled'], 'cancelar':['Cancelado','canceled'], 'inhabilitar':['Inhabilitado','canceled'] };
                    const [titulo, tipo] = _ms[action] || ['Actualizado','success'];
                    Toast[tipo](result.message || 'Estado actualizado correctamente', titulo);
                    setTimeout(() => window.location.reload(), 1400);
                } else {
                    Toast.error(result.message || 'No se pudo actualizar el estado', 'Error');
                }
            } catch (err) {
                Toast.error('Error de red: ' + err.message, 'Error de conexión');
            }
        }

        // Funciones para asignar vendedor
        let currentPedidoIdForAssign = null;

        function openAssignSellerModal(pedidoId) {
            currentPedidoIdForAssign = pedidoId;
            document.getElementById('sellerSelect').value = '';
            document.getElementById('assignSellerError').style.display = 'none';
            document.getElementById('modalAssignSeller').classList.add('active');
        }

        function closeAssignSellerModal() {
            document.getElementById('modalAssignSeller').classList.remove('active');
            currentPedidoIdForAssign = null;
        }

        async function confirmAssignSeller() {
            const sellerId = document.getElementById('sellerSelect').value;
            if (!sellerId) {
                document.getElementById('assignSellerError').textContent = 'Por favor selecciona un vendedor.';
                document.getElementById('assignSellerError').style.display = 'block';
                return;
            }

            try {
                const response = await fetch(apiUpdatePedido, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        pedido_id: currentPedidoIdForAssign,
                        action: 'assign_seller',
                        seller_id: sellerId
                    })
                });
                const result = await response.json();
                if (result.success) {
                    closeAssignSellerModal();
                    if (window.Toast) Toast.success(result.message || 'Vendedor asignado correctamente', '¡Asignado!');
                    else alert(result.message || 'Vendedor asignado correctamente');
                    setTimeout(() => window.location.reload(), 1400);
                } else {
                    if (window.Toast) Toast.error(result.message || 'No se pudo asignar el vendedor', 'Error');
                    else alert(result.message || 'Error');
                }
            } catch (err) {
                console.error(err);
                if (window.Toast) Toast.error('Error de conexión al asignar vendedor', 'Error de red');
                else alert('Error al asignar vendedor');
                closeAssignSellerModal();
            }
        }

        function filtrarPorEstado(estado) {
            document.getElementById('estadoFilter').value = estado;
            filtrarTabla();
        }

        function filtrarTabla() {
            const search  = document.getElementById('searchInput')?.value.toLowerCase() || '';
            const estado  = document.getElementById('estadoFilter')?.value || '';
            const canal   = (document.getElementById('canalFilter')?.value || '').toLowerCase();
            const desde   = document.getElementById('fechaDesdePed')?.value || '';
            const hasta   = document.getElementById('fechaHastaPed')?.value || '';

            document.querySelectorAll('.table-row[data-pedido-id]').forEach(row => {
                const texto     = row.textContent.toLowerCase();
                const estadoRow = row.querySelector('.status-badge')?.textContent.toLowerCase() || '';
                // Canal: buscar en el badge de canal
                const canalRow  = (row.querySelector('.canal-badge')?.textContent || '').toLowerCase().trim();
                // Fecha: última celda de fecha (col-fecha o similar)
                const fechaTxt  = row.querySelectorAll('div')[6]?.textContent?.trim() || '';
                let fechaISO = '';
                if (fechaTxt) {
                    const p = fechaTxt.split('/');
                    if (p.length === 3) fechaISO = `${p[2]}-${p[1]}-${p[0]}`;
                }

                let mostrar = true;
                if (search && !texto.includes(search))           mostrar = false;
                if (estado && !estadoRow.includes(estado.toLowerCase())) mostrar = false;
                if (canal  && canal !== 'todos los canales' && !canalRow.includes(canal)) mostrar = false;
                if (desde  && fechaISO && fechaISO < desde)      mostrar = false;
                if (hasta  && fechaISO && fechaISO > hasta)      mostrar = false;

                row.style.display = mostrar ? '' : 'none';
            });
        }

        // ── Modal confirmar pago directo (WhatsApp / Email / Telegram) ──
        function openConfirmarDirectoModal(pedidoId) {
            currentPedidoId = pedidoId;
            cdSeleccionarTipo(null);
            document.getElementById('cdError').style.display = 'none';
            ['cdMovilBanco','cdMovilTelefono','cdMovilRef','cdTransfBanco','cdTransfRef','cdTransfMonto']
                .forEach(id => { const el = document.getElementById(id); if(el) el.value = ''; });
            document.getElementById('modalConfirmarDirecto').classList.add('active');
        }

        function closeConfirmarDirectoModal() {
            document.getElementById('modalConfirmarDirecto').classList.remove('active');
            currentPedidoId = null;
        }

        function cdSeleccionarTipo(tipo) {
            document.getElementById('cdTipoSeleccionado').value = tipo || '';
            const movil  = document.getElementById('cdTipoMovil');
            const transf = document.getElementById('cdTipoTransf');
            const cMovil = document.getElementById('cdCamposMovil');
            const cTransf= document.getElementById('cdCamposTransf');

            movil.style.borderColor  = '#e0e0e0'; movil.style.background  = '';
            transf.style.borderColor = '#e0e0e0'; transf.style.background = '';
            cMovil.style.display = 'none'; cTransf.style.display = 'none';

            if (tipo === 'movil') {
                movil.style.borderColor = '#1F9166'; movil.style.background = '#f0f9f4';
                cMovil.style.display = 'block';
            } else if (tipo === 'transferencia') {
                transf.style.borderColor = '#2c7be5'; transf.style.background = '#f0f5ff';
                cTransf.style.display = 'block';
            }
        }

        document.getElementById('cdConfirmarBtn')?.addEventListener('click', async function() {
            const tipo  = document.getElementById('cdTipoSeleccionado').value;
            const errEl = document.getElementById('cdError');
            errEl.style.display = 'none';

            if (!tipo) {
                errEl.textContent = 'Selecciona el método de pago recibido';
                errEl.style.display = 'block'; return;
            }

            let referencia = '';
            if (tipo === 'movil') {
                const banco = document.getElementById('cdMovilBanco').value.trim();
                const tel   = document.getElementById('cdMovilTelefono').value.trim();
                const ref   = document.getElementById('cdMovilRef').value.trim();
                
                // Validar teléfono si está presente
                if (tel && !InvValidate.phone({value: tel}, 'Teléfono')) return;
                
                if (!ref) { errEl.textContent = 'El número de confirmación es obligatorio'; errEl.style.display = 'block'; return; }
                referencia = `Pago Móvil` + (banco ? ` | Banco: ${banco}` : '') + (tel ? ` | Tel: ${tel}` : '') + ` | Ref: ${ref}`;
            } else {
                const banco = document.getElementById('cdTransfBanco').value.trim();
                const ref   = document.getElementById('cdTransfRef').value.trim();
                const monto = document.getElementById('cdTransfMonto').value.trim();
                if (!ref) { errEl.textContent = 'El número de referencia es obligatorio'; errEl.style.display = 'block'; return; }
                referencia = `Transferencia` + (banco ? ` | Banco: ${banco}` : '') + ` | Ref: ${ref}` + (monto ? ` | Monto: Bs ${monto}` : '');
            }

            if (!currentPedidoId) {
                errEl.textContent = 'Error: no hay pedido seleccionado. Cierra el modal y vuelve a intentarlo.';
                errEl.style.display = 'block'; return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            try {
                const url = apiUpdatePedido;
                const body = JSON.stringify({ pedido_id: parseInt(currentPedidoId), action: 'upload_proof', payment_reference: referencia });
                const r = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: body
                });
                // Leer como texto primero para ver si hay error PHP
                const text = await r.text();
                let d;
                try {
                    d = JSON.parse(text);
                } catch(parseErr) {
                    errEl.textContent = 'Respuesta inválida del servidor: ' + text.substring(0, 200);
                    errEl.style.display = 'block';
                    if (window.Toast) Toast.error('Respuesta inválida del servidor', 'Error');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-check"></i> Completar pedido';
                    return;
                }
                if (d.success) {
                    closeConfirmarDirectoModal();
                    Toast.success(d.message || 'Pago registrado — pedido pasa a verificación', '¡En verificación!');
                    setTimeout(() => location.reload(), 1400);
                } else {
                    errEl.textContent = d.message || 'Error al procesar';
                    errEl.style.display = 'block';
                }
            } catch(e) {
                errEl.textContent = 'Error de red: ' + e.message + ' | URL: ' + apiUpdatePedido;
                errEl.style.display = 'block';
                console.error('fetch error:', e);
            }
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-check"></i> Completar pedido';
        });

        document.getElementById('modalConfirmarDirecto')?.addEventListener('click', e => {
            if (e.target === e.currentTarget) closeConfirmarDirectoModal();
        });

        // ── VER DETALLE ────────────────────────────────────────────────
        async function abrirModalDetalle(pedidoId, showConfirmarBtn = false) {
            let modal = document.getElementById('modalVerDetalle');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'modalVerDetalle';
                modal.className = 'modal-overlay';
                modal.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);align-items:center;justify-content:center;z-index:9999;padding:16px;';
                modal.innerHTML = `
                    <div class="modal" style="max-width:620px;border-radius:12px;overflow:hidden;">
                        <div class="modal-header" style="background:#1F9166;border-radius:0;padding:14px 20px;display:flex;justify-content:space-between;align-items:center;">
                            <h3 style="margin:0;color:#fff;font-size:15px;display:flex;align-items:center;gap:8px;">
                                <i class="fas fa-file-alt"></i> Detalle del Pedido
                            </h3>
                            <button class="modal-close" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:30px;height:30px;border-radius:50%;font-size:20px;cursor:pointer;"
                                    onclick="document.getElementById('modalVerDetalle').style.display='none'">&times;</button>
                        </div>
                        <div class="modal-body" id="verDetalleBody" style="padding:0;max-height:70vh;overflow-y:auto;"></div>
                        <div class="modal-footer" style="padding:12px 18px;border-top:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                            <div id="verDetalleConfirmarZone"></div>
                            <button class="btn btn-secondary" onclick="document.getElementById('modalVerDetalle').style.display='none'">
                                <i class="fas fa-times"></i> Cerrar
                            </button>
                        </div>
                    </div>`;
                modal.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });
                document.body.appendChild(modal);
            }
            modal.style.display = 'flex';

            const zone = document.getElementById('verDetalleConfirmarZone');
            if (zone) {
                zone.innerHTML = showConfirmarBtn
                    ? `<button class="btn btn-primary" onclick="document.getElementById('modalVerDetalle').style.display='none';openApproveModal(${pedidoId})">
                           <i class="fas fa-search-dollar"></i> Verificar pago
                       </button>`
                    : '';
            }

            const body = document.getElementById('verDetalleBody');
            body.innerHTML = '<div style="text-align:center;padding:36px;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:#1F9166;"></i></div>';

            try {
                const apiBase = '<?php echo rtrim(BASE_URL, "/"); ?>';
                const r = await fetch(`${apiBase}/api/get_pedido_detalle.php?id=${pedidoId}`);
                const j = await r.json();
                if (!j.success) { body.innerHTML = `<p style="color:#e74c3c;padding:20px;">Error al cargar el pedido.</p>`; return; }

                const p     = j.pedido;
                const items = j.items || [];
                const tasa  = TASA_CAMBIO;
                const fmtBs  = v => `<span class="moneda-bs">Bs ${(parseFloat(v||0)*tasa).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,'.')}</span>`;
                const fmtUsd = v => `<span class="moneda-usd">($${parseFloat(v||0).toFixed(2)})</span>`;
                const fmt    = v => fmtBs(v) + ' ' + fmtUsd(v);

                const rp       = (p.referencia_pago || '').trim();
                const obs      = (p.observaciones   || '').trim();
                const enObs    = obs.includes('Pago:') ? obs.replace(/^[^|]*\|\s*/, '').trim() : '';
                const refMostrar = rp || enObs || null;

                const estadoColors = {
                    'PENDIENTE':       '#fff3cd|#856404',
                    'EN_VERIFICACION': '#fff0f0|#c0392b',
                    'COMPLETADO':      '#d1e7dd|#0a3622',
                    'RECHAZADO':       '#f8d7da|#721c24',
                    'CANCELADO':       '#f8d7da|#721c24',
                };
                const [estadoBg, estadoColor] = (estadoColors[p.estado_pedido?.toUpperCase()] || '#e9ecef|#333').split('|');

                const filas = items.map(it => `
                    <tr>
                        <td style="padding:8px 10px;border-bottom:1px solid #f0f0f0;">${it.nombre}</td>
                        <td style="padding:8px 10px;border-bottom:1px solid #f0f0f0;text-align:center;">${it.cantidad}</td>
                        <td style="padding:8px 10px;border-bottom:1px solid #f0f0f0;text-align:right;">${fmt(it.precio_unitario)}</td>
                        <td style="padding:8px 10px;border-bottom:1px solid #f0f0f0;text-align:right;font-weight:700;">${fmt(it.subtotal)}</td>
                    </tr>`).join('');

                const entregaTxt = p.tipo_entrega === 'domicilio'
                    ? `<i class="fas fa-motorcycle" style="color:#3498db;"></i> Delivery${p.direccion_entrega ? ': ' + p.direccion_entrega : ''}`
                    : `<i class="fas fa-store" style="color:#1F9166;"></i> Retiro en tienda`;

                const comprobante_url = p.comprobante_url || '';
                const comprobanteHtml = comprobante_url
                    ? `<div style="margin-top:12px;">
                           <p style="font-size:11px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.04em;margin:0 0 7px;">
                               <i class="fas fa-camera" style="color:#1F9166;"></i> Comprobante de pago
                           </p>
                           <div style="border:2px solid #1F9166;border-radius:10px;overflow:hidden;cursor:zoom-in;text-align:center;background:#000;"
                                onclick="window.open('${(APP_BASE||'')+comprobante_url}','_blank')">
                               <img src="${(APP_BASE||'')+comprobante_url}" alt="Comprobante"
                                    style="max-width:100%;max-height:240px;object-fit:contain;display:block;margin:0 auto;">
                           </div>
                           <p style="font-size:10px;color:#888;text-align:center;margin:5px 0 0;">Clic para ampliar</p>
                       </div>`
                    : '';

                const vendedorHtml = p.vendedor_nombre
                    ? `<div style="display:flex;align-items:center;gap:8px;padding:10px 13px;background:#f0f9f4;border-radius:8px;border:1px solid #c3e6cb;margin-bottom:12px;">
                           <i class="fas fa-user-tie" style="color:#1F9166;font-size:1.1rem;"></i>
                           <div>
                               <p style="margin:0;font-size:10px;text-transform:uppercase;color:#555;font-weight:700;">Vendedor asignado</p>
                               <p style="margin:2px 0 0;font-size:13px;font-weight:600;color:#1F9166;">${p.vendedor_nombre}</p>
                           </div>
                       </div>`
                    : '';

                body.innerHTML = `
                    <!-- Cabecera con código y estado -->
                    <div style="padding:14px 18px;background:#f8fafb;border-bottom:1px solid #e9ecef;display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <p style="margin:0;font-size:10px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Código del Pedido</p>
                            <p style="margin:3px 0 0;font-weight:800;font-size:1.1rem;color:#1F9166;">${p.codigo_pedido}</p>
                        </div>
                        <span style="padding:6px 14px;border-radius:30px;font-size:12px;font-weight:700;background:${estadoBg};color:${estadoColor};">${p.estado_pedido}</span>
                    </div>

                    <div style="padding:16px 18px;">
                        ${vendedorHtml}

                        <!-- Info del cliente -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 16px;margin-bottom:14px;">
                            <div>
                                <p style="margin:0;font-size:10px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Cliente</p>
                                <p style="margin:2px 0 0;font-size:13px;font-weight:600;">${p.cliente_nombre || '—'}</p>
                            </div>
                            <div>
                                <p style="margin:0;font-size:10px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Teléfono</p>
                                <p style="margin:2px 0 0;font-size:13px;">${p.telefono_contacto || '—'}</p>
                            </div>
                            <div>
                                <p style="margin:0;font-size:10px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Canal</p>
                                <p style="margin:2px 0 0;font-size:13px;">${p.canal_comunicacion || '—'}</p>
                            </div>
                            <div>
                                <p style="margin:0;font-size:10px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Entrega</p>
                                <p style="margin:2px 0 0;font-size:13px;">${entregaTxt}</p>
                            </div>
                        </div>

                        <!-- Productos -->
                        <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:14px;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);">
                            <thead>
                                <tr style="background:#f8f9fa;">
                                    <th style="padding:9px 10px;text-align:left;font-weight:600;font-size:11px;color:#555;text-transform:uppercase;border-bottom:2px solid #e9ecef;">Producto</th>
                                    <th style="padding:9px 10px;text-align:center;font-weight:600;font-size:11px;color:#555;text-transform:uppercase;border-bottom:2px solid #e9ecef;">Cant.</th>
                                    <th style="padding:9px 10px;text-align:right;font-weight:600;font-size:11px;color:#555;text-transform:uppercase;border-bottom:2px solid #e9ecef;">Precio</th>
                                    <th style="padding:9px 10px;text-align:right;font-weight:600;font-size:11px;color:#555;text-transform:uppercase;border-bottom:2px solid #e9ecef;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>${filas}</tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="padding:10px;text-align:right;font-weight:700;font-size:13px;border-top:2px solid #1F9166;">TOTAL</td>
                                    <td style="padding:10px;text-align:right;font-weight:800;font-size:14px;border-top:2px solid #1F9166;color:#1F9166;">${fmt(p.total)}</td>
                                </tr>
                            </tfoot>
                        </table>

                        <!-- Pago del cliente -->
                        ${refMostrar || comprobante_url ? `
                        <div style="background:#f8fafb;border:1px solid #e9ecef;border-radius:10px;padding:13px 15px;">
                            <p style="font-size:11px;font-weight:700;color:#2c3e50;text-transform:uppercase;letter-spacing:.04em;margin:0 0 10px;display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-credit-card" style="color:#1F9166;"></i> Información de Pago
                            </p>
                            ${p.metodo_pago ? `<p style="margin:0 0 6px;font-size:12px;"><b>Método:</b> ${p.metodo_pago}</p>` : ''}
                            ${refMostrar ? `<p style="margin:0 0 4px;font-size:13px;"><b>Referencia:</b>
                                <span style="color:#1F9166;font-weight:700;">${refMostrar}</span></p>` : ''}
                            ${comprobanteHtml}
                        </div>` : ''}

                        ${obs && !obs.includes('Pago:') ? `<div style="margin-top:12px;padding:10px 13px;background:#fffbf0;border:1px solid #ffeaa7;border-radius:8px;font-size:13px;"><b style="color:#c17f00;"><i class="fas fa-comment-dots"></i> Observaciones:</b> ${obs}</div>` : ''}
                    </div>`;

            } catch(e) {
                body.innerHTML = `<p style="color:#e74c3c;padding:20px;">Error de conexión al cargar el pedido.</p>`;
            }
        }

        // ── CANCELAR PEDIDO ────────────────────────────────────────────
        async function confirmarCancelarPedido(pedidoId) {
            const confirmed = await showConfirm({
                title: '¿Cancelar este pedido?',
                message: 'El pedido quedará inhabilitado. Podrás reactivarlo después si es necesario.',
                type: 'danger', confirmText: 'Sí, cancelar', cancelText: 'Volver'
            });
            if (!confirmed) return;
            try {
                const r = await fetch(apiUpdatePedido, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pedido_id: parseInt(pedidoId), action: 'toggle_active' })
                });
                const d = await r.json();
                if (d.success) { Toast.canceled(d.message || 'Pedido cancelado', 'Cancelado'); setTimeout(() => location.reload(), 1400); }
                else Toast.error(d.message || 'No se pudo cancelar', 'Error');
            } catch(e) { Toast.error('Error de conexión', 'Error de red'); }
        }

        // ── REACTIVAR PEDIDO ───────────────────────────────────────────
        async function confirmarReactivarPedido(pedidoId) {
            const confirmed = await showConfirm({
                title: '¿Reactivar este pedido?',
                message: 'El pedido volverá a estado Pendiente.',
                type: 'info', confirmText: 'Sí, reactivar', cancelText: 'Cancelar'
            });
            if (!confirmed) return;
            try {
                const r = await fetch(apiUpdatePedido, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pedido_id: parseInt(pedidoId), action: 'toggle_active' })
                });
                const d = await r.json();
                if (d.success) { Toast.success(d.message || 'Pedido reactivado', '¡Reactivado!'); setTimeout(() => location.reload(), 1400); }
                else Toast.error(d.message || 'No se pudo reactivar', 'Error');
            } catch(e) { Toast.error('Error de conexión', 'Error de red'); }
        }
    </script>
</body>
</html>
<?php ob_end_flush(); ?>