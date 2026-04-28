<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../helpers/promocion_helper.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';

$producto = [];
$imagenes = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $producto_id = (int)$_GET['id'];
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Primero verificar si el producto existe sin condición de estado
        $stmt_check = $conn->prepare("SELECT id, estado FROM productos WHERE id = :id");
        $stmt_check->execute([':id' => $producto_id]);
        $producto_check = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto_check) {
            error_log("Producto con ID $producto_id no existe en la tabla productos");
        } elseif ($producto_check['estado'] == false) {
            error_log("Producto con ID $producto_id existe pero estado = false");
        }
        
        // Obtener información del producto
        $sql = "SELECT p.* FROM productos p WHERE p.id = :id";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([':id' => $producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            error_log("No se encontró producto con ID: $producto_id");
        } else {
            // Normalizar stock INMEDIATAMENTE después de obtener el producto
            $stock_raw = $producto['stock_actual'] ?? $producto['stock'] ?? null;
            $producto['stock_actual'] = is_numeric($stock_raw) ? intval($stock_raw) : 0;
            
            if ($producto['stock_actual'] <= 0) {
                error_log("Producto ID $producto_id tiene stock = " . var_export($stock_raw, true) . ", normalizado a: " . $producto['stock_actual']);
            }
        }
        
        // Si se encontró el producto, buscar promoción activa por separado
        if ($producto) {
            // Obtener promoción activa directamente
            $promoSql = "SELECT pr.tipo_promocion, pr.valor, pr.nombre
                        FROM producto_promociones pp
                        INNER JOIN promociones pr ON pr.id = pp.promocion_id
                        WHERE pp.producto_id = :id 
                        AND pr.estado = true
                        AND pr.fecha_inicio <= CURRENT_DATE
                        AND pr.fecha_fin >= CURRENT_DATE
                        ORDER BY pr.fecha_fin ASC
                        LIMIT 1";
            $promoStmt = $conn->prepare($promoSql);
            $promoStmt->execute([':id' => $producto_id]);
            $promo = $promoStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($promo) {
                $producto['promo_tipo_promocion'] = $promo['tipo_promocion'];
                $producto['promo_valor'] = $promo['valor'];
                $producto['promo_nombre'] = $promo['nombre'];
            }
        }
        
        // Si se encontró el producto, obtener marca, categoria y datos específicos
        if ($producto) {
            if (!empty($producto['marca_id'])) {
                $stmt_marca = $conn->prepare("SELECT nombre FROM marcas WHERE id = :id");
                $stmt_marca->execute([':id' => $producto['marca_id']]);
                $marca = $stmt_marca->fetch(PDO::FETCH_ASSOC);
                $producto['marca'] = $marca ? $marca['nombre'] : null;
            }
            
            if (!empty($producto['categoria_id'])) {
                $stmt_cat = $conn->prepare("SELECT nombre FROM categorias WHERE id = :id");
                $stmt_cat->execute([':id' => $producto['categoria_id']]);
                $categoria = $stmt_cat->fetch(PDO::FETCH_ASSOC);
                $producto['categoria'] = $categoria ? $categoria['nombre'] : null;
            }
            
            // Obtener datos específicos según el tipo de producto
            // Intentar obtener como vehículo
            $stmt_vehiculo = $conn->prepare("SELECT * FROM vehiculos WHERE producto_id = :id");
            $stmt_vehiculo->execute([':id' => $producto_id]);
            $vehiculo = $stmt_vehiculo->fetch(PDO::FETCH_ASSOC);
            if ($vehiculo) {
                $producto['marca'] = $vehiculo['marca'];
                $producto['modelo'] = $vehiculo['modelo'];
                $producto['anio'] = $vehiculo['anio'];
                $producto['cilindrada'] = $vehiculo['cilindrada'];
                $producto['color'] = $vehiculo['color'];
                $producto['kilometraje'] = $vehiculo['kilometraje'];
                $producto['tipo_vehiculo'] = $vehiculo['tipo_vehiculo'];
            }
            
            // Intentar obtener como repuesto
            $stmt_repuesto = $conn->prepare("SELECT * FROM repuestos WHERE producto_id = :id");
            $stmt_repuesto->execute([':id' => $producto_id]);
            $repuesto = $stmt_repuesto->fetch(PDO::FETCH_ASSOC);
            if ($repuesto) {
                $producto['categoria_tecnica'] = $repuesto['categoria_tecnica'];
                $producto['marca_compatible'] = $repuesto['marca_compatible'];
                $producto['modelo_compatible'] = $repuesto['modelo_compatible'];
                $producto['anio_compatible'] = $repuesto['anio_compatible'];
            }
            
            // Intentar obtener como accesorio
            $stmt_accesorio = $conn->prepare("SELECT * FROM accesorios WHERE producto_id = :id");
            $stmt_accesorio->execute([':id' => $producto_id]);
            $accesorio = $stmt_accesorio->fetch(PDO::FETCH_ASSOC);
            if ($accesorio) {
                $producto['subtipo_accesorio'] = $accesorio['subtipo_accesorio'];
                $producto['talla'] = $accesorio['talla'];
                $producto['color_accesorio'] = $accesorio['color'];
                $producto['material'] = $accesorio['material'];
                $producto['marca_accesorio'] = $accesorio['marca'];
                $producto['certificacion'] = $accesorio['certificacion'];
            }
        }
        
        if ($producto) {
            // Aplicar promoción si existe
            $producto = aplicarPromocionAProducto($producto);
        }

        // El stock ya fue normalizado inmediatamente después de obtener el producto
        // Ahora calcular precios
        if ($producto) {
            $precioBaseUsd = 0.0;
            // FIX: !empty() falla con "0" y 0; usar isset() + floatval() > 0
            $pv     = isset($producto['precio_venta'])     ? floatval($producto['precio_venta'])     : 0.0;
            $pv_usd = isset($producto['precio_venta_usd']) ? floatval($producto['precio_venta_usd']) : 0.0;
            $pv_bs  = isset($producto['precio_venta_bs'])  ? floatval($producto['precio_venta_bs'])  : 0.0;

            if ($pv > 0) {
                $precioBaseUsd = $pv;
            } elseif ($pv_usd > 0) {
                $precioBaseUsd = $pv_usd;
            } elseif (isset($producto['moneda_base']) && strtoupper($producto['moneda_base']) === 'BS' && $pv_bs > 0) {
                $precioBaseUsd = $pv_bs / max(1, getTasaCambio());
            } else {
                $precioBaseUsd = $pv; // 0 si ninguno aplica
            }

            $precioRealUsd = isset($producto['precio_real']) && floatval($producto['precio_real']) > 0
                ? floatval($producto['precio_real'])
                : $precioBaseUsd;
            if ($precioRealUsd <= 0 && $precioBaseUsd > 0) {
                $precioRealUsd = $precioBaseUsd;
            }

            $producto['precio_base_usd'] = $precioBaseUsd;
            $producto['precio_real_usd'] = $precioRealUsd;
        }
        
        // Obtener imágenes del producto
        if ($producto) {
            $sqlImagenes = "SELECT * FROM producto_imagenes WHERE producto_id = :id ORDER BY es_principal DESC";
            $stmtImagenes = $conn->prepare($sqlImagenes);
            $stmtImagenes->execute([':id' => $producto_id]);
            $imagenes = $stmtImagenes->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log('ERROR product_detail.php: ' . $e->getMessage());
    }
}

