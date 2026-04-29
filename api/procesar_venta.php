<?php

if (session_status() === PHP_SESSION_NONE) session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// ── Log detallado ─────────────────────────────────────────────────────────
$log_file    = __DIR__ . '/debug_ventas.log';
$log_content = "=== " . date('Y-m-d H:i:s') . " ===\n";

try {
    $log_content .= "METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";

    $raw_input = file_get_contents('php://input');
    $log_content .= "RAW (500): " . substr($raw_input, 0, 500) . "\n";

    if (empty($raw_input)) throw new Exception('No se recibieron datos');

    $input = json_decode($raw_input, true);
    if (json_last_error() !== JSON_ERROR_NONE)
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    if (!$input) throw new Exception('JSON decodificado como null');

    // ── Validación básica ─────────────────────────────────────────────────
    if (empty($input['productos']) || !is_array($input['productos']))
        throw new Exception('No hay productos en la venta');

    $log_content .= "Productos: " . count($input['productos']) . "\n";
    $log_content .= "Multi-pago: " . ($input['multi_pago'] ? 'sí' : 'no') . "\n";

    // ── Conexión ──────────────────────────────────────────────────────────
    // Incluir database con manejo de errores robusto
    $db_path = realpath(__DIR__ . '/../app/models/database.php');
    if (!file_exists($db_path)) {
        throw new Exception('No se encontró: ' . $db_path);
    }
    require_once $db_path;
    
    // Verificar que la clase exista
    if (!class_exists('Database')) {
        throw new Exception('Clase Database no definida después de incluir');
    }
    
    $pdo = Database::getInstance();
    if (!$pdo) throw new Exception('Sin conexión a la base de datos');

    $log_content .= "Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";

    // ── Código de venta único ─────────────────────────────────────────────
    do {
        $codigo_venta = 'V-' . date('YmdHis') . '-' . rand(100, 999);
        $st = $pdo->prepare("SELECT id FROM ventas WHERE codigo_venta = ?");
        $st->execute([$codigo_venta]);
    } while ($st->fetch());

    $log_content .= "Código: $codigo_venta\n";

    // ── Cliente ───────────────────────────────────────────────────────────
    $cliente_id = null;
    if (!empty($input['cliente_id'])) {
        $cs = (string)$input['cliente_id'];
        if (strpos($cs, 'cf-') !== 0) {
            $cid = (int)$cs;
            $sc  = $pdo->prepare("SELECT id FROM clientes WHERE id = ? AND estado = true");
            $sc->execute([$cid]);
            if ($sc->fetch()) $cliente_id = $cid;
        }
    }

    // ── Valores financieros ───────────────────────────────────────────────
    $usuario_id  = $_SESSION['user_id'] ?? 1;
    $tasa_cambio = (float)($input['tasa_cambio'] ?? 1);
    $subtotal    = (float)($input['subtotal']    ?? 0);
    $iva         = (float)($input['iva']         ?? 0);
    $total       = (float)($input['total']       ?? 0);
    $monto_bs    = (float)($input['monto_bs']    ?? ($total * $tasa_cambio));
    $monto_usd   = (float)($input['monto_usd']   ?? $total);
    $multi_pago  = !empty($input['multi_pago']);
    $pagos       = $multi_pago ? (array)($input['pagos'] ?? []) : [];

    // ── Determinar método de pago principal ───────────────────────────────
    if ($multi_pago && !empty($pagos)) {
        // Método principal = primer método de la lista con mayor monto
        usort($pagos, fn($a, $b) => floatval($b['monto_usd'] ?? $b['monto'] ?? 0) <=> floatval($a['monto_usd'] ?? $a['monto'] ?? 0));
        $metodo_pago_id = (int)($pagos[0]['metodo_pago_id'] ?? 1);
        $moneda_pago    = 'MIXTO';
    } else {
        $metodo_pago_id = (int)($input['metodo_pago_id'] ?? 1);
        $moneda_pago    = $input['moneda_pago'] ?? 'BS';
    }

    // ── Observaciones (efectivo / multi-pago) ─────────────────────────────
    $observaciones = '';
    if ($multi_pago && !empty($pagos)) {
        $partes = [];
        foreach ($pagos as $p) {
            $nom    = $p['metodo_nombre'] ?? ('Método #' . ($p['metodo_pago_id'] ?? '?'));
            $mon    = strtoupper($p['moneda'] ?? 'BS');
            $monto  = (float)($p['monto'] ?? 0);
            $sym    = ($mon === 'USD') ? '$' : 'Bs';
            $partes[] = "$nom: {$sym}" . number_format($monto, 2, '.', ',');
        }
        $observaciones = 'Pago mixto: ' . implode(' | ', $partes);
    } elseif (!empty($input['efectivo_recibido'])) {
        $efe_usd = (float)($input['efectivo_recibido_usd'] ?? $input['efectivo_recibido'] ?? 0);
        $efe_bs  = (float)($input['efectivo_recibido_bs']  ?? ($efe_usd * $tasa_cambio));
        $moneda_pago_key = strtoupper($moneda_pago);
        if ($moneda_pago_key === 'USD') {
            $vuelto = $efe_usd - $monto_usd;
            $observaciones = "Efectivo: \${$efe_usd}";
            if ($vuelto > 0) $observaciones .= " | Vuelto: \${$vuelto}";
        } else {
            $vuelto = $efe_bs - $monto_bs;
            $observaciones = "Efectivo: Bs" . number_format($efe_bs, 2, '.', ',');
            if ($vuelto > 0) $observaciones .= " | Vuelto: Bs" . number_format($vuelto, 2, '.', ',');
        }
    }

    $log_content .= "metodo_pago_id=$metodo_pago_id, moneda=$moneda_pago, tasa=$tasa_cambio\n";
    $log_content .= "subtotal=$subtotal, iva=$iva, total=$total\n";
    $log_content .= "obs=$observaciones\n";

    // Asegurar que la tabla venta_pagos existe (ANTES de la transacción)
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS venta_pagos (
                id             SERIAL PRIMARY KEY,
                venta_id       INTEGER      NOT NULL REFERENCES ventas(id) ON DELETE CASCADE,
                metodo_pago_id INTEGER,
                metodo_nombre  VARCHAR(200),
                moneda         VARCHAR(10)  DEFAULT 'BS',
                monto          NUMERIC(14,2) DEFAULT 0,
                monto_bs       NUMERIC(14,2) DEFAULT 0,
                monto_usd      NUMERIC(14,2) DEFAULT 0,
                es_efectivo    BOOLEAN       DEFAULT false,
                created_at     TIMESTAMPTZ   DEFAULT NOW()
            )
        ");
    } catch (Exception $e) {
        $log_content .= "Tabla venta_pagos ya existe o error: " . $e->getMessage() . "\n";
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  TRANSACCIÓN
    // ═══════════════════════════════════════════════════════════════════════
    $pdo->beginTransaction();

    try {
        // ── 1. Insertar venta ────────────────────────────────────────────
        $sql_venta = "
            INSERT INTO ventas (
                codigo_venta, cliente_id, usuario_id, metodo_pago_id,
                subtotal, iva, total, estado_venta, observaciones, created_at,
                moneda_pago, tasa_cambio, monto_bs, monto_usd
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'COMPLETADA', ?, NOW(), ?, ?, ?, ?)
            RETURNING id
        ";

        $stmt_venta = $pdo->prepare($sql_venta);
        $stmt_venta->execute([
            $codigo_venta, $cliente_id, $usuario_id, $metodo_pago_id,
            $subtotal, $iva, $total, $observaciones,
            $moneda_pago, $tasa_cambio, $monto_bs, $monto_usd
        ]);

        $venta_id = $stmt_venta->fetchColumn();
        if (!$venta_id) throw new Exception('No se pudo crear la venta (RETURNING id vacío)');

        $log_content .= "Venta insertada ID=$venta_id\n";

        // ── 2. Registrar pagos múltiples (tabla venta_pagos) ─────────────
        // La tabla ya debe existir (creada antes de la transacción)
        if ($multi_pago && !empty($pagos)) {
            try {
                $stmt_pago = $pdo->prepare("
                    INSERT INTO venta_pagos
                        (venta_id, metodo_pago_id, metodo_nombre, moneda, monto, monto_bs, monto_usd, es_efectivo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                foreach ($pagos as $p) {
                    $p_moneda = strtoupper($p['moneda'] ?? 'BS');
                    $p_monto  = (float)($p['monto']     ?? 0);
                    $p_bs     = (float)($p['monto_bs']  ?? ($p_moneda === 'BS' ? $p_monto : $p_monto * $tasa_cambio));
                    $p_usd    = (float)($p['monto_usd'] ?? ($p_moneda === 'USD' ? $p_monto : ($tasa_cambio > 0 ? $p_monto / $tasa_cambio : 0)));

                    $stmt_pago->execute([
                        $venta_id,
                        (int)($p['metodo_pago_id'] ?? 0) ?: null,
                        $p['metodo_nombre'] ?? null,
                        $p_moneda,
                        $p_monto,
                        $p_bs,
                        $p_usd,
                        !empty($p['es_efectivo'])
                    ]);
                }

                $log_content .= "Pagos múltiples registrados: " . count($pagos) . "\n";

            } catch (Exception $e) {
                // No crítico: los datos ya están en observaciones
                $log_content .= "AVISO venta_pagos: " . $e->getMessage() . "\n";
            }
        }

        // ── 3. Detalles de venta + stock + movimientos ───────────────────
        $stmt_detalle = $pdo->prepare("
            INSERT INTO detalle_ventas (
                venta_id, producto_id, cantidad, precio_unitario, subtotal,
                created_at, precio_unitario_bs, precio_unitario_usd
            ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
        ");

        $stmt_stock = $pdo->prepare(
            "UPDATE productos SET stock_actual = GREATEST(0, stock_actual - ?) WHERE id = ?"
        );

        foreach ($input['productos'] as $idx => $prod) {
            $prod_id  = (int)$prod['id'];
            $cantidad = (int)$prod['quantity'];

            // Obtener datos actuales del producto (precio y stock)
            $stmt_p = $pdo->prepare("SELECT precio_venta, nombre, stock_actual FROM productos WHERE id = ?");
            $stmt_p->execute([$prod_id]);
            $prod_db = $stmt_p->fetch(PDO::FETCH_ASSOC);

            if (!$prod_db) throw new Exception("Producto ID $prod_id no existe");

            if ((int)$prod_db['stock_actual'] < $cantidad)
                throw new Exception("Stock insuficiente: {$prod_db['nombre']} (disponible: {$prod_db['stock_actual']}, solicitado: $cantidad)");

            $precio      = (float)($prod['precio_unitario']     ?? $prod_db['precio_venta']);
            $precio_bs   = (float)($prod['precio_unitario_bs']  ?? ($precio * $tasa_cambio));
            $precio_usd  = (float)($prod['precio_unitario_usd'] ?? $precio);
            $sub_prod    = round($precio * $cantidad, 2);

            $stmt_detalle->execute([
                $venta_id, $prod_id, $cantidad,
                $precio, $sub_prod,
                $precio_bs, $precio_usd
            ]);

            $stmt_stock->execute([$cantidad, $prod_id]);

            // Movimiento de inventario (no crítico)
            try {
                $stock_post = (int)$pdo->query("SELECT stock_actual FROM productos WHERE id = $prod_id")->fetchColumn();
                $pdo->prepare("
                    INSERT INTO movimientos_inventario
                        (producto_id, tipo_movimiento, cantidad, stock_anterior, stock_actual, motivo, referencia, usuario_id, created_at)
                    VALUES (?, 'SALIDA', ?, ?, ?, ?, ?, ?, NOW())
                ")->execute([
                    $prod_id, $cantidad,
                    $prod_db['stock_actual'], $stock_post,
                    "Venta $codigo_venta",
                    $venta_id, $usuario_id
                ]);
            } catch (Exception $e) {
                $log_content .= "Mov. inventario fallido (no crítico): " . $e->getMessage() . "\n";
            }

            $log_content .= "  Producto $idx: id=$prod_id, cant=$cantidad, precio=$precio\n";
        }

        // ── 4. Commit ────────────────────────────────────────────────────
        $pdo->commit();
        $log_content .= "COMMIT OK\n=== VENTA EXITOSA ===\n\n";
        file_put_contents($log_file, $log_content, FILE_APPEND);

        // ── 5. Respuesta ─────────────────────────────────────────────────
        echo json_encode([
            'success'         => true,
            'message'         => 'Venta registrada exitosamente',
            'codigo_venta'    => $codigo_venta,
            'venta_id'        => $venta_id,
            'total'           => $total,
            'monto_bs'        => $monto_bs,
            'monto_usd'       => $monto_usd,
            'multi_pago'      => $multi_pago,
            'pagos_count'     => count($pagos),
            'productos_count' => count($input['productos']),
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        $log_content .= "ERROR (ROLLBACK): " . $e->getMessage() . "\n";
        file_put_contents($log_file, $log_content, FILE_APPEND);
        throw $e;
    }

} catch (Exception $e) {
    $log_content .= "ERROR GENERAL: " . $e->getMessage() . "\n";
    $log_content .= "TRACE: " . $e->getTraceAsString() . "\n=== FALLIDO ===\n\n";
    file_put_contents($log_file, $log_content, FILE_APPEND);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error'   => $e->getMessage(),
    ]);
}
?>