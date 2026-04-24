<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/app/views/auth/Login.php');
    exit();
}

$user_id   = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Cliente';
$debug     = [];

$cliente_id = null;
$conn       = null;

try {
    $db   = new Database();
    $conn = $db->getConnection();

    // M1: clientes.usuario_id
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE usuario_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $cliente_id = $stmt->fetchColumn() ?: null;
    $debug[] = "M1 usuario_id={$user_id} → cliente_id=" . ($cliente_id ?? 'NULL');

    // M2: usuarios.cliente_id
    if (!$cliente_id) {
        $stmt = $conn->prepare("SELECT cliente_id FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $val = $stmt->fetchColumn();
        if ($val) { $cliente_id = (int)$val; }
        $debug[] = "M2 usuarios.cliente_id → " . ($cliente_id ?? 'NULL');
    }

    // M3: por email
    if (!$cliente_id) {
        $email = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';
        if ($email) {
            $stmt = $conn->prepare("SELECT id FROM clientes WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $val = $stmt->fetchColumn();
            if ($val) $cliente_id = (int)$val;
        }
        $debug[] = "M3 email=" . ($email ?: 'vacío') . " → " . ($cliente_id ?? 'NULL');
    }

    // M4: por nombre
    if (!$cliente_id) {
        $stmt = $conn->prepare("SELECT id FROM clientes WHERE LOWER(nombre_completo) = LOWER(?) LIMIT 1");
        $stmt->execute([$user_name]);
        $val = $stmt->fetchColumn();
        if ($val) $cliente_id = (int)$val;
        $debug[] = "M4 nombre={$user_name} → " . ($cliente_id ?? 'NULL');
    }

} catch (Exception $e) {
    $debug[] = "ERROR BD: " . $e->getMessage();
}

$pedidos = [];
if ($cliente_id && $conn) {
    try {
        $stmt = $conn->prepare(
            "SELECT p.id, p.codigo_pedido, p.created_at, p.estado_pedido, p.total,
                    COALESCE(p.canal_comunicacion,'web') AS canal_comunicacion,
                    p.vendedor_asignado_id,
                    u.nombre_completo AS vendedor_nombre
             FROM pedidos_online p
             LEFT JOIN usuarios u ON p.vendedor_asignado_id = u.id
             WHERE p.cliente_id = ?
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([$cliente_id]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $debug[] = "Query OK → " . count($pedidos) . " pedido(s)";
    } catch (Exception $e) {
        $debug[] = "ERROR query: " . $e->getMessage();
    }
} else {
    $debug[] = "Query NO ejecutado. cliente_id=" . ($cliente_id ?? 'NULL');
}

$statusLabels = [
    'PENDIENTE'=>'Pendiente','EN_VERIFICACION'=>'En verificación',
    'CONFIRMADO'=>'Confirmado','RECHAZADO'=>'Rechazado',
    'EN_PROCESO'=>'En proceso','ENVIADO'=>'Enviado',
    'ENTREGADO'=>'Entregado','COMPLETADO'=>'Completado',
    'CANCELADO'=>'Cancelado','INHABILITADO'=>'Inhabilitado',
];
$statusClasses = [
    'PENDIENTE'=>'status-pending','EN_VERIFICACION'=>'status-verification',
    'CONFIRMADO'=>'status-confirmed','RECHAZADO'=>'status-inhabilitado',
    'EN_PROCESO'=>'status-preparing','ENVIADO'=>'status-shipped',
    'ENTREGADO'=>'status-delivered','COMPLETADO'=>'status-completed',
    'CANCELADO'=>'status-cancelled','INHABILITADO'=>'status-inhabilitado',
];
$canalMap = [
    'whatsapp'       => ['label'=>'WhatsApp',    'icon'=>'fab fa-whatsapp',  'color'=>'#25D366'],
    'email'          => ['label'=>'Email',        'icon'=>'fas fa-envelope',  'color'=>'#d93025'],
    'telegram'       => ['label'=>'Telegram',     'icon'=>'fab fa-telegram',  'color'=>'#0088cc'],
    'notificaciones' => ['label'=>'Notificación', 'icon'=>'fas fa-bell',       'color'=>'#f39c12'],
    'web'            => ['label'=>'Web',          'icon'=>'fas fa-globe',      'color'=>'#1F9166'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/components/user-panel.css">
    <style>
        /* Estilos para moneda dual */
        .moneda-bs { color: #1F9166; font-weight: 700; }
        .moneda-usd { color: #6c757d; font-size: 0.85em; }
        
        .page-header{padding:20px 24px;background:#1F9166;color:white;}
        .page-header h1{margin:0;font-size:1.45rem;}
        .page-header p{margin:6px 0 0;font-size:.9rem;opacity:.9;}
        .content{max-width:1100px;margin:0 auto;padding:24px;}
        .card{background:white;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.06);padding:20px;margin-bottom:18px;}
        .debug-box{background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:14px;margin-bottom:16px;font-size:12px;font-family:monospace;color:#5d4037;}
        .debug-box h4{margin:0 0 6px;}
        table{width:100%;border-collapse:collapse;}
        thead th{text-align:left;padding:11px 14px;font-weight:700;color:#444;border-bottom:2px solid #f0f0f0;}
        tbody tr{border-bottom:1px solid #f1f1f1;}
        tbody tr:hover{background:#fafafa;}
        td{padding:11px 14px;vertical-align:middle;font-size:13px;}
        .btn{padding:8px 14px;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:13px;}
        .btn-primary{background:#1F9166;color:white;}
        .btn-primary:hover{background:#187a54;}
        .btn-secondary{background:#f0f0f0;color:#555;}
        .status-badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block;}
        .status-pending{background:#fff3cd;color:#856404;}
        .status-verification{background:#fff0f0;color:#c0392b;border:1px solid #f5c6cb;}
        .status-confirmed{background:#d4edda;color:#155724;}
        .status-preparing{background:#cce5ff;color:#004085;}
        .status-shipped{background:#d1ecf1;color:#0c5460;}
        .status-delivered{background:#e0d4f5;color:#5e2e9e;}
        .status-completed{background:#d1e7dd;color:#0a3622;}
        .status-cancelled,.status-inhabilitado{background:#f8d7da;color:#721c24;}
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);justify-content:center;align-items:center;z-index:9999;}
        .modal-overlay.active{display:flex;}
        .modal{background:white;border-radius:12px;padding:22px;width:100%;max-width:460px;}
        .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;}
        .modal-close{background:none;border:none;font-size:20px;cursor:pointer;color:#666;}
        .modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:16px;}
    </style>
</head>
<body>
    <?php require __DIR__ . '/partials/header.php'; ?>

    <div class="page-header">
        <h1><i class="fas fa-shopping-bag"></i> Mis Pedidos Online</h1>
        <p>Consulta el estado de tus pedidos. Los pedidos en <strong>Verificación</strong> ya tienen tu comprobante y serán confirmados a la brevedad.</p>
    </div>

    <div class="content">


        <div class="card">
            <?php if (!$cliente_id): ?>
                <div style="text-align:center;padding:40px;color:#666;">
                    <i class="fas fa-user-slash" style="font-size:2rem;color:#ddd;display:block;margin-bottom:12px;"></i>
                    <p><strong>No se encontró tu perfil de cliente.</strong></p>
                    <p style="font-size:13px;margin-top:6px;">Contacta al administrador.</p>
                </div>
            <?php elseif (empty($pedidos)): ?>
                <div style="text-align:center;padding:40px;color:#666;">
                    <i class="fas fa-shopping-bag" style="font-size:2rem;color:#ddd;display:block;margin-bottom:12px;"></i>
                    <p><strong>No tienes pedidos registrados.</strong></p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Pedido</th><th>Canal</th><th>Fecha</th>
                                <th>Total</th><th>Estado</th><th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pedidos as $p):
                            $fecha    = $p['created_at'] ? date('d/m/Y H:i', strtotime($p['created_at'])) : '';
                            $estado   = strtoupper($p['estado_pedido'] ?? 'PENDIENTE');
                            $lEst     = $statusLabels[$estado] ?? $estado;
                            $cEst     = $statusClasses[$estado] ?? 'status-pending';
                            $canPay   = ($estado === 'PENDIENTE');
                            $cRaw     = strtolower($p['canal_comunicacion'] ?? 'web');
                            $cInfo    = $canalMap[$cRaw] ?? $canalMap['web'];
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($p['codigo_pedido']); ?></strong></td>
                            <td><span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:<?php echo $cInfo['color']; ?>;"><i class="<?php echo $cInfo['icon']; ?>"></i><?php echo $cInfo['label']; ?></span></td>
                            <td><?php echo $fecha; ?></td>
                            <?php $precios = formatearMonedaDual((float)$p['total']); ?>
                            <td><strong><span class="moneda-bs"><?php echo $precios['bs']; ?></span> <span class="moneda-usd">(<?php echo $precios['usd']; ?>)</span></strong></td>
                            <td><span class="status-badge <?php echo $cEst; ?>"><?php echo $lEst; ?></span></td>
                            <td>
                                <?php if ($canPay): ?>
                                    <button class="btn btn-primary" data-action="mark_paid" data-id="<?php echo $p['id']; ?>">
                                        <i class="fas fa-credit-card"></i> Marcar pago
                                    </button>
                                <?php elseif ($estado === 'EN_VERIFICACION'): ?>
                                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#d63031;font-weight:600;background:#fff5f5;padding:6px 10px;border-radius:20px;border:1px solid #f5c6cb;">
                                        <i class="fas fa-clock"></i> Verificando tu pago...
                                    </span>
                                <?php elseif ($estado === 'CONFIRMADO'): ?>
                                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#155724;font-weight:600;background:#d4edda;padding:6px 10px;border-radius:20px;">
                                        <i class="fas fa-check-circle"></i> Pago confirmado
                                    </span>
                                <?php else: ?>
                                    <span class="btn btn-secondary" style="cursor:default;"><?php echo $lEst; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal-overlay" id="modalMarkPaid">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-credit-card" style="color:#1F9166;margin-right:6px;"></i>Informar Pago</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>

            <p style="font-size:13px;color:#555;margin-bottom:16px;">
                Selecciona el método y escribe la referencia del pago para que el vendedor lo verifique.
            </p>

            <!-- Tipo de pago -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
                <label id="tipoPagoMovil" style="border:2px solid #e0e0e0;border-radius:10px;padding:12px;cursor:pointer;text-align:center;transition:all 0.2s;" onclick="seleccionarTipoPago('movil')">
                    <i class="fas fa-mobile-alt" style="font-size:1.4rem;color:#1F9166;display:block;margin-bottom:6px;"></i>
                    <span style="font-size:13px;font-weight:600;">Pago Móvil</span>
                    <div style="font-size:11px;color:#888;margin-top:2px;">Banco, cédula, teléfono</div>
                </label>
                <label id="tipoPagoTransferencia" style="border:2px solid #e0e0e0;border-radius:10px;padding:12px;cursor:pointer;text-align:center;transition:all 0.2s;" onclick="seleccionarTipoPago('transferencia')">
                    <i class="fas fa-university" style="font-size:1.4rem;color:#2c7be5;display:block;margin-bottom:6px;"></i>
                    <span style="font-size:13px;font-weight:600;">Transferencia</span>
                    <div style="font-size:11px;color:#888;margin-top:2px;">Número de referencia</div>
                </label>
            </div>
            <input type="hidden" id="tipoPagoSeleccionado" value="">

            <!-- Campos según tipo -->
            <div id="camposPagoMovil" style="display:none;">
                <div style="margin-bottom:10px;">
                    <label style="font-size:12px;font-weight:600;color:#333;display:block;margin-bottom:4px;">Banco emisor</label>
                    <input type="text" id="pagoMovilBanco" placeholder="Ej: Banesco, Mercantil, BNC..."
                           style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div style="margin-bottom:10px;">
                    <label style="font-size:12px;font-weight:600;color:#333;display:block;margin-bottom:4px;">Teléfono origen</label>
                    <input type="text" id="pagoMovilTelefono" placeholder="Ej: 0412-1234567"
                           style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div style="margin-bottom:10px;">
                    <label style="font-size:12px;font-weight:600;color:#333;display:block;margin-bottom:4px;">Referencia / Confirmación <span style="color:#e74c3c;">*</span></label>
                    <input type="text" id="pagoMovilRef" placeholder="Número de confirmación del pago"
                           style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
            </div>

            <div id="camposTransferencia" style="display:none;">
                <div style="margin-bottom:10px;">
                    <label style="font-size:12px;font-weight:600;color:#333;display:block;margin-bottom:4px;">Banco emisor</label>
                    <input type="text" id="transfBanco" placeholder="Ej: Banesco, Mercantil, BNC..."
                           style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div style="margin-bottom:10px;">
                    <label style="font-size:12px;font-weight:600;color:#333;display:block;margin-bottom:4px;">Número de referencia <span style="color:#e74c3c;">*</span></label>
                    <input type="text" id="transfRef" placeholder="Ej: 00123456789"
                           style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
                <div style="margin-bottom:10px;">
                    <label style="font-size:12px;font-weight:600;color:#333;display:block;margin-bottom:4px;">Monto transferido (Bs)</label>
                    <input type="number" id="transfMonto" placeholder="Ej: 46.40" step="0.01"
                           style="width:100%;padding:9px;border:1px solid #ddd;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
            </div>

            <div id="pagoError" style="display:none;color:#e74c3c;font-size:12px;margin-bottom:10px;"></div>

            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button class="btn btn-primary" id="confirmMarkPaid">
                    <i class="fas fa-paper-plane"></i> Enviar comprobante
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentPedidoId = null;
        const API = '<?php echo rtrim(BASE_URL,"/"); ?>/api/update_pedido_estado.php';

        document.querySelectorAll('[data-action="mark_paid"]').forEach(btn => {
            btn.addEventListener('click', function() {
                currentPedidoId = this.dataset.id;
                // Reset modal
                seleccionarTipoPago(null);
                document.getElementById('pagoError').style.display = 'none';
                ['pagoMovilBanco','pagoMovilTelefono','pagoMovilRef',
                 'transfBanco','transfRef','transfMonto'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.value = '';
                });
                document.getElementById('modalMarkPaid').classList.add('active');
            });
        });

        function seleccionarTipoPago(tipo) {
            document.getElementById('tipoPagoSeleccionado').value = tipo || '';
            const movil  = document.getElementById('tipoPagoMovil');
            const transf = document.getElementById('tipoPagoTransferencia');
            const cMovil = document.getElementById('camposPagoMovil');
            const cTransf= document.getElementById('camposTransferencia');

            // Reset estilos
            movil.style.borderColor  = '#e0e0e0';
            transf.style.borderColor = '#e0e0e0';
            movil.style.background   = '#fafafa';
            transf.style.background  = '#fafafa';
            cMovil.style.display  = 'none';
            cTransf.style.display = 'none';

            if (tipo === 'movil') {
                movil.style.borderColor = '#1F9166';
                movil.style.background  = '#f0f9f4';
                cMovil.style.display    = 'block';
            } else if (tipo === 'transferencia') {
                transf.style.borderColor = '#2c7be5';
                transf.style.background  = '#f0f5ff';
                cTransf.style.display    = 'block';
            }
        }

        function closeModal() {
            document.getElementById('modalMarkPaid').classList.remove('active');
            currentPedidoId = null;
        }

        document.getElementById('confirmMarkPaid').addEventListener('click', async function() {
            const tipo = document.getElementById('tipoPagoSeleccionado').value;
            const errEl = document.getElementById('pagoError');
            errEl.style.display = 'none';

            if (!tipo) {
                errEl.textContent = 'Selecciona el método de pago (Pago Móvil o Transferencia)';
                errEl.style.display = 'block';
                return;
            }

            let referencia = '';
            if (tipo === 'movil') {
                const banco  = document.getElementById('pagoMovilBanco').value.trim();
                const tel    = document.getElementById('pagoMovilTelefono').value.trim();
                const ref    = document.getElementById('pagoMovilRef').value.trim();
                if (!ref) {
                    errEl.textContent = 'El número de confirmación es obligatorio';
                    errEl.style.display = 'block';
                    return;
                }
                referencia = `Pago Móvil` + (banco ? ` | Banco: ${banco}` : '') + (tel ? ` | Tel: ${tel}` : '') + ` | Ref: ${ref}`;
            } else {
                const banco = document.getElementById('transfBanco').value.trim();
                const ref   = document.getElementById('transfRef').value.trim();
                const monto = document.getElementById('transfMonto').value.trim();
                if (!ref) {
                    errEl.textContent = 'El número de referencia es obligatorio';
                    errEl.style.display = 'block';
                    return;
                }
                referencia = `Transferencia` + (banco ? ` | Banco: ${banco}` : '') + ` | Ref: ${ref}` + (monto ? ` | Monto: Bs ${monto}` : '');
            }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            try {
                const r = await fetch(API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        pedido_id: currentPedidoId,
                        action: 'upload_proof',
                        payment_reference: referencia
                    })
                });
                const d = await r.json();
                if (d.success) {
                    closeModal();
                    // Mostrar mensaje de éxito antes de recargar
                    alert('✅ Comprobante enviado. El vendedor verificará tu pago a la brevedad.');
                    location.reload();
                } else {
                    errEl.textContent = d.message || 'Error al procesar. Intenta de nuevo.';
                    errEl.style.display = 'block';
                }
            } catch(e) {
                errEl.textContent = 'Error de conexión. Intenta de nuevo.';
                errEl.style.display = 'block';
            }
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar comprobante';
        });
    </script>
    <script src="<?php echo BASE_URL; ?>/public/js/components/user-panel.js"></script>
</body>
</html>