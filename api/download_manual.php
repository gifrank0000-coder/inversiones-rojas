<?php
// api/download_manual.php
// Sirve los manuales en PDF según el tipo solicitado

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

// Obtener el tipo de manual
$type = $_GET['type'] ?? '';

if (!in_array($type, ['manual_usuario', 'manual_administrador'])) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Tipo de manual no válido']));
}

// Definir la ruta del archivo
$manualesDir = __DIR__ . '/../docs/manuales/';
$fileName = $type . '.pdf';
$filePath = $manualesDir . $fileName;

// Verificar que el archivo existe
if (!file_exists($filePath)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Manual no encontrado']));
}

// Verificar que sea un archivo
if (!is_file($filePath)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Archivo no válido']));
}

// Configurar headers para descarga
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Leer y enviar el archivo
readfile($filePath);
exit;
?>