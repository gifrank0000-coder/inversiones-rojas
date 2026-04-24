<?php

// Definir constantes de sesión primero
define('SESSION_LIFETIME', 28800); // 8 horas

// Configuración del entorno (necesaria para APP_DEBUG)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('APP_DEBUG', $host === 'localhost' || $host === '127.0.0.1');
define('ENVIRONMENT', APP_DEBUG ? 'development' : 'production');

// Configurar sesiones ANTES de cualquier session_start()
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.cookie_secure', !APP_DEBUG); // Solo HTTPS en producción
    ini_set('session.cookie_httponly', true); // Prevenir acceso via JS
    ini_set('session.use_only_cookies', true);
}

// =============================================
// CONFIGURACIÓN DE BASE_URL (DINÁMICA)
// =============================================
// URL manual - establece esta variable si usas túneles (tunnelmole, ngrok, etc.)
// Ejemplo: $force_base_url = 'https://1a2b3c4d.tunnelmole.net/inversiones-rojas';
$force_base_url = '';

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Detectar si es un túnel (tunnelmole, ngrok, etc.) - estos usan HTTPS
$is_tunnel = false;
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
    $is_tunnel = true;
}
if (isset($_SERVER['HTTP_X_REAL_HOST'])) {
    $host = $_SERVER['HTTP_X_REAL_HOST'];
    $is_tunnel = true;
}
// Detectar si el host contiene dominios de túneles conocidos
if (preg_match('/(tunnelmole|ngrok|localhost\.run)/i', $host)) {
    $is_tunnel = true;
}

// Forzar HTTPS para túneles y conexiones seguras
if ($is_tunnel || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    $protocol = 'https';
} else {
    $protocol = 'http';
}

// Usar URL forzada si está definida (para túneles)
if (!empty($force_base_url)) {
    define('BASE_URL', $force_base_url);
} else {
    define('BASE_URL', $protocol . "://" . $host . "/inversiones-rojas");
}

define('SITE_NAME', 'INVERSIONES ROJAS 2016. C.A.');
define('ADMIN_EMAIL', '2016rojasinversiones@gmail.com');

// Información de la empresa
define('COMPANY_NAME', 'INVERSIONES ROJAS 2016. C.A.');
define('COMPANY_RIF', 'J-40888806-8');
define('COMPANY_ADDRESS', 'AV ARAGUA LOCAL NRO 286 SECTOR ANDRES ELOY BLANCO, MARACAY ARAGUA ZONA POSTAL 2102');
define('COMPANY_PHONE', '0243-2343044');
define('COMPANY_EMAIL', '2016rojasinversiones@gmail.com');

// Configuración de errores
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// =============================================
// CONFIGURACIÓN SMTP PARA GMAIL (DATOS REALES)
// =============================================
define('SMTP_ENABLED', true); // ¡ACTIVAR PARA ENVIAR CORREOS REALES!

// Tus datos REALES de Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'gifrank0000@gmail.com'); // Tu correo Gmail
define('SMTP_PASS', 'hkac hswn ijbm gbrv'); // La contraseña de aplicación de 16 caracteres
define('SMTP_PORT', 587); // Puerto para TLS
define('SMTP_SECURE', 'tls'); // tls o ssl
define('SMTP_FROM_NAME', SITE_NAME);
define('SMTP_FROM', 'gifrank0000@gmail.com'); // Mismo que SMTP_USER
define('SMTP_REPLY_TO', 'no-reply@inversionesrojas.com');

// Configuración adicional
define('SMTP_TIMEOUT', 30);
define('SMTP_AUTH', true);

// reCAPTCHA v2 - TUS NUEVAS CLAVES
define('RECAPTCHA_SITE_KEY', '6Lc4AVYsAAAAAMJ2i0BnL1nAALufQnvfx9TRgnaa'); // Clave de sitio
define('RECAPTCHA_SECRET_KEY', '6Lc4AVYsAAAAAOtpCN_1Lc-wf2ujsFjOpL4nAV8c'); // Clave secreta

// Configuración de la base de datos - CORREGIDO
define('DB_HOST', 'localhost');
define('DB_NAME', 'InversionesRojas'); // Nombre exacto de la base de datos
define('DB_USER', 'postgres');
define('DB_PASS', '1234');
define('DB_PORT', '5432');

