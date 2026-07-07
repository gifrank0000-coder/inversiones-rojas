<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';
$base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devoluciones - Inversiones Rojas</title>
    <link rel="icon" href="<?php echo $base_url; ?>/public/img/logo.png">
    <script>
        var APP_BASE = '<?php echo $base_url; ?>';
        var TASA_CAMBIO = <?php echo getTasaCambio(); ?>;
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/layouts/devoluciones.css">
    <!-- Sistema de notificaciones y diálogos personalizados -->
    <script src="<?php echo $base_url; ?>/public/js/inv-notifications.js"></script>
    <style>
        /* Estilos para moneda dual */
        .moneda-bs { color: #1F9166; font-weight: 700; }
        .moneda-usd { color: #6c757d; font-size: 0.85em; }

        /* ★ Wrapper con scroll horizontal ★ */
        .devoluciones-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .devoluciones-table {
            width: 100%;
            min-width: 980px;
        }

        /* ★ Grid layout con unidades flexibles ★ */
        .table-header, .table-row {
            display: grid;
            grid-template-columns: 1.8fr 1.5fr 1fr 0.9fr 0.7fr 0.6fr 0.8fr 0.7fr 0.9fr;
            gap: 12px;
            align-items: center;
            padding: 15px 20px;
        }

        .table-header {
            background: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #e0e0e0;
        }

        .table-row {
            border-bottom: 1px solid #e0e0e0;
        }

        .table-row:hover {
            background: #f8f9fa;
        }

        /* ★ Responsive - 4 breakpoints ★ */
        
        /* Tablet - ocultar columnas menos importantes */
        @media (max-width: 1024px) {
            .table-header, .table-row {
                grid-template-columns: 1.8fr 1.5fr 1fr 0.7fr 0.6fr 0.9fr;
            }
            /* Ocultar: Producto(3), Monto(6), Venta(7) */
            .table-header > div:nth-child(3),
            .table-row > div:nth-child(3),
            .table-header > div:nth-child(6),
            .table-row > div:nth-child(6),
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
            .table-row .col-acciones {
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
        
        /* Botones de acción */
        .row-actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            background: white;
            border: 1px solid #e0e0e0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .btn-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-icon.btn-success { color: #1F9166; border-color: #c3e6cb; background: #e8f6f1; }
        .btn-icon.btn-success:hover { background: #d4edda; }
        .btn-icon.btn-danger { color: #e04b4b; border-color: #f5c6cb; background: #fdecea; }
        .btn-icon.btn-danger:hover { background: #f8d7da; }
        .btn-icon.btn-info { color: #3498db; border-color: #bee5eb; background: #e8f3ff; }
        .btn-icon.btn-info:hover { background: #d1ecf1; }
        
        /* Status badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            width: 100%;
        }
        
        .status-pendiente { background: #fdecea; color: #e04b4b; }
        .status-aprobado { background: #e8f6f1; color: #1F9166; }
        .status-rechazado { background: #f8e9f0; color: #c12b7a; }

        /* Modal de detalles — igual al patrón de otras ventanas */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.65);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999;
            opacity: 0; visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
    </style>
</head>
<body>

<?php
require_once __DIR__ . '/../../models/database.php';
$pdo = Database::getInstance();
$devoluciones = [];
$stats = ['pendientes' => 0, 'aprobadas' => 0, 'valor_total' => 0, 'total' => 0];
$ventasList = [];
$motivos = [];
$trendLabels = [];
$trendData = [];
$topMotivos = [];

try {
    // Consulta mejorada con cálculo real de monto
    $sql = "SELECT d.id, d.codigo_devolucion, d.motivo, d.observaciones AS descripcion, 
                   d.estado_devolucion AS estado, d.cantidad, 
                   to_char(d.created_at, 'DD/MM/YYYY') AS fecha, 
                   c.nombre_completo AS cliente, p.nombre AS producto, 
                   p.codigo_interno,
                   COALESCE(v.codigo_venta, po.codigo_pedido) AS venta_codigo,
                   COALESCE(dv.precio_unitario, dpo.precio_unitario, 0) as precio_unitario,
                   (d.cantidad * COALESCE(dv.precio_unitario, dpo.precio_unitario, 0)) as monto
            FROM devoluciones d
            LEFT JOIN clientes c ON d.cliente_id = c.id
            LEFT JOIN productos p ON d.producto_id = p.id
            LEFT JOIN ventas v ON d.venta_id = v.id
            LEFT JOIN pedidos_online po ON d.pedido_id = po.id
            LEFT JOIN detalle_ventas dv ON d.venta_id = dv.venta_id AND d.producto_id = dv.producto_id
            LEFT JOIN detalle_pedidos_online dpo ON d.pedido_id = dpo.pedido_id AND d.producto_id = dpo.producto_id
            ORDER BY d.created_at DESC
            LIMIT 200";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $devoluciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TUS ESTADÍSTICAS ORIGINALES
    $statsSql = "SELECT 
        SUM(CASE WHEN UPPER(estado_devolucion) = 'PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
        SUM(CASE WHEN UPPER(estado_devolucion) = 'APROBADO' THEN 1 ELSE 0 END) AS aprobadas,
        0 AS valor_total,
        COUNT(*) AS total
        FROM devoluciones";
    $sstmt = $pdo->prepare($statsSql);
    $sstmt->execute();
    $row = $sstmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $stats['pendientes'] = intval($row['pendientes']);
        $stats['aprobadas'] = intval($row['aprobadas']);
        $stats['valor_total'] = floatval($row['valor_total']);
        $stats['total'] = intval($row['total']);
    }

    // TU LISTA DE VENTAS ORIGINAL
    $vsql = "SELECT v.id, v.codigo_venta, c.nombre_completo 
             FROM ventas v 
             LEFT JOIN clientes c ON v.cliente_id = c.id 
             ORDER BY v.created_at DESC 
             LIMIT 100";
    $vstmt = $pdo->prepare($vsql);
    $vstmt->execute();
    $ventasList = $vstmt->fetchAll(PDO::FETCH_ASSOC);

    // TUS MOTIVOS ORIGINALES
    $mstmt = $pdo->prepare("SELECT motivo, COUNT(*) AS cnt 
                            FROM devoluciones 
                            GROUP BY motivo 
                            ORDER BY cnt DESC");
    $mstmt->execute();
    $motivos = $mstmt->fetchAll(PDO::FETCH_ASSOC);
    
    $topMotivos = array_slice($motivos, 0, 4);

    // TUS TENDENCIAS ORIGINALES
    $tstmt = $pdo->prepare("SELECT to_char(created_at, 'YYYY-MM') AS ym, COUNT(*) AS cnt 
                            FROM devoluciones 
                            GROUP BY ym 
                            ORDER BY ym ASC");
    $tstmt->execute();
    $trendRows = $tstmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($trendRows as $r) {
        $trendLabels[] = $r['ym'];
        $trendData[] = intval($r['cnt']);
    }

} catch (Exception $e) {
    error_log('Error cargando devoluciones: ' . $e->getMessage());
}
?>

<div class="admin-content">
    <!-- STATS CARDS -->
    <div class="devoluciones-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['pendientes']); ?></h3>
                <p>Devoluciones Pendientes</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['aprobadas']); ?></h3>
                <p>Devoluciones Aprobadas</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <?php $precios = formatearMonedaDual($stats['valor_total']); ?>
                <h3><span class="moneda-bs"><?php echo $precios['bs']; ?></span> <span class="moneda-usd">(<?php echo $precios['usd']; ?>)</span></h3>
                <p>Valor en Devoluciones</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total'] > 0 ? number_format(($stats['total'] / max(1, $stats['total'])) * 100, 1) : '0.0'; ?>%</h3>
                <p>Tasa de Devolución</p>
            </div>
        </div>
    </div>

    <!-- GRÁFICAS -->
    <div class="charts-grid">
        <div class="chart-container">
            <div class="chart-header">
                <h3>Devoluciones por Motivo</h3>
            </div>
            <div class="chart-wrapper">
                <canvas id="motivosChart"></canvas>
            </div>
        </div>
        <div class="chart-container">
            <div class="chart-header">
                <h3>Tendencias de Devoluciones</h3>
            </div>
            <div class="chart-wrapper">
                <canvas id="tendenciasChart"></canvas>
            </div>
        </div>
    </div>

    <!-- FORMULARIO REFACTORIZADO - Versión compatible con admin -->
    <div class="form-container" id="formDevolucion" style="display: none;">
        <h3 style="margin-bottom: 20px; color: #2c3e50;">Nueva Devolución (Registro Manual)</h3>
        
        <div class="form-grid">
            <!-- Selección de Cliente -->
            <div class="form-group">
                <label for="clienteDevolucionAdminSelect">Cliente <span style="color: #e74c3c;">*</span></label>
                <select class="form-control" id="clienteDevolucionAdminSelect" required>
                    <option value="">Seleccione un cliente</option>
                    <?php 
                    try {
                        $clientesStmt = $pdo->prepare("SELECT id, nombre_completo, cedula_rif FROM clientes WHERE estado = true ORDER BY nombre_completo ASC");
                        $clientesStmt->execute();
                        $clientes = $clientesStmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($clientes as $cli) {
                            echo '<option value="' . htmlspecialchars($cli['id']) . '">' . htmlspecialchars($cli['nombre_completo'] . ' (' . $cli['cedula_rif'] . ')') . '</option>';
                        }
                    } catch (Exception $e) {
                        error_log('Error cargando clientes: ' . $e->getMessage());
                    }
                    ?>
                </select>
            </div>

            <!-- Selección de Venta/Pedido -->
            <div class="form-group">
                <label for="ventaDevolucionAdminSelect">Venta/Pedido <span style="color: #e74c3c;">*</span></label>
                <select class="form-control" id="ventaDevolucionAdminSelect" required style="display: none;">
                    <option value="">Cargando...</option>
                </select>
                <div id="ventaDevolucionMsg" style="padding: 10px; background: #f0f0f0; border-radius: 4px; color: #666; font-size: 14px;">
                    Seleccione primero un cliente
                </div>
            </div>

            <!-- Motivo de Devolución -->
            <div class="form-group">
                <label for="motivoDevolucionAdminSelect">Motivo <span style="color: #e74c3c;">*</span></label>
                <select class="form-control" id="motivoDevolucionAdminSelect" required>
                    <option value="">Seleccione motivo</option>
                    <option value="Producto Defectuoso">Producto Defectuoso</option>
                    <option value="Producto Incorrecto">Producto Incorrecto</option>
                    <option value="Arrepentimiento">Arrepentimiento</option>
                    <option value="Producto Dañado">Producto Dañado</option>
                    <option value="Garantía">Garantía</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
        </div>

        <!-- Productos del Pedido -->
        <div class="form-group" id="productosContainerAdminManual" style="display: none; margin-top: 20px;">
            <label>Productos a devolver <span style="color: #e74c3c;">*</span></label>
            <div id="productosListAdminManual" style="border: 1px solid #e0e0e0; border-radius: 8px; max-height: 400px; overflow-y: auto;">
                <!-- Se llena dinámicamente con JS -->
            </div>
        </div>

        <!-- Observaciones -->
        <div class="form-group">
            <label for="descripcionDevolucionAdminManual">Observaciones adicionales</label>
            <textarea class="form-control" id="descripcionDevolucionAdminManual" rows="3" placeholder="Notas del administrador sobre la devolución..."></textarea>
        </div>

        <div class="form-actions">
            <button class="btn btn-secondary" id="cancelarDevolucionBtn">
                <i class="fas fa-times"></i>
                Cancelar
            </button>
            <button class="btn btn-primary" id="guardarDevolucionBtnManual">
                <i class="fas fa-save"></i>
                Guardar Devolución
            </button>
        </div>
    </div>

    <!-- MOTIVOS -->
    <div class="motivos-section">
        <h3 style="margin-bottom: 20px; color: #2c3e50;">Motivos de Devolución Más Comunes</h3>
        <div class="motivos-grid">
            <?php if (!empty($topMotivos)): ?>
                <?php foreach ($topMotivos as $tm): ?>
                    <div class="motivo-card">
                        <div class="motivo-header">
                            <div class="motivo-name"><?php echo htmlspecialchars($tm['motivo'] ?: 'Sin motivo'); ?></div>
                            <div class="motivo-count"><?php echo intval($tm['cnt']); ?> casos</div>
                        </div>
                        <div class="motivo-description">
                            Motivo de devolución registrado <?php echo intval($tm['cnt']); ?> veces
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="motivo-card">
                    <div class="motivo-header">
                        <div class="motivo-name">No hay datos</div>
                        <div class="motivo-count">0 casos</div>
                    </div>
                    <div class="motivo-description">
                        No hay motivos de devolución registrados
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="search-filters">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Buscar devoluciones...">
            <i class="fas fa-search search-icon"></i>
        </div>
        <select class="filter-select" id="estadoFilter">
            <option value="">Todos los estados</option>
            <option value="PENDIENTE">Pendiente</option>
            <option value="APROBADO">Aprobado</option>
            <option value="RECHAZADO">Rechazado</option>
        </select>
    </div>

    <!-- BOTONES DE ACCIÓN -->
    <div class="devoluciones-actions">
        <div class="action-buttons">
            <button class="btn btn-primary" id="mostrarFormBtn">
                <i class="fas fa-plus"></i>
                Nueva Devolución
            </button>

        </div>
    </div>

    <!-- TABLA - responsive con scroll horizontal -->
    <div class="devoluciones-table-wrapper">
    <div class="devoluciones-table">
        <div class="table-header">
            <div class="col-id">Código</div>
            <div class="col-cliente">Cliente</div>
            <div class="col-producto">Producto</div>
            <div class="col-motivo">Motivo</div>
            <div class="col-fecha">Fecha</div>
            <div class="col-monto">Monto</div>
            <div class="col-venta">Venta</div>
            <div class="col-estado">Estado</div>
            <div class="col-acciones">Acciones</div>
        </div>
        
        <?php if (!empty($devoluciones)): ?>
            <?php foreach ($devoluciones as $d): ?>
                <div class="table-row" data-id="<?php echo $d['id']; ?>">
                    <div class="col-id"><strong><?php echo htmlspecialchars($d['codigo_devolucion'] ?? ('DEV-' . $d['id'])); ?></strong></div>
                    <div class="col-cliente"><?php echo htmlspecialchars($d['cliente'] ?? '—'); ?></div>
                    <div class="col-producto"><?php echo htmlspecialchars($d['producto'] ?? '—'); ?></div>
                    <div class="col-motivo"><?php echo htmlspecialchars($d['motivo'] ?? '—'); ?></div>
                    <div class="col-fecha"><?php echo htmlspecialchars($d['fecha'] ?? '—'); ?></div>
                    <?php $precios = formatearMonedaDual(floatval($d['monto'] ?? 0)); ?>
                    <div class="col-monto"><span class="moneda-bs"><?php echo $precios['bs']; ?></span> <span class="moneda-usd">(<?php echo $precios['usd']; ?>)</span></div>
                    <div class="col-venta"><?php echo htmlspecialchars($d['venta_codigo'] ?? '—'); ?></div>
                    <div class="col-estado">
                        <?php $st = strtolower($d['estado'] ?? 'pendiente'); ?>
                        <span class="status-badge status-<?php echo $st; ?>">
                            <?php echo ucfirst($st); ?>
                        </span>
                    </div>
                    <div class="col-acciones">
                        <div class="row-actions">
                            <?php if (strtolower($d['estado'] ?? '') == 'pendiente'): ?>
                                <button class="btn-icon btn-success" onclick="aprobarDevolucion(<?php echo $d['id']; ?>)" title="Aprobar">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn-icon btn-danger" onclick="rechazarDevolucion(<?php echo $d['id']; ?>)" title="Rechazar">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                            <button class="btn-icon btn-info" onclick="verDetalles(<?php echo $d['id']; ?>)" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="table-row">
                <div class="col-id">—</div>
                <div class="col-cliente">No hay devoluciones registradas</div>
                <div class="col-producto">—</div>
                <div class="col-motivo">—</div>
                <div class="col-fecha">—</div>
                <div class="col-monto"><span class="moneda-bs">Bs 0,00</span> <span class="moneda-usd">($0,00)</span></div>
                <div class="col-venta">—</div>
                <div class="col-estado">—</div>
                <div class="col-acciones">—</div>
            </div>
        <?php endif; ?>
    </div>
    </div> <!-- cierre de devoluciones-table-wrapper -->
</div>

<script>
// ═══════════════════════════════════════════════════
// GRÁFICAS (sin cambios respecto al original)
// ═══════════════════════════════════════════════════
const motivosData = <?php echo json_encode(array_map(function($m) { 
    return ['label' => $m['motivo'] ?: 'Sin motivo', 'value' => intval($m['cnt'])]; 
}, $motivos)); ?>;

const trendLabels = <?php echo json_encode($trendLabels); ?>;
const trendData   = <?php echo json_encode($trendData); ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Gráfica de Motivos
    const motivosCtx = document.getElementById('motivosChart').getContext('2d');
    const palette = ['#e74c3c','#f39c12','#3498db','#1abc9c','#9b59b6','#95a5a6','#f1c40f','#2ecc71'];
    let motivosLabels = ['Defectuoso','Arrepentimiento','Incorrecto','Dañado','Otro'];
    let motivosValues = [0,0,0,0,0];
    if (motivosData.length > 0) {
        motivosLabels = motivosData.map(d => d.label);
        motivosValues = motivosData.map(d => d.value);
    }
    const bg = motivosLabels.map((_, i) => palette[i % palette.length]);
    new Chart(motivosCtx, {
        type: 'doughnut',
        data: { labels: motivosLabels, datasets: [{ data: motivosValues, backgroundColor: bg, borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Gráfica de Tendencias
    const tendenciasCtx = document.getElementById('tendenciasChart').getContext('2d');
    let displayLabels = trendLabels.length ? trendLabels : ['Ene','Feb','Mar'];
    let displayData   = trendData.length   ? trendData   : [0,0,0];
    new Chart(tendenciasCtx, {
        type: 'line',
        data: { labels: displayLabels, datasets: [{ label: 'Devoluciones', data: displayData, backgroundColor: 'rgba(231,76,60,0.1)', borderColor: '#e74c3c', borderWidth: 2, tension: 0.3, fill: true }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true } }, scales: { y: { beginAtZero: true } } }
    });
});

// ═══════════════════════════════════════════════════
// MAPA VENTA → CLIENTE (igual que original)
// ═══════════════════════════════════════════════════
const ventaClienteMap = <?php echo json_encode(array_reduce($ventasList, function($acc, $v) { 
    $acc[$v['id']] = $v['nombre_completo']; 
    return $acc; 
}, [])); ?>;

const ventaSelect  = document.getElementById('ventaDevolucion');
const clienteInput = document.getElementById('clienteDevolucion');

if (ventaSelect) {
    ventaSelect.addEventListener('change', function() {
        clienteInput.value = ventaClienteMap[this.value] || '';
    });
}

// ═══════════════════════════════════════════════════
// MOSTRAR / OCULTAR FORMULARIO (CORREGIDO - MÁS ROBUSTO)
// ═══════════════════════════════════════════════════
const mostrarFormBtn = document.getElementById('mostrarFormBtn');
const formDevolucion = document.getElementById('formDevolucion');
const cancelarBtn = document.getElementById('cancelarDevolucionBtn');

function mostrarFormulario() {
    if (formDevolucion) {
        formDevolucion.style.display = 'block';
        // Limpiar formulario al abrir
        document.getElementById('clienteDevolucionAdminSelect').value = '';
        document.getElementById('ventaDevolucionAdminSelect').innerHTML = '<option value="">Cargando...</option>';
        document.getElementById('ventaDevolucionAdminSelect').style.display = 'none';
        document.getElementById('ventaDevolucionMsg').style.display = 'block';
        document.getElementById('ventaDevolucionMsg').textContent = 'Seleccione primero un cliente';
        document.getElementById('productosContainerAdminManual').style.display = 'none';
        document.getElementById('productosListAdminManual').innerHTML = '';
        document.getElementById('motivoDevolucionAdminSelect').value = '';
        document.getElementById('descripcionDevolucionAdminManual').value = '';
        limpiarErroresForm();
    }
    if (mostrarFormBtn) {
        mostrarFormBtn.style.display = 'none';
    }
}

function ocultarFormulario() {
    if (formDevolucion) {
        formDevolucion.style.display = 'none';
    }
    if (mostrarFormBtn) {
        mostrarFormBtn.style.display = 'inline-flex';
    }
    limpiarErroresForm();
}

// Eliminar event listeners anteriores para evitar duplicados
if (mostrarFormBtn) {
    // Remover listeners anteriores (si los hay) usando clone + replace
    const newBtn = mostrarFormBtn.cloneNode(true);
    mostrarFormBtn.parentNode.replaceChild(newBtn, mostrarFormBtn);
    newBtn.addEventListener('click', mostrarFormulario);
}

if (cancelarBtn) {
    const newCancel = cancelarBtn.cloneNode(true);
    cancelarBtn.parentNode.replaceChild(newCancel, cancelarBtn);
    newCancel.addEventListener('click', ocultarFormulario);
}

// También asegurar que al guardar exitosamente se oculte el formulario
// (esto ya está en el éxito de guardarDevolucion, pero reforzamos)
const guardarBtn = document.getElementById('guardarDevolucionBtnManual');
if (guardarBtn) {
    const originalGuardar = guardarBtn.onclick;
    guardarBtn.addEventListener('click', function() {
        // El código original ya tiene ocultamiento al éxito, solo aseguramos
        setTimeout(() => {
            if (document.getElementById('formDevolucion').style.display === 'none') {
                if (mostrarFormBtn) mostrarFormBtn.style.display = 'inline-flex';
            }
        }, 100);
    });
}

// ═══════════════════════════════════════════════════
// VALIDACIONES DE CAMPOS (NUEVO — no altera HTML)
// ═══════════════════════════════════════════════════
function mostrarError(inputId, mensaje) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.style.borderColor = '#e74c3c';
    input.style.boxShadow   = '0 0 0 2px rgba(231,76,60,0.15)';
    // Reutiliza InvValidate si está disponible, sino crea mensaje inline
    let errEl = document.getElementById('err_' + inputId);
    if (!errEl) {
        errEl = document.createElement('div');
        errEl.id = 'err_' + inputId;
        errEl.style.cssText = 'color:#e74c3c;font-size:11.5px;margin-top:3px;';
        input.parentNode.insertBefore(errEl, input.nextSibling);
    }
    errEl.textContent = mensaje;
}

function limpiarError(inputId) {
    const input = document.getElementById(inputId);
    if (input) { input.style.borderColor = ''; input.style.boxShadow = ''; }
    const errEl = document.getElementById('err_' + inputId);
    if (errEl) errEl.textContent = '';
}

function limpiarErroresForm() {
    ['ventaDevolucion','productoDevolucion','cantidadDevolucion','motivoDevolucion',
     'fechaDevolucion','montoDevolucion','estadoDevolucion'].forEach(limpiarError);
}

function validarFormulario() {
    let ok = true;

    // Venta obligatoria
    if (!InvValidate.required({value: document.getElementById('ventaDevolucion')?.value}, 'Venta de referencia')) ok = false;

    // Cantidad positiva
    if (!InvValidate.positiveNumber({value: document.getElementById('cantidadDevolucion')?.value}, 'Cantidad')) ok = false;

    // Motivo obligatorio
    if (!InvValidate.required({value: document.getElementById('motivoDevolucion')?.value}, 'Motivo')) ok = false;

    // Fecha: no futura
    const fechaEl = document.getElementById('fechaDevolucion');
    if (!InvValidate.date({value: fechaEl?.value, maxDate: new Date().toISOString().split('T')[0]}, 'Fecha de devolución')) ok = false;

    // Monto: positivo si se ingresa
    const monto = document.getElementById('montoDevolucion')?.value;
    if (monto && !InvValidate.positiveNumber({value: monto}, 'Monto')) ok = false;

    return ok;
}

// ═══════════════════════════════════════════════════
// CARGAR VENTAS/PEDIDOS DEL CLIENTE (NUEVO)
// ═══════════════════════════════════════════════════
async function cargarVentasDelCliente(clienteId) {
    const ventaSelect = document.getElementById('ventaDevolucionAdminSelect');
    const ventaMsg = document.getElementById('ventaDevolucionMsg');
    
    if (!clienteId) {
        ventaMsg.style.display = 'block';
        ventaSelect.style.display = 'none';
        ventaMsg.textContent = 'Seleccione primero un cliente';
        return;
    }

    try {
        const apiUrl = (window.APP_BASE || '') + '/api/get_cliente_ventas.php?cliente_id=' + clienteId;
        const res = await fetch(apiUrl);
        
        if (!res.ok) {
            throw new Error('Error cargando ventas');
        }
        
        const data = await res.json();
        
        if (!data.success || !data.ventas || data.ventas.length === 0) {
            ventaMsg.style.display = 'block';
            ventaSelect.style.display = 'none';
            ventaMsg.textContent = 'Este cliente no tiene ventas registradas';
            ventaSelect.innerHTML = '<option value="">Sin ventas</option>';
            return;
        }

        // Llenar select de ventas
        let html = '<option value="">Seleccione una venta/pedido</option>';
        data.ventas.forEach(venta => {
            const tipo = venta.tipo === 'pedido' ? 'Pedido' : 'Venta';
            const codigo = venta.codigo || ('V-' + venta.id);
            html += `<option value="${venta.id}" data-tipo="${venta.tipo}" data-codigo="${escapeHtmlAdmin(codigo)}">
                ${tipo} ${escapeHtmlAdmin(codigo)} - ${new Date(venta.fecha).toLocaleDateString('es-VE')}
            </option>`;
        });
        
        ventaSelect.innerHTML = html;
        ventaSelect.style.display = 'block';
        ventaMsg.style.display = 'none';
        
    } catch (err) {
        console.error('Error:', err);
        Toast.error('Error al cargar ventas del cliente', 'Error');
        ventaMsg.textContent = 'Error cargando ventas';
        ventaMsg.style.display = 'block';
        ventaSelect.style.display = 'none';
    }
}

// Evento: cuando cambia el cliente
document.getElementById('clienteDevolucionAdminSelect')?.addEventListener('change', function() {
    cargarVentasDelCliente(this.value);
    document.getElementById('productosContainerAdminManual').style.display = 'none';
    document.getElementById('productosListAdminManual').innerHTML = '';
});

// ═══════════════════════════════════════════════════
// CARGAR PRODUCTOS DE LA VENTA (MEJORADO)
// ═══════════════════════════════════════════════════
async function cargarProductosVentaManual(ventaId, tipoVenta) {
    if (!ventaId) {
        document.getElementById('productosContainerAdminManual').style.display = 'none';
        document.getElementById('productosListAdminManual').innerHTML = '';
        return;
    }

    try {
        const apiUrl = tipoVenta === 'pedido' 
            ? (window.APP_BASE || '') + '/api/get_pedido_products.php?pedido_id=' + ventaId
            : (window.APP_BASE || '') + '/api/get_venta_products_admin.php?venta_id=' + ventaId;
        
        const res = await fetch(apiUrl);
        
        if (!res.ok) {
            throw new Error('Error cargando productos');
        }
        
        const data = await res.json();
        
        if (!data.ok || !data.products || data.products.length === 0) {
            document.getElementById('productosContainerAdminManual').style.display = 'none';
            document.getElementById('productosListAdminManual').innerHTML = '';
            return;
        }

        let html = '';
        data.products.forEach(prod => {
            const maxQty = parseInt(prod.cantidad_disponible || 1);
            const price = parseFloat(prod.precio_unitario || 0).toFixed(2);
            
            html += `
            <div style="display: grid; grid-template-columns: 50px 1fr 100px 100px 100px; gap: 15px; padding: 12px 15px; align-items: center; border-bottom: 1px solid #f0f0f0;">
                <input type="checkbox" class="product-checkbox-manual" data-producto-id="${prod.producto_id}" data-producto-nombre="${escapeHtmlAdmin(prod.nombre || '')}" data-precio="${price}" style="width: 18px; height: 18px; cursor: pointer;">
                <div>
                    <div style="font-weight: 600; color: #2c3e50; font-size: 14px;">${escapeHtmlAdmin(prod.nombre || 'Producto')}</div>
                    <div style="font-size: 11px; color: #7f8c8d;">Código: ${escapeHtmlAdmin(prod.codigo_interno || 'N/A')}</div>
                </div>
                <div style="text-align: center;">
                    <span style="font-size: 13px; color: #555;">Disp: ${maxQty}</span>
                </div>
                <div style="text-align: center;">
                    <span style="font-size: 12px; color: #888;">$${price}</span>
                </div>
                <input type="number" class="product-quantity-manual" min="1" max="${maxQty}" value="1" disabled style="width: 80px; padding: 6px; border: 1px solid #e0e0e0; border-radius: 4px; text-align: center;">
            </div>
            `;
        });

        document.getElementById('productosListAdminManual').innerHTML = html;
        document.getElementById('productosContainerAdminManual').style.display = 'block';

        // Reactivar eventos de checkboxes
        document.querySelectorAll('.product-checkbox-manual').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const qty = this.closest('[style*="grid"]').querySelector('.product-quantity-manual');
                qty.disabled = !this.checked;
                if (!this.checked) qty.value = 1;
            });
        });

    } catch (err) {
        console.error('Error:', err);
        Toast.error('Error al cargar productos', 'Error');
    }
}

// Evento: cuando cambia la venta
document.getElementById('ventaDevolucionAdminSelect')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const tipoVenta = selectedOption?.dataset?.tipo || 'venta';
    cargarProductosVentaManual(this.value, tipoVenta);
});

// ═══════════════════════════════════════════════════
// GUARDAR DEVOLUCIÓN (MANUAL - NUEVO FORMATO)
// ═══════════════════════════════════════════════════
document.getElementById('guardarDevolucionBtnManual')?.addEventListener('click', async function() {
    const clienteId = document.getElementById('clienteDevolucionAdminSelect')?.value;
    const ventaId = document.getElementById('ventaDevolucionAdminSelect')?.value;
    const motivo = document.getElementById('motivoDevolucionAdminSelect')?.value;
    const ventaSelect = document.getElementById('ventaDevolucionAdminSelect');
    const selectedOption = ventaSelect?.options[ventaSelect.selectedIndex];
    const tipoVenta = selectedOption?.dataset?.tipo || 'venta';
    
    if (!clienteId) {
        Toast.warning('Por favor seleccione un cliente', 'Validación');
        return;
    }

    if (!ventaId) {
        Toast.warning('Por favor seleccione una venta/pedido', 'Validación');
        return;
    }

    if (!motivo) {
        Toast.warning('Por favor seleccione un motivo', 'Validación');
        return;
    }

    // Obtener productos seleccionados
    const productosSeleccionados = Array.from(document.querySelectorAll('.product-checkbox-manual:checked'));
    
    if (productosSeleccionados.length === 0) {
        Toast.warning('Por favor seleccione al menos un producto', 'Validación');
        return;
    }

    // Construir items array
    const items = productosSeleccionados.map(cb => ({
        producto_id: parseInt(cb.dataset.productoId, 10) || 0,
        cantidad: parseInt(cb.closest('[style*="grid"]').querySelector('.product-quantity-manual').value, 10) || 1
    }));

    const payload = tipoVenta === 'pedido'
        ? {
            pedido_id: parseInt(ventaId, 10),
            items: items,
            motivo: motivo,
            descripcion: document.getElementById('descripcionDevolucionAdminManual')?.value || ''
          }
        : {
            venta_id: parseInt(ventaId, 10),
            items: items,
            motivo: motivo,
            descripcion: document.getElementById('descripcionDevolucionAdminManual')?.value || ''
          };

    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    try {
        const apiUrl = (window.APP_BASE || '') + '/api/add_devolucion.php';
        const res = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        
        if (data.success) {
            document.getElementById('formDevolucion').style.display = 'none';
            document.getElementById('mostrarFormBtn').style.display = 'inline-flex';
            Toast.success('Devolución registrada exitosamente', '¡Éxito!');
            setTimeout(() => location.reload(), 1400);
        } else {
            Toast.error(data.error || 'Error al guardar la devolución', 'Error');
        }
    } catch (e) {
        console.error('Error:', e);
        Toast.error('Error de conexión con el servidor', 'Error de red');
    }

    this.disabled = false;
    this.innerHTML = '<i class="fas fa-save"></i> Guardar Devolución';
});

function escapeHtmlAdmin(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function (s) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[s];
    });
}

// ═══════════════════════════════════════════════════
// APROBAR DEVOLUCIÓN (MEJORADO — reemplaza confirm/alert)
// ═══════════════════════════════════════════════════
async function aprobarDevolucion(id) {
    // showConfirm retorna Promise<boolean> — NO usa callbacks
    const confirmed = await showConfirm({
        title:       '¿Aprobar esta devolución?',
        message:     'La devolución será marcada como aprobada y procesada.',
        type:        'info',
        confirmText: 'Sí, aprobar',
        cancelText:  'Cancelar'
    });
    if (!confirmed) return;
    try {
        const apiUrl = (window.APP_BASE || '') + '/api/update_devolucion_estado.php';
        const res  = await fetch(apiUrl, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ id: id, estado: 'APROBADO' })
        });
        const data = await res.json();
        if (data.ok || data.success) {
            Toast.success(data.message || 'Devolución aprobada correctamente', '¡Aprobado!');
            setTimeout(() => location.reload(), 1400);
        } else {
            Toast.error(data.error || data.message || 'No se pudo aprobar la devolución', 'Error');
        }
    } catch(e) {
        Toast.error('Error de conexión con el servidor', 'Error de red');
    }
}

