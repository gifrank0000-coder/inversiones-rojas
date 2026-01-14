<?php
session_start();
header('Content-Type: application/json');

// Este endpoint ha sido simplificado: ahora solo registra un usuario en la tabla `usuarios`.
// Espera: POST con campos `email`, `password`, `confirm_password` y opcional `nombre_completo`.

// Cargar configuración global para BASE_URL (opcional)
$root_path = dirname(__DIR__);
if (file_exists($root_path . '/config/config.php')) {
    require_once $root_path . '/config/config.php';
}

include_once __DIR__ . '/../app/models/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    $debugInfo = [];
    if (defined('APP_DEBUG') && APP_DEBUG) {
        $debugInfo['db_error'] = method_exists($database, 'getLastError') ? $database->getLastError() : 'no_error_info';
    }
    echo json_encode(array_merge(['success' => false, 'message' => 'Error de conexión a la base de datos'], $debugInfo));
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$nombre_completo = trim($_POST['nombre_completo'] ?? '');

$errors = [];
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email inválido';
}
if (empty($password) || strlen($password) < 6) {
    $errors[] = 'La contraseña debe tener al menos 6 caracteres';
}
if ($password !== $confirm) {
    $errors[] = 'Las contraseñas no coinciden';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode('. ', $errors)]);
    exit;
}

try {
    // Verificar email único
    $sql = 'SELECT id FROM usuarios WHERE email = :email LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
        exit;
    }

    // Generar username desde email y asegurar unicidad
    $username_base = strtolower(explode('@', $email)[0]);
    $username = $username_base;
    $counter = 1;
    while (true) {
        $q = 'SELECT id FROM usuarios WHERE username = :username LIMIT 1';
        $s = $db->prepare($q);
        $s->bindParam(':username', $username);
        $s->execute();
        if ($s->rowCount() === 0) break;
        $username = $username_base . $counter;
        $counter++;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar el usuario (sin cliente_id)
    $insert = "INSERT INTO usuarios (username, email, password_hash, nombre_completo, rol_id, estado, created_at, updated_at)
               VALUES (:username, :email, :password_hash, :nombre_completo, 5, true, NOW(), NOW()) RETURNING id";
    $ins = $db->prepare($insert);
    $ins->bindParam(':username', $username);
    $ins->bindParam(':email', $email);
    $ins->bindParam(':password_hash', $password_hash);
    $ins->bindParam(':nombre_completo', $nombre_completo);
    $ins->execute();
    $row = $ins->fetch(PDO::FETCH_ASSOC);
    $user_id = $row['id'] ?? null;

    if ($user_id) {
        // Preparar URL de inicio (usar BASE_URL si está definida)
        if (defined('BASE_URL') && !empty(BASE_URL)) {
            $redirect = rtrim(BASE_URL, '/') . '/app/views/layouts/inicio.php';
        } else {
            $redirect = '/app/views/layouts/inicio.php';
        }

        echo json_encode([
            'success' => true,
            'message' => 'Usuario registrado correctamente',
            'user_id' => $user_id,
            'redirect' => $redirect
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo obtener el id del nuevo usuario']);
        exit;
    }

} catch (PDOException $e) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en el registro']);
    }
    exit;
}

?>