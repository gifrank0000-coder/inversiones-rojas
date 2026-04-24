<?php
// user_action.php - Versión simplificada
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../models/database.php';

// Verificar autenticación: permitir a cualquier usuario autenticado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado. Por favor inicie sesión.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Depuración: loguear payload entrante cuando APP_DEBUG está activo
if (defined('APP_DEBUG') && APP_DEBUG) {
    $logDir = __DIR__ . '/../../../logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logFile = $logDir . '/user_action_debug.log';
    $entry = "----\n" . date('Y-m-d H:i:s') . "\n";
    $entry .= "SESSION_USER_ID=" . ($_SESSION['user_id'] ?? 'null') . "\n";
    $entry .= "RAW_INPUT=" . file_get_contents('php://input') . "\n";
    $entry .= "POST=" . json_encode($_POST) . "\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

$action = $_POST['action'] ?? '';
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

try {
    $usuario = null;

    // Para acciones distintas a 'create' necesitamos un id válido y el usuario existente
    if ($action !== 'create') {
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
            exit;
        }

        // Verificar que el usuario existe
        $stmt = $conn->prepare("SELECT id, username, email FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            exit;
        }

        // Evitar que un administrador se elimine a sí mismo (solo relevante para delete)
        if ($action === 'delete' && $id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'No puede eliminar su propio usuario']);
            exit;
        }
    }

    $conn->beginTransaction();

    switch ($action) {
            case 'create':
    

                // Recibir campos
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $nombre = trim($_POST['nombre_completo'] ?? '');
                $rol_id = isset($_POST['rol_id']) ? (int)$_POST['rol_id'] : null;
                $password = $_POST['password'] ?? '';
                $cedula_rif = $_POST['cedula_rif'] ?? '';
                $telefono_principal = $_POST['telefono_principal'] ?? '';
                $direccion = $_POST['direccion'] ?? '';

                if (empty($username) || empty($email) || empty($nombre) || !$rol_id) {
                    throw new Exception('Faltan campos obligatorios para crear el usuario');
                }

                if (empty($password) || strlen($password) < 8) {
                    throw new Exception('La contraseña debe tener al menos 8 caracteres');
                }

                // Verificar unicidad
                $q = $conn->prepare("SELECT id FROM usuarios WHERE username = :username OR email = :email");
                $q->execute(['username' => $username, 'email' => $email]);
                if ($q->rowCount() > 0) {
                    throw new Exception('El nombre de usuario o email ya están en uso');
                }

                // Insertar usuario
                $pwhash = password_hash($password, PASSWORD_BCRYPT);
                $ins = $conn->prepare('INSERT INTO usuarios (username, email, password_hash, nombre_completo, rol_id, created_at, updated_at) VALUES (:username, :email, :password_hash, :nombre, :rol_id, NOW(), NOW()) RETURNING id');
                $ins->execute([
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => $pwhash,
                    'nombre' => $nombre,
                    'rol_id' => $rol_id
                ]);

                $newRow = $ins->fetch(PDO::FETCH_ASSOC);
                $newId = $newRow['id'] ?? null;
                if (!$newId) {
                    throw new Exception('No se pudo crear el usuario');
                }

                // Si el rol es Cliente, crear o vincular cliente si se proveyó cédula
                $rstmt = $conn->prepare('SELECT nombre FROM roles WHERE id = :rid');
                $rstmt->execute(['rid' => $rol_id]);
                $rinfo = $rstmt->fetch(PDO::FETCH_ASSOC);
                $rolNombre = $rinfo['nombre'] ?? '';

                if (strtolower($rolNombre) === 'cliente' && !empty($cedula_rif)) {
                    $c = $conn->prepare('SELECT id FROM clientes WHERE cedula_rif = :cedula LIMIT 1');
                    $c->execute(['cedula' => $cedula_rif]);
                    if ($c->rowCount() > 0) {
                        $crow = $c->fetch(PDO::FETCH_ASSOC);
                        $cliente_id = (int)$crow['id'];
                    } else {
                        $ci = $conn->prepare('INSERT INTO clientes (cedula_rif, nombre_completo, email, telefono_principal, direccion, usuario_id, created_at, updated_at) VALUES (:cedula, :nombre, :email, :telefono, :direccion, :usuario_id, NOW(), NOW()) RETURNING id');
                        $ci->execute(['cedula' => $cedula_rif, 'nombre' => $nombre, 'email' => $email, 'telefono' => $telefono_principal, 'direccion' => $direccion, 'usuario_id' => $newId]);
                        $crow = $ci->fetch(PDO::FETCH_ASSOC);
                        $cliente_id = $crow['id'] ?? null;
                    }

                    if (!empty($cliente_id)) {
                        $uup = $conn->prepare('UPDATE usuarios SET cliente_id = :cliente_id WHERE id = :id');
                        $uup->execute(['cliente_id' => $cliente_id, 'id' => $newId]);
                    }
                }

                // Registrar en bitácora
                $b = $conn->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address) VALUES (:usuario_id, :accion, :tabla_afectada, :registro_id, :detalles, :ip_address)");
                $detalles = json_encode(['usuario_creado' => $username, 'rol_id' => $rol_id]);
                $b->execute([
                    'usuario_id' => $_SESSION['user_id'],
                    'accion' => 'CREAR_USUARIO',
                    'tabla_afectada' => 'usuarios',
                    'registro_id' => $newId,
                    'detalles' => $detalles,
                    'ip_address' => $_SERVER['REMOTE_ADDR']
                ]);

                $message = 'Usuario creado correctamente';
                $id = $newId;
                break;
            case 'update':
            
                // Recibir campos
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $nombre = $_POST['nombre_completo'] ?? '';
                $rol_id = isset($_POST['rol_id']) ? (int)$_POST['rol_id'] : null;
                $password = $_POST['password'] ?? null;
                $cedula_rif = $_POST['cedula_rif'] ?? '';
                $telefono_principal = $_POST['telefono_principal'] ?? '';
                $direccion = $_POST['direccion'] ?? '';

                if (empty($username) || empty($email) || empty($nombre) || !$rol_id) {
                    throw new Exception('Faltan campos obligatorios');
                }

                // Verificar unicidad (excluyendo id)
                $q = $conn->prepare("SELECT id FROM usuarios WHERE (username = :username OR email = :email) AND id != :id");
                $q->execute(['username' => $username, 'email' => $email, 'id' => $id]);
                if ($q->rowCount() > 0) {
                    throw new Exception('El nombre de usuario o email ya están en uso por otro usuario');
                }

                // Construir update
                $fields = 'username = :username, email = :email, nombre_completo = :nombre, rol_id = :rol_id, updated_at = NOW()';
                $params = [
                    'username' => $username,
                    'email' => $email,
                    'nombre' => $nombre,
                    'rol_id' => $rol_id,
                    'id' => $id
                ];

                if (!empty($password)) {
                    $fields .= ', password_hash = :password_hash';
                    $params['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
                }

                $upd = $conn->prepare("UPDATE usuarios SET $fields WHERE id = :id");
                $upd->execute($params);

                // Manejar cliente: si rol es cliente (buscar nombre del rol)
                $rstmt = $conn->prepare('SELECT nombre FROM roles WHERE id = :rid');
                $rstmt->execute(['rid' => $rol_id]);
                $rinfo = $rstmt->fetch(PDO::FETCH_ASSOC);
                $rolNombre = $rinfo['nombre'] ?? '';

                if (strtolower($rolNombre) === 'cliente') {
                    // Buscar o crear cliente si se proveyó cedula
                    if (!empty($cedula_rif)) {
                        $c = $conn->prepare('SELECT id FROM clientes WHERE cedula_rif = :cedula LIMIT 1');
                        $c->execute(['cedula' => $cedula_rif]);
                        if ($c->rowCount() > 0) {
                            $crow = $c->fetch(PDO::FETCH_ASSOC);
                            $cliente_id = (int)$crow['id'];
                        } else {
                            $ci = $conn->prepare('INSERT INTO clientes (cedula_rif, nombre_completo, email, telefono_principal, direccion, usuario_id, created_at, updated_at) VALUES (:cedula, :nombre, :email, :telefono, :direccion, :usuario_id, NOW(), NOW()) RETURNING id');
                            $ci->execute(['cedula' => $cedula_rif, 'nombre' => $nombre, 'email' => $email, 'telefono' => $telefono_principal, 'direccion' => $direccion, 'usuario_id' => $id]);
                            $crow = $ci->fetch(PDO::FETCH_ASSOC);
                            $cliente_id = $crow['id'] ?? null;
                        }
                        if (!empty($cliente_id)) {
                            $uup = $conn->prepare('UPDATE usuarios SET cliente_id = :cliente_id WHERE id = :id');
                            $uup->execute(['cliente_id' => $cliente_id, 'id' => $id]);
                        }
                    }
                } else {
                    // Desvincular cliente
                    $uup = $conn->prepare('UPDATE usuarios SET cliente_id = NULL WHERE id = :id');
                    $uup->execute(['id' => $id]);
                }

                $message = 'Usuario actualizado correctamente';
                break;
        case 'toggle_status':
            // Cambiar estado activo/inactivo (Eliminación LÓGICA)
            $stmt = $conn->prepare("UPDATE usuarios SET estado = NOT estado, updated_at = NOW() WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $message = 'Estado del usuario actualizado exitosamente';
            break;
            
        case 'delete':
            // ELIMINACIÓN LÓGICA (Recomendado)
            // 1. Desactivar el usuario
            $stmt = $conn->prepare("UPDATE usuarios SET estado = false, updated_at = NOW() WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // 2. Registrar en bitácora
            $stmt = $conn->prepare("
                INSERT INTO bitacora_sistema 
                (usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address) 
                VALUES (:usuario_id, :accion, :tabla_afectada, :registro_id, :detalles, :ip_address)
            ");
            
            $detalles = json_encode([
                'usuario_desactivado' => $usuario['username'],
                'email' => $usuario['email'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            $stmt->execute([
                'usuario_id' => $_SESSION['user_id'],
                'accion' => 'DESACTIVAR_USUARIO',
                'tabla_afectada' => 'usuarios',
                'registro_id' => $id,
                'detalles' => $detalles,
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            
            $message = 'Usuario desactivado exitosamente';
            break;
            
        default:
            throw new Exception('Acción no válida: ' . $action);
    }
    
    $conn->commit();
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'action' => $action,
        'user_id' => $id
    ]);
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>