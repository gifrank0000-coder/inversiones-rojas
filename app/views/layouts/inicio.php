<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
// Cargar productos reales para sliders
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../helpers/promocion_helper.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';

$featured = [];
try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio_venta, pi.imagen_url,
                       pr.tipo_promocion AS promo_tipo_promocion,
                       pr.valor AS promo_valor,
                       pr.nombre AS promo_nombre
                FROM productos p
                LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
                LEFT JOIN producto_promociones pp ON pp.producto_id = p.id
                LEFT JOIN promociones pr ON pr.id = pp.promocion_id
                    AND pr.estado = true
                    AND pr.fecha_inicio <= CURRENT_DATE
                    AND pr.fecha_fin >= CURRENT_DATE
                WHERE p.estado = true
                ORDER BY p.id DESC
                LIMIT 12";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($featured as &$f) {
            $f = aplicarPromocionAProducto($f);
        }
        unset($f);
        $featured = deduplicarProductosPorId($featured);
    }
} catch (Exception $e) {
    error_log('ERROR inicio.php: fallo al cargar productos: ' . $e->getMessage());
}

// Cargar productos para sliders por categoría (vehículos y grupos de productos)
$vehicles = [];
$products_group = [];
try {
    $db2 = new Database();
    $conn2 = $db2->getConnection();
    if ($conn2) {
        // Vehículos: SOLO los productos definidos en la tabla vehiculos
        $sqlV = "SELECT p.id, p.nombre, p.descripcion, p.precio_venta, pi.imagen_url,
                        v.marca, v.modelo, v.anio, v.cilindrada,
                        pr.tipo_promocion AS promo_tipo_promocion,
                        pr.valor AS promo_valor,
                        pr.nombre AS promo_nombre
                 FROM productos p
                 INNER JOIN vehiculos v ON v.producto_id = p.id
                 LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
                 LEFT JOIN producto_promociones pp ON pp.producto_id = p.id
                 LEFT JOIN promociones pr ON pr.id = pp.promocion_id
                     AND pr.estado = true
                     AND pr.fecha_inicio <= CURRENT_DATE
                     AND pr.fecha_fin >= CURRENT_DATE
                 WHERE p.estado = true
                 ORDER BY p.id DESC
                 LIMIT 12";
        $stmtV = $conn2->prepare($sqlV);
        $stmtV->execute();
        $vehicles = $stmtV->fetchAll(PDO::FETCH_ASSOC);
        foreach ($vehicles as &$v) {
            $v = aplicarPromocionAProducto($v);
        }
        unset($v);
        $vehicles = deduplicarProductosPorId($vehicles);

        // Productos: SOLO repuestos y accesorios, excluye motos
        $sqlP = "SELECT p.id, p.nombre, p.descripcion, p.precio_venta, pi.imagen_url, c.nombre AS categoria,
                        COALESCE(a.subtipo_accesorio, r.categoria_tecnica) AS tipo_producto,
                        pr.tipo_promocion AS promo_tipo_promocion,
                        pr.valor AS promo_valor,
                        pr.nombre AS promo_nombre
                 FROM productos p
                 LEFT JOIN accesorios a ON a.producto_id = p.id
                 LEFT JOIN repuestos r ON r.producto_id = p.id
                 LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
                 LEFT JOIN categorias c ON c.id = p.categoria_id
                 LEFT JOIN producto_promociones pp ON pp.producto_id = p.id
                 LEFT JOIN promociones pr ON pr.id = pp.promocion_id
                     AND pr.estado = true
                     AND pr.fecha_inicio <= CURRENT_DATE
                     AND pr.fecha_fin >= CURRENT_DATE
                 WHERE p.estado = true AND (a.producto_id IS NOT NULL OR r.producto_id IS NOT NULL)
                 ORDER BY p.id DESC
                 LIMIT 20";
        $stmtP = $conn2->prepare($sqlP);
        $stmtP->execute();
        $products_group = $stmtP->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products_group as &$p) {
            $p = aplicarPromocionAProducto($p);
        }
        unset($p);
        $products_group = deduplicarProductosPorId($products_group);
    }
} catch (Exception $e) {
    error_log('ERROR inicio.php: fallo al cargar sliders por categoría: ' . $e->getMessage());
}

