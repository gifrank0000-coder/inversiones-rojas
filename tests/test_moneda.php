<?php
require_once __DIR__ . '/../config/config.php';

echo "=== PRUEBA DE MONEDA DUAL ===<br><br>";

echo "Tasa actual: " . TASA_CAMBIO . "<br><br>";

// Probar formateo
$precios = formatearMonedaDual(100);
echo "Precio base: USD 100<br>";
echo "En Bs: " . $precios['bs'] . "<br>";
echo "En USD: " . $precios['usd'] . "<br><br>";

$precios = formatearMonedaDual(30000);
echo "Precio base: USD 30,000<br>";
echo "En Bs: " . $precios['bs'] . "<br>";
echo "En USD: " . $precios['usd'] . "<br><br>";

// Probar guardar tasa
echo "=== GUARDAR NUEVA TASA ===<br>";
$result = guardarTasaCambio(36.50);
echo "Resultado: " . ($result['success'] ? 'OK' : 'ERROR') . "<br>";

echo "Nueva tasa: " . TASA_CAMBIO . "<br><br>";

$precios = formatearMonedaDual(30000);
echo "Precio base: USD 30,000 (con nueva tasa)<br>";
echo "En Bs: " . $precios['bs'] . "<br>";
echo "En USD: " . $precios['usd'] . "<br>";
