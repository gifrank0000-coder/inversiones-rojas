<?php
// ============================================================
// process_reserva.php  →  /api/process_reserva.php
// Crea una reserva (apartado) desde el carrito del cliente.
// Incluye:
// - Rate limiting (max 3 reservas/día/usuario)
// - Payment reference generation
// - Payment instructions
// - Rate limiting por IP
// ============================================================
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('process_reserva shutdown: ' . json_encode($err));
        if (ob_get_level()) ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
});

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/reserva_mail_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Debes iniciar sesión para crear una reserva']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

$input = [];
if (!empty($_POST)) {
    $input = $_POST;
} else {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }
}

if (isset($input['items']) && is_string($input['items'])) {
    $decodedItems = json_decode($input['items'], true);
    if (is_array($decodedItems)) {
        $input['items'] = $decodedItems;
    }
}

$telefono       = trim($input['telefono']      ?? '');
$observaciones = trim($input['observaciones'] ?? '');
$monto_adelanto = (float)($input['monto_adelanto'] ?? 0);
$fecha_cuota    = trim($input['fecha_cuota']    ?? '');
$referencia_pago = trim($input['referencia_pago'] ?? '');
$estado_pago    = trim($input['estado_pago']    ?? 'PENDIENTE');
$items_payload = $input['items']                ?? [];
$subtotal_input = (float)($input['subtotal']    ?? 0);
$iva_input     = (float)($input['iva']          ?? 0);
$total_input   = (float)($input['total']        ?? 0);
$metodo_pago_id = isset($input['metodo_pago_id']) ? (int)$input['metodo_pago_id'] : null;
$metodo_pago_nombre = trim($input['metodo_pago_nombre'] ?? '');
$metodo_pago_descripcion = trim($input['metodo_pago_descripcion'] ?? '');
$comprobante_file = $_FILES['comprobante'] ?? null;

