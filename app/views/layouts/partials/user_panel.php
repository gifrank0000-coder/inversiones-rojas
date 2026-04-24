<?php
// Partial: user panel shared entre vistas
// Requiere: session_start() ya ejecutado y BASE_URL disponible
// Normalizar rol usando helper
$user_name = $_SESSION['user_name'] ?? ($_SESSION['user_email'] ?? 'Usuario');
$user_email = $_SESSION['user_email'] ?? '';
$raw_role = $_SESSION['user_rol'] ?? '';
// Intentar usar canonical_role si está disponible
if (file_exists(__DIR__ . '/../../../helpers/permissions.php')) {
    require_once __DIR__ . '/../../../helpers/permissions.php';
}
$role_norm = '';
if (function_exists('canonical_role')) {
    $role_norm = strtolower(canonical_role($raw_role));
} else {
    $role_norm = strtolower($raw_role);
}
?>

<div class="user-panel" id="userPanel">
    <div class="user-toggle" id="userToggle" role="button" tabindex="0">
        <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
        <div class="user-info">
            <a href="<?php echo BASE_URL; ?>/app/views/layouts/perfil.php" class="user-name" style="color:inherit; text-decoration:none;"><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></a>
            <span class="user-role"><?php echo ucfirst($role_norm ?: $raw_role); ?></span>
        </div>
        <i class="fas fa-chevron-down"></i>
    </div>

    <div class="user-dropdown">
        <div class="dropdown-header">
            <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
            <div class="user-email"><?php echo htmlspecialchars($user_email); ?></div>
        </div>

        <ul class="dropdown-menu">
            <?php if ($role_norm === 'cliente'): ?>
                <!-- Cliente: mantener el header de la página; mostrar solo enlaces esenciales -->
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/bandeja_pedidos.php" class="dropdown-item"><i class="fas fa-inbox"></i> Mi Bandeja</a></li>
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/perfil.php" class="dropdown-item"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/devolucionCliente.php" class="dropdown-item"><i class="fas fa-undo"></i> Mis Devoluciones</a></li>
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/configuracion.php" class="dropdown-item"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/soporte.php" class="dropdown-item"><i class="fas fa-life-ring"></i> Soporte Técnico</a></li>
                <li class="dropdown-divider"></li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>
            <?php else: ?>
                <!-- Usuarios no-cliente: menú administrativo completo -->
                <li>
                    <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php" class="dropdown-item">
                        <i class="fas fa-tachometer-alt"></i> Mi Panel
                    </a>
                </li>
                <li class="dropdown-divider"></li>
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/perfil.php" class="dropdown-item"><i class="fas fa-user"></i> Mi Perfil</a></li>
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/configuracion.php" class="dropdown-item"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a href="<?php echo BASE_URL; ?>/app/views/layouts/soporte.php" class="dropdown-item"><i class="fas fa-life-ring"></i> Soporte Técnico</a></li>
                <li class="dropdown-divider"></li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>
