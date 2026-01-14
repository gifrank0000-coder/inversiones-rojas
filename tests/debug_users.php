<?php
include_once 'database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Error de conexión");
}

try {
    $query = "SELECT u.id, u.username, u.email, u.nombre_completo, u.rol_id, r.nombre as rol_nombre, 
                     c.cedula_rif, c.telefono_principal
              FROM usuarios u 
              LEFT JOIN roles r ON u.rol_id = r.id 
              LEFT JOIN clientes c ON u.id = c.usuario_id 
              ORDER BY u.created_at DESC";
    
    $stmt = $db->query($query);
    $users = $stmt->fetchAll();
    
    echo "<h2>Usuarios en el sistema:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Nombre</th><th>Rol</th><th>Cédula/RIF</th><th>Teléfono</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['nombre_completo']}</td>";
        echo "<td>{$user['rol_nombre']}</td>";
        echo "<td>{$user['cedula_rif']}</td>";
        echo "<td>{$user['telefono_principal']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>