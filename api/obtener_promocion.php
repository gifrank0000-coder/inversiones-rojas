<?php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

// Ruta robusta compatible con cualquier ubicación
foreach ([
    __DIR__ . '/../../models/database.php',
    __DIR__ . '/../app/models/database.php',
    __DIR__ . '/../../app/models/database.php',
] as $path) {
    if (file_exists($path)) { require_once $path; break; }
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception('No se pudo conectar a la base de datos');

    $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
    if (!$id) {
        throw new Exception('ID de promoción requerido');
    }

    // Obtener promoción con detalles
    $sql = "SELECT 
                p.id, p.nombre, p.descripcion, p.tipo_promocion, 
                p.valor, p.fecha_inicio, p.fecha_fin, p.estado,
                p.imagen_url, p.imagen_banco_key, p.tipo_imagen,
                COUNT(pp.producto_id) as total_productos,
                COALESCE(SUM(CASE WHEN dv.id IS NOT NULL THEN 1 ELSE 0 END), 0) as ventas_generadas,
                COALESCE(SUM(CASE WHEN dv.id IS NOT NULL THEN dv.subtotal ELSE 0 END), 0) as monto_generado
            FROM promociones p
            LEFT JOIN producto_promociones pp ON p.id = pp.promocion_id
            LEFT JOIN detalle_ventas dv ON pp.producto_id = dv.producto_id 
                AND dv.venta_id IN (
                    SELECT id FROM ventas WHERE created_at >= p.fecha_inicio::date AND created_at::date <= p.fecha_fin::date
                )
            WHERE p.id = ?
            GROUP BY p.id, p.nombre, p.descripcion, p.tipo_promocion, p.valor, 
                     p.fecha_inicio, p.fecha_fin, p.estado, p.imagen_url, 
                     p.imagen_banco_key, p.tipo_imagen";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $promocion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promocion) {
        throw new Exception('Promoción no encontrada');
    }

    // Obtener productos asociados (solo IDs para el formulario)
    $sql = "SELECT producto_id FROM producto_promociones WHERE promocion_id = ? ORDER BY producto_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $productos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Obtener detalles de productos para modal
    $sql = "SELECT pp.producto_id, pr.nombre, pr.precio_venta,
                    COALESCE(pi.imagen_url, '') as imagen_url
            FROM producto_promociones pp
            INNER JOIN productos pr ON pp.producto_id = pr.id
            LEFT JOIN producto_imagenes pi ON pr.id = pi.producto_id AND pi.es_principal = true
            WHERE pp.promocion_id = ?
            ORDER BY pr.nombre ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $productos_detail = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mapear tipo para consistencia con frontend
    $tipoMap = [
        'DESCUENTO' => 'descuento',
        '2X1' => '2x1',
        'PORCENTAJE' => 'porcentaje'
    ];
    
    $tipoUpper = strtoupper($promocion['tipo_promocion'] ?? '');
    $promocion['tipo_promocion'] = $tipoMap[$tipoUpper] ?? strtolower($tipoUpper);

    echo json_encode([
        'success' => true,
        'promocion' => $promocion,
        'productos' => $productos,
        'productos_detail' => $productos_detail
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>