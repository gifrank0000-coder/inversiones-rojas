<?php
// ============================================================
// panel_notificaciones.php  →  app/views/pages/panel_notificaciones.php
// Panel del vendedor: notificaciones + pedidos asignados
// ============================================================
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../models/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/app/views/auth/Login.php');
    exit;
}

$usuario_id   = (int)$_SESSION['user_id'];
$usuario_nombre = $_SESSION['user_name'] ?? 'Vendedor';

// Cargar pedidos asignados a este vendedor
$pedidos_asignados = [];
$stats = ['pendientes'=>0,'en_proceso'=>0,'completados'=>0,'total'=>0];

try {
    $db   = new Database();
    $conn = $db->getConnection();

    // Crear tabla si no existe
    $conn->exec("CREATE TABLE IF NOT EXISTS notificaciones_vendedor (
        id SERIAL PRIMARY KEY, pedido_id INT NOT NULL,
        titulo VARCHAR(200) NOT NULL, mensaje TEXT,
        tipo VARCHAR(40) DEFAULT 'pedido', leida BOOLEAN DEFAULT false,
        usuario_id INT, created_at TIMESTAMPTZ DEFAULT NOW()
    )");

    // Asegurar columna vendedor_asignado_id en pedidos_online
    try {
        $conn->exec("ALTER TABLE pedidos_online ADD COLUMN IF NOT EXISTS vendedor_asignado_id INT");
        $conn->exec("ALTER TABLE pedidos_online ADD COLUMN IF NOT EXISTS fecha_asignacion TIMESTAMPTZ");
    } catch(Exception $e) {}

    // Pedidos asignados a este vendedor
    $stmt = $conn->prepare(
        "SELECT p.id, p.codigo_pedido, p.estado_pedido, p.total,
                p.created_at, p.fecha_asignacion, p.telefono_contacto,
                p.tipo_entrega, p.direccion_entrega,
                c.nombre_completo AS cliente_nombre, c.email AS cliente_email,
                COUNT(d.id) AS num_items,
                STRING_AGG(pr.nombre || ' x' || d.cantidad, ', ' ORDER BY d.id) AS productos
         FROM pedidos_online p
         LEFT JOIN clientes c ON p.cliente_id = c.id
         LEFT JOIN detalle_pedidos_online d ON d.pedido_id = p.id
         LEFT JOIN productos pr ON pr.id = d.producto_id
         WHERE p.vendedor_asignado_id = ?
         GROUP BY p.id, c.nombre_completo, c.email
         ORDER BY p.created_at DESC
         LIMIT 100"
    );
    $stmt->execute([$usuario_id]);
    $pedidos_asignados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    foreach ($pedidos_asignados as $p) {
        $stats['total']++;
        $e = strtoupper($p['estado_pedido']);
        if (in_array($e, ['PENDIENTE','EN_VERIFICACION'])) $stats['pendientes']++;
        elseif (in_array($e, ['CONFIRMADO','EN_PROCESO','ENVIADO','ENTREGADO'])) $stats['en_proceso']++;
        elseif (in_array($e, ['COMPLETADO'])) $stats['completados']++;
    }

} catch(Exception $e) {
    error_log('[panel_notificaciones] '.$e->getMessage());
}

