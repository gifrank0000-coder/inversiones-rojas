<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/promocion_helper.php';

$response = [
    'success' => false,
    'products' => [],
    'count' => 0,
    'message' => ''
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $type = $data['type'] ?? 'all'; // all, motos, repuestos, accesorios
    $year = $data['year'] ?? null;
    $brand = $data['brand'] ?? null;
    $model = $data['model'] ?? null;
    $category = $data['category'] ?? null;
    $minPrice = $data['minPrice'] ?? null;
    $maxPrice = $data['maxPrice'] ?? null;

    $db = new Database();
    $conn = $db->getConnection();

    $where_conditions = ["p.estado = true"];
    $params = [];
    $sql = "";

    if ($type === 'motos') {
        // Filtrar motos
        $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio_compra, p.precio_compra_bs, p.precio_compra_usd, p.precio_venta, p.precio_venta_bs, p.precio_venta_usd, pi.imagen_url, 
                   p.stock_actual AS stock, v.marca AS marca, v.modelo, v.anio, v.cilindrada, v.tipo_vehiculo,
                   c.nombre as categoria_nombre,
                   pr.tipo_promocion AS promo_tipo_promocion, pr.valor AS promo_valor, pr.nombre AS promo_nombre
            FROM productos p
            INNER JOIN vehiculos v ON v.producto_id = p.id
            LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
            LEFT JOIN categorias c ON c.id = p.categoria_id
            LEFT JOIN producto_promociones pp ON pp.producto_id = p.id
            LEFT JOIN promociones pr ON pr.id = pp.promocion_id
                AND pr.estado = true
                AND pr.fecha_inicio <= CURRENT_DATE
                AND pr.fecha_fin >= CURRENT_DATE
            WHERE " . implode(" AND ", $where_conditions);
        
        if ($year) {
            $sql .= " AND v.anio = :year";
            $params[':year'] = $year;
        }
        if ($brand) {
            $sql .= " AND LOWER(v.marca) LIKE LOWER(:brand)";
            $params[':brand'] = "%$brand%";
        }
        if ($model) {
            $sql .= " AND LOWER(v.modelo) LIKE LOWER(:model)";
            $params[':model'] = "%$model%";
        }
        if ($category) {
            $sql .= " AND LOWER(c.nombre) LIKE LOWER(:category)";
            $params[':category'] = "%$category%";
        }
        if (!empty($data['cilindrada'])) {
            $cilindrada = $data['cilindrada'];
            $sql .= " AND v.cilindrada LIKE :cilindrada";
            $params[':cilindrada'] = "%$cilindrada%";
        }
        if ($minPrice) {
            $sql .= " AND p.precio_venta >= :minPrice";
            $params[':minPrice'] = $minPrice;
        }
        if ($maxPrice) {
            $sql .= " AND p.precio_venta <= :maxPrice";
            $params[':maxPrice'] = $maxPrice;
        }

    } elseif ($type === 'repuestos') {
        // Filtrar repuestos
        $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio_compra, p.precio_compra_bs, p.precio_compra_usd, p.precio_venta, p.precio_venta_bs, p.precio_venta_usd, pi.imagen_url, 
                       p.stock_actual AS stock, c.nombre as categoria, r.marca_compatible AS marca
                FROM productos p
                INNER JOIN repuestos r ON r.producto_id = p.id
                LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE " . implode(" AND ", $where_conditions);
        
        if ($category) {
            $sql .= " AND LOWER(c.nombre) LIKE LOWER(:category)";
            $params[':category'] = "%$category%";
        }
        if ($brand) {
            $sql .= " AND LOWER(r.marca_compatible) LIKE LOWER(:brand)";
            $params[':brand'] = "%$brand%";
        }
        if ($minPrice) {
            $sql .= " AND p.precio_venta >= :minPrice";
            $params[':minPrice'] = $minPrice;
        }
        if ($maxPrice) {
            $sql .= " AND p.precio_venta <= :maxPrice";
            $params[':maxPrice'] = $maxPrice;
        }

    } elseif ($type === 'accesorios') {
        // Filtrar accesorios
        $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio_compra, p.precio_compra_bs, p.precio_compra_usd, p.precio_venta, p.precio_venta_bs, p.precio_venta_usd, pi.imagen_url, 
                       p.stock_actual AS stock, c.nombre as categoria,
                       a.subtipo_accesorio, a.talla, a.color, a.material, a.marca as marca_accesorio
                FROM productos p
                INNER JOIN accesorios a ON a.producto_id = p.id
                LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE " . implode(" AND ", $where_conditions);
        
        if ($category) {
            $sql .= " AND LOWER(c.nombre) LIKE LOWER(:category)";
            $params[':category'] = "%$category%";
        }
        if ($brand) {
            $sql .= " AND LOWER(a.marca) LIKE LOWER(:brand)";
            $params[':brand'] = "%$brand%";
        }
        if ($minPrice) {
            $sql .= " AND p.precio_venta >= :minPrice";
            $params[':minPrice'] = $minPrice;
        }
        if ($maxPrice) {
            $sql .= " AND p.precio_venta <= :maxPrice";
            $params[':maxPrice'] = $maxPrice;
        }

    } else {
        // Filtro general (todos los tipos)
        $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio_compra, p.precio_compra_bs, p.precio_compra_usd, p.precio_venta, p.precio_venta_bs, p.precio_venta_usd, pi.imagen_url, 
                   p.stock_actual AS stock, c.nombre as categoria
            FROM productos p
            LEFT JOIN vehiculos v ON v.producto_id = p.id
            LEFT JOIN repuestos r ON r.producto_id = p.id
            LEFT JOIN accesorios a ON a.producto_id = p.id
            LEFT JOIN producto_imagenes pi ON pi.producto_id = p.id AND pi.es_principal = true
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE " . implode(" AND ", $where_conditions);
        
        if ($category) {
            $sql .= " AND LOWER(c.nombre) LIKE LOWER(:category)";
            $params[':category'] = "%$category%";
        }
        // año puede venir de vehiculos o repuestos
        if ($year) {
            $sql .= " AND (
                        v.anio = :year OR 
                        r.anio_compatible = :year
                    )";
            $params[':year'] = $year;
        }
        if ($brand) {
            $sql .= " AND (
                        LOWER(v.marca) LIKE LOWER(:brand) OR 
                        LOWER(r.marca_compatible) LIKE LOWER(:brand) OR 
                        LOWER(a.marca) LIKE LOWER(:brand)
                    )";
            $params[':brand'] = "%$brand%";
        }
        if ($model) {
            $sql .= " AND (
                        LOWER(v.modelo) LIKE LOWER(:model) OR 
                        LOWER(r.modelo_compatible) LIKE LOWER(:model) OR 
                        LOWER(a.subtipo_accesorio) LIKE LOWER(:model) OR 
                        LOWER(a.material) LIKE LOWER(:model)
                    )";
            $params[':model'] = "%$model%";
        }
        if ($minPrice) {
            $sql .= " AND p.precio_venta >= :minPrice";
            $params[':minPrice'] = $minPrice;
        }
        if ($maxPrice) {
            $sql .= " AND p.precio_venta <= :maxPrice";
            $params[':maxPrice'] = $maxPrice;
        }
    }

    $sql .= " ORDER BY p.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Aplicar promociones y eliminar duplicados por producto
    foreach ($products as &$product) {
        $product = aplicarPromocionAProducto($product);
    }
    unset($product);
    $products = deduplicarProductosPorId($products);

    $response['success'] = true;
    $response['products'] = $products;
    $response['count'] = count($products);
    $response['message'] = 'Filtrado completado';

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
