<?php
// api/expira_reservas.php
// Script para expirar reservas vencidas y liberar stock
// Puede ejecutarse manualmente o via cronjob

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../config/config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    
    $conn->beginTransaction();
    
    // ── Buscar reservas vencidas (fecha_limite < hoy y estado PENDIENTE o PRORROGADA) ──
    $sql = "SELECT r.id, r.codigo_reserva, r.producto_id, r.cantidad, r.fecha_limite,
                   p.nombre as producto_nombre, p.stock_reservado
            FROM reservas r
            INNER JOIN productos p ON r.producto_id = p.id
            WHERE r.estado_reserva IN ('PENDIENTE', 'PRORROGADA')
              AND r.fecha_limite < CURRENT_DATE
            ORDER BY r.fecha_limite";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $reservas_vencidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $expired_count = 0;
    $errors = [];
    
    foreach ($reservas_vencidas as $reserva) {
        $producto_id = $reserva['producto_id'];
        $cantidad = (int)$reserva['cantidad'];
        $codigo_reserva = $reserva['codigo_reserva'];
        
        try {
            // 1. Marcar reserva como VENCIDA
            $stmtUpd = $conn->prepare("
                UPDATE reservas 
                SET estado_reserva = 'VENCIDA', 
                    updated_at = NOW() 
                WHERE id = ? AND estado_reserva IN ('PENDIENTE', 'PRORROGADA')
            ");
            $stmtUpd->execute([$reserva['id']]);
            
            // Solo proceder si se actualizó la reserva
            if ($stmtUpd->rowCount() > 0) {
                // 2. Restaurar stock (devolver al inventario)
                $stmtStock = $conn->prepare("
                    UPDATE productos 
                    SET stock_actual = stock_actual + ?
                    WHERE id = ?
                ");
                $stmtStock->execute([$cantidad, $producto_id]);
                
                // 3. Registrar en bitácora
                $stmtBitacora = $conn->prepare("
                    INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at)
                    VALUES (1, 'EXPIRAR_RESERVA', 'reservas', ?, ?::jsonb, NOW())
                ");
                $stmtBitacora->execute([$reserva['id'], json_encode([
                    'codigo_reserva' => $codigo_reserva,
                    'producto_id' => $producto_id,
                    'cantidad' => $cantidad,
                    'motivo' => 'Vencida por tiempo'
                ])]);
                
                $expired_count++;
            }
        } catch (Exception $e) {
            $errors[] = "Error con reserva $codigo_reserva: " . $e->getMessage();
        }
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Se expiraron $expired_count reserva(s)",
        'expired_count' => $expired_count,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('ERROR en expira_reservas.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>