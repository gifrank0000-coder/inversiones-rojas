<?php
session_start();
require_once __DIR__ . '/../config/config.php';



$backupDir = realpath(__DIR__ . '/../config') . DIRECTORY_SEPARATOR . 'backups';
if ($backupDir === false) {
    $backupDir = __DIR__ . '/../config/backups';
}

if (isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $filePath = $backupDir . DIRECTORY_SEPARATOR . $file;
    
    if (file_exists($filePath) && is_file($filePath)) {
        // Registrar en bitácora
        try {
            require_once __DIR__ . '/../app/models/database.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            if ($conn) {
                $stmt = $conn->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, detalles, ip_address) VALUES (:usuario_id, :accion, :tabla_afectada, :detalles, :ip_address)");
                $stmt->execute([
                    'usuario_id' => $_SESSION['user_id'] ?? null,
                    'accion' => 'BACKUP_DOWNLOAD',
                    'tabla_afectada' => 'sistema',
                    'detalles' => json_encode(['archivo' => $file]),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]);
            }
        } catch (Exception $e) {
            // Error en bitácora no debe afectar la descarga
        }
        
        // Descargar archivo
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        
        ob_clean();
        flush();
        readfile($filePath);
        exit;
    } else {
        die('Archivo no encontrado');
    }
} else {
    die('Parámetro inválido');
}
?>