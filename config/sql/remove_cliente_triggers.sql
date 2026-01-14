-- Script para eliminar triggers/funciones que auto-crean usuarios al insertar clientes
-- ADVERTENCIA: este script elimina triggers y funciones que coincidan con patrones comunes.
-- Haz backup de la base de datos antes de ejecutarlo.

-- 1) Mostrar triggers no internos sobre la tabla clientes (informativo)
SELECT tgname, pg_get_triggerdef(oid) AS definition
FROM pg_trigger
WHERE NOT tgisinternal AND tgrelid = 'public.clientes'::regclass;

-- 2) Eliminar triggers definidos por el usuario sobre public.clientes
DO $$
DECLARE
    tr RECORD;
BEGIN
    FOR tr IN (SELECT tgname FROM pg_trigger WHERE NOT tgisinternal AND tgrelid = 'public.clientes'::regclass) LOOP
        RAISE NOTICE 'Dropping trigger on clients: %', tr.tgname;
        EXECUTE format('DROP TRIGGER IF EXISTS %I ON public.clientes CASCADE', tr.tgname);
    END LOOP;
END$$;

-- 3) Buscar funciones con nombre parecido a 'crear_usuario' y eliminarlas
SELECT proname, oid::regprocedure, pg_get_functiondef(oid) AS definition
FROM pg_proc
WHERE proname ILIKE '%crear_usuario%' OR proname ILIKE '%crear_usuario_%';

DO $$
DECLARE
    fn RECORD;
BEGIN
    FOR fn IN (SELECT oid::regprocedure AS rq FROM pg_proc WHERE proname ILIKE '%crear_usuario%' OR proname ILIKE '%crear_usuario_%') LOOP
        RAISE NOTICE 'Dropping function %', fn.rq;
        EXECUTE format('DROP FUNCTION IF EXISTS %s CASCADE', fn.rq::text);
    END LOOP;
END$$;

-- 4) Opcional: si quieres recrear constraints no-deferrable (cambia según nombres reales)
-- Nota: se recomienda revisar antes de dropear/crear constraints.

-- FIN
