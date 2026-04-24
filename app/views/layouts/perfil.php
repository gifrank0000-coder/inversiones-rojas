<?php
// Iniciar sesión y cargar datos del usuario antes de enviar cualquier salida
if (session_status() == PHP_SESSION_NONE) session_start();

// Cargar configuración general (BASE_URL, etc.)
require_once __DIR__ . '/../../../config/config.php';

require_once __DIR__ . '/../../models/database.php';

// Conexión a la base de datos
$pdo = Database::getInstance();

// Obtener información del usuario desde la sesión
$user = null;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$session_username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$session_email = isset($_SESSION['email']) ? $_SESSION['email'] : null;

try {
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id WHERE u.id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } elseif ($session_username) {
        $stmt = $pdo->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id WHERE u.username = ?");
        $stmt->execute([$session_username]);
        $user = $stmt->fetch();
    } elseif ($session_email) {
        $stmt = $pdo->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id WHERE u.email = ?");
        $stmt->execute([$session_email]);
        $user = $stmt->fetch();
    }
} catch (Exception $e) {
    $user = null;
}

// Si encontramos el usuario, establecer su id para consultas posteriores
if ($user && isset($user['id'])) {
    $user_id = $user['id'];
}

// Iniciales para avatar
$iniciales = 'J';
if ($user && isset($user['nombre_completo'])) {
    $nombre_tmp = trim($user['nombre_completo']);
    if (!empty($nombre_tmp)) {
        $iniciales = strtoupper(substr($nombre_tmp, 0, 1));
    }
}

// Obtener actividades y estadísticas solo si hay un usuario identificado
if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT bs.accion, bs.usuario_id, u.nombre_completo as usuario, bs.created_at FROM bitacora_sistema bs LEFT JOIN usuarios u ON bs.usuario_id = u.id WHERE bs.usuario_id = ? ORDER BY bs.created_at DESC LIMIT 10");
        $stmt->execute([$user_id]);
        $actividades = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT v.id) as total_ventas, COUNT(DISTINCT c.id) as clientes_registrados, COUNT(DISTINCT bs.id) as actividades_total, COALESCE(SUM(v.total), 0) as ventas_monto_total FROM usuarios u LEFT JOIN ventas v ON u.id = v.usuario_id LEFT JOIN clientes c ON u.id = c.usuario_id LEFT JOIN bitacora_sistema bs ON u.id = bs.usuario_id WHERE u.id = ?");
        $stmt->execute([$user_id]);
        $estadisticas = $stmt->fetch();
    } catch (Exception $e) {
        $actividades = [];
        $estadisticas = ['total_ventas' => 0, 'clientes_registrados' => 0, 'actividades_total' => 0, 'ventas_monto_total' => 0];
    }
} else {
    $actividades = [];
    $estadisticas = ['total_ventas' => 0, 'clientes_registrados' => 0, 'actividades_total' => 0, 'ventas_monto_total' => 0];
}

// Preparar valores para mostrar y para poblar el formulario
$display_username = $user['username'] ?? '';
$display_nombre = $user['nombre_completo'] ?? '';
$display_email = $user['email'] ?? '';
$display_rol = $user['rol_nombre'] ?? '';
$member_since = isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '';

// separar nombre completo en nombre y apellido (fallback simples)
$first_name = '';
$last_name = '';
if (!empty($display_nombre)) {
    $parts = preg_split('/\s+/', $display_nombre, 2);
    $first_name = $parts[0] ?? '';
    $last_name = $parts[1] ?? '';
}

$display_telefono = '';
$display_cedula = '';

// Intentar obtener teléfono y cédula desde la tabla clientes si existe relación por usuario
if (isset($pdo) && $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT cedula_rif, telefono_principal FROM clientes WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $cliente = $stmt->fetch();
        if ($cliente) {
            $display_cedula = $cliente['cedula_rif'] ?? '';
            $display_telefono = $cliente['telefono_principal'] ?? '';
        }
    } catch (Exception $e) {
        // no interrumpir la vista por errores menores
    }

    // fallback: si existe columna 'cedula' en usuarios
    if (empty($display_cedula) && isset($user['cedula'])) {
        $display_cedula = $user['cedula'];
    }
}

