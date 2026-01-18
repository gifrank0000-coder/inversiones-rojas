<?php
// Configuración básica del proyecto
// Ajusta BASE_URL si el proyecto no está en la raíz del servidor
// Ruta relativa del proyecto (útil para construir rutas internas)
if (!defined('BASE_URL')) {
    define('BASE_URL', '/inversiones-rojas');
}

// Nota: soporte HTTPS gestionado fuera de la aplicación.
// Se ha eliminado la constante FORCE_HTTPS y cualquier redirección automática
// a HTTPS para evitar problemas en entornos de desarrollo/local.

// Debug: activar mensajes detallados en desarrollo (NO dejar true en producción)
if (!defined('APP_DEBUG')) {
    // Activar debug automáticamente cuando se accede por localhost en desarrollo
    $host = $_SERVER['HTTP_HOST'] ?? null;
    if (php_sapi_name() === 'cli' || $host === 'localhost' || $host === '127.0.0.1') {
        define('APP_DEBUG', true);
    } else {
        define('APP_DEBUG', false);
    }
}

// (Redirección HTTPS eliminada por seguridad en entornos locales)

// Puedes añadir más constantes aquí (DB, env, etc.)
// Control de versionado de assets (cache-busting) — desactivar en desarrollo
if (!defined('ASSET_VERSIONING')) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        define('ASSET_VERSIONING', false);
    } else {
        define('ASSET_VERSIONING', true);
    }
}
?>