// Verificar si el usuario está logueado
$usuario_logueado = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $producto ? htmlspecialchars($producto['nombre']) : 'Producto no encontrado'; ?> - Inversiones Rojas</title>
    <script>var TASA_CAMBIO = <?php echo getTasaCambio(); ?>;</script>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/components/user-panel.css">
    <style>
        /* Estilos específicos para detalle de producto */
        .product-detail-page {
            padding: 40px 0;
            min-height: 70vh;
        }
        
        .product-not-found {
            text-align: center;
            padding: 60px 20px;
        }
        
        .product-not-found h1 {
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: #1F9166;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 20px;
        }
        
        .back-btn:hover {
            background: #187a54;
        }
        
        .product-detail-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .product-breadcrumb {
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .product-breadcrumb a {
            color: #1F9166;
            text-decoration: none;
        }
        
        .product-detail-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .product-detail-content {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 20px;
            }
        }
        
        /* Galería de imágenes */
        .product-gallery {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .main-image {
            width: 100%;
            height: 400px;
            object-fit: contain;
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
        }
        
        .thumbnail-images {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.3s;
        }
        
        .thumbnail.active,
        .thumbnail:hover {
            border-color: #1F9166;
        }
        
        /* Upload section */
        .image-upload-section {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            text-align: center;
        }
        
        .upload-status {
            padding: 12px;
            border-radius: 6px;
            font-weight: 500;
            text-align: center;
        }
        
        .upload-status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .upload-status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .upload-status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Información del producto */
        .product-info {
            display: flex;
            flex-direction: column;
        }
        
        .product-category {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .product-title {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 15px;
            line-height: 1.2;
        }
        
        .product-price {
            color: #e74c3c;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 20px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: baseline;
        }
        
        .product-price .price-segment {
            white-space: nowrap;
            display: inline-flex;
            align-items: baseline;
            gap: 0.25rem;
        }
        
        .product-specs {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .spec-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .spec-item:last-child {
            border-bottom: none;
        }
        
        .spec-label {
            color: #666;
        }
        
        .spec-value {
            color: #333;
            font-weight: 500;
        }
        
        .product-description {
            color: #555;
            line-height: 1.6;
            margin: 20px 0;
        }
        
        .product-stock {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            font-size: 16px;
        }
        
        .in-stock {
            color: #27ae60;
            font-weight: bold;
        }
        
        .out-of-stock {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .product-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-add-cart {
            flex: 1;
            padding: 15px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-add-cart:hover {
            background: #27ae60;
        }
        
        .btn-add-cart:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php

    $_producto_detail = $producto;
    $__imagenes_detail = $imagenes;
    require __DIR__ . '/partials/header.php';
    $producto  = $_producto_detail;
    $imagenes  = $__imagenes_detail;
    unset($_producto_detail, $__imagenes_detail);
    ?>

    <main class="product-detail-page container">
        <?php if (!$producto): ?>
            <div class="product-not-found">
                <h1>Producto no encontrado</h1>
                <p>El producto que buscas (ID: <?php echo htmlspecialchars($_GET['id'] ?? 'no especificado'); ?>) no está disponible o ha sido eliminado.</p>
                <a href="<?php echo BASE_URL; ?>/app/views/pages/motos.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Volver al catálogo
                </a>
            </div>
        <?php else: ?>
            <script>
                window.USER_LOGGED_IN = <?php echo $usuario_logueado ? 'true' : 'false'; ?>;
            </script>
            <div class="product-detail-container">
             
                
                <!-- Contenido principal -->
                <div class="product-detail-content">
                    <!-- Galería de imágenes -->
                    <div class="product-gallery">
                        <?php if (!empty($imagenes)): ?>
                            <img id="mainImage" src="<?php echo htmlspecialchars($imagenes[0]['imagen_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                 class="main-image">
                        <?php else: ?>
                            <div id="mainImage" class="main-image no-image" style="background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#666; font-size:18px; height:400px;">Sin imagen</div>
                        <?php endif; ?>
                        
                        <?php if (!empty($imagenes)): ?>
                            <div class="thumbnail-images">
                                <?php foreach ($imagenes as $index => $imagen): ?>
                                    <img src="<?php echo htmlspecialchars($imagen['imagen_url']); ?>" 
                                         alt="Vista <?php echo $index + 1; ?>" 
                                         class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                         onclick="cambiarImagen('<?php echo htmlspecialchars($imagen['imagen_url']); ?>', this)">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($usuario_logueado): ?>
                        
                        <?php endif; ?>
                    </div>
                    
                    <!-- Información del producto -->
                    <div class="product-info">
                        <div class="product-category">
                            <?php echo htmlspecialchars($producto['categoria'] ?? 'Moto'); ?>
                        </div>
                        <h1 class="product-title"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
                        
                        <?php 
                        $precioOriginal = floatval($producto['precio_base_usd'] ?? floatval($producto['precio_venta'] ?? 0));
                        $precioMostrar = floatval($producto['precio_real_usd'] ?? $precioOriginal);
                        $descuento = 0;
                        if ($precioOriginal > 0 && $precioMostrar < $precioOriginal) {
                            $descuento = round((($precioOriginal - $precioMostrar) / $precioOriginal) * 100);
                        }
                        
                        // Mostrar precio con soporte para moneda dual
                        $precios = formatearMonedaDual($precioMostrar);
                        $preciosOriginal = formatearMonedaDual($precioOriginal);
                        ?>
                        
                        <div class="product-price">
                            <?php if ($descuento > 0): ?>
                                <span class="promo-badge-mini" style="display:inline-block; background:#e74c3c; color:white; padding:3px 8px; border-radius:12px; font-weight:700; font-size:0.75em; margin-right:5px;">-<?php echo $descuento; ?>%</span>
                                <span class="price-segment" style="text-decoration:line-through;color:#999;font-size:0.85em;"><?php echo htmlspecialchars($preciosOriginal['bs']); ?></span>
                                <span class="price-segment" style="color:#e74c3c;font-weight:700;font-size:1.3em;"><?php echo htmlspecialchars($precios['bs']); ?></span>
                                <span class="moneda-usd"><?php echo htmlspecialchars($precios['usd']); ?></span>
                            <?php else: ?>
                                <span class="price-segment"><?php echo htmlspecialchars($precios['bs']); ?></span>
                                <span class="moneda-usd"><?php echo htmlspecialchars($precios['usd']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($descuento > 0 && !empty($producto['promo_nombre'])): ?>
                            <div style="font-size:0.85rem; color:#1F9166; margin-top:5px;">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($producto['promo_nombre']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Especificaciones -->
                        <div class="product-specs">
                            <?php if (!empty($producto['categoria'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Categoría:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['categoria']); ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if (isset($producto['marca']) && !empty($producto['marca'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Marca:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['marca']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['modelo']) && !empty($producto['modelo'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Modelo:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['modelo']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['anio']) && !empty($producto['anio'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Año:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['anio']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['cilindrada']) && !empty($producto['cilindrada'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Cilindrada:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['cilindrada']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['color']) && !empty($producto['color'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Color:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['color']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['kilometraje'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Kilometraje:</span>
                                <span class="spec-value"><?php echo number_format((int)($producto['kilometraje'] ?? 0)); ?> km</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['categoria_tecnica']) && !empty($producto['categoria_tecnica'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Categoría Técnica:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['categoria_tecnica']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['marca_compatible']) && !empty($producto['marca_compatible'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Compatible con Marca:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['marca_compatible']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['modelo_compatible']) && !empty($producto['modelo_compatible'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Compatible con Modelo:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['modelo_compatible']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['anio_compatible']) && !empty($producto['anio_compatible'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Compatible con Año:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['anio_compatible']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['subtipo_accesorio']) && !empty($producto['subtipo_accesorio'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Tipo de Accesorio:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['subtipo_accesorio']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['talla']) && !empty($producto['talla'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Talla:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['talla']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['material']) && !empty($producto['material'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Material:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['material']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($producto['certificacion']) && !empty($producto['certificacion'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Certificación:</span>
                                <span class="spec-value"><?php echo htmlspecialchars($producto['certificacion']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Descripción -->
                        <div class="product-description">
                            <h3>Descripción</h3>
                            <p><?php echo nl2br(htmlspecialchars($producto['descripcion'] ?? 'Sin descripción disponible.')); ?></p>
                        </div>
                        
                        <!-- Stock -->
                        <div class="product-stock" id="stockDisplay">
                            <?php if (($producto['stock_actual'] ?? 0) > 0): ?>
                                <span class="in-stock">
                                    <i class="fas fa-check-circle"></i> 
                                    <?php echo $producto['stock_actual']; ?> unidades disponibles
                                </span>
                            <?php else: ?>
                                <span class="out-of-stock">
                                    <i class="fas fa-times-circle"></i> 
                                    Agotado
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Acciones -->
                        <div class="product-actions">
                            <?php
                            $productNameJs  = json_encode($producto['nombre'] ?? '');
                            $productPriceJs = json_encode($precioMostrar);
                            $stockActual    = (int)($producto['stock_actual'] ?? 0);
                            ?>
                            <!-- FIX: usar addEventListener en DOMContentLoaded en lugar de onclick inline
                                 para poder llamar event.preventDefault() correctamente -->
                            <button type="button" id="btnAddCart" class="btn-add-cart"
                                    data-product-id="<?php echo ($producto['id'] ?? 0); ?>"
                                    data-product-name="<?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>"
                                    data-product-price="<?php echo $precioMostrar; ?>"
                                    data-stock="<?php echo $stockActual; ?>"
                                    <?php echo $stockActual <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-<?php echo $stockActual > 0 ? 'cart-plus' : 'times-circle'; ?>"></i>
                                <?php echo $stockActual > 0 ? 'Agregar al Carrito' : 'Agotado'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

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
                <a href="<?php echo BASE_URL; ?>/app/views/pages/inicio.php">Inicio</a>
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
    <script src="<?php echo BASE_URL; ?>/public/js/script.js"></script>
    <script>
        // Cambiar imagen principal
        function cambiarImagen(src, elemento) {
            document.getElementById('mainImage').src = src;
            document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
            elemento.classList.add('active');
        }

        // Agregar imágenes adicionales
        function setupImageUpload() {
            const addBtn = document.getElementById('addImagesBtn');
            const fileInput = document.getElementById('additionalImages');
            const statusDiv = document.getElementById('uploadStatus');
            
            if (!addBtn || !fileInput) return;
            
            addBtn.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', async function() {
                if (this.files.length === 0) return;
                
                // Validar archivos
                const maxFiles = 6;
                const maxBytes = 5 * 1024 * 1024;
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                
                if (this.files.length > maxFiles) {
                    showUploadStatus('Máximo ' + maxFiles + ' imágenes permitidas', 'error');
                    return;
                }
                
                for (let file of this.files) {
                    if (!allowedTypes.includes(file.type)) {
                        showUploadStatus('Solo se permiten imágenes (JPG, PNG, GIF, WebP)', 'error');
                        return;
                    }
                    if (file.size > maxBytes) {
                        showUploadStatus('La imagen "' + file.name + '" excede 5MB', 'error');
                        return;
                    }
                }
                
                // Subir imágenes
                await uploadAdditionalImages(this.files);
            });
        }
        
        async function uploadAdditionalImages(files) {
            const statusDiv = document.getElementById('uploadStatus');
            const addBtn = document.getElementById('addImagesBtn');
            
            // Mostrar progreso
            addBtn.disabled = true;
            addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
            showUploadStatus('Subiendo imágenes...', 'info');
            
            try {
                const formData = new FormData();
                formData.append('id', <?php echo $producto['id']; ?>);
                
                for (let i = 0; i < files.length; i++) {
                    formData.append('images[]', files[i]);
                }
                
                const apiUrl = (window.APP_BASE || '<?php echo rtrim(BASE_URL, "/"); ?>') + '/api/update_product.php';
                
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                
                if (result.ok) {
                    showUploadStatus('Imágenes agregadas correctamente', 'success');
                    
                    // Recargar la página para mostrar las nuevas imágenes
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showUploadStatus(result.error || 'Error al subir imágenes', 'error');
                }
                
            } catch (error) {
                console.error('Error:', error);
                showUploadStatus('Error de conexión', 'error');
            } finally {
                addBtn.disabled = false;
                addBtn.innerHTML = '<i class="fas fa-plus"></i> Agregar imágenes';
            }
        }
        
        function showUploadStatus(message, type) {
            const statusDiv = document.getElementById('uploadStatus');
            if (!statusDiv) return;
            
            statusDiv.style.display = 'block';
            statusDiv.className = 'upload-status ' + type;
            statusDiv.textContent = message;
        }

        // ── Agregar al carrito (vinculado via addEventListener, NO onclick inline) ──
        // Usar addEventListener evita que cualquier formulario padre o comportamiento
        // por defecto del navegador provoque un page reload al hacer clic.
        document.addEventListener('DOMContentLoaded', function () {
            // Inicializar carga de imágenes adicionales
            setupImageUpload();
            
            const btn = document.getElementById('btnAddCart');
            if (!btn) return;

            btn.addEventListener('click', function (e) {
                // CRÍTICO: prevenir cualquier submit de form padre o navegación
                e.preventDefault();
                e.stopPropagation();

                if (!window.USER_LOGGED_IN) {
                    if (typeof Toast !== 'undefined') {
                        Toast.warning('Debes iniciar sesión para agregar productos al carrito', 'Acceso requerido', 5000);
                    } else {
                        alert('Debes iniciar sesión para usar el carrito');
                    }
                    return;
                }

                if (btn.disabled) return;

                const productId = btn.dataset.productId;
                if (!productId || productId === '0') return;

                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
                btn.style.background = '';

                const apiUrl = (window.APP_BASE || '<?php echo rtrim(BASE_URL, "/"); ?>') + '/api/add_to_cart.php';

                fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ product_id: parseInt(productId), quantity: 1 })
                })
                .then(function (response) {
                    // Verificar que la respuesta sea JSON antes de parsear
                    const ct = response.headers.get('Content-Type') || '';
                    if (!ct.includes('application/json') && !ct.includes('text/json')) {
                        return response.text().then(function(text) {
                            console.error('add_to_cart.php returned non-JSON response:', text);
                            throw new Error('La respuesta del servidor no es JSON. Estado: ' + response.status);
                        });
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (data.success || data.ok) {
                        // Éxito: mostrar feedback y actualizar contador sin recargar la página
                        btn.innerHTML = '<i class="fas fa-check"></i> ¡Agregado!';
                        btn.style.background = '#27ae60';

                        if (typeof Toast !== 'undefined') {
                            Toast.success('Producto agregado al carrito', '¡Listo!', 3500);
                        }

                        // Actualizar contador del carrito en el header
                        const totalItems = data.total_items || data.cart_count || 0;
                        document.querySelectorAll('.cart-count').forEach(function (el) {
                            el.textContent = totalItems;
                            el.style.display = totalItems > 0 ? 'inline-block' : 'none';
                        });

                        // Actualizar stock en pantalla si el servidor devuelve el stock restante
                        if (typeof data.stock_remaining !== 'undefined') {
                            const stockDisplay = document.getElementById('stockDisplay');
                            if (stockDisplay) {
                                if (data.stock_remaining > 0) {
                                    stockDisplay.innerHTML = '<span class="in-stock"><i class="fas fa-check-circle"></i> ' + data.stock_remaining + ' unidades disponibles</span>';
                                } else {
                                    stockDisplay.innerHTML = '<span class="out-of-stock"><i class="fas fa-times-circle"></i> Agotado</span>';
                                    // Si se agotó, deshabilitar el botón permanentemente
                                    btn.innerHTML = '<i class="fas fa-times-circle"></i> Agotado';
                                    btn.disabled = true;
                                    btn.dataset.stock = '0';
                                    return; // No restaurar el botón
                                }
                            }
                        }

                        // Restaurar botón tras 2 s
                        setTimeout(function () {
                            btn.disabled = false;
                            btn.innerHTML = originalHTML;
                            btn.style.background = '';
                        }, 2000);

                    } else {
                        throw new Error(data.message || 'Error al agregar el producto');
                    }
                })
                .catch(function (error) {
                    console.error('agregarAlCarrito error:', error);
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                    if (typeof Toast !== 'undefined') {
                        Toast.error(error.message || 'Error al agregar el producto al carrito', 'Error', 5000);
                    }
                });
            });
        });

        // Mantener la función global por retrocompatibilidad con otros scripts que la llamen
        function agregarAlCarrito(id, nombre, precio, event) {
            if (event) { event.preventDefault(); event.stopPropagation(); }
            const btn = document.getElementById('btnAddCart');
            if (btn) btn.click();
        }
    </script>
</body>
</html>