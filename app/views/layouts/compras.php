<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: /inversiones-rojas/login.php');
    exit();
}

// Cargar constantes y configuración de la aplicación
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';
// Obtener el nombre del usuario logueado
$nombre_usuario = $_SESSION['user_name'] ?? 'Sistema';

// Si no está en sesión, obtenerlo de la base de datos
if (!isset($_SESSION['user_name']) || $nombre_usuario == 'Sistema') {
    require_once __DIR__ . '/../../models/database.php';
    $pdo_temp = Database::getInstance();
    $stmt = $pdo_temp->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch();
    if ($usuario && !empty($usuario['nombre_completo'])) {
        $_SESSION['user_name'] = $usuario['nombre_completo'];
        $nombre_usuario = $usuario['nombre_completo'];
    }
}

// Incluir la conexión a la base de datos
require_once __DIR__ . '/../../models/database.php';
$pdo = Database::getInstance();

// Obtener proveedores
$stmt = $pdo->query("
    SELECT id, razon_social, rif, persona_contacto, 
           telefono_principal, telefono_alternativo,
           email, direccion 
    FROM proveedores 
    WHERE estado = true 
    ORDER BY razon_social
");
$proveedores = $stmt->fetchAll();

// Obtener estados de compra únicos
$stmt = $pdo->query("SELECT DISTINCT estado_compra FROM compras ORDER BY estado_compra");
$estados_compra = $stmt->fetchAll();

// Obtener compras recientes con más detalles
$stmt = $pdo->query("
    SELECT c.*, p.razon_social as proveedor_nombre, 
           u.nombre_completo as comprador,
           (SELECT COUNT(*) FROM detalle_compras dc WHERE dc.compra_id = c.id) as productos_count,
           (SELECT SUM(cantidad) FROM detalle_compras dc WHERE dc.compra_id = c.id) as total_unidades,
           c.fecha_estimada_entrega,
           c.activa,
           CASE 
               WHEN c.estado_compra = 'PENDIENTE' AND c.fecha_estimada_entrega < CURRENT_DATE THEN 'ATRASADA'
               WHEN c.estado_compra = 'PENDIENTE' AND c.fecha_estimada_entrega = CURRENT_DATE THEN 'HOY'
               WHEN c.estado_compra = 'PENDIENTE' AND c.fecha_estimada_entrega > CURRENT_DATE THEN 'PROXIMA'
               ELSE NULL
           END as alerta_entrega
    FROM compras c
    LEFT JOIN proveedores p ON c.proveedor_id = p.id
    LEFT JOIN usuarios u ON c.usuario_id = u.id
    ORDER BY
        c.created_at DESC,
        c.fecha_estimada_entrega ASC NULLS LAST
");
$compras_recientes = $stmt->fetchAll();

// Estadísticas reales - mes con datos similar a ventas
$stmt = $pdo->query("
    SELECT 
        DATE_TRUNC('month', created_at) as mes,
        TO_CHAR(DATE_TRUNC('month', created_at), 'YYYY-MM') as mes_formato
    FROM compras
    WHERE activa = true
    GROUP BY DATE_TRUNC('month', created_at)
    ORDER BY mes DESC
    LIMIT 1
");
$ultimo_mes = $stmt->fetch();
$mes_seleccionado = $ultimo_mes['mes_formato'] ?? date('Y-m');

$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN DATE(created_at) = CURRENT_DATE THEN total ELSE 0 END), 0) as compras_hoy,
        COUNT(CASE WHEN DATE(created_at) = CURRENT_DATE THEN 1 END) as ordenes_hoy,
        COUNT(CASE WHEN estado_compra = 'PENDIENTE' AND activa = true THEN 1 END) as pendientes_recepcion,
        COUNT(CASE WHEN estado_compra IN ('RECEPCION', 'REVISION') AND activa = true THEN 1 END) as en_recepcion,
        COUNT(CASE WHEN estado_compra = 'COMPLETADA' AND activa = true THEN 1 END) as completadas,
        COUNT(CASE WHEN estado_compra = 'INCOMPLETA' AND activa = true THEN 1 END) as incompletas,
        COUNT(CASE WHEN estado_compra = 'CANCELADA' OR activa = false THEN 1 END) as canceladas,
        COALESCE(SUM(total), 0) as compras_mes,
        COUNT(DISTINCT CASE WHEN activa = true THEN proveedor_id END) as proveedores_activos
    FROM compras
    WHERE DATE_TRUNC('month', created_at) = TO_DATE(:mes, 'YYYY-MM-DD')
");
$stmt->execute(['mes' => $mes_seleccionado . '-01']);
$estadisticas = $stmt->fetch();

// Guardar mes actual seleccionado para posibles filtros o texto de UI
$mes_compras_seleccionado = $mes_seleccionado;

// Gráfico de compras mensuales
$stmt = $pdo->query("
    SELECT 
        DATE_TRUNC('month', created_at) as mes,
        TO_CHAR(DATE_TRUNC('month', created_at), 'Month') as nombre_mes,
        SUM(total) as total_compras
    FROM compras
    WHERE created_at >= CURRENT_DATE - INTERVAL '3 months'
    GROUP BY DATE_TRUNC('month', created_at)
    ORDER BY mes
");
$compras_mensuales = $stmt->fetchAll();

// Compras por proveedor
$stmt = $pdo->query("
    SELECT 
        pr.razon_social as proveedor,
        COUNT(*) as cantidad_ordenes,
        SUM(c.total) as total_compras
    FROM compras c
    JOIN proveedores pr ON c.proveedor_id = pr.id
    WHERE c.created_at >= CURRENT_DATE - INTERVAL '30 days' AND c.activa = true
    GROUP BY pr.razon_social
    ORDER BY total_compras DESC
    LIMIT 5
");
$compras_proveedores = $stmt->fetchAll();

// Información de la empresa para el modal
$empresa_nombre = COMPANY_NAME;
$empresa_rif = COMPANY_RIF;
$empresa_direccion = COMPANY_ADDRESS;
$empresa_telefono = COMPANY_PHONE;

// Datos del comprador (usuario actual)
$comprador_nombre = $nombre_usuario;
$comprador_email = $_SESSION['user_email'] ?? 'gifrank0000@gmail.com';
$comprador_telefono = $empresa_telefono;

// Si el usuario no tiene email, usar uno por defecto
if (empty($comprador_email)) {
    $stmt = $pdo->prepare("SELECT email FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario_email = $stmt->fetchColumn();
    $comprador_email = $usuario_email ?: 'gifrank0000@gmail.com';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras - Inversiones Rojas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/inversiones-rojas/public/js/inv-notifications.js"></script>
    <link rel="stylesheet" href="/inversiones-rojas/public/css/layouts/compras.css">
    <style>
        /* ========== ESTILOS PARA MONEDA DUAL ========== */
        .moneda-usd {
            color: #1F9166;
            font-weight: 600;
            font-size: 1.2rem;
            display: block;
            margin-bottom: 3px;
        }
        
        .moneda-bs {
            color: #6c757d;
            font-size: 0.85rem;
            font-weight: 500;
            display: block;
        }

        /* ========== ESTILOS PARA EL MODAL MEJORADO DE COMPRAS ========== */
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
            width: 95%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            animation: slideUp 0.3s;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal.small-modal {
            max-width: 500px;
        }

        .modal-overlay.registro-modal {
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            inset: 0;
        }

        .modal.registro-modal {
            width: 800px;
            max-width: 98%;
            max-height: 90vh;
            overflow-y: auto;
            margin: auto;
        }

        .modal.registro-modal .modal-header {
            background: #f8f9fa;
            color: #333;
            border-radius: 16px 16px 0 0;
        }

        .modal.registro-modal .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            color: #1f2937;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .modal.registro-modal .form-control:focus {
            outline: none;
            border-color: #1F9166;
            box-shadow: 0 0 0 4px rgba(31, 145, 102, 0.12);
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
            overflow-y: auto;
        }

        .modal.registro-modal .modal-footer {
            justify-content: flex-end;
            gap: 10px;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1F9166;
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .modal-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.4rem;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .modal-body {
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }

        /* Sección de información de empresa */
        .empresa-info {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px dashed #1F9166;
        }

        .empresa-info h3 {
            color: #2c3e50;
            margin: 0 0 10px 0;
            font-size: 1.8rem;
        }

        .empresa-info p {
            margin: 4px 0;
            color: #666;
            font-size: 14px;
        }

        /* Título de la orden */
        .orden-title {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .orden-title h2 {
            color: #2c3e50;
            margin: 0;
            font-size: 1.6rem;
        }

        /* Sección de información del proveedor y comprador */
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .info-section {
                grid-template-columns: 1fr;
            }
        }

        .info-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
        }

        .info-card h3 {
            color: #1F9166;
            margin: 0 0 12px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #f1f1f1;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card h3 i {
            color: #1F9166;
            font-size: 1.1rem;
        }

        .info-row {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 100px;
            font-size: 14px;
        }

        .info-value {
            flex: 1;
            color: #333;
            font-size: 14px;
        }

        /* Botón para agregar proveedor */
        .btn-add-prov {
            padding: 6px 12px;
            background: #1F9166;
            color: #ffffff !important;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn-add-prov:hover {
            background: #146643;
            color: #ffffff !important;
        }

        /* Selectores */
        .select-group {
            margin-bottom: 15px;
        }

        .select-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        .select-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            transition: border-color 0.3s;
            cursor: pointer;
        }

        .select-group select:focus {
            outline: none;
            border-color: #1F9166;
            box-shadow: 0 0 0 2px rgba(31, 145, 102, 0.2);
        }

        /* Búsqueda de productos */
        .product-search {
            margin: 20px 0;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #1F9166;
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 16px;
        }

        /* Tabla de productos disponibles */
        .products-table-container {
            margin: 20px 0;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 6px;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .products-table thead {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 10;
        }

        .products-table th {
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e9ecef;
            background: #f8f9fa;
        }

        .products-table td {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .products-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .product-code {
            font-family: monospace;
            font-size: 12px;
            color: #666;
        }

        .product-name {
            font-weight: 500;
            color: #333;
        }

        .product-price {
            font-weight: 600;
            color: #1F9166;
        }

        .add-product-btn {
            padding: 6px 12px;
            background: #1F9166;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .add-product-btn:hover {
            background: #146643;
        }

        .add-product-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Tabla de productos seleccionados */
        .selected-products {
            margin: 25px 0;
        }

        .selected-products h3 {
            color: #2c3e50;
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .selected-products h3 i {
            color: #1F9166;
        }

        .selected-table-container {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            overflow: hidden;
        }

        .selected-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .selected-table thead {
            background: #f8f9fa;
        }

        .selected-table th {
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e9ecef;
        }

        .selected-table td {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .quantity-btn {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 12px;
        }

        .quantity-btn:hover {
            background: #f8f9fa;
            border-color: #bbb;
        }

        .quantity-input {
            width: 50px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }

        .remove-item {
            background: none;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            font-size: 16px;
            width: 28px;
            height: 28px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .remove-item:hover {
            background: rgba(231, 76, 60, 0.1);
        }

        /* Resumen de la orden */
        .order-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
        }

        .summary-title {
            color: #2c3e50;
            margin: 0 0 15px 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .summary-title i {
            color: #1F9166;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .summary-label {
            font-weight: 600;
            color: #555;
        }

        .summary-value {
            font-weight: 600;
            color: #333;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-top: 2px solid #1F9166;
            margin-top: 10px;
            font-size: 1.1rem;
        }

        .total-label {
            color: #2c3e50;
        }

  
        /* Campos adicionales */
        .additional-fields {
            margin: 20px 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1F9166;
            box-shadow: 0 0 0 2px rgba(31, 145, 102, 0.2);
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        /* Footer del modal */
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
        }

        .modal-footer .btn {
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 15px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
        }

        .btn-cancel:hover {
            background: #e9ecef;
        }

        .btn-create {
            background: #1F9166;
            color: white;
        }

        .btn-create:hover {
            background: #146643;
        }

        .btn-create:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-print {
            background: #6c757d;
            color: white;
        }

        .btn-print:hover {
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
        .report-card {
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
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

        .btn-edit {
            background: #ffc107;
            color: #212529;
        }

        .btn-edit:hover {
            background: #e0a800;
        }

        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #888;
            background: white;
            border-radius: 6px;
            border: 2px dashed #ddd;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }

        .empty-state p {
            font-size: 16px;
            margin: 5px 0;
        }

        /* ========== TABLA REDISEÑADA - MÁS ESPACIOSA Y SIMÉTRICA ========== */
        .compras-table {
            margin-top: 20px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table-header {
            display: grid;
            grid-template-columns: 1.4fr 2fr 1fr 0.6fr 0.6fr 1.1fr 1.2fr 1.1fr 1fr;
            gap: 15px;
            align-items: center;
            padding: 16px 20px;
            background: #f8f9fa;
            font-weight: 700;
            color: #2c3e50;
            font-size: 14px;
            letter-spacing: 0.3px;
        }

        .table-row {
            display: grid;
            grid-template-columns: 1.4fr 2fr 1fr 0.6fr 0.6fr 1.1fr 1.2fr 1.1fr 1fr;
            gap: 15px;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid #edf2f7;
            transition: background-color 0.2s;
        }

        .table-row:hover {
            background-color: #f8fafc;
        }

        /* Columna de código de orden */
        .order-code-block {
            display: flex;
            flex-direction: column;
        }

        .order-code {
            font-weight: 700;
            font-size: 14px;
        }

        .order-date {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
        }

        /* Columna de proveedor - ahora tiene espacio suficiente */
        .proveedor-cell {
            font-weight: 500;
            color: #334155;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Valores numéricos */
        .total-value {
            font-weight: 700;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
        }
        
        .total-value .moneda-usd {
            color: #1F9166;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .total-value .moneda-bs {
            color: #6c757d;
            font-size: 0.8rem;
        }

        /* Badges de estado - para el proceso de la orden */
        .status-badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 100px;
        }

        .status-pendiente { 
            background: #fff3cd; 
            color: #856404; 
            border: 1px solid #ffeeba;
        }

        .status-recepcion { 
            background: #cce5ff; 
            color: #004085; 
            border: 1px solid #b8daff;
        }

        .status-completada { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }

        .status-incompleta { 
            background: #fff3cd; 
            color: #856404; 
            border: 1px solid #ffeeba;
        }

        .status-cancelada { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }

        /* Alertas - para indicar si está ACTIVA o INHABILITADA */
        .alerta-activa {
            color: #10b981;
            font-weight: 600;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .alerta-inhabilitada {
            color: #ef4444;
            font-weight: 600;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .alerta-atrasada {
            color: #ef4444;
            font-weight: 600;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .alerta-hoy {
            color: #f97316;
            font-weight: 600;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .alerta-proxima {
            color: #10b981;
            font-weight: 600;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Botones de acción - más grandes y visibles */
        .actions-cell {
            display: flex;
            justify-content: flex-start;
            gap: 8px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            color: #fff;
            font-size: 14px;
            transition: transform 0.1s ease, box-shadow 0.1s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }

        .action-btn i {
            font-size: 16px;
        }

        .action-btn.view { 
            background: #1F9166; 
        }

        .action-btn.status { 
            background: #f59e0b; 
        }

        .action-btn.disable { 
            background: #ef4444; 
        }

        .action-btn.enable { 
            background: #10b981; 
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Responsive - Tablet (≤1200px) - ajusta proporciones */
        @media (max-width: 1200px) {
            .table-header,
            .table-row {
                grid-template-columns: 1.2fr 1.6fr 0.8fr 0.5fr 0.5fr 0.9fr 1fr 0.9fr 0.8fr;
                gap: 6px;
                padding: 10px 12px;
                font-size: 0.8rem;
            }

            .action-btn {
                width: 28px;
                height: 28px;
                font-size: 11px;
            }
        }

        /* Responsive - Tablet pequeña (≤1024px) - mantiene tabla, ajusta más */
        @media (max-width: 1024px) {
            .table-header,
            .table-row {
                grid-template-columns: 1.1fr 1.5fr 0.7fr 0.4fr 0.4fr 0.8fr 0.9fr 0.8fr 0.7fr;
                gap: 4px;
                padding: 8px 10px;
                font-size: 0.75rem;
            }

            .action-btn {
                width: 26px;
                height: 26px;
                font-size: 10px;
            }
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 11000;
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

        /* Toast notifications */
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
            z-index: 12000;
            animation: slideIn 0.3s ease;
            max-width: 350px;
        }

        .toast.success {
            border-left: 4px solid #1F9166;
        }

        .toast.error {
            border-left: 4px solid #e74c3c;
        }

        .toast.warning {
            border-left: 4px solid #ffc107;
        }

        .toast.info {
            border-left: 4px solid #17a2b8;
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

        .toast-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #999;
            margin-left: 10px;
        }

        .toast-close:hover {
            color: #333;
        }

        /* Estilos para el modal de detalles */
        .detail-row {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
            min-width: 120px;
        }

        .detail-value {
            color: #333;
            flex: 1;
        }

        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .productos-table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #555;
        }

        .productos-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }

        .productos-table tfoot td {
            background: #f8f9fa;
            font-weight: 600;
        }

        .total-row {
            font-size: 1.2rem;
            color: #1F9166;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <!-- Overlay de carga -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="admin-content">
        <!-- Stats Cards con datos reales -->
        <div class="compras-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <?php 
                    $precios_compras = formatearMonedaDual($estadisticas['compras_mes'] ?? 0);
                    ?>
                    <h3>
                        <span class="moneda-usd"><?php echo $precios_compras['usd']; ?></span>
                        <span class="moneda-bs"><?php echo $precios_compras['bs']; ?></span>
                    </h3>
                    <p>Compras del Mes</p>
                    <div class="stat-trend">
                        <i class="fas fa-chart-line"></i>
                        Mes Actual
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $estadisticas['pendientes_recepcion']; ?></h3>
                    <p>Pendientes</p>
                    <div class="stat-trend">
                        <i class="fas fa-clock"></i>
                        Por recibir
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $estadisticas['en_recepcion']; ?></h3>
                    <p>En Recepción</p>
                    <div class="stat-trend">
                        <i class="fas fa-search"></i>
                        Verificando
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $estadisticas['incompletas']; ?></h3>
                    <p>Incompletas</p>
                    <div class="stat-trend trend-down">
                        <i class="fas fa-arrow-down"></i>
                        Faltan productos
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <!-- Gráfica de Compras Mensuales -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Compras Mensuales</h3>
                    <div class="chart-actions">
                        <select class="chart-filter" id="chartMonthFilter">
                            <option value="3">Últimos 3 meses</option>
                            <option value="6">Últimos 6 meses</option>
                            <option value="12">Último año</option>
                        </select>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="comprasChart"></canvas>
                </div>
            </div>

            <!-- Gráfica de Compras por Proveedor -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Compras por Proveedor</h3>
                    <div class="chart-actions">
                        <select class="chart-filter" id="chartProveedorFilter">
                            <option value="30">Últimos 30 días</option>
                            <option value="90">Últimos 90 días</option>
                            <option value="365">Último año</option>
                        </select>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="proveedoresChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Barra de búsqueda y filtros -->
        <div class="search-filters">
            <form id="comprasSearchForm" class="search-box" onsubmit="event.preventDefault(); searchCompras();" autocomplete="off" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <input type="search" id="comprasSearchInput" placeholder="Buscar órdenes por código, proveedor..." style="flex:1; min-width:200px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" />
                
                <select id="dateFilter" class="filter-select" style="min-width:160px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="">Todos los días</option>
                    <option value="today">Hoy</option>
                    <option value="yesterday">Ayer</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mes</option>
                    <option value="last_month">Mes anterior</option>
                    <option value="custom">Rango personalizado</option>
                </select>

                <select id="statusFilter" class="filter-select">
                    <option value="" selected>Todos los estados</option>
                    <option value="PENDIENTE">PENDIENTE</option>
                    <option value="REVISION">REVISION</option>
                    <option value="COMPLETADA">COMPLETADA</option>
                    <option value="INCOMPLETA">INCOMPLETA</option>
                    <option value="CANCELADA">CANCELADA</option>
                </select>

                <select id="proveedorFilter" class="filter-select">
                    <option value="">Todos los proveedores</option>
                    <?php foreach ($proveedores as $proveedor): ?>
                    <option value="<?php echo $proveedor['id']; ?>">
                        <?php echo htmlspecialchars($proveedor['razon_social']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <button type="button" class="btn btn-primary" onclick="searchCompras()" style="padding: 10px 20px;">
                    <i class="fas fa-search"></i> Buscar
                </button>

                <button type="button" class="btn btn-outline" onclick="clearFilters()" style="padding: 10px 20px;">
                    <i class="fas fa-times"></i> Limpiar
                </button>
            </form>
            
            <!-- Rango de fechas personalizado -->
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
        </div>

        <!-- Action Buttons -->
        <div class="compras-actions">
            <div class="action-buttons">
                <button class="btn btn-primary" id="openCompraModalBtn">
                    <i class="fas fa-file-purchase"></i>
                    Nueva Orden Compra
                </button>
                <button class="btn btn-report" id="openReportsModalBtn">
                    <i class="fas fa-chart-bar"></i>
                    Reportes
                </button>
                <button class="btn btn-secondary" id="viewSuppliersBtn" onclick="showSuppliersModal()">
                    <i class="fas fa-truck"></i>
                    Ver Proveedores
                </button>
            </div>
        </div>

        <!-- Compras Table - responsive con scroll horizontal -->
        <div class="compras-table-wrapper">
        <div class="compras-table">
            <div class="table-header">
                <div>Orden #</div>
                <div>Proveedor</div>
                <div>Entrega</div>
                <div>Prod.</div>
                <div>Unds.</div>
                <div>Total</div>
                <div>Estado</div>
                <div>Alerta</div>
                <div>Acciones</div>
            </div>
            
            <div id="comprasTableBody">
                <?php if (empty($compras_recientes)): ?>
                <div class="empty-table" style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-box-open fa-3x" style="color: #ddd; margin-bottom: 15px;"></i>
                    <p>No hay órdenes de compra registradas</p>
                    <button class="btn btn-primary" onclick="document.getElementById('openCompraModalBtn').click()" style="margin-top: 15px;">
                        <i class="fas fa-plus"></i> Crear primera orden
                    </button>
                </div>
                <?php else: ?>
                    <?php foreach ($compras_recientes as $compra): 
                        // ===== ESTADO (proceso de la orden) =====
                        $estado_class = '';
                        $estado_texto = $compra['estado_compra'];
                        
                        switch(strtolower($compra['estado_compra'])) {
                            case 'pendiente':
                                $estado_class = 'status-pendiente';
                                break;
                            case 'recepcion':
                            case 'revision':
                                $estado_class = 'status-recepcion';
                                $estado_texto = 'RECEPCIÓN';
                                break;
                            case 'completada':
                                $estado_class = 'status-completada';
                                break;
                            case 'incompleta':
                                $estado_class = 'status-incompleta';
                                break;
                            case 'cancelada':
                                $estado_class = 'status-cancelada';
                                break;
                            default:
                                $estado_class = 'status-pendiente';
                        }
                        
                        // ===== ALERTA (activa/inhabilitada) =====
                        $alerta_class = '';
                        $alerta_texto = '';
                        $alerta_icono = '';
                        
                        // Si la orden está cancelada o inactiva → INHABILITADA
                        if ($compra['estado_compra'] == 'CANCELADA' || !$compra['activa']) {
                            $alerta_class = 'alerta-inhabilitada';
                            $alerta_texto = 'Inhabilitada';
                            $alerta_icono = '<i class="fas fa-ban"></i>';
                        } 
                        // Si está activa
                        else {
                            $alerta_class = 'alerta-activa';
                            $alerta_texto = 'Activa';
                            $alerta_icono = '<i class="fas fa-check-circle"></i>';
                            
                            // Solo para órdenes PENDIENTE, mostrar alertas de entrega
                            if ($compra['estado_compra'] == 'PENDIENTE' && $compra['fecha_estimada_entrega']) {
                                $hoy = new DateTime();
                                $entrega = new DateTime($compra['fecha_estimada_entrega']);
                                $hoy->setTime(0, 0, 0);
                                $entrega->setTime(0, 0, 0);
                                
                                if ($entrega < $hoy) {
                                    $alerta_class = 'alerta-atrasada';
                                    $alerta_texto = 'Atrasada';
                                    $alerta_icono = '<i class="fas fa-exclamation-triangle"></i>';
                                } elseif ($entrega->format('Y-m-d') == $hoy->format('Y-m-d')) {
                                    $alerta_class = 'alerta-hoy';
                                    $alerta_texto = 'Hoy';
                                    $alerta_icono = '<i class="fas fa-clock"></i>';
                                } else {
                                    $dias = $hoy->diff($entrega)->days;
                                    if ($dias <= 7) {
                                        $alerta_class = 'alerta-proxima';
                                        $alerta_texto = 'Próxima';
                                        $alerta_icono = '<i class="fas fa-calendar-check"></i>';
                                    } else {
                                        $alerta_class = 'alerta-activa';
                                        $alerta_texto = 'Activa';
                                        $alerta_icono = '<i class="fas fa-check-circle"></i>';
                                    }
                                }
                            }
                        }
                    ?>
                    <div class="table-row" data-id="<?php echo $compra['id']; ?>" data-codigo="<?php echo htmlspecialchars($compra['codigo_compra']); ?>">
                        <!-- Orden # (Código + Fecha) -->
                        <div data-label="Orden #">
                            <div class="order-code-block">
                                <span class="order-code"><?php echo htmlspecialchars($compra['codigo_compra']); ?></span>
                                <span class="order-date"><?php echo date('d/m/Y', strtotime($compra['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <!-- Proveedor (con espacio suficiente) -->
                        <div data-label="Proveedor" class="proveedor-cell">
                            <?php echo htmlspecialchars($compra['proveedor_nombre'] ?? 'N/A'); ?>
                        </div>
                        
                        <!-- Fecha de Entrega -->
                        <div data-label="Entrega">
                            <?php echo $compra['fecha_estimada_entrega'] ? date('d/m/Y', strtotime($compra['fecha_estimada_entrega'])) : 'No definida'; ?>
                        </div>
                        
                        <!-- Productos (cantidad) -->
                        <div data-label="Prod."><?php echo $compra['productos_count']; ?></div>
                        
                        <!-- Unidades (cantidad total) -->
                        <div data-label="Unds."><?php echo $compra['total_unidades'] ?? 0; ?></div>
                        
                        <!-- Total -->
                        <div data-label="Total" class="total-value">
                            <?php 
                            $precios_total = formatearMonedaDual($compra['total']);
                            ?>
                            <span class="moneda-usd"><?php echo $precios_total['usd']; ?></span>
                            <span class="moneda-bs"><?php echo $precios_total['bs']; ?></span>
                        </div>
                        
                        <!-- ESTADO (proceso de la orden) -->
                        <div data-label="Estado">
                            <span class="status-badge <?php echo $estado_class; ?>"><?php echo $estado_texto; ?></span>
                        </div>
                        
                        <!-- ALERTA (activa/inhabilitada) -->
                        <div data-label="Alerta">
                            <span class="<?php echo $alerta_class; ?>">
                                <?php echo $alerta_icono; ?> <?php echo $alerta_texto; ?>
                            </span>
                        </div>
                        
                        <!-- Acciones - SIN BOTÓN DE EDICIÓN -->
                        <div class="actions-cell" data-label="Acciones">
                            <button class="action-btn view" onclick="verDetalleCompra(<?php echo $compra['id']; ?>)" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn status" onclick="cambiarEstadoCompra(<?php echo $compra['id']; ?>)" title="Cambiar estado">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                            <?php if ($compra['estado_compra'] != 'CANCELADA'): ?>
                                <button class="action-btn disable" onclick="inhabilitarCompra(<?php echo $compra['id']; ?>)" title="Cancelar orden">
                                    <i class="fas fa-ban"></i>
                                </button>
                            <?php else: ?>
                                <button class="action-btn enable" onclick="reactivarCompra(<?php echo $compra['id']; ?>)" title="Reactivar orden">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        </div> <!-- cierre de compras-table-wrapper -->
    </div>

    <!-- ========== MODAL DE NUEVA/EDICIÓN DE ORDEN DE COMPRA ========== -->
    <div class="modal-overlay" id="compraModal">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalCompraTitle"><i class="fas fa-file-purchase"></i> Nueva Orden de Compra</h2>
                <button class="modal-close" id="closeCompraModal">&times;</button>
            </div>
            
            <div class="modal-body">
                <input type="hidden" id="compraId" value="">
                <input type="hidden" id="compraCodigo" value="">
                
                <!-- Información de la empresa -->
                <div class="empresa-info">
                    <h3><?php echo htmlspecialchars($empresa_nombre); ?></h3>
                    <p><strong>RIF:</strong> <?php echo htmlspecialchars($empresa_rif); ?></p>
                    <p><strong>Dirección:</strong> <?php echo htmlspecialchars($empresa_direccion); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($empresa_telefono); ?></p>
                </div>

                <!-- Título de la orden -->
                <div class="orden-title">
                    <h2 id="ordenTitle">ORDEN DE COMPRA</h2>
                </div>

                <!-- Información del proveedor y comprador -->
                <div class="info-section">
                    <!-- Proveedor -->
                    <div class="info-card">
                        <h3>
                            <span><i class="fas fa-truck"></i> Proveedor</span>
                            <button class="btn-add-prov" id="addSupplierBtn" style="white-space:nowrap; margin-left:12px;">
                                <i class="fas fa-plus" style="color: white;"></i> Agregar
                            </button>
                        </h3>
                        <div class="select-group">
                            <label for="proveedorSelect">Seleccionar Proveedor *</label>
                            <select id="proveedorSelect">
                                <option value="">-- Seleccione un proveedor --</option>
                                <?php foreach ($proveedores as $proveedor): ?>
                                <option value="<?php echo $proveedor['id']; ?>"
                                        data-razon="<?php echo htmlspecialchars($proveedor['razon_social']); ?>"
                                        data-rif="<?php echo htmlspecialchars($proveedor['rif']); ?>"
                                        data-direccion="<?php echo htmlspecialchars($proveedor['direccion']); ?>"
                                        data-telefono="<?php echo htmlspecialchars($proveedor['telefono_principal']); ?>"
                                        data-contacto="<?php echo htmlspecialchars($proveedor['persona_contacto']); ?>"
                                        data-email="<?php echo htmlspecialchars($proveedor['email']); ?>">
                                    <?php echo htmlspecialchars($proveedor['razon_social'] . ' (' . $proveedor['rif'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="proveedorInfo" style="display: none;">
                            <div class="info-row">
                                <span class="info-label">Nombre:</span>
                                <span class="info-value" id="proveedorNombre">-</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Dirección:</span>
                                <span class="info-value" id="proveedorDireccion">-</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Teléfono:</span>
                                <span class="info-value" id="proveedorTelefono">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Comprador -->
                    <div class="info-card">
                        <h3><i class="fas fa-user-tie"></i> Comprador</h3>
                        <div class="info-row">
                            <span class="info-label">Nombre:</span>
                            <span class="info-value"><?php echo htmlspecialchars($comprador_nombre); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Correo:</span>
                            <span class="info-value"><?php echo htmlspecialchars($comprador_email); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Teléfono:</span>
                            <span class="info-value"><?php echo htmlspecialchars($comprador_telefono); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de selección de proveedor -->
                <div id="selectProviderMessage" class="empty-state" style="margin: 20px 0; display: block;">
                    <i class="fas fa-hand-pointer"></i>
                    <p>Seleccione un proveedor para ver los productos disponibles</p>
                </div>

                <!-- Búsqueda y selección de productos (oculto hasta seleccionar proveedor) -->
                <div id="productSelectionSection" style="display: none;">
                    <div class="product-search">
                        <div class="search-box">
                            <input type="text" id="productSearch" placeholder="Buscar producto por código o nombre...">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>

                    <!-- Tabla de productos disponibles (solo del proveedor seleccionado) -->
                    <div class="products-table-container">
                        <table class="products-table" id="productsTable" style="table-layout: fixed; width:100%;">
                            <colgroup>
                                <col style="width:12%">
                                <col style="width:44%">
                                <col style="width:18%">
                                <col style="width:16%">
                                <col style="width:10%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Precio Compra</th>
                                    <th>Stock Actual</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <!-- Los productos se cargarán vía AJAX según proveedor -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Mensaje cuando no hay productos para el proveedor -->
                    <div id="noProductsMessage" style="display: none; text-align: center; padding: 40px; background: #f8f9fa; border-radius: 6px; margin: 20px 0;">
                        <i class="fas fa-box-open fa-3x" style="color: #ccc;"></i>
                        <p style="margin: 15px 0; color: #666;">Este proveedor no tiene productos asociados</p>
                        <button class="btn btn-primary" onclick="window.open('/inversiones-rojas/views/productos.php', '_blank')">
                            <i class="fas fa-plus"></i> Asignar Productos
                        </button>
                    </div>
                </div>

                <!-- Productos seleccionados -->
                <div class="selected-products">
                    <h3><i class="fas fa-shopping-cart"></i> Productos en la Orden</h3>
                    
                    <div class="selected-table-container">
                        <table class="selected-table" id="selectedTable" style="table-layout: fixed; width:100%;">
                            <colgroup>
                                <col style="width:12%">
                                <col style="width:44%">
                                <col style="width:12%">
                                <col style="width:16%">
                                <col style="width:12%">
                                <col style="width:4%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="selectedTableBody">
                                <!-- Los productos seleccionados aparecerán aquí -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Estado vacío -->
                    <div class="empty-state" id="emptyOrder">
                        <i class="fas fa-shopping-cart"></i>
                        <p>No hay productos en la orden</p>
                        <p>Agrega productos desde la tabla superior</p>
                    </div>
                </div>

                <!-- Resumen de la orden -->
                <div class="order-summary">
                    <h3 class="summary-title"><i class="fas fa-file-invoice"></i> Resumen de la Orden</h3>
                    
                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="summary-label">Código Orden:</span>
                            <span class="summary-value" id="orderCodeDisplay">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Fecha:</span>
                            <span class="summary-value" id="orderDateDisplay"><?php echo date('d/m/Y'); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Productos:</span>
                            <span class="summary-value" id="productsCount">0</span>
                        </div>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label">Subtotal:</span>
                        <span class="summary-value">$ <span id="orderSubtotal">0.00</span></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">IVA (16%):</span>
                        <span class="summary-value">$ <span id="orderTax">0.00</span></span>
                    </div>
                    
                    <div class="summary-total">
                        <span class="total-label">TOTAL:</span>
                        <span class="total-value">$ <span id="orderTotal">0.00</span></span>
                    </div>
                </div>

                <!-- Campos adicionales -->
                <div class="additional-fields">
                    <div class="form-group">
                        <label for="fechaEntrega">Fecha Estimada de Entrega *</label>
                        <input type="date" id="fechaEntrega" min="<?php echo date('Y-m-d'); ?>" 
                               value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                    </div>
                    
                    <!-- Estado oculto en nuevas órdenes, siempre PENDIENTE -->
                    <div class="form-group" style="display: none;">
                        <label for="estadoCompra">Estado</label>
                        <select id="estadoCompra">
                            <option value="PENDIENTE" selected>PENDIENTE</option>
                            <option value="RECEPCION">RECEPCIÓN</option>
                            <option value="REVISION">REVISIÓN</option>
                            <option value="COMPLETADA">COMPLETADA</option>
                            <option value="INCOMPLETA">INCOMPLETA</option>
                            <option value="CANCELADA">CANCELADA</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" placeholder="Notas adicionales..."></textarea>
                    </div>
                    
                    <div class="form-group" id="notasIncidenciaGroup" style="display:none;">
                        <label for="notasIncidencia">Notas de Incidencia</label>
                        <textarea id="notasIncidencia" placeholder="Describa qué productos faltan o los problemas encontrados..."></textarea>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-cancel" id="cancelCompra">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <div>
                    <button class="btn btn-create" id="saveOrder" disabled>
                        <i class="fas fa-save"></i> Guardar Orden
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== MODAL DE CAMBIO DE ESTADO ========== -->
    <div class="modal-overlay" id="estadoCompraModal">
        <div class="modal small-modal" style="max-width:420px;">
            <div class="modal-header">
                <h2><i class="fas fa-exchange-alt"></i> Cambiar Estado</h2>
                <button class="modal-close" id="closeEstadoModal">&times;</button>
            </div>

            <div class="modal-body" style="padding:24px;">
                <input type="hidden" id="estadoCompraId" value="">
                <input type="hidden" id="estadoActual" value="">

                <!-- Código y proveedor -->
                <div style="text-align:center;margin-bottom:20px;">
                    <div id="estadoCompraCodigo" style="font-size:1.1rem;font-weight:700;color:#1F9166;"></div>
                    <div id="estadoCompraProveedor" style="font-size:13px;color:#888;margin-top:4px;"></div>
                </div>

                <!-- Badge estado actual -->
                <div style="text-align:center;margin-bottom:22px;">
                    <span style="font-size:11px;color:#aaa;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">Estado actual</span>
                    <span id="estadoActualBadge" style="display:inline-block;padding:5px 18px;border-radius:20px;font-size:13px;font-weight:700;"></span>
                </div>

                <!-- Notas (visible en RECEPCION y REVISION para marcar incompleta) -->
                <div id="notasSection" style="display:none;margin-bottom:16px;">
                    <label style="font-size:12px;font-weight:600;display:block;margin-bottom:6px;color:#555;">
                        Notas de recepción <span style="font-weight:400;color:#e74c3c;">(requerido para recepción incompleta)</span>
                    </label>
                    <textarea id="notasRecepcion" placeholder="Describa las observaciones sobre la recepción incompleta..."
                        style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:13px;
                               resize:vertical;min-height:75px;box-sizing:border-box;font-family:inherit;"></textarea>
                </div>

                <!-- Botones de acción dinámicos -->
                <div id="receiptActions" style="display:flex;flex-direction:column;gap:10px;"></div>
            </div>
        </div>
    </div>

    <!-- ========== MODAL DE DETALLE DE COMPRA CON BOTONES EDITAR E IMPRIMIR ========== -->
    <div class="modal-overlay" id="detalleCompraModal">
        <div class="modal small-modal" style="max-width: 700px;">
            <div class="modal-header">
                <h2><i class="fas fa-eye"></i> Detalle de Compra</h2>
                <button class="modal-close" id="closeDetalleModal">&times;</button>
            </div>
            
            <div class="modal-body" id="detalleCompraBody">
                <!-- Contenido cargado dinámicamente -->
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p style="margin-top: 10px;">Cargando detalles...</p>
                </div>
            </div>
            
            <div class="modal-footer" style="display: flex; justify-content: space-between;">
                <div>
                    <button class="btn btn-print" onclick="imprimirOrdenCompra()">
                        <i class="fas fa-print"></i> Imprimir Orden
                    </button>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-edit" onclick="editarDesdeDetalle()">
                        <i class="fas fa-edit"></i> Editar Orden
                    </button>
                    <button class="btn btn-cancel" onclick="cerrarDetalleModal()">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== MODAL DE REGISTRO DE PROVEEDOR ========== -->
    <div class="modal-overlay registro-modal" id="addSupplierModal">
        <div class="modal registro-modal">
            <form id="addSupplierForm">
                <div class="modal-header">
                    <h3><i class="fas fa-truck-fast"></i> Nuevo Proveedor</h3>
                    <button class="modal-close" id="closeAddSupplierModal" type="button">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="provRazon">Razón Social *</label>
                        <input id="provRazon" name="razon_social" class="form-control" placeholder="Ej: Distribuidora XYZ C.A." />
                        <div class="field-error" id="err_provRazon"></div>
                    </div>
                    <div class="form-group">
                        <label for="provRif">RIF / Cédula *</label>
                        <div style="display: flex; gap: 10px;">
                            <select id="provRifType" class="form-control" style="width: 80px; flex-shrink: 0;">
                                <option value="J">J</option>
                                <option value="V">V</option>
                            </select>
                            <input id="provRif" name="rif" class="form-control" placeholder="123456789" style="flex: 1;" />
                        </div>
                        <small style="color: #666; font-size: 0.9em;">J para RIF (9 dígitos), V para Cédula (7-8 dígitos)</small>
                        <div class="field-error" id="err_provRif"></div>
                    </div>
                    <div class="form-group">
                        <label for="provContacto">Persona Contacto</label>
                        <input id="provContacto" name="persona_contacto" class="form-control" placeholder="Ej: Juan Pérez" />
                    </div>
                    <div class="form-group">
                        <label for="provTelefono">Teléfono Principal</label>
                        <input id="provTelefono" name="telefono_principal" class="form-control" placeholder="Ej: 0414-1234567" />
                        <div class="field-error" id="err_provTelefono"></div>
                    </div>
                    <div class="form-group">
                        <label for="provTelefonoAlt">Teléfono Alternativo</label>
                        <input id="provTelefonoAlt" name="telefono_alternativo" class="form-control" placeholder="Ej: 0212-1234567" />
                        <div class="field-error" id="err_provTelefonoAlt"></div>
                    </div>
                    <div class="form-group">
                        <label for="provEmail">Email</label>
                        <input id="provEmail" name="email" type="email" class="form-control" placeholder="Ej: contacto@empresa.com" />
                        <div class="field-error" id="err_provEmail"></div>
                    </div>
                    <div class="form-group">
                        <label for="provDireccion">Dirección</label>
                        <textarea id="provDireccion" name="direccion" class="form-control" rows="3"></textarea>
                    </div>
                    <div id="addSupplierMsg" style="margin-top:8px; display:none; padding:10px; border-radius:6px;"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline" type="button" id="cancelAddSupplier">Cancelar</button>
                    <button class="btn btn-primary" type="submit" id="saveAddSupplier">Guardar Proveedor</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ========== VARIABLES GLOBALES ==========
        let selectedProducts = [];
        let selectedProveedor = null;
        let editingCompraId = null;
        let detalleCompraActual = null;
        const IVA_RATE = 0.16;
        
        // Definir tasa de cambio global
        var TASA_CAMBIO = <?php echo getTasaCambio(); ?>;
        var APP_BASE = '<?php echo defined("BASE_URL") ? BASE_URL : ""; ?>';
        console.log('Tasa de cambio cargada:', TASA_CAMBIO);

        // Elementos del DOM
        const loadingOverlay = document.getElementById('loadingOverlay');
        const compraModal = document.getElementById('compraModal');
        const estadoModal = document.getElementById('estadoCompraModal');
        const detalleModal = document.getElementById('detalleCompraModal');
        const openCompraModalBtn = document.getElementById('openCompraModalBtn');
        const closeCompraModalBtn = document.getElementById('closeCompraModal');
        const cancelCompraBtn = document.getElementById('cancelCompra');
        const closeEstadoBtn = document.getElementById('closeEstadoModal');
        const closeDetalleBtn = document.getElementById('closeDetalleModal');
        const saveOrderBtn = document.getElementById('saveOrder');
        const proveedorSelect = document.getElementById('proveedorSelect');
        const productSearch = document.getElementById('productSearch');
        const productsTableBody = document.getElementById('productsTableBody');
        const selectedTableBody = document.getElementById('selectedTableBody');
        const emptyOrder = document.getElementById('emptyOrder');
        const selectedTable = document.querySelector('.selected-table');
        const orderCodeDisplay = document.getElementById('orderCodeDisplay');
        const productsCount = document.getElementById('productsCount');
        const orderSubtotal = document.getElementById('orderSubtotal');
        const orderTax = document.getElementById('orderTax');
        const orderTotal = document.getElementById('orderTotal');
        const fechaEntrega = document.getElementById('fechaEntrega');
        const observaciones = document.getElementById('observaciones');
        const estadoCompra = document.getElementById('estadoCompra');
        const notasIncidencia = document.getElementById('notasIncidencia');
        const notasIncidenciaGroup = document.getElementById('notasIncidenciaGroup');
        const selectProviderMessage = document.getElementById('selectProviderMessage');
        const productSelectionSection = document.getElementById('productSelectionSection');
        const noProductsMessage = document.getElementById('noProductsMessage');

        // ========== FUNCIONES DE UTILIDAD ==========
        function mostrarLoading() {
            loadingOverlay.classList.add('active');
        }

        function ocultarLoading() {
            loadingOverlay.classList.remove('active');
        }

        function mostrarToast(mensaje, tipo = 'success', duration = 10000) {
            if (window.Toast && typeof window.Toast[tipo] === 'function') {
                Toast[tipo](mensaje, '', duration);
            } else if (window.Toast && typeof window.Toast.info === 'function') {
                Toast.info(mensaje, '', duration);
            } else {
                // Fallback mínimo
                console.warn('Toast no disponible, usando alert');
                alert(mensaje);
            }
        }

        // ========== FUNCIONES PARA CARGAR PRODUCTOS DEL PROVEEDOR ==========
        async function cargarProductosPorProveedor(proveedorId) {
            mostrarLoading();
            
            try {
                const response = await fetch(`/inversiones-rojas/api/productos_por_proveedor.php?id=${proveedorId}`);
                const data = await response.json();
                
                if (data.success) {
                    if (data.productos && data.productos.length > 0) {
                        renderProductosTable(data.productos);
                        noProductsMessage.style.display = 'none';
                    } else {
                        productsTableBody.innerHTML = '';
                        noProductsMessage.style.display = 'block';
                    }
                } else {
                    mostrarToast('Error al cargar productos', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarToast('Error de conexión', 'error');
            } finally {
                ocultarLoading();
            }
        }

        function renderProductosTable(productos) {
            productsTableBody.innerHTML = '';
            
            productos.forEach(producto => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="product-code">${producto.codigo_interno}</td>
                    <td class="product-name">${producto.nombre}</td>
                    <td class="product-price">$ ${parseFloat(producto.precio_compra).toFixed(2)}</td>
                    <td class="product-stock">${producto.stock_actual} unidades</td>
                    <td>
                        <button class="add-product-btn" 
                            data-id="${producto.id}" 
                            data-code="${producto.codigo_interno}"
                            data-name="${producto.nombre}"
                            data-price="${producto.precio_compra}">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </td>
                `;
                productsTableBody.appendChild(row);
            });
            
            // Verificar productos ya seleccionados para deshabilitar botones
            selectedProducts.forEach(p => {
                const addBtn = productsTableBody.querySelector(`.add-product-btn[data-id="${p.id}"]`);
                if (addBtn) {
                    addBtn.disabled = true;
                    addBtn.innerHTML = '<i class="fas fa-check"></i> Agregado';
                }
            });
        }

        // ========== FUNCIONES PARA EL MODAL DE COMPRAS ==========
        function initCompraModal(editMode = false) {
            if (!editMode) {
                editingCompraId = null;
                document.getElementById('modalCompraTitle').innerHTML = '<i class="fas fa-file-purchase"></i> Nueva Orden de Compra';
                selectedProducts = [];
                selectedProveedor = null;
                proveedorSelect.value = '';
                document.getElementById('proveedorInfo').style.display = 'none';
                selectProviderMessage.style.display = 'block';
                productSelectionSection.style.display = 'none';
                fechaEntrega.value = getDatePlusDays(7);
                observaciones.value = '';
                estadoCompra.value = 'PENDIENTE';
                notasIncidencia.value = '';
                notasIncidenciaGroup.style.display = 'none';
                generateOrderCode();
            }
            
            updateSelectedProductsUI();
            updateOrderSummary();
            
            // Reiniciar búsqueda
            productSearch.value = '';
            
            // Limpiar tabla de productos
            productsTableBody.innerHTML = '';
        }

        function getDatePlusDays(days) {
            const date = new Date();
            date.setDate(date.getDate() + days);
            return date.toISOString().split('T')[0];
        }

        function generateOrderCode() {
            const date = new Date();
            const dateStr = date.getFullYear().toString().slice(-2) + 
                          ('0' + (date.getMonth() + 1)).slice(-2) + 
                          ('0' + date.getDate()).slice(-2);
            const randomNum = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            orderCodeDisplay.textContent = `OC-${dateStr}-${randomNum}`;
        }

        function addProductToOrder(product) {
            // Verificar que el producto sea del proveedor seleccionado
            if (!selectedProveedor) {
                mostrarToast('Primero debe seleccionar un proveedor', 'error');
                return;
            }
            
            const existingProduct = selectedProducts.find(item => item.id == product.id);
            
            if (existingProduct) {
                existingProduct.cantidad += 1;
            } else {
                selectedProducts.push({
                    id: product.id,
                    codigo: product.codigo,
                    nombre: product.nombre,
                    precio: parseFloat(product.precio),
                    cantidad: 1
                });
            }

            updateSelectedProductsUI();
            updateOrderSummary();
            
            // Deshabilitar botón en la tabla de productos
            const addBtn = productsTableBody.querySelector(`.add-product-btn[data-id="${product.id}"]`);
            if (addBtn) {
                addBtn.disabled = true;
                addBtn.innerHTML = '<i class="fas fa-check"></i> Agregado';
            }
        }

        function removeProductFromOrder(productId) {
            selectedProducts = selectedProducts.filter(item => item.id != productId);
            updateSelectedProductsUI();
            updateOrderSummary();
            
            // Habilitar botón en la tabla de productos
            const addBtn = productsTableBody.querySelector(`.add-product-btn[data-id="${productId}"]`);
            if (addBtn) {
                addBtn.disabled = false;
                addBtn.innerHTML = '<i class="fas fa-plus"></i> Agregar';
            }
        }

        function updateProductQuantity(productId, newCantidad) {
            if (newCantidad < 1) {
                removeProductFromOrder(productId);
                return;
            }
            
            const product = selectedProducts.find(item => item.id == productId);
            if (product) {
                product.cantidad = newCantidad;
            }
            
            updateSelectedProductsUI();
            updateOrderSummary();
        }

        function updateSelectedProductsUI() {
            if (selectedProducts.length === 0) {
                emptyOrder.style.display = 'block';
                selectedTable.style.display = 'none';
            } else {
                emptyOrder.style.display = 'none';
                selectedTable.style.display = 'table';
                
                selectedTableBody.innerHTML = '';
                
                selectedProducts.forEach(product => {
                    const total = product.precio * product.cantidad;
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="product-code">${product.codigo}</td>
                        <td class="product-name">${product.nombre}</td>
                        <td>
                            <div class="quantity-controls">
                                <button class="quantity-btn decrease" data-id="${product.id}">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="quantity-input" value="${product.cantidad}" min="1" data-id="${product.id}">
                                <button class="quantity-btn increase" data-id="${product.id}">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </td>
                        <td class="product-price">$ ${product.precio.toFixed(2)}</td>
                        <td class="product-price">$ ${total.toFixed(2)}</td>
                        <td>
                            <button class="remove-item" data-id="${product.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    selectedTableBody.appendChild(row);
                });
            }
            
            updateCreateButtonState();
        }

        function updateOrderSummary() {
            // Calcular totales
            let subtotal = 0;
            selectedProducts.forEach(product => {
                subtotal += product.precio * product.cantidad;
            });
            
            const tax = subtotal * IVA_RATE;
            const total = subtotal + tax;
            
            // Actualizar contadores
            productsCount.textContent = selectedProducts.length;
            orderSubtotal.textContent = subtotal.toFixed(2);
            orderTax.textContent = tax.toFixed(2);
            orderTotal.textContent = total.toFixed(2);
        }

        function filterProducts() {
            const query = productSearch.value.toLowerCase();
            const rows = productsTableBody.querySelectorAll('tr');
            
            rows.forEach(row => {
                const code = row.querySelector('.product-code').textContent.toLowerCase();
                const name = row.querySelector('.product-name').textContent.toLowerCase();
                
                if (code.includes(query) || name.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function updateCreateButtonState() {
            if (selectedProducts.length > 0 && selectedProveedor && fechaEntrega.value) {
                saveOrderBtn.disabled = false;
            } else {
                saveOrderBtn.disabled = true;
            }
        }

        // ========== FUNCIONES PARA EDITAR COMPRA ==========
        async function editarCompra(id) {
            mostrarLoading();
            
            try {
                const response = await fetch(`/inversiones-rojas/api/compras.php?action=get&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const compra = data.compra;
                    editingCompraId = compra.id;
                    
                    // Configurar modal para edición
                    document.getElementById('modalCompraTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Orden de Compra';
                    document.getElementById('compraId').value = compra.id;
                    document.getElementById('compraCodigo').value = compra.codigo_compra;
                    orderCodeDisplay.textContent = compra.codigo_compra;
                    
                    // Seleccionar proveedor
                    if (compra.proveedor_id) {
                        proveedorSelect.value = compra.proveedor_id;
                        proveedorSelect.dispatchEvent(new Event('change'));
                        
                        // Esperar a que carguen los productos
                        await cargarProductosPorProveedor(compra.proveedor_id);
                        
                        // Cargar productos seleccionados
                        selectedProducts = data.productos.map(p => ({
                            id: p.id,
                            codigo: p.codigo_interno,
                            nombre: p.nombre,
                            precio: parseFloat(p.precio_unitario),
                            cantidad: parseInt(p.cantidad)
                        }));
                        
                        // Actualizar UI
                        updateSelectedProductsUI();
                        updateOrderSummary();
                        
                        // Deshabilitar botones de productos ya agregados
                        selectedProducts.forEach(p => {
                            const addBtn = productsTableBody.querySelector(`.add-product-btn[data-id="${p.id}"]`);
                            if (addBtn) {
                                addBtn.disabled = true;
                                addBtn.innerHTML = '<i class="fas fa-check"></i> Agregado';
                            }
                        });
                    }
                    
                    // Fecha de entrega
                    if (compra.fecha_estimada_entrega) {
                        fechaEntrega.value = compra.fecha_estimada_entrega.split('T')[0];
                    }
                    
                    // Observaciones y estado
                    observaciones.value = compra.observaciones || '';
                    estadoCompra.value = compra.estado_compra || 'PENDIENTE';
                    
                    // Mostrar/ocultar notas de incidencia según estado
                    if (compra.estado_compra === 'INCOMPLETA') {
                        notasIncidenciaGroup.style.display = 'block';
                        notasIncidencia.value = compra.notas_incidencia || '';
                    } else {
                        notasIncidenciaGroup.style.display = 'none';
                    }
                    
                    compraModal.classList.add('active');
                    detalleModal.classList.remove('active');
                } else {
                    mostrarToast('Error al cargar la compra: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarToast('Error al cargar los datos de la compra', 'error');
            } finally {
                ocultarLoading();
            }
        }

        // ========== FUNCIÓN PARA EDITAR DESDE DETALLE ==========
        function editarDesdeDetalle() {
            if (detalleCompraActual && detalleCompraActual.id) {
                editarCompra(detalleCompraActual.id);
            }
        }

        // ========== FUNCIONES PARA VER DETALLE ==========
        async function verDetalleCompra(id) {
            mostrarLoading();
            detalleModal.classList.add('active');
            
            try {
                const response = await fetch(`/inversiones-rojas/api/compras.php?action=detalle&id=${id}`);
                const data = await response.json();
                
                if (data.success) {
                    const compra = data.compra;
                    const productos = data.productos;
                    
                    // Guardar datos para impresión y edición
                    detalleCompraActual = {
                        id: compra.id,
                        codigo_compra: compra.codigo_compra,
                        proveedor_nombre: compra.proveedor_nombre,
                        proveedor_rif: compra.proveedor_rif,
                        proveedor_direccion: compra.proveedor_direccion,
                        proveedor_telefono: compra.proveedor_telefono,
                        comprador_nombre: compra.comprador_nombre || '<?php echo $comprador_nombre; ?>',
                        comprador_email: '<?php echo $comprador_email; ?>',
                        comprador_telefono: '<?php echo $comprador_telefono; ?>',
                        empresa_nombre: '<?php echo $empresa_nombre; ?>',
                        empresa_rif: '<?php echo $empresa_rif; ?>',
                        empresa_direccion: '<?php echo $empresa_direccion; ?>',
                        empresa_telefono: '<?php echo $empresa_telefono; ?>',
                        subtotal: compra.subtotal,
                        iva: compra.iva,
                        total: compra.total,
                        observaciones: compra.observaciones,
                        fecha_creacion: new Date(compra.created_at).toLocaleString('es-ES'),
                        fecha_entrega: compra.fecha_estimada_entrega ? new Date(compra.fecha_estimada_entrega).toLocaleDateString('es-ES') : 'No definida',
                        productos: productos,
                        productosHTML: productos.map(p => `
                            <tr>
                                <td>${p.codigo_interno}</td>
                                <td>${p.nombre}</td>
                                <td>${p.cantidad}</td>
                                <td>$ ${parseFloat(p.precio_unitario).toFixed(2)}</td>
                                <td>$ ${(p.cantidad * p.precio_unitario).toFixed(2)}</td>
                            </tr>
                        `).join('')
                    };
                    
                    let html = `
                        <div style="text-align: center; margin-bottom: 20px;">
                            <h3 style="color: #1F9166; margin: 0;">${compra.codigo_compra}</h3>
                            <p style="color: #666; margin: 5px 0;">${compra.proveedor_nombre}</p>
                        </div>
                        
                        <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <p><strong>Fecha creación:</strong><br>${new Date(compra.created_at).toLocaleString('es-ES')}</p>
                                    <p><strong>Fecha entrega:</strong><br>${compra.fecha_estimada_entrega ? new Date(compra.fecha_estimada_entrega).toLocaleDateString('es-ES') : 'No definida'}</p>
                                </div>
                                <div>
                                    <p><strong>Comprador:</strong><br>${compra.comprador_nombre || 'Sistema'}</p>
                                    <p><strong>Estado:</strong><br><span class="status-badge status-${compra.estado_compra.toLowerCase()}">${compra.estado_compra}</span></p>
                                </div>
                            </div>
                        </div>
                        
                        <h4 style="margin: 15px 0 10px;">Productos</h4>
                        <div style="border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead style="background: #f8f9fa;">
                                    <tr>
                                        <th style="padding: 10px; text-align: left;">Código</th>
                                        <th style="padding: 10px; text-align: left;">Producto</th>
                                        <th style="padding: 10px; text-align: center;">Cant.</th>
                                        <th style="padding: 10px; text-align: right;">Precio</th>
                                        <th style="padding: 10px; text-align: right;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    productos.forEach(p => {
                        html += `
                            <tr style="border-top: 1px solid #e9ecef;">
                                <td style="padding: 10px;">${p.codigo_interno}</td>
                                <td style="padding: 10px;">${p.nombre}</td>
                                <td style="padding: 10px; text-align: center;">${p.cantidad}</td>
                                <td style="padding: 10px; text-align: right;">$ ${parseFloat(p.precio_unitario).toFixed(2)}</td>
                                <td style="padding: 10px; text-align: right;">$ ${(p.cantidad * p.precio_unitario).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                                <tfoot style="background: #f8f9fa; border-top: 2px solid #1F9166;">
                                    <tr>
                                        <td colspan="4" style="padding: 10px; text-align: right;"><strong>Subtotal:</strong></td>
                                        <td style="padding: 10px; text-align: right;">$ ${compra.subtotal}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" style="padding: 10px; text-align: right;"><strong>IVA (16%):</strong></td>
                                        <td style="padding: 10px; text-align: right;">$ ${compra.iva}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" style="padding: 10px; text-align: right;"><strong>TOTAL:</strong></td>
                                        <td style="padding: 10px; text-align: right; color: #1F9166; font-weight: 700; font-size: 1.2rem;">$ ${compra.total}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                    
                    if (compra.observaciones) {
                        html += `
                            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                <p><strong>Observaciones:</strong></p>
                                <p style="color: #666; margin: 5px 0 0;">${compra.observaciones}</p>
                            </div>
                        `;
                    }
                    
                    if (compra.notas_incidencia) {
                        html += `
                            <div style="margin-top: 10px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                                <p><strong><i class="fas fa-exclamation-triangle"></i> Notas de incidencia:</strong></p>
                                <p style="color: #856404; margin: 5px 0 0;">${compra.notas_incidencia}</p>
                            </div>
                        `;
                    }
                    
                    document.getElementById('detalleCompraBody').innerHTML = html;
                } else {
                    document.getElementById('detalleCompraBody').innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #e74c3c;">
                            <i class="fas fa-exclamation-triangle fa-3x"></i>
                            <p style="margin-top: 20px;">Error al cargar los detalles</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('detalleCompraBody').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #e74c3c;">
                        <i class="fas fa-exclamation-triangle fa-3x"></i>
                        <p style="margin-top: 20px;">Error de conexión</p>
                    </div>
                `;
            } finally {
                ocultarLoading();
            }
        }

        function cerrarDetalleModal() {
            detalleModal.classList.remove('active');
            detalleCompraActual = null;
        }

        // ========== FUNCIÓN PARA IMPRIMIR ORDEN DE COMPRA ==========
        async function imprimirOrdenCompra() {
            if (!detalleCompraActual) {
                mostrarToast('No hay datos para imprimir', 'error');
                return;
            }

            mostrarLoading();
            try {
                const apiUrl = (window.APP_BASE || '') + '/api/generar_orden_compra_pdf.php';
                console.log('Generando PDF para compra:', detalleCompraActual.id, 'URL:', apiUrl);
                
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ compra_id: detalleCompraActual.id })
                });

                console.log('Response status:', response.status, response.statusText);
                
                if (!response.ok) {
                    const text = await response.text();
                    console.error('Error response:', text);
                    throw new Error('Error al generar PDF: ' + response.status);
                }

                const contentType = response.headers.get('content-type');
                console.log('Content-Type:', contentType);
                
                if (!contentType || !contentType.includes('application/pdf')) {
                    const text = await response.text();
                    console.error('Non-PDF response:', text.substring(0, 500));
                    throw new Error('La respuesta no es un PDF válido');
                }

                const blob = await response.blob();
                console.log('Blob size:', blob.size);
                
                if (blob.size === 0) {
                    throw new Error('El PDF está vacío');
                }

                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Orden_Compra_${detalleCompraActual.codigo_compra}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                ocultarLoading();
                mostrarToast('PDF descargado exitosamente', 'success');
            } catch (error) {
                console.error('Error:', error);
                ocultarLoading();
                mostrarToast('Error al descargar PDF: ' + error.message, 'error');
            }
        }

        // ========== FUNCIONES PARA CAMBIO DE ESTADO ==========
        // ══════════════════════════════════════════════════════
        //  MODAL DE CAMBIO DE ESTADO — lógica simplificada
        // ══════════════════════════════════════════════════════

        async function cambiarEstadoCompra(id) {
            mostrarLoading();
            try {
                const response = await fetch(`/inversiones-rojas/api/compras.php?action=get_estado&id=${id}`);
                const data = await response.json();

                if (!data.success) {
                    mostrarToast('Error al cargar datos de la compra', 'error');
                    return;
                }

                const compra = data.compra;
                document.getElementById('estadoCompraId').value    = compra.id;
                document.getElementById('estadoActual').value       = compra.estado_compra;
                document.getElementById('estadoCompraCodigo').textContent   = compra.codigo_compra;
                document.getElementById('estadoCompraProveedor').textContent = compra.proveedor_nombre;

                // Badge de estado actual
                const badgeMap = {
                    PENDIENTE:  { bg:'#fff3cd', color:'#856404', label:'Pendiente'  },
                    RECEPCION:  { bg:'#d1ecf1', color:'#0c5460', label:'En Recepción' },
                    COMPLETADA: { bg:'#d4edda', color:'#155724', label:'Completada' },
                    INCOMPLETA: { bg:'#ffeeba', color:'#856404', label:'Incompleta' },
                    CANCELADA:  { bg:'#f8d7da', color:'#721c24', label:'Cancelada'  },
                };
                const bm = badgeMap[compra.estado_compra] || { bg:'#e9ecef', color:'#333', label: compra.estado_compra };
                const badge = document.getElementById('estadoActualBadge');
                badge.textContent       = bm.label;
                badge.style.background  = bm.bg;
                badge.style.color       = bm.color;

                // Notas visible en RECEPCION y REVISION (para marcar incompleta)
                const notasSection = document.getElementById('notasSection');
                notasSection.style.display = (compra.estado_compra === 'RECEPCION' || compra.estado_compra === 'REVISION') ? 'block' : 'none';
                document.getElementById('notasRecepcion').value = '';

                _renderAccionesEstado(compra.estado_compra);
                estadoModal.classList.add('active');

            } catch (error) {
                console.error('Error:', error);
                mostrarToast('Error al cargar los datos', 'error');
            } finally {
                ocultarLoading();
            }
        }

        // Stubs vacíos para no romper si algún código externo los llama
        function actualizarFlujoEstados()       {}
        function cargarProductosVerificacion()  {}
        function actualizarResumenRecepcion()   {}

        function _renderAccionesEstado(estado) {
            const c   = document.getElementById('receiptActions');
            const btn = (txt, bg, color, fn, icon) =>
                `<button onclick="${fn}" style="border:none;padding:12px 18px;border-radius:8px;font-size:14px;
                 font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;
                 gap:8px;width:100%;background:${bg};color:${color};">
                    <i class="${icon}"></i> ${txt}
                 </button>`;

            switch (estado) {
                case 'PENDIENTE':
                    c.innerHTML =
                        btn('Iniciar Recepción',  '#17a2b8', '#fff', "procesarCambioEstado('RECEPCION')",  'fas fa-truck') +
                        btn('Cancelar Orden',     '#f8f9fa', '#555', "procesarCambioEstado('CANCELADA')",  'fas fa-ban') ;
                    break;

                case 'RECEPCION':
                case 'REVISION':
                    c.innerHTML =
                        btn('Marcar Completada',  '#1F9166', '#fff', "procesarCambioEstado('COMPLETADA')", 'fas fa-check-circle') +
                        btn('Marcar Incompleta',  '#ffc107', '#212529', "procesarRecepcionIncompleta()",   'fas fa-exclamation-triangle');
                    break;

                case 'COMPLETADA':
                case 'INCOMPLETA':
                case 'CANCELADA':
                    c.innerHTML =
                        `<p style="text-align:center;color:#aaa;font-size:13px;margin:0 0 12px;">
                            Esta orden ya fue procesada.
                         </p>` +
                        btn('Reabrir como Pendiente', '#f8f9fa', '#555', "procesarCambioEstado('PENDIENTE')", 'fas fa-undo');
                    break;

                default:
                    c.innerHTML = '';
            }
        }

        // Alias público para que el HTML pueda llamarlo
        function configurarAccionesEstado(estado) { _renderAccionesEstado(estado); }

        async function procesarCambioEstado(nuevoEstado) {
            const compraId = document.getElementById('estadoCompraId').value;
            const notas    = document.getElementById('notasRecepcion')?.value?.trim() ?? '';

            const msgs = {
                RECEPCION:  { title:'Iniciar Recepción',       msg:'La orden pasará a estado En Recepción.',                    type:'info'    },
                COMPLETADA: { title:'Marcar como Completada',  msg:'La orden se registrará como completada.',                   type:'info'    },
                INCOMPLETA: { title:'Marcar como Incompleta',  msg:'La orden se registrará como recibida de forma incompleta.', type:'warning' },
                CANCELADA:  { title:'Cancelar Orden',          msg:'La orden quedará cancelada. ¿Confirmas?',                   type:'danger'  },
                PENDIENTE:  { title:'Reabrir Orden',           msg:'La orden volverá a estado Pendiente.',                      type:'warning' },
            };
            const m = msgs[nuevoEstado] || { title:'Cambiar estado', msg:`¿Cambiar a ${nuevoEstado}?`, type:'info' };

            const ok = await showConfirm({ title: m.title, message: m.msg, confirmText:'Confirmar', cancelText:'Cancelar', type: m.type });
            if (!ok) return;

            mostrarLoading();
            try {
                const r = await fetch('/inversiones-rojas/api/compras.php?action=change_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: compraId, estado: nuevoEstado, notas })
                });
                const d = await r.json();
                if (d.success) {
                    const labels = { RECEPCION:'En Recepción', COMPLETADA:'Completada', INCOMPLETA:'Incompleta', CANCELADA:'Cancelada', PENDIENTE:'Pendiente' };
                    mostrarToast(`Estado actualizado: ${labels[nuevoEstado] || nuevoEstado}`, 'success');
                    estadoModal.classList.remove('active');
                    setTimeout(() => location.reload(), 800);
                } else {
                    mostrarToast('Error: ' + d.message, 'error');
                }
            } catch (e) {
                console.error(e);
                mostrarToast('Error al cambiar el estado', 'error');
            } finally {
                ocultarLoading();
            }
        }

        async function procesarRecepcionCompleta() {
            await procesarCambioEstado('COMPLETADA');
        }

        async function procesarRecepcionIncompleta() {
            const compraId = document.getElementById('estadoCompraId').value;
            const notas    = document.getElementById('notasRecepcion')?.value?.trim() ?? '';

            if (!notas) {
                mostrarToast('Debe especificar las notas de recepción para marcar como incompleta', 'error');
                document.getElementById('notasRecepcion')?.focus();
                return;
            }

            const ok = await showConfirm({
                title:       'Marcar como Incompleta',
                message:     'La orden se registrará como recibida de forma incompleta con las siguientes observaciones:<br><br>' +
                             '<strong>' + notas + '</strong>',
                confirmText: 'Confirmar',
                cancelText:  'Cancelar',
                type:        'warning',
            });
            if (!ok) return;

            mostrarLoading();
            try {
                const r = await fetch('/inversiones-rojas/api/compras.php?action=recepcion_incompleta', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: compraId, notas })
                });
                const d = await r.json();
                if (d.success) {
                    mostrarToast('Recepción marcada como incompleta', 'warning');
                    estadoModal.classList.remove('active');
                    setTimeout(() => location.reload(), 800);
                } else {
                    mostrarToast('Error: ' + d.message, 'error');
                }
            } catch (e) {
                console.error(e);
                mostrarToast('Error al procesar la recepción', 'error');
            } finally {
                ocultarLoading();
            }
        }

        // ========== FUNCIÓN PARA INHABILITAR COMPRA ==========
        async function inhabilitarCompra(id) {
            const row = document.querySelector(`.table-row[data-id="${id}"]`);
            const codigo = row ? row.dataset.codigo : 'esta orden';

            const confirmed = await showConfirm({
                title: 'Cancelar orden',
                message: `¿Está seguro de cancelar ${codigo}? Esta acción marcará la orden como cancelada.`,
                confirmText: 'Sí, cancelar',
                cancelText: 'No, mantener',
                type: 'warning'
            });
            if (!confirmed) return;

            mostrarLoading();

            try {
                const response = await fetch('/inversiones-rojas/api/compras.php?action=change_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        estado: 'CANCELADA',
                        notas: 'Cancelación manual'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Toast.success('Orden cancelada', '', 10000);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Toast.error('Error: ' + data.message, '', 10000);
                }
            } catch (error) {
                console.error('Error:', error);
                Toast.error('Error al procesar la solicitud', '', 10000);
            } finally {
                ocultarLoading();
            }
        }

        // ========== FUNCIÓN PARA REACTIVAR COMPRA ==========
        async function reactivarCompra(id) {
            const confirmed = await showConfirm({
                title: 'Reactivar orden',
                message: '¿Está seguro de reactivar esta orden? Se cambiará el estado a PENDIENTE.',
                confirmText: 'Sí, reactivar',
                cancelText: 'No, cancelar',
                type: 'info'
            });
            if (!confirmed) return;

            mostrarLoading();

            try {
                const response = await fetch('/inversiones-rojas/api/compras.php?action=change_status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        estado: 'PENDIENTE',
                        notas: 'Reactivación manual'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Toast.success('Orden reactivada', '', 10000);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Toast.error('Error: ' + data.message, '', 10000);
                }
            } catch (error) {
                console.error('Error:', error);
                Toast.error('Error al procesar la solicitud', '', 10000);
            } finally {
                ocultarLoading();
            }
        }

        // ========== FUNCIÓN PARA GUARDAR ORDEN (NUEVA O EDITADA) ==========
        async function guardarOrden() {
            // Validaciones básicas
            if (selectedProducts.length === 0) {
                Toast.error('No hay productos en la orden', '', 10000);
                return;
            }

            if (!selectedProveedor && !proveedorSelect.value) {
                Toast.error('Por favor seleccione un proveedor', '', 10000);
                return;
            }

            if (!fechaEntrega.value) {
                Toast.error('Por favor seleccione una fecha estimada de entrega', '', 10000);
                return;
            }

            // Fecha no debe ser pasada
            if (!InvValidate.notPastDate(fechaEntrega, 'Fecha estimada de entrega')) {
                Toast.error('La fecha estimada de entrega no puede ser pasada', '', 10000);
                return;
            }

            // Cantidades deben ser positivas
            const invalidProduct = selectedProducts.find(p => !Number.isFinite(p.cantidad) || p.cantidad <= 0);
            if (invalidProduct) {
                Toast.error(`Cantidad inválida para ${invalidProduct.nombre}`, '', 10000);
                return;
            }
            
            // Calcular totales
            let subtotal = 0;
            selectedProducts.forEach(product => {
                subtotal += product.precio * product.cantidad;
            });
            
            const tax = subtotal * IVA_RATE;
            const total = subtotal + tax;
            
            // Si es nueva orden, siempre PENDIENTE
            const estadoOrden = editingCompraId ? estadoCompra.value : 'PENDIENTE';
            
            // Preparar datos
            const ordenData = {
                id: editingCompraId,
                codigo_orden: orderCodeDisplay.textContent,
                proveedor_id: parseInt(proveedorSelect.value),
                fecha_estimada_entrega: fechaEntrega.value,
                observaciones: observaciones.value.trim(),
                estado: estadoOrden,
                notas_incidencia: notasIncidencia.value.trim(),
                subtotal: subtotal,
                iva: tax,
                total: total,
                productos: selectedProducts.map(product => ({
                    id: parseInt(product.id),
                    codigo: product.codigo,
                    nombre: product.nombre,
                    precio_unitario: parseFloat(product.precio),
                    cantidad: parseInt(product.cantidad)
                }))
            };
            
            mostrarLoading();
            
            try {
                const response = await fetch('/inversiones-rojas/api/compras.php?action=save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(ordenData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarToast(
                        editingCompraId ? 'Orden actualizada exitosamente' : 'Orden creada exitosamente',
                        'success'
                    );

                    // ── Enviar email al proveedor solo en órdenes nuevas ──
                    if (!editingCompraId && selectedProveedor?.email) {
                        try {
                            const emailResp = await fetch('/inversiones-rojas/api/enviar_orden_compra.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    compra_id:         data.id,
                                    codigo_compra:     data.codigo_compra,
                                    proveedor_email:   selectedProveedor.email,
                                    proveedor_nombre:  selectedProveedor.razon,
                                    total:             data.total,
                                    fecha_entrega:     fechaEntrega.value,
                                    productos:         ordenData.productos,
                                })
                            });
                            const emailData = await emailResp.json();
                            if (emailData.success) {
                                mostrarToast(`Orden enviada por correo a ${selectedProveedor.email}`, 'success');
                            } else {
                                mostrarToast('Orden creada, pero no se pudo enviar el email: ' + (emailData.message || ''), 'warning');
                            }
                        } catch (emailErr) {
                            console.warn('Error enviando email al proveedor:', emailErr);
                            mostrarToast('Orden creada, pero el envío de email falló.', 'warning');
                        }
                    }

                    compraModal.classList.remove('active');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    mostrarToast('Error: ' + (data.message || 'Error desconocido'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarToast('Error de conexión', 'error');
            } finally {
                ocultarLoading();
            }
        }

        // ========== FUNCIONES PARA FILTROS Y BÚSQUEDA ==========
        async function searchCompras() {
            const query = document.getElementById('comprasSearchInput').value.trim();
            const dateRange = document.getElementById('dateFilter').value;
            const status = document.getElementById('statusFilter').value;
            const proveedor = document.getElementById('proveedorFilter').value;
            const dateFromValue = document.getElementById('dateFrom').value;
            const dateToValue = document.getElementById('dateTo').value;

            function normalizeDate(dateString) {
                if (!dateString) return '';
                const d = new Date(dateString);
                if (Number.isNaN(d.getTime())) return '';
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function rangeFromFilter(filter) {
                const today = new Date();
                const todayISO = normalizeDate(today.toISOString().split('T')[0]);
                let from = '', to = '';

                switch (filter) {
                    case 'today':
                        from = to = todayISO;
                        break;
                    case 'yesterday':
                        const ayer = new Date(today);
                        ayer.setDate(today.getDate() - 1);
                        from = to = normalizeDate(ayer.toISOString().split('T')[0]);
                        break;
                    case 'week':
                        const firstDay = new Date(today);
                        firstDay.setDate(today.getDate() - today.getDay() + 1);
                        from = normalizeDate(firstDay.toISOString().split('T')[0]);
                        to = todayISO;
                        break;
                    case 'month':
                        from = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-01`;
                        to = todayISO;
                        break;
                    case 'last_month':
                        const firstPrev = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        const lastPrev = new Date(today.getFullYear(), today.getMonth(), 0);
                        from = normalizeDate(firstPrev.toISOString().split('T')[0]);
                        to = normalizeDate(lastPrev.toISOString().split('T')[0]);
                        break;
                }

                return {from, to};
            }

            mostrarLoading();

            try {
                const formData = new FormData();
                formData.append('action', 'search');
                if (query) formData.append('q', query);
                if (status) formData.append('estado', status);
                if (proveedor) formData.append('proveedor_id', proveedor);
                
                // Procesar rango de fechas
                let fromDate = '';
                let toDate = '';

                if (dateRange === 'custom') {
                    fromDate = normalizeDate(dateFromValue);
                    toDate = normalizeDate(dateToValue);
                } else {
                    const dates = rangeFromFilter(dateRange);
                    fromDate = dates.from;
                    toDate = dates.to;
                }

                if (fromDate) {
                    formData.append('date_from', `${fromDate} 00:00:00`);
                }
                if (toDate) {
                    formData.append('date_to', `${toDate} 23:59:59`);
                }

                if (dateRange && dateRange !== 'custom') {
                    formData.append('date_range', dateRange);
                }

                const response = await fetch('/inversiones-rojas/api/compras.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();

                if (data.success) {
                    actualizarTablaCompras(data.compras);
                } else {
                    console.warn('Search compras response error:', data);
                    mostrarToast(data.message ? data.message : 'Error al buscar compras', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarToast('Error de conexión', 'error');
            } finally {
                ocultarLoading();
            }
        }

        function formatMonedaDual(amount) {
            const monto = Number(amount) || 0;
            const usd = monto;
            const bs = monto * (window.TASA_CAMBIO || 1);
            const formatter = new Intl.NumberFormat('es-VE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            return {
                usd: '$' + formatter.format(usd),
                bs: 'Bs ' + formatter.format(bs)
            };
        }

        function actualizarTablaCompras(compras) {
            const tbody = document.getElementById('comprasTableBody');
            
            if (compras.length === 0) {
                tbody.innerHTML = `
                    <div class="empty-table" style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-box-open fa-3x" style="color: #ddd; margin-bottom: 15px;"></i>
                        <p>No se encontraron órdenes</p>
                    </div>
                `;
                return;
            }

            let html = '';
            compras.forEach(compra => {
                // Determinar estado
                let estado_class = '';
                let estado_texto = compra.estado_compra || 'PENDIENTE';
                
                switch((compra.estado_compra || '').toLowerCase()) {
                    case 'pendiente':
                        estado_class = 'status-pendiente';
                        break;
                    case 'recepcion':
                    case 'revision':
                        estado_class = 'status-recepcion';
                        estado_texto = 'RECEPCIÓN';
                        break;
                    case 'completada':
                        estado_class = 'status-completada';
                        break;
                    case 'incompleta':
                        estado_class = 'status-incompleta';
                        break;
                    case 'cancelada':
                        estado_class = 'status-cancelada';
                        break;
                    default:
                        estado_class = 'status-pendiente';
                }
                
                // Determinar alerta
                let alerta_class = '';
                let alerta_texto = '';
                let alerta_icono = '';
                
                if (compra.estado_compra === 'CANCELADA' || compra.activa === false) {
                    alerta_class = 'alerta-inhabilitada';
                    alerta_texto = 'Inhabilitada';
                    alerta_icono = '<i class="fas fa-ban"></i>';
                } else {
                    alerta_class = 'alerta-activa';
                    alerta_texto = 'Activa';
                    alerta_icono = '<i class="fas fa-check-circle"></i>';
                    
                    if (compra.estado_compra === 'PENDIENTE' && compra.fecha_estimada_entrega) {
                        const hoy = new Date();
                        const entrega = new Date(compra.fecha_estimada_entrega);
                        hoy.setHours(0,0,0,0);
                        entrega.setHours(0,0,0,0);
                        
                        if (entrega < hoy) {
                            alerta_class = 'alerta-atrasada';
                            alerta_texto = 'Atrasada';
                            alerta_icono = '<i class="fas fa-exclamation-triangle"></i>';
                        } else if (entrega.getTime() === hoy.getTime()) {
                            alerta_class = 'alerta-hoy';
                            alerta_texto = 'Hoy';
                            alerta_icono = '<i class="fas fa-clock"></i>';
                        } else {
                            const diffTime = entrega - hoy;
                            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                            if (diffDays <= 7) {
                                alerta_class = 'alerta-proxima';
                                alerta_texto = 'Próxima';
                                alerta_icono = '<i class="fas fa-calendar-check"></i>';
                            }
                        }
                    }
                }
                
                html += `
                    <div class="table-row" data-id="${compra.id}" data-codigo="${compra.codigo_compra || ''}">
                        <div data-label="Orden #">
                            <div class="order-code-block">
                                <span class="order-code">${compra.codigo_compra || 'N/A'}</span>
                                <span class="order-date">${compra.created_at ? new Date(compra.created_at).toLocaleDateString('es-ES') : ''}</span>
                            </div>
                        </div>
                        <div data-label="Proveedor" class="proveedor-cell">${compra.proveedor_nombre || 'N/A'}</div>
                        <div data-label="Entrega">${compra.fecha_estimada_entrega ? new Date(compra.fecha_estimada_entrega).toLocaleDateString('es-ES') : 'No definida'}</div>
                        <div data-label="Prod.">${compra.productos_count || 0}</div>
                        <div data-label="Unds.">${compra.total_unidades || 0}</div>
                        <div data-label="Total" class="total-value">
                            <span class="moneda-usd">${formatMonedaDual(compra.total).usd}</span>
                            <span class="moneda-bs">${formatMonedaDual(compra.total).bs}</span>
                        </div>
                        <div data-label="Estado"><span class="status-badge ${estado_class}">${estado_texto}</span></div>
                        <div data-label="Alerta"><span class="${alerta_class}">${alerta_icono} ${alerta_texto}</span></div>
                        <div class="actions-cell" data-label="Acciones">
                            <button class="action-btn view" onclick="verDetalleCompra(${compra.id})" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn status" onclick="cambiarEstadoCompra(${compra.id})" title="Cambiar estado">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                            ${compra.estado_compra !== 'CANCELADA' ? 
                                `<button class="action-btn disable" onclick="inhabilitarCompra(${compra.id})" title="Cancelar orden">
                                    <i class="fas fa-ban"></i>
                                </button>` : 
                                `<button class="action-btn enable" onclick="reactivarCompra(${compra.id})" title="Reactivar orden">
                                    <i class="fas fa-check"></i>
                                </button>`
                            }
                        </div>
                    </div>
                `;
            });
            
            tbody.innerHTML = html;
        }

        function clearFilters() {
            document.getElementById('comprasSearchInput').value = '';
            document.getElementById('dateFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('proveedorFilter').value = '';
            document.getElementById('customDateRange').style.display = 'none';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            location.reload();
        }

        function applyCustomDate() {
            const from = document.getElementById('dateFrom').value;
            const to = document.getElementById('dateTo').value;
            if (from && to) {
                document.getElementById('dateFilter').value = 'custom';
                searchCompras();
            } else {
                mostrarToast('Seleccione ambas fechas', 'warning');
            }
        }

        function exportReport() {
            mostrarToast('Funcionalidad de exportación en desarrollo', 'info');
        }

        // ========== FUNCIONES PARA MODAL DE PROVEEDOR ==========
        function showAddSupplierModal() {
            addSupplierModal.classList.add('active');
            // Limpiar validaciones previas
            InvValidate.clearField(document.getElementById('provRazon'));
            InvValidate.clearField(document.getElementById('provRif'));
            InvValidate.clearField(document.getElementById('provTelefono'));
            InvValidate.clearField(document.getElementById('provTelefonoAlt'));
            InvValidate.clearField(document.getElementById('provEmail'));
            document.getElementById('addSupplierMsg').style.display = 'none';
            setTimeout(() => document.getElementById('provRazon').focus(), 100);
        }

        function hideAddSupplierModal() {
            addSupplierModal.classList.remove('active');
            document.getElementById('addSupplierForm').reset();
            document.getElementById('addSupplierMsg').style.display = 'none';
            InvValidate.clearField(document.getElementById('provRazon'));
            InvValidate.clearField(document.getElementById('provRif'));
            InvValidate.clearField(document.getElementById('provTelefono'));
            InvValidate.clearField(document.getElementById('provTelefonoAlt'));
            InvValidate.clearField(document.getElementById('provEmail'));
        }

        // =================== FUNCIONES DE REPORTES ====================

        // Abrir modal de reportes
        function openReportsModal() {
            const modal = document.getElementById('reportsModal');
            if (modal) {
                modal.classList.add('active');
                // Resetear selección
                document.querySelectorAll('.report-card').forEach(card => card.classList.remove('selected'));
                document.getElementById('generateReportBtn').disabled = true;
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
                const response = await fetch('/inversiones-rojas/api/generate_report.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        report_type: reportType,
                        module: 'compras'
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
                Toast.success('Reporte generado exitosamente', '', 10000);
                
            } catch (error) {
                console.error('Error generando reporte:', error);
                Toast.error('Error al generar el reporte: ' + error.message, '', 10000);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // ========== EVENT LISTENERS ==========
        document.addEventListener('DOMContentLoaded', function() {
            // Modal de compra
            openCompraModalBtn.addEventListener('click', () => {
                compraModal.classList.add('active');
                initCompraModal();
            });

            closeCompraModalBtn.addEventListener('click', () => {
                if (selectedProducts.length > 0) {
                    if (confirm('¿Está seguro de cerrar? Se perderán los cambios no guardados.')) {
                        compraModal.classList.remove('active');
                    }
                } else {
                    compraModal.classList.remove('active');
                }
            });

            cancelCompraBtn.addEventListener('click', () => {
                if (selectedProducts.length > 0) {
                    if (confirm('¿Está seguro de cancelar? Se perderán todos los cambios.')) {
                        compraModal.classList.remove('active');
                    }
                } else {
                    compraModal.classList.remove('active');
                }
            });

            compraModal.addEventListener('click', (e) => {
                if (e.target === compraModal) {
                    if (selectedProducts.length > 0) {
                        if (confirm('¿Está seguro de cerrar? Se perderán los cambios no guardados.')) {
                            compraModal.classList.remove('active');
                        }
                    } else {
                        compraModal.classList.remove('active');
                    }
                }
            });

            // Modal de estado
            closeEstadoBtn.addEventListener('click', () => {
                estadoModal.classList.remove('active');
            });

            estadoModal.addEventListener('click', (e) => {
                if (e.target === estadoModal) {
                    estadoModal.classList.remove('active');
                }
            });

            // Modal de detalle
            closeDetalleBtn.addEventListener('click', cerrarDetalleModal);
            detalleModal.addEventListener('click', (e) => {
                if (e.target === detalleModal) {
                    cerrarDetalleModal();
                }
            });

            // Botón guardar
            saveOrderBtn.addEventListener('click', guardarOrden);

            // Proveedor select
            proveedorSelect.addEventListener('change', async () => {
                const selectedOption = proveedorSelect.options[proveedorSelect.selectedIndex];

                // ── Limpiar productos de la orden al cambiar proveedor ──
                if (selectedProducts.length > 0) {
                    selectedProducts = [];
                    selectedTableBody.innerHTML = '';
                    emptyOrder.style.display = 'block';
                    if (selectedTable) selectedTable.style.display = 'none';
                    updateOrderSummary();
                    updateCreateButtonState();
                }

                if (selectedOption && selectedOption.value) {
                    selectedProveedor = {
                        id: selectedOption.value,
                        razon: selectedOption.dataset.razon,
                        rif: selectedOption.dataset.rif,
                        direccion: selectedOption.dataset.direccion,
                        telefono: selectedOption.dataset.telefono,
                        contacto: selectedOption.dataset.contacto,
                        email: selectedOption.dataset.email
                    };

                    document.getElementById('proveedorInfo').style.display = 'block';
                    document.getElementById('proveedorNombre').textContent = selectedProveedor.razon;
                    document.getElementById('proveedorDireccion').textContent = selectedProveedor.direccion || '-';
                    document.getElementById('proveedorTelefono').textContent = selectedProveedor.telefono || '-';

                    selectProviderMessage.style.display = 'none';
                    productSelectionSection.style.display = 'block';

                    await cargarProductosPorProveedor(selectedProveedor.id);
                } else {
                    selectedProveedor = null;
                    document.getElementById('proveedorInfo').style.display = 'none';
                    selectProviderMessage.style.display = 'block';
                    productSelectionSection.style.display = 'none';
                    productsTableBody.innerHTML = '';
                }

                updateCreateButtonState();
            });

            // Búsqueda de productos
            productSearch.addEventListener('input', filterProducts);

            // Agregar producto desde tabla (event delegation)
            productsTableBody.addEventListener('click', (e) => {
                const addBtn = e.target.closest('.add-product-btn');
                if (addBtn && !addBtn.disabled) {
                    const product = {
                        id: addBtn.dataset.id,
                        codigo: addBtn.dataset.code,
                        nombre: addBtn.dataset.name,
                        precio: parseFloat(addBtn.dataset.price)
                    };
                    addProductToOrder(product);
                }
            });

            // Eventos en tabla de productos seleccionados
            selectedTableBody.addEventListener('click', (e) => {
                // Disminuir cantidad
                if (e.target.closest('.decrease')) {
                    const productId = e.target.closest('.decrease').dataset.id;
                    const product = selectedProducts.find(item => item.id == productId);
                    if (product) {
                        updateProductQuantity(product.id, product.cantidad - 1);
                    }
                }
                
                // Aumentar cantidad
                if (e.target.closest('.increase')) {
                    const productId = e.target.closest('.increase').dataset.id;
                    const product = selectedProducts.find(item => item.id == productId);
                    if (product) {
                        updateProductQuantity(product.id, product.cantidad + 1);
                    }
                }
                
                // Eliminar producto
                if (e.target.closest('.remove-item')) {
                    const productId = e.target.closest('.remove-item').dataset.id;
                    removeProductFromOrder(productId);
                }
            });

            // Cambio de cantidad por input
            selectedTableBody.addEventListener('change', (e) => {
                if (e.target.classList.contains('quantity-input')) {
                    const productId = e.target.dataset.id;
                    const newCantidad = parseInt(e.target.value);
                    
                    if (!isNaN(newCantidad) && newCantidad >= 1) {
                        updateProductQuantity(productId, newCantidad);
                    } else {
                        Toast.warning('La cantidad debe ser un número entero mayor a 0', '', 10000);
                        e.target.value = 1;
                        updateProductQuantity(productId, 1);
                    }
                }
            });

            // Estado cambia, mostrar/ocultar notas de incidencia
            estadoCompra.addEventListener('change', () => {
                if (estadoCompra.value === 'INCOMPLETA') {
                    notasIncidenciaGroup.style.display = 'block';
                } else {
                    notasIncidenciaGroup.style.display = 'none';
                }
            });

            // Fecha de entrega cambia, actualizar botón
            fechaEntrega.addEventListener('change', updateCreateButtonState);

            // Modal de proveedor
            document.getElementById('addSupplierBtn').addEventListener('click', showAddSupplierModal);
            document.getElementById('closeAddSupplierModal').addEventListener('click', hideAddSupplierModal);
            document.getElementById('cancelAddSupplier').addEventListener('click', hideAddSupplierModal);
            addSupplierModal.addEventListener('click', (e) => {
                if (e.target === addSupplierModal) hideAddSupplierModal();
            });

            // Formulario de proveedor
            document.getElementById('addSupplierForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                const provRazonInput = document.getElementById('provRazon');
                const provRifType = document.getElementById('provRifType');
                const provRifInput = document.getElementById('provRif');
                const provTelefonoInput = document.getElementById('provTelefono');
                const provTelefonoAltInput = document.getElementById('provTelefonoAlt');
                const provEmailInput = document.getElementById('provEmail');

                // Limpiar errores previos (usando InvValidate)
                InvValidate.clearField(provRazonInput);
                InvValidate.clearField(provRifInput);
                InvValidate.clearField(provTelefonoInput);
                InvValidate.clearField(provTelefonoAltInput);
                InvValidate.clearField(provEmailInput);

                // Normalizar RIF (tipo + número)
                const rifValue = provRifInput.value.replace(/\D/g, '');
                provRifInput.value = `${provRifType.value}-${rifValue}`;

                const validRazon = InvValidate.required(provRazonInput, 'Razón social');
                const validRif = InvValidate.rif(provRifInput, true);
                const validTelefono = InvValidate.telefono(provTelefonoInput, false);
                const validTelefonoAlt = InvValidate.telefono(provTelefonoAltInput, false);
                const validEmail = InvValidate.email(provEmailInput, false);

                if (!validRazon || !validRif || !validTelefono || !validTelefonoAlt || !validEmail) {
                    Toast.error('Corrige los campos destacados antes de continuar', '', 10000);
                    return;
                }

                // Validar unicidad (async)
                const rifUnique = await InvValidate.rifUnico(provRifInput);
                if (!rifUnique) {
                    Toast.error('El RIF ya está registrado en el sistema', '', 10000);
                    return;
                }

                if (provEmailInput.value.trim()) {
                    const emailUnique = await InvValidate.emailUnico(provEmailInput);
                    if (!emailUnique) {
                        Toast.error('El correo ya está registrado en el sistema', '', 10000);
                        return;
                    }
                }

                const saveBtn = document.getElementById('saveAddSupplier');
                const originalText = saveBtn.innerHTML;
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                
                document.getElementById('addSupplierMsg').style.display = 'none';

                try {
                    const fd = new FormData(this);
                    const response = await fetch('/inversiones-rojas/api/add_proveedor.php', {
                        method: 'POST',
                        body: fd
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Agregar al select
                        const opt = document.createElement('option');
                        opt.value = data.id;
                        opt.text = data.razon_social + (data.rif ? ' (' + data.rif + ')' : '');
                        opt.dataset.razon = data.razon_social;
                        opt.dataset.rif = data.rif || rif;
                        opt.dataset.direccion = document.getElementById('provDireccion').value;
                        opt.dataset.telefono = document.getElementById('provTelefono').value;
                        opt.dataset.contacto = document.getElementById('provContacto').value;
                        opt.dataset.email = document.getElementById('provEmail').value;
                        
                        proveedorSelect.appendChild(opt);
                        proveedorSelect.value = data.id;
                        proveedorSelect.dispatchEvent(new Event('change'));
                        
                        hideAddSupplierModal();
                        mostrarToast('Proveedor agregado exitosamente', 'success');
                    } else {
                        document.getElementById('addSupplierMsg').style.display = 'block';
                        document.getElementById('addSupplierMsg').style.background = '#f8d7da';
                        document.getElementById('addSupplierMsg').style.color = '#721c24';
                        document.getElementById('addSupplierMsg').textContent = data.message || 'Error al guardar proveedor';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    document.getElementById('addSupplierMsg').style.display = 'block';
                    document.getElementById('addSupplierMsg').style.background = '#f8d7da';
                    document.getElementById('addSupplierMsg').style.color = '#721c24';
                    document.getElementById('addSupplierMsg').textContent = 'Error de conexión';
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            });

            // Filtro de fechas
            document.getElementById('dateFilter').addEventListener('change', function() {
                document.getElementById('customDateRange').style.display = 
                    this.value === 'custom' ? 'block' : 'none';
            });

            // Búsqueda con Enter
            document.getElementById('comprasSearchInput').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    searchCompras();
                }
            });

            // Configurar fechas mín/máx
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('dateFrom').min = '2020-01-01';
            document.getElementById('dateFrom').max = today;
            document.getElementById('dateTo').min = '2020-01-01';
            document.getElementById('dateTo').max = today;

            // Reset inicial de filtros para que la tabla arranque en "Todos los estados"
            document.getElementById('statusFilter').value = '';
            document.getElementById('comprasSearchInput').value = '';
            document.getElementById('proveedorFilter').value = '';
            document.getElementById('dateFilter').value = '';
            document.getElementById('customDateRange').style.display = 'none';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';

            // Inicializar gráficos
            initCharts();
        });

        // ========== FUNCIONES PARA GRÁFICOS ==========
        function initCharts() {
            // Al inicializar, no dibujamos un Chart directamente para evitar conflicto
            // con updateComprasChart/updateProveedoresChart (icono de canvas ya en uso).
            // Las gráficas se actualizan desde updateComprasChart y updateProveedoresChart.
            return;
        }

        // ========== FUNCIONES PARA ACTUALIZAR GRÁFICAS ==========

        // Las gráficas se actualizan desde updateComprasChart y updateProveedoresChart

        function filterPendingOrders() {
            document.getElementById('statusFilter').value = 'PENDIENTE';
            document.getElementById('dateFilter').value = '';
            document.getElementById('proveedorFilter').value = '';
            document.getElementById('comprasSearchInput').value = '';
            document.getElementById('customDateRange').style.display = 'none';
            searchCompras();
        }

        function filterIncompleteOrders() {
    document.getElementById('statusFilter').value = 'INCOMPLETA';
    document.getElementById('dateFilter').value = '';
    document.getElementById('proveedorFilter').value = '';
    document.getElementById('comprasSearchInput').value = '';
    document.getElementById('customDateRange').style.display = 'none';
    searchCompras();
}

// ========== FUNCIÓN PARA PROBAR API ==========
async function testApiConnection() {
    try {
        const response = await fetch('/inversiones-rojas/api/compras.php?action=test');
        const text = await response.text();
        console.log('Respuesta de API (texto):', text.substring(0, 500));
        
        try {
            const data = JSON.parse(text);
            console.log('Respuesta de API (JSON):', data);
            if (data.success) {
                mostrarToast('Conexión API exitosa', 'success');
            } else {
                mostrarToast('Error: ' + data.message, 'error');
            }
        } catch (e) {
            console.error('No es JSON válido:', e);
            mostrarToast('Error: La API no devuelve JSON válido', 'error');
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        mostrarToast('Error de conexión con la API', 'error');
    }
}

// Agregar botón de prueba (opcional)
document.addEventListener('DOMContentLoaded', function() {

});

        // Exponer funciones globalmente
        window.editarCompra = editarCompra;
        window.verDetalleCompra = verDetalleCompra;
        window.cambiarEstadoCompra = cambiarEstadoCompra;
        window.inhabilitarCompra = inhabilitarCompra;
        window.reactivarCompra = reactivarCompra;
        window.searchCompras = searchCompras;
        window.clearFilters = clearFilters;
        window.applyCustomDate = applyCustomDate;
        window.filterPendingOrders = filterPendingOrders;
        window.filterIncompleteOrders = filterIncompleteOrders;
        window.exportReport = exportReport;
        window.cerrarDetalleModal = cerrarDetalleModal;
        window.procesarCambioEstado = procesarCambioEstado;
        window.procesarRecepcionCompleta = procesarRecepcionCompleta;
        window.procesarRecepcionIncompleta = procesarRecepcionIncompleta;
        window.actualizarResumenRecepcion = actualizarResumenRecepcion;
        window.editarDesdeDetalle = editarDesdeDetalle;
        window.imprimirOrdenCompra = imprimirOrdenCompra;
    </script>

    <script>
    // =================== EVENT LISTENERS PARA REPORTES ====================
    document.addEventListener('DOMContentLoaded', function() {
        // Botón Abrir Modal de Reportes
        const openReportsBtn = document.getElementById('openReportsModalBtn');
        if (openReportsBtn) {
            openReportsBtn.addEventListener('click', openReportsModal);
        }

        // Botón Cerrar y Cancelar del modal de reportes
        const closeReportsBtn = document.getElementById('closeReportsModal');
        if (closeReportsBtn) {
            closeReportsBtn.addEventListener('click', closeReportsModal);
        }

        const cancelReportsBtn = document.getElementById('cancelReportsBtn');
        if (cancelReportsBtn) {
            cancelReportsBtn.addEventListener('click', closeReportsModal);
        }

        const reportsModalOverlay = document.getElementById('reportsModal');
        if (reportsModalOverlay) {
            reportsModalOverlay.addEventListener('click', function(e) {
                if (e.target === reportsModalOverlay) {
                    closeReportsModal();
                }
            });
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
        
        // ========== FUNCIONES PARA ACTUALIZAR GRÁFICAS ==========

        let comprasChart    = null;
        let proveedoresChart = null;

        const CHART_COLORS = ['#1F9166','#28a745','#ffc107','#17a2b8','#dc3545','#6f42c1','#e83e8c','#fd7e14','#20c997','#6c757d'];

        async function updateComprasChart(period) {
            const ctx = document.getElementById('comprasChart');
            if (!ctx) return;

            // Indicador de carga
            ctx.style.opacity = '0.4';

            try {
                const resp = await fetch(`/inversiones-rojas/api/compras.php?action=charts_monthly&period=${period}`);
                const json = await resp.json();

                if (comprasChart) { comprasChart.destroy(); comprasChart = null; }

                if (!json.success || !json.data || json.data.length === 0) {
                    // Sin datos — mostrar gráfica vacía con mensaje
                    comprasChart = new Chart(ctx.getContext('2d'), {
                        type: 'line',
                        data: { labels: [], datasets: [{ label: 'Total Compras', data: [] }] },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                title: { display: true, text: 'Sin datos para este período', color: '#aaa', font: { size: 13 } }
                            }
                        }
                    });
                    return;
                }

                comprasChart = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: json.data.map(d => d.nombre_mes.trim()),
                        datasets: [{
                            label: 'Total Compras (Bs)',
                            data: json.data.map(d => parseFloat(d.total_compras)),
                            borderColor: '#1F9166',
                            backgroundColor: 'rgba(31,145,102,0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointBackgroundColor: '#1F9166',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: v => 'Bs ' + v.toLocaleString('es-VE') }
                            }
                        }
                    }
                });

            } catch (e) {
                console.error('Error gráfica mensual:', e);
            } finally {
                ctx.style.opacity = '1';
            }
        }

        async function updateProveedoresChart(period) {
            const ctx = document.getElementById('proveedoresChart');
            if (!ctx) return;

            ctx.style.opacity = '0.4';

            try {
                const resp = await fetch(`/inversiones-rojas/api/compras.php?action=charts_providers&period=${period}`);
                const json = await resp.json();

                if (proveedoresChart) { proveedoresChart.destroy(); proveedoresChart = null; }

                if (!json.success || !json.data || json.data.length === 0) {
                    proveedoresChart = new Chart(ctx.getContext('2d'), {
                        type: 'doughnut',
                        data: { labels: [], datasets: [{ data: [] }] },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                title: { display: true, text: 'Sin datos para este período', color: '#aaa', font: { size: 13 } }
                            }
                        }
                    });
                    return;
                }

                proveedoresChart = new Chart(ctx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: json.data.map(d => d.proveedor),
                        datasets: [{
                            data: json.data.map(d => parseFloat(d.total_compras)),
                            backgroundColor: CHART_COLORS.slice(0, json.data.length),
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 12 } } },
                            tooltip: {
                                callbacks: {
                                    label: ctx => {
                                        const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                                        const pct = total > 0 ? ((ctx.raw/total)*100).toFixed(1) : 0;
                                        return ` Bs ${ctx.raw.toLocaleString('es-VE')} (${pct}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });

            } catch (e) {
                console.error('Error gráfica proveedores:', e);
            } finally {
                ctx.style.opacity = '1';
            }
        }

        // Event listeners para los filtros de gráficas
        const monthFilter    = document.getElementById('chartMonthFilter');
        const providerFilter = document.getElementById('chartProveedorFilter');

        if (monthFilter) {
            monthFilter.addEventListener('change', function() {
                updateComprasChart(this.value);
            });
        }

        if (providerFilter) {
            providerFilter.addEventListener('change', function() {
                updateProveedoresChart(this.value);
            });
        }

        // Cargar gráficas con el valor inicial del selector al arrancar
        const initialMonth    = monthFilter?.value    ?? '3';
        const initialProvider = providerFilter?.value ?? '30';
        updateComprasChart(initialMonth);
        updateProveedoresChart(initialProvider);
    });
    </script>

<!-- Modal para Reportes de Compras -->
<div class="modal-overlay" id="reportsModal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h2><i class="fas fa-chart-bar"></i> Reportes de Compras</h2>
            <button class="modal-close" id="closeReportsModal">&times;</button>
        </div>
        
        <div class="modal-body" style="padding: 20px;">
            <p style="margin-bottom: 20px; color: #666;">Selecciona el tipo de reporte que deseas generar:</p>
            
         
            
            <div class="report-card" data-report="listado_proveedores">
                <i class="fas fa-truck"></i>
                <strong>Listado de Proveedores</strong>
                <p>Información completa de todos los proveedores activos</p>
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

<!-- Modal: Gestión de Proveedores -->
<div class="modal-overlay registro-modal" id="suppliersListModal" style="display: none;">
    <div class="modal registro-modal" style="width: 800px; max-width: 98%; max-height: 90vh;">
        <div class="modal-header">
            <h2><i class="fas fa-truck"></i> Gestión de Proveedores</h2>
            <button type="button" class="modal-close" onclick="closeSuppliersModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <!-- Barra de filtros -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" id="supplierSearchInput" class="form-control" placeholder="Buscar proveedor..." 
                               style="width: 100%;">
                    </div>
                    <div style="min-width: 130px;">
                        <select id="supplierStatusFilter" class="form-control" style="width: 100%;">
                            <option value="">Todos</option>
                            <option value="true">Activos</option>
                            <option value="false">Inactivos</option>
                        </select>
                    </div>
                    <div style="min-width: 140px;">
                        <input type="date" id="supplierDateFrom" class="form-control" style="width: 100%;" title="Desde">
                    </div>
                    <div style="min-width: 140px;">
                        <input type="date" id="supplierDateTo" class="form-control" style="width: 100%;" title="Hasta">
                    </div>
                </div>
            </div>
            
            <!-- Tabla de proveedores -->
            <div style="max-height: 450px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead style="position: sticky; top: 0; background: #f8f9fa;">
                        <tr style="border-bottom: 2px solid #dee2e6;">
                            <th style="padding: 12px; text-align: left; font-weight: 600;">RIF</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Razón Social</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Contacto</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Teléfono</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Email</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600;">Estado</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600; min-width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="suppliersTableBody">
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: #666;">
                                <i class="fas fa-spinner fa-spin"></i> Cargando proveedores...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" onclick="closeSuppliersModal()">
                <i class="fas fa-times"></i> Cerrar
            </button>
            <button type="button" class="btn btn-primary" onclick="showAddSupplierModal()">
                <i class="fas fa-plus"></i> Nuevo Proveedor
            </button>
        </div>
    </div>
</div>

<script>
document.getElementById('viewSuppliersBtn').addEventListener('click', function() {
    showSuppliersModal();
});

function showSuppliersModal() {
    const modal = document.getElementById('suppliersListModal');
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('active'), 10);
    loadSuppliersList();
}

function closeSuppliersModal() {
    const modal = document.getElementById('suppliersListModal');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

const suppliersModalEl = document.getElementById('suppliersListModal');
if (suppliersModalEl) {
    suppliersModalEl.addEventListener('click', function(e) {
        if (e.target === this) closeSuppliersModal();
    });
}

async function loadSuppliersList(search = '', status = '', dateFrom = '', dateTo = '') {
    const tbody = document.getElementById('suppliersTableBody');
    tbody.innerHTML = '<tr><td colspan="7" style="padding: 30px; text-align: center;"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';
    
    try {
        let url = '/inversiones-rojas/api/get_proveedores.php?';
        if (search) url += 'search=' + encodeURIComponent(search) + '&';
        if (status) url += 'estado=' + status + '&';
        if (dateFrom) url += 'fecha_from=' + dateFrom + '&';
        if (dateTo) url += 'fecha_to=' + dateTo + '&';
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.ok && data.proveedores.length > 0) {
            tbody.innerHTML = data.proveedores.map(p => `
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">${p.rif || '-'}</td>
                    <td style="padding: 12px;">${p.razon_social || '-'}</td>
                    <td style="padding: 12px;">${p.persona_contacto || '-'}</td>
                    <td style="padding: 12px;">${p.telefono_principal || '-'}</td>
                    <td style="padding: 12px;">${p.email || '-'}</td>
                    <td style="padding: 12px; text-align: center;">
                        <span style="padding: 4px 10px; border-radius: 12px; font-size: 12px; background: ${p.estado ? '#d4edda' : '#f8d7da'}; color: ${p.estado ? '#155724' : '#721c24'};">
                            ${p.estado ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td style="padding: 12px; text-align: center; white-space: nowrap;">
                        <div style="display: inline-flex; gap: 8px; justify-content: center; align-items: center; flex-wrap: nowrap;">
                            <button type="button" onclick="editSupplier(${p.id})" style="background: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" onclick="toggleSupplierStatus(${p.id}, ${!p.estado})" style="background: ${p.estado ? '#dc3545' : '#28a745'}; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;" title="${p.estado ? 'Inhabilitar' : 'Activar'}">
                                <i class="fas fa-${p.estado ? 'ban' : 'check'}"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" style="padding: 30px; text-align: center; color: #666;">No se encontraron proveedores</td></tr>';
        }
    } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="7" style="padding: 30px; text-align: center; color: #dc3545;">Error al cargar proveedores</td></tr>';
    }
}

document.getElementById('supplierSearchInput').addEventListener('input', function(e) {
    const status = document.getElementById('supplierStatusFilter').value;
    const dateFrom = document.getElementById('supplierDateFrom').value;
    const dateTo = document.getElementById('supplierDateTo').value;
    loadSuppliersList(e.target.value, status, dateFrom, dateTo);
});

document.getElementById('supplierStatusFilter').addEventListener('change', function(e) {
    const search = document.getElementById('supplierSearchInput').value;
    const dateFrom = document.getElementById('supplierDateFrom').value;
    const dateTo = document.getElementById('supplierDateTo').value;
    loadSuppliersList(search, e.target.value, dateFrom, dateTo);
});

document.getElementById('supplierDateFrom').addEventListener('change', function(e) {
    const search = document.getElementById('supplierSearchInput').value;
    const status = document.getElementById('supplierStatusFilter').value;
    const dateTo = document.getElementById('supplierDateTo').value;
    loadSuppliersList(search, status, e.target.value, dateTo);
});

document.getElementById('supplierDateTo').addEventListener('change', function(e) {
    const search = document.getElementById('supplierSearchInput').value;
    const status = document.getElementById('supplierStatusFilter').value;
    const dateFrom = document.getElementById('supplierDateFrom').value;
    loadSuppliersList(search, status, dateFrom, e.target.value);
});

function editSupplier(supplierId) {
    Toast.info('Función de edición en desarrollo', 'Editar Proveedor');
}

async function toggleSupplierStatus(supplierId, newStatus) {
    const action = newStatus ? 'activar' : 'inhabilitar';
    if (!confirm(`¿Está seguro de ${action} este proveedor?`)) return;
    
    try {
        const formData = new FormData();
        formData.append('id', supplierId);
        formData.append('estado', newStatus ? '1' : '0');
        
        const response = await fetch('/inversiones-rojas/api/update_proveedor_estado.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.ok) {
            Toast.success(data.message || `Proveedor ${newStatus ? 'activado' : 'inhabilitado'} correctamente`);
            loadSuppliersList(
                document.getElementById('supplierSearchInput').value,
                document.getElementById('supplierStatusFilter').value,
                document.getElementById('supplierDateFrom').value,
                document.getElementById('supplierDateTo').value
            );
        } else {
            Toast.error(data.error || 'Error al actualizar estado');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Error de conexión');
    }
}
</script>
</body>
</html>