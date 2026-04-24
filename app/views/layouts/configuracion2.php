<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
$base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - Inversiones Rojas</title>
    <script>var APP_BASE = '<?php echo $base_url; ?>';</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/layouts/configuracion.css">
    <style>
        /* Estilos del módulo de configuración - SOLO LOS MÍNIMOS NECESARIOS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
            padding: 20px;
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
            flex-wrap: wrap;
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
            white-space: nowrap;
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

        /* Permissions table */
        .permissions-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .table-header {
            display: grid;
            grid-template-columns: 250px repeat(5, 1fr);
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .table-row {
            display: grid;
            grid-template-columns: 250px repeat(5, 1fr);
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            align-items: center;
            transition: background 0.3s;
        }

        .table-row:hover {
            background: #f8f9fa;
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

        /* Modal styles */
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
            z-index: 10000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 12px;
            width: 500px;
            max-width: 95%;
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

        /* Tabla de usuarios */
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .users-table th {
            padding: 12px 15px;
            background: #f8f9fa;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
        }

        .users-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .users-table tr:hover {
            background: #f8f9fa;
        }

        .btn-icon {
            background: transparent;
            border: none;
            padding: 8px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 4px;
        }

        .btn-icon i { font-size: 18px; }
        .btn-icon.edit { color: #107a50; }
        .btn-icon.toggle { color: #495057; }
        .btn-icon.delete { color: #b00020; }

        .status-active {
            color: #1F9166;
            font-weight: 600;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        @media (max-width: 1200px) {
            .table-header,
            .table-row {
                grid-template-columns: 200px repeat(3, 1fr);
            }
            
            .table-header div:nth-child(n+5),
            .table-row div:nth-child(n+5) {
                display: none;
            }
        }

        @media (max-width: 768px) {
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
                grid-template-columns: 150px repeat(2, 1fr);
                font-size: 0.85rem;
            }
            
            .modal {
                width: 95%;
            }
            
            .users-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Tabs de Configuración -->
    <div class="config-tabs">
        <div class="tabs-header">
            <button class="tab-btn active" data-tab="general">
                <i class="fas fa-cogs"></i>
                <span>Configuración General</span>
            </button>
            <button class="tab-btn" data-tab="users">
                <i class="fas fa-users-cog"></i>
                <span>Usuarios y Roles</span>
            </button>
            <button class="tab-btn" data-tab="business">
                <i class="fas fa-building"></i>
                <span>Empresa</span>
            </button>
            <button class="tab-btn" data-tab="backup">
                <i class="fas fa-database"></i>
                <span>Respaldo</span>
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
                
                <div class="action-buttons">
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

        <!-- Tab: Usuarios y Roles -->
        <div class="tab-content" id="tab-users">
            <!-- Sección de Roles y Permisos -->
            <div class="config-section">
                <div class="section-header">
                    <h2><i class="fas fa-user-shield"></i> Gestión de Roles y Permisos</h2>
                </div>
                
                <!-- Tabla de permisos -->
                <div class="permissions-table">
                    <div class="table-header">
                        <div>Módulo / Permiso</div>
                        <div>Administrador</div>
                        <div>Gerente</div>
                        <div>Vendedor</div>
                        <div>Operador</div>
                        <div>Cliente</div>
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
                    </div>
                </div>
            </div>

            <!-- Sección de Gestión de Usuarios -->
            <div class="config-section">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> Gestión de Usuarios</h2>
                    <button class="btn btn-primary" id="newUserBtn">
                        <i class="fas fa-user-plus"></i>
                        Nuevo Usuario
                    </button>
                </div>
                
                <!-- Aquí va la tabla de usuarios -->
                <?php
                require_once __DIR__ . '/../../models/database.php';
                require_once __DIR__ . '/../../models/Usuario.php';
                
                $db = new Database();
                $conn = $db->getConnection();
                $users = [];
                
                if ($conn) {
                    $usuarioModel = new Usuario($conn);
                    $users = $usuarioModel->obtenerFiltrados('', '', '') ?: [];
                }
                ?>
                
                <div style="overflow-x: auto;">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px;">
                                        No hay usuarios registrados
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['nombre_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($user['rol_id']); ?></td>
                                    <td>
                                        <span class="<?php echo $user['estado'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $user['estado'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-icon edit" title="Editar" data-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon toggle" title="Cambiar estado" data-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                        <button class="btn-icon delete" title="Eliminar" data-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
                
                <div class="action-buttons">
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
                    
                    <div class="action-buttons">
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

    <!-- Modal de Nuevo Usuario -->
    <div class="modal-overlay" id="userModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</h3>
                <button class="modal-close" id="closeUserModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <div class="form-group">
                        <label for="modalUsername">Nombre de Usuario *</label>
                        <input type="text" id="modalUsername" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalEmail">Correo Electrónico *</label>
                        <input type="email" id="modalEmail" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalFullname">Nombre Completo *</label>
                        <input type="text" id="modalFullname" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalRole">Rol *</label>
                        <select id="modalRole" class="form-control" required>
                            <option value="">Seleccionar rol...</option>
                            <option value="1">Administrador</option>
                            <option value="2">Gerente</option>
                            <option value="3">Vendedor</option>
                            <option value="4">Operador</option>
                            <option value="5">Cliente</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalPassword">Contraseña *</label>
                        <input type="password" id="modalPassword" class="form-control" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="modalConfirmPassword">Confirmar Contraseña *</label>
                        <input type="password" id="modalConfirmPassword" class="form-control" required minlength="8">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelUserModal">Cancelar</button>
                <button class="btn btn-primary" id="saveUser">Crear Usuario</button>
            </div>
        </div>
    </div>

    <script>
        // Notificar al dashboard que estamos en módulo de configuración
        (function() {
            try {
                const payload = {
                    irModuleHeader: true,
                    title: 'Configuración',
                    breadcrumb: ['Inicio', 'Configuración']
                };
                window.parent.postMessage(payload, '*');
            } catch (e) {
                console.log('No se pudo comunicar con el padre');
            }
        })();

        document.addEventListener('DOMContentLoaded', function() {
            // 1. Control de tabs
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remover active de todos
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Agregar active al seleccionado
                    this.classList.add('active');
                    const targetTab = document.getElementById(`tab-${tabId}`);
                    if (targetTab) targetTab.classList.add('active');
                });
            });
            
            // 2. Botón "Nuevo Usuario" - VERSIÓN FUNCIONAL
            const newUserBtn = document.getElementById('newUserBtn');
            const userModal = document.getElementById('userModal');
            
            if (newUserBtn && userModal) {
                newUserBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Limpiar formulario
                    const form = document.getElementById('userForm');
                    if (form) form.reset();
                    
                    // Mostrar modal
                    userModal.classList.add('active');
                    
                    // Enfocar primer campo
                    setTimeout(() => {
                        const firstInput = document.getElementById('modalUsername');
                        if (firstInput) firstInput.focus();
                    }, 100);
                });
                
                // Cerrar modal
                const closeModal = () => {
                    userModal.classList.remove('active');
                };
                
                document.getElementById('closeUserModal').addEventListener('click', closeModal);
                document.getElementById('cancelUserModal').addEventListener('click', closeModal);
                
                // Cerrar al hacer clic fuera
                userModal.addEventListener('click', function(e) {
                    if (e.target === userModal) {
                        closeModal();
                    }
                });
                
                // Guardar usuario
                document.getElementById('saveUser').addEventListener('click', function() {
                    const username = document.getElementById('modalUsername').value;
                    const email = document.getElementById('modalEmail').value;
                    const fullname = document.getElementById('modalFullname').value;
                    const password = document.getElementById('modalPassword').value;
                    const confirmPassword = document.getElementById('modalConfirmPassword').value;
                    const role = document.getElementById('modalRole').value;
                    
                    // Validaciones básicas
                    if (!username || !email || !fullname || !password || !confirmPassword || !role) {
                        alert('Por favor complete todos los campos obligatorios (*)');
                        return;
                    }
                    
                    if (password !== confirmPassword) {
                        alert('Las contraseñas no coinciden');
                        return;
                    }
                    
                    if (password.length < 8) {
                        alert('La contraseña debe tener al menos 8 caracteres');
                        return;
                    }
                    
                    // Mostrar loading
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
                    this.disabled = true;
                    
                    // Simular petición AJAX
                    setTimeout(() => {
                        console.log('Creando usuario:', { username, email, fullname, role, password });
                        
                        // Simular éxito
                        alert(`Usuario "${username}" creado exitosamente`);
                        this.innerHTML = originalText;
                        this.disabled = false;
                        closeModal();
                        
                        // Recargar la página para ver cambios
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }, 1500);
                });
            }
            
            // 3. Botones de acción en la tabla
            document.addEventListener('click', function(e) {
                // Editar usuario
                if (e.target.closest('.btn-icon.edit')) {
                    const btn = e.target.closest('.btn-icon.edit');
                    const userId = btn.getAttribute('data-id');
                    alert(`Editar usuario ID: ${userId}`);
                }
                
                // Cambiar estado
                if (e.target.closest('.btn-icon.toggle')) {
                    const btn = e.target.closest('.btn-icon.toggle');
                    const userId = btn.getAttribute('data-id');
                    
                    if (confirm('¿Desea cambiar el estado de este usuario?')) {
                        console.log(`Cambiando estado usuario ID: ${userId}`);
                        alert('Estado cambiado exitosamente');
                        window.location.reload();
                    }
                }
                
                // Eliminar usuario
                if (e.target.closest('.btn-icon.delete')) {
                    const btn = e.target.closest('.btn-icon.delete');
                    const userId = btn.getAttribute('data-id');
                    
                    if (confirm('¿Está seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
                        console.log(`Eliminando usuario ID: ${userId}`);
                        alert('Usuario eliminado exitosamente');
                        window.location.reload();
                    }
                }
            });
            
            // 4. Guardar configuraciones generales
            document.querySelectorAll('.btn-primary').forEach(btn => {
                if (btn.textContent.includes('Guardar') && !btn.id) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                        this.disabled = true;
                        
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.disabled = false;
                            alert('Configuración guardada exitosamente');
                        }, 1000);
                    });
                }
            });
            
            // 5. Activar tab según URL
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            if (activeTab && activeTab !== 'general') {
                const tabBtn = document.querySelector(`.tab-btn[data-tab="${activeTab}"]`);
                const tabContent = document.getElementById(`tab-${activeTab}`);
                
                if (tabBtn && tabContent) {
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    tabBtn.classList.add('active');
                    tabContent.classList.add('active');
                }
            }
        });
    </script>
</body>
</html>