// Obtener IP y User Agent para rate limiting
$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    $db   = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception('Sin conexión a la base de datos');

    $usuario_id = (int)$_SESSION['user_id'];
    $email     = $_SESSION['user_email'] ?? '';

    // Rate limiting COMENTADO para pruebas
    /*
    $stRateLimit = $conn->prepare("
        SELECT COUNT(*) as cnt 
        FROM reservas 
        WHERE cliente_id IN (
            SELECT id FROM clientes WHERE usuario_id = ? OR email = ?
        )
        AND fecha_reserva = CURRENT_DATE
    ");
    $stRateLimit->execute([$usuario_id, $email]);
    $rateData = $stRateLimit->fetch(PDO::FETCH_ASSOC);
    
    if ($rateData && (int)$rateData['cnt'] >= 3) {
        ob_end_clean();
        die(json_encode([
            'success' => false, 
            'message' => 'Has alcanzado el límite máximo de 3 reservas por día. Intenta nuevamente mañana.'
        ]));
    }
    */
    
    // ============================================================
    // RATE LIMITING: Max 10 reservas por día por IP (COMENTADO)
    // ====================================
    /*
    if ($ip_address && strlen($ip_address) < 40) {
        $stIpLimit = $conn->prepare("
            SELECT COUNT(*) as cnt 
            FROM reservas 
            WHERE ip_address = ? 
            AND fecha_reserva = CURRENT_DATE
        ");
        $stIpLimit->execute([$ip_address]);
        $ipData = $stIpLimit->fetch(PDO::FETCH_ASSOC);
        
        if ($ipData && (int)$ipData['cnt'] >= 10) {
            ob_end_clean();
            die(json_encode([
                'success' => false, 
                'message' => 'Demasiadas solicitudes desde tu conexión. Intenta más tarde.'
            ]));
        }
    }
    */
    
    // ── Obtener cliente del usuario logueado (por email primero, luego usuario_id) ──
    $cliente = null;
    $user_email = $_SESSION['user_email'] ?? '';

    // MÉTODO 1: Buscar por email del usuario logueado (más preciso)
    if (!empty($user_email)) {
        $st = $conn->prepare("SELECT id, nombre_completo, email, telefono_principal FROM clientes WHERE LOWER(email) = LOWER(?) AND estado = true LIMIT 1");
        $st->execute([$user_email]);
        $cliente = $st->fetch(PDO::FETCH_ASSOC);
    }

    // MÉTODO 2: Buscar por usuario_id
    if (!$cliente) {
        $st2 = $conn->prepare("SELECT id, nombre_completo, email, telefono_principal FROM clientes WHERE usuario_id = ? AND estado = true ORDER BY id DESC LIMIT 1");
        $st2->execute([$usuario_id]);
        $cliente = $st2->fetch(PDO::FETCH_ASSOC);
    }

    // MÉTODO 3: Buscar por cliente_id en tabla usuarios
    if (!$cliente) {
        $st3 = $conn->prepare("SELECT cliente_id FROM usuarios WHERE id = ?");
        $st3->execute([$usuario_id]);
        $row3 = $st3->fetch(PDO::FETCH_ASSOC);
        if ($row3 && $row3['cliente_id']) {
            $st4 = $conn->prepare("SELECT id, nombre_completo, email, telefono_principal FROM clientes WHERE id = ? AND estado = true");
            $st4->execute([$row3['cliente_id']]);
            $cliente = $st4->fetch(PDO::FETCH_ASSOC);
        }
    }

    if (!$cliente) {
        ob_end_clean();
        die(json_encode(['success' => false, 'message' => 'No se encontró tu perfil de cliente. Completa tu perfil primero.']));
    }

    // Debug: registrar cual cliente se usó
    error_log("Reserva para cliente: {$cliente['nombre_completo']} (ID: {$cliente['id']}) por usuario_id: $usuario_id");

    $cliente_id = (int)$cliente['id'];
    $cliente_email = $cliente['email'] ?? '';
    if (!$telefono && !empty($cliente['telefono_principal'])) {
        $telefono = $cliente['telefono_principal'];
    }

    // ── Obtener productos: del payload o de la sesión ─────────
    $productos_a_reservar = [];

    if (!empty($items_payload)) {
        foreach ($items_payload as $item) {
            $pid  = (int)($item['id']       ?? 0);
            $cant = (int)($item['cantidad'] ?? 0);
            if ($pid <= 0 || $cant <= 0) continue;
            $stP = $conn->prepare("SELECT id, nombre, codigo_interno, precio_venta, stock_actual FROM productos WHERE id = ? AND estado = true");
            $stP->execute([$pid]);
            $prow = $stP->fetch(PDO::FETCH_ASSOC);
            if (!$prow) {
                ob_end_clean();
                die(json_encode(['success' => false, 'message' => "Producto ID {$pid} no encontrado o inactivo"]));
            }
            $productos_a_reservar[] = [
                'id'           => $pid,
                'nombre'       => $prow['nombre'],
                'codigo_interno' => $prow['codigo_interno'] ?? '',
                'cantidad'     => $cant,
                'precio_venta' => (float)$prow['precio_venta'],
                'stock_actual' => (int)$prow['stock_actual'],
            ];
        }
    } elseif (!empty($_SESSION['carrito'])) {
        $ids  = array_keys($_SESSION['carrito']);
        $ph   = implode(',', array_fill(0, count($ids), '?'));
        $stAll = $conn->prepare("SELECT id, nombre, codigo_interno, precio_venta, stock_actual FROM productos WHERE id IN ({$ph}) AND estado = true");
        $stAll->execute($ids);
        foreach ($stAll->fetchAll(PDO::FETCH_ASSOC) as $prow) {
            $pid  = $prow['id'];
            $cant = (int)$_SESSION['carrito'][$pid]['quantity'];
            if ($cant <= 0) continue;
            $productos_a_reservar[] = [
                'id'           => $pid,
                'nombre'       => $prow['nombre'],
                'codigo_interno' => $prow['codigo_interno'] ?? '',
                'cantidad'     => $cant,
                'precio_venta' => (float)$prow['precio_venta'],
                'stock_actual' => (int)$prow['stock_actual'],
            ];
        }
    }

    if (empty($productos_a_reservar)) {
        ob_end_clean();
        die(json_encode(['success' => false, 'message' => 'El carrito está vacío']));
    }

    // ── Verificar stock ───────────────────────────────────────
    foreach ($productos_a_reservar as $p) {
        if ($p['stock_actual'] < $p['cantidad']) {
            ob_end_clean();
            die(json_encode([
                'success' => false,
                'message' => "Stock insuficiente para \"{$p['nombre']}\". Disponible: {$p['stock_actual']} unidad(es)"
            ]));
        }
    }

// ── Calcular totales ──────────────────────────────────────
// PRIORIZAR valores del frontend (más confiables)
if ($subtotal_input > 0 && $total_input > 0) {
    $subtotal = $subtotal_input;
    $iva = $iva_input;
    $total = $total_input;
} else {
    // Solo calcular si no vienen del frontend
    $subtotal = 0;
    foreach ($productos_a_reservar as $p) $subtotal += $p['precio_venta'] * $p['cantidad'];
    $iva = round($subtotal * 0.16, 2);
    $total = round($subtotal + $iva, 2);
}

// Validación: monto mínimo 25% del total
$monto_minimo = round($total * 0.25, 2);

