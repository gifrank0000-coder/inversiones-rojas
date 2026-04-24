<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../helpers/promocion_helper.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';

// Obtener productos del carrito desde la sesión
$carrito = [];
if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $product_ids = array_keys($_SESSION['carrito']);
        $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
        
        $sql = "SELECT DISTINCT ON (p.id) p.id, p.nombre, p.descripcion, p.precio_venta, p.stock_actual, 
                       COALESCE(pi.imagen_url, '') as imagen_url,
                       c.nombre as categoria,
                       pr.tipo_promocion AS promo_tipo_promocion,
                       pr.valor AS promo_valor,
                       pr.nombre AS promo_nombre
                FROM productos p
                LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
                LEFT JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN producto_promociones pp ON pp.producto_id = p.id
                LEFT JOIN promociones pr ON pr.id = pp.promocion_id
                    AND pr.estado = true
                    AND pr.fecha_inicio <= CURRENT_DATE
                    AND pr.fecha_fin >= CURRENT_DATE
                WHERE p.id IN ($placeholders) AND p.estado = true
                ORDER BY p.id, pr.fecha_fin ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($product_ids);
        $productos_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combinar con cantidades del carrito
        $seenProducts = [];
        foreach ($productos_db as $producto) {
            $id = $producto['id'];
            if (isset($seenProducts[$id])) {
                continue; // evitar duplicados si existen varias promo asociadas
            }
            $seenProducts[$id] = true;

            if (isset($_SESSION['carrito'][$id])) {
                $precioReal = calcularPrecioConPromocion(
                    floatval($producto['precio_venta']),
                    $producto['promo_tipo_promocion'] ?? null,
                    $producto['promo_valor'] ?? null
                );

                $cantidad = $_SESSION['carrito'][$id]['quantity'] ?? $_SESSION['carrito'][$id]['cantidad'] ?? 1;

                $carrito[] = [
                    'id' => $id,
                    'nombre' => $producto['nombre'],
                    'descripcion' => $producto['descripcion'],
                    'categoria' => $producto['categoria'],
                    'precio' => $precioReal,
                    'precio_original' => floatval($producto['precio_venta']),
                    'imagen' => !empty($producto['imagen_url']) ? $producto['imagen_url'] : '',
                    'cantidad' => $cantidad,
                    'stock' => $producto['stock_actual'],
                    'promocion' => [
                        'tipo' => $producto['promo_tipo_promocion'] ?? null,
                        'valor' => $producto['promo_valor'] ?? null,
                        'nombre' => $producto['promo_nombre'] ?? null,
                    ]
                ];
            }
        }
    } catch (Exception $e) {
        error_log('Error al cargar carrito: ' . $e->getMessage());
        $carrito = [];
    }
}

// Calcular totales correctamente
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}
$iva = round($subtotal * 0.16, 2); // 16% IVA
$total = $subtotal + $iva;

