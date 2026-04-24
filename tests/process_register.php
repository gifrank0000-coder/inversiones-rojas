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
$cedula_rif = trim($_POST['cedula_rif'] ?? '');
$telefono_principal = trim($_POST['telefono_principal'] ?? '');
$username_post = trim($_POST['username'] ?? '');
$doc_type = trim($_POST['doc_type'] ?? '');

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
if (empty($doc_type) || !in_array($doc_type, ['V', 'J'])) {
    $errors[] = 'Tipo de documento inválido';
}
if (empty($cedula_rif)) {
    $errors[] = 'Número de documento requerido';
}
// Validar formato del documento
$clean_cedula = preg_replace('/[^\d]/', '', $cedula_rif);
if ($doc_type === 'V' && (!preg_match('/^\d{7,8}$/', $clean_cedula))) {
    $errors[] = 'Cédula debe tener 7-8 dígitos';
} elseif ($doc_type === 'J' && (!preg_match('/^\d{9}$/', $clean_cedula))) {
    $errors[] = 'RIF debe tener 9 dígitos';
}
if (empty($telefono_principal) || !preg_match('/^04\d{2}-\d{7}$/', $telefono_principal)) {
    $errors[] = 'Teléfono inválido (formato: 0412-1234567)';
}
if (empty($nombre_completo)) {
    $errors[] = 'Nombre completo requerido';
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

    // Verificar cédula/RIF único
    $formatted_cedula_check = $doc_type . '-' . $clean_cedula;
    $cedula_sql = 'SELECT id FROM clientes WHERE cedula_rif = :cedula_rif LIMIT 1';
    $cedula_stmt = $db->prepare($cedula_sql);
    $cedula_stmt->bindParam(':cedula_rif', $formatted_cedula_check);
    $cedula_stmt->execute();
    if ($cedula_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'El documento ya está registrado']);
        exit;
    }

    // Determinar username: usar el enviado por el formulario si existe, sino generar desde el email
    if (!empty($username_post)) {
        // Normalizar username (permitir letras, números, guión y guión bajo)
        $base = preg_replace('/[^a-zA-Z0-9_\-]/', '', strtolower($username_post));
        $username = substr($base, 0, 50);
        $counter = 1;
        // Asegurar unicidad
        while (true) {
            $q = 'SELECT id FROM usuarios WHERE username = :username LIMIT 1';
            $s = $db->prepare($q);
            $s->bindParam(':username', $username);
            $s->execute();
            if ($s->rowCount() === 0) break;
            $username = substr($base, 0, 45) . $counter;
            $counter++;
        }
    } else {
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
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    // Insertar el usuario en la tabla `usuarios` (registro público)
    $db->beginTransaction();
    try {
        $insert = "INSERT INTO usuarios (username, email, password_hash, nombre_completo, rol_id, estado, created_at, updated_at)
                   VALUES (:username, :email, :password_hash, :nombre_completo, 3, true, NOW(), NOW()) RETURNING id";
        $ins = $db->prepare($insert);
        $ins->bindParam(':username', $username);
        $ins->bindParam(':email', $email);
        $ins->bindParam(':password_hash', $password_hash);
        $ins->bindParam(':nombre_completo', $nombre_completo);
        $ins->execute();
        $row = $ins->fetch(PDO::FETCH_ASSOC);
        $user_id = $row['id'] ?? null;

        if (!$user_id) throw new Exception('No se pudo obtener el id del nuevo usuario');

        // Insertar en tabla clientes
        $formatted_cedula = $doc_type . '-' . $clean_cedula;
        $cliente_insert = "INSERT INTO clientes (cedula_rif, nombre_completo, email, telefono_principal, usuario_id, estado, created_at, updated_at)
                          VALUES (:cedula_rif, :nombre_completo, :email, :telefono_principal, :usuario_id, true, NOW(), NOW()) RETURNING id";
        $cliente_ins = $db->prepare($cliente_insert);
        $cliente_ins->bindParam(':cedula_rif', $formatted_cedula);
        $cliente_ins->bindParam(':nombre_completo', $nombre_completo);
        $cliente_ins->bindParam(':email', $email);
        $cliente_ins->bindParam(':telefono_principal', $telefono_principal);
        $cliente_ins->bindParam(':usuario_id', $user_id);
        $cliente_ins->execute();
        $cliente_row = $cliente_ins->fetch(PDO::FETCH_ASSOC);
        $cliente_id = $cliente_row['id'] ?? null;

        // Actualizar usuario con cliente_id
        $update_user = "UPDATE usuarios SET cliente_id = :cliente_id WHERE id = :user_id";
        $update_stmt = $db->prepare($update_user);
        $update_stmt->bindParam(':cliente_id', $cliente_id);
        $update_stmt->bindParam(':user_id', $user_id);
        $update_stmt->execute();

        // Registrar en bitácora (solo registro de usuario)
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $bstmt = $db->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address, created_at)
                                   VALUES (:usuario_id, :accion, :tabla_afectada, :registro_id, :detalles, :ip_address, NOW())");
            $bstmt->execute([
                'usuario_id' => $user_id,
                'accion' => 'REGISTRO_USUARIO',
                'tabla_afectada' => 'usuarios',
                'registro_id' => $user_id,
                'detalles' => json_encode(['username' => $username, 'email' => $email]),
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
        }

        $db->commit();

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

    } catch (Exception $ex) {
        $db->rollBack();
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $ex->getMessage()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error en el registro']);
        }
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