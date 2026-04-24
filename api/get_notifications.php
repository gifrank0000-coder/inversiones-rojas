<?php
// API endpoint que devuelve notificaciones para el panel del dashboard
// Incluye alertas de stock bajo y notificaciones de pedidos asignados.

header('Content-Type: application/json; charset=UTF-8');

session_start();
require_once __DIR__ . '/../app/models/database.php';
require_once __DIR__ . '/../app/helpers/permissions.php';

$notifications = [];
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_rol'] ?? null;

try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        // Notificaciones de pedidos asignados (solo para vendedores)
        if ($user_role === 'Vendedor' && $user_id) {
            $sql = "SELECT n.id, n.titulo, n.mensaje, n.tipo, n.created_at, n.leida,
                           p.codigo_pedido
                    FROM notificaciones_vendedor n
                    LEFT JOIN pedidos_online p ON n.pedido_id = p.id
                    WHERE n.usuario_id = ?
                    ORDER BY n.created_at DESC
                    LIMIT 20";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $message = $row['mensaje'];
                if (!empty($row['codigo_pedido']) && stripos($message, '(pedido:') === false) {
                    $message .= ' (Pedido: ' . $row['codigo_pedido'] . ')';
                }
                $notifications[] = [
                    'title'   => $row['titulo'],
                    'message' => $message,
                    'type'    => 'info',
                    'icon'    => 'bell',
                    'time'    => $row['created_at'],
                    'unread'  => !$row['leida'],
                    'id'      => 'notif_' . $row['id'],
                ];
            }
        }

        // productos con stock igual o menor al mínimo (solo para quienes manejan inventario)
        // Vendedores no deben ver alertas de stock.
        if (role_has_permission($user_role, 'inventario')) {
            $sql = "SELECT id, nombre, stock_actual, stock_minimo
                    FROM productos
                    WHERE estado = true
                      AND stock_actual <= stock_minimo
                    ORDER BY stock_actual ASC
                    LIMIT 10";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $notifications[] = [
                    'title'   => 'Stock bajo alerta',
                    'message' => $row['nombre'] . ' - Solo ' . $row['stock_actual'] . ' unid. (mín ' . $row['stock_minimo'] . ')',
                    // Usar el mismo estilo base (info) para mantener consistencia visual en el panel
                    'type'    => 'info',
                    'icon'    => 'exclamation-triangle',
                    'time'    => '',
                    'unread'  => true,
                    'id'      => 'stock_' . $row['id'],
                ];
            }
        }
    }
} catch (Exception $e) {
    error_log('Error en get_notifications.php: ' . $e->getMessage());
}

echo json_encode($notifications);
