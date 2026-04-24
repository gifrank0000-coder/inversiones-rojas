-- Migration: Add complete payment system for reservas
-- Date: 2026-04-19
-- Features: Payment reference, payment verification, rate limiting

-- Add new columns for payment tracking
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS monto_adelanto DECIMAL(12,2) DEFAULT 0;
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS fecha_cuota DATE;
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS monto_restante DECIMAL(12,2) DEFAULT 0;
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS estado_pago VARCHAR(20) DEFAULT 'PENDIENTE';
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS referencia_pago VARCHAR(50);
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS metodo_pago VARCHAR(50);
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS fecha_pago TIMESTAMP;
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS comprobante_url TEXT;

-- Add rate limiting columns
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45);
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS user_agent TEXT;

-- Create index for rate limiting queries
CREATE INDEX IF NOT EXISTS idx_reservas_fecha_cliente ON reservas(fecha_reserva, cliente_id);
CREATE INDEX IF NOT EXISTS idx_reservas_ip ON reservas(ip_address) WHERE ip_address IS NOT NULL;