<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../models/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/app/views/auth/Login.php');
    exit;
}

$cliente_id = null;
$pedidos_con_disponibles = [];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Obtener el cliente asociado al usuario
    $stmt = $conn->prepare("SELECT id, nombre_completo, email, telefono_principal FROM clientes WHERE usuario_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cliente) {
        $cliente_id = $cliente['id'];
        
        // Obtener SOLO pedidos/ventas que TENGAN productos disponibles para devolución
        // Se consideran tanto las ventas (ventas) como los pedidos digitales (pedidos_online)
        $stmt = $conn->prepare("
            WITH devoluciones_por_venta AS (
                SELECT 
                    dv.venta_id,
                    dv.producto_id,
                    dv.cantidad as cantidad_comprada,
                    COALESCE((
                        SELECT SUM(d.cantidad) 
                        FROM devoluciones d 
                        WHERE d.venta_id = dv.venta_id 
                        AND d.producto_id = dv.producto_id 
                        AND d.estado_devolucion IN ('PENDIENTE', 'APROBADO')
                    ), 0) as cantidad_devuelta
                FROM detalle_ventas dv
                WHERE dv.venta_id IN (
                    SELECT v.id 
                    FROM ventas v 
                    WHERE v.cliente_id = ?
                )
            ),
            devoluciones_por_pedido AS (
                SELECT 
                    dp.pedido_id,
                    dp.producto_id,
                    dp.cantidad as cantidad_comprada,
                    COALESCE((
                        SELECT SUM(d.cantidad) 
                        FROM devoluciones d 
                        WHERE d.pedido_id = dp.pedido_id 
                        AND d.producto_id = dp.producto_id 
                        AND d.estado_devolucion IN ('PENDIENTE', 'APROBADO')
                    ), 0) as cantidad_devuelta
                FROM detalle_pedidos_online dp
                WHERE dp.pedido_id IN (
                    SELECT p.id 
                    FROM pedidos_online p 
                    WHERE p.cliente_id = ?
                )
            )
            SELECT * FROM (
                SELECT 'venta' AS tipo, v.id, v.codigo_venta AS codigo, v.created_at as fecha_venta, v.total
                FROM ventas v
                INNER JOIN devoluciones_por_venta dpv ON dpv.venta_id = v.id
                WHERE (dpv.cantidad_comprada - dpv.cantidad_devuelta) > 0
                UNION ALL
                SELECT 'pedido' AS tipo, p.id, p.codigo_pedido AS codigo, p.created_at as fecha_venta, p.total
                FROM pedidos_online p
                INNER JOIN devoluciones_por_pedido dpp ON dpp.pedido_id = p.id
                WHERE (dpp.cantidad_comprada - dpp.cantidad_devuelta) > 0
            ) x
            ORDER BY fecha_venta DESC
            LIMIT 10
        ");
        $stmt->execute([$cliente_id, $cliente_id]);
        $pedidos_con_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    error_log('Error cargando datos para devolución: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Devolución - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ============================================
           ESTILOS GENERALES
        ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header */
        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h1 i {
            color: #e74c3c;
        }

        .page-header p {
            color: #666;
            font-size: 1rem;
        }

        /* ============================================
           CARDS INFORMATIVAS
        ============================================ */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .card-icon.warning { background: #fdecea; color: #e04b4b; }
        .card-icon.info { background: #e8f3ff; color: #2c7be5; }
        .card-icon.success { background: #e8f6f1; color: #1F9166; }

        .card-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .card-info p {
            color: #7f8c8d;
            font-size: 14px;
        }

        /* ============================================
           FORMULARIO
        ============================================ */
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .form-container h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-group label span {
            color: #e74c3c;
            margin-left: 3px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #1F9166;
            box-shadow: 0 0 0 3px rgba(31,145,102,0.1);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* ============================================
           TABLA DE PRODUCTOS
        ============================================ */
        .products-table {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
        }

        .product-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            gap: 15px;
            transition: background 0.2s;
        }

        .product-item:hover {
            background: #f0f0f0;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #1F9166;
            cursor: pointer;
        }

        .product-info {
            flex: 1;
        }

        .product-info h4 {
            font-size: 15px;
            color: #2c3e50;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .product-info p {
            font-size: 12px;
            color: #7f8c8d;
        }

        .product-price {
            font-weight: 600;
            color: #1F9166;
            min-width: 90px;
            text-align: right;
            font-size: 15px;
        }

        .product-quantity {
            width: 70px;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            font-size: 14px;
        }

        .product-quantity:focus {
            outline: none;
            border-color: #1F9166;
        }

        /* ============================================
           BOTONES
        ============================================ */
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #1F9166;
            color: white;
        }

        .btn-primary:hover {
            background: #187a54;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(31,145,102,0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #2c3e50;
            border: 1px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }

        /* ============================================
           INFO BOX
        ============================================ */
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #1F9166;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .info-box h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box ul {
            list-style: none;
            padding-left: 25px;
        }

        .info-box li {
            color: #666;
            margin-bottom: 8px;
            position: relative;
        }

        .info-box li:before {
            content: "•";
            color: #1F9166;
            font-weight: bold;
            position: absolute;
            left: -15px;
        }

        /* ============================================
           MODAL DE CONFIRMACIÓN
        ============================================ */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            padding: 35px;
            position: relative;
            animation: modalFadeIn 0.3s ease;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            background: #e8f6f1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }

        .modal-icon i {
            font-size: 40px;
            color: #1F9166;
        }

        .modal h3 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .modal .codigo {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border: 2px dashed #1F9166;
        }

        .modal .codigo h4 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .modal .codigo .numero {
            color: #1F9166;
            font-size: 2rem;
            font-weight: 700;
            font-family: monospace;
            letter-spacing: 2px;
        }

        .modal .info {
            text-align: left;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .modal .info p {
            margin: 10px 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal .info i {
            color: #1F9166;
            width: 20px;
            font-size: 1.1rem;
        }

        .modal .mensaje {
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
            font-size: 1rem;
        }

        .modal .direccion {
            background: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 0.95rem;
        }

        .modal .direccion i {
            margin-right: 8px;
            color: #1F9166;
        }

        .modal .btn {
            width: 100%;
            justify-content: center;
            padding: 15px;
            font-size: 1.1rem;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Mensaje cuando no hay pedidos disponibles */
        .no-pedidos-message {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        .no-pedidos-message i {
            font-size: 48px;
            color: #1F9166;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .no-pedidos-message p {
            color: #666;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-undo-alt"></i>
                Solicitar Devolución
            </h1>
            <p>Selecciona los productos que deseas devolver y completa el formulario</p>
        </div>

        <!-- Cards informativas -->
        <div class="cards-grid">
            <div class="card">
                <div class="card-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-info">
                    <h3>7 Días</h3>
                    <p>Plazo para solicitar devolución</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon info">
                    <i class="fas fa-box-open"></i>
                </div>
                <div class="card-info">
                    <h3>Producto Nuevo</h3>
                    <p>Debe estar en perfecto estado</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon success">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="card-info">
                    <h3>Factura Original</h3>
                    <p>Requerida para el proceso</p>
                </div>
            </div>
        </div>

        <!-- Formulario de Devolución -->
        <div class="form-container">
            <h2>
                <i class="fas fa-file-signature"></i>
                Formulario de Devolución
            </h2>

            <div class="info-box">
                <h4>
                    <i class="fas fa-info-circle"></i>
                    Instrucciones:
                </h4>
                <ul>
                    <li>Selecciona el pedido del cual deseas devolver productos</li>
                    <li>Marca los productos a devolver y especifica la cantidad</li>
                    <li>Indica el motivo de la devolución</li>
                    <li>Un vendedor se comunicará contigo para coordinar el proceso</li>
                </ul>
            </div>

            <?php if (empty($pedidos_con_disponibles)): ?>
                <!-- Mensaje cuando no hay pedidos con productos disponibles -->
                <div class="no-pedidos-message">
                    <i class="fas fa-box-open"></i>
                    <p>No hay pedidos con productos disponibles para devolución en este momento.</p>
                </div>
                
                <!-- Botón Cancelar (mismo estilo que el original) -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo BASE_URL; ?>/app/views/layouts/inicio.php'">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                </div>
            <?php else: ?>
                <!-- Formulario normal cuando hay pedidos disponibles -->
                <form id="devolucionForm">
                    <!-- Selección de Pedido -->
                    <div class="form-group">
                        <label>Pedido / Factura <span>*</span></label>
                        <select class="form-control" id="pedidoSelect" required>
                            <option value="">Seleccione un pedido</option>
                            <?php foreach ($pedidos_con_disponibles as $pedido): ?>
                                <option value="<?php echo $pedido['id']; ?>" data-type="<?php echo $pedido['tipo']; ?>">
                                    <?php echo $pedido['tipo'] === 'pedido' ? 'Pedido' : 'Venta'; ?> <?php echo $pedido['codigo']; ?> - 
                                    <?php echo date('d/m/Y', strtotime($pedido['fecha_venta'])); ?> - 
                                    $<?php echo number_format($pedido['total'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Productos del Pedido -->
                    <div class="form-group" id="productosContainer" style="display: none;">
                        <label>Productos a devolver <span>*</span></label>
                        <div class="products-table" id="productosList">
                            <!-- Se llena dinámicamente con JS -->
                        </div>
                    </div>

                    <!-- Motivo de Devolución -->
                    <div class="form-group">
                        <label>Motivo de la devolución <span>*</span></label>
                        <select class="form-control" id="motivo" required>
                            <option value="">Seleccione un motivo</option>
                            <option value="Producto Defectuoso">Producto Defectuoso</option>
                            <option value="Garantía">Garantía</option>
                            <option value="Producto Incorrecto">Producto Incorrecto</option>
                            <option value="Producto Dañado">Producto Dañado</option>
                        </select>
                    </div>

                    <!-- Observaciones -->
                    <div class="form-group">
                        <label>Observaciones adicionales</label>
                        <textarea class="form-control" id="observaciones" placeholder="Describe con más detalle el motivo de la devolución..."></textarea>
                    </div>

                    <!-- Botones de acción -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo BASE_URL; ?>/app/views/pages/inicio.php'">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnEnviar">
                            <i class="fas fa-paper-plane"></i>
                            Enviar Solicitud
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Información de contacto -->
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-phone-alt" style="color: #1F9166;"></i>
                    <span style="color: #2c3e50;">0243-2343044</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-envelope" style="color: #1F9166;"></i>
                    <span style="color: #2c3e50;">2016rojasinversiones@gmail.com</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-map-marker-alt" style="color: #1F9166;"></i>
                    <span style="color: #2c3e50;">AV ARAGUA LOCAL NRO 286, MARACAY</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación Mejorado -->
    <div class="modal-overlay" id="modalConfirmacion">
        <div class="modal">
            <div class="modal-icon">
                <i class="fas fa-undo-alt"></i>
            </div>
            <h3>¡Devolución Solicitada!</h3>
            
            <div class="codigo">
                <h4>Código de devolución</h4>
                <div class="numero" id="codigoDevolucion">DEV-20260226-0001</div>
            </div>

            <div class="info">
                <p>
                    <i class="fas fa-box"></i>
                    <strong>Productos:</strong> <span id="resumenProductos">1 producto</span>
                </p>
                <p>
                    <i class="fas fa-tag"></i>
                    <strong>Motivo:</strong> <span id="resumenMotivo">Producto Defectuoso</span>
                </p>
                <p>
                    <i class="fas fa-calendar"></i>
                    <strong>Fecha límite:</strong> <span id="fechaLimite">2026-03-05</span>
                </p>
            </div>

            <div class="mensaje">
                <i class="fas fa-clock" style="color: #e74c3c; margin-right: 5px;"></i>
                Un vendedor se comunicará contigo en las próximas 24 horas para coordinar el proceso de devolución.
            </div>

            <div class="direccion">
                <i class="fas fa-map-marker-alt"></i>
                AV ARAGUA LOCAL NRO 286, MARACAY
                <br>
                <small style="color: #aaa;">Presenta este código en nuestra tienda si es necesario</small>
            </div>

            <button class="btn btn-primary" onclick="cerrarModal()">
                <i class="fas fa-check"></i>
                Entendido
            </button>
        </div>
    </div>

    <script>
        // Variables globales
        let ultimaDevolucion = null;

        // Función para cargar productos de una venta o pedido (desde la BD)
        async function cargarProductos(orderId, orderType = 'venta') {
            const productosContainer = document.getElementById('productosContainer');
            const productosList = document.getElementById('productosList');
            productosList.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Cargando productos...</div>';

            try {
                const queryParam = orderType === 'pedido' ? 'pedido_id' : 'venta_id';
                const resp = await fetch('<?php echo BASE_URL; ?>/api/get_venta_products.php?' + queryParam + '=' + encodeURIComponent(orderId));
                const data = await resp.json();
                
                if (!resp.ok || !data.success) {
                    throw new Error(data.error || 'Error cargando productos');
                }

                if (!data.items || data.items.length === 0) {
                    // Esto no debería pasar porque filtramos las ventas, pero por si acaso
                    productosContainer.style.display = 'none';
                    
                    // Recargar la página para actualizar la lista de ventas
                    alert('Esta venta ya no tiene productos disponibles. La página se recargará.');
                    window.location.reload();
                    return;
                }

                // Mostrar el contenedor y construir el HTML
                productosContainer.style.display = 'block';
                
                let html = '';
                data.items.forEach(item => {
                    const maxQty = parseInt(item.cantidad_disponible, 10);
                    const price = parseFloat(item.precio_unitario || 0).toFixed(2);
                    html += `
                    <div class="product-item">
                        <input type="checkbox" class="product-checkbox" data-producto-id="${item.producto_id}" data-producto-nombre="${escapeHtml(item.producto_nombre)}" data-precio="${price}">
                        <div class="product-info">
                            <h4>${escapeHtml(item.producto_nombre || 'Producto')}</h4>
                            <p>Código: ${escapeHtml(item.codigo_interno || 'N/A')} | Disponible: ${maxQty} de ${item.cantidad_original}</p>
                        </div>
                        <div class="product-price">$${price}</div>
                        <input type="number" class="product-quantity" min="1" max="${maxQty}" value="1" disabled>
                    </div>
                    `;
                });

                productosList.innerHTML = html;

                // Reactivar eventos de checkboxes
                document.querySelectorAll('.product-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const qty = this.closest('.product-item').querySelector('.product-quantity');
                        qty.disabled = !this.checked;
                        if (!this.checked) qty.value = 1;
                    });
                });

            } catch (err) {
                productosContainer.style.display = 'none';
                console.error('Error cargando productos:', err);
                alert('Error al cargar los productos: ' + err.message);
            }
        }

        // Helper para escapar HTML mínimo
        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>"']/g, function (s) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[s];
            });
        }

        // Evento change del select de pedidos/ventas
        document.getElementById('pedidoSelect')?.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const orderType = selectedOption?.dataset?.type || 'venta';

            if (this.value) {
                cargarProductos(this.value, orderType);
            } else {
                document.getElementById('productosContainer').style.display = 'none';
                document.getElementById('productosList').innerHTML = '';
            }
        });

        // Función para formatear fecha (YYYY-MM-DD)
        function formatearFecha(fecha) {
            const d = new Date(fecha);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Función para calcular fecha límite (3 días después)
        function calcularFechaLimite() {
            const fecha = new Date();
            fecha.setDate(fecha.getDate() + 3);
            return formatearFecha(fecha);
        }

        // Envío del formulario
        document.getElementById('devolucionForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnEnviar');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner"></span> Enviando...';
            btn.disabled = true;

            // Validar pedido/venta seleccionado
            const pedidoSelect = document.getElementById('pedidoSelect');
            const pedidoId = pedidoSelect.value;
            const selectedOption = pedidoSelect.options[pedidoSelect.selectedIndex];
            const orderType = selectedOption?.dataset?.type || 'venta';

            if (!pedidoId) {
                alert('Seleccione un pedido');
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }

            // Validar productos seleccionados
            const productosSeleccionados = Array.from(document.querySelectorAll('.product-checkbox:checked'));
            if (productosSeleccionados.length === 0) {
                alert('Seleccione al menos un producto para devolver');
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }

            // Validar motivo
            const motivo = document.getElementById('motivo').value;
            if (!motivo) {
                alert('Seleccione un motivo de devolución');
                btn.innerHTML = originalText;
                btn.disabled = false;
                return;
            }

            // Construir payload
            const items = productosSeleccionados.map(cb => ({
                producto_id: parseInt(cb.dataset.productoId, 10) || 0,
                cantidad: parseInt(cb.closest('.product-item').querySelector('.product-quantity').value, 10) || 1
            }));

            // Crear resumen para el modal
            const totalProductos = items.reduce((sum, item) => sum + item.cantidad, 0);
            const nombresProductos = productosSeleccionados.map(cb => cb.dataset.productoNombre).join(', ');

            const payload = {
                items: items,
                motivo: motivo,
                descripcion: document.getElementById('observaciones').value
            };

            if (orderType === 'pedido') {
                payload.pedido_id = parseInt(pedidoId, 10);
            } else {
                payload.venta_id = parseInt(pedidoId, 10);
            }

            try {
                const resp = await fetch('<?php echo BASE_URL; ?>/api/add_devolucion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await resp.json();
                
                if (!resp.ok || !data.success) {
                    throw new Error(data.error || 'Error en servidor');
                }

                // Guardar datos de la devolución
                ultimaDevolucion = {
                    codigo: data.devoluciones[0].codigo,
                    motivo: motivo,
                    productos: totalProductos,
                    fechaLimite: calcularFechaLimite()
                };

                // Actualizar modal con los datos
                document.getElementById('codigoDevolucion').textContent = ultimaDevolucion.codigo;
                document.getElementById('resumenProductos').innerHTML = `${totalProductos} producto(s): ${nombresProductos.substring(0, 50)}${nombresProductos.length > 50 ? '...' : ''}`;
                document.getElementById('resumenMotivo').textContent = motivo;
                document.getElementById('fechaLimite').textContent = ultimaDevolucion.fechaLimite;

                // Mostrar modal de confirmación
                document.getElementById('modalConfirmacion').classList.add('active');

                // Resetear formulario
                this.reset();
                document.getElementById('productosContainer').style.display = 'none';
                document.getElementById('productosList').innerHTML = '';
                
            } catch (err) {
                alert('Error enviando la solicitud: ' + err.message);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        // Función para cerrar modal
        function cerrarModal() {
            document.getElementById('modalConfirmacion').classList.remove('active');
            // Redirigir al inicio
            window.location.href = '<?php echo BASE_URL; ?>/app/views/layouts/inicio.php';
        }

        // Cerrar modal al hacer clic fuera
        document.getElementById('modalConfirmacion')?.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>