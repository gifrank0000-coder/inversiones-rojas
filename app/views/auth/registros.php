<?php require_once __DIR__ . '/../../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registros del Sistema - Inversiones Rojas</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/auth.css">
    <style>
    .auth-back-btn {
        position: fixed;
        top: 14px;
        left: 14px;
        color: #fff;
        padding: 12px 16px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        font-weight: 600;
        text-decoration: none;
        z-index: 9999;
        padding-left: 14px;
    }
    .auth-back-btn i { color: #fff; font-size: 24px; line-height: 1; }
    .auth-back-btn span { color: #fff; display: inline-block; transform: translateY(-1px); font-size:15px; }
    .auth-back-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(31,145,102,0.22); }
    
    .registros-container {
        max-width: 800px;
        margin: 20px auto;
        padding: 20px;
    }

    .registro-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin-bottom: 30px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .registro-card:hover {
        border-color: #1F9166;
        box-shadow: 0 25px 50px rgba(31, 145, 102, 0.15);
    }

    .registro-card:last-child {
        margin-bottom: 0;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .stock-grid, .precio-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 15px;
    }

    .document-type {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
    }

    .doc-option {
        flex: 1;
        text-align: center;
    }

    .doc-option input[type="radio"] {
        display: none;
    }

    .doc-label {
        display: block;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 500;
        background: white;
    }

    .doc-option input[type="radio"]:checked + .doc-label {
        border-color: #1F9166;
        background: #f0f9f4;
        color: #1F9166;
    }

    .section-title {
        color: #1F9166;
        font-size: 1.4rem;
        margin-bottom: 25px;
        padding-bottom: 10px;
        border-bottom: 2px solid #1F9166;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .image-upload {
        border: 2px dashed #e0e0e0;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 20px;
        background: #f8f9fa;
    }

    .image-upload:hover {
        border-color: #1F9166;
        background: #f0f9f4;
    }

    .image-upload i {
        font-size: 48px;
        color: #6c757d;
        margin-bottom: 10px;
    }

    .btn-group {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        margin-top: 25px;
    }

    /* Estilos mejorados para selects */
    .input-group select {
        width: 100%;
        padding: 12px 15px 12px 45px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        background: white;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 12px;
        transition: all 0.3s;
    }

    .input-group select:focus {
        border-color: #1F9166;
        outline: none;
        box-shadow: 0 0 0 3px rgba(31, 145, 102, 0.1);
    }

    .input-group select:hover {
        border-color: #1F9166;
    }

    /* Estilos para textarea */
    .input-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        font-family: inherit;
        resize: vertical;
        min-height: 80px;
        transition: all 0.3s;
    }

    .input-group textarea:focus {
        border-color: #1F9166;
        outline: none;
        box-shadow: 0 0 0 3px rgba(31, 145, 102, 0.1);
    }

    /* Mejoras visuales generales */
    .auth-form {
        margin-top: 10px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 8px;
        display: block;
        font-size: 14px;
    }

    .input-group {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-group i {
        position: absolute;
        left: 15px;
        color: #6c757d;
        z-index: 2;
        font-size: 16px;
    }

    .input-group input {
        width: 100%;
        padding: 12px 15px 12px 45px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s;
        background: white;
    }

    .input-group input:focus {
        border-color: #1F9166;
        outline: none;
        box-shadow: 0 0 0 3px rgba(31, 145, 102, 0.1);
    }

    .auth-btn {
        padding: 15px 25px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        flex: 1;
        justify-content: center;
    }

    .auth-btn.primary {
        background: #1F9166;
        color: white;
    }

    .auth-btn.primary:hover {
        background: #187a54;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(31, 145, 102, 0.3);
    }

    .auth-btn.secondary {
        background: #f8f9fa;
        color: #333;
        border: 2px solid #e0e0e0;
    }

    .auth-btn.secondary:hover {
        background: #e9ecef;
        border-color: #1F9166;
        color: #1F9166;
    }

    @media (max-width: 768px) {
        .form-row,
        .stock-grid,
        .precio-grid,
        .document-type {
            grid-template-columns: 1fr;
            flex-direction: column;
        }
        
        .registros-container {
            padding: 15px;
            margin: 15px;
        }
        
        .registro-card {
            padding: 20px;
        }
        
        .btn-group {
            flex-direction: column;
        }
        
        .auth-btn {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .registros-container {
            padding: 10px;
            margin: 10px;
        }
        
        .registro-card {
            padding: 15px;
        }
        
        .auth-back-btn { 
            top:10px; 
            left:8px; 
            padding:8px 10px; 
            font-size:14px; 
            border-radius:10px; 
            gap:8px; 
        }
        
        .auth-back-btn i { font-size:20px; }
        
        .section-title {
            font-size: 1.2rem;
        }
    }
    </style>
</head>
<body class="auth-page">
    <!-- Botón de volver -->
    <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php" class="auth-back-btn">
        <i class="fas fa-arrow-left"></i>
        <span>Volver al Dashboard</span>
    </a>

    <div class="registros-container">
        <!-- ========== REGISTRO DE CLIENTES ========== -->
        <div class="registro-card">
            <div class="auth-header">
                <div class="auth-logo">
                    
                </div>
                <h3 class="section-title">
                    <i class="fas fa-users"></i>
                    Registro de Clientes
                </h3>
                <p>Complete la información del nuevo cliente</p>
            </div>

            <form class="auth-form" id="clientForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombres">Nombres *</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="nombres" name="nombres" required placeholder="Tus nombres">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="apellidos">Apellidos *</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="apellidos" name="apellidos" required placeholder="Tus apellidos">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tipo de Documento *</label>
                    <div class="document-type">
                        <div class="doc-option">
                            <input type="radio" id="cedula" name="tipo_documento" value="cedula" checked>
                            <label for="cedula" class="doc-label">Cédula</label>
                        </div>
                        <div class="doc-option">
                            <input type="radio" id="rif" name="tipo_documento" value="rif">
                            <label for="rif" class="doc-label">RIF</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="documento">Número de Documento *</label>
                    <div class="input-group">
                        <i class="fas fa-id-card"></i>
                        <input type="text" id="documento" name="documento" required placeholder="Ej: V-12345678 o J-123456789">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono">Teléfono *</label>
                        <div class="input-group">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="telefono" name="telefono" required placeholder="0412-1234567">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="telefono_alt">Teléfono Alternativo</label>
                        <div class="input-group">
                            <i class="fas fa-phone-alt"></i>
                            <input type="tel" id="telefono_alt" name="telefono_alt" placeholder="0424-1234567">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="cliente@email.com">
                    </div>
                </div>

                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <div class="input-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" id="direccion" name="direccion" placeholder="Dirección completa">
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="auth-btn primary">
                        <i class="fas fa-save"></i>
                        Registrar
                    </button>
                    <button type="button" class="auth-btn secondary" onclick="limpiarFormulario('clientForm')">
                        <i class="fas fa-broom"></i>
                        Limpiar
                    </button>
                </div>
            </form>
        </div>

        <!-- ========== REGISTRO DE PROVEEDORES ========== -->
        <div class="registro-card">
            <h3 class="section-title">
                <i class="fas fa-truck"></i>
                Registro de Proveedores
            </h3>

            <form class="auth-form" id="proveedorForm">
                <div class="form-group">
                    <label for="razon_social">Razón Social *</label>
                    <div class="input-group">
                        <i class="fas fa-building"></i>
                        <input type="text" id="razon_social" name="razon_social" required placeholder="Nombre o razón social">
                    </div>
                </div>

                <div class="form-group">
                    <label for="rif_proveedor">RIF *</label>
                    <div class="input-group">
                        <i class="fas fa-id-card"></i>
                        <input type="text" id="rif_proveedor" name="rif" required placeholder="J-123456789">
                    </div>
                </div>

                <div class="form-group">
                    <label for="contacto">Persona de Contacto *</label>
                    <div class="input-group">
                        <i class="fas fa-user-tie"></i>
                        <input type="text" id="contacto" name="contacto" required placeholder="Nombre del contacto">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono_proveedor">Teléfono *</label>
                        <div class="input-group">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="telefono_proveedor" name="telefono" required placeholder="0412-1234567">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="telefono_alt_proveedor">Teléfono Alternativo</label>
                        <div class="input-group">
                            <i class="fas fa-phone-alt"></i>
                            <input type="tel" id="telefono_alt_proveedor" name="telefono_alt" placeholder="0424-1234567">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email_proveedor">Correo Electrónico</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email_proveedor" name="email" placeholder="proveedor@empresa.com">
                    </div>
                </div>

                <div class="form-group">
                    <label for="direccion_proveedor">Dirección</label>
                    <div class="input-group">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" id="direccion_proveedor" name="direccion" placeholder="Dirección de la empresa">
                    </div>
                </div>

                <div class="form-group">
                    <label for="productos_suministrados">Productos Suministrados</label>
                    <div class="input-group">
                        <i class="fas fa-boxes"></i>
                        <textarea id="productos_suministrados" name="productos_suministrados" placeholder="Lista de productos que suministra" rows="3"></textarea>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="auth-btn primary">
                        <i class="fas fa-save"></i>
                        Registrar
                    </button>
                    <button type="button" class="auth-btn secondary" onclick="limpiarFormulario('proveedorForm')">
                        <i class="fas fa-broom"></i>
                        Limpiar
                    </button>
                </div>
            </form>
        </div>

        <!-- ========== REGISTRO DE PRODUCTOS ========== -->
        <div class="registro-card">
            <h3 class="section-title">
                <i class="fas fa-box"></i>
                Registro de Productos
            </h3>

            <form class="auth-form" id="productoForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="codigo">Código del Producto *</label>
                        <div class="input-group">
                            <i class="fas fa-barcode"></i>
                            <input type="text" id="codigo" name="codigo" required placeholder="Código interno">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="nombre_producto">Nombre *</label>
                        <div class="input-group">
                            <i class="fas fa-tag"></i>
                            <input type="text" id="nombre_producto" name="nombre" required placeholder="Nombre del producto">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <div class="input-group">
                        <i class="fas fa-file-alt"></i>
                        <textarea id="descripcion" name="descripcion" placeholder="Descripción detallada del producto" rows="3"></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="categoria">Categoría *</label>
                        <div class="input-group">
                            <i class="fas fa-folder"></i>
                            <select id="categoria" name="categoria" required>
                                <option value="">Seleccionar categoría</option>
                                <option value="motos">Motos</option>
                                <option value="repuestos">Repuestos</option>
                                <option value="accesorios">Accesorios</option>
                                <option value="lubricantes">Lubricantes</option>
                                <option value="herramientas">Herramientas</option>
                                <option value="seguridad">Equipo de Seguridad</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="proveedor_producto">Proveedor *</label>
                        <div class="input-group">
                            <i class="fas fa-truck"></i>
                            <select id="proveedor_producto" name="proveedor" required>
                                <option value="">Seleccionar proveedor</option>
                                <option value="1">Bera Motors</option>
                                <option value="2">Empire Parts</option>
                                <option value="3">Repuestos Venezuela</option>
                                <option value="4">Moto Accesorios CA</option>
                                <option value="5">Distribuidora Aragua</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="precio-grid">
                    <div class="form-group">
                        <label for="precio_compra">Precio de Compra *</label>
                        <div class="input-group">
                            <i class="fas fa-dollar-sign"></i>
                            <input type="number" id="precio_compra" name="precio_compra" required step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="precio_venta">Precio de Venta *</label>
                        <div class="input-group">
                            <i class="fas fa-dollar-sign"></i>
                            <input type="number" id="precio_venta" name="precio_venta" required step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="margen">Margen (%)</label>
                        <div class="input-group">
                            <i class="fas fa-percentage"></i>
                            <input type="text" id="margen" name="margen" readonly placeholder="Auto-calculado" style="background: #f8f9fa;">
                        </div>
                    </div>
                </div>

                <div class="stock-grid">
                    <div class="form-group">
                        <label for="stock_actual">Stock Actual *</label>
                        <div class="input-group">
                            <i class="fas fa-boxes"></i>
                            <input type="number" id="stock_actual" name="stock_actual" required min="0" placeholder="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="stock_minimo">Stock Mínimo *</label>
                        <div class="input-group">
                            <i class="fas fa-exclamation-triangle"></i>
                            <input type="number" id="stock_minimo" name="stock_minimo" required min="0" placeholder="5">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="stock_maximo">Stock Máximo</label>
                        <div class="input-group">
                            <i class="fas fa-warehouse"></i>
                            <input type="number" id="stock_maximo" name="stock_maximo" min="0" placeholder="100">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Imagen del Producto</label>
                    <div class="image-upload" onclick="document.getElementById('imagenProducto').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Haz clic para subir una imagen</p>
                        <input type="file" id="imagenProducto" name="imagen" accept="image/*" style="display: none;">
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="auth-btn primary">
                        <i class="fas fa-save"></i>
                        Registrar
                    </button>
                    <button type="button" class="auth-btn secondary" onclick="limpiarFormulario('productoForm')">
                        <i class="fas fa-broom"></i>
                        Limpiar
                    </button>
                </div>
            </form>
        </div>

        <!-- ========== REGISTRO DE CATEGORÍAS ========== -->
        <div class="registro-card">
            <h3 class="section-title">
                <i class="fas fa-folder"></i>
                Registro de Categorías
            </h3>

            <form class="auth-form" id="categoriaForm">
                <div class="form-group">
                    <label for="nombre_categoria">Nombre de Categoría *</label>
                    <div class="input-group">
                        <i class="fas fa-tag"></i>
                        <input type="text" id="nombre_categoria" name="nombre" required placeholder="Nombre de la categoría">
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion_categoria">Descripción</label>
                    <div class="input-group">
                        <i class="fas fa-file-alt"></i>
                        <textarea id="descripcion_categoria" name="descripcion" placeholder="Descripción de la categoría" rows="3"></textarea>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="auth-btn primary">
                        <i class="fas fa-save"></i>
                        Registrar
                    </button>
                    <button type="button" class="auth-btn secondary" onclick="limpiarFormulario('categoriaForm')">
                        <i class="fas fa-broom"></i>
                        Limpiar
                    </button>
                </div>
            </form>
        </div>

        <!-- ========== REGISTRO DE MÉTODOS DE PAGO ========== -->
        <div class="registro-card">
            <h3 class="section-title">
                <i class="fas fa-credit-card"></i>
                Registro de Métodos de Pago
            </h3>

            <form class="auth-form" id="metodoPagoForm">
                <div class="form-group">
                    <label for="nombre_metodo">Nombre del Método *</label>
                    <div class="input-group">
                        <i class="fas fa-credit-card"></i>
                        <input type="text" id="nombre_metodo" name="nombre" required placeholder="Ej: Efectivo, Transferencia, Pago Móvil">
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion_metodo">Descripción</label>
                    <div class="input-group">
                        <i class="fas fa-file-alt"></i>
                        <textarea id="descripcion_metodo" name="descripcion" placeholder="Descripción del método de pago" rows="2"></textarea>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="auth-btn primary">
                        <i class="fas fa-save"></i>
                        Registrar
                    </button>
                    <button type="button" class="auth-btn secondary" onclick="limpiarFormulario('metodoPagoForm')">
                        <i class="fas fa-broom"></i>
                        Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Función para limpiar formularios
        function limpiarFormulario(formId) {
            document.getElementById(formId).reset();
            // Resetear cálculo de margen si existe
            if (formId === 'productoForm') {
                document.getElementById('margen').value = '';
            }
        }

        // Calcular margen automáticamente
        document.getElementById('precio_compra').addEventListener('input', calcularMargen);
        document.getElementById('precio_venta').addEventListener('input', calcularMargen);

        function calcularMargen() {
            const precioCompra = parseFloat(document.getElementById('precio_compra').value) || 0;
            const precioVenta = parseFloat(document.getElementById('precio_venta').value) || 0;
            
            if (precioCompra > 0 && precioVenta > 0) {
                const margen = ((precioVenta - precioCompra) / precioCompra) * 100;
                document.getElementById('margen').value = margen.toFixed(2) + '%';
            } else {
                document.getElementById('margen').value = '';
            }
        }

        // Validaciones de formularios
        document.getElementById('clientForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (validarCliente()) {
                enviarFormulario(this, 'Cliente registrado exitosamente');
            }
        });

        document.getElementById('proveedorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (validarProveedor()) {
                enviarFormulario(this, 'Proveedor registrado exitosamente');
            }
        });

        document.getElementById('productoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (validarProducto()) {
                enviarFormulario(this, 'Producto registrado exitosamente');
            }
        });

        document.getElementById('categoriaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            enviarFormulario(this, 'Categoría registrada exitosamente');
        });

        document.getElementById('metodoPagoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            enviarFormulario(this, 'Método de pago registrado exitosamente');
        });

        function validarCliente() {
            const documento = document.getElementById('documento').value;
            const tipoDoc = document.querySelector('input[name="tipo_documento"]:checked').value;
            const telefono = document.getElementById('telefono').value;
            
            if (tipoDoc === 'cedula' && !/^[VE]-\d{8}$/.test(documento)) {
                alert('Formato de cédula inválido. Use V-12345678');
                return false;
            }
            if (tipoDoc === 'rif' && !/^[J]-\d{9}$/.test(documento)) {
                alert('Formato de RIF inválido. Use J-123456789');
                return false;
            }
            if (!/^\d{4}-\d{7}$/.test(telefono)) {
                alert('Formato de teléfono inválido. Use 0412-1234567');
                return false;
            }
            return true;
        }

        function validarProveedor() {
            const rif = document.getElementById('rif_proveedor').value;
            const telefono = document.getElementById('telefono_proveedor').value;
            
            if (!/^[J]-\d{9}$/.test(rif)) {
                alert('Formato de RIF inválido. Use J-123456789');
                return false;
            }
            if (!/^\d{4}-\d{7}$/.test(telefono)) {
                alert('Formato de teléfono inválido. Use 0412-1234567');
                return false;
            }
            return true;
        }

        function validarProducto() {
            const precioCompra = parseFloat(document.getElementById('precio_compra').value);
            const precioVenta = parseFloat(document.getElementById('precio_venta').value);
            const stockMinimo = parseInt(document.getElementById('stock_minimo').value);
            const stockMaximo = parseInt(document.getElementById('stock_maximo').value);
            
            if (precioVenta <= precioCompra) {
                alert('El precio de venta debe ser mayor al precio de compra');
                return false;
            }
            if (stockMaximo > 0 && stockMinimo >= stockMaximo) {
                alert('El stock mínimo debe ser menor al stock máximo');
                return false;
            }
            return true;
        }

        function enviarFormulario(form, mensaje) {
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            submitBtn.disabled = true;
            
            // Simular envío al servidor
            setTimeout(() => {
                alert(mensaje);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                form.reset();
                calcularMargen(); // Resetear cálculo de margen
            }, 1500);
        }

        // Prevenir envío con Enter en campos numéricos
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const target = e.target;
                if (target.type === 'number') {
                    e.preventDefault();
                }
            }
        });

        // Mostrar nombre de archivo seleccionado
        document.getElementById('imagenProducto').addEventListener('change', function(e) {
            const fileName = this.files[0]?.name;
            if (fileName) {
                const uploadArea = this.parentElement;
                uploadArea.innerHTML = `
                    <i class="fas fa-check-circle" style="color: #1F9166;"></i>
                    <p>Archivo seleccionado: ${fileName}</p>
                    <small>Haz clic para cambiar</small>
                `;
                uploadArea.onclick = () => document.getElementById('imagenProducto').click();
            }
        });
    </script>
</body>
</html>