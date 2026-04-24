<?php
// test_session_api.php - Prueba si la sesión se transmite correctamente desde AJAX

// Configurar CORS para permitir credenciales
header('Access-Control-Allow-Origin: http://localhost:8000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configurar cookie de sesión para que sea accesible desde JavaScript
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => 'localhost',
        'secure' => false,
        'httponly' => false, // Permitir acceso desde JavaScript
        'samesite' => 'Lax'
    ]);
    session_start();
}

header('Content-Type: application/json');

// Verificar si hay sesión activa
$session_active = isset($_SESSION['user_id']);
$user_id = $session_active ? $_SESSION['user_id'] : null;

// Devolver información de la sesión
echo json_encode([
    'session_active' => $session_active,
    'user_id' => $user_id,
    'session_id' => session_id(),
    'session_status' => session_status(),
    'cookies' => $_COOKIE,
    'server_session_id' => isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : null
]);
?>