<?php
session_start();

// Cargar configuración
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die('Error: No se encontró el archivo de configuración en ' . $configPath);
}
require_once $configPath;

// Nota: este script se ejecuta desde /config
// Directorio de backups (config/backups)
$backupDir = __DIR__ . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($backupDir)) {
    die('No se encontró el directorio de backups: ' . $backupDir);
}

$deleted_count = 0;
$deleted_size = 0;

if (is_dir($backupDir)) {
    $cutoff = time() - (30 * 24 * 60 * 60); // 30 días
    
    $files = glob($backupDir . DIRECTORY_SEPARATOR . '*.sql');
    if ($files) {
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                $deleted_size += filesize($file);
                if (@unlink($file)) {
                    $deleted_count++;
                }
            }
        }
    }
    
    // Registrar en bitácora (si es posible)
    try {
        require_once dirname(__DIR__) . '/app/models/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        if ($conn) {
            $stmt = $conn->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, detalles, ip_address) VALUES (:usuario_id, :accion, :tabla_afectada, :detalles, :ip_address)");
            $stmt->execute([
                'usuario_id' => $_SESSION['user_id'] ?? null,
                'accion' => 'BACKUP_CLEAN',
                'tabla_afectada' => 'sistema',
                'detalles' => json_encode([
                    'eliminados' => $deleted_count,
                    'espacio_liberado' => round($deleted_size / (1024*1024), 2) . ' MB'
                ]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        }
    } catch (Exception $e) {
        // No interrumpir si falla la bitácora
    }
    
    echo "✅ Se eliminaron $deleted_count backups antiguos. Espacio liberado: " . round($deleted_size / (1024*1024), 2) . " MB";
} else {
    echo "No se encontró el directorio de backups";
}
?>