<?php
// Cerrar sesión y redirigir al login
session_start();
// Limpiar todas las variables de sesión
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Redirigir al login dentro de BASE_URL si está definido
$base = '';
if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
    $base = defined('BASE_URL') ? BASE_URL : '';
}

header('Location: ' . ($base ?: '/inversiones-rojas') . '/app/views/auth/Login.php');
exit;
