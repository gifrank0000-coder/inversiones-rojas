<?php
session_start();
require_once __DIR__ . '/../../models/database.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../../config/config.php';

// Inicializar variables
$is_admin = false;
$user_role = null;
$previous_url = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php';


// === Lógica de Backup / Restauración de Base de Datos (Postgres) ===
// Directorio de backups (config/backups)
$backupDir = realpath(__DIR__ . '/../../../config') . DIRECTORY_SEPARATOR . 'backups';
if ($backupDir === false) {
    $backupDir = __DIR__ . '/../../../config/backups';
}
if (!is_dir($backupDir)) {
    if (!@mkdir($backupDir, 0755, true)) {
        $error = "No se pudo crear el directorio de backups: " . $backupDir;
        error_log($error);
    }
}

// Verificar permisos del directorio
$backup_writable = false;
if (is_dir($backupDir)) {
    if (!is_writable($backupDir)) {
        // Intentar cambiar permisos
        @chmod($backupDir, 0755);
        $backup_writable = is_writable($backupDir);
    } else {
        $backup_writable = true;
    }
}

// Función para encontrar ejecutables de PostgreSQL
function find_postgres_executable($name) {
    // Primero intentar con la constante definida
    if ($name === 'pg_dump' && defined('PG_DUMP_PATH') && is_file(PG_DUMP_PATH)) {
        return PG_DUMP_PATH;
    }
    if ($name === 'psql' && defined('PSQL_PATH') && is_file(PSQL_PATH)) {
        return PSQL_PATH;
    }
    
    // Buscar en el sistema
    $os = PHP_OS_FAMILY;
    if ($os === 'Windows') {
        // Windows
        @exec('where ' . escapeshellarg($name) . ' 2>&1', $o, $r);
        if ($r === 0 && !empty($o)) return $o[0];
        
        // Rutas comunes en Windows
        $programFiles = getenv('ProgramFiles') ?: 'C:\\Program Files';
        $candidates = [
            $programFiles . "\\PostgreSQL\\18\\bin\\$name.exe",
            $programFiles . "\\PostgreSQL\\15\\bin\\$name.exe",
            $programFiles . "\\PostgreSQL\\14\\bin\\$name.exe",
            $programFiles . "\\PostgreSQL\\13\\bin\\$name.exe",
            'C:\\Program Files (x86)\\PostgreSQL\\18\\bin\\' . $name . '.exe'
        ];
        foreach ($candidates as $c) {
            if (is_file($c)) return $c;
        }
    } else {
        // Linux/Unix
        @exec('which ' . escapeshellarg($name) . ' 2>&1', $o, $r);
        if ($r === 0 && !empty($o)) return $o[0];
        
        // Rutas comunes en Linux
        $candidates = [
            '/usr/bin/' . $name,
            '/usr/local/bin/' . $name,
            '/usr/pgsql/bin/' . $name,
            '/opt/local/bin/' . $name
        ];
        foreach ($candidates as $c) {
            if (is_file($c)) return $c;
        }
    }
    
    return false;
}

// Función simplificada para crear backup
function create_backup_simple($backupDir) {
    $host = DB_HOST;
    $port = DB_PORT;
    $db   = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;
    
    // Nombre del archivo
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "backup_{$timestamp}.sql";
    $filePath = $backupDir . DIRECTORY_SEPARATOR . $filename;
    
    // Buscar pg_dump
    $pg_dump = find_postgres_executable('pg_dump');
    if (!$pg_dump) {
        return [
            'success' => false,
            'message' => 'Error: pg_dump no encontrado. Instale PostgreSQL o configure la ruta en config.php',
            'filename' => null
        ];
    }
    
    // Configurar contraseña
    putenv('PGPASSWORD=' . $pass);
    
    // Comando simplificado
    $cmd = escapeshellarg($pg_dump) . ' -h ' . escapeshellarg($host) . 
           ' -p ' . escapeshellarg($port) . 
           ' -U ' . escapeshellarg($user) . 
           ' -d ' . escapeshellarg($db) . 
           ' -f ' . escapeshellarg($filePath);
    
    // Ejecutar
    exec($cmd . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($filePath)) {
        return [
            'success' => true,
            'message' => 'Backup creado exitosamente',
            'filename' => $filename,
            'filepath' => $filePath,
            'size' => filesize($filePath)
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al crear backup: ' . implode("\n", $output),
            'filename' => null
        ];
    }
}

// Función simplificada para restaurar
function restore_backup_simple($backupFile, $backupDir) {
    $host = DB_HOST;
    $port = DB_PORT;
    $db   = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;
    
    $filePath = $backupDir . DIRECTORY_SEPARATOR . $backupFile;
    
    if (!file_exists($filePath)) {
        return [
            'success' => false,
            'message' => 'Archivo de backup no encontrado'
        ];
    }
    
    // Buscar psql
    $psql = find_postgres_executable('psql');
    if (!$psql) {
        return [
            'success' => false,
            'message' => 'Error: psql no encontrado. Instale PostgreSQL o configure la ruta en config.php'
        ];
    }
    
    // Configurar contraseña
    putenv('PGPASSWORD=' . $pass);
    
    // Comando para restaurar
    $cmd = escapeshellarg($psql) . ' -h ' . escapeshellarg($host) . 
           ' -p ' . escapeshellarg($port) . 
           ' -U ' . escapeshellarg($user) . 
           ' -d ' . escapeshellarg($db) . 
           ' -f ' . escapeshellarg($filePath);
    
    // Ejecutar
    exec($cmd . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        return [
            'success' => true,
            'message' => 'Base de datos restaurada exitosamente'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al restaurar: ' . implode("\n", $output)
        ];
    }
}

