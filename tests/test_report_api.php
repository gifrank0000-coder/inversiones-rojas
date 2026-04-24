<?php
// test_report_api.php - Prueba del API de reportes

// Simular sesión
session_start();
$_SESSION['user_id'] = 1; // Usuario de prueba

// Simular la solicitud POST con JSON
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Datos de prueba para el reporte
$json_input = json_encode([
    'report_type' => 'ingresos_diario',
    'module' => 'ventas'
]);
file_put_contents('php://input', $json_input);

// Ejecutar el API
require_once __DIR__ . '/../api/generate_report.php';

echo "Prueba completada.\n";
?>