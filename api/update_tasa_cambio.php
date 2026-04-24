<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/moneda_helper.php';

header('Content-Type: application/json');

session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tasa = $_POST['tasa'] ?? '';
    
    if (empty($tasa) || !is_numeric($tasa) || floatval($tasa) <= 0) {
        echo json_encode(['success' => false, 'message' => 'Tasa de cambio inválida']);
        exit;
    }
    
    $result = guardarTasaCambio(floatval($tasa));
    
    if ($result['success']) {
        echo json_encode([
            'success' => true, 
            'message' => 'Tasa de cambio actualizada',
            'tasa' => floatval($tasa)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $result['message']]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
