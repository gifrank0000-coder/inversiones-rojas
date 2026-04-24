<?php
// Intentar localizar PHPMailer en rutas comunes y mostrar error útil si no se encuentra
$candidates = [
    __DIR__ . '/../PHPMailer-master/src/Exception.php',
    __DIR__ . '/../PHPMailer/src/Exception.php',
    __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php'
];
$found = false;
foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        $base = dirname($candidate);
        require $base . '/Exception.php';
        require $base . '/PHPMailer.php';
        require $base . '/SMTP.php';
        $found = true;
        break;
    }
}

if (!$found) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'PHPMailer not found', 'checked' => $candidates], JSON_UNESCAPED_SLASHES);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587;
    $mail->SMTPAuth = true;
    $mail->Username = 'gifrank0000@gmail.com'; // reemplaza si es necesario
    $mail->Password = 'TU_APP_PASSWORD';       // reemplaza con app password
    $mail->SMTPSecure = 'tls';
    $mail->SMTPOptions = ['ssl'=>['verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true]];
    $mail->setFrom('gifrank0000@gmail.com','Inversiones Rojas');
    $mail->addAddress('tu@correo.com','Destinatario');
    $mail->isHTML(true);
    $mail->Subject = 'Prueba SMTP';
    $mail->Body = '<p>Prueba SMTP desde PHPMailer</p>';
    $mail->send();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>true,'message'=>'Enviado']);
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'errorInfo'=>$mail->ErrorInfo]);
}
?>