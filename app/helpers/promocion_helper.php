<?php

function calcularPrecioConPromocion(float $precioVenta, ?string $tipoPromocion, $valor): float {
    if (empty($tipoPromocion) || $valor === null) {
        return $precioVenta;
    }

    $tipo = strtolower(trim($tipoPromocion));
    $valor = floatval($valor);

    if ($valor <= 0) {
        return $precioVenta;
    }

    if ($tipo === 'descuento' || $tipo === 'porcentaje') {
        // valor en porcentaje  (10 => 10%)
        $factor = min(100, max(0, $valor)) / 100;
        return round($precioVenta * (1 - $factor), 2);
    }

    if ($tipo === 'monto') {
        $precioDesc = $precioVenta - $valor;
        return round(max(0, $precioDesc), 2);
    }

    if ($tipo === '2x1') {
        // 2x1 no cambia el precio unitario, pero en el carrito la cantidad efectiva es 50% si pares.
        // Aplicado solo a etiqueta visual (mostramos precio venta igual)
        return round($precioVenta, 2);
    }

    return $precioVenta;
}

function obtenerPromocionActivaPorProducto(PDO $conn, int $productoId): ?array {
    $sql = "SELECT pr.tipo_promocion, pr.valor, pr.nombre AS promocion_nombre
            FROM producto_promociones pp
            INNER JOIN promociones pr ON pp.promocion_id = pr.id
            WHERE pp.producto_id = ?
              AND pr.estado = true
              AND pr.fecha_inicio <= CURRENT_DATE
              AND pr.fecha_fin >= CURRENT_DATE
            ORDER BY pr.fecha_fin ASC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$productoId]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);
    return $promo ?: null;
}

function aplicarPromocionAProducto(array $producto): array {
    $producto['precio_real'] = $producto['precio_venta'];
    $producto['promocion'] = null;

    if (!empty($producto['promo_tipo_promocion'])) {
        $precioPromo = calcularPrecioConPromocion($producto['precio_venta'], $producto['promo_tipo_promocion'], $producto['promo_valor']);
        if ($precioPromo < $producto['precio_venta']) {
            $producto['precio_real'] = $precioPromo;
            $producto['promocion'] = [
                'nombre' => $producto['promo_nombre'] ?? '',
                'tipo' => $producto['promo_tipo_promocion'],
                'valor' => $producto['promo_valor']
            ];
        }
    }

    return $producto;
}

function deduplicarProductosPorId(array $productos): array {
    $map = [];
    foreach ($productos as $prod) {
        $id = $prod['id'] ?? null;
        if ($id === null) continue;

        // Si ya existía misma id, tomar la fila con el precio real más bajo (mejor promo)
        if (!isset($map[$id])) {
            $map[$id] = $prod;
            continue;
        }

        $ex = $map[$id];
        $precioEx = floatval($ex['precio_real'] ?? $ex['precio_venta'] ?? 0);
        $precioCurr = floatval($prod['precio_real'] ?? $prod['precio_venta'] ?? 0);

        if ($precioCurr < $precioEx) {
            $map[$id] = $prod;
        }
    }

    return array_values($map);
}
