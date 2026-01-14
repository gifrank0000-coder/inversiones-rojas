-- Convierte las constraints circulares a DEFERRABLE INITIALLY DEFERRED
-- Ejecuta esto en tu base de datos (psql o pgAdmin) como superusuario o dueño del esquema.

BEGIN;

-- Dropear y crear la constraint usuarios_cliente_id_fkey como DEFERRABLE
ALTER TABLE IF EXISTS public.usuarios DROP CONSTRAINT IF EXISTS usuarios_cliente_id_fkey;
ALTER TABLE public.usuarios
  ADD CONSTRAINT usuarios_cliente_id_fkey FOREIGN KEY (cliente_id)
  REFERENCES public.clientes(id)
  ON UPDATE NO ACTION
  ON DELETE NO ACTION
  DEFERRABLE INITIALLY DEFERRED;

-- Dropear y crear la constraint fk_cliente_usuario en clientes como DEFERRABLE (si existe)
ALTER TABLE IF EXISTS public.clientes DROP CONSTRAINT IF EXISTS fk_cliente_usuario;
ALTER TABLE public.clientes
  ADD CONSTRAINT fk_cliente_usuario FOREIGN KEY (usuario_id)
  REFERENCES public.usuarios(id)
  ON UPDATE NO ACTION
  ON DELETE NO ACTION
  DEFERRABLE INITIALLY DEFERRED;

COMMIT;

-- Nota: si tu base ya tiene triggers que crean usuarios al insertar clientes,
-- estas constraints deferrables permiten que ambas inserciones se vean consistentes al COMMIT.
-- Ejecuta en pgAdmin: abrir Query Tool y pegar este script, luego Run.
