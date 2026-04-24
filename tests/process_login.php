<?php
// Configurar rutas absolutas
$root_path = dirname(__DIR__); // Sube un nivel desde tests/
require_once $root_path . '/app/models/database.php';
require_once $root_path . '/app/models/Usuario.php'; // AGREGADO: Modelo Usuario
// Cargar configuración global (define BASE_URL entre otras)
if (file_exists($root_path . '/config/config.php')) {
    require_once $root_path . '/config/config.php';
}
// Valor seguro para la clave secreta de reCAPTCHA (puede no estar definida)
$__RECAPTCHA_SECRET_KEY = defined('RECAPTCHA_SECRET_KEY') ? constant('RECAPTCHA_SECRET_KEY') : '';
// Helpers (permisos y utilidades)
if (file_exists($root_path . '/app/helpers/permissions.php')) {
    require_once $root_path . '/app/helpers/permissions.php';
}

session_start();
header('Content-Type: application/json');

// Validar token CSRF
$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($submittedToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $submittedToken)) {
    echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido. Por favor recarga la página.']);
    exit;
}

// Solo ejecutar si es una petición POST real
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        $debugInfo = [];
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $debugInfo['db_error'] = method_exists($database, 'getLastError') ? $database->getLastError() : 'no_error_info';
        }
        echo json_encode(array_merge(['success' => false, 'message' => 'Error de conexión a la base de datos'], $debugInfo));
        exit;
    }
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    // Anti-bot server-side checks
    $honeypot = $_POST['website'] ?? '';
    $notRobot = $_POST['not_robot'] ?? '';
    if (!empty($honeypot)) {
        echo json_encode(['success' => false, 'message' => 'Envío detectado como spam']);
        exit;
    }
    // Si hay una clave secreta de reCAPTCHA configurada, usaremos reCAPTCHA
    // y no requerimos la casilla manual `not_robot`. Si no hay clave, exigirla.
    if (empty($__RECAPTCHA_SECRET_KEY)) {
        if ($notRobot !== '1') {
            echo json_encode(['success' => false, 'message' => 'Por favor confirma que no eres un robot']);
            exit;
        }
    }

    // En process_login.php, reemplaza la sección de verificación de reCAPTCHA:

// Si se ha configurado RECAPTCHA_SECRET_KEY en config, verificar el token enviado
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

