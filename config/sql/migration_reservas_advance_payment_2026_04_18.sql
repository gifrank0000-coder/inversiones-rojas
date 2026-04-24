-- Migration: Add advance payment and quota date fields to reservas table
-- Date: 2026-04-18

ALTER TABLE reservas ADD COLUMN IF NOT EXISTS monto_adelanto DECIMAL(12,2) DEFAULT 0;
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS fecha_cuota DATE;
ALTER TABLE reservas ADD COLUMN IF NOT EXISTS monto_restante DECIMAL(12,2) DEFAULT 0;