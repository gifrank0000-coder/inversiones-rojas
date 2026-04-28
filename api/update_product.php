<?php

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['ok' => false, 'error' => 'No autenticado']));
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['ok' => false, 'error' => 'Método no permitido']));
}

// ── Leer datos (acepta JSON o FormData) ───────────────────────
$raw = file_get_contents('php://input');
$data = [];
if ($raw && ($json = json_decode($raw, true)) !== null) {
    $data = $json;
} else {
    $data = $_POST;
}

$id          = (int)($data['id'] ?? 0);
$nombre      = trim($data['nombre']      ?? '');
$descripcion = trim($data['descripcion'] ?? '');
$categoria_id  = !empty($data['categoria_id'])  ? (int)$data['categoria_id']  : null;
$proveedor_id  = !empty($data['proveedor_id'])   ? (int)$data['proveedor_id']  : null;
$stock_actual  = isset($data['stock_actual'])    ? (int)$data['stock_actual']  : null;
$stock_minimo  = isset($data['stock_minimo'])    ? (int)$data['stock_minimo']  : null;
$stock_maximo  = isset($data['stock_maximo'])    ? (int)$data['stock_maximo']  : null;
$precio_compra = isset($data['precio_compra'])   ? (float)$data['precio_compra'] : null;
$precio_compra_bs = isset($data['precio_compra_bs']) ? (float)$data['precio_compra_bs'] : null;
$precio_compra_usd = isset($data['precio_compra_usd']) ? (float)$data['precio_compra_usd'] : null;
$precio_venta  = isset($data['precio_venta'])    ? (float)$data['precio_venta']  : null;
$precio_venta_bs = isset($data['precio_venta_bs']) ? (float)$data['precio_venta_bs'] : null;
$precio_venta_usd = isset($data['precio_venta_usd']) ? (float)$data['precio_venta_usd'] : null;
$especificaciones_raw = $data['especificaciones'] ?? null;

if (!$id) {
    die(json_encode(['ok' => false, 'error' => 'ID de producto inválido']));
}

// ── Validaciones ──────────────────────────────────────────────
$errors = [];

if (empty($nombre)) {
    $errors['nombre'] = 'El nombre es obligatorio';
} elseif (strlen($nombre) > 200) {
    $errors['nombre'] = 'El nombre no puede exceder 200 caracteres';
}

if ($precio_compra !== null && $precio_compra < 0) {
    $errors['precio_compra'] = 'El precio de compra no puede ser negativo';
}

if ($precio_venta !== null) {
    if ($precio_venta < 0) {
        $errors['precio_venta'] = 'El precio de venta no puede ser negativo';
    } elseif ($precio_compra !== null && $precio_venta < $precio_compra) {
        $errors['precio_venta'] = "El precio de venta (Bs {$precio_venta}) no puede ser menor al de compra (Bs {$precio_compra})";
    }
}

if ($stock_minimo !== null && $stock_maximo !== null && $stock_minimo > $stock_maximo) {
    $errors['stock_minimo'] = 'El stock mínimo no puede ser mayor al máximo';
}

if ($stock_actual !== null && $stock_actual < 0) {
    $errors['stock_actual'] = 'El stock actual no puede ser negativo';
}

if (!empty($errors)) {
    http_response_code(422);
    die(json_encode(['ok' => false, 'errors' => $errors, 'error' => 'Datos inválidos']));
}

