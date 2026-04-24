<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../models/database.php';

// Obtener parámetro de búsqueda
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];
$total_count = 0;

if (strlen($search_query) >= 2) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        if ($conn) {
            $searchTerm = '%' . $search_query . '%';
            
            $sql = "SELECT 
                        p.id,
                        p.nombre,
                        p.descripcion,
                        p.precio_venta,
                        c.nombre as categoria,
                        pi.imagen_url
                    FROM productos p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    LEFT JOIN producto_imagenes pi ON p.id = pi.producto_id AND pi.es_principal = true
                    WHERE p.estado = true
                    AND LOWER(p.nombre) LIKE LOWER(:search)
                    ORDER BY p.nombre ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([':search' => $searchTerm]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total_count = count($results);
        }
    } catch (Exception $e) {
        error_log('ERROR en search_results.php: ' . $e->getMessage());
    }
}

// Detectar sesión
$usuario_logueado = false;
$user_name = '';
$user_rol = '';
if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    $usuario_logueado = true;
    $user_name = $_SESSION['user_name'];
    $user_rol = $_SESSION['user_rol'] ?? 'cliente';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Búsqueda - Inversiones Rojas</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/auth.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/admin.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/home.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/components/user-panel.css">
    <style>
        .search-results-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .search-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .search-header h1 {
            color: #1F9166;
            margin-bottom: 10px;
        }
        .search-header p {
            color: #666;
            font-size: 16px;
        }
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .product-image {
            width: 100%;
            height: 180px;
            background: #f5f5f5;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-info {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .product-name {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 8px;
            color: #333;
            line-height: 1.3;
        }
        .product-category {
            font-size: 12px;
            color: #1F9166;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 500;
        }
        .product-desc {
            font-size: 13px;
            color: #666;
            margin-bottom: 12px;
            flex: 1;
            line-height: 1.4;
        }
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #1F9166;
        }
        .btn-details {
            flex: 1;
            padding: 8px 12px;
            background: #1F9166;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .btn-details:hover {
            background: #187a54;
        }
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .no-results i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }
        .no-results h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .search-footer {
            text-align: center;
            margin-top: 40px;
        }
        .search-footer a {
            color: #1F9166;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/partials/header.php'; ?>
    
    <div class="search-results-container">
        <div class="search-header">
            <h1>Resultados de Búsqueda</h1>
            <p><strong><?php echo htmlspecialchars($search_query); ?></strong> - Se encontraron <strong><?php echo $total_count; ?></strong> producto(s)</p>
        </div>
        
        <?php if ($total_count > 0): ?>
            <div class="results-grid">
                <?php foreach ($results as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (!empty($product['imagen_url'])): ?>
                                <img src="<?php echo htmlspecialchars($product['imagen_url']); ?>" alt="<?php echo htmlspecialchars($product['nombre']); ?>">
                            <?php else: ?>
                                <i class="fas fa-box" style="font-size: 48px; color: #ddd;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <?php if (!empty($product['categoria'])): ?>
                                <div class="product-category"><?php echo htmlspecialchars($product['categoria']); ?></div>
                            <?php endif; ?>
                            <div class="product-name"><?php echo htmlspecialchars($product['nombre']); ?></div>
                            <?php if (!empty($product['descripcion'])): ?>
                                <div class="product-desc"><?php echo htmlspecialchars(substr($product['descripcion'], 0, 100)); ?><?php echo strlen($product['descripcion']) > 100 ? '...' : ''; ?></div>
                            <?php endif; ?>
                            <div class="product-footer">
                                <div class="product-price">$<?php echo number_format((float)($product['precio_venta'] ?? 0), 2); ?></div>
                                <a href="<?php echo BASE_URL; ?>/app/views/layouts/product_detail.php?id=<?php echo $product['id']; ?>" class="btn-details">
                                    <i class="fas fa-arrow-right"></i> Ver
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h2>No se encontraron productos</h2>
                <p>Intenta con otras palabras clave o revisa las categorías disponibles.</p>
            </div>
        <?php endif; ?>
        
        <div class="search-footer">
            <a href="<?php echo BASE_URL; ?>/">← Volver a Inicio</a>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/components/user-panel.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/script.js"></script>
</body>
</html>
