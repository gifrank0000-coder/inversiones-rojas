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
        
        // Debug: log del resultado
        error_log("Consulta producto ID $producto_id: " . ($producto ? 'Encontrado' : 'No encontrado'));
        if ($producto) {
            error_log("Producto: " . json_encode($producto));
            
            // Aplicar promoción si existe
            $producto = aplicarPromocionAProducto($producto);
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
    <?php require __DIR__ . '/partials/header.php'; ?>

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
            <div class="product-detail-container">
                <!-- Breadcrumb -->
                <div class="product-breadcrumb">
                    <a href="<?php echo BASE_URL; ?>/app/views/pages/inicio.php">Inicio</a> / 
                    <?php
                    $categoria_link = BASE_URL . '/app/views/pages/motos.php'; // default
                    if (isset($producto['categoria'])) {
                        $cat = strtolower($producto['categoria']);
                        if (strpos($cat, 'repuesto') !== false) {
                            $categoria_link = BASE_URL . '/app/views/pages/repuestos.php';
                        } elseif (strpos($cat, 'accesorio') !== false) {
                            $categoria_link = BASE_URL . '/app/views/pages/accesorios.php';
                        }
                    }
                    ?>
                    <a href="<?php echo $categoria_link; ?>"><?php echo htmlspecialchars($producto['categoria'] ?? 'Productos'); ?></a> / 
                    <span><?php echo htmlspecialchars($producto['nombre']); ?></span>
                </div>
                
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
                    </div>
                    
                    <!-- Información del producto -->
                    <div class="product-info">
                        <div class="product-category">
                            <?php echo htmlspecialchars($producto['categoria'] ?? 'Moto'); ?>
                        </div>
                        <h1 class="product-title"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
                        
                        <?php 
                        $precioOriginal = floatval($producto['precio_venta'] ?? 0);
                        $precioMostrar = floatval($producto['precio_real'] ?? $precioOriginal);
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
                            <?php else: ?>
                                <span class="price-segment"><?php echo htmlspecialchars($precios['bs']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($descuento > 0 && !empty($producto['promo_nombre'])): ?>
                            <div style="font-size:0.85rem; color:#1F9166; margin-top:5px;">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($producto['promo_nombre']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Especificaciones -->
                        <div class="product-specs">
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
                        <div class="product-stock">
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
                            <button class="btn-add-cart" 
                                    onclick="agregarAlCarrito(<?php echo $producto['id']; ?>, '<?php echo htmlspecialchars($producto['nombre']); ?>', <?php echo isset($producto['precio_real']) ? $producto['precio_real'] : $producto['precio_venta']; ?>, event)" 
                                    <?php echo ($producto['stock_actual'] ?? 0) <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-cart-plus"></i>
                                <?php echo ($producto['stock_actual'] ?? 0) > 0 ? 'Agregar al Carrito' : 'Agotado'; ?>
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
            
            // Remover clase active de todas las miniaturas
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            
            // Agregar clase active a la miniatura clickeada
            elemento.classList.add('active');
        }
        
        // Agregar al carrito
        function agregarAlCarrito(id, nombre, precio, event) {
            const cantidad = 1; // Por defecto agregar 1 unidad
            const baseUrl = document.querySelector('[data-base-url]')?.getAttribute('data-base-url') || 
                           (typeof BASE_URL !== 'undefined' ? BASE_URL : '/inversiones-rojas');
            
            // Mostrar indicador de carga
            const btn = event ? event.target.closest('.btn-add-cart') : null;
            const btnText = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
            }

            // Hacer petición AJAX al endpoint
            fetch(baseUrl + '/api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: id,
                    quantity: cantidad
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    btn.innerHTML = '<i class="fas fa-check"></i> ¡Agregado al carrito!';
                    btn.style.background = '#27ae60';
                    
                    // Actualizar contador del carrito si existe
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.total_items;
                        cartCount.style.display = 'flex';
                    }
                    
                    // Restaurar botón después de 2 segundos
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = btnText;
                        btn.style.background = '';
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Error al agregar al carrito');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.disabled = false;
                btn.innerHTML = btnText;
                alert('Error: ' + error.message);
            });
        }
    </script>
</body>
</html>