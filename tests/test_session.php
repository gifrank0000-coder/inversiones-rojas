<?php
// test_session.php - Verificar estado de sesión
session_start();

header('Content-Type: application/json');

$response = [
    'session_status' => session_status(),
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? null,
    'user_name' => $_SESSION['user_name'] ?? null,
    'cookies' => $_COOKIE,
    'server' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? null,
        'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>