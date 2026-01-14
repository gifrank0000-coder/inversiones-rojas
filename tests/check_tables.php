<?php
include_once 'database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "Conexión exitosa!<br><br>";
    
    // Verificar tablas existentes
    $query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
    $stmt = $db->query($query);
    $tables = $stmt->fetchAll();
    
    echo "Tablas en la base de datos:<br>";
    foreach ($tables as $table) {
        echo "- " . $table['table_name'] . "<br>";
    }
    
    echo "<br>--- Estructura de tablas importantes ---<br>";
    
    // Verificar estructura de tabla usuarios si existe
    $check_usuarios = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'usuarios'";
    $stmt_usuarios = $db->query($check_usuarios);
    $usuarios_cols = $stmt_usuarios->fetchAll();
    
    echo "<br>Estructura de 'usuarios':<br>";
    foreach ($usuarios_cols as $col) {
        echo "- " . $col['column_name'] . " (" . $col['data_type'] . ")<br>";
    }
    
    // Verificar estructura de tabla clientes si existe
    $check_clientes = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'clientes'";
    $stmt_clientes = $db->query($check_clientes);
    $clientes_cols = $stmt_clientes->fetchAll();
    
    echo "<br>Estructura de 'clientes':<br>";
    foreach ($clientes_cols as $col) {
        echo "- " . $col['column_name'] . " (" . $col['data_type'] . ")<br>";
    }
    
} else {
    echo "Error de conexión";
}
?>