// Debug temporal (quitar después)
error_log("=== DEBUG ===");
error_log("Total usado: {$total}");
error_log("Monto mínimo: {$monto_minimo}");
error_log("Monto adelanto: {$monto_adelanto}");

if ($monto_adelanto < $monto_minimo) {
    ob_end_clean();
    die(json_encode([
        'success' => false, 
        'message' => "El monto mínimo de apartado es {$monto_minimo} (25% del total de Bs. {$total})"
    ]));
}

// Asignar fechas y valores requeridos para la reserva
$fecha_hoy = date('Y-m-d');
$fecha_limite = $fecha_cuota ?: date('Y-m-d', strtotime('+7 days'));
$monto_restante = round(max(0, $total - $monto_adelanto), 2);
$obs_final = $observaciones !== '' ? $observaciones : null;

    // ── Generar código único ──────────────────────────────────
    do {
        $codigo = 'RES-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $stDup = $conn->prepare("SELECT 1 FROM reservas WHERE codigo_reserva = ? LIMIT 1");
        $stDup->execute([$codigo]);
    } while ($stDup->fetch());

    // ── Generar referencia de pago única si no fue enviada por el cliente ───────────────
    if ($referencia_pago === '') {
        $referencia_pago = 'AP' . date('Ym') . strtoupper(substr(md5($codigo . time()), 0, 8));
    }

    $metodo_pago = $metodo_pago_nombre;
    $metodo_pago_descripcion = trim($input['metodo_pago_descripcion'] ?? '');

    if ($metodo_pago_id) {
        $stMetodo = $conn->prepare("SELECT tipo, banco, codigo_banco, cedula, telefono, numero_cuenta FROM metodos_pago_reservas WHERE id = ? LIMIT 1");
        $stMetodo->execute([$metodo_pago_id]);
        $metodo_row = $stMetodo->fetch(PDO::FETCH_ASSOC);
        if ($metodo_row) {
            $metodo_pago = $metodo_row['tipo'];
            if ($metodo_row['tipo'] === 'pago_movil') {
                $metodo_pago_descripcion = "Banco: {$metodo_row['banco']} ({$metodo_row['codigo_banco']})\nCédula: {$metodo_row['cedula']}\nTeléfono: {$metodo_row['telefono']}";
            } elseif ($metodo_row['tipo'] === 'transferencia') {
                $metodo_pago_descripcion = "Banco: {$metodo_row['banco']}\nCédula: {$metodo_row['cedula']}\nCuenta: {$metodo_row['numero_cuenta']}";
            } else {
                $metodo_pago_descripcion = ($metodo_row['banco'] ? "Banco: {$metodo_row['banco']}\n" : '') . "Cédula: {$metodo_row['cedula']}";
            }
        }
    }

    // ── Manejo de comprobante ───────────────────────────────
$comprobante_url = null;

// Verificar si se subió un archivo
if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
    $comprobante_file = $_FILES['comprobante'];
    
    // Validar que sea una imagen
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $comprobante_file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Tipo de archivo no permitido. Solo imágenes JPG, PNG o WEBP.');
    }
    
    // Validar tamaño (max 5MB)
    if ($comprobante_file['size'] > 5 * 1024 * 1024) {
        throw new Exception('El archivo no debe superar los 5MB.');
    }
    
    // Crear directorio si no existe
    $uploadDir = dirname(__DIR__) . '/public/uploads/reservas/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de uploads');
        }
    }
    
    // Generar nombre seguro
    $extension = pathinfo($comprobante_file['name'], PATHINFO_EXTENSION);
    $safeName = 'reserva_' . date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $uploadDir . $safeName;
    
    // Mover archivo
    if (move_uploaded_file($comprobante_file['tmp_name'], $targetPath)) {
        $comprobante_url = '/public/uploads/reservas/' . $safeName;
    } else {
        error_log('Error moviendo archivo: ' . error_get_last()['message']);
        throw new Exception('No se pudo guardar el comprobante');
    }
} elseif (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] !== UPLOAD_ERR_NO_FILE) {
    // Hubo un error en la subida
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
        UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco',
        UPLOAD_ERR_EXTENSION => 'Extensión de archivo no permitida'
    ];
    $error_msg = $upload_errors[$_FILES['comprobante']['error']] ?? 'Error desconocido';
    throw new Exception('Error al subir comprobante: ' . $error_msg);
}
    // ── Transacción ───────────────────────────────────────────
