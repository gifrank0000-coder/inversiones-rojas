<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========================================== */
        /* ESTILOS BASE - Consistentes con el proyecto */
        /* ========================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: #333;
            min-height: 100vh;
        }

        /* ========================================== */
        /* CONTENEDOR PRINCIPAL */
        /* ========================================== */
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        /* ========================================== */
        /* HEADER DEL PERFIL */
        /* ========================================== */
        .profile-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 5px solid #1F9166;
        }

        .profile-header-content {
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        /* ========================================== */
        /* AVATAR DEL PERFIL */
        /* ========================================== */
        .profile-avatar-section {
            position: relative;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1F9166, #30B583);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3.5rem;
            font-weight: 700;
            border: 5px solid white;
            box-shadow: 0 8px 25px rgba(31, 145, 102, 0.3);
        }

        .avatar-edit-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid #1F9166;
            color: #1F9166;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .avatar-edit-btn:hover {
            background: #1F9166;
            color: white;
            transform: scale(1.1);
        }

        /* ========================================== */
        /* INFORMACIÓN DEL PERFIL */
        /* ========================================== */
        .profile-info {
            flex: 1;
            min-width: 300px;
        }

        .profile-name {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .profile-role {
            display: inline-block;
            background: #e8f5e8;
            color: #1F9166;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .profile-email {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-email i {
            color: #1F9166;
        }

        .profile-member-since {
            color: #95a5a6;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        /* ========================================== */
        /* ESTADÍSTICAS DEL PERFIL */
        /* ========================================== */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            border-top: 4px solid #1F9166;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1F9166, #30B583);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.5rem;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* ========================================== */
        /* TABS DE PERFIL */
        /* ========================================== */
        .profile-tabs {
            background: white;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .tabs-header {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 0;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 20px 30px;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: #6c757d;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 150px;
            justify-content: center;
        }

        .tab-btn:hover {
            color: #2c3e50;
            background: rgba(31, 145, 102, 0.05);
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
            padding: 40px;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ========================================== */
        /* FORMULARIOS */
        /* ========================================== */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
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
            padding: 14px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
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
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* ========================================== */
        /* BOTONES */
        /* ========================================== */
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
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

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }

        /* ========================================== */
        /* TARJETAS DE ACTIVIDAD */
        /* ========================================== */
        .activity-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            border-left: 4px solid #1F9166;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .activity-title {
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .activity-time {
            color: #95a5a6;
            font-size: 0.85rem;
        }

        .activity-content {
            color: #6c757d;
            line-height: 1.6;
        }

        /* ========================================== */
        /* MODAL DE CAMBIO DE AVATAR */
        /* ========================================== */
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
            border-radius: 15px;
            width: 500px;
            max-width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            padding: 25px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            border-radius: 15px 15px 0 0;
        }

        .modal-header h3 {
            color: #2c3e50;
            font-size: 1.3rem;
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
            padding: 30px;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            background: #f8f9fa;
            border-radius: 0 0 15px 15px;
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* ========================================== */
        /* AVATAR SELECTOR */
        /* ========================================== */
        .avatar-options {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .avatar-option {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1F9166, #30B583);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            border: 3px solid transparent;
        }

        .avatar-option:hover {
            transform: scale(1.1);
        }

        .avatar-option.selected {
            border-color: #1F9166;
            box-shadow: 0 0 0 3px rgba(31, 145, 102, 0.3);
        }

        /* ========================================== */
        /* RESPONSIVE DESIGN */
        /* ========================================== */
        @media (max-width: 768px) {
            .profile-container {
                padding: 20px;
            }
            
            .profile-header-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .profile-avatar {
                width: 120px;
                height: 120px;
                font-size: 2.5rem;
            }
            
            .tabs-header {
                flex-direction: column;
            }
            
            .tab-btn {
                padding: 15px;
                justify-content: flex-start;
                min-width: auto;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .profile-container {
                padding: 15px;
            }
            
            .profile-header,
            .tab-content {
                padding: 20px;
            }
            
            .avatar-options {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .avatar-option {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Header del Perfil -->
        <div class="profile-header">
            <div class="profile-header-content">
                <div class="profile-avatar-section">
                    <div class="profile-avatar" id="profileAvatar">
                        J
                    </div>
                    <button class="avatar-edit-btn" id="editAvatarBtn">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                
                <div class="profile-info">
                    <h1 class="profile-name">Juan Andrés Pérez</h1>
                    <span class="profile-role">Administrador</span>
                    <p class="profile-email">
                        <i class="fas fa-envelope"></i>
                        juan.perez@inversionesrojas.com
                    </p>
                    <p class="profile-member-since">
                        <i class="fas fa-calendar-alt"></i>
                        Miembro desde: 15 de Marzo, 2023
                    </p>
                </div>
            </div>
            
            <!-- Estadísticas del Usuario -->
        
        </div>

        <!-- Tabs del Perfil -->
        <div class="profile-tabs">
            <div class="tabs-header">
                <button class="tab-btn active" data-tab="info">
                    <i class="fas fa-user"></i>
                    Información Personal
                </button>
                <button class="tab-btn" data-tab="security">
                    <i class="fas fa-shield-alt"></i>
                    Seguridad
                </button>
           
              
                </button>
                <button class="tab-btn" data-tab="activity">
                    <i class="fas fa-history"></i>
                    Actividad
                </button>
            </div>

            <!-- Tab: Información Personal -->
            <div class="tab-content active" id="tab-info">
                <div class="config-section">
                    <div class="section-header">
                        <h2><i class="fas fa-id-card"></i> Información Básica</h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="firstName">
                                <i class="fas fa-user"></i> Nombre
                            </label>
                            <input type="text" id="firstName" class="form-control" value="Juan Andrés">
                            <div class="form-hint">Tu nombre legal</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="lastName">
                                <i class="fas fa-user"></i> Apellido
                            </label>
                            <input type="text" id="lastName" class="form-control" value="Pérez Rodríguez">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Correo Electrónico
                            </label>
                            <input type="email" id="email" class="form-control" value="juan.perez@inversionesrojas.com">
                            <div class="form-hint">Tu email corporativo</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Teléfono
                            </label>
                            <input type="tel" id="phone" class="form-control" value="+58 424 123 4567">
                            <div class="form-hint">Número de contacto personal</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="department">
                                <i class="fas fa-building"></i> Departamento
                            </label>
                            <select id="department" class="form-control">
                                <option value="sales" selected>Ventas</option>
                                <option value="inventory">Inventario</option>
                                <option value="finance">Finanzas</option>
                                <option value="management">Gerencia</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="position">
                                <i class="fas fa-briefcase"></i> Cargo
                            </label>
                            <input type="text" id="position" class="form-control" value="Jefe de Ventas">
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-primary" id="saveProfileBtn">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                    <button class="btn btn-outline">
                        <i class="fas fa-redo"></i>
                        Restablecer
                    </button>
                </div>
            </div>

            <!-- Tab: Seguridad -->
            <div class="tab-content" id="tab-security">
                <div class="config-section">
                    <div class="section-header">
                        <h2><i class="fas fa-key"></i> Cambiar Contraseña</h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="currentPassword">
                                <i class="fas fa-lock"></i> Contraseña Actual
                            </label>
                            <input type="password" id="currentPassword" class="form-control" placeholder="••••••••">
                            <div class="form-hint">Ingresa tu contraseña actual</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="newPassword">
                                <i class="fas fa-key"></i> Nueva Contraseña
                            </label>
                            <input type="password" id="newPassword" class="form-control" placeholder="••••••••">
                            <div class="form-hint">Mínimo 8 caracteres</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">
                                <i class="fas fa-check-circle"></i> Confirmar Contraseña
                            </label>
                            <input type="password" id="confirmPassword" class="form-control" placeholder="••••••••">
                        </div>
                    </div>
                </div>
                
                
                <div class="action-buttons">
                    <button class="btn btn-primary" id="updatePasswordBtn">
                        <i class="fas fa-key"></i>
                        Actualizar Contraseña
                    </button>
                    <button class="btn btn-danger" id="logoutAllBtn">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar Todas las Sesiones
                    </button>
                </div>
            </div>

            <!-- Tab: Actividad -->
            <div class="tab-content" id="tab-activity">
                <div class="config-section">
                    <div class="section-header">
                        <h2><i class="fas fa-history"></i> Historial Reciente</h2>
                        <button class="btn btn-outline btn-sm" id="exportActivityBtn">
                            <i class="fas fa-download"></i>
                            Exportar
                        </button>
                    </div>
                    
                    <div class="activity-card">
                        <div class="activity-header">
                            <div class="activity-title">
                                <i class="fas fa-shopping-cart"></i> Venta Realizada
                            </div>
                            <div class="activity-time">Hace 15 minutos</div>
                        </div>
                        <div class="activity-content">
                            <strong>Cliente:</strong> María González<br>
                            <strong>Total:</strong> $2,450.00<br>
                            <strong>Productos:</strong> 3 items<br>
                            <strong>Referencia:</strong> VENTA-2024-0158
                        </div>
                    </div>
                    
                    <div class="activity-card">
                        <div class="activity-header">
                            <div class="activity-title">
                                <i class="fas fa-user-plus"></i> Nuevo Cliente Registrado
                            </div>
                            <div class="activity-time">Hace 2 horas</div>
                        </div>
                        <div class="activity-content">
                            <strong>Cliente:</strong> Carlos Rojas<br>
                            <strong>Email:</strong> carlos.rojas@email.com<br>
                            <strong>Teléfono:</strong> +58 414 987 6543<br>
                            <strong>ID:</strong> CLI-0458
                        </div>
                    </div>
                    
                    <div class="activity-card">
                        <div class="activity-header">
                            <div class="activity-title">
                                <i class="fas fa-chart-line"></i> Reporte Generado
                            </div>
                            <div class="activity-time">Hoy, 10:00 AM</div>
                        </div>
                        <div class="activity-content">
                            <strong>Reporte:</strong> Ventas del Mes de Marzo<br>
                            <strong>Período:</strong> 01/03/2024 - 15/03/2024<br>
                            <strong>Total Ventas:</strong> $45,820.00<br>
                            <strong>Archivo:</strong> reporte_ventas_marzo.pdf
                        </div>
                    </div>
                    
                    <div class="activity-card">
                        <div class="activity-header">
                            <div class="activity-title">
                                <i class="fas fa-box"></i> Producto Actualizado
                            </div>
                            <div class="activity-time">Ayer, 4:30 PM</div>
                        </div>
                        <div class="activity-content">
                            <strong>Producto:</strong> Casco Integral LS2<br>
                            <strong>Código:</strong> PROD-0012<br>
                            <strong>Cambios:</strong> Precio actualizado y stock<br>
                            <strong>Usuario:</strong> Juan Pérez
                        </div>
                    </div>
                    
                    <div class="activity-card">
                        <div class="activity-header">
                            <div class="activity-title">
                                <i class="fas fa-sign-in-alt"></i> Inicio de Sesión
                            </div>
                            <div class="activity-time">Ayer, 8:00 AM</div>
                        </div>
                        <div class="activity-content">
                            <strong>Ubicación:</strong> Maracay, Aragua<br>
                            <strong>Dispositivo:</strong> Chrome en Windows 10<br>
                            <strong>IP:</strong> 192.168.1.100<br>
                            <strong>Duración:</strong> 9 horas 30 minutos
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-outline" id="loadMoreActivity">
                        <i class="fas fa-sync-alt"></i>
                        Cargar Más Actividad
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Cambiar Avatar -->
    <div class="modal-overlay" id="avatarModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-circle"></i> Cambiar Foto de Perfil</h3>
                <button class="modal-close" id="closeAvatarModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Selecciona tu avatar</label>
                    <div class="avatar-options" id="avatarOptions">
                        <div class="avatar-option" data-avatar="J">J</div>
                        <div class="avatar-option" data-avatar="A">A</div>
                        <div class="avatar-option" data-avatar="M">M</div>
                        <div class="avatar-option" data-avatar="C">C</div>
                        <div class="avatar-option" data-avatar="R">R</div>
                        <div class="avatar-option" data-avatar="P">P</div>
                        <div class="avatar-option" data-avatar="S">S</div>
                        <div class="avatar-option" data-avatar="D">D</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="avatarUpload">
                        <i class="fas fa-upload"></i> Subir Foto Personalizada
                    </label>
                    <input type="file" id="avatarUpload" class="form-control" accept="image/*">
                    <div class="form-hint">Formatos permitidos: JPG, PNG, GIF (Max. 5MB)</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelAvatarModal">Cancelar</button>
                <button class="btn btn-primary" id="saveAvatarBtn">Guardar Avatar</button>
            </div>
        </div>
    </div>

    <script>
        // Switch Toggle (si no está definido en otro lugar)
        const switchStyle = document.createElement('style');
        switchStyle.textContent = `
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
        `;
        document.head.appendChild(switchStyle);

        document.addEventListener('DOMContentLoaded', function() {
            // Control de tabs
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
                    document.getElementById(`tab-${tabId}`).classList.add('active');
                    
                    // Guardar en localStorage
                    localStorage.setItem('lastProfileTab', tabId);
                });
            });
            
            // Restaurar última pestaña
            const lastTab = localStorage.getItem('lastProfileTab');
            if (lastTab) {
                const tabBtn = document.querySelector(`[data-tab="${lastTab}"]`);
                const tabContent = document.getElementById(`tab-${lastTab}`);
                if (tabBtn && tabContent) {
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    tabBtn.classList.add('active');
                    tabContent.classList.add('active');
                }
            }
            
            // Control del modal de avatar
            const editAvatarBtn = document.getElementById('editAvatarBtn');
            const avatarModal = document.getElementById('avatarModal');
            const closeAvatarModal = document.getElementById('closeAvatarModal');
            const cancelAvatarModal = document.getElementById('cancelAvatarModal');
            const saveAvatarBtn = document.getElementById('saveAvatarBtn');
            const avatarOptions = document.querySelectorAll('.avatar-option');
            const profileAvatar = document.getElementById('profileAvatar');
            let selectedAvatar = 'J';
            
            if (editAvatarBtn && avatarModal) {
                editAvatarBtn.addEventListener('click', () => {
                    avatarModal.classList.add('active');
                });
                
                closeAvatarModal.addEventListener('click', () => {
                    avatarModal.classList.remove('active');
                });
                
                cancelAvatarModal.addEventListener('click', () => {
                    avatarModal.classList.remove('active');
                });
                
                // Cerrar modal al hacer clic fuera
                avatarModal.addEventListener('click', (e) => {
                    if (e.target === avatarModal) {
                        avatarModal.classList.remove('active');
                    }
                });
                
                // Seleccionar avatar
                avatarOptions.forEach(option => {
                    option.addEventListener('click', function() {
                        avatarOptions.forEach(o => o.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedAvatar = this.getAttribute('data-avatar');
                    });
                });
                
                // Guardar avatar
                saveAvatarBtn.addEventListener('click', () => {
                    profileAvatar.textContent = selectedAvatar;
                    avatarModal.classList.remove('active');
                    
                    // Simular guardado
                    const originalText = saveAvatarBtn.innerHTML;
                    saveAvatarBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                    saveAvatarBtn.disabled = true;
                    
                    setTimeout(() => {
                        saveAvatarBtn.innerHTML = originalText;
                        saveAvatarBtn.disabled = false;
                        alert('Avatar actualizado exitosamente');
                    }, 1000);
                });
                
                // Subir foto personalizada
                const avatarUpload = document.getElementById('avatarUpload');
                if (avatarUpload) {
                    avatarUpload.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                profileAvatar.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                                avatarModal.classList.remove('active');
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }
            }
            
            // Guardar perfil
            const saveProfileBtn = document.getElementById('saveProfileBtn');
            if (saveProfileBtn) {
                saveProfileBtn.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                    this.disabled = true;
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                        alert('Perfil actualizado exitosamente');
                    }, 1500);
                });
            }
            
            // Actualizar contraseña
            const updatePasswordBtn = document.getElementById('updatePasswordBtn');
            if (updatePasswordBtn) {
                updatePasswordBtn.addEventListener('click', function() {
                    const currentPass = document.getElementById('currentPassword').value;
                    const newPass = document.getElementById('newPassword').value;
                    const confirmPass = document.getElementById('confirmPassword').value;
                    
                    if (!currentPass || !newPass || !confirmPass) {
                        alert('Por favor complete todos los campos de contraseña');
                        return;
                    }
                    
                    if (newPass !== confirmPass) {
                        alert('Las contraseñas no coinciden');
                        return;
                    }
                    
                    if (newPass.length < 8) {
                        alert('La contraseña debe tener al menos 8 caracteres');
                        return;
                    }
                    
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
                    this.disabled = true;
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                        alert('Contraseña actualizada exitosamente');
                        document.getElementById('currentPassword').value = '';
                        document.getElementById('newPassword').value = '';
                        document.getElementById('confirmPassword').value = '';
                    }, 1500);
                });
            }
            
            // Cerrar todas las sesiones
            const logoutAllBtn = document.getElementById('logoutAllBtn');
            if (logoutAllBtn) {
                logoutAllBtn.addEventListener('click', function() {
                    if (confirm('¿Está seguro de cerrar todas las sesiones activas? Será redirigido al login.')) {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cerrando...';
                        this.disabled = true;
                        
                        setTimeout(() => {
                            alert('Todas las sesiones han sido cerradas. Será redirigido al login.');
                            window.location.href = '/logout.php';
                        }, 1000);
                    }
                });
            }
            
            // Guardar preferencias
            const savePreferencesBtn = document.getElementById('savePreferencesBtn');
            if (savePreferencesBtn) {
                savePreferencesBtn.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                    this.disabled = true;
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                        alert('Preferencias guardadas exitosamente');
                    }, 1500);
                });
            }
            
            // Cargar más actividad
            const loadMoreActivity = document.getElementById('loadMoreActivity');
            if (loadMoreActivity) {
                loadMoreActivity.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
                    this.disabled = true;
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                        alert('Más actividad cargada (simulación)');
                    }, 1000);
                });
            }
            
            // Exportar actividad
            const exportActivityBtn = document.getElementById('exportActivityBtn');
            if (exportActivityBtn) {
                exportActivityBtn.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportando...';
                    this.disabled = true;
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                        alert('Actividad exportada exitosamente como PDF');
                    }, 1500);
                });
            }
        });
    </script>
</body>
</html>