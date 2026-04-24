<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/moneda_helper.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'No autorizado';
    echo json_encode($response);
    exit;
}

$tasa = $_POST['tasa'] ?? null;
$fechaVigencia = $_POST['fecha_vigencia'] ?? date('Y-m-d');
$observaciones = $_POST['observaciones'] ?? '';

if (empty($tasa) || !is_numeric($tasa) || floatval($tasa) <= 0) {
    $response['message'] = 'La tasa debe ser un número positivo';
    echo json_encode($response);
    exit;
}

$tasa = floatval($tasa);
$usuarioId = $_SESSION['user_id'];

$result = guardarTasaCambio($tasa, $fechaVigencia, $usuarioId, $observaciones);

if ($result['success']) {
    $response['success'] = true;
    $response['message'] = 'Tasa guardada correctamente';
    $response['data'] = [
        'tasa' => $tasa,
        'fecha_vigencia' => $fechaVigencia,
        'tasa_info' => getTasaInfo()
    ];
} else {
    $response['message'] = $result['message'] ?? 'Error al guardar la tasa';
}

echo json_encode($response);
