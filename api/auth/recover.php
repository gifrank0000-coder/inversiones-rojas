<?php
// api/auth/recover.php - VERSIÓN CORREGIDA
session_start();

// ============================================
// 1. CONFIGURACIÓN DE ERRORES
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// ============================================
// 2. HEADERS (PRIMERO, NADA ANTES)
// ============================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// 3. CARGAR PHPMailer MANUALMENTE
// ============================================
$phpmailerPath = dirname(__DIR__, 2) . '/PHPMailer/';

// Verificar que los archivos existan
if (!file_exists($phpmailerPath . 'PHPMailer.php')) {
    $response = [
        'success' => false,
        'message' => 'PHPMailer no encontrado. Verifica la instalación.',
        'debug_path' => $phpmailerPath
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Cargar PHPMailer
require_once $phpmailerPath . 'Exception.php';
require_once $phpmailerPath . 'PHPMailer.php';
require_once $phpmailerPath . 'SMTP.php';

// Usar namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$configPath = dirname(__DIR__, 2) . '/config/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    // Configuración por defecto
    define('APP_DEBUG', true);
    define('SITE_NAME', 'Inversiones Rojas');
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_USER', 'gifrank0000@gmail.com');
    define('SMTP_PASS', 'hkac hswn ijbm gbrv');
    define('SMTP_PORT', 587);
    define('SMTP_SECURE', 'tls');
    define('SMTP_FROM_NAME', SITE_NAME);
    define('SMTP_FROM', 'gifrank0000@gmail.com');
}




$response = [
    'success' => false,
    'message' => '',
    'debug' => []
];

try {



    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Use POST', 405);
    }


    $input = file_get_contents('php://input');
    $postData = [];
    
    if (!empty($_POST)) {
        $postData = $_POST;
    } elseif (!empty($input)) {
        $jsonData = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $postData = $jsonData;
        } else {
            parse_str($input, $postData);
        }
    }

   
   
    $action = $postData['action'] ?? '';
    $email = trim($postData['email'] ?? '');

    if (empty($action)) {
        throw new Exception('No se especificó ninguna acción', 400);
    }

    
 
    switch ($action) {
        case 'send_code':
            // Validar email
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Por favor ingresa un email válido', 400);
            }

            // Generar código
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $token = 'rec_' . time() . '_' . bin2hex(random_bytes(8));
            
            // Guardar en sesión
            $_SESSION['recovery'] = [
                'email' => $email,
                'code' => $code,
                'token' => $token,
                'expires' => time() + 1800
            ];
            
        
            $mailResult = sendEmailWithPHPMailer($email, $code);
            
            if ($mailResult['success']) {
                $response['success'] = true;
                $response['message'] = 'Código enviado a tu correo electrónico';
                $response['token'] = $token;
                
                if (APP_DEBUG) {
                    $response['debug']['code'] = $code;
                    $response['debug']['mail_info'] = $mailResult['message'];
                }
                
                error_log(" Correo enviado a: {$email}, Código: {$code}");
            } else {
                
                if (APP_DEBUG) {
                    $response['success'] = true;
                    $response['message'] = 'Código generado (modo desarrollo)';
                    $response['token'] = $token;
                    $response['debug']['code'] = $code;
                    $response['debug']['mail_error'] = $mailResult['message'];
                    
                    error_log("Correo falló, pero continuando en modo desarrollo. Código: {$code}");
                } else {
                    throw new Exception('Error enviando correo: ' . $mailResult['message'], 500);
                }
            }
            break;

        case 'verify_code':
            $code = $postData['code'] ?? '';
            $token = $postData['token'] ?? '';
            
            if (empty($code) || strlen($code) !== 6) {
                throw new Exception('El código debe tener 6 dígitos', 400);
            }
            
            if (empty($token)) {
                throw new Exception('Token no proporcionado', 400);
            }
            
            // Verificar que exista la sesión
            if (!isset($_SESSION['recovery'])) {
                throw new Exception('No hay una solicitud de recuperación activa', 401);
            }
            
            $recoveryData = $_SESSION['recovery'];
            
            // Verificar token
            if ($recoveryData['token'] !== $token) {
                throw new Exception('Token inválido o expirado', 401);
            }
            
            // Verificar expiración
            if (time() > $recoveryData['expires']) {
                unset($_SESSION['recovery']);
                throw new Exception('El código ha expirado. Solicita uno nuevo', 401);
            }
            
            if ((string)$recoveryData['code'] !== (string)$code) {
                error_log("Código incorrecto. Esperado: {$recoveryData['code']}, Recibido: {$code}");
                throw new Exception('Código incorrecto. Intenta nuevamente', 400);
            }
            
            $response['success'] = true;
            $response['message'] = 'Código verificado correctamente';
            $response['data'] = [
                'email' => $recoveryData['email'],
                'verified' => true,
                'next_step' => 'update_password'
            ];
            
            error_log("Código verificado para: {$recoveryData['email']}");
            break;

        case 'update_password':
            $token = $postData['token'] ?? '';
            $newPassword = $postData['new_password'] ?? '';
            $confirmPassword = $postData['confirm_password'] ?? '';
            
            if (empty($token)) {
                throw new Exception('Token requerido', 400);
            }
            
            if (empty($newPassword) || strlen($newPassword) < 8) {
                throw new Exception('La contraseña debe tener al menos 8 caracteres', 400);
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('Las contraseñas no coinciden', 400);
            }
            
            // Verificar sesión
            if (!isset($_SESSION['recovery']) || $_SESSION['recovery']['token'] !== $token) {
                throw new Exception('Sesión inválida o expirada', 401);
            }
            
            $email = $_SESSION['recovery']['email'];

            $dbPath = dirname(__DIR__, 2) . '/app/models/database.php';
            if (!file_exists($dbPath)) {
                throw new Exception('No se encontró el archivo de conexión a la base de datos');
            }
            require_once $dbPath;
            $database = new Database();
            $pdo = $database->getConnection();
            if (!$pdo) {
                throw new Exception('Error de conexión a la base de datos');
            }

            $password_hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE usuarios SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP WHERE email = :email');
            $ok = $stmt->execute(['password_hash' => $password_hash, 'email' => $email]);
            if (!$ok) {
                throw new Exception('No se pudo actualizar la contraseña en la base de datos');
            }

            // Limpiar sesión
            unset($_SESSION['recovery']);

            $response['success'] = true;
            $response['message'] = 'Contraseña actualizada exitosamente';
            $response['data'] = [
                'email' => $email,
                'redirect' => defined('BASE_URL') ? BASE_URL . '/Login.php' : '/Login.php'
            ];
            break;

        default:
            throw new Exception('Acción no válida. Usa: send_code, verify_code, update_password', 400);
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['error_code'] = $e->getCode();
    
    if (APP_DEBUG) {
        $response['debug']['trace'] = $e->getTraceAsString();
    }
    
    error_log("Error en recover.php: " . $e->getMessage());
}


