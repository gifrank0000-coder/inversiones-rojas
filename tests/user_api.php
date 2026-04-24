<?php
session_start();
header('Content-Type: application/json');

$root_path = dirname(__DIR__);
if (file_exists($root_path . '/config/config.php')) require_once $root_path . '/config/config.php';
require_once __DIR__ . '/../app/models/database.php';

$dbObj = new Database();
$db = $dbObj->getConnection();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// GET ?id= -> devuelve usuario con datos de cliente
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'ID inválido']); exit; }
    try {
        $sql = "SELECT u.id, u.username, u.email, u.nombre_completo, u.rol_id, c.id as cliente_id, c.cedula_rif, c.telefono_principal
                FROM usuarios u
                LEFT JOIN clientes c ON c.usuario_id = u.id
                WHERE u.id = :id LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) { echo json_encode(['success'=>false,'message'=>'Usuario no encontrado']); exit; }
        echo json_encode(['success'=>true,'user'=>$user]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success'=>false,'message'=>'Error de BD: '.$e->getMessage()]);
        exit;
    }
}

// POST -> actualizar usuario (necesita user_id)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_GET['id'] ?? ($_POST['user_id'] ?? 0));
    if ($user_id <= 0) { echo json_encode(['success'=>false,'message'=>'ID de usuario requerido']); exit; }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $cedula_rif = trim($_POST['cedula_rif'] ?? '');
    $telefono_principal = trim($_POST['telefono_principal'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($nombre_completo)) {
        echo json_encode(['success'=>false,'message'=>'Campos obligatorios faltantes']); exit;
    }

    try {
        // Verificar unicidad username/email (excluyendo este usuario)
        $q = $db->prepare('SELECT id FROM usuarios WHERE (username = :username OR email = :email) AND id <> :id LIMIT 1');
        $q->execute(['username'=>$username,'email'=>$email,'id'=>$user_id]);
        if ($q->rowCount() > 0) { echo json_encode(['success'=>false,'message'=>'Username o email ya en uso']); exit; }

        $db->beginTransaction();

        // Actualizar usuarios
        if (!empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $up = $db->prepare('UPDATE usuarios SET username=:username, email=:email, password_hash=:password_hash, nombre_completo=:nombre_completo, rol_id=:rol_id, updated_at=NOW() WHERE id = :id');
            $up->execute(['username'=>$username,'email'=>$email,'password_hash'=>$password_hash,'nombre_completo'=>$nombre_completo,'rol_id'=>intval($_POST['rol_id'] ?? 5),'id'=>$user_id]);
        } else {
            $up = $db->prepare('UPDATE usuarios SET username=:username, email=:email, nombre_completo=:nombre_completo, rol_id=:rol_id, updated_at=NOW() WHERE id = :id');
            $up->execute(['username'=>$username,'email'=>$email,'nombre_completo'=>$nombre_completo,'rol_id'=>intval($_POST['rol_id'] ?? 5),'id'=>$user_id]);
        }

        // Actualizar o insertar cliente
        $c = $db->prepare('SELECT id FROM clientes WHERE usuario_id = :uid LIMIT 1');
        $c->execute(['uid'=>$user_id]);
        if ($c->rowCount() > 0) {
            $cid = $c->fetchColumn();
            $updateC = $db->prepare('UPDATE clientes SET cedula_rif=:cedula_rif, nombre_completo=:nombre_completo, email=:email, telefono_principal=:telefono_principal, updated_at=NOW() WHERE id = :id');
            $updateC->execute(['cedula_rif'=>$cedula_rif,'nombre_completo'=>$nombre_completo,'email'=>$email,'telefono_principal'=>$telefono_principal,'id'=>$cid]);
        } else {
            $insertC = $db->prepare('INSERT INTO clientes (cedula_rif, nombre_completo, email, telefono_principal, usuario_id, created_at, updated_at) VALUES (:cedula_rif, :nombre_completo, :email, :telefono_principal, :usuario_id, NOW(), NOW()) RETURNING id');
            $insertC->execute(['cedula_rif'=>$cedula_rif,'nombre_completo'=>$nombre_completo,'email'=>$email,'telefono_principal'=>$telefono_principal,'usuario_id'=>$user_id]);
            $cidRow = $insertC->fetch(PDO::FETCH_ASSOC);
            $cid = $cidRow['id'] ?? null;
            if ($cid) {
                $upd = $db->prepare('UPDATE usuarios SET cliente_id = :cliente_id WHERE id = :id');
                $upd->execute(['cliente_id'=>$cid,'id'=>$user_id]);
            }
        }

        // Bitácora opcional
        $bst = $db->prepare('INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address, created_at) VALUES (:uid, :accion, :tabla, :rid, :det, :ip, NOW())');
        $bst->execute(['uid'=>$_SESSION['user_id'] ?? null,'accion'=>'ACTUALIZAR_USUARIO','tabla'=>'usuarios','rid'=>$user_id,'det'=>json_encode(['username'=>$username,'email'=>$email]),'ip'=>$_SERVER['REMOTE_ADDR'] ?? '']);

        $db->commit();
        echo json_encode(['success'=>true,'message'=>'Usuario actualizado correctamente']); exit;
    } catch (Exception $ex) {
        $db->rollBack();
        echo json_encode(['success'=>false,'message'=>'Error: '.$ex->getMessage()]); exit;
    }
}

// Otros métodos no permitidos
http_response_code(405);
echo json_encode(['success'=>false,'message'=>'Método no permitido']);
exit;

?>