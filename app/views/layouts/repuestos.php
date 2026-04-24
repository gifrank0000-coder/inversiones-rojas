<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
// Cargar productos de la categoría repuestos
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../helpers/promocion_helper.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';

$repuestos = [];
try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        // Cargar todos los repuestos enlazados en la tabla repuestos
        $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio_venta, p.precio_venta_bs, p.precio_venta_usd, pi.imagen_url, 
                       p.stock_actual AS stock, c.nombre as categoria, r.marca_compatible AS marca,
                       r.categoria_tecnica, r.modelo_compatible, r.anio_compatible,
                       pr.nombre as promo_promocion_nombre, pr.tipo_promocion as promo_tipo_promocion, pr.valor as promo_valor
                FROM productos p
                INNER JOIN repuestos r ON r.producto_id = p.id
                LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
                LEFT JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN producto_promociones pp ON pp.producto_id = p.id
                LEFT JOIN promociones pr ON pp.promocion_id = pr.id
                    AND pr.estado = true
                    AND pr.fecha_inicio <= CURRENT_DATE
                    AND pr.fecha_fin >= CURRENT_DATE
                WHERE p.estado = true
                ORDER BY p.id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $repuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($repuestos as &$repuesto) {
            $repuesto = aplicarPromocionAProducto($repuesto);
        }
        unset($repuesto);
        $repuestos = deduplicarProductosPorId($repuestos);
    }
} catch (Exception $e) {
    error_log('ERROR repuestos.php: fallo al cargar repuestos: ' . $e->getMessage());
}

// Obtener filtros reales de la base de datos
$categorias_repuestos = [];
$marcas_repuestos = [];
$modelos_repuestos = [];
$anios_repuestos = [];

