<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/models/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    $tipo = trim($_POST['tipo'] ?? '');
    $banco = trim($_POST['banco'] ?? '');
    $codigo_banco = trim($_POST['codigo_banco'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $numero_cuenta = trim($_POST['numero_cuenta'] ?? '');

    if (empty($tipo)) {
        throw new Exception('El tipo de método es obligatorio');
    }

    // Validaciones según tipo
    if ($tipo === 'pago_movil') {
        if (empty($banco) || empty($codigo_banco) || empty($cedula) || empty($telefono)) {
            throw new Exception('Para Pago Móvil se requieren: banco, código de banco, cédula y teléfono');
        }
    } elseif ($tipo === 'transferencia') {
        if (empty($cedula) || empty($numero_cuenta) || empty($banco)) {
            throw new Exception('Para Transferencia se requieren: cédula, número de cuenta y banco');
        }
    } else {
        // Para otros tipos, al menos banco y cédula
        if (empty($banco) || empty($cedula)) {
            throw new Exception('Se requieren al menos banco y cédula');
        }
    }

    $stmt = $conn->prepare("INSERT INTO metodos_pago_reservas (tipo, banco, codigo_banco, cedula, telefono, numero_cuenta) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$tipo, $banco, $codigo_banco, $cedula, $telefono, $numero_cuenta]);

    echo json_encode(['ok' => true, 'id' => $conn->lastInsertId()]);
} catch (Exception $e) {
    error_log('ERROR: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
exit;
?>