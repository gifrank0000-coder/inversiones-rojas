<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/models/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$cedula_rif = trim($_POST['cedula_rif'] ?? '');
$telefono_principal = trim($_POST['telefono_principal'] ?? '');
$rol_id = intval($_POST['rol_id'] ?? 0);
$password = $_POST['password'] ?? '';

$errors = [];

if (empty($username)) {
    $errors['username'] = 'El nombre de usuario es obligatorio';
}
if (strlen($username) < 3) {
    $errors['username'] = 'El usuario debe tener al menos 3 caracteres';
}
if (strlen($username) > 20) {
    $errors['username'] = 'El usuario no puede exceder 20 caracteres';
}
if (empty($email)) {
    $errors['email'] = 'El correo electrónico es obligatorio';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Correo electrónico inválido';
}
if (empty($nombre_completo)) {
    $errors['nombre_completo'] = 'El nombre completo es obligatorio';
}
if (empty($cedula_rif)) {
    $errors['cedula_rif'] = 'La cédula o RIF es obligatorio';
}
if (empty($rol_id)) {
    $errors['rol_id'] = 'El rol es obligatorio';
}
if (empty($password)) {
    $errors['password'] = 'La contraseña es obligatoria';
}
if (strlen($password) < 8) {
    $errors['password'] = 'La contraseña debe tener al menos 8 caracteres';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos', 'errors' => $errors]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Verificar username único
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya está en uso']);
        exit;
    }

    // Verificar email único
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está en uso']);
        exit;
    }

    // Verificar RIF único
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE cedula_rif = :cedula_rif LIMIT 1");
    $stmt->execute([':cedula_rif' => $cedula_rif]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'La cédula o RIF ya está registrado']);
        exit;
    }

    // Hash de contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (username, email, password_hash, nombre_completo, rol_id, estado, created_at) 
                            VALUES (:username, :email, :password_hash, :nombre_completo, :rol_id, true, NOW())");
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => $password_hash,
        ':nombre_completo' => $nombre_completo,
        ':rol_id' => $rol_id
    ]);

    $user_id = $conn->lastInsertId();

    // Si tiene cédula, crear cliente asociado
    if (!empty($cedula_rif)) {
        $stmtCliente = $conn->prepare("INSERT INTO clientes (cedula_rif, nombre_completo, telefono_principal, email, usuario_id, created_at) 
                                      VALUES (:cedula_rif, :nombre_completo, :telefono, :email, :usuario_id, NOW())");
        $stmtCliente->execute([
            ':cedula_rif' => $cedula_rif,
            ':nombre_completo' => $nombre_completo,
            ':telefono' => $telefono_principal,
            ':email' => $email,
            ':usuario_id' => $user_id
        ]);
    }

    // Bitácora
    $stmtBitacora = $conn->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at) 
                                    VALUES (:usuario_id, 'CREAR', 'usuarios', :registro_id, :detalles, NOW())");
    $stmtBitacora->execute([
        ':usuario_id' => $_SESSION['user_id'],
        ':registro_id' => $user_id,
        ':detalles' => json_encode(['username' => $username, 'email' => $email])
    ]);

    echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente', 'id' => $user_id]);

} catch (Exception $e) {
    error_log('ERROR add_usuario: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al crear usuario: ' . $e->getMessage()]);
}