try {
    $db   = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception('Sin conexión a la base de datos');

    // Verificar que el producto existe
    $stmt = $conn->prepare("SELECT id, tipo_id FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$producto) die(json_encode(['ok' => false, 'error' => 'Producto no encontrado']));

    $conn->beginTransaction();

    // ── Construir SET dinámico ────────────────────────────────
    $sets   = [];
    $params = [];

    $sets[] = 'nombre = ?';       $params[] = $nombre;
    $sets[] = 'descripcion = ?';  $params[] = $descripcion ?: null;
    $sets[] = 'updated_at = NOW()';

    if ($categoria_id !== null) { $sets[] = 'categoria_id = ?'; $params[] = $categoria_id; }
    if ($proveedor_id !== null) { $sets[] = 'proveedor_id = ?'; $params[] = $proveedor_id; }
    if ($stock_actual !== null) { $sets[] = 'stock_actual = ?'; $params[] = $stock_actual; }
    if ($stock_minimo !== null) { $sets[] = 'stock_minimo = ?'; $params[] = $stock_minimo; }
    if ($stock_maximo !== null) { $sets[] = 'stock_maximo = ?'; $params[] = $stock_maximo; }
    if ($precio_compra !== null){ $sets[] = 'precio_compra = ?'; $params[] = $precio_compra; }
    if ($precio_compra_bs !== null){ $sets[] = 'precio_compra_bs = ?'; $params[] = $precio_compra_bs; }
    if ($precio_compra_usd !== null){ $sets[] = 'precio_compra_usd = ?'; $params[] = $precio_compra_usd; }
    if ($precio_venta  !== null){ $sets[] = 'precio_venta = ?';  $params[] = $precio_venta; }
    if ($precio_venta_bs !== null){ $sets[] = 'precio_venta_bs = ?'; $params[] = $precio_venta_bs; }
    if ($precio_venta_usd !== null){ $sets[] = 'precio_venta_usd = ?'; $params[] = $precio_venta_usd; }

    $params[] = $id;
    $conn->prepare("UPDATE productos SET " . implode(', ', $sets) . " WHERE id = ?")
         ->execute($params);

    // ── Actualizar proveedor principal en producto_proveedor si cambia
    if ($proveedor_id !== null) {
        // Marcar todos los proveedores asociados como no principales
        $stmt = $conn->prepare("UPDATE producto_proveedor SET es_principal = false WHERE producto_id = ?");
        $stmt->execute([$id]);

        // Intentar activar o actualizar el proveedor existente
        $precioCompraParaProveedor = $precio_compra !== null ? $precio_compra : (float)$producto['precio_compra'];
        $stmt = $conn->prepare(
            "UPDATE producto_proveedor SET es_principal = true, activo = true, precio_compra = ? WHERE producto_id = ? AND proveedor_id = ?"
        );
        $stmt->execute([$precioCompraParaProveedor, $id, $proveedor_id]);

        if ($stmt->rowCount() === 0) {
            $stmt = $conn->prepare(
                "INSERT INTO producto_proveedor (producto_id, proveedor_id, precio_compra, es_principal, activo, created_at, updated_at)
                 VALUES (?, ?, ?, true, true, NOW(), NOW())"
            );
            $stmt->execute([$id, $proveedor_id, $precioCompraParaProveedor]);
        }
    }

    // ── Actualizar especificaciones según tipo ────────────────
    if ($especificaciones_raw) {
        $esp = is_string($especificaciones_raw)
            ? json_decode($especificaciones_raw, true)
            : $especificaciones_raw;

        if ($esp && is_array($esp)) {
            $tipo = strtolower($esp['tipo'] ?? '');

            if ($tipo === 'vehiculo') {
                $conn->prepare("
                    UPDATE vehiculos SET
                        marca      = ?,
                        modelo     = ?,
                        anio       = ?,
                        cilindrada = ?,
                        color      = ?,
                        kilometraje = ?
                    WHERE producto_id = ?
                ")->execute([
                    $esp['marca']      ?? null,
                    $esp['modelo']     ?? null,
                    $esp['anio']       ?? null,
                    $esp['cilindrada'] ?? null,
                    $esp['color']      ?? null,
                    (int)($esp['kilometraje'] ?? 0),
                    $id,
                ]);

            } elseif ($tipo === 'repuesto') {
                $conn->prepare("
                    UPDATE repuestos SET
                        categoria_tecnica  = ?,
                        marca_compatible   = ?,
                        modelo_compatible  = ?,
                        anio_compatible    = ?
                    WHERE producto_id = ?
                ")->execute([
                    $esp['categoria_tecnica'] ?? null,
                    $esp['marca_compatible']  ?? null,
                    $esp['modelo_compatible'] ?? null,
                    $esp['anio_compatible']   ?? null,
                    $id,
                ]);

            } elseif ($tipo === 'accesorio') {
                $conn->prepare("
                    UPDATE accesorios SET
                        subtipo_accesorio = ?,
                        talla             = ?,
                        color             = ?,
                        material          = ?,
                        marca             = ?,
                        certificacion     = ?
                    WHERE producto_id = ?
                ")->execute([
                    $esp['subtipo_accesorio'] ?? null,
                    $esp['talla']             ?? null,
                    $esp['color']             ?? null,
                    $esp['material']          ?? null,
                    $esp['marca']             ?? null,
                    $esp['certificacion']     ?? null,
                    $id,
                ]);
            }
        }
    }

    // ── Procesar imágenes adicionales si existen ─────────────────
    $imagenes_subidas = [];
    $file_processing_errors = [];
    
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
        error_log('Procesando ' . count($_FILES['images']['name']) . ' imágenes adicionales para producto ' . $id);
        
        $upload_dir = dirname(__DIR__) . '/public/img/products/';
        
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $file_processing_errors[] = 'No se pudo crear directorio de destino';
            }
        }
        
        if (is_dir($upload_dir) && is_writable($upload_dir)) {
            // Obtener el orden máximo actual para este producto
            $stmt_max_orden = $conn->prepare("SELECT COALESCE(MAX(orden), 0) as max_orden FROM producto_imagenes WHERE producto_id = ?");
            $stmt_max_orden->execute([$id]);
            $max_orden = $stmt_max_orden->fetch(PDO::FETCH_ASSOC)['max_orden'];
            
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                
                $tmp_name = $_FILES['images']['tmp_name'][$i];
                $origName = $_FILES['images']['name'][$i];
                
                // Validar tipo de archivo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $tmp_name);
                finfo_close($finfo);
                
                $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                
                if (!in_array($mime_type, $allowed_mimes)) {
                    $file_processing_errors[] = "Tipo no permitido para $origName";
                    continue;
                }
                
                // Validar tamaño (5MB máximo)
                if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024) {
                    $file_processing_errors[] = "Archivo $origName excede 5MB";
                    continue;
                }
                
                // Generar nombre único
                $extension = pathinfo($origName, PATHINFO_EXTENSION);
                $filename = "prod_{$id}_" . time() . "_{$i}." . $extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($tmp_name, $filepath)) {
                    // Determinar si es principal (solo si no hay imágenes existentes)
                    $stmt_count_imgs = $conn->prepare("SELECT COUNT(*) as total FROM producto_imagenes WHERE producto_id = ?");
                    $stmt_count_imgs->execute([$id]);
                    $total_imgs = $stmt_count_imgs->fetch(PDO::FETCH_ASSOC)['total'];
                    
                    $es_principal = ($total_imgs == 0 && $i === 0) ? 'true' : 'false';
                    $orden = $max_orden + $i + 1;
                    
                    // Insertar en base de datos
                    $sqlImg = "INSERT INTO producto_imagenes (
                        producto_id, imagen_url, es_principal, orden, created_at
                    ) VALUES (?, ?, ?::boolean, ?, NOW())";
                    
                    $stmtImg = $conn->prepare($sqlImg);
                    $url = '/inversiones-rojas/public/img/products/' . $filename;
                    
                    $stmtImg->execute([$id, $url, $es_principal, $orden]);
                    
                    $imagenes_subidas[] = $url;
                    error_log("Imagen adicional guardada: $filename");
                } else {
                    $file_processing_errors[] = "No se pudo guardar $origName";
                }
            }
        } else {
            $file_processing_errors[] = 'Directorio de destino no escribible';
        }
    }

    // ── Bitácora ──────────────────────────────────────────────
    try {
        $conn->prepare(
            "INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at)
             VALUES (?, 'ACTUALIZAR_PRODUCTO', 'productos', ?, ?::jsonb, NOW())"
        )->execute([
            $_SESSION['user_id'],
            $id,
            json_encode(['nombre' => $nombre, 'campos_actualizados' => array_keys(array_filter(compact('precio_venta','precio_compra','stock_actual','stock_minimo','stock_maximo')))])
        ]);
    } catch(Exception $e) {}

    $conn->commit();

    // Devolver producto actualizado
    $stmt = $conn->prepare("
        SELECT p.*, c.nombre AS categoria_nombre, tp.nombre AS tipo_nombre, pv.razon_social AS proveedor_nombre
        FROM productos p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        LEFT JOIN tipos_producto tp ON tp.id = p.tipo_id
        LEFT JOIN proveedores pv ON pv.id = p.proveedor_id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok'      => true,
        'success' => true,
        'message' => "Producto \"{$nombre}\" actualizado correctamente",
        'producto' => $updated,
        'imagenes_subidas' => $imagenes_subidas,
        'file_warnings' => $file_processing_errors
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    error_log('[update_product] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
}