while (ob_get_level()) {
    ob_end_clean();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;


function sendEmailWithPHPMailer($to, $code) {
    $result = ['success' => false, 'message' => ''];
    
    try {
  
        $mail = new PHPMailer(true);
        
    
        $mail->isSMTP();
        
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->Port = SMTP_PORT;
        $mail->SMTPSecure = SMTP_SECURE;
        
     
        if (APP_DEBUG) {

        $mail->SMTPDebug = SMTP::DEBUG_CLIENT; 
            $mail->Debugoutput = function($str, $level) {

            error_log("[PHPMailer Nivel {$level}] {$str}");
            };
            
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        } else {
            $mail->SMTPDebug = 0; // Sin debug en producción
        }
        
        // Timeout más largo
        $mail->Timeout = 30;
        
        // ============================================
        // D. CONFIGURAR REMITENTE Y DESTINATARIO
        // ============================================
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);
        
        // ============================================
        // E. CONFIGURAR CONTENIDO DEL CORREO
        // ============================================
        $mail->isHTML(true);
        $mail->Subject = '🔐 Código de Recuperación - ' . SITE_NAME;
        $mail->CharSet = 'UTF-8';
        
        // Plantilla simple pero efectiva
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
                .container { max-width: 600px; background: white; border-radius: 10px; padding: 30px; margin: 0 auto; }
                .header { background: #1F9166; color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
                .code { font-size: 36px; font-weight: bold; color: #1F9166; text-align: center; padding: 20px; margin: 20px 0; background: #f8f9fa; border-radius: 5px; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . SITE_NAME . '</h1>
                    <p>Recuperación de Contraseña</p>
                </div>
                
                <h2>¡Hola!</h2>
                <p>Has solicitado recuperar tu contraseña.</p>
                <p>Usa el siguiente código de verificación:</p>
                
                <div class="code">' . $code . '</div>
                
                <p><strong> Este código expira en 30 minutos</strong></p>
                <p>Si no solicitaste este código, ignora este correo.</p>
                
                <p>Saludos,<br>El equipo de ' . SITE_NAME . '</p>
                
                <div class="footer">
                    <p>Este es un mensaje automático, no respondas.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->Body = $html;
        $mail->AltBody = "Tu código de recuperación es: {$code}\n\nUsa este código en " . SITE_NAME . "\n\nVálido por 30 minutos.";
        
        // ============================================
        // F. ENVIAR CORREO
        // ============================================
        $mail->send();
        
        $result['success'] = true;
        $result['message'] = 'Correo enviado exitosamente';
        
        error_log(" CORREO REAL ENVIADO A: {$to}");
        
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        $result['message'] = "Error PHPMailer: {$errorMsg}";
        
        error_log(" ERROR PHPMailer: {$errorMsg}");
        
        // Información adicional del error
        if (isset($mail->ErrorInfo) && !empty($mail->ErrorInfo)) {
            error_log("ErrorInfo: " . $mail->ErrorInfo);
            $result['message'] .= " | Info: " . $mail->ErrorInfo;
        }
    }
    
    return $result;
}
?>