$statusLabels = [
    'PENDIENTE'=>'Pendiente','EN_VERIFICACION'=>'En Verificación',
    'CONFIRMADO'=>'Confirmado','EN_PROCESO'=>'En Proceso',
    'ENVIADO'=>'Enviado','ENTREGADO'=>'Entregado',
    'COMPLETADO'=>'Completado','RECHAZADO'=>'Rechazado','CANCELADO'=>'Cancelado',
];
$statusColors = [
    'PENDIENTE'=>'#c17f00','EN_VERIFICACION'=>'#2c7be5','CONFIRMADO'=>'#1F9166',
    'EN_PROCESO'=>'#7b2ff7','ENVIADO'=>'#0097b2','ENTREGADO'=>'#0a7c45',
    'COMPLETADO'=>'#1565c0','RECHAZADO'=>'#c0392b','CANCELADO'=>'#888',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Notificaciones - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; color: #333; }

        .panel-header {
            background: #1F9166; color: white;
            padding: 18px 24px;
            display: flex; align-items: center; gap: 14px;
        }
        .panel-header h1 { font-size: 1.3rem; font-weight: 700; }
        .panel-header span { font-size: 0.9rem; opacity: 0.85; }

        .panel-body { max-width: 1200px; margin: 0 auto; padding: 24px; }

        /* Stats */
        .stats-grid {
            display: grid; grid-template-columns: repeat(4,1fr); gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white; border-radius: 10px; padding: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            display: flex; align-items: center; gap: 14px;
            border-left: 4px solid #1F9166;
        }
        .stat-card:nth-child(2) { border-left-color: #c17f00; }
        .stat-card:nth-child(3) { border-left-color: #7b2ff7; }
        .stat-card:nth-child(4) { border-left-color: #1565c0; }
        .stat-icon { width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px; }
        .stat-card:nth-child(1) .stat-icon { background:#e8f6f1;color:#1F9166; }
        .stat-card:nth-child(2) .stat-icon { background:#fff7e6;color:#c17f00; }
        .stat-card:nth-child(3) .stat-icon { background:#f0e8ff;color:#7b2ff7; }
        .stat-card:nth-child(4) .stat-icon { background:#eaf4fe;color:#1565c0; }
        .stat-num { font-size: 1.8rem; font-weight: 700; color: #333; }
        .stat-label { font-size: 12px; color: #888; margin-top: 2px; }

        /* Secciones */
        .section { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.07); margin-bottom:24px; overflow:hidden; }
        .section-header {
            padding: 16px 20px; border-bottom: 1px solid #f0f0f0;
            display: flex; align-items: center; justify-content: space-between;
        }
        .section-header h2 { font-size: 15px; font-weight: 600; display:flex;align-items:center;gap:8px; }
        .badge-count {
            background:#e74c3c;color:white;font-size:11px;font-weight:700;
            padding:2px 7px;border-radius:12px;min-width:20px;text-align:center;
        }

        /* Notificaciones */
        .notif-list { max-height: 340px; overflow-y: auto; }
        .notif-item {
            padding: 14px 20px; border-bottom: 1px solid #f5f5f5;
            display: flex; gap: 12px; align-items: flex-start;
            transition: background 0.15s; cursor: pointer;
        }
        .notif-item:hover { background: #fafafa; }
        .notif-item.no-leida { background: #f0f9f4; border-left: 3px solid #1F9166; }
        .notif-item.no-leida:hover { background: #e8f6f1; }
        .notif-icon {
            width: 36px; height: 36px; border-radius: 50%;
            background: #e8f6f1; color: #1F9166;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; flex-shrink: 0;
        }
        .notif-icon.tipo-asignado { background:#e8f6f1;color:#1F9166; }
        .notif-icon.tipo-pedido   { background:#fff7e6;color:#c17f00; }
        .notif-titulo  { font-size: 13px; font-weight: 600; color: #333; }
        .notif-mensaje { font-size: 12px; color: #666; margin-top: 2px; }
        .notif-hace    { font-size: 11px; color: #aaa; margin-top: 3px; }
        .notif-empty   { padding: 30px; text-align: center; color: #aaa; font-size: 13px; }

        /* Tabla de pedidos */
        .pedidos-table { overflow-x: auto; }
        .table-header, .table-row {
            display: grid;
            grid-template-columns: 160px 160px 200px 90px 100px 120px 130px;
            gap: 8px; padding: 11px 16px; align-items: center; font-size: 13px;
            min-width: 1000px;
        }
        .table-header { background:#f8f9fa; font-weight:600; color:#555; border-bottom:2px solid #e0e0e0; }
        .table-row { border-bottom:1px solid #f0f0f0; }
        .table-row:hover { background:#fafafa; }
        .status-badge {
            padding:4px 10px; border-radius:12px; font-size:11px; font-weight:600;
            display:inline-block; white-space:nowrap;
        }
        .btn-accion {
            padding: 5px 10px; border: none; border-radius: 6px;
            font-size: 12px; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 5px;
            transition: all 0.2s;
        }
        .btn-ver   { background:#e8f3ff;color:#2c7be5; }
        .btn-ver:hover { background:#d0e6fd; }
        .btn-accion:disabled { opacity:0.4; cursor:not-allowed; }
        .cell-sub  { font-size:11px; color:#aaa; margin-top:2px; }

        /* Modal detalle */
        .modal-overlay { display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;justify-content:center;align-items:center; }
        .modal-overlay.active { display:flex; }
        .modal { background:white;border-radius:12px;max-width:520px;width:90%;padding:26px;max-height:90vh;overflow-y:auto;animation:slideDown 0.2s ease; }
        @keyframes slideDown { from{opacity:0;transform:translateY(-16px)} to{opacity:1;transform:translateY(0)} }
        .modal-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:18px; }
        .modal-header h3 { font-size:16px;font-weight:700; }
        .btn-close { background:none;border:none;font-size:22px;cursor:pointer;color:#999; }
        .info-box { background:#f8f9fa;border-left:4px solid #1F9166;border-radius:6px;padding:12px;margin-bottom:14px;font-size:13px; }
        .info-box p { margin:3px 0; }

        @media(max-width:768px) {
            .stats-grid { grid-template-columns:repeat(2,1fr); }
        }
    </style>
</head>
<body>

<div class="panel-header">
    <i class="fas fa-bell" style="font-size:1.4rem;"></i>
    <div>
        <h1>Panel de Notificaciones</h1>
        <span>Bienvenido, <?php echo htmlspecialchars($usuario_nombre); ?> — tus pedidos asignados</span>
    </div>
    <div style="margin-left:auto;">
        <button onclick="marcarTodasLeidas()" style="background:rgba(255,255,255,0.2);color:white;border:1px solid rgba(255,255,255,0.4);padding:7px 14px;border-radius:6px;cursor:pointer;font-size:13px;">
            <i class="fas fa-check-double"></i> Marcar todas como leídas
        </button>
    </div>
</div>

<div class="panel-body">

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-list"></i></div>
            <div><div class="stat-num"><?php echo $stats['total']; ?></div><div class="stat-label">Total Asignados</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div><div class="stat-num"><?php echo $stats['pendientes']; ?></div><div class="stat-label">Pendientes</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-spinner"></i></div>
            <div><div class="stat-num"><?php echo $stats['en_proceso']; ?></div><div class="stat-label">En Gestión</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div><div class="stat-num"><?php echo $stats['completados']; ?></div><div class="stat-label">Completados</div></div>
        </div>
    </div>

    <!-- Notificaciones recientes -->
    <div class="section">
        <div class="section-header">
            <h2><i class="fas fa-bell" style="color:#1F9166;"></i> Notificaciones <span class="badge-count" id="badgeCount">0</span></h2>
            <button onclick="recargarNotifs()" style="background:none;border:none;color:#1F9166;cursor:pointer;font-size:13px;"><i class="fas fa-sync-alt"></i> Actualizar</button>
        </div>
        <div class="notif-list" id="notifList">
            <div class="notif-empty"><i class="fas fa-bell-slash" style="font-size:1.5rem;margin-bottom:8px;display:block;"></i>Cargando notificaciones...</div>
        </div>
    </div>

    <!-- Pedidos asignados -->
    <div class="section">
        <div class="section-header">
            <h2><i class="fas fa-shopping-bag" style="color:#1F9166;"></i> Mis Pedidos Asignados (<?php echo count($pedidos_asignados); ?>)</h2>
        </div>
        <div class="pedidos-table">
            <div class="table-header">
                <div>Código</div>
                <div>Cliente</div>
                <div>Productos</div>
                <div>Total</div>
                <div>Fecha</div>
                <div>Estado</div>
                <div>Acciones</div>
            </div>
            <?php if (empty($pedidos_asignados)): ?>
                <div style="padding:30px;text-align:center;color:#aaa;font-size:13px;">
                    <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>
                    No tienes pedidos asignados aún
                </div>
            <?php else: ?>
                <?php foreach ($pedidos_asignados as $p):
                    $estado = strtoupper($p['estado_pedido'] ?? 'PENDIENTE');
                    $label  = $statusLabels[$estado] ?? $estado;
                    $color  = $statusColors[$estado]  ?? '#888';
                    $fecha  = !empty($p['created_at']) ? date('d/m/Y H:i', strtotime($p['created_at'])) : '—';
                    $total  = number_format((float)$p['total'], 2);
                ?>
                <div class="table-row">
                    <div>
                        <strong><?php echo htmlspecialchars($p['codigo_pedido']); ?></strong>
                        <div class="cell-sub"><?php echo htmlspecialchars($p['telefono_contacto'] ?? ''); ?></div>
                    </div>
                    <div>
                        <?php echo htmlspecialchars($p['cliente_nombre'] ?? '—'); ?>
                        <div class="cell-sub"><?php echo htmlspecialchars($p['cliente_email'] ?? ''); ?></div>
                    </div>
                    <div style="font-size:12px;"><?php echo htmlspecialchars($p['productos'] ?: $p['num_items'].' producto(s)'); ?></div>
                    <div><strong>Bs <?php echo $total; ?></strong></div>
                    <div><?php echo $fecha; ?></div>
                    <div>
                        <span class="status-badge" style="background:<?php echo $color; ?>20;color:<?php echo $color; ?>;">
                            <?php echo $label; ?>
                        </span>
                    </div>
                    <div>
                        <button class="btn-accion btn-ver"
                                onclick="verDetalle(<?php echo $p['id']; ?>,'<?php echo htmlspecialchars($p['codigo_pedido']); ?>')">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /panel-body -->

<!-- Modal de detalle de pedido -->
<div class="modal-overlay" id="modalDetalle">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-receipt" style="color:#1F9166;margin-right:6px;"></i> Detalle del Pedido</h3>
            <button class="btn-close" onclick="cerrarModal()">&times;</button>
        </div>
        <div id="detalleBody"><p style="color:#aaa;text-align:center;">Cargando...</p></div>
        <div style="margin-top:16px;text-align:right;">
            <button onclick="cerrarModal()" style="padding:8px 18px;background:#f0f0f0;border:none;border-radius:6px;cursor:pointer;font-size:13px;">Cerrar</button>
        </div>
    </div>
</div>

<script>
const BASE = '<?php echo BASE_URL; ?>';

// ── Cargar notificaciones ─────────────────────────────────────
async function recargarNotifs() {
    try {
        const r = await fetch(`${BASE}/api/get_notificaciones.php?accion=listar`);
        const j = await r.json();
        const lista = document.getElementById('notifList');
        const badge = document.getElementById('badgeCount');

        badge.textContent = j.total_no_leidas || 0;
        badge.style.display = j.total_no_leidas > 0 ? 'inline-block' : 'none';

        if (!j.notificaciones || j.notificaciones.length === 0) {
            lista.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>No hay notificaciones</div>';
            return;
        }

        lista.innerHTML = j.notificaciones.map(n => `
            <div class="notif-item ${n.leida ? '' : 'no-leida'}" onclick="marcarLeida(${n.id}, this)">
                <div class="notif-icon tipo-${n.tipo === 'PEDIDO_ASIGNADO' ? 'asignado' : 'pedido'}">
                    <i class="fas ${n.tipo === 'PEDIDO_ASIGNADO' ? 'fa-user-plus' : 'fa-shopping-bag'}"></i>
                </div>
                <div style="flex:1;">
                    <div class="notif-titulo">${n.titulo}</div>
                    <div class="notif-mensaje">${n.mensaje || ''}</div>
                    ${n.codigo_pedido ? `<div class="notif-hace">Pedido: <strong>${n.codigo_pedido}</strong> • ${n.hace}</div>` : `<div class="notif-hace">${n.hace}</div>`}
                </div>
                ${!n.leida ? '<span style="width:8px;height:8px;border-radius:50%;background:#1F9166;flex-shrink:0;margin-top:5px;display:inline-block;"></span>' : ''}
            </div>
        `).join('');
    } catch(e) {
        console.error('Error cargando notificaciones:', e);
    }
}

async function marcarLeida(id, el) {
    el.classList.remove('no-leida');
    const dot = el.querySelector('span[style*="border-radius:50%"]');
    if (dot) dot.remove();
    await fetch(`${BASE}/api/get_notificaciones.php?accion=marcar_leida&id=${id}`);
    recargarNotifs();
}

async function marcarTodasLeidas() {
    await fetch(`${BASE}/api/get_notificaciones.php?accion=marcar_todas`);
    recargarNotifs();
}

// ── Detalle de pedido ─────────────────────────────────────────
async function verDetalle(pedidoId, codigo) {
    document.getElementById('modalDetalle').classList.add('active');
    document.getElementById('detalleBody').innerHTML = '<p style="color:#aaa;text-align:center;padding:20px;">Cargando...</p>';
    try {
        const r = await fetch(`${BASE}/api/get_pedido_detalle.php?id=${pedidoId}`);
        const j = await r.json();
        if (j.success) {
            const p = j.pedido, items = j.items || [];
            const rows = items.map(i =>
                `<tr>
                    <td style="padding:6px 8px;border-bottom:1px solid #f0f0f0;">${i.nombre}</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #f0f0f0;text-align:center;">${i.cantidad}</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #f0f0f0;text-align:right;">Bs ${parseFloat(i.precio_unitario).toFixed(2)}</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #f0f0f0;text-align:right;">Bs ${parseFloat(i.subtotal).toFixed(2)}</td>
                </tr>`
            ).join('');
            document.getElementById('detalleBody').innerHTML = `
                <div class="info-box">
                    <p>📋 <strong>Código:</strong> ${p.codigo_pedido}</p>
                    <p>👤 <strong>Cliente:</strong> ${p.cliente_nombre || '—'}</p>
                    <p>📞 <strong>Teléfono:</strong> ${p.telefono_contacto || '—'}</p>
                    <p>📦 <strong>Entrega:</strong> ${p.tipo_entrega === 'domicilio' ? 'Delivery — '+p.direccion_entrega : 'Retiro en tienda'}</p>
                    ${p.observaciones ? `<p>📝 <strong>Notas:</strong> ${p.observaciones}</p>` : ''}
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead><tr style="background:#1F9166;color:white;">
                        <th style="padding:8px;text-align:left;">Producto</th>
                        <th style="padding:8px;text-align:center;">Cant.</th>
                        <th style="padding:8px;text-align:right;">Precio</th>
                        <th style="padding:8px;text-align:right;">Subtotal</th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                </table>
                <div style="border-top:2px dashed #1F9166;margin-top:10px;padding-top:10px;display:flex;justify-content:space-between;font-weight:700;font-size:1.05rem;">
                    <span>TOTAL</span>
                    <span style="color:#1F9166;">Bs ${parseFloat(p.total).toFixed(2)}</span>
                </div>
                ${p.telefono_contacto ? `
                <div style="margin-top:14px;">
                    <a href="https://wa.me/${p.telefono_contacto.replace(/\D/g,'')}" target="_blank"
                       style="display:flex;align-items:center;justify-content:center;gap:8px;background:#25D366;color:white;text-decoration:none;padding:10px;border-radius:8px;font-weight:700;font-size:14px;">
                        <i class="fab fa-whatsapp" style="font-size:1.2rem;"></i>
                        Contactar cliente por WhatsApp
                    </a>
                </div>` : ''}`;
        } else {
            document.getElementById('detalleBody').innerHTML = '<p style="color:#e74c3c;">Error al cargar el detalle.</p>';
        }
    } catch(e) {
        document.getElementById('detalleBody').innerHTML = '<p style="color:#e74c3c;">Error de conexión.</p>';
    }
}

function cerrarModal() {
    document.getElementById('modalDetalle').classList.remove('active');
}

// Cargar al inicio y cada 60 segundos
recargarNotifs();
setInterval(recargarNotifs, 60000);
</script>
</body>
</html>