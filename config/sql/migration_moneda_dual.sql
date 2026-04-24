-- ============================================================
-- MIGRACIÓN PARA SISTEMA DE MONEDA DUAL (Bs/USD)
-- Inversiones Rojas
-- ============================================================

-- 1. Tabla de tasas de cambio (historial)
CREATE TABLE IF NOT EXISTS tasas_cambio (
    id SERIAL PRIMARY KEY,
    tasa NUMERIC(12, 4) NOT NULL,
    moneda_origen VARCHAR(3) DEFAULT 'USD',
    moneda_destino VARCHAR(3) DEFAULT 'VES',
    fecha_vigencia DATE NOT NULL,
    usuario_id INTEGER,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Agregar campos de moneda dual a productos
ALTER TABLE productos ADD COLUMN IF NOT EXISTS moneda_base VARCHAR(3) DEFAULT 'USD';
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_venta_bs NUMERIC(12, 2);
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_venta_usd NUMERIC(12, 2);
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_compra_bs NUMERIC(12, 2);
ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_compra_usd NUMERIC(12, 2);

-- 3. Agregar campos de moneda a ventas
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS moneda_pago VARCHAR(3) DEFAULT 'USD';
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS tasa_cambio NUMERIC(12, 4);
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS monto_bs NUMERIC(12, 2);
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS monto_usd NUMERIC(12, 2);
ALTER TABLE detalle_ventas ADD COLUMN IF NOT EXISTS precio_unitario_bs NUMERIC(12, 2);
ALTER TABLE detalle_ventas ADD COLUMN IF NOT EXISTS precio_unitario_usd NUMERIC(12, 2);

-- 4. Agregar campos de moneda a compras
ALTER TABLE compras ADD COLUMN IF NOT EXISTS moneda_factura VARCHAR(3) DEFAULT 'USD';
ALTER TABLE compras ADD COLUMN IF NOT EXISTS tasa_cambio NUMERIC(12, 4);
ALTER TABLE compras ADD COLUMN IF NOT EXISTS monto_bs NUMERIC(12, 2);
ALTER TABLE compras ADD COLUMN IF NOT EXISTS monto_usd NUMERIC(12, 2);
ALTER TABLE detalle_compras ADD COLUMN IF NOT EXISTS precio_unitario_bs NUMERIC(12, 2);
ALTER TABLE detalle_compras ADD COLUMN IF NOT EXISTS precio_unitario_usd NUMERIC(12, 2);

-- 5. Insertar tasa inicial si no hay tasas
INSERT INTO tasas_cambio (tasa, moneda_origen, moneda_destino, fecha_vigencia, observaciones)
SELECT 35.50, 'USD', 'VES', CURRENT_DATE, 'Tasa inicial'
WHERE NOT EXISTS (SELECT 1 FROM tasas_cambio LIMIT 1);

-- 6. Índices para optimización
CREATE INDEX IF NOT EXISTS idx_tasas_vigencia ON tasas_cambio(fecha_vigencia DESC);
CREATE INDEX IF NOT EXISTS idx_ventas_moneda ON ventas(moneda_pago);
CREATE INDEX IF NOT EXISTS idx_compras_moneda ON compras(moneda_factura);
