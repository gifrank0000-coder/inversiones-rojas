<?php
// ============================================================
// api/compras.php  →  /api/compras.php
// API backend del módulo de Compras.
// Actions soportados:
//   GET  ?action=get&id=X
//   GET  ?action=get_estado&id=X
//   GET  ?action=charts_monthly&period=3|6|12
//   GET  ?action=charts_providers&period=30|90|180
//   POST ?action=save          → crear / editar orden
//   POST ?action=change_status → cambiar estado
//   POST ?action=recepcion_incompleta
//   POST (FormData) action=search → buscar/filtrar
//   GET  ?action=test
// ============================================================
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'No autenticado']));
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

// Leer JSON body para POST
$body = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw) $body = json_decode($raw, true) ?? [];
}

try {
    $db = Database::getInstance();

    // ══════════════════════════════════════════════════════════
    //  TEST
    // ══════════════════════════════════════════════════════════
    if ($action === 'test') {
        echo json_encode(['success' => true, 'message' => 'API compras OK', 'user' => $_SESSION['user_id']]);
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  GRÁFICA MENSUAL  ?action=charts_monthly&period=3|6|12
    // ══════════════════════════════════════════════════════════
    if ($action === 'charts_monthly') {
        $period = (int)($_GET['period'] ?? 3);
        $period = in_array($period, [1, 3, 6, 12]) ? $period : 3;

        $stmt = $db->prepare("
            SELECT
                DATE_TRUNC('month', created_at)                          AS mes,
                TO_CHAR(DATE_TRUNC('month', created_at), 'Mon YYYY')     AS nombre_mes,
                COALESCE(SUM(CASE WHEN activa = true THEN total ELSE 0 END), 0) AS total_compras,
                COUNT(CASE WHEN activa = true THEN 1 END)                AS cantidad_ordenes
            FROM compras
            WHERE created_at >= CURRENT_DATE - INTERVAL '1 month' * :period
            GROUP BY DATE_TRUNC('month', created_at)
            ORDER BY mes ASC
        ");
        $stmt->execute([':period' => $period]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $row['total_compras']    = (float)$row['total_compras'];
            $row['cantidad_ordenes'] = (int)$row['cantidad_ordenes'];
        }

        echo json_encode(['success' => true, 'data' => $data, 'period' => $period]);
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  GRÁFICA PROVEEDORES  ?action=charts_providers&period=30|90|180
    // ══════════════════════════════════════════════════════════
    if ($action === 'charts_providers') {
        $period = (int)($_GET['period'] ?? 30);
        $period = in_array($period, [30, 90, 180, 365]) ? $period : 30;

        $stmt = $db->prepare("
            SELECT
                pr.razon_social                AS proveedor,
                COUNT(*)                       AS cantidad_ordenes,
                COALESCE(SUM(c.total), 0)      AS total_compras
            FROM compras c
            JOIN proveedores pr ON c.proveedor_id = pr.id
            WHERE c.created_at >= CURRENT_DATE - INTERVAL '1 day' * :period
              AND c.activa = true
            GROUP BY pr.razon_social
            ORDER BY total_compras DESC
            LIMIT 8
        ");
        $stmt->execute([':period' => $period]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $row['total_compras']    = (float)$row['total_compras'];
            $row['cantidad_ordenes'] = (int)$row['cantidad_ordenes'];
        }

        echo json_encode(['success' => true, 'data' => $data, 'period' => $period]);
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  GET ORDEN  ?action=get&id=X
    // ══════════════════════════════════════════════════════════
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) die(json_encode(['success' => false, 'message' => 'ID requerido']));

        $stmt = $db->prepare("
            SELECT c.*, pr.razon_social AS proveedor_nombre, pr.rif AS proveedor_rif,
                   pr.direccion AS proveedor_direccion, pr.telefono_principal AS proveedor_telefono,
                   pr.email AS proveedor_email
            FROM compras c
            LEFT JOIN proveedores pr ON pr.id = c.proveedor_id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $compra = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$compra) die(json_encode(['success' => false, 'message' => 'Orden no encontrada']));

        $stmtDet = $db->prepare("
            SELECT dc.*, p.nombre, p.codigo_interno
            FROM detalle_compras dc
            JOIN productos p ON p.id = dc.producto_id
            WHERE dc.compra_id = ?
        ");
        $stmtDet->execute([$id]);
        $productos = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'compra' => $compra, 'productos' => $productos]);
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  GET ESTADO  ?action=get_estado&id=X
    // ══════════════════════════════════════════════════════════
    if ($action === 'get_estado') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) die(json_encode(['success' => false, 'message' => 'ID requerido']));

        $stmt = $db->prepare("
            SELECT c.id, c.codigo_compra, c.estado_compra, c.total,
                   pr.razon_social AS proveedor_nombre
            FROM compras c
            LEFT JOIN proveedores pr ON pr.id = c.proveedor_id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $compra = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$compra) die(json_encode(['success' => false, 'message' => 'Orden no encontrada']));

        $stmtDet = $db->prepare("
            SELECT dc.producto_id AS id, p.nombre, dc.cantidad, dc.precio_unitario
            FROM detalle_compras dc
            JOIN productos p ON p.id = dc.producto_id
            WHERE dc.compra_id = ?
        ");
        $stmtDet->execute([$id]);
        $productos = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'compra' => $compra, 'productos' => $productos]);
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  DETALLE COMPLETO  ?action=detalle&id=X
    // ══════════════════════════════════════════════════════════
    if ($action === 'detalle') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) die(json_encode(['success' => false, 'message' => 'ID requerido']));

        $stmt = $db->prepare("
            SELECT c.*, pr.razon_social AS proveedor_nombre, pr.rif AS proveedor_rif,
                   pr.direccion AS proveedor_direccion, pr.telefono_principal AS proveedor_telefono,
                   pr.email AS proveedor_email, u.nombre_completo AS comprador_nombre
            FROM compras c
            LEFT JOIN proveedores pr ON pr.id = c.proveedor_id
            LEFT JOIN usuarios u ON u.id = c.usuario_id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $compra = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$compra) die(json_encode(['success' => false, 'message' => 'Orden no encontrada']));

        $stmtDet = $db->prepare("
            SELECT dc.*, p.nombre, p.codigo_interno
            FROM detalle_compras dc
            JOIN productos p ON p.id = dc.producto_id
            WHERE dc.compra_id = ?
        ");
        $stmtDet->execute([$id]);
        $productos = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'compra' => $compra, 'productos' => $productos]);
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  SAVE  POST ?action=save
    // ══════════════════════════════════════════════════════════
    if ($action === 'save' && $method === 'POST') {
        $id          = (int)($body['id'] ?? 0);
        $provId      = (int)($body['proveedor_id'] ?? 0);
        $fecha       = trim($body['fecha_estimada_entrega'] ?? '');
        $obs         = trim($body['observaciones'] ?? '');
        $notas_inc   = trim($body['notas_incidencia'] ?? '');
        $subtotal    = (float)($body['subtotal'] ?? 0);
        $iva         = (float)($body['iva'] ?? 0);
        $total       = (float)($body['total'] ?? 0);
        $estado      = $id ? trim($body['estado'] ?? 'PENDIENTE') : 'PENDIENTE';
        $productos   = $body['productos'] ?? [];
        
        // Campos de moneda
        $monedaFactura = $body['moneda_factura'] ?? 'USD';
        $tasaCambio    = (float)($body['tasa_cambio'] ?? 1);
        $montoBs       = (float)($body['monto_bs'] ?? $total);
        $montoUsd      = (float)($body['monto_usd'] ?? $total);

        if (!$provId) die(json_encode(['success' => false, 'message' => 'Proveedor requerido']));
        if (!$fecha)  die(json_encode(['success' => false, 'message' => 'Fecha estimada requerida']));
        if (empty($productos)) die(json_encode(['success' => false, 'message' => 'Debe agregar productos']));

        $conn = $db; // singleton PDO

        if ($id) {
            // ACTUALIZAR
            $conn->prepare("
                UPDATE compras SET proveedor_id=?, fecha_estimada_entrega=?, observaciones=?,
                    notas_incidencia=?, subtotal=?, iva=?, total=?, estado_compra=?,
                    moneda_factura=?, tasa_cambio=?, monto_bs=?, monto_usd=?
                WHERE id=?
            ")->execute([$provId, $fecha, $obs, $notas_inc, $subtotal, $iva, $total, $estado, $monedaFactura, $tasaCambio, $montoBs, $montoUsd, $id]);
            
            // Borrar detalles anteriores y reinsertar
            $conn->prepare("DELETE FROM detalle_compras WHERE compra_id=?")->execute([$id]);
            $ins = $conn->prepare("INSERT INTO detalle_compras (compra_id, producto_id, cantidad, precio_unitario, subtotal, created_at, precio_unitario_bs, precio_unitario_usd) VALUES (?,?,?,?,?,NOW(),?,?)");
            foreach ($productos as $p) {
                $pid  = (int)($p['id'] ?? 0);
                $cant = (int)($p['cantidad'] ?? 0);
                $prec = (float)($p['precio_unitario'] ?? 0);
                $precBs = (float)($p['precio_unitario_bs'] ?? $prec);
                $precUsd = (float)($p['precio_unitario_usd'] ?? $prec);
                if ($pid <= 0 || $cant <= 0) continue;
                $ins->execute([$id, $pid, $cant, $prec, round($prec*$cant,2), $precBs, $precUsd]);
            }

            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Orden actualizada']);

        } else {
            // CREAR
            $codigo = 'OC' . date('Ymd') . strtoupper(substr(md5(uniqid('',true)), 0, 6));
            $stmt = $conn->prepare("
                INSERT INTO compras (codigo_compra, proveedor_id, usuario_id, subtotal, iva, total,
                    estado_compra, fecha_estimada_entrega, observaciones, created_at,
                    moneda_factura, tasa_cambio, monto_bs, monto_usd)
                VALUES (?,?,?,?,?,?,'PENDIENTE',?,?,NOW(),?,?,?,?) RETURNING id
            ");
            $stmt->execute([$codigo, $provId, $_SESSION['user_id'], $subtotal, $iva, $total, $fecha, $obs, $monedaFactura, $tasaCambio, $montoBs, $montoUsd]);
            $newId = $stmt->fetchColumn();
            
            $ins = $conn->prepare("INSERT INTO detalle_compras (compra_id, producto_id, cantidad, precio_unitario, subtotal, created_at, precio_unitario_bs, precio_unitario_usd) VALUES (?,?,?,?,?,NOW(),?,?)");
            foreach ($productos as $p) {
                $pid  = (int)($p['id'] ?? 0);
                $cant = (int)($p['cantidad'] ?? 0);
                $prec = (float)($p['precio_unitario'] ?? 0);
                $precBs = (float)($p['precio_unitario_bs'] ?? $prec);
                $precUsd = (float)($p['precio_unitario_usd'] ?? $prec);
                if ($pid <= 0 || $cant <= 0) continue;
                $ins->execute([$newId, $pid, $cant, $prec, round($prec*$cant,2), $precBs, $precUsd]);
            }

            // Bitácora
            try {
                $conn->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at) VALUES (?,?,?,?,?::jsonb,NOW())")
                     ->execute([$_SESSION['user_id'], 'CREAR_COMPRA', 'compras', $newId, json_encode(['codigo'=>$codigo,'total'=>$total])]);
            } catch(Exception $e) {}

            echo json_encode(['success' => true, 'id' => $newId, 'codigo_compra' => $codigo, 'total' => $total]);
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  CHANGE STATUS  POST ?action=change_status
    // ══════════════════════════════════════════════════════════
    if ($action === 'change_status' && $method === 'POST') {
        $id     = (int)($body['id'] ?? 0);
        $estado = strtoupper(trim($body['estado'] ?? ''));
        $notas  = trim($body['notas'] ?? '');

        $permitidos = ['PENDIENTE','RECEPCION','COMPLETADA','INCOMPLETA','CANCELADA'];
        if (!$id || !in_array($estado, $permitidos)) {
            die(json_encode(['success' => false, 'message' => 'Datos inválidos']));
        }

        $db->prepare("UPDATE compras SET estado_compra=?, notas_incidencia=CASE WHEN ?<>'' THEN ? ELSE notas_incidencia END WHERE id=?")
           ->execute([$estado, $notas, $notas, $id]);

        // Si se completó, actualizar stock
        if ($estado === 'COMPLETADA') {
            $dets = $db->prepare("SELECT producto_id, cantidad FROM detalle_compras WHERE compra_id=?");
            $dets->execute([$id]);
            foreach ($dets->fetchAll(PDO::FETCH_ASSOC) as $det) {
                try {
                    // Stock anterior para bitácora
                    $sa = $db->prepare("SELECT stock_actual FROM productos WHERE id=?");
                    $sa->execute([$det['producto_id']]);
                    $stock_ant = (int)$sa->fetchColumn();
                    $stock_new = $stock_ant + (int)$det['cantidad'];

                    $db->prepare("UPDATE productos SET stock_actual=?, updated_at=NOW() WHERE id=?")
                       ->execute([$stock_new, $det['producto_id']]);

                    $db->prepare("INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, motivo, referencia, usuario_id, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())")
                       ->execute([$det['producto_id'], 'ENTRADA', $det['cantidad'], $stock_ant, $stock_new, 'Recepción de compra', 'compra_id:'.$id, $_SESSION['user_id']]);
                } catch(Exception $e) { error_log('Stock update error: '.$e->getMessage()); }
            }
        }

        try {
            $db->prepare("INSERT INTO bitacora_sistema (usuario_id, accion, tabla_afectada, registro_id, detalles, created_at) VALUES (?,?,?,?,?::jsonb,NOW())")
               ->execute([$_SESSION['user_id'], 'CAMBIAR_ESTADO_COMPRA', 'compras', $id, json_encode(['estado'=>$estado,'notas'=>$notas])]);
        } catch(Exception $e) {}

        echo json_encode(['success' => true, 'message' => "Estado actualizado a {$estado}"]);
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  RECEPCION INCOMPLETA
    // ══════════════════════════════════════════════════════════
    if ($action === 'recepcion_incompleta' && $method === 'POST') {
        $id    = (int)($body['id'] ?? 0);
        $notas = trim($body['notas'] ?? '');
        if (!$id) die(json_encode(['success' => false, 'message' => 'ID requerido']));

        $db->prepare("UPDATE compras SET estado_compra='INCOMPLETA', notas_incidencia=? WHERE id=?")
           ->execute([$notas, $id]);

        echo json_encode(['success' => true, 'message' => 'Orden marcada como incompleta']);
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  SEARCH  POST (FormData) action=search
    // ══════════════════════════════════════════════════════════
    if ($action === 'search') {
        $q          = trim($_POST['q']           ?? '');
        $estado     = trim($_POST['estado']      ?? '');
        $prov_id    = (int)($_POST['proveedor_id'] ?? 0);
        $date_from  = trim($_POST['date_from']   ?? '');
        $date_to    = trim($_POST['date_to']     ?? '');
        $date_range = trim($_POST['date_range']  ?? '');

        // Convertir date_range a date_from/date_to si se usa
        if (!$date_from && !$date_to && $date_range) {
            $today = new DateTime();
            switch ($date_range) {
                case 'today':
                    $date_from = $date_to = $today->format('Y-m-d');
                    break;
                case 'yesterday':
                    $yesterday = (clone $today)->modify('-1 day');
                    $date_from = $date_to = $yesterday->format('Y-m-d');
                    break;
                case 'week':
                    $startWeek = (clone $today)->modify('monday this week');
                    $date_from = $startWeek->format('Y-m-d');
                    $date_to = $today->format('Y-m-d');
                    break;
                case 'month':
                    $date_from = $today->format('Y-m-01');
                    $date_to = $today->format('Y-m-t');
                    break;
                case 'last_month':
                    $firstPrev = (clone $today)->modify('first day of last month');
                    $lastPrev = (clone $today)->modify('last day of last month');
                    $date_from = $firstPrev->format('Y-m-d');
                    $date_to = $lastPrev->format('Y-m-d');
                    break;
            }
        }

        $where  = ['c.activa = true'];
        $params = [];

        if ($q) {
            $where[]  = "(c.codigo_compra ILIKE ? OR pr.razon_social ILIKE ?)";
            $params[] = "%{$q}%"; $params[] = "%{$q}%";
        }
        if ($estado) { $where[] = "c.estado_compra = ?"; $params[] = $estado; }
        if ($prov_id) { $where[] = "c.proveedor_id = ?"; $params[] = $prov_id; }
        if ($date_from) { $where[] = "c.created_at >= ?"; $params[] = $date_from . ' 00:00:00'; }
        if ($date_to)   { $where[] = "c.created_at <= ?"; $params[] = $date_to   . ' 23:59:59'; }

        $sql = "
            SELECT c.id, c.codigo_compra, c.estado_compra, c.total, c.subtotal, c.iva,
                   c.fecha_estimada_entrega, c.created_at, c.activa, c.observaciones,
                   pr.razon_social AS proveedor_nombre,
                   (SELECT COUNT(*) FROM detalle_compras dc WHERE dc.compra_id = c.id) AS productos_count,
                   (SELECT SUM(dc.cantidad) FROM detalle_compras dc WHERE dc.compra_id = c.id) AS total_unidades
            FROM compras c
            LEFT JOIN proveedores pr ON pr.id = c.proveedor_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY c.created_at DESC
            LIMIT 100
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'compras' => $compras]);
        exit;
    }

    // ══════════════════════════════════════════════════════════
    //  TOGGLE ACTIVA  ?action=toggle_activa
    // ══════════════════════════════════════════════════════════
    if ($action === 'toggle_activa' && $method === 'POST') {
        $id     = (int)($body['id'] ?? 0);
        $activa = isset($body['activa']) ? (bool)$body['activa'] : false;
        if (!$id) die(json_encode(['success' => false, 'message' => 'ID requerido']));

        $db->prepare("UPDATE compras SET activa=? WHERE id=?")
           ->execute([$activa, $id]);

        echo json_encode(['success' => true]);
        exit;
    }

    // Action desconocido
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => "Acción desconocida: {$action}"]);

} catch (Exception $e) {
    error_log('[api/compras.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}