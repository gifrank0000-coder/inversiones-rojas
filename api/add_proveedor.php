<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'No autenticado']); exit; }

require_once __DIR__ . '/../app/models/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Método no permitido']); exit; }

    $razon = trim($_POST['razon_social'] ?? '');
    $rif = trim($_POST['rif'] ?? '');
    $contacto = trim($_POST['persona_contacto'] ?? '');
    $telefono = trim($_POST['telefono_principal'] ?? '');
    $telefonoAlt = trim($_POST['telefono_alternativo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    $errors = [];
    if (empty($razon)) $errors['razon_social'] = 'Razón social requerida';
    if (empty($rif)) $errors['rif'] = 'RIF requerido';

    if (!empty($errors)) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Errores de validación','errors'=>$errors]); exit; }

    $db = new Database(); $conn = $db->getConnection(); if (!$conn) throw new Exception('No se pudo conectar a la base de datos');

    // Verificar duplicados por rif
    $stmt = $conn->prepare('SELECT id FROM proveedores WHERE rif = ?');
    $stmt->execute([$rif]);
    if ($stmt->fetch()) { http_response_code(409); echo json_encode(['success'=>false,'message'=>'Ya existe un proveedor con ese RIF','errors'=>['rif'=>'RIF ya registrado']]); exit; }

    $sql = "INSERT INTO proveedores (razon_social, rif, persona_contacto, telefono_principal, telefono_alternativo, email, direccion, estado, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, true, NOW()) RETURNING id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$razon, $rif, $contacto, $telefono, $telefonoAlt, $email, $direccion]);
    $newId = $stmt->fetchColumn();

    if (!$newId) { throw new Exception('No se pudo crear proveedor'); }

    echo json_encode(['success'=>true,'id'=>$newId,'razon_social'=>$razon,'rif'=>$rif]);
    exit;

} catch (Exception $e) {
    error_log('ERROR add_proveedor.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error interno del servidor']);
    exit;
}

?><?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'No autenticado']); exit; }

require_once __DIR__ . '/../app/models/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); echo json_encode(['success'=>false,'message'=>'Método no permitido']); exit;
    }

    $razon = trim($_POST['razon_social'] ?? '');
    $rif = trim($_POST['rif'] ?? '');
    $persona_contacto = trim($_POST['persona_contacto'] ?? '');
    $telefono_principal = trim($_POST['telefono_principal'] ?? '');
    $telefono_alternativo = trim($_POST['telefono_alternativo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    $errors = [];
    if ($razon === '') $errors['razon_social'] = 'Razón social requerida';
    if ($rif === '') $errors['rif'] = 'RIF requerido';
    if (strlen($razon) > 200) $errors['razon_social'] = 'Razón social demasiado larga';
    if (strlen($rif) > 20) $errors['rif'] = 'RIF demasiado largo';

    if (!empty($errors)) { http_response_code(422); echo json_encode(['success'=>false,'errors'=>$errors]); exit; }

    $db = new Database(); $conn = $db->getConnection(); if (!$conn) throw new Exception('DB error');

    // Verificar duplicado por RIF
    $check = $conn->prepare('SELECT id, razon_social, rif FROM proveedores WHERE LOWER(rif) = LOWER(?) LIMIT 1');
    $check->execute([$rif]);
    $exist = $check->fetch();
    if ($exist) {
        // retornar existente para selección
        echo json_encode(['success'=>true,'id'=>(int)$exist['id'],'razon_social'=>$exist['razon_social'],'rif'=>$exist['rif'],'created'=>false,'message'=>'Proveedor ya existe']);
        exit;
    }

    $ins = $conn->prepare('INSERT INTO proveedores (razon_social, rif, persona_contacto, telefono_principal, telefono_alternativo, email, direccion, estado, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, true, NOW(), NOW()) RETURNING id');
    $ins->execute([$razon, $rif, $persona_contacto, $telefono_principal, $telefono_alternativo, $email, $direccion]);
    $newId = $ins->fetchColumn();

    // Bitacora
    try {
        $bit_sql = "INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address, user_agent, created_at) VALUES (?, 'CREAR_PROVEEDOR', 'proveedores', ?, ?, ?, ?, NOW())";
        $bit_stmt = $conn->prepare($bit_sql);
        $detalles = json_encode(['razon_social' => $razon, 'rif' => $rif]);
        $bit_stmt->execute([$_SESSION['user_id'], $newId, $detalles, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
    } catch (Exception $e) { error_log('Warning bitacora crear proveedor: '.$e->getMessage()); }

    http_response_code(201);
    echo json_encode(['success'=>true,'id'=>(int)$newId,'razon_social'=>$razon,'rif'=>$rif,'created'=>true]);
    exit;

} catch (Exception $e) {
    error_log('Error add_proveedor.php: '.$e->getMessage());
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'Error interno']); exit;
}

?>
