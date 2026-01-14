<?php
include_once '../app/models/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "Conexión exitosa a PostgreSQL!";
    
    // Probar consulta
    $stmt = $db->query("SELECT version()");
    $version = $stmt->fetch();
    echo "<br>Versión de PostgreSQL: " . $version['version'];
} else {
    echo "Error de conexión";
}
?>