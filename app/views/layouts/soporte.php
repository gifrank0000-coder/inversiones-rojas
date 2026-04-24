<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
// Cargar productos reales para sliders
require_once __DIR__ . '/../../models/database.php';

// Obtener roles desde la sesión real y normalizar usando el helper
require_once __DIR__ . '/../../helpers/permissions.php';
// Lista de roles legibles y tokenizados
$roles_disponibles = ['cliente', 'operador', 'administrador'];
// Normalizar rol desde sesión y convertir a token en minúsculas (cliente|operador|administrador)
$rol_actual = strtolower(canonical_role($_SESSION['user_rol'] ?? $_SESSION['rol'] ?? 'Cliente'));
// canonical_role puede devolver valores como 'Administrador' — convertir a token seguro
if (!in_array($rol_actual, $roles_disponibles, true)) {
    // Intentar mapear variantes comunes
    $map = [
        'administrador' => 'administrador',
        'admin' => 'administrador',
        'administr' => 'administrador',
        'operador' => 'operador',
        'vendedor' => 'operador',
        'cliente' => 'cliente'
    ];
    $found = 'cliente';
    foreach ($map as $k => $v) {
        if (strpos($rol_actual, $k) !== false) { $found = $v; break; }
    }
    $rol_actual = $found;
}

// Conexión y carga de datos reales desde la base de datos
$incidencias_data = [];
$operadores_data = [];
$user_reports_data = [];
$incidencias_stats = [
    'total' => 0,
    'pendientes' => 0,
    'proceso' => 0,
    'resueltos' => 0
];
$current_user = [
    'id' => null,
    'nombre' => null,
    'cliente_id' => null
];

