<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';
$base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// Verificar si el usuario está logueado (usando los nombres de session de process_login.php)
if (!isset($_SESSION['user_id'])) {
    // Redirigir al login si no está logueado
    header('Location: ' . $base_url . '/login.php');
    exit();
}

// Obtener el nombre del usuario logueado de la sesión
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

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ventas - INVERSIONES ROJAS 2016. C.A.</title>
    <script>
        var APP_BASE = '<?php echo $base_url; ?>';
        var TASA_CAMBIO = <?php echo getTasaCambio(); ?>;
        console.log('Tasa de cambio cargada:', TASA_CAMBIO);
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?php echo $base_url; ?>/public/js/inv-notifications.js"></script>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/layouts/ventas.css">
    <style>
    
    /* ================================================================
       VENTAS — Estilos inline (modal + componentes)
       El CSS de layout de página viene de ventas.css externo.
       Este bloque hace el modal autosuficiente sin depender de caché.
       ================================================================ */

    /* ── Moneda dual ── */
    .moneda-bs  { color: #1F9166; font-weight: 600; }
    .moneda-usd { color: #6c757d; font-size: 0.9em; }

    /* ── Tabla de ventas ── */
    .sales-table-wrapper {
        overflow-x: auto;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.05);
    }
    .sales-table { width: 100%; border-collapse: collapse; min-width: 900px; }
    .sales-table .table-header,
    .sales-table .table-row {
        display: grid;
        grid-template-columns: 1.4fr 1.4fr 1.1fr 1.1fr 1.1fr 1.3fr .8fr;
        align-items: center;
        gap: 6px;
        min-height: 42px;
    }
    .sales-table .table-header {
        background: #f6f8f9;
        border-bottom: 1px solid #e0e6ea;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
        font-size: .76rem;
    }
    .sales-table .table-header > div,
    .sales-table .table-row > div {
        padding: 8px 10px;
        font-size: .86rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sales-table .table-row { border-bottom: 1px solid #edf1f5; }
    .sales-table .table-row:hover { background: #f0faf6; }
    .sales-table .col-acciones { text-align: center; }
    .sales-table .col-total {
        text-align: right;
        font-size: .82rem;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }
    .sales-table .col-total .moneda-usd { color: #1F9166; font-weight: 600; font-size: .9rem; }
    .sales-table .col-total .moneda-bs  { color: #6c757d; font-size: .8rem; }
    .sales-table .col-estado { text-align: center; }
    .sales-table .factura-code {
        font-size: .78rem; font-weight: 600; display: block;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .sales-table .factura-vendedor {
        font-size: .72rem; color: #6c757d; display: block;
        margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* ── Botones de acción en tabla ── */
    .btn-view-invoice, .btn-disable-venta {
        padding: 5px 8px; border: none; border-radius: 4px; cursor: pointer;
        font-size: 12px; transition: all .2s;
        display: flex; align-items: center; justify-content: center;
        width: 32px; height: 32px;
    }
    .btn-view-invoice  { background: #3498db; color: white; }
    .btn-view-invoice:hover { background: #2980b9; }
    .btn-disable-venta { background: #e74c3c; color: white; }
    .btn-disable-venta:hover { background: #c0392b; }
    .btn-disable-venta:disabled { background: #95a5a6; cursor: not-allowed; }

    /* ── Botones acción tabla — estilo unificado con compras ── */
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 7px;
        border: none;
        cursor: pointer;
        color: #fff;
        font-size: 14px;
        transition: transform 0.1s ease, box-shadow 0.1s ease;
        box-shadow: 0 2px 6px rgba(0,0,0,.06);
    }
    .action-btn i { font-size: 15px; }
    .action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.15); }
    .action-btn:disabled { opacity: .55; cursor: not-allowed; transform: none; }
    .action-btn.view    { background: #1F9166; }
    .action-btn.disable { background: #ef4444; }
    .action-btn.enable  { background: #10b981; }

    /* Badge estado inhabilitado */
    .status-badge.status-inhabilitado {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* ================================================================
       MODAL OVERLAY — Base (aplica a TODOS los modales)
       ================================================================ */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.68);
        z-index: 30000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.25s ease, visibility 0.25s ease;
    }
    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    body.modal-open { overflow: hidden; }

    /* ── Contenedor genérico del modal ── */
    .modal {
        background: #fff;
        border-radius: 14px;
        width: min(1100px, 100%);
        max-height: calc(100dvh - 32px);
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.22);
        animation: modalSlideUp 0.25s ease;
        overflow: hidden;
        position: relative;
    }
    @keyframes modalSlideUp {
        from { transform: translateY(36px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }

    /* ── Header del modal ── */
    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 13px 20px;
        background: #1F9166;
        color: #fff;
        flex-shrink: 0;
        gap: 10px;
        border-bottom: 1px solid #187c56;
    }
    .modal-header h2 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .modal-close {
        width: 30px; height: 30px;
        border-radius: 50%;
        background: rgba(255,255,255,.2);
        border: none; color: #fff; font-size: 20px;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        line-height: 1; flex-shrink: 0;
        transition: background 0.2s;
    }
    .modal-close:hover { background: rgba(255,255,255,.35); }

    /* ── Footer del modal ── */
    .modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 20px;
        background: #fff;
        border-top: 1px solid #e9ecef;
        flex-shrink: 0;
    }
    .modal-footer .btn {
        padding: 9px 18px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13.5px;
        cursor: pointer;
        border: none;
        display: flex; align-items: center; gap: 6px;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .btn-cancel  { background: #f1f3f4; color: #555; border: 1px solid #ddd !important; }
    .btn-cancel:hover  { background: #e3e5e7; }
    .btn-complete { background: #1F9166; color: #fff; }
    .btn-complete:hover { background: #187c56; }
    .btn-print  { background: #3498db; color: #fff; }
    .btn-print:hover { background: #2980b9; }

    /* ================================================================
       MODAL NUEVA VENTA — Layout de dos paneles
       ================================================================ */
    #saleModal .modal-body {
        flex: 1;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 330px;
        min-height: 0;
        overflow: hidden;
    }

    /* ── Panel izquierdo: Productos + Carrito ── */
    .left-panel {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-right: 1px solid #e9ecef;
        background: #fff;
    }

    /* Búsqueda */
    .search-section {
        padding: 12px 18px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        flex-shrink: 0;
    }
    .search-box { display: flex; gap: 8px; align-items: center; }
    .search-box input {
        flex: 1; min-width: 0;
        padding: 9px 13px;
        border: 1px solid #ddd; border-radius: 6px;
        font-size: 13.5px;
        transition: border-color 0.2s;
    }
    .search-box input:focus {
        outline: none; border-color: #1F9166;
        box-shadow: 0 0 0 2px rgba(31,145,102,.12);
    }
    .search-box button {
        padding: 9px 14px;
        background: #1F9166; color: #fff;
        border: none; border-radius: 6px;
        font-size: 13.5px; font-weight: 600; cursor: pointer;
        white-space: nowrap; flex-shrink: 0;
        display: flex; align-items: center; gap: 5px;
        transition: background 0.2s;
    }
    .search-box button:hover { background: #187c56; }

    /* Tabla de productos */
    .products-table-container {
        flex: 1;
        overflow-y: auto;
        padding: 12px 18px;
        min-height: 0;
    }
    .products-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
        background: #fff;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
    }
    .products-table thead { position: sticky; top: 0; z-index: 5; background: #f8f9fa; }
    .products-table th {
        padding: 9px 8px;
        font-size: 11.5px; font-weight: 600; color: #555;
        text-transform: uppercase; letter-spacing: .03em;
        border-bottom: 2px solid #e9ecef; white-space: nowrap;
    }
    .products-table td { padding: 8px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    .products-table tbody tr:hover { background: #f8faf9; }
    .products-table tbody tr.selected { background: rgba(31,145,102,.07); }

    /* Carrito */
    .cart-section {
        border-top: 1px solid #e9ecef;
        background: #f8f9fa;
        display: flex; flex-direction: column;
        min-height: 0; max-height: 230px; flex-shrink: 0;
    }
    .cart-section h3 {
        padding: 10px 18px 6px; margin: 0;
        font-size: 0.88rem; color: #333;
        display: flex; align-items: center; gap: 6px; flex-shrink: 0;
    }
    .cart-table-container {
        flex: 1; overflow-y: auto; padding: 0 18px 10px; min-height: 0;
    }
    .cart-table {
        width: 100%; border-collapse: collapse; font-size: 12px; background: #fff;
        border-radius: 6px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.05);
    }
    .cart-table thead { position: sticky; top: 0; background: #f8f9fa; z-index: 5; }
    .cart-table th {
        padding: 8px 7px; font-size: 11px; font-weight: 600; color: #555;
        border-bottom: 2px solid #e9ecef; white-space: nowrap;
    }
    .cart-table td { padding: 7px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }

    /* Estado vacío */
    .empty-state { text-align: center; padding: 20px 12px; color: #aaa; background: #fff; border-radius: 6px; }
    #emptyCart {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        min-height: 80px; background: #fff; border-radius: 6px; margin-top: 4px;
    }
    .empty-state i, #emptyCart i { font-size: 28px; margin-bottom: 7px; display: block; color: #ddd; }
    .empty-state p { font-size: 12.5px; margin: 0 0 3px; }

    /* ── Panel derecho: Cliente + Pago + Comprobante ── */
    .right-panel {
        background: #f8f9fa;
        display: flex; flex-direction: column;
        overflow-y: auto;
        padding: 14px; gap: 12px;
        border-left: 1px solid #e9ecef;
        min-width: 0;
    }
    .customer-section, .payment-section {
        background: #fff; border-radius: 8px;
        padding: 12px 13px; border: 1px solid #e9ecef; flex-shrink: 0;
    }
    .section-title {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 10px; font-size: 0.82rem; font-weight: 700; color: #2c3e50; gap: 6px;
    }
    .section-title span { display: flex; align-items: center; gap: 6px; }
    .section-title button {
        padding: 4px 9px; background: #3498db; color: #fff;
        border: none; border-radius: 4px; font-size: 11px; cursor: pointer;
        display: flex; align-items: center; gap: 3px;
        transition: background 0.2s; white-space: nowrap; flex-shrink: 0;
    }
    .section-title button:hover { background: #2980b9; }

    .config-group { margin-bottom: 8px; }
    .config-group label {
        display: block; margin-bottom: 4px; font-size: 11px; font-weight: 600;
        color: #777; text-transform: uppercase; letter-spacing: .03em;
    }
    .config-select {
        width: 100%; padding: 7px 9px; border: 1px solid #ddd; border-radius: 5px;
        font-size: 12.5px; background: #fff; cursor: pointer; transition: border-color 0.2s;
    }
    .config-select:focus { outline: none; border-color: #1F9166; }

    .client-select-with-search { position: relative; }
    #clientSearch {
        margin-top: 6px; padding: 7px 9px; width: 100%;
        border: 1px solid #ddd; border-radius: 5px; font-size: 12.5px;
        transition: border-color 0.2s;
    }
    #clientSearch:focus { outline: none; border-color: #1F9166; }
    .client-info-row { display: flex; gap: 4px; margin-bottom: 3px; font-size: 12px; line-height: 1.4; }
    .client-info-label { font-weight: 600; color: #555; min-width: 64px; flex-shrink: 0; }
    .client-info-value { color: #444; }

    /* Efectivo */
    .cash-section {
        margin-top: 8px; padding: 10px; background: #f8f9fa;
        border-radius: 6px; border: 1px solid #e9ecef;
    }
    .cash-input-group { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; }
    .cash-input-group label { min-width: 120px; font-size: 11.5px; font-weight: 600; color: #555; flex-shrink: 0; }
    .cash-input-group input { flex: 1; min-width: 0; padding: 6px 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 12.5px; }
    .change-display { margin-top: 6px; padding: 7px; background: #fff; border-radius: 4px; text-align: center; font-size: 12px; font-weight: 700; color: #1F9166; }

    /* Pago múltiple */
    .multiple-payment-section {
        display: none; margin-top: 8px; border: 1px solid #e9ecef;
        border-radius: 6px; padding: 10px; background: #fff; font-size: 12px;
    }
    .payment-method-item { display: flex; align-items: center; gap: 7px; margin-bottom: 7px; padding-bottom: 7px; border-bottom: 1px solid #f5f5f5; }
    .payment-method-select { flex: 1; padding: 6px 7px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px; }
    .payment-amount-input { width: 90px; padding: 6px 7px; border: 1px solid #ddd; border-radius: 4px; font-size: 12px; text-align: right; }
    .remove-payment-method { background: none; border: none; color: #e74c3c; cursor: pointer; font-size: 14px; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .add-payment-method-btn { width: 100%; padding: 6px; background: #f8f9fa; border: 1px dashed #ddd; border-radius: 4px; color: #666; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; gap: 4px; transition: all .2s; }
    .add-payment-method-btn:hover { background: #e9ecef; border-color: #bbb; }
    .payment-summary { margin-top: 7px; padding: 7px; background: #f8f9fa; border-radius: 4px; font-size: 12px; }
    .payment-total { font-weight: 600; color: #1F9166; }
    .payment-difference { font-weight: 600; }
    .payment-difference.positive { color: #1F9166; }
    .payment-difference.negative { color: #e74c3c; }

    /* ── Comprobante de factura ── */
    .invoice-comprobante {
        background: #fff; border-radius: 8px; border: 1px solid #e0e0e0;
        overflow: hidden; font-family: 'Courier New', monospace;
        font-size: 10.5px; line-height: 1.35; flex-shrink: 0;
    }
    .invoice-header-comprobante { background: #f8f9fa; padding: 10px 12px; text-align: center; border-bottom: 1px dashed #ccc; }
    .invoice-header-comprobante h3 { margin: 0 0 3px; font-size: 12.5px; font-weight: 700; color: #222; }
    .invoice-header-comprobante p  { margin: 1px 0; font-size: 9.5px; color: #666; }
    .invoice-body-comprobante { padding: 10px 12px; }
    .invoice-line { display: flex; justify-content: space-between; gap: 6px; margin-bottom: 3px; font-size: 10.5px; }
    .invoice-line > span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .invoice-line-title { font-weight: 600; color: #333; margin: 5px 0 3px; border-bottom: 1px solid #eee; padding-bottom: 2px; font-size: 10.5px; }
    .invoice-line-dashed { border-bottom: 1px dashed #ccc; margin: 5px 0; }
    .invoice-line-center { text-align: center; font-weight: 700; font-size: 11px; margin: 4px 0; letter-spacing: .05em; }
    .invoice-line-items { margin: 4px 0; max-height: 110px; overflow-y: auto; }
    .invoice-line-item { display: flex; justify-content: space-between; gap: 4px; margin-bottom: 2px; font-size: 10px; }
    .invoice-line-item-detail { padding-left: 8px; font-size: 9px; color: #666; margin-bottom: 2px; }
    .invoice-total { border-top: 1px dashed #ccc; margin-top: 5px; padding-top: 5px; font-weight: 700; font-size: 11px; }
    .invoice-footer-comprobante { text-align: center; padding: 8px; border-top: 1px dashed #ccc; background: #f8f9fa; font-style: italic; color: #666; font-size: 9.5px; }

    /* ── Elementos de producto / carrito ── */
    .product-code  { font-family: monospace; font-size: 11.5px; color: #777; }
    .product-name  { font-weight: 500; color: #333; font-size: 12.5px; }
    .product-price { font-weight: 600; color: #1F9166; font-size: 12.5px; display: flex; flex-direction: column; gap: 1px; }
    .product-stock { font-size: 11.5px; color: #666; }
    .product-stock.low { color: #e74c3c; font-weight: 700; }
    .add-product-btn {
        padding: 5px 10px; background: #1F9166; color: #fff; border: none;
        border-radius: 4px; font-size: 12px; cursor: pointer;
        display: flex; align-items: center; gap: 3px; white-space: nowrap;
        transition: background .2s;
    }
    .add-product-btn:hover { background: #187c56; }
    .add-product-btn:disabled { background: #ccc; cursor: not-allowed; }
    .quantity-controls { display: flex; align-items: center; gap: 4px; }
    .quantity-btn {
        width: 28px; height: 28px; border: 1px solid #ddd; border-radius: 4px;
        background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 600; transition: background .15s; flex-shrink: 0;
    }
    .quantity-btn:hover { background: #f0f0f0; }
    .quantity-btn:active { background: #e9ecef; transform: scale(.95); }
    .quantity-input { width: 44px; padding: 4px; text-align: center; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; font-weight: 600; }
    .quantity-input:focus { outline: none; border-color: #1F9166; }
    .remove-item {
        background: none; border: none; color: #e74c3c; cursor: pointer;
        width: 24px; height: 24px; border-radius: 4px;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; transition: background .15s;
    }
    .remove-item:hover { background: rgba(231,76,60,.1); }

    /* ── Scrollbars finos ── */
    .products-table-container::-webkit-scrollbar,
    .cart-table-container::-webkit-scrollbar,
    .right-panel::-webkit-scrollbar,
    .invoice-line-items::-webkit-scrollbar { width: 5px; }
    .products-table-container::-webkit-scrollbar-track,
    .cart-table-container::-webkit-scrollbar-track,
    .right-panel::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
    .products-table-container::-webkit-scrollbar-thumb,
    .cart-table-container::-webkit-scrollbar-thumb,
    .right-panel::-webkit-scrollbar-thumb { background: #c8c8c8; border-radius: 3px; }

    /* ================================================================
       MODALES DE REGISTRO (clientes, pagos) — sobreescriben base
       ================================================================ */
    .modal-overlay.registro-modal { z-index: 35000; padding: 20px; }
    .modal.registro-modal { width: 500px; max-width: 95%; max-height: 85vh; overflow-y: auto; margin: auto; }
    .modal.registro-modal .modal-header { background: #f8f9fa; color: #333; border-bottom: 1px solid #e9ecef; }
    .modal.registro-modal .modal-close { background: rgba(0,0,0,.1); color: #666; }
    .modal.registro-modal .modal-close:hover { background: rgba(0,0,0,.2); }
    .modal.registro-modal .modal-body { min-height: auto; padding: 25px; display: block; overflow-y: auto; }
    .modal.registro-modal .modal-footer { justify-content: flex-end; gap: 10px; }
    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #333; font-size: 14px; }
    .form-control { width: 100%; padding: 9px 13px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; color: #333; transition: border-color .2s; }
    .form-control:focus { outline: none; border-color: #1F9166; box-shadow: 0 0 0 2px rgba(31,145,102,.1); }
    .form-control::placeholder { color: #999; }
    textarea.form-control { min-height: 80px; resize: vertical; }
    .checkbox-group { display: flex; align-items: center; gap: 10px; }
    .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: #1F9166; cursor: pointer; }

    /* ── Modal de reportes ── */
    #reportsModal .modal-body { display: block !important; max-height: 70vh; overflow-y: auto; padding: 20px; }
    .report-card {
        padding: 14px 18px; border: 2px solid #e9ecef; border-radius: 10px;
        margin-bottom: 10px; cursor: pointer; transition: all .25s;
        background: #f8f9fa; display: flex; align-items: center; gap: 14px;
    }
    .report-card:hover { border-color: #6c757d; background: #e9ecef; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,.1); }
    .report-card.selected { background: #6c757d; color: white; border-color: #6c757d; }
    .report-card i { font-size: 22px; width: 28px; text-align: center; }
    .report-card strong { display: block; margin-bottom: 3px; font-size: 15px; }
    .report-card p { margin: 0; font-size: 13px; opacity: .8; }
    .modal-footer .btn-report { background: #1F9166; color: white; }
    .modal-footer .btn-report:hover { background: #159652; }

    /* ── Animaciones ── */
    @keyframes slideIn  { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }

    /* ================================================================
       RESPONSIVE — Modal Nueva Venta
       ================================================================ */

    /* Tablet landscape */
    @media (max-width: 1100px) {
        #saleModal .modal-body { grid-template-columns: minmax(0, 1fr) 290px; }
    }

    /* Tablet portrait: apilar paneles */
    @media (max-width: 860px) {
        #saleModal.modal { width: 100%; max-height: calc(100dvh - 24px); border-radius: 12px; }
        #saleModal .modal-body {
            grid-template-columns: 1fr;
            overflow-y: auto;
            max-height: calc(100dvh - 56px - 58px);
            -webkit-overflow-scrolling: touch;
        }
        .left-panel  { border-right: none; border-bottom: 1px solid #e9ecef; overflow: visible; }
        .right-panel { border-left: none; border-top: 1px solid #e9ecef; overflow-y: visible; max-height: none; }
        .products-table-container { max-height: 240px; }
        .cart-section { max-height: 210px; }
    }

    /* Móvil: pantalla completa */
    @media (max-width: 640px) {
        #saleModal.modal-overlay { padding: 0; align-items: flex-end; }
        #saleModal .modal {
            width: 100vw; max-height: 100dvh; height: 100dvh;
            border-radius: 0;
            animation: modalMobileUp .3s ease;
        }
        @keyframes modalMobileUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        #saleModal .modal-body {
            grid-template-columns: 1fr;
            height: calc(100dvh - 54px - 60px);
            overflow-y: auto; max-height: none;
        }
        .modal-footer { flex-wrap: wrap; padding: 10px 12px; gap: 8px; }
        .modal-footer .btn {
            flex: 1 1 calc(50% - 4px);
            min-height: 44px; justify-content: center;
            font-size: 13px; padding: 10px 8px;
        }
        .config-select, #clientSearch,
        .search-box input, .cash-input-group input { font-size: 16px !important; min-height: 44px; }
        .search-box button, .add-product-btn { min-height: 42px; }
        /* Ocultar columna Código en móvil */
        .products-table th:nth-child(1),
        .products-table td:nth-child(1),
        .cart-table th:nth-child(1),
        .cart-table td:nth-child(1) { display: none; }
    }

    @media (max-width: 380px) {
        .modal-header h2 { font-size: .95rem; }
        .modal-footer .btn { font-size: 12px; padding: 10px 6px; }
    }
    </style>
</head>
<body>

    <?php
    // Incluir la conexión a la base de datos
    require_once __DIR__ . '/../../models/database.php';
    
    // Obtener datos reales de la base de datos
    $pdo = Database::getInstance();
    
    // Obtener productos con stock
    $stmt = $pdo->query("
        SELECT p.*, c.nombre as categoria_nombre, 
               pr.razon_social as proveedor_nombre 
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
        WHERE p.estado = true
        ORDER BY p.nombre
    ");
    $productos = $stmt->fetchAll();
    
    // Clientes presenciales: los registrados en tienda tienen dirección física
    // Los clientes digitales (pedidos online) no tienen dirección → quedan excluidos
    $stmt = $pdo->query("
        SELECT id, nombre_completo, cedula_rif, telefono_principal, direccion
        FROM clientes
        WHERE estado = true
          AND direccion IS NOT NULL
          AND TRIM(direccion) != ''
        ORDER BY nombre_completo
    ");
    $clientes = $stmt->fetchAll();
    
    // Obtener clientes de facturación
 // Clientes de facturación (usando la misma tabla de clientes)
$clientes_facturacion = []; // Array vacío o usa clientes regulares si necesitas
    
    // Obtener métodos de pago
    $stmt = $pdo->query("
        SELECT id, nombre, descripcion 
        FROM metodos_pago 
        WHERE estado = true 
        ORDER BY nombre
    ");
    $metodos_pago = $stmt->fetchAll();
    
    // Obtener estados de venta únicos para el filtro
    $stmt = $pdo->query("SELECT DISTINCT estado_venta FROM ventas ORDER BY estado_venta");
    $estados_venta = $stmt->fetchAll();
    
    // Obtener métodos de pago para el filtro
    $stmt = $pdo->query("SELECT id, nombre FROM metodos_pago WHERE estado = true ORDER BY nombre");
    $metodos_filtro = $stmt->fetchAll();

    // Obtener categorías para el filtro de ventas
    $stmt = $pdo->query("SELECT id, nombre FROM categorias WHERE estado = true ORDER BY nombre");
    $categorias_filtro = $stmt->fetchAll();
    
    // Obtener ventas recientes (inicialmente las 10 más recientes)
    $stmt = $pdo->query("
        SELECT v.*, c.nombre_completo as cliente_nombre, 
               mp.nombre as metodo_pago_nombre, u.nombre_completo as vendedor
        FROM ventas v
        LEFT JOIN clientes c ON v.cliente_id = c.id
        LEFT JOIN metodos_pago mp ON v.metodo_pago_id = mp.id
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        ORDER BY v.created_at DESC 
       
    ");
    $ventas_recientes = $stmt->fetchAll();
    
    // Estadísticas reales - mostrar el último mes con datos (ventas completadas)
    $stmt = $pdo->query("
        SELECT 
            DATE_TRUNC('month', created_at) as mes,
            TO_CHAR(DATE_TRUNC('month', created_at), 'YYYY-MM') as mes_formato,
            SUM(total) as total_ventas
        FROM ventas 
        WHERE estado_venta = 'COMPLETADA'
        GROUP BY DATE_TRUNC('month', created_at)
        ORDER BY mes DESC
        LIMIT 1
    ");
    $ultimo_mes = $stmt->fetch();
    $mes_seleccionado = $ultimo_mes['mes_formato'] ?? date('Y-m');
    
    // Obtener estadísticas del último mes con datos
    $stmt = $pdo->query("
        SELECT 
            COALESCE(SUM(total), 0) as ventas_mes,
            COUNT(*) as transacciones_mes,
            COUNT(DISTINCT cliente_id) as clientes_unicos
        FROM ventas 
        WHERE DATE_TRUNC('month', created_at) = TO_DATE('$mes_seleccionado-01', 'YYYY-MM-DD')
          AND estado_venta = 'COMPLETADA'
    ");
    $estadisticas_mes = $stmt->fetch();
    
    // Obtener datos de hoy
    $stmt = $pdo->query("
        SELECT 
            COALESCE(SUM(total), 0) as ventas_hoy,
            COUNT(*) as transacciones_hoy,
            COUNT(DISTINCT cliente_id) as clientes_hoy
        FROM ventas 
        WHERE DATE(created_at) = CURRENT_DATE
          AND estado_venta = 'COMPLETADA'
    ");
    $estadisticas_hoy = $stmt->fetch();
    
    // Unir los datos con los nombres correctos para el HTML
    $estadisticas = [
        'ventas_mes' => $estadisticas_mes['ventas_mes'],
        'transacciones_hoy' => $estadisticas_hoy['transacciones_hoy'],
        'clientes_hoy' => $estadisticas_hoy['clientes_hoy'] ?? 0,
        'ventas_hoy' => $estadisticas_hoy['ventas_hoy']
    ];
    
    // Gráfico de ventas mensuales
    $stmt = $pdo->query("
        SELECT 
            DATE_TRUNC('month', created_at) as mes,
            TO_CHAR(DATE_TRUNC('month', created_at), 'Month') as nombre_mes_raw,
            SUM(total) as total_ventas
        FROM ventas
        WHERE created_at >= CURRENT_DATE - INTERVAL '3 months'
          AND estado_venta = 'COMPLETADA'
        GROUP BY DATE_TRUNC('month', created_at)
        ORDER BY mes
    ");
    $ventas_mensuales = $stmt->fetchAll();
    
    // Traducir meses al español
    $meses_es = [
        'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
        'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
        'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
        'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
    ];
    foreach ($ventas_mensuales as &$v) {
        $v['nombre_mes'] = $meses_es[trim($v['nombre_mes_raw'])] ?? $v['nombre_mes_raw'];
    }
    
    // Métodos de pago más usados
    $stmt = $pdo->query("
        SELECT 
            mp.nombre,
            COUNT(*) as cantidad,
            ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 1) as porcentaje
        FROM ventas v
        JOIN metodos_pago mp ON v.metodo_pago_id = mp.id
        WHERE v.created_at >= CURRENT_DATE - INTERVAL '30 days'
        GROUP BY mp.nombre
        ORDER BY cantidad DESC
        LIMIT 4
    ");
    $metodos_populares = $stmt->fetchAll();
    ?>

    <div class="admin-content">
        <!-- Stats Cards con datos reales -->
        <div class="sales-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>
                        <?php 
                        $precios = formatearMonedaDual($estadisticas['ventas_hoy']);
                        echo '<span class="moneda-usd">' . $precios['usd'] . '</span>';
                        echo '<span class="moneda-bs">' . $precios['bs'] . '</span>';
                        ?>
                    </h3>
                    <p>Ventas Hoy</p>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        12.5%
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $estadisticas['transacciones_hoy']; ?></h3>
                    <p>Transacciones Hoy</p>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        8.2%
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $estadisticas['clientes_hoy']; ?></h3>
                    <p>Clientes Atendidos</p>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        5.7%
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>
                        <?php 
                        $precios = formatearMonedaDual($estadisticas['ventas_mes']);
                        echo '<span class="moneda-usd">' . $precios['usd'] . '</span>';
                        echo '<span class="moneda-bs">' . $precios['bs'] . '</span>';
                        ?>
                    </h3>
                    <p>Ventas del Mes</p>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        15.3%
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section Compacta -->
        <div class="charts-grid">
            <!-- Gráfica de Ventas Mensuales Compacta -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Ventas Mensuales</h3>
                    <div class="chart-actions">
                        <select class="chart-filter">
                            <option>Últimos 3 meses</option>
                            <option>Este año</option>
                        </select>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Top Métodos de Pago -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3>Métodos de Pago Más Usados</h3>
                </div>
                <div class="chart-wrapper">
                    <canvas id="paymentChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Barra de búsqueda y filtros MEJORADA Y FUNCIONAL -->
        <div class="search-filters" style="margin: 20px 0;">
            <form id="salesSearchForm" class="search-box" onsubmit="event.preventDefault(); searchSales();" style="display:flex; gap:8px; align-items:center; flex-wrap: wrap;">
                <input id="salesSearchInput" type="search" name="q" placeholder="Buscar ventas por código, cliente..." style="flex:1; min-width:200px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;" />

                <select id="dateFilter" name="date_range" class="filter-select" style="min-width:160px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="">Todos los días</option>
                    <option value="today">Hoy</option>
                    <option value="yesterday">Ayer</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mes</option>
                    <option value="last_month">Mes anterior</option>
                    <option value="custom">Rango personalizado</option>
                </select>

                <select id="statusFilter" name="estado" class="filter-select" style="min-width:140px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estados_venta as $estado): ?>
                    <option value="<?php echo htmlspecialchars($estado['estado_venta']); ?>">
                        <?php echo htmlspecialchars($estado['estado_venta']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select id="categoryFilter" name="categoria" class="filter-select" style="min-width:160px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias_filtro as $categoria): ?>
                    <option value="<?php echo $categoria['id']; ?>">
                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select id="paymentFilter" name="metodo_pago" class="filter-select" style="min-width:160px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="">Todos los métodos</option>
                    <?php foreach ($metodos_filtro as $metodo): ?>
                    <option value="<?php echo $metodo['id']; ?>">
                        <?php echo htmlspecialchars($metodo['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <button type="button" class="btn btn-secondary" onclick="searchSales()" style="padding: 10px 20px; background: #1F9166; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.3s; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-search"></i> Buscar
                </button>

                <button type="button" class="btn btn-outline" onclick="clearFilters()" style="padding: 10px 20px; background: #f8f9fa; color: #666; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.3s;">
                    Limpiar
                </button>
            </form>
            
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
        <div class="sales-actions">
            <div class="action-buttons">
                <button class="btn btn-primary" id="openSaleModalBtn">
                    <i class="fas fa-cash-register"></i>
                    Nueva Venta
                </button>
             
                <button class="btn btn-report" id="openReportsModalBtn">
                    <i class="fas fa-chart-bar"></i>
                    Reportes
                </button>
                <button class="btn btn-secondary" id="viewClientsBtn" onclick="showClientsModal()">
                    <i class="fas fa-users"></i>
                    ver clientes
                </button>
            </div>
        </div>

       <!-- Sales Table con datos reales y búsqueda dinámica -->
<div class="sales-table-wrapper">
<div class="sales-table" id="salesTableContainer">
    <div class="table-header">
        <div class="col-factura">Factura</div>
        <div class="col-cliente">Cliente</div>
        <div class="col-fecha">Fecha</div>
        <div class="col-total">Total</div>
        <div class="col-metodo">Método Pago</div>
        <div class="col-estado">Estado</div>
        <div class="col-acciones">Acciones</div>
    </div>
    
    <div id="salesTableBody">
        <?php foreach ($ventas_recientes as $venta): ?>
        <div class="table-row" data-venta-id="<?php echo $venta['id']; ?>">
            <div class="col-factura">
                <span class="factura-code"><?php echo htmlspecialchars($venta['codigo_venta']); ?></span>
                <span class="factura-vendedor"><?php echo htmlspecialchars($venta['vendedor'] ?? 'Sistema'); ?></span>
            </div>
            <div class="col-cliente"><?php echo htmlspecialchars($venta['cliente_nombre'] ?? '—'); ?></div>
            <div class="col-fecha"><?php echo date('d/m/Y H:i', strtotime($venta['created_at'])); ?></div>
            <div class="col-total">
                                        <?php 
                                        $precios = formatearMonedaDual($venta['total']);
                                        echo '<span class="moneda-usd">' . $precios['usd'] . '</span>';
                                        echo '<span class="moneda-bs">' . $precios['bs'] . '</span>';
                                        ?>
                                    </div>
            <div class="col-metodo"><?php echo htmlspecialchars($venta['metodo_pago_nombre'] ?? 'No especificado'); ?></div>
            <div class="col-estado">
                <span class="status-badge status-<?php echo strtolower($venta['estado_venta']); ?>">
                    <?php echo htmlspecialchars($venta['estado_venta']); ?>
                </span>
            </div>
            <div class="col-acciones actions-cell">
                <div class="table-actions">
                    <!-- Ver Factura -->
                    <button class="action-btn view" data-venta-id="<?php echo $venta['id']; ?>"
                            title="Ver Factura">
                        <i class="fas fa-file-invoice"></i>
                    </button>

                    <!-- Inhabilitar / Reactivar (igual que compras) -->
                    <?php if ($venta['estado_venta'] !== 'INHABILITADO'): ?>
                    <button class="action-btn disable"
                            data-venta-id="<?php echo $venta['id']; ?>"
                            data-codigo="<?php echo htmlspecialchars($venta['codigo_venta']); ?>"
                            title="Inhabilitar Venta">
                        <i class="fas fa-ban"></i>
                    </button>
                    <?php else: ?>
                    <button class="action-btn enable"
                            data-venta-id="<?php echo $venta['id']; ?>"
                            data-codigo="<?php echo htmlspecialchars($venta['codigo_venta']); ?>"
                            title="Reactivar Venta">
                        <i class="fas fa-check"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</div>

    <!-- Modal de Nueva Venta -->
    <div class="modal-overlay" id="saleModal">
        <div class="modal">
            <div class="modal-header">
                <h2><i class="fas fa-cash-register"></i> Nueva Venta</h2>
                <button class="modal-close" id="closeSaleModal">&times;</button>
            </div>
            
            <div class="modal-body">
                <!-- Panel izquierdo: Productos y Carrito -->
                <div class="left-panel">
                    <!-- Búsqueda de productos -->
                    <div class="search-section">
                        <div class="search-box">
                            <input type="text" id="productSearch" placeholder="Ingrese código de barras o nombre del producto...">
                            <button id="searchBtn">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tabla de productos -->
                    <div class="products-table-container">
                        <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-size: 14px; color: #666;">
                                Productos: <span id="productCount"><?php echo count($productos); ?></span>
                            </div>
                        </div>
                        
                        <table class="products-table" id="productsTable">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td class="product-code"><?php echo htmlspecialchars($producto['codigo_interno']); ?></td>
                                    <td class="product-name"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td class="product-price">
                                        <?php 
                                        $precios = formatearMonedaDual($producto['precio_venta']);
                                        echo '<span class="moneda-usd">' . $precios['usd'] . '</span>';
                                        echo '<span class="moneda-bs">' . $precios['bs'] . '</span>';
                                        ?>
                                    </td>
                                    <td class="product-stock <?php echo $producto['stock_actual'] <= $producto['stock_minimo'] ? 'low' : ''; ?>">
                                        <?php echo $producto['stock_actual']; ?> unidades
                                    </td>
                                    <td>
                                        <button class="add-product-btn" data-id="<?php echo $producto['id']; ?>" 
                                                data-code="<?php echo htmlspecialchars($producto['codigo_interno']); ?>"
                                                data-name="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                                data-price="<?php echo $producto['precio_venta']; ?>"
                                                data-stock="<?php echo $producto['stock_actual']; ?>">
                                            <i class="fas fa-plus"></i> Agregar
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Carrito de compras -->
                    <div class="cart-section">
                        <h3><i class="fas fa-shopping-cart"></i> Productos en la Venta</h3>
                        <div class="cart-table-container">
                            <table class="cart-table" id="cartTable">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio</th>
                                        <th>Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="cartTableBody">
                                    <!-- Los productos del carrito se agregarán aquí -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Estado vacío del carrito -->
                        <div class="empty-state" id="emptyCart">
                            <i class="fas fa-shopping-cart"></i>
                            <p>No hay productos en la venta</p>
                            <p style="font-size: 14px; margin-top: 10px;">Busca y agrega productos desde la tabla superior</p>
                        </div>
                    </div>
                </div>
                
                <!-- Panel derecho: Cliente, Pago y Factura Preview -->
                <div class="right-panel">
                    <!-- Selección de cliente -->
                    <div class="customer-section">
                        <div class="section-title">
                            <span><i class="fas fa-user"></i> Cliente</span>
                            <button id="addClientBtn">
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>
                        
                        <div class="config-group">
                            <label for="clientSelect">Seleccionar Cliente</label>
                            <div class="client-select-with-search">
                                <select id="clientSelect" class="config-select">
                                    <option value="">-- Seleccionar Cliente --</option>
                                    <!-- Clientes regulares -->
                                    <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id']; ?>"
                                            data-source="clientes"
                                            data-cedula="<?php echo htmlspecialchars($cliente['cedula_rif']); ?>"
                                            data-telefono="<?php echo htmlspecialchars($cliente['telefono_principal']); ?>"
                                            data-direccion="<?php echo htmlspecialchars($cliente['direccion']); ?>">
                                        <?php echo htmlspecialchars($cliente['nombre_completo'] . ' (' . $cliente['cedula_rif'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <!-- Clientes de facturación -->
                                    <?php foreach ($clientes_facturacion as $cf): ?>
                                    <option value="cf-<?php echo $cf['id']; ?>"
                                            data-source="facturacion"
                                            data-cedula="<?php echo htmlspecialchars($cf['cedula']); ?>"
                                            data-telefono="<?php echo htmlspecialchars($cf['telefono']); ?>"
                                            data-direccion="">
                                        <?php echo htmlspecialchars($cf['nombre'] . (empty($cf['cedula']) ? '' : ' (' . $cf['cedula'] . ')')); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="text" id="clientSearch" 
                                   style="margin-top: 8px; padding: 8px 12px; width: 100%; border: 1px solid #ddd; border-radius: 6px; font-size: 13px;" 
                                   placeholder="Buscar cliente por nombre o cédula...">
                        </div>
                        
                        <div id="clientInfo" style="display: none;">
                            <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef; margin-top: 10px;">
                                <div style="font-weight: 600; margin-bottom: 5px; color: #333;" id="clientName"></div>
                                <div style="font-size: 13px; color: #666; line-height: 1.4;">
                                    <div><strong>Cédula:</strong> <span id="clientCedula"></span></div>
                                    <div><strong>Teléfono:</strong> <span id="clientPhone"></span></div>
                                    <div><strong>Dirección:</strong> <span id="clientAddress"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Selección de método de pago -->
                    <div class="payment-section">
                        <div class="section-title">
                            <span><i class="fas fa-credit-card"></i> Método de Pago</span>
                            <button id="addPaymentMethodBtn">
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>
                        
                        <div class="config-group">
                            <label for="paymentMethod">Tipo de Pago *</label>
                            <select id="paymentMethod" class="config-select">
                                <option value="">-- Seleccionar método --</option>
                                <?php foreach ($metodos_pago as $metodo): 
                                    $moneda = $metodo['moneda'] ?? 'AMBOS';
                                ?>
                                <option value="<?php echo $metodo['id']; ?>" data-moneda="<?php echo $moneda; ?>"><?php echo htmlspecialchars($metodo['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>

                        </div>
                        

                        
                        <!-- Sección de efectivo -->
                        <div id="cashSection" style="display: none;">
                            <div class="cash-section">
                                <div class="cash-input-group">
                                    <label>Efectivo Recibido (USD):</label>
                                    <input type="number" id="cashReceived" placeholder="0.00" step="0.01" min="0">
                                </div>
                                <div class="change-display" id="changeDisplay">
                                    Vuelto: Bs 0.00
                                </div>
                                <div style="font-size: 0.75rem; color: #666; margin-top: 4px; text-align: center;">
                                    Equivalente: Bs <span id="cashReceivedBs">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- COMPROBANTE DE FACTURA -->
                    <div class="invoice-comprobante">
                        <div class="invoice-header-comprobante">
                            <h3>INVERSIONES ROJAS 2016. C.A.</h3>
                            <p>RIF: J-40888806-8</p>
                            <p>AV ARAGUA LOCAL NRO 286 SECTOR ANDRES ELOY BLANCO, MARACAY ARAGUA ZONA POSTAL 2102</p>
                        </div>
                        
                        <div class="invoice-body-comprobante">
                            <div class="invoice-line">
                                <span><strong>Código:</strong> V-<span id="invCode">001</span></span>
                                <span id="invDateComprobante"><?php echo date('Y-m-d'); ?></span>
                            </div>
                            
                            <div class="invoice-line">
                                <span><strong>Cliente:</strong> <span id="invClientNameComprobante">Sin cliente</span></span>
                                <span><strong>Hora:</strong> <span id="invTimeComprobante"><?php echo date('H:i:s'); ?></span></span>
                            </div>
                            
                            <div class="invoice-line">
                                <span><strong>Cédula:</strong> <span id="invClientDocComprobante">00000000</span></span>
<span><strong>Vendedor:</strong> <span id="invSellerComprobante"><?php echo isset($nombre_usuario) ? htmlspecialchars($nombre_usuario) : 'Sistema'; ?></span></span>                            </div>
                            <div class="invoice-line-center">
                                COMPROBANTE DE PAGO
                            </div>
                            
                            <div class="invoice-line-dashed"></div>
                            
                            <div class="invoice-line-title">Productos/Servicios:</div>
                            <div class="invoice-line-items" id="invoiceItemsComprobante">
                                <!-- Productos se agregarán aquí -->
                                <div class="invoice-line-item">
                                    <span>No hay productos</span>
                                    <span>$ 0.00</span>
                                </div>
                            </div>
                            
                            <div class="invoice-line-dashed"></div>
                            
                            <div class="invoice-line-title">Método de Pago:</div>
                            <div id="paymentMethodsComprobante">
                                <!-- Métodos de pago se mostrarán aquí -->
                                <div class="invoice-line-item">
                                    <span>Por definir</span>
                                    <span>$ 0.00</span>
                                </div>
                            </div>
                            
                            <div class="invoice-line-dashed"></div>
                            
                            <div class="invoice-line-item">
                                <span><strong>Subtotal:</strong></span>
                                <span><strong>Bs <span id="invSubtotalComprobante">0.00</span></strong></span>
                            </div>

                            <div class="invoice-line-item">
                                <span><strong>IVA (16%):</strong></span>
                                <span><strong>Bs <span id="invTaxComprobante">0.00</span></strong></span>
                            </div>

                            <div class="invoice-line-item invoice-total">
                                <span><strong>TOTAL:</strong></span>
                                <span><strong>Bs <span id="invTotalComprobante">0.00</span></strong></span>
                            </div>
                            
                            <div class="invoice-line-item" style="font-size: 0.7rem; color: #666; margin-top: 4px;">
                                <span>Tasa: Bs <span id="invTasaComprobante">0.00</span> por $1</span>
                            </div>
                            
                            <div class="invoice-line-dashed"></div>
                        </div>
                        
                        <div class="invoice-footer-comprobante">
                            ¡Gracias por su preferencia!
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-cancel" id="cancelSale">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn btn-complete" id="completeSale">
                    <i class="fas fa-check"></i> Completar Venta
                </button>
            </div>
        </div>
    </div>

<script>
    // Variables globales
    let cart = [];
    const IVA_RATE = 0.16;
    let selectedClient = null;
    let selectedPaymentMethod = null;

    // Elementos del DOM - Modal de Venta
    const saleModal = document.getElementById('saleModal');
    const openSaleModalBtn = document.getElementById('openSaleModalBtn');
    const closeSaleModalBtn = document.getElementById('closeSaleModal');
    const cancelSaleBtn = document.getElementById('cancelSale');
    const completeSaleBtn = document.getElementById('completeSale');
    const productsTableBody = document.getElementById('productsTableBody');
    const cartTableBody = document.getElementById('cartTableBody');
    const emptyCart = document.getElementById('emptyCart');
    const cartTable = document.querySelector('.cart-table');
    const productCount = document.getElementById('productCount');
    const productSearch = document.getElementById('productSearch');
    const searchBtn = document.getElementById('searchBtn');
    
    // Cliente
    const clientSelect = document.getElementById('clientSelect');
    const clientSearch = document.getElementById('clientSearch');
    const clientInfo = document.getElementById('clientInfo');
    const clientName = document.getElementById('clientName');
    const clientCedula = document.getElementById('clientCedula');
    const clientPhone = document.getElementById('clientPhone');
    const clientAddress = document.getElementById('clientAddress');
    const addClientBtn = document.getElementById('addClientBtn');
    
    // Pago
    const paymentMethodSelect = document.getElementById('paymentMethod');
    const cashSection = document.getElementById('cashSection');
    const cashReceived = document.getElementById('cashReceived');
    const changeDisplay = document.getElementById('changeDisplay');
    
    // Comprobante
    const invCode = document.getElementById('invCode');
    const invDateComprobante = document.getElementById('invDateComprobante');
    const invClientNameComprobante = document.getElementById('invClientNameComprobante');
    const invTimeComprobante = document.getElementById('invTimeComprobante');
    const invClientDocComprobante = document.getElementById('invClientDocComprobante');
    const invSellerComprobante = document.getElementById('invSellerComprobante');
    const invoiceItemsComprobante = document.getElementById('invoiceItemsComprobante');
    const paymentMethodsComprobante = document.getElementById('paymentMethodsComprobante');
    const invSubtotalComprobante = document.getElementById('invSubtotalComprobante');
    const invTaxComprobante = document.getElementById('invTaxComprobante');
    const invTotalComprobante = document.getElementById('invTotalComprobante');

    // Elementos de búsqueda de ventas
    const salesSearchInput = document.getElementById('salesSearchInput');
    const dateFilter = document.getElementById('dateFilter');
    const customDateRange = document.getElementById('customDateRange');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const statusFilter = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const paymentFilter = document.getElementById('paymentFilter');
    const salesTableBody = document.getElementById('salesTableBody');

    // Inicializar modal de venta
    function initSaleModal() {
        cart = [];
        selectedClient = null;
        selectedPaymentMethod = null;
        updateCartUI();
        updateInvoiceComprobante();
        updateDateTime();
    }

    // ==================== FUNCIONES DEL CARRITO ====================
    function addToCart(product) {
        // Validar antes de agregar
        if (!product.price || Number(product.price) <= 0) {
            Toast.error('No se puede vender un producto sin precio válido.', 'Precio inválido');
            return false;
        }

        const existingItem = cart.find(item => item.id == product.id);
        
        if (existingItem) {
            if (existingItem.quantity < product.stock) {
                existingItem.quantity += 1;
            } else {
                Toast.warning(`No hay suficiente stock. Solo quedan ${product.stock} unidades.`, 'Stock insuficiente');
                return false;
            }
        } else {
            cart.push({
                id: product.id,
                code: product.code,
                name: product.name,
                price: product.price,
                quantity: 1,
                stock: product.stock
            });
        }
        
        updateCartUI();
        updateInvoiceComprobante();
        return true;
    }

    function removeFromCart(productId) {
        cart = cart.filter(item => item.id != productId);
        updateCartUI();
        updateInvoiceComprobante();
        
        // Rehabilitar botón en la tabla de productos
        const addBtn = productsTableBody.querySelector(`.add-product-btn[data-id="${productId}"]`);
        if (addBtn) {
            addBtn.disabled = false;
            addBtn.innerHTML = '<i class="fas fa-plus"></i> Agregar';
        }
    }

    function updateQuantity(productId, newQuantity) {
        if (newQuantity < 1) {
            removeFromCart(productId);
            return;
        }
        
        const item = cart.find(item => item.id == productId);
        if (item) {
            if (newQuantity > item.stock) {
                Toast.warning(`No hay suficiente stock. Solo quedan ${item.stock} unidades.`, 'Stock insuficiente');
                return;
            }
            item.quantity = newQuantity;
        }
        
        updateCartUI();
        updateInvoiceComprobante();
    }

    function calculateTotals() {
        let subtotal = 0;
        
        cart.forEach(item => {
            subtotal += item.price * item.quantity;
        });
        
        const tax = subtotal * IVA_RATE;
        const total = subtotal + tax;
        
        return { subtotal, tax, total };
    }
    
    function getPaymentCurrency() {
        const selectedOption = paymentMethodSelect?.options[paymentMethodSelect.selectedIndex];
        const moneda = selectedOption?.dataset?.moneda || 'AMBOS';
        const selectedText = selectedOption?.textContent?.toLowerCase() || '';
        
        if (moneda === 'USD') return 'USD';
        if (moneda === 'BS') return 'BS';
        if (selectedText.includes('efectivo $')) return 'USD';
        return 'BS';
    }
    
    function calculateTotalsInCurrency() {
        const totals = calculateTotals();
        const currency = getPaymentCurrency();
        const tasa = window.TASA_CAMBIO || 35.50;
        
        let subtotalBs, taxBs, totalBs;
        let subtotalUsd, taxUsd, totalUsd;
        
        if (currency === 'USD') {
            subtotalUsd = totals.subtotal;
            taxUsd = totals.tax;
            totalUsd = totals.total;
            subtotalBs = subtotalUsd * tasa;
            taxBs = taxUsd * tasa;
            totalBs = totalUsd * tasa;
        } else {
            subtotalBs = totals.subtotal * tasa;
            taxBs = totals.tax * tasa;
            totalBs = totals.total * tasa;
            subtotalUsd = totals.subtotal;
            taxUsd = totals.tax;
            totalUsd = totals.total;
        }
        
        return {
            subtotal: totals.subtotal, tax: totals.tax, total: totals.total,
            subtotalBs, taxBs, totalBs,
            subtotalUsd, taxUsd, totalUsd,
            currency, tasa
        };
    }
    
    function getPaymentAmount() {
        const currency = document.getElementById('paymentCurrency')?.value || 'BS';
        const totals = calculateTotalsInCurrency();
        
        if (currency === 'USD') {
            return totals.totalUsd;
        } else if (currency === 'TARJETA') {
            return totals.totalBs;
        }
        return totals.totalBs;
    }

    function updateCartUI() {
        // Mostrar/ocultar carrito vacío
        if (cart.length === 0) {
            emptyCart.style.display = 'block';
            cartTable.style.display = 'none';
        } else {
            emptyCart.style.display = 'none';
            cartTable.style.display = 'table';
            
            // Actualizar tabla del carrito
            cartTableBody.innerHTML = '';
            
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="product-code">${item.code}</td>
                    <td class="product-name">${item.name}</td>
                    <td>
                        <div class="quantity-controls">
                            <button class="quantity-btn decrease" data-id="${item.id}">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="quantity-input" value="${item.quantity}" min="1" max="${item.stock}" data-id="${item.id}">
                            <button class="quantity-btn increase" data-id="${item.id}">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </td>
                    <td class="product-price">
                        <span class="moneda-usd">$${item.price.toFixed(2)}</span>
                        <span class="moneda-bs">Bs ${(item.price * (window.TASA_CAMBIO || 35.50)).toFixed(0)}</span>
                    </td>
                    <td class="product-price">
                        <span class="moneda-usd">$${itemTotal.toFixed(2)}</span>
                        <span class="moneda-bs">Bs ${(itemTotal * (window.TASA_CAMBIO || 35.50)).toFixed(0)}</span>
                    </td>
                    <td>
                        <button class="remove-item" data-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                cartTableBody.appendChild(row);
            });
        }
        
    }

    // ==================== FUNCIONES DE FECHA Y HORA ====================
    function updateDateTime() {
        const now = new Date();
        const dateStr = now.toISOString().split('T')[0];
        const timeStr = now.toTimeString().split(' ')[0];
        
        invDateComprobante.textContent = dateStr;
        invTimeComprobante.textContent = timeStr;
        
        // Generar código de venta único
        invCode.textContent = String(now.getTime()).slice(-4);
    }

    // ==================== FUNCIONES DEL COMPROBANTE ====================
    function updateInvoiceComprobante() {
        const totals = calculateTotals();
        const tasa = window.TASA_CAMBIO || 400;
        
        // Convertir totales a Bs
        const subtotalBs = totals.subtotal * tasa;
        const taxBs = totals.tax * tasa;
        const totalBs = totals.total * tasa;
        
        // Actualizar tasa en el comprobante
        const invTasaComprobante = document.getElementById('invTasaComprobante');
        if (invTasaComprobante) {
            invTasaComprobante.textContent = tasa.toFixed(2);
        }

        // Actualizar información del cliente
        if (selectedClient) {
            invClientNameComprobante.textContent = selectedClient.name;
            invClientDocComprobante.textContent = selectedClient.cedula || "00000000";
        } else {
            invClientNameComprobante.textContent = "Sin cliente";
            invClientDocComprobante.textContent = "00000000";
        }

        // Actualizar vendedor
        invSellerComprobante.textContent = "<?php echo isset($nombre_usuario) ? htmlspecialchars($nombre_usuario) : 'Sistema'; ?>";

        // Actualizar items del comprobante (en Bs)
        invoiceItemsComprobante.innerHTML = '';

        if (cart.length === 0) {
            const emptyItem = document.createElement('div');
            emptyItem.className = 'invoice-line-item';
            emptyItem.innerHTML = `
                <span>No hay productos</span>
                <span>Bs 0.00</span>
            `;
            invoiceItemsComprobante.appendChild(emptyItem);
        } else {
            cart.forEach(item => {
                const itemTotalBs = item.price * item.quantity * tasa;
                const itemRow = document.createElement('div');
                itemRow.className = 'invoice-line-item';
                itemRow.innerHTML = `
                    <span>${item.name} (x${item.quantity})</span>
                    <span>Bs ${itemTotalBs.toFixed(2)}</span>
                `;
                invoiceItemsComprobante.appendChild(itemRow);
            });
        }

        // Actualizar métodos de pago en el comprobante (en Bs)
        paymentMethodsComprobante.innerHTML = '';

        if (selectedPaymentMethod) {
            const selectedOption = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
            if (selectedOption) {
                const paymentRow = document.createElement('div');
                paymentRow.className = 'invoice-line-item';
                paymentRow.innerHTML = `
                    <span>${selectedOption.textContent}:</span>
                    <span>Bs ${totalBs.toFixed(2)}</span>
                `;
                paymentMethodsComprobante.appendChild(paymentRow);

                // Si es efectivo, mostrar el cambio
                if (selectedOption.textContent.toLowerCase().includes('efectivo') && cashReceived.value) {
                    const cash = parseFloat(cashReceived.value);
                    const cashBs = cash * tasa;
                    if (cashBs > totalBs) {
                        const changeRow = document.createElement('div');
                        changeRow.className = 'invoice-line-item';
                        changeRow.innerHTML = `
                            <span>Vuelto:</span>
                            <span>Bs ${(cashBs - totalBs).toFixed(2)}</span>
                        `;
                        paymentMethodsComprobante.appendChild(changeRow);
                    }
                }
            }
        } else {
            const paymentRow = document.createElement('div');
            paymentRow.className = 'invoice-line-item';
            paymentRow.innerHTML = `
                <span>Por definir</span>
                <span>Bs 0.00</span>
            `;
            paymentMethodsComprobante.appendChild(paymentRow);
        }

        // Actualizar totales (en Bs)
        invSubtotalComprobante.textContent = subtotalBs.toFixed(2);
        invTaxComprobante.textContent = taxBs.toFixed(2);
        invTotalComprobante.textContent = totalBs.toFixed(2);

        // Calcular vuelto si es pago en efectivo
        calculateChange();
    }

    // Calcular cambio para pagos en efectivo
    function calculateChange() {
        const tasa = window.TASA_CAMBIO || 400;
        if (cashReceived.value) {
            const cash = parseFloat(cashReceived.value);
            const cashBs = cash * tasa;
            const totalBs = calculateTotals().total * tasa;

            if (cashBs >= totalBs) {
                changeDisplay.textContent = `Vuelto: Bs ${(cashBs - totalBs).toFixed(2)}`;
            } else {
                changeDisplay.textContent = `Vuelto: Bs 0.00`;
            }
        } else {
            changeDisplay.textContent = `Vuelto: Bs 0.00`;
        }
    }

    // ==================== FUNCIONES DE BÚSQUEDA DE VENTAS ====================
    async function searchSales() {
        const query = salesSearchInput.value.trim();
        const dateRange = dateFilter.value;
        const status = statusFilter.value;
        const category = categoryFilter.value;
        const paymentMethod = paymentFilter.value;
        const dateFromValue = dateFrom.value;
        const dateToValue = dateTo.value;

        // Mostrar indicador de carga
        salesTableBody.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top: 10px;">Buscando ventas...</p>
            </div>
        `;

        try {
            // Enviar petición al servidor usando FormData
            const formData = new FormData();
            if (query) formData.append('q', query);
            if (dateRange && dateRange !== 'custom') formData.append('date_range', dateRange);
            if (dateRange === 'custom' && dateFromValue) formData.append('date_from', dateFromValue);
            if (dateRange === 'custom' && dateToValue) formData.append('date_to', dateToValue);
            if (status) formData.append('estado', status);
            if (category) formData.append('categoria', category);
            if (paymentMethod) formData.append('metodo_pago', paymentMethod);

            const response = await fetch('/inversiones-rojas/api/search_sales.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();

            if (data.success) {
                updateSalesTable(data.ventas);
            } else {
                const msg = 'Error al buscar ventas: ' + (data.message || 'Error desconocido');
                showErrorMessage(msg);
                Toast.error(msg, 'Error');
            }
        } catch (error) {
            console.error('Error en búsqueda:', error);
            const msg = 'Error de conexión con el servidor';
            showErrorMessage(msg);
            Toast.error(msg, 'Error');
        }
    }

    function updateSalesTable(ventas) {
        if (ventas.length === 0) {
            salesTableBody.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-search fa-2x"></i>
                    <p style="margin-top: 10px;">No se encontraron ventas</p>
                    <p style="font-size: 14px;">Intenta con otros criterios de búsqueda</p>
                </div>
            `;
            return;
        }

        let html = '';
        ventas.forEach(venta => {
            const statusClass = venta.estado_venta ? venta.estado_venta.toLowerCase().replace(/\s+/g, '-') : 'pendiente';
            const fecha = venta.created_at ? formatDate(venta.created_at) : '';
            
            html += `
                <div class="table-row" data-venta-id="${venta.id}">
                    <div class="col-factura">
                        <span class="factura-code">${venta.codigo_venta || 'N/A'}</span>
                        <span class="factura-vendedor">${venta.vendedor || 'Sistema'}</span>
                    </div>
                    <div class="col-cliente">${venta.cliente_nombre || '—'}</div>
                    <div class="col-fecha">${fecha}</div>
                    <div class="col-total">
                        <span class="moneda-usd">$${venta.monto_usd ? parseFloat(venta.monto_usd).toFixed(2) : parseFloat(venta.total || 0).toFixed(2)}</span>
                        <span class="moneda-bs">Bs ${(venta.monto_bs ? parseFloat(venta.monto_bs) : parseFloat(venta.total || 0) * (window.TASA_CAMBIO || 35.50)).toFixed(0)}</span>
                    </div>
                    <div class="col-metodo">${venta.metodo_pago_nombre || 'No especificado'}</div>
                    <div class="col-estado">
                        <span class="status-badge status-${statusClass}">
                            ${venta.estado_venta || 'Pendiente'}
                        </span>
                    </div>
                    <div class="col-acciones actions-cell">
                        <div class="table-actions">
                            <button class="action-btn view" data-venta-id="${venta.id}"
                                    title="Ver Factura">
                                <i class="fas fa-file-invoice"></i>
                            </button>
                            ${venta.estado_venta !== 'INHABILITADO' ? `
                                <button class="action-btn disable" data-venta-id="${venta.id}" data-codigo="${venta.codigo_venta}"
                                        title="Inhabilitar Venta">
                                    <i class="fas fa-ban"></i>
                                </button>
                            ` : `
                                <button class="action-btn enable" data-venta-id="${venta.id}" data-codigo="${venta.codigo_venta}"
                                        title="Reactivar Venta">
                                    <i class="fas fa-check"></i>
                                </button>
                            `}
                        </div>
                    </div>
                    </div>
                </div>
            `;
        });
        
        salesTableBody.innerHTML = html;
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function showErrorMessage(message) {
        // Mostrar mensaje en la tabla (para casos de búsqueda/vistas) y también mostrar toast.
        if (salesTableBody) {
            salesTableBody.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #e74c3c;">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <p style="margin-top: 10px;">${message}</p>
                </div>
            `;
        }
        Toast.error(message, 'Error', 10000);
    }

    // Función para limpiar filtros
    function clearFilters() {
        salesSearchInput.value = '';
        dateFilter.value = '';
        statusFilter.value = '';
        categoryFilter.value = '';
        paymentFilter.value = '';
        customDateRange.style.display = 'none';
        dateFrom.value = '';
        dateTo.value = '';
        
        // Recargar ventas recientes (sin filtros)
        loadRecentSales();
    }

    // Función para cargar ventas recientes
    async function loadRecentSales() {
        try {
            const formData = new FormData();
            formData.append('recent', 'true');
            
            const response = await fetch('/inversiones-rojas/api/search_sales.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) throw new Error('Error al cargar ventas');
            
            const data = await response.json();
            if (data.success) {
                updateSalesTable(data.ventas);
            }
        } catch (error) {
            console.error('Error al cargar ventas recientes:', error);
            Toast.error('No se pudieron cargar las ventas recientes', 'Error');
        }
    }
// ==================== FUNCIONES DEL MODAL DE MÉTODO DE PAGO ====================
function showAddPaymentMethodModal() {
    const modalHTML = `
        <div class="modal-overlay registro-modal" id="addPaymentMethodModal">
            <div class="modal registro-modal">
                <div class="modal-header">
                    <h2><i class="fas fa-credit-card"></i> Nuevo Método de Pago</h2>
                    <button type="button" class="modal-close" id="closePaymentMethodModal">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="newPaymentName">Nombre del Método *</label>
                        <input type="text" id="newPaymentName" class="form-control" 
                               placeholder="Ej: Efectivo, Transferencia, Pago Móvil, Tarjeta de Débito, etc.">
                    </div>

                    <div class="form-group">
                        <label for="newPaymentDescription">Descripción (Opcional)</label>
                        <textarea id="newPaymentDescription" rows="3" class="form-control" 
                                  placeholder="Descripción del método de pago"></textarea>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="newPaymentActive" checked>
                            <label for="newPaymentActive">Activo</label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" id="cancelPaymentMethodBtn">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-complete" id="savePaymentMethodBtn">
                        <i class="fas fa-save"></i> Guardar Método
                    </button>
                </div>
            </div>
        </div>
    `;

    // Remover modal existente si hay
    const existingModal = document.getElementById('addPaymentMethodModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Agregar el modal al documento
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const modal = document.getElementById('addPaymentMethodModal');
    
    // Mostrar modal con animación
    setTimeout(() => {
        modal.classList.add('active');
    }, 10);

    // Configurar eventos del modal
    document.getElementById('closePaymentMethodModal').addEventListener('click', closePaymentMethodModal);
    document.getElementById('cancelPaymentMethodBtn').addEventListener('click', closePaymentMethodModal);
    document.getElementById('savePaymentMethodBtn').addEventListener('click', saveNewPaymentMethod);

    // Cerrar modal al hacer clic fuera
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closePaymentMethodModal();
        }
    });

    // Enfocar el primer campo
    setTimeout(() => {
        const nameField = document.getElementById('newPaymentName');
        if (nameField) nameField.focus();
    }, 100);
}

function closePaymentMethodModal() {
    const modal = document.getElementById('addPaymentMethodModal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

// Guardar nuevo método de pago
async function saveNewPaymentMethod() {
    const nameInput = document.getElementById('newPaymentName');
    const name = nameInput.value.trim();
    const descriptionInput = document.getElementById('newPaymentDescription');
    const description = descriptionInput.value.trim();
    const active = document.getElementById('newPaymentActive').checked;

    // 1. Validar nombre obligatorio
    if (!InvValidate.required(nameInput, 'Nombre del método')) {
        nameInput.focus();
        return;
    }

    // 2. Validar longitud mínima
    if (!InvValidate.minLength(nameInput, 3, 'Nombre')) {
        nameInput.focus();
        return;
    }

    // 3. Validar nombre único - verificar que no existe otro método con el mismo nombre
    try {
        const checkUrl = '/inversiones-rojas/api/check_metodo_pago.php?nombre=' + encodeURIComponent(name);
        const checkResp = await fetch(checkUrl);
        const checkData = await checkResp.json();
        
        if (!checkData.available) {
            InvValidate.setError(nameInput, 'Ya existe un método de pago con este nombre');
            nameInput.focus();
            return;
        }
    } catch (error) {
        console.error('Error al verificar método:', error);
    }

    // Mostrar indicador de carga
    const saveBtn = document.getElementById('savePaymentMethodBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveBtn.disabled = true;

    try {
        // Enviar datos al servidor
        const formData = new FormData();
        formData.append('nombre', name);
        if (description) formData.append('descripcion', description);
        formData.append('estado', active ? '1' : '0');

        const response = await fetch('/inversiones-rojas/api/add_metodo_pago.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Agregar la nueva opción al select
            const select = document.getElementById('paymentMethod');
            const newOption = document.createElement('option');
            newOption.value = result.id;
            newOption.textContent = name;
            
            // Agregar antes de la opción "-- Seleccionar método --"
            const firstOption = select.querySelector('option[value=""]');
            select.insertBefore(newOption, firstOption.nextSibling);
            
            // Seleccionar el nuevo método
            select.value = result.id;
            
            // Disparar evento change para actualizar UI
            select.dispatchEvent(new Event('change'));
            
            // Mostrar mensaje de éxito
            Toast.success(`Método de pago "${name}" creado exitosamente`, '¡Éxito!');
            
            // Cerrar modal
            closePaymentMethodModal();
        } else {
            Toast.error('Error: ' + (result.message || 'No se pudo guardar el método de pago'), 'Error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Error al guardar método de pago:', error);
        Toast.error('Error de conexión al servidor', 'Error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
}
// Buscar el botón de agregar método de pago y asignar evento
const addPaymentMethodBtn = document.getElementById('addPaymentMethodBtn');
if (addPaymentMethodBtn) {
    addPaymentMethodBtn.addEventListener('click', showAddPaymentMethodModal);
}
    // ==================== FUNCIONES DEL MODAL DE CLIENTE ====================
    function showAddClientModal() {
        const modalHTML = `
            <div class="modal-overlay registro-modal" id="addClientModal">
                <div class="modal registro-modal">
                    <div class="modal-header">
                        <h2><i class="fas fa-user-plus"></i> Nuevo Cliente</h2>
                        <button type="button" class="modal-close" id="closeAddClientModal">&times;</button>
                    </div>

                    <div class="modal-body">
                        <div class="form-row" style="align-items:flex-end; gap:10px;">
                            <div class="form-group" style="flex:0 0 90px;">
                                <label for="newClientDocType">Tipo</label>
                                <select id="newClientDocType" class="form-control">
                                    <option value="">---</option>
                                    <option value="V">V</option>
                                    <option value="J">J</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label for="newClientCedula">Cédula/RIF *</label>
                                <input type="text" id="newClientCedula" class="form-control" placeholder="12345678">
                                <div class="form-hint" style="font-size:12px; color:#666;">
                                    J para RIF (9 dígitos), V para Cédula (7-8 dígitos)
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="newClientName">Nombre Completo *</label>
                            <input type="text" id="newClientName" class="form-control" placeholder="Nombre y apellido del cliente">
                        </div>

                        <div class="form-group">
                            <label for="newClientEmail">Email</label>
                            <input type="text" id="newClientEmail" class="form-control" placeholder="cliente@email.com">
                        </div>

                        <div class="form-group">
                            <label for="newClientPhone">Teléfono Principal *</label>
                            <input type="text" id="newClientPhone" class="form-control" placeholder="0412-1234567">
                        </div>

                        <div class="form-group">
                            <label for="newClientAltPhone">Teléfono Alternativo</label>
                            <input type="text" id="newClientAltPhone" class="form-control" placeholder="Opcional">
                        </div>

                        <div class="form-group">
                            <label for="newClientAddress">Dirección</label>
                            <textarea id="newClientAddress" rows="3" class="form-control" placeholder="Dirección completa del cliente"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel" id="cancelAddClientBtn">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-complete" id="saveClientBtn">
                            <i class="fas fa-save"></i> Guardar Cliente
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);

        const modal = document.getElementById('addClientModal');
        setTimeout(() => {
            modal.classList.add('active');
        }, 10);

        document.getElementById('closeAddClientModal').addEventListener('click', closeAddClientModal);
        document.getElementById('cancelAddClientBtn').addEventListener('click', closeAddClientModal);
        document.getElementById('saveClientBtn').addEventListener('click', saveNewClient);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeAddClientModal();
            }
        });

        // Manejo del tipo de documento usando select
        const docTypeSelect = document.getElementById('newClientDocType');
        const cedulaInput = document.getElementById('newClientCedula');
        
        docTypeSelect.addEventListener('change', function() {
            const type = this.value;
            if (type === 'V') {
                cedulaInput.placeholder = '12345678 (7-8 dígitos)';
            } else if (type === 'J') {
                cedulaInput.placeholder = '123456789 (9 dígitos)';
            } else {
                cedulaInput.placeholder = '12345678';
            }
        });
    }

    function closeAddClientModal() {
        const modal = document.getElementById('addClientModal');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.remove();
            }, 300);
        }
    }

    // Guardar nuevo cliente
async function saveNewClient() {
    const docType = document.getElementById('newClientDocType').value;
    const cedula = document.getElementById('newClientCedula').value.trim();
    const nombre = document.getElementById('newClientName').value.trim();
    const email = document.getElementById('newClientEmail').value.trim();
    const telefono = document.getElementById('newClientPhone').value.trim();
    const telefonoAlt = document.getElementById('newClientAltPhone').value.trim();
    const direccion = document.getElementById('newClientAddress').value.trim();
    
    // Validar tipo de documento
    if (!InvValidate.required(document.getElementById('newClientDocType'), 'Tipo de documento')) return;

    // Validar cédula/RIF
    const cedulaInputEl = document.getElementById('newClientCedula');
    const cleanCedula = cedulaInputEl.value.trim().replace(/[^\d]/g, '');
    cedulaInputEl.value = `${docType}-${cleanCedula}`;
    if (!InvValidate.rif(cedulaInputEl, true)) {
        cedulaInputEl.focus();
        return;
    }

    // Validar nombre completo
    const nombreInputEl = document.getElementById('newClientName');
    if (!InvValidate.required(nombreInputEl, 'Nombre completo')) {
        nombreInputEl.focus();
        return;
    }

    // Validar email (opcional)
    const emailInputEl = document.getElementById('newClientEmail');
    if (emailInputEl.value.trim() && !InvValidate.email(emailInputEl, true)) {
        emailInputEl.focus();
        return;
    }

    // Validar teléfono principal
    const telefonoInputEl = document.getElementById('newClientPhone');
    if (!InvValidate.telefono(telefonoInputEl, true)) {
        telefonoInputEl.focus();
        return;
    }

    // Validar teléfono alternativo (opcional)
    const telefonoAltInputEl = document.getElementById('newClientAltPhone');
    if (telefonoAltInputEl.value.trim() && !InvValidate.telefono(telefonoAltInputEl, false)) {
        telefonoAltInputEl.focus();
        return;
    }

    // Mostrar indicador de carga
    const saveBtn = document.getElementById('saveClientBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveBtn.disabled = true;

    try {
        // Formatear cédula
        const formattedCedula = docType + '-' + cleanCedula;
        
        // Enviar datos al servidor - USAR LOS NOMBRES CORRECTOS DE CAMPOS
        const formData = new FormData();
        formData.append('cedula_rif', formattedCedula);              // Nombre correcto: cedula_rif
        formData.append('nombre_completo', nombre);         // Nombre correcto: nombre_completo
        formData.append('email', email || '');
        formData.append('telefono_principal', telefono);    // Nombre correcto: telefono_principal
        formData.append('telefono_alternativo', telefonoAlt || ''); // Nombre correcto: telefono_alternativo
        formData.append('direccion', direccion || '');

        const response = await fetch('/inversiones-rojas/api/add_cliente.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Agregar la nueva opción al select
            const select = document.getElementById('clientSelect');
            const newOption = document.createElement('option');
            newOption.value = result.cliente.id;
            newOption.setAttribute('data-source', 'clientes');
            newOption.setAttribute('data-cedula', result.cliente.cedula_rif);
            newOption.setAttribute('data-telefono', result.cliente.telefono_principal);
            newOption.setAttribute('data-direccion', result.cliente.direccion || '');
            
            // Texto del option: "Nombre Completo (Cédula)"
            newOption.textContent = `${result.cliente.nombre_completo} (${result.cliente.cedula_rif})`;
            
            // Agregar antes de la opción "-- Seleccionar Cliente --"
            const firstOption = select.querySelector('option[value=""]');
            select.insertBefore(newOption, firstOption.nextSibling);
            
            // Seleccionar el nuevo cliente automáticamente
            select.value = result.cliente.id;
            
            // Disparar evento change para actualizar UI
            const changeEvent = new Event('change');
            select.dispatchEvent(changeEvent);
            
            // Mostrar mensaje de éxito
            Toast.success(`Cliente "${result.cliente.nombre_completo}" registrado exitosamente`, '¡Éxito!');
            
            // Cerrar modal
            closeAddClientModal();
            
            // Enfocar el select de clientes
            setTimeout(() => {
                select.focus();
            }, 100);
            
        } else {
            Toast.error(`Error: ${result.message}`, 'Error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
            
            // Enfocar el campo que causó el error
            if (result.message.includes('cédula/RIF')) {
                document.getElementById('newClientCedula').focus();
            } else if (result.message.includes('nombre')) {
                document.getElementById('newClientName').focus();
            } else if (result.message.includes('teléfono')) {
                document.getElementById('newClientPhone').focus();
            }
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        Toast.error('Error de conexión con el servidor. Verifique su conexión a internet.', 'Error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
}

    // ==================== EVENT LISTENERS PRINCIPALES ====================
    
    // Modal de venta
    openSaleModalBtn.addEventListener('click', () => {
        saleModal.classList.add('active');
        document.body.classList.add('modal-open');
        initSaleModal();
    });

    closeSaleModalBtn.addEventListener('click', async () => {
        if (cart.length > 0) {
            const confirmed = await showConfirm({
                title: 'Cerrar venta',
                message: '¿Está seguro de cerrar? Se perderán los productos agregados.',
                confirmText: 'Cerrar',
                cancelText: 'Cancelar',
                type: 'warning'
            });
            if (confirmed) {
                resetSale();
                saleModal.classList.remove('active');
                document.body.classList.remove('modal-open');
                Toast.canceled('Se descartó la venta en curso', 'Venta cancelada');
            }
        } else {
            saleModal.classList.remove('active');
            document.body.classList.remove('modal-open');
        }
    });

    cancelSaleBtn.addEventListener('click', async () => {
        if (cart.length > 0) {
            const confirmed = await showConfirm({
                title: 'Cancelar venta',
                message: '¿Está seguro de cancelar la venta? Se perderán todos los productos agregados.',
                confirmText: 'Cancelar',
                cancelText: 'Volver',
                type: 'warning'
            });
            if (confirmed) {
                resetSale();
                saleModal.classList.remove('active');
                document.body.classList.remove('modal-open');
                Toast.canceled('La venta ha sido cancelada', 'Venta cancelada');
            }
        } else {
            saleModal.classList.remove('active');
            document.body.classList.remove('modal-open');
        }
    });

    // Completar venta
    console.log('✅ Event listener completeSaleBtn registrado');
    completeSaleBtn.addEventListener('click', () => {
        console.log('🔘 Botón Completar Venta clickeado');
        console.log('Cart length:', cart.length);
        console.log('selectedPaymentMethod:', selectedPaymentMethod);
        
        if (cart.length === 0) {
            Toast.warning('Agrega al menos un producto al carrito antes de completar la venta', 'Carrito vacío');
            return;
        }
        
        if (!selectedPaymentMethod) {
            Toast.warning('Por favor seleccione un método de pago', 'Falta información');
            return;
        }
        
        // Validar pago en efectivo
        const selectedText = paymentMethodSelect.options[paymentMethodSelect.selectedIndex].textContent.toLowerCase();
        if (selectedText.includes('efectivo')) {
            const cash = parseFloat(cashReceived.value) || 0;
            const total = calculateTotals().total;
            if (!InvValidate.positiveNumber(cashReceived, 'El efectivo recibido', false)) {
                return;
            }
            if (cash < total) {
                Toast.warning(`El efectivo recibido ($ ${cash.toFixed(2)}) es menor al total ($ ${total.toFixed(2)})`, 'Pago insuficiente');
                return;
            }
        }
        
        // Validación de stock antes de enviar (por si cambió en el servidor)
        const stockErrors = cart.filter(item => item.quantity > item.stock);
        if (stockErrors.length) {
            const first = stockErrors[0];
            Toast.warning(`Stock insuficiente para ${first.name}. Disponible: ${first.stock}`, 'Stock insuficiente');
            return;
        }

        // Preparar datos para enviar
        const totalsCurrency = calculateTotalsInCurrency();
        const paymentCurrency = getPaymentCurrency();
        const tasa = window.TASA_CAMBIO || 35.50;
        
        let efectivoRecibido = null;
        let efectivoRecibidoBs = null;
        let efectivoRecibidoUsd = null;
        
        if (cashReceived.value && parseFloat(cashReceived.value) > 0) {
            if (paymentCurrency === 'USD') {
                efectivoRecibidoUsd = parseFloat(cashReceived.value);
                efectivoRecibidoBs = efectivoRecibidoUsd * tasa;
                efectivoRecibido = efectivoRecibidoUsd;
            } else {
                efectivoRecibidoBs = parseFloat(cashReceived.value);
                efectivoRecibidoUsd = tasa > 0 ? efectivoRecibidoBs / tasa : 0;
                efectivoRecibido = efectivoRecibidoBs;
            }
        }
        
        // Crear objeto de venta
        const ventaData = {
            cliente_id: selectedClient ? selectedClient.id : null,
            metodo_pago_id: selectedPaymentMethod,
            moneda_pago: paymentCurrency,
            tasa_cambio: tasa,
            subtotal: totalsCurrency.subtotal,
            iva: totalsCurrency.tax,
            total: totalsCurrency.total,
            monto_bs: totalsCurrency.totalBs,
            monto_usd: totalsCurrency.totalUsd,
            productos: cart.map(item => ({
                id: parseInt(item.id),
                quantity: parseInt(item.quantity),
                precio_unitario: item.price,
                precio_unitario_bs: item.price * tasa,
                precio_unitario_usd: item.price
            })),
            efectivo_recibido: efectivoRecibido,
            efectivo_recibido_bs: efectivoRecibidoBs,
            efectivo_recibido_usd: efectivoRecibidoUsd
        };
        
        console.log('Datos a enviar (JSON):', JSON.stringify(ventaData));
        
        // Mostrar loader
        completeSaleBtn.disabled = true;
        const originalText = completeSaleBtn.innerHTML;
        completeSaleBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        
        // Enviar datos al servidor
        fetch('/inversiones-rojas/api/procesar_venta.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(ventaData)
        })
        .then(response => {
            console.log('Respuesta HTTP:', response.status, response.statusText);
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Respuesta del servidor:', data);
            
            if (data.success) {
                Toast.success('Venta completada exitosamente', '¡Éxito!', 10000);
                resetSale();
                saleModal.classList.remove('active');
                // Dejar que el usuario vea la notificación antes de recargar
                setTimeout(() => location.reload(), 1200);
            } else {
                Toast.error('Error al procesar la venta: ' + (data.message || 'Error desconocido'), 'Error');
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
            Toast.error('Error de conexión: ' + error.message, 'Error');
        })
        .finally(() => {
            completeSaleBtn.disabled = false;
            completeSaleBtn.innerHTML = originalText;
        });
    });

    // Búsqueda de productos en el modal
    searchBtn.addEventListener('click', () => {
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
        
        const visibleCount = Array.from(rows).filter(row => row.style.display !== 'none').length;
        productCount.textContent = visibleCount;
    });

    productSearch.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchBtn.click();
        }
    });

    // Agregar producto al carrito
    productsTableBody.addEventListener('click', (e) => {
        const addBtn = e.target.closest('.add-product-btn');
        if (addBtn) {
            const product = {
                id: addBtn.dataset.id,
                code: addBtn.dataset.code,
                name: addBtn.dataset.name,
                price: parseFloat(addBtn.dataset.price),
                stock: parseInt(addBtn.dataset.stock)
            };
            
            if (addToCart(product)) {
                addBtn.disabled = true;
                addBtn.innerHTML = '<i class="fas fa-check"></i> Agregado';
            }
        }
    });

    // Manejar eventos del carrito
    document.addEventListener('click', (e) => {
        // Disminuir cantidad
        if (e.target.closest('.decrease')) {
            const productId = e.target.closest('.decrease').dataset.id;
            const item = cart.find(item => item.id == productId);
            if (item) {
                updateQuantity(item.id, item.quantity - 1);
            }
        }
        
        // Aumentar cantidad
        if (e.target.closest('.increase')) {
            const productId = e.target.closest('.increase').dataset.id;
            const item = cart.find(item => item.id == productId);
            if (item) {
                updateQuantity(item.id, item.quantity + 1);
            }
        }
        
        // Eliminar producto
        if (e.target.closest('.remove-item')) {
            const productId = e.target.closest('.remove-item').dataset.id;
            removeFromCart(productId);
        }
    });

    // Actualizar cantidad desde input
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('quantity-input')) {
            const productId = e.target.dataset.id;
            const newQuantity = parseInt(e.target.value);
            
            if (!isNaN(newQuantity)) {
                updateQuantity(productId, newQuantity);
            }
        }
    });

    // Seleccionar cliente
    clientSelect.addEventListener('change', () => {
        const selectedOption = clientSelect.options[clientSelect.selectedIndex];

        if (selectedOption && selectedOption.value) {
            const val = selectedOption.value;
            const source = selectedOption.dataset.source || 'clientes';

            selectedClient = {
                id: val,
                source: source,
                name: selectedOption.textContent.split(' (')[0],
                cedula: selectedOption.dataset.cedula || '',
                phone: selectedOption.dataset.telefono || '',
                address: selectedOption.dataset.direccion || ''
            };

            clientInfo.style.display = 'block';
            clientName.textContent = selectedClient.name;
            clientCedula.textContent = selectedClient.cedula;
            clientPhone.textContent = selectedClient.phone;
            clientAddress.textContent = selectedClient.address;
        } else {
            clientInfo.style.display = 'none';
            selectedClient = null;
        }

        updateInvoiceComprobante();
    });

    // Búsqueda de clientes en el modal
    clientSearch.addEventListener('input', () => {
        const query = clientSearch.value.toLowerCase();
        const options = clientSelect.querySelectorAll('option');
        
        options.forEach(option => {
            if (option.value === '' || option.textContent.toLowerCase().includes(query)) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
    });

    // Agregar nuevo cliente
    addClientBtn.addEventListener('click', showAddClientModal);

    // Seleccionar método de pago en el modal
    paymentMethodSelect.addEventListener('change', () => {
        selectedPaymentMethod = paymentMethodSelect.value;
        const selectedOption = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
        const selectedText = selectedOption.textContent.toLowerCase();
        
        // Mostrar/ocultar sección de efectivo
        if (selectedText.includes('efectivo')) {
            cashSection.style.display = 'block';
        } else {
            cashSection.style.display = 'none';
        }
        
        // Recalcular totales
        updateCartUI();
        updateInvoiceComprobante();
    });

    // Calcular cambio al modificar efectivo recibido
    cashReceived.addEventListener('input', () => {
        const tasa = window.TASA_CAMBIO || 400;
        const cash = parseFloat(cashReceived.value) || 0;
        const cashBs = cash * tasa;
        
        // Mostrar equivalente en Bs
        const cashReceivedBs = document.getElementById('cashReceivedBs');
        if (cashReceivedBs) {
            cashReceivedBs.textContent = cashBs.toFixed(2);
        }
        
        calculateChange();
        updateInvoiceComprobante();
    });

    // ==================== FUNCIONES DE BÚSQUEDA Y FILTROS ====================
    
    // Mostrar/ocultar rango de fechas personalizado
    dateFilter.addEventListener('change', function() {
        if (this.value === 'custom') {
            customDateRange.style.display = 'block';
        } else {
            customDateRange.style.display = 'none';
        }
    });

    // Aplicar rango de fechas personalizado
    function applyCustomDate() {
        // Validar fechas (no futuras)
        const validFrom = InvValidate.notFutureDate(dateFrom, 'Fecha Desde');
        const validTo = InvValidate.notFutureDate(dateTo, 'Fecha Hasta');

        if (!validFrom || !validTo) {
            Toast.warning('Selecciona un rango de fechas válido.', 'Fechas inválidas');
            return;
        }

        if (new Date(dateTo.value) < new Date(dateFrom.value)) {
            Toast.warning('La fecha Hasta no puede ser menor a la fecha Desde.', 'Rango inválido');
            return;
        }

        searchSales();
    }

    // Configurar búsqueda con Enter en la barra de búsqueda principal
    salesSearchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchSales();
        }
    });

    // Resetear venta
    function resetSale() {
        cart = [];
        productSearch.value = '';
        clientSearch.value = '';
        clientSelect.value = '';
        paymentMethodSelect.value = '';
        cashReceived.value = '';
        cashSection.style.display = 'none';
        clientInfo.style.display = 'none';
        selectedClient = null;
        selectedPaymentMethod = null;
        updateCartUI();
        updateInvoiceComprobante();
        updateDateTime();
        
        // Rehabilitar todos los botones de agregar
        const addButtons = productsTableBody.querySelectorAll('.add-product-btn');
        addButtons.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Agregar';
        });
    }

    // Cerrar modal al hacer clic fuera
    saleModal.addEventListener('click', async (e) => {
        if (e.target === saleModal) {
            if (cart.length > 0) {
                const confirmed = await showConfirm({
                    title: 'Cerrar venta',
                    message: '¿Está seguro de cerrar? Se perderán los productos agregados.',
                    confirmText: 'Cerrar',
                    cancelText: 'Cancelar',
                    type: 'warning'
                });
                if (confirmed) {
                    resetSale();
                    saleModal.classList.remove('active');
                    document.body.classList.remove('modal-open');
                    Toast.canceled('Se descartó la venta en curso', 'Venta cancelada');
                }
            } else {
                saleModal.classList.remove('active');
                document.body.classList.remove('modal-open');
            }
        }
    });

    // ==================== GRÁFICOS ====================
    
    <?php
    // Preparar datos para los gráficos
    $chart_months = [];
    $chart_sales = [];
    foreach ($ventas_mensuales as $venta) {
        $chart_months[] = $venta['nombre_mes'];
        $chart_sales[] = $venta['total_ventas'];
    }
    
    $payment_labels = [];
    $payment_data = [];
    foreach ($metodos_populares as $metodo) {
        $payment_labels[] = $metodo['nombre'];
        $payment_data[] = $metodo['porcentaje'];
    }
    ?>

    // Gráfica de Ventas Mensuales
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_months); ?>,
            datasets: [{
                label: 'Ventas ($)',
                data: <?php echo json_encode($chart_sales); ?>,
                borderColor: '#1F9166',
                backgroundColor: 'rgba(31, 145, 102, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + (value/1000).toFixed(0) + 'k';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });


    // Gráfica de Métodos de Pago
    const paymentCtx = document.getElementById('paymentChart').getContext('2d');
    const paymentChart = new Chart(paymentCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($payment_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($payment_data); ?>,
                backgroundColor: [
                    '#1F9166',
                    '#3498db',
                    '#9b59b6',
                    '#e67e22'
                ],
                borderWidth: 2,
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

    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
        updateDateTime();
        setInterval(updateDateTime, 60000);
        // Cargar las ventas más recientes al iniciar
        loadRecentSales();
    });

// ==================== FUNCIONES PARA VER FACTURA (CORREGIDO) ====================

// Helpers para obtener elementos del modal
function $id(id){ return document.getElementById(id); }

/// ==================== FUNCIÓN PARA VER FACTURA EN MODAL ====================
async function showFactura(ventaId) {
    try {
        const modal = document.getElementById('viewInvoiceModal');
        const body = document.getElementById('invoiceModalBody');
        const printBtn = document.getElementById('printFacturaBtn');
        
        if (modal) modal.classList.add('active');
        if (body) body.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top: 10px;">Cargando ticket...</p>
            </div>
        `;
        if (printBtn) printBtn.disabled = true;

        const formData = new FormData();
        formData.append('venta_id', ventaId);
        
        const response = await fetch('/inversiones-rojas/api/get_factura.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success && data.factura) {
            if (printBtn) {
                printBtn.dataset.factura = JSON.stringify(data.factura);
                printBtn.disabled = false;
            }
            renderTicketEnModal(data.factura);
        } else {
            throw new Error(data.message || 'Error al cargar el ticket');
        }
        
    } catch (error) {
        console.error('Error:', error);
        const body = document.getElementById('invoiceModalBody');
        if (body) body.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #e74c3c;">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <p style="margin-top: 10px;">Error al cargar el ticket</p>
                <p style="font-size: 14px;">${error.message}</p>
            </div>
        `;
    }
}

// Mostrar ticket en el modal (vista previa)
function renderTicketEnModal(factura) {
    const fecha = new Date(factura.created_at);
    const fechaFormateada = fecha.toLocaleDateString('es-VE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
    const horaFormateada = fecha.toLocaleTimeString('es-VE', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });
    
    const body = document.getElementById('invoiceModalBody');
    
    // Usar valores en Bs convertidos (disponibles en factura.total_bs, etc.)
    const tasa = factura.tasa_cambio || 35.50;
    const subtotal = factura.subtotal_bs || (factura.subtotal * tasa);
    const iva = factura.iva_bs || (factura.iva * tasa);
    const total = factura.total_bs || (factura.total * tasa);

    let observacionesHtml = '';
    if (factura.observaciones) {
        observacionesHtml = `<div style="font-size: 11px; margin-top: 2px;">${factura.observaciones}</div>`;
    }

    let productosHtml = '';
    if (factura.detalles && factura.detalles.length > 0) {
        productosHtml = factura.detalles.map(detalle => {
            // Usar precio en Bs (precio_unitario_bs)
            const precioBs = detalle.precio_unitario_bs || (detalle.precio_unitario * tasa);
            const itemTotalBs = detalle.subtotal_bs || (detalle.subtotal * tasa);
            return `
                <div style="margin-bottom: 8px;">
                    <div style="font-weight: bold;">${detalle.producto_nombre}</div>
                    <div style="display: flex; justify-content: space-between; margin-left: 10px; font-size: 11px;">
                        <span>${detalle.cantidad} x Bs ${precioBs.toFixed(2)}</span>
                        <span style="font-weight: bold;">Bs ${itemTotalBs.toFixed(2)}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    body.innerHTML = `
        <div style="font-family: 'Courier New', monospace; width: 280px; margin: 0 auto; font-size: 12px; line-height: 1.4;">
            <!-- ENCABEZADO -->
            <div style="text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 8px;">
                <div style="font-weight: bold; font-size: 14px;">INVERSIONES ROJAS 2016. C.A.</div>
                <div style="font-size: 11px;">RIF: J-40888806-8</div>
                <div style="font-size: 10px;">AV ARAGUA LOCAL NRO 286</div>
                <div style="font-size: 10px;">SECTOR ANDRES ELOY BLANCO, MARACAY</div>
                <div style="font-size: 10px;">TEL: 0243-2343044</div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span><strong>FACTURA:</strong> ${factura.codigo_venta || 'N/A'}</span>
                <span><strong>FECHA:</strong> ${fechaFormateada}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; border-bottom: 1px dashed #000; padding-bottom: 5px;">
                <span><strong>HORA:</strong> ${horaFormateada}</span>
                <span></span>
            </div>

            <div style="margin-bottom: 8px;">
                <div><strong>CLIENTE:</strong> ${factura.cliente_nombre || 'CLIENTE GENERAL'}</div>
                <div><strong>CÉDULA:</strong> ${factura.cliente_cedula || 'V-00000000'}</div>
                <div><strong>VENDEDOR:</strong> ${factura.vendedor || 'SISTEMA'}</div>
            </div>

            <div style="text-align: center; margin: 5px 0; font-weight: bold;">======== PRODUCTOS ========</div>
            <div style="margin-bottom: 10px;">${productosHtml}</div>

            <div style="margin-bottom: 8px; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0;">
                <div><strong>MÉTODO DE PAGO:</strong> ${factura.metodo_pago_nombre || 'NO ESPECIFICADO'}</div>
                ${observacionesHtml}
            </div>

            <div style="margin-bottom: 8px;">
                <div style="display: flex; justify-content: space-between;">
                    <span>SUBTOTAL:</span>
                    <span>Bs ${subtotal.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>IVA (16%):</span>
                    <span>Bs ${iva.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 5px 0; margin-top: 5px;">
                    <span>TOTAL Bs.:</span>
                    <span>${total.toFixed(2)}</span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 10px; padding-top: 8px; border-top: 1px dashed #000;">
                <div style="font-weight: bold;">¡GRACIAS POR SU COMPRA!</div>
                <div>VUELVA PRONTO</div>
                <div style="font-size: 10px; margin-top: 3px;">${fechaFormateada} ${horaFormateada}</div>
                <div style="font-size: 8px; margin-top: 5px;">*** TICKET NO VÁLIDO COMO FACTURA ***</div>
            </div>
        </div>
    `;
}

// ==================== FUNCIÓN PARA GENERAR PDF CON FPDF ====================
function generarPDF(factura) {
    if (!factura || !factura.id) {
        Toast.warning('No hay datos del ticket para generar PDF', 'Sin datos');
        return;
    }

    // Mostrar indicador de carga
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #1F9166;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        z-index: 10000;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    `;
    toast.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando PDF...';
    document.body.appendChild(toast);

    // Crear un enlace temporal para descargar el PDF
    const link = document.createElement('a');
    link.href = `/inversiones-rojas/api/generate_ticket_pdf.php?venta_id=${factura.id}`;
    link.download = `Ticket_${factura.codigo_venta || 'factura'}.pdf`;
    link.style.display = 'none';

    // Agregar al DOM y hacer clic para descargar
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Remover el toast después de un breve delay
    setTimeout(() => {
        if (document.body.contains(toast)) {
            document.body.removeChild(toast);
        }
    }, 1000);
}
    


// ==================== FUNCIONES PARA REPORTES ====================

// Función de compatibilidad para notificaciones
displayNotification = function(message, type = 'info', duration = 10000) {
    // Usa el sistema de Toast global para mantener estilo uniforme en toda la app
    if (Toast && typeof Toast[type] === 'function') {
        Toast[type](message, '', duration);
    } else {
        Toast.info(message, '', duration);
    }
};

// Abrir modal de reportes
function openReportsModal() {
    const modal = document.getElementById('reportsModal');
    if (modal) {
        modal.classList.add('active');
        // Resetear selección
        document.querySelectorAll('.report-card').forEach(card => card.classList.remove('selected'));
        document.getElementById('generateReportBtn').disabled = true;
        // Ocultar opciones de fecha/mes
        updateReportOptions('');
        // asegurarse de que el cuerpo esté arriba para ver el primer elemento
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

// Mostrar/Ocultar opciones de fecha/mes según tipo de reporte
function updateReportOptions(reportType) {
    const reportOptions = document.getElementById('reportOptions');
    const dayOpt = document.getElementById('reportDayOption');
    const monthOpt = document.getElementById('reportMonthOption');
    const dateInput = document.getElementById('reportDateInput');
    const monthInput = document.getElementById('reportMonthInput');

    if (!reportOptions || !dayOpt || !monthOpt || !dateInput || !monthInput) return;

    reportOptions.style.display = 'none';
    dayOpt.style.display = 'none';
    monthOpt.style.display = 'none';

    if (reportType === 'ingresos_diario') {
        reportOptions.style.display = 'block';
        dayOpt.style.display = 'flex';
        if (!dateInput.value) {
            dateInput.value = new Date().toISOString().slice(0, 10);
        }
    } else if (reportType === 'ingresos_mensual') {
        reportOptions.style.display = 'block';
        monthOpt.style.display = 'flex';
        if (!monthInput.value) {
            monthInput.value = new Date().toISOString().slice(0, 7);
        }
    }
}

// Generar reporte seleccionado
async function generateReport() {
    const selectedCard = document.querySelector('.report-card.selected');
    if (!selectedCard) return;
    
    const reportType = selectedCard.dataset.report;
    const btn = document.getElementById('generateReportBtn');
    const originalText = btn.innerHTML;

    const reportDate = document.getElementById('reportDateInput')?.value;
    const reportMonth = document.getElementById('reportMonthInput')?.value;

    if (reportType === 'ingresos_diario' && !reportDate) {
        Toast.warning('Por favor selecciona una fecha para el reporte', 'Fecha requerida');
        return;
    }

    if (reportType === 'ingresos_mensual' && !reportMonth) {
        Toast.warning('Por favor selecciona un mes para el reporte', 'Mes requerido');
        return;
    }

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Descargando...';
    btn.disabled = true;
    
    try {
        const response = await fetch('/inversiones-rojas/api/generate_report.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify(Object.assign({report_type: reportType, module: 'ventas'},
                reportType === 'ingresos_diario' ? { report_date: document.getElementById('reportDateInput')?.value } : {},
                reportType === 'ingresos_mensual' ? { report_month: document.getElementById('reportMonthInput')?.value } : {}
            ))
        });
        
        if (!response.ok) throw new Error('Error ' + response.status);
        
        // Obtener el PDF como blob
        const blob = await response.blob();
        
        // Crear descarga
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'reporte_' + reportType + '_' + new Date().toISOString().slice(0, 10) + '.pdf';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
        
        closeReportsModal();
        Toast.success('Reporte descargado exitosamente', '¡Éxito!');
        
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Error al descargar reporte', 'Error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// ==================== EVENT LISTENERS (SOLO UNO) ====================
document.addEventListener('DOMContentLoaded', function() {
    
    document.addEventListener('click', (e) => {
        // ── Ver Factura ───────────────────────────────────────────────
        const viewBtn = e.target.closest('.action-btn.view, .table-action-btn.view');
        if (viewBtn) {
            const ventaId = viewBtn.dataset.ventaId;
            if (ventaId) showFactura(ventaId);
        }

        // ── Inhabilitar Venta (botón rojo .disable) ───────────────────
        const disableBtn = e.target.closest('.action-btn.disable');
        if (disableBtn) {
            const ventaId    = disableBtn.dataset.ventaId;
            const codigoVenta = disableBtn.dataset.codigo || ventaId;
            if (ventaId) toggleVentaEstado(ventaId, codigoVenta, 'INHABILITADO', disableBtn);
        }

        // ── Reactivar Venta (botón verde .enable) ─────────────────────
        const enableBtn = e.target.closest('.action-btn.enable');
        if (enableBtn) {
            const ventaId    = enableBtn.dataset.ventaId;
            const codigoVenta = enableBtn.dataset.codigo || ventaId;
            if (ventaId) toggleVentaEstado(ventaId, codigoVenta, 'COMPLETADA', enableBtn);
        }
        
        // ── Abrir Modal de Reportes ───────────────────────────────────
        if (e.target.closest('#openReportsModalBtn')) {
            openReportsModal();
        }
        
        // ── Selección de tarjeta de reporte ──────────────────────────
        if (e.target.closest('.report-card')) {
            const card = e.target.closest('.report-card');
            document.querySelectorAll('.report-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            document.getElementById('generateReportBtn').disabled = false;
            const reportType = card.dataset.report;
            updateReportOptions(reportType);
        }
        
        // ── Generar Reporte ───────────────────────────────────────────
        if (e.target.closest('#generateReportBtn')) {
            generateReport();
        }
    });

    // Botón Imprimir en el modal
    const printFacturaBtn = document.getElementById('printFacturaBtn');
    if (printFacturaBtn) {
        printFacturaBtn.addEventListener('click', function() {
            const facturaData = this.dataset.factura;
            if (facturaData) {
                generarPDF(JSON.parse(facturaData));
            } else {
                Toast.warning('No hay datos del ticket para generar PDF', 'Sin datos');
            }
        });
    }

    // Cerrar modal de factura
    document.addEventListener('click', (e) => {
        const modal = document.getElementById('viewInvoiceModal');
        if (!modal) return;
        
        if (e.target.id === 'closeViewInvoiceModal' || 
            e.target.id === 'closeInvoiceModalBtn' ||
            (e.target === modal && !e.target.closest('.modal'))) {
            modal.classList.remove('active');
            const printBtn = document.getElementById('printFacturaBtn');
            if (printBtn) {
                printBtn.dataset.factura = '';
            }
        }
    });
    
    // Cerrar modal de reportes
    document.addEventListener('click', (e) => {
        const modal = document.getElementById('reportsModal');
        if (!modal) return;
        
        if (e.target.id === 'closeReportsModal' || 
            e.target.id === 'cancelReportsBtn' ||
            (e.target === modal && !e.target.closest('.modal'))) {
            closeReportsModal();
        }
    });
});

// ==================== TOGGLE ESTADO VENTA (INHABILITAR / REACTIVAR) ====================
// Misma lógica que compras: un solo endpoint, dos acciones según el estado objetivo.

async function toggleVentaEstado(ventaId, codigoVenta, nuevoEstado, btn) {
    const esInhabilitar = nuevoEstado === 'INHABILITADO';

    const confirmed = await showConfirm({
        title:       esInhabilitar ? 'Inhabilitar venta' : 'Reactivar venta',
        message:     esInhabilitar
            ? `¿Está seguro de inhabilitar la venta <strong>${codigoVenta}</strong>? Quedará marcada como INHABILITADA.`
            : `¿Está seguro de reactivar la venta <strong>${codigoVenta}</strong>? Volverá al estado COMPLETADA.`,
        confirmText: esInhabilitar ? 'Sí, inhabilitar' : 'Sí, reactivar',
        cancelText:  'Cancelar',
        type:        esInhabilitar ? 'warning' : 'info'
    });

    if (!confirmed) return;

    // Feedback visual en el botón
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled  = true;

    try {
        const res = await fetch('/inversiones-rojas/api/toggle_venta_estado.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ venta_id: ventaId, estado: nuevoEstado })
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status} ${res.statusText}`);
        }

        const data = await res.json();

        if (data.success) {
            Toast.success(
                esInhabilitar
                    ? `Venta ${codigoVenta} inhabilitada correctamente`
                    : `Venta ${codigoVenta} reactivada correctamente`,
                '¡Listo!',
                8000
            );
            // Recargar tabla tras breve pausa
            setTimeout(() => loadRecentSales(), 700);
        } else {
            Toast.error(data.message || 'No se pudo actualizar el estado.', 'Error', 8000);
            btn.innerHTML = orig;
            btn.disabled  = false;
        }
    } catch (error) {
        console.error('toggleVentaEstado error:', error);
        Toast.error(`Error de conexión: ${error.message}`, 'Error', 8000);
        btn.innerHTML = orig;
        btn.disabled  = false;
    }
}

// Alias por compatibilidad si algo externo llama inhabilitarVenta
function inhabilitarVenta(ventaId, codigoVenta, btn) {
    return toggleVentaEstado(ventaId, codigoVenta, 'INHABILITADO', btn);
}

</script>
<!-- Modal para Ver Factura -->
<div class="modal-overlay" id="viewInvoiceModal">
    <div class="modal" style="max-width: 800px;">
        <div class="modal-header">
            <h2><i class="fas fa-file-invoice"></i> Factura de Venta</h2>
            <button class="modal-close" id="closeViewInvoiceModal">&times;</button>
        </div>
        
        <div class="modal-body" id="invoiceModalBody" style="padding: 20px; overflow-y: auto;">
            <!-- La factura se cargará aquí dinámicamente -->
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top: 10px;">Cargando factura...</p>
            </div>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-cancel" id="closeInvoiceModalBtn">
                <i class="fas fa-times"></i> Cerrar
            </button>
            <button class="btn btn-print" id="printFacturaBtn">
                <i class="fas fa-print"></i> Imprimir Factura
            </button>
        </div>
    </div>
</div>

<!-- Modal para Reportes de Ventas -->
<div class="modal-overlay" id="reportsModal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h2><i class="fas fa-chart-bar"></i> Reportes de Ventas</h2>
            <button class="modal-close" id="closeReportsModal">&times;</button>
        </div>
        
        <div class="modal-body" style="padding: 20px;">
            <p style="margin-bottom: 20px; color: #666;">Selecciona el tipo de reporte que deseas generar:</p>
            
        
            
            <div class="report-card" data-report="ingresos_diario">
                <i class="fas fa-calendar-day"></i>
                <strong>Informe de Ingresos Diario</strong>
                <p>Reporte de ingresos del día actual</p>
            </div>
            
            <div class="report-card" data-report="ingresos_mensual">
                <i class="fas fa-calendar-alt"></i>
                <strong>Informe de Ingresos Mensual</strong>
                <p>Reporte de ingresos del mes actual</p>
            </div>
            
            <div class="report-card" data-report="historial_clientes">
                <i class="fas fa-users"></i>
                <strong>Historial de Clientes</strong>
                <p>Listado completo de clientes y sus compras</p>
            </div>

            <!-- Opciones de fecha/mes para los reportes -->
            <div id="reportOptions" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd;">
                <div id="reportDayOption" style="display: none; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <label style="font-weight: 600; color: #333; min-width: 100px;">Fecha:</label>
                    <input type="date" id="reportDateInput" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" />
                </div>
                <div id="reportMonthOption" style="display: none; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <label style="font-weight: 600; color: #333; min-width: 100px;">Mes:</label>
                    <input type="month" id="reportMonthInput" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" />
                </div>
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

<!-- Modal: Gestión de Clientes -->
<div class="modal-overlay registro-modal" id="clientsListModal" style="display: none;">
    <div class="modal registro-modal" style="width: 800px; max-width: 98%; max-height: 90vh;">
        <div class="modal-header">
            <h2><i class="fas fa-users"></i> Gestión de Clientes</h2>
            <button type="button" class="modal-close" onclick="closeClientsModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <!-- Barra de filtros -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" id="clientSearchInput" class="form-control" placeholder="Buscar cliente..." 
                               style="width: 100%;">
                    </div>
                    <div style="min-width: 130px;">
                        <select id="clientStatusFilter" class="form-control" style="width: 100%;">
                            <option value="">Todos</option>
                            <option value="true">Activos</option>
                            <option value="false">Inactivos</option>
                        </select>
                    </div>
                    <div style="min-width: 140px;">
                        <input type="date" id="clientDateFrom" class="form-control" style="width: 100%;" title="Desde">
                    </div>
                    <div style="min-width: 140px;">
                        <input type="date" id="clientDateTo" class="form-control" style="width: 100%;" title="Hasta">
                    </div>
                </div>
            </div>
            
            <!-- Tabla de clientes -->
            <div style="max-height: 450px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead style="position: sticky; top: 0; background: #f8f9fa;">
                        <tr style="border-bottom: 2px solid #dee2e6;">
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Cédula/RIF</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Nombre</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Teléfono</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600;">Email</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600;">Estado</th>
                            <th style="padding: 12px; text-align: center; font-weight: 600;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="clientsTableBody">
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: #666;">
                                <i class="fas fa-spinner fa-spin"></i> Cargando clientes...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" onclick="closeClientsModal()">
                <i class="fas fa-times"></i> Cerrar
            </button>
            <button type="button" class="btn btn-primary" onclick="showAddClientModal()">
                <i class="fas fa-plus"></i> Nuevo Cliente
            </button>
        </div>
    </div>
</div>

<script>
function showClientsModal() {
    const modal = document.getElementById('clientsListModal');
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('active'), 10);
    loadClientsList();
}

function closeClientsModal() {
    const modal = document.getElementById('clientsListModal');
    modal.classList.remove('active');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// Cerrar al hacer clic fuera
const clientsModalEl = document.getElementById('clientsListModal');
if (clientsModalEl) {
    clientsModalEl.addEventListener('click', function(e) {
        if (e.target === this) closeClientsModal();
    });
}

// Cargar lista de clientes
async function loadClientsList(search = '', status = '', dateFrom = '', dateTo = '') {
    const tbody = document.getElementById('clientsTableBody');
    tbody.innerHTML = '<tr><td colspan="6" style="padding: 30px; text-align: center;"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';
    
    try {
        let url = '/inversiones-rojas/api/get_clientes.php?';
        if (search) url += 'search=' + encodeURIComponent(search) + '&';
        if (status) url += 'estado=' + status + '&';
        if (dateFrom) url += 'fecha_from=' + dateFrom + '&';
        if (dateTo) url += 'fecha_to=' + dateTo + '&';
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.ok && data.clientes.length > 0) {
            tbody.innerHTML = data.clientes.map(c => `
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">${c.cedula_rif || '-'}</td>
                    <td style="padding: 12px;">${c.nombre_completo || '-'}</td>
                    <td style="padding: 12px;">${c.telefono_principal || '-'}</td>
                    <td style="padding: 12px;">${c.email || '-'}</td>
                    <td style="padding: 12px; text-align: center;">
                        <span style="padding: 4px 10px; border-radius: 12px; font-size: 12px; background: ${c.estado ? '#d4edda' : '#f8d7da'}; color: ${c.estado ? '#155724' : '#721c24'};">
                            ${c.estado ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <button type="button" onclick="editClient(${c.id})" style="background: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-right: 5px;" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" onclick="toggleClientStatus(${c.id}, ${!c.estado})" style="background: ${c.estado ? '#dc3545' : '#28a745'}; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;" title="${c.estado ? 'Inhabilitar' : 'Activar'}">
                            <i class="fas fa-${c.estado ? 'ban' : 'check'}"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" style="padding: 30px; text-align: center; color: #666;">No se encontraron clientes</td></tr>';
        }
    } catch (error) {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="6" style="padding: 30px; text-align: center; color: #dc3545;">Error al cargar clientes</td></tr>';
    }
}

// Filtrar clientes
document.getElementById('clientSearchInput').addEventListener('input', function(e) {
    const status = document.getElementById('clientStatusFilter').value;
    const dateFrom = document.getElementById('clientDateFrom').value;
    const dateTo = document.getElementById('clientDateTo').value;
    loadClientsList(e.target.value, status, dateFrom, dateTo);
});

document.getElementById('clientStatusFilter').addEventListener('change', function(e) {
    const search = document.getElementById('clientSearchInput').value;
    const dateFrom = document.getElementById('clientDateFrom').value;
    const dateTo = document.getElementById('clientDateTo').value;
    loadClientsList(search, e.target.value, dateFrom, dateTo);
});

document.getElementById('clientDateFrom').addEventListener('change', function(e) {
    const search = document.getElementById('clientSearchInput').value;
    const status = document.getElementById('clientStatusFilter').value;
    const dateTo = document.getElementById('clientDateTo').value;
    loadClientsList(search, status, e.target.value, dateTo);
});

document.getElementById('clientDateTo').addEventListener('change', function(e) {
    const search = document.getElementById('clientSearchInput').value;
    const status = document.getElementById('clientStatusFilter').value;
    const dateFrom = document.getElementById('clientDateFrom').value;
    loadClientsList(search, status, dateFrom, e.target.value);
});

// Editar cliente
function editClient(clientId) {
    Toast.info('Función de edición en desarrollo', 'Editar Cliente');
}

// Cambiar estado del cliente
async function toggleClientStatus(clientId, newStatus) {
    const action = newStatus ? 'activar' : 'inhabilitar';
    if (!confirm(`¿Está seguro de ${action} este cliente?`)) return;
    
    try {
        const formData = new FormData();
        formData.append('id', clientId);
        formData.append('estado', newStatus ? '1' : '0');
        
        const response = await fetch('/inversiones-rojas/api/update_cliente_estado.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.ok) {
            Toast.success(data.message || `Cliente ${newStatus ? 'activado' : 'inhabilitado'} correctamente`);
            loadClientsList(
                document.getElementById('clientSearchInput').value,
                document.getElementById('clientStatusFilter').value,
                document.getElementById('clientDateFrom').value,
                document.getElementById('clientDateTo').value
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