// Configuración de PostgreSQL - NUEVO
// Define las rutas de los ejecutables de PostgreSQL
// Para Linux:
define('PG_DUMP_PATH', '/usr/bin/pg_dump');
define('PSQL_PATH', '/usr/bin/psql');
// Para Windows, descomenta y ajusta:
// define('PG_DUMP_PATH', 'C:\\Program Files\\PostgreSQL\\15\\bin\\pg_dump.exe');
// define('PSQL_PATH', 'C:\\Program Files\\PostgreSQL\\15\\bin\\psql.exe');

// Seguridad
define('RECOVERY_CODE_EXPIRY', 1800);

// =============================================
// CONFIGURACIÓN DE INTEGRACIONES
// =============================================
// Valores por defecto (se sobrescriben con DB si existen)
$default_integrations = [
    'INTEGRATION_WHATSAPP_NUMBER' => '584122343044',
    'INTEGRATION_WHATSAPP_ENABLED' => true,
    'INTEGRATION_EMAIL_NOTIFICATIONS' => '2016rojasinversiones@gmail.com',
    'INTEGRATION_EMAIL_ENABLED' => true,
    'INTEGRATION_TELEGRAM_BOT_TOKEN' => '',
    'INTEGRATION_TELEGRAM_CHAT_ID' => '',
    'INTEGRATION_TELEGRAM_ENABLED' => false,
    'INTEGRATION_INTERNAL_NOTIFICATIONS_ENABLED' => true,
    'INTEGRATION_AUTO_ASSIGN_VENDORS' => false,
];

define('INTEGRATION_EMAIL_FROM', 'noreply@inversionesrojas.com'); // Email desde donde enviar
define('INTEGRATION_EMAIL_SUBJECT', 'Nuevo Pedido Digital - Inversiones Rojas');

// =============================================
// CARGA DINÁMICA DE CONFIGURACIONES DE INTEGRACIONES
// =============================================
function loadDynamicConfig() {
    global $default_integrations;

    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";user=" . DB_USER . ";password=" . DB_PASS;
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->query("SELECT clave, valor, tipo FROM configuraciones WHERE clave IN ('whatsapp_number', 'whatsapp_enabled', 'email_notifications', 'email_enabled', 'telegram_bot_token', 'telegram_chat_id', 'telegram_enabled', 'internal_notifications_enabled', 'auto_assign_vendors')");
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $configMap = [
            'whatsapp_number' => 'INTEGRATION_WHATSAPP_NUMBER',
            'whatsapp_enabled' => 'INTEGRATION_WHATSAPP_ENABLED',
            'email_notifications' => 'INTEGRATION_EMAIL_NOTIFICATIONS',
            'email_enabled' => 'INTEGRATION_EMAIL_ENABLED',
            'telegram_bot_token' => 'INTEGRATION_TELEGRAM_BOT_TOKEN',
            'telegram_chat_id' => 'INTEGRATION_TELEGRAM_CHAT_ID',
            'telegram_enabled' => 'INTEGRATION_TELEGRAM_ENABLED',
            'internal_notifications_enabled' => 'INTEGRATION_INTERNAL_NOTIFICATIONS_ENABLED',
            'auto_assign_vendors' => 'INTEGRATION_AUTO_ASSIGN_VENDORS'
        ];

        foreach ($configs as $config) {
            $constName = $configMap[$config['clave']] ?? null;
            if ($constName) {
                $value = $config['valor'];
                if ($config['tipo'] === 'boolean') {
                    $value = $value === '1' || $value === 'true';
                }
                define($constName, $value);
                unset($default_integrations[$constName]); // Remover de defaults si se cargó de DB
            }
        }
    } catch (Exception $e) {
        // Si falla la conexión, usar valores por defecto
        error_log("Error loading dynamic config: " . $e->getMessage());
    }

    // Definir constantes por defecto que no se cargaron de DB
    foreach ($default_integrations as $const => $value) {
        if (!defined($const)) {
            define($const, $value);
        }
    }
}

// Cargar configuraciones dinámicas
loadDynamicConfig();

// Cargar helper de moneda
require_once __DIR__ . '/../app/helpers/moneda_helper.php';

// Cargar tasa de cambio como constante global
define('TASA_CAMBIO', getTasaCambio());
?>