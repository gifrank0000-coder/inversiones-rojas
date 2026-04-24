<?php
// delete_reserva.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit;
}

// Incluir conexión a base de datos
require_once __DIR__ . '/../app/models/database.php';

// Función para verificar errores de PostgreSQL
function checkPostgresError($stmt, $query_name, $conn) {
    $errorInfo = $stmt->errorInfo();
    if ($errorInfo[0] !== '00000') {
        error_log("ERROR en $query_name:");
        error_log("SQLSTATE: " . $errorInfo[0]);
        error_log("Código: " . $errorInfo[1]);
        error_log("Mensaje: " . $errorInfo[2]);

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => "Error en $query_name: " . $errorInfo[2]
        ]);
        exit;
    }
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }

    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
        exit;
    }

    // Obtener ID de la reserva
    $reserva_id = !empty($_POST['reserva_id']) ? (int)$_POST['reserva_id'] : null;

    if (empty($reserva_id)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'ID de reserva requerido']);
        exit;
    }

    error_log("=== INHABILITANDO RESERVA ID: $reserva_id ===");

    // Verificar que la reserva existe
    $stmtCheck = $conn->prepare("SELECT id, estado_reserva, codigo_reserva FROM reservas WHERE id = ?");
    $stmtCheck->execute([$reserva_id]);
    $reserva = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Reserva no encontrada']);
        exit;
    }

    // Verificar que no esté ya cancelada
    if ($reserva['estado_reserva'] === 'CANCELADA') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'La reserva ya está cancelada']);
        exit;
    }

    // INICIAR TRANSACCIÓN
    $conn->beginTransaction();
    error_log('=== TRANSACCIÓN INICIADA ===');

    try {
        // Cambiar estado a CANCELADA
        $sqlUpdate = "UPDATE reservas SET
            estado_reserva = 'CANCELADA',
            updated_at = NOW()
        WHERE id = ?";

        error_log("Ejecutando UPDATE para cancelar reserva");
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->execute([$reserva_id]);
        checkPostgresError($stmtUpdate, "cancelar_reserva", $conn);

        // Verificar que se actualizó al menos una fila
        if ($stmtUpdate->rowCount() === 0) {
            throw new Exception('No se pudo cancelar la reserva');
        }

        // Registrar en bitácora
        try {
            $stmtBitacora = $conn->prepare(
                "INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at)
                 VALUES (?, 'CANCELAR_RESERVA', 'reservas', ?, ?::jsonb, NOW())"
            );
            $stmtBitacora->execute([
                $_SESSION['user_id'],
                $reserva_id,
                json_encode(['codigo' => $reserva['codigo_reserva'], 'estado_anterior' => $reserva['estado_reserva']])
            ]);
        } catch (Exception $e) {
            error_log('Error al registrar en bitácora: ' . $e->getMessage());
        }

        // COMMIT TRANSACCIÓN
        $conn->commit();
        error_log('=== TRANSACCIÓN COMPLETADA ===');

        // Respuesta exitosa
        echo json_encode([
            'ok' => true,
            'message' => 'Reserva cancelada exitosamente',
            'data' => [
                'reserva_id' => $reserva_id,
                'codigo_reserva' => $reserva['codigo_reserva'],
                'estado_anterior' => $reserva['estado_reserva'],
                'estado_nuevo' => 'CANCELADA'
            ]
        ]);

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('ERROR en transacción: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error interno del servidor: ' . $e->getMessage()]);
    }

} catch (Exception $e) {
    error_log('ERROR general: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno del servidor']);
}
?>