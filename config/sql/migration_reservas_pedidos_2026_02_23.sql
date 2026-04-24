-- Migración: añadir detalle_reservas, ampliar reservas, registrar origen en ventas, tabla whatsapp_messages
BEGIN;

-- 1) Crear tabla detalle_reservas
CREATE TABLE IF NOT EXISTS detalle_reservas (
    id SERIAL PRIMARY KEY,
    reserva_id INTEGER NOT NULL,
    producto_id INTEGER NOT NULL,
    cantidad INTEGER NOT NULL DEFAULT 1,
    precio_unitario NUMERIC(12,2) NOT NULL,
    subtotal NUMERIC(12,2) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    CONSTRAINT fk_detalle_reserva_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE CASCADE,
    CONSTRAINT fk_detalle_reserva_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- 2) Ampliar tabla reservas con campos de control y vinculo a venta
ALTER TABLE reservas
    ADD COLUMN IF NOT EXISTS estado_reserva VARCHAR(32) DEFAULT 'activa',
    ADD COLUMN IF NOT EXISTS vendedor_id INTEGER DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS venta_id INTEGER DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP WITH TIME ZONE DEFAULT (now() + INTERVAL '7 days'),
    ADD COLUMN IF NOT EXISTS fecha_pago TIMESTAMP WITH TIME ZONE DEFAULT NULL;

ALTER TABLE reservas
    ADD CONSTRAINT fk_reserva_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE SET NULL;

ALTER TABLE reservas
    ADD CONSTRAINT fk_reserva_venta FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE SET NULL;

-- 3) Añadir columnas opcionales en ventas para referenciar origen pedido/reserva
ALTER TABLE ventas
    ADD COLUMN IF NOT EXISTS reserva_id INTEGER DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS pedido_id INTEGER DEFAULT NULL;

ALTER TABLE ventas
    ADD CONSTRAINT fk_venta_reserva FOREIGN KEY (reserva_id) REFERENCES reservas(id) ON DELETE SET NULL;

-- Si existe tabla pedidos_online, conectar fk
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name='pedidos_online') THEN
        ALTER TABLE ventas
            ADD CONSTRAINT IF NOT EXISTS fk_venta_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos_online(id) ON DELETE SET NULL;
    END IF;
END$$;

-- 4) Crear tabla para registrar envíos WhatsApp (para auditoría y webhook status)
CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id SERIAL PRIMARY KEY,
    external_sid VARCHAR(128) DEFAULT NULL,
    to_number VARCHAR(64) NOT NULL,
    from_number VARCHAR(64) NOT NULL,
    body TEXT,
    status VARCHAR(32) DEFAULT 'queued',
    payload JSONB,
    related_type VARCHAR(32), -- 'reserva','pedido','venta',etc
    related_id INTEGER DEFAULT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

COMMIT;

-- Notas:
--  - Ejecute este archivo con psql o desde su herramienta de migración: psql -U <user> -d <db> -f migration_reservas_pedidos_2026_02_23.sql
--  - Después de aplicar la migración podemos implementar los endpoints server-side que usen las nuevas tablas y columnas.
