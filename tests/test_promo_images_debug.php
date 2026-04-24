<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/promocion_helper.php';

$db = new Database();
$conn = $db->getConnection();

$sqlPromo = "SELECT p.id, p.nombre, p.descripcion, p.precio_venta, 
            COALESCE(pr.imagen_url, pi.imagen_url, '') as imagen_url, 
            pr.nombre as promocion_nombre, 
            pr.descripcion as promocion_descripcion,
            pr.tipo_promocion as promo_tipo_promocion, 
            pr.valor as promo_valor
     FROM productos p
     INNER JOIN producto_promociones pp ON p.id = pp.producto_id
     INNER JOIN promociones pr ON pp.promocion_id = pr.id
     LEFT JOIN producto_imagenes pi ON p.id = pi.producto_id AND pi.es_principal = true
     WHERE pr.estado = true
       AND pr.fecha_inicio <= CURRENT_DATE
       AND pr.fecha_fin >= CURRENT_DATE
     ORDER BY pr.fecha_fin ASC
     LIMIT 8";

$stmtPromo = $conn->prepare($sqlPromo);
$stmtPromo->execute();
$productos_promocion = $stmtPromo->fetchAll(PDO::FETCH_ASSOC);

foreach ($productos_promocion as &$pp) {
    $pp = aplicarPromocionAProducto($pp);
}
unset($pp);

echo "=== PRODUCTOS EN PROMOCIÓN ===<br><br>";
echo "Total: " . count($productos_promocion) . "<br><br>";

foreach ($productos_promocion as $idx => $pp) {
    echo "=== Slide " . ($idx + 1) . " ===<br>";
    echo "Producto: " . $pp['nombre'] . "<br>";
    echo "Promoción: " . $pp['promocion_nombre'] . "<br>";
    
    // Convertir URL de imagen a ruta relativa
    $imagenUrl = $pp['imagen_url'] ?? '';
    if (!empty($imagenUrl)) {
        if (strpos($imagenUrl, '/inversiones-rojas/') !== false) {
            $imagenUrl = substr($imagenUrl, strpos($imagenUrl, '/inversiones-rojas/') + 1);
        }
        if (strpos($imagenUrl, 'http') === 0) {
            $imagenUrl = preg_replace('#^https?://[^/]+(/inversiones-rojas/)#', '$1', $imagenUrl);
        }
    }
    
    echo "URL relativa: " . ($imagenUrl ?: 'SIN IMAGEN') . "<br>";
    echo "<hr>";
}