try {
    $conn = $db->getConnection();
    if ($conn) {
        // Categorías técnicas de repuestos
        $stmtCat = $conn->query("
            SELECT DISTINCT r.categoria_tecnica 
            FROM repuestos r
            INNER JOIN productos p ON p.id = r.producto_id
            WHERE p.estado = true AND r.categoria_tecnica IS NOT NULL AND r.categoria_tecnica != ''
            ORDER BY r.categoria_tecnica
        ");
        $categorias_repuestos = $stmtCat->fetchAll(PDO::FETCH_COLUMN);
        
        // Marcas compatibles
        $stmtMarcas = $conn->query("
            SELECT DISTINCT LOWER(TRIM(r.marca_compatible)) as marca_min
            FROM repuestos r
            INNER JOIN productos p ON p.id = r.producto_id
            WHERE p.estado = true AND r.marca_compatible IS NOT NULL AND r.marca_compatible != ''
            ORDER BY LOWER(TRIM(r.marca_compatible))
        ");
        $marcas_repuestos = $stmtMarcas->fetchAll(PDO::FETCH_COLUMN);
        
        // Modelos compatibles
        $stmtModelos = $conn->query("
            SELECT DISTINCT r.modelo_compatible 
            FROM repuestos r
            INNER JOIN productos p ON p.id = r.producto_id
            WHERE p.estado = true AND r.modelo_compatible IS NOT NULL AND r.modelo_compatible != ''
            ORDER BY r.modelo_compatible
        ");
        $modelos_repuestos = $stmtModelos->fetchAll(PDO::FETCH_COLUMN);
        
        // Años compatibles
        $stmtAnios = $conn->query("
            SELECT DISTINCT r.anio_compatible 
            FROM repuestos r
            INNER JOIN productos p ON p.id = r.producto_id
            WHERE p.estado = true AND r.anio_compatible IS NOT NULL
            ORDER BY r.anio_compatible DESC
        ");
        $anios_repuestos = $stmtAnios->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Exception $e) {
    error_log('ERROR repuestos.php: fallo al cargar filtros: ' . $e->getMessage());
}

// Verificar si el usuario está logueado
$usuario_logueado = false;
$user_name = '';
$user_rol = '';
$user_email = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    $usuario_logueado = true;
    $user_name = $_SESSION['user_name'];
    $user_rol = $_SESSION['user_rol'] ?? 'cliente';
    $user_email = $_SESSION['user_email'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repuestos - Inversiones Rojas</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/auth.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/admin.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/home.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/components/user-panel.css">
    <style>
        .moneda-bs { color: #1F9166; font-weight: 700; }
        .moneda-usd { color: #6c757d; font-size: 0.85em; }
        .promo-badge-mini {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.75em;
            margin-right: 5px;
        }
        
        /* Estilos específicos para la página de repuestos */
        .repuestos-page {
            padding: 40px 0;
            min-height: 70vh;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .page-title h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .page-title p {
            color: #666;
            font-size: 1.1rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Filtros estilo catálogo */
        .catalog-filters {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }
        
        .filter-group select {
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            background: white;
            font-size: 16px;
        }
        
        .filter-group select:focus {
            border-color: #1F9166;
            outline: none;
        }
        
        .filter-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .filter-btn {
            padding: 12px 30px;
            background: #1F9166;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .filter-btn:hover {
            background: #187a54;
            transform: translateY(-2px);
        }
        
        .filter-btn.reset {
            background: #95a5a6;
        }
        
        .filter-btn.reset:hover {
            background: #7f8c8d;
        }
        
        /* Grid de productos estilo e-commerce */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #e74c3c;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 2;
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: contain;
            background: #f9f9f9;
            padding: 20px;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        
        .product-details {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .product-price {
            color: #e74c3c;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .product-stock {
            color: #27ae60;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-view {
            flex: 2;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
            text-align: center;
            text-decoration: none;
        }
        
        .btn-view:hover {
            background: #2980b9;
        }
        
        .btn-cart {
            flex: 1;
            padding: 12px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-cart:hover {
            background: #27ae60;
        }
        
        /* PAGINACIÓN - Estilo clásico */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 40px 0;
            padding: 20px 0;
        }
        
        .pagination-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 2px solid #ddd;
            border-radius: 6px;
            color: #333;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .pagination-btn:hover {
            background: #f5f5f5;
            border-color: #1F9166;
            color: #1F9166;
        }
        
        .pagination-btn.active {
            background: #1F9166;
            border-color: #1F9166;
            color: white;
        }
        
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-btn.disabled:hover {
            background: white;
            border-color: #ddd;
            color: #333;
        }
        
        .pagination-dots {
            color: #666;
            font-size: 14px;
        }
        
        /* Contador de productos */
        .products-count {
            text-align: center;
            margin: 20px 0;
            color: #666;
            font-size: 1.1rem;
        }
        
        .products-count strong {
            color: #e74c3c;
        }
        
        /* Búsqueda por referencia */
        .reference-search {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .search-container {
            display: flex;
            gap: 10px;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .search-btn {
            padding: 12px 25px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                flex-direction: column;
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .btn-view, .btn-cart {
                width: 100%;
            }
            
            .pagination {
                gap: 5px;
            }
            
            .pagination-btn {
                width: 35px;
                height: 35px;
                font-size: 13px;
            }
            
            .search-container {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title h1 {
                font-size: 2rem;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
            
            .pagination-btn {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Reutilizar el mismo header que inicio.php -->
    <?php require __DIR__ . '/partials/header.php'; ?>

    <!-- Contenido principal de la página de repuestos -->
    <main class="repuestos-page container">
        <!-- Título y descripción -->
        <div class="page-title">
            <h1><i class="fas fa-cogs"></i> REPUESTOS PARA MOTOS</h1>
            <p>Encuentra los repuestos originales y de calidad para el mantenimiento de tu moto</p>
        </div>
        
        <!-- Contador de productos -->
        <div class="products-count">
            Mostrando <strong><?php echo count($repuestos); ?> repuestos</strong> disponibles
        </div>
        
   
        <!-- Filtros -->
        <div class="catalog-filters">
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="categoria"><i class="fas fa-tag"></i> Categoría</label>
                    <select id="categoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias_repuestos as $cat): ?>
                            <option value="<?php echo strtolower(htmlspecialchars($cat)); ?>">
                                <?php echo htmlspecialchars(ucfirst($cat)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="marca"><i class="fas fa-trademark"></i> Marca Compatible</label>
                    <select id="marca">
                        <option value="">Todas las marcas</option>
                        <?php foreach ($marcas_repuestos as $marca): ?>
                            <option value="<?php echo htmlspecialchars($marca); ?>">
                                <?php echo htmlspecialchars(ucfirst($marca)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="modelo"><i class="fas fa-motorcycle"></i> Modelo Compatible</label>
                    <select id="modelo">
                        <option value="">Todos los modelos</option>
                        <?php foreach ($modelos_repuestos as $modelo): ?>
                            <option value="<?php echo strtolower(htmlspecialchars($modelo)); ?>">
                                <?php echo htmlspecialchars($modelo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="anio"><i class="fas fa-calendar"></i> Año Compatible</label>
                    <select id="anio">
                        <option value="">Todos los años</option>
                        <?php foreach ($anios_repuestos as $anio): ?>
                            <option value="<?php echo htmlspecialchars($anio); ?>">
                                <?php echo htmlspecialchars($anio); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="filter-actions">
                <button class="filter-btn" onclick="filtrarProductos()">
                    <i class="fas fa-search"></i> Filtrar Repuestos
                </button>
                <button class="filter-btn reset" onclick="limpiarFiltros()">
                    <i class="fas fa-redo"></i> Limpiar Filtros
                </button>
            </div>
        </div>
        
        <!-- Grid de productos (estilo e-commerce) -->
        <div class="products-grid" id="productsGrid">
            <?php if (!empty($repuestos)): ?>
                <?php foreach ($repuestos as $repuesto): ?>
                    <?php 
                    // Determinar el tipo de badge según disponibilidad
                    $badge_class = 'product-badge';
                    $badge_text = 'ORIGINAL';
                    
                    if (($repuesto['stock'] ?? 0) <= 5) {
                        $badge_text = 'POCO STOCK';
                    }
                    
                    // Determinar categoría para filtros (usar categoria_tecnica de la tabla repuestos)
                    $categoria = strtolower($repuesto['categoria_tecnica'] ?? $repuesto['categoria'] ?? '');
                    $marca = strtolower($repuesto['marca'] ?? '');
                    $modelo = strtolower($repuesto['modelo_compatible'] ?? '');
                    $anio = $repuesto['anio_compatible'] ?? '';
                    ?>
                    
                    <div class="product-card" 
                         data-categoria="<?php echo $categoria; ?>" 
                         data-marca="<?php echo $marca; ?>"
                         data-modelo="<?php echo $modelo; ?>"
                         data-anio="<?php echo $anio; ?>">
                        <?php if ($badge_text): ?>
                            <div class="<?php echo $badge_class; ?>"><?php echo $badge_text; ?></div>
                        <?php endif; ?>
                        
                            <?php if (!empty($repuesto['imagen_url'])): ?>
                                <img src="<?php echo htmlspecialchars($repuesto['imagen_url']); ?>" alt="<?php echo htmlspecialchars($repuesto['nombre']); ?>" class="product-image">
                            <?php else: ?>
                                <div class="product-image no-image" style="background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#666; font-size:14px;">Sin imagen</div>
                            <?php endif; ?>
                        
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($repuesto['nombre']); ?></h3>
                            
                            <div class="product-details">
                                <i class="fas fa-tag"></i> 
                                <?php echo htmlspecialchars($repuesto['categoria'] ?? 'Repuesto'); ?>
                                <?php if ($repuesto['marca']): ?>
                                    | <i class="fas fa-trademark"></i> 
                                    <?php echo htmlspecialchars($repuesto['marca']); ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-price">
                                <?php 
                                $precioOriginal = floatval($repuesto['precio_venta'] ?? 0);
                                $precioMostrar = floatval($repuesto['precio_real'] ?? $precioOriginal);
                                $descuento = 0;
                                if ($precioOriginal > 0 && $precioMostrar < $precioOriginal) {
                                    $descuento = round((($precioOriginal - $precioMostrar) / $precioOriginal) * 100);
                                }
                                $precios = formatearMonedaDual($precioMostrar);
                                $preciosOriginal = formatearMonedaDual($precioOriginal);
                                ?>
                                <?php if ($descuento > 0): ?>
                                    <span class="promo-badge-mini">-<?php echo $descuento; ?>%</span>
                                    <span style="text-decoration:line-through;color:#999;font-size:0.85em;"><?php echo $preciosOriginal['bs']; ?></span>
                                    <span style="color:#e74c3c;font-weight:700;"><?php echo $precios['bs']; ?></span>
                                    <span class="moneda-usd"><?php echo $precios['usd']; ?></span>
                                <?php else: ?>
                                    <span class="moneda-bs"><?php echo $precios['bs']; ?></span>
                                    <span class="moneda-usd"><?php echo $precios['usd']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($repuesto['promocion']['nombre']) || !empty($repuesto['promo_nombre'])): ?>
                                <div style="font-size:0.75rem;color:#1F9166;margin-top:4px;">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($repuesto['promocion']['nombre'] ?? $repuesto['promo_nombre']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($repuesto['stock'])): ?>
                                <div class="product-stock">
                                    <i class="fas fa-check-circle"></i> 
                                    <?php echo $repuesto['stock']; ?> unidades disponibles
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-actions">
                                <a href="<?php echo BASE_URL; ?>/app/views/layouts/product_detail.php?id=<?php echo $repuesto['id']; ?>" 
                                   class="btn-view">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </a>
                                <button class="btn-cart" onclick="agregarAlCarrito(<?php echo $repuesto['id']; ?>)">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="padding:40px;">
                    <i class="fas fa-box-open" style="font-size:36px;color:#ccc;margin-bottom:10px;"></i>
                    <p>No hay repuestos disponibles en este momento.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- PAGINACIÓN - Generada dinámicamente -->
        <div class="pagination" id="paginationContainer">
            <!-- Los botones se generan con JavaScript según la cantidad real de productos -->
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="footer-content-custom">
            <div class="footer-section-custom">
                <h3>Acerca de Nosotros</h3>
                <p>Somos una tienda especializada en repuestos y vehículos Bera, ofreciendo productos de alta calidad y el mejor servicio al cliente.</p>
            </div>
            
            <div class="footer-section-custom">
                <h3>Contacto</h3>
                <p><i class="fas fa-map-marker-alt"></i> Av. Principal 123, Ciudad</p>
                <p><i class="fas fa-phone"></i> +1 234 567 890</p>
                <p><i class="fas fa-envelope"></i> info@inversionesrojas.com</p>
            </div>
            
            <div class="footer-section-custom">
                <h3>Enlaces Rápidos</h3>
                <a href="<?php echo BASE_URL; ?>/app/views/pages/inicio.php">Inicio</a>
                <a href="<?php echo BASE_URL; ?>/app/views/pages/motos.php">Motos</a>
                <a href="<?php echo BASE_URL; ?>/app/views/pages/accesorios.php">Accesorios</a>
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
    <script src="<?php echo BASE_URL; ?>/public/js/components/user-panel.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/script.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/inv-notifications.js"></script>
    <script src="/inversiones-rojas/public/js/toast.js"></script>
    <script>
        var APP_BASE = '<?php echo BASE_URL; ?>';
        
        function addItemToDrawer(id, nombre, cantidad, precio, imagen) {
            const cartItems = document.getElementById('cartItems');
            if (!cartItems) return;
            
            const imgSrc = imagen || '';
            const tasaCambio = window.TASA_CAMBIO || 1;
            const precioNum = typeof precio === 'number' ? precio : parseFloat(String(precio).replace(/[^0-9.]/g, '')) || 0;
            const precioBS = precioNum * tasaCambio;
            const precioUSDStr = '$' + precioNum.toFixed(2);
            const precioBSStr = 'Bs ' + precioBS.toFixed(2);
            
            const existingItem = cartItems.querySelector(`.cart-drawer-item[data-id="${id}"]`);
            if (existingItem) {
                const meta = existingItem.querySelector('.cart-drawer-meta small');
                if (meta) {
                    const currentQty = parseInt(meta.textContent.split(' × ')[0]) || 1;
                    meta.innerHTML = `${cantidad} × <span class="moneda-usd">${precioUSDStr}</span> <span class="moneda-bs">${precioBSStr}</span>`;
                }
                return;
            }
            
            const html = `<li class="cart-drawer-item" data-id="${id}">
                <img src="${imgSrc}" class="cart-drawer-img" alt="${nombre}"/>
                <div class="cart-drawer-meta">
                    <div><strong>${nombre}</strong></div>
                    <small>${cantidad} × <span class="moneda-usd">${precioUSDStr}</span> <span class="moneda-bs">${precioBSStr}</span></small>
                </div>
                <button class="cart-item-remove" title="Eliminar" data-id="${id}"><i class="fas fa-trash-alt"></i></button>
            </li>`;
            
            const emptyMsg = cartItems.querySelector('p[style*="Tu carrito está vacío"]');
            if (emptyMsg) {
                cartItems.innerHTML = '<ul class="cart-drawer-list">' + html + '</ul>';
            } else {
                const list = cartItems.querySelector('.cart-drawer-list');
                if (list) {
                    list.insertAdjacentHTML('beforeend', html);
                } else {
                    cartItems.innerHTML = '<ul class="cart-drawer-list">' + html + '</ul>';
                }
            }
        }
        
        // Variables para paginación
        let paginaActual = 1;
        const productosPorPagina = 8; // Mostrar 8 productos por página
        
        // Búsqueda por referencia
        function buscarPorReferencia() {
            const input = document.querySelector('.search-input');
            const busqueda = input.value.toLowerCase().trim();
            
            const notify = (message, type = 'info', title = '') => {
                if (window.Toast && typeof Toast[type] === 'function') {
                    Toast[type](message, title);
                } else if (typeof showToast === 'function') {
                    showToast(message, type, title);
                } else {
                    alert(message);
                }
            };
            
            if (!busqueda) {
                notify('Por favor, ingresa una referencia o nombre para buscar', 'warning', 'Búsqueda requerida');
                return;
            }
            
            const productos = document.querySelectorAll('.product-card');
            let encontrados = 0;
            
            productos.forEach(producto => {
                const nombre = producto.getAttribute('data-nombre').toLowerCase();
                
                if (nombre.includes(busqueda)) {
                    producto.style.display = 'block';
                    encontrados++;
                } else {
                    producto.style.display = 'none';
                }
            });
            
            // Actualizar contador
            const contador = document.querySelector('.products-count strong');
            if (contador) {
                contador.textContent = encontrados + ' repuestos encontrados';
            }
            
            if (encontrados === 0) {
                notify('No se encontraron repuestos con la búsqueda: ' + busqueda, 'warning', 'Sin resultados');
            }
            
            // Resetear paginación
            paginaActual = 1;
            actualizarPaginacion();
            mostrarPagina(paginaActual);
            
            // Limpiar campo de búsqueda
            input.value = '';
        }
        
        // Funcionalidad para filtros
        function filtrarProductos() {
            const categoria = document.getElementById('categoria').value.toLowerCase();
            const marca = document.getElementById('marca').value.toLowerCase();
            const modelo = document.getElementById('modelo').value.toLowerCase();
            const anio = document.getElementById('anio').value;
            
            // Obtener todas las cards de productos
            const productos = document.querySelectorAll('.product-card');
            let productosVisibles = 0;
            
            productos.forEach(producto => {
                const productoCategoria = producto.dataset.categoria || '';
                const productoMarca = producto.dataset.marca || '';
                const productoModelo = producto.dataset.modelo || '';
                const productoAnio = producto.dataset.anio || '';
                
                let mostrar = true;
                
                if (categoria && productoCategoria !== categoria) {
                    mostrar = false;
                }
                if (marca && productoMarca !== marca) {
                    mostrar = false;
                }
                if (modelo && productoModelo !== modelo) {
                    mostrar = false;
                }
                if (anio && productoAnio !== anio) {
                    mostrar = false;
                }
                
                if (mostrar) {
                    producto.style.display = 'block';
                    productosVisibles++;
                } else {
                    producto.style.display = 'none';
                }
            });
            
            // Actualizar contador
            document.querySelector('.products-count strong').textContent = productosVisibles + ' repuestos';
            
            // Resetear paginación
            paginaActual = 1;
            actualizarPaginacion();
            mostrarPagina(paginaActual);
        }
        
        function mostrarProductosFiltrados(productos) {
            const grid = document.getElementById('productsGrid');
            grid.innerHTML = '';
            
            if (productos.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><p style="font-size: 18px; color: #666;">No se encontraron repuestos con los filtros seleccionados</p></div>';
                return;
            }
            
            productos.forEach(repuesto => {
                const imagen = repuesto.imagen_url ? repuesto.imagen_url : 'https://via.placeholder.com/300x200?text=Sin+Imagen';
                const cardHtml = `
                    <div class="product-card" data-categoria="${repuesto.categoria || ''}" data-marca="${repuesto.marca || ''}" data-precio="${repuesto.precio_venta}">
                        ${repuesto.stock <= 0 ? '<div class="product-badge">Agotado</div>' : ''}
                        <img src="${imagen}" alt="${repuesto.nombre}" class="product-image">
                        <div class="product-info">
                            <h3 class="product-title">${repuesto.nombre}</h3>
                            <div class="product-details">${repuesto.categoria || ''}</div>
                            <div class="product-price">$${parseFloat(repuesto.precio_venta).toLocaleString('es-VE', {minimumFractionDigits: 2})}</div>
                            <div class="product-stock">${repuesto.stock > 0 ? '✓ En stock: ' + repuesto.stock + ' unidades' : 'Agotado'}</div>
                            <div class="product-actions">
                                <a href="<?php echo BASE_URL; ?>/app/views/layouts/product_detail.php?id=${repuesto.id}" class="btn-view">Ver Detalles</a>
                                <button class="btn-cart" onclick="agregarAlCarrito(${repuesto.id})">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                grid.innerHTML += cardHtml;
            });
        }
        
        function limpiarFiltros() {
            document.getElementById('categoria').value = '';
            document.getElementById('marca').value = '';
            document.getElementById('modelo').value = '';
            document.getElementById('anio').value = '';
            
            const productos = document.querySelectorAll('.product-card');
            productos.forEach(producto => {
                producto.style.display = 'block';
            });
            
            // Restaurar contador
            const contador = document.querySelector('.products-count strong');
            const totalProductos = document.querySelectorAll('.product-card').length;
            if (contador) {
                contador.textContent = totalProductos + ' repuestos';
            }
            
            // Resetear paginación
            paginaActual = 1;
            actualizarPaginacion();
            mostrarPagina(paginaActual);
        }
        
        // Función para agregar al carrito
        async function agregarAlCarrito(productoId) {
            const productoCard = event.target.closest('.product-card');
            const productoNombre = productoCard.querySelector('.product-title').textContent;
            const productoPrecio = productoCard.querySelector('.product-price').textContent;
            const productoImagen = productoCard.querySelector('img.product-image')?.src || '';

            const confirmed = typeof showConfirm === 'function'
                ? await showConfirm({
                    title: '¿Agregar al carrito?',
                    message: `¿Agregar "${productoNombre}" al carrito?\nPrecio: ${productoPrecio}`,
                    confirmText: 'Aceptar',
                    cancelText: 'Cancelar',
                    type: 'info'
                })
                : confirm(`¿Agregar "${productoNombre}" al carrito?\nPrecio: ${productoPrecio}`);

            if (!confirmed) return;

            try {
                const response = await fetch((window.APP_BASE || '') + '/api/add_to_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productoId, quantity: 1 })
                });

                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(text || 'HTTP ' + response.status);
                }

                const ct = response.headers.get('content-type') || '';
                const data = ct.includes('application/json') ? await response.json() : JSON.parse(await response.text());

                if (data.success) {
                    if (window.Toast && typeof Toast.success === 'function') {
                        Toast.success(`${productoNombre} ha sido agregado al carrito exitosamente.`, '¡Producto agregado!', 5000);
                    } else if (typeof showToast === 'function') {
                        showToast(`${productoNombre} ha sido agregado al carrito exitosamente.`, 'success', '¡Producto agregado!');
                    }

                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.total_items;
                        cartCount.style.display = 'flex';
                    }
                    
                    // Actualizar drawer directamente con datos del servidor
                    const finalQty = data.producto_qty || 1;
                    const finalImg = data.producto_imagen || productoImagen;
                    const finalNombre = data.producto_nombre || productoNombre;
                    const finalPrecio = data.producto_precio || productoPrecio;
                    addItemToDrawer(productoId, finalNombre, finalQty, finalPrecio, finalImg);
                } else {
                    if (window.Toast && typeof Toast.error === 'function') {
                        Toast.error(data.message || 'Error al agregar el producto.', 'Error');
                    } else if (typeof showToast === 'function') {
                        showToast(data.message || 'Error al agregar el producto.', 'error', 'Error');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (window.Toast && typeof Toast.error === 'function') {
                    Toast.error('Error al agregar el producto al carrito. Inténtalo de nuevo.', 'Error de conexión');
                } else if (typeof showToast === 'function') {
                    showToast('Error al agregar el producto al carrito. Inténtalo de nuevo.', 'error', 'Error de conexión');
                }
            }
        }
        
        // Funcionalidad de paginación
        function cambiarPagina(numeroPagina) {
            if (numeroPagina === -1) {
                // Página anterior
                if (paginaActual > 1) {
                    paginaActual--;
                }
            } else if (numeroPagina === 0) {
                // Página siguiente
                const totalProductos = document.querySelectorAll('.product-card').length;
                const totalPaginas = Math.ceil(totalProductos / productosPorPagina);
                if (paginaActual < totalPaginas) {
                    paginaActual++;
                }
            } else {
                // Página específica
                paginaActual = numeroPagina;
            }
            
            mostrarPagina(paginaActual);
            actualizarPaginacion();
        }
        
        function mostrarPagina(pagina) {
            const productos = document.querySelectorAll('.product-card');
            const inicio = (pagina - 1) * productosPorPagina;
            const fin = inicio + productosPorPagina;
            
            let contador = 0;
            productos.forEach((producto, index) => {
                // Solo mostrar productos que estén visibles (no filtrados)
                if (producto.style.display !== 'none') {
                    contador++;
                    if (contador > inicio && contador <= fin) {
                        producto.style.display = 'block';
                    } else {
                        producto.style.display = 'none';
                    }
                }
            });
            
            // Actualizar contador
            const totalProductosVisibles = document.querySelectorAll('.product-card[style="display: block"]').length;
            const totalProductos = Array.from(productos).filter(p => p.style.display !== 'none').length;
            
            const contadorElement = document.querySelector('.products-count strong');
            if (contadorElement) {
                contadorElement.textContent = `${totalProductosVisibles} de ${totalProductos} repuestos`;
            }
        }
        
        function actualizarPaginacion() {
            const productos = document.querySelectorAll('.product-card');
            const totalProductos = Array.from(productos).filter(p => p.style.display !== 'none').length;
            const totalPaginas = Math.max(1, Math.ceil(totalProductos / productosPorPagina));
            
            // Ocultar paginación si solo hay una página
            const paginationContainer = document.getElementById('paginationContainer');
            if (paginationContainer) {
                if (totalPaginas <= 1) {
                    paginationContainer.style.display = 'none';
                } else {
                    paginationContainer.style.display = 'flex';
                    let paginationHTML = `
                        <button class="pagination-btn prev-btn" onclick="cambiarPagina(-1)" ${paginaActual === 1 ? 'disabled' : ''}>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    `;
                    
                    for (let i = 1; i <= totalPaginas; i++) {
                        paginationHTML += `<button class="pagination-btn ${i === paginaActual ? 'active' : ''}" onclick="cambiarPagina(${i})">${i}</button>`;
                    }
                    
                    paginationHTML += `
                        <button class="pagination-btn next-btn" onclick="cambiarPagina(0)" ${paginaActual === totalPaginas ? 'disabled' : ''}>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    `;
                    
                    paginationContainer.innerHTML = paginationHTML;
                }
            }
            
            const prevBtn = document.querySelector('.prev-btn');
            const nextBtn = document.querySelector('.next-btn');
            
            if (prevBtn) {
                prevBtn.disabled = paginaActual === 1;
                prevBtn.classList.toggle('disabled', paginaActual === 1);
            }
            if (nextBtn) {
                nextBtn.disabled = paginaActual === totalPaginas;
                nextBtn.classList.toggle('disabled', paginaActual === totalPaginas);
            }
        }
        
        // Inicializar paginación
        document.addEventListener('DOMContentLoaded', function() {
            const totalProductos = document.querySelectorAll('.product-card').length;
            if (totalProductos > productosPorPagina) {
                mostrarPagina(1);
                actualizarPaginacion();
            }
        });
    </script>
</body>
</html>