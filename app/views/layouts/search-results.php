<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../helpers/moneda_helper.php';
require_once __DIR__ . '/../../helpers/promocion_helper.php';

$base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// Obtener parámetros de búsqueda
$type = $_GET['type'] ?? 'all';
$year = $_GET['year'] ?? '';
$brand = $_GET['brand'] ?? '';
$model = $_GET['model'] ?? '';
$category = $_GET['category'] ?? '';
$q = $_GET['q'] ?? '';

$resultados = [];
$search_active = !empty($type) || !empty($year) || !empty($brand) || !empty($model) || !empty($category) || !empty($q);

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        // Construir consulta según los filtros
        $sql = "SELECT p.*, 
                       COALESCE(pi.imagen_url, '') as imagen_url,
                       c.nombre as categoria_nombre,
                       v.marca, v.modelo, v.anio, v.cilindrada,
                       a.subtipo_accesorio,
                       r.categoria_tecnica,
                       pr.tipo_promocion as promo_tipo_promocion,
                       pr.valor as promo_valor,
                       pr.nombre as promo_nombre
                FROM productos p
                LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
                LEFT JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN vehiculos v ON v.producto_id = p.id
                LEFT JOIN accesorios a ON a.producto_id = p.id
                LEFT JOIN repuestos r ON r.producto_id = p.id
                LEFT JOIN producto_promociones pp ON pp.producto_id = p.id
                LEFT JOIN promociones pr ON pr.id = pp.promocion_id
                    AND pr.estado = true
                    AND pr.fecha_inicio <= CURRENT_DATE
                    AND pr.fecha_fin >= CURRENT_DATE
                WHERE p.estado = true";
        
        $params = [];
        
        // Filtro por tipo
        if ($type === 'motos') {
            $sql .= " AND v.producto_id IS NOT NULL";
        } elseif ($type === 'repuestos') {
            $sql .= " AND r.producto_id IS NOT NULL";
        } elseif ($type === 'accesorios') {
            $sql .= " AND a.producto_id IS NOT NULL";
        }
        
        // Filtros adicionales
        if (!empty($brand)) {
            $sql .= " AND LOWER(v.marca) = LOWER(:brand)";
            $params[':brand'] = $brand;
        }
        
        if (!empty($model)) {
            $sql .= " AND LOWER(v.modelo) = LOWER(:model)";
            $params[':model'] = $model;
        }
        
        if (!empty($year)) {
            $sql .= " AND v.anio = :year";
            $params[':year'] = $year;
        }
        
        if (!empty($category)) {
            $sql .= " AND LOWER(c.nombre) = LOWER(:category)";
            $params[':category'] = $category;
        }
        
        if (!empty($q)) {
            $sql .= " AND (p.nombre ILIKE :q OR p.codigo_interno ILIKE :q OR p.descripcion ILIKE :q)";
            $params[':q'] = "%$q%";
        }
        
        $sql .= " ORDER BY p.id DESC LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($resultados as &$prod) {
            $prod = aplicarPromocionAProducto($prod);
        }
    }
} catch (Exception $e) {
    error_log('Error en search-results.php: ' . $e->getMessage());
}

// Construir texto descriptivo de los filtros aplicados
$filtros_aplicados = [];
if (!empty($q)) $filtros_aplicados[] = "Búsqueda: \"$q\"";
if (!empty($category)) $filtros_aplicados[] = "Categoría: $category";
if (!empty($brand)) $filtros_aplicados[] = "Marca: $brand";
if (!empty($model)) $filtros_aplicados[] = "Modelo: $model";
if (!empty($year)) $filtros_aplicados[] = "Año: $year";
if ($type !== 'all' && empty($category)) {
    $tipo_texto = ['motos' => 'Motos', 'repuestos' => 'Repuestos', 'accesorios' => 'Accesorios'];
    $filtros_aplicados[] = "Tipo: " . ($tipo_texto[$type] ?? $type);
}
$filtros_texto = !empty($filtros_aplicados) ? implode(' • ', $filtros_aplicados) : 'Todos los productos';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Búsqueda - Inversiones Rojas</title>
    <script>var APP_BASE = '<?php echo $base_url; ?>';</script>
    <link rel="icon" href="<?php echo $base_url; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/layouts/inicio.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/components/product-cards.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/components/user-panel.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/layouts/inicio.css">
