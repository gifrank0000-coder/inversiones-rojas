<?php
// Helper simple para permisos basados en roles (RBAC mínimo)
// Ubicación: app/helpers/permissions.php

if (!function_exists('get_role_permissions')) {
    function get_role_permissions()
    {
        return [
            'Administrador' => ['inventario','ventas','compras','pedidos','reservas','promociones','devoluciones','configuracion','perfil','soporte'],
            // Soporte técnico debe estar disponible para los roles operativos y usuarios
            'Gerente' => ['compras','ventas','promociones','inventario','devoluciones','soporte'],
            'Vendedor' => ['ventas','pedidos','reservas','promociones','devoluciones','soporte'],
            'Operador' => ['pedidos','reservas','devoluciones','soporte'],
            'Cliente' => ['inventario','ventas','compras','pedidos','reservas','promociones','devoluciones','configuracion','perfil','soporte'],
        ];
    }
}

if (!function_exists('canonical_role')) {
    /**
     * Normaliza nombres de rol recibidos desde la BD o sesión
     * Devuelve uno de: Administrador, Gerente, Vendedor, Operador, Cliente
     */
    function canonical_role($raw)
    {
        if (!$raw) return null;
        $r = trim(strtolower($raw));
        // Mapear variantes comunes
        if (strpos($r, 'admin') !== false || strpos($r, 'administr') !== false) return 'Administrador';
        if (strpos($r, 'gerente') !== false) return 'Gerente';
        if (strpos($r, 'vendedor') !== false || strpos($r, 'venta') !== false) return 'Vendedor';
        if (strpos($r, 'operador') !== false || strpos($r, 'oper') !== false) return 'Operador';
        if (strpos($r, 'cliente') !== false || strpos($r, 'client') !== false) return 'Cliente';
        // Si no coincide, intentar capitalizar palabras
        $cap = ucwords($r);
        // Si está en el mapa de permisos, devolverlo
        $perms = get_role_permissions();
        foreach (array_keys($perms) as $known) {
            if (strtolower($known) === strtolower($cap)) return $known;
        }
        // Fallback: retornar ucfirst de la cadena
        return ucfirst($r);
    }
}

if (!function_exists('role_has_permission')) {
    function role_has_permission($role, $permission)
    {
        $map = get_role_permissions();
        // Normalizar el rol entrante
        $roleNorm = is_string($role) ? canonical_role($role) : $role;
        if (!$roleNorm || !isset($map[$roleNorm])) return false;
        return in_array($permission, $map[$roleNorm]);
    }
}

if (!function_exists('require_permission')) {
    function require_permission($permission)
    {
        // Asegurar sesión
        if (session_status() === PHP_SESSION_NONE) session_start();
        $role = $_SESSION['user_rol'] ?? null;
        if (!$role || !role_has_permission($role, $permission)) {
            // Redirigir a inicio por rol con mensaje breve (flash puede implementarse luego)
            $home = function_exists('get_role_home') ? get_role_home($role) : ((defined('BASE_URL') ? BASE_URL : '') . '/app/views/layouts/inicio_role.php');
            header('Location: ' . $home);
            exit;
        }
        return true;
    }
}

if (!function_exists('get_role_home')) {
    /**
     * Devuelve la URL de inicio según el rol.
     * Usa BASE_URL si está definida para construir rutas absolutas dentro del proyecto.
     */
    function get_role_home($role)
    {
        $base = defined('BASE_URL') ? BASE_URL : '';
        $map = [
            // Todos los roles administrativos o de operación usan el mismo Dashboard,
            // la visibilidad de módulos se controla en la UI con role_has_permission().
            'Administrador' => $base . '/app/views/layouts/Dashboard.php',
            'Gerente' => $base . '/app/views/layouts/Dashboard.php',
            'Vendedor' => $base . '/app/views/layouts/Dashboard.php',
            'Operador' => $base . '/app/views/layouts/Dashboard.php',
            'Cliente' => $base . '/app/views/layouts/inicio.php',
        ];
        return $map[$role] ?? $base . '/app/views/layouts/inicio_role.php';
    }
}

?>