// ═══════════════════════════════════════════════════
// RECHAZAR DEVOLUCIÓN (MEJORADO)
// ═══════════════════════════════════════════════════
async function rechazarDevolucion(id) {
    const confirmed = await showConfirm({
        title:       '¿Rechazar esta devolución?',
        message:     'La devolución será marcada como rechazada.',
        type:        'danger',
        confirmText: 'Sí, rechazar',
        cancelText:  'Cancelar'
    });
    if (!confirmed) return;
    try {
        const apiUrl = (window.APP_BASE || '') + '/api/update_devolucion_estado.php';
        const res  = await fetch(apiUrl, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ id: id, estado: 'RECHAZADO' })
        });
        const data = await res.json();
        if (data.ok || data.success) {
            Toast.canceled(data.message || 'Devolución rechazada', 'Rechazado');
            setTimeout(() => location.reload(), 1400);
        } else {
            Toast.error(data.error || data.message || 'No se pudo rechazar la devolución', 'Error');
        }
    } catch(e) {
        Toast.error('Error de conexión con el servidor', 'Error de red');
    }
}

// ═══════════════════════════════════════════════════
// VER DETALLES (MEJORADO — usa modal nativo del sistema)
// ═══════════════════════════════════════════════════
// Datos de filas inyectados desde PHP para el modal de detalles
const devolucionesData = <?php echo json_encode(array_map(function($d) {
    return [
        'id'             => $d['id'],
        'codigo'         => $d['codigo_devolucion'] ?? ('DEV-'.$d['id']),
        'cliente'        => $d['cliente'] ?? '—',
        'producto'       => $d['producto'] ?? '—',
        'motivo'         => $d['motivo'] ?? '—',
        'fecha'          => $d['fecha'] ?? '—',
        'monto'          => number_format(floatval($d['monto'] ?? 0), 2),
        'venta_codigo'   => $d['venta_codigo'] ?? '—',
        'estado'         => $d['estado'] ?? 'PENDIENTE',
        'descripcion'    => $d['descripcion'] ?? '',
        'cantidad'       => $d['cantidad'] ?? 1,
    ];
}, $devoluciones)); ?>;

