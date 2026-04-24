<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
// Cargar productos de la categoría motos
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../helpers/promocion_helper.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';

$motos = [];
try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        // Cargar todas las motos enlazadas en la tabla vehiculos
        $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio_venta, p.precio_venta_bs, p.precio_venta_usd, pi.imagen_url, 
                   p.stock_actual AS stock, v.marca AS marca, v.modelo, v.anio, v.cilindrada,
                   c.nombre AS categoria_nombre,
                   pr.tipo_promocion AS promo_tipo_promocion,
                   pr.valor AS promo_valor,
                   pr.nombre AS promo_nombre
            FROM productos p
            INNER JOIN vehiculos v ON v.producto_id = p.id
            LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
            LEFT JOIN categorias c ON c.id = p.categoria_id
            LEFT JOIN producto_promociones pp ON pp.producto_id = p.id
            LEFT JOIN promociones pr ON pr.id = pp.promocion_id
                AND pr.estado = true
                AND pr.fecha_inicio <= CURRENT_DATE
                AND pr.fecha_fin >= CURRENT_DATE
            WHERE p.estado = true
            ORDER BY p.id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $motos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($motos as &$moto) {
            $moto = aplicarPromocionAProducto($moto);
        }
        unset($moto);
        $motos = deduplicarProductosPorId($motos);
    }
} catch (Exception $e) {
    error_log('ERROR motos.php: fallo al cargar motos: ' . $e->getMessage());
}

// Obtener categorías reales de la base de datos para los filtros
$categorias_motos = [];
$marcas_disponibles = [];
$tipos_disponibles = [];
$cilindradas_disponibles = [];

