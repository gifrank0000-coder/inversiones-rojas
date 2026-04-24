-- Agregar campo moneda a metodos_pago
ALTER TABLE metodos_pago ADD COLUMN IF NOT EXISTS moneda VARCHAR(3) DEFAULT 'AMBOS';
-- 'BS' = solo bs, 'USD' = solo usd, 'AMBOS' = efectivo puede ser cualquiera, 'TARJETA' = siempre bs

-- Agregar método de pago con moneda específica si no existen
INSERT INTO metodos_pago (nombre, descripcion, moneda) VALUES 
('Efectivo $', 'Pago en dólares efectivo', 'USD'),
('Efectivo Bs', 'Pago en bolívares efectivo', 'BS')
WHERE NOT EXISTS (SELECT 1 FROM metodos_pago WHERE nombre = 'Efectivo $');

-- Agregar campo moneda_pago a tabla compras (cómo se pagó al proveedor)
ALTER TABLE compras ADD COLUMN IF NOT EXISTS moneda_pago VARCHAR(3) DEFAULT 'USD';
ALTER TABLE compras ADD COLUMN IF NOT EXISTS metodo_pago_id INTEGER REFERENCES metodos_pago(id);