?>

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

        /* Estilos para botón de regreso mejorado (reutilizable) */
        .back-btn-wrapper {
            padding: 18px 24px;
            background: transparent;
            margin-bottom: 12px;
        }
        .back-btn-enhanced {
            background: linear-gradient(135deg, #1F9166 0%, #30B583 100%);
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(31,145,102,0.18);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .back-btn-enhanced:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(31,145,102,0.22); }
        .back-btn-enhanced i { font-size: 16px; }

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
        /* TARJETAS DE ACTIVIDAD - Estilo como en ventas.php */
        /* ========================================== */
        .activity-section {
            margin-top: 30px;
            padding: 25px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .activity-section h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #1F9166;
            transition: all 0.3s;
        }

        .activity-item:hover {
            background: #f1f3f4;
            transform: translateX(5px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .activity-icon.sales {
            background: linear-gradient(135deg, #1F9166, #30B583);
        }

        .activity-icon.payment {
            background: linear-gradient(135deg, #3498db, #5dade2);
        }

        .activity-icon.client {
            background: linear-gradient(135deg, #9b59b6, #bb8fce);
        }

        .activity-icon.info {
            background: linear-gradient(135deg, #f39c12, #f5b041);
        }

        .activity-content {
            flex: 1;
        }

        .activity-content p {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-weight: 500;
            line-height: 1.4;
        }

        .activity-content span {
            font-size: 0.85rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .activity-content span i {
            font-size: 0.75rem;
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
            
            .activity-item {
                flex-direction: column;
                align-items: flex-start;
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
    </style>
    </head>
    <body>
        <!-- Botón de regreso mejorado -->
        <div class="back-btn-wrapper">
            <button class="back-btn-enhanced" onclick="history.back()" aria-label="Regresar">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Regresar
            </button>
        </div>

    <div class="profile-container">
        <!-- Header del Perfil -->
        <div class="profile-header">
            <div class="profile-header-content">
                <div class="profile-avatar-section">
                    <div class="profile-avatar" id="profileAvatar">
                        <?php echo htmlspecialchars($iniciales); ?>
                    </div>
                    <button class="avatar-edit-btn" id="editAvatarBtn">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                
                <div class="profile-info">
                    <h1 class="profile-name">
                        <?php echo $user ? htmlspecialchars($user['nombre_completo']) : 'Juan Andrés Pérez'; ?>
                    </h1>
                    <span class="profile-role">
                        <?php echo $user && $user['rol_nombre'] ? htmlspecialchars($user['rol_nombre']) : 'Administrador'; ?>
                    </span>
                    <p class="profile-email">
                        <i class="fas fa-envelope"></i>
                        <?php echo $user ? htmlspecialchars($user['email']) : 'juan.perez@inversionesrojas.com'; ?>
                    </p>
                    <p class="profile-member-since">
                        <i class="fas fa-calendar-alt"></i>
                        Miembro desde: <?php echo $user ? date('d/m/Y', strtotime($user['created_at'])) : '15 de Marzo, 2023'; ?>
                    </p>
                </div>
            </div>
            
            <!-- Estadísticas del Usuario (solo para roles internos, no para clientes) -->
            <?php if (!empty($display_rol) && strtolower(trim($display_rol)) !== 'cliente'): ?>
            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-number"><?php echo $estadisticas ? $estadisticas['total_ventas'] : 0; ?></div>
                    <div class="stat-label">Total Ventas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo $estadisticas ? $estadisticas['clientes_registrados'] : 0; ?></div>
                    <div class="stat-label">Clientes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-number">$<?php echo $estadisticas ? number_format($estadisticas['ventas_monto_total'], 0) : '0'; ?></div>
                    <div class="stat-label">Monto Ventas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="stat-number"><?php echo $estadisticas ? $estadisticas['actividades_total'] : 0; ?></div>
                    <div class="stat-label">Actividades</div>
                </div>
            </div>
            <?php endif; ?>
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
                <?php if (!empty($display_rol) && strtolower(trim($display_rol)) !== 'cliente'): ?>
                <button class="tab-btn" data-tab="activity">
                    <i class="fas fa-history"></i>
                    Actividad
                </button>
                <?php endif; ?>
            </div>

            <!-- Tab: Información Personal -->
            <div class="tab-content active" id="tab-info">
                <div class="config-section">
                    <div class="section-header">
                        <h2><i class="fas fa-id-card"></i> Información Básica</h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">
                                <i class="fas fa-user-circle"></i> Username
                            </label>
                            <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($display_username); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="nombre_completo">
                                <i class="fas fa-user"></i> Nombre Completo
                            </label>
                            <input type="text" id="nombre_completo" class="form-control" value="<?php echo htmlspecialchars($display_nombre); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Correo Electrónico
                            </label>
                            <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($display_email); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Teléfono
                            </label>
                            <input type="tel" id="phone" class="form-control" value="<?php echo htmlspecialchars($display_telefono ?: ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="rol">
                                <i class="fas fa-user-tag"></i> Rol
                            </label>
                            <input type="text" id="rol" class="form-control" value="<?php echo htmlspecialchars($display_rol); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="cedula">
                                <i class="fas fa-id-card"></i> Cédula / RIF
                            </label>
                            <input type="text" id="cedula" class="form-control" value="<?php echo htmlspecialchars($display_cedula ?: ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-primary" id="saveProfileBtn">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                    <button class="btn btn-outline" id="resetProfileBtn">
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

            <!-- Tab: Actividad (Estilo como en ventas.php) -->
            <?php if (!empty($display_rol) && strtolower(trim($display_rol)) !== 'cliente'): ?>
            <div class="tab-content" id="tab-activity">
                <div class="activity-section">
                    <h3><i class="fas fa-history"></i> Actividad Reciente</h3>
                    <div class="activity-list">
                        <?php if (empty($actividades)): ?>
                            <div class="activity-item">
                                <div class="activity-icon info">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="activity-content">
                                    <p>No hay actividades registradas</p>
                                    <span>Realiza alguna acción en el sistema para ver tu historial</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($actividades as $actividad): ?>
                                <?php 
                                // Determinar icono y color basado en la acción (como en ventas.php)
                                $icon = 'fas fa-cog';
                                $color = 'info';
                                $accion = htmlspecialchars($actividad['accion']);
                                
                                if (stripos($accion, 'venta') !== false) {
                                    $icon = 'fas fa-shopping-cart';
                                    $color = 'sales';
                                } elseif (stripos($accion, 'pago') !== false) {
                                    $icon = 'fas fa-credit-card';
                                    $color = 'payment';
                                } elseif (stripos($accion, 'cliente') !== false) {
                                    $icon = 'fas fa-user-plus';
                                    $color = 'client';
                                } elseif (stripos($accion, 'producto') !== false) {
                                    $icon = 'fas fa-box';
                                    $color = 'info';
                                } elseif (stripos($accion, 'login') !== false || stripos($accion, 'sesión') !== false) {
                                    $icon = 'fas fa-sign-in-alt';
                                    $color = 'payment';
                                }
                                ?>
                                <div class="activity-item">
                                    <div class="activity-icon <?php echo $color; ?>">
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p><?php echo $accion; ?></p>
                                        <span>
                                            <?php echo htmlspecialchars($actividad['usuario'] ?? 'Sistema'); ?> • 
                                            <?php echo date('H:i', strtotime($actividad['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-outline" id="loadMoreActivity">
                        <i class="fas fa-sync-alt"></i>
                        Cargar Más Actividad
                    </button>
                    <button class="btn btn-primary" id="exportActivityBtn">
                        <i class="fas fa-download"></i>
                        Exportar Actividad
                    </button>
                </div>
            </div>
            <?php endif; ?>
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
                        <?php 
                        $opciones = ['J', 'A', 'M', 'C', 'R', 'P', 'S', 'D'];
                        foreach ($opciones as $opcion): 
                        ?>
                            <div class="avatar-option <?php echo $opcion == $iniciales ? 'selected' : ''; ?>" 
                                 data-avatar="<?php echo $opcion; ?>">
                                <?php echo $opcion; ?>
                            </div>
                        <?php endforeach; ?>
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
        document.addEventListener('DOMContentLoaded', function() {
            console.info('Perfil.js cargado');

            const baseUrl = (typeof BASE_URL !== 'undefined' && BASE_URL) ? String(BASE_URL).replace(/\/+$/, '') : (window.location.origin + '/inversiones-rojas');
            console.info('BASE_URL usado en perfil:', baseUrl);

            window.addEventListener('error', function(event) {
                console.error('Error global en perfil.js:', event.message, 'en', event.filename + ':' + event.lineno);
            });

            try {


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
                });
            });
            
            // Control del modal de avatar
            const editAvatarBtn = document.getElementById('editAvatarBtn');
            const avatarModal = document.getElementById('avatarModal');
            const closeAvatarModal = document.getElementById('closeAvatarModal');
            const cancelAvatarModal = document.getElementById('cancelAvatarModal');
            const saveAvatarBtn = document.getElementById('saveAvatarBtn');
            const avatarOptions = document.querySelectorAll('.avatar-option');
            const profileAvatar = document.getElementById('profileAvatar');
            let selectedAvatar = '<?php echo $iniciales; ?>';
            
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
                saveProfileBtn.addEventListener('click', async function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                    this.disabled = true;

                    const payload = {
                        username: document.getElementById('username').value,
                        nombre_completo: document.getElementById('nombre_completo').value,
                        email: document.getElementById('email').value,
                        telefono: document.getElementById('phone').value,
                        cedula: document.getElementById('cedula').value
                    };

                    try {
                        const resp = await fetch(baseUrl + '/api/update_profile.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        });

                        const data = await resp.json();
                        if (!resp.ok || !data.success) {
                            throw new Error(data.message || 'Error al actualizar el perfil');
                        }

                        alert(data.message || 'Perfil actualizado exitosamente');

                        // Recargar la página para reflejar los cambios
                        window.location.reload();

                    } catch (err) {
                        alert('No se pudo actualizar el perfil: ' + err.message);
                    } finally {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }
                });
            }
            
            // Restablecer perfil
            const resetProfileBtn = document.getElementById('resetProfileBtn');
            if (resetProfileBtn) {
                resetProfileBtn.addEventListener('click', function() {
                    if (confirm('¿Restablecer todos los cambios?')) {
                        location.reload();
                    }
                });
            }
            
            // Actualizar contraseña
            const updatePasswordBtn = document.getElementById('updatePasswordBtn');
            if (updatePasswordBtn) {
                updatePasswordBtn.addEventListener('click', async function() {
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

                    try {
                        const resp = await fetch(baseUrl + '/api/update_profile.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                current_password: currentPass,
                                new_password: newPass,
                                confirm_password: confirmPass
                            })
                        });

                        const data = await resp.json();
                        if (!resp.ok || !data.success) {
                            throw new Error(data.message || 'Error al actualizar la contraseña');
                        }

                        alert(data.message || 'Contraseña actualizada exitosamente');
                        document.getElementById('currentPassword').value = '';
                        document.getElementById('newPassword').value = '';
                        document.getElementById('confirmPassword').value = '';

                    } catch (err) {
                        alert('No se pudo actualizar la contraseña: ' + err.message);
                    } finally {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }
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

        } catch (err) {
            console.error('Error en perfil.js:', err);
            alert('Error en el perfil: ' + (err.message || err));
        }
        });
    </script>
</body>
</html>