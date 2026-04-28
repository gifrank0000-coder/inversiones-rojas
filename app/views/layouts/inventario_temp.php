<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';
$base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// Cargar conexión a la base de datos y lista de proveedores activos
require_once __DIR__ . '/../../models/database.php';
$pdo = Database::getInstance();
$stmt = $pdo->query("SELECT id, razon_social FROM proveedores WHERE estado = true ORDER BY razon_social");
$proveedores = $stmt->fetchAll();

// Listado de categorías para filtro de inventario
$stmt = $pdo->query("SELECT id, nombre FROM categorias WHERE estado = true ORDER BY nombre");
$categorias = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario - Inversiones Rojas</title>
    <script>
        var APP_BASE = '<?php echo $base_url; ?>';
        var TASA_CAMBIO = <?php echo getTasaCambio(); ?>;
        var CATEGORIAS = <?php echo json_encode($categorias, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); ?>;
        var PROVEEDORES = <?php echo json_encode($proveedores, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE); ?>;
        console.log('Tasa de cambio cargada:', TASA_CAMBIO);
        console.log('CATEGORIAS disponibles:', CATEGORIAS.length);
        console.log('PROVEEDORES disponibles:', PROVEEDORES.length);
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/layouts/inventario.css">
    <!-- Sistema de notificaciones y validaciones personalizadas -->
    <script src="<?php echo $base_url; ?>/public/js/inv-notifications.js"></script>
    <style>
        /* Estilos para moneda dual en stats (stat-card) */
        .inventory-stats .stat-card .moneda-usd {
            margin: 0 0 5px 0;
            font-size: 1.8rem;
            color: #2c3e50;
            font-weight: 700;
            display: block;
        }
        .inventory-stats .stat-card .moneda-bs {
            margin: 0 0 8px 0;
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
            display: block;
        }

        /* Estilos para moneda en tabla de inventario (como total en compras) */
        .inventory-table .moneda-bs,
        .inventory-table .moneda-usd {
          
          
            display: block;
            
        }

        .inventory-table .moneda-usd {
      color: #1F9166;
    font-weight: 600;
        }

        .inventory-table .moneda-bs {
          color: #6c757d;
          font-size: 0.9em;
        }

        /* Estilos adicionales para modales */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 12px;
            width: 650px;
            max-width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        /* ========== ESTILOS PARA EL MODAL DE REGISTRO DE PROVEEDOR ========== */
        .modal-overlay.registro-modal {
            z-index: 10000;
        }

        .modal.registro-modal {
            width: 500px;
            max-width: 95%;
            max-height: 85vh;
            overflow-y: auto;
        }

        .modal.registro-modal .modal-header {
            background: #f8f9fa;
            color: #333;
            border-radius: 16px 16px 0 0;
        }

        .modal.registro-modal .modal-close {
            background: rgba(0, 0, 0, 0.1);
            color: #666;
        }

        .modal.registro-modal .modal-close:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        .modal.registro-modal .modal-body {
            min-height: auto;
            padding: 25px;
            display: block;
        }

        .modal.registro-modal .modal-footer {
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            border-radius: 12px 12px 0 0;
        }

        .modal-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 18px;
        }

        .modal-header h3 i {
            color: #1F9166;
        }

        .modal-close {
            background: none;
            border: none;
            color: #666;
            font-size: 28px;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .modal-close:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #eaeaea;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #1F9166;
            box-shadow: 0 0 0 2px rgba(31, 145, 102, 0.1);
        }

        /* Estilos para la sección de filtros */
        .filters-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .filter-date {
            max-width: 150px;
        }

        /* Miniaturas de producto en la tabla */
        .prod-thumb {
            width: 56px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
            display: inline-block;
        }
        
        .prod-thumb.placeholder {
            display:flex;
            align-items:center;
            justify-content:center;
            background:#f0f0f0;
            color:#666;
            font-size:12px;
        }
        
        .field-error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }
        
        .field-error.active {
            display: block;
        }
        
        /* Estilos adicionales para el sistema de pasos */
        body, button, input, select, textarea {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Step indicator */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
            padding: 0 10px;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 16px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            background: white;
            padding: 0 5px;
        }
        
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .step.active .step-circle {
            background: #1F9166;
            color: white;
            border-color: #1F9166;
        }
        
        .step-label {
            font-size: 12px;
            color: #666;
            text-align: center;
            max-width: 80px;
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: #1F9166;
            font-weight: 600;
        }
        
        .step.completed .step-circle {
            background: #1F9166;
            color: white;
        }
        
        /* Type selection */
        .type-selection {
            text-align: center;
            padding: 20px 0;
        }
        
        .type-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .type-btn {
            padding: 25px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        
        .type-btn:hover {
            border-color: #1F9166;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(31, 145, 102, 0.15);
        }
        
        .type-btn.active {
            border-color: #1F9166;
            background: #f0f9f5;
        }
        
        .type-btn i {
            font-size: 36px;
            color: #1F9166;
        }
        
        .type-btn span {
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }
        
        .type-description {
            font-size: 11px;
            color: #666;
            text-align: center;
            margin-top: 5px;
            line-height: 1.4;
        }
        
        /* Especificaciones */
        .specifications-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
        }
        
        .specifications-section h5 {
            margin: 0 0 20px 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .specifications-section h5 i {
            color: #1F9166;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        /* Summary section */
        .summary-content {
            background: white;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            margin-bottom: 25px;
        }
        
        .summary-section {
            margin-bottom: 25px;
        }
        
        .summary-section h6 {
            margin: 0 0 15px 0;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            font-size: 15px;
            font-weight: 600;
        }
        
        .summary-section h6 i {
            color: #1F9166;
            margin-right: 8px;
        }
        
        .summary-table {
            width: 100%;
            font-size: 14px;
        }
        
        .summary-table tr {
            border-bottom: 1px solid #f5f5f5;
        }
        
        .summary-table td {
            padding: 10px 0;
            vertical-align: top;
        }
        
        .summary-table td:first-child {
            color: #666;
            font-weight: 500;
            width: 40%;
            padding-right: 15px;
        }
        
        .summary-table td:last-child {
            color: #333;
            font-weight: 500;
        }
        
        /* Navigation buttons */
        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navigation-left {
            display: flex;
            gap: 10px;
        }
        
        .navigation-right {
            display: flex;
            gap: 10px;
        }
        
        /* Botones */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        
        .btn-primary {
            background: #1F9166;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1a7c56;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* botón de reportes gris */
        .btn-report {
            background: #6c757d;
            color: white;
        }
        .btn-report:hover {
            background: #5a6268;
        }
        
        /* tarjetas dentro del modal de reportes */
        #reportsModal .modal-body {
            display: block !important;
            max-height: 70vh;
            overflow-y: auto;
            padding: 20px;
        }
        .report-card {
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
            max-width: 100%;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .report-card:hover {
            border-color: #6c757d;
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .report-card.selected {
            background: #6c757d;
            color: white;
            border-color: #6c757d;
            box-shadow: 0 4px 12px rgba(108,117,125,0.3);
        }
        .report-card i {
            font-size: 24px;
            width: 30px;
            text-align: center;
        }
        .report-card strong {
            display: block;
            margin-bottom: 4px;
            font-size: 16px;
        }
        .report-card p {
            margin: 0;
            font-size: 14px;
            opacity: 0.8;
        }
        .report-card.selected p {
            opacity: 0.9;
        }
        .modal-footer .btn-report {
            background: #1F9166;
            color: white;
        }
        .modal-footer .btn-report:hover {
            background: #159652;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #ddd;
            color: #333;
        }
        
        .btn-outline:hover {
            background: #f8f9fa;
            border-color: #1F9166;
            color: #1F9166;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* Mensaje final */
        .final-message {
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .final-message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .final-message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        /* Estilos de validación visual */
        .form-control.error {
            border-color: #e74c3c;
        }
        
        .form-control.valid {
            border-color: #1F9166;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .type-buttons {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .modal {
                width: 95%;
                margin: 10px;
            }
            
            .step-indicator {
                flex-wrap: wrap;
                gap: 15px;
                justify-content: center;
            }
            
            .step {
                flex: 1;
                min-width: 80px;
            }
            
            .step-indicator::before {
                display: none;
            }
            
            .navigation-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .navigation-left, .navigation-right {
                width: 100%;
                justify-content: center;
            }
        }
        
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Estados en la tabla */
        .status-active {
            color: #1F9166;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .actions-cell {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .prod-image-cell {
            width: 80px;
        }
        
        .loading-message {
            text-align: center;
            color: #666;
        }
        
        .error-message {
            text-align: center;
            color: #e74c3c;
        }

        /* Para selects con botón + */
        .select-with-btn {
            display: flex;
            gap: 8px;
            align-items: stretch;
        }

        .select-with-btn select {
            flex: 1;
        }

        .select-with-btn .btn-add {
            flex-shrink: 0;
            width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Preview de imágenes */
        .preview-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .image-preview-item {
            position: relative;
            width: 80px;
            height: 80px;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }

        .image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview-item .remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(231, 76, 60, 0.9);
            color: white;
            border: none;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }

        .image-preview-item .remove-btn:hover {
            background: rgb(231, 76, 60);
        }

        /* Mensajes en formularios */
        .form-hint {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
            font-style: italic;
        }
        
        /* Tooltips para información de stock */
        .stock-info-tooltip {
            position: relative;
            display: inline-block;
            cursor: help;
        }
        
        .stock-info-tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }
        
        .stock-info-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* Alertas de stock */
        .alert-card {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .alert-card.warning {
            background: #fff3cd;
            border-color: #ffeaa7;
        }
        
        .alert-card.danger {
            background: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .alert-icon {
            font-size: 24px;
            color: #ffc107;
        }
        
        .alert-icon.danger {
            color: #dc3545;
        }
        
        /* Animación para notificaciones */
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        /* Resalte visual para filas con stock bajo */
        tr.low-stock {
            background-color: #fff8e1 !important;
            border-left: 4px solid #f1c40f;
        }

        .low-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            margin-right: 8px;
            background: #f1c40f;
            color: #3a3a3a;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="admin-content">
        <!-- Stats Cards -->
        <div class="inventory-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-info">
                    <h3 id="stat-total-products"><?php echo $stats['total_products'] ?? 0; ?></h3>
                    <p>Productos Totales</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3 id="stat-low-stock"><?php echo $stats['low_stock'] ?? 0; ?></h3>
                    <p>Stock Bajo</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3 id="stat-out-of-stock"><?php echo $stats['out_of_stock'] ?? 0; ?></h3>
                    <p>Sin Stock</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <h3 id="stat-total-value">
                                <?php 
                                $precios = formatearMonedaDual($stats['total_value'] ?? 0);
                                echo '<span class="moneda-usd">' . $precios['usd'] . '</span>';
                                echo '<span class="moneda-bs">' . $precios['bs'] . '</span>';
                                ?>
                            </h3>
                    <p>Valor Total</p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <!-- Distribución por Tipo de Producto -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Productos por Tipo</h3>
                </div>
                <div class="chart-wrapper">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>

            <!-- Movimiento de Stock -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Movimiento de Stock</h3>
                    <div class="chart-actions">
                        <select class="chart-filter" id="stockPeriodFilter">
                            <option value="3">Últimos 3 meses</option>
                            <option value="6">Últimos 6 meses</option>
                            <option value="12">Este año</option>
                        </select>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="stockMovementChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="search-filters">
            <div class="search-box" style="display:flex; gap:8px; align-items:center;">
                <input id="inventorySearchInput" type="search" placeholder="Buscar productos..." style="flex:1; min-width:160px;" />

                <select id="typeFilter" class="filter-select" style="min-width:140px;">
                    <option value="">Todos los tipos</option>
                    <option value="vehiculo">Vehículos</option>
                    <option value="repuesto">Repuestos</option>
                    <option value="accesorio">Accesorios</option>
                </select>

                <select id="categoryFilter" class="filter-select" style="min-width:160px;">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria['id']; ?>">
                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="dateFilter" name="date_range" class="filter-select">
                    <option value="">Todas las fechas</option>
                    <option value="today">Hoy</option>
                    <option value="yesterday">Ayer</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mes</option>
                    <option value="last_month">Mes anterior</option>
                    <option value="custom">Rango personalizado</option>
                </select>

                <select id="stateFilter" class="filter-select" style="min-width:140px;">
                    <option value="all">Todos los estados</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </select>
                
                <button id="refreshBtn" class="btn btn-outline">
                    <i class="fas fa-redo"></i> Actualizar
                </button>
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
                    <button type="button" onclick="applyCustomDate()" style="margin-top: 22px; padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                        Aplicar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="inventory-actions">
            <div class="action-buttons">
                <button class="btn btn-primary" id="addProductBtn">
                    <i class="fas fa-plus"></i>
                    Agregar Producto
                </button>
        
                <button class="btn btn-report" id="openReportsModalBtn">
                    <i class="fas fa-chart-bar"></i>
                    Reportes
                </button>
            </div>
        </div>

        <!-- TABLA DE INVENTARIO -->
        <div class="inventory-table">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                        <th>Stock Máximo</th>
                        <th>Precio Compra</th>
                        <th>Precio Venta</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="inventoryRows">
                    <!-- Las filas se cargarán dinámicamente aquí -->
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 40px;">
                            <div class="loading-message">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p style="margin-top: 10px;">Cargando productos...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Alertas -->
        <div class="alerts-section" id="inventoryAlerts">
            <!-- Alertas dinámicas aparecerán aquí -->
        </div>
    </div>

    <!-- Modal: Nuevo Producto (Sistema de Pasos) -->
    <div class="modal-overlay" id="addProductModal">
        <div class="modal" style="width: 700px;">
            <div class="modal-header">
                <h3><i class="fas fa-box-open"></i> Nuevo Producto</h3>
                <button class="modal-close" id="closeAddProductModal" type="button">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Indicador de pasos -->
                <div class="step-indicator">
                    <div class="step active" id="step1-indicator">
                        <div class="step-circle">1</div>
                        <div class="step-label">Tipo</div>
                    </div>
                    <div class="step" id="step2-indicator">
                        <div class="step-circle">2</div>
                        <div class="step-label">Información</div>
                    </div>
                    <div class="step" id="step3-indicator">
                        <div class="step-circle">3</div>
                        <div class="step-label">Especificaciones</div>
                    </div>
                    <div class="step" id="step4-indicator">
                        <div class="step-circle">4</div>
                        <div class="step-label">Resumen</div>
                    </div>
                </div>

                <!-- Paso 1: Selección de Tipo -->
                <div class="form-step active" id="step1">
                    <div class="type-selection">
                        <h4 style="margin-bottom: 25px; color: #333; font-weight: 600;">Selecciona el tipo de producto</h4>
                        <div class="type-buttons">
                            <button class="type-btn" data-type="vehiculo">
                                <i class="fas fa-motorcycle"></i>
                                <span>Vehículo</span>
                                <div class="type-description">Motos, cuatrimotos, scooters y similares</div>
                            </button>
                            <button class="type-btn" data-type="repuesto">
                                <i class="fas fa-cog"></i>
                                <span>Repuesto</span>
                                <div class="type-description">Piezas, componentes y partes</div>
                            </button>
                            <button class="type-btn" data-type="accesorio">
                                <i class="fas fa-helmet-safety"></i>
                                <span>Accesorio</span>
                                <div class="type-description">Cascos, chaquetas, protección</div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Información General -->
                <div class="form-step" id="step2">
                    <form id="generalProductForm">
                        <div class="form-group">
                            <label for="prodCodigo">Código Interno *</label>
                            <input id="prodCodigo" name="codigo_interno" class="form-control" readonly 
                                   title="Código generado automáticamente" placeholder="Se generará automáticamente" />
                            <div class="field-error" id="err_prodCodigo"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="prodNombre">Nombre del Producto *</label>
                            <input id="prodNombre" name="nombre" class="form-control" 
                                   placeholder="Ej: Moto Bera BR 200, Casco LS2, Freno trasero" />
                            <div class="field-error" id="err_prodNombre"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="prodDescripcion">Descripción</label>
                            <textarea id="prodDescripcion" name="descripcion" class="form-control" rows="3"
                                      placeholder="Descripción detallada del producto..."></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="prodStock">Stock Inicial *</label>
                                <input id="prodStock" name="stock_actual" type="number" class="form-control" 
                                       value="0" min="0" placeholder="Ej: 10" />
                                <div class="field-error" id="err_prodStock"></div>
                            </div>
                            <div class="form-group">
                                <label for="prodStockMin">Stock Mínimo *</label>
                                <input id="prodStockMin" name="stock_minimo" type="number" class="form-control" 
                                       value="5" min="0" placeholder="Ej: 5" />
                                <div class="field-error" id="err_prodStockMin"></div>
                            </div>
                            <div class="form-group">
                                <label for="prodStockMax">Stock Máximo *</label>
                                <input id="prodStockMax" name="stock_maximo" type="number" class="form-control" 
                                       value="100" min="0" placeholder="Ej: 100" />
                                <div class="field-error" id="err_prodStockMax"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="prodPrecioCompra">Precio Compra (USD) *</label>
                                <input id="prodPrecioCompra" name="precio_compra" type="number" step="0.01" 
                                       class="form-control" value="0.00" min="0" 
                                       placeholder="Ej: 1500.00" />
                                <small style="color: #666;">Precio en dólares</small>
                                <div class="field-error" id="err_prodPrecioCompra"></div>
                            </div>
                            <div class="form-group">
                                <label>Precio Compra (Bs)</label>
                                <input id="prodPrecioCompraBs" type="number" step="0.01" 
                                       class="form-control" value="0.00" min="0" 
                                       placeholder="Ej: 52500.00" />
                                <small style="color: #666;">Se calcula automáticamente con la tasa</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="prodPrecioVenta">Precio Venta (USD) *</label>
                                <input id="prodPrecioVenta" name="precio_venta" type="number" step="0.01" 
                                       class="form-control" value="0.00" min="0" 
                                       placeholder="Ej: 1800.00" />
                                <small style="color: #666;">Precio en dólares</small>
                                <div class="field-error" id="err_prodPrecioVenta"></div>
                            </div>
                            <div class="form-group">
                                <label>Precio Venta (Bs)</label>
                                <input id="prodPrecioVentaBs" type="number" step="0.01" 
                                       class="form-control" value="0.00" min="0" 
                                       placeholder="Ej: 63000.00" />
                                <small style="color: #666;">Se calcula automáticamente con la tasa</small>
                            </div>
                        </div>   
                        <div class="form-group">
                            <label>Proveedores del producto</label>
                            <p class="form-hint" style="margin-bottom: 10px; color: #666; font-size: 12px;">
                                <i class="fas fa-info-circle"></i> Selecciona todos los proveedores que pueden surtir este producto
                            </p>
                            
                            <!-- Contenedor de proveedores -->
                            <div id="proveedores-container">
                                <!-- Primer proveedor (obligatorio) -->
                                <div class="proveedor-row" style="background: #f9f9f9; padding: 12px; border-radius: 6px; margin-bottom: 8px; border: 1px solid #e0e0e0;">
                                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                        <select name="proveedores[0][id]" class="form-control" style="min-width: 250px; flex: 2;">
                                            <option value="">-- Selecciona un proveedor --</option>
                                            <?php foreach ($proveedores as $proveedor): ?>
                                                <option value="<?php echo $proveedor['id']; ?>">
                                                    <?php echo htmlspecialchars($proveedor['razon_social']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        
                                        <div style="display: flex; align-items: center; gap: 8px; min-width: 140px;">
                                            <input type="radio" name="proveedor_principal" 
                                                id="prov_principal_0" value="0" checked>
                                            <label for="prov_principal_0" style="font-size: 13px; margin: 0;">Principal</label>
                                            
                                            <button type="button" class="remove-proveedor-btn" 
                                                style="background: none; border: none; color: #ccc; cursor: not-allowed; font-size: 18px; margin-left: 10px;"
                                                disabled title="No se puede eliminar">×</button>
                                        </div>
                                    </div>
                                    <div style="font-size: 11px; color: #999; margin-top: 5px;">
                                        Este proveedor usará el precio de compra configurado en el producto
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botón para agregar más proveedores -->
                            <button type="button" id="add-proveedor-btn" class="btn btn-outline btn-sm" style="margin-top: 10px;">
                                <i class="fas fa-plus"></i> Agregar otro proveedor
                            </button>
                            
                            <!-- Botón para crear nuevo proveedor -->
                            <div style="margin-top: 15px;">
                                <button type="button" class="btn btn-outline btn-add" id="addSupplierBtnProd">
                                    <i class="fas fa-plus"></i> Crear nuevo proveedor
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Paso 3: Vehículo -->
                <div class="form-step" id="step3-vehiculo">
                    <div class="specifications-section">
                        <h5><i class="fas fa-motorcycle"></i> Especificaciones del Vehículo</h5>
                        <form id="vehiculoForm">
                            <div class="form-group">
                                <label for="vehiculoCategorySelect">Categoría de Moto *</label>
                                <div class="select-with-btn">
                                    <select id="vehiculoCategorySelect" name="categoria_id" class="form-control" required>
                                        <option value="">-- Selecciona una categoría --</option>
                                        <?php foreach ($categoriasPorTipo['VEHICULO'] as $categoria): ?>
                                            <option value="<?php echo $categoria['id']; ?>">
                                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline btn-add" onclick="showAddCategoryModal('vehiculo')" 
                                            title="Agregar nueva categoría">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div class="field-error" id="err_vehiculoCategorySelect"></div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="vehiculoMarca">Marca *</label>
                                    <input id="vehiculoMarca" name="marca" class="form-control" 
                                           placeholder="Ej: Bera, Yamaha, Honda" />
                                    <div class="field-error" id="err_vehiculoMarca"></div>
                                </div>
                                <div class="form-group">
                                    <label for="vehiculoModelo">Modelo *</label>
                                    <input id="vehiculoModelo" name="modelo" class="form-control" 
                                           placeholder="Ej: BR 200, YZF-R3, CBR 250" />
                                    <div class="field-error" id="err_vehiculoModelo"></div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="vehiculoAnio">Año *</label>
                                    <input id="vehiculoAnio" name="anio" type="number" min="1900" max="2099" 
                                           class="form-control" placeholder="Ej: 2024" />
                                    <div class="field-error" id="err_vehiculoAnio"></div>
                                </div>
                                <div class="form-group">
                                    <label for="vehiculoCilindrada">Cilindrada *</label>
                                    <input id="vehiculoCilindrada" name="cilindrada" class="form-control" 
                                           placeholder="Ej: 200cc, 300cc, 450cc" />
                                    <div class="field-error" id="err_vehiculoCilindrada"></div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="vehiculoColor">Color *</label>
                                    <input id="vehiculoColor" name="color" class="form-control" 
                                           placeholder="Ej: Rojo, Negro, Blanco" />
                                    <div class="field-error" id="err_vehiculoColor"></div>
                                </div>
                                <div class="form-group">
                                    <label for="vehiculoKilometraje">Kilometraje (km)</label>
                                    <input id="vehiculoKilometraje" name="kilometraje" type="number" 
                                           class="form-control" value="0" min="0" placeholder="Ej: 0 (para nuevo)" />
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="vehiculoImages">Imágenes del Vehículo</label>
                                <div class="image-uploader" id="vehiculoImageUploader">
                                    <div class="uploader-controls">
                                        <button type="button" class="btn btn-outline" id="selectVehiculoImagesBtn">
                                            <i class="fas fa-image"></i> Seleccionar imágenes
                                        </button>
                                        <div class="form-hint">Puedes subir hasta 6 imágenes (máx. 5MB cada una). La primera será la principal.</div>
                                    </div>
                                    <input id="vehiculoImages" name="vehiculo_images[]" type="file" accept="image/*" multiple style="display:none;" />
                                    <div class="preview-list" id="vehiculoPreviewList"></div>
                                    <div class="field-error" id="err_vehiculoImages"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Paso 3: Repuesto -->
                <div class="form-step" id="step3-repuesto">
                    <div class="specifications-section">
                        <h5><i class="fas fa-cog"></i> Especificaciones del Repuesto</h5>
                        <form id="repuestoForm">
                            <div class="form-group">
                                <label for="repuestoCategorySelect">Categoría del Repuesto *</label>
                                <div class="select-with-btn">
                                    <select id="repuestoCategorySelect" name="categoria_id" class="form-control" required>
                                        <option value="">-- Selecciona una categoría --</option>
                                        <?php foreach ($categoriasPorTipo['REPUESTO'] as $categoria): ?>
                                            <option value="<?php echo $categoria['id']; ?>">
                                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline btn-add" onclick="showAddCategoryModal('repuesto')" 
                                            title="Agregar nueva categoría">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div class="field-error" id="err_repuestoCategorySelect"></div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="repuestoCategoriaTecnica">Categoría Técnica *</label>
                                    <input id="repuestoCategoriaTecnica" name="categoria_tecnica" class="form-control" 
                                           placeholder="Ej: Frenos, Motor, Suspensión" />
                                    <div class="field-error" id="err_repuestoCategoriaTecnica"></div>
                                </div>
                                <div class="form-group">
                                    <label for="repuestoMarcaCompatible">Marca Compatible *</label>
                                    <input id="repuestoMarcaCompatible" name="marca_compatible" class="form-control" 
                                           placeholder="Ej: Bera, Honda, Yamaha" />
                                    <div class="field-error" id="err_repuestoMarcaCompatible"></div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="repuestoModeloCompatible">Modelo Compatible</label>
                                    <input id="repuestoModeloCompatible" name="modelo_compatible" class="form-control" 
                                           placeholder="Ej: BR 200, CBR 250" />
                                </div>
                                <div class="form-group">
                                    <label for="repuestoAnioCompatible">Año Compatible</label>
                                    <input id="repuestoAnioCompatible" name="anio_compatible" class="form-control" 
                                           placeholder="Ej: 2022-2024, 2020, 2019-2021" />
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="repuestoImages">Imágenes del Repuesto</label>
                                <div class="image-uploader" id="repuestoImageUploader">
                                    <div class="uploader-controls">
                                        <button type="button" class="btn btn-outline" id="selectRepuestoImagesBtn">
                                            <i class="fas fa-image"></i> Seleccionar imágenes
                                        </button>
                                        <div class="form-hint">Puedes subir hasta 6 imágenes (máx. 5MB cada una). La primera será la principal.</div>
                                    </div>
                                    <input id="repuestoImages" name="repuesto_images[]" type="file" accept="image/*" multiple style="display:none;" />
                                    <div class="preview-list" id="repuestoPreviewList"></div>
                                    <div class="field-error" id="err_repuestoImages"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Paso 3: Accesorio -->
                <div class="form-step" id="step3-accesorio">
                    <div class="specifications-section">
                        <h5><i class="fas fa-helmet-safety"></i> Especificaciones del Accesorio</h5>
                        <form id="accesorioForm">
                            <div class="form-group">
                                <label for="accesorioCategorySelect">Categoría del Accesorio *</label>
                                <div class="select-with-btn">
                                    <select id="accesorioCategorySelect" name="categoria_id" class="form-control" required>
                                        <option value="">-- Selecciona una categoría --</option>
                                        <?php foreach ($categoriasPorTipo['ACCESORIO'] as $categoria): ?>
                                            <option value="<?php echo $categoria['id']; ?>">
                                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline btn-add" onclick="showAddCategoryModal('accesorio')" 
                                            title="Agregar nueva categoría">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <div class="field-error" id="err_accesorioCategorySelect"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="accesorioSubtipo">Subtipo de Accesorio (opcional)</label>
                                <input id="accesorioSubtipo" name="subtipo_accesorio" class="form-control" 
                                       placeholder="Ej: Casco, Chaqueta, Guantes, Protección" />
                                <div class="field-error" id="err_accesorioSubtipo"></div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="accesorioTalla">Talla</label>
                                    <input id="accesorioTalla" name="talla" class="form-control" 
                                           placeholder="Ej: M, L, XL, 42, Universal" />
                                </div>
                                <div class="form-group">
                                    <label for="accesorioColor">Color</label>
                                    <input id="accesorioColor" name="color" class="form-control" 
                                           placeholder="Ej: Negro, Rojo, Multicolor" />
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="accesorioMaterial">Material</label>
                                    <input id="accesorioMaterial" name="material" class="form-control" 
                                           placeholder="Ej: Policarbonato, Cuero, Tela" />
                                </div>
                                <div class="form-group">
                                    <label for="accesorioMarca">Marca *</label>
                                    <input id="accesorioMarca" name="marca" class="form-control" 
                                           placeholder="Ej: LS2, Shoei, Alpinestars" />
                                    <div class="field-error" id="err_accesorioMarca"></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="accesorioCertificacion">Certificación</label>
                                <input id="accesorioCertificacion" name="certificacion" class="form-control" 
                                       placeholder="Ej: DOT, ECE, NOM, CE" />
                            </div>
                            
                            <div class="form-group">
                                <label for="accesorioImages">Imágenes del Accesorio</label>
                                <div class="image-uploader" id="accesorioImageUploader">
                                    <div class="uploader-controls">
                                        <button type="button" class="btn btn-outline" id="selectAccesorioImagesBtn">
                                            <i class="fas fa-image"></i> Seleccionar imágenes
                                        </button>
                                        <div class="form-hint">Puedes subir hasta 6 imágenes (máx. 5MB cada una). La primera será la principal.</div>
                                    </div>
                                    <input id="accesorioImages" name="accesorio_images[]" type="file" accept="image/*" multiple style="display:none;" />
                                    <div class="preview-list" id="accesorioPreviewList"></div>
                                    <div class="field-error" id="err_accesorioImages"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Paso 4: Resumen -->
                <div class="form-step" id="step4">
                    <div class="summary-content">
                        <div id="summaryContent">
                            <!-- El resumen se generará dinámicamente -->
                        </div>
                    </div>
                    <div id="finalMessage" class="final-message"></div>
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="navigation-buttons">
                    <div class="navigation-left">
                        <button class="btn btn-outline" id="btnPrev" style="display: none;">
                            <i class="fas fa-arrow-left"></i> Anterior
                        </button>
                    </div>
                    <div class="navigation-right">
                        <button class="btn btn-outline" id="btnCancel">
                            Cancelar
                        </button>
                        <button class="btn btn-primary" id="btnNext">
                            Siguiente <i class="fas fa-arrow-right"></i>
                        </button>
                        <button class="btn btn-primary" id="btnSubmit" style="display: none;">
                            <i class="fas fa-save"></i> Guardar Producto
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Nueva Categoría -->
    <div class="modal-overlay" id="addCategoryModal">
        <div class="modal" style="max-width: 500px;">
            <form id="addCategoryForm">
                <input type="hidden" id="catTargetSelect" name="target_select" value="">
                
                <div class="modal-header">
                    <h3><i class="fas fa-tag"></i> Nueva Categoría</h3>
                    <button class="modal-close" id="closeAddCategoryModal" type="button">&times;</button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="catNombre">Nombre de la Categoría *</label>
                        <input id="catNombre" name="nombre" class="form-control" 
                               placeholder="Ej: Deportiva, Casco, Frenos" />
                        <div class="field-error" id="err_catNombre"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="catTipoProducto">Tipo de Producto *</label>
                        <select id="catTipoProducto" name="tipo_producto" class="form-control">
                            <option value="MOTO">Vehículo/Moto</option>
                            <option value="REPUESTO">Repuesto</option>
                            <option value="ACCESORIO">Accesorio</option>
                            <option value="GENERAL">General</option>
                        </select>
                        <div class="field-error" id="err_catTipoProducto"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="catDescripcion">Descripción</label>
                        <textarea id="catDescripcion" name="descripcion" class="form-control" rows="3"
                                  placeholder="Descripción de la categoría..."></textarea>
                    </div>
                    
                    <div id="addCategoryMsg" style="margin-top: 15px; display: none; padding: 12px; border-radius: 6px;"></div>
                </div>
                
                <div class="modal-footer">
                    <button class="btn btn-outline" type="button" id="cancelAddCategory">Cancelar</button>
                    <button class="btn btn-primary" type="submit" id="saveAddCategory">
                        <i class="fas fa-save"></i> Guardar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Nuevo Proveedor (para inventario) -->
    <div class="modal-overlay registro-modal" id="addSupplierModalInv">
        <div class="modal registro-modal">
            <form id="addSupplierFormInv">
                <div class="modal-header">
                    <h3><i class="fas fa-truck-fast"></i> Nuevo Proveedor</h3>
                    <button class="modal-close" id="closeAddSupplierModalInv" type="button">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="provRazonInv">Razón Social *</label>
                        <input id="provRazonInv" name="razon_social" class="form-control" placeholder="Ej: Distribuidora XYZ C.A." />
                        <div class="field-error" id="err_provRazonInv"></div>
                    </div>
                    <div class="form-group">
                        <label for="provRifInv">RIF / Cédula *</label>
                        <div style="display: flex; gap: 10px;">
                            <select id="provRifTypeInv" class="form-control" style="width: 80px; flex-shrink: 0;">
                                <option value="J">J</option>
                                <option value="V">V</option>
                            </select>
                            <input id="provRifInv" name="rif" class="form-control" placeholder="123456789" style="flex: 1;" />
                        </div>
                        <small style="color: #666; font-size: 0.9em;">J para RIF (9 dígitos), V para Cédula (7-8 dígitos)</small>
                        <div class="field-error" id="err_provRifInv"></div>
                    </div>
                    <div class="form-group">
                        <label for="provContactoInv">Persona Contacto</label>
                        <input id="provContactoInv" name="persona_contacto" class="form-control" placeholder="Ej: Juan Pérez" />
                    </div>
                    <div class="form-group">
                        <label for="provTelefonoInv">Teléfono Principal</label>
                        <input id="provTelefonoInv" name="telefono_principal" class="form-control" placeholder="Ej: 0414-1234567" />
                        <div class="field-error" id="err_provTelefonoInv"></div>
                    </div>
                    <div class="form-group">
                        <label for="provTelefonoAltInv">Teléfono Alternativo</label>
                        <input id="provTelefonoAltInv" name="telefono_alternativo" class="form-control" placeholder="Ej: 0212-1234567" />
                        <div class="field-error" id="err_provTelefonoAltInv"></div>
                    </div>
                    <div class="form-group">
                        <label for="provEmailInv">Email</label>
                        <input id="provEmailInv" name="email" type="email" class="form-control" placeholder="Ej: contacto@empresa.com" />
                        <div class="field-error" id="err_provEmailInv"></div>
                    </div>
                    <div class="form-group">
                        <label for="provDireccionInv">Dirección</label>
                        <textarea id="provDireccionInv" name="direccion" class="form-control" rows="3"></textarea>
                    </div>
                    <div id="addSupplierMsgInv" style="margin-top:8px; display:none; padding:10px; border-radius:6px;"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" type="button" id="cancelAddSupplierInv">Cancelar</button>
                    <button class="btn btn-primary" type="submit" id="saveAddSupplierInv">Guardar Proveedor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Detalles del Producto -->
    <div class="modal-overlay" id="productDetailsModal">
        <div class="modal" style="max-width: 800px;">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Detalles del Producto</h3>
                <button class="modal-close" id="closeDetailsModal" type="button">&times;</button>
            </div>
            <div class="modal-body">
                <div class="product-details-precise" id="productDetailsPrecise">
                    <!-- Se llenará dinámicamente -->
                    <div class="loading-message" style="text-align: center; padding: 40px;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p style="margin-top: 10px;">Cargando detalles...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer precise-footer">
                <button class="btn btn-update" id="updateProductBtn" style="display: none;">
                    <i class="fas fa-edit"></i> Actualizar
                </button>
                <button class="btn btn-close-precise" id="closeDetailsBtn">Cerrar</button>
            </div>
        </div>
    </div>
    
    <!-- ========== MODAL DE REGISTRO DE PROVEEDOR (Para producto) ========== -->
    <div class="modal-overlay registro-modal" id="addSupplierModalProd">
        <div class="modal registro-modal">
            <form id="addSupplierFormProd">
                <div class="modal-header">
                    <h3><i class="fas fa-truck-fast"></i> Nuevo Proveedor</h3>
                    <button class="modal-close" id="closeAddSupplierModalProd" type="button">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="provRazonProd">Razón Social *</label>
                        <input id="provRazonProd" name="razon_social" class="form-control" placeholder="Ej: Distribuidora XYZ C.A." />
                        <div class="field-error" id="err_provRazonProd"></div>
                    </div>
                    <div class="form-group">
                        <label for="provRifProd">RIF / Cédula *</label>
                        <div style="display: flex; gap: 10px;">
                            <select id="provRifTypeProd" class="form-control" style="width: 80px; flex-shrink: 0;">
                                <option value="J">J</option>
                                <option value="V">V</option>
                            </select>
                            <input id="provRifProd" name="rif" class="form-control" placeholder="123456789" style="flex: 1;" />
                        </div>
                        <small style="color: #666; font-size: 0.9em;">J para RIF (9 dígitos), V para Cédula (7-8 dígitos)</small>
                        <div class="field-error" id="err_provRifProd"></div>
                    </div>
                    <div class="form-group">
                        <label for="provContactoProd">Persona Contacto</label>
                        <input id="provContactoProd" name="persona_contacto" class="form-control" placeholder="Ej: Juan Pérez" />
                    </div>
                    <div class="form-group">
                        <label for="provTelefonoProd">Teléfono Principal</label>
                        <input id="provTelefonoProd" name="telefono_principal" class="form-control" placeholder="Ej: 0414-1234567" />
                        <div class="field-error" id="err_provTelefonoProd"></div>
                    </div>
                    <div class="form-group">
                        <label for="provTelefonoAltProd">Teléfono Alternativo</label>
                        <input id="provTelefonoAltProd" name="telefono_alternativo" class="form-control" placeholder="Ej: 0212-1234567" />
                        <div class="field-error" id="err_provTelefonoAltProd"></div>
                    </div>
                    <div class="form-group">
                        <label for="provEmailProd">Email</label>
                        <input id="provEmailProd" name="email" type="email" class="form-control" placeholder="Ej: contacto@empresa.com" />
                        <div class="field-error" id="err_provEmailProd"></div>
                    </div>
                    <div class="form-group">
                        <label for="provDireccionProd">Dirección</label>
                        <textarea id="provDireccionProd" name="direccion" class="form-control" rows="3"></textarea>
                    </div>
                    <div id="addSupplierMsgProd" style="margin-top:8px; display:none; padding:10px; border-radius:6px;"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" type="button" id="cancelAddSupplierProd">Cancelar</button>
                    <button class="btn btn-primary" type="submit" id="saveAddSupplierProd">Guardar Proveedor</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Modal y envío nuevo proveedor desde Inventario (producto)
    (function(){
        const addSupplierBtn = document.getElementById('addSupplierBtnProd');
        const addSupplierModal = document.getElementById('addSupplierModalProd');
        const addSupplierFormEl = document.getElementById('addSupplierFormProd');
        const closeAddSupplierModal = document.getElementById('closeAddSupplierModalProd');
        const cancelAddSupplier = document.getElementById('cancelAddSupplierProd');
        const saveAddSupplier = document.getElementById('saveAddSupplierProd');
        const addSupplierMsgEl = document.getElementById('addSupplierMsgProd');
        const prodProveedorSelect = document.getElementById('prodProveedor');

        function showFieldError(fieldId, message) {
            const el = document.getElementById(fieldId);
            if (el) {
                el.style.display = 'block';
                el.textContent = message;
            }
        }

        function showAddSupplierModalProd(){ if (!addSupplierModal) return; addSupplierModal.classList.add('active'); setTimeout(()=>{ const el=document.getElementById('provRazonProd'); if(el) el.focus(); },60); }
        function hideAddSupplierModalProd(){ 
            if (!addSupplierModal) return; 
            addSupplierModal.classList.remove('active'); 
            if(addSupplierFormEl) addSupplierFormEl.reset(); 
            if(addSupplierMsgEl){ addSupplierMsgEl.style.display='none'; addSupplierMsgEl.textContent=''; } 
            // Limpiar todos los errores
            document.querySelectorAll('.field-error').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });
        }

        if (addSupplierBtn) addSupplierBtn.addEventListener('click', showAddSupplierModalProd);
        if (closeAddSupplierModal) closeAddSupplierModal.addEventListener('click', hideAddSupplierModalProd);
        if (cancelAddSupplier) cancelAddSupplier.addEventListener('click', hideAddSupplierModalProd);
        if (addSupplierModal) addSupplierModal.addEventListener('click', function(e){ if(e.target===addSupplierModal) hideAddSupplierModalProd(); });

        // Validación en tiempo real para RIF
        const rifTypeSelect = document.getElementById('provRifTypeProd');
        const rifInput = document.getElementById('provRifProd');
        if (rifTypeSelect && rifInput) {
            rifTypeSelect.addEventListener('change', function() {
                const type = this.value;
                const placeholder = type === 'J' ? '123456789' : '12345678';
                rifInput.placeholder = placeholder;
                rifInput.maxLength = type === 'J' ? 9 : 8;
                // Limpiar errores si había
                const errEl = document.getElementById('err_provRifProd');
                if (errEl) {
                    errEl.style.display = 'none';
                    errEl.textContent = '';
                }
            });
            // Inicializar
            rifTypeSelect.dispatchEvent(new Event('change'));
        }

        if (addSupplierFormEl) addSupplierFormEl.addEventListener('submit', async function(e){
            e.preventDefault();

            const razon      = document.getElementById('provRazonProd');
            const rifType    = document.getElementById('provRifTypeProd')?.value ?? 'J';
            const rifNumEl   = document.getElementById('provRifProd');
            const telefonoEl = document.getElementById('provTelefonoProd');
            const telAltEl   = document.getElementById('provTelefonoAltProd');
            const emailEl    = document.getElementById('provEmailProd');

            // Construir RIF completo para validar
            if (rifNumEl) {
                const fullRif = rifType + '-' + (rifNumEl.value.trim());
                rifNumEl._fullRif = fullRif; // Guardar temporalmente
            }

            let ok = true;
            if (!InvValidate.required(razon, 'La razón social')) ok = false;

            // Validar RIF: construir input virtual con el valor completo
            const rifFull = rifType + '-' + (rifNumEl?.value?.trim() ?? '');
            const rifVirtual = { value: rifFull, id: 'provRifProd' };
            Object.setPrototypeOf(rifVirtual, HTMLInputElement.prototype);
            // Validación manual de RIF
            const rifClean = rifFull.toUpperCase().replace(/\s/g,'');
            const rifOk = /^J-\d{9}$/.test(rifClean) || /^[VE]-\d{7,8}$/.test(rifClean) || /^G-\d{9}$/.test(rifClean);
            if (!rifNumEl?.value?.trim()) {
                InvValidate.setError(rifNumEl, 'El número de RIF/Cédula es obligatorio');
                ok = false;
            } else if (!rifOk) {
                InvValidate.setError(rifNumEl, rifType === 'J' ? 'RIF empresarial: exactamente 9 dígitos' : 'Cédula: 7 u 8 dígitos numéricos');
                ok = false;
            } else {
                InvValidate.setValid(rifNumEl);
            }
            
            // Validar RIF único en el sistema
            if (ok && rifNumEl?.value?.trim()) {
                try {
                    const rifCompleto = rifType + '-' + rifNumEl.value.trim();
                    const checkUrl = (window.APP_BASE || '') + '/api/check_rif.php?rif=' + encodeURIComponent(rifCompleto);
                    const checkResp = await fetch(checkUrl);
                    const checkData = await checkResp.json();
                    
                    if (!checkData.available) {
                        InvValidate.setError(rifNumEl, 'Este RIF/Cédula ya está registrado');
                        ok = false;
                    }
                } catch (error) {
                    console.error('Error al verificar RIF:', error);
                }
            }

            if (!InvValidate.telefono(telefonoEl, false)) ok = false;
            if (!InvValidate.telefono(telAltEl, false))   ok = false;
            if (!InvValidate.email(emailEl, false))        ok = false;

            if (!ok) {
                Toast.error('Por favor corrige los campos marcados', 'Datos incompletos');
                return;
            }

            if (saveAddSupplier) { saveAddSupplier.disabled = true; saveAddSupplier.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...'; }
            addSupplierMsgEl.style.display='none';

            try{
                const fd = new FormData();
                fd.append('razon_social', razon.value.trim());
                fd.append('rif', rifType + (rifNumEl?.value?.trim() ?? ''));
                fd.append('persona_contacto', document.getElementById('provContactoProd')?.value.trim() ?? '');
                fd.append('telefono_principal', telefonoEl?.value.trim() ?? '');
                fd.append('telefono_alternativo', telAltEl?.value.trim() ?? '');
                fd.append('email', emailEl?.value.trim() ?? '');
                fd.append('direccion', document.getElementById('provDireccionProd')?.value.trim() ?? '');

                const apiUrl = (window.APP_BASE || '') + '/api/add_proveedor.php';
                const resp = await fetch(apiUrl, { method: 'POST', credentials: 'same-origin', body: fd });
                const js = await resp.json();
                if (!resp.ok || !js.success) {
                    if (resp.status === 422 && js.errors) {
                        if (js.errors.razon_social) showFieldError('err_provRazonProd', js.errors.razon_social);
                        if (js.errors.rif) showFieldError('err_provRifProd', js.errors.rif);
                        addSupplierMsgEl.style.display='block'; addSupplierMsgEl.style.background='#f8d7da'; addSupplierMsgEl.style.color='#721c24'; addSupplierMsgEl.textContent = js.message || 'Error de validación';
                    } else {
                        addSupplierMsgEl.style.display='block'; addSupplierMsgEl.style.background='#f8d7da'; addSupplierMsgEl.style.color='#721c24'; addSupplierMsgEl.textContent = js.message || 'Error al guardar proveedor';
                    }
                } else {
                    const newId = js.id; const newName = js.razon_social || razon;
                    // agregar al select del producto y a todos los selects dentro de proveedores-container
                    try {
                        const container = document.getElementById('proveedores-container');
                        if (container) {
                            const selects = container.querySelectorAll('select');
                            selects.forEach(sel => {
                                let exists = false;
                                for (let i=0;i<sel.options.length;i++) { if (sel.options[i].value == newId) { exists=true; sel.selectedIndex = i; break; } }
                                if (!exists) {
                                    const opt = document.createElement('option');
                                    opt.value = newId;
                                    opt.text = newName + (js.rif ? ' ('+js.rif+')' : '');
                                    sel.appendChild(opt);
                                }
                            });
                        }
                    } catch(e) { console.error('No se pudo agregar provider selects', e); }

                    // notificar a otras pestañas/páginas (compras) que hay nuevo proveedor
                    try { localStorage.setItem('last_added_provider', JSON.stringify({ id: newId, razon_social: newName, rif: js.rif || rif })); } catch(e){ /* ignore */ }

                    hideAddSupplierModalProd();
                }

            } catch(err){
                console.error('Error al guardar proveedor:', err);
                addSupplierMsgEl.style.display='block'; addSupplierMsgEl.style.background='#f8d7da'; addSupplierMsgEl.style.color='#721c24'; addSupplierMsgEl.textContent = 'Error interno. Intente nuevamente.';
            } finally {
                if (saveAddSupplier) { saveAddSupplier.disabled = false; saveAddSupplier.innerHTML = 'Guardar Proveedor'; }
            }
        });
    })();
    </script>

    <!-- Script: dinámico de filas de proveedores para el modal de producto -->
    <script>
    (function(){
        const provContainer = document.getElementById('proveedores-container');
        const addProvBtn = document.getElementById('add-proveedor-btn');
        if (!provContainer) return;

        function reindexRows() {
            const rows = provContainer.querySelectorAll('.proveedor-row');
            rows.forEach((row, idx) => {
                const sel = row.querySelector('select');
                if (sel) sel.name = `proveedores[${idx}][id]`;
                const radio = row.querySelector('input[type="radio"]');
                if (radio) { radio.name = 'proveedor_principal'; radio.id = `prov_principal_${idx}`; radio.value = `${idx}`; const lbl = row.querySelector('label[for]'); if(lbl) lbl.setAttribute('for', radio.id); }
                const removeBtn = row.querySelector('.remove-proveedor-btn');
                if (removeBtn) {
                    removeBtn.disabled = (idx===0);
                    removeBtn.style.cursor = (idx===0)?'not-allowed':'pointer';
                    if (idx===0) removeBtn.title = 'No se puede eliminar'; else removeBtn.title = 'Eliminar proveedor';
                }
            });
            // Ensure at least one principal is selected (default to first)
            const anyChecked = provContainer.querySelector('input[name="proveedor_principal"]:checked');
            if (!anyChecked) {
                const firstRadio = provContainer.querySelector('input[name="proveedor_principal"]');
                if (firstRadio) firstRadio.checked = true;
            }
        }

        function createRowFromTemplate() {
            const first = provContainer.querySelector('.proveedor-row');
            if (!first) return null;
            const clone = first.cloneNode(true);
            const sel = clone.querySelector('select'); if (sel) sel.value = '';
            const radio = clone.querySelector('input[type="radio"]'); if (radio) { radio.checked = false; radio.disabled = false; }
            const removeBtn = clone.querySelector('.remove-proveedor-btn');
            if (removeBtn) { removeBtn.disabled = false; removeBtn.style.cursor = 'pointer'; removeBtn.title = 'Eliminar proveedor'; removeBtn.innerHTML = '×'; }
            return clone;
        }

        if (addProvBtn) {
            addProvBtn.addEventListener('click', () => {
                const newRow = createRowFromTemplate();
                if (!newRow) return;
                provContainer.appendChild(newRow);
                reindexRows();
                const btn = newRow.querySelector('.remove-proveedor-btn');
                if (btn) btn.addEventListener('click', (e) => { e.preventDefault(); newRow.remove(); reindexRows(); });
            });
        }

        // Attach existing remove buttons
        provContainer.querySelectorAll('.remove-proveedor-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const row = btn.closest('.proveedor-row');
                if (!row) return;
                if (btn.disabled) return;
                row.remove();
                reindexRows();
            });
        });

        // Escuchar nuevos proveedores creados en otras pestañas (localStorage)
        window.addEventListener('storage', (e) => {
            if (e.key === 'last_added_provider' && e.newValue) {
                try {
                    const obj = JSON.parse(e.newValue);
                    const selects = provContainer.querySelectorAll('select');
                    selects.forEach(sel => {
                        let exists=false; for (let i=0;i<sel.options.length;i++){ if (sel.options[i].value==obj.id) { exists=true; break; } }
                        if (!exists) {
                            const opt = document.createElement('option'); opt.value = obj.id; opt.text = obj.razon_social + (obj.rif ? ' ('+obj.rif+')' : ''); sel.appendChild(opt);
                        }
                    });
                } catch(_){ }
            }
        });

        // Inicializar índices
        reindexRows();
    })();
    </script>

    <div id="initialData" style="display:none" data-json="<?php echo htmlspecialchars(json_encode(['categoriesByType' => $categoriasPorTipo], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"></div>

    <script>
    // =================== VARIABLES GLOBALES ===================
    let stockMovementChart = null;
    let typeChart = null;
    let currentStep = 1;
    let selectedType = null;
    const totalSteps = 4;
    let categoriesByType = {};

    // =================== FUNCIONES DE REPORTES ====================

    // Abrir modal de reportes
    function openReportsModal() {
        const modal = document.getElementById('reportsModal');
        if (modal) {
            modal.classList.add('active');
            // Resetear selección
            document.querySelectorAll('.report-card').forEach(card => card.classList.remove('selected'));
            document.getElementById('generateReportBtn').disabled = true;
            const body = modal.querySelector('.modal-body');
            if (body) body.scrollTop = 0;
        }
    }

    // Cerrar modal de reportes
    function closeReportsModal() {
        const modal = document.getElementById('reportsModal');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    // Generar reporte seleccionado
    async function generateReport() {
        const selectedCard = document.querySelector('.report-card.selected');
        if (!selectedCard) return;
        
        const reportType = selectedCard.dataset.report;
        
        // Mostrar indicador de carga
        const btn = document.getElementById('generateReportBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
        btn.disabled = true;
        
        try {
            const apiUrl = (window.APP_BASE || '') + '/api/generate_report.php';
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    report_type: reportType,
                    module: 'inventario'
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `reporte_${reportType}_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            // Cerrar modal
            closeReportsModal();
            
            // Notificación de éxito
            Toast.success('El reporte de inventario fue generado correctamente', 'Reporte generado');
            
        } catch (error) {
            console.error('Error generando reporte:', error);
            Toast.error('No se pudo generar el reporte: ' + error.message, 'Error de reporte');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    // =================== FUNCIONES DE INVENTARIO ===================

    // Función principal para cargar inventario
    async function fetchInventory(params = {}) {
        console.log('🔍 fetchInventory ejecutándose con params:', params);

        // Si no se pasó un período, tomarlo del selector del gráfico
        if (!params.period) {
            const periodFilter = document.getElementById('stockPeriodFilter');
            if (periodFilter && periodFilter.value) {
                params.period = periodFilter.value;
            }
        }

        // Si no se pasaron fechas desde/hasta, tomarlo de los filtros de fecha
        if (!params.created_from) {
            const dateFromFilter = document.getElementById('dateFromFilter');
            if (dateFromFilter && dateFromFilter.value) {
                params.created_from = dateFromFilter.value;
            }
        }
        if (!params.created_to) {
            const dateToFilter = document.getElementById('dateToFilter');
            if (dateToFilter && dateToFilter.value) {
                params.created_to = dateToFilter.value;
            }
        }

        const url = new URL('/inversiones-rojas/api/inventory_stats.php', window.location.origin);
        
        // Agregar parámetros
        if (params.q) url.searchParams.set('q', params.q);
        if (params.category) url.searchParams.set('category', params.category);
        if (params.estado && params.estado !== 'all') {
            url.searchParams.set('estado', params.estado);
        }
        if (params.period) {
            url.searchParams.set('period', params.period);
        }
        if (params.created_from) {
            url.searchParams.set('created_from', params.created_from);
        }
        if (params.created_to) {
            url.searchParams.set('created_to', params.created_to);
        }
        
        try {
            // Mostrar estado de carga
            const tbody = document.getElementById('inventoryRows');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 40px;">
                            <div class="loading-message">
                                <i class="fas fa-spinner fa-spin fa-2x"></i>
                                <p style="margin-top: 10px;">Cargando productos...</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
            
            // Hacer la petición
            const resp = await fetch(url.toString(), { 
                credentials: 'same-origin'
            });
            
            console.log('📥 Respuesta status:', resp.status);
            
            if (!resp.ok) {
                throw new Error(`HTTP ${resp.status}: ${await resp.text()}`);
            }
            
            const data = await resp.json();
            console.log('✅ Datos recibidos:', data);
            
            // Si hay error en la respuesta
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Actualizar estadísticas
            if (data.stats) {
                updateStats(data.stats);
            }
            
            // Actualizar tabla - USAR rowsHtml QUE VIENE DEL SERVIDOR
            if (data.rowsHtml) {
                updateTable(data.rowsHtml);
            } 
            // O si viene en formato de productos, construir tabla
            else if (data.products && data.products.length > 0) {
                buildTableFromProducts(data.products);
            } else {
                showNoProductsMessage();
            }

            // Marcar visualmente filas con stock bajo (si la API devolvió la lista)
            try {
                if (Array.isArray(data.lowStockProducts) && data.lowStockProducts.length > 0) {
                    data.lowStockProducts.forEach(p => {
                        try {
                            const row = document.querySelector(`tr[data-product-id="${p.id}"]`);
                            if (row) {
                                row.classList.add('low-stock');
                                if (!row.querySelector('.low-badge')) {
                                    const firstCell = row.querySelector('td');
                                    const badge = document.createElement('span');
                                    badge.className = 'low-badge';
                                    badge.title = 'Stock bajo';
                                    badge.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                                    if (firstCell) firstCell.insertBefore(badge, firstCell.firstChild);
                                }
                            }
                        } catch (e) { /* continue */ }
                    });
                }
            } catch (e) { console.warn('No se pudo marcar lowStockProducts:', e); }
            
            // Actualizar gráficas
            if (data.stockByType) {
                updateCharts(data);
            }
            
            // Actualizar categorías por tipo si vienen en la respuesta
            if (data.categoriesByType) {
                categoriesByType = data.categoriesByType;
                updateCategorySelects();
            }
            
            // Mostrar alertas de stock bajo
            if (data.stats) {
                showStockAlerts(data.stats);
            }
            
        } catch (error) {
            console.error('❌ Error en fetchInventory:', error);
            showError(error.message);
        }
    }

    // Actualizar estadísticas
    function updateStats(stats) {
        if (!stats) return;

        document.getElementById('stat-total-products').textContent = stats.totalProducts || '0';
        document.getElementById('stat-low-stock').textContent = stats.lowStock || '0';
        document.getElementById('stat-out-of-stock').textContent = stats.outOfStock || '0';

        const totalValueEl = document.getElementById('stat-total-value');
        if (totalValueEl) {
            if (stats.totalValueFormatted) {
                totalValueEl.innerHTML = stats.totalValueFormatted;
            } else {
                totalValueEl.textContent = stats.totalValue || '0.00';
            }
        }
    }

    // Actualizar tabla con HTML del servidor
    function updateTable(rowsHtml) {
        const tbody = document.getElementById('inventoryRows');
        if (!tbody || !rowsHtml) return;
        
        tbody.innerHTML = rowsHtml;
        
        // Agregar eventos a los botones recién creados
        attachTableEvents();
        
        // Agregar tooltips para información de stock
        addStockTooltips();
    }

    // Construir tabla desde productos (backup)
    function buildTableFromProducts(products) {
        const tbody = document.getElementById('inventoryRows');
        if (!tbody || !products || products.length === 0) {
            showNoProductsMessage();
            return;
        }
        
        let html = '';
        
        products.forEach(product => {
            const estadoLabel = product.estado ? 'Activo' : 'Inactivo';
            const estadoClass = product.estado ? 'status-active' : 'status-inactive';
            
            // Determinar clase de stock
            const stockActual = parseInt(product.stock_actual) || 0;
            const stockMinimo = parseInt(product.stock_minimo) || 0;
            let stockClass = '';
            
            if (stockActual === 0) {
                stockClass = 'stock-zero';
            } else if (stockActual <= stockMinimo) {
                stockClass = 'stock-low';
            } else {
                stockClass = 'stock-ok';
            }
            
            html += `
                <tr>
                    <td class="prod-image-cell">
                        ${product.imagen_principal ? 
                            `<img src="${product.imagen_principal}" alt="${product.nombre}" class="prod-thumb">` : 
                            `<div class="prod-thumb placeholder"><i class="fas fa-box"></i></div>`
                        }
                    </td>
                    <td><strong>${product.codigo_interno || ''}</strong></td>
                    <td>${product.nombre || ''}</td>
                    <td>${product.categoria_nombre || '-'}</td>
                    <td class="stock-info-tooltip ${stockClass}">${stockActual}</td>
                    <td>${stockMinimo}</td>
                    <td>
                        <span class="moneda-usd">$${(() => {
                            const pcUsd = product.precio_compra_usd ? parseFloat(product.precio_compra_usd) : parseFloat(product.precio_compra || 0);
                            return pcUsd.toFixed(2);
                        })()}</span>
                        <span class="moneda-bs">Bs ${(() => {
                            const tasa = window.TASA_CAMBIO || 35.50;
                            const pcBs = product.precio_compra_bs ? parseFloat(product.precio_compra_bs) : (parseFloat(product.precio_compra || 0) * tasa);
                            return pcBs.toFixed(0);
                        })()}</span>
                    </td>
                    <td>
                        <span class="moneda-usd">$${(() => {
                            const pvUsd = product.precio_venta_usd ? parseFloat(product.precio_venta_usd) : parseFloat(product.precio_venta || 0);
                            return pvUsd.toFixed(2);
                        })()}</span>
                        <span class="moneda-bs">Bs ${(() => {
                            const tasa = window.TASA_CAMBIO || 35.50;
                            const pvBs = product.precio_venta_bs ? parseFloat(product.precio_venta_bs) : (parseFloat(product.precio_venta || 0) * tasa);
                            return pvBs.toFixed(0);
                        })()}</span>
                    </td>
                    <td><span class="${estadoClass}">${estadoLabel}</span></td>
                    <td class="actions-cell">
                        <button class="btn btn-outline btn-sm btn-view" 
                                data-detalles='${JSON.stringify(product).replace(/'/g, "&#39;")}'
                                title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm btn-toggle" 
                                data-id="${product.id}"
                                data-estado="${product.estado ? '1' : '0'}"
                                title="${product.estado ? 'Inhabilitar' : 'Habilitar'}">
                            <i class="fas fa-toggle-${product.estado ? 'on' : 'off'}"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        tbody.innerHTML = html;
        attachTableEvents();
        addStockTooltips();
    }

    // Mostrar mensaje de no productos
    function showNoProductsMessage() {
        const tbody = document.getElementById('inventoryRows');
        if (!tbody) return;
        
        tbody.innerHTML = `
            <tr>
                <td colspan="11" style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-box-open fa-2x"></i>
                    <p style="margin-top: 10px;">No hay productos registrados</p>
                </td>
            </tr>
        `;
    }

    // Mostrar error
    function showError(message) {
        const tbody = document.getElementById('inventoryRows');
        if (!tbody) return;
        
        tbody.innerHTML = `
            <tr>
                <td colspan="11" style="text-align: center; padding: 40px; color: #e74c3c;">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <p style="margin-top: 10px;">Error: ${message}</p>
                    <button onclick="fetchInventory()" class="btn btn-outline" style="margin-top: 15px;">
                        <i class="fas fa-redo"></i> Reintentar
                    </button>
                </td>
            </tr>
        `;
    }

    // Agregar tooltips para información de stock
    function addStockTooltips() {
        const stockCells = document.querySelectorAll('.stock-info-tooltip');
        stockCells.forEach(cell => {
            const stockActual = parseInt(cell.textContent) || 0;
            const row = cell.closest('tr');
            if (!row) return;
            
            const stockMinCell = row.querySelector('td:nth-child(6)');
            const stockMaxCell = row.querySelector('td:nth-child(7)');
            const stockMin = stockMinCell ? parseInt(stockMinCell.textContent) || 0 : 0;
            const stockMax = stockMaxCell ? parseInt(stockMaxCell.textContent) || 0 : 0;
            
            let tooltipText = '';
            if (stockActual === 0) {
                tooltipText = '⚠️ Producto agotado';
            } else if (stockActual <= stockMin) {
                tooltipText = `⚠️ Stock bajo\nMínimo requerido: ${stockMin}\nActual: ${stockActual}`;
            } else if (stockMax > 0 && stockActual >= stockMax * 0.9) {
                tooltipText = `📈 Stock alto\nMáximo: ${stockMax}\nActual: ${stockActual}`;
            } else {
                tooltipText = `✅ Stock normal\nMínimo: ${stockMin}\nMáximo: ${stockMax}\nActual: ${stockActual}`;
            }
            
            const tooltipSpan = document.createElement('span');
            tooltipSpan.className = 'tooltip-text';
            tooltipSpan.textContent = tooltipText;
            tooltipSpan.style.whiteSpace = 'pre-line';
            cell.appendChild(tooltipSpan);
        });
    }

    // Mostrar alertas de stock
    function showStockAlerts(stats) {
        const alertsSection = document.getElementById('inventoryAlerts');
        if (!alertsSection || !stats) return;
        alertsSection.innerHTML = '';

        // Helper para navegar y resaltar fila
        function goToRow(productId) {
            const row = document.querySelector(`tr[data-product-id="${productId}"]`);
            if (!row) return;
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const originalBg = row.style.backgroundColor;
            row.style.transition = 'background-color 0.6s ease';
            row.style.backgroundColor = '#fff3cd';
            setTimeout(() => {
                row.style.backgroundColor = originalBg || '';
            }, 3000);
            // foco breve para accesibilidad
            row.tabIndex = -1;
            row.focus();
            setTimeout(() => { row.removeAttribute('tabindex'); }, 1000);
        }

        // Crear alerta para stock bajo (con lista de filas)
        if ((stats.lowStock && stats.lowStock > 0) || (Array.isArray(stats.lowStockProducts) && stats.lowStockProducts.length > 0)) {
            const count = stats.lowStock || (Array.isArray(stats.lowStockProducts) ? stats.lowStockProducts.length : 0);
            const alert = document.createElement('div');
            alert.className = 'alert-card warning';
            let listHtml = '';
            if (Array.isArray(stats.lowStockProducts) && stats.lowStockProducts.length > 0) {
                listHtml = '<ul style="margin-top:8px; padding-left:18px;">';
                stats.lowStockProducts.forEach(p => {
                    const display = `${p.codigo ? p.codigo + ' — ' : ''}${p.nombre} (Actual: ${p.stock_actual}, Mín: ${p.stock_minimo})`;
                    listHtml += `<li style="margin-bottom:6px;"><a href="#" data-pid="${p.id}" class="low-link">${display}</a></li>`;
                });
                listHtml += '</ul>';
            }

            alert.innerHTML = `
                <div class="alert-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <strong>${count} productos tienen stock bajo</strong>
                    <p>Algunos productos están cerca de su stock mínimo. Revisa el inventario.</p>
                    ${listHtml}
                </div>
            `;

            alertsSection.appendChild(alert);

            // Attach click handlers to links
            alert.querySelectorAll('.low-link').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    const pid = this.getAttribute('data-pid');
                    if (pid) goToRow(pid);
                });
            });
        }

        // Crear alerta para stock agotado
        if (stats.outOfStock > 0) {
            const alert = document.createElement('div');
            alert.className = 'alert-card danger';
            alert.innerHTML = `
                <div class="alert-icon danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div>
                    <strong>${stats.outOfStock} productos están agotados</strong>
                    <p>Necesitas reponer estos productos urgentemente.</p>
                </div>
            `;
            alertsSection.appendChild(alert);
        }
    }

    // Agregar eventos a los botones de la tabla
    function attachTableEvents() {
        // Botones "Ver Detalles" - usa data-detalles
        document.querySelectorAll('.btn-view').forEach(btn => {
            btn.addEventListener('click', function() {
                const detallesJson = this.dataset.detalles;
                try {
                    const detalles = detallesJson ? JSON.parse(detallesJson) : null;
                    let productData = null;

                    if (detalles && detalles.general) {
                        // Si viene del servidor con estructura {general: ..., especifico: ...}
                        productData = {
                            id: detalles.general.id,
                            codigo_interno: detalles.general.codigo_interno,
                            nombre: detalles.general.nombre,
                            descripcion: detalles.general.descripcion,
                            categoria_id: detalles.general.categoria_id,
                            categoria_nombre: detalles.general.categoria_nombre,
                            proveedor_id: detalles.general.proveedor_id,
                            proveedor_nombre: detalles.general.proveedor_nombre,
                            tipo_nombre: detalles.general.tipo_nombre,
                            stock_actual: detalles.general.stock_actual,
                            stock_minimo: detalles.general.stock_minimo,
                            stock_maximo: detalles.general.stock_maximo,
                            precio_compra: detalles.general.precio_compra,
                            precio_venta: detalles.general.precio_venta,
                            estado: detalles.general.estado,
                            created_at: detalles.general.created_at,
                            updated_at: detalles.general.updated_at,
                            imagen_principal: detalles.imagenes && detalles.imagenes.length > 0 ? detalles.imagenes[0] : null,
                            especifico: detalles.especifico
                        };
                    } else {
                        productData = detalles || null;
                    }

                    if (productData) {
                        showProductDetails(productData);
                    }
                } catch (error) {
                    console.error('Error al parsear detalles:', error, detallesJson);
                    Toast.error('No se pudieron cargar los detalles del producto', 'Error');
                }
            });
        });

        // Botones "Inhabilitar/Habilitar"
        document.querySelectorAll('.btn-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.id;
                const currentState = this.dataset.estado === '1';
                toggleProductStatus(productId, currentState, this);
            });
        });
    }

    // Función para mostrar detalles del producto
    function showProductDetails(product) {
        try {
            const modal = document.getElementById('productDetailsModal');
            const container = document.getElementById('productDetailsPrecise');
            
            if (!modal || !container || !product) {
                console.error('Elementos del modal no encontrados');
                return;
            }
            
            // Formatear fechas
            let fechaCreacion = 'No disponible';
            let fechaActualizacion = 'No disponible';

            if (product.created_at) {
                const fecha = new Date(product.created_at);
                fechaCreacion = fecha.toLocaleString('es-ES', {
                    year: 'numeric', month: 'long', day: 'numeric',
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });
            }

            if (product.updated_at) {
                const fecha = new Date(product.updated_at);
                fechaActualizacion = fecha.toLocaleString('es-ES', {
                    year: 'numeric', month: 'long', day: 'numeric',
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });
            }

            // Determinar estado del stock
            const stockActual = parseInt(product.stock_actual) || 0;
            const stockMinimo = parseInt(product.stock_minimo) || 0;
            let stockStatus = '';
            let stockClass = '';
            
            if (stockActual === 0) {
                stockStatus = 'AGOTADO';
                stockClass = 'stock-zero';
            } else if (stockActual <= stockMinimo) {
                stockStatus = 'BAJO';
                stockClass = 'stock-low';
            } else {
                stockStatus = 'OK';
                stockClass = 'stock-ok';
            }

            // Determinar tipo de producto y especificaciones
            let tipoNombre = product.tipo_nombre || 'Producto';
            let especificaciones = null;
            
            // Procesar especificaciones
            if (product.especifico) {
                especificaciones = product.especifico.datos || product.especifico;
                
                // Determinar tipo si no está definido
                if (!tipoNombre || tipoNombre === 'Producto') {
                    if (product.especifico.tipo === 'vehiculo') tipoNombre = 'Vehículo';
                    else if (product.especifico.tipo === 'repuesto') tipoNombre = 'Repuesto';
                    else if (product.especifico.tipo === 'accesorio') tipoNombre = 'Accesorio';
                }
            }

            // Construir HTML
            let html = `
                <div class="product-title-precise">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <h4 style="color: #333; margin-bottom: 10px;">Detalles del Producto</h4>
                            <div style="font-size: 18px; font-weight: 700; color: #1F9166;">
                                ${product.nombre || 'Producto sin nombre'}
                            </div>
                            <div style="color: #666; margin-top: 5px;">
                                <strong>Código:</strong> ${product.codigo_interno || 'Sin código'}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="margin-bottom: 8px;">
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; background: #e8f5e9; color: #1F9166; border: 1px solid #c8e6c9;">
                                    <i class="fas fa-box"></i> ${tipoNombre}
                                </span>
                            </div>
                            <div>
                                <span class="stock-indicator ${stockClass}" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background: ${stockClass === 'stock-zero' ? '#fdeaea' : stockClass === 'stock-low' ? '#fff3cd' : '#e8f5e9'}; color: ${stockClass === 'stock-zero' ? '#e74c3c' : stockClass === 'stock-low' ? '#ff9800' : '#1F9166'}; border: 1px solid ${stockClass === 'stock-zero' ? '#f5c6cb' : stockClass === 'stock-low' ? '#ffeaa7' : '#c8e6c9'}">
                                    <i class="fas fa-box${stockActual === 0 ? '-open' : ''}"></i>
                                    Stock: ${stockStatus}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1. INFORMACIÓN GENERAL -->
                <div class="detail-section" style="margin-top: 20px;">
                    <h5 style="color: #1F9166; border-bottom: 2px solid #1F9166; padding-bottom: 8px; margin-bottom: 15px;">
                        <i class="fas fa-info-circle"></i> INFORMACIÓN GENERAL
                    </h5>
                    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 15px; align-items: start;">
                        <div>
                            <div style="color: #666; font-size: 13px; margin-bottom: 4px;">Categoría:</div>
                            <div style="color: #333; font-size: 14px; background: #f8f9fa; padding: 12px; border-radius: 8px; ">
                                ${product.categoria_nombre || 'Sin categoría'}
                            </div>
                        </div>
                        <div>
                            <div style="color: #666; font-size: 13px; margin-bottom: 4px;">Proveedor:</div>
                            <div style="color: #333; font-size: 14px; background: #f8f9fa; padding: 12px; border-radius: 8px; ">
                                ${product.proveedor_nombre || 'No especificado'}
                            </div>
                        </div>
                        <div>
                            <div style="color: #666; font-size: 13px; margin-bottom: 4px;">Estado:</div>
                            <div style="color: #333; font-size: 14px; background: #f8f9fa; padding: 12px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <span class="${product.estado ? 'status-active' : 'status-inactive'}">
                                    ${product.estado ? 'Activo' : 'Inactivo'}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 18px;">
                        <div style="color: #666; font-size: 13px; margin-bottom: 6px;">Descripción:</div>
                        <div style="background: #f8f9fa; padding: 18px; border-radius: 10px; font-size: 14px; line-height: 1.7; color: #333;">
                            ${product.descripcion || 'Sin descripción disponible'}
                        </div>
                    </div>
                </div>
                <br />
                <!-- 2. ESPECIFICACIONES -->
                <div class="detail-section">
                    <h5 style="color: #1F9166; border-bottom: 2px solid #1F9166; padding-bottom: 8px; margin-bottom: 15px;">
                        <i class="fas fa-cogs"></i> ESPECIFICACIONES
                    </h5>
                    <div class="specs-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                    
            `;

            if (especificaciones) {
                if (tipoNombre === 'Vehículo' || (especificaciones.marca && especificaciones.modelo)) {
                    html += `
                        <div class="spec-item">
                            <div class="spec-label">Marca</div>
                            <div class="spec-value">${especificaciones.marca || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Modelo</div>
                            <div class="spec-value">${especificaciones.modelo || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Año</div>
                            <div class="spec-value">${especificaciones.anio || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Cilindrada</div>
                            <div class="spec-value">${especificaciones.cilindrada || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Color</div>
                            <div class="spec-value">${especificaciones.color || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Kilometraje</div>
                            <div class="spec-value">${especificaciones.kilometraje || '0'} km</div>
                        </div>
                    `;
                } else if (tipoNombre === 'Repuesto' || (especificaciones.categoria_tecnica && especificaciones.marca_compatible)) {
                    html += `
                        <div class="spec-item">
                            <div class="spec-label">Categoría Técnica</div>
                            <div class="spec-value">${especificaciones.categoria_tecnica || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Marca Compatible</div>
                            <div class="spec-value">${especificaciones.marca_compatible || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Modelo Compatible</div>
                            <div class="spec-value">${especificaciones.modelo_compatible || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Año Compatible</div>
                            <div class="spec-value">${especificaciones.anio_compatible || '-'}</div>
                        </div>
                    `;
                } else if (tipoNombre === 'Accesorio' || (especificaciones.subtipo_accesorio && especificaciones.marca)) {
                    html += `
                        <div class="spec-item">
                            <div class="spec-label">Subtipo</div>
                            <div class="spec-value">${especificaciones.subtipo_accesorio || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Talla</div>
                            <div class="spec-value">${especificaciones.talla || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Color</div>
                            <div class="spec-value">${especificaciones.color || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Material</div>
                            <div class="spec-value">${especificaciones.material || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Marca</div>
                            <div class="spec-value">${especificaciones.marca || '-'}</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Certificación</div>
                            <div class="spec-value">${especificaciones.certificacion || '-'}</div>
                        </div>
                    `;
                } else {
                    // Mostrar todas las especificaciones disponibles
                    for (const [key, value] of Object.entries(especificaciones)) {
                        if (value !== null && value !== undefined) {
                            html += `
                                <div class="spec-item">
                                    <div class="spec-label">${formatSpecLabel(key)}</div>
                                    <div class="spec-value">${value}</div>
                                </div>
                            `;
                        }
                    }
                }
            } else {
                html += `<div style="color:#666; grid-column: 1/-1;">No hay especificaciones disponibles para este producto.</div>`;
            }

            html += `</div></div> <br />`;
            
            // 3. FECHAS
            html += `
                <div class="detail-section">
                    <h5 style="color: #1F9166; border-bottom: 2px solid #1F9166; padding-bottom: 8px; margin-bottom: 15px;">
                        <i class="far fa-calendar-alt"></i> FECHAS
                    </h5>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <div style="color: #666; font-size: 12px; margin-bottom: 4px; text-transform: uppercase;">Creado</div>
                            <div style="color: #333; font-size: 14px;">${fechaCreacion}</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                            <div style="color: #666; font-size: 12px; margin-bottom: 4px; text-transform: uppercase;">Actualizado</div>
                            <div style="color: #333; font-size: 14px;">${fechaActualizacion}</div>
                        </div>
                    </div>
                </div>
                <br />
            `;
            
            // 4. IMÁGENES
            html += `
                <div class="detail-section">
                    <h5 style="color: #1F9166; border-bottom: 2px solid #1F9166; padding-bottom: 8px; margin-bottom: 15px;">
                        <i class="fas fa-images"></i> IMÁGENES
                    </h5>
            `;
            
            if (product.imagen_principal) {
                html += `
                    <div style="text-align: center;">
                        <img src="${product.imagen_principal}" alt="Imagen principal" style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 2px solid #e0e0e0;">
                    </div>
                `;
            } else {
                html += `
                    <div style="text-align: center; padding: 30px; color: #999; border: 2px dashed #ddd; border-radius: 8px;">
                        <i class="fas fa-image fa-2x"></i>
                        <p style="margin-top: 10px;">No hay imágenes para este producto</p>
                    </div>
                `;
            }
            
            html += `</div>`;
            
            // Actualizar contenido del modal
            container.innerHTML = html;
            
            // Configurar botón de actualización
            const updateBtn = document.getElementById('updateProductBtn');
            if (updateBtn && product.id) {
                updateBtn.onclick = function() {
                    openUpdateModal(product);
                };
                updateBtn.style.display = 'inline-flex';
            } else if (updateBtn) {
                updateBtn.style.display = 'none';
            }

            // Mostrar modal
            modal.classList.add('active');

        } catch (error) {
            console.error('❌ Error al mostrar detalles:', error);
            const container = document.getElementById('productDetailsPrecise');
            if (container) {
                container.innerHTML = `
                    <div style="text-align:center;padding:40px;color:#e74c3c;">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                        <p style="margin-top:10px;">Error al cargar los detalles del producto</p>
                        <p style="font-size:12px;margin-top:5px;">${error.message}</p>
                    </div>`;
            }
        }
    }

    // ── Modal de actualización de producto ────────────────────
    function openUpdateModal(product) {
        console.log('🔧 Abriendo modal de edición para:', product);
        console.log('  categoria_id:', product.categoria_id, 'tipo:', typeof product.categoria_id);
        console.log('  proveedor_id:', product.proveedor_id, 'tipo:', typeof product.proveedor_id);
        console.log('  CATEGORIAS disponibles:', window.CATEGORIAS?.length || 0);
        console.log('  PROVEEDORES disponibles:', window.PROVEEDORES?.length || 0);
        
        // Cerrar modal de detalles
        document.getElementById('productDetailsModal')?.classList.remove('active');

        // Crear o reutilizar modal de edición
        let modal = document.getElementById('updateProductModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'updateProductModal';
            modal.className = 'modal-overlay';
            modal.style.zIndex = '99998';
            document.body.appendChild(modal);
        }

        const rawEsp = product.especifico || {};
        // Normalizar el objeto de especificaciones (puede venir en rawEsp.datos o directamente en rawEsp)
        const esp = (rawEsp && rawEsp.datos) ? rawEsp.datos : rawEsp;
        // Priorizar el tipo real del detalle específico (vehículo/repuesto/accesorio)
        const tipoNombre = ('' + (rawEsp.tipo || product.tipo_nombre || '')).toLowerCase();

        // Campos específicos según tipo
        let especHtml = '';
        if (tipoNombre.includes('veh')) {
            especHtml = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
                    <div><label style="font-size:12px;font-weight:600;">Marca</label>
                    <input id="upd_marca" value="${esp.marca||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Modelo</label>
                    <input id="upd_modelo" value="${esp.modelo||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Año</label>
                    <input id="upd_anio" value="${esp.anio||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Cilindrada</label>
                    <input id="upd_cilindrada" value="${esp.cilindrada||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Color</label>
                    <input id="upd_color" value="${esp.color||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Kilometraje</label>
                    <input id="upd_kilometraje" type="number" min="0" value="${esp.kilometraje||0}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                </div>`;
        } else if (tipoNombre.includes('repuest')) {
            especHtml = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
                    <div><label style="font-size:12px;font-weight:600;">Categoría Técnica</label>
                    <input id="upd_categoria_tecnica" value="${esp.categoria_tecnica||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Marca Compatible</label>
                    <input id="upd_marca_compatible" value="${esp.marca_compatible||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Modelo Compatible</label>
                    <input id="upd_modelo_compatible" value="${esp.modelo_compatible||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Año Compatible</label>
                    <input id="upd_anio_compatible" value="${esp.anio_compatible||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                </div>`;
        } else if (tipoNombre.includes('acces')) {
            especHtml = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
                    <div><label style="font-size:12px;font-weight:600;">Subtipo</label>
                    <input id="upd_subtipo" value="${esp.subtipo_accesorio||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Marca</label>
                    <input id="upd_marca_acc" value="${esp.marca||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Talla</label>
                    <input id="upd_talla" value="${esp.talla||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Color</label>
                    <input id="upd_color_acc" value="${esp.color||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Material</label>
                    <input id="upd_material" value="${esp.material||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                    <div><label style="font-size:12px;font-weight:600;">Certificación</label>
                    <input id="upd_certificacion" value="${esp.certificacion||''}" class="form-control" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:13px;box-sizing:border-box;"></div>
                </div>`;
        }

        <div style="padding:20px;max-height:70vh;overflow-y:auto;">

                    button.dataset.estado = newState ? '1' : '0';
                    button.innerHTML  = newState ? '<i class="fas fa-toggle-on"></i>' : '<i class="fas fa-toggle-off"></i>';
                    button.title      = newState ? 'Inhabilitar' : 'Habilitar';

                    fetchInventory();

                    if (newState) {
                        Toast.success('Producto habilitado correctamente', '¡Habilitado!');
                    } else {
                        Toast.warning('Producto inhabilitado', 'Inhabilitado');
                    }
                } else {
                    const msg = result.error || result.message || 'Respuesta inválida del servidor';
                    Toast.error(msg, 'Error al cambiar estado');
                    button.innerHTML = originalText;
                }
            } else {
                const error = await response.text();
                Toast.error(`Error del servidor: ${error}`, 'Error');
                button.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('No se pudo conectar con el servidor. Verifica tu conexión.', 'Error de conexión');
            button.innerHTML = originalText;
        } finally {
            button.disabled = false;
        }
    }

    // Mostrar notificación
    // showNotification ya está definida en inv-notifications.js
    // Esta función es un alias para compatibilidad con llamadas existentes
    // (no reemplaza la de inv-notifications.js, solo asegura que exista)
    if (typeof window.showNotification === 'undefined') {
        window.showNotification = function(message, type = 'info') {
            console.warn('inv-notifications.js no cargó, usando fallback');
            alert(message);
        };
    }

    // Actualizar gráficas
    function updateCharts(data) {
        // Inicializar gráficas si no existen
        initializeCharts();
        
        // Actualizar gráfica por TIPO DE PRODUCTO
        if (typeChart && data.stockByType) {
            typeChart.data.labels = data.stockByType.labels || [];
            typeChart.data.datasets = [{
                data: data.stockByType.data || [],
                backgroundColor: data.stockByType.colors || ['#1F9166', '#3498db', '#9b59b6'],
                borderWidth: 2
            }];
            typeChart.update();
        }
        
        // Actualizar gráfica de movimiento de stock
        if (stockMovementChart && data.stockMovement) {
            stockMovementChart.data.labels = data.stockMovement.labels || [];
            stockMovementChart.data.datasets = data.stockMovement.datasets || [];
            stockMovementChart.update();
        }
    }

    // Inicializar gráficas
    function initializeCharts() {
        // Gráfica por TIPO DE PRODUCTO
        const typeCanvas = document.getElementById('typeChart');
        if (typeCanvas && !typeChart) {
            const ctx = typeCanvas.getContext('2d');
            typeChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: []
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    return `${label.split(' (')[0]}: ${value} productos`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfica de movimiento de stock
        const stockCanvas = document.getElementById('stockMovementChart');
        if (stockCanvas && !stockMovementChart) {
            const ctx = stockCanvas.getContext('2d');
            stockMovementChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad'
                            }
                        }
                    }
                }
            });
        }
    }

    // =================== SISTEMA DE CATEGORÍAS ===================

    // Función para actualizar selects de categorías según tipo de producto
    function updateCategorySelects() {
        // Mapeo de tipos de producto frontend a tipos de base de datos
        const typeMapping = {
            'vehiculo': 'VEHICULO',
            'repuesto': 'REPUESTO', 
            'accesorio': 'ACCESORIO'
        };
        
        // Actualizar cada tipo de producto
        Object.keys(typeMapping).forEach(productType => {
            const categoryType = typeMapping[productType];
            const selectId = `${productType}CategorySelect`;
            const selectElement = document.getElementById(selectId);
            
            if (selectElement) {
                // Guardar selección actual
                const currentValue = selectElement.value;
                
                // Limpiar opciones
                selectElement.innerHTML = '<option value="">-- Selecciona una categoría --</option>';
                
                // Agregar categorías si existen para este tipo
                if (categoriesByType[categoryType] && categoriesByType[categoryType].length > 0) {
                    categoriesByType[categoryType].forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.nombre;
                        selectElement.appendChild(option);
                    });
                }
                
                // Restaurar selección si existe
                if (currentValue && selectElement.querySelector(`option[value="${currentValue}"]`)) {
                    selectElement.value = currentValue;
                }
            }
        });
    }

    // =================== SISTEMA DE PASOS PARA NUEVO PRODUCTO ===================

    // Elementos del DOM (modal de producto)
    const addProductModalEl = document.getElementById('addProductModal');
    const addProductBtnEl = document.getElementById('addProductBtn');
    const closeAddProductModalEl = document.getElementById('closeAddProductModal');
    const btnPrevEl = document.getElementById('btnPrev');
    const btnNextEl = document.getElementById('btnNext');
    const btnCancelEl = document.getElementById('btnCancel');
    const btnSubmitEl = document.getElementById('btnSubmit');
    const stepsEls = document.querySelectorAll('.form-step');
    const stepIndicatorsEls = document.querySelectorAll('.step');
    const typeButtonsEls = document.querySelectorAll('.type-btn');

    function generateProductCode() {
        if (!selectedType) return '';
        const prefixMap = { 'vehiculo': 'VH', 'repuesto': 'RP', 'accesorio': 'AC' };
        const prefix = prefixMap[selectedType] || 'PR';
        const timestamp = Date.now().toString().slice(-6);
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        return `${prefix}-${timestamp}-${random}`;
    }

    // Inicializar el modal y comportamientos
    if (addProductBtnEl && addProductModalEl) {
        addProductBtnEl.addEventListener('click', () => {
            resetForm();
            addProductModalEl.classList.add('active');
            const codeField = document.getElementById('prodCodigo');
            if (codeField) codeField.value = generateProductCode();
        });

        closeAddProductModalEl?.addEventListener('click', hideAddProductModal);
        btnCancelEl?.addEventListener('click', hideAddProductModal);

        addProductModalEl.addEventListener('click', (e) => {
            if (e.target === addProductModalEl) hideAddProductModal();
        });
    }

    function hideAddProductModal() {
        addProductModalEl.classList.remove('active');
        resetForm();
    }

    // Selección de tipo
    typeButtonsEls.forEach(button => {
        button.addEventListener('click', () => {
            typeButtonsEls.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            selectedType = button.dataset.type;
            const code = generateProductCode();
            const codeField = document.getElementById('prodCodigo');
            if (codeField) codeField.value = code;
        });
    });

    // Navegación entre pasos
    btnPrevEl?.addEventListener('click', prevStep);
    btnNextEl?.addEventListener('click', nextStep);
    btnSubmitEl?.addEventListener('click', submitForm);

    function updateStepIndicator() {
        stepIndicatorsEls.forEach((indicator, index) => {
            indicator.classList.remove('active', 'completed');
            if (index + 1 === currentStep) {
                indicator.classList.add('active');
            } else if (index + 1 < currentStep) {
                indicator.classList.add('completed');
            }
        });
    }

    function showStep(stepNumber) {
        stepsEls.forEach(step => step.classList.remove('active'));
        let stepId = `step${stepNumber}`;
        if (stepNumber === 3 && selectedType) stepId = `step3-${selectedType}`;
        const currentStepElement = document.getElementById(stepId);
        if (currentStepElement) currentStepElement.classList.add('active');
        updateButtonStates();
        updateStepIndicator();
    }

    function updateButtonStates() {
        if (btnPrevEl) btnPrevEl.style.display = currentStep > 1 ? 'inline-flex' : 'none';
        if (currentStep === totalSteps) {
            btnNextEl.style.display = 'none';
            btnSubmitEl.style.display = 'inline-flex';
            generateSummary();
        } else {
            btnNextEl.style.display = 'inline-flex';
            btnSubmitEl.style.display = 'none';
        }
        btnNextEl.innerHTML = currentStep === 1 ? 'Continuar <i class="fas fa-arrow-right"></i>' : 'Siguiente <i class="fas fa-arrow-right"></i>';
    }

    function validateCurrentStep() {
        clearErrors();
        let isValid = true;
        switch(currentStep) {
            case 1:
                if (!selectedType) {
                    Toast.warning('Selecciona el tipo de producto (Vehículo, Repuesto o Accesorio)', 'Tipo requerido');
                    return false;
                }
                return true;

            case 2:
                // Nombre
                if (!InvValidate.required(document.getElementById('prodNombre'), 'El nombre')) isValid = false;
                else if (!InvValidate.maxLength(document.getElementById('prodNombre'), 200, 'El nombre')) isValid = false;

                // Precios
                if (!InvValidate.positiveNumber(document.getElementById('prodPrecioCompra'), 'El precio de compra')) isValid = false;
                if (!InvValidate.precioVenta(
                    document.getElementById('prodPrecioCompra'),
                    document.getElementById('prodPrecioVenta')
                )) isValid = false;

                // Validación adicional: precios deben ser mayores a 0
                const precioCompra = parseFloat(document.getElementById('prodPrecioCompra').value) || 0;
                const precioVenta = parseFloat(document.getElementById('prodPrecioVenta').value) || 0;
                if (precioCompra <= 0) {
                    showFieldError('prodPrecioCompra', 'El precio de compra debe ser mayor a 0.');
                    isValid = false;
                }
                if (precioVenta <= 0) {
                    showFieldError('prodPrecioVenta', 'El precio de venta debe ser mayor a 0.');
                    isValid = false;
                }

                // Stock
                if (!InvValidate.stockConsistency(
                    document.getElementById('prodStock'),
                    document.getElementById('prodStockMin'),
                    document.getElementById('prodStockMax')
                )) isValid = false;

                // Validación adicional: stock actual >= stock mínimo
                const stockActualEl = document.getElementById('prodStock');
                const stockMinEl = document.getElementById('prodStockMin');
                if (stockActualEl && stockMinEl) {
                    const stockActual = parseFloat(stockActualEl.value) || 0;
                    const stockMin = parseFloat(stockMinEl.value) || 0;
                    if (stockActual < stockMin) {
                        showFieldError('prodStock', 'El stock actual no puede ser menor al stock mínimo.');
                        isValid = false;
                    }
                }

                return isValid;

            case 3:
                return validateSpecifications();

            default:
                return true;
        }
    }

    function nextStep() {
        if (currentStep < totalSteps) {
            if (validateCurrentStep()) { 
                currentStep++; 
                showStep(currentStep); 
            }
        }
    }

    function prevStep() {
        if (currentStep > 1) { 
            currentStep--; 
            showStep(currentStep); 
            clearErrors(); 
        }
    }

    function validateSpecifications() {
        let isValid = true; 
        clearErrors();
        
        // Validar categoría según tipo
        const categoriaSelectId = `${selectedType}CategorySelect`;
        const categoriaSelect = document.getElementById(categoriaSelectId);
        
        if (categoriaSelect && (!categoriaSelect.value || categoriaSelect.value === '')) {
            showFieldError(categoriaSelectId, 'Selecciona una categoría válida');
            isValid = false;
        }
        
        if (selectedType === 'vehiculo') {
            // Validar campos específicos de vehículo
            const requiredFields = ['vehiculoMarca','vehiculoModelo','vehiculoAnio','vehiculoCilindrada','vehiculoColor'];
            required.forEach(id => {
                const el = document.getElementById(id);
                if (el && !el.value.trim()) { 
                    showFieldError(id, 'Campo requerido'); 
                    isValid = false; 
                }
            });
            
            const km = parseInt(document.getElementById('vehiculoKilometraje')?.value) || 0; 
            if (km < 0) { 
                showFieldError('vehiculoKilometraje','Kilometraje inválido'); 
                isValid = false; 
            }
        } else if (selectedType === 'repuesto') {
            // Validar campos específicos de repuesto
            const repuestoFields = ['repuestoCategoriaTecnica', 'repuestoMarcaCompatible'];
            repuestoFields.forEach(id => {
                const el = document.getElementById(id);
                if (el && !el.value.trim()) { 
                    showFieldError(id, 'Campo requerido'); 
                    isValid = false; 
                }
            });
        } else if (selectedType === 'accesorio') {
            // Validar campos específicos de accesorio (subtipo opcional)
            const accesorioFields = ['accesorioMarca'];
            accesorioFields.forEach(id => {
                const el = document.getElementById(id);
                if (el && !el.value.trim()) { 
                    showFieldError(id, 'Campo requerido'); 
                    isValid = false; 
                }
            });
        }
        
        return isValid;
    }

    function showFieldError(fieldId, message) {
        const errorElement = document.getElementById(`err_${fieldId}`);
        const inputElement = document.getElementById(fieldId);
        if (errorElement) { 
            errorElement.textContent = message; 
            errorElement.classList.add('active'); 
        }
        if (inputElement) { 
            inputElement.classList.add('error'); 
            inputElement.focus(); 
        }
    }

    function clearErrors() {
        document.querySelectorAll('.field-error').forEach(el => { 
            el.classList.remove('active'); 
            el.textContent = ''; 
        });
        document.querySelectorAll('.form-control.error').forEach(el => el.classList.remove('error'));
    }

    function generateSummary() {
        const summaryContent = document.getElementById('summaryContent'); 
        if (!summaryContent) return;
        
        let html = `
            <div class="summary-section">
                <h6><i class="fas fa-info-circle"></i> Información General</h6>
                <table class="summary-table">
                    <tr><td>Tipo de Producto:</td><td><strong>${getTypeName(selectedType)}</strong></td></tr>
                    <tr><td>Código Interno:</td><td><code>${document.getElementById('prodCodigo').value}</code></td></tr>
                    <tr><td>Nombre:</td><td>${document.getElementById('prodNombre').value}</td></tr>
                    <tr><td>Descripción:</td><td>${document.getElementById('prodDescripcion').value || '<em>Sin descripción</em>'}</td></tr>
                    <tr><td>Stock Inicial:</td><td>${document.getElementById('prodStock').value} unidades</td></tr>
                    <tr><td>Stock Mínimo:</td><td>${document.getElementById('prodStockMin').value} unidades</td></tr>
                    <tr><td>Stock Máximo:</td><td>${document.getElementById('prodStockMax').value} unidades</td></tr>
                    <tr><td>Precio Compra:</td><td>
                        <span class="moneda-bs">Bs ${(parseFloat(document.getElementById('prodPrecioCompra').value || 0) * (window.TASA_CAMBIO || 35.50)).toFixed(0)}</span>
                        <span class="moneda-usd">($${parseFloat(document.getElementById('prodPrecioCompra').value || 0).toFixed(2)})</span>
                    </td></tr>
                    <tr><td>Precio Venta:</td><td>
                        <span class="moneda-bs">Bs ${(parseFloat(document.getElementById('prodPrecioVenta').value || 0) * (window.TASA_CAMBIO || 35.50)).toFixed(0)}</span>
                        <span class="moneda-usd">($${parseFloat(document.getElementById('prodPrecioVenta').value || 0).toFixed(2)})</span>
                    </td></tr>
                </table>
            </div>`;
        
        // Especificaciones según tipo
        html += `<div class="summary-section"><h6><i class="fas fa-list-alt"></i> Especificaciones</h6><table class="summary-table">`;
        
        if (selectedType === 'vehiculo') {
            const categoriaSelect = document.getElementById('vehiculoCategorySelect');
            const categoriaText = categoriaSelect?.options[categoriaSelect.selectedIndex]?.text || '';
            
            html += `
                <tr><td>Categoría:</td><td>${categoriaText}</td></tr>
                <tr><td>Marca:</td><td>${document.getElementById('vehiculoMarca').value}</td></tr>
                <tr><td>Modelo:</td><td>${document.getElementById('vehiculoModelo').value}</td></tr>
                <tr><td>Año:</td><td>${document.getElementById('vehiculoAnio').value}</td></tr>
                <tr><td>Cilindrada:</td><td>${document.getElementById('vehiculoCilindrada').value}</td></tr>
                <tr><td>Color:</td><td>${document.getElementById('vehiculoColor').value}</td></tr>
                <tr><td>Kilometraje:</td><td>${document.getElementById('vehiculoKilometraje').value || '0'} km</td></tr>`;
        } else if (selectedType === 'repuesto') {
            const categoriaSelect = document.getElementById('repuestoCategorySelect');
            const categoriaText = categoriaSelect?.options[categoriaSelect.selectedIndex]?.text || '';
            
            html += `
                <tr><td>Categoría:</td><td>${categoriaText}</td></tr>
                <tr><td>Categoría Técnica:</td><td>${document.getElementById('repuestoCategoriaTecnica').value}</td></tr>
                <tr><td>Marca Compatible:</td><td>${document.getElementById('repuestoMarcaCompatible').value}</td></tr>
                <tr><td>Modelo Compatible:</td><td>${document.getElementById('repuestoModeloCompatible').value || '<em>No especificado</em>'}</td></tr>
                <tr><td>Año Compatible:</td><td>${document.getElementById('repuestoAnioCompatible').value || '<em>No especificado</em>'}</td></tr>`;
        } else if (selectedType === 'accesorio') {
            const categoriaSelect = document.getElementById('accesorioCategorySelect');
            const categoriaText = categoriaSelect?.options[categoriaSelect.selectedIndex]?.text || '';
            
            html += `
                <tr><td>Categoría:</td><td>${categoriaText}</td></tr>
                <tr><td>Subtipo:</td><td>${document.getElementById('accesorioSubtipo').value}</td></tr>
                <tr><td>Talla:</td><td>${document.getElementById('accesorioTalla').value || '<em>No especificada</em>'}</td></tr>
                <tr><td>Color:</td><td>${document.getElementById('accesorioColor').value || '<em>No especificado</em>'}</td></tr>
                <tr><td>Material:</td><td>${document.getElementById('accesorioMaterial').value || '<em>No especificado</em>'}</td></tr>
                <tr><td>Marca:</td><td>${document.getElementById('accesorioMarca').value}</td></tr>
                <tr><td>Certificación:</td><td>${document.getElementById('accesorioCertificacion').value || '<em>No especificada</em>'}</td></tr>`;
        }
        
        html += `</table></div>`;
        summaryContent.innerHTML = html;
    }

    function getTypeName(type) { 
        if (type === 'vehiculo') return 'Vehículo'; 
        if (type === 'repuesto') return 'Repuesto'; 
        if (type === 'accesorio') return 'Accesorio'; 
        return type; 
    }

    async function submitForm() {
        try {
            if (!validateCurrentStep()) { 
                Toast.error('Por favor corrige los campos marcados antes de continuar', 'Formulario incompleto'); 
                return; 
            }
            
            btnSubmitEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...'; 
            btnSubmitEl.disabled = true;
            
            // Recoger datos generales
            const generalData = {
                codigo_interno: document.getElementById('prodCodigo').value,
                nombre: document.getElementById('prodNombre').value,
                descripcion: document.getElementById('prodDescripcion').value,
                stock_actual: document.getElementById('prodStock').value,
                stock_minimo: document.getElementById('prodStockMin').value,
                stock_maximo: document.getElementById('prodStockMax').value,
                precio_compra: document.getElementById('prodPrecioCompra').value,
                precio_venta: document.getElementById('prodPrecioVenta').value,
                precio_compra_bs: document.getElementById('prodPrecioCompraBs')?.value || (parseFloat(document.getElementById('prodPrecioCompra').value || 0) * (window.TASA_CAMBIO || 35.50)).toFixed(2),
                precio_compra_usd: document.getElementById('prodPrecioCompra').value,
                precio_venta_bs: document.getElementById('prodPrecioVentaBs')?.value || (parseFloat(document.getElementById('prodPrecioVenta').value || 0) * (window.TASA_CAMBIO || 35.50)).toFixed(2),
                precio_venta_usd: document.getElementById('prodPrecioVenta').value,
                proveedor_id: document.getElementById('prodProveedor')?.value || null,
                tipo_producto: selectedType
            };

            // Recoger filas de proveedores del modal (si existen) y construir payload JSON
            const provRows = Array.from(document.querySelectorAll('#proveedores-container .proveedor-row'));
            const proveedoresPayload = [];
            const selectedPrincipalRadio = document.querySelector('input[name="proveedor_principal"]:checked');
            const selectedPrincipalIndex = selectedPrincipalRadio ? parseInt(selectedPrincipalRadio.value, 10) : null;
            provRows.forEach((row, idx) => {
                const sel = row.querySelector('select');
                if (!sel) return;
                const pid = sel.value ? sel.value : null;
                if (!pid) return; // ignorar filas sin proveedor seleccionado
                const es_principal = (selectedPrincipalIndex !== null && selectedPrincipalIndex === idx) ? true : false;
                // Si en el futuro se añaden inputs adicionales (precio_compra, sku, tiempo_entrega), recogerlos aquí
                proveedoresPayload.push({ proveedor_id: parseInt(pid, 10), es_principal: es_principal });
            });

            // Si no hay proveedor_id principal en el campo único, usar el primero de la lista
            if ((!generalData.proveedor_id || generalData.proveedor_id === '') && proveedoresPayload.length > 0) {
                generalData.proveedor_id = proveedoresPayload[0].proveedor_id;
            }
            
            // Recoger datos específicos según tipo
            let especificaciones = { tipo: selectedType };
            let categoria_id = null;
            
            if (selectedType === 'vehiculo') {
                categoria_id = document.getElementById('vehiculoCategorySelect').value;
                especificaciones = {
                    tipo: 'vehiculo', 
                    marca: document.getElementById('vehiculoMarca').value,
                    modelo: document.getElementById('vehiculoModelo').value, 
                    anio: document.getElementById('vehiculoAnio').value,
                    cilindrada: document.getElementById('vehiculoCilindrada').value, 
                    color: document.getElementById('vehiculoColor').value,
                    kilometraje: parseInt(document.getElementById('vehiculoKilometraje').value) || 0
                };
            } else if (selectedType === 'repuesto') {
                categoria_id = document.getElementById('repuestoCategorySelect').value;
                especificaciones = { 
                    tipo: 'repuesto', 
                    categoria_tecnica: document.getElementById('repuestoCategoriaTecnica').value,
                    marca_compatible: document.getElementById('repuestoMarcaCompatible').value, 
                    modelo_compatible: document.getElementById('repuestoModeloCompatible').value, 
                    anio_compatible: document.getElementById('repuestoAnioCompatible').value 
                };
            } else if (selectedType === 'accesorio') {
                categoria_id = document.getElementById('accesorioCategorySelect').value;
                especificaciones = { 
                    tipo: 'accesorio', 
                    subtipo_accesorio: document.getElementById('accesorioSubtipo').value,
                    talla: document.getElementById('accesorioTalla').value, 
                    color: document.getElementById('accesorioColor').value, 
                    material: document.getElementById('accesorioMaterial').value, 
                    marca: document.getElementById('accesorioMarca').value, 
                    certificacion: document.getElementById('accesorioCertificacion').value 
                };
            }
            
            // Combinar datos
            const productData = { 
                ...generalData, 
                categoria_id: categoria_id,
                especificaciones: JSON.stringify(especificaciones) 
            };
            
            const formData = new FormData();
            for (const key in productData) {
                if (productData[key] !== null && productData[key] !== undefined) {
                    formData.append(key, productData[key]);
                }
            }

            // Adjuntar payload de proveedores como JSON (esperado por api/add_product.php)
            if (proveedoresPayload.length > 0) {
                formData.append('proveedores', JSON.stringify(proveedoresPayload));
            }
            
            // Agregar imágenes
            let imageInput;
            if (selectedType === 'vehiculo') imageInput = document.getElementById('vehiculoImages');
            else if (selectedType === 'repuesto') imageInput = document.getElementById('repuestoImages');
            else if (selectedType === 'accesorio') imageInput = document.getElementById('accesorioImages');
            
            if (imageInput && imageInput.files.length > 0) {
                for (let i = 0; i < imageInput.files.length; i++) {
                    formData.append('images[]', imageInput.files[i]);
                }
            }

            // Envío al servidor
            const apiUrl = (window.APP_BASE || '') + '/api/add_product.php';
            console.log('Enviando a:', apiUrl);
            
            const response = await fetch(apiUrl, { 
                method: 'POST', 
                body: formData, 
                credentials: 'same-origin' 
            });
            
            // Log para debugging
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error response:', errorText);
                throw new Error('Error del servidor: ' + response.status);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('No es JSON:', text.substring(0, 500));
                throw new Error('La respuesta del servidor no es JSON válido');
            }
            
            const result = await response.json();
            const finalMessage = document.getElementById('finalMessage');
            
            if (response.ok && result.ok) {
                finalMessage.className = 'final-message success';
                finalMessage.innerHTML = `
                    <div style="display:flex; align-items:flex-start; gap:15px;">
                        <i class="fas fa-check-circle fa-2x"></i>
                        <div>
                            <strong style="font-size:16px;">✅ Producto creado exitosamente</strong>
                            <div style="margin-top:10px; font-size:14px;">
                                <strong>${productData.nombre}</strong> ha sido registrado en el inventario con el código <code>${productData.codigo_interno}</code>.
                            </div>
                        </div>
                    </div>`;
                finalMessage.style.display = 'block';
                
                // Cerrar modal y recargar inventario después de 2 segundos
                setTimeout(() => {
                    hideAddProductModal();
                    fetchInventory();
                    Toast.success('El producto fue registrado exitosamente en el inventario', '¡Producto creado!');
                }, 2000);
                // Restaurar estado del botón para permitir nuevos envíos sin recargar
                if (btnSubmitEl) {
                    btnSubmitEl.innerHTML = '<i class="fas fa-save"></i> Guardar Producto';
                    btnSubmitEl.disabled = false;
                }
            } else {
                finalMessage.className = 'final-message error';
                finalMessage.innerHTML = `❌ Error: ${result.error || 'No se pudo guardar el producto'}`;
                // Mostrar debug si está disponible (útil en desarrollo)
                if (result.debug) {
                    finalMessage.innerHTML += `<pre style="white-space:pre-wrap;margin-top:10px; background:#f8f8f8; padding:8px; border-radius:6px; font-size:12px;">${result.debug}</pre>`;
                }
                finalMessage.style.display = 'block';
                btnSubmitEl.innerHTML = '<i class="fas fa-save"></i> Guardar Producto'; 
                btnSubmitEl.disabled = false;
                
                // Mostrar errores de validación si existen
                if (result.errors) {
                    let errorsHtml = '<ul style="margin-top: 10px; padding-left: 20px;">';
                    for (const [field, message] of Object.entries(result.errors)) {
                        errorsHtml += `<li><strong>${field}:</strong> ${message}</li>`;
                    }
                    errorsHtml += '</ul>';
                    finalMessage.innerHTML += errorsHtml;
                }
                
                Toast.error(result.error || 'No se pudo guardar el producto. Intenta nuevamente.', 'Error al guardar');
            }
        } catch (error) {
            console.error('Error al guardar producto:', error);
            const finalMessage = document.getElementById('finalMessage');
            finalMessage.className = 'final-message error'; 
            finalMessage.textContent = '❌ Error al guardar el producto'; 
            finalMessage.style.display = 'block';
            btnSubmitEl.innerHTML = '<i class="fas fa-save"></i> Guardar Producto'; 
            btnSubmitEl.disabled = false;
            Toast.error('No se pudo conectar con el servidor. Verifica tu conexión.', 'Error de conexión');
        }
    }

    function resetForm() {
        currentStep = 1; 
        selectedType = null;
        
        // Mostrar paso 1
        document.querySelectorAll('.form-step').forEach(step => step.classList.remove('active'));
        const step1 = document.getElementById('step1');
        if (step1) step1.classList.add('active');
        
        // Resetear formularios
        document.querySelectorAll('form').forEach(f => {
            if (f.reset) f.reset();
        });
        document.querySelectorAll('select').forEach(s => {
            if (s.selectedIndex !== undefined) s.selectedIndex = 0;
        });
        
        // Limpiar previews de imágenes
        ['vehiculoPreviewList','repuestoPreviewList','accesorioPreviewList'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '';
        });
        
        // Limpiar inputs de archivo
        ['vehiculoImages','repuestoImages','accesorioImages'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        
        // Limpiar mensaje final
        const finalMessage = document.getElementById('finalMessage');
        if (finalMessage) {
            finalMessage.style.display = 'none';
            finalMessage.className = 'final-message';
            finalMessage.innerHTML = '';
        }
        
        // Resetear botones de tipo
        document.querySelectorAll('.type-btn').forEach(btn => btn.classList.remove('active'));
        
        // Generar nuevo código
        const codeField = document.getElementById('prodCodigo');
        if (codeField) codeField.value = generateProductCode();
        
        // Limpiar errores
        clearErrors();
        
        // Actualizar estados de botones
        updateButtonStates();
        
        // Actualizar indicadores
        updateStepIndicator();
        // Asegurar que el botón de submit esté habilitado por defecto al resetear el formulario
        if (typeof btnSubmitEl !== 'undefined' && btnSubmitEl) {
            btnSubmitEl.disabled = false;
            btnSubmitEl.innerHTML = '<i class="fas fa-save"></i> Guardar Producto';
        }
    }

    // =================== MANEJO DE MODALES DESDE EDICIÓN DE PRODUCTO ===================

    // Función para abrir modal de crear categoría desde el modal de edición de producto
    function showAddCategoryModalFromUpdate(source = 'update') {
        const modal = document.getElementById('addCategoryModal');
        const form = document.getElementById('addCategoryForm');
        const targetSelectField = document.getElementById('catTargetSelect');
        
        if (!modal || !form) return;
        
        // Limpiar formulario
        form.reset();
        showCategoryMessage('', '');
        const msgDiv = document.getElementById('addCategoryMsg');
        if (msgDiv) {
            msgDiv.style.display = 'none';
        }
        
        // Guardar que viene desde el modal de edición
        if (targetSelectField) {
            targetSelectField.value = 'upd_categoria';  // Select donde guardar la nueva categoría
        }
        
        // Mostrar modal
        modal.classList.add('active');
        
        // Enfocar el campo de nombre
        setTimeout(() => {
            const nombreInput = document.getElementById('catNombre');
            if (nombreInput) nombreInput.focus();
        }, 100);
    }

    // Función para abrir modal de crear proveedor desde el modal de edición de producto
    function showAddSupplierModalFromUpdate() {
        const modal = document.getElementById('addSupplierModal');
        const form = document.getElementById('addSupplierForm');
        
        if (!modal || !form) return;
        
        // Limpiar formulario
        form.reset();
        
        // Limpiar mensajes
        const msgDiv = document.getElementById('addSupplierMsg');
        if (msgDiv) {
            msgDiv.style.display = 'none';
        }
        
        // Guardar que viene desde el modal de edición (se usará al guardar)
        window._supplierModalSource = 'updateProduct';
        
        // Mostrar modal
        modal.classList.add('active');
        
        // Enfocar el campo de razón social
        setTimeout(() => {
            const razonInput = document.getElementById('provRazon');
            if (razonInput) razonInput.focus();
        }, 100);
    }

    // =================== MANEJO DE IMÁGENES ===================

    function setupImageUploader(type) {
        const selectBtn = document.getElementById(`select${type.charAt(0).toUpperCase() + type.slice(1)}ImagesBtn`);
        const hiddenInput = document.getElementById(`${type}Images`);
        const previewList = document.getElementById(`${type}PreviewList`);
        
        if (selectBtn && hiddenInput) {
            selectBtn.addEventListener('click', () => hiddenInput.click());
            hiddenInput.addEventListener('change', function() {
                handleFilesList(this.files, previewList, hiddenInput, type);
            });
        }
    }

    function handleFilesList(files, previewList, hiddenInput, type) {
        const maxFiles = 6;
        const maxBytes = 5 * 1024 * 1024;
        const errorElement = document.getElementById(`err_${type}Images`);
        
        if (errorElement) {
            errorElement.classList.remove('active');
            errorElement.textContent = '';
        }
        
        if (files.length > maxFiles) {
            showFieldError(`${type}Images`, `Máximo ${maxFiles} imágenes permitidas`);
            return;
        }
        
        // Validar cada archivo
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (!file.type.startsWith('image/')) {
                showFieldError(`${type}Images`, 'Solo se permiten archivos de imagen (JPG, PNG, etc.)');
                return;
            }
            if (file.size > maxBytes) {
                showFieldError(`${type}Images`, `La imagen "${file.name}" excede el tamaño máximo de 5MB`);
                return;
            }
        }
        
        // Limpiar previews anteriores
        previewList.innerHTML = '';
        
        // Crear nuevos previews
        Array.from(files).forEach((file, idx) => {
            const item = document.createElement('div');
            item.className = 'image-preview-item';
            
            const img = document.createElement('img');
            img.alt = file.name;
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-btn';
            removeBtn.innerHTML = '×';
            removeBtn.title = 'Eliminar imagen';
            
            removeBtn.addEventListener('click', function() {
                const dataTransfer = new DataTransfer();
                Array.from(hiddenInput.files).forEach((f, index) => {
                    if (index !== idx) dataTransfer.items.add(f);
                });
                hiddenInput.files = dataTransfer.files;
                handleFilesList(hiddenInput.files, previewList, hiddenInput, type);
            });
            
            const url = URL.createObjectURL(file);
            img.src = url;
            img.onload = () => URL.revokeObjectURL(url);
            
            item.appendChild(img);
            item.appendChild(removeBtn);
            previewList.appendChild(item);
        });
    }

    // =================== EVENTOS Y FILTROS ===================

    // Función debounce para búsqueda
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Enviar consulta con filtros
    const sendCurrentQuery = debounce(() => {
        const searchInput = document.getElementById('inventorySearchInput');
        const typeFilter = document.getElementById('typeFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        const stateFilter = document.getElementById('stateFilter');
        const dateFilter = document.getElementById('dateFilter');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const periodFilter = document.getElementById('stockPeriodFilter');

        const params = {};

        if (searchInput && searchInput.value.trim()) {
            params.q = searchInput.value.trim();
        }

        if (typeFilter && typeFilter.value) {
            params.type = typeFilter.value;
        }

        if (categoryFilter && categoryFilter.value) {
            params.category = categoryFilter.value;
        }

        if (stateFilter && stateFilter.value !== 'all') {
            params.estado = stateFilter.value;
        }

        // Manejar filtro de fecha
        if (dateFilter && dateFilter.value) {
            if (dateFilter.value === 'custom') {
                if (dateFrom && dateFrom.value) {
                    params.created_from = dateFrom.value;
                }
                if (dateTo && dateTo.value) {
                    params.created_to = dateTo.value;
                }
            } else {
                params.date_range = dateFilter.value;
            }
        }

        if (periodFilter) {
            params.period = periodFilter.value;
        }

        fetchInventory(params);
    }, 300);

    // Función para aplicar rango de fechas personalizado
    function applyCustomDate() {
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');

        if (!dateFrom || !dateTo) return;

        const fromValue = dateFrom.value;
        const toValue = dateTo.value;

        // Validar que ambas fechas estén presentes
        if (!fromValue || !toValue) {
            Toast.warning('Por favor seleccione ambas fechas (desde y hasta)', 'Fechas requeridas');
            return;
        }

        // Validar que la fecha desde no sea futura
        if (!InvValidate.notFutureDate(dateFrom, true)) {
            return;
        }

        // Validar que la fecha hasta no sea futura
        if (!InvValidate.notFutureDate(dateTo, true)) {
            return;
        }

        // Validar que desde <= hasta
        if (fromValue > toValue) {
            Toast.error('La fecha "Desde" no puede ser posterior a la fecha "Hasta"', 'Fechas inválidas');
            return;
        }

        // Aplicar filtro
        sendCurrentQuery();
    }

    // Función para limpiar filtros
    function clearFilters() {
        const searchInput = document.getElementById('inventorySearchInput');
        const typeFilter = document.getElementById('typeFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        const stateFilter = document.getElementById('stateFilter');
        const dateFilter = document.getElementById('dateFilter');
        const customDateRange = document.getElementById('customDateRange');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');

        if (searchInput) searchInput.value = '';
        if (typeFilter) typeFilter.value = '';
        if (categoryFilter) categoryFilter.value = '';
        if (stateFilter) stateFilter.value = 'all';
        if (dateFilter) dateFilter.value = '';
        if (customDateRange) customDateRange.style.display = 'none';
        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';

        sendCurrentQuery();
    }

    // =================== INICIALIZACIÓN ===================

    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 DOM cargado, inicializando inventario...');
        
        // Calcular precios duales automáticamente
        const prodPrecioCompra = document.getElementById('prodPrecioCompra');
        const prodPrecioCompraBs = document.getElementById('prodPrecioCompraBs');
        const prodPrecioVenta = document.getElementById('prodPrecioVenta');
        const prodPrecioVentaBs = document.getElementById('prodPrecioVentaBs');
        
        if (prodPrecioCompra && prodPrecioCompraBs) {
            prodPrecioCompra.addEventListener('input', function() {
                const usd = parseFloat(this.value) || 0;
                prodPrecioCompraBs.value = (usd * (window.TASA_CAMBIO || 35.50)).toFixed(2);
            });
            prodPrecioCompraBs.addEventListener('input', function() {
                const bs = parseFloat(this.value) || 0;
                prodPrecioCompra.value = (bs / (window.TASA_CAMBIO || 35.50)).toFixed(2);
            });
        }
        
        if (prodPrecioVenta && prodPrecioVentaBs) {
            prodPrecioVenta.addEventListener('input', function() {
                const usd = parseFloat(this.value) || 0;
                prodPrecioVentaBs.value = (usd * (window.TASA_CAMBIO || 35.50)).toFixed(2);
            });
            prodPrecioVentaBs.addEventListener('input', function() {
                const bs = parseFloat(this.value) || 0;
                prodPrecioVenta.value = (bs / (window.TASA_CAMBIO || 35.50)).toFixed(2);
            });
        }
        
        // Cargar datos iniciales
        fetchInventory();
        
        // Configurar eventos de filtros
        const searchInput = document.getElementById('inventorySearchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const stateFilter = document.getElementById('stateFilter');
        const dateFilter = document.getElementById('dateFilter');
        const customDateRange = document.getElementById('customDateRange');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const periodFilter = document.getElementById('stockPeriodFilter');
        const refreshBtn = document.getElementById('refreshBtn');
        const exportReportBtn = document.getElementById('exportReportBtn');
        const closeDetailsBtn = document.getElementById('closeDetailsBtn');
        const closeDetailsModal = document.getElementById('closeDetailsModal');
        const productDetailsModal = document.getElementById('productDetailsModal');
        
        if (searchInput) {
            searchInput.addEventListener('input', sendCurrentQuery);
        }
        
        if (typeFilter) {
            typeFilter.addEventListener('change', sendCurrentQuery);
        }
        
        if (categoryFilter) {
            categoryFilter.addEventListener('change', sendCurrentQuery);
        }
        
        if (stateFilter) {
            stateFilter.addEventListener('change', sendCurrentQuery);
        }
        
        // Evento para mostrar/ocultar rango de fechas personalizado
        if (dateFilter) {
            dateFilter.addEventListener('change', function() {
                if (this.value === 'custom') {
                    if (customDateRange) customDateRange.style.display = 'block';
                } else {
                    if (customDateRange) customDateRange.style.display = 'none';
                    // Limpiar fechas si no es custom
                    if (dateFrom) dateFrom.value = '';
                    if (dateTo) dateTo.value = '';
                }
                sendCurrentQuery();
            });
        }
        
        if (periodFilter) {
            periodFilter.addEventListener('change', sendCurrentQuery);
        }
        
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                sendCurrentQuery();
                Toast.info('Datos del inventario actualizados', 'Inventario');
            });
        }
        
        if (exportReportBtn) {
            exportReportBtn.addEventListener('click', () => {
                Toast.info('La exportación estará disponible próximamente', 'En desarrollo');
            });
        }
        
        if (closeDetailsBtn) {
            closeDetailsBtn.addEventListener('click', () => {
                productDetailsModal.classList.remove('active');
            });
        }
        
        if (closeDetailsModal) {
            closeDetailsModal.addEventListener('click', () => {
                productDetailsModal.classList.remove('active');
            });
        }
        
        if (productDetailsModal) {
            productDetailsModal.addEventListener('click', (e) => {
                if (e.target === productDetailsModal) {
                    productDetailsModal.classList.remove('active');
                }
            });
        }
        
        // Configurar sistema de imágenes
        setupImageUploader('vehiculo');
        setupImageUploader('repuesto');
        setupImageUploader('accesorio');
        
        // Validación en tiempo real con InvValidate
        const precioVentaInput = document.getElementById('prodPrecioVenta');
        const precioCompraInput = document.getElementById('prodPrecioCompra');
        if (precioVentaInput) {
            precioVentaInput.addEventListener('blur', function() {
                InvValidate.precioVenta(precioCompraInput, this);
            });
        }
        if (precioCompraInput) {
            precioCompraInput.addEventListener('blur', function() {
                InvValidate.positiveNumber(this, 'El precio de compra');
                // Re-validar venta si ya tiene valor
                if (precioVentaInput?.value) InvValidate.precioVenta(this, precioVentaInput);
            });
        }

        // Validación de stock en tiempo real
        const prodStockInput    = document.getElementById('prodStock');
        const prodStockMinInput = document.getElementById('prodStockMin');
        const prodStockMaxInput = document.getElementById('prodStockMax');

        [prodStockInput, prodStockMinInput, prodStockMaxInput].forEach(inp => {
            if (inp) inp.addEventListener('blur', () => {
                InvValidate.stockConsistency(prodStockInput, prodStockMinInput, prodStockMaxInput);
            });
        });
        
        // Configurar modal de categoría
        const addCategoryForm = document.getElementById('addCategoryForm');
        const closeAddCategoryModal = document.getElementById('closeAddCategoryModal');
        const cancelAddCategory = document.getElementById('cancelAddCategory');
        const addCategoryModal = document.getElementById('addCategoryModal');
        
        if (addCategoryForm) {
            addCategoryForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const nombreInput = document.getElementById('catNombre');
                const tipoInput = document.getElementById('catTipoProducto');
                const nombre = nombreInput.value.trim();
                
                // Validar nombre obligatorio
                if (!InvValidate.required(nombreInput, 'El nombre de categoría')) {
                    return;
                }
                
                // Validar longitud mínima
                if (!InvValidate.minLength(nombreInput, 2, 'El nombre')) {
                    return;
                }
                
                // Validar tipo seleccionado
                if (!InvValidate.required(tipoInput, 'El tipo de producto')) {
                    return;
                }
                
                // Validar nombre único
                try {
                    const checkUrl = (window.APP_BASE || '') + '/api/check_categoria.php?nombre=' + encodeURIComponent(nombre);
                    const checkResp = await fetch(checkUrl);
                    const checkData = await checkResp.json();
                    
                    if (!checkData.available) {
                        InvValidate.setError(nombreInput, 'Ya existe una categoría con este nombre');
                        showCategoryMessage('Ya existe una categoría con este nombre', 'error');
                        return;
                    }
                } catch (error) {
                    console.error('Error al verificar categoría:', error);
                }
                
                // Crear FormData con los datos validados
                const formData = new FormData();
                formData.append('nombre', nombre);
                formData.append('tipo_producto', document.getElementById('catTipoProducto').value);
                const descripcion = document.getElementById('catDescripcion')?.value.trim() || '';
                if (descripcion) formData.append('descripcion', descripcion);
                
                try {
                    const apiUrl = (window.APP_BASE || '') + '/api/add_categoria.php';
                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });
                    
                    const result = await response.json();
                    
                    if (result.ok) {
                        showCategoryMessage(result.message || 'Categoría creada exitosamente', 'success');
                        
                        // Recargar categorías después de un breve retraso
                        setTimeout(async () => {
                            try {
                                // Recargar inventario para actualizar categorías
                                await fetchInventory();
                            } catch (err) {
                                console.error('Error al recargar inventario:', err);
                            }
                            
                            // Cerrar modal después de éxito
                            setTimeout(() => {
                                addCategoryModal.classList.remove('active');
                                
                                // Actualizar el select del modal de edición si existe
                                const targetSelectId = document.getElementById('catTargetSelect')?.value;
                                if (targetSelectId && result.id && result.nombre) {
                                    const selectElement = document.getElementById(targetSelectId);
                                    if (selectElement) {
                                        // Agregar la nueva categoría a window.CATEGORIAS si no existe
                                        const catExists = (window.CATEGORIAS || []).some(c => c.id == result.id);
                                        if (!catExists && window.CATEGORIAS) {
                                            window.CATEGORIAS.push({
                                                id: result.id,
                                                nombre: result.nombre
                                            });
                                        }
                                        
                                        // Reconstruir el select con las categorías actualizadas
                                        selectElement.innerHTML = '<option value="">Seleccionar...</option>';
                                        (window.CATEGORIAS || []).forEach(cat => {
                                            const option = document.createElement('option');
                                            option.value = cat.id;
                                            option.textContent = cat.nombre;
                                            selectElement.appendChild(option);
                                        });
                                        selectElement.value = result.id;
                                    }
                                }
                                
                                Toast.success('La categoría fue creada y ya está disponible', '¡Categoría creada!');
                            }, 1000);
                        }, 500);
                    } else {
                        showCategoryMessage(result.error || 'Error al crear la categoría', 'error');
                        Toast.error(result.error || 'No se pudo crear la categoría', 'Error');
                    }
                } catch (error) {
                    console.error('Error al guardar categoría:', error);
                    showCategoryMessage('Error de conexión', 'error');
                    Toast.error('No se pudo conectar con el servidor. Verifica tu conexión.', 'Error de conexión');
                }
            });
        }
        
        // Cerrar modal de categoría
        if (closeAddCategoryModal) {
            closeAddCategoryModal.addEventListener('click', () => {
                addCategoryModal.classList.remove('active');
            });
        }
        
        if (cancelAddCategory) {
            cancelAddCategory.addEventListener('click', () => {
                addCategoryModal.classList.remove('active');
            });
        }
        
        if (addCategoryModal) {
            addCategoryModal.addEventListener('click', (e) => {
                if (e.target === addCategoryModal) {
                    addCategoryModal.classList.remove('active');
                }
            });
        }
        
        // Inicializar sistema de pasos
        updateButtonStates();
        updateStepIndicator();
        
        console.log('✅ Inventario inicializado correctamente');
    });
    
    // Función auxiliar para mensajes de categoría
    function showCategoryMessage(message, type) {
        const msgDiv = document.getElementById('addCategoryMsg');
        if (msgDiv) {
            msgDiv.textContent = message;
            msgDiv.style.display = message ? 'block' : 'none';
            
            if (type === 'success') {
                msgDiv.style.backgroundColor = '#d4edda';
                msgDiv.style.color = '#155724';
                msgDiv.style.border = '1px solid #c3e6cb';
            } else if (type === 'error') {
                msgDiv.style.backgroundColor = '#f8d7da';
                msgDiv.style.color = '#721c24';
                msgDiv.style.border = '1px solid #f5c6cb';
            } else {
                msgDiv.style.backgroundColor = '';
                msgDiv.style.color = '';
                msgDiv.style.border = '';
            }
        }
    }
    
    // Función para mostrar modal de nueva categoría
    function showAddCategoryModal(productType) {
        const modal = document.getElementById('addCategoryModal');
        const form = document.getElementById('addCategoryForm');
        const targetSelectField = document.getElementById('catTargetSelect');
        const tipoSelect = document.getElementById('catTipoProducto');
        
        if (!modal || !form) return;
        
        // Mapear tipo de producto a tipo_producto
        const typeMapping = {
            'vehiculo': 'VEHICULO',
            'repuesto': 'REPUESTO',
            'accesorio': 'ACCESORIO'
        };
        
        // Establecer tipo de producto predeterminado en el select
        if (tipoSelect && typeMapping[productType]) {
            tipoSelect.value = typeMapping[productType];
        }
        
        // Guardar el select objetivo
        if (targetSelectField) {
            targetSelectField.value = `${productType}CategorySelect`;
        }
        
        // Limpiar formulario
        form.reset();
        showCategoryMessage('', '');
        const msgDiv = document.getElementById('addCategoryMsg');
        if (msgDiv) {
            msgDiv.style.display = 'none';
        }
        
        // Mostrar modal
        modal.classList.add('active');
        
        // Enfocar el campo de nombre
        setTimeout(() => {
            const nombreInput = document.getElementById('catNombre');
            if (nombreInput) nombreInput.focus();
        }, 100);
    }

    // =================== EVENT LISTENERS PARA REPORTES ====================
    document.addEventListener('DOMContentLoaded', function() {
        // Botón Abrir Modal de Reportes
        const openReportsBtn = document.getElementById('openReportsModalBtn');
        if (openReportsBtn) {
            openReportsBtn.addEventListener('click', openReportsModal);
        }
        
        // Selección de tarjeta de reporte
        document.addEventListener('click', (e) => {
            if (e.target.closest('.report-card')) {
                const card = e.target.closest('.report-card');
                document.querySelectorAll('.report-card').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                document.getElementById('generateReportBtn').disabled = false;
            }
        });
        
        // Botón Generar Reporte
        const generateBtn = document.getElementById('generateReportBtn');
        if (generateBtn) {
            generateBtn.addEventListener('click', generateReport);
        }
        
        // Cerrar modal de reportes
        const closeBtn = document.getElementById('closeReportsModal');
        const cancelBtn = document.getElementById('cancelReportsBtn');
        const modal = document.getElementById('reportsModal');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', closeReportsModal);
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeReportsModal);
        }
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeReportsModal();
                }
            });
        }
    });
    </script>

    <!-- Modal para Reportes de Inventario -->
    <div class="modal-overlay" id="reportsModal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h2><i class="fas fa-chart-bar"></i> Reportes de Inventario</h2>
                <button class="modal-close" id="closeReportsModal">&times;</button>
            </div>
            
            <div class="modal-body" style="padding: 20px;">
                <p style="margin-bottom: 20px; color: #666;">Selecciona el tipo de reporte que deseas generar:</p>
                
                <div class="report-card" data-report="productos_demandados">
                    <i class="fas fa-star"></i>
                    <strong>Productos Más Demandados</strong>
                    <p>Reporte de productos más vendidos en los últimos 30 días</p>
                </div>
                
                <div class="report-card" data-report="estado_inventario">
                    <i class="fas fa-warehouse"></i>
                    <strong>Estado del Inventario</strong>
                    <p>Estado actual completo del inventario con niveles de stock</p>
                </div>
                
                
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-cancel" id="cancelReportsBtn">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn btn-report" id="generateReportBtn" disabled>
                    <i class="fas fa-file-pdf"></i> Generar Reporte
                </button>
            </div>
        </div>
    </div>
</body>
</html>