$pdo = \Database::getInstance();
if ($pdo) {
    try {
        // Obtener usuarios operadores (buscar rol 'operador')
        $stmt = $pdo->prepare("SELECT u.id, u.nombre_completo FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE lower(r.nombre) LIKE :term AND u.estado = true ORDER BY u.nombre_completo");
        $stmt->execute([':term' => '%operador%']);
        $operadores_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Incidencias (todas) con nombres relacionados
        $sql = "SELECT i.id, i.codigo_incidencia, i.cliente_id, c.nombre_completo AS cliente_nombre, 
                       i.usuario_id, u.nombre_completo AS creador_nombre, 
                       i.descripcion, i.urgencia, i.modulo_sistema, i.estado, 
                       i.asignado_a, a.nombre_completo AS asignado_nombre, 
                       to_char(i.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at, 
                       i.notas_solucion 
                FROM incidencias_soporte i 
                LEFT JOIN clientes c ON i.cliente_id = c.id 
                LEFT JOIN usuarios u ON i.usuario_id = u.id 
                LEFT JOIN usuarios a ON i.asignado_a = a.id 
                ORDER BY i.created_at DESC";
        $stmt = $pdo->query($sql);
        $incidencias_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Normalizar estructura para JS (mantener campos esperados por el frontend)
        $incidencias_data_js = [];
        foreach ($incidencias_data as $itm) {
            $incidencias_data_js[] = [
                'id' => $itm['codigo_incidencia'] ?? $itm['id'],
                'db_id' => $itm['id'],
                'descripcion' => $itm['descripcion'] ?? '',
                'usuario' => $itm['creador_nombre'] ?? '',
                'urgencia' => $itm['urgencia'] ?? '',
                'modulo' => $itm['modulo_sistema'] ?? '',
                'estado' => $itm['estado'] ?? '',
                'asignado' => $itm['asignado_nombre'] ?? '',  // IMPORTANTE: Este campo faltaba
                'asignado_id' => $itm['asignado_a'] ?? null,
                'fecha' => $itm['created_at'] ?? '',
                'notas' => $itm['notas_solucion'] ?? ''
            ];
        }

        // Estadísticas
        $stmt = $pdo->query("SELECT COUNT(*) AS total, 
                                     SUM(CASE WHEN estado='pendiente' THEN 1 ELSE 0 END) AS pendientes, 
                                     SUM(CASE WHEN estado='proceso' THEN 1 ELSE 0 END) AS en_proceso, 
                                     SUM(CASE WHEN estado='resuelto' THEN 1 ELSE 0 END) AS resueltos 
                              FROM incidencias_soporte");
        $incidencias_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Información del usuario actual
        $current_user_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? null;
        if ($current_user_id) {
            $stmt = $pdo->prepare("SELECT id, nombre_completo, cliente_id FROM usuarios WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $current_user_id]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($u) {
                $current_user['id'] = $u['id'];
                $current_user['nombre'] = $u['nombre_completo'];
                $current_user['cliente_id'] = $u['cliente_id'];
            }

            // Reportes del usuario (por usuario o por cliente relacionado)
            $params = [':uid' => $current_user_id];
            if ($current_user['cliente_id']) {
                $sql = "SELECT i.id, i.codigo_incidencia, i.descripcion, i.urgencia, i.modulo_sistema, i.estado, 
                               to_char(i.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at, 
                               a.nombre_completo AS asignado_nombre 
                        FROM incidencias_soporte i 
                        LEFT JOIN usuarios a ON i.asignado_a = a.id 
                        WHERE i.usuario_id = :uid OR i.cliente_id = :cid 
                        ORDER BY i.created_at DESC";
                $params[':cid'] = $current_user['cliente_id'];
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            } else {
                $sql = "SELECT i.id, i.codigo_incidencia, i.descripcion, i.urgencia, i.modulo_sistema, i.estado, 
                               to_char(i.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at, 
                               a.nombre_completo AS asignado_nombre 
                        FROM incidencias_soporte i 
                        LEFT JOIN usuarios a ON i.asignado_a = a.id 
                        WHERE i.usuario_id = :uid 
                        ORDER BY i.created_at DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':uid' => $current_user_id]);
            }
            $user_reports_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Normalizar reportes de usuario para JS
            $user_reports_data_js = [];
            foreach ($user_reports_data as $r) {
                $user_reports_data_js[] = [
                    'id' => $r['codigo_incidencia'] ?? $r['id'],
                    'description' => $r['descripcion'] ?? '',
                    'urgency' => $r['urgencia'] ?? '',
                    'systemPart' => $r['modulo_sistema'] ?? '',
                    'date' => $r['created_at'] ?? '',
                    'status' => $r['estado'] ?? '',
                    'solution' => $r['notas_solucion'] ?? ''
                ];
            }
        }

    } catch (PDOException $e) {
        error_log('Soporte: error cargando datos: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Soporte Técnico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos para botón de regreso mejorado */
        .back-btn-wrapper {
            padding: 18px 24px;
            background: transparent;
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

        /* ===== ESTILOS BASE ===== */
        * {
            margin: 0;
            padding: 0;
            align-items: center;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f7fb;
            color: #333;
            min-height: 100vh;
        }

        /* ===== HEADER DE SISTEMA ===== */
        .system-header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        /* ===== BOTONES DE URGENCIA ===== */
        .urgency-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 15px 0;
        }

        .urgency-btn {
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .urgency-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .urgency-btn.selected {
            border-color: #1F9166;
            background: rgba(31, 145, 102, 0.05);
        }

        .urgency-btn.alta.selected {
            border-color: #e74c3c;
            background: rgba(231, 76, 60, 0.05);
        }

        .urgency-btn i {
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
        }

        .urgency-btn.alta i { color: #e74c3c; }
        .urgency-btn.media i { color: #f39c12; }
        .urgency-btn.baja i { color: #1F9166; }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            background: #1F9166;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .logo-text h1 {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 3px;
        }

        .logo-text p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 25px;
        }

        .role-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-cliente {
            background: #e3f2fd;
            color: #1976d2;
        }

        .role-operador {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .role-admin {
            background: #fff3e0;
            color: #f57c00;
        }

        .role-selector {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .role-btn {
            padding: 8px 15px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .role-btn:hover {
            transform: translateY(-2px);
        }

        .role-btn.active {
            border-color: #1F9166;
            background: rgba(31, 145, 102, 0.1);
        }

        /* ===== CONTENEDOR PRINCIPAL ===== */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 25px;
        }

        /* ===== ESTILOS COMUNES PARA TODAS LAS VISTAS ===== */
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .section-title {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

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

        .btn-sm {
            padding: 10px 18px;
            font-size: 0.9rem;
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

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* ===== BADGES COMUNES ===== */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            min-width: 100px;
        }

        .status-pendiente {
            background: #fff3cd;
            color: #856404;
        }

        .status-proceso {
            background: #cce5ff;
            color: #004085;
        }

        .status-resuelto {
            background: #d4edda;
            color: #155724;
        }

        .urgency-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .urgency-alta {
            background: #f8d7da;
            color: #721c24;
        }

        .urgency-media {
            background: #fff3cd;
            color: #856404;
        }

        .urgency-baja {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* ===== TABLAS ===== */
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 800px;
        }

        .data-table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        .data-table th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        .data-table td {
            padding: 18px 15px;
            color: #495057;
            font-size: 0.95rem;
            vertical-align: middle;
        }

        /* ===== MODAL ===== */
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
            animation: fadeIn 0.3s;
        }

        .modal-overlay.active {
            display: flex;
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
            font-size: 1.5rem;
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

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* ===== ESTADÍSTICAS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            border-top: 4px solid;
        }

        .stat-card.total { border-color: #2c3e50; }
        .stat-card.pendientes { border-color: #f39c12; }
        .stat-card.proceso { border-color: #3498db; }
        .stat-card.resueltos { border-color: #1F9166; }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.3rem;
        }

        .stat-card.total .stat-icon { background: #2c3e50; }
        .stat-card.pendientes .stat-icon { background: #f39c12; }
        .stat-card.proceso .stat-icon { background: #3498db; }
        .stat-card.resueltos .stat-icon { background: #1F9166; }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .main-container {
                padding: 15px;
            }
            
            .system-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }
            
            .user-info {
                width: 100%;
                justify-content: space-between;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .card {
                padding: 20px;
            }
            
            .modal {
                width: 95%;
            }
            
            .modal-body {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 10px;
            }
            
            .system-header {
                padding: 15px;
            }
            
            .logo-text h1 {
                font-size: 1.3rem;
            }
        }

        /* ===== OCULTAR/MOSTRAR VISTAS ===== */
        .view {
            display: none;
        }

        .view.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        /* ===== HERO HEADER PARA VISTAS POR ROL ===== */
        .role-hero { padding: 20px; display: flex; gap: 18px; align-items: center; }
        .role-hero .hero-icon { width: 72px; height: 72px; border-radius: 12px; display:flex; align-items:center; justify-content:center; color: #fff; font-size: 1.6rem; }
        .role-hero .hero-content { flex: 1; }
        .hero-title { font-size: 1.6rem; color: #123; margin: 0 0 6px 0; }
        .hero-subtitle { color: #6c757d; margin: 0 0 10px 0; font-weight:600; }
        .hero-desc { background: rgba(31,145,102,0.06); border-left: 4px solid #1F9166; padding: 12px 14px; border-radius: 8px; color: #234; margin-top: 10px; }
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

    <!-- Header del Sistema -->
    <div class="system-header">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-headset"></i>
            </div>
            <div class="logo-text">
                <h1>Sistema de Soporte Técnico</h1>
                <p>Soporte y seguimiento de incidencias</p>
            </div>
        </div>
    </div>

    <!-- VISTA DE CLIENTE -->
    <div class="view <?php echo $rol_actual == 'cliente' ? 'active' : ''; ?>" id="clienteView">
        <!-- Cabecera principal cliente (hero) -->
        <div class="card role-hero">
            <div class="hero-icon" style="background:#1F9166"><i class="fas fa-headset"></i></div>
            <div class="hero-content">
                <h2 class="hero-title">Centro de Soporte</h2>
                <p class="hero-subtitle">Envía tus reportes y sigue su estado</p>
                <div class="hero-desc">Describe tu problema con detalle y nuestro equipo de soporte lo revisará cuanto antes. Recibirás un número de seguimiento para consultar el estado.</div>
            </div>
        </div>

        <!-- Mensajes -->
        <div class="message success" id="successMessage" style="display: none;">
            <strong>¡Reporte enviado!</strong> Tu problema ha sido registrado con éxito.
        </div>

        <!-- Formulario principal -->
        <div class="card" id="formContainer">
            <div class="form-group">
                <label>¿Qué está pasando?</label>
                <textarea 
                    class="form-control" 
                    placeholder="Describe el problema que tienes. Sé lo más claro posible..."
                    id="problemDescription"
                ></textarea>
            </div>

            <div class="form-group">
                <label>¿Qué tan urgente es?</label>
                <div class="urgency-buttons" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 15px 0;">
                    <button class="urgency-btn alta" data-urgency="alta">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>Urgente</div>
                        <small>Sistema no funciona</small>
                    </button>
                    <button class="urgency-btn media" data-urgency="media">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>Importante</div>
                        <small>Funciona limitado</small>
                    </button>
                    <button class="urgency-btn baja" data-urgency="baja">
                        <i class="fas fa-info-circle"></i>
                        <div>Consulta</div>
                        <small>Pregunta general</small>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label>¿En qué parte del sistema?</label>
                <select class="form-control" id="systemPart">
                    <option value="">Selecciona una opción...</option>
                    <option value="ventas">Ventas / Facturación</option>
                    <option value="inventario">Inventario / Productos</option>
                    <option value="compras">Compras / Proveedores</option>
                    <option value="clientes">Clientes / Usuarios</option>
                    <option value="reportes">Reportes / Estadísticas</option>
                    <option value="login">Acceso / Inicio de sesión</option>
                    <option value="otros">Otro / No estoy seguro</option>
                </select>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 30px;">
                <button class="btn btn-secondary" id="viewMyReports">
                    <i class="fas fa-history"></i> Ver mis reportes
                </button>
                <button class="btn btn-primary" id="sendReport">
                    <i class="fas fa-paper-plane"></i> Enviar reporte
                </button>
            </div>
        </div>

        <!-- Sección de mis reportes (oculta inicialmente) -->
        <div class="card" id="myReports" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
                <h2 class="section-title"><i class="fas fa-clipboard-list"></i> Mis Reportes Anteriores</h2>
                <button class="btn btn-secondary" id="backToForm">
                    <i class="fas fa-arrow-left"></i> Nuevo reporte
                </button>
            </div>

            <div id="reportsList">
                <!-- Los reportes se cargarán aquí -->
            </div>

            <div class="empty-state" id="emptyReports" style="text-align: center; padding: 40px 20px; color: #6c757d;">
                <i class="fas fa-clipboard-list" style="font-size: 3rem; margin-bottom: 15px; color: #dee2e6;"></i>
                <h3>No tienes reportes aún</h3>
                <p>Cuando envíes un reporte, aparecerá aquí con su estado actual.</p>
            </div>
        </div>
    </div>

    <!-- VISTA DE OPERADOR -->
    <div class="view <?php echo $rol_actual == 'operador' ? 'active' : ''; ?>" id="operadorView">
        <!-- Hero header operador -->
        <div class="card role-hero">
            <div class="hero-content">
                <h2 class="hero-title">Panel del Operador</h2>
                <p class="hero-subtitle">Gestiona las incidencias asignadas a ti</p>
                <div class="hero-desc">Revisa las incidencias asignadas, agrega notas de solución y marca como resueltas cuando corresponda.</div>
            </div>
        </div>

        <!-- Estadísticas del Operador -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-number" id="totalAsignadas">0</div>
                <div class="stat-label">Asignadas a Mí</div>
            </div>
            <div class="stat-card proceso">
                <div class="stat-icon">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-number" id="enProcesoOp">0</div>
                <div class="stat-label">En Proceso</div>
            </div>
            <div class="stat-card resueltos">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number" id="resueltasOp">0</div>
                <div class="stat-label">Resueltas</div>
            </div>
        </div>

        <!-- Tabla de Incidencias del Operador -->
        <div class="card">
            <h3 class="section-title"><i class="fas fa-tasks"></i> Mis Incidencias</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Descripción</th>
                            <th>Usuario</th>
                            <th>Urgencia</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="operadorIncidentsTable">
                        <!-- Los datos se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- VISTA DE ADMINISTRADOR -->
    <div class="view <?php echo $rol_actual == 'administrador' ? 'active' : ''; ?>" id="administradorView">
        <!-- Hero header administrador -->
        <div class="card role-hero">
            <div class="hero-content">
                <h2 class="hero-title">Panel de Administración</h2>
                <p class="hero-subtitle">Asigna incidencias a operadores para su resolución</p>
                <div class="hero-desc">Supervisa y administra todas las incidencias, asigna prioridad y operadores para una resolución eficiente.</div>
            </div>
        </div>

        <!-- Estadísticas del Administrador -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-number" id="totalIncidencias">0</div>
                <div class="stat-label">Total Incidencias</div>
            </div>
            <div class="stat-card pendientes">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number" id="pendientes">0</div>
                <div class="stat-label">Pendientes</div>
            </div>
            <div class="stat-card proceso">
                <div class="stat-icon">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-number" id="enProcesoAdmin">0</div>
                <div class="stat-label">En Proceso</div>
            </div>
            <div class="stat-card resueltos">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number" id="resueltasAdmin">0</div>
                <div class="stat-label">Resueltas</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card">
            <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
                    <input type="text" class="form-control" placeholder="Buscar incidencias..." id="searchInput" style="padding-left: 40px;">
                </div>
                <select class="form-control" id="filterStatus" style="min-width: 150px;">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="proceso">En Proceso</option>
                    <option value="resuelto">Resuelto</option>
                </select>
                <select class="form-control" id="filterUrgency" style="min-width: 150px;">
                    <option value="">Todas las urgencias</option>
                    <option value="alta">Alta</option>
                    <option value="media">Media</option>
                    <option value="baja">Baja</option>
                </select>
            </div>

            <!-- Tabla de Incidencias del Administrador -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Descripción</th>
                            <th>Usuario</th>
                            <th>Urgencia</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Asignado a</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="adminIncidentsTable">
                        <!-- Los datos se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para asignar incidencia (Administrador) -->
    <div class="modal-overlay" id="assignModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-check"></i> Asignar Incidencia</h3>
                <button class="modal-close" id="closeAssignModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Incidencia</label>
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; margin-bottom: 15px;">
                        <strong id="incidentIdText"></strong><br>
                        <small id="incidentDescText" style="color: #6c757d;"></small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="assignTo">Asignar a operador</label>
                    <select id="assignTo" class="form-control">
                        <option value="">Seleccionar operador...</option>
                        <option value="operador1">Juan Pérez</option>
                        <option value="operador2">María González</option>
                        <option value="operador3">Carlos Rodríguez</option>
                        <option value="operador4">Ana Torres</option>
                    </select>
                </div>
                
                <div style="background: #e8f5e9; padding: 10px; border-radius: 6px; margin-top: 15px; font-size: 0.85rem;">
                    <i class="fas fa-info-circle"></i> Al asignar, la incidencia cambiará a estado "En Proceso"
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelAssign">Cancelar</button>
                <button class="btn btn-primary" id="confirmAssign">
                    <i class="fas fa-check"></i> Asignar Incidencia
                </button>
            </div>
        </div>
    </div>

    <!-- Modal para marcar como resuelto (Operador) -->
    <div class="modal-overlay" id="resolveModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-check-circle"></i> Marcar como Resuelto</h3>
                <button class="modal-close" id="closeResolveModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Incidencia</label>
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; margin-bottom: 15px;">
                        <strong id="resolveIncidentId"></strong><br>
                        <small id="resolveIncidentDesc" style="color: #6c757d;"></small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="solutionNotes">Notas de solución</label>
                    <textarea id="solutionNotes" class="form-control" placeholder="Describe cómo resolviste el problema..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelResolve">Cancelar</button>
                <button class="btn btn-primary" id="confirmResolve">
                    <i class="fas fa-check"></i> Marcar como Resuelto
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación (Cliente) -->
    <div class="modal-overlay" id="confirmationModal">
        <div class="modal">
            <div class="modal-header">
                <i class="fas fa-check-circle"></i>
                <h3>¡Reporte Enviado!</h3>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Hemos recibido tu reporte. Nuestro equipo de soporte lo revisará pronto.</p>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; font-size: 1.5rem; font-weight: bold; color: #1F9166; margin: 20px 0; display: inline-block; min-width: 150px;" id="reportCode">#REP-001</div>
                
                <p>Guarda este número para seguir el estado de tu reporte.</p>
                
                <div style="display: flex; gap: 15px; justify-content: center; margin-top: 25px;">
                    <button class="btn btn-secondary" id="closeConfirmationModal">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                    <button class="btn btn-primary" id="viewReport">
                        <i class="fas fa-eye"></i> Ver mi reporte
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ============================================
            // DATOS COMPARTIDOS ENTRE TODOS LOS ROLES
            // ============================================

            // Asegurar que ningún modal esté abierto por defecto
            try {
                const _assign = document.getElementById('assignModal'); if (_assign) _assign.classList.remove('active');
                const _resolve = document.getElementById('resolveModal'); if (_resolve) _resolve.classList.remove('active');
                const _confirm = document.getElementById('confirmationModal'); if (_confirm) _confirm.classList.remove('active');
            } catch (e) {
                // no interrumpir si no existen
            }
            
            // Datos cargados desde el servidor (PHP -> JSON)
            const operadores = <?php echo json_encode(array_column($operadores_data, 'nombre_completo', 'id') ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;

            // Datos REALES de incidencias desde PHP
            const incidenciasDataFromPHP = <?php echo json_encode($incidencias_data_js ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;

            // Reportes del usuario actual (si existe sesión)
            let userReports = <?php echo json_encode($user_reports_data_js ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;

            const serverStats = <?php echo json_encode($incidencias_stats ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
            const currentUserInfo = <?php echo json_encode($current_user ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
            const currentRoleFromPHP = '<?php echo addslashes($rol_actual); ?>';

            // ============================================
            // FUNCIONES COMPARTIDAS
            // ============================================
            
            function getUrgencyText(urgency) {
                const urgencyMap = {
                    'alta': 'Alta',
                    'media': 'Media',
                    'baja': 'Baja'
                };
                return urgencyMap[urgency] || urgency;
            }

            function getStatusText(status) {
                const statusMap = {
                    'pendiente': 'Pendiente',
                    'proceso': 'En Proceso',
                    'resuelto': 'Resuelto'
                };
                return statusMap[status] || status;
            }

            function getOperatorValue(name) {
                if (!name) return '';
                for (const [key, value] of Object.entries(operadores)) {
                    if (value === name) return key;
                }
                return '';
            }

            function getOperatorName(value) {
                return operadores[value] || '';
            }

            function showNotification(message, type = 'success') {
                const color = type === 'success' ? '#1F9166' : '#e74c3c';
                
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${color};
                    color: white;
                    padding: 12px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 1001;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    animation: slideIn 0.3s;
                `;
                
                notification.innerHTML = `
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    <span>${message}</span>
                `;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s';
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            }

            // Agregar estilos de animación
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
                .message {
                    padding: 20px;
                    border-radius: 10px;
                    margin-bottom: 20px;
                }
                .message.success {
                    background: #d4edda;
                    border: 1px solid #c3e6cb;
                    color: #155724;
                }
                .message.error {
                    background: #f8d7da;
                    border: 1px solid #f5c6cb;
                    color: #721c24;
                }
            `;
            document.head.appendChild(style);

            // ============================================
            // VISTA DE CLIENTE
            // ============================================
            
            // Elementos del DOM para cliente
            const clienteView = document.getElementById('clienteView');
            const formContainer = document.getElementById('formContainer');
            const myReports = document.getElementById('myReports');
            const viewMyReportsBtn = document.getElementById('viewMyReports');
            const backToFormBtn = document.getElementById('backToForm');
            const sendReportBtn = document.getElementById('sendReport');
            const confirmationModal = document.getElementById('confirmationModal');
            const closeModalBtn = document.getElementById('closeModal');
            const closeConfirmationModal = document.getElementById('closeConfirmationModal');
            const viewReportBtn = document.getElementById('viewReport');
            const reportsList = document.getElementById('reportsList');
            const emptyReports = document.getElementById('emptyReports');
            const successMessage = document.getElementById('successMessage');
            const urgencyButtons = document.querySelectorAll('.urgency-btn');
            const systemPartSelect = document.getElementById('systemPart');
            const problemDescription = document.getElementById('problemDescription');
            const reportCodeElement = document.getElementById('reportCode');

            // Variables para cliente
            let selectedUrgency = 'media';
            let lastReportId = null;

            // Inicializar botones de urgencia
            urgencyButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    urgencyButtons.forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedUrgency = this.getAttribute('data-urgency');
                });
            });

            // Seleccionar urgencia media por defecto
            document.querySelector('.urgency-btn.media').classList.add('selected');

            // Ver mis reportes
            viewMyReportsBtn.addEventListener('click', function() {
                formContainer.style.display = 'none';
                myReports.style.display = 'block';
                loadUserReports();
            });

            // Volver al formulario
            backToFormBtn.addEventListener('click', function() {
                myReports.style.display = 'none';
                formContainer.style.display = 'block';
                successMessage.style.display = 'none';
            });

            // Enviar reporte (CLIENTE)
            sendReportBtn.addEventListener('click', function() {
                const description = problemDescription.value.trim();
                const systemPart = systemPartSelect.value;
                
                if (!description) {
                    showError('Por favor describe tu problema');
                    return;
                }
                
                if (!systemPart) {
                    showError('Por favor selecciona en qué parte del sistema ocurre');
                    return;
                }
                
                const originalText = sendReportBtn.innerHTML;
                sendReportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                sendReportBtn.disabled = true;
                
                // Preparar datos para enviar al servidor
                const payload = {
                    descripcion: description,
                    urgencia: selectedUrgency,
                    modulo_sistema: systemPartSelect.selectedOptions[0] ? systemPartSelect.selectedOptions[0].text : systemPart
                };
                
                console.log('Enviando reporte:', payload);
                
                // Enviar al servidor
                fetch('../../../api/add_incidencia.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Respuesta del servidor:', data);
                    
                    if (data.success) {
                        // Limpiar formulario
                        problemDescription.value = '';
                        systemPartSelect.value = '';
                        
                        // Mostrar código de reporte
                        const codigo = data.incidencia?.codigo_incidencia || 'INC-000';
                        reportCodeElement.textContent = `#${codigo}`;
                        confirmationModal.classList.add('active');
                        
                        // Mostrar notificación de éxito
                        showNotification(`¡Reporte creado exitosamente! Código: ${codigo}`);
                        
                        // Recargar la página para ver cambios
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                        
                    } else {
                        throw new Error(data.error || 'Error al crear la incidencia');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Error al enviar reporte: ' + error.message);
                })
                .finally(() => {
                    sendReportBtn.innerHTML = originalText;
                    sendReportBtn.disabled = false;
                });
            });

            function getResponseTime(urgency) {
                if (urgency === 'alta') return '2-4 horas';
                if (urgency === 'media') return '24 horas';
                return '48 horas';
            }

            // Cerrar modal de confirmación
            closeModalBtn.addEventListener('click', () => {
                confirmationModal.classList.remove('active');
            });

            closeConfirmationModal.addEventListener('click', () => {
                confirmationModal.classList.remove('active');
            });

            // Ver reporte desde modal
            viewReportBtn.addEventListener('click', function() {
                confirmationModal.classList.remove('active');
                formContainer.style.display = 'none';
                myReports.style.display = 'block';
                loadUserReports();
            });

            // Cerrar modal al hacer clic fuera
            confirmationModal.addEventListener('click', (e) => {
                if (e.target === confirmationModal) {
                    confirmationModal.classList.remove('active');
                }
            });

            // Cargar reportes del usuario
            function loadUserReports() {
                reportsList.innerHTML = '';
                
                if (userReports.length === 0) {
                    emptyReports.style.display = 'block';
                    return;
                }
                
                emptyReports.style.display = 'none';
                
                userReports.forEach(report => {
                    const reportElement = document.createElement('div');
                    reportElement.className = `report-item ${report.status}`;
                    reportElement.setAttribute('data-id', report.id);
                    
                    let urgencyIcon = 'fa-info-circle';
                    if (report.urgency === 'alta') urgencyIcon = 'fa-exclamation-triangle';
                    if (report.urgency === 'media') urgencyIcon = 'fa-exclamation-circle';
                    
                    let statusText = 'Pendiente';
                    if (report.status === 'proceso') statusText = 'En proceso';
                    if (report.status === 'resuelto') statusText = 'Resuelto';
                    
                    // Formatear fecha
                    let fecha = report.date;
                    if (report.date && report.date.includes('T')) {
                        const fechaObj = new Date(report.date);
                        fecha = fechaObj.toLocaleDateString('es-ES');
                    }
                    
                    reportElement.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                            <div style="flex: 1; min-width: 200px;">
                                <h3 style="color: #2c3e50; font-size: 1.1rem; margin-bottom: 5px;">
                                    <i class="fas ${urgencyIcon}"></i> ${report.description.substring(0, 60)}${report.description.length > 60 ? '...' : ''}
                                </h3>
                                <div style="color: #6c757d; font-size: 0.9rem;">
                                    ${fecha} • ${report.systemPart || 'Sistema'}
                                </div>
                            </div>
                            <div class="status-${report.status}" style="padding: 6px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                ${statusText}
                            </div>
                        </div>
                        ${report.solution ? `
                            <div style="margin-top: 15px; padding: 10px; background: white; border-radius: 8px; font-size: 0.9rem;">
                                <strong style="color: #1F9166;">Respuesta:</strong> ${report.solution}
                            </div>
                        ` : ''}
                    `;
                    
                    reportsList.appendChild(reportElement);
                });
            }

            function showError(message) {
                let errorMsg = document.querySelector('.message.error');
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.className = 'message error';
                    formContainer.insertBefore(errorMsg, formContainer.firstChild);
                }
                
                errorMsg.innerHTML = `<strong>Error:</strong> ${message}`;
                errorMsg.style.display = 'block';
                
                setTimeout(() => {
                    errorMsg.style.display = 'none';
                }, 5000);
            }

            // ============================================
            // VISTA DE OPERADOR
            // ============================================
            
            // Elementos del DOM para operador
            const operadorView = document.getElementById('operadorView');
            const operadorIncidentsTable = document.getElementById('operadorIncidentsTable');
            const resolveModal = document.getElementById('resolveModal');
            const closeResolveModal = document.getElementById('closeResolveModal');
            const cancelResolve = document.getElementById('cancelResolve');
            const confirmResolve = document.getElementById('confirmResolve');
            const resolveIncidentId = document.getElementById('resolveIncidentId');
            const resolveIncidentDesc = document.getElementById('resolveIncidentDesc');
            const solutionNotes = document.getElementById('solutionNotes');
            const totalAsignadas = document.getElementById('totalAsignadas');
            const enProcesoOp = document.getElementById('enProcesoOp');
            const resueltasOp = document.getElementById('resueltasOp');

            // Variables para operador
            let currentIncidentId = null;
            let currentIncidentDbId = null;

            // ============================================
            // FUNCIÓN PRINCIPAL CORREGIDA PARA OPERADOR
            // ============================================
            function renderOperadorTable() {
                console.log('=== RENDERIZANDO TABLA OPERADOR ===');
                console.log('ID operador actual:', currentUserInfo.id);
                console.log('Nombre operador actual:', currentUserInfo.nombre);
                console.log('Datos de incidencias desde PHP:', incidenciasDataFromPHP);
                
                operadorIncidentsTable.innerHTML = '';
                
                // Filtrar incidencias asignadas al operador actual (usando datos REALES de PHP)
                const misIncidencias = incidenciasDataFromPHP.filter(incidencia => {
                    // Verificar si está asignada al operador actual por ID
                    if (incidencia.asignado_id && currentUserInfo.id && 
                        parseInt(incidencia.asignado_id) === parseInt(currentUserInfo.id)) {
                        console.log('Encontrada por ID:', incidencia.id, 'asignado_id:', incidencia.asignado_id);
                        return true;
                    }
                    
                    // Verificar por nombre (backup)
                    if (incidencia.asignado && currentUserInfo.nombre && 
                        incidencia.asignado.toLowerCase().includes(currentUserInfo.nombre.toLowerCase())) {
                        console.log('Encontrada por nombre:', incidencia.id, 'asignado:', incidencia.asignado);
                        return true;
                    }
                    
                    return false;
                });
                
                console.log('Incidencias filtradas para operador:', misIncidencias);
                
                // Actualizar estadísticas
                const total = misIncidencias.length;
                const enProceso = misIncidencias.filter(i => i.estado === 'proceso' || i.estado === 'en_proceso').length;
                const resueltas = misIncidencias.filter(i => i.estado === 'resuelto').length;
                
                totalAsignadas.textContent = total;
                enProcesoOp.textContent = enProceso;
                resueltasOp.textContent = resueltas;
                
                if (misIncidencias.length === 0) {
                    operadorIncidentsTable.innerHTML = `
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #6c757d;">
                                <i class="fas fa-inbox" style="font-size: 2rem; opacity: 0.5; margin-bottom: 10px;"></i><br>
                                No tienes incidencias asignadas<br>
                                <small style="font-size: 0.8rem; margin-top: 10px; display: block;">
                                    ID Operador: ${currentUserInfo.id || 'No disponible'}<br>
                                    Nombre: ${currentUserInfo.nombre || 'No disponible'}<br>
                                    Total incidencias en sistema: ${incidenciasDataFromPHP.length}
                                </small>
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                // Renderizar cada incidencia
                misIncidencias.forEach(incidencia => {
                    const row = document.createElement('tr');
                    
                    const estadoClass = `status-${incidencia.estado}`;
                    const urgenciaClass = `urgency-${incidencia.urgencia}`;
                    
                    const estadoText = getStatusText(incidencia.estado);
                    const urgenciaText = getUrgencyText(incidencia.urgencia);
                    
                    // Formatear fecha
                    let fechaMostrar = incidencia.fecha;
                    if (incidencia.fecha && incidencia.fecha.includes('T')) {
                        const fechaObj = new Date(incidencia.fecha);
                        fechaMostrar = fechaObj.toLocaleDateString('es-ES');
                    }
                    
                    row.innerHTML = `
                        <td><strong>${incidencia.id}</strong></td>
                        <td>${incidencia.descripcion}</td>
                        <td>${incidencia.usuario}</td>
                        <td><span class="urgency-badge ${urgenciaClass}">${urgenciaText}</span></td>
                        <td><span class="status-badge ${estadoClass}">${estadoText}</span></td>
                        <td>${fechaMostrar}</td>
                        <td>
                            ${(incidencia.estado === 'proceso' || incidencia.estado === 'en_proceso') ? 
                                `<button class="btn btn-primary btn-sm resolve-btn" 
                                        data-id="${incidencia.id}" 
                                        data-dbid="${incidencia.db_id || ''}"
                                        style="padding: 8px 15px; font-size: 0.85rem;">
                                    <i class="fas fa-check"></i> Resolver
                                </button>` : 
                                `<button class="btn btn-primary btn-sm" disabled 
                                        style="padding: 8px 15px; font-size: 0.85rem;">
                                    <i class="fas fa-check-circle"></i> ${incidencia.estado === 'resuelto' ? 'Resuelta' : 'Pendiente'}
                                </button>`
                            }
                        </td>
                    `;
                    
                    operadorIncidentsTable.appendChild(row);
                });
                
                // Agregar event listeners a los botones de resolver
                document.querySelectorAll('.resolve-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const dbId = this.getAttribute('data-dbid');
                        openResolveModal(id, dbId);
                    });
                });
            }

            // Abrir modal para marcar como resuelto
            function openResolveModal(id, dbId) {
                currentIncidentId = id;
                currentIncidentDbId = dbId;
                
                // Buscar la incidencia en los datos de PHP
                const incidencia = incidenciasDataFromPHP.find(i => i.id === id);
                
                if (!incidencia) {
                    showNotification('No se encontró la incidencia en el sistema', 'error');
                    return;
                }
                
                console.log('Abriendo modal para resolver:', {
                    id: id,
                    dbId: currentIncidentDbId,
                    incidencia: incidencia
                });
                
                resolveIncidentId.textContent = `${id} - ${incidencia.usuario || 'Usuario'}`;
                resolveIncidentDesc.textContent = incidencia.descripcion;
                solutionNotes.value = incidencia.notas || '';
                
                resolveModal.classList.add('active');
            }

            // Control del modal de resolución
            closeResolveModal.addEventListener('click', () => {
                resolveModal.classList.remove('active');
            });

            cancelResolve.addEventListener('click', () => {
                resolveModal.classList.remove('active');
            });

            resolveModal.addEventListener('click', (e) => {
                if (e.target === resolveModal) {
                    resolveModal.classList.remove('active');
                }
            });

            // Confirmar resolución
            confirmResolve.addEventListener('click', () => {
                if (!currentIncidentId || !currentIncidentDbId) {
                    showNotification('Error: No se pudo identificar la incidencia en la base de datos', 'error');
                    return;
                }
                
                const notas = solutionNotes.value.trim();
                
                if (!notas) {
                    showNotification('Por favor describe cómo resolviste el problema', 'error');
                    return;
                }
                
                // Deshabilitar botón mientras procesa
                const originalText = confirmResolve.innerHTML;
                confirmResolve.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                confirmResolve.disabled = true;
                
                // Enviar al servidor
                const payload = {
                    id: currentIncidentDbId,
                    notas_solucion: notas
                };
                
                console.log('Enviando resolución:', payload);
                
                fetch('../../../api/resolve_incidencia.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Respuesta resolución:', data);
                    
                    if (data.success) {
                        showNotification(`¡Incidencia ${currentIncidentId} marcada como resuelta!`);
                        
                        // Recargar la página para ver cambios
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                        
                    } else {
                        throw new Error(data.error || 'Error al resolver la incidencia');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error al resolver: ' + error.message, 'error');
                })
                .finally(() => {
                    confirmResolve.innerHTML = originalText;
                    confirmResolve.disabled = false;
                    resolveModal.classList.remove('active');
                });
            });

       // ============================================
// VISTA DE ADMINISTRADOR
// ============================================

// Elementos del DOM para administrador
const administradorView = document.getElementById('administradorView');
const adminIncidentsTable = document.getElementById('adminIncidentsTable');
const searchInput = document.getElementById('searchInput');
const filterStatus = document.getElementById('filterStatus');
const filterUrgency = document.getElementById('filterUrgency');
const assignModal = document.getElementById('assignModal');
const closeAssignModal = document.getElementById('closeAssignModal');
const cancelAssign = document.getElementById('cancelAssign');
const confirmAssign = document.getElementById('confirmAssign');
const assignToSelect = document.getElementById('assignTo');
const incidentIdText = document.getElementById('incidentIdText');
const incidentDescText = document.getElementById('incidentDescText');
const totalIncidencias = document.getElementById('totalIncidencias');
const pendientesElement = document.getElementById('pendientes');
const enProcesoAdmin = document.getElementById('enProcesoAdmin');
const resueltasAdmin = document.getElementById('resueltasAdmin');

// Variables para administrador
let currentAdminIncidentId = null;
let currentAdminIncidentDbId = null;

// Poblar lista de operadores desde el servidor
try {
    assignToSelect.innerHTML = '<option value="">Seleccionar operador...</option>';
    for (const [opId, opName] of Object.entries(operadores)) {
        const opt = document.createElement('option');
        opt.value = opId;
        opt.textContent = opName;
        assignToSelect.appendChild(opt);
    }
} catch (e) {
    console.warn('No se pudo poblar lista de operadores:', e);
}

// Renderizar tabla del administrador
function renderAdminTable() {
    adminIncidentsTable.innerHTML = '';
    
    // Aplicar filtros
    let filtered = incidenciasDataFromPHP.filter(incidencia => {
        // Filtro de búsqueda
        if (searchInput.value) {
            const search = searchInput.value.toLowerCase();
            if (!incidencia.id.toLowerCase().includes(search) &&
                !incidencia.descripcion.toLowerCase().includes(search) &&
                !incidencia.usuario.toLowerCase().includes(search) &&
                !incidencia.asignado.toLowerCase().includes(search)) {
                return false;
            }
        }
        
        // Filtro de estado
        if (filterStatus.value && incidencia.estado !== filterStatus.value) {
            return false;
        }
        
        // Filtro de urgencia
        if (filterUrgency.value && incidencia.urgencia !== filterUrgency.value) {
            return false;
        }
        
        return true;
    });
    
    // Actualizar estadísticas
    const total = incidenciasDataFromPHP.length;
    const pendientes = incidenciasDataFromPHP.filter(i => i.estado === 'pendiente').length;
    const enProceso = incidenciasDataFromPHP.filter(i => i.estado === 'proceso' || i.estado === 'en_proceso').length;
    const resueltas = incidenciasDataFromPHP.filter(i => i.estado === 'resuelto').length;
    
    totalIncidencias.textContent = total;
    pendientesElement.textContent = pendientes;
    enProcesoAdmin.textContent = enProceso;
    resueltasAdmin.textContent = resueltas;
    
    if (filtered.length === 0) {
        adminIncidentsTable.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: #6c757d;">
                    <i class="fas fa-search" style="font-size: 2rem; opacity: 0.5; margin-bottom: 10px;"></i><br>
                    No se encontraron incidencias
                </td>
            </tr>
        `;
        return;
    }
    
    // Renderizar cada incidencia
    filtered.forEach(incidencia => {
        const row = document.createElement('tr');
        
        const estadoClass = `status-${incidencia.estado}`;
        const urgenciaClass = `urgency-${incidencia.urgencia}`;
        
        const estadoText = getStatusText(incidencia.estado);
        const urgenciaText = getUrgencyText(incidencia.urgencia);
        
        // Formatear fecha
        let fechaMostrar = incidencia.fecha;
        if (incidencia.fecha && incidencia.fecha.includes('T')) {
            const fechaObj = new Date(incidencia.fecha);
            fechaMostrar = fechaObj.toLocaleDateString('es-ES');
        }
        
        // Verificar si se puede asignar (pendientes sin asignar)
        const puedeAsignar = incidencia.estado === 'pendiente' && (!incidencia.asignado || incidencia.asignado.trim() === '');
        
        row.innerHTML = `
            <td><strong>${incidencia.id}</strong></td>
            <td>${incidencia.descripcion.substring(0, 80)}${incidencia.descripcion.length > 80 ? '...' : ''}</td>
            <td>${incidencia.usuario}</td>
            <td><span class="urgency-badge ${urgenciaClass}">${urgenciaText}</span></td>
            <td><span class="status-badge ${estadoClass}">${estadoText}</span></td>
            <td>${fechaMostrar}</td>
            <td>${incidencia.asignado || '<span style="color: #6c757d; font-style: italic;">Sin asignar</span>'}</td>
            <td>
                ${puedeAsignar ? 
                    `<button class="btn btn-primary btn-sm assign-btn-admin" 
                            data-id="${incidencia.id}" 
                            data-dbid="${incidencia.db_id}"
                            style="padding: 8px 15px; font-size: 0.85rem;">
                        <i class="fas fa-user-check"></i> Asignar
                    </button>` : 
                    `<button class="btn btn-primary btn-sm" disabled 
                            style="padding: 8px 15px; font-size: 0.85rem; background: #6c757d; border-color: #6c757d;">
                        <i class="fas fa-check"></i> ${incidencia.estado === 'resuelto' ? 'Resuelta' : 'Asignada'}
                    </button>`
                }
            </td>
        `;
        
        adminIncidentsTable.appendChild(row);
    });
    
    // Agregar event listeners a los botones de asignar
    setTimeout(() => {
        document.querySelectorAll('.assign-btn-admin').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const dbId = this.getAttribute('data-dbid');
                console.log('Botón asignar clickeado:', { id, dbId });
                openAssignModalAdmin(id, dbId);
            });
        });
    }, 100);
}

// Abrir modal de asignación desde administrador - CORREGIDO
function openAssignModalAdmin(id, dbId) {
    console.log('Abriendo modal para asignar incidencia:', { id, dbId });
    
    currentAdminIncidentId = id;
    currentAdminIncidentDbId = dbId;
    
    // Buscar la incidencia en los datos REALES de PHP
    const incidencia = incidenciasDataFromPHP.find(i => i.id === id);
    
    if (!incidencia) {
        console.error('No se encontró la incidencia:', id);
        showNotification('No se encontró la incidencia', 'error');
        return;
    }
    
    incidentIdText.textContent = `${id} - ${incidencia.usuario || 'Usuario'}`;
    incidentDescText.textContent = incidencia.descripcion.substring(0, 100) + (incidencia.descripcion.length > 100 ? '...' : '');
    
    // Limpiar y establecer valor del select
    assignToSelect.value = '';
    
    assignModal.classList.add('active');
    console.log('Modal de asignación abierto');
}

// Control del modal de asignación
closeAssignModal.addEventListener('click', () => {
    assignModal.classList.remove('active');
});

cancelAssign.addEventListener('click', () => {
    assignModal.classList.remove('active');
});

assignModal.addEventListener('click', (e) => {
    if (e.target === assignModal) {
        assignModal.classList.remove('active');
    }
});

// Confirmar asignación (persistir en servidor) - VERSIÓN SIMPLIFICADA
confirmAssign.addEventListener('click', () => {
    if (!currentAdminIncidentId || !currentAdminIncidentDbId) {
        showNotification('Error: No se pudo identificar la incidencia', 'error');
        return;
    }

    const operadorValue = assignToSelect.value;

    if (!operadorValue) {
        showNotification('Por favor selecciona un operador', 'error');
        return;
    }

    const operadorNombre = getOperatorName(operadorValue);

    // Deshabilitar botón mientras procesa
    confirmAssign.disabled = true;
    const originalText = confirmAssign.innerHTML;
    confirmAssign.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Asignando...';

    // Preparar payload
    const payload = {
        id: currentAdminIncidentDbId,
        operador_id: operadorValue
    };

    console.log('Enviando asignación:', payload);

    // Hacer la petición
    fetch('../../../api/assign_incidencia.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta del servidor:', data);
        
        if (data.success) {
            showNotification(`¡Incidencia ${currentAdminIncidentId} asignada a ${operadorNombre}!`, 'success');
            
            // Cerrar modal
            assignModal.classList.remove('active');
            
            // Recargar la página para ver cambios
            setTimeout(() => {
                location.reload();
            }, 1500);
            
        } else {
            throw new Error(data.error || 'Error al asignar la incidencia');
        }
    })
    .catch(err => {
        console.error('Error en asignación:', err);
        showNotification('Error al asignar: ' + err.message, 'error');
    })
    .finally(() => {
        confirmAssign.disabled = false;
        confirmAssign.innerHTML = originalText;
    });
});

// Event Listeners para filtros del administrador
searchInput.addEventListener('input', renderAdminTable);
filterStatus.addEventListener('change', renderAdminTable);
filterUrgency.addEventListener('change', renderAdminTable);

            // ============================================
            // CONTROL DE ROLES
            // ============================================
            
            // Elementos para cambio de rol
            const roleButtons = document.querySelectorAll('.role-btn');
            const currentRole = document.getElementById('currentRole');
            const currentUser = document.getElementById('currentUser');

            // Cambiar rol
            roleButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const role = this.getAttribute('data-role');
                    
                    // Actualizar botones activos
                    roleButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Actualizar badge de rol
                    currentRole.textContent = role.charAt(0).toUpperCase() + role.slice(1);
                    currentRole.className = `role-badge role-${role}`;
                    
                    // Actualizar nombre de usuario según rol
                    if (role === 'cliente') {
                        currentUser.textContent = 'Usuario Cliente';
                    } else if (role === 'operador') {
                        currentUser.textContent = 'María González';
                    } else if (role === 'administrador') {
                        currentUser.textContent = 'Administrador';
                    }
                    
                    // Ocultar todas las vistas
                    document.querySelectorAll('.view').forEach(view => {
                        view.classList.remove('active');
                    });
                    
                    // Mostrar la vista correspondiente
                    document.getElementById(`${role}View`).classList.add('active');
                    
                    // Actualizar las tablas según la vista
                    if (role === 'cliente') {
                        loadUserReports();
                    } else if (role === 'operador') {
                        renderOperadorTable();
                    } else if (role === 'administrador') {
                        renderAdminTable();
                    }
                    
                    // Mostrar notificación
                    showNotification(`Cambiado a rol: ${role}`);
                });
            });

            // ============================================
            // INICIALIZACIÓN
            // ============================================
            
            // Inicializar según el rol actual (desde PHP)
            
            // Activar el botón correspondiente al rol actual (si existe)
            const roleBtnEl = document.querySelector(`.role-btn[data-role="${currentRoleFromPHP}"]`);
            if (roleBtnEl) roleBtnEl.classList.add('active');

            // Actualizar badge de rol (si el elemento existe)
            if (currentRole) {
                currentRole.textContent = currentRoleFromPHP.charAt(0).toUpperCase() + currentRoleFromPHP.slice(1);
                currentRole.className = `role-badge role-${currentRoleFromPHP}`;
            }

            // Actualizar nombre de usuario según rol (si el elemento existe)
            if (currentUser) {
                if (currentRoleFromPHP === 'cliente') {
                    currentUser.textContent = 'Usuario Cliente';
                } else if (currentRoleFromPHP === 'operador') {
                    currentUser.textContent = 'María González';
                } else if (currentRoleFromPHP === 'administrador') {
                    currentUser.textContent = 'Administrador';
                }
            }
            
            // Inicializar las vistas según el rol
            if (currentRoleFromPHP === 'cliente') {
                loadUserReports();
            } else if (currentRoleFromPHP === 'operador') {
                renderOperadorTable();
            } else if (currentRoleFromPHP === 'administrador') {
                renderAdminTable();
            }

           
        });
    </script>
</body>
</html>