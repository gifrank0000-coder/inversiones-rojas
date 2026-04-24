<?php
// update_reserva.php
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

    // Obtener datos del POST
    $reserva_id = !empty($_POST['reserva_id']) ? (int)$_POST['reserva_id'] : null;
    $codigo_reserva = trim($_POST['codigo_reserva'] ?? '');
    $cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
    $producto_id = !empty($_POST['producto_id']) ? (int)$_POST['producto_id'] : null;
    $cantidad = !empty($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
    $fecha_limite = trim($_POST['fecha_limite'] ?? '');
    $estado_reserva = trim($_POST['estado_reserva'] ?? 'ACTIVA');
    $observaciones = trim($_POST['observaciones'] ?? '');

    // Si no se proporcionó el ID, intentar buscarlo por código
    if (empty($reserva_id) && $codigo_reserva !== '') {
        $stmtCodigo = $conn->prepare("SELECT id FROM reservas WHERE codigo_reserva = ? LIMIT 1");
        $stmtCodigo->execute([$codigo_reserva]);
        $fila = $stmtCodigo->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            $reserva_id = (int)$fila['id'];
        }
    }

    error_log("=== DATOS RECIBIDOS EN UPDATE_RESERVA ===");
    error_log("reserva_id: $reserva_id");
    error_log("cliente_id: $cliente_id");
    error_log("producto_id: $producto_id");
    error_log("cantidad: $cantidad");
    error_log("fecha_limite: $fecha_limite");
    error_log("estado_reserva: $estado_reserva");
    error_log("observaciones: $observaciones");

    // Validaciones básicas
    $errors = [];

    // Reserva ID
    if (empty($reserva_id)) {
        $errors['reserva_id'] = 'El ID de la reserva es requerido';
    } else {
        $stmtReservaExist = $conn->prepare("SELECT id FROM reservas WHERE id = ?");
        $stmtReservaExist->execute([$reserva_id]);
        if (!$stmtReservaExist->fetch()) {
            $errors['reserva_id'] = 'La reserva no existe';
        }
    }

    // Cliente
    if (empty($cliente_id)) {
        $errors['cliente_id'] = 'El cliente es requerido';
    } else {
        $stmtCliente = $conn->prepare("SELECT id FROM clientes WHERE id = ? AND estado = true");
        $stmtCliente->execute([$cliente_id]);
        if (!$stmtCliente->fetch()) {
            $errors['cliente_id'] = 'El cliente seleccionado no existe o está inactivo';
        }
    }

    // Producto
    if (empty($producto_id)) {
        $errors['producto_id'] = 'El producto es requerido';
    } else {
        $stmtProducto = $conn->prepare("SELECT id, nombre, stock_actual FROM productos WHERE id = ? AND estado = true");
        $stmtProducto->execute([$producto_id]);
        $producto_data = $stmtProducto->fetch(PDO::FETCH_ASSOC);

        if (!$producto_data) {
            $errors['producto_id'] = 'El producto seleccionado no existe o está inactivo';
        } else {
            // Verificar stock disponible (solo si no es una reserva existente con el mismo producto)
            $stmtReservaActual = $conn->prepare("SELECT cantidad FROM reservas WHERE id = ? AND producto_id = ?");
            $stmtReservaActual->execute([$reserva_id, $producto_id]);
            $reserva_actual = $stmtReservaActual->fetch(PDO::FETCH_ASSOC);

            $stock_disponible = $producto_data['stock_actual'];
            if ($reserva_actual) {
                $stock_disponible += $reserva_actual['cantidad']; // Liberar el stock actual de la reserva
            }

            if ($stock_disponible < $cantidad) {
                $errors['cantidad'] = "Stock insuficiente. Disponible: {$stock_disponible} unidad(es)";
            }
        }
    }

    // Cantidad
    if ($cantidad <= 0) {
        $errors['cantidad'] = 'La cantidad debe ser mayor a 0';
    }

    // Fecha límite
    if (empty($fecha_limite)) {
        $errors['fecha_limite'] = 'La fecha límite es requerida';
    } else {
        $fecha_limite_obj = DateTime::createFromFormat('Y-m-d', $fecha_limite);
        if (!$fecha_limite_obj) {
            $errors['fecha_limite'] = 'Formato de fecha inválido';
        } else {
            $hoy = new DateTime();
            if ($fecha_limite_obj <= $hoy) {
                $errors['fecha_limite'] = 'La fecha límite debe ser posterior a hoy';
            }
        }
    }

    // Estado
    $estados_validos = ['ACTIVA', 'COMPLETADA', 'CANCELADA', 'PRORROGADA'];
    if (!in_array($estado_reserva, $estados_validos)) {
        $errors['estado_reserva'] = 'Estado de reserva inválido';
    }

    // Si hay errores, retornarlos
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'errors' => $errors]);
        error_log('ERROR: Errores de validación: ' . print_r($errors, true));
        exit;
    }

    // INICIAR TRANSACCIÓN
    $conn->beginTransaction();
    error_log('=== TRANSACCIÓN INICIADA ===');

    try {
        // Actualizar reserva
        $sqlUpdate = "UPDATE reservas SET
            cliente_id = ?,
            producto_id = ?,
            cantidad = ?,
            fecha_limite = ?,
            estado_reserva = ?,
            observaciones = ?,
            updated_at = NOW()
        WHERE id = ?";

        error_log("Ejecutando UPDATE en reservas");
        $stmtUpdate = $conn->prepare($sqlUpdate);

        $params = [
            $cliente_id,
            $producto_id,
            $cantidad,
            $fecha_limite,
            $estado_reserva,
            $observaciones,
            $reserva_id
        ];

        error_log("Parámetros update: " . json_encode($params));
        $stmtUpdate->execute($params);
        checkPostgresError($stmtUpdate, "actualizar_reserva", $conn);

        // Verificar que se actualizó al menos una fila
        if ($stmtUpdate->rowCount() === 0) {
            throw new Exception('No se pudo actualizar la reserva');
        }

        // Registrar en bitácora
        try {
            $stmtBitacora = $conn->prepare(
                "INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at)
                 VALUES (?, 'ACTUALIZAR_RESERVA', 'reservas', ?, ?::jsonb, NOW())"
            );
            $stmtBitacora->execute([
                $_SESSION['user_id'],
                $reserva_id,
                json_encode(['cliente_id' => $cliente_id, 'producto_id' => $producto_id, 'estado' => $estado_reserva])
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
            'message' => 'Reserva actualizada exitosamente',
            'data' => [
                'reserva_id' => $reserva_id,
                'cliente_id' => $cliente_id,
                'producto_id' => $producto_id,
                'cantidad' => $cantidad,
                'fecha_limite' => $fecha_limite,
                'estado_reserva' => $estado_reserva
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