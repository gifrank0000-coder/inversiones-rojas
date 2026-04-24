<?php
// /inversiones-rojas/api/toggle_venta_estado.php
// Inhabilita o reactiva una venta — reemplaza inhabilitar_venta.php
// y agrega la funcionalidad de reactivar que antes no existía.

if (session_status() === PHP_SESSION_NONE) session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// ── Autenticación ──────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado. Inicie sesión.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Use POST.']);
    exit;
}

// ── Leer input (JSON o FormData) ───────────────────────────────────────────
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
} else {
    $input = $_POST;
}

$venta_id    = (int) ($input['venta_id'] ?? 0);
$nuevo_estado = trim($input['estado'] ?? '');

// Estados permitidos
$estados_validos = ['INHABILITADO', 'COMPLETADA'];

if (!$venta_id) {
    echo json_encode(['success' => false, 'message' => 'ID de venta inválido o no proporcionado.']);
    exit;
}

if (!in_array($nuevo_estado, $estados_validos, true)) {
    echo json_encode([
        'success' => false,
        'message' => "Estado no permitido: '{$nuevo_estado}'. Use INHABILITADO o COMPLETADA."
    ]);
    exit;
}

// ── Base de datos ──────────────────────────────────────────────────────────
require_once __DIR__ . '/../app/models/database.php';

try {
    $pdo = Database::getInstance();

    if (!$pdo) {
        throw new Exception('No se pudo obtener conexión a la base de datos.');
    }

    // Verificar que la venta existe
    $stmt = $pdo->prepare("SELECT id, codigo_venta, estado_venta FROM ventas WHERE id = ?");
    $stmt->execute([$venta_id]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        echo json_encode(['success' => false, 'message' => "Venta ID {$venta_id} no encontrada."]);
        exit;
    }

    // Evitar cambio innecesario
    if ($venta['estado_venta'] === $nuevo_estado) {
        echo json_encode([
            'success' => true,
            'message' => "La venta ya se encuentra en estado {$nuevo_estado}.",
            'codigo_venta' => $venta['codigo_venta'],
            'estado_venta' => $nuevo_estado
        ]);
        exit;
    }

    // ── Actualizar estado — SIN updated_at para máxima compatibilidad ─────
    // Si tu tabla tiene updated_at, cambia la siguiente línea por:
    // UPDATE ventas SET estado_venta = ? , updated_at = NOW() WHERE id = ?
    $stmt_upd = $pdo->prepare("UPDATE ventas SET estado_venta = ? WHERE id = ?");
    $stmt_upd->execute([$nuevo_estado, $venta_id]);

    if ($stmt_upd->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'No se realizaron cambios en la base de datos.']);
        exit;
    }

    // ── Registrar en bitácora (no crítico) ──────────────────────────────
    try {
        $accion = $nuevo_estado === 'INHABILITADO' ? 'INHABILITAR_VENTA' : 'REACTIVAR_VENTA';
        $stmt_log = $pdo->prepare("
            INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at)
            VALUES (?, ?, 'ventas', ?, ?, NOW())
        ");
        $detalles = json_encode([
            'codigo_venta'    => $venta['codigo_venta'],
            'estado_anterior' => $venta['estado_venta'],
            'estado_nuevo'    => $nuevo_estado
        ]);
        $stmt_log->execute([$_SESSION['user_id'], $accion, $venta_id, $detalles]);
    } catch (Exception $e) {
        // La bitácora no debe detener la operación
        error_log('toggle_venta_estado bitácora: ' . $e->getMessage());
    }

    $mensaje = $nuevo_estado === 'INHABILITADO'
        ? "Venta {$venta['codigo_venta']} inhabilitada correctamente."
        : "Venta {$venta['codigo_venta']} reactivada correctamente.";

    echo json_encode([
        'success'      => true,
        'message'      => $mensaje,
        'venta_id'     => $venta_id,
        'codigo_venta' => $venta['codigo_venta'],
        'estado_venta' => $nuevo_estado
    ]);

} catch (PDOException $e) {
    error_log('toggle_venta_estado PDO: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('toggle_venta_estado: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}
?>