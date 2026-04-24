<?php
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/models/database.php';

$pdo = Database::getInstance();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida. Inicie sesión.']);
    exit;
}

// Leer datos tanto de form-data como de JSON
$rawBody = file_get_contents('php://input');
$bodyData = [];
if (!empty($rawBody)) {
    $json = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        $bodyData = $json;
    }
}

$username = trim($bodyData['username'] ?? filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING) ?? '');
$nombre_completo = trim($bodyData['nombre_completo'] ?? filter_input(INPUT_POST, 'nombre_completo', FILTER_SANITIZE_STRING) ?? '');
$email = trim($bodyData['email'] ?? filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
$cedula = trim($bodyData['cedula'] ?? filter_input(INPUT_POST, 'cedula', FILTER_SANITIZE_STRING) ?? '');
$telefono = trim($bodyData['telefono'] ?? filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING) ?? '');
$current_password = trim($bodyData['current_password'] ?? filter_input(INPUT_POST, 'current_password', FILTER_SANITIZE_STRING) ?? '');
$new_password = trim($bodyData['new_password'] ?? filter_input(INPUT_POST, 'new_password', FILTER_SANITIZE_STRING) ?? '');
$confirm_password = trim($bodyData['confirm_password'] ?? filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_STRING) ?? '');

// Determinar si es una actualización de perfil normal o sólo cambio de contraseña
$updateProfileData = ($username !== '' || $nombre_completo !== '' || $email !== '');
$updatePassword = ($current_password !== '' || $new_password !== '' || $confirm_password !== '');

if ($updateProfileData) {
    if (empty($username) || empty($nombre_completo) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Los campos usuario, nombre y correo son obligatorios.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Correo electrónico inválido.']);
        exit;
    }
}

if ($updatePassword) {
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos de contraseña son obligatorios.']);
        exit;
    }
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden.']);
        exit;
    }
    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.']);
        exit;
    }
}

try {
    // Si se actualizarán datos de perfil (username/nombre/email), validar y escribirlos
    if ($updateProfileData) {
        // Verificar que username y email no estén en uso por otro usuario
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1");
        $stmt->execute([$username, $email, $user_id]);
        $conflict = $stmt->fetch();
        if ($conflict) {
            echo json_encode(['success' => false, 'message' => 'El nombre de usuario o correo ya está en uso.']);
            exit;
        }

        // Actualizar tabla usuarios
        $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, nombre_completo = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$username, $nombre_completo, $email, $user_id]);

        // Actualizar datos de sesión mínimos
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
    }

    // Cambiar contraseña si se proporcionó
    if ($updatePassword) {
        $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentHash = $row['password_hash'] ?? '';

        if (!password_verify($current_password, $currentHash)) {
            echo json_encode(['success' => false, 'message' => 'Contraseña actual incorrecta.']);
            exit;
        }

        $newHash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$newHash, $user_id]);
    }

    // Actualizar o insertar información en clientes relacionada (cedula/telefono) solo si se está editando perfil o se enviaron datos de cliente
    if ($updateProfileData || !empty($cedula) || !empty($telefono)) {
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $cliente = $stmt->fetch();
        if ($cliente) {
            $stmt = $pdo->prepare("UPDATE clientes SET cedula_rif = ?, telefono_principal = ?, updated_at = CURRENT_TIMESTAMP WHERE usuario_id = ?");
            $stmt->execute([$cedula, $telefono, $user_id]);
        } else {
            // Si se proporcionan cédula o teléfono, crear registro mínimo
            if (!empty($cedula) || !empty($telefono)) {
                $stmt = $pdo->prepare("INSERT INTO clientes (cedula_rif, nombre_completo, email, telefono_principal, usuario_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $stmt->execute([$cedula, $nombre_completo, $email, $telefono, $user_id]);
            }
        }
    }

    // Registrar acción en bitácora (si tabla existe)
    try {
        $log = $pdo->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $log->execute([$user_id, 'Actualizó su perfil', 'usuarios']);
    } catch (Exception $e) {
        // No interrumpir si la bitácora no existe
    }

    echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente.']);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
    exit;
}

?>
