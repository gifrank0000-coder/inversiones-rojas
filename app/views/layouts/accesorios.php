<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
// Cargar productos de la categoría accesorios
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../helpers/promocion_helper.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';

$accesorios = [];
try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        // Cargar todos los accesorios enlazados en la tabla accesorios
        $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio_venta, p.precio_venta_bs, p.precio_venta_usd, pi.imagen_url, 
                       p.stock_actual AS stock, c.nombre as categoria,
                       a.subtipo_accesorio, a.talla, a.color, a.material, a.marca as marca_accesorio,
                       pr.nombre as promo_promocion_nombre, pr.tipo_promocion as promo_tipo_promocion, pr.valor as promo_valor
                FROM productos p
                INNER JOIN accesorios a ON a.producto_id = p.id
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
        $accesorios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($accesorios as &$accesorio) {
            $accesorio = aplicarPromocionAProducto($accesorio);
        }
        unset($accesorio);
        $accesorios = deduplicarProductosPorId($accesorios);
    }
} catch (Exception $e) {
    error_log('ERROR accesorios.php: fallo al cargar accesorios: ' . $e->getMessage());
}

// Obtener filtros reales de la base de datos para accesorios
$tipos_accesorios = [];
$tallas_accesorios = [];
$colores_accesorios = [];
$marcas_accesorios = [];

