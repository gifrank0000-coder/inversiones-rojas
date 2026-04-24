<?php
// ============================================================
// debug_bandeja.php → Poner en la raíz del proyecto
// Visitar: http://localhost/inversiones-rojas/debug_bandeja.php
// ELIMINAR después de resolver el problema
// ============================================================
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/models/database.php';

echo "<pre style='font-family:monospace;font-size:13px;padding:20px;'>";
echo "<h2>🔍 Debug Bandeja de Pedidos</h2>\n";
echo str_repeat('─', 60) . "\n\n";

// ── 1. Sesión actual ──────────────────────────────────────────
echo "1. VARIABLES DE SESIÓN\n";
$vars = ['user_id','user_name','email','user_rol','cliente_id'];
foreach ($vars as $v) {
    $val = $_SESSION[$v] ?? '(no definida)';
    echo "   \$_SESSION['{$v}'] = {$val}\n";
}
echo "\n   Todas las variables de sesión:\n";
foreach ($_SESSION as $k => $v) {
    if (!is_array($v)) echo "   [{$k}] = {$v}\n";
}

// ── 2. Buscar cliente ─────────────────────────────────────────
echo "\n2. BÚSQUEDA DE CLIENTE\n";
$user_id = (int)($_SESSION['user_id'] ?? 0);

if (!$user_id) {
    echo "   ❌ No hay user_id en sesión — inicia sesión primero\n";
    echo "</pre>"; exit;
}

try {
    $db   = new Database();
    $conn = $db->getConnection();

    // Método 1
    $stmt = $conn->prepare("SELECT id, nombre_completo, email, usuario_id FROM clientes WHERE usuario_id = ?");
    $stmt->execute([$user_id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Método 1 (clientes.usuario_id = {$user_id}): " . ($r ? "✅ cliente_id={$r['id']} nombre={$r['nombre_completo']}" : "❌ No encontrado") . "\n";

    // Método 2
    $stmt = $conn->prepare("SELECT id, nombre_completo, cliente_id FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Método 2 (usuarios.cliente_id WHERE id={$user_id}): ";
    if ($u) {
        echo "usuario encontrado, cliente_id=" . ($u['cliente_id'] ?? 'NULL') . "\n";
    } else {
        echo "❌ usuario no encontrado\n";
    }

    // Mostrar datos completos del usuario
    echo "\n3. DATOS COMPLETOS DEL USUARIO (id={$user_id})\n";
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usr = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($usr) {
        foreach ($usr as $k => $v) {
            if ($k !== 'password_hash') echo "   {$k} = " . ($v ?? 'NULL') . "\n";
        }
    }

    // ── 3. Buscar pedidos por todos los métodos ───────────────
    echo "\n4. PEDIDOS BUSCANDO POR DIFERENTES CRITERIOS\n";

    // Por usuario_id en clientes
    if ($r) {
        $stmt = $conn->prepare("SELECT id, codigo_pedido, cliente_id FROM pedidos_online WHERE cliente_id = ? ORDER BY id DESC LIMIT 5");
        $stmt->execute([$r['id']]);
        $ped = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Por cliente_id={$r['id']}: " . count($ped) . " pedido(s)\n";
        foreach ($ped as $p) echo "     → {$p['codigo_pedido']} (cliente_id={$p['cliente_id']})\n";
    }

    // Por cliente_id de usuarios
    if ($u && $u['cliente_id']) {
        $stmt = $conn->prepare("SELECT id, codigo_pedido, cliente_id FROM pedidos_online WHERE cliente_id = ? ORDER BY id DESC LIMIT 5");
        $stmt->execute([$u['cliente_id']]);
        $ped2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Por usuarios.cliente_id={$u['cliente_id']}: " . count($ped2) . " pedido(s)\n";
        foreach ($ped2 as $p) echo "     → {$p['codigo_pedido']}\n";
    }

    // Todos los pedidos (para verificar que existen)
    $stmt = $conn->query("SELECT id, codigo_pedido, cliente_id FROM pedidos_online ORDER BY id DESC LIMIT 5");
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\n   Últimos 5 pedidos en la BD (todos los clientes):\n";
    foreach ($todos as $p) echo "     → {$p['codigo_pedido']} (cliente_id={$p['cliente_id']})\n";

    // ── 4. Verificar clientes ─────────────────────────────────
    echo "\n5. CLIENTES EN LA BD (primeros 10)\n";
    $stmt = $conn->query("SELECT id, nombre_completo, email, usuario_id FROM clientes ORDER BY id LIMIT 10");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($clientes as $c) {
        echo "   cliente_id={$c['id']} | nombre={$c['nombre_completo']} | email=" . ($c['email']??'NULL') . " | usuario_id=" . ($c['usuario_id']??'NULL') . "\n";
    }

} catch (Exception $e) {
    echo "   ❌ Error BD: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('─', 60) . "\n";
echo "⚠️  ELIMINA este archivo después del diagnóstico\n";
echo "</pre>";