// Verificar si el usuario está logueado
$usuario_logueado = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Inversiones Rojas</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/components/user-panel.css">
    <style>
        /* Estilos específicos para carrito */
        .cart-page {
            padding: 40px 0;
            min-height: 70vh;
        }
        
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .cart-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 992px) {
            .cart-content {
                grid-template-columns: 1fr;
            }
        }
        
        /* Lista de productos en el carrito */
        .cart-items {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .cart-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            padding: 15px 0;
            border-bottom: 2px solid #eee;
            font-weight: bold;
            color: #333;
        }
        
        @media (max-width: 768px) {
            .cart-header {
                display: none;
            }
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            position: relative;
        }
        
        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 1fr;
                gap: 15px;
                position: relative;
                padding: 25px 0;
            }
        }
        
        .item-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .item-image-placeholder {
            width: 100px;
            height: 100px;
            background: #f5f5f5;
            border: 2px dashed #ddd;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 12px;
            text-align: center;
        }
        
        .item-image-placeholder i {
            font-size: 24px;
            margin-bottom: 4px;
        }
        
        .item-details h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .item-details p {
            color: #666;
            font-size: 14px;
        }
        
        .item-category {
            display: inline-block;
            background: #e8f5e9;
            color: #1F9166;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .item-price {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
        }
        
        .item-price .moneda-usd {
            font-size: 16px;
            color: #333;
            font-weight: 700;
        }
        
        .item-price .moneda-bs {
            font-size: 14px;
            color: #1F9166;
            font-weight: 500;
        }
        
        .item-price-original {
            font-size: 13px;
            color: #adb5bd;
            text-decoration: line-through;
        }
        
        .item-price-sale {
            font-size: 16px;
            color: #333;
            font-weight: 700;
        }
        
        .item-price-promo {
            font-size: 11px;
            color: #1F9166;
            font-weight: 600;
            background: #e8f6f1;
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        .item-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 30px;
        }
        
        .quantity-btn {
            width: 35px;
            height: 35px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s;
        }
        
        .quantity-btn:hover {
            background: #1F9166;
            color: white;
            border-color: #1F9166;
        }
        
        .quantity-input {
            width: 50px;
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .item-total {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
        }
        
        .item-total .moneda-usd {
            font-size: 18px;
            color: #333;
            font-weight: 800;
        }
        
        .item-total .moneda-bs {
            font-size: 14px;
            color: #1F9166;
            font-weight: 500;
        }
        
        .item-remove {
            position: absolute;
            top: 10px;
            right: 0;
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 18px;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s ease;
            opacity: 0.7;
        }
        .item-remove:hover {
            background: #ffeef0;
            opacity: 1;
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .item-remove {
                position: absolute;
                top: 10px;
                right: 0;
            }
            
            .item-info {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }
            
            .item-image {
                width: 100%;
                height: 150px;
            }
            
            .item-price, .item-total {
                display: flex;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }
            
            .item-price::before {
                content: "Precio unitario: ";
                color: #666;
            }
            
            .item-total::before {
                content: "Total: ";
                color: #666;
            }
        }
        
        /* ============================================= */
        /* ESTILOS OPTIMIZADOS PARA MODAL DE CONFIRMACIÓN */
        /* ============================================= */

        #modalConfirmacion .modal-container {
            max-width: 500px;
            border-radius: 12px;
            overflow-y: scroll;
        }

        #modalConfirmacion .confirmation-message {
            padding: 25px;
            text-align: left;
            background: white;
        }

        #modalConfirmacion .confirmation-message i {
            font-size: 48px;
            width: 80px;
            height: 80px;
            line-height: 80px;
            text-align: center;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: block;
            background: rgba(31, 145, 102, 0.1);
        }

        #modalConfirmacion #confirmIcon.fa-calendar-check {
            background: rgba(243, 156, 18, 0.1);
        }

        #modalConfirmacion .confirmation-message h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        #modalConfirmacion .confirmation-code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-size: 1.3rem;
            font-family: 'Courier New', monospace;
            text-align: center;
            margin: 15px 0;
            border: 1px solid #e0e0e0;
            color: #1F9166;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        #modalConfirmacion .confirmation-code i {
            font-size: 18px;
            width: auto;
            height: auto;
            margin: 0;
            background: none;
        }

        #modalConfirmacion .confirmation-details {
            margin: 20px 0;
        }

        #modalConfirmacion .confirmation-details > div {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }

        #modalConfirmacion .confirmation-details .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.1rem;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px dashed #1F9166;
        }

        #modalConfirmacion .confirmation-details .total-row strong {
            color: #1F9166;
            font-size: 1.2rem;
        }



        #modalConfirmacion #confirmInfo ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }

        #modalConfirmacion #confirmInfo li {
            margin-bottom: 8px;
            color: #555;
            list-style-type: none;
            position: relative;
            padding-left: 25px;
        }

        #modalConfirmacion #confirmInfo li:before {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            color: #1F9166;
        }

        #modalConfirmacion #confirmInfo li:nth-child(1):before { content: "\f058"; } /* check-circle */
        #modalConfirmacion #confirmInfo li:nth-child(2):before { content: "\f073"; } /* calendar */
        #modalConfirmacion #confirmInfo li:nth-child(3):before { content: "\f07a"; } /* shopping-cart */

        #modalConfirmacion .store-info {
            background: #f0f9f4;
            border-left: 4px solid #1F9166;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        #modalConfirmacion .store-info i {
            font-size: 18px;
            width: auto;
            height: auto;
            margin: 0;
            background: none;
        }

        #modalConfirmacion .btn-checkout {
            width: 100%;
            padding: 15px;
            background: #1F9166;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 20px;
        }

        #modalConfirmacion .btn-checkout:hover {
            background: #187a54;
        }

        /* Versión para apartado (colores naranja) */
        #modalConfirmacion #confirmInfo .apartado-info {
            border-left-color: #f39c12;
        }

        #modalConfirmacion #confirmInfo .apartado-info i {
            color: #f39c12;
        }

        #modalConfirmacion .fecha-limite-badge {
            background: #f39c12;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }

        #modalConfirmacion .direccion-tienda {
            margin-top: 15px;
            padding: 10px;
            background: white;
            border-radius: 6px;
            font-size: 0.9rem;
            border: 1px solid #e0e0e0;
        }

        #modalConfirmacion .direccion-tienda i {
            color: #f39c12;
            width: auto;
            height: auto;
            margin-right: 8px;
            font-size: 14px;
        }

        /* Resumen del pedido MEJORADO - SIN ENVÍO */
        .order-summary {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .summary-title {
            padding-bottom: 15px;
        }
        
        .summary-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        
        .summary-table th {
            text-align: left;
            padding: 10px 0;
            color: #666;
            font-weight: normal;
            border-bottom: 1px solid #eee;
        }
        
        .summary-table td {
            padding: 8px 0;
            color: #333;
            vertical-align: top;
        }
        
        .summary-table td:last-child {
            text-align: right;
        }
        
        .summary-table .subtotal-row td:last-child,
        .summary-table .iva-row td:last-child,
        .summary-table .total-row td:last-child {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
        }
        
        .summary-table .summary-usd {
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }
        
        .summary-table .summary-bs {
            font-size: 13px;
            color: #1F9166;
            font-weight: 500;
        }
        
        .summary-table .product-row td {
            border-bottom: 1px dashed #f0f0f0;
        }
        
        .summary-table .product-name {
            font-size: 0.95rem;
        }
        
        .summary-table .product-qty {
            color: #999;
            font-size: 0.9rem;
        }
        
        .summary-table .subtotal-row td {
            padding-top: 15px;
            border-top: 2px solid #eee;
        }
        
        .summary-table .iva-row td {
            color: #666;
        }
        
        .summary-table .total-row td {
            padding-top: 15px;
            border-top: 2px solid #1F9166;
        }
        
        .summary-table .total-row .summary-usd {
            font-size: 22px;
            font-weight: 800;
        }
        
        .summary-table .total-row .summary-bs {
            font-size: 15px;
            color: #1F9166;
        }
        
        .summary-actions {
            margin-top: 25px;
        }
        
        .btn-checkout {
            width: 100%;
            padding: 15px;
            background: #1F9166;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            margin-bottom: 15px;
        }

        .btn-listo {
            width: 100%;
            padding: 15px;
            background: #1F9166;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-checkout:hover {
            background: #187a54;
        }
        
        .btn-continue {
            width: 100%;
            padding: 15px;
            background: white;
            color: #1F9166;
            border: 2px solid #1F9166;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-continue:hover {
            background: #1F9166;
            color: white;
        }
        
        .btn-secondary {
            width: 100%;
            padding: 12px;
            background: #f5f5f5;
            color: #666;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }

        /* Carrito vacío */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-cart h2 {
            color: #666;
            margin-bottom: 15px;
        }
        
        .empty-cart p {
            color: #999;
            margin-bottom: 30px;
        }
        
        /* Información de la tienda */
        .store-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #1F9166;
        }
        
        .store-info h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .store-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .store-info i {
            color: #1F9166;
            width: 20px;
            margin-right: 8px;
        }
        
        /* MODAL DE SELECCIÓN DE TIPO DE PEDIDO */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-container {
            background: white;
            border-radius: 15px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: modalFadeIn 0.3s ease;
        }

        /* Estilos específicos para botones dentro del modal (balancear tamaños) */
        .modal-container .btn-secondary {
            padding: 10px 12px;
            font-size: 14px;
            width: auto;
        }

        .modal-container .btn-checkout {
            padding: 12px 14px;
            font-size: 15px;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 25px 30px;
            border-bottom: 2px solid #eee;
            position: relative;
        }
        
        .modal-header h2 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .modal-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .modal-close:hover {
            color: #e74c3c;
            background: #fef2f2;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .modal-footer {
            padding: 20px 30px;
            border-top: 2px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }
        
        /* Tarjetas de opciones */
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 600px) {
            .options-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .option-card {
            border: 2px solid #eee;
            border-radius: 12px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .option-card.selected {
            border-color: #1F9166;
            background: #f0f9f4;
        }
        
        .option-card.popular::before {
            content: "MÁS SOLICITADO";
            position: absolute;
            top: 10px;
            right: -30px;
            background: #f39c12;
            color: white;
            padding: 5px 30px;
            font-size: 0.8rem;
            font-weight: bold;
            transform: rotate(45deg);
        }
        
        .option-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .option-icon.digital {
            color: #1F9166;
        }
        
        .option-icon.apartado {
            color: #f39c12;
        }
        
        .option-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .option-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .option-features {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }
        
        .option-features li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }
        
        .option-features li i {
            width: 20px;
        }
        
        .option-features li i.fa-check-circle {
            color: #1F9166;
        }
        
        .option-features li i.fa-clock {
            color: #f39c12;
        }
        
        .option-features li i.fa-store {
            color: #3498db;
        }
        
        /* Resumen dentro del modal */
        .modal-summary {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .modal-summary h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .modal-summary-items {
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 15px;
        }
        
        .modal-summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #ddd;
        }
        
        .modal-summary-item .item-name {
            flex: 2;
            font-size: 0.95rem;
        }
        
        .modal-summary-item .item-qty {
            flex: 1;
            text-align: center;
            color: #666;
        }
        
        .modal-summary-item .item-price {
            flex: 1;
            text-align: right;
            font-weight: 500;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 1px;
        }
        
        .modal-summary-item .modal-usd,
        .modal-summary-total .modal-usd {
            font-size: 14px;
            font-weight: 700;
            color: #333;
        }
        
        .modal-summary-item .modal-bs,
        .modal-summary-total .modal-bs {
            font-size: 12px;
            color: #1F9166;
            font-weight: 500;
        }
        
        .modal-summary-total {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 1.2rem;
            padding-top: 15px;
            border-top: 2px solid #ddd;
        }
        
        .modal-summary-total .item-price {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
        }
        
        .modal-summary-total .modal-usd {
            font-size: 18px;
            font-weight: 800;
        }
        
        .modal-summary-total .modal-bs {
            font-size: 14px;
            color: #1F9166;
        }
        
        /* Formulario de datos adicionales */
        .extra-fields {
            display: none;
            margin-top: 25px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        
        .extra-fields.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1F9166;
        }

        .payment-info-grid {
            display: grid;
            gap: 10px;
            margin-bottom: 10px;
        }

        .payment-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .payment-info-row span {
            font-size: 14px;
            color: #444;
            font-weight: 600;
        }

        .payment-info-value {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .payment-info-value span {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .copy-btn {
            padding: 6px 10px;
            border: 1px solid #1F9166;
            background: #1F9166;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.2s ease;
        }

        .copy-btn:hover {
            background: #187a54;
        }

        .file-upload-wrapper {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .file-upload-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 6px;
            border: 1px solid #1F9166;
            background: white;
            color: #1F9166;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .file-upload-button:hover {
            background: #1F9166;
            color: white;
        }

        .file-upload-filename {
            color: #666;
            font-size: 13px;
            flex: 1 1 100%;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Mensajes de confirmación */
        .confirmation-message {
            text-align: center;
            padding: 30px;
        }
        
        .confirmation-message i {
            font-size: 5rem;
            color: #1F9166;
            margin-bottom: 20px;
        }
        
        .confirmation-message h3 {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .confirmation-code {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            font-size: 1.5rem;
            font-family: monospace;
            margin: 20px 0;
            border: 2px dashed #1F9166;
        }
        
        .confirmation-details {
            margin: 20px 0;
            color: #666;
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
            z-index: 999999;
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
            border-left: 4px solid #f39c12;
        }
        
        .toast i {
            font-size: 1.5rem;
        }
        
        .toast.success i {
            color: #1F9166;
        }
        
        .toast.error i {
            color: #e74c3c;
        }
        
        .toast.warning i {
            color: #f39c12;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .toast-message {
            color: #666;
            font-size: 0.9rem;
        }
        
        .toast-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #999;
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
        
        /* Spinner de carga */
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #1F9166;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        .loading-content {
            text-align: center;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .loading-content p {
            margin-top: 15px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="cart-page container">
        <div class="cart-container">
            <div class="page-header">
                <h1><i class="fas fa-shopping-cart"></i> Carrito de Compras</h1>
                <p>Revisa los productos en tu carrito antes de finalizar</p>
            </div>
            
            <?php if (empty($carrito)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Tu carrito está vacío</h2>
                    <p>Agrega productos para comenzar tu compra</p>
                </div>
            <?php else: ?>
                <div class="cart-content">
                    <!-- Lista de productos -->
                    <div class="cart-items">
                        <div class="cart-header">
                            <div>Producto</div>
                            <div>Precio Unitario</div>
                            <div>Cantidad</div>
                            <div>Total</div>
                        </div>
                        
                        <?php foreach ($carrito as $item): ?>
                            <div class="cart-item" id="item-<?php echo $item['id']; ?>">
                                <button class="item-remove" type="button" onclick="eliminarDelCarrito(<?php echo $item['id']; ?>)" aria-label="Eliminar producto"><i class="fas fa-times"></i></button>
                              
                                <div class="item-info">
                                    <?php if (!empty($item['imagen'])): ?>
                                        <img src="<?php echo $item['imagen']; ?>" 
                                             alt="<?php echo htmlspecialchars($item['nombre']); ?>" 
                                             class="item-image">
                                    <?php else: ?>
                                        <div class="item-image-placeholder">
                                            <i class="fas fa-image"></i>
                                            <span>Sin imagen</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="item-details">
                                        <h3><?php echo htmlspecialchars($item['nombre']); ?></h3>
                                        <?php if (!empty($item['categoria'])): ?>
                                            <span class="item-category"><?php echo $item['categoria']; ?></span>
                                        <?php endif; ?>
                                        <p style="font-size: 12px; color: #999;">Stock disponible: <?php echo $item['stock']; ?> unidades</p>
                                        <?php if (!empty($item['descripcion'])): ?>
                                            <p style="font-size: 12px; color: #666; margin-top: 5px;"><?php echo htmlspecialchars(substr($item['descripcion'], 0, 60)); ?>...</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="item-price">
                                    <?php 
                                    $precios = formatearMonedaDual($item['precio']);
                                    if (!empty($item['promocion']['nombre']) || (isset($item['precio_original']) && $item['precio_original'] > $item['precio'])): 
                                        $preciosOriginal = formatearMonedaDual($item['precio_original'] ?? $item['precio']);
                                    ?>
                                        <span class="item-price-original"><?php echo $preciosOriginal['bs']; ?></span>
                                        <span class="item-price-sale"><?php echo $precios['usd']; ?></span>
                                        <span class="moneda-bs"><?php echo $precios['bs']; ?></span>
                                        <?php if (!empty($item['promocion']['nombre'])): ?>
                                            <span class="item-price-promo"><?php echo htmlspecialchars($item['promocion']['nombre']); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="moneda-usd"><?php echo $precios['usd']; ?></span>
                                        <span class="moneda-bs"><?php echo $precios['bs']; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-quantity">
                                    <button class="quantity-btn minus" 
                                            onclick="cambiarCantidad(<?php echo $item['id']; ?>, -1)"
                                            <?php echo $item['cantidad'] <= 1 ? 'disabled' : ''; ?>>
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           class="quantity-input" 
                                           value="<?php echo $item['cantidad']; ?>" 
                                           min="1" 
                                           max="<?php echo $item['stock']; ?>"
                                           onchange="actualizarCantidad(<?php echo $item['id']; ?>, this.value)">
                                    <button class="quantity-btn plus" 
                                            onclick="cambiarCantidad(<?php echo $item['id']; ?>, 1)"
                                            <?php echo $item['cantidad'] >= $item['stock'] ? 'disabled' : ''; ?>>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                
                                <?php $preciosTotal = formatearMonedaDual($item['precio'] * $item['cantidad']); ?>
                                <div class="item-total" id="total-<?php echo $item['id']; ?>">
                                    <span class="moneda-usd"><?php echo $preciosTotal['usd']; ?></span>
                                    <span class="moneda-bs"><?php echo $preciosTotal['bs']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Resumen del pedido DETALLADO - SIN ENVÍO -->
                    <div class="order-summary">
                        <h2 class="summary-title">Detalle de Facturación</h2>
                        
                        <table class="summary-table">
                            <?php 
                            $preciosSubtotal = formatearMonedaDual($subtotal);
                            $preciosIva = formatearMonedaDual($iva);
                            $preciosTotal = formatearMonedaDual($total);
                            ?>
                            <tr class="subtotal-row">
                                <td colspan="2"><strong>Subtotal</strong></td>
                                <td>
                                    <span class="summary-usd"><strong><?php echo $preciosSubtotal['usd']; ?></strong></span>
                                    <span class="summary-bs"><?php echo $preciosSubtotal['bs']; ?></span>
                                </td>
                            </tr>
                            
                            <tr class="iva-row">
                                <td colspan="2">IVA (16%)</td>
                                <td>
                                    <span class="summary-usd"><?php echo $preciosIva['usd']; ?></span>
                                    <span class="summary-bs"><?php echo $preciosIva['bs']; ?></span>
                                </td>
                            </tr>
                            
                            <tr class="total-row">
                                <td colspan="2"><strong>TOTAL</strong></td>
                                <td>
                                    <span class="summary-usd"><strong><?php echo $preciosTotal['usd']; ?></strong></span>
                                    <span class="summary-bs"><?php echo $preciosTotal['bs']; ?></span>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="summary-actions">
                            <button class="btn-checkout" onclick="abrirModalPago()">
                                <i class="fas fa-credit-card"></i> Proceder al Pago
                            </button>
                            <button class="btn-continue" onclick="cancelarCompra()" type="button">
                                <i class="fas fa-ban"></i> Cancelar Pedido
                            </button>
                            <button class="btn-secondary" onclick="seguirComprando()" type="button">
                                <i class="fas fa-arrow-left"></i> Seguir Comprando
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- MODAL DE SELECCIÓN DE TIPO DE PEDIDO -->
    <div class="modal-overlay" id="modalPago">
        <div class="modal-container">
            <div class="modal-header">
                <h2>Selecciona tu método de compra</h2>
                <p>Elige cómo quieres completar tu pedido</p>
                <button class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            
            <div class="modal-body">
                <!-- Resumen rápido del pedido en el modal -->
                <div class="modal-summary">
                    <h3>Resumen de tu compra</h3>
                    <div class="modal-summary-items" id="modalSummaryItems">
                        <?php foreach ($carrito as $item): ?>
                        <?php $itemTotal = formatearMonedaDual($item['precio'] * $item['cantidad']); ?>
                        <div class="modal-summary-item">
                            <span class="item-name"><?php echo htmlspecialchars($item['nombre']); ?></span>
                            <span class="item-qty">x<?php echo $item['cantidad']; ?></span>
                            <span class="item-price">
                                <span class="modal-usd"><?php echo $itemTotal['usd']; ?></span>
                                <span class="modal-bs"><?php echo $itemTotal['bs']; ?></span>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php $totalFormateado = formatearMonedaDual($total); ?>
                    <div class="modal-summary-total">
                        <span>Total a pagar:</span>
                        <span>
                            <span class="modal-usd"><?php echo $totalFormateado['usd']; ?></span>
                            <span class="modal-bs"><?php echo $totalFormateado['bs']; ?></span>
                        </span>
                    </div>
                </div>
                
                <!-- Opciones de compra -->
                <div class="options-grid">
                    <!-- Opción 1: Pedido Digital (Compra inmediata) -->
                    <div class="option-card popular" id="optionDigital" onclick="seleccionarOpcion('digital')">
                        <div class="option-icon digital">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="option-title">Pedido Digital</div>
                        <div class="option-description">
                            Compra inmediata - Te facturamos ahora y coordinas la entrega
                        </div>
                        <ul class="option-features">
                            <li><i class="fas fa-check-circle"></i> Facturación inmediata</li>
                            <li><i class="fas fa-check-circle"></i> Garantía desde hoy</li>
                            <li><i class="fas fa-check-circle"></i> Coordinas entrega con vendedor</li>
                            <li><i class="fas fa-check-circle"></i> Retiro en tienda o delivery*</li>
                        </ul>
                    </div>
                    
                    <!-- Opción 2: Apartado / Reserva -->
                    <div class="option-card" id="optionApartado" onclick="seleccionarOpcion('apartado')">
                        <div class="option-icon apartado">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="option-title">Apartado / Reserva</div>
                        <div class="option-description">
                            Reserva tus productos por 7 días y paga después en tienda
                        </div>
                        <ul class="option-features">
                            <li><i class="fas fa-clock"></i> Aparta por 7 días</li>
                            <li><i class="fas fa-store"></i> Pago exclusivo en tienda física</li>
                            <li><i class="fas fa-check-circle"></i> Sin pago en línea</li>
                            <li><i class="fas fa-check-circle"></i> Retiro inmediato al pagar</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Campos adicionales según la opción seleccionada -->
                <div class="extra-fields" id="camposDigital">
                    <h3>Información para coordinar tu pedido</h3>
                    <div class="form-group">
                        <label for="tipoEntrega">¿Cómo prefieres recibir tu pedido?</label>
                        <select id="tipoEntrega">
                            <option value="tienda">Retirar en tienda</option>
                            <option value="domicilio">Solicitar delivery (a coordinar con vendedor)</option>
                        </select>
                    </div>
                    <div class="form-group" id="campoDireccion" style="display: none;">
                        <label for="direccion">Dirección para delivery</label>
                        <textarea id="direccion" rows="2" placeholder="Calle, número, sector, referencia..."></textarea>
                        <small style="color: #999;">El vendedor se comunicará para confirmar costo y disponibilidad</small>
                    </div>
                    <div class="form-group">
                        <label for="telefonoContacto">Teléfono de contacto <span style="color: #e74c3c;">*</span></label>
                        <input type="tel" id="telefonoContacto" placeholder="0412-1234567" required>
                    </div>
                    <div class="form-group">
                        <label for="observaciones">Notas para el vendedor (opcional)</label>
                        <textarea id="observaciones" rows="2" placeholder="Ej: Prefiero que me llamen en la tarde..."></textarea>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600;color:#333;margin-bottom:10px;display:block;">
                            ¿Cómo prefieres que te contactemos? <span style="color:#e74c3c;">*</span>
                        </label>
                        <!-- Tarjetas de canal cargadas dinámicamente según integraciones activas -->
                        <div id="canalComunicacionGrid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:6px;">
                            <!-- Se renderizan via JS al abrir el modal -->
                        </div>
                        <input type="hidden" id="metodoComunicacion" value="whatsapp">
                        <small style="color:#999;font-size:11px;">Solo se muestran los canales habilitados por la tienda. El vendedor te contactará para coordinar pago y entrega.</small>
                    </div>
<style>
.canal-card {
    border: 2px solid #e0e0e0; border-radius: 10px; padding: 12px 10px;
    cursor: pointer; transition: all 0.2s; text-align: center;
    background: #fafafa; display: flex; flex-direction: column;
    align-items: center; gap: 6px;
}
.canal-card:hover { border-color: #1F9166; background: #f0f9f4; }
.canal-card.selected { border-color: #1F9166; background: #e8f6f1; box-shadow: 0 0 0 3px rgba(31,145,102,0.15); }
.canal-card.disabled { opacity: 0.35; cursor: not-allowed; pointer-events: none; }
.canal-card i { font-size: 22px; }
.canal-card span { font-size: 12px; font-weight: 600; color: #333; }
.canal-card small { font-size: 10px; color: #888; }
</style>
<script>
// Datos de canales (colores e iconos)
const CANALES_INFO = {
    whatsapp:       { icon:'fab fa-whatsapp',  color:'#25D366', label:'WhatsApp',        desc:'Mensaje directo al vendedor' },
    email:          { icon:'fas fa-envelope',  color:'#d93025', label:'Gmail / Email',   desc:'Correo de confirmación'      },
    telegram:       { icon:'fab fa-telegram',  color:'#0088cc', label:'Telegram',        desc:'Mensaje vía bot Telegram'    },
    notificaciones: { icon:'fas fa-bell',       color:'#f39c12', label:'Notificación Web', desc:'Pago con comprobante'         },
};

async function cargarCanalesDisponibles() {
    const grid = document.getElementById('canalComunicacionGrid');
    if (!grid) return;
    grid.innerHTML = '<p style="color:#999;font-size:12px;grid-column:span 2;text-align:center;padding:10px;">Cargando canales...</p>';

    // Estado de canales: por defecto todos deshabilitados salvo WhatsApp
    let estado = {
        whatsapp:       { enabled: true  },
        email:          { enabled: false },
        telegram:       { enabled: false },
        notificaciones: { enabled: false },
    };

    try {
        const r = await fetch('<?php echo BASE_URL; ?>/api/get_integraciones.php');
        const j = await r.json();
        if (j.success && j.integraciones) {
            const int = j.integraciones;
            estado.whatsapp.enabled       = int.whatsapp?.enabled       ?? true;
            estado.email.enabled          = int.email?.enabled          ?? false;
            estado.telegram.enabled       = int.telegram?.enabled       ?? false;
            estado.notificaciones.enabled = int.notificaciones?.enabled ?? false;
        }
    } catch(e) {
        // Si falla la API, mostrar solo WhatsApp habilitado
        console.warn('No se pudo cargar config de integraciones:', e);
    }

    // Siempre mostrar las 4 tarjetas — deshabilitadas si no están activas
    const orden = ['whatsapp', 'email', 'telegram', 'notificaciones'];
    grid.innerHTML = '';

    // Ajustar grid según cuántos están activos (siempre 2 columnas)
    grid.style.gridTemplateColumns = 'repeat(2, 1fr)';

    let primerActivo = null;

    orden.forEach((canal) => {
        const info     = CANALES_INFO[canal];
        const activo   = estado[canal].enabled;
        const card     = document.createElement('div');

        card.className    = 'canal-card' + (!activo ? ' disabled' : '');
        card.dataset.canal = canal;
        card.title         = activo ? `Contactar por ${info.label}` : 'Canal no habilitado por la tienda';

        card.innerHTML = `
            <i class="${info.icon}" style="color:${activo ? info.color : '#ccc'};"></i>
            <span>${info.label}</span>
            <small>${activo ? info.desc : 'No disponible'}</small>
            ${activo ? '' : '<small style="color:#e74c3c;font-size:9px;">No habilitado</small>'}`;

        if (activo) {
            card.addEventListener('click', () => {
                grid.querySelectorAll('.canal-card').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                document.getElementById('metodoComunicacion').value = canal;
            });
            if (!primerActivo) primerActivo = { card, canal };
        }

        grid.appendChild(card);
    });

    // Seleccionar el primer canal activo por defecto
    if (primerActivo) {
        primerActivo.card.classList.add('selected');
        document.getElementById('metodoComunicacion').value = primerActivo.canal;
    } else {
        // Ninguno activo — fallback a WhatsApp de todas formas
        document.getElementById('metodoComunicacion').value = 'whatsapp';
        const firstCard = grid.querySelector('.canal-card');
        if (firstCard) {
            firstCard.classList.remove('disabled');
            firstCard.classList.add('selected');
        }
    }
}
</script>
                </div>
                
                <div class="extra-fields" id="camposApartado">
                    <h3>Información para tu apartado</h3>
                    <div class="store-info" style="margin-bottom: 15px; background: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: 8px;">
                        <i class="fas fa-exclamation-triangle" style="color: #856404;"></i> <strong>Política de Apartado</strong>
                        <p style="margin-top: 8px; font-size: 13px;">Para apartar tus productos debes pagar un <strong>adelanto mínimo del 25%</strong> del total. El resto podrás cancelarlo en tienda dentro de los 7 días.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="montoAdelanto">Monto del Adelanto (25% mínimo) *</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" id="montoAdelanto" class="form-control" placeholder="$" min="0" step="0.01" style="flex: 1;">
                            <span style="color: #666; font-size: 13px;" id="montoMinimoLabel"></span>
                        </div>
                        <small style="color: #999;">Ingresa el monto que pagarás ahora como apartado</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="fechaPrimeraCuota">Fecha para pagar el resto *</label>
                        <input type="date" id="fechaPrimeraCuota" class="form-control" style="width: 100%;">
                        <small style="color: #999;">Elige la fecha en que completarás el pago (máximo 7 días)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefonoApartado">Teléfono de contacto <span style="color: #e74c3c;">*</span></label>
                        <input type="tel" id="telefonoApartado" placeholder="0412-1234567" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notasApartado">Notas para el apartado (opcional)</label>
                        <textarea id="notasApartado" rows="2" placeholder="Indica alguna instrucción especial..."></textarea>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn-continue" onclick="cerrarModal()" style="width: auto; padding: 10px 25px;">Cancelar</button>
                <button class="btn-listo" id="btnConfirmar" onclick="confirmarOperacion()" disabled style="width: auto; padding: 10px 25px;">
                    <i class="fas fa-check"></i> Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación (después de procesar) -->
    <div class="modal-overlay" id="modalConfirmacion">
        <div class="modal-container">
            <div class="modal-body confirmation-message">
                <i class="fas fa-check-circle" id="confirmIcon"></i>
                <h3 id="confirmTitle">¡Pedido confirmado!</h3>
                <div class="confirmation-code" id="confirmCode"></div>
                <div class="confirmation-details" id="confirmDetails"></div>
                <div class="store-info" id="confirmInfo"></div>
                <button class="btn-checkout" onclick="finalizarCompra()" style=" min-width: 200px; margin: 20px auto 0 auto; padding: 12px 25px; background: #1F9166; border: none; border-radius: 6px; color: white; font-size: 16px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                    Volver al inicio
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de pago (para reservas) -->
    <div class="modal-overlay" id="modalPagoReserva">
        <div class="modal-container" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fas fa-university"></i> Confirmar Pago</h2>
                <button class="modal-close" onclick="cerrarModalPagoReserva()">&times;</button>
            </div>
            <div class="modal-body" id="pagoReservaContent">
                <!-- Selector de método de pago -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Selecciona método de pago:</label>
                    <select id="metodoPagoSelect" onchange="actualizarDatosMetodoPago()" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                        <option value="">Cargando métodos...</option>
                    </select>
                </div>
                
                <!-- Datos del método de pago -->
                <div id="datosMetodoPago" style="display: none; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                    <div style="font-size: 14px; color: #333; margin-bottom: 12px;">
                        <strong id="metodoNombre">Método seleccionado</strong>
                    </div>
                    <div class="payment-info-grid">
                        <div class="payment-info-row">
                            <span>Banco</span>
                            <div class="payment-info-value">
                                <span id="paymentBanco">-</span>
                                <button type="button" class="copy-btn" onclick="copyPaymentInfo('paymentBanco')">Copiar</button>
                            </div>
                        </div>
                        <div class="payment-info-row">
                            <span>Cédula</span>
                            <div class="payment-info-value">
                                <span id="paymentCedula">-</span>
                                <button type="button" class="copy-btn" onclick="copyPaymentInfo('paymentCedula')">Copiar</button>
                            </div>
                        </div>
                        <div class="payment-info-row">
                            <span>Teléfono</span>
                            <div class="payment-info-value">
                                <span id="paymentTelefono">-</span>
                                <button type="button" class="copy-btn" onclick="copyPaymentInfo('paymentTelefono')">Copiar</button>
                            </div>
                        </div>
                        <div class="payment-info-row" style="display:none;" id="paymentCuentaRow">
                            <span>Cuenta</span>
                            <div class="payment-info-value">
                                <span id="paymentCuenta">-</span>
                                <button type="button" class="copy-btn" onclick="copyPaymentInfo('paymentCuenta')">Copiar</button>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 10px; font-size: 13px; color: #555;">
                        <p style="margin: 5px 0;"><strong>Monto a pagar:</strong> <span id="montoAPagar" style="color: #1F9166; font-weight: bold;">Bs 0.00</span></p>
                    </div>
                </div>
                
                <!-- Campo para referencia o comprobante -->
                <div id="camposConfirmarPago" style="display: none; margin-top: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Confirmar pago:</label>
                    
                    <div style="margin-bottom: 10px;">
                        <label style="font-size: 13px; color: #666;">Número de referencia:</label>
                        <input type="text" id="referenciaPago" placeholder="Ingresa la referencia que te dio el banco" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; margin-top: 5px; box-sizing: border-box;">
                    </div>
                    
                    <div>
                        <label style="font-size: 13px; color: #666; display: block; margin-bottom: 8px;">Adjuntar foto del comprobante:</label>
                        <div class="file-upload-wrapper">
                            <button type="button" class="btn btn-outline file-upload-button" onclick="document.getElementById('comprobantePago').click();">
                                <i class="fas fa-upload"></i> Seleccionar comprobante
                            </button>
                            <span class="file-upload-filename" id="comprobantePagoFilename">No se ha seleccionado ningún archivo</span>
                        </div>
                        <input type="file" id="comprobantePago" accept="image/*" multiple style="display:none;" />
                    </div>
                </div>
                
                <button id="btnConfirmarPago" onclick="confirmarPagoYReservar()" disabled style="width: 100%; padding: 14px; background: #ccc; border: none; border-radius: 6px; color: white; font-size: 16px; font-weight: 600; cursor: not-allowed; margin-top: 20px;">
                    <i class="fas fa-check"></i> Selecciona un método de pago
                </button>
            </div>
        </div>
    </div>

    <!-- Overlay de carga -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Procesando tu solicitud...</p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="footer-content-custom">
            <div class="footer-section-custom">
                <h3>Acerca de Nosotros</h3>
                <p>INVERSIONES ROJAS 2016. C.A. es una empresa especializada en repuestos y vehículos Bera, ofreciendo productos de alta calidad y el mejor servicio al cliente.</p>
            </div>
            
            <div class="footer-section-custom">
                <h3>Contacto</h3>
                <p><i class="fas fa-map-marker-alt"></i> AV ARAGUA LOCAL NRO 286 SECTOR ANDRES ELOY BLANCO, MARACAY ARAGUA ZONA POSTAL 2102</p>
                <p><i class="fas fa-phone"></i> 0243-2343044</p>
                <p><i class="fas fa-envelope"></i> 2016rojasinversiones@gmail.com</p>
            </div>
            
            <div class="footer-section-custom">
                <h3>Enlaces Rápidos</h3>
                <a href="<?php echo BASE_URL; ?>/app/views/layouts/inicio.php">Inicio</a>
                <a href="<?php echo BASE_URL; ?>/app/views/pages/motos.php">Motos</a>
                <a href="<?php echo BASE_URL; ?>/app/views/pages/repuestos.php">Repuestos</a>
                <a href="<?php echo BASE_URL; ?>/app/views/pages/contacto.php">Contacto</a>
            </div>
            
            <div class="footer-section-custom">
                <h3>Síguenos</h3>
                <div class="social-links-custom">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom-custom">
            <p>&copy; 2023 Inversiones Rojas. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/inv-notifications.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/components/user-panel.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/script.js"></script>
    <script>
        // Variables globales accessibles
        let opcionSeleccionada = '';
        let datosCarrito = <?php echo json_encode($carrito); ?>;
        let totalGeneral = <?php echo $total; ?>;
        let subtotalGeneral = <?php echo $subtotal; ?>;
        let ivaGeneral = <?php echo $iva; ?>;
        let TASA_CAMBIO = <?php echo TASA_CAMBIO; ?>;
        
        function fmtBsLocal(amount) {
            return 'Bs ' + (amount * TASA_CAMBIO).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        let _pedidoData = null;
        let fechaLimite = ''; // Para usar en mostrarConfirmacion
        
        // Funciones del carrito
        function cambiarCantidad(itemId, cambio) {
            const input = document.querySelector(`#item-${itemId} .quantity-input`);
            let nuevaCantidad = parseInt(input.value) + cambio;
            const max = parseInt(input.max);
            const min = parseInt(input.min);
            
            if (nuevaCantidad >= min && nuevaCantidad <= max) {
                input.value = nuevaCantidad;
                actualizarItem(itemId, nuevaCantidad);
            }
        }
        
        function actualizarCantidad(itemId, cantidad) {
            const input = document.querySelector(`#item-${itemId} .quantity-input`);
            const max = parseInt(input.max);
            const min = parseInt(input.min);
            
            if (cantidad >= min && cantidad <= max) {
                actualizarItem(itemId, parseInt(cantidad));
            } else {
                input.value = Math.min(Math.max(cantidad, min), max);
                actualizarItem(itemId, parseInt(input.value));
            }
        }
        
        function actualizarItem(itemId, cantidad) {
            fetch('<?php echo BASE_URL; ?>/api/update_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: itemId, quantity: cantidad })
            })
            .then(response => {
                if (!response.ok) return response.text().then(t => { throw new Error(t || 'HTTP ' + response.status); });
                const ct = response.headers.get('content-type') || '';
                if (ct.indexOf('application/json') !== -1) return response.json();
                return response.text().then(t => { throw new Error(t || 'Respuesta no JSON'); });
            })
            .then(data => {
                if (data.success) {
                    const minusBtn = document.querySelector(`#item-${itemId} .minus`);
                    const plusBtn = document.querySelector(`#item-${itemId} .plus`);
                    minusBtn.disabled = cantidad <= 1;
                    plusBtn.disabled = cantidad >= parseInt(document.querySelector(`#item-${itemId} .quantity-input`).max);
                    
                    // Usar precio_unitario del API (en USD)
                    let precio = data.precios_unitarios?.[itemId];
                    if (!precio || isNaN(precio)) {
                        // Fallback: del elemento .moneda-usd dentro de .item-price
                        const usdPriceEl = document.querySelector(`#item-${itemId} .item-price .moneda-usd`);
                        if (usdPriceEl) {
                            precio = parseFloat(usdPriceEl.textContent.replace(/[^0-9.]/g, '')) || 0;
                        }
                    }
                    
                    if (precio && !isNaN(precio)) {
                        const nuevoTotal = precio * cantidad;
                        const totalBs = nuevoTotal * TASA_CAMBIO;
                        const totalEl = document.getElementById(`total-${itemId}`);
                        if (totalEl) {
                            totalEl.innerHTML = `<span class="moneda-usd">$${nuevoTotal.toFixed(2)}</span><span class="moneda-bs">Bs ${totalBs.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.')}</span>`;
                        }
                    }
                    
                    actualizarResumen(data);
                    actualizarModalSummary();
                    actualizarDrawerCantidad(itemId, cantidad);
                } else {
                    alert('Error: ' + data.message);
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar el carrito');
                location.reload();
            });
        }
        
        function actualizarDrawerCantidad(itemId, cantidad) {
            const drawerItem = document.querySelector(`.cart-drawer-item[data-id="${itemId}"]`);
            if (drawerItem) {
                const meta = drawerItem.querySelector('.cart-drawer-meta small');
                if (meta) {
                    const precioText = meta.textContent.split(' × ')[1] || '';
                    meta.textContent = `${cantidad} × ${precioText}`;
                }
            }
            const totalItems = cantidad;
            const countEl = document.querySelector('.cart-count');
            if (countEl) {
                let suma = 0;
                document.querySelectorAll('.cart-item .quantity-input').forEach(inp => {
                    suma += parseInt(inp.value) || 0;
                });
                countEl.textContent = suma;
            }
            if (window.updateCount) {
                let suma = 0;
                document.querySelectorAll('.cart-item .quantity-input').forEach(inp => {
                    suma += parseInt(inp.value) || 0;
                });
                window.updateCount(suma);
            }
        }
        
        async function eliminarDelCarrito(itemId) {
            const el = document.getElementById(`item-${itemId}`);
            const nombre = el?.querySelector('h3')?.textContent || 'este producto';
            
            const confirmed = typeof showConfirm === 'function'
                ? await showConfirm({
                    title: 'Eliminar producto',
                    message: `¿Estás seguro de eliminar "${nombre}" del carrito?`,
                    confirmText: 'Eliminar',
                    cancelText: 'Cancelar',
                    type: 'warning'
                })
                : confirm('¿Eliminar este producto del carrito?');

            if (!confirmed) return;

            // Animación de eliminación
            if (el) {
                el.style.transition = 'all 0.3s ease';
                el.style.opacity = '0.5';
            }

            fetch('<?php echo BASE_URL; ?>/api/update_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: itemId, quantity: 0 })
            })
            .then(response => {
                if (!response.ok) return response.text().then(t => { throw new Error(t || 'HTTP ' + response.status); });
                const ct = response.headers.get('content-type') || '';
                if (ct.indexOf('application/json') !== -1) return response.json();
                return response.text().then(t => { throw new Error(t || 'Respuesta no JSON'); });
            })
            .then(data => {
                if (data.success) {
                    if (el) {
                        el.style.transition = 'all 0.3s ease';
                        el.style.opacity = '0';
                        el.style.transform = 'translateX(-20px)';
                        setTimeout(() => el.remove(), 300);
                    }

                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.total_items;
                        if (data.total_items === 0) cartCount.style.display = 'none';
                    }

                    actualizarResumen(data);
                    actualizarModalSummary();

                    if (window.Toast) {
                        Toast.success('Producto eliminado del carrito', 'Eliminado', 2000);
                    }

                    setTimeout(() => {
                        const itemsRestantes = document.querySelectorAll('.cart-item').length;
                        if (itemsRestantes === 0) location.reload();
                    }, 350);
                } else {
                    if (el) el.style.opacity = '1';
                    if (window.Toast) Toast.error(data.message || 'Error al eliminar');
                    else alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Error:', err);
                if (el) el.style.opacity = '1';
                if (window.Toast) Toast.error('Error al eliminar el producto');
                else alert('Error al eliminar el producto del carrito');
            });
        }

        function cancelarCompra() {
            if (!confirm('¿Cancelar la compra y vaciar el carrito?')) return;
            fetch('<?php echo BASE_URL; ?>/api/clear_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            })
            .then(response => {
                if (!response.ok) return response.text().then(t => { throw new Error(t || 'HTTP ' + response.status); });
                const ct = response.headers.get('content-type') || '';
                if (ct.indexOf('application/json') !== -1) return response.json();
                return response.text().then(t => { throw new Error(t || 'Respuesta no JSON'); });
            })
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.cart-item').forEach(el => el.remove());
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) { cartCount.textContent = 0; cartCount.style.display = 'none'; }
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error al vaciar el carrito');
            });
        }
        
        function fmtBsLocal(amount) {
            return 'Bs ' + (amount * TASA_CAMBIO).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        
        function actualizarResumen(data) {
            let subtotalUsd = 0;
            if (data && typeof data.subtotal === 'number') {
                subtotalUsd = data.subtotal;
            } else {
                document.querySelectorAll('.cart-item').forEach(item => {
                    const usdEl = item.querySelector('.item-total .moneda-usd');
                    if (usdEl) {
                        const val = parseFloat(usdEl.textContent.replace(/[^0-9.]/g, '')) || 0;
                        subtotalUsd += val;
                    }
                });
            }

            const ivaUsd = +(subtotalUsd * 0.16).toFixed(2);
            const totalUsd = +(subtotalUsd + ivaUsd).toFixed(2);
            const ivaBs = ivaUsd * TASA_CAMBIO;
            const totalBs = totalUsd * TASA_CAMBIO;

            const subEl = document.querySelector('.summary-table .subtotal-row td:last-child');
            const ivaEl = document.querySelector('.summary-table .iva-row td:last-child');
            const totEl = document.querySelector('.summary-table .total-row td:last-child');
            if (subEl) subEl.innerHTML = `<span class="summary-usd"><strong>$${subtotalUsd.toFixed(2)}</strong></span><span class="summary-bs">${fmtBsLocal(subtotalUsd)}</span>`;
            if (ivaEl) ivaEl.innerHTML = `<span class="summary-usd">$${ivaUsd.toFixed(2)}</span><span class="summary-bs">${fmtBsLocal(ivaUsd)}</span>`;
            if (totEl) totEl.innerHTML = `<span class="summary-usd"><strong>$${totalUsd.toFixed(2)}</strong></span><span class="summary-bs">${fmtBsLocal(totalUsd)}</span>`;

            totalGeneral = totalUsd;
            subtotalGeneral = subtotalUsd;
            ivaGeneral = ivaUsd;
        }
        
        function seguirComprando() {
            window.location.href = '<?php echo BASE_URL; ?>/app/views/layouts/inicio.php';
        }
        
        // NUEVAS FUNCIONES PARA EL MODAL
        
        function abrirModalPago() {
            // Verificar si el usuario está logueado
            <?php if (!$usuario_logueado): ?>
                if (confirm('Debes iniciar sesión para continuar con la compra. ¿Deseas iniciar sesión ahora?')) {
                    window.location.href = '<?php echo BASE_URL; ?>/app/views/auth/Login.php?redirect=carrito';
                }
                return;
            <?php endif; ?>
            
            document.getElementById('modalPago').classList.add('active');
            opcionSeleccionada = '';
            document.getElementById('btnConfirmar').disabled = true;
            
            // Resetear selección visual
            document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
            document.querySelectorAll('.extra-fields').forEach(f => f.classList.remove('active'));
        }
        
        function cerrarModal() {
            document.getElementById('modalPago').classList.remove('active');
        }
        
        async function seleccionarOpcion(opcion) {
            opcionSeleccionada = opcion;
            document.getElementById('btnConfirmar').disabled = false;
            
            // Remover selección anterior
            document.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
            
            if (opcion === 'digital') {
                document.getElementById('optionDigital').classList.add('selected');
                document.getElementById('optionApartado').classList.remove('selected');
                
                // Mostrar campos digital
                document.getElementById('camposDigital').classList.add('active');
                document.getElementById('camposApartado').classList.remove('active');
                // Cargar canales de comunicación disponibles
                if (typeof cargarCanalesDisponibles === 'function') cargarCanalesDisponibles();
            } else {
                document.getElementById('optionApartado').classList.add('selected');
                document.getElementById('optionDigital').classList.remove('selected');
                
                // Mostrar campos apartado
                document.getElementById('camposApartado').classList.add('active');
                document.getElementById('camposDigital').classList.remove('active');
                
                // Calcular 25% mínimo del total y mostrar (redondear para evitar errores de decimales)
                const montoMinimo = Math.round(totalGeneral * 0.25 * 100) / 100;
                document.getElementById('montoMinimoLabel').textContent = '(mínimo $' + montoMinimo.toFixed(2) + ' = ' + fmtBsLocal(montoMinimo) + ')';
                document.getElementById('montoAdelanto').value = montoMinimo.toFixed(2);
                
                // Configurar fecha máxima (7 días desde hoy)
                const hoy = new Date();
                const maxFecha = new Date(hoy);
                maxFecha.setDate(hoy.getDate() + 7);
                
                const fechaInput = document.getElementById('fechaPrimeraCuota');
                fechaInput.min = hoy.toISOString().split('T')[0];
                fechaInput.max = maxFecha.toISOString().split('T')[0];
                fechaInput.value = maxFecha.toISOString().split('T')[0];
            }
        }
        
        function actualizarModalSummary() {
            const modalItems = document.getElementById('modalSummaryItems');
            if (!modalItems) return;
            
            let subtotalUsd = 0;
            document.querySelectorAll('.cart-item').forEach(row => {
                const usdEl = row.querySelector('.item-total .moneda-usd');
                if (usdEl) {
                    const val = parseFloat(usdEl.textContent.replace(/[^0-9.]/g, '')) || 0;
                    subtotalUsd += val;
                }
            });
            
            const ivaUsd = +(subtotalUsd * 0.16).toFixed(2);
            const totalUsd = +(subtotalUsd + ivaUsd).toFixed(2);
            const totalBs = totalUsd * TASA_CAMBIO;
            
            const totalEl = document.querySelector('.modal-summary-total');
            if (totalEl) {
                totalEl.innerHTML = `
                    <span>Total a pagar:</span>
                    <span>
                        <span class="modal-usd">$${totalUsd.toFixed(2)}</span>
                        <span class="modal-bs">Bs ${totalBs.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.')}</span>
                    </span>
                `;
            }
        }
        
        function confirmarOperacion() {
            if (!opcionSeleccionada) {
                mostrarToast('Selecciona una opción de compra', 'error');
                return;
            }
            
            // Mostrar overlay de carga
            document.getElementById('loadingOverlay').classList.add('active');
            
            // Recopilar datos según la opción
            let datosCliente = {};
            
            if (opcionSeleccionada === 'digital') {
                datosCliente = {
                    tipo_entrega: document.getElementById('tipoEntrega').value,
                    direccion: document.getElementById('direccion').value,
                    telefono: document.getElementById('telefonoContacto').value,
                    observaciones: document.getElementById('observaciones').value
                };
                
                // Validar campos obligatorios
                if (!datosCliente.telefono) {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    mostrarToast('El teléfono de contacto es obligatorio', 'error');
                    return;
                }
                
                if (datosCliente.tipo_entrega === 'domicilio' && !datosCliente.direccion) {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    mostrarToast('La dirección es necesaria para coordinar el delivery', 'error');
                    return;
                }
            } else {
// Validaciones para APARTADO/RESERVA
                const montoAdelanto = parseFloat(document.getElementById('montoAdelanto').value) || 0;
                const fechaCuota = document.getElementById('fechaPrimeraCuota').value;
                const telefonoApartado = document.getElementById('telefonoApartado').value;
                const montoMinimo = Math.round(totalGeneral * 0.25 * 100) / 100;
                
                // Validar teléfono obligatorio
                if (!telefonoApartado) {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    mostrarToast('El teléfono de contacto es obligatorio', 'error');
                    return;
                }
                
                // Validar monto de adelantado (mínimo 25% del total) - permitir pequeña diferencia
                if (montoAdelanto < montoMinimo - 0.01) {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    mostrarToast('El monto del acercamiento debe ser al menos el 25% del total ($' + montoMinimo.toFixed(2) + ' / ' + fmtBsLocal(montoMinimo) + ')', 'error');
                    return;
                }
                
                // Validar que el monto no exceda el total
                if (montoAdelanto > totalGeneral) {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    mostrarToast('El monto del acercamiento no puede exceder el total ($' + totalGeneral.toFixed(2) + ' / ' + fmtBsLocal(totalGeneral) + ')', 'error');
                    return;
                }
                
                // Validar fecha de cuota
                if (!fechaCuota) {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    mostrarToast('Selecciona la fecha para pagar el resto', 'error');
                    return;
                }
                
                // Validar que la fecha no sea passée ni mayor a 7 días
                const hoy = new Date();
                hoy.setHours(0,0,0,0);
                const fechaSeleccionada = new Date(fechaCuota);
                const maxFecha = new Date(hoy);
                maxFecha.setDate(hoy.getDate() + 7);
                
                if (fechaSeleccionada < hoy) {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    mostrarToast('La fecha no puede ser passé', 'error');
                    return;
                }
                
                if (fechaSeleccionada > maxFecha) {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    mostrarToast('La fecha máxima para pagar el resto es 7 días', 'error');
                    return;
                }
                
                datosCliente = {
                    telefono: telefonoApartado,
                    observaciones: document.getElementById('notasApartado').value,
                    monto_adelanto: montoAdelanto,
                    fecha_cuota: fechaCuota,
                    total: totalGeneral
                };

                // NUEVO FLUJO: Mostrar modal de pago para confirmar primero
                document.getElementById('loadingOverlay').classList.remove('active');
                mostrarModalPagoReserva(datosCliente);
                return;
            }
            
            // Agregar método de comunicación para pedidos digitales
            let comunicacion = 'whatsapp';
            if (opcionSeleccionada === 'digital') {
                comunicacion = document.getElementById('metodoComunicacion').value;
            }

            // ── NUEVO FLUJO: notificaciones → modal de pago con comprobante ──
            // En lugar de crear el pedido en PENDIENTE, pedimos el comprobante
            // aquí mismo y lo creamos directamente en EN_VERIFICACION.
            if (opcionSeleccionada === 'digital' && comunicacion === 'notificaciones') {
                document.getElementById('loadingOverlay').classList.remove('active');
                _datosPedidoTemporal = { ...datosCliente, comunicacion };
                mostrarModalPagoPedido();
                return;
            }
            
            // Construir array de items para enviar al backend
            const itemsParaEnviar = datosCarrito.map(item => ({
                id: item.id,
                nombre: item.nombre,
                cantidad: item.cantidad,
                precio: item.precio,
                stock_actual: item.stock
            }));
            
            // Enviar solicitud al servidor con TODOS los datos necesarios
            const endpoint = opcionSeleccionada === 'digital'
                ? '<?php echo BASE_URL; ?>/api/process_order.php'
                : '<?php echo BASE_URL; ?>/api/process_reserva.php';

            const payload = opcionSeleccionada === 'digital'
                ? {
                    tipo: 'pedido_digital',
                    comunicacion: comunicacion,
                    datos_cliente: datosCliente,
                    subtotal: subtotalGeneral,
                    iva: ivaGeneral,
                    total: totalGeneral,
                    items: itemsParaEnviar
                }
                : {
                    telefono: datosCliente.telefono,
                    observaciones: datosCliente.observaciones,
                    monto_adelanto: datosCliente.monto_adelanto,
                    fecha_cuota: datosCliente.fecha_cuota,
                    subtotal: subtotalGeneral,
                    iva: ivaGeneral,
                    total: totalGeneral,
                    items: itemsParaEnviar
                };

            fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Respuesta no OK:', text);
                        throw new Error('Error del servidor: ' + response.status);
                    });
                }
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.indexOf('application/json') !== -1) {
                    return response.json();
                }
                return response.text().then(text => {
                    console.error('Respuesta no JSON:', text);
                    throw new Error('Respuesta inválida del servidor');
                });
            })
            .then(data => {
                document.getElementById('loadingOverlay').classList.remove('active');
                
                if (data.success) {
                    // Cerrar modal de selección
                    cerrarModal();

                    // Mostrar modal de confirmación con los datos correctos
                    mostrarConfirmacion(data, datosCliente);
                } else {
                    mostrarToast(data.message || 'Error al procesar la solicitud', 'error');
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                document.getElementById('loadingOverlay').classList.remove('active');
                mostrarToast('Error al procesar la solicitud: ' + error.message, 'error');
});
        }
        
        // Variables para datos temporales del pago
        let _datosApartadoTemporal = null;
        let _selectedMetodoPago = null;
        let _metodosPagoApartado = [];
        // Variables para pedido digital con comprobante
        let _datosPedidoTemporal   = null;
        let _selectedMetodoPagoPedido = null;

        function copyPaymentInfo(targetId) {
            const el = document.getElementById(targetId);
            if (!el) return;
            const texto = el.textContent.trim();
            if (!texto || texto === '-') {
                mostrarToast('No hay nada para copiar', 'warning');
                return;
            }
            navigator.clipboard?.writeText(texto).then(() => {
                mostrarToast('Copiado al portapapeles', 'success');
            }).catch(() => {
                mostrarToast('No se pudo copiar', 'error');
            });
        }

        const comprobantePagoInput = document.getElementById('comprobantePago');
        if (comprobantePagoInput) {
            comprobantePagoInput.addEventListener('change', function () {
                const label = document.getElementById('comprobantePagoFilename');
                if (!label) return;
                if (!this.files || this.files.length === 0) {
                    label.textContent = 'No se ha seleccionado ningún archivo';
                } else if (this.files.length === 1) {
                    label.textContent = this.files[0].name;
                } else {
                    label.textContent = `${this.files.length} archivos seleccionados`;
                }
            });
        }
        
        function mostrarModalPagoReserva(datosCliente) {
            _datosApartadoTemporal = datosCliente;
            _selectedMetodoPago = null;
            
            // Resetear campos
            document.getElementById('referenciaPago').value = '';
            document.getElementById('comprobantePago').value = '';
            const comprobanteLabel = document.getElementById('comprobantePagoFilename');
            if (comprobanteLabel) {
                comprobanteLabel.textContent = 'No se ha seleccionado ningún archivo';
            }
            document.getElementById('datosMetodoPago').style.display = 'none';
            document.getElementById('camposConfirmarPago').style.display = 'none';
            
            const btn = document.getElementById('btnConfirmarPago');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-check"></i> Selecciona un método de pago';
            btn.style.background = '#ccc';
            btn.style.cursor = 'not-allowed';
            
            // Cargar métodos de pago desde la base de datos
            fetch('<?php echo BASE_URL; ?>/api/get_metodos_pago_reservas.php')
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('metodoPagoSelect');
                select.innerHTML = '<option value="">Selecciona...</option>';
                
                if (data.ok && data.metodos && data.metodos.length > 0) {
                    data.metodos.forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.tipo + (m.banco ? ' - ' + m.banco : '');
                        opt.dataset.banco = m.banco || '';
                        opt.dataset.codigo = m.codigo_banco || '';
                        opt.dataset.cedula = m.cedula || '';
                        opt.dataset.telefono = m.telefono || '';
                        opt.dataset.cuenta = m.numero_cuenta || '';
                        opt.dataset.desc = m.banco ? `Banco: ${m.banco}\nCódigo: ${m.codigo_banco}\nCédula: ${m.cedula}\nTeléfono: ${m.telefono}\nCuenta: ${m.numero_cuenta}` : '';
                        select.appendChild(opt);
                    });
                } else {
                    // Fallback: solo Pago Móvil
                    select.innerHTML = '<option value="">Selecciona...</option><option value="pago_movil">Pago Móvil</option>';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                const select = document.getElementById('metodoPagoSelect');
                select.innerHTML = '<option value="">Selecciona...</option><option value="pago_movil">Pago Móvil</option>';
            });
            
            // Mostrar monto en USD y Bs
            const montoEnBs = datosCliente.monto_adelanto * TASA_CAMBIO;
            const montoMinimo = Math.round(totalGeneral * 0.25 * 100) / 100;
            document.getElementById('montoAPagar').textContent = '$ ' + datosCliente.monto_adelanto.toFixed(2) + ' (' + fmtBsLocal(datosCliente.monto_adelanto) + ')';
            
            // Mostrar modal
            document.getElementById('modalPagoReserva').classList.add('active');
        }
        
        function actualizarDatosMetodoPago() {
            const select = document.getElementById('metodoPagoSelect');
            const option = select.options[select.selectedIndex];
            
            if (!select.value) {
                document.getElementById('datosMetodoPago').style.display = 'none';
                document.getElementById('camposConfirmarPago').style.display = 'none';
                _selectedMetodoPago = null;
                
                const btn = document.getElementById('btnConfirmarPago');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-check"></i> Selecciona un método de pago';
                btn.style.background = '#ccc';
                return;
            }
            
            _selectedMetodoPago = {
                id: select.value,
                nombre: option.textContent,
                descripcion: option.dataset.desc || '',
                banco: option.dataset.banco || '',
                cedula: option.dataset.cedula || '',
                telefono: option.dataset.telefono || '',
                cuenta: option.dataset.cuenta || ''
            };
            
            document.getElementById('datosMetodoPago').style.display = 'block';
            document.getElementById('metodoNombre').textContent = option.textContent;
            document.getElementById('paymentBanco').textContent = _selectedMetodoPago.banco || '-';
            document.getElementById('paymentCedula').textContent = _selectedMetodoPago.cedula || '-';
            document.getElementById('paymentTelefono').textContent = _selectedMetodoPago.telefono || '-';
            document.getElementById('paymentCuenta').textContent = _selectedMetodoPago.cuenta || '-';
            document.getElementById('paymentCuentaRow').style.display = _selectedMetodoPago.cuenta ? 'flex' : 'none';
            document.getElementById('camposConfirmarPago').style.display = 'block';
            
            const btn = document.getElementById('btnConfirmarPago');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Confirmar Pago y Crear Reserva';
            btn.style.background = '#1F9166';
            btn.style.cursor = 'pointer';
        }
        
        function cerrarModalPagoReserva() {
            document.getElementById('modalPagoReserva').classList.remove('active');
        }
        
        async function confirmarPagoYReservar() {
    const referencia = document.getElementById('referenciaPago').value.trim();
    const comprobanteInput = document.getElementById('comprobantePago');
    const comprobanteFile = comprobanteInput.files[0]; // Solo el primer archivo
    const montoAdelanto = _datosApartadoTemporal.monto_adelanto;
    
    // Validar que haya referencia o comprobante
    if (!referencia && !comprobanteFile) {
        mostrarToast('Debes ingresar una referencia de pago o subir un comprobante', 'error');
        return;
    }
    
    // Mostrar loading
    document.getElementById('loadingOverlay').classList.add('active');
    
    // Construir items
    const itemsParaEnviar = datosCarrito.map(item => ({
        id: item.id,
        nombre: item.nombre,
        cantidad: item.cantidad,
        precio: item.precio,
        stock_actual: item.stock
    }));
    
    // Preparar FormData
    const formData = new FormData();
    formData.append('telefono', _datosApartadoTemporal.telefono);
    formData.append('observaciones', _datosApartadoTemporal.observaciones || '');
    formData.append('monto_adelanto', montoAdelanto);
    formData.append('fecha_cuota', _datosApartadoTemporal.fecha_cuota);
    formData.append('subtotal', subtotalGeneral);
    formData.append('iva', ivaGeneral);
    formData.append('total', _datosApartadoTemporal.total);
    formData.append('items', JSON.stringify(itemsParaEnviar));
    formData.append('referencia_pago', referencia);
    formData.append('estado_pago', 'PENDIENTE');
    
    // Agregar método de pago si existe
    if (_selectedMetodoPago) {
        formData.append('metodo_pago_id', _selectedMetodoPago.id);
        formData.append('metodo_pago_nombre', _selectedMetodoPago.nombre);
        formData.append('metodo_pago_descripcion', _selectedMetodoPago.descripcion);
    }
    
    // Agregar comprobante (solo uno, con nombre correcto)
    if (comprobanteFile) {
        // Validar tamaño (max 5MB)
        if (comprobanteFile.size > 5 * 1024 * 1024) {
            document.getElementById('loadingOverlay').classList.remove('active');
            mostrarToast('El comprobante no debe superar los 5MB', 'error');
            return;
        }
        
        // Validar tipo
        const tiposPermitidos = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!tiposPermitidos.includes(comprobanteFile.type)) {
            document.getElementById('loadingOverlay').classList.remove('active');
            mostrarToast('Solo se permiten imágenes JPG, PNG o WEBP', 'error');
            return;
        }
        
        formData.append('comprobante', comprobanteFile); // Nombre SINGULAR
    }
    
    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/process_reserva.php', {
            method: 'POST',
            body: formData  // No establecer Content-Type, el navegador lo hace automáticamente
        });
        
        const textResponse = await response.text();
        console.log('Respuesta raw:', textResponse);
        
        let data;
        try {
            data = JSON.parse(textResponse);
        } catch(e) {
            console.error('Error parseando JSON:', e);
            throw new Error('Respuesta inválida del servidor: ' + textResponse.substring(0, 200));
        }
        
        document.getElementById('loadingOverlay').classList.remove('active');
        
        if (data.success) {
            cerrarModalPagoReserva();
            cerrarModal();
            mostrarConfirmacion(data, _datosApartadoTemporal);
            _datosApartadoTemporal = null;
        } else {
            mostrarToast(data.message || 'Error al crear la reserva', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('loadingOverlay').classList.remove('active');
        mostrarToast('Error al procesar: ' + error.message, 'error');
    }
}
        
        function mostrarConfirmacion(data, datosCliente) {
            // Guardar para usar en finalizarCompra
            _pedidoData = data;

            const modalConfirm = document.getElementById('modalConfirmacion');
            const icon    = document.getElementById('confirmIcon');
            const title   = document.getElementById('confirmTitle');
            const code    = document.getElementById('confirmCode');
            const details = document.getElementById('confirmDetails');
            const info    = document.getElementById('confirmInfo');

            icon.removeAttribute('style');
            code.removeAttribute('style');

const subtotalUsd = Number(data.subtotal || subtotalGeneral);
            const ivaUsd = Number(data.iva || ivaGeneral);
            const totalUsd = Number(data.total || totalGeneral);
            const subtotalBs = subtotalUsd * TASA_CAMBIO;
            const ivaBs = ivaUsd * TASA_CAMBIO;
            const totalBs = totalUsd * TASA_CAMBIO;
            const montoAdelantoUsd = Number(data.monto_adelanto || 0);
            const montoAdelantoBs = montoAdelantoUsd * TASA_CAMBIO;
            const montoRestanteUsd = Number(data.monto_restante || 0);
            const montoRestanteBs = montoRestanteUsd * TASA_CAMBIO;
            
            const fmtBs = (n) => 'Bs ' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            
            let fechaLimite = data.fecha_limite_formateada || data.fecha_limite;
            if (!fechaLimite) {
                const f = new Date(); f.setDate(f.getDate() + 7);
                fechaLimite = f.toLocaleDateString('es-ES', { day:'2-digit', month:'2-digit', year:'numeric' });
            }
            
            if (data.tipo === 'pedido_digital') {
                icon.className   = 'fas fa-check-circle';
                icon.style.color = '#1F9166';
                title.textContent = '¡Pedido registrado!';
                code.textContent  = data.codigo;

                const enVerificacion = data.estado_pedido === 'EN_VERIFICACION';

                details.innerHTML = `
                    <div style="background:#f8f9fa;border-radius:8px;padding:15px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                            <span><strong>Subtotal:</strong></span>
                            <span>${fmtBs(subtotalBs)}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                            <span><strong>IVA (16%):</strong></span>
                            <span>${fmtBs(ivaBs)}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:1.15rem;margin-top:10px;padding-top:10px;border-top:2px dashed #1F9166;">
                            <span><strong>TOTAL:</strong></span>
                            <span><strong style="color:#1F9166;">${fmtBs(totalBs)}</strong></span>
                        </div>
                    </div>`;

                if (enVerificacion) {
                    // Flujo nuevo: comprobante ya enviado
                    info.innerHTML = `
                        <div style="margin-top:12px;">
                            <div style="background:#e8f6f1;border:2px solid #1F9166;border-radius:10px;padding:14px 16px;margin-bottom:12px;">
                                <p style="margin:0 0 6px;font-weight:700;color:#1F9166;font-size:14px;">
                                    <i class="fas fa-shield-alt"></i> Comprobante recibido
                                </p>
                                <p style="margin:0;font-size:13px;color:#444;line-height:1.5;">
                                    Tu pedido <strong>${data.codigo}</strong> está <strong>En Verificación</strong>.
                                    El vendedor revisará tu pago y te contactará para coordinar la entrega.
                                </p>
                            </div>
                            <div style="background:#fafafa;border:1px solid #e0e0e0;border-radius:8px;padding:11px 14px;font-size:12px;color:#666;display:flex;align-items:center;gap:10px;">
                                <i class="fas fa-clock" style="font-size:1.2rem;color:#f39c12;flex-shrink:0;"></i>
                                <span>Tiempo de verificación habitual: <strong>menos de 24 horas</strong>. Revisa el estado en <em>Mis Pedidos</em>.</span>
                            </div>
                        </div>`;
                } else {
                    // Flujo antiguo (otros canales): vendedor contacta al cliente
                    const canalInfo = {
                        whatsapp:       { icon:'fab fa-whatsapp',  color:'#25D366', label:'WhatsApp'        },
                        email:          { icon:'fas fa-envelope',  color:'#d93025', label:'Gmail / Email'   },
                        telegram:       { icon:'fab fa-telegram',  color:'#0088cc', label:'Telegram'        },
                        notificaciones: { icon:'fas fa-bell',       color:'#f39c12', label:'Notificación Web'},
                    };
                    const ch = canalInfo[data.comunicacion] || canalInfo.whatsapp;

                    info.innerHTML = `
                        <div style="margin-top:12px;">
                            <div style="background:#f0f9f4;border-left:4px solid #1F9166;border-radius:6px;padding:13px 15px;margin-bottom:12px;">
                                <p style="margin:0;font-size:0.88rem;color:#1F5C3A;">
                                    ✅ <strong>Pedido <code style="background:#d4edda;padding:1px 6px;border-radius:4px;">${data.codigo}</code> registrado correctamente.</strong><br>
                                    <span style="color:#555;">Un vendedor revisará tu pedido y te contactará para coordinar el pago y la entrega.</span>
                                </p>
                            </div>
                            <div style="background:#fafafa;border:1px solid #e0e0e0;border-radius:8px;padding:12px 15px;display:flex;align-items:center;gap:12px;">
                                <i class="${ch.icon}" style="font-size:1.8rem;color:${ch.color};flex-shrink:0;"></i>
                                <div>
                                    <p style="margin:0;font-size:0.85rem;font-weight:600;color:#333;">Notificación vía ${ch.label}</p>
                                    <p style="margin:3px 0 0;font-size:0.8rem;color:#777;">
                                        Al dar <strong>"Finalizar"</strong> se enviará automáticamente la notificación a la tienda.
                                    </p>
                                </div>
                            </div>
                        </div>`;
                }
            } else {
                icon.className   = 'fas fa-calendar-check';
                icon.style.color = '#f39c12';
                title.textContent = '¡Apartado creado!';
                code.textContent  = data.codigo;

                details.innerHTML = `
                    <div style="background:#f8f9fa;border-radius:8px;padding:15px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                            <span><strong>Subtotal:</strong></span>
                            <span>${fmtBs(subtotalBs)}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                            <span><strong>IVA (16%):</strong></span>
                            <span>${fmtBs(ivaBs)}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:1.1rem;margin:10px 0;padding:10px 0;border-top:2px dashed #f39c12;">
                            <span><strong>TOTAL:</strong></span>
                            <span><strong style="color:#f39c12;">${fmtBs(totalBs)}</strong></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-top:10px;background:#fff3cd;padding:8px;border-radius:6px;">
                            <span><strong>📤 Adelanto:</strong></span>
                            <span><strong style="color:#856404;">${fmtBs(montoAdelantoBs)}</strong></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-top:5px;background:#fce4ec;padding:8px;border-radius:6px;">
                            <span><strong>💰 Resta por pagar:</strong></span>
                            <span><strong style="color:#c62828;">${fmtBs(montoRestanteBs)}</strong></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-top:10px;">
                            <span><strong>⏰ Fecha límite:</strong></span>
                            <span><strong style="color:#f39c12;">${fechaLimite}</strong></span>
                        </div>
                    </div>`;

                const metodoNombre = data.metodo_pago || '';
                const metodoDescripcion = data.metodo_pago_descripcion || '';
                const refPago = data.referencia_pago || 'N/A';

                let metodoHtml = '';
                if (metodoNombre) {
                    metodoHtml = `
                        <div style="background:#fff3cd;border:2px solid #ffc107;border-radius:8px;padding:15px;margin-bottom:15px;">
                            <p style="margin:0 0 10px;font-weight:bold;color:#856404;"><i class="fas fa-credit-card"></i> MÉTODO DE PAGO SELECCIONADO</p>
                            <p style="margin:0 0 8px;font-size:14px;font-weight:700;color:#1F9166;">${metodoNombre}</p>
                            <div style="font-size:13px;color:#444;white-space:pre-wrap;line-height:1.5;">${metodoDescripcion || 'Sin detalles adicionales.'}</div>
                        </div>`;
                }

                info.innerHTML = `
                    <div style="margin-top:10px;">
                        ${metodoHtml}
                        <div style="background:#e8f5e9;border:2px solid #1F9166;border-radius:8px;padding:15px;margin-bottom:15px;">
                            <p style="margin:0 0 10px;font-weight:bold;color:#1F9166;"><i class="fas fa-check-circle"></i> RESERVA CREADA</p>
                            <p style="margin:0;font-size:13px;line-height:1.5;">Tu reservado ha sido procesada. Presenta el código <strong>${data.codigo}</strong> en nuestra tienda para retirar tus productos.</p>
                        </div>
                        <p><strong>⏰ Fecha límite:</strong> antes del <strong>${fechaLimite}</strong></p>
                        <p style="margin-top:10px;"><strong>📍 Dirección:</strong><br>
                        AV ARAGUA LOCAL NRO 286, SECTOR ANDRES ELOY BLANCO, MARACAY</p>
                    </div>`;
            }

            modalConfirm.classList.add('active');
        }

        async function finalizarCompra() {
            const btn = document.querySelector('#modalConfirmacion .btn-checkout');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizando...'; }

            if (_pedidoData && _pedidoData.tipo === 'pedido_digital') {
                const canal     = _pedidoData.comunicacion || 'whatsapp';
                const pedidoId  = _pedidoData.pedido_id;

                try {
                    if (canal === 'whatsapp') {
                        // ── WhatsApp: abrir wa.me directo al chat de la tienda
                        // El mensaje ya viene armado — el cliente solo da Send
                        if (_pedidoData.whatsapp_url) {
                            window.open(_pedidoData.whatsapp_url, '_blank', 'noopener,noreferrer');
                        }
                        // Pausa para que la pestaña se abra antes de redirigir
                        await new Promise(r => setTimeout(r, 500));

                    } else if (canal === 'telegram' || canal === 'email') {
                        // ── Telegram / Email: el servidor envía al grupo/correo directamente
                        // No molesta al cliente con ninguna ventana
                        await fetch('<?php echo BASE_URL; ?>/api/send_notificacion_pedido.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ pedido_id: pedidoId, canal: canal })
                        });
                        // No esperamos respuesta — si falla, el pedido ya quedó en BD

                    }
                    // ── Notificaciones internas: ya creadas en el servidor, nada que hacer aquí

                } catch(e) {
                    console.warn('[finalizarCompra] Error al notificar:', e);
                    // Nunca bloquear la redirección
                }
            }

            document.getElementById('modalConfirmacion').classList.remove('active');
            _pedidoData = null;
            window.location.href = '<?php echo BASE_URL; ?>/app/views/layouts/inicio.php';
        }
        
