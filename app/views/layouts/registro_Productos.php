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
    <title>Gestión de Inventario - Inversiones Rojas</title>
    <script>
        var APP_BASE = '<?php echo $base_url; ?>';
        var TASA_CAMBIO = <?php echo getTasaCambio(); ?>;
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/layouts/inventario.css">
    <style>
        /* Estilos para moneda dual */
        .moneda-bs { color: #1F9166; font-weight: 700; }
        .moneda-usd { color: #6c757d; font-size: 0.85em; }
        
        /* Estilos adicionales para el sistema de pasos - MANTENIENDO LA IDENTIDAD VISUAL ORIGINAL */
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
        
        /* Step indicator - usando los colores originales del sistema */
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
        
        /* Type selection - manteniendo la paleta de colores original */
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
        
        /* Especificaciones - usando la identidad visual */
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
        
        /* Ajustes en los grupos de formulario para más espacio */
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1F9166;
            box-shadow: 0 0 0 3px rgba(31, 145, 102, 0.1);
        }
        
        .form-control::placeholder {
            color: #999;
            font-style: italic;
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
        
        /* Navigation buttons - mejor espaciado */
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
        
        /* Botones con la paleta de colores original */
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        
        /* Estilos para errores y validaciones */
        .field-error {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 6px;
            display: none;
            font-weight: 500;
        }
        
        .field-error.active {
            display: block;
        }
        
        .form-hint {
            color: #6c757d;
            font-size: 12px;
            margin-top: 6px;
            font-style: italic;
        }
        
        /* Image uploader - estilos originales */
        .image-uploader {
            margin-top: 15px;
        }
        
        .uploader-controls {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .preview-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 15px;
        }
        
        .image-preview-item {
            position: relative;
            width: 100px;
        }
        
        .image-preview-item img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }
        
        .image-preview-item .remove-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e74c3c;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Estilos de validación visual */
        .form-control.error {
            border-color: #e74c3c;
        }
        
        .form-control.valid {
            border-color: #1F9166;
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
        
        /* Estilos específicos para el modal */
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

        .modal-overlay.active .modal {
            transform: translateY(0);
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
            color: #333;
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
        
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Main Content (existente) -->
    <div class="admin-content">
        <!-- ... contenido existente del dashboard ... -->
        
  <!-- Action Buttons -->
<div class="inventory-actions">
    <div class="action-buttons">
        <button class="btn btn-primary" id="addProductBtn">
            <i class="fas fa-plus"></i>
            Agregar Producto
        </button>
    </div> <!-- Cierra .action-buttons -->
</div> <!-- Cierra .inventory-actions -->
        

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
                            <input id="prodCodigo" name="codigo_interno" class="form-control" required readonly 
                                   title="Código generado automáticamente" placeholder="Se generará automáticamente" />
                            <div class="field-error" id="err_prodCodigo"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="prodNombre">Nombre del Producto *</label>
                            <input id="prodNombre" name="nombre" class="form-control" required 
                                   placeholder="Ej: Moto Bera BR 200, Casco LS2, Freno delantero" />
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
                                       value="0" min="0" required placeholder="Ej: 10" />
                                <div class="field-error" id="err_prodStock"></div>
                            </div>
                            <div class="form-group">
                                <label for="prodStockMin">Stock Mínimo *</label>
                                <input id="prodStockMin" name="stock_minimo" type="number" class="form-control" 
                                       value="5" min="0" required placeholder="Ej: 5" />
                                <div class="field-error" id="err_prodStockMin"></div>
                            </div>
                            <div class="form-group">
                                <label for="prodStockMax">Stock Máximo *</label>
                                <input id="prodStockMax" name="stock_maximo" type="number" class="form-control" 
                                       value="100" min="0" required placeholder="Ej: 100" />
                                <div class="field-error" id="err_prodStockMax"></div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="prodPrecioCompra">Precio Compra ($) *</label>
                                <input id="prodPrecioCompra" name="precio_compra" type="number" step="0.01" 
                                       class="form-control" value="0.00" min="0" required 
                                       placeholder="Ej: 1500.00" />
                                <div class="field-error" id="err_prodPrecioCompra"></div>
                            </div>
                            <div class="form-group">
                                <label for="prodPrecioVenta">Precio Venta ($) *</label>
                                <input id="prodPrecioVenta" name="precio_venta" type="number" step="0.01" 
                                       class="form-control" value="0.00" min="0" required 
                                       placeholder="Ej: 1800.00" />
                                <div class="field-error" id="err_prodPrecioVenta"></div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Paso 3: Especificaciones -->
                <!-- Vehículo -->
                <div class="form-step" id="step3-vehiculo">
                    <div class="specifications-section">
                        <h5><i class="fas fa-motorcycle"></i> Especificaciones del Vehículo</h5>
                        <form id="vehiculoForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="vehiculoMarca">Marca *</label>
                                    <input id="vehiculoMarca" name="marca" class="form-control" required 
                                           placeholder="Ej: Bera, Yamaha, Honda" />
                                    <div class="field-error" id="err_vehiculoMarca"></div>
                                </div>
                                <div class="form-group">
                                    <label for="vehiculoModelo">Modelo *</label>
                                    <input id="vehiculoModelo" name="modelo" class="form-control" required 
                                           placeholder="Ej: BR 200, YZF-R3, CBR 250" />
                                    <div class="field-error" id="err_vehiculoModelo"></div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="vehiculoAnio">Año *</label>
                                    <input id="vehiculoAnio" name="anio" type="number" min="1900" max="2099" 
                                           class="form-control" required placeholder="Ej: 2024" />
                                    <div class="field-error" id="err_vehiculoAnio"></div>
                                </div>
                                <div class="form-group">
                                    <label for="vehiculoCilindrada">Cilindrada *</label>
                                    <input id="vehiculoCilindrada" name="cilindrada" class="form-control" required 
                                           placeholder="Ej: 200cc, 300cc, 450cc" />
                                    <div class="field-error" id="err_vehiculoCilindrada"></div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="vehiculoColor">Color *</label>
                                    <input id="vehiculoColor" name="color" class="form-control" required 
                                           placeholder="Ej: Rojo, Negro, Blanco" />
                                    <div class="field-error" id="err_vehiculoColor"></div>
                                </div>
                                <div class="form-group">
                                    <label for="vehiculoKilometraje">Kilometraje (km)</label>
                                    <input id="vehiculoKilometraje" name="kilometraje" type="number" 
                                           class="form-control" value="0" min="0" placeholder="Ej: 0 (para nuevo)" />
                                    <div class="form-hint">0 para vehículos nuevos</div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="vehiculoTipoMoto">Tipo de Moto *</label>
                                <select id="vehiculoTipoMoto" name="tipo_moto" class="form-control" required>
                                    <option value="">-- Selecciona el tipo --</option>
                                    <option value="deportiva">Deportiva</option>
                                    <option value="naked">Naked</option>
                                    <option value="touring">Touring</option>
                                    <option value="scooter">Scooter</option>
                                    <option value="cross">Cross/Enduro</option>
                                    <option value="custom">Custom</option>
                                    <option value="atv">ATV/Cuatrimoto</option>
                                    <option value="doble_proposito">Doble Propósito</option>
                                </select>
                                <div class="field-error" id="err_vehiculoTipoMoto"></div>
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

                <!-- Repuesto -->
                <div class="form-step" id="step3-repuesto">
                    <div class="specifications-section">
                        <h5><i class="fas fa-cog"></i> Especificaciones del Repuesto</h5>
                        <form id="repuestoForm">
                            <div class="form-group">
                                <label for="repuestoCategoria">Categoría del Repuesto *</label>
                                <select id="repuestoCategoria" name="categoria" class="form-control" required>
                                    <option value="">-- Selecciona la categoría --</option>
                                    <option value="frenos">Frenos</option>
                                    <option value="motor">Motor</option>
                                    <option value="suspension">Suspensión</option>
                                    <option value="electrico">Eléctrico</option>
                                    <option value="transmision">Transmisión</option>
                                    <option value="carroceria">Carrocería</option>
                                    <option value="luces">Luces</option>
                                    <option value="escape">Escape</option>
                                    <option value="filtros">Filtros</option>
                                    <option value="otros">Otros</option>
                                </select>
                                <div class="field-error" id="err_repuestoCategoria"></div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="repuestoNumeroParte">Número de Parte</label>
                                    <input id="repuestoNumeroParte" name="numero_parte" class="form-control" 
                                           placeholder="Ej: FR-2024-BR200, OEM-12345" />
                                </div>
                                <div class="form-group">
                                    <label for="repuestoMarcaCompatible">Marca Compatible *</label>
                                    <input id="repuestoMarcaCompatible" name="marca_compatible" class="form-control" required 
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

                <!-- Accesorio -->
                <div class="form-step" id="step3-accesorio">
                    <div class="specifications-section">
                        <h5><i class="fas fa-helmet-safety"></i> Especificaciones del Accesorio</h5>
                        <form id="accesorioForm">
                            <div class="form-group">
                                <label for="accesorioSubtipo">Subtipo / Categoría *</label>
                                <select id="accesorioSubtipo" name="subtipo" class="form-control" required>
                                    <option value="">-- Selecciona el subtipo --</option>
                                    <option value="casco">Casco</option>
                                    <option value="chaqueta">Chaqueta</option>
                                    <option value="guantes">Guantes</option>
                                    <option value="botas">Botas</option>
                                    <option value="proteccion">Protección</option>
                                    <option value="maletas">Maletas/Alforjas</option>
                                    <option value="cubre">Cubrepuños/Cubretanques</option>
                                    <option value="luces">Luces adicionales</option>
                                    <option value="herramientas">Herramientas</option>
                                    <option value="otros">Otros</option>
                                </select>
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
                                    <input id="accesorioMarca" name="marca" class="form-control" required 
                                           placeholder="Ej: LS2, Shoei, Alpinestars" />
                                    <div class="field-error" id="err_accesorioMarca"></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="accesorioCertificaciones">Certificaciones</label>
                                <input id="accesorioCertificaciones" name="certificaciones" class="form-control" 
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

    <!-- Modal: Agregar Categoría (existente) -->
    <div class="modal-overlay" id="addCategoryModal">
        <div class="modal" style="max-width:420px;">
            <form id="addCategoryForm">
                <div class="modal-header">
                    <h3><i class="fas fa-tags"></i> Nueva Categoría</h3>
                    <button class="modal-close" id="closeAddCategoryModal" type="button">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="catNombre">Nombre *</label>
                        <input id="catNombre" name="nombre" class="form-control" required />
                        <div class="field-error" id="err_catNombre"></div>
                    </div>
                    <div class="form-group">
                        <label for="catDescripcion">Descripción</label>
                        <textarea id="catDescripcion" name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div id="addCategoryMsg" style="margin-top: 8px; display: none; padding: 10px; border-radius: 6px;"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" type="button" id="cancelAddCategory">Cancelar</button>
                    <button class="btn btn-primary" type="submit" id="saveAddCategory">Guardar Categoría</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Variables globales del sistema de pasos
        let currentStep = 1;
        let selectedType = null;
        const totalSteps = 4;

        // Elementos del DOM
        const addProductModal = document.getElementById('addProductModal');
        const addProductBtn = document.getElementById('addProductBtn');
        const closeAddProductModal = document.getElementById('closeAddProductModal');
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const btnCancel = document.getElementById('btnCancel');
        const btnSubmit = document.getElementById('btnSubmit');
        const steps = document.querySelectorAll('.form-step');
        const stepIndicators = document.querySelectorAll('.step');
        const typeButtons = document.querySelectorAll('.type-btn');

        // Función para generar código basado en tipo
        function generateProductCode() {
            if (!selectedType) return '';
            
            const prefixMap = {
                'vehiculo': 'VH',
                'repuesto': 'RP',
                'accesorio': 'AC'
            };
            
            const prefix = prefixMap[selectedType] || 'PR';
            const timestamp = Date.now().toString().slice(-6);
            const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            
            return `${prefix}-${timestamp}-${random}`;
        }

        // Inicializar el modal
        addProductBtn.addEventListener('click', () => {
            resetForm();
            addProductModal.classList.add('active');
            document.getElementById('prodCodigo').value = generateProductCode();
        });

        // Cerrar modal
        closeAddProductModal.addEventListener('click', hideAddProductModal);
        btnCancel.addEventListener('click', hideAddProductModal);

        addProductModal.addEventListener('click', (e) => {
            if (e.target === addProductModal) hideAddProductModal();
        });

        function hideAddProductModal() {
            addProductModal.classList.remove('active');
            resetForm();
        }

        // Selección de tipo de producto
        typeButtons.forEach(button => {
            button.addEventListener('click', () => {
                typeButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                selectedType = button.dataset.type;
                
                // Actualizar código al seleccionar tipo
                document.getElementById('prodCodigo').value = generateProductCode();
            });
        });

        // Navegación entre pasos
        btnPrev.addEventListener('click', prevStep);
        btnNext.addEventListener('click', nextStep);
        btnSubmit.addEventListener('click', submitForm);

        function updateStepIndicator() {
            stepIndicators.forEach((indicator, index) => {
                indicator.classList.remove('active', 'completed');
                
                if (index + 1 === currentStep) {
                    indicator.classList.add('active');
                } else if (index + 1 < currentStep) {
                    indicator.classList.add('completed');
                }
            });
        }

        function showStep(stepNumber) {
            // Ocultar todos los pasos
            steps.forEach(step => step.classList.remove('active'));
            
            // Determinar qué paso mostrar
            let stepId = `step${stepNumber}`;
            if (stepNumber === 3 && selectedType) {
                stepId = `step3-${selectedType}`;
            }
            
            // Mostrar paso actual
            const currentStepElement = document.getElementById(stepId);
            if (currentStepElement) {
                currentStepElement.classList.add('active');
            }
            
            // Actualizar estado de botones
            updateButtonStates();
            updateStepIndicator();
        }

        function updateButtonStates() {
            // Botón Anterior
            btnPrev.style.display = currentStep > 1 ? 'inline-flex' : 'none';
            
            // Botón Siguiente/Enviar
            if (currentStep === totalSteps) {
                btnNext.style.display = 'none';
                btnSubmit.style.display = 'inline-flex';
                generateSummary();
            } else {
                btnNext.style.display = 'inline-flex';
                btnSubmit.style.display = 'none';
            }
            
            // Texto del botón Siguiente
            btnNext.innerHTML = currentStep === 1 ? 'Continuar <i class="fas fa-arrow-right"></i>' : 'Siguiente <i class="fas fa-arrow-right"></i>';
        }

        // Función de validación mejorada
        function validateCurrentStep() {
            clearErrors();
            let isValid = true;

            switch(currentStep) {
                case 1: // Selección de tipo
                    if (!selectedType) {
                        showError('Por favor, selecciona un tipo de producto');
                        isValid = false;
                    }
                    return isValid;
                    
                case 2: // Información general
                    // Validar nombre
                    const nombre = document.getElementById('prodNombre').value.trim();
                    if (!nombre) {
                        showFieldError('prodNombre', 'El nombre del producto es requerido');
                        isValid = false;
                    } else if (nombre.length > 200) {
                        showFieldError('prodNombre', 'El nombre no puede exceder 200 caracteres');
                        isValid = false;
                    }
                    
                    // Validar stock
                    const stock = parseInt(document.getElementById('prodStock').value);
                    const stockMin = parseInt(document.getElementById('prodStockMin').value);
                    const stockMax = parseInt(document.getElementById('prodStockMax').value);
                    
                    if (isNaN(stock) || stock < 0) {
                        showFieldError('prodStock', 'Stock inicial inválido');
                        isValid = false;
                    }
                    
                    if (isNaN(stockMin) || stockMin < 0) {
                        showFieldError('prodStockMin', 'Stock mínimo inválido');
                        isValid = false;
                    }
                    
                    if (isNaN(stockMax) || stockMax < 0) {
                        showFieldError('prodStockMax', 'Stock máximo inválido');
                        isValid = false;
                    }
                    
                    if (isValid && stockMin > stockMax) {
                        showFieldError('prodStockMin', 'El stock mínimo no puede ser mayor al máximo');
                        isValid = false;
                    }
                    
                    if (isValid && stock < stockMin) {
                        showFieldError('prodStock', 'El stock inicial no puede ser menor al mínimo');
                        isValid = false;
                    }
                    
                    // Validar precios
                    const precioCompra = parseFloat(document.getElementById('prodPrecioCompra').value);
                    const precioVenta = parseFloat(document.getElementById('prodPrecioVenta').value);
                    
                    if (isNaN(precioCompra) || precioCompra < 0) {
                        showFieldError('prodPrecioCompra', 'Precio de compra inválido');
                        isValid = false;
                    }
                    
                    if (isNaN(precioVenta) || precioVenta < 0) {
                        showFieldError('prodPrecioVenta', 'Precio de venta inválido');
                        isValid = false;
                    }
                    
                    if (isValid && precioVenta < precioCompra) {
                        showFieldError('prodPrecioVenta', 'El precio de venta no puede ser menor al de compra');
                        isValid = false;
                    }
                    
                    return isValid;
                    
                case 3: // Especificaciones
                    return validateSpecifications();
                    
                default:
                    return true;
            }
        }

        function validateSpecifications() {
            let isValid = true;
            clearErrors();

            if (selectedType === 'vehiculo') {
                const fields = [
                    { id: 'vehiculoMarca', name: 'marca' },
                    { id: 'vehiculoModelo', name: 'modelo' },
                    { id: 'vehiculoAnio', name: 'año' },
                    { id: 'vehiculoCilindrada', name: 'cilindrada' },
                    { id: 'vehiculoColor', name: 'color' },
                    { id: 'vehiculoTipoMoto', name: 'tipo de moto' }
                ];

                fields.forEach(field => {
                    const element = document.getElementById(field.id);
                    const value = element.type === 'select-one' ? element.value : element.value.trim();
                    
                    if (!value) {
                        showFieldError(field.id, `El campo ${field.name} es requerido`);
                        isValid = false;
                    } else if (field.id === 'vehiculoAnio') {
                        const year = parseInt(value);
                        if (isNaN(year) || year < 1900 || year > new Date().getFullYear() + 1) {
                            showFieldError(field.id, 'Año inválido');
                            isValid = false;
                        }
                    }
                });

                const kilometraje = parseInt(document.getElementById('vehiculoKilometraje').value);
                if (kilometraje < 0) {
                    showFieldError('vehiculoKilometraje', 'Kilometraje inválido');
                    isValid = false;
                }
                
            } else if (selectedType === 'repuesto') {
                const categoria = document.getElementById('repuestoCategoria').value;
                const marcaCompatible = document.getElementById('repuestoMarcaCompatible').value.trim();
                
                if (!categoria) {
                    showFieldError('repuestoCategoria', 'La categoría es requerida');
                    isValid = false;
                }
                
                if (!marcaCompatible) {
                    showFieldError('repuestoMarcaCompatible', 'La marca compatible es requerida');
                    isValid = false;
                }
                
            } else if (selectedType === 'accesorio') {
                const subtipo = document.getElementById('accesorioSubtipo').value;
                const marca = document.getElementById('accesorioMarca').value.trim();
                
                if (!subtipo) {
                    showFieldError('accesorioSubtipo', 'El subtipo es requerido');
                    isValid = false;
                }
                
                if (!marca) {
                    showFieldError('accesorioMarca', 'La marca es requerida');
                    isValid = false;
                }
            }
            
            return isValid;
        }

        // Funciones auxiliares para manejo de errores
        function showError(message) {
            alert(message);
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
            // Limpiar todos los errores visuales
            document.querySelectorAll('.field-error').forEach(el => {
                el.classList.remove('active');
                el.textContent = '';
            });
            
            document.querySelectorAll('.form-control.error').forEach(el => {
                el.classList.remove('error');
            });
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

        function generateSummary() {
            const summaryContent = document.getElementById('summaryContent');
            let html = `
                <div class="summary-section">
                    <h6><i class="fas fa-info-circle"></i> Información General</h6>
                    <table class="summary-table">
                        <tr>
                            <td>Tipo de Producto:</td>
                            <td><strong>${getTypeName(selectedType)}</strong></td>
                        </tr>
                        <tr>
                            <td>Código Interno:</td>
                            <td><code>${document.getElementById('prodCodigo').value}</code></td>
                        </tr>
                        <tr>
                            <td>Nombre:</td>
                            <td>${document.getElementById('prodNombre').value}</td>
                        </tr>
                        <tr>
                            <td>Descripción:</td>
                            <td>${document.getElementById('prodDescripcion').value || '<em>Sin descripción</em>'}</td>
                        </tr>
                        <tr>
                            <td>Stock Inicial:</td>
                            <td>${document.getElementById('prodStock').value} unidades</td>
                        </tr>
                        <tr>
                            <td>Stock Mínimo:</td>
                            <td>${document.getElementById('prodStockMin').value} unidades</td>
                        </tr>
                        <tr>
                            <td>Stock Máximo:</td>
                            <td>${document.getElementById('prodStockMax').value} unidades</td>
                        </tr>
                        <tr>
                            <td>Precio Compra:</td>
                            <td><strong>$${parseFloat(document.getElementById('prodPrecioCompra').value).toFixed(2)}</strong></td>
                        </tr>
                        <tr>
                            <td>Precio Venta:</td>
                            <td><strong>$${parseFloat(document.getElementById('prodPrecioVenta').value).toFixed(2)}</strong></td>
                        </tr>
                    </table>
                </div>
            `;
            
            // Agregar especificaciones según el tipo
            html += `<div class="summary-section">
                <h6><i class="fas fa-list-alt"></i> Especificaciones</h6>
                <table class="summary-table">`;
            
            if (selectedType === 'vehiculo') {
                const tipoMoto = document.getElementById('vehiculoTipoMoto');
                const tipoMotoText = tipoMoto.options[tipoMoto.selectedIndex].text;
                
                html += `
                    <tr><td>Marca:</td><td>${document.getElementById('vehiculoMarca').value}</td></tr>
                    <tr><td>Modelo:</td><td>${document.getElementById('vehiculoModelo').value}</td></tr>
                    <tr><td>Año:</td><td>${document.getElementById('vehiculoAnio').value}</td></tr>
                    <tr><td>Cilindrada:</td><td>${document.getElementById('vehiculoCilindrada').value}</td></tr>
                    <tr><td>Color:</td><td>${document.getElementById('vehiculoColor').value}</td></tr>
                    <tr><td>Kilometraje:</td><td>${document.getElementById('vehiculoKilometraje').value || '0'} km</td></tr>
                    <tr><td>Tipo de Moto:</td><td>${tipoMotoText}</td></tr>
                `;
            } else if (selectedType === 'repuesto') {
                const categoria = document.getElementById('repuestoCategoria');
                const categoriaText = categoria.options[categoria.selectedIndex].text;
                
                html += `
                    <tr><td>Categoría:</td><td>${categoriaText}</td></tr>
                    <tr><td>Número de Parte:</td><td>${document.getElementById('repuestoNumeroParte').value || '<em>No especificado</em>'}</td></tr>
                    <tr><td>Marca Compatible:</td><td>${document.getElementById('repuestoMarcaCompatible').value}</td></tr>
                    <tr><td>Modelo Compatible:</td><td>${document.getElementById('repuestoModeloCompatible').value || '<em>No especificado</em>'}</td></tr>
                    <tr><td>Año Compatible:</td><td>${document.getElementById('repuestoAnioCompatible').value || '<em>No especificado</em>'}</td></tr>
                `;
            } else if (selectedType === 'accesorio') {
                const subtipo = document.getElementById('accesorioSubtipo');
                const subtipoText = subtipo.options[subtipo.selectedIndex].text;
                
                html += `
                    <tr><td>Subtipo:</td><td>${subtipoText}</td></tr>
                    <tr><td>Talla:</td><td>${document.getElementById('accesorioTalla').value || '<em>No especificada</em>'}</td></tr>
                    <tr><td>Color:</td><td>${document.getElementById('accesorioColor').value || '<em>No especificado</em>'}</td></tr>
                    <tr><td>Material:</td><td>${document.getElementById('accesorioMaterial').value || '<em>No especificado</em>'}</td></tr>
                    <tr><td>Marca:</td><td>${document.getElementById('accesorioMarca').value}</td></tr>
                    <tr><td>Certificaciones:</td><td>${document.getElementById('accesorioCertificaciones').value || '<em>No especificadas</em>'}</td></tr>
                `;
            }
            
            html += `</table></div>`;
            summaryContent.innerHTML = html;
        }

        function getTypeName(type) {
            switch(type) {
                case 'vehiculo': return 'Vehículo';
                case 'repuesto': return 'Repuesto';
                case 'accesorio': return 'Accesorio';
                default: return type;
            }
        }

        async function submitForm() {
            try {
                // Validar antes de enviar
                if (!validateCurrentStep()) {
                    showError('Por favor, corrige los errores antes de guardar');
                    return;
                }

                // Mostrar estado de carga
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                btnSubmit.disabled = true;
                
                // Recolectar datos del formulario general
                const generalData = {
                    codigo_interno: document.getElementById('prodCodigo').value,
                    nombre: document.getElementById('prodNombre').value,
                    descripcion: document.getElementById('prodDescripcion').value,
                    stock_actual: document.getElementById('prodStock').value,
                    stock_minimo: document.getElementById('prodStockMin').value,
                    stock_maximo: document.getElementById('prodStockMax').value,
                    precio_compra: document.getElementById('prodPrecioCompra').value,
                    precio_venta: document.getElementById('prodPrecioVenta').value,
                    tipo_producto: selectedType
                };
                
                // Recolectar especificaciones según el tipo
                let especificaciones = { tipo: selectedType };
                
                if (selectedType === 'vehiculo') {
                    especificaciones = {
                        tipo: 'vehiculo',
                        marca: document.getElementById('vehiculoMarca').value,
                        modelo: document.getElementById('vehiculoModelo').value,
                        anio: document.getElementById('vehiculoAnio').value,
                        cilindrada: document.getElementById('vehiculoCilindrada').value,
                        color: document.getElementById('vehiculoColor').value,
                        kilometraje: parseInt(document.getElementById('vehiculoKilometraje').value) || 0,
                        tipo_moto: document.getElementById('vehiculoTipoMoto').value
                    };
                } else if (selectedType === 'repuesto') {
                    especificaciones = {
                        tipo: 'repuesto',
                        categoria: document.getElementById('repuestoCategoria').value,
                        numero_parte: document.getElementById('repuestoNumeroParte').value,
                        marca_compatible: document.getElementById('repuestoMarcaCompatible').value,
                        modelo_compatible: document.getElementById('repuestoModeloCompatible').value,
                        anio_compatible: document.getElementById('repuestoAnioCompatible').value
                    };
                } else if (selectedType === 'accesorio') {
                    especificaciones = {
                        tipo: 'accesorio',
                        subtipo: document.getElementById('accesorioSubtipo').value,
                        talla: document.getElementById('accesorioTalla').value,
                        color: document.getElementById('accesorioColor').value,
                        material: document.getElementById('accesorioMaterial').value,
                        marca: document.getElementById('accesorioMarca').value,
                        certificaciones: document.getElementById('accesorioCertificaciones').value
                    };
                }
                
                // Combinar datos
                const productData = {
                    ...generalData,
                    especificaciones: JSON.stringify(especificaciones)
                };
                
                // Crear FormData para enviar imágenes
                const formData = new FormData();
                
                // Agregar datos del producto
                for (const key in productData) {
                    if (productData[key] !== null && productData[key] !== undefined) {
                        formData.append(key, productData[key]);
                    }
                }
                
                // Agregar imágenes según el tipo
                let imageInput;
                if (selectedType === 'vehiculo') {
                    imageInput = document.getElementById('vehiculoImages');
                } else if (selectedType === 'repuesto') {
                    imageInput = document.getElementById('repuestoImages');
                } else if (selectedType === 'accesorio') {
                    imageInput = document.getElementById('accesorioImages');
                }
                
                if (imageInput && imageInput.files.length > 0) {
                    for (let i = 0; i < imageInput.files.length; i++) {
                        formData.append('images[]', imageInput.files[i]);
                    }
                }
                
                console.log('Datos a enviar:', productData);
                
                // Simulación de envío (reemplazar con tu API real)
                setTimeout(() => {
                    const finalMessage = document.getElementById('finalMessage');
                    finalMessage.className = 'final-message success';
                    finalMessage.innerHTML = `
                        <div style="display: flex; align-items: flex-start; gap: 15px;">
                            <i class="fas fa-check-circle fa-2x"></i>
                            <div>
                                <strong style="font-size: 16px;">✅ Producto creado exitosamente</strong>
                                <div style="margin-top: 10px; font-size: 14px;">
                                    <strong>${productData.nombre}</strong> ha sido registrado en el inventario con el código <code>${productData.codigo_interno}</code>.
                                </div>
                                <div style="margin-top: 15px; font-size: 13px; color: #0c4128;">
                                    <i class="fas fa-info-circle"></i> Este formulario se cerrará automáticamente en 3 segundos.
                                </div>
                            </div>
                        </div>
                    `;
                    finalMessage.style.display = 'block';
                    
                    // Scroll al mensaje
                    finalMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Restaurar botón
                    btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar Producto';
                    btnSubmit.disabled = false;
                    
                    // Cerrar automáticamente después de 3 segundos
                    setTimeout(() => {
                        hideAddProductModal();
                        // Recargar inventario si es necesario
                        if (typeof fetchInventory === 'function') {
                            fetchInventory();
                        }
                    }, 3000);
                    
                }, 1500);
                
                /*
                // Código real para enviar al servidor:
                const response = await fetch('/inversiones-rojas/api/add_product.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                
                if (result.ok) {
                    // Mostrar mensaje de éxito
                    const finalMessage = document.getElementById('finalMessage');
                    finalMessage.className = 'final-message success';
                    finalMessage.textContent = '✅ Producto creado exitosamente';
                    finalMessage.style.display = 'block';
                    
                    // Cerrar modal después de 3 segundos
                    setTimeout(() => {
                        hideAddProductModal();
                        fetchInventory(); // Recargar lista
                    }, 3000);
                } else {
                    // Mostrar error
                    const finalMessage = document.getElementById('finalMessage');
                    finalMessage.className = 'final-message error';
                    finalMessage.textContent = '❌ Error: ' + (result.error || 'No se pudo guardar el producto');
                    finalMessage.style.display = 'block';
                    btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar Producto';
                    btnSubmit.disabled = false;
                }
                */
                
            } catch (error) {
                console.error('Error al guardar producto:', error);
                const finalMessage = document.getElementById('finalMessage');
                finalMessage.className = 'final-message error';
                finalMessage.textContent = '❌ Error al guardar el producto';
                finalMessage.style.display = 'block';
                btnSubmit.innerHTML = '<i class="fas fa-save"></i> Guardar Producto';
                btnSubmit.disabled = false;
            }
        }

        function resetForm() {
            // Resetear variables
            currentStep = 1;
            selectedType = null;
            
            // Resetear UI
            typeButtons.forEach(btn => btn.classList.remove('active'));
            steps.forEach(step => step.classList.remove('active'));
            document.getElementById('step1').classList.add('active');
            stepIndicators.forEach(indicator => {
                indicator.classList.remove('active', 'completed');
            });
            document.getElementById('step1-indicator').classList.add('active');
            
            // Resetear formularios
            document.getElementById('generalProductForm').reset();
            document.getElementById('vehiculoForm')?.reset();
            document.getElementById('repuestoForm')?.reset();
            document.getElementById('accesorioForm')?.reset();
            
            // Resetear selects
            document.querySelectorAll('select').forEach(select => {
                select.selectedIndex = 0;
            });
            
            // Resetear previews de imágenes
            ['vehiculoPreviewList', 'repuestoPreviewList', 'accesorioPreviewList'].forEach(id => {
                const element = document.getElementById(id);
                if (element) element.innerHTML = '';
            });
            
            // Resetear inputs de archivos
            ['vehiculoImages', 'repuestoImages', 'accesorioImages'].forEach(id => {
                const element = document.getElementById(id);
                if (element) element.value = '';
            });
            
            // Resetear mensajes
            const finalMessage = document.getElementById('finalMessage');
            if (finalMessage) {
                finalMessage.style.display = 'none';
                finalMessage.className = 'final-message';
                finalMessage.innerHTML = '';
            }
            
            // Generar nuevo código
            document.getElementById('prodCodigo').value = generateProductCode();
            
            // Limpiar errores
            clearErrors();
            
            // Actualizar botones
            updateButtonStates();
        }

        // Inicializar sistema de imágenes y validaciones
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar image uploaders para cada tipo
            setupImageUploader('vehiculo');
            setupImageUploader('repuesto');
            setupImageUploader('accesorio');
            
            // Validación en tiempo real para precios
            document.getElementById('prodPrecioVenta')?.addEventListener('blur', function() {
                const precioCompra = parseFloat(document.getElementById('prodPrecioCompra').value) || 0;
                const precioVenta = parseFloat(this.value) || 0;
                
                if (precioVenta < precioCompra) {
                    showFieldError('prodPrecioVenta', 'El precio de venta no puede ser menor al precio de compra');
                    this.value = precioCompra;
                    this.focus();
                } else {
                    clearErrors();
                }
            });
            
            // Validación en tiempo real para stock
            document.getElementById('prodStock')?.addEventListener('blur', function() {
                const stock = parseInt(this.value) || 0;
                const stockMin = parseInt(document.getElementById('prodStockMin').value) || 0;
                
                if (stock < stockMin) {
                    showFieldError('prodStock', 'El stock inicial no puede ser menor al mínimo');
                }
            });
            
            // Validación en tiempo real para stock mínimo
            document.getElementById('prodStockMin')?.addEventListener('blur', function() {
                const stockMin = parseInt(this.value) || 0;
                const stockMax = parseInt(document.getElementById('prodStockMax').value) || 0;
                
                if (stockMin > stockMax) {
                    showFieldError('prodStockMin', 'El stock mínimo no puede ser mayor al máximo');
                }
            });
        });

        function setupImageUploader(type) {
            const selectBtn = document.getElementById(`select${type.charAt(0).toUpperCase() + type.slice(1)}ImagesBtn`);
            const hiddenInput = document.getElementById(`${type}Images`);
            const previewList = document.getElementById(`${type}PreviewList`);
            const maxFiles = 6;
            const maxBytes = 5 * 1024 * 1024;

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
            
            // Limpiar error
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
            
            // Crear previews
            previewList.innerHTML = '';
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
                    // Crear nueva lista de archivos sin el eliminado
                    const dataTransfer = new DataTransfer();
                    Array.from(hiddenInput.files).forEach((f, index) => {
                        if (index !== idx) {
                            dataTransfer.items.add(f);
                        }
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
    </script>
</body>
</html>