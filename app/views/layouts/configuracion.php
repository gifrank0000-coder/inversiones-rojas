<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - Inversiones Rojas</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/css/admin/dashboard.css">
     <link rel="stylesheet" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/css/base.css">
     <link rel="stylesheet" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/css/components/user-panel.css">
     <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      
       * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-main {
            flex: 1;
            margin-left: 280px;
            transition: all 0.3s ease;
        }

 
        .admin-header {
            background: white;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        .header-left h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .breadcrumb a {
            color: #1F9166;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

    
        .admin-content {
            padding: 30px;
        }


        .config-tabs {
            background: white;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .tabs-header {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 0 20px;
        }

        .tab-btn {
            padding: 15px 25px;
            background: none;
            border: none;
            font-size: 0.95rem;
            font-weight: 600;
            color: #6c757d;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            color: #2c3e50;
        }

        .tab-btn.active {
            color: #1F9166;
            background: white;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #1F9166;
        }

        .tab-content {
            display: none;
            padding: 30px;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .config-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f3f5;
        }

        .section-header h2 {
            color: #2c3e50;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-header h2 i {
            color: #1F9166;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #1F9166;
            background: white;
            box-shadow: 0 0 0 3px rgba(31, 145, 102, 0.1);
        }

        .form-hint {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 6px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #1F9166;
        }

        input:checked + .slider:before {
            transform: translateX(30px);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1F9166 0%, #30B583 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(31, 145, 102, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #e9ecef;
            color: #2c3e50;
        }

        .btn-outline:hover {
            border-color: #1F9166;
            color: #1F9166;
            transform: translateY(-2px);
        }

        .permissions-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .table-header {
            display: grid;
            grid-template-columns: 200px repeat(7, 1fr);
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .table-row {
            display: grid;
            grid-template-columns: 200px repeat(7, 1fr);
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            align-items: center;
            transition: background 0.3s;
        }

        .table-row:hover {
            background: #f8f9fa;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .permission-cell {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .permission-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
            animation: fadeIn 0.3s;
        }

        .modal {
            background: white;
            border-radius: 12px;
            width: 500px;
            max-width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
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
            color: #2c3e50;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #6c757d;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: #e9ecef;
            color: #2c3e50;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }


        @media (max-width: 1200px) {
            .table-header,
            .table-row {
                grid-template-columns: 150px repeat(4, 1fr);
                overflow-x: auto;
            }
            
            .table-header div:nth-child(n+6),
            .table-row div:nth-child(n+6) {
                display: none;
            }
        }

        @media (max-width: 1024px) {
            .admin-main {
                margin-left: 0;
            }
            
            .table-header,
            .table-row {
                grid-template-columns: 120px repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .admin-content {
                padding: 20px;
            }
            
            .tabs-header {
                flex-direction: column;
            }
            
            .tab-btn {
                width: 100%;
                justify-content: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header,
            .table-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .table-header div,
            .table-row div {
                text-align: center;
            }
            
            .modal {
                width: 95%;
            }
        }

        @media (max-width: 480px) {
            .admin-header {
                padding: 15px 20px;
            }
            
            .admin-content {
                padding: 15px;
            }
            
            .btn {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .config-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
           

                <!-- Tabs de Configuración -->
                <div class="config-tabs">
                    <div class="tabs-header">
                        <button class="tab-btn active" data-tab="general">
                                      
                                            <span>Configuración</span>
                            General
                  
                        <button class="tab-btn" data-tab="security">
                            <i class="fas fa-shield-alt"></i>
                            Seguridad
                        </button>
                        <button class="tab-btn" data-tab="users">
                            <i class="fas fa-users-cog"></i>
                            Usuarios y Roles
                        </button>
                        <button class="tab-btn" data-tab="business">
                            <i class="fas fa-building"></i>
                            Empresa
                        </button>
                        <button class="tab-btn" data-tab="backup">
                            <i class="fas fa-database"></i>
                            Backup
                        </button>
                    </div>

                    <!-- Tab: Configuración General -->
                    <div class="tab-content active" id="tab-general">
                        <div class="config-section">
                            <div class="section-header">
                                <h2><i class="fas fa-cogs"></i> Configuración General del Sistema</h2>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="systemName">
                                        <i class="fas fa-signature"></i> Nombre del Sistema
                                    </label>
                                    <input type="text" id="systemName" class="form-control" value="Inversiones Rojas ERP">
                                    <div class="form-hint">Nombre que aparece en el sistema</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="systemCurrency">
                                        <i class="fas fa-dollar-sign"></i> Moneda del Sistema
                                    </label>
                                    <select id="systemCurrency" class="form-control">
                                        <option value="USD" selected>USD - Dólar Americano</option>
                                        <option value="VES">VES - Bolívar Soberano</option>
                                        <option value="EUR">EUR - Euro</option>
                                    </select>
                                    <div class="form-hint">Moneda para cálculos y reportes</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="timezone">
                                        <i class="fas fa-clock"></i> Zona Horaria
                                    </label>
                                    <select id="timezone" class="form-control">
                                        <option value="America/Caracas" selected>Caracas (GMT-4)</option>
                                        <option value="America/Mexico_City">Ciudad de México (GMT-6)</option>
                                        <option value="America/New_York">Nueva York (GMT-5)</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="dateFormat">
                                        <i class="fas fa-calendar"></i> Formato de Fecha
                                    </label>
                                    <select id="dateFormat" class="form-control">
                                        <option value="d/m/Y" selected>DD/MM/YYYY</option>
                                        <option value="m/d/Y">MM/DD/YYYY</option>
                                        <option value="Y-m-d">YYYY-MM-DD</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="language">
                                        <i class="fas fa-language"></i> Idioma del Sistema
                                    </label>
                                    <select id="language" class="form-control">
                                        <option value="es" selected>Español</option>
                                        <option value="en">Inglés</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="taxRate">
                                        <i class="fas fa-percentage"></i> Tasa de Impuesto (%)
                                    </label>
                                    <input type="number" id="taxRate" class="form-control" value="16" min="0" max="100" step="0.01">
                                    <div class="form-hint">Según documento: 16% de tasa de impuesto</div>
                                </div>
                            </div>
                            
                            <div class="action-buttons" style="display: flex; gap: 15px; margin-top: 25px;">
                                <button class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Guardar Cambios
                                </button>
                                <button class="btn btn-outline">
                                    <i class="fas fa-redo"></i>
                                    Restablecer
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Configuración de Seguridad -->
                    <div class="tab-content" id="tab-security">
                        <div class="config-section">
                            <div class="section-header">
                                <h2><i class="fas fa-shield-alt"></i> Configuración de Seguridad</h2>
                            </div>
                            
                            <div class="form-grid">
                                <!-- Seguridad según documento del proyecto -->
                                <div class="form-group">
                                    <label style="display: flex; align-items: center; justify-content: space-between;">
                                        <span><i class="fas fa-user-plus"></i> Permitir Registro de Nuevos Usuarios</span>
                                        <label class="switch">
                                            <input type="checkbox" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </label>
                                    <div class="form-hint">Permitir que nuevos usuarios se registren en el sistema</div>
                                </div>
                                
                                <div class="form-group">
                                    <label style="display: flex; align-items: center; justify-content: space-between;">
                                        <span><i class="fas fa-key"></i> Encriptación bcrypt para contraseñas</span>
                                        <label class="switch">
                                            <input type="checkbox" checked disabled>
                                            <span class="slider"></span>
                                        </label>
                                    </label>
                                    <div class="form-hint">Algoritmo bcrypt activado (requerido por documento)</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="maxLoginAttempts">
                                        <i class="fas fa-ban"></i> Intentos Máximos de Login
                                    </label>
                                    <input type="number" id="maxLoginAttempts" class="form-control" value="3" min="1" max="10">
                                    <div class="form-hint">Según documento: Bloqueo tras 3 intentos fallidos</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="sessionTimeout">
                                        <i class="fas fa-hourglass-half"></i> Tiempo de Expiración de Sesión (minutos)
                                    </label>
                                    <input type="number" id="sessionTimeout" class="form-control" value="30" min="5" max="480">
                                    <div class="form-hint">Tiempo de inactividad antes de cerrar sesión automáticamente</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="passwordMinLength">
                                        <i class="fas fa-user-lock"></i> Longitud Mínima de Contraseña
                                    </label>
                                    <input type="number" id="passwordMinLength" class="form-control" value="8" min="6" max="32">
                                    <div class="form-hint">Número mínimo de caracteres para las contraseñas</div>
                                </div>
                                
                                <div class="form-group">
                                    <label style="display: flex; align-items: center; justify-content: space-between;">
                                        <span><i class="fas fa-robot"></i> Protección Captcha en formularios</span>
                                        <label class="switch">
                                            <input type="checkbox" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </label>
                                    <div class="form-hint">Protección contra bots en formularios públicos (documento)</div>
                                </div>
                            </div>
                            
                            <div class="config-section">
                                <div class="section-header">
                                    <h2><i class="fas fa-lock"></i> Políticas de Contraseñas</h2>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label style="display: flex; align-items: center; justify-content: space-between;">
                                            <span><i class="fas fa-sync-alt"></i> Forzar Cambio de Contraseña Periódico</span>
                                            <label class="switch">
                                                <input type="checkbox" checked>
                                                <span class="slider"></span>
                                            </label>
                                        </label>
                                        <div class="form-hint">Obligar a cambiar contraseña cada 90 días</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label style="display: flex; align-items: center; justify-content: space-between;">
                                            <span><i class="fas fa-history"></i> Historial de Contraseñas</span>
                                            <label class="switch">
                                                <input type="checkbox" checked>
                                                <span class="slider"></span>
                                            </label>
                                        </label>
                                        <div class="form-hint">Evitar reutilización de contraseñas anteriores</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label style="display: flex; align-items: center; justify-content: space-between;">
                                            <span><i class="fas fa-user-shield"></i> Autenticación de Dos Factores</span>
                                            <label class="switch">
                                                <input type="checkbox">
                                                <span class="slider"></span>
                                            </label>
                                        </label>
                                        <div class="form-hint">Requerir verificación adicional para acceder</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="action-buttons" style="display: flex; gap: 15px; margin-top: 25px;">
                                <button class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Guardar Configuración de Seguridad
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Usuarios y Roles -->
                    <div class="tab-content" id="tab-users">
                        <div class="config-section">
                            <div class="section-header">
                                <h2><i class="fas fa-user-shield"></i> Gestión de Roles y Permisos</h2>
                                <button class="btn btn-primary" id="newRoleBtn">
                                    <i class="fas fa-plus"></i>
                                    Nuevo Rol
                                </button>
                            </div>
                            
                            <div class="permissions-table">
                                <!-- Tabla de permisos según niveles de usuario del documento -->
                                <div class="table-header">
                                    <div>Módulo / Permiso</div>
                                    <div>Administrador</div>
                                    <div>Gerente</div>
                                    <div>Vendedor</div>
                                    <div>Operador</div>
                                    <div>Cliente</div>
                                    <div>Acciones</div>
                                </div>
                                
                                <!-- Fila: Gestión de Inventario -->
                                <div class="table-row">
                                    <div>
                                        <strong>Gestión de Inventario</strong>
                                        <div class="form-hint">Control de stock y alertas</div>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked disabled>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div>
                                        <button class="btn btn-outline btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Fila: Ventas -->
                                <div class="table-row">
                                    <div>
                                        <strong>Ventas</strong>
                                        <div class="form-hint">Procesamiento de transacciones</div>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked disabled>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div>
                                        <button class="btn btn-outline btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Fila: Compras -->
                                <div class="table-row">
                                    <div>
                                        <strong>Compras</strong>
                                        <div class="form-hint">Órdenes de compra a proveedores</div>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked disabled>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div>
                                        <button class="btn btn-outline btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Fila: Pedidos Digitales -->
                                <div class="table-row">
                                    <div>
                                        <strong>Pedidos Digitales</strong>
                                        <div class="form-hint">Catálogo y carrito de compras</div>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked disabled>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div>
                                        <button class="btn btn-outline btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Fila: Reservas y Apartados -->
                                <div class="table-row">
                                    <div>
                                        <strong>Reservas y Apartados</strong>
                                        <div class="form-hint">Sistema de reservas con plazos</div>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked disabled>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div>
                                        <button class="btn btn-outline btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Fila: Promociones -->
                                <div class="table-row">
                                    <div>
                                        <strong>Promociones</strong>
                                        <div class="form-hint">Configuración de ofertas especiales</div>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked disabled>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox">
                                    </div>
                                    <div>
                                        <button class="btn btn-outline btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Fila: Devoluciones -->
                                <div class="table-row">
                                    <div>
                                        <strong>Devoluciones</strong>
                                        <div class="form-hint">Registro y seguimiento de devoluciones</div>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked disabled>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div class="permission-cell">
                                        <input type="checkbox" class="permission-checkbox" checked>
                                    </div>
                                    <div>
                                        <button class="btn btn-outline btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="config-section">
                            <div class="section-header">
                                <h2><i class="fas fa-users"></i> Gestión de Usuarios</h2>
                                <button class="btn btn-primary" id="newUserBtn">
                                    <i class="fas fa-user-plus"></i>
                                    Nuevo Usuario
                                </button>
                            </div>
                            
                            <div class="action-buttons" style="display: flex; gap: 15px; margin-bottom: 20px;">
                                <?php
                                    // Preparar filtros desde GET (mantener valores en el formulario)
                                    $q = trim($_GET['q'] ?? '');
                                    $filterRole = $_GET['role'] ?? '';
                                    $filterStatus = $_GET['status'] ?? '';

                                    // Conectar para obtener lista de roles (si no hay conexión, se omite)
                                    require_once __DIR__ . '/../../models/database.php';
                                    $roles = [];
                                    try {
                                        $dbTmp = new Database();
                                        $connTmp = $dbTmp->getConnection();
                                        if ($connTmp) {
                                            $stmtR = $connTmp->query("SELECT id, nombre FROM roles ORDER BY nombre");
                                            $roles = $stmtR->fetchAll(PDO::FETCH_ASSOC);
                                        }
                                    } catch (Exception $e) {
                                        $roles = [];
                                    }
                                ?>

                                <form method="get" id="usersFilterForm" style="flex:1; display:flex; gap:10px; align-items:center;">
                                    <input type="hidden" name="tab" value="users" />
                                    <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Buscar usuarios..." class="form-control" style="flex:1;" />
                                    <select name="role" class="form-control" style="width:220px;">
                                        <option value="">Todos los roles</option>
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?php echo htmlspecialchars($r['id']); ?>" <?php echo ((string)$filterRole === (string)$r['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="status" class="form-control" style="width:160px;">
                                        <option value="">Todos</option>
                                        <option value="activo" <?php if ($filterStatus === 'activo') echo 'selected'; ?>>Activos</option>
                                        <option value="inactivo" <?php if ($filterStatus === 'inactivo') echo 'selected'; ?>>Inactivos</option>
                                    </select>
                                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Buscar</button>
                                    <a class="btn btn-outline" href="<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>">Limpiar</a>
                                </form>
                            </div>
                            
                            <?php
                                // Si no se definieron arriba (por ejemplo al incluir directamente este archivo), crear la conexión y obtener filtros
                                if (!isset($users) || !isset($usersError)) {
                                    require_once __DIR__ . '/../../models/database.php';
                                    require_once __DIR__ . '/../../models/Usuario.php';

                                    $users = [];
                                    $usersError = null;

                                    $db = new Database();
                                    $conn = $db->getConnection();
                                    if ($conn) {
                                        $usuarioModel = new Usuario($conn);
                                        $res = $usuarioModel->obtenerFiltrados($q ?? '', $filterRole ?? '', $filterStatus ?? '');
                                        if ($res !== false) {
                                            $users = $res;
                                        } else {
                                            $usersError = 'No se pudieron obtener los usuarios.';
                                            if (defined('APP_DEBUG') && APP_DEBUG) {
                                                try {
                                                    $stmt = $conn->query("SELECT count(*) as c FROM usuarios");
                                                    $cnt = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    $usersError .= ' Registros en tabla: ' . intval($cnt['c']);
                                                } catch (Exception $e) {
                                                    $usersError .= ' Detalle: ' . $e->getMessage();
                                                }
                                            }
                                        }
                                    } else {
                                        $usersError = 'Error de conexión a la base de datos.';
                                        if (defined('APP_DEBUG') && APP_DEBUG) {
                                            $usersError .= ' ' . $db->getLastError();
                                        }
                                    }
                                }
                            ?>

                            <?php if ($usersError): ?>
                                <div style="margin-top:10px;padding:12px;background:#ffecec;border:1px solid #f5c2c2;color:#8a1f1f;border-radius:6px;">
                                    <?php echo htmlspecialchars($usersError); ?>
                                </div>
                            <?php else: ?>
                                <div id="usersSection" style="overflow-x:auto; background:#fff; padding:16px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                                    <table class="users-table" style="width:100%; border-collapse:collapse;">
                                        <thead>
                                            <tr>
                                                <th style="padding:10px;border-bottom:1px solid #e9ecef;text-align:left;">ID</th>
                                                <th style="padding:10px;border-bottom:1px solid #e9ecef;text-align:left;">Usuario</th>
                                                <th style="padding:10px;border-bottom:1px solid #e9ecef;text-align:left;">Email</th>
                                                <th style="padding:10px;border-bottom:1px solid #e9ecef;text-align:left;">Nombre</th>
                                                <th style="padding:10px;border-bottom:1px solid #e9ecef;text-align:left;">Rol</th>
                                                <th style="padding:10px;border-bottom:1px solid #e9ecef;text-align:left;">Estado</th>
                                                <th style="padding:10px;border-bottom:1px solid #e9ecef;text-align:left;">Últ. acceso</th>
                                                <th style="padding:10px;border-bottom:1px solid #e9ecef;text-align:left;">Creado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($users)): ?>
                                                <tr><td colspan="8" style="padding:14px;text-align:center;color:#6c757d;">No hay usuarios registrados.</td></tr>
                                            <?php else: ?>
                                                <?php foreach ($users as $u): ?>
                                                    <tr>
                                                        <td style="padding:10px;border-top:1px solid #f1f3f5;"><?php echo htmlspecialchars($u['id']); ?></td>
                                                        <td style="padding:10px;border-top:1px solid #f1f3f5;"><?php echo htmlspecialchars($u['username']); ?></td>
                                                        <td style="padding:10px;border-top:1px solid #f1f3f5;"><?php echo htmlspecialchars($u['email']); ?></td>
                                                        <td style="padding:10px;border-top:1px solid #f1f3f5;"><?php echo htmlspecialchars($u['nombre_completo']); ?></td>
                                                        <td style="padding:10px;border-top:1px solid #f1f3f5;"><?php echo htmlspecialchars($u['rol_id']); ?></td>
                                                        <td style="padding:10px;border-top:1px solid #f1f3f5;"><?php echo $u['estado'] ? 'Activo' : 'Inactivo'; ?></td>
                                                        <td style="padding:10px;border-top:1px solid #f1f3f5;"><?php echo htmlspecialchars($u['ultimo_acceso']); ?></td>
                                                        <td style="padding:10px;border-top:1px solid #f1f3f5;"><?php echo htmlspecialchars($u['created_at']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tab: Configuración de Empresa -->
                    <div class="tab-content" id="tab-business">
                        <div class="config-section">
                            <div class="section-header">
                                <h2><i class="fas fa-building"></i> Información de la Empresa</h2>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="companyName">
                                        <i class="fas fa-signature"></i> Nombre de la Empresa
                                    </label>
                                    <input type="text" id="companyName" class="form-control" value="Inversiones Rojas 2016 C.A">
                                    <div class="form-hint">Nombre legal de la empresa</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="companyRIF">
                                        <i class="fas fa-id-card"></i> RIF de la Empresa
                                    </label>
                                    <input type="text" id="companyRIF" class="form-control" value="J-123456789" pattern="[JGV]-[0-9]{9}">
                                    <div class="form-hint">Formato: J-123456789</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="companyAddress">
                                        <i class="fas fa-map-marker-alt"></i> Dirección
                                    </label>
                                    <input type="text" id="companyAddress" class="form-control" value="AV ARAGUA LOCAL NRO 286 SECTOR ANDRES ELOY BLANCO, MARACAY ARAGUA">
                                    <div class="form-hint">Dirección completa según documento</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="companyPhone">
                                        <i class="fas fa-phone"></i> Teléfono
                                    </label>
                                    <input type="tel" id="companyPhone" class="form-control" value="0243-2343044" pattern="[0-9]{4}-[0-9]{7}">
                                    <div class="form-hint">Formato: 0243-2343044</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="companyEmail">
                                        <i class="fas fa-envelope"></i> Email Corporativo
                                    </label>
                                    <input type="email" id="companyEmail" class="form-control" value="2016rojasinversiones@gmail.com">
                                    <div class="form-hint">Email principal según documento</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="companyWebsite">
                                        <i class="fas fa-globe"></i> Sitio Web
                                    </label>
                                    <input type="url" id="companyWebsite" class="form-control" value="https://inversionesrojas.com">
                                </div>
                            </div>
                            
                            <div class="config-section">
                                <div class="section-header">
                                    <h2><i class="fas fa-percentage"></i> Configuración Fiscal y Comercial</h2>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="ivaRate">
                                            <i class="fas fa-receipt"></i> Tasa de IVA (%)
                                        </label>
                                        <input type="number" id="ivaRate" class="form-control" value="16" min="0" max="100" step="0.01">
                                        <div class="form-hint">Tasa de impuesto al valor agregado</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="invoicePrefix">
                                            <i class="fas fa-file-invoice"></i> Prefijo de Facturas
                                        </label>
                                        <input type="text" id="invoicePrefix" class="form-control" value="FAC-">
                                        <div class="form-hint">Prefijo para números de factura (Ej: FAC-001)</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="purchasePrefix">
                                            <i class="fas fa-shopping-cart"></i> Prefijo de Órdenes de Compra
                                        </label>
                                        <input type="text" id="purchasePrefix" class="form-control" value="OC-">
                                        <div class="form-hint">Prefijo para órdenes de compra</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="defaultPaymentMethod">
                                            <i class="fas fa-credit-card"></i> Método de Pago Predeterminado
                                        </label>
                                        <select id="defaultPaymentMethod" class="form-control">
                                            <option value="efectivo">Efectivo</option>
                                            <option value="transferencia" selected>Transferencia</option>
                                            <option value="pago_movil">Pago Móvil</option>
                                            <option value="tarjeta">Tarjeta</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="action-buttons" style="display: flex; gap: 15px; margin-top: 25px;">
                                <button class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Guardar Información de Empresa
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Backup y Restauración -->
                    <div class="tab-content" id="tab-backup">
                        <div class="config-section">
                            <div class="section-header">
                                <h2><i class="fas fa-database"></i> Backup y Restauración de Datos</h2>
                            </div>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="backupFrequency">
                                        <i class="fas fa-history"></i> Frecuencia de Backup Automático
                                    </label>
                                    <select id="backupFrequency" class="form-control">
                                        <option value="daily">Diario</option>
                                        <option value="weekly" selected>Semanal</option>
                                        <option value="monthly">Mensual</option>
                                        <option value="disabled">Desactivado</option>
                                    </select>
                                    <div class="form-hint">Según documento: Respaldo y restauración de datos</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="backupLocation">
                                        <i class="fas fa-folder"></i> Ubicación de Backup
                                    </label>
                                    <input type="text" id="backupLocation" class="form-control" value="/backups/inversiones-rojas/">
                                    <div class="form-hint">Ruta donde se guardarán las copias de seguridad</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="retentionDays">
                                        <i class="fas fa-calendar-times"></i> Días de Retención
                                    </label>
                                    <input type="number" id="retentionDays" class="form-control" value="30" min="1" max="365">
                                    <div class="form-hint">Número de días para mantener los backups</div>
                                </div>
                            </div>
                            
                            <div class="config-section">
                                <div class="section-header">
                                    <h2><i class="fas fa-tasks"></i> Acciones de Backup</h2>
                                </div>
                                
                                <div class="action-buttons" style="display: flex; gap: 15px; flex-wrap: wrap;">
                                    <button class="btn btn-primary">
                                        <i class="fas fa-download"></i>
                                        Crear Backup Ahora
                                    </button>
                                    <button class="btn btn-secondary">
                                        <i class="fas fa-upload"></i>
                                        Restaurar desde Backup
                                    </button>
                                    <button class="btn btn-outline">
                                        <i class="fas fa-list"></i>
                                        Ver Historial de Backups
                                    </button>
                                    <button class="btn btn-outline">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        Subir Backup Manual
                                    </button>
                                </div>
                            </div>
                            
                            <div class="config-section">
                                <div class="section-header">
                                    <h2><i class="fas fa-history"></i> Últimos Backups</h2>
                                </div>
                                
                                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <div>
                                            <strong>backup_2024_01_15.sql</strong>
                                            <div class="form-hint">15/01/2024 23:30 - 1.2 GB</div>
                                        </div>
                                        <button class="btn btn-outline btn-sm">
                                            <i class="fas fa-download"></i>
                                            Descargar
                                        </button>
                                    </div>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <div>
                                            <strong>backup_2024_01_08.sql</strong>
                                            <div class="form-hint">08/01/2024 23:30 - 1.1 GB</div>
                                        </div>
                                        <button class="btn btn-outline btn-sm">
                                            <i class="fas fa-download"></i>
                                            Descargar
                                        </button>
                                    </div>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong>backup_2024_01_01.sql</strong>
                                            <div class="form-hint">01/01/2024 23:30 - 1.0 GB</div>
                                        </div>
                                        <button class="btn btn-outline btn-sm">
                                            <i class="fas fa-download"></i>
                                            Descargar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Nuevo Rol -->
    <div class="modal-overlay" id="newRoleModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-shield"></i> Crear Nuevo Rol</h3>
                <button class="modal-close" id="closeRoleModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="newRoleForm">
                    <div class="form-group">
                        <label for="roleName">Nombre del Rol</label>
                        <input type="text" id="roleName" class="form-control" placeholder="Ej: Supervisor de Ventas" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="roleDescription">Descripción</label>
                        <textarea id="roleDescription" class="form-control" rows="3" placeholder="Descripción del rol y sus responsabilidades..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="roleLevel">Nivel de Acceso</label>
                        <select id="roleLevel" class="form-control" required>
                            <option value="">Seleccionar nivel...</option>
                            <option value="admin">Administrador</option>
                            <option value="manager">Gerente</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="operator">Operador</option>
                            <option value="viewer">Solo Lectura</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelRoleModal">Cancelar</button>
                <button class="btn btn-primary" id="saveRole">Guardar Rol</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Control de tabs
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            // Activar pestaña desde query param `tab` si está presente (evita volver a "General" tras buscar)
                try {
                const params = new URLSearchParams(window.location.search);
                const initialTab = params.get('tab');
                if (initialTab) {
                    // Quitar active por si acaso
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    const targetBtn = document.querySelector(`.tab-btn[data-tab="${initialTab}"]`);
                    const targetContent = document.getElementById(`tab-${initialTab}`);
                    if (targetBtn && targetContent) {
                        targetBtn.classList.add('active');
                        targetContent.classList.add('active');
                    }
                    // Si la pestaña es 'users', desplazar a la tabla y enfocar el buscador
                    if (initialTab === 'users') {
                        setTimeout(() => {
                            const usersEl = document.getElementById('usersSection');
                            if (usersEl) {
                                usersEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                            const qInput = document.querySelector('input[name="q"]');
                            if (qInput) qInput.focus();
                        }, 120);
                    }
                }
            } catch (e) {
                // no bloquear si URLSearchParams falla en algún entorno antiguo
            }
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remover active de todos
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Agregar active al seleccionado
                    this.classList.add('active');
                    document.getElementById(`tab-${tabId}`).classList.add('active');
                });
            });
            
            // Control del modal de nuevo rol
            const newRoleBtn = document.getElementById('newRoleBtn');
            const newRoleModal = document.getElementById('newRoleModal');
            const closeRoleModal = document.getElementById('closeRoleModal');
            const cancelRoleModal = document.getElementById('cancelRoleModal');
            const saveRole = document.getElementById('saveRole');
            const newRoleForm = document.getElementById('newRoleForm');
            
            if (newRoleBtn && newRoleModal) {
                newRoleBtn.addEventListener('click', () => {
                    newRoleModal.classList.add('active');
                });
                
                closeRoleModal.addEventListener('click', () => {
                    newRoleModal.classList.remove('active');
                    if (newRoleForm) newRoleForm.reset();
                });
                
                cancelRoleModal.addEventListener('click', () => {
                    newRoleModal.classList.remove('active');
                    if (newRoleForm) newRoleForm.reset();
                });
                
                // Cerrar modal al hacer clic fuera
                newRoleModal.addEventListener('click', (e) => {
                    if (e.target === newRoleModal) {
                        newRoleModal.classList.remove('active');
                        if (newRoleForm) newRoleForm.reset();
                    }
                });
                
                // Guardar rol
                if (saveRole && newRoleForm) {
                    saveRole.addEventListener('click', () => {
                        const roleName = document.getElementById('roleName').value;
                        
                        if (!roleName) {
                            alert('Por favor complete el nombre del rol');
                            return;
                        }
                        
                        // Simular guardado
                        alert(`Rol "${roleName}" creado exitosamente`);
                        newRoleForm.reset();
                        newRoleModal.classList.remove('active');
                    });
                }
            }

            
            
            // Control del modal de nuevo usuario
            const newUserBtn = document.getElementById('newUserBtn');
            
            if (newUserBtn) {
                newUserBtn.addEventListener('click', () => {
                    alert('Funcionalidad de nuevo usuario - Se abriría modal de creación');
                    // Aquí se abriría un modal similar para crear usuario
                });
            }
            
            // Guardar configuraciones
            const saveButtons = document.querySelectorAll('.btn-primary');
            saveButtons.forEach(btn => {
                if (!btn.id && btn.textContent.includes('Guardar')) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        // Simular guardado
                        const btnText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                        this.disabled = true;
                        
                        setTimeout(() => {
                            this.innerHTML = btnText;
                            this.disabled = false;
                            alert('Configuración guardada exitosamente');
                        }, 1000);
                    });
                }
            });
            
            // Acciones de backup
            const backupButtons = document.querySelectorAll('.btn');
            backupButtons.forEach(btn => {
                if (btn.textContent.includes('Backup')) {
                    btn.addEventListener('click', function() {
                        if (this.textContent.includes('Crear Backup')) {
                            alert('Iniciando creación de backup...');
                            // Simular proceso de backup
                            setTimeout(() => {
                                alert('Backup creado exitosamente');
                            }, 1500);
                        }
                    });
                }
            });
            
            // Validación de formato RIF
            const companyRIF = document.getElementById('companyRIF');
            if (companyRIF) {
                companyRIF.addEventListener('blur', function() {
                    const rifPattern = /^[JGV]-[0-9]{9}$/;
                    if (!rifPattern.test(this.value) && this.value !== '') {
                        alert('Formato de RIF inválido. Use J-123456789');
                        this.focus();
                    }
                });
            }
            
              // Toggle sidebar
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-sidebar').classList.toggle('collapsed');
        });

            // Validación de teléfono
            const companyPhone = document.getElementById('companyPhone');
            if (companyPhone) {
                companyPhone.addEventListener('blur', function() {
                    const phonePattern = /^[0-9]{4}-[0-9]{7}$/;
                    if (!phonePattern.test(this.value) && this.value !== '') {
                        alert('Formato de teléfono inválido. Use 0243-2343044');
                        this.focus();
                    }
                });
            }
        });
    </script>
    <script>
    // Notificar al padre (Dashboard) el título y breadcrumb de este módulo
    (function(){
        try {
            const payload = { irModuleHeader: true, title: 'Configuración', breadcrumb: ['Inicio','Configuración'] };
            // Enviar después de pequeño delay para asegurar que el iframe está listo
            setTimeout(function(){
                if (window.parent && window.parent !== window) {
                    window.parent.postMessage(payload, window.location.origin);
                }
            }, 50);
        } catch(e) {
            // no bloquear
        }
    })();
    </script>
</body>
</html>