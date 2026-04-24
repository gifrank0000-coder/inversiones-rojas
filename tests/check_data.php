<?php
require_once 'app/models/database.php';
$db = new Database();
$conn = $db->getConnection();
if ($conn) {
    $stmt = $conn->query('SELECT COUNT(*) as total FROM ventas');
    $result = $stmt->fetch();
    echo 'Total ventas: ' . $result['total'] . PHP_EOL;

    $stmt = $conn->query('SELECT COUNT(*) as total FROM clientes');
    $result = $stmt->fetch();
    echo 'Total clientes: ' . $result['total'] . PHP_EOL;

    // Verificar ventas de hoy
    $stmt = $conn->query("SELECT COUNT(*) as total FROM ventas WHERE DATE(created_at) = CURRENT_DATE");
    $result = $stmt->fetch();
    echo 'Ventas de hoy: ' . $result['total'] . PHP_EOL;
} else {
    echo 'Error de conexión' . PHP_EOL;
}
?>