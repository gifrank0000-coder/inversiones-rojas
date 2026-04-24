<?php
// ============================================================
// test_email.php  →  Poner en la RAÍZ del proyecto
// Acceder desde el navegador: http://localhost/inversiones-rojas/test_email.php
// ELIMINAR después de confirmar que funciona
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/models/database.php';
require_once __DIR__ . '/app/helpers/EmailHelper.php';

echo "<pre style='font-family:monospace;font-size:13px;padding:20px;'>";
echo "<h2>🔧 Diagnóstico SMTP - Inversiones Rojas</h2>\n";
echo str_repeat('─', 60) . "\n\n";

// ── 1. Verificar extensión OpenSSL ────────────────────────────
echo "1. OPENSSL\n";
if (extension_loaded('openssl')) {
    echo "   ✅ openssl CARGADO — " . OPENSSL_VERSION_TEXT . "\n";
} else {
    echo "   ❌ openssl NO ESTÁ CARGADO\n";
    echo "   → Abre C:\\xampp\\php\\php.ini\n";
    echo "   → Busca ';extension=openssl'\n";
    echo "   → Quita el punto y coma → 'extension=openssl'\n";
    echo "   → Reinicia Apache\n\n";
    echo "</pre>";
    exit;
}

// ── 2. Verificar constantes SMTP ──────────────────────────────
echo "\n2. CONSTANTES SMTP EN config.php\n";
$ok = true;
foreach (['SMTP_HOST','SMTP_PORT','SMTP_USER','SMTP_PASS','SMTP_FROM','SMTP_SECURE'] as $c) {
    if (defined($c)) {
        $val = $c === 'SMTP_PASS' ? str_repeat('*', strlen(constant($c))) : constant($c);
        echo "   ✅ {$c} = {$val}\n";
    } else {
        echo "   ❌ {$c} NO DEFINIDA\n";
        $ok = false;
    }
}
if (!$ok) {
    echo "\n   → Agrega las constantes faltantes en config/config.php\n";
    echo "</pre>"; exit;
}

// ── 3. Test de conexión TCP al servidor SMTP ──────────────────
echo "\n3. CONEXIÓN TCP A " . SMTP_HOST . ":" . SMTP_PORT . "\n";
$errno = 0; $errstr = '';
$sock = @fsockopen(SMTP_HOST, (int)SMTP_PORT, $errno, $errstr, 10);
if ($sock) {
    echo "   ✅ Conexión TCP exitosa\n";
    fclose($sock);
} else {
    echo "   ❌ No se pudo conectar: {$errstr} (código {$errno})\n";
    echo "   → Verifica que XAMPP no tiene el firewall bloqueando el puerto " . SMTP_PORT . "\n";
    echo "   → O que tu antivirus/Windows Firewall permite conexiones salientes\n";
    echo "</pre>"; exit;
}

// ── 4. Intentar enviar email real ─────────────────────────────
echo "\n4. ENVIANDO EMAIL DE PRUEBA\n";

// Leer email destino desde BD si es posible
$em_dest = '';
try {
    $db   = new Database();
    $conn = $db->getConnection();
    $cfg  = $conn->query("SELECT clave, valor FROM configuracion_integraciones")
                 ->fetchAll(PDO::FETCH_KEY_PAIR);
    $em_dest = $cfg['email_notifications'] ?? '';
    echo "   Email destino (desde BD): {$em_dest}\n";
} catch (Exception $e) {
    echo "   ⚠️  No se pudo leer BD: " . $e->getMessage() . "\n";
}

// Fallback si no hay BD
if (!$em_dest) {
    $em_dest = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : SMTP_USER;
    echo "   Email destino (fallback config): {$em_dest}\n";
}

$html_test = "
<div style='font-family:Arial,sans-serif;max-width:500px;margin:0 auto;'>
    <div style='background:#1F9166;padding:20px;border-radius:8px 8px 0 0;text-align:center;'>
        <h2 style='color:white;margin:0;'>Inversiones Rojas</h2>
    </div>
    <div style='padding:20px;background:#f9f9f9;border:1px solid #e0e0e0;'>
        <p>✅ Este es un <strong>email de prueba</strong> del sistema de pedidos digitales.</p>
        <p>Si recibes este mensaje, el SMTP está configurado correctamente.</p>
        <p style='color:#888;font-size:12px;'>Enviado el: " . date('d/m/Y H:i:s') . "</p>
    </div>
</div>";

$resultado = enviarEmailSMTP(
    $em_dest,
    'Inversiones Rojas',
    '✅ Prueba SMTP - Inversiones Rojas',
    $html_test
);

if ($resultado['success']) {
    echo "   ✅ EMAIL ENVIADO CORRECTAMENTE a {$em_dest}\n";
    echo "   → Revisa la bandeja de entrada (también Spam)\n";
} else {
    echo "   ❌ ERROR: " . $resultado['message'] . "\n\n";
    echo "   SOLUCIONES COMUNES:\n";
    echo "   ─────────────────────────────────────────\n";

    $msg = $resultado['message'];

    if (stripos($msg, 'starttls') !== false || stripos($msg, 'tls') !== false) {
        echo "   → Problema TLS: verifica que extension=openssl está en php.ini\n";
    }
    if (stripos($msg, 'contrase') !== false || stripos($msg, '535') !== false || stripos($msg, 'auth') !== false) {
        echo "   → Contraseña incorrecta. El SMTP_PASS debe ser el App Password\n";
        echo "     de 16 caracteres (con espacios), NO tu contraseña normal de Gmail.\n";
        echo "   → Crea uno en: https://myaccount.google.com/apppasswords\n";
        echo "   → Asegura que tienes activada la Verificación en 2 pasos\n";
    }
    if (stripos($msg, 'fsockopen') !== false || stripos($msg, 'connect') !== false) {
        echo "   → No se pudo conectar al servidor SMTP\n";
        echo "   → Firewall de Windows o antivirus puede estar bloqueando\n";
        echo "   → Prueba desactivar temporalmente el antivirus y reintenta\n";
    }
    if (stripos($msg, 'banner') !== false) {
        echo "   → El servidor rechazó la conexión. Verifica SMTP_HOST y SMTP_PORT\n";
    }
}

echo "\n" . str_repeat('─', 60) . "\n";
echo "⚠️  ELIMINA este archivo después de la prueba\n";
echo "   (contiene información sensible de SMTP)\n";
echo "</pre>";