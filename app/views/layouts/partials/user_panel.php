<?php
// Partial: user panel shared entre vistas
// Requiere: session_start() ya ejecutado y BASE_URL disponible
?>
<div class="user-panel" id="userPanel">
    <div class="user-toggle" id="userToggle" role="button" tabindex="0">
        <div class="user-avatar">
            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
        </div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars(explode(' ', ($_SESSION['user_name'] ?? ($_SESSION['user_email'] ?? 'Usuario')))[0]); ?></span>
            <span class="user-role"><?php echo ucfirst( (is_string($_SESSION['user_rol'] ?? '') ? $_SESSION['user_rol'] : '') ); ?></span>
        </div>
        <i class="fas fa-chevron-down"></i>
    </div>

    <div class="user-dropdown">
        <div class="dropdown-header">
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ($_SESSION['user_email'] ?? 'Usuario')); ?></div>
            <div class="user-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></div>
        </div>

        <ul class="dropdown-menu">
            <?php if ( (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] !== 'Cliente') ): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>/app/views/layouts/Dashboard.php" class="dropdown-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Mi Panel
                    </a>
                </li>
                <li class="dropdown-divider"></li>
            <?php endif; ?>
            <li>
                <button class="dropdown-item" onclick="openProfile()">
                    <i class="fas fa-user"></i>
                    Mi Perfil
                </button>
            </li>
            <li>
                <button class="dropdown-item" onclick="openOrders()">
                    <i class="fas fa-shopping-bag"></i>
                    Mis Pedidos
                </button>
            </li>
            <li>
                <button class="dropdown-item" onclick="openSettings()">
                    <i class="fas fa-cog"></i>
                    Configuración
                </button>
            </li>
            <li class="dropdown-divider"></li>
            <li>
                <a href="<?php echo BASE_URL; ?>/logout.php" class="dropdown-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>
</div>