try {
    $conn = $db->getConnection();
    if ($conn) {
        // Obtener categorías de vehículos
        $stmtCat = $conn->query("
            SELECT c.id, c.nombre 
            FROM categorias c 
            INNER JOIN productos p ON p.categoria_id = c.id
            INNER JOIN vehiculos v ON v.producto_id = p.id
            WHERE c.estado = true AND p.estado = true
            GROUP BY c.id, c.nombre
            ORDER BY c.nombre
        ");
        $categorias_motos = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener marcas únicas de vehículos (normalizadas)
        $stmtMarcas = $conn->query("
            SELECT DISTINCT LOWER(TRIM(marca)) as marca_min, INITCAP(TRIM(marca)) as marca_label
            FROM vehiculos v
            INNER JOIN productos p ON p.id = v.producto_id
            WHERE p.estado = true AND v.marca IS NOT NULL AND v.marca != ''
            ORDER BY INITCAP(TRIM(marca))
        ");
        $marcas_raw = $stmtMarcas->fetchAll(PDO::FETCH_ASSOC);
        // Eliminar duplicados por nombre normalizado
        $marcas_unicas = [];
        foreach ($marcas_raw as $m) {
            if (!isset($marcas_unicas[$m['marca_min']])) {
                $marcas_unicas[$m['marca_min']] = $m['marca_label'];
            }
        }
        $marcas_disponibles = array_values($marcas_unicas);
        
        // Obtener tipos únicos de vehículos
        $stmtTipos = $conn->query("
            SELECT DISTINCT tipo_vehiculo FROM vehiculos v
            INNER JOIN productos p ON p.id = v.producto_id
            WHERE p.estado = true AND v.tipo_vehiculo IS NOT NULL AND v.tipo_vehiculo != ''
            ORDER BY tipo_vehiculo
        ");
        $tipos_disponibles = $stmtTipos->fetchAll(PDO::FETCH_COLUMN);
        
        // Obtener cilindradas únicas
        $stmtCil = $conn->query("
            SELECT DISTINCT cilindrada FROM vehiculos v
            INNER JOIN productos p ON p.id = v.producto_id
            WHERE p.estado = true AND v.cilindrada IS NOT NULL AND v.cilindrada != ''
            ORDER BY cilindrada
        ");
        $cilindradas_disponibles = $stmtCil->fetchAll(PDO::FETCH_COLUMN);
        
        // Debug: verificar que se cargaron las cilindradas
        error_log('Cilindradas disponibles: ' . count($cilindradas_disponibles));
    }
} catch (Exception $e) {
    error_log('ERROR motos.php: fallo al cargar filtros: ' . $e->getMessage());
    // Asegurar que siempre haya opciones por defecto
    if (empty($cilindradas_disponibles)) {
        $cilindradas_disponibles = [];
    }
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
    <title>Motos - Inversiones Rojas</title>
    <script>var TASA_CAMBIO = <?php echo getTasaCambio(); ?>;</script>
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
        
        /* Estilos específicos para la página de motos */
        .motos-page {
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
        
        .product-specs {
            display: flex;
            gap: 15px;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .product-spec {
            display: flex;
            align-items: center;
            gap: 5px;
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

    <!-- Contenido principal de la página de motos -->
    <main class="motos-page container">
        <!-- Título y descripción -->
        <div class="page-title">
            <h1><i class="fas fa-motorcycle"></i> CATÁLOGO DE MOTOS</h1>
            <p>Explora nuestra amplia selección de motos disponibles para compra inmediata</p>
        </div>
        
        <!-- Contador de productos -->
        <div class="products-count">
            Mostrando <strong><?php echo count($motos); ?> motos</strong> disponibles para compra
        </div>
        
 
        <div class="catalog-filters">
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="marca"><i class="fas fa-tag"></i> Marca</label>
                    <select id="marca">
                        <option value="">Todas las marcas</option>
                        <?php foreach ($marcas_disponibles as $marca): ?>
                            <option value="<?php echo strtolower(htmlspecialchars($marca)); ?>">
                                <?php echo htmlspecialchars(ucfirst($marca)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="cilindrada"><i class="fas fa-tachometer-alt"></i> Cilindrada</label>
                    <select id="cilindrada">
                        <option value="">Todas</option>
                        <?php foreach ($cilindradas_disponibles as $cil): ?>
                            <option value="<?php echo htmlspecialchars($cil); ?>">
                                <?php echo htmlspecialchars($cil); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="categoria"><i class="fas fa-tag"></i> Categoría</label>
                    <select id="categoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias_motos as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['nombre']); ?>">
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="anio"><i class="fas fa-calendar"></i> Año</label>
                    <select id="anio">
                        <option value="">Todos los años</option>
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                        <option value="2022">2022</option>
                        <option value="2021">2021</option>
                        <option value="2020">2020</option>
                        <option value="antiguo">Anterior a 2020</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-actions">
                <button class="filter-btn" onclick="filtrarProductos()">
                    <i class="fas fa-search"></i> Filtrar Motos
                </button>
                <button class="filter-btn reset" onclick="limpiarFiltros()">
                    <i class="fas fa-redo"></i> Limpiar Filtros
                </button>
            </div>
        </div>
        
        <!-- Grid de productos (estilo e-commerce) -->
        <div class="products-grid" id="productsGrid">
            <?php if (!empty($motos)): ?>
                <?php foreach ($motos as $moto): ?>
                    <?php 
                    // Determinar el tipo de badge según disponibilidad
                    $badge_class = 'product-badge';
                    $badge_text = 'NUEVO';
                    
                    if (($moto['stock'] ?? 0) <= 2) {
                        $badge_text = 'ÚLTIMAS UNIDADES';
                    }
                    ?>
                    
                    <div class="product-card" 
                         data-marca="<?php echo strtolower($moto['marca'] ?? ''); ?>" 
                         data-cilindrada="<?php echo strtolower($moto['cilindrada'] ?? ''); ?>"
                         data-categoria="<?php echo strtolower($moto['categoria_nombre'] ?? ''); ?>"
                         data-anio="<?php echo $moto['anio'] ?? ''; ?>">
                        <?php if ($badge_text): ?>
                            <div class="<?php echo $badge_class; ?>"><?php echo $badge_text; ?></div>
                        <?php endif; ?>
                        
                            <?php if (!empty($moto['imagen_url'])): ?>
                                <img src="<?php echo htmlspecialchars($moto['imagen_url']); ?>" alt="<?php echo htmlspecialchars($moto['nombre']); ?>" class="product-image">
                            <?php else: ?>
                                <div class="product-image no-image" style="background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#666; font-size:14px;">Sin imagen</div>
                            <?php endif; ?>
                        
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($moto['nombre']); ?></h3>
                            
                            <div class="product-specs">
                                <span class="product-spec">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span><?php echo htmlspecialchars($moto['modelo'] ?? '200cc'); ?></span>
                                </span>
                                <span class="product-spec">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo htmlspecialchars($moto['anio'] ?? '2024'); ?></span>
                                </span>
                                <span class="product-spec">
                                    <i class="fas fa-road"></i>
                                    <span>0km</span>
                                </span>
                            </div>
                            
                            <div class="product-price">
                                <?php 
                                $precioOriginal = floatval($moto['precio_venta'] ?? 0);
                                $precioMostrar = floatval($moto['precio_real'] ?? $precioOriginal);
                                $descuento = 0;
                                if ($precioOriginal > 0 && $precioMostrar < $precioOriginal) {
                                    $descuento = round((($precioOriginal - $precioMostrar) / $precioOriginal) * 100);
                                }
                                $precios = formatearMonedaDual($precioMostrar);
                                $preciosOriginal = formatearMonedaDual($precioOriginal);
                                ?>
                                <?php if ($descuento > 0): ?>
                                    <span class="promo-badge-mini">-<?php echo $descuento; ?>%</span>
                                    <span style="text-decoration:line-through;color:#999;font-size:0.85em;"><?php echo $preciosOriginal['bs']; ?></span><br>
                                    <span style="color:#e74c3c;font-weight:700;font-size:1.1em;"><?php echo $precios['bs']; ?></span>
                                    <span class="moneda-usd"><?php echo $precios['usd']; ?></span>
                                <?php else: ?>
                                    <span class="moneda-bs"><?php echo $precios['bs']; ?></span>
                                    <span class="moneda-usd"><?php echo $precios['usd']; ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($moto['promocion']['nombre']) || !empty($moto['promo_nombre'])): ?>
                                <div style="font-size:0.75rem;color:#1F9166;margin-top:4px;">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($moto['promocion']['nombre'] ?? $moto['promo_nombre']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($moto['stock'])): ?>
                                <div class="product-stock">
                                    <i class="fas fa-check-circle"></i> 
                                    <?php echo $moto['stock']; ?> unidades disponibles
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-actions">
                                <a href="<?php echo BASE_URL; ?>/app/views/layouts/product_detail.php?id=<?php echo $moto['id']; ?>" 
                                   class="btn-view">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </a>
                                <button class="btn-cart" onclick="agregarAlCarrito(<?php echo $moto['id']; ?>)">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="padding:40px;">
                    <i class="fas fa-box-open" style="font-size:36px;color:#ccc;margin-bottom:10px;"></i>
                    <p>No hay motos disponibles en este momento.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- PAGINACIÓN - Generada dinámicamente -->
        <div class="pagination" id="paginationContainer">
            <!-- Los botones se generan con JavaScript según la cantidad real de productos -->
        </div>
    </main>

    <!-- Footer (reutilizar el mismo que inicio.php) -->
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
        const productosPorPagina = 6; // Mostrar 6 productos por página
        
        // Funcionalidad para filtros - filtrar en el DOM (sin API)
        function filtrarProductos() {
            const marca = document.getElementById('marca').value.toLowerCase();
            const cilindrada = document.getElementById('cilindrada').value.toLowerCase();
            const categoria = document.getElementById('categoria').value.toLowerCase();
            const anio = document.getElementById('anio').value;
            
            // Obtener todas las cards de productos
            const productos = document.querySelectorAll('.product-card');
            let productosVisibles = 0;
            
            productos.forEach(producto => {
                // Obtener atributos data del producto
                const productoMarca = producto.dataset.marca || '';
                const productoCilindrada = producto.dataset.cilindrada || '';
                const productoCategoria = producto.dataset.categoria || '';
                const productoAnio = producto.dataset.anio || '';
                
                // Verificar si cumple con los filtros
                let mostrar = true;
                
                if (marca && productoMarca !== marca) {
                    mostrar = false;
                }
                if (cilindrada && productoCilindrada.toLowerCase() !== cilindrada) {
                    mostrar = false;
                }
                if (categoria && productoCategoria.toLowerCase() !== categoria) {
                    mostrar = false;
                }
                // Filtro por año
                if (anio) {
                    if (anio === 'antiguo') {
                        // Mostrar solo los anteriores a 2020
                        if (productoAnio && parseInt(productoAnio) >= 2020) {
                            mostrar = false;
                        }
                    } else {
                        if (productoAnio !== anio) {
                            mostrar = false;
                        }
                    }
                }
                
                if (mostrar) {
                    producto.style.display = 'block';
                    productosVisibles++;
                } else {
                    producto.style.display = 'none';
                }
            });
            
            // Actualizar contador
            document.querySelector('.products-count strong').textContent = productosVisibles + ' motos';
            
            // Resetear paginación
            paginaActual = 1;
            actualizarPaginacion();
            mostrarPagina(paginaActual);
        }
        
        function mostrarProductosFiltrados(productos) {
            const grid = document.getElementById('productsGrid');
            grid.innerHTML = '';
            
            if (productos.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><p style="font-size: 18px; color: #666;">No se encontraron motos con los filtros seleccionados</p></div>';
                return;
            }
            
            // Aplicar promoción a cada producto
            productos.forEach(moto => {
                moto.precio_real = moto.precio_venta;
                moto.promocion = null;
                
                if (moto.promo_tipo_promocion && moto.promo_valor) {
                    const tipo = moto.promo_tipo_promocion.toLowerCase();
                    const valor = parseFloat(moto.promo_valor);
                    
                    if ((tipo === 'descuento' || tipo === 'porcentaje') && valor > 0 && valor <= 100) {
                        const precioPromo = moto.precio_venta * (1 - valor / 100);
                        if (precioPromo < moto.precio_venta) {
                            moto.precio_real = precioPromo;
                            moto.promocion = { nombre: moto.promo_nombre || 'Oferta especial', tipo: tipo, valor: valor };
                        }
                    }
                }
                
                const imagen = moto.imagen_url ? moto.imagen_url : 'https://via.placeholder.com/300x200?text=Sin+Imagen';
                const tasaCambio = window.TASA_CAMBIO || 35.50;
                
                // Usar precio_venta_bs si está disponible, si no calcular desde USD
                const precioBs = moto.precio_venta_bs ? parseFloat(moto.precio_venta_bs) : (parseFloat(moto.precio_real || 0) * tasaCambio);
                const precioOriginalBs = moto.precio_venta_bs ? parseFloat(moto.precio_venta_bs) : (parseFloat(moto.precio_venta || 0) * tasaCambio);
                
                // Precio USD: usar precio_venta_usd si está disponible
                const precioUsd = moto.precio_venta_usd ? parseFloat(moto.precio_venta_usd) : parseFloat(moto.precio_real || 0);
                
                const descuento = moto.promocion ? Math.round(moto.promocion.valor) : 0;
                
                const cardHtml = `
                    <div class="product-card" data-marca="${moto.marca || ''}" data-precio="${moto.precio_venta}">
                        ${moto.stock <= 0 ? '<div class="product-badge">Agotado</div>' : ''}
                        ${moto.promocion ? '<div class="product-badge promo-badge">-' + descuento + '%</div>' : ''}
                        <img src="${imagen}" alt="${moto.nombre}" class="product-image">
                        <div class="product-info">
                            <h3 class="product-title">${moto.nombre}</h3>
                            <div class="product-specs">
                                ${moto.marca ? `<div class="product-spec"><i class="fas fa-tag"></i> ${moto.marca}</div>` : ''}
                                ${moto.modelo ? `<div class="product-spec"><i class="fas fa-cog"></i> ${moto.modelo}</div>` : ''}
                                ${moto.anio ? `<div class="product-spec"><i class="fas fa-calendar"></i> ${moto.anio}</div>` : ''}
                            </div>
                            <div class="product-price">
                                ${descuento > 0 ? `
                                    <span style="text-decoration:line-through;color:#999;font-size:0.85em;">Bs ${precioOriginalBs.toFixed(0)}</span><br>
                                    <span style="color:#e74c3c;font-weight:700;font-size:1.1em;">Bs ${precioBs.toFixed(0)}</span>
                                    <span class="moneda-usd">($${precioUsd.toFixed(2)})</span>
                                ` : `
                                    <span class="moneda-bs">Bs ${precioBs.toFixed(0)}</span>
                                    <span class="moneda-usd">($${precioUsd.toFixed(2)})</span>
                                `}
                            </div>
                            ${moto.promocion ? `
                                <div style="font-size:0.75rem;color:#1F9166;margin-top:4px;">
                                    <i class="fas fa-tag"></i> ${moto.promocion.nombre}
                                </div>
                            ` : ''}
                            <div class="product-stock">${moto.stock > 0 ? '✓ En stock: ' + moto.stock + ' unidades' : 'Agotado'}</div>
                            <div class="product-actions">
                                <a href="<?php echo BASE_URL; ?>/app/views/layouts/product_detail.php?id=${moto.id}" class="btn-view">Ver Detalles</a>
                                <button class="btn-cart" onclick="agregarAlCarrito(${moto.id})">
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
            document.getElementById('marca').value = '';
            document.getElementById('cilindrada').value = '';
            document.getElementById('categoria').value = '';
            document.getElementById('anio').value = '';
            
            const productos = document.querySelectorAll('.product-card');
            productos.forEach(producto => {
                producto.style.display = 'block';
            });
            
            // Restaurar contador
            const contador = document.querySelector('.products-count strong');
            const totalProductos = document.querySelectorAll('.product-card').length;
            if (contador) {
                contador.textContent = totalProductos + ' motos';
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
            
            // Actualizar texto de la página actual
            const totalProductosVisibles = document.querySelectorAll('.product-card[style="display: block"]').length;
            const totalProductos = Array.from(productos).filter(p => p.style.display !== 'none').length;
            const totalPaginas = Math.ceil(totalProductos / productosPorPagina);
            
            // Actualizar contador
            const contadorElement = document.querySelector('.products-count strong');
            if (contadorElement) {
                contadorElement.textContent = `${totalProductosVisibles} de ${totalProductos} motos`;
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
                    // Generar botones dinámicamente
                    let paginationHTML = `
                        <button class="pagination-btn prev-btn" onclick="cambiarPagina(-1)" ${paginaActual === 1 ? 'disabled' : ''}>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                    `;
                    
                    // Generar botones de páginas
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
            
            // Actualizar botones prev/next
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
            // Simular que hay más productos para mostrar paginación
            const totalProductos = document.querySelectorAll('.product-card').length;
            if (totalProductos > productosPorPagina) {
                mostrarPagina(1);
                actualizarPaginacion();
            }
        });
        
        // Efectos hover en tarjetas
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.zIndex = '10';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.zIndex = '1';
            });
        });
        
        // Funcionalidad para botones "Ver Detalles"
        document.querySelectorAll('.btn-view').forEach((btn, index) => {
            btn.addEventListener('click', function(e) {
                // En un sistema real, esto redirigiría a la página de detalles
                // Por ahora solo mostramos un mensaje
                if (this.getAttribute('href') === '#') {
                    e.preventDefault();
                    const productoNombre = this.closest('.product-card').querySelector('.product-title').textContent;
                    const message = `Mostrando detalles de: ${productoNombre}\n\nEn el sistema real, esto abriría una página con todos los detalles del producto, fotos adicionales, especificaciones técnicas, etc.`;
                    if (window.Toast && typeof Toast.info === 'function') {
                        Toast.info(message, 'Ver detalles');
                    } else if (typeof showToast === 'function') {
                        showToast(message, 'info', 'Ver detalles');
                    } else {
                        alert(message);
                    }
                }
            });
        });
    </script>
</body>
</html>