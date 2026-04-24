<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';

$reservas_stats = [
    'activas' => 0,
    'completadas' => 0,
    'canceladas' => 0,
    'prorrogadas' => 0
];

$clientes = [];
$productos = [];

try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        // Reservas activas
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT codigo_reserva) as total FROM reservas WHERE estado_reserva = 'PENDIENTE' AND fecha_limite >= CURRENT_DATE");
        $stmt->execute();
        $reservas_stats['activas'] = (int) ($stmt->fetchColumn() ?: 0);

        // Reservas completadas
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT codigo_reserva) as total FROM reservas WHERE estado_reserva = 'COMPLETADA'");
        $stmt->execute();
        $reservas_stats['completadas'] = (int) ($stmt->fetchColumn() ?: 0);

        // Reservas canceladas
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT codigo_reserva) as total FROM reservas WHERE estado_reserva = 'CANCELADA'");
        $stmt->execute();
        $reservas_stats['canceladas'] = (int) ($stmt->fetchColumn() ?: 0);

        // Reservas prorrogadas
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT codigo_reserva) as total FROM reservas WHERE estado_reserva = 'PRORROGADA'");
        $stmt->execute();
        $reservas_stats['prorrogadas'] = (int) ($stmt->fetchColumn() ?: 0);

        // Listado de clientes para select
        $stmt = $conn->prepare("SELECT id, nombre_completo, cedula_rif FROM clientes WHERE estado = true ORDER BY nombre_completo LIMIT 200");
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Listado de productos para select
        $stmt = $conn->prepare("SELECT id, nombre, precio_venta FROM productos WHERE estado = true ORDER BY nombre LIMIT 500");
        $stmt->execute();
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Listado completo de reservas
        $reservas = [];
        $stmt = $conn->prepare("
            SELECT 
                r.codigo_reserva,
                MIN(r.fecha_reserva)  AS fecha_reserva,
                MIN(r.fecha_limite)   AS fecha_limite,
                MIN(CASE 
                    WHEN r.estado_reserva = 'ACTIVA' THEN 'PENDIENTE' 
                    ELSE r.estado_reserva 
                END) AS estado_reserva,
                c.nombre_completo     AS cliente_nombre,
                COUNT(*)              AS num_productos,
                COALESCE(
                    SUM(p.precio_venta * r.cantidad), 0
                ) AS total,
                STRING_AGG(p.nombre || ' x' || r.cantidad::text, ', ') AS productos
            FROM reservas r
            LEFT JOIN clientes  c ON c.id = r.cliente_id
            LEFT JOIN productos p ON p.id = r.producto_id
            GROUP BY r.codigo_reserva, c.nombre_completo
            ORDER BY MIN(r.created_at) DESC
            LIMIT 500
        ");
        $stmt->execute();
        $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log('ERROR reserva.php: ' . $e->getMessage());
    $reservas = [];
}
$base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva y Apartado - Inversiones Rojas</title>
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
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/layouts/reserva.css">
    <!-- Sistema de notificaciones y validaciones personalizadas -->
    <script src="<?php echo $base_url; ?>/public/js/inv-notifications.js"></script>
