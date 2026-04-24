<?php
// /inversiones-rojas/api/add_cliente.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar solicitudes OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'No autorizado. Por favor inicie sesión.'
    ]);
    exit();
}

// Incluir conexión a la base de datos
require_once __DIR__ . '/../app/models/database.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Método no permitido. Use POST.'
    ]);
    exit();
}

// Obtener y validar datos del POST
$cedula_rif = isset($_POST['cedula_rif']) ? trim($_POST['cedula_rif']) : '';
$nombre_completo = isset($_POST['nombre_completo']) ? trim($_POST['nombre_completo']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : null;
$telefono_principal = isset($_POST['telefono_principal']) ? trim($_POST['telefono_principal']) : '';
$telefono_alternativo = isset($_POST['telefono_alternativo']) ? trim($_POST['telefono_alternativo']) : null;
$direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : null;
$usuario_id = $_SESSION['user_id'];

// Validaciones básicas
if (empty($cedula_rif)) {
    echo json_encode([
        'success' => false, 
        'message' => 'La cédula/RIF es obligatoria'
    ]);
    exit();
}

if (empty($nombre_completo)) {
    echo json_encode([
        'success' => false, 
        'message' => 'El nombre completo es obligatorio'
    ]);
    exit();
}

if (empty($telefono_principal)) {
    echo json_encode([
        'success' => false, 
        'message' => 'El teléfono principal es obligatorio'
    ]);
    exit();
}

// Validar formato de email si se proporciona
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false, 
        'message' => 'El formato del email no es válido'
    ]);
    exit();
}

try {
    $pdo = Database::getInstance();
    
    // Verificar si el cliente ya existe (por cédula/RIF)
    $stmt = $pdo->prepare("SELECT id, nombre_completo FROM clientes WHERE cedula_rif = ? AND estado = true");
    $stmt->execute([$cedula_rif]);
    $cliente_existente = $stmt->fetch();
    
    if ($cliente_existente) {
        echo json_encode([
            'success' => false, 
            'message' => 'Ya existe un cliente registrado con la cédula/RIF: ' . $cedula_rif . 
                        '. Nombre: ' . $cliente_existente['nombre_completo']
        ]);
        exit();
    }
    
    // Insertar nuevo cliente con todos los campos necesarios
    $sql = "INSERT INTO clientes (
        cedula_rif, 
        nombre_completo, 
        email, 
        telefono_principal, 
        telefono_alternativo, 
        direccion, 
        fecha_registro, 
        estado, 
        usuario_id, 
        created_at, 
        updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE, true, ?, NOW(), NOW()) 
    RETURNING id, cedula_rif, nombre_completo, email, telefono_principal, telefono_alternativo, direccion";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $cedula_rif,
        $nombre_completo,
        $email,
        $telefono_principal,
        $telefono_alternativo,
        $direccion,
        $usuario_id
    ]);
    
    $cliente = $stmt->fetch();
    
    if (!$cliente) {
        throw new Exception('No se pudo recuperar el cliente recién insertado');
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Cliente registrado exitosamente',
        'cliente' => [
            'id' => (int)$cliente['id'],
            'cedula_rif' => $cliente['cedula_rif'],
            'nombre_completo' => $cliente['nombre_completo'],
            'telefono_principal' => $cliente['telefono_principal'],
            'telefono_alternativo' => $cliente['telefono_alternativo'],
            'email' => $cliente['email'],
            'direccion' => $cliente['direccion']
        ]
    ]);
    
} catch (PDOException $e) {
    // Error específico de base de datos
    error_log('Error PDO al registrar cliente: ' . $e->getMessage());
    
    // Mensaje amigable según el tipo de error
    if (strpos($e->getMessage(), 'unique constraint') !== false) {
        $message = 'Ya existe un cliente con esta cédula/RIF';
    } else {
        $message = 'Error en la base de datos al registrar el cliente';
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'error' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    // Error general
    error_log('Error general al registrar cliente: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ]);
}
?>