// Manejo de acciones AJAX para backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = [];
    
    if ($action === 'create_backup') {
        $result = create_backup_simple($backupDir);
        $response = $result;
        
        // Si fue exitoso, guardar en bitácora
        if ($result['success'] && isset($conn)) {
            try {
                $stmt = $conn->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, detalles, ip_address) VALUES (:usuario_id, :accion, :tabla_afectada, :detalles, :ip_address)");
                $stmt->execute([
                    'usuario_id' => $_SESSION['user_id'] ?? null,
                    'accion' => 'BACKUP_CREATE',
                    'tabla_afectada' => 'sistema',
                    'detalles' => json_encode(['archivo' => $result['filename']]),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);
            } catch (Exception $e) {
                // Error en bitácora no debe afectar el backup
            }
        }
        
    } elseif ($action === 'restore_backup' && isset($_POST['filename'])) {
        // Confirmación adicional para restauración
        if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'yes') {
            $response = [
                'success' => false,
                'message' => 'Se requiere confirmación para restaurar'
            ];
        } else {
            $result = restore_backup_simple($_POST['filename'], $backupDir);
            $response = $result;
            
            // Si fue exitoso, guardar en bitácora
            if ($result['success'] && isset($conn)) {
                try {
                    $stmt = $conn->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, detalles, ip_address) VALUES (:usuario_id, :accion, :tabla_afectada, :detalles, :ip_address)");
                    $stmt->execute([
                        'usuario_id' => $_SESSION['user_id'] ?? null,
                        'accion' => 'BACKUP_RESTORE',
                        'tabla_afectada' => 'sistema',
                        'detalles' => json_encode(['archivo' => $_POST['filename']]),
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
                    ]);
                } catch (Exception $e) {
                    // Error en bitácora no debe afectar la restauración
                }
            }
        }
        
    } elseif ($action === 'delete_backup' && isset($_POST['filename'])) {
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $_POST['filename'];
        if (file_exists($filePath) && @unlink($filePath)) {
            $response = [
                'success' => true,
                'message' => 'Backup eliminado'
            ];
            
            // Guardar en bitácora
            if (isset($conn)) {
                try {
                    $stmt = $conn->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, detalles, ip_address) VALUES (:usuario_id, :accion, :tabla_afectada, :detalles, :ip_address)");
                    $stmt->execute([
                        'usuario_id' => $_SESSION['user_id'] ?? null,
                        'accion' => 'BACKUP_DELETE',
                        'tabla_afectada' => 'sistema',
                        'detalles' => json_encode(['archivo' => $_POST['filename']]),
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
                    ]);
                } catch (Exception $e) {
                    // Error en bitácora no debe afectar la eliminación
                }
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'No se pudo eliminar el archivo'
            ];
        }
    } elseif ($action === 'download_backup' && isset($_POST['filename'])) {
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $_POST['filename'];
        if (file_exists($filePath)) {
            $response = [
                'success' => true,
                'url' => BASE_URL . '/config/download_backup.php?file=' . urlencode($_POST['filename'])
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Archivo no encontrado'
            ];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Inicializar conexión para registro/bitácora y uso posterior
$database = new Database();
$conn = $database->getConnection();

// Función para obtener actividades de bitácora
function get_bitacora_actividades($conn, $page = 1, $filters = []) {
    $actividades = [];
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    if ($conn) {
        try {
            $sql = "SELECT bs.*, u.username, u.nombre_completo 
                    FROM bitacora_sistema bs 
                    LEFT JOIN usuarios u ON bs.usuario_id = u.id 
                    WHERE 1=1";
            
            $params = [];
            
            // Filtro por texto
            if (!empty($filters['bitacora_q'])) {
                $sql .= " AND (bs.accion ILIKE ? OR bs.tabla_afectada ILIKE ? OR u.username ILIKE ? OR u.nombre_completo ILIKE ?)";
                $search_term = '%' . $filters['bitacora_q'] . '%';
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
                $params[] = $search_term;
            }
            
            // Filtro por usuario
            if (!empty($filters['bitacora_usuario'])) {
                $sql .= " AND bs.usuario_id = ?";
                $params[] = $filters['bitacora_usuario'];
            }
            
            // Filtro por fecha
            if (!empty($filters['bitacora_fecha'])) {
                switch ($filters['bitacora_fecha']) {
                    case 'today':
                        $sql .= " AND DATE(bs.created_at) = CURRENT_DATE";
                        break;
                    case 'week':
                        $sql .= " AND bs.created_at >= CURRENT_DATE - INTERVAL '7 days'";
                        break;
                    case 'month':
                        $sql .= " AND bs.created_at >= CURRENT_DATE - INTERVAL '30 days'";
                        break;
                }
            }
            
            $sql .= " ORDER BY bs.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $actividades = [];
        }
    }
    
    return $actividades;
}

// Manejo de AJAX para bitácora
if (isset($_GET['ajax']) && $_GET['ajax'] == 'bitacora') {
    $page = (int)($_GET['page'] ?? 1);
    $filters = [
        'bitacora_q' => $_GET['bitacora_q'] ?? '',
        'bitacora_usuario' => $_GET['bitacora_usuario'] ?? '',
        'bitacora_fecha' => $_GET['bitacora_fecha'] ?? ''
    ];
    
    $actividades = get_bitacora_actividades($conn, $page, $filters);
    
    header('Content-Type: application/json');
    echo json_encode($actividades);
    exit;
}

// Obtener el rol del usuario actual
if ($conn && isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT rol_id FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_role = $stmt->fetchColumn();
    $is_admin = ($user_role == 1); // Asumiendo que rol_id 1 es Administrador
}

// ── Cargar configuración de integraciones desde BD ─────────────
$int_cfg = [];
if ($conn) {
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS configuracion_integraciones (clave VARCHAR(80) PRIMARY KEY, valor TEXT NOT NULL DEFAULT '', updated_at TIMESTAMPTZ DEFAULT NOW())");
        $int_cfg = $conn->query("SELECT clave, valor FROM configuracion_integraciones")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) { $int_cfg = []; }
}
// Helper: BD tiene prioridad sobre constante PHP, luego default
function intCfg(array $cfg, string $key, $default = '') {
    if (array_key_exists($key, $cfg)) return $cfg[$key];
    $const = 'INTEGRATION_' . strtoupper($key);
    return defined($const) ? constant($const) : $default;
}

// La sección Empresa fue removida; mantener variable vacía para compatibilidad
$empresa_config = [];

$error = '';
$success = '';

// Si se configuró retención (por POST o GET) usarla, por defecto 30 días
$retentionDays = isset($_POST['retention_days']) ? max(0, (int)$_POST['retention_days']) : (isset($_GET['retention_days']) ? max(0,(int)$_GET['retention_days']) : 30);

// Limpiar backups antiguos según retención (solo si el directorio existe)
if ($retentionDays > 0 && is_dir($backupDir)) {
    $cut = time() - ($retentionDays * 24 * 60 * 60);
    foreach (glob(rtrim($backupDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.sql') as $fdel) {
        if (filemtime($fdel) < $cut) {
            @unlink($fdel);
        }
    }
}

// Sección "Empresa" eliminada: el manejo de configuración de empresa fue removido
// para evitar inconsistencias. Si se necesita restaurar esta funcionalidad,
// revisa el historial de commits y agrega de nuevo la lógica de validación y DB.
$roles = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT id, nombre FROM roles WHERE id > 1 ORDER BY id");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener la URL anterior para el botón de regresar
$previous_url = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/index.php';

// Evitar procesar el formulario de usuarios cuando se envían acciones de backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['backup_action'])) {
    // Recoger datos del formulario
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $cedula_rif = trim($_POST['cedula_rif'] ?? '');
    $telefono_principal = trim($_POST['telefono_principal'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $rol_id = isset($_POST['rol_id']) ? (int)$_POST['rol_id'] : 5; // Por defecto Cliente
    $user_id = isset($_POST['user_id']) && !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;

    // Validaciones básicas
    if (empty($username) || empty($email) || empty($nombre_completo)) {
        $error = 'Por favor complete todos los campos obligatorios.';
    } elseif ($user_id === null && (empty($password) || empty($confirm_password))) {
        $error = 'La contraseña es obligatoria al crear un usuario.';
    } elseif ($user_id === null && $password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden.';
    } elseif ($user_id === null && strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        try {
            // Determinar nombre del rol seleccionado (si está disponible en $roles)
            $roleName = null;
            foreach ($roles as $r) {
                if ((int)$r['id'] === (int)$rol_id) { $roleName = $r['nombre']; break; }
            }

            if ($user_id) {
                // === Edición de usuario existente ===
                // Verificar unicidad de username/email excluyendo el propio registro
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE (username = :username OR email = :email) AND id != :id");
                $stmt->execute(['username' => $username, 'email' => $email, 'id' => $user_id]);
                if ($stmt->rowCount() > 0) {
                    $error = 'El nombre de usuario o email ya están en uso por otro usuario.';
                } else {
                    $conn->beginTransaction();
                    try {
                        // Construir SQL de actualización
                        $updateFields = 'username = :username, email = :email, nombre_completo = :nombre_completo, rol_id = :rol_id';
                        $params = [
                            'username' => $username,
                            'email' => $email,
                            'nombre_completo' => $nombre_completo,
                            'rol_id' => $rol_id,
                            'id' => $user_id
                        ];

                        if (!empty($password)) {
                            if ($password !== $confirm_password) {
                                throw new Exception('Las contraseñas no coinciden.');
                            }
                            if (strlen($password) < 8) {
                                throw new Exception('La contraseña debe tener al menos 8 caracteres.');
                            }
                            $password_hash = password_hash($password, PASSWORD_BCRYPT);
                            $updateFields .= ', password_hash = :password_hash';
                            $params['password_hash'] = $password_hash;
                        }

                        $sql = "UPDATE usuarios SET $updateFields WHERE id = :id";
                        $ustmt = $conn->prepare($sql);
                        $ustmt->execute($params);

                        // Manejar asociación a clientes solo si el rol es Cliente
                        if ($roleName && strtolower($roleName) === 'cliente') {
                            // Buscar cliente por cédula
                            $cstmt = $conn->prepare('SELECT id FROM clientes WHERE cedula_rif = :cedula_rif LIMIT 1');
                            $cstmt->execute(['cedula_rif' => $cedula_rif]);
                            if ($cstmt->rowCount() > 0) {
                                $crow = $cstmt->fetch(PDO::FETCH_ASSOC);
                                $cliente_id = (int)$crow['id'];
                            } else {
                                // Crear cliente si no existe y hay cédula
                                if (!empty($cedula_rif)) {
                                    $insc = $conn->prepare('INSERT INTO clientes (cedula_rif, nombre_completo, email, telefono_principal, direccion, usuario_id) VALUES (:cedula_rif, :nombre_completo, :email, :telefono_principal, :direccion, :usuario_id) RETURNING id');
                                    $insc->execute([
                                        'cedula_rif' => $cedula_rif,
                                        'nombre_completo' => $nombre_completo,
                                        'email' => $email,
                                        'telefono_principal' => $telefono_principal,
                                        'direccion' => $direccion,
                                        'usuario_id' => $user_id
                                    ]);
                                    $crow = $insc->fetch(PDO::FETCH_ASSOC);
                                    $cliente_id = $crow['id'] ?? null;
                                } else {
                                    $cliente_id = null;
                                }
                            }

                            // Actualizar usuario con cliente_id si lo obtenemos
                            if (!empty($cliente_id)) {
                                $uup = $conn->prepare('UPDATE usuarios SET cliente_id = :cliente_id WHERE id = :id');
                                $uup->execute(['cliente_id' => $cliente_id, 'id' => $user_id]);
                            }
                        } else {
                            // Si no es cliente, desvincular cliente_id
                            $uup = $conn->prepare('UPDATE usuarios SET cliente_id = NULL WHERE id = :id');
                            $uup->execute(['id' => $user_id]);
                        }

                        // Registrar en bitácora
                        $bst = $conn->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address) VALUES (:usuario_id, :accion, :tabla_afectada, :registro_id, :detalles, :ip_address)");
                        $bst->execute([
                            'usuario_id' => $_SESSION['user_id'] ?? null,
                            'accion' => 'USUARIO_UPDATE',
                            'tabla_afectada' => 'usuarios',
                            'registro_id' => $user_id,
                            'detalles' => json_encode(['username' => $username, 'email' => $email, 'rol_id' => $rol_id]),
                            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
                        ]);

                        $conn->commit();
                        $success = 'Usuario actualizado correctamente.';
                        $_POST = [];
                    } catch (Exception $e) {
                        $conn->rollBack();
                        $error = 'Error al actualizar usuario: ' . $e->getMessage();
                    }
                }
            } else {
                // === Creación de nuevo usuario por administrador ===
                // Verificar unicidad
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = :username OR email = :email");
                $stmt->execute(['username' => $username, 'email' => $email]);
                if ($stmt->rowCount() > 0) {
                    $error = 'El nombre de usuario o email ya están registrados.';
                } else {
                    // Iniciar transacción
                    $conn->beginTransaction();
                    try {
                        // Crear el usuario primero
                        $password_hash = password_hash($password, PASSWORD_BCRYPT);
                        $ist = $conn->prepare("INSERT INTO usuarios (username, email, password_hash, nombre_completo, rol_id, estado) VALUES (:username, :email, :password_hash, :nombre_completo, :rol_id, true) RETURNING id");
                        $ist->execute([
                            'username' => $username,
                            'email' => $email,
                            'password_hash' => $password_hash,
                            'nombre_completo' => $nombre_completo,
                            'rol_id' => $rol_id
                        ]);
                        $usuario_id = (int)$ist->fetchColumn();

                        // Crear cliente asociado SOLO si el rol es Cliente
                        if ($roleName && strtolower($roleName) === 'cliente' && !empty($cedula_rif)) {
                            $cins = $conn->prepare('INSERT INTO clientes (cedula_rif, nombre_completo, email, telefono_principal, direccion, usuario_id) VALUES (:cedula_rif, :nombre_completo, :email, :telefono_principal, :direccion, :usuario_id) RETURNING id');
                            $cins->execute([
                                'cedula_rif' => $cedula_rif,
                                'nombre_completo' => $nombre_completo,
                                'email' => $email,
                                'telefono_principal' => $telefono_principal,
                                'direccion' => $direccion,
                                'usuario_id' => $usuario_id
                            ]);
                            $crow = $cins->fetch(PDO::FETCH_ASSOC);
                            $cliente_id = $crow['id'] ?? null;
                            if (!empty($cliente_id)) {
                                $conn->prepare('UPDATE usuarios SET cliente_id = :cliente_id WHERE id = :id')->execute(['cliente_id' => $cliente_id, 'id' => $usuario_id]);
                            }
                        }

                        // Registrar en bitácora
                        $stmt = $conn->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, ip_address) VALUES (:usuario_id, :accion, :tabla_afectada, :registro_id, :detalles, :ip_address)");
                        $stmt->execute([
                            'usuario_id' => $_SESSION['user_id'] ?? null,
                            'accion' => 'REGISTRO_USUARIO',
                            'tabla_afectada' => 'usuarios',
                            'registro_id' => $usuario_id,
                            'detalles' => json_encode(['username' => $username, 'email' => $email, 'rol_id' => $rol_id]),
                            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
                        ]);

                        $conn->commit();
                        $success = 'Usuario registrado exitosamente.';
                        $_POST = [];
                    } catch (Exception $e) {
                        $conn->rollBack();
                        $error = 'Error al registrar usuario: ' . $e->getMessage();
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Error de base de datos: ' . $e->getMessage();
        }
    }
}

// Obtener backups existentes
$backup_files = [];
if (is_dir($backupDir)) {
    $files = glob($backupDir . DIRECTORY_SEPARATOR . '*.sql');
    if ($files) {
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        foreach ($files as $file) {
            $backup_files[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => date('d/m/Y H:i:s', filemtime($file)),
                'path' => $file
            ];
        }
    }
}

// Verificar herramientas PostgreSQL
$pg_dump_found = find_postgres_executable('pg_dump');
$psql_found = find_postgres_executable('psql');
?>
<?php $base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Sistema - Inversiones Rojas</title>
    <script>var APP_BASE = '<?php echo $base_url; ?>';</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/layouts/configuracion.css">
    <style>
        /* Estilos para botón de regreso mejorado */
        .back-btn-wrapper {
            padding: 18px 24px;
            background: transparent;
        }
        .back-btn-enhanced {
            background: linear-gradient(135deg, #1F9166 0%, #30B583 100%);
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(31,145,102,0.18);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .back-btn-enhanced:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(31,145,102,0.22); }
        .back-btn-enhanced i { font-size: 16px; }

        /* Estilos específicos para la sección de backup */
        .backup-section {
            border-radius: 10px;
        }
        
        .backup-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .backup-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .backup-btn-primary {
            background: #28a745;
            color: white;
        }
        
        .backup-btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .backup-btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .backup-btn-secondary:hover {
            background: #5a6268;
        }
        
        .backup-btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .backup-btn-danger:hover {
            background: #c82333;
        }
        
        .backup-list {
            border-radius: 8px;
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .backup-item:last-child {
            border-bottom: none;
        }
        
        .backup-info {
            flex: 1;
        }
        
        .backup-name {
            font-weight: 600;
            color: #333;
        }
        
        .backup-meta {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        
        .backup-actions-small {
            display: flex;
            gap: 8px;
        }
        
        .backup-action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 0.9em;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .alert-box {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .section-subtitle {
            color: #666;
            font-size: 0.95em;
            margin-top: 5px;
        }
  
        /* ========================================== */
        /* ESTILOS PARA LA BITÁCORA */
        /* ========================================== */
        .activity-section {
            margin-top: 30px;
            padding: 25px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .activity-section h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #1F9166;
            transition: all 0.3s;
        }

        .activity-item:hover {
            background: #f1f3f4;
            transform: translateX(5px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .activity-icon.sales {
            background: linear-gradient(135deg, #1F9166, #30B583);
        }

        .activity-icon.payment {
            background: linear-gradient(135deg, #3498db, #5dade2);
        }

        .activity-icon.client {
            background: linear-gradient(135deg, #9b59b6, #bb8fce);
        }

        .activity-icon.info {
            background: linear-gradient(135deg, #f39c12, #f5b041);
        }

        .activity-content {
            flex: 1;
        }

        .activity-content p {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-weight: 500;
            line-height: 1.4;
        }

        .activity-content span {
            font-size: 0.85rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .activity-content span i {
            font-size: 0.75rem;
        }

        /* Estilos para las estadísticas de bitácora */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            border-top: 4px solid #1F9166;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1F9166, #30B583);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 1.5rem;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Estilos para la tabla de usuarios */
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
        }
        
        .users-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .users-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .btn-icon {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 2px;
        }
        
        .btn-icon.edit {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-icon.toggle {
            background: #17a2b8;
            color: white;
        }
        
        .date-compact {
            font-size: 0.9rem;
            color: #666;
        }
        
        .actions-cell {
            white-space: nowrap;
        }
        
        .search-container {
            margin: 20px 0;
        }
        
        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto auto;
            gap: 10px;
            align-items: center;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #1F9166;
            color: #1F9166;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-outline:hover {
            background: #1F9166;
            color: white;
        }
        
        .btn-primary {
            background: #1F9166;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: #30B583;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(31,145,102,0.3);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .form-control {
            padding: 10px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.95rem;
            width: 100%;
        }
        
        .form-control:focus {
            border-color: #1F9166;
            outline: none;
            box-shadow: 0 0 0 3px rgba(31, 145, 102, 0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-hint {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: flex-end;
        }
        
        .config-tabs {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .tabs-header {
            display: flex;
            gap: 5px;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 30px;
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            color: #1F9166;
        }
        
        .tab-btn.active {
            color: #1F9166;
            border-bottom-color: #1F9166;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .config-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            color: #2c3e50;
            font-size: 1.5rem;
        }
        
        /* Estilos para el modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .tabs-header {
                flex-wrap: wrap;
            }
            
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .backup-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .backup-actions-small {
                width: 100%;
                justify-content: flex-end;
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
            }
        }

        /* ========================================== */
        /* ESTILOS PARA LA SECCIÓN DE AYUDA */
        /* ========================================== */
        .help-section {
            max-width: 600px;
            margin: 0 auto;
        }

        .manual-download-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            text-align: center;
            margin-bottom: 30px;
        }

        .manual-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .manual-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .manual-info h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .manual-info p {
            margin: 0;
            color: #6c757d;
            font-size: 1rem;
            line-height: 1.5;
        }

        .manual-actions {
            margin-top: 20px;
        }

        .download-manual {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: #1F9166;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .download-manual:hover {
            background: #187a54;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(31,145,102,0.3);
        }

        .manual-not-available {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background: #fff3cd;
            color: #856404;
            border-radius: 8px;
            border: 1px solid #ffeaa7;
            font-weight: 500;
        }

        .no-manual {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px dashed #dee2e6;
        }

        .no-manual i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .no-manual h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .no-manual p {
            color: #6c757d;
            margin: 0;
        }

        .support-section {
            background: linear-gradient(135deg, #1F9166 0%, #30B583 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
        }

        .support-section h3 {
            margin: 0 0 10px 0;
            font-size: 1.3rem;
        }

        .support-section p {
            margin: 0 0 20px 0;
            opacity: 0.9;
        }

        .support-contact {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .contact-item i {
            font-size: 1.2rem;
            opacity: 0.9;
            min-width: 20px;
        }

        .contact-item div {
            text-align: left;
            flex: 1;
        }

        .contact-item strong {
            display: block;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .contact-item span {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .manual-download-card {
                padding: 20px;
            }

            .manual-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .support-contact {
                gap: 10px;
            }

            .contact-item {
                padding: 12px;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Botón de regreso para volver a la pantalla anterior -->
    <div class="back-btn-wrapper">
        <button class="back-btn-enhanced" onclick="window.location.href='<?php echo htmlspecialchars($previous_url); ?>'" aria-label="Regresar">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Regresar
        </button>
    </div>
    
    <!-- Tabs de Configuración -->
    <div class="config-tabs">
        <div class="tabs-header">
            <button class="tab-btn active" data-tab="general">
                <i class="fas fa-cogs"></i>
                <span>Configuración General</span>
            </button>
            <button class="tab-btn" data-tab="ayuda">
                <i class="fas fa-question-circle"></i>
                <span>Ayuda</span>
            </button>
            <?php if ($is_admin): ?>
            <button class="tab-btn" data-tab="users">
                <i class="fas fa-users-cog"></i>
                <span>Usuarios y Roles</span>
            </button>
            <!-- Empresa tab removed -->
            <button class="tab-btn" data-tab="bitacora">
                <i class="fas fa-history"></i>
                <span>Bitácora del Sistema</span>
            </button>
            <button class="tab-btn" data-tab="backup">
                <i class="fas fa-database"></i>
                <span>Backup</span>
            </button>
            <button class="tab-btn" data-tab="integraciones">
                <i class="fas fa-plug"></i>
                <span>Integraciones</span>
            </button>
            <button class="tab-btn" data-tab="pagos">
                <i class="fas fa-credit-card"></i>
                <span>Métodos de Pago</span>
            </button>
            <?php endif; ?>
        </div>

        <!-- Tab: Configuración General -->
        <div class="tab-content active" id="tab-general">
            <div class="config-section">
                <div class="section-header">
                    <h2><i class="fas fa-cogs"></i> Configuración General del Sistema</h2>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="systemName">
                            <i class="fas fa-signature"></i> Nombre del Sistema
                        </label>
                        <input type="text" id="systemName" class="form-control" value="Inversiones Rojas ERP">
                        <div class="form-hint">Nombre que aparece en el sistema</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="systemCurrency">
                            <i class="fas fa-dollar-sign"></i> Moneda del Sistema
                        </label>
                        <select id="systemCurrency" class="form-control">
                            <option value="USD" selected>USD - Dólar Americano</option>
                            <option value="VES">VES - Bolívar Soberano</option>
                            <option value="EUR">EUR - Euro</option>
                        </select>
                        <div class="form-hint">Moneda para cálculos y reportes</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tasaCambio">
                            <i class="fas fa-exchange-alt"></i> Tasa de Cambio (Bs por USD)
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" id="tasaCambio" class="form-control" step="0.01" min="0" placeholder="Ej: 36.50" style="flex: 1;">
                            <button type="button" class="btn btn-primary" onclick="guardarTasaCambio()" style="white-space: nowrap;">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                        </div>
                        <div class="form-hint">Tasa del Bolívar respecto al Dólar (USD × Tasa = Bs)</div>
                        <div id="tasaMensaje" style="margin-top: 8px; font-size: 0.9em;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="timezone">
                            <i class="fas fa-clock"></i> Zona Horaria
                        </label>
                        <select id="timezone" class="form-control">
                            <option value="America/Caracas" selected>Caracas (GMT-4)</option>
                            <option value="America/Mexico_City">Ciudad de México (GMT-6)</option>
                            <option value="America/New_York">Nueva York (GMT-5)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="dateFormat">
                            <i class="fas fa-calendar"></i> Formato de Fecha
                        </label>
                        <select id="dateFormat" class="form-control">
                            <option value="d/m/Y" selected>DD/MM/YYYY</option>
                            <option value="m/d/Y">MM/DD/YYYY</option>
                            <option value="Y-m-d">YYYY-MM-DD</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="language">
                            <i class="fas fa-language"></i> Idioma del Sistema
                        </label>
                        <select id="language" class="form-control">
                            <option value="es" selected>Español</option>
                            <option value="en">Inglés</option>
                        </select>
                    </div>
                </div>
                
                <!-- Información de empresa removida -->
                
                <div class="action-buttons">
                    <button class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                    <button class="btn btn-outline">
                        <i class="fas fa-redo"></i>
                        Restablecer
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab: Ayuda -->
        <div class="tab-content" id="tab-ayuda">
            <div class="config-section">
                <div class="section-header">
                    <h2><i class="fas fa-question-circle"></i> Centro de Ayuda</h2>
                    <p>Descarga el manual de usuario correspondiente a tu rol</p>
                </div>

                <div class="help-section">
                    <?php
                    // Determinar el rol del usuario
                    $user_role_name = '';
                    if ($conn && isset($_SESSION['user_id'])) {
                        $stmt = $conn->prepare("SELECT r.nombre FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE u.id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $user_role_name = $stmt->fetchColumn() ?: '';
                    }

                    // Configurar manual según rol
                    if ($is_admin || strtolower($user_role_name) === 'administrador') {
                        $manual_info = [
                            'titulo' => 'Manual del Administrador',
                            'descripcion' => 'Guía completa para administradores del sistema',
                            'archivo' => 'MANUAL(ADMINISTRADOR).pdf',
                            'disponible' => true,
                            'color' => '#e74c3c',
                            'icono' => 'fas fa-user-shield'
                        ];
                    } elseif (in_array(strtolower($user_role_name), ['gerente', 'manager'])) {
                        $manual_info = [
                            'titulo' => 'Manual del Gerente',
                            'descripcion' => 'Guía para gerentes del sistema',
                            'archivo' => 'MANUAL(GERENTE).pdf',
                            'disponible' => true,
                            'color' => '#f39c12',
                            'icono' => 'fas fa-user-tie'
                        ];
                    } elseif (in_array(strtolower($user_role_name), ['operador'])) {
                        $manual_info = [
                            'titulo' => 'Manual del Operador',
                            'descripcion' => 'Guía para operadores del sistema',
                            'archivo' => 'MANUAL(OPERADOR).pdf',
                            'disponible' => true,
                            'color' => '#2980b9',
                            'icono' => 'fas fa-tools'
                        ];
                    } elseif (in_array(strtolower($user_role_name), ['vendedor', 'ventas', 'comercial'])) {
                        $manual_info = [
                            'titulo' => 'Manual del Vendedor',
                            'descripcion' => 'Guía para vendedores del sistema',
                            'archivo' => 'MANUAL(VENDEDOR).pdf',
                            'disponible' => true,
                            'color' => '#27ae60',
                            'icono' => 'fas fa-shopping-cart'
                        ];
                    } else {
                        // Por defecto mostrar manual de cliente
                        $manual_info = [
                            'titulo' => 'Manual del Cliente',
                            'descripcion' => 'Guía para clientes del sistema',
                            'archivo' => 'MANUAL(CLIENTE).pdf',
                            'disponible' => true,
                            'color' => '#1F9166',
                            'icono' => 'fas fa-book'
                        ];
                    }
                    ?>

                    <div class="manual-download-card">
                        <div class="manual-header">
                            <div class="manual-icon" style="background: linear-gradient(135deg, #1F9166, #30B583);">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="manual-info">
                                <h3><?php echo $manual_info['titulo']; ?></h3>
                                <p><?php echo $manual_info['descripcion']; ?></p>
                            </div>
                        </div>

                        <div class="manual-actions">
                            <?php if ($manual_info['disponible']): ?>
                                <button class="download-manual" data-file="<?php echo $manual_info['archivo']; ?>" data-title="<?php echo $manual_info['titulo']; ?>">
                                    <i class="fas fa-download"></i>
                                    Descargar Manual
                                </button>
                            <?php else: ?>
                                <div class="manual-not-available">
                                    <i class="fas fa-clock"></i>
                                    Manual en desarrollo
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>


                </div>
            </div>
        </div>

        <?php if ($is_admin): ?>
        <!-- Tab: Usuarios y Roles -->
        <div class="tab-content" id="tab-users">
            <!-- Sección de Gestión de Usuarios con BUSCADOR -->
            <div class="config-section">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> Gestión de Usuarios</h2>
                    <button class="btn btn-primary" id="newUserBtn">
                        <i class="fas fa-user-plus"></i>
                        Nuevo Usuario
                    </button>
                </div>
                
                <!-- BUSCADOR DE USUARIOS -->
                <div class="search-container">
                    <?php
                    // Preparar filtros desde GET
                    $q = trim($_GET['q'] ?? '');
                    $filterRole = $_GET['role'] ?? '';
                    // Pasar null cuando no se especifique status
                    $filterStatus = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
                    ?>
                    
                    <form method="get" class="search-form" id="usersFilterForm">
                        <input type="hidden" name="tab" value="users" />
                        
                        <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" 
                               placeholder="Buscar por nombre, usuario o email..." class="form-control" />
                        
                        <select name="role" class="form-control">
                            <option value="">Todos los roles</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo htmlspecialchars($r['id']); ?>" 
                                    <?php echo ((string)$filterRole === (string)$r['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="status" class="form-control">
                            <option value="">Todos los estados</option>
                            <option value="activo" <?php if ($filterStatus === 'activo') echo 'selected'; ?>>Activos</option>
                            <option value="inactivo" <?php if ($filterStatus === 'inactivo') echo 'selected'; ?>>Inactivos</option>
                        </select>
                        
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        
                        <a class="btn btn-outline" href="<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?>?tab=users">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </form>
                </div>
                
                <!-- Tabla de usuarios -->
                <?php
                // Obtener usuarios con filtros
                if (!isset($users) || !isset($usersError)) {
                    require_once __DIR__ . '/../../models/database.php';
                    require_once __DIR__ . '/../../models/Usuario.php';

                    $users = [];
                    $usersError = null;

                    $db = new Database();
                    $conn = $db->getConnection();
                    if ($conn) {
                        $usuarioModel = new Usuario($conn);
                        $res = $usuarioModel->obtenerFiltrados($q ?? '', $filterRole ?? '', $filterStatus ?? '');
                        if ($res !== false) {
                            $users = $res;
                            // Construir mapa de roles id => nombre para mostrar en la tabla
                            try {
                                $rolesMap = [];
                                $rstmt = $conn->query("SELECT id, nombre FROM roles");
                                $allRoles = $rstmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($allRoles as $rr) {
                                    $rolesMap[$rr['id']] = $rr['nombre'];
                                }
                            } catch (Exception $e) {
                                $rolesMap = [];
                            }
                        } else {
                            $usersError = 'No se pudieron obtener los usuarios.';
                        }
                    } else {
                        $usersError = 'Error de conexión a la base de datos.';
                    }
                }
                ?>
                
                <?php if ($usersError): ?>
                    <div style="margin-top:10px; padding:12px; background:#ffecec; border:1px solid #f5c2c2; color:#8a1f1f; border-radius:6px;">
                        <?php echo htmlspecialchars($usersError); ?>
                    </div>
                <?php else: ?>
                    <?php 
                    if (!function_exists('format_date_only')) {
                        function format_date_only($dt) {
                            if (empty($dt)) return '';
                            try {
                                $d = new DateTime($dt);
                                return $d->format('d/m/Y');
                            } catch (Exception $e) {
                                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $dt, $m)) return $m[1];
                                return preg_replace('/\s.*$/', '', $dt);
                            }
                        }
                    }
                    ?>
                    
                    <div style="overflow-x: auto;">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Nombre</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Últ. acceso</th>
                                    <th>Creado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 20px;">
                                            <?php echo empty($q) && empty($filterRole) && empty($filterStatus) ? 
                                                'No hay usuarios registrados' : 
                                                'No se encontraron usuarios con los filtros aplicados'; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u['id']); ?></td>
                                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><?php echo htmlspecialchars($u['nombre_completo']); ?></td>
                                        <td><?php echo htmlspecialchars($rolesMap[$u['rol_id']] ?? $u['rol_id']); ?></td>
                                        <td>
                                            <span class="<?php echo $u['estado'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $u['estado'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td class="date-compact"><?php echo htmlspecialchars(format_date_only($u['ultimo_acceso'])); ?></td>
                                        <td class="date-compact"><?php echo htmlspecialchars(format_date_only($u['created_at'])); ?></td>
                                        <td class="actions-cell">
                                            <button class="btn-icon edit btn-edit-user" title="Editar" 
                                                    data-id="<?php echo $u['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($u['username'], ENT_QUOTES); ?>"
                                                    data-email="<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>"
                                                    data-nombre="<?php echo htmlspecialchars($u['nombre_completo'], ENT_QUOTES); ?>"
                                                    data-rol="<?php echo htmlspecialchars($u['rol_id'], ENT_QUOTES); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon toggle btn-toggle-user" title="Cambiar estado" 
                                                    data-id="<?php echo $u['id']; ?>"
                                                    data-estado="<?php echo $u['estado'] ? '1' : '0'; ?>">
                                                <i class="<?php echo $u['estado'] ? 'fas fa-user-slash' : 'fas fa-user-check'; ?>"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sección Empresa eliminada -->

        <!-- Tab: Bitácora del Sistema -->
        <div class="tab-content" id="tab-bitacora">
            <div class="config-section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Bitácora del Sistema</h2>
                    <p class="section-subtitle">Auditoría de actividades de todos los usuarios</p>
                </div>
                
                <!-- Filtros de búsqueda -->
                <div class="search-container" style="margin-bottom: 20px;">
                    <form method="get" class="search-form" id="bitacoraFilterForm">
                        <input type="hidden" name="tab" value="bitacora" />
                        <input type="hidden" name="page" value="<?php echo (int)($_GET['page'] ?? 1); ?>" id="bitacoraPage" />
                        
                        <input type="search" name="bitacora_q" value="<?php echo htmlspecialchars($_GET['bitacora_q'] ?? ''); ?>" 
                               placeholder="Buscar por acción, usuario o tabla..." class="form-control" />
                        
                        <select name="bitacora_usuario" class="form-control">
                            <option value="">Todos los usuarios</option>
                            <?php
                            // Obtener lista de usuarios para el filtro
                            $usuarios_bitacora = [];
                            if ($conn) {
                                $stmt = $conn->query("SELECT id, username, nombre_completo FROM usuarios ORDER BY nombre_completo");
                                $usuarios_bitacora = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            }
                            foreach ($usuarios_bitacora as $usuario): 
                            ?>
                                <option value="<?php echo htmlspecialchars($usuario['id']); ?>" 
                                    <?php echo ((string)($_GET['bitacora_usuario'] ?? '') === (string)$usuario['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usuario['nombre_completo'] . ' (' . $usuario['username'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="bitacora_fecha" class="form-control">
                            <option value="">Todo el tiempo</option>
                            <option value="today" <?php echo ($_GET['bitacora_fecha'] ?? '') === 'today' ? 'selected' : ''; ?>>Hoy</option>
                            <option value="week" <?php echo ($_GET['bitacora_fecha'] ?? '') === 'week' ? 'selected' : ''; ?>>Última semana</option>
                            <option value="month" <?php echo ($_GET['bitacora_fecha'] ?? '') === 'month' ? 'selected' : ''; ?>>Último mes</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        
                        <button class="btn btn-outline" id="generateBitacoraReportBtn">
                            <i class="fas fa-file-pdf"></i> Generar Reporte
                        </button>
                    </form>
                </div>
                
                <!-- Estadísticas rápidas -->
                <div class="profile-stats" style="margin: 20px 0;">
                    <?php
                    // Obtener estadísticas de bitácora
                    $stats_bitacora = [];
                    if ($conn) {
                        try {
                            // Total de registros
                            $stmt = $conn->query("SELECT COUNT(*) as total FROM bitacora_sistema");
                            $stats_bitacora['total'] = $stmt->fetchColumn();
                            
                            // Hoy
                            $stmt = $conn->query("SELECT COUNT(*) as hoy FROM bitacora_sistema WHERE DATE(created_at) = CURRENT_DATE");
                            $stats_bitacora['hoy'] = $stmt->fetchColumn();
                            
                            // Última semana
                            $stmt = $conn->query("SELECT COUNT(*) as semana FROM bitacora_sistema WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'");
                            $stats_bitacora['semana'] = $stmt->fetchColumn();
                            
                            // Usuarios distintos
                            $stmt = $conn->query("SELECT COUNT(DISTINCT usuario_id) as usuarios FROM bitacora_sistema WHERE usuario_id IS NOT NULL");
                            $stats_bitacora['usuarios'] = $stmt->fetchColumn();
                        } catch (Exception $e) {
                            $stats_bitacora = ['total' => 0, 'hoy' => 0, 'semana' => 0, 'usuarios' => 0];
                        }
                    }
                    ?>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats_bitacora['total']); ?></div>
                        <div class="stat-label">Total Registros</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats_bitacora['hoy']); ?></div>
                        <div class="stat-label">Hoy</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats_bitacora['semana']); ?></div>
                        <div class="stat-label">Última Semana</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?php echo number_format($stats_bitacora['usuarios']); ?></div>
                        <div class="stat-label">Usuarios Activos</div>
                    </div>
                </div>
                
                <!-- Lista de actividades (bitácora) -->
                <div class="activity-section">
                    <h3><i class="fas fa-list-ol"></i> Actividades Recientes</h3>
                    
                    <div id="bitacoraList" class="activity-list">
                        <!-- Los registros se cargan aquí con AJAX -->
                    </div>
                    
                    <div id="bitacoraLoading" style="display: none; text-align: center; padding: 20px;">
                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                    </div>
                </div>
                
                <!-- Paginación y acciones -->
                <div class="action-buttons">
                    <button class="btn btn-primary" id="loadMoreBitacoraBtn">
                        <i class="fas fa-plus"></i>
                        Ver más movimientos
                    </button>
                    <?php if (($_SESSION['rol_id'] ?? 0) == 1): // Solo administrador ?>
                    <button class="btn btn-danger" id="cleanBitacoraBtn">
                        <i class="fas fa-trash"></i>
                        Limpiar Bitácora Antigua
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab: Backup y Restauración -->
        <div class="tab-content" id="tab-backup">
            <div class="config-section">
                <div class="section-header">
                    <h2><i class="fas fa-database"></i> Sistema de Backup</h2>
                    <p class="section-subtitle">Crea, restaura y gestiona copias de seguridad de la base de datos</p>
                </div>

                <!-- Alertas -->
                <div id="backupAlert" class="alert-box"></div>

                <!-- Comprobación del sistema -->
                <?php if (!$pg_dump_found || !$psql_found || !$backup_writable): ?>
                    <div class="alert-warning" style="display: block;">
                        <strong><i class="fas fa-exclamation-triangle"></i> Advertencias del sistema:</strong>
                        <ul>
                            <?php if (!$pg_dump_found): ?>
                                <li><code>pg_dump</code> no encontrado. Verifique la instalación de PostgreSQL.</li>
                            <?php endif; ?>
                            <?php if (!$psql_found): ?>
                                <li><code>psql</code> no encontrado. Verifique la instalación de PostgreSQL.</li>
                            <?php endif; ?>
                            <?php if (!$backup_writable): ?>
                                <li>El directorio de backups no tiene permisos de escritura: <?php echo htmlspecialchars($backupDir); ?></li>
                            <?php endif; ?>
                        </ul>
                        <p><small>Solución: Configure las rutas en config.php o instale PostgreSQL</small></p>
                    </div>
                <?php endif; ?>

                <!-- Acciones principales -->
                <div class="backup-section">
                    <h3><i class="fas fa-bolt"></i> Acciones Rápidas</h3>
                    <br>
                    <div class="backup-actions">
                        <button id="createBackupBtn" class="btn btn-primary" <?php echo !$pg_dump_found ? 'disabled' : ''; ?> type="button">
                            <i class="fas fa-plus-circle"></i>
                            <span>Crear Backup Ahora</span>
                        </button>

                        <button id="uploadBackupBtn" class="btn btn-outline" type="button">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Subir Backup</span>
                        </button>

                        <div style="flex: 1;"></div>

                        <button id="cleanOldBtn" class="btn btn-danger" type="button">
                            <i class="fas fa-trash-alt"></i>
                            <span>Limpiar Backups Antiguos (&gt;30 días)</span>
                        </button>
                    </div>
                    
                    <!-- Contador de backups -->
                    <div style="text-align: center; margin: 20px 0; padding: 15px; background: #e9ecef; border-radius: 6px;">
                        <h4 style="margin: 0;">
                            <i class="fas fa-history"></i> 
                            <?php echo count($backup_files); ?> backups disponibles
                        </h4>
                        <p style="margin: 5px 0 0 0; color: #666;">
                            Espacio total: 
                            <?php 
                            $total_size = 0;
                            foreach ($backup_files as $file) {
                                $total_size += $file['size'];
                            }
                            echo round($total_size / (1024*1024), 2) . ' MB';
                            ?>
                        </p>
                    </div>
                </div>

                <?php if (empty($backup_files)): ?>
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-database fa-3x" style="margin-bottom: 15px; opacity: 0.5;"></i>
                        <h4>No hay backups disponibles</h4>
                        <p>Presiona "Crear Backup Ahora" para crear tu primera copia de seguridad.</p>
                    </div>
                <?php else: ?>
                    <div class="backup-list">
                        <?php foreach ($backup_files as $backup): ?>
                            <div class="backup-item" data-filename="<?php echo htmlspecialchars($backup['name']); ?>">
                                <div class="backup-info">
                                    <div class="backup-name">
                                        <i class="fas fa-file-archive"></i>
                                        <?php echo htmlspecialchars($backup['name']); ?>
                                    </div>
                                    <div class="backup-meta">
                                        <span><i class="far fa-clock"></i> <?php echo $backup['modified']; ?></span>
                                        <span style="margin-left: 15px;"><i class="fas fa-hdd"></i> <?php echo round($backup['size'] / (1024*1024), 2); ?> MB</span>
                                    </div>
                                </div>
                                <div class="backup-actions-small">
                                    <button class="backup-action-btn" onclick="downloadBackup('<?php echo htmlspecialchars($backup['name']); ?>')" title="Descargar">
                                        <i class="fas fa-download"></i> Descargar
                                    </button>
                                    <button class="backup-action-btn" onclick="restoreBackup('<?php echo htmlspecialchars($backup['name']); ?>')" title="Restaurar" style="background: #17a2b8; color: white;">
                                        <i class="fas fa-upload"></i> Restaurar
                                    </button>
                                    <button class="backup-action-btn" onclick="deleteBackup('<?php echo htmlspecialchars($backup['name']); ?>')" title="Eliminar" style="background: #dc3545; color: white;">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab: Integraciones -->
        <div class="tab-content" id="tab-integraciones">
            <div class="config-section">
                <div class="section-header">
                    <h2><i class="fas fa-plug"></i> Configuración de Integraciones</h2>
                    <p>Define los canales de comunicación disponibles para los pedidos digitales. Los cambios se guardan en la base de datos y se aplican de inmediato.</p>
                </div>

                <!-- Alerta de feedback visible -->
                <div id="intAlert" style="display:none;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-weight:500;"></div>

                <form id="integrationsForm">

                    <!-- WhatsApp -->
                    <div class="form-section" style="border:1px solid #e0e0e0;border-radius:10px;padding:20px;margin-bottom:20px;">
                        <h3 style="margin:0 0 15px;display:flex;align-items:center;gap:10px;">
                            <i class="fab fa-whatsapp" style="color:#25d366;font-size:1.4rem;"></i> WhatsApp Business
                            <span id="wa-status" style="font-size:12px;font-weight:400;padding:3px 10px;border-radius:12px;background:<?php echo intCfg($int_cfg,'whatsapp_enabled','0')==='1'?'#e8f6f1;color:#1F9166':'#f5f5f5;color:#aaa'; ?>;">
                                <?php echo intCfg($int_cfg,'whatsapp_enabled','0')==='1' ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Número de WhatsApp de la tienda</label>
                                <input type="text" name="whatsapp_number" class="form-control"
                                       value="<?php echo htmlspecialchars(intCfg($int_cfg,'whatsapp_number','')); ?>"
                                       placeholder="Ej: 584121234567 (solo dígitos con código de país)">
                                <div class="form-hint">Sin +, sin espacios. Ej Venezuela: 584121234567</div>
                            </div>
                            <div class="form-group" style="display:flex;align-items:center;gap:10px;padding-top:28px;">
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
                                    <input type="checkbox" name="whatsapp_enabled" value="1"
                                           <?php echo intCfg($int_cfg,'whatsapp_enabled','0')==='1' ? 'checked' : ''; ?>
                                           style="width:16px;height:16px;">
                                    Habilitar canal WhatsApp
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-section" style="border:1px solid #e0e0e0;border-radius:10px;padding:20px;margin-bottom:20px;">
                        <h3 style="margin:0 0 15px;display:flex;align-items:center;gap:10px;">
                            <i class="fas fa-envelope" style="color:#d93025;font-size:1.4rem;"></i> Gmail / Email
                            <span style="font-size:12px;font-weight:400;padding:3px 10px;border-radius:12px;background:<?php echo intCfg($int_cfg,'email_enabled','0')==='1'?'#e8f6f1;color:#1F9166':'#f5f5f5;color:#aaa'; ?>;">
                                <?php echo intCfg($int_cfg,'email_enabled','0')==='1' ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-at"></i> Email de la tienda (destino de pedidos)</label>
                                <input type="email" name="email_notifications" class="form-control"
                                       value="<?php echo htmlspecialchars(intCfg($int_cfg,'email_notifications','')); ?>"
                                       placeholder="pedidos@tuempresa.com">
                                <div class="form-hint">Aquí llegarán los pedidos cuando el cliente elija Email</div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-paper-plane"></i> Email remitente (From)</label>
                                <input type="email" name="email_from" class="form-control"
                                       value="<?php echo htmlspecialchars(intCfg($int_cfg,'email_from','no-reply@inversionesrojas.com')); ?>"
                                       placeholder="no-reply@inversionesrojas.com">
                                <div class="form-hint">Dirección que aparece como remitente en los correos</div>
                            </div>
                            <div class="form-group" style="display:flex;align-items:center;gap:10px;padding-top:28px;">
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
                                    <input type="checkbox" name="email_enabled" value="1"
                                           <?php echo intCfg($int_cfg,'email_enabled','0')==='1' ? 'checked' : ''; ?>
                                           style="width:16px;height:16px;">
                                    Habilitar canal Email
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Telegram -->
                    <div class="form-section" style="border:1px solid #e0e0e0;border-radius:10px;padding:20px;margin-bottom:20px;">
                        <h3 style="margin:0 0 15px;display:flex;align-items:center;gap:10px;">
                            <i class="fab fa-telegram" style="color:#0088cc;font-size:1.4rem;"></i> Telegram
                            <span style="font-size:12px;font-weight:400;padding:3px 10px;border-radius:12px;background:<?php echo intCfg($int_cfg,'telegram_enabled','0')==='1'?'#e8f6f1;color:#1F9166':'#f5f5f5;color:#aaa'; ?>;">
                                <?php echo intCfg($int_cfg,'telegram_enabled','0')==='1' ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </h3>
                        <div class="form-hint" style="margin-bottom:12px;padding:10px;background:#e8f0fe;border-radius:6px;font-size:12px;">
                            <strong>¿Cómo configurar?</strong> 1) Crea un bot en @BotFather y copia el token.
                            2) Agrega el bot a tu canal/grupo y obtén el Chat ID usando @userinfobot.
                            3) El username es opcional pero ayuda a identificar el canal.
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-key"></i> Token del Bot</label>
                                <input type="text" name="telegram_bot_token" class="form-control"
                                       value="<?php echo htmlspecialchars(intCfg($int_cfg,'telegram_bot_token','')); ?>"
                                       placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz">
                                <div class="form-hint">Desde @BotFather en Telegram</div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-hashtag"></i> Chat ID</label>
                                <input type="text" name="telegram_chat_id" class="form-control"
                                       value="<?php echo htmlspecialchars(intCfg($int_cfg,'telegram_chat_id','')); ?>"
                                       placeholder="-1001234567890">
                                <div class="form-hint">ID del grupo o canal donde llegan los pedidos</div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-at"></i> Username del canal (opcional)</label>
                                <input type="text" name="telegram_username" class="form-control"
                                       value="<?php echo htmlspecialchars(intCfg($int_cfg,'telegram_username','')); ?>"
                                       placeholder="micanal (sin @)">
                                <div class="form-hint">Nombre del canal para mostrar en el carrito</div>
                            </div>
                            <div class="form-group" style="display:flex;align-items:center;gap:10px;padding-top:28px;">
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
                                    <input type="checkbox" name="telegram_enabled" value="1"
                                           <?php echo intCfg($int_cfg,'telegram_enabled','0')==='1' ? 'checked' : ''; ?>
                                           style="width:16px;height:16px;">
                                    Habilitar canal Telegram
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Notificaciones Internas -->
                    <div class="form-section" style="border:1px solid #e0e0e0;border-radius:10px;padding:20px;margin-bottom:20px;">
                        <h3 style="margin:0 0 15px;display:flex;align-items:center;gap:10px;">
                            <i class="fas fa-bell" style="color:#f39c12;font-size:1.4rem;"></i> Notificaciones Internas (Web)
                            <span style="font-size:12px;font-weight:400;padding:3px 10px;border-radius:12px;background:<?php echo intCfg($int_cfg,'internal_notifications_enabled','1')==='1'?'#e8f6f1;color:#1F9166':'#f5f5f5;color:#aaa'; ?>;">
                                <?php echo intCfg($int_cfg,'internal_notifications_enabled','1')==='1' ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
                                    <input type="checkbox" name="internal_notifications_enabled" value="1"
                                           <?php echo intCfg($int_cfg,'internal_notifications_enabled','1')==='1' ? 'checked' : ''; ?>
                                           style="width:16px;height:16px;">
                                    Habilitar notificaciones internas
                                </label>
                                <div class="form-hint">Los pedidos aparecen en el panel del vendedor como notificaciones en tiempo real</div>
                            </div>
                            <div class="form-group">
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;">
                                    <input type="checkbox" name="auto_assign_vendors" value="1"
                                           <?php echo intCfg($int_cfg,'auto_assign_vendors','0')==='1' ? 'checked' : ''; ?>
                                           style="width:16px;height:16px;">
                                    Asignación automática de vendedores
                                </label>
                                <div class="form-hint">Asigna pedidos automáticamente al vendedor disponible (si no, queda pendiente de asignar)</div>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;gap:12px;justify-content:flex-end;flex-wrap:wrap;">
                        <button type="button" class="btn btn-outline" onclick="testIntegrations()">
                            <i class="fas fa-flask"></i> Probar Conexiones
                        </button>
                        <button type="button" class="btn btn-primary" id="btnGuardarIntegraciones" onclick="guardarIntegraciones()">
                            <i class="fas fa-save"></i> Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tab: Métodos de Pago -->
        <div class="tab-content" id="tab-pagos">
            <div class="config-section">
                <div class="section-header">
                    <h2><i class="fas fa-credit-card"></i> Métodos de Pago para Reservas</h2>
                    <p>Configura los métodos de pago disponibles para apartados y reservas.</p>
                </div>

                <div id="paymentMethodsAlert" style="display:none;padding:12px 16px;border-radius:8px;margin-bottom:20px;font-weight:500;"></div>

                <div class="form-group">
                    <label for="paymentMethodType">Tipo de Método *</label>
                    <select id="paymentMethodType" class="form-control" onchange="updatePaymentForm()">
                        <option value="">Seleccionar tipo...</option>
                        <option value="pago_movil">Pago Móvil</option>
                        <option value="transferencia">Transferencia Bancaria</option>
                        <option value="binance">Binance</option>
                        <option value="paypal">PayPal</option>
                        <option value="zelle">Zelle</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                <div id="paymentFormFields">
                    <!-- Campos dinámicos se cargarán aquí -->
                </div>

                <div style="display:flex;gap:12px;justify-content:flex-end;flex-wrap:wrap;margin-bottom:30px;">
                    <button type="button" class="btn btn-outline" onclick="loadConfigPaymentMethods()">
                        <i class="fas fa-sync-alt"></i> Refrescar lista
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveConfigPaymentMethod()">
                        <i class="fas fa-save"></i> Guardar Método
                    </button>
                </div>

                <div class="form-section" style="border:1px solid #e0e0e0;border-radius:10px;padding:20px;background:#fafafa;">
                    <h3>Métodos configurados</h3>
                    <div id="paymentMethodsList" style="display:grid;gap:12px;">
                        <div style="color:#666;font-size:14px;">Cargando métodos...</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Nuevo Usuario -->
    <div class="modal-overlay" id="newUserModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</h3>
                <button class="modal-close" id="closeNewUserModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="newUserForm" method="POST" action="">
                    <input type="hidden" id="user_id" name="user_id" value="">
                    <div class="form-group">
                        <label for="userUsername">Nombre de Usuario *</label>
                        <input type="text" id="userUsername" name="username" class="form-control" placeholder="usuario123">
                    </div>
                    
                    <div class="form-group">
                        <label for="userEmail">Correo Electrónico *</label>
                        <input type="text" id="userEmail" name="email" class="form-control" placeholder="correo@ejemplo.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="userFullname">Nombre Completo *</label>
                        <input type="text" id="userFullname" name="nombre_completo" class="form-control" placeholder="Juan Pérez">
                    </div>

                    <div class="form-group">
                        <label for="userCedula">Cédula / RIF *</label>
                        <input type="text" id="userCedula" name="cedula_rif" class="form-control" placeholder="V-12345678">
                    </div>

                    <div class="form-group">
                        <label for="userPhone">Teléfono</label>
                        <input type="text" id="userPhone" name="telefono_principal" class="form-control" placeholder="0412-1234567">
                    </div>

                    <div class="form-group">
                        <label for="userRole">Rol *</label>
                        <select id="userRole" name="rol_id" class="form-control">
                            <option value="">Seleccionar rol...</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo htmlspecialchars($r['id']); ?>">
                                    <?php echo htmlspecialchars($r['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="userPassword">Contraseña *</label>
                        <input type="password" id="userPassword" name="password" class="form-control" placeholder="Mínimo 8 caracteres">
                    </div>
                    
                    <div class="form-group">
                        <label for="userConfirmPassword">Confirmar Contraseña *</label>
                        <input type="password" id="userConfirmPassword" name="confirm_password" class="form-control" placeholder="Repite tu contraseña">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="cancelNewUser">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveNewUser">Crear Usuario</button>
            </div>
        </div>
    </div>

    <!-- Modal para subir backup -->
    <div class="modal-overlay" id="uploadModal" style="display: none;">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3><i class="fas fa-cloud-upload-alt"></i> Subir Backup</h3>
                <button class="modal-close" onclick="closeUploadModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="uploadBackupForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="backupFile">Seleccionar archivo .sql</label>
                        <input type="file" id="backupFile" name="backupFile" accept=".sql" class="form-control">
                        <div class="form-hint">Solo se permiten archivos .sql de hasta 100MB</div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="autoRestore" name="autoRestore">
                            Restaurar automáticamente después de subir
                        </label>
                        <div class="form-hint" style="color: #dc3545;">
                            <i class="fas fa-exclamation-triangle"></i> Advertencia: Esto sobrescribirá la base de datos actual
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeUploadModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="uploadBackup()">Subir Archivo</button>
            </div>
        </div>
    </div>

<script>
    // Configuración
    window.APP_BASE = '<?php echo BASE_URL; ?>';
    
    // Elementos del DOM para backup
    const backupAlert = document.getElementById('backupAlert');
    const createBackupBtn = document.getElementById('createBackupBtn');
    const uploadBackupBtn = document.getElementById('uploadBackupBtn');
    const cleanOldBtn = document.getElementById('cleanOldBtn');
    const uploadModal = document.getElementById('uploadModal');
    
    // Elementos del DOM para bitácora
    const loadMoreBitacoraBtn = document.getElementById('loadMoreBitacoraBtn');
    const cleanBitacoraBtn = document.getElementById('cleanBitacoraBtn');
    
    // Mostrar alerta
    function showAlert(message, type = 'success') {
        backupAlert.innerHTML = `
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'exclamation-triangle'}"></i>
                <div>${message}</div>
            </div>
        `;
        backupAlert.className = `alert-box alert-${type}`;
        backupAlert.style.display = 'block';
        
        // Ocultar después de 5 segundos
        setTimeout(() => {
            backupAlert.style.display = 'none';
        }, 5000);
    }
    
    // Crear backup
    async function createBackup() {
        if (!confirm('¿Crear una nueva copia de seguridad de la base de datos?')) {
            return;
        }
        
        createBackupBtn.disabled = true;
        createBackupBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Creando backup...</span>';
        
        try {
            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=create_backup'
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert(`✅ Backup creado exitosamente: ${data.filename}`, 'success');
                // Recargar la lista después de 1 segundo
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(`❌ Error: ${data.message}`, 'error');
            }
        } catch (error) {
            showAlert(`❌ Error de conexión: ${error.message}`, 'error');
        } finally {
            createBackupBtn.disabled = false;
            createBackupBtn.innerHTML = '<i class="fas fa-plus-circle"></i> <span>Crear Backup Ahora</span>';
        }
    }
    
    // Descargar backup
    function downloadBackup(filename) {
        window.open(`${APP_BASE}/config/download_backup.php?file=${encodeURIComponent(filename)}`, '_blank');
    }
    
    // Restaurar backup
    async function restoreBackup(filename) {
        if (!confirm(`⚠️ ATENCIÓN: Esto sobrescribirá TODOS los datos actuales con los del backup "${filename}".\n\n¿Está completamente seguro de continuar?`)) {
            return;
        }
        
        if (!confirm('❌ ÚLTIMA ADVERTENCIA: Esta acción NO se puede deshacer. ¿Continuar?')) {
            return;
        }
        
        const password = prompt('Escriba "CONFIRMAR" para proceder con la restauración:');
        if (password !== 'CONFIRMAR') {
            alert('Restauración cancelada');
            return;
        }
        
        showAlert(`🔄 Restaurando backup: ${filename}...`, 'warning');
        
        try {
            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=restore_backup&filename=${encodeURIComponent(filename)}&confirm=yes`
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert(`✅ Base de datos restaurada exitosamente. La página se recargará en 3 segundos.`, 'success');
                setTimeout(() => location.reload(), 3000);
            } else {
                showAlert(`❌ Error al restaurar: ${data.message}`, 'error');
            }
        } catch (error) {
            showAlert(`❌ Error de conexión: ${error.message}`, 'error');
        }
    }
    
    // Eliminar backup
    async function deleteBackup(filename) {
        if (!confirm(`¿Eliminar permanentemente el backup "${filename}"?`)) {
            return;
        }
        
        try {
            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete_backup&filename=${encodeURIComponent(filename)}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert(`✅ Backup eliminado: ${filename}`, 'success');
                // Recargar la lista después de 1 segundo
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(`❌ Error: ${data.message}`, 'error');
            }
        } catch (error) {
            showAlert(`❌ Error de conexión: ${error.message}`, 'error');
        }
    }
    
    // Modal para subir backup
    function openUploadModal() {
        uploadModal.style.display = 'block';
    }
    
    function closeUploadModal() {
        uploadModal.style.display = 'none';
        document.getElementById('backupFile').value = '';
    }
    
    // Subir backup
    async function uploadBackup() {
        const fileInput = document.getElementById('backupFile');
        const autoRestore = document.getElementById('autoRestore').checked;
        
        if (!fileInput.files.length) {
            alert('Por favor seleccione un archivo');
            return;
        }
        
        const file = fileInput.files[0];
        if (!file.name.endsWith('.sql')) {
            alert('Solo se permiten archivos .sql');
            return;
        }
        
        if (file.size > 100 * 1024 * 1024) { // 100MB
            alert('El archivo es demasiado grande (máximo 100MB)');
            return;
        }
        
        if (autoRestore && !confirm('⚠️ ADVERTENCIA: Esto restaurará el backup después de subirlo, sobrescribiendo la base de datos actual. ¿Continuar?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('backupFile', file);
        formData.append('autoRestore', autoRestore ? '1' : '0');
        
        showAlert('📤 Subiendo archivo...', 'warning');
        
        try {
            const response = await fetch(`${APP_BASE}/config/upload_backup.php`, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert(`✅ ${data.message}`, 'success');
                closeUploadModal();
                setTimeout(() => location.reload(), 2000);
            } else {
                showAlert(`❌ ${data.message}`, 'error');
            }
        } catch (error) {
            showAlert(`❌ Error al subir: ${error.message}`, 'error');
        }
    }
    
    // Limpiar backups antiguos
    async function cleanOldBackups() {
        if (!confirm('¿Eliminar todos los backups con más de 30 días?')) {
            return;
        }
        
        showAlert('🧹 Limpiando backups antiguos...', 'warning');
        
        try {
            const response = await fetch(`${APP_BASE}/config/clean_backups.php`);
            const data = await response.text();
            showAlert('✅ Backups antiguos eliminados', 'success');
            setTimeout(() => location.reload(), 1000);
        } catch (error) {
            showAlert(`❌ Error: ${error.message}`, 'error');
        }
    }
    
    // Funciones para bitácora
    let currentBitacoraPage = 1;
    
    function loadBitacoraActivities(page = 1, append = false) {
        const listContainer = document.getElementById('bitacoraList');
        const loading = document.getElementById('bitacoraLoading');
        
        if (!append) {
            listContainer.innerHTML = '';
            currentBitacoraPage = 1;
        }
        
        loading.style.display = 'block';
        
        // Obtener filtros actuales
        const form = document.getElementById('bitacoraFilterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams();
        params.append('ajax', 'bitacora');
        params.append('page', page);
        formData.forEach((value, key) => {
            if (key !== 'tab' && key !== 'page') {
                params.append(key, value);
            }
        });
        
        fetch(window.location.pathname + '?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.length === 0 && !append) {
                    listContainer.innerHTML = `
                        <div class="activity-item">
                            <div class="activity-icon info">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="activity-content">
                                <p>No hay actividades registradas en la bitácora</p>
                                <span>Los usuarios aún no han realizado acciones en el sistema</span>
                            </div>
                        </div>
                    `;
                } else {
                    data.forEach(registro => {
                        const itemHTML = createActivityItemHTML(registro);
                        listContainer.insertAdjacentHTML('beforeend', itemHTML);
                    });
                    if (append) currentBitacoraPage = page;
                }
            })
            .catch(error => {
                console.error('Error cargando bitácora:', error);
                if (!append) {
                    listContainer.innerHTML = `
                        <div class="activity-item">
                            <div class="activity-icon info">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="activity-content">
                                <p>Error al cargar las actividades</p>
                                <span>Inténtalo de nuevo más tarde</span>
                            </div>
                        </div>
                    `;
                }
            })
            .finally(() => {
                loading.style.display = 'none';
            });
    }
    
    function createActivityItemHTML(registro) {
        // Determinar icono y color basado en la acción
        let icon = 'fas fa-cog';
        let color = 'info';
        const accion = registro.accion || '';
        
        if (accion.toLowerCase().includes('login') || accion.toLowerCase().includes('logout')) {
            icon = accion.toLowerCase().includes('login') ? 'fas fa-sign-in-alt' : 'fas fa-sign-out-alt';
            color = 'payment';
        } else if (accion.toLowerCase().includes('insert') || accion.toLowerCase().includes('create') || accion.toLowerCase().includes('nuevo')) {
            icon = 'fas fa-plus-circle';
            color = 'sales';
        } else if (accion.toLowerCase().includes('update') || accion.toLowerCase().includes('edit')) {
            icon = 'fas fa-edit';
            color = 'client';
        } else if (accion.toLowerCase().includes('delete') || accion.toLowerCase().includes('remove')) {
            icon = 'fas fa-trash-alt';
            color = 'info';
        } else if (accion.toLowerCase().includes('backup')) {
            icon = accion.toLowerCase().includes('restore') ? 'fas fa-upload' : 'fas fa-download';
            color = 'client';
        }
        
        // Formatear fecha
        const fecha = new Date(registro.created_at).toLocaleString('es-ES');
        const ahora = new Date();
        const diferencia = Math.floor((ahora - new Date(registro.created_at)) / 1000);
        let hace = '';
        if (diferencia < 3600) {
            hace = Math.floor(diferencia / 60) + ' minutos';
        } else if (diferencia < 86400) {
            hace = Math.floor(diferencia / 3600) + ' horas';
        } else {
            hace = Math.floor(diferencia / 86400) + ' días';
        }
        
        const detalles = registro.detalles ? JSON.parse(registro.detalles) : {};
        let detallesHTML = '';
        if (detalles && typeof detalles === 'object') {
            for (const [key, value] of Object.entries(detalles)) {
                if (typeof value !== 'object') {
                    detallesHTML += `<br/><strong>${key}:</strong> ${value}`;
                }
            }
        }
        
        return `
            <div class="activity-item">
                <div class="activity-icon ${color}">
                    <i class="${icon}"></i>
                </div>
                <div class="activity-content">
                    <p>
                        <strong>${accion}</strong>
                        ${registro.tabla_afectada ? `<span style="color: #666; font-size: 0.9em;">en ${registro.tabla_afectada}</span>` : ''}
                    </p>
                    <p style="font-size: 0.9em; margin: 5px 0; color: #555;">
                        ${registro.nombre_completo ? 
                            `<strong>Usuario:</strong> ${registro.nombre_completo} (${registro.username})` :
                            registro.usuario_id ? 
                                `<strong>Usuario ID:</strong> ${registro.usuario_id}` :
                                '<strong>Sistema</strong>'
                        }
                        ${detallesHTML}
                    </p>
                    <span>
                        <i class="far fa-clock"></i> ${fecha} (hace ${hace})
                        ${registro.ip_address ? ` • <i class="fas fa-network-wired"></i> IP: ${registro.ip_address}` : ''}
                    </span>
                </div>
            </div>
        `;
    }
    
    async function cleanBitacora() {
        if (!confirm('⚠️ ¿Eliminar registros de bitácora con más de 90 días?\n\nEsta acción no se puede deshacer.')) {
            return;
        }
        
        const password = prompt('Para confirmar, escriba "LIMPIAR BITACORA":');
        if (password !== 'LIMPIAR BITACORA') {
            alert('Operación cancelada');
            return;
        }
        
        const originalText = cleanBitacoraBtn.innerHTML;
        cleanBitacoraBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Limpiando...';
        cleanBitacoraBtn.disabled = true;
        
        try {
            const response = await fetch(`${APP_BASE}/config/clean_bitacora.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(`✅ ${result.message}\n\nRegistros eliminados: ${result.deleted}`);
                location.reload();
            } else {
                alert(`❌ Error: ${result.message}`);
            }
        } catch (error) {
            alert('Error de conexión: ' + error.message);
        } finally {
            cleanBitacoraBtn.innerHTML = originalText;
            cleanBitacoraBtn.disabled = false;
        }
    }
    
    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Eventos para backup
        if (createBackupBtn) {
            createBackupBtn.addEventListener('click', createBackup);
        }
        
        if (uploadBackupBtn) {
            uploadBackupBtn.addEventListener('click', openUploadModal);
        }
        
        if (cleanOldBtn) {
            cleanOldBtn.addEventListener('click', cleanOldBackups);
        }
        
        // Cerrar modal de backup al hacer clic fuera
        if (uploadModal) {
            uploadModal.addEventListener('click', function(e) {
                if (e.target === uploadModal) {
                    closeUploadModal();
                }
            });
        }
        
        // Eventos para bitácora
        if (loadMoreBitacoraBtn) {
            loadMoreBitacoraBtn.addEventListener('click', function() {
                loadBitacoraActivities(currentBitacoraPage + 1, true);
            });
        }
        
        if (cleanBitacoraBtn) {
            cleanBitacoraBtn.addEventListener('click', cleanBitacora);
        }
        
        // Cargar bitácora inicial
        loadBitacoraActivities();
        
        // Evento para el formulario de filtros
        const bitacoraForm = document.getElementById('bitacoraFilterForm');
        if (bitacoraForm) {
            bitacoraForm.addEventListener('submit', function(e) {
                e.preventDefault();
                loadBitacoraActivities(1, false);
            });
        }

        if (document.getElementById('paymentMethodsList')) {
            loadConfigPaymentMethods();
        }

        const pagosTabBtn = document.querySelector('.tab-btn[data-tab="pagos"]');
        if (pagosTabBtn) {
            pagosTabBtn.addEventListener('click', function() {
                loadConfigPaymentMethods();
            });
        }
    });

    // =================== FUNCIONES PARA REPORTE DE BITÁCORA ====================
    
    // Generar reporte de bitácora con filtros actuales
    async function generateBitacoraReport() {
        const btn = document.getElementById('generateBitacoraReportBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
        btn.disabled = true;
        
        try {
            // Obtener los valores actuales de los filtros
            const form = document.getElementById('bitacoraFilterForm');
            const formData = new FormData(form);
            const filters = {
                bitacora_q: formData.get('bitacora_q') || '',
                bitacora_usuario: formData.get('bitacora_usuario') || '',
                bitacora_fecha: formData.get('bitacora_fecha') || ''
            };
            
            const response = await fetch('/inversiones-rojas/api/generate_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    report_type: 'bitacora',
                    module: 'bitacora',
                    ...filters
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `reporte_bitacora_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            // Notificación de éxito
            showNotification('Reporte de bitácora generado exitosamente', 'success');
            
        } catch (error) {
            console.error('Error generando reporte de bitácora:', error);
            showNotification('Error al generar el reporte: ' + error.message, 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    // =================== INTEGRACIONES ===================

    async function guardarIntegraciones() {
        const btn    = document.getElementById('btnGuardarIntegraciones');
        const alerta = document.getElementById('intAlert');
        const form   = document.getElementById('integrationsForm');
        if (!form) return;

        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        alerta.style.display = 'none';

        // Recopilar datos del formulario
        const data = {};
        form.querySelectorAll('input[type=text], input[type=email]').forEach(el => {
            if (el.name) data[el.name] = el.value.trim();
        });
        form.querySelectorAll('input[type=checkbox]').forEach(el => {
            if (el.name) data[el.name] = el.checked ? '1' : '0';
        });

        try {
            const r = await fetch('<?php echo BASE_URL; ?>/api/update_integrations.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const j = await r.json();

            if (j.success) {
                alerta.style.cssText = 'display:block;padding:14px 18px;border-radius:8px;margin-bottom:20px;font-weight:500;background:#d4edda;color:#155724;border:1px solid #c3e6cb;';
                alerta.innerHTML = '<i class="fas fa-check-circle"></i> ✅ Configuración guardada correctamente. Los cambios se aplican de inmediato en el carrito.';
                // Recargar la página tras 1.5s para que los badges de estado se actualicen
                setTimeout(() => location.reload(), 1500);
            } else {
                alerta.style.cssText = 'display:block;padding:14px 18px;border-radius:8px;margin-bottom:20px;font-weight:500;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;';
                alerta.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error: ' + (j.message || 'No se pudo guardar');
            }
        } catch(e) {
            alerta.style.cssText = 'display:block;padding:14px 18px;border-radius:8px;margin-bottom:20px;font-weight:500;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;';
            alerta.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error de conexión: ' + e.message;
        }

        btn.disabled = false;
        btn.innerHTML = orig;
        alerta.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function testIntegrations() {
        const alerta = document.getElementById('intAlert');
        const form   = document.getElementById('integrationsForm');
        const wa     = form.querySelector('[name=whatsapp_number]')?.value?.trim();
        const em     = form.querySelector('[name=email_notifications]')?.value?.trim();
        const tg_tok = form.querySelector('[name=telegram_bot_token]')?.value?.trim();
        const tg_id  = form.querySelector('[name=telegram_chat_id]')?.value?.trim();

        let resultados = [];

        // WhatsApp — verificar que el número tenga formato correcto
        if (wa) {
            const onlyDigits = wa.replace(/\D/,'');
            if (onlyDigits.length >= 10 && onlyDigits.length <= 15) {
                resultados.push('<span style="color:#25D366;">✅ WhatsApp: número válido (' + wa + ')</span>');
            } else {
                resultados.push('<span style="color:#e74c3c;">❌ WhatsApp: número inválido (debe tener 10-15 dígitos)</span>');
            }
        } else {
            resultados.push('<span style="color:#aaa;">⚪ WhatsApp: sin número configurado</span>');
        }

        // Email — verificar formato
        if (em) {
            const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em);
            resultados.push(emailOk
                ? '<span style="color:#25D366;">✅ Email: dirección válida (' + em + ')</span>'
                : '<span style="color:#e74c3c;">❌ Email: formato inválido</span>');
        } else {
            resultados.push('<span style="color:#aaa;">⚪ Email: sin dirección configurada</span>');
        }

        // Telegram — verificar formato del token
        if (tg_tok && tg_id) {
            const tokenOk = /^\d+:[A-Za-z0-9_-]{30,}$/.test(tg_tok);
            resultados.push(tokenOk
                ? '<span style="color:#25D366;">✅ Telegram: token con formato válido</span>'
                : '<span style="color:#e74c3c;">❌ Telegram: token con formato inválido</span>');
        } else if (tg_tok || tg_id) {
            resultados.push('<span style="color:#f39c12;">⚠️ Telegram: faltan Token o Chat ID</span>');
        } else {
            resultados.push('<span style="color:#aaa;">⚪ Telegram: sin configurar</span>');
        }

        alerta.style.cssText = 'display:block;padding:14px 18px;border-radius:8px;margin-bottom:20px;background:#f0f9f4;border:1px solid #1F9166;';
        alerta.innerHTML = '<strong><i class="fas fa-flask"></i> Resultado de prueba:</strong><br><br>' + resultados.join('<br>') +
            '<br><br><small style="color:#888;">Nota: solo verifica el formato de los datos. Para probar el envío real, guarda primero y realiza un pedido de prueba.</small>';
        alerta.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function loadConfigPaymentMethods() {
        const list = document.getElementById('paymentMethodsList');
        const alertBox = document.getElementById('paymentMethodsAlert');
        if (!list) return;
        if (alertBox) {
            alertBox.style.display = 'none';
            alertBox.innerHTML = '';
        }

        list.innerHTML = '<div style="color:#666;font-size:14px;">Cargando métodos...</div>';

        try {
            const response = await fetch('<?php echo BASE_URL; ?>/api/get_metodos_pago_reservas.php');
            const data = await response.json();
            if (!data.ok || !Array.isArray(data.metodos)) {
                list.innerHTML = '<div style="color:#c62828;font-size:14px;">No se pudieron cargar los métodos de pago.</div>';
                return;
            }

            if (data.metodos.length === 0) {
                list.innerHTML = '<div style="color:#666;font-size:14px;">No hay métodos de pago registrados.</div>';
                return;
            }

            list.innerHTML = '';
            data.metodos.forEach(metodo => {
                const card = document.createElement('div');
                card.style.cssText = 'border:1px solid #ddd;border-radius:12px;padding:16px;background:#fff;display:flex;flex-direction:column;gap:8px;';
                let details = '';
                if (metodo.tipo === 'pago_movil') {
                    details = `Banco: ${metodo.banco} (${metodo.codigo_banco})<br>Cédula: ${metodo.cedula}<br>Teléfono: ${metodo.telefono}`;
                } else if (metodo.tipo === 'transferencia') {
                    details = `Banco: ${metodo.banco}<br>Cédula: ${metodo.cedula}<br>Cuenta: ${metodo.numero_cuenta}`;
                } else {
                    details = metodo.banco ? `Banco: ${metodo.banco}<br>Cédula: ${metodo.cedula}` : `Cédula: ${metodo.cedula}`;
                }
                card.innerHTML = `
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                        <strong style="font-size:15px;color:#1F9166;">${metodo.tipo.replace('_', ' ').toUpperCase()}</strong>
                        <span style="font-size:12px;color:#777;">Activo</span>
                    </div>
                    <div style="font-size:13px;color:#444;white-space:pre-wrap;line-height:1.5;">${details}</div>
                `;
                list.appendChild(card);
            });
        } catch (error) {
            list.innerHTML = '<div style="color:#c62828;font-size:14px;">Error cargando métodos de pago.</div>';
            console.error('Error cargando métodos de pago:', error);
        }
    }

    function updatePaymentForm() {
        const tipo = document.getElementById('paymentMethodType').value;
        const container = document.getElementById('paymentFormFields');
        
        let html = '';
        
        if (tipo === 'pago_movil') {
            html = `
                <div class="form-group">
                    <label for="banco">Banco *</label>
                    <input type="text" id="banco" class="form-control" placeholder="Ej: Mercantil, Banesco, Provincial" required>
                </div>
                <div class="form-group">
                    <label for="codigo_banco">Código del Banco *</label>
                    <input type="text" id="codigo_banco" class="form-control" placeholder="Ej: 0102" maxlength="4" required>
                </div>
                <div class="form-group">
                    <label for="cedula">Cédula *</label>
                    <input type="text" id="cedula" class="form-control" placeholder="Ej: V-12345678" required>
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono *</label>
                    <input type="text" id="telefono" class="form-control" placeholder="Ej: 0412-1234567" required>
                </div>
            `;
        } else if (tipo === 'transferencia') {
            html = `
                <div class="form-group">
                    <label for="banco">Banco *</label>
                    <input type="text" id="banco" class="form-control" placeholder="Ej: Mercantil, Banesco, Provincial" required>
                </div>
                <div class="form-group">
                    <label for="cedula">Cédula *</label>
                    <input type="text" id="cedula" class="form-control" placeholder="Ej: V-12345678" required>
                </div>
                <div class="form-group">
                    <label for="numero_cuenta">Número de Cuenta *</label>
                    <input type="text" id="numero_cuenta" class="form-control" placeholder="Ej: 0102-1234-56-7891234567" required>
                </div>
            `;
        } else if (tipo) {
            html = `
                <div class="form-group">
                    <label for="banco">Banco</label>
                    <input type="text" id="banco" class="form-control" placeholder="Ej: Mercantil, Banesco, Provincial">
                </div>
                <div class="form-group">
                    <label for="cedula">Cédula *</label>
                    <input type="text" id="cedula" class="form-control" placeholder="Ej: V-12345678" required>
                </div>
            `;
        }
        
        container.innerHTML = html;
    }

    async function saveConfigPaymentMethod() {
        const tipo = document.getElementById('paymentMethodType').value;
        const alertBox = document.getElementById('paymentMethodsAlert');
        
        if (!tipo) {
            if (alertBox) {
                alertBox.style.cssText = 'display:block;padding:14px 18px;border-radius:8px;margin-bottom:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;';
                alertBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> Debes seleccionar un tipo de método.';
            }
            document.getElementById('paymentMethodType').focus();
            return;
        }

        const formData = new FormData();
        formData.append('tipo', tipo);
        
        const banco = document.getElementById('banco')?.value.trim() || '';
        const codigo_banco = document.getElementById('codigo_banco')?.value.trim() || '';
        const cedula = document.getElementById('cedula')?.value.trim() || '';
        const telefono = document.getElementById('telefono')?.value.trim() || '';
        const numero_cuenta = document.getElementById('numero_cuenta')?.value.trim() || '';

        // Validaciones
        if (tipo === 'pago_movil') {
            if (!banco || !codigo_banco || !cedula || !telefono) {
                if (alertBox) {
                    alertBox.style.cssText = 'display:block;padding:14px 18px;border-radius:8px;margin-bottom:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;';
                    alertBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> Todos los campos son obligatorios para Pago Móvil.';
                }
                return;
            }
        } else if (tipo === 'transferencia') {
            if (!banco || !cedula || !numero_cuenta) {
                if (alertBox) {
                    alertBox.style.cssText = 'display:block;padding:14px 18px;border-radius:8px;margin-bottom:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;';
                    alertBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> Todos los campos son obligatorios para Transferencia.';
                }
                return;
            }
        } else {
            if (!cedula) {
                if (alertBox) {
                    alertBox.style.cssText = 'display:block;padding:14px 18px;border-radius:8px;margin-bottom:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;';
                    alertBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> La cédula es obligatoria.';
                }
                return;
            }
        }

        formData.append('banco', banco);
        formData.append('codigo_banco', codigo_banco);
        formData.append('cedula', cedula);
        formData.append('telefono', telefono);
        formData.append('numero_cuenta', numero_cuenta);

        try {
            const response = await fetch('<?php echo BASE_URL; ?>/api/add_metodo_pago_reserva.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.ok) {
                if (alertBox) {
                    alertBox.style.cssText = 'display:block;padding:14px 18px;border-radius:8px;margin-bottom:20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;';
                    alertBox.innerHTML = '<i class="fas fa-check-circle"></i> Método de pago guardado correctamente.';
                }
                document.getElementById('paymentMethodType').value = '';
                updatePaymentForm();
                loadConfigPaymentMethods();
            } else {
                if (alertBox) {
                    alertBox.style.cssText = 'display:block;padding:14px 18px;border-radius:8px;margin-bottom:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;';
                    alertBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.error || 'No se pudo guardar el método de pago.');
                }
            }
        } catch (error) {
            if (alertBox) {
                alertBox.style.cssText = 'display:block;padding:14px 18px;border-radius:8px;margin-bottom:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;';
                alertBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error de conexión: ' + error.message;
            }
            console.error('Error guardando método de pago:', error);
        }
    }

    // =================== FIN INTEGRACIONES ===================

    // Event listener para el botón de reporte de bitácora
    document.addEventListener('DOMContentLoaded', function() {
        const generateBitacoraBtn = document.getElementById('generateBitacoraReportBtn');
        if (generateBitacoraBtn) {
            generateBitacoraBtn.addEventListener('click', generateBitacoraReport);
        }
    });

    // =================== FUNCIONALIDAD DE DESCARGA DE MANUALES ===================

    // Función para descargar manuales
    async function downloadManual(btn) {
        if (!btn) {
            console.error('Botón de descarga no encontrado');
            return;
        }

        const fileName = btn.getAttribute('data-file');
        const title = btn.getAttribute('data-title');

        if (!fileName || !title) {
            console.error('Archivo o título faltante para descarga de manual');
            return;
        }

        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Descargando...';
        btn.disabled = true;

        try {
            // Construir la URL del manual
            const manualUrl = `${APP_BASE}/docs/manuales/${fileName}`;

            // Intentar descargar el archivo
            const response = await fetch(manualUrl);

            if (!response.ok) {
                throw new Error(`Archivo no encontrado (HTTP ${response.status})`);
            }

            // Convertir la respuesta a blob
            const blob = await response.blob();

            // Crear URL del blob para descarga
            const url = window.URL.createObjectURL(blob);

            // Crear elemento <a> para la descarga
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = fileName;

            // Agregar al DOM, hacer clic y remover
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            // Mostrar mensaje de éxito
            showNotification(`✅ ${title} descargado exitosamente`, 'success');

        } catch (error) {
            console.error('Error descargando manual:', error);

            // Mostrar mensaje de error
            showNotification(`❌ Error al descargar ${title}: ${error.message}`, 'error');

            // Si el archivo no existe, mostrar mensaje alternativo
            if (error.message.includes('404') || error.message.includes('no encontrado')) {
                setTimeout(() => {
                    alert(`El manual "${title}" aún no está disponible.\n\nContacta al administrador del sistema para obtener el archivo.`);
                }, 1000);
            }
        } finally {
            // Restaurar el botón
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    }

    // Función para mostrar notificaciones
    function showNotification(message, type = 'info') {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `alert-box alert-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 500;
            max-width: 400px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            animation: slideInRight 0.3s ease-out;
        `;

        // Estilos según tipo
        if (type === 'success') {
            notification.style.background = '#d4edda';
            notification.style.color = '#155724';
            notification.style.border = '1px solid #c3e6cb';
        } else if (type === 'error') {
            notification.style.background = '#f8d7da';
            notification.style.color = '#721c24';
            notification.style.border = '1px solid #f5c6cb';
        } else {
            notification.style.background = '#d1ecf1';
            notification.style.color = '#0c5460';
            notification.style.border = '1px solid #bee5eb';
        }

        notification.innerHTML = message;

        // Agregar animación CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);

        // Agregar al DOM
        document.body.appendChild(notification);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);

        // Agregar animación de salida
        setTimeout(() => {
            const styleOut = document.createElement('style');
            styleOut.textContent = `
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(styleOut);
        }, 4500);
    }

    // Event listeners para botones de descarga de manuales
    document.addEventListener('DOMContentLoaded', function() {
        // Agregar event listeners a todos los botones de descarga
        document.querySelectorAll('.download-manual').forEach(btn => {
            btn.addEventListener('click', function() {
                downloadManual(this);
            });
        });
        
        // Cargar tasa de cambio actual
        cargarTasaCambio();
    });
    
    async function cargarTasaCambio() {
        try {
            const tasaInput = document.getElementById('tasaCambio');
            if (tasaInput) {
                tasaInput.value = '<?php echo TASA_CAMBIO; ?>';
            }
        } catch (e) {
            console.error('Error al cargar tasa:', e);
        }
    }
    
    async function guardarTasaCambio() {
        const tasaInput = document.getElementById('tasaCambio');
        const mensajeDiv = document.getElementById('tasaMensaje');
        const tasa = tasaInput.value.trim();
        
        if (!tasa || isNaN(tasa) || parseFloat(tasa) <= 0) {
            mensajeDiv.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i> Ingrese una tasa válida</span>';
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('tasa', tasa);
            
            const response = await fetch(APP_BASE + '/api/update_tasa_cambio.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                mensajeDiv.innerHTML = '<span style="color: #28a745;"><i class="fas fa-check-circle"></i> Tasa actualizada: Bs ' + parseFloat(tasa).toFixed(2) + ' por USD</span>';
                // Mostrar notificación global
                showNotification('Tasa de cambio actualizada correctamente', 'success');
            } else {
                mensajeDiv.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i> Error: ' + data.message + '</span>';
            }
        } catch (e) {
            mensajeDiv.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i> Error de conexión</span>';
        }
    }
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        if (type === 'success') {
            notification.style.background = '#d4edda';
            notification.style.color = '#155724';
            notification.style.border = '1px solid #c3e6cb';
        } else if (type === 'error') {
            notification.style.background = '#f8d7da';
            notification.style.color = '#721c24';
            notification.style.border = '1px solid #f5c6cb';
        } else {
            notification.style.background = '#d1ecf1';
            notification.style.color = '#0c5460';
            notification.style.border = '1px solid #bee5eb';
        }
        
        notification.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle') + '"></i> ' + message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // ==================== VALIDACIONES DEL MODAL DE NUEVO USUARIO ====================
    const newUserBtn = document.getElementById('newUserBtn');
    const newUserModal = document.getElementById('newUserModal');
    const saveNewUserBtn = document.getElementById('saveNewUser');
    const cancelNewUserBtn = document.getElementById('cancelNewUser');

    if (newUserBtn) {
        newUserBtn.addEventListener('click', function() {
            newUserModal.classList.add('active');
            document.getElementById('newUserForm').reset();
            document.getElementById('user_id').value = '';
        });
    }

    if (cancelNewUserBtn) {
        cancelNewUserBtn.addEventListener('click', function() {
            newUserModal.classList.remove('active');
        });
    }

    if (newUserModal) {
        newUserModal.addEventListener('click', function(e) {
            if (e.target === newUserModal) {
                newUserModal.classList.remove('active');
            }
        });
    }

    if (saveNewUserBtn) {
        saveNewUserBtn.addEventListener('click', async function() {
            const usernameInput = document.getElementById('userUsername');
            const emailInput = document.getElementById('userEmail');
            const fullnameInput = document.getElementById('userFullname');
            const cedulaInput = document.getElementById('userCedula');
            const phoneInput = document.getElementById('userPhone');
            const roleInput = document.getElementById('userRole');
            const passwordInput = document.getElementById('userPassword');
            const confirmPasswordInput = document.getElementById('userConfirmPassword');

            // 1. Validar nombre de usuario obligatorio
            if (!InvValidate.required(usernameInput, 'El nombre de usuario')) {
                usernameInput.focus();
                return;
            }

            // 2. Validar longitud mínima de usuario
            if (!InvValidate.minLength(usernameInput, 3, 'El usuario')) {
                usernameInput.focus();
                return;
            }

            // 3. Validar longitud máxima de usuario
            if (!InvValidate.maxLength(usernameInput, 20, 'El usuario')) {
                usernameInput.focus();
                return;
            }

            // 4. Validar email obligatorio
            if (!InvValidate.required(emailInput, 'El correo electrónico')) {
                emailInput.focus();
                return;
            }

            // 5. Validar formato email
            if (!InvValidate.email(emailInput, true)) {
                emailInput.focus();
                return;
            }

            // 6. Validar nombre completo obligatorio
            if (!InvValidate.required(fullnameInput, 'El nombre completo')) {
                fullnameInput.focus();
                return;
            }

            // 7. Validar cédula/RIF obligatorio
            if (!InvValidate.required(cedulaInput, 'La cédula o RIF')) {
                cedulaInput.focus();
                return;
            }

            // 8. Validar formato RIF
            if (!InvValidate.rif(cedulaInput, true)) {
                cedulaInput.focus();
                return;
            }

            // 9. Validar teléfono (opcional)
            if (phoneInput.value.trim() && !InvValidate.telefono(phoneInput, false)) {
                phoneInput.focus();
                return;
            }

            // 10. Validar rol obligatorio
            if (!InvValidate.required(roleInput, 'El rol')) {
                roleInput.focus();
                return;
            }

            // 11. Validar contraseña obligatoria
            if (!InvValidate.required(passwordInput, 'La contraseña')) {
                passwordInput.focus();
                return;
            }

            // 12. Validar longitud mínima de contraseña
            if (!InvValidate.minLength(passwordInput, 8, 'La contraseña')) {
                passwordInput.focus();
                return;
            }

            // 13. Validar que contraseñas coincidan
            if (passwordInput.value !== confirmPasswordInput.value) {
                InvValidate.setError(confirmPasswordInput, 'Las contraseñas no coinciden');
                confirmPasswordInput.focus();
                return;
            }

            // 14. Validar nombre de usuario único
            try {
                const checkUrl = (window.APP_BASE || '') + '/api/check_username.php?username=' + encodeURIComponent(usernameInput.value.trim());
                const checkResp = await fetch(checkUrl);
                const checkData = await checkResp.json();
                
                if (!checkData.available) {
                    InvValidate.setError(usernameInput, 'El nombre de usuario ya está en uso');
                    usernameInput.focus();
                    return;
                }
            } catch (error) {
                console.error('Error al verificar usuario:', error);
            }

            // 15. Validar email único
            try {
                const checkEmailUrl = (window.APP_BASE || '') + '/api/check_email.php?email=' + encodeURIComponent(emailInput.value.trim());
                const checkEmailResp = await fetch(checkEmailUrl);
                const checkEmailData = await checkEmailResp.json();
                
                if (!checkEmailData.available) {
                    InvValidate.setError(emailInput, 'El correo electrónico ya está en uso');
                    emailInput.focus();
                    return;
                }
            } catch (error) {
                console.error('Error al verificar email:', error);
            }

            // Mostrar indicador de carga
            const originalText = saveNewUserBtn.innerHTML;
            saveNewUserBtn.disabled = true;
            saveNewUserBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            try {
                const formData = new FormData();
                formData.append('username', usernameInput.value.trim());
                formData.append('email', emailInput.value.trim());
                formData.append('nombre_completo', fullnameInput.value.trim());
                formData.append('cedula_rif', cedulaInput.value.trim());
                formData.append('telefono_principal', phoneInput.value.trim());
                formData.append('rol_id', roleInput.value);
                formData.append('password', passwordInput.value);

                const response = await fetch((window.APP_BASE || '') + '/api/add_usuario.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Usuario creado exitosamente', 'success');
                    newUserModal.classList.remove('active');
                    document.getElementById('newUserForm').reset();
                    // Recargar página o tabla de usuarios
                    location.reload();
                } else {
                    showNotification(data.message || 'Error al crear usuario', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error de conexión', 'error');
            } finally {
                saveNewUserBtn.disabled = false;
                saveNewUserBtn.innerHTML = originalText;
            }
        });
    }
</script>

<script src="<?php echo BASE_URL; ?>/public/js/layouts/configuracion.js"></script>

</body>
</html>