// ── Cargar opciones dinámicas para el catálogo de búsqueda ─────────────────
$catalogo_anos    = [];
$catalogo_marcas  = [];
$catalogo_modelos = [];
$catalogo_cats    = [];
try {
    $dbc = new Database();
    $cc  = $dbc->getConnection();
    if ($cc) {
        // Años de vehículos con productos activos
        $r = $cc->query("SELECT DISTINCT v.anio FROM vehiculos v INNER JOIN productos p ON p.id = v.producto_id WHERE p.estado = true AND v.anio IS NOT NULL ORDER BY v.anio DESC");
        $catalogo_anos = $r->fetchAll(PDO::FETCH_COLUMN);

        // Marcas con productos activos
        $r = $cc->query("SELECT DISTINCT v.marca FROM vehiculos v INNER JOIN productos p ON p.id = v.producto_id WHERE p.estado = true AND v.marca IS NOT NULL AND v.marca <> '' ORDER BY v.marca ASC");
        $catalogo_marcas = $r->fetchAll(PDO::FETCH_COLUMN);

        // Modelos con productos activos
        $r = $cc->query("SELECT DISTINCT v.modelo FROM vehiculos v INNER JOIN productos p ON p.id = v.producto_id WHERE p.estado = true AND v.modelo IS NOT NULL AND v.modelo <> '' ORDER BY v.modelo ASC");
        $catalogo_modelos = $r->fetchAll(PDO::FETCH_COLUMN);

        // Categorías de productos activos
        $r = $cc->query("SELECT DISTINCT c.nombre FROM categorias c INNER JOIN productos p ON p.categoria_id = c.id WHERE p.estado = true AND c.nombre IS NOT NULL ORDER BY c.nombre ASC");
        $catalogo_cats = $r->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Exception $e) {
    error_log('ERROR inicio.php: fallo filtros catálogo: ' . $e->getMessage());
}
// Cargar promociones activas (para mostrar en home)
// Agrupar por promoción, no por producto - mostrar los detalles diferente según cantidad de productos
$productos_promocion = [];
try {
    $dbp = new Database();
    $connp = $dbp->getConnection();
    if ($connp) {
        // Query principal: obtener una fila por PROMOCIÓN (no por producto)
        // Incluye: nombre, descripción, valor, imagen, fecha vencimiento, COUNT de productos
        $sqlPromo = "SELECT pr.id as promo_id,
                            pr.nombre as promocion_nombre, 
                            pr.descripcion as promocion_descripcion,
                            pr.tipo_promocion as promo_tipo_promocion, 
                            pr.valor as promo_valor,
                            pr.fecha_inicio,
                            pr.fecha_fin,
                            pr.estado,
                            COALESCE(pr.imagen_url, '') as imagen_url,
                            COUNT(DISTINCT pp.producto_id) as cantidad_productos,
                            MIN(p.precio_venta) as precio_min,
                            MAX(p.precio_venta) as precio_max,
                            STRING_AGG(DISTINCT p.nombre, ', ') as nombres_productos
                     FROM promociones pr
                     LEFT JOIN producto_promociones pp ON pp.promocion_id = pr.id
                     LEFT JOIN productos p ON p.id = pp.producto_id
                     WHERE pr.estado = true
                       AND pr.fecha_inicio <= CURRENT_DATE
                       AND pr.fecha_fin >= CURRENT_DATE
                     GROUP BY pr.id, pr.nombre, pr.descripcion, pr.tipo_promocion, 
                              pr.valor, pr.fecha_inicio, pr.fecha_fin, pr.estado, pr.imagen_url
                     ORDER BY pr.fecha_fin ASC
                     LIMIT 8";
        $stmtPromo = $connp->prepare($sqlPromo);
        $stmtPromo->execute();
        $promociones_data = $stmtPromo->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar cada promoción para obtener imagen e info del producto representativo
        foreach ($promociones_data as &$promo) {
            // Si no hay imagen de promoción, obtener imagen del primer producto
            if (empty($promo['imagen_url'])) {
                $sqlImg = "SELECT pi.imagen_url 
                          FROM producto_promociones pp
                          INNER JOIN productos p ON p.id = pp.producto_id
                          LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
                          WHERE pp.promocion_id = ? 
                          LIMIT 1";
                $stmtImg = $connp->prepare($sqlImg);
                $stmtImg->execute([$promo['promo_id']]);
                $imgRow = $stmtImg->fetch(PDO::FETCH_ASSOC);
                if ($imgRow) {
                    $promo['imagen_url'] = $imgRow['imagen_url'] ?? '';
                }
            }
            
            // Para promociones de un solo producto, obtener sus detalles (precio antes/después)
            if ($promo['cantidad_productos'] == 1) {
                $sqlSingle = "SELECT p.id, p.nombre, p.descripcion, p.precio_venta
                             FROM producto_promociones pp
                             INNER JOIN productos p ON p.id = pp.producto_id
                             WHERE pp.promocion_id = ?";
                $stmtSingle = $connp->prepare($sqlSingle);
                $stmtSingle->execute([$promo['promo_id']]);
                $producto = $stmtSingle->fetch(PDO::FETCH_ASSOC);
                if ($producto) {
                    $promo['id'] = $producto['id'];
                    $promo['nombre'] = $producto['nombre'];
                    $promo['descripcion'] = $producto['descripcion'];
                    $promo['precio_venta'] = $producto['precio_venta'];
                    $promo = aplicarPromocionAProducto($promo);
                }
            }
        }
        unset($promo);
        $productos_promocion = $promociones_data;
    }
} catch (Exception $e) {
    error_log('ERROR inicio.php: fallo al cargar promociones: ' . $e->getMessage());
}

// Verificar si el usuario está logueado de forma segura
$usuario_logueado = false;
$user_name = '';
$user_rol = '';
$user_email = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    $usuario_logueado = true;
    $user_name = $_SESSION['user_name'];
    $user_rol = $_SESSION['user_rol'] ?? 'cliente'; // Valor por defecto
    $user_email = $_SESSION['user_email'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inversiones Rojas - Motos y Repuestos</title>
    <?php $base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>
    <script>var APP_BASE = '<?php echo $base_url; ?>';</script>
    <link rel="icon" href="<?php echo $base_url; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/pages/auth.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/admin.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/pages/home.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/components/user-panel.css">
    
    <style>
        .moneda-bs { color: #1F9166; font-weight: 700; }
        .moneda-usd { color: #6c757d; font-size: 0.85em; }
        
        /* Colores de la identidad visual */
        :root {
            --primary-color: #1F9166;
            --primary-dark: #156b4d;
            --accent-red: #e74c3c;
            --accent-orange: #f39c12;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
        }
        
        /* Estilos para slider de promociones */
        .main-promo-slider {
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            min-height: 520px;
        }
        /* TODOS los slides ocultos por defecto */
        .promo-slide {
            display: none;
            position: relative;
            animation: promoFadeIn 0.4s ease;
            min-height: 520px;
        }
        /* Solo el slide activo es visible */
        .promo-slide.active {
            display: flex;
        }
        @keyframes promoFadeIn {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        .promo-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1em;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .promo-price-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin: 10px 0;
        }
        .promo-price-old {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9em;
        }
        .promo-price-new {
            color: #e74c3c;
            font-weight: 700;
            font-size: 1.3em;
        }
        .promo-price-usd {
            color: #6c757d;
            font-size: 0.85em;
        }
        .promo-detail {
            color: #1F9166;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        /* Indicadores de slider */
        .promo-slider-dots {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }
        .promo-slider-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ccc;
            cursor: pointer;
            transition: all 0.3s;
        }
        .promo-slider-dot.active {
            background: #1F9166;
            transform: scale(1.2);
        }
        
        /* Estilos mejorados para sliders de productos y vehículos */
        .sliders-section {
            background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 40px 0;
        }
        .slider-container-custom {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .slider-header-custom {
            background: linear-gradient(135deg, #1F9166 0%, #2ecc71 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(31,145,102,0.3);
        }
        .producto-custom {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin: 0 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .producto-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #1F9166;
        }
        .producto-titulo-custom {
            color: #2c3e50;
            font-size: 0.95em;
            font-weight: 600;
            margin: 10px 0 5px;
        }
        .producto-descripcion-custom {
            color: #7f8c8d;
            font-size: 0.8em;
            margin-bottom: 8px;
        }
        .producto-precio-custom {
            font-size: 1.1em;
            margin: 8px 0;
        }
        .slider-control-custom {
            background: #1F9166;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 10;
            position: relative;
        }
        .slider-control-custom:hover {
            background: #156b4d;
            transform: scale(1.1);
        }
        .boton-detalles-custom {
            display: block;
            background: linear-gradient(135deg, #1F9166 0%, #2ecc71 100%);
            color: white;
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s;
        }
        .boton-detalles-custom:hover {
            background: linear-gradient(135deg, #156b4d 0%, #1F9166 100%);
        }

        /* ── Promo slide description — estilo vidriera profesional ── */
        .promo-slide-description {
          
            backdrop-filter: blur(4px);
        
            border-radius: 0 12px 12px 0;
            padding: 24px 26px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .promo-slide-description h3 {
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 10px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.5);
            line-height: 1.2;
        }
        .promo-product-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #2ecc71;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin: 0 0 8px;
        }
        .promo-descripcion {
            font-size: 0.88rem;
            color: #e0e0e0;
            line-height: 1.5;
            margin: 0 0 12px;
        }
        /* Estilo para imágenes de promociones - evitar que se vean cortadas */
        .promo-slide-image {
            flex: 1;
            min-height: 350px;
            position: relative;
            overflow: hidden;
            background: #2c2c2c;
        }
        .promo-slide-image img {
            width: 100%;
            height: 100%;
            
        }
        
        /* Precio anterior tachado */
        .promo-price-old {
            font-size: 0.88rem;
            color: #aaa;
            text-decoration: line-through;
            font-weight: 500;
        }
        /* Precio con oferta */
        .promo-price-new {
            font-size: 1.5rem;
            font-weight: 900;
            
            text-shadow: 0 1px 6px rgba(0,0,0,0.4);
            letter-spacing: -0.01em;
        }
        .promo-price-usd {
            font-size: 0.82rem;
            color: #adb5bd;
        }
        /* Multi-producto */
        .promo-multi-product {
            font-size: 1rem;
            color: #f8f9fa;
            margin: 0 0 8px;
            line-height: 1.4;
        }
        .promo-multi-product strong {
               color: #34eea8;

            font-size: 1.15rem;
        }
        .promo-availability {
         
          align-items: center;

    background: #1F9166;
    border: 1px solid rgba(46, 204, 113, 0.4);
    border-radius: 20px;
    padding: 6px 14px;
    color: white;
           
        }
        .promo-detail {
            color: #2ecc71;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 4px;
        }
        /* Botón ver producto dentro del slide */
        .promo-slide-description .btn-promo-ver {
            display: inline-block;
            margin-top: 14px;
            padding: 9px 20px;
            background: linear-gradient(135deg, #1F9166 0%, #2ecc71 100%);
            color: #fff;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 0.03em;
            box-shadow: 0 4px 14px rgba(31,145,102,0.35);
            transition: transform 0.2s, box-shadow 0.2s;
            align-self: flex-start;
        }
        .promo-slide-description .btn-promo-ver:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(31,145,102,0.5);
        }
                </style>
</head>
<body>
 

    <?php require __DIR__ . '/partials/header.php'; ?>
                   

    <!-- Hero Section con Texto Fijo y Slider -->
    <div class="hero-section">
        <div class="static-content">
            <h2 class="main-title">Encuentra lo que necesitas</h2>
            <h3 class="subtitle">Más de 4000 motos y repuestos disponibles en Inversiones Rojas</h3>
            <p class="description">Inversiones Rojas es tu concesionario de confianza con la mejor variedad de motos, repuestos y accesorios al mejor precio.</p>
            <a href="<?php echo BASE_URL; ?>/app/views/layouts/about.php" class="cta-button">Conoce más...</a>
        </div>
        
        <div class="hero-slider-container">
            <div class="hero-slider" id="heroSlider">
                <div class="hero-slide">
                    <img src="https://images.unsplash.com/photo-1609630875171-b1321377ee65?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Moto scooters">
                </div>
                <div class="hero-slide">
                    <img src="https://images.unsplash.com/photo-1517846693594-1567da72af75?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Moto custom">
                </div>
            </div>
            <div class="hero-slider-nav" id="heroSliderNav">
                <div class="hero-slider-dot active"></div>
                <div class="hero-slider-dot"></div>
            </div>
        </div>
    </div>

    <!-- Sección Catálogo -->
    <section class="catalog-section">
        <div class="catalog-container">
            <h2 class="section-title">Explora nuestro Catálogo</h2>
            <div class="filters-container">
                <!-- Año: dinámico desde vehiculos activos -->
                <div class="filter-box">
                    <select id="catalogo-year">
                        <option value="">Año</option>
                        <?php foreach ($catalogo_anos as $ano): ?>
                            <option value="<?php echo htmlspecialchars($ano); ?>"><?php echo htmlspecialchars($ano); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Marca: dinámica desde vehiculos activos -->
                <div class="filter-box">
                    <select id="catalogo-brand">
                        <option value="">Marca</option>
                        <?php foreach ($catalogo_marcas as $marca): ?>
                            <option value="<?php echo htmlspecialchars($marca); ?>"><?php echo htmlspecialchars($marca); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Modelo: dinámico desde vehiculos activos -->
                <div class="filter-box">
                    <select id="catalogo-model">
                        <option value="">Modelo</option>
                        <?php foreach ($catalogo_modelos as $modelo): ?>
                            <option value="<?php echo htmlspecialchars($modelo); ?>"><?php echo htmlspecialchars($modelo); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Categoría: dinámica desde categorías activas -->
                <div class="filter-box">
                    <select id="catalogo-category">
                        <option value="">Categoría</option>
                        <?php foreach ($catalogo_cats as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                        <!-- Fallback si no hay categorías en BD -->
                        <?php if (empty($catalogo_cats)): ?>
                            <option value="Motos">Motos</option>
                            <option value="Repuestos">Repuestos</option>
                            <option value="Accesorios">Accesorios</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            <button class="catalog-button" onclick="buscarCatalogo()">Buscar en Catálogo</button>
        </div>
    </section>

    <!-- Sección de Promociones Especiales -->
    <section class="promotions-section">
        <div class="container">
            <div class="promotions-header">
                <h2 class="promotions-title">PROMOCIONES ESPECIALES</h2>
            </div>
           
            <!-- Sección de Sliders de Promociones -->
            <div class="promo-sliders-container">
                <!-- Slider Principal dinámico: promociones activas -->
                <div class="main-promo-slider" id="main-promo-slider" style="min-height: 400px; display: block;">
                                     <?php if (!empty($productos_promocion)): ?>
                        <?php foreach ($productos_promocion as $idx => $pp): 
                            // Variables principales
                            $promoNombre = trim($pp['promocion_nombre'] ?? 'Promoción');
                            $prodNombre = trim($pp['nombre'] ?? '');
                            $descuento = intval($pp['promo_valor'] ?? 0);
                            $cantidadProductos = intval($pp['cantidad_productos'] ?? 1);
                            $imagenUrl = $pp['imagen_url'] ?? '';
                            
                            // Limpiar URL de imagen
                            if (!empty($imagenUrl)) {
                                if (strpos($imagenUrl, '/inversiones-rojas/') !== false) {
                                    $imagenUrl = substr($imagenUrl, strpos($imagenUrl, '/inversiones-rojas/') + strlen('/inversiones-rojas/'));
                                } elseif (strpos($imagenUrl, 'inversiones-rojas/') !== false) {
                                    $imagenUrl = substr($imagenUrl, strpos($imagenUrl, 'inversiones-rojas/') + strlen('inversiones-rojas/'));
                                }
                                $imagenUrl = BASE_URL . '/' . ltrim($imagenUrl, '/');
                            }
                            
                            // Para promociones de UN SOLO producto: mostrar precio antes/después
                            $esUnProducto = ($cantidadProductos == 1);
                            $productoUrl = '';
                            
                            if ($esUnProducto && isset($pp['id'])) {
                                $productoUrl = BASE_URL . '/app/views/layouts/product_detail.php?id=' . intval($pp['id']);
                            }
                            
                            if ($esUnProducto && isset($pp['precio_venta'])) {
                                $precioOriginal = floatval($pp['precio_venta'] ?? 0);
                                $precioConPromo = floatval($pp['precio_real'] ?? $precioOriginal);
                                $preciosOriginal = formatearMonedaDual($precioOriginal);
                                $preciosNuevo = formatearMonedaDual($precioConPromo);
                            }
                        ?>
                            <div class="promo-slide <?php echo $idx === 0 ? 'active' : ''; ?>">
                                <?php if ($esUnProducto && !empty($productoUrl)): ?>
                                <a href="<?php echo $productoUrl; ?>" class="promo-slide-image-link" style="display: block; text-decoration: none;">
                                <?php endif; ?>
                                    <div class="promo-slide-image">
                                        <?php if ($descuento > 0): ?>
                                            <span class="promo-badge">-<?php echo $descuento; ?>%</span>
                                        <?php endif; ?>
                                        <img src="<?php echo !empty($imagenUrl) ? htmlspecialchars($imagenUrl) : BASE_URL . '/public/img/default-promo.png'; ?>" alt="<?php echo htmlspecialchars($promoNombre); ?>">
                                    </div>
                                <?php if ($esUnProducto && !empty($productoUrl)): ?>
                                </a>
                                <?php endif; ?>
                                <div class="promo-slide-description">
                                    <h3><?php echo htmlspecialchars($promoNombre); ?></h3>
                                    
                                    <?php if ($esUnProducto): ?>
                                        <!-- Formato SIMPLE: 1 solo producto con precio antes/después -->
                                        <p class="promo-product-name"><?php echo htmlspecialchars($prodNombre); ?></p>
                                        <?php if (!empty($pp['promocion_descripcion'])): ?>
                                            <p class="promo-descripcion"><?php echo htmlspecialchars($pp['promocion_descripcion']); ?></p>
                                        <?php else: ?>
                                            <p class="promo-descripcion"><?php echo htmlspecialchars(substr($pp['descripcion'] ?? '', 0, 120)); ?>...</p>
                                        <?php endif; ?>
                                        <div class="promo-price-container">
                                            <span class="promo-price-old">Antes: <?php echo $preciosOriginal['bs']; ?> (<?php echo $preciosOriginal['usd']; ?>)</span>
                                            <span class="promo-price-new">Ahora: <?php echo $preciosNuevo['bs']; ?> (<?php echo $preciosNuevo['usd']; ?>)</span>
                                        </div>
                                    <?php else: ?>
                                        <!-- Formato MULTI-PRODUCTO: mostrar porcentaje y cantidad -->
                                        <p class="promo-multi-product">
                                            <strong><?php echo $descuento; ?>% menos</strong> en 
                                            <strong><?php echo $cantidadProductos; ?> productos distintos</strong>
                                        </p>
                                        <?php if (!empty($pp['promocion_descripcion'])): ?>
                                            <p class="promo-descripcion"><?php echo htmlspecialchars($pp['promocion_descripcion']); ?></p>
                                        <?php else: ?>
                                            <p class="promo-descripcion">Aprovecha esta promoción en una amplia variedad de productos. Vigencia: hasta el <?php echo date('d/m/Y', strtotime($pp['fecha_fin'])); ?></p>
                                        <?php endif; ?>
                                        <div class="promo-availability">
                                            <i class="fas fa-check-circle"></i> <strong>Disponible ya</strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="promo-slider-nav prev" data-slider="main-promo-slider">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        <div class="promo-slider-nav next" data-slider="main-promo-slider">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        <?php if (count($productos_promocion) > 1): ?>
                        <div class="promo-slider-dots">
                            <?php foreach ($productos_promocion as $idx => $pp): ?>
                                <div class="promo-slider-dot <?php echo $idx === 0 ? 'active' : ''; ?>" data-slide="<?php echo $idx; ?>"></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="promo-slide active">
                            <div class="promo-slide-image">
                                <img src="<?php echo BASE_URL; ?>/public/img/default-promo.png" alt="Sin promociones">
                            </div>
                            <div class="promo-slide-description">
                                <h3>No hay promociones activas</h3>
                                <p>Revisa esta sección más tarde para ver nuevas ofertas.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                

            </div>
            
            <!-- Galería de imágenes decorativas -->
            <div class="decorative-gallery">
                <div class="decorative-image">
                    <img src="<?php echo BASE_URL; ?>/public/img/imagen1.png" alt="Imagen decorativa 1">
                </div>
                <div class="decorative-image">
                    <img src="<?php echo BASE_URL; ?>/public/img/imagen2.png"  alt="Imagen decorativa 2">
                </div>
                <div class="decorative-image">
                    <img src="<?php echo BASE_URL; ?>/public/img/imagen3.png" alt="Imagen decorativa 3">
                </div>
                <div class="decorative-image">
                    <img src="<?php echo BASE_URL; ?>/public/img/imagen4.png"  alt="Imagen decorativa 4">
                </div>
            </div>
            
            <div class="promo-footer">
                <p>TENEMOS TODO LO QUE BUSCAS PARA TU MOTO</p>
            </div>
        </div>
    </section>

    <!-- Sección de Sliders de Productos y Vehículos -->
    <section class="sliders-section">
        <div class="container">
            <!-- Slider de Repuestos y Accesorios -->
            <div class="slider-container-custom">
                <div class="slider-header-custom"><i class="fas fa-tools"></i> REPUESTOS Y ACCESORIOS</div>
                
                <div class="slider-custom">
                    <div class="slider-control-custom prev-custom" id="prev-productos">
                        <i class="fas fa-chevron-left"></i>
                    </div>
                    
                    <div class="slider-track-custom" id="track-productos" style="display: flex; transition: transform 0.3s ease;">
                        <?php if (!empty($products_group)): ?>
                            <?php foreach ($products_group as $p): ?>
                                <div class="producto-custom">
                                    <div class="producto-imagen-custom">
                                        <?php if (!empty($p['imagen_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($p['imagen_url']); ?>" alt="<?php echo htmlspecialchars($p['nombre']); ?>" style="width:100%; height:140px; object-fit:contain; border-radius:6px; background:#f9f9f9;">
                                        <?php else: ?>
                                            <div style="width:100%; height:140px; border-radius:6px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#666; font-size:14px;">Sin imagen</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="producto-info-custom">
                                        <h3 class="producto-titulo-custom"><?php echo htmlspecialchars($p['nombre']); ?></h3>
                                        <p class="producto-descripcion-custom"><?php echo htmlspecialchars(substr($p['descripcion'] ?? '', 0, 80)); ?></p>
                                        <div class="producto-precio-custom">
                                            <?php 
                                            $precioMostrar = (isset($p['precio_real']) && $p['precio_real'] !== $p['precio_venta']) 
                                                ? ($p['precio_real'] ?? $p['precio_venta']) 
                                                : ($p['precio_venta'] ?? 0);
                                            $precios = formatearMonedaDual($precioMostrar);
                                            ?>
                                            <?php if (!isset($p['precio_real']) || $p['precio_real'] === $p['precio_venta']): ?>
                                                <div style="font-size: 1em; color: #1F9166; font-weight: 700;">
                                                    <?php echo $precios['bs']; ?> <span style="color: #6c757d; font-size: 0.85em;">(<?php echo $precios['usd']; ?>)</span>
                                                </div>
                                            <?php else: ?>
                                                <?php $preciosOriginal = formatearMonedaDual($p['precio_venta'] ?? 0); ?>
                                                <div style="font-size: 0.9em; color: #999; margin-bottom: 5px;">
                                                    <?php echo $preciosOriginal['bs']; ?> <span style="font-size: 0.85em;">(<?php echo $preciosOriginal['usd']; ?>)</span>
                                                </div>
                                                <div style="font-size: 1.05em; color: #e74c3c; font-weight: 700;">
                                                    <?php echo $precios['bs']; ?> <span style="color: #6c757d; font-size: 0.85em;">(<?php echo $precios['usd']; ?>)</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($p['promocion']['nombre']) || !empty($p['promo_nombre'])): ?>
                                            <div style="font-size:0.75rem;color:#1F9166;margin-top:4px;">
                                                <?php echo htmlspecialchars($p['promocion']['nombre'] ?? $p['promo_nombre'] ?? 'Promoción activa'); ?>
                                            </div>
                                        <?php endif; ?>
                                        <a href="<?php echo (defined('BASE_URL') ? BASE_URL : '') . '/app/views/layouts/product_detail.php?id=' . $p['id']; ?>" class="boton-detalles-custom">Detalles</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="producto-custom">
                                <div class="producto-imagen-custom" style="display:flex;align-items:center;justify-content:center;color:#666;font-size:14px;">
                                    No hay productos para mostrar en este slider
                                </div>
                                <div class="producto-info-custom">
                                    <h3 class="producto-titulo-custom">Sin resultados</h3>
                                    <p class="producto-descripcion-custom">No se encontraron productos activos que coincidan con este criterio.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="slider-control-custom next-custom" id="next-productos">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </div>
            
            <!-- Slider de Mejores Vehículos -->
            <div class="slider-container-custom">
                <div class="slider-header-custom"><i class="fas fa-motorcycle"></i> MOTOS Y VEHÍCULOS</div>
                
                <div class="slider-custom">
                    <div class="slider-control-custom prev-custom" id="prev-vehiculos">
                        <i class="fas fa-chevron-left"></i>
                    </div>
                    
                    <div class="slider-track-custom" id="track-vehiculos" style="display: flex; transition: transform 0.3s ease;">
                        <?php if (!empty($vehicles)): ?>
                            <?php foreach ($vehicles as $v): ?>
                                <div class="producto-custom">
                                    <div class="producto-imagen-custom">
                                        <?php if (!empty($v['imagen_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($v['imagen_url']); ?>" alt="<?php echo htmlspecialchars($v['nombre']); ?>" style="width:100%; height:160px; object-fit:contain; border-radius:6px; background:#f9f9f9;">
                                        <?php else: ?>
                                            <div style="width:100%; height:160px; border-radius:6px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#666; font-size:14px;">Sin imagen</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="producto-info-custom">
                                        <h3 class="producto-titulo-custom"><?php echo htmlspecialchars($v['nombre']); ?></h3>
                                        <div class="producto-precio-custom">
                                            <?php 
                                            $precioMostrar = (isset($v['precio_real']) && $v['precio_real'] !== $v['precio_venta']) 
                                                ? ($v['precio_real'] ?? $v['precio_venta']) 
                                                : ($v['precio_venta'] ?? 0);
                                            $precios = formatearMonedaDual($precioMostrar);
                                            ?>
                                            <?php if (!isset($v['precio_real']) || $v['precio_real'] === $v['precio_venta']): ?>
                                                <div style="font-size: 1em; color: #1F9166; font-weight: 700;">
                                                    <?php echo $precios['bs']; ?> <span style="color: #6c757d; font-size: 0.85em;">(<?php echo $precios['usd']; ?>)</span>
                                                </div>
                                            <?php else: ?>
                                                <?php $preciosOriginal = formatearMonedaDual($v['precio_venta'] ?? 0); ?>
                                                <div style="font-size: 0.9em; color: #999; margin-bottom: 5px;">
                                                    <?php echo $preciosOriginal['bs']; ?> <span style="font-size: 0.85em;">(<?php echo $preciosOriginal['usd']; ?>)</span>
                                                </div>
                                                <div style="font-size: 1.05em; color: #e74c3c; font-weight: 700;">
                                                    <?php echo $precios['bs']; ?> <span style="color: #6c757d; font-size: 0.85em;">(<?php echo $precios['usd']; ?>)</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($v['promocion']['nombre']) || !empty($v['promo_nombre'])): ?>
                                            <div style="font-size:0.75rem;color:#1F9166;margin-top:4px;">
                                                <?php echo htmlspecialchars($v['promocion']['nombre'] ?? $v['promo_nombre'] ?? 'Promoción activa'); ?>
                                            </div>
                                        <?php endif; ?>
                                        <a href="<?php echo (defined('BASE_URL') ? BASE_URL : '') . '/app/views/layouts/product_detail.php?id=' . $v['id']; ?>" class="boton-detalles-custom">Detalles</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="producto-custom">
                                <div class="producto-imagen-custom">Imagen de Bera Br 200</div>
                                <div class="producto-info-custom">
                                    <h3 class="producto-titulo-custom">Bera Br 200 (2021)</h3>
                                    <div class="producto-precio-custom">1,820 $</div>
                                    <a href="#" class="boton-detalles-custom">Detalles</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="slider-control-custom next-custom" id="next-vehiculos">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
                <a href="<?php echo BASE_URL; ?>/app/views/layouts/motos.php">Motos</a>
                <a href="<?php echo BASE_URL; ?>/app/views/layouts/repuestos.php">Repuestos</a>
                <a href="<?php echo BASE_URL; ?>/app/views/layouts/contacto.php">Contacto</a>
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
    <script src="<?php echo BASE_URL; ?>/public/js/inv-notifications.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/components/user-panel.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/script.js"></script>
    
    <script>
        const baseUrl = document.body.getAttribute('data-base-url') || '<?php echo BASE_URL; ?>';
        
        function buscarCatalogo() {
            const year     = document.getElementById('catalogo-year').value.trim();
            const brand    = document.getElementById('catalogo-brand').value.trim();
            const model    = document.getElementById('catalogo-model').value.trim();
            const category = document.getElementById('catalogo-category').value.trim();

            if (!year && !brand && !model && !category) {
                // Si ningún filtro → redirigir a búsqueda de todos los productos
                window.location.href = baseUrl + '/app/views/layouts/search-results.php?q=';
                return;
            }

            const params = new URLSearchParams();

            // Determinar tipo según categoría seleccionada (insensible a mayúsculas)
            const catLow = category.toLowerCase();
            if (catLow.includes('moto') || catLow.includes('veh')) params.set('type', 'motos');
            else if (catLow.includes('repuesto'))                   params.set('type', 'repuestos');
            else if (catLow.includes('accesorio'))                  params.set('type', 'accesorios');
            else                                                     params.set('type', 'all');

            if (year)     params.set('year',     year);
            if (brand)    params.set('brand',    brand);
            if (model)    params.set('model',    model);
            if (category) params.set('category', category);

            window.location.href = baseUrl + '/app/views/layouts/search-results.php?' + params.toString();
        }

        // Slider de promociones
        document.addEventListener('DOMContentLoaded', function() {
            const slider = document.getElementById('main-promo-slider');
            if (slider) {
                const slides = slider.querySelectorAll('.promo-slide');
                const prevBtn = slider.querySelector('.promo-slider-nav.prev');
                const nextBtn = slider.querySelector('.promo-slider-nav.next');
                const dots = slider.querySelectorAll('.promo-slider-dot');
                
                console.log('Slider debug: slides encontrados:', slides.length);
                console.log('Slider debug: dots encontrados:', dots.length);
                
                let currentSlide = 0;
                let autoSlideInterval = null;
                let isHovering = false;

                function showSlide(index) {
                    slides.forEach((slide, i) => {
                        slide.classList.toggle('active', i === index);
                    });
                    dots.forEach((dot, i) => {
                        dot.classList.toggle('active', i === index);
                    });
                }

                slider.addEventListener('mouseenter', () => {
                    isHovering = true;
                    if (autoSlideInterval) {
                        clearInterval(autoSlideInterval);
                        autoSlideInterval = null;
                    }
                });

                slider.addEventListener('mouseleave', () => {
                    isHovering = false;
                    resetAutoSlide();
                });

                if (prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
                        showSlide(currentSlide);
                        resetAutoSlide();
                    });
                }

                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        currentSlide = (currentSlide + 1) % slides.length;
                        showSlide(currentSlide);
                        resetAutoSlide();
                    });
                }

                // Dots click
                dots.forEach((dot, idx) => {
                    dot.addEventListener('click', () => {
                        currentSlide = idx;
                        showSlide(currentSlide);
                        resetAutoSlide();
                    });
                });

                function resetAutoSlide() {
                    if (autoSlideInterval) {
                        clearInterval(autoSlideInterval);
                    }
                    if (slides.length > 1 && !isHovering) {
                        autoSlideInterval = setInterval(() => {
                            currentSlide = (currentSlide + 1) % slides.length;
                            showSlide(currentSlide);
                        }, 10000);
                    }
                }

                // Auto slide cada 10 segundos (más tiempo para leer)
                if (slides.length > 1) {
                    resetAutoSlide();
                }
            }

            // Sliders de productos y vehículos
            function initCustomSlider(trackId, prevId, nextId) {
                const track = document.getElementById(trackId);
                const prevBtn = document.getElementById(prevId);
                const nextBtn = document.getElementById(nextId);
                
                if (!track || !prevBtn || !nextBtn) return;
                
                let currentPosition = 0;
                const itemWidth = 220; // ancho aproximado de cada item
                const visibleItems = 4; // items visibles
                const totalItems = track.children.length;
                const maxPosition = Math.max(0, -(totalItems - visibleItems) * itemWidth);
                
                prevBtn.addEventListener('click', () => {
                    currentPosition = Math.min(currentPosition + itemWidth, 0);
                    track.style.transform = `translateX(${currentPosition}px)`;
                });
                
                nextBtn.addEventListener('click', () => {
                    currentPosition = Math.max(currentPosition - itemWidth, -maxPosition);
                    track.style.transform = `translateX(${currentPosition}px)`;
                });

            }
            
            initCustomSlider('track-productos', 'prev-productos', 'next-productos');
            initCustomSlider('track-vehiculos', 'prev-vehiculos', 'next-vehiculos');
        });
    </script>
</body>
</html>