<link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/components/product-cards.css">
    <style>
        .moneda-bs { color: #1F9166; font-weight: 700; }
        .moneda-usd { color: #6c757d; font-size: 0.85em; }
        
        /* Estilos específicos para resultados de búsqueda */
        .search-results-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: 60vh;
        }
        
        .search-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .search-title {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-title i {
            color: #1F9166;
            font-size: 1.6rem;
        }
        
        .search-subtitle {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .filters-badge {
            display: inline-block;
            background: #f0faf5;
            color: #1F9166;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-top: 10px;
        }
        
        .clear-filters-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f8f9fa;
            color: #e74c3c;
            border: 1px solid #e74c3c;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            margin-left: 15px;
        }
        
        .clear-filters-btn:hover {
            background: #e74c3c;
            color: white;
            transform: translateY(-2px);
        }
        
        .results-count {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 25px;
        }
        
        .results-count strong {
            color: #1F9166;
            font-size: 1.1rem;
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .result-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .result-image {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            padding: 20px;
            position: relative;
        }
        
        .promo-badge-mini {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        
        .result-image img {
            max-width: 100%;
            max-height: 140px;
            object-fit: contain;
        }
        
        .result-info {
            padding: 15px;
        }
        
        .result-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .result-category {
            font-size: 0.7rem;
            color: #1F9166;
            margin-bottom: 8px;
            display: inline-block;
            background: #e8f6f1;
            padding: 3px 10px;
            border-radius: 15px;
        }
        
        .result-code {
            font-size: 0.7rem;
            color: #95a5a6;
            margin-bottom: 8px;
            font-family: monospace;
        }
        
        .result-price {
            margin: 12px 0;
        }
        
        .result-price .moneda-bs {
            font-size: 1.2rem;
            font-weight: 800;
            color: #e74c3c;
        }
        
        .result-price .moneda-usd {
            font-size: 0.75rem;
        }
        
        .result-stock {
            font-size: 0.7rem;
            margin-bottom: 12px;
        }
        
        .result-stock.in-stock {
            color: #27ae60;
        }
        
        .result-stock.out-stock {
            color: #e74c3c;
        }
        
        .btn-result {
            display: block;
            background: linear-gradient(135deg, #1F9166 0%, #2ecc71 100%);
            color: white;
            text-align: center;
            padding: 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-result:hover {
            background: linear-gradient(135deg, #156b4d 0%, #1F9166 100%);
            transform: translateY(-2px);
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .no-results i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .no-results h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .no-results p {
            color: #7f8c8d;
            margin-bottom: 25px;
        }
        
        .btn-back-home {
            display: inline-block;
            background: #1F9166;
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-back-home:hover {
            background: #156b4d;
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .results-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 15px;
            }
            
            .search-title {
                font-size: 1.4rem;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .clear-filters-btn {
                margin-left: 0;
                margin-top: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .results-grid {
                grid-template-columns: 1fr;
            }
            
            .search-results-container {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>

<?php require __DIR__ . '/partials/header.php'; ?>

<div class="search-results-container">
    <div class="search-header">
        <div class="search-title">
            <i class="fas fa-search"></i>
            Resultados de Búsqueda
            <?php if ($search_active): ?>
                <a href="<?php echo BASE_URL; ?>/app/views/layouts/inicio.php" class="clear-filters-btn">
                    <i class="fas fa-times-circle"></i> Limpiar filtros
                </a>
            <?php endif; ?>
        </div>
        <div class="search-subtitle">
            <span class="filters-badge">
                <i class="fas fa-filter"></i> <?php echo $filtros_texto; ?>
            </span>
        </div>
        <div class="results-count">
            <strong><?php echo count($resultados); ?></strong> producto(s) encontrado(s)
        </div>
    </div>
    
    <?php if (!empty($resultados)): ?>
        <div class="results-grid">
            <?php foreach ($resultados as $producto): 
                $precio = floatval($producto['precio_venta'] ?? 0);
                $precioReal = floatval($producto['precio_real'] ?? $precio);
                $precios = formatearMonedaDual($precioReal);
                $stock = intval($producto['stock_actual'] ?? 0);
                $descuento = 0;
                if ($precio > 0 && $precioReal < $precio) {
                    $descuento = round((($precio - $precioReal) / $precio) * 100);
                }
            ?>
                <div class="result-card">
                    <div class="result-image">
                        <?php if ($descuento > 0): ?>
                            <span class="promo-badge-mini">-<?php echo $descuento; ?>%</span>
                        <?php endif; ?>
                        <?php if (!empty($producto['imagen_url'])): ?>
                            <img src="<?php echo htmlspecialchars($producto['imagen_url']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <?php else: ?>
                            <i class="fas fa-box fa-3x" style="color: #ccc;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="result-info">
                        <div class="result-title"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                        <div class="result-category">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?>
                        </div>
                        <?php if (!empty($producto['codigo_interno'])): ?>
                            <div class="result-code">
                                <i class="fas fa-barcode"></i> <?php echo htmlspecialchars($producto['codigo_interno']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="result-price">
                            <span class="moneda-bs"><?php echo $precios['bs']; ?></span>
                            <span class="moneda-usd"><?php echo $precios['usd']; ?></span>
                        </div>
                        <div class="result-stock <?php echo $stock > 0 ? 'in-stock' : 'out-stock'; ?>">
                            <i class="fas fa-<?php echo $stock > 0 ? 'check-circle' : 'times-circle'; ?>"></i>
                            <?php echo $stock > 0 ? $stock . ' unidades disponibles' : 'Agotado'; ?>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/app/views/layouts/product_detail.php?id=<?php echo $producto['id']; ?>" class="btn-result">
                            Ver Detalles <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h3>No se encontraron productos</h3>
            <p>No hay productos que coincidan con los filtros seleccionados.</p>
            <a href="<?php echo BASE_URL; ?>/app/views/layouts/inicio.php" class="btn-back-home">
                <i class="fas fa-home"></i> Volver al inicio
            </a>
        </div>
    <?php endif; ?>
</div>


<script src="<?php echo BASE_URL; ?>/public/js/inv-notifications.js"></script>
<script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>
<script src="<?php echo BASE_URL; ?>/public/js/components/user-panel.js"></script>
<script src="<?php echo BASE_URL; ?>/public/js/script.js"></script>

<script>
    // Mensaje de bienvenida o información si no hay resultados
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (empty($resultados) && $search_active): ?>
            if (typeof Toast !== 'undefined') {
                Toast.info('No se encontraron productos con los criterios seleccionados. Prueba con otros filtros.', 'Sin resultados', 5000);
            }
        <?php endif; ?>
    });
</script>

</body>
</html>