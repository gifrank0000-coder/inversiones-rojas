<?php
// ============================================================
// get_notificaciones.php  →  /api/get_notificaciones.php
// Devuelve las notificaciones del vendedor logueado.
// SOLO muestra notificaciones de pedidos PENDIENTES y EN_VERIFICACION
// Los pedidos CONFIRMADO, INHABILITADO, CANCELADO no aparecen.
// ============================================================
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success'=>false,'notificaciones'=>[],'total_no_leidas'=>0]));
}

$usuario_id = (int)$_SESSION['user_id'];
$accion     = $_GET['accion'] ?? 'listar'; // listar | marcar_leida | marcar_todas

try {
    $db   = new Database();
    $conn = $db->getConnection();

    // Crear tabla si no existe
    $conn->exec("CREATE TABLE IF NOT EXISTS notificaciones_vendedor (
        id          SERIAL PRIMARY KEY,
        pedido_id   INT          NOT NULL,
        titulo      VARCHAR(200) NOT NULL,
        mensaje     TEXT,
        tipo        VARCHAR(40)  DEFAULT 'pedido',
        leida       BOOLEAN      DEFAULT false,
        usuario_id  INT,
        created_at  TIMESTAMPTZ  DEFAULT NOW()
    )");

    // ── Marcar todas las notificaciones de un pedido como leídas ─
    if ($accion === 'marcar_leida_pedido' && isset($_GET['pedido_id'])) {
        $pid = (int)$_GET['pedido_id'];
        $conn->prepare(
            "UPDATE notificaciones_vendedor SET leida = true
             WHERE pedido_id = ? AND (usuario_id = ? OR usuario_id IS NULL)"
        )->execute([$pid, $usuario_id]);
        echo json_encode(['success'=>true]);
        exit;
    }

    // ── Marcar una notificación como leída ────────────────────
    if ($accion === 'marcar_leida' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $conn->prepare(
            "UPDATE notificaciones_vendedor SET leida = true
             WHERE id = ? AND (usuario_id = ? OR usuario_id IS NULL)"
        )->execute([$id, $usuario_id]);
        echo json_encode(['success'=>true]);
        exit;
    }

    // ── Marcar todas como leídas ──────────────────────────────
    if ($accion === 'marcar_todas') {
        $conn->prepare(
            "UPDATE notificaciones_vendedor SET leida = true
             WHERE (usuario_id = ? OR usuario_id IS NULL) AND leida = false"
        )->execute([$usuario_id]);
        echo json_encode(['success'=>true]);
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    // LISTAR NOTIFICACIONES - SOLO PEDIDOS SIN SOLVENTAR
    // ═══════════════════════════════════════════════════════════
    
    // Estados que SÍ deben mostrarse (pedidos sin solventar)
    $estados_activos = ['PENDIENTE', 'EN_VERIFICACION'];
    
    // Placeholder para la query
    $placeholders = implode(',', array_fill(0, count($estados_activos), '?'));
    
    $stmt = $conn->prepare(
        "SELECT n.id, n.pedido_id, n.titulo, n.mensaje, n.tipo, n.leida,
                n.created_at,
                p.codigo_pedido, p.estado_pedido, p.total,
                c.nombre_completo AS cliente_nombre,
                p.telefono_contacto
         FROM notificaciones_vendedor n
         INNER JOIN pedidos_online p ON n.pedido_id = p.id
         LEFT JOIN clientes c ON p.cliente_id = c.id
         WHERE (n.usuario_id = ? OR n.usuario_id IS NULL)
           AND p.estado_pedido IN ($placeholders)
         ORDER BY n.created_at DESC
         LIMIT 50"
    );
    
    // Ejecutar con parámetros
    $params = array_merge([$usuario_id], $estados_activos);
    $stmt->execute($params);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Contar no leídas (solo de pedidos sin solventar) ──────
    $stmt2 = $conn->prepare(
        "SELECT COUNT(*) FROM notificaciones_vendedor n
         INNER JOIN pedidos_online p ON n.pedido_id = p.id
         WHERE (n.usuario_id = ? OR n.usuario_id IS NULL)
           AND n.leida = false
           AND p.estado_pedido IN ($placeholders)"
    );
    $stmt2->execute($params);
    $no_leidas = (int)$stmt2->fetchColumn();

    // ── Formatear para el cliente ─────────────────────────────
    $resultado = array_map(function($n) {
        $hace = '';
        $diff = time() - strtotime($n['created_at']);
        if ($diff < 60)        $hace = 'Hace un momento';
        elseif ($diff < 3600)  $hace = 'Hace ' . floor($diff/60) . ' min';
        elseif ($diff < 86400) $hace = 'Hace ' . floor($diff/3600) . ' h';
        else                   $hace = date('d/m/Y', strtotime($n['created_at']));

        return [
            'id'             => (int)$n['id'],
            'pedido_id'      => (int)$n['pedido_id'],
            'titulo'         => $n['titulo'],
            'mensaje'        => $n['mensaje'],
            'tipo'           => $n['tipo'],
            'leida'          => (bool)$n['leida'],
            'hace'           => $hace,
            'codigo_pedido'  => $n['codigo_pedido'] ?? '',
            'estado_pedido'  => $n['estado_pedido'] ?? '',
            'total'          => number_format((float)($n['total'] ?? 0), 2),
            'cliente_nombre' => $n['cliente_nombre'] ?? '—',
            'telefono'       => $n['telefono_contacto'] ?? '',
        ];
    }, $notifs);

    echo json_encode([
        'success'          => true,
        'notificaciones'   => $resultado,
        'total_no_leidas'  => $no_leidas,
    ]);

} catch(Exception $e) {
    error_log('[get_notificaciones] '.$e->getMessage());
    echo json_encode(['success'=>false,'notificaciones'=>[],'total_no_leidas'=>0]);
}