$conn->beginTransaction();

    $stIns = $conn->prepare("
        INSERT INTO reservas
            (codigo_reserva, cliente_id, producto_id, cantidad,
             fecha_reserva, fecha_limite, estado_reserva, observaciones,
             monto_adelanto, fecha_cuota, monto_restante, estado_pago,
             metodo_pago, referencia_pago, comprobante_url, ip_address, user_agent,
             subtotal, iva, monto_total,
             created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'PENDIENTE', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    foreach ($productos_a_reservar as $p) {
        $stIns->execute([
            $codigo, $cliente_id, $p['id'], $p['cantidad'], 
            $fecha_hoy, $fecha_limite, $obs_final, 
            $monto_adelanto, $fecha_cuota ?: null, $monto_restante,
            $estado_pago, $metodo_pago, $referencia_pago, $comprobante_url, $ip_address, $user_agent,
            $subtotal, $iva, $total
        ]);
        
        // ── Reducir stock (reserva pendiente) ────────────────────────────
        $stStock = $conn->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?");
        $stStock->execute([$p['cantidad'], $p['id']]);
    }

    // Bitácora
    try {
        $conn->prepare(
            "INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at)
             VALUES (?, 'CREAR_RESERVA', 'reservas', 0, ?::jsonb, NOW())"
        )->execute([$usuario_id, json_encode([
            'codigo' => $codigo, 
            'total' => $total,
            'adelanto' => $monto_adelanto,
            'ip' => $ip_address
        ])]);
    } catch (Exception $e) {}

    $conn->commit();

    $_SESSION['carrito'] = [];

    // ── Instrucciones de pago ─────────────────────────────
    if (!empty($metodo_pago)) {
        $instrucciones_pago = [
            'metodo' => $metodo_pago,
            'descripcion' => $metodo_pago_descripcion
        ];
    } else {
        $instrucciones_pago = [
            'banco' => 'Mercantil',
            'cuenta' => '0102-1234-56-7891234567',
            'titular' => 'INVERSIONES ROJAS 2016 C.A.',
            'rif' => 'J-40888806-8',
            'monto' => number_format($monto_adelanto, 2, ',', '.'),
            'referencia' => $referencia_pago,
            'concepto' => 'Apartado ' . $codigo,
            'fecha_limite' => $fecha_limite
        ];
    }

    // ── Enviar correo de confirmación al cliente ─────────────
    $productos_correo = [];
    foreach ($productos_a_reservar as $p) {
        $productos_correo[] = [
            'nombre' => $p['nombre'],
            'cantidad' => $p['cantidad'],
            'precio_formato' => number_format($p['precio_venta'], 2, ',', '.')
        ];
    }
    
    $cliente_nombre = $cliente['nombre_completo'] ?? 'Cliente';
    $partes = explode(' ', $cliente_nombre, 2);
    $cliente_nombre_first = $partes[0] ?? '';
    $cliente_apellido = $partes[1] ?? '';
    $cliente_email = $cliente['email'] ?? '';

    $reserva_data = [
        'codigo_reserva' => $codigo,
        'fecha_limite' => $fecha_limite,
        'monto_adelanto' => $monto_adelanto,
        'monto_total' => $total
    ];
    
    $productos_formateados = [];
    foreach ($productos_a_reservar as $p) {
        $productos_formateados[] = [
            'producto_nombre' => $p['nombre'],
            'codigo_interno' => $p['codigo_interno'] ?? '',
            'cantidad' => $p['cantidad'],
            'precio_venta' => $p['precio_venta']
        ];
    }
    
    $correo_enviado = false;
    if (!empty($cliente_email)) {
        $correo_enviado = enviarCorreoReserva(
            $cliente_email,
            $cliente_nombre_first,
            $cliente_apellido,
            'creacion',
            $codigo,
            $productos_formateados,
            (float)$total,
            $metodo_pago_descripcion
        );
    }

    // ── Respuesta ─────────────────────────────────────
    ob_end_clean();
    echo json_encode([
        'success'                => true,
        'tipo'                   => 'apartado',
        'codigo'                 => $codigo,
        'referencia_pago'         => $referencia_pago,
        'fecha_limite'           => $fecha_limite,
        'fecha_limite_formateada'=> date('d/m/Y', strtotime($fecha_limite)),
        'subtotal'               => round($subtotal, 2),
        'iva'                    => round($iva, 2),
        'total'                  => round($total, 2),
        'monto_adelanto'         => round($monto_adelanto, 2),
        'monto_restante'         => round($monto_restante, 2),
        'fecha_cuenta'            => $fecha_cuota,
        'instrucciones_pago'     => $instrucciones_pago,
        'cliente_email'         => $cliente_email,
        'metodo_pago'            => $metodo_pago,
        'metodo_pago_descripcion'=> $metodo_pago_descripcion,
        'comprobante_url'        => $comprobante_url,
        'email_enviado'          => $correo_enviado,
        'message'              => "Reserva {$codigo} creada. Tu apartado está confirmado.",
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log('[process_reserva] ' . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al procesar la reserva: ' . $e->getMessage()]);
}