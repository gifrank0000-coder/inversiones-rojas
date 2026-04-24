<?php
// add_reserva.php
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
    $cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
    $producto_id = !empty($_POST['producto_id']) ? (int)$_POST['producto_id'] : null;
    $cantidad = !empty($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
    $fecha_limite = trim($_POST['fecha_limite'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');

    error_log("=== DATOS RECIBIDOS EN ADD_RESERVA ===");
    error_log("cliente_id: $cliente_id");
    error_log("producto_id: $producto_id");
    error_log("cantidad: $cantidad");
    error_log("fecha_limite: $fecha_limite");
    error_log("observaciones: $observaciones");

    // Validaciones básicas
    $errors = [];

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
            // Verificar stock disponible
            if ($producto_data['stock_actual'] < $cantidad) {
                $errors['cantidad'] = "Stock insuficiente. Disponible: {$producto_data['stock_actual']} unidad(es)";
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
        // Generar código único de reserva
        do {
            $codigo = 'RES-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $stmtDup = $conn->prepare("SELECT 1 FROM reservas WHERE codigo_reserva = ? LIMIT 1");
            $stmtDup->execute([$codigo]);
        } while ($stmtDup->fetch());

        error_log("Código de reserva generado: $codigo");

        // Insertar reserva
        $sqlReserva = "INSERT INTO reservas (
            codigo_reserva, cliente_id, producto_id, cantidad,
            fecha_reserva, fecha_limite, estado_reserva, observaciones,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, NOW(), ?, 'PENDIENTE', ?, NOW(), NOW())";

        error_log("Ejecutando INSERT en reservas");
        $stmtReserva = $conn->prepare($sqlReserva);

        $params = [
            $codigo,
            $cliente_id,
            $producto_id,
            $cantidad,
            $fecha_limite,
            $observaciones
        ];

        error_log("Parámetros reserva: " . json_encode($params));
        $stmtReserva->execute($params);
        checkPostgresError($stmtReserva, "insertar_reserva", $conn);

        // Registrar en bitácora
        try {
            $stmtBitacora = $conn->prepare(
                "INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at)
                 VALUES (?, 'CREAR_RESERVA', 'reservas', 0, ?::jsonb, NOW())"
            );
            $stmtBitacora->execute([
                $_SESSION['user_id'],
                json_encode(['codigo' => $codigo, 'cliente_id' => $cliente_id, 'producto_id' => $producto_id])
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
            'message' => 'Reserva creada exitosamente',
            'data' => [
                'codigo_reserva' => $codigo,
                'cliente_id' => $cliente_id,
                'producto_id' => $producto_id,
                'cantidad' => $cantidad,
                'fecha_limite' => $fecha_limite,
                'estado_reserva' => 'PENDIENTE'
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