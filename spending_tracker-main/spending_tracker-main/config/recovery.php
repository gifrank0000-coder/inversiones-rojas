<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

require_once('config.php');

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
if (empty($email)) {
  header("Location: ../index.php?message=invalid_email");
  exit;
}

$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
  header("Location: ../index.php?message=db_error");
  exit;
}

try {
  $stmt = $conn->prepare('SELECT * FROM usuarios WHERE correo = :email AND status = 1');
  $stmt->execute(['email' => $email]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  header("Location: ../index.php?message=db_error");
  exit;
}

if ($row) {
  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'gifrank0000@gmail.com';
    $mail->Password   = 'hkac hswn ijbm gbrv';
    $mail->Port       = 587;

    $mail->setFrom('CORREO_ELECTRONICO_FROM', 'NOMBRE_FORM');
    // enviar al correo del usuario encontrado
    $mail->addAddress($row['correo'], $row['nombre'] ?? '');
    $mail->isHTML(true);
    $mail->Subject = 'Recuperación de contraseña';
    $mail->Body    = 'Hola, este es un correo generado para solicitar tu recuperación de contraseña, por favor, visita la página de <a href="http://localhost/spending_tracker/change_password.php?id=' . htmlspecialchars($row['id']) . '">Recuperación de contraseña</a>';

    $mail->send();
    header("Location: ../index.php?message=ok");
  } catch (Exception $e) {
    header("Location: ../index.php?message=error");
  }

} else {
  header("Location: ../index.php?message=not_found");
}

?>
