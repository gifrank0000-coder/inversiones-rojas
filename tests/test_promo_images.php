<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

$db = new Database();
$conn = $db->getConnection();

$sql = "SELECT pr.id, pr.nombre, pr.descripcion, pr.imagen_url, pr.tipo_imagen, pr.imagen_banco_key,
        pr.tipo_promocion, pr.valor, pr.fecha_inicio, pr.fecha_fin, pr.estado
        FROM promociones pr
        WHERE pr.estado = true
        AND pr.fecha_inicio <= CURRENT_DATE
        AND pr.fecha_fin >= CURRENT_DATE
        ORDER BY pr.fecha_fin ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$promos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== PROMOCIONES ACTIVAS ===<br><br>";
foreach ($promos as $p) {
    echo "ID: " . $p['id'] . "<br>";
    echo "Nombre: " . $p['nombre'] . "<br>";
    echo "Descripción: " . $p['descripcion'] . "<br>";
    echo "Imagen URL: " . ($p['imagen_url'] ?: 'SIN IMAGEN') . "<br>";
    echo "Tipo imagen: " . $p['tipo_imagen'] . "<br>";
    echo "Banco key: " . ($p['imagen_banco_key'] ?: 'NINGUNO') . "<br>";
    echo "Tipo promo: " . $p['tipo_promocion'] . "<br>";
    echo "Valor: " . $p['valor'] . "<br>";
    echo "Desde: " . $p['fecha_inicio'] . " Hasta: " . $p['fecha_fin'] . "<br>";
    echo "<hr>";
}
