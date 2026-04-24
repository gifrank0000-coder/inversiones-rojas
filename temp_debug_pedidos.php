<?php
// temp_debug_pedidos.php - Debug script para verificar pedidos del usuario logueado
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/database.php';

if (!isset($_SESSION['user_id'])) {
    die("No hay sesión activa. Inicia sesión primero.\n");
}

$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Desconocido';
$email_sesion = $_SESSION['email'] ?? $_SESSION['user_email'] ?? '';

echo "=== DEBUG PEDIDOS PARA USUARIO ===\n";
echo "User ID: $user_id\n";
echo "User Name: $user_name\n";
echo "Email Sesión: $email_sesion\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Buscar cliente_id
    $cliente_id = null;

    // Método 1: clientes.usuario_id = sesión user_id
    $stmt = $conn->prepare("SELECT id, nombre_completo, email FROM clientes WHERE usuario_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cliente) {
        $cliente_id = $cliente['id'];
        echo "Cliente encontrado por usuario_id: ID=$cliente_id, Nombre={$cliente['nombre_completo']}, Email={$cliente['email']}\n";
    } else {
        echo "No se encontró cliente por usuario_id.\n";
    }

    // Método 2: usuarios.cliente_id
    if (!$cliente_id) {
        $stmt = $conn->prepare("SELECT cliente_id FROM usuarios WHERE id = ? AND cliente_id IS NOT NULL LIMIT 1");
        $stmt->execute([$user_id]);
        $cliente_id = $stmt->fetchColumn();
        if ($cliente_id) {
            echo "Cliente encontrado por usuarios.cliente_id: $cliente_id\n";
        } else {
            echo "No se encontró cliente por usuarios.cliente_id.\n";
        }
    }

    // Método 3: por email
    if (!$cliente_id && !empty($email_sesion)) {
        $stmt = $conn->prepare("SELECT id, nombre_completo FROM clientes WHERE email = ? LIMIT 1");
        $stmt->execute([$email_sesion]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente) {
            $cliente_id = $cliente['id'];
            echo "Cliente encontrado por email: ID=$cliente_id, Nombre={$cliente['nombre_completo']}\n";
        } else {
            echo "No se encontró cliente por email.\n";
        }
    }

    // Método 4: por nombre
    if (!$cliente_id && !empty($_SESSION['user_name'])) {
        $stmt = $conn->prepare("SELECT id, nombre_completo FROM clientes WHERE nombre_completo ILIKE ? LIMIT 1");
        $stmt->execute([$_SESSION['user_name']]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente) {
            $cliente_id = $cliente['id'];
            echo "Cliente encontrado por nombre: ID=$cliente_id, Nombre={$cliente['nombre_completo']}\n";
        } else {
            echo "No se encontró cliente por nombre.\n";
        }
    }

    if (!$cliente_id) {
        echo "\nERROR: No se pudo encontrar cliente_id para este usuario.\n";
        echo "Verifica que el usuario esté vinculado a un cliente en la tabla 'clientes'.\n";
        exit;
    }

    echo "\nCliente ID final: $cliente_id\n\n";

    // Buscar pedidos
    $stmt = $conn->prepare(
        "SELECT p.id, p.codigo_pedido, p.created_at, p.estado_pedido, p.total,
                COALESCE(p.canal_comunicacion,'web') AS canal_comunicacion,
                COALESCE(p.referencia_pago,'') AS referencia_pago
         FROM pedidos_online p
         WHERE p.cliente_id = ?
         ORDER BY p.created_at DESC"
    );
    $stmt->execute([$cliente_id]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== PEDIDOS ENCONTRADOS ===\n";
    if (empty($pedidos)) {
        echo "No se encontraron pedidos para cliente_id = $cliente_id\n";
        echo "Posibles causas:\n";
        echo "- Los pedidos no tienen cliente_id asignado.\n";
        echo "- Los pedidos están en otro cliente_id.\n";
        echo "- Los pedidos no existen en la tabla pedidos_online.\n";
    } else {
        echo "Total pedidos: " . count($pedidos) . "\n\n";
        foreach ($pedidos as $p) {
            echo "ID: {$p['id']}, Código: {$p['codigo_pedido']}, Estado: {$p['estado_pedido']}, Total: {$p['total']}, Canal: {$p['canal_comunicacion']}, Fecha: {$p['created_at']}\n";
        }
    }

    // Verificar si hay pedidos sin cliente_id asignado
    $stmt = $conn->prepare("SELECT COUNT(*) FROM pedidos_online WHERE cliente_id IS NULL");
    $stmt->execute();
    $pedidos_sin_cliente = $stmt->fetchColumn();
    echo "\nPedidos sin cliente_id asignado: $pedidos_sin_cliente\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>