<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/promocion_helper.php';
require_once __DIR__ . '/../app/helpers/moneda_helper.php';

echo "=== PRUEBA DE PROMOCIONES ===<br><br>";

echo "Tasa actual: " . TASA_CAMBIO . "<br><br>";

// Ver promociones activas
$db = new Database();
$conn = $db->getConnection();

$sql = "SELECT p.id, p.nombre, p.precio_venta, 
        pr.nombre as promo_nombre, pr.tipo_promocion, pr.valor,
        pr.fecha_inicio, pr.fecha_fin, pr.estado
        FROM productos p
        INNER JOIN producto_promociones pp ON p.id = pp.producto_id
        INNER JOIN promociones pr ON pp.promocion_id = pr.id
        WHERE pr.estado = true
        ORDER BY pr.fecha_fin ASC
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->execute();
$promos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Promociones encontradas: " . count($promos) . "<br><br>";

foreach ($promos as $promo) {
    echo "--- Producto: " . $promo['nombre'] . " ---<br>";
    echo "Precio original: " . formatearMonedaDual($promo['precio_venta'])['bs'] . " (" . formatearMonedaDual($promo['precio_venta'])['usd'] . ")<br>";
    
    // Calcular precio con promoción
    $precioConPromo = calcularPrecioConPromocion(
        floatval($promo['precio_venta']),
        $promo['tipo_promocion'],
        $promo['valor']
    );
    
    echo "Promoción: " . $promo['promo_nombre'] . "<br>";
    echo "Tipo: " . $promo['tipo_promocion'] . " - Valor: " . $promo['valor'] . "<br>";
    echo "Precio con promo: " . formatearMonedaDual($precioConPromo)['bs'] . " (" . formatearMonedaDual($precioConPromo)['usd'] . ")<br>";
    echo "Válido desde: " . $promo['fecha_inicio'] . " hasta " . $promo['fecha_fin'] . "<br><br>";
}