try {
    $conn = $db->getConnection();
    if ($conn) {
        // Subtipos de accesorios
        $stmtTipos = $conn->query("
            SELECT DISTINCT a.subtipo_accesorio 
            FROM accesorios a
            INNER JOIN productos p ON p.id = a.producto_id
            WHERE p.estado = true AND a.subtipo_accesorio IS NOT NULL AND a.subtipo_accesorio != ''
            ORDER BY a.subtipo_accesorio
        ");
        $tipos_accesorios = $stmtTipos->fetchAll(PDO::FETCH_COLUMN);
        
        // Tallas
        $stmtTallas = $conn->query("
            SELECT DISTINCT a.talla 
            FROM accesorios a
            INNER JOIN productos p ON p.id = a.producto_id
            WHERE p.estado = true AND a.talla IS NOT NULL AND a.talla != ''
            ORDER BY a.talla
        ");
        $tallas_accesorios = $stmtTallas->fetchAll(PDO::FETCH_COLUMN);
        
        // Colores - dividir por coma ya que pueden estar juntos (ej: "Negro,Rojo,Azul")
        $stmtColores = $conn->query("
            SELECT DISTINCT a.color 
            FROM accesorios a
            INNER JOIN productos p ON p.id = a.producto_id
            WHERE p.estado = true AND a.color IS NOT NULL AND a.color != ''
            ORDER BY a.color
        ");
        $colores_raw = $stmtColores->fetchAll(PDO::FETCH_COLUMN);
        // Separar colores por coma y eliminar duplicados
        $colores_unicos = [];
        foreach ($colores_raw as $colorStr) {
            $colores = explode(',', $colorStr);
            foreach ($colores as $c) {
                $c = trim($c);
                if ($c && !in_array(strtolower($c), $colores_unicos)) {
                    $colores_unicos[] = strtolower($c);
                }
            }
        }
        sort($colores_unicos);
        $colores_accesorios = $colores_unicos;
        
        // Marcas
        $stmtMarcas = $conn->query("
            SELECT DISTINCT LOWER(TRIM(a.marca)) as marca_min
            FROM accesorios a
            INNER JOIN productos p ON p.id = a.producto_id
            WHERE p.estado = true AND a.marca IS NOT NULL AND a.marca != ''
            ORDER BY LOWER(TRIM(a.marca))
        ");
        $marcas_accesorios = $stmtMarcas->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Exception $e) {
    error_log('ERROR accesorios.php: fallo al cargar filtros: ' . $e->getMessage());
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
    <title>Accesorios - Inversiones Rojas</title>
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
        
        /* Estilos específicos para la página de accesorios */
        .accesorios-page {
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
        
        .product-category {
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

    <!-- Contenido principal de la página de accesorios -->
    <main class="accesorios-page container">
        <!-- Título y descripción -->
        <div class="page-title">
            <h1><i class="fas fa-helmet-safety"></i> ACCESORIOS PARA MOTOS</h1>
            <p>Encuentra los mejores accesorios para complementar tu moto y garantizar tu seguridad</p>
        </div>
        
        <!-- Contador de productos -->
        <div class="products-count">
            Mostrando <strong><?php echo count($accesorios); ?> accesorios</strong> disponibles
        </div>
        
        <!-- Filtros -->
        <div class="catalog-filters">
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="categoria"><i class="fas fa-tag"></i> Tipo</label>
                    <select id="categoria">
                        <option value="">Todos los tipos</option>
                        <?php foreach ($tipos_accesorios as $tipo): ?>
                            <option value="<?php echo strtolower(htmlspecialchars($tipo)); ?>">
                                <?php echo htmlspecialchars($tipo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="talla"><i class="fas fa-ruler"></i> Talla</label>
                    <select id="talla">
                        <option value="">Todas las tallas</option>
                        <?php foreach ($tallas_accesorios as $talla): ?>
                            <option value="<?php echo htmlspecialchars($talla); ?>">
                                <?php echo htmlspecialchars($talla); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="color"><i class="fas fa-palette"></i> Color</label>
                    <select id="color">
                        <option value="">Todos los colores</option>
                        <?php foreach ($colores_accesorios as $color): ?>
                            <option value="<?php echo strtolower(htmlspecialchars($color)); ?>">
                                <?php echo htmlspecialchars(ucfirst($color)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="marca"><i class="fas fa-trademark"></i> Marca</label>
                    <select id="marca">
                        <option value="">Todas las marcas</option>
                        <?php foreach ($marcas_accesorios as $marca): ?>
                            <option value="<?php echo htmlspecialchars($marca); ?>">
                                <?php echo htmlspecialchars(ucfirst($marca)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="filter-actions">
                <button class="filter-btn" onclick="filtrarProductos()">
                    <i class="fas fa-search"></i> Filtrar Accesorios
                </button>
                <button class="filter-btn reset" onclick="limpiarFiltros()">
                    <i class="fas fa-redo"></i> Limpiar Filtros
                </button>
            </div>
        </div>
        
        <!-- Grid de productos (estilo e-commerce) -->
        <div class="products-grid" id="productsGrid">
            <?php if (!empty($accesorios)): ?>
                <?php foreach ($accesorios as $accesorio): ?>
                    <?php 
                    // Determinar el tipo de badge según disponibilidad
                    $badge_class = 'product-badge';
                    $badge_text = 'NUEVO';
                    
                    if (($accesorio['stock'] ?? 0) <= 3) {
                        $badge_text = 'ÚLTIMAS UNIDADES';
                    }
                    
                    // Determinar atributos para filtros
                    $tipo = strtolower($accesorio['subtipo_accesorio'] ?? $accesorio['categoria'] ?? '');
                    $talla = $accesorio['talla'] ?? '';
                    $color = strtolower($accesorio['color'] ?? '');
                    $marca = strtolower($accesorio['marca_accesorio'] ?? '');
                    ?>
                    
                    <div class="product-card" 
                         data-tipo="<?php echo $tipo; ?>" 
                         data-talla="<?php echo htmlspecialchars($talla); ?>"
                         data-color="<?php echo $color; ?>"
                         data-marca="<?php echo $marca; ?>"
                         data-stock="<?php echo $accesorio['stock'] ?? 0; ?>">
                        <?php if ($badge_text): ?>
                            <div class="<?php echo $badge_class; ?>"><?php echo $badge_text; ?></div>
                        <?php endif; ?>
                        
                            <?php if (!empty($accesorio['imagen_url'])): ?>
                                <img src="<?php echo htmlspecialchars($accesorio['imagen_url']); ?>" alt="<?php echo htmlspecialchars($accesorio['nombre']); ?>" class="product-image">
                            <?php else: ?>
                                <div class="product-image no-image" style="background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#666; font-size:14px;">Sin imagen</div>
                            <?php endif; ?>
                        
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($accesorio['nombre']); ?></h3>
                            
                            <div class="product-category">
                                <i class="fas fa-tag"></i> 
                                <?php echo htmlspecialchars($accesorio['categoria'] ?? 'Accesorio'); ?>
                            </div>
                            
                            <div class="product-price">
                                <?php 
                                $precioOriginal = floatval($accesorio['precio_venta'] ?? 0);
                                $precioMostrar = floatval($accesorio['precio_real'] ?? $precioOriginal);
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
                            
                            <?php if (!empty($accesorio['promocion']['nombre']) || !empty($accesorio['promo_nombre'])): ?>
                                <div style="font-size:0.75rem;color:#1F9166;margin-top:4px;">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($accesorio['promocion']['nombre'] ?? $accesorio['promo_nombre']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($accesorio['stock'])): ?>
                                <div class="product-stock">
                                    <i class="fas fa-check-circle"></i> 
                                    <?php echo $accesorio['stock']; ?> unidades disponibles
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-actions">
                                <a href="<?php echo BASE_URL; ?>/app/views/layouts/product_detail.php?id=<?php echo $accesorio['id']; ?>" 
                                   class="btn-view">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </a>
                                <button class="btn-cart" onclick="agregarAlCarrito(<?php echo $accesorio['id']; ?>, event)">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state" style="padding:40px;">
                    <i class="fas fa-box-open" style="font-size:36px;color:#ccc;margin-bottom:10px;"></i>
                    <p>No hay accesorios disponibles en este momento.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- PAGINACIÓN - Generada dinámicamente -->
        <div class="pagination" id="paginationContainer">
            <!-- Los botones se generan con JavaScript según la cantidad real de productos -->
        </div>
    </main>
            </button>
        </div>
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
            
            // Verificar si el item ya existe
            const existingItem = cartItems.querySelector(`.cart-drawer-item[data-id="${id}"]`);
            if (existingItem) {
                // Actualizar cantidad
                const meta = existingItem.querySelector('.cart-drawer-meta small');
                if (meta) {
                    const currentQty = parseInt(meta.textContent.split(' × ')[0]) || 1;
                    meta.innerHTML = `${cantidad} × <span class="moneda-usd">${precioUSDStr}</span> <span class="moneda-bs">${precioBSStr}</span>`;
                }
                return;
            }
            
            // Agregar nuevo item
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
        
        // Funcionalidad para filtros
        function filtrarProductos() {
            const tipo = document.getElementById('categoria').value.toLowerCase();
            const talla = document.getElementById('talla').value;
            const color = document.getElementById('color').value.toLowerCase();
            const marca = document.getElementById('marca').value.toLowerCase();
            
            // Obtener todas las cards de productos
            const productos = document.querySelectorAll('.product-card');
            let productosVisibles = 0;
            
            productos.forEach(producto => {
                const productoTipo = producto.dataset.tipo || '';
                const productoTalla = producto.dataset.talla || '';
                const productoColor = producto.dataset.color || '';
                const productoMarca = producto.dataset.marca || '';
                
                let mostrar = true;
                
                if (tipo && productoTipo !== tipo) {
                    mostrar = false;
                }
                if (talla && productoTalla !== talla) {
                    mostrar = false;
                }
                if (color && productoColor !== color) {
                    mostrar = false;
                }
                if (marca && productoMarca !== marca) {
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
            document.querySelector('.products-count strong').textContent = productosVisibles + ' accesorios';
            
            // Resetear paginación
            paginaActual = 1;
            actualizarPaginacion();
            mostrarPagina(paginaActual);
        }
        
        function mostrarProductosFiltrados(productos) {
            const grid = document.getElementById('productsGrid');
            grid.innerHTML = '';
            
            if (productos.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><p style="font-size: 18px; color: #666;">No se encontraron accesorios con los filtros seleccionados</p></div>';
                return;
            }
            
            productos.forEach(accesorio => {
                const imagen = accesorio.imagen_url ? accesorio.imagen_url : 'https://via.placeholder.com/300x200?text=Sin+Imagen';
                const cardHtml = `
                    <div class="product-card" data-categoria="${accesorio.categoria || ''}" data-precio="${accesorio.precio_venta}">
                        ${accesorio.stock <= 0 ? '<div class="product-badge">Agotado</div>' : ''}
                        <img src="${imagen}" alt="${accesorio.nombre}" class="product-image">
                        <div class="product-info">
                            <h3 class="product-title">${accesorio.nombre}</h3>
                            <div class="product-category">${accesorio.categoria || ''}</div>
                            <div class="product-price">$${parseFloat(accesorio.precio_venta).toLocaleString('es-VE', {minimumFractionDigits: 2})}</div>
                            <div class="product-stock">${accesorio.stock > 0 ? '✓ En stock: ' + accesorio.stock + ' unidades' : 'Agotado'}</div>
                            <div class="product-actions">
                                <a href="<?php echo BASE_URL; ?>/app/views/layouts/product_detail.php?id=${accesorio.id}" class="btn-view">Ver Detalles</a>
                                <button class="btn-cart" onclick="agregarAlCarrito(${accesorio.id}, event)">
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
            document.getElementById('talla').value = '';
            document.getElementById('color').value = '';
            document.getElementById('marca').value = '';
            
            const productos = document.querySelectorAll('.product-card');
            productos.forEach(producto => {
                producto.style.display = 'block';
            });
            
            // Restaurar contador
            const contador = document.querySelector('.products-count');
            const totalProductos = document.querySelectorAll('.product-card').length;
            if (contador) {
                contador.innerHTML = `Mostrando <strong>${totalProductos} accesorios</strong> disponibles`;
            }
            
            // Resetear paginación
            paginaActual = 1;
            actualizarPaginacion();
            mostrarPagina(paginaActual);
        }
        
        // Función para agregar al carrito
        async function agregarAlCarrito(productoId, event) {
            if (!<?php echo isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                if (window.Toast && typeof Toast.warning === 'function') {
                    Toast.warning('Debes iniciar sesión para usar el carrito', 'Acceso restringido', 5000);
                }
                return;
            }
            
            const productoCard = event?.target?.closest('.product-card');
            const productoNombre = productoCard?.querySelector('.product-title')?.textContent || '';
            const productoPrecio = productoCard?.querySelector('.product-price')?.textContent || '';
            const productoImagen = productoCard?.querySelector('img.product-image')?.src || '';

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
            
            const contadorElement = document.querySelector('.products-count');
            if (contadorElement) {
                contadorElement.innerHTML = `Mostrando <strong>${totalProductosVisibles} accesorios</strong> disponibles`;
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