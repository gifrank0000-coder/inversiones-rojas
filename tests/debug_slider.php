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

// Generar HTML de debug
echo "<!-- DEBUG: Total slides = " . count($productos_promocion) . " -->\n";
echo "<!-- DEBUG: Slides = ";
foreach ($productos_promocion as $idx => $pp) {
    echo $idx . ":" . ($pp['promocion_nombre'] ?? 'sin nombre') . "; ";
}
echo " -->\n";
