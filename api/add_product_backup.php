<?php
// add_product.php
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

// Función para convertir a boolean de PostgreSQL
function toPgBoolean($value) {
    if ($value === true || $value === 'true' || $value === '1' || $value === 1) {
        return 'true';
    }
    return 'false'; // PostgreSQL espera 'true' o 'false' como strings
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
    $codigo = trim($_POST['codigo_interno'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $stock_actual = !empty($_POST['stock_actual']) ? (int)$_POST['stock_actual'] : 0;
    $stock_minimo = !empty($_POST['stock_minimo']) ? (int)$_POST['stock_minimo'] : 5;
    $stock_maximo = !empty($_POST['stock_maximo']) ? (int)$_POST['stock_maximo'] : 100;
    $precio_compra = !empty($_POST['precio_compra']) ? (float)$_POST['precio_compra'] : 0.0;
    $precio_venta = !empty($_POST['precio_venta']) ? (float)$_POST['precio_venta'] : 0.0;
    $proveedor_id = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;
    $tipo_producto = trim($_POST['tipo_producto'] ?? '');
    
    error_log("=== DATOS RECIBIDOS EN ADD_PRODUCT ===");
    error_log("codigo: $codigo");
    error_log("nombre: $nombre");
    error_log("categoria_id: $categoria_id");
    error_log("tipo_producto: $tipo_producto");
    
    // Obtener y decodificar especificaciones
    $especificaciones = [];
    if (!empty($_POST['especificaciones'])) {
        $especificaciones_raw = $_POST['especificaciones'];
        $especificaciones = json_decode($especificaciones_raw, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('ERROR JSON decode: ' . json_last_error_msg());
            $especificaciones = [];
        }
    }
    
    error_log("especificaciones: " . print_r($especificaciones, true));

    // Validaciones básicas
    $errors = [];

    // Código
    if (empty($codigo)) {
        $errors['codigo_interno'] = 'El código interno es requerido';
    } elseif (!preg_match('/^[A-Za-z0-9\-_]{1,50}$/', $codigo)) {
        $errors['codigo_interno'] = 'Código inválido. Solo letras, números, guiones y guiones bajos.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM productos WHERE codigo_interno = ?");
        $stmt->execute([$codigo]);
        if ($stmt->fetch()) {
            $errors['codigo_interno'] = 'El código ya existe';
        }
    }

    // Nombre
    if (empty($nombre)) {
        $errors['nombre'] = 'El nombre es requerido';
    } elseif (strlen($nombre) > 200) {
        $errors['nombre'] = 'El nombre no puede exceder 200 caracteres';
    }

    // Tipo de producto
    if (empty($tipo_producto)) {
        $errors['tipo_producto'] = 'El tipo de producto es requerido';
    } elseif (!in_array($tipo_producto, ['vehiculo', 'repuesto', 'accesorio'])) {
        $errors['tipo_producto'] = 'Tipo de producto inválido';
    }

    // Categoría
    if (empty($categoria_id)) {
        $errors['categoria_id'] = 'La categoría es requerida';
    } else {
        $stmtCat = $conn->prepare("SELECT id, tipo_producto_id FROM categorias WHERE id = ? AND estado = true");
        $stmtCat->execute([$categoria_id]);
        $categoria_data = $stmtCat->fetch(PDO::FETCH_ASSOC);
        
        if (!$categoria_data) {
            $errors['categoria_id'] = 'La categoría seleccionada no existe o está inactiva';
        }
    }

    // Precios
    if ($precio_compra < 0) {
        $errors['precio_compra'] = 'El precio de compra no puede ser negativo';
    }

    if ($precio_venta < 0) {
        $errors['precio_venta'] = 'El precio de venta no puede ser negativo';
    }

    if ($precio_venta < $precio_compra) {
        $errors['precio_venta'] = 'El precio de venta no puede ser menor al precio de compra';
    }

    // Stock
    if ($stock_actual < 0) {
        $errors['stock_actual'] = 'El stock actual no puede ser negativo';
    }

    if ($stock_minimo < 0) {
        $errors['stock_minimo'] = 'El stock mínimo no puede ser negativo';
    }

    if ($stock_maximo < 0) {
        $errors['stock_maximo'] = 'El stock máximo no puede ser negativo';
    }

    if ($stock_minimo > $stock_maximo) {
        $errors['stock_minimo'] = 'El stock mínimo no puede ser mayor al máximo';
    }

    // Validaciones específicas por tipo de producto
    if ($tipo_producto === 'vehiculo') {
        if (empty($especificaciones['marca'])) {
            $errors['vehiculo_marca'] = 'La marca del vehículo es requerida';
        }
        if (empty($especificaciones['modelo'])) {
            $errors['vehiculo_modelo'] = 'El modelo del vehículo es requerido';
        }
        if (empty($especificaciones['anio'])) {
            $errors['vehiculo_anio'] = 'El año del vehículo es requerido';
        }
        if (empty($especificaciones['cilindrada'])) {
            $errors['vehiculo_cilindrada'] = 'La cilindrada del vehículo es requerida';
        }
        if (empty($especificaciones['color'])) {
            $errors['vehiculo_color'] = 'El color del vehículo es requerido';
        }
    } elseif ($tipo_producto === 'repuesto') {
        if (empty($especificaciones['categoria_tecnica'])) {
            $errors['repuesto_categoria_tecnica'] = 'La categoría técnica del repuesto es requerida';
        }
        if (empty($especificaciones['marca_compatible'])) {
            $errors['repuesto_marca_compatible'] = 'La marca compatible del repuesto es requerida';
        }
    } elseif ($tipo_producto === 'accesorio') {
        // El subtipo es opcional ya que la categoría ya identifica el tipo de accesorio
        if (empty($especificaciones['marca'])) {
            $errors['accesorio_marca'] = 'La marca del accesorio es requerida';
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
        // 1. Obtener tipo_id correspondiente
        $tipo_id = null;
        $tipo_nombre_bd = '';
        
        // Mapeo de tipos frontend a tipos de base de datos (nombres exactos existentes en tipos_producto)
        $tipo_mapping = [
            'vehiculo' => 'Vehículo',
            'repuesto' => 'Repuesto',
            'accesorio' => 'Accesorio'
        ];
        
        $tipo_nombre_bd = $tipo_mapping[$tipo_producto] ?? '';
        
        if ($tipo_nombre_bd) {
            $stmtTipo = $conn->prepare("SELECT id FROM tipos_producto WHERE nombre = ?");
            $stmtTipo->execute([$tipo_nombre_bd]);
            checkPostgresError($stmtTipo, "buscar_tipo_producto", $conn);
            
            $tipo_id = $stmtTipo->fetchColumn();
            
            // Si no encuentra MOTO, intentar con VEHICULO
            if (!$tipo_id && $tipo_nombre_bd === 'MOTO') {
                $stmtTipo->execute(['VEHICULO']);
                $tipo_id = $stmtTipo->fetchColumn();
            }
        }
        
        if (!$tipo_id) {
            // Intentar obtener cualquier tipo de producto activo
            $stmtTipo = $conn->query("SELECT id FROM tipos_producto WHERE estado = true LIMIT 1");
            $tipo_id = $stmtTipo->fetchColumn();
        }
        
        if (!$tipo_id) {
            throw new Exception("No se encontró ningún tipo de producto en la base de datos");
        }
        
        error_log("tipo_id encontrado: $tipo_id");

        // 2. Insertar producto en la tabla productos
        $sqlProducto = "INSERT INTO productos (
            codigo_interno, nombre, descripcion, categoria_id, 
            precio_compra, precio_venta, stock_actual, stock_minimo, stock_maximo,
            proveedor_id, tipo_id, estado, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, true, NOW(), NOW()) RETURNING id";

        error_log("Ejecutando INSERT en productos");
        $stmtProducto = $conn->prepare($sqlProducto);
        
        $params = [
            $codigo, 
            $nombre, 
            $descripcion, 
            $categoria_id,
            $precio_compra, 
            $precio_venta, 
            $stock_actual, 
            $stock_minimo, 
            $stock_maximo,
            $proveedor_id,
            $tipo_id
        ];
        
        error_log("Parámetros producto: " . json_encode($params));
        $stmtProducto->execute($params);
        checkPostgresError($stmtProducto, "insertar_producto", $conn);
        
        $producto_id = $stmtProducto->fetchColumn();

        if (!$producto_id) {
            throw new Exception('No se pudo obtener el ID del producto insertado');
        }

        error_log('Producto insertado con ID: ' . $producto_id);

        // 3. Procesar proveedores asociados (producto_proveedor)
        if (!empty($_POST['proveedores'])) {
            $proveedores_raw = $_POST['proveedores'];
            $proveedores = json_decode($proveedores_raw, true);
            
            error_log("Proveedores a procesar: " . print_r($proveedores, true));
            
            if (is_array($proveedores) && !empty($proveedores)) {
                $sqlProv = "INSERT INTO producto_proveedor (
                    producto_id, proveedor_id, precio_compra, es_principal, activo, created_at, updated_at
                ) VALUES (?, ?, ?, ?::boolean, true, NOW(), NOW())";
                
                $stmtProv = $conn->prepare($sqlProv);
                
                foreach ($proveedores as $prov) {
                    if (empty($prov['proveedor_id'])) continue;
                    
                    // Determinar si es principal (convertir a boolean correctamente)
                    $es_principal = false;
                    if (isset($prov['es_principal'])) {
                        if ($prov['es_principal'] === true || 
                            $prov['es_principal'] === 'true' || 
                            $prov['es_principal'] === '1' || 
                            $prov['es_principal'] === 1) {
                            $es_principal = true;
                        }
                    }
                    
                    $prov_params = [
                        $producto_id,
                        $prov['proveedor_id'],
                        $prov['precio_compra'] ?? $precio_compra,
                        $es_principal ? 'true' : 'false' // Enviar como string para cast explícito
                    ];
                    
                    error_log("Insertando proveedor con params: " . json_encode($prov_params));
                    $stmtProv->execute($prov_params);
                    checkPostgresError($stmtProv, "insertar_producto_proveedor", $conn);
                }
                error_log('Proveedores asociados exitosamente');
            }
        } elseif ($proveedor_id) {
            // Si hay un proveedor_id pero no viene en proveedores, insertarlo
            $sqlProv = "INSERT INTO producto_proveedor (
                producto_id, proveedor_id, precio_compra, es_principal, activo, created_at, updated_at
            ) VALUES (?, ?, ?, true, true, NOW(), NOW())";
            
            $stmtProv = $conn->prepare($sqlProv);
            $prov_params = [$producto_id, $proveedor_id, $precio_compra];
            
            error_log("Insertando proveedor único: " . json_encode($prov_params));
            $stmtProv->execute($prov_params);
            checkPostgresError($stmtProv, "insertar_producto_proveedor_unico", $conn);
        }

        // 4. Insertar en la tabla específica según el tipo de producto
        if ($tipo_producto === 'vehiculo') {
            error_log('Insertando en tabla vehiculos');
            
            $sqlVehiculo = "INSERT INTO vehiculos (
                producto_id, marca, modelo, anio, cilindrada, color, kilometraje, tipo_vehiculo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmtVehiculo = $conn->prepare($sqlVehiculo);
            $vehiculo_params = [
                $producto_id,
                $especificaciones['marca'] ?? '',
                $especificaciones['modelo'] ?? '',
                $especificaciones['anio'] ?? '',
                $especificaciones['cilindrada'] ?? '',
                $especificaciones['color'] ?? '',
                (int)($especificaciones['kilometraje'] ?? 0),
                'Moto'
            ];
            
            error_log("Parámetros vehículo: " . json_encode($vehiculo_params));
            $stmtVehiculo->execute($vehiculo_params);
            checkPostgresError($stmtVehiculo, "insertar_vehiculo", $conn);
            error_log('Vehículo insertado exitosamente');

        } elseif ($tipo_producto === 'repuesto') {
            error_log('Insertando en tabla repuestos');
            
            $sqlRepuesto = "INSERT INTO repuestos (
                producto_id, categoria_tecnica, marca_compatible, modelo_compatible, anio_compatible
            ) VALUES (?, ?, ?, ?, ?)";
            
            $stmtRepuesto = $conn->prepare($sqlRepuesto);
            $repuesto_params = [
                $producto_id,
                $especificaciones['categoria_tecnica'] ?? '',
                $especificaciones['marca_compatible'] ?? '',
                $especificaciones['modelo_compatible'] ?? '',
                $especificaciones['anio_compatible'] ?? ''
            ];
            
            error_log("Parámetros repuesto: " . json_encode($repuesto_params));
            $stmtRepuesto->execute($repuesto_params);
            checkPostgresError($stmtRepuesto, "insertar_repuesto", $conn);
            error_log('Repuesto insertado exitosamente');

        } elseif ($tipo_producto === 'accesorio') {
            error_log('Insertando en tabla accesorios');
            
            $sqlAccesorio = "INSERT INTO accesorios (
                producto_id, subtipo_accesorio, talla, color, material, marca, certificacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmtAccesorio = $conn->prepare($sqlAccesorio);
            $accesorio_params = [
                $producto_id,
                $especificaciones['subtipo_accesorio'] ?? '',
                $especificaciones['talla'] ?? '',
                $especificaciones['color'] ?? '',
                $especificaciones['material'] ?? '',
                $especificaciones['marca'] ?? '',
                $especificaciones['certificacion'] ?? ''
            ];
            
            error_log("Parámetros accesorio: " . json_encode($accesorio_params));
            $stmtAccesorio->execute($accesorio_params);
            checkPostgresError($stmtAccesorio, "insertar_accesorio", $conn);
            error_log('Accesorio insertado exitosamente');
        }

        // 5. Procesar imágenes si existen
        $imagenes_subidas = [];
        $file_processing_errors = [];
        
        if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
            error_log('Procesando ' . count($_FILES['images']['name']) . ' imágenes');
            
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/inversiones-rojas/public/img/products/';
            
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    error_log('No se pudo crear directorio: ' . $upload_dir);
                    $file_processing_errors[] = 'No se pudo crear directorio de destino';
                }
            }

            if (is_dir($upload_dir) && is_writable($upload_dir)) {
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
                    $filename = "prod_{$producto_id}_" . time() . "_{$i}." . $extension;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($tmp_name, $filepath)) {
                        // Insertar en base de datos
                        $sqlImg = "INSERT INTO producto_imagenes (
                            producto_id, imagen_url, es_principal, orden, created_at
                        ) VALUES (?, ?, ?::boolean, ?, NOW())";
                        
                        $stmtImg = $conn->prepare($sqlImg);
                        $url = '/inversiones-rojas/public/img/products/' . $filename;
                        $es_principal = ($i === 0) ? 'true' : 'false';
                        $orden = $i + 1;
                        
                        $img_params = [$producto_id, $url, $es_principal, $orden];
                        error_log("Insertando imagen: " . json_encode($img_params));
                        
                        $stmtImg->execute($img_params);
                        checkPostgresError($stmtImg, "insertar_imagen", $conn);
                        
                        $imagenes_subidas[] = $url;
                        error_log("Imagen guardada: $filename");
                    } else {
                        $file_processing_errors[] = "No se pudo guardar $origName";
                    }
                }
            } else {
                $file_processing_errors[] = 'Directorio de destino no escribible';
            }
        }

        // 6. Registrar en bitácora
        try {
            $sqlBitacora = "INSERT INTO bitacora_sistema (
                usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address, user_agent, created_at
            ) VALUES (?, 'CREAR_PRODUCTO', 'productos', ?, ?::jsonb, ?, ?, NOW())";
            
            $stmtBitacora = $conn->prepare($sqlBitacora);
            $detalles = json_encode([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'tipo' => $tipo_producto,
                'stock_inicial' => $stock_actual
            ]);
            
            $bitacora_params = [
                $_SESSION['user_id'],
                $producto_id,
                $detalles,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
            
            error_log("Insertando bitácora");
            $stmtBitacora->execute($bitacora_params);
            checkPostgresError($stmtBitacora, "insertar_bitacora", $conn);
            
            error_log('Bitácora registrada');
        } catch (Exception $e) {
            error_log('Error en bitácora (no crítico): ' . $e->getMessage());
        }

        // 7. Confirmar transacción
        $conn->commit();
        error_log('=== TRANSACCIÓN CONFIRMADA EXITOSAMENTE ===');

        // 8. Respuesta exitosa
        $respuesta = [
            'ok' => true,
            'id' => $producto_id,
            'codigo' => $codigo,
            'message' => 'Producto creado exitosamente'
        ];
        
        if (!empty($imagenes_subidas)) {
            $respuesta['images'] = $imagenes_subidas;
            $respuesta['message'] .= ' con ' . count($imagenes_subidas) . ' imagen(es)';
        }
        
        if (!empty($file_processing_errors)) {
            $respuesta['files_warnings'] = $file_processing_errors;
        }
        
        http_response_code(201);
        echo json_encode($respuesta);

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('ERROR EN TRANSACCIÓN: ' . $e->getMessage());
        error_log('Trace: ' . $e->getTraceAsString());
        
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Error interno del servidor durante la transacción: ' . $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    error_log('ERROR GLOBAL: ' . $e->getMessage());
    error_log('Trace: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}

exit;
?>