// Función para mostrar toasts - usar sistema inv-notifications.js
        function mostrarToast(mensaje, tipo = 'success') {
            // Usar Toast del sistema (15 segundos)
            if (typeof Toast !== 'undefined') {
                Toast[tipo](mensaje, '', 15000);
            } else {
                // Fallback manual
                const toast = document.createElement('div');
                toast.className = 'inv-toast ' + tipo;
                toast.innerHTML = `
                    <div class="inv-toast-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="inv-toast-body">
                        <div class="inv-toast-title">${tipo === 'success' ? 'Éxito' : 'Error'}</div>
                        <div class="inv-toast-message">${mensaje}</div>
</div>
                `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 15000);
            }
        }

        // ══════════════════════════════════════════════════════════════
        //  MODAL DE PAGO PARA PEDIDO DIGITAL (canal: notificaciones)
        //  Espejo del modalPagoReserva pero orientado a pedidos.
        // ══════════════════════════════════════════════════════════════

        function mostrarModalPagoPedido() {
            _selectedMetodoPagoPedido = null;

            // Reset campos
            const refEl = document.getElementById('pedidoReferenciaPago');
            const compEl = document.getElementById('pedidoComprobantePago');
            const labelEl = document.getElementById('pedidoComprobantePagoFilename');
            const previewEl = document.getElementById('pedidoComprobantePreview');

            if (refEl) refEl.value = '';
            if (compEl) compEl.value = '';
            if (labelEl) labelEl.textContent = 'No se ha seleccionado ningún archivo';
            if (previewEl) previewEl.style.display = 'none';

            document.getElementById('pedidoDatosMetodoPago').style.display = 'none';
            document.getElementById('pedidoCamposConfirmarPago').style.display = 'none';

            const btn = document.getElementById('btnConfirmarPagoPedido');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-check"></i> Selecciona un método de pago';
            btn.style.background = '#ccc';
            btn.style.cursor = 'not-allowed';

            // Mostrar total
            const totalBs = totalGeneral * TASA_CAMBIO;
            document.getElementById('pedidoMontoAPagar').textContent =
                'Bs ' + totalBs.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.') +
                ' ($' + totalGeneral.toFixed(2) + ')';

            // Cargar métodos de pago (mismo endpoint que reservas)
            const sel = document.getElementById('pedidoMetodoPagoSelect');
            sel.innerHTML = '<option value="">Cargando métodos...</option>';

            fetch('<?php echo BASE_URL; ?>/api/get_metodos_pago_reservas.php')
                .then(r => r.json())
                .then(data => {
                    sel.innerHTML = '<option value="">Selecciona un método...</option>';
                    if (data.ok && data.metodos && data.metodos.length > 0) {
                        data.metodos.forEach(m => {
                            const opt = document.createElement('option');
                            opt.value = m.id;
                            opt.textContent = m.tipo + (m.banco ? ' - ' + m.banco : '');
                            opt.dataset.banco    = m.banco || '';
                            opt.dataset.cedula   = m.cedula || '';
                            opt.dataset.telefono = m.telefono || '';
                            opt.dataset.cuenta   = m.numero_cuenta || '';
                            opt.dataset.nombre   = m.tipo + (m.banco ? ' - ' + m.banco : '');
                            sel.appendChild(opt);
                        });
                    } else {
                        sel.innerHTML = '<option value="">— Sin métodos disponibles —</option>';
                    }
                })
                .catch(() => {
                    sel.innerHTML = '<option value="">Error al cargar métodos</option>';
                });

            // Listener para preview de imagen
            const compInput = document.getElementById('pedidoComprobantePago');
            compInput.onchange = function () {
                const label = document.getElementById('pedidoComprobantePagoFilename');
                const preview = document.getElementById('pedidoComprobantePreview');
                const img = document.getElementById('pedidoComprobanteImg');
                if (this.files && this.files[0]) {
                    label.textContent = this.files[0].name;
                    const reader = new FileReader();
                    reader.onload = e => {
                        img.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    label.textContent = 'No se ha seleccionado ningún archivo';
                    preview.style.display = 'none';
                }
            };

            document.getElementById('modalPagoPedido').classList.add('active');
        }

        function actualizarDatosMetodoPagoPedido() {
            const sel = document.getElementById('pedidoMetodoPagoSelect');
            const opt = sel.options[sel.selectedIndex];

            if (!sel.value) {
                document.getElementById('pedidoDatosMetodoPago').style.display = 'none';
                document.getElementById('pedidoCamposConfirmarPago').style.display = 'none';
                _selectedMetodoPagoPedido = null;
                const btn = document.getElementById('btnConfirmarPagoPedido');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-check"></i> Selecciona un método de pago';
                btn.style.background = '#ccc';
                btn.style.cursor = 'not-allowed';
                return;
            }

            _selectedMetodoPagoPedido = {
                id:       sel.value,
                nombre:   opt.dataset.nombre || opt.textContent,
                banco:    opt.dataset.banco    || '',
                cedula:   opt.dataset.cedula   || '',
                telefono: opt.dataset.telefono || '',
                cuenta:   opt.dataset.cuenta   || '',
            };

            document.getElementById('pedidoDatosMetodoPago').style.display = 'block';
            document.getElementById('pedidoMetodoNombre').textContent = _selectedMetodoPagoPedido.nombre;
            document.getElementById('pedidoPaymentBanco').textContent    = _selectedMetodoPagoPedido.banco    || '-';
            document.getElementById('pedidoPaymentCedula').textContent   = _selectedMetodoPagoPedido.cedula   || '-';
            document.getElementById('pedidoPaymentTelefono').textContent = _selectedMetodoPagoPedido.telefono || '-';
            document.getElementById('pedidoPaymentCuenta').textContent   = _selectedMetodoPagoPedido.cuenta   || '-';
            document.getElementById('pedidoPaymentCuentaRow').style.display = _selectedMetodoPagoPedido.cuenta ? 'flex' : 'none';
            document.getElementById('pedidoCamposConfirmarPago').style.display = 'block';

            const btn = document.getElementById('btnConfirmarPagoPedido');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar pedido con comprobante';
            btn.style.background = '#1F9166';
            btn.style.cursor = 'pointer';
        }

        function cerrarModalPagoPedido() {
            document.getElementById('modalPagoPedido').classList.remove('active');
        }

        async function confirmarPagoYPedido() {
            const referencia    = document.getElementById('pedidoReferenciaPago').value.trim();
            const comprobanteInput = document.getElementById('pedidoComprobantePago');
            const comprobanteFile  = comprobanteInput.files[0];

            if (!referencia && !comprobanteFile) {
                mostrarToast('Debes ingresar una referencia o subir un comprobante', 'error');
                return;
            }

            // Validar tamaño del comprobante (max 5 MB)
            if (comprobanteFile && comprobanteFile.size > 5 * 1024 * 1024) {
                mostrarToast('El comprobante no debe superar los 5MB', 'error');
                return;
            }

            document.getElementById('loadingOverlay').classList.add('active');

            // Construir items
            const itemsParaEnviar = datosCarrito.map(item => ({
                id:           item.id,
                nombre:       item.nombre,
                cantidad:     item.cantidad,
                precio:       item.precio,
                stock_actual: item.stock
            }));

            const fd = new FormData();
            fd.append('tipo',           'pedido_digital');
            fd.append('comunicacion',   'notificaciones');
            fd.append('tipo_entrega',   _datosPedidoTemporal.tipo_entrega  || 'tienda');
            fd.append('direccion',      _datosPedidoTemporal.direccion     || '');
            fd.append('telefono',       _datosPedidoTemporal.telefono      || '');
            fd.append('observaciones',  _datosPedidoTemporal.observaciones || '');
            fd.append('subtotal',       subtotalGeneral);
            fd.append('iva',            ivaGeneral);
            fd.append('total',          totalGeneral);
            fd.append('items',          JSON.stringify(itemsParaEnviar));
            fd.append('referencia_pago', referencia);

            if (_selectedMetodoPagoPedido) {
                fd.append('metodo_pago_id',     _selectedMetodoPagoPedido.id);
                fd.append('metodo_pago_nombre', _selectedMetodoPagoPedido.nombre);
            }

            if (comprobanteFile) {
                fd.append('comprobante', comprobanteFile);
            }

            try {
                const res  = await fetch('<?php echo BASE_URL; ?>/api/process_pedido.php', {
                    method: 'POST',
                    body:   fd   // multipart automático, NO poner Content-Type
                });
                const text = await res.text();
                let data;
                try { data = JSON.parse(text); }
                catch(e) { throw new Error('Respuesta inválida: ' + text.substring(0, 200)); }

                document.getElementById('loadingOverlay').classList.remove('active');

                if (data.success) {
                    cerrarModalPagoPedido();
                    cerrarModal();
                    mostrarConfirmacion(data, _datosPedidoTemporal);
                    _datosPedidoTemporal = null;
                } else {
                    mostrarToast(data.message || 'Error al procesar el pedido', 'error');
                }
            } catch (err) {
                console.error('confirmarPagoYPedido:', err);
                document.getElementById('loadingOverlay').classList.remove('active');
                mostrarToast('Error al procesar: ' + err.message, 'error');
            }
        }
    </script>
    <!-- ═══════════════════════════════════════════════════════════
         MODAL DE PAGO PARA PEDIDO DIGITAL (canal notificaciones)
         El cliente sube su comprobante y el pedido queda EN_VERIFICACION
         ═══════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modalPagoPedido">
        <div class="modal-container" style="max-width:520px;">
            <div class="modal-header" style="background:#1F9166;border-radius:15px 15px 0 0;padding:20px 25px;">
                <h2 style="color:white;margin:0;font-size:1.3rem;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-shield-alt"></i> Confirmar Pago del Pedido
                </h2>
                <button class="modal-close" onclick="cerrarModalPagoPedido()"
                        style="color:white;background:rgba(255,255,255,.2);">&times;</button>
            </div>

            <div class="modal-body" style="padding:22px;">

                <!-- Resumen del total a pagar -->
                <div style="background:#f0f9f4;border-left:4px solid #1F9166;border-radius:8px;padding:13px 16px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <p style="margin:0;font-size:12px;color:#555;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Total a pagar</p>
                        <p style="margin:3px 0 0;font-size:1.3rem;font-weight:800;color:#1F9166;" id="pedidoMontoAPagar">Bs 0.00</p>
                    </div>
                    <i class="fas fa-receipt" style="font-size:2rem;color:#c3e6cb;"></i>
                </div>

                <!-- 1. Selección de método de pago -->
                <div style="margin-bottom:16px;">
                    <label style="display:block;margin-bottom:7px;font-weight:700;color:#333;font-size:13px;">
                        <i class="fas fa-credit-card" style="color:#1F9166;margin-right:5px;"></i>
                        Método de Pago <span style="color:#e74c3c;">*</span>
                    </label>
                    <select id="pedidoMetodoPagoSelect" onchange="actualizarDatosMetodoPagoPedido()"
                            style="width:100%;padding:10px;border:1.5px solid #ddd;border-radius:8px;font-size:14px;cursor:pointer;">
                        <option value="">Selecciona un método...</option>
                    </select>
                </div>

                <!-- 2. Datos del método seleccionado -->
                <div id="pedidoDatosMetodoPago" style="display:none;background:#fff3cd;border:2px solid #ffc107;border-radius:10px;padding:15px;margin-bottom:16px;">
                    <p style="margin:0 0 10px;font-weight:700;font-size:13px;color:#856404;">
                        <i class="fas fa-university"></i> <span id="pedidoMetodoNombre">—</span>
                    </p>
                    <div class="payment-info-grid">
                        <div class="payment-info-row">
                            <span>Banco</span>
                            <div class="payment-info-value">
                                <span id="pedidoPaymentBanco">-</span>
                                <button type="button" class="copy-btn" onclick="copyPaymentInfo('pedidoPaymentBanco')">Copiar</button>
                            </div>
                        </div>
                        <div class="payment-info-row">
                            <span>Cédula</span>
                            <div class="payment-info-value">
                                <span id="pedidoPaymentCedula">-</span>
                                <button type="button" class="copy-btn" onclick="copyPaymentInfo('pedidoPaymentCedula')">Copiar</button>
                            </div>
                        </div>
                        <div class="payment-info-row">
                            <span>Teléfono</span>
                            <div class="payment-info-value">
                                <span id="pedidoPaymentTelefono">-</span>
                                <button type="button" class="copy-btn" onclick="copyPaymentInfo('pedidoPaymentTelefono')">Copiar</button>
                            </div>
                        </div>
                        <div class="payment-info-row" id="pedidoPaymentCuentaRow" style="display:none;">
                            <span>Cuenta</span>
                            <div class="payment-info-value">
                                <span id="pedidoPaymentCuenta">-</span>
                                <button type="button" class="copy-btn" onclick="copyPaymentInfo('pedidoPaymentCuenta')">Copiar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Referencia y comprobante -->
                <div id="pedidoCamposConfirmarPago" style="display:none;">
                    <div class="form-group" style="margin-bottom:13px;">
                        <label style="font-weight:700;font-size:13px;color:#333;margin-bottom:6px;display:block;">
                            <i class="fas fa-hashtag" style="color:#1F9166;"></i> Número de Referencia <span style="color:#e74c3c;">*</span>
                        </label>
                        <input type="text" id="pedidoReferenciaPago" placeholder="Ej: 0123456789"
                               style="width:100%;padding:10px;border:1.5px solid #ddd;border-radius:8px;font-size:14px;box-sizing:border-box;">
                    </div>

                    <div class="form-group" style="margin-bottom:13px;">
                        <label style="font-weight:700;font-size:13px;color:#333;margin-bottom:8px;display:block;">
                            <i class="fas fa-camera" style="color:#1F9166;"></i> Foto del Comprobante
                            <span style="font-weight:400;color:#888;font-size:11px;"> (recomendado)</span>
                        </label>
                        <div class="file-upload-wrapper">
                            <label class="file-upload-button" for="pedidoComprobantePago">
                                <i class="fas fa-upload"></i> Seleccionar imagen
                            </label>
                            <span class="file-upload-filename" id="pedidoComprobantePagoFilename">
                                No se ha seleccionado ningún archivo
                            </span>
                            <input type="file" id="pedidoComprobantePago" name="pedidoComprobantePago"
                                   accept="image/*" style="display:none;">
                        </div>
                        <div id="pedidoComprobantePreview" style="display:none;margin-top:10px;">
                            <img id="pedidoComprobanteImg" src="" alt="Vista previa"
                                 style="max-width:100%;max-height:150px;border-radius:8px;border:2px solid #1F9166;object-fit:cover;">
                        </div>
                    </div>
                </div>

                <p style="font-size:11px;color:#999;margin-top:4px;text-align:center;">
                    Tu pedido quedará en estado <strong>"En Verificación"</strong>. El vendedor lo revisará y confirmará a la brevedad.
                </p>
            </div>

            <div class="modal-footer" style="padding:16px 22px;border-top:1px solid #eee;">
                <button class="btn-continue" onclick="cerrarModalPagoPedido()"
                        style="width:auto;padding:10px 22px;font-size:14px;">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
                <button id="btnConfirmarPagoPedido" onclick="confirmarPagoYPedido()"
                        disabled
                        style="width:auto;padding:12px 24px;background:#ccc;color:white;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:not-allowed;display:inline-flex;align-items:center;gap:8px;transition:all .2s;">
                    <i class="fas fa-check"></i> Selecciona un método de pago
                </button>
            </div>
        </div>
    </div>

</body>
</html>