if ($__RECAPTCHA_SECRET_KEY) {
    // Verificar que se envió el token del checkbox
    if (empty($recaptchaResponse)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Por favor marca la casilla "No soy un robot"'
        ]);
        exit;
    }

    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $postData = http_build_query([
        'secret' => $__RECAPTCHA_SECRET_KEY,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    // Preferir cURL si está disponible
    $verifyResponse = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $verifyResponse = curl_exec($ch);
        curl_close($ch);
    } else {
        // Fallback a file_get_contents
        $opts = ['http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => $postData,
            'timeout' => 5
        ]];
        $context = stream_context_create($opts);
        $verifyResponse = @file_get_contents($verifyUrl, false, $context);
    }

    if (!$verifyResponse) {
        error_log('reCAPTCHA verification call failed');
        echo json_encode(['success' => false, 'message' => 'No se pudo verificar reCAPTCHA. Intenta de nuevo.']);
        exit;
    }

    $decoded = json_decode($verifyResponse, true);
    
    // Verificación para reCAPTCHA v2 (más simple)
    if (empty($decoded['success'])) {
        error_log('reCAPTCHA verification failed: ' . json_encode($decoded));
        $errorMessage = 'Verificación de seguridad fallida. ';
        
        // Mensajes más específicos según el error
        if (isset($decoded['error-codes'])) {
            if (in_array('timeout-or-duplicate', $decoded['error-codes'])) {
                $errorMessage .= 'El tiempo de verificación expiró.';
            } elseif (in_array('missing-input-response', $decoded['error-codes'])) {
                $errorMessage .= 'No se marcó la casilla.';
            } else {
                $errorMessage .= 'Por favor inténtalo de nuevo.';
            }
        }
        
        echo json_encode(['success' => false, 'message' => $errorMessage]);
        exit;
    }
    
}
    // Validaciones básicas
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email y contraseña son requeridos']);
        exit;
    }
    
    try {
        // Buscar usuario usando el modelo Usuario
        $usuarioModel = new Usuario($db);
        $user = $usuarioModel->obtenerPorEmail($email);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o cuenta desactivada']);
            exit;
        }

        // Obtener información del rol si no está incluida
        if (!isset($user['rol_nombre'])) {
            $query = "SELECT r.nombre as rol_nombre FROM roles r WHERE r.id = :rol_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':rol_id', $user['rol_id']);
            $stmt->execute();
            $rol = $stmt->fetch(PDO::FETCH_ASSOC);
            $user['rol_nombre'] = $rol['rol_nombre'] ?? $user['rol_id'];
        }

        // VERIFICAR BLOQUEO POR INTENTOS FALLIDOS
        if ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
            $tiempoRestante = strtotime($user['bloqueado_hasta']) - time();
            $minutos = ceil($tiempoRestante / 60);
            echo json_encode(['success' => false, 'message' => "Cuenta bloqueada. Intente nuevamente en $minutos minutos."]);
            exit;
        }
        
        // Verificar contraseña
        if (password_verify($password, $user['password_hash'])) {
            // Login exitoso - resetear intentos fallidos
            $usuarioModel->resetearIntentosFallidos($user['id']);
            $usuarioModel->actualizarUltimoAcceso($user['id']);
            
            // Iniciar sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre_completo'];
            $_SESSION['user_email'] = $user['email'];
            
            // Regenerar token CSRF después de login exitoso
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Normalizar el rol antes de guardarlo en sesión si el helper existe
            $rawRole = $user['rol_nombre'] ?? $user['rol_id'] ?? null;
            $canonicalRole = null;
            if (function_exists('canonical_role') && is_string($rawRole)) {
                $canonicalRole = canonical_role($rawRole);
            }
            $_SESSION['user_rol'] = $canonicalRole ?? $rawRole;
            $_SESSION['loggedin'] = true;

            // Determinar redirección según el rol usando el helper si existe
            $roleName = $_SESSION['user_rol'];
            if (function_exists('get_role_home')) {
                $redirect = get_role_home($roleName);
            } else {
                // Fallback al landing por rol unificado
                $redirect = '/inversiones-rojas/app/views/layouts/inicio_role.php';
            }

            // Normalizar redirect: si BASE_URL está definida, asegurarnos de que la ruta la incluya
            if (defined('BASE_URL') && !empty(BASE_URL)) {
                $base = rtrim(BASE_URL, '/');
                if (strpos($redirect, $base) !== 0) {
                    // Añadir base si falta
                    $redirect = $base . '/' . ltrim($redirect, '/');
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Login exitoso! Bienvenido ' . $user['nombre_completo'],
                'redirect' => $redirect
            ]);
            
        } else {
            // Contraseña incorrecta - incrementar intentos fallidos
            $nuevosIntentos = $user['intentos_fallidos'] + 1;
            
            // Bloquear usuario después de 3 intentos fallidos
            if ($nuevosIntentos >= 3) {
                $bloqueadoHasta = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $usuarioModel->bloquearUsuario($user['id'], $bloqueadoHasta);
                $usuarioModel->actualizarIntentosFallidos($user['id'], $nuevosIntentos);
                
                echo json_encode(['success' => false, 'message' => 'Demasiados intentos fallidos. Cuenta bloqueada por 15 minutos.']);
            } else {
                // Actualizar contador de intentos
                $usuarioModel->actualizarIntentosFallidos($user['id'], $nuevosIntentos);
                
                $intentosRestantes = 3 - $nuevosIntentos;
                echo json_encode(['success' => false, 'message' => "Contraseña incorrecta. Le quedan $intentosRestantes intentos."]);
            }
        }
        
    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error en el sistema: ' . $e->getMessage()]);
    }
} else {
    // Si se ejecuta desde consola, mostrar mensaje útil
    if (php_sapi_name() === 'cli') {
        echo "❌ Este archivo debe ejecutarse a través del navegador, no desde línea de comandos.\n";
        echo "📱 Accede a: http://localhost/inversiones-rojas/app/views/auth/Login.php\n";
    } else {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
}
?>