<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../helpers/permissions.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';

// Verificar permisos
require_permission('promociones');

// Conectar a la base de datos
$database = new Database();
$conn = $database->getConnection();

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

// Obtener estadísticas de promociones
$stats = [
    'activas' => 0,
    'ventas_con_promocion' => 0,
    'ingresos_promociones' => 0,
    'proximas_vencer' => 0
];

if ($conn) {
    // Promociones activas (solo por estado, las fechas se controlan en el slider)
    $sql = "SELECT COUNT(*) as total FROM promociones 
            WHERE estado = true";
    $stmt = ejecutarConsulta($conn, $sql);
    if ($stmt) {
        $result = $stmt->fetch();
        $stats['activas'] = $result['total'];
    }
    
    // Ventas con promoción (asumiendo que tienes relación con ventas)
    $sql = "SELECT COUNT(DISTINCT v.id) as total 
            FROM ventas v
            INNER JOIN detalle_ventas dv ON v.id = dv.venta_id
            INNER JOIN producto_promociones pp ON dv.producto_id = pp.producto_id
            WHERE v.estado_venta = 'COMPLETADA'
            AND v.created_at >= CURRENT_DATE - INTERVAL '30 days'";
    $stmt = ejecutarConsulta($conn, $sql);
    if ($stmt) {
        $result = $stmt->fetch();
        $stats['ventas_con_promocion'] = $result['total'];
    }
    
    // Ingresos por promociones (estimado)
    $sql = "SELECT COALESCE(SUM(v.total), 0) as total 
            FROM ventas v
            INNER JOIN detalle_ventas dv ON v.id = dv.venta_id
            INNER JOIN producto_promociones pp ON dv.producto_id = pp.producto_id
            WHERE v.estado_venta = 'COMPLETADA'
            AND v.created_at >= CURRENT_DATE - INTERVAL '30 days'";
    $stmt = ejecutarConsulta($conn, $sql);
    if ($stmt) {
        $result = $stmt->fetch();
        $stats['ingresos_promociones'] = $result['total'];
    }
    
    // Próximas a vencer (en los próximos 7 días)
    $sql = "SELECT COUNT(*) as total FROM promociones 
            WHERE estado = true 
            AND fecha_fin BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '7 days'";
    $stmt = ejecutarConsulta($conn, $sql);
    if ($stmt) {
        $result = $stmt->fetch();
        $stats['proximas_vencer'] = $result['total'];
    }
    
    // Obtener productos en promoción
    $sql = "SELECT p.id, p.nombre, p.precio_venta, 
                   pr.nombre as promocion_nombre, pr.tipo_promocion, pr.valor,
                   COALESCE(pr.imagen_url, pi.imagen_url, '') as imagen_url
            FROM productos p
            INNER JOIN producto_promociones pp ON p.id = pp.producto_id
            INNER JOIN promociones pr ON pp.promocion_id = pr.id
            LEFT JOIN producto_imagenes pi ON p.id = pi.producto_id AND pi.es_principal = true
            WHERE pr.estado = true 
            ORDER BY pr.fecha_fin ASC
            LIMIT 8";
    $stmt = ejecutarConsulta($conn, $sql);
    $productos_promocion = $stmt ? $stmt->fetchAll() : [];
    
    // Obtener promociones - por defecto solo activas
    $showInactive = $_GET['show_inactive'] ?? false;
    $whereEstado = $showInactive ? 'WHERE p.estado IN (true, false)' : 'WHERE p.estado = true';
    $sql = "SELECT p.*, 
                   COUNT(pp.producto_id) as total_productos
            FROM promociones p
            LEFT JOIN producto_promociones pp ON p.id = pp.promocion_id
            {$whereEstado}
            GROUP BY p.id
            ORDER BY p.estado ASC, p.created_at DESC";
    $stmt = ejecutarConsulta($conn, $sql);
    $promociones = $stmt ? $stmt->fetchAll() : [];
    
    // Obtener productos para el select
    $sql = "SELECT id, nombre, precio_venta FROM productos WHERE estado = true ORDER BY nombre";
    $stmt = ejecutarConsulta($conn, $sql);
    $productos = $stmt ? $stmt->fetchAll() : [];

    // Cargar banco de imágenes de promociones (carpeta public/img/promo_bank)
    $promoBankDir = __DIR__ . '/../../../public/img/promo_bank';
    $promo_bank_images = [];
    if (is_dir($promoBankDir)) {
        $files = scandir($promoBankDir);
        foreach ($files as $f) {
            if (in_array($f, ['.','..'])) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $promo_bank_images[] = $f;
            }
        }
    }
    
    // Obtener categorías para el select
    $sql = "SELECT id, nombre FROM categorias WHERE estado = true ORDER BY nombre";
    $stmt = ejecutarConsulta($conn, $sql);
    $categorias = $stmt ? $stmt->fetchAll() : [];
    
    // Datos para gráficas
    // Efectividad de promociones (últimas 5 promociones)
    $sql = "SELECT pr.nombre, COUNT(dv.id) as ventas
            FROM promociones pr
            LEFT JOIN producto_promociones pp ON pr.id = pp.promocion_id
            LEFT JOIN detalle_ventas dv ON pp.producto_id = dv.producto_id
            LEFT JOIN ventas v ON dv.venta_id = v.id
            WHERE v.estado_venta = 'COMPLETADA'
            AND v.created_at >= CURRENT_DATE - INTERVAL '60 days'
            GROUP BY pr.id, pr.nombre
            ORDER BY ventas DESC
            LIMIT 5";
    $stmt = ejecutarConsulta($conn, $sql);
    $efectividad = $stmt ? $stmt->fetchAll() : [];
    
    // Ventas por promoción activa (solo porcentaje)
    $sql = "SELECT pr.nombre as promocion, pr.tipo_promocion, COUNT(dv.id) as ventas
            FROM promociones pr
            LEFT JOIN producto_promociones pp ON pr.id = pp.promocion_id
            LEFT JOIN detalle_ventas dv ON pp.producto_id = dv.producto_id
            LEFT JOIN ventas v ON dv.venta_id = v.id
            WHERE pr.estado = true
            AND v.estado_venta = 'COMPLETADA'
            AND v.created_at >= CURRENT_DATE - INTERVAL '60 days'
            GROUP BY pr.id, pr.nombre, pr.tipo_promocion
            ORDER BY ventas DESC";
    $stmt = ejecutarConsulta($conn, $sql);
    $ventas_tipo = $stmt ? $stmt->fetchAll() : [];

    // Ventas con vs sin promoción
    $sql = "SELECT 
                SUM(CASE WHEN pp.promocion_id IS NOT NULL AND pr.estado = true THEN 1 ELSE 0 END) as con_promo,
                SUM(CASE WHEN pp.promocion_id IS NULL OR pr.estado = false THEN 1 ELSE 0 END) as sin_promo
            FROM detalle_ventas dv
            LEFT JOIN producto_promociones pp ON dv.producto_id = pp.producto_id
            LEFT JOIN promociones pr ON pp.promocion_id = pr.id
            LEFT JOIN ventas v ON dv.venta_id = v.id
            WHERE v.estado_venta = 'COMPLETADA'
            AND v.created_at >= CURRENT_DATE - INTERVAL '60 days'";
    $stmt = ejecutarConsulta($conn, $sql);
    $ventas_promo = $stmt ? $stmt->fetch() : ['con_promo' => 0, 'sin_promo' => 0];
    
    // Obtener tipos únicos de promociones para el filtro
    $sql = "SELECT DISTINCT tipo_promocion FROM promociones WHERE tipo_promocion IS NOT NULL ORDER BY tipo_promocion";
    $stmt = ejecutarConsulta($conn, $sql);
    $tipos_promociones = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
}
$base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
// Validar que BASE_URL esté definido, si no usa una ruta por defecto
if (empty($base_url)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base_url = $protocol . "://" . $host . "/inversiones-rojas";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promociones - Inversiones Rojas</title>
    <script>
        var APP_BASE = '<?php echo $base_url; ?>';
        var TASA_CAMBIO = <?php echo getTasaCambio(); ?>;
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/layouts/promociones.css?v=<?php echo time(); ?>">
    <!-- Sistema de notificaciones y validaciones personalizadas -->
    <script src="<?php echo $base_url; ?>/public/js/inv-notifications.js"></script>
    <style>
        /* Estilos para moneda dual */
        .moneda-bs { color: #1F9166; font-weight: 700; }
        .moneda-usd { color: #6c757d; font-size: 0.85em; }
        
        /* Modal básico: oculto por defecto, se muestra con la clase .active */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-container {
            background: #fff;
            border-radius: 12px;
            max-width: 900px;
            width: 92%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            padding: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .modal-header { padding: 18px 22px; border-bottom: 1px solid #eee; }
        .modal-header h2 { margin:0; font-size:1.25rem; }
        .modal-close { position:absolute; top:12px; right:12px; background:none; border:none; font-size:22px; cursor:pointer; }
        .modal-body { padding: 18px 22px; }
        .modal-footer { padding: 14px 22px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:12px; flex-wrap:wrap; }
        @keyframes modalFadeIn { from { opacity: 0; transform: translateY(-12px);} to { opacity: 1; transform: translateY(0);} }
        /* Promo bank thumbnails */
        label.promo-bank { display:inline-block; margin:6px; }
        label.promo-bank img { width:90px; height:60px; object-fit:cover; border:2px solid transparent; border-radius:6px; transition: box-shadow .15s, border-color .12s; cursor:pointer; }
        label.promo-bank.selected img { border-color:#1F9166; box-shadow: 0 4px 12px rgba(31,145,102,0.16); }
        /* Modal footer buttons */
        .modal-footer .btn { padding:8px 14px; border-radius:6px; }
        .modal-footer .btn-secondary { background:#f5f7f8; color:#333; border:1px solid #e0e6e8; }
        .modal-footer .btn-secondary:hover { background:#eef3f4; }
        .modal-footer .btn-primary { background:#1F9166; color:#fff; border:none; }
        .modal-footer .btn-primary:hover { background:#187a54; }
        /* Image uploader inside modal */
        .image-uploader { border: 1px dashed #e6eaeb; padding:12px; border-radius:8px; background:#fff; }
        .image-uploader .uploader-controls { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .image-uploader input[type="file"] { padding:6px; border-radius:6px; border:1px solid #e6eaeb; }
        .promo-bank-container { margin-top:10px; display:flex; gap:8px; flex-wrap:wrap; }
        /* Preview thumbnails (similar look to inventario uploader) */
        .preview-list { display:flex; gap:8px; flex-wrap:wrap; }
        .image-preview-item { position: relative; width: 96px; height: 64px; border-radius: 6px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .image-preview-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .image-preview-item .remove-btn { position: absolute; top: -6px; right: -6px; background: #e74c3c; color: #fff; width: 22px; height: 22px; border-radius: 50%; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.12); }
    </style>
    <script>
        // Función de validación completa para promociones
        function validarPromocion() {
            const nombre = document.getElementById('nombre');
            const tipo = document.getElementById('tipo');
            const valor = document.getElementById('valor');
            const fechaInicio = document.getElementById('fecha_inicio');
            const fechaFin = document.getElementById('fecha_fin');
            const productosSeleccionados = document.querySelectorAll('input[name="productos[]"]:checked');

            // Validar campos obligatorios
            if (!InvValidate.required(nombre, 'Nombre de la promoción')) return false;
            if (!InvValidate.required(tipo, 'Tipo de promoción')) return false;
            if (!InvValidate.positiveNumber(valor, 'Valor', false)) return false;
            if (!InvValidate.required(fechaInicio, 'Fecha de inicio')) return false;
            if (!InvValidate.required(fechaFin, 'Fecha de fin')) return false;

            // Validar fechas
            if (fechaFin.value < fechaInicio.value) {
                mostrarToast('La fecha de fin no puede ser menor a la fecha de inicio', 'error');
                return false;
            }

            // Para promociones nuevas, validar que fecha de inicio no sea pasada
            const isEditing = document.getElementById('promocionId').value;
            if (!isEditing && !InvValidate.notPastDate(fechaInicio, 'Fecha de inicio')) return false;

            // Validar que se haya seleccionado al menos un producto
            if (productosSeleccionados.length === 0) {
                mostrarToast('Debes seleccionar al menos un producto', 'error');
                return false;
            }

            return true;
        }

        // Guardar promoción
        function guardarPromocion(event) {
            event.preventDefault();

            // Validar formulario completo
            if (!validarPromocion()) return false;

            // Mostrar loading
            document.getElementById('loadingOverlay').classList.add('active');

            // Recopilar datos
            const fileInput = document.getElementById('imagen');
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

            // Datos básicos
            const baseData = {
                id: document.getElementById('promocionId').value,
                nombre: document.getElementById('nombre').value,
                tipo: document.getElementById('tipo').value,
                valor: document.getElementById('valor').value,
                fecha_inicio: document.getElementById('fecha_inicio').value,
                fecha_fin: document.getElementById('fecha_fin').value,
                estado: document.getElementById('estado').value,
                descripcion: document.getElementById('descripcion').value,
                productos: Array.from(productosSeleccionados).map(cb => cb.value)
            };
            // Incluir imagen existente si aplica (cuando se está editando y no se reemplaza)
            const existingImgEl = document.getElementById('existing_imagen_url');
            if (existingImgEl && existingImgEl.value) baseData.imagen_existente = existingImgEl.value;

            function handleGuardarResponse(data) {
                document.getElementById('loadingOverlay').classList.remove('active');
                if (data && data.success) {
                    mostrarToast(data.message || 'Promoción guardada', 'success');
                    cerrarModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    mostrarToast((data && data.message) ? data.message : 'Error al guardar la promoción', 'error');
                }
            }

            if (hasFile) {
                const fd = new FormData();
                // Agregar campos al FormData, productos como arreglo
                for (const k in baseData) {
                    if (k === 'productos') {
                        baseData.productos.forEach(p => fd.append('productos[]', p));
                    } else {
                        fd.append(k, baseData[k]);
                    }
                }
                fd.append('imagen', fileInput.files[0]);

                fetch('<?php echo BASE_URL; ?>/api/guardar_promocion.php', {
                    method: 'POST',
                    body: fd
                })
                .then(response => response.json())
                .then(handleGuardarResponse)
                .catch(err => { console.error(err); document.getElementById('loadingOverlay').classList.remove('active'); mostrarToast('Error al guardar la promoción', 'error'); });
            } else {
                // Ver si se seleccionó imagen del banco
                const selectedBank = document.querySelector('input[name="imagen_banco_key"]:checked');
                if (selectedBank) baseData.imagen_banco_key = selectedBank.value;

                fetch('<?php echo BASE_URL; ?>/api/guardar_promocion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(baseData)
                })
                .then(response => response.json())
                .then(handleGuardarResponse)
                .catch(err => { console.error(err); document.getElementById('loadingOverlay').classList.remove('active'); mostrarToast('Error al guardar la promoción', 'error'); });
            }

            return false;
        }
    </script>
    <style>
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1F9166;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #1F9166;
            color: white;
        }
        
        .btn-primary:hover {
            background: #187a54;
        }
        
        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .productos-select {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px;
        }
        
        .producto-checkbox {
            display: flex;
            align-items: center;
            padding: 5px 0;
        }
        
        .producto-checkbox input {
            margin-right: 10px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #1F9166;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            padding: 15px 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1100;
            animation: slideIn 0.3s ease;
            max-width: 350px;
        }
        
        .toast.success {
            border-left: 4px solid #1F9166;
        }
        
        .toast.error {
            border-left: 4px solid #e74c3c;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
    <style>
        /* =============== UNIFIED TABLE STYLES =============== */
        .promociones-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .promociones-table .data-table-container {
            min-width: 1026px;
        }
        
        .promociones-table .data-table-header,
        .promociones-table .data-table-row {
            grid-template-columns: 1.2fr 2fr 1fr 0.9fr 0.8fr 1fr 1fr 0.8fr 1.5fr;
            gap: 12px;
        }

        /* Tablet - ocultar columnas menos importantes */
        @media (max-width: 1024px) {
            .promociones-table .data-table-header,
            .promociones-table .data-table-row {
                grid-template-columns: 1.2fr 2fr 1fr 1fr 0.8fr 1.5fr;
            }
            /* Ocultar: Valor(4), Productos(5), Fecha Inicio(6), Fecha Fin(7) → dejar Código, Nombre, Tipo, Estado, Acciones */
            .promociones-table .data-table-header > div:nth-child(4),
            .promociones-table .data-table-row > div:nth-child(4),
            .promociones-table .data-table-header > div:nth-child(5),
            .promociones-table .data-table-row > div:nth-child(5),
            .promociones-table .data-table-header > div:nth-child(6),
            .promociones-table .data-table-row > div:nth-child(6),
            .promociones-table .data-table-header > div:nth-child(7),
            .promociones-table .data-table-row > div:nth-child(7) {
                display: none;
            }
        }

        /* Móvil grande - layout de tarjetas */
        @media (max-width: 900px) {
            .promociones-table .data-table-header { display: none; }
            .promociones-table .data-table-row {
                display: block;
                padding: 16px;
                margin-bottom: 12px;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                background: #fff;
                box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            }
            .promociones-table .data-table-row > div {
                display: flex;
                align-items: center;
                padding: 6px 0;
                border-bottom: 1px dashed #e9ecef;
                font-size: 0.85rem;
            }
            .promociones-table .data-table-row > div:last-child { border-bottom: none; }
            .promociones-table .data-table-row > div:before {
                content: attr(data-label);
                font-weight: 600;
                width: 100px;
                min-width: 100px;
                color: #64748b;
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }
            .promociones-table .actions-cell {
                justify-content: flex-start;
                gap: 12px;
                padding-top: 12px;
                border-bottom: none;
            }
            .promociones-table .action-btn {
                width: 42px;
                height: 42px;
            }
        }

        /* Móvil pequeño */
        @media (max-width: 600px) {
            .promociones-table .data-table-row { padding: 12px; }
            .promociones-table .data-table-row > div { font-size: 0.8rem; }
            .promociones-table .data-table-row > div:before { width: 80px; min-width: 80px; font-size: 0.7rem; }
            .promociones-table .action-btn { width: 38px; height: 38px; }
        }

        /* =============== END UNIFIED TABLE STYLES =============== */

        /* Tabla de promociones: columnas y estilos de acciones */
        
        /* ========== MODO IFRAME - ASEGURAR VISIBILIDAD ========== */
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        .admin-content {
            padding: 30px;
            width: 100%;
            min-height: 100%;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .promociones-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #1F9166; }
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .chart-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); height: 280px; }
        .chart-wrapper { height: 200px; position: relative; }
        .data-table-container { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 30px; }
        .data-table-header { display: grid; padding: 16px 20px; background: #f7f7f7; font-weight: 600; color: #333; border-bottom: 1px solid #e6e6e6; }
        .data-table-row { display: grid; padding: 14px 20px; border-bottom: 1px solid #f0f0f0; }
    </style>
<body>
    <!-- Overlay de carga -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="admin-content">
        <!-- Stats Cards -->
        <div class="promociones-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['activas']; ?></h3>
                    <p>Promociones Activas</p>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        Activas hoy
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['ventas_con_promocion']; ?></h3>
                    <p>Ventas con Promoción</p>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        Últimos 30 días
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <?php 
                    $precios = formatearMonedaDual($stats['ingresos_promociones']);
                    echo '<span class="moneda-usd">' . $precios['usd'] . '</span>';
                    echo '<span class="moneda-bs">' . $precios['bs'] . '</span>';
                    ?>
                    <p>Ingresos por Promociones</p>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        Últimos 30 días
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['proximas_vencer']; ?></h3>
                    <p>Próximas a Vencer</p>
                    <div class="stat-trend trend-down">
                        <i class="fas fa-arrow-down"></i>
                        En 7 días
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <!-- Ventas por Promoción Activa -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Promociones Activas - Ventas</h3>
                </div>
                <div class="chart-wrapper">
                    <canvas id="tipoPromocionesChart"></canvas>
                </div>
            </div>

            <!-- Ventas con vs sin Promoción -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Ventas con Promoción</h3>
                </div>
                <div class="chart-wrapper">
                    <canvas id="ventasPromoChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Productos en Promoción -->
        <div class="productos-section">
            <h3 style="margin-bottom: 20px; color: #2c3e50;">Productos en Promoción</h3>
            <div class="productos-grid">
                <?php if (!empty($productos_promocion)): ?>
                    <?php foreach ($productos_promocion as $producto): 
                        $precio_descuento = $producto['precio_venta'];
                        $tipo_producto = strtolower($producto['tipo_promocion'] ?? '');
                        if ($tipo_producto == 'descuento' || $tipo_producto == 'porcentaje') {
                            $precio_descuento = $producto['precio_venta'] * (1 - $producto['valor'] / 100);
                        } elseif ($tipo_producto == 'monto') {
                            $precio_descuento = $producto['precio_venta'] - $producto['valor'];
                        }
                        $descuento_porcentaje = round((1 - $precio_descuento / $producto['precio_venta']) * 100);
                    ?>
                    <div class="producto-card">
                        <div class="producto-img">
                            <?php if (!empty($producto['imagen_url'])): ?>
                                <img src="<?php echo $producto['imagen_url']; ?>" alt="<?php echo $producto['nombre']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-motorcycle fa-2x"></i>
                            <?php endif; ?>
                        </div>
                        <div class="producto-name"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                        <div class="producto-price">
                            <?php $precios_old = formatearMonedaDual($producto['precio_venta']); ?>
                            <?php $precios_new = formatearMonedaDual($precio_descuento); ?>
                            <span class="price-old"><span class="moneda-bs"><?php echo $precios_old['bs']; ?></span> <span class="moneda-usd">(<?php echo $precios_old['usd']; ?>)</span></span>
                            <span class="price-new"><span class="moneda-bs"><?php echo $precios_new['bs']; ?></span> <span class="moneda-usd">(<?php echo $precios_new['usd']; ?>)</span></span>
                        </div>
                        <div class="producto-discount"><?php echo $descuento_porcentaje; ?>% OFF</div>
                        <div class="producto-promo"><?php echo $producto['promocion_nombre']; ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-tags" style="font-size: 3rem; margin-bottom: 15px;"></i>
                        <p>No hay productos en promoción activos</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="search-filters">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Buscar promociones...">
                <i class="fas fa-search search-icon"></i>
            </div>
            <select class="filter-select" id="estadoFilter">
                <option value="">Todos los estados</option>
                <option value="activa">Activa</option>
                <option value="inactiva">Inactiva</option>
            </select>
            <select class="filter-select" id="tipoFilter">
                <option value="">Todos los tipos</option>
                <?php foreach ($tipos_promociones as $tipo): ?>
                    <option value="<?php echo strtolower($tipo); ?>"><?php echo ucfirst(strtolower($tipo)); ?></option>
                <?php endforeach; ?>
            </select>
            <select class="filter-select" id="dateFilter">
                <option value="">Todas las fechas</option>
                <option value="today">Hoy</option>
                <option value="this_week">Esta semana</option>
                <option value="this_month">Este mes</option>
                <option value="next_30">Próximos 30 días</option>
                <option value="custom">Rango personalizado</option>
            </select>
        </div>

        <!-- Rango de fechas personalizado (oculto por defecto) -->
        <div id="customDateRange" style="display: none; margin-top: 10px; padding: 15px; background: #f8f9fa; border-radius: 6px; border: 1px solid #ddd;">
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px;">Desde:</label>
                    <input type="date" id="dateFrom" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #555; font-size: 13px;">Hasta:</label>
                    <input type="date" id="dateTo" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                </div>
                <button type="button" onclick="applyCustomDate()" style="margin-top: 22px; padding: 8px 16px; background: #1F9166; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                    Aplicar
                </button>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="promociones-actions">
            <div class="action-buttons">
                <button class="btn btn-primary" id="mostrarModalBtn">
                    <i class="fas fa-plus"></i>
                    Nueva Promoción
                </button>
                <?php if ($stats['proximas_vencer'] > 0): ?>
                <button class="btn btn-warning" onclick="filtrarProximasVencer()">
                    <i class="fas fa-clock"></i>
                    Próximas a Vencer (<?php echo $stats['proximas_vencer']; ?>)
                </button>
                <?php endif; ?>
                </div>
        </div>

        <!-- Promociones Table - responsive con scroll horizontal -->
        <div class="promociones-table-wrapper">
        <div class="data-table-container promociones-table">
            <div class="data-table-header">
                <div>Código</div>
                <div>Nombre</div>
                <div>Tipo</div>
                <div>Valor</div>
                <div>Productos</div>
                <div>Fecha Inicio</div>
                <div>Fecha Fin</div>
                <div>Estado</div>
                <div>Acciones</div>
            </div>
            
            <div id="promocionesList">
                <?php if (!empty($promociones)): ?>
                    <?php foreach ($promociones as $promo): 
                        $estado_class = $promo['estado'] ? 'success' : 'danger';
                        $estado_text = $promo['estado'] ? 'Activa' : 'Inactiva';
                        
                        $valor_texto = $promo['valor'];
                        $tipo_promo = strtolower($promo['tipo_promocion'] ?? '');
                        if ($tipo_promo == 'descuento' || $tipo_promo == 'porcentaje') {
                            $valor_texto = $promo['valor'] . '% OFF';
                        } elseif ($tipo_promo == 'monto') {
                            $valor_texto = '$' . $promo['valor'] . ' OFF';
                        } elseif ($tipo_promo == 'envio') {
                            $valor_texto = 'Envío Gratis';
                        } elseif ($tipo_promo == 'regalo') {
                            $valor_texto = 'Producto de Regalo';
                        }
                    ?>
                    <div class="data-table-row"
                         data-id="<?php echo $promo['id']; ?>"
                         data-nombre="<?php echo strtolower(htmlspecialchars($promo['nombre'])); ?>"
                         data-nombre-orig="<?php echo htmlspecialchars($promo['nombre']); ?>"
                         data-tipo="<?php echo strtolower($promo['tipo_promocion'] ?? ''); ?>"
                         data-tipo-orig="<?php echo htmlspecialchars(ucfirst(strtolower($promo['tipo_promocion'] ?? ''))); ?>"
                         data-valor="<?php echo htmlspecialchars($valor_texto); ?>"
                         data-productos="<?php echo intval($promo['total_productos']); ?>"
                         data-estado="<?php echo $promo['estado'] ? 'activa' : 'inactiva'; ?>"
                         data-estado-text="<?php echo htmlspecialchars($estado_text); ?>"
                         data-fecha-inicio="<?php echo $promo['fecha_inicio']; ?>"
                         data-fecha-fin="<?php echo $promo['fecha_fin']; ?>"
                         data-descripcion="<?php echo htmlspecialchars($promo['descripcion'] ?? ''); ?>">
                        <div>
                            <strong>PROM-<?php echo str_pad($promo['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                        </div>
                        <div><?php echo htmlspecialchars($promo['nombre']); ?></div>
                        <div><?php echo ucfirst(strtolower($promo['tipo_promocion'] ?? '')); ?></div>
                        <div><?php echo $valor_texto; ?></div>
                        <div><?php echo $promo['total_productos']; ?> productos</div>
                        <div><?php echo date('d/m/Y', strtotime($promo['fecha_inicio'])); ?></div>
                        <div><?php echo date('d/m/Y', strtotime($promo['fecha_fin'])); ?></div>
                        <div><span class="data-status-badge <?php echo $promo['estado'] ? 'success' : 'danger'; ?>"><?php echo $estado_text; ?></span></div>
                        <div class="table-actions">
                            <button class="table-action-btn view" onclick="verPromocion(<?php echo $promo['id']; ?>)" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="table-action-btn edit" onclick="editarPromocion(<?php echo $promo['id']; ?>)" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($promo['estado']): ?>
                                <button class="table-action-btn delete" onclick="inhabilitarPromocion(<?php echo $promo['id']; ?>)" title="Inhabilitar">
                                    <i class="fas fa-ban"></i>
                                </button>
                            <?php else: ?>
                                <button class="table-action-btn view" onclick="inhabilitarPromocion(<?php echo $promo['id']; ?>)" title="Habilitar">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
                        <p>No hay promociones registradas</p>
                    </div>
                <?php endif; ?>
                <!-- Mensaje cuando los filtros no encuentran resultados -->
                <div id="noResultsPromo" style="display:none; text-align:center; padding:40px; color:#999;">
                    <i class="fas fa-search" style="font-size:2rem; margin-bottom:12px; display:block;"></i>
                    <p>No se encontraron promociones con los filtros aplicados</p>
                </div>
            </div>
        </div>
        </div> <!-- cierre de promociones-table-wrapper -->
    </div>

    <!-- MODAL PARA NUEVA PROMOCIÓN -->
    <div class="modal-overlay" id="modalPromocion">
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="modalTitle">Nueva Promoción</h2>
                <button class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            
            <form id="formPromocion" onsubmit="return guardarPromocion(event)" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="promocionId" value="">
                    
<div class="form-grid-2">
                        <div class="form-group">
                            <label for="nombre">Nombre de la Promoción *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej: Oferta de Verano">
                            <div class="field-error" id="error_nombre"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="valor">Valor *</label>
                            <input type="text" class="form-control" id="valor" name="valor" placeholder="Ej: 15 o 50">
                            <small style="color: #999;">Para % solo número, para monto número sin símbolo</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha de Inicio *</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_fin">Fecha de Fin *</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo">Tipo de Promoción *</label>
                            <select class="form-control" id="tipo" name="tipo" onchange="actualizarPlaceholderValor()">
                                <option value="">Seleccione tipo</option>
                                <option value="descuento">DESCUENTO</option>
                                <option value="porcentaje">PORCENTAJE</option>
                                <option value="2x1">2X1</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select class="form-control" id="estado" name="estado">
                                <option value="1">Activa</option>
                                <option value="0">Inactiva</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Descripción detallada de la promoción..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Productos Aplicables</label>
                        <div class="productos-select" id="productosContainer">
                            <?php foreach ($productos as $producto): ?>
                            <div class="producto-checkbox">
                                <input type="checkbox" name="productos[]" value="<?php echo $producto['id']; ?>" id="prod_<?php echo $producto['id']; ?>">
                                <label for="prod_<?php echo $producto['id']; ?>">
                                    <?php $precios = formatearMonedaDual($producto['precio_venta']); ?>
                                    <?php echo htmlspecialchars($producto['nombre']); ?> - <span class="moneda-bs"><?php echo $precios['bs']; ?></span> <span class="moneda-usd">(<?php echo $precios['usd']; ?>)</span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <small style="color: #999;">Selecciona los productos que aplican a esta promoción</small>
                    </div>

                    <div class="form-group">
                        <label>Imagen de Promoción (opcional)</label>
                        <div class="image-uploader">
                            <div class="uploader-controls">
                                <input type="file" id="imagen" name="imagen" accept="image/*" class="form-control" style="display:none;">
                                <button type="button" class="btn btn-secondary" id="selectPromoImageBtn">Seleccionar imagen</button>
                                <small style="color: #999; display:block; margin-top:6px;">Puedes subir una imagen personalizada o elegir una del banco automático.</small>
                            </div>

                            <div class="preview-list" id="promoImagePreview" style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;"></div>

                            <?php if (!empty($promo_bank_images)): ?>
                            <div class="promo-bank-container" style="margin-top:12px;">
                                <?php foreach ($promo_bank_images as $img): ?>
                                <label class="promo-bank" style="cursor:pointer; text-align:center;">
                                    <input type="radio" name="imagen_banco_key" value="<?php echo $img; ?>" style="display:none;">
                                    <img src="<?php echo BASE_URL; ?>/public/img/promo_bank/<?php echo $img; ?>" alt="<?php echo $img; ?>" onclick="selectBankImage(this)">
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Guardar Promoción
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Datos para gráfica de Promociones Activas
        const promoLabels = [];
        const promoData = [];
        <?php foreach ($ventas_tipo as $tipo): ?>
            promoLabels.push('<?php echo addslashes($tipo['promocion']); ?>');
            promoData.push(<?php echo $tipo['ventas']; ?>);
        <?php endforeach; ?>
        
        // Colores para gráfica
        const colores = ['#1F9166', '#3498db', '#9b59b6', '#e74c3c', '#f39c12', '#1abc9c'];

        // Gráfica de Promociones Activas
        if (promoLabels.length > 0) {
            const tipoPromocionesCtx = document.getElementById('tipoPromocionesChart').getContext('2d');
            const tipoPromocionesChart = new Chart(tipoPromocionesCtx, {
                type: 'bar',
                data: {
                    labels: promoLabels,
                    datasets: [{
                        label: 'Ventas',
                        data: promoData,
                        backgroundColor: colores.slice(0, promoLabels.length),
                        borderWidth: 2,
                        borderColor: '#fff',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        } else {
            document.getElementById('tipoPromocionesChart').parentNode.innerHTML = '<div style="text-align: center; padding: 40px;">No hay datos suficientes</div>';
        }

        // Gráfica de Ventas con vs sin Promoción
        const ventasPromoData = [<?php echo intval($ventas_promo['con_promo'] ?? 0); ?>, <?php echo intval($ventas_promo['sin_promo'] ?? 0); ?>];
        const ventasPromoLabels = ['Con Promoción', 'Sin Promoción'];
        
        const ventasPromoCtx = document.getElementById('ventasPromoChart').getContext('2d');
        new Chart(ventasPromoCtx, {
            type: 'doughnut',
            data: {
                labels: ventasPromoLabels,
                datasets: [{
                    data: ventasPromoData,
                    backgroundColor: ['#1F9166', '#6c757d'],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Funciones del modal
        function abrirModal() {
            document.getElementById('modalPromocion').classList.add('active');
            document.getElementById('formPromocion').reset();
            // limpiar preview y hidden existente
            const prev = document.getElementById('promoImagePreview'); if (prev) prev.innerHTML = '';
            const ex = document.getElementById('existing_imagen_url'); if (ex) ex.remove();
            document.getElementById('promocionId').value = '';
            document.getElementById('modalTitle').textContent = 'Nueva Promoción';
            
            // Establecer fechas por defecto
            const hoy = new Date().toISOString().split('T')[0];
            document.getElementById('fecha_inicio').value = hoy;
            
            const fechaFin = new Date();
            fechaFin.setDate(fechaFin.getDate() + 30);
            document.getElementById('fecha_fin').value = fechaFin.toISOString().split('T')[0];
        }
        
        function cerrarModal() {
            document.getElementById('modalPromocion').classList.remove('active');
        }

        // Abrir modal al hacer click en el botón "Nueva Promoción"
        document.getElementById('mostrarModalBtn')?.addEventListener('click', abrirModal);
        
        function actualizarPlaceholderValor() {
            const tipo = document.getElementById('tipo').value;
            const valorInput = document.getElementById('valor');
            
            if (tipo === 'descuento') {
                valorInput.placeholder = 'Ej: 15 (para 15%)';
            } else if (tipo === 'monto') {
                valorInput.placeholder = 'Ej: 50 (para $50 OFF)';
            } else if (tipo === 'envio') {
                valorInput.placeholder = '0 (Envío gratis)';
                valorInput.value = '0';
            } else {
                valorInput.placeholder = 'Valor de la promoción';
            }
        }
        
        // Guardar promoción
        function guardarPromocion(event) {
            event.preventDefault();
            
            const nombreInput = document.getElementById('nombre');
            const tipoInput = document.getElementById('tipo');
            const valorInput = document.getElementById('valor');
            const fechaInicioInput = document.getElementById('fecha_inicio');
            const fechaFinInput = document.getElementById('fecha_fin');
            
            // 1. Validar nombre obligatorio
            if (!nombreInput.value.trim()) {
                mostrarToast('El nombre de la promoción es obligatorio', 'error');
                nombreInput.focus();
                return false;
            }
            
            // 2. Validar tipo obligatorio
            if (!tipoInput.value) {
                mostrarToast('Debes seleccionar el tipo de promoción', 'error');
                tipoInput.focus();
                return false;
            }
            
            // 3. Validar valor obligatorio y positivo
            const valor = parseFloat(valorInput.value);
            if (isNaN(valor) || valor <= 0) {
                mostrarToast('El valor debe ser un número positivo', 'error');
                valorInput.focus();
                return false;
            }
            
            // 4. Validar porcentaje (0-100)
            if (tipoInput.value === 'porcentaje' && (valor < 0 || valor > 100)) {
                mostrarToast('El porcentaje debe estar entre 0 y 100', 'error');
                valorInput.focus();
                return false;
            }
            
            // 5. Validar fechas obligatorias
            if (!fechaInicioInput.value || !fechaFinInput.value) {
                mostrarToast('Las fechas de inicio y fin son obligatorias', 'error');
                return false;
            }
            
            // 6. Validar que fecha fin >= fecha inicio
            if (fechaFinInput.value < fechaInicioInput.value) {
                mostrarToast('La fecha de fin no puede ser menor a la fecha de inicio', 'error');
                return false;
            }
            
            // 7. Validar que se haya seleccionado al menos un producto
            const productosSeleccionados = document.querySelectorAll('input[name="productos[]"]:checked');
            if (productosSeleccionados.length === 0) {
                mostrarToast('Debes seleccionar al menos un producto', 'error');
                return false;
            }
            
            // Mostrar loading
            document.getElementById('loadingOverlay').classList.add('active');
            
            // Recopilar datos
            const fileInput = document.getElementById('imagen');
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

            // Datos básicos
            const baseData = {
                id: document.getElementById('promocionId').value,
                nombre: document.getElementById('nombre').value,
                tipo: document.getElementById('tipo').value,
                valor: document.getElementById('valor').value,
                fecha_inicio: document.getElementById('fecha_inicio').value,
                fecha_fin: document.getElementById('fecha_fin').value,
                estado: document.getElementById('estado').value,
                descripcion: document.getElementById('descripcion').value,
                productos: Array.from(productosSeleccionados).map(cb => cb.value)
            };

            function handleGuardarResponse(data) {
                document.getElementById('loadingOverlay').classList.remove('active');
                if (data && data.success) {
                    mostrarToast(data.message || 'Promoción guardada', 'success');
                    cerrarModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    mostrarToast((data && data.message) ? data.message : 'Error al guardar la promoción', 'error');
                }
            }

            if (hasFile) {
                const fd = new FormData();
                // Agregar campos al FormData, productos como arreglo
                for (const k in baseData) {
                    if (k === 'productos') {
                        baseData.productos.forEach(p => fd.append('productos[]', p));
                    } else {
                        fd.append(k, baseData[k]);
                    }
                }
                fd.append('imagen', fileInput.files[0]);

                fetch('<?php echo BASE_URL; ?>/api/guardar_promocion.php', {
                    method: 'POST',
                    body: fd
                })
                .then(response => response.json())
                .then(handleGuardarResponse)
                .catch(err => {
                    console.error(err);
                    document.getElementById('loadingOverlay').classList.remove('active');
                    mostrarToast('Error al guardar la promoción', 'error');
                });
            } else {
                // Ver si se seleccionó imagen del banco
                const selectedBank = document.querySelector('input[name="imagen_banco_key"]:checked');
                if (selectedBank) baseData.imagen_banco_key = selectedBank.value;

                fetch('<?php echo BASE_URL; ?>/api/guardar_promocion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(baseData)
                })
                .then(response => response.json())
                .then(handleGuardarResponse)
                .catch(err => {
                    console.error(err);
                    document.getElementById('loadingOverlay').classList.remove('active');
                    mostrarToast('Error al guardar la promoción', 'error');
                });
            }
            
            return false;
        }
        
        // Ver promoción — modal con datos de la fila (sin redirect)
        function verPromocion(id) {
            const row = document.querySelector('.table-row[data-id="' + id + '"]');
            if (!row) return;

            // Leer datos desde los data-* de la fila
            const nombre      = row.dataset.nombreOrig  || row.dataset.nombre || '—';
            const tipo        = row.dataset.tipoOrig    || row.dataset.tipo   || '—';
            const valor       = row.dataset.valor       || '—';
            const productos   = row.dataset.productos   || '0';
            const estadoText  = row.dataset.estadoText  || row.dataset.estado || '—';
            const estadoKey   = row.dataset.estado      || '';
            const fechaInicio = row.dataset.fechaInicio || '—';
            const fechaFin    = row.dataset.fechaFin    || '—';
            const descripcion = row.dataset.descripcion || '';
            const codigo      = row.querySelector('strong')?.textContent || ('PROM-' + String(id).padStart(6,'0'));

            // Clases de badge
            const badgeMap = {
                'activa':     'background:#e8f6f1;color:#1F9166;border:1px solid #a3cfbb;',
                'inactiva':   'background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;',
                'programada': 'background:#cce5ff;color:#004085;border:1px solid #b8daff;',
            };
            const badgeStyle = badgeMap[estadoKey] || badgeMap['inactiva'];

            // Crear modal si no existe
            let modal = document.getElementById('modalVerPromo');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'modalVerPromo';
                modal.className = 'modal-overlay';
                modal.innerHTML = `
                    <div style="background:#fff;border-radius:12px;width:560px;max-width:95%;max-height:90vh;overflow-y:auto;animation:modalFadeIn .3s;box-shadow:0 10px 30px rgba(0,0,0,.2);">
                        <div style="padding:18px 22px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;background:#1F9166;border-radius:12px 12px 0 0;">
                            <h3 style="margin:0;color:#fff;font-size:16px;display:flex;align-items:center;gap:8px;"><i class="fas fa-tag"></i> Detalle de Promoción</h3>
                            <button onclick="document.getElementById('modalVerPromo').classList.remove('active')" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:30px;height:30px;border-radius:50%;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
                        </div>
                        <div id="modalVerPromoBody" style="padding:24px;"></div>
                        <div style="padding:14px 22px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px;">
                            <button onclick="document.getElementById('modalVerPromo').classList.remove('active')" class="btn btn-secondary"><i class="fas fa-times"></i> Cerrar</button>
                        </div>
                    </div>`;
                modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('active'); });
                document.body.appendChild(modal);
            }

            document.getElementById('modalVerPromoBody').innerHTML = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px 24px;">
                    <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Código</div>
                         <div style="font-weight:700;color:#1F9166;">${codigo}</div></div>
                    <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Estado</div>
                         <span style="padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;${badgeStyle}">${estadoText}</span></div>
                    <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Nombre</div>
                         <div style="font-weight:600;">${nombre}</div></div>
                    <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Tipo</div>
                         <div>${tipo}</div></div>
                    <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Descuento / Valor</div>
                         <div style="font-weight:700;">${valor}</div></div>
                    <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Productos aplicados</div>
                         <div>${productos} producto(s)</div></div>
                    <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Fecha inicio</div>
                         <div>${fechaInicio}</div></div>
                    <div><div style="font-size:11px;color:#888;font-weight:600;margin-bottom:3px;">Fecha fin</div>
                         <div>${fechaFin}</div></div>
                    ${descripcion ? `<div style="grid-column:1/-1;border-top:1px solid #edf2f7;padding-top:14px;">
                        <div style="font-size:11px;color:#888;font-weight:600;margin-bottom:4px;">Descripción</div>
                        <div style="font-size:13px;color:#555;">${descripcion}</div>
                    </div>` : ''}
                </div>`;

            modal.classList.add('active');
        }
        
        // Editar promoción
        function editarPromocion(id) {
            document.getElementById('loadingOverlay').classList.add('active');
            
            fetch('<?php echo BASE_URL; ?>/api/obtener_promocion.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingOverlay').classList.remove('active');
                
                if (data.success) {
                    const promo = data.promocion;
                    
                    document.getElementById('promocionId').value = promo.id;
                    document.getElementById('nombre').value = promo.nombre;
                    // Mapear valor de BD (DESCUENTO/2X1/PORCENTAJE) al select que usa lowercase
                    const tipoMap = {
                        'DESCUENTO': 'descuento',
                        '2X1': '2x1',
                        'PORCENTAJE': 'porcentaje'
                    };
                    const tipoUpper = (promo.tipo_promocion || '').toString().toUpperCase();
                    document.getElementById('tipo').value = tipoMap[tipoUpper] || promo.tipo_promocion.toLowerCase();
                    document.getElementById('valor').value = promo.valor;
                    // Manejar imagenes: limpiar file input
                    const fileInput = document.getElementById('imagen');
                    if (fileInput) fileInput.value = '';
                    // Seleccionar imagen del banco si aplica
                    if (promo.tipo_imagen === 'auto' && promo.imagen_banco_key) {
                        const radio = document.querySelector('input[name="imagen_banco_key"][value="' + promo.imagen_banco_key + '"]');
                        if (radio) {
                            // quitar clase selected de todas
                            document.querySelectorAll('label.promo-bank').forEach(l => l.classList.remove('selected'));
                            radio.checked = true;
                            const lab = radio.closest('label.promo-bank');
                            if (lab) lab.classList.add('selected');
                        }
                    } else {
                        // desmarcar radios y clases
                        document.querySelectorAll('input[name="imagen_banco_key"]').forEach(r => { r.checked = false; const l = r.closest('label.promo-bank'); if (l) l.classList.remove('selected'); });
                        // Si la promoción tiene una imagen (manual), mostrar preview y mantener valor existente en hidden
                        if (promo.imagen_url) {
                            renderPromoUrlPreview(promo.imagen_url);
                            let ex = document.getElementById('existing_imagen_url');
                            if (!ex) {
                                ex = document.createElement('input');
                                ex.type = 'hidden';
                                ex.id = 'existing_imagen_url';
                                ex.name = 'imagen_existente';
                                document.getElementById('formPromocion').appendChild(ex);
                            }
                            ex.value = promo.imagen_url;
                        } else {
                            const ex = document.getElementById('existing_imagen_url'); if (ex) ex.value = '';
                        }
                    }
                    document.getElementById('fecha_inicio').value = promo.fecha_inicio;
                    document.getElementById('fecha_fin').value = promo.fecha_fin;
                    document.getElementById('estado').value = promo.estado ? '1' : '0';
                    document.getElementById('descripcion').value = promo.descripcion || '';
                    
                    // Seleccionar productos
                    document.querySelectorAll('input[name="productos[]"]').forEach(cb => {
                        cb.checked = data.productos.includes(parseInt(cb.value));
                    });
                    
                    document.getElementById('modalTitle').textContent = 'Editar Promoción';
                    document.getElementById('modalPromocion').classList.add('active');
                } else {
                    mostrarToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loadingOverlay').classList.remove('active');
                mostrarToast('Error al cargar la promoción', 'error');
            });
        }
        
        // Eliminar promoción
        function eliminarPromocion(id) {
            if (!confirm('¿Estás seguro de eliminar esta promoción?')) return;
            
            document.getElementById('loadingOverlay').classList.add('active');
            
            fetch('<?php echo BASE_URL; ?>/api/eliminar_promocion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingOverlay').classList.remove('active');
                
                if (data.success) {
                    mostrarToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    mostrarToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loadingOverlay').classList.remove('active');
                mostrarToast('Error al eliminar la promoción', 'error');
            });
        }
        
        // Filtrar próximas a vencer
        function filtrarProximasVencer() {
            document.getElementById('estadoFilter').value = 'activa';
            aplicarFiltros();
        }
        
        // Aplicar filtros con llamada a API
        // Filtrar filas del DOM existentes — sin fetch ni destrucción de HTML
        function aplicarFiltros() {
            const term    = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();
            const estado  = (document.getElementById('estadoFilter')?.value || '').toLowerCase();
            const tipo    = (document.getElementById('tipoFilter')?.value || '').toLowerCase();

            // Determinar rango de fechas
            let desde = '', hasta = '';
            const dateFilter = document.getElementById('dateFilter')?.value || '';
            const hoy = new Date();
            const hoyISO = hoy.toISOString().slice(0, 10);

            if (dateFilter === 'today') {
                desde = hasta = hoyISO;
            } else if (dateFilter === 'this_week') {
                const inicioSemana = new Date(hoy);
                inicioSemana.setDate(hoy.getDate() - hoy.getDay());
                desde = inicioSemana.toISOString().slice(0, 10);
                hasta = hoyISO;
            } else if (dateFilter === 'this_month') {
                desde = `${hoy.getFullYear()}-${(hoy.getMonth()+1).toString().padStart(2,'0')}-01`;
                hasta = hoyISO;
            } else if (dateFilter === 'next_30') {
                desde = hoyISO;
                const en30 = new Date(hoy);
                en30.setDate(hoy.getDate() + 30);
                hasta = en30.toISOString().slice(0, 10);
            } else if (dateFilter === 'custom') {
                desde = document.getElementById('dateFrom')?.value || '';
                hasta = document.getElementById('dateTo')?.value || '';
            }

            let visibles = 0;
            document.querySelectorAll('#promocionesList .table-row[data-id]').forEach(row => {
                const texto     = row.textContent.toLowerCase();
                const rowEstado = (row.dataset.estado || '').toLowerCase();
                const rowTipo   = (row.dataset.tipo   || '').toLowerCase();
                // Fecha: extraída del texto de la celda col-fecha (formato DD/MM/YYYY)
                const fechaTxt   = (row.querySelector('.col-fecha')?.textContent || '').trim();
                let fechaISO     = '';
                if (fechaTxt && fechaTxt !== '—') {
                    const parts = fechaTxt.split('/');
                    if (parts.length === 3) {
                        fechaISO = `${parts[2]}-${parts[1].padStart(2,'0')}-${parts[0].padStart(2,'0')}`;
                    }
                }

                let show = true;
                if (term   && !texto.includes(term))                           show = false;
                if (estado && rowEstado !== estado)                           show = false;
                if (tipo   && !rowTipo.includes(tipo))                       show = false;
                if (desde  && fechaISO && fechaISO < desde)                  show = false;
                if (hasta  && fechaISO && fechaISO > hasta)                  show = false;

                row.style.display = show ? '' : 'none';
                if (show) visibles++;
            });

            const noRes = document.getElementById('noResultsPromo');
            if (noRes) noRes.style.display = visibles === 0 ? 'block' : 'none';
        }

        function applyCustomDate() { aplicarFiltros(); }
        
        // Funciones auxiliares
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
        
        // Event listeners para filtros
        let _searchTimer = null;
        document.getElementById('searchInput').addEventListener('keyup', function() {
            clearTimeout(_searchTimer);
            _searchTimer = setTimeout(aplicarFiltros, 300);
        });
        document.getElementById('estadoFilter').addEventListener('change', aplicarFiltros);
        document.getElementById('tipoFilter').addEventListener('change', aplicarFiltros);
        
        // Event listener para dateFilter
        const dateFilter = document.getElementById('dateFilter');
        if (dateFilter) {
            dateFilter.addEventListener('change', function() {
                const customRange = document.getElementById('customDateRange');
                if (customRange) {
                    customRange.style.display = this.value === 'custom' ? 'block' : 'none';
                }
                aplicarFiltros();
            });
        }
        
        // Función para mostrar toasts
        function mostrarToast(mensaje, tipo = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${tipo}`;
            
            const icono = tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            toast.innerHTML = `
                <i class="fas ${icono}"></i>
                <div class="toast-content">
                    <div class="toast-message">${mensaje}</div>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 5000);
        }

        // Seleccionar imagen del banco (marca el radio y resalta)
        function selectBankImage(imgEl) {
            const label = imgEl.closest('label.promo-bank');
            if (!label) return;
            const radio = label.querySelector('input[type="radio"]');
            if (!radio) return;
            radio.checked = true;
            // limpiar seleccion visual
            document.querySelectorAll('label.promo-bank').forEach(l => l.classList.remove('selected'));
            label.classList.add('selected');
            // limpiar file input when choosing bank image
            const fileInput = document.getElementById('imagen');
            if (fileInput) fileInput.value = '';
            // limpiar existing_imagen_url si existe (se eligió otra imagen)
            const ex = document.getElementById('existing_imagen_url'); if (ex) ex.value = '';
        }

        // Limpiar selección de banco si se carga un archivo
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('imagen');
            if (fileInput) {
                fileInput.addEventListener('change', () => {
                    if (fileInput.files && fileInput.files.length > 0) {
                        document.querySelectorAll('input[name="imagen_banco_key"]').forEach(r => { r.checked = false; const l = r.closest('label.promo-bank'); if (l) l.classList.remove('selected'); });
                        // mostrar preview de la imagen seleccionada
                        renderPromoFilePreview(fileInput.files[0]);
                    }
                });
            }
            // botón para abrir selector de archivo
            const selectBtn = document.getElementById('selectPromoImageBtn');
            if (selectBtn) {
                selectBtn.addEventListener('click', () => {
                    const fi = document.getElementById('imagen');
                    if (fi) fi.click();
                });
            }
        });

        // Render preview for selected File
        function renderPromoFilePreview(file) {
            const preview = document.getElementById('promoImagePreview');
            if (!preview) return;
            preview.innerHTML = '';
            const url = URL.createObjectURL(file);
            const item = document.createElement('div');
            item.className = 'image-preview-item';
            item.innerHTML = `<img src="${url}" alt="preview"><div class="remove-btn" title="Eliminar">&times;</div>`;
            preview.appendChild(item);
            item.querySelector('.remove-btn').addEventListener('click', () => {
                const fi = document.getElementById('imagen');
                if (fi) { fi.value = ''; }
                item.remove();
                URL.revokeObjectURL(url);
            });
        }

        // Render preview from existing URL (used when editing)
        function renderPromoUrlPreview(url) {
            const preview = document.getElementById('promoImagePreview');
            if (!preview) return;
            preview.innerHTML = '';
            const item = document.createElement('div');
            item.className = 'image-preview-item';
            item.innerHTML = `<img src="${url}" alt="preview"><div class="remove-btn" title="Eliminar">&times;</div>`;
            preview.appendChild(item);
            item.querySelector('.remove-btn').addEventListener('click', () => {
                // marcar para eliminar imagen existente
                const existingInput = document.getElementById('existing_imagen_url');
                if (existingInput) existingInput.value = '';
                item.remove();
            });
        }

        // Inhabilitar / Habilitar promoción
        function inhabilitarPromocion(id) {
            if (!confirm('¿Seguro que desea cambiar el estado de esta promoción?')) return;
            document.getElementById('loadingOverlay').classList.add('active');
            fetch('<?php echo BASE_URL; ?>/api/toggle_promocion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('loadingOverlay').classList.remove('active');
                if (data.success) {
                    mostrarToast(data.message || 'Estado actualizado', 'success');
                    setTimeout(() => location.reload(), 700);
                } else {
                    mostrarToast(data.message || 'Error al cambiar estado', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('loadingOverlay').classList.remove('active');
                mostrarToast('Error en la petición', 'error');
            });
        }
    </script>
</body>
</html>