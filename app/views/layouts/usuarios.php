<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../models/Usuario.php';

$db = new Database();
$conn = $db->getConnection();
$users = [];
$errorMsg = null;
if ($conn) {
    $usuarioModel = new Usuario($conn);
    $result = $usuarioModel->obtenerTodos();
    if ($result !== false) {
        $users = $result;
    } else {
        $errorMsg = 'No se pudieron obtener los usuarios.';
        // Si estamos en modo debug, intentar obtener más detalles
        if (defined('APP_DEBUG') && APP_DEBUG) {
            try {
                $stmt = $conn->query("SELECT count(*) as c FROM usuarios");
                $count = $stmt->fetch(PDO::FETCH_ASSOC);
                $errorMsg .= ' (consulta falló, pero tabla existe, registros: ' . intval($count['c']) . ')';
            } catch (Exception $e) {
                $errorMsg .= ' (detalle: ' . $e->getMessage() . ')';
            }
        }
    }
} else {
    $errorMsg = 'Error de conexión a la base de datos.';
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $errorMsg .= ' Detalle: ' . $db->getLastError();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios - Inversiones Rojas</title>
    <link rel="stylesheet" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/public/css/admin/dashboard.css">
    <style>
        .users-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .users-table th, .users-table td { padding: 10px 12px; border: 1px solid #e9ecef; text-align: left; }
        .users-table th { background: #f8f9fa; }
        .container { max-width: 1100px; margin: 30px auto; padding: 20px; }
        .title { display:flex; align-items:center; justify-content:space-between; }
        .btn { padding:8px 14px; border-radius:6px; text-decoration:none; background:#1F9166; color:white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">
            <h1>Usuarios registrados</h1>
            <a class="btn" href="<?php echo rtrim(defined('BASE_URL') ? BASE_URL : '', '/'); ?>/">Volver</a>
        </div>

        <?php if ($errorMsg): ?>
            <div style="margin-top:20px;padding:12px;background:#ffecec;border:1px solid #f5c2c2;color:#8a1f1f;border-radius:6px;">
                <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php else: ?>
            <table class="users-table" aria-describedby="Lista de usuarios registrados">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Nombre</th>
                        <th>Rol ID</th>
                        <th>Estado</th>
                        <th>Último acceso</th>
                        <th>Creado</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="8">No hay usuarios registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['id']); ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($u['rol_id']); ?></td>
                            <td><?php echo $u['estado'] ? 'Activo' : 'Inactivo'; ?></td>
                            <td><?php echo htmlspecialchars($u['ultimo_acceso']); ?></td>
                            <td><?php echo htmlspecialchars($u['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
