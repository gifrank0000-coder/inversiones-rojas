<?php
// tests/send_test_mail_verbose.php
// Script de diagnóstico: intenta enviar un mail usando la configuración del proyecto
// Muestra salida detallada en consola y escribe en logs/mail_debug.log

ini_set('display_errors', 1);
error_reporting(E_ALL);

$root = dirname(__DIR__);
$configPath = $root . '/config/config.php';
if (!file_exists($configPath)) {
    echo "ERROR: no se encontró config/config.php\n";
    exit(1);
}
require $configPath;

$checked = [];
$pmLocations = [
    $root . '/PHPMailer-master/src',
    $root . '/PHPMailer/src',
    $root . '/vendor/phpmailer/phpmailer/src'
];
$hasPHPMailer = false; $pmBase = null;
foreach ($pmLocations as $loc) {
    $checked[] = $loc . '/Exception.php';
    if (file_exists($loc . '/Exception.php')) { $hasPHPMailer = true; $pmBase = $loc; break; }
}

$out = [
    'ok' => false,
    'method' => null,
    'exception' => null,
    'errorInfo' => null,
    'debug_output' => null,
    'php_mail_return' => null,
    'last_error' => null,
    'checked_paths' => $checked,
];

$logDir = $root . '/logs';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
$logFile = $logDir . '/mail_debug.log';

if ($hasPHPMailer) {
    require_once $pmBase . '/Exception.php';
    require_once $pmBase . '/PHPMailer.php';
    require_once $pmBase . '/SMTP.php';

    $debugLog = '';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        if (defined('SMTP_USER') && SMTP_USER) {
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS ?? '';
        }
        $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
        $mail->SMTPOptions = ['ssl'=>['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]];

        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) use (&$debugLog) {
            $debugLog .= "[level={$level}] {$str}\n";
        };

        $from = defined('SMTP_FROM') ? SMTP_FROM : (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'no-reply@example.com');
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : (defined('SITE_NAME') ? SITE_NAME : 'App');
        $mail->setFrom($from, $fromName);

        // destinatario de prueba: preferir SMTP_USER, luego SMTP_FROM
        $testTo = defined('SMTP_USER') && SMTP_USER ? SMTP_USER : $from;
        $mail->addAddress($testTo);
        $mail->isHTML(true);
        $mail->Subject = 'Prueba SMTP - diagnóstico';
        $mail->Body = '<p>Prueba de envío desde tests/send_test_mail_verbose.php</p>';

        $mail->send();
        $out['ok'] = true; $out['method'] = 'phpmailer';
        $out['debug_output'] = $debugLog;
        $msg = json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        file_put_contents($logFile, "[" . date('c') . "] SUCCESS via PHPMailer\n" . $msg . "\n", FILE_APPEND);
        echo $msg . "\n";
        exit(0);
    } catch (Exception $e) {
        $out['exception'] = $e->getMessage();
        $out['errorInfo'] = $mail->ErrorInfo ?? null;
        $out['debug_output'] = $debugLog;
        file_put_contents($logFile, "[" . date('c') . "] PHPMailer FAILED\n" . json_encode($out) . "\n", FILE_APPEND);
        echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        exit(1);
    }

} else {
    // fallback a mail()
    $to = defined('SMTP_USER') && SMTP_USER ? SMTP_USER : (defined('SMTP_FROM') ? SMTP_FROM : 'test@example.com');
    $subject = 'Prueba mail() - diagnóstico';
    $body = '<p>Prueba de mail()</p>';
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $headers[] = 'From: ' . (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : (defined('SITE_NAME') ? SITE_NAME : 'App')) . ' <' . (defined('SMTP_FROM') ? SMTP_FROM : 'no-reply@example.com') . '>';
    $ok = @mail($to, $subject, $body, implode("\r\n", $headers));
    $out['php_mail_return'] = $ok;
    $out['method'] = 'mail';
    $last = error_get_last();
    $out['last_error'] = $last;
    file_put_contents($logFile, "[" . date('c') . "] mail() result: " . json_encode($out) . "\n", FILE_APPEND);
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    exit($ok ? 0 : 1);
}

?>