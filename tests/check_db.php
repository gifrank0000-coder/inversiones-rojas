<?php
// Verifica la conexión a la base de datos usando la clase Database
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$dbClass = new Database();
$conn = $dbClass->getConnection();

$result = [
    'connected' => false,
    'message' => '',
    'config' => [
        'host' => property_exists($dbClass, 'host') ? (new ReflectionProperty($dbClass, 'host'))->getValue($dbClass) : null,
        'db_name' => property_exists($dbClass, 'db_name') ? (new ReflectionProperty($dbClass, 'db_name'))->getValue($dbClass) : null,
        'username' => property_exists($dbClass, 'username') ? (new ReflectionProperty($dbClass, 'username'))->getValue($dbClass) : null,
        'port' => property_exists($dbClass, 'port') ? (new ReflectionProperty($dbClass, 'port'))->getValue($dbClass) : null,
    ]
];

if ($conn) {
    try {
        $stmt = $conn->query("SELECT 1 as ok");
        $row = $stmt->fetch();
        $result['connected'] = true;
        $result['message'] = 'Conectado correctamente (consulta simple OK)';
    } catch (PDOException $e) {
        $result['message'] = 'Conectado pero la consulta falló: ' . $e->getMessage();
    }
} else {
    $result['message'] = 'No se pudo establecer la conexión. Revisa logs de PHP/Apache y las credenciales.';
    if (defined('APP_DEBUG') && APP_DEBUG && method_exists($dbClass, 'getLastError')) {
        $result['db_error'] = $dbClass->getLastError();
    }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

?>