function verDetalles(id) {
    const d = devolucionesData.find(x => x.id == id);
    if (!d) { Toast.error('No se encontraron datos para esta devolución', 'Error'); return; }

    const estadoClass = {
        'aprobado' : 'status-aprobado',
        'rechazado': 'status-rechazado',
        'pendiente': 'status-pendiente'
    }[d.estado.toLowerCase()] || 'status-pendiente';

    // Crear modal si no existe
    let overlay = document.getElementById('devDetalleModal');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'devDetalleModal';
        overlay.className = 'modal-overlay';
        overlay.innerHTML = `
            <div style="background:#fff;border-radius:12px;width:580px;max-width:95%;max-height:90vh;overflow-y:auto;animation:slideUp 0.3s;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
                <div style="padding:18px 24px;border-bottom:1px solid #e9ecef;display:flex;justify-content:space-between;align-items:center;background:#1F9166;border-radius:12px 12px 0 0;">
                    <h3 style="margin:0;color:#fff;font-size:16px;display:flex;align-items:center;gap:8px;"><i class="fas fa-file-alt"></i> Detalle de Devolución</h3>
                    <button onclick="document.getElementById('devDetalleModal').classList.remove('active')" style="background:rgba(255,255,255,0.2);border:none;color:#fff;width:30px;height:30px;border-radius:50%;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
                </div>
                <div style="padding:24px;" id="devDetalleBody"></div>
                <div style="padding:16px 24px;border-top:1px solid #eaeaea;background:#f8f9fa;border-radius:0 0 12px 12px;display:flex;justify-content:flex-end;">
                    <button onclick="document.getElementById('devDetalleModal').classList.remove('active')" class="btn btn-secondary"><i class="fas fa-times"></i> Cerrar</button>
                </div>
            </div>`;
        overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('active'); });
        document.body.appendChild(overlay);
    }

    document.getElementById('devDetalleBody').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px 24px;">
            <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Código</div><div style="font-weight:700;color:#1F9166;">${d.codigo}</div></div>
            <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Estado</div><span class="status-badge status-${d.estado.toLowerCase()}">${d.estado.charAt(0)+d.estado.slice(1).toLowerCase()}</span></div>
            <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Cliente</div><div>${d.cliente}</div></div>
            <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Venta Referencia</div><div>${d.venta_codigo}</div></div>
            <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Producto</div><div>${d.producto}</div></div>
            <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Cantidad</div><div>${d.cantidad}</div></div>
            <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Motivo</div><div>${d.motivo}</div></div>
            <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Fecha</div><div>${d.fecha}</div></div>
            <div style="grid-column:1/-1;border-top:1px solid #edf2f7;padding-top:14px;"><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:4px;">Observaciones</div><div style="font-size:13px;color:#555;">${d.descripcion || 'Sin observaciones'}</div></div>
        </div>`;

    overlay.classList.add('active');
}

// ═══════════════════════════════════════════════════
// BÚSQUEDA (mejorada — incluye filtro combinado)
// ═══════════════════════════════════════════════════
function aplicarFiltros() {
    const term    = (document.getElementById('searchInput')?.value || '').toLowerCase();
    const estado  = (document.getElementById('estadoFilter')?.value || '').toLowerCase();
    const motivo  = (document.getElementById('motivoFilterDev')?.value || '').toLowerCase();
    const desde   = document.getElementById('fechaDesdeDev')?.value || '';
    const hasta   = document.getElementById('fechaHastaDev')?.value || '';

    document.querySelectorAll('.table-row').forEach(row => {
        if (!row.querySelector('.col-id')) return;

        const texto      = row.textContent.toLowerCase();
        const rowEstado  = (row.querySelector('.status-badge')?.textContent || '').toLowerCase();
        const rowMotivo  = (row.querySelector('.col-motivo')?.textContent || '').toLowerCase();
        // Fecha: extraída del texto de la celda col-fecha (formato DD/MM/YYYY)
        const fechaTxt   = (row.querySelector('.col-fecha')?.textContent || '').trim();
        let fechaISO     = '';
        if (fechaTxt && fechaTxt !== '—') {
            const p = fechaTxt.split('/');
            if (p.length === 3) fechaISO = `${p[2]}-${p[1]}-${p[0]}`;
        }

        let mostrar = true;
        if (term   && !texto.includes(term))                           mostrar = false;
        if (estado && !rowEstado.includes(estado))                     mostrar = false;
        if (motivo && !rowMotivo.includes(motivo))                     mostrar = false;
        if (desde  && fechaISO && fechaISO < desde)                    mostrar = false;
        if (hasta  && fechaISO && fechaISO > hasta)                    mostrar = false;

        row.style.display = mostrar ? 'flex' : 'none';
    });
}

document.getElementById('searchInput')?.addEventListener('keyup', aplicarFiltros);
document.getElementById('estadoFilter')?.addEventListener('change', aplicarFiltros);

// ═══════════════════════════════════════════════════
// FILTROS ADICIONALES DE MOTIVO Y FECHA
// (Se inyectan en la barra de filtros existente sin tocar el HTML)
// ═══════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    const filtersBar = document.querySelector('.search-filters');
    if (!filtersBar) return;

    // Agregar select de motivo
    const motivoSel = document.createElement('select');
    motivoSel.className = 'filter-select';
    motivoSel.id = 'motivoFilterDev';
    motivoSel.innerHTML = `
        <option value="">Todos los motivos</option>
        <option value="defectuoso">Producto Defectuoso</option>
        <option value="incorrecto">Producto Incorrecto</option>
        <option value="arrepentimiento">Arrepentimiento</option>
        <option value="dañado">Producto Dañado</option>
        <option value="otro">Otro</option>`;
    filtersBar.appendChild(motivoSel);

    // Agregar selector de rango de fecha (igual a pedidos)
    const dateFilterDev = document.createElement('select');
    dateFilterDev.className = 'filter-select';
    dateFilterDev.id = 'dateFilterDev';
    dateFilterDev.innerHTML = `
        <option value="">Todos los días</option>
        <option value="today">Hoy</option>
        <option value="yesterday">Ayer</option>
        <option value="week">Esta semana</option>
        <option value="last_week">Semana pasada</option>
        <option value="month">Este mes</option>
        <option value="last_month">Mes pasado</option>
        <option value="custom">Rango personalizado</option>
    `;
    filtersBar.appendChild(dateFilterDev);

    // Panel personalizado
    const customDateRangeDev = document.createElement('div');
    customDateRangeDev.id = 'customDateRangeDev';
    customDateRangeDev.style.cssText = 'display: none; width: 100%; margin-top: 10px; padding: 15px; background: #f8f9fa; border-radius: 6px; border: 1px solid #ddd;';
    customDateRangeDev.innerHTML = `
        <div style="display: flex; gap: 10px; align-items: center;">
            <label for="fechaDesdeDev" style="font-weight: 500; color: #333;">Desde:</label>
            <input type="date" id="fechaDesdeDev" class="filter-input" style="padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
            <label for="fechaHastaDev" style="font-weight: 500; color: #333;">Hasta:</label>
            <input type="date" id="fechaHastaDev" class="filter-input" style="padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
            <button class="btn btn-primary" id="applyDateBtnDev">Aplicar</button>
        </div>
    `;
    filtersBar.parentNode.insertBefore(customDateRangeDev, filtersBar.nextSibling);

    function applyDateRangeDev(range) {
        const customDateRange = document.getElementById('customDateRangeDev');
        const fechaDesde = document.getElementById('fechaDesdeDev');
        const fechaHasta = document.getElementById('fechaHastaDev');
        const hoy = new Date();
        const formatDate = d => d.toISOString().slice(0, 10);

        if (range === 'today') {
            fechaDesde.value = formatDate(hoy);
            fechaHasta.value = formatDate(hoy);
            customDateRange.style.display = 'none';
        } else if (range === 'yesterday') {
            const ayer = new Date(hoy);
            ayer.setDate(hoy.getDate() - 1);
            fechaDesde.value = formatDate(ayer);
            fechaHasta.value = formatDate(ayer);
            customDateRange.style.display = 'none';
        } else if (range === 'week') {
            const inicioSemana = new Date(hoy);
            inicioSemana.setDate(hoy.getDate() - hoy.getDay());
            fechaDesde.value = formatDate(inicioSemana);
            fechaHasta.value = formatDate(hoy);
            customDateRange.style.display = 'none';
        } else if (range === 'last_week') {
            const finSemanaPasada = new Date(hoy);
            finSemanaPasada.setDate(hoy.getDate() - hoy.getDay() - 1);
            const inicioSemanaPasada = new Date(finSemanaPasada);
            inicioSemanaPasada.setDate(finSemanaPasada.getDate() - 6);
            fechaDesde.value = formatDate(inicioSemanaPasada);
            fechaHasta.value = formatDate(finSemanaPasada);
            customDateRange.style.display = 'none';
        } else if (range === 'month') {
            const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            fechaDesde.value = formatDate(inicioMes);
            fechaHasta.value = formatDate(hoy);
            customDateRange.style.display = 'none';
        } else if (range === 'last_month') {
            const inicioMesPasado = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
            const finMesPasado = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
            fechaDesde.value = formatDate(inicioMesPasado);
            fechaHasta.value = formatDate(finMesPasado);
            customDateRange.style.display = 'none';
        } else if (range === 'custom') {
            customDateRange.style.display = 'block';
            return; // No aplicar filtro aún
        } else {
            fechaDesde.value = '';
            fechaHasta.value = '';
            customDateRange.style.display = 'none';
        }
        aplicarFiltros();
    }

    dateFilterDev.addEventListener('change', function() {
        applyDateRangeDev(this.value);
    });
    document.getElementById('applyDateBtnDev')?.addEventListener('click', aplicarFiltros);
    document.getElementById('fechaDesdeDev')?.addEventListener('change', aplicarFiltros);
    document.getElementById('fechaHastaDev')?.addEventListener('change', aplicarFiltros);
});



function obtenerSeleccionadas() { return []; }
</script>

</body>
</html>