</head>
<style>
    /* Estilos para moneda dual */
    .moneda-bs { color: #1F9166; font-weight: 700; }
    .moneda-usd { color: #6c757d; font-size: 0.85em; }

    /* ★ Estilos unificados con scroll horizontal ★ */
    .reservas-table-wrapper {
        margin-top: 20px;
        background: white;
        border-radius: 12px;
        overflow-x: auto;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        -webkit-overflow-scrolling: touch;
    }

    .reservas-table {
        width: 100%;
        min-width: 1110px;
    }

    .table-header {
        display: grid;
        grid-template-columns: 1.2fr 1.5fr 0.5fr 2fr 0.9fr 0.9fr 1fr 0.8fr 1fr 1.3fr;
        gap: 8px;
        align-items: center;
        padding: 16px 20px;
        background: #f8f9fa;
        font-weight: 700;
        color: #2c3e50;
        font-size: 14px;
        letter-spacing: 0.3px;
    }

    .table-row {
        display: grid;
        grid-template-columns: 1.2fr 1.5fr 0.5fr 2fr 0.9fr 0.9fr 1fr 0.8fr 1fr 1.3fr;
        gap: 8px;
        align-items: center;
        padding: 14px 20px;
        border-bottom: 1px solid #edf2f7;
        transition: background-color 0.2s;
    }

    .table-row:hover {
        background-color: #f8fafc;
    }

    /* ★ Estilos para los badges de estado (igual que en compras) ★ */
    .status-badge {
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        display: inline-block;
        min-width: 90px;
    }

    .status-pendiente { background: #fff7e6; color: #c17f00; border: 1px solid #ffeaa7; }  /* PENDIENTE */
    .status-completed { background: #e8f6f1; color: #1F9166; border: 1px solid #c3e6cb; }  /* COMPLETADA */
    .status-cancelled { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }  /* CANCELADA */
    .status-prorroged { background: #cce5ff; color: #004085; border: 1px solid #b8daff; }  /* PRORROGADA */

    /* ★ Estilos de botones de acción (copiados de compras/inventario) ★ */
    .actions-cell {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        transition: transform 0.1s ease, box-shadow 0.1s ease;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }

    .btn-icon.btn-sm {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }

    .btn-icon i {
        font-size: 16px;
    }

    .btn-icon:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* Colores específicos (igual que en compras) */
    .btn-view { background: #1F9166; color: white; }
    .btn-edit { background: #ffc107; color: #212529; }
    .btn-success { background: #28a745; color: white; }
    .btn-warning { background: #ffc107; color: #212529; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-info { background: #17a2b8; color: white; }

    /* Para compatibilidad con clases existentes */
    .btn-ver-detalles { background: #1F9166; color: white; }
    .btn-editar { background: #ffc107; color: #212529; }
    .btn-completar { background: #28a745; color: white; }
    .btn-prorrogar { background: #ffc107; color: #212529; }
    .btn-cancelar { background: #dc3545; color: white; }

    /* Responsive mejorado - 4 breakpoints */
    @media (max-width: 1200px) {
        .table-header, .table-row {
            grid-template-columns: 1.1fr 1.4fr 0.4fr 1.8fr 0.8fr 0.8fr 0.9fr 0.7fr 0.9fr 1.2fr;
            gap: 6px;
            padding: 12px 16px;
            font-size: 13px;
        }
        .btn-icon { width: 30px; height: 30px; }
    }

    /* Tablet - ocultar columnas menos importantes */
    @media (max-width: 1024px) {
        .table-header, .table-row {
            grid-template-columns: 1.2fr 1.5fr 2fr 0.9fr 0.9fr 1fr 1.3fr;
        }
        /* Ocultar: Items(3), Tiempo restante(7) */
        .table-header > div:nth-child(3),
        .table-row > div:nth-child(3),
        .table-header > div:nth-child(7),
        .table-row > div:nth-child(7) {
            display: none;
        }
    }

    /* Móvil grande - layout de tarjetas */
    @media (max-width: 900px) {
        .table-header { display: none; }
        .table-row {
            display: block;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .table-row > div {
            display: flex;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px dashed #e9ecef;
            font-size: 0.85rem;
        }
        .table-row > div:last-child { border-bottom: none; }
        .table-row > div:before {
            content: attr(data-label);
            font-weight: 600;
            width: 100px;
            min-width: 100px;
            color: #64748b;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .actions-cell {
            justify-content: flex-start;
            gap: 12px;
            padding-top: 12px;
            border-bottom: none;
        }
        .btn-icon { width: 42px; height: 42px; }
    }

    /* Móvil pequeño */
    @media (max-width: 600px) {
        .table-row { padding: 12px; }
        .table-row > div { font-size: 0.8rem; }
        .table-row > div:before { width: 80px; min-width: 80px; font-size: 0.7rem; }
        .btn-icon { width: 38px; height: 38px; }
    }

    /* Stats y charts responsive */
    .reservas-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:30px; }
    .stat-card { background:white; border-radius:10px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,0.1); display:flex; align-items:center; gap:20px; border-left:4px solid #1F9166; transition:transform 0.3s; }
    .stat-card:hover { transform:translateY(-5px); }
    .stat-icon { width:60px; height:60px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:24px; color:white; background:linear-gradient(135deg,#1F9166,#30B583); }
    .stat-info h3 { font-size:24px; font-weight:bold; color:#333; margin:0; }
    .stat-info p { color:#666; margin:5px 0 0; font-size:14px; }
    
    @media (max-width: 1024px) {
        .charts-grid { grid-template-columns: 1fr; }
    }
    
    .charts-grid { display:grid; grid-template-columns:1fr 1fr; gap:25px; margin-bottom:30px; }
    .chart-container { background:white; border-radius:10px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
    .chart-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
    .chart-header h3 { color:#333; font-size:16px; margin:0; }
    .chart-wrapper { height:200px; position:relative; }
    /* Modales (se mantienen) */
    .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; }
    .modal-overlay.active { display:flex; }
    .modal { background:white; border-radius:10px; max-width:400px; width:90%; padding:25px; position:relative; animation:slideDown 0.3s; max-height: 90vh; overflow-y: auto; }

.modal--small { max-width: 430px; width: 90%; }
    @keyframes slideDown { from{opacity:0;transform:translateY(-30px);} to{opacity:1;transform:translateY(0);} }
    .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
    .modal-header h3 { margin:0; color:#333; font-size:18px; }
    .modal-close { background:none; border:none; font-size:24px; cursor:pointer; color:#999; }
    .modal-body { margin-bottom:20px; }
    .modal-footer { display:flex; justify-content:flex-end; gap:10px; }
    .form-group { margin-bottom:15px; }
    .form-group label { display:block; margin-bottom:5px; color:#333; font-weight:500; font-size:14px; }
    .form-group select, .form-group input { width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; font-size:14px; }
    .btn { padding:10px 20px; border:none; border-radius:5px; cursor:pointer; font-size:14px; font-weight:500; transition:all 0.3s; }
    .btn-primary { background:#1F9166; color:white; }
    .btn-primary:hover { background:#187a54; }
    .btn-secondary { background:#f5f5f5; color:#666; }
    .btn-secondary:hover { background:#e0e0e0; }
    .btn-danger { background:#e74c3c; color:white; }
    .btn-danger:hover { background:#c0392b; }

    /* ── Modal Ver Detalles — layout estructurado ── */
    .rd-modal { max-width: 580px !important; }

    /* Cabecera del detalle */
    .rd-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 8px;
    }
    .rd-code { font-size: 1rem; font-weight: 700; color: #2c3e50; }
    .rd-code span { font-size: 0.85rem; font-weight: 400; color: #6c757d; }

    /* Grid de info principal */
    .rd-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px 20px;
        background: #f8fafb;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 14px;
    }
    .rd-field { display: flex; flex-direction: column; gap: 2px; }
    .rd-label {
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 600;
        color: #8a9ab0;
    }
    .rd-value { font-size: 13.5px; color: #2c3e50; font-weight: 500; }
    .rd-value.full { grid-column: 1 / -1; }

    /* Secciones de pago */
    .rd-section {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 12px;
    }
    .rd-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: #f0f7f4;
        border-bottom: 1px solid #e9ecef;
    }
    .rd-section-title {
        font-size: 13px;
        font-weight: 700;
        color: #1F9166;
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .rd-section-title i { font-size: 13px; }
    .rd-pct {
        font-size: 11px;
        font-weight: 600;
        background: #1F9166;
        color: #fff;
        padding: 2px 8px;
        border-radius: 30px;
    }
    .rd-section-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; }

    .rd-row {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 13px;
        color: #444;
    }
    .rd-row b { color: #2c3e50; min-width: 130px; flex-shrink: 0; }

    /* Comprobante thumb */
    .rd-comprobante-thumb {
        width: 90px; height: 90px;
        border-radius: 8px;
        border: 2px solid #1F9166;
        object-fit: cover;
        cursor: pointer;
        transition: transform 0.15s;
        display: block;
        margin-top: 4px;
    }
    .rd-comprobante-thumb:hover { transform: scale(1.04); }

    /* Obs box */
    .rd-obs {
        background: #fffbf0;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 10px 13px;
        font-size: 13px;
        color: #555;
        margin-top: 2px;
    }
    .rd-obs b { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .03em; color: #c17f00; margin-bottom: 3px; }

    /* Estado COMPLETADA del section restante */
    .rd-section.rd-completed .rd-section-header { background: #eaf7f2; }
    .rd-section.rd-completed .rd-section-title { color: #0d6e47; }
    .rd-section.rd-completed .rd-pct { background: #0d6e47; }

    @media (max-width: 560px) {
        .rd-grid { grid-template-columns: 1fr; }
        .rd-row b { min-width: 110px; }
    }
</style>

<body>
    <div class="admin-content">
        <!-- Stats Cards - SOLO 4 CUADROS -->
        <div class="reservas-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $reservas_stats['activas']; ?></h3>
                    <p>Reservas Pendientes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $reservas_stats['completadas']; ?></h3>
                    <p>Completadas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $reservas_stats['canceladas']; ?></h3>
                    <p>Canceladas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $reservas_stats['prorrogadas']; ?></h3>
                    <p>Prorrogadas</p>
                </div>
            </div>
        </div>

        <!-- Charts Section - 2 GRÁFICAS COHERENTES -->
        <div class="charts-grid">
            <!-- Gráfica 1: Distribución por Estado -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Reservas por Estado</h3>
                </div>
                <div class="chart-wrapper">
                    <canvas id="estadosChart"></canvas>
                </div>
            </div>

            <!-- Gráfica 2: Tendencia Mensual -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Tendencia de Reservas</h3>
                </div>
                <div class="chart-wrapper">
                    <canvas id="tendenciaChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Formulario de Nueva Reserva (oculto) -->
        <div class="form-container" id="formReserva" style="display: none; background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;">Nueva Reserva</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <div class="form-group">
                    <label>Cliente *</label>
                    <select class="form-control" id="cliente">
                        <option value="">Seleccione cliente</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" data-rif="<?php echo htmlspecialchars($c['cedula_rif']); ?>"><?php echo htmlspecialchars($c['nombre_completo']); ?> (<?php echo htmlspecialchars($c['cedula_rif']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Producto *</label>
                    <select class="form-control" id="producto">
                        <option value="">Seleccione producto</option>
                        <?php foreach ($productos as $p): ?>
                            <?php $precios = formatearMonedaDual($p['precio_venta']); ?>
                            <option value="<?php echo $p['id']; ?>" data-precio="<?php echo $p['precio_venta']; ?>"><?php echo htmlspecialchars($p['nombre']); ?> - <span class="moneda-bs"><?php echo $precios['bs']; ?></span> <span class="moneda-usd">(<?php echo $precios['usd']; ?>)</span></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cantidad *</label>
                    <input type="number" class="form-control" id="cantidad" min="1" value="1">
                </div>
                <div class="form-group">
                    <label>Fecha Límite *</label>
                    <input type="date" class="form-control" id="fecha_limite">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>Observaciones</label>
                    <textarea class="form-control" id="observaciones" rows="3" placeholder="Observaciones opcionales..."></textarea>
                </div>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" id="cancelarReservaBtn">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardarReservaBtn">Guardar Reserva</button>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="search-filters" style="margin: 20px 0;">
            <form id="reservaSearchForm" class="search-box" onsubmit="event.preventDefault(); aplicarFiltros();" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                <input id="searchInput" type="search" name="q" placeholder="Buscar reservas por código, cliente o producto..." style="flex:1; min-width:200px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" />

                <select id="dateFilter" name="date_range" class="filter-select" style="min-width:160px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="">Todos los días</option>
                    <option value="today">Hoy</option>
                    <option value="yesterday">Ayer</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mes</option>
                    <option value="last_month">Mes anterior</option>
                    <option value="custom">Rango personalizado</option>
                </select>

                <select id="estadoFilter" name="estado" class="filter-select" style="min-width:140px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="">Todos</option>
                    <option value="PENDIENTE" selected>Pendiente</option>
                    <option value="COMPLETADA">Completada</option>
                    <option value="PRORROGADA">Prorrogada</option>
                    <option value="VENCIDA">Vencida</option>
                    <option value="CANCELADA">Cancelada</option>
                </select>

                <button id="filtrarBtn" type="button" class="btn btn-secondary" style="padding: 10px 20px; background: #1F9166; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.3s; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-search"></i> Buscar
                </button>

                <button id="limpiarBtn" type="button" class="btn btn-outline" style="padding: 10px 20px; background: #f8f9fa; color: #666; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    Limpiar
                </button>
            </form>

            <!-- Rango de fechas personalizado (oculto por defecto) -->
            <div id="customDateRange" style="display: none; margin-top: 10px; padding: 15px; background: #f8f9fa; border-radius: 6px; border: 1px solid #ddd; flex: 1 1 100%;">
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1 1 240px; min-width: 240px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px;">Desde:</label>
                        <input type="date" id="dateFrom" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    <div style="flex: 1 1 240px; min-width: 240px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px;">Hasta:</label>
                        <input type="date" id="dateTo" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    <button type="button" onclick="aplicarFiltros()" style="margin-top: 22px; padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                        Aplicar
                    </button>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="reservas-actions" style="margin-bottom: 20px; position: relative; z-index: 1;">
            <div class="action-buttons" style="display: flex; gap: 10px; position: relative; z-index: 2;">
                <button class="btn btn-primary" id="mostrarFormBtn" style="cursor: pointer; pointer-events: auto; position: relative; z-index: 3;">
                    <i class="fas fa-plus"></i> Nueva Reserva
                </button>
                <button class="btn btn-secondary">
                    <i class="fas fa-clock"></i> Próximas a Vencer
                </button>
            </div>
        </div>

        <!-- Reservas Table - responsive con scroll horizontal -->
        <div class="reservas-table-wrapper">
        <div class="reservas-table">
            <div class="table-header">
                <div>Código</div>
                <div>Cliente</div>
                <div>Items</div>
                <div>Productos</div>
                <div>Fecha Reserva</div>
                <div>Fecha Vencimiento</div>
                <div>Tiempo restante</div>
                <div>Total</div>
                <div>Estado</div>
                <div>Acciones</div>
            </div>

                <?php if (!empty($reservas)): ?>
                <?php foreach ($reservas as $r): 
                    $fecha_reserva = !empty($r['fecha_reserva']) ? date('d/m/Y', strtotime($r['fecha_reserva'])) : '';
                    $fecha_lim = !empty($r['fecha_limite']) ? date('d/m/Y', strtotime($r['fecha_limite'])) : '';
                    $precios = formatearMonedaDual((float)$r['total']);
                    $estado = $r['estado_reserva'] ?? 'PENDIENTE';
                    
                    $badgeClass = 'status-pendiente';
                    $label = 'Pendiente';
                    switch (strtoupper($estado)) {
                        case 'COMPLETADA': $badgeClass = 'status-completed'; $label = 'Completada'; break;
                        case 'CANCELADA': $badgeClass = 'status-cancelled'; $label = 'Cancelada'; break;
                        case 'PRORROGADA': $badgeClass = 'status-prorroged'; $label = 'Prorrogada'; break;
                    }
                ?>
                <div class="table-row" data-codigo="<?php echo htmlspecialchars($r['codigo_reserva']); ?>">
                    <div data-label="Código"><strong><?php echo htmlspecialchars($r['codigo_reserva']); ?></strong></div>
                    <div data-label="Cliente"><?php echo htmlspecialchars($r['cliente_nombre'] ?? '—'); ?></div>
                    <div data-label="Items"><?php echo (int)($r['num_productos'] ?? 0); ?></div>
                    <div data-label="Productos"><?php echo htmlspecialchars($r['productos'] ?? '—'); ?></div>
                    <div data-label="Fecha Reserva"><?php echo $fecha_reserva; ?></div>
                    <div data-label="Fecha Vencimiento"><?php echo $fecha_lim; ?></div>
                    <div data-label="Tiempo restante"><span class="time-remaining" data-fecha="<?php echo !empty($r['fecha_limite']) ? date('c', strtotime($r['fecha_limite'])) : ''; ?>">--:--:--</span></div>
                    <div data-label="Total"><span class="moneda-bs"><?php echo $precios['bs']; ?></span> <span class="moneda-usd">(<?php echo $precios['usd']; ?>)</span></div>
                    <div data-label="Estado"><span class="status-badge <?php echo $badgeClass; ?>"><?php echo $label; ?></span></div>
                    <div data-label="Acciones" class="actions-cell" style="display: flex; gap: 5px; flex-wrap: wrap; justify-content: center;">
                        <button class="btn-icon btn-sm btn-view btn-ver-detalles" data-codigo="<?php echo htmlspecialchars($r['codigo_reserva']); ?>" title="Ver Detalles" style="display: inline-flex;"><i class="fas fa-eye"></i></button>
                        <?php if ($estado === 'PENDIENTE' || $estado === 'PRORROGADA'): ?>
                            <button class="btn-icon btn-sm btn-success btn-completar" data-codigo="<?php echo htmlspecialchars($r['codigo_reserva']); ?>" title="Completar" style="display: inline-flex;"><i class="fas fa-check"></i></button>
                            <button class="btn-icon btn-sm btn-danger btn-cancelar" data-codigo="<?php echo htmlspecialchars($r['codigo_reserva']); ?>" title="Cancelar" style="display: inline-flex;"><i class="fas fa-times"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="table-row">
                    <div data-label="Código">—</div>
                    <div data-label="Cliente">—</div>
                    <div data-label="Items">0</div>
                    <div data-label="Productos">No hay reservas registradas.</div>
                    <div data-label="Fecha Reserva">—</div>
                    <div data-label="Fecha Vencimiento">—</div>
                    <div data-label="Tiempo restante">—</div>
                    <div data-label="Total">$0.00</div>
                    <div data-label="Estado"><span class="status-badge status-reserved">—</span></div>
                    <div data-label="Acciones" class="actions-cell"></div>
                </div>
            <?php endif; ?>
        </div>
        </div> <!-- cierre de reservas-table-wrapper -->
    </div>

    <!-- Modal de método de pago (corregido) -->
    <div class="modal-overlay" id="modalMetodoPago">
        <div class="modal">
            <div class="modal-header">
                <h3>Seleccionar método de pago</h3>
                <button class="modal-close" id="closeModalMetodoPago">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px;">Reserva: <strong id="modalCodigoReserva">-</strong></p>
                <div class="form-group">
                    <label>Método de pago</label>
                    <select id="selectMetodoPago" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></select>
                </div>
                <div class="form-group" id="referenceField" style="display: none; margin-top: 15px;">
                    <label>Referencia bancaria</label>
                    <input type="text" id="paymentReference" placeholder="Ej. #123456, transferencia bancaria" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div class="form-group" id="paymentProofField" style="margin-top: 15px;">
                    <label>Imágenes del comprobante</label>
                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('paymentComprobantes').click();">Seleccionar imágenes</button>
                        <span id="paymentComprobantesLabel" style="color:#555; font-size:13px;">No se han seleccionado archivos</span>
                    </div>
                    <input type="file" id="paymentComprobantes" name="comprobante[]" accept="image/*" multiple style="display:none;">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelModalMetodoPago">Cancelar</button>
                <button class="btn btn-primary" id="confirmarMetodoPago">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Modal para seleccionar días de prórroga -->
    <div class="modal-overlay" id="modalProrrogar">
        <div class="modal modal--small">
            <div class="modal-header">
                <h3>Prorrogar reserva</h3>
                <button class="modal-close" onclick="closeProrrogarModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Cuántos días deseas extender la reserva <strong id="prorrogarCodigo">-</strong>?</p>
                <div class="form-group" style="margin-top: 15px;">
                    <label>Días de extensión</label>
                    <select id="diasProrroga" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="1">1 día</option>
                        <option value="2">2 días</option>
                        <option value="3" selected>3 días</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeProrrogarModal()">Cancelar</button>
                <button class="btn btn-primary" id="btnConfirmarProrroga" onclick="confirmarProrroga()">Prorrogar</button>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalles -->
    <div class="modal-overlay" id="modalVerDetalles">
        <div class="modal rd-modal">
            <div class="modal-header">
                <h3>Detalles de Reserva</h3>
                <button class="modal-close" id="closeModalDetalles">&times;</button>
            </div>
            <div class="modal-body" id="detallesContent">
                <!-- Contenido se carga dinámicamente -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cerrarModalDetalles">Cerrar</button>
                <button class="btn btn-warning" id="btnModalProrrogar">Prorrogar</button>
                <button class="btn btn-secondary" id="btnModalEditar">Editar</button>
            </div>
        </div>
    </div>

    <!-- Modal Editar Reserva -->
    <div class="modal-overlay" id="modalEditarReserva">
        <div class="modal modal--small">
            <div class="modal-header">
                <h3>Editar Reserva</h3>
                <button class="modal-close" id="closeModalEditar">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formEditarReserva" novalidate>
                    <input type="hidden" id="editReservaId">
                    <input type="hidden" id="editCodigoReserva">
                    <div class="form-group">
                        <label>Cliente *</label>
                        <select class="form-control" id="editCliente">
                            <option value="">Seleccione cliente</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?php echo $c['id']; ?>" data-rif="<?php echo htmlspecialchars($c['cedula_rif']); ?>"><?php echo htmlspecialchars($c['nombre_completo']); ?> (<?php echo htmlspecialchars($c['cedula_rif']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Producto *</label>
                        <select class="form-control" id="editProducto">
                            <option value="">Seleccione producto</option>
                            <?php foreach ($productos as $p): ?>
                            <?php $precios = formatearMonedaDual($p['precio_venta']); ?>
                            <option value="<?php echo $p['id']; ?>" data-precio="<?php echo $p['precio_venta']; ?>"><?php echo htmlspecialchars($p['nombre']); ?> - <span class="moneda-bs"><?php echo $precios['bs']; ?></span> <span class="moneda-usd">(<?php echo $precios['usd']; ?>)</span></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cantidad *</label>
                        <input type="number" class="form-control" id="editCantidad" min="1">
                    </div>
                    <div class="form-group">
                        <label>Fecha Límite *</label>
                        <input type="date" class="form-control" id="editFechaLimite">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="tel" class="form-control" id="editTelefono" placeholder="Teléfono de contacto">
                    </div>
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea class="form-control" id="editObservaciones" rows="3" placeholder="Observaciones adicionales"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelarEditarBtn">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardarEditarBtn">Guardar Cambios</button>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación Personalizado -->
    <div class="modal-overlay" id="modalConfirmDialog">
        <div class="modal modal--small">
            <div class="modal-header">
                <h3 id="confirmTitle">Confirmar acción</h3>
                <button class="modal-close" id="closeConfirmDialog">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage" style="font-size: 14px; color: #555; margin: 0;"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="confirmCancel">Cancelar</button>
                <button class="btn btn-danger" id="confirmAccept">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Motivo de Cancelación -->
    <div class="modal-overlay" id="modalCancelar">
        <div class="modal modal--small">
            <div class="modal-header">
                <h3>Cancelar Reserva</h3>
                <button class="modal-close" id="closeModalCancelar">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size: 14px; color: #555; margin: 0 0 15px;">
                    Indica el motivo de cancelación para la reserva 
                    <strong id="cancelCodigoReserva"></strong>
                </p>
                <textarea id="cancelMotivo" class="form-control" rows="3" 
                    placeholder="Ej: Producto no disponible, cancelado por el cliente, etc."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelMotivoCancel">Cerrar</button>
                <button class="btn btn-danger" id="cancelMotivoAceptar">Cancelar Reserva</button>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // FUNCIONES DE UTILIDAD
        // ============================================
        
        /**
         * Modal de confirmación personalizado
         * @param {string} title - Título del diálogo
         * @param {string} message - Mensaje de confirmación
         * @param {function} onConfirm - Callback si el usuario confirma
         */
        function showConfirmDialog(title, message, onConfirm) {
            const modal = document.getElementById('modalConfirmDialog');
            const titleEl = document.getElementById('confirmTitle');
            const msgEl = document.getElementById('confirmMessage');
            const acceptBtn = document.getElementById('confirmAccept');
            const cancelBtn = document.getElementById('confirmCancel');
            const closeBtn = document.getElementById('closeConfirmDialog');
            
            titleEl.textContent = title;
            msgEl.textContent = message;
            
            const handleAccept = async () => {
                modal.classList.remove('active');
                acceptBtn.removeEventListener('click', handleAccept);
                cancelBtn.removeEventListener('click', handleCancel);
                closeBtn.removeEventListener('click', handleCancel);
                if (typeof onConfirm === 'function') {
                    await onConfirm();
                }
            };
            
            const handleCancel = () => {
                modal.classList.remove('active');
                acceptBtn.removeEventListener('click', handleAccept);
                cancelBtn.removeEventListener('click', handleCancel);
                closeBtn.removeEventListener('click', handleCancel);
            };
            
            acceptBtn.addEventListener('click', handleAccept);
            cancelBtn.addEventListener('click', handleCancel);
            closeBtn.addEventListener('click', handleCancel);
            
            modal.classList.add('active');
        }

        // ============================================
        // GRÁFICAS CON DATOS REALES
        // ============================================
        
        // Función para cargar datos de gráficas
        async function loadChartData() {
            try {
                const response = await fetch('/inversiones-rojas/api/get_reservas_chart.php');
                const data = await response.json();
                
                if (data.success) {
                    // Actualizar gráfica de estados
                    estadosChart.data.datasets[0].data = [
                        data.activas || 0,
                        data.completadas || 0,
                        data.canceladas || 0,
                        data.prorrogadas || 0
                    ];
                    estadosChart.update();
                    
                    // Actualizar gráfica de tendencia
                    if (data.tendencia && data.tendencia.length > 0) {
                        tendenciaChart.data.labels = data.tendencia.map(item => item.mes);
                        tendenciaChart.data.datasets[0].data = data.tendencia.map(item => item.total);
                        tendenciaChart.update();
                    }
                }
            } catch (error) {
                console.error('Error cargando datos de gráficas:', error);
            }
        }

        // Gráfica 1: Distribución por Estado
        const estadosCtx = document.getElementById('estadosChart').getContext('2d');
        const estadosChart = new Chart(estadosCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pendientes', 'Completadas', 'Canceladas', 'Prorrogadas'],
                datasets: [{
                    data: [
                        <?php echo $reservas_stats['activas']; ?>,
                        <?php echo $reservas_stats['completadas']; ?>,
                        <?php echo $reservas_stats['canceladas']; ?>,
                        <?php echo $reservas_stats['prorrogadas']; ?>
                    ],
                    backgroundColor: ['#1F9166', '#3498db', '#e74c3c', '#f39c12'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Gráfica 2: Tendencia Mensual
        const tendenciaCtx = document.getElementById('tendenciaChart').getContext('2d');
        const tendenciaChart = new Chart(tendenciaCtx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Reservas',
                    data: [12, 19, 15, 22, 28, 24],
                    borderColor: '#1F9166',
                    backgroundColor: 'rgba(31,145,102,0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // ============================================
        // VARIABLES GLOBALES
        // ============================================
        let completingCodigo = null;
        let editingCodigo = null;

        // ============================================
        // FUNCIONES DE VALIDACIÓN
        // ============================================
        
        async function validarNuevaReserva() {
            const cliente = document.getElementById('cliente');
            const producto = document.getElementById('producto');
            const cantidad = document.getElementById('cantidad');
            const fechaLimite = document.getElementById('fecha_limite');
            const telefono = document.getElementById('telefono');
            
            // Validar campos requeridos
            if (!InvValidate.required(cliente, 'Cliente')) return false;
            if (!InvValidate.required(producto, 'Producto')) return false;
            if (!InvValidate.positiveNumber(cantidad, 'Cantidad', false)) return false;
            if (!InvValidate.required(fechaLimite, 'Fecha límite')) return false;
            
            // Validar fecha futura
            if (!InvValidate.notPastDate(fechaLimite, 'Fecha límite')) return false;
            
            // Validar teléfono si está presente
            if (telefono && telefono.value.trim() && !InvValidate.phone(telefono, 'Teléfono')) return false;
            
            // Validar stock disponible
            const productoId = producto.value;
            const cantidadValue = parseInt(cantidad.value);
            if (!(await validarStockDisponible(productoId, cantidadValue))) return false;
            
            return true;
        }

        async function validarEditarReserva() {
            const cliente = document.getElementById('editCliente');
            const producto = document.getElementById('editProducto');
            const cantidad = document.getElementById('editCantidad');
            const fechaLimite = document.getElementById('editFechaLimite');
            const telefono = document.getElementById('editTelefono');
            
            // Validar campos requeridos
            if (!InvValidate.required(cliente, 'Cliente')) return false;
            if (!InvValidate.required(producto, 'Producto')) return false;
            if (!InvValidate.positiveNumber(cantidad, 'Cantidad', false)) return false;
            if (!InvValidate.required(fechaLimite, 'Fecha límite')) return false;
            
            // Validar fecha futura
            if (!InvValidate.notPastDate(fechaLimite, 'Fecha límite')) return false;
            
            // Validar teléfono si está presente
            if (telefono && telefono.value.trim() && !InvValidate.phone(telefono, 'Teléfono')) return false;
            
            // Validar stock disponible
            const productoId = producto.value;
            const cantidadValue = parseInt(cantidad.value);
            if (!(await validarStockDisponible(productoId, cantidadValue))) return false;
            
            return true;
        }

        // Función para validar stock disponible
        async function validarStockDisponible(productoId, cantidad) {
            try {
                const response = await fetch(`/inversiones-rojas/api/check_stock.php?producto_id=${productoId}&cantidad=${cantidad}`);
                const data = await response.json();
                
                if (!data.success) {
                    showNotification(data.message || 'Error verificando stock', 'error', 'Error');
                    return false;
                }
                
                if (!data.disponible) {
                    showNotification(`Stock insuficiente. Disponible: ${data.stock_disponible}`, 'error', 'Stock insuficiente');
                    return false;
                }
                
                return true;
            } catch (error) {
                console.error('Error verificando stock:', error);
                showNotification('Error verificando stock disponible', 'error', 'Error');
                return false;
            }
        }

        // ============================================
        // FUNCIONES DE MODALES
        // ============================================
        
        async function loadPaymentMethods() {
            try {
                // Usar la tabla normal de métodos de pago (para ventas)
                const resp = await fetch('/inversiones-rojas/api/get_metodos_pago.php');
                const json = await resp.json();
                const sel = document.getElementById('selectMetodoPago');
                sel.innerHTML = '';
                
                if (json && json.success && json.metodos) {
                    json.metodos.forEach(m => {
                        const o = document.createElement('option');
                        o.value = m.id;
                        o.text = m.nombre;
                        sel.appendChild(o);
                    });
                } else {
                    sel.innerHTML = '<option value="">Error cargando métodos</option>';
                }
                
                // Agregar event listener para toggle de referencia
                sel.addEventListener('change', toggleReferenceField);
                toggleReferenceField();
            } catch (e) {
                console.error('Error cargando métodos de pago:', e);
                showNotification('Error cargando métodos de pago: ' + e.message, 'error', 'Error');
            }
        }

        function toggleReferenceField() {
            const select = document.getElementById('selectMetodoPago');
            const field = document.getElementById('referenceField');
            const input = document.getElementById('paymentReference');
            if (!select || !field) return;

            const selectedOption = select.options[select.selectedIndex];
            const methodName = selectedOption ? selectedOption.textContent.toLowerCase() : '';

            // Mostrar el campo solo cuando el método requiere referencia; la validación se realiza en JS
            const requiresReference = methodName.includes('transferencia') || 
                                    methodName.includes('pago móvil') || 
                                    methodName.includes('bancario') || 
                                    methodName.includes('depósito') ||
                                    methodName.includes('movil') ||
                                    methodName.includes('digital');

            if (requiresReference) {
                field.style.display = 'block';
            } else {
                field.style.display = 'none';
                input.value = ''; // Limpiar si no se necesita
            }
        }

        function parseObservaciones(obs) {
            const result = { telefono: '', observaciones: '' };
            if (!obs) return result;

            const parts = obs
                .split('|')
                .map(p => p.trim())
                .filter(Boolean);

            const telefonoPart = parts.find(p => /^tel:/i.test(p));
            if (telefonoPart) {
                result.telefono = telefonoPart.replace(/^tel:\s*/i, '').trim();
            }

            result.observaciones = parts
                .filter(p => !/^tel:/i.test(p))
                .join(' | ')
                .trim();

            return result;
        }

        // ── Helpers de formato ──────────────────────────────────────────
        function fmtBs(usd) {
            const bs = (parseFloat(usd) * TASA_CAMBIO).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return `<span class="moneda-bs">Bs ${bs}</span>`;
        }
        function fmtUsd(usd) {
            return `<span class="moneda-usd">($${parseFloat(usd).toFixed(2)})</span>`;
        }
        function fmtDual(usd) { return fmtBs(usd) + ' ' + fmtUsd(usd); }
        function fmtDate(d) {
            if (!d) return '—';
            const dt = new Date(d.includes('T') ? d : d + 'T12:00:00');
            return dt.toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
        function comprobantePart(url, label) {
            if (!url) return '';
            const full = (window.APP_BASE || '') + url;
            return `<div class="rd-row" style="flex-direction:column;gap:4px;">
                        <b>${label}</b>
                        <img class="rd-comprobante-thumb" src="${full}" alt="Comprobante"
                             onclick="verImagenComprobante('${full}')" />
                    </div>`;
        }

        async function verDetallesReserva(codigo) {
            try {
                const response = await fetch(`/inversiones-rojas/api/get_reserva_detalle.php?codigo=${codigo}`);
                const data = await response.json();

                if (!data.success) {
                    showNotification(data.message || 'Error cargando detalles', 'error', 'Error');
                    return;
                }

                const r  = data.reserva;
                const cl = data.cliente;
                const pr = data.producto;
                const parsed = parseObservaciones(r.observaciones);

                const isCompleted = r.estado_reserva === 'COMPLETADA';
                const estadoClass = {
                    'PENDIENTE':  'status-pendiente',
                    'COMPLETADA': 'status-completed',
                    'CANCELADA':  'status-cancelled',
                    'PRORROGADA': 'status-prorroged'
                }[r.estado_reserva] || 'status-pendiente';

                // ── 1. Cabecera ─────────────────────────────────────────────────
                const headerHtml = `
                    <div class="rd-header">
                        <div class="rd-code">
                            ${r.codigo_reserva}
                            <span style="margin-left:6px;">— Detalles</span>
                        </div>
                        <span class="status-badge ${estadoClass}">${r.estado_reserva}</span>
                    </div>`;

                // ── 2. Info principal ───────────────────────────────────────────
                const infoHtml = `
                    <div class="rd-grid">
                        <div class="rd-field">
                            <span class="rd-label">Cliente</span>
                            <span class="rd-value">${cl.nombre_completo} <span style="color:#6c757d;font-weight:400;">(${cl.cedula_rif || '—'})</span></span>
                        </div>
                        <div class="rd-field">
                            <span class="rd-label">Producto</span>
                            <span class="rd-value">${pr.nombre}</span>
                        </div>
                        <div class="rd-field">
                            <span class="rd-label">Cantidad</span>
                            <span class="rd-value">${r.cantidad} unidad${r.cantidad != 1 ? 'es' : ''}</span>
                        </div>
                        <div class="rd-field">
                            <span class="rd-label">Precio Unitario</span>
                            <span class="rd-value">${fmtBs(pr.precio_venta)} ${fmtUsd(pr.precio_venta)}</span>
                        </div>
                        <div class="rd-field">
                            <span class="rd-label">Subtotal</span>
                            <span class="rd-value">${fmtBs(r.subtotal)} ${fmtUsd(r.subtotal)}</span>
                        </div>
                        <div class="rd-field">
                            <span class="rd-label">IVA (16%)</span>
                            <span class="rd-value">${fmtBs(r.iva)} ${fmtUsd(r.iva)}</span>
                        </div>
                        <div class="rd-field">
                            <span class="rd-label">Total</span>
                            <span class="rd-value" style="font-weight:700;">${fmtBs(r.total)} ${fmtUsd(r.total)}</span>
                        </div>
                        <div class="rd-field">
                            <span class="rd-label">Teléfono</span>
                            <span class="rd-value">${parsed.telefono || cl.telefono_principal || '—'}</span>
                        </div>
                        <div class="rd-field">
                            <span class="rd-label">Fecha Reserva</span>
                            <span class="rd-value">${fmtDate(r.fecha_reserva)}</span>
                        </div>
                        <div class="rd-field">
                            <span class="rd-label">Fecha Límite</span>
                            <span class="rd-value">${fmtDate(r.fecha_limite)}</span>
                        </div>
                    </div>`;

                // ── 3. Pago adelantado (25%) ────────────────────────────────────
                const adelantadoHtml = `
                    <div class="rd-section">
                        <div class="rd-section-header">
                            <div class="rd-section-title">
                                <i class="fas fa-hand-holding-usd"></i>
                                Pago Adelantado
                            </div>
                            <span class="rd-pct">25%</span>
                        </div>
                        <div class="rd-section-body">
                            <div class="rd-row">
                                <b>Método de pago:</b>
                                <span>${r.metodo_pago || 'No especificado'}</span>
                            </div>
                            <div class="rd-row">
                                <b>Monto adelantado:</b>
                                <span>${fmtBs(r.monto_adelanto)} ${fmtUsd(r.monto_adelanto)}</span>
                            </div>
                            ${r.referencia_pago ? `
                            <div class="rd-row">
                                <b>Referencia:</b>
                                <span>${r.referencia_pago}</span>
                            </div>` : ''}
                            ${comprobantePart(r.comprobante_url, 'Comprobante:')}
                        </div>
                    </div>`;

                // ── 4. Pago restante (75%) ──────────────────────────────────────
                let restanteHtml;
                if (isCompleted) {
                    // Reserva completada: mostrar datos del pago final (75%)
                    restanteHtml = `
                        <div class="rd-section rd-completed">
                            <div class="rd-section-header">
                                <div class="rd-section-title">
                                    <i class="fas fa-check-circle"></i>
                                    Pago Restante — Cancelado
                                </div>
                                <span class="rd-pct">75%</span>
                            </div>
                            <div class="rd-section-body">
                                <div class="rd-row">
                                    <b>Método de pago:</b>
                                    <span>${r.metodo_pago_resto || 'No especificado'}</span>
                                </div>
                                <div class="rd-row">
                                    <b>Monto pagado:</b>
                                    <span>${fmtBs(r.monto_pagado_resto || r.monto_restante)} ${fmtUsd(r.monto_pagado_resto || r.monto_restante)}</span>
                                </div>
                                ${r.referencia_pago_resto ? `
                                <div class="rd-row">
                                    <b>Referencia:</b>
                                    <span>${r.referencia_pago_resto}</span>
                                </div>` : ''}
                                ${r.fecha_pago_resto ? `
                                <div class="rd-row">
                                 
                                </div>` : ''}
                                ${comprobantePart(r.comprobante_url_resto, 'Comprobante:')}
                            </div>
                        </div>`;
                } else {
                    // Reserva pendiente: mostrar monto pendiente y observaciones
                    const obs = parsed.observaciones || 'Sin observaciones';
                    restanteHtml = `
                        <div class="rd-section">
                            <div class="rd-section-header">
                                <div class="rd-section-title">
                                    <i class="fas fa-clock"></i>
                                    Pago Restante — Pendiente
                                </div>
                                <span class="rd-pct">75%</span>
                            </div>
                            <div class="rd-section-body">
                                <div class="rd-row">
                                    <b>Monto a cancelar:</b>
                                    <span style="font-weight:700;">${fmtBs(r.monto_restante)} ${fmtUsd(r.monto_restante)}</span>
                                </div>
                                <div class="rd-obs">
                                    <b><i class="fas fa-comment-dots" style="margin-right:4px;"></i>Observaciones</b>
                                    ${obs}
                                </div>
                            </div>
                        </div>`;
                }

                // ── Ensamblar y mostrar ─────────────────────────────────────────
                document.getElementById('detallesContent').innerHTML =
                    headerHtml + infoHtml + adelantadoHtml + restanteHtml;

                const modalDetalles = document.getElementById('modalVerDetalles');
                modalDetalles.dataset.codigo = codigo;
                modalDetalles.classList.add('active');

                // Ocultar el botón de prórroga si ya está completada
                document.getElementById('btnModalProrrogar').style.display =
                    isCompleted ? 'none' : 'inline-block';

            } catch (error) {
                console.error('Error:', error);
                showNotification('Error de conexión: ' + error.message, 'error', 'Error');
            }
        }

        async function prorrogarReserva(codigo) {
            document.getElementById('prorrogarCodigo').textContent = codigo;
            document.getElementById('modalProrrogar').classList.add('active');
        }

        async function confirmarProrroga() {
            const codigo = document.getElementById('prorrogarCodigo').textContent;
            const dias = document.getElementById('diasProrroga').value;
            const btn = document.getElementById('btnConfirmarProrroga');
            
            // Deshabilitar botón
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Prorrogando...';
            
            try {
                const res = await fetch('/inversiones-rojas/api/prorrogar_reserva.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ codigo_reserva: codigo, days: parseInt(dias) })
                });

                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    console.error('Respuesta no válida:', text);
                    throw new Error('La API retornó datos inválidos');
                }

                if (data.success) {
                    showNotification(`Reserva ${codigo} prorrogada ${dias} día(s)`, 'success', '¡Prorrogada!');
                    closeProrrogarModal();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showNotification(data.message || 'No se pudo prorrogar', 'error', 'Error');
                }
            } catch (err) {
                console.error('Error al prorrogar:', err);
                showNotification('Error de conexión: ' + err.message, 'error', 'Error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        function closeProrrogarModal() {
            document.getElementById('modalProrrogar').classList.remove('active');
        }

        async function cargarEditarReserva(codigo) {
            try {
                const response = await fetch(`/inversiones-rojas/api/get_reserva_detalle.php?codigo=${codigo}`);
                const data = await response.json();
                
                if (data.success) {
                    const reserva = data.reserva;
                    
                    const parsed = parseObservaciones(reserva.observaciones);

                    document.getElementById('editReservaId').value = reserva.id;
                    document.getElementById('editCodigoReserva').value = reserva.codigo_reserva;
                    document.getElementById('editTelefono').value = parsed.telefono;
                    document.getElementById('editCliente').value = reserva.cliente_id;
                    document.getElementById('editProducto').value = reserva.producto_id;
                    document.getElementById('editCantidad').value = reserva.cantidad;
                    document.getElementById('editFechaLimite').value = reserva.fecha_limite.split('T')[0];
                    document.getElementById('editObservaciones').value = parsed.observaciones;

                    editingCodigo = codigo;
                    document.getElementById('modalEditarReserva').classList.add('active');
                } else {
                    showNotification(data.message || 'Error cargando reserva', 'error', 'Error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error de conexión: ' + error.message, 'error', 'Error');
            }
        }

        // ============================================
        // FUNCIONES DE FILTROS
        // ============================================
        
        function aplicarFiltros() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const estadoFilter = document.getElementById('estadoFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            
            let dateFrom = null;
            let dateTo = null;
            
            if (dateFilter === 'custom') {
                dateFrom = document.getElementById('dateFrom').value;
                dateTo = document.getElementById('dateTo').value;
            } else if (dateFilter) {
                const now = new Date();
                switch (dateFilter) {
                    case 'today':
                        dateFrom = dateTo = now.toISOString().split('T')[0];
                        break;
                    case 'yesterday':
                        const yesterday = new Date(now);
                        yesterday.setDate(now.getDate() - 1);
                        dateFrom = dateTo = yesterday.toISOString().split('T')[0];
                        break;
                    case 'week':
                        const weekStart = new Date(now);
                        weekStart.setDate(now.getDate() - now.getDay());
                        dateFrom = weekStart.toISOString().split('T')[0];
                        dateTo = now.toISOString().split('T')[0];
                        break;
                    case 'month':
                        dateFrom = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
                        dateTo = now.toISOString().split('T')[0];
                        break;
                    case 'last_month':
                        const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                        dateFrom = lastMonth.toISOString().split('T')[0];
                        dateTo = new Date(now.getFullYear(), now.getMonth(), 0).toISOString().split('T')[0];
                        break;
                }
            }
            
            document.querySelectorAll('.table-row').forEach(row => {
                if (row.children.length < 2) return;
                
                const codigo = row.children[0]?.textContent.toLowerCase() || '';
                const cliente = row.children[1]?.textContent.toLowerCase() || '';
                const productos = row.children[3]?.textContent.toLowerCase() || '';
                const fechaReserva = row.children[4]?.textContent || '';
                const estado = row.querySelector('.status-badge')?.textContent.toLowerCase() || '';
                
                // Filtro de búsqueda
                const matchesSearch = searchText === '' || 
                    codigo.includes(searchText) || 
                    cliente.includes(searchText) ||
                    productos.includes(searchText);
                
                // Filtro de estado (CANCELADA oculto por defecto)
                let matchesEstado = false;
                if (estadoFilter === '') {
                    // Por defecto excluir CANCELADA
                    matchesEstado = !estado.includes('cancelada');
                } else {
                    // Mostrar solo el estado seleccionado
                    matchesEstado = estado.includes(estadoFilter.toLowerCase());
                }
                
                // Filtro de fecha
                let matchesDate = true;
                if (dateFrom && dateTo && fechaReserva) {
                    const rowDate = new Date(fechaReserva.split('/').reverse().join('-'));
                    const fromDate = new Date(dateFrom);
                    const toDate = new Date(dateTo);
                    matchesDate = rowDate >= fromDate && rowDate <= toDate;
                }
                
                row.style.display = matchesSearch && matchesEstado && matchesDate ? 'grid' : 'none';
            });
        }

        function limpiarFiltros() {
            document.getElementById('searchInput').value = '';
            document.getElementById('estadoFilter').value = '';
            document.getElementById('dateFilter').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            document.getElementById('customDateRange').style.display = 'none';
            
            document.querySelectorAll('.table-row').forEach(row => {
                row.style.display = 'grid';
            });
        }

        // ============================================
        // EVENT LISTENERS
        // ============================================
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOMContentLoaded ejecutado en reserva.php');
            // Cargar datos de gráficas
            loadChartData();
            
            // Mostrar/ocultar formulario
            document.getElementById('mostrarFormBtn').addEventListener('click', function() {
                console.log('Botón Nueva Reserva clickeado');
                document.getElementById('formReserva').style.display = 'block';
                // Establecer fecha mínima para hoy + 1 día
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                document.getElementById('fecha_limite').min = tomorrow.toISOString().split('T')[0];
            });

            document.getElementById('cancelarReservaBtn').addEventListener('click', function() {
                document.getElementById('formReserva').style.display = 'none';
                document.getElementById('formReserva').reset();
            });

            // Guardar nueva reserva
            document.getElementById('guardarReservaBtn').addEventListener('click', async function() {
                if (!(await validarNuevaReserva())) return;
                
                const btn = this;
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                
                try {
                    const formData = new FormData();
                    formData.append('cliente_id', document.getElementById('cliente').value);
                    formData.append('producto_id', document.getElementById('producto').value);
                    formData.append('cantidad', document.getElementById('cantidad').value);
                    formData.append('fecha_limite', document.getElementById('fecha_limite').value);
                    formData.append('observaciones', document.getElementById('observaciones').value);
                    
                    const response = await fetch('/inversiones-rojas/api/add_reserva.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.ok) {
                        showNotification('Reserva creada exitosamente', 'success', '¡Éxito!');
                        document.getElementById('formReserva').style.display = 'none';
                        document.getElementById('formReserva').reset();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        if (data.errors) {
                            Object.values(data.errors).forEach(error => {
                                showNotification(error, 'error', 'Error de validación');
                            });
                        } else {
                            showNotification(data.error || 'Error creando reserva', 'error', 'Error');
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showNotification('Error de conexión: ' + error.message, 'error', 'Error');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            });

            // Filtros
            document.getElementById('dateFilter').addEventListener('change', function() {
                const customRange = document.getElementById('customDateRange');
                if (this.value === 'custom') {
                    customRange.style.display = 'flex';
                } else {
                    customRange.style.display = 'none';
                }
            });
            
            document.getElementById('filtrarBtn').addEventListener('click', aplicarFiltros);
            document.getElementById('limpiarBtn').addEventListener('click', limpiarFiltros);
            
            // Buscar en tiempo real
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(aplicarFiltros, 300);
            });

            // Botones de acción
            document.querySelectorAll('.btn-ver-detalles').forEach(btn => {
                btn.addEventListener('click', function() {
                    verDetallesReserva(this.dataset.codigo);
                });
            });

            document.querySelectorAll('.btn-editar').forEach(btn => {
                btn.addEventListener('click', function() {
                    cargarEditarReserva(this.dataset.codigo);
                });
            });

            // Cerrar modales
            document.getElementById('closeModalDetalles').addEventListener('click', function() {
                document.getElementById('modalVerDetalles').classList.remove('active');
            });
            
            document.getElementById('cerrarModalDetalles').addEventListener('click', function() {
                document.getElementById('modalVerDetalles').classList.remove('active');
            });

            document.getElementById('closeModalEditar').addEventListener('click', function() {
                document.getElementById('modalEditarReserva').classList.remove('active');
            });
            
            document.getElementById('cancelarEditarBtn').addEventListener('click', function() {
                document.getElementById('modalEditarReserva').classList.remove('active');
            });

           // Guardar edición
document.getElementById('guardarEditarBtn').addEventListener('click', async function() {
    if (!(await validarEditarReserva())) return;
    
    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    
    try {
        const formData = new FormData();
        formData.append('reserva_id', document.getElementById('editReservaId').value);
        formData.append('codigo_reserva', document.getElementById('editCodigoReserva').value);
        formData.append('cliente_id', document.getElementById('editCliente').value);
        formData.append('producto_id', document.getElementById('editProducto').value);
        formData.append('cantidad', document.getElementById('editCantidad').value);
        formData.append('fecha_limite', document.getElementById('editFechaLimite').value);
        const obsValue = document.getElementById('editObservaciones').value.trim();
        const telValue = document.getElementById('editTelefono').value.trim();
        const obsPayload = telValue ? `Tel: ${telValue}` + (obsValue ? ` | ${obsValue}` : '') : obsValue;
        formData.append('observaciones', obsPayload);

        const response = await fetch('/inversiones-rojas/api/update_reserva.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.ok || data.success) {
            // Usar showNotification en lugar de Toast.edited
            showNotification('Reserva actualizada exitosamente', 'success', '¡Éxito!');
            document.getElementById('modalEditarReserva').classList.remove('active');
            setTimeout(() => location.reload(), 1500);
        } else {
            let msg = data.message || data.error || 'Error actualizando reserva';
            if (data.errors) {
                const errors = Object.values(data.errors).filter(Boolean);
                if (errors.length) msg = errors.join(' | ');
            }
            showNotification(msg, 'error', 'Error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión: ' + error.message, 'error', 'Error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});
            // Botones de completar
            document.querySelectorAll('.btn-completar').forEach(btn => {
                btn.addEventListener('click', function() {
                    completingCodigo = this.dataset.codigo;
                    document.getElementById('modalCodigoReserva').textContent = completingCodigo;
                    loadPaymentMethods();
                    document.getElementById('modalMetodoPago').classList.add('active');
                });
            });

            // Cerrar modal de pago
            document.getElementById('closeModalMetodoPago').addEventListener('click', function() {
                document.getElementById('modalMetodoPago').classList.remove('active');
            });

            document.getElementById('cancelModalMetodoPago').addEventListener('click', function() {
                document.getElementById('modalMetodoPago').classList.remove('active');
            });

            const proofInputRes = document.getElementById('paymentComprobantes');
            const proofLabelRes = document.getElementById('paymentComprobantesLabel');
            if (proofInputRes) {
                proofInputRes.addEventListener('change', function() {
                    if (this.files.length === 0) {
                        proofLabelRes.textContent = 'No se han seleccionado archivos';
                    } else if (this.files.length === 1) {
                        proofLabelRes.textContent = this.files[0].name;
                    } else {
                        proofLabelRes.textContent = `${this.files.length} archivos seleccionados`;
                    }
                });
            }

            // Confirmar pago
            document.getElementById('confirmarMetodoPago').addEventListener('click', async function() {
                const metodoId = document.getElementById('selectMetodoPago').value;
                const referencia = document.getElementById('paymentReference').value.trim();
                
                if (!metodoId) {
                    showNotification('Selecciona un método de pago antes de continuar', 'warning', 'Advertencia');
                    return;
                }

                // Validar referencia si es requerida
                const select = document.getElementById('selectMetodoPago');
                const selectedOption = select.options[select.selectedIndex];
                const methodName = selectedOption ? selectedOption.textContent.toLowerCase() : '';
                const requiresReference = methodName.includes('transferencia') || 
                                        methodName.includes('pago móvil') || 
                                        methodName.includes('bancario') || 
                                        methodName.includes('depósito') ||
                                        methodName.includes('movil') ||
                                        methodName.includes('digital');
                
                if (requiresReference && !referencia) {
                    showNotification('Este método de pago requiere una referencia bancaria', 'warning', 'Advertencia');
                    return;
                }

                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

                try {
                    const formData = new FormData();
                    formData.append('codigo_reserva', completingCodigo);
                    formData.append('metodo_pago_id', metodoId);
                    if (referencia) {
                        formData.append('referencia_pago', referencia);
                    }

                    const proofInput = document.getElementById('paymentComprobantes');
                    if (proofInput && proofInput.files.length) {
                        Array.from(proofInput.files).forEach(file => {
                            formData.append('comprobante[]', file);
                        });
                    }

                    const res = await fetch('/inversiones-rojas/api/complete_reserva.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await res.json();

                    if (data.success) {
                        showNotification('Venta generada y reserva completada', 'success', '¡Completada!');
                        document.getElementById('modalMetodoPago').classList.remove('active');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message || 'No se pudo completar la reserva', 'error', 'Error');
                    }
                } catch (err) {
                    console.error('Error al procesar reserva:', err);
                    showNotification('Error de conexión: ' + err.message, 'error', 'Error');
                } finally {
                    this.disabled = false;
                    this.innerHTML = 'Confirmar';
                }
            });

            // Botones de prorrogar
            document.querySelectorAll('.btn-prorrogar').forEach(btn => {
                btn.addEventListener('click', function() {
                    const codigo = this.dataset.codigo;
                    prorrogarReserva(codigo);
                });
            });

            // Modal de cancelación con motivo
            const cancelModal = document.getElementById('modalCancelar');
            const cancelCodigoEl = document.getElementById('cancelCodigoReserva');
            const cancelMotivoEl = document.getElementById('cancelMotivo');
            const closeCancelBtn = document.getElementById('closeModalCancelar');
            const cancelMotivoCancelBtn = document.getElementById('cancelMotivoCancel');
            const cancelMotivoAceptarBtn = document.getElementById('cancelMotivoAceptar');

            let pendingCancelCodigo = null;

            function openCancelModal(codigo) {
                pendingCancelCodigo = codigo;
                cancelCodigoEl.textContent = codigo;
                cancelMotivoEl.value = '';
                cancelModal.classList.add('active');
            }

            function closeCancelModal() {
                cancelModal.classList.remove('active');
                pendingCancelCodigo = null;
            }

            if (closeCancelBtn) closeCancelBtn.addEventListener('click', closeCancelModal);
            if (cancelMotivoCancelBtn) cancelMotivoCancelBtn.addEventListener('click', closeCancelModal);

            if (cancelMotivoAceptarBtn) {
                cancelMotivoAceptarBtn.addEventListener('click', async function() {
                    if (!pendingCancelCodigo) return;
                    
                    const codigo = pendingCancelCodigo;
                    const motivo = cancelMotivoEl.value.trim() || 'Cancelado por el administrador';
                    const btn = this;
                    btn.disabled = true;
                    btn.textContent = 'Cancelando...';

                    try {
                        const url = '/inversiones-rojas/api/cancel_reserva.php';
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                                codigo_reserva: codigo,
                                motivo: motivo
                            })
                        });

                        const text = await res.text();
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (parseErr) {
                            console.error('Respuesta no válida:', text);
                            throw new Error('La API retornó datos inválidos');
                        }

                        if (data.success) {
                            closeCancelModal();
                            showNotification(`Reserva ${codigo} cancelada. Correo enviado al cliente.`, 'success', '¡Cancelada!');
                            setTimeout(() => location.reload(), 800);
                        } else {
                            showNotification(data.message || 'No se pudo procesar', 'error', 'Error');
                        }
                    } catch (err) {
                        console.error('Error al cancelar:', err);
                        showNotification('Error de conexión: ' + err.message, 'error', 'Error');
                    } finally {
                        btn.disabled = false;
                        btn.textContent = 'Cancelar Reserva';
                    }
                });
            }

            // Botones de cancelar (solo para PENDIENTE/PRORROGADA)
            document.querySelectorAll('.btn-cancelar:not([disabled])').forEach(btn => {
                btn.addEventListener('click', function() {
                    const codigo = this.dataset.codigo;
                    openCancelModal(codigo);
                });
            });

            // Botones dentro del modal "Ver Detalles"
            document.getElementById('btnModalEditar').addEventListener('click', function() {
                const codigo = document.getElementById('modalVerDetalles').dataset.codigo;
                if (!codigo) return;
                document.getElementById('modalVerDetalles').classList.remove('active');
                cargarEditarReserva(codigo);
            });

            document.getElementById('btnModalProrrogar').addEventListener('click', function() {
                const codigo = document.getElementById('modalVerDetalles').dataset.codigo;
                if (!codigo) return;
                document.getElementById('modalVerDetalles').classList.remove('active');
                prorrogarReserva(codigo);
            });
        });

        // ============================================
        // TIEMPO RESTANTE
        // ============================================
        
        function updateTimeRemainingElements() {
            document.querySelectorAll('.time-remaining').forEach(el => {
                const iso = el.dataset.fecha;
                const row = el.closest('.table-row');
                const statusBadge = row ? row.querySelector('.status-badge') : null;
                const statusText = statusBadge ? statusBadge.textContent.toLowerCase() : '';
                
                // Si la reserva está completada, mostrar "Completada"
                if (statusText.includes('completada')) {
                    el.textContent = 'Completada';
                    el.style.color = '#1F9166';
                    return;
                }
                
                // Si la reserva está cancelada, mostrar "Cancelada"
                if (statusText.includes('cancelada')) {
                    el.textContent = 'Cancelada';
                    el.style.color = '#e74c3c';
                    return;
                }
                
                if (!iso) return;
                
                const diff = Date.parse(iso) - Date.now();
                if (isNaN(diff)) return;
                
                if (diff <= 0) {
                    el.textContent = 'Vencida';
                    el.style.color = '#e74c3c';
                    return;
                }
                
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const secs = Math.floor((diff % (1000 * 60)) / 1000);
                
                if (days > 0) {
                    el.textContent = `${days}d ${hours.toString().padStart(2,'0')}:${mins.toString().padStart(2,'0')}:${secs.toString().padStart(2,'0')}`;
                    el.style.color = '#1F9166';
                } else {
                    el.textContent = `${hours.toString().padStart(2,'0')}:${mins.toString().padStart(2,'0')}:${secs.toString().padStart(2,'0')}`;
                    el.style.color = days === 0 ? '#f39c12' : '#1F9166';
                }
            });
        }

        // Modal para ver imagen en grande
        function verImagenComprobante(url) {
            const modal = document.createElement('div');
            modal.id = 'modalVerImagen';
            modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:999999;display:flex;align-items:center;justify-content:center;cursor:pointer;';
            modal.innerHTML = `<img src="${url}" style="max-width:90%;max-height:90%;border-radius:8px;object-fit:contain;" />`;
            modal.onclick = () => modal.remove();
            document.body.appendChild(modal);
        }

        updateTimeRemainingElements();
        setInterval(updateTimeRemainingElements, 1000);
    </script>
</body>
</html>