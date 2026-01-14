<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';

// Asegurar que el usuario esté logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ' . BASE_URL . '/app/views/auth/Login.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? 'Cliente';

// Mapeo de permisos / secciones por rol
function getFeaturesByRole($rol) {
    $roles = [
        'Administrador' => [
            ['title' => 'Gestión de Inventario', 'icon' => 'fas fa-boxes', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=inventario'],
            ['title' => 'Ventas', 'icon' => 'fas fa-shopping-cart', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=ventas'],
            ['title' => 'Compras', 'icon' => 'fas fa-truck', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=compras'],
            ['title' => 'Pedidos Digitales', 'icon' => 'fas fa-receipt', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=pedidos'],
            ['title' => 'Reservas y Apartados', 'icon' => 'fas fa-calendar-check', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=reservas'],
            ['title' => 'Promociones', 'icon' => 'fas fa-tags', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=promociones'],
            ['title' => 'Devoluciones', 'icon' => 'fas fa-undo', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=devoluciones'],
        ],
        'Gerente' => [
            ['title' => 'Compras', 'icon' => 'fas fa-truck', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=compras'],
            ['title' => 'Ventas', 'icon' => 'fas fa-shopping-cart', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=ventas'],
            ['title' => 'Promociones', 'icon' => 'fas fa-tags', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=promociones'],
            ['title' => 'Inventario', 'icon' => 'fas fa-boxes', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=inventario'],
            ['title' => 'Devoluciones', 'icon' => 'fas fa-undo', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=devoluciones'],
        ],
        'Vendedor' => [
            ['title' => 'Ventas', 'icon' => 'fas fa-shopping-cart', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=ventas'],
            ['title' => 'Reservas/Apartados', 'icon' => 'fas fa-calendar-check', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=reservas'],
            ['title' => 'Promociones', 'icon' => 'fas fa-tags', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=promociones'],
            ['title' => 'Devoluciones', 'icon' => 'fas fa-undo', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=devoluciones'],
        ],
        'Operador' => [
            ['title' => 'Pedidos Digitales', 'icon' => 'fas fa-receipt', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=pedidos'],
            ['title' => 'Reservas/Apartados', 'icon' => 'fas fa-calendar-check', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=reservas'],
            ['title' => 'Devoluciones', 'icon' => 'fas fa-undo', 'link' => BASE_URL . '/app/views/layouts/Dashboard.php?module=devoluciones'],
        ],
        'Cliente' => [
            ['title' => 'Mi Pedidos', 'icon' => 'fas fa-shopping-bag', 'link' => BASE_URL . '/app/views/layouts/inicio.php?section=pedidos'],
            ['title' => 'Reservas/Apartados', 'icon' => 'fas fa-calendar-check', 'link' => BASE_URL . '/app/views/layouts/inicio.php?section=reservas'],
            ['title' => 'Devoluciones', 'icon' => 'fas fa-undo', 'link' => BASE_URL . '/app/views/layouts/inicio.php?section=devoluciones'],
        ],
    ];

    return $roles[$rol] ?? $roles['Cliente'];
}

$features = getFeaturesByRole($user_rol);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Inicio - <?php echo htmlspecialchars($user_rol); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/base.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/pages/auth.css">
    <style>
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 20px; margin: 30px 0; }
        .feature-card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 6px 18px rgba(0,0,0,0.06); display:flex; align-items:center; gap: 15px; }
        .feature-icon { width:56px; height:56px; border-radius:10px; background:#1F9166; color:white; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .feature-title { font-size:16px; font-weight:600; }
    </style>
</head>
<body>
    <header style="padding:18px 30px; background:#f8f9fa; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2>Bienvenido, <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></h2>
            <small>Rol: <?php echo htmlspecialchars($user_rol); ?></small>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/logout.php" style="color:#dc3545; text-decoration:none;">Cerrar sesión</a>
        </div>
    </header>

    <main style="padding:30px; max-width:1100px; margin:0 auto;">
        <h3>Accesos rápidos</h3>
        <div class="feature-grid">
            <?php foreach ($features as $f): ?>
                <a class="feature-card" href="<?php echo $f['link']; ?>">
                    <div class="feature-icon"><i class="<?php echo $f['icon']; ?>"></i></div>
                    <div>
                        <div class="feature-title"><?php echo htmlspecialchars($f['title']); ?></div>
                        <div style="font-size:13px; color:#666;">Abrir módulo: <?php echo htmlspecialchars($f['title']); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <section style="margin-top:40px;">
            <h4>Resumen rápido</h4>
            <p>Aquí puedes mostrar métricas o widgets específicos por rol (ventas, pedidos pendientes, stock bajo, etc.).</p>
            <!-- Placeholder: puedes invocar queries si el rol lo permite -->
        </section>
    </main>

    <script src="<?php echo BASE_URL; ?>/public/js/main.js"></script>
</body>
</html>
