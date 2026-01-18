<?php
// Partial: sidebar menu reutilizable para el área administrativa
// Ubicación: app/views/layouts/partials/sidebar_menu.php
// Requiere: `BASE_URL` definido y (opcional) helper `role_has_permission` en scope
$base = rtrim(defined('BASE_URL') ? BASE_URL : '', '/');
?>
<div class="admin-sidebar">
    <div class="sidebar-header">
        <div class="admin-logo">
            <i class="fas fa-motorcycle"></i>
            <h2>Inversiones Rojas</h2>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="sidebar-menu">
        <div class="menu-section">
            <h3>Dashboard</h3>
            <ul>
                <li class="menu-item"><a href="<?php echo $base; ?>/app/views/layouts/Dashboard.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            </ul>
        </div>

        <div class="menu-section">
            <h3>Gestión</h3>
            <ul>
                <?php if (function_exists('role_has_permission') ? role_has_permission($_SESSION['user_rol'] ?? null, 'ventas') : true): ?>
                <li class="menu-item"><a href="<?php echo $base; ?>/app/views/layouts/Dashboard.php?module=ventas"><i class="fas fa-shopping-cart"></i> <span>Ventas</span></a></li>
                <?php endif; ?>

                <?php if (function_exists('role_has_permission') ? role_has_permission($_SESSION['user_rol'] ?? null, 'inventario') : true): ?>
                <li class="menu-item"><a href="<?php echo $base; ?>/app/views/layouts/Dashboard.php?module=inventario"><i class="fas fa-boxes"></i> <span>Inventario</span></a></li>
                <?php endif; ?>

                <?php if (function_exists('role_has_permission') ? role_has_permission($_SESSION['user_rol'] ?? null, 'compras') : true): ?>
                <li class="menu-item"><a href="<?php echo $base; ?>/app/views/layouts/Dashboard.php?module=compras"><i class="fas fa-shopping-bag"></i> <span>Compras</span></a></li>
                <?php endif; ?>

                <li class="menu-item"><a href="<?php echo $base; ?>/app/views/layouts/Dashboard.php?module=reservas"><i class="fas fa-calendar-check"></i> <span>Reserva y Apartado</span></a></li>

                <li class="menu-item"><a href="<?php echo $base; ?>/app/views/layouts/Dashboard.php?module=promociones"><i class="fas fa-tags"></i> <span>Promociones</span></a></li>

                <li class="menu-item"><a href="<?php echo $base; ?>/app/views/layouts/Dashboard.php?module=devoluciones"><i class="fas fa-exchange-alt"></i> <span>Devoluciones</span></a></li>
            </ul>
        </div>

        <div class="menu-section">
            <h3>E-commerce</h3>
            <ul>
                <li class="menu-item"><a href="<?php echo $base; ?>/app/views/layouts/Dashboard.php?module=pedidos"><i class="fas fa-receipt"></i> <span>Pedidos Digitales</span></a></li>
                <li class="menu-item"><a href="<?php echo $base; ?>/app/views/layouts/inicio.php"><i class="fas fa-store"></i> <span>Catálogo Online</span></a></li>
            </ul>
        </div>
    </div>
</div>

<script>
// Toggle del sidebar (se ejecuta en páginas que cargan este partial)
document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('sidebarToggle');
    if (btn) {
        btn.addEventListener('click', function(){
            var sidebar = document.querySelector('.admin-sidebar');
            if (sidebar) sidebar.classList.toggle('collapsed');
        });
    }
});
</script>
