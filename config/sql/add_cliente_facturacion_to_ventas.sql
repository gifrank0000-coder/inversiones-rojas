-- Agregar cliente_facturacion_id a ventas para ventas del modal (solo clientes de facturación)
-- Ejecutar en la base de datos existente una sola vez.
-- Desde pgAdmin o: psql -U postgres -d Inversiones_Rojas -f config/sql/add_cliente_facturacion_to_ventas.sql

ALTER TABLE ventas ADD COLUMN IF NOT EXISTS cliente_facturacion_id integer;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'ventas_cliente_facturacion_id_fkey'
    ) THEN
        ALTER TABLE ventas
            ADD CONSTRAINT ventas_cliente_facturacion_id_fkey
            FOREIGN KEY (cliente_facturacion_id)
            REFERENCES clientes_facturacion (id)
            ON UPDATE NO ACTION
            ON DELETE NO ACTION;
    END IF;
END $$;

COMMENT ON COLUMN ventas.cliente_facturacion_id IS 'Cliente de facturación seleccionado en el modal de venta (cuando no se usa cliente_id)';
