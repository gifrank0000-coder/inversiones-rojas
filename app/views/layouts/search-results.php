<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';

$type = $_GET['type'] ?? 'all';
$year = $_GET['year'] ?? null;
$brand = $_GET['brand'] ?? null;
$model = $_GET['model'] ?? null;

// Validar parámetros
$type = in_array($type, ['motos', 'repuestos', 'accesorios', 'all']) ? $type : 'all';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Búsqueda - Inversiones Rojas</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/components/user-panel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body data-base-url="<?php echo BASE_URL; ?>">
    <?php require_once __DIR__ . '/partials/header.php'; ?>
    
    <section class="search-results-section">
        <div class="container">
            <h1>Resultados de Búsqueda</h1>
            
            <div class="filters-resumen">
                <h3>Filtros aplicados:</h3>
                <div class="filter-tags">
                    <?php if ($year): ?>
                        <span class="filter-tag">Año: <?php echo htmlspecialchars($year); ?></span>
                    <?php endif; ?>
                    <?php if ($brand): ?>
                        <span class="filter-tag">Marca: <?php echo htmlspecialchars($brand); ?></span>
                    <?php endif; ?>
                    <?php if ($model): ?>
                        <span class="filter-tag">Modelo: <?php echo htmlspecialchars($model); ?></span>
                    <?php endif; ?>
                    <?php if ($type !== 'all'): ?>
                        <span class="filter-tag">Tipo: <?php echo htmlspecialchars(ucfirst($type)); ?></span>
                    <?php endif; ?>
                </div>
                <a href="<?php echo BASE_URL; ?>/inicio.php" class="btn-reset-filters">Limpiar Filtros</a>
            </div>
            
            <div class="products-count">
                <p>Se encontraron <strong id="product-count">0</strong> productos</p>
            </div>
            
            <div id="productsGrid" class="products-grid">
                <div class="loading">Cargando resultados...</div>
            </div>
        </div>
    </section>
    
    <!-- El footer no existe en esta ruta; se omite para evitar error -->
    <!-- Se puede añadir aquí un footer manual si se desea -->
    
    <style>
        .search-results-section {
            padding: 40px 0;
            min-height: calc(100vh - 400px);
        }
        
        .search-results-section h1 {
            font-size: 2.5rem;
            margin-bottom: 40px;
            color: #333;
            text-align: center;
        }
        
        .filters-resumen {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .filters-resumen h3 {
            margin: 0;
            font-size: 1rem;
            color: #666;
            min-width: 150px;
        }
        
        .filter-tags {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            flex: 1;
        }
        
        .filter-tag {
            background: #007bff;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .btn-reset-filters {
            background: #6c757d;
            color: white;
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .btn-reset-filters:hover {
            background: #5a6268;
        }
        
        .products-count {
            margin-bottom: 30px;
            padding: 15px;
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        
        .products-count p {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
        }
        
        .products-count strong {
            color: #007bff;
            font-weight: 600;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .loading {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 1.1rem;
        }
        
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-results p {
            font-size: 1.2rem;
            margin: 20px 0;
        }
        
        .no-results a {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 30px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .no-results a:hover {
            background: #0056b3;
        }
    </style>
    
    <script>
        const baseUrl = document.body.getAttribute('data-base-url');
        const urlParams = new URLSearchParams(window.location.search);
        const type = urlParams.get('type') || 'all';
        const year = urlParams.get('year');
        const brand = urlParams.get('brand');
        const model = urlParams.get('model');
        
        document.addEventListener('DOMContentLoaded', function() {
            cargarResultados();
        });
        
        function cargarResultados() {
            const filtros = {
                type: type,
                year: year,
                brand: brand,
                model: model
            };
            
            // Eliminar valores nulos
            Object.keys(filtros).forEach(key => {
                if (!filtros[key]) delete filtros[key];
            });
            
            fetch(baseUrl + '/api/filter_products.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(filtros)
            })
            .then(response => response.json())
            .then(data => {
                mostrarResultados(data);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('productsGrid').innerHTML = 
                    '<div class="loading">Error al cargar los resultados.</div>';
            });
        }
        
        function mostrarResultados(data) {
            const grid = document.getElementById('productsGrid');
            const countElement = document.getElementById('product-count');
            
            if (!data.success || data.products.length === 0) {
                countElement.textContent = '0';
                grid.innerHTML = `
                    <div class="no-results">
                        <p><i class="fas fa-search"></i></p>
                        <p>No se encontraron productos con los filtros seleccionados.</p>
                    </div>
                `;
                return;
            }
            
            countElement.textContent = data.count;
            grid.innerHTML = data.products.map(producto => `
                <div class="product-card">
                    <div class="product-image">
                        <img src="${baseUrl}${producto.imagen_url || '/public/img/products/placeholder.jpg'}" 
                             alt="${producto.nombre}">
                        <span class="product-type">${producto.tipo || 'Producto'}</span>
                    </div>
                    <div class="product-info">
                        <h3>${producto.nombre}</h3>
                        <p class="product-description">${producto.descripcion || 'Sin descripción'}</p>
                        <div class="product-price">
                            <span class="price">$${Number(producto.precio_venta).toLocaleString()}</span>
                        </div>
                        <div class="product-stock">
                            ${producto.stock_actual > 0 
                                ? `<span class="in-stock">✓ En stock (${producto.stock_actual})</span>` 
                                : '<span class="out-of-stock">Agotado</span>'}
                        </div>
                        <div class="product-actions">
                            <a href="${baseUrl}/app/views/layouts/product_detail.php?id=${producto.id}" 
                               class="btn btn-view">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </a>
                            <button class="btn btn-cart" onclick="agregarAlCarrito(${producto.id})">
                                <i class="fas fa-cart-plus"></i> Carrito
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        function agregarAlCarrito(productId) {
            fetch(baseUrl + '/api/add_to_cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({product_id: productId, quantity: 1})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Producto agregado al carrito');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al agregar al carrito');
            });
        }
    </script>

    <!-- Scripts comunes para user panel y navegación -->
    <script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/components/user-panel.js"></script>
    <script src="<?php echo BASE_URL; ?>/public/js/script.js"></script>
</body>
</html>
