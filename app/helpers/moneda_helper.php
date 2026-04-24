<?php

function getTasaCambioVigente($fecha = null) {
    static $cache = [];
    
    $fecha = $fecha ?? date('Y-m-d');
    $cacheKey = $fecha;
    
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }
    
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";user=" . DB_USER . ";password=" . DB_PASS;
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("
            SELECT tasa 
            FROM tasas_cambio 
            WHERE fecha_vigencia <= :fecha 
            ORDER BY fecha_vigencia DESC, created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([':fecha' => $fecha]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && isset($result['tasa'])) {
            $cache[$cacheKey] = floatval($result['tasa']);
        } else {
            $cache[$cacheKey] = 1.0;
        }
    } catch (Exception $e) {
        error_log("Error al obtener tasa de cambio: " . $e->getMessage());
        $cache[$cacheKey] = 1.0;
    }
    
    return $cache[$cacheKey];
}

function getTasaCambio($deprecated_param = null) {
    return getTasaCambioVigente($deprecated_param);
}

function getTasaInfo($fecha = null) {
    $fecha = $fecha ?? date('Y-m-d');
    
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";user=" . DB_USER . ";password=" . DB_PASS;
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("
            SELECT id, tasa, fecha_vigencia, created_at, observaciones
            FROM tasas_cambio 
            WHERE fecha_vigencia <= :fecha 
            ORDER BY fecha_vigencia DESC, created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([':fecha' => $fecha]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error al obtener info de tasa: " . $e->getMessage());
        return null;
    }
}

function getHistorialTasas($limit = 10) {
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";user=" . DB_USER . ";password=" . DB_PASS;
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("
            SELECT tc.*, u.nombre_completo as usuario_nombre
            FROM tasas_cambio tc
            LEFT JOIN usuarios u ON tc.usuario_id = u.id
            ORDER BY tc.fecha_vigencia DESC, tc.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error al obtener historial de tasas: " . $e->getMessage());
        return [];
    }
}

function guardarTasaCambio($tasa, $fechaVigencia = null, $usuarioId = null, $observaciones = '') {
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";user=" . DB_USER . ";password=" . DB_PASS;
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $fechaVigencia = $fechaVigencia ?? date('Y-m-d');
        $observaciones = $observaciones ?? 'Actualización de tasa';
        
        $stmt = $pdo->prepare("
            INSERT INTO tasas_cambio (tasa, moneda_origen, moneda_destino, fecha_vigencia, usuario_id, observaciones)
            VALUES (:tasa, 'USD', 'VES', :fecha, :usuario, :observaciones)
        ");
        $stmt->execute([
            ':tasa' => floatval($tasa),
            ':fecha' => $fechaVigencia,
            ':usuario' => $usuarioId,
            ':observaciones' => $observaciones
        ]);
        
        return ['success' => true, 'id' => $pdo->lastInsertId()];
    } catch (Exception $e) {
        error_log("Error al guardar tasa de cambio: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function formatearMoneda($monto, $simbolo = 'Bs') {
    $monto = floatval($monto);
    
    if ($simbolo === '$' || $simbolo === 'USD') {
        return '$' . number_format($monto, 2, ',', '.');
    }
    
    return 'Bs ' . number_format($monto, 2, ',', '.');
}

function formatearMonedaDual($monto) {
    $tasa = getTasaCambio();
    $montoBase = floatval($monto);
    $montoBs = $montoBase * $tasa;
    
    return [
        'bs' => formatearMoneda($montoBs, 'Bs'),
        'usd' => formatearMoneda($montoBase, '$'),
        'bs_raw' => $montoBs,
        'usd_raw' => $montoBase,
        'tasa' => $tasa
    ];
}

function formatearMonedaDualHTML($monto, $claseBs = 'moneda-bs', $claseUsd = 'moneda-usd') {
    $dual = formatearMonedaDual($monto);
    
    return '<span class="' . $claseUsd . '">' . $dual['usd'] . '</span>' .
           '<span class="' . $claseBs . '">' . $dual['bs'] . '</span>';
}

function convertirMonto($monto, $monedaOrigen, $monedaDestino, $tasa = null) {
    $tasa = $tasa ?? getTasaCambio();
    $monto = floatval($monto);
    
    if ($monedaOrigen === $monedaDestino) {
        return $monto;
    }
    
    if ($monedaOrigen === 'USD' && $monedaDestino === 'BS') {
        return $monto * $tasa;
    }
    
    if ($monedaOrigen === 'BS' && $monedaDestino === 'USD') {
        return $tasa > 0 ? $monto / $tasa : 0;
    }
    
    return $monto;
}

function getPrecioEnMoneda($producto, $moneda) {
    $monedaBase = $producto['moneda_base'] ?? 'USD';
    $precioVentaBs = floatval($producto['precio_venta_bs'] ?? 0);
    $precioVentaUsd = floatval($producto['precio_venta_usd'] ?? 0);
    $precioVenta = floatval($producto['precio_venta'] ?? 0);
    
    if ($moneda === $monedaBase) {
        if ($moneda === 'USD') {
            return $precioVentaUsd > 0 ? $precioVentaUsd : $precioVenta;
        }
        return $precioVentaBs > 0 ? $precioVentaBs : ($precioVenta * getTasaCambio());
    }
    
    if ($moneda === 'USD') {
        if ($monedaBase === 'BS') {
            return $precioVentaBs > 0 ? $precioVentaBs / getTasaCambio() : $precioVenta;
        }
        return $precioVentaUsd > 0 ? $precioVentaUsd : $precioVenta;
    }
    
    if ($moneda === 'BS') {
        if ($monedaBase === 'USD') {
            return $precioVentaUsd > 0 ? $precioVentaUsd * getTasaCambio() : ($precioVenta * getTasaCambio());
        }
        return $precioVentaBs > 0 ? $precioVentaBs : ($precioVenta * getTasaCambio());
    }
    
    return $precioVenta;
}
