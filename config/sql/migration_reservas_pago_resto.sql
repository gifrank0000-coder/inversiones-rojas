-- Agregar campos para el pago restante (75%)
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS metodo_pago_resto VARCHAR(100);
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS referencia_pago_resto VARCHAR(100);
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS comprobante_url_resto TEXT;
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS monto_pagado_resto DECIMAL(12,2) DEFAULT 0;
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS fecha